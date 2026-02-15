-- =====================================================
-- Migration: Fix Inconsistent Grid Positions
-- Description: Sets all grid positions to NULL where they are incomplete
--              This forces recalculation of positions on next load
-- Date: 2025-10-14
-- Author: K-Systems
-- =====================================================

-- Find and fix categories where grid positions are incomplete
-- (e.g., grid_x is NULL but grid_y has a value)
UPDATE kdd_cheatsheet_categories
SET grid_x = NULL, grid_y = NULL, grid_w = NULL, grid_h = NULL
WHERE
    (grid_x IS NULL AND (grid_y IS NOT NULL OR grid_w IS NOT NULL OR grid_h IS NOT NULL))
    OR (grid_y IS NULL AND (grid_x IS NOT NULL OR grid_w IS NOT NULL OR grid_h IS NOT NULL))
    OR (grid_w IS NULL AND (grid_x IS NOT NULL OR grid_y IS NOT NULL OR grid_h IS NOT NULL))
    OR (grid_h IS NULL AND (grid_x IS NOT NULL OR grid_y IS NOT NULL OR grid_w IS NOT NULL));
