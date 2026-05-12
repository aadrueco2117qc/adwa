<?php
// modules/technician/claim.php — Claim/take a queued work order.
// POST: wo_id
// JSON: {success:bool, message?:string}

$module = 'technician';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$wo_id = (int)($_POST['wo_id'] ?? 0);
if ($wo_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Work order required']);
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$role_id = (int)($_SESSION['role_id'] ?? 0);
$is_admin = tech_is_admin_role($pdo);

if (!technician_has_role_queue_schema($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Queue claiming is not enabled in the database yet.']);
    exit;
}

$wo = get_work_order_detail($pdo, $wo_id);
if (!$wo) {
    echo json_encode(['success' => false, 'message' => 'Work order not found']);
    exit;
}

if (!empty($wo['assigned_to'])) {
    echo json_encode(['success' => false, 'message' => 'Already claimed']);
    exit;
}

$queue_role_id = (int)($wo['assigned_role_id'] ?? 0);
// Allow technicians to claim work orders if:
// 1. They are admin, OR
// 2. The work order is assigned to their specific role, OR
// 3. The work order is not assigned to any role (unassigned queue)
if (!$is_admin && $queue_role_id > 0 && $queue_role_id !== $role_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot claim this queue']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Claim
    $stmt = $pdo->prepare("
        UPDATE work_orders
        SET assigned_to = ?, assigned_by = ?, claimed_at = NOW(), claimed_by = ?, status = 'assigned'
        WHERE wo_id = ? AND assigned_to IS NULL
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $wo_id]);

    if ($stmt->rowCount() < 1) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Could not claim (already taken)']);
        exit;
    }

    // Log assignment
    $stmt = $pdo->prepare("
        INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason)
        VALUES (?,?,?,?,?)
    ");
    $stmt->execute([$wo_id, null, $user_id, $user_id, 'Claimed from queue']);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
