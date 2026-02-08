<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);

if (!$linkedUser) {
    $_SESSION['error_msg'] = "No linked user found";
    header("Location: /modules/settings/linked_users.php");
    exit;
}

// Get current permissions (what THEY can do with MY data)
$theirPermissions = $linkedHelper->getPermissions($linkedUser['id'], $linkedUser['linked_user_id']);

if (!$theirPermissions) {
    // Default permissions: all disabled except receive_nudges
    // receive_nudges defaults to 1 (enabled) to allow basic communication between linked users
    $theirPermissions = [
        'can_view_medications' => 0,
        'can_view_schedule' => 0,
        'can_mark_taken' => 0,
        'can_add_medications' => 0,
        'can_edit_medications' => 0,
        'can_delete_medications' => 0,
        'notify_on_medication_taken' => 0,
        'notify_on_overdue' => 0,
        'receive_nudges' => 1,  // Enabled by default for basic reminders
        'can_export_data' => 0
    ];
}

$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Settings</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--color-primary);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div style="max-width: 700px; margin: 0 auto; padding: 80px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 8px;">🔒 Privacy Settings</h2>
        <p style="color: var(--color-text-secondary); margin-bottom: 32px;">
            Control what <strong><?= htmlspecialchars($linkedUser['linked_user_name']) ?></strong> can see and do with your medications.
        </p>
        
        <?php if ($successMsg): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                ✓ <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/modules/settings/privacy_settings_handler.php">
            <input type="hidden" name="link_id" value="<?= $linkedUser['id'] ?>">
            <input type="hidden" name="their_user_id" value="<?= $linkedUser['linked_user_id'] ?>">
            
            <!-- View Permissions -->
            <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: var(--color-primary);">👁️ View Permissions</h3>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">View my medications</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can see what medications you take</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_view_medications" value="1" 
                               <?= $theirPermissions['can_view_medications'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0;">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">View my medication schedule</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can see when you're supposed to take meds</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_view_schedule" value="1" 
                               <?= $theirPermissions['can_view_schedule'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Action Permissions -->
            <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: var(--color-primary);">✏️ Action Permissions</h3>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Mark medications as taken</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can log medications on your behalf</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_mark_taken" value="1" 
                               <?= $theirPermissions['can_mark_taken'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Add medications</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can add new medications to your profile</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_add_medications" value="1" 
                               <?= $theirPermissions['can_add_medications'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Edit medications</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can modify your existing medications</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_edit_medications" value="1" 
                               <?= $theirPermissions['can_edit_medications'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Delete medications</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can remove medications from your profile</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_delete_medications" value="1" 
                               <?= $theirPermissions['can_delete_medications'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0;">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Export my medication data</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can download your medication history</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="can_export_data" value="1" 
                               <?= $theirPermissions['can_export_data'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Notification Permissions -->
            <div style="background: white; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: var(--color-primary);">🔔 Notification Preferences</h3>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Notify when I take medications</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They get notified when you mark a med as taken</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_on_medication_taken" value="1" 
                               <?= $theirPermissions['notify_on_medication_taken'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--color-bg-light);">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Notify when I have overdue meds</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They get alerts when your medications are overdue</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_on_overdue" value="1" 
                               <?= $theirPermissions['notify_on_overdue'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0;">
                    <div style="flex: 1;">
                        <strong style="display: block; color: var(--color-text); margin-bottom: 4px;">Allow them to send me nudges</strong>
                        <small style="color: var(--color-text-secondary); font-size: 13px;">They can remind you to take overdue meds</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="receive_nudges" value="1" 
                               <?= $theirPermissions['receive_nudges'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 16px;">
                💾 Save Privacy Settings
            </button>
        </form>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/modules/settings/linked_users.php" style="color: var(--color-text-secondary);">← Back to Linked Users</a>
        </div>
    </div>
</body>
</html>
