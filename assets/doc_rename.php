<?php
// modules/assets/doc_rename.php — Rename a document (display name only, not the file on disk).
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

$doc_id   = (int)($_POST['document_id'] ?? 0);
$new_name = trim($_POST['document_name'] ?? '');

if (!$doc_id || $new_name === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Sanitize: strip path traversal, keep extension
$new_name = basename($new_name);
if (strlen($new_name) > 255) {
    echo json_encode(['success' => false, 'message' => 'Name too long.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM asset_documents WHERE document_id = ? AND is_latest = 1");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'Document not found.']);
    exit;
}

// Force the display name to keep the real file extension — prevents a user
// from renaming "manual.pdf" to "manual.exe" and confusing future readers.
$base = pathinfo($new_name, PATHINFO_FILENAME);
$new_name = $base . '.' . $doc['file_type'];

$pdo->prepare("UPDATE asset_documents SET document_name = ? WHERE document_id = ?")
    ->execute([$new_name, $doc_id]);

echo json_encode(['success' => true, 'document_name' => $new_name]);
