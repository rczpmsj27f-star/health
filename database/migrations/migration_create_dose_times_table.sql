-- Migration: Create medication_dose_times table
-- Created: 2026-02-03
-- Description: Creates the medication_dose_times table to store specific dose times for daily medications

CREATE TABLE IF NOT EXISTS medication_dose_times (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medication_id INT UNSIGNED NOT NULL,
    dose_number INT NOT NULL,
    dose_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    INDEX idx_medication_dose_times (medication_id, dose_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
