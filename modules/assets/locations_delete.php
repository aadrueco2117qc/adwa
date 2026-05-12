<?php
// modules/assets/locations_delete.php — AJAX: delete a location if no assets reference it
$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$location_id = (int)($_POST['location_id'] ?? 0);
if ($location_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid location ID.']);
    exit;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE location_id = ?");
$count_stmt->execute([$location_id]);
$asset_count = (int)$count_stmt->fetchColumn();

if ($asset_count > 0) {
    echo json_encode([
        'success' => false,
        'message' => "Cannot delete: $asset_count asset" . ($asset_count === 1 ? ' is' : 's are') . " assigned to this location."
    ]);
    exit;
}

$pdo->prepare("DELETE FROM locations WHERE location_id = ?")->execute([$location_id]);
echo json_encode(['success' => true, 'message' => 'Location deleted.']);
