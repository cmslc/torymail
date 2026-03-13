<?php
/**
 * Admin authentication check
 */

$getAdmin = null;

$token = $_SESSION['admin_login'] ?? $_COOKIE['torymail_admin_token'] ?? null;

if (!$token) {
    redirect(base_url('auth/login'));
}

$getAdmin = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND role = 'admin' AND status = 'active'",
    [$token]
);

if (!$getAdmin) {
    unset($_SESSION['admin_login']);
    setcookie('torymail_admin_token', '', time() - 3600, '/');
    redirect(base_url('auth/login'));
}

$_SESSION['admin_login'] = $token;
$ToryMail->update_safe('users', ['last_activity' => gettime()], 'id = ?', [$getAdmin['id']]);
