<?php
// modules/assets/doc_upload.php — Handle document upload for an asset.
// POST-only, multipart. Returns JSON.

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

$asset_id     = (int)($_POST['asset_id'] ?? 0);
$document_type = trim($_POST['document_type'] ?? '');
$user_id      = (int)$_SESSION['user_id'];

if (!$asset_id || !get_asset_by_id($pdo, $asset_id)) {
    echo json_encode(['success' => false, 'message' => 'Asset not found.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file']['error'] ?? -1;
    $msg = match($err) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum allowed size.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        default            => 'Upload failed (error ' . $err . ').',
    };
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file      = $_FILES['file'];
$orig_name = basename($file['name']);
$ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
$allowed   = ['pdf', 'jpg', 'jpeg', 'png', 'dwg', 'zip'];
$max_kb    = 51200; // 50 MB
$size_kb   = (int) ceil($file['size'] / 1024);

if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed. Accepted: ' . implode(', ', $allowed) . '.']);
    exit;
}

if ($size_kb > $max_kb) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 50 MB limit.']);
    exit;
}

// Determine version
$prev = $pdo->prepare("
    SELECT document_id, version FROM asset_documents
    WHERE asset_id = ? AND document_name = ? AND is_latest = 1
");
$prev->execute([$asset_id, $orig_name]);
$existing = $prev->fetch();
$version  = $existing ? $existing['version'] + 1 : 1;

// Store file
$dir = __DIR__ . '/../../public/uploads/assets/' . $asset_id . '/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$stored_name = 'v' . $version . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
$dest        = $dir . $stored_name;
$db_path     = 'public/uploads/assets/' . $asset_id . '/' . $stored_name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Check server permissions.']);
    exit;
}

// Mark previous version as not latest
if ($existing) {
    $pdo->prepare("UPDATE asset_documents SET is_latest = 0 WHERE document_id = ?")
        ->execute([$existing['document_id']]);
}

// Insert new row
$pdo->prepare("
    INSERT INTO asset_documents
        (asset_id, document_name, file_path, file_type, file_size_kb, document_type, version, is_latest, uploaded_by)
    VALUES (?,?,?,?,?,?,?,1,?)
")->execute([$asset_id, $orig_name, $db_path, $ext, $size_kb, $document_type ?: null, $version, $user_id]);

$doc_id = (int) $pdo->lastInsertId();

echo json_encode([
    'success'       => true,
    'document_id'   => $doc_id,
    'document_name' => $orig_name,
    'file_type'     => $ext,
    'file_size_kb'  => $size_kb,
    'document_type' => $document_type,
    'version'       => $version,
    'uploaded_at'   => date('M j, Y'),
]);
