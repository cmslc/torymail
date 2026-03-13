<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Templates - Torymail',
    'desc'  => 'Email templates',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch templates
$templates = $ToryMail->get_list_safe("
    SELECT * FROM `email_templates`
    WHERE `user_id` = ?
    ORDER BY `updated_at` DESC
", [$getUser['id']]);
?>

<div class="tm-card">
    <div class="tm-card-header">
        <h5 class="mb-0 fw-semibold" style="font-size:18px;">
            <i class="ri-file-copy-line me-2 text-primary"></i> Email Templates
            <span class="badge bg-light text-muted ms-2" style="font-size:12px;"><?= count($templates); ?></span>
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="resetTemplateForm()">
            <i class="ri-add-line me-1"></i> Add Template
        </button>
    </div>

    <?php if (empty($templates)): ?>
    <div class="text-center py-5">
        <i class="ri-file-copy-line" style="font-size:48px;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-1">No templates yet</p>
        <p class="text-muted" style="font-size:13px;">Create reusable email templates to save time composing emails.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:14px;">
            <thead class="table-light">
                <tr>
                    <th>Template Name</th>
                    <th>Subject</th>
                    <th>Last Updated</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($tpl['name']); ?></td>
                    <td class="text-muted"><?= htmlspecialchars(str_truncate($tpl['subject'] ?? '', 60)); ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= time_ago($tpl['updated_at']); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= base_url('compose?template=' . $tpl['id']); ?>" class="btn btn-sm btn-light" title="Use template">
                                <i class="ri-mail-send-line"></i>
                            </a>
                            <button class="btn btn-sm btn-light" onclick="editTemplate(<?= htmlspecialchars(json_encode($tpl)); ?>)" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteTemplate(<?= $tpl['id']; ?>)" title="Delete">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle">Add Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="template_id" id="tplId">
                    <div class="mb-3">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="tplName" class="form-control" placeholder="e.g., Welcome Email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" id="tplSubject" class="form-control" placeholder="Email subject line">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Body (HTML)</label>
                        <!-- Mini toolbar -->
                        <div class="border rounded-top px-2 py-1 bg-light d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-light" onclick="tplExecCmd('bold')"><i class="ri-bold"></i></button>
                            <button type="button" class="btn btn-sm btn-light" onclick="tplExecCmd('italic')"><i class="ri-italic"></i></button>
                            <button type="button" class="btn btn-sm btn-light" onclick="tplExecCmd('underline')"><i class="ri-underline"></i></button>
                            <button type="button" class="btn btn-sm btn-light" onclick="tplExecCmd('insertUnorderedList')"><i class="ri-list-unordered"></i></button>
                        </div>
                        <div id="tplBody" contenteditable="true"
                             class="form-control rounded-top-0"
                             style="min-height:250px;font-size:14px;line-height:1.7;"></div>
                        <input type="hidden" name="body" id="tplBodyInput">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTemplateBtn">Save Template</button>
            </div>
        </div>
    </div>
</div>

<script>
function tplExecCmd(cmd) {
    document.execCommand(cmd, false, null);
    document.getElementById('tplBody').focus();
}

function resetTemplateForm() {
    $('#templateModalTitle').text('Add Template');
    $('#tplId').val('');
    $('#tplName').val('');
    $('#tplSubject').val('');
    $('#tplBody').html('');
}

function editTemplate(tpl) {
    $('#templateModalTitle').text('Edit Template');
    $('#tplId').val(tpl.id);
    $('#tplName').val(tpl.name || '');
    $('#tplSubject').val(tpl.subject || '');
    $('#tplBody').html(tpl.body || '');
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

$('#saveTemplateBtn').on('click', function() {
    $('#tplBodyInput').val($('#tplBody').html());
    var data = $('#templateForm').serialize();
    var isEdit = !!$('#tplId').val();
    data += '&action=' + (isEdit ? 'update' : 'create');

    $.post('<?= base_url("ajaxs/user/templates.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Template saved!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to save template.');
        }
    }, 'json');
});

function deleteTemplate(id) {
    tmConfirm('Delete this template?', 'This action cannot be undone.', function() {
        $.post('<?= base_url("ajaxs/user/templates.php"); ?>', {
            action: 'delete',
            template_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Template deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete template.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
