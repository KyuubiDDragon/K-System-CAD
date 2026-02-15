<?php
/**
 * Mail Module: Folder and Label Management
 * Handles custom folders and labels/tags for mail organization
 */
declare(strict_types=1);

/**
 * Get all folders for user
 */
function getFolders(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id, user_id, authority_id, name, color, icon,
                sort_order, is_system, created_at
            FROM kdd_mail_folders
            WHERE user_id = :user_id
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['folders' => $folders]);
    } catch (Exception $e) {
        error_log("Error in getFolders: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve folders']);
    }
}

/**
 * Create custom folder
 */
function createFolder(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder name is required']);
            return;
        }

        // Check if folder name already exists for this user
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_folders
            WHERE user_id = :user_id AND name = :name
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder with this name already exists']);
            return;
        }

        // Get the next sort_order
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order
            FROM kdd_mail_folders
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $nextOrder = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];

        $stmt = $pdo->prepare("
            INSERT INTO kdd_mail_folders (
                user_id, authority_id, name, color, icon, sort_order, is_system
            ) VALUES (
                :user_id, :authority_id, :name, :color, :icon, :sort_order, 0
            )
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':color', $data['color'] ?? '#1976D2', PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? 'mdi-folder', PDO::PARAM_STR);
        $stmt->bindParam(':sort_order', $nextOrder, PDO::PARAM_INT);

        $stmt->execute();
        $folderId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'folder_id' => (int)$folderId]);
    } catch (Exception $e) {
        error_log("Error in createFolder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create folder']);
    }
}

/**
 * Update folder
 */
function updateFolder(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        if (empty($data['folder_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder ID is required']);
            return;
        }

        // Check ownership and that it's not a system folder
        $stmt = $pdo->prepare("
            SELECT id, is_system FROM kdd_mail_folders
            WHERE id = :folder_id AND user_id = :user_id
        ");
        $stmt->bindParam(':folder_id', $data['folder_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $folder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$folder) {
            http_response_code(404);
            echo json_encode(['error' => 'Folder not found or access denied']);
            return;
        }

        if ($folder['is_system']) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot edit system folders']);
            return;
        }

        // Build dynamic update query
        $updateFields = [];
        $params = [':folder_id' => $data['folder_id']];

        $allowedFields = ['name', 'color', 'icon', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $sql = "UPDATE kdd_mail_folders SET " . implode(', ', $updateFields) . " WHERE id = :folder_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in updateFolder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update folder']);
    }
}

/**
 * Delete folder
 */
function deleteFolder(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null || empty($data['folder_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder ID is required']);
            return;
        }

        // Check ownership and that it's not a system folder
        $stmt = $pdo->prepare("
            SELECT id, is_system FROM kdd_mail_folders
            WHERE id = :folder_id AND user_id = :user_id
        ");
        $stmt->bindParam(':folder_id', $data['folder_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $folder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$folder) {
            http_response_code(404);
            echo json_encode(['error' => 'Folder not found or access denied']);
            return;
        }

        if ($folder['is_system']) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete system folders']);
            return;
        }

        $pdo->beginTransaction();

        // Move mails in this folder to inbox (folder_id = NULL or inbox folder)
        // Assuming folder_id in kdd_mail_recipients can be set to NULL
        $stmt = $pdo->prepare("
            UPDATE kdd_mail_recipients
            SET folder_id = NULL
            WHERE folder_id = :folder_id AND recipient_user_id = :user_id
        ");
        $stmt->bindParam(':folder_id', $data['folder_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete the folder
        $stmt = $pdo->prepare("
            DELETE FROM kdd_mail_folders
            WHERE id = :folder_id AND user_id = :user_id
        ");
        $stmt->bindParam(':folder_id', $data['folder_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in deleteFolder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete folder']);
    }
}

/**
 * Get all labels for user
 */
function getLabels(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id, user_id, authority_id, name, color, icon,
                sort_order, created_at
            FROM kdd_mail_labels
            WHERE user_id = :user_id
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['labels' => $labels]);
    } catch (Exception $e) {
        error_log("Error in getLabels: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve labels']);
    }
}

/**
 * Create custom label/tag
 */
function createLabel(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Label name is required']);
            return;
        }

        // Check if label name already exists for this user
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_labels
            WHERE user_id = :user_id AND name = :name
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Label with this name already exists']);
            return;
        }

        // Get the next sort_order
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order
            FROM kdd_mail_labels
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $nextOrder = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];

        $stmt = $pdo->prepare("
            INSERT INTO kdd_mail_labels (
                user_id, authority_id, name, color, icon, sort_order
            ) VALUES (
                :user_id, :authority_id, :name, :color, :icon, :sort_order
            )
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':color', $data['color'] ?? '#4CAF50', PDO::PARAM_STR);
        $stmt->bindValue(':icon', $data['icon'] ?? 'mdi-label', PDO::PARAM_STR);
        $stmt->bindParam(':sort_order', $nextOrder, PDO::PARAM_INT);

        $stmt->execute();
        $labelId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'label_id' => (int)$labelId]);
    } catch (Exception $e) {
        error_log("Error in createLabel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create label']);
    }
}

