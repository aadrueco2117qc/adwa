<?php
// modules/notifications/mark_read.php
// AJAX POST — marks one or all notifications as read.
// POST body: id=N  (single)  OR  all=1  (all for user)

if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

require_once 'functions.php';

$user_id = (int) $_SESSION['user_id'];

if (!empty($_POST['all'])) {
    mark_all_notifications_read($pdo, $user_id);
    echo json_encode(['ok' => true]);
    exit;
}

if (!empty($_POST['id'])) {
    mark_notification_read($pdo, (int) $_POST['id'], $user_id);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false]);
