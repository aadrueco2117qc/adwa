<?php
// modules/tickets/asset_lookup.php — AJAX: look up one asset by ID for QR scan prefill
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

$asset_id = (int)($_GET['asset_id'] ?? 0);
$asset_tag = trim($_GET['asset_tag'] ?? '');

if (!$asset_id && !$asset_tag) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing asset_id or asset_tag']);
    exit;
}

$sql = "
    SELECT a.asset_id, a.asset_tag, a.category_id, a.location_id,
           a.manufacturer, a.model, a.status,
           c.category_name,
           l.building, l.floor, l.room,
           w.warranty_end
    FROM assets a
    LEFT JOIN asset_categories c ON a.category_id = c.category_id
    LEFT JOIN locations l       ON a.location_id  = l.location_id
    LEFT JOIN asset_warranty w  ON a.asset_id     = w.asset_id
";

if ($asset_id) {
    $sql .= " WHERE a.asset_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asset_id]);
} else {
    $sql .= " WHERE a.asset_tag = ? OR a.serial_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asset_tag, $asset_tag]);
}

$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    http_response_code(404);
    echo json_encode(['error' => 'Asset not found']);
    exit;
}

$warranty_status = '';
if ($asset['warranty_end']) {
    $end = new DateTime($asset['warranty_end']);
    $now = new DateTime();
    $warranty_status = $end >= $now
        ? 'Under Warranty (expires ' . $end->format('Y-m-d') . ')'
        : 'Warranty Expired (' . $end->format('Y-m-d') . ')';
}
$asset['warranty_status'] = $warranty_status;
unset($asset['warranty_end']);

echo json_encode($asset);
