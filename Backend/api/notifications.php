<?php
/**
 * Real-time notifications SSE Stream
 * GET /Backend/api/notifications.php
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering on Nginx

// Disable PHP output buffering
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_implicit_flush(true);

$db = Database::getConnection();
$lastCheckTime = date('Y-m-d H:i:s');

// Keep connection open for up to 50 seconds (browser will reconnect automatically)
$endTime = time() + 50;

while (time() < $endTime) {
    try {
        $now = date('Y-m-d H:i:s');

        // Check for new leads created since last check
        $stmtLeads = $db->prepare("SELECT * FROM leads WHERE created_at > ? ORDER BY id ASC");
        $stmtLeads->execute([$lastCheckTime]);
        $newLeads = $stmtLeads->fetchAll();

        foreach ($newLeads as $lead) {
            $type = 'new_lead';
            $title = 'New Lead Arrived! 🚗';
            $message = $lead['full_name'] . ' is interested in ' . $lead['interested_vehicle'];

            if ($lead['inquiry_type'] === 'book_inspection') {
                $type = 'inspection';
                $title = 'Inspection Requested! 📅';
            } elseif ($lead['source'] === 'whatsapp') {
                $type = 'whatsapp';
                $title = 'WhatsApp Engagement! 💬';
            }

            echo "data: " . json_encode([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'lead_id' => $lead['id'],
                'time' => date('H:i:s')
            ], JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        // Check for newly upgraded HOT leads since last check
        $stmtHot = $db->prepare("SELECT * FROM leads WHERE lead_category = 'hot' AND updated_at > ? AND created_at <= ? ORDER BY id ASC");
        $stmtHot->execute([$lastCheckTime, $lastCheckTime]);
        $upgradedHot = $stmtHot->fetchAll();

        foreach ($upgradedHot as $hotLead) {
            echo "data: " . json_encode([
                'type' => 'hot_lead',
                'title' => 'Hot Lead Detected! 🔥',
                'message' => $hotLead['full_name'] . ' is highly engaged (' . $hotLead['lead_score'] . ' points)!',
                'lead_id' => $hotLead['id'],
                'time' => date('H:i:s')
            ], JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        $lastCheckTime = $now;
        
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

    } catch (Throwable $e) {
        // Stream error for debugging
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        flush();
    }

    sleep(3); // Poll database every 3 seconds
}

echo "data: reconnect\n\n";
flush();
