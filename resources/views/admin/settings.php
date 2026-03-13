<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('settings'),
    'header' => '',
    'footer' => '',
];

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('settings'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>"><?= __('admin'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('settings'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header p-0 border-0">
                <!-- Velzon Nav Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-primary border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-general" role="tab">
                            <i class="ri-global-line me-1"></i> <?= __('general'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-mail" role="tab">
                            <i class="ri-mail-line me-1"></i> <?= __('mail_server'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-security" role="tab">
                            <i class="ri-shield-line me-1"></i> <?= __('security'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-limits" role="tab">
                            <i class="ri-bar-chart-line me-1"></i> <?= __('limits'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <form id="formGeneral" class="settings-form" data-tab="general">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('site_name'); ?></label>
                                    <input type="text" name="settings[site_name]" class="form-control" value="<?= sanitize(get_setting('site_name', 'Torymail')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('site_url'); ?></label>
                                    <input type="url" name="settings[site_url]" class="form-control" value="<?= sanitize(get_setting('site_url', '')); ?>" placeholder="https://mail.example.com">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('timezone_label'); ?></label>
                                    <select name="settings[timezone]" class="form-select">
                                        <?php
                                        $timezones = ['UTC', 'Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'Asia/Singapore', 'Asia/Tokyo', 'Asia/Seoul', 'Asia/Shanghai', 'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Australia/Sydney'];
                                        $currentTz = get_setting('timezone', 'UTC');
                                        foreach ($timezones as $tz):
                                        ?>
                                            <option value="<?= $tz; ?>" <?= $currentTz === $tz ? 'selected' : ''; ?>><?= $tz; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3 fw-semibold"><?= __('system_logo'); ?></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <?php $currentLogo = get_setting('site_logo', ''); ?>
                                        <div id="logoPreview" class="border rounded d-flex align-items-center justify-content-center" style="width:160px;height:50px;background:var(--vz-tertiary-bg);overflow:hidden;">
                                            <?php if ($currentLogo): ?>
                                            <img src="<?= base_url($currentLogo); ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                                            <?php else: ?>
                                            <span class="text-muted fs-12"><?= __('no_logo'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($currentLogo): ?>
                                        <button type="button" class="btn btn-soft-danger btn-sm" id="btnRemoveLogo">
                                            <i class="ri-delete-bin-line me-1"></i> <?= __('remove'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <label class="form-label"><?= __('upload_logo'); ?></label>
                                    <input type="file" id="logoFile" class="form-control" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                                    <small class="text-muted"><?= __('logo_hint'); ?></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('favicon_url'); ?></label>
                                    <input type="text" name="settings[site_favicon]" class="form-control" value="<?= sanitize(get_setting('site_favicon', '')); ?>" placeholder="https://example.com/favicon.ico">
                                    <small class="text-muted"><?= __('favicon_hint'); ?></small>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> <?= __('save_general'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Mail Server Settings -->
                    <div class="tab-pane fade" id="tab-mail" role="tabpanel">
                        <form id="formMail" class="settings-form" data-tab="mail">
                            <h6 class="mb-3 fw-semibold"><?= __('smtp_config'); ?></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('smtp_host'); ?></label>
                                    <input type="text" name="settings[smtp_host]" class="form-control" value="<?= sanitize(get_setting('smtp_host', '')); ?>" placeholder="smtp.example.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('smtp_port'); ?></label>
                                    <input type="number" name="settings[smtp_port]" class="form-control" value="<?= sanitize(get_setting('smtp_port', '587')); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('encryption'); ?></label>
                                    <select name="settings[smtp_encryption]" class="form-select">
                                        <?php $enc = get_setting('smtp_encryption', 'tls'); ?>
                                        <option value="tls" <?= $enc === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?= $enc === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?= $enc === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('smtp_username'); ?></label>
                                    <input type="text" name="settings[smtp_username]" class="form-control" value="<?= sanitize(get_setting('smtp_username', '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('smtp_password'); ?></label>
                                    <input type="password" name="settings[smtp_password]" class="form-control" value="<?= sanitize(get_setting('smtp_password', '')); ?>" placeholder="<?= __('keep_current'); ?>">
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3 fw-semibold"><?= __('mail_server'); ?></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('mail_hostname'); ?></label>
                                    <input type="text" name="settings[mail_server_hostname]" class="form-control" value="<?= sanitize(get_setting('mail_server_hostname', '')); ?>" placeholder="mail.example.com">
                                    <small class="text-muted"><?= __('mail_hostname_hint'); ?></small>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3 fw-semibold"><?= __('mx_config'); ?></h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label"><?= __('mx_value'); ?></label>
                                    <input type="text" name="settings[mx_record_value]" class="form-control" value="<?= sanitize(get_setting('mx_record_value', '')); ?>" placeholder="mail.example.com">
                                    <small class="text-muted"><?= __('mx_value_hint'); ?></small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?= __('mx_priority'); ?></label>
                                    <input type="number" name="settings[mx_record_priority]" class="form-control" value="<?= sanitize(get_setting('mx_record_priority', '10')); ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> <?= __('save_mail'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel">
                        <form id="formSecurity" class="settings-form" data-tab="security">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('allow_registration'); ?></label>
                                    <select name="settings[allow_registration]" class="form-select">
                                        <?php $ar = get_setting('allow_registration', '1'); ?>
                                        <option value="1" <?= $ar === '1' ? 'selected' : ''; ?>><?= __('yes'); ?></option>
                                        <option value="0" <?= $ar === '0' ? 'selected' : ''; ?>><?= __('no'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('require_verification'); ?></label>
                                    <select name="settings[require_email_verification]" class="form-select">
                                        <?php $rev = get_setting('require_email_verification', '1'); ?>
                                        <option value="1" <?= $rev === '1' ? 'selected' : ''; ?>><?= __('yes'); ?></option>
                                        <option value="0" <?= $rev === '0' ? 'selected' : ''; ?>><?= __('no'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('max_login_attempts'); ?></label>
                                    <input type="number" name="settings[max_login_attempts]" class="form-control" value="<?= sanitize(get_setting('max_login_attempts', '5')); ?>" min="1">
                                    <small class="text-muted"><?= __('max_login_hint'); ?></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('session_timeout'); ?></label>
                                    <input type="number" name="settings[session_timeout]" class="form-control" value="<?= sanitize(get_setting('session_timeout', '120')); ?>" min="5">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> <?= __('save_security'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Limits Settings -->
                    <div class="tab-pane fade" id="tab-limits" role="tabpanel">
                        <form id="formLimits" class="settings-form" data-tab="limits">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('max_domains_user'); ?></label>
                                    <input type="number" name="settings[max_domains_per_user]" class="form-control" value="<?= sanitize(get_setting('max_domains_per_user', '5')); ?>" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= __('max_mailboxes_domain'); ?></label>
                                    <input type="number" name="settings[max_mailboxes_per_domain]" class="form-control" value="<?= sanitize(get_setting('max_mailboxes_per_domain', '50')); ?>" min="1">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label"><?= __('default_quota_mb'); ?></label>
                                    <input type="number" name="settings[default_quota_mb]" class="form-control" value="<?= round(intval(get_setting('default_quota', '1073741824')) / 1048576); ?>" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?= __('max_attach_mb'); ?></label>
                                    <input type="number" name="settings[max_attachment_size_mb]" class="form-control" value="<?= round(intval(get_setting('max_attachment_size', '26214400')) / 1048576); ?>" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?= __('max_email_mb'); ?></label>
                                    <input type="number" name="settings[max_email_size_mb]" class="form-control" value="<?= round(intval(get_setting('max_email_size', '52428800')) / 1048576); ?>" min="1">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> <?= __('save_limits'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
$(document).ready(function() {
    // Save settings - each tab independently
    $('.settings-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var tab = form.data('tab');
        var btn = form.find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> <?= __('saving'); ?>');

        $.ajax({
            url: '<?= base_url("ajaxs/admin/settings.php?action=save"); ?>',
            method: 'POST',
            data: form.serialize() + '&tab=' + tab,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                } else {
                    showToast('error', res.message);
                }
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save ' + tab.charAt(0).toUpperCase() + tab.slice(1) + ' Settings');
            },
            error: function() {
                showToast('error', '<?= __('server_error'); ?>');
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save ' + tab.charAt(0).toUpperCase() + tab.slice(1) + ' Settings');
            }
        });
    });

    // Logo upload
    $('#logoFile').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            showToast('error', '<?= __('logo_max_size'); ?>');
            return;
        }
        var fd = new FormData();
        fd.append('logo', file);
        $.ajax({
            url: '<?= base_url("ajaxs/admin/settings.php?action=upload_logo"); ?>',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    $('#logoPreview').html('<img src="' + res.data.logo_url + '" style="max-width:100%;max-height:100%;object-fit:contain;">');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showToast('error', res.message);
                }
            },
            error: function() { showToast('error', '<?= __('upload_failed'); ?>'); }
        });
    });

    // Remove logo
    $('#btnRemoveLogo').on('click', function() {
        $.ajax({
            url: '<?= base_url("ajaxs/admin/settings.php?action=remove_logo"); ?>',
            method: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showToast('error', res.message);
                }
            }
        });
    });
});
</script>
