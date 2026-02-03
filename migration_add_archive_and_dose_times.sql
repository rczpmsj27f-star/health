-- Migration script to add archive functionality and dose times
-- Run this SQL script to add the necessary columns and tables

-- Add archived and end_date columns to medications table
ALTER TABLE medications 
ADD COLUMN archived TINYINT(1) DEFAULT 0,
ADD COLUMN end_date DATETIME NULL;

-- Create medication_dose_times table for storing specific dose times
CREATE TABLE IF NOT EXISTS medication_dose_times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    dose_number INT NOT NULL,
    dose_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE
);
