#!/usr/bin/env php
<?php
/**
 * Enhancement Features Migration Runner
 * Applies all enhancement feature migrations in the correct order
 * 
 * Usage: php run_enhancement_migrations.php
 */

require_once __DIR__ . '/app/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Enhancement Features Migration Runner                     ║\n";
echo "║  Applies migrations for all enhancement features           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Define migrations in order
$migrations = [
    'migration_create_user_preferences.sql',
    'migration_add_medication_appearance.sql',
    'migration_create_stock_notification_log.sql'
];

$migrationsPath = __DIR__ . '/database/migrations/';
$successCount = 0;
$errorCount = 0;

foreach ($migrations as $index => $migration) {
    $filePath = $migrationsPath . $migration;
    $number = $index + 1;
    
    echo "[$number/" . count($migrations) . "] Applying: $migration\n";
    
    if (!file_exists($filePath)) {
        echo "    ❌ ERROR: Migration file not found!\n";
        $errorCount++;
        continue;
    }
    
    try {
        // Read migration file
        $sql = file_get_contents($filePath);
        
        if (empty($sql)) {
            echo "    ❌ ERROR: Migration file is empty!\n";
            $errorCount++;
            continue;
        }
        
        // Execute migration
        $pdo->exec($sql);
        
        echo "    ✅ SUCCESS\n";
        $successCount++;
        
    } catch (PDOException $e) {
        echo "    ❌ ERROR: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n";
}

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Migration Summary                                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total migrations: " . count($migrations) . "\n";
echo "✅ Successful: $successCount\n";
echo "❌ Failed: $errorCount\n";
echo "\n";

if ($errorCount > 0) {
    echo "⚠️  Some migrations failed. Please review the errors above.\n";
    exit(1);
} else {
    echo "🎉 All migrations applied successfully!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test the new features in your browser\n";
    echo "2. Configure cron job for stock notifications:\n";
    echo "   0 9 * * * /usr/bin/php " . __DIR__ . "/app/cron/check_low_stock.php\n";
    echo "\n";
    exit(0);
}
