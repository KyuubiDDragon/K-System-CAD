<?php
/**
 * Mail Operations Module
 * Handles core mail operations: send, receive, drafts, read, star, delete
 */

declare(strict_types=1);

/**
 * Helper function to strip HTML tags for preview text
 */
function stripHtmlForPreview(string $html, int $maxLength = 150): string {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
    $text = trim($text);

    if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength) . '...';
    }

    return $text;
}

/**
 * Helper function to generate UUID for message_id
 */
function generateUUID(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * 1. Get Inbox - Get all mails in inbox for user
 */
function getInbox(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT
                    m.id,
                    m.message_id,
                    m.thread_id,
                    m.from_address,
                    m.from_user_id,
                    m.subject,
                    m.body_html,
                    m.body_text,
                    m.has_attachments,
                    m.sent_at,
                    m.priority,
                    r.is_read,
                    r.is_starred,
                    r.is_important,
                    r.folder_id,
                    r.label_ids
                FROM kdd_mails m
                INNER JOIN kdd_mail_recipients r ON m.id = r.mail_id
                WHERE r.recipient_user_id = ?
                    AND r.is_deleted = 0
                    AND m.authority_id = ?
                ORDER BY m.sent_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add preview text to each mail
        foreach ($mails as &$mail) {
            $mail['preview'] = stripHtmlForPreview($mail['body_html'] ?: $mail['body_text']);
            $mail['label_ids'] = $mail['label_ids'] ? json_decode($mail['label_ids'], true) : [];
        }

        http_response_code(200);
        echo json_encode($mails);

    } catch (PDOException $e) {
        error_log("DB Error in getInbox: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve inbox."]);
    }
}

/**
 * 2. Get Sent - Get all sent mails
 */
function getSent(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT
                    id,
                    message_id,
                    thread_id,
                    from_address,
                    from_user_id,
                    to_addresses,
                    cc_addresses,
                    bcc_addresses,
                    subject,
                    body_html,
                    body_text,
                    has_attachments,
                    sent_at,
                    priority
                FROM kdd_mails
                WHERE from_user_id = ?
                    AND is_sent = 1
                    AND authority_id = ?
                ORDER BY sent_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields and add preview
        foreach ($mails as &$mail) {
            $mail['to_addresses'] = $mail['to_addresses'] ? json_decode($mail['to_addresses'], true) : [];
            $mail['cc_addresses'] = $mail['cc_addresses'] ? json_decode($mail['cc_addresses'], true) : [];
            $mail['bcc_addresses'] = $mail['bcc_addresses'] ? json_decode($mail['bcc_addresses'], true) : [];
            $mail['preview'] = stripHtmlForPreview($mail['body_html'] ?: $mail['body_text']);
        }

        http_response_code(200);
        echo json_encode($mails);

    } catch (PDOException $e) {
        error_log("DB Error in getSent: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve sent mails."]);
    }
}

/**
 * 3. Get Drafts - Get all draft mails
 */
function getDrafts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT
                    id,
                    message_id,
                    thread_id,
                    from_address,
                    from_user_id,
                    to_addresses,
                    cc_addresses,
                    bcc_addresses,
                    subject,
                    body_html,
                    body_text,
                    has_attachments,
                    sent_at,
                    priority
                FROM kdd_mails
                WHERE from_user_id = ?
                    AND is_draft = 1
                    AND authority_id = ?
                ORDER BY sent_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields and add preview
        foreach ($mails as &$mail) {
            $mail['to_addresses'] = $mail['to_addresses'] ? json_decode($mail['to_addresses'], true) : [];
            $mail['cc_addresses'] = $mail['cc_addresses'] ? json_decode($mail['cc_addresses'], true) : [];
            $mail['bcc_addresses'] = $mail['bcc_addresses'] ? json_decode($mail['bcc_addresses'], true) : [];
            $mail['preview'] = stripHtmlForPreview($mail['body_html'] ?: $mail['body_text']);
        }

        http_response_code(200);
        echo json_encode($mails);

    } catch (PDOException $e) {
        error_log("DB Error in getDrafts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve drafts."]);
    }
}

