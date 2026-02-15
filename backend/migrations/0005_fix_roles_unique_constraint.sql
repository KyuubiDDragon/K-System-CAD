-- Migration: Fix kdd_roles unique constraint to allow same role name for different authorities
-- Date: 2025-01-06
-- Issue: Roles should be unique per authority, not globally

-- First, drop the existing unique constraint on name column
ALTER TABLE `kdd_roles` DROP INDEX `name`;

-- Add a composite unique constraint on (name, authority_id)
-- This allows the same role name to exist for different authorities
ALTER TABLE `kdd_roles` ADD UNIQUE KEY `unique_role_per_authority` (`name`, `authority_id`);