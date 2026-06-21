<?php
/**
 * One-time database setup script
 */
require_once __DIR__ . '/Backend/lib/helpers.php';

$config = require __DIR__ . '/Backend/config/database.php';
$schema = file_get_contents(__DIR__ . '/database/schema.sql');

try {
    $dsn = sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']);
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Execute schema statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    // Stage 2 migration (leads / CRM)
    $stage2 = __DIR__ . '/database/migrate_stage2.sql';
    if (is_file($stage2)) {
        $stage2Sql = file_get_contents($stage2);
        $stage2Statements = array_filter(array_map('trim', explode(';', $stage2Sql)));
        foreach ($stage2Statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    }

    // Create uploads directory
    $uploadDir = __DIR__ . '/Backend/uploads/cars';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $message = 'Database setup completed successfully!';
    $success = true;
} catch (PDOException $e) {
    $message = 'Setup failed: ' . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VA Auto Sales - Setup</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 80px auto; padding: 0 20px; }
        .box { padding: 24px; border-radius: 12px; background: <?= $success ? '#ecfdf5' : '#fef2f2' ?>; border: 1px solid <?= $success ? '#6ee7b7' : '#fca5a5' ?>; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Setup <?= $success ? 'Complete' : 'Failed' ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
        <?php if ($success): ?>
            <p>
                <a href="<?= sanitize(url('Frontend/index.php')) ?>">Visit Website</a> |
                <a href="<?= sanitize(url('Backend/admin/login.php')) ?>">Admin Login</a>
            </p>
            <p><small>Admin: <strong>vaautosales</strong> / <strong>vaautosales123</strong></small></p>
        <?php endif; ?>
    </div>
</body>
</html>
