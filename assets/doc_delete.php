<?php
// modules/assets/doc_delete.php — Delete the latest version of a document.
// POST-only. Returns JSON.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

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

$doc_id = (int)($_POST['document_id'] ?? 0);
if (!$doc_id) {
    echo json_encode(['success' => false, 'message' => 'Missing document ID.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM asset_documents WHERE document_id = ? AND is_latest = 1");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'Document not found or is not the latest version.']);
    exit;
}

// Delete physical file
$file = __DIR__ . '/../../' . $doc['file_path'];
if (file_exists($file)) @unlink($file);

// Delete DB row
$pdo->prepare("DELETE FROM asset_documents WHERE document_id = ?")->execute([$doc_id]);

// Restore previous version to is_latest = 1 if one exists
$prev = $pdo->prepare("
    SELECT document_id FROM asset_documents
    WHERE asset_id = ? AND document_name = ?
    ORDER BY version DESC LIMIT 1
");
$prev->execute([$doc['asset_id'], $doc['document_name']]);
$prev_row = $prev->fetch();
if ($prev_row) {
    $pdo->prepare("UPDATE asset_documents SET is_latest = 1 WHERE document_id = ?")
        ->execute([$prev_row['document_id']]);
}

echo json_encode(['success' => true]);
