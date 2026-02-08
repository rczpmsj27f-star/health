<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$notificationHelper = new NotificationHelper($pdo);

$preferences = [];
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
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
