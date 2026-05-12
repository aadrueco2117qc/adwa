<?php
// modules/workorders/search_ajax.php — Returns table HTML for AJAX refresh.

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$filters = [
    'q'           => trim($_GET['q']           ?? ''),
    'status'      => trim($_GET['status']      ?? ''),
    'wo_type'     => trim($_GET['wo_type']     ?? ''),
    'assigned_to' => (int)($_GET['assigned_to'] ?? 0),
    'priority'    => trim($_GET['priority']    ?? ''),
    'sort_col'    => trim($_GET['sort_col']    ?? 'updated_at'),
    'sort_dir'    => trim($_GET['sort_dir']    ?? 'DESC'),
];
$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 10;

$work_orders = get_work_orders($pdo, $filters, $current_page, $per_page);
$total       = count_work_orders($pdo, $filters);

require __DIR__ . '/_table.php';
