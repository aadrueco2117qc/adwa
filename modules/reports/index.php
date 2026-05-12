<?php
$module = 'reports';
$page_title = 'SLA & Performance Analytics';
require_once __DIR__ . '/../../config/guard.php';

// The frontend will load data via AJAX from api_stats.php and api_audit.php
// We just need to load the view.

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
