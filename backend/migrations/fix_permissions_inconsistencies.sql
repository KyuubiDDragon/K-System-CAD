-- ============================================================================
-- Fix Permissions Inconsistencies
-- Description: Fixes logical errors and inconsistencies in kdd_permissions
-- Date: 2025-10-31
-- ============================================================================

-- BACKUP: Create backup before making changes
-- CREATE TABLE kdd_permissions_backup_20251031 AS SELECT * FROM kdd_permissions;

-- ============================================================================
-- FIX 1: ADMIN_* Permissions - Redundante module/sub_module
-- ============================================================================

-- ADMIN_WRITE_USERS / ADMIN_READ_USERS (ID 22, 23)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'users'
WHERE id IN (22, 23);

-- ADMIN_*_APPLICATION (ID 24, 36, 44)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'application'
WHERE id IN (24, 36, 44);

-- ADMIN_*_ROLES (ID 38, 41, 42)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'roles'
WHERE id IN (38, 41, 42);

-- ADMIN_*_SETTINGS (ID 48, 49, 50)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'settings'
WHERE id IN (48, 49, 50);

-- ADMIN_*_EMPLOYEE (ID 51, 52, 53)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'employee'
WHERE id IN (51, 52, 53);

-- ADMIN_*_MAP (ID 54, 55)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'map'
WHERE id IN (54, 55);

-- ADMIN_*_TRAINING (ID 70, 71, 72)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'training'
WHERE id IN (70, 71, 72);

-- ADMIN_*_MESSAGES (ID 103, 104, 105)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'messaging'
WHERE id IN (103, 104, 105);

-- ADMIN_VIEW_WEATHER (ID 117)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'weather'
WHERE id = 117;

-- ADMIN_*_WEATHER (ID 270, 271)
UPDATE kdd_permissions
SET module = 'admin', sub_module = 'weather'
WHERE id IN (270, 271);

-- ============================================================================
-- FIX 2: ADMIN_WRITE_READ - Komplett falscher Eintrag (ID 56)
-- ============================================================================

-- Dieser Eintrag ist ein Parsing-Fehler und sollte entweder gelöscht
-- oder zu ADMIN_WRITE_MAP korrigiert werden
UPDATE kdd_permissions
SET
    name = 'ADMIN_WRITE_MAP',
    module = 'admin',
    sub_module = 'map',
    action = 'write',
    bitmask_value = 2,
    module_display = 'Admin_Map',
    description = 'Karteneinstellungen bearbeiten'
WHERE id = 56;

-- ============================================================================
-- FIX 3: Bitmask-Fehler bei VIEW-Permissions
-- ============================================================================

-- ADMIN_VIEW_* haben action='view' aber bitmask_value=8 (sollte 16 sein)
UPDATE kdd_permissions
SET bitmask_value = 16
WHERE action = 'view' AND bitmask_value = 8;

-- Für echte ADMIN actions sollte bitmask_value=8 sein
-- Diese müssen manuell geprüft werden:
-- ID 36 (ADMIN_VIEW_APPLICATION) - ist VIEW oder ADMIN?
-- ID 37 (ADMIN_VIEW_USERS) - ist VIEW oder ADMIN?
-- ID 38 (ADMIN_VIEW_ROLES) - ist VIEW oder ADMIN?

-- Wenn diese VIEW sind (wie der name suggeriert):
UPDATE kdd_permissions
SET bitmask_value = 16, action = 'view'
WHERE id IN (36, 37, 38, 48, 51, 54, 70, 117);

-- Wenn diese ADMIN sind, dann action ändern:
-- UPDATE kdd_permissions
-- SET action = 'admin', bitmask_value = 8
-- WHERE id IN (36, 37, 38, 48, 51, 54, 70, 117);

-- ============================================================================
-- FIX 4: VIEW_DOCUMENT_ADMINISTRATION - Falscher Bitmask (ID 57)
-- ============================================================================

UPDATE kdd_permissions
SET bitmask_value = 16
WHERE id = 57;

-- ============================================================================
-- FIX 5: VEHICLE/CREW sollten zu dispatch Modul gehören (ID 85-90)
-- ============================================================================

