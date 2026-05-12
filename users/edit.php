<?php
// modules/users/edit.php — Edit an existing user.

$module = 'users';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$edit_id = (int)($_GET['id'] ?? 0);
if ($edit_id <= 0) {
    header('Location: index.php');
    exit;
}

$user = get_user_by_id($pdo, $edit_id);
if (!$user) {
    header('Location: index.php');
    exit;
}

$is_edit = true;

$roles       = get_roles($pdo);
$departments = get_departments_list($pdo);

$old    = $_SESSION['form_data']   ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

// Pre-fill from $user if no old POST data
$v = function(string $k, mixed $default = '') use ($old, $user): mixed {
    if (isset($old[$k])) return $old[$k];
    return $user[$k] ?? $default;
};

$page_heading = 'Edit User — ' . htmlspecialchars($user['full_name']);
$back_url     = 'index.php';

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
