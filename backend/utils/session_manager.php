<?php
/**
 * Session Manager
 * Handles JWT token-based session tracking for multi-device management
 */

/**
 * Get real client IP address (handles reverse proxy headers)
 * Checks X-Forwarded-For, X-Real-IP headers for Docker/proxy setups
 */
function getRealClientIP(): ?string
{
    // Priority order for IP detection
    $headers = [
        'HTTP_X_FORWARDED_FOR',  // Most common proxy header
        'HTTP_X_REAL_IP',        // Alternative proxy header
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'            // Fallback (Docker container IP)
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];

            // X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2, ...)
            // We want the first one (original client)
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }

            // Validate IP format
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }

            // Allow private IPs for local development/testing
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return null;
}

/**
 * Parse device information from User-Agent string
 */
function parseDeviceInfo(string $userAgent): array
{
    $deviceInfo = [
        'device_name' => 'Unknown Device',
        'device_type' => 'desktop'
    ];

    $userAgent = strtolower($userAgent);

    // Detect OS
    $os = 'Unknown OS';
    if (strpos($userAgent, 'windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'mac os x') !== false || strpos($userAgent, 'macos') !== false) {
        $os = 'macOS';
    } elseif (strpos($userAgent, 'linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'android') !== false) {
        $os = 'Android';
        $deviceInfo['device_type'] = 'mobile';
    } elseif (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
        $os = strpos($userAgent, 'ipad') !== false ? 'iPad' : 'iPhone';
        $deviceInfo['device_type'] = strpos($userAgent, 'ipad') !== false ? 'tablet' : 'mobile';
    }

    // Detect Browser
    $browser = 'Unknown Browser';
    if (strpos($userAgent, 'edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'opr') !== false || strpos($userAgent, 'opera') !== false) {
        $browser = 'Opera';
    } elseif (strpos($userAgent, 'fivem') !== false || strpos($userAgent, 'citizenfx') !== false) {
        $browser = 'FiveM';
        $os = 'GTA V';
    }

    $deviceInfo['device_name'] = "$browser on $os";

    return $deviceInfo;
}

/**
 * Save session to database after login
 */
function saveSession(
    PDO $pdo,
    int $userId,
    int $authorityId,
    string $jwt,
    string $jti,
    int $expiresAt
): bool {
    try {
        $tokenHash = hash('sha256', $jwt);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = getRealClientIP();

        // Parse device info
        $deviceInfo = parseDeviceInfo($userAgent);

        $sql = "INSERT INTO kdd_sessions
                (user_id, authority_id, token_hash, jti, device_name, device_type, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $authorityId,
            $tokenHash,
            $jti,
            $deviceInfo['device_name'],
            $deviceInfo['device_type'],
            $ipAddress,
            $userAgent,
            date('Y-m-d H:i:s', $expiresAt)
        ]);

        error_log("[SESSION] Session saved: user_id=$userId, device={$deviceInfo['device_name']}");
        return true;
    } catch (PDOException $e) {
        error_log("[SESSION] Error saving session: " . $e->getMessage());
        return false;
    }
}

/**
 * Update existing session OR create new one (for token refresh)
 * Only creates a new session if IP or device changed
 * @param string|null $oldTokenHash Optional: hash of old token to revoke if creating new session
 */
function updateOrCreateSession(
    PDO $pdo,
    int $userId,
    int $authorityId,
    string $jwt,
    string $jti,
    int $expiresAt,
    ?string $oldTokenHash = null
): bool {
    try {
        $tokenHash = hash('sha256', $jwt);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = getRealClientIP();

        // Parse device info
        $deviceInfo = parseDeviceInfo($userAgent);

        // Check if there's an active session with same IP and device for this user
        $checkSql = "SELECT id FROM kdd_sessions
                     WHERE user_id = ?
                     AND ip_address = ?
                     AND device_type = ?
                     AND is_active = 1
                     AND expires_at > NOW()
                     ORDER BY last_activity DESC
                     LIMIT 1";

        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$userId, $ipAddress, $deviceInfo['device_type']]);
        $existingSession = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingSession) {
            // UPDATE existing session with new token info (same device/IP)
            $updateSql = "UPDATE kdd_sessions
                         SET token_hash = ?,
                             jti = ?,
                             expires_at = ?,
                             last_activity = CURRENT_TIMESTAMP,
                             user_agent = ?,
                             device_name = ?
                         WHERE id = ?";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                $tokenHash,
                $jti,
                date('Y-m-d H:i:s', $expiresAt),
                $userAgent,
                $deviceInfo['device_name'],
                $existingSession['id']
            ]);

            error_log("[SESSION] Session UPDATED (token refresh): session_id={$existingSession['id']}, user_id=$userId, ip=$ipAddress");
            return true;
        } else {
            // IP or device changed - CREATE NEW session and revoke old one

            // Revoke old session if provided
            if ($oldTokenHash) {
                try {
                    $revokeSql = "UPDATE kdd_sessions SET is_active = 0 WHERE token_hash = ? AND user_id = ?";
                    $revokeStmt = $pdo->prepare($revokeSql);
                    $revokeStmt->execute([$oldTokenHash, $userId]);
                    error_log("[SESSION] Old session revoked (IP/device changed): user_id=$userId");
                } catch (\PDOException $e) {
                    error_log("[SESSION] Could not revoke old session: " . $e->getMessage());
                }
            }

            // CREATE NEW session
            $insertSql = "INSERT INTO kdd_sessions
                         (user_id, authority_id, token_hash, jti, device_name, device_type, ip_address, user_agent, expires_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                $userId,
                $authorityId,
                $tokenHash,
                $jti,
                $deviceInfo['device_name'],
                $deviceInfo['device_type'],
                $ipAddress,
                $userAgent,
                date('Y-m-d H:i:s', $expiresAt)
            ]);

            error_log("[SESSION] NEW session created (IP/device changed): user_id=$userId, ip=$ipAddress, device={$deviceInfo['device_name']}");
            return true;
        }
    } catch (PDOException $e) {
        error_log("[SESSION] Error in updateOrCreateSession: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate session exists and is active
 */
function validateSession(PDO $pdo, string $jwt): bool
{
    try {
        $tokenHash = hash('sha256', $jwt);

        $sql = "SELECT id, is_active, expires_at
                FROM kdd_sessions
                WHERE token_hash = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tokenHash]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            error_log("[SESSION] Session not found in database");
            return false;
        }

        if ($session['is_active'] != 1) {
            error_log("[SESSION] Session is inactive (logged out)");
            return false;
        }

        if (strtotime($session['expires_at']) < time()) {
            error_log("[SESSION] Session expired");
            return false;
        }

        // Update last_activity
        updateSessionActivity($pdo, $tokenHash);

        return true;
    } catch (PDOException $e) {
        error_log("[SESSION] Error validating session: " . $e->getMessage());
        return false;
    }
}

