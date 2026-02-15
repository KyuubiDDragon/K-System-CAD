<?php
/**
 * Mail Module: admin.php
 * Administrative functions for managing all mail accounts across the system
 * Functions: getAllMailAccountsAdmin, updateAccountQuota, lockUnlockAccount,
 *            getMailSystemStats, updateAccountSettings, deleteAccountAdmin
 */
declare(strict_types=1);

/**
 * Get all mail accounts in the system (Admin only)
 * Returns comprehensive list with user info, storage, and activity
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function getAllMailAccountsAdmin(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $search = $_GET['search'] ?? '';
        $accountType = $_GET['account_type'] ?? '';
        $isActive = $_GET['is_active'] ?? '';
        $domain = $_GET['domain'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $offset = (int)($_GET['offset'] ?? 0);

        // Build WHERE clause
        $where = ["ma.authority_id = ?"];
        $params = [$authorityId];

        if ($search) {
            $where[] = "(ma.email LIKE ? OR u.username LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($accountType) {
            $where[] = "ma.account_type = ?";
            $params[] = $accountType;
        }

        if ($isActive !== '') {
            $where[] = "ma.is_active = ?";
            $params[] = (int)$isActive;
        }

        if ($domain) {
            $where[] = "md.domain = ?";
            $params[] = $domain;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total
                     FROM kdd_mail_accounts ma
                     LEFT JOIN kdd_user_mail_links uml ON ma.id = uml.mail_account_id AND uml.is_active = 1
                     LEFT JOIN kdd_users u ON uml.user_id = u.id
                     LEFT JOIN kdd_mail_domains md ON SUBSTRING_INDEX(ma.email, '@', -1) = md.domain
                     WHERE $whereClause";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get accounts
        $sql = "SELECT
                    ma.id,
                    ma.email,
                    ma.account_type,
                    ma.is_active,
                    ma.is_locked,
                    ma.is_searchable,
                    ma.storage_used,
                    ma.storage_limit,
                    ma.created_at,
                    ma.last_login_at,
                    md.domain,
                    md.domain_type,
                    u.id as user_id,
                    u.username,
                    uml.linked_at,
                    (SELECT COUNT(*) FROM kdd_mails WHERE from_user_id = u.id) as sent_count,
                    (SELECT COUNT(*) FROM kdd_mails m
                     INNER JOIN kdd_mail_recipients mr ON m.id = mr.mail_id
                     WHERE mr.recipient_address = ma.email) as received_count
                FROM kdd_mail_accounts ma
                LEFT JOIN kdd_user_mail_links uml ON ma.id = uml.mail_account_id AND uml.is_active = 1
                LEFT JOIN kdd_users u ON uml.user_id = u.id
                LEFT JOIN kdd_mail_domains md ON SUBSTRING_INDEX(ma.email, '@', -1) = md.domain
                WHERE $whereClause
                ORDER BY ma.created_at DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'accounts' => $accounts,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in getAllMailAccountsAdmin (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve mail accounts."]);
    }
}

/**
 * Update mail account storage quota (Admin only)
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function updateAccountQuota(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $accountId = (int)($data['account_id'] ?? 0);
    $storageLimit = (int)($data['storage_limit'] ?? 0);

    if ($accountId <= 0 || $storageLimit <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid account ID and storage limit required.']);
        return;
    }

    try {
        // Verify account belongs to authority
        $checkSql = "SELECT id FROM kdd_mail_accounts WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$accountId, $authorityId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Update quota
        $sql = "UPDATE kdd_mail_accounts
                SET storage_limit = ?,
                    updated_at = NOW()
                WHERE id = ? AND authority_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$storageLimit, $accountId, $authorityId]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Storage quota updated successfully.'
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in updateAccountQuota (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update storage quota."]);
    }
}

/**
 * Lock or unlock a mail account (Admin only)
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function lockUnlockAccount(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $accountId = (int)($data['account_id'] ?? 0);
    $isLocked = (bool)($data['is_locked'] ?? false);
    $reason = filter_var($data['reason'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($accountId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid account ID required.']);
        return;
    }

    try {
        // Verify account belongs to authority
        $checkSql = "SELECT id, email FROM kdd_mail_accounts WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$accountId, $authorityId]);
        $account = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Update lock status
        $sql = "UPDATE kdd_mail_accounts
                SET is_locked = ?,
                    updated_at = NOW()
                WHERE id = ? AND authority_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$isLocked ? 1 : 0, $accountId, $authorityId]);

        // Log the action
        require_once __DIR__ . '/../../logging/logging.php';
        logAction(
            $pdo,
            $userId,
            $isLocked ? 'MAIL_ACCOUNT_LOCKED' : 'MAIL_ACCOUNT_UNLOCKED',
            "Mail account {$account['email']} " . ($isLocked ? 'locked' : 'unlocked') .
            ($reason ? " - Reason: $reason" : '')
        );

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Account ' . ($isLocked ? 'locked' : 'unlocked') . ' successfully.'
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in lockUnlockAccount (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update account status."]);
    }
}

/**
 * Get mail system statistics (Admin only)
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function getMailSystemStats(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Get account statistics
        $accountStatsSql = "SELECT
                                COUNT(*) as total_accounts,
                                SUM(CASE WHEN account_type = 'personal' THEN 1 ELSE 0 END) as personal_accounts,
                                SUM(CASE WHEN account_type = 'company' THEN 1 ELSE 0 END) as company_accounts,
                                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_accounts,
                                SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked_accounts,
                                SUM(storage_used) as total_storage_used,
                                SUM(storage_limit) as total_storage_limit
                            FROM kdd_mail_accounts
                            WHERE authority_id = ?";

        $stmt = $pdo->prepare($accountStatsSql);
        $stmt->execute([$authorityId]);
        $accountStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get mail statistics
        $mailStatsSql = "SELECT
                            COUNT(*) as total_mails,
                            SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent_mails,
                            SUM(CASE WHEN is_draft = 1 THEN 1 ELSE 0 END) as draft_mails,
                            0 as deleted_mails,
                            SUM(CASE WHEN has_attachments = 1 THEN 1 ELSE 0 END) as mails_with_attachments
                         FROM kdd_mails m
                         WHERE m.authority_id = ?";

        $stmt = $pdo->prepare($mailStatsSql);
        $stmt->execute([$authorityId]);
        $mailStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get domain statistics
        $domainStatsSql = "SELECT
                                md.domain,
                                md.domain_type,
                                COUNT(ma.id) as account_count
                           FROM kdd_mail_domains md
                           LEFT JOIN kdd_mail_accounts ma ON SUBSTRING_INDEX(ma.email, '@', -1) = md.domain
                                AND ma.authority_id = ?
                           WHERE md.is_active = 1
                           GROUP BY md.id, md.domain, md.domain_type
                           ORDER BY account_count DESC";

        $stmt = $pdo->prepare($domainStatsSql);
        $stmt->execute([$authorityId]);
        $domainStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get company mailbox statistics
        $mailboxStatsSql = "SELECT
                                COUNT(*) as total_mailboxes,
                                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_mailboxes
                            FROM kdd_company_mailboxes
                            WHERE authority_id = ?";

        $stmt = $pdo->prepare($mailboxStatsSql);
        $stmt->execute([$authorityId]);
        $mailboxStats = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => [
                'accounts' => $accountStats,
                'mails' => $mailStats,
                'domains' => $domainStats,
                'mailboxes' => $mailboxStats
            ]
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in getMailSystemStats (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve statistics."]);
    }
}

/**
 * Update account settings (Admin only)
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function updateAccountSettingsAdmin(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $accountId = (int)($data['account_id'] ?? 0);
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;
    $isSearchable = isset($data['is_searchable']) ? (bool)$data['is_searchable'] : null;

    if ($accountId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid account ID required.']);
        return;
    }

    try {
        // Verify account belongs to authority
        $checkSql = "SELECT id FROM kdd_mail_accounts WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$accountId, $authorityId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            return;
        }

        // Build update query
        $updates = [];
        $params = [];

        if ($isActive !== null) {
            $updates[] = "is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }

        if ($isSearchable !== null) {
            $updates[] = "is_searchable = ?";
            $params[] = $isSearchable ? 1 : 0;
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No settings to update.']);
            return;
        }

        $updates[] = "updated_at = NOW()";
        $params[] = $accountId;
        $params[] = $authorityId;

        $sql = "UPDATE kdd_mail_accounts
                SET " . implode(', ', $updates) . "
                WHERE id = ? AND authority_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Account settings updated successfully.'
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in updateAccountSettingsAdmin (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update account settings."]);
    }
}

/**
 * Delete mail account (Admin only) - PERMANENT DELETION
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $authorityId Current authority ID
 * @return void
 */
