-- Add Two-Factor Authentication columns to users table
ALTER TABLE users 
ADD COLUMN two_factor_secret VARCHAR(32) NULL,
ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN two_factor_backup_codes TEXT NULL;
