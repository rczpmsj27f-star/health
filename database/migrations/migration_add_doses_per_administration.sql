-- Migration: Add doses per administration for PRN medications
-- Created: 2026-02-03
-- Description: Adds column to specify how many tablets/doses can be taken per administration

-- Add doses_per_administration column to medication_schedules table
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS doses_per_administration INT DEFAULT 1 COMMENT 'Number of tablets/doses to take per administration for PRN medications';
