<?php
/**
 * Refresh Token Helper Functions
 * Manages refresh token lifecycle: creation, validation, rotation, cleanup
 */

// Minimal file - no global error_log() calls yet

/**
 * Generate a cryptographically secure refresh token
 *
 * @return string 64-character random token
 */
function generateRefreshToken(): string {
    return bin2hex(random_bytes(32)); // 64 characters
}


/**
 * Create and store a new refresh token
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @param int $expirationDays Token validity in days (default: 7)
 * @return string The generated refresh token
 */
function createRefreshToken(PDO $pdo, int $userId, int $authorityId, int $expirationDays = 7): string {
    $token = generateRefreshToken();
    $expiresAt = date('Y-m-d H:i:s', time() + ($expirationDays * 24 * 60 * 60));

    // Get client info
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    error_log("[REFRESH_TOKEN] Preparing to insert: user={$userId}, authority={$authorityId}, expires={$expiresAt}, ip={$ipAddress}");

    try {
        $stmt = $pdo->prepare("
            INSERT INTO kdd_refresh_tokens
            (token, user_id, authority_id, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        error_log("[REFRESH_TOKEN] Statement prepared, executing...");

        $result = $stmt->execute([$token, $userId, $authorityId, $expiresAt, $ipAddress, $userAgent]);

        error_log("[REFRESH_TOKEN] Execute result: " . ($result ? "success" : "failed"));

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("[REFRESH_TOKEN] PDO Error: " . json_encode($errorInfo));
            throw new \PDOException("Failed to insert refresh token: " . $errorInfo[2]);
        }

        error_log("[REFRESH_TOKEN] Created for user {$userId}, expires: {$expiresAt}");

        return $token;
    } catch (\PDOException $e) {
        error_log("[REFRESH_TOKEN] PDO Exception: " . $e->getMessage());
        error_log("[REFRESH_TOKEN] Error Code: " . $e->getCode());
        throw $e;
    }
}


/**
 * Validate a refresh token
 *
 * @param PDO $pdo Database connection
 * @param string $token Refresh token to validate
 * @return array|null Token data if valid, null if invalid/expired/revoked
 */
function validateRefreshToken(PDO $pdo, string $token): ?array {
    $stmt = $pdo->prepare("
        SELECT id, user_id, authority_id, expires_at, is_revoked
        FROM kdd_refresh_tokens
        WHERE token = ?
    ");

    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        error_log("[REFRESH_TOKEN] Token not found in database");
        return null;
    }

    // Check if revoked
    if ($tokenData['is_revoked']) {
        error_log("[REFRESH_TOKEN] Token is revoked");
        return null;
    }

    // Check if expired
    if (strtotime($tokenData['expires_at']) < time()) {
        error_log("[REFRESH_TOKEN] Token expired at {$tokenData['expires_at']}");
        return null;
    }

    // Update last_used_at
    $updateStmt = $pdo->prepare("
        UPDATE kdd_refresh_tokens
        SET last_used_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$tokenData['id']]);

    error_log("[REFRESH_TOKEN] Token validated for user {$tokenData['user_id']}");

    return $tokenData;
}


/**
 * Revoke a refresh token (security measure)
 *
 * @param PDO $pdo Database connection
 * @param string $token Token to revoke
 * @return bool Success
 */
function revokeRefreshToken(PDO $pdo, string $token): bool {
    $stmt = $pdo->prepare("
        UPDATE kdd_refresh_tokens
        SET is_revoked = 1, revoked_at = NOW()
        WHERE token = ?
    ");

    $result = $stmt->execute([$token]);

    if ($result) {
        error_log("[REFRESH_TOKEN] Token revoked: {$token}");
    }

    return $result;
}

/**
 * Revoke all refresh tokens for a user (e.g., on password change or logout from all devices)
 * Renamed to avoid conflict with jwt_token_manager.php
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return int Number of tokens revoked
 */
function revokeAllUserRefreshTokens(PDO $pdo, int $userId, int $authorityId): int {
    $stmt = $pdo->prepare("
        UPDATE kdd_refresh_tokens
        SET is_revoked = 1, revoked_at = NOW()
        WHERE user_id = ? AND authority_id = ? AND is_revoked = 0
    ");

    $stmt->execute([$userId, $authorityId]);
    $count = $stmt->rowCount();

    error_log("[REFRESH_TOKEN] Revoked {$count} refresh tokens for user {$userId}");

    return $count;
}

/**
 * Token Rotation: Revoke old token and create new one
 * Security best practice: prevents token reuse attacks
 *
 * @param PDO $pdo Database connection
 * @param string $oldToken Old refresh token to revoke
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @param int $expirationDays New token validity
 * @return string New refresh token
 */
function rotateRefreshToken(PDO $pdo, string $oldToken, int $userId, int $authorityId, int $expirationDays = 7): string {
    // Revoke old token
    revokeRefreshToken($pdo, $oldToken);

    // Create new token
    $newToken = createRefreshToken($pdo, $userId, $authorityId, $expirationDays);

    error_log("[REFRESH_TOKEN] Rotated token for user {$userId}");

    return $newToken;
}

/**
 * Cleanup expired and revoked tokens (run periodically via cron)
 * Renamed to avoid conflict with jwt_token_manager.php
 *
 * @param PDO $pdo Database connection
 * @param int $olderThanDays Delete tokens older than X days (default: 30)
 * @return int Number of tokens deleted
 */
function cleanupExpiredRefreshTokens(PDO $pdo, int $olderThanDays = 30): int {
    $cutoffDate = date('Y-m-d H:i:s', time() - ($olderThanDays * 24 * 60 * 60));

    $stmt = $pdo->prepare("
        DELETE FROM kdd_refresh_tokens
        WHERE (expires_at < ? OR is_revoked = 1)
        AND created_at < ?
    ");

    $stmt->execute([date('Y-m-d H:i:s'), $cutoffDate]);
    $count = $stmt->rowCount();

    error_log("[REFRESH_TOKEN] Cleanup: Deleted {$count} old refresh tokens");

    return $count;
}

/**
 * Get all active refresh tokens for a user (for "active sessions" feature)
 * Renamed to avoid conflict with jwt_token_manager.php
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return array Array of active tokens with metadata
 */
function getUserActiveRefreshTokens(PDO $pdo, int $userId, int $authorityId): array {
    $stmt = $pdo->prepare("
        SELECT
            id,
            SUBSTRING(token, 1, 8) as token_preview,
            created_at,
            last_used_at,
            expires_at,
            ip_address,
            user_agent
        FROM kdd_refresh_tokens
        WHERE user_id = ?
        AND authority_id = ?
        AND is_revoked = 0
        AND expires_at > NOW()
        ORDER BY last_used_at DESC
    ");

    $stmt->execute([$userId, $authorityId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

