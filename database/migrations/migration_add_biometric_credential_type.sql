-- Add credential_type and biometric_type columns to users table
-- Supports tracking the type of biometric credential stored (webauthn, native_biometric, passkey)
ALTER TABLE users
ADD COLUMN credential_type VARCHAR(20) DEFAULT 'webauthn' AFTER biometric_counter,
ADD COLUMN biometric_type INT NOT NULL DEFAULT 0 AFTER credential_type;
