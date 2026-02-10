<?php
/**
 * NotificationHelper
 * Manages all notification types: in-app, push, and email
 */

class NotificationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a notification and send via enabled channels
     */
    public function create($userId, $type, $title, $message, $relatedUserId = null, $relatedMedicationId = null, $data = []) {
        // Create in-app notification
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_user_id, related_medication_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $type, $title, $message, $relatedUserId, $relatedMedicationId]);
        $notificationId = $this->pdo->lastInsertId();
        
        // Add notification ID and medication ID to data if provided
        if (!isset($data['notification_id'])) {
            $data['notification_id'] = $notificationId;
        }
        if ($relatedMedicationId && !isset($data['medication_id'])) {
            $data['medication_id'] = $relatedMedicationId;
        }
        
        // Send via other channels based on preferences
        $this->sendViaChannels($userId, $type, $title, $message, $data);
        
        return $notificationId;
    }
    
    /**
     * Send notification via enabled channels
     */
    private function sendViaChannels($userId, $type, $title, $message, $data = []) {
        $stmt = $this->pdo->prepare("
            SELECT in_app, push, email FROM notification_preferences 
            WHERE user_id = ? AND notification_type = ?
        ");
        $stmt->execute([$userId, $type]);
        $prefs = $stmt->fetch();
        
        if (!$prefs) {
            $prefs = ['in_app' => 1, 'push' => 1, 'email' => 0];
        }
        
        // Push notification via OneSignal
        if ($prefs['push']) {
            $this->sendPushNotification($userId, $type, $title, $message, $data);
        }
        
        // Email notification (basic implementation)
        if ($prefs['email']) {
            $this->sendEmailNotification($userId, $title, $message);
        }
    }
    
    /**
     * Send push notification via OneSignal
     */
    private function sendPushNotification($userId, $type, $title, $message, $data = []) {
        try {
            // Get user's OneSignal player ID or device token
            $stmt = $this->pdo->prepare("
                SELECT onesignal_player_id, device_token, platform, notifications_enabled
                FROM user_notification_settings
                WHERE user_id = ? AND notifications_enabled = 1
            ");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch();
            
            if (!$settings || empty($settings['onesignal_player_id'])) {
                // User hasn't registered for push notifications
                return;
            }
            
            // Prepare notification data
            $notificationData = array_merge($data, [
                'type' => $type
            ]);
            
            // Send via OneSignal API
            $this->sendToOneSignal(
                $settings['onesignal_player_id'],
                $title,
                $message,
                $notificationData
            );
            
        } catch (Exception $e) {
            error_log("Error sending push notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send notification to OneSignal
     */
    private function sendToOneSignal($playerId, $title, $message, $data = []) {
        if (!defined('ONESIGNAL_APP_ID') || !defined('ONESIGNAL_REST_API_KEY')) {
            error_log("OneSignal credentials not configured");
            return;
        }
        
        $payload = [
            'app_id' => ONESIGNAL_APP_ID,
            'include_player_ids' => [$playerId],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'ios_badgeType' => 'Increase',
            'ios_badgeCount' => 1
        ];
        
        if (!empty($data)) {
            $payload['data'] = $data;
        }
        
        // Add action buttons for medication reminders
        if (isset($data['type']) && $data['type'] === 'medication_reminder') {
            $payload['buttons'] = [
                ['id' => 'mark_taken', 'text' => 'Mark as Taken'],
                ['id' => 'snooze', 'text' => 'Snooze']
            ];
        }
        
        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . ONESIGNAL_REST_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("OneSignal API error: HTTP $httpCode, Response: $response");
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($userId, $title, $message) {
        try {
            $stmt = $this->pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !$user['email']) {
                return;
            }
            
            // Use PHPMailer for reliable email delivery
            require_once __DIR__ . '/../config/mailer.php';
            $mail = mailer();
            
            $mail->addAddress($user['email'], $user['first_name']);
            $mail->Subject = "Health Tracker: " . $title;
            
            // Escape HTML to prevent injection
            $titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $messageEscaped = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;'>
                    <div style='background: #6366f1; color: white; padding: 20px;'>
                        <h2 style='margin: 0;'>💊 Health Tracker</h2>
                    </div>
                    <div style='padding: 30px;'>
                        <h3 style='color: #374151; margin-top: 0;'>{$titleEscaped}</h3>
                        <p style='color: #4b5563; line-height: 1.6;'>{$messageEscaped}</p>
                    </div>
                    <div style='background: #f3f4f6; padding: 20px; text-align: center;'>
                        <p style='color: #6b7280; font-size: 12px; margin: 0;'>This is an automated notification from Health Tracker.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $body;
            $mail->send();
            
        } catch (Exception $e) {
            error_log("Error sending email notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("NotificationHelper::getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent notifications
     */
    public function getRecent($userId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT n.*, u.first_name as related_user_name, m.name as medication_name
                FROM notifications n
                LEFT JOIN users u ON n.related_user_id = u.id
                LEFT JOIN medications m ON n.related_medication_id = m.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("NotificationHelper::getRecent error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1 WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Get notification preferences
     */
    public function getPreferences($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notification_preferences WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        $prefs = [];
        while ($row = $stmt->fetch()) {
            $prefs[$row['notification_type']] = [
                'in_app' => (bool)$row['in_app'],
                'push' => (bool)$row['push'],
                'email' => (bool)$row['email']
            ];
        }
        
        return $prefs;
    }
    
    /**
     * Save notification preferences
     */
    public function savePreferences($userId, $preferences) {
        foreach ($preferences as $type => $channels) {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, in_app, push, email)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    in_app = VALUES(in_app),
                    push = VALUES(push),
                    email = VALUES(email)
            ");
            
            $stmt->execute([
                $userId,
                $type,
                $channels['in_app'] ?? 1,
                $channels['push'] ?? 1,
                $channels['email'] ?? 0
            ]);
        }
        
        return true;
    }
}