/**
 * 4. Get Starred - Get starred mails
 */
function getStarred(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT
                    m.id,
                    m.message_id,
                    m.thread_id,
                    m.from_address,
                    m.from_user_id,
                    m.subject,
                    m.body_html,
                    m.body_text,
                    m.has_attachments,
                    m.sent_at,
                    m.priority,
                    r.is_read,
                    r.is_starred,
                    r.is_important,
                    r.folder_id,
                    r.label_ids
                FROM kdd_mails m
                INNER JOIN kdd_mail_recipients r ON m.id = r.mail_id
                WHERE r.recipient_user_id = ?
                    AND r.is_starred = 1
                    AND r.is_deleted = 0
                    AND m.authority_id = ?
                ORDER BY m.sent_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add preview text to each mail
        foreach ($mails as &$mail) {
            $mail['preview'] = stripHtmlForPreview($mail['body_html'] ?: $mail['body_text']);
            $mail['label_ids'] = $mail['label_ids'] ? json_decode($mail['label_ids'], true) : [];
        }

        http_response_code(200);
        echo json_encode($mails);

    } catch (PDOException $e) {
        error_log("DB Error in getStarred: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve starred mails."]);
    }
}

/**
 * 5. Get Trash - Get deleted mails
 */
function getTrash(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT
                    m.id,
                    m.message_id,
                    m.thread_id,
                    m.from_address,
                    m.from_user_id,
                    m.subject,
                    m.body_html,
                    m.body_text,
                    m.has_attachments,
                    m.sent_at,
                    m.priority,
                    r.is_read,
                    r.is_starred,
                    r.is_important,
                    r.deleted_at,
                    r.folder_id,
                    r.label_ids
                FROM kdd_mails m
                INNER JOIN kdd_mail_recipients r ON m.id = r.mail_id
                WHERE r.recipient_user_id = ?
                    AND r.is_deleted = 1
                    AND m.authority_id = ?
                ORDER BY r.deleted_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add preview text to each mail
        foreach ($mails as &$mail) {
            $mail['preview'] = stripHtmlForPreview($mail['body_html'] ?: $mail['body_text']);
            $mail['label_ids'] = $mail['label_ids'] ? json_decode($mail['label_ids'], true) : [];
        }

        http_response_code(200);
        echo json_encode($mails);

    } catch (PDOException $e) {
        error_log("DB Error in getTrash: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve trash."]);
    }
}

/**
 * 6. Get Mail - Get single mail by ID with full details
 */
