/**
 * OneSignal Permission Request for Authenticated Users
 * This script requests notification permissions ONLY after the user has logged in
 * Prevents the permission prompt from appearing on the login page
 * Only prompts once - respects user's decision (granted/denied)
 */

(function() {
    'use strict';
    
    // Configuration constants
    const ONESIGNAL_LOAD_RETRY_DELAY = 500; // ms - delay between retries when waiting for OneSignal to load
    const ONESIGNAL_INIT_DELAY = 1000; // ms - delay before requesting permissions to ensure SDK is fully loaded
    const PERMISSION_CHECK_KEY = 'onesignal_permission_requested'; // LocalStorage key to track if we've already prompted
    
    console.log('🔔 OneSignal Permission Request: Checking if running in Capacitor...');
    
    // Only run in Capacitor environment (native app)
    if (typeof window.Capacitor === 'undefined' || !window.Capacitor.isNativePlatform()) {
        console.log('ℹ️ Not running in Capacitor - skipping OneSignal permission request');
        return;
    }
    
    console.log('📱 Running in Capacitor - checking notification permission status');
    
    // Function to request OneSignal notification permissions
    async function requestOneSignalPermissions() {
        try {
            // Check if OneSignal is available
            if (typeof window.OneSignal === 'undefined') {
                console.log('⚠️ OneSignal not available - waiting for it to load...');
                // Wait a bit and try again
                setTimeout(requestOneSignalPermissions, ONESIGNAL_LOAD_RETRY_DELAY);
                return;
            }
            
            console.log('✅ OneSignal detected - checking permission status');
            
            // Check if we've already prompted the user
            const alreadyPrompted = localStorage.getItem(PERMISSION_CHECK_KEY);
            
            if (alreadyPrompted === 'true') {
                console.log('ℹ️ User has already been prompted for notification permissions - skipping');
                return;
            }
            
            // Check current permission status
            if (window.OneSignal.Notifications && window.OneSignal.Notifications.permission) {
                const hasPermission = window.OneSignal.Notifications.permission;
                
                if (hasPermission) {
                    console.log('✅ Notification permission already granted');
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                }
            }
            
            // Check if permission was explicitly denied (check if we have the API)
            if (window.OneSignal.User && window.OneSignal.User.PushSubscription) {
                const pushSubscription = window.OneSignal.User.PushSubscription;
                
                // If user has opted out or denied, don't prompt again
                if (pushSubscription.optedIn === false) {
                    console.log('⚠️ User has denied notification permissions - will not prompt again');
                    localStorage.setItem(PERMISSION_CHECK_KEY, 'true');
                    return;
                }
            }
            
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
            }
            
        } catch (error) {
            console.error('❌ Error requesting OneSignal permissions:', error);
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
