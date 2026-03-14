<?php
$body = ['title' => __('register_title')];
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
                            <h3 class="text-white"><i class="ri-mail-line"></i> Torymail</h3>
                            <p class="mt-2 fs-15 fw-medium"><?= __('register_subtitle') ?></p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary"><?= __('register') ?></h5>
                                    <p class="text-muted"><?= __('register_desc') ?></p>
                                </div>
                                <div id="alert-box"></div>
                                <div class="p-2 mt-4">
                                    <form id="registerForm" autocomplete="off">
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('fullname') ?></label>
                                            <input type="text" name="fullname" class="form-control" placeholder="<?= __('fullname_placeholder') ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('email') ?></label>
                                            <input type="email" name="email" class="form-control" placeholder="<?= __('email_register_placeholder') ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('password') ?></label>
                                            <input type="password" name="password" class="form-control" placeholder="<?= __('password_min') ?>" required minlength="8">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('confirm_password') ?></label>
                                            <input type="password" name="password_confirm" class="form-control" placeholder="<?= __('confirm_password_placeholder') ?>" required>
                                        </div>
                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit"><?= __('register') ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="mb-0"><?= __('has_account') ?> <a href="<?= base_url('auth/login') ?>" class="fw-semibold text-primary text-decoration-underline"><?= __('login') ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><div class="container"><div class="text-center"><div class="mb-2"><a href="?lang=en" class="text-muted me-2 <?= current_lang() === 'en' ? 'fw-bold text-primary' : '' ?>">English</a><span class="text-muted">|</span><a href="?lang=vi" class="text-muted ms-2 <?= current_lang() === 'vi' ? 'fw-bold text-primary' : '' ?>">Tiếng Việt</a></div><p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> Torymail</p></div></div></footer>
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
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> <?= __('processing') ?>');
        $.ajax({
            url: '<?= base_url('ajaxs/user/auth.php?action=register') ?>',
            method: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#alert-box').html('<div class="alert alert-success"><i class="ri-check-double-line me-2"></i><?= __("register_success") ?></div>');
                    setTimeout(function() { window.location.href = '<?= base_url("auth/login") ?>'; }, 1500);
                } else {
                    $('#alert-box').html('<div class="alert alert-danger">' + res.message + '</div>');
                    btn.prop('disabled', false).html('<?= __('register') ?>');
                }
            },
            error: function() {
                $('#alert-box').html('<div class="alert alert-danger"><?= __('server_error') ?></div>');
                btn.prop('disabled', false).html('<?= __('register') ?>');
            }
        });
    });
    </script>
</body>
</html>
