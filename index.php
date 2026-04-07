<?php
// Session security (must be set before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

define('IN_SITE', true);

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

// Language switcher via query param
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'vi'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language
load_language();

// Route handling
$module = isset($_GET['module']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['module']) : '';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

// Default: show temp mail page as homepage
if ($module === '' && $action === '') {
    $module = 'auth';
    $action = 'create-mailbox';
} elseif ($module === 'user' && $action === '') {
    $action = 'inbox';
} elseif ($module === 'admin' && $action === '') {
    $action = 'home';
} elseif ($module === 'auth' && $action === '') {
    $action = 'create-mailbox';
}
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

// Mailbox login restriction — only allow inbox, compose, read pages
if ($module === 'user' && (!empty($_SESSION['mailbox_id']) || !empty($_SESSION['public_mailbox_id']))) {
    $allowedMailboxPages = ['inbox', 'compose', 'read'];
    if (!in_array($action, $allowedMailboxPages)) {
        header('Location: ' . base_url('inbox'));
        exit;
    }
}

// Load view
$viewFile = $viewDir . $action . '.php';
if (file_exists($viewFile)) {
    require_once $viewFile;
} else {
    require_once __DIR__ . '/resources/views/common/404.php';
}
