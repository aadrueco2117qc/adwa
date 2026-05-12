<?php
// modules/assets/index.php — Asset registry landing page.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$stats      = get_asset_stats($pdo);
$categories = get_categories($pdo);
$buildings  = get_distinct_buildings($pdo);
$floors     = get_distinct_floors($pdo);

$filters = [
    'q'           => trim($_GET['q']            ?? ''),
    'status'      => trim($_GET['status']       ?? ''),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'building'    => trim($_GET['building']     ?? ''),
    'floor'       => trim($_GET['floor']        ?? ''),
    'sort_col'    => trim($_GET['sort_col']     ?? 'updated_at'),
    'sort_dir'    => trim($_GET['sort_dir']     ?? 'DESC'),
];
$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 10;

$assets        = get_assets($pdo, $filters, $current_page, $per_page);
$total         = count_assets($pdo, $filters);
$expiring      = (int)$stats['expiring_soon'];
$owners        = get_owners($pdo);
$all_locations = get_all_locations($pdo);
$departments   = get_departments($pdo);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
