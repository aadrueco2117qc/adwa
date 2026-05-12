<?php
// modules/profile/save.php — handles profile info and password changes
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// ── Update Profile Info ────────────────────────────────────────
if ($action === 'profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $contact   = trim($_POST['contact_number'] ?? '');
    $position  = trim($_POST['position'] ?? '');

    if ($full_name === '') {
        $_SESSION['flash_err'] = 'Full name is required.';
        header('Location: index.php');
        exit;
    }

    update_profile_info($pdo, $user_id, [
        'full_name'      => $full_name,
        'contact_number' => $contact,
        'position'       => $position,
    ]);

    // Keep session name up to date
    $_SESSION['full_name'] = $full_name;

    $_SESSION['flash_ok'] = 'Profile updated successfully.';
    header('Location: index.php');
    exit;
}

// ── Change Password ────────────────────────────────────────────
if ($action === 'password') {
    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    $hash = get_password_hash($pdo, $user_id);

    if (!password_verify($current, $hash)) {
        $_SESSION['flash_err'] = 'Current password is incorrect.';
        header('Location: index.php#security');
        exit;
    }

    if (strlen($new) < 8) {
        $_SESSION['flash_err'] = 'New password must be at least 8 characters.';
        header('Location: index.php#security');
        exit;
    }

    if ($new !== $confirm) {
        $_SESSION['flash_err'] = 'New passwords do not match.';
        header('Location: index.php#security');
        exit;
    }

    update_password($pdo, $user_id, $new);

    $_SESSION['flash_ok'] = 'Password changed successfully.';
    header('Location: index.php');
    exit;
}

header('Location: index.php');
exit;
