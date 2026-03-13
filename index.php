<?php
session_start();

// Load environment
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    header('Location: install.php');
    exit;
}

// Parse .env
$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
    putenv(trim($key) . '=' . trim($value));
}

// Load core
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/libs/db.php';
require_once __DIR__ . '/libs/helper.php';

// Database connection
$ToryMail = new DB();

// Get settings
$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
if ($settingsRows) {
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Route handling
$module = isset($_GET['module']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['module']) : 'user';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : 'inbox';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Module routing
switch ($module) {
    case 'admin':
        $authFile = __DIR__ . '/models/is_admin.php';
        $viewDir = __DIR__ . '/resources/views/admin/';
        break;
    case 'auth':
        $authFile = null;
        $viewDir = __DIR__ . '/resources/views/auth/';
        break;
    case 'user':
    default:
        $module = 'user';
        $authFile = __DIR__ . '/models/is_user.php';
        $viewDir = __DIR__ . '/resources/views/user/';
        break;
}

// Check auth
if ($authFile) {
    require_once $authFile;
}

// Load view
$viewFile = $viewDir . $action . '.php';
if (file_exists($viewFile)) {
    require_once $viewFile;
} else {
    require_once __DIR__ . '/resources/views/common/404.php';
}
