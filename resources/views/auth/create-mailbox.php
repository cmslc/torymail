<?php
$body = [
    'title' => __('temp_mail_title') . ' — ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('temp_mail_subtitle'),
];
$sharedDomains = $ToryMail->get_list_safe(
    "SELECT id, domain_name FROM domains WHERE is_shared = 1 AND status = 'active' ORDER BY domain_name ASC", []
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

    /* ── Generate Section ── */
    .tm-generate { background: var(--vz-card-bg); border: 1px solid var(--vz-border-color); border-radius: 10px; padding: 24px 28px; }
    .tm-generate-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--vz-secondary-color); margin-bottom: 14px; }
    .tm-gen-row { display: flex; gap: 10px; align-items: stretch; }
    .tm-gen-input { flex: 1; display: flex; align-items: center; border: 2px solid var(--vz-border-color); border-radius: 8px; background: var(--vz-light); transition: border-color .2s, box-shadow .2s; overflow: hidden; }
    .tm-gen-input:focus-within { border-color: var(--vz-primary); box-shadow: 0 0 0 3px rgba(var(--vz-primary-rgb), .1); }
    .tm-gen-input input { border: none; background: none; padding: 11px 14px; font-size: .95rem; width: 100%; min-width: 0; }
    .tm-gen-input input:focus { outline: none; box-shadow: none; }
    .tm-gen-input .sep { color: var(--vz-secondary-color); font-weight: 700; padding: 0 1px; user-select: none; }
    .tm-gen-input select { border: none; background: none; padding: 11px 10px 11px 4px; font-size: .95rem; cursor: pointer; color: var(--vz-body-color); }
    .tm-gen-input select:focus { outline: none; box-shadow: none; }
    .btn-gen { padding: 0 28px; font-weight: 600; border-radius: 8px; white-space: nowrap; font-size: .95rem; }
    @media(max-width:575px) { .tm-gen-row { flex-direction: column; } .btn-gen { padding: 11px 20px; } }

    /* ── Active Email Card ── */
    .tm-active-email { background: linear-gradient(135deg, rgba(var(--vz-success-rgb),.08) 0%, rgba(var(--vz-primary-rgb),.06) 100%); border: 1px solid rgba(var(--vz-success-rgb),.25); border-radius: 10px; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
    .tm-active-email .ae-icon { width: 44px; height: 44px; border-radius: 10px; background: var(--vz-success); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .tm-active-email .ae-info { flex: 1; min-width: 0; }
    .tm-active-email .ae-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--vz-secondary-color); }
    .tm-active-email .ae-addr { font-size: 1.05rem; font-weight: 700; color: var(--vz-body-color); word-break: break-all; }
    .tm-active-email .ae-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .tm-active-email .ae-actions .btn { width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
    @media(max-width:575px) { .tm-active-email { flex-wrap: wrap; } .tm-active-email .ae-actions { width: 100%; justify-content: flex-end; } }

    /* ── Inbox ── */
    .tm-inbox { background: var(--vz-card-bg); border: 1px solid var(--vz-border-color); border-radius: 10px; overflow: hidden; }
    .tm-inbox-head { padding: 12px 20px; border-bottom: 1px solid var(--vz-border-color); display: flex; align-items: center; justify-content: space-between; }
    .tm-inbox-head .ih-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--vz-secondary-color); display: flex; align-items: center; gap: 8px; }
    .tm-inbox-head .ih-title .count { background: var(--vz-primary); color: #fff; font-size: .7rem; border-radius: 10px; padding: 1px 8px; display: none; }
    .tm-inbox-head .ih-actions { display: flex; gap: 4px; }
    .tm-email-list { min-height: 350px; max-height: 520px; overflow-y: auto; }
    .tm-email-item { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--vz-border-color); cursor: pointer; transition: background .12s; position: relative; }
    .tm-email-item:last-child { border-bottom: none; }
    .tm-email-item:hover { background: var(--vz-tertiary-bg); }
    .tm-email-item.active { background: rgba(var(--vz-primary-rgb),.06); }
    .tm-email-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--vz-primary); border-radius: 0 3px 3px 0; }
    .tm-email-item.unread .ei-from { font-weight: 700; color: var(--vz-body-color); }
    .tm-email-item.unread .ei-subject { color: var(--vz-body-color); }
    .tm-email-item.unread::after { content: ''; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 8px; height: 8px; border-radius: 50%; background: var(--vz-primary); }
    .ei-avatar { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .82rem; color: #fff; flex-shrink: 0; }
    .ei-content { flex: 1; min-width: 0; }
    .ei-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .ei-from { font-size: .88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--vz-body-color); }
    .ei-time { font-size: .72rem; color: var(--vz-secondary-color); flex-shrink: 0; }
    .ei-subject { font-size: .8rem; color: var(--vz-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }

    /* ── Reader ── */
    .tm-reader { min-height: 350px; }
    .tm-reader-head { padding: 20px 24px 16px; border-bottom: 1px solid var(--vz-border-color); }
    .tm-reader-head h5 { font-size: 1.05rem; font-weight: 700; margin-bottom: 14px; line-height: 1.4; }
    .tm-reader-sender { display: flex; align-items: center; gap: 12px; }
    .tm-reader-sender .rs-avatar { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; color: #fff; flex-shrink: 0; }
    .tm-reader-sender .rs-info { flex: 1; min-width: 0; }
    .tm-reader-sender .rs-name { font-weight: 600; font-size: .9rem; }
    .tm-reader-sender .rs-email { font-size: .8rem; color: var(--vz-secondary-color); }
    .tm-reader-sender .rs-date { font-size: .75rem; color: var(--vz-secondary-color); flex-shrink: 0; text-align: right; }
    .tm-reader-body { padding: 20px 24px; line-height: 1.75; font-size: .95rem; }
    .tm-reader-body pre { white-space: pre-wrap; font-family: inherit; margin: 0; }
    .tm-reader-attach { padding: 14px 24px; border-top: 1px solid var(--vz-border-color); background: var(--vz-light); }

    /* ── Placeholder ── */
    .tm-ph { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 350px; text-align: center; padding: 40px 20px; }
    .tm-ph-icon { width: 72px; height: 72px; border-radius: 50%; background: var(--vz-light); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .tm-ph-icon i { font-size: 1.8rem; color: var(--vz-secondary-color); opacity: .5; }
    .tm-ph-title { font-weight: 600; font-size: .95rem; color: var(--vz-body-color); margin-bottom: 4px; }
    .tm-ph-sub { font-size: .82rem; color: var(--vz-secondary-color); }
    .tm-ph-dot { display: flex; gap: 6px; margin-top: 16px; }
    .tm-ph-dot span { width: 6px; height: 6px; border-radius: 50%; background: var(--vz-secondary-color); opacity: .3; animation: phPulse 1.4s infinite ease-in-out; }
    .tm-ph-dot span:nth-child(2) { animation-delay: .2s; }
    .tm-ph-dot span:nth-child(3) { animation-delay: .4s; }
    @keyframes phPulse { 0%,80%,100% { opacity:.3; transform:scale(1); } 40% { opacity:1; transform:scale(1.3); } }

    .refresh-spin { animation: spin .8s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    .tm-divider { border-right: 1px solid var(--vz-border-color); }
    @media(max-width:991px) { .tm-divider { border-right: none; border-bottom: 1px solid var(--vz-border-color); } .tm-email-list { max-height: 280px; min-height: 180px; } .tm-reader { min-height: 280px; } }
    </style>
</head>
<body>
<div id="layout-wrapper">

    <!-- TOPBAR -->
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
                    <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon"><span class="hamburger-icon"><span></span><span></span><span></span></span></button>
                </div>
                <div class="d-flex align-items-center">
                    <div class="dropdown ms-1 header-item"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown"><span class="fs-14 fw-medium"><?= strtoupper(current_lang()); ?></span></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item <?= current_lang()==='en'?'active':''; ?>" href="?lang=en">English</a><a class="dropdown-item <?= current_lang()==='vi'?'active':''; ?>" href="?lang=vi">Tiếng Việt</a></div></div>
                    <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen"><i class="ri-fullscreen-line fs-22"></i></button></div>
                    <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode"><i class="ri-moon-line fs-22"></i></button></div>
                    <div class="ms-2 header-item"><a href="<?= base_url('auth/login'); ?>" class="btn btn-soft-primary btn-sm"><i class="ri-login-box-line me-1"></i><?= __('login'); ?></a></div>
                </div>
            </div>
        </div>
    </header>

    <div id="two-column-menu"></div>

    <!-- SIDEBAR -->
    <div class="app-menu navbar-menu">
        <div class="navbar-brand-box">
            <a href="<?= base_url(); ?>" class="logo logo-dark"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22 text-primary"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1 text-primary fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
            <a href="<?= base_url(); ?>" class="logo logo-light"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1 fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
            <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover"><i class="ri-record-circle-line"></i></button>
        </div>
        <div id="scrollbar" data-simplebar>
            <div class="container-fluid">
                <ul class="navbar-nav" id="navbar-nav">
                    <li class="menu-title"><span><?= __('temp_mail_title'); ?></span></li>
                    <li class="nav-item"><a href="#" class="nav-link menu-link active" onclick="return false;"><i class="ri-inbox-line"></i><span><?= __('inbox'); ?></span><span class="badge badge-center rounded-pill bg-danger ms-auto d-none" id="sidebar-unread">0</span></a></li>
                    <li class="menu-title"><span><?= __('temp_mail_sidebar_info'); ?></span></li>
                    <li class="nav-item" id="sb-email-on" style="display:none;"><div class="px-3 py-2"><div class="d-flex align-items-center"><i class="ri-mail-check-line text-success me-2 fs-16"></i><span class="text-truncate small fw-semibold" id="sb-email-text" style="color:#fff;"></span></div><button class="btn btn-sm btn-light w-100 mt-2" onclick="copyEmail()"><i class="ri-file-copy-line me-1"></i><?= __('temp_mail_copy'); ?></button></div></li>
                    <li class="nav-item" id="sb-email-off"><div class="px-3 py-2"><small style="color:var(--vz-sidebar-menu-item-color);opacity:.6;"><?= __('temp_mail_sidebar_hint'); ?></small></div></li>
                    <li class="menu-title"><span><?= __('temp_mail_sidebar_account'); ?></span></li>
                    <li class="nav-item"><a href="<?= base_url('auth/login'); ?>" class="nav-link menu-link"><i class="ri-login-box-line"></i><span><?= __('login'); ?></span></a></li>
                    <li class="nav-item"><a href="<?= base_url('auth/register'); ?>" class="nav-link menu-link"><i class="ri-user-add-line"></i><span><?= __('register'); ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="vertical-overlay"></div>

    <!-- MAIN -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Step 1: Generate -->
                <div class="tm-generate mb-3" id="gen-section">
                    <div class="tm-generate-label"><i class="ri-add-circle-line me-1"></i> <?= __('temp_mail_gen_label'); ?></div>
                    <?php if (!empty($sharedDomains)): ?>
                    <form id="getEmailForm" autocomplete="off">
                        <div id="alert-box"></div>
                        <div class="tm-gen-row">
                            <div class="tm-gen-input">
                                <input type="text" name="local_part" id="local_part" placeholder="<?= __('temp_mail_placeholder'); ?>" required minlength="3" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9._-]/g,'')">
                                <span class="sep">@</span>
                                <select name="domain_id" id="domain_id" required>
                                    <?php foreach ($sharedDomains as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-gen" id="btnGet"><i class="ri-arrow-right-line me-1"></i><?= __('temp_mail_get_btn'); ?></button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-warning mb-0"><i class="ri-information-line me-1"></i><?= __('create_mailbox_no_domains'); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Active Email -->
                <div class="tm-active-email mb-3 d-none" id="active-section">
                    <div class="ae-icon"><i class="ri-mail-check-fill"></i></div>
                    <div class="ae-info">
                        <div class="ae-label"><?= __('temp_mail_your_address'); ?></div>
                        <div class="ae-addr" id="ae-email"></div>
                    </div>
                    <div class="ae-actions">
                        <button class="btn btn-soft-primary" onclick="copyEmail()" title="<?= __('temp_mail_copy'); ?>" id="btnCopy"><i class="ri-file-copy-line"></i></button>
                        <button class="btn btn-soft-secondary" onclick="refreshInbox()" title="<?= __('refresh'); ?>" id="btnRefresh2"><i class="ri-refresh-line" id="refreshIcon2"></i></button>
                    </div>
                </div>

                <!-- Step 3: Inbox -->
                <div class="tm-inbox d-none" id="inbox-section">
                    <div class="row g-0">
                        <div class="col-lg-4 tm-divider">
                            <div class="tm-inbox-head">
                                <div class="ih-title"><i class="ri-inbox-line"></i> <?= __('inbox'); ?> <span class="count" id="ih-count">0</span></div>
                                <div class="ih-actions">
                                    <button class="btn btn-sm btn-ghost-secondary" onclick="refreshInbox()" title="<?= __('refresh'); ?>"><i class="ri-refresh-line" id="refreshIcon"></i></button>
                                </div>
                            </div>
                            <div class="tm-email-list" id="email-list">
                                <div class="tm-ph">
                                    <div class="tm-ph-icon"><i class="ri-mail-open-line"></i></div>
                                    <div class="tm-ph-title"><?= __('temp_mail_no_email'); ?></div>
                                    <div class="tm-ph-sub"><?= __('temp_mail_waiting'); ?></div>
                                    <div class="tm-ph-dot"><span></span><span></span><span></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="tm-reader" id="email-content">
                                <div class="tm-ph">
                                    <div class="tm-ph-icon"><i class="ri-mail-unread-line"></i></div>
                                    <div class="tm-ph-title"><?= __('temp_mail_select_email'); ?></div>
                                    <div class="tm-ph-sub"><?= __('temp_mail_click_to_read'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <footer class="footer"><div class="container-fluid"><div class="row"><div class="col-sm-6"><script>document.write(new Date().getFullYear())</script> &copy; <?= htmlspecialchars($__siteName); ?></div><div class="col-sm-6"><div class="text-sm-end d-none d-sm-block"><?= __('footer_system'); ?></div></div></div></div></footer>
    </div>
</div>

<script src="<?= base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/simplebar/simplebar.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/node-waves/waves.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/libs/feather-icons/feather.min.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/plugins.js'); ?>"></script>
<script src="<?= base_url('public/material/assets/js/app.js'); ?>"></script>
<script>
var BASE=<?=json_encode(base_url())?>, refreshTimer=null, currentEmail='';
var colors=['#405189','#0ab39c','#f06548','#f7b84b','#299cdb','#6559cc','#e83e8c','#2b8a3e','#1098ad','#d6336c'];
function gc(s){var h=0;for(var i=0;i<(s||'').length;i++)h=s.charCodeAt(i)+((h<<5)-h);return colors[Math.abs(h)%colors.length];}
function gi(s){return(s||'?').charAt(0).toUpperCase();}

(function(){var el=document.getElementById('scrollbar');if(el&&typeof SimpleBar!=='undefined'&&!el.SimpleBar){el.classList.add('h-100');new SimpleBar(el);}})();

$('#getEmailForm').submit(function(e){
    e.preventDefault();
    var btn=$('#btnGet');
    btn.prop('disabled',true).html('<i class="ri-loader-4-line ri-spin me-1"></i>...');
    $.ajax({
        url:BASE+'/ajaxs/public/mailboxes.php?action=create',method:'POST',data:$(this).serialize(),dataType:'json',
        success:function(r){
            if(r.status==='success'){
                currentEmail=r.email_address;
                $('#ae-email').text(r.email_address);
                $('#active-section').removeClass('d-none');
                $('#inbox-section').removeClass('d-none');
                $('#alert-box').empty();
                $('#sb-email-text').text(r.email_address);$('#sb-email-on').show();$('#sb-email-off').hide();
                refreshInbox();
                if(refreshTimer)clearInterval(refreshTimer);
                refreshTimer=setInterval(refreshInbox,5000);
            } else {
                $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show mb-3">'+r.message+'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            }
            btn.prop('disabled',false).html('<i class="ri-arrow-right-line me-1"></i>'+<?=json_encode(__('temp_mail_get_btn'))?>);
        },
        error:function(x){
            var m=(x.responseJSON&&x.responseJSON.message)?x.responseJSON.message:<?=json_encode(__('server_error'))?>;
            $('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show mb-3">'+m+'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            btn.prop('disabled',false).html('<i class="ri-arrow-right-line me-1"></i>'+<?=json_encode(__('temp_mail_get_btn'))?>);
        }
    });
});

function refreshInbox(){
    $('#refreshIcon,#refreshIcon2').addClass('refresh-spin');
    $.ajax({
        url:BASE+'/ajaxs/public/mailboxes.php?action=inbox',method:'GET',dataType:'json',
        success:function(r){
            $('#refreshIcon,#refreshIcon2').removeClass('refresh-spin');
            if(r.status==='success'){
                renderList(r.emails);
                var u=(r.emails||[]).filter(function(e){return e.is_read==0;}).length;
                var t=(r.emails||[]).length;
                if(u>0){$('#sidebar-unread').text(u).removeClass('d-none');}else{$('#sidebar-unread').addClass('d-none');}
                if(t>0){$('#ih-count').text(t).show();}else{$('#ih-count').hide();}
            }
        },
        error:function(){$('#refreshIcon,#refreshIcon2').removeClass('refresh-spin');}
    });
}

function renderList(emails){
    var el=$('#email-list');
    if(!emails||!emails.length){
        el.html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-mail-open-line"></i></div><div class="tm-ph-title"><?=__("temp_mail_no_email")?></div><div class="tm-ph-sub"><?=__("temp_mail_waiting")?></div><div class="tm-ph-dot"><span></span><span></span><span></span></div></div>');
        return;
    }
    var h='';
    emails.forEach(function(e){
        var f=e.from_name||e.from_address||'?',s=e.subject||'<?=__("no_subject")?>',t=fmtTime(e.received_at||e.created_at);
        var u=e.is_read==0?' unread':'',c=gc(f);
        h+='<div class="tm-email-item'+u+'" onclick="readEmail('+e.id+',this)">'+
            '<div class="ei-avatar" style="background:'+c+'">'+gi(f)+'</div>'+
            '<div class="ei-content">'+
                '<div class="ei-top"><span class="ei-from">'+esc(f)+'</span><span class="ei-time">'+t+'</span></div>'+
                '<div class="ei-subject">'+esc(s)+'</div>'+
            '</div></div>';
    });
    el.html(h);
}

function readEmail(id,el){
    $('.tm-email-item').removeClass('active');
    $(el).addClass('active').removeClass('unread');
    $('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-loader-4-line ri-spin"></i></div></div>');
    $.ajax({
        url:BASE+'/ajaxs/public/mailboxes.php?action=read&id='+id,method:'GET',dataType:'json',
        success:function(r){
            if(r.status==='success')showEmail(r.email);
            else $('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-error-warning-line"></i></div><div class="tm-ph-title">'+r.message+'</div></div>');
        },
        error:function(){$('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-error-warning-line"></i></div><div class="tm-ph-title"><?=__("server_error")?></div></div>');}
    });
}

function showEmail(e){
    var fn=e.from_name||e.from_address||'?',sub=e.subject||'<?=__("no_subject")?>',c=gc(fn);
    var body=e.body_html||('<pre>'+esc(e.body_text||'')+'</pre>');
    var att='';
    if(e.attachments&&e.attachments.length){
        att='<div class="tm-reader-attach"><div class="fw-semibold small mb-2"><i class="ri-attachment-2 me-1"></i><?=__("attachments")?> ('+e.attachments.length+')</div>';
        e.attachments.forEach(function(a){att+='<a href="'+BASE+'/ajaxs/user/emails.php?action=download_attachment&id='+a.id+'" class="btn btn-sm btn-outline-secondary me-2 mb-1" target="_blank"><i class="ri-download-2-line me-1"></i>'+esc(a.original_filename)+' <small class="text-muted">('+fmtSize(a.size)+')</small></a>';});
        att+='</div>';
    }
    $('#email-content').html(
        '<div class="tm-reader">'+
            '<div class="tm-reader-head">'+
                '<div class="d-flex justify-content-between align-items-start gap-3">'+
                    '<h5>'+esc(sub)+'</h5>'+
                    '<button class="btn btn-sm btn-soft-danger flex-shrink-0" onclick="delEmail('+e.id+')"><i class="ri-delete-bin-5-line"></i></button>'+
                '</div>'+
                '<div class="tm-reader-sender">'+
                    '<div class="rs-avatar" style="background:'+c+'">'+gi(fn)+'</div>'+
                    '<div class="rs-info">'+
                        '<div class="rs-name">'+esc(fn)+'</div>'+
                        '<div class="rs-email">'+esc(e.from_address)+'</div>'+
                    '</div>'+
                    '<div class="rs-date">'+esc(e.received_at||e.created_at)+'</div>'+
                '</div>'+
            '</div>'+
            '<div class="tm-reader-body">'+body+'</div>'+
            att+
        '</div>');
}

function delEmail(id){
    if(!confirm(<?=json_encode(__('delete_email').'?')?>))return;
    $.ajax({url:BASE+'/ajaxs/public/mailboxes.php?action=delete',method:'POST',data:{email_id:id},dataType:'json',
        success:function(r){if(r.status==='success'){$('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-mail-unread-line"></i></div><div class="tm-ph-title"><?=__("temp_mail_select_email")?></div><div class="tm-ph-sub"><?=__("temp_mail_click_to_read")?></div></div>');refreshInbox();}}});
}

function copyEmail(){
    navigator.clipboard.writeText(currentEmail).then(function(){
        var b=$('#btnCopy i');b.removeClass('ri-file-copy-line').addClass('ri-check-line');
        setTimeout(function(){b.removeClass('ri-check-line').addClass('ri-file-copy-line');},1500);
    });
}

function esc(t){if(!t)return'';var d=document.createElement('div');d.appendChild(document.createTextNode(t));return d.innerHTML;}
function fmtTime(dt){if(!dt)return'';var d=new Date(dt),n=new Date();if(d.toDateString()===n.toDateString())return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0');return(d.getMonth()+1)+'/'+d.getDate()+' '+d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0');}
function fmtSize(b){if(b>=1048576)return(b/1048576).toFixed(1)+' MB';if(b>=1024)return(b/1024).toFixed(1)+' KB';return b+' B';}
</script>
</body>
</html>
