<?php
// modules/tickets/search_ajax.php — AJAX endpoint for tickets table

$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$is_staff = is_ticket_staff((int)$_SESSION['role_id']);
$requester_id_filter = $is_staff ? 0 : $_SESSION['user_id'];

$filters = [
    'requester_id' => $requester_id_filter,
    'q'            => trim($_GET['q'] ?? ''),
    'status'       => trim($_GET['status'] ?? ''),
    'priority'     => trim($_GET['priority'] ?? ''),
    'sort_col'     => trim($_GET['sort_col'] ?? 'updated_at'),
    'sort_dir'     => trim($_GET['sort_dir'] ?? 'DESC'),
];

$current_page = max(1, (int)($_GET['p'] ?? 1));
$per_page     = 10;

$tickets = get_tickets($pdo, $filters, $current_page, $per_page);
$total   = count_tickets($pdo, $filters);

require __DIR__ . '/_table.php';
