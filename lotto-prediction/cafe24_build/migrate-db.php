<?php
/**
 * Database Migration Script
 * Makes user_id nullable in saved_combinations table to support guest combinations
 */

$db_path = __DIR__ . '/data/lotto.db';

try {
    echo "=== Database Migration Started ===\n\n";
    
    // Backup database first
    $backup_path = __DIR__ . '/data/lotto_backup_' . date('Y-m-d_H-i-s') . '.db';
    if (!copy($db_path, $backup_path)) {
        throw new Exception("Failed to create backup");
    }
    echo "✓ Backup created: $backup_path\n";
    
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current schema
    echo "\n--- Current Schema ---\n";
    $schema = $db->query("PRAGMA table_info(saved_combinations)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($schema as $col) {
        echo "{$col['name']} - Type: {$col['type']}, NotNull: {$col['notnull']}\n";
    }
    
    // Create new table with nullable user_id
    echo "\n--- Creating New Table ---\n";
    $db->exec("CREATE TABLE saved_combinations_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        draw_number INTEGER NOT NULL,
        numbers TEXT NOT NULL,
        session_id TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id)
    )");
    echo "✓ New table created\n";
    
    // Copy data from old table
    echo "\n--- Migrating Data ---\n";
    $count = $db->query("SELECT COUNT(*) FROM saved_combinations")->fetchColumn();
    echo "Found $count records to migrate\n";
    
    $db->exec("INSERT INTO saved_combinations_new (id, user_id, draw_number, numbers, created_at)
               SELECT id, user_id, draw_number, numbers, created_at 
               FROM saved_combinations");
    echo "✓ Data migrated\n";
    
    // Drop old table and rename new one
    echo "\n--- Replacing Table ---\n";
    $db->exec("DROP TABLE saved_combinations");
    $db->exec("ALTER TABLE saved_combinations_new RENAME TO saved_combinations");
    echo "✓ Table replaced\n";
    
    // Verify new schema
    echo "\n--- New Schema ---\n";
    $new_schema = $db->query("PRAGMA table_info(saved_combinations)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($new_schema as $col) {
        echo "{$col['name']} - Type: {$col['type']}, NotNull: {$col['notnull']}\n";
    }
    
    // Verify data count
    $new_count = $db->query("SELECT COUNT(*) FROM saved_combinations")->fetchColumn();
    echo "\n--- Verification ---\n";
    echo "Records after migration: $new_count\n";
    
    if ($new_count == $count) {
        echo "✓ All records migrated successfully\n";
    } else {
        throw new Exception("Data count mismatch! Before: $count, After: $new_count");
    }
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "Backup: $backup_path\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration Failed: " . $e->getMessage() . "\n";
    if (isset($backup_path) && file_exists($backup_path)) {
        echo "\nTo restore backup, run:\n";
        echo "copy \"$backup_path\" \"$db_path\"\n";
    }
    exit(1);
}
?>
