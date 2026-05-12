<?php
// scratch/simulate_breach.php
require_once __DIR__ . '/../config/db.php';

// Force Ticket #1 to be breached by setting its due dates to yesterday
$stmt = $pdo->prepare("
    UPDATE ticket_sla 
    SET response_due = DATE_SUB(NOW(), INTERVAL 1 DAY),
        diagnosis_due = DATE_SUB(NOW(), INTERVAL 1 DAY),
        resolution_due = DATE_SUB(NOW(), INTERVAL 1 DAY),
        is_response_breached = 0,
        is_diagnosis_breached = 0,
        is_resolution_breached = 0,
        responded_at = NULL,
        diagnosed_at = NULL,
        resolved_at = NULL
    WHERE ticket_id = 1
");
$stmt->execute();

echo "Ticket 1 due dates moved to yesterday. Now run the SLA Cron to trigger the breach detection!\n";
echo "Run this command: php C:\\xampp\\htdocs\\mtrts\\modules\\reports\\cron_sla.php\n";
