<?php
// modules/profile/index.php
$module = 'profile';
require_once '../../config/guard.php';
require_once 'functions.php';

$user_id = (int)$_SESSION['user_id'];
$profile = get_profile($pdo, $user_id);

if (!$profile) {
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

$flash_ok  = $_SESSION['flash_ok']  ?? '';
$flash_err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

$asset_activity  = get_profile_asset_activity($pdo, $user_id, 15);
$created_assets  = get_profile_created_assets($pdo, $user_id, 10);
$tickets         = get_profile_tickets($pdo, $user_id, 10);
$work_orders     = get_profile_work_orders($pdo, $user_id, 10);

require_once 'index.view.php';
require_once '../../includes/footer.php';
