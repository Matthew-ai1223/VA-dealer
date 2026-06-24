<?php
$pageTitle = 'System Health & Security';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../lib/firewall.php';

$db = Database::getConnection();

// ── Server Metrics ──────────────────────────────────────────────────────────
$phpVersion   = PHP_VERSION;
$serverSoft   = $_SERVER['SERVER_SOFTWARE'] ?? php_uname('s') . ' ' . php_uname('r');
$memLimit     = ini_get('memory_limit');
$uploadMax    = ini_get('upload_max_filesize');
$maxExecTime  = ini_get('max_execution_time') . 's';
$diskTotal    = disk_total_space(__DIR__);
$diskFree     = disk_free_space(__DIR__);
$diskUsed     = $diskTotal - $diskFree;
$diskPct      = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;
$memUsageMb   = round(memory_get_usage(true) / 1024 / 1024, 1);

function fmtBytes(float $b): string {
    if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
    if ($b >= 1048576)    return round($b / 1048576, 1) . ' MB';
    return round($b / 1024, 1) . ' KB';
}

// ── Database Metrics ────────────────────────────────────────────────────────
$dbStatus = 'OK';
$tables   = [];
try {
    $dbName = $db->query('SELECT DATABASE()')->fetchColumn();
    $tblStmt = $db->query(
        "SELECT TABLE_NAME as name,
                TABLE_ROWS as `rows`,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) as size_kb
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = '$dbName'
         ORDER BY TABLE_ROWS DESC"
    );
    $tables = $tblStmt->fetchAll();
    $dbSizeKb = array_sum(array_column($tables, 'size_kb'));
} catch (Throwable $e) {
    $dbStatus = 'Error: ' . $e->getMessage();
    $dbSizeKb = 0;
}

// ── Firewall Stats ──────────────────────────────────────────────────────────
$fwStats  = firewallStats();
$fwBlocks = firewallGetBlocks();
$fwEvents = firewallGetEvents(100);

// ── Application Checks ──────────────────────────────────────────────────────
$checks = [
    ['label' => 'PHP Version ≥ 8.0',   'pass' => version_compare(PHP_VERSION, '8.0', '>=')],
    ['label' => 'PDO MySQL Extension',  'pass' => extension_loaded('pdo_mysql')],
    ['label' => 'OpenSSL Extension',    'pass' => extension_loaded('openssl')],
    ['label' => 'Mbstring Extension',   'pass' => extension_loaded('mbstring')],
    ['label' => 'Uploads Directory',    'pass' => is_writable(__DIR__ . '/../uploads/cars')],
    ['label' => 'Config File Exists',   'pass' => is_file(__DIR__ . '/../config/app.php')],
    ['label' => 'VAPID Keys Present',   'pass' => is_file(__DIR__ . '/../config/push.php')],
    ['label' => 'Firewall Tables',      'pass' => $dbStatus === 'OK'],
];

