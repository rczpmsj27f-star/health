// OneSignal Capacitor Initialization
// This file handles OneSignal setup for Capacitor iOS apps using the native plugin
// No CDN dependency - uses window.OneSignal from onesignal-cordova-plugin v5.3.0

const ONESIGNAL_APP_ID = '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b';

// Helper function to check if running on native platform
function isNativePlatform() {
    return window.Capacitor && 
           typeof window.Capacitor.isNativePlatform === 'function' && 
           window.Capacitor.isNativePlatform();
}

async function initializeOneSignalCapacitor() {
    console.log('🔔 Initializing OneSignal for Capacitor...');
    
    // Check if we're in a native Capacitor app
    if (!isNativePlatform()) {
        console.log('ℹ️ Not running in native Capacitor - skipping native OneSignal setup');
        return;
    }
    
    try {
        // Access OneSignal directly from window (Cordova Plugin v5)
        const OneSignal = window.OneSignal;
        
        if (!OneSignal) {
            console.error('❌ OneSignal plugin not found at window.OneSignal');
            return;
        }
        
        console.log('📱 Found OneSignal plugin, initializing...');
        
        // Initialize OneSignal with App ID as string (Cordova Plugin v5 API)
        await OneSignal.initialize(ONESIGNAL_APP_ID);
        
        console.log('✅ OneSignal initialized for Capacitor with App ID:', ONESIGNAL_APP_ID);
        
        // Set up notification handlers before requesting permission
        await setupOneSignalHandlers(OneSignal);
        
        // Automatically request notification permission on initialization
        await requestNotificationPermission(OneSignal);
        
        // Get and log player ID for debugging
        await getAndStorePlayerId(OneSignal);
        
    } catch (error) {
        console.error('❌ Failed to initialize OneSignal:', error);
    }
}

// Set up handlers for incoming notifications
async function setupOneSignalHandlers(OneSignal) {
    try {
        console.log('🔧 Setting up OneSignal notification handlers...');
        
        // Handle notification received (app in foreground)
        await OneSignal.addListener('pushNotificationReceived', (notification) => {
            console.log('🔔 Notification received:', notification);
            
            // Display in-app notification
            if (notification && notification.notification) {
                const { title, body } = notification.notification;
                showNotificationAlert(title, body);
            }
        });
        
        // Handle notification opened (user tapped notification)
        await OneSignal.addListener('pushNotificationActionPerformed', (result) => {
            console.log('🔔 Notification opened:', result);
            
            // Handle notification click - navigate to specific page if data.link exists
            if (result && result.notification && result.notification.data) {
                const { data } = result.notification;
                if (data.link) {
                    console.log('🔗 Navigating to:', data.link);
                    window.location.href = data.link;
                }
            }
        });
        
        console.log('✅ Notification handlers set up successfully');
    } catch (error) {
        console.error('❌ Failed to set up notification handlers:', error);
    }
}

// Request notification permissions automatically
async function requestNotificationPermission(OneSignal) {
    try {
        console.log('📱 Requesting notification permission...');
        
        const result = await OneSignal.requestPermission();
        
        if (result && result.hasPrompted !== undefined) {
            console.log('✅ Permission requested - hasPrompted:', result.hasPrompted);
        } else {
            console.log('✅ Permission request completed');
        }
        
        return result;
    } catch (error) {
        console.error('❌ Permission request failed:', error);
        return null;
    }
}

// Get and store the player ID
async function getAndStorePlayerId(OneSignal) {
    try {
        console.log('📱 Getting OneSignal player ID...');
        
        const result = await OneSignal.getIds();
        
        if (result && result.userId) {
            console.log('✅ OneSignal Player ID received');
            console.log('✅ Push Token received');
            
            // Store player ID globally for other scripts to use
            window.oneSignalPlayerId = result.userId;
            window.oneSignalPushToken = result.pushToken;
            
            return result;
        } else {
            console.log('⚠️ No player ID available yet');
            return null;
        }
    } catch (error) {
        console.error('❌ Failed to get player ID:', error);
        return null;
    }
}

// Show alert for notifications (simple implementation)
function showNotificationAlert(title, body) {
    const message = body ? `${title}\n\n${body}` : title;
    
    // Log to console instead of using AlertModal
    // Note: AlertModal is defined in confirm-modal.js which is loaded globally
    // Referencing it here caused "duplicate variable" errors in bundled scripts
    console.log('📬 Notification:', title, body);
}

// Export for use in other scripts
window.OneSignalCapacitor = {
    initialize: initializeOneSignalCapacitor,
    getPlayerId: async () => {
        if (isNativePlatform() && window.OneSignal) {
            return await getAndStorePlayerId(window.OneSignal);
        }
        return null;
    },
    requestPermission: async () => {
        if (isNativePlatform() && window.OneSignal) {
            return await requestNotificationPermission(window.OneSignal);
        }
        return null;
    }
};

// Auto-initialize when Capacitor is ready
// Wait for Capacitor to be fully loaded with retry mechanism
let initRetryCount = 0;
const RETRY_DELAY_MS = 100;
const MAX_INIT_TIMEOUT_MS = 5000; // 5 seconds max
const MAX_RETRIES = MAX_INIT_TIMEOUT_MS / RETRY_DELAY_MS;

function initializeWhenReady() {
    // Check if Capacitor is available and has native platform
    if (isNativePlatform()) {
        console.log('🚀 Capacitor ready - initializing OneSignal...');
        initializeOneSignalCapacitor();
    } else {
        // Retry after a short delay (Capacitor might still be loading)
        initRetryCount++;
        if (initRetryCount < MAX_RETRIES) {
            setTimeout(initializeWhenReady, RETRY_DELAY_MS);
        } else {
            console.log('⚠️ Capacitor native platform not detected after', (initRetryCount * RETRY_DELAY_MS), 'ms - OneSignal initialization skipped');
        }
    }
}

// Start initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeWhenReady);
} else {
    // DOM already loaded
    initializeWhenReady();
}