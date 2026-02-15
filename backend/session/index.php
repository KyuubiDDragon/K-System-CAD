<?php
/**
 * Session Management API
 * Handles multi-device session management
 *
 * Endpoints:
 * - GET /session/ - Get current user's active sessions
 * - GET /session/user/{user_id} - Get sessions for specific user (Admin only)
 * - DELETE /session/{session_id} - Logout specific session
 * - DELETE /session/other - Logout all other sessions (keep current)
 * - DELETE /session/user/{user_id} - Logout all sessions for user (Admin only)
 */

declare(strict_types=1);

// Bootstrap
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_check.php';

// Session manager
require_once __DIR__ . '/../utils/session_manager.php';
require_once __DIR__ . '/../helpers/PermissionManager.php';

// Get current user from JWT
$userId = $decoded_jwt->userId ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if (!$userId || !$authorityId) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid authentication']);
    exit();
}

// Get current JWT token for identifying current session
$jwt = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $jwt = $matches[1];
    }
}
if (!$jwt && isset($_COOKIE['auth_token'])) {
    $jwt = $_COOKIE['auth_token'];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// Route based on action parameter (consistent with other endpoints)
switch ($action) {
    case 'getSessions':
        // GET /session/?action=getSessions
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        getSessions($pdo, $userId, $jwt);
        break;

    case 'getHistory':
        // GET /session/?action=getHistory
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        getLoginHistory($pdo, $userId);
        break;

    case 'getSuspicious':
        // GET /session/?action=getSuspicious
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        getSuspiciousLoginsEndpoint($pdo, $userId);
        break;

    case 'getUserSessions':
        // GET /session/?action=getUserSessions&userId=123 (Admin)
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $targetUserId = (int)($_REQUEST['userId'] ?? 0);
        if (!$targetUserId) {
            http_response_code(400);
            echo json_encode(['error' => 'userId parameter required']);
            exit;
        }
        getUserSessionsAdmin($pdo, $userId, $authorityId, $targetUserId);
        break;

    case 'logoutSession':
        // POST /session/?action=logoutSession&sessionId=123
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $sessionId = (int)($_REQUEST['sessionId'] ?? 0);
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['error' => 'sessionId parameter required']);
            exit;
        }
        revokeUserSession($pdo, $userId, $sessionId);
        break;

    case 'logoutOther':
        // POST /session/?action=logoutOther
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        revokeOtherUserSessions($pdo, $userId, $jwt);
        break;

    case 'logoutUser':
        // POST /session/?action=logoutUser&userId=123 (Admin)
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $targetUserId = (int)($_REQUEST['userId'] ?? 0);
        if (!$targetUserId) {
            http_response_code(400);
            echo json_encode(['error' => 'userId parameter required']);
            exit;
        }
        revokeAllUserSessionsAdmin($pdo, $userId, $authorityId, $targetUserId);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action specified']);
}

/**
 * Get current user's active sessions
 */
function getSessions(PDO $pdo, int $userId, string $currentJwt): void
{
    try {
        $sessions = getUserSessions($pdo, $userId, $currentJwt);

        http_response_code(200);
        echo json_encode([
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error getting sessions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve sessions']);
    }
}

/**
 * Get sessions for specific user (Admin only)
 */
function getUserSessionsAdmin(PDO $pdo, int $requestingUserId, int $authorityId, int $targetUserId): void
{
    try {
        // Check if requesting user has admin permission
        $permissionManager = new PermissionManager($pdo, $authorityId);
        if (!$permissionManager->hasModulePermission($requestingUserId, 'admin', 'users', 'read')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied: Admin access required']);
            return;
        }

        // Verify target user belongs to same authority
        $stmt = $pdo->prepare("SELECT authority_id FROM kdd_users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['authority_id'] != $authorityId) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $sessions = getUserSessions($pdo, $targetUserId);

        http_response_code(200);
        echo json_encode([
            'user_id' => $targetUserId,
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error getting user sessions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve user sessions']);
    }
}

/**
 * Revoke specific session
 */
function revokeUserSession(PDO $pdo, int $userId, int $sessionId): void
{
    try {
        $success = revokeSession($pdo, $sessionId, $userId);

        if ($success) {
            http_response_code(200);
            echo json_encode([
                'message' => 'Session logged out successfully',
                'session_id' => $sessionId
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Session not found or permission denied']);
        }
    } catch (Exception $e) {
        error_log("[SESSION API] Error revoking session: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to logout session']);
    }
}

/**
 * Revoke all other sessions (keep current)
 */
function revokeOtherUserSessions(PDO $pdo, int $userId, string $currentJwt): void
{
    try {
        $count = revokeOtherSessions($pdo, $userId, $currentJwt);

        http_response_code(200);
        echo json_encode([
            'message' => 'Other sessions logged out successfully',
            'sessions_revoked' => $count
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error revoking other sessions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to logout other sessions']);
    }
}

/**
 * Revoke all sessions for a user (Admin only)
 */
function revokeAllUserSessionsAdmin(PDO $pdo, int $requestingUserId, int $authorityId, int $targetUserId): void
{
    try {
        // Check if requesting user has admin permission
        $permissionManager = new PermissionManager($pdo, $authorityId);
        if (!$permissionManager->hasModulePermission($requestingUserId, 'admin', 'users', 'write')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied: Admin access required']);
            return;
        }

        // Verify target user belongs to same authority
        $stmt = $pdo->prepare("SELECT authority_id, username FROM kdd_users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['authority_id'] != $authorityId) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $count = revokeAllUserSessions($pdo, $targetUserId);

        http_response_code(200);
        echo json_encode([
            'message' => 'All sessions logged out successfully',
            'user_id' => $targetUserId,
            'username' => $user['username'],
            'sessions_revoked' => $count
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error revoking all user sessions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to logout user']);
    }
}

/**
 * Get login history for current user (all sessions)
 */
function getLoginHistory(PDO $pdo, int $userId): void
{
    try {
        $history = getUserLoginHistory($pdo, $userId, 50);

        http_response_code(200);
        echo json_encode([
            'history' => $history,
            'count' => count($history)
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error getting login history: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve login history']);
    }
}

/**
 * Get suspicious login attempts for current user
 */
function getSuspiciousLoginsEndpoint(PDO $pdo, int $userId): void
{
    try {
        require_once __DIR__ . '/../utils/session_manager.php';
        $suspicious = getSuspiciousLogins($pdo, $userId);

        http_response_code(200);
        echo json_encode([
            'suspicious' => $suspicious,
            'count' => count($suspicious)
        ]);
    } catch (Exception $e) {
        error_log("[SESSION API] Error getting suspicious logins: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve suspicious logins']);
    }
}
