<?php
// config/auth_only.php
// ─────────────────────────────────────────────────────────────
// Include this at the TOP of POST handlers and AJAX endpoints.
// Like guard.php but outputs NO layout HTML — just auth.
//
//   $module = 'assets';
//   require_once '../../config/auth_only.php';
//   // ... process form / return partial HTML ...
//
// ─────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

// Check module access if $module is defined
if (!empty($module) && !can_access($pdo, $_SESSION['role_id'], $module)) {
    http_response_code(403);
    exit('Access denied.');
}
