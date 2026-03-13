<?php
$body = ['title' => 'Đăng ký - Torymail'];
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $body['title'] ?></title>
    <link href="<?= asset_url('material/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('material/assets/css/icons.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('material/assets/css/app.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('material/assets/css/custom.css') ?>" rel="stylesheet">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body>
    <div class="auth-page-wrapper pt-5">
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z" fill="var(--vz-body-bg)"></path>
                </svg>
            </div>
        </div>

        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <h3 class="text-white"><i class="ri-mail-line"></i> Torymail</h3>
                            <p class="mt-2 fs-15 fw-medium">Tạo tài khoản mới</p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Đăng ký tài khoản</h5>
                                    <p class="text-muted">Tạo tài khoản miễn phí ngay hôm nay.</p>
                                </div>
                                <div id="alert-box"></div>
                                <div class="p-2 mt-4">
                                    <form id="registerForm" autocomplete="off">
                                        <div class="mb-3">
                                            <label class="form-label">Họ và tên</label>
                                            <input type="text" name="fullname" class="form-control" placeholder="Nguyễn Văn A" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Mật khẩu</label>
                                            <input type="password" name="password" class="form-control" placeholder="Tối thiểu 8 ký tự" required minlength="8">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Xác nhận mật khẩu</label>
                                            <input type="password" name="password_confirm" class="form-control" placeholder="Nhập lại mật khẩu" required>
                                        </div>
                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="mb-0">Đã có tài khoản? <a href="<?= base_url('auth/login') ?>" class="fw-semibold text-primary text-decoration-underline">Đăng nhập</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><div class="container"><div class="text-center"><p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> Torymail</p></div></div></footer>
    </div>
    <script src="<?= asset_url('material/assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        beforeSend: function(xhr, s) { if (s.type==='POST'&&typeof s.data==='string') s.data+='&_csrf_token='+encodeURIComponent($('meta[name="csrf-token"]').attr('content')); }
    });
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Đang xử lý...');
        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=register') ?>',
            method: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    window.location.href = '<?= base_url('auth/login') ?>?registered=1';
                } else {
                    $('#alert-box').html('<div class="alert alert-danger">' + res.message + '</div>');
                    btn.prop('disabled', false).html('Đăng ký');
                }
            },
            error: function() {
                $('#alert-box').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                btn.prop('disabled', false).html('Đăng ký');
            }
        });
    });
    </script>
</body>
</html>
