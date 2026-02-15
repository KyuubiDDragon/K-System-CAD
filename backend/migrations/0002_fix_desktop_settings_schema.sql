-- Migration: Fix desktop settings schema for multi-tenant support
-- Date: 2025-01-30
-- Description: Fixes unique constraint and ensures widget_state column exists

-- Add widget_state column if it doesn't exist
ALTER TABLE kdd_desktop_settings 
ADD COLUMN IF NOT EXISTS widget_state LONGTEXT NOT NULL DEFAULT '{"active":[],"positions":{}}' 
AFTER theme;

-- Remove the old unique constraint on user_id only
-- This is needed for multi-tenant support
ALTER TABLE kdd_desktop_settings DROP INDEX IF EXISTS user_id;

-- Add proper compound unique constraint for authority_id + user_id
-- This allows the same user_id to exist across different authorities
ALTER TABLE kdd_desktop_settings 
ADD UNIQUE KEY IF NOT EXISTS authority_user (authority_id, user_id);