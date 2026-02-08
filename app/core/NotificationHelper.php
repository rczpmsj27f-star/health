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
    public function create($userId, $type, $title, $message, $relatedUserId = null, $relatedMedicationId = null) {
        // Create in-app notification
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_user_id, related_medication_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $type, $title, $message, $relatedUserId, $relatedMedicationId]);
        $notificationId = $this->pdo->lastInsertId();
        
        // Send via other channels based on preferences
        $this->sendViaChannels($userId, $type, $title, $message);
        
        return $notificationId;
    }
    
    /**
     * Send notification via enabled channels
     */
    private function sendViaChannels($userId, $type, $title, $message) {
        $stmt = $this->pdo->prepare("
            SELECT in_app, push, email FROM notification_preferences 
            WHERE user_id = ? AND notification_type = ?
        ");
        $stmt->execute([$userId, $type]);
        $prefs = $stmt->fetch();
        
        if (!$prefs) {
            $prefs = ['in_app' => 1, 'push' => 1, 'email' => 0];
        }
        
        // Push notification (placeholder for Phase 7)
        if ($prefs['push']) {
            error_log("PUSH notification for user $userId: $title");
        }
        
        // Email notification (basic implementation)
        if ($prefs['email']) {
            $this->sendEmailNotification($userId, $title, $message);
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($userId, $title, $message) {
        $stmt = $this->pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['email']) {
            return;
        }
        
        $to = $user['email'];
        $subject = "Health Tracker: " . $title;
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;'>
                <div style='background: #6366f1; color: white; padding: 20px;'>
                    <h2 style='margin: 0;'>💊 Health Tracker</h2>
                </div>
                <div style='padding: 30px;'>
                    <h3 style='color: #374151; margin-top: 0;'>{$title}</h3>
                    <p style='color: #4b5563; line-height: 1.6;'>{$message}</p>
                </div>
                <div style='background: #f3f4f6; padding: 20px; text-align: center;'>
                    <p style='color: #6b7280; font-size: 12px; margin: 0;'>This is an automated notification from Health Tracker.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Health Tracker <noreply@healthtracker.com>\r\n";
        
        mail($to, $subject, $body, $headers);
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
