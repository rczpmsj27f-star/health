-- Migration: Add archive and dose times functionality
-- Created: 2026-02-03
-- Description: Adds support for archiving medications and tracking dose times

-- Modify medications table to support archive functionality
ALTER TABLE medications
ADD COLUMN IF NOT EXISTS end_date DATE DEFAULT NULL COMMENT 'Date when medication was discontinued/archived',
ADD COLUMN IF NOT EXISTS archived BOOLEAN DEFAULT FALSE COMMENT 'Whether medication is archived',
ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the medication was archived';

-- Modify medication_schedules table to support dose times
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS dose_times JSON DEFAULT NULL COMMENT 'Array of specific dose times throughout the day',
ADD COLUMN IF NOT EXISTS last_taken_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Last time this dose was taken',
ADD COLUMN IF NOT EXISTS next_due_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Next scheduled dose time';

-- Create indexes for better performance on archive queries
CREATE INDEX IF NOT EXISTS idx_medications_archived ON medications(archived, end_date);
CREATE INDEX IF NOT EXISTS idx_medications_user_archived ON medications(user_id, archived);

-- Create indexes for dose time queries
CREATE INDEX IF NOT EXISTS idx_schedules_next_due ON medication_schedules(next_due_at);
CREATE INDEX IF NOT EXISTS idx_schedules_last_taken ON medication_schedules(last_taken_at);
