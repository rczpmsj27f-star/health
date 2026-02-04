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
        let oneSignalReady = false;

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

                console.log('OneSignal SDK loaded, waiting for initialization...');
                
                // Wait for OneSignal to be fully ready before checking permissions
                // This is especially important for iOS Safari PWA
                await waitForOneSignalReady();
                
                // Check current notification permission
                checkNotificationPermission();
            } catch (error) {
                console.error('Failed to initialize OneSignal:', error);
            }
        }

        // Wait for OneSignal to be fully initialized
        // This ensures the SDK is ready before we attempt any operations
        // Uses recursive setTimeout to be consistent with other polling operations
        async function waitForOneSignalReady() {
            return new Promise((resolve) => {
                const maxWaitTime = 5000; // 5 seconds maximum wait
                const checkInterval = 100; // Check every 100ms
                const startTime = Date.now();

                const checkReady = () => {
                    const elapsedTime = Date.now() - startTime;
                    
                    // Try to access OneSignal methods to verify it's ready
                    if (window.OneSignal && typeof window.OneSignal.isPushNotificationsSupported === 'function') {
                        oneSignalReady = true;
                        console.log('OneSignal is fully ready after', elapsedTime, 'ms');
                        resolve();
                    } else if (elapsedTime >= maxWaitTime) {
                        console.warn('OneSignal initialization timeout - proceeding anyway');
                        oneSignalReady = true;
                        resolve();
                    } else {
                        // Continue checking
                        setTimeout(checkReady, checkInterval);
                    }
                };
                
                // Start checking
                checkReady();
            });
        }

        // Check notification permission status
        async function checkNotificationPermission() {
            try {
                // Use OneSignal API to check if push notifications are supported
                // This works better on iOS Safari than checking Notification API directly
                let isSupported = false;
                
                if (window.OneSignal && typeof window.OneSignal.isPushNotificationsSupported === 'function') {
                    await window.OneSignal.push(async function() {
                        isSupported = await window.OneSignal.isPushNotificationsSupported();
                    });
                }
                
                console.log('Push notifications supported:', isSupported);
                
                if (!isSupported) {
                    // For iOS, provide more helpful message
                    const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
                    if (isIOS) {
                        console.log('iOS detected - notifications require HTTPS and Safari 16.4+');
                    }
                    // Don't show error immediately - some browsers may still support it
                    // Let the user try to enable, and we'll show error if it fails
                }

                // Check current permission status using OneSignal API
                let permission = 'default';
                
                if (window.OneSignal) {
                    await window.OneSignal.push(async function() {
                        try {
                            permission = await window.OneSignal.getNotificationPermission();
                            console.log('Notification permission from OneSignal:', permission);
                        } catch (error) {
                            console.log('Could not get permission from OneSignal, falling back to Notification API');
                            // Fallback to browser Notification API if OneSignal method fails
                            if ('Notification' in window) {
                                permission = Notification.permission;
                            }
                        }
                    });
                }
                
                if (permission === 'granted' && notificationsEnabled) {
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
            console.log('Requesting notification permission...');
            
            // Check if running in standalone PWA mode with multiple detection methods
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                                 window.navigator.standalone === true ||
                                 document.referrer.includes('android-app://');
            
            const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
            
            console.log('Standalone detection:', {
                'matchMedia standalone': window.matchMedia('(display-mode: standalone)').matches,
                'navigator.standalone': window.navigator.standalone,
                'isStandalone final': isStandalone,
                'isIOS': isIOS
            });
            
            // On iOS, if OneSignal says push is supported, trust that over display-mode detection
            // because iOS can be quirky about when it reports standalone mode
            if (isIOS) {
                let pushSupported = false;
                
                try {
                    // Ensure OneSignal is available before checking
                    if (window.OneSignal) {
                        await window.OneSignal.push(async function() {
                            pushSupported = await window.OneSignal.isPushNotificationsSupported();
                        });
                    }
                    
                    console.log('OneSignal push supported:', pushSupported);
                    
                    // If OneSignal says push is supported on iOS, we're probably in the right context
                    // even if display-mode detection is flaky
                    if (pushSupported) {
                        console.log('OneSignal confirms push is supported, proceeding with permission request');
                        // Continue to permission request below, don't show "must use home screen" message
                    } else if (!isStandalone) {
                        // Only show the error if BOTH standalone detection fails AND OneSignal says not supported
                        alert('Push notifications require:\n\n' +
                              '1. Safari 16.4 or later\n' +
                              '2. Adding this site to your Home Screen (Add to Home Screen)\n' +
                              '3. Opening the app from the Home Screen icon\n\n' +
                              'Please ensure you meet these requirements and try again.');
                        return;
                    }
                } catch (error) {
                    console.error('Error checking OneSignal support:', error);
                    // If we can't check with OneSignal, fall back to standalone detection
                    if (!isStandalone) {
                        alert('Push notifications require:\n\n' +
                              '1. Safari 16.4 or later\n' +
                              '2. Adding this site to your Home Screen (Add to Home Screen)\n' +
                              '3. Opening the app from the Home Screen icon\n\n' +
                              'Please ensure you meet these requirements and try again.');
                        return;
                    }
                }
            }
            
            // Ensure OneSignal is ready before proceeding
            if (!oneSignalReady) {
                console.log('OneSignal not ready yet, waiting...');
                await waitForOneSignalReady();
            }
            
            if (!window.OneSignal) {
                alert('Notification system is not ready. Please refresh the page and try again.');
                return;
            }

            try {
                // Check if push notifications are supported
                // For iOS: already checked earlier in the function, this is for logging/debugging
                // For non-iOS: this check determines if we should show an error
                let isSupported = false;
                
                await window.OneSignal.push(async function() {
                    isSupported = await window.OneSignal.isPushNotificationsSupported();
                });
                
                console.log('Push notifications supported (verification):', isSupported);
                
                // For non-iOS browsers, check support and show error if not supported
                if (!isSupported && !isIOS) {
                    alert('Push notifications are not supported in this browser. Please try:\n\n' +
                          '1. Using a modern browser (Chrome, Firefox, Safari, Edge)\n' +
                          '2. Ensuring you are on HTTPS\n' +
                          '3. Checking your browser settings');
                    return;
                }
                // For iOS, we already handled the support check earlier in the function

                // Use OneSignal's native prompt method - this handles iOS Safari properly
                console.log('Showing OneSignal native prompt...');
                
                await window.OneSignal.push(async function() {
                    try {
                        // OneSignal's showNativePrompt handles browser differences automatically
                        await window.OneSignal.showNativePrompt();
                        console.log('Native prompt shown');
                    } catch (error) {
                        console.error('Error showing native prompt:', error);
                        throw error;
                    }
                });

                // Wait for permission to be processed
                // Poll for permission state change with timeout
                // On iOS this may take longer than other platforms
                // Use recursive setTimeout to avoid concurrency issues with async operations
                const maxWaitTime = 3000; // 3 seconds maximum wait
                const checkInterval = 200; // Check every 200ms
                const startTime = Date.now();
                
                const pollPermissionState = async () => {
                    try {
                        const elapsedTime = Date.now() - startTime;
                        let permission = 'default';
                        
                        await window.OneSignal.push(async function() {
                            permission = await window.OneSignal.getNotificationPermission();
                        });
                        
                        console.log('Checking permission state:', permission, 'elapsed:', elapsedTime);
                        
                        // Stop if permission changed or timeout
                        if (permission !== 'default' || elapsedTime >= maxWaitTime) {
                            // Get player ID only once after permission is granted
                            let playerId = null;
                            if (permission === 'granted') {
                                await window.OneSignal.push(async function() {
                                    playerId = await window.OneSignal.getUserId();
                                });
                            }
                            
                            await handlePermissionResult(permission, playerId);
                        } else {
                            // Continue polling
                            setTimeout(pollPermissionState, checkInterval);
                        }
                    } catch (error) {
                        console.error('Error checking permission state:', error);
                        alert('Error checking notification permission. Please try again.');
                    }
                };
                
                // Start polling
                pollPermissionState();
                
                // Helper function to handle the permission result
                async function handlePermissionResult(permission, playerId) {
                    if (permission === 'granted') {
                        console.log('Notification permission granted, Player ID:', playerId);
                        
                        // Save notification enabled status to database
                        await saveNotificationStatus(true, playerId);
                        
                        showNotificationSettings();
                        
                        // Show success message
                        alert('✅ Notifications enabled! You will now receive medication reminders on this device.');
                    } else if (permission === 'denied') {
                        alert('❌ Notification permission was denied.\n\n' +
                              'To enable notifications:\n' +
                              '1. Go to your browser/device settings\n' +
                              '2. Find this website in the notifications settings\n' +
                              '3. Enable notifications\n' +
                              '4. Return here and try again');
                    } else {
                        console.log('Permission still default/undecided');
                    }
                }
            } catch (error) {
                console.error('Failed to request notification permission:', error);
                
                // Provide helpful error messages based on the error
                let errorMessage = 'Failed to enable notifications.\n\n';
                
                const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
                if (isIOS) {
                    errorMessage += 'For iOS:\n' +
                                  '1. Ensure you are using Safari 16.4+\n' +
                                  '2. Add this site to Home Screen\n' +
                                  '3. Open from the Home Screen icon\n' +
                                  '4. Try enabling notifications again\n\n';
                }
                
                errorMessage += 'Error details: ' + error.message;
                
                alert(errorMessage);
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
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
</body>
</html>
