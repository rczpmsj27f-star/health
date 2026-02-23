<?php
/**
 * Push Device Registration API
 * Handles native device token registration for iOS push notifications
 */

session_start();
require_once "../../app/config/database.php";
require_once __DIR__ . '/../../config.php';

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

            // Diagnostic logging so server logs always show what was received
            error_log("push-devices.php register: user_id={$user_id} platform=" . ($platform ?? 'null') . " onesignal_player_id=" . ($onesignal_player_id ?? 'NULL'));
            
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
                        onesignal_player_id = ?,
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
            
            // If no Player ID was provided in the request, look it up server-side
            // via the OneSignal REST API so the DB record stays up to date even
            // when the JS bridge cannot read the native subscription ID.
            if (empty($onesignal_player_id)) {
                $looked_up_id = lookupOneSignalSubscription($device_token);
                if ($looked_up_id) {
                    $onesignal_player_id = $looked_up_id;
                    $stmt = $pdo->prepare("UPDATE user_notification_settings SET onesignal_player_id = ?, updated_at = NOW() WHERE user_id = ?");
                    $stmt->execute([$onesignal_player_id, $user_id]);
                    error_log("push-devices.php register: Updated onesignal_player_id via server-side lookup to {$onesignal_player_id} for user {$user_id}");
                }
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
            
        case 'update_player_id':
            // Update OneSignal Player ID for an existing device registration.
            // Called by the frontend when the Player ID becomes available after
            // the initial device-token registration (timing/initialization fix).
            $device_token = $input['device_token'] ?? null;
            $onesignal_player_id = $input['onesignal_player_id'] ?? null;

            error_log("push-devices.php update_player_id: user_id={$user_id} onesignal_player_id=" . ($onesignal_player_id ?? 'NULL'));

            if (empty($onesignal_player_id) || empty($device_token)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing onesignal_player_id or device_token']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE user_notification_settings
                SET onesignal_player_id = ?,
                    updated_at = NOW()
                WHERE user_id = ?
                  AND device_token = ?
            ");
            $stmt->execute([
                $onesignal_player_id,
                $user_id,
                $device_token
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Player ID updated successfully',
                'onesignal_player_id' => $onesignal_player_id
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
 * Look up the OneSignal subscription ID for a given APNs device token
 * using the OneSignal REST API server-side.
 *
 * @param string $device_token  The APNs device token
 * @return string|null          The OneSignal subscription/player ID, or null if not found
 */
function lookupOneSignalSubscription($device_token) {
    if (!function_exists('onesignal_is_configured') || !onesignal_is_configured()) {
        error_log("lookupOneSignalSubscription: OneSignal not configured, skipping lookup");
        return null;
    }

    $app_id  = ONESIGNAL_APP_ID;
    $api_key = ONESIGNAL_REST_API_KEY;

    // Use OneSignal REST API v5 to search for a subscription by device token
    $url = "https://api.onesignal.com/apps/" . urlencode($app_id)
         . "/subscriptions?token=" . urlencode($device_token);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("lookupOneSignalSubscription: HTTP {$http_code} for token " . substr($device_token, 0, 8) . "...");

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        // v5 subscriptions endpoint returns { "subscriptions": [ { "id": "...", ... } ] }
        if (!empty($data['subscriptions'][0]['id'])) {
            $player_id = $data['subscriptions'][0]['id'];
            error_log("lookupOneSignalSubscription: Found player ID: {$player_id}");
            return $player_id;
        }
    }

    error_log("lookupOneSignalSubscription: No subscription found. HTTP={$http_code}, response=" . substr($response ?: '', 0, 200));
    return null;
}


