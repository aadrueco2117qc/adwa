<?php
// modules/workorders/index.php — Work Order list/landing page.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$stats       = get_wo_stats($pdo);
$technicians = get_all_technicians($pdo);

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

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
