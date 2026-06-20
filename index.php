<?php
/** Root redirect to public website */
require_once __DIR__ . '/Backend/lib/helpers.php';
header('Location: ' . url('Frontend/index.php'));
exit;
