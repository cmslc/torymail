<?php
$body = [
    'title' => 'Đăng nhập - Torymail',
    'desc' => 'Đăng nhập vào hệ thống email Torymail'
];
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
                            <div>
                                <a href="<?= base_url() ?>" class="d-inline-block auth-logo">
                                    <h3 class="text-white"><i class="ri-mail-line"></i> Torymail</h3>
                                </a>
                            </div>
                            <p class="mt-2 fs-15 fw-medium">Hệ thống email chuyên nghiệp</p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Chào mừng trở lại!</h5>
                                    <p class="text-muted">Đăng nhập để tiếp tục sử dụng Torymail.</p>
                                </div>

                                <div id="alert-box"></div>

                                <div class="p-2 mt-4">
                                    <form id="loginForm" autocomplete="off">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" id="email" placeholder="Nhập email" required>
                                        </div>

                                        <div class="mb-3">
                                            <div class="float-end">
                                                <a href="<?= base_url('auth/forgot-password') ?>" class="text-muted">Quên mật khẩu?</a>
                                            </div>
                                            <label for="password" class="form-label">Mật khẩu</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" name="password" class="form-control pe-5" id="password" placeholder="Nhập mật khẩu" required>
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                                    <i class="ri-eye-fill align-middle"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <p class="mb-0">Chưa có tài khoản? <a href="<?= base_url('auth/register') ?>" class="fw-semibold text-primary text-decoration-underline">Đăng ký ngay</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> Torymail</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="<?= asset_url('material/assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    document.getElementById('password-addon').addEventListener('click', function() {
        var input = document.getElementById('password');
        if (input.type === 'password') {
            input.type = 'text';
            this.querySelector('i').classList.replace('ri-eye-fill', 'ri-eye-off-fill');
        } else {
            input.type = 'password';
            this.querySelector('i').classList.replace('ri-eye-off-fill', 'ri-eye-fill');
        }
    });

    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        beforeSend: function(xhr, settings) {
            if (settings.type === 'POST' && settings.data) {
                var token = $('meta[name="csrf-token"]').attr('content');
                if (typeof settings.data === 'string') {
                    settings.data += '&_csrf_token=' + encodeURIComponent(token);
                }
            }
        }
    });

    $('#loginForm').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin align-middle me-1"></i> Đang xử lý...');

        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=login') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    window.location.href = res.redirect || '<?= base_url('inbox') ?>';
                } else {
                    $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i>' + res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    btn.prop('disabled', false).html('Đăng nhập');
                }
            },
            error: function() {
                $('#alert-box').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                btn.prop('disabled', false).html('Đăng nhập');
            }
        });
    });
    </script>
</body>
</html>
