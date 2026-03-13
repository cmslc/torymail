<?php
session_start();

// If already installed, redirect
if (file_exists(__DIR__ . '/.env')) {
    header('Location: index.php');
    exit;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // Test database connection
            $db_host = trim($_POST['db_host'] ?? 'localhost');
            $db_user = trim($_POST['db_username'] ?? 'root');
            $db_pass = $_POST['db_password'] ?? '';
            $db_name = trim($_POST['db_database'] ?? 'torymail');

            $conn = @new mysqli($db_host, $db_user, $db_pass);
            if ($conn->connect_error) {
                $error = 'Không thể kết nối database: ' . $conn->connect_error;
            } else {
                // Create database if not exists
                $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $conn->select_db($db_name);

                // Run migration
                $migration = file_get_contents(__DIR__ . '/migrations/1.0.0.sql');
                if ($migration) {
                    $conn->multi_query($migration);
                    // Consume all results
                    while ($conn->next_result()) {
                        $conn->store_result();
                    }
                }

                $conn->close();

                // Save to session for next step
                $_SESSION['install'] = [
                    'db_host' => $db_host,
                    'db_username' => $db_user,
                    'db_password' => $db_pass,
                    'db_database' => $db_name,
                ];

                header('Location: install.php?step=3');
                exit;
            }
            break;

        case 3:
            // Save settings and create admin
            $site_url = rtrim(trim($_POST['site_url'] ?? ''), '/');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_name = trim($_POST['admin_name'] ?? 'Admin');
            $mail_hostname = trim($_POST['mail_hostname'] ?? '');
            $encryption_key = bin2hex(random_bytes(32));

            if (!$admin_email || !$admin_password) {
                $error = 'Vui lòng điền đầy đủ thông tin admin.';
            } else {
                $install = $_SESSION['install'] ?? [];
                if (empty($install)) {
                    header('Location: install.php?step=2');
                    exit;
                }

                // Write .env file
                $env_content = "DB_HOST={$install['db_host']}\n";
                $env_content .= "DB_USERNAME={$install['db_username']}\n";
                $env_content .= "DB_PASSWORD={$install['db_password']}\n";
                $env_content .= "DB_DATABASE={$install['db_database']}\n\n";
                $env_content .= "MAIL_STORAGE_PATH=/var/mail/vhosts\n";
                $env_content .= "MAIL_SERVER_HOSTNAME={$mail_hostname}\n\n";
                $env_content .= "IMAP_PORT=993\n";
                $env_content .= "SMTP_PORT=587\n\n";
                $env_content .= "ENCRYPTION_KEY={$encryption_key}\n\n";
                $env_content .= "APP_URL={$site_url}\n";

                file_put_contents(__DIR__ . '/.env', $env_content);

                // Connect to database and set up admin
                $conn = new mysqli($install['db_host'], $install['db_username'], $install['db_password'], $install['db_database']);
                $conn->set_charset('utf8mb4');

                // Update/create admin user
                $token = bin2hex(random_bytes(32));
                $hashed = password_hash($admin_password, PASSWORD_BCRYPT);

                // Delete default admin if exists
                $conn->query("DELETE FROM users WHERE email = 'admin@torymail.local'");

                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status, token, max_domains, max_mailboxes_per_domain, storage_quota, created_at, updated_at) VALUES (?, ?, ?, 'admin', 'active', ?, 999, 999, 10737418240, NOW(), NOW())");
                $stmt->bind_param('ssss', $admin_name, $admin_email, $hashed, $token);
                $stmt->execute();

                // Update settings
                $settings_to_update = [
                    'site_name' => 'Torymail',
                    'site_url' => $site_url,
                    'mail_server_hostname' => $mail_hostname,
                ];
                foreach ($settings_to_update as $key => $value) {
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->bind_param('ss', $value, $key);
                    $stmt->execute();
                }

                $conn->close();
                unset($_SESSION['install']);

                header('Location: install.php?step=4');
                exit;
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Torymail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root { --primary: #4F46E5; }
        body { background: linear-gradient(135deg, #1e1e2d 0%, #2d2d44 50%, #4338CA 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .install-card { background: #fff; border-radius: 16px; padding: 40px; max-width: 600px; width: 100%; margin: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .install-logo { text-align: center; margin-bottom: 30px; }
        .install-logo h2 { color: var(--primary); font-weight: 700; }
        .install-logo p { color: #6b7280; font-size: 14px; }
        .step-indicator { display: flex; justify-content: center; gap: 8px; margin-bottom: 30px; }
        .step-dot { width: 36px; height: 36px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #9ca3af; }
        .step-dot.active { background: var(--primary); color: #fff; }
        .step-dot.done { background: #10b981; color: #fff; }
        .btn-primary { background: var(--primary); border-color: var(--primary); border-radius: 8px; padding: 10px 24px; font-weight: 600; }
        .btn-primary:hover { background: #4338CA; border-color: #4338CA; }
        .form-control { border-radius: 8px; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-logo">
            <h2><i class="ri-mail-line"></i> Torymail</h2>
            <p>Hướng dẫn cài đặt</p>
        </div>

        <div class="step-indicator">
            <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
            <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
            <div class="step-dot <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' ?>">3</div>
            <div class="step-dot <?= $step >= 4 ? 'active' : '' ?>">4</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Step 1: Requirements Check -->
            <h4 class="mb-4">Kiểm tra yêu cầu hệ thống</h4>
            <?php
            $requirements = [
                'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'MySQLi Extension' => extension_loaded('mysqli'),
                'OpenSSL Extension' => extension_loaded('openssl'),
                'Mbstring Extension' => extension_loaded('mbstring'),
                'JSON Extension' => extension_loaded('json'),
                'Fileinfo Extension' => extension_loaded('fileinfo'),
                'storage/ writable' => is_writable(__DIR__ . '/storage'),
            ];
            $all_pass = !in_array(false, $requirements);
            ?>
            <table class="table">
                <?php foreach ($requirements as $name => $pass): ?>
                <tr>
                    <td><?= $name ?></td>
                    <td class="text-end">
                        <?php if ($pass): ?>
                            <span class="text-success"><i class="ri-check-line"></i> OK</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="ri-close-line"></i> Thiếu</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php if ($all_pass): ?>
                <a href="install.php?step=2" class="btn btn-primary w-100">Tiếp tục <i class="ri-arrow-right-line"></i></a>
            <?php else: ?>
                <div class="alert alert-warning">Vui lòng cài đặt các yêu cầu còn thiếu trước khi tiếp tục.</div>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Database Configuration -->
            <h4 class="mb-4">Cấu hình Database</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Username</label>
                    <input type="text" name="db_username" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_database" class="form-control" value="torymail" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kết nối & Tạo bảng <i class="ri-arrow-right-line"></i></button>
            </form>

        <?php elseif ($step === 3): ?>
            <!-- Step 3: Admin & Site Settings -->
            <h4 class="mb-4">Cấu hình Website & Admin</h4>
            <form method="POST">
                <h6 class="text-muted mb-3">Website</h6>
                <div class="mb-3">
                    <label class="form-label">URL Website</label>
                    <input type="url" name="site_url" class="form-control" placeholder="https://mail.yourdomain.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mail Server Hostname</label>
                    <input type="text" name="mail_hostname" class="form-control" placeholder="mail.yourdomain.com">
                    <small class="text-muted">Hostname cho MX record</small>
                </div>

                <h6 class="text-muted mb-3 mt-4">Tài khoản Admin</h6>
                <div class="mb-3">
                    <label class="form-label">Họ tên Admin</label>
                    <input type="text" name="admin_name" class="form-control" value="Admin" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Admin</label>
                    <input type="email" name="admin_email" class="form-control" placeholder="admin@yourdomain.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu Admin</label>
                    <input type="password" name="admin_password" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary w-100">Hoàn tất cài đặt <i class="ri-check-line"></i></button>
            </form>

        <?php elseif ($step === 4): ?>
            <!-- Step 4: Complete -->
            <div class="text-center">
                <div class="mb-4">
                    <i class="ri-check-double-line" style="font-size: 64px; color: #10b981;"></i>
                </div>
                <h4 class="text-success mb-3">Cài đặt thành công!</h4>
                <p class="text-muted mb-4">Torymail đã được cài đặt thành công. Bạn có thể đăng nhập bằng tài khoản admin vừa tạo.</p>
                <div class="alert alert-warning text-start">
                    <strong><i class="ri-alert-line"></i> Quan trọng:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Xóa hoặc đổi tên file <code>install.php</code> để bảo mật</li>
                        <li>Cấu hình cron job: <code>* * * * * php <?= __DIR__ ?>/cron/cron.php</code></li>
                        <li>Cấu hình Postfix/mail server để pipe email vào hệ thống</li>
                    </ul>
                </div>
                <a href="auth/login" class="btn btn-primary">Đăng nhập <i class="ri-login-box-line"></i></a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
