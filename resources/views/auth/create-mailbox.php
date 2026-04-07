<?php
$body = [
    'title' => __('temp_mail_title') . ' — ' . get_setting('site_name', 'Torymail'),
];

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
    <style>
        body { background: linear-gradient(135deg, #e8dff5 0%, #fce4ec 50%, #e3f2fd 100%); min-height: 100vh; }
        .hero-title { font-size: 2.8rem; font-weight: 800; color: #1a1a2e; letter-spacing: -0.5px; }
        .hero-subtitle { color: #6c757d; font-size: 1.1rem; }
        .get-email-form { background: #fff; border-radius: 50px; padding: 6px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .get-email-form input, .get-email-form select { border: none; background: transparent; font-size: 1rem; }
        .get-email-form input:focus, .get-email-form select:focus { box-shadow: none; outline: none; }
        .get-email-form .at-sign { color: #adb5bd; font-weight: 600; font-size: 1.1rem; padding: 0 4px; }
        .btn-get-email { border-radius: 50px; padding: 10px 28px; font-weight: 600; font-size: 1rem; }
        .inbox-wrapper { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden; min-height: 450px; }
        .email-list { border-right: 1px solid #e9ecef; min-height: 450px; }
        .email-item { padding: 14px 18px; border-bottom: 1px solid #f1f3f5; cursor: pointer; transition: background 0.15s; }
        .email-item:hover { background: #f8f9ff; }
        .email-item.active { background: #eef2ff; border-left: 3px solid var(--vz-primary); }
        .email-item.unread { font-weight: 600; }
        .email-item .email-from { font-size: 0.95rem; color: #1a1a2e; }
        .email-item .email-subject { font-size: 0.85rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .email-item .email-time { font-size: 0.75rem; color: #adb5bd; }
        .email-content { padding: 24px; }
        .email-content .email-header { border-bottom: 1px solid #e9ecef; padding-bottom: 16px; margin-bottom: 16px; }
        .inbox-header { padding: 16px 18px; border-bottom: 1px solid #e9ecef; background: #fafbfc; }
        .no-email-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; color: #adb5bd; }
        .no-email-placeholder i { font-size: 4rem; margin-bottom: 12px; }
        .email-address-display { background: #f0f4ff; border-radius: 8px; padding: 8px 16px; font-weight: 600; color: var(--vz-primary); display: inline-block; }
        .refresh-spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            .hero-title { font-size: 1.8rem; }
            .get-email-form { border-radius: 16px; padding: 8px; }
            .get-email-form .d-flex { flex-wrap: wrap; gap: 8px; }
            .btn-get-email { width: 100%; border-radius: 12px; }
            .email-list { min-height: auto; border-right: none; border-bottom: 1px solid #e9ecef; }
            .inbox-wrapper { border-radius: 12px; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Hero -->
    <div class="text-center mb-4 pt-4">
        <h1 class="hero-title"><?= __('temp_mail_hero') ?></h1>
        <p class="hero-subtitle"><?= __('temp_mail_subtitle') ?></p>
    </div>

    <!-- Get Email Form -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8 col-xl-7">
            <div id="alert-box"></div>
            <?php if (empty($sharedDomains)): ?>
                <div class="alert alert-warning text-center">
                    <i class="ri-information-line me-1"></i> <?= __('create_mailbox_no_domains') ?>
                </div>
            <?php else: ?>
            <form id="getEmailForm" autocomplete="off">
                <div class="get-email-form d-flex align-items-center">
                    <input type="text" name="local_part" id="local_part" class="form-control flex-grow-1 ps-4"
                           placeholder="<?= __('temp_mail_placeholder') ?>" required minlength="3"
                           oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9._-]/g, '')">
                    <span class="at-sign">@</span>
                    <select name="domain_id" id="domain_id" class="form-select" style="max-width: 220px;" required>
                        <?php foreach ($sharedDomains as $d): ?>
                            <option value="<?= $d['id'] ?>" data-domain="<?= htmlspecialchars($d['domain_name']) ?>"><?= htmlspecialchars($d['domain_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-get-email ms-2" id="btnGetEmail">
                        <?= __('temp_mail_get_btn') ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current email display -->
    <div id="current-email-bar" class="text-center mb-3 d-none">
        <span class="email-address-display">
            <i class="ri-mail-line me-1"></i> <span id="current-email-text"></span>
            <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyEmail()" title="<?= __('temp_mail_copy') ?>">
                <i class="ri-file-copy-line"></i>
            </button>
        </span>
    </div>

    <!-- Inbox -->
    <div id="inbox-section" class="row justify-content-center d-none">
        <div class="col-lg-10 col-xl-9">
            <div class="inbox-wrapper">
                <div class="row g-0">
                    <!-- Email List -->
                    <div class="col-md-5 col-lg-4">
                        <div class="inbox-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><i class="ri-inbox-archive-line me-1"></i> <?= __('inbox') ?></h5>
                            <button class="btn btn-sm btn-light" onclick="refreshInbox()" id="btnRefresh" title="<?= __('refresh') ?>">
                                <i class="ri-refresh-line" id="refreshIcon"></i>
                            </button>
                        </div>
                        <div id="email-list">
                            <div class="no-email-placeholder" id="no-emails">
                                <i class="ri-mail-open-line"></i>
                                <span><?= __('temp_mail_no_email') ?></span>
                            </div>
                        </div>
                    </div>
                    <!-- Email Content -->
                    <div class="col-md-7 col-lg-8">
                        <div id="email-content">
                            <div class="no-email-placeholder">
                                <i class="ri-mail-unread-line"></i>
                                <span><?= __('temp_mail_select_email') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4 pb-3">
        <p class="text-muted mb-1">
            <a href="<?= base_url('auth/login') ?>" class="text-muted"><i class="ri-login-box-line me-1"></i><?= __('login') ?></a>
            <span class="mx-2">|</span>
            <a href="?lang=en" class="text-muted <?= current_lang() === 'en' ? 'fw-bold' : '' ?>">English</a>
            <span class="mx-1">|</span>
            <a href="?lang=vi" class="text-muted <?= current_lang() === 'vi' ? 'fw-bold' : '' ?>">Tiếng Việt</a>
        </p>
        <p class="text-muted mb-0 small">&copy; <script>document.write(new Date().getFullYear())</script> <?= htmlspecialchars(get_setting('site_name', 'Torymail')) ?></p>
    </div>
</div>

<script src="<?= asset_url('material/assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset_url('js/jquery-3.6.0.js') ?>"></script>
<script>
var BASE = <?= json_encode(base_url()) ?>;
var refreshTimer = null;
var currentMailboxId = null;
var currentEmailAddress = '';

$.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
    beforeSend: function(xhr, s) {
        if (s.type === 'POST' && typeof s.data === 'string') {
            s.data += '&_csrf_token=' + encodeURIComponent($('meta[name="csrf-token"]').attr('content'));
        }
    }
});

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
                refreshInbox();
                // Auto-refresh every 5 seconds
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

// Refresh Inbox
function refreshInbox() {
    var icon = $('#refreshIcon');
    icon.addClass('refresh-spin');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=inbox',
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            icon.removeClass('refresh-spin');
            if (res.status === 'success') {
                renderEmailList(res.emails);
            }
        },
        error: function() {
            icon.removeClass('refresh-spin');
        }
    });
}

// Render email list
function renderEmailList(emails) {
    var list = $('#email-list');
    if (!emails || emails.length === 0) {
        list.html('<div class="no-email-placeholder" id="no-emails"><i class="ri-mail-open-line"></i><span><?= __("temp_mail_no_email") ?></span></div>');
        return;
    }

    var html = '';
    emails.forEach(function(e) {
        var fromDisplay = e.from_name || e.from_address || '<?= __("temp_mail_unknown") ?>';
        var subject = e.subject || '<?= __("no_subject") ?>';
        var time = formatTime(e.received_at || e.created_at);
        var unreadClass = e.is_read == 0 ? 'unread' : '';
        var dot = e.is_read == 0 ? '<span class="badge bg-primary rounded-circle p-1 me-1">&nbsp;</span>' : '';

        html += '<div class="email-item ' + unreadClass + '" onclick="readEmail(' + e.id + ', this)">' +
            '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="email-from text-truncate">' + dot + escapeHtml(fromDisplay) + '</div>' +
                '<div class="email-time">' + time + '</div>' +
            '</div>' +
            '<div class="email-subject">' + escapeHtml(subject) + '</div>' +
            (e.has_attachments == 1 ? '<small class="text-muted"><i class="ri-attachment-2 me-1"></i><?= __("attachments") ?></small>' : '') +
        '</div>';
    });
    list.html(html);
}

// Read email
function readEmail(id, el) {
    $('.email-item').removeClass('active');
    $(el).addClass('active').removeClass('unread');
    $(el).find('.badge').remove();

    $('#email-content').html('<div class="no-email-placeholder"><i class="ri-loader-4-line ri-spin" style="font-size:2rem"></i></div>');

    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=read&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                renderEmail(res.email);
            } else {
                $('#email-content').html('<div class="no-email-placeholder"><i class="ri-error-warning-line"></i><span>' + res.message + '</span></div>');
            }
        },
        error: function() {
            $('#email-content').html('<div class="no-email-placeholder"><i class="ri-error-warning-line"></i><span><?= __("server_error") ?></span></div>');
        }
    });
}

// Render email content
function renderEmail(email) {
    var from = email.from_name ? escapeHtml(email.from_name) + ' &lt;' + escapeHtml(email.from_address) + '&gt;' : escapeHtml(email.from_address);
    var time = email.received_at || email.created_at;
    var subject = email.subject || '<?= __("no_subject") ?>';
    var body = email.body_html || ('<pre style="white-space:pre-wrap;font-family:inherit">' + escapeHtml(email.body_text || '') + '</pre>');

    var attachHtml = '';
    if (email.attachments && email.attachments.length > 0) {
        attachHtml = '<div class="mt-3 pt-3 border-top"><strong><i class="ri-attachment-2 me-1"></i><?= __("attachments") ?></strong><div class="mt-2">';
        email.attachments.forEach(function(a) {
            attachHtml += '<a href="' + BASE + '/ajaxs/user/emails.php?action=download_attachment&id=' + a.id + '" class="btn btn-sm btn-outline-primary me-2 mb-2" target="_blank">' +
                '<i class="ri-download-line me-1"></i>' + escapeHtml(a.original_filename) +
                ' <small class="text-muted">(' + formatSize(a.size) + ')</small></a>';
        });
        attachHtml += '</div></div>';
    }

    var html = '<div class="email-content">' +
        '<div class="email-header">' +
            '<div class="d-flex justify-content-between align-items-start mb-2">' +
                '<h5 class="mb-0">' + escapeHtml(subject) + '</h5>' +
                '<button class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" onclick="deleteEmail(' + email.id + ')" title="<?= __("delete") ?>"><i class="ri-delete-bin-line"></i></button>' +
            '</div>' +
            '<div class="text-muted small">' +
                '<div><strong><?= __("from") ?>:</strong> ' + from + '</div>' +
                '<div><strong><?= __("date") ?>:</strong> ' + escapeHtml(time) + '</div>' +
            '</div>' +
        '</div>' +
        '<div class="email-body mt-3">' + body + '</div>' +
        attachHtml +
    '</div>';

    $('#email-content').html(html);
}

// Delete email
function deleteEmail(id) {
    if (!confirm(<?= json_encode(__('delete_email') . ' ' . __('confirm') . '?') ?>)) return;
    $.ajax({
        url: BASE + '/ajaxs/public/mailboxes.php?action=delete',
        method: 'POST',
        data: {email_id: id},
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#email-content').html('<div class="no-email-placeholder"><i class="ri-mail-unread-line"></i><span><?= __("temp_mail_select_email") ?></span></div>');
                refreshInbox();
            }
        }
    });
}

// Copy email
function copyEmail() {
    navigator.clipboard.writeText(currentEmailAddress).then(function() {
        var btn = $('#current-email-bar .btn-link i');
        btn.removeClass('ri-file-copy-line').addClass('ri-check-line text-success');
        setTimeout(function() { btn.removeClass('ri-check-line text-success').addClass('ri-file-copy-line'); }, 1500);
    });
}

// Helpers
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function formatTime(dt) {
    if (!dt) return '';
    var d = new Date(dt);
    var now = new Date();
    if (d.toDateString() === now.toDateString()) {
        return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
    }
    return (d.getMonth()+1) + '/' + d.getDate() + ' ' + d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function formatSize(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}
</script>
</body>
</html>
