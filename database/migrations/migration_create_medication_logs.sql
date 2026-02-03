-- Migration: Create medication logs table for tracking medication adherence
-- Date: 2026-02-03

CREATE TABLE IF NOT EXISTS medication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    user_id INT NOT NULL,
    scheduled_date_time DATETIME NOT NULL,
    status ENUM('pending', 'taken', 'skipped') NOT NULL DEFAULT 'pending',
    taken_at DATETIME NULL,
    skipped_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    INDEX idx_medication_id (medication_id),
    INDEX idx_user_id (user_id),
    INDEX idx_scheduled_date_time (scheduled_date_time),
    INDEX idx_status (status)
);
