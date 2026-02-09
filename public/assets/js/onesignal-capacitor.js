// OneSignal Capacitor - Minimal No-Op Implementation
// This file provides a minimal interface for OneSignal without calling any initialization methods.
// The native Cordova plugin (onesignal-cordova-plugin v5.3.0) handles push notifications 
// automatically in the background without requiring JavaScript initialization.
//
// IMPORTANT: Calling OneSignal.initialize() triggers the Cordova plugin to inject the Web SDK,
// which overwrites the native plugin and causes conflicts. This minimal implementation prevents
// that by not calling any OneSignal methods.

console.log('📱 OneSignal Capacitor: Using native plugin only (no JavaScript initialization)');
console.log('ℹ️ The native Cordova plugin handles push notifications automatically in the background');
console.log('ℹ️ No OneSignal methods will be called from JavaScript to prevent Web SDK injection');

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
        console.log('ℹ️ OneSignalCapacitor.requestPermission() called - no action taken (native plugin handles everything)');
        return Promise.resolve(null);
    }
};