<?php
// modules/technician/api/parts_use.php
// API endpoint: Record part usage on a work order with stock-safe decrement.
// POST /modules/technician/api/parts_use.php
// Body: { wo_id, part_id, quantity_used, serial_number (optional) }

$module = 'technician';
require_once __DIR__ . '/../../../config/auth_only.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $wo_id         = (int)($input['wo_id'] ?? 0);
    $part_id       = (int)($input['part_id'] ?? 0);
    $quantity_used = (int)($input['quantity_used'] ?? 0);
    $serial_number = $input['serial_number'] ?? null;
    $technician_id = (int)($_SESSION['user_id'] ?? 0);

    if (!$wo_id || !$part_id || $quantity_used <= 0 || !$technician_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: wo_id, part_id, quantity_used']);
        exit;
    }

    $info = save_work_order_part($pdo, $wo_id, $part_id, $quantity_used, $serial_number);

    echo json_encode([
        'success'         => true,
        'usage_id'        => $info['usage_id'],
        'current_stock'   => $info['current_stock'],
        'reorder_level'   => $info['reorder_level'],
        'low_stock_alert' => $info['low_stock_alert'],
        'message'         => 'Part usage recorded successfully',
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    // Stock-insufficient and part-not-found are RuntimeExceptions from save_work_order_part.
    // They are user errors (409 conflict) rather than 500.
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
