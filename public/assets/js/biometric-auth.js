/**
 * Biometric Authentication Module
 * Handles Face ID/Touch ID authentication using Capacitor Native Biometric (for native apps) or WebAuthn API (for web)
 */

const BiometricAuth = {
    /**
     * Check if Capacitor Native Biometric is available (for iOS/Android apps)
     */
    isNativeBiometricAvailable: async function() {
        try {
            console.log('[BiometricAuth] Checking native biometric availability...');
            // Check if running in Capacitor app
            if (!window.Capacitor) {
                console.log('[BiometricAuth] Capacitor not detected');
                return { available: false, isNative: false };
            }
            if (typeof window.Capacitor.isNativePlatform !== 'function' || !window.Capacitor.isNativePlatform()) {
                console.log('[BiometricAuth] Not running on a native platform');
                return { available: false, isNative: false };
            }
            if (!window.Capacitor.Plugins || !window.Capacitor.Plugins.NativeBiometric) {
                console.log('[BiometricAuth] NativeBiometric plugin not loaded');
                return { available: false, isNative: false };
            }
            try {
                const result = await window.Capacitor.Plugins.NativeBiometric.isAvailable();
                console.log('[BiometricAuth] Native biometric check result:', result);
                return { available: !!result.isAvailable, biometryType: result.biometryType || null, isNative: true };
            } catch (pluginError) {
                console.error('[BiometricAuth] NativeBiometric.isAvailable() threw an error:', pluginError);
                return { available: false, isNative: false };
            }
        } catch (e) {
            console.error('[BiometricAuth] Error checking native biometric:', e);
        }
        return { available: false, isNative: false };
    },

    /**
     * Check if WebAuthn is supported in the current browser
     */
    isSupported: function() {
        return window.PublicKeyCredential !== undefined;
    },

    /**
     * Check if platform authenticator (Face ID/Touch ID) is available
     */
    isPlatformAuthenticatorAvailable: async function() {
        // First, check if native biometric is available (Capacitor app)
        const nativeCheck = await this.isNativeBiometricAvailable();
        if (nativeCheck.available) {
            console.log('[BiometricAuth] Native biometric available, type:', nativeCheck.biometryType);
            return nativeCheck;
        }

        // Fallback to WebAuthn for web
        if (!this.isSupported()) {
            console.log('[BiometricAuth] WebAuthn not supported in this browser');
            return { available: false, isNative: false };
        }
        try {
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            console.log('[BiometricAuth] WebAuthn platform authenticator available:', available);
            return { available, isNative: false };
        } catch (e) {
            console.error('[BiometricAuth] Error checking platform authenticator:', e);
            return { available: false, isNative: false };
        }
    },

    /**
     * Verify identity using native biometric (Capacitor)
     */
    verifyNative: async function(reason = 'Unlock your session') {
        try {
            console.log('[BiometricAuth] Starting native biometric verification...');
            if (!window.Capacitor || !window.Capacitor.Plugins || !window.Capacitor.Plugins.NativeBiometric) {
                throw new Error('Native biometric not available');
            }

            const result = await window.Capacitor.Plugins.NativeBiometric.verifyIdentity({
                reason: reason,
                title: 'Health Tracker',
                subtitle: 'Secure Access',
                description: 'Authenticate to continue',
                useFallback: true,
                fallbackTitle: 'Use Passcode'
            });

            console.log('[BiometricAuth] Native biometric verification succeeded');
            return { success: true, verified: true };
        } catch (error) {
            console.error('[BiometricAuth] Native biometric verification error:', error);
            // Provide user-friendly error message based on error code
            const userMessage = this._getUserFriendlyError(error);
            const enhancedError = new Error(userMessage);
            enhancedError.originalError = error;
            throw enhancedError;
        }
    },

    /**
     * Convert a biometric error to a user-friendly message
     */
    _getUserFriendlyError: function(error) {
        const msg = (error?.message ?? '').toLowerCase();
        const code = error?.code != null ? String(error.code) : '';
        if (msg.includes('cancel') || code === '10' || code === '-128') {
            return 'Authentication was cancelled.';
        }
        if (msg.includes('lockout') || code === '7' || code === '9') {
            return 'Biometric authentication is locked out due to too many failed attempts. Please use your passcode.';
        }
        if (msg.includes('not enrolled') || msg.includes('no biometrics') || code === '11') {
            return 'No biometric data is enrolled on this device. Please set up Face ID or Touch ID in Settings.';
        }
        if (msg.includes('not available') || msg.includes('unavailable')) {
            return 'Biometric authentication is not available on this device.';
        }
        return 'Biometric authentication failed. Please try again or use your passcode.';
    },

    /**
     * Convert base64 string to ArrayBuffer
     */
    base64ToArrayBuffer: function(base64) {
        const binary = atob(base64.replace(/-/g, '+').replace(/_/g, '/'));
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    },

    /**
     * Convert ArrayBuffer to base64 string
     */
    arrayBufferToBase64: function(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    },

    /**
     * Generate a random challenge
     */
    generateChallenge: function() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return array;
    },

    /**
     * Get a challenge from the server
     */
    getChallenge: async function() {
        try {
            const response = await fetch('/api/biometric/challenge.php');
            const result = await response.json();
            
            if (!result.success || !result.challenge) {
                throw new Error('Failed to get challenge from server');
            }
            
            return this.base64ToArrayBuffer(result.challenge);
        } catch (error) {
            console.error('Error getting challenge:', error);
            throw error;
        }
    },

    /**
     * Register biometric authentication using native Face ID/Touch ID
     * This stores credentials locally on THIS device only (no iCloud sync)
     */
    register: async function(username, userId, password) {
        try {
            const NativeBiometric = window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.NativeBiometric;

            if (!NativeBiometric) {
                throw new Error('Native biometric plugin is not available');
            }

            // Check if native biometric is available
            const result = await NativeBiometric.isAvailable();
            if (!result.isAvailable) {
                throw new Error('Biometric authentication is not available on this device');
            }

            // Verify the user's identity to confirm enrollment (triggers Face ID prompt)
            await NativeBiometric.verifyIdentity({
                reason: "Confirm your identity to enable Face ID login",
                title: "Enable Face ID",
                subtitle: "Authenticate to continue",
                description: "Use Face ID to sign in faster"
            });

            // Generate a secure credential ID
            const credentialId = this.generateCredentialId();

            // Store the credential securely in iOS Keychain using Native Biometric
            await NativeBiometric.setCredentials({
                username: username,
                password: credentialId,
                server: window.location.hostname
            });

            // Register this credential with the backend
            const response = await fetch('/api/biometric/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credential_id: credentialId,
                    user_id: userId,
                    password: password,
                    biometric_type: result.biometryType
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Registration failed');
            }

            return {
                success: true,
                credentialId: credentialId,
                biometryType: result.biometryType
            };

        } catch (error) {
            console.error('Biometric registration error:', error);
            throw error;
        }
    },

    /**
     * Authenticate using Face ID/Touch ID
     * Prompts for biometric, retrieves credential from Keychain, validates with server
     */
    authenticate: async function() {
        try {
            const NativeBiometric = window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.NativeBiometric;

            if (!NativeBiometric) {
                throw new Error('Native biometric plugin is not available');
            }

            // Check if native biometric is available
            const result = await NativeBiometric.isAvailable();
            if (!result.isAvailable) {
                throw new Error('Biometric authentication is not available');
            }

            // Prompt for Face ID/Touch ID
            await NativeBiometric.verifyIdentity({
                reason: "Sign in with Face ID",
                title: "Sign In",
                subtitle: "Authenticate to continue",
                description: ""
            });

            // Retrieve the stored credential from iOS Keychain
            const credentials = await NativeBiometric.getCredentials({
                server: window.location.hostname
            });

            if (!credentials || !credentials.password) {
                throw new Error('No biometric credentials found. Please enable Face ID in Settings.');
            }

            const credentialId = credentials.password;

            // Verify with backend
            const response = await fetch('/api/biometric/authenticate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credential_id: credentialId
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Authentication failed');
            }

            return data;

        } catch (error) {
            console.error('Biometric authentication error:', error);
            throw error;
        }
    },

    /**
     * Get biometric status for current user
     */
    getStatus: async function() {
        try {
            const response = await fetch('/api/biometric/status.php');
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Error getting biometric status:', error);
            throw error;
        }
    },

    /**
     * Disable biometric authentication
     */
    disable: async function() {
        try {
            const NativeBiometric = window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.NativeBiometric;

            if (NativeBiometric) {
                try {
                    // Get the credential ID from Keychain before deleting
                    const credentials = await NativeBiometric.getCredentials({
                        server: window.location.hostname
                    });

                    if (credentials && credentials.password) {
                        const credentialId = credentials.password;

                        // Delete from backend
                        await fetch('/api/biometric/disable.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                credential_id: credentialId
                            })
                        });
                    }

                    // Delete from iOS Keychain
                    await NativeBiometric.deleteCredentials({
                        server: window.location.hostname
                    });
                } catch (e) {
                    // If keychain operations fail, still attempt backend disable
                    await fetch('/api/biometric/disable.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                }
            } else {
                await fetch('/api/biometric/disable.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
            }

            return { success: true };
        } catch (error) {
            console.error('Error disabling biometric:', error);
            throw error;
        }
    },

    /**
     * Generate a unique credential ID
     */
    generateCredentialId: function() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    }
};

// Make available globally
if (typeof window !== 'undefined') {
    window.BiometricAuth = BiometricAuth;
}
