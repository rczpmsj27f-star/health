-- Migration: Update medication icons to remove UK-invalid two-tone icons
-- Purpose: Migrate medications using diagonal two-tone icons to vertical split icons
-- Date: 2026-02-06

-- Migrate pill-two-tone to pill-half (diagonal to vertical split)
UPDATE medications 
SET icon = 'pill-half' 
WHERE icon = 'pill-two-tone';

-- Migrate capsule-two-tone to capsule-half (diagonal to vertical split)
UPDATE medications 
SET icon = 'capsule-half' 
WHERE icon = 'capsule-two-tone';

-- Migrate capsule (diagonal two-tone) to capsule-half (vertical split)
UPDATE medications 
SET icon = 'capsule-half' 
WHERE icon = 'capsule';
