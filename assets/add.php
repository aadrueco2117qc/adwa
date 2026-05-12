<?php
// modules/assets/add.php — Add a new asset.
// Logic only: guard, lookups, flash recovery, then hands off to the shared form view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit      = false;
$edit_id      = 0;
$warranty_docs = [];
$parent_id = (int)($_GET['parent_id'] ?? 0);
$asset     = null;
$warranty  = null;

$categories  = get_categories($pdo);
$locations   = get_all_locations($pdo);
$owners      = get_owners($pdo);
$parents     = get_top_level_assets($pdo, 0);
$departments = get_departments($pdo);

// Re-populate from POST on validation failure
$old    = $_SESSION['form_data']  ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$v  = fn(string $k, mixed $default = '') =>
    $old[$k] ?? ($k === 'parent_asset_id' && $parent_id ? $parent_id : $default);
$wv = fn(string $k) => $old[$k] ?? '';

// Build location data for cascading JS selects
$loc_data = [];
foreach ($locations as $l) {
    $loc_data[$l['building']][$l['floor']][] = ['id' => $l['location_id'], 'room' => $l['room']];
}
$current_loc_id   = (int)($old['location_id'] ?? 0);
$current_building = '';
$current_floor    = '';

$page_heading = 'Add New Asset';
$back_url     = 'index.php';

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
