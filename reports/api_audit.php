<?php
// modules/reports/api_audit.php
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Only admins and IT managers can view audit logs
if (!in_array($_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'object_type' => $_GET['object_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
];

$page = (int)($_GET['page'] ?? 1);
$per = (int)($_GET['per'] ?? 20);

$logs = get_audit_logs($pdo, $filters, $page, $per);

// Mask PII if not super_admin (role 8)
if ($_SESSION['role_id'] != 8) {
    foreach ($logs as &$log) {
        if (!empty($log['old_values'])) {
            $old = json_decode($log['old_values'], true);
            if (isset($old['email'])) $old['email'] = '********@***.***';
            if (isset($old['contact_number'])) $old['contact_number'] = '********';
            $log['old_values'] = json_encode($old);
        }
        if (!empty($log['new_values'])) {
            $new = json_decode($log['new_values'], true);
            if (isset($new['email'])) $new['email'] = '********@***.***';
            if (isset($new['contact_number'])) $new['contact_number'] = '********';
            $log['new_values'] = json_encode($new);
        }
    }
}

echo json_encode($logs);
