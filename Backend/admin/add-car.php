<?php
$pageTitle = 'Add Car';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <h2>Add New Car</h2>
    <a href="dashboard.php" class="btn btn--outline">&larr; Back to Dashboard</a>
</div>

<form method="POST" action="save-car.php" enctype="multipart/form-data" class="admin-form admin-form--wide">
    <?php include __DIR__ . '/includes/car-form-fields.php'; ?>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save Car</button>
        <a href="dashboard.php" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
