-- Migration: Update theme mode from boolean to three-way toggle
-- Purpose: Convert dark_mode boolean to theme_mode ENUM with 'light', 'dark', 'device' options
-- Date: 2026-02-06

-- Add new theme_mode column
ALTER TABLE user_preferences 
ADD COLUMN theme_mode ENUM('light', 'dark', 'device') DEFAULT 'device' AFTER dark_mode;

-- Migrate existing dark_mode values to theme_mode
UPDATE user_preferences 
SET theme_mode = CASE 
    WHEN dark_mode = 1 THEN 'dark'
    WHEN dark_mode = 0 THEN 'light'
    ELSE 'device'
END;

-- Drop old dark_mode column
ALTER TABLE user_preferences 
DROP COLUMN dark_mode;
