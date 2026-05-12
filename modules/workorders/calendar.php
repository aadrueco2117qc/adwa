<?php
// modules/workorders/calendar.php — Weekly Calendar View
$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$week_offset = (int)($_GET['week'] ?? 0);

$today = new DateTime();
$today->modify('monday this week');
if ($week_offset !== 0) {
    $today->modify($week_offset > 0 ? "+$week_offset week" : "$week_offset week");
}

$start_date = clone $today;
$end_date = clone $today;
$end_date->modify('+6 days');

$start_str = $start_date->format('Y-m-d 00:00:00');
$end_str   = $end_date->format('Y-m-d 23:59:59');

// Fetch WOs overlapping this week
$stmt = $pdo->prepare("
    SELECT w.wo_id, w.wo_number, w.wo_type, w.status, w.scheduled_start, w.scheduled_end,
           u.full_name AS technician_name, t.ticket_number
    FROM work_orders w
    LEFT JOIN users u ON w.assigned_to = u.user_id
    LEFT JOIN tickets t ON w.ticket_id = t.ticket_id
    WHERE w.scheduled_start IS NOT NULL AND w.scheduled_end IS NOT NULL
      AND w.status NOT IN ('cancelled')
      AND w.scheduled_start <= ? 
      AND w.scheduled_end >= ?
    ORDER BY w.scheduled_start ASC
");
$stmt->execute([$end_str, $start_str]);
$wos = $stmt->fetchAll();

// Build week days array
$days = [];
$curr = clone $start_date;
for ($i = 0; $i < 7; $i++) {
    $days[] = [
        'date' => $curr->format('Y-m-d'),
        'day'  => $curr->format('D'),
        'num'  => $curr->format('j'),
        'is_today' => $curr->format('Y-m-d') === (new DateTime())->format('Y-m-d')
    ];
    $curr->modify('+1 day');
}

// Hours 8 AM to 6 PM
$hours = range(8, 18);

require __DIR__ . '/calendar.view.php';
require_once __DIR__ . '/../../includes/footer.php';
