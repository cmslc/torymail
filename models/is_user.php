<?php
/**
 * User authentication check
 * Include this file at the top of user-only pages
 */

$getUser = null;

// Public mailbox session (no user account required)
if (!empty($_SESSION['public_mailbox_id'])) {
    $publicMailbox = $ToryMail->get_row_safe(
        "SELECT * FROM mailboxes WHERE id = ? AND status = 'active'",
        [$_SESSION['public_mailbox_id']]
    );
    if (!$publicMailbox) {
        unset($_SESSION['public_mailbox_id'], $_SESSION['public_mailbox_email'], $_SESSION['mailbox_id'], $_SESSION['mailbox_email']);
        redirect(base_url('auth/login'));
    }
    // Create a minimal user context for public mailbox
    $getUser = [
        'id'                      => 0,
        'fullname'                => $publicMailbox['display_name'] ?: $publicMailbox['email_address'],
        'email'                   => $publicMailbox['email_address'],
        'role'                    => 'user',
        'status'                  => 'active',
        'unread_count'            => $ToryMail->get_value_safe(
            "SELECT COUNT(*) FROM emails WHERE mailbox_id = ? AND folder = 'inbox' AND is_read = 0",
            [$publicMailbox['id']]
        ),
        'max_mailboxes_per_domain'=> 0,
        'max_domains'             => 0,
        'storage_quota'           => $publicMailbox['quota'],
        'storage_used'            => $publicMailbox['used_space'],
        'timezone'                => 'UTC',
        'signature'               => '',
    ];
    $_SESSION['mailbox_id'] = $publicMailbox['id'];
    $_SESSION['mailbox_email'] = $publicMailbox['email_address'];
} else {
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
}
