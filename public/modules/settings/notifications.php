<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../config.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$isAdmin = Auth::isAdmin();
$user_id = $_SESSION['user_id'];

// Load existing notification settings
$stmt = $pdo->prepare("SELECT * FROM user_notification_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

// If no settings exist, create default settings
if (!$settings) {
    $stmt = $pdo->prepare("
        INSERT INTO user_notification_settings 
        (user_id, notifications_enabled, notify_at_time, notify_after_10min, notify_after_20min, notify_after_30min, notify_after_60min) 
        VALUES (?, 0, 1, 1, 1, 1, 0)
    ");
    $stmt->execute([$user_id]);
    
    // Reload settings
    $stmt = $pdo->prepare("SELECT * FROM user_notification_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings</title>
    
    <!-- OneSignal App ID for client-side JavaScript -->
    <script>
        window.ONESIGNAL_APP_ID = '<?php echo htmlspecialchars(ONESIGNAL_APP_ID, ENT_QUOTES, 'UTF-8'); ?>';
    </script>
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <style>
        .settings-container {
            padding: 80px 16px 40px 16px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .notification-status {
            background: #f0f4ff;
            border: 2px solid #4c6ef5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .notification-status.enabled {
            background: #d3f9d8;
            border-color: #2f9e44;
        }
        
        .notification-status p {
            margin: 0 0 16px 0;
            font-size: 16px;
        }
        
        .settings-list {
            margin-top: 24px;
        }
        
        .setting-item {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .toggle-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
            user-select: none;
        }
        
        .toggle-text {
            flex: 1;
            font-size: 16px;
            color: #333;
        }
        
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            margin-left: 12px;
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
            transition: .4s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #4c6ef5;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        input:disabled + .toggle-slider {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 32px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="settings-container">
        <div class="page-card">
            <div class="page-header">
                <h2>🔔 Notification Settings</h2>
                <p>Configure your medication reminder notifications</p>
            </div>

            <div id="notificationStatus" class="notification-status">
                <p><strong>Push notifications are currently disabled.</strong></p>
                <p>Enable push notifications to receive medication reminders on this device.</p>
                <button id="enableNotificationsBtn" class="btn btn-primary">Enable Notifications</button>
            </div>

            <div id="notificationSettings" class="settings-list" style="display: none;">
                <h3 class="section-title">Reminder Timing</h3>
                <p style="color: #666; margin-bottom: 16px;">Choose when you want to receive reminder notifications if you haven't taken your medication.</p>
                
                <form id="settingsForm" method="POST" action="save_notifications_handler.php">
                    <div class="setting-item">
                        <label class="toggle-label">
                            <span class="toggle-text">At scheduled time</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="notify_at_time" id="notifyAtTime" 
                                    <?= $settings['notify_at_time'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="toggle-label">
                            <span class="toggle-text">10 minutes after (if not taken)</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="notify_after_10min" id="notifyAfter10Min" 
                                    <?= $settings['notify_after_10min'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="toggle-label">
                            <span class="toggle-text">20 minutes after (if not taken)</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="notify_after_20min" id="notifyAfter20Min" 
                                    <?= $settings['notify_after_20min'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="toggle-label">
                            <span class="toggle-text">30 minutes after (if not taken)</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="notify_after_30min" id="notifyAfter30Min" 
                                    <?= $settings['notify_after_30min'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="toggle-label">
                            <span class="toggle-text">60 minutes after (if not taken)</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="notify_after_60min" id="notifyAfter60Min" 
                                    <?= $settings['notify_after_60min'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 24px; width: 100%;">
                        💾 Save Preferences
                    </button>
                </form>
                
                <div style="margin-top: 24px; text-align: center;">
                    <button id="disableNotificationsBtn" class="btn btn-danger">
                        🔕 Disable Notifications
                    </button>
                </div>
            </div>

            <div class="info-box" style="margin-top: 32px;">
                <p><strong>Note:</strong> Notifications are device-specific. You'll need to enable them on each device where you want to receive reminders.</p>
            </div>
            
            <div class="page-footer" style="margin-top: 32px; text-align: center;">
                <p><a href="/dashboard.php">⬅️ Back to Dashboard</a></p>
            </div>
        </div>
    </div>

    <script>
        // OneSignal Configuration
        let OneSignal;
        let notificationsEnabled = <?= $settings['notifications_enabled'] ? 'true' : 'false' ?>;

        // Initialize OneSignal when page loads
        async function initializeOneSignal() {
            try {
                if (window.ONESIGNAL_APP_ID === 'YOUR_ONESIGNAL_APP_ID') {
                    console.warn('OneSignal App ID not configured. Notifications will not work.');
                    document.getElementById('notificationStatus').innerHTML = 
                        '<p style="color: #dc3545;"><strong>OneSignal not configured.</strong></p>' +
                        '<p>Please contact your administrator to set up push notifications.</p>';
                    document.getElementById('enableNotificationsBtn').disabled = true;
                    return;
                }

                window.OneSignal = window.OneSignal || [];
                OneSignal = window.OneSignal;

                OneSignal.push(function() {
                    OneSignal.init({
                        appId: window.ONESIGNAL_APP_ID,
                        allowLocalhostAsSecureOrigin: true,
                        notifyButton: {
                            enable: false
                        }
                    });
                });

                console.log('OneSignal initialized');
                
                // Check current notification permission
                checkNotificationPermission();
            } catch (error) {
                console.error('Failed to initialize OneSignal:', error);
            }
        }

        // Check notification permission status
        async function checkNotificationPermission() {
            if (!('Notification' in window)) {
                console.log('This browser does not support notifications');
                return;
            }

            // Wait for OneSignal to be ready
            setTimeout(async () => {
                try {
                    const permission = Notification.permission;
                    console.log('Notification permission:', permission);
                    
                    if (permission === 'granted' && notificationsEnabled) {
                        showNotificationSettings();
                    } else {
                        showNotificationPrompt();
                    }
                } catch (error) {
                    console.error('Error checking notification permission:', error);
                }
            }, 1000);
        }

        // Show notification prompt
        function showNotificationPrompt() {
            const statusDiv = document.getElementById('notificationStatus');
            statusDiv.style.display = 'block';
            statusDiv.className = 'notification-status';
            document.getElementById('notificationSettings').style.display = 'none';
        }

        // Show notification settings
        function showNotificationSettings() {
            const statusDiv = document.getElementById('notificationStatus');
            statusDiv.style.display = 'block';
            statusDiv.className = 'notification-status enabled';
            statusDiv.innerHTML = '<p><strong>✅ Push notifications are enabled on this device.</strong></p>';
            document.getElementById('notificationSettings').style.display = 'block';
        }

        // Request notification permission
        async function requestNotificationPermission() {
            if (!('Notification' in window)) {
                alert('This browser does not support notifications');
                return;
            }

            if (!window.OneSignal) {
                alert('OneSignal is not initialized. Please refresh the page and try again.');
                return;
            }

            try {
                // Request permission via OneSignal
                await window.OneSignal.push(async function() {
                    await window.OneSignal.showNativePrompt();
                });

                // Wait a moment for permission to be processed
                setTimeout(async () => {
                    const permission = Notification.permission;
                    
                    if (permission === 'granted') {
                        console.log('Notification permission granted');
                        
                        // Get OneSignal player ID
                        const playerId = await window.OneSignal.push(function() {
                            return window.OneSignal.getUserId();
                        });
                        
                        // Save notification enabled status to database
                        await saveNotificationStatus(true, playerId);
                        
                        showNotificationSettings();
                    } else {
                        alert('Notification permission denied. You will not receive medication reminders on this device.');
                    }
                }, 500);
            } catch (error) {
                console.error('Failed to request notification permission:', error);
                alert('Failed to enable notifications. Please try again.');
            }
        }

        // Disable notifications
        async function disableNotifications() {
            if (confirm('Are you sure you want to disable notifications on this device?')) {
                try {
                    await saveNotificationStatus(false, null);
                    notificationsEnabled = false;
                    showNotificationPrompt();
                } catch (error) {
                    console.error('Failed to disable notifications:', error);
                    alert('Failed to disable notifications. Please try again.');
                }
            }
        }

        // Save notification status to database
        async function saveNotificationStatus(enabled, playerId) {
            const formData = new FormData();
            formData.append('notifications_enabled', enabled ? '1' : '0');
            if (playerId) {
                formData.append('onesignal_player_id', playerId);
            }

            const response = await fetch('save_notifications_handler.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to save notification status');
            }
        }

        // Auto-save settings when toggles change
        document.querySelectorAll('#settingsForm input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', async function() {
                const formData = new FormData(document.getElementById('settingsForm'));
                
                try {
                    const response = await fetch('save_notifications_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        console.log('Settings auto-saved');
                    }
                } catch (error) {
                    console.error('Failed to auto-save settings:', error);
                }
            });
        });

        // Event listeners
        document.getElementById('enableNotificationsBtn').addEventListener('click', requestNotificationPermission);
        document.getElementById('disableNotificationsBtn').addEventListener('click', disableNotifications);

        // Handle form submission (manual save)
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('save_notifications_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    alert('✅ Preferences saved successfully!');
                } else {
                    alert('❌ Failed to save preferences. Please try again.');
                }
            } catch (error) {
                console.error('Failed to save settings:', error);
                alert('❌ Failed to save preferences. Please try again.');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializeOneSignal);
    </script>
</body>
</html>
