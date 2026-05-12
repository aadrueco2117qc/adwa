<?php
// modules/reports/cron_sla.php
// This script should be run via CRON every 5-15 minutes.
// Command: php C:\xampp\htdocs\mtrts\modules\reports\cron_sla.php

// Define BASE_URL manually since we're in CLI and may not have $_SERVER
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mtrts/');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/sla.php';
require_once __DIR__ . '/../notifications/functions.php';

// Ensure $pdo is available (it comes from config/db.php)
if (!isset($pdo)) {
    die("[SLA WORKER] Error: PDO connection not established.\n");
}

echo "[SLA WORKER] Starting run at " . date('Y-m-d H:i:s') . "\n";

/**
 * PHASE 1: MARK BREACHES
 * Updates the flags for tickets that have passed their due date but are not yet resolved.
 */
$pdo->query("UPDATE ticket_sla SET is_response_breached = 1 WHERE responded_at IS NULL AND response_due < NOW() AND is_response_breached = 0");
$pdo->query("UPDATE ticket_sla SET is_diagnosis_breached = 1 WHERE diagnosed_at IS NULL AND diagnosis_due < NOW() AND is_diagnosis_breached = 0");
$pdo->query("UPDATE ticket_sla SET is_resolution_breached = 1 WHERE resolved_at IS NULL AND resolution_due < NOW() AND is_resolution_breached = 0");


/**
 * PHASE 2: ESCALATIONS
 */

// LEVEL 1: Warning (30 mins before breach)
// Targeting: The Assignee
$stmt = $pdo->query("
    SELECT ts.*, t.ticket_number, t.assigned_to 
    FROM ticket_sla ts
    JOIN tickets t ON ts.ticket_id = t.ticket_id
    WHERE ts.escalation_level = 0
      AND ts.resolved_at IS NULL
      AND (
        (ts.responded_at IS NULL AND ts.response_due <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)) OR 
        (ts.resolved_at IS NULL AND ts.resolution_due <= DATE_ADD(NOW(), INTERVAL 30 MINUTE))
      )
      AND t.status NOT IN ('resolved', 'closed', 'cancelled')
");
$l1_count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['assigned_to']) {
        $notif_key = "sla_warn_" . $row['sla_id'] . "_1";
        notify_user($pdo, (int)$row['assigned_to'], "SLA Warning: #" . $row['ticket_number'], "This ticket is approaching its SLA breach window. Please update the status.", BASE_URL . "modules/tickets/view.php?id=" . $row['ticket_id'], $notif_key);
    }
    $pdo->prepare("UPDATE ticket_sla SET escalation_level = 1 WHERE sla_id = ?")->execute([$row['sla_id']]);
    $l1_count++;
}
if ($l1_count > 0) echo "[SLA WORKER] Sent $l1_count L1 Warnings.\n";


// LEVEL 2: Breach Alert (Immediate breach)
// Targeting: Assignee + IT Managers (Role 2)
$stmt = $pdo->query("
    SELECT ts.*, t.ticket_number, t.assigned_to 
    FROM ticket_sla ts
    JOIN tickets t ON ts.ticket_id = t.ticket_id
    WHERE ts.escalation_level < 2
      AND (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1)
      AND t.status NOT IN ('resolved', 'closed', 'cancelled')
");
$l2_count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recipients = [];
    if ($row['assigned_to']) $recipients[] = (int)$row['assigned_to'];
    
    // Get IT Managers
    $managers = $pdo->query("SELECT user_id FROM users WHERE role_id = 2 AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($managers as $mid) $recipients[] = (int)$mid;
    
    $recipients = array_unique($recipients);
    foreach ($recipients as $uid) {
        $notif_key = "sla_breach_" . $row['sla_id'] . "_2_" . $uid;
        notify_user($pdo, $uid, "SLA BREACH: #" . $row['ticket_number'], "This ticket has breached its SLA. Immediate action is required.", BASE_URL . "modules/tickets/view.php?id=" . $row['ticket_id'], $notif_key);
    }
    
    $pdo->prepare("UPDATE ticket_sla SET escalation_level = 2 WHERE sla_id = ?")->execute([$row['sla_id']]);
    
    // AUTO-REPRIORITIZE: Bump ticket priority one level on breach
    $priority_ladder = ['low' => 'medium', 'medium' => 'high', 'high' => 'critical'];
    $stmt_pri = $pdo->prepare("SELECT priority FROM tickets WHERE ticket_id = ?");
    $stmt_pri->execute([$row['ticket_id']]);
    $current_priority = $stmt_pri->fetchColumn();
    if (isset($priority_ladder[$current_priority])) {
        $new_priority = $priority_ladder[$current_priority];
        $pdo->prepare("UPDATE tickets SET priority = ? WHERE ticket_id = ?")->execute([$new_priority, $row['ticket_id']]);
        echo "[SLA WORKER] Auto-reprioritized ticket #{$row['ticket_number']}: $current_priority → $new_priority\n";
    }
    
    $l2_count++;
}
if ($l2_count > 0) echo "[SLA WORKER] Processed $l2_count L2 Breaches.\n";


// LEVEL 3: Critical Escalation (2 hours after breach)
// Targeting: IT Director / Super Admins (Role 8)
$stmt = $pdo->query("
    SELECT ts.*, t.ticket_number, t.assigned_to
    FROM ticket_sla ts
    JOIN tickets t ON ts.ticket_id = t.ticket_id
    WHERE ts.escalation_level = 2
      AND (
        (ts.is_response_breached = 1 AND ts.response_due <= DATE_SUB(NOW(), INTERVAL 2 HOUR)) OR
        (ts.is_resolution_breached = 1 AND ts.resolution_due <= DATE_SUB(NOW(), INTERVAL 2 HOUR))
      )
      AND t.status NOT IN ('resolved', 'closed', 'cancelled')
");
$l3_count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Get Super Admins
    $directors = $pdo->query("SELECT user_id FROM users WHERE role_id = 8 AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($directors as $did) {
        $notif_key = "sla_esc_" . $row['sla_id'] . "_3_" . $did;
        notify_user($pdo, (int)$did, "CRITICAL ESCALATION: #" . $row['ticket_number'], "SLA breach remains unresolved after 2 hours. Management intervention requested.", BASE_URL . "modules/tickets/view.php?id=" . $row['ticket_id'], $notif_key);
    }
    
    $pdo->prepare("UPDATE ticket_sla SET escalation_level = 3 WHERE sla_id = ?")->execute([$row['sla_id']]);
    $l3_count++;
}
if ($l3_count > 0) echo "[SLA WORKER] Escalated $l3_count L3 Critical items.\n";

echo "[SLA WORKER] Run completed at " . date('Y-m-d H:i:s') . "\n";
