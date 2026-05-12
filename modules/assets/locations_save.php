<?php
// modules/assets/locations_save.php — AJAX: create or update a location
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
$building    = trim($_POST['building'] ?? '');
$floor       = trim($_POST['floor'] ?? '');
$room        = trim($_POST['room'] ?? '');

if ($building === '' || $floor === '' || $room === '') {
    echo json_encode(['success' => false, 'message' => 'Building, Floor, and Room are required.']);
    exit;
}
if (mb_strlen($building) > 100 || mb_strlen($floor) > 50 || mb_strlen($room) > 100) {
    echo json_encode(['success' => false, 'message' => 'One or more fields exceed the maximum length.']);
    exit;
}

$dup = $pdo->prepare("
    SELECT location_id FROM locations
    WHERE building = ? AND floor = ? AND room = ? AND location_id <> ?
    LIMIT 1
");
$dup->execute([$building, $floor, $room, $location_id]);
if ($dup->fetch()) {
    echo json_encode(['success' => false, 'message' => 'A location with the same Building / Floor / Room already exists.']);
    exit;
}

if ($location_id > 0) {
    $stmt = $pdo->prepare("UPDATE locations SET building=?, floor=?, room=? WHERE location_id=?");
    $stmt->execute([$building, $floor, $room, $location_id]);
    echo json_encode(['success' => true, 'message' => 'Location updated.', 'location_id' => $location_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO locations (building, floor, room) VALUES (?, ?, ?)");
    $stmt->execute([$building, $floor, $room]);
    echo json_encode(['success' => true, 'message' => 'Location added.', 'location_id' => (int)$pdo->lastInsertId()]);
}
