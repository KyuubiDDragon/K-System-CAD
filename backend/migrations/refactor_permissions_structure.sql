-- ============================================================================
-- Migration: Refactor Permissions System
-- Description: Restructures kdd_permissions for module-based permissions
--              with bitmask support while maintaining backwards compatibility
-- Author: Claude Code
-- Date: 2025-10-31
-- ============================================================================

-- Step 1: Create backup table
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS kdd_permissions_backup;
CREATE TABLE kdd_permissions_backup AS SELECT * FROM kdd_permissions;

-- Step 2: Create new permissions table structure
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS kdd_permissions_new;
CREATE TABLE kdd_permissions_new (
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- Core permission identifier (backwards compatible)
  name VARCHAR(128) NOT NULL COMMENT 'Full permission name (e.g., READ_BLACKBOARD_AREA)',

  -- Structured fields for modern usage
  module VARCHAR(64) NOT NULL COMMENT 'Module identifier (e.g., blackboard, employee)',
  sub_module VARCHAR(64) NULL COMMENT 'Sub-module for hierarchical permissions (e.g., area, admin)',
  action VARCHAR(32) NOT NULL COMMENT 'Action type (read, write, delete, admin, view)',

  -- Bitmask value for this action (used for efficient permission checks)
  bitmask_value INT NULL COMMENT 'Bitmask value: 1=read, 2=write, 4=delete, 8=admin, 16=view',

  -- Display information
  action_display VARCHAR(64) NULL COMMENT 'Localized action name (e.g., Lesen, Schreiben)',
  module_display VARCHAR(128) NULL COMMENT 'Localized module name (e.g., Schwarzes Brett Bereiche)',
  description VARCHAR(255) NULL,

  -- Multi-tenancy
  authority_id INT NOT NULL DEFAULT 1,

  -- Metadata
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Constraints
  UNIQUE KEY unique_permission (module, sub_module, action, authority_id),
  UNIQUE KEY unique_name (name, authority_id),
  INDEX idx_module (module, authority_id),
  INDEX idx_action (action),
  INDEX idx_bitmask (module, sub_module, authority_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Restructured permissions system with module-based organization and bitmask support';

-- Step 3: Parse and migrate existing data
-- ----------------------------------------------------------------------------
-- This complex migration extracts module, sub_module, and action from the existing 'name' field

INSERT INTO kdd_permissions_new
  (id, name, module, sub_module, action, bitmask_value, action_display, module_display, description, authority_id, created_at)
SELECT
  id,
  name,

  -- Extract module from name (lowercased)
  LOWER(
    CASE
      -- Special cases: ADMIN_ prefixed permissions
      WHEN name LIKE 'ADMIN_%' THEN
        SUBSTRING_INDEX(SUBSTRING_INDEX(name, '_', 3), '_', -1)

      -- Regular pattern: ACTION_MODULE or ACTION_MODULE_SUBMODULE
      ELSE
        SUBSTRING_INDEX(SUBSTRING(name, LOCATE('_', name) + 1), '_', 1)
    END
  ) as module,

  -- Extract sub_module if exists
  CASE
    -- ADMIN_ prefix cases
    WHEN name LIKE 'ADMIN_%' AND LENGTH(name) - LENGTH(REPLACE(name, '_', '')) > 2 THEN
      LOWER(SUBSTRING_INDEX(name, '_', -1))

    -- More than 2 underscores means we have a sub_module
    WHEN LENGTH(name) - LENGTH(REPLACE(name, '_', '')) > 1 THEN
      LOWER(SUBSTRING_INDEX(name, '_', -1))

    ELSE NULL
  END as sub_module,

  -- Extract action (read, write, delete, admin, view, create)
  LOWER(
    CASE
      WHEN name LIKE 'ADMIN_%' THEN
        SUBSTRING_INDEX(name, '_', 2)
      ELSE
        SUBSTRING_INDEX(name, '_', 1)
    END
  ) as action,

  -- Assign bitmask values based on action
  CASE
    WHEN name LIKE '%READ%' THEN 1
    WHEN name LIKE '%WRITE%' THEN 2
    WHEN name LIKE '%DELETE%' THEN 4
    WHEN name LIKE '%ADMIN%' THEN 8
    WHEN name LIKE '%VIEW%' THEN 16
    WHEN name LIKE '%CREATE%' THEN 32
    ELSE NULL
  END as bitmask_value,

  name_alias as action_display,
  site as module_display,
  description,
  authority_id,
  created_at

FROM kdd_permissions_backup;

-- Step 4: Manual corrections for special cases
-- ----------------------------------------------------------------------------

-- Fix common patterns that might have been misparsed

-- ALL_PERMISSIONS special case
UPDATE kdd_permissions_new
SET module = 'system',
    sub_module = NULL,
    action = 'all',
    bitmask_value = 255
WHERE name = 'ALL_PERMISSIONS';

-- CAN_LOGIN special case
UPDATE kdd_permissions_new
SET module = 'auth',
    sub_module = NULL,
    action = 'login',
    bitmask_value = NULL
WHERE name = 'CAN_LOGIN';

-- Fix BLACKBOARD patterns
UPDATE kdd_permissions_new
SET module = 'blackboard',
    sub_module = CASE
      WHEN name LIKE '%AREA%' THEN 'area'
      WHEN name LIKE '%ADMIN%' THEN 'admin'
      WHEN name LIKE '%EMPLOYEE%' THEN 'employee'
      ELSE NULL
    END
WHERE name LIKE '%BLACKBOARD%';

-- Fix DOCUMENT patterns
UPDATE kdd_permissions_new
SET module = 'document',
    sub_module = CASE
      WHEN name LIKE '%TRAINING%' THEN 'training'
      WHEN name LIKE '%ADMINISTRATION%' THEN 'administration'
      WHEN name LIKE '%DEPARTMENT%' THEN 'department'
      ELSE NULL
    END
WHERE name LIKE '%DOCUMENT%';

-- Fix ADMIN_ prefixed permissions
UPDATE kdd_permissions_new
SET action = LOWER(SUBSTRING_INDEX(SUBSTRING(name, 7), '_', 1)),
    module = LOWER(SUBSTRING_INDEX(SUBSTRING(name, LOCATE('_', name, 7) + 1), '_', 1))
WHERE name LIKE 'ADMIN_%'
  AND action NOT IN ('read', 'write', 'delete', 'view', 'admin');

-- Fix DISPATCH/VEHICLE/CREW - they all belong to dispatch module
UPDATE kdd_permissions_new
SET module = 'dispatch',
    sub_module = LOWER(
      CASE
        WHEN name LIKE '%VEHICLE%' THEN 'vehicle'
        WHEN name LIKE '%CREW%' THEN 'crew'
        WHEN name LIKE '%DISPATCH%' THEN NULL
      END
    )
WHERE name LIKE '%VEHICLE%' OR name LIKE '%CREW%' OR name LIKE '%DISPATCH%';

-- Fix REPORT patterns
UPDATE kdd_permissions_new
SET module = 'report',
    sub_module = CASE
      WHEN name LIKE '%REPORTCATEGORY%' THEN 'category'
      WHEN name LIKE '%REPORTTEMPLATE%' THEN 'template'
      WHEN name LIKE '%REPORTCODE%' THEN 'code'
      WHEN name LIKE '%REPORT%' THEN NULL
    END
WHERE name LIKE '%REPORT%';

-- Fix MESSAGE/MESSAGING patterns
UPDATE kdd_permissions_new
SET module = 'messaging'
WHERE name LIKE '%MESSAGE%';

-- Fix FIREPROTECTION
UPDATE kdd_permissions_new
SET module = 'fireprotection'
WHERE name LIKE '%FIREPROTECTION%';

-- Fix TRAININGASSIGN
UPDATE kdd_permissions_new
SET module = 'training',
    sub_module = 'assign'
WHERE name LIKE '%TRAININGASSIGN%';

-- Fix TEST (training tests)
UPDATE kdd_permissions_new
SET module = 'training',
    sub_module = 'test'
WHERE name LIKE '%TEST%' AND module != 'training';

-- Step 5: Replace old table with new one
-- ----------------------------------------------------------------------------

-- Drop foreign key constraints first
ALTER TABLE kdd_role_permissions DROP FOREIGN KEY IF EXISTS kdd_role_permissions_ibfk__fireguard_2;
ALTER TABLE kdd_role_permissions DROP FOREIGN KEY IF EXISTS kdd_role_permissions_ibfk_2;

-- Rename tables
DROP TABLE IF EXISTS kdd_permissions_old;
RENAME TABLE kdd_permissions TO kdd_permissions_old;
RENAME TABLE kdd_permissions_new TO kdd_permissions;

-- Step 6: Re-create foreign keys
-- ----------------------------------------------------------------------------
ALTER TABLE kdd_role_permissions
  ADD CONSTRAINT kdd_role_permissions_ibfk_2
  FOREIGN KEY (permission_id)
  REFERENCES kdd_permissions (id)
  ON DELETE CASCADE;

-- Step 7: Create helper views for common queries
-- ----------------------------------------------------------------------------

-- View: Permissions grouped by module with bitmask aggregation
DROP VIEW IF EXISTS kdd_permissions_by_module;
CREATE VIEW kdd_permissions_by_module AS
SELECT
  module,
  sub_module,
  authority_id,
  GROUP_CONCAT(action ORDER BY bitmask_value) as actions,
  SUM(IFNULL(bitmask_value, 0)) as total_bitmask,
  COUNT(*) as permission_count
FROM kdd_permissions
WHERE bitmask_value IS NOT NULL
GROUP BY module, sub_module, authority_id;

-- View: Full permission names in readable format
DROP VIEW IF EXISTS kdd_permissions_readable;
CREATE VIEW kdd_permissions_readable AS
SELECT
  id,
  name,
  CONCAT(
    COALESCE(action_display, UPPER(action)),
    ' - ',
    COALESCE(module_display, UPPER(module)),
    CASE WHEN sub_module IS NOT NULL THEN CONCAT(' (', UPPER(sub_module), ')') ELSE '' END
  ) as display_name,
  module,
  sub_module,
  action,
  bitmask_value,
  authority_id
FROM kdd_permissions;

-- Step 8: Verification queries
-- ----------------------------------------------------------------------------

-- Count comparison
SELECT
  'Original' as source, COUNT(*) as count
FROM kdd_permissions_old
UNION ALL
SELECT
  'Migrated' as source, COUNT(*) as count
FROM kdd_permissions;

-- Sample of migrated data
SELECT
  id,
  name,
  module,
  sub_module,
  action,
  bitmask_value,
  action_display,
  module_display
FROM kdd_permissions
ORDER BY module, sub_module, bitmask_value
LIMIT 20;

-- Check for any NULL modules or actions (these need manual review)
SELECT
  id,
  name,
  module,
  sub_module,
  action
FROM kdd_permissions
WHERE module IS NULL OR action IS NULL;

-- ============================================================================
-- ROLLBACK PROCEDURE (if needed)
-- ============================================================================
-- To rollback this migration:
--
-- DROP TABLE IF EXISTS kdd_permissions;
-- RENAME TABLE kdd_permissions_old TO kdd_permissions;
-- DROP TABLE IF EXISTS kdd_permissions_backup;
-- DROP VIEW IF EXISTS kdd_permissions_by_module;
-- DROP VIEW IF EXISTS kdd_permissions_readable;
--
-- Then re-create foreign keys as needed.
-- ============================================================================

-- Success message
SELECT '✅ Migration completed successfully!' as status;
SELECT 'Review the verification queries above to ensure data integrity.' as next_step;
SELECT 'Backup tables kdd_permissions_old and kdd_permissions_backup are available for rollback.' as note;
