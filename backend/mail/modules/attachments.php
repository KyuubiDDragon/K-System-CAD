<?php
/**
 * Mail System: Attachments Module
 * Handles file attachment operations for mail system
 */

declare(strict_types=1);

// Constants for file validation
const ALLOWED_MIME_TYPES = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png'
];

const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

const MAX_FILE_SIZE = 10485760; // 10 MB in bytes
const MAX_TOTAL_SIZE = 26214400; // 25 MB in bytes
const MAX_FILES_PER_MAIL = 5;
const STORAGE_QUOTA = 1073741824; // 1 GB in bytes

/**
 * Upload file attachment
 * Validates file, stores it securely, and updates database
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return void Outputs JSON response
 */
function uploadAttachment(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        // Check for upload errors
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'File upload error: ' . $_FILES['file']['error']]);
            return;
        }

        $file = $_FILES['file'];
        $mailId = isset($_POST['mail_id']) ? (int)$_POST['mail_id'] : null;
        $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : null;

        // Get file info
        $originalFilename = basename($file['name']);
        $fileSize = $file['size'];
        $tmpPath = $file['tmp_name'];

        // Detect MIME type from actual file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        // Validate attachment
        $validation = validateAttachment($originalFilename, $fileSize, $mimeType);
        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => $validation['error']]);
            return;
        }

        // If mail_id is provided, check existing attachments count and total size
        if ($mailId !== null) {
            // Check if user has access to this mail
            $mailCheckStmt = $pdo->prepare("
                SELECT
                    m.id,
                    m.from_user_id as user_id,
                    m.has_attachments
                FROM kdd_mails m
                WHERE m.id = :mail_id
                    AND m.authority_id = :authority_id
            ");
            $mailCheckStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
            $mailCheckStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
            $mailCheckStmt->execute();
            $mail = $mailCheckStmt->fetch(PDO::FETCH_ASSOC);

            if (!$mail || $mail['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied to this mail']);
                return;
            }

            // Check attachment count limit
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_size
                FROM kdd_mail_attachments
                WHERE mail_id = :mail_id
            ");
            $countStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
            $countStmt->execute();
            $attachmentStats = $countStmt->fetch(PDO::FETCH_ASSOC);

            if ($attachmentStats['count'] >= MAX_FILES_PER_MAIL) {
                http_response_code(400);
                echo json_encode(['error' => 'Maximum ' . MAX_FILES_PER_MAIL . ' files per mail allowed']);
                return;
            }

            if (($attachmentStats['total_size'] + $fileSize) > MAX_TOTAL_SIZE) {
                http_response_code(400);
                echo json_encode(['error' => 'Total attachment size exceeds 25 MB limit']);
                return;
            }

            $accountId = $mail['sender_account_id'];
        }

        // Check user's storage quota
        if ($accountId !== null) {
            $quotaStmt = $pdo->prepare("
                SELECT storage_used, storage_quota
                FROM kdd_mail_accounts
                WHERE id = :account_id
                    AND user_id = :user_id
                    AND authority_id = :authority_id
            ");
            $quotaStmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $quotaStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $quotaStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
            $quotaStmt->execute();
            $account = $quotaStmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                http_response_code(403);
                echo json_encode(['error' => 'Mail account not found or access denied']);
                return;
            }

            $storageQuota = $account['storage_quota'] ?? STORAGE_QUOTA;
            if (($account['storage_used'] + $fileSize) > $storageQuota) {
                http_response_code(400);
                echo json_encode(['error' => 'Storage quota exceeded']);
                return;
            }
        }

        // Generate unique filename with UUID
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $filename = $uuid . '.' . $extension;

        // Create directory structure: /uploads/mail/attachments/YYYY/MM/
        $year = date('Y');
        $month = date('m');
        $uploadDir = __DIR__ . '/../../uploads/mail/attachments/' . $year . '/' . $month;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create upload directory']);
                return;
            }
        }

        $filePath = $uploadDir . '/' . $filename;
        $relativePath = 'uploads/mail/attachments/' . $year . '/' . $month . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($tmpPath, $filePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file']);
            return;
        }

        // Set file permissions
        chmod($filePath, 0644);

        // Insert into database
        $insertStmt = $pdo->prepare("
            INSERT INTO kdd_mail_attachments (
                mail_id,
                filename,
                original_filename,
                file_path,
                file_size,
                mime_type,
                is_safe,
                scan_status
            ) VALUES (
                :mail_id,
                :filename,
                :original_filename,
                :file_path,
                :file_size,
                :mime_type,
                1,
                'pending'
            )
        ");
        $insertStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
        $insertStmt->bindParam(':filename', $filename, PDO::PARAM_STR);
        $insertStmt->bindParam(':original_filename', $originalFilename, PDO::PARAM_STR);
        $insertStmt->bindParam(':file_path', $relativePath, PDO::PARAM_STR);
        $insertStmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
        $insertStmt->bindParam(':mime_type', $mimeType, PDO::PARAM_STR);
        $insertStmt->execute();

        $attachmentId = (int)$pdo->lastInsertId();

        // Update mail.has_attachments flag if mail_id is provided
        if ($mailId !== null) {
            $updateMailStmt = $pdo->prepare("
                UPDATE kdd_mail
                SET has_attachments = 1
                WHERE id = :mail_id
            ");
            $updateMailStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
            $updateMailStmt->execute();
        }

        // Update storage_used in mail account
        if ($accountId !== null) {
            $updateStorageStmt = $pdo->prepare("
                UPDATE kdd_mail_accounts
                SET storage_used = storage_used + :file_size
                WHERE id = :account_id
            ");
            $updateStorageStmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
            $updateStorageStmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $updateStorageStmt->execute();
        }

        error_log("File uploaded successfully: $filename (ID: $attachmentId) for user: $userId");

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'attachment_id' => $attachmentId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType
        ]);

    } catch (PDOException $e) {
        error_log("Database error in uploadAttachment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to upload attachment',
            'details' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("Error in uploadAttachment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to upload attachment',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Download attachment
 * Checks user access and serves the file for download
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return void Outputs file content
 */
function downloadAttachment(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $attachmentId = isset($_GET['attachment_id']) ? (int)$_GET['attachment_id'] : 0;

        if ($attachmentId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Attachment ID is required']);
            return;
        }

        // Get attachment and check user access
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.filename,
                a.original_filename,
                a.file_path,
                a.file_size,
                a.mime_type,
                a.mail_id,
                m.from_user_id as sender_user_id
            FROM kdd_mail_attachments a
            JOIN kdd_mails m ON m.id = a.mail_id
            WHERE a.id = :attachment_id
                AND m.authority_id = :authority_id
        ");
        $stmt->bindParam(':attachment_id', $attachmentId, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attachment) {
            http_response_code(404);
            echo json_encode(['error' => 'Attachment not found or access denied']);
            return;
        }

        // Check if user is sender
        $isSender = ($attachment['sender_user_id'] == $userId);

        // Check if user is recipient
        $isRecipient = false;
        if (!$isSender) {
            $recipStmt = $pdo->prepare("
                SELECT COUNT(*) FROM kdd_mail_recipients
                WHERE mail_id = :mail_id AND recipient_user_id = :user_id
            ");
            $recipStmt->execute([':mail_id' => $attachment['mail_id'], ':user_id' => $userId]);
            $isRecipient = $recipStmt->fetchColumn() > 0;
        }

        $hasAccess = ($isSender || $isRecipient);

        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this attachment']);
            return;
        }

        // Build full file path
        $filePath = __DIR__ . '/../../' . $attachment['file_path'];

        // Check if file exists
        if (!file_exists($filePath)) {
            error_log("Attachment file not found on disk: $filePath");
            http_response_code(404);
            echo json_encode(['error' => 'Attachment file not found on disk']);
            return;
        }

        // Set headers for download
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output file content
        readfile($filePath);
        exit();

    } catch (PDOException $e) {
        error_log("Database error in downloadAttachment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to download attachment',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Delete attachment
 * Removes attachment file and database record, updates storage quota
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return void Outputs JSON response
 */
function deleteAttachment(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        $attachmentId = $data['attachment_id'] ?? 0;

        if ($attachmentId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Attachment ID is required']);
            return;
        }

        // Get attachment and verify ownership
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.file_path,
                a.file_size,
                a.mail_id,
                m.from_user_id as user_id
            FROM kdd_mail_attachments a
            JOIN kdd_mails m ON m.id = a.mail_id
            WHERE a.id = :attachment_id
                AND m.authority_id = :authority_id
        ");
        $stmt->bindParam(':attachment_id', $attachmentId, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attachment) {
            http_response_code(404);
            echo json_encode(['error' => 'Attachment not found or access denied']);
            return;
        }

        // Check if user is the sender (only sender can delete attachments)
        if ($attachment['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Only the sender can delete attachments']);
            return;
        }

        $mailId = $attachment['mail_id'];
        $fileSize = $attachment['file_size'];

        // Get sender's account ID for storage update
        $accountStmt = $pdo->prepare("
            SELECT id FROM kdd_mail_accounts
            WHERE current_user_id = ? AND authority_id = ?
            LIMIT 1
        ");
        $accountStmt->execute([$userId, $authorityId]);
        $accountId = $accountStmt->fetchColumn();

        // Delete file from disk
        $filePath = __DIR__ . '/../../' . $attachment['file_path'];
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                error_log("Failed to delete file from disk: $filePath");
            }
        }

        // Delete from database
        $deleteStmt = $pdo->prepare("
            DELETE FROM kdd_mail_attachments
            WHERE id = :attachment_id
        ");
        $deleteStmt->bindParam(':attachment_id', $attachmentId, PDO::PARAM_INT);
        $deleteStmt->execute();

        // Update storage_used
        $updateStorageStmt = $pdo->prepare("
            UPDATE kdd_mail_accounts
            SET storage_used = GREATEST(0, storage_used - :file_size)
            WHERE id = :account_id
        ");
        $updateStorageStmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
        $updateStorageStmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
        $updateStorageStmt->execute();

        // Check if mail has any remaining attachments
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM kdd_mail_attachments
            WHERE mail_id = :mail_id
        ");
        $checkStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Update has_attachments flag if no more attachments
        if ($result['count'] == 0) {
            $updateMailStmt = $pdo->prepare("
                UPDATE kdd_mail
                SET has_attachments = 0
                WHERE id = :mail_id
            ");
            $updateMailStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
            $updateMailStmt->execute();
        }

        error_log("Attachment deleted: ID $attachmentId by user: $userId");

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Database error in deleteAttachment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to delete attachment',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Get all attachments for a mail
 * Returns array of attachment objects
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return void Outputs JSON response
 */
function getAttachments(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $mailId = isset($_GET['mail_id']) ? (int)$_GET['mail_id'] : 0;

        if ($mailId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Mail ID is required']);
            return;
        }

        // Check if user has access to this mail (sender or recipient)
        $accessStmt = $pdo->prepare("
            SELECT m.id, m.from_user_id as sender_user_id
            FROM kdd_mails m
            WHERE m.id = :mail_id AND m.authority_id = :authority_id
        ");
        $accessStmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
        $accessStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $accessStmt->execute();
        $mail = $accessStmt->fetch(PDO::FETCH_ASSOC);

        if (!$mail) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied']);
            return;
        }

        // Check if user is sender
        $isSender = ($mail['sender_user_id'] == $userId);

        // Check if user is recipient
        $isRecipient = false;
        if (!$isSender) {
            $recipStmt = $pdo->prepare("
                SELECT COUNT(*) FROM kdd_mail_recipients
                WHERE mail_id = :mail_id AND recipient_user_id = :user_id
            ");
            $recipStmt->execute([':mail_id' => $mailId, ':user_id' => $userId]);
            $isRecipient = $recipStmt->fetchColumn() > 0;
        }

        $hasAccess = ($isSender || $isRecipient);

        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this mail']);
            return;
        }

        // Get all attachments
        $stmt = $pdo->prepare("
            SELECT
                id,
                filename,
                original_filename,
                file_size,
                mime_type,
                is_safe,
                scan_status
            FROM kdd_mail_attachments
            WHERE mail_id = :mail_id
            ORDER BY id ASC
        ");
        $stmt->bindParam(':mail_id', $mailId, PDO::PARAM_INT);
        $stmt->execute();
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format attachments
        $formattedAttachments = array_map(function($att) {
            return [
                'id' => (int)$att['id'],
                'filename' => $att['filename'],
                'original_filename' => $att['original_filename'],
                'file_size' => (int)$att['file_size'],
                'mime_type' => $att['mime_type'],
                'is_safe' => (bool)$att['is_safe'],
                'scan_status' => $att['scan_status']
            ];
        }, $attachments);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'attachments' => $formattedAttachments
        ]);

    } catch (PDOException $e) {
        error_log("Database error in getAttachments: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to retrieve attachments',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Validate attachment file
 * Checks MIME type, file size, and extension
 *
 * @param string $filename Original filename
 * @param int $fileSize File size in bytes
 * @param string $mimeType MIME type
 * @return array Array with 'valid' boolean and 'error' string
 */
function validateAttachment(string $filename, int $fileSize, string $mimeType): array {
    // Check file size
    if ($fileSize > MAX_FILE_SIZE) {
        return [
            'valid' => false,
            'error' => 'File size exceeds maximum allowed size of 10 MB'
        ];
    }

    if ($fileSize === 0) {
        return [
            'valid' => false,
            'error' => 'File is empty'
        ];
    }

    // Check MIME type
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return [
            'valid' => false,
            'error' => 'File type not allowed. Allowed types: PDF, JPG, JPEG, PNG'
        ];
    }

    // Check file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return [
            'valid' => false,
            'error' => 'File extension not allowed. Allowed extensions: ' . implode(', ', ALLOWED_EXTENSIONS)
        ];
    }

    // Additional MIME type validation based on extension
    $extensionMimeMap = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png' => ['image/png']
    ];

    if (isset($extensionMimeMap[$extension])) {
        if (!in_array($mimeType, $extensionMimeMap[$extension])) {
            return [
                'valid' => false,
                'error' => 'File extension does not match file content'
            ];
        }
    }

    return [
        'valid' => true,
        'error' => null
    ];
}

?>
