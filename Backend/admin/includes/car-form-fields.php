<?php
/** Shared car form fields for add/edit */
$config = appConfig();
$maxImages = (int) ($config['max_images_per_car'] ?? 5);
$car = $car ?? [
    'title' => '', 'brand' => '', 'model' => '', 'year' => date('Y'),
    'price' => '', 'description' => '', 'status' => 'available', 'featured' => 0,
    'specs' => [], 'images' => [],
];
$specs = $car['specs'] ?? [];
$currentImageCount = count($car['images'] ?? []);
$remainingSlots = max(0, $maxImages - $currentImageCount);
?>
<div class="form-grid">
    <div class="form-group form-group--full">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" value="<?= sanitize($car['title']) ?>" required placeholder="e.g. 2022 Toyota Camry XSE">
    </div>

    <div class="form-group">
        <label for="brand">Brand *</label>
        <input type="text" id="brand" name="brand" value="<?= sanitize($car['brand']) ?>" required placeholder="Toyota">
    </div>

    <div class="form-group">
        <label for="model">Model *</label>
        <input type="text" id="model" name="model" value="<?= sanitize($car['model']) ?>" required placeholder="Camry">
    </div>

    <div class="form-group">
        <label for="year">Year *</label>
        <input type="number" id="year" name="year" value="<?= (int) $car['year'] ?>" required min="1990" max="2030">
    </div>

    <div class="form-group">
        <label for="price">Price (₦) *</label>
        <input type="number" id="price" name="price" value="<?= sanitize((string) $car['price']) ?>" required min="0" step="0.01">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="available" <?= ($car['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="sold" <?= ($car['status'] ?? '') === 'sold' ? 'selected' : '' ?>>Sold</option>
        </select>
    </div>

    <div class="form-group form-group--checkbox">
        <label>
            <input type="checkbox" name="featured" value="1" <?= !empty($car['featured']) ? 'checked' : '' ?>>
            Featured on homepage
        </label>
    </div>

    <div class="form-group form-group--full">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" placeholder="Describe the vehicle..."><?= sanitize($car['description'] ?? '') ?></textarea>
    </div>
</div>

<fieldset class="form-fieldset">
    <legend>Specifications</legend>
    <div class="form-grid">
        <div class="form-group">
            <label for="mileage">Mileage</label>
            <input type="text" id="mileage" name="mileage" value="<?= sanitize($specs['mileage'] ?? '') ?>" placeholder="45,000 km">
        </div>
        <div class="form-group">
            <label for="transmission">Transmission</label>
            <input type="text" id="transmission" name="transmission" value="<?= sanitize($specs['transmission'] ?? '') ?>" placeholder="Automatic">
        </div>
        <div class="form-group">
            <label for="fuel">Fuel Type</label>
            <input type="text" id="fuel" name="fuel" value="<?= sanitize($specs['fuel'] ?? '') ?>" placeholder="Petrol">
        </div>
        <div class="form-group">
            <label for="color">Color</label>
            <input type="text" id="color" name="color" value="<?= sanitize($specs['color'] ?? '') ?>" placeholder="White">
        </div>
        <div class="form-group">
            <label for="engine">Engine</label>
            <input type="text" id="engine" name="engine" value="<?= sanitize($specs['engine'] ?? '') ?>" placeholder="2.5L">
        </div>
    </div>
</fieldset>

<fieldset class="form-fieldset">
    <legend>Images (up to <?= $maxImages ?> per listing)</legend>

    <p class="image-upload-status">
        <strong id="image-count"><?= $currentImageCount ?></strong> of <?= $maxImages ?> images uploaded
        <?php if ($remainingSlots > 0): ?>
            — you can add <?= $remainingSlots ?> more
        <?php else: ?>
            — <span class="text-warning">limit reached</span>
        <?php endif; ?>
    </p>

    <?php if (!empty($car['images'])): ?>
    <div class="image-preview-grid">
        <?php foreach ($car['images'] as $index => $img): ?>
        <label class="image-preview">
            <span class="image-preview__badge">#<?= $index + 1 ?></span>
            <img src="<?= sanitize(getImageUrl($img)) ?>" alt="">
            <span class="image-preview__remove">
                <input type="checkbox" name="remove_images[]" value="<?= sanitize($img) ?>"> Remove
            </span>
        </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="images">Upload Images</label>
        <input
            type="file"
            id="images"
            name="images[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
            <?= $remainingSlots <= 0 ? 'disabled' : '' ?>
        >
        <small>
            Select up to <?= $remainingSlots ?> image<?= $remainingSlots !== 1 ? 's' : '' ?>.
            JPG, PNG or WebP. Max 5MB each. First image is the cover photo.
        </small>
    </div>
</fieldset>