function getMail(PDO $pdo, int $userId, int $authorityId): void {
    $mailId = filter_var($_GET['mail_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing mail_id.']);
        return;
    }

    try {
        // First check if user has access (is sender or recipient)
        $accessCheckSql = "SELECT COUNT(*) FROM (
            SELECT 1 FROM kdd_mails WHERE id = ? AND from_user_id = ? AND authority_id = ?
            UNION
            SELECT 1 FROM kdd_mail_recipients WHERE mail_id = ? AND recipient_user_id = ?
        ) AS access_check";

        $stmt = $pdo->prepare($accessCheckSql);
        $stmt->execute([$mailId, $userId, $authorityId, $mailId, $userId]);
        $hasAccess = $stmt->fetchColumn() > 0;

        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this mail.']);
            return;
        }

        // Get full mail details
        $mailSql = "SELECT
                        id,
                        message_id,
                        thread_id,
                        from_address,
                        from_user_id,
                        to_addresses,
                        cc_addresses,
                        bcc_addresses,
                        subject,
                        body_html,
                        body_text,
                        has_attachments,
                        is_draft,
                        is_sent,
                        sent_at,
                        priority
                    FROM kdd_mails
                    WHERE id = ? AND authority_id = ?";

        $stmt = $pdo->prepare($mailSql);
        $stmt->execute([$mailId, $authorityId]);
        $mail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mail) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            return;
        }

        // Parse JSON fields
        $mail['to_addresses'] = $mail['to_addresses'] ? json_decode($mail['to_addresses'], true) : [];
        $mail['cc_addresses'] = $mail['cc_addresses'] ? json_decode($mail['cc_addresses'], true) : [];
        $mail['bcc_addresses'] = $mail['bcc_addresses'] ? json_decode($mail['bcc_addresses'], true) : [];

        // Get recipient information
        $recipientSql = "SELECT
                            recipient_address,
                            recipient_user_id,
                            recipient_type,
                            is_read,
                            read_at,
                            is_starred,
                            is_important,
                            folder_id,
                            label_ids,
                            assigned_to_user_id,
                            status,
                            private_note
                        FROM kdd_mail_recipients
                        WHERE mail_id = ? AND recipient_user_id = ?";

        $stmt = $pdo->prepare($recipientSql);
        $stmt->execute([$mailId, $userId]);
        $recipientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recipientInfo) {
            $recipientInfo['label_ids'] = $recipientInfo['label_ids'] ? json_decode($recipientInfo['label_ids'], true) : [];
            $mail['recipient_info'] = $recipientInfo;

            // Auto mark as read if not already read and in inbox
            if (!$recipientInfo['is_read']) {
                $updateSql = "UPDATE kdd_mail_recipients
                             SET is_read = 1, read_at = NOW()
                             WHERE mail_id = ? AND recipient_user_id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$mailId, $userId]);
                $mail['recipient_info']['is_read'] = 1;
                $mail['recipient_info']['read_at'] = date('Y-m-d H:i:s');
            }
        }

        // Get all recipients (for display)
        $allRecipientsSql = "SELECT
                                recipient_address,
                                recipient_user_id,
                                recipient_type
                            FROM kdd_mail_recipients
                            WHERE mail_id = ?
                            ORDER BY recipient_type, recipient_address";

        $stmt = $pdo->prepare($allRecipientsSql);
        $stmt->execute([$mailId]);
        $mail['all_recipients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get attachments (if any) - Placeholder for future implementation
        $mail['attachments'] = [];

        http_response_code(200);
        echo json_encode($mail);

    } catch (PDOException $e) {
        error_log("DB Error in getMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve mail."]);
    }
}

/**
 * 7. Send Mail - Send new mail
 */
