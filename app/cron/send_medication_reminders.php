<?php

/**
 * Medication Reminder Cron Job
 * 
 * This script should be run every minute via cron to send medication reminders
 * based on user notification preferences and medication schedules.
 * 
 * Cron setup example (run every minute):
 * * * * * /usr/bin/php /path/to/health/app/cron/send_medication_reminders.php >> /path/to/logs/cron.log 2>&1
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Configuration constants
const NOTIFICATION_TOLERANCE_MINUTES = 5; // Tolerance window for matching scheduled times (increased to handle cron delays)

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/NotificationService.php';

// Initialize notification service
$notificationService = new NotificationService();

// Get current date and time
$now = new DateTime();
$currentDate = $now->format('Y-m-d');
$currentTime = $now->format('H:i');
$currentDateTime = $now->format('Y-m-d H:i:s');

echo "[" . date('Y-m-d H:i:s') . "] Starting medication reminder check...\n";

try {
    // Find all pending medication doses that need reminders
    // This queries medication_logs for pending doses scheduled for today
    $stmt = $pdo->prepare("
        SELECT 
            ml.id as log_id,
            ml.medication_id,
            ml.user_id,
            ml.scheduled_date_time,
            m.name as medication_name,
            md.dose_amount,
            md.dose_unit,
            uns.notify_at_time,
            uns.notify_after_10min,
            uns.notify_after_20min,
            uns.notify_after_30min,
            uns.notify_after_60min,
            uns.onesignal_player_id
        FROM medication_logs ml
        INNER JOIN medications m ON ml.medication_id = m.id
        LEFT JOIN medication_doses md ON m.id = md.medication_id
        INNER JOIN user_notification_settings uns ON ml.user_id = uns.user_id
        WHERE ml.status = 'pending'
        AND uns.notifications_enabled = 1
        AND uns.onesignal_player_id IS NOT NULL
        AND uns.onesignal_player_id != ''
        AND DATE(ml.scheduled_date_time) = ?
    ");
    $stmt->execute([$currentDate]);
    $pendingDoses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($pendingDoses) . " pending doses with active notifications\n";
    
    foreach ($pendingDoses as $dose) {
        $scheduledDateTime = new DateTime($dose['scheduled_date_time']);
        $scheduledTime = $scheduledDateTime->format('H:i');
        
        // Calculate minutes difference between scheduled time and current time
        $diffMinutes = ($now->getTimestamp() - $scheduledDateTime->getTimestamp()) / 60;
        
        $shouldNotify = false;
        $notificationType = '';
        
        // Check if we should send a notification based on user preferences
        // Note: Using tolerance to account for cron timing variations
        if ($diffMinutes >= 0 && $diffMinutes <= NOTIFICATION_TOLERANCE_MINUTES && $dose['notify_at_time']) {
            // At scheduled time (within tolerance, not before)
            $shouldNotify = true;
            $notificationType = 'scheduled';
        } elseif ($diffMinutes >= (10 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (10 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_10min']) {
            // 10 minutes after (within tolerance)
            $shouldNotify = true;
            $notificationType = 'reminder-10';
        } elseif ($diffMinutes >= (20 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (20 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_20min']) {
            // 20 minutes after
            $shouldNotify = true;
            $notificationType = 'reminder-20';
        } elseif ($diffMinutes >= (30 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (30 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_30min']) {
            // 30 minutes after
            $shouldNotify = true;
            $notificationType = 'reminder-30';
        } elseif ($diffMinutes >= (60 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (60 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_60min']) {
            // 60 minutes after
            $shouldNotify = true;
            $notificationType = 'reminder-60';
        }
        
        if ($shouldNotify) {
            // Prepare notification message
            $medicationName = $dose['medication_name'];
            $doseInfo = '';
            if ($dose['dose_amount'] && $dose['dose_unit']) {
                $doseInfo = ' - ' . $dose['dose_amount'] . ' ' . $dose['dose_unit'];
            }
            
            if ($notificationType === 'scheduled') {
                $title = 'Medication Reminder';
                $message = "Time to take {$medicationName}{$doseInfo}";
            } else {
                $minutesOverdue = (int)$diffMinutes;
                $title = 'Medication Reminder';
                $message = "Reminder: You haven't taken {$medicationName} ({$minutesOverdue} min overdue)";
            }
            
            // Additional data for the notification
            $data = [
                'medication_id' => $dose['medication_id'],
                'log_id' => $dose['log_id'],
                'type' => $notificationType,
                'url' => '/dashboard.php',
                'tag' => "medication-{$dose['medication_id']}-{$scheduledTime}"
            ];
            
            // Send notification to this user's device
            $result = $notificationService->sendNotification(
                [$dose['onesignal_player_id']],
                $title,
                $message,
                $data
            );
            
            if ($result['success']) {
                echo "[{$currentDateTime}] Sent {$notificationType} notification for {$medicationName} to user {$dose['user_id']}\n";
            } else {
                echo "[{$currentDateTime}] Failed to send notification for {$medicationName} to user {$dose['user_id']}: " . 
                     json_encode($result['error']) . "\n";
            }
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Medication reminder check completed\n\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
