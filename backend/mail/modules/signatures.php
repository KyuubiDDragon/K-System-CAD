<?php
/**
 * Mail Signatures Module
 * Handles personal and mail account-specific email signatures
 */

declare(strict_types=1);

/**
 * Get all signatures for user
 */
function getSignatures(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Get all signatures for this user or their mail accounts
        $stmt = $pdo->prepare("
            SELECT
                s.*,
                ma.email as account_email
            FROM kdd_mail_signatures s
            LEFT JOIN kdd_mail_accounts ma ON s.mail_account_id = ma.id
            WHERE s.user_id = :user_id
            AND s.authority_id = :authority_id
            ORDER BY s.is_default DESC, s.name ASC
        ");
        $stmt->execute([
            'user_id' => $userId,
            'authority_id' => $authorityId
        ]);

        $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert boolean fields
        foreach ($signatures as &$signature) {
            $signature['is_default'] = (bool)$signature['is_default'];
            $signature['use_for_new'] = (bool)$signature['use_for_new'];
            $signature['use_for_reply'] = (bool)$signature['use_for_reply'];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'signatures' => $signatures
        ]);

    } catch (Exception $e) {
        error_log("Error in getSignatures: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve signatures']);
    }
}

/**
 * Create new signature
 */
function createSignature(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $name = isset($data['name']) ? trim((string)$data['name']) : '';
        $signatureHtml = isset($data['signature_html']) ? trim((string)$data['signature_html']) : '';

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: name cannot be empty']);
            return;
        }

        if (empty($signatureHtml)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: signature_html cannot be empty']);
            return;
        }

        $mailAccountId = $data['mail_account_id'] ?? null;
        $isDefault = $data['is_default'] ?? false;
        $useForNew = $data['use_for_new'] ?? true;
        $useForReply = $data['use_for_reply'] ?? true;

        // If mail_account_id is provided, verify user owns this account
        if ($mailAccountId) {
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_mail_accounts
                WHERE id = :account_id
                AND user_id = :user_id
                AND authority_id = :authority_id
            ");
            $stmt->execute([
                'account_id' => $mailAccountId,
                'user_id' => $userId,
                'authority_id' => $authorityId
            ]);

            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Mail account not found or access denied']);
                return;
            }
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // If setting as default, unset other default signatures for this user/account
            if ($isDefault) {
                $unsetQuery = "
                    UPDATE kdd_mail_signatures
                    SET is_default = 0
                    WHERE user_id = :user_id
                    AND authority_id = :authority_id
                ";

                if ($mailAccountId) {
                    $unsetQuery .= " AND mail_account_id = :mail_account_id";
                    $stmt = $pdo->prepare($unsetQuery);
                    $stmt->execute([
                        'user_id' => $userId,
                        'authority_id' => $authorityId,
                        'mail_account_id' => $mailAccountId
                    ]);
                } else {
                    $unsetQuery .= " AND mail_account_id IS NULL";
                    $stmt = $pdo->prepare($unsetQuery);
                    $stmt->execute([
                        'user_id' => $userId,
                        'authority_id' => $authorityId
                    ]);
                }
            }

            // Sanitize HTML
            $sanitizedHtml = sanitizeSignatureHtml($signatureHtml);

            // Insert new signature
            $stmt = $pdo->prepare("
                INSERT INTO kdd_mail_signatures (
                    user_id, mail_account_id, authority_id, name,
                    signature_html, is_default, use_for_new, use_for_reply
                ) VALUES (
                    :user_id, :mail_account_id, :authority_id, :name,
                    :signature_html, :is_default, :use_for_new, :use_for_reply
                )
            ");

            $stmt->execute([
                'user_id' => $userId,
                'mail_account_id' => $mailAccountId,
                'authority_id' => $authorityId,
                'name' => $name,
                'signature_html' => $sanitizedHtml,
                'is_default' => $isDefault ? 1 : 0,
                'use_for_new' => $useForNew ? 1 : 0,
                'use_for_reply' => $useForReply ? 1 : 0
            ]);

            $signatureId = $pdo->lastInsertId();

            $pdo->commit();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'signature_id' => (int)$signatureId,
                'message' => 'Signature created successfully'
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Error in createSignature: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create signature']);
    }
}

/**
 * Update existing signature
 */
