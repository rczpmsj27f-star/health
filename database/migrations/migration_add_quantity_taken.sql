-- Migration: Add quantity_taken column to medication_logs table
-- Date: 2026-02-03
-- Purpose: Track the number of tablets/doses taken for PRN medications

ALTER TABLE medication_logs 
ADD COLUMN quantity_taken INT DEFAULT 1 AFTER status;
