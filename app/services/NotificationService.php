<?php

/**
 * NotificationService
 * Handles OneSignal push notification sending with targeted Player IDs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../config.php';

class NotificationService {
    
    private $pdo;
    private $appId;
    private $restApiKey;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->appId = ONESIGNAL_APP_ID;
        $this->restApiKey = ONESIGNAL_REST_API_KEY;
    }
    
    /**
     * Get all active Player IDs for users with notifications enabled
     * 
     * @return array Array of Player IDs
     */
    public function getActivePlayerIds() {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT onesignal_player_id 
            FROM user_notification_settings 
            WHERE notifications_enabled = 1 
            AND onesignal_player_id IS NOT NULL 
            AND onesignal_player_id != ''
        ");
        $stmt->execute();
        
        $playerIds = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $playerIds[] = $row['onesignal_player_id'];
        }
        
        return $playerIds;
    }
    
    /**
     * Get Player ID for a specific user
     * 
     * @param int $userId The user ID
     * @return string|null The Player ID or null
     */
    public function getUserPlayerId($userId) {
        $stmt = $this->pdo->prepare("
            SELECT onesignal_player_id 
            FROM user_notification_settings 
            WHERE user_id = ? 
            AND notifications_enabled = 1 
            AND onesignal_player_id IS NOT NULL 
            AND onesignal_player_id != ''
        ");
        $stmt->execute([$userId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['onesignal_player_id'] : null;
    }
    
    /**
     * Send push notification to specific Player IDs
     * 
     * @param array $playerIds Array of OneSignal Player IDs
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data to send with notification
     * @return array Response from OneSignal API
     */
    public function sendNotification($playerIds, $title, $message, $data = []) {
        if (empty($playerIds)) {
            return ['success' => false, 'error' => 'No Player IDs provided'];
        }
        
        // Prepare notification payload
        $payload = [
            'app_id' => $this->appId,
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'data' => $data,
            'web_url' => $data['url'] ?? '/',
            'chrome_web_icon' => '/assets/images/icon-192x192.png',
            'chrome_web_badge' => '/assets/images/badge-72x72.png'
        ];
        
        // Add tag if provided
        if (isset($data['tag'])) {
            $payload['web_push_topic'] = $data['tag'];
        }
        
        // Send to OneSignal API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . $this->restApiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Check for success: HTTP 200, notification ID present, and no errors
        if ($httpCode === 200 && isset($result['id']) && !isset($result['errors'])) {
            return [
                'success' => true,
                'notification_id' => $result['id'],
                'recipients' => $result['recipients'] ?? 0
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['errors'] ?? ($result['error'] ?? 'Unknown error'),
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Send notification to a single user
     * 
     * @param int $userId The user ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return array Response
     */
    public function sendToUser($userId, $title, $message, $data = []) {
        $playerId = $this->getUserPlayerId($userId);
        
        if (!$playerId) {
            return ['success' => false, 'error' => 'User does not have notifications enabled or Player ID not found'];
        }
        
        return $this->sendNotification([$playerId], $title, $message, $data);
    }
    
    /**
     * Send notification to all users with notifications enabled
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return array Response
     */
    public function sendToAll($title, $message, $data = []) {
        $playerIds = $this->getActivePlayerIds();
        
        if (empty($playerIds)) {
            return ['success' => false, 'error' => 'No active subscribers found'];
        }
        
        return $this->sendNotification($playerIds, $title, $message, $data);
    }
}

