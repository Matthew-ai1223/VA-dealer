<?php
/**
 * Dynamic robots.txt
 */
require_once __DIR__ . '/../Backend/lib/helpers.php';

header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Allow: /\n\n";
echo "Sitemap: " . fullUrl('Frontend/sitemap.php') . "\n";
