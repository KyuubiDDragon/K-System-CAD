-- Fix module_display values that are causing wrong grouping
-- Date: 2025-01-03
-- Description: Fixes incorrect module_display values that caused permissions
--              to appear in wrong groups in the role management UI

-- 1. Fix CREATE_DOCUMENT (was incorrectly showing under Fire Protection)
UPDATE kdd_permissions
SET module_display = 'Dokumente'
WHERE id = 13 AND name = 'CREATE_DOCUMENT' AND module = 'document';

-- 2. Fix InvoiceItems display (was showing under same group as Invoice)
-- InvoiceItems should be separate from Invoice module
UPDATE kdd_permissions
SET module_display = 'Rechnungsposten'
WHERE module = 'invoiceitems';

-- 3. Fix Behördenaustausch (Authority Exchange) - make each module distinct
-- These are _GLOBAL permissions for sharing data between authorities
-- They were all showing as "Behördenaustausch" causing 3 duplicate entries

UPDATE kdd_permissions
SET module_display = 'Behördenaustausch › Schwarzes Brett'
WHERE module = 'blackboard' AND module_display = 'Behördenaustausch';

UPDATE kdd_permissions
SET module_display = 'Behördenaustausch › Dokumente'
WHERE module = 'document' AND module_display = 'Behördenaustausch';

UPDATE kdd_permissions
SET module_display = 'Behördenaustausch › Karte'
WHERE module = 'map' AND module_display = 'Behördenaustausch';

-- Verification queries (optional - run manually to verify)
-- Check CREATE_DOCUMENT and InvoiceItems:
-- SELECT id, name, module, module_display
-- FROM kdd_permissions
-- WHERE id = 13 OR module = 'invoiceitems'
-- ORDER BY module, id;

-- Check Behördenaustausch grouping:
-- SELECT module, module_display, COUNT(*) as count
-- FROM kdd_permissions
-- WHERE module_display LIKE 'Behördenaustausch%'
-- GROUP BY module, module_display
-- ORDER BY module_display;
