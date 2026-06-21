<?php
/**
 * Visitor & Campaign Tracking API
 * POST /Backend/api/track.php
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'pageview';

// Read or create visitor token
$token = $_COOKIE['va_visitor_token'] ?? $input['visitor_token'] ?? null;
$isNew = false;
if (!$token || strlen($token) < 10) {
    $token = bin2hex(random_bytes(16));
    setcookie('va_visitor_token', $token, time() + (365 * 24 * 60 * 60), '/');
    $isNew = true;
}

// Read and persist UTM source
$utmSource = $input['utm_source'] ?? $_GET['utm_source'] ?? $_COOKIE['va_utm_source'] ?? null;
if (isset($input['utm_source']) && $input['utm_source'] !== '') {
    $utmSource = trim($input['utm_source']);
    setcookie('va_utm_source', $utmSource, time() + (30 * 24 * 60 * 60), '/');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['lead_source'] = $utmSource;
}

try {
    $db = Database::getConnection();

    if ($action === 'pageview') {
        // Check if visitor token already exists in db
        $stmt = $db->prepare('SELECT id, utm_source FROM visitors WHERE visitor_token = ?');
        $stmt->execute([$token]);
        $visitor = $stmt->fetch();

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($visitor) {
            // Returning visitor
            $updateSql = 'UPDATE visitors SET 
                last_seen = CURRENT_TIMESTAMP, 
                is_returning = 1,
                ip_address = ?,
                user_agent = ?
                ' . ($utmSource ? ', utm_source = COALESCE(utm_source, ?)' : '') . '
                WHERE id = ?';
            $params = [$ip, $ua];
            if ($utmSource) {
                $params[] = $utmSource;
            }
            $params[] = $visitor['id'];

            $db->prepare($updateSql)->execute($params);
        } else {
            // New visitor
            $insertSql = 'INSERT INTO visitors (visitor_token, ip_address, user_agent, utm_source, is_returning) 
                          VALUES (?, ?, ?, ?, 0)';
            $db->prepare($insertSql)->execute([$token, $ip, $ua, $utmSource]);
        }

        jsonResponse(['success' => true, 'visitor_token' => $token, 'is_returning' => !$isNew]);

    } elseif ($action === 'ping') {
        // Heartbeat ping to update session duration (add 15 seconds)
        $stmt = $db->prepare('UPDATE visitors SET session_duration = session_duration + 15 WHERE visitor_token = ?');
        $stmt->execute([$token]);
        jsonResponse(['success' => true]);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
