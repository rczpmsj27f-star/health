// OneSignal Capacitor - Minimal No-Op Implementation
// This file provides a minimal interface for OneSignal without calling any initialization methods.
// The native Cordova plugin (onesignal-cordova-plugin v5.3.0) handles push notifications 
// automatically in the background without requiring JavaScript initialization.
//
// IMPORTANT: Calling OneSignal.initialize() triggers the Cordova plugin to inject the Web SDK,
// which overwrites the native plugin and causes conflicts. This minimal implementation prevents
// that by not calling any OneSignal methods.
//
// AUTHENTICATION CHECK: This script only runs on authenticated pages (loaded in dashboard.php)
// The dashboard.php file already checks for $_SESSION['user_id'], ensuring notification prompts
// only appear after login.

console.log('📱 OneSignal Capacitor: Using native plugin only (no JavaScript initialization)');
console.log('ℹ️ The native Cordova plugin handles push notifications automatically in the background');
console.log('ℹ️ No OneSignal methods will be called from JavaScript to prevent Web SDK injection');
console.log('✅ Script loaded on authenticated page - user is logged in');

// Export minimal compatible interface for backward compatibility
// Any code that references window.OneSignalCapacitor will still work
window.OneSignalCapacitor = {
    initialize: async () => {
        console.log('ℹ️ OneSignalCapacitor.initialize() called - no action taken (native plugin handles everything)');
        return Promise.resolve();
    },
    getPlayerId: async () => {
        console.log('ℹ️ OneSignalCapacitor.getPlayerId() called - no action taken (native plugin handles everything)');
        return Promise.resolve(null);
    },
    requestPermission: async () => {
        console.log('📱 OneSignalCapacitor.requestPermission() called');
        
        // Check if OneSignal native SDK is available
        if (typeof window.OneSignal !== 'undefined' && window.OneSignal.Notifications) {
            try {
                console.log('✅ OneSignal.Notifications found - requesting permission...');
                const result = await window.OneSignal.Notifications.requestPermission(true);
                console.log('✅ Permission request result:', result);
                return result;
            } catch (error) {
                console.error('❌ Error requesting permission:', error);
                return null;
            }
        } else {
            console.warn('⚠️ OneSignal.Notifications not available - native plugin may not be loaded');
            return null;
        }
    }
};