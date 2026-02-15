<?php
/**
 * Company Mailboxes Module
 * Handles company/shared mailboxes with flexible permissions system
 */
declare(strict_types=1);

/**
 * Get all company mailboxes user has access to
 * Joins mailboxes with permissions and includes mail account info
 */
function getCompanyMailboxes(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Get all mailboxes where user has direct or group-based access
        $sql = "SELECT DISTINCT
                    cm.id,
                    cm.mail_account_id,
                    cm.name,
                    cm.description,
                    cm.department,
                    cm.max_mail_addresses,
                    cm.current_mail_count,
                    cm.auto_reply_enabled,
                    cm.auto_reply_message,
                    cm.signature,
                    cm.is_active,
                    ma.email,
                    MAX(cmp.can_read) as can_read,
                    MAX(cmp.can_send) as can_send,
                    MAX(cmp.can_delete) as can_delete,
                    MAX(cmp.can_assign) as can_assign,
                    MAX(cmp.can_manage_members) as can_manage_members,
                    MAX(cmp.can_manage_settings) as can_manage_settings
                FROM kdd_company_mailboxes cm
                LEFT JOIN kdd_mail_accounts ma ON cm.mail_account_id = ma.id
                LEFT JOIN kdd_company_mailbox_permissions cmp ON cm.id = cmp.mailbox_id
                WHERE cm.authority_id = ?
                    AND cm.is_active = 1
                    AND (cmp.user_id = ? OR cmp.group_id IN (
                        SELECT group_id FROM kdd_message_group_members WHERE user_id = ? AND authority_id = ?
                    ))
                GROUP BY cm.id, cm.mail_account_id, cm.name, cm.description, cm.department,
                         cm.max_mail_addresses, cm.current_mail_count, cm.auto_reply_enabled,
                         cm.auto_reply_message, cm.signature, cm.is_active, ma.email
                ORDER BY cm.name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId, $userId, $authorityId]);
        $mailboxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert boolean values
        foreach ($mailboxes as &$mailbox) {
            $mailbox['is_active'] = (bool)$mailbox['is_active'];
            $mailbox['auto_reply_enabled'] = (bool)$mailbox['auto_reply_enabled'];
            $mailbox['can_read'] = (bool)$mailbox['can_read'];
            $mailbox['can_send'] = (bool)$mailbox['can_send'];
            $mailbox['can_delete'] = (bool)$mailbox['can_delete'];
            $mailbox['can_assign'] = (bool)$mailbox['can_assign'];
            $mailbox['can_manage_members'] = (bool)$mailbox['can_manage_members'];
            $mailbox['can_manage_settings'] = (bool)$mailbox['can_manage_settings'];
        }

        http_response_code(200);
        echo json_encode($mailboxes);
    } catch (PDOException $e) {
        error_log("DB Error in getCompanyMailboxes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching company mailboxes."]);
    }
}

/**
 * Create new company mailbox
 * Requires ADMIN_MAIL permission
 */
