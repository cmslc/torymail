<?php
$body = [
    'title' => __('create_mailbox_title') . ' — ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('create_mailbox_subtitle')
];

// Fetch shared domains
$sharedDomains = $ToryMail->get_list_safe(
    "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC",
    []
);
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
                            <h3 class="text-white"><i class="ri-mail-line"></i> <?= htmlspecialchars(get_setting('site_name', 'Torymail')) ?></h3>
                            <p class="mt-2 fs-15 fw-medium"><?= __('create_mailbox_subtitle') ?></p>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary"><?= __('create_mailbox_heading') ?></h5>
                                    <p class="text-muted"><?= __('create_mailbox_desc') ?></p>
                                </div>

                                <div id="alert-box"></div>

                                <!-- Success result (hidden by default) -->
                                <div id="success-result" class="d-none">
                                    <div class="text-center py-4">
                                        <div class="avatar-lg mx-auto mb-4">
                                            <div class="avatar-title bg-light text-success rounded-circle fs-1">
                                                <i class="ri-checkbox-circle-fill"></i>
                                            </div>
                                        </div>
                                        <h5 class="text-success"><?= __('create_mailbox_success_title') ?></h5>
                                        <p class="text-muted mb-1"><?= __('create_mailbox_success_desc') ?></p>
                                        <p class="fw-semibold fs-5 mb-4" id="created-email"></p>
                                        <a href="<?= base_url('auth/login') ?>" class="btn btn-primary">
                                            <i class="ri-login-box-line me-1"></i> <?= __('create_mailbox_login_now') ?>
                                        </a>
                                    </div>
                                </div>

                                <!-- Create form -->
                                <div id="create-form" class="p-2 mt-4">
                                    <?php if (empty($sharedDomains)): ?>
                                        <div class="alert alert-warning text-center">
                                            <i class="ri-information-line me-1"></i> <?= __('create_mailbox_no_domains') ?>
                                        </div>
                                    <?php else: ?>
                                    <form id="createMailboxForm" autocomplete="off">
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('email_address') ?></label>
                                            <div class="input-group">
                                                <input type="text" name="local_part" class="form-control" id="local_part"
                                                       placeholder="<?= __('email_username') ?>" required
                                                       pattern="[a-z0-9._-]+" minlength="3"
                                                       oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9._-]/g, '')">
                                                <span class="input-group-text">@</span>
                                                <select name="domain_id" class="form-select" id="domain_id" required>
                                                    <?php foreach ($sharedDomains as $d): ?>
                                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-text"><?= __('create_mailbox_email_hint') ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= __('display_name') ?> <small class="text-muted">(<?= __('optional') ?>)</small></label>
                                            <input type="text" name="display_name" class="form-control" placeholder="<?= __('fullname_placeholder') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= __('password') ?></label>
                                            <div class="position-relative auth-pass-inputgroup">
                                                <input type="password" name="password" class="form-control pe-5" id="password"
                                                       placeholder="<?= __('password_min') ?>" required minlength="8">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                                    <i class="ri-eye-fill align-middle"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= __('confirm_password') ?></label>
                                            <input type="password" name="password_confirm" class="form-control" id="password_confirm"
                                                   placeholder="<?= __('confirm_password_placeholder') ?>" required minlength="8">
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit">
                                                <i class="ri-mail-add-line me-1"></i> <?= __('create_mailbox_btn') ?>
                                            </button>
                                        </div>
                                    </form>
                                    <?php endif; ?>
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

        <footer class="footer"><div class="container"><div class="text-center"><div class="mb-2"><a href="?lang=en" class="text-muted me-2 <?= current_lang() === 'en' ? 'fw-bold text-primary' : '' ?>">English</a><span class="text-muted">|</span><a href="?lang=vi" class="text-muted ms-2 <?= current_lang() === 'vi' ? 'fw-bold text-primary' : '' ?>">Tiếng Vit</a></div><p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> <?= htmlspecialchars(get_setting('site_name', 'Torymail')) ?></p></div></div></footer>
    </div>

    <script src="<?= asset_url('material/assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
    <script>
    // Toggle password visibility
    document.getElementById('password-addon')?.addEventListener('click', function() {
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
        beforeSend: function(xhr, s) {
            if (s.type === 'POST' && typeof s.data === 'string') {
                s.data += '&_csrf_token=' + encodeURIComponent($('meta[name="csrf-token"]').attr('content'));
            }
        }
    });

    $('#createMailboxForm').submit(function(e) {
        e.preventDefault();

        var password = $('#password').val();
        var passwordConfirm = $('#password_confirm').val();

        if (password !== passwordConfirm) {
            $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i><?= __("create_mailbox_password_mismatch") ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            return;
        }

        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> <?= __("creating") ?>');

        $.ajax({
            url: '<?= base_url("ajaxs/public/mailboxes.php?action=create") ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#create-form').addClass('d-none');
                    $('#alert-box').empty();
                    $('#created-email').text(res.email_address);
                    $('#success-result').removeClass('d-none');
                } else {
                    $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i>' + res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    btn.prop('disabled', false).html('<i class="ri-mail-add-line me-1"></i> <?= __("create_mailbox_btn") ?>');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('server_error')) ?>;
                $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-2"></i>' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                btn.prop('disabled', false).html('<i class="ri-mail-add-line me-1"></i> <?= __("create_mailbox_btn") ?>');
            }
        });
    });
    </script>
</body>
</html>
