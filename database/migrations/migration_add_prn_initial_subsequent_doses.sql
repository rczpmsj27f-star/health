-- Migration: Add initial and subsequent dose fields for PRN medications
-- Created: 2026-02-05
-- Description: Adds columns to distinguish between initial dose and subsequent doses for PRN medications

-- Add initial_dose and subsequent_dose columns to medication_schedules table
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS initial_dose INT NULL COMMENT 'Number of tablets/doses for the first administration of PRN medication',
ADD COLUMN IF NOT EXISTS subsequent_dose INT NULL COMMENT 'Number of tablets/doses for follow-up administrations of PRN medication';
