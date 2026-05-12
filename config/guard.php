<?php
// config/guard.php
// ─────────────────────────────────────────────────────────────
// Include this at the TOP of every module page file.
// It handles everything in one line from the developer's view:
//
//   $module = 'assets';          // which module this page belongs to
//   require_once '../../config/guard.php';
//   // ... write your page content here ...
//   require_once '../../includes/footer.php';
//
// It automatically:
//   1. Starts the session
//   2. Connects to the database
//   3. Redirects to login if not logged in
//   4. Blocks access if the user's role cannot access $module
//   5. Outputs the sidebar + topbar (opens the <main> content area)
// ─────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// 1. Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

// 1b. Session must carry a valid role_id (guards against stale sessions
//     or users with NULL role_id in the DB). Force re-login to refresh it.
if (!isset($_SESSION['role_id'])) {
    session_destroy();
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

// 2. Check module access (skip if $module is empty — e.g. dashboard)
if (!empty($module) && !can_access($pdo, $_SESSION['role_id'], $module)) {
    http_response_code(403);
    echo '<div style="padding:2rem;font-family:sans-serif;color:#b91c1c">
            <strong>Access Denied.</strong> You do not have permission to view this page.
          </div>';
    exit;
}

// 3. Expose $page for header.php and navbar.php (active state + breadcrumb)
$page = $module ?? '';

// 4. Run warranty expiry check — throttled to once per hour per session.
//    Inserts in-app notifications for admin/it_manager/it_staff.
//    Uses INSERT IGNORE so duplicate thresholds are never re-sent.
$_now = time();
if (!isset($_SESSION['last_warranty_check']) || ($_now - $_SESSION['last_warranty_check']) > 3600) {
    require_once __DIR__ . '/../modules/notifications/functions.php';
    check_warranty_expiry($pdo);
    check_low_stock($pdo);
    $_SESSION['last_warranty_check'] = $_now;
}
unset($_now);

// 5. Output the full layout shell (header, sidebar, topbar, opens <main>)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
