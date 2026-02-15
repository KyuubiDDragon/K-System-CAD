<?php
/**
 * Mail Module: access_rights.php
 * Handles granular per-mail access rights
 * Functions: grantMailAccess, revokeMailAccess, getMailAccessList, updateMailAccess, checkMailAccess
 */
declare(strict_types=1);

/**
 * Grant access to a mail for specific user or role
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function grantMailAccess(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $mailId = (int)($data['mail_id'] ?? 0);
    $targetUserId = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $targetRoleId = isset($data['role_id']) ? (int)$data['role_id'] : null;
    $canRead = (bool)($data['can_read'] ?? true);
    $canReply = (bool)($data['can_reply'] ?? false);
    $canForward = (bool)($data['can_forward'] ?? false);
    $canDelete = (bool)($data['can_delete'] ?? false);

    // Validation
    if ($mailId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail ID required.']);
        return;
    }

    if ($targetUserId === null && $targetRoleId === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Either user_id or role_id must be provided.']);
        return;
    }

    if ($targetUserId !== null && $targetRoleId !== null) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot grant access to both user and role simultaneously.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verify mail exists and belongs to authority
        $checkSql = "SELECT id FROM kdd_mails
                     WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$mailId, $authorityId]);
        $mail = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$mail) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            $pdo->rollBack();
            return;
        }

        // If granting to user, verify user exists in same authority
        if ($targetUserId !== null) {
            $userCheckSql = "SELECT id FROM kdd_users WHERE id = ? AND authority_id = ?";
            $userCheckStmt = $pdo->prepare($userCheckSql);
            $userCheckStmt->execute([$targetUserId, $authorityId]);

            if (!$userCheckStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found in authority.']);
                $pdo->rollBack();
                return;
            }
        }

        // If granting to role, verify role exists
        if ($targetRoleId !== null) {
            $roleCheckSql = "SELECT id FROM kdd_roles WHERE id = ?";
            $roleCheckStmt = $pdo->prepare($roleCheckSql);
            $roleCheckStmt->execute([$targetRoleId]);

            if (!$roleCheckStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Role not found.']);
                $pdo->rollBack();
                return;
            }
        }

        // Check if access already exists
        $existingSql = "SELECT id, is_active FROM kdd_mail_access_rights
                        WHERE mail_id = ? AND authority_id = ?
                        AND " . ($targetUserId ? "user_id = ?" : "role_id = ?");
        $existingStmt = $pdo->prepare($existingSql);
        $existingStmt->execute(array_filter([$mailId, $authorityId, $targetUserId ?? $targetRoleId]));
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        $accessId = null;
        $isUpdate = false;

        if ($existing) {
            // Update existing access
            $updateSql = "UPDATE kdd_mail_access_rights
                          SET can_read = ?, can_reply = ?, can_forward = ?, can_delete = ?,
                              is_active = 1, revoked_at = NULL
                          WHERE id = ?";
            $pdo->prepare($updateSql)->execute([
                $canRead ? 1 : 0,
                $canReply ? 1 : 0,
                $canForward ? 1 : 0,
                $canDelete ? 1 : 0,
                $existing['id']
            ]);
            $accessId = $existing['id'];
            $isUpdate = true;
        } else {
            // Create new access
            $insertSql = "INSERT INTO kdd_mail_access_rights
                          (mail_id, authority_id, user_id, role_id, can_read, can_reply, can_forward, can_delete, granted_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($insertSql)->execute([
                $mailId,
                $authorityId,
                $targetUserId,
                $targetRoleId,
                $canRead ? 1 : 0,
                $canReply ? 1 : 0,
                $canForward ? 1 : 0,
                $canDelete ? 1 : 0,
                $userId
            ]);
            $accessId = $pdo->lastInsertId();
        }

        // Mark mail as having restricted access
        $updateMailSql = "UPDATE kdd_mails SET has_restricted_access = 1 WHERE id = ?";
        $pdo->prepare($updateMailSql)->execute([$mailId]);

        $pdo->commit();

        http_response_code($isUpdate ? 200 : 201);
        echo json_encode([
            'success' => true,
            'message' => 'Access granted successfully.',
            'access_id' => (int)$accessId
        ]);

    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("DB error in grantMailAccess (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not grant access."]);
    }
}

/**
 * Revoke access to a mail
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function revokeMailAccess(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $accessId = (int)($data['access_id'] ?? 0);

    if ($accessId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid access ID required.']);
        return;
    }

    try {
        // Verify access belongs to authority
        $checkSql = "SELECT id, mail_id FROM kdd_mail_access_rights WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$accessId, $authorityId]);
        $access = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$access) {
            http_response_code(404);
            echo json_encode(['error' => 'Access record not found.']);
            return;
        }

        // Revoke access
        $revokeSql = "UPDATE kdd_mail_access_rights
                      SET is_active = 0, revoked_at = NOW()
                      WHERE id = ?";
        $pdo->prepare($revokeSql)->execute([$accessId]);

        // Check if mail still has other active access rights
        $countSql = "SELECT COUNT(*) as count FROM kdd_mail_access_rights
                     WHERE mail_id = ? AND is_active = 1";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$access['mail_id']]);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // If no more active access rights, unmark restricted access
        if ($count == 0) {
            $updateMailSql = "UPDATE kdd_mails SET has_restricted_access = 0 WHERE id = ?";
            $pdo->prepare($updateMailSql)->execute([$access['mail_id']]);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Access revoked successfully.'
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in revokeMailAccess (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not revoke access."]);
    }
}

/**
 * Get list of users/roles who have access to a mail
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function getMailAccessList(PDO $pdo, int $userId, int $authorityId): void {
    $mailId = (int)($_GET['mail_id'] ?? 0);

    if ($mailId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail ID required.']);
        return;
    }

    try {
        // Verify mail belongs to authority
        $checkSql = "SELECT id FROM kdd_mails
                     WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$mailId, $authorityId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            return;
        }

        // Get access list
        $sql = "SELECT
                    mar.id,
                    mar.user_id,
                    mar.role_id,
                    mar.can_read,
                    mar.can_reply,
                    mar.can_forward,
                    mar.can_delete,
                    mar.granted_at,
                    mar.granted_by,
                    mar.is_active,
                    u.username,
                    r.name as role_name,
                    granter.username as granted_by_username
                FROM kdd_mail_access_rights mar
                LEFT JOIN kdd_users u ON mar.user_id = u.id
                LEFT JOIN kdd_roles r ON mar.role_id = r.id
                LEFT JOIN kdd_users granter ON mar.granted_by = granter.id
                WHERE mar.mail_id = ? AND mar.authority_id = ? AND mar.is_active = 1
                ORDER BY mar.granted_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailId, $authorityId]);
        $accessList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'access_list' => $accessList
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in getMailAccessList (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve access list."]);
    }
}

/**
 * Check if current user has access to a specific mail
 * Helper function used internally by other modules
 *
 * @param PDO $pdo Database connection
 * @param int $mailId Mail ID
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @param string $permission Permission to check (read, reply, forward, delete)
 * @return bool
 */
