<?php
// modules/assets/functions.php
// All database queries and helpers for the Assets module.
// $pdo is provided by the hub; never create a new connection here.

// ── Stats ─────────────────────────────────────────────────────

function get_asset_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT COUNT(*)                 AS total,
               SUM(status = 'active')  AS active,
               SUM(status = 'spare')   AS spare,
               SUM(status = 'retired') AS retired
        FROM assets
    ")->fetch();

    $row['expiring_soon'] = (int) $pdo->query("
        SELECT COUNT(*) FROM asset_warranty
        WHERE warranty_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();

    return $row;
}

// ── Listing ───────────────────────────────────────────────────

function get_assets(PDO $pdo, array $f = [], int $page = 1, int $per = 10): array {
    [$where, $params] = _asset_where($f);
    $offset = ($page - 1) * $per;

    // Whitelist sortable columns — prevents SQL injection via $f['sort_col']
    $sort_map = [
        'asset_tag'     => 'a.asset_tag',
        'manufacturer'  => 'a.manufacturer',
        'category_name' => 'c.category_name',
        'building'      => 'l.building',
        'floor'         => 'l.floor',
        'room'          => 'l.room',
        'status'        => 'a.status',
        'warranty_end'  => 'w.warranty_end',
        'updated_at'    => 'a.updated_at',
    ];
    $sort_col = $sort_map[$f['sort_col'] ?? ''] ?? 'a.updated_at';
    $sort_dir = strtoupper($f['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT a.asset_id, a.asset_tag, a.manufacturer, a.model, a.status, a.updated_at,
               c.category_name,
               l.building, l.floor, l.room,
               w.warranty_end
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id = c.category_id
        LEFT JOIN locations        l ON a.location_id  = l.location_id
        LEFT JOIN asset_warranty   w ON a.asset_id     = w.asset_id
        WHERE $where
        ORDER BY $sort_col $sort_dir
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per, $offset]));
    return $stmt->fetchAll();
}

