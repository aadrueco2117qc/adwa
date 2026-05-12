<?php
// modules/assets/doc_view.php — Stream a document inline (for in-browser viewing).
// GET only. Uses Content-Disposition: inline so the browser renders it.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$doc_id = (int)($_GET['id'] ?? 0);
if (!$doc_id) { http_response_code(400); exit; }

$stmt = $pdo->prepare("
    SELECT d.* FROM asset_documents d
    JOIN assets a ON a.asset_id = d.asset_id
    WHERE d.document_id = ?
");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) { http_response_code(404); exit; }

$file = __DIR__ . '/../../' . $doc['file_path'];
if (!file_exists($file)) { http_response_code(404); echo 'File not found.'; exit; }

$mime_map = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mime_map[$doc['file_type']] ?? null;

if (!$mime) { http_response_code(415); exit; }

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes($doc['document_name']) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-store');
readfile($file);
exit;
