<?php
// modules/tickets/view.php — Ticket Details page
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

$ticket = get_ticket_by_id($pdo, $id);
if (!$ticket) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

// Role checks
$is_staff = is_ticket_staff((int)$_SESSION['role_id']);
if (!$is_staff && $ticket['requester_id'] != $_SESSION['user_id']) {
    // Regular users can only view their own tickets
    require_once __DIR__ . '/../denied.php';
    exit;
}

$attachments    = get_ticket_attachments($pdo, $id);
$comments       = get_ticket_comments($pdo, $id, $is_staff);
$dynamic_fields = get_ticket_dynamic_fields($pdo, $id);



// See if there's an existing Work Order for this ticket
$related_wos = [];
$linked_wo_id = null;
$linked_wo_status = null;
$wo_rating = null;

if ($is_staff) {
    $stmt = $pdo->prepare("SELECT wo_id, wo_number, status, scheduled_start FROM work_orders WHERE ticket_id = ?");
    $stmt->execute([$id]);
    $related_wos = $stmt->fetchAll();
}

// For requester: find the closed work order linked to this ticket (if any)
// and load its signoff rating data
$stmt_wo = $pdo->prepare("
    SELECT wo_id, status FROM work_orders
    WHERE ticket_id = ? AND status = 'closed'
    ORDER BY wo_id DESC LIMIT 1
");
$stmt_wo->execute([$id]);
$closed_wo = $stmt_wo->fetch();

if ($closed_wo) {
    $linked_wo_id     = (int)$closed_wo['wo_id'];
    $linked_wo_status = $closed_wo['status'];

    $stmt_rating = $pdo->prepare("
        SELECT satisfaction, feedback FROM wo_signoff WHERE wo_id = ?
    ");
    $stmt_rating->execute([$linked_wo_id]);
    $wo_rating = $stmt_rating->fetch() ?: null;
}

// Duplicate handling
$original_ticket = null;
if (!empty($ticket['duplicate_of_id'])) {
    $original_ticket = get_ticket_by_id($pdo, (int)$ticket['duplicate_of_id']);
}
$stmt_dups = $pdo->prepare("SELECT ticket_id, ticket_number, status FROM tickets WHERE duplicate_of_id = ?");
$stmt_dups->execute([$id]);
$duplicates = $stmt_dups->fetchAll();

// Fetch SLA data for this ticket (for the SLA countdown widget)
$ticket_sla = null;
$sla_policy = null;
$stmt_sla = $pdo->prepare("
    SELECT ts.*, sp.policy_name, sp.response_minutes, sp.diagnosis_minutes, sp.resolution_minutes, sp.uses_business_hours
    FROM ticket_sla ts
    JOIN sla_policies sp ON ts.policy_id = sp.policy_id
    WHERE ts.ticket_id = ?
");
$stmt_sla->execute([$id]);
$ticket_sla = $stmt_sla->fetch();

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