function sendMail(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $fromAddress = filter_var($data['from_address'] ?? '', FILTER_SANITIZE_EMAIL);
    if (empty($fromAddress)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid from_address']);
        return;
    }

    $toAddresses = $data['to_addresses'] ?? null;
    if (!is_array($toAddresses) || empty($toAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid to_addresses (must be non-empty array)']);
        return;
    }

    $subject = $data['subject'] ?? '';
    $bodyHtml = $data['body_html'] ?? '';
    $bodyText = $data['body_text'] ?? '';

    // Note: subject/body validation happens AFTER template loading (if template_id provided)

    $ccAddresses = $data['cc_addresses'] ?? [];
    if (!is_array($ccAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cc_addresses (must be array)']);
        return;
    }

    $bccAddresses = $data['bcc_addresses'] ?? [];
    if (!is_array($bccAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid bcc_addresses (must be array)']);
        return;
    }

    $priority = filter_var($data['priority'] ?? 'normal', FILTER_SANITIZE_SPECIAL_CHARS);
    $attachmentIds = $data['attachments'] ?? [];
    if (!is_array($attachmentIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid attachments (must be array)']);
        return;
    }

    $templateId = isset($data['template_id']) ? filter_var($data['template_id'], FILTER_VALIDATE_INT) : null;
    $templateVariables = $data['template_variables'] ?? [];
    if (!is_array($templateVariables)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid template_variables (must be array)']);
        return;
    }

    $signatureId = isset($data['signature_id']) ? filter_var($data['signature_id'], FILTER_VALIDATE_INT) : null;
    $useSignature = isset($data['use_signature']) ? (bool)$data['use_signature'] : true; // Default: use signature

    try {
        $pdo->beginTransaction();

        // Load and render template if provided
        if ($templateId) {
            $templateStmt = $pdo->prepare("
                SELECT subject_template, body_template
                FROM kdd_mail_templates
                WHERE id = ? AND authority_id = ? AND is_active = 1
            ");
            $templateStmt->execute([$templateId, $authorityId]);
            $template = $templateStmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new Exception("Template not found or inactive");
            }

            // Render template with variables
            foreach ($templateVariables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $template['subject_template'] = str_replace($placeholder, $value, $template['subject_template']);
                $template['body_template'] = str_replace($placeholder, $value, $template['body_template']);
            }

            // Use template values if subject/body not explicitly provided
            if (empty($subject)) {
                $subject = $template['subject_template'];
            }
            if (empty($bodyHtml)) {
                $bodyHtml = $template['body_template'];
            }
        }

        // Append signature if enabled
        if ($useSignature && !empty($bodyHtml)) {
            $signatureToUse = null;

            // If specific signature_id provided, use that
            if ($signatureId) {
                $sigStmt = $pdo->prepare("
                    SELECT signature_html
                    FROM kdd_mail_signatures
                    WHERE id = ? AND user_id = ? AND authority_id = ?
                ");
                $sigStmt->execute([$signatureId, $userId, $authorityId]);
                $signatureToUse = $sigStmt->fetchColumn();
            } else {
                // Otherwise, use default signature for this user
                $sigStmt = $pdo->prepare("
                    SELECT signature_html
                    FROM kdd_mail_signatures
                    WHERE user_id = ? AND authority_id = ? AND is_default = 1
                    LIMIT 1
                ");
                $sigStmt->execute([$userId, $authorityId]);
                $signatureToUse = $sigStmt->fetchColumn();
            }

            // Append signature to body_html
            if ($signatureToUse) {
                $bodyHtml .= "\n\n" . $signatureToUse;
            }
        }

        // Validate subject and body AFTER template/signature processing
        if (empty($subject)) {
            throw new Exception('Missing subject (either provide subject or use template_id)');
        }

        if (empty($bodyHtml) && empty($bodyText)) {
            throw new Exception('Missing body (either provide body_html/body_text or use template_id)');
        }

        // Verify from_address belongs to user
        $checkSql = "SELECT COUNT(*) FROM kdd_mail_accounts
                     WHERE email = ? AND current_user_id = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$fromAddress, $userId]);

        if ($stmt->fetchColumn() == 0) {
            throw new Exception("You don't have permission to send from this address.");
        }

        // Generate unique message_id
        $messageId = generateUUID() . '@' . parse_url($fromAddress, PHP_URL_HOST);

        // Insert into kdd_mails
        $mailSql = "INSERT INTO kdd_mails
                    (message_id, from_address, from_user_id, to_addresses, cc_addresses, bcc_addresses,
                     subject, body_html, body_text, has_attachments, is_draft, is_sent, sent_at,
                     priority, authority_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), ?, ?)";

        $hasAttachments = !empty($attachmentIds);

        $stmt = $pdo->prepare($mailSql);
        $stmt->execute([
            $messageId,
            $fromAddress,
            $userId,
            json_encode($toAddresses),
            json_encode($ccAddresses),
            json_encode($bccAddresses),
            $subject,
            $bodyHtml,
            $bodyText,
            $hasAttachments ? 1 : 0,
            $priority,
            $authorityId
        ]);

        $mailId = $pdo->lastInsertId();

        // Insert recipients
        $recipientSql = "INSERT INTO kdd_mail_recipients
                        (mail_id, recipient_address, recipient_user_id, recipient_type, authority_id)
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($recipientSql);

        // Helper function to resolve user_id from email
        $getUserIdFromEmail = function($email) use ($pdo, $authorityId) {
            $sql = "SELECT current_user_id FROM kdd_mail_accounts
                    WHERE email = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetchColumn() ?: null;
        };

        // Insert TO recipients
        foreach ($toAddresses as $toAddr) {
            $recipientUserId = $getUserIdFromEmail($toAddr);
            $stmt->execute([$mailId, $toAddr, $recipientUserId, 'to', $authorityId]);
        }

        // Insert CC recipients
        foreach ($ccAddresses as $ccAddr) {
            $recipientUserId = $getUserIdFromEmail($ccAddr);
            $stmt->execute([$mailId, $ccAddr, $recipientUserId, 'cc', $authorityId]);
        }

        // Insert BCC recipients
        foreach ($bccAddresses as $bccAddr) {
            $recipientUserId = $getUserIdFromEmail($bccAddr);
            $stmt->execute([$mailId, $bccAddr, $recipientUserId, 'bcc', $authorityId]);
        }

        // Link attachments if provided
        if (!empty($attachmentIds)) {
            // Verify all attachment IDs exist (either unassigned OR in a draft state)
            $placeholders = implode(',', array_fill(0, count($attachmentIds), '?'));
            $checkAttStmt = $pdo->prepare("
                SELECT id FROM kdd_mail_attachments
                WHERE id IN ($placeholders)
            ");
            $checkAttStmt->execute($attachmentIds);
            $validAttachments = $checkAttStmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($validAttachments) !== count($attachmentIds)) {
                throw new Exception("Some attachment IDs are invalid");
            }

            // Update attachments to link them to this mail
            $updateAttStmt = $pdo->prepare("
                UPDATE kdd_mail_attachments
                SET mail_id = ?
                WHERE id IN ($placeholders)
            ");
            $params = array_merge([$mailId], $attachmentIds);
            $updateAttStmt->execute($params);

            error_log("Linked " . count($attachmentIds) . " attachments to mail ID: $mailId");
        }

        // Send socket notifications to recipients
        $socketUrl = getenv('SOCKET_SERVER_URL') ?: 'http://socket-server:3001';
        $apiKey = getenv('API_KEY') ?: '';

        // Get all recipient user IDs
        $recipientUserIds = [];
        foreach ($toAddresses as $toAddr) {
            $recipUserId = $getUserIdFromEmail($toAddr);
            if ($recipUserId) {
                $recipientUserIds[] = $recipUserId;
            }
        }
        foreach ($ccAddresses as $ccAddr) {
            $recipUserId = $getUserIdFromEmail($ccAddr);
            if ($recipUserId) {
                $recipientUserIds[] = $recipUserId;
            }
        }
        foreach ($bccAddresses as $bccAddr) {
            $recipUserId = $getUserIdFromEmail($bccAddr);
            if ($recipUserId) {
                $recipientUserIds[] = $recipUserId;
            }
        }

        // Remove duplicates
        $recipientUserIds = array_unique($recipientUserIds);

        // Send notification to each recipient
        foreach ($recipientUserIds as $recipientId) {
            $notificationPayload = [
                'namespace' => 'mail',
                'event' => 'new_mail',
                'userId' => $recipientId,
                'data' => [
                    'mail_id' => $mailId,
                    'from_address' => $fromAddress,
                    'subject' => $subject,
                    'preview' => substr(strip_tags($bodyHtml ?: $bodyText), 0, 100),
                    'has_attachments' => $hasAttachments,
                    'priority' => $priority,
                    'timestamp' => date('c')
                ]
            ];

            // Non-blocking HTTP request to socket server
            $ch = curl_init("$socketUrl/api/notify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationPayload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // Non-blocking, 500ms timeout
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                error_log("Socket notification sent successfully to user $recipientId for mail $mailId");
            } else {
                error_log("Failed to send socket notification to user $recipientId: HTTP $httpCode");
            }
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Mail sent successfully.',
            'mail_id' => $mailId
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error in sendMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB Error in sendMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not send mail."]);
    }
}

/**
 * 8. Save Draft - Save mail as draft
 */
function saveDraft(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $draftId = filter_var($data['draft_id'] ?? null, FILTER_VALIDATE_INT);

    $fromAddress = filter_var($data['from_address'] ?? '', FILTER_SANITIZE_EMAIL);
    if (empty($fromAddress)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid from_address']);
        return;
    }

    $toAddresses = $data['to_addresses'] ?? [];
    if (!is_array($toAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid to_addresses (must be array)']);
        return;
    }

    $ccAddresses = $data['cc_addresses'] ?? [];
    if (!is_array($ccAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cc_addresses (must be array)']);
        return;
    }

    $bccAddresses = $data['bcc_addresses'] ?? [];
    if (!is_array($bccAddresses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid bcc_addresses (must be array)']);
        return;
    }

    // For drafts, require at least some content (to_addresses OR subject/body)
    $subject = $data['subject'] ?? '';
    $bodyHtml = $data['body_html'] ?? '';
    $bodyText = $data['body_text'] ?? '';

    if (empty($toAddresses) && empty($subject) && empty($bodyHtml) && empty($bodyText)) {
        http_response_code(400);
        echo json_encode(['error' => 'Draft must contain at least to_addresses, subject, or body']);
        return;
    }

    $priority = filter_var($data['priority'] ?? 'normal', FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        if ($draftId) {
            // Update existing draft
            $updateSql = "UPDATE kdd_mails SET
                         from_address = ?,
                         to_addresses = ?,
                         cc_addresses = ?,
                         bcc_addresses = ?,
                         subject = ?,
                         body_html = ?,
                         body_text = ?,
                         priority = ?
                         WHERE id = ? AND from_user_id = ? AND is_draft = 1 AND authority_id = ?";

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                $fromAddress,
                json_encode($toAddresses),
                json_encode($ccAddresses),
                json_encode($bccAddresses),
                $subject,
                $bodyHtml,
                $bodyText,
                $priority,
                $draftId,
                $userId,
                $authorityId
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Draft updated successfully.',
                'draft_id' => $draftId
            ]);
        } else {
            // Create new draft
            $messageId = generateUUID() . '@draft';

            $insertSql = "INSERT INTO kdd_mails
                         (message_id, from_address, from_user_id, to_addresses, cc_addresses, bcc_addresses,
                          subject, body_html, body_text, is_draft, is_sent, priority, authority_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?)";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $messageId,
                $fromAddress,
                $userId,
                json_encode($toAddresses),
                json_encode($ccAddresses),
                json_encode($bccAddresses),
                $subject,
                $bodyHtml,
                $bodyText,
                $priority,
                $authorityId
            ]);

            $newDraftId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Draft saved successfully.',
                'draft_id' => $newDraftId
            ]);
        }

    } catch (PDOException $e) {
        error_log("DB Error in saveDraft: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not save draft."]);
    }
}

/**
 * 9. Mark as Read - Mark mail as read/unread
 */
function markAsRead(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_id']);
        return;
    }

    $isRead = filter_var($data['is_read'] ?? true, FILTER_VALIDATE_BOOLEAN);

    try {
        $sql = "UPDATE kdd_mail_recipients
                SET is_read = ?, read_at = " . ($isRead ? "NOW()" : "NULL") . "
                WHERE mail_id = ? AND recipient_user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$isRead ? 1 : 0, $mailId, $userId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Mail marked as ' . ($isRead ? 'read' : 'unread') . '.'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied.']);
        }

    } catch (PDOException $e) {
        error_log("DB Error in markAsRead: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update read status."]);
    }
}

