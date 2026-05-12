<?php
// modules/workorders/get_kb_ajax.php
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$ticket_id = (int)($_GET['ticket_id'] ?? 0);
if ($ticket_id < 1) {
    echo json_encode(['kb' => []]);
    exit;
}

// Get the asset details for this ticket
$stmt = $pdo->prepare("
    SELECT a.category_id, a.model, l.building, l.room 
    FROM tickets t 
    JOIN assets a ON t.asset_id = a.asset_id 
    LEFT JOIN locations l ON a.location_id = l.location_id
    WHERE t.ticket_id = ?
");
$stmt->execute([$ticket_id]);
$asset = $stmt->fetch();

if (!$asset) {
    echo json_encode(['kb' => []]);
    exit;
}

$cat_id = $asset['category_id'];
$model  = $asset['model'];
$loc    = $asset['building'] . ' ' . $asset['room'];

// 1. Get category-based articles (Playbooks/Triage)
$kb_cat = get_related_kb_articles($pdo, $cat_id);

// 2. Search for model/location specific issues
$stmt = $pdo->prepare("
    SELECT article_id, title, content, updated_at
    FROM kb_articles
    WHERE (title LIKE ? OR content LIKE ? OR tags LIKE ?)
    AND article_id NOT IN (" . implode(',', array_column($kb_cat, 'article_id') ?: [0]) . ")
    LIMIT 3
");
$term_model = "%$model%";
$term_loc   = "%$loc%";
$stmt->execute([$term_model, $term_model, $term_model]);
$kb_model = $stmt->fetchAll();

// Combine
$kb_all = array_merge($kb_cat, $kb_model);

echo json_encode(['kb' => $kb_all]);
