<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/NotificationHelper.php";

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);
$notificationHelper = new NotificationHelper($pdo);

$medicationId = $_POST['medication_id'] ?? 0;
$toUserId = $_POST['to_user_id'] ?? 0;

// Verify linked user
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);
if (!$linkedUser || $linkedUser['linked_user_id'] != $toUserId) {
    echo json_encode(['success' => false, 'error' => 'Not linked']);
    exit;
}

// Check if they allow nudges
$theirPermissions = $linkedHelper->getPermissions($linkedUser['id'], $toUserId);
if (!$theirPermissions || !$theirPermissions['receive_nudges']) {
    echo json_encode(['success' => false, 'error' => 'Nudges not allowed']);
    exit;
}

// Check cooldown
if (!$linkedHelper->canNudge($_SESSION['user_id'], $toUserId, $medicationId)) {
    echo json_encode(['success' => false, 'error' => 'Please wait before nudging again (1 hour cooldown)']);
    exit;
}

// Get medication name
$stmt = $pdo->prepare("SELECT name FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medicationId, $toUserId]);
$med = $stmt->fetch();

if (!$med) {
    echo json_encode(['success' => false, 'error' => 'Medication not found']);
    exit;
}

// Get sender name
$stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$myNameRow = $stmt->fetch();

if (!$myNameRow) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$myName = $myNameRow['first_name'];

// Send nudge notification
$notificationHelper->create(
    $toUserId,
    'nudge_received',
    '👋 Nudge from ' . $myName,
    $myName . ' is reminding you to take "' . $med['name'] . '"',
    $_SESSION['user_id'],
    $medicationId
);

// Record nudge
$linkedHelper->recordNudge($_SESSION['user_id'], $toUserId, $medicationId);

echo json_encode(['success' => true, 'message' => 'Nudge sent!']);
