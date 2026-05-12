<?php
require_once __DIR__ . '/../config/db.php';

echo "=== ticket_sla records ===\n";
$rows = $pdo->query("SELECT sla_id, ticket_id, policy_id, response_due, diagnosis_due, resolution_due, responded_at, diagnosed_at, resolved_at, is_response_breached, is_diagnosis_breached, is_resolution_breached FROM ticket_sla LIMIT 15")->fetchAll();
if (empty($rows)) {
    echo "No SLA records found!\n";
} else {
    foreach ($rows as $r) {
        echo "Ticket #{$r['ticket_id']} | Policy: {$r['policy_id']} | Resp Due: {$r['response_due']} | Resp Breach: {$r['is_response_breached']} | Resolved: " . ($r['resolved_at'] ?: 'N/A') . "\n";
    }
}

echo "\n=== Tickets without SLA ===\n";
$missing = $pdo->query("SELECT t.ticket_id, t.ticket_number, t.status FROM tickets t LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id WHERE ts.sla_id IS NULL")->fetchAll();
if (empty($missing)) {
    echo "All tickets have SLA records!\n";
} else {
    foreach ($missing as $m) {
        echo "Missing: {$m['ticket_number']} (Status: {$m['status']})\n";
    }
}
