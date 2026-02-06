-- Migration: Add special timing options for once-daily medications
-- Created: 2026-02-06
-- Description: Adds support for special timing options (before bed, on waking, with meal)
--              and custom instructions for medications

-- Add special timing columns to medication_schedules table
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS special_timing VARCHAR(20) NULL COMMENT 'Special timing option: on_waking, before_bed, with_meal' AFTER times_per_day,
ADD COLUMN IF NOT EXISTS custom_instructions TEXT NULL COMMENT 'Custom timing instructions' AFTER special_timing;

-- Create index for special timing queries
CREATE INDEX IF NOT EXISTS idx_schedules_special_timing ON medication_schedules(special_timing);
