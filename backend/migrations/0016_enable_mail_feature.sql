-- =====================================================
-- Migration 0016: Enable Mail Feature and Permissions
-- Date: 2025-10-22
-- Description: Adds mail feature and permissions to enable the mail system
-- =====================================================

-- =====================================================
-- 1. ADD MAIL FEATURE TO FEATURES TABLE
-- =====================================================
INSERT INTO `kdd_authority_features` (`name`, `code`, `description`, `created_at`, `updated_at`)
VALUES ('Mail System', 'mail', 'Access to enterprise mail and communication system', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = 'Mail System';

-- =====================================================
-- 2. ADD MAIL PERMISSIONS
-- =====================================================
INSERT INTO `kdd_permissions` (`name`, `display_name`, `category`, `description`) VALUES
('READ_MAIL', 'Mail lesen', 'Mail', 'Kann Mails lesen und empfangen'),
('WRITE_MAIL', 'Mail schreiben', 'Mail', 'Kann Mails senden und verwalten'),
('ADMIN_MAIL', 'Mail administrieren', 'Mail', 'Kann Unternehmenspostfächer verwalten'),
('DELETE_MAIL', 'Mail löschen', 'Mail', 'Kann Mails permanent löschen')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- 3. ASSIGN MAIL PERMISSIONS TO ADMIN ROLE
-- Note: This assumes role_id = 1 is admin. Adjust if needed.
-- =====================================================
INSERT IGNORE INTO `kdd_role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `kdd_permissions` WHERE `name` IN ('READ_MAIL', 'WRITE_MAIL', 'ADMIN_MAIL', 'DELETE_MAIL');

-- =====================================================
-- 4. ENABLE MAIL FEATURE FOR ALL ACTIVE AUTHORITIES
-- =====================================================
INSERT IGNORE INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`)
SELECT a.`id`, f.`id`
FROM `kdd_authorities` a
CROSS JOIN `kdd_authority_features` f
WHERE a.`active` = 1 AND f.`code` = 'mail';

-- =====================================================
-- 5. CREATE DEFAULT MAIL DOMAIN
-- =====================================================
INSERT INTO `kdd_mail_domains` (`domain`, `domain_type`, `authority_id`, `is_active`, `verified`, `display_name`)
VALUES ('mail.ls', 'default', NULL, 1, 1, 'K-Systems Mail')
ON DUPLICATE KEY UPDATE `domain` = 'mail.ls';

-- =====================================================
-- END OF MIGRATION 0016
-- =====================================================