function createCompanyMailbox(PDO $pdo, int $userId, int $authorityId): void {
    // Get JSON request data (using the helper from mail/index.php)
    $data = getJsonRequestData();
    if ($data === null) return; // Error already sent by helper

    // VALIDATION
    $mailAccountId = filter_var($data['mail_account_id'] ?? null, FILTER_VALIDATE_INT);
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $name = trim($data['name'] ?? '');

    // Either mail_account_id OR email must be provided
    if (!$mailAccountId && empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: either mail_account_id or email is required']);
        return;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name is required and cannot be empty']);
        return;
    }

    // Sanitize optional fields
    $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $department = filter_var($data['department'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $maxMailAddresses = filter_var($data['max_mail_addresses'] ?? 100, FILTER_VALIDATE_INT);

    // If email provided, validate format and .ls domain
    if (!$mailAccountId && !empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '.ls')) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format. Company mailboxes must use .ls domain.']);
            return;
        }
    }

    try {
        $pdo->beginTransaction();

        // If mail_account_id not provided, create new mail account from email
        if (!$mailAccountId) {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM kdd_mail_accounts WHERE email = ? AND authority_id = ?");
            $stmt->execute([$email, $authorityId]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Email address already exists.']);
                return;
            }

            // Create mail account WITHOUT password (company mailboxes don't need passwords)
            $stmt = $pdo->prepare(
                "INSERT INTO kdd_mail_accounts (email, password, account_type, authority_id, created_at)
                 VALUES (?, NULL, 'company', ?, NOW())"
            );
            $stmt->execute([$email, $authorityId]);
            $mailAccountId = (int)$pdo->lastInsertId();
        }

        // Insert into company mailboxes
        $stmt = $pdo->prepare(
            "INSERT INTO kdd_company_mailboxes
             (mail_account_id, authority_id, name, description, department, max_mail_addresses,
              current_mail_count, auto_reply_enabled, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 1, NOW())"
        );
        $stmt->execute([$mailAccountId, $authorityId, $name, $description, $department, $maxMailAddresses]);
        $mailboxId = (int)$pdo->lastInsertId();

        // Auto-add creator as admin (all permissions)
        $stmt = $pdo->prepare(
            "INSERT INTO kdd_company_mailbox_permissions
             (mailbox_id, user_id, group_id, authority_id, can_read, can_send, can_delete,
              can_assign, can_manage_members, can_manage_settings, added_by_user_id, added_at)
             VALUES (?, ?, NULL, ?, 1, 1, 1, 1, 1, 1, ?, NOW())"
        );
        $stmt->execute([$mailboxId, $userId, $authorityId, $userId]);

        $pdo->commit();

        // Log the creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'email', 'old_value' => null, 'new_value' => $email],
            ['column_name' => 'department', 'old_value' => null, 'new_value' => $department]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'company_mailboxes', $mailboxId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Company mailbox created successfully.',
            'mailbox_id' => $mailboxId,
            'email' => $email
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB Error in createCompanyMailbox: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while creating company mailbox."]);
    }
}

/**
 * Update company mailbox settings
 * Requires can_manage_settings permission
 */
