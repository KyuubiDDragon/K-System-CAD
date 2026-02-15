-- Migration: Rename blackboard type from 'admin' to 'all'
-- Date: 2025-10-29
-- Description: Updates all blackboard entries with type 'admin' to 'all'

-- Update all blackboard entries
UPDATE kdd_blackboard
SET blackboard = 'all'
WHERE blackboard = 'admin';

-- Note: This will rename all 'admin' board entries to 'all'
-- The 'all' type represents the general notice board (Schwarzes Brett)
