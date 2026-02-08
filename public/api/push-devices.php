<?php
/**
 * Push Device Registration API
 * Handles native device token registration for iOS push notifications
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

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

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
            
            // Register device token with OneSignal if needed
            $onesignal_player_id = null;
            if (defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID) {
                try {
                    $onesignal_player_id = registerWithOneSignal($device_token, $platform, $device_id);
                } catch (Exception $e) {
                    error_log("OneSignal registration failed: " . $e->getMessage());
                    // Continue anyway - store local token
                }
            }
            
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

/**
 * Register device with OneSignal
 * Creates a player/device in OneSignal for targeted notifications
 */
function registerWithOneSignal($device_token, $platform, $device_id) {
    $app_id = ONESIGNAL_APP_ID;
    $api_key = ONESIGNAL_REST_API_KEY;
    
    if (empty($app_id) || empty($api_key)) {
        throw new Exception("OneSignal credentials not configured");
    }
    
    // Prepare device type
    $device_type = match($platform) {
        'ios' => 0,      // iOS
        'android' => 1,  // Android
        'web' => 5,      // Chrome/Web Push
        default => 5
    };
    
    // Prepare OneSignal API request
    $data = [
        'app_id' => $app_id,
        'device_type' => $device_type,
        'identifier' => $device_token
    ];
    
    // Add device ID as external user ID for easier targeting
    if ($device_id) {
        $data['external_user_id'] = $device_id;
    }
    
    // Make API request to OneSignal
    $ch = curl_init('https://onesignal.com/api/v1/players');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 && $http_code !== 201) {
        error_log("OneSignal API error: HTTP $http_code, Response: $response");
        throw new Exception("OneSignal API request failed");
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['id'])) {
        return $result['id']; // Return OneSignal player ID
    }
    
    throw new Exception("OneSignal registration failed: no player ID returned");
}
