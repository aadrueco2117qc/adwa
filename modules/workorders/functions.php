<?php
// modules/workorders/functions.php
// All database queries and helpers for the Work Orders module.
date_default_timezone_set('Asia/Manila');
// $pdo is provided by guard.php; never create a new connection here.

// ── Stats ─────────────────────────────────────────────────────

function get_wo_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT COUNT(*)                        AS total,
               SUM(status = 'new')            AS wo_new,
               SUM(status = 'assigned')       AS assigned,
               SUM(status = 'scheduled')      AS scheduled,
               SUM(status = 'in_progress')    AS in_progress,
               SUM(status = 'on_hold')        AS on_hold,
               SUM(status = 'resolved')       AS resolved,
               SUM(status = 'closed')         AS closed
        FROM work_orders
    ")->fetch();

    // Overdue: scheduled_end is past and WO is not resolved/closed
    $row['overdue'] = (int) $pdo->query("
        SELECT COUNT(*) FROM work_orders
        WHERE scheduled_end < NOW()
          AND status NOT IN ('resolved','closed')
          AND scheduled_end IS NOT NULL
    ")->fetchColumn();

    return $row;
}

// ── Listing ───────────────────────────────────────────────────

function get_work_orders(PDO $pdo, array $f = [], int $page = 1, int $per = 10): array {
    [$where, $params] = _wo_where($f);
    $offset = ($page - 1) * $per;

    $sort_map = [
        'wo_number'      => 'w.wo_number',
        'ticket_number'  => 't.ticket_number',
        'assigned_to'    => 'u.full_name',
        'status'         => 'w.status',
        'scheduled_start'=> 'w.scheduled_start',
        'updated_at'     => 'w.updated_at',
    ];
    $sort_col = $sort_map[$f['sort_col'] ?? ''] ?? 'w.updated_at';
    $sort_dir = strtoupper($f['sort_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT w.wo_id, w.wo_number, w.wo_type, w.status, w.is_rma,
               w.scheduled_start, w.scheduled_end, w.updated_at,
               w.on_hold_reason,
               t.ticket_number, t.ticket_id, t.priority, t.title AS ticket_title,
               a.asset_tag, a.asset_id,
               u.full_name AS technician_name
        FROM work_orders w
        LEFT JOIN tickets t ON w.ticket_id = t.ticket_id
        LEFT JOIN assets  a ON t.asset_id  = a.asset_id
        LEFT JOIN users   u ON w.assigned_to = u.user_id
        WHERE $where
        ORDER BY $sort_col $sort_dir
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per, $offset]));
    return $stmt->fetchAll();
}

function count_work_orders(PDO $pdo, array $f = []): int {
    [$where, $params] = _wo_where($f);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM work_orders w
        LEFT JOIN tickets t ON w.ticket_id = t.ticket_id
        LEFT JOIN users   u ON w.assigned_to = u.user_id
        WHERE $where
    ");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function _wo_where(array $f): array {
    $where  = ['1=1'];
    $params = [];

    if (!empty($f['q'])) {
        $q = '%' . $f['q'] . '%';
        $where[] = '(w.wo_number LIKE ? OR t.ticket_number LIKE ? OR u.full_name LIKE ? OR w.notes LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($f['status'])) {
        if ($f['status'] === 'overdue') {
            $where[] = "w.scheduled_end < NOW() AND w.status NOT IN ('resolved','closed') AND w.scheduled_end IS NOT NULL";
        } else {
            $where[]  = 'w.status = ?';
            $params[] = $f['status'];
        }
    }
    if (!empty($f['wo_type'])) {
        $where[]  = 'w.wo_type = ?';
        $params[] = $f['wo_type'];
    }
    if (!empty($f['assigned_to'])) {
        $where[]  = 'w.assigned_to = ?';
        $params[] = (int) $f['assigned_to'];
    }
    if (!empty($f['priority'])) {
        $where[]  = 't.priority = ?';
        $params[] = $f['priority'];
    }

    return [implode(' AND ', $where), $params];
}

// ── Single WO ─────────────────────────────────────────────────

