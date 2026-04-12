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

$folderNames = [
    'inbox'   => __('inbox'),
    'starred' => __('starred'),
    'sent'    => __('sent'),
    'drafts'  => __('drafts'),
    'spam'    => __('spam'),
    'trash'   => __('trash'),
    'archive' => __('archive'),
];

$body = [
    'title' => ($folderNames[$folder] ?? ucfirst($folder)) . ' - ' . get_setting('site_name', 'Torymail'),
    'desc'  => get_setting('site_name', 'Torymail') . ' email management',
];
$body['header'] = '';
$body['footer'] = '';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

// Check if user has any mailboxes — show setup prompt if not
$hasMailboxes = $ToryMail->get_value_safe(
    "SELECT COUNT(*) FROM mailboxes WHERE user_id = ?",
    [$getUser['id']]
);
if (!$hasMailboxes && empty($_SESSION['mailbox_id'])): ?>
<div class="alert alert-primary alert-dismissible fade show d-flex align-items-center gap-3 mb-3" role="alert">
    <div class="avatar-sm flex-shrink-0">
        <div class="avatar-title bg-primary rounded-circle fs-20"><i class="ri-mail-add-line"></i></div>
    </div>
    <div class="flex-grow-1">
        <h6 class="mb-1"><?= __('welcome_setup_title'); ?></h6>
        <p class="mb-0 small"><?= __('welcome_setup_desc'); ?></p>
    </div>
    <a href="<?= base_url('mailboxes'); ?>" class="btn btn-primary btn-sm flex-shrink-0">
        <i class="ri-add-line me-1"></i><?= __('welcome_setup_btn'); ?>
    </a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif;

// Valid folders
$validFolders = ['inbox', 'starred', 'sent', 'drafts', 'spam', 'trash', 'archive'];
if (!in_array($folder, $validFolders)) {
    $folder = 'inbox';
}

