<?php
// modules/technician/parts_use.php
// API endpoint: Record part usage on a work order with audit trail
// POST /modules/technician/parts_use.php
// Body: { wo_id, part_id, quantity_used, serial_number (optional) }

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $wo_id = (int)($input['wo_id'] ?? 0);
    $part_id = (int)($input['part_id'] ?? 0);
    $quantity_used = (int)($input['quantity_used'] ?? 0);
    $serial_number = $input['serial_number'] ?? null;
    $technician_id = $_SESSION['user_id'] ?? null;

    if (!$wo_id || !$part_id || !$quantity_used || !$technician_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: wo_id, part_id, quantity_used']);
        exit;
    }

    require_once __DIR__ . '/../../config/db.php';

    // Start transaction
    $pdo->beginTransaction();

    // Record the part usage
    $stmt = $pdo->prepare("
        INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, serial_number, used_by, used_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$wo_id, $part_id, $quantity_used, $serial_number, $technician_id]);
    $usage_id = $pdo->lastInsertId();

    // Decrement inventory
    $stmt = $pdo->prepare("
        UPDATE parts_inventory 
        SET quantity_on_hand = quantity_on_hand - ?, 
            updated_at = NOW()
        WHERE part_id = ?
    ");
    $stmt->execute([$quantity_used, $part_id]);

    // Record audit trail in specialized table
    $stmt = $pdo->prepare("
        INSERT INTO parts_inventory_audit (part_id, wo_id, action, quantity_change, technician_id, notes)
        VALUES (?, ?, 'usage', ?, ?, CONCAT('Part usage: ', ?, ' units consumed on WO ', ?))
    ");
    $stmt->execute([$part_id, $wo_id, -$quantity_used, $technician_id, $quantity_used, $wo_id]);

    // LOG AUDIT: MASTER LOG
    log_audit($pdo, 'USE_PART', 'inventory', $part_id, null, [
        'wo_id' => $wo_id,
        'quantity' => $quantity_used,
        'serial' => $serial_number
    ]);

    // Check if stock is now below reorder level
    $stmt = $pdo->prepare("
        SELECT quantity_on_hand, reorder_level, part_name
        FROM parts_inventory
        WHERE part_id = ?
    ");
    $stmt->execute([$part_id]);
    $part = $stmt->fetch(PDO::FETCH_ASSOC);

    $low_stock_alert = false;
    if ($part && $part['quantity_on_hand'] <= $part['reorder_level']) {
        $low_stock_alert = true;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'usage_id' => $usage_id,
        'low_stock_alert' => $low_stock_alert,
        'current_stock' => $part['quantity_on_hand'] ?? 0,
        'reorder_level' => $part['reorder_level'] ?? 0,
        'message' => 'Part usage recorded successfully'
    ]);

} catch (PDOException $e) {
    try { $pdo->rollBack(); } catch (Throwable $ex) {}
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
