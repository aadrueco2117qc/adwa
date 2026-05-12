<?php
// modules/tickets/add.php
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_staff = is_ticket_staff((int)$_SESSION['role_id']);

// Fetch logged-in user details for auto-fill
$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, d.department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.department_id 
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch();

// Populate initial fields based on query string (e.g., from QR Code)
$t = [
    'ticket_id'        => 0,
    'requester_id'     => $_SESSION['user_id'],
    'full_name'        => $user_info['full_name'] ?? '',
    'email'            => $user_info['email'] ?? '',
    'department'       => $user_info['department_name'] ?? '',
    'asset_tag'        => $_GET['asset_tag'] ?? '',
    'title'            => '',
    'description'      => '',
    'impact'           => 'medium',
    'urgency'          => 'medium',
    'is_event_support' => 0,
    'category_id'      => $_GET['category_id'] ?? null,
    'location_id'      => $_GET['location_id'] ?? null,
    'asset_id'         => $_GET['asset_id'] ?? null,
    'preferred_window' => '',
    'model'            => '',
    'warranty_status'  => ''
];

// If passing asset_id or asset_tag, fetch asset to auto-fill details
if ($t['asset_id'] || $t['asset_tag']) {
    $sql = "
        SELECT a.asset_id, a.asset_tag, a.category_id, a.location_id, a.model, w.warranty_end
        FROM assets a
        LEFT JOIN asset_warranty w ON a.asset_id = w.asset_id
        WHERE " . ($t['asset_id'] ? "a.asset_id = ?" : "a.asset_tag = ?");
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$t['asset_id'] ?: $t['asset_tag']]);
    if ($asset = $stmt->fetch()) {
        $t['asset_id']         = $asset['asset_id'];
        $t['asset_tag']        = $asset['asset_tag'];
        $t['asset_tag_linked'] = $asset['asset_tag'];
        $t['category_id']      = $asset['category_id'];
        $t['location_id']      = $asset['location_id'];
        $t['asset_model']      = $asset['model'];
        
        if ($asset['warranty_end']) {
            $end = new DateTime($asset['warranty_end']);
            $now = new DateTime();
            $t['warranty_status'] = $end >= $now
                ? 'Under Warranty (expires ' . $end->format('Y-m-d') . ')'
                : 'Warranty Expired (' . $end->format('Y-m-d') . ')';
        }
    }
}

$categories = get_all_categories($pdo);
$locations  = get_all_locations($pdo);
$kb_articles = get_recommended_kb_articles($pdo, $t['category_id']);
// Only fetch assets if we don't have one pre-filled or maybe we just want to fetch a list
$assets     = $pdo->query("SELECT asset_id, asset_tag, serial_number, manufacturer, model FROM assets WHERE status IN ('active', 'spare') ORDER BY asset_tag")->fetchAll();

$dynamic_fields = [];
$attachments    = [];
$assignables    = [];
$is_edit        = false;

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
