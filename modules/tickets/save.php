<?php
// modules/tickets/save.php
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../notifications/functions.php';
require_once __DIR__ . '/../reports/functions.php';
require_once __DIR__ . '/../../config/sla.php';

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$is_staff = is_ticket_staff((int)$_SESSION['role_id']);

$cat_in = (int)($_POST['category_id'] ?? 0);
$category_id = ($cat_in === 999 || $cat_in === 0) ? null : $cat_in;

$description = trim($_POST['description'] ?? '');
if (!empty($_POST['category_others'])) {
    $description = "[Category: " . trim($_POST['category_others']) . "]\n" . $description;
}

if ($action === 'create') {
    $d = [
        'requester_id'     => $user_id,
        'title'            => trim($_POST['title'] ?? ''),
        'description'      => $description,
        'impact'           => $_POST['impact'] ?? 'medium',
        'urgency'          => $_POST['urgency'] ?? 'medium',
        'is_event_support' => isset($_POST['is_event_support']) ? 1 : 0,
        'category_id'      => $category_id,
        'location_id'      => ((int)($_POST['location_id'] ?? 0)) ?: null,
        'asset_id'         => ((int)($_POST['asset_id'] ?? 0)) ?: null,
        'asset_tag'        => trim($_POST['asset_tag'] ?? ''),
        'request_type'     => $_POST['request_type'] ?? 'repair',
        'model'            => trim($_POST['model'] ?? ''),
        'warranty_status'  => trim($_POST['warranty_status'] ?? ''),
        'preferred_window' => $_POST['preferred_window'] ?: null,
        'dynamic_fields'   => $_POST['dynamic_fields'] ?? [],
        'channel'          => 'web',
    ];

    // Check duplicate
    $dup_id = check_duplicate_ticket($pdo, $d);
    
    if ($dup_id) {
        // Create the ticket anyway but mark as 'cancelled' (voided)
        $ticket_id = create_ticket($pdo, $d);
        $pdo->prepare("UPDATE tickets SET status = 'cancelled', duplicate_of_id = ? WHERE ticket_id = ?")->execute([$dup_id, $ticket_id]);
        
        // Add a comment to the original ticket
        $stmt_num = $pdo->prepare("SELECT ticket_number FROM tickets WHERE ticket_id = ?");
        $stmt_num->execute([$ticket_id]);
        $new_ticket_num = $stmt_num->fetchColumn();
        
        $comment = "Duplicate request detected: Ticket $new_ticket_num was submitted but has been automatically voided and linked to this ticket.";
        $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment_text, is_internal) VALUES (?, ?, ?, 1)")
            ->execute([$dup_id, $user_id, $comment]);

        // Notify user about the voiding
        notify_user($pdo, $user_id, 'Duplicate Request Detected', "Your request was detected as a duplicate of #$dup_id and has been voided. You can follow the original ticket here.", BASE_URL . "modules/tickets/view.php?id=$dup_id");

        header('Location: ' . BASE_URL . 'modules/tickets/view.php?id=' . $dup_id . '&msg=duplicate_voided&new_id=' . $ticket_id);
        exit;
    }

    $ticket_id = create_ticket($pdo, $d);

    // LOG AUDIT: Ticket Creation
    log_audit($pdo, 'CREATE', 'ticket', $ticket_id, null, $d);

    // Initialize SLA for this ticket
    init_ticket_sla($pdo, $ticket_id);

    // --- Handle File Uploads (Create) ---
    handle_ticket_uploads($pdo, $ticket_id, $user_id);

    // Notify IT Managers / Admins
    $notif_targets = $pdo->query("SELECT user_id FROM users WHERE role_id IN (1, 2) AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    $stmt_num = $pdo->prepare("SELECT ticket_number FROM tickets WHERE ticket_id = ?");
    $stmt_num->execute([$ticket_id]);
    $ticket_num = $stmt_num->fetchColumn();
    
    foreach ($notif_targets as $target_id) {
        notify_user($pdo, (int)$target_id, 'New Ticket: ' . $ticket_num, $d['title'], BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    }

    header('Location: ' . BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    exit;

} elseif ($action === 'update') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $t = get_ticket_by_id($pdo, $ticket_id);

    // Security check — ticket exists and user is staff or the requester
    if (!$t || (!$is_staff && $t['requester_id'] != $user_id)) {
        header('Location: ' . BASE_URL . 'modules/tickets/index.php');
        exit;
    }

    $d = [
        'title'            => trim($_POST['title'] ?? ''),
        'description'      => $description,
        'impact'           => $_POST['impact'] ?? 'medium',
        'urgency'          => $_POST['urgency'] ?? 'medium',
        'is_event_support' => isset($_POST['is_event_support']) ? 1 : 0,
        'category_id'      => $category_id,
        'location_id'      => ((int)($_POST['location_id'] ?? 0)) ?: null,
        'asset_id'         => ((int)($_POST['asset_id'] ?? 0)) ?: null,
        'asset_tag'        => trim($_POST['asset_tag'] ?? ''),
        'model'            => trim($_POST['model'] ?? ''),
        'warranty_status'  => trim($_POST['warranty_status'] ?? ''),
        'preferred_window' => $_POST['preferred_window'] ?: null,
        'request_type'     => $_POST['request_type'] ?? 'repair',
        'dynamic_fields'   => $_POST['dynamic_fields'] ?? [],
    ];

    // Staff-only fields — only trust them when the actor is staff
    if ($is_staff) {
        if (isset($_POST['status'])) {
            $d['status'] = $_POST['status'];
        }
        if (isset($_POST['assigned_to'])) {
            $d['assigned_to'] = ((int)$_POST['assigned_to']) ?: null;
        }
        if (isset($_POST['on_hold_reason'])) {
            $d['on_hold_reason'] = $_POST['on_hold_reason'] ?: null;
        }
    }

    update_ticket($pdo, $ticket_id, $d);

    // LOG AUDIT: Ticket Update
    log_audit($pdo, 'UPDATE', 'ticket', $ticket_id, $t, $d);

    // Notify on status / assignment changes
    if ($is_staff && isset($d['status']) && $d['status'] !== $t['status']) {
        notify_user($pdo, (int)$t['requester_id'], 'Ticket Status Update', "Ticket {$t['ticket_number']} is now " . strtoupper($d['status']), BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    }
    if ($is_staff && isset($d['assigned_to']) && $d['assigned_to'] && $d['assigned_to'] != $t['assigned_to']) {
        notify_user($pdo, (int)$d['assigned_to'], 'Ticket Assigned', "Ticket {$t['ticket_number']} has been assigned to you.", BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    }

    // Update SLA actual timestamps (Response, Diagnosis, Resolution)
    $sla_status = $d['status'] ?? $t['status'];
    $sla_pause_reason = ($sla_status === 'on_hold') ? ($d['on_hold_reason'] ?? $t['on_hold_reason'] ?? null) : null;
    update_ticket_sla($pdo, $ticket_id, $sla_status, $sla_pause_reason);

    // --- Handle Attachment Deletions (Update) ---
    if (!empty($_POST['deleted_attachments'])) {
        $del_ids = $_POST['deleted_attachments'];
        foreach ($del_ids as $att_id) {
            $att_id = (int)$att_id;
            // Verify this attachment belongs to this ticket before deleting
            $stmt_att = $pdo->prepare("SELECT file_path FROM ticket_attachments WHERE attachment_id = ? AND ticket_id = ?");
            $stmt_att->execute([$att_id, $ticket_id]);
            $att = $stmt_att->fetch();
            if ($att) {
                $full_path = __DIR__ . '/../../' . $att['file_path'];
                if (file_exists($full_path)) @unlink($full_path);
                $pdo->prepare("DELETE FROM ticket_attachments WHERE attachment_id = ?")->execute([$att_id]);
            }
        }
    }

    // --- Handle New Uploads (Update) ---
    handle_ticket_uploads($pdo, $ticket_id, $user_id);
    
    header('Location: ' . BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    exit;
    

} elseif ($action === 'add_comment') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $text = trim($_POST['comment_text'] ?? '');
    $is_internal = isset($_POST['is_internal']) && $is_staff ? 1 : 0;
    
    if ($ticket_id && $text) {
        $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment_text, is_internal) VALUES (?, ?, ?, ?)")
            ->execute([$ticket_id, $user_id, $text, $is_internal]);
            
        // Notify relevant parties
        $t = get_ticket_by_id($pdo, $ticket_id);
        if ($t) {
            if (!$is_internal && $user_id != $t['requester_id']) {
                notify_user($pdo, $t['requester_id'], 'New Comment on Ticket', "IT Staff added a comment to {$t['ticket_number']}.", BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
            } elseif ($user_id == $t['requester_id'] && $t['assigned_to']) {
                notify_user($pdo, $t['assigned_to'], 'New Reply from Requester', "The requester replied to ticket {$t['ticket_number']}.", BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
            }
        }
    }
    
    header('Location: ' . BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id);
    exit;
}

// Fallback
header('Location: ' . BASE_URL . 'modules/tickets/index.php');
exit;
