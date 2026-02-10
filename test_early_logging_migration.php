#!/usr/bin/env php
<?php
/**
 * Test Script: Verify Early Logging Migration Status
 * 
 * This script checks if the early_logging_reason column exists in the database.
 * Run this BEFORE and AFTER applying the migration to verify it worked.
 * 
 * Usage:
 *   php test_early_logging_migration.php
 */

require_once __DIR__ . '/app/config/database.php';

echo "\n";
echo "========================================\n";
echo "Early Logging Migration Status Check\n";
echo "========================================\n\n";

try {
    // Check if the column exists
    $stmt = $pdo->query("DESCRIBE medication_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $earlyLoggingExists = false;
    $lateLoggingExists = false;
    
    echo "Medication Logs Table Structure:\n";
    echo "--------------------------------\n";
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'early_logging_reason') {
            $earlyLoggingExists = true;
            echo "✅ early_logging_reason: {$column['Type']} (Null: {$column['Null']})\n";
        }
        if ($column['Field'] === 'late_logging_reason') {
            $lateLoggingExists = true;
            echo "✅ late_logging_reason: {$column['Type']} (Null: {$column['Null']})\n";
        }
    }
    
    echo "\n";
    echo "Migration Status:\n";
    echo "----------------\n";
    
    if ($lateLoggingExists) {
        echo "✅ Late logging migration: APPLIED\n";
    } else {
        echo "❌ Late logging migration: NOT APPLIED (run run_late_logging_migration.php)\n";
    }
    
    if ($earlyLoggingExists) {
        echo "✅ Early logging migration: APPLIED\n";
    } else {
        echo "❌ Early logging migration: NOT APPLIED (run run_early_logging_migration.php)\n";
    }
    
    echo "\n";
    
    // Check for indexes
    echo "Indexes Check:\n";
    echo "-------------\n";
    $stmt = $pdo->query("SHOW INDEX FROM medication_logs WHERE Column_name IN ('early_logging_reason', 'late_logging_reason')");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($indexes)) {
        echo "ℹ️  No indexes found for logging reason columns (may impact performance)\n";
    } else {
        foreach ($indexes as $index) {
            echo "✅ Index '{$index['Key_name']}' on column '{$index['Column_name']}'\n";
        }
    }
    
    echo "\n";
    
    // Summary
    echo "Summary:\n";
    echo "--------\n";
    
    if ($earlyLoggingExists && $lateLoggingExists) {
        echo "🎉 SUCCESS! Both logging migrations are applied.\n";
        echo "   The application should work without database errors.\n";
    } elseif ($earlyLoggingExists) {
        echo "⚠️  WARNING: Early logging is applied but late logging is missing.\n";
        echo "   Run: php run_late_logging_migration.php\n";
    } elseif ($lateLoggingExists) {
        echo "❌ ERROR: Late logging is applied but early logging is MISSING.\n";
        echo "   This is the cause of the database error!\n";
        echo "   Fix: Run php run_early_logging_migration.php\n";
    } else {
        echo "❌ ERROR: Both logging migrations are missing.\n";
        echo "   Run: php run_late_logging_migration.php\n";
        echo "   Run: php run_early_logging_migration.php\n";
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\nPossible causes:\n";
    echo "- Database not accessible\n";
    echo "- Incorrect credentials in .env file\n";
    echo "- medication_logs table doesn't exist\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "========================================\n\n";
exit(0);
