-- Migration: Add medication appearance columns
-- Purpose: Allow users to customize medication icons and colors
-- Date: 2026-02-06

-- Add icon and color columns to medications table
ALTER TABLE medications 
ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'pill' COMMENT 'Icon identifier (pill, liquid, injection, inhaler, etc.)',
ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT '#5b21b6' COMMENT 'Hex color code for medication';

-- Create index for faster icon/color lookups
CREATE INDEX IF NOT EXISTS idx_medications_appearance ON medications(icon, color);
