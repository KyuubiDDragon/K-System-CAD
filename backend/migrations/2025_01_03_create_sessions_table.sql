-- Create sessions table for JWT token tracking
-- Date: 2025-01-03
-- Description: Enables session management - tracking active JWT tokens per user/device
--              Allows admins to logout users and users to manage their own devices

CREATE TABLE IF NOT EXISTS kdd_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    authority_id INT NOT NULL,

    -- Token identification
    token_hash VARCHAR(255) UNIQUE NOT NULL COMMENT 'SHA256 hash of JWT token',
    jti VARCHAR(255) UNIQUE NOT NULL COMMENT 'JWT ID (unique identifier)',

    -- Device/Client information
    device_name VARCHAR(255) DEFAULT NULL COMMENT 'e.g., Chrome on Windows',
    device_type VARCHAR(50) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
    user_agent TEXT DEFAULT NULL,

    -- Activity tracking
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,

    -- Status
    is_active TINYINT(1) DEFAULT 1 COMMENT '0 = logged out, 1 = active',

    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES kdd_users(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,

    -- Indexes for performance
    INDEX idx_user (user_id, is_active),
    INDEX idx_token_hash (token_hash),
    INDEX idx_jti (jti),
    INDEX idx_authority (authority_id),
    INDEX idx_active_expires (is_active, expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cleanup procedure for VERY old sessions only (keep for audit trail)
-- Only deletes sessions older than 90 days for storage management
-- Active audit trail is preserved!
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS cleanup_old_sessions()
BEGIN
    -- Only delete sessions older than 90 days (audit retention period)
    DELETE FROM kdd_sessions
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

    SELECT ROW_COUNT() as deleted_sessions;
END //
DELIMITER ;

-- View for active sessions only (for UI)
CREATE OR REPLACE VIEW kdd_active_sessions AS
SELECT
    s.*,
    u.username,
    u.email
FROM kdd_sessions s
JOIN kdd_users u ON s.user_id = u.id
WHERE s.is_active = 1
AND s.expires_at > NOW();

-- View for login history (all sessions, for audit)
CREATE OR REPLACE VIEW kdd_login_history AS
SELECT
    s.id,
    s.user_id,
    u.username,
    u.email,
    s.device_name,
    s.device_type,
    s.ip_address,
    s.created_at as login_time,
    s.last_activity,
    s.expires_at,
    s.is_active,
    CASE
        WHEN s.is_active = 1 AND s.expires_at > NOW() THEN 'active'
        WHEN s.is_active = 0 THEN 'logged_out'
        ELSE 'expired'
    END as status
FROM kdd_sessions s
JOIN kdd_users u ON s.user_id = u.id
ORDER BY s.created_at DESC;
