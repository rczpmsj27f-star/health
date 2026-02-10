<?php

/**
 * BiometricAuth - Server-side WebAuthn credential management
 * Handles registration and verification of biometric credentials (Face ID/Touch ID)
 */
class BiometricAuth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Generate a cryptographically secure challenge
     */
    public function generateChallenge(): string {
        return base64_encode(random_bytes(32));
    }

    /**
     * Store challenge for a session
     */
    public function storeChallenge(string $challenge): void {
        $_SESSION['biometric_challenge'] = $challenge;
        $_SESSION['biometric_challenge_time'] = time();
    }

    /**
     * Verify and consume challenge
     */
    public function verifyChallenge(string $clientChallenge): bool {
        if (!isset($_SESSION['biometric_challenge']) || !isset($_SESSION['biometric_challenge_time'])) {
            return false;
        }

        // Challenge expires after 2 minutes
        if (time() - $_SESSION['biometric_challenge_time'] > 120) {
            unset($_SESSION['biometric_challenge'], $_SESSION['biometric_challenge_time']);
            return false;
        }

        $valid = $_SESSION['biometric_challenge'] === $clientChallenge;
        
        // Consume the challenge (one-time use)
        unset($_SESSION['biometric_challenge'], $_SESSION['biometric_challenge_time']);
        
        return $valid;
    }

    /**
     * Register a new biometric credential for a user
     */
    public function registerCredential(int $userId, array $credential): bool {
        try {
            // Extract credential data
            $credentialId = $credential['id'] ?? null;
            $publicKey = $credential['publicKey'] ?? null;
            
            if (!$credentialId || !$publicKey) {
                return false;
            }

            // Store credential in database
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET biometric_enabled = 1,
                    biometric_credential_id = ?,
                    biometric_public_key = ?,
                    biometric_counter = 0
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $credentialId,
                $publicKey,
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Biometric registration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify a biometric authentication assertion
     * 
     * NOTE: This is a simplified implementation for demonstration purposes.
     * In a production environment, you should perform full WebAuthn verification including:
     * - Signature verification using the stored public key
     * - Challenge validation (ensure it matches the one sent to client)
     * - Origin validation (ensure request comes from expected domain)
     * - Authenticator data validation
     * - Counter validation (replay attack prevention)
     * 
     * For full implementation, consider using a WebAuthn library like:
     * - web-auth/webauthn-lib (PHP)
     * - lbuchs/WebAuthn (PHP)
     */
    public function verifyAssertion(string $credentialId, array $assertion, int &$userId = null): bool {
        try {
            // Find user by credential ID
            $stmt = $this->pdo->prepare("
                SELECT id, biometric_public_key, biometric_counter 
                FROM users 
                WHERE biometric_credential_id = ? 
                AND biometric_enabled = 1
            ");
            $stmt->execute([$credentialId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            // TODO: Implement full WebAuthn verification here
            // For now, we're doing basic validation to demonstrate the flow
            // This should be replaced with proper cryptographic verification
            
            $userId = $user['id'];
            
            // Update counter and last login time
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET biometric_counter = biometric_counter + 1,
                    last_biometric_login = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            return true;
        } catch (PDOException $e) {
            error_log("Biometric verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has biometric authentication enabled
     */
    public function isEnabled(int $userId): bool {
        $stmt = $this->pdo->prepare("
            SELECT biometric_enabled 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return !empty($result['biometric_enabled']);
    }

    /**
     * Get user's biometric credential info
     */
    public function getCredential(int $userId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT biometric_credential_id, biometric_public_key 
            FROM users 
            WHERE id = ? AND biometric_enabled = 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['biometric_credential_id']) {
            return null;
        }
        
        return [
            'credentialId' => $result['biometric_credential_id'],
            'publicKey' => $result['biometric_public_key']
        ];
    }

    /**
     * Disable biometric authentication for a user
     */
    public function disable(int $userId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET biometric_enabled = 0,
                    biometric_credential_id = NULL,
                    biometric_public_key = NULL,
                    biometric_counter = 0
                WHERE id = ?
            ");
            
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Biometric disable error: " . $e->getMessage());
            return false;
        }
    }
}
