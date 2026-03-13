<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Clear all session data
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear remember me cookies
setcookie('torymail_token', '', time() - 42000, '/');
setcookie('torymail_admin_token', '', time() - 42000, '/');

// Redirect to login
header('Location: ' . base_url('auth/login'));
exit;
