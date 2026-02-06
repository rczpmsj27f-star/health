-- Migration: Create user_preferences table
-- Purpose: Store user preferences including dark mode, time format, and notification settings
-- Date: 2026-02-06

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dark_mode BOOLEAN DEFAULT FALSE,
    time_format VARCHAR(10) DEFAULT '12h',
    stock_notification_threshold INT DEFAULT 10,
    stock_notification_enabled BOOLEAN DEFAULT TRUE,
    notify_linked_users BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON user_preferences(user_id);
