<?php
/**
 * Dynamic XML sitemap for SEO
 */
require_once __DIR__ . '/../Backend/lib/helpers.php';
require_once __DIR__ . '/../Backend/models/Car.php';

$config = appConfig();
$carModel = new Car();
$cars = $carModel->getAll([], false);

header('Content-Type: application/xml; charset=utf-8');

$base = fullUrl('Frontend');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($base . '/index.php') ?></loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($base . '/listings.php') ?></loc>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
<?php foreach ($cars as $car): ?>
  <url>
    <loc><?= htmlspecialchars($base . '/car.php?id=' . $car['id']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($car['updated_at'])) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>
</urlset>
