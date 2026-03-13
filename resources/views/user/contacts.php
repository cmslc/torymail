<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => 'Contacts - Torymail',
    'desc'  => 'Manage your contacts',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

$search = sanitize($_GET['search'] ?? '');
$group = sanitize($_GET['group'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["`user_id` = ?"];
$params = [$getUser['id']];

if ($search) {
    $where[] = "(`name` LIKE ? OR `email` LIKE ? OR `company` LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($group) {
    $where[] = "`group_name` = ?";
    $params[] = $group;
}

$whereClause = implode(' AND ', $where);

$totalRow = $ToryMail->get_row_safe("SELECT COUNT(*) as total FROM `contacts` WHERE {$whereClause}", $params);
$totalContacts = $totalRow['total'] ?? 0;
$totalPages = max(1, ceil($totalContacts / $perPage));

$contacts = $ToryMail->get_list_safe("
    SELECT * FROM `contacts`
    WHERE {$whereClause}
    ORDER BY `name` ASC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Fetch groups for filter
$groups = $ToryMail->get_list_safe("
    SELECT DISTINCT `group_name` FROM `contacts`
    WHERE `user_id` = ? AND `group_name` IS NOT NULL AND `group_name` != ''
    ORDER BY `group_name` ASC
", [$getUser['id']]);
?>

<div class="tm-card">
    <div class="tm-card-header">
        <h5 class="mb-0 fw-semibold" style="font-size:18px;">
            <i class="ri-contacts-line me-2 text-primary"></i> Contacts
            <span class="badge bg-light text-muted ms-2" style="font-size:12px;"><?= $totalContacts; ?></span>
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal" onclick="resetContactForm()">
            <i class="ri-add-line me-1"></i> Add Contact
        </button>
    </div>

    <!-- Filters -->
    <div class="tm-toolbar">
        <form class="d-flex align-items-center gap-2 flex-fill flex-wrap" method="GET" action="<?= base_url('contacts'); ?>">
            <div class="position-relative flex-fill" style="max-width:300px;">
                <i class="ri-search-line position-absolute" style="left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search contacts..."
                       value="<?= htmlspecialchars($search); ?>" style="padding-left:32px;border-radius:6px;">
            </div>
            <?php if (!empty($groups)): ?>
            <select name="group" class="form-select form-select-sm" style="width:160px;border-radius:6px;">
                <option value="">All Groups</option>
                <?php foreach ($groups as $g): ?>
                <option value="<?= htmlspecialchars($g['group_name']); ?>" <?= $group === $g['group_name'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($g['group_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
    </div>

    <!-- Contact List -->
    <?php if (empty($contacts)): ?>
    <div class="text-center py-5">
        <i class="ri-contacts-line" style="font-size:48px;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-0">No contacts found</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:14px;">
            <thead class="table-light">
                <tr>
                    <th style="width:50px;"></th>
                    <th>Name</th>
                    <th>Email</th>
                    <th class="d-none d-md-table-cell">Company</th>
                    <th class="d-none d-md-table-cell">Group</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td>
                        <div class="tm-user-avatar" style="width:34px;height:34px;font-size:13px;">
                            <?= strtoupper(substr($contact['name'] ?: $contact['email'], 0, 1)); ?>
                        </div>
                    </td>
                    <td class="fw-medium"><?= htmlspecialchars($contact['name'] ?: '-'); ?></td>
                    <td><span class="text-muted"><?= htmlspecialchars($contact['email']); ?></span></td>
                    <td class="d-none d-md-table-cell text-muted"><?= htmlspecialchars($contact['company'] ?? '-'); ?></td>
                    <td class="d-none d-md-table-cell">
                        <?php if (!empty($contact['group_name'])): ?>
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($contact['group_name']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light" onclick="editContact(<?= htmlspecialchars(json_encode($contact)); ?>)" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteContact(<?= $contact['id']; ?>)" title="Delete">
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= base_url('contacts?page=' . ($page - 1) . ($search ? '&search=' . urlencode($search) : '') . ($group ? '&group=' . urlencode($group) : '')); ?>">&laquo;</a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= base_url('contacts?page=' . $p . ($search ? '&search=' . urlencode($search) : '') . ($group ? '&group=' . urlencode($group) : '')); ?>"><?= $p; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= base_url('contacts?page=' . ($page + 1) . ($search ? '&search=' . urlencode($search) : '') . ($group ? '&group=' . urlencode($group) : '')); ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalTitle">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm">
                    <input type="hidden" name="contact_id" id="contactId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="contactName" class="form-control" placeholder="Full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="contactEmail" class="form-control" placeholder="email@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" id="contactCompany" class="form-control" placeholder="Company name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Group</label>
                        <input type="text" name="group_name" id="contactGroup" class="form-control" placeholder="Group name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="contactPhone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="contactNotes" class="form-control" rows="2" placeholder="Notes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveContactBtn">Save Contact</button>
            </div>
        </div>
    </div>
</div>

<script>
function resetContactForm() {
    $('#contactModalTitle').text('Add Contact');
    $('#contactId').val('');
    $('#contactForm')[0].reset();
}

function editContact(contact) {
    $('#contactModalTitle').text('Edit Contact');
    $('#contactId').val(contact.id);
    $('#contactName').val(contact.name || '');
    $('#contactEmail').val(contact.email || '');
    $('#contactCompany').val(contact.company || '');
    $('#contactGroup').val(contact.group_name || '');
    $('#contactPhone').val(contact.phone || '');
    $('#contactNotes').val(contact.notes || '');
    new bootstrap.Modal(document.getElementById('contactModal')).show();
}

$('#saveContactBtn').on('click', function() {
    var data = $('#contactForm').serialize();
    var isEdit = !!$('#contactId').val();
    data += '&action=' + (isEdit ? 'update' : 'create');

    $.post('<?= base_url("ajaxs/user/contacts.php"); ?>', data, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Contact saved!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'Failed to save contact.');
        }
    }, 'json');
});

function deleteContact(id) {
    tmConfirm('Delete contact?', 'This action cannot be undone.', function() {
        $.post('<?= base_url("ajaxs/user/contacts.php"); ?>', {
            action: 'delete',
            contact_id: id
        }, function(res) {
            if (res.success) {
                tmToast('success', 'Contact deleted.');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || 'Failed to delete contact.');
            }
        }, 'json');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
