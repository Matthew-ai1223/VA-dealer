<?php
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
requireAdmin();

$config = appConfig();
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Admin') ?> | <?= sanitize($config['site_name']) ?></title>
    <link rel="icon" href="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>" type="image/jpeg">
    <link rel="stylesheet" href="<?= sanitize(url('Frontend/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= sanitize(url('Backend/admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container admin-header__inner">
            <a href="dashboard.php" class="admin-header__brand">
                <img src="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>" alt="<?= sanitize($config['site_name']) ?>" class="admin-header__logo" height="36">
                <span>Admin</span>
            </a>
            <nav class="admin-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="pipeline.php">Pipeline</a>
                <a href="campaigns.php">Campaigns</a>
                <a href="leads.php">Leads<?php
                    try {
                        require_once __DIR__ . '/../../models/Lead.php';
                        $newCount = (new Lead())->countNewSince(date('Y-m-d', strtotime('-7 days')));
                        if ($newCount > 0) echo ' <span class="admin-nav__badge">' . (int) $newCount . '</span>';
                    } catch (Throwable $e) { /* tables may not exist yet */ }
                ?></a>
                <a href="add-car.php">Add Car</a>
                <a href="<?= sanitize(url('Frontend/index.php')) ?>" target="_blank">View Site</a>
                <span class="admin-nav__user"><?= sanitize($adminUsername) ?></span>
                <a href="logout.php" class="btn btn--sm btn--outline">Logout</a>
            </nav>
        </div>
    </header>

    <div class="toast-container" id="admin-toast-container"></div>

    <script>
    // Real-time admin notifications via Server-Sent Events (SSE)
    (function() {
        var toastContainer = document.getElementById("admin-toast-container");
        var notificationsApi = '<?= url("Backend/api/notifications.php") ?>';

        // Play a premium synth audio "ding"
        function playNotificationSound(isHot) {
            try {
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                var ctx = new AudioContext();
                
                var osc1 = ctx.createOscillator();
                var gain = ctx.createGain();
                
                osc1.connect(gain);
                gain.connect(ctx.destination);
                
                osc1.type = "sine";
                if (isHot) {
                    // Double-chime for Hot Leads
                    osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc1.frequency.setValueAtTime(880, ctx.currentTime + 0.12); // A5
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                    osc1.start(ctx.currentTime);
                    osc1.stop(ctx.currentTime + 0.4);
                } else {
                    // Soft chime for standard events
                    osc1.frequency.setValueAtTime(659.25, ctx.currentTime); // E5
                    osc1.frequency.exponentialRampToValueAtTime(880.00, ctx.currentTime + 0.1); // A5
                    gain.gain.setValueAtTime(0.12, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                    osc1.start(ctx.currentTime);
                    osc1.stop(ctx.currentTime + 0.35);
                }
            } catch(e) {}
        }

        function showToast(title, message, type) {
            if (!toastContainer) return;
            
            var toast = document.createElement("div");
            toast.className = "toast toast--" + (type || "info");
            
            var header = document.createElement("div");
            header.className = "toast__header";
            
            var titleEl = document.createElement("span");
            titleEl.textContent = title;
            header.appendChild(titleEl);
            
            var close = document.createElement("button");
            close.className = "toast__close";
            close.innerHTML = "&times;";
            close.onclick = function() { toast.remove(); };
            header.appendChild(close);
            
            var msgEl = document.createElement("div");
            msgEl.className = "toast__message";
            msgEl.textContent = message;
            
            toast.appendChild(header);
            toast.appendChild(msgEl);
            toastContainer.appendChild(toast);
            
            // Play notification sound
            var isHot = (type === 'danger' || title.toLowerCase().includes('hot') || title.toLowerCase().includes('won'));
            playNotificationSound(isHot);

            // Auto remove after 6 seconds
            setTimeout(function() {
                toast.style.animation = "toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) reverse forwards";
                setTimeout(function() { toast.remove(); }, 300);
            }, 6000);
        }

        window.VA_ADMIN_NOTIFY = {
            show: showToast,
            play: playNotificationSound
        };

        if (!!window.EventSource) {
            var source = new EventSource(notificationsApi);
            source.onmessage = function(event) {
                if (event.data === 'reconnect') return;
                try {
                    var data = JSON.parse(event.data);
                    if (data.error) return;
                    
                    var type = 'info';
                    if (data.type === 'hot_lead') type = 'danger';
                    if (data.type === 'inspection') type = 'warning';
                    
                    showToast(data.title, data.message, type);
                } catch(e) {}
            };
        }
    })();
    </script>

    <main class="admin-main container">
