<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);

// Validate and sanitize input
$linkId = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
$theirUserId = filter_input(INPUT_POST, 'their_user_id', FILTER_VALIDATE_INT);

if (!$linkId || !$theirUserId || $linkId <= 0 || $theirUserId <= 0) {
    $_SESSION['error_msg'] = "Invalid request parameters";
    header("Location: /modules/settings/linked_users.php");
    exit;
}

// Verify the link belongs to the current user
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);
if (!$linkedUser || $linkedUser['id'] !== $linkId) {
    $_SESSION['error_msg'] = "Unauthorized: This link does not belong to you";
    header("Location: /modules/settings/linked_users.php");
    exit;
}

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
        // Get the linked user's name from database instead of POST for security
        $_SESSION['success_msg'] = "Privacy settings saved! Waiting for " . 
                                   htmlspecialchars($linkedUser['linked_user_name']) . 
                                   " to set their permissions.";
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = "Failed to save settings: " . $e->getMessage();
    header("Location: /modules/settings/privacy_settings.php");
    exit;
}

header("Location: /modules/settings/linked_users.php");
exit;
