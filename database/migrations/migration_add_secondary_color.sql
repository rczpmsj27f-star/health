-- Migration: Add secondary_color column for two-tone medications
-- Purpose: Support dual-color medication icons (e.g., two-tone capsules)
-- Date: 2026-02-06

-- Add secondary_color column to medications table
ALTER TABLE medications 
ADD COLUMN IF NOT EXISTS secondary_color VARCHAR(7) DEFAULT NULL COMMENT 'Optional hex color code for two-tone medications';

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_medications_secondary_color ON medications(secondary_color);
