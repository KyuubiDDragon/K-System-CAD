-- Migration: Rename file module displays to distinguish them
-- Date: 2025-01-03
-- Purpose: Change "Akten" to "Fahrzeugakten", "Wohnungsakten", "Personenakten"

-- Update vehicle module
UPDATE kdd_permissions
SET module_display = 'Fahrzeugakten'
WHERE module = 'vehicle';

-- Update apartment module
UPDATE kdd_permissions
SET module_display = 'Wohnungsakten'
WHERE module = 'apartment';

-- Update person module
UPDATE kdd_permissions
SET module_display = 'Personenakten'
WHERE module = 'person';

-- Verify the changes
SELECT DISTINCT module, module_display, COUNT(*) as permission_count
FROM kdd_permissions
WHERE module IN ('vehicle', 'apartment', 'person')
GROUP BY module, module_display
ORDER BY module_display;

-- Expected result:
-- Module: apartment | Display: Wohnungsakten   | Count: 4
-- Module: person    | Display: Personenakten   | Count: 4
-- Module: vehicle   | Display: Fahrzeugakten   | Count: 4
