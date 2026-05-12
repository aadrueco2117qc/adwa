<?php
// modules/tickets/functions.php
// Database queries and helpers for Request Submission & Intake
date_default_timezone_set('Asia/Manila');

const TICKET_STAFF_ROLES = [1, 2, 3, 4, 8];

function is_ticket_staff(int $role_id): bool {
    return in_array($role_id, TICKET_STAFF_ROLES, true);
}

// ── Stats ─────────────────────────────────────────────────────

function get_ticket_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT COUNT(*)                        AS total,
               SUM(status = 'new')            AS t_new,
               SUM(status = 'assigned')       AS assigned,
               SUM(status = 'in_progress')    AS in_progress,
               SUM(status = 'on_hold')        AS on_hold,
               SUM(status = 'resolved')       AS resolved,
               SUM(status = 'closed')         AS closed,
               SUM(status = 'cancelled')      AS cancelled
        FROM tickets
    ")->fetch();

    return $row;
}

// ── Listing ───────────────────────────────────────────────────

function get_tickets(PDO $pdo, array $f = [], int $page = 1, int $per = 10): array {
    [$where, $params] = _ticket_where($f);
    $offset = ($page - 1) * $per;

    $sort_map = [
        'ticket_number' => 't.ticket_number',
        'priority'      => "FIELD(t.priority, 'critical', 'high', 'medium', 'low')",
        'status'        => 't.status',
        'created_at'    => 't.created_at',
        'updated_at'    => 't.updated_at',
    ];
    $sort_col = $sort_map[$f['sort_col'] ?? ''] ?? 't.updated_at';
    $sort_dir = strtoupper($f['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.ticket_number, t.title, t.status, t.priority, t.created_at, t.updated_at,
               t.is_event_support, t.assigned_team, t.on_hold_reason,
               r.full_name,
               COALESCE(a.asset_tag, t.asset_tag) AS asset_tag, t.asset_id,
               c.category_name,
               u.full_name AS assigned_to_name
        FROM tickets t
        LEFT JOIN users r            ON t.requester_id = r.user_id
        LEFT JOIN assets a           ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN users u            ON t.assigned_to = u.user_id
        WHERE $where
        ORDER BY $sort_col $sort_dir
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per, $offset]));
    return $stmt->fetchAll();
}

