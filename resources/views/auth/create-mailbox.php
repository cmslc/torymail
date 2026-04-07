<?php
$body = [
    'title' => __('temp_mail_title') . ' — ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('temp_mail_subtitle'),
];

$sharedDomains = $ToryMail->get_list_safe(
    "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC",
    []
);

$__siteName = get_setting('site_name', 'Torymail');
$siteLogo = get_setting('site_logo', '');
?>
<!doctype html>
<html lang="<?= current_lang(); ?>" data-layout="vertical" data-bs-theme="light" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-sidebar-visibility="show" data-layout-width="fluid" data-layout-position="fixed" data-layout-style="default" data-preloader="disable">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($body['title']); ?></title>
    <meta name="description" content="<?= htmlspecialchars($body['desc']); ?>">
    <meta name="csrf-token" content="<?= csrf_token(); ?>">
    <script src="<?= base_url('public/material/assets/js/layout.js'); ?>"></script>
    <link href="<?= base_url('public/material/assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/icons.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/custom.css'); ?>" rel="stylesheet">
    <script src="<?= base_url('public/js/jquery-3.6.0.js'); ?>"></script>
    <script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        beforeSend: function(xhr, s) {
            if (s.type === 'POST' && s.data) {
                var token = $('meta[name="csrf-token"]').attr('content');
                if (typeof s.data === 'string') s.data += '&_csrf_token=' + encodeURIComponent(token);
                else if (s.data instanceof FormData) s.data.append('_csrf_token', token);
            }
        }
    });
    </script>
    <style>
    [data-sidebar-size="sm"] .app-menu { width: 70px; }
    .app-menu .simplebar-content-wrapper { overflow: hidden; }
    .hero-title { font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; }
    .get-email-form { background: var(--vz-card-bg); border-radius: 50px; padding: 6px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid var(--vz-border-color); }
    .get-email-form input, .get-email-form select { border: none; background: transparent; font-size: 1rem; }
    .get-email-form input:focus, .get-email-form select:focus { box-shadow: none; outline: none; }
    .get-email-form .at-sign { color: var(--vz-secondary-color); font-weight: 600; font-size: 1.1rem; padding: 0 4px; }
    .btn-get-email { border-radius: 50px; padding: 10px 28px; font-weight: 600; }
    .inbox-wrapper { background: var(--vz-card-bg); border-radius: 8px; box-shadow: var(--vz-card-box-shadow); overflow: hidden; min-height: 450px; border: 1px solid var(--vz-border-color); }
    .email-list { border-right: 1px solid var(--vz-border-color); min-height: 450px; }
    .email-item { padding: 14px 18px; border-bottom: 1px solid var(--vz-border-color); cursor: pointer; transition: background 0.15s; }
    .email-item:hover { background: var(--vz-tertiary-bg); }
    .email-item.active { background: rgba(var(--vz-primary-rgb), 0.08); border-left: 3px solid var(--vz-primary); }
    .email-item.unread .email-from { font-weight: 600; }
    .email-item .email-from { font-size: 0.95rem; }
    .email-item .email-subject { font-size: 0.85rem; color: var(--vz-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .email-item .email-time { font-size: 0.75rem; color: var(--vz-secondary-color); }
    .email-content { padding: 24px; }
    .email-content .email-header { border-bottom: 1px solid var(--vz-border-color); padding-bottom: 16px; margin-bottom: 16px; }
    .inbox-header { padding: 16px 18px; border-bottom: 1px solid var(--vz-border-color); }
    .no-email-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; color: var(--vz-secondary-color); }
    .no-email-placeholder i { font-size: 4rem; margin-bottom: 12px; opacity: 0.4; }
    .email-address-display { background: rgba(var(--vz-primary-rgb), 0.1); border-radius: 8px; padding: 8px 16px; font-weight: 600; color: var(--vz-primary); }
    .refresh-spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    @media (max-width: 768px) {
        .hero-title { font-size: 1.5rem; }
        .get-email-form { border-radius: 16px; padding: 8px; flex-wrap: wrap; gap: 8px; }
        .btn-get-email { width: 100%; border-radius: 12px; }
        .email-list { min-height: auto; border-right: none; border-bottom: 1px solid var(--vz-border-color); }
    }
    </style>
</head>
<body>
<div id="layout-wrapper">

    <!-- ========== TOPBAR ========== -->
    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box horizontal-logo">
                        <a href="<?= base_url(); ?>" class="logo logo-dark">
                            <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22 text-primary"></i><?php endif; ?></span>
                            <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="28"><?php else: ?><i class="ri-mail-line me-1 text-primary"></i> <span class="fw-bold"><?= sanitize($__siteName); ?></span><?php endif; ?></span>
                        </a>
                        <a href="<?= base_url(); ?>" class="logo logo-light">
                            <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                            <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="28"><?php else: ?><i class="ri-mail-line me-1"></i> <span class="fw-bold"><?= sanitize($__siteName); ?></span><?php endif; ?></span>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                        <span class="hamburger-icon"><span></span><span></span><span></span></span>
                    </button>
                </div>
                <div class="d-flex align-items-center">
                    <!-- Language -->
                    <div class="dropdown ms-1 header-item">
                        <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown">
                            <span class="fs-14 fw-medium"><?= strtoupper(current_lang()); ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item <?= current_lang() === 'en' ? 'active' : ''; ?>" href="?lang=en">English</a>
                            <a class="dropdown-item <?= current_lang() === 'vi' ? 'active' : ''; ?>" href="?lang=vi">Tiếng Việt</a>
                        </div>
                    </div>
                    <!-- Fullscreen -->
                    <div class="ms-1 header-item d-none d-sm-flex">
                        <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen"><i class="ri-fullscreen-line fs-22"></i></button>
                    </div>
                    <!-- Dark/Light -->
                    <div class="ms-1 header-item d-none d-sm-flex">
                        <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode"><i class="ri-moon-line fs-22"></i></button>
                    </div>
                    <!-- Login -->
                    <div class="ms-2 header-item">
                        <a href="<?= base_url('auth/login'); ?>" class="btn btn-soft-primary btn-sm"><i class="ri-login-box-line me-1"></i> <?= __('login'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Two Column Menu -->
    <div id="two-column-menu"></div>

    <!-- ========== SIDEBAR ========== -->
    <div class="app-menu navbar-menu">
        <div class="navbar-brand-box">
            <a href="<?= base_url(); ?>" class="logo logo-dark">
                <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22 text-primary"></i><?php endif; ?></span>
                <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="28"><?php else: ?><i class="ri-mail-line me-1 text-primary fs-20"></i> <span class="fw-bold fs-16"><?= sanitize($__siteName); ?></span><?php endif; ?></span>
            </a>
            <a href="<?= base_url(); ?>" class="logo logo-light">
                <span class="logo-sm"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="22"><?php else: ?><i class="ri-mail-line fs-22"></i><?php endif; ?></span>
                <span class="logo-lg"><?php if ($siteLogo): ?><img src="<?= base_url($siteLogo); ?>" alt="" height="28"><?php else: ?><i class="ri-mail-line me-1 fs-20"></i> <span class="fw-bold fs-16"><?= sanitize($__siteName); ?></span><?php endif; ?></span>
            </a>
            <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover"><i class="ri-record-circle-line"></i></button>
        </div>

        <div id="scrollbar" data-simplebar>
            <div class="container-fluid">
                <ul class="navbar-nav" id="navbar-nav">

                    <li class="menu-title"><span><?= __('temp_mail_title'); ?></span></li>

                    <!-- Inbox -->
                    <li class="nav-item">
                        <a href="#" class="nav-link menu-link active" id="sidebar-inbox-link" onclick="return false;">
                            <i class="ri-inbox-line"></i>
                            <span><?= __('inbox'); ?></span>
                            <span class="badge badge-center rounded-pill bg-danger ms-auto d-none" id="sidebar-unread-count">0</span>
                        </a>
                    </li>

                    <li class="menu-title"><span><?= __('temp_mail_sidebar_info'); ?></span></li>

                    <!-- Current email display in sidebar -->
                    <li class="nav-item" id="sidebar-email-info" style="display:none;">
                        <div class="px-3 py-2">
                            <div class="d-flex align-items-center">
                                <i class="ri-mail-check-line text-success me-2 fs-16"></i>
                                <span class="text-truncate small" id="sidebar-email-text" style="color: var(--vz-sidebar-menu-item-color);"></span>
                            </div>
                            <button class="btn btn-sm btn-soft-light w-100 mt-2" onclick="copyEmail()">
                                <i class="ri-file-copy-line me-1"></i> <?= __('temp_mail_copy'); ?>
                            </button>
                        </div>
                    </li>

                    <li class="nav-item" id="sidebar-no-email" style="display:block;">
                        <div class="px-3 py-2">
                            <small style="color: var(--vz-sidebar-menu-item-color); opacity: 0.6;"><?= __('temp_mail_sidebar_hint'); ?></small>
                        </div>
                    </li>

                    <li class="menu-title"><span><?= __('temp_mail_sidebar_account'); ?></span></li>

                    <li class="nav-item">
                        <a href="<?= base_url('auth/login'); ?>" class="nav-link menu-link">
                            <i class="ri-login-box-line"></i>
                            <span><?= __('login'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('auth/register'); ?>" class="nav-link menu-link">
                            <i class="ri-user-add-line"></i>
                            <span><?= __('register'); ?></span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </div>
    <div class="vertical-overlay"></div>

    <!-- ========== MAIN CONTENT ========== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Hero -->
                <div class="text-center mb-4">
                    <h1 class="hero-title"><?= __('temp_mail_hero'); ?></h1>
                    <p class="text-muted fs-15"><?= __('temp_mail_subtitle'); ?></p>
                </div>

                <!-- Get Email Form -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8 col-xl-7">
                        <div id="alert-box"></div>
                        <?php if (empty($sharedDomains)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="ri-information-line me-1"></i> <?= __('create_mailbox_no_domains'); ?>
                            </div>
                        <?php else: ?>
                        <form id="getEmailForm" autocomplete="off">
                            <div class="get-email-form d-flex align-items-center">
                                <input type="text" name="local_part" id="local_part" class="form-control flex-grow-1 ps-4"
                                       placeholder="<?= __('temp_mail_placeholder'); ?>" required minlength="3"
                                       oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9._-]/g, '')">
                                <span class="at-sign">@</span>
                                <select name="domain_id" id="domain_id" class="form-select" style="max-width: 220px;" required>
                                    <?php foreach ($sharedDomains as $d): ?>
                                        <option value="<?= $d['id'] ?>" data-domain="<?= htmlspecialchars($d['domain_name']) ?>"><?= htmlspecialchars($d['domain_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-get-email ms-2" id="btnGetEmail">
                                    <?= __('temp_mail_get_btn'); ?>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Current email bar -->
                <div id="current-email-bar" class="text-center mb-3 d-none">
                    <span class="email-address-display">
                        <i class="ri-mail-line me-1"></i> <span id="current-email-text"></span>
                        <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyEmail()" title="<?= __('temp_mail_copy'); ?>"><i class="ri-file-copy-line"></i></button>
                    </span>
                </div>

                <!-- Inbox -->
                <div id="inbox-section" class="row justify-content-center d-none">
                    <div class="col-12">
                        <div class="inbox-wrapper">
                            <div class="row g-0">
                                <!-- Email List -->
                                <div class="col-md-5 col-lg-4">
                                    <div class="inbox-header d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0"><i class="ri-inbox-archive-line me-1"></i> <?= __('inbox'); ?></h5>
                                        <button class="btn btn-sm btn-light" onclick="refreshInbox()" id="btnRefresh" title="<?= __('refresh'); ?>">
                                            <i class="ri-refresh-line" id="refreshIcon"></i>
                                        </button>
                                    </div>
                                    <div id="email-list">
                                        <div class="no-email-placeholder" id="no-emails">
                                            <i class="ri-mail-open-line"></i>
                                            <span><?= __('temp_mail_no_email'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Email Content -->
                                <div class="col-md-7 col-lg-8">
                                    <div id="email-content">
                                        <div class="no-email-placeholder">
                                            <i class="ri-mail-unread-line"></i>
                                            <span><?= __('temp_mail_select_email'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><script>document.write(new Date().getFullYear())</script> &copy; <?= htmlspecialchars($__siteName); ?></div>
                    <div class="col-sm-6"><div class="text-sm-end d-none d-sm-block"><?= __('footer_system'); ?></div></div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Velzon Scripts -->
<script src="<?= base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/simplebar/simplebar.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/node-waves/waves.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/feather-icons/feather.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/plugins.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/app.js'); ?>"></script>

<script>
var BASE = <?= json_encode(base_url()) ?>;
var refreshTimer = null;
var currentMailboxId = null;
var currentEmailAddress = '';

// Init sidebar scrollbar
(function(){
    var el = document.getElementById('scrollbar');
    if (el && typeof SimpleBar !== 'undefined' && !el.SimpleBar) {
        el.classList.add('h-100');
        new SimpleBar(el);
    }
})();

// Get Email
$('#getEmailForm').submit(function(e) {
    e.preventDefault();
    var btn = $('#btnGetEmail');
    btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> ...');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=create',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                currentMailboxId = res.mailbox_id;
                currentEmailAddress = res.email_address;
                $('#current-email-text').text(res.email_address);
                $('#current-email-bar').removeClass('d-none');
                $('#inbox-section').removeClass('d-none');
                $('#alert-box').empty();
                // Update sidebar
                $('#sidebar-email-text').text(res.email_address);
                $('#sidebar-email-info').show();
                $('#sidebar-no-email').hide();
                refreshInbox();
                if (refreshTimer) clearInterval(refreshTimer);
                refreshTimer = setInterval(refreshInbox, 5000);
            } else {
                $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show text-center">' + res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            }
            btn.prop('disabled', false).html(<?= json_encode(__('temp_mail_get_btn')) ?>);
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('server_error')) ?>;
            $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show text-center">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            btn.prop('disabled', false).html(<?= json_encode(__('temp_mail_get_btn')) ?>);
        }
    });
});

function refreshInbox() {
    var icon = $('#refreshIcon');
    icon.addClass('refresh-spin');
    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=inbox',
        method: 'GET', dataType: 'json',
        success: function(res) {
            icon.removeClass('refresh-spin');
            if (res.status === 'success') {
                renderEmailList(res.emails);
                // Update sidebar badge
                var unread = (res.emails || []).filter(function(e){ return e.is_read == 0; }).length;
                if (unread > 0) {
                    $('#sidebar-unread-count').text(unread).removeClass('d-none');
                } else {
                    $('#sidebar-unread-count').addClass('d-none');
                }
            }
        },
        error: function() { icon.removeClass('refresh-spin'); }
    });
}

