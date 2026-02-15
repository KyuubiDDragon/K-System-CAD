-- Migration: Add widget_state column to kdd_desktop_settings table
-- Date: 2025-01-30
-- Description: Adds widget_state column to store desktop widget configuration

-- Add widget_state column if table exists
-- Note: This will fail silently if column already exists, which is what we want
ALTER TABLE kdd_desktop_settings 
ADD COLUMN widget_state LONGTEXT NOT NULL DEFAULT '{"active":[],"positions":{}}' 
AFTER theme;