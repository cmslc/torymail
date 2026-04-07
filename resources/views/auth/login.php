<?php
$body = [
    'title' => __('login') . ' — ' . get_setting('site_name', 'Torymail'),
    'desc' => __('login_subtitle')
];
?>
<!doctype html>
<html lang="<?= current_lang() ?>" data-bs-theme="light">
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
                                    <h3 class="text-white"><i class="ri-mail-line"></i> <?= htmlspecialchars(get_setting('site_name', 'Torymail')) ?></h3>
                                </a>
                            </div>
                            <p class="mt-2 fs-15 fw-medium"><?= __('system_tagline') ?></p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary"><?= __('login_welcome') ?></h5>
                                    <p class="text-muted"><?= __('login_desc') ?></p>
                                </div>

                                <div id="alert-box"></div>

                                <div class="p-2 mt-4">
                                    <form id="loginForm" autocomplete="off">
                                        <div class="mb-3">
                                            <label for="email" class="form-label"><?= __('email') ?></label>
                                            <input type="email" name="email" class="form-control" id="email" placeholder="<?= __('email_placeholder') ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <div class="float-end">
                                                <a href="<?= base_url('auth/forgot-password') ?>" class="text-muted"><?= __('forgot_password') ?></a>
                                            </div>
                                            <label for="password" class="form-label"><?= __('password') ?></label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" name="password" class="form-control pe-5" id="password" placeholder="<?= __('password_placeholder') ?>" required>
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                                    <i class="ri-eye-fill align-middle"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                            <label class="form-check-label" for="remember"><?= __('remember_me') ?></label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit"><?= __('login') ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <p class="mb-0"><?= __('no_account') ?> <a href="<?= base_url('auth/register') ?>" class="fw-semibold text-primary text-decoration-underline"><?= __('register_now') ?></a></p>
                            <p class="mb-0 mt-2"><?= __('or_create_mailbox') ?> <a href="<?= base_url('auth/create-mailbox') ?>" class="fw-semibold text-primary text-decoration-underline"><?= __('create_mailbox_link') ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="text-center">
                    <div class="mb-2">
                        <a href="?lang=en" class="text-muted me-2 <?= current_lang() === 'en' ? 'fw-bold text-primary' : '' ?>">English</a>
                        <span class="text-muted">|</span>
                        <a href="?lang=vi" class="text-muted ms-2 <?= current_lang() === 'vi' ? 'fw-bold text-primary' : '' ?>">Tiếng Việt</a>
                    </div>
                    <p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> <?= htmlspecialchars(get_setting('site_name', 'Torymail')) ?></p>
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
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin align-middle me-1"></i> <?= __('processing') ?>');

        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=login') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#alert-box').html('<div class="alert alert-success"><i class="ri-check-double-line me-2"></i><?= __("login_success") ?></div>');
                    setTimeout(function() { window.location.href = res.redirect || '<?= base_url("inbox") ?>'; }, 800);
                } else {
                    $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i>' + res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    btn.prop('disabled', false).html('<?= __('login') ?>');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('server_error')) ?>;
                $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i>' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                btn.prop('disabled', false).html('<?= __('login') ?>');
            }
        });
    });
    </script>
</body>
</html>
