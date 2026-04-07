<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('settings_title'),
    'desc'  => __('account_settings'),
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
    SELECT `id`, `email_address`, `display_name`, `auto_reply_enabled`, `auto_reply_subject`, `auto_reply_message`
    FROM `mailboxes`
    WHERE `user_id` = ? AND `status` = 'active'
    ORDER BY `email_address` ASC
", [$getUser['id']]);

// User preferences (stored on users table)
$userPrefs = [
    'emails_per_page' => 20,
    'default_mailbox_id' => '',
    'timezone' => $getUser['timezone'] ?? 'UTC',
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('settings'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('settings'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="ri-settings-3-line me-1 align-bottom text-primary"></i> <?= __('settings'); ?>
        </h5>
    </div>

    <!-- Tabs -->
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'profile' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=profile'); ?>">
                    <i class="ri-user-line me-1 align-bottom"></i> <?= __('profile'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'security' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=security'); ?>">
                    <i class="ri-lock-line me-1 align-bottom"></i> <?= __('security'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'signature' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=signature'); ?>">
                    <i class="ri-pen-nib-line me-1 align-bottom"></i> <?= __('signature'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'autoreply' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=autoreply'); ?>">
                    <i class="ri-reply-line me-1 align-bottom"></i> <?= __('auto_reply'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'display' ? 'active' : ''; ?>" href="<?= base_url('settings?tab=display'); ?>">
                    <i class="ri-palette-line me-1 align-bottom"></i> <?= __('display'); ?>
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
                        <label class="form-label fw-medium"><?= __('username'); ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($getUser['email'] ?? ''); ?>" disabled>
                        <div class="form-text"><?= __('username_note'); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium"><?= __('fullname'); ?></label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($getUser['fullname'] ?? ''); ?>" placeholder="<?= __('your_fullname'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium"><?= __('email'); ?></label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($getUser['email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium"><?= __('timezone_label'); ?></label>
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
                        <label class="form-label fw-medium"><?= __('avatar_url'); ?></label>
                        <input type="url" name="avatar" class="form-control" value="<?= htmlspecialchars($getUser['avatar'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                        <div class="form-text"><?= __('avatar_hint'); ?></div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('profile')">
                        <i class="ri-save-line me-1"></i> <?= __('save_profile'); ?>
                    </button>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 fw-semibold">
                            <?= strtoupper(substr($getUser['email'] ?? $getUser['email'] ?? 'U', 0, 1)); ?>
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
                <h6 class="fw-semibold mb-3"><?= __('change_password'); ?></h6>
                <form id="securityForm">
                    <div class="mb-3">
                        <label class="form-label"><?= __('current_password'); ?></label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('new_password_label'); ?></label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('confirm_new_password'); ?></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('security')">
                        <i class="ri-lock-line me-1"></i> <?= __('update_password'); ?>
                    </button>
                </form>
            </div>
            <div class="col-lg-6">
                <h6 class="fw-semibold mb-3"><?= __('two_factor'); ?></h6>
                <div class="card border">
                    <div class="card-body text-center">
                        <?php if ($getUser['two_factor_enabled'] ?? false): ?>
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                <i class="ri-shield-check-fill"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold"><?= __('2fa_enabled'); ?></h6>
                        <p class="text-muted fs-13 mb-3"><?= __('2fa_enabled_desc'); ?></p>
                        <button class="btn btn-soft-danger btn-sm" onclick="toggle2FA('disable')">
                            <i class="ri-shield-line me-1"></i> <?= __('disable_2fa'); ?>
                        </button>
                        <?php else: ?>
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                                <i class="ri-shield-line"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold"><?= __('2fa_disabled'); ?></h6>
                        <p class="text-muted fs-13 mb-3"><?= __('2fa_disabled_desc'); ?></p>
                        <button class="btn btn-primary btn-sm" onclick="toggle2FA('enable')">
                            <i class="ri-shield-check-line me-1"></i> <?= __('enable_2fa'); ?>
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
            <p class="text-muted"><?= __('no_mailbox_msg'); ?></p>
        </div>
        <?php else: ?>
        <div class="mb-3">
            <label class="form-label fw-medium"><?= __('select_mailbox'); ?></label>
            <select id="signatureMailbox" class="form-select" style="max-width:350px;">
                <?php foreach ($userMailboxes as $mb): ?>
                <option value="<?= $mb['id']; ?>">
                    <?= htmlspecialchars($mb['email_address']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium"><?= __('email_signature'); ?></label>
            <div id="signatureEditor" contenteditable="true"
                 class="form-control" style="min-height:200px;font-size:14px;">
                <?= htmlspecialchars($getUser['signature'] ?? ''); ?>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="saveSignature()">
            <i class="ri-save-line me-1"></i> <?= __('save_signature'); ?>
        </button>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Auto-reply Tab -->
        <?php if ($activeTab === 'autoreply'): ?>
        <?php if (empty($userMailboxes)): ?>
        <div class="text-center py-4">
            <p class="text-muted"><?= __('no_mailbox_msg'); ?></p>
        </div>
        <?php else: ?>
        <div class="mb-3">
            <label class="form-label fw-medium"><?= __('select_mailbox'); ?></label>
            <select id="autoreplyMailbox" class="form-select" style="max-width:350px;">
                <?php foreach ($userMailboxes as $mb): ?>
                <option value="<?= $mb['id']; ?>"
                        data-enabled="<?= $mb['auto_reply_enabled'] ?? 0; ?>"
                        data-subject="<?= htmlspecialchars($mb['auto_reply_subject'] ?? ''); ?>"
                        data-message="<?= htmlspecialchars($mb['auto_reply_message'] ?? ''); ?>">
                    <?= htmlspecialchars($mb['email_address']); ?>
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
                    <label class="form-check-label fw-medium" for="arEnabled"><?= __('enable_auto_reply'); ?></label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= __('subject'); ?></label>
                <input type="text" name="auto_reply_subject" id="arSubject" class="form-control"
                       value="<?= htmlspecialchars($userMailboxes[0]['auto_reply_subject'] ?? ''); ?>"
                       placeholder="<?= __('auto_reply_subject'); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label"><?= __('message'); ?></label>
                <textarea name="auto_reply_message" id="arMessage" class="form-control" rows="6"
                          placeholder="<?= __('auto_reply_msg'); ?>"><?= htmlspecialchars($userMailboxes[0]['auto_reply_message'] ?? ''); ?></textarea>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveAutoReply()">
                <i class="ri-save-line me-1"></i> <?= __('save_auto_reply'); ?>
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
                        <label class="form-label fw-medium"><?= __('emails_per_page'); ?></label>
                        <select name="emails_per_page" class="form-select">
                            <?php foreach ([10, 20, 30, 50, 100] as $n): ?>
                            <option value="<?= $n; ?>" <?= ($userPrefs['emails_per_page'] ?? 20) == $n ? 'selected' : ''; ?>><?= $n; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium"><?= __('default_mailbox'); ?></label>
                        <select name="default_mailbox_id" class="form-select">
                            <option value=""><?= __('no_default'); ?></option>
                            <?php foreach ($userMailboxes as $mb): ?>
                            <option value="<?= $mb['id']; ?>" <?= ($userPrefs['default_mailbox_id'] ?? '') == $mb['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($mb['email_address']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings('display')">
                        <i class="ri-save-line me-1"></i> <?= __('save_display'); ?>
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
    var actionMap = {'profile': 'update_profile', 'security': 'change_password', 'display': 'update_profile'};
    var act = actionMap[section] || 'update_profile';

    $.ajax({
        url: '<?= base_url("ajaxs/user/settings.php"); ?>?action=' + act,
        method: 'POST', data: data, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__('settings_saved')); ?>);
            } else {
                tmToast('error', res.message || <?= json_encode(__('settings_failed')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('settings_failed')); ?>;
            tmToast('error', msg);
        }
    });
}

function toggle2FA(action) {
    $.ajax({
        url: '<?= base_url("ajaxs/user/settings.php"); ?>?action=toggle_2fa',
        method: 'POST', data: { mode: action }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__('settings_saved')); ?>);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                tmToast('error', res.message || <?= json_encode(__('settings_failed')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('settings_failed')); ?>;
            tmToast('error', msg);
        }
    });
}

// Signature is user-level, not per-mailbox

function saveSignature() {
    var mailboxId = $('#signatureMailbox').val();
    var signature = $('#signatureEditor').html();

    $.ajax({
        url: '<?= base_url("ajaxs/user/settings.php"); ?>?action=update_signature',
        method: 'POST', data: { mailbox_id: mailboxId, signature: signature }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', <?= json_encode(__('settings_saved')); ?>);
            } else {
                tmToast('error', res.message || <?= json_encode(__('settings_failed')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('settings_failed')); ?>;
            tmToast('error', msg);
        }
    });
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
    if (!$('#arEnabled').is(':checked')) {
        data += '&auto_reply_enabled=0';
    }

    $.ajax({
        url: '<?= base_url("ajaxs/user/mailboxes.php"); ?>?action=set_auto_reply',
        method: 'POST', data: data, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', <?= json_encode(__('settings_saved')); ?>);
            } else {
                tmToast('error', res.message || <?= json_encode(__('settings_failed')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('settings_failed')); ?>;
            tmToast('error', msg);
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
