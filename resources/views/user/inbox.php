<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$folder = sanitize($_GET['folder'] ?? 'inbox');
$mailboxFilter = sanitize($_GET['mailbox'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$labelFilter = sanitize($_GET['label'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$body = [
    'title' => ucfirst($folder) . ' - Torymail',
    'desc'  => 'Torymail email management',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Valid folders
$validFolders = ['inbox', 'starred', 'sent', 'drafts', 'spam', 'trash', 'archive'];
if (!in_array($folder, $validFolders)) {
    $folder = 'inbox';
}

// Fetch user's mailboxes for selector
$userMailboxes = $ToryMail->get_list_safe("
    SELECT `id`, `email`, `display_name` FROM `mailboxes`
    WHERE `user_id` = ? AND `status` = 'active'
    ORDER BY `email` ASC
", [$getUser['id']]);

// Build query
$where = ["e.`user_id` = ?"];
$params = [$getUser['id']];

if ($folder === 'starred') {
    $where[] = "e.`is_starred` = 1";
    $where[] = "e.`folder` NOT IN ('trash')";
} else {
    $where[] = "e.`folder` = ?";
    $params[] = $folder;
}

if ($mailboxFilter) {
    $where[] = "e.`mailbox_id` = ?";
    $params[] = $mailboxFilter;
}

if ($search) {
    $where[] = "(e.`subject` LIKE ? OR e.`from_name` LIKE ? OR e.`from_email` LIKE ? OR e.`body_text` LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($labelFilter) {
    $where[] = "EXISTS (SELECT 1 FROM `email_labels` el WHERE el.`email_id` = e.`id` AND el.`label_id` = ?)";
    $params[] = $labelFilter;
}

$whereClause = implode(' AND ', $where);

// Count total
$totalRow = $ToryMail->get_row_safe("SELECT COUNT(*) as total FROM `emails` e WHERE {$whereClause}", $params);
$totalEmails = $totalRow['total'] ?? 0;
$totalPages = max(1, ceil($totalEmails / $perPage));

// Fetch emails
$emails = $ToryMail->get_list_safe("
    SELECT e.*,
           (SELECT COUNT(*) FROM `attachments` a WHERE a.`email_id` = e.`id`) as attachment_count
    FROM `emails` e
    WHERE {$whereClause}
    ORDER BY e.`created_at` DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Folder icons
$folderIcons = [
    'inbox'   => 'ri-inbox-line',
    'starred' => 'ri-star-line',
    'sent'    => 'ri-send-plane-line',
    'drafts'  => 'ri-draft-line',
    'spam'    => 'ri-spam-2-line',
    'trash'   => 'ri-delete-bin-line',
    'archive' => 'ri-archive-line',
];
?>

<div class="tm-card">
    <!-- Mailbox selector (if multiple) -->
    <?php if (count($userMailboxes) > 1): ?>
    <div class="px-3 pt-3 pb-0">
        <div class="d-flex align-items-center gap-2">
            <label class="text-muted" style="font-size:13px;white-space:nowrap;">Mailbox:</label>
            <select id="mailboxSelector" class="form-select form-select-sm" style="max-width:280px;border-radius:8px;">
                <option value="">All Mailboxes</option>
                <?php foreach ($userMailboxes as $mb): ?>
                <option value="<?= $mb['id']; ?>" <?= $mailboxFilter == $mb['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($mb['email']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Folder Tabs -->
    <div class="tm-folder-tabs">
        <?php foreach ($validFolders as $f): ?>
        <a href="<?= base_url('inbox?folder=' . $f . ($mailboxFilter ? '&mailbox=' . $mailboxFilter : '')); ?>"
           class="<?= $folder === $f ? 'active' : ''; ?>">
            <i class="<?= $folderIcons[$f]; ?> me-1"></i>
            <?= ucfirst($f); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <div class="tm-toolbar">
        <div class="form-check" style="margin-right:4px;">
            <input class="form-check-input" type="checkbox" id="selectAll">
        </div>
        <button class="btn-toolbar" onclick="refreshInbox()" title="Refresh">
            <i class="ri-refresh-line"></i>
        </button>
        <button class="btn-toolbar" onclick="bulkAction('read')" title="Mark as read">
            <i class="ri-mail-open-line"></i>
        </button>
        <button class="btn-toolbar" onclick="bulkAction('unread')" title="Mark as unread">
            <i class="ri-mail-unread-line"></i>
        </button>
        <button class="btn-toolbar" onclick="bulkAction('archive')" title="Archive">
            <i class="ri-archive-line"></i>
        </button>
        <button class="btn-toolbar" onclick="bulkAction('delete')" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>

        <!-- Move to folder dropdown -->
        <div class="dropdown">
            <button class="btn-toolbar dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-folder-transfer-line"></i>
                <span class="d-none d-sm-inline">Move</span>
            </button>
            <ul class="dropdown-menu">
                <?php foreach (['inbox', 'archive', 'spam', 'trash'] as $mf): ?>
                <li><a class="dropdown-item" href="#" onclick="bulkAction('move','<?= $mf; ?>');return false;">
                    <i class="<?= $folderIcons[$mf]; ?> me-2"></i> <?= ucfirst($mf); ?>
                </a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if ($search): ?>
            <span class="badge bg-light text-dark border" style="font-size:12px;">
                Search: "<?= htmlspecialchars($search); ?>"
                <a href="<?= base_url('inbox?folder=' . $folder); ?>" class="text-danger ms-1"><i class="ri-close-line"></i></a>
            </span>
            <?php endif; ?>
            <span style="font-size:12px;color:#9ca3af;">
                <?= ($offset + 1); ?>-<?= min($offset + $perPage, $totalEmails); ?> of <?= $totalEmails; ?>
            </span>
        </div>
    </div>

    <!-- Email List -->
    <div class="tm-email-list">
        <?php if (empty($emails)): ?>
        <div class="text-center py-5">
            <i class="ri-inbox-line" style="font-size:48px;color:#d1d5db;"></i>
            <p class="text-muted mt-3 mb-0">No emails in <?= $folder; ?></p>
        </div>
        <?php else: ?>
        <?php foreach ($emails as $email): ?>
        <div class="tm-email-row <?= !$email['is_read'] ? 'unread' : ''; ?>" data-id="<?= $email['id']; ?>">
            <div class="email-checkbox" onclick="event.stopPropagation();">
                <input type="checkbox" class="form-check-input email-check" value="<?= $email['id']; ?>">
            </div>
            <div class="email-star <?= $email['is_starred'] ? 'starred' : ''; ?>"
                 onclick="event.stopPropagation();toggleStar(<?= $email['id']; ?>, this);"
                 title="<?= $email['is_starred'] ? 'Unstar' : 'Star'; ?>">
                <i class="<?= $email['is_starred'] ? 'ri-star-fill' : 'ri-star-line'; ?>"></i>
            </div>
            <a href="<?= base_url('read/' . $email['id']); ?>" class="d-flex align-items-center flex-fill text-decoration-none" style="gap:12px;min-width:0;color:inherit;">
                <div class="email-from"><?= htmlspecialchars($email['from_name'] ?: $email['from_email']); ?></div>
                <div class="email-content">
                    <span class="email-subject"><?= htmlspecialchars($email['subject'] ?: '(No subject)'); ?></span>
                    <span class="email-preview">- <?= htmlspecialchars(str_truncate($email['body_text'] ?? '', 80)); ?></span>
                </div>
                <div class="email-meta">
                    <?php if ($email['attachment_count'] > 0): ?>
                    <span class="email-attachment" title="<?= $email['attachment_count']; ?> attachment(s)">
                        <i class="ri-attachment-2"></i>
                    </span>
                    <?php endif; ?>
                    <span class="email-date"><?= format_date($email['created_at'], 'M j'); ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3">
        <?php
        $paginationBase = base_url('inbox') . '?folder=' . $folder
            . ($mailboxFilter ? '&mailbox=' . $mailboxFilter : '')
            . ($search ? '&search=' . urlencode($search) : '')
            . ($labelFilter ? '&label=' . $labelFilter : '')
            . '&page=';
        ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= $paginationBase . ($page - 1); ?>">&laquo;</a>
                </li>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= $paginationBase . $p; ?>"><?= $p; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= $paginationBase . ($page + 1); ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
$('#selectAll').on('change', function() {
    $('.email-check').prop('checked', $(this).is(':checked'));
});

// Mailbox selector
$('#mailboxSelector').on('change', function() {
    var mb = $(this).val();
    var url = '<?= base_url("inbox"); ?>?folder=<?= $folder; ?>';
    if (mb) url += '&mailbox=' + mb;
    window.location.href = url;
});

// Toggle star
function toggleStar(emailId, el) {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: 'toggle_star',
        email_id: emailId
    }, function(res) {
        if (res.success) {
            var $el = $(el);
            $el.toggleClass('starred');
            $el.find('i').toggleClass('ri-star-line ri-star-fill');
        }
    }, 'json');
}

// Get selected email IDs
function getSelectedIds() {
    var ids = [];
    $('.email-check:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}

// Bulk action
function bulkAction(action, target) {
    var ids = getSelectedIds();
    if (ids.length === 0) {
        tmToast('warning', 'Please select at least one email.');
        return;
    }

    if (action === 'delete') {
        tmConfirm('Delete emails?', 'Selected emails will be moved to trash.', function() {
            doBulkAction(action, ids, target);
        });
        return;
    }

    doBulkAction(action, ids, target);
}

function doBulkAction(action, ids, target) {
    $.post('<?= base_url("ajaxs/user/email_action.php"); ?>', {
        action: 'bulk_' + action,
        email_ids: ids,
        target: target || ''
    }, function(res) {
        if (res.success) {
            tmToast('success', res.message || 'Done!');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            tmToast('error', res.message || 'An error occurred.');
        }
    }, 'json');
}

// Refresh
function refreshInbox() {
    location.reload();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
