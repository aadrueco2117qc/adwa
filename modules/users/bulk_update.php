<?php
// modules/users/bulk_update.php — Bulk field update on multiple users.
// POST-only. Returns JSON.

$module = 'users';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user_ids = array_values(array_filter(array_map('intval', (array)($_POST['user_ids'] ?? []))));
$field    = trim($_POST['field'] ?? '');
$value    = trim($_POST['value'] ?? '');
$self_id  = (int)$_SESSION['user_id'];

if (empty($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'No users selected.']);
    exit;
}

// Whitelist — only these columns may be bulk-updated
$col_map = [
    'role_id'       => 'role_id',
    'department_id' => 'department_id',
    'is_active'     => 'is_active',
];

if (!array_key_exists($field, $col_map)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
    exit;
}

// Validate value for role_id — must be a valid non-super_admin role
if ($field === 'role_id') {
    if (!$value) {
        echo json_encode(['success' => false, 'message' => 'Role is required.']);
        exit;
    }
    // exclude_super_admin = true: prevents bulk-assigning the super_admin role
    $roles       = get_roles($pdo, true);
    $valid_roles = array_column($roles, 'role_id');
    if (!in_array((int)$value, $valid_roles, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role.']);
        exit;
    }
}

// Validate value for is_active
if ($field === 'is_active' && !in_array($value, ['0', '1'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Nullable FK: empty value = NULL
$nullable = ['department_id'];
$db_val   = (in_array($field, $nullable) && $value === '') ? null : ($value === '' ? null : $value);

$col        = $col_map[$field];
$actor_role = current_user_role($pdo);
$updated    = 0;
$skipped    = [];

foreach ($user_ids as $uid) {
    // Never touch your own account via bulk
    if ($uid === $self_id) {
        $skipped[] = 'Your own account';
        continue;
    }

    $user        = get_user_by_id($pdo, $uid);
    if (!$user) continue;
    $target_role = $user['role_name'] ?? '';

    // Permission check: applies to status changes AND role reassignment
    if (!can_manage_user($actor_role, $target_role)) {
        if ($target_role === 'super_admin') {
            $skipped[] = $user['full_name'] . ' (Super Admin is protected)';
        } else {
            $skipped[] = $user['full_name'] . ' (insufficient permission)';
        }
        continue;
    }

    $pdo->prepare("UPDATE users SET `{$col}` = ? WHERE user_id = ?")
        ->execute([$db_val, $uid]);

    $updated++;
}

echo json_encode([
    'success' => true,
    'updated' => $updated,
    'skipped' => $skipped,
]);
