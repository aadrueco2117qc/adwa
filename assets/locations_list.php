<?php
// modules/assets/locations_list.php — AJAX: returns all locations with asset counts
$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT l.location_id, l.building, l.floor, l.room,
           COUNT(a.asset_id) AS asset_count
    FROM locations l
    LEFT JOIN assets a ON a.location_id = l.location_id
    GROUP BY l.location_id
    ORDER BY l.building, l.floor, l.room
")->fetchAll();

echo json_encode($rows);
