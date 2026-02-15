-- Migration 0017: Mail Access Rights System
-- Adds granular per-mail access control
-- Allows specific users or roles to access individual mails

-- Create mail access rights table
CREATE TABLE IF NOT EXISTS `kdd_mail_access_rights` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `mail_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Access can be granted to users or roles (one must be set)
  `user_id` INT(11) NULL,
  `role_id` INT(11) NULL,

  -- Access levels
  `can_read` TINYINT(1) DEFAULT 1,
  `can_reply` TINYINT(1) DEFAULT 0,
  `can_forward` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,

  -- Metadata
  `granted_by` INT(11) NOT NULL,
  `granted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,

  PRIMARY KEY (`id`),
  KEY `idx_mail_id` (`mail_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_authority_id` (`authority_id`),
  KEY `idx_active` (`is_active`),

  CONSTRAINT `fk_mail_access_mail` FOREIGN KEY (`mail_id`) REFERENCES `kdd_mails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mail_access_user` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mail_access_role` FOREIGN KEY (`role_id`) REFERENCES `kdd_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mail_access_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mail_access_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `kdd_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure at least one of user_id or role_id is set
ALTER TABLE `kdd_mail_access_rights`
ADD CONSTRAINT `chk_user_or_role` CHECK (
  (`user_id` IS NOT NULL AND `role_id` IS NULL) OR
  (`user_id` IS NULL AND `role_id` IS NOT NULL)
);

-- Add index for performance on access checks
CREATE INDEX `idx_mail_user_active` ON `kdd_mail_access_rights` (`mail_id`, `user_id`, `is_active`);
CREATE INDEX `idx_mail_role_active` ON `kdd_mail_access_rights` (`mail_id`, `role_id`, `is_active`);

-- Add column to kdd_mails to indicate if mail has restricted access
ALTER TABLE `kdd_mails`
ADD COLUMN `has_restricted_access` TINYINT(1) DEFAULT 0 AFTER `is_important`;

-- Create index for restricted access filtering
CREATE INDEX `idx_has_restricted_access` ON `kdd_mails` (`has_restricted_access`);
