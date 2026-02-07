<?php
/**
 * Database Migration Runner - Late Logging
 * Purpose: Add late_logging_reason column to medication_logs table
 * Usage: Run this file once via browser or CLI
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Late Logging Migration Runner</h1>";
echo "<pre>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/migration_add_late_logging.sql';
    
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
    
    echo "Running migration: migration_add_late_logging.sql\n";
    echo "Number of statements to execute: " . count($statements) . "\n\n";
    
    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "...\n";
        
        // Add semicolon back
        $statement = trim($statement) . ';';
        
        try {
            $pdo->exec($statement);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            // Check if error is "already exists" which is okay
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false ||
                strpos($e->getMessage(), 'duplicate') !== false) {
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
    
    // Verify column was added
    echo "Verifying medication_logs table structure:\n";
    $stmt = $pdo->query("DESCRIBE medication_logs");
    $columnExists = false;
    while ($row = $stmt->fetch()) {
        if ($row['Field'] === 'late_logging_reason') {
            $columnExists = true;
            echo "  ✓ Column 'late_logging_reason' exists ({$row['Type']})";
            if ($row['Null'] === 'YES') echo " NULL";
            echo "\n";
        }
    }
    
    if (!$columnExists) {
        echo "  ❌ Column 'late_logging_reason' was not added!\n";
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
echo "<p><strong>Migration complete! You can delete this file (run_late_logging_migration.php) for security.</strong></p>";
