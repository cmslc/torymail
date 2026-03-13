<?php
$body = [
    'title' => 'Đăng ký - Torymail',
    'desc' => 'Tạo tài khoản Torymail mới'
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
                <p>Tạo tài khoản mới</p>
            </div>

            <div id="alert-box"></div>

            <form id="registerForm" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-user-line"></i></span>
                        <input type="text" name="fullname" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                </div>

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
                        <input type="password" name="password" class="form-control" placeholder="Tối thiểu 8 ký tự" required minlength="8">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-lock-line"></i></span>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="ri-user-add-line"></i> Đăng ký
                </button>

                <div class="text-center">
                    <span>Đã có tài khoản?</span>
                    <a href="<?= base_url('auth/login') ?>">Đăng nhập</a>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $('#registerForm').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Đang xử lý...');

        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=register') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    window.location.href = '<?= base_url('auth/login') ?>?registered=1';
                } else {
                    $('#alert-box').html('<div class="alert alert-danger">' + res.message + '</div>');
                    btn.prop('disabled', false).html('<i class="ri-user-add-line"></i> Đăng ký');
                }
            },
            error: function() {
                $('#alert-box').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                btn.prop('disabled', false).html('<i class="ri-user-add-line"></i> Đăng ký');
            }
        });
    });
    </script>
</body>
</html>
