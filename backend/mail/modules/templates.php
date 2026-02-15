<?php
/**
 * Mail Templates Module
 * Handles personal, mailbox-specific, and system mail templates
 */

declare(strict_types=1);

/**
 * Get all templates available to user
 * Returns personal templates, mailbox templates where user has access, and system templates
 */
function getTemplates(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // TODO: Check mailbox access when kdd_mail_mailbox_access table exists
        // For now, skip mailbox access check since table doesn't exist yet
        $accessibleMailboxIds = [];

        // Build query for templates
        $query = "
            SELECT
                t.*,
                CASE
                    WHEN t.owner_user_id = :user_id THEN 'personal'
                    WHEN t.owner_mailbox_id IS NOT NULL THEN 'mailbox'
                    ELSE 'system'
                END as access_type
            FROM kdd_mail_templates t
            WHERE t.authority_id = :authority_id
            AND t.is_active = 1
            AND (
                t.owner_user_id = :user_id
                OR t.template_type = 'system'
                " . (count($accessibleMailboxIds) > 0 ? "OR t.owner_mailbox_id IN (" . implode(',', array_map('intval', $accessibleMailboxIds)) . ")" : "") . "
            )
            ORDER BY t.sort_order ASC, t.name ASC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $userId, 'authority_id' => $authorityId]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode available_variables JSON
        foreach ($templates as &$template) {
            $template['available_variables'] = json_decode($template['available_variables'] ?? '[]', true);
        }

        // Group by type
        $grouped = [
            'personal' => [],
            'mailbox' => [],
            'system' => []
        ];

        foreach ($templates as $template) {
            $type = $template['access_type'];
            unset($template['access_type']);
            $grouped[$type][] = $template;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'templates' => $grouped
        ]);

    } catch (Exception $e) {
        error_log("Error in getTemplates: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve templates']);
    }
}

/**
 * Create new mail template
 */
function createTemplate(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $name = isset($data['name']) ? filter_var($data['name'], FILTER_SANITIZE_STRING) : '';
        $subjectTemplate = $data['subject_template'] ?? '';
        $bodyTemplate = $data['body_template'] ?? '';

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: name cannot be empty']);
            return;
        }

        if (empty($subjectTemplate)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: subject_template cannot be empty']);
            return;
        }

        if (empty($bodyTemplate)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: body_template cannot be empty']);
            return;
        }

        // Use validated name for database insertion
        $data['name'] = $name;

        $templateType = $data['template_type'] ?? 'personal';
        $ownerMailboxId = $data['owner_mailbox_id'] ?? null;

        // If mailbox template, verify user has access to that mailbox
        if ($templateType === 'mailbox') {
            if (!$ownerMailboxId) {
                http_response_code(400);
                echo json_encode(['error' => 'owner_mailbox_id required for mailbox templates']);
                return;
            }

            // Check user has access to this mailbox
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM kdd_mail_accounts ma
                JOIN kdd_mail_mailbox_access mla ON ma.id = mla.mail_account_id
                WHERE ma.user_id = :user_id
                AND ma.authority_id = :authority_id
                AND mla.mailbox_id = :mailbox_id
            ");
            $stmt->execute([
                'user_id' => $userId,
                'authority_id' => $authorityId,
                'mailbox_id' => $ownerMailboxId
            ]);

            if ($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['error' => 'No access to specified mailbox']);
                return;
            }
        }

        // Sanitize and prepare available_variables
        $availableVariables = $data['available_variables'] ?? [];
        if (!is_array($availableVariables)) {
            $availableVariables = [];
        }

        // Sanitize HTML in body_template
        $bodyTemplate = sanitizeHtml($data['body_template']);

        // Insert template
        $stmt = $pdo->prepare("
            INSERT INTO kdd_mail_templates (
                name, description, owner_user_id, owner_mailbox_id,
                authority_id, template_type, subject_template, body_template,
                available_variables, is_active, sort_order
            ) VALUES (
                :name, :description, :owner_user_id, :owner_mailbox_id,
                :authority_id, :template_type, :subject_template, :body_template,
                :available_variables, 1, :sort_order
            )
        ");

        $sortOrder = $data['sort_order'] ?? 999;

        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'owner_user_id' => $templateType === 'personal' ? $userId : null,
            'owner_mailbox_id' => $ownerMailboxId,
            'authority_id' => $authorityId,
            'template_type' => $templateType,
            'subject_template' => $data['subject_template'],
            'body_template' => $bodyTemplate,
            'available_variables' => json_encode($availableVariables),
            'sort_order' => $sortOrder
        ]);

        $templateId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'template_id' => (int)$templateId,
            'message' => 'Template created successfully'
        ]);

    } catch (Exception $e) {
        error_log("Error in createTemplate: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create template']);
    }
}

