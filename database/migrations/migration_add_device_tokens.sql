-- Migration: Add device token fields for native iOS push notifications
-- Purpose: Store device tokens and platform info for native push via OneSignal
-- Date: 2026-02-08

-- Add device token columns to user_notification_settings table
ALTER TABLE user_notification_settings 
ADD COLUMN IF NOT EXISTS device_token VARCHAR(255) NULL COMMENT 'Native device push token (APNs for iOS)',
ADD COLUMN IF NOT EXISTS platform VARCHAR(50) NULL COMMENT 'Device platform (ios, android, web)',
ADD COLUMN IF NOT EXISTS device_id VARCHAR(255) NULL COMMENT 'Unique device identifier',
ADD COLUMN IF NOT EXISTS last_token_update TIMESTAMP NULL COMMENT 'Last time device token was updated';

-- Create index for device token lookups
CREATE INDEX IF NOT EXISTS idx_device_token ON user_notification_settings(device_token);
CREATE INDEX IF NOT EXISTS idx_device_id ON user_notification_settings(device_id);
