<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Settings - Torymail',
    'desc'  => 'Account settings',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

$activeTab = sanitize($_GET['tab'] ?? 'profile');
$validTabs = ['profile', 'security', 'signature', 'autoreply', 'display'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'profile';
}

// Fetch user's mailboxes for signature/autoreply tabs
$userMailboxes = $ToryMail->get_list_safe("
    SELECT `id`, `email`, `display_name`, `signature`, `auto_reply_enabled`, `auto_reply_subject`, `auto_reply_message`
    FROM `mailboxes`
    WHERE `user_id` = ? AND `status` = 'active'
    ORDER BY `email` ASC
", [$getUser['id']]);

// User preferences
$userPrefs = $ToryMail->get_row_safe("
    SELECT * FROM `user_settings`
    WHERE `user_id` = ?
", [$getUser['id']]);

if (!$userPrefs) {
    $userPrefs = [
        'emails_per_page' => 20,
        'default_mailbox_id' => '',
        'timezone' => 'UTC',
    ];
}
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Settings</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>">Home</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="ri-settings-3-line me-1 align-bottom text-primary"></i> Settings
        </h5>
    </div>

    <!-- Tabs -->
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'profile' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=profile'); ?>">
                    <i class="ri-user-line me-1 align-bottom"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'security' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=security'); ?>">
                    <i class="ri-lock-line me-1 align-bottom"></i> Security
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'signature' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=signature'); ?>">
                    <i class="ri-pen-nib-line me-1 align-bottom"></i> Signature
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'autoreply' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=autoreply'); ?>">
                    <i class="ri-reply-line me-1 align-bottom"></i> Auto-reply
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'display' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=display'); ?>">
                    <i class="ri-palette-line me-1 align-bottom"></i> Display
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <!-- Profile Tab -->
        <?php if ($activeTab === 'profile'): ?>
        <form id="profileForm">
            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($getUser['username'] ?? ''); ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($getUser['full_name'] ?? ''); ?>" placeholder="Your full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($getUser['email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php
                            $timezones = timezone_identifiers_list();
                            $currentTz = $userPrefs['timezone'] ?? 'UTC';
                            foreach ($timezones as $tz):
                            ?>
                            <option value="<?= $tz; ?>" <?= $currentTz === $tz ? 'selected' : ''; ?>><?= $tz; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Avatar URL</label>
                        <input type="url" name="avatar" class="form-control" value="<?= htmlspecialchars($getUser['avatar'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                        <div class="form-text">Enter a URL to your avatar image.</div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('profile')">
                        <i class="ri-save-line me-1"></i> Save Profile
                    </button>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 fw-semibold">
                            <?= strtoupper(substr($getUser['username'] ?? $getUser['email'] ?? 'U', 0, 1)); ?>
                        </div>
                    </div>
                    <p class="text-muted fs-13">
                        <?= htmlspecialchars($getUser['email'] ?? ''); ?>
                    </p>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Security Tab -->
        <?php if ($activeTab === 'security'): ?>
        <div class="row">
            <div class="col-lg-6">
                <h6 class="fw-semibold mb-3">Change Password</h6>
                <form id="securityForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('security')">
                        <i class="ri-lock-line me-1"></i> Update Password
                    </button>
                </form>
            </div>
            <div class="col-lg-6">
                <h6 class="fw-semibold mb-3">Two-Factor Authentication</h6>
                <div class="card border">
                    <div class="card-body text-center">
                        <?php if ($getUser['two_factor_enabled'] ?? false): ?>
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                <i class="ri-shield-check-fill"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">2FA is enabled</h6>
                        <p class="text-muted fs-13 mb-3">Your account is protected with two-factor authentication.</p>
                        <button class="btn btn-soft-danger btn-sm" onclick="toggle2FA('disable')">
                            <i class="ri-shield-line me-1"></i> Disable 2FA
                        </button>
                        <?php else: ?>
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                                <i class="ri-shield-line"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">2FA is not enabled</h6>
                        <p class="text-muted fs-13 mb-3">Add an extra layer of security to your account.</p>
                        <button class="btn btn-primary btn-sm" onclick="toggle2FA('enable')">
                            <i class="ri-shield-check-line me-1"></i> Enable 2FA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Signature Tab -->
        <?php if ($activeTab === 'signature'): ?>
        <?php if (empty($userMailboxes)): ?>
        <div class="text-center py-4">
            <p class="text-muted">No active mailboxes. <a href="<?= base_url('mailboxes'); ?>">Create a mailbox</a> first.</p>
        </div>
        <?php else: ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Select Mailbox</label>
            <select id="signatureMailbox" class="form-select" style="max-width:350px;">
                <?php foreach ($userMailboxes as $mb): ?>
                <option value="<?= $mb['id']; ?>" data-signature="<?= htmlspecialchars($mb['signature'] ?? ''); ?>">
                    <?= htmlspecialchars($mb['email']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Email Signature (HTML)</label>
            <div id="signatureEditor" contenteditable="true"
                 class="form-control" style="min-height:200px;font-size:14px;">
                <?= htmlspecialchars($userMailboxes[0]['signature'] ?? ''); ?>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="saveSignature()">
            <i class="ri-save-line me-1"></i> Save Signature
        </button>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Auto-reply Tab -->
        <?php if ($activeTab === 'autoreply'): ?>
        <?php if (empty($userMailboxes)): ?>
        <div class="text-center py-4">
            <p class="text-muted">No active mailboxes. <a href="<?= base_url('mailboxes'); ?>">Create a mailbox</a> first.</p>
        </div>
        <?php else: ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Select Mailbox</label>
            <select id="autoreplyMailbox" class="form-select" style="max-width:350px;">
                <?php foreach ($userMailboxes as $mb): ?>
                <option value="<?= $mb['id']; ?>"
                        data-enabled="<?= $mb['auto_reply_enabled'] ?? 0; ?>"
                        data-subject="<?= htmlspecialchars($mb['auto_reply_subject'] ?? ''); ?>"
                        data-message="<?= htmlspecialchars($mb['auto_reply_message'] ?? ''); ?>">
                    <?= htmlspecialchars($mb['email']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <form id="autoreplyForm">
            <input type="hidden" name="mailbox_id" id="arMailboxId" value="<?= $userMailboxes[0]['id'] ?? ''; ?>">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="arEnabled" name="auto_reply_enabled"
                           <?= ($userMailboxes[0]['auto_reply_enabled'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-medium" for="arEnabled">Enable Auto-reply</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="auto_reply_subject" id="arSubject" class="form-control"
                       value="<?= htmlspecialchars($userMailboxes[0]['auto_reply_subject'] ?? ''); ?>"
                       placeholder="I'm currently out of office">
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="auto_reply_message" id="arMessage" class="form-control" rows="6"
                          placeholder="Thank you for your email. I am currently out of the office..."><?= htmlspecialchars($userMailboxes[0]['auto_reply_message'] ?? ''); ?></textarea>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveAutoReply()">
                <i class="ri-save-line me-1"></i> Save Auto-reply
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Display Tab -->
        <?php if ($activeTab === 'display'): ?>
        <form id="displayForm">
            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Emails Per Page</label>
                        <select name="emails_per_page" class="form-select">
                            <?php foreach ([10, 20, 30, 50, 100] as $n): ?>
                            <option value="<?= $n; ?>" <?= ($userPrefs['emails_per_page'] ?? 20) == $n ? 'selected' : ''; ?>><?= $n; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Default Mailbox</label>
                        <select name="default_mailbox_id" class="form-select">
                            <option value="">No default</option>
                            <?php foreach ($userMailboxes as $mb): ?>
                            <option value="<?= $mb['id']; ?>" <?= ($userPrefs['default_mailbox_id'] ?? '') == $mb['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($mb['email']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('display')">
                        <i class="ri-save-line me-1"></i> Save Display Settings
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function saveSettings(section) {
    var formId = section + 'Form';
    var data = $('#' + formId).serialize();
    data += '&action=save_' + section;

    $.post('<?= base_url("ajaxs/user/settings.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Settings saved!');
        } else {
            tmToast('error', res.message || 'Failed to save settings.');
        }
    }, 'json');
}

function toggle2FA(action) {
    $.post('<?= base_url("ajaxs/user/settings.php"); ?>', {
        action: 'toggle_2fa',
        mode: action
    }, function(res) {
        if (res.success) {
            tmToast('success', res.message || '2FA updated!');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            tmToast('error', res.message || 'Failed to update 2FA.');
        }
    }, 'json');
}

// Signature mailbox switcher
$('#signatureMailbox').on('change', function() {
    var sig = $(this).find('option:selected').data('signature') || '';
    $('#signatureEditor').html(sig);
});

function saveSignature() {
    var mailboxId = $('#signatureMailbox').val();
    var signature = $('#signatureEditor').html();

    $.post('<?= base_url("ajaxs/user/settings.php"); ?>', {
        action: 'save_signature',
        mailbox_id: mailboxId,
        signature: signature
    }, function(res) {
        if (res.success) {
            tmToast('success', 'Signature saved!');
        } else {
            tmToast('error', res.message || 'Failed to save signature.');
        }
    }, 'json');
}

// Auto-reply mailbox switcher
$('#autoreplyMailbox').on('change', function() {
    var $opt = $(this).find('option:selected');
    $('#arMailboxId').val($(this).val());
    $('#arEnabled').prop('checked', $opt.data('enabled') == 1);
    $('#arSubject').val($opt.data('subject') || '');
    $('#arMessage').val($opt.data('message') || '');
});

function saveAutoReply() {
    var data = $('#autoreplyForm').serialize();
    data += '&action=save_autoreply';
    if (!$('#arEnabled').is(':checked')) {
        data += '&auto_reply_enabled=0';
    }

    $.post('<?= base_url("ajaxs/user/settings.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', 'Auto-reply settings saved!');
        } else {
            tmToast('error', res.message || 'Failed to save auto-reply settings.');
        }
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