-- VEHICLE Permissions
UPDATE kdd_permissions
SET module = 'dispatch', sub_module = 'vehicle'
WHERE name LIKE '%VEHICLE%' AND module = 'vehicle';

-- CREW Permissions
UPDATE kdd_permissions
SET module = 'dispatch', sub_module = 'crew'
WHERE name LIKE '%CREW%' AND module = 'crew';

-- ============================================================================
-- FIX 6: COMPANYTYPE sollte Submodul von COMPANY sein (ID 112-114)
-- ============================================================================

UPDATE kdd_permissions
SET module = 'company', sub_module = 'type'
WHERE module = 'companytype';

-- ============================================================================
-- FIX 7: ADMIN_DOCUMENT_AREAS - Falsche Struktur (ID 212)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'document',
    action = 'admin',
    bitmask_value = 8,
    module_display = 'Dokumentenbereiche',
    description = 'Kann Dokumentenbereiche verwalten (erstellen, bearbeiten, löschen)'
WHERE id = 212;

-- ============================================================================
-- FIX 8: ADMIN_AUTHORITY_SETTINGS - Falsche Struktur (ID 217)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'authority',
    action = 'admin',
    bitmask_value = 8,
    action_display = 'Verwalten',
    description = 'Kann Authority-Einstellungen und Branding verwalten'
WHERE id = 217;

-- ============================================================================
-- FIX 9: ADMIN_CHEATSHEET - Inkonsistente Struktur (ID 219)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'cheatsheet',
    action = 'admin',
    bitmask_value = 8
WHERE id = 219;

-- ============================================================================
-- FIX 10: ADMIN_MAIL - Inkonsistente Struktur (ID 248)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'mail',
    action = 'admin',
    bitmask_value = 8
WHERE id = 248;

-- ============================================================================
-- FIX 11: ADMIN_REPORTS - Inkonsistente Struktur (ID 262)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'report',
    action = 'admin',
    bitmask_value = 8,
    module_display = 'Admin_Berichte'
WHERE id = 262;

-- ============================================================================
-- FIX 12: ADMIN_AUTHORITY_FIELDS - Falsche Struktur (ID 265)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'authority',
    action = 'admin',
    bitmask_value = 8,
    module_display = 'Authority Felder'
WHERE id = 265;

-- ============================================================================
-- FIX 13: ADMIN_BLACKBOARD_AREAS - Falsche Struktur (ID 283)
-- ============================================================================

UPDATE kdd_permissions
SET
    module = 'admin',
    sub_module = 'blackboard',
    action = 'admin',
    bitmask_value = 8,
    module_display = 'Schwarzes Brett Bereiche'
WHERE id = 283;

-- ============================================================================
-- FIX 14: Blackboard Permissions (NEW Area-Based System)
-- ============================================================================

-- IMPORTANT: The new Blackboard system uses AREA-BASED permissions
-- Permissions are dynamically calculated from kdd_blackboard_area_permissions table
-- See: BLACKBOARD_PERMISSIONS_CLEANUP.md for full documentation

-- Only TWO permissions are needed for the NEW system:

-- 1. READ_BLACKBOARD_AREA - Permission to list and view blackboard areas
INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'READ_BLACKBOARD_AREA',
    'blackboard',
    'area',
    'read',
    1,
    'Lesen',
    'Schwarzes Brett',
    'Kann Schwarzes-Brett-Bereiche auflisten und einsehen',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'READ_BLACKBOARD_AREA'
);

-- 2. ADMIN_BLACKBOARD_AREAS - Permission to manage areas and permissions
INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'ADMIN_BLACKBOARD_AREAS',
    'admin',
    'blackboard',
    'admin',
    8,
    'Verwalten',
    'Schwarzes Brett Bereiche',
    'Kann Schwarzes-Brett-Bereiche erstellen, bearbeiten, löschen und Berechtigungen verwalten',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'ADMIN_BLACKBOARD_AREAS'
);

-- ============================================================================
-- OPTIONAL: Legacy Permissions (Backwards Compatibility Only)
-- ============================================================================

-- These are ONLY for backwards compatibility with old boardType system
-- If you still use /blackboard/?boardType=admin or boardType=employee
-- Otherwise, these can be omitted

