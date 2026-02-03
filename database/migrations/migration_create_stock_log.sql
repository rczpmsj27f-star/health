-- Migration: Create medication stock log table for tracking stock changes
-- Date: 2026-02-03

CREATE TABLE IF NOT EXISTS medication_stock_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity_change INT NOT NULL,
    change_type ENUM('add', 'remove') NOT NULL,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    INDEX idx_medication_id (medication_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