function updateSignature(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $signatureId = filter_var($data['signature_id'] ?? null, FILTER_VALIDATE_INT);
        if ($signatureId === false || $signatureId === null || $signatureId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing signature_id: must be a positive integer']);
            return;
        }

        // Validate that at least one field to update is provided
        $hasName = isset($data['name']);
        $hasSignatureHtml = isset($data['signature_html']);
        $hasIsDefault = isset($data['is_default']);
        $hasUseForNew = isset($data['use_for_new']);
        $hasUseForReply = isset($data['use_for_reply']);

        if (!$hasName && !$hasSignatureHtml && !$hasIsDefault && !$hasUseForNew && !$hasUseForReply) {
            http_response_code(400);
            echo json_encode(['error' => 'At least one field to update is required (name, signature_html, is_default, use_for_new, use_for_reply)']);
            return;
        }

        // Validate name if provided
        if ($hasName) {
            $name = trim((string)$data['name']);
            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Field name cannot be empty if provided']);
                return;
            }
        }

        // Validate signature_html if provided
        if ($hasSignatureHtml) {
            $signatureHtml = trim((string)$data['signature_html']);
            if (empty($signatureHtml)) {
                http_response_code(400);
                echo json_encode(['error' => 'Field signature_html cannot be empty if provided']);
                return;
            }
        }

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_mail_signatures
            WHERE id = :signature_id
            AND user_id = :user_id
            AND authority_id = :authority_id
        ");
        $stmt->execute([
            'signature_id' => $signatureId,
            'user_id' => $userId,
            'authority_id' => $authorityId
        ]);
        $signature = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$signature) {
            http_response_code(404);
            echo json_encode(['error' => 'Signature not found or access denied']);
            return;
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // If setting as default, unset other default signatures
            if (isset($data['is_default']) && $data['is_default']) {
                $unsetQuery = "
                    UPDATE kdd_mail_signatures
                    SET is_default = 0
                    WHERE user_id = :user_id
                    AND authority_id = :authority_id
                    AND id != :signature_id
                ";

                if ($signature['mail_account_id']) {
                    $unsetQuery .= " AND mail_account_id = :mail_account_id";
                    $stmt = $pdo->prepare($unsetQuery);
                    $stmt->execute([
                        'user_id' => $userId,
                        'authority_id' => $authorityId,
                        'signature_id' => $signatureId,
                        'mail_account_id' => $signature['mail_account_id']
                    ]);
                } else {
                    $unsetQuery .= " AND mail_account_id IS NULL";
                    $stmt = $pdo->prepare($unsetQuery);
                    $stmt->execute([
                        'user_id' => $userId,
                        'authority_id' => $authorityId,
                        'signature_id' => $signatureId
                    ]);
                }
            }

            // Build update query
            $updates = [];
            $params = ['signature_id' => $signatureId];

            if ($hasName) {
                $updates[] = "name = :name";
                $params['name'] = $name;
            }
            if ($hasSignatureHtml) {
                $updates[] = "signature_html = :signature_html";
                $params['signature_html'] = sanitizeSignatureHtml($signatureHtml);
            }
            if ($hasIsDefault) {
                $updates[] = "is_default = :is_default";
                $params['is_default'] = $data['is_default'] ? 1 : 0;
            }
            if ($hasUseForNew) {
                $updates[] = "use_for_new = :use_for_new";
                $params['use_for_new'] = $data['use_for_new'] ? 1 : 0;
            }
            if ($hasUseForReply) {
                $updates[] = "use_for_reply = :use_for_reply";
                $params['use_for_reply'] = $data['use_for_reply'] ? 1 : 0;
            }

            $sql = "UPDATE kdd_mail_signatures SET " . implode(', ', $updates) . " WHERE id = :signature_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $pdo->commit();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Signature updated successfully'
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Error in updateSignature: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update signature']);
    }
}

/**
 * Delete signature
 */
function deleteSignature(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $signatureId = filter_var($data['signature_id'] ?? null, FILTER_VALIDATE_INT);
        if ($signatureId === false || $signatureId === null || $signatureId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing signature_id: must be a positive integer']);
            return;
        }

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_signatures
            WHERE id = :signature_id
            AND user_id = :user_id
            AND authority_id = :authority_id
        ");
        $stmt->execute([
            'signature_id' => $signatureId,
            'user_id' => $userId,
            'authority_id' => $authorityId
        ]);

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Signature not found or access denied']);
            return;
        }

        // Delete signature
        $stmt = $pdo->prepare("DELETE FROM kdd_mail_signatures WHERE id = :signature_id");
        $stmt->execute(['signature_id' => $signatureId]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Signature deleted successfully'
        ]);

    } catch (Exception $e) {
        error_log("Error in deleteSignature: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete signature']);
    }
}

/**
 * Get default signature for compose
 * Helper function that can be used by other modules
 */
function getDefaultSignature(PDO $pdo, int $userId, ?int $mailAccountId = null): ?array {
    try {
        $query = "
            SELECT * FROM kdd_mail_signatures
            WHERE user_id = :user_id
            AND is_default = 1
        ";

        $params = ['user_id' => $userId];

        if ($mailAccountId) {
            $query .= " AND mail_account_id = :mail_account_id";
            $params['mail_account_id'] = $mailAccountId;
        } else {
            $query .= " AND mail_account_id IS NULL";
        }

        $query .= " ORDER BY id DESC LIMIT 1";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $signature = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($signature) {
            $signature['is_default'] = (bool)$signature['is_default'];
            $signature['use_for_new'] = (bool)$signature['use_for_new'];
            $signature['use_for_reply'] = (bool)$signature['use_for_reply'];
        }

        return $signature ?: null;

    } catch (Exception $e) {
        error_log("Error in getDefaultSignature: " . $e->getMessage());
        return null;
    }
}

/**
 * Sanitize signature HTML for security
 * More permissive than template sanitization to allow formatting
 */
function sanitizeSignatureHtml(string $html): string {
    // Allow common HTML tags used in email signatures
    $allowed_tags = '<p><br><b><i><u><strong><em><a><ul><ol><li><h1><h2><h3><h4><h5><h6><div><span><table><tr><td><th><tbody><thead><img><hr><blockquote><pre><code><font><center>';
    return strip_tags($html, $allowed_tags);
}
