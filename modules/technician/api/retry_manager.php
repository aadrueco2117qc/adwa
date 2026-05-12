<?php
// modules/technician/api/retry_manager.php
// Manages offline sync queue retries with exponential backoff
// GET /modules/technician/api/retry_manager.php?action=process&wo_id=123
// GET /modules/technician/api/retry_manager.php?action=status&wo_id=123
// GET /modules/technician/api/retry_manager.php?action=list&wo_id=123

$module = 'technician';
require_once __DIR__ . '/../../../config/auth_only.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../conflict_resolver.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $wo_id = (int)($_GET['wo_id'] ?? 0);
    
    switch ($action) {
        case 'status':
            // Get retry queue status for a work order
            if (!$wo_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Work order ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'pending') AS pending,
                    SUM(status = 'pending' AND retry_count < 10) AS ready_to_retry,
                    SUM(status = 'failed') AS failed,
                    SUM(status = 'synced') AS synced
                FROM offline_sync_queue
                WHERE wo_id = ?
            ");
            $stmt->execute([$wo_id]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'wo_id' => $wo_id,
                'queue_status' => $status
            ]);
            break;
        
        case 'process':
            // Process ready-to-retry items
            if (!$wo_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Work order ID required']);
                exit;
            }
            
            $queue_items = get_sync_queue($pdo, $wo_id);
            $processed = [];
            $errors = [];
            
            foreach ($queue_items as $item) {
                $ready = process_retry_queue($pdo, $item['id']);
                
                if (!$ready) {
                    if ($item['retry_count'] >= 10) {
                        $processed[] = [
                            'queue_id' => $item['id'],
                            'status' => 'max_retries_exceeded',
                            'needs_manual_review' => true
                        ];
                    } else {
                        // Backoff period not elapsed yet
                        $processed[] = [
                            'queue_id' => $item['id'],
                            'status' => 'waiting_for_backoff',
                            'retry_count' => $item['retry_count']
                        ];
                    }
                    continue;
                }
                
                // Item is ready to retry - attempt to process it
                try {
                    $payload = json_decode($item['payload'], true);
                    $action_type = $item['action'];
                    
                    switch ($action_type) {
                        case 'safety_update':
                            $success = update_safety_completion(
                                $pdo,
                                $wo_id,
                                (int)$payload['safety_id'],
                                (bool)$payload['is_done'],
                                $payload['notes'] ?? null
                            );
                            break;
                        
                        case 'checklist_update':
                            $success = update_checklist_completion(
                                $pdo,
                                $wo_id,
                                (int)$payload['item_id'],
                                (bool)$payload['is_done'],
                                $payload['notes'] ?? null
                            );
                            break;
                        
                        case 'parts_use':
                            $success = record_part_usage(
                                $pdo,
                                $wo_id,
                                (int)$payload['part_id'],
                                (int)$payload['quantity_used'],
                                $_SESSION['user_id'],
                                $payload['serial_number'] ?? null
                            );
                            break;
                        
                        default:
                            $success = false;
                            throw new Exception("Unknown action: $action_type");
                    }
                    
                    if ($success) {
                        // Mark as synced
                        mark_synced($pdo, $item['id']);
                        $processed[] = [
                            'queue_id' => $item['id'],
                            'status' => 'synced',
                            'action' => $action_type
                        ];
                    } else {
                        throw new Exception("Failed to execute $action_type");
                    }
                } catch (Throwable $e) {
                    $errors[] = [
                        'queue_id' => $item['id'],
                        'error' => $e->getMessage(),
                        'retry_count' => $item['retry_count']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'wo_id' => $wo_id,
                'processed_count' => count($processed),
                'error_count' => count($errors),
                'processed' => $processed,
                'errors' => $errors
            ]);
            break;
        
        case 'list':
            // List all queue items for a work order (for debugging)
            if (!$wo_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Work order ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    action,
                    status,
                    retry_count,
                    created_at,
                    last_retry_at,
                    error_reason
                FROM offline_sync_queue
                WHERE wo_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$wo_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'wo_id' => $wo_id,
                'count' => count($items),
                'items' => $items
            ]);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action. Use: status, process, or list']);
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
