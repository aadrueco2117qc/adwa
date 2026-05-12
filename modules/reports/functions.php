<?php
// modules/reports/functions.php
// SLA Engine, Analytics, and Audit Log logic for MTRTS

require_once __DIR__ . '/../../config/sla.php';

/**
 * ── ANALYTICS QUERIES ─────────────────────────────────────────
 */

/**
 * Calculates SLA Compliance Rate
 */
function get_sla_compliance_stats(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(t.ticket_id) as total_tickets,
            SUM(CASE WHEN ts.is_response_breached = 0 THEN 1 ELSE 0 END) as met_response,
            SUM(CASE WHEN ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL THEN 1 ELSE 0 END) as met_resolution,
            ROUND(
                (SUM(CASE WHEN ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL THEN 1 ELSE 0 END) / 
                NULLIF(COUNT(t.ticket_id), 0)) * 100, 
            2) as compliance_rate
        FROM tickets t
        LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
        WHERE t.created_at >= ? AND t.created_at <= ?
          AND t.status != 'cancelled'
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Mean Time To Repair (MTTR) - Average time from Open to Resolved
 */
function get_mttr_stats(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_mttr_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as min_mttr_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as max_mttr_minutes
        FROM tickets
        WHERE status IN ('resolved', 'closed')
          AND resolved_at IS NOT NULL
          AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * First Time Fix Rate (FTFR) and Backlog
 */
function get_operational_stats(PDO $pdo, string $start_date, string $end_date): array {
    // FTFR: Resolved tickets that only required 1 work order
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT t.ticket_id) as total_resolved,
            SUM(CASE WHEN (SELECT COUNT(*) FROM work_orders w WHERE w.ticket_id = t.ticket_id) <= 1 THEN 1 ELSE 0 END) as ftfr_count
        FROM tickets t
        WHERE t.status IN ('resolved', 'closed')
          AND t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $ftfr_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $ftfr_rate = !empty($ftfr_data['total_resolved']) ? round(($ftfr_data['ftfr_count'] / $ftfr_data['total_resolved']) * 100, 1) : 0;

    // Total Tickets in range
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE created_at >= ? AND created_at <= ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $total_tickets = $stmt->fetchColumn();

    // Backlog: All currently open tickets
    $backlog = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();

    return [
        'ftfr_rate' => $ftfr_rate,
        'backlog' => $backlog,
        'total_tickets' => $total_tickets
    ];
}

/**
 * Resolution Trends (Chart Data)
 */
function get_resolution_trends(PDO $pdo, string $start_date, string $end_date): array {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(resolved_at) as resolve_date,
            COUNT(*) as ticket_count
        FROM tickets
        WHERE status IN ('resolved', 'closed')
          AND resolved_at IS NOT NULL
          AND resolved_at >= ? AND resolved_at <= ?
        GROUP BY DATE(resolved_at)
        ORDER BY resolve_date ASC
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Identifies Asset Hotspots (Most problematic equipment)
 */
function get_asset_hotspots(PDO $pdo, int $limit = 10): array {
    return $pdo->query("
        SELECT 
            a.asset_tag, a.model, c.category_name, 
            COUNT(t.ticket_id) as ticket_count,
            MAX(t.created_at) as last_reported
        FROM tickets t
        JOIN assets a ON t.asset_id = a.asset_id
        JOIN asset_categories c ON a.category_id = c.category_id
        GROUP BY a.asset_id
        ORDER BY ticket_count DESC
        LIMIT $limit
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Technician Scorecards
 */
function get_technician_scorecards(PDO $pdo): array {
    return $pdo->query("
        SELECT 
            u.full_name,
            COUNT(w.wo_id) as total_jobs,
            SUM(w.status = 'closed') as completed_jobs,
            AVG(TIMESTAMPDIFF(MINUTE, w.actual_start, w.actual_end)) as avg_labor_time,
            AVG(s.satisfaction) as avg_rating
        FROM users u
        JOIN work_orders w ON u.user_id = w.assigned_to
        LEFT JOIN wo_signoff s ON w.wo_id = s.wo_id
        WHERE u.role_id = 4
        GROUP BY u.user_id
        ORDER BY avg_rating DESC, completed_jobs DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ── AUDIT LOGS ────────────────────────────────────────────────
 */

function get_audit_logs(PDO $pdo, array $f = [], int $page = 1, int $per = 20): array {
    $where = ["1=1"];
    $params = [];
    
    if (!empty($f['user_id'])) {
        $where[] = "l.user_id = ?";
        $params[] = $f['user_id'];
    }
    if (!empty($f['object_type'])) {
        $where[] = "l.object_type = ?";
        $params[] = $f['object_type'];
    }
    if (!empty($f['date_from'])) {
        $where[] = "l.created_at >= ?";
        $params[] = $f['date_from'] . ' 00:00:00';
    }
    if (!empty($f['date_to'])) {
        $where[] = "l.created_at <= ?";
        $params[] = $f['date_to'] . ' 23:59:59';
    }

    $where_str = implode(" AND ", $where);
    $offset = ($page - 1) * $per;

    $stmt = $pdo->prepare("
        SELECT l.*, u.full_name as user_name
        FROM audit_log l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE $where_str
        ORDER BY l.created_at DESC
        LIMIT $per OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PII Masking for non-admins
    if (session_status() === PHP_SESSION_NONE) session_start();
    $is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    
    if (!$is_admin) {
        foreach ($logs as &$log) {
            if (!empty($log['new_values'])) {
                $log['new_values'] = mask_pii($log['new_values']);
            }
        }
    }
    return $logs;
}

/**
 * Helper to mask PII in strings
 */
function mask_pii(string $str): string {
    // Mask emails
    $str = preg_replace('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', '***@***.***', $str);
    // Mask phone numbers
    $str = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', '***-***-****', $str);
    return $str;
}

function count_audit_logs(PDO $pdo, array $f = []): int {
    $where = ["1=1"];
    $params = [];
    
    if (!empty($f['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = $f['user_id'];
    }
    if (!empty($f['object_type'])) {
        $where[] = "object_type = ?";
        $params[] = $f['object_type'];
    }
    if (!empty($f['date_from'])) {
        $where[] = "created_at >= ?";
        $params[] = $f['date_from'] . ' 00:00:00';
    }
    if (!empty($f['date_to'])) {
        $where[] = "created_at <= ?";
        $params[] = $f['date_to'] . ' 23:59:59';
    }

    $where_str = implode(" AND ", $where);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE $where_str");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * ── DRILL-DOWN QUERIES ──────────────────────────────────────────
 */
function get_drilldown_tickets(PDO $pdo, string $type, string $start_date, string $end_date): array {
    $base_query = "
        SELECT 
            t.ticket_id, t.ticket_number, t.priority, c.category_name, 
            t.status, t.created_at, ts.resolution_due, u.full_name as requester
        FROM tickets t
        LEFT JOIN asset_categories c ON t.category_id = c.category_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
        WHERE 1=1
    ";
    
    $params = [];
    
    switch ($type) {
        case 'backlog':
            $base_query .= " AND t.status NOT IN ('resolved', 'closed')";
            break;
            
        case 'ftfr':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.created_at >= ? AND t.created_at <= ?
                             AND (SELECT COUNT(*) FROM work_orders w WHERE w.ticket_id = t.ticket_id) <= 1";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'mttr':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.resolved_at IS NOT NULL
                             AND t.created_at >= ? AND t.created_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'resolved':
            $base_query .= " AND t.status IN ('resolved', 'closed') 
                             AND t.resolved_at IS NOT NULL
                             AND t.resolved_at >= ? AND t.resolved_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'breaches':
            $base_query .= " AND t.created_at >= ? AND t.created_at <= ?
                             AND t.status != 'cancelled'
                             AND (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1 OR ts.sla_id IS NULL)";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
            
        case 'event_support':
            $base_query .= " AND t.is_event_support = 1 AND t.status NOT IN ('resolved', 'closed')";
            break;
            
        case 'total':
        default:
            $base_query .= " AND t.created_at >= ? AND t.created_at <= ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            break;
    }
    
    $base_query .= " ORDER BY t.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($base_query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Location Heatmap: Building/Room with most tickets
 */
function get_location_heatmap(PDO $pdo): array {
    return $pdo->query("
        SELECT l.building, l.room, COUNT(t.ticket_id) as ticket_count
        FROM tickets t
        JOIN locations l ON t.location_id = l.location_id
        GROUP BY l.location_id
        ORDER BY ticket_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Warranty Exposure: Assets with warranty expiring in next 90 days
 */
function get_warranty_exposure(PDO $pdo): array {
    return $pdo->query("
        SELECT a.asset_tag, a.model, a.manufacturer, w.warranty_end as warranty_expiry
        FROM assets a
        JOIN asset_warranty w ON a.asset_id = w.asset_id
        WHERE w.warranty_end >= CURDATE()
          AND w.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY w.warranty_end ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Active Escalations: Breached tickets that are still open
 */
function get_active_escalations(PDO $pdo): array {
    return $pdo->query("
        SELECT t.ticket_id, t.ticket_number, u.full_name as assignee,
               ts.is_response_breached, ts.is_resolution_breached,
               CASE 
                 WHEN ts.is_resolution_breached = 1 THEN ts.resolution_due 
                 ELSE ts.response_due 
               END as deadline
        FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        LEFT JOIN users u ON t.assigned_to = u.user_id
        WHERE (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1)
          AND t.status NOT IN ('resolved', 'closed', 'cancelled')
        ORDER BY deadline ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ── TICKET AGING ──────────────────────────────────────────────
 * Groups open tickets into aging buckets: 0-7d, 8-14d, 15-30d, 30d+
 */
function get_ticket_aging(PDO $pdo): array {
    return $pdo->query("
        SELECT 
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 0 AND 7) as bucket_0_7,
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 8 AND 14) as bucket_8_14,
            SUM(DATEDIFF(NOW(), created_at) BETWEEN 15 AND 30) as bucket_15_30,
            SUM(DATEDIFF(NOW(), created_at) > 30) as bucket_over_30,
            COUNT(*) as total_open,
            ROUND(AVG(DATEDIFF(NOW(), created_at)), 1) as avg_age_days
        FROM tickets
        WHERE status NOT IN ('resolved', 'closed', 'cancelled')
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * ── COST PER TICKET / ASSET ──────────────────────────────────
 * Aggregates parts cost from work orders to compute cost per ticket and per asset.
 */
function get_cost_stats(PDO $pdo, string $start_date, string $end_date): array {
    // Total parts cost and cost per ticket
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT t.ticket_id) as total_tickets,
            COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0) as total_parts_cost,
            ROUND(COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0) / NULLIF(COUNT(DISTINCT t.ticket_id), 0), 2) as avg_cost_per_ticket
        FROM tickets t
        LEFT JOIN work_orders w ON t.ticket_id = w.ticket_id
        LEFT JOIN wo_parts_used pu ON w.wo_id = pu.wo_id
        LEFT JOIN parts_inventory pi ON pu.part_id = pi.part_id
        WHERE t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $ticket_cost = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Top 5 costliest assets
    $costliest = $pdo->query("
        SELECT 
            a.asset_tag, a.model,
            COALESCE(SUM(pu.quantity_used * pi.unit_cost), 0) as total_cost,
            COUNT(DISTINCT w.wo_id) as wo_count
        FROM assets a
        JOIN tickets t ON t.asset_id = a.asset_id
        JOIN work_orders w ON t.ticket_id = w.ticket_id
        JOIN wo_parts_used pu ON w.wo_id = pu.wo_id
        JOIN parts_inventory pi ON pu.part_id = pi.part_id
        GROUP BY a.asset_id
        ORDER BY total_cost DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total_parts_cost' => $ticket_cost['total_parts_cost'] ?? 0,
        'avg_cost_per_ticket' => $ticket_cost['avg_cost_per_ticket'] ?? 0,
        'costliest_assets' => $costliest
    ];
}

