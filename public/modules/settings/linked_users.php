<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../app/core/TimeFormatter.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);
$pendingInvites = $linkedHelper->getPendingInvites($_SESSION['user_id']);

// Initialize TimeFormatter with current user's preferences
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linked Users</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div style="max-width: 800px; margin: 0 auto; padding: 80px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 24px;">👥 Linked Users</h2>
        
        <?php if ($successMsg): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                ✓ <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                ✗ <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($linkedUser && $linkedUser['status'] === 'active'): ?>
        <!-- Active Link -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; border-radius: 10px; margin-bottom: 20px;">
            <div style="font-size: 24px; font-weight: 600; margin-bottom: 8px;">
                🔗 Linked with <?= htmlspecialchars($linkedUser['linked_user_name']) ?>
            </div>
            <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: rgba(255,255,255,0.3);">
                ✓ ACTIVE
            </span>
            <p style="margin-top: 16px; opacity: 0.9;">
                You are currently linked. Manage permissions or unlink below.
            </p>
            <div style="margin-top: 20px;">
                <a href="/modules/settings/privacy_settings.php" class="btn btn-primary" style="margin-right: 8px;">
                    ⚙️ Manage Permissions
                </a>
                <button onclick="confirmUnlink(<?= $linkedUser['id'] ?>)" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    🔓 Unlink
                </button>
            </div>
        </div>
        
        <?php elseif ($linkedUser && $linkedUser['status'] === 'pending_b'): ?>
        <!-- Pending Setup -->
        <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 20px; font-weight: 600; color: var(--color-primary); margin-bottom: 16px;">
                ⏳ Pending Link Setup
            </div>
            <p>You've accepted the invite! Now both of you need to set privacy permissions before the link becomes active.</p>
            <a href="/modules/settings/privacy_settings.php" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">
                Set Privacy Permissions →
            </a>
        </div>
        
        <?php else: ?>
        
        <!-- Generate Invite -->
        <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 20px; font-weight: 600; color: var(--color-primary); margin-bottom: 16px;">
                📤 Invite Someone to Link
            </div>
            <p style="color: var(--color-text-secondary); margin-bottom: 20px;">
                Generate an invite code to share with someone you trust. They'll enter this code to link profiles.
                Your usernames remain private - only first names are shared.
            </p>
            <form method="POST" action="/modules/settings/linked_users_handler.php">
                <input type="hidden" name="action" value="generate_invite">
                <button type="submit" class="btn btn-primary">
                    Generate Invite Code
                </button>
            </form>
        </div>
        
        <!-- Accept Invite -->
        <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 20px; font-weight: 600; color: var(--color-primary); margin-bottom: 16px;">
                📥 Accept an Invite
            </div>
            <p style="color: var(--color-text-secondary); margin-bottom: 20px;">
                If someone sent you an invite code, enter it below to link your profiles.
            </p>
            <form method="POST" action="/modules/settings/linked_users_handler.php">
                <input type="hidden" name="action" value="accept_invite">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Invite Code</label>
                    <input type="text" name="invite_code" 
                           placeholder="Enter 10-character code" 
                           maxlength="10" 
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: monospace; font-size: 18px; letter-spacing: 2px; text-transform: uppercase;"
                           required>
                    <small style="display: block; margin-top: 8px; color: var(--color-text-secondary);">
                        Code format: ABCD123456 (10 characters)
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">
                    Accept Invite
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- Pending Invites -->
        <?php if (!empty($pendingInvites)): ?>
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 20px; font-weight: 600; color: var(--color-primary); margin-bottom: 16px;">
                ⏳ Your Pending Invites
            </div>
            <p style="color: var(--color-text-secondary); margin-bottom: 16px;">
                Invite codes you've generated that haven't been accepted yet.
            </p>
            
            <?php foreach ($pendingInvites as $invite): ?>
            <div style="background: var(--color-bg-light); padding: 16px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="font-family: monospace; font-size: 18px; letter-spacing: 2px;">
                        <?= htmlspecialchars($invite['invite_code']) ?>
                    </strong>
                    <br>
                    <small style="color: var(--color-text-secondary);">
                        Created: <?= $timeFormatter->formatDateTime($invite['created_at']) ?>
                        <br>
                        Expires: <?= $timeFormatter->formatDateTime($invite['expires_at']) ?>
                    </small>
                </div>
                <form method="POST" action="/modules/settings/linked_users_handler.php" style="margin: 0;">
                    <input type="hidden" name="action" value="revoke_invite">
                    <input type="hidden" name="link_id" value="<?= $invite['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">
                        Revoke
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 32px; text-align: center;">
            <a href="/dashboard.php" style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
    async function confirmUnlink(linkId) {
        const confirmed = await ConfirmModal.show({
            title: 'Unlink User',
            message: 'Are you sure you want to unlink? This will remove all shared permissions and notifications.',
            confirmText: 'Unlink',
            cancelText: 'Cancel',
            danger: true
        });
        
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/modules/settings/linked_users_handler.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'unlink';
            form.appendChild(actionInput);
            
            const linkIdInput = document.createElement('input');
            linkIdInput.type = 'hidden';
            linkIdInput.name = 'link_id';
            linkIdInput.value = linkId;
            form.appendChild(linkIdInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
