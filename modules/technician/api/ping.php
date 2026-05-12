<?php
$module = 'technician';
require_once __DIR__ . '/../../../config/auth_only.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'ts' => time()]);
