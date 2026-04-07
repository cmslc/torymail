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
        headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
        beforeSend:function(x,s){if(s.type==='POST'&&s.data){var t=$('meta[name="csrf-token"]').attr('content');if(typeof s.data==='string')s.data+='&_csrf_token='+encodeURIComponent(t);else if(s.data instanceof FormData)s.data.append('_csrf_token',t);}}
    });
    </script>
    <style>
    [data-sidebar-size="sm"] .app-menu{width:70px}
    .app-menu .simplebar-content-wrapper{overflow:hidden}

    /* Cards */
    .tm-card{background:var(--vz-card-bg);border:1px solid var(--vz-border-color);border-radius:12px;overflow:hidden}

    /* Left Panel */
    .tm-left{position:sticky;top:90px}
    .tm-section-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--vz-secondary-color);margin-bottom:12px;display:flex;align-items:center;gap:6px}
    .tm-gen-input{display:flex;align-items:center;border:2px solid var(--vz-border-color);border-radius:8px;background:var(--vz-light);transition:border-color .2s,box-shadow .2s}
    .tm-gen-input:focus-within{border-color:var(--vz-primary);box-shadow:0 0 0 3px rgba(var(--vz-primary-rgb),.1)}
    .tm-gen-input input{border:none;background:none;padding:10px 12px;font-size:.9rem;flex:1;min-width:0}
    .tm-gen-input input:focus{outline:none;box-shadow:none}
    .tm-gen-input .sep{color:var(--vz-secondary-color);font-weight:700;font-size:.9rem;user-select:none}
    .tm-gen-input select{border:none;border-left:1px solid var(--vz-border-color);background:none;padding:10px 8px;font-size:.9rem;cursor:pointer}
    .tm-gen-input select:focus{outline:none;box-shadow:none}
    .btn-gen{width:100%;padding:10px;font-weight:600;border-radius:8px;font-size:.9rem;margin-top:10px}

    /* Email display */
    .tm-email-display{padding:16px;border-radius:10px;background:rgba(var(--vz-success-rgb),.06);border:1px solid rgba(var(--vz-success-rgb),.2)}
    .tm-email-display .ed-label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--vz-success);margin-bottom:6px}
    .tm-email-display .ed-addr{font-size:.92rem;font-weight:700;color:var(--vz-body-color);word-break:break-all;margin-bottom:10px}
    .tm-email-display .ed-actions{display:flex;gap:6px}
    .tm-email-display .ed-actions .btn{flex:1;font-size:.78rem;padding:6px 10px;font-weight:600}

    /* Stats */
    .tm-stats{display:flex;gap:8px;margin-top:12px}
    .tm-stat{flex:1;text-align:center;padding:10px 8px;border-radius:8px;background:var(--vz-light)}
    .tm-stat .s-val{font-size:1.1rem;font-weight:700;color:var(--vz-body-color);line-height:1}
    .tm-stat .s-label{font-size:.65rem;color:var(--vz-secondary-color);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}

    /* Inbox Header */
    .tm-inbox-head{padding:16px 20px;border-bottom:1px solid var(--vz-border-color);display:flex;align-items:center;justify-content:space-between}
    .tm-inbox-head .ih-left{display:flex;align-items:center;gap:10px}
    .tm-inbox-head .ih-title{font-size:1rem;font-weight:700;margin:0}
    .tm-inbox-head .ih-badge{font-size:.7rem;background:var(--vz-primary);color:#fff;border-radius:10px;padding:1px 8px;font-weight:600}
    .tm-inbox-head .ih-desc{font-size:.8rem;color:var(--vz-secondary-color);margin:0}

    /* Split View */
    .tm-split{display:flex;min-height:480px}
    .tm-split-list{width:100%;border-right:1px solid var(--vz-border-color);overflow-y:auto;max-height:520px}
    .tm-split-reader{flex:1;min-width:0;overflow-y:auto;max-height:520px}
    @media(min-width:768px){.tm-split-list{width:320px;min-width:320px}}
    @media(max-width:767px){.tm-split{flex-direction:column}.tm-split-list{width:100%;max-height:280px;border-right:none;border-bottom:1px solid var(--vz-border-color)}}

    /* Email items */
    .tm-ei{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--vz-border-color);cursor:pointer;transition:background .12s;position:relative}
    .tm-ei:last-child{border-bottom:none}
    .tm-ei:hover{background:var(--vz-tertiary-bg)}
    .tm-ei.active{background:rgba(var(--vz-primary-rgb),.05)}
    .tm-ei.active::after{content:'';position:absolute;left:0;top:8px;bottom:8px;width:3px;background:var(--vz-primary);border-radius:0 4px 4px 0}
    .tm-ei.unread .ei-f{font-weight:700}
    .tm-ei.unread .ei-s{color:var(--vz-body-color)}
    .tm-ei .ei-dot{width:7px;height:7px;border-radius:50%;background:var(--vz-primary);flex-shrink:0;display:none}
    .tm-ei.unread .ei-dot{display:block}
    .ei-av{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:#fff;flex-shrink:0}
    .ei-c{flex:1;min-width:0}
    .ei-r1{display:flex;justify-content:space-between;align-items:baseline;gap:6px}
    .ei-f{font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ei-t{font-size:.7rem;color:var(--vz-secondary-color);flex-shrink:0}
    .ei-s{font-size:.78rem;color:var(--vz-secondary-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}

    /* Reader */
    .tm-rd-head{padding:20px;border-bottom:1px solid var(--vz-border-color)}
    .tm-rd-head h5{font-size:1rem;font-weight:700;margin-bottom:14px;line-height:1.4}
    .tm-rd-sender{display:flex;align-items:center;gap:10px}
    .tm-rd-sender .rd-av{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:#fff;flex-shrink:0}
    .tm-rd-sender .rd-info{flex:1;min-width:0}
    .tm-rd-sender .rd-name{font-weight:600;font-size:.85rem}
    .tm-rd-sender .rd-email{font-size:.78rem;color:var(--vz-secondary-color)}
    .tm-rd-sender .rd-date{font-size:.72rem;color:var(--vz-secondary-color);text-align:right;flex-shrink:0}
    .tm-rd-body{padding:20px;line-height:1.7;font-size:.92rem}
    .tm-rd-body pre{white-space:pre-wrap;font-family:inherit;margin:0}
    .tm-rd-attach{padding:12px 20px;border-top:1px solid var(--vz-border-color);background:var(--vz-light)}

    /* Placeholder */
    .tm-ph{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:300px;text-align:center;padding:40px 20px}
    .tm-ph-icon{width:64px;height:64px;border-radius:50%;background:var(--vz-light);display:flex;align-items:center;justify-content:center;margin-bottom:14px}
    .tm-ph-icon i{font-size:1.5rem;color:var(--vz-secondary-color);opacity:.4}
    .tm-ph-t{font-weight:600;font-size:.88rem;margin-bottom:4px}
    .tm-ph-s{font-size:.78rem;color:var(--vz-secondary-color)}
    .tm-ph-dots{display:flex;gap:5px;margin-top:14px}
    .tm-ph-dots span{width:5px;height:5px;border-radius:50%;background:var(--vz-secondary-color);opacity:.3;animation:dotPulse 1.4s infinite ease-in-out}
    .tm-ph-dots span:nth-child(2){animation-delay:.2s}
    .tm-ph-dots span:nth-child(3){animation-delay:.4s}
    @keyframes dotPulse{0%,80%,100%{opacity:.3;transform:scale(1)}40%{opacity:1;transform:scale(1.4)}}
    /* Features */
    .tm-feat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}

    .refresh-spin{animation:spin .8s linear infinite}
    @keyframes spin{100%{transform:rotate(360deg)}}

    @media(max-width:991px){.tm-left{position:static}}
    </style>
</head>
<body>
<div id="layout-wrapper">

    <!-- TOPBAR -->
    <header id="page-topbar"><div class="layout-width"><div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box horizontal-logo">
                <a href="<?=base_url();?>" class="logo logo-dark"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22 text-primary"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1 text-primary"></i> <span class="fw-bold"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
                <a href="<?=base_url();?>" class="logo logo-light"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1"></i> <span class="fw-bold"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
            </div>
            <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon"><span class="hamburger-icon"><span></span><span></span><span></span></span></button>
        </div>
        <div class="d-flex align-items-center">
            <div class="dropdown ms-1 header-item"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown"><span class="fs-14 fw-medium"><?=strtoupper(current_lang());?></span></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item <?=current_lang()==='en'?'active':'';?>" href="?lang=en">English</a><a class="dropdown-item <?=current_lang()==='vi'?'active':'';?>" href="?lang=vi">Tiếng Việt</a></div></div>
            <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="fullscreen"><i class="ri-fullscreen-line fs-22"></i></button></div>
            <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode"><i class="ri-moon-line fs-22"></i></button></div>
            <div class="ms-2 header-item"><a href="<?=base_url('auth/login');?>" class="btn btn-soft-primary btn-sm"><i class="ri-login-box-line me-1"></i><?=__('login');?></a></div>
        </div>
    </div></div></header>

    <div id="two-column-menu"></div>

    <!-- SIDEBAR -->
    <div class="app-menu navbar-menu">
        <div class="navbar-brand-box">
            <a href="<?=base_url();?>" class="logo logo-dark"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22 text-primary"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1 text-primary fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
            <a href="<?=base_url();?>" class="logo logo-light"><span class="logo-sm"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="22"><?php else:?><i class="ri-mail-line fs-22"></i><?php endif;?></span><span class="logo-lg"><?php if($siteLogo):?><img src="<?=base_url($siteLogo);?>" alt="" height="28"><?php else:?><i class="ri-mail-line me-1 fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span><?php endif;?></span></a>
            <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover"><i class="ri-record-circle-line"></i></button>
        </div>
        <div id="scrollbar" data-simplebar>
            <div class="container-fluid">
                <ul class="navbar-nav" id="navbar-nav">
                    <li class="menu-title"><span><?=__('temp_mail_title');?></span></li>
                    <li class="nav-item"><a href="#" class="nav-link menu-link active" onclick="return false;"><i class="ri-inbox-line"></i><span><?=__('inbox');?></span><span class="badge badge-center rounded-pill bg-danger ms-auto d-none" id="sb-badge">0</span></a></li>
                    <li class="menu-title"><span><?=__('temp_mail_sidebar_info');?></span></li>
                    <li class="nav-item" id="sb-on" style="display:none"><div class="px-3 py-2"><div class="d-flex align-items-center"><i class="ri-mail-check-line text-success me-2 fs-16"></i><span class="text-truncate small fw-semibold" id="sb-email" style="color:#fff"></span></div><button class="btn btn-sm btn-light w-100 mt-2" onclick="copyEmail()"><i class="ri-file-copy-line me-1"></i><?=__('temp_mail_copy');?></button></div></li>
                    <li class="nav-item" id="sb-off"><div class="px-3 py-2"><small style="color:var(--vz-sidebar-menu-item-color);opacity:.6"><?=__('temp_mail_sidebar_hint');?></small></div></li>
                    <li class="menu-title"><span><?=__('temp_mail_sidebar_account');?></span></li>
                    <li class="nav-item"><a href="<?=base_url('auth/login');?>" class="nav-link menu-link"><i class="ri-login-box-line"></i><span><?=__('login');?></span></a></li>
                    <li class="nav-item"><a href="<?=base_url('auth/register');?>" class="nav-link menu-link"><i class="ri-user-add-line"></i><span><?=__('register');?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="vertical-overlay"></div>

    <!-- MAIN -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row g-3">

                    <!-- LEFT: Generate + Info -->
                    <div class="col-lg-4">
                        <div class="tm-left">
                            <!-- Generate -->
                            <div class="tm-card p-3 mb-3">
                                <div class="tm-section-label"><i class="ri-sparkling-2-fill text-warning"></i> <?= __('temp_mail_gen_label'); ?></div>
                                <?php if (!empty($sharedDomains)): ?>
                                <form id="getEmailForm" autocomplete="off">
                                    <div id="alert-box"></div>
                                    <div class="tm-gen-input mb-0">
                                        <input type="text" name="local_part" id="local_part" placeholder="<?=__('temp_mail_placeholder');?>" required minlength="3" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9._-]/g,'')">
                                        <span class="sep">@</span>
                                        <select name="domain_id" id="domain_id" required>
                                            <?php foreach ($sharedDomains as $d): ?>
                                            <option value="<?=$d['id']?>"><?=htmlspecialchars($d['domain_name'])?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-gen" id="btnGet"><i class="ri-arrow-right-line me-1"></i><?=__('temp_mail_get_btn');?></button>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-warning mb-0 small"><i class="ri-information-line me-1"></i><?=__('create_mailbox_no_domains');?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Active email -->
                            <div class="tm-card p-3 mb-3 d-none" id="email-panel">
                                <div class="tm-email-display">
                                    <div class="ed-label"><i class="ri-checkbox-circle-fill me-1"></i> <?= __('temp_mail_your_address'); ?></div>
                                    <div class="ed-addr" id="ed-email"></div>
                                    <div class="ed-actions">
                                        <button class="btn btn-soft-primary" onclick="copyEmail()" id="btnCopy"><i class="ri-file-copy-line me-1"></i><?=__('temp_mail_copy');?></button>
                                        <button class="btn btn-soft-secondary" onclick="refreshInbox()"><i class="ri-refresh-line me-1"></i><?=__('refresh');?></button>
                                    </div>
                                </div>
                                <div class="tm-stats">
                                    <div class="tm-stat">
                                        <div class="s-val" id="stat-total">0</div>
                                        <div class="s-label"><?=__('temp_mail_stat_total');?></div>
                                    </div>
                                    <div class="tm-stat">
                                        <div class="s-val" id="stat-unread">0</div>
                                        <div class="s-label"><?=__('temp_mail_stat_unread');?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Inbox -->
                    <div class="col-lg-8">
                        <div class="tm-card" id="inbox-card">
                            <div class="tm-inbox-head">
                                <div class="ih-left">
                                    <h6 class="ih-title"><?=__('inbox');?></h6>
                                    <span class="ih-badge d-none" id="ih-badge">0</span>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-ghost-secondary" onclick="refreshInbox()" title="<?=__('refresh');?>"><i class="ri-refresh-line" id="rIcon"></i></button>
                                </div>
                            </div>

                            <!-- Before email is generated -->
                            <div id="inbox-empty">
                                <div class="tm-ph" style="min-height:420px">
                                    <div class="tm-ph-icon"><i class="ri-mail-add-line"></i></div>
                                    <div class="tm-ph-t"><?=__('temp_mail_inbox_empty_title');?></div>
                                    <div class="tm-ph-s"><?=__('temp_mail_inbox_empty_desc');?></div>
                                </div>
                            </div>

                            <!-- After email is generated -->
                            <div class="d-none" id="inbox-active">
                                <div class="tm-split">
                                    <div class="tm-split-list" id="email-list">
                                        <div class="tm-ph">
                                            <div class="tm-ph-icon"><i class="ri-mail-open-line"></i></div>
                                            <div class="tm-ph-t"><?=__('temp_mail_no_email');?></div>
                                            <div class="tm-ph-s"><?=__('temp_mail_waiting');?></div>
                                            <div class="tm-ph-dots"><span></span><span></span><span></span></div>
                                        </div>
                                    </div>
                                    <div class="tm-split-reader" id="email-content">
                                        <div class="tm-ph">
                                            <div class="tm-ph-icon"><i class="ri-mail-unread-line"></i></div>
                                            <div class="tm-ph-t"><?=__('temp_mail_select_email');?></div>
                                            <div class="tm-ph-s"><?=__('temp_mail_click_to_read');?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Why Choose Section -->
                <div class="mt-4 mb-2">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold"><?= __('why_choose_title'); ?></h4>
                        <p class="text-muted mb-0" style="max-width:560px;margin:0 auto"><?= __('why_choose_desc'); ?></p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(var(--vz-primary-rgb),.1);color:var(--vz-primary)"><i class="ri-shield-check-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_privacy_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_privacy_desc'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(var(--vz-success-rgb),.1);color:var(--vz-success)"><i class="ri-flashlight-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_instant_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_instant_desc'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(var(--vz-warning-rgb),.1);color:var(--vz-warning)"><i class="ri-spam-2-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_spam_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_spam_desc'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(var(--vz-info-rgb),.1);color:var(--vz-info)"><i class="ri-user-unfollow-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_noreg_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_noreg_desc'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(var(--vz-danger-rgb),.1);color:var(--vz-danger)"><i class="ri-money-dollar-circle-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_free_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_free_desc'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="tm-card p-3 h-100">
                                <div class="tm-feat-icon" style="background:rgba(108,117,125,.1);color:#6c757d"><i class="ri-global-line"></i></div>
                                <h6 class="fw-semibold mt-3 mb-1"><?= __('why_feat_multi_title'); ?></h6>
                                <p class="text-muted small mb-0"><?= __('why_feat_multi_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <footer class="footer"><div class="container-fluid"><div class="row"><div class="col-sm-6"><script>document.write(new Date().getFullYear())</script> &copy; <?=htmlspecialchars($__siteName);?></div><div class="col-sm-6"><div class="text-sm-end d-none d-sm-block"><?=__('footer_system');?></div></div></div></div></footer>
    </div>
</div>

<script src="<?=base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/simplebar/simplebar.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/node-waves/waves.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/feather-icons/feather.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/js/plugins.js');?>"></script>
<script src="<?=base_url('public/material/assets/js/app.js');?>"></script>
<script>
var B=<?=json_encode(base_url())?>,rT=null,cE='';
var cs=['#405189','#0ab39c','#f06548','#f7b84b','#299cdb','#6559cc','#e83e8c','#2b8a3e','#1098ad','#845ef7'];
function gc(s){var h=0;for(var i=0;i<(s||'').length;i++)h=s.charCodeAt(i)+((h<<5)-h);return cs[Math.abs(h)%cs.length]}
function gi(s){return(s||'?')[0].toUpperCase()}
(function(){var e=document.getElementById('scrollbar');if(e&&typeof SimpleBar!=='undefined'&&!e.SimpleBar){e.classList.add('h-100');new SimpleBar(e)}})();

$('#getEmailForm').submit(function(e){
    e.preventDefault();var b=$('#btnGet');
    b.prop('disabled',1).html('<i class="ri-loader-4-line ri-spin me-1"></i>...');
    $.ajax({url:B+'/ajaxs/public/mailboxes.php?action=create',method:'POST',data:$(this).serialize(),dataType:'json',
        success:function(r){
            if(r.status==='success'){
                cE=r.email_address;
                $('#ed-email').text(cE);$('#email-panel').removeClass('d-none');
                $('#inbox-empty').addClass('d-none');$('#inbox-active').removeClass('d-none');
                $('#sb-email').text(cE);$('#sb-on').show();$('#sb-off').hide();
                $('#alert-box').empty();
                refreshInbox();if(rT)clearInterval(rT);rT=setInterval(refreshInbox,5000);
            }else{$('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show small mb-2">'+r.message+'<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>')}
            b.prop('disabled',0).html('<i class="ri-arrow-right-line me-1"></i>'+<?=json_encode(__('temp_mail_get_btn'))?>);
        },
        error:function(x){var m=(x.responseJSON&&x.responseJSON.message)?x.responseJSON.message:<?=json_encode(__('server_error'))?>;$('#alert-box').html('<div class="alert alert-danger alert-dismissible fade show small mb-2">'+m+'<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>');b.prop('disabled',0).html('<i class="ri-arrow-right-line me-1"></i>'+<?=json_encode(__('temp_mail_get_btn'))?>)}
    });
});

function refreshInbox(){
    $('#rIcon').addClass('refresh-spin');
    $.ajax({url:B+'/ajaxs/public/mailboxes.php?action=inbox',method:'GET',dataType:'json',
        success:function(r){
            $('#rIcon').removeClass('refresh-spin');
            if(r.status!=='success')return;
            var em=r.emails||[],u=em.filter(function(e){return e.is_read==0}).length,t=em.length;
            $('#stat-total').text(t);$('#stat-unread').text(u);
            u>0?$('#sb-badge').text(u).removeClass('d-none'):$('#sb-badge').addClass('d-none');
            t>0?$('#ih-badge').text(t).removeClass('d-none'):$('#ih-badge').addClass('d-none');
            renderList(em);
        },
        error:function(){$('#rIcon').removeClass('refresh-spin')}
    });
}

function renderList(em){
    var el=$('#email-list');
    if(!em||!em.length){el.html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-mail-open-line"></i></div><div class="tm-ph-t"><?=__("temp_mail_no_email")?></div><div class="tm-ph-s"><?=__("temp_mail_waiting")?></div><div class="tm-ph-dots"><span></span><span></span><span></span></div></div>');return}
    var h='';em.forEach(function(e){
        var f=e.from_name||e.from_address||'?',s=e.subject||'<?=__("no_subject")?>',t=ft(e.received_at||e.created_at),u=e.is_read==0?' unread':'',c=gc(f);
        h+='<div class="tm-ei'+u+'" onclick="readEmail('+e.id+',this)"><div class="ei-dot"></div><div class="ei-av" style="background:'+c+'">'+gi(f)+'</div><div class="ei-c"><div class="ei-r1"><span class="ei-f">'+esc(f)+'</span><span class="ei-t">'+t+'</span></div><div class="ei-s">'+esc(s)+'</div></div></div>';
    });el.html(h);
}

function readEmail(id,el){
    $('.tm-ei').removeClass('active');$(el).addClass('active').removeClass('unread');$(el).find('.ei-dot').hide();
    $('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-loader-4-line ri-spin"></i></div></div>');
    $.ajax({url:B+'/ajaxs/public/mailboxes.php?action=read&id='+id,method:'GET',dataType:'json',
        success:function(r){if(r.status==='success')showEmail(r.email);else $('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-error-warning-line"></i></div><div class="tm-ph-t">'+r.message+'</div></div>')},
        error:function(){$('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-error-warning-line"></i></div><div class="tm-ph-t"><?=__("server_error")?></div></div>')}
    });
}

function showEmail(e){
    var fn=e.from_name||e.from_address||'?',sub=e.subject||'<?=__("no_subject")?>',c=gc(fn),dt=e.received_at||e.created_at;
    var body=e.body_html||('<pre>'+esc(e.body_text||'')+'</pre>');
    var att='';
    if(e.attachments&&e.attachments.length){att='<div class="tm-rd-attach"><div class="fw-semibold small mb-2"><i class="ri-attachment-2 me-1"></i><?=__("attachments")?> ('+e.attachments.length+')</div>';e.attachments.forEach(function(a){att+='<a href="'+B+'/ajaxs/user/emails.php?action=download_attachment&id='+a.id+'" class="btn btn-sm btn-outline-secondary me-2 mb-1" target="_blank"><i class="ri-download-2-line me-1"></i>'+esc(a.original_filename)+' <small>('+fs(a.size)+')</small></a>'});att+='</div>'}
    $('#email-content').html('<div class="tm-rd-head"><div class="d-flex justify-content-between align-items-start gap-2"><h5>'+esc(sub)+'</h5><button class="btn btn-sm btn-soft-danger flex-shrink-0" onclick="delEmail('+e.id+')"><i class="ri-delete-bin-5-line"></i></button></div><div class="tm-rd-sender"><div class="rd-av" style="background:'+c+'">'+gi(fn)+'</div><div class="rd-info"><div class="rd-name">'+esc(fn)+'</div><div class="rd-email">'+esc(e.from_address)+'</div></div><div class="rd-date">'+esc(dt)+'</div></div></div><div class="tm-rd-body">'+body+'</div>'+att);
}

function delEmail(id){if(!confirm(<?=json_encode(__('delete_email').'?')?>))return;$.ajax({url:B+'/ajaxs/public/mailboxes.php?action=delete',method:'POST',data:{email_id:id},dataType:'json',success:function(r){if(r.status==='success'){$('#email-content').html('<div class="tm-ph"><div class="tm-ph-icon"><i class="ri-mail-unread-line"></i></div><div class="tm-ph-t"><?=__("temp_mail_select_email")?></div></div>');refreshInbox()}}})}
function copyEmail(){navigator.clipboard.writeText(cE).then(function(){var i=$('#btnCopy i');i.removeClass('ri-file-copy-line').addClass('ri-check-line');setTimeout(function(){i.removeClass('ri-check-line').addClass('ri-file-copy-line')},1500)})}
function esc(t){if(!t)return'';var d=document.createElement('div');d.appendChild(document.createTextNode(t));return d.innerHTML}
function ft(d){if(!d)return'';var o=new Date(d),n=new Date();if(o.toDateString()===n.toDateString())return o.getHours().toString().padStart(2,'0')+':'+o.getMinutes().toString().padStart(2,'0');return(o.getMonth()+1)+'/'+o.getDate()+' '+o.getHours().toString().padStart(2,'0')+':'+o.getMinutes().toString().padStart(2,'0')}
function fs(b){if(b>=1048576)return(b/1048576).toFixed(1)+' MB';if(b>=1024)return(b/1024).toFixed(1)+' KB';return b+' B'}
</script>
</body>
</html>
