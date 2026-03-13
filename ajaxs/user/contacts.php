<?php
require_once __DIR__ . "/../bootstrap.php";

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
        $search = trim($_GET['search'] ?? '');
        $group  = trim($_GET['group'] ?? '');

        $where = "user_id = ?";
        $params = [$getUser['id']];

        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR email LIKE ? OR company LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($group)) {
            $where .= " AND group_name = ?";
            $params[] = $group;
        }

        $contacts = $ToryMail->get_list_safe(
            "SELECT * FROM contacts WHERE $where ORDER BY is_favorite DESC, name ASC",
            $params
        );

        success_response('OK', ['contacts' => $contacts]);
        break;

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $email   = trim($_POST['email'] ?? '');
        $name    = sanitize($_POST['name'] ?? '');
        $company = sanitize($_POST['company'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $notes   = sanitize($_POST['notes'] ?? '');
        $group   = sanitize($_POST['group_name'] ?? '');

        if (empty($email)) error_response('Email is required');
        if (!validate_email($email)) error_response('Invalid email format');

        $contactId = $ToryMail->insert_safe('contacts', [
            'user_id'    => $getUser['id'],
            'email'      => $email,
            'name'       => $name,
            'company'    => $company ?: null,
            'phone'      => $phone ?: null,
            'notes'      => $notes ?: null,
            'group_name' => $group ?: null,
            'is_favorite'=> 0,
            'created_at' => gettime(),
            'updated_at' => gettime(),
        ]);

        if (!$contactId) error_response('Failed to add contact');
        success_response('Contact added', ['contact_id' => $contactId]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $contact_id = intval($_POST['contact_id'] ?? 0);
        if ($contact_id <= 0) error_response('Invalid contact ID');

        $contact = $ToryMail->get_row_safe(
            "SELECT * FROM contacts WHERE id = ? AND user_id = ?",
            [$contact_id, $getUser['id']]
        );
        if (!$contact) error_response('Contact not found or access denied', 403);

        $updateData = ['updated_at' => gettime()];

        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if (!validate_email($email)) error_response('Invalid email format');
            $updateData['email'] = $email;
        }
        if (isset($_POST['name'])) $updateData['name'] = sanitize($_POST['name']);
        if (isset($_POST['company'])) $updateData['company'] = sanitize($_POST['company']) ?: null;
        if (isset($_POST['phone'])) $updateData['phone'] = sanitize($_POST['phone']) ?: null;
        if (isset($_POST['notes'])) $updateData['notes'] = sanitize($_POST['notes']) ?: null;
        if (isset($_POST['group_name'])) $updateData['group_name'] = sanitize($_POST['group_name']) ?: null;

        $ToryMail->update_safe('contacts', $updateData, 'id = ?', [$contact_id]);
        success_response('Contact updated');
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $contact_id = intval($_POST['contact_id'] ?? 0);
        if ($contact_id <= 0) error_response('Invalid contact ID');

        $contact = $ToryMail->get_row_safe(
            "SELECT * FROM contacts WHERE id = ? AND user_id = ?",
            [$contact_id, $getUser['id']]
        );
        if (!$contact) error_response('Contact not found or access denied', 403);

        $ToryMail->remove_safe('contacts', 'id = ?', [$contact_id]);
        success_response('Contact deleted');
        break;

    // -------------------------------------------------------
    // TOGGLE FAVORITE
    // -------------------------------------------------------
    case 'toggle_favorite':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $contact_id = intval($_POST['contact_id'] ?? 0);
        if ($contact_id <= 0) error_response('Invalid contact ID');

        $contact = $ToryMail->get_row_safe(
            "SELECT * FROM contacts WHERE id = ? AND user_id = ?",
            [$contact_id, $getUser['id']]
        );
        if (!$contact) error_response('Contact not found or access denied', 403);

        $newVal = $contact['is_favorite'] ? 0 : 1;
        $ToryMail->update_safe('contacts', ['is_favorite' => $newVal, 'updated_at' => gettime()], 'id = ?', [$contact_id]);

        success_response($newVal ? 'Added to favorites' : 'Removed from favorites', ['is_favorite' => $newVal]);
        break;

    // -------------------------------------------------------
    // SEARCH (for compose autocomplete)
    // -------------------------------------------------------
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            success_response('OK', ['contacts' => []]);
        }

        $term = '%' . $q . '%';
        $contacts = $ToryMail->get_list_safe(
            "SELECT id, name, email, company FROM contacts
             WHERE user_id = ? AND (name LIKE ? OR email LIKE ?)
             ORDER BY is_favorite DESC, name ASC
             LIMIT 20",
            [$getUser['id'], $term, $term]
        );

        success_response('OK', ['contacts' => $contacts]);
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
