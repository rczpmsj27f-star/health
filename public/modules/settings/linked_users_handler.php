<?php
session_start();
require_once "../../../config.php";
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
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
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'generate_invite':
            $invite = $linkedHelper->createInvitation($_SESSION['user_id']);
            $_SESSION['success_msg'] = "Invite code generated: " . $invite['invite_code'] . " (expires in 7 days)";
            break;
            
        case 'accept_invite':
            $inviteCode = strtoupper(trim($_POST['invite_code'] ?? ''));
            if (empty($inviteCode)) {
                throw new Exception("Please enter an invite code");
            }
            
            $result = $linkedHelper->acceptInvite($_SESSION['user_id'], $inviteCode);
            
            if ($result['success']) {
                $_SESSION['success_msg'] = "Invite accepted from " . htmlspecialchars($result['inviter_name']) . "! Now set your privacy permissions.";

                // Notify the inviter that their link was accepted
                $stmt = $pdo->prepare("SELECT invited_by FROM user_links WHERE id = ?");
                $stmt->execute([$result['link_id']]);
                $linkRow = $stmt->fetch();

                $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $accepterRow = $stmt->fetch();

                if ($linkRow && $accepterRow) {
                    $notificationHelper->create(
                        $linkRow['invited_by'],
                        'link_accepted',
                        '🔗 Link Accepted',
                        $accepterRow['first_name'] . ' has accepted your link invitation',
                        $_SESSION['user_id']
                    );
                    sendPush($pdo, $linkRow['invited_by'], '🔗 Link Accepted', $accepterRow['first_name'] . ' has accepted your link invitation');

                    // Notify the accepter that they are now linked
                    $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
                    $stmt->execute([$linkRow['invited_by']]);
                    $inviterRow = $stmt->fetch();

                    if ($inviterRow) {
                        $notificationHelper->create(
                            $_SESSION['user_id'],
                            'link_request',
                            '🔗 Link Request Accepted',
                            'You are now linked with ' . $inviterRow['first_name'],
                            $linkRow['invited_by']
                        );
                        sendPush($pdo, $_SESSION['user_id'], '🔗 Link Request Accepted', 'You are now linked with ' . $inviterRow['first_name']);
                    }
                }

                header("Location: /modules/settings/privacy_settings.php");
                exit;
            } else {
                throw new Exception($result['error']);
            }
            break;
            
        case 'revoke_invite':
            $linkId = $_POST['link_id'] ?? 0;
            if (!is_numeric($linkId) || $linkId <= 0) {
                throw new Exception("Invalid invite ID");
            }
            if ($linkedHelper->revokeInvite($linkId, $_SESSION['user_id'])) {
                $_SESSION['success_msg'] = "Invite code revoked successfully";
            } else {
                throw new Exception("Failed to revoke invite");
            }
            break;
            
        case 'unlink':
            $linkId = $_POST['link_id'] ?? 0;
            if (!is_numeric($linkId) || $linkId <= 0) {
                throw new Exception("Invalid link ID");
            }
            if ($linkedHelper->unlinkUsers($linkId)) {
                $_SESSION['success_msg'] = "Successfully unlinked";
            } else {
                throw new Exception("Failed to unlink users");
            }
            break;
            
        default:
            throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header("Location: /modules/settings/linked_users.php");
exit;
