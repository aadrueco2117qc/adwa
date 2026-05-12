<?php
// modules/workorders/view.php — Work order detail page.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'workorders';

// Technicians (Role 4) use the dedicated technician module for work orders.
// We handle their redirect here BEFORE guard.php outputs any HTML shell.
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
    // Ensure they are actually logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../modules/login.php');
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    header('Location: ../technician/view.php?id=' . $id);
    exit;
}

require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { header('Location: index.php'); exit; }

$wo = get_wo_by_id($pdo, $id);
if (!$wo) { header('Location: index.php'); exit; }

$checklist    = get_wo_checklist($pdo, $id, $wo['category_id'] ?? null);
$cl_name      = get_checklist_name($pdo, $wo['category_id'] ?? null);
$parts        = get_wo_parts($pdo, $id);
$time_logs    = get_wo_time_logs($pdo, $id);
$total_time   = compute_total_time($time_logs);
$media        = get_wo_media($pdo, $id);
$signoff      = get_wo_signoff($pdo, $id);
$notes        = get_wo_notes($pdo, $id);
$assignments  = get_wo_assignment_history($pdo, $id);
$technicians  = get_all_technicians($pdo);
$kb_articles  = get_related_kb_articles($pdo, $wo['category_id'] ?? null);

$active_tab   = $_GET['tab'] ?? 'checklist';

// Overdue check
$is_overdue = $wo['scheduled_end']
    && strtotime($wo['scheduled_end']) < time()
    && !in_array($wo['status'], ['resolved','closed']);

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
