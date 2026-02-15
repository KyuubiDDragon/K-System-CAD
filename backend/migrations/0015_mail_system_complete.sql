-- =====================================================
-- Migration 0015: Complete Mail System
-- Date: 2025-10-22
-- Description: Vollständiges Mail-System mit Domains, Accounts,
--              Company Mailboxes, Permissions, Templates
-- =====================================================

-- =====================================================
-- 1. MAIL DOMAINS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_domains` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `domain` VARCHAR(191) NOT NULL,
  `domain_type` ENUM('faction', 'default') NOT NULL,
  `authority_id` INT(11) NULL,

  -- Settings
  `is_active` TINYINT(1) DEFAULT 1,
  `verified` TINYINT(1) DEFAULT 1,
  `max_accounts` INT(11) DEFAULT 1000,

  -- Branding
  `display_name` VARCHAR(255) NULL,
  `logo_url` VARCHAR(255) NULL,

  -- Stats
  `total_accounts` INT(11) DEFAULT 0,
  `total_mails_sent` BIGINT(20) DEFAULT 0,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_domain` (`domain`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_type` (`domain_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. MAIL ACCOUNTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `salt` VARCHAR(255) NOT NULL,

  -- Ownership
  `current_user_id` INT(11) NULL,
  `authority_id` INT(11) NOT NULL,

  -- Account Type
  `account_type` ENUM('personal', 'company', 'faction') NOT NULL DEFAULT 'personal',

  -- Settings
  `is_active` TINYINT(1) DEFAULT 1,
  `is_locked` TINYINT(1) DEFAULT 0,
  `is_searchable` TINYINT(1) DEFAULT 1,

  -- Quota
  `storage_used` BIGINT(20) DEFAULT 0,
  `storage_limit` BIGINT(20) DEFAULT 1073741824,

  -- Security
  `last_login_at` TIMESTAMP NULL,
  `failed_login_attempts` INT(11) DEFAULT 0,
  `locked_until` TIMESTAMP NULL,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_user` (`current_user_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_searchable` (`is_searchable`),
  KEY `idx_active` (`is_active`),
  KEY `idx_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. USER MAIL LINKS (Historie)
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_user_mail_links` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `mail_account_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Status
  `is_active` TINYINT(1) DEFAULT 1,
  `linked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unlinked_at` TIMESTAMP NULL,
  `unlinked_reason` ENUM('user_request', 'job_change', 'termination', 'admin') NULL,

  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_mail` (`mail_account_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. MAILS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mails` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `message_id` VARCHAR(255) NOT NULL,
  `thread_id` VARCHAR(255) NULL,
  `reply_to_id` BIGINT(20) NULL,

  -- Sender
  `from_address` VARCHAR(255) NOT NULL,
  `from_user_id` INT(11) NULL,
  `from_name` VARCHAR(255) NULL,

  -- Recipients (stored as JSON)
  `to_addresses` JSON NOT NULL,
  `cc_addresses` JSON NULL,
  `bcc_addresses` JSON NULL,

  -- Content
  `subject` VARCHAR(500) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `body_text` LONGTEXT NULL,
  `has_attachments` TINYINT(1) DEFAULT 0,

  -- Status
  `is_draft` TINYINT(1) DEFAULT 0,
  `is_sent` TINYINT(1) DEFAULT 0,
  `sent_at` TIMESTAMP NULL,
  `priority` ENUM('low', 'normal', 'high') DEFAULT 'normal',

  -- Meta
  `authority_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_message_id` (`message_id`),
  KEY `idx_thread` (`thread_id`),
  KEY `idx_reply_to` (`reply_to_id`),
  KEY `idx_from_user` (`from_user_id`),
  KEY `idx_from_address` (`from_address`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_draft` (`is_draft`),
  KEY `idx_sent` (`is_sent`, `sent_at`),
  FULLTEXT KEY `search_content` (`subject`, `body_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. MAIL RECIPIENTS (User-spezifische Daten)
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_recipients` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `mail_id` BIGINT(20) NOT NULL,
  `recipient_address` VARCHAR(255) NOT NULL,
  `recipient_user_id` INT(11) NULL,
  `recipient_type` ENUM('to', 'cc', 'bcc') NOT NULL,

  -- User-spezifische Flags
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `is_starred` TINYINT(1) DEFAULT 0,
  `is_important` TINYINT(1) DEFAULT 0,
  `is_deleted` TINYINT(1) DEFAULT 0,
  `deleted_at` TIMESTAMP NULL,

  -- Organisation
  `folder_id` INT(11) NULL,
  `label_ids` JSON NULL,

  -- Assignment (für Company Mails)
  `assigned_to_user_id` INT(11) NULL,
  `assigned_at` TIMESTAMP NULL,
  `assigned_by_user_id` INT(11) NULL,
  `status` ENUM('new', 'in_progress', 'done', 'archived') DEFAULT 'new',

  -- Notes
  `private_note` TEXT NULL,

  -- Meta
  `authority_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_mail` (`mail_id`),
  KEY `idx_recipient_user` (`recipient_user_id`),
  KEY `idx_recipient_address` (`recipient_address`),
  KEY `idx_status` (`is_read`, `is_deleted`, `is_starred`),
  KEY `idx_folder` (`folder_id`),
  KEY `idx_assigned` (`assigned_to_user_id`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. MAIL ATTACHMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_attachments` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `mail_id` BIGINT(20) NOT NULL,

  -- File Info
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT(20) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,

  -- Validation
  `is_safe` TINYINT(1) DEFAULT 1,
  `scan_status` ENUM('pending', 'clean', 'infected') DEFAULT 'pending',

  -- Meta
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_mail` (`mail_id`),
  KEY `idx_scan_status` (`scan_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. COMPANY MAILBOXES
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_company_mailboxes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `mail_account_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Info
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `department` VARCHAR(255) NULL,

  -- Quota
  `max_mail_addresses` INT(11) DEFAULT 5,
  `current_mail_count` INT(11) DEFAULT 1,

  -- Settings
  `auto_reply_enabled` TINYINT(1) DEFAULT 0,
  `auto_reply_message` TEXT NULL,
  `signature` TEXT NULL,

  -- Meta
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_mail_account` (`mail_account_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. COMPANY MAILBOX PERMISSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_company_mailbox_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `mailbox_id` INT(11) NOT NULL,
  `user_id` INT(11) NULL,
  `group_id` INT(11) NULL,
  `authority_id` INT(11) NOT NULL,

  -- Permissions
  `can_read` TINYINT(1) DEFAULT 1,
  `can_send` TINYINT(1) DEFAULT 1,
  `can_delete` TINYINT(1) DEFAULT 0,
  `can_assign` TINYINT(1) DEFAULT 1,
  `can_manage_members` TINYINT(1) DEFAULT 0,
  `can_manage_settings` TINYINT(1) DEFAULT 0,

  -- Meta
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `added_by_user_id` INT(11) NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mailbox` (`mailbox_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_group` (`group_id`),
  CONSTRAINT `unique_mailbox_user` UNIQUE (`mailbox_id`, `user_id`),
  CONSTRAINT `unique_mailbox_group` UNIQUE (`mailbox_id`, `group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. MAIL FOLDERS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_folders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Folder Info
  `name` VARCHAR(255) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#3B82F6',
  `icon` VARCHAR(50) NULL,

  -- Settings
  `sort_order` INT(11) DEFAULT 0,
  `is_system` TINYINT(1) DEFAULT 0,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. MAIL LABELS/TAGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_labels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Label Info
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#3B82F6',
  `icon` VARCHAR(50) NULL,

  -- Settings
  `sort_order` INT(11) DEFAULT 0,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_label` (`user_id`, `name`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. MAIL TEMPLATES (Personal + Mailbox-spezifisch)
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,

  -- Ownership
  `owner_user_id` INT(11) NULL,
  `owner_mailbox_id` INT(11) NULL,
  `authority_id` INT(11) NOT NULL,

  -- Template Type
  `template_type` ENUM('personal', 'mailbox', 'system') NOT NULL DEFAULT 'personal',

  -- Content
  `subject_template` VARCHAR(500) NULL,
  `body_template` LONGTEXT NOT NULL,

  -- Variables allowed in template
  `available_variables` JSON NULL COMMENT 'e.g., ["{{name}}", "{{company}}", "{{date}}"]',

  -- Settings
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT(11) DEFAULT 0,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_owner_user` (`owner_user_id`),
  KEY `idx_owner_mailbox` (`owner_mailbox_id`),
  KEY `idx_type` (`template_type`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. MAIL SIGNATURES
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_mail_signatures` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL,
  `mail_account_id` INT(11) NULL,
  `authority_id` INT(11) NOT NULL,

  -- Signature Content
  `name` VARCHAR(255) NOT NULL,
  `signature_html` TEXT NOT NULL,

  -- Settings
  `is_default` TINYINT(1) DEFAULT 0,
  `use_for_new` TINYINT(1) DEFAULT 1,
  `use_for_reply` TINYINT(1) DEFAULT 1,

  -- Meta
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_mail_account` (`mail_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. CONTACTS (neu für Mail-System)
-- =====================================================
CREATE TABLE IF NOT EXISTS `kdd_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT(11) NOT NULL,
  `authority_id` INT(11) NOT NULL,

  -- Contact Type
  `contact_type` ENUM('personal', 'company') DEFAULT 'personal',

  -- Email
  `email` VARCHAR(255) NOT NULL,
  `secondary_emails` JSON NULL,

  -- Name
  `display_name` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NULL,

  -- Organisation
  `company` VARCHAR(255) NULL,
  `department` VARCHAR(255) NULL,
  `position` VARCHAR(255) NULL,

  -- Contact Details
  `phone` VARCHAR(50) NULL,
  `mobile` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `website` VARCHAR(255) NULL,

  -- Categorization
  `tags` JSON NULL,
  `category` VARCHAR(100) NULL,
  `is_favorite` TINYINT(1) DEFAULT 0,

  -- Meta
  `notes` TEXT NULL,
  `avatar_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_favorite` (`is_favorite`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EXTEND EXISTING TABLES
-- Note: IF NOT EXISTS removed - migration system handles duplicate errors
-- =====================================================

-- Extend kdd_authorities
ALTER TABLE `kdd_authorities`
ADD COLUMN `authority_type` ENUM('faction', 'company', 'government', 'private') DEFAULT 'private';

ALTER TABLE `kdd_authorities`
ADD COLUMN `has_custom_domain` TINYINT(1) DEFAULT 0;

ALTER TABLE `kdd_authorities`
ADD COLUMN `mail_domain` VARCHAR(191) NULL;

ALTER TABLE `kdd_authorities`
ADD COLUMN `default_mail_quota` INT(11) DEFAULT 5 COMMENT 'Wie viele Mails kann ein User dieser Authority haben?';

-- Extend kdd_users
ALTER TABLE `kdd_users`
ADD COLUMN `linked_mail_account_id` INT(11) NULL COMMENT 'Verknüpfte Mail-Adresse';

ALTER TABLE `kdd_users`
ADD KEY `idx_linked_mail` (`linked_mail_account_id`);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Mail Domain
INSERT INTO `kdd_mail_domains` (`domain`, `domain_type`, `authority_id`, `is_active`, `verified`, `display_name`)
VALUES ('mail.ls', 'default', NULL, 1, 1, 'K-Systems Mail')
ON DUPLICATE KEY UPDATE `domain` = 'mail.ls';

-- =====================================================
-- TRIGGERS
-- Note: Triggers must be created separately after this migration
-- Run: docker exec -i k-systems-db mysql -uroot -p k_systems < backend/migrations/mail_triggers_manual.sql
-- =====================================================

-- =====================================================
-- INDICES FOR PERFORMANCE
-- =====================================================

-- Additional composite indices for common queries
ALTER TABLE `kdd_mail_recipients`
ADD KEY `idx_user_status` (`recipient_user_id`, `is_read`, `is_deleted`);

ALTER TABLE `kdd_mails`
ADD KEY `idx_sent_date` (`is_sent`, `sent_at`);

-- =====================================================
-- END OF MIGRATION
-- =====================================================
