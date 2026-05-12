<?php
// modules/reports/audit.php
$module = 'reports';
$page_title = 'E-Discovery & Audit Logs';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/functions.php';

// E-Discovery Search Logic
$f = [
    'user_id'     => $_GET['user_id']     ?? '',
    'object_type' => $_GET['object_type'] ?? '',
    'date_from'   => $_GET['date_from']   ?? date('Y-m-d', strtotime('-30 days')),
    'date_to'     => $_GET['date_to']     ?? date('Y-m-d'),
    'q'           => $_GET['q']           ?? '',
];

$page = (int)($_GET['page'] ?? 1);
$per  = 50;

$logs = get_audit_logs($pdo, $f, $page, $per);
$total_logs = count_audit_logs($pdo, $f);
$total_pages = ceil($total_logs / $per);

// Lookups for filters
$users = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll();
$object_types = $pdo->query("SELECT DISTINCT object_type FROM audit_log ORDER BY object_type")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/audit.view.php';
require_once __DIR__ . '/../../includes/footer.php';
