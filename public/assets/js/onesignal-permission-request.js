/**
 * OneSignal Permission Request for Authenticated Users
 * This script requests notification permissions ONLY after the user has logged in
 * Prevents the permission prompt from appearing on the login page
 * Only prompts once when permission is 'default' - respects user's decision (granted/denied)
 * Handles unsupported environments gracefully
 */

(function() {
    'use strict';
    
    // Configuration constants
    const ONESIGNAL_LOAD_RETRY_DELAY = 500; // ms - delay between retries when waiting for OneSignal to load
    const ONESIGNAL_INIT_DELAY = 1000; // ms - delay before requesting permissions to ensure SDK is fully loaded
    const PERMISSION_CHECK_KEY = 'onesignal_permission_requested'; // LocalStorage key to track if we've already prompted
    
    console.log('🔔 OneSignal Permission Request: Checking environment...');
    
    // Check if push notifications are supported in this environment
    function isPushSupported() {
        // Check for Notification API support
        if (!('Notification' in window)) {
            console.log('⚠️ Push notifications not supported: Notification API not available');
            return false;
        }
        
        // Check for ServiceWorker support (required for web push)
        if (!('serviceWorker' in navigator)) {
            console.log('⚠️ Push notifications not supported: ServiceWorker API not available');
            return false;
        }
        
        return true;
    }
    
    // Prefer Capacitor environment, but also support web browsers
    const isCapacitor = typeof window.Capacitor !== 'undefined' && window.Capacitor.isNativePlatform();
    
    if (isCapacitor) {
        console.log('📱 Running in Capacitor native environment');
    } else if (isPushSupported()) {
        console.log('🌐 Running in web browser with push notification support');
    } else {
        console.log('⚠️ Push notifications not supported in this environment');
        // Don't return - let OneSignal handle it gracefully
    }
    
    console.log('🔔 Checking notification permission status');
    
    // Function to request OneSignal notification permissions
    async function requestOneSignalPermissions() {
        try {
            // FIRST: Check if we're in Capacitor native environment
            if (isCapacitor) {
                console.log('📱 Using native Capacitor plugin for permission request');
                
                // Wait for OneSignalCapacitor to be available
                if (!window.OneSignalCapacitor || typeof window.OneSignalCapacitor.requestPermission !== 'function') {
                    console.log('⚠️ OneSignalCapacitor not ready - retrying...');
                    setTimeout(requestOneSignalPermissions, ONESIGNAL_LOAD_RETRY_DELAY);
                    return;
                }
                
                // First check current permission status
                try {
                    const status = await window.OneSignalCapacitor.checkPermission();
                    if (status && status.permission === true) {
                        console.log('✅ Native notification permission already granted');
                        localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                        return;
                    } else if (status && status.permission === false) {
                        console.log('⚠️ Native notification permission denied - checking if already prompted');
                        const alreadyPrompted = localStorage.getItem(PERMISSION_CHECK_KEY);
                        if (alreadyPrompted === 'true') {
                            console.log('ℹ️ User previously denied - not prompting again');
                            return;
                        }
                        // If not prompted before, fall through to request
                    }
                } catch (e) {
                    console.log('ℹ️ Could not check permission status:', e);
                }
                
                // Check if we've already prompted
                const alreadyPrompted = localStorage.getItem(PERMISSION_CHECK_KEY);
                if (alreadyPrompted === 'true') {
                    console.log('ℹ️ User has already been prompted for notification permissions - skipping');
                    return;
                }
                
                // Request permission via native plugin
                try {
                    const accepted = await window.OneSignalCapacitor.requestPermission();
                    
                    // Only mark as prompted if we got a definitive answer (not null)
                    // null means the plugin wasn't available, so we shouldn't prevent future prompts
                    if (accepted !== null) {
                        localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                        if (accepted) {
                            console.log('✅ Notification permission granted via native plugin');
                        } else {
                            console.log('⚠️ Notification permission denied via native plugin');
                        }
                    } else {
                        console.log('❌ PushPermission plugin not available - will retry on next page load');
                    }
                } catch (e) {
                    console.error('❌ Error requesting native permission:', e);
                    // Don't mark as prompted on error - allow retry on next page load
                }
                return; // Don't fall through to web SDK path
            }
            
            // Check if OneSignal is available
            if (typeof window.OneSignal === 'undefined') {
                console.log('⚠️ OneSignal not available - waiting for it to load...');
                // Wait a bit and try again
                setTimeout(requestOneSignalPermissions, ONESIGNAL_LOAD_RETRY_DELAY);
                return;
            }
            
            console.log('✅ OneSignal detected - checking permission status');
            
            // Check current permission status using native Notification API when available
            if (typeof Notification !== 'undefined' && Notification.permission) {
                const permission = Notification.permission;
                console.log('🔔 Current notification permission status:', permission);
                
                if (permission === 'granted') {
                    console.log('✅ Notification permission already granted');
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                } else if (permission === 'denied') {
                    console.log('⚠️ Notification permission denied by browser');
                    // Check if we've already prompted the user about this
                    const alreadyPrompted = localStorage.getItem(PERMISSION_CHECK_KEY);
                    if (alreadyPrompted === 'true') {
                        console.log('ℹ️ User previously denied - not prompting again');
                        return;
                    }
                    // Mark as prompted so we don't keep trying
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                }
                // If permission === 'default', continue to request permission below
            }
            
            // Check if we've already prompted the user
            const alreadyPrompted = localStorage.getItem(PERMISSION_CHECK_KEY);
            
            if (alreadyPrompted === 'true') {
                console.log('ℹ️ User has already been prompted for notification permissions - skipping');
                return;
            }
            
            // Additional check for OneSignal's permission status
            if (window.OneSignal.Notifications && window.OneSignal.Notifications.permission) {
                const hasPermission = window.OneSignal.Notifications.permission;
                
                if (hasPermission) {
                    console.log('✅ OneSignal notification permission already granted');
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                }
            }
            
            // Check if user has opted out via OneSignal
            if (window.OneSignal.User && window.OneSignal.User.PushSubscription) {
                const pushSubscription = window.OneSignal.User.PushSubscription;
                
                // If user has opted out or denied, don't prompt again
                if (pushSubscription.optedIn === false) {
                    console.log('⚠️ User has opted out of notifications - will not prompt again');
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                }
            }
            
            // Only request if permission is 'default' (not yet decided)
            console.log('🔔 Requesting notification permissions from user...');
            
            // Request permission using OneSignal Notifications API
            if (window.OneSignal.Notifications && typeof window.OneSignal.Notifications.requestPermission === 'function') {
                const accepted = await window.OneSignal.Notifications.requestPermission();
                
                // Mark that we've prompted the user
                localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                
                if (accepted) {
                    console.log('✅ Notification permission granted by user');
                } else {
                    console.log('⚠️ Notification permission denied by user - will not prompt again');
                }
            } else {
                console.log('ℹ️ OneSignal.Notifications.requestPermission not available - may already be initialized');
                // If push is not supported, surface a message
                if (!isPushSupported()) {
                    console.log('⚠️ Push notifications are not supported in this environment');
                    // Could surface a UI message here if needed
                }
            }
            
        } catch (error) {
            console.error('❌ Error requesting OneSignal permissions:', error);
            // Check if error is due to unsupported environment
            if (!isPushSupported()) {
                console.log('⚠️ Push notifications are not supported in this environment');
            }
            // Mark as prompted even on error to avoid infinite loops
            localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
        }
    }
    
    // Wait for DOM to be ready before requesting permissions
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure OneSignal SDK is fully loaded
            setTimeout(requestOneSignalPermissions, ONESIGNAL_INIT_DELAY);
        });
    } else {
        // DOM already ready
        setTimeout(requestOneSignalPermissions, ONESIGNAL_INIT_DELAY);
    }
    
})();