/**
 * 10. Mark as Starred - Star/unstar mail
 */
function markAsStarred(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_id']);
        return;
    }

    // Validate is_starred if provided
    if (isset($data['is_starred']) && !is_bool($data['is_starred']) && $data['is_starred'] !== 'true' && $data['is_starred'] !== 'false' && $data['is_starred'] !== 1 && $data['is_starred'] !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid is_starred (must be boolean)']);
        return;
    }

    $isStarred = filter_var($data['is_starred'] ?? true, FILTER_VALIDATE_BOOLEAN);

    try {
        $sql = "UPDATE kdd_mail_recipients
                SET is_starred = ?
                WHERE mail_id = ? AND recipient_user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$isStarred ? 1 : 0, $mailId, $userId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Mail ' . ($isStarred ? 'starred' : 'unstarred') . ' successfully.'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied.']);
        }

    } catch (PDOException $e) {
        error_log("DB Error in markAsStarred: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update starred status."]);
    }
}

/**
 * 11. Delete Mail - Move to trash
 */
function deleteMail(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_id']);
        return;
    }

    try {
        $sql = "UPDATE kdd_mail_recipients
                SET is_deleted = 1, deleted_at = NOW()
                WHERE mail_id = ? AND recipient_user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailId, $userId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Mail moved to trash.'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied.']);
        }

    } catch (PDOException $e) {
        error_log("DB Error in deleteMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete mail."]);
    }
}

