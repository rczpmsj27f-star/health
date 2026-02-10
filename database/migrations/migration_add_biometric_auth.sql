-- Add Biometric Authentication columns to users table
-- Supports Face ID/Touch ID authentication via WebAuthn
ALTER TABLE users 
ADD COLUMN biometric_enabled TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN biometric_credential_id VARCHAR(255) NULL,
ADD COLUMN biometric_public_key TEXT NULL,
ADD COLUMN biometric_counter INT NOT NULL DEFAULT 0,
ADD COLUMN last_biometric_login DATETIME NULL;
