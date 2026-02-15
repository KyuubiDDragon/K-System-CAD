<?php
/**
 * Mail Module: Contact Management
 * Handles personal contacts and global directory search
 */
declare(strict_types=1);

/**
 * Get all contacts for user
 */
function getContacts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id, owner_user_id, authority_id, contact_type, email,
                secondary_emails, display_name, first_name, last_name,
                company, department, position, phone, mobile, address,
                website, tags, category, is_favorite, notes, avatar_url,
                created_at, updated_at
            FROM kdd_contacts
            WHERE owner_user_id = :user_id
            ORDER BY is_favorite DESC, display_name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields
        foreach ($contacts as &$contact) {
            $contact['secondary_emails'] = json_decode($contact['secondary_emails'] ?? '[]', true) ?: [];
            $contact['tags'] = json_decode($contact['tags'] ?? '[]', true) ?: [];
        }

        http_response_code(200);
        echo json_encode(['contacts' => $contacts]);
    } catch (Exception $e) {
        error_log("Error in getContacts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve contacts']);
    }
}

/**
 * Search in user's contacts
 */
function searchContacts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $query = $_REQUEST['query'] ?? $_GET['query'] ?? '';

        // If no query provided, return all contacts instead of error
        if (empty($query)) {
            // Return all contacts for this user
            $stmt = $pdo->prepare("
                SELECT
                    id, owner_user_id, authority_id, contact_type, email,
                    secondary_emails, display_name, first_name, last_name,
                    company, department, position, phone, mobile, address,
                    website, tags, category, is_favorite, notes, avatar_url,
                    created_at, updated_at
                FROM kdd_contacts
                WHERE owner_user_id = :user_id
                ORDER BY is_favorite DESC, display_name ASC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse JSON fields
            foreach ($contacts as &$contact) {
                $contact['secondary_emails'] = json_decode($contact['secondary_emails'] ?? '[]', true) ?: [];
                $contact['tags'] = json_decode($contact['tags'] ?? '[]', true) ?: [];
            }

            http_response_code(200);
            echo json_encode(['contacts' => $contacts]);
            return;
        }

        $searchTerm = '%' . $query . '%';

        $stmt = $pdo->prepare("
            SELECT
                id, owner_user_id, authority_id, contact_type, email,
                secondary_emails, display_name, first_name, last_name,
                company, department, position, phone, mobile, address,
                website, tags, category, is_favorite, notes, avatar_url,
                created_at, updated_at
            FROM kdd_contacts
            WHERE owner_user_id = :user_id
            AND (
                display_name LIKE :search
                OR email LIKE :search
                OR first_name LIKE :search
                OR last_name LIKE :search
                OR company LIKE :search
            )
            ORDER BY is_favorite DESC, display_name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();

        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields
        foreach ($contacts as &$contact) {
            $contact['secondary_emails'] = json_decode($contact['secondary_emails'] ?? '[]', true) ?: [];
            $contact['tags'] = json_decode($contact['tags'] ?? '[]', true) ?: [];
        }

        http_response_code(200);
        echo json_encode(['contacts' => $contacts]);
    } catch (Exception $e) {
        error_log("Error in searchContacts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to search contacts']);
    }
}

/**
 * Search global K-Systems directory (all registered users)
 */
function getGlobalDirectory(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $query = $_REQUEST['query'] ?? $_GET['query'] ?? '';

        // If no query, return all searchable mail accounts
        if (empty($query)) {
            $stmt = $pdo->prepare("
                SELECT
                    ma.email,
                    COALESCE(ma.display_name, u.username) as display_name,
                    NULL as avatar_url,
                    a.name as authority_name,
                    a.id as authority_id
                FROM kdd_mail_accounts ma
                INNER JOIN kdd_users u ON ma.current_user_id = u.id
                LEFT JOIN kdd_authorities a ON u.authority_id = a.id
                WHERE ma.is_searchable = 1
                ORDER BY ma.display_name ASC
                LIMIT 100
            ");
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format results for autocomplete
            $directory = array_map(function($row) {
                return [
                    'email' => $row['email'],
                    'display_name' => $row['display_name'],
                    'avatar_url' => $row['avatar_url'],
                    'authority_name' => $row['authority_name'],
                    'authority_id' => $row['authority_id']
                ];
            }, $results);

            http_response_code(200);
            echo json_encode($directory);
            return;
        }

        $searchTerm = '%' . $query . '%';

        $stmt = $pdo->prepare("
            SELECT
                ma.email,
                COALESCE(ma.display_name, u.username) as display_name,
                NULL as avatar_url,
                a.name as authority_name,
                a.id as authority_id
            FROM kdd_mail_accounts ma
            INNER JOIN kdd_users u ON ma.current_user_id = u.id
            LEFT JOIN kdd_authorities a ON u.authority_id = a.id
            WHERE ma.is_searchable = 1
            AND (
                ma.email LIKE :search
                OR ma.display_name LIKE :search
                OR u.username LIKE :search
            )
            ORDER BY ma.display_name ASC
            LIMIT 50
        ");
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format results for autocomplete
        $directory = array_map(function($row) {
            return [
                'email' => $row['email'],
                'display_name' => trim(($row['display_name'] ?? '') . ' ' . ($row['surname'] ?? '')),
                'avatar_url' => $row['avatar_url'],
                'authority_name' => $row['authority_name'],
                'authority_id' => $row['authority_id']
            ];
        }, $results);

        http_response_code(200);
        echo json_encode(['directory' => $directory]);
    } catch (Exception $e) {
        error_log("Error in getGlobalDirectory: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to search global directory']);
    }
}

/**
 * Create new contact
 */
function createContact(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        // Validate required fields
        if (empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is required']);
            return;
        }

        if (empty($data['display_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Display name is required']);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Prepare JSON fields
        $secondaryEmails = !empty($data['secondary_emails']) && is_array($data['secondary_emails'])
            ? json_encode($data['secondary_emails'])
            : json_encode([]);
        $tags = !empty($data['tags']) && is_array($data['tags'])
            ? json_encode($data['tags'])
            : json_encode([]);

        $stmt = $pdo->prepare("
            INSERT INTO kdd_contacts (
                owner_user_id, authority_id, contact_type, email, secondary_emails,
                display_name, first_name, last_name, company, department, position,
                phone, mobile, address, website, tags, category, is_favorite, notes, avatar_url
            ) VALUES (
                :owner_user_id, :authority_id, :contact_type, :email, :secondary_emails,
                :display_name, :first_name, :last_name, :company, :department, :position,
                :phone, :mobile, :address, :website, :tags, :category, :is_favorite, :notes, :avatar_url
            )
        ");

        $stmt->bindParam(':owner_user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(':contact_type', $data['contact_type'] ?? 'personal', PDO::PARAM_STR);
        $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindParam(':secondary_emails', $secondaryEmails, PDO::PARAM_STR);
        $stmt->bindParam(':display_name', $data['display_name'], PDO::PARAM_STR);
        $stmt->bindValue(':first_name', $data['first_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $data['last_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':company', $data['company'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':department', $data['department'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':position', $data['position'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':mobile', $data['mobile'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':website', $data['website'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':tags', $tags, PDO::PARAM_STR);
        $stmt->bindValue(':category', $data['category'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_favorite', $data['is_favorite'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':avatar_url', $data['avatar_url'] ?? null, PDO::PARAM_STR);

        $stmt->execute();
        $contactId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['success' => true, 'contact_id' => (int)$contactId]);
    } catch (Exception $e) {
        error_log("Error in createContact: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create contact']);
    }
}

/**
 * Update existing contact
 */
function updateContact(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        if (empty($data['contact_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Contact ID is required']);
            return;
        }

        // Check ownership
        $stmt = $pdo->prepare("SELECT id FROM kdd_contacts WHERE id = :contact_id AND owner_user_id = :user_id");
        $stmt->bindParam(':contact_id', $data['contact_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Contact not found or access denied']);
            return;
        }

        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Build dynamic update query
        $updateFields = [];
        $params = [':contact_id' => $data['contact_id']];

        $allowedFields = [
            'contact_type', 'email', 'display_name', 'first_name', 'last_name',
            'company', 'department', 'position', 'phone', 'mobile', 'address',
            'website', 'category', 'is_favorite', 'notes', 'avatar_url'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Handle JSON fields
        if (isset($data['secondary_emails']) && is_array($data['secondary_emails'])) {
            $updateFields[] = "secondary_emails = :secondary_emails";
            $params[':secondary_emails'] = json_encode($data['secondary_emails']);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $updateFields[] = "tags = :tags";
            $params[':tags'] = json_encode($data['tags']);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $sql = "UPDATE kdd_contacts SET " . implode(', ', $updateFields) . " WHERE id = :contact_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in updateContact: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update contact']);
    }
}

/**
 * Delete contact
 */
function deleteContact(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['contact_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Contact ID is required']);
            return;
        }

        // Check ownership before deleting
        $stmt = $pdo->prepare("DELETE FROM kdd_contacts WHERE id = :contact_id AND owner_user_id = :user_id");
        $stmt->bindParam(':contact_id', $data['contact_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Contact not found or access denied']);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in deleteContact: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete contact']);
    }
}

/**
 * Mark/unmark contact as favorite
 */
function toggleFavorite(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['contact_id']) || !isset($data['is_favorite'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Contact ID and is_favorite flag are required']);
            return;
        }

        $isFavorite = (int)$data['is_favorite'];

        $stmt = $pdo->prepare("
            UPDATE kdd_contacts
            SET is_favorite = :is_favorite
            WHERE id = :contact_id AND owner_user_id = :user_id
        ");
        $stmt->bindParam(':is_favorite', $isFavorite, PDO::PARAM_INT);
        $stmt->bindParam(':contact_id', $data['contact_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Contact not found or access denied']);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error in toggleFavorite: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to toggle favorite']);
    }
}

/**
 * Bulk import contacts (CSV/JSON)
 */
function importContacts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['contacts']) || !is_array($data['contacts'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Contacts array is required']);
            return;
        }

        $imported = 0;
        $failed = 0;
        $errors = [];

        $pdo->beginTransaction();

        foreach ($data['contacts'] as $index => $contact) {
            try {
                // Validate required fields
                if (empty($contact['email']) || empty($contact['display_name'])) {
                    $errors[] = "Row $index: Email and display_name are required";
                    $failed++;
                    continue;
                }

                if (!filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row $index: Invalid email format";
                    $failed++;
                    continue;
                }

                $secondaryEmails = !empty($contact['secondary_emails']) && is_array($contact['secondary_emails'])
                    ? json_encode($contact['secondary_emails'])
                    : json_encode([]);
                $tags = !empty($contact['tags']) && is_array($contact['tags'])
                    ? json_encode($contact['tags'])
                    : json_encode([]);

                $stmt = $pdo->prepare("
                    INSERT INTO kdd_contacts (
                        owner_user_id, authority_id, contact_type, email, secondary_emails,
                        display_name, first_name, last_name, company, department, position,
                        phone, mobile, address, website, tags, category, is_favorite, notes, avatar_url
                    ) VALUES (
                        :owner_user_id, :authority_id, :contact_type, :email, :secondary_emails,
                        :display_name, :first_name, :last_name, :company, :department, :position,
                        :phone, :mobile, :address, :website, :tags, :category, :is_favorite, :notes, :avatar_url
                    )
                ");

                $stmt->bindParam(':owner_user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
                $stmt->bindValue(':contact_type', $contact['contact_type'] ?? 'personal', PDO::PARAM_STR);
                $stmt->bindParam(':email', $contact['email'], PDO::PARAM_STR);
                $stmt->bindParam(':secondary_emails', $secondaryEmails, PDO::PARAM_STR);
                $stmt->bindParam(':display_name', $contact['display_name'], PDO::PARAM_STR);
                $stmt->bindValue(':first_name', $contact['first_name'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':last_name', $contact['last_name'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':company', $contact['company'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':department', $contact['department'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':position', $contact['position'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':phone', $contact['phone'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':mobile', $contact['mobile'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':address', $contact['address'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':website', $contact['website'] ?? null, PDO::PARAM_STR);
                $stmt->bindParam(':tags', $tags, PDO::PARAM_STR);
                $stmt->bindValue(':category', $contact['category'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':is_favorite', $contact['is_favorite'] ?? 0, PDO::PARAM_INT);
                $stmt->bindValue(':notes', $contact['notes'] ?? null, PDO::PARAM_STR);
                $stmt->bindValue(':avatar_url', $contact['avatar_url'] ?? null, PDO::PARAM_STR);

                $stmt->execute();
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Row $index: " . $e->getMessage();
                $failed++;
            }
        }

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in importContacts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to import contacts']);
    }
}

/**
 * Export contacts as JSON/CSV
 */
function exportContacts(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $format = $_GET['format'] ?? 'json';

        $stmt = $pdo->prepare("
            SELECT
                id, contact_type, email, secondary_emails, display_name,
                first_name, last_name, company, department, position,
                phone, mobile, address, website, tags, category,
                is_favorite, notes, avatar_url, created_at, updated_at
            FROM kdd_contacts
            WHERE owner_user_id = :user_id
            ORDER BY display_name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields
        foreach ($contacts as &$contact) {
            $contact['secondary_emails'] = json_decode($contact['secondary_emails'] ?? '[]', true) ?: [];
            $contact['tags'] = json_decode($contact['tags'] ?? '[]', true) ?: [];
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="contacts_export.csv"');

            $output = fopen('php://output', 'w');

            // Write headers
            if (!empty($contacts)) {
                fputcsv($output, array_keys($contacts[0]));
            }

            // Write data
            foreach ($contacts as $contact) {
                $contact['secondary_emails'] = json_encode($contact['secondary_emails']);
                $contact['tags'] = json_encode($contact['tags']);
                fputcsv($output, $contact);
            }

            fclose($output);
        } else {
            http_response_code(200);
            echo json_encode(['contacts' => $contacts]);
        }
    } catch (Exception $e) {
        error_log("Error in exportContacts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to export contacts']);
    }
}
?>
