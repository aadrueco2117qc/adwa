<?php
// modules/notifications/fetch.php
// AJAX — returns JSON: { count: int, items: [...] }
// Called by the navbar bell polling script every 30 seconds.

if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'functions.php';

$user_id = (int) $_SESSION['user_id'];
$count   = get_unread_count($pdo, $user_id);
$items   = get_recent_notifications($pdo, $user_id, 10);

header('Content-Type: application/json');
echo json_encode([
    'count' => $count,
    'items' => array_map(fn($n) => [
        'id'         => (int) $n['notif_id'],
        'title'      => $n['title'],
        'body'       => $n['body'],
        'link'       => $n['link'],
        'is_read'    => (bool) $n['is_read'],
        'created_at' => $n['created_at'],
    ], $items),
]);
