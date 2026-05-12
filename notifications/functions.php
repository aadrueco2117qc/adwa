<?php
// modules/notifications/functions.php
// All database queries and helpers for the Notifications module.
// $pdo is provided by guard.php / auth_only.php — never create one here.

// ── Write ─────────────────────────────────────────────────────

/**
 * Insert a notification for one user.
 * $notif_key prevents duplicates for automated alerts — pass null for ad-hoc.
 * If the same (notif_key, user_id) pair already exists, the INSERT is silently
 * ignored thanks to the UNIQUE KEY + INSERT IGNORE.
 */
function notify_user(
    PDO $pdo,
    int $user_id,
    string $title,
    string $body,
    string $link = '',
    ?string $notif_key = null
): void {
    $pdo->prepare("
        INSERT IGNORE INTO notifications (user_id, title, body, link, notif_key)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$user_id, $title, $body ?: null, $link ?: null, $notif_key]);
}

// ── Read ──────────────────────────────────────────────────────

function get_unread_count(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns the N most recent notifications for the dropdown.
 */
function get_recent_notifications(PDO $pdo, int $user_id, int $limit = 10): array {
    $stmt = $pdo->prepare("
        SELECT notif_id, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Returns a paginated list of all notifications for the full-page view.
 */
function get_all_notifications(PDO $pdo, int $user_id, int $page = 1, int $per = 20): array {
    $offset = ($page - 1) * $per;
    $stmt = $pdo->prepare("
        SELECT notif_id, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $per, $offset]);
    return $stmt->fetchAll();
}

function count_all_notifications(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

// ── Mark read ─────────────────────────────────────────────────

function mark_notification_read(PDO $pdo, int $notif_id, int $user_id): void {
    $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE notif_id = ? AND user_id = ?"
    )->execute([$notif_id, $user_id]);
}

function mark_all_notifications_read(PDO $pdo, int $user_id): void {
    $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
    )->execute([$user_id]);
}

// ── Warranty expiry check ─────────────────────────────────────

/**
 * Scans asset_warranty for upcoming expirations and fires notifications.
 *
 * Standard notice  → roles 1, 2, 3, 8 (all IT staff + admins)
 * Escalation alert → roles 1, 2 only (managers/admins) when the asset
 *                    also has unresolved tickets — they need to act before
 *                    warranty coverage ends.
 *
 * Alert thresholds: 60, 30, 7 days before warranty_end.
 * Each asset fires only the MOST SPECIFIC (smallest) applicable threshold.
 *
 * notif_key format:
 *   warranty_{asset_id}_{warranty_end}_{threshold}         — standard notice
 *   warranty_esc_{asset_id}_{warranty_end}_{threshold}     — escalation
 * Guarantees each alert fires exactly once per asset per threshold.
 *
 * Call this on page load, throttled to once per hour via $_SESSION.
 */
function check_warranty_expiry(PDO $pdo): void {
    // Assets expiring within 60 days, with open-ticket count per asset
    $assets = $pdo->query("
        SELECT a.asset_id, a.asset_tag, a.manufacturer, a.model,
               w.warranty_end,
               DATEDIFF(w.warranty_end, CURDATE()) AS days_left,
               COUNT(t.ticket_id) AS open_tickets
        FROM asset_warranty w
        JOIN  assets  a ON a.asset_id = w.asset_id
        LEFT JOIN tickets t ON t.asset_id = a.asset_id
                           AND t.status NOT IN ('resolved', 'closed')
        WHERE w.warranty_end >= CURDATE()
          AND w.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        GROUP BY a.asset_id, a.asset_tag, a.manufacturer, a.model, w.warranty_end
    ")->fetchAll();

    if (!$assets) return;

    // All IT staff receive the standard warranty notice
    $all_recipients = $pdo->query("
        SELECT user_id FROM users
        WHERE role_id IN (1, 2, 3, 8) AND is_active = 1
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Only managers/admins receive the open-ticket escalation
    $managers = $pdo->query("
        SELECT user_id FROM users
        WHERE role_id IN (1, 2) AND is_active = 1
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!$all_recipients) return;

    $thresholds = [
        7  => '7-Day Warranty Warning',
        30 => '30-Day Warranty Notice',
        60 => '60-Day Warranty Notice',
    ];

    foreach ($assets as $asset) {
        $days = (int) $asset['days_left'];
        $end  = $asset['warranty_end'];
        $open = (int) $asset['open_tickets'];
        $link = BASE_URL . "modules/assets/view.php?id={$asset['asset_id']}";
        $name = "{$asset['manufacturer']} {$asset['model']} ({$asset['asset_tag']})";

        foreach ($thresholds as $t => $title) {
            if ($days > $t) continue;

            // ── Standard notice (all staff) ────────────────────────────
            $body = "{$name} — warranty expires on {$end}.";
            if ($open > 0) {
                $body .= " {$open} open ticket(s) still unresolved.";
            }

            $notif_key = "warranty_{$asset['asset_id']}_{$end}_{$t}";
            foreach ($all_recipients as $user_id) {
                notify_user($pdo, (int) $user_id, $title, $body, $link, $notif_key);
            }

            // ── Escalation (managers only, when open tickets exist) ────
            if ($open > 0 && $managers) {
                $esc_title = "Action Required: Open Tickets on Expiring Asset";
                $esc_body  = "{$name} has {$open} open ticket(s) and warranty expires "
                           . "in {$days} day(s) ({$end}). Resolve tickets before "
                           . "coverage ends.";
                $esc_key   = "warranty_esc_{$asset['asset_id']}_{$end}_{$t}";

                foreach ($managers as $user_id) {
                    notify_user($pdo, (int) $user_id, $esc_title, $esc_body, $link, $esc_key);
                }
            }

            break; // Only fire the most specific threshold
        }
    }
}

// ── Low stock check ───────────────────────────────────────────

/**
 * Scans parts_inventory for low-stock and out-of-stock parts and fires
 * in-app notifications to admins, IT managers, and IT staff.
 *
 * Thresholds:
 *   - Out of stock : quantity_on_hand = 0
 *   - Low stock    : quantity_on_hand > 0 AND quantity_on_hand <= reorder_level
 *
 * notif_key format:
 *   stock_out_{part_id}_{date}      — out of stock (fires once per day)
 *   stock_low_{part_id}_{date}      — low stock (fires once per day)
 * Guarantees each alert fires exactly once per part per day.
 *
 * Recipients: roles 1 (admin), 2 (it_manager), 3 (it_staff), 4 (technician), 8 (super_admin)
 * Technicians are included because they are the ones using parts.
 *
 * Call this on page load, throttled to once per hour via $_SESSION.
 */
function check_low_stock(PDO $pdo): void {
    $today = date('Y-m-d');

    // Fetch all active parts that are low or out of stock
    $stmt = $pdo->query("
        SELECT part_id, part_name, part_number, category,
               quantity_on_hand, reorder_level
        FROM parts_inventory
        WHERE is_active = 1
          AND quantity_on_hand <= reorder_level
        ORDER BY quantity_on_hand ASC
    ");
    $parts = $stmt->fetchAll();

    if (!$parts) return;

    // Recipients: admins, IT managers, IT staff, technicians
    $recipients = $pdo->query("
        SELECT user_id FROM users
        WHERE role_id IN (1, 2, 3, 4, 8) AND is_active = 1
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!$recipients) return;

    $inv_link = BASE_URL . 'modules/inventory/index.php';

    foreach ($parts as $part) {
        $qty      = (int)$part['quantity_on_hand'];
        $reorder  = (int)$part['reorder_level'];
        $name     = htmlspecialchars($part['part_name']);
        $number   = htmlspecialchars($part['part_number']);

        if ($qty === 0) {
            $title     = 'Part Out of Stock';
            $body      = "{$name} ({$number}) is completely out of stock. Reorder level: {$reorder}.";
            $notif_key = "stock_out_{$part['part_id']}_{$today}";
        } else {
            $title     = 'Low Stock Alert';
            $body      = "{$name} ({$number}) is running low — {$qty} remaining (reorder at {$reorder}).";
            $notif_key = "stock_low_{$part['part_id']}_{$today}";
        }

        foreach ($recipients as $user_id) {
            notify_user($pdo, (int)$user_id, $title, $body, $inv_link, $notif_key);
        }
    }
}

// ── Render helpers ────────────────────────────────────────────

function notif_time_ago(string $datetime): string {
    $diff = (int) ((new DateTime())->getTimestamp() - (new DateTime($datetime))->getTimestamp());
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return (new DateTime($datetime))->format('M j, Y');
}
