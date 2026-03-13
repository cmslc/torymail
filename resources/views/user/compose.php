<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Compose - Torymail',
    'desc'  => 'Compose a new email',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch user's active mailboxes
$userMailboxes = $ToryMail->get_list_safe("
    SELECT m.`id`, m.`email`, m.`display_name`, m.`signature`
    FROM `mailboxes` m
    JOIN `domains` d ON m.`domain_id` = d.`id`
    WHERE m.`user_id` = ? AND m.`status` = 'active' AND d.`status` = 'verified'
    ORDER BY m.`email` ASC
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
    $original = $ToryMail->get_row_safe("SELECT * FROM `emails` WHERE `id` = ? AND `user_id` = ?", [$replyTo, $getUser['id']]);
    if ($original) {
        $prefill['to'] = $original['from_email'];
        $prefill['subject'] = 'Re: ' . preg_replace('/^Re:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>' . htmlspecialchars($original['from_name'] ?: $original['from_email']) . '</strong> wrote on ' . format_date($original['created_at']) . ':</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($replyAll) {
    $original = $ToryMail->get_row_safe("SELECT * FROM `emails` WHERE `id` = ? AND `user_id` = ?", [$replyAll, $getUser['id']]);
    if ($original) {
        $prefill['to'] = $original['from_email'];
        $prefill['cc'] = $original['cc'] ?? '';
        $prefill['subject'] = 'Re: ' . preg_replace('/^Re:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>' . htmlspecialchars($original['from_name'] ?: $original['from_email']) . '</strong> wrote on ' . format_date($original['created_at']) . ':</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($forward) {
    $original = $ToryMail->get_row_safe("SELECT * FROM `emails` WHERE `id` = ? AND `user_id` = ?", [$forward, $getUser['id']]);
    if ($original) {
        $prefill['subject'] = 'Fwd: ' . preg_replace('/^Fwd:\s*/i', '', $original['subject']);
        $prefill['body'] = '<br><br><div style="border-left:2px solid #ccc;padding-left:12px;margin-left:4px;color:#6b7280;">'
            . '<p><strong>---------- Forwarded message ----------</strong></p>'
            . '<p>From: ' . htmlspecialchars($original['from_name'] . ' <' . $original['from_email'] . '>') . '<br>'
            . 'Date: ' . format_date($original['created_at']) . '<br>'
            . 'Subject: ' . htmlspecialchars($original['subject']) . '</p>'
            . ($original['body_html'] ?: nl2br(htmlspecialchars($original['body_text'])))
            . '</div>';
        $prefill['from_mailbox'] = $original['mailbox_id'];
    }
}

if ($draftId) {
    $draft = $ToryMail->get_row_safe("SELECT * FROM `emails` WHERE `id` = ? AND `user_id` = ? AND `folder` = 'drafts'", [$draftId, $getUser['id']]);
    if ($draft) {
        $prefill['to'] = $draft['to_email'] ?? '';
        $prefill['cc'] = $draft['cc'] ?? '';
        $prefill['bcc'] = $draft['bcc'] ?? '';
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
?>

<div class="tm-card">
    <div class="tm-card-header">
        <h5 class="mb-0 fw-semibold" style="font-size:18px;">
            <i class="ri-edit-line me-2 text-primary"></i>
            <?php
            if ($replyTo || $replyAll) echo 'Reply';
            elseif ($forward) echo 'Forward';
            elseif ($draftId) echo 'Edit Draft';
            else echo 'New Email';
            ?>
        </h5>
        <a href="<?= base_url('inbox'); ?>" class="btn btn-sm btn-light">
            <i class="ri-close-line"></i> Discard
        </a>
    </div>

    <form id="composeForm" enctype="multipart/form-data">
        <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draftId); ?>">
        <input type="hidden" name="reply_to" value="<?= htmlspecialchars($replyTo ?: $replyAll); ?>">
        <input type="hidden" name="forward" value="<?= htmlspecialchars($forward); ?>">

        <div class="p-3 border-bottom">
            <!-- From -->
            <div class="d-flex align-items-center mb-2">
                <label class="text-muted me-3" style="min-width:50px;font-size:14px;">From</label>
                <select name="from_mailbox_id" class="form-select form-select-sm" style="max-width:350px;border-radius:6px;" required>
                    <?php foreach ($userMailboxes as $mb): ?>
                    <option value="<?= $mb['id']; ?>"
                            data-signature="<?= htmlspecialchars($mb['signature'] ?? ''); ?>"
                            <?= $prefill['from_mailbox'] == $mb['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars(($mb['display_name'] ? $mb['display_name'] . ' ' : '') . '<' . $mb['email'] . '>'); ?>
                    </option>
                    <?php endforeach; ?>
                    <?php if (empty($userMailboxes)): ?>
                    <option value="" disabled selected>No active mailboxes - please set up a mailbox first</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- To -->
            <div class="d-flex align-items-center mb-2">
                <label class="text-muted me-3" style="min-width:50px;font-size:14px;">To</label>
                <div class="flex-fill position-relative">
                    <input type="text" name="to" id="toField" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($prefill['to']); ?>"
                           placeholder="recipient@example.com" style="border-radius:6px;" required>
                    <div id="toAutocomplete" class="autocomplete-dropdown"></div>
                </div>
                <div class="ms-2 d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-light" id="toggleCc">Cc</button>
                    <button type="button" class="btn btn-sm btn-light" id="toggleBcc">Bcc</button>
                </div>
            </div>

            <!-- CC (hidden by default) -->
            <div class="d-flex align-items-center mb-2 <?= empty($prefill['cc']) ? 'd-none' : ''; ?>" id="ccRow">
                <label class="text-muted me-3" style="min-width:50px;font-size:14px;">Cc</label>
                <input type="text" name="cc" class="form-control form-control-sm flex-fill"
                       value="<?= htmlspecialchars($prefill['cc']); ?>"
                       placeholder="cc@example.com" style="border-radius:6px;">
            </div>

            <!-- BCC (hidden by default) -->
            <div class="d-flex align-items-center mb-2 d-none" id="bccRow">
                <label class="text-muted me-3" style="min-width:50px;font-size:14px;">Bcc</label>
                <input type="text" name="bcc" class="form-control form-control-sm flex-fill"
                       value="<?= htmlspecialchars($prefill['bcc']); ?>"
                       placeholder="bcc@example.com" style="border-radius:6px;">
            </div>

            <!-- Subject -->
            <div class="d-flex align-items-center">
                <label class="text-muted me-3" style="min-width:50px;font-size:14px;">Subject</label>
                <input type="text" name="subject" class="form-control form-control-sm flex-fill"
                       value="<?= htmlspecialchars($prefill['subject']); ?>"
                       placeholder="Email subject" style="border-radius:6px;">
            </div>
        </div>

        <!-- Editor Toolbar -->
        <div class="border-bottom px-3 py-2 d-flex flex-wrap gap-1" id="editorToolbar">
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('bold')" title="Bold"><i class="ri-bold"></i></button>
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('italic')" title="Italic"><i class="ri-italic"></i></button>
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('underline')" title="Underline"><i class="ri-underline"></i></button>
            <div class="vr mx-1"></div>
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('insertUnorderedList')" title="Bullet list"><i class="ri-list-unordered"></i></button>
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('insertOrderedList')" title="Numbered list"><i class="ri-list-ordered"></i></button>
            <div class="vr mx-1"></div>
            <button type="button" class="btn btn-sm btn-light" onclick="insertLink()" title="Insert link"><i class="ri-link"></i></button>
            <button type="button" class="btn btn-sm btn-light" onclick="insertImage()" title="Insert image"><i class="ri-image-line"></i></button>
            <div class="vr mx-1"></div>
            <button type="button" class="btn btn-sm btn-light" onclick="execCmd('removeFormat')" title="Clear formatting"><i class="ri-format-clear"></i></button>
        </div>

        <!-- Editor Body -->
        <div id="emailBody" contenteditable="true"
             style="min-height:350px;padding:20px;outline:none;font-size:14px;line-height:1.7;overflow-y:auto;"><?= $prefill['body']; ?></div>
        <input type="hidden" name="body_html" id="bodyHtmlInput">

        <!-- Attachments -->
        <div class="border-top px-3 py-3">
            <div id="attachmentZone" class="border border-dashed rounded-3 p-3 text-center"
                 style="cursor:pointer;border-color:#d1d5db!important;transition:border-color 0.2s;">
                <i class="ri-attachment-2 fs-22 text-muted"></i>
                <p class="text-muted mb-0" style="font-size:13px;">Drop files here or click to attach</p>
                <input type="file" name="attachments[]" id="attachmentInput" multiple style="display:none;">
            </div>
            <div id="attachmentList" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>

        <!-- Bottom Toolbar -->
        <div class="border-top px-3 py-3 d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btnSend">
                <i class="ri-send-plane-fill me-1"></i> Send
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnSaveDraft">
                <i class="ri-save-line me-1"></i> Save Draft
            </button>
            <a href="<?= base_url('inbox'); ?>" class="btn btn-light">
                <i class="ri-delete-bin-line me-1"></i> Discard
            </a>

            <div class="ms-auto">
                <select name="priority" class="form-select form-select-sm" style="width:140px;border-radius:6px;">
                    <option value="normal">Normal Priority</option>
                    <option value="high">High Priority</option>
                    <option value="low">Low Priority</option>
                </select>
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
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    transition: background 0.1s;
}

.autocomplete-dropdown .ac-item:hover {
    background: #f0f4ff;
}

.autocomplete-dropdown .ac-item .ac-name { font-weight: 500; }
.autocomplete-dropdown .ac-item .ac-email { color: #9ca3af; }

#emailBody:empty:before {
    content: 'Write your email here...';
    color: #9ca3af;
}

.border-dashed { border-style: dashed !important; }

#attachmentZone.dragover {
    border-color: var(--tm-primary) !important;
    background: rgba(79,70,229,0.03);
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #f3f4f6;
    border-radius: 6px;
    font-size: 12px;
}

.attachment-item .remove-attachment {
    color: #ef4444;
    cursor: pointer;
    font-size: 14px;
}
</style>

<script>
var contacts = <?= $contactsJson; ?>;
var selectedFiles = [];

// CC/BCC toggle
$('#toggleCc').on('click', function() {
    $('#ccRow').toggleClass('d-none');
});
$('#toggleBcc').on('click', function() {
    $('#bccRow').toggleClass('d-none');
});

// Rich text editor commands
function execCmd(cmd, val) {
    document.execCommand(cmd, false, val || null);
    document.getElementById('emailBody').focus();
}

function insertLink() {
    var url = prompt('Enter URL:');
    if (url) {
        execCmd('createLink', url);
    }
}

function insertImage() {
    var url = prompt('Enter image URL:');
    if (url) {
        execCmd('insertImage', url);
    }
}

// To field autocomplete
$('#toField').on('input', function() {
    var val = $(this).val().toLowerCase();
    var parts = val.split(',');
    var current = parts[parts.length - 1].trim();

    if (current.length < 1) {
        $('#toAutocomplete').hide();
        return;
    }

    var matches = contacts.filter(function(c) {
        return (c.name && c.name.toLowerCase().indexOf(current) !== -1) ||
               c.email.toLowerCase().indexOf(current) !== -1;
    }).slice(0, 8);

    if (matches.length === 0) {
        $('#toAutocomplete').hide();
        return;
    }

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
$('#attachmentZone').on('click', function() {
    $('#attachmentInput').click();
});

$('#attachmentZone').on('dragover', function(e) {
    e.preventDefault();
    $(this).addClass('dragover');
}).on('dragleave drop', function(e) {
    e.preventDefault();
    $(this).removeClass('dragover');
});

$('#attachmentZone').on('drop', function(e) {
    e.preventDefault();
    var files = e.originalEvent.dataTransfer.files;
    addFiles(files);
});

$('#attachmentInput').on('change', function() {
    addFiles(this.files);
});

function addFiles(files) {
    for (var i = 0; i < files.length; i++) {
        selectedFiles.push(files[i]);
        var idx = selectedFiles.length - 1;
        var size = (files[i].size / 1024).toFixed(1) + ' KB';
        if (files[i].size > 1024 * 1024) {
            size = (files[i].size / 1024 / 1024).toFixed(1) + ' MB';
        }
        $('#attachmentList').append(
            '<div class="attachment-item" data-idx="' + idx + '">' +
            '<i class="ri-file-line"></i>' +
            '<span>' + files[i].name + ' (' + size + ')</span>' +
            '<i class="ri-close-line remove-attachment" onclick="removeFile(' + idx + ')"></i>' +
            '</div>'
        );
    }
}

function removeFile(idx) {
    selectedFiles[idx] = null;
    $('[data-idx="' + idx + '"]').remove();
}

// Send email
$('#btnSend').on('click', function() {
    submitCompose('send');
});

// Save draft
$('#btnSaveDraft').on('click', function() {
    submitCompose('draft');
});

function submitCompose(action) {
    // Copy editor content to hidden input
    $('#bodyHtmlInput').val($('#emailBody').html());

    var formData = new FormData($('#composeForm')[0]);
    formData.append('action', action);

    // Add attachments
    selectedFiles.forEach(function(f, i) {
        if (f) formData.append('attachments[]', f);
    });

    var $btn = action === 'send' ? $('#btnSend') : $('#btnSaveDraft');
    var origText = $btn.html();
    $btn.html('<i class="ri-loader-4-line ri-spin me-1"></i> ' + (action === 'send' ? 'Sending...' : 'Saving...')).prop('disabled', true);

    $.ajax({
        url: '<?= base_url("ajaxs/user/compose.php"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || (action === 'send' ? 'Email sent!' : 'Draft saved!'));
                setTimeout(function() {
                    window.location.href = '<?= base_url("inbox"); ?>?folder=' + (action === 'send' ? 'sent' : 'drafts');
                }, 1000);
            } else {
                tmToast('error', res.message || 'Failed to ' + action + ' email.');
                $btn.html(origText).prop('disabled', false);
            }
        },
        error: function() {
            tmToast('error', 'A network error occurred.');
            $btn.html(origText).prop('disabled', false);
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
