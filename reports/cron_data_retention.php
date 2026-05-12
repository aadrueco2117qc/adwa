<?php
// modules/reports/cron_data_retention.php
// Scheduled task to enforce Data Retention Policies.
// Goal: PII minimization for historical records (> 2 years old).

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/functions.php';

echo "--- DATA RETENTION WORKER START ---\n";

// Policy: Mask PII for tickets closed more than 2 years ago.
$cutoff = date('Y-m-d H:i:s', strtotime('-2 years'));

try {
    $pdo->beginTransaction();

    // 1. Mask Requester Names/Emails in the audit logs for old records
    $stmt = $pdo->prepare("
        UPDATE audit_log 
        SET new_values = REPLACE(new_values, '\"email\":', '\"email_masked\":'),
            old_values = REPLACE(old_values, '\"email\":', '\"email_masked\":')
        WHERE created_at < ?
    ");
    $stmt->execute([$cutoff]);
    $affected = $stmt->rowCount();
    echo "Audit logs minimized: $affected records.\n";

    // 2. Anonymize very old tickets (e.g. > 5 years)
    $extreme_cutoff = date('Y-m-d H:i:s', strtotime('-5 years'));
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET description = '[DATA EXPIRED - RETENTION POLICY]',
            title = CONCAT('Archive: ', ticket_number)
        WHERE created_at < ? AND status IN ('closed', 'cancelled')
    ");
    $stmt->execute([$extreme_cutoff]);
    $affected_tickets = $stmt->rowCount();
    echo "Archived tickets: $affected_tickets records.\n";

    $pdo->commit();
    echo "--- DATA RETENTION WORKER COMPLETE ---\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
