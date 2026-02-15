-- Migration: Add Authority Branding Features
-- Version: 0006
-- Description: Adds branding capabilities to authorities including logos, colors, and default backgrounds
-- Date: 2024-01-XX

-- Add branding columns to kdd_authorities table
ALTER TABLE kdd_authorities ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) NULL COMMENT 'URL zum Authority-Logo';
ALTER TABLE kdd_authorities ADD COLUMN IF NOT EXISTS primary_color VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Haupt-Farbe für Buttons etc.';
ALTER TABLE kdd_authorities ADD COLUMN IF NOT EXISTS secondary_color VARCHAR(7) DEFAULT '#6B7280' COMMENT 'Sekundär-Farbe für Akzente';
ALTER TABLE kdd_authorities ADD COLUMN IF NOT EXISTS app_title VARCHAR(100) NULL COMMENT 'Custom App-Titel (z.B. LSFD Los Santos)';
ALTER TABLE kdd_authorities ADD COLUMN IF NOT EXISTS default_background VARCHAR(255) NULL COMMENT 'Standard-Hintergrund für neue Member';

-- Add new permission for authority branding management
INSERT IGNORE INTO kdd_permissions (name, description, site) VALUES 
('ADMIN_AUTHORITY_SETTINGS', 'Authority Branding & Einstellungen verwalten', 'Full Site');

-- Give Super Admin role the new permission (if it exists)
INSERT IGNORE INTO kdd_role_permissions (role_id, permission_id, authority_id) 
SELECT r.id, p.id, 1
FROM kdd_roles r, kdd_permissions p 
WHERE r.name = 'Super Admin' AND p.name = 'ADMIN_AUTHORITY_SETTINGS'
AND NOT EXISTS (
    SELECT 1 FROM kdd_role_permissions rp2 
    WHERE rp2.role_id = r.id AND rp2.permission_id = p.id AND rp2.authority_id = 1
);

-- Example data for GTA RP servers (only insert if name exists)
UPDATE kdd_authorities SET 
    logo_url = '/uploads/logos/lsfd.png',
    primary_color = '#D32F2F',
    secondary_color = '#FFEB3B', 
    app_title = 'LSFD Los Santos',
    default_background = '/uploads/backgrounds/lsfd_station.jpg'
WHERE name = 'lsfd' AND active = 1;

UPDATE kdd_authorities SET 
    logo_url = '/uploads/logos/lspd.png',
    primary_color = '#1976D2',
    secondary_color = '#FFC107',
    app_title = 'LSPD Los Santos', 
    default_background = '/uploads/backgrounds/mission_row.jpg'
WHERE name = 'lspd' AND active = 1;

UPDATE kdd_authorities SET 
    logo_url = '/uploads/logos/ems.png',
    primary_color = '#2E7D32',
    secondary_color = '#4CAF50',
    app_title = 'EMS Los Santos',
    default_background = '/uploads/backgrounds/hospital.jpg'
WHERE name = 'ems' AND active = 1;

UPDATE kdd_authorities SET 
    logo_url = '/uploads/logos/fib.png',
    primary_color = '#424242',
    secondary_color = '#757575',
    app_title = 'FIB San Andreas',
    default_background = '/uploads/backgrounds/fib_building.jpg'
WHERE name = 'fib' AND active = 1;