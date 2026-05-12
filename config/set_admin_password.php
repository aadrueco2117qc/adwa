<?php
/**
 * One-time admin password setup script.
 * Run once after resetting the database
 * Usage: visit http://localhost/mtrts/config/set_admin_password.php
 */

require_once __DIR__ . '/db.php';

$new_password = '123123123';

$hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare(

    "UPDATE users SET password_hash = ? WHERE email = 'admin@olfu.edu.ph' LIMIT 1"
);
$stmt->execute([$hash]);

if ($stmt->rowCount() === 1) {
    echo "<pre>Password updated successfully.\n";
    echo "Email : admin\@olfu.edu.ph\n";
    echo "Pass  : {$new_password}\n\n";
} else {
    echo "<pre>No rows updated. Check that admin\@olfu.edu.ph exists in the users table.</pre>";
}