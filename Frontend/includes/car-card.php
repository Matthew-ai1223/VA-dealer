<?php
$imageUrls = $car['image_urls'] ?? [];
if (empty($imageUrls) && !empty($car['primary_image'])) {
    $imageUrls = [$car['primary_image']];
}
if (empty($imageUrls)) {
    $imageUrls = [getImageUrl(null)];
}
$imageCount = count($imageUrls);
$galleryClass = 'car-card__gallery car-card__gallery--' . min(max($imageCount, 1), 5);
$galleryJson = htmlspecialchars(json_encode($imageUrls, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
?>
<article class="car-card animate-fade-up">
    <div class="<?= sanitize($galleryClass) ?>" data-gallery="<?= $galleryJson ?>" data-title="<?= sanitize($car['title']) ?>">
        <?php if ($imageCount > 0): ?>
            <?php foreach ($imageUrls as $index => $url): ?>
            <button
                type="button"
                class="car-card__thumb"
                data-index="<?= (int) $index ?>"
                aria-label="View image <?= $index + 1 ?> of <?= $imageCount ?> for <?= sanitize($car['title']) ?>"
            >
                <img src="<?= sanitize($url) ?>" alt="<?= sanitize($car['title']) ?> — photo <?= $index + 1 ?>" loading="lazy">
            </button>
            <?php endforeach; ?>
        <?php else: ?>
            <button type="button" class="car-card__thumb car-card__thumb--solo" data-index="0" aria-label="View placeholder">
                <img src="<?= sanitize(getImageUrl(null)) ?>" alt="<?= sanitize($car['title']) ?>" loading="lazy">
            </button>
        <?php endif; ?>
        <span class="car-card__year"><?= (int) $car['year'] ?></span>
        <?php if ($imageCount > 1): ?>
            <span class="car-card__photo-count"><?= $imageCount ?> photos</span>
        <?php endif; ?>
    </div>

    <a href="car.php?id=<?= (int) $car['id'] ?>" class="car-card__link">
        <div class="car-card__body">
            <h3 class="car-card__title"><?= sanitize($car['title']) ?></h3>
            <p class="car-card__meta"><?= sanitize($car['brand']) ?> · <?= sanitize($car['model']) ?></p>
            <p class="car-card__price"><?= sanitize($car['price_formatted']) ?></p>
        </div>
    </a>

    <div class="car-card__footer">
        <a href="car.php?id=<?= (int) $car['id'] ?>" class="btn btn--sm btn--outline">View Details</a>
        <a href="<?= sanitize($car['whatsapp_url']) ?>" class="btn btn--sm btn--whatsapp" target="_blank" rel="noopener">WhatsApp</a>
    </div>
</article>