/**
 * Update session last_activity timestamp
 */
function updateSessionActivity(PDO $pdo, string $tokenHash): void
{
    try {
        $sql = "UPDATE kdd_sessions
                SET last_activity = CURRENT_TIMESTAMP
                WHERE token_hash = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tokenHash]);
    } catch (PDOException $e) {
        error_log("[SESSION] Error updating activity: " . $e->getMessage());
    }
}

/**
 * Revoke/logout a specific session
 */
function revokeSession(PDO $pdo, int $sessionId, int $requestingUserId): bool
{
    try {
        // Security: Verify the requesting user owns this session or is admin
        $sql = "SELECT user_id FROM kdd_sessions WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            error_log("[SESSION] Session not found: $sessionId");
            return false;
        }

        // User can only revoke their own sessions (admin check done in API endpoint)
        if ($session['user_id'] != $requestingUserId) {
            error_log("[SESSION] User $requestingUserId tried to revoke session of user {$session['user_id']}");
            return false;
        }

        // Mark as inactive
        $sql = "UPDATE kdd_sessions SET is_active = 0 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId]);

        error_log("[SESSION] Session revoked: $sessionId");
        return true;
    } catch (PDOException $e) {
        error_log("[SESSION] Error revoking session: " . $e->getMessage());
        return false;
    }
}

/**
 * Revoke all sessions for a user except current
 */