function count_assets(PDO $pdo, array $f = []): int {
    [$where, $params] = _asset_where($f);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM assets a
        LEFT JOIN locations      l ON a.location_id = l.location_id
        LEFT JOIN asset_warranty w ON a.asset_id    = w.asset_id
        WHERE $where
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function _asset_where(array $f): array {
    $where = ['1=1'];
    $params = [];

    if (!empty($f['q'])) {
        $q = '%' . $f['q'] . '%';
        $where[] = '(a.asset_tag LIKE ? OR a.manufacturer LIKE ? OR a.model LIKE ? OR a.serial_number LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($f['status'])) {
        if ($f['status'] === 'expiring') {
            $where[] = 'w.warranty_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } else {
            $where[]  = 'a.status = ?';
            $params[] = $f['status'];
        }
    }
    if (!empty($f['category_id'])) {
        $where[]  = 'a.category_id = ?';
        $params[] = (int) $f['category_id'];
    }
    if (!empty($f['building'])) {
        $where[]  = 'l.building = ?';
        $params[] = $f['building'];
    }
    if (!empty($f['floor'])) {
        $where[]  = 'l.floor = ?';
        $params[] = $f['floor'];
    }

    return [implode(' AND ', $where), $params];
}

// ── Single asset ──────────────────────────────────────────────

function get_asset_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("
        SELECT a.*, c.category_name, c.has_bulb_hours,
               l.building, l.floor, l.room,
               u.full_name AS owner_name,
               d.department_name,
               p.asset_tag AS parent_tag
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id     = c.category_id
        LEFT JOIN locations        l ON a.location_id     = l.location_id
        LEFT JOIN users            u ON a.owner_id        = u.user_id
        LEFT JOIN departments      d ON a.department_id   = d.department_id
        LEFT JOIN assets           p ON a.parent_asset_id = p.asset_id
        WHERE a.asset_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_asset_warranty(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("SELECT * FROM asset_warranty WHERE asset_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_asset_documents(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT * FROM asset_documents WHERE asset_id = ? AND is_latest = 1
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_asset_children(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT a.asset_id, a.asset_tag, a.model, a.status, c.category_name
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id = c.category_id
        WHERE a.parent_asset_id = ?
        ORDER BY a.asset_tag
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_asset_repair_history(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.ticket_number, t.title, t.status, t.priority,
               t.created_at, t.resolved_at,
               u.full_name AS requester_name,
               wo.wo_number
        FROM tickets t
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN work_orders wo ON wo.wo_id = (
            SELECT wo2.wo_id FROM work_orders wo2
            WHERE wo2.ticket_id = t.ticket_id
            ORDER BY wo2.wo_id DESC LIMIT 1
        )
        WHERE t.asset_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function has_open_tickets(PDO $pdo, int $asset_id): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE asset_id = ? AND status NOT IN ('closed','cancelled')
    ");
    $stmt->execute([$asset_id]);
    return (int)$stmt->fetchColumn() > 0;
}

function get_open_ticket_count(PDO $pdo, int $asset_id): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tickets
        WHERE asset_id = ? AND status NOT IN ('closed','cancelled')
    ");
    $stmt->execute([$asset_id]);
    return (int)$stmt->fetchColumn();
}

function get_departments(PDO $pdo): array {
    return $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll();
}

// ── Lookups ───────────────────────────────────────────────────

function get_categories(PDO $pdo): array {
    return $pdo->query("SELECT * FROM asset_categories ORDER BY category_name")->fetchAll();
}

function get_distinct_buildings(PDO $pdo): array {
    return $pdo->query("SELECT DISTINCT building FROM locations ORDER BY building")
               ->fetchAll(PDO::FETCH_COLUMN);
}

function get_distinct_floors(PDO $pdo): array {
    return $pdo->query("SELECT DISTINCT floor FROM locations ORDER BY floor")
               ->fetchAll(PDO::FETCH_COLUMN);
}

function get_all_locations(PDO $pdo): array {
    return $pdo->query("SELECT * FROM locations ORDER BY building, floor, room")->fetchAll();
}

function get_owners(PDO $pdo): array {
    return $pdo->query(
        "SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY full_name"
    )->fetchAll();
}

function get_top_level_assets(PDO $pdo, int $exclude = 0): array {
    // Exclude the asset itself AND every descendant — picking any of them as
    // a parent would create a circular relationship.
    $excluded_ids = [0];
    if ($exclude > 0) {
        $excluded_ids[] = $exclude;
        $stmt = $pdo->prepare("
            WITH RECURSIVE descendants AS (
                SELECT asset_id FROM assets WHERE parent_asset_id = ?
                UNION ALL
                SELECT a.asset_id FROM assets a
                JOIN descendants d ON a.parent_asset_id = d.asset_id
            )
            SELECT asset_id FROM descendants
        ");
        $stmt->execute([$exclude]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $excluded_ids[] = (int)$id;
        }
    }

    $placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT asset_id, asset_tag, manufacturer, model
         FROM assets
         WHERE parent_asset_id IS NULL AND asset_id NOT IN ($placeholders)
         ORDER BY asset_tag"
    );
    $stmt->execute($excluded_ids);
    return $stmt->fetchAll();
}

// ── Write operations ──────────────────────────────────────────

function create_asset(PDO $pdo, array $d): int {
    $pdo->prepare("
        INSERT INTO assets
            (asset_tag, serial_number, manufacturer, model, category_id, status,
             location_id, parent_asset_id, install_date, firmware_version,
             network_info, bulb_hours, department_id, owner_id, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $d['asset_tag'],       $d['serial_number'],    $d['manufacturer'], $d['model'],
        $d['category_id'],     $d['status'],           $d['location_id'],  $d['parent_asset_id'],
        $d['install_date'],    $d['firmware_version'],  $d['network_info'],
        $d['bulb_hours'],      $d['department_id'],     $d['owner_id'],     $d['created_by'],
    ]);
    return (int) $pdo->lastInsertId();
}

function update_asset(PDO $pdo, int $id, array $d): void {
    $pdo->prepare("
        UPDATE assets SET
            serial_number=?, manufacturer=?, model=?, category_id=?, status=?,
            location_id=?, parent_asset_id=?, install_date=?, firmware_version=?,
            network_info=?, bulb_hours=?, department_id=?, owner_id=?
        WHERE asset_id=?
    ")->execute([
        $d['serial_number'],   $d['manufacturer'],  $d['model'],        $d['category_id'],
        $d['status'],          $d['location_id'],   $d['parent_asset_id'], $d['install_date'],
        $d['firmware_version'], $d['network_info'], $d['bulb_hours'],   $d['department_id'],
        $d['owner_id'],        $id,
    ]);
}

function upsert_warranty(PDO $pdo, int $asset_id, array $d): void {
    if (empty($d['warranty_start']) || empty($d['warranty_end'])) return;
    $pdo->prepare("
        INSERT INTO asset_warranty
            (asset_id, warranty_start, warranty_end, coverage_type, vendor_name, contract_reference)
        VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            warranty_start=VALUES(warranty_start), warranty_end=VALUES(warranty_end),
            coverage_type=VALUES(coverage_type),   vendor_name=VALUES(vendor_name),
            contract_reference=VALUES(contract_reference)
    ")->execute([
        $asset_id,          $d['warranty_start'], $d['warranty_end'],
        $d['coverage_type'], $d['vendor_name'],   $d['contract_reference'],
    ]);
}

function log_asset_change(PDO $pdo, int $asset_id, string $field, mixed $old, mixed $new, int $by): void {
    $pdo->prepare("
        INSERT INTO asset_audit_log (asset_id, field_name, old_value, new_value, changed_by)
        VALUES (?,?,?,?,?)
    ")->execute([
        $asset_id, $field,
        $old !== null ? (string) $old : null,
        $new !== null ? (string) $new : null,
        $by,
    ]);
}

function log_asset_changes(PDO $pdo, int $id, array $old, array $new, int $by): void {
    $fields = [
        'serial_number','manufacturer','model','category_id','status',
        'location_id','parent_asset_id','install_date','firmware_version',
        'network_info','bulb_hours','department_id','owner_id',
    ];
    $norm = fn($v) => ($v === null || $v === '' || $v === 0 || $v === '0') ? '' : (string)$v;
    foreach ($fields as $f) {
        if ($norm($old[$f] ?? null) !== $norm($new[$f] ?? null)) {
            log_asset_change($pdo, $id, $f, $old[$f] ?? null, $new[$f] ?? null, $by);
        }
    }
}

// ── Validation ────────────────────────────────────────────────

function asset_tag_exists(PDO $pdo, string $tag, int $exclude = 0): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_tag = ? AND asset_id != ?");
    $stmt->execute([$tag, $exclude]);
    return (int) $stmt->fetchColumn() > 0;
}

