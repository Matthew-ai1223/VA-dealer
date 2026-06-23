<?php
$pageTitle = 'Manage Cars';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Car.php';

$carModel = new Car();
$cars = $carModel->getAll([], true); // adminView = true to see both available and sold cars
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert--success"><?= sanitize($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert--error"><?= sanitize($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="admin-toolbar">
    <h2>All Listings</h2>
    <a href="add-car.php" class="btn btn--primary">+ Add New Car</a>
</div>

<?php if (empty($cars)): ?>
    <div class="empty-state" style="background:#fff; padding:40px; text-align:center; border-radius:12px; border:1px solid #e2e8f0;">
        <p style="color:#64748b; margin-bottom:16px;">No cars found in the system.</p>
        <a href="add-car.php" class="btn btn--primary">Add Your First Car</a>
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
                <?php foreach ($cars as $car): ?>
                <tr>
                    <td>
                        <img src="<?= sanitize($car['primary_image']) ?>" class="admin-table__thumb" alt="<?= sanitize($car['title']) ?>">
                    </td>
                    <td style="font-weight: 700; color: #1e293b;"><?= sanitize($car['title']) ?></td>
                    <td><?= sanitize($car['brand']) ?></td>
                    <td><?= (int) $car['year'] ?></td>
                    <td style="font-weight: 600; color: #0f172a;"><?= sanitize($car['price_formatted']) ?></td>
                    <td>
                        <?php if ($car['status'] === 'available'): ?>
                            <span class="badge badge--success">Available</span>
                        <?php else: ?>
                            <span class="badge badge--muted">Sold</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($car['featured'])): ?>
                            <span style="color: #475569; font-weight: 600;">Yes</span>
                        <?php else: ?>
                            <span style="color: #94a3b8;">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="admin-table__actions">
                            <a href="edit-car.php?id=<?= (int) $car['id'] ?>" class="btn btn--sm btn--outline">Edit</a>
                            <form method="POST" action="delete-car.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this car?');">
                                <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
