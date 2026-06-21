<?php
require_once __DIR__ . '/../Backend/lib/helpers.php';
require_once __DIR__ . '/../Backend/models/Car.php';

$config = appConfig();
$id = (int) ($_GET['id'] ?? 0);
$carModel = new Car();
$car = $carModel->getById($id, false);

if (!$car) {
    http_response_code(404);
    $pageTitle = 'Car Not Found | ' . $config['site_name'];
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><h1>Car Not Found</h1><p>This listing may have been sold or removed.</p><a href="listings.php" class="btn btn--primary">Browse Cars</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = sanitize($car['title']) . ' | ' . $config['site_name'];
$pageDescription = mb_substr(strip_tags($car['description'] ?? ''), 0, 160);
$specs = $car['specs'] ?? [];
require __DIR__ . '/includes/header.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Vehicle",
  "name": <?= json_encode($car['title']) ?>,
  "brand": { "@type": "Brand", "name": <?= json_encode($car['brand']) ?> },
  "model": <?= json_encode($car['model']) ?>,
  "vehicleModelDate": <?= json_encode((string) $car['year']) ?>,
  "offers": {
    "@type": "Offer",
    "price": <?= json_encode((string) $car['price']) ?>,
    "priceCurrency": "NGN",
    "availability": "https://schema.org/InStock"
  }
}
</script>

<section class="car-detail reveal-on-scroll" data-track-car-view="<?= (int) $car['id'] ?>">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">Home</a> /
            <a href="listings.php">Cars</a> /
            <span><?= sanitize($car['title']) ?></span>
        </nav>

        <div class="car-detail__grid">
            <div class="car-detail__gallery">
                <?php if (!empty($car['images'])): ?>
                    <div class="gallery__main">
                        <img src="<?= sanitize(getImageUrl($car['images'][0])) ?>" alt="<?= sanitize($car['title']) ?>" id="main-image">
                    </div>
                    <?php if (count($car['images']) > 1): ?>
                    <div class="gallery__thumbs">
                        <?php foreach ($car['images'] as $index => $img): ?>
                            <button type="button" class="gallery__thumb <?= $index === 0 ? 'active' : '' ?>" data-src="<?= sanitize(getImageUrl($img)) ?>">
                                <img src="<?= sanitize(getImageUrl($img)) ?>" alt="">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="gallery__main">
                        <img src="<?= sanitize(getImageUrl(null)) ?>" alt="<?= sanitize($car['title']) ?>">
                    </div>
                <?php endif; ?>
            </div>

            <div class="car-detail__info">
                <span class="car-detail__year"><?= (int) $car['year'] ?></span>
                <h1><?= sanitize($car['title']) ?></h1>
                <p class="car-detail__price"><?= sanitize($car['price_formatted']) ?></p>
                <p class="car-detail__meta"><?= sanitize($car['brand']) ?> · <?= sanitize($car['model']) ?></p>

                <div class="car-detail__actions">
                    <button
                        type="button"
                        class="btn btn--primary btn--lg js-open-lead-modal"
                        data-car-id="<?= (int) $car['id'] ?>"
                        data-car-title="<?= sanitize($car['title']) ?>"
                        data-car-price="<?= sanitize($car['price_formatted']) ?>"
                    >
                        Interested in this car?
                    </button>
                    <button
                        type="button"
                        class="btn btn--lg btn--outline btn--share js-share-car"
                        data-share-url="<?= sanitize(carShareUrl((int) $car['id'])) ?>"
                        data-share-title="<?= sanitize($car['title']) ?>"
                        aria-label="Copy link to share this car"
                    >
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                        Copy link
                    </button>
                </div>

                <?php if (!empty($specs)): ?>
                <div class="specs-table">
                    <h3>Specifications</h3>
                    <dl>
                        <?php foreach ($specs as $key => $value): ?>
                            <?php if ($value): ?>
                            <div class="specs-table__row">
                                <dt><?= sanitize(ucfirst($key)) ?></dt>
                                <dd><?= sanitize($value) ?></dd>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($car['description'])): ?>
        <div class="car-detail__description">
            <h2>Description</h2>
            <p><?= nl2br(sanitize($car['description'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
