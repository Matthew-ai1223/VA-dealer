<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
requireAdmin();

require_once __DIR__ . '/../models/HeroSlide.php';
require_once __DIR__ . '/../models/Car.php';

$heroModel = new HeroSlide();
$carModel = new Car();

// Handle Form Submission (Add / Edit / Delete / Reorder)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $year = $_POST['year'] !== '' ? (int)$_POST['year'] : null;
        $link = trim($_POST['link'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        
        // If they selected a specific car, override link with the car detail link
        $carId = (int)($_POST['link_car_id'] ?? 0);
        if ($carId > 0) {
            $link = 'car.php?id=' . $carId;
            // Auto fill empty fields from selected car
            $selectedCar = $carModel->getById($carId, true);
            if ($selectedCar) {
                if (empty($title)) $title = $selectedCar['title'];
                if (empty($brand)) $brand = $selectedCar['brand'];
                if (empty($year)) $year = (int)$selectedCar['year'];
            }
        }

        if (empty($title) && $carId === 0) {
            $error = 'Title or linked car is required.';
        } else {
            // Handle image upload
            $imageFilename = $_POST['existing_image'] ?? '';
            $uploadErrors = [];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $uploadDir = __DIR__ . '/../uploads/hero';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $mime = mime_content_type($file['tmp_name']);
                if (!in_array($mime, $allowedTypes, true)) {
                    $uploadErrors[] = "Only JPG, PNG, and WEBP images are allowed.";
                }
                
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($file['size'] > $maxSize) {
                    $uploadErrors[] = "Image size cannot exceed 5MB.";
                }
                
                if (empty($uploadErrors)) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFilename = uniqid('hero_', true) . '.' . strtolower($ext);
                    $destination = $uploadDir . '/' . $newFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        // Delete old image if updating
                        if (!empty($imageFilename)) {
                            $oldPath = $uploadDir . '/' . $imageFilename;
                            if (is_file($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                        $imageFilename = $newFilename;
                    } else {
                        $uploadErrors[] = "Failed to save uploaded image.";
                    }
                }
            } elseif ($id === 0 && (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)) {
                $uploadErrors[] = "Hero slide image is required for new slides.";
            }

            if (!empty($uploadErrors)) {
                $error = implode(' ', $uploadErrors);
            } else {
                $data = [
                    'image_path' => $imageFilename,
                    'title'      => $title,
                    'brand'      => $brand,
                    'year'       => $year,
                    'link'       => $link,
                    'sort_order' => $sortOrder
                ];

                if ($id > 0) {
                    $heroModel->update($id, $data);
                    $_SESSION['flash_success'] = 'Hero slide updated successfully.';
                } else {
                    $heroModel->create($data);
                    $_SESSION['flash_success'] = 'Hero slide created successfully.';
                }
                header('Location: hero-settings.php');
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $slide = $heroModel->getById($id);
        if ($slide) {
            $heroModel->delete($id);
            // Delete image file
            $oldPath = __DIR__ . '/../uploads/hero/' . $slide['image_path'];
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
            $_SESSION['flash_success'] = 'Hero slide deleted successfully.';
        }
        header('Location: hero-settings.php');
        exit;
    }
}

$carsList = $carModel->getAll([], true);
$slides = $heroModel->getAll();

$editSlide = null;
$editId = (int)($_GET['edit_id'] ?? 0);
if ($editId > 0) {
    $editSlide = $heroModel->getById($editId);
}

// Display messages
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$pageTitle = 'Manage Hero Slides';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert--success"><?= sanitize($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert--error"><?= sanitize($error) ?></div>
<?php endif; ?>

<div class="admin-toolbar">
    <h2>Manage Homepage Hero Slides</h2>
    <?php if ($editSlide): ?>
        <a href="hero-settings.php" class="btn btn--outline">&larr; Back to Add Form</a>
    <?php endif; ?>
</div>

<div class="crm-grid" style="grid-template-columns: 3fr 2fr; gap: 24px; align-items: start;">
    
    <!-- 1. Hero Slides List -->
    <div class="crm-panel">
        <h3>Current Hero Carousel Slides</h3>
        <p class="text-muted" style="margin-bottom: 16px;">These are the slides displayed in the home page carousel. Fallback defaults are used if this list is empty.</p>
        
        <?php if (empty($slides)): ?>
            <div style="text-align: center; padding: 30px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px dashed #cbd5e1;">
                <p class="text-muted">No custom hero slides. Fallback defaults are active.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Slide Title</th>
                            <th>Info</th>
                            <th>Link</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): 
                            $imgUrl = url('Backend/uploads/hero/' . $slide['image_path']);
                        ?>
                        <tr>
                            <td>
                                <img src="<?= sanitize($imgUrl) ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;" alt="">
                            </td>
                            <td style="font-weight: 700;">
                                <?= sanitize($slide['title']) ?>
                            </td>
                            <td>
                                <small style="display:block; color:#64748b;">
                                    Brand: <?= sanitize($slide['brand'] ?: 'N/A') ?><br>
                                    Year: <?= $slide['year'] ?: 'N/A' ?>
                                </small>
                            </td>
                            <td>
                                <small style="word-break: break-all; color:#64748b;">
                                    <?= sanitize($slide['link'] ?: 'N/A') ?>
                                </small>
                            </td>
                            <td style="font-weight: 600; text-align: center;">
                                <?= (int)$slide['sort_order'] ?>
                            </td>
                            <td>
                                <div class="admin-table__actions">
                                    <a href="hero-settings.php?edit_id=<?= (int)$slide['id'] ?>" class="btn btn--sm btn--outline">Edit</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this hero slide?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
                                        <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. Add/Edit Form -->
    <div class="crm-panel">
        <h3><?= $editSlide ? 'Edit Hero Slide' : 'Add New Hero Slide' ?></h3>
        <form method="POST" enctype="multipart/form-data" class="admin-form" style="padding: 16px 0 0 0; box-shadow: none;">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editSlide ? (int)$editSlide['id'] : 0 ?>">
            <input type="hidden" name="existing_image" value="<?= $editSlide ? sanitize($editSlide['image_path']) : '' ?>">

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Upload Slide Image *</label>
                <?php if ($editSlide): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?= sanitize(url('Backend/uploads/hero/' . $editSlide['image_path'])) ?>" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;" alt="">
                        <small class="text-muted">Current image. Upload a new one to replace it.</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="form-control" <?= $editSlide ? '' : 'required' ?>>
                <small class="text-muted">Recommended resolution: 1920x800. Max size: 5MB.</small>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Link to Car Listing (Optional)</label>
                <select name="link_car_id" class="form-control" id="link-car-select">
                    <option value="">-- Choose Car (Auto-fills Title, Brand, Year) --</option>
                    <?php foreach ($carsList as $car): ?>
                        <option value="<?= (int)$car['id'] ?>" <?= $editSlide && $editSlide['link'] === 'car.php?id=' . $car['id'] ? 'selected' : '' ?>>
                            <?= sanitize($car['title']) ?> (<?= sanitize($car['price_formatted']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Directly links this slide to a car detail page and automatically uses its specifications.</small>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Slide Title</label>
                <input type="text" name="title" id="slide-title" class="form-control" placeholder="e.g. BMW M5 Coupe" value="<?= $editSlide ? sanitize($editSlide['title']) : '' ?>">
                <small class="text-muted">Leave blank if using the Linked Car above.</small>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group">
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Brand</label>
                    <input type="text" name="brand" id="slide-brand" class="form-control" placeholder="e.g. BMW" value="<?= $editSlide ? sanitize($editSlide['brand']) : '' ?>">
                </div>
                <div class="form-group">
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Year</label>
                    <input type="number" name="year" id="slide-year" class="form-control" placeholder="e.g. 2022" value="<?= $editSlide ? sanitize($editSlide['year']) : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;" id="custom-link-group">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Custom Link URL (Fallback)</label>
                <input type="text" name="link" id="slide-link" class="form-control" placeholder="e.g. listings.php" value="<?= $editSlide ? sanitize($editSlide['link']) : 'listings.php' ?>">
                <small class="text-muted">Ignored if a Car Listing is selected above.</small>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Sort Order (Ascending)</label>
                <input type="number" name="sort_order" class="form-control" value="<?= $editSlide ? (int)$editSlide['sort_order'] : 0 ?>">
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn--primary" style="flex: 1;"><?= $editSlide ? 'Update Slide' : 'Create Slide' ?></button>
                <?php if ($editSlide): ?>
                    <a href="hero-settings.php" class="btn btn--outline" style="flex: 1; text-align: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var carSelect = document.getElementById('link-car-select');
    var customLinkGroup = document.getElementById('custom-link-group');
    
    function toggleLinkField() {
        if (carSelect.value !== '') {
            customLinkGroup.style.opacity = '0.5';
            customLinkGroup.style.pointerEvents = 'none';
        } else {
            customLinkGroup.style.opacity = '1';
            customLinkGroup.style.pointerEvents = 'auto';
        }
    }
    
    carSelect.addEventListener('change', toggleLinkField);
    toggleLinkField();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
