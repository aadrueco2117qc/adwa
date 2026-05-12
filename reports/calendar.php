<?php
// modules/reports/calendar.php
$module = 'reports';
$page_title = 'Operating Calendar';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/../../config/db.php';

// Fetch all holidays
$holidays = $pdo->query("SELECT * FROM holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch business hours
$biz_hours = $pdo->query("SELECT * FROM business_hours ORDER BY day_of_week ASC")->fetchAll(PDO::FETCH_ASSOC);

// For the visual calendar, we'll default to the current month
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');

require __DIR__ . '/calendar.view.php';
require_once __DIR__ . '/../../includes/footer.php';
