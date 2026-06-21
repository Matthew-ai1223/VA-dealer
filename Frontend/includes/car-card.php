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
$shareUrl = carShareUrl((int) $car['id']);
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
        <button
            type="button"
            class="btn btn--sm btn--share js-share-car"
            data-share-url="<?= sanitize($shareUrl) ?>"
            data-share-title="<?= sanitize($car['title']) ?>"
            aria-label="Copy link to share <?= sanitize($car['title']) ?>"
        >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
            Share
        </button>
        <button
            type="button"
            class="btn btn--sm btn--primary js-open-lead-modal"
            data-car-id="<?= (int) $car['id'] ?>"
            data-car-title="<?= sanitize($car['title']) ?>"
            data-car-price="<?= sanitize($car['price_formatted']) ?>"
        >
            Interested in this car?
        </button>
    </div>
</article>
