-- Populate display_group field for all permissions
-- Date: 2025-01-03
-- Description: Sets display_group for logical grouping in the role management UI
--              display_group is used for grouping modules together
--              module_display is used for showing the individual module name

-- ========================================
-- 1. BEHÖRDENAUSTAUSCH (Authority Exchange)
-- ========================================
-- Groups: blackboard GLOBAL, document GLOBAL, map GLOBAL

UPDATE kdd_permissions
SET display_group = 'Behördenaustausch'
WHERE module = 'blackboard' AND sub_module IS NULL;

UPDATE kdd_permissions
SET display_group = 'Behördenaustausch'
WHERE module = 'document' AND name LIKE '%_DOCUMENT_GLOBAL';

UPDATE kdd_permissions
SET display_group = 'Behördenaustausch'
WHERE module = 'map' AND name LIKE '%_MAP_GLOBAL';

-- ========================================
-- 2. RECHNUNGEN (Invoices)
-- ========================================
-- Groups: invoice + invoiceitems

UPDATE kdd_permissions
SET display_group = 'Rechnungen'
WHERE module IN ('invoice', 'invoiceitems');

-- ========================================
-- 3. UNTERNEHMEN (Companies)
-- ========================================
-- Groups: company (all sub_modules)

UPDATE kdd_permissions
SET display_group = 'Unternehmen'
WHERE module = 'company';

-- ========================================
-- 4. LEITSTELLE (Dispatch)
-- ========================================
-- Groups: dispatch (all sub_modules)

UPDATE kdd_permissions
SET display_group = 'Leitstelle'
WHERE module = 'dispatch';

-- ========================================
-- 5. BERICHTE (Reports)
-- ========================================
-- Groups: report (all sub_modules)

UPDATE kdd_permissions
SET display_group = 'Berichte'
WHERE module = 'report';

-- ========================================
-- 6. DOKUMENTE (Documents)
-- ========================================
-- Groups: document (except GLOBAL which goes to Behördenaustausch)

UPDATE kdd_permissions
SET display_group = 'Dokumente'
WHERE module = 'document' AND name NOT LIKE '%_DOCUMENT_GLOBAL';

-- ========================================
-- 7. SCHWARZES BRETT (Blackboard)
-- ========================================
-- Groups: blackboard with sub_module

UPDATE kdd_permissions
SET display_group = 'Schwarzes Brett'
WHERE module = 'blackboard' AND sub_module IS NOT NULL;

-- ========================================
-- 8. SCHULUNGEN (Training)
-- ========================================
-- Groups: training (all sub_modules)

UPDATE kdd_permissions
SET display_group = 'Schulungen'
WHERE module = 'training';

-- ========================================
-- 9. KARTE (Map)
-- ========================================
-- Groups: map (except GLOBAL which goes to Behördenaustausch)

UPDATE kdd_permissions
SET display_group = 'Karte'
WHERE module = 'map' AND name NOT LIKE '%_MAP_GLOBAL';

-- ========================================
-- 10. AKTEN (Files)
-- ========================================

UPDATE kdd_permissions
SET display_group = 'Fahrzeugakten'
WHERE module = 'vehicle' AND sub_module = 'file';

UPDATE kdd_permissions
SET display_group = 'Wohnungsakten'
WHERE module = 'apartment' AND sub_module = 'file';

UPDATE kdd_permissions
SET display_group = 'Personenakten'
WHERE module = 'person' AND sub_module = 'file';

-- ========================================
-- 11. STANDALONE MODULES (keep module_display as group)
-- ========================================

UPDATE kdd_permissions
SET display_group = module_display
WHERE module IN (
    'account',
    'application',
    'auth',
    'calendar',
    'cheatsheet',
    'employee',
    'filemanager',
    'fireprotection',
    'mail',
    'messaging',
    'rank',
    'settings',
    'suspended',
    'system',
    'template',
    'todo',
    'users',
    'weather',
    'whiteboard',
    'access',
    'authority'
);

-- ========================================
-- 12. ADMIN MODULE
-- ========================================
-- For admin module, use module_display as group (they're already well organized)

UPDATE kdd_permissions
SET display_group = module_display
WHERE module = 'admin';

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Check grouping summary:
-- SELECT
--     display_group,
--     COUNT(DISTINCT module) as modules_count,
--     COUNT(*) as permissions_count,
--     GROUP_CONCAT(DISTINCT module ORDER BY module) as modules
-- FROM kdd_permissions
-- WHERE name NOT IN ('ALL_PERMISSIONS', 'IS_SUSPENDED_GROUP', 'SYSTEM_ADMIN')
-- GROUP BY display_group
-- ORDER BY display_group;

-- Check for NULL display_groups (should be none):
-- SELECT id, name, module, sub_module, module_display, display_group
-- FROM kdd_permissions
-- WHERE display_group IS NULL;