function renderEmailList(emails) {
    var list = $('#email-list');
    if (!emails || emails.length === 0) {
        list.html('<div class="no-email-placeholder"><i class="ri-mail-open-line"></i><span><?= __("temp_mail_no_email") ?></span></div>');
        return;
    }
    var html = '';
    emails.forEach(function(e) {
        var from = e.from_name || e.from_address || '<?= __("temp_mail_unknown") ?>';
        var subject = e.subject || '<?= __("no_subject") ?>';
        var time = formatTime(e.received_at || e.created_at);
        var unread = e.is_read == 0 ? 'unread' : '';
        var dot = e.is_read == 0 ? '<span class="badge bg-primary rounded-circle p-1 me-1">&nbsp;</span>' : '';
        html += '<div class="email-item ' + unread + '" onclick="readEmail(' + e.id + ', this)">' +
            '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="email-from text-truncate">' + dot + escapeHtml(from) + '</div>' +
                '<div class="email-time">' + time + '</div>' +
            '</div>' +
            '<div class="email-subject">' + escapeHtml(subject) + '</div>' +
            (e.has_attachments == 1 ? '<small class="text-muted"><i class="ri-attachment-2 me-1"></i><?= __("attachments") ?></small>' : '') +
        '</div>';
    });
    list.html(html);
}