// Fetch user's mailboxes for selector
$isMailboxLogin = !empty($_SESSION['mailbox_id']);
if ($isMailboxLogin) {
    $userMailboxes = $ToryMail->get_list_safe("
        SELECT `id`, `email_address`, `display_name` FROM `mailboxes`
        WHERE `id` = ? AND `status` = 'active'
    ", [$_SESSION['mailbox_id']]);
} else {
    $userMailboxes = $ToryMail->get_list_safe("
        SELECT `id`, `email_address`, `display_name` FROM `mailboxes`
        WHERE `user_id` = ? AND `status` = 'active'
        ORDER BY `email_address` ASC
    ", [$getUser['id']]);
}

// Build query — scope to single mailbox if mailbox login
if ($isMailboxLogin) {
    $where = ["e.`mailbox_id` = ?"];
    $params = [$_SESSION['mailbox_id']];
} else {
    $where = ["e.`mailbox_id` IN (SELECT id FROM mailboxes WHERE user_id = ?)"];
    $params = [$getUser['id']];
}

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
    $where[] = "(e.`subject` LIKE ? OR e.`from_name` LIKE ? OR e.`from_address` LIKE ? OR e.`body_text` LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($labelFilter) {
    $where[] = "EXISTS (SELECT 1 FROM `email_label_map` el WHERE el.`email_id` = e.`id` AND el.`label_id` = ?)";
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
           (SELECT COUNT(*) FROM `email_attachments` a WHERE a.`email_id` = e.`id`) as attachment_count
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

<!-- Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= $folderNames[$folder] ?? ucfirst($folder); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('inbox'); ?>"><?= __('home'); ?></a></li>
                    <li class="breadcrumb-item active"><?= $folderNames[$folder] ?? ucfirst($folder); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0">
                <i class="<?= $folderIcons[$folder] ?? 'ri-inbox-line'; ?> me-1 align-bottom"></i>
                <?= $folderNames[$folder] ?? ucfirst($folder); ?>
            </h5>
            <div class="d-flex align-items-center gap-2">
                <?php if (count($userMailboxes) > 1): ?>
                <select id="mailboxSelector" class="form-select form-select-sm" style="width:220px;">
                    <option value=""><?= __('all_mailboxes_filter'); ?></option>
                    <?php foreach ($userMailboxes as $mb): ?>
                    <option value="<?= $mb['id']; ?>" <?= $mailboxFilter == $mb['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($mb['email_address']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Folder Tabs -->
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
            <?php foreach ($validFolders as $f): ?>
            <li class="nav-item">
                <a class="nav-link <?= $folder === $f ? 'active' : ''; ?>"
                   href="<?= base_url('inbox?folder=' . $f . ($mailboxFilter ? '&mailbox=' . $mailboxFilter : '')); ?>">
                    <i class="<?= $folderIcons[$f]; ?> me-1"></i>
                    <?= $folderNames[$f] ?? ucfirst($f); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Toolbar -->
    <div class="card-header border-bottom-dashed py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="form-check fs-15">
                <input class="form-check-input" type="checkbox" id="selectAll">
            </div>
            <button class="btn btn-soft-secondary btn-sm" onclick="refreshInbox()" title="<?= __('refresh'); ?>">
                <i class="ri-refresh-line"></i>
            </button>
            <button class="btn btn-soft-secondary btn-sm" onclick="bulkAction('read')" title="<?= __('mark_read'); ?>">
                <i class="ri-mail-open-line"></i>
            </button>
            <button class="btn btn-soft-secondary btn-sm" onclick="bulkAction('unread')" title="<?= __('mark_unread'); ?>">
                <i class="ri-mail-unread-line"></i>
            </button>
            <button class="btn btn-soft-secondary btn-sm" onclick="bulkAction('archive')" title="<?= __('archive'); ?>">
                <i class="ri-archive-line"></i>
            </button>
            <button class="btn btn-soft-danger btn-sm" onclick="bulkAction('delete')" title="<?= __('delete'); ?>">
                <i class="ri-delete-bin-line"></i>
            </button>

            <!-- Move dropdown -->
            <div class="dropdown">
                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ri-folder-transfer-line me-1"></i> <?= __('move'); ?>
                </button>
                <ul class="dropdown-menu">
                    <?php foreach (['inbox', 'archive', 'spam', 'trash'] as $mf): ?>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('move','<?= $mf; ?>');return false;">
                        <i class="<?= $folderIcons[$mf]; ?> me-2 align-bottom"></i> <?= $folderNames[$mf] ?? ucfirst($mf); ?>
                    </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if ($search): ?>
                <span class="badge bg-primary-subtle text-primary">
                    <?= __('search'); ?>: "<?= htmlspecialchars($search); ?>"
                    <a href="<?= base_url('inbox?folder=' . $folder); ?>" class="text-danger ms-1"><i class="ri-close-line"></i></a>
                </span>
                <?php endif; ?>
                <span class="text-muted fs-13">
                    <?= ($offset + 1); ?>-<?= min($offset + $perPage, $totalEmails); ?> / <?= $totalEmails; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($emails)): ?>
        <div class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                    <i class="<?= $folderIcons[$folder] ?? 'ri-inbox-line'; ?>"></i>
                </div>
            </div>
            <h5 class="fs-16 text-muted"><?= __('no_emails'); ?></h5>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <tbody>
                    <?php foreach ($emails as $email): ?>
                    <tr class="<?= !$email['is_read'] ? 'fw-semibold' : ''; ?>" style="cursor:pointer;">
                        <td style="width:40px;" onclick="event.stopPropagation();">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input email-check" value="<?= $email['id']; ?>">
                            </div>
                        </td>
                        <td style="width:40px;" onclick="event.stopPropagation();">
                            <span class="email-star <?= $email['is_starred'] ? 'text-warning' : 'text-muted'; ?>" style="cursor:pointer;font-size:18px;"
                                  onclick="toggleStar(<?= $email['id']; ?>, this);">
                                <i class="<?= $email['is_starred'] ? 'ri-star-fill' : 'ri-star-line'; ?>"></i>
                            </span>
                        </td>
                        <td style="width:180px;" onclick="window.location='<?= base_url('read/' . $email['id']); ?>'">
                            <span class="text-truncate d-inline-block" style="max-width:170px;">
                                <?= htmlspecialchars(decode_mime($email['from_name'] ?: $email['from_address'])); ?>
                            </span>
                        </td>
                        <td onclick="window.location='<?= base_url('read/' . $email['id']); ?>'">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-truncate d-inline-block <?= !$email['is_read'] ? 'text-body' : 'text-muted'; ?>" style="max-width:350px;">
                                    <?= htmlspecialchars(decode_mime($email['subject'] ?: __('no_subject'))); ?>
                                </span>
                                <span class="text-muted fw-normal text-truncate d-none d-lg-inline-block" style="max-width:250px;">
                                    - <?= htmlspecialchars(str_truncate($email['body_text'] ?? '', 80)); ?>
                                </span>
                            </div>
                        </td>
                        <td style="width:30px;" onclick="window.location='<?= base_url('read/' . $email['id']); ?>'">
                            <?php if ($email['attachment_count'] > 0): ?>
                            <i class="ri-attachment-2 text-muted fs-16" title="<?= $email['attachment_count']; ?> <?= __('attachments'); ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td style="width:80px;" onclick="window.location='<?= base_url('read/' . $email['id']); ?>'">
                            <span class="text-muted fs-12"><?= format_date($email['created_at'], 'M j'); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-center">
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
    $.ajax({
        url: '<?= base_url("ajaxs/user/email_action.php"); ?>',
        method: 'POST', data: { action: 'toggle_star', email_id: emailId }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                var $el = $(el);
                $el.toggleClass('text-warning text-muted');
                $el.find('i').toggleClass('ri-star-line ri-star-fill');
            }
        }
    });
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
        tmToast('warning', <?= json_encode(__("select_emails_warning")); ?>);
        return;
    }

    if (action === 'delete') {
        tmConfirm(<?= json_encode(__("delete_emails_confirm")); ?>, <?= json_encode(__("delete_emails_desc")); ?>, function() {
            doBulkAction(action, ids, target);
        });
        return;
    }

    doBulkAction(action, ids, target);
}

function doBulkAction(action, ids, target) {
    $.ajax({
        url: '<?= base_url("ajaxs/user/email_action.php"); ?>',
        method: 'POST', data: { action: 'bulk_' + action, email_ids: ids, target: target || '' }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                tmToast('success', res.message || <?= json_encode(__("done")); ?>);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                tmToast('error', res.message || <?= json_encode(__("error_occurred")); ?>);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : <?= json_encode(__("error_occurred")); ?>;
            tmToast('error', msg);
        }
    });
}

// Refresh
function refreshInbox() {
    location.reload();
}

// Auto-check for new emails every 10 seconds
(function() {
    var lastId = <?= !empty($emails) ? intval($emails[0]['id']) : 0; ?>;
    var folder = '<?= $folder; ?>';
    var checking = false;

    setInterval(function() {
        if (checking) return;
        checking = true;
        $.get('<?= base_url("ajaxs/user/email_action.php"); ?>', {
            action: 'check_new',
            folder: folder,
            last_id: lastId
        }, function(res) {
            checking = false;
            if (res.success && res.new_count > 0) {
                location.reload();
            }
        }, 'json').fail(function() { checking = false; });
    }, 10000);
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
