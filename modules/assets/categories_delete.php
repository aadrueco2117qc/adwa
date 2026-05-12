<?php
// modules/assets/categories_delete.php — AJAX: delete a category if no assets reference it
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

$category_id = (int)($_POST['category_id'] ?? 0);
if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
    exit;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE category_id = ?");
$count_stmt->execute([$category_id]);
$asset_count = (int)$count_stmt->fetchColumn();

if ($asset_count > 0) {
    echo json_encode([
        'success' => false,
        'message' => "Cannot delete: $asset_count asset" . ($asset_count === 1 ? ' is' : 's are') . " assigned to this category."
    ]);
    exit;
}

$pdo->prepare("DELETE FROM asset_categories WHERE category_id = ?")->execute([$category_id]);
echo json_encode(['success' => true, 'message' => 'Category deleted.']);
