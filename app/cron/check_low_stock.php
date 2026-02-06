<?php
/**
 * Low Stock Notification Cron Job
 * Checks medication stock levels and sends notifications when low
 * Schedule: Daily at 9:00 AM
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';

// Log start
error_log("Low stock notification check started at " . date('Y-m-d H:i:s'));

try {
    // Get all users with stock notifications enabled
    $usersStmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.first_name,
               up.stock_notification_threshold, up.notify_linked_users
        FROM users u
        JOIN user_preferences up ON u.id = up.user_id
        WHERE up.stock_notification_enabled = 1
        AND u.is_active = 1
    ");
    
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($users) . " users with stock notifications enabled");
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $threshold = $user['stock_notification_threshold'] ?? 10;
        
        // Get medications with low stock for this user
        $medicationsStmt = $pdo->prepare("
            SELECT m.id, m.name, m.current_stock,
                   ms.doses_per_administration,
                   ms.times_per_day,
                   ms.is_prn
            FROM medications m
            LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
            WHERE m.user_id = ?
            AND m.archived = 0
            AND m.current_stock IS NOT NULL
            AND m.current_stock > 0
        ");
        
        $medicationsStmt->execute([$userId]);
        $medications = $medicationsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $lowStockMeds = [];
        
        foreach ($medications as $med) {
            // Skip PRN medications (they don't have regular schedules)
            if ($med['is_prn']) {
                continue;
            }
            
            $currentStock = $med['current_stock'];
            $dosesPerDay = ($med['doses_per_administration'] ?? 1) * ($med['times_per_day'] ?? 1);
            
            if ($dosesPerDay > 0) {
                $daysRemaining = floor($currentStock / $dosesPerDay);
                
                if ($daysRemaining <= $threshold) {
                    // Check if we've already sent a notification recently (within 7 days)
                    $recentNotificationStmt = $pdo->prepare("
                        SELECT id FROM stock_notification_log
                        WHERE medication_id = ?
                        AND user_id = ?
                        AND notification_sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ");
                    $recentNotificationStmt->execute([$med['id'], $userId]);
                    
                    if (!$recentNotificationStmt->fetch()) {
                        $lowStockMeds[] = [
                            'id' => $med['id'],
                            'name' => $med['name'],
                            'current_stock' => $currentStock,
                            'days_remaining' => $daysRemaining
                        ];
                    }
                }
            }
        }
        
        // Send notification if there are low stock medications
        if (!empty($lowStockMeds)) {
            sendLowStockNotification($user, $lowStockMeds, $pdo);
        }
    }
    
    error_log("Low stock notification check completed successfully");
    
} catch (Exception $e) {
    error_log("Error in low stock notification check: " . $e->getMessage());
}

/**
 * Send low stock notification email
 */
function sendLowStockNotification($user, $medications, $pdo) {
    try {
        $mail = mailer();
        $mail->addAddress($user['email']);
        $mail->Subject = "Health Tracker: Low Medication Stock Alert";
        
        $medicationList = '';
        foreach ($medications as $med) {
            $medicationList .= sprintf(
                "<li><strong>%s</strong> - %d units remaining (%d days supply)</li>\n",
                htmlspecialchars($med['name']),
                $med['current_stock'],
                $med['days_remaining']
            );
            
            // Log the notification
            $logStmt = $pdo->prepare("
                INSERT INTO stock_notification_log 
                (medication_id, user_id, stock_level, threshold)
                VALUES (?, ?, ?, ?)
            ");
            $logStmt->execute([
                $med['id'],
                $user['id'],
                $med['current_stock'],
                $user['stock_notification_threshold'] ?? 10
            ]);
        }
        
        $mail->Body = "
            <h2>Low Medication Stock Alert</h2>
            <p>Hello {$user['first_name']},</p>
            <p>The following medications are running low:</p>
            <ul>
                $medicationList
            </ul>
            <p>Please reorder these medications soon to avoid running out.</p>
            <p>You can update your stock levels in the Health Tracker app.</p>
            <p><a href='https://ht.ianconroy.co.uk/medications/stock'>Manage Medication Stock</a></p>
        ";
        
        $mail->send();
        error_log("Low stock notification sent to {$user['email']} for " . count($medications) . " medications");
        
        // If notify_linked_users is enabled, send to linked users too
        if ($user['notify_linked_users']) {
            // TODO: Implement linked users notification
            // This would require a user_links or similar table
        }
        
    } catch (Exception $e) {
        error_log("Failed to send low stock notification to {$user['email']}: " . $e->getMessage());
    }
}
