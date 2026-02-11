<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$notificationHelper = new NotificationHelper($pdo);

// Define valid notification types
$validTypes = [
    'medication_reminder',
    'overdue_alert',
    'partner_took_med',
    'partner_overdue',
    'nudge_received',
    'link_request',
    'link_accepted',
    'stock_low'
];

$preferences = [];
foreach ($_POST as $key => $value) {
    // Only process valid notification types
    if (in_array($key, $validTypes) && is_array($value)) {
        $preferences[$key] = [
            'in_app' => isset($value['in_app']) ? 1 : 0,
            'push' => isset($value['push']) ? 1 : 0,
            'email' => isset($value['email']) ? 1 : 0
        ];
    }
}

$notificationHelper->savePreferences($_SESSION['user_id'], $preferences);

// Save reminder frequency settings
$reminderSettings = [
    'notify_at_time' => isset($_POST['notify_at_time']) ? 1 : 0,
    'notify_after_10min' => isset($_POST['notify_after_10min']) ? 1 : 0,
    'notify_after_20min' => isset($_POST['notify_after_20min']) ? 1 : 0,
    'notify_after_30min' => isset($_POST['notify_after_30min']) ? 1 : 0,
    'notify_after_60min' => isset($_POST['notify_after_60min']) ? 1 : 0
];

// Update user_notification_settings table with reminder preferences
$stmt = $pdo->prepare("
    UPDATE user_notification_settings 
    SET notify_at_time = ?, 
        notify_after_10min = ?, 
        notify_after_20min = ?, 
        notify_after_30min = ?, 
        notify_after_60min = ?
    WHERE user_id = ?
");
$stmt->execute([
    $reminderSettings['notify_at_time'],
    $reminderSettings['notify_after_10min'],
    $reminderSettings['notify_after_20min'],
    $reminderSettings['notify_after_30min'],
    $reminderSettings['notify_after_60min'],
    $_SESSION['user_id']
]);

$_SESSION['success_msg'] = "Notification preferences saved successfully!";

header("Location: /modules/settings/notifications.php");
exit;
