<?php
// modules/users/search_ajax.php — Returns updated table HTML for AJAX calls.

$module = 'users';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$filters = [
    'q'        => trim($_GET['q']        ?? ''),
    'role_id'  => (int)($_GET['role_id']  ?? 0),
    'dept_id'  => (int)($_GET['dept_id']  ?? 0),
    'status'   => trim($_GET['status']   ?? ''),
    'sort_col' => trim($_GET['sort_col'] ?? 'created_at'),
    'sort_dir' => trim($_GET['sort_dir'] ?? 'DESC'),
];
$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 15;

$users = get_users($pdo, $filters, $current_page, $per_page);
$total = count_users($pdo, $filters);

require __DIR__ . '/_table.php';
