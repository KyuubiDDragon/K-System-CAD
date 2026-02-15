<?php
/**
 * Sections and News API Functions
 * To be appended to index.php
 */

// ========================================
// SECTIONS CRUD (for One-Pager Templates)
// ========================================

/**
 * Get sections for a website
 */
function getSections(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] :
                     (isset($inputData['website_id']) ? (int)$inputData['website_id'] : 0);
        global $userId, $is_public_action;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // For public access, we don't check access permissions
        if (!$is_public_action && !hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // For public actions, get the website's authority_id
        if ($is_public_action) {
            $authStmt = $pdo->prepare("SELECT authority_id FROM kdd_website_config WHERE id = ?");
            $authStmt->execute([$websiteId]);
            $authorityId = $authStmt->fetchColumn();

            if (!$authorityId) {
                http_response_code(404);
                echo json_encode(['error' => 'Website not found']);
                return;
            }
        }

        // Get all active sections
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_sections
            WHERE website_id = ? AND authority_id = ?
            AND is_active = 1
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse settings JSON
        foreach ($sections as &$section) {
            if (!empty($section['settings'])) {
                $section['settings'] = json_decode($section['settings'], true) ?? [];
            } else {
                $section['settings'] = [];
            }
        }

        echo json_encode([
            'success' => true,
            'sections' => $sections
        ]);
    } catch (PDOException $e) {
        error_log("Error in getSections: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create a new section
 */
function createSection(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['website_id']) ? (int)$inputData['website_id'] : 0;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Validate section type
        $validTypes = ['hero', 'about', 'services', 'portfolio', 'team', 'testimonials', 'contact', 'features', 'pricing', 'cta', 'custom'];
        $sectionType = $inputData['section_type'] ?? 'custom';
        if (!in_array($sectionType, $validTypes)) {
            $sectionType = 'custom';
        }

        // Get max sort_order for this website
        $maxStmt = $pdo->prepare("SELECT MAX(sort_order) FROM kdd_website_sections WHERE website_id = ?");
        $maxStmt->execute([$websiteId]);
        $maxOrder = $maxStmt->fetchColumn() ?? 0;

        // Prepare settings JSON
        $settings = isset($inputData['settings']) ? json_encode($inputData['settings']) : null;

        // Create section
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_sections (
                website_id, section_type, title, subtitle, content,
                settings, sort_order, is_active, authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $websiteId,
            $sectionType,
            $inputData['title'] ?? null,
            $inputData['subtitle'] ?? null,
            $inputData['content'] ?? null,
            $settings,
            $maxOrder + 1,
            isset($inputData['is_active']) ? (int)$inputData['is_active'] : 1,
            $authorityId
        ]);

        $sectionId = $pdo->lastInsertId();

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_sections', $sectionId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'Section created']
        ]);

        // Get the created section
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_sections WHERE id = ?");
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($section['settings'])) {
            $section['settings'] = json_decode($section['settings'], true) ?? [];
        }

        echo json_encode([
            'success' => true,
            'section' => $section
        ]);
    } catch (PDOException $e) {
        error_log("Error in createSection: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update an existing section
 */
function updateSection(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $sectionId = isset($inputData['id']) ? (int)$inputData['id'] : 0;

        if (!$sectionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Section ID is required']);
            return;
        }

        // Check if section exists and get website_id
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_sections
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sectionId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Prepare settings JSON
        $settings = isset($inputData['settings']) ? json_encode($inputData['settings']) : null;

        // Update section
        $stmt = $pdo->prepare("
            UPDATE kdd_website_sections SET
            title = ?,
            subtitle = ?,
            content = ?,
            settings = ?,
            is_active = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");

        $stmt->execute([
            $inputData['title'] ?? null,
            $inputData['subtitle'] ?? null,
            $inputData['content'] ?? null,
            $settings,
            isset($inputData['is_active']) ? (int)$inputData['is_active'] : 1,
            $sectionId,
            $authorityId
        ]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_sections', $sectionId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'Section updated']
        ]);

        // Get the updated section
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_sections WHERE id = ?");
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($section['settings'])) {
            $section['settings'] = json_decode($section['settings'], true) ?? [];
        }

        echo json_encode([
            'success' => true,
            'section' => $section
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateSection: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a section
 */
function deleteSection(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $sectionId = isset($inputData['section_id']) ? (int)$inputData['section_id'] : 0;

        if (!$sectionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Section ID is required']);
            return;
        }

        // Check if section exists and get website_id
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_sections
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sectionId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Delete the section
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_sections
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sectionId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_sections', $sectionId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'Section deleted']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in deleteSection: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update section order
 */
function updateSectionOrder(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $sections = isset($inputData['sections']) ? $inputData['sections'] : [];

        if (empty($sections) || !is_array($sections)) {
            http_response_code(400);
            echo json_encode(['error' => 'Sections array is required']);
            return;
        }

        // Update each section's sort_order
        $stmt = $pdo->prepare("
            UPDATE kdd_website_sections
            SET sort_order = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");

        foreach ($sections as $section) {
            $stmt->execute([
                $section['sort_order'] ?? 0,
                $section['id'] ?? 0,
                $authorityId
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Section order updated successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateSectionOrder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create default sections for a template
 * Called when switching to a template that requires sections (e.g., onepager)
 */
function createDefaultSections(PDO $pdo, int $websiteId, int $userId, int $authorityId, string $template = 'onepager'): bool
{
    error_log("DEBUG: createDefaultSections called with websiteId=$websiteId, userId=$userId, authorityId=$authorityId, template=$template");
    try {
        // Check if sections already exist
        error_log("DEBUG: Checking if sections already exist for website $websiteId");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kdd_website_sections WHERE website_id = ?");
        $stmt->execute([$websiteId]);
        $count = $stmt->fetchColumn();
        error_log("DEBUG: Found $count existing sections");

        if ($count > 0) {
            // Sections already exist, don't create defaults
            error_log("DEBUG: Sections already exist, returning false");
            return false;
        }

        // Get website details for personalization
        error_log("DEBUG: Fetching website details for personalization");
        $stmt = $pdo->prepare("SELECT site_name, site_slogan, site_description FROM kdd_website_config WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("DEBUG: Website details fetched");

        $siteName = $website['site_name'] ?? 'Unsere Website';
        $siteSlogan = $website['site_slogan'] ?? 'Willkommen auf unserer Website';
        $siteDescription = $website['site_description'] ?? '';
        error_log("DEBUG: siteName=$siteName, siteSlogan=$siteSlogan");

        // Define default sections based on template
        error_log("DEBUG: Defining default sections for template: $template");
        $defaultSections = [];

        if ($template === 'onepager') {
            error_log("DEBUG: Creating OnePager default sections");
            $defaultSections = [
                [
                    'section_type' => 'hero',
                    'title' => $siteName,
                    'subtitle' => $siteSlogan,
                    'content' => $siteDescription ?: 'Entdecken Sie unsere Leistungen und erfahren Sie mehr über uns.',
                    'settings' => json_encode([
                        'backgroundImage' => null,
                        'buttonText' => 'Mehr erfahren',
                        'buttonLink' => '#about'
                    ]),
                    'sort_order' => 0,
                    'is_active' => 1
                ],
                [
                    'section_type' => 'about',
                    'title' => 'Über uns',
                    'subtitle' => 'Wer wir sind',
                    'content' => '<p>Wir sind ein engagiertes Team, das sich darauf spezialisiert hat, erstklassige Lösungen zu liefern. Mit jahrelanger Erfahrung und Leidenschaft für Innovation helfen wir unseren Kunden, ihre Ziele zu erreichen.</p><p>Unsere Mission ist es, durch Qualität und Zuverlässigkeit zu überzeugen.</p>',
                    'settings' => json_encode([]),
                    'sort_order' => 1,
                    'is_active' => 1
                ],
                [
                    'section_type' => 'services',
                    'title' => 'Unsere Leistungen',
                    'subtitle' => 'Was wir anbieten',
                    'content' => null,
                    'settings' => json_encode([
                        'items' => [
                            [
                                'icon' => 'mdi mdi-cog',
                                'title' => 'Beratung',
                                'description' => 'Professionelle Beratung für Ihre individuellen Anforderungen'
                            ],
                            [
                                'icon' => 'mdi mdi-rocket',
                                'title' => 'Umsetzung',
                                'description' => 'Schnelle und effiziente Umsetzung Ihrer Projekte'
                            ],
                            [
                                'icon' => 'mdi mdi-shield-check',
                                'title' => 'Support',
                                'description' => 'Zuverlässiger Support und Wartung für Ihre Systeme'
                            ]
                        ]
                    ]),
                    'sort_order' => 2,
                    'is_active' => 1
                ],
                [
                    'section_type' => 'contact',
                    'title' => 'Kontakt',
                    'subtitle' => 'Nehmen Sie Kontakt mit uns auf',
                    'content' => '<p>Haben Sie Fragen oder möchten Sie mehr über unsere Leistungen erfahren? Wir freuen uns auf Ihre Nachricht!</p>',
                    'settings' => json_encode([]),
                    'sort_order' => 3,
                    'is_active' => 1
                ]
            ];
        }

        // Insert default sections
        error_log("DEBUG: Preparing to insert " . count($defaultSections) . " sections");
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_sections (
                website_id, section_type, title, subtitle, content,
                settings, sort_order, is_active, authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        error_log("DEBUG: INSERT statement prepared");

        foreach ($defaultSections as $index => $section) {
            error_log("DEBUG: Inserting section $index: " . $section['section_type']);
            $stmt->execute([
                $websiteId,
                $section['section_type'],
                $section['title'],
                $section['subtitle'] ?? null,
                $section['content'],
                $section['settings'],
                $section['sort_order'],
                $section['is_active'],
                $authorityId
            ]);
            error_log("DEBUG: Section $index inserted successfully");
        }
        error_log("DEBUG: All sections inserted");

        // Log the change
        error_log("DEBUG: Attempting to log database change");
        try {
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_sections', 0, $userId, [
                ['column_name' => 'action', 'old_value' => null, 'new_value' => "Default sections created for template: $template"]
            ]);
            error_log("DEBUG: Database change logged successfully");
        } catch (Exception $e) {
            // Log error but don't fail the section creation
            error_log("DEBUG: Error logging createDefaultSections change: " . $e->getMessage());
        }

        error_log("DEBUG: createDefaultSections returning true");
        return true;
    } catch (PDOException $e) {
        error_log("DEBUG: PDOException in createDefaultSections: " . $e->getMessage());
        error_log("DEBUG: PDO Error trace: " . $e->getTraceAsString());
        return false;
    } catch (Exception $e) {
        error_log("DEBUG: Exception in createDefaultSections (general): " . $e->getMessage());
        error_log("DEBUG: Error trace: " . $e->getTraceAsString());
        return false;
    } catch (Throwable $e) {
        error_log("DEBUG: Throwable in createDefaultSections: " . $e->getMessage());
        error_log("DEBUG: Throwable trace: " . $e->getTraceAsString());
        return false;
    }
}

// ========================================
// NEWS / DOCUMENTS CRUD
// ========================================

/**
 * Get news for a website
 */
function getNews(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] :
                     (isset($inputData['website_id']) ? (int)$inputData['website_id'] : 0);
        global $userId, $is_public_action;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // For public access, we don't check access permissions
        if (!$is_public_action && !hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // For public actions, get the website's authority_id and only published news
        if ($is_public_action) {
            $authStmt = $pdo->prepare("SELECT authority_id FROM kdd_website_config WHERE id = ?");
            $authStmt->execute([$websiteId]);
            $authorityId = $authStmt->fetchColumn();

            if (!$authorityId) {
                http_response_code(404);
                echo json_encode(['error' => 'Website not found']);
                return;
            }

            // Only published news for public
            $stmt = $pdo->prepare("
                SELECT n.*, m.file_path, m.file_name, m.file_type
                FROM kdd_website_news n
                LEFT JOIN kdd_website_media m ON n.document_id = m.id
                WHERE n.website_id = ? AND n.authority_id = ?
                AND n.is_published = 1
                AND (n.expires_at IS NULL OR n.expires_at > NOW())
                ORDER BY n.priority DESC, n.published_at DESC
            ");
        } else {
            // All news for authenticated users
            $stmt = $pdo->prepare("
                SELECT n.*, m.file_path, m.file_name, m.file_type
                FROM kdd_website_news n
                LEFT JOIN kdd_website_media m ON n.document_id = m.id
                WHERE n.website_id = ? AND n.authority_id = ?
                ORDER BY n.created_at DESC
            ");
        }

        $stmt->execute([$websiteId, $authorityId]);
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'news' => $news
        ]);
    } catch (PDOException $e) {
        error_log("Error in getNews: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create news
 */
function createNews(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['website_id']) ? (int)$inputData['website_id'] : 0;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // document_id is optional for text-based documents
        $documentId = $inputData['document_id'] ?? null;

        // Create news
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_news (
                website_id, title, excerpt, content, document_id, thumbnail,
                is_published, published_at, expires_at, priority, category,
                authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $websiteId,
            $inputData['title'] ?? '',
            $inputData['excerpt'] ?? null,
            $inputData['content'] ?? null,
            $documentId,
            $inputData['thumbnail'] ?? null,
            isset($inputData['is_published']) ? (int)$inputData['is_published'] : 0,
            $inputData['published_at'] ?? null,
            $inputData['expires_at'] ?? null,
            $inputData['priority'] ?? 'normal',
            $inputData['category'] ?? 'announcement',
            $authorityId
        ]);

        $newsId = $pdo->lastInsertId();

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_news', $newsId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'News created']
        ]);

        // Get the created news
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_news WHERE id = ?");
        $stmt->execute([$newsId]);
        $news = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'news' => $news
        ]);
    } catch (PDOException $e) {
        error_log("Error in createNews: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update news
 */
function updateNews(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $newsId = isset($inputData['id']) ? (int)$inputData['id'] : 0;

        if (!$newsId) {
            http_response_code(400);
            echo json_encode(['error' => 'News ID is required']);
            return;
        }

        // Check if news exists and get website_id
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_news
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$newsId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'News not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // document_id is optional for text-based documents
        $documentId = $inputData['document_id'] ?? null;

        // Update news
        $stmt = $pdo->prepare("
            UPDATE kdd_website_news SET
            title = ?,
            excerpt = ?,
            content = ?,
            document_id = ?,
            thumbnail = ?,
            is_published = ?,
            published_at = ?,
            expires_at = ?,
            priority = ?,
            category = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");

        $stmt->execute([
            $inputData['title'] ?? '',
            $inputData['excerpt'] ?? null,
            $inputData['content'] ?? null,
            $documentId,
            $inputData['thumbnail'] ?? null,
            isset($inputData['is_published']) ? (int)$inputData['is_published'] : 0,
            $inputData['published_at'] ?? null,
            $inputData['expires_at'] ?? null,
            $inputData['priority'] ?? 'normal',
            $inputData['category'] ?? 'announcement',
            $newsId,
            $authorityId
        ]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_news', $newsId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'News updated']
        ]);

        // Get the updated news
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_news WHERE id = ?");
        $stmt->execute([$newsId]);
        $news = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'news' => $news
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateNews: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete news
 */
function deleteNews(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $newsId = isset($inputData['news_id']) ? (int)$inputData['news_id'] : 0;

        if (!$newsId) {
            http_response_code(400);
            echo json_encode(['error' => 'News ID is required']);
            return;
        }

        // Check if news exists and get website_id
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_news
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$newsId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'News not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Delete download tracking records first
        $stmt = $pdo->prepare("DELETE FROM kdd_website_news_downloads WHERE news_id = ?");
        $stmt->execute([$newsId]);

        // Delete the news
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_news
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$newsId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_news', $newsId, $userId, [
            ['column_name' => 'action', 'old_value' => null, 'new_value' => 'News deleted']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'News deleted successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in deleteNews: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Track news download
 */
function trackNewsDownload(PDO $pdo): void
{
    try {
        $inputData = getInputData();
        $newsId = isset($inputData['newsId']) ? (int)$inputData['newsId'] :
                  (isset($inputData['news_id']) ? (int)$inputData['news_id'] : 0);

        if (!$newsId) {
            http_response_code(400);
            echo json_encode(['error' => 'News ID is required']);
            return;
        }

        // Increment download count
        $stmt = $pdo->prepare("
            UPDATE kdd_website_news
            SET download_count = download_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$newsId]);

        // Track download
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_news_downloads (news_id, ip_address, user_agent)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $newsId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Download tracked successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in trackNewsDownload: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