-- Legacy Admin Board
INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'READ_BLACKBOARD_ADMIN',
    'blackboard',
    NULL,
    'read',
    1,
    'Lesen',
    'Schwarzes Brett Admin (Legacy)',
    'Legacy: Kann Admin-Board Einträge lesen',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'READ_BLACKBOARD_ADMIN' AND sub_module IS NULL
);

INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'WRITE_BLACKBOARD_ADMIN',
    'blackboard',
    NULL,
    'write',
    2,
    'Schreiben',
    'Schwarzes Brett Admin (Legacy)',
    'Legacy: Kann Admin-Board Einträge erstellen/bearbeiten',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'WRITE_BLACKBOARD_ADMIN' AND sub_module IS NULL
);

INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'DELETE_BLACKBOARD_ADMIN',
    'blackboard',
    NULL,
    'delete',
    4,
    'Löschen',
    'Schwarzes Brett Admin (Legacy)',
    'Legacy: Kann Admin-Board Einträge löschen',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'DELETE_BLACKBOARD_ADMIN' AND sub_module IS NULL
);

-- Legacy Employee Board
INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'READ_BLACKBOARD_EMPLOYEE',
    'blackboard',
    NULL,
    'read',
    1,
    'Lesen',
    'Schwarzes Brett Mitarbeiter (Legacy)',
    'Legacy: Kann Mitarbeiter-Board Einträge lesen',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'READ_BLACKBOARD_EMPLOYEE' AND sub_module IS NULL
);

INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'WRITE_BLACKBOARD_EMPLOYEE',
    'blackboard',
    NULL,
    'write',
    2,
    'Schreiben',
    'Schwarzes Brett Mitarbeiter (Legacy)',
    'Legacy: Kann Mitarbeiter-Board Einträge erstellen/bearbeiten',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'WRITE_BLACKBOARD_EMPLOYEE' AND sub_module IS NULL
);

INSERT INTO kdd_permissions
    (name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id)
SELECT
    'DELETE_BLACKBOARD_EMPLOYEE',
    'blackboard',
    NULL,
    'delete',
    4,
    'Löschen',
    'Schwarzes Brett Mitarbeiter (Legacy)',
    'Legacy: Kann Mitarbeiter-Board Einträge löschen',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_permissions WHERE name = 'DELETE_BLACKBOARD_EMPLOYEE' AND sub_module IS NULL
);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check für Duplikate in unique constraint
SELECT
    module,
    sub_module,
    action,
    authority_id,
    COUNT(*) as count
FROM kdd_permissions
GROUP BY module, sub_module, action, authority_id
HAVING count > 1;

-- Check für inkonsistente Bitmask-Werte
SELECT
    id,
    name,
    action,
    bitmask_value,
    CASE action
        WHEN 'read' THEN 1
        WHEN 'write' THEN 2
        WHEN 'delete' THEN 4
        WHEN 'admin' THEN 8
        WHEN 'view' THEN 16
        WHEN 'create' THEN 32
        ELSE NULL
    END as expected_bitmask
FROM kdd_permissions
WHERE bitmask_value IS NOT NULL
  AND bitmask_value != CASE action
        WHEN 'read' THEN 1
        WHEN 'write' THEN 2
        WHEN 'delete' THEN 4
        WHEN 'admin' THEN 8
        WHEN 'view' THEN 16
        WHEN 'create' THEN 32
        ELSE bitmask_value
    END;

-- Check für redundante module/sub_module
SELECT
    id,
    name,
    module,
    sub_module,
    action
FROM kdd_permissions
WHERE module = sub_module
  AND sub_module IS NOT NULL;

-- Zusammenfassung der Module
SELECT
    module,
    COUNT(*) as permission_count,
    GROUP_CONCAT(DISTINCT sub_module ORDER BY sub_module) as sub_modules,
    GROUP_CONCAT(DISTINCT action ORDER BY action) as actions
FROM kdd_permissions
WHERE module IS NOT NULL
GROUP BY module
ORDER BY module;

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
SELECT '✅ Permissions Inkonsistenzen wurden korrigiert!' as status;
SELECT 'Bitte führe die Verification Queries aus, um die Änderungen zu prüfen.' as next_step;
