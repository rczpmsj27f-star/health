<?php
/**
 * Database Migration Runner - Theme Mode Update
 * Purpose: Convert dark_mode boolean to theme_mode ENUM
 * Usage: Run this file once via browser or CLI
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Theme Mode Migration Runner</h1>";
echo "<pre>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/migration_update_theme_mode.sql';
    
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
            // Filter out empty statements and comments
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Running migration: migration_update_theme_mode.sql\n";
    echo "Number of statements to execute: " . count($statements) . "\n\n";
    
    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "...\n";
        
        // Add semicolon back
        $statement = trim($statement) . ';';
        
        try {
            $pdo->exec($statement);
            echo "✓ Success\n";
        } catch (PDOException $e) {
            // Check if error is about duplicate column (already migrated)
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), "Can't DROP") !== false) {
                echo "⚠ Already migrated (skipping)\n";
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "\n================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "================================\n\n";
    
    // Verify theme_mode column exists
    echo "Verifying migration...\n";
    $stmt = $pdo->query("DESCRIBE user_preferences");
    $hasThemeMode = false;
    $hasDarkMode = false;
    
    while ($row = $stmt->fetch()) {
        if ($row['Field'] === 'theme_mode') {
            $hasThemeMode = true;
            echo "✓ Column 'theme_mode' exists ({$row['Type']})\n";
        }
        if ($row['Field'] === 'dark_mode') {
            $hasDarkMode = true;
        }
    }
    
    if ($hasThemeMode && !$hasDarkMode) {
        echo "✓ Migration successful: dark_mode removed, theme_mode added\n";
    } elseif ($hasThemeMode && $hasDarkMode) {
        echo "⚠ Both columns exist - migration may be incomplete\n";
    } else {
        echo "❌ Migration may have failed - theme_mode column not found\n";
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
echo "<p><strong>Migration complete. You can delete this file (run_theme_migration.php) for security.</strong></p>";