function serial_exists(PDO $pdo, string $serial, string $mfr, int $exclude = 0): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM assets WHERE serial_number = ? AND manufacturer = ? AND asset_id != ?"
    );
    $stmt->execute([$serial, $mfr, $exclude]);
    return (int) $stmt->fetchColumn() > 0;
}

// ── Render helpers ────────────────────────────────────────────

function status_badge(string $status): string {
    return match ($status) {
        'active'  => '<span class="asset-badge badge-active"><span class="bdot"></span>Active</span>',
        'spare'   => '<span class="asset-badge badge-spare"><span class="bdot"></span>Spare</span>',
        'retired' => '<span class="asset-badge badge-retired"><span class="bdot"></span>Retired</span>',
        default   => '<span class="asset-badge badge-retired">' . htmlspecialchars(ucfirst($status)) . '</span>',
    };
}

function ticket_status_badge(string $status): string {
    $map = [
        'new'         => ['bg-gray-100 text-gray-600',    'bg-gray-400',   'New'],
        'assigned'    => ['bg-blue-50 text-blue-700',     'bg-blue-500',   'Assigned'],
        'scheduled'   => ['bg-indigo-50 text-indigo-700', 'bg-indigo-500', 'Scheduled'],
        'in_progress' => ['bg-amber-50 text-amber-700',   'bg-amber-500',  'In Progress'],
        'on_hold'     => ['bg-orange-50 text-orange-700', 'bg-orange-400', 'On Hold'],
        'resolved'    => ['bg-green-50 text-green-700',   'bg-green-500',  'Resolved'],
        'closed'      => ['bg-gray-100 text-gray-500',    'bg-gray-400',   'Closed'],
        'cancelled'   => ['bg-red-50 text-red-600',       'bg-red-400',    'Cancelled'],
    ];
    [$cls, $dot, $label] = $map[$status] ?? ['bg-gray-100 text-gray-500', 'bg-gray-400', ucfirst(str_replace('_', ' ', $status))];
    return "<span class=\"inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full $cls\"><span class=\"w-1.5 h-1.5 rounded-full $dot\"></span>$label</span>";
}

