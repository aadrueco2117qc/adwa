<?php
// config/sla.php
// Shared SLA Engine logic for MTRTS

/**
 * Initializes a new SLA record for a ticket using the specificity-based policy selection.
 */
function init_ticket_sla(PDO $pdo, int $ticket_id): void {
    // 1. Get ticket details
    $stmt = $pdo->prepare("SELECT ticket_id, priority, category_id, location_id, request_type, is_event_support, created_at FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$t) return;

    // 2. Find matching policy
    $policy_id = get_matching_sla_policy($pdo, $t);
    if (!$policy_id) return;

    $stmt = $pdo->prepare("SELECT * FROM sla_policies WHERE policy_id = ?");
    $stmt->execute([$policy_id]);
    $policy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$policy) return;

    // 3. Get location timezone
    $timezone = 'Asia/Manila';
    if ($t['location_id']) {
        try {
            $stmt_tz = $pdo->prepare("SELECT timezone FROM locations WHERE location_id = ?");
            $stmt_tz->execute([$t['location_id']]);
            $fetched_tz = $stmt_tz->fetchColumn();
            if ($fetched_tz) {
                $timezone = $fetched_tz;
            }
        } catch (PDOException $e) {
            // Fallback if 'timezone' column doesn't exist in older DB schemas
            $timezone = 'Asia/Manila';
        }
    }

    // 4. Calculate deadlines
    $resp_due = calculate_sla_deadline($pdo, $t['created_at'], $policy['response_minutes'], $policy['uses_business_hours'], $timezone);
    $diag_due = calculate_sla_deadline($pdo, $t['created_at'], $policy['diagnosis_minutes'], $policy['uses_business_hours'], $timezone);
    $res_due  = calculate_sla_deadline($pdo, $t['created_at'], $policy['resolution_minutes'], $policy['uses_business_hours'], $timezone);

    // 5. Save to ticket_sla
    $stmt = $pdo->prepare("
        INSERT INTO ticket_sla 
            (ticket_id, policy_id, response_due, diagnosis_due, resolution_due)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            policy_id = VALUES(policy_id),
            response_due = VALUES(response_due),
            diagnosis_due = VALUES(diagnosis_due),
            resolution_due = VALUES(resolution_due)
    ");
    $stmt->execute([$ticket_id, $policy['policy_id'], $resp_due, $diag_due, $res_due]);
}

/**
 * The "Brain" - Calculates a deadline by skipping non-working time
 */
function calculate_sla_deadline(PDO $pdo, string $start_time, int $minutes, bool $use_business_hours = true, string $timezone = 'Asia/Manila'): string {
    $current = new DateTime($start_time, new DateTimeZone('UTC'));
    $current->setTimezone(new DateTimeZone($timezone));
    
    if (!$use_business_hours) {
        $current->modify("+$minutes minutes");
        $current->setTimezone(new DateTimeZone('UTC'));
        return $current->format('Y-m-d H:i:s');
    }

    // Fetch business hours
    $stmt = $pdo->query("SELECT day_of_week, start_time, end_time, is_working FROM business_hours");
    $biz_hours = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $biz_hours[$row['day_of_week']] = $row;
    }

    // Fetch holidays
    $year = (int)$current->format('Y');
    $stmt = $pdo->prepare("SELECT holiday_date, is_recurring FROM holidays");
    $stmt->execute();
    $holidays = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['is_recurring']) {
            $date = new DateTime($row['holiday_date']);
            $holidays[] = $year . '-' . $date->format('m-d');
        } else {
            $holidays[] = $row['holiday_date'];
        }
    }

    $remaining_minutes = $minutes;

    while ($remaining_minutes > 0) {
        $dow = (int)$current->format('w'); // 0=Sun, 6=Sat
        $date_str = $current->format('Y-m-d');
        
        $is_working_day = isset($biz_hours[$dow]) && $biz_hours[$dow]['is_working'] && !in_array($date_str, $holidays);

        if (!$is_working_day) {
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        $start_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['start_time'], new DateTimeZone($timezone));
        $end_of_work = new DateTime($date_str . ' ' . $biz_hours[$dow]['end_time'], new DateTimeZone($timezone));

        if ($current < $start_of_work) {
            $current = clone $start_of_work;
        }

        if ($current >= $end_of_work) {
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
            continue;
        }

        $diff = $end_of_work->getTimestamp() - $current->getTimestamp();
        $available_minutes = floor($diff / 60);

        if ($remaining_minutes <= $available_minutes) {
            $current->modify("+$remaining_minutes minutes");
            $remaining_minutes = 0;
        } else {
            $remaining_minutes -= $available_minutes;
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
        }
    }

    $current->setTimezone(new DateTimeZone('UTC'));
    return $current->format('Y-m-d H:i:s');
}

