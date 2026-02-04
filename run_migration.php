<?php
/**
 * Database Migration Runner
 * Purpose: Apply the notification settings migration
 * Usage: Run this file once via browser or CLI to create the user_notification_settings table
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Database Migration Runner</h1>";
echo "<pre>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/migration_create_notification_settings.sql';
    
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
    
    echo "Running migration: migration_create_notification_settings.sql\n";
    echo "Number of statements to execute: " . count($statements) . "\n\n";
    
    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "...\n";
        
        // Add semicolon back
        $statement = trim($statement) . ';';
        
        try {
            $pdo->exec($statement);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            // Check if error is "table already exists" which is okay
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "⚠ Already exists (skipping)\n";
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "\n================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "================================\n\n";
    
    // Verify table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_notification_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'user_notification_settings' exists\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $stmt = $pdo->query("DESCRIBE user_notification_settings");
        while ($row = $stmt->fetch()) {
            echo "  - {$row['Field']} ({$row['Type']})";
            if ($row['Null'] === 'NO') echo " NOT NULL";
            if ($row['Default'] !== null) echo " DEFAULT {$row['Default']}";
            echo "\n";
        }
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
echo "<p><strong>You can now delete this file (run_migration.php) for security.</strong></p>";
