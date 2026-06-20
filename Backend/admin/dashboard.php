<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Car.php';

$carModel = new Car();
$allCars = $carModel->getAll([], true);
$available = $carModel->countAll('available');
$sold = $carModel->countAll('sold');
$total = $carModel->countAll();
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert--success"><?= sanitize($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert--error"><?= sanitize($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="admin-stats">
    <div class="stat-card">
        <span class="stat-card__value"><?= $total ?></span>
        <span class="stat-card__label">Total Listings</span>
    </div>
    <div class="stat-card stat-card--success">
        <span class="stat-card__value"><?= $available ?></span>
        <span class="stat-card__label">Available</span>
    </div>
    <div class="stat-card stat-card--muted">
        <span class="stat-card__value"><?= $sold ?></span>
        <span class="stat-card__label">Sold</span>
    </div>
</div>

<div class="admin-toolbar">
    <h2>All Listings</h2>
    <a href="add-car.php" class="btn btn--primary">+ Add New Car</a>
</div>

<?php if (empty($allCars)): ?>
    <div class="empty-state">
        <p>No cars listed yet.</p>
        <a href="add-car.php" class="btn btn--primary">Add your first car</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Brand</th>
                    <th>Year</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allCars as $car): ?>
                <tr>
                    <td>
                        <img src="<?= sanitize($car['primary_image']) ?>" alt="" class="admin-table__thumb">
                    </td>
                    <td><?= sanitize($car['title']) ?></td>
                    <td><?= sanitize($car['brand']) ?></td>
                    <td><?= (int) $car['year'] ?></td>
                    <td><?= sanitize($car['price_formatted']) ?></td>
                    <td>
                        <span class="badge badge--<?= $car['status'] === 'available' ? 'success' : 'muted' ?>">
                            <?= ucfirst(sanitize($car['status'])) ?>
                        </span>
                    </td>
                    <td><?= $car['featured'] ? 'Yes' : 'No' ?></td>
                    <td class="admin-table__actions">
                        <a href="edit-car.php?id=<?= (int) $car['id'] ?>" class="btn btn--sm btn--outline">Edit</a>
                        <form method="POST" action="delete-car.php" class="inline-form" onsubmit="return confirm('Delete this listing?')">
                            <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
                            <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
