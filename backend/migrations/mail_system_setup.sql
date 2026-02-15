-- =====================================================
-- Mail System: Quick Setup SQL
-- Run this AFTER the main migration is successful
-- =====================================================

-- 1. ADD PERMISSIONS
INSERT INTO kdd_permissions (name, display_name, category, description) VALUES
('READ_MAIL', 'Mail lesen', 'Mail', 'Kann Mails lesen und empfangen'),
('WRITE_MAIL', 'Mail schreiben', 'Mail', 'Kann Mails senden und verwalten'),
('ADMIN_MAIL', 'Mail administrieren', 'Mail', 'Kann Unternehmenspostfächer verwalten')
ON DUPLICATE KEY UPDATE name=name;

-- 2. ASSIGN PERMISSIONS TO ADMIN ROLE (Adjust role_id as needed)
-- Find your admin role ID first: SELECT * FROM kdd_roles WHERE name LIKE '%admin%';
-- Then insert permissions for that role

-- Example: If admin role ID is 1
INSERT INTO kdd_role_permissions (role_id, permission_id)
SELECT 1, id FROM kdd_permissions WHERE name IN ('READ_MAIL', 'WRITE_MAIL', 'ADMIN_MAIL')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- 3. ACTIVATE MAIL FEATURE FOR AUTHORITIES
-- Check your authority IDs: SELECT id, name FROM kdd_authorities;
-- Then activate for desired authorities (example: IDs 1, 2, 3)

UPDATE kdd_authorities
SET features = JSON_ARRAY_APPEND(COALESCE(features, '[]'), '$', 'mail')
WHERE id IN (1, 2, 3)
AND NOT JSON_CONTAINS(COALESCE(features, '[]'), '"mail"', '$');

-- 4. OPTIONAL: CREATE FACTION DOMAINS
-- Example for Fire Department (adjust authority_id)
INSERT INTO kdd_mail_domains (domain, domain_type, authority_id, is_active, verified, display_name)
VALUES
  ('fire-department.ls', 'faction', 1, 1, 1, 'Fire Department Mail'),
  ('police-department.ls', 'faction', 2, 1, 1, 'Police Department Mail')
ON DUPLICATE KEY UPDATE domain=domain;

-- Update authorities with custom domains
UPDATE kdd_authorities
SET has_custom_domain = 1,
    mail_domain = 'fire-department.ls',
    authority_type = 'faction'
WHERE id = 1;

UPDATE kdd_authorities
SET has_custom_domain = 1,
    mail_domain = 'police-department.ls',
    authority_type = 'faction'
WHERE id = 2;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if permissions were created
SELECT * FROM kdd_permissions WHERE name LIKE '%MAIL%';

-- Check if features are active
SELECT id, name, features FROM kdd_authorities;

-- Check mail domains
SELECT * FROM kdd_mail_domains;

-- =====================================================
-- DONE!
-- =====================================================
-- Now restart frontend and try accessing /mail
