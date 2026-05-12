<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE locations ADD COLUMN timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Manila' AFTER room");
    echo "Migration successful: Added timezone column to locations.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
