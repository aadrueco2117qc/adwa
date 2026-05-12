<?php
// modules/users/functions.php
// All database queries and helpers for the Users module.
// $pdo is provided by the guard; never create a new connection here.

// ── Stats ─────────────────────────────────────────────────────

function get_user_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT COUNT(*)             AS total,
               SUM(is_active = 1)  AS active,
               SUM(is_active = 0)  AS inactive
        FROM users
    ")->fetch();

    $by_role = $pdo->query("
        SELECT r.role_name, COUNT(u.user_id) AS cnt
        FROM roles r
        LEFT JOIN users u ON u.role_id = r.role_id AND u.is_active = 1
        GROUP BY r.role_id, r.role_name
        ORDER BY r.role_id
    ")->fetchAll();

    $row['by_role'] = $by_role;
    return $row;
}

// ── Where clause builder ──────────────────────────────────────

function _user_where(array $f): array {
    $where  = ['1=1'];
    $params = [];

    if (($f['q'] ?? '') !== '') {
        $where[]  = '(u.full_name LIKE ? OR u.email LIKE ? OR u.id_number LIKE ? OR u.position LIKE ?)';
        $like     = '%' . $f['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if (($f['role_id'] ?? 0) > 0) {
        $where[]  = 'u.role_id = ?';
        $params[] = (int)$f['role_id'];
    }
    if (($f['dept_id'] ?? 0) > 0) {
        $where[]  = 'u.department_id = ?';
        $params[] = (int)$f['dept_id'];
    }
    if (($f['status'] ?? '') === 'active') {
        $where[] = 'u.is_active = 1';
    } elseif (($f['status'] ?? '') === 'inactive') {
        $where[] = 'u.is_active = 0';
    }

    return [implode(' AND ', $where), $params];
}

// ── Listing ───────────────────────────────────────────────────

function get_users(PDO $pdo, array $f = [], int $page = 1, int $per = 15): array {
    [$where, $params] = _user_where($f);
    $offset = ($page - 1) * $per;

    $sort_map = [
        'full_name'   => 'u.full_name',
        'email'       => 'u.email',
        'role_name'   => 'r.role_name',
        'department'  => 'd.department_name',
        'is_active'   => 'u.is_active',
        'last_login'  => 'u.last_login',
        'created_at'  => 'u.created_at',
    ];
    $sort_col = $sort_map[$f['sort_col'] ?? ''] ?? 'u.created_at';
    $sort_dir = strtoupper($f['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.email, u.id_number, u.position,
               u.contact_number, u.is_active, u.last_login, u.created_at,
               r.role_name, d.department_name
        FROM   users u
        LEFT JOIN roles       r ON u.role_id       = r.role_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE  $where
        ORDER  BY $sort_col $sort_dir
        LIMIT  ? OFFSET ?
    ");
    $params[] = $per;
    $params[] = $offset;
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_users(PDO $pdo, array $f = []): int {
    [$where, $params] = _user_where($f);
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM   users u
        LEFT JOIN roles       r ON u.role_id       = r.role_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE  $where
    ");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// ── Single record ─────────────────────────────────────────────

function get_user_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name, d.department_name
        FROM   users u
        LEFT JOIN roles       r ON u.role_id       = r.role_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE  u.user_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ── Lookups ───────────────────────────────────────────────────

/**
 * Returns roles for dropdowns.
 * $exclude_super_admin = true (default): hides super_admin — used in all create/edit forms.
 * Pass false only for internal logic checks.
 */
function get_roles(PDO $pdo, bool $exclude_super_admin = true): array {
    if ($exclude_super_admin) {
        return $pdo->query(
            "SELECT role_id, role_name FROM roles WHERE role_name != 'super_admin' ORDER BY role_id"
        )->fetchAll();
    }
    return $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll();
}

function get_departments_list(PDO $pdo): array {
    return $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll();
}

// ── Uniqueness checks ─────────────────────────────────────────

function email_exists(PDO $pdo, string $email, int $exclude_id = 0): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([strtolower(trim($email)), $exclude_id]);
    return (int)$stmt->fetchColumn() > 0;
}

function id_number_exists(PDO $pdo, string $id_number, int $exclude_id = 0): bool {
    if ($id_number === '') return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_number = ? AND user_id != ?");
    $stmt->execute([$id_number, $exclude_id]);
    return (int)$stmt->fetchColumn() > 0;
}

// ── Write ─────────────────────────────────────────────────────

function create_user(PDO $pdo, array $d): int {
    $stmt = $pdo->prepare("
        INSERT INTO users
            (email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        strtolower(trim($d['email'])),
        password_hash($d['password'], PASSWORD_BCRYPT),
        trim($d['full_name']),
        $d['id_number']      ?: null,
        $d['contact_number'] ?: null,
        $d['position']       ?: null,
        $d['department_id']  ?: null,
        (int)$d['role_id'],
    ]);
    return (int)$pdo->lastInsertId();
}

function update_user(PDO $pdo, int $id, array $d): void {
    if (!empty($d['password'])) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET    email=?, full_name=?, id_number=?, contact_number=?,
                   position=?, department_id=?, role_id=?, password_hash=?
            WHERE  user_id=?
        ");
        $stmt->execute([
            strtolower(trim($d['email'])),
            trim($d['full_name']),
            $d['id_number']      ?: null,
            $d['contact_number'] ?: null,
            $d['position']       ?: null,
            $d['department_id']  ?: null,
            (int)$d['role_id'],
            password_hash($d['password'], PASSWORD_BCRYPT),
            $id,
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET    email=?, full_name=?, id_number=?, contact_number=?,
                   position=?, department_id=?, role_id=?
            WHERE  user_id=?
        ");
        $stmt->execute([
            strtolower(trim($d['email'])),
            trim($d['full_name']),
            $d['id_number']      ?: null,
            $d['contact_number'] ?: null,
            $d['position']       ?: null,
            $d['department_id']  ?: null,
            (int)$d['role_id'],
            $id,
        ]);
    }
}

function set_user_active(PDO $pdo, int $id, int $active): void {
    $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?")
        ->execute([$active, $id]);
}

// ── Display helpers ───────────────────────────────────────────

function role_badge(string $role_name): string {
    $styles = [
        'super_admin'      => 'bg-red-100 text-red-700',
        'admin'            => 'bg-purple-100 text-purple-700',
        'it_manager'       => 'bg-blue-100 text-blue-700',
        'it_staff'         => 'bg-sky-100 text-sky-700',
        'technician'       => 'bg-orange-100 text-orange-700',
        'faculty'          => 'bg-teal-100 text-teal-700',
        'department_staff' => 'bg-yellow-100 text-yellow-700',
        'student'          => 'bg-gray-100 text-gray-600',
    ];
    $labels = [
        'super_admin'      => 'Super Admin',
        'admin'            => 'Admin',
        'it_manager'       => 'IT Manager',
        'it_staff'         => 'IT Staff',
        'technician'       => 'Technician',
        'faculty'          => 'Faculty',
        'department_staff' => 'Dept. Staff',
        'student'          => 'Student',
    ];
    $cls   = $styles[$role_name] ?? 'bg-gray-100 text-gray-600';
    $label = $labels[$role_name] ?? htmlspecialchars(ucwords(str_replace('_', ' ', $role_name)));
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold $cls\">$label</span>";
}

function user_status_badge(int $is_active): string {
    if ($is_active) {
        return '<span class="u-badge u-badge-active"><span class="bdot"></span>Active</span>';
    }
    return '<span class="u-badge u-badge-inactive"><span class="bdot"></span>Inactive</span>';
}

function user_time_ago(?string $dt): string {
    if (!$dt || $dt === '0000-00-00 00:00:00') {
        return '<span class="text-gray-300 italic text-xs">Never</span>';
    }
    $ts   = strtotime($dt);
    $diff = time() - $ts;
    if ($diff < 60)     return '<span class="text-xs text-gray-500">just now</span>';
    if ($diff < 3600)   return '<span class="text-xs text-gray-500">' . floor($diff / 60) . 'm ago</span>';
    if ($diff < 86400)  return '<span class="text-xs text-gray-500">' . floor($diff / 3600) . 'h ago</span>';
    if ($diff < 604800) return '<span class="text-xs text-gray-500">' . floor($diff / 86400) . 'd ago</span>';
    return '<span class="text-xs text-gray-500">' . date('M j, Y', $ts) . '</span>';
}

function role_display_name(string $role_name): string {
    $labels = [
        'super_admin'      => 'Super Admin',
        'admin'            => 'Admin',
        'it_manager'       => 'IT Manager',
        'it_staff'         => 'IT Staff',
        'technician'       => 'Technician',
        'faculty'          => 'Faculty',
        'department_staff' => 'Department Staff',
        'student'          => 'Student',
    ];
    return $labels[$role_name] ?? ucwords(str_replace('_', ' ', $role_name));
}

/**
 * Returns the role_name of the currently logged-in user.
 * Uses the session role_id — always accurate after login.
 */
function current_user_role(PDO $pdo): string {
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $stmt->execute([$_SESSION['role_id'] ?? 0]);
    $cache = $stmt->fetchColumn() ?: '';
    return $cache;
}

/**
 * Permission check: can $actor_role act on $target_role?
 * Super admins are protected — no one can deactivate them.
 * Admins cannot act on other admins (only super_admin can).
 */
function can_manage_user(string $actor_role, string $target_role): bool {
    if ($target_role === 'super_admin') return false;          // nobody touches super_admin
    if ($actor_role === 'super_admin')  return true;           // super_admin can manage everyone else
    if ($actor_role === 'admin' && $target_role === 'admin') return false; // admin can't touch admin
    return $actor_role === 'admin';                            // only admins+ reach this point
}

function user_initials(string $name): string {
    $parts = array_filter(explode(' ', trim($name)));
    $init  = '';
    foreach ($parts as $p) {
        $init .= strtoupper($p[0]);
        if (strlen($init) >= 2) break;
    }
    return $init ?: 'U';
}
