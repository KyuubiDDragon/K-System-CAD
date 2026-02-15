-- Migration: Add is_absent field to kdd_employee table for dispatch absence tracking
-- Date: 2025-11-29
-- Description: Allows marking employees as temporarily absent in the dispatch board

ALTER TABLE `kdd_employee`
ADD COLUMN `is_absent` tinyint(1) NOT NULL DEFAULT 0 AFTER `dispatch_id`;
