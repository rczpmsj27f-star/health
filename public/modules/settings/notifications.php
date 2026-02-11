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

// Fetch OneSignal Player ID from database to check if notifications are already enabled
$stmt = $pdo->prepare("SELECT onesignal_player_id FROM user_notification_settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
// Use empty array as fallback when no row exists - both cases (no row, null value) result in null $storedPlayerId
$playerIdRow = $stmt->fetch() ?: [];
$storedPlayerId = $playerIdRow['onesignal_player_id'] ?? null;

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
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
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
        
        <!-- Push Notification Permission Toggle -->
        <div id="push-permission-section" style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px; display: none;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 20px; margin-bottom: 16px;">📱 Push Notifications</h3>
            <div id="push-permission-status" style="padding: 12px; background: #f3f4f6; border-radius: 6px; margin-bottom: 16px;">
                Checking permission status...
            </div>
            <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 12px;">
                Enable push notifications to receive medication reminders, alerts, and updates even when the app is closed.
            </p>
            <button type="button" id="enable-push-btn" onclick="enablePushNotifications()" 
                    style="padding: 12px 24px; background: var(--color-primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                🔔 Enable Push Notifications
            </button>
            <div id="push-denied-message" style="display: none; margin-top: 16px; padding: 12px; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 14px;">
                <strong>⚠️ Permission Denied</strong><br>
                Push notifications are disabled. To enable them, go to your device Settings, find this app, tap Notifications, and allow notifications.
            </div>
        </div>
        
        <form method="POST" action="/modules/settings/notifications_handler.php">
            <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
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

    // Helper function to show notification settings as enabled
    function showNotificationSettings() {
        const statusElement = document.getElementById('push-permission-status');
        const button = document.getElementById('enable-push-btn');
        const deniedMessage = document.getElementById('push-denied-message');
        
        statusElement.innerHTML = '✅ Push Notifications Enabled';
        statusElement.style.background = '#d1fae5';
        statusElement.style.color = '#065f46';
        button.style.display = 'none';
        deniedMessage.style.display = 'none';
    }

    // Check and display push notification permission status
    async function checkPushPermissionStatus() {
        const section = document.getElementById('push-permission-section');
        const statusElement = document.getElementById('push-permission-status');
        const button = document.getElementById('enable-push-btn');
        const deniedMessage = document.getElementById('push-denied-message');
        
        // Check if push notifications are supported in the browser
        const isPushSupported = ('Notification' in window) && ('serviceWorker' in navigator);
        
        // For Capacitor (native app) environments
        if (isCapacitor()) {
            console.log('Running in Capacitor - checking native push permissions');
            section.style.display = 'block'; // Always show in Capacitor
            
            // If we have a stored Player ID in the database, notifications are working
            if (storedPlayerId) {
                console.log('✅ Player ID found in database - notifications are enabled');
                showNotificationSettings();
                return;
            }
            
            // For new users without a Player ID, check if native plugin is available
            try {
                // Check if the native plugin is available
                if (!window.OneSignalCapacitor || typeof window.OneSignalCapacitor.requestPermission !== 'function') {
                    // Plugin not available - show helpful message
                    statusElement.innerHTML = '⚠️ Push Notifications Setup Required';
                    statusElement.style.background = '#fef3c7';
                    statusElement.style.color = '#92400e';
                    button.style.display = 'none';
                    deniedMessage.innerHTML = '<strong>ℹ️ Native Plugin Not Available</strong><br>The push notification plugin may need to be registered in the Xcode project. See IOS_PUSH_PLUGIN_SETUP.md for setup instructions.';
                    deniedMessage.style.display = 'block';
                    return;
                }
                
                // Wait for OneSignal to be available
                await waitForOneSignal();
                
                // Check current permission status using OneSignal User API
                if (window.OneSignal && window.OneSignal.User) {
                    // Check if user has push subscription
                    const pushSubscription = window.OneSignal.User.PushSubscription;
                    
                    if (pushSubscription && pushSubscription.optedIn) {
                        // User is subscribed - permissions granted
                        showNotificationSettings();
                    } else {
                        // Not subscribed - show button to allow enabling
                        statusElement.innerHTML = '⚠️ Push Notifications Not Enabled';
                        statusElement.style.background = '#fef3c7';
                        statusElement.style.color = '#92400e';
                        button.style.display = 'inline-block';
                        deniedMessage.style.display = 'none';
                    }
                } else {
                    throw new Error('OneSignal.User not available');
                }
            } catch (error) {
                console.error('Error checking push permission status:', error);
                statusElement.innerHTML = '⚠️ Unable to check permission status';
                statusElement.style.background = '#f3f4f6';
                statusElement.style.color = '#6b7280';
                button.style.display = 'inline-block';
                deniedMessage.style.display = 'none';
            }
            return;
        }
        
        // For web browsers
        if (!isPushSupported) {
            console.log('⚠️ Push notifications not supported in this browser');
            section.style.display = 'block';
            statusElement.innerHTML = '⚠️ Push Notifications Not Available';
            statusElement.style.background = '#fef3c7';
            statusElement.style.color = '#92400e';
            button.style.display = 'none';
            deniedMessage.innerHTML = '<strong>⚠️ Not Supported</strong><br>Push notifications are not supported in this browser or environment.';
            deniedMessage.style.display = 'block';
            return;
        }
        
        // Show the section for web browsers with push support
        section.style.display = 'block';
        
        // Check actual browser permission state
        const permission = Notification.permission;
        console.log('🔔 Browser notification permission:', permission);
        
        if (permission === 'granted') {
            // Permission granted - check if we have a valid player ID
            if (storedPlayerId) {
                console.log('✅ Permission granted and Player ID registered');
                showNotificationSettings();
            } else {
                // Permission granted but no player ID - may need to register
                statusElement.innerHTML = '⚠️ Notifications Enabled (Registration Pending)';
                statusElement.style.background = '#fef3c7';
                statusElement.style.color = '#92400e';
                button.style.display = 'none';
                deniedMessage.innerHTML = '<strong>ℹ️ Registration Required</strong><br>Notification permission is granted but registration is not complete. Please reload the page or contact support if this persists.';
                deniedMessage.style.display = 'block';
            }
        } else if (permission === 'denied') {
            // Permission explicitly denied
            console.log('⚠️ Notification permission denied by user');
            statusElement.innerHTML = '❌ Push Notifications Denied';
            statusElement.style.background = '#fee2e2';
            statusElement.style.color = '#991b1b';
            button.style.display = 'none';
            deniedMessage.innerHTML = '<strong>⚠️ Permission Denied</strong><br>Push notifications are disabled. To enable them, go to your browser Settings, find this site, and allow notifications.';
            deniedMessage.style.display = 'block';
        } else {
            // Permission is 'default' - not yet decided
            console.log('🔔 Notification permission is default - showing enable button');
            statusElement.innerHTML = '⚠️ Push Notifications Not Enabled';
            statusElement.style.background = '#fef3c7';
            statusElement.style.color = '#92400e';
            button.style.display = 'inline-block';
            deniedMessage.style.display = 'none';
        }
    }
    
    // Wait for OneSignal to be available
    function waitForOneSignal(maxRetries = 50, retryDelay = 100) {
        return new Promise((resolve, reject) => {
            let retries = 0;
            
            const checkOneSignal = () => {
                if (typeof window.OneSignal !== 'undefined' && window.OneSignal.Notifications) {
                    resolve();
                } else if (retries < maxRetries) {
                    retries++;
                    setTimeout(checkOneSignal, retryDelay);
                } else {
                    reject(new Error('OneSignal not available after timeout'));
                }
            };
            
            checkOneSignal();
        });
    }
    
    // Enable push notifications
    async function enablePushNotifications() {
        const button = document.getElementById('enable-push-btn');
        const statusElement = document.getElementById('push-permission-status');
        const deniedMessage = document.getElementById('push-denied-message');
        
        button.disabled = true;
        button.innerHTML = '⏳ Requesting Permission...';
        
        try {
            // Handle Capacitor (native app)
            if (isCapacitor()) {
                // Use OneSignalCapacitor bridge to request permission
                if (window.OneSignalCapacitor && typeof window.OneSignalCapacitor.requestPermission === 'function') {
                    console.log('Requesting OneSignal permission...');
                    const accepted = await window.OneSignalCapacitor.requestPermission();
                    
                    console.log('Permission request result:', accepted);
                    
                    // Update UI based on result
                    if (accepted) {
                        statusElement.innerHTML = '✅ Push Notifications Enabled';
                        statusElement.style.background = '#d1fae5';
                        statusElement.style.color = '#065f46';
                        button.style.display = 'none';
                        deniedMessage.style.display = 'none';
                    } else {
                        statusElement.innerHTML = '❌ Push Notifications Disabled';
                        statusElement.style.background = '#fee2e2';
                        statusElement.style.color = '#991b1b';
                        button.style.display = 'none';
                        deniedMessage.innerHTML = '<strong>⚠️ Permission Denied</strong><br>Push notifications are disabled. To enable them, go to your device Settings, find this app, tap Notifications, and allow notifications.';
                        deniedMessage.style.display = 'block';
                    }
                } else {
                    throw new Error('OneSignalCapacitor.requestPermission not available');
                }
            } else {
                // Handle web browser
                // Wait for OneSignal to be available
                await waitForOneSignal();
                
                if (window.OneSignal && window.OneSignal.Notifications && 
                    typeof window.OneSignal.Notifications.requestPermission === 'function') {
                    console.log('Requesting browser notification permission...');
                    const accepted = await window.OneSignal.Notifications.requestPermission();
                    
                    console.log('Permission request result:', accepted);
                    
                    if (accepted) {
                        statusElement.innerHTML = '✅ Push Notifications Enabled';
                        statusElement.style.background = '#d1fae5';
                        statusElement.style.color = '#065f46';
                        button.style.display = 'none';
                        deniedMessage.style.display = 'none';
                    } else {
                        statusElement.innerHTML = '❌ Push Notifications Disabled';
                        statusElement.style.background = '#fee2e2';
                        statusElement.style.color = '#991b1b';
                        button.style.display = 'none';
                        deniedMessage.innerHTML = '<strong>⚠️ Permission Denied</strong><br>Push notifications are disabled. To enable them, go to your browser Settings, find this site, and allow notifications.';
                        deniedMessage.style.display = 'block';
                    }
                } else {
                    throw new Error('OneSignal.Notifications.requestPermission not available');
                }
            }
        } catch (error) {
            console.error('Error enabling push notifications:', error);
            alert('Failed to enable push notifications. Please try again.');
            button.disabled = false;
            button.innerHTML = '🔔 Enable Push Notifications';
        }
    }
    
    // Check permission status on page load
    // Wait for both DOM and OneSignal to be ready
    function initializePushSection(retryCount = 0) {
        const MAX_RETRIES = 50; // 50 * 100ms = 5 seconds
        const RETRY_DELAY = 100;
        
        if (typeof window.OneSignal !== 'undefined' || retryCount >= MAX_RETRIES) {
            // OneSignal is available or we've timed out - check status
            checkPushPermissionStatus();
        } else {
            // Wait and retry
            setTimeout(() => initializePushSection(retryCount + 1), RETRY_DELAY);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializePushSection(0));
    } else {
        initializePushSection(0);
    }
    </script>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
