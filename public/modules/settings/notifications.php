<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/NotificationHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$notificationHelper = new NotificationHelper($pdo);
$preferences = $notificationHelper->getPreferences($_SESSION['user_id']);

// Fetch OneSignal Player ID and reminder settings from database
$stmt = $pdo->prepare("SELECT onesignal_player_id, notify_at_time, notify_after_10min, notify_after_20min, notify_after_30min, notify_after_60min FROM user_notification_settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
// Use empty array as fallback when no row exists
$settingsRow = $stmt->fetch() ?: [];
$storedPlayerId = $settingsRow['onesignal_player_id'] ?? null;

// Reminder settings with defaults
$reminderSettings = [
    'notify_at_time' => $settingsRow['notify_at_time'] ?? 1,
    'notify_after_10min' => $settingsRow['notify_after_10min'] ?? 1,
    'notify_after_20min' => $settingsRow['notify_after_20min'] ?? 1,
    'notify_after_30min' => $settingsRow['notify_after_30min'] ?? 1,
    'notify_after_60min' => $settingsRow['notify_after_60min'] ?? 0
];

// Default notification types
$notificationTypes = [
    'medication_reminder' => 'Medication Reminders',
    'overdue_alert' => 'Overdue Alerts',
    'partner_took_med' => 'Partner Took Medication',
    'partner_overdue' => 'Partner Has Overdue Meds',
    'nudge_received' => 'Nudge Received',
    'link_request' => 'Link Requests',
    'link_accepted' => 'Link Accepted',
    'stock_low' => 'Stock Low Warning'
];

$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/capacitor-push.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/onesignal-capacitor.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div style="max-width: 900px; margin: 0 auto; padding: 16px 16px 40px 16px;">
        <h2 style="color: var(--color-primary); font-size: 28px; margin-bottom: 24px;">🔔 Notification Preferences</h2>
        
        <?php if ($successMsg): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                ✓ <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <!-- iOS Native Push Notification Section (Capacitor) -->
        <div id="ios-push-status" style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px; display: none;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 20px; margin-bottom: 16px;">📱 iOS Push Notifications</h3>
            <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 12px;">
                Enable iOS push notifications to receive medication reminders, alerts, and updates even when the app is closed.
            </p>
            <button type="button" id="enable-ios-push-btn" onclick="initializeNativePush()" 
                    style="padding: 12px 24px; background: var(--color-primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                🔔 Enable iOS Push Notifications
            </button>
            <div id="ios-push-status-message" style="margin-top: 16px; padding: 12px; background: #f3f4f6; border-radius: 6px; font-size: 14px;">
            </div>
        </div>
        
        <form method="POST" action="/modules/settings/notifications_handler.php">
            <!-- Notification Types Section (Expandable) -->
            <div class="expandable-section" style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <div class="section-header-toggle" onclick="toggleSection(this)" style="cursor: pointer; user-select: none; margin: -8px -8px 16px -8px; padding: 12px 8px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; background: var(--color-bg-light);">
                    <h3 style="margin: 0; color: var(--color-primary); font-size: 18px;">📬 Notification Types</h3>
                    <span class="toggle-icon" style="font-size: 20px;">▶</span>
                </div>
                <div class="section-content">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--color-bg-light);">
                                <th style="text-align: left; padding: 12px;">Notification Type</th>
                                <th style="text-align: center; padding: 12px; width: 100px;">In-App</th>
                                <th style="text-align: center; padding: 12px; width: 100px;">Push</th>
                                <th style="text-align: center; padding: 12px; width: 100px;">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notificationTypes as $type => $label): 
                                $pref = $preferences[$type] ?? ['in_app' => 1, 'push' => 1, 'email' => 0];
                            ?>
                            <tr style="border-bottom: 1px solid var(--color-bg-light);">
                                <td style="padding: 12px;"><?= htmlspecialchars($label) ?></td>
                                <td style="text-align: center; padding: 12px;">
                                    <input type="checkbox" name="<?= $type ?>[in_app]" value="1" 
                                           <?= $pref['in_app'] ? 'checked' : '' ?>>
                                </td>
                                <td style="text-align: center; padding: 12px;">
                                    <input type="checkbox" name="<?= $type ?>[push]" value="1" 
                                           <?= $pref['push'] ? 'checked' : '' ?>>
                                </td>
                                <td style="text-align: center; padding: 12px;">
                                    <input type="checkbox" name="<?= $type ?>[email]" value="1" 
                                           <?= $pref['email'] ? 'checked' : '' ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Medicine Reminder Frequency Section (Expandable) -->
            <div class="expandable-section" style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <div class="section-header-toggle" onclick="toggleSection(this)" style="cursor: pointer; user-select: none; margin: -8px -8px 16px -8px; padding: 12px 8px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; background: var(--color-bg-light);">
                    <h3 style="margin: 0; color: var(--color-primary); font-size: 18px;">⏰ Medicine Reminder Frequency</h3>
                    <span class="toggle-icon" style="font-size: 20px;">▶</span>
                </div>
                <div class="section-content">
                    <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 20px;">
                        Choose when you want to receive medication reminders. You can enable multiple reminder times to ensure you don't miss a dose.
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--color-bg-light); border-radius: 8px; cursor: pointer; transition: background 0.2s;">
                            <input type="checkbox" name="notify_at_time" value="1" <?= $reminderSettings['notify_at_time'] ? 'checked' : '' ?> style="margin-top: 2px;">
                            <div>
                                <strong style="display: block; margin-bottom: 4px;">At scheduled time</strong>
                                <span style="font-size: 14px; color: var(--color-text-secondary);">Send reminder at the exact scheduled medication time</span>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--color-bg-light); border-radius: 8px; cursor: pointer; transition: background 0.2s;">
                            <input type="checkbox" name="notify_after_10min" value="1" <?= $reminderSettings['notify_after_10min'] ? 'checked' : '' ?> style="margin-top: 2px;">
                            <div>
                                <strong style="display: block; margin-bottom: 4px;">10 minutes after (if not taken)</strong>
                                <span style="font-size: 14px; color: var(--color-text-secondary);">Send reminder 10 minutes after scheduled time if medication not taken</span>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--color-bg-light); border-radius: 8px; cursor: pointer; transition: background 0.2s;">
                            <input type="checkbox" name="notify_after_20min" value="1" <?= $reminderSettings['notify_after_20min'] ? 'checked' : '' ?> style="margin-top: 2px;">
                            <div>
                                <strong style="display: block; margin-bottom: 4px;">20 minutes after (if not taken)</strong>
                                <span style="font-size: 14px; color: var(--color-text-secondary);">Send reminder 20 minutes after scheduled time if medication not taken</span>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--color-bg-light); border-radius: 8px; cursor: pointer; transition: background 0.2s;">
                            <input type="checkbox" name="notify_after_30min" value="1" <?= $reminderSettings['notify_after_30min'] ? 'checked' : '' ?> style="margin-top: 2px;">
                            <div>
                                <strong style="display: block; margin-bottom: 4px;">30 minutes after (if not taken)</strong>
                                <span style="font-size: 14px; color: var(--color-text-secondary);">Send reminder 30 minutes after scheduled time if medication not taken</span>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: var(--color-bg-light); border-radius: 8px; cursor: pointer; transition: background 0.2s;">
                            <input type="checkbox" name="notify_after_60min" value="1" <?= $reminderSettings['notify_after_60min'] ? 'checked' : '' ?> style="margin-top: 2px;">
                            <div>
                                <strong style="display: block; margin-bottom: 4px;">60 minutes after (if not taken)</strong>
                                <span style="font-size: 14px; color: var(--color-text-secondary);">Send reminder 60 minutes after scheduled time if medication not taken</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%; padding: 16px;">
                💾 Save Preferences
            </button>
        </form>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="/dashboard.php" style="color: var(--color-text-secondary);">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
    // Stored Player ID from database
    const storedPlayerId = <?= json_encode($storedPlayerId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>;
    
    // Check if running in Capacitor (native app)
    function isCapacitor() {
        return typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
    }

    // Toggle expandable sections
    function toggleSection(header) {
        const section = header.closest('.expandable-section');
        const content = section.querySelector('.section-content');
        const icon = header.querySelector('.toggle-icon');
        
        section.classList.toggle('expanded');
    }
    </script>
    
    <style>
    /* Expandable section styles */
    .expandable-section .section-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    
    .expandable-section.expanded .section-content {
        max-height: 5000px;
    }
    
    .expandable-section .toggle-icon {
        transition: transform 0.3s ease;
        display: inline-block;
    }
    
    .expandable-section.expanded .toggle-icon {
        transform: rotate(90deg);
    }
    
    .section-header-toggle:hover {
        background: #e5e7eb !important;
    }
    
    /* Hover effect for reminder labels */
    label:has(input[type="checkbox"]):hover {
        background: #e5e7eb !important;
    }
    </style>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
