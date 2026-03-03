<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
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
                        'link_request',
                        '🔗 Link Accepted',
                        $accepterRow['first_name'] . ' has accepted your link invitation',
                        $_SESSION['user_id']
                    );
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
