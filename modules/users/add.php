<?php
// modules/users/add.php — Create a new user.

$module = 'users';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit = false;
$edit_id = 0;
$user    = null;

$roles       = get_roles($pdo);
$departments = get_departments_list($pdo);

$old    = $_SESSION['form_data']   ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$v = fn(string $k, mixed $default = '') => $old[$k] ?? $default;

$page_heading = 'Add New User';
$back_url     = 'index.php';

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