/**
 * Update existing template
 */
function updateTemplate(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $templateId = $data['template_id'] ?? null;

        // Validate template_id is present and is an integer
        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: template_id is required']);
            return;
        }

        $templateId = filter_var($templateId, FILTER_VALIDATE_INT);
        if ($templateId === false || $templateId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid template_id: must be a positive integer']);
            return;
        }

        // Validate that at least one field is provided for update
        $hasUpdateFields = isset($data['name']) || isset($data['description']) ||
                          isset($data['subject_template']) || isset($data['body_template']) ||
                          isset($data['available_variables']) || isset($data['is_active']) ||
                          isset($data['sort_order']);

        if (!$hasUpdateFields) {
            http_response_code(400);
            echo json_encode(['error' => 'At least one field to update is required (name, description, subject_template, body_template, available_variables, is_active, or sort_order)']);
            return;
        }

        // Validate individual fields if present
        if (isset($data['name'])) {
            $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid name: cannot be empty']);
                return;
            }
            $data['name'] = $name;
        }

        if (isset($data['sort_order'])) {
            $sortOrder = filter_var($data['sort_order'], FILTER_VALIDATE_INT);
            if ($sortOrder === false || $sortOrder < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid sort_order: must be a non-negative integer']);
                return;
            }
            $data['sort_order'] = $sortOrder;
        }

        // Check ownership/permission
        $stmt = $pdo->prepare("
            SELECT t.*,
                   (SELECT COUNT(*) FROM kdd_mail_accounts ma
                    JOIN kdd_mail_mailbox_access mla ON ma.id = mla.mail_account_id
                    WHERE ma.user_id = :user_id
                    AND ma.authority_id = :authority_id
                    AND mla.mailbox_id = t.owner_mailbox_id) as has_mailbox_access
            FROM kdd_mail_templates t
            WHERE t.id = :template_id AND t.authority_id = :authority_id
        ");
        $stmt->execute([
            'template_id' => $templateId,
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template not found']);
            return;
        }

        // Verify permission
        $canEdit = false;
        if ($template['owner_user_id'] == $userId) {
            $canEdit = true;
        } elseif ($template['owner_mailbox_id'] && $template['has_mailbox_access'] > 0) {
            $canEdit = true;
        }

        if (!$canEdit) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }

        // Build update query
        $updates = [];
        $params = ['template_id' => $templateId];

        if (isset($data['name'])) {
            $updates[] = "name = :name";
            $params['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updates[] = "description = :description";
            $params['description'] = $data['description'];
        }
        if (isset($data['subject_template'])) {
            $updates[] = "subject_template = :subject_template";
            $params['subject_template'] = $data['subject_template'];
        }
        if (isset($data['body_template'])) {
            $updates[] = "body_template = :body_template";
            $params['body_template'] = sanitizeHtml($data['body_template']);
        }
        if (isset($data['available_variables'])) {
            $updates[] = "available_variables = :available_variables";
            $params['available_variables'] = json_encode($data['available_variables']);
        }
        if (isset($data['is_active'])) {
            $updates[] = "is_active = :is_active";
            $params['is_active'] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['sort_order'])) {
            $updates[] = "sort_order = :sort_order";
            $params['sort_order'] = $data['sort_order'];
        }

        $sql = "UPDATE kdd_mail_templates SET " . implode(', ', $updates) . " WHERE id = :template_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);

    } catch (Exception $e) {
        error_log("Error in updateTemplate: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update template']);
    }
}

/**
 * Delete template
 */
