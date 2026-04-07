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

    /* Hero */
    .tm-hero { background: linear-gradient(135deg, var(--vz-primary) 0%, #7c3aed 100%); border-radius: 12px; padding: 32px; color: #fff; position: relative; overflow: hidden; }
    .tm-hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: rgba(255,255,255,0.08); border-radius: 50%; }
    .tm-hero::after { content: ''; position: absolute; bottom: -30%; left: -10%; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; }
    .tm-hero h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 6px; position: relative; z-index: 1; }
    .tm-hero p { opacity: 0.85; margin-bottom: 0; position: relative; z-index: 1; }

    /* Form */
    .tm-form-card { background: var(--vz-card-bg); border-radius: 12px; padding: 20px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); border: 1px solid var(--vz-border-color); margin-top: -24px; position: relative; z-index: 2; }
    .tm-input-group { display: flex; align-items: center; gap: 0; background: var(--vz-light); border-radius: 8px; border: 2px solid var(--vz-border-color); transition: border-color 0.2s; }
    .tm-input-group:focus-within { border-color: var(--vz-primary); }
    .tm-input-group input { border: none; background: transparent; padding: 10px 14px; font-size: 0.95rem; flex: 1; min-width: 0; }
    .tm-input-group input:focus { box-shadow: none; outline: none; }
    .tm-input-group .at-divider { color: var(--vz-secondary-color); font-weight: 700; padding: 0 2px; font-size: 1rem; }
    .tm-input-group select { border: none; background: transparent; padding: 10px 8px; font-size: 0.95rem; min-width: 140px; cursor: pointer; }
    .tm-input-group select:focus { box-shadow: none; outline: none; }
    .btn-get { padding: 10px 24px; font-weight: 600; border-radius: 8px; white-space: nowrap; }

    /* Email badge */
    .tm-email-badge { display: inline-flex; align-items: center; gap: 10px; background: var(--vz-card-bg); border: 2px solid var(--vz-success); border-radius: 8px; padding: 10px 18px; }
    .tm-email-badge .email-text { font-weight: 600; font-size: 1rem; color: var(--vz-success); }
    .tm-email-badge .btn-copy { border: none; background: none; color: var(--vz-secondary-color); cursor: pointer; padding: 2px 6px; border-radius: 4px; transition: all 0.15s; }
    .tm-email-badge .btn-copy:hover { background: var(--vz-light); color: var(--vz-primary); }

    /* Inbox */
    .tm-inbox { background: var(--vz-card-bg); border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); border: 1px solid var(--vz-border-color); overflow: hidden; }
    .tm-inbox-header { padding: 14px 20px; border-bottom: 1px solid var(--vz-border-color); display: flex; align-items: center; justify-content: space-between; }
    .tm-inbox-header h6 { margin: 0; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vz-secondary-color); }
    .tm-email-list { min-height: 380px; max-height: 500px; overflow-y: auto; }
    .tm-email-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--vz-border-color); cursor: pointer; transition: background 0.15s; }
    .tm-email-item:last-child { border-bottom: none; }
    .tm-email-item:hover { background: var(--vz-tertiary-bg); }
    .tm-email-item.active { background: rgba(var(--vz-primary-rgb), 0.06); }
    .tm-email-item.unread .ei-from { font-weight: 700; }
    .tm-email-item.unread .ei-subject { color: var(--vz-body-color); font-weight: 500; }
    .tm-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; flex-shrink: 0; }
    .ei-body { flex: 1; min-width: 0; }
    .ei-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .ei-from { font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ei-time { font-size: 0.75rem; color: var(--vz-secondary-color); white-space: nowrap; flex-shrink: 0; }
    .ei-subject { font-size: 0.82rem; color: var(--vz-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

    /* Reader */
    .tm-reader { min-height: 380px; }
    .tm-reader-header { padding: 20px 24px; border-bottom: 1px solid var(--vz-border-color); }
    .tm-reader-header h5 { font-size: 1.1rem; font-weight: 600; margin-bottom: 12px; }
    .tm-reader-meta { display: flex; align-items: center; gap: 12px; }
    .tm-reader-meta .meta-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #fff; flex-shrink: 0; }
    .tm-reader-meta .meta-info { font-size: 0.85rem; }
    .tm-reader-meta .meta-name { font-weight: 600; }
    .tm-reader-meta .meta-email { color: var(--vz-secondary-color); }
    .tm-reader-meta .meta-date { font-size: 0.78rem; color: var(--vz-secondary-color); }
    .tm-reader-body { padding: 24px; line-height: 1.7; }
    .tm-reader-body pre { white-space: pre-wrap; font-family: inherit; margin: 0; }
    .tm-reader-attachments { padding: 16px 24px; border-top: 1px solid var(--vz-border-color); }

    /* Placeholder */
    .tm-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 380px; color: var(--vz-secondary-color); text-align: center; padding: 24px; }
    .tm-placeholder .ph-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--vz-light); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .tm-placeholder .ph-icon i { font-size: 2rem; opacity: 0.5; }
    .tm-placeholder .ph-text { font-size: 0.9rem; }

    .refresh-spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    @media (max-width: 991px) {
        .tm-inbox-row .col-lg-4 { border-right: none !important; border-bottom: 1px solid var(--vz-border-color); }
        .tm-email-list { max-height: 260px; min-height: 200px; }
        .tm-reader { min-height: 300px; }
    }
    @media (max-width: 575px) {
        .tm-hero { padding: 20px; }
        .tm-hero h1 { font-size: 1.3rem; }
        .tm-form-row { flex-direction: column; }
        .btn-get { width: 100%; }
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
                    <div class="dropdown ms-1 header-item">
                        <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown"><span class="fs-14 fw-medium"><?= strtoupper(current_lang()); ?></span></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item <?= current_lang() === 'en' ? 'active' : ''; ?>" href="?lang=en">English</a>
                            <a class="dropdown-item <?= current_lang() === 'vi' ? 'active' : ''; ?>" href="?lang=vi">Tiếng Việt</a>
                        </div>
                    </div>
                    <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen"><i class="ri-fullscreen-line fs-22"></i></button></div>
                    <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode"><i class="ri-moon-line fs-22"></i></button></div>
                    <div class="ms-2 header-item"><a href="<?= base_url('auth/login'); ?>" class="btn btn-soft-primary btn-sm"><i class="ri-login-box-line me-1"></i> <?= __('login'); ?></a></div>
                </div>
            </div>
        </div>
    </header>

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
                    <li class="nav-item">
                        <a href="#" class="nav-link menu-link active" onclick="return false;">
                            <i class="ri-inbox-line"></i><span><?= __('inbox'); ?></span>
                            <span class="badge badge-center rounded-pill bg-danger ms-auto d-none" id="sidebar-unread-count">0</span>
                        </a>
                    </li>
                    <li class="menu-title"><span><?= __('temp_mail_sidebar_info'); ?></span></li>
                    <li class="nav-item" id="sidebar-email-info" style="display:none;">
                        <div class="px-3 py-2">
                            <div class="d-flex align-items-center"><i class="ri-mail-check-line text-success me-2 fs-16"></i><span class="text-truncate small fw-semibold" id="sidebar-email-text" style="color:#fff;"></span></div>
                            <button class="btn btn-sm btn-light w-100 mt-2" onclick="copyEmail()"><i class="ri-file-copy-line me-1"></i> <?= __('temp_mail_copy'); ?></button>
                        </div>
                    </li>
                    <li class="nav-item" id="sidebar-no-email"><div class="px-3 py-2"><small style="color:var(--vz-sidebar-menu-item-color);opacity:.6;"><?= __('temp_mail_sidebar_hint'); ?></small></div></li>
                    <li class="menu-title"><span><?= __('temp_mail_sidebar_account'); ?></span></li>
                    <li class="nav-item"><a href="<?= base_url('auth/login'); ?>" class="nav-link menu-link"><i class="ri-login-box-line"></i><span><?= __('login'); ?></span></a></li>
                    <li class="nav-item"><a href="<?= base_url('auth/register'); ?>" class="nav-link menu-link"><i class="ri-user-add-line"></i><span><?= __('register'); ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="vertical-overlay"></div>

    <!-- ========== MAIN CONTENT ========== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Hero + Form -->
                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <div class="tm-hero mb-2">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h1><i class="ri-mail-star-line me-2"></i><?= __('temp_mail_hero'); ?></h1>
                                    <p><?= __('temp_mail_subtitle'); ?></p>
                                </div>
                                <div class="col-md-5 text-end d-none d-md-block">
                                    <i class="ri-shield-check-line" style="font-size:4rem;opacity:0.15;"></i>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($sharedDomains)): ?>
                        <div class="tm-form-card mx-auto" style="max-width:680px;">
                            <div id="alert-box"></div>
                            <form id="getEmailForm" autocomplete="off">
                                <div class="d-flex gap-2 align-items-center tm-form-row">
                                    <div class="tm-input-group flex-grow-1">
                                        <input type="text" name="local_part" id="local_part"
                                               placeholder="<?= __('temp_mail_placeholder'); ?>" required minlength="3"
                                               oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9._-]/g, '')">
                                        <span class="at-divider">@</span>
                                        <select name="domain_id" id="domain_id" required>
                                            <?php foreach ($sharedDomains as $d): ?>
                                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-get" id="btnGetEmail">
                                        <i class="ri-arrow-right-line me-1"></i> <?= __('temp_mail_get_btn'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning text-center mt-3"><i class="ri-information-line me-1"></i> <?= __('create_mailbox_no_domains'); ?></div>
                        <?php endif; ?>

                        <!-- Email badge -->
                        <div id="current-email-bar" class="text-center mt-3 d-none">
                            <div class="tm-email-badge">
                                <i class="ri-mail-check-fill fs-18 text-success"></i>
                                <span class="email-text" id="current-email-text"></span>
                                <button class="btn-copy" onclick="copyEmail()" title="<?= __('temp_mail_copy'); ?>"><i class="ri-file-copy-line fs-16"></i></button>
                            </div>
                        </div>

                        <!-- Inbox -->
                        <div id="inbox-section" class="mt-4 d-none">
                            <div class="tm-inbox">
                                <div class="row g-0 tm-inbox-row">
                                    <!-- Email List -->
                                    <div class="col-lg-4" style="border-right: 1px solid var(--vz-border-color);">
                                        <div class="tm-inbox-header">
                                            <h6><i class="ri-inbox-line me-1"></i> <?= __('inbox'); ?> <span class="text-primary ms-1 d-none" id="email-count-label"></span></h6>
                                            <button class="btn btn-sm btn-ghost-secondary" onclick="refreshInbox()" title="<?= __('refresh'); ?>"><i class="ri-refresh-line" id="refreshIcon"></i></button>
                                        </div>
                                        <div class="tm-email-list" id="email-list">
                                            <div class="tm-placeholder">
                                                <div class="ph-icon"><i class="ri-mail-open-line"></i></div>
                                                <div class="ph-text"><?= __('temp_mail_no_email'); ?></div>
                                                <div class="text-muted small mt-2"><?= __('temp_mail_waiting'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Email Reader -->
                                    <div class="col-lg-8">
                                        <div class="tm-reader" id="email-content">
                                            <div class="tm-placeholder">
                                                <div class="ph-icon"><i class="ri-mail-unread-line"></i></div>
                                                <div class="ph-text"><?= __('temp_mail_select_email'); ?></div>
                                            </div>
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

<script src="<?= base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/simplebar/simplebar.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/node-waves/waves.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/feather-icons/feather.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/plugins.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/app.js'); ?>"></script>

<script>
var BASE = <?= json_encode(base_url()) ?>;
var refreshTimer = null, currentEmailAddress = '';
var avatarColors = ['#405189','#0ab39c','#f06548','#f7b84b','#299cdb','#6559cc','#e83e8c','#2b8a3e'];

(function(){
    var el = document.getElementById('scrollbar');
    if (el && typeof SimpleBar !== 'undefined' && !el.SimpleBar) { el.classList.add('h-100'); new SimpleBar(el); }
})();

function getColor(str) { var h=0; for(var i=0;i<(str||'').length;i++) h=str.charCodeAt(i)+((h<<5)-h); return avatarColors[Math.abs(h)%avatarColors.length]; }
function getInitial(str) { return (str||'?').charAt(0).toUpperCase(); }

$('#getEmailForm').submit(function(e) {
    e.preventDefault();
    var btn = $('#btnGetEmail');
    btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> ...');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=create',
        method: 'POST', data: $(this).serialize(), dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                currentEmailAddress = res.email_address;
                $('#current-email-text').text(res.email_address);
                $('#current-email-bar').removeClass('d-none');
                $('#inbox-section').removeClass('d-none');
                $('#alert-box').empty();
                $('#sidebar-email-text').text(res.email_address);
                $('#sidebar-email-info').show();
                $('#sidebar-no-email').hide();
                refreshInbox();
                if (refreshTimer) clearInterval(refreshTimer);
                refreshTimer = setInterval(refreshInbox, 5000);
            } else {
                $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show">' + res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            }
            btn.prop('disabled', false).html('<i class="ri-arrow-right-line me-1"></i> ' + <?= json_encode(__('temp_mail_get_btn')) ?>);
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('server_error')) ?>;
            $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            btn.prop('disabled', false).html('<i class="ri-arrow-right-line me-1"></i> ' + <?= json_encode(__('temp_mail_get_btn')) ?>);
        }
    });
});

