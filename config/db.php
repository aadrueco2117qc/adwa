<?php
define('DB_HOST', 'localhost'); // CHANGE this depending on your database specifications
define('DB_NAME', 'mtrts_sql');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL — adjust if deployed under a sub-folder
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mtrts/');
}

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
  try {
    $dsn = "mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  } catch (PDOException $e2) {
    die("Database connection failed on both ports (3306 and 3307). Error: " . $e2->getMessage());
  }
}

/**
 * ── SYSTEM AUDIT LOGGING ──────────────────────────────────────
 * Global function to record immutable audit logs.
 * Accessible everywhere via config/db.php
 */
function log_audit(PDO $pdo, string $action, string $object_type, ?int $object_id = null, ?array $old_values = null, ?array $new_values = null): void {
    // If we're in a session, get the user ID
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

    $stmt = $pdo->prepare("
        INSERT INTO audit_log 
        (user_id, action, object_type, object_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $action,
        $object_type,
        $object_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $ip,
        $agent
    ]);
}
?>