function deleteTemplate(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if ($data === null) return;

        // VALIDATION
        $templateId = $data['template_id'] ?? null;

        // Validate template_id is present and is an integer
        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: template_id is required']);
            return;
        }

        $templateId = filter_var($templateId, FILTER_VALIDATE_INT);
        if ($templateId === false || $templateId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid template_id: must be a positive integer']);
            return;
        }

        // Check ownership/permission
        $stmt = $pdo->prepare("
            SELECT t.*,
                   (SELECT COUNT(*) FROM kdd_mail_accounts ma
                    JOIN kdd_mail_mailbox_access mla ON ma.id = mla.mail_account_id
                    WHERE ma.user_id = :user_id
                    AND ma.authority_id = :authority_id
                    AND mla.mailbox_id = t.owner_mailbox_id) as has_mailbox_access
            FROM kdd_mail_templates t
            WHERE t.id = :template_id AND t.authority_id = :authority_id
        ");
        $stmt->execute([
            'template_id' => $templateId,
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template not found']);
            return;
        }

        // Verify permission
        $canDelete = false;
        if ($template['owner_user_id'] == $userId) {
            $canDelete = true;
        } elseif ($template['owner_mailbox_id'] && $template['has_mailbox_access'] > 0) {
            $canDelete = true;
        }

        if (!$canDelete) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }

        // Delete template
        $stmt = $pdo->prepare("DELETE FROM kdd_mail_templates WHERE id = :template_id");
        $stmt->execute(['template_id' => $templateId]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);

    } catch (Exception $e) {
        error_log("Error in deleteTemplate: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete template']);
    }
}

/**
 * Get single template by ID with details
 */
function getTemplateById(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $templateId = $_GET['template_id'] ?? null;
        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['error' => 'template_id is required']);
            return;
        }

        // Get user's accessible mailboxes
        $stmt = $pdo->prepare("
            SELECT DISTINCT mla.mailbox_id
            FROM kdd_mail_accounts ma
            LEFT JOIN kdd_mail_mailbox_access mla ON ma.id = mla.mail_account_id
            WHERE ma.user_id = :user_id AND ma.authority_id = :authority_id
        ");
        $stmt->execute(['user_id' => $userId, 'authority_id' => $authorityId]);
        $accessibleMailboxIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get template
        $query = "
            SELECT t.*
            FROM kdd_mail_templates t
            WHERE t.id = :template_id
            AND t.authority_id = :authority_id
            AND (
                t.owner_user_id = :user_id
                OR t.template_type = 'system'
                " . (count($accessibleMailboxIds) > 0 ? "OR t.owner_mailbox_id IN (" . implode(',', array_map('intval', $accessibleMailboxIds)) . ")" : "") . "
            )
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'template_id' => $templateId,
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template not found or access denied']);
            return;
        }

        // Decode available_variables
        $template['available_variables'] = json_decode($template['available_variables'] ?? '[]', true);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'template' => $template
        ]);

    } catch (Exception $e) {
        error_log("Error in getTemplateById: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve template']);
    }
}

/**
 * Render template with variable substitution
 */
function renderTemplate(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data) return;

        $templateId = $data['template_id'] ?? null;
        $variables = $data['variables'] ?? [];

        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['error' => 'template_id is required']);
            return;
        }

        // Get template (reuse getTemplateById logic)
        $stmt = $pdo->prepare("
            SELECT DISTINCT mla.mailbox_id
            FROM kdd_mail_accounts ma
            LEFT JOIN kdd_mail_mailbox_access mla ON ma.id = mla.mail_account_id
            WHERE ma.user_id = :user_id AND ma.authority_id = :authority_id
        ");
        $stmt->execute(['user_id' => $userId, 'authority_id' => $authorityId]);
        $accessibleMailboxIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $query = "
            SELECT t.*
            FROM kdd_mail_templates t
            WHERE t.id = :template_id
            AND t.authority_id = :authority_id
            AND (
                t.owner_user_id = :user_id
                OR t.template_type = 'system'
                " . (count($accessibleMailboxIds) > 0 ? "OR t.owner_mailbox_id IN (" . implode(',', array_map('intval', $accessibleMailboxIds)) . ")" : "") . "
            )
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'template_id' => $templateId,
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template not found or access denied']);
            return;
        }

        // Replace variables in subject and body
        $renderedSubject = $template['subject_template'];
        $renderedBody = $template['body_template'];

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $renderedSubject = str_replace($placeholder, $value, $renderedSubject);
            $renderedBody = str_replace($placeholder, $value, $renderedBody);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'rendered' => [
                'subject' => $renderedSubject,
                'body' => $renderedBody
            ]
        ]);

    } catch (Exception $e) {
        error_log("Error in renderTemplate: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to render template']);
    }
}

/**
 * Sanitize HTML content for security
 */
function sanitizeHtml(string $html): string {
    // Basic HTML sanitization - strip dangerous tags
    $allowed_tags = '<p><br><b><i><u><strong><em><a><ul><ol><li><h1><h2><h3><h4><h5><h6><div><span><table><tr><td><th><tbody><thead><img>';
    return strip_tags($html, $allowed_tags);
}
