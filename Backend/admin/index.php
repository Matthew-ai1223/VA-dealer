<?php
require_once __DIR__ . '/../lib/auth.php';
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
