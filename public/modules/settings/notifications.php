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
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <!-- OneSignal Initialization -->
    <script>
        // v16 initialization pattern
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        
        OneSignalDeferred.push(async function(OneSignal) {
    console.log('🔵 OneSignal callback triggered');
    console.log('🔵 OneSignal object:', OneSignal);
    
    // Get App ID from server-side config
    const appId = '<?php echo htmlspecialchars(ONESIGNAL_APP_ID, ENT_QUOTES, 'UTF-8'); ?>';
    console.log('🔵 App ID:', appId);
    
    if (!appId || appId === 'YOUR_ONESIGNAL_APP_ID') {
        console.warn('OneSignal App ID not configured. Set ONESIGNAL_APP_ID in config.php');
        alert('❌ App ID not configured');
        return;
    }
    
    try {
        console.log('🔵 Starting OneSignal.init...');
        await OneSignal.init({
            appId: appId,
            allowLocalhostAsSecureOrigin: true,
            serviceWorkerPath: '/OneSignalSDKWorker.js',
            serviceWorkerParam: { scope: '/' },
            notifyButton: {
                enable: false
            }
        });
        console.log('✅ OneSignal.init completed');
        
        // Check what's available
        console.log('🔵 OneSignal.User:', OneSignal.User);
        console.log('🔵 OneSignal.User.PushSubscription:', OneSignal.User?.PushSubscription);
        
        // Make available globally
        window.OneSignal = OneSignal;
        alert('✅ OneSignal initialized successfully');
        console.log('✅ OneSignal initialized');
    } catch (error) {
        console.error('❌ OneSignal init failed:', error);
        alert('❌ Init failed: ' + error.message);
    }
});
    </script>
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    
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
        let notificationsEnabled = <?= $settings['notifications_enabled'] ? 'true' : 'false' ?>;
        let storedPlayerId = <?= $settings['onesignal_player_id'] ? '"' . htmlspecialchars($settings['onesignal_player_id'], ENT_QUOTES, 'UTF-8') . '"' : 'null' ?>;

        // Initialize OneSignal when page loads
        async function initializeOneSignal() {
            try {
                // OneSignal is already initialized via OneSignalDeferred in the head section
                // Just wait for it to be ready
                console.log('Waiting for OneSignal to be ready...');
                
                // Wait for OneSignal to be available
                let attempts = 0;
                const startTime = Date.now();
                while (!window.OneSignal && attempts < 50) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    attempts++;
                }
                
                if (!window.OneSignal) {
                    console.warn(`OneSignal not available after ${attempts * 100}ms`);
                    document.getElementById('notificationStatus').innerHTML = 
                        '<p style="color: #dc3545;"><strong>OneSignal failed to load.</strong></p>' +
                        '<p>Please refresh the page or check your internet connection.</p>';
                    return;
                }
                
                console.log(`✅ OneSignal is ready (loaded in ${Date.now() - startTime}ms)`);
                
                // Check current notification permission
                checkNotificationPermission();
            } catch (error) {
                console.error('Failed to initialize OneSignal:', error);
            }
        }

        // Check notification permission status
        async function checkNotificationPermission() {
            try {
                // Check browser Notification API permission status
                let permission = 'default';
                
                if ('Notification' in window) {
                    permission = Notification.permission;
                }
                
                console.log('Current notification permission:', permission);
                console.log('Stored Player ID:', storedPlayerId);
                console.log('Notifications enabled in DB:', notificationsEnabled);
                
                // Show settings if:
                // 1. Browser permission is granted AND
                // 2. Either notifications are enabled in DB OR we have a stored Player ID
                if (permission === 'granted' && (notificationsEnabled || storedPlayerId)) {
                    showNotificationSettings();
                } else {
                    showNotificationPrompt();
                }
            } catch (error) {
                console.error('Error checking notification permission:', error);
                // Show prompt anyway to let user try
                showNotificationPrompt();
            }
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
            console.log('🔔 Requesting notification permission...');
            
            // Check browser support
            if (!('Notification' in window)) {
                alert('This browser does not support notifications');
                return;
            }
            
            try {
                // Native browser permission first
                console.log('📱 Requesting native permission...');
                const permission = await Notification.requestPermission();
                console.log('✅ Permission result:', permission);
                
                if (permission !== 'granted') {
                    alert('Notification permission denied.');
                    return;
                }
                
                // Subscribe to OneSignal after permission granted
                console.log('🎯 Subscribing to OneSignal...');
                
                if (!window.OneSignal) {
                    console.warn('⚠️ Waiting for OneSignal...');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
                
                if (window.OneSignal?.User?.PushSubscription) {
                    await window.OneSignal.User.PushSubscription.optIn();
                    console.log('✅ OneSignal subscription successful');
                    
                    // Get user ID for saving to database (id is a property, not a promise)
                    const userId = window.OneSignal.User.PushSubscription.id;
                    console.log('OneSignal User ID:', userId);
                    
                    // Save notification enabled status to database
                    await saveNotificationStatus(true, userId);
                } else {
                    console.error('⚠️ OneSignal PushSubscription not available');
                    throw new Error('OneSignal is not properly initialized. Please refresh the page and try again.');
                }
                
                // Update UI
                showNotificationSettings();
                console.log('🎉 Notifications enabled!');
                alert('✅ Notifications enabled! You will now receive medication reminders on this device.');
                
            } catch (error) {
                console.error('❌ Permission request failed:', error);
                // Check if error is session-related
                if (error.message.includes('Session expired') || error.message.includes('log in')) {
                    alert('⚠️ Your session has expired. Please log in again.');
                    window.location.href = '/login.php';
                } else {
                    alert('Failed to enable notifications: ' + error.message);
                }
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
                    // Check if error is session-related
                    if (error.message.includes('Session expired') || error.message.includes('log in')) {
                        alert('⚠️ Your session has expired. Please log in again.');
                        window.location.href = '/login.php';
                    } else {
                        alert('Failed to disable notifications. Please try again.');
                    }
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
                body: formData,
                credentials: 'include'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('Session expired. Please log in again.');
                }
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
                        body: formData,
                        credentials: 'include'
                    });
                    
                    if (response.ok) {
                        console.log('Settings auto-saved');
                    } else if (response.status === 401) {
                        alert('⚠️ Your session has expired. Please log in again.');
                        window.location.href = '/login.php';
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
                    body: formData,
                    credentials: 'include'
                });
                
                if (response.ok) {
                    alert('✅ Preferences saved successfully!');
                } else if (response.status === 401) {
                    alert('⚠️ Your session has expired. Please log in again.');
                    window.location.href = '/login.php';
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
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
