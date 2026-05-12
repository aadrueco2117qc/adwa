<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE ticket_sla ADD COLUMN escalation_level TINYINT NOT NULL DEFAULT 0 AFTER pause_reason");
    echo "Migration successful: Added escalation_level to ticket_sla.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
