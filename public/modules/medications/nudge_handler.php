<?php
session_start();
require_once "../../../config.php";
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/NotificationHelper.php";

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

function sendPush($pdo, $userId, $title, $message) {
    $envFile = __DIR__ . '/../../../.env';
    if (!file_exists($envFile)) return;
    $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    $appId  = $env['ONESIGNAL_APP_ID'] ?? '';
    $apiKey = $env['ONESIGNAL_REST_API_KEY'] ?? '';
    if (!$appId || !$apiKey) return;

    $stmt = $pdo->prepare("SELECT onesignal_player_id FROM user_notification_settings WHERE user_id = ? AND notifications_enabled = 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || empty($row['onesignal_player_id'])) return;

    $payload = json_encode([
        'app_id'                   => $appId,
        'include_subscription_ids' => [$row['onesignal_player_id']],
        'headings'                 => ['en' => $title],
        'contents'                 => ['en' => $message],
        'ios_badgeType'            => 'Increase',
        'ios_badgeCount'           => 1,
    ]);

    $ch = curl_init('https://onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log("Direct push to user $userId: curl error: " . curl_error($ch));
        curl_close($ch);
        return;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    error_log("Direct push to user $userId: HTTP $httpCode, Response: $response");
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

sendPush($pdo, $toUserId, '👋 Nudge from ' . $myName, $myName . ' is reminding you to take "' . $med['name'] . '"');

// Record nudge
$linkedHelper->recordNudge($_SESSION['user_id'], $toUserId, $medicationId);

echo json_encode(['success' => true, 'message' => 'Nudge sent!']);
