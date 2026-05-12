<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/reports/functions.php';

echo "--- DATA INTEGRITY VERIFICATION ---\n";

// 1. Backlog & FTFR
$ops = get_operational_stats($pdo, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
echo "Backlog (Open Tickets): " . $ops['backlog'] . "\n";

// 2. MTTR
$mttr = get_mttr_stats($pdo, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
echo "Mean Time To Repair (MTTR): " . round($mttr['avg_mttr_minutes'] ?? 0, 1) . " mins\n";

// 3. SLA Compliance
$sla = get_sla_compliance_stats($pdo, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
echo "SLA Compliance Rate: " . ($sla['compliance_rate'] ?? 0) . "%\n";

// 4. Aging
$aging = get_ticket_aging($pdo);
echo "Ticket Aging Avg: " . ($aging['avg_age_days'] ?? 0) . " days\n";

// 5. Hotspots
$hotspots = get_asset_hotspots($pdo, 3);
echo "\n--- TOP FAILURE HOTSPOTS (ASSETS) ---\n";
foreach($hotspots as $h) {
    echo $h['asset_tag'] . " (" . $h['model'] . "): " . $h['failure_count'] . " failures\n";
}

// 6. Heatmap
$heatmap = get_location_heatmap($pdo);
echo "\n--- LOCATION HEATMAP ---\n";
foreach($heatmap as $loc) {
    echo $loc['building'] . " " . $loc['room'] . ": " . $loc['ticket_count'] . " tickets\n";
}

echo "\n--- VERIFICATION COMPLETE ---\n";
