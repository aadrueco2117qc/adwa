<?php
// modules/profile/upload_avatar.php — handles profile picture upload
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

$user_id   = (int)$_SESSION['user_id'];
$upload_dir = __DIR__ . '/../../public/uploads/avatars/';

// Ensure directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Validate upload
if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_err'] = 'No file uploaded or upload error occurred.';
    header('Location: index.php');
    exit;
}

$file      = $_FILES['avatar'];
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$mime      = mime_content_type($file['tmp_name']);
$max_bytes = 2 * 1024 * 1024; // 2 MB

if (!in_array($mime, $allowed, true)) {
    $_SESSION['flash_err'] = 'Only JPG, PNG, WebP, or GIF images are allowed.';
    header('Location: index.php');
    exit;
}

if ($file['size'] > $max_bytes) {
    $_SESSION['flash_err'] = 'Image must be 2 MB or smaller.';
    header('Location: index.php');
    exit;
}

// Delete old avatar if it exists
$old_pic = get_profile($pdo, $user_id)['profile_picture'] ?? '';
if ($old_pic) {
    $old_path = __DIR__ . '/../../' . ltrim($old_pic, '/');
    if (is_file($old_path)) {
        @unlink($old_path);
    }
}

// Generate a safe filename
$ext      = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => 'jpg',
};
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$dest     = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    $_SESSION['flash_err'] = 'Failed to save the image. Please try again.';
    header('Location: index.php');
    exit;
}

$web_path = 'public/uploads/avatars/' . $filename;
update_profile_picture($pdo, $user_id, $web_path);
$_SESSION['profile_picture'] = $web_path;

$_SESSION['flash_ok'] = 'Profile picture updated.';
header('Location: index.php');
exit;
