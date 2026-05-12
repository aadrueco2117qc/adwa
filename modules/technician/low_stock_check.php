<?php
// modules/technician/low_stock_check.php
// API endpoint: Get all parts below reorder level
// GET /modules/technician/low_stock_check.php

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all parts where quantity is at or below reorder level
    $stmt = $pdo->prepare("
        SELECT 
            part_id,
            part_name,
            part_number,
            category,
            quantity_on_hand,
            reorder_level,
            quantity_on_hand - reorder_level AS shortage_amount,
            unit_price,
            (quantity_on_hand - reorder_level) * unit_price AS shortage_value
        FROM parts_inventory
        WHERE is_active = 1
          AND quantity_on_hand <= reorder_level
        ORDER BY shortage_amount ASC
    ");
    
    $stmt->execute();
    $low_stock_parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total shortage value
    $total_shortage_value = 0;
    foreach ($low_stock_parts as $part) {
        $total_shortage_value += abs($part['shortage_value'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'count' => count($low_stock_parts),
        'total_shortage_value' => round($total_shortage_value, 2),
        'parts' => $low_stock_parts
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
