<?php
$module = 'technician';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

// Everyone can see all work orders (queue-without-claim).
$hasListFn = function_exists('get_all_queue_work_orders');
tech_dbg('H_BOOT', 'modules/technician/index.php:8', 'Index boot', ['has_get_all_queue_work_orders' => $hasListFn]);

$all_work_orders = get_all_queue_work_orders($pdo);
$my_work_orders  = get_assigned_work_orders($pdo, $_SESSION['user_id']);

// Debug: Check work order data
tech_dbg('H_WO_DATA', 'modules/technician/index.php', 'Work order data debug', [
    'total_work_orders' => count($all_work_orders),
    'sample_wo' => array_map(function($wo) {
        return [
            'wo_id' => $wo['wo_id'],
            'wo_number' => $wo['wo_number'],
            'assigned_to' => $wo['assigned_to'],
            'assigned_to_name' => $wo['assigned_to_name'] ?? 'NULL',
            'status' => $wo['status']
        ];
    }, array_slice($all_work_orders, 0, 3))
]);

$role_name = function_exists('current_role_name') ? (current_role_name($pdo) ?: '') : '';
$is_admin = tech_is_admin_role($pdo);

// Low stock banner — count parts at or below reorder level
try {
    $stmt_low = $pdo->query("
        SELECT COUNT(*) AS low_count,
               SUM(quantity_on_hand = 0) AS out_count
        FROM parts_inventory
        WHERE is_active = 1
          AND quantity_on_hand <= reorder_level
    ");
    $low_stock_stats = $stmt_low->fetch();
    $low_stock_count = (int)($low_stock_stats['low_count'] ?? 0);
    $out_stock_count = (int)($low_stock_stats['out_count'] ?? 0);
} catch (Throwable $e) {
    $low_stock_count = 0;
    $out_stock_count = 0;
}
tech_dbg('H1', 'modules/technician/index.php:15', 'Technician index loaded', [
    'has_global_current_role_name' => function_exists('current_role_name'),
    'role_name' => $role_name,
    'is_admin' => $is_admin,
    'has_role_queue_schema' => technician_has_role_queue_schema($pdo),
]);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
?>