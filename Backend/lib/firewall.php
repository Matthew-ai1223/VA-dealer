<?php
/**
 * VA Auto Sales — Application-Layer Firewall
 *
 * Protections:
 *  - IP blocklist (database-driven, with TTL expiry)
 *  - Rate limiting (file-based sliding window, no APCu dependency)
 *  - SQL injection pattern detection
 *  - XSS pattern detection
 *  - Bad bot / scanner User-Agent blocking
 *  - Admin brute-force tracking (call reportFailedLogin() on bad logins)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// ─── Configuration ────────────────────────────────────────────────────────────
define('FW_RATE_LIMIT',       120);  // max requests per IP per minute
define('FW_BRUTE_LIMIT',      5);    // failed logins before IP block
define('FW_BRUTE_WINDOW',     600);  // brute-force tracking window (seconds)
define('FW_BLOCK_DURATION',   3600); // block duration in seconds (1 hour)
define('FW_RATE_DIR',         sys_get_temp_dir() . '/va_fw_rate/');

// ─── Public Entry Point ───────────────────────────────────────────────────────

/**
 * Run the full firewall check. Call this at the top of any public PHP file.
 * Silently skips if the DB tables don't exist yet (e.g. on fresh install).
 */
function runFirewall(): void
{
    try {
        $ip = _fwClientIp();

        // 1. Whitelist — never block the local machine
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return;
        }

        // 2. DB blocklist check
        if (_fwIsBlocked($ip)) {
            _fwDeny($ip, 'IP_BLOCKED', 'IP is on the blocklist');
        }

        // 3. Rate limiting
        if (_fwRateLimitExceeded($ip)) {
            _fwBlock($ip, 'rate_limit', FW_BLOCK_DURATION);
            _fwDeny($ip, 'RATE_LIMIT', 'Too many requests');
        }

        // 4. Bad bot / scanner detection
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (_fwIsBadBot($ua)) {
            _fwBlock($ip, 'bad_bot', FW_BLOCK_DURATION * 24);
            _fwDeny($ip, 'BAD_BOT', substr($ua, 0, 120));
        }

        // 5. SQL injection detection
        $sqliTarget = _fwInputString();
        if (_fwIsSqli($sqliTarget)) {
            _fwBlock($ip, 'sqli_detected', FW_BLOCK_DURATION);
            _fwDeny($ip, 'SQLI', substr($sqliTarget, 0, 200));
        }

        // 6. XSS detection
        if (_fwIsXss($sqliTarget)) {
            _fwBlock($ip, 'xss_detected', FW_BLOCK_DURATION);
            _fwDeny($ip, 'XSS', substr($sqliTarget, 0, 200));
        }

    } catch (Throwable $e) {
        // Never crash the site due to firewall errors
        error_log('[Firewall] Error: ' . $e->getMessage());
    }
}

/**
 * Call after a failed admin login attempt.
 * Blocks the IP after FW_BRUTE_LIMIT failures in FW_BRUTE_WINDOW seconds.
 */
function reportFailedLogin(): void
{
    try {
        $ip = _fwClientIp();
        $db = Database::getConnection();

        // Log the event
        _fwLogEvent($ip, 'FAILED_LOGIN', 'Admin login failure', null);

        // Count recent failures
        $since = date('Y-m-d H:i:s', time() - FW_BRUTE_WINDOW);
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM security_events
             WHERE ip = ? AND event_type = 'FAILED_LOGIN' AND created_at >= ?"
        );
        $stmt->execute([$ip, $since]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= FW_BRUTE_LIMIT) {
            _fwBlock($ip, 'brute_force', FW_BLOCK_DURATION * 6);
            error_log("[Firewall] Brute-force block applied: $ip after $count failed logins.");
        }
    } catch (Throwable $e) {
        error_log('[Firewall] reportFailedLogin error: ' . $e->getMessage());
    }
}

/**
 * Manually block an IP address.
 */
function firewallBlockIp(string $ip, string $reason = 'manual', int $durationSeconds = 0): bool
{
    return _fwBlock($ip, $reason, $durationSeconds);
}

/**
 * Manually unblock an IP address.
 */
