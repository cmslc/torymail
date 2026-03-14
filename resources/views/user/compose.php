<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('compose_title'),
    'desc'  => __('new_email'),
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's active mailboxes
$userMailboxes = $ToryMail->get_list_safe("
    SELECT m.`id`, m.`email_address`, m.`display_name`
    FROM `mailboxes` m
    JOIN `domains` d ON m.`domain_id` = d.`id`
    WHERE m.`user_id` = ? AND m.`status` = 'active' AND d.`status` = 'active'
    ORDER BY m.`email_address` ASC
", [$getUser['id']]);

// Check for reply/forward
$replyTo = sanitize($_GET['reply_to'] ?? '');
$forward = sanitize($_GET['forward'] ?? '');
$replyAll = sanitize($_GET['reply_all'] ?? '');
$draftId = sanitize($_GET['draft'] ?? '');

$prefill = [
    'to' => '',
    'cc' => '',
    'bcc' => '',
    'subject' => '',
    'body' => '',
    'from_mailbox' => '',
];

if ($replyTo) {
    $original = $ToryMail->get_row_safe("SELECT e.* FROM `emails` e JOIN `mailboxes` m ON e.`mailbox_id` = m.`id` WHERE e.`id` = ? AND m.`user_id` = ?", [$replyTo, $getUser['id']]);
    if ($original) {
        $prefill['to'] = $original['from_address'];
        $prefill['subject'] = 'Re: ' . preg_replace('/^Re:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>' . htmlspecialchars($original['from_name'] ?: $original['from_address']) . '</strong> wrote on ' . format_date($original['created_at']) . ':</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($replyAll) {
    $original = $ToryMail->get_row_safe("SELECT e.* FROM `emails` e JOIN `mailboxes` m ON e.`mailbox_id` = m.`id` WHERE e.`id` = ? AND m.`user_id` = ?", [$replyAll, $getUser['id']]);
    if ($original) {
        $prefill['to'] = $original['from_address'];
        $prefill['cc'] = $original['cc_addresses'] ?? '';
        $prefill['subject'] = 'Re: ' . preg_replace('/^Re:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>' . htmlspecialchars($original['from_name'] ?: $original['from_address']) . '</strong> wrote on ' . format_date($original['created_at']) . ':</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($forward) {
    $original = $ToryMail->get_row_safe("SELECT e.* FROM `emails` e JOIN `mailboxes` m ON e.`mailbox_id` = m.`id` WHERE e.`id` = ? AND m.`user_id` = ?", [$forward, $getUser['id']]);
    if ($original) {
        $prefill['subject'] = 'Fwd: ' . preg_replace('/^Fwd:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>---------- Forwarded message ----------</strong></p>'
            . '<p>From: ' . htmlspecialchars($original['from_name'] . ' <' . $original['from_address'] . '>') . '<br>'
            . 'Date: ' . format_date($original['created_at']) . '<br>'
            . 'Subject: ' . htmlspecialchars($original['subject']) . '</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($draftId) {
    $draft = $ToryMail->get_row_safe("SELECT e.* FROM `emails` e JOIN `mailboxes` m ON e.`mailbox_id` = m.`id` WHERE e.`id` = ? AND m.`user_id` = ? AND e.`folder` = 'drafts'", [$draftId, $getUser['id']]);
    if ($draft) {
        $prefill['to'] = $draft['to_addresses'] ?? '';
        $prefill['cc'] = $draft['cc_addresses'] ?? '';
        $prefill['bcc'] = $draft['bcc_addresses'] ?? '';
        $prefill['subject'] = $draft['subject'] ?? '';
        $prefill['body'] = $draft['body_html'] ?: $draft['body_text'] ?? '';
        $prefill['from_mailbox'] = $draft['mailbox_id'] ?? '';
    }
}

// Fetch contacts for autocomplete
$contacts = $ToryMail->get_list_safe("
    SELECT `name`, `email` FROM `contacts`
    WHERE `user_id` = ?
    ORDER BY `name` ASC
    LIMIT 500
", [$getUser['id']]);
$contactsJson = json_encode($contacts);

$composeTitle = __('new_email');
if ($replyTo || $replyAll) $composeTitle = __('reply');
elseif ($forward) $composeTitle = __('forward');
elseif ($draftId) $composeTitle = __('edit_draft');
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= $composeTitle; ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('compose'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-edit-2-line me-1 align-bottom text-primary"></i> <?= $composeTitle; ?>
            </h5>
            <a href="<?= base_url('inbox'); ?>" class="btn btn-soft-danger btn-sm">
                <i class="ri-close-line me-1"></i> <?= __('discard'); ?>
            </a>
        </div>
    </div>

    <form id="composeForm" enctype="multipart/form-data">
        <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draftId); ?>">
        <input type="hidden" name="reply_to" value="<?= htmlspecialchars($replyTo ?: $replyAll); ?>">
        <input type="hidden" name="forward" value="<?= htmlspecialchars($forward); ?>">

        <div class="card-body border-bottom">
            <!-- From -->
            <div class="row mb-3">
                <label class="col-sm-1 col-form-label text-muted"><?= __('from'); ?></label>
                <div class="col-sm-11">
                    <select name="from_mailbox_id" class="form-select" required>
                        <?php foreach ($userMailboxes as $mb): ?>
                        <option value="<?= $mb['id']; ?>"
                                data-signature=""
                                <?= $prefill['from_mailbox'] == $mb['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars(($mb['display_name'] ? $mb['display_name'] . ' ' : '') . '<' . $mb['email_address'] . '>'); ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($userMailboxes)): ?>
                        <option value="" disabled selected><?= __('no_mailbox_warning'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- To -->
            <div class="row mb-3">
                <label class="col-sm-1 col-form-label text-muted"><?= __('to'); ?></label>
                <div class="col-sm-9">
                    <div class="position-relative">
                        <input type="text" name="to" id="toField" class="form-control"
                               value="<?= htmlspecialchars($prefill['to']); ?>"
                               placeholder="<?= __('to_placeholder'); ?>" required>
                        <div id="toAutocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="col-sm-2 d-flex gap-1 align-items-center">
                    <button type="button" class="btn btn-soft-secondary btn-sm" id="toggleCc"><?= __('cc'); ?></button>
                    <button type="button" class="btn btn-soft-secondary btn-sm" id="toggleBcc"><?= __('bcc'); ?></button>
                </div>
            </div>

            <!-- CC -->
            <div class="row mb-3 <?= empty($prefill['cc']) ? 'd-none' : ''; ?>" id="ccRow">
                <label class="col-sm-1 col-form-label text-muted"><?= __('cc'); ?></label>
                <div class="col-sm-11">
                    <input type="text" name="cc" class="form-control"
                           value="<?= htmlspecialchars($prefill['cc']); ?>"
                           placeholder="cc@example.com">
                </div>
            </div>

            <!-- BCC -->
            <div class="row mb-3 d-none" id="bccRow">
                <label class="col-sm-1 col-form-label text-muted"><?= __('bcc'); ?></label>
                <div class="col-sm-11">
                    <input type="text" name="bcc" class="form-control"
                           value="<?= htmlspecialchars($prefill['bcc']); ?>"
                           placeholder="bcc@example.com">
                </div>
            </div>

            <!-- Subject -->
            <div class="row">
                <label class="col-sm-1 col-form-label text-muted"><?= __('subject'); ?></label>
                <div class="col-sm-11">
                    <input type="text" name="subject" class="form-control"
                           value="<?= htmlspecialchars($prefill['subject']); ?>"
                           placeholder="<?= __('subject_placeholder'); ?>">
                </div>
            </div>
        </div>

        <!-- Editor Toolbar -->
        <div class="card-body border-bottom py-2">
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('bold')" title="<?= __('bold'); ?>"><i class="ri-bold"></i></button>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('italic')" title="<?= __('italic'); ?>"><i class="ri-italic"></i></button>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('underline')" title="<?= __('underline'); ?>"><i class="ri-underline"></i></button>
                <div class="vr mx-1"></div>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('insertUnorderedList')" title="<?= __('bullet_list'); ?>"><i class="ri-list-unordered"></i></button>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('insertOrderedList')" title="<?= __('number_list'); ?>"><i class="ri-list-ordered"></i></button>
                <div class="vr mx-1"></div>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="insertLink()" title="<?= __('insert_link'); ?>"><i class="ri-link"></i></button>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="insertImage()" title="<?= __('insert_image'); ?>"><i class="ri-image-line"></i></button>
                <div class="vr mx-1"></div>
                <button type="button" class="btn btn-soft-secondary btn-sm" onclick="execCmd('removeFormat')" title="<?= __('clear_format'); ?>"><i class="ri-format-clear"></i></button>
            </div>
        </div>

        <!-- Editor Body -->
        <div class="card-body">
            <div id="emailBody" contenteditable="true"
                 style="min-height:350px;padding:20px;outline:none;font-size:14px;line-height:1.7;overflow-y:auto;"><?= $prefill['body']; ?></div>
            <input type="hidden" name="body_html" id="bodyHtmlInput">
        </div>

        <!-- Attachments -->
        <div class="card-body border-top">
            <div id="attachmentZone" class="border border-dashed rounded p-3 text-center" style="cursor:pointer;">
                <i class="ri-attachment-2 fs-22 text-muted"></i>
                <p class="text-muted mb-0 fs-13"><?= __('drop_files'); ?></p>
                <input type="file" name="attachments[]" id="attachmentInput" multiple style="display:none;">
            </div>
            <div id="attachmentList" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>

        <!-- Bottom Toolbar -->
        <div class="card-footer">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" id="btnSend">
                    <i class="ri-send-plane-fill me-1"></i> <?= __('send'); ?>
                </button>
                <button type="button" class="btn btn-soft-secondary" id="btnSaveDraft">
                    <i class="ri-save-line me-1"></i> <?= __('save_draft'); ?>
                </button>
                <a href="<?= base_url('inbox'); ?>" class="btn btn-soft-danger">
                    <i class="ri-delete-bin-line me-1"></i> <?= __('discard'); ?>
                </a>

                <div class="ms-auto">
                    <select name="priority" class="form-select form-select-sm" style="width:150px;">
                        <option value="normal"><?= __('normal_priority'); ?></option>
                        <option value="high"><?= __('high_priority'); ?></option>
                        <option value="low"><?= __('low_priority'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.autocomplete-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--vz-card-bg, #fff);
    border: 1px solid var(--vz-border-color);
    border-radius: var(--vz-border-radius);
    box-shadow: var(--vz-box-shadow);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
}
.autocomplete-dropdown .ac-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    justify-content: space-between;
}
.autocomplete-dropdown .ac-item:hover { background: var(--vz-tertiary-bg); }
.autocomplete-dropdown .ac-item .ac-name { font-weight: 500; }
.autocomplete-dropdown .ac-item .ac-email { color: var(--vz-secondary-color); }
#emailBody:empty:before { content: '<?= __("compose_hint"); ?>'; color: var(--vz-secondary-color); }
#attachmentZone.dragover { border-color: var(--vz-primary) !important; background: rgba(var(--vz-primary-rgb), 0.03); }
.attachment-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: var(--vz-tertiary-bg);
    border-radius: var(--vz-border-radius);
    font-size: 12px;
}
.attachment-item .remove-attachment { color: var(--vz-danger); cursor: pointer; font-size: 14px; }
</style>

<script>
var contacts = <?= $contactsJson; ?>;
var selectedFiles = [];

// CC/BCC toggle
$('#toggleCc').on('click', function() { $('#ccRow').toggleClass('d-none'); });
$('#toggleBcc').on('click', function() { $('#bccRow').toggleClass('d-none'); });

// Rich text editor commands
function execCmd(cmd, val) {
    document.execCommand(cmd, false, val || null);
    document.getElementById('emailBody').focus();
}
function insertLink() { var url = prompt('Enter URL:'); if (url) execCmd('createLink', url); }
function insertImage() { var url = prompt('Enter image URL:'); if (url) execCmd('insertImage', url); }

// To field autocomplete
$('#toField').on('input', function() {
    var val = $(this).val().toLowerCase();
    var parts = val.split(',');
    var current = parts[parts.length - 1].trim();
    if (current.length < 1) { $('#toAutocomplete').hide(); return; }
    var matches = contacts.filter(function(c) {
        return (c.name && c.name.toLowerCase().indexOf(current) !== -1) ||
               c.email.toLowerCase().indexOf(current) !== -1;
    }).slice(0, 8);
    if (matches.length === 0) { $('#toAutocomplete').hide(); return; }
    var html = '';
    matches.forEach(function(c) {
        html += '<div class="ac-item" data-email="' + c.email + '">';
        html += '<span class="ac-name">' + (c.name || c.email) + '</span>';
        html += '<span class="ac-email">' + c.email + '</span>';
        html += '</div>';
    });
    $('#toAutocomplete').html(html).show();
});

$(document).on('click', '#toAutocomplete .ac-item', function() {
    var email = $(this).data('email');
    var $input = $('#toField');
    var val = $input.val();
    var parts = val.split(',');
    parts[parts.length - 1] = ' ' + email;
    $input.val(parts.join(',').replace(/^,\s*/, '') + ', ');
    $('#toAutocomplete').hide();
    $input.focus();
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('#toField, #toAutocomplete').length) {
        $('#toAutocomplete').hide();
    }
});

// Attachment handling
$('#attachmentZone').on('click', function() { $('#attachmentInput').click(); });
$('#attachmentZone').on('dragover', function(e) { e.preventDefault(); $(this).addClass('dragover'); })
    .on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('dragover'); });
$('#attachmentZone').on('drop', function(e) { e.preventDefault(); addFiles(e.originalEvent.dataTransfer.files); });
$('#attachmentInput').on('change', function() { addFiles(this.files); });

function addFiles(files) {
    for (var i = 0; i < files.length; i++) {
        selectedFiles.push(files[i]);
        var idx = selectedFiles.length - 1;
        var size = (files[i].size / 1024).toFixed(1) + ' KB';
        if (files[i].size > 1024 * 1024) size = (files[i].size / 1024 / 1024).toFixed(1) + ' MB';
        $('#attachmentList').append(
            '<div class="attachment-item" data-idx="' + idx + '">' +
            '<i class="ri-file-line"></i>' +
            '<span>' + files[i].name + ' (' + size + ')</span>' +
            '<i class="ri-close-line remove-attachment" onclick="removeFile(' + idx + ')"></i>' +
            '</div>'
        );
    }
}
function removeFile(idx) { selectedFiles[idx] = null; $('[data-idx="' + idx + '"]').remove(); }

// Send email
$('#btnSend').on('click', function() { submitCompose('send'); });
$('#btnSaveDraft').on('click', function() { submitCompose('draft'); });

function submitCompose(action) {
    $('#bodyHtmlInput').val($('#emailBody').html());
    var formData = new FormData($('#composeForm')[0]);
    formData.append('action', action);
    selectedFiles.forEach(function(f) { if (f) formData.append('attachments[]', f); });

    var $btn = action === 'send' ? $('#btnSend') : $('#btnSaveDraft');
    var origText = $btn.html();
    $btn.html('<i class="ri-loader-4-line ri-spin me-1"></i> ' + (action === 'send' ? '<?= __("sending"); ?>' : '<?= __("saving"); ?>')).prop('disabled', true);

    $.ajax({
        url: '<?= base_url("ajaxs/user/compose.php"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || (action === 'send' ? '<?= __("email_sent"); ?>' : '<?= __("draft_saved"); ?>'));
                setTimeout(function() {
                    window.location.href = '<?= base_url("inbox"); ?>?folder=' + (action === 'send' ? 'sent' : 'drafts');
                }, 1000);
            } else {
                tmToast('error', res.message || '<?= __("send_failed"); ?>');
                $btn.html(origText).prop('disabled', false);
            }
        },
        error: function() {
            tmToast('error', '<?= __("network_error"); ?>');
            $btn.html(origText).prop('disabled', false);
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
