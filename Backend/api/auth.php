<?php
/**
 * Auth API - admin login/logout/session check
 */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Username and password required'], 422);
    }

    if (loginAdmin($username, $password)) {
        jsonResponse([
            'success'  => true,
            'message'  => 'Login successful',
            'username' => $_SESSION['admin_username'],
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
}

if ($method === 'POST' && $action === 'logout') {
    logoutAdmin();
    jsonResponse(['success' => true, 'message' => 'Logged out']);
}

if ($method === 'GET' && $action === 'check') {
    jsonResponse([
        'success'  => isAdminLoggedIn(),
        'username' => $_SESSION['admin_username'] ?? null,
    ]);
}

jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
