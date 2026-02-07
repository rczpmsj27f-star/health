-- Migration: Add late logging reason column
-- Created: 2026-02-07
-- Description: Adds late_logging_reason column to medication_logs table to track why medications were logged late

-- Add late logging reason column
ALTER TABLE medication_logs 
ADD COLUMN IF NOT EXISTS late_logging_reason VARCHAR(255) NULL 
AFTER skipped_reason;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_medication_logs_late_reason 
ON medication_logs(late_logging_reason);