function firewallUnblockIp(string $ip): bool
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM firewall_blocks WHERE ip = ?');
        return $stmt->execute([$ip]);
    } catch (Throwable $e) {
        error_log('[Firewall] unblock error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get recent security events (for dashboard).
 */
function firewallGetEvents(int $limit = 50): array
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM security_events ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Get all active blocks (for dashboard).
 */
function firewallGetBlocks(): array
{
    try {
        $db = Database::getConnection();
        $stmt = $db->query(
            "SELECT * FROM firewall_blocks
             WHERE expires_at IS NULL OR expires_at > NOW()
             ORDER BY blocked_at DESC"
        );
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Get a quick stats summary for the dashboard.
 */
function firewallStats(): array
{
    try {
        $db = Database::getConnection();
        $blockedIps     = (int) $db->query("SELECT COUNT(*) FROM firewall_blocks WHERE expires_at IS NULL OR expires_at > NOW()")->fetchColumn();
        $events24h      = (int) $db->query("SELECT COUNT(*) FROM security_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $eventsTotal    = (int) $db->query("SELECT COUNT(*) FROM security_events")->fetchColumn();
        $topStmt        = $db->query("SELECT ip, COUNT(*) as c FROM security_events GROUP BY ip ORDER BY c DESC LIMIT 5");
        $topAttackers   = $topStmt->fetchAll();
        $byType         = $db->query("SELECT event_type, COUNT(*) as c FROM security_events GROUP BY event_type ORDER BY c DESC")->fetchAll();
        return compact('blockedIps', 'events24h', 'eventsTotal', 'topAttackers', 'byType');
    } catch (Throwable $e) {
        return ['blockedIps' => 0, 'events24h' => 0, 'eventsTotal' => 0, 'topAttackers' => [], 'byType' => []];
    }
}

// ─── Internal Helpers ─────────────────────────────────────────────────────────

function _fwClientIp(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $v = $_SERVER[$key] ?? '';
        if ($v !== '') {
            return trim(explode(',', $v)[0]);
        }
    }
    return '0.0.0.0';
}

function _fwIsBlocked(string $ip): bool
{
    $db = Database::getConnection();
    $stmt = $db->prepare(
        "SELECT id FROM firewall_blocks
         WHERE ip = ? AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt->execute([$ip]);
    return (bool) $stmt->fetch();
}

function _fwBlock(string $ip, string $reason, int $durationSeconds = 0): bool
{
    try {
        $db      = Database::getConnection();
        $expires = $durationSeconds > 0 ? date('Y-m-d H:i:s', time() + $durationSeconds) : null;
        $stmt    = $db->prepare(
            "INSERT INTO firewall_blocks (ip, reason, expires_at, hit_count)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
               reason = VALUES(reason),
               expires_at = CASE WHEN VALUES(expires_at) IS NULL THEN NULL ELSE GREATEST(IFNULL(expires_at, NOW()), VALUES(expires_at)) END,
               hit_count = hit_count + 1"
        );
        return $stmt->execute([$ip, $reason, $expires]);
    } catch (Throwable $e) {
        error_log('[Firewall] block error: ' . $e->getMessage());
        return false;
    }
}

function _fwDeny(string $ip, string $eventType, string $detail): never
{
    _fwLogEvent($ip, $eventType, $detail, null);
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Denied</title>'
        . '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f172a;color:#e2e8f0}'
        . '.box{text-align:center;padding:48px;border:1px solid #1e3a8a;border-radius:16px;background:#0f172a;max-width:480px}'
        . 'h1{color:#f59e0b;font-size:3rem;margin:0}h2{color:#94a3b8;margin-top:8px}p{color:#64748b;margin-top:16px}'
        . 'a{color:#3b82f6}</style></head>'
        . '<body><div class="box"><h1>&#x1F6AB;</h1><h2>Access Denied</h2>'
        . '<p>Your request has been blocked by our security firewall.</p>'
        . '<p><a href="javascript:history.back()">Go back</a></p></div></body></html>';
    exit;
}

function _fwLogEvent(string $ip, string $eventType, ?string $detail, ?string $extra): void
{
    try {
        $db   = Database::getConnection();
        $url  = ($_SERVER['REQUEST_URI'] ?? '');
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
        $stmt = $db->prepare(
            'INSERT INTO security_events (ip, event_type, detail, url, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$ip, $eventType, $detail, substr($url, 0, 500), $ua]);
    } catch (Throwable $e) {
        // Silent — don't crash if table doesn't exist yet
    }
}

function _fwRateLimitExceeded(string $ip): bool
{
    if (!is_dir(FW_RATE_DIR)) {
        @mkdir(FW_RATE_DIR, 0700, true);
    }
    $file = FW_RATE_DIR . md5($ip) . '.json';
    $now  = time();
    $window = 60; // 1 minute

    $data = ['ts' => []];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw) {
            $data = json_decode($raw, true) ?? ['ts' => []];
        }
    }

    // Remove timestamps outside the window
    $data['ts'] = array_values(array_filter($data['ts'], fn($t) => $t >= $now - $window));
    $data['ts'][] = $now;

    @file_put_contents($file, json_encode($data), LOCK_EX);

    return count($data['ts']) > FW_RATE_LIMIT;
}

function _fwIsBadBot(string $ua): bool
{
    if ($ua === '') return false;
    $patterns = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'zgrab', 'nuclei',
        'python-requests', 'go-http-client', 'java/', 'libwww-perl',
        'curl/', 'wget/', 'scrapy', 'dirbuster', 'gobuster', 'wfuzz',
        'hydra', 'burpsuite', 'havij', 'acunetix', 'nessus', 'openvas',
        'metasploit', 'zgrab', 'phantomjs', 'headlesschrome'
    ];
    $uaLower = strtolower($ua);
    foreach ($patterns as $p) {
        if (str_contains($uaLower, $p)) {
            return true;
        }
    }
    return false;
}

function _fwIsSqli(string $input): bool
{
    if ($input === '') return false;
    $patterns = [
        "/(\bUNION\b.+\bSELECT\b)/i",
        "/(\bSELECT\b.+\bFROM\b)/i",
        "/(\bINSERT\b.+\bINTO\b)/i",
        "/(\bDROP\b.+\bTABLE\b)/i",
        "/(\bDELETE\b.+\bFROM\b)/i",
        "/(\bUPDATE\b.+\bSET\b)/i",
        "/(--\s*$|;\s*--)/m",
        "/(\bOR\b\s+[\w'\"]+\s*=\s*[\w'\"]+)/i",
        "/(\bAND\b\s+[\w'\"]+\s*=\s*[\w'\"]+\s*--)/i",
        "/(SLEEP\s*\(\s*\d+\s*\))/i",
        "/(BENCHMARK\s*\()/i",
        "/(WAITFOR\s+DELAY)/i",
        "/(LOAD_FILE\s*\()/i",
        "/(INTO\s+OUTFILE)/i",
        "/(INFORMATION_SCHEMA)/i",
        "/(\bEXEC\b\s*\()/i",
        "/(CAST\s*\(.+AS\s+)/i",
        "/(CONVERT\s*\(.+USING\s+)/i",
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $input)) {
            return true;
        }
    }
    return false;
}

function _fwIsXss(string $input): bool
{
    if ($input === '') return false;
    $decoded = html_entity_decode(urldecode($input), ENT_QUOTES);
    $patterns = [
        "/<script[\s>]/i",
        "/javascript\s*:/i",
        "/on\w+\s*=/i",
        "/<iframe/i",
        "/<object/i",
        "/<embed/i",
        "/document\s*\.\s*cookie/i",
        "/document\s*\.\s*location/i",
        "/eval\s*\(/i",
        "/expression\s*\(/i",
        "/vbscript\s*:/i",
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $decoded)) {
            return true;
        }
    }
    return false;
}

function _fwInputString(): string
{
    $parts = [
        $_SERVER['QUERY_STRING'] ?? '',
        $_SERVER['REQUEST_URI']  ?? '',
    ];
    // Include POST body for text content types only
    $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    if (str_contains($ct, 'application/x-www-form-urlencoded') || str_contains($ct, 'text/')) {
        $parts[] = file_get_contents('php://input');
    }
    // Add individual GET/POST values
    foreach (array_merge($_GET, $_POST) as $v) {
        if (is_string($v)) $parts[] = $v;
    }
    return implode(' ', $parts);
}
