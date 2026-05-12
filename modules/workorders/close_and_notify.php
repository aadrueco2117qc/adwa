<?php
// modules/workorders/close_and_notify.php
// Closes a resolved work order and notifies the requester to rate the service.
// Accepts POST only. Returns JSON.

$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Validate wo_id
$wo_id = (int)($_POST['wo_id'] ?? 0);
if ($wo_id < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid work order ID.']);
    exit;
}

$closed_by = (int)($_SESSION['user_id'] ?? 0);

try {
    close_work_order($pdo, $wo_id, $closed_by);
    echo json_encode(['success' => true, 'message' => 'Work order closed and requester notified.']);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
