<?php

/**
 * Torymail Helper Functions
 */

// ============================================================
// URL & Path Helpers
// ============================================================

function base_url($path = '')
{
    global $settings;
    $url = rtrim($settings['site_url'] ?? getenv('APP_URL') ?: '', '/');
    if ($path) $url .= '/' . ltrim($path, '/');
    return $url;
}

function admin_url($path = '')
{
    return base_url('admin/' . ltrim($path, '/'));
}

function asset_url($path = '')
{
    return base_url('public/' . ltrim($path, '/'));
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ============================================================
// Security Helpers
// ============================================================

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify()
{
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

function sanitize($str)
{
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function clean_input($str)
{
    $str = trim($str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return $str;
}

// ============================================================
// Password Helpers
// ============================================================

function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

// ============================================================
// Encryption Helpers (for SMTP passwords etc.)
// ============================================================

function encrypt_string($plaintext)
{
    $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-me';
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

function decrypt_string($ciphertext)
{
    $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-me';
    $parts = explode('::', base64_decode($ciphertext), 2);
    if (count($parts) !== 2) return '';
    return openssl_decrypt($parts[1], 'AES-256-CBC', $key, 0, $parts[0]);
}

// ============================================================
// Date & Time Helpers
// ============================================================

function gettime()
{
    return date('Y-m-d H:i:s');
}

function time_ago($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' năm trước';
    if ($diff->m > 0) return $diff->m . ' tháng trước';
    if ($diff->d > 0) return $diff->d . ' ngày trước';
    if ($diff->h > 0) return $diff->h . ' giờ trước';
    if ($diff->i > 0) return $diff->i . ' phút trước';
    return 'Vừa xong';
}

function format_date($datetime, $format = 'd/m/Y H:i')
{
    return date($format, strtotime($datetime));
}

// ============================================================
// Email Helpers
// ============================================================

function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_domain($domain)
{
    return preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain);
}

function get_email_domain($email)
{
    $parts = explode('@', $email);
    return $parts[1] ?? '';
}

function get_email_local($email)
{
    $parts = explode('@', $email);
    return $parts[0] ?? '';
}

function format_email_size($bytes)
{
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// ============================================================
// File Helpers
// ============================================================

function get_attachment_path($filename)
{
    return __DIR__ . '/../storage/attachments/' . $filename;
}

function allowed_attachment_types()
{
    return [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', 'application/x-rar-compressed',
        'text/plain', 'text/csv',
    ];
}

function max_attachment_size()
{
    return 25 * 1024 * 1024; // 25MB
}

// ============================================================
// Response Helpers
// ============================================================

function json_response($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function success_response($message, $data = [])
{
    json_response(array_merge(['status' => 'success', 'message' => $message], $data));
}

function error_response($message, $code = 400)
{
    json_response(['status' => 'error', 'message' => $message], $code);
}

// ============================================================
// Pagination Helper
// ============================================================

function paginate($total, $per_page = 20, $current_page = 1)
{
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
    ];
}

function render_pagination($pagination, $base_url)
{
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Previous
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($pagination['current_page'] - 1) . '">&laquo;</a></li>';
    }

    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $pagination['current_page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Next
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($pagination['current_page'] + 1) . '">&raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// ============================================================
// IP & User Agent
// ============================================================

function get_client_ip()
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

// ============================================================
// DNS Helpers
// ============================================================

function check_dns_record($domain, $type, $expected_value)
{
    $records = @dns_get_record($domain, constant('DNS_' . strtoupper($type)));
    if (!$records) return false;

    foreach ($records as $record) {
        switch (strtoupper($type)) {
            case 'TXT':
                if (isset($record['txt']) && strpos($record['txt'], $expected_value) !== false) return true;
                break;
            case 'MX':
                if (isset($record['target']) && $record['target'] === $expected_value) return true;
                break;
            case 'CNAME':
                if (isset($record['target']) && $record['target'] === $expected_value) return true;
                break;
            case 'A':
                if (isset($record['ip']) && $record['ip'] === $expected_value) return true;
                break;
        }
    }
    return false;
}

function generate_dkim_selector()
{
    return 'torymail' . date('Ymd');
}

function generate_verification_token()
{
    return 'torymail-verify-' . bin2hex(random_bytes(16));
}

// ============================================================
// String Helpers
// ============================================================

function str_truncate($str, $length = 100, $suffix = '...')
{
    if (mb_strlen($str) <= $length) return $str;
    return mb_substr($str, 0, $length) . $suffix;
}

function generate_token($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

function generate_message_id($domain)
{
    return '<' . uniqid('tm-', true) . '@' . $domain . '>';
}

// ============================================================
// Settings Helper
// ============================================================

function get_setting($key, $default = '')
{
    global $settings;
    return $settings[$key] ?? $default;
}

function set_setting($key, $value)
{
    global $ToryMail, $settings;
    $existing = $ToryMail->get_row_safe("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($existing) {
        $ToryMail->update_safe('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    } else {
        $ToryMail->insert_safe('settings', ['setting_key' => $key, 'setting_value' => $value]);
    }
    $settings[$key] = $value;
}
