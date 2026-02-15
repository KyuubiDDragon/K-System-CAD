<?php
/**
 * JWT Token Manager
 * Handles storage, validation, and revocation of JWT tokens in database
 */

declare(strict_types=1);

/**
 * Save JWT token to database
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @param string $jwt The JWT token string
 * @param int $expiresAt Unix timestamp when token expires
 * @return bool True on success
 */
function saveJwtToken(PDO $pdo, int $userId, int $authorityId, string $jwt, int $expiresAt): bool
{
    try {
        // Hash the token (don't store plain token for security)
        $tokenHash = hash('sha256', $jwt);

        // Get user agent and IP
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        // Convert timestamp to datetime
        $expiresAtDate = date('Y-m-d H:i:s', $expiresAt);

        $sql = "INSERT INTO kdd_jwt_tokens
                (user_id, authority_id, token_hash, expires_at, device_info, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $userId,
            $authorityId,
            $tokenHash,
            $expiresAtDate,
            $deviceInfo,
            $ipAddress
        ]);

        if ($result) {
            error_log("[JWT Token Manager] Token saved for user $userId");
        }

        return $result;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error saving token: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate if JWT token exists and is not revoked
 *
 * @param PDO $pdo Database connection
 * @param string $jwt The JWT token string
 * @return bool True if token is valid and not revoked
 */
function validateJwtToken(PDO $pdo, string $jwt): bool
{
    try {
        $tokenHash = hash('sha256', $jwt);

        $sql = "SELECT id, expires_at, is_revoked
                FROM kdd_jwt_tokens
                WHERE token_hash = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tokenHash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) {
            error_log("[JWT Token Manager] Token not found in database");
            return false;
        }

        if ($token['is_revoked']) {
            error_log("[JWT Token Manager] Token is revoked");
            return false;
        }

        $expiresAt = strtotime($token['expires_at']);
        if ($expiresAt < time()) {
            error_log("[JWT Token Manager] Token has expired");
            return false;
        }

        // Update last_used_at
        $updateSql = "UPDATE kdd_jwt_tokens SET last_used_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$token['id']]);

        return true;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error validating token: " . $e->getMessage());
        return false;
    }
}

/**
 * Revoke specific JWT token (set is_revoked = 1)
 * Better than delete for audit trail and multi-device support
 *
 * @param PDO $pdo Database connection
 * @param string $jwt The JWT token string
 * @return bool True on success
 */
function revokeJwtToken(PDO $pdo, string $jwt): bool
{
    try {
        $tokenHash = hash('sha256', $jwt);

        $sql = "UPDATE kdd_jwt_tokens SET is_revoked = 1 WHERE token_hash = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$tokenHash]);

        if ($result && $stmt->rowCount() > 0) {
            error_log("[JWT Token Manager] Token revoked (hash: " . substr($tokenHash, 0, 16) . "...)");
        }

        return $result;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error revoking token: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete specific JWT token from database
 * Use revokeJwtToken() instead for better audit trail
 *
 * @param PDO $pdo Database connection
 * @param string $jwt The JWT token string
 * @return bool True on success
 */
function deleteJwtToken(PDO $pdo, string $jwt): bool
{
    try {
        $tokenHash = hash('sha256', $jwt);

        $sql = "DELETE FROM kdd_jwt_tokens WHERE token_hash = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$tokenHash]);

        if ($result) {
            error_log("[JWT Token Manager] Token deleted");
        }

        return $result;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error deleting token: " . $e->getMessage());
        return false;
    }
}

/**
 * Revoke all tokens for a specific user
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return int Number of tokens revoked
 */
function revokeAllUserTokens(PDO $pdo, int $userId): int
{
    try {
        $sql = "UPDATE kdd_jwt_tokens SET is_revoked = 1 WHERE user_id = ? AND is_revoked = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        $count = $stmt->rowCount();
        error_log("[JWT Token Manager] Revoked $count tokens for user $userId");

        return $count;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error revoking tokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Delete all tokens for a specific user
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return int Number of tokens deleted
 */
function deleteAllUserTokens(PDO $pdo, int $userId): int
{
    try {
        $sql = "DELETE FROM kdd_jwt_tokens WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        $count = $stmt->rowCount();
        error_log("[JWT Token Manager] Deleted $count tokens for user $userId");

        return $count;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error deleting user tokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Clean up expired tokens from database
 * Should be called periodically (e.g., via cron job)
 *
 * @param PDO $pdo Database connection
 * @return int Number of tokens deleted
 */
function cleanupExpiredTokens(PDO $pdo): int
{
    try {
        $sql = "DELETE FROM kdd_jwt_tokens WHERE expires_at < NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $count = $stmt->rowCount();
        if ($count > 0) {
            error_log("[JWT Token Manager] Cleaned up $count expired tokens");
        }

        return $count;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error cleaning up tokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get all active tokens for a user (for session management UI)
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array List of active tokens with metadata and parsed device info
 */
function getUserActiveTokens(PDO $pdo, int $userId): array
{
    try {
        $sql = "SELECT id, device_info, ip_address, created_at, last_used_at, expires_at
                FROM kdd_jwt_tokens
                WHERE user_id = ? AND is_revoked = 0 AND expires_at > NOW()
                ORDER BY last_used_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse user agent for each token
        require_once __DIR__ . '/user_agent_parser.php';

        foreach ($tokens as &$token) {
            $parsed = parseUserAgent($token['device_info']);
            $token['device_type'] = $parsed['device_type'];
            $token['device_icon'] = $parsed['device_icon'];
            $token['os'] = $parsed['os'];
            $token['browser'] = $parsed['browser'];
            $token['device_description'] = $parsed['full_string'];
        }

        return $tokens;
    } catch (PDOException $e) {
        error_log("[JWT Token Manager] Error getting user tokens: " . $e->getMessage());
        return [];
    }
}
