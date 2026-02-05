<?php
/**
 * Save Notification Settings Handler
 * 
 * Handles AJAX requests to save user notification preferences and OneSignal Player ID.
 * 
 * Features:
 * - Robust AJAX/JSON request detection
 * - Comprehensive debug logging (when enabled)
 * - Proper JSON responses for AJAX, redirects for normal requests
 * - Detailed error messages for troubleshooting
 * 
 * Security:
 * - Requires authenticated session
 * - Returns 401 for unauthorized AJAX requests
 * - Redirects to login for unauthorized page requests
 */

session_start();
require_once "../../../app/config/database.php";
require_once "../../../config.php";
require_once "../../../app/helpers/ajax_helpers.php";
require_once "../../../app/helpers/debug_helpers.php";

// Debug logging: Create snapshot of request
debug_snapshot('save_notifications_handler');

// Detect if this is an AJAX/JSON request
$isAjax = is_ajax_request();

// Set JSON content type header for AJAX requests
if ($isAjax) {
    header('Content-Type: application/json');
}

// Check authentication
if (empty($_SESSION['user_id'])) {
    debug_log('save_notifications_handler', 'Authentication failed - no user_id in session');
    
    // Return appropriate response based on request type
    if ($isAjax) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Unauthorized. Please log in again.',
            'error_code' => 'AUTH_REQUIRED'
        ]);
        exit;
    } else {
        // Normal page request - redirect to login
        header("Location: /login.php");
        exit;
    }
}

$user_id = $_SESSION['user_id'];

debug_log('save_notifications_handler', 'Processing request for user', ['user_id' => $user_id]);

try {
    // Get POST data
    $notifications_enabled = isset($_POST['notifications_enabled']) ? (int)$_POST['notifications_enabled'] : null;
    $onesignal_player_id = isset($_POST['onesignal_player_id']) ? $_POST['onesignal_player_id'] : null;
    $notify_at_time = isset($_POST['notify_at_time']) ? 1 : 0;
    $notify_after_10min = isset($_POST['notify_after_10min']) ? 1 : 0;
    $notify_after_20min = isset($_POST['notify_after_20min']) ? 1 : 0;
    $notify_after_30min = isset($_POST['notify_after_30min']) ? 1 : 0;
    $notify_after_60min = isset($_POST['notify_after_60min']) ? 1 : 0;
    
    debug_log('save_notifications_handler', 'Parsed settings', [
        'notifications_enabled' => $notifications_enabled,
        'has_player_id' => !empty($onesignal_player_id),
        'player_id_length' => $onesignal_player_id ? strlen($onesignal_player_id) : 0,
        'notify_at_time' => $notify_at_time,
        'notify_after_10min' => $notify_after_10min,
        'notify_after_20min' => $notify_after_20min,
        'notify_after_30min' => $notify_after_30min,
        'notify_after_60min' => $notify_after_60min,
    ]);

    // Check if settings exist for this user
    $stmt = $pdo->prepare("SELECT id FROM user_notification_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing settings
        $updateFields = [];
        $params = [];
        
        if ($notifications_enabled !== null) {
            $updateFields[] = "notifications_enabled = ?";
            $params[] = $notifications_enabled;
        }
        
        if ($onesignal_player_id !== null) {
            $updateFields[] = "onesignal_player_id = ?";
            $params[] = $onesignal_player_id;
        }
        
        // Always update all timing preferences if any timing field is present in the POST data
        // This ensures unchecked checkboxes (not included in POST) are properly set to 0
        if (isset($_POST['notify_at_time']) || isset($_POST['notify_after_10min']) || 
            isset($_POST['notify_after_20min']) || isset($_POST['notify_after_30min']) || 
            isset($_POST['notify_after_60min']) || isset($_POST['notifications_enabled'])) {
            // When updating via form, update all timing fields to handle unchecked checkboxes
            $updateFields[] = "notify_at_time = ?";
            $params[] = $notify_at_time;
            $updateFields[] = "notify_after_10min = ?";
            $params[] = $notify_after_10min;
            $updateFields[] = "notify_after_20min = ?";
            $params[] = $notify_after_20min;
            $updateFields[] = "notify_after_30min = ?";
            $params[] = $notify_after_30min;
            $updateFields[] = "notify_after_60min = ?";
            $params[] = $notify_after_60min;
        }
        
        if (!empty($updateFields)) {
            $params[] = $user_id;
            $sql = "UPDATE user_notification_settings SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            debug_log('save_notifications_handler', 'Updated existing settings', [
                'fields_updated' => count($updateFields),
                'sql_fields' => implode(', ', $updateFields)
            ]);
        } else {
            debug_log('save_notifications_handler', 'No fields to update');
        }
    } else {
        // Insert new settings
        $stmt = $pdo->prepare("
            INSERT INTO user_notification_settings 
            (user_id, notifications_enabled, notify_at_time, notify_after_10min, notify_after_20min, notify_after_30min, notify_after_60min, onesignal_player_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $notifications_enabled ?? 0,
            $notify_at_time,
            $notify_after_10min,
            $notify_after_20min,
            $notify_after_30min,
            $notify_after_60min,
            $onesignal_player_id
        ]);
        
        debug_log('save_notifications_handler', 'Inserted new settings for user');
    }

    http_response_code(200);
    
    $response = [
        'success' => true, 
        'message' => 'Settings saved successfully'
    ];
    
    debug_log('save_notifications_handler', 'Request completed successfully');
    
    if ($isAjax) {
        echo json_encode($response);
    } else {
        // For form submissions, redirect back with success message
        $_SESSION['success'] = 'Notification settings saved successfully';
        header("Location: /modules/settings/notifications.php");
    }
    exit;
    
} catch (Exception $e) {
    debug_log('save_notifications_handler', 'Database error occurred', [
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
    
    http_response_code(500);
    
    $errorResponse = [
        'success' => false, 
        'message' => 'Failed to save notification settings. Please try again.',
        'error_code' => 'DATABASE_ERROR'
    ];
    
    // Include detailed error in debug mode
    if (is_debug_enabled()) {
        $errorResponse['debug_info'] = $e->getMessage();
    }
    
    if ($isAjax) {
        echo json_encode($errorResponse);
    } else {
        // For form submissions, redirect back with error message
        $_SESSION['error'] = $errorResponse['message'];
        header("Location: /modules/settings/notifications.php");
    }
    exit;
}