/**
 * Updates SLA actual timestamps and handles PAUSE logic
 */
function update_ticket_sla(PDO $pdo, int $ticket_id, string $status, ?string $pause_reason = null): void {
    // 1. Handle unpausing if coming off on_hold
    if ($status !== 'on_hold') {
        $stmt = $pdo->prepare("SELECT paused_at FROM ticket_sla WHERE ticket_id = ? AND paused_at IS NOT NULL");
        $stmt->execute([$ticket_id]);
        $paused_at = $stmt->fetchColumn();
        
        if ($paused_at) {
            $pdo->prepare("
                UPDATE ticket_sla 
                SET 
                    total_paused_minutes = total_paused_minutes + TIMESTAMPDIFF(MINUTE, paused_at, NOW()),
                    response_due = DATE_ADD(response_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    diagnosis_due = DATE_ADD(diagnosis_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    resolution_due = DATE_ADD(resolution_due, INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    paused_at = NULL
                WHERE ticket_id = ?
            ")->execute([$ticket_id]);
        }
    }

    // 2. Handle actual completion timestamps or pausing
    if ($status === 'assigned') {
        $pdo->prepare("UPDATE ticket_sla SET responded_at = NOW() WHERE ticket_id = ? AND responded_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'in_progress') {
        $pdo->prepare("UPDATE ticket_sla SET diagnosed_at = NOW() WHERE ticket_id = ? AND diagnosed_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'resolved' || $status === 'closed') {
        $pdo->prepare("UPDATE ticket_sla SET resolved_at = NOW() WHERE ticket_id = ? AND resolved_at IS NULL")->execute([$ticket_id]);
    } elseif ($status === 'on_hold') {
        $pdo->prepare("UPDATE ticket_sla SET paused_at = NOW(), pause_reason = ? WHERE ticket_id = ? AND paused_at IS NULL")->execute([$pause_reason, $ticket_id]);
    }
}

/**
 * Checks for breached tickets and escalates them to on-call or managers
 */
function check_sla_escalations(PDO $pdo): array {
    $breached = [];
    
    // 1. Mark new breaches
    $pdo->query("UPDATE ticket_sla SET is_response_breached = 1 WHERE responded_at IS NULL AND response_due < NOW() AND is_response_breached = 0");
    $pdo->query("UPDATE ticket_sla SET is_diagnosis_breached = 1 WHERE diagnosed_at IS NULL AND diagnosis_due < NOW() AND is_diagnosis_breached = 0");
    $pdo->query("UPDATE ticket_sla SET is_resolution_breached = 1 WHERE resolved_at IS NULL AND resolution_due < NOW() AND is_resolution_breached = 0");

    // 2. Fetch escalated tickets (breached but not yet resolved)
    $stmt = $pdo->query("
        SELECT t.ticket_id, t.ticket_number, t.title, t.priority, t.assigned_to, 
               ts.is_response_breached, ts.is_resolution_breached,
               u.full_name as current_assignee
        FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        LEFT JOIN users u ON t.assigned_to = u.user_id
        WHERE (ts.is_response_breached = 1 OR ts.is_resolution_breached = 1)
          AND t.status NOT IN ('resolved', 'closed', 'cancelled')
    ");
    $breached = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Trigger hierarchical notifications (Simulated)
    foreach ($breached as $b) {
        // In a real system, we'd check if we already notified for this breach level
        // Level 1: Technician (already notified)
        // Level 2: IT Managers (Role 2)
        $managers = $pdo->query("SELECT user_id FROM users WHERE role_id = 2 AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($managers as $mid) {
            // notify_user($pdo, $mid, "ESCALATION: Ticket " . $b['ticket_number'], "Breached ticket assigned to " . ($b['current_assignee'] ?: 'Unassigned'));
        }
    }
    
    return $breached;
}
/**
 * Find the best matching SLA policy for a ticket using a weighted specificity system.
 * Priority (10) > Location (5) > Category (3) > Request Type (2)
 */
function get_matching_sla_policy(PDO $pdo, array $ticket_data): ?int {
    $policies = $pdo->query("SELECT * FROM sla_policies WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($policies)) return null;

    $best_policy_id = null;
    $highest_score = -1;

    foreach ($policies as $policy) {
        $score = 0;
        
        // 1. Priority Match (10 pts)
        if ($policy['priority'] === $ticket_data['priority']) {
            $score += 10;
        } elseif ($policy['priority'] !== null) {
            continue; // Explicitly defined for a DIFFERENT priority
        }

        // 2. Location Match (5 pts)
        $policy_location_id = $policy['location_id'] ?? null;
        if (isset($ticket_data['location_id']) && $policy_location_id == $ticket_data['location_id']) {
            $score += 5;
        } elseif ($policy_location_id !== null) {
            continue; // Explicitly defined for a DIFFERENT location
        }

        // 3. Category Match (3 pts)
        $policy_category_id = $policy['category_id'] ?? null;
        if (isset($ticket_data['category_id']) && $policy_category_id == $ticket_data['category_id']) {
            $score += 3;
        } elseif ($policy_category_id !== null) {
            continue; // Explicitly defined for a DIFFERENT category
        }

        // 4. Request Type Match (2 pts)
        $policy_request_type = $policy['request_type'] ?? null;
        if (isset($ticket_data['request_type']) && $policy_request_type === $ticket_data['request_type']) {
            $score += 2;
        } elseif ($policy_request_type !== null) {
            continue; // Explicitly defined for a DIFFERENT request type
        }

        // Ties go to the first one found or highest score
        if ($score > $highest_score) {
            $highest_score = $score;
            $best_policy_id = (int)$policy['policy_id'];
        }
    }

    // Default to the first general policy if no specific match
    if ($best_policy_id === null && !empty($policies)) {
        foreach($policies as $p) {
            if ($p['priority'] === null && $p['location_id'] === null && $p['category_id'] === null) {
                return (int)$p['policy_id'];
            }
        }
        return (int)$policies[0]['policy_id'];
    }

    return $best_policy_id;
}

/**
 * ── SLA RETROACTIVE CHANGE PROTECTION ──────────────────────────
 * Updates an SLA policy ONLY if a justification is provided when
 * active tickets are using it. Logs the old values + justification
 * to the audit_log table for compliance.
 *
 * Returns: ['success' => bool, 'message' => string]
 */
function update_sla_policy(PDO $pdo, int $policy_id, array $new_values, ?string $justification = null, ?int $changed_by = null): array {
    // 1. Fetch old policy
    $stmt = $pdo->prepare("SELECT * FROM sla_policies WHERE policy_id = ?");
    $stmt->execute([$policy_id]);
    $old_policy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old_policy) {
        return ['success' => false, 'message' => 'Policy not found.'];
    }

    // 2. Check if any open tickets are using this policy
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM ticket_sla ts
        JOIN tickets t ON ts.ticket_id = t.ticket_id
        WHERE ts.policy_id = ?
          AND t.status NOT IN ('resolved', 'closed', 'cancelled')
    ");
    $stmt->execute([$policy_id]);
    $active_count = (int)$stmt->fetchColumn();

    // 3. If active tickets exist, REQUIRE justification
    if ($active_count > 0 && empty(trim($justification ?? ''))) {
        return [
            'success' => false,
            'message' => "This policy is applied to $active_count active ticket(s). A justification reason is required to make retroactive changes."
        ];
    }

    // 4. Build the UPDATE query from allowed fields
    $allowed = ['policy_name', 'priority', 'category_id', 'location_id', 'is_event_support',
                'request_type', 'response_minutes', 'diagnosis_minutes', 'resolution_minutes',
                'uses_business_hours', 'is_active'];
    $sets = [];
    $params = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $new_values)) {
            $sets[] = "$col = ?";
            $params[] = $new_values[$col];
        }
    }
    if (empty($sets)) {
        return ['success' => false, 'message' => 'No valid fields to update.'];
    }
    $params[] = $policy_id;
    $pdo->prepare("UPDATE sla_policies SET " . implode(', ', $sets) . " WHERE policy_id = ?")->execute($params);

    // 5. Log to audit_log with justification
    $pdo->prepare("
        INSERT INTO audit_log (user_id, action, object_type, object_id, old_values, new_values, ip_address, created_at)
        VALUES (?, 'UPDATE', 'sla_policy', ?, ?, ?, ?, NOW())
    ")->execute([
        $changed_by,
        $policy_id,
        json_encode($old_policy),
        json_encode(array_merge($new_values, ['_justification' => $justification ?? 'No active tickets affected'])),
        $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ]);

    return ['success' => true, 'message' => 'Policy updated. ' . ($active_count > 0 ? "Audit logged with justification (affects $active_count active tickets)." : 'No active tickets affected.')];
}
