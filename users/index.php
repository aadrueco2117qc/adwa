<?php
// modules/users/index.php — User Access Control landing page.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'users';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$stats       = get_user_stats($pdo);
$roles       = get_roles($pdo);
$departments = get_departments_list($pdo);

$filters = [
    'q'        => trim($_GET['q']       ?? ''),
    'role_id'  => (int)($_GET['role_id']  ?? 0),
    'dept_id'  => (int)($_GET['dept_id']  ?? 0),
    'status'   => trim($_GET['status']  ?? ''),
    'sort_col' => trim($_GET['sort_col'] ?? 'created_at'),
    'sort_dir' => trim($_GET['sort_dir'] ?? 'DESC'),
];
$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 15;

$users = get_users($pdo, $filters, $current_page, $per_page);
$total = count_users($pdo, $filters);

// Flash messages from redirects
$flash_ok  = $_SESSION['flash_ok']  ?? null;
$flash_err = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
