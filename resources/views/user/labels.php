<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Labels - Torymail',
    'desc'  => 'Manage email labels',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch labels with email counts
$labels = $ToryMail->get_list_safe("
    SELECT l.*,
           (SELECT COUNT(*) FROM `email_labels` el WHERE el.`label_id` = l.`id`) as email_count
    FROM `labels` l
    WHERE l.`user_id` = ?
    ORDER BY l.`name` ASC
", [$getUser['id']]);

$defaultColors = ['#4F46E5', '#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#6366F1'];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Labels</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>">Home</a></li>
                    <li class="breadcrumb-item active">Labels</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-price-tag-3-line me-1 align-bottom text-primary"></i> Labels
                <span class="badge bg-primary-subtle text-primary ms-1"><?= count($labels); ?></span>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#labelModal" onclick="resetLabelForm()">
                <i class="ri-add-line me-1"></i> Add Label
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($labels)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-price-tag-3-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted">No labels yet</h5>
            <p class="text-muted fs-13">Create labels to organize your emails by category.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($labels as $label): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border mb-0">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-xs flex-shrink-0">
                                <div class="avatar-title rounded-circle" style="background:<?= htmlspecialchars($label['color']); ?>;">
                                    <i class="ri-price-tag-3-line text-white fs-14"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-medium"><?= htmlspecialchars($label['name']); ?></h6>
                                <span class="text-muted fs-12"><?= $label['email_count']; ?> email<?= $label['email_count'] != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-soft-primary btn-sm" onclick="editLabel(<?= htmlspecialchars(json_encode($label)); ?>)" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-soft-danger btn-sm" onclick="deleteLabel(<?= $label['id']; ?>, '<?= htmlspecialchars($label['name']); ?>')" title="Delete">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Label Modal -->
<div class="modal fade" id="labelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelModalTitle">Add Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="labelForm">
                    <input type="hidden" name="label_id" id="lblId">
                    <div class="mb-3">
                        <label class="form-label">Label Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="lblName" class="form-control" placeholder="e.g., Important" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($defaultColors as $color): ?>
                            <div class="color-option" data-color="<?= $color; ?>"
                                 style="width:28px;height:28px;border-radius:6px;background:<?= $color; ?>;cursor:pointer;border:2px solid transparent;transition:border-color 0.2s;"
                                 onclick="selectColor('<?= $color; ?>')">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="color" name="color" id="lblColor" class="form-control form-control-color" value="#4F46E5" style="width:50px;height:34px;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLabelBtn">Save Label</button>
            </div>
        </div>
    </div>
</div>

<script>
function selectColor(color) {
    $('#lblColor').val(color);
    $('.color-option').css('border-color', 'transparent');
    $('[data-color="' + color + '"]').css('border-color', '#333');
}

function resetLabelForm() {
    $('#labelModalTitle').text('Add Label');
    $('#lblId').val('');
    $('#labelForm')[0].reset();
    $('#lblColor').val('#4F46E5');
    $('.color-option').css('border-color', 'transparent');
    $('[data-color="#4F46E5"]').css('border-color', '#333');
}

function editLabel(label) {
    $('#labelModalTitle').text('Edit Label');
    $('#lblId').val(label.id);
    $('#lblName').val(label.name || '');
    $('#lblColor').val(label.color || '#4F46E5');
    $('.color-option').css('border-color', 'transparent');
    $('[data-color="' + (label.color || '#4F46E5') + '"]').css('border-color', '#333');
    new bootstrap.Modal(document.getElementById('labelModal')).show();
}

$('#saveLabelBtn').on('click', function() {
    var data = $('#labelForm').serialize();
    var isEdit = !!$('#lblId').val();
    data += '&action=' + (isEdit ? 'update' : 'create');

    $.post('<?= base_url("ajaxs/user/labels.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Label saved!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to save label.');
        }
    }, 'json');
});

function deleteLabel(id, name) {
    tmConfirm('Delete label "' + name + '"?', 'The label will be removed from all emails.', function() {
        $.post('<?= base_url("ajaxs/user/labels.php"); ?>', {
            action: 'delete',
            label_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Label deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete label.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
