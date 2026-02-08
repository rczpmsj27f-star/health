<?php
/**
 * Dropdown Options Migration Runner
 * Run this once to create dropdown system tables and populate initial data
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>Dropdown Options Migration</h1>";
echo "<pre>";

try {
    $migrationFile = __DIR__ . '/database/migrations/migration_create_dropdown_options.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n\n";
    
    // Verify tables were created
    $stmt = $pdo->query("SHOW TABLES LIKE 'dropdown_%'");
    echo "Tables created:\n";
    while ($row = $stmt->fetch()) {
        echo "  ✓ " . $row[0] . "\n";
    }
    
    // Show counts
    $categories = $pdo->query("SELECT COUNT(*) FROM dropdown_categories")->fetchColumn();
    $options = $pdo->query("SELECT COUNT(*) FROM dropdown_options")->fetchColumn();
    
    echo "\nData populated:\n";
    echo "  ✓ $categories categories\n";
    echo "  ✓ $options options\n";
    
    // Show category breakdown
    echo "\nOptions per category:\n";
    $stmt = $pdo->query("
        SELECT c.category_name, COUNT(o.id) as option_count
        FROM dropdown_categories c
        LEFT JOIN dropdown_options o ON c.id = o.category_id
        GROUP BY c.id, c.category_name
        ORDER BY c.category_name
    ");
    while ($row = $stmt->fetch()) {
        echo "  ✓ " . $row['category_name'] . ": " . $row['option_count'] . " options\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>";
echo "<p><strong>Delete this file after running.</strong></p>";
