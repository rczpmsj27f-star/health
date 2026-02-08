<?php
/**
 * Device Tokens Migration Runner
 * Purpose: Add device token fields to user_notification_settings table
 * Usage: Run this file once via browser or CLI
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Device Tokens Migration Runner</h1>";
echo "<pre>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/migration_add_device_tokens.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\s]*$/m', $sql)
        ),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Running migration: migration_add_device_tokens.sql\n";
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
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
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
    
    // Verify columns were added
    echo "Verifying table structure:\n";
    $stmt = $pdo->query("DESCRIBE user_notification_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedColumns = ['device_token', 'platform', 'device_id', 'last_token_update'];
    foreach ($expectedColumns as $col) {
        if (in_array($col, $columns)) {
            echo "  ✓ Column '$col' exists\n";
        } else {
            echo "  ✗ Column '$col' NOT FOUND\n";
        }
    }
    
    // Show updated table structure
    echo "\nFull table structure:\n";
    $stmt = $pdo->query("DESCRIBE user_notification_settings");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})";
        if ($row['Null'] === 'NO') echo " NOT NULL";
        if ($row['Default'] !== null) echo " DEFAULT {$row['Default']}";
        echo "\n";
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
echo "<p><strong>Migration completed. You can safely delete this file (run_device_tokens_migration.php) for security.</strong></p>";
