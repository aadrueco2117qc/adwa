<?php
// modules/assets/search_ajax.php
// AJAX-only endpoint — returns the table partial HTML.
// Called by list.php via fetch(). No layout output.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

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

$assets = get_assets($pdo, $filters, $current_page, $per_page);
$total  = count_assets($pdo, $filters);

require __DIR__ . '/_table.php';
