<?php
$body = [
    'title' => 'Đăng nhập - Torymail',
    'desc' => 'Đăng nhập vào hệ thống email Torymail'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $body['title'] ?></title>
    <link href="<?= asset_url('material/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('material/assets/css/icons.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/auth.css') ?>" rel="stylesheet">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <h2><i class="ri-mail-line"></i> Torymail</h2>
                <p>Hệ thống email chuyên nghiệp</p>
            </div>

            <div id="alert-box"></div>

            <form id="loginForm" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-mail-line"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-lock-line"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary toggle-password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Ghi nhớ</label>
                    </div>
                    <a href="<?= base_url('auth/forgot-password') ?>">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="ri-login-box-line"></i> Đăng nhập
                </button>

                <div class="text-center">
                    <span>Chưa có tài khoản?</span>
                    <a href="<?= base_url('auth/register') ?>">Đăng ký ngay</a>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $('.toggle-password').click(function() {
        var input = $(this).siblings('input');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
        } else {
            input.attr('type', 'password');
            icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
        }
    });

    $('#loginForm').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Đang xử lý...');

        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=login') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    window.location.href = '<?= base_url('inbox') ?>';
                } else {
                    $('#alert-box').html('<div class="alert alert-danger">' + res.message + '</div>');
                    btn.prop('disabled', false).html('<i class="ri-login-box-line"></i> Đăng nhập');
                }
            },
            error: function() {
                $('#alert-box').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                btn.prop('disabled', false).html('<i class="ri-login-box-line"></i> Đăng nhập');
            }
        });
    });
    </script>
</body>
</html>
