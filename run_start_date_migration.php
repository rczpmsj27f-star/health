<?php
/**
 * Start Date Migration Runner
 * Run this once to add start_date column to medications table
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Start Date Migration</h1>";
echo "<pre>";

try {
    $migrationFile = __DIR__ . '/database/migrations/migration_add_medication_start_date.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n\n";
    
    // Verify column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM medications LIKE 'start_date'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'start_date' added to medications table\n";
        $row = $stmt->fetch();
        echo "  Type: " . $row['Type'] . "\n";
        echo "  Null: " . $row['Null'] . "\n";
        echo "  Default: " . ($row['Default'] ?? 'NULL') . "\n";
    }
    
    // Verify index was created
    $stmt = $pdo->query("SHOW INDEX FROM medications WHERE Key_name = 'idx_medications_start_date'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Index 'idx_medications_start_date' created\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>";
echo "<p><strong>Delete this file after running.</strong></p>";
