<?php
require_once __DIR__ . '/../config/db.php';

$pass = password_hash('123123123', PASSWORD_BCRYPT, ['cost' => 12]);

echo "Seeding users...\n";
$users = [
    ['jdelacruz@olfu.edu.ph', 'Juan Dela Cruz', 'T-1001', '09171234567', 'IT Staff', 1, 3],
    ['msantos@olfu.edu.ph', 'Maria Santos', 'T-1002', '09171234568', 'Technician', 1, 4],
    ['aramos@olfu.edu.ph', 'Antonio Ramos', 'T-1003', '09171234569', 'Technician', 1, 4],
    ['blopez@olfu.edu.ph', 'Bianca Lopez', 'F-2001', '09181234561', 'Professor', 2, 5],
    ['rgomez@olfu.edu.ph', 'Roberto Gomez', 'F-2002', '09181234562', 'Professor', 3, 5],
    ['nreyes@olfu.edu.ph', 'Nina Reyes', 'S-3001', '09191234561', 'Admin Staff', 8, 6],
    ['ptan@olfu.edu.ph', 'Paul Tan', 'M-4001', '09201234561', 'IT Manager', 1, 2]
];

$stmtUser = $pdo->prepare("INSERT IGNORE INTO users (email, password_hash, full_name, id_number, contact_number, position, department_id, role_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($users as $u) {
    $stmtUser->execute([$u[0], $pass, $u[1], $u[2], $u[3], $u[4], $u[5], $u[6]]);
}

echo "Seeding assets...\n";
$stmtAsset = $pdo->prepare("
    INSERT INTO assets (asset_tag, serial_number, manufacturer, model, category_id, status, location_id, install_date, department_id)
    VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)
");

$categories = [
    1 => ['Epson', 'PowerLite 119W'],
    2 => ['JBL', 'EON610'],
    3 => ['Crestron', 'DM-MD8X8'],
    4 => ['Samsung', 'QM55R']
];

$locations = $pdo->query("SELECT location_id FROM locations")->fetchAll(PDO::FETCH_COLUMN);
if(empty($locations)) $locations = [1];

$count = 1000;
for ($i = 1; $i <= 30; $i++) {
    $cat_id = array_rand($categories);
    $make = $categories[$cat_id][0];
    $model = $categories[$cat_id][1];
    $tag = 'AST-' . str_pad($count++, 5, '0', STR_PAD_LEFT);
    $sn = 'SN' . rand(100000, 999999);
    $loc = $locations[array_rand($locations)];
    $date = date('Y-m-d', strtotime('-' . rand(30, 1000) . ' days'));
    $dept = rand(1, 8);
    
    $stmtAsset->execute([$tag, $sn, $make, $model, $cat_id, $loc, $date, $dept]);
}

echo "Seeding complete!\n";
