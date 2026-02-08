-- Migration: Create dropdown options system
-- Purpose: Replace hard-coded dropdown values with database-driven options
-- Date: 2026-02-08

-- Create dropdown categories table
CREATE TABLE IF NOT EXISTS dropdown_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique key for the dropdown category',
    category_name VARCHAR(255) NOT NULL COMMENT 'Human-readable name',
    description TEXT NULL COMMENT 'What this dropdown is used for',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_key (category_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create dropdown options table
CREATE TABLE IF NOT EXISTS dropdown_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    option_value VARCHAR(255) NOT NULL COMMENT 'The value stored in database (same as label)',
    display_order INT DEFAULT 0 COMMENT 'Sort order for display',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'FALSE hides option without deleting (data integrity)',
    icon_emoji VARCHAR(10) NULL COMMENT 'Optional emoji icon',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES dropdown_categories(id) ON DELETE CASCADE,
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert dropdown categories
INSERT INTO dropdown_categories (category_key, category_name, description) VALUES
('special_instructions', 'Special Instructions', 'Medication instructions (take with food, etc.)'),
('special_timing', 'Special Timing', 'When to take medication (on waking, before bed, etc.)'),
('dose_units', 'Dose Units', 'Units of measurement for medication doses'),
('skipped_reasons', 'Skipped Reasons', 'Reasons for skipping a scheduled medication'),
('late_logging_reasons', 'Late Logging Reasons', 'Reasons for logging medication late');

-- Insert special instructions options
INSERT INTO dropdown_options (category_id, option_value, display_order, icon_emoji) 
SELECT id, 'Take with water', 1, '💧' FROM dropdown_categories WHERE category_key = 'special_instructions'
UNION ALL
SELECT id, 'Take on empty stomach', 2, '🍽️' FROM dropdown_categories WHERE category_key = 'special_instructions'
UNION ALL
SELECT id, 'Take with food', 3, '🍴' FROM dropdown_categories WHERE category_key = 'special_instructions'
UNION ALL
SELECT id, 'Do not crush or chew', 4, '💊' FROM dropdown_categories WHERE category_key = 'special_instructions';

-- Insert special timing options
INSERT INTO dropdown_options (category_id, option_value, display_order, icon_emoji)
SELECT id, 'On Waking', 1, '🌅' FROM dropdown_categories WHERE category_key = 'special_timing'
UNION ALL
SELECT id, 'Before Bed', 2, '🌙' FROM dropdown_categories WHERE category_key = 'special_timing'
UNION ALL
SELECT id, 'With Main Meal', 3, '🍽️' FROM dropdown_categories WHERE category_key = 'special_timing';

-- Insert dose units options
INSERT INTO dropdown_options (category_id, option_value, display_order)
SELECT id, 'mg (milligrams)', 1 FROM dropdown_categories WHERE category_key = 'dose_units'
UNION ALL
SELECT id, 'ml (milliliters)', 2 FROM dropdown_categories WHERE category_key = 'dose_units'
UNION ALL
SELECT id, 'tablet(s)', 3 FROM dropdown_categories WHERE category_key = 'dose_units'
UNION ALL
SELECT id, 'capsule(s)', 4 FROM dropdown_categories WHERE category_key = 'dose_units'
UNION ALL
SELECT id, 'g (grams)', 5 FROM dropdown_categories WHERE category_key = 'dose_units'
UNION ALL
SELECT id, 'mcg (micrograms)', 6 FROM dropdown_categories WHERE category_key = 'dose_units';

-- Insert skipped reasons options
INSERT INTO dropdown_options (category_id, option_value, display_order)
SELECT id, 'Unwell', 1 FROM dropdown_categories WHERE category_key = 'skipped_reasons'
UNION ALL
SELECT id, 'Forgot', 2 FROM dropdown_categories WHERE category_key = 'skipped_reasons'
UNION ALL
SELECT id, 'Did not have them with me', 3 FROM dropdown_categories WHERE category_key = 'skipped_reasons'
UNION ALL
SELECT id, 'Lost', 4 FROM dropdown_categories WHERE category_key = 'skipped_reasons'
UNION ALL
SELECT id, 'Side effects', 5 FROM dropdown_categories WHERE category_key = 'skipped_reasons'
UNION ALL
SELECT id, 'Other', 6 FROM dropdown_categories WHERE category_key = 'skipped_reasons';

-- Insert late logging reasons options
INSERT INTO dropdown_options (category_id, option_value, display_order)
SELECT id, 'Did not have phone with me', 1 FROM dropdown_categories WHERE category_key = 'late_logging_reasons'
UNION ALL
SELECT id, 'Forgot to log', 2 FROM dropdown_categories WHERE category_key = 'late_logging_reasons'
UNION ALL
SELECT id, 'Skipped and logged late', 3 FROM dropdown_categories WHERE category_key = 'late_logging_reasons'
UNION ALL
SELECT id, 'Other (please specify)', 4 FROM dropdown_categories WHERE category_key = 'late_logging_reasons';
