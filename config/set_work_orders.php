<?php
/**
 * Work Order seed script — fully updated for current schema.
 * Run AFTER set_accounts_password.php (needs users, assets, tickets to exist).
 * Usage: visit http://localhost/mtrts-main/config/set_work_orders.php
 *
 * What this seeds:
 *  - 3 work orders per asset category (24 total)
 *  - Each WO is linked to a real ticket → real asset → real category
 *  - WOs cycle through all 4 types: diagnosis, repair, maintenance, follow_up
 *  - WOs cycle through statuses: new, assigned, in_progress, on_hold, resolved
 *  - Assigned to the technician account (technician@olfu.edu.ph)
 *  - Assignment log entries created so history is visible
 *  - Scheduled dates spread across the next 30 days
 *
 * Safe to re-run: generates fresh WO numbers each time (no duplicate key issues).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../modules/workorders/functions.php';

$errors   = [];
$sections = [];

// ── Helper ────────────────────────────────────────────────────────────────────
function wo_seed_section(string $title, callable $fn, array &$sections, array &$errors): void {
    ob_start();
    try {
        $fn();
        $out = ob_get_clean();
        $sections[] = ['title' => $title, 'lines' => array_filter(explode("\n", $out))];
    } catch (Throwable $e) {
        ob_get_clean();
        $errors[] = "[{$title}] " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// PRE-FLIGHT: verify required accounts exist
// ─────────────────────────────────────────────────────────────────────────────
wo_seed_section('Pre-flight checks', function () use ($pdo) {

    $required = [
        'technician@olfu.edu.ph' => 'Technician account',
        'itstaff@olfu.edu.ph'    => 'IT Staff account',
        'faculty@olfu.edu.ph'    => 'Faculty account (ticket requester)',
    ];

    foreach ($required as $email => $label) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $exists = (int) $stmt->fetchColumn();
        if (!$exists) {
            throw new RuntimeException(
                "{$label} ({$email}) not found. Run set_accounts_password.php first."
            );
        }
        echo "  ✓ {$label} ({$email})\n";
    }

    $ticket_count = (int) $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $asset_count  = (int) $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    echo "  ✓ {$asset_count} assets found\n";
    echo "  ✓ {$ticket_count} tickets found\n";

    if ($ticket_count === 0) {
        throw new RuntimeException(
            "No tickets found. Run set_accounts_password.php first to seed assets and tickets."
        );
    }

}, $sections, $errors);

if (!empty($errors)) {
    // Pre-flight failed — stop here and show the error
    goto output;
}

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 1 — WORK ORDERS (3 per category, all types, mixed statuses)
// ─────────────────────────────────────────────────────────────────────────────
wo_seed_section('Work Orders', function () use ($pdo) {

    $tech_id    = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'technician@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    $creator_id = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'itstaff@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    // Fetch all tickets with their asset category info
    $tickets = $pdo->query("
        SELECT t.ticket_id, t.ticket_number, t.priority,
               ac.category_name, ac.category_id
        FROM tickets t
        JOIN assets a          ON t.asset_id    = a.asset_id
        JOIN asset_categories ac ON a.category_id = ac.category_id
        ORDER BY ac.category_id, t.ticket_id
    ")->fetchAll();

    if (empty($tickets)) {
        throw new RuntimeException('No tickets with linked assets found.');
    }

    // 3 WO definitions to create per ticket (cycles through all 4 types)
    // [wo_type, status, notes_template, scheduled_offset_hours]
    $wo_templates = [
        [
            'type'    => 'diagnosis',
            'status'  => 'new',
            'notes'   => 'Initial diagnosis — identify root cause of reported issue.',
            'hours'   => 0,
        ],
        [
            'type'    => 'repair',
            'status'  => 'assigned',
            'notes'   => 'Repair work based on diagnosis findings. Parts may be required.',
            'hours'   => 3,
        ],
        [
            'type'    => 'maintenance',
            'status'  => 'assigned',
            'notes'   => 'Scheduled preventive maintenance following repair completion.',
            'hours'   => 6,
        ],
    ];

    // Extra WOs to demonstrate more statuses (follow_up, in_progress, on_hold, resolved)
    // These are added for the first 4 tickets only to keep total manageable
    $extra_templates = [
        [
            'type'    => 'follow_up',
            'status'  => 'in_progress',
            'notes'   => 'Follow-up inspection — verifying repair holds under normal use.',
            'hours'   => 24,
        ],
        [
            'type'    => 'repair',
            'status'  => 'on_hold',
            'notes'   => 'Repair on hold — waiting for replacement parts to arrive.',
            'hours'   => 48,
            'hold'    => 'waiting_parts',
        ],
        [
            'type'    => 'repair',
            'status'  => 'resolved',
            'notes'   => 'Repair completed successfully. Unit tested and returned to service.',
            'hours'   => 72,
        ],
    ];

    $insert = $pdo->prepare("
        INSERT INTO work_orders
            (wo_number, ticket_id, wo_type, assigned_to, assigned_by,
             status, on_hold_reason, is_rma,
             scheduled_start, scheduled_end,
             notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
    ");

    $log_assign = $pdo->prepare("
        INSERT INTO wo_assignment_log (wo_id, assigned_to, assigned_by, reason)
        VALUES (?, ?, ?, ?)
    ");

    $base = new DateTime('2026-05-12 09:00:00');
    $day_offset = 0;
    $total = 0;

    foreach ($tickets as $idx => $ticket) {
        $templates = $wo_templates;

        // Add extra status variety for first 4 tickets
        if ($idx < 4) {
            $templates = array_merge($templates, $extra_templates);
        }

        foreach ($templates as $tpl) {
            $wo_number = generate_wo_number($pdo);

            $start = (clone $base)->modify("+{$day_offset} days +{$tpl['hours']} hours");
            $end   = (clone $start)->modify('+2 hours');

            $hold_reason = $tpl['hold'] ?? null;
            $assigned_to = in_array($tpl['status'], ['new']) ? null : $tech_id;
            $assigned_by = $assigned_to ? $creator_id : null;

            $insert->execute([
                $wo_number,
                $ticket['ticket_id'],
                $tpl['type'],
                $assigned_to,
                $assigned_by,
                $tpl['status'],
                $hold_reason,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                "[{$ticket['category_name']}] {$tpl['notes']}",
                $creator_id ?: null,
            ]);

            $wo_id = (int) $pdo->lastInsertId();

            // Log assignment for assigned/in_progress/on_hold/resolved WOs
            if ($assigned_to && $wo_id) {
                $reason_map = [
                    'assigned'    => 'Assigned by IT staff',
                    'in_progress' => 'Assigned and in progress',
                    'on_hold'     => 'Assigned — currently on hold',
                    'resolved'    => 'Assigned — work completed',
                ];
                $reason = isset($reason_map[$tpl['status']]) ? $reason_map[$tpl['status']] : 'Seed assignment';
                $log_assign->execute([$wo_id, $assigned_to, $assigned_by, $reason]);
            }

            $status_label = strtoupper($tpl['status']);
            $type_padded   = str_pad($tpl['type'],   12);
            $status_padded = str_pad($tpl['status'], 12);
            echo "  {$wo_number}  {$type_padded}  {$status_padded}  {$ticket['category_name']}  ({$ticket['ticket_number']})\n";
            $total++;
        }

        $day_offset++;
    }

    echo "\n  Total work orders created: {$total}\n";

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 2 — PARTS INVENTORY (46 parts, realistic stock levels)
// ─────────────────────────────────────────────────────────────────────────────
wo_seed_section('Parts Inventory', function () use ($pdo) {

    // Each entry: [part_number, part_name, category, reorder_level, unit_price, quantity_on_hand]
    // quantity_on_hand: some plentiful, some deliberately low to trigger low-stock alerts
    $parts = [
        // Cables
        ['CABLE-HDMI-001',  'HDMI cable',              'cables',     5,  12.50,  50],
        ['CABLE-VGA-001',   'VGA cable',               'cables',     5,   8.00,  35],
        ['CABLE-DP-001',    'DisplayPort cable',       'cables',     5,  15.00,   3],  // LOW
        ['CABLE-AUX-001',   'AUX 3.5mm cable',         'cables',     5,   5.00,  45],
        ['CABLE-XLR-001',   'XLR cable',               'cables',     5,  18.00,   2],  // LOW
        ['CABLE-ETH-001',   'Ethernet cable',          'cables',    10,   6.00,  60],
        ['CABLE-USB-001',   'USB cable',               'cables',    10,   7.50,  55],
        ['CABLE-COX-001',   'Coaxial cable',           'cables',     5,   9.00,   4],  // LOW
        // Projector
        ['PROJ-LAMP-001',   'Projector lamp',          'projector',  3, 120.00,   1],  // CRITICAL LOW
        ['PROJ-AFIL-001',   'Air filter',              'projector',  5,   8.50,  12],
        ['PROJ-LCD-001',    'LCD panel',               'projector',  2, 350.00,   0],  // OUT OF STOCK
        ['PROJ-BALL-001',   'Ballast (lamp driver)',   'projector',  2,  85.00,   5],
        ['PROJ-LENS-001',   'Lens assembly',           'projector',  2, 200.00,   3],
        ['PROJ-DLP-001',    'DLP chip',                'projector',  1, 450.00,   1],  // AT REORDER
        ['PROJ-FAN-001',    'Cooling fan (projector)', 'projector',  4,  25.00,   8],
        // Audio
        ['AUD-SPK-001',     'Speaker driver',          'audio',      4,  45.00,  10],
        ['AUD-JACK-001',    'Audio jack 3.5mm',        'audio',     10,   3.50,  40],
        ['AUD-XLR-001',     'XLR connector',           'audio',      8,  12.00,  25],
        ['AUD-VOL-001',     'Volume potentiometer',    'audio',      6,   8.00,   3],  // LOW
        ['AUD-AMP-001',     'Amplifier board',         'audio',      3,  75.00,   2],  // LOW
        ['AUD-TRF-001',     'Audio transformer',       'audio',      3,  35.00,   6],
        // Electrical
        ['ELEC-PWR-001',    'Power cable (AC)',        'electrical', 8,  10.00,  30],
        ['ELEC-ADP-001',    'Power adapter',           'electrical', 6,  22.00,  18],
        ['ELEC-F5A-001',    'Fuse 5A',                 'electrical',15,   2.50,   5],  // LOW
        ['ELEC-F10A-001',   'Fuse 10A',                'electrical',15,   2.75,   8],  // LOW
        ['ELEC-CBK-001',    'Circuit breaker',         'electrical', 4,  18.00,  10],
        ['ELEC-PST-001',    'Power strip',             'electrical', 5,  28.00,  12],
        ['ELEC-SPG-001',    'Surge protector',         'electrical', 5,  35.00,   4],  // LOW
        // Electronic
        ['ELCN-C100-001',   'Capacitor 100uF',         'electronic',20,   1.50,  80],
        ['ELCN-C470-001',   'Capacitor 470uF',         'electronic',20,   2.00,  15],  // LOW
        ['ELCN-R10K-001',   'Resistor 10kOhm',         'electronic',25,   0.50, 100],
        ['ELCN-DDE-001',    'Diode',                   'electronic',25,   0.75,  90],
        ['ELCN-TRN-001',    'Transistor',              'electronic',20,   1.25,  18],  // LOW
        ['ELCN-RLY-001',    'IC relay',                'electronic',10,   5.50,  35],
        ['ELCN-MOS-001',    'MOSFET',                  'electronic',15,   3.00,  50],
        // Cooling
        ['COOL-F80-001',    'Cooling fan 80mm',        'cooling',    6,  12.00,  14],
        ['COOL-F120-001',   'Cooling fan 120mm',       'cooling',    6,  15.00,   5],  // LOW
        ['COOL-TPS-001',    'Thermal paste',           'cooling',    8,   6.50,  22],
        ['COOL-HSK-001',    'Heat sink',               'cooling',    5,  18.00,   3],  // LOW
        ['COOL-DST-001',    'Dust filter',             'cooling',   10,   4.00,  28],
        // Mounting
        ['MNT-M3-001',      'M3 screw set',            'mounting',  12,   5.00,  40],
        ['MNT-M4-001',      'M4 screw set',            'mounting',  12,   5.50,  38],
        ['MNT-BKT-001',     'Bracket kit',             'mounting',   8,  15.00,   6],  // LOW
        ['MNT-TIE-001',     'Cable ties',              'mounting',  20,   3.00,  75],
        ['MNT-WPL-001',     'Wall plate',              'mounting',   6,   8.50,   2],  // LOW
        ['MNT-RMR-001',     'Rack mount rails',        'mounting',   4,  45.00,   1],  // LOW
    ];

    $upsert = $pdo->prepare("
        INSERT INTO parts_inventory
            (part_number, part_name, category, quantity_on_hand, reorder_level, unit_price, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            part_name        = VALUES(part_name),
            category         = VALUES(category),
            quantity_on_hand = VALUES(quantity_on_hand),
            reorder_level    = VALUES(reorder_level),
            unit_price       = VALUES(unit_price),
            is_active        = 1,
            updated_at       = CURRENT_TIMESTAMP
    ");

    $low_count = 0;
    foreach ($parts as [$pn, $name, $cat, $reorder, $price, $qty]) {
        $upsert->execute([$pn, $name, $cat, $qty, $reorder, $price]);
        $flag = ($qty <= $reorder) ? ' *** LOW STOCK ***' : '';
        if ($flag) $low_count++;
        echo "  {$pn}  qty={$qty}  reorder={$reorder}{$flag}\n";
    }

    echo "\n  Total parts inserted/updated: " . count($parts) . "\n";
    echo "  Parts at or below reorder level: {$low_count}\n";

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 3 — SUMMARY COUNTS
// ─────────────────────────────────────────────────────────────────────────────
wo_seed_section('Database summary', function () use ($pdo) {

    $counts = [
        'users'        => "SELECT COUNT(*) FROM users",
        'assets'       => "SELECT COUNT(*) FROM assets",
        'tickets'      => "SELECT COUNT(*) FROM tickets",
        'work_orders'  => "SELECT COUNT(*) FROM work_orders",
        'parts'        => "SELECT COUNT(*) FROM parts_inventory",
        'checklists'   => "SELECT COUNT(*) FROM wo_checklists",
        'safety_checks'=> "SELECT COUNT(*) FROM wo_safety_checks",
    ];

    foreach ($counts as $label => $sql) {
        $n = (int) $pdo->query($sql)->fetchColumn();
        echo "  {$label}: {$n}\n";
    }

    echo "\n  Work orders by status:\n";
    $rows = $pdo->query("
        SELECT status, COUNT(*) AS n FROM work_orders GROUP BY status ORDER BY status
    ")->fetchAll();
    foreach ($rows as $r) {
        echo "    {$r['status']}: {$r['n']}\n";
    }

    echo "\n  Work orders by type:\n";
    $rows = $pdo->query("
        SELECT wo_type, COUNT(*) AS n FROM work_orders GROUP BY wo_type ORDER BY wo_type
    ")->fetchAll();
    foreach ($rows as $r) {
        echo "    {$r['wo_type']}: {$r['n']}\n";
    }

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// OUTPUT
// ─────────────────────────────────────────────────────────────────────────────
output:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MTRTS — Work Order Seed</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1   { color: #38bdf8; margin-bottom: 1.5rem; }
  h2   { color: #7dd3fc; margin: 1.5rem 0 0.5rem; border-bottom: 1px solid #334155; padding-bottom: 0.25rem; }
  pre  { background: #1e293b; padding: 1rem; border-radius: 6px; line-height: 1.6; white-space: pre-wrap; }
  .ok  { color: #4ade80; }
  .err { color: #f87171; background: #1e293b; padding: 1rem; border-radius: 6px; margin-top: 1rem; }
  .tip { background: #1e293b; border-left: 4px solid #38bdf8; padding: 1rem; margin-top: 2rem; border-radius: 4px; color: #94a3b8; }
</style>
</head>
<body>
<h1>MTRTS — Work Order Seed</h1>

<?php foreach ($sections as $s): ?>
<h2 class="ok">✓ <?= htmlspecialchars($s['title']) ?></h2>
<pre><?= htmlspecialchars(implode("\n", $s['lines'])) ?></pre>
<?php endforeach; ?>

<?php if ($errors): ?>
<h2 style="color:#f87171">✗ Errors</h2>
<?php foreach ($errors as $e): ?>
<div class="err"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (empty($errors)): ?>
<div class="tip">
  <strong>Next steps</strong><br><br>
  Log in as <strong>technician@olfu.edu.ph</strong> (password: <code>123123123</code>) to see work orders in Technician Ops.<br>
  Log in as <strong>itstaff@olfu.edu.ph</strong> or <strong>itmanager@olfu.edu.ph</strong> to manage work orders from the admin side.<br><br>
  Work orders with status <code>new</code> appear in the queue for technicians to claim.<br>
  Work orders with status <code>assigned</code> / <code>in_progress</code> appear in the technician's active jobs list.
</div>
<?php endif; ?>

</body>
</html>
