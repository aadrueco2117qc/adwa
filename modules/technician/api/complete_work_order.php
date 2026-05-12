<?php
// modules/technician/api/complete_work_order.php
// API endpoint: Mark a work order as resolved, save signoff, update Work Orders module.
// POST body (JSON): { wo_id, signer_name, signer_satisfaction, feedback,
//                    signature_data_url, resolution_notes }

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

    // TEMP DEBUG: Log the raw payload to find what's wrong
    $raw_input = file_get_contents('php://input');
    error_log('[COMPLETE_WO] Raw input length: ' . strlen($raw_input));
    error_log('[COMPLETE_WO] Raw input (first 500 chars): ' . substr($raw_input, 0, 500));
    
    $input               = json_decode($raw_input, true) ?? [];
    
    error_log('[COMPLETE_WO] Parsed keys: ' . implode(', ', array_keys($input)));
    error_log('[COMPLETE_WO] wo_id=' . ($input['wo_id'] ?? 'MISSING') . ', signer_name=' . ($input['signer_name'] ?? 'MISSING') . ', session_user=' . ($_SESSION['user_id'] ?? 'MISSING'));
    
    $wo_id         = (int)($input['wo_id'] ?? 0);
    $signer_name   = trim($input['signer_name'] ?? '');
    $technician_id = (int)($_SESSION['user_id'] ?? 0);

    if (!$wo_id || !$signer_name || !$technician_id) {
        error_log('[COMPLETE_WO] VALIDATION FAILED: wo_id=' . $wo_id . ', signer_name=' . $signer_name . ', tech_id=' . $technician_id);
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: wo_id, signer_name']);
        exit;
    }

    $result = complete_work_order_transactional($pdo, $input, $technician_id);

    tech_dbg('H_COMPLETE', 'api/complete_work_order.php', 'WO resolved', [
        'wo_id'         => $result['wo_id'],
        'technician_id' => $technician_id,
        'has_signature' => $result['has_signature'],
        'satisfaction'  => $result['satisfaction'],
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Work order completed successfully',
        'wo_id'   => $result['wo_id'],
        'status'  => $result['status'],
    ]);
    exit;

} catch (Throwable $e) {
    tech_dbg('H_COMPLETE', 'api/complete_work_order.php', 'Error', ['error' => $e->getMessage()]);
    http_response_code(($e instanceof InvalidArgumentException) ? 400 : (($e->getMessage() === 'Work order not found') ? 404 : 500));
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>