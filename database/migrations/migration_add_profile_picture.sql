-- Migration: Add profile picture support to users table
-- Created: 2026-02-06
-- Description: Adds profile_picture column to store user avatar/profile image URLs

-- Add profile_picture column to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL COMMENT 'URL or path to user profile picture' AFTER email;

-- Create index for faster lookups (optional, since it's nullable)
CREATE INDEX IF NOT EXISTS idx_users_profile_picture ON users(profile_picture);
