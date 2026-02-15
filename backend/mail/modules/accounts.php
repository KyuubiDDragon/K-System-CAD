<?php
/**
 * Mail Module: accounts.php
 * Handles mail account management for the K-Systems mail system.
 * Functions: getMyMailAccounts, createMailAccount, linkMailAccount, unlinkMailAccount,
 *            changeMailPassword, updateMailSettings
 */
declare(strict_types=1);

/**
 * Get all mail accounts linked to current user
 * Returns array of mail accounts with domain info
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function getMyMailAccounts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Get all mail accounts linked to this user
        $sql = "SELECT
                    ma.id,
                    ma.email,
                    ma.display_name,
                    ma.account_type,
                    ma.is_active,
                    ma.is_searchable,
                    ma.storage_used,
                    ma.storage_limit,
                    ma.created_at,
                    uml.linked_at,
                    uml.is_active as link_is_active,
                    md.domain,
                    md.domain_type
                FROM kdd_mail_accounts ma
                INNER JOIN kdd_user_mail_links uml ON ma.id = uml.mail_account_id
                LEFT JOIN kdd_mail_domains md ON SUBSTRING_INDEX(ma.email, '@', -1) = md.domain
                WHERE uml.user_id = ?
                    AND uml.is_active = 1
                    AND ma.is_active = 1
                ORDER BY uml.linked_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'accounts' => $accounts
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in getMyMailAccounts (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve mail accounts."]);
    }
}

/**
 * Create new mail account
 * Validates email format (must end with .ls), hashes password with Argon2id
 * If personal account: links to user automatically
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function createMailAccount(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return; // Error already handled by getJsonRequestData
    }

    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';
    $displayName = filter_var($data['display_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $accountType = filter_var($data['account_type'] ?? 'personal', FILTER_SANITIZE_SPECIAL_CHARS);
    $domainId = isset($data['domain_id']) ? filter_var($data['domain_id'], FILTER_VALIDATE_INT) : null;

    // If domain_id is provided and email doesn't contain @, build full email
    if ($domainId && !str_contains($email, '@')) {
        // Get domain name from domain_id
        $stmt = $pdo->prepare("SELECT domain FROM kdd_mail_domains WHERE id = ?");
        $stmt->execute([$domainId]);
        $domainName = $stmt->fetchColumn();
        if ($domainName) {
            $email = $email . '@' . $domainName;
        }
    }

    // Validate required fields
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required.']);
        return;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format.']);
        return;
    }

    // Validate email ends with .ls
    if (!preg_match('/\.ls$/i', $email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email must end with .ls domain.']);
        return;
    }

    // Validate account type
    if (!in_array($accountType, ['personal', 'company', 'faction'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid account type. Must be personal, company, or faction.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM kdd_mail_accounts WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Email address is already taken.']);
            return;
        }

        // Extract domain from email
        $domain = substr($email, strpos($email, '@') + 1);

        // Check if domain exists and belongs to authority or is default
        $stmt = $pdo->prepare("SELECT id, authority_id FROM kdd_mail_domains WHERE domain = ?");
        $stmt->execute([$domain]);
        $domainData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$domainData) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Domain not available for registration.']);
            return;
        }

        // Check authority match (domain must belong to user's authority or be NULL for default domains)
        if ($domainData['authority_id'] !== null && $domainData['authority_id'] != $authorityId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to create accounts on this domain.']);
            return;
        }

        // Hash password using modern bcrypt/argon2id
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

        // If no display name provided, use local part of email
        if (empty($displayName)) {
            $displayName = substr($email, 0, strpos($email, '@'));
        }

        // Insert mail account
        $sql = "INSERT INTO kdd_mail_accounts
                (email, display_name, password_hash, account_type, authority_id, is_active, is_searchable, storage_used, storage_limit, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 1, 0, 5368709120, NOW())"; // Default 5GB storage limit

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $displayName, $passwordHash, $accountType, $authorityId]);
        $mailAccountId = (int)$pdo->lastInsertId();

        // If personal account, link to user automatically
        if ($accountType === 'personal') {
            // Check if user already has an active link
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kdd_user_mail_links WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $activeLinkCount = (int)$stmt->fetchColumn();

            if ($activeLinkCount >= 1) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'You already have an active mail account. Please unlink your existing account before creating a new one.']);
                return;
            }

            // Create link
            $stmt = $pdo->prepare("INSERT INTO kdd_user_mail_links (user_id, mail_account_id, authority_id, is_active, linked_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$userId, $mailAccountId, $authorityId]);

            // Update current_user_id
            $stmt = $pdo->prepare("UPDATE kdd_mail_accounts SET current_user_id = ? WHERE id = ?");
            $stmt->execute([$userId, $mailAccountId]);
        }

        $pdo->commit();

        // Log the creation
        $changes = [
            ['column_name' => 'email', 'old_value' => null, 'new_value' => $email],
            ['column_name' => 'account_type', 'old_value' => null, 'new_value' => $accountType]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_mail_accounts", $mailAccountId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Mail account created successfully.',
            'account' => [
                'id' => $mailAccountId,
                'email' => $email,
                'account_type' => $accountType
            ]
        ]);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in createMailAccount (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create mail account."]);
    }
}

/**
 * Link existing mail account to user profile
 * Verifies email + password, checks user has only 1 active link,
 * checks mail not linked to other user, checks authority match
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function linkMailAccount(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return; // Error already handled by getJsonRequestData
    }

    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get mail account with email
        $stmt = $pdo->prepare("SELECT id, password_hash, account_type, authority_id, current_user_id, is_active FROM kdd_mail_accounts WHERE email = ?");
        $stmt->execute([$email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Check if account is active
        if (!$account['is_active']) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'This mail account is inactive.']);
            return;
        }

        // Verify password using modern password_verify
        if (!password_verify($password, $account['password_hash'])) {
            $pdo->rollBack();
            http_response_code(401);
            echo json_encode(['error' => 'Invalid password.']);
            return;
        }

        // Check if user already has an active link
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kdd_user_mail_links WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $activeLinkCount = (int)$stmt->fetchColumn();

        if ($activeLinkCount >= 1) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'You already have an active mail account linked. Please unlink your existing account first.']);
            return;
        }

        // Check if mail account is already linked to another user
        if ($account['current_user_id'] !== null && $account['current_user_id'] != $userId) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'This mail account is already linked to another user.']);
            return;
        }

        // Check authority match (account must belong to user's authority or be NULL for default domains)
        if ($account['authority_id'] !== null && $account['authority_id'] != $authorityId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to link this mail account.']);
            return;
        }

        // Create link
        $stmt = $pdo->prepare("INSERT INTO kdd_user_mail_links (user_id, mail_account_id, authority_id, is_active, linked_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$userId, $account['id'], $authorityId]);

        // Update current_user_id
        $stmt = $pdo->prepare("UPDATE kdd_mail_accounts SET current_user_id = ? WHERE id = ?");
        $stmt->execute([$userId, $account['id']]);

        $pdo->commit();

        // Log the link
        $changes = [
            ['column_name' => 'linked_user', 'old_value' => $account['current_user_id'], 'new_value' => $userId]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_mail_accounts", $account['id'], $userId, $changes);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mail account linked successfully.'
        ]);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in linkMailAccount (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not link mail account."]);
    }
}

/**
 * Unlink mail account from user
 * Sets is_active = 0 in kdd_user_mail_links, sets unlinked_at and unlinked_reason
 * Sets current_user_id = NULL in kdd_mail_accounts
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function unlinkMailAccount(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return; // Error already handled by getJsonRequestData
    }

    $mailAccountId = filter_var($data['mail_account_id'] ?? 0, FILTER_VALIDATE_INT);
    $unlinkReason = filter_var($data['unlink_reason'] ?? 'User initiated', FILTER_SANITIZE_SPECIAL_CHARS);

    // Validate required fields
    if (!$mailAccountId || $mailAccountId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail account ID is required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if user owns this mail account
        $stmt = $pdo->prepare("SELECT ma.id, ma.current_user_id, uml.id as link_id
                               FROM kdd_mail_accounts ma
                               LEFT JOIN kdd_user_mail_links uml ON ma.id = uml.mail_account_id AND uml.user_id = ? AND uml.is_active = 1
                               WHERE ma.id = ?");
        $stmt->execute([$userId, $mailAccountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        if (!$account['link_id']) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have this mail account linked.']);
            return;
        }

        // Update link to inactive
        $stmt = $pdo->prepare("UPDATE kdd_user_mail_links SET is_active = 0, unlinked_at = NOW(), unlinked_reason = ? WHERE id = ?");
        $stmt->execute([$unlinkReason, $account['link_id']]);

        // Update current_user_id to NULL
        $stmt = $pdo->prepare("UPDATE kdd_mail_accounts SET current_user_id = NULL WHERE id = ?");
        $stmt->execute([$mailAccountId]);

        $pdo->commit();

        // Log the unlink
        $changes = [
            ['column_name' => 'link_active', 'old_value' => 1, 'new_value' => 0],
            ['column_name' => 'unlink_reason', 'old_value' => null, 'new_value' => $unlinkReason]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_user_mail_links", $account['link_id'], $userId, $changes);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mail account unlinked successfully.'
        ]);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in unlinkMailAccount (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not unlink mail account."]);
    }
}

/**
 * Change mail account password
 * Verifies old password, checks user owns this mail account
 * Hashes new password with Argon2id
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function changeMailPassword(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return; // Error already handled by getJsonRequestData
    }

    $mailAccountId = filter_var($data['mail_account_id'] ?? 0, FILTER_VALIDATE_INT);
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    // Validate required fields
    if (!$mailAccountId || $mailAccountId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail account ID is required.']);
        return;
    }

    if (empty($oldPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'Old password and new password are required.']);
        return;
    }

    // Validate new password strength (optional but recommended)
    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 8 characters long.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get mail account and verify ownership
        $stmt = $pdo->prepare("SELECT ma.id, ma.password_hash, ma.current_user_id
                               FROM kdd_mail_accounts ma
                               WHERE ma.id = ?");
        $stmt->execute([$mailAccountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Check if user owns this mail account
        if ($account['current_user_id'] != $userId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to change this account\'s password.']);
            return;
        }

        // Verify old password using modern password_verify
        if (!password_verify($oldPassword, $account['password_hash'])) {
            $pdo->rollBack();
            http_response_code(401);
            echo json_encode(['error' => 'Old password is incorrect.']);
            return;
        }

        // Hash new password using modern bcrypt/argon2id
        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);

        // Update password
        $stmt = $pdo->prepare("UPDATE kdd_mail_accounts SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newPasswordHash, $mailAccountId]);

        $pdo->commit();

        // Log the password change (don't log actual passwords)
        $changes = [
            ['column_name' => 'password_changed', 'old_value' => 'old_password_hash', 'new_value' => 'new_password_hash']
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_mail_accounts", $mailAccountId, $userId, $changes);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in changeMailPassword (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not change password."]);
    }
}

/**
 * Update mail account settings (privacy, etc.)
 * Checks user owns this mail account
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function updateMailSettings(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return; // Error already handled by getJsonRequestData
    }

    $mailAccountId = filter_var($data['mail_account_id'] ?? 0, FILTER_VALIDATE_INT);
    $displayName = isset($data['display_name']) ? filter_var($data['display_name'], FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $isSearchable = isset($data['is_searchable']) ? filter_var($data['is_searchable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

    // Validate required fields
    if (!$mailAccountId || $mailAccountId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail account ID is required.']);
        return;
    }

    // At least one setting must be provided
    if ($isSearchable === null && $displayName === null) {
        http_response_code(400);
        echo json_encode(['error' => 'At least one setting (display_name or is_searchable) is required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get mail account and verify ownership
        $stmt = $pdo->prepare("SELECT id, current_user_id, is_searchable FROM kdd_mail_accounts WHERE id = ?");
        $stmt->execute([$mailAccountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Check if user owns this mail account
        if ($account['current_user_id'] != $userId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to update this account\'s settings.']);
            return;
        }

        // Build dynamic UPDATE query
        $updates = [];
        $params = [];

        if ($displayName !== null) {
            $updates[] = "display_name = ?";
            $params[] = $displayName;
        }

        if ($isSearchable !== null) {
            $updates[] = "is_searchable = ?";
            $params[] = $isSearchable ? 1 : 0;
        }

        $params[] = $mailAccountId;

        // Update settings
        $sql = "UPDATE kdd_mail_accounts SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();

        // Log the settings change
        $changes = [
            ['column_name' => 'is_searchable', 'old_value' => $oldIsSearchable, 'new_value' => $isSearchable]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_mail_accounts", $mailAccountId, $userId, $changes);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mail settings updated successfully.'
        ]);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in updateMailSettings (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update mail settings."]);
    }
}
?>
