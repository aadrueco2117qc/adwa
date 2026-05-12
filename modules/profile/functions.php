<?php
// modules/profile/functions.php
// Database helpers for the Profile module.

function get_profile(PDO $pdo, int $user_id): array|false {
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name, d.department_name
        FROM   users u
        LEFT JOIN roles       r ON u.role_id       = r.role_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE  u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function update_profile_info(PDO $pdo, int $user_id, array $d): void {
    $pdo->prepare("
        UPDATE users
        SET    full_name=?, contact_number=?, position=?
        WHERE  user_id=?
    ")->execute([
        trim($d['full_name']),
        trim($d['contact_number']) ?: null,
        trim($d['position'])       ?: null,
        $user_id,
    ]);
}

function update_profile_picture(PDO $pdo, int $user_id, string $path): void {
    $pdo->prepare("UPDATE users SET profile_picture=? WHERE user_id=?")
        ->execute([$path, $user_id]);
}

function get_password_hash(PDO $pdo, int $user_id): string {
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    return (string)$stmt->fetchColumn();
}

function update_password(PDO $pdo, int $user_id, string $new_password): void {
    $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")
        ->execute([password_hash($new_password, PASSWORD_BCRYPT), $user_id]);
}

// ── Activity History ───────────────────────────────────────────

function get_profile_asset_activity(PDO $pdo, int $user_id, int $limit = 15): array {
    try {
        $stmt = $pdo->prepare("
            SELECT l.log_id, l.field_name, l.old_value, l.new_value,
                   l.changed_at, l.change_reason,
                   a.asset_tag, a.model, a.asset_id
            FROM   asset_audit_log l
            LEFT JOIN assets a ON l.asset_id = a.asset_id
            WHERE  l.changed_by = ?
            ORDER  BY l.changed_at DESC
            LIMIT  ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_profile_created_assets(PDO $pdo, int $user_id, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT a.asset_id, a.asset_tag, a.model, a.status, a.created_at,
                   c.category_name
            FROM   assets a
            LEFT JOIN asset_categories c ON a.category_id = c.category_id
            WHERE  a.created_by = ?
            ORDER  BY a.created_at DESC
            LIMIT  ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_profile_tickets(PDO $pdo, int $user_id, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM tickets
            WHERE  submitted_by = ?
            ORDER  BY created_at DESC
            LIMIT  ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_profile_work_orders(PDO $pdo, int $user_id, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM work_orders
            WHERE  assigned_to = ?
            ORDER  BY created_at DESC
            LIMIT  ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// ── Display helpers ────────────────────────────────────────────

function profile_time_ago(?string $dt): string {
    if (!$dt || $dt === '0000-00-00 00:00:00') {
        return '<span class="text-gray-400 italic text-xs">Never</span>';
    }
    $ts   = strtotime($dt);
    $diff = time() - $ts;
    if ($diff < 60)     return '<span class="text-xs text-gray-500">just now</span>';
    if ($diff < 3600)   return '<span class="text-xs text-gray-500">' . floor($diff / 60) . 'm ago</span>';
    if ($diff < 86400)  return '<span class="text-xs text-gray-500">' . floor($diff / 3600) . 'h ago</span>';
    if ($diff < 604800) return '<span class="text-xs text-gray-500">' . floor($diff / 86400) . 'd ago</span>';
    return '<span class="text-xs text-gray-500">' . date('M j, Y', $ts) . '</span>';
}

function asset_status_dot(string $status): string {
    $map = [
        'active'  => 'bg-green-500',
        'spare'   => 'bg-yellow-400',
        'retired' => 'bg-gray-400',
    ];
    $cls = $map[$status] ?? 'bg-gray-400';
    return "<span class=\"inline-block w-2 h-2 rounded-full $cls\"></span>";
}
