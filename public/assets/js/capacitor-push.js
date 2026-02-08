/**
 * Capacitor Native Push Notifications Handler
 * Handles iOS native push notifications via Capacitor Push Notifications plugin
 */

// Check if running in native Capacitor environment
function isCapacitor() {
    return window.Capacitor && window.Capacitor.isNativePlatform();
}

// Initialize push notifications for native iOS
async function initializeNativePush() {
    if (!isCapacitor()) {
        console.log('Not running in Capacitor - skipping native push setup');
        return;
    }

    try {
        const { PushNotifications } = window.Capacitor.Plugins;
        
        console.log('Initializing native push notifications...');

        // Request permission to use push notifications
        // iOS will prompt user or use existing permissions
        let permStatus = await PushNotifications.checkPermissions();
        
        if (permStatus.receive === 'prompt') {
            permStatus = await PushNotifications.requestPermissions();
        }
        
        if (permStatus.receive !== 'granted') {
            console.warn('Push notification permission denied');
            return;
        }

        // Register with Apple Push Notification service (APNs)
        await PushNotifications.register();
        
        // Listen for registration success
        await PushNotifications.addListener('registration', async (token) => {
            console.log('Push registration success, token:', token.value);
            
            // Register device token with backend
            await registerDeviceToken(token.value);
        });

        // Listen for registration errors
        await PushNotifications.addListener('registrationError', (error) => {
            console.error('Push registration error:', error);
        });

        // Listen for push notifications received
        await PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Push notification received:', notification);
            
            // Show in-app notification if app is in foreground
            showInAppNotification(notification);
        });

        // Listen for push notification actions (user tapped notification)
        await PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('Push notification action performed:', notification);
            
            const data = notification.notification.data;
            
            // Handle notification actions based on data
            handleNotificationAction(data);
        });

        console.log('Native push notifications initialized successfully');
        
    } catch (error) {
        console.error('Error initializing native push:', error);
    }
}

// Register device token with backend API
async function registerDeviceToken(deviceToken) {
    try {
        // Get device info
        const { Device } = window.Capacitor.Plugins;
        const deviceInfo = await Device.getInfo();
        const deviceId = await Device.getId();
        
        const response = await fetch('/api/push-devices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register',
                device_token: deviceToken,
                platform: deviceInfo.platform, // 'ios'
                device_id: deviceId.identifier,
                device_model: deviceInfo.model,
                os_version: deviceInfo.osVersion
            })
        });

        const result = await response.json();
        
        if (result.success) {
            console.log('Device token registered successfully');
            
            // Update UI to show registration status
            updatePushRegistrationStatus(true);
        } else {
            console.error('Failed to register device token:', result.error);
        }
        
    } catch (error) {
        console.error('Error registering device token:', error);
    }
}

// Show in-app notification when received while app is in foreground
function showInAppNotification(notification) {
    const title = notification.title || 'Notification';
    const body = notification.body || '';
    
    // Create a simple in-app notification banner
    const banner = document.createElement('div');
    banner.className = 'push-notification-banner';
    banner.style.cssText = `
        position: fixed;
        top: 60px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        color: #333;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 90%;
        width: 400px;
        animation: slideDown 0.3s ease-out;
    `;
    
    banner.innerHTML = `
        <div style="font-weight: 600; margin-bottom: 4px;">${escapeHtml(title)}</div>
        <div style="font-size: 14px; color: #666;">${escapeHtml(body)}</div>
    `;
    
    document.body.appendChild(banner);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        banner.style.animation = 'slideUp 0.3s ease-in';
        setTimeout(() => banner.remove(), 300);
    }, 5000);
    
    // Allow tap to dismiss
    banner.addEventListener('click', () => {
        banner.style.animation = 'slideUp 0.3s ease-in';
        setTimeout(() => banner.remove(), 300);
        
        // Handle the notification action
        if (notification.data) {
            handleNotificationAction(notification.data);
        }
    });
}

// Handle notification action when user taps
function handleNotificationAction(data) {
    if (!data) return;
    
    // Handle different notification types
    if (data.type === 'medication_reminder' && data.medication_id) {
        // Navigate to medication detail or log page
        window.location.href = `/modules/medications/view.php?id=${data.medication_id}`;
    } else if (data.url) {
        // Navigate to custom URL
        window.location.href = data.url;
    } else {
        // Default: go to notifications page
        window.location.href = '/modules/settings/notifications.php';
    }
}

// Update UI to show registration status
function updatePushRegistrationStatus(isRegistered) {
    const statusElement = document.getElementById('push-registration-status');
    if (statusElement) {
        if (isRegistered) {
            statusElement.innerHTML = '✅ iOS Push Notifications Enabled';
            statusElement.style.color = '#10b981';
        } else {
            statusElement.innerHTML = '⚠️ iOS Push Notifications Not Registered';
            statusElement.style.color = '#f59e0b';
        }
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            transform: translate(-50%, -100%);
            opacity: 0;
        }
        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            transform: translate(-50%, 0);
            opacity: 1;
        }
        to {
            transform: translate(-50%, -100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on page load if running in Capacitor
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNativePush);
} else {
    initializeNativePush();
}

// Export functions for use in other scripts
window.CapacitorPush = {
    initialize: initializeNativePush,
    isCapacitor: isCapacitor,
    registerDeviceToken: registerDeviceToken
};
