-- Migration: Add PRN medication constraints
-- Created: 2026-02-03
-- Description: Adds columns for PRN medication dosage limits and time constraints

-- Add PRN constraint columns to medication_schedules table
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS max_doses_per_day INT NULL COMMENT 'Maximum doses allowed in 24 hours for PRN medications',
ADD COLUMN IF NOT EXISTS min_hours_between_doses DECIMAL(4,2) NULL COMMENT 'Minimum hours required between PRN doses';

-- Allow frequency_type to be NULL for PRN medications
ALTER TABLE medication_schedules MODIFY COLUMN frequency_type VARCHAR(50) NULL;
