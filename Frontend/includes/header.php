<?php
if (!isset($config)) {
    require_once __DIR__ . '/../../Backend/lib/helpers.php';
    $config = appConfig();
}
$pageTitle = $pageTitle ?? $config['site_name'];
$pageDescription = $pageDescription ?? $config['site_tagline'];
$canonical = $canonical ?? fullUrl('Frontend/' . basename($_SERVER['PHP_SELF']));
$ogImage = $ogImage ?? ($config['logo_full_url'] ?? fullUrl('Frontend/assets/images/log.jpg'));
$ogImageAlt = $ogImageAlt ?? ($config['site_name'] . ' — Premium Pre-Owned Vehicles');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="<?= sanitize($pageDescription) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= sanitize($canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription) ?>">
    <meta property="og:url" content="<?= sanitize($canonical) ?>">
    <meta property="og:site_name" content="<?= sanitize($config['site_name']) ?>">
    <meta property="og:image" content="<?= sanitize($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= sanitize($ogImage) ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:alt" content="<?= sanitize($ogImageAlt) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= sanitize($pageTitle) ?>">
    <meta name="twitter:description" content="<?= sanitize($pageDescription) ?>">
    <meta name="twitter:image" content="<?= sanitize($ogImage) ?>">
    <meta name="twitter:image:alt" content="<?= sanitize($ogImageAlt) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sanitize(url('Frontend/assets/css/style.css')) ?>?v=8">
    <link rel="icon" href="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>" type="image/jpeg">
    <link rel="apple-touch-icon" href="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>">
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
