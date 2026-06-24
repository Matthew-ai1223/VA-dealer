<?php
if (!isset($config)) {
    require_once __DIR__ . '/../../Backend/lib/helpers.php';
    $config = appConfig();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Clean canonical URLs
if ($currentPage === 'index.php') {
    $canonical = $canonical ?? fullUrl('');
} elseif ($currentPage === 'listings.php') {
    $canonical = $canonical ?? fullUrl('cars');
} else {
    $canonical = $canonical ?? fullUrl('Frontend/' . $currentPage);
}

// Enhance page titles with site name if needed
if ($currentPage === 'index.php') {
    $pageTitle = $config['site_name'] . ' | ' . $config['site_tagline'];
} else {
    $pageTitle = $pageTitle ?? $config['site_name'];
}

$pageDescription = $pageDescription ?? $config['site_tagline'];
$ogImage = $ogImage ?? ($config['logo_full_url'] ?? fullUrl('Frontend/assets/images/log.jpg'));
$ogImageAlt = $ogImageAlt ?? ($config['site_name'] . ' — Premium Pre-Owned Vehicles');

// Dynamic og:type — 'product' for car listings, 'website' for all other pages
$ogType = ($currentPage === 'car.php') ? 'product' : 'website';

// Dynamic og:image MIME type — derived from file extension
$_ogExt = strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH), PATHINFO_EXTENSION));
$ogImageType = match($_ogExt) {
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
    default => 'image/jpeg',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="<?= sanitize($pageDescription) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= sanitize($canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= $ogType ?>">
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription) ?>">
    <meta property="og:url" content="<?= sanitize($canonical) ?>">
    <meta property="og:site_name" content="<?= sanitize($config['site_name']) ?>">
    <meta property="og:image" content="<?= sanitize($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= sanitize($ogImage) ?>">
    <meta property="og:image:type" content="<?= $ogImageType ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= sanitize($ogImageAlt) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= sanitize($pageTitle) ?>">
    <meta name="twitter:description" content="<?= sanitize($pageDescription) ?>">
    <meta name="twitter:image" content="<?= sanitize($ogImage) ?>">
    <meta name="twitter:image:alt" content="<?= sanitize($ogImageAlt) ?>">

    <!-- Structured Data / JSON-LD Schema Markup -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": <?= json_encode($config['site_name']) ?>,
      "url": <?= json_encode(fullUrl('')) ?>,
      "potentialAction": {
        "@type": "SearchAction",
        "target": <?= json_encode(fullUrl('cars?search={search_term_string}')) ?>,
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <?php if ($currentPage === 'index.php'): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "AutoDealer",
      "name": <?= json_encode($config['site_name']) ?>,
      "image": <?= json_encode($ogImage) ?>,
      "@id": <?= json_encode(fullUrl('#dealer')) ?>,
      "url": <?= json_encode(fullUrl('')) ?>,
      "telephone": <?= json_encode('+' . ($config['whatsapp_number'] ?? '')) ?>,
      "priceRange": "₦₦₦",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Abuja",
        "addressRegion": "FCT",
        "addressCountry": "NG"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 9.072264,
        "longitude": 7.491302
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
          "opens": "08:00",
          "closes": "18:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Saturday",
          "opens": "09:00",
          "closes": "18:00"
        }
      ],
      "sameAs": <?= json_encode(array_values(array_filter($config['social'] ?? []))) ?>
    }
    </script>
    <?php endif; ?>

    <?php if ($currentPage === 'listings.php' && !empty($cars)): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ItemList",
      "name": "Available Vehicle Showroom",
      "numberOfItems": <?= count($cars) ?>,
      "itemListElement": [
        <?php foreach (array_values($cars) as $idx => $carItem): ?>
        {
          "@type": "ListItem",
          "position": <?= $idx + 1 ?>,
          "url": <?= json_encode(carShareUrl((int)$carItem['id'])) ?>
        }<?= $idx < count($cars) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sanitize(url('Frontend/assets/css/style.css')) ?>?v=8">
    <link rel="icon" href="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>" type="image/jpeg">
    
    <!-- PWA Manifest & App Config -->
    <link rel="manifest" href="<?= sanitize(url('manifest.json')) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= sanitize($config['site_name']) ?>">
    <meta name="theme-color" content="#1e40af">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="<?= sanitize(url('Frontend/assets/images/apple-touch-icon.png')) ?>">

    <!-- iOS Launch Splash Screens -->
    <!-- iPhone 15 Pro Max / 14 Pro Max -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1290-2796.png')) ?>" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone 15 Pro / 15 / 14 Pro -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1179-2556.png')) ?>" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone 14 Plus / 13 Pro Max / 12 Pro Max -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1284-2778.png')) ?>" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone 14 / 13 Pro / 13 / 12 Pro / 12 -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1170-2532.png')) ?>" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone X / XS / 11 Pro -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1125-2436.png')) ?>" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone XS Max / 11 Pro Max -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1242-2688.png')) ?>" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone XR / 11 -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-828-1792.png')) ?>" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
    <!-- iPhone 8 Plus / 7 Plus / 6s Plus -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-1242-2208.png')) ?>" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
    <!-- iPhone 8 / 7 / 6s / SE (2nd gen) -->
    <link rel="apple-touch-startup-image" href="<?= sanitize(url('Frontend/assets/images/splash/apple-splash-750-1334.png')) ?>" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">

    <script>window.APP_BASE = <?= json_encode(basePath(), JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body>
    <header class="site-header" id="site-header">
        <div class="container site-header__inner">
            <a href="<?= sanitize(url('Frontend/index.php')) ?>" class="site-header__logo">
                <img
                    src="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>"
                    alt="<?= sanitize($config['site_name']) ?>"
                    class="site-logo-img"
                    width="180"
                    height="44"
                >
            </a>
            <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <nav class="site-nav" id="site-nav">
                <a href="<?= sanitize(url('Frontend/index.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="<?= sanitize(url('Frontend/listings.php')) ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'listings.php' ? 'active' : '' ?>">Browse Cars</a>
                <a href="<?= sanitize(url('Backend/admin/login.php')) ?>" class="nav-admin">Admin</a>
            </nav>
        </div>
    </header>
    <main>
