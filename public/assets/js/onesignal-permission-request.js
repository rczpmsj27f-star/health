/**
 * OneSignal Permission Request for Authenticated Users
 * This script requests notification permissions ONLY after the user has logged in
 * Prevents the permission prompt from appearing on the login page
 */

(function() {
    'use strict';
    
    console.log('🔔 OneSignal Permission Request: Checking if running in Capacitor...');
    
    // Only run in Capacitor environment (native app)
    if (typeof window.Capacitor === 'undefined' || !window.Capacitor.isNativePlatform()) {
        console.log('ℹ️ Not running in Capacitor - skipping OneSignal permission request');
        return;
    }
    
    console.log('📱 Running in Capacitor - will request notification permissions for authenticated user');
    
    // Function to request OneSignal notification permissions
    async function requestOneSignalPermissions() {
        try {
            // Check if OneSignal is available
            if (typeof window.OneSignal === 'undefined') {
                console.log('⚠️ OneSignal not available - waiting for it to load...');
                // Wait a bit and try again
                setTimeout(requestOneSignalPermissions, 500);
                return;
            }
            
            console.log('✅ OneSignal detected - requesting notification permissions');
            
            // Request permission using OneSignal Notifications API
            if (window.OneSignal.Notifications && typeof window.OneSignal.Notifications.requestPermission === 'function') {
                const accepted = await window.OneSignal.Notifications.requestPermission();
                
                if (accepted) {
                    console.log('✅ Notification permission granted by user');
                } else {
                    console.log('⚠️ Notification permission denied by user');
                }
            } else {
                console.log('ℹ️ OneSignal.Notifications.requestPermission not available - may already be initialized');
            }
            
        } catch (error) {
            console.error('❌ Error requesting OneSignal permissions:', error);
        }
    }
    
    // Wait for DOM to be ready before requesting permissions
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure OneSignal SDK is fully loaded
            setTimeout(requestOneSignalPermissions, 1000);
        });
    } else {
        // DOM already ready
        setTimeout(requestOneSignalPermissions, 1000);
    }
    
})();
