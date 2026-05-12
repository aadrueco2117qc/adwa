<?php
// modules/assets/doc_download.php — Stream a document file securely.
// GET only. Never exposes the real file path to the browser.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$doc_id = (int)($_GET['id'] ?? 0);
if (!$doc_id) {
    http_response_code(400); exit;
}

$stmt = $pdo->prepare("
    SELECT d.* FROM asset_documents d
    JOIN assets a ON a.asset_id = d.asset_id
    WHERE d.document_id = ?
");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404); exit;
}

$file = __DIR__ . '/../../' . $doc['file_path'];
if (!file_exists($file)) {
    http_response_code(404);
    echo 'File not found on server.';
    exit;
}

$mime_map = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'dwg'  => 'application/acad',
    'zip'  => 'application/zip',
];
$mime = $mime_map[$doc['file_type']] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . addslashes($doc['document_name']) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-store');
readfile($file);
exit;
