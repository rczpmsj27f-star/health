-- Add last_login field to users table
ALTER TABLE users ADD COLUMN last_login DATETIME NULL DEFAULT NULL;
