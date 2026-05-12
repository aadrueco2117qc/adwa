<?php
// modules/assets/import_csv.php — Bulk import assets from a CSV file.
// POST-only, multipart. Returns JSON with per-row results.

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

$user_id = (int)$_SESSION['user_id'];

if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['csv_file'];
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Only CSV files are accepted.']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 5 MB limit.']);
    exit;
}

// ── Parse CSV ─────────────────────────────────────────────────
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Could not read file.']);
    exit;
}

// Read and normalize header row
$raw_headers = fgetcsv($handle);
if (!$raw_headers) {
    echo json_encode(['success' => false, 'message' => 'CSV file is empty.']);
    exit;
}
$headers = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', $h))), $raw_headers);

// Required columns
$required = ['asset_tag', 'manufacturer', 'model', 'category', 'status', 'install_date'];
$missing  = array_diff($required, $headers);
if ($missing) {
    echo json_encode(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing) . '.']);
    exit;
}

// ── Pre-load lookup tables for matching ───────────────────────
$cats = $pdo->query("SELECT category_id, LOWER(category_name) AS nm FROM asset_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$locs = $pdo->query("SELECT location_id, LOWER(CONCAT(building,'|',floor,'|',room)) AS nm FROM locations")->fetchAll(PDO::FETCH_KEY_PAIR);
$locs_flip = array_flip($locs);

// Also index locations by building+floor+room separately for flexible matching
$loc_lookup = [];
foreach ($pdo->query("SELECT location_id, LOWER(building) AS b, LOWER(floor) AS f, LOWER(room) AS r FROM locations")->fetchAll() as $l) {
    $loc_lookup[$l['b']][$l['f']][$l['r']] = $l['location_id'];
}

$owners = $pdo->query("SELECT user_id, LOWER(full_name) AS nm FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);
$owners_flip = array_flip($owners);

$depts = $pdo->query("SELECT department_id, LOWER(department_name) AS nm FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);
$depts_flip = array_flip($depts);

$valid_statuses = ['active', 'spare', 'retired'];

// ── Process rows ──────────────────────────────────────────────
$imported = 0;
$skipped  = [];
$row_num  = 1;

while (($row = fgetcsv($handle)) !== false) {
    $row_num++;
    if (count(array_filter($row)) === 0) continue; // skip blank lines

    $r = [];
    foreach ($headers as $i => $h) {
        $r[$h] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $errors = [];

    // ── Required fields ───────────────────────────────────────
    $asset_tag = strtoupper($r['asset_tag'] ?? '');
    if (!$asset_tag) { $errors[] = 'asset_tag is required'; }
    elseif (asset_tag_exists($pdo, $asset_tag)) { $errors[] = "asset tag '{$asset_tag}' already exists"; }

    $manufacturer = $r['manufacturer'] ?? '';
    if (!$manufacturer) $errors[] = 'manufacturer is required';

    $model = $r['model'] ?? '';
    if (!$model) $errors[] = 'model is required';

    // Category — match by name
    $cat_key    = strtolower($r['category'] ?? '');
    $category_id = array_search($cat_key, $cats);
    if (!$category_id) $errors[] = "unknown category '{$r['category']}'";

    $status = strtolower($r['status'] ?? '');
    if (!in_array($status, $valid_statuses, true)) $errors[] = "invalid status '{$r['status']}' (use active/spare/retired)";

    $install_date = $r['install_date'] ?? '';
    if (!$install_date) {
        $errors[] = 'install_date is required';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $install_date)) {
        $errors[] = 'install_date must be YYYY-MM-DD';
    } elseif ($install_date > date('Y-m-d')) {
        $errors[] = 'install_date cannot be in the future';
    }

    // ── Optional fields ───────────────────────────────────────
    $serial_number = $r['serial_number'] ?? '' ?: null;
    if ($serial_number && $manufacturer && serial_exists($pdo, $serial_number, $manufacturer, 0)) {
        $errors[] = "serial '{$serial_number}' already exists for {$manufacturer}";
    }

    // Location — match by building|floor|room
    $location_id = null;
    $building = strtolower($r['building'] ?? '');
    $floor    = strtolower($r['floor']    ?? '');
    $room     = strtolower($r['room']     ?? '');
    if ($building && $floor && $room) {
        $location_id = $loc_lookup[$building][$floor][$room] ?? null;
        if (!$location_id) $errors[] = "location not found: {$r['building']} / {$r['floor']} / {$r['room']}";
    }

    // Owner — match by full name
    $owner_id = null;
    if (!empty($r['owner'])) {
        $owner_id = $owners_flip[strtolower($r['owner'])] ?? null;
        if (!$owner_id) $errors[] = "owner '{$r['owner']}' not found";
    }

    // Department — match by name
    $department_id = null;
    if (!empty($r['department'])) {
        $department_id = $depts_flip[strtolower($r['department'])] ?? null;
        if (!$department_id) $errors[] = "department '{$r['department']}' not found";
    }

    $firmware_version = $r['firmware_version'] ?? '' ?: null;
    $network_info     = $r['network_info']     ?? '' ?: null;
    $bulb_hours       = isset($r['bulb_hours']) && $r['bulb_hours'] !== '' ? (int)$r['bulb_hours'] : null;

    // Warranty fields
    $warranty_start      = $r['warranty_start']      ?? '' ?: null;
    $warranty_end        = $r['warranty_end']        ?? '' ?: null;
    $coverage_type       = $r['coverage_type']       ?? 'parts_and_labor';
    $vendor_name         = $r['vendor_name']         ?? '' ?: null;
    $contract_reference  = $r['contract_reference']  ?? '' ?: null;

    if ($warranty_start && $warranty_end && $warranty_end <= $warranty_start) {
        $errors[] = 'warranty_end must be after warranty_start';
    }

    // ── Skip row if any errors ────────────────────────────────
    if ($errors) {
        $skipped[] = ['row' => $row_num, 'asset_tag' => $asset_tag ?: "row {$row_num}", 'reasons' => $errors];
        continue;
    }

    // ── Insert ────────────────────────────────────────────────
    $d = [
        'asset_tag'        => $asset_tag,
        'serial_number'    => $serial_number,
        'manufacturer'     => $manufacturer,
        'model'            => $model,
        'category_id'      => $category_id,
        'status'           => $status,
        'location_id'      => $location_id,
        'parent_asset_id'  => null,
        'install_date'     => $install_date,
        'firmware_version' => $firmware_version,
        'network_info'     => $network_info,
        'bulb_hours'       => $bulb_hours,
        'department_id'    => $department_id,
        'owner_id'         => $owner_id,
        'created_by'       => $user_id,
        'warranty_start'   => $warranty_start,
        'warranty_end'     => $warranty_end,
        'coverage_type'    => $coverage_type,
        'vendor_name'      => $vendor_name,
        'contract_reference' => $contract_reference,
    ];

    try {
        $new_id = create_asset($pdo, $d);
        log_asset_change($pdo, $new_id, 'created', null, $asset_tag, $user_id);
        upsert_warranty($pdo, $new_id, $d);
        $imported++;
    } catch (PDOException $e) {
        error_log("[assets/import_csv] row {$row_num} ({$asset_tag}): " . $e->getMessage());
        $skipped[] = ['row' => $row_num, 'asset_tag' => $asset_tag, 'reasons' => ['A database error occurred. Please contact an administrator.']];
    }
}

fclose($handle);

echo json_encode([
    'success'  => true,
    'imported' => $imported,
    'skipped'  => $skipped,
]);
