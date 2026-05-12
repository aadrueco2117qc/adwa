<?php
$module = 'technician';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

$wo_id = (int)($_GET['id'] ?? 0);
if (!$wo_id) {
    header('Location: ' . BASE_URL . 'modules/technician/index.php');
    exit;
}

$wo = get_work_order_detail($pdo, $wo_id);
if (!$wo) {
    http_response_code(404);
    echo 'Work order not found';
    exit;
}

// Everyone can view all work orders. Execution is role-gated (queue-without-claim).
$user_id = (int)($_SESSION['user_id'] ?? 0);
$role_id = (int)($_SESSION['role_id'] ?? 0);
$is_admin = tech_is_admin_role($pdo);
$assigned_role_id = technician_has_role_queue_schema($pdo) ? (int)($wo['assigned_role_id'] ?? 0) : 0;
$assigned_to = (int)($wo['assigned_to'] ?? 0);

// Access Logic: Admin or the specific Assigned Technician. 
// Fallback to role-based if no specific user is assigned yet.
$can_edit = $is_admin || ($assigned_to > 0 && $assigned_to === $user_id) || ($assigned_to === 0 && $assigned_role_id > 0 && $assigned_role_id === $role_id);
$status_key = strtolower(trim((string)($wo['status'] ?? '')));
$can_execute_now = $can_edit && $status_key === 'in_progress';

// #region agent log
tech_dbg('H_ACCESS', 'modules/technician/view.php:access', 'Computed access', [
    'wo_id' => $wo_id,
    'session_role_id' => $role_id,
    'assigned_role_id' => $assigned_role_id,
    'is_admin' => $is_admin,
    'can_edit' => $can_edit,
    'status' => $status_key,
    'can_execute_now' => $can_execute_now,
]);
// #endregion

$checklist = get_checklist_for_work_order($pdo, $wo_id);
$safety_checks = get_safety_checks_for_work_order($pdo, $wo_id);

// Debug: Log what we got from the database
tech_dbg('H_VIEW_DATA', 'modules/technician/view.php:data', 'Loaded checklists and safety', [
    'wo_id' => $wo_id,
    'checklist_count' => count($checklist),
    'safety_count' => count($safety_checks),
    'checklist_done' => count(array_filter($checklist, fn($i) => $i['is_done'])),
    'safety_done' => count(array_filter($safety_checks, fn($i) => $i['is_done'])),
]);

// FALLBACK: Only use if database has no checklist/safety data seeded
// These IDs must match what the wo_safety_checks and wo_checklist_items tables would have
if (empty($checklist)) {
    // Create fallback with proper completion status checking
    $fallback_checklist = [
        ['item_id' => 1, 'item_text' => 'Capture before-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_before'],
        ['item_id' => 2, 'item_text' => 'Perform visual inspection', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null],
        ['item_id' => 3, 'item_text' => 'Perform power-on test', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null],
        ['item_id' => 4, 'item_text' => 'Verify core functionality', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null],
        ['item_id' => 5, 'item_text' => 'Document findings and actions', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null],
        ['item_id' => 6, 'item_text' => 'Capture after-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_after'],
    ];
    
    // Check for any completions for this work order
    try {
        $stmt = $pdo->prepare("SELECT item_id, is_done FROM wo_checklist_completions WHERE wo_id = ?");
        $stmt->execute([$wo_id]);
        $completions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($fallback_checklist as &$item) {
            $item['is_done'] = (int)($completions[$item['item_id']] ?? 0);
        }
    } catch (Throwable $e) {
        foreach ($fallback_checklist as &$item) {
            $item['is_done'] = 0;
        }
    }
    
    $checklist = $fallback_checklist;
}

if (empty($safety_checks)) {
    // Create fallback with proper completion status checking
    $fallback_safety = [
        ['safety_id' => 1, 'safety_text' => 'Verify ESD protection (wrist strap or mat) is in place', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 2, 'safety_text' => 'Confirm equipment is powered off and unplugged', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 3, 'safety_text' => 'Ensure proper personal protective equipment (PPE) is worn', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 4, 'safety_text' => 'Clear workspace of clutter and hazards', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 5, 'safety_text' => 'Verify tools are in good working condition', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 6, 'safety_text' => 'Check for visible damage before starting work', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 7, 'safety_text' => 'Test bench voltage verified safe (multimeter checked)', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 8, 'safety_text' => 'Required parts and replacements inventoried', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 9, 'safety_text' => 'Static-safe mat and containers in use', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 10, 'safety_text' => 'Proper disposal container for old/hazardous parts ready', 'is_mandatory' => 1, 'is_done' => 0],
        ['safety_id' => 11, 'safety_text' => 'Safety glasses or face shield worn (when required)', 'is_mandatory' => 1, 'is_done' => 0],
    ];
    
    // Check for any completions for this work order
    try {
        $stmt = $pdo->prepare("SELECT safety_id, is_done FROM wo_safety_completions WHERE wo_id = ?");
        $stmt->execute([$wo_id]);
        $completions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($fallback_safety as &$item) {
            $item['is_done'] = (int)($completions[$item['safety_id']] ?? 0);
        }
    } catch (Throwable $e) {
        foreach ($fallback_safety as &$item) {
            $item['is_done'] = 0;
        }
    }
    
    $safety_checks = $fallback_safety;
}

// Remove photo items from the manual checklist — they are rendered as
// auto-verified rows in the view (photo_before / photo_after). Filter by
// verification_type first; fall back to item_text for DB rows where the
// column was not populated.
$photo_texts = ['capture before-repair photo', 'capture after-repair photo'];
$checklist = array_values(array_filter($checklist, fn($item) =>
    !in_array($item['verification_type'] ?? '', ['photo_before', 'photo_after']) &&
    !in_array(strtolower(trim($item['item_text'] ?? '')), $photo_texts)
));

$time_logs = get_time_logs($pdo, $wo_id);
$total_time = calculate_total_time($time_logs);
$notes = get_work_order_notes($pdo, $wo_id);
$media = get_work_order_media($pdo, $wo_id);
// Count before/after photos for photo-gated checklist items
$has_before_photo = !empty(array_filter($media ?? [], fn($m) => ($m['media_type'] ?? '') === 'photo_before'));
$has_after_photo  = !empty(array_filter($media ?? [], fn($m) => ($m['media_type'] ?? '') === 'photo_after'));
$parts = get_work_order_parts($pdo, $wo_id);
$signoff = get_work_order_signoff($pdo, $wo_id);

// Fetch users eligible to be an Authorized Signatory (roles with technician module access)
try {
    $stmt_sig = $pdo->prepare("
        SELECT DISTINCT u.user_id, u.full_name, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        JOIN role_modules rm ON r.role_id = rm.role_id
        WHERE rm.module_slug = 'technician'
          AND u.is_active = 1
        ORDER BY r.role_id, u.full_name
    ");
    $stmt_sig->execute();
    $signatory_users = $stmt_sig->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    tech_dbg('H_SIGNATORY', 'modules/technician/view.php:signatory_query', 'Failed to fetch signatory users', [
        'wo_id' => $wo_id,
        'error' => $e->getMessage(),
    ]);
    $signatory_users = [];
}

// Fetch all active parts from inventory for the browse panel
try {
    $stmt_inv = $pdo->prepare("
        SELECT part_id, part_number, part_name, category,
               quantity_on_hand, reorder_level, unit_price
        FROM parts_inventory
        WHERE is_active = 1
        ORDER BY category, part_name
    ");
    $stmt_inv->execute();
    $inventory_parts = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $inventory_parts = [];
}

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
?>