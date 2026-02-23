<?php
/**
 * Push Device Registration API
 * Handles native device token registration for iOS push notifications
 */

session_start();
require_once "../../app/config/database.php";

header('Content-Type: application/json');

// Get JSON input early (needed for fallback authentication)
$input = json_decode(file_get_contents('php://input'), true);

// Check authentication
// Primary: PHP session (web browser)
// Fallback: user_id in POST body (native Capacitor app where session cookies may not transmit)
if (!empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
} elseif (!empty($input['user_id'])) {
    $candidate_id = (int)$input['user_id'];
    // Validate the user exists in the database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$candidate_id]);
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $user_id = $candidate_id;
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $input['action'];

try {
    switch ($action) {
        case 'register':
            // Register or update device token
            $device_token = $input['device_token'] ?? null;
            $platform = $input['platform'] ?? null;
            $device_id = $input['device_id'] ?? null;
            $device_model = $input['device_model'] ?? null;
            $os_version = $input['os_version'] ?? null;
            
            // Validate required fields
            if (empty($device_token) || empty($platform)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            // Validate platform
            if (!in_array($platform, ['ios', 'android', 'web'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid platform']);
                exit;
            }
            
            // Get OneSignal Player ID from the request (provided by native SDK)
            $onesignal_player_id = $input['onesignal_player_id'] ?? null;
            
            // Check if user already has notification settings
            $stmt = $pdo->prepare("
                SELECT id FROM user_notification_settings 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if ($settings) {
                // Update existing settings
                $stmt = $pdo->prepare("
                    UPDATE user_notification_settings 
                    SET device_token = ?,
                        platform = ?,
                        device_id = ?,
                        onesignal_player_id = COALESCE(?, onesignal_player_id),
                        last_token_update = NOW(),
                        updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $device_token,
                    $platform,
                    $device_id,
                    $onesignal_player_id,
                    $user_id
                ]);
            } else {
                // Create new settings
                $stmt = $pdo->prepare("
                    INSERT INTO user_notification_settings 
                    (user_id, device_token, platform, device_id, onesignal_player_id, 
                     notifications_enabled, last_token_update)
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $device_token,
                    $platform,
                    $device_id,
                    $onesignal_player_id
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Device registered successfully',
                'platform' => $platform,
                'onesignal_player_id' => $onesignal_player_id
            ]);
            break;
            
        case 'unregister':
            // Remove device token
            $stmt = $pdo->prepare("
                UPDATE user_notification_settings 
                SET device_token = NULL,
                    platform = NULL,
                    device_id = NULL,
                    last_token_update = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Device unregistered successfully'
            ]);
            break;
            
        case 'status':
            // Get device registration status
            $stmt = $pdo->prepare("
                SELECT device_token, platform, device_id, 
                       onesignal_player_id, last_token_update,
                       notifications_enabled
                FROM user_notification_settings 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'registered' => !empty($status['device_token']),
                'platform' => $status['platform'] ?? null,
                'notifications_enabled' => (bool)($status['notifications_enabled'] ?? false),
                'last_update' => $status['last_token_update'] ?? null
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in push-devices.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}


