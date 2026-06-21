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
    <link rel="stylesheet" href="<?= sanitize(url('Frontend/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= sanitize(url('Backend/admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container admin-header__inner">
            <a href="dashboard.php" class="admin-header__brand"><?= sanitize($config['site_name']) ?> Admin</a>
            <nav class="admin-nav">
                <a href="dashboard.php">Dashboard</a>
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
    <main class="admin-main container">
