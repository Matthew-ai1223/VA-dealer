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

<section class="car-detail reveal-on-scroll">
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
                    <a href="<?= sanitize($car['whatsapp_url']) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Contact Seller on WhatsApp
                    </a>
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