// ── Security Hardening Checks ───────────────────────────────────────────────
$hardening = [
    ['label' => 'display_errors is Off',       'pass' => ini_get('display_errors') == '0'],
    ['label' => 'expose_php is Off',            'pass' => ini_get('expose_php') == ''],
    ['label' => 'session.cookie_httponly',      'pass' => (bool) ini_get('session.cookie_httponly')],
    ['label' => 'HTTPS (production)',           'pass' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'],
];

$eventTypeColors = [
    'SQLI'         => '#ef4444',
    'XSS'          => '#f97316',
    'RATE_LIMIT'   => '#eab308',
    'BAD_BOT'      => '#8b5cf6',
    'FAILED_LOGIN' => '#ec4899',
    'IP_BLOCKED'   => '#64748b',
];
?>

<style>
/* ── Health Dashboard Styles ── */
.health-page { padding: 32px 0 60px; }
.health-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 28px; }
.health-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(15,23,42,.05);
}
.health-card--wide { grid-column: 1 / -1; }
.health-card__title {
    font-size: .75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #64748b; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}
.health-card__title svg { opacity: .7; }

/* Stat pills */
.stat-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }
.stat-row:last-child { border: none; }
.stat-row__label { color: #475569; }
.stat-row__value { font-weight: 600; color: #0f172a; }

/* Progress bar */
.prog-bar { background: #e2e8f0; border-radius: 99px; height: 8px; overflow: hidden; margin-top: 6px; }
.prog-bar__fill { height: 100%; border-radius: 99px; transition: width .4s; }

/* KPI row */
.kpi-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.kpi-card {
    flex: 1; min-width: 140px; background: #fff; border: 1px solid #e2e8f0;
    border-radius: 12px; padding: 20px; text-align: center;
    box-shadow: 0 1px 6px rgba(15,23,42,.04);
}
.kpi-card__num  { font-size: 2rem; font-weight: 800; line-height: 1.1; }
.kpi-card__lbl  { font-size: .75rem; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: .5px; }
.kpi-card--red  .kpi-card__num { color: #ef4444; }
.kpi-card--amber .kpi-card__num { color: #f59e0b; }
.kpi-card--green .kpi-card__num { color: #22c55e; }
.kpi-card--blue  .kpi-card__num { color: #3b82f6; }

/* Check badges */
.checks-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.check-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border-radius: 9px; font-size: .875rem; font-weight: 500;
    background: #f0fdf4; color: #16a34a;
    border: 1px solid #bbf7d0;
}
.check-item--fail { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.check-item__dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

/* Events table */
.events-table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
.events-table th { text-align: left; padding: 8px 12px; background: #f8fafc; font-size: .7rem; text-transform: uppercase; letter-spacing: .6px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.events-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.events-table tr:hover td { background: #f8fafc; }
.event-badge {
    display: inline-block; padding: 2px 8px; border-radius: 99px;
    font-size: .65rem; font-weight: 700; letter-spacing: .5px; color: #fff;
}
.events-scroll { max-height: 420px; overflow-y: auto; border-radius: 10px; border: 1px solid #e2e8f0; }

/* IP block form */
.fw-action-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.fw-action-form .form-group { flex: 1; min-width: 140px; }
.fw-action-form label { font-size: .75rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px; }
.fw-action-form input, .fw-action-form select {
    width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0;
    border-radius: 8px; font-size: .875rem; font-family: inherit;
    background: #fff; color: #0f172a;
}
.fw-action-form input:focus, .fw-action-form select:focus {
    outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30,64,175,.12);
}
.alert-flash { padding: 12px 16px; border-radius: 9px; font-size: .875rem; font-weight: 500; margin-bottom: 16px; display: none; }
.alert-flash--ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.alert-flash--err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Blocks list */
.blocks-list { display: flex; flex-wrap: wrap; gap: 8px; }
.block-chip {
    display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 99px;
    font-size: .8125rem; color: #dc2626; font-weight: 500;
}
.block-chip button {
    background: none; border: none; cursor: pointer; color: #dc2626;
    font-size: 1rem; line-height: 1; padding: 0 2px; opacity: .7;
}
.block-chip button:hover { opacity: 1; }

/* Section header */
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.section-header h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.section-header p  { color: #64748b; font-size: .875rem; margin: 4px 0 0; }

@media (max-width: 640px) {
    .kpi-row { gap: 10px; }
    .kpi-card { min-width: 100px; padding: 14px; }
    .kpi-card__num { font-size: 1.5rem; }
}
</style>

<main>
<div class="container health-page">

    <div class="section-header">
        <div>
            <h1>🛡️ System Health &amp; Security</h1>
            <p>Live overview of server health, firewall activity, and security events.</p>
        </div>
        <button class="btn btn--outline" onclick="location.reload()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Refresh
        </button>
    </div>

    <!-- KPI Row -->
    <div class="kpi-row">
        <div class="kpi-card kpi-card--red">
            <div class="kpi-card__num"><?= number_format($fwStats['blockedIps']) ?></div>
            <div class="kpi-card__lbl">Blocked IPs</div>
        </div>
        <div class="kpi-card kpi-card--amber">
            <div class="kpi-card__num"><?= number_format($fwStats['events24h']) ?></div>
            <div class="kpi-card__lbl">Events (24h)</div>
        </div>
        <div class="kpi-card kpi-card--blue">
            <div class="kpi-card__num"><?= number_format($fwStats['eventsTotal']) ?></div>
            <div class="kpi-card__lbl">Total Events</div>
        </div>
        <div class="kpi-card kpi-card--green">
            <div class="kpi-card__num"><?= count($tables) ?></div>
            <div class="kpi-card__lbl">DB Tables</div>
        </div>
        <div class="kpi-card kpi-card--blue">
            <div class="kpi-card__num"><?= $diskPct ?>%</div>
            <div class="kpi-card__lbl">Disk Used</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="health-grid">

        <!-- Server Info -->
        <div class="health-card">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                Server Environment
            </div>
            <div class="stat-row"><span class="stat-row__label">PHP Version</span><span class="stat-row__value"><?= htmlspecialchars($phpVersion) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Server</span><span class="stat-row__value" style="font-size:.75rem"><?= htmlspecialchars(substr($serverSoft, 0, 40)) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Memory Limit</span><span class="stat-row__value"><?= htmlspecialchars($memLimit) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Memory Usage</span><span class="stat-row__value"><?= $memUsageMb ?> MB</span></div>
            <div class="stat-row"><span class="stat-row__label">Upload Max</span><span class="stat-row__value"><?= htmlspecialchars($uploadMax) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Max Exec Time</span><span class="stat-row__value"><?= htmlspecialchars($maxExecTime) ?></span></div>
            <div style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:4px">
                    <span>Disk Used</span>
                    <span><?= fmtBytes($diskUsed) ?> / <?= fmtBytes($diskTotal) ?></span>
                </div>
                <div class="prog-bar">
                    <div class="prog-bar__fill" style="width:<?= $diskPct ?>%;background:<?= $diskPct > 90 ? '#ef4444' : ($diskPct > 75 ? '#f59e0b' : '#22c55e') ?>"></div>
                </div>
            </div>
        </div>

        <!-- Database -->
        <div class="health-card">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                Database
            </div>
            <div class="stat-row"><span class="stat-row__label">Status</span><span class="stat-row__value" style="color:<?= $dbStatus === 'OK' ? '#22c55e' : '#ef4444' ?>"><?= htmlspecialchars($dbStatus) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Tables</span><span class="stat-row__value"><?= count($tables) ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Total Size</span><span class="stat-row__value"><?= number_format($dbSizeKb, 1) ?> KB</span></div>
            <?php foreach ($tables as $tbl): ?>
            <div class="stat-row">
                <span class="stat-row__label" style="font-size:.8rem"><?= htmlspecialchars($tbl['name']) ?></span>
                <span class="stat-row__value" style="font-size:.8rem"><?= number_format((int)$tbl['rows']) ?> rows</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Firewall Stats -->
        <div class="health-card">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Firewall Activity
            </div>
            <div class="stat-row"><span class="stat-row__label">Active Blocks</span><span class="stat-row__value" style="color:#ef4444"><?= $fwStats['blockedIps'] ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Events (24h)</span><span class="stat-row__value"><?= $fwStats['events24h'] ?></span></div>
            <div class="stat-row"><span class="stat-row__label">Total Events</span><span class="stat-row__value"><?= $fwStats['eventsTotal'] ?></span></div>
            <?php if (!empty($fwStats['byType'])): ?>
            <div style="margin-top:14px;font-size:.75rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">By Type</div>
            <?php foreach ($fwStats['byType'] as $t): $color = $eventTypeColors[$t['event_type']] ?? '#64748b'; ?>
            <div class="stat-row">
                <span class="stat-row__label"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $color ?>;margin-right:6px"></span><?= htmlspecialchars($t['event_type']) ?></span>
                <span class="stat-row__value"><?= (int)$t['c'] ?></span>
            </div>
            <?php endforeach; endif; ?>
            <?php if (!empty($fwStats['topAttackers'])): ?>
            <div style="margin-top:14px;font-size:.75rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Top Attacking IPs</div>
            <?php foreach ($fwStats['topAttackers'] as $atk): ?>
            <div class="stat-row">
                <span class="stat-row__label" style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($atk['ip']) ?></span>
                <span class="stat-row__value"><?= (int)$atk['c'] ?> events</span>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Application Health Checks -->
        <div class="health-card">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Application Checks
            </div>
            <div class="checks-grid">
                <?php foreach ($checks as $c): ?>
                <div class="check-item <?= $c['pass'] ? '' : 'check-item--fail' ?>">
                    <div class="check-item__dot"></div>
                    <?= htmlspecialchars($c['label']) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:20px;font-size:.75rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Security Hardening</div>
            <div class="checks-grid">
                <?php foreach ($hardening as $c): ?>
                <div class="check-item <?= $c['pass'] ? '' : 'check-item--fail' ?>">
                    <div class="check-item__dot"></div>
                    <?= htmlspecialchars($c['label']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Manual IP Block/Unblock -->
        <div class="health-card">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Firewall Controls
            </div>
            <div id="fw-flash" class="alert-flash"></div>
            <p style="font-size:.875rem;color:#64748b;margin-bottom:14px">Manually block or unblock an IP address.</p>
            <div class="fw-action-form" id="fw-block-form">
                <div class="form-group">
                    <label for="fw-ip">IP Address</label>
                    <input type="text" id="fw-ip" placeholder="e.g. 192.168.1.1" pattern="^[\d\.:a-fA-F]+$">
                </div>
                <div class="form-group">
                    <label for="fw-reason">Reason</label>
                    <input type="text" id="fw-reason" placeholder="manual" value="manual">
                </div>
                <div class="form-group">
                    <label for="fw-duration">Duration</label>
                    <select id="fw-duration">
                        <option value="3600">1 Hour</option>
                        <option value="86400">24 Hours</option>
                        <option value="604800">7 Days</option>
                        <option value="0">Permanent</option>
                    </select>
                </div>
                <button class="btn btn--primary" onclick="fwBlockIp()">Block IP</button>
                <button class="btn btn--outline" onclick="fwUnblockIp()">Unblock</button>
            </div>

            <?php if (!empty($fwBlocks)): ?>
            <div style="margin-top:20px">
                <div style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Active Blocks</div>
                <div class="blocks-list" id="blocks-list">
                    <?php foreach ($fwBlocks as $blk): ?>
                    <div class="block-chip" data-ip="<?= htmlspecialchars($blk['ip']) ?>">
                        <span><?= htmlspecialchars($blk['ip']) ?> <small style="opacity:.7">(<?= htmlspecialchars($blk['reason']) ?>)</small></span>
                        <button onclick="fwUnblockChip(this)" title="Unblock">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Security Events Log -->
        <div class="health-card health-card--wide">
            <div class="health-card__title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Security Event Log
                <span style="margin-left:auto;font-size:.75rem;background:#f1f5f9;padding:2px 8px;border-radius:99px;color:#475569">Last 100</span>
            </div>
            <?php if (empty($fwEvents)): ?>
                <p style="color:#64748b;font-size:.875rem;text-align:center;padding:20px 0">No security events recorded yet. Events will appear here when the firewall detects threats.</p>
            <?php else: ?>
            <div class="events-scroll">
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Time</th><th>IP</th><th>Event</th><th>Detail</th><th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fwEvents as $ev):
                            $color = $eventTypeColors[$ev['event_type']] ?? '#64748b';
                        ?>
                        <tr>
                            <td style="white-space:nowrap;color:#64748b;font-size:.75rem"><?= htmlspecialchars(substr($ev['created_at'], 0, 16)) ?></td>
                            <td><code style="font-size:.8rem"><?= htmlspecialchars($ev['ip']) ?></code></td>
                            <td><span class="event-badge" style="background:<?= $color ?>"><?= htmlspecialchars($ev['event_type']) ?></span></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($ev['detail'] ?? '') ?>"><?= htmlspecialchars(substr($ev['detail'] ?? '—', 0, 60)) ?></td>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;color:#64748b" title="<?= htmlspecialchars($ev['url'] ?? '') ?>"><?= htmlspecialchars(substr($ev['url'] ?? '—', 0, 60)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.health-grid -->
</div><!-- /.container -->
</main>

<script>
const FW_API = '<?= url('Backend/api/firewall.php') ?>';

function showFlash(msg, ok) {
    const el = document.getElementById('fw-flash');
    el.textContent = msg;
    el.className = 'alert-flash ' + (ok ? 'alert-flash--ok' : 'alert-flash--err');
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function fwBlockIp() {
    const ip = document.getElementById('fw-ip').value.trim();
    const reason = document.getElementById('fw-reason').value.trim() || 'manual';
    const duration = parseInt(document.getElementById('fw-duration').value);
    if (!ip) { showFlash('Please enter an IP address.', false); return; }
    const res = await fetch(FW_API + '?action=block', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ip, reason, duration })
    });
    const data = await res.json();
    showFlash(data.message, data.success);
    if (data.success) setTimeout(() => location.reload(), 1500);
}

async function fwUnblockIp() {
    const ip = document.getElementById('fw-ip').value.trim();
    if (!ip) { showFlash('Please enter an IP address.', false); return; }
    const res = await fetch(FW_API + '?action=unblock', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ip })
    });
    const data = await res.json();
    showFlash(data.message, data.success);
    if (data.success) setTimeout(() => location.reload(), 1500);
}

async function fwUnblockChip(btn) {
    const chip = btn.closest('.block-chip');
    const ip = chip.dataset.ip;
    const res = await fetch(FW_API + '?action=unblock', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ip })
    });
    const data = await res.json();
    if (data.success) chip.remove();
    showFlash(data.message, data.success);
}

// Auto-refresh the events section every 30 seconds
setInterval(() => {
    fetch(FW_API + '?action=stats')
        .then(r => r.json())
        .catch(() => {});
}, 30000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
