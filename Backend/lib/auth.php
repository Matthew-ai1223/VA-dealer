<?php
/**
 * Admin authentication helpers
 * Stage 2: add token-based API auth for automation services
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

function startAdminSession(): void
{
    $config = appConfig();
    if (session_status() === PHP_SESSION_NONE) {
        session_name($config['session_name']);
        session_start();
    }
}

function isAdminLoggedIn(): bool
{
    startAdminSession();
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . url('Backend/admin/login.php'));
        exit;
    }
}

function loginAdmin(string $username, string $password): bool
{
    startAdminSession();
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        return true;
    }

    return false;
}

function logoutAdmin(): void
{
    startAdminSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function requireAdminApi(): void
{
    if (!isAdminLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
