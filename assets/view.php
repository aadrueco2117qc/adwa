<?php
// modules/assets/view.php — View a single asset (read-only).
// Logic only: guard, data fetch, then hands off to the view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id    = (int)($_GET['id'] ?? 0);
$asset = $id ? get_asset_by_id($pdo, $id) : null;

if (!$asset) {
    echo '<div class="text-red-600 text-sm p-4">Asset not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$warranty          = get_asset_warranty($pdo, $id);
$documents         = get_asset_documents($pdo, $id);
$children          = get_asset_children($pdo, $id);
$history           = get_asset_repair_history($pdo, $id);
$open_ticket_count = get_open_ticket_count($pdo, $id);
$wp                = warranty_progress($warranty['warranty_start'] ?? null, $warranty['warranty_end'] ?? null);
$active_tab        = $_GET['tab'] ?? 'warranty';

$show_warn_banner = $warranty && !$wp['expired'] && isset($wp['days_left']) && $wp['days_left'] <= 30;
$expired_banner   = $warranty && $wp['expired'];

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
