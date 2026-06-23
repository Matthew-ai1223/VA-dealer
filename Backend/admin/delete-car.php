<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Car.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$carModel = new Car();
$deleted = $carModel->delete($id);

if ($deleted) {
    deleteImageFiles($deleted['images']);
    $_SESSION['flash_success'] = 'Car deleted successfully.';
}

header('Location: cars.php');
exit;
