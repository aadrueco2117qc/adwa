<?php
// modules/users/save.php — Handles POST from add.php and edit.php.

$module = 'users';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$user_id = (int)($_POST['user_id'] ?? 0);
$is_edit = $user_id > 0;

// ── Collect input ─────────────────────────────────────────────
$full_name      = trim($_POST['full_name']      ?? '');
$email          = strtolower(trim($_POST['email'] ?? ''));
$id_number      = trim($_POST['id_number']      ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$position       = trim($_POST['position']       ?? '');
$department_id  = (int)($_POST['department_id'] ?? 0) ?: null;
$role_id        = (int)($_POST['role_id']       ?? 0);
$password       = $_POST['password']         ?? '';
$confirm        = $_POST['confirm_password'] ?? '';

// ── Validate ──────────────────────────────────────────────────
$errors = [];

if ($full_name === '') {
    $errors['full_name'] = 'Full name is required.';
}

if ($email === '') {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
} elseif (email_exists($pdo, $email, $user_id)) {
    $errors['email'] = 'This email address is already in use.';
}

if ($id_number !== '' && id_number_exists($pdo, $id_number, $user_id)) {
    $errors['id_number'] = 'This ID number is already assigned to another user.';
}

if ($role_id <= 0) {
    $errors['role_id'] = 'Please select a role.';
}

if (!$is_edit) {
    // Password required for new user
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
} elseif ($password !== '') {
    // Optional on edit — only validate if provided
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
}

// ── On failure: flash back to form ───────────────────────────
if ($errors) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data']   = $_POST;
    $redirect = $is_edit ? "edit.php?id={$user_id}" : 'add.php';
    header("Location: $redirect");
    exit;
}

// ── Save ──────────────────────────────────────────────────────
$d = [
    'full_name'      => $full_name,
    'email'          => $email,
    'id_number'      => $id_number,
    'contact_number' => $contact_number,
    'position'       => $position,
    'department_id'  => $department_id,
    'role_id'        => $role_id,
    'password'       => $password,
];

try {
    if ($is_edit) {
        update_user($pdo, $user_id, $d);
        $_SESSION['flash_ok'] = 'User updated successfully.';
    } else {
        create_user($pdo, $d);
        $_SESSION['flash_ok'] = 'User created successfully.';
    }
} catch (PDOException $e) {
    $_SESSION['flash_err'] = 'Database error: ' . $e->getMessage();
}

header('Location: index.php');
exit;