function readEmail(id, el) {
    $('.email-item').removeClass('active');
    $(el).addClass('active').removeClass('unread');
    $(el).find('.badge').remove();
    $('#email-content').html('<div class="no-email-placeholder"><i class="ri-loader-4-line ri-spin" style="font-size:2rem"></i></div>');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=read&id=' + id,
        method: 'GET', dataType: 'json',
        success: function(res) {
            if (res.status === 'success') renderEmail(res.email);
            else $('#email-content').html('<div class="no-email-placeholder"><i class="ri-error-warning-line"></i><span>' + res.message + '</span></div>');
        },
        error: function() {
            $('#email-content').html('<div class="no-email-placeholder"><i class="ri-error-warning-line"></i><span><?= __("server_error") ?></span></div>');
        }
    });
}

function renderEmail(email) {
    var from = email.from_name ? escapeHtml(email.from_name) + ' &lt;' + escapeHtml(email.from_address) + '&gt;' : escapeHtml(email.from_address);
    var subject = email.subject || '<?= __("no_subject") ?>';
    var body = email.body_html || ('<pre style="white-space:pre-wrap;font-family:inherit">' + escapeHtml(email.body_text || '') + '</pre>');

    var attachHtml = '';
    if (email.attachments && email.attachments.length > 0) {
        attachHtml = '<div class="mt-3 pt-3 border-top"><strong><i class="ri-attachment-2 me-1"></i><?= __("attachments") ?></strong><div class="mt-2">';
        email.attachments.forEach(function(a) {
            attachHtml += '<a href="' + BASE + '/ajaxs/user/emails.php?action=download_attachment&id=' + a.id + '" class="btn btn-sm btn-outline-primary me-2 mb-2" target="_blank">' +
                '<i class="ri-download-line me-1"></i>' + escapeHtml(a.original_filename) + ' <small class="text-muted">(' + formatSize(a.size) + ')</small></a>';
        });
        attachHtml += '</div></div>';
    }

    $('#email-content').html(
        '<div class="email-content">' +
            '<div class="email-header">' +
                '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<h5 class="mb-0">' + escapeHtml(subject) + '</h5>' +
                    '<button class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" onclick="deleteEmail(' + email.id + ')" title="<?= __("delete") ?>"><i class="ri-delete-bin-line"></i></button>' +
                '</div>' +
                '<div class="text-muted small">' +
                    '<div><strong><?= __("from") ?>:</strong> ' + from + '</div>' +
                    '<div><strong><?= __("date") ?>:</strong> ' + escapeHtml(email.received_at || email.created_at) + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="email-body mt-3">' + body + '</div>' +
            attachHtml +
        '</div>'
    );
}

function deleteEmail(id) {
    if (!confirm(<?= json_encode(__('delete_email') . '?') ?>)) return;
    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=delete',
        method: 'POST', data: {email_id: id}, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#email-content').html('<div class="no-email-placeholder"><i class="ri-mail-unread-line"></i><span><?= __("temp_mail_select_email") ?></span></div>');
                refreshInbox();
            }
        }
    });
}

function copyEmail() {
    navigator.clipboard.writeText(currentEmailAddress).then(function() {
        var btn = $('#current-email-bar .btn-link i');
        btn.removeClass('ri-file-copy-line').addClass('ri-check-line text-success');
        setTimeout(function() { btn.removeClass('ri-check-line text-success').addClass('ri-file-copy-line'); }, 1500);
    });
}

function escapeHtml(t) { if(!t) return ''; var d=document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
function formatTime(dt) { if(!dt) return ''; var d=new Date(dt), n=new Date(); if(d.toDateString()===n.toDateString()) return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); return (d.getMonth()+1)+'/'+d.getDate()+' '+d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); }
function formatSize(b) { if(b>=1048576) return (b/1048576).toFixed(1)+' MB'; if(b>=1024) return (b/1024).toFixed(1)+' KB'; return b+' B'; }
</script>
</body>
</html>
