<?php
// modules/users/toggle_active.php — Activate or deactivate a user account.
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

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$active  = (int)($_POST['active']  ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

// Prevent acting on your own account
if ($user_id === (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own account status.']);
    exit;
}

$user       = get_user_by_id($pdo, $user_id);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$actor_role  = current_user_role($pdo);
$target_role = $user['role_name'] ?? '';

if (!can_manage_user($actor_role, $target_role)) {
    if ($target_role === 'super_admin') {
        $msg = 'The Super Admin account cannot be deactivated.';
    } else {
        $msg = 'Admins cannot change the status of other admin accounts.';
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

set_user_active($pdo, $user_id, $active ? 1 : 0);

echo json_encode(['success' => true]);