function refreshInbox() {
    $('#refreshIcon').addClass('refresh-spin');
    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=inbox',
        method: 'GET', dataType: 'json',
        success: function(res) {
            $('#refreshIcon').removeClass('refresh-spin');
            if (res.status === 'success') {
                renderEmailList(res.emails);
                var unread = (res.emails || []).filter(function(e){ return e.is_read == 0; }).length;
                if (unread > 0) { $('#sidebar-unread-count').text(unread).removeClass('d-none'); }
                else { $('#sidebar-unread-count').addClass('d-none'); }
                var total = (res.emails || []).length;
                if (total > 0) { $('#email-count-label').text('(' + total + ')').removeClass('d-none'); }
                else { $('#email-count-label').addClass('d-none'); }
            }
        },
        error: function() { $('#refreshIcon').removeClass('refresh-spin'); }
    });
}

function renderEmailList(emails) {
    var list = $('#email-list');
    if (!emails || emails.length === 0) {
        list.html('<div class="tm-placeholder"><div class="ph-icon"><i class="ri-mail-open-line"></i></div><div class="ph-text"><?= __("temp_mail_no_email") ?></div><div class="text-muted small mt-2"><?= __("temp_mail_waiting") ?></div></div>');
        return;
    }
    var html = '';
    emails.forEach(function(e) {
        var from = e.from_name || e.from_address || '?';
        var subject = e.subject || '<?= __("no_subject") ?>';
        var time = formatTime(e.received_at || e.created_at);
        var unread = e.is_read == 0 ? ' unread' : '';
        var color = getColor(from);
        html += '<div class="tm-email-item' + unread + '" onclick="readEmail(' + e.id + ', this)">' +
            '<div class="tm-avatar" style="background:' + color + '">' + getInitial(from) + '</div>' +
            '<div class="ei-body">' +
                '<div class="ei-top"><span class="ei-from">' + escapeHtml(from) + '</span><span class="ei-time">' + time + '</span></div>' +
                '<div class="ei-subject">' + escapeHtml(subject) + '</div>' +
            '</div>' +
        '</div>';
    });
    list.html(html);
}

