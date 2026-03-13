<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Settings',
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
            <h4 class="mb-sm-0">Settings</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= admin_url('home'); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Settings</li>
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
                            <i class="ri-global-line me-1"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-mail" role="tab">
                            <i class="ri-mail-line me-1"></i> Mail Server
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-security" role="tab">
                            <i class="ri-shield-line me-1"></i> Security
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-limits" role="tab">
                            <i class="ri-bar-chart-line me-1"></i> Limits
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
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-control" value="<?= sanitize(get_setting('site_name', 'Torymail')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Site URL</label>
                                    <input type="url" name="site_url" class="form-control" value="<?= sanitize(get_setting('site_url', '')); ?>" placeholder="https://mail.example.com">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-select">
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
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Mail Server Settings -->
                    <div class="tab-pane fade" id="tab-mail" role="tabpanel">
                        <form id="formMail" class="settings-form" data-tab="mail">
                            <h6 class="mb-3 fw-semibold">SMTP Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= sanitize(get_setting('smtp_host', '')); ?>" placeholder="smtp.example.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?= sanitize(get_setting('smtp_port', '587')); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Encryption</label>
                                    <select name="smtp_encryption" class="form-select">
                                        <?php $enc = get_setting('smtp_encryption', 'tls'); ?>
                                        <option value="tls" <?= $enc === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?= $enc === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?= $enc === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-control" value="<?= sanitize(get_setting('smtp_username', '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="smtp_password" class="form-control" value="<?= sanitize(get_setting('smtp_password', '')); ?>" placeholder="Leave empty to keep current">
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3 fw-semibold">Mail Server</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mail Server Hostname</label>
                                    <input type="text" name="mail_server_hostname" class="form-control" value="<?= sanitize(get_setting('mail_server_hostname', '')); ?>" placeholder="mail.example.com">
                                    <small class="text-muted">The hostname that MX records should point to</small>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3 fw-semibold">MX Record Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Required MX Record Value</label>
                                    <input type="text" name="mx_record_value" class="form-control" value="<?= sanitize(get_setting('mx_record_value', '')); ?>" placeholder="mail.example.com">
                                    <small class="text-muted">Users must point their MX records to this value</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">MX Priority</label>
                                    <input type="number" name="mx_record_priority" class="form-control" value="<?= sanitize(get_setting('mx_record_priority', '10')); ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Save Mail Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel">
                        <form id="formSecurity" class="settings-form" data-tab="security">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Allow Registration</label>
                                    <select name="allow_registration" class="form-select">
                                        <?php $ar = get_setting('allow_registration', '1'); ?>
                                        <option value="1" <?= $ar === '1' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?= $ar === '0' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Require Email Verification</label>
                                    <select name="require_email_verification" class="form-select">
                                        <?php $rev = get_setting('require_email_verification', '1'); ?>
                                        <option value="1" <?= $rev === '1' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?= $rev === '0' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" class="form-control" value="<?= sanitize(get_setting('max_login_attempts', '5')); ?>" min="1">
                                    <small class="text-muted">Before account lockout</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" name="session_timeout" class="form-control" value="<?= sanitize(get_setting('session_timeout', '120')); ?>" min="5">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Save Security Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Limits Settings -->
                    <div class="tab-pane fade" id="tab-limits" role="tabpanel">
                        <form id="formLimits" class="settings-form" data-tab="limits">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Max Domains per User</label>
                                    <input type="number" name="max_domains_per_user" class="form-control" value="<?= sanitize(get_setting('max_domains_per_user', '5')); ?>" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Max Mailboxes per Domain</label>
                                    <input type="number" name="max_mailboxes_per_domain" class="form-control" value="<?= sanitize(get_setting('max_mailboxes_per_domain', '50')); ?>" min="1">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Default Storage Quota (MB)</label>
                                    <input type="number" name="default_quota_mb" class="form-control" value="<?= round(intval(get_setting('default_quota', '1073741824')) / 1048576); ?>" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Attachment Size (MB)</label>
                                    <input type="number" name="max_attachment_size_mb" class="form-control" value="<?= round(intval(get_setting('max_attachment_size', '26214400')) / 1048576); ?>" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Email Size (MB)</label>
                                    <input type="number" name="max_email_size_mb" class="form-control" value="<?= round(intval(get_setting('max_email_size', '52428800')) / 1048576); ?>" min="1">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i> Save Limit Settings
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
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

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
                showToast('error', 'Server connection error');
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save ' + tab.charAt(0).toUpperCase() + tab.slice(1) + ' Settings');
            }
        });
    });
});
</script>
