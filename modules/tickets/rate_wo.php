<?php
// modules/tickets/rate_wo.php
// Accepts a requester's 1–5 star rating for a completed work order.
// POST only. Returns JSON.

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

// ── Auth ──────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// ── Method ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Input ─────────────────────────────────────────────────────
$wo_id    = (int)($_POST['wo_id']    ?? 0);
$rating   = (int)($_POST['rating']   ?? 0);
$feedback = trim((string)($_POST['feedback'] ?? ''));

if ($wo_id < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid work order ID.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ── Verify work order exists and is closed ────────────────────
$stmt = $pdo->prepare("
    SELECT wo.wo_id, wo.status, wo.ticket_id
    FROM work_orders wo
    WHERE wo.wo_id = ?
");
$stmt->execute([$wo_id]);
$wo = $stmt->fetch();

if (!$wo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Work order not found.']);
    exit;
}

if ($wo['status'] !== 'closed') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Work order is not yet closed.']);
    exit;
}

// ── Verify the authenticated user is the ticket requester ─────
if (empty($wo['ticket_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'This work order has no linked ticket.']);
    exit;
}

$stmt = $pdo->prepare("SELECT requester_id FROM tickets WHERE ticket_id = ?");
$stmt->execute([$wo['ticket_id']]);
$requester_id = (int)$stmt->fetchColumn();

if ($requester_id !== $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not the requester for this ticket.']);
    exit;
}

// ── Check not already rated ───────────────────────────────────
$stmt = $pdo->prepare("SELECT satisfaction FROM wo_signoff WHERE wo_id = ?");
$stmt->execute([$wo_id]);
$existing = $stmt->fetch();

if ($existing && $existing['satisfaction'] !== null) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Rating already submitted.']);
    exit;
}

// ── Write the rating ──────────────────────────────────────────
// wo_signoff row should already exist (created when technician completed the WO).
// If for some reason it doesn't, insert a minimal row.
if ($existing === false) {
    // No signoff row at all — insert one
    $stmt = $pdo->prepare("
        INSERT INTO wo_signoff (wo_id, signer_name, signature_path, satisfaction, feedback, signed_at)
        VALUES (?, '', 'data:inline', ?, ?, NOW())
    ");
    $stmt->execute([$wo_id, $rating, $feedback ?: null]);
} else {
    // Row exists, just update satisfaction + feedback
    $stmt = $pdo->prepare("
        UPDATE wo_signoff
        SET satisfaction = ?, feedback = ?
        WHERE wo_id = ?
    ");
    $stmt->execute([$rating, $feedback ?: null, $wo_id]);
}

echo json_encode(['success' => true, 'rating' => $rating]);
