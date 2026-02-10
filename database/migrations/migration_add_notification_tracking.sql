-- Migration: Add notification tracking to medication_logs
-- Purpose: Track which notifications have been sent to prevent duplicate notifications
-- Date: 2026-02-10

-- Add columns to track when each type of notification was sent
ALTER TABLE medication_logs 
ADD COLUMN notification_sent_at_time DATETIME NULL COMMENT 'When at-time notification was sent',
ADD COLUMN notification_sent_10min DATETIME NULL COMMENT 'When 10-min reminder was sent',
ADD COLUMN notification_sent_20min DATETIME NULL COMMENT 'When 20-min reminder was sent',
ADD COLUMN notification_sent_30min DATETIME NULL COMMENT 'When 30-min reminder was sent',
ADD COLUMN notification_sent_60min DATETIME NULL COMMENT 'When 60-min reminder was sent';

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_notification_sent_at_time ON medication_logs(notification_sent_at_time);
CREATE INDEX IF NOT EXISTS idx_notification_sent_10min ON medication_logs(notification_sent_10min);
CREATE INDEX IF NOT EXISTS idx_notification_sent_20min ON medication_logs(notification_sent_20min);
CREATE INDEX IF NOT EXISTS idx_notification_sent_30min ON medication_logs(notification_sent_30min);
CREATE INDEX IF NOT EXISTS idx_notification_sent_60min ON medication_logs(notification_sent_60min);
