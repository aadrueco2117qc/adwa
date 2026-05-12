<?php
// modules/technician/api/parts_search.php
// API endpoint: Search parts by name, part number, or description
// GET /modules/technician/api/parts_search.php?q=query

$module = 'technician';
require_once __DIR__ . '/../../../config/auth_only.php';

header('Content-Type: application/json');

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($query) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Query must be at least 2 characters']);
        exit;
    }

    // LIKE-based search on part_name, part_number, and description
    $search_term = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT 
            part_id,
            part_name,
            part_number,
            description,
            category,
            quantity_on_hand,
            reorder_level,
            unit_price,
            is_active
        FROM parts_inventory
        WHERE is_active = 1
          AND (
              part_name LIKE ?
              OR part_number LIKE ?
              OR description LIKE ?
          )
        ORDER BY 
            CASE 
                WHEN part_name LIKE CONCAT(?, '%') THEN 1
                WHEN part_number LIKE CONCAT(?, '%') THEN 2
                ELSE 3
            END,
            part_name ASC
        LIMIT 20
    ");
    
    $stmt->execute([$search_term, $search_term, $search_term, $query, $query]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($parts),
        'parts' => $parts
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
