-- Migration: Add PRN (as needed) schedule support
-- Created: 2026-02-03
-- Description: Adds support for PRN (as and when needed) medication scheduling

-- Modify medication_schedules table to support PRN scheduling
ALTER TABLE medication_schedules
ADD COLUMN IF NOT EXISTS is_prn BOOLEAN DEFAULT FALSE COMMENT 'Whether this medication is taken as and when needed (PRN)';

-- Create index for PRN queries
CREATE INDEX IF NOT EXISTS idx_schedules_prn ON medication_schedules(is_prn);
