<?php
/**
 * Cron trigger for lead nurturing follow-up sequence
 * CLI: php Backend/api/cron-nurture.php
 * Web: GET /Backend/api/cron-nurture.php
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nurture.php';

// Allow CLI execution or admin verification
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../lib/auth.php';
    requireAdmin();
}

$logs = runLeadNurturing();

if (php_sapi_name() === 'cli') {
    echo "Lead nurturing sequence run complete.\n";
    echo "Follow-ups sent: " . count($logs) . "\n";
    foreach ($logs as $log) {
        echo sprintf(" - Lead #%d: %s sent to %s (Sent: %s)\n", $log['lead_id'], $log['status'], $log['email'], $log['sent'] ? 'Yes' : 'No');
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Lead nurturing sequence run complete.',
        'sent_count' => count($logs),
        'logs' => $logs
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
