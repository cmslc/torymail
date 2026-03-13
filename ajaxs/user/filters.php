<?php
session_start();

// Load environment
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System not configured']);
    exit;
}
$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
    putenv(trim($key) . '=' . trim($value));
}

require_once __DIR__ . '/../../libs/db.php';
require_once __DIR__ . '/../../libs/helper.php';

$ToryMail = new DB();

$settings = [];
$settingsRows = $ToryMail->get_list_safe("SELECT * FROM settings", []);
if ($settingsRows) {
    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Auth check
$token = $_SESSION['user_login'] ?? $_COOKIE['torymail_token'] ?? null;
if (!$token) error_response('Authentication required', 401);

$getUser = $ToryMail->get_row_safe(
    "SELECT * FROM users WHERE token = ? AND status = 'active'",
    [$token]
);
if (!$getUser) error_response('Authentication required', 401);

$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']) : '';

switch ($action) {

    // -------------------------------------------------------
    // LIST
    // -------------------------------------------------------
    case 'list':
        $filters = $ToryMail->get_list_safe(
            "SELECT * FROM email_filters WHERE user_id = ? ORDER BY priority_order ASC, created_at ASC",
            [$getUser['id']]
        );

        success_response('OK', ['filters' => $filters]);
        break;

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $name       = sanitize($_POST['name'] ?? '');
        $conditions = $_POST['conditions'] ?? '';
        $actions    = $_POST['actions'] ?? '';
        $is_active  = intval($_POST['is_active'] ?? 1) ? 1 : 0;

        if (empty($name)) error_response('Filter name is required');
        if (empty($conditions) || empty($actions)) error_response('Conditions and actions are required');

        // Validate JSON
        $conditionsDecoded = json_decode($conditions, true);
        $actionsDecoded    = json_decode($actions, true);
        if ($conditionsDecoded === null) error_response('Invalid conditions JSON');
        if ($actionsDecoded === null) error_response('Invalid actions JSON');

        // Get next priority order
        $maxOrder = $ToryMail->get_value_safe(
            "SELECT MAX(priority_order) FROM email_filters WHERE user_id = ?",
            [$getUser['id']]
        );
        $nextOrder = ($maxOrder !== null) ? $maxOrder + 1 : 0;

        $filterId = $ToryMail->insert_safe('email_filters', [
            'user_id'        => $getUser['id'],
            'name'           => $name,
            'conditions'     => $conditions,
            'actions'        => $actions,
            'is_active'      => $is_active,
            'priority_order' => $nextOrder,
            'created_at'     => gettime(),
        ]);

        if (!$filterId) error_response('Failed to create filter');
        success_response('Filter created', ['filter_id' => $filterId]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $filter_id = intval($_POST['filter_id'] ?? 0);
        if ($filter_id <= 0) error_response('Invalid filter ID');

        $filter = $ToryMail->get_row_safe(
            "SELECT * FROM email_filters WHERE id = ? AND user_id = ?",
            [$filter_id, $getUser['id']]
        );
        if (!$filter) error_response('Filter not found or access denied', 403);

        $updateData = [];

        if (isset($_POST['name'])) {
            $name = sanitize($_POST['name']);
            if (empty($name)) error_response('Filter name is required');
            $updateData['name'] = $name;
        }
        if (isset($_POST['conditions'])) {
            $conditionsDecoded = json_decode($_POST['conditions'], true);
            if ($conditionsDecoded === null) error_response('Invalid conditions JSON');
            $updateData['conditions'] = $_POST['conditions'];
        }
        if (isset($_POST['actions'])) {
            $actionsDecoded = json_decode($_POST['actions'], true);
            if ($actionsDecoded === null) error_response('Invalid actions JSON');
            $updateData['actions'] = $_POST['actions'];
        }
        if (isset($_POST['is_active'])) {
            $updateData['is_active'] = intval($_POST['is_active']) ? 1 : 0;
        }

        if (empty($updateData)) error_response('No changes provided');

        $ToryMail->update_safe('email_filters', $updateData, 'id = ?', [$filter_id]);
        success_response('Filter updated');
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $filter_id = intval($_POST['filter_id'] ?? 0);
        if ($filter_id <= 0) error_response('Invalid filter ID');

        $filter = $ToryMail->get_row_safe(
            "SELECT * FROM email_filters WHERE id = ? AND user_id = ?",
            [$filter_id, $getUser['id']]
        );
        if (!$filter) error_response('Filter not found or access denied', 403);

        $ToryMail->remove_safe('email_filters', 'id = ?', [$filter_id]);
        success_response('Filter deleted');
        break;

    // -------------------------------------------------------
    // TOGGLE
    // -------------------------------------------------------
    case 'toggle':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $filter_id = intval($_POST['filter_id'] ?? 0);
        if ($filter_id <= 0) error_response('Invalid filter ID');

        $filter = $ToryMail->get_row_safe(
            "SELECT * FROM email_filters WHERE id = ? AND user_id = ?",
            [$filter_id, $getUser['id']]
        );
        if (!$filter) error_response('Filter not found or access denied', 403);

        $newVal = $filter['is_active'] ? 0 : 1;
        $ToryMail->update_safe('email_filters', ['is_active' => $newVal], 'id = ?', [$filter_id]);

        success_response($newVal ? 'Filter activated' : 'Filter deactivated', ['is_active' => $newVal]);
        break;

    // -------------------------------------------------------
    // REORDER
    // -------------------------------------------------------
    case 'reorder':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $order = $_POST['order'] ?? '';
        if (empty($order)) error_response('Order data is required');

        // Expect JSON array of filter IDs in desired order
        $orderArray = json_decode($order, true);
        if (!is_array($orderArray)) error_response('Invalid order data');

        foreach ($orderArray as $priority => $filterId) {
            $filterId = intval($filterId);
            // Verify ownership
            $filter = $ToryMail->get_row_safe(
                "SELECT id FROM email_filters WHERE id = ? AND user_id = ?",
                [$filterId, $getUser['id']]
            );
            if ($filter) {
                $ToryMail->update_safe('email_filters', ['priority_order' => $priority], 'id = ?', [$filterId]);
            }
        }

        success_response('Filters reordered');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
