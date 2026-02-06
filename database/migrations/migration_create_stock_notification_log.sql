-- Migration: Create stock_notification_log table
-- Purpose: Track when low stock notifications are sent to prevent spam
-- Date: 2026-02-06

CREATE TABLE IF NOT EXISTS stock_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stock_level INT NOT NULL,
    threshold INT NOT NULL,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for efficient queries
CREATE INDEX IF NOT EXISTS idx_stock_notification_medication ON stock_notification_log(medication_id);
CREATE INDEX IF NOT EXISTS idx_stock_notification_user ON stock_notification_log(user_id);
CREATE INDEX IF NOT EXISTS idx_stock_notification_sent_at ON stock_notification_log(notification_sent_at);
