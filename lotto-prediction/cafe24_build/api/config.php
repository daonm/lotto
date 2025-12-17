<?php
// config.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Connection
$db_path = __DIR__ . '/../data/lotto.db';

try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create tables if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if saved_combinations needs migration
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='saved_combinations'")->fetchAll();
    
    if (empty($tables)) {
        // Create new table with nullable user_id
        $db->exec("CREATE TABLE saved_combinations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            draw_number INTEGER NOT NULL,
            numbers TEXT NOT NULL,
            session_id TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )");
    } else {
        // Check if migration is needed
        $columns = $db->query("PRAGMA table_info(saved_combinations)")->fetchAll(PDO::FETCH_ASSOC);
        $has_session_id = false;
        $user_id_nullable = true;
        
        foreach ($columns as $col) {
            if ($col['name'] === 'session_id') {
                $has_session_id = true;
            }
            if ($col['name'] === 'user_id' && $col['notnull'] == 1) {
                $user_id_nullable = false;
            }
        }
        
        // Migrate if needed
        if (!$has_session_id || !$user_id_nullable) {
            // Create backup
            $backup_table = 'saved_combinations_backup_' . time();
            $db->exec("ALTER TABLE saved_combinations RENAME TO $backup_table");
            
            // Create new table
            $db->exec("CREATE TABLE saved_combinations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                draw_number INTEGER NOT NULL,
                numbers TEXT NOT NULL,
                session_id TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id)
            )");
            
            // Copy data
            $db->exec("INSERT INTO saved_combinations (id, user_id, draw_number, numbers, created_at)
                       SELECT id, user_id, draw_number, numbers, created_at FROM $backup_table");
            
            // Keep backup table for safety (can be manually deleted later)
        }
    }


} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// JWT Secret Key (Change this in production!)
define('JWT_SECRET', 'your_secret_key_here_make_it_long_and_complex');

// Helper function to verify JWT
function verify_jwt() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }

    $authHeader = $headers['Authorization'];
    list($type, $token) = explode(' ', $authHeader);

    if (strtolower($type) !== 'bearer' || !$token) {
        return null;
    }

    // Simple JWT decoding (for demonstration, use a library in production if possible)
    // Here we implement a basic verification to avoid external dependencies for simple hosting
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    $signature_provided = $parts[2];

    // Re-create signature
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    if ($base64UrlSignature === $signature_provided) {
        return $payload; // Returns user data
    }

    return null;
}
?>
