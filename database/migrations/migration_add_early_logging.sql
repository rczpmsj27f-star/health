-- Migration: Add early logging reason column
-- Created: 2026-02-07
-- Description: Adds early_logging_reason column to medication_logs table to track why medications were taken early

-- Add early logging reason column
ALTER TABLE medication_logs 
ADD COLUMN IF NOT EXISTS early_logging_reason VARCHAR(255) NULL 
AFTER late_logging_reason;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_medication_logs_early_reason 
ON medication_logs(early_logging_reason);
