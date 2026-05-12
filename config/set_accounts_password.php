<?php
/**
 * Full seed script — accounts, assets, tickets, and work orders.
 * Run once after resetting the database (database.sql must be imported first).
 * Usage: visit http://localhost/mtrts-main/config/set_accounts_password.php
 *
 * Creates:
 *  - 1 account per role (admin, it_manager, it_staff, technician,
 *    faculty, department_staff, student, super_admin)
 *  - 8 assets (one per category)
 *  - 8 tickets (one per asset, various priorities)
 *  - 3 work orders per asset category (24 total), linked to tickets,
 *    assigned to the technician account — visible in Technician Ops
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../modules/workorders/functions.php';

// ── Shared password ───────────────────────────────────────────────────────────
$password     = '123123123';
$hash         = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$errors   = [];
$sections = [];

// ── Helper ────────────────────────────────────────────────────────────────────
function seed_section(string $title, callable $fn, array &$sections, array &$errors): void {
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
// SECTION 1 — ACCOUNTS (1 per role)
// ─────────────────────────────────────────────────────────────────────────────
seed_section('Accounts', function () use ($pdo, $hash, $password) {

    $roles_to_seed = [
        'admin'            => ['Admin User',            'admin@olfu.edu.ph'],
        'super_admin'      => ['Super Admin',           'superadmin@olfu.edu.ph'],
        'it_manager'       => ['IT Manager',            'itmanager@olfu.edu.ph'],
        'it_staff'         => ['IT Staff',              'itstaff@olfu.edu.ph'],
        'technician'       => ['Tech Reyes',            'technician@olfu.edu.ph'],
        'faculty'          => ['Prof. Santos',          'faculty@olfu.edu.ph'],
        'department_staff' => ['Dept Staff Cruz',       'deptstaff@olfu.edu.ph'],
        'student'          => ['Student Dela Cruz',     'student@olfu.edu.ph'],
    ];

    // Fetch role IDs
    $placeholders = implode(',', array_fill(0, count($roles_to_seed), '?'));
    $stmt = $pdo->prepare("SELECT role_name, role_id FROM roles WHERE role_name IN ({$placeholders})");
    $stmt->execute(array_keys($roles_to_seed));
    $role_ids = array_column($stmt->fetchAll(), 'role_id', 'role_name');

    $missing = array_diff(array_keys($roles_to_seed), array_keys($role_ids));
    if ($missing) {
        throw new RuntimeException('Missing roles in DB: ' . implode(', ', $missing));
    }

    $upsert = $pdo->prepare("
        INSERT INTO users (email, password_hash, full_name, role_id, is_active)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            full_name     = VALUES(full_name),
            role_id       = VALUES(role_id),
            is_active     = 1,
            updated_at    = CURRENT_TIMESTAMP
    ");

    foreach ($roles_to_seed as $role_name => [$full_name, $email]) {
        $upsert->execute([$email, $hash, $full_name, $role_ids[$role_name]]);
        echo "  {$role_name}: {$email} / {$password}\n";
    }

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 2 — ASSETS (1 per category)
// ─────────────────────────────────────────────────────────────────────────────
seed_section('Assets', function () use ($pdo) {

    // Fetch category IDs
    $cats = $pdo->query("SELECT category_id, category_name FROM asset_categories ORDER BY category_id")
                ->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fetch first location
    $loc_id = (int) $pdo->query("SELECT location_id FROM locations LIMIT 1")->fetchColumn();

    // Fetch the IT staff user to set as created_by
    $creator_id = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'itstaff@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    $assets = [
        // [asset_tag, serial, manufacturer, model, category_name, install_date]
        ['ASSET-PROJ-001',  'SN-PROJ-001',  'Epson',     'EB-X51',          'Projector',    '2023-01-15'],
        ['ASSET-SND-001',   'SN-SND-001',   'Yamaha',    'MG10XU',          'Sound System', '2023-02-20'],
        ['ASSET-AVS-001',   'SN-AVS-001',   'Kramer',    'VS-411X',         'AV Switcher',  '2023-03-10'],
        ['ASSET-DISP-001',  'SN-DISP-001',  'Samsung',   'QM55R',           'Display',      '2023-04-05'],
        ['ASSET-MIC-001',   'SN-MIC-001',   'Shure',     'SM58',            'Microphone',   '2023-05-01'],
        ['ASSET-RACK-001',  'SN-RACK-001',  'Middle Atlantic', 'ERK-2026', 'AV Rack',      '2023-06-12'],
        ['ASSET-CAM-001',   'SN-CAM-001',   'Sony',      'HXR-NX80',        'Camera',       '2023-07-18'],
        ['ASSET-AMP-001',   'SN-AMP-001',   'Crown',     'XLi 800',         'Amplifier',    '2023-08-22'],
    ];

    // Reverse-map category name → id
    $cat_by_name = array_flip($cats);

    $insert = $pdo->prepare("
        INSERT INTO assets
            (asset_tag, serial_number, manufacturer, model, category_id,
             status, location_id, install_date, created_by)
        VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            serial_number = VALUES(serial_number),
            manufacturer  = VALUES(manufacturer),
            model         = VALUES(model),
            updated_at    = CURRENT_TIMESTAMP
    ");

    foreach ($assets as [$tag, $serial, $mfr, $model, $cat_name, $install]) {
        $cat_id = $cat_by_name[$cat_name] ?? null;
        if (!$cat_id) { echo "  SKIP (no category): {$cat_name}\n"; continue; }
        $insert->execute([$tag, $serial, $mfr, $model, $cat_id, $loc_id, $install, $creator_id ?: null]);
        echo "  {$tag} — {$mfr} {$model} ({$cat_name})\n";
    }

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 3 — TICKETS (1 per asset)
// ─────────────────────────────────────────────────────────────────────────────
seed_section('Tickets', function () use ($pdo) {

    // Requester = faculty account
    $requester_id = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'faculty@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    $loc_id = (int) $pdo->query("SELECT location_id FROM locations LIMIT 1")->fetchColumn();

    $ticket_defs = [
        // [asset_tag, title, description, priority, category_name]
        ['ASSET-PROJ-001',  'Projector not displaying image',
         'The projector in Room 101 powers on but shows no image on screen.',
         'high',     'Projector'],
        ['ASSET-SND-001',   'Sound system producing feedback noise',
         'Loud feedback squeal when microphone is used with the sound system.',
         'high',     'Sound System'],
        ['ASSET-AVS-001',   'AV switcher input 2 not working',
         'Input 2 on the AV switcher does not pass signal to the output.',
         'medium',   'AV Switcher'],
        ['ASSET-DISP-001',  'Display shows black screen on HDMI input',
         'The Samsung display shows a black screen when connected via HDMI.',
         'medium',   'Display'],
        ['ASSET-MIC-001',   'Microphone cutting out intermittently',
         'The Shure SM58 microphone cuts out every few minutes during use.',
         'low',      'Microphone'],
        ['ASSET-RACK-001',  'AV rack power strip not working',
         'One of the power outlets in the AV rack is not supplying power.',
         'medium',   'AV Rack'],
        ['ASSET-CAM-001',   'Camera autofocus not functioning',
         'The Sony camera autofocus fails to lock on subjects.',
         'low',      'Camera'],
        ['ASSET-AMP-001',   'Amplifier overheating and shutting down',
         'The Crown amplifier shuts off after 10 minutes of use due to overheating.',
         'critical', 'Amplifier'],
    ];

    // Fetch asset IDs and category IDs
    $asset_rows = $pdo->query(
        "SELECT asset_tag, asset_id, category_id FROM assets"
    )->fetchAll(PDO::FETCH_UNIQUE);

    // Fetch category IDs by name
    $cat_ids = $pdo->query(
        "SELECT category_name, category_id FROM asset_categories"
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    // Generate ticket number
    $get_ticket_number = function () use ($pdo): string {
        $year = date('Y');
        $last = $pdo->query(
            "SELECT ticket_number FROM tickets WHERE ticket_number LIKE 'TKT-{$year}-%' ORDER BY ticket_id DESC LIMIT 1"
        )->fetchColumn();
        $seq = $last ? ((int) substr($last, strrpos($last, '-') + 1) + 1) : 1;
        return 'TKT-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    };

    $insert = $pdo->prepare("
        INSERT INTO tickets
            (ticket_number, requester_id, asset_id, category_id, location_id,
             title, description, impact, urgency, priority, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    ");

    foreach ($ticket_defs as [$asset_tag, $title, $desc, $priority, $cat_name]) {
        $asset_id  = $asset_rows[$asset_tag]['asset_id']   ?? null;
        $cat_id    = $cat_ids[$cat_name]                   ?? null;
        if (!$asset_id) { echo "  SKIP (asset not found): {$asset_tag}\n"; continue; }

        $ticket_number = $get_ticket_number();
        $insert->execute([
            $ticket_number, $requester_id, $asset_id, $cat_id, $loc_id,
            $title, $desc, $priority, $priority, $priority,
        ]);
        echo "  {$ticket_number} — {$title} [{$priority}]\n";
    }

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// SECTION 4 — WORK ORDERS (3 per asset category, linked to tickets)
// ─────────────────────────────────────────────────────────────────────────────
seed_section('Work Orders', function () use ($pdo) {

    // Assigned to the technician account
    $tech_id = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'technician@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    // Created by IT staff
    $creator_id = (int) $pdo->query(
        "SELECT user_id FROM users WHERE email = 'itstaff@olfu.edu.ph' LIMIT 1"
    )->fetchColumn();

    // Fetch tickets with their asset category
    $tickets = $pdo->query("
        SELECT t.ticket_id, t.ticket_number, ac.category_name
        FROM tickets t
        JOIN assets a ON t.asset_id = a.asset_id
        JOIN asset_categories ac ON a.category_id = ac.category_id
        ORDER BY t.ticket_id
    ")->fetchAll();

    if (empty($tickets)) {
        echo "  No tickets found — skipping work orders.\n";
        return;
    }

    // 3 WO types to cycle through per ticket
    $wo_cycle = [
        ['diagnosis',   'Initial diagnosis of reported issue',                    'new'],
        ['repair',      'Repair work based on diagnosis findings',                'assigned'],
        ['maintenance', 'Scheduled preventive maintenance after repair',          'assigned'],
    ];

    $insert = $pdo->prepare("
        INSERT INTO work_orders
            (wo_number, ticket_id, wo_type, assigned_to, assigned_by,
             status, scheduled_start, scheduled_end, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $log_assign = $pdo->prepare("
        INSERT INTO wo_assignment_log (wo_id, assigned_to, assigned_by, reason)
        VALUES (?, ?, ?, 'Seed assignment')
    ");

    $base_start = new DateTime('2026-05-12 09:00:00');
    $offset_days = 0;

    foreach ($tickets as $ticket) {
        foreach ($wo_cycle as $i => [$wo_type, $notes, $status]) {
            $wo_number = generate_wo_number($pdo);

            $start = (clone $base_start)->modify("+{$offset_days} days");
            $end   = (clone $start)->modify('+2 hours');

            $insert->execute([
                $wo_number,
                $ticket['ticket_id'],
                $wo_type,
                $tech_id   ?: null,
                $creator_id ?: null,
                $status,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                "[{$ticket['category_name']}] {$notes}",
                $creator_id ?: null,
            ]);

            $wo_id = (int) $pdo->lastInsertId();

            // Log assignment so it appears in assignment history
            if ($tech_id && $wo_id) {
                $log_assign->execute([$wo_id, $tech_id, $creator_id ?: $tech_id]);
            }

            echo "  {$wo_number} — {$wo_type} / {$ticket['category_name']} / {$ticket['ticket_number']} [{$status}]\n";
            $offset_days++;
        }
    }

}, $sections, $errors);

// ─────────────────────────────────────────────────────────────────────────────
// OUTPUT
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MTRTS Seed</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1   { color: #38bdf8; margin-bottom: 1.5rem; }
  h2   { color: #7dd3fc; margin: 1.5rem 0 0.5rem; border-bottom: 1px solid #334155; padding-bottom: 0.25rem; }
  pre  { background: #1e293b; padding: 1rem; border-radius: 6px; line-height: 1.6; white-space: pre-wrap; }
  .ok  { color: #4ade80; }
  .err { color: #f87171; background: #1e293b; padding: 1rem; border-radius: 6px; margin-top: 1rem; }
  .summary { background: #1e293b; border-left: 4px solid #38bdf8; padding: 1rem; margin-top: 2rem; border-radius: 4px; }
</style>
</head>
<body>
<h1>MTRTS — Seed Script</h1>

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

<div class="summary">
<strong>Login credentials (all accounts use the same password)</strong><br><br>
<table style="border-collapse:collapse;width:100%">
<tr style="color:#7dd3fc"><th align="left">Role</th><th align="left">Email</th><th align="left">Password</th></tr>
<?php
$creds = [
    ['admin',            'admin@olfu.edu.ph'],
    ['super_admin',      'superadmin@olfu.edu.ph'],
    ['it_manager',       'itmanager@olfu.edu.ph'],
    ['it_staff',         'itstaff@olfu.edu.ph'],
    ['technician',       'technician@olfu.edu.ph'],
    ['faculty',          'faculty@olfu.edu.ph'],
    ['department_staff', 'deptstaff@olfu.edu.ph'],
    ['student',          'student@olfu.edu.ph'],
];
foreach ($creds as [$role, $email]):
?>
<tr>
  <td style="padding:4px 16px 4px 0;color:#94a3b8"><?= $role ?></td>
  <td style="padding:4px 16px 4px 0"><?= $email ?></td>
  <td style="padding:4px 0;color:#fbbf24">123123123</td>
</tr>
<?php endforeach; ?>
</table>
<br>
<span style="color:#94a3b8">Work orders are assigned to <strong>technician@olfu.edu.ph</strong> and visible in Technician Ops.</span>
</div>
</body>
</html>
