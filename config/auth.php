<?php
// config/auth.php
// Authentication & access-control helpers.
// $pdo must already exist (created by config/db.php).

// ── Module access ─────────────────────────────────────────────

/**
 * Returns an array of module slugs the given role can access.
 */
function get_user_modules(PDO $pdo, int $role_id): array {
    $stmt = $pdo->prepare(
        "SELECT module_slug FROM role_modules WHERE role_id = ?"
    );
    $stmt->execute([$role_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Returns true if the role can access the given module slug.
 */
function can_access(PDO $pdo, int $role_id, string $module): bool {
    return in_array($module, get_user_modules($pdo, $role_id), true);
}

/**
 * Redirects to the denied page if the role cannot access the module.
 * Call this at the TOP of every module's index.php.
 */
function require_access(PDO $pdo, int $role_id, string $module): void {
    if (!can_access($pdo, $role_id, $module)) {
        header('Location: ' . BASE_URL . 'index.php?page=denied');
        exit;
    }
}

// ── Session guard ─────────────────────────────────────────────

/**
 * Ensures a user is logged in.
 * Redirects to login.php if no active session exists.
 * Call this in index.php before any module is loaded.
 */
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'modules/login.php');
        exit;
    }
}

// ── Login / logout ────────────────────────────────────────────

/**
 * Attempts to log in a user by email and password.
 *
 * Returns the user row array on success, or a string error
 * message on failure. The caller decides how to present errors.
 *
 * @return array|string  User row on success, error string on failure.
 */
function attempt_login(PDO $pdo, string $email, string $password): array|string {
    if ($email === '' || $password === '') {
        return 'Please enter your email and password.';
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, role_id, full_name, email, password_hash, is_active, profile_picture
         FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return 'Invalid email or password.';
    }

    if (!$user['is_active']) {
        return 'Your account has been deactivated. Contact the IT Administrator.';
    }

    return $user;
}

/**
 * Creates a fresh session for the given user row.
 * Must be called immediately after a successful login.
 */
function create_session(PDO $pdo, array $user): void {
    session_regenerate_id(true);

    $_SESSION['user_id']         = $user['user_id'];
    $_SESSION['role_id']         = $user['role_id'];
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['email']           = $user['email'];
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? '';

    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
        ->execute([$user['user_id']]);
}

/**
 * Destroys the current session and redirects to login.
 */
function logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
    header('Location: ' . BASE_URL . 'modules/login.php');
    exit;
}

// ── Role helpers ──────────────────────────────────────────────

/**
 * Returns the role name string for the current session user,
 * or null if not logged in or role not found.
 */
function current_role_name(PDO $pdo): ?string {
    if (!isset($_SESSION['role_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $stmt->execute([$_SESSION['role_id']]);
    return $stmt->fetchColumn() ?: null;
}
?>