/**
 * Update label
 */
function updateLabel(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        if (empty($data['label_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Label ID is required']);
            return;
        }

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_labels
            WHERE id = :label_id AND user_id = :user_id
        ");
        $stmt->bindParam(':label_id', $data['label_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Label not found or access denied']);
            return;
        }

        // Build dynamic update query
        $updateFields = [];
        $params = [':label_id' => $data['label_id']];

        $allowedFields = ['name', 'color', 'icon', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $sql = "UPDATE kdd_mail_labels SET " . implode(', ', $updateFields) . " WHERE id = :label_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in updateLabel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update label']);
    }
}

/**
 * Delete label
 */
function deleteLabel(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null || empty($data['label_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Label ID is required']);
            return;
        }

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_labels
            WHERE id = :label_id AND user_id = :user_id
        ");
        $stmt->bindParam(':label_id', $data['label_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Label not found or access denied']);
            return;
        }

        $pdo->beginTransaction();

        // Remove this label from all mails (update label_ids JSON array)
        $stmt = $pdo->prepare("
            SELECT id, label_ids FROM kdd_mail_recipients
            WHERE recipient_user_id = :user_id AND label_ids IS NOT NULL AND label_ids != '[]'
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recipients as $recipient) {
            $labelIds = json_decode($recipient['label_ids'], true) ?: [];

            // Remove the label_id from array
            $labelIds = array_filter($labelIds, function($id) use ($data) {
                return $id != $data['label_id'];
            });

            // Re-index array
            $labelIds = array_values($labelIds);

            // Update the record
            $updateStmt = $pdo->prepare("
                UPDATE kdd_mail_recipients
                SET label_ids = :label_ids
                WHERE id = :recipient_id
            ");
            $updateStmt->bindValue(':label_ids', json_encode($labelIds), PDO::PARAM_STR);
            $updateStmt->bindParam(':recipient_id', $recipient['id'], PDO::PARAM_INT);
            $updateStmt->execute();
        }

        // Delete the label
        $stmt = $pdo->prepare("
            DELETE FROM kdd_mail_labels
            WHERE id = :label_id AND user_id = :user_id
        ");
        $stmt->bindParam(':label_id', $data['label_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in deleteLabel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete label']);
    }
}

/**
 * Apply label to mail
 */
function applyLabel(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null || empty($data['mail_id']) || empty($data['label_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Mail ID and Label ID are required']);
            return;
        }

        // Verify label ownership
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_mail_labels
            WHERE id = :label_id AND user_id = :user_id
        ");
        $stmt->bindParam(':label_id', $data['label_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Label not found or access denied']);
            return;
        }

        // Get current label_ids for this mail recipient
        $stmt = $pdo->prepare("
            SELECT id, label_ids FROM kdd_mail_recipients
            WHERE mail_id = :mail_id AND recipient_user_id = :user_id
        ");
        $stmt->bindParam(':mail_id', $data['mail_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recipient) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied']);
            return;
        }

        $labelIds = json_decode($recipient['label_ids'] ?? '[]', true) ?: [];

        // Add label if not already present
        if (!in_array($data['label_id'], $labelIds)) {
            $labelIds[] = (int)$data['label_id'];

            $stmt = $pdo->prepare("
                UPDATE kdd_mail_recipients
                SET label_ids = :label_ids
                WHERE id = :recipient_id
            ");
            $stmt->bindValue(':label_ids', json_encode($labelIds), PDO::PARAM_STR);
            $stmt->bindParam(':recipient_id', $recipient['id'], PDO::PARAM_INT);
            $stmt->execute();
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in applyLabel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to apply label']);
    }
}

/**
 * Remove label from mail
 */
function removeLabel(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();

        if ($data === null || empty($data['mail_id']) || empty($data['label_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Mail ID and Label ID are required']);
            return;
        }

        // Get current label_ids for this mail recipient
        $stmt = $pdo->prepare("
            SELECT id, label_ids FROM kdd_mail_recipients
            WHERE mail_id = :mail_id AND recipient_user_id = :user_id
        ");
        $stmt->bindParam(':mail_id', $data['mail_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recipient) {
            http_response_code(404);
            echo json_encode(['error' => 'Mail not found or access denied']);
            return;
        }

        $labelIds = json_decode($recipient['label_ids'] ?? '[]', true) ?: [];

        // Remove label
        $labelIds = array_filter($labelIds, function($id) use ($data) {
            return $id != $data['label_id'];
        });

        // Re-index array
        $labelIds = array_values($labelIds);

        $stmt = $pdo->prepare("
            UPDATE kdd_mail_recipients
            SET label_ids = :label_ids
            WHERE id = :recipient_id
        ");
        $stmt->bindValue(':label_ids', json_encode($labelIds), PDO::PARAM_STR);
        $stmt->bindParam(':recipient_id', $recipient['id'], PDO::PARAM_INT);
        $stmt->execute();

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in removeLabel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove label']);
    }
}
?>
