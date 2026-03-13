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
        $templates = $ToryMail->get_list_safe(
            "SELECT id, name, subject, created_at, updated_at FROM email_templates WHERE user_id = ? ORDER BY updated_at DESC",
            [$getUser['id']]
        );

        success_response('OK', ['templates' => $templates]);
        break;

    // -------------------------------------------------------
    // GET
    // -------------------------------------------------------
    case 'get':
        $template_id = intval($_GET['id'] ?? 0);
        if ($template_id <= 0) error_response('Invalid template ID');

        $template = $ToryMail->get_row_safe(
            "SELECT * FROM email_templates WHERE id = ? AND user_id = ?",
            [$template_id, $getUser['id']]
        );
        if (!$template) error_response('Template not found or access denied', 403);

        success_response('OK', ['template' => $template]);
        break;

    // -------------------------------------------------------
    // ADD
    // -------------------------------------------------------
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $name      = sanitize($_POST['name'] ?? '');
        $subject   = sanitize($_POST['subject'] ?? '');
        $body_html = $_POST['body_html'] ?? '';

        if (empty($name)) error_response('Template name is required');

        $templateId = $ToryMail->insert_safe('email_templates', [
            'user_id'    => $getUser['id'],
            'name'       => $name,
            'subject'    => $subject,
            'body_html'  => $body_html,
            'created_at' => gettime(),
            'updated_at' => gettime(),
        ]);

        if (!$templateId) error_response('Failed to create template');
        success_response('Template created', ['template_id' => $templateId]);
        break;

    // -------------------------------------------------------
    // EDIT
    // -------------------------------------------------------
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $template_id = intval($_POST['template_id'] ?? 0);
        if ($template_id <= 0) error_response('Invalid template ID');

        $template = $ToryMail->get_row_safe(
            "SELECT * FROM email_templates WHERE id = ? AND user_id = ?",
            [$template_id, $getUser['id']]
        );
        if (!$template) error_response('Template not found or access denied', 403);

        $updateData = ['updated_at' => gettime()];

        if (isset($_POST['name'])) {
            $name = sanitize($_POST['name']);
            if (empty($name)) error_response('Template name is required');
            $updateData['name'] = $name;
        }
        if (isset($_POST['subject'])) {
            $updateData['subject'] = sanitize($_POST['subject']);
        }
        if (isset($_POST['body_html'])) {
            $updateData['body_html'] = $_POST['body_html'];
        }

        $ToryMail->update_safe('email_templates', $updateData, 'id = ?', [$template_id]);
        success_response('Template updated');
        break;

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_response('Invalid request method', 405);
        csrf_verify();

        $template_id = intval($_POST['template_id'] ?? 0);
        if ($template_id <= 0) error_response('Invalid template ID');

        $template = $ToryMail->get_row_safe(
            "SELECT * FROM email_templates WHERE id = ? AND user_id = ?",
            [$template_id, $getUser['id']]
        );
        if (!$template) error_response('Template not found or access denied', 403);

        $ToryMail->remove_safe('email_templates', 'id = ?', [$template_id]);
        success_response('Template deleted');
        break;

    default:
        error_response('Invalid action', 400);
        break;
}
