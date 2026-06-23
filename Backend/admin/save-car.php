<?php
/**
 * Handle car create/update from admin forms
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Car.php';

requireAdmin();

$carModel = new Car();
$id = (int) ($_POST['id'] ?? 0);
$existing = $id ? $carModel->getById($id, true) : null;

$data = [
    'title'       => trim($_POST['title'] ?? ''),
    'brand'       => trim($_POST['brand'] ?? ''),
    'model'       => trim($_POST['model'] ?? ''),
    'year'        => trim($_POST['year'] ?? ''),
    'price'       => trim($_POST['price'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'specs'       => [
        'mileage'      => trim($_POST['mileage'] ?? ''),
        'transmission' => trim($_POST['transmission'] ?? ''),
        'fuel'         => trim($_POST['fuel'] ?? ''),
        'color'        => trim($_POST['color'] ?? ''),
        'engine'       => trim($_POST['engine'] ?? ''),
    ],
    'images'      => $existing['images'] ?? [],
    'status'      => in_array($_POST['status'] ?? '', ['available', 'sold'], true) ? $_POST['status'] : 'available',
    'featured'    => !empty($_POST['featured']),
];

if (empty($data['title']) || empty($data['brand']) || empty($data['model'])) {
    $_SESSION['flash_error'] = 'Title, brand, and model are required.';
    header('Location: ' . ($id ? "edit-car.php?id={$id}" : 'add-car.php'));
    exit;
}

// Remove selected images
if (!empty($_POST['remove_images'])) {
    $toRemove = (array) $_POST['remove_images'];
    $data['images'] = array_values(array_diff($data['images'], $toRemove));
    deleteImageFiles($toRemove);
}

// Upload new images
$uploadResult = handleImageUpload($_FILES['images'] ?? [], $data['images']);
$data['images'] = $uploadResult['images'];

if ($id && $existing) {
    $carModel->update($id, $data);
    $message = 'Car updated successfully.';
} else {
    $carModel->create($data);
    $message = 'Car added successfully.';
}

if (!empty($uploadResult['errors'])) {
    $message .= ' Note: ' . implode(' ', $uploadResult['errors']);
}

$_SESSION['flash_success'] = $message;

header('Location: cars.php');
exit;