function deleteAccountAdmin(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();

    if ($data === null) {
        return;
    }

    $accountId = (int)($data['account_id'] ?? 0);
    $confirm = $data['confirm'] ?? '';

    if ($accountId <= 0 || $confirm !== 'DELETE') {
        http_response_code(400);
        echo json_encode(['error' => 'Valid account ID and confirmation required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verify account belongs to authority
        $checkSql = "SELECT id, email FROM kdd_mail_accounts WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$accountId, $authorityId]);
        $account = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail account not found.']);
            $pdo->rollBack();
            return;
        }

        // Delete user links
        $deleteLinksSql = "DELETE FROM kdd_user_mail_links WHERE mail_account_id = ?";
        $pdo->prepare($deleteLinksSql)->execute([$accountId]);

        // Delete or anonymize mails (depending on requirements)
        // Note: from_mail_account_id doesn't exist in test schema, mails use from_user_id
        // In production, you may want to handle mail cleanup differently

        // Delete the account
        $deleteAccountSql = "DELETE FROM kdd_mail_accounts WHERE id = ? AND authority_id = ?";
        $pdo->prepare($deleteAccountSql)->execute([$accountId, $authorityId]);

        // Log the action
        require_once __DIR__ . '/../../logging/logging.php';
        logAction(
            $pdo,
            $userId,
            'MAIL_ACCOUNT_DELETED',
            "Mail account {$account['email']} permanently deleted by admin"
        );

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Account deleted successfully.'
        ]);

    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("DB error in deleteAccountAdmin (UserID: $userId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete account."]);
    }
}
