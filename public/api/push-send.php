<?php
/**
 * Push Notification Send API
 * Sends push notifications via OneSignal to native iOS devices
 */

session_start();
require_once "../../app/config/database.php";

header('Content-Type: application/json');

// Check authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$sender_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Validate required fields
$recipient_user_id = $input['user_id'] ?? null;
$title = $input['title'] ?? null;
$message = $input['message'] ?? null;
$data = $input['data'] ?? [];

if (empty($recipient_user_id) || empty($title) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Get recipient's device token and OneSignal player ID
    $stmt = $pdo->prepare("
        SELECT device_token, platform, onesignal_player_id, notifications_enabled
        FROM user_notification_settings
        WHERE user_id = ? AND notifications_enabled = 1
    ");
    $stmt->execute([$recipient_user_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recipient) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found or notifications disabled'
        ]);
        exit;
    }
    
    // Check if user has push notifications configured
    if (empty($recipient['onesignal_player_id']) && empty($recipient['device_token'])) {
        echo json_encode([
            'success' => false,
            'error' => 'User has not registered for push notifications'
        ]);
        exit;
    }
    
    // Send via OneSignal
    $result = sendViaOneSignal(
        $recipient['onesignal_player_id'],
        $title,
        $message,
        $data
    );
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully',
            'notification_id' => $result['notification_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in push-send.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Error in push-send.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send notification']);
}

/**
 * Send push notification via OneSignal
 */
function sendViaOneSignal($player_id, $title, $message, $data = []) {
    $app_id = ONESIGNAL_APP_ID;
    $api_key = ONESIGNAL_REST_API_KEY;
    
    if (empty($app_id) || empty($api_key)) {
        return ['success' => false, 'error' => 'OneSignal not configured'];
    }
    
    if (empty($player_id)) {
        return ['success' => false, 'error' => 'No player ID'];
    }
    
    // Prepare notification payload
    $payload = [
        'app_id' => $app_id,
        'include_player_ids' => [$player_id],
        'headings' => ['en' => $title],
        'contents' => ['en' => $message],
        'ios_badgeType' => 'Increase',
        'ios_badgeCount' => 1
    ];
    
    // Add custom data if provided
    if (!empty($data)) {
        $payload['data'] = $data;
    }
    
    // Add action buttons for medication reminders
    if (isset($data['type']) && $data['type'] === 'medication_reminder') {
        $payload['buttons'] = [
            [
                'id' => 'mark_taken',
                'text' => 'Mark as Taken',
                'icon' => 'ic_check'
            ],
            [
                'id' => 'snooze',
                'text' => 'Snooze',
                'icon' => 'ic_alarm'
            ]
        ];
    }
    
    // Make API request to OneSignal
    $ch = curl_init('https://onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 && $http_code !== 201) {
        error_log("OneSignal notification error: HTTP $http_code, Response: $response");
        return ['success' => false, 'error' => 'OneSignal API request failed'];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['id'])) {
        return [
            'success' => true,
            'notification_id' => $result['id']
        ];
    }
    
    return ['success' => false, 'error' => 'Unknown error'];
}
