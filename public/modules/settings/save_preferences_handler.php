<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/settings/preferences.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get form values
$darkMode = isset($_POST['dark_mode']) ? 1 : 0;
$timeFormat = $_POST['time_format'] ?? '12h';
$stockNotificationEnabled = isset($_POST['stock_notification_enabled']) ? 1 : 0;
$stockNotificationThreshold = intval($_POST['stock_notification_threshold'] ?? 10);
$notifyLinkedUsers = isset($_POST['notify_linked_users']) ? 1 : 0;

// Validate time format
if (!in_array($timeFormat, ['12h', '24h'])) {
    $_SESSION['error'] = "Invalid time format selected.";
    header("Location: /modules/settings/preferences.php");
    exit;
}

// Validate threshold
if ($stockNotificationThreshold < 1 || $stockNotificationThreshold > 90) {
    $_SESSION['error'] = "Stock notification threshold must be between 1 and 90 days.";
    header("Location: /modules/settings/preferences.php");
    exit;
}

try {
    // Update or insert preferences
    $stmt = $pdo->prepare("
        INSERT INTO user_preferences 
            (user_id, dark_mode, time_format, stock_notification_threshold, stock_notification_enabled, notify_linked_users) 
        VALUES 
            (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            dark_mode = VALUES(dark_mode),
            time_format = VALUES(time_format),
            stock_notification_threshold = VALUES(stock_notification_threshold),
            stock_notification_enabled = VALUES(stock_notification_enabled),
            notify_linked_users = VALUES(notify_linked_users),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([
        $userId,
        $darkMode,
        $timeFormat,
        $stockNotificationThreshold,
        $stockNotificationEnabled,
        $notifyLinkedUsers
    ]);
    
    $_SESSION['success'] = "Preferences saved successfully!";
} catch (PDOException $e) {
    error_log("Failed to save preferences for user $userId: " . $e->getMessage());
    $_SESSION['error'] = "Failed to save preferences. Please try again.";
}

header("Location: /modules/settings/preferences.php");
exit;
