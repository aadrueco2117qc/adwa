<?php
// modules/assets/categories_list.php — AJAX: returns all categories with asset counts
$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT c.category_id, c.category_name, c.has_bulb_hours, c.description,
           COUNT(a.asset_id) AS asset_count
    FROM asset_categories c
    LEFT JOIN assets a ON a.category_id = c.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
")->fetchAll();

echo json_encode($rows);