/**
 * 12. Restore Mail - Restore from trash
 */
function restoreMail(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_id']);
        return;
    }

    try {
        $sql = "UPDATE kdd_mail_recipients
                SET is_deleted = 0, deleted_at = NULL
                WHERE mail_id = ? AND recipient_user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mailId, $userId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Mail restored successfully.'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied.']);
        }

    } catch (PDOException $e) {
        error_log("DB Error in restoreMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not restore mail."]);
    }
}

/**
 * 13. Permanent Delete Mail - Delete permanently
 */
function permanentDeleteMail(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $mailId = filter_var($data['mail_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$mailId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing mail_id.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Delete from recipients table
        $deleteSql = "DELETE FROM kdd_mail_recipients
                     WHERE mail_id = ? AND recipient_user_id = ?";
        $stmt = $pdo->prepare($deleteSql);
        $stmt->execute([$mailId, $userId]);

        if ($stmt->rowCount() == 0) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied.']);
            return;
        }

        // Check if there are any remaining recipients
        $checkSql = "SELECT COUNT(*) FROM kdd_mail_recipients WHERE mail_id = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$mailId]);
        $remainingRecipients = $stmt->fetchColumn();

        // If no more recipients, delete the mail itself and attachments
        if ($remainingRecipients == 0) {
            // TODO: Delete attachments

            $deleteMailSql = "DELETE FROM kdd_mails WHERE id = ? AND authority_id = ?";
            $stmt = $pdo->prepare($deleteMailSql);
            $stmt->execute([$mailId, $authorityId]);
        }

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mail permanently deleted.'
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB Error in permanentDeleteMail: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not permanently delete mail."]);
    }
}

