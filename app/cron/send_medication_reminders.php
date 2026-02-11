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
require_once __DIR__ . '/../core/NotificationHelper.php';

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
            ml.notification_sent_at_time,
            ml.notification_sent_10min,
            ml.notification_sent_20min,
            ml.notification_sent_30min,
            ml.notification_sent_60min,
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
        // IMPORTANT: Also check if notification was already sent to prevent duplicates
        if ($diffMinutes >= 0 && $diffMinutes <= NOTIFICATION_TOLERANCE_MINUTES && $dose['notify_at_time'] && !$dose['notification_sent_at_time']) {
            // At scheduled time (within tolerance, not before) - only if not already sent
            $shouldNotify = true;
            $notificationType = 'scheduled';
        } elseif ($diffMinutes >= (10 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (10 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_10min'] && !$dose['notification_sent_10min']) {
            // 10 minutes after (within tolerance) - only if not already sent
            $shouldNotify = true;
            $notificationType = 'reminder-10';
        } elseif ($diffMinutes >= (20 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (20 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_20min'] && !$dose['notification_sent_20min']) {
            // 20 minutes after - only if not already sent
            $shouldNotify = true;
            $notificationType = 'reminder-20';
        } elseif ($diffMinutes >= (30 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (30 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_30min'] && !$dose['notification_sent_30min']) {
            // 30 minutes after - only if not already sent
            $shouldNotify = true;
            $notificationType = 'reminder-30';
        } elseif ($diffMinutes >= (60 - NOTIFICATION_TOLERANCE_MINUTES) && $diffMinutes <= (60 + NOTIFICATION_TOLERANCE_MINUTES) && $dose['notify_after_60min'] && !$dose['notification_sent_60min']) {
            // 60 minutes after - only if not already sent
            $shouldNotify = true;
            $notificationType = 'reminder-60';
        }
        
        if ($shouldNotify) {
            // Mark this notification type as sent FIRST to prevent race condition
            // This prevents duplicate notifications if the cron runs again before this completes
            // Using switch to safely build query without SQL injection risk
            $updateQuery = null;
            switch ($notificationType) {
                case 'scheduled':
                    $updateQuery = "UPDATE medication_logs SET notification_sent_at_time = NOW() WHERE id = ?";
                    break;
                case 'reminder-10':
                    $updateQuery = "UPDATE medication_logs SET notification_sent_10min = NOW() WHERE id = ?";
                    break;
                case 'reminder-20':
                    $updateQuery = "UPDATE medication_logs SET notification_sent_20min = NOW() WHERE id = ?";
                    break;
                case 'reminder-30':
                    $updateQuery = "UPDATE medication_logs SET notification_sent_30min = NOW() WHERE id = ?";
                    break;
                case 'reminder-60':
                    $updateQuery = "UPDATE medication_logs SET notification_sent_60min = NOW() WHERE id = ?";
                    break;
            }
            
            if ($updateQuery) {
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$dose['log_id']]);
            }
            
            // Prepare notification message
            $medicationName = $dose['medication_name'];
            $doseInfo = '';
            if ($dose['dose_amount'] && $dose['dose_unit']) {
                // Format dose amount to 2 decimal places, removing trailing zeros
                $formattedAmount = rtrim(rtrim(number_format((float)$dose['dose_amount'], 2, '.', ''), '0'), '.');
                $doseInfo = ' - ' . $formattedAmount . ' ' . $dose['dose_unit'];
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
                'url' => 'https://ht.ianconroy.co.uk/dashboard.php',
                'tag' => "medication-{$dose['medication_id']}-{$scheduledTime}"
            ];
            
            // Use NotificationHelper to create in-app notification and send via enabled channels
            // This will create a record in the notifications table AND send push/email based on preferences
            $notificationHelper = new NotificationHelper($pdo);
            try {
                // Create in-app notification and send via channels (push/email)
                $notificationId = $notificationHelper->create(
                    $dose['user_id'],
                    'medication_reminder',
                    $title,
                    $message,
                    null, // no related user
                    $dose['medication_id'],
                    $data
                );
                
                echo "[{$currentDateTime}] Sent {$notificationType} notification for {$medicationName} to user {$dose['user_id']}\n";
            } catch (Exception $e) {
                echo "[{$currentDateTime}] Failed to send notification for {$medicationName} to user {$dose['user_id']}: " . 
                     $e->getMessage() . "\n";
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
