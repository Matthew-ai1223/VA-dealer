<?php
/**
 * Cars API - CRUD endpoints for public and admin use
 * GET    /Backend/api/cars.php           - List cars (with filters)
 * GET    /Backend/api/cars.php?id=1      - Single car
 * GET    /Backend/api/cars.php?meta=1    - Brands & years for filters
 * POST   /Backend/api/cars.php           - Create car (admin)
 * PUT    /Backend/api/cars.php?id=1      - Update car (admin)
 * DELETE /Backend/api/cars.php?id=1      - Delete car (admin)
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Car.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$carModel = new Car();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$adminView = isAdminLoggedIn();

try {
    // Filter metadata for search dropdowns
    if ($method === 'GET' && isset($_GET['meta'])) {
        jsonResponse([
            'success' => true,
            'brands'  => $carModel->getBrands(),
            'years'   => $carModel->getYears(),
        ]);
    }

    // Single car
    if ($method === 'GET' && $id) {
        $car = $carModel->getById($id, $adminView);
        if (!$car) {
            jsonResponse(['success' => false, 'message' => 'Car not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $car]);
    }

    // List cars
    if ($method === 'GET') {
        $filters = [
            'brand'     => $_GET['brand'] ?? '',
            'model'     => $_GET['model'] ?? '',
            'year'      => $_GET['year'] ?? '',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'search'    => $_GET['search'] ?? '',
            'status'    => $_GET['status'] ?? '',
            'featured'  => isset($_GET['featured']) ? 1 : 0,
        ];

        $cars = $carModel->getAll($filters, $adminView);
        jsonResponse(['success' => true, 'data' => $cars, 'count' => count($cars)]);
    }

    // Create car
    if ($method === 'POST') {
        requireAdminApi();

        $data = collectCarData($_POST);
        $uploadResult = handleImageUpload($_FILES['images'] ?? []);
        $data['images'] = $uploadResult['images'];

        if (empty($data['title']) || empty($data['brand']) || empty($data['model'])) {
            jsonResponse(['success' => false, 'message' => 'Title, brand, and model are required'], 422);
        }

        $newId = $carModel->create($data);
        $car = $carModel->getById($newId, true);

        jsonResponse([
            'success' => true,
            'message' => 'Car created successfully',
            'data'    => $car,
            'warnings' => $uploadResult['errors'],
        ], 201);
    }

    // Update car
    if ($method === 'PUT' || ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
        requireAdminApi();

        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Car ID required'], 400);
        }

        $existing = $carModel->getById($id, true);
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Car not found'], 404);
        }

        $input = $method === 'PUT' ? parsePutInput() : $_POST;
        $data = collectCarData($input, $existing);

        // Handle image removal
        if (!empty($input['remove_images'])) {
            $toRemove = is_array($input['remove_images']) ? $input['remove_images'] : [$input['remove_images']];
            $data['images'] = array_values(array_diff($data['images'], $toRemove));
            deleteImageFiles($toRemove);
        }

        $uploadResult = handleImageUpload($_FILES['images'] ?? [], $data['images']);
        $data['images'] = $uploadResult['images'];

        $carModel->update($id, $data);
        $car = $carModel->getById($id, true);

        jsonResponse([
            'success'  => true,
            'message'  => 'Car updated successfully',
            'data'     => $car,
            'warnings' => $uploadResult['errors'],
        ]);
    }

    // Delete car
    if ($method === 'DELETE' || ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
        requireAdminApi();

        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Car ID required'], 400);
        }

        $deleted = $carModel->delete($id);
        if (!$deleted) {
            jsonResponse(['success' => false, 'message' => 'Car not found'], 404);
        }

        deleteImageFiles($deleted['images']);
        jsonResponse(['success' => true, 'message' => 'Car deleted successfully']);
    }

    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

function collectCarData(array $input, ?array $existing = null): array
{
    $specs = [
        'mileage'      => trim($input['mileage'] ?? ($existing['specs']['mileage'] ?? '')),
        'transmission' => trim($input['transmission'] ?? ($existing['specs']['transmission'] ?? '')),
        'fuel'         => trim($input['fuel'] ?? ($existing['specs']['fuel'] ?? '')),
        'color'        => trim($input['color'] ?? ($existing['specs']['color'] ?? '')),
        'engine'       => trim($input['engine'] ?? ($existing['specs']['engine'] ?? '')),
    ];

    return [
        'title'       => trim($input['title'] ?? ($existing['title'] ?? '')),
        'brand'       => trim($input['brand'] ?? ($existing['brand'] ?? '')),
        'model'       => trim($input['model'] ?? ($existing['model'] ?? '')),
        'year'        => trim($input['year'] ?? ($existing['year'] ?? '')),
        'price'       => trim($input['price'] ?? ($existing['price'] ?? '')),
        'description' => trim($input['description'] ?? ($existing['description'] ?? '')),
        'specs'       => $specs,
        'images'      => $existing['images'] ?? [],
        'status'      => in_array($input['status'] ?? '', ['available', 'sold'], true)
            ? $input['status']
            : ($existing['status'] ?? 'available'),
        'featured'    => !empty($input['featured']),
    ];
}

function parsePutInput(): array
{
    parse_str(file_get_contents('php://input'), $data);
    return $data;
}
