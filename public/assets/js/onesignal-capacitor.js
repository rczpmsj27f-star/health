// OneSignal Capacitor - Native Bridge Implementation
// Uses custom PushPermissionPlugin to request iOS notification permissions

(function() {

console.log('📱 OneSignal Capacitor: Initializing native bridge');

// Check if running in Capacitor
function isCapacitor() {
    return typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
}

// Internal state for Player ID tracking
let _playerIdResolve = null;
let _playerIdResolved = false;
let _playerIdPromise = new Promise(resolve => {
    _playerIdResolve = resolve;
});
let _playerIdListenerRegistered = false;

// Internal helper to resolve the shared promise exactly once
function _resolvePlayerId(id) {
    if (!_playerIdResolved) {
        _playerIdResolved = true;
        _playerIdResolve(id);
    }
}

/**
 * Reset player ID tracking state so that a fresh subscription can be
 * obtained after the user disables and re-enables notifications.
 * Creates a new promise that will resolve with the next available ID.
 */
function resetPlayerIdTracking() {
    _playerIdResolved = false;
    _playerIdPromise = new Promise(resolve => { _playerIdResolve = resolve; });
    // Expose the fresh promise on the public interface
    if (window.OneSignalCapacitor) {
        window.OneSignalCapacitor.playerIdPromise = _playerIdPromise;
    }
}

/**
 * Wait for OneSignal Player ID to become available.
 * Polls window.OneSignal.User.PushSubscription.id until it is set or
 * the optional maxWaitMs timeout expires.
 *
 * @param {number} maxWaitMs  Maximum milliseconds to wait (default 15000)
 * @param {number} intervalMs Polling interval in milliseconds (default 500)
 * @returns {Promise<string|null>} Resolves with the Player ID or null on timeout
 */
async function waitForOneSignalPlayerId(maxWaitMs = 15000, intervalMs = 500) {
    // Register the subscription-change listener at most once so we react instantly
    if (!_playerIdListenerRegistered) {
        _playerIdListenerRegistered = true;
        try {
            window.OneSignal?.User?.PushSubscription?.addEventListener('change', (event) => {
                const id = event?.current?.id;
                if (id) {
                    console.log('✅ OneSignal Player ID received via change event:', id);
                    _resolvePlayerId(id);
                }
            });
        } catch (e) {
            // addEventListener may not be available on all SDK versions – ignore
        }
    }

    const startTime = Date.now();

    while (Date.now() - startTime < maxWaitMs) {
        const playerId = window.OneSignal?.User?.PushSubscription?.id;
        if (playerId) {
            console.log('✅ OneSignal Player ID available:', playerId);
            _resolvePlayerId(playerId);
            return playerId;
        }

        // Try native plugin (Capacitor context — window.OneSignal typically not available in native WebView)
        if (window.Capacitor?.Plugins?.PushPermission?.getSubscriptionId) {
            try {
                const result = await window.Capacitor.Plugins.PushPermission.getSubscriptionId();
                if (result?.subscriptionId) {
                    console.log('✅ OneSignal Player ID from native plugin:', result.subscriptionId);
                    _resolvePlayerId(result.subscriptionId);
                    return result.subscriptionId;
                }
            } catch (e) {
                // Plugin method may not be ready yet – continue polling
            }
        }

        await new Promise(resolve => setTimeout(resolve, intervalMs));
    }

    console.warn('⚠️ OneSignal Player ID not available after', maxWaitMs, 'ms');
    _resolvePlayerId(null);
    return null;
}

// Export interface for requesting push permissions
window.OneSignalCapacitor = {
    initialize: async () => {
        console.log('ℹ️ OneSignalCapacitor.initialize() called - native plugin handles initialization');
        return Promise.resolve();
    },

    /**
     * Wait for the OneSignal Player ID to be populated by the native SDK.
     * Returns the Player ID string or null if it is not available within the timeout.
     */
    waitForPlayerId: waitForOneSignalPlayerId,

    /** Promise that resolves with the Player ID (or null) when first available */
    playerIdPromise: _playerIdPromise,

    /** Reset tracking so re-enable flows can obtain a fresh Player ID */
    resetPlayerIdTracking: resetPlayerIdTracking,

    getPlayerId: async () => {
        console.log('ℹ️ OneSignalCapacitor.getPlayerId() called');
        // Return immediately if already available via JS SDK
        const immediate = window.OneSignal?.User?.PushSubscription?.id;
        if (immediate) return immediate;
        // Try native plugin (Capacitor context)
        if (window.Capacitor?.Plugins?.PushPermission?.getSubscriptionId) {
            try {
                const result = await window.Capacitor.Plugins.PushPermission.getSubscriptionId();
                if (result?.subscriptionId) return result.subscriptionId;
            } catch (e) {
                // fall through to polling
            }
        }
        return waitForOneSignalPlayerId(5000);
    },
    
    requestPermission: async () => {
        console.log('📱 OneSignalCapacitor.requestPermission() called');
        
        if (!isCapacitor()) {
            console.log('⚠️ Not running in Capacitor - cannot request native permissions');
            return null;
        }
        
        try {
            // Check if Capacitor and its Plugins are available
            if (!window.Capacitor || !window.Capacitor.Plugins) {
                console.error('❌ Capacitor or Capacitor.Plugins not available');
                return null;
            }
            
            // Use our custom Capacitor plugin
            const { PushPermission } = window.Capacitor.Plugins;
            
            if (PushPermission && typeof PushPermission.requestPermission === 'function') {
                console.log('✅ Calling native PushPermission.requestPermission()...');
                const result = await PushPermission.requestPermission();
                console.log('✅ Permission result:', result);
                
                // Safely extract accepted value with fallback
                return result && typeof result.accepted === 'boolean' ? result.accepted : null;
            } else {
                console.error('❌ PushPermission plugin not available');
                console.error('Note: The plugin files may need to be added to Xcode. See IOS_PUSH_PLUGIN_SETUP.md for instructions.');
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
            // Check if Capacitor and its Plugins are available
            if (!window.Capacitor || !window.Capacitor.Plugins) {
                console.error('❌ Capacitor or Capacitor.Plugins not available');
                return { permission: false };
            }
            
            const { PushPermission } = window.Capacitor.Plugins;
            
            if (PushPermission && typeof PushPermission.checkPermission === 'function') {
                const result = await PushPermission.checkPermission();
                console.log('✅ Permission status:', result);
                
                // Ensure result has expected structure
                return result && typeof result === 'object' ? result : { permission: false };
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

// Kick off passive polling so playerIdPromise resolves automatically
// once the SDK populates the subscription (no external call needed).
if (typeof window !== 'undefined') {
    waitForOneSignalPlayerId();
}

})();