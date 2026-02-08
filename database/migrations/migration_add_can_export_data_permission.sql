-- Migration: Add can_export_data permission to user_link_permissions table
-- This allows linked users to export each other's medication data

ALTER TABLE user_link_permissions 
ADD COLUMN can_export_data TINYINT(1) DEFAULT 0 AFTER receive_nudges;
