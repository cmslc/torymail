<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('filters') . ' - ' . get_setting('site_name', 'Torymail'),
    'desc'  => __('email_filters'),
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Fetch filters
$filters = $ToryMail->get_list_safe("
    SELECT * FROM `email_filters`
    WHERE `user_id` = ?
    ORDER BY `priority_order` ASC, `created_at` DESC
", [$getUser['id']]);

// Fetch labels for action dropdown
$userLabels = $ToryMail->get_list_safe("
    SELECT `id`, `name`, `color` FROM `email_labels`
    WHERE `user_id` = ?
    ORDER BY `name` ASC
", [$getUser['id']]);

$conditionTypes = [
    'from_contains'    => __('from_contains'),
    'from_equals'      => __('from_equals'),
    'to_contains'      => __('to_contains'),
    'to_equals'        => __('to_equals'),
    'subject_contains' => __('subject_contains'),
    'subject_equals'   => __('subject_equals'),
    'body_contains'    => __('body_contains'),
    'has_attachment'    => __('has_attachment'),
];

$actionTypes = [
    'move_to_folder' => __('move_to_folder_action'),
    'add_label'      => __('add_label'),
    'mark_read'      => __('mark_as_read'),
    'mark_starred'   => __('mark_as_starred'),
    'forward_to'     => __('forward_to'),
    'delete'         => __('delete'),
];
?>

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= __('filters'); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= __('filters'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ri-filter-line me-1 align-bottom text-primary"></i> <?= __('email_filters'); ?>
                <span class="badge bg-primary-subtle text-primary ms-1"><?= count($filters); ?></span>
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal" onclick="resetFilterForm()">
                <i class="ri-add-line me-1"></i> <?= __('add_filter'); ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($filters)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="ri-filter-line"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted"><?= __('no_filters'); ?></h5>
            <p class="text-muted fs-13"><?= __('no_filters_hint'); ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th><?= __('name'); ?></th>
                        <th><?= __('condition'); ?></th>
                        <th><?= __('action'); ?></th>
                        <th class="text-center"><?= __('active'); ?></th>
                        <th style="width:120px;"><?= __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filters as $i => $filter): ?>
                    <?php
                    $filterConditions = json_decode($filter['conditions'] ?? '[]', true) ?: [];
                    $filterActions = json_decode($filter['actions'] ?? '[]', true) ?: [];
                    $firstCond = $filterConditions[0] ?? [];
                    $firstAct = $filterActions[0] ?? [];
                    $conditionLabel = $conditionTypes[$firstCond['type'] ?? ''] ?? ($firstCond['type'] ?? '-');
                    $actionLabel = $actionTypes[$firstAct['type'] ?? ''] ?? ($firstAct['type'] ?? '-');
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1; ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($filter['name'] ?? 'Filter #' . ($i + 1)); ?></td>
                        <td>
                            <span class="text-muted"><?= $conditionLabel; ?></span>
                            <?php if (($firstCond['type'] ?? '') !== 'has_attachment' && !empty($firstCond['value'])): ?>
                            <code class="fs-12"><?= htmlspecialchars(str_truncate($firstCond['value'] ?? '', 40)); ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary"><?= $actionLabel; ?></span>
                            <?php if (!empty($firstAct['value'])): ?>
                            <span class="text-muted fs-12">: <?= htmlspecialchars(str_truncate($firstAct['value'], 30)); ?></span>
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
                                <button class="btn btn-soft-primary btn-sm" onclick="editFilter(<?= htmlspecialchars(json_encode($filter)); ?>)" title="<?= __('edit'); ?>">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-soft-danger btn-sm" onclick="deleteFilter(<?= $filter['id']; ?>)" title="<?= __('delete'); ?>">
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
                <h5 class="modal-title" id="filterModalTitle"><?= __('add_filter'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <input type="hidden" name="filter_id" id="filterId">

                    <div class="mb-3">
                        <label class="form-label"><?= __('filter_name'); ?></label>
                        <input type="text" name="name" id="filterName" class="form-control" placeholder="<?= __('filter_name_placeholder'); ?>">
                    </div>

                    <h6 class="fw-semibold text-muted mb-2 fs-13 text-uppercase"><?= __('condition'); ?></h6>
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label"><?= __('when'); ?></label>
                                <select name="condition_type" id="filterCondType" class="form-select">
                                    <?php foreach ($conditionTypes as $key => $label): ?>
                                    <option value="<?= $key; ?>"><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-0" id="condValueGroup">
                                <label class="form-label"><?= __('value'); ?></label>
                                <input type="text" name="condition_value" id="filterCondValue" class="form-control" placeholder="e.g., newsletter@example.com">
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold text-muted mb-2 fs-13 text-uppercase"><?= __('action'); ?></h6>
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label"><?= __('then'); ?></label>
                                <select name="action_type" id="filterActType" class="form-select">
                                    <?php foreach ($actionTypes as $key => $label): ?>
                                    <option value="<?= $key; ?>"><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-0" id="actValueGroup">
                                <label class="form-label" id="actValueLabel"><?= __('folder'); ?></label>
                                <select name="action_value" id="filterActValue" class="form-select">
                                    <option value="inbox"><?= __('inbox'); ?></option>
                                    <option value="archive"><?= __('archive'); ?></option>
                                    <option value="spam"><?= __('spam'); ?></option>
                                    <option value="trash"><?= __('trash'); ?></option>
                                </select>
                                <input type="text" name="action_value_text" id="filterActValueText" class="form-control d-none" placeholder="">
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label"><?= __('priority_order'); ?></label>
                        <input type="number" name="priority_order" id="filterPriority" class="form-control" value="10" min="0" max="999" style="width:120px;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><?= __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="saveFilterBtn"><?= __('save_filter'); ?></button>
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
        $select.html('<option value="inbox">' + <?= json_encode(__("inbox")); ?> + '<\/option><option value="archive">' + <?= json_encode(__("archive")); ?> + '<\/option><option value="spam">' + <?= json_encode(__("spam")); ?> + '<\/option><option value="trash">' + <?= json_encode(__("trash")); ?> + '<\/option>').removeClass('d-none');
        $text.addClass('d-none');
        $('#actValueLabel').text(<?= json_encode(__("folder")); ?>);
        $('#actValueGroup').show();
    } else if (act === 'add_label') {
        var opts = '';
        labelsData.forEach(function(l) {
            opts += '<option value="' + l.id + '">' + l.name + '</option>';
        });
        $select.html(opts || '<option value="">' + <?= json_encode(__("no_labels")); ?> + '<\/option>').removeClass('d-none');
        $text.addClass('d-none');
        $('#actValueLabel').text(<?= json_encode(__("label")); ?>);
        $('#actValueGroup').show();
    } else if (act === 'forward_to') {
        $select.addClass('d-none');
        $text.removeClass('d-none').attr('placeholder', <?= json_encode(__("forward_to")); ?>);
        $('#actValueLabel').text(<?= json_encode(__("email")); ?>);
        $('#actValueGroup').show();
    } else {
        $('#actValueGroup').hide();
    }
});

