<?php
// modules/assets/categories_save.php — AJAX: create or update an asset category
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

$category_id    = (int)($_POST['category_id'] ?? 0);
$category_name  = trim($_POST['category_name'] ?? '');
$description    = trim($_POST['description'] ?? '');
$has_bulb_hours = !empty($_POST['has_bulb_hours']) ? 1 : 0;

if ($category_name === '') {
    echo json_encode(['success' => false, 'message' => 'Category name is required.']);
    exit;
}
if (mb_strlen($category_name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Category name must be 100 characters or fewer.']);
    exit;
}
if (mb_strlen($description) > 255) {
    echo json_encode(['success' => false, 'message' => 'Description must be 255 characters or fewer.']);
    exit;
}
if (strcasecmp($category_name, 'Others') === 0) {
    echo json_encode(['success' => false, 'message' => '"Others" is reserved and cannot be created as a real category.']);
    exit;
}

$dup = $pdo->prepare("
    SELECT category_id FROM asset_categories
    WHERE category_name = ? AND category_id <> ?
    LIMIT 1
");
$dup->execute([$category_name, $category_id]);
if ($dup->fetch()) {
    echo json_encode(['success' => false, 'message' => 'A category with that name already exists.']);
    exit;
}

if ($category_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE asset_categories
        SET category_name=?, has_bulb_hours=?, description=?
        WHERE category_id=?
    ");
    $stmt->execute([$category_name, $has_bulb_hours, $description !== '' ? $description : null, $category_id]);
    echo json_encode(['success' => true, 'message' => 'Category updated.', 'category_id' => $category_id]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO asset_categories (category_name, has_bulb_hours, description)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$category_name, $has_bulb_hours, $description !== '' ? $description : null]);
    echo json_encode(['success' => true, 'message' => 'Category added.', 'category_id' => (int)$pdo->lastInsertId()]);
}
