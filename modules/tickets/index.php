<?php
// modules/tickets/index.php — Tickets list/landing page.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$stats = get_ticket_stats($pdo);

// Role-based restrictions: 
// Regular users (faculty=5, dept_staff=6, student=7) only see their own tickets.
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

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