function revokeOtherSessions(PDO $pdo, int $userId, string $currentJwt): int
{
    try {
        $currentTokenHash = hash('sha256', $currentJwt);

        $sql = "UPDATE kdd_sessions
                SET is_active = 0
                WHERE user_id = ?
                AND token_hash != ?
                AND is_active = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $currentTokenHash]);

        $count = $stmt->rowCount();
        error_log("[SESSION] Revoked $count other sessions for user $userId");

        return $count;
    } catch (PDOException $e) {
        error_log("[SESSION] Error revoking other sessions: " . $e->getMessage());
        return 0;
    }
}

/**
 * Revoke ALL sessions for a user (admin action)
 */
function revokeAllUserSessions(PDO $pdo, int $userId): int
{
    try {
        $sql = "UPDATE kdd_sessions
                SET is_active = 0
                WHERE user_id = ?
                AND is_active = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        $count = $stmt->rowCount();
        error_log("[SESSION] Admin revoked ALL $count sessions for user $userId");

        return $count;
    } catch (PDOException $e) {
        error_log("[SESSION] Error revoking all user sessions: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get active sessions for a user
 */
function getUserSessions(PDO $pdo, int $userId, string $currentJwt = null): array
{
    try {
        $currentTokenHash = $currentJwt ? hash('sha256', $currentJwt) : null;

        $sql = "SELECT id, device_name, device_type, ip_address, last_activity, created_at, expires_at, token_hash
                FROM kdd_sessions
                WHERE user_id = ?
                AND is_active = 1
                AND expires_at > NOW()
                ORDER BY last_activity DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark current session
        foreach ($sessions as &$session) {
            $session['is_current'] = ($currentTokenHash && $session['token_hash'] === $currentTokenHash);
            unset($session['token_hash']); // Don't expose token hash to frontend
        }

        return $sessions;
    } catch (PDOException $e) {
        error_log("[SESSION] Error getting user sessions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all sessions for an authority (admin)
 */
function getAuthoritySessions(PDO $pdo, int $authorityId, int $limit = 100): array
{
    try {
        $sql = "SELECT s.id, s.user_id, u.username, s.device_name, s.device_type,
                       s.ip_address, s.last_activity, s.created_at, s.expires_at
                FROM kdd_sessions s
                JOIN kdd_users u ON s.user_id = u.id
                WHERE s.authority_id = ?
                AND s.is_active = 1
                AND s.expires_at > NOW()
                ORDER BY s.last_activity DESC
                LIMIT ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[SESSION] Error getting authority sessions: " . $e->getMessage());
        return [];
    }
}

/**
 * Clean up VERY old sessions (cron job)
 * Only deletes sessions older than 90 days for storage management
 * Keeps recent history for security audit trail
 */
function cleanupOldSessions(PDO $pdo): int
{
    try {
        // Delete ONLY very old sessions (90+ days) - keep audit trail!
        $sql = "DELETE FROM kdd_sessions
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $count = $stmt->rowCount();
        error_log("[SESSION] Cleaned up $count old sessions (90+ days)");

        return $count;
    } catch (PDOException $e) {
        error_log("[SESSION] Error cleaning up old sessions: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get login history for a user (all sessions, for audit)
 * Shows: active, logged out, and expired sessions
 */
function getUserLoginHistory(PDO $pdo, int $userId, int $limit = 50): array
{
    try {
        $sql = "SELECT
                    id,
                    device_name,
                    device_type,
                    ip_address,
                    created_at as login_time,
                    last_activity,
                    expires_at,
                    is_active,
                    CASE
                        WHEN is_active = 1 AND expires_at > NOW() THEN 'active'
                        WHEN is_active = 0 THEN 'logged_out'
                        ELSE 'expired'
                    END as status
                FROM kdd_sessions
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[SESSION] Error getting login history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get suspicious login attempts (new IPs, unusual locations)
 */
function getSuspiciousLogins(PDO $pdo, int $userId): array
{
    try {
        // Get logins from new IPs in last 7 days
        $sql = "SELECT s1.id, s1.device_name, s1.ip_address, s1.created_at
                FROM kdd_sessions s1
                WHERE s1.user_id = ?
                AND s1.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM kdd_sessions s2
                    WHERE s2.user_id = s1.user_id
                    AND s2.ip_address = s1.ip_address
                    AND s2.created_at < s1.created_at
                    AND s2.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                )
                ORDER BY s1.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[SESSION] Error checking suspicious logins: " . $e->getMessage());
        return [];
    }
}
