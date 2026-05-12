<?php
// modules/assets/edit.php — Edit an existing asset.
// Logic only: guard, load asset, lookups, flash recovery, then hands off to the shared form view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit   = true;
$edit_id   = (int)($_GET['id'] ?? 0);
$parent_id = 0;
$asset     = $edit_id ? get_asset_by_id($pdo, $edit_id) : null;
$warranty      = $edit_id ? get_asset_warranty($pdo, $edit_id) : null;
$warranty_docs = $edit_id ? array_values(array_filter(
    get_asset_documents($pdo, $edit_id),
    fn($d) => in_array($d['document_type'], ['warranty', 'contract'])
)) : [];

if (!$asset) {
    echo '<div class="text-red-600 text-sm p-4">Asset not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$categories  = get_categories($pdo);
$locations   = get_all_locations($pdo);
$owners      = get_owners($pdo);
$parents     = get_top_level_assets($pdo, $edit_id);
$departments = get_departments($pdo);

// Re-populate from POST on validation failure
$old    = $_SESSION['form_data']  ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$v  = fn(string $k, mixed $default = '') => $old[$k] ?? ($asset[$k] ?? $default);
$wv = fn(string $k) => $old[$k] ?? ($warranty[$k] ?? '');

// Build location data for cascading JS selects
$loc_data = [];
foreach ($locations as $l) {
    $loc_data[$l['building']][$l['floor']][] = ['id' => $l['location_id'], 'room' => $l['room']];
}
$current_loc_id   = (int)($old['location_id'] ?? $asset['location_id'] ?? 0);
$current_building = '';
$current_floor    = '';
foreach ($locations as $l) {
    if ((int)$l['location_id'] === $current_loc_id) {
        $current_building = $l['building'];
        $current_floor    = $l['floor'];
        break;
    }
}

$page_heading = "Edit Asset — {$asset['asset_tag']}";
$back_url     = "view.php?id={$edit_id}";

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
