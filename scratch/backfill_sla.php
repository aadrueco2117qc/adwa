<?php
// Backfill SLA records for tickets that were created before the SLA hook was added
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sla.php';

$missing = $pdo->query("
    SELECT t.ticket_id, t.ticket_number 
    FROM tickets t 
    LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id 
    WHERE ts.sla_id IS NULL
      AND t.status NOT IN ('cancelled')
")->fetchAll();

echo "<h3>SLA Backfill Tool</h3>";
echo "<p>Found " . count($missing) . " tickets without SLA records.</p>";

foreach ($missing as $m) {
    try {
        init_ticket_sla($pdo, $m['ticket_id']);
        echo "✅ Initialized SLA for {$m['ticket_number']}<br>";
    } catch (Exception $e) {
        echo "❌ Failed for {$m['ticket_number']}: " . $e->getMessage() . "<br>";
    }
}

echo "<hr><p>Done! <a href='/mtrts/modules/tickets/index.php'>Go to Tickets →</a></p>";
