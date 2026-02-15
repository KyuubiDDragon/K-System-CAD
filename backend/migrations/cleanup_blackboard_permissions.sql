-- ============================================================================
-- Blackboard Permissions Cleanup Script
-- Description: Removes incorrectly added blackboard permissions and ensures
--              correct permissions exist for the new area-based system
-- Date: 2025-10-31
-- ============================================================================

-- IMPORTANT: Read BLACKBOARD_PERMISSIONS_CLEANUP.md before running this script!

-- ============================================================================
-- STEP 1: Remove incorrectly added blackboard sub-module permissions
-- ============================================================================

-- These permissions were added based on an old database structure
-- The new system uses dynamic permissions from kdd_blackboard_area_permissions table

DELETE FROM kdd_permissions
WHERE name IN (
    'VIEW_BLACKBOARD_ADMIN',
    'WRITE_BLACKBOARD_ADMIN',
    'READ_BLACKBOARD_ADMIN',
    'DELETE_BLACKBOARD_ADMIN',
    'VIEW_BLACKBOARD_EMPLOYEE',
    'WRITE_BLACKBOARD_EMPLOYEE',
    'READ_BLACKBOARD_EMPLOYEE',
    'DELETE_BLACKBOARD_EMPLOYEE'
)
AND module = 'blackboard'
AND sub_module IN ('admin', 'employee');

-- ============================================================================
-- STEP 2: Ensure correct NEW system permissions exist
-- ============================================================================

-- Permission to read/list blackboard areas
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

-- Permission to manage blackboard areas and permissions
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
-- STEP 3: Optional - Ensure legacy permissions exist (backwards compatibility)
-- ============================================================================

-- Only needed if you still use the old boardType system (admin/employee boards)
-- If you've fully migrated to areas, you can skip this section

-- Legacy Admin Board Permissions
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

-- Legacy Employee Board Permissions
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

-- 1. Check that incorrect permissions were deleted
SELECT 'Checking deleted permissions...' as step;
SELECT id, name, module, sub_module, action
FROM kdd_permissions
WHERE name LIKE '%BLACKBOARD%'
  AND module = 'blackboard'
  AND sub_module IN ('admin', 'employee');
-- Expected: 0 rows

-- 2. Check that new system permissions exist
SELECT 'Checking NEW system permissions...' as step;
SELECT id, name, module, sub_module, action, bitmask_value, description
FROM kdd_permissions
WHERE name IN ('READ_BLACKBOARD_AREA', 'ADMIN_BLACKBOARD_AREAS')
ORDER BY name;
-- Expected: 2 rows

-- 3. Check legacy permissions (if applicable)
SELECT 'Checking LEGACY permissions...' as step;
SELECT id, name, module, sub_module, action, bitmask_value, description
FROM kdd_permissions
WHERE name LIKE '%BLACKBOARD_%'
  AND name NOT LIKE '%AREA%'
  AND sub_module IS NULL
ORDER BY name;
-- Expected: 6 rows (READ/WRITE/DELETE for ADMIN and EMPLOYEE)

-- 4. Summary of all blackboard-related permissions
SELECT 'Summary of all blackboard permissions...' as step;
SELECT
    id,
    name,
    CONCAT(module, COALESCE(CONCAT('.', sub_module), ''), '.', action) as permission_path,
    bitmask_value,
    description
FROM kdd_permissions
WHERE name LIKE '%BLACKBOARD%'
  OR module = 'blackboard'
ORDER BY module, sub_module, action;

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
SELECT '✅ Blackboard permissions cleanup completed!' as status;
SELECT 'Review the verification queries above to ensure everything is correct.' as next_step;
SELECT 'Read BLACKBOARD_PERMISSIONS_CLEANUP.md for details on the new system.' as documentation;
