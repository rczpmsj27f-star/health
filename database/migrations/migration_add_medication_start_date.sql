-- Migration: Add medication start date
-- Purpose: Track when user actually started taking the medication
-- Date: 2026-02-08

ALTER TABLE medications 
ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT NULL
AFTER current_stock;

-- Create index for start date queries
CREATE INDEX IF NOT EXISTS idx_medications_start_date ON medications(start_date);
