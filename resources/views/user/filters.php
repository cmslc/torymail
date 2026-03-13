<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Filters - Torymail',
    'desc'  => 'Email filter rules',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch filters
$filters = $ToryMail->get_list_safe("
    SELECT * FROM `email_filters`
    WHERE `user_id` = ?
    ORDER BY `priority` ASC, `created_at` DESC
", [$getUser['id']]);

// Fetch labels for action dropdown
$userLabels = $ToryMail->get_list_safe("
    SELECT `id`, `name`, `color` FROM `labels`
    WHERE `user_id` = ?
    ORDER BY `name` ASC
", [$getUser['id']]);

$conditionTypes = [
    'from_contains'    => 'From contains',
    'from_equals'      => 'From equals',
    'to_contains'      => 'To contains',
    'to_equals'        => 'To equals',
    'subject_contains' => 'Subject contains',
    'subject_equals'   => 'Subject equals',
    'body_contains'    => 'Body contains',
    'has_attachment'    => 'Has attachment',
];

$actionTypes = [
    'move_to_folder' => 'Move to folder',
    'add_label'      => 'Add label',
    'mark_read'      => 'Mark as read',
    'mark_starred'   => 'Mark as starred',
    'forward_to'     => 'Forward to',
    'delete'         => 'Delete',
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Filters</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>">Home</a></li>
                    <li class="breadcrumb-item active">Filters</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-filter-line me-1 align-bottom text-primary"></i> Email Filters
                <span class="badge bg-primary-subtle text-primary ms-1"><?= count($filters); ?></span>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal" onclick="resetFilterForm()">
                <i class="ri-add-line me-1"></i> Add Filter
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($filters)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-filter-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted">No filters configured</h5>
            <p class="text-muted fs-13">Create filters to automatically organize incoming emails.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Name</th>
                        <th>Condition</th>
                        <th>Action</th>
                        <th class="text-center">Active</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filters as $i => $filter): ?>
                    <?php
                    $conditionLabel = $conditionTypes[$filter['condition_type']] ?? $filter['condition_type'];
                    $actionLabel = $actionTypes[$filter['action_type']] ?? $filter['action_type'];
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1; ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($filter['name'] ?? 'Filter #' . ($i + 1)); ?></td>
                        <td>
                            <span class="text-muted"><?= $conditionLabel; ?></span>
                            <?php if ($filter['condition_type'] !== 'has_attachment'): ?>
                            <code class="fs-12"><?= htmlspecialchars(str_truncate($filter['condition_value'] ?? '', 40)); ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary"><?= $actionLabel; ?></span>
                            <?php if (!empty($filter['action_value'])): ?>
                            <span class="text-muted fs-12">: <?= htmlspecialchars(str_truncate($filter['action_value'], 30)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block mb-0">
                                <input class="form-check-input" type="checkbox" <?= ($filter['is_active'] ?? 1) ? 'checked' : ''; ?>
                                       onchange="toggleFilter(<?= $filter['id']; ?>, this.checked)">
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-soft-primary btn-sm" onclick="editFilter(<?= htmlspecialchars(json_encode($filter)); ?>)" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteFilter(<?= $filter['id']; ?>)" title="Delete">
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

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalTitle">Add Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <input type="hidden" name="filter_id" id="filterId">

                    <div class="mb-3">
                        <label class="form-label">Filter Name</label>
                        <input type="text" name="name" id="filterName" class="form-control" placeholder="e.g., Move newsletters">
                    </div>

                    <h6 class="fw-semibold text-muted mb-2 fs-13 text-uppercase">Condition</h6>
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">When</label>
                                <select name="condition_type" id="filterCondType" class="form-select">
                                    <?php foreach ($conditionTypes as $key => $label): ?>
                                    <option value="<?= $key; ?>"><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-0" id="condValueGroup">
                                <label class="form-label">Value</label>
                                <input type="text" name="condition_value" id="filterCondValue" class="form-control" placeholder="e.g., newsletter@example.com">
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold text-muted mb-2 fs-13 text-uppercase">Action</h6>
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">Then</label>
                                <select name="action_type" id="filterActType" class="form-select">
                                    <?php foreach ($actionTypes as $key => $label): ?>
                                    <option value="<?= $key; ?>"><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-0" id="actValueGroup">
                                <label class="form-label" id="actValueLabel">Folder</label>
                                <select name="action_value" id="filterActValue" class="form-select">
                                    <option value="inbox">Inbox</option>
                                    <option value="archive">Archive</option>
                                    <option value="spam">Spam</option>
                                    <option value="trash">Trash</option>
                                </select>
                                <input type="text" name="action_value_text" id="filterActValueText" class="form-control d-none" placeholder="Enter value">
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Priority (lower = runs first)</label>
                        <input type="number" name="priority" id="filterPriority" class="form-control" value="10" min="1" max="999" style="width:120px;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveFilterBtn">Save Filter</button>
            </div>
        </div>
    </div>
</div>

<script>
var labelsData = <?= json_encode($userLabels); ?>;

$('#filterCondType').on('change', function() {
    if ($(this).val() === 'has_attachment') {
        $('#condValueGroup').hide();
    } else {
        $('#condValueGroup').show();
    }
});

$('#filterActType').on('change', function() {
    var act = $(this).val();
    var $select = $('#filterActValue');
    var $text = $('#filterActValueText');

    if (act === 'move_to_folder') {
        $select.html('<option value="inbox">Inbox</option><option value="archive">Archive</option><option value="spam">Spam</option><option value="trash">Trash</option>').removeClass('d-none');
        $text.addClass('d-none');
        $('#actValueLabel').text('Folder');
        $('#actValueGroup').show();
    } else if (act === 'add_label') {
        var opts = '';
        labelsData.forEach(function(l) {
            opts += '<option value="' + l.id + '">' + l.name + '</option>';
        });
        $select.html(opts || '<option value="">No labels available</option>').removeClass('d-none');
        $text.addClass('d-none');
        $('#actValueLabel').text('Label');
        $('#actValueGroup').show();
    } else if (act === 'forward_to') {
        $select.addClass('d-none');
        $text.removeClass('d-none').attr('placeholder', 'Forward to email address');
        $('#actValueLabel').text('Email');
        $('#actValueGroup').show();
    } else {
        $('#actValueGroup').hide();
    }
});

function resetFilterForm() {
    $('#filterModalTitle').text('Add Filter');
    $('#filterId').val('');
    $('#filterForm')[0].reset();
    $('#condValueGroup').show();
    $('#filterActType').trigger('change');
}

function editFilter(filter) {
    $('#filterModalTitle').text('Edit Filter');
    $('#filterId').val(filter.id);
    $('#filterName').val(filter.name || '');
    $('#filterCondType').val(filter.condition_type).trigger('change');
    $('#filterCondValue').val(filter.condition_value || '');
    $('#filterActType').val(filter.action_type).trigger('change');
    $('#filterPriority').val(filter.priority || 10);

    setTimeout(function() {
        if (filter.action_type === 'forward_to') {
            $('#filterActValueText').val(filter.action_value || '');
        } else {
            $('#filterActValue').val(filter.action_value || '');
        }
    }, 50);

    new bootstrap.Modal(document.getElementById('filterModal')).show();
}

$('#saveFilterBtn').on('click', function() {
    var data = {};
    data.action = $('#filterId').val() ? 'update' : 'create';
    data.filter_id = $('#filterId').val();
    data.name = $('#filterName').val();
    data.condition_type = $('#filterCondType').val();
    data.condition_value = $('#filterCondValue').val();
    data.action_type = $('#filterActType').val();
    data.priority = $('#filterPriority').val();

    if (data.action_type === 'forward_to') {
        data.action_value = $('#filterActValueText').val();
    } else if (['mark_read','mark_starred','delete'].indexOf(data.action_type) !== -1) {
        data.action_value = '';
    } else {
        data.action_value = $('#filterActValue').val();
    }

    $.post('<?= base_url("ajaxs/user/filters.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Filter saved!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to save filter.');
        }
    }, 'json');
});

function toggleFilter(id, active) {
    $.post('<?= base_url("ajaxs/user/filters.php"); ?>', {
        action: 'toggle',
        filter_id: id,
        is_active: active ? 1 : 0
    }, function(res) {
        if (!res.success) {
            tmToast('error', res.message || 'Failed to update filter.');
            location.reload();
        }
    }, 'json');
}

function deleteFilter(id) {
    tmConfirm('Delete this filter?', 'This action cannot be undone.', function() {
        $.post('<?= base_url("ajaxs/user/filters.php"); ?>', {
            action: 'delete',
            filter_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Filter deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete filter.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
