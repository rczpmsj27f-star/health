// OneSignal Capacitor - Native Bridge Implementation
// Uses custom PushPermissionPlugin to request iOS notification permissions

console.log('📱 OneSignal Capacitor: Initializing native bridge');

// Check if running in Capacitor
function isCapacitor() {
    return typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
}

// Export interface for requesting push permissions
window.OneSignalCapacitor = {
    initialize: async () => {
        console.log('ℹ️ OneSignalCapacitor.initialize() called - native plugin handles initialization');
        return Promise.resolve();
    },
    
    getPlayerId: async () => {
        console.log('ℹ️ OneSignalCapacitor.getPlayerId() called');
        return Promise.resolve(null);
    },
    
    requestPermission: async () => {
        console.log('📱 OneSignalCapacitor.requestPermission() called');
        
        if (!isCapacitor()) {
            console.log('⚠️ Not running in Capacitor - cannot request native permissions');
            return null;
        }
        
        try {
            // Use our custom Capacitor plugin
            const { PushPermission } = window.Capacitor.Plugins;
            
            if (PushPermission && typeof PushPermission.requestPermission === 'function') {
                console.log('✅ Calling native PushPermission.requestPermission()...');
                const result = await PushPermission.requestPermission();
                console.log('✅ Permission result:', result);
                return result.accepted;
            } else {
                console.error('❌ PushPermission plugin not available');
                return null;
            }
        } catch (error) {
            console.error('❌ Error requesting permission:', error);
            throw error;
        }
    },
    
    checkPermission: async () => {
        console.log('📱 OneSignalCapacitor.checkPermission() called');
        
        if (!isCapacitor()) {
            return { permission: false };
        }
        
        try {
            const { PushPermission } = window.Capacitor.Plugins;
            
            if (PushPermission && typeof PushPermission.checkPermission === 'function') {
                const result = await PushPermission.checkPermission();
                console.log('✅ Permission status:', result);
                return result;
            } else {
                return { permission: false };
            }
        } catch (error) {
            console.error('❌ Error checking permission:', error);
            return { permission: false };
        }
    }
};

console.log('✅ OneSignalCapacitor bridge ready');