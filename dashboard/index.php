<?php
// modules/dashboard/index.php
$module = '';   // empty = no module access check; dashboard is open to all logged-in users
require_once '../../config/guard.php';

// ── Stats ─────────────────────────────────────────────────────

// Assets
$asset_stats = ['total' => 0, 'active' => 0, 'spare' => 0, 'retired' => 0, 'expiring' => 0];
try {
    $row = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(status = 'active')  AS active,
               SUM(status = 'spare')   AS spare,
               SUM(status = 'retired') AS retired
        FROM assets
    ")->fetch();
    $asset_stats = array_merge($asset_stats, $row);

    $asset_stats['expiring'] = (int)$pdo->query("
        SELECT COUNT(*) FROM asset_warranty
        WHERE warranty_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();
} catch (PDOException $e) {}

// Users
$user_stats = ['total' => 0, 'active' => 0];
try {
    $row = $pdo->query("
        SELECT COUNT(*) AS total, SUM(is_active = 1) AS active FROM users
    ")->fetch();
    $user_stats = $row;
} catch (PDOException $e) {}

// Tickets
$ticket_stats = ['total' => 0, 'open' => 0];
try {
    $row = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(status NOT IN ('closed','resolved')) AS open
        FROM tickets
    ")->fetch();
    $ticket_stats = $row;
} catch (PDOException $e) {}

// Recent asset audit activity
$recent_activity = [];
try {
    $recent_activity = $pdo->query("
        SELECT l.field_name, l.old_value, l.new_value, l.changed_at,
               a.asset_tag, a.model,
               u.full_name AS changed_by_name
        FROM asset_audit_log l
        LEFT JOIN assets a ON l.asset_id = a.asset_id
        LEFT JOIN users  u ON l.changed_by = u.user_id
        ORDER BY l.changed_at DESC
        LIMIT 8
    ")->fetchAll();
} catch (PDOException $e) {}

// Recently added assets
$recent_assets = [];
try {
    $recent_assets = $pdo->query("
        SELECT a.asset_id, a.asset_tag, a.model, a.status, a.created_at,
               c.category_name, u.full_name AS created_by_name
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id = c.category_id
        LEFT JOIN users            u ON a.created_by   = u.user_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {}

require_once 'index.view.php';
require_once '../../includes/footer.php';
