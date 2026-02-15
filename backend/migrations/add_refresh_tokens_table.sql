-- Migration: Add Refresh Tokens Table
-- Date: 2025-01-01
-- Purpose: Support refresh token authentication for long-lived sessions

CREATE TABLE IF NOT EXISTS `kdd_refresh_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `authority_id` INT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `is_revoked` TINYINT(1) DEFAULT 0,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,

  INDEX `idx_token` (`token`),
  INDEX `idx_user` (`user_id`, `authority_id`),
  INDEX `idx_expires` (`expires_at`),
  INDEX `idx_revoked` (`is_revoked`),

  FOREIGN KEY (`user_id`) REFERENCES `kdd_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for cleanup queries (delete expired tokens)
CREATE INDEX `idx_cleanup` ON `kdd_refresh_tokens` (`expires_at`, `is_revoked`);

-- Comments
ALTER TABLE `kdd_refresh_tokens`
  COMMENT = 'Stores refresh tokens for JWT authentication system';
