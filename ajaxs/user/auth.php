<?php
require_once __DIR__ . '/../bootstrap.php';

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            error_response('Email and password are required');
        }
        if (!validate_email($email)) {
            error_response('Invalid email format');
        }

        $user = $ToryMail->get_row_safe("SELECT * FROM users WHERE email = ?", [$email]);
        if (!$user) {
            error_response('Invalid email or password');
        }

        if ($user['status'] === 'banned') {
            error_response('Your account has been suspended');
        }
        if ($user['status'] === 'inactive') {
            error_response('Your account is inactive');
        }

        if (!verify_password($password, $user['password'])) {
            // Log failed attempt
            $ToryMail->insert_safe('activity_logs', [
                'user_id'    => $user['id'],
                'action'     => 'login_failed',
                'details'    => 'Invalid password attempt',
                'ip_address' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => gettime(),
            ]);
            error_response('Invalid email or password');
        }

        // Generate token and set session
        $token = generate_token(64);
        $ToryMail->update_safe('users', [
            'token'         => $token,
            'last_activity' => gettime(),
        ], 'id = ?', [$user['id']]);

        $_SESSION['user_login'] = $token;
        $expiry = $remember ? time() + (30 * 24 * 60 * 60) : 0;
        setcookie('torymail_token', $token, $expiry, '/', '', true, true);

        if ($user['role'] === 'admin') {
            $_SESSION['admin_login'] = $token;
            setcookie('torymail_admin_token', $token, $expiry, '/', '', true, true);
        }

        // Log activity
        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $user['id'],
            'action'     => 'login',
            'details'    => 'Successful login',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Login successful', [
            'redirect' => ($user['role'] === 'admin') ? base_url('admin') : base_url('inbox'),
        ]);
        break;

    // -------------------------------------------------------
    // REGISTER
    // -------------------------------------------------------
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        if (get_setting('allow_registration', '1') !== '1') {
            error_response('Registration is currently disabled');
        }

        $fullname = sanitize($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($fullname) || empty($email) || empty($password)) {
            error_response('All fields are required');
        }
        if (!validate_email($email)) {
            error_response('Invalid email format');
        }
        if (mb_strlen($password) < 8) {
            error_response('Password must be at least 8 characters');
        }
        if ($password !== $password_confirm) {
            error_response('Passwords do not match');
        }

        // Check unique email
        $exists = $ToryMail->get_value_safe("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
        if ($exists > 0) {
            error_response('Email address is already registered');
        }

        $userId = $ToryMail->insert_safe('users', [
            'fullname'                => $fullname,
            'email'                   => $email,
            'password'                => hash_password($password),
            'role'                    => 'user',
            'status'                  => 'active',
            'timezone'                => 'UTC',
            'max_domains'             => (int)get_setting('max_domains_per_user', '5'),
            'max_mailboxes_per_domain'=> (int)get_setting('max_mailboxes_per_domain', '50'),
            'storage_quota'           => (int)get_setting('default_quota', '1073741824'),
            'created_at'              => gettime(),
            'updated_at'              => gettime(),
        ]);

        if (!$userId) {
            error_response('Registration failed. Please try again.');
        }

        // Log activity
        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $userId,
            'action'     => 'register',
            'details'    => 'New user registration',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Registration successful. You can now log in.');
        break;

    // -------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------
    case 'logout':
        $token = $_SESSION['user_login'] ?? $_SESSION['admin_login'] ?? null;

        if ($token) {
            // Clear token in DB
            $ToryMail->update_safe('users', ['token' => null], 'token = ?', [$token]);
        }

        // Clear session
        unset($_SESSION['user_login']);
        unset($_SESSION['admin_login']);
        session_destroy();

        // Clear cookies
        setcookie('torymail_token', '', time() - 3600, '/', '', true, true);
        setcookie('torymail_admin_token', '', time() - 3600, '/', '', true, true);

        success_response('Logged out successfully', [
            'redirect' => base_url('auth/login'),
        ]);
        break;

    // -------------------------------------------------------
    // FORGOT PASSWORD
    // -------------------------------------------------------
    case 'forgot_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !validate_email($email)) {
            error_response('Please provide a valid email address');
        }

        $user = $ToryMail->get_row_safe("SELECT id FROM users WHERE email = ? AND status = 'active'", [$email]);

        // Always return success to prevent email enumeration
        if ($user) {
            // Invalidate previous reset tokens
            $ToryMail->update_safe('password_resets', ['used' => 1], 'user_id = ? AND used = 0', [$user['id']]);

            $resetToken = generate_token(64);
            $expiresAt  = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $ToryMail->insert_safe('password_resets', [
                'user_id'    => $user['id'],
                'token'      => $resetToken,
                'expires_at' => $expiresAt,
                'used'       => 0,
                'created_at' => gettime(),
            ]);

            // Log activity
            $ToryMail->insert_safe('activity_logs', [
                'user_id'    => $user['id'],
                'action'     => 'forgot_password',
                'details'    => 'Password reset requested. Token: ' . $resetToken,
                'ip_address' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => gettime(),
            ]);
        }

        success_response('If an account exists with that email, a password reset link has been generated.');
        break;

    // -------------------------------------------------------
    // RESET PASSWORD
    // -------------------------------------------------------
    case 'reset_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $token       = trim($_POST['token'] ?? '');
        $password    = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password)) {
            error_response('Token and new password are required');
        }
        if (mb_strlen($password) < 8) {
            error_response('Password must be at least 8 characters');
        }
        if ($password !== $passwordConfirm) {
            error_response('Passwords do not match');
        }

        $reset = $ToryMail->get_row_safe(
            "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );
        if (!$reset) {
            error_response('Invalid or expired reset token');
        }

        // Update user password
        $ToryMail->update_safe('users', [
            'password'   => hash_password($password),
            'updated_at' => gettime(),
        ], 'id = ?', [$reset['user_id']]);

        // Mark token as used
        $ToryMail->update_safe('password_resets', ['used' => 1], 'id = ?', [$reset['id']]);

        // Log activity
        $ToryMail->insert_safe('activity_logs', [
            'user_id'    => $reset['user_id'],
            'action'     => 'reset_password',
            'details'    => 'Password reset completed',
            'ip_address' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => gettime(),
        ]);

        success_response('Password has been reset successfully. You can now log in.');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