function count_tickets(PDO $pdo, array $f = []): int {
    [$where, $params] = _ticket_where($f);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets t
        LEFT JOIN users r  ON t.requester_id = r.user_id
        LEFT JOIN assets a ON t.asset_id     = a.asset_id
        WHERE $where
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function _ticket_where(array $f): array {
    $where  = ['1=1'];
    $params = [];

    // Base scoping (if restricted by role)
    if (!empty($f['requester_id'])) {
        $where[]  = 't.requester_id = ?';
        $params[] = (int) $f['requester_id'];
    }

    if (!empty($f['q'])) {
        $q = '%' . $f['q'] . '%';
        $where[] = '(t.ticket_number LIKE ? OR t.title LIKE ? OR r.full_name LIKE ? OR a.asset_tag LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($f['status'])) {
        $where[]  = 't.status = ?';
        $params[] = $f['status'];
    }
    if (!empty($f['priority'])) {
        $where[]  = 't.priority = ?';
        $params[] = $f['priority'];
    }
    
    return [implode(' AND ', $where), $params];
}

// ── Single Ticket ─────────────────────────────────────────────

function get_ticket_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("
        SELECT t.*,
               r.full_name,
               r.email,
               r.contact_number,
               d.department_name AS department,
               a.asset_tag AS asset_tag_linked, a.manufacturer, a.model AS asset_model,
               c.category_name,
               l.building, l.floor, l.room,
               w.warranty_end,
               u_assign.full_name AS assigned_to_name,
               u_app.full_name AS approved_by_name
        FROM tickets t
        LEFT JOIN users r            ON t.requester_id = r.user_id
        LEFT JOIN departments d      ON r.department_id = d.department_id
        LEFT JOIN assets a           ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN locations l        ON t.location_id = l.location_id
        LEFT JOIN asset_warranty w   ON a.asset_id = w.asset_id
        LEFT JOIN users u_assign     ON t.assigned_to = u_assign.user_id
        LEFT JOIN users u_app        ON t.approved_by = u_app.user_id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    
    if ($t && $t['warranty_end']) {
        $end = new DateTime($t['warranty_end']);
        $now = new DateTime();
        $t['warranty_status'] = $end >= $now
            ? 'Under Warranty (expires ' . $end->format('Y-m-d') . ')'
            : 'Warranty Expired (' . $end->format('Y-m-d') . ')';
    } else if ($t) {
        $t['warranty_status'] = '';
    }
    
    return $t;
}

// ── Attachments, Comments & Dynamic Fields ────────────────────

function get_ticket_attachments(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT ta.*, u.full_name AS uploaded_by_name
        FROM ticket_attachments ta
        LEFT JOIN users u ON ta.uploaded_by = u.user_id
        WHERE ta.ticket_id = ?
        ORDER BY ta.uploaded_at DESC
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_ticket_comments(PDO $pdo, int $id, bool $include_internal = true): array {
    $sql = "
        SELECT tc.*, u.full_name AS user_name, u.profile_picture
        FROM ticket_comments tc
        LEFT JOIN users u ON tc.user_id = u.user_id
        WHERE tc.ticket_id = ?
    ";
    if (!$include_internal) {
        $sql .= " AND tc.is_internal = 0 ";
    }
    $sql .= " ORDER BY tc.created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_ticket_dynamic_fields(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT field_name, field_value FROM ticket_dynamic_fields WHERE ticket_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function get_all_categories(PDO $pdo): array {
    $cats = $pdo->query("SELECT * FROM asset_categories ORDER BY category_name")->fetchAll();
    
    // Check if "Others" already exists in DB
    $has_others = false;
    foreach ($cats as $c) {
        if (strtolower($c['category_name']) === 'others') {
            $has_others = true;
            break;
        }
    }
    
    // If not in DB, add it as a virtual option for the UI
    if (!$has_others) {
        $cats[] = [
            'category_id' => 999, // A high ID for "Others"
            'category_name' => 'Others',
            'has_bulb_hours' => 0
        ];
    }
    
    return $cats;
}

function get_all_locations(PDO $pdo): array {
    return $pdo->query("SELECT * FROM locations ORDER BY building, floor, room")->fetchAll();
}

// ── Write Operations ──────────────────────────────────────────

function generate_ticket_number(PDO $pdo): string {
    $year = date('Y');
    $last = $pdo->query("
        SELECT ticket_number FROM tickets
        WHERE ticket_number LIKE 'TKT-$year-%'
        ORDER BY ticket_id DESC LIMIT 1
    ")->fetchColumn();

    if ($last) {
        $seq = (int) substr($last, strrpos($last, '-') + 1) + 1;
    } else {
        $seq = 1;
    }
    return 'TKT-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function calculate_priority(string $urgency, string $impact): string {
    // A simple matrix for Priority = Urgency x Impact
    $matrix = [
        'critical' => ['critical'=>'critical', 'high'=>'critical', 'medium'=>'high', 'low'=>'high'],
        'high'     => ['critical'=>'critical', 'high'=>'high',     'medium'=>'high', 'low'=>'medium'],
        'medium'   => ['critical'=>'high',     'high'=>'high',     'medium'=>'medium','low'=>'low'],
        'low'      => ['critical'=>'high',     'high'=>'medium',   'medium'=>'low',   'low'=>'low'],
    ];
    return $matrix[$urgency][$impact] ?? 'medium';
}

function create_ticket(PDO $pdo, array $d): int {
    $ticket_number = generate_ticket_number($pdo);
    $priority = calculate_priority($d['urgency'], $d['impact']);

    $pdo->prepare("
        INSERT INTO tickets
            (ticket_number, requester_id, asset_id, asset_tag, model, warranty_status,
             category_id, location_id, title, description,
             impact, urgency, priority, channel,
             is_event_support, request_type, preferred_window, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $ticket_number,
        $d['requester_id'],
        $d['asset_id'] ?: null,
        ($d['asset_tag'] ?? '') !== '' ? $d['asset_tag'] : null,
        ($d['model'] ?? '') !== '' ? $d['model'] : null,
        ($d['warranty_status'] ?? '') !== '' ? $d['warranty_status'] : null,
        $d['category_id'] ?: null,
        $d['location_id'] ?: null,
        $d['title'],
        $d['description'] ?: null,
        $d['impact'] ?? 'medium',
        $d['urgency'] ?? 'medium',
        $priority,
        $d['channel'] ?? 'web',
        $d['is_event_support'] ?? 0,
        $d['request_type'] ?? 'repair',
        $d['preferred_window'] ?: null,
        'new',
    ]);

    $ticket_id = (int) $pdo->lastInsertId();

    _save_ticket_dynamic_fields($pdo, $ticket_id, $d['dynamic_fields'] ?? []);

    return $ticket_id;
}

function update_ticket(PDO $pdo, int $id, array $d): void {
    $priority = calculate_priority($d['urgency'], $d['impact']);

    // Common (requester-editable) fields
    $sets   = [
        'title=?', 'description=?', 'impact=?', 'urgency=?', 'priority=?',
        'is_event_support=?', 'request_type=?', 'category_id=?', 'location_id=?',
        'asset_id=?', 'asset_tag=?', 'model=?', 'warranty_status=?', 'preferred_window=?',
    ];
    $params = [
        $d['title'],
        $d['description'],
        $d['impact'],
        $d['urgency'],
        $priority,
        $d['is_event_support'] ?? 0,
        $d['request_type'] ?? 'repair',
        $d['category_id'] ?: null,
        $d['location_id'] ?: null,
        $d['asset_id'] ?? null,
        ($d['asset_tag'] ?? '') !== '' ? $d['asset_tag'] : null,
        ($d['model'] ?? '') !== '' ? $d['model'] : null,
        ($d['warranty_status'] ?? '') !== '' ? $d['warranty_status'] : null,
        $d['preferred_window'] ?: null,
    ];

    // Staff-only fields (caller decides whether to include)
    if (array_key_exists('status', $d)) {
        $sets[]   = 'status=?';
        $params[] = $d['status'];
    }
    if (array_key_exists('on_hold_reason', $d)) {
        $sets[]   = 'on_hold_reason=?';
        $params[] = $d['on_hold_reason'] ?: null;
    }
    if (array_key_exists('assigned_to', $d)) {
        $sets[]   = 'assigned_to=?';
        $params[] = $d['assigned_to'] ?: null;
    }

    $params[] = $id;
    $pdo->prepare("UPDATE tickets SET " . implode(', ', $sets) . " WHERE ticket_id=?")->execute($params);

    if (!empty($d['status']) && in_array($d['status'], ['resolved', 'closed'], true)) {
        $col = $d['status'] === 'resolved' ? 'resolved_at' : 'closed_at';
        $pdo->prepare("UPDATE tickets SET $col = NOW() WHERE ticket_id=?")->execute([$id]);
    }

    if (array_key_exists('dynamic_fields', $d)) {
        $pdo->prepare("DELETE FROM ticket_dynamic_fields WHERE ticket_id = ?")->execute([$id]);
        _save_ticket_dynamic_fields($pdo, $id, $d['dynamic_fields'] ?? []);
    }
}

function _save_ticket_dynamic_fields(PDO $pdo, int $ticket_id, array $fields): void {
    if (!$fields) return;
    $stmt = $pdo->prepare("INSERT INTO ticket_dynamic_fields (ticket_id, field_name, field_value) VALUES (?, ?, ?)");
    foreach ($fields as $key => $val) {
        if (trim((string)$val) !== '') {
            $stmt->execute([$ticket_id, $key, $val]);
        }
    }
}

function check_duplicate_ticket(PDO $pdo, array $d, int $days = 7): ?int {
    // 1. Check by Asset ID (most reliable)
    if (!empty($d['asset_id'])) {
        $stmt = $pdo->prepare("
            SELECT ticket_id 
            FROM tickets 
            WHERE asset_id = ? 
              AND status NOT IN ('resolved', 'closed', 'cancelled')
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            LIMIT 1
        ");
        $stmt->execute([(int)$d['asset_id'], $days]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
    }

    // 2. Check by Requester + Title (catches rapid double-clicks or same-day re-submissions)
    $stmt = $pdo->prepare("
        SELECT ticket_id 
        FROM tickets 
        WHERE requester_id = ? 
          AND title = ?
          AND status NOT IN ('resolved', 'closed', 'cancelled')
          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        LIMIT 1
    ");
    $stmt->execute([(int)$d['requester_id'], $d['title']]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    return null;
}

function handle_ticket_uploads(PDO $pdo, int $ticket_id, int $user_id): void {
    if (empty($_FILES['attachments']['name'][0])) return;

    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'avi', 'webm', 'pdf'];
    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm',
        'application/pdf',
    ];
    $max_bytes = 10 * 1024 * 1024;

    $upload_dir = __DIR__ . '/../../public/uploads/tickets/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $stmt_att = $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_type, file_size_kb, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");

    $count = count($_FILES['attachments']['name']);
    for ($i = 0; $i < $count; $i++) {
        $tmp_name = $_FILES['attachments']['tmp_name'][$i];
        $name     = basename($_FILES['attachments']['name'][$i]);
        $size     = (int)$_FILES['attachments']['size'][$i];
        $error    = (int)$_FILES['attachments']['error'][$i];

        if ($error !== UPLOAD_ERR_OK || $size <= 0 || $size > $max_bytes) continue;
        if (!is_uploaded_file($tmp_name)) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_exts, true)) continue;

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp_name);
        if (!in_array($mime, $allowed_mimes, true)) continue;

        $safe_name = $ticket_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $upload_dir . $safe_name;

        if (move_uploaded_file($tmp_name, $dest)) {
            $stmt_att->execute([
                $ticket_id,
                $name,
                'public/uploads/tickets/' . $safe_name,
                $ext,
                (int) round($size / 1024),
                $user_id,
            ]);
        }
    }
}

function get_recommended_kb_articles(PDO $pdo, ?int $category_id = null, int $limit = 3): array {
    $sql = "SELECT article_id, title, content FROM kb_articles WHERE is_published = 1 ";
    $params = [];
    
    if ($category_id) {
        $sql .= "AND (category_id = ? OR category_id IS NULL) ";
        $params[] = $category_id;
    }
    
    $sql .= "ORDER BY (category_id IS NOT NULL) DESC, views DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Render Helpers ────────────────────────────────────────────

function ticket_status_badge(string $status): string {
    $labels = [
        'new'         => 'New',
        'assigned'    => 'Assigned',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'on_hold'     => 'On Hold',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
        'cancelled'   => 'Cancelled',
    ];
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="wo-badge badge-' . htmlspecialchars($status) . '"><span class="bdot"></span>' . $label . '</span>';
}

function ticket_priority_badge(?string $priority): string {
    if (!$priority) return '<span class="text-gray-300">—</span>';
    $label = ucfirst($priority);
    return '<span class="wo-badge badge-priority badge-' . htmlspecialchars($priority) . '">' . $label . '</span>';
}

function ticket_time_ago(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->days === 0 && $diff->h === 0 && $diff->i === 0) return 'Just now';
    if ($diff->days === 0 && $diff->h === 0) return $diff->i . ' min ago';
    if ($diff->days === 0) return $diff->h . ' hours ago';
    if ($diff->days === 1) return 'Yesterday';
    if ($diff->days < 7)   return $diff->days . ' days ago';
    if ($diff->days < 14)  return '1 week ago';
    if ($diff->days < 30)  return intdiv($diff->days, 7) . ' weeks ago';
    if ($diff->days < 60)  return '1 month ago';
    return intdiv($diff->days, 30) . ' months ago';
}
