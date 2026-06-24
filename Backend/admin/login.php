<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/firewall.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginAdmin($username, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password';
    reportFailedLogin(); // Track brute-force attempts
}

$config = appConfig();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= sanitize($config['site_name']) ?></title>
    <link rel="stylesheet" href="<?= sanitize(url('Frontend/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= sanitize(url('Backend/admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
    <div class="admin-login">
        <div class="admin-login__card">
            <div class="admin-login__header">
                <h1><?= sanitize($config['site_name']) ?></h1>
                <p>Admin Dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert--error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn--primary btn--block">Sign In</button>
            </form>

            <p class="admin-login__footer">
                <a href="<?= sanitize(url('Frontend/index.php')) ?>">&larr; Back to website</a>
            </p>
        </div>
    </div>
</body>
</html>
