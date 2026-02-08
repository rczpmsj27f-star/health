<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);

$linkId = $_POST['link_id'] ?? 0;
$theirUserId = $_POST['their_user_id'] ?? 0;

$permissions = [
    'can_view_medications' => isset($_POST['can_view_medications']) ? 1 : 0,
    'can_view_schedule' => isset($_POST['can_view_schedule']) ? 1 : 0,
    'can_mark_taken' => isset($_POST['can_mark_taken']) ? 1 : 0,
    'can_add_medications' => isset($_POST['can_add_medications']) ? 1 : 0,
    'can_edit_medications' => isset($_POST['can_edit_medications']) ? 1 : 0,
    'can_delete_medications' => isset($_POST['can_delete_medications']) ? 1 : 0,
    'notify_on_medication_taken' => isset($_POST['notify_on_medication_taken']) ? 1 : 0,
    'notify_on_overdue' => isset($_POST['notify_on_overdue']) ? 1 : 0,
    'receive_nudges' => isset($_POST['receive_nudges']) ? 1 : 0
];

try {
    // Save permissions for the linked user (what THEY can do with MY data)
    $linkedHelper->savePermissions($linkId, $theirUserId, $permissions);
    
    // Try to activate the link if both users have set permissions
    if ($linkedHelper->activateLink($linkId)) {
        $_SESSION['success_msg'] = "Privacy settings saved and link activated!";
    } else {
        $_SESSION['success_msg'] = "Privacy settings saved! Waiting for " . 
                                   htmlspecialchars($_POST['their_name'] ?? 'your partner') . 
                                   " to set their permissions.";
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = "Failed to save settings: " . $e->getMessage();
    header("Location: /modules/settings/privacy_settings.php");
    exit;
}

header("Location: /modules/settings/linked_users.php");
exit;