/**
 * 14. Bulk Mark as Read - Mark multiple mails as read
 */
function bulkMarkAsRead(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailIds = $data['mail_ids'] ?? null;
    if (!is_array($mailIds) || empty($mailIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_ids (must be non-empty array)']);
        return;
    }

    // Validate all mail_ids are integers
    foreach ($mailIds as $id) {
        if (!is_int($id) && !ctype_digit((string)$id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid mail_ids (all IDs must be integers)']);
            return;
        }
    }

    $isRead = filter_var($data['is_read'] ?? true, FILTER_VALIDATE_BOOLEAN);

    try {
        $placeholders = rtrim(str_repeat('?,', count($mailIds)), ',');
        $sql = "UPDATE kdd_mail_recipients
                SET is_read = ?, read_at = " . ($isRead ? "NOW()" : "NULL") . "
                WHERE mail_id IN ($placeholders) AND recipient_user_id = ?";

        $params = array_merge([$isRead ? 1 : 0], $mailIds, [$userId]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $stmt->rowCount() . ' mails marked as ' . ($isRead ? 'read' : 'unread') . '.',
            'affected_count' => $stmt->rowCount()
        ]);

    } catch (PDOException $e) {
        error_log("DB Error in bulkMarkAsRead: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update read status."]);
    }
}

/**
 * 15. Bulk Delete - Move multiple mails to trash
 */
function bulkDelete(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailIds = $data['mail_ids'] ?? null;
    if (!is_array($mailIds) || empty($mailIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_ids (must be non-empty array)']);
        return;
    }

    // Validate all mail_ids are integers
    foreach ($mailIds as $id) {
        if (!is_int($id) && !ctype_digit((string)$id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid mail_ids (all IDs must be integers)']);
            return;
        }
    }

    try {
        $placeholders = rtrim(str_repeat('?,', count($mailIds)), ',');
        $sql = "UPDATE kdd_mail_recipients
                SET is_deleted = 1, deleted_at = NOW()
                WHERE mail_id IN ($placeholders) AND recipient_user_id = ?";

        $params = array_merge($mailIds, [$userId]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $stmt->rowCount() . ' mails moved to trash.',
            'affected_count' => $stmt->rowCount()
        ]);

    } catch (PDOException $e) {
        error_log("DB Error in bulkDelete: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete mails."]);
    }
}

