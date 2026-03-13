<?php
$body = ['title' => 'Quên mật khẩu - Torymail'];
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
                <p>Khôi phục mật khẩu</p>
            </div>
            <div id="alert-box"></div>
            <form id="forgotForm">
                <div class="mb-3">
                    <label class="form-label">Email đăng ký</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-mail-line"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="ri-send-plane-line"></i> Gửi link khôi phục
                </button>
                <div class="text-center">
                    <a href="<?= base_url('auth/login') ?>">Quay lại đăng nhập</a>
                </div>
            </form>
        </div>
    </div>
    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});
    $('#forgotForm').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true);
        $.post('<?= base_url('ajaxs/user/auth.php?action=forgot_password') ?>', $(this).serialize(), function(res) {
            $('#alert-box').html('<div class="alert alert-' + (res.status === 'success' ? 'success' : 'danger') + '">' + res.message + '</div>');
            btn.prop('disabled', false);
        }, 'json');
    });
    </script>
</body>
</html>