function get_wo_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare("
        SELECT w.*,
               t.ticket_number, t.ticket_id, t.title AS ticket_title,
               t.priority, t.impact, t.urgency, t.description AS ticket_desc,
               a.asset_tag, a.asset_id, a.manufacturer, a.model,
               a.category_id,
               c.category_name,
               l.building, l.floor, l.room,
               u_tech.full_name AS technician_name,
               u_by.full_name   AS assigned_by_name,
               u_cr.full_name   AS created_by_name
        FROM work_orders w
        LEFT JOIN tickets          t      ON w.ticket_id   = t.ticket_id
        LEFT JOIN assets           a      ON t.asset_id    = a.asset_id
        LEFT JOIN asset_categories c      ON a.category_id = c.category_id
        LEFT JOIN locations        l      ON t.location_id = l.location_id
        LEFT JOIN users            u_tech ON w.assigned_to = u_tech.user_id
        LEFT JOIN users            u_by   ON w.assigned_by = u_by.user_id
        LEFT JOIN users            u_cr   ON w.created_by  = u_cr.user_id
        WHERE w.wo_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ── Checklist ─────────────────────────────────────────────────

function get_wo_checklist(PDO $pdo, int $wo_id, ?int $category_id): array {
    // Find the checklist for this WO's asset category, or fallback to General
    $checklist_id = null;
    if ($category_id) {
        $stmt = $pdo->prepare("SELECT checklist_id FROM wo_checklists WHERE category_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$category_id]);
        $checklist_id = $stmt->fetchColumn();
    }
    if (!$checklist_id) {
        $checklist_id = $pdo->query("SELECT checklist_id FROM wo_checklists WHERE category_id IS NULL AND is_active = 1 LIMIT 1")->fetchColumn();
    }
    if (!$checklist_id) return [];

    $stmt = $pdo->prepare("
        SELECT ci.item_id, ci.item_text, ci.is_mandatory, ci.requires_photo, ci.sort_order,
               cc.is_done, cc.notes AS completion_notes, cc.completed_at,
               u.full_name AS completed_by_name
        FROM wo_checklist_items ci
        LEFT JOIN wo_checklist_completions cc ON ci.item_id = cc.item_id AND cc.wo_id = ?
        LEFT JOIN users u ON cc.completed_by = u.user_id
        WHERE ci.checklist_id = ?
        ORDER BY ci.sort_order
    ");
    $stmt->execute([$wo_id, $checklist_id]);
    return $stmt->fetchAll();
}

function get_checklist_name(PDO $pdo, ?int $category_id): string {
    $name = null;
    if ($category_id) {
        $stmt = $pdo->prepare("SELECT checklist_name FROM wo_checklists WHERE category_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$category_id]);
        $name = $stmt->fetchColumn();
    }
    return $name ?: 'General Repair Checklist';
}

// ── Parts Used ────────────────────────────────────────────────

function get_wo_parts(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT pu.*, p.part_name, p.part_number, p.unit_cost,
               u.full_name AS used_by_name
        FROM wo_parts_used pu
        JOIN parts_inventory p ON pu.part_id = p.part_id
        LEFT JOIN users u ON pu.used_by = u.user_id
        WHERE pu.wo_id = ?
        ORDER BY pu.used_at DESC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

// ── Time Logs ─────────────────────────────────────────────────

function get_wo_time_logs(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT tl.*, u.full_name AS technician_name
        FROM wo_time_logs tl
        LEFT JOIN users u ON tl.technician_id = u.user_id
        WHERE tl.wo_id = ?
        ORDER BY tl.logged_at ASC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

function compute_total_time(array $logs): int {
    // Two sources of truth: explicit elapsed_ms on stop/pause rows (preferred,
    // sent by the technician's completion payload), and start→pause/stop
    // intervals derived from logged_at timestamps. Sum them in seconds.
    $elapsed_total_ms = 0;
    $interval_total_s = 0;
    $started = null;
    foreach ($logs as $log) {
        $elapsed = (int)($log['elapsed_ms'] ?? 0);
        if ($elapsed > 0 && in_array($log['action'], ['stop', 'pause'], true)) {
            $elapsed_total_ms += $elapsed;
            $started = null;
            continue;
        }
        if (in_array($log['action'], ['start', 'resume'], true)) {
            $started = strtotime($log['logged_at']);
        } elseif (in_array($log['action'], ['pause', 'stop'], true) && $started) {
            $interval_total_s += strtotime($log['logged_at']) - $started;
            $started = null;
        }
    }
    return (int)floor($elapsed_total_ms / 1000) + $interval_total_s;
}

function format_duration(int $seconds): string {
    if ($seconds < 60)   return $seconds . 's';
    if ($seconds < 3600) return floor($seconds / 60) . 'm';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return $h . 'h ' . ($m > 0 ? $m . 'm' : '');
}

// ── Media ─────────────────────────────────────────────────────

function get_wo_media(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name AS uploaded_by_name
        FROM wo_media m
        LEFT JOIN users u ON m.uploaded_by = u.user_id
        WHERE m.wo_id = ?
        ORDER BY m.uploaded_at DESC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

// ── Sign-off ──────────────────────────────────────────────────

function get_wo_signoff(PDO $pdo, int $wo_id): array|false {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name AS signer_full_name
        FROM wo_signoff s
        LEFT JOIN users u ON s.signed_by_user_id = u.user_id
        WHERE s.wo_id = ?
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetch();
}

// ── Notes ─────────────────────────────────────────────────────

function get_wo_notes(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT n.note_id, n.note_type, n.note_text, n.is_voice, n.voice_path, n.added_at,
               u.full_name AS added_by_name
        FROM wo_notes n
        LEFT JOIN users u ON n.added_by = u.user_id
        WHERE n.wo_id = ? AND n.is_private = 0
        ORDER BY n.added_at DESC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}


function check_wo_conflict(PDO $pdo, int $assigned_to, string $start, string $end, int $exclude_wo_id = 0, int $ticket_id = 0): array|false {
    $BUFFER_MINS = 15;
    $startTime = new DateTime($start);
    $endTime   = new DateTime($end);

    // 1. Check Technician Conflict
    $existing = $pdo->prepare("
        SELECT wo_id, wo_number, scheduled_start, scheduled_end
        FROM work_orders
        WHERE assigned_to = ?
          AND wo_id != ?
          AND status NOT IN ('closed', 'cancelled')
          AND DATE(scheduled_start) = DATE(?)
    ");
    $existing->execute([$assigned_to, $exclude_wo_id, $start]);
    $jobs = $existing->fetchAll();

    foreach ($jobs as $job) {
        $jStart = new DateTime($job['scheduled_start']);
        $jEnd   = new DateTime($job['scheduled_end']);
        $totalBuffer = $BUFFER_MINS + 15; 
        $jStartWithBuffer = (clone $jStart)->modify("-{$totalBuffer} minutes");
        $jEndWithBuffer   = (clone $jEnd)->modify("+{$totalBuffer} minutes");

        if ($startTime < $jEndWithBuffer && $endTime > $jStartWithBuffer) {
            return ['type' => 'technician', 'data' => $job];
        }
    }

    // 2. Check Room Conflict
    if ($ticket_id > 0) {
        $locStmt = $pdo->prepare("SELECT location_id FROM tickets WHERE ticket_id = ?");
        $locStmt->execute([$ticket_id]);
        $locId = $locStmt->fetchColumn();

        if ($locId) {
            $roomQuery = $pdo->prepare("
                SELECT wo.wo_id, wo.wo_number, wo.scheduled_start, wo.scheduled_end
                FROM work_orders wo
                JOIN tickets t ON wo.ticket_id = t.ticket_id
                WHERE t.location_id = ?
                  AND wo.wo_id != ?
                  AND wo.status NOT IN ('closed', 'cancelled')
                  AND DATE(wo.scheduled_start) = DATE(?)
            ");
            $roomQuery->execute([$locId, $exclude_wo_id, $start]);
            $roomJobs = $roomQuery->fetchAll();

            foreach ($roomJobs as $job) {
                $jStart = new DateTime($job['scheduled_start']);
                $jEnd   = new DateTime($job['scheduled_end']);
                // Rooms don't strictly need "travel time" but might need "setup/reset buffer"
                $jStartWithBuffer = (clone $jStart)->modify("-{$BUFFER_MINS} minutes");
                $jEndWithBuffer   = (clone $jEnd)->modify("+{$BUFFER_MINS} minutes");

                if ($startTime < $jEndWithBuffer && $endTime > $jStartWithBuffer) {
                    return ['type' => 'room', 'data' => $job];
                }
            }
        }
    }

    return false;
}

// ── Assignment History ────────────────────────────────────────

function get_wo_assignment_history(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT al.*,
               uf.full_name AS from_name,
               ut.full_name AS to_name,
               ub.full_name AS by_name
        FROM wo_assignment_log al
        LEFT JOIN users uf ON al.assigned_from = uf.user_id
        LEFT JOIN users ut ON al.assigned_to   = ut.user_id
        LEFT JOIN users ub ON al.assigned_by   = ub.user_id
        WHERE al.wo_id = ?
        ORDER BY al.assigned_at DESC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

// ── Lookups ───────────────────────────────────────────────────

function get_technicians(PDO $pdo): array {
    return $pdo->query("
        SELECT user_id, full_name FROM users
        WHERE role_id = 4 AND is_active = 1
        ORDER BY full_name
    ")->fetchAll();
}

function get_all_technicians(PDO $pdo): array {
    // Includes IT staff (role 3) and technicians (role 4) for assignment
    return $pdo->query("
        SELECT user_id, full_name FROM users
        WHERE role_id IN (3, 4) AND is_active = 1
        ORDER BY full_name
    ")->fetchAll();
}

function get_available_tickets(PDO $pdo): array {
    return $pdo->query("
        SELECT t.ticket_id, t.ticket_number, t.title, t.priority,
               a.asset_tag, t.warranty_status
        FROM tickets t
        LEFT JOIN assets a ON t.asset_id = a.asset_id
        WHERE t.status NOT IN ('closed','cancelled')
        ORDER BY t.created_at DESC
    ")->fetchAll();
}

function get_all_parts(PDO $pdo): array {
    return $pdo->query("
        SELECT part_id, part_number, part_name, quantity_on_hand, unit_cost
        FROM parts_inventory
        WHERE is_active = 1
        ORDER BY part_name ASC
    ")->fetchAll();
}

function get_related_kb_articles(PDO $pdo, ?int $category_id): array {
    if (!$category_id) return [];
    $stmt = $pdo->prepare("
        SELECT article_id, title, content, updated_at
        FROM kb_articles
        WHERE category_id = ?
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

// ── Write Operations ──────────────────────────────────────────

function generate_wo_number(PDO $pdo): string {
    $year = date('Y');
    $last = $pdo->query("
        SELECT wo_number FROM work_orders
        WHERE wo_number LIKE 'WO-$year-%'
        ORDER BY wo_id DESC LIMIT 1
    ")->fetchColumn();

    if ($last) {
        $seq = (int) substr($last, strrpos($last, '-') + 1) + 1;
    } else {
        $seq = 1;
    }
    return 'WO-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function create_work_order(PDO $pdo, array $d): int {
    $wo_number = generate_wo_number($pdo);

    $pdo->prepare("
        INSERT INTO work_orders
            (wo_number, ticket_id, wo_type, assigned_to, assigned_by,
             status, is_rma, scheduled_start, scheduled_end, notes, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $wo_number,
        $d['ticket_id'] ?: null,
        $d['wo_type'],
        $d['assigned_to'] ?: null,
        $d['assigned_by'] ?: null,
        'new',
        $d['is_rma'] ?? 0,
        $d['scheduled_start'] ?: null,
        $d['scheduled_end'] ?: null,
        $d['notes'] ?: null,
        $d['created_by'],
    ]);

    $wo_id = (int) $pdo->lastInsertId();

    // Log initial assignment if set
    if (!empty($d['assigned_to'])) {
        $pdo->prepare("
            INSERT INTO wo_assignment_log (wo_id, assigned_to, assigned_by, reason)
            VALUES (?,?,?,?)
        ")->execute([$wo_id, $d['assigned_to'], $d['created_by'], 'Initial assignment']);

        // Update status to assigned
        $pdo->prepare("UPDATE work_orders SET status = 'assigned' WHERE wo_id = ?")->execute([$wo_id]);
    }

    return $wo_id;
}

function update_work_order(PDO $pdo, int $id, array $d): void {
    $pdo->prepare("
        UPDATE work_orders SET
            wo_type=?, assigned_to=?, assigned_by=?,
            status=?, on_hold_reason=?, is_rma=?,
            scheduled_start=?, scheduled_end=?,
            notes=?, resolution_notes=?
        WHERE wo_id=?
    ")->execute([
        $d['wo_type'],
        $d['assigned_to'] ?: null,
        $d['assigned_by'] ?: null,
        $d['status'],
        $d['on_hold_reason'] ?: null,
        $d['is_rma'] ?? 0,
        $d['scheduled_start'] ?: null,
        $d['scheduled_end'] ?: null,
        $d['notes'] ?: null,
        $d['resolution_notes'] ?: null,
        $id,
    ]);
}

function set_wo_parts(PDO $pdo, int $wo_id, array $parts, int $user_id): void {
    // Only touches manager-side pre-allocations (is_preallocated = 1).
    // Technician-recorded usage rows (is_preallocated = 0, written by the
    // technician module's save_work_order_part) are never deleted here.
    $pdo->prepare("DELETE FROM wo_parts_used WHERE wo_id = ? AND is_preallocated = 1")
        ->execute([$wo_id]);

    if (empty($parts)) return;

    $stmt = $pdo->prepare("
        INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, is_preallocated, used_by, used_at)
        VALUES (?, ?, ?, 1, ?, NOW())
    ");

    foreach ($parts as $p) {
        if (!empty($p['id']) && !empty($p['qty'])) {
            $stmt->execute([$wo_id, $p['id'], $p['qty'], $user_id]);
        }
    }
}

function reassign_wo(PDO $pdo, int $wo_id, int $to, int $by, string $reason): void {
    $current = $pdo->prepare("SELECT assigned_to FROM work_orders WHERE wo_id = ?");
    $current->execute([$wo_id]);
    $from = $current->fetchColumn();

    $pdo->prepare("UPDATE work_orders SET assigned_to = ?, assigned_by = ?, status = 'assigned' WHERE wo_id = ?")->execute([$to, $by, $wo_id]);

    $pdo->prepare("
        INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason)
        VALUES (?,?,?,?,?)
    ")->execute([$wo_id, $from ?: null, $to, $by, $reason]);
}

// ── Close & Pass to Requester ─────────────────────────────────

/**
 * Closes a resolved work order and sends an in-app notification to the
 * requester asking them to rate the service.
 *
 * @throws RuntimeException if the WO is not found or not in 'resolved' status.
 */
function close_work_order(PDO $pdo, int $wo_id, int $closed_by): void {
    $pdo->beginTransaction();
    try {
        // Verify WO exists and is resolved
        $stmt = $pdo->prepare("
            SELECT wo.wo_id, wo.ticket_id, wo.status
            FROM work_orders wo
            WHERE wo.wo_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$wo_id]);
        $wo = $stmt->fetch();

        if (!$wo) {
            throw new RuntimeException('Work order not found.');
        }
        if ($wo['status'] !== 'resolved') {
            throw new RuntimeException('Work order must be resolved before it can be closed.');
        }

        // Close the work order
        $pdo->prepare("
            UPDATE work_orders
            SET status = 'closed',
                actual_end = COALESCE(actual_end, NOW()),
                updated_at = NOW()
            WHERE wo_id = ?
        ")->execute([$wo_id]);

        // Log the status change in audit_log
        $pdo->prepare("
            INSERT INTO audit_log (user_id, action, object_type, object_id, old_values, new_values, ip_address, created_at)
            VALUES (?, 'UPDATE', 'work_order', ?, ?, ?, ?, NOW())
        ")->execute([
            $closed_by,
            $wo_id,
            json_encode(['status' => 'resolved']),
            json_encode(['status' => 'closed']),
            $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        ]);

        // Notify the requester if a ticket is linked
        if (!empty($wo['ticket_id'])) {
            $ticket_id = (int)$wo['ticket_id'];

            $tstmt = $pdo->prepare("
                SELECT requester_id FROM tickets WHERE ticket_id = ?
            ");
            $tstmt->execute([$ticket_id]);
            $requester_id = $tstmt->fetchColumn();

            if ($requester_id) {
                $link = rtrim(BASE_URL, '/') . '/modules/tickets/view.php?id=' . $ticket_id;
                $pdo->prepare("
                    INSERT INTO notifications (user_id, title, body, link, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ")->execute([
                    (int)$requester_id,
                    'Service Completed — Please Rate',
                    'Your repair request has been completed and reviewed. Please take a moment to rate the service.',
                    $link,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function log_wo_audit(PDO $pdo, int $wo_id, string $field, mixed $old, mixed $new, int $by): void {    $pdo->prepare("
        INSERT INTO audit_log (user_id, action, object_type, object_id, old_values, new_values, ip_address, created_at)
        VALUES (?, 'UPDATE', 'work_order', ?, ?, ?, ?, NOW())
    ")->execute([
        $by,
        $wo_id,
        json_encode([$field => $old !== null ? (string) $old : null]),
        json_encode([$field => $new !== null ? (string) $new : null]),
        $_SERVER['REMOTE_ADDR'] ?? 'CLI',
    ]);
}

// ── Render Helpers ────────────────────────────────────────────

function wo_status_badge(string $status): string {
    $labels = [
        'new'         => 'New',
        'assigned'    => 'Assigned',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'on_hold'     => 'On Hold',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
    ];
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="wo-badge badge-' . htmlspecialchars($status) . '"><span class="bdot"></span>' . $label . '</span>';
}

function wo_type_badge(string $type): string {
    $labels = [
        'diagnosis'   => 'Diagnosis',
        'repair'      => 'Repair',
        'maintenance' => 'Maintenance',
        'follow_up'   => 'Follow-up',
    ];
    $label = $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    return '<span class="wo-badge badge-type badge-type-' . htmlspecialchars($type) . '">' . $label . '</span>';
}

function wo_priority_badge(?string $priority): string {
    if (!$priority) return '<span class="text-gray-300">—</span>';
    $label = ucfirst($priority);
    return '<span class="wo-badge badge-priority badge-' . htmlspecialchars($priority) . '">' . $label . '</span>';
}

function wo_hold_reason(?string $reason): string {
    if (!$reason) return '';
    $labels = [
        'waiting_parts'  => '⏳ Waiting for parts',
        'waiting_vendor'  => '⏳ Waiting for vendor',
        'waiting_access' => '🔒 Waiting for access',
        'other'          => '⏸ On hold',
    ];
    return '<span class="hold-reason">' . ($labels[$reason] ?? htmlspecialchars($reason)) . '</span>';
}

function wo_time_ago(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->days === 0) return 'Today';
    if ($diff->days === 1) return '1 day ago';
    if ($diff->days < 7)   return $diff->days . ' days ago';
    if ($diff->days < 14)  return '1 week ago';
    if ($diff->days < 30)  return intdiv($diff->days, 7) . ' weeks ago';
    if ($diff->days < 60)  return '1 month ago';
    return intdiv($diff->days, 30) . ' months ago';
}

// ── Sanitize POST ─────────────────────────────────────────────

function sanitize_wo_post(array $post, int $user_id): array {
    $str  = fn($k) => trim($post[$k] ?? '') ?: null;
    $int  = fn($k) => ((int)($post[$k] ?? 0)) > 0 ? (int)$post[$k] : null;

    return [
        'ticket_id'        => $int('ticket_id'),
        'wo_type'          => $post['wo_type'] ?? 'repair',
        'assigned_to'      => $int('assigned_to'),
        'assigned_by'      => !empty($post['assigned_to']) ? $user_id : null,
        'status'           => $post['status'] ?? 'new',
        'on_hold_reason'   => ($post['status'] ?? '') === 'on_hold' ? ($post['on_hold_reason'] ?? null) : null,
        'is_rma'           => isset($post['is_rma']) ? 1 : 0,
        'scheduled_start'  => $str('scheduled_start'),
        'scheduled_end'    => $str('scheduled_end'),
        'notes'            => $str('notes'),
        'resolution_notes' => $str('resolution_notes'),
        'created_by'       => $user_id,
    ];
}
