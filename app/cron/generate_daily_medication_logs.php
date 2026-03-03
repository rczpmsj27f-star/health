<?php
/**
 * Generate Daily Medication Logs
 * 
 * Run once per day (at midnight) to create tomorrow's medication_logs entries
 * from active medication schedules.
 * 
 * Cron setup:
 * 0 0 * * * /usr/bin/php /path/to/health/app/cron/generate_daily_medication_logs.php >> /path/to/logs/cron.log 2>&1
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../config/database.php';

// Generate logs for tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "[" . date('Y-m-d H:i:s') . "] Generating medication logs for $tomorrow...\n";

try {
    // Find all active medications with daily schedules valid for tomorrow
    $stmt = $pdo->prepare("
        SELECT 
            m.id as medication_id,
            m.user_id,
            mdt.dose_number,
            mdt.dose_time
        FROM medications m
        INNER JOIN medication_schedules ms ON m.id = ms.medication_id
        INNER JOIN medication_dose_times mdt ON m.id = mdt.medication_id
        WHERE m.archived_at IS NULL
        AND ms.frequency_type = 'per_day'
        AND ms.is_prn = 0
        AND m.start_date <= ?
        AND (m.end_date IS NULL OR m.end_date >= ?)
        ORDER BY m.id, mdt.dose_number
    ");
    $stmt->execute([$tomorrow, $tomorrow]);
    
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $created = 0;
    $skipped = 0;
    
    echo "Found " . count($schedules) . " scheduled doses to process\n";
    
    foreach ($schedules as $schedule) {
        $scheduledDateTime = $tomorrow . ' ' . $schedule['dose_time'];
        
        // Check if log already exists (prevent duplicates)
        $checkStmt = $pdo->prepare("
            SELECT id FROM medication_logs 
            WHERE medication_id = ? 
            AND user_id = ? 
            AND scheduled_date_time = ?
        ");
        $checkStmt->execute([
            $schedule['medication_id'],
            $schedule['user_id'],
            $scheduledDateTime
        ]);
        
        if ($checkStmt->fetch()) {
            $skipped++;
            continue; // Already exists
        }
        
        // Create the medication log entry
        $insertStmt = $pdo->prepare("
            INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $insertStmt->execute([
            $schedule['medication_id'],
            $schedule['user_id'],
            $scheduledDateTime
        ]);
        
        $created++;
        
        echo "  ✓ Created log for medication_id={$schedule['medication_id']} "
             . "user_id={$schedule['user_id']} at {$scheduledDateTime}\n";
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Summary:\n";
    echo "  Created: $created\n";
    echo "  Skipped (already exists): $skipped\n";
    echo "  Total processed: " . count($schedules) . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Completed successfully\n\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
