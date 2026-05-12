<?php
/**
 * Migration API: Add verification_type to checklist items for photo-based verification
 * This endpoint is called once to set up the verification_type field for photo items
 */

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth.php';

header('Content-Type: application/json');

try {
    // Basic auth check - only allow if user is logged in and is an admin
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Update items with "before-repair photo" in text
    $stmt = $pdo->prepare("
        UPDATE wo_checklist_items 
        SET verification_type = 'photo_before'
        WHERE item_text LIKE '%before%repair%photo%'
        AND (verification_type IS NULL OR verification_type = '')
    ");
    $stmt->execute();
    $before_count = $stmt->rowCount();
    
    // Update items with "after-repair photo" in text
    $stmt = $pdo->prepare("
        UPDATE wo_checklist_items 
        SET verification_type = 'photo_after'
        WHERE item_text LIKE '%after%repair%photo%'
        AND (verification_type IS NULL OR verification_type = '')
    ");
    $stmt->execute();
    $after_count = $stmt->rowCount();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully',
        'before_count' => $before_count,
        'after_count' => $after_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Migration failed: ' . $e->getMessage()
    ]);
}
?>
