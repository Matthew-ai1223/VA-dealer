<?php
$pageTitle = 'Edit Car';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Car.php';

$id = (int) ($_GET['id'] ?? 0);
$carModel = new Car();
$car = $carModel->getById($id, true);

if (!$car) {
    echo '<div class="alert alert--error">Car not found.</div>';
    echo '<a href="dashboard.php" class="btn btn--outline">Back to Dashboard</a>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="admin-toolbar">
    <h2>Edit Car</h2>
    <a href="dashboard.php" class="btn btn--outline">&larr; Back to Dashboard</a>
</div>

<form method="POST" action="save-car.php" enctype="multipart/form-data" class="admin-form admin-form--wide">
    <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
    <?php include __DIR__ . '/includes/car-form-fields.php'; ?>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Update Car</button>
        <a href="dashboard.php" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
