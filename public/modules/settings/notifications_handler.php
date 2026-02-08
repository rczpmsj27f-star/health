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
$_SESSION['success_msg'] = "Notification preferences saved successfully!";

header("Location: /modules/settings/notifications.php");
exit;
