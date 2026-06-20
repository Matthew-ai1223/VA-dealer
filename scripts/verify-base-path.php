<?php
/**
 * Quick check for basePath() / url() — run: php scripts/verify-base-path.php [subfolder|root]
 */
$mode = $argv[1] ?? 'subfolder';

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS']     = 'off';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2); // C:/xampp/htdocs

if ($mode === 'root') {
    $_SERVER['SCRIPT_NAME'] = '/Frontend/index.php';
    $label = 'Domain root (InfinityFree)';
} else {
    $_SERVER['SCRIPT_NAME'] = '/VA_AUT_SALES/Frontend/index.php';
    $label = 'Subfolder (XAMPP)';
}

require __DIR__ . '/../Backend/lib/helpers.php';

echo $label . PHP_EOL;
echo 'basePath: [' . basePath() . ']' . PHP_EOL;
echo 'url:      ' . url('Frontend/index.php') . PHP_EOL;
echo 'fullUrl:  ' . fullUrl('Frontend/index.php') . PHP_EOL;