/**
 * 16. Bulk Move - Move multiple mails to folder
 */
function bulkMove(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    // VALIDATION
    $mailIds = $data['mail_ids'] ?? null;
    if (!is_array($mailIds) || empty($mailIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid mail_ids (must be non-empty array)']);
        return;
    }

    // Validate all mail_ids are integers
    foreach ($mailIds as $id) {
        if (!is_int($id) && !ctype_digit((string)$id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid mail_ids (all IDs must be integers)']);
            return;
        }
    }

    // Validate folder_id (can be null or integer)
    $folderId = $data['folder_id'] ?? null;
    if ($folderId !== null) {
        $folderId = filter_var($folderId, FILTER_VALIDATE_INT);
        if ($folderId === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid folder_id (must be integer or null)']);
            return;
        }
    }

    try {
        $placeholders = rtrim(str_repeat('?,', count($mailIds)), ',');
        $sql = "UPDATE kdd_mail_recipients
                SET folder_id = ?
                WHERE mail_id IN ($placeholders) AND recipient_user_id = ?";

        $params = array_merge([$folderId], $mailIds, [$userId]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $stmt->rowCount() . ' mails moved to folder.',
            'affected_count' => $stmt->rowCount()
        ]);

    } catch (PDOException $e) {
        error_log("DB Error in bulkMove: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not move mails."]);
    }
}

/**
 * BulkAction - Universal bulk action router for frontend compatibility
 * Dispatches to specific bulk functions based on action parameter
 */
function bulkAction(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $action = $data['action'] ?? '';

    // Dispatch to appropriate bulk function
    switch($action) {
        case 'mark_read':
        case 'mark_unread':
            bulkMarkAsRead($pdo, $userId, $authorityId);
            break;
        case 'star':
        case 'unstar':
            // Not implemented yet
            http_response_code(501);
            echo json_encode(['error' => 'Bulk star/unstar not yet implemented']);
            break;
        case 'delete':
            bulkDelete($pdo, $userId, $authorityId);
            break;
        case 'move':
            bulkMove($pdo, $userId, $authorityId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid bulk action: ' . htmlspecialchars($action)]);
            break;
    }
}

/**
 * MoveToFolder - Move a single mail to a folder (frontend compatibility)
 */
function moveToFolder(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if ($data === null) return;

    $mailId = (int)($data['mail_id'] ?? 0);
    $folderId = $data['folder_id'] ?? null;

    if ($mailId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid mail ID required.']);
        return;
    }

    // Allow null for "remove from folder"
    if ($folderId !== null) {
        $folderId = (int)$folderId;
    }

    try {
        // Verify mail belongs to user's authority
        $checkSql = "SELECT id FROM kdd_mails WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$mailId, $authorityId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found.']);
            return;
        }

        // If folder_id provided, verify it exists
        if ($folderId !== null) {
            $folderCheckSql = "SELECT id FROM kdd_mail_folders WHERE id = ? AND user_id = ? AND authority_id = ?";
            $folderCheckStmt = $pdo->prepare($folderCheckSql);
            $folderCheckStmt->execute([$folderId, $userId, $authorityId]);

            if (!$folderCheckStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Folder not found.']);
                return;
            }
        }

        // Update mail folder
        $updateSql = "UPDATE kdd_mails SET folder_id = ? WHERE id = ? AND authority_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$folderId, $mailId, $authorityId]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mail moved successfully.'
        ]);

    } catch (PDOException $e) {
        error_log("DB Error in moveToFolder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not move mail."]);
    }
}

// Note: assignMail() and updateMailStatus() are implemented in company_mailboxes.php
// They belong there because they handle company mailbox workflows and permissions

// Note: uploadAttachment(), downloadAttachment(), deleteAttachment(), and getAttachments()
// are implemented in attachments.php module, not here.
