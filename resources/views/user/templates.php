<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('templates') . ' - ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('email_templates'),
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

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('templates'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('templates'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-file-copy-line me-1 align-bottom text-primary"></i> <?= __('email_templates'); ?>
                <span class="badge bg-primary-subtle text-primary ms-1"><?= count($templates); ?></span>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="resetTemplateForm()">
                <i class="ri-add-line me-1"></i> <?= __('add_template'); ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($templates)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-file-copy-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted"><?= __('no_templates'); ?></h5>
            <p class="text-muted fs-13"><?= __('no_templates_hint'); ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= __('template_name'); ?></th>
                        <th><?= __('subject'); ?></th>
                        <th><?= __('last_updated'); ?></th>
                        <th style="width:140px;"><?= __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $tpl): ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($tpl['name']); ?></td>
                        <td class="text-muted"><?= htmlspecialchars(str_truncate($tpl['subject'] ?? '', 60)); ?></td>
                        <td class="text-muted fs-12"><?= time_ago($tpl['updated_at']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= base_url('compose?template=' . $tpl['id']); ?>" class="btn btn-soft-success btn-sm" title="<?= __('send'); ?>">
                                    <i class="ri-mail-send-line"></i>
                                </a>
                                <button class="btn btn-soft-primary btn-sm" onclick="editTemplate(<?= htmlspecialchars(json_encode($tpl)); ?>)" title="<?= __('edit'); ?>">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteTemplate(<?= $tpl['id']; ?>)" title="<?= __('delete'); ?>">
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
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle"><?= __('add_template'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="template_id" id="tplId">
                    <div class="mb-3">
                        <label class="form-label"><?= __('template_name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="tplName" class="form-control" placeholder="<?= __('template_name_placeholder'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('subject'); ?></label>
                        <input type="text" name="subject" id="tplSubject" class="form-control" placeholder="<?= __('subject_placeholder_tpl'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('body_html'); ?></label>
                        <div class="border rounded-top px-2 py-1 bg-light d-flex gap-1">
                            <button type="button" class="btn btn-soft-secondary btn-sm" onclick="tplExecCmd('bold')"><i class="ri-bold"></i></button>
                            <button type="button" class="btn btn-soft-secondary btn-sm" onclick="tplExecCmd('italic')"><i class="ri-italic"></i></button>
                            <button type="button" class="btn btn-soft-secondary btn-sm" onclick="tplExecCmd('underline')"><i class="ri-underline"></i></button>
                            <button type="button" class="btn btn-soft-secondary btn-sm" onclick="tplExecCmd('insertUnorderedList')"><i class="ri-list-unordered"></i></button>
                        </div>
                        <div id="tplBody" contenteditable="true"
                             class="form-control rounded-top-0"
                             style="min-height:250px;font-size:14px;line-height:1.7;"></div>
                        <input type="hidden" name="body_html" id="tplBodyInput">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><?= __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="saveTemplateBtn"><?= __('save_template'); ?></button>
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
    $('#templateModalTitle').text(<?= json_encode(__('add_template')); ?>);
    $('#tplId').val('');
    $('#tplName').val('');
    $('#tplSubject').val('');
    $('#tplBody').html('');
}

function editTemplate(tpl) {
    $('#templateModalTitle').text(<?= json_encode(__('edit_template')); ?>);
    $('#tplId').val(tpl.id);
    $('#tplName').val(tpl.name || '');
    $('#tplSubject').val(tpl.subject || '');
    $('#tplBody').html(tpl.body_html || '');
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

$('#saveTemplateBtn').on('click', function() {
    $('#tplBodyInput').val($('#tplBody').html());
    var data = $('#templateForm').serialize();
    var isEdit = !!$('#tplId').val();
    var act = isEdit ? 'edit' : 'add';

    $.ajax({
        url: '<?= base_url("ajaxs/user/templates.php"); ?>?action=' + act,
        method: 'POST', data: data, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__('template_saved')); ?>);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || <?= json_encode(__('template_save_fail')); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('template_save_fail')); ?>;
            tmToast('error', msg);
        }
    });
});

function deleteTemplate(id) {
    tmConfirm(<?= json_encode(__('delete_template')); ?>, <?= json_encode(__('delete_template_desc')); ?>, function() {
        $.ajax({
            url: '<?= base_url("ajaxs/user/templates.php"); ?>?action=delete',
            method: 'POST', data: { template_id: id }, dataType: 'json',
            success: function(res) {
                if (res.success) {
                    tmToast('success', <?= json_encode(__('template_deleted')); ?>);
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    tmToast('error', res.message || <?= json_encode(__('template_delete_fail')); ?>);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__('template_delete_fail')); ?>;
                tmToast('error', msg);
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
