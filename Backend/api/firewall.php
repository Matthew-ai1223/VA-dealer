<?php
/**
 * Firewall Management API (admin-only)
 * GET  ?action=events  — recent security events
 * GET  ?action=blocks  — active blocked IPs
 * GET  ?action=stats   — summary stats
 * POST ?action=block   — block an IP {ip, reason, duration}
 * POST ?action=unblock — unblock an IP {ip}
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/firewall.php';

requireAdminApi();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    if ($action === 'events') {
        $limit = min((int) ($_GET['limit'] ?? 50), 200);
        jsonResponse(['success' => true, 'data' => firewallGetEvents($limit)]);
    }

    if ($action === 'blocks') {
        jsonResponse(['success' => true, 'data' => firewallGetBlocks()]);
    }

    if ($action === 'stats') {
        jsonResponse(['success' => true, 'data' => firewallStats()]);
    }
}

if ($method === 'POST') {
    if ($action === 'block') {
        $ip       = trim($input['ip'] ?? '');
        $reason   = trim($input['reason'] ?? 'manual');
        $duration = (int) ($input['duration'] ?? 0); // seconds, 0 = permanent

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            jsonResponse(['success' => false, 'message' => 'Invalid IP address.'], 400);
        }

        $ok = firewallBlockIp($ip, $reason, $duration);
        jsonResponse(['success' => $ok, 'message' => $ok ? "IP $ip blocked." : 'Failed to block IP.']);
    }

    if ($action === 'unblock') {
        $ip = trim($input['ip'] ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            jsonResponse(['success' => false, 'message' => 'Invalid IP address.'], 400);
        }
        $ok = firewallUnblockIp($ip);
        jsonResponse(['success' => $ok, 'message' => $ok ? "IP $ip unblocked." : 'IP not found.']);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid action or method.'], 400);
