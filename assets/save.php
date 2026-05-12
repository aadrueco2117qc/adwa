<?php
// modules/assets/save.php — Handles POST for both create and update.
// Validates, writes to DB, logs changes, redirects.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/assets/index.php');
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$asset_id = (int)($_POST['asset_id'] ?? 0);
$is_edit  = $asset_id > 0;
$user_id  = (int)$_SESSION['user_id'];

// ── Retire shortcut ───────────────────────────────────────────
if (!empty($_POST['retire']) && $is_edit) {
    if (has_open_tickets($pdo, $asset_id)) {
        $_SESSION['flash_error'] = 'Cannot retire — asset has open tickets.';
        header('Location: ' . BASE_URL . "modules/assets/edit.php?id={$asset_id}");
        exit;
    }
    $old = get_asset_by_id($pdo, $asset_id);
    $pdo->prepare("UPDATE assets SET status = 'retired' WHERE asset_id = ?")->execute([$asset_id]);
    log_asset_change($pdo, $asset_id, 'status', $old['status'], 'retired', $user_id);
    header('Location: ' . BASE_URL . "modules/assets/view.php?id={$asset_id}&flash=retired");
    exit;
}

// ── Sanitize input ────────────────────────────────────────────
$d = sanitize_asset_post($_POST, $user_id);

// ── Validate ──────────────────────────────────────────────────
$errors = [];

if (empty($d['asset_tag'])) {
    $errors['asset_tag'] = 'Asset tag is required.';
} elseif (asset_tag_exists($pdo, $d['asset_tag'], $asset_id)) {
    $errors['asset_tag'] = 'This asset tag is already in use.';
}

if (empty($d['manufacturer'])) {
    $errors['manufacturer'] = 'Manufacturer is required.';
}

if (empty($d['model'])) {
    $errors['model'] = 'Model is required.';
}

if (!$d['category_id']) {
    $errors['category_id'] = 'Please select a category.';
}

if (!empty($d['serial_number']) && !empty($d['manufacturer'])) {
    if (serial_exists($pdo, $d['serial_number'], $d['manufacturer'], $asset_id)) {
        $errors['serial_number'] = 'This serial number already exists for this manufacturer.';
    }
}

if (empty($d['install_date'])) {
    $errors['install_date'] = 'Install date is required.';
} elseif ($d['install_date'] > date('Y-m-d')) {
    $errors['install_date'] = 'Install date cannot be in the future.';
}

if (!$d['location_id']) {
    $errors['location_id'] = 'Please select a location (room).';
}

// Warranty cross-field checks
if (!empty($d['warranty_start']) && !empty($d['warranty_end'])) {
    if ($d['warranty_end'] <= $d['warranty_start']) {
        $errors['warranty_end'] = 'Warranty end must be after warranty start.';
    }
    if (!empty($d['install_date']) && $d['warranty_start'] < $d['install_date']) {
        $errors['warranty_start'] = 'Warranty start cannot be before install date.';
    }
}

if (!empty($_POST['warranty_end']) && empty($_POST['warranty_start'])) {
    $errors['warranty_start'] = 'Warranty start is required when an end date is set.';
}

// ── Re-flash on error ─────────────────────────────────────────
if ($errors) {
    $_SESSION['form_data']   = $_POST;
    $_SESSION['form_errors'] = $errors;
    $redirect = $is_edit
        ? BASE_URL . "modules/assets/edit.php?id={$asset_id}"
        : BASE_URL . 'modules/assets/add.php';
    header("Location: {$redirect}");
    exit;
}

// ── Write to DB ───────────────────────────────────────────────
try {
    if ($is_edit) {
        $old = get_asset_by_id($pdo, $asset_id);
        update_asset($pdo, $asset_id, $d);
        
        // LOG AUDIT: Asset Update
        log_audit($pdo, 'UPDATE', 'asset', $asset_id, $old, $d);

        log_asset_changes($pdo, $asset_id, $old, $d, $user_id);
        upsert_warranty($pdo, $asset_id, $d);
        header('Location: ' . BASE_URL . "modules/assets/view.php?id={$asset_id}&flash=updated");
    } else {
        $new_id = create_asset($pdo, $d);

        // LOG AUDIT: Asset Creation
        log_audit($pdo, 'CREATE', 'asset', $new_id, null, $d);

        log_asset_change($pdo, $new_id, 'created', null, $d['asset_tag'], $user_id);
        upsert_warranty($pdo, $new_id, $d);
        header('Location: ' . BASE_URL . "modules/assets/view.php?id={$new_id}&flash=created");
    }
    exit;

} catch (PDOException $e) {
    // Surface as a form error — do not expose raw DB message
    $_SESSION['form_data']   = $_POST;
    $_SESSION['form_errors'] = ['asset_tag' => 'A database error occurred. Please try again.'];
    $redirect = $is_edit
        ? BASE_URL . "modules/assets/edit.php?id={$asset_id}"
        : BASE_URL . 'modules/assets/add.php';
    header("Location: {$redirect}");
    exit;
}
