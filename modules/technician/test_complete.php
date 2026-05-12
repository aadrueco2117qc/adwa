<?php
// Temporary test script - DELETE AFTER DEBUGGING
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role_id'] = 3;

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/functions.php';

echo "Testing complete_work_order_transactional...\n";

try {
    $result = complete_work_order_transactional($pdo, [
        'wo_id' => 8,
        'checklist' => [],
        'safety' => [],
        'time_logs' => [],
        'signer_name' => 'Test Signer',
        'signer_satisfaction' => 0,
        'feedback' => '',
        'signature_data_url' => '',
        'resolution_notes' => 'test completion',
    ], 2);
    echo "SUCCESS: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