function readEmail(id, el) {
    $('.tm-email-item').removeClass('active');
    $(el).addClass('active').removeClass('unread');
    $('#email-content').html('<div class="tm-placeholder"><div class="ph-icon"><i class="ri-loader-4-line ri-spin"></i></div></div>');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=read&id=' + id,
        method: 'GET', dataType: 'json',
        success: function(res) {
            if (res.status === 'success') renderEmail(res.email);
            else $('#email-content').html('<div class="tm-placeholder"><div class="ph-icon"><i class="ri-error-warning-line"></i></div><div class="ph-text">' + res.message + '</div></div>');
        },
        error: function() { $('#email-content').html('<div class="tm-placeholder"><div class="ph-icon"><i class="ri-error-warning-line"></i></div><div class="ph-text"><?= __("server_error") ?></div></div>'); }
    });
}

function renderEmail(email) {
    var fromName = email.from_name || email.from_address || '?';
    var subject = email.subject || '<?= __("no_subject") ?>';
    var body = email.body_html || ('<pre>' + escapeHtml(email.body_text || '') + '</pre>');
    var color = getColor(fromName);
    var date = email.received_at || email.created_at;

    var attachHtml = '';
    if (email.attachments && email.attachments.length > 0) {
        attachHtml = '<div class="tm-reader-attachments"><div class="fw-semibold small mb-2"><i class="ri-attachment-2 me-1"></i><?= __("attachments") ?> (' + email.attachments.length + ')</div>';
        email.attachments.forEach(function(a) {
            attachHtml += '<a href="' + BASE + '/ajaxs/user/emails.php?action=download_attachment&id=' + a.id + '" class="btn btn-sm btn-outline-primary me-2 mb-2" target="_blank"><i class="ri-download-line me-1"></i>' + escapeHtml(a.original_filename) + ' <small>(' + formatSize(a.size) + ')</small></a>';
        });
        attachHtml += '</div>';
    }

    $('#email-content').html(
        '<div class="tm-reader">' +
            '<div class="tm-reader-header">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<h5 class="flex-grow-1">' + escapeHtml(subject) + '</h5>' +
                    '<button class="btn btn-sm btn-soft-danger ms-3 flex-shrink-0" onclick="deleteEmail(' + email.id + ')"><i class="ri-delete-bin-line"></i></button>' +
                '</div>' +
                '<div class="tm-reader-meta">' +
                    '<div class="meta-avatar" style="background:' + color + '">' + getInitial(fromName) + '</div>' +
                    '<div class="meta-info flex-grow-1">' +
                        '<div><span class="meta-name">' + escapeHtml(fromName) + '</span> <span class="meta-email">&lt;' + escapeHtml(email.from_address) + '&gt;</span></div>' +
                        '<div class="meta-date">' + escapeHtml(date) + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="tm-reader-body">' + body + '</div>' +
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
                $('#email-content').html('<div class="tm-placeholder"><div class="ph-icon"><i class="ri-mail-unread-line"></i></div><div class="ph-text"><?= __("temp_mail_select_email") ?></div></div>');
                refreshInbox();
            }
        }
    });
}

function copyEmail() {
    navigator.clipboard.writeText(currentEmailAddress).then(function() {
        var el = $('.tm-email-badge .btn-copy i');
        el.removeClass('ri-file-copy-line').addClass('ri-check-line text-success');
        setTimeout(function() { el.removeClass('ri-check-line text-success').addClass('ri-file-copy-line'); }, 1500);
    });
}

function escapeHtml(t) { if(!t) return ''; var d=document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
function formatTime(dt) { if(!dt) return ''; var d=new Date(dt), n=new Date(); if(d.toDateString()===n.toDateString()) return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); return (d.getMonth()+1)+'/'+d.getDate()+' '+d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); }
function formatSize(b) { if(b>=1048576) return (b/1048576).toFixed(1)+' MB'; if(b>=1024) return (b/1024).toFixed(1)+' KB'; return b+' B'; }
</script>
</body>
</html>
