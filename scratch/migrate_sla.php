<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Add columns to sla_policies
    $pdo->exec("ALTER TABLE sla_policies 
        ADD COLUMN location_id INT NULL AFTER category_id, 
        ADD COLUMN request_type VARCHAR(50) NULL AFTER is_event_support");
    
    $pdo->exec("ALTER TABLE sla_policies 
        ADD CONSTRAINT fk_sla_location FOREIGN KEY (location_id) REFERENCES locations(location_id)");

    // Add request_type to tickets
    $pdo->exec("ALTER TABLE tickets 
        ADD COLUMN request_type VARCHAR(50) NULL AFTER is_event_support");

    echo "Migration successful: Added location_id and request_type to SLA policies and tickets.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
