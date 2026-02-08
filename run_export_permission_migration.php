<?php
/**
 * Database Migration Runner
 * Purpose: Add can_export_data permission column to user_link_permissions table
 * Usage: Run this file once via browser or CLI
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Database Migration Runner - Add Export Permission</h1>";
echo "<pre>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/migration_add_can_export_data_permission.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split into individual statements (handle multiple statements)
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\s]*$/m', $sql)
        ),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Running migration: migration_add_can_export_data_permission.sql\n";
    echo "Number of statements to execute: " . count($statements) . "\n\n";
    
    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "...\n";
        
        // Add semicolon back
        $statement = trim($statement) . ';';
        
        try {
            $pdo->exec($statement);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            // Check if error is "column already exists" which is okay
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ Column already exists (skipping)\n";
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "\n================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "================================\n\n";
    
    // Verify column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM user_link_permissions LIKE 'can_export_data'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'can_export_data' exists\n\n";
        
        // Show column structure
        echo "Column structure:\n";
        $row = $stmt->fetch();
        echo "  - {$row['Field']} ({$row['Type']})";
        if ($row['Null'] === 'NO') echo " NOT NULL";
        if ($row['Default'] !== null) echo " DEFAULT {$row['Default']}";
        echo "\n\n";
        
        // Show all columns in the table
        echo "All columns in user_link_permissions table:\n";
        $stmt = $pdo->query("DESCRIBE user_link_permissions");
        while ($row = $stmt->fetch()) {
            echo "  - {$row['Field']} ({$row['Type']})";
            if ($row['Null'] === 'NO') echo " NOT NULL";
            if ($row['Default'] !== null) echo " DEFAULT {$row['Default']}";
            echo "\n";
        }
    } else {
        echo "❌ Warning: Column 'can_export_data' was not added!\n";
    }
    
} catch (Exception $e) {
    echo "\n================================\n";
    echo "❌ Migration failed!\n";
    echo "================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "</pre>";
echo "<p><strong>You can now delete this file (run_export_permission_migration.php) for security.</strong></p>";
