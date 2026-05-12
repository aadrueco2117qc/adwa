<?php
// modules/reports/api_stats.php
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Only staff/managers can access analytics
if (!in_array($_SESSION['role_id'], [1, 2, 3, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

$type = $_GET['type'] ?? 'all';

if (isset($_GET['drilldown'])) {
    echo json_encode(get_drilldown_tickets($pdo, $_GET['drilldown'], $start_date, $end_date));
    exit;
}

$data = [];

switch ($type) {
    case 'sla':
        $data = get_sla_compliance_stats($pdo, $start_date, $end_date);
        break;
    case 'mttr':
        $data = get_mttr_stats($pdo, $start_date, $end_date);
        break;
    case 'hotspots':
        $data = get_asset_hotspots($pdo, (int)($_GET['limit'] ?? 10));
        break;
    case 'scorecards':
        $data = get_technician_scorecards($pdo);
        break;
    case 'all':
    default:
        $data = [
            'sla' => get_sla_compliance_stats($pdo, $start_date, $end_date),
            'mttr' => get_mttr_stats($pdo, $start_date, $end_date),
            'trends' => get_resolution_trends($pdo, $start_date, $end_date),
            'hotspots' => get_asset_hotspots($pdo, 5),
            'scorecards' => get_technician_scorecards($pdo),
            'operational' => get_operational_stats($pdo, $start_date, $end_date),
            'heatmap' => get_location_heatmap($pdo),
            'warranty' => get_warranty_exposure($pdo),
            'escalations' => get_active_escalations($pdo),
            'aging' => get_ticket_aging($pdo),
            'cost' => get_cost_stats($pdo, $start_date, $end_date)
        ];
        break;
}

echo json_encode($data);
