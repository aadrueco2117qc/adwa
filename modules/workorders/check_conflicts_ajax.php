<?php
// modules/workorders/check_conflicts_ajax.php
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$assigned_to = (int)($_GET['assigned_to'] ?? 0);
$start       = $_GET['start'] ?? '';
$end         = $_GET['end'] ?? '';
$ticket_id   = (int)($_GET['ticket_id'] ?? 0);
$wo_id       = (int)($_GET['wo_id'] ?? 0);

if (!$assigned_to || !$start || !$end) {
    echo json_encode(['conflict' => false]);
    exit;
}

$conflict = check_wo_conflict($pdo, $assigned_to, $start, $end, $wo_id, $ticket_id);

if ($conflict) {
    $type = $conflict['type'];
    $data = $conflict['data'];
    $c_start = (new DateTime($data['scheduled_start']))->format('M j, g:ia');
    $c_end   = (new DateTime($data['scheduled_end']))->format('M j, g:ia');
    
    $prefix = ($type === 'technician') ? "Technician conflict" : "Room conflict";
    $suffix = ($type === 'technician') ? "(Includes 30m buffer/travel)" : "(Includes 15m buffer)";

    echo json_encode([
        'conflict' => true,
        'message'  => "⚠️ $prefix: {$data['wo_number']} is scheduled from $c_start to $c_end. $suffix"
    ]);
} else {
    echo json_encode(['conflict' => false]);
}
