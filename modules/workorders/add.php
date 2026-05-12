<?php
// modules/workorders/add.php — Create new work order.
// Logic only: guard, load lookups, require the shared form view.

$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit     = false;
$wo          = [];
$ticket_id   = (int)($_GET['ticket_id'] ?? 0);
if ($ticket_id > 0) {
    $wo['ticket_id'] = $ticket_id;
    // Get ticket info to fetch category_id for KB articles
    $stmt = $pdo->prepare("SELECT t.ticket_number, t.title, a.category_id FROM tickets t LEFT JOIN assets a ON t.asset_id = a.asset_id WHERE t.ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $tk_info = $stmt->fetch();
    if ($tk_info) {
        $wo['ticket_number'] = $tk_info['ticket_number'];
        $wo['category_id']   = $tk_info['category_id'];
    }
}
$errors      = $_SESSION['wo_errors'] ?? [];
$old         = $_SESSION['wo_old'] ?? [];
unset($_SESSION['wo_errors'], $_SESSION['wo_old']);

$technicians = get_all_technicians($pdo);
$tickets     = get_available_tickets($pdo);
$all_parts   = get_all_parts($pdo);
$kb_articles = get_related_kb_articles($pdo, $wo['category_id'] ?? null);

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
