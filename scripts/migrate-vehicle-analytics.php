<?php
$config = require dirname(__DIR__) . '/Backend/config/database.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sql = file_get_contents(dirname(__DIR__) . '/database/migrate_vehicle_analytics.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if ($statement !== '') {
        $pdo->exec($statement);
    }
}
echo "Vehicle analytics migration OK\n";
