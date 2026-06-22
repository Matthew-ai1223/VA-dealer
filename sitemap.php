<?php
/**
 * Dynamic XML Sitemap Generator
 * VA Auto Sales - SEO Sitemap
 *
 * Generates an up-to-date sitemap of all active vehicle listings.
 * Routes through sitemap.xml via mod_rewrite in .htaccess
 */

// Set XML headers
header('Content-Type: application/xml; charset=utf-8');

// Include base system files
require_once __DIR__ . '/Backend/lib/helpers.php';
require_once __DIR__ . '/Backend/models/Car.php';

try {
    $carModel = new Car();
    // Only index available vehicles on public sitemap
    $cars = $carModel->getAll(['status' => 'available'], false);
} catch (Exception $e) {
    $cars = [];
}

// Find last modification date of dynamic entries to update static routes dynamically
$lastModDate = date('Y-m-d');
if (!empty($cars)) {
    $timestamps = array_map(function($car) {
        return isset($car['updated_at']) ? strtotime($car['updated_at']) : (isset($car['created_at']) ? strtotime($car['created_at']) : time());
    }, $cars);
    $lastModDate = date('Y-m-d', max($timestamps));
}

// Build Sitemap XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= htmlspecialchars(fullUrl(''), ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($lastModDate, ENT_QUOTES, 'UTF-8') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Browse Listings -->
    <url>
        <loc><?= htmlspecialchars(fullUrl('cars'), ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($lastModDate, ENT_QUOTES, 'UTF-8') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Dynamic Showroom Listings -->
    <?php foreach ($cars as $car): ?>
        <?php 
            $carDate = isset($car['updated_at']) 
                ? date('Y-m-d', strtotime($car['updated_at'])) 
                : (isset($car['created_at']) ? date('Y-m-d', strtotime($car['created_at'])) : date('Y-m-d'));
        ?>
        <url>
            <loc><?= htmlspecialchars(carShareUrl((int)$car['id']), ENT_QUOTES, 'UTF-8') ?></loc>
            <lastmod><?= htmlspecialchars($carDate, ENT_QUOTES, 'UTF-8') ?></lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    <?php endforeach; ?>
</urlset>
