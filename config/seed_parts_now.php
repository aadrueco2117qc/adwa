<?php
/**
 * One-time runner: seeds parts_inventory from the canonical list in database.sql.
 * Safe to run multiple times — uses ON DUPLICATE KEY UPDATE.
 * Delete this file after running.
 */
require_once __DIR__ . '/db.php';

$sql = "
INSERT INTO parts_inventory
    (part_number, part_name, category, quantity_on_hand, reorder_level, unit_price, is_active)
VALUES
  ('CABLE-HDMI-001','HDMI cable','cables',50,5,12.50,1),
  ('CABLE-VGA-001','VGA cable','cables',35,5,8.00,1),
  ('CABLE-DP-001','DisplayPort cable','cables',3,5,15.00,1),
  ('CABLE-AUX-001','AUX 3.5mm cable','cables',45,5,5.00,1),
  ('CABLE-XLR-001','XLR cable','cables',2,5,18.00,1),
  ('CABLE-ETH-001','Ethernet cable','cables',60,10,6.00,1),
  ('CABLE-USB-001','USB cable','cables',55,10,7.50,1),
  ('CABLE-COX-001','Coaxial cable','cables',4,5,9.00,1),
  ('PROJ-LAMP-001','Projector lamp','projector',1,3,120.00,1),
  ('PROJ-AFIL-001','Air filter','projector',12,5,8.50,1),
  ('PROJ-LCD-001','LCD panel','projector',0,2,350.00,1),
  ('PROJ-BALL-001','Ballast (lamp driver)','projector',5,2,85.00,1),
  ('PROJ-LENS-001','Lens assembly','projector',3,2,200.00,1),
  ('PROJ-DLP-001','DLP chip','projector',1,1,450.00,1),
  ('PROJ-FAN-001','Cooling fan (projector)','projector',8,4,25.00,1),
  ('AUD-SPK-001','Speaker driver','audio',10,4,45.00,1),
  ('AUD-JACK-001','Audio jack 3.5mm','audio',40,10,3.50,1),
  ('AUD-XLR-001','XLR connector','audio',25,8,12.00,1),
  ('AUD-VOL-001','Volume potentiometer','audio',3,6,8.00,1),
  ('AUD-AMP-001','Amplifier board','audio',2,3,75.00,1),
  ('AUD-TRF-001','Audio transformer','audio',6,3,35.00,1),
  ('ELEC-PWR-001','Power cable (AC)','electrical',30,8,10.00,1),
  ('ELEC-ADP-001','Power adapter','electrical',18,6,22.00,1),
  ('ELEC-F5A-001','Fuse 5A','electrical',5,15,2.50,1),
  ('ELEC-F10A-001','Fuse 10A','electrical',8,15,2.75,1),
  ('ELEC-CBK-001','Circuit breaker','electrical',10,4,18.00,1),
  ('ELEC-PST-001','Power strip','electrical',12,5,28.00,1),
  ('ELEC-SPG-001','Surge protector','electrical',4,5,35.00,1),
  ('ELCN-C100-001','Capacitor 100uF','electronic',80,20,1.50,1),
  ('ELCN-C470-001','Capacitor 470uF','electronic',15,20,2.00,1),
  ('ELCN-R10K-001','Resistor 10kOhm','electronic',100,25,0.50,1),
  ('ELCN-DDE-001','Diode','electronic',90,25,0.75,1),
  ('ELCN-TRN-001','Transistor','electronic',18,20,1.25,1),
  ('ELCN-RLY-001','IC relay','electronic',35,10,5.50,1),
  ('ELCN-MOS-001','MOSFET','electronic',50,15,3.00,1),
  ('COOL-F80-001','Cooling fan 80mm','cooling',14,6,12.00,1),
  ('COOL-F120-001','Cooling fan 120mm','cooling',5,6,15.00,1),
  ('COOL-TPS-001','Thermal paste','cooling',22,8,6.50,1),
  ('COOL-HSK-001','Heat sink','cooling',3,5,18.00,1),
  ('COOL-DST-001','Dust filter','cooling',28,10,4.00,1),
  ('MNT-M3-001','M3 screw set','mounting',40,12,5.00,1),
  ('MNT-M4-001','M4 screw set','mounting',38,12,5.50,1),
  ('MNT-BKT-001','Bracket kit','mounting',6,8,15.00,1),
  ('MNT-TIE-001','Cable ties','mounting',75,20,3.00,1),
  ('MNT-WPL-001','Wall plate','mounting',2,6,8.50,1),
  ('MNT-RMR-001','Rack mount rails','mounting',1,4,45.00,1)
ON DUPLICATE KEY UPDATE
  part_name        = VALUES(part_name),
  category         = VALUES(category),
  quantity_on_hand = VALUES(quantity_on_hand),
  reorder_level    = VALUES(reorder_level),
  unit_price       = VALUES(unit_price),
  is_active        = 1,
  updated_at       = CURRENT_TIMESTAMP;
";

try {
    $pdo->exec($sql);
    $count = $pdo->query("SELECT COUNT(*) FROM parts_inventory")->fetchColumn();
    echo "<pre style='font-family:monospace;background:#0f172a;color:#4ade80;padding:2rem;'>";
    echo "✓ Parts inventory seeded successfully.\n";
    echo "  Total parts in inventory: {$count}\n\n";
    echo "You can delete this file now: config/seed_parts_now.php\n";
    echo "</pre>";
} catch (Throwable $e) {
    echo "<pre style='color:red;padding:2rem;'>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
