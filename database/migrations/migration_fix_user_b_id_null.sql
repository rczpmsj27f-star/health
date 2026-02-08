-- Fix user_b_id to allow NULL values
-- This ensures the column can be NULL for pending invitations
-- Run this if you're getting "Column 'user_b_id' cannot be null" errors

ALTER TABLE user_links MODIFY user_b_id INT NULL;
