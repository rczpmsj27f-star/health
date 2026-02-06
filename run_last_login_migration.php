<?php
/**
 * Migration Runner for last_login field
 * Run this once to add the last_login column to the users table
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Running last_login Migration</h1>";
echo "<pre>";

try {
    $migrationFile = __DIR__ . '/database/migrations/migration_add_last_login.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    echo "Running migration: migration_add_last_login.sql\n\n";
    echo "SQL: $sql\n\n";
    
    try {
        $pdo->exec($sql);
        echo "✓ Migration executed successfully!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ Column 'last_login' already exists (migration already applied)\n";
        } else {
            throw $e;
        }
    }
    
    // Verify column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($stmt->rowCount() > 0) {
        echo "\n✓ Column 'last_login' exists in users table\n";
        $col = $stmt->fetch();
        echo "  Type: {$col['Type']}\n";
        echo "  Null: {$col['Null']}\n";
        echo "  Default: {$col['Default']}\n";
    } else {
        echo "\n❌ Column 'last_login' not found!\n";
    }
    
    echo "\n================================\n";
    echo "✅ Migration completed!\n";
    echo "================================\n";
    
} catch (Exception $e) {
    echo "\n================================\n";
    echo "❌ Migration failed!\n";
    echo "================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>";
echo "<p><a href='/modules/admin/users.php'>Go to User Management</a></p>";
echo "<p><strong>Note: You can delete this file after running it.</strong></p>";
