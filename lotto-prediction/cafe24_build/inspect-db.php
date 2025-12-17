<?php
// DB 스키마 확인 스크립트
$db_path = __DIR__ . '/data/lotto.db';

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Schema ===\n\n";
    
    // Get all tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $schema = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($schema as $col) {
            echo "  - {$col['name']} ({$col['type']}) " . ($col['notnull'] ? 'NOT NULL' : '') . "\n";
        }
        echo "\n";
    }
    
    // Sample data
    echo "=== Sample Data ===\n\n";
    
    echo "Users:\n";
    $users = $db->query("SELECT * FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
    
    echo "\nSaved Combinations:\n";
    $combos = $db->query("SELECT * FROM saved_combinations LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    print_r($combos);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
