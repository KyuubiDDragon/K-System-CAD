-- =====================================================
-- Migration: Reset All Grid Positions
-- Description: Resets all grid positions to NULL for clean recalculation
--              This ensures no overlapping positions
-- Date: 2025-10-15
-- Author: K-Systems
-- =====================================================

-- Reset all grid positions to NULL
UPDATE kdd_cheatsheet_categories
SET grid_x = NULL, grid_y = NULL, grid_w = NULL, grid_h = NULL;
