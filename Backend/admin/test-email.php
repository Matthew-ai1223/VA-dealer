<?php
/**
 * Email test script — DELETE AFTER TESTING
 * Access via: http://localhost/VA_AUT_SALES/Backend/admin/test-email.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../Backend/lib/helpers.php';
require_once __DIR__ . '/../../Backend/lib/mail.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testTo = trim($_POST['email'] ?? '');
    if (filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $result = sendMail(
            $testTo,
            '✅ VA Auto Sales — Email Test',
            "This is a test email from VA Auto Sales.\n\nIf you received this, your SMTP config is working!",
            '<div style="font-family:Arial,sans-serif;padding:28px;max-width:500px;background:#f8fafc;border-radius:10px;">
                <h2 style="color:#1e40af;">✅ Email is Working!</h2>
                <p style="color:#0f172a;font-size:15px;">This test email confirms your SMTP configuration is correctly set up for <strong>VA Auto Sales</strong>.</p>
                <p style="color:#64748b;font-size:13px;margin-top:16px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
            </div>'
        );
        if ($result) {
            $sent = true;
        } else {
            $error = 'sendMail() returned false. Check your mail.local.php credentials and that PHPMailer is installed.';
        }
    } else {
        $error = 'Invalid email address entered.';
    }
}

$mailCfg = _mailConfig();
$appCfg  = appConfig();
$autoload = __DIR__ . '/../../vendor/autoload.php';
$hasAutoload = is_file($autoload);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Email Test — VA Auto Sales</title>
<style>
    body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 32px 16px; }
    .card { background: #fff; border-radius: 12px; padding: 32px; max-width: 560px; margin: auto; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    h1 { color: #1e40af; font-size: 1.4rem; margin: 0 0 24px; }
    .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .row:last-of-type { border: none; }
    .label { color: #64748b; font-weight: 500; }
    .val { font-weight: 600; color: #0f172a; }
    .ok { color: #16a34a; } .bad { color: #dc2626; }
    form { margin-top: 28px; }
    input[type=email] { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 15px; box-sizing: border-box; margin-bottom: 12px; }
    button { background: #1e40af; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; }
    button:hover { background: #1e3a8a; }
    .alert { padding: 14px 18px; border-radius: 8px; margin-top: 16px; font-size: 14px; }
    .alert-ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .alert-err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
</style>
</head>
<body>
<div class="card">
    <h1>📧 Email Configuration Test</h1>

    <div class="row">
        <span class="label">PHPMailer installed</span>
        <span class="val <?= $hasAutoload ? 'ok' : 'bad' ?>"><?= $hasAutoload ? '✅ Yes' : '❌ No — run: composer require phpmailer/phpmailer' ?></span>
    </div>
    <div class="row">
        <span class="label">mail.local.php found</span>
        <span class="val <?= !empty($mailCfg) ? 'ok' : 'bad' ?>"><?= !empty($mailCfg) ? '✅ Yes' : '❌ Missing' ?></span>
    </div>
    <div class="row">
        <span class="label">SMTP Username</span>
        <span class="val <?= !empty($mailCfg['username']) && $mailCfg['username'] !== 'YOUR_GMAIL@gmail.com' ? 'ok' : 'bad' ?>">
            <?= !empty($mailCfg['username']) ? htmlspecialchars($mailCfg['username']) : '❌ Not set' ?>
        </span>
    </div>
    <div class="row">
        <span class="label">SMTP Password</span>
        <span class="val <?= !empty($mailCfg['password']) && $mailCfg['password'] !== 'xxxx xxxx xxxx xxxx' ? 'ok' : 'bad' ?>">
            <?= !empty($mailCfg['password']) && $mailCfg['password'] !== 'xxxx xxxx xxxx xxxx' ? '✅ Set (hidden)' : '❌ Not set' ?>
        </span>
    </div>
    <div class="row">
        <span class="label">Admin email (app.php)</span>
        <span class="val <?= !empty($appCfg['admin_email']) && $appCfg['admin_email'] !== 'YOUR_EMAIL@gmail.com' ? 'ok' : 'bad' ?>">
            <?= !empty($appCfg['admin_email']) ? htmlspecialchars($appCfg['admin_email']) : '❌ Not set' ?>
        </span>
    </div>

    <form method="POST">
        <p style="font-size:14px;color:#64748b;margin:0 0 10px;">Send a test email to verify SMTP is working:</p>
        <input type="email" name="email" placeholder="your@email.com" required
            value="<?= htmlspecialchars($_POST['email'] ?? $appCfg['admin_email'] ?? '') ?>">
        <button type="submit">Send Test Email</button>
    </form>

    <?php if ($sent): ?>
        <div class="alert alert-ok">✅ Email sent successfully! Check your inbox (and spam folder).</div>
    <?php elseif ($error): ?>
        <div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</div>
</body>
</html>