function ticket_priority_badge(?string $priority): string {
    if (!$priority) return '<span class="text-gray-300 text-xs">—</span>';
    $map = [
        'low'      => 'bg-gray-100 text-gray-500',
        'medium'   => 'bg-blue-50 text-blue-600',
        'high'     => 'bg-amber-50 text-amber-700',
        'critical' => 'bg-red-50 text-red-700',
    ];
    $cls = $map[$priority] ?? 'bg-gray-100 text-gray-500';
    return "<span class=\"text-xs font-semibold px-2 py-0.5 rounded-full $cls\">" . ucfirst($priority) . '</span>';
}

function cat_badge(string $name): string {
    return '<span class="asset-badge badge-cat">' . htmlspecialchars($name) . '</span>';
}

function warranty_cell(?string $end_date): string {
    if (!$end_date) return '<span class="text-gray-300">—</span>';
    $end = new DateTime($end_date);
    $now = new DateTime();
    if ($end < $now) return '<span class="asset-badge badge-warn">Expired</span>';
    $days = (int) $now->diff($end)->days;
    $fmt  = $end->format('M j, Y');
    if ($days <= 30) {
        return '<span class="warn-cell"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'
             . htmlspecialchars($fmt) . '</span>';
    }
    return htmlspecialchars($fmt);
}

function warranty_progress(?string $start, ?string $end): array {
    if (!$start || !$end) return ['pct' => 0, 'color' => 'bg-gray-200', 'days_left' => null, 'expired' => false];
    $s = new DateTime($start);
    $e = new DateTime($end);
    $n = new DateTime();
    if ($n >= $e) return ['pct' => 100, 'color' => 'bg-red-500', 'days_left' => 0, 'expired' => true];
    $total     = max(1, (int) $s->diff($e)->days);
    $elapsed   = max(0, (int) $s->diff($n)->days);
    $days_left = (int) $n->diff($e)->days;
    $pct   = min(100, (int) round($elapsed / $total * 100));
    $color = $days_left <= 30 ? 'bg-red-500' : 'bg-green-500';
    return ['pct' => $pct, 'color' => $color, 'days_left' => $days_left, 'expired' => false];
}

function time_ago(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->days === 0) return 'Today';
    if ($diff->days === 1) return '1 day ago';
    if ($diff->days < 7)  return $diff->days . ' days ago';
    if ($diff->days < 14) return '1 week ago';
    if ($diff->days < 30) return intdiv($diff->days, 7) . ' weeks ago';
    if ($diff->days < 60) return '1 month ago';
    return intdiv($diff->days, 30) . ' months ago';
}

function coverage_label(string $type): string {
    return match ($type) {
        'parts_and_labor' => 'Parts & Labor',
        'parts'           => 'Parts Only',
        'labor'           => 'Labor Only',
        'onsite'          => 'On-site',
        default           => ucfirst($type),
    };
}

// ── Sanitize POST inputs ──────────────────────────────────────

function sanitize_asset_post(array $post, int $created_by): array {
    $str  = fn($k) => trim($post[$k] ?? '') ?: null;
    $int  = fn($k) => ((int)($post[$k] ?? 0)) > 0 ? (int)$post[$k] : null;
    $date = fn($k) => !empty($post[$k]) ? $post[$k] : null;

    return [
        'asset_tag'         => strtoupper(trim($post['asset_tag'] ?? '')),
        'serial_number'     => $str('serial_number'),
        'manufacturer'      => trim($post['manufacturer'] ?? ''),
        'model'             => trim($post['model'] ?? ''),
        'category_id'       => (int)($post['category_id'] ?? 0),
        'status'            => $post['status'] ?? 'active',
        'location_id'       => $int('location_id'),
        'parent_asset_id'   => $int('parent_asset_id'),
        'install_date'      => $date('install_date'),
        'firmware_version'  => $str('firmware_version'),
        'network_info'      => $str('network_info'),
        'bulb_hours'        => !empty($post['bulb_hours']) ? (int)$post['bulb_hours'] : null,
        'department_id'     => $int('department_id'),
        'owner_id'          => $int('owner_id'),
        'created_by'        => $created_by,
        // Warranty
        'warranty_start'    => $date('warranty_start'),
        'warranty_end'      => $date('warranty_end'),
        'coverage_type'     => $post['coverage_type'] ?? 'parts_and_labor',
        'vendor_name'       => $str('vendor_name'),
        'contract_reference' => $str('contract_reference'),
    ];
}
