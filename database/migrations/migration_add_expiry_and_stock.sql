-- Migration: Add stock tracking
-- Created: 2026-02-03
-- Description: Adds support for tracking medication current stock levels
-- Note: Expiry date uses existing end_date column

-- Modify medications table to support stock tracking
ALTER TABLE medications
ADD COLUMN IF NOT EXISTS current_stock INT DEFAULT NULL COMMENT 'Current quantity/stock on hand';

ALTER TABLE medications
ADD COLUMN IF NOT EXISTS stock_updated_at DATETIME DEFAULT NULL COMMENT 'Last time stock was updated';

