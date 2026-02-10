/**
 * Biometric Authentication Module
 * Handles Face ID/Touch ID authentication using WebAuthn API
 */

const BiometricAuth = {
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
        if (!this.isSupported()) {
            return false;
        }
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch (e) {
            console.error('Error checking platform authenticator:', e);
            return false;
        }
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
     * Register a new biometric credential
     */
    register: async function(username, userId, password) {
        try {
            // Check if supported
            if (!await this.isPlatformAuthenticatorAvailable()) {
                throw new Error('Biometric authentication is not available on this device');
            }

            // Get challenge from server
            const challenge = await this.getChallenge();

            // Encode user ID properly
            const userIdBuffer = new Uint8Array(16);
            const userIdView = new DataView(userIdBuffer.buffer);
            userIdView.setUint32(0, userId, true); // Little-endian

            // Create credential options
            const publicKeyCredentialCreationOptions = {
                challenge: challenge,
                rp: {
                    name: "Health Tracker",
                    id: window.location.hostname
                },
                user: {
                    id: userIdBuffer,
                    name: username,
                    displayName: username
                },
                pubKeyCredParams: [
                    { alg: -7, type: "public-key" },  // ES256
                    { alg: -257, type: "public-key" } // RS256
                ],
                authenticatorSelection: {
                    authenticatorAttachment: "platform", // Use device biometrics
                    userVerification: "required",
                    requireResidentKey: false
                },
                timeout: 60000,
                attestation: "none"
            };

            // Create credential
            const credential = await navigator.credentials.create({
                publicKey: publicKeyCredentialCreationOptions
            });

            if (!credential) {
                throw new Error('Failed to create credential');
            }

            // Prepare credential data for server
            const credentialData = {
                id: this.arrayBufferToBase64(credential.rawId),
                publicKey: this.arrayBufferToBase64(credential.response.getPublicKey()),
                type: credential.type
            };

            // Send to server for registration
            const response = await fetch('/api/biometric/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credential: credentialData,
                    password: password
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Registration failed');
            }

            return result;
        } catch (error) {
            console.error('Biometric registration error:', error);
            throw error;
        }
    },

    /**
     * Authenticate using biometric
     */
    authenticate: async function(credentialId) {
        try {
            // Check if supported
            if (!await this.isPlatformAuthenticatorAvailable()) {
                throw new Error('Biometric authentication is not available on this device');
            }

            // Get challenge from server
            const challenge = await this.getChallenge();

            // Prepare credential ID
            const credentialIdBuffer = this.base64ToArrayBuffer(credentialId);

            // Create authentication options
            const publicKeyCredentialRequestOptions = {
                challenge: challenge,
                allowCredentials: [{
                    id: credentialIdBuffer,
                    type: 'public-key',
                    transports: ['internal']
                }],
                userVerification: "required",
                timeout: 60000
            };

            // Get credential
            const assertion = await navigator.credentials.get({
                publicKey: publicKeyCredentialRequestOptions
            });

            if (!assertion) {
                throw new Error('Authentication failed');
            }

            // Prepare assertion data for server
            const assertionData = {
                credentialId: credentialId,
                assertion: {
                    authenticatorData: this.arrayBufferToBase64(assertion.response.authenticatorData),
                    clientDataJSON: this.arrayBufferToBase64(assertion.response.clientDataJSON),
                    signature: this.arrayBufferToBase64(assertion.response.signature)
                }
            };

            // Send to server for verification
            const response = await fetch('/api/biometric/authenticate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(assertionData)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Authentication failed');
            }

            return result;
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
            const response = await fetch('/api/biometric/disable.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Failed to disable biometric authentication');
            }
            
            return result;
        } catch (error) {
            console.error('Error disabling biometric:', error);
            throw error;
        }
    }
};

// Make available globally
if (typeof window !== 'undefined') {
    window.BiometricAuth = BiometricAuth;
}
