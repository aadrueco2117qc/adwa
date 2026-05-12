<?php
// modules/users/import_csv.php — Bulk import users from a CSV file.
// POST-only, multipart. Returns JSON with per-row results.

$module = 'users';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['csv_file'];
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Only CSV files are accepted.']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 5 MB limit.']);
    exit;
}

// ── Parse CSV ─────────────────────────────────────────────────
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Could not read file.']);
    exit;
}

$raw_headers = fgetcsv($handle);
if (!$raw_headers) {
    echo json_encode(['success' => false, 'message' => 'CSV file is empty.']);
    exit;
}
$headers = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', $h))), $raw_headers);

$required = ['full_name', 'email', 'role', 'password'];
$missing  = array_diff($required, $headers);
if ($missing) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing) . '.']);
    exit;
}

// ── Pre-load lookup tables ─────────────────────────────────────
$roles_raw  = $pdo->query("SELECT role_id, LOWER(role_name) AS nm FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
// $roles_raw: ['admin' => 1, 'it_manager' => 2, ...]
// We need name → id, so flip
$roles_by_name = array_flip($roles_raw);   // role_id => role_name → flip → role_name => role_id
// Actually fetchAll(PDO::FETCH_KEY_PAIR) returns [role_id => nm], so flip gives [nm => role_id]
// Let's be explicit:
$roles_lookup = [];
foreach ($pdo->query("SELECT role_id, LOWER(role_name) AS nm FROM roles")->fetchAll() as $r) {
    $roles_lookup[$r['nm']] = $r['role_id'];
}

$depts_lookup = [];
foreach ($pdo->query("SELECT department_id, LOWER(department_name) AS nm FROM departments")->fetchAll() as $d) {
    $depts_lookup[$d['nm']] = $d['department_id'];
}

// ── Process rows ──────────────────────────────────────────────
$imported = 0;
$skipped  = [];
$row_num  = 1;

while (($row = fgetcsv($handle)) !== false) {
    $row_num++;
    if (count(array_filter($row)) === 0) continue; // skip blank lines

    // Map columns to named keys
    $r = [];
    foreach ($headers as $i => $h) {
        $r[$h] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $errors = [];

    // ── Required fields ───────────────────────────────────────
    $full_name = trim($r['full_name'] ?? '');
    if (!$full_name) $errors[] = 'full_name is required';

    $email = strtolower(trim($r['email'] ?? ''));
    if (!$email) {
        $errors[] = 'email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "invalid email format '$email'";
    } elseif (email_exists($pdo, $email)) {
        $errors[] = "email '$email' already exists";
    }

    $role_key = strtolower(trim($r['role'] ?? ''));
    $role_id  = $roles_lookup[$role_key] ?? null;
    if (!$role_id) $errors[] = "unknown role '$role_key' (use: admin, it_manager, it_staff, technician, faculty, department_staff, student)";

    $password = $r['password'] ?? '';
    if (!$password) {
        $errors[] = 'password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'password must be at least 8 characters';
    }

    // ── Optional fields ───────────────────────────────────────
    $id_number = $r['id_number'] ?? '' ?: null;
    if ($id_number && id_number_exists($pdo, $id_number)) {
        $errors[] = "id_number '$id_number' already exists";
    }

    $contact_number = $r['contact_number'] ?? '' ?: null;
    $position       = $r['position']       ?? '' ?: null;

    $department_id = null;
    if (!empty($r['department'])) {
        $dept_key      = strtolower(trim($r['department']));
        $department_id = $depts_lookup[$dept_key] ?? null;
        if (!$department_id) $errors[] = "department '{$r['department']}' not found";
    }

    // ── Skip row if errors ────────────────────────────────────
    if ($errors) {
        $skipped[] = ['row' => $row_num, 'email' => $email ?: "row {$row_num}", 'reasons' => $errors];
        continue;
    }

    // ── Insert ────────────────────────────────────────────────
    try {
        create_user($pdo, [
            'full_name'      => $full_name,
            'email'          => $email,
            'id_number'      => $id_number,
            'contact_number' => $contact_number,
            'position'       => $position,
            'department_id'  => $department_id,
            'role_id'        => $role_id,
            'password'       => $password,
        ]);
        $imported++;
    } catch (PDOException $e) {
        $skipped[] = ['row' => $row_num, 'email' => $email, 'reasons' => ['Database error: ' . $e->getMessage()]];
    }
}

fclose($handle);

echo json_encode([
    'success'  => true,
    'imported' => $imported,
    'skipped'  => $skipped,
]);