function updateCompanyMailbox(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $mailboxId = filter_var($data['mailbox_id'] ?? null, FILTER_VALIDATE_INT);
    $name = filter_var($data['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $department = filter_var($data['department'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $maxMailAddresses = filter_var($data['max_mail_addresses'] ?? 100, FILTER_VALIDATE_INT);
    $autoReplyEnabled = filter_var($data['auto_reply_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoReplyMessage = filter_var($data['auto_reply_message'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $signature = $data['signature'] ?? ''; // Allow HTML in signature

    if (!$mailboxId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mailbox ID.']);
        return;
    }

    try {
        // Check user has can_manage_settings permission
        if (!checkUserMailboxPermission($pdo, $userId, $mailboxId, 'can_manage_settings', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You need can_manage_settings permission.']);
            return;
        }

        // Get old data for logging
        $oldData = getEntryById($pdo, $mailboxId, 'kdd_company_mailboxes', $authorityId);
        if (!$oldData) {
            http_response_code(404);
            echo json_encode(['error' => 'Company mailbox not found.']);
            return;
        }

        // Update mailbox
        $stmt = $pdo->prepare(
            "UPDATE kdd_company_mailboxes
             SET name = ?, description = ?, department = ?, max_mail_addresses = ?,
                 auto_reply_enabled = ?, auto_reply_message = ?, signature = ?, updated_at = NOW()
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([
            $name, $description, $department, $maxMailAddresses,
            $autoReplyEnabled ? 1 : 0, $autoReplyMessage, $signature,
            $mailboxId, $authorityId
        ]);

        // Log changes
        $newData = getEntryById($pdo, $mailboxId, 'kdd_company_mailboxes', $authorityId);
        $changes = getEntryChanges($oldData, $newData);
        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'company_mailboxes', $mailboxId, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Company mailbox updated successfully.']);
    } catch (PDOException $e) {
        error_log("DB Error in updateCompanyMailbox: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while updating company mailbox."]);
    }
}

/**
 * Delete/deactivate company mailbox
 * Requires ADMIN_MAIL permission
 */
function deleteCompanyMailbox(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $mailboxId = filter_var($data['mailbox_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$mailboxId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mailbox ID.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get mailbox data
        $mailbox = getEntryById($pdo, $mailboxId, 'kdd_company_mailboxes', $authorityId);
        if (!$mailbox) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Company mailbox not found.']);
            return;
        }

        // Deactivate mailbox
        $stmt = $pdo->prepare(
            "UPDATE kdd_company_mailboxes SET is_active = 0, updated_at = NOW()
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$mailboxId, $authorityId]);

        // Optionally: Deactivate associated mail account
        if ($mailbox['mail_account_id']) {
            $stmt = $pdo->prepare(
                "UPDATE kdd_mail_accounts SET is_active = 0 WHERE id = ? AND authority_id = ?"
            );
            $stmt->execute([$mailbox['mail_account_id'], $authorityId]);
        }

        $pdo->commit();

        // Log deletion
        $changes = [
            ['column_name' => 'is_active', 'old_value' => 1, 'new_value' => 0]
        ];
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'company_mailboxes', $mailboxId, $userId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Company mailbox deleted successfully.']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB Error in deleteCompanyMailbox: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while deleting company mailbox."]);
    }
}

/**
 * Add user or group to mailbox with specific permissions
 * Requires can_manage_members permission
 */
function addMailboxPermission(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $mailboxId = filter_var($data['mailbox_id'] ?? null, FILTER_VALIDATE_INT);
    $targetUserId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
    $targetGroupId = filter_var($data['group_id'] ?? null, FILTER_VALIDATE_INT);

    // At least one of user_id or group_id must be provided
    if (!$mailboxId || (!$targetUserId && !$targetGroupId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mailbox ID or missing user_id/group_id.']);
        return;
    }

    // Cannot add both user and group at once
    if ($targetUserId && $targetGroupId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot add user and group simultaneously. Choose one.']);
        return;
    }

    // Get permissions
    $canRead = filter_var($data['can_read'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canSend = filter_var($data['can_send'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canDelete = filter_var($data['can_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canAssign = filter_var($data['can_assign'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canManageMembers = filter_var($data['can_manage_members'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canManageSettings = filter_var($data['can_manage_settings'] ?? false, FILTER_VALIDATE_BOOLEAN);

    try {
        // Check requester has can_manage_members permission
        if (!checkUserMailboxPermission($pdo, $userId, $mailboxId, 'can_manage_members', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You need can_manage_members permission.']);
            return;
        }

        // Check if permission already exists
        $checkSql = "SELECT id FROM kdd_company_mailbox_permissions
                     WHERE mailbox_id = ? AND authority_id = ? AND ";
        if ($targetUserId) {
            $checkSql .= "user_id = ?";
            $checkParams = [$mailboxId, $authorityId, $targetUserId];
        } else {
            $checkSql .= "group_id = ?";
            $checkParams = [$mailboxId, $authorityId, $targetGroupId];
        }

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute($checkParams);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Permission already exists for this user/group.']);
            return;
        }

        // Insert permission
        $stmt = $pdo->prepare(
            "INSERT INTO kdd_company_mailbox_permissions
             (mailbox_id, user_id, group_id, authority_id, can_read, can_send, can_delete,
              can_assign, can_manage_members, can_manage_settings, added_by_user_id, added_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $mailboxId,
            $targetUserId,
            $targetGroupId,
            $authorityId,
            $canRead ? 1 : 0,
            $canSend ? 1 : 0,
            $canDelete ? 1 : 0,
            $canAssign ? 1 : 0,
            $canManageMembers ? 1 : 0,
            $canManageSettings ? 1 : 0,
            $userId
        ]);

        $permissionId = (int)$pdo->lastInsertId();

        // Log permission addition
        $targetType = $targetUserId ? "user_id: $targetUserId" : "group_id: $targetGroupId";
        $changes = [
            ['column_name' => 'target', 'old_value' => null, 'new_value' => $targetType],
            ['column_name' => 'permissions', 'old_value' => null, 'new_value' => json_encode([
                'can_read' => $canRead,
                'can_send' => $canSend,
                'can_delete' => $canDelete,
                'can_assign' => $canAssign,
                'can_manage_members' => $canManageMembers,
                'can_manage_settings' => $canManageSettings
            ])]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'company_mailbox_permissions', $permissionId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Permission added successfully.',
            'permission_id' => $permissionId
        ]);
    } catch (PDOException $e) {
        error_log("DB Error in addMailboxPermission: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while adding permission."]);
    }
}

/**
 * Update permissions for existing member
 * Requires can_manage_members permission
 */
function updateMailboxPermission(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $permissionId = filter_var($data['permission_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$permissionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid permission ID.']);
        return;
    }

    // Get permissions
    $canRead = filter_var($data['can_read'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canSend = filter_var($data['can_send'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canDelete = filter_var($data['can_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canAssign = filter_var($data['can_assign'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canManageMembers = filter_var($data['can_manage_members'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $canManageSettings = filter_var($data['can_manage_settings'] ?? false, FILTER_VALIDATE_BOOLEAN);

    try {
        // Get permission record to check mailbox_id
        $stmt = $pdo->prepare(
            "SELECT mailbox_id FROM kdd_company_mailbox_permissions
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$permissionId, $authorityId]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$permission) {
            http_response_code(404);
            echo json_encode(['error' => 'Permission not found.']);
            return;
        }

        // Check requester has can_manage_members permission
        if (!checkUserMailboxPermission($pdo, $userId, $permission['mailbox_id'], 'can_manage_members', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You need can_manage_members permission.']);
            return;
        }

        // Get old data for logging
        $oldData = getEntryById($pdo, $permissionId, 'kdd_company_mailbox_permissions', $authorityId);

        // Update permission
        $stmt = $pdo->prepare(
            "UPDATE kdd_company_mailbox_permissions
             SET can_read = ?, can_send = ?, can_delete = ?, can_assign = ?,
                 can_manage_members = ?, can_manage_settings = ?
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([
            $canRead ? 1 : 0,
            $canSend ? 1 : 0,
            $canDelete ? 1 : 0,
            $canAssign ? 1 : 0,
            $canManageMembers ? 1 : 0,
            $canManageSettings ? 1 : 0,
            $permissionId,
            $authorityId
        ]);

        // Log changes
        $newData = getEntryById($pdo, $permissionId, 'kdd_company_mailbox_permissions', $authorityId);
        $changes = getEntryChanges($oldData, $newData);
        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'company_mailbox_permissions', $permissionId, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Permission updated successfully.']);
    } catch (PDOException $e) {
        error_log("DB Error in updateMailboxPermission: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while updating permission."]);
    }
}

/**
 * Remove user/group from mailbox
 * Requires can_manage_members permission
 */
function removeMailboxPermission(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $permissionId = filter_var($data['permission_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$permissionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid permission ID.']);
        return;
    }

    try {
        // Get permission record to check mailbox_id and for logging
        $stmt = $pdo->prepare(
            "SELECT mailbox_id, user_id, group_id FROM kdd_company_mailbox_permissions
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$permissionId, $authorityId]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$permission) {
            http_response_code(404);
            echo json_encode(['error' => 'Permission not found.']);
            return;
        }

        // Check requester has can_manage_members permission
        if (!checkUserMailboxPermission($pdo, $userId, $permission['mailbox_id'], 'can_manage_members', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You need can_manage_members permission.']);
            return;
        }

        // Delete permission
        $stmt = $pdo->prepare(
            "DELETE FROM kdd_company_mailbox_permissions WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$permissionId, $authorityId]);

        // Log deletion
        $targetType = $permission['user_id'] ? "user_id: {$permission['user_id']}" : "group_id: {$permission['group_id']}";
        $changes = [
            ['column_name' => 'target', 'old_value' => $targetType, 'new_value' => null]
        ];
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'company_mailbox_permissions', $permissionId, $userId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Permission removed successfully.']);
    } catch (PDOException $e) {
        error_log("DB Error in removeMailboxPermission: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while removing permission."]);
    }
}

/**
 * Assign mail to team member (for company mailboxes)
 * Requires can_assign permission
 */
function assignMail(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    $assignedToUserId = filter_var($data['assigned_to_user_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$mailId || !$assignedToUserId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: mail_id and assigned_to_user_id are required']);
        return;
    }

    try {
        // Get mail recipient to check if it's in a company mailbox
        $stmt = $pdo->prepare(
            "SELECT mr.id, mr.mail_account_id, cm.id as mailbox_id
             FROM kdd_mail_recipients mr
             LEFT JOIN kdd_company_mailboxes cm ON mr.mail_account_id = cm.mail_account_id
             WHERE mr.id = ? AND mr.authority_id = ?"
        );
        $stmt->execute([$mailId, $authorityId]);
        $mail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mail) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            return;
        }

        if (!$mail['mailbox_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'This mail is not in a company mailbox.']);
            return;
        }

        // Check requester has can_assign permission
        if (!checkUserMailboxPermission($pdo, $userId, $mail['mailbox_id'], 'can_assign', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You need can_assign permission.']);
            return;
        }

        // Update mail recipient with assignment
        $stmt = $pdo->prepare(
            "UPDATE kdd_mail_recipients
             SET assigned_to_user_id = ?, assigned_at = NOW(), assigned_by_user_id = ?
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$assignedToUserId, $userId, $mailId, $authorityId]);

        // TODO: Send notification to assigned user
        // This could be implemented via socket.io or email notification

        // Log assignment
        $changes = [
            ['column_name' => 'assigned_to_user_id', 'old_value' => null, 'new_value' => $assignedToUserId],
            ['column_name' => 'assigned_by_user_id', 'old_value' => null, 'new_value' => $userId]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'mail_recipients', $mailId, $userId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Mail assigned successfully.']);
    } catch (PDOException $e) {
        error_log("DB Error in assignMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while assigning mail."]);
    }
}

/**
 * Update mail status in company mailbox
 * Status: new/in_progress/done/archived
 */
function updateMailStatus(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    $status = trim($data['status'] ?? '');

    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: mail_id is required and must be a valid integer']);
        return;
    }

    if (empty($status)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: status is required and cannot be empty']);
        return;
    }

    // Validate status against allowed values
    $validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)]);
        return;
    }

    try {
        // Get old status for logging
        $stmt = $pdo->prepare(
            "SELECT status FROM kdd_mail_recipients WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$mailId, $authorityId]);
        $oldStatus = $stmt->fetchColumn();

        if ($oldStatus === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            return;
        }

        // Update status
        $stmt = $pdo->prepare(
            "UPDATE kdd_mail_recipients SET status = ?, updated_at = NOW()
             WHERE id = ? AND authority_id = ?"
        );
        $stmt->execute([$status, $mailId, $authorityId]);

        // Log status change
        if ($oldStatus !== $status) {
            $changes = [
                ['column_name' => 'status', 'old_value' => $oldStatus, 'new_value' => $status]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'mail_recipients', $mailId, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Mail status updated successfully.']);
    } catch (PDOException $e) {
        error_log("DB Error in updateMailStatus: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while updating mail status."]);
    }
}

/**
 * Get all members/groups with permissions for a mailbox
 * Requires access to the mailbox
 */
function getMailboxPermissions(PDO $pdo, int $userId, int $authorityId): void {
    $mailboxId = filter_var($_REQUEST['mailbox_id'] ?? $_GET['mailbox_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$mailboxId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mailbox ID.']);
        return;
    }

    try {
        // Check user has access to this mailbox
        if (!checkUserMailboxPermission($pdo, $userId, $mailboxId, 'can_read', $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied. You do not have access to this mailbox.']);
            return;
        }

        // Get all permissions with user/group details
        $sql = "SELECT
                    cmp.id,
                    cmp.mailbox_id,
                    cmp.user_id,
                    cmp.group_id,
                    cmp.can_read,
                    cmp.can_send,
                    cmp.can_delete,
                    cmp.can_assign,
                    cmp.can_manage_members,
                    cmp.can_manage_settings,
                    cmp.added_by_user_id,
                    cmp.added_at,
                    u.username as user_name,
                    mg.name as group_name
                FROM kdd_company_mailbox_permissions cmp
                LEFT JOIN kdd_users u ON cmp.user_id = u.id
                LEFT JOIN kdd_message_groups mg ON cmp.group_id = mg.id
                WHERE cmp.mailbox_id = ? AND cmp.authority_id = ?
                ORDER BY
                    CASE WHEN cmp.user_id IS NOT NULL THEN 0 ELSE 1 END,
                    u.username,
                    mg.name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailboxId, $authorityId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert boolean values
        foreach ($permissions as &$permission) {
            $permission['can_read'] = (bool)$permission['can_read'];
            $permission['can_send'] = (bool)$permission['can_send'];
            $permission['can_delete'] = (bool)$permission['can_delete'];
            $permission['can_assign'] = (bool)$permission['can_assign'];
            $permission['can_manage_members'] = (bool)$permission['can_manage_members'];
            $permission['can_manage_settings'] = (bool)$permission['can_manage_settings'];
        }

        http_response_code(200);
        echo json_encode($permissions);
    } catch (PDOException $e) {
        error_log("DB Error in getMailboxPermissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching mailbox permissions."]);
    }
}

/**
 * Helper function to check if user has specific permission for a mailbox
 * Checks both direct user permission and group permissions
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID to check
 * @param int $mailboxId Mailbox ID
 * @param string $permission Permission to check (e.g., 'can_read', 'can_send')
 * @param int $authorityId Authority ID
 * @return bool True if user has permission
 */
function checkUserMailboxPermission(PDO $pdo, int $userId, int $mailboxId, string $permission, int $authorityId): bool {
    try {
        // Check direct user permission OR group permission
        $sql = "SELECT MAX(cmp.$permission) as has_permission
                FROM kdd_company_mailbox_permissions cmp
                WHERE cmp.mailbox_id = ?
                    AND cmp.authority_id = ?
                    AND (cmp.user_id = ? OR cmp.group_id IN (
                        SELECT group_id
                        FROM kdd_message_group_members
                        WHERE user_id = ? AND authority_id = ?
                    ))";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailboxId, $authorityId, $userId, $userId, $authorityId]);
        $result = $stmt->fetchColumn();

        return (bool)$result;
    } catch (PDOException $e) {
        error_log("DB Error in checkUserMailboxPermission: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Mailbox Members - Get list of users with access to a mailbox
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function getMailboxMembers(PDO $pdo, int $userId, int $authorityId): void {
    $mailboxId = (int)($_GET['mailbox_id'] ?? 0);

    if ($mailboxId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mailbox ID required.']);
        return;
    }

    try {
        // Verify mailbox exists and belongs to authority
        $checkSql = "SELECT id, name FROM kdd_company_mailboxes WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$mailboxId, $authorityId]);
        $mailbox = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$mailbox) {
            http_response_code(404);
            echo json_encode(['error' => 'Mailbox not found.']);
            return;
        }

        // Get all users with permissions to this mailbox
        $sql = "SELECT
                    p.id as permission_id,
                    p.user_id,
                    u.username,
                    p.can_read,
                    p.can_send,
                    p.can_delete,
                    p.can_assign,
                    p.can_manage_members,
                    p.can_manage_settings,
                    p.added_at,
                    adder.username as added_by_username
                FROM kdd_company_mailbox_permissions p
                INNER JOIN kdd_users u ON p.user_id = u.id
                LEFT JOIN kdd_users adder ON p.added_by_user_id = adder.id
                WHERE p.mailbox_id = ? AND p.authority_id = ?
                ORDER BY p.added_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailboxId, $authorityId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $members,
            'mailbox_name' => $mailbox['name']
        ]);

    } catch (PDOException $e) {
        error_log("DB Error in getMailboxMembers: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve mailbox members."]);
    }
}
?>
