<?php
/**
 * User authentication check
 * Include this file at the top of user-only pages
 */

$getUser = null;

// Check session first, then cookie
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;

if (!$token) {
    redirect(base_url('auth/login'));
}

// Validate token
$getUser = $ToryMail->get_row_safe(
    "SELECT u.*,
            (SELECT COUNT(*) FROM emails WHERE mailbox_id IN (SELECT id FROM mailboxes WHERE user_id = u.id) AND folder = 'inbox' AND is_read = 0) as unread_count
     FROM users u
     WHERE u.token = ? AND u.status = 'active'",
    [$token]
);

if (!$getUser) {
    // Invalid token - clear session
    unset($_SESSION['user_login']);
    setcookie('torymail_token', '', time() - 3600, '/');
    redirect(base_url('auth/login'));
}

// Update session
$_SESSION['user_login'] = $token;

// Update last activity
$ToryMail->update_safe('users', ['last_activity' => gettime()], 'id = ?', [$getUser['id']]);
