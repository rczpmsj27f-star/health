-- Migration: Create user_notification_settings table
-- Purpose: Store user notification preferences for medication reminders
-- Date: 2026-02-04

CREATE TABLE IF NOT EXISTS user_notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notifications_enabled BOOLEAN DEFAULT 0,
    notify_at_time BOOLEAN DEFAULT 1,
    notify_after_10min BOOLEAN DEFAULT 1,
    notify_after_20min BOOLEAN DEFAULT 1,
    notify_after_30min BOOLEAN DEFAULT 1,
    notify_after_60min BOOLEAN DEFAULT 0,
    onesignal_player_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
