<?php
session_start();
require_once "../../../app/config/database.php";

// Set JSON content type header
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get POST data
    $notifications_enabled = isset($_POST['notifications_enabled']) ? (int)$_POST['notifications_enabled'] : null;
    $onesignal_player_id = isset($_POST['onesignal_player_id']) ? $_POST['onesignal_player_id'] : null;
    $notify_at_time = isset($_POST['notify_at_time']) ? 1 : 0;
    $notify_after_10min = isset($_POST['notify_after_10min']) ? 1 : 0;
    $notify_after_20min = isset($_POST['notify_after_20min']) ? 1 : 0;
    $notify_after_30min = isset($_POST['notify_after_30min']) ? 1 : 0;
    $notify_after_60min = isset($_POST['notify_after_60min']) ? 1 : 0;

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
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
