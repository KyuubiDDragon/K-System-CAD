-- =====================================================
-- Migration: Add Grid Layout Support to Cheatsheet
-- Description: Adds grid position and size fields for vue-grid-layout
-- Date: 2025-01-21
-- Author: K-Systems
-- =====================================================

-- Note: Database connection is already established by migration system
-- No USE statement needed - causes permission errors

-- =====================================================
-- Add grid layout columns to cheatsheet_categories
-- =====================================================
ALTER TABLE kdd_cheatsheet_categories
ADD COLUMN grid_x INT DEFAULT NULL COMMENT 'Grid X position for vue-grid-layout',
ADD COLUMN grid_y INT DEFAULT NULL COMMENT 'Grid Y position for vue-grid-layout',
ADD COLUMN grid_w INT DEFAULT NULL COMMENT 'Grid width (columns) for vue-grid-layout',
ADD COLUMN grid_h INT DEFAULT NULL COMMENT 'Grid height (rows) for vue-grid-layout';

-- =====================================================
-- Add show_headers and auto_height options if not exist
-- =====================================================
-- These columns may already exist from previous updates, so we check first
SET @dbname = DATABASE();
SET @tablename = 'kdd_cheatsheet_categories';

-- Add show_headers column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'show_headers'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE kdd_cheatsheet_categories ADD COLUMN show_headers BOOLEAN DEFAULT TRUE COMMENT "Show table headers for table content types"',
    'SELECT "Column show_headers already exists" AS Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add auto_height column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'auto_height'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE kdd_cheatsheet_categories ADD COLUMN auto_height BOOLEAN DEFAULT FALSE COMMENT "Table height adapts to number of entries"',
    'SELECT "Column auto_height already exists" AS Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Add indexes for better grid layout queries
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_grid_position ON kdd_cheatsheet_categories(grid_x, grid_y);
