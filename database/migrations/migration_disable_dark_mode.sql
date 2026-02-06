-- Migration: Temporarily disable dark mode for all users
-- Purpose: Force light mode while dark mode implementation is incomplete
-- Date: 2026-02-06
-- DARK MODE TEMPORARILY DISABLED - causing usability issues (dark text on dark backgrounds)

-- Set all users to light mode
UPDATE user_preferences SET theme_mode = 'light' WHERE theme_mode IS NOT NULL;

-- Set default to light mode for new users
ALTER TABLE user_preferences ALTER COLUMN theme_mode SET DEFAULT 'light';

-- Note: This is a temporary fix. When dark mode is properly implemented with correct
-- text colors and contrast, this migration can be reverted and users can choose their
-- preferred theme again.
