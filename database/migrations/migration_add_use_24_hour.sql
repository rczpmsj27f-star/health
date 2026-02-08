-- Migration: Add use_24_hour column to user_preferences
-- Purpose: Allow users to choose between 12-hour and 24-hour time format
-- Date: 2026-02-08

ALTER TABLE user_preferences 
ADD COLUMN IF NOT EXISTS use_24_hour BOOLEAN DEFAULT FALSE
AFTER time_format;