function resetFilterForm() {
    $('#filterModalTitle').text(<?= json_encode(__("add_filter")); ?>);
    $('#filterId').val('');
    $('#filterForm')[0].reset();
    $('#condValueGroup').show();
    $('#filterActType').trigger('change');
}

function editFilter(filter) {
    $('#filterModalTitle').text(<?= json_encode(__("edit_filter")); ?>);
    $('#filterId').val(filter.id);
    $('#filterName').val(filter.name || '');
    $('#filterPriority').val(filter.priority_order || 0);

    var conditions = [];
    var actions = [];
    try { conditions = JSON.parse(filter.conditions || '[]'); } catch(e) {}
    try { actions = JSON.parse(filter.actions || '[]'); } catch(e) {}

    var firstCond = conditions[0] || {};
    var firstAct = actions[0] || {};

    $('#filterCondType').val(firstCond.type || 'from_contains').trigger('change');
    $('#filterCondValue').val(firstCond.value || '');
    $('#filterActType').val(firstAct.type || 'move_to_folder').trigger('change');

    setTimeout(function() {
        if (firstAct.type === 'forward_to') {
            $('#filterActValueText').val(firstAct.value || '');
        } else {
            $('#filterActValue').val(firstAct.value || '');
        }
    }, 50);

    new bootstrap.Modal(document.getElementById('filterModal')).show();
}

$('#saveFilterBtn').on('click', function() {
    var condType = $('#filterCondType').val();
    var condValue = $('#filterCondValue').val();
    var actType = $('#filterActType').val();
    var actValue = '';

    if (actType === 'forward_to') {
        actValue = $('#filterActValueText').val();
    } else if (['mark_read','mark_starred','delete'].indexOf(actType) !== -1) {
        actValue = '';
    } else {
        actValue = $('#filterActValue').val();
    }

    var data = {};
    data.action = $('#filterId').val() ? 'edit' : 'add';
    data.filter_id = $('#filterId').val();
    data.name = $('#filterName').val();
    data.conditions = JSON.stringify([{type: condType, value: condValue}]);
    data.actions = JSON.stringify([{type: actType, value: actValue}]);

    $.ajax({
        url: '<?= base_url("ajaxs/user/filters.php"); ?>?action=' + data.action,
        method: 'POST', data: data, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__("filter_saved")); ?>);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || <?= json_encode(__("filter_save_fail")); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__("filter_save_fail")); ?>;
            tmToast('error', msg);
        }
    });
});

function toggleFilter(id, active) {
    $.ajax({
        url: '<?= base_url("ajaxs/user/filters.php"); ?>?action=toggle',
        method: 'POST', data: { filter_id: id, is_active: active ? 1 : 0 }, dataType: 'json',
        success: function(res) {
            if (!res.success) {
                tmToast('error', res.message || <?= json_encode(__("filter_save_fail")); ?>);
                location.reload();
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__("filter_save_fail")); ?>;
            tmToast('error', msg);
            location.reload();
        }
    });
}

function deleteFilter(id) {
    tmConfirm(<?= json_encode(__("delete_filter")); ?>, <?= json_encode(__("delete_filter_desc")); ?>, function() {
        $.ajax({
            url: '<?= base_url("ajaxs/user/filters.php"); ?>?action=delete',
            method: 'POST', data: { filter_id: id }, dataType: 'json',
            success: function(res) {
                if (res.success) {
                    tmToast('success', <?= json_encode(__("filter_deleted")); ?>);
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    tmToast('error', res.message || <?= json_encode(__("filter_delete_fail")); ?>);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__("filter_delete_fail")); ?>;
                tmToast('error', msg);
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
