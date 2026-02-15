-- Migration: Merge Settings and Authority Branding systems
-- Date: 2025-10-22
-- Description: Adds branding and theme fields to kdd_authorities table
--              Merges Settings functionality into Authority Branding for single source of truth

-- Add branding fields if they don't exist
ALTER TABLE `kdd_authorities`
ADD COLUMN IF NOT EXISTS `logo_url` VARCHAR(255) DEFAULT NULL COMMENT 'URL to authority logo',
ADD COLUMN IF NOT EXISTS `primary_color` VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Primary brand color (hex)',
ADD COLUMN IF NOT EXISTS `secondary_color` VARCHAR(7) DEFAULT '#6B7280' COMMENT 'Secondary brand color (hex)',
ADD COLUMN IF NOT EXISTS `app_title` VARCHAR(100) DEFAULT NULL COMMENT 'Application title for this authority',
ADD COLUMN IF NOT EXISTS `default_background` VARCHAR(255) DEFAULT NULL COMMENT 'Default background image URL',
ADD COLUMN IF NOT EXISTS `theme_settings` JSON DEFAULT NULL COMMENT 'Complete theme configuration as JSON';

-- Create index for faster lookups if it doesn't exist
CREATE INDEX IF NOT EXISTS `idx_active` ON `kdd_authorities` (`active`);

-- Example theme_settings JSON structure (for documentation):
-- {
--   "darkMode": true,
--   "employeeNavigation": "Mitarbeiter",
--   "accentColor": "#10B981",
--   "backgroundColor": "#0F172A",
--   "surfaceColor": "#1E293B",
--   "tertiaryColor": "#8B5CF6",
--   "infoColor": "#3B82F6",
--   "successColor": "#10B981",
--   "warningColor": "#F59E0B",
--   "errorColor": "#EF4444",
--   "onPrimaryColor": "#FFFFFF",
--   "onSecondaryColor": "#FFFFFF",
--   "onAccentColor": "#FFFFFF",
--   "onBackgroundColor": "#F1F5F9",
--   "onSurfaceColor": "#F1F5F9",
--   "onTertiaryColor": "#FFFFFF",
--   "onInfoColor": "#FFFFFF",
--   "onSuccessColor": "#FFFFFF",
--   "onWarningColor": "#FFFFFF",
--   "onErrorColor": "#FFFFFF",
--   "borderRadius": 8,
--   "enableGradients": true,
--   "systemSettings": {
--     "sessionTimeout": 30,
--     "maxFileUploadSize": 10,
--     "enableNotifications": true,
--     "enableDarkMode": true,
--     "language": "de",
--     "timezone": "Europe/Berlin"
--   }
-- }
