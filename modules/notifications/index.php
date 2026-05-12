<?php
// modules/notifications/index.php — Notification history page (logic only)

$module = 'notifications';
require_once '../../config/guard.php';
require_once 'functions.php';

$user_id  = (int) $_SESSION['user_id'];
$cur_page = max(1, (int) ($_GET['p'] ?? 1));
$per      = 20;

$notifs      = get_all_notifications($pdo, $user_id, $cur_page, $per);
$total       = count_all_notifications($pdo, $user_id);
$total_pages = (int) ceil($total / $per);
$unread      = get_unread_count($pdo, $user_id);

// Mark all as read when the page is visited
if ($unread > 0) {
    mark_all_notifications_read($pdo, $user_id);
}

require_once 'index.view.php';
require_once '../../includes/footer.php';
