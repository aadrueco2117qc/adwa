<?php
// modules/assets/bulk_update.php — Handles bulk field updates on multiple assets.
// POST-only. Returns JSON. Called by the bulk update modal in index.view.php.

$module = 'assets';
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

$asset_ids = array_values(array_filter(array_map('intval', (array)($_POST['asset_ids'] ?? []))));
$field     = trim($_POST['field'] ?? '');
$value     = trim($_POST['value'] ?? '');
$user_id   = (int)$_SESSION['user_id'];

if (empty($asset_ids)) {
    echo json_encode(['success' => false, 'message' => 'No assets selected.']);
    exit;
}

// Whitelist — only these columns may be bulk-updated
$col_map = [
    'status'      => 'status',
    'location_id' => 'location_id',
    'owner_id'    => 'owner_id',
    'department_id' => 'department_id',
];

if (!array_key_exists($field, $col_map)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
    exit;
}

if ($field === 'status' && !in_array($value, ['active', 'spare', 'retired'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Empty value for nullable FK columns means clear (set NULL)
$nullable = ['location_id', 'owner_id'];
$db_val   = (in_array($field, $nullable) && $value === '') ? null : ($value === '' ? null : $value);

$col     = $col_map[$field];
$updated = 0;
$skipped = [];

foreach ($asset_ids as $asset_id) {
    $asset = get_asset_by_id($pdo, $asset_id);
    if (!$asset) continue;

    // Business rule: cannot retire an asset that has open tickets
    if ($field === 'status' && $value === 'retired' && has_open_tickets($pdo, $asset_id)) {
        $skipped[] = $asset['asset_tag'];
        continue;
    }

    $old_val = isset($asset[$col]) ? (string)$asset[$col] : null;
    $new_val_str = $db_val !== null ? (string)$db_val : null;

    if ($old_val === $new_val_str) continue; // skip no-op writes

    $pdo->prepare("UPDATE assets SET `{$col}` = ? WHERE asset_id = ?")
        ->execute([$db_val, $asset_id]);

    log_asset_change($pdo, $asset_id, $field, $old_val ?: null, $db_val, $user_id);
    $updated++;
}

echo json_encode([
    'success' => true,
    'updated' => $updated,
    'skipped' => $skipped,
]);