function hasMailAccess(PDO $pdo, int $mailId, int $userId, int $authorityId, string $permission = 'read'): bool {
    try {
        // First check if mail has restricted access
        $checkRestrictedSql = "SELECT has_restricted_access FROM kdd_mails WHERE id = ?";
        $checkStmt = $pdo->prepare($checkRestrictedSql);
        $checkStmt->execute([$mailId]);
        $mail = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If mail doesn't have restricted access, allow access
        if (!$mail || !$mail['has_restricted_access']) {
            return true;
        }

        // Get user's roles
        $rolesSql = "SELECT role_id FROM kdd_user_roles WHERE user_id = ?";
        $rolesStmt = $pdo->prepare($rolesSql);
        $rolesStmt->execute([$userId]);
        $userRoles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

        // Check direct user access or role-based access
        $permissionColumn = 'can_' . $permission;
        $sql = "SELECT $permissionColumn FROM kdd_mail_access_rights
                WHERE mail_id = ? AND authority_id = ? AND is_active = 1
                AND (user_id = ? OR role_id IN (" . implode(',', array_fill(0, count($userRoles), '?')) . "))
                LIMIT 1";

        $params = array_merge([$mailId, $authorityId, $userId], $userRoles);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $access = $stmt->fetch(PDO::FETCH_ASSOC);

        return $access && $access[$permissionColumn];

    } catch (\PDOException $e) {
        error_log("DB error in hasMailAccess: " . $e->getMessage());
        return false;
    }
}
