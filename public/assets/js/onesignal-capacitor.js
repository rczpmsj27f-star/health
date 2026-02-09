// OneSignal Capacitor Initialization
// This file handles OneSignal setup for Capacitor iOS apps

const ONESIGNAL_APP_ID = '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b';

async function initializeOneSignalCapacitor() {
    console.log('🔔 Initializing OneSignal for Capacitor...');
    
    // Check if we're in a Capacitor app
    if (typeof window.Capacitor === 'undefined') {
        console.log('ℹ️ Not running in Capacitor - skipping native OneSignal setup');
        return;
    }
    
    try {
        // Import OneSignal plugin
        const { OneSignal } = window.Capacitor.Plugins;
        
        if (!OneSignal) {
            console.error('❌ OneSignal plugin not found in Capacitor');
            return;
        }
        
        // Initialize OneSignal with App ID
        await OneSignal.initialize({
            appId: ONESIGNAL_APP_ID
        });
        
        console.log('✅ OneSignal initialized for Capacitor');
        
        // Set up notification handlers
        setupOneSignalHandlers();
        
        // Request notification permission
        await requestNotificationPermission();
        
    } catch (error) {
        console.error('❌ Failed to initialize OneSignal:', error);
    }
}

// Set up handlers for incoming notifications
function setupOneSignalHandlers() {
    const { OneSignal } = window.Capacitor.Plugins;
    
    // Handle notification received
    OneSignal.addListener('notification', (notification) => {
        console.log('🔔 Notification received:', notification);
        
        // Display notification in app
        const { title, body, data } = notification.notification;
        showNotificationAlert(title, body, data);
    });
    
    // Handle notification opened
    OneSignal.addListener('notificationOpened', (result) => {
        console.log('🔔 Notification opened:', result);
        const { data } = result.notification;
        
        // Handle notification click (navigate, etc.)
        if (data && data.link) {
            window.location.href = data.link;
        }
    });
}

// Request notification permissions
async function requestNotificationPermission() {
    try {
        const { OneSignal } = window.Capacitor.Plugins;
        
        console.log('📱 Requesting notification permission...');
        const permission = await OneSignal.requestPermission();
        
        console.log('✅ Permission result:', permission);
        return permission;
    } catch (error) {
        console.error('❌ Permission request failed:', error);
    }
}

// Show alert for notifications
function showNotificationAlert(title, body, data) {
    const message = body ? `${title}\n\n${body}` : title;
    alert(message);
}

// Get player ID for sending notifications
async function getOneSignalPlayerId() {
    try {
        const { OneSignal } = window.Capacitor.Plugins;
        
        const id = await OneSignal.getPlayerId();
        console.log('📱 OneSignal Player ID:', id);
        return id;
    } catch (error) {
        console.error('❌ Failed to get player ID:', error);
    }
}

// Export for use in other scripts
window.OneSignalCapacitor = {
    initialize: initializeOneSignalCapacitor,
    getPlayerId: getOneSignalPlayerId,
    requestPermission: requestNotificationPermission
};

// Auto-initialize when Capacitor is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.Capacitor) {
        window.Capacitor.ready.then(() => {
            initializeOneSignalCapacitor();
        });
    }
});