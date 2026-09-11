<?php

declare(strict_types=1);
/**
 * Backend Endpoint: company/website/index.php
 * Handles CRUD operations for Company Websites, including configuration,
 * pages, posts, categories, and media management.
 * Uses Cookie-based Authentication and PDO database connection.
 */

// --- Core Initialisation ---
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap.php';


// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange

// --- Helper function for reading input data ---
// Note: getJsonRequestData() is already defined in bootstrap.php
// IMPORTANT: This function caches the result because php://input can only be read once
function getInputData(): array
{
    static $cachedData = null;

    // Return cached data if already read
    if ($cachedData !== null) {
        return $cachedData;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

    // If content type contains 'json', parse the request body as JSON
    if (strpos($contentType, 'json') !== false) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $data = json_decode($rawInput, true);
            $cachedData = is_array($data) ? $data : [];
            return $cachedData;
        }
        $cachedData = [];
        return $cachedData;
    }

    // Otherwise, return POST or GET data depending on request method
    if ($requestMethod === 'GET') {
        $cachedData = $_GET ?? [];
    } else {
        $cachedData = $_POST ?? [];
    }

    return $cachedData;
}

// --- Action Determination ---
$inputData = getInputData();
$action = $inputData['action'] ?? $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Define public actions (no auth required)
$public_actions = ['getWebsiteDetails', 'getNavigation', 'getPageDetails', 'submitContactForm', 'getPages', 'getPublicWebsites', 'getCategories', 'getPosts', 'getSections', 'getNews', 'trackNewsDownload'];

// Check if this is a public action
$is_public_action = in_array($action, $public_actions);

// Only require authentication for non-public actions
if (!$is_public_action) {
    // --- Authentication ---
    try {
        require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
    } catch (\Throwable $e) {
        error_log("Critical error during auth check include: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Authentication system error."]);
        exit();
    }

    // --- User Context & Authority Validation ---
    $userId = $decoded_jwt->userId ?? null;
    $userPermissions = $decoded_jwt->permissions ?? [];
    $authority = $decoded_jwt->authority ?? null;
    $authorityId = $decoded_jwt->authority_id ?? null;

    if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid token payload."]);
        exit();
    }
    require_once __DIR__ . '/../../utils/authority_helper.php';
    if (!isValidAuthority($pdo, $authorityId)) {
        http_response_code(403);
        echo json_encode(["error" => "Invalid authority context."]);
        exit();
    }

    // Feature-Zugriff prüfen
    if (!hasFeatureAccess($pdo, $authorityId, 'company_websites')) {
        http_response_code(403);
        echo json_encode(["error" => "This authority doesn't have access to company website features."]);
        exit();
    }
} else {
    // For public actions, set default values
    $userId = 0;
    $userPermissions = [];
    $authority = '';
    $authorityId = 0; // We'll use the authorityId from the website config for public actions
    
    // Include necessary helper files
    require_once __DIR__ . '/../../utils/authority_helper.php';
}

// --- Authorization & Action Routing ---
// $action and $inputData are already set at the top
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
$permissions_map = [
    // Configuration permissions
    'getWebsites'           => 'READ_COMPANY_WEBSITES', // Alias for getAllWebsites
    'getAllWebsites'        => 'READ_COMPANY_WEBSITES',
    'getWebsiteDetails'     => 'READ_COMPANY_WEBSITES',
    'getWebsiteConfig'      => 'READ_COMPANY_WEBSITES',
    'createWebsite'         => 'WRITE_COMPANY_WEBSITES',
    'saveWebsite'           => 'WRITE_COMPANY_WEBSITES',
    'updateWebsite'         => 'WRITE_COMPANY_WEBSITES', // Alias for saveWebsite
    'setWebsitePublished'   => 'WRITE_COMPANY_WEBSITES',
    'saveConfig'            => 'WRITE_COMPANY_WEBSITES',
    // Page permissions
    'getPages'              => 'READ_COMPANY_WEBSITES',
    'getPageDetails'        => 'READ_COMPANY_WEBSITES',
    'savePage'              => 'WRITE_COMPANY_WEBSITES',
    'createPage'            => 'WRITE_COMPANY_WEBSITES', // Alias for savePage
    'updatePage'            => 'WRITE_COMPANY_WEBSITES', // Alias for savePage
    'deletePage'            => 'DELETE_COMPANY_WEBSITES',
    // Post permissions
    'getPosts'              => 'READ_COMPANY_WEBSITES',
    'savePost'              => 'WRITE_COMPANY_WEBSITES',
    'createPost'            => 'WRITE_COMPANY_WEBSITES', // Alias for savePost
    'updatePost'            => 'WRITE_COMPANY_WEBSITES', // Alias for savePost
    'deletePost'            => 'DELETE_COMPANY_WEBSITES',
    // Category permissions
    'getCategories'         => 'READ_COMPANY_WEBSITES',
    'saveCategory'          => 'WRITE_COMPANY_WEBSITES',
    'deleteCategory'        => 'DELETE_COMPANY_WEBSITES',
    // Media permissions
    'getMedia'              => 'READ_COMPANY_WEBSITES',
    'uploadMedia'           => 'WRITE_COMPANY_WEBSITES',
    'uploadMediaBulk'       => 'WRITE_COMPANY_WEBSITES',
    'updateMedia'           => 'WRITE_COMPANY_WEBSITES',
    'deleteMedia'           => 'DELETE_COMPANY_WEBSITES',
    // Contact permissions
    'getContactSubmissions' => 'READ_COMPANY_WEBSITES',
    'markSubmissionAsRead'  => 'WRITE_COMPANY_WEBSITES',
    'deleteContact'         => 'DELETE_COMPANY_WEBSITES',
    // Navigation permissions
    'getNavigation'         => 'READ_COMPANY_WEBSITES',
    'saveNavigationItem'    => 'WRITE_COMPANY_WEBSITES',
    'createNavigationItem'  => 'WRITE_COMPANY_WEBSITES',
    'updateNavigationItem'  => 'WRITE_COMPANY_WEBSITES',
    'deleteNavigationItem'  => 'DELETE_COMPANY_WEBSITES',
    'updateNavigationOrder' => 'WRITE_COMPANY_WEBSITES',
    'updatePostOrder'       => 'WRITE_COMPANY_WEBSITES',
    'updateCategoryOrder'   => 'WRITE_COMPANY_WEBSITES',
    'getPublicWebsites'     => 'READ_COMPANY_WEBSITES',
    // Section permissions (for templates)
    'getSections'           => 'READ_COMPANY_WEBSITES',
    'createSection'         => 'WRITE_COMPANY_WEBSITES',
    'updateSection'         => 'WRITE_COMPANY_WEBSITES',
    'deleteSection'         => 'DELETE_COMPANY_WEBSITES',
    'updateSectionOrder'    => 'WRITE_COMPANY_WEBSITES',
    // News permissions
    'getNews'               => 'READ_COMPANY_WEBSITES',
    'createNews'            => 'WRITE_COMPANY_WEBSITES',
    'updateNews'            => 'WRITE_COMPANY_WEBSITES',
    'deleteNews'            => 'DELETE_COMPANY_WEBSITES',
    'trackNewsDownload'     => 'READ_COMPANY_WEBSITES'
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}
if ($required_permission === 'ACTION_NOT_DEFINED') {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid or missing action']);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific permission
} elseif ($required_permission === 'READ_COMPANY_WEBSITES' && (hasPermission($userPermissions, 'WRITE_COMPANY_WEBSITES') || hasPermission($userPermissions, 'DELETE_COMPANY_WEBSITES'))) {
    $has_permission = true; // WRITE or DELETE implies READ for company websites
}

// --- Execute Action or Deny ---
if ($has_permission || $is_public_action) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // GET Actions
        case 'getWebsites': // Alias for backward compatibility
        case 'getAllWebsites':
            if ($request_method === 'GET') getAllWebsites($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getPublicWebsites':
            if ($request_method === 'GET') getPublicWebsites($pdo);
            else MethodNotAllowed();
            break;
        case 'getWebsiteDetails':
            if ($request_method === 'GET') getWebsiteDetails($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getWebsiteConfig':
            if ($request_method === 'GET') getWebsiteConfig($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getPages':
            if ($request_method === 'GET') getPages($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getPageDetails':
            if ($request_method === 'GET') getPageDetails($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getPosts':
            if ($request_method === 'GET') getPosts($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getCategories':
            if ($request_method === 'GET') getCategories($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getMedia':
            if ($request_method === 'GET') getMedia($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getContactSubmissions':
            if ($request_method === 'GET') getContactSubmissions($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getNavigation':
            if ($request_method === 'GET') getNavigation($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // POST Actions
        case 'createWebsite':
            if ($is_post_request) createWebsite($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'saveWebsite':
        case 'updateWebsite': // Alias for frontend compatibility
            if ($is_post_request) saveWebsite($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'setWebsitePublished':
            if ($is_post_request) setWebsitePublished($pdo, $userId, $authorityId);
            else MethodNotAllowed();
            break;
        case 'saveConfig':
            if ($is_post_request) saveWebsiteConfig($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'savePage':
        case 'createPage': // Alias for frontend compatibility
        case 'updatePage': // Alias for frontend compatibility
            if ($is_post_request) savePage($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deletePage':
            if ($is_post_request) deletePage($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'savePost':
        case 'createPost': // Alias for frontend compatibility
        case 'updatePost': // Alias for frontend compatibility
            if ($is_post_request) savePost($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deletePost':
            if ($is_post_request) deletePost($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'saveCategory':
            if ($is_post_request) saveCategory($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteCategory':
            if ($is_post_request) deleteCategory($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'uploadMedia':
            if ($is_post_request) uploadMedia($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'uploadMediaBulk':
            if ($is_post_request) uploadMediaBulk($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateMedia':
            if ($is_post_request) updateMedia($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteMedia':
            if ($is_post_request) deleteMedia($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'markSubmissionAsRead':
            if ($is_post_request) markSubmissionAsRead($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteContact':
            if ($is_post_request) deleteContact($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
            // Navigation actions
        case 'createNavigationItem':
            if ($is_post_request) createNavigationItem($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateNavigationItem':
            if ($is_post_request) updateNavigationItem($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteNavigationItem':
            if ($is_post_request) deleteNavigationItem($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateNavigationOrder':
            if ($is_post_request) updateNavigationOrder($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updatePostOrder':
            if ($is_post_request) updatePostOrder($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateCategoryOrder':
            if ($is_post_request) updateCategoryOrder($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'saveNavigationItem':
            if ($is_post_request) saveNavigationItem($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Sections Actions (for templates)
        case 'getSections':
            if ($request_method === 'GET') getSections($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'createSection':
            if ($is_post_request) createSection($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateSection':
            if ($is_post_request) updateSection($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteSection':
            if ($is_post_request) deleteSection($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateSectionOrder':
            if ($is_post_request) updateSectionOrder($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // News Actions
        case 'getNews':
            if ($request_method === 'GET') getNews($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'createNews':
            if ($is_post_request) createNews($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateNews':
            if ($is_post_request) updateNews($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteNews':
            if ($is_post_request) deleteNews($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'trackNewsDownload':
            if ($request_method === 'GET') trackNewsDownload($pdo);
            else MethodNotAllowed();
            break;

        default:
            http_response_code(500);
            echo json_encode(['error' => 'Action routing error.']);
            break;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





/**
 * Check if a user has access to a website
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $websiteId Website ID
 * @param int $authorityId Authority ID
 * @return bool Whether the user has access to the website
 */
function hasWebsiteAccess(PDO $pdo, int $userId, int $websiteId, int $authorityId): bool
{
    try {
        // Check if the website exists and belongs to the authority
        $stmt = $pdo->prepare("
            SELECT id
            FROM kdd_website_config
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        error_log("Error checking website access: " . $e->getMessage());
        return false;
    }
}

// --- Function Implementations ---

/**
 * Get website configuration for a company
 */
function getWebsiteConfig(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] : 0;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, site_name, site_slogan, site_description, 
                   primary_color, secondary_color, background_color,
                   contact_email, contact_phone, footer_text,
                   is_active, maintenance_mode, maintenance_message, layout_template, navbar_name, 
                   social_links, show_contact_form, contact_form_fields, 
                   logo, banner, background_image, hero_image,
                   cta_text, cta_target_type, cta_target_id,
                   selected_scheme, use_custom_colors, custom_colors
            FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            http_response_code(404);
            echo json_encode(['error' => 'Website config not found']);
            return;
        }

        // Convert is_active to boolean for frontend
        $config['is_active'] = (bool)$config['is_active'];
        $config['show_contact_form'] = (bool)$config['show_contact_form'];
        $config['maintenance_mode'] = (bool)$config['maintenance_mode'];

        // Decode JSON fields
        if (!empty($config['social_links'])) {
            $config['social_links'] = json_decode($config['social_links'], true) ?? [];
        }

        if (!empty($config['contact_form_fields'])) {
            $config['contact_form_fields'] = json_decode($config['contact_form_fields'], true) ?? [];
        } else {
            $config['contact_form_fields'] = [];
        }

        // Decode custom_colors JSON field
        if (!empty($config['custom_colors'])) {
            $config['custom_colors'] = json_decode($config['custom_colors'], true) ?? null;
        }
        
        // Convert boolean fields
        $config['use_custom_colors'] = (bool)$config['use_custom_colors'];

        echo json_encode(['success' => true, 'config' => $config]);
    } catch (PDOException $e) {
        error_log("Error in getWebsiteConfig: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save website configuration
 */
function saveWebsiteConfig(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $config = $inputData['config'] ?? null;
        $companyId = isset($inputData['companyId']) ? (int)$inputData['companyId'] : 0;

        if (!$config || !$companyId) {
            http_response_code(400);
            echo json_encode(['error' => 'Config data and company ID are required']);
            return;
        }

        // Bereite Social Links für die Speicherung vor
        if (isset($config['social_links']) && is_array($config['social_links'])) {
            $config['social_links'] = json_encode($config['social_links']);
        }

        // Bereite Kontaktformularfelder für die Speicherung vor
        if (isset($config['contact_form_fields']) && is_array($config['contact_form_fields'])) {
            $config['contact_form_fields'] = json_encode($config['contact_form_fields']);
        } else {
            $config['contact_form_fields'] = '[]';
        }
        
        // Bereite custom_colors für die Speicherung vor
        if (isset($config['customColors']) && is_array($config['customColors'])) {
            $config['customColors'] = json_encode($config['customColors']);
        } else {
            $config['customColors'] = null;
        }

        // Prüfe, ob bereits eine Konfiguration für diese Firma existiert
        $stmt = $pdo->prepare("SELECT id FROM kdd_website_config WHERE authority_id = ?");
        $stmt->execute([$authorityId]);
        $configId = $stmt->fetchColumn();

        if ($configId) {
            // Update bestehende Konfiguration
            $stmt = $pdo->prepare("
                UPDATE kdd_website_config SET
                site_name = ?,
                site_slogan = ?,
                site_description = ?,
                primary_color = ?,
                secondary_color = ?,
                background_color = ?,
                contact_email = ?,
                contact_phone = ?,
                footer_text = ?,
                is_active = ?,
                maintenance_mode = ?,
                maintenance_message = ?,
                layout_template = ?,
                navbar_name = ?,
                show_contact_form = ?,
                contact_form_fields = ?,
                social_links = ?,
                logo = ?,
                banner = ?,
                background_image = ?,
                hero_image = ?,
                cta_text = ?,
                cta_target_type = ?,
                cta_target_id = ?,
                selected_scheme = ?,
                use_custom_colors = ?,
                custom_colors = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $config['site_name'],
                $config['site_slogan'] ?? '',
                $config['site_description'] ?? '',
                $config['primary_color'] ?? '#3b82f6',
                $config['secondary_color'] ?? '#1e3a8a',
                $config['background_color'] ?? '#ffffff',
                $config['contact_email'] ?? '',
                $config['contact_phone'] ?? '',
                $config['footer_text'] ?? '',
                $config['is_active'] ? 1 : 0,
                $config['maintenance_mode'] ? 1 : 0,
                $config['maintenance_message'] ?? 'Diese Website befindet sich derzeit im Wartungsmodus und wird in Kürze wieder verfügbar sein.',
                $config['layout_template'] ?? 'default',
                $config['navbar_name'] ?? 'Home',
                $config['show_contact_form'] ? 1 : 0,
                $config['contact_form_fields'],
                $config['social_links'] ?? '[]',
                $config['logo'] ?? null,
                $config['banner'] ?? null,
                $config['background_image'] ?? null,
                $config['hero_image'] ?? null,
                $config['cta_text'] ?? '',
                $config['cta_target_type'] ?? 'page',
                $config['cta_target_id'] ?? null,
                $config['selectedScheme'] ?? null,
                $config['useCustomColors'] == true ? 1 : 0,
                $config['customColors'] ?? null,
                $configId
            ]);
        } else {
            // Neue Konfiguration erstellen
            $stmt = $pdo->prepare("
                INSERT INTO kdd_website_config (
                    company_id, site_name, site_slogan, site_description, 
                    primary_color, secondary_color, background_color,
                    contact_email, contact_phone, footer_text, 
                    is_active, maintenance_mode, maintenance_message, layout_template, navbar_name, 
                    show_contact_form, contact_form_fields, social_links,
                    logo, banner, background_image, hero_image,
                    cta_text, cta_target_type, cta_target_id, authority_id,
                    selected_scheme, use_custom_colors, custom_colors
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId,
                $config['site_name'],
                $config['site_slogan'] ?? '',
                $config['site_description'] ?? '',
                $config['primary_color'] ?? '#3b82f6',
                $config['secondary_color'] ?? '#1e3a8a',
                $config['background_color'] ?? '#ffffff',
                $config['contact_email'] ?? '',
                $config['contact_phone'] ?? '',
                $config['footer_text'] ?? '',
                $config['is_active'] ? 1 : 0,
                $config['maintenance_mode'] ? 1 : 0,
                $config['maintenance_message'] ?? 'Diese Website befindet sich derzeit im Wartungsmodus und wird in Kürze wieder verfügbar sein.',
                $config['layout_template'] ?? 'default',
                $config['navbar_name'] ?? 'Home',
                $config['show_contact_form'] ? 1 : 0,
                $config['contact_form_fields'],
                $config['social_links'] ?? '[]',
                $config['logo'] ?? null,
                $config['banner'] ?? null,
                $config['background_image'] ?? null,
                $config['hero_image'] ?? null,
                $config['cta_text'] ?? '',
                $config['cta_target_type'] ?? 'page',
                $config['cta_target_id'] ?? null,
                $authorityId,
                $config['selectedScheme'] ?? null,
                $config['useCustomColors'] == true ? 1 : 0,
                $config['customColors'] ?? null
            ]);
            $configId = $pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'config_id' => $configId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save website config: ' . $e->getMessage()]);
    }
}

/**
 * Get pages for a website
 */
function getPages(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] : 0;
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
        
        // For public actions, get the website's authority_id for subsequent operations
        if ($is_public_action) {
            $authStmt = $pdo->prepare("SELECT authority_id FROM kdd_website_config WHERE id = ?");
            $authStmt->execute([$websiteId]);
            $authorityId = $authStmt->fetchColumn();
            
            if (!$authorityId) {
                http_response_code(404);
                echo json_encode(['error' => 'Website not found']);
                return;
            }
            
            // For public requests, only return published pages
            $stmt = $pdo->prepare("
                SELECT * FROM kdd_website_pages
                WHERE website_id = ? AND is_published = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$websiteId]);
        } else {
            // For authenticated requests, return all pages
            $stmt = $pdo->prepare("
                SELECT * FROM kdd_website_pages
                WHERE website_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$websiteId]);
        }
        
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'pages' => $pages
        ]);
    } catch (PDOException $e) {
        error_log("Error in getPages: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

/**
 * Save a page
 */
function savePage(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both wrapped (pageData) and unwrapped data structures
        $pageData = $inputData['pageData'] ?? $inputData;

        // Get website_id from either pageData or inputData directly
        $websiteId = isset($pageData['website_id']) ? (int)$pageData['website_id'] :
                     (isset($inputData['website_id']) ? (int)$inputData['website_id'] : 0);

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Page data and website ID are required']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Handle create/update logic
        $isUpdate = isset($pageData['id']) && $pageData['id'] > 0;

        if ($isUpdate) {
            // Check if page exists and belongs to this authority
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_website_pages 
                WHERE id = ? AND website_id = ? AND authority_id = ?
            ");
            $stmt->execute([$pageData['id'], $websiteId, $authorityId]);

            if (!$stmt->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Page not found or access denied']);
                return;
            }

            // Update existing page
            $stmt = $pdo->prepare("
                UPDATE kdd_website_pages SET
                title = ?,
                slug = ?,
                content = ?,
                is_published = ?,
                use_blocks = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $pageData['title'],
                $pageData['slug'] ?? '',
                $pageData['content'] ?? '',
                isset($pageData['is_published']) ? ($pageData['is_published'] ? 1 : 0) : 1,
                isset($pageData['use_blocks']) ? ($pageData['use_blocks'] ? 1 : 0) : 0,
                $pageData['id'],
                $authorityId
            ]);

            $pageId = $pageData['id'];
            $action = 'UPDATE';
            $message = 'Page updated successfully';
        } else {
            // Create new page
            $stmt = $pdo->prepare("
                INSERT INTO kdd_website_pages (
                    website_id, title, slug, content, 
                    is_published, use_blocks, authority_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId,
                $pageData['title'],
                $pageData['slug'] ?? '',
                $pageData['content'] ?? '',
                isset($pageData['is_published']) ? ($pageData['is_published'] ? 1 : 0) : 1,
                isset($pageData['use_blocks']) ? ($pageData['use_blocks'] ? 1 : 0) : 0,
                $authorityId
            ]);
            $pageId = $pdo->lastInsertId();
            $action = 'INSERT';
            $message = 'Page created successfully';
        }

        // Log the change
        logDatabaseChange($authorityId, $pdo, $action, 'kdd_website_pages', $pageId, $userId, [
            [
                'column_name' => 'action',
                'old_value' => $isUpdate ? 'Page before update' : null,
                'new_value' => $message
            ]
        ]);

        // Get the updated/created page
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'page' => $page,
            'message' => $message
        ]);
    } catch (PDOException $e) {
        error_log("Error in savePage: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a page
 */
function deletePage(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both camelCase and snake_case
        $pageId = isset($inputData['pageId']) ? (int)$inputData['pageId'] :
                  (isset($inputData['page_id']) ? (int)$inputData['page_id'] : 0);

        if (!$pageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID is required']);
            return;
        }

        // Get the website ID for the page
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_pages 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$pageId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Page not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Check if page is being used in navigation
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM kdd_website_navigation 
            WHERE page_id = ?
        ");
        $stmt->execute([$pageId]);
        $navCount = $stmt->fetchColumn();

        if ($navCount > 0) {
            // Return a warning but allow deletion
            $warning = 'This page is linked in navigation menu. Menu items will be updated.';

            // Update navigation items to remove references to this page
            $stmt = $pdo->prepare("
                UPDATE kdd_website_navigation 
                SET page_id = NULL 
                WHERE page_id = ?
            ");
            $stmt->execute([$pageId]);
        } else {
            $warning = null;
        }

        // Delete the page
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_pages 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$pageId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_pages', $pageId, $userId, [
            [
                'column_name' => 'deletion',
                'old_value' => 'Page',
                'new_value' => null
            ]
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Page deleted successfully',
            'warning' => $warning
        ]);
    } catch (PDOException $e) {
        error_log("Error in deletePage: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get posts for a website
 */
function getPosts(PDO $pdo, string $authority, int $authorityId): void
{
    // Get the website ID from the request
    $websiteId = filter_input(INPUT_GET, 'websiteId', FILTER_VALIDATE_INT);

    if (!$websiteId) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Website-ID']);
        return;
    }

    try {
        // Check if website belongs to current authority
        $stmt = $pdo->prepare("SELECT id FROM kdd_website_config WHERE id = ?");
        $stmt->execute([$websiteId]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Website nicht gefunden']);
            return;
        }

        // Get all posts for this website with their categories
        $query = "
            SELECT p.*, 
                   GROUP_CONCAT(DISTINCT c.id) AS category_ids,
                   GROUP_CONCAT(DISTINCT c.name) AS category_names
            FROM kdd_website_posts p
            LEFT JOIN kdd_website_post_category_rel pc ON p.id = pc.post_id
            LEFT JOIN kdd_website_categories c ON pc.category_id = c.id
            WHERE p.website_id = ?
            GROUP BY p.id
            ORDER BY p.sort_order ASC, p.created_at DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$websiteId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process categories for each post
        foreach ($posts as &$post) {
            if (!empty($post['category_ids'])) {
                $categoryIds = explode(',', $post['category_ids']);
                $categoryNames = explode(',', $post['category_names']);

                $categories = [];
                for ($i = 0; $i < count($categoryIds); $i++) {
                    $categories[] = [
                        'id' => (int)$categoryIds[$i],
                        'name' => $categoryNames[$i] ?? ''
                    ];
                }

                $post['categories'] = $categories;
            } else {
                $post['categories'] = [];
            }

            // Convert numeric fields to proper types
            $post['id'] = (int)$post['id'];
            $post['website_id'] = (int)$post['website_id'];
            $post['is_published'] = (bool)$post['is_published'];
            $post['is_featured'] = (bool)$post['is_featured'];
            $post['sort_order'] = (int)$post['sort_order'];

            // Remove the concatenated fields
            unset($post['category_ids']);
            unset($post['category_names']);
        }

        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        error_log('Error in getPosts: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Datenbankfehler beim Laden der Beiträge']);
    }
}

/**
 * Save a post
 */
function savePost(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get post data from the request
    $requestData = getInputData();
    // Support both wrapped (postData) and unwrapped data structures
    $postData = $requestData['postData'] ?? $requestData;

    // Debug log
    error_log("SavePost received data: " . json_encode($postData));

    // Validate required fields
    if (empty($postData['title']) || empty($postData['slug'])) {
        echo json_encode(['success' => false, 'error' => 'Titel und Slug sind erforderlich']);
        return;
    }

    // Check if website belongs to this authority
    $websiteId = $postData['website_id'] ?? 0;
    if (!$websiteId) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Website-ID']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verify website belongs to current authority
        $stmt = $pdo->prepare("SELECT id FROM kdd_website_config WHERE id = ? AND authority_id = ?");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Website nicht gefunden']);
            $pdo->rollBack();
            return;
        }

        // Check if slug is unique for this website (except for updates of the same post)
        $slugCheckQuery = "SELECT id FROM kdd_website_posts WHERE slug = ? AND website_id = ?";
        $params = [$postData['slug'], $websiteId];

        if (!empty($postData['id'])) {
            $slugCheckQuery .= " AND id <> ?";
            $params[] = $postData['id'];
        }

        $stmt = $pdo->prepare($slugCheckQuery);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Ein Beitrag mit diesem Slug existiert bereits']);
            $pdo->rollBack();
            return;
        }

        // Prepare data for database
        $title = $postData['title'];
        $slug = $postData['slug'];
        $excerpt = $postData['excerpt'] ?? '';
        $content = $postData['content'] ?? '';
        $featuredImage = $postData['featured_image'] ?? null;
        $isPublished = !empty($postData['is_published']) ? 1 : 0;
        $isFeatured = !empty($postData['is_featured']) ? 1 : 0;
        $publishedAt = $isPublished ? date('Y-m-d H:i:s') : null;

        // Debug log for featured image
        error_log("Featured image value before save: " . ($featuredImage ?: 'NULL'));

        // Get the highest sort_order for new posts
        $sortOrder = 0;
        if (empty($postData['id'])) {
            $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM kdd_website_posts WHERE website_id = ?");
            $stmt->execute([$websiteId]);
            $highestSortOrder = $stmt->fetchColumn();
            $sortOrder = $highestSortOrder !== false ? $highestSortOrder + 1 : 0;
        }

        if (!empty($postData['id'])) {
            // Update existing post
            $query = "
                UPDATE kdd_website_posts 
                SET 
                    title = ?,
                    slug = ?,
                    excerpt = ?,
                    content = ?,
                    featured_image = ?,
                    is_published = ?,
                    is_featured = ?,
                    updated_at = NOW(),
                    publish_date = CASE WHEN is_published = 0 AND ? = 1 THEN NOW() WHEN publish_date IS NULL AND ? = 1 THEN NOW() ELSE publish_date END
                WHERE id = ? AND website_id = ?
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $title,
                $slug,
                $excerpt,
                $content,
                $featuredImage,
                $isPublished,
                $isFeatured,
                $isPublished,
                $isPublished,
                $postData['id'],
                $websiteId
            ]);

            // Delete existing category relationships
            $stmt = $pdo->prepare("DELETE FROM kdd_website_post_category_rel WHERE post_id = ?");
            $stmt->execute([$postData['id']]);

            $postId = $postData['id'];

            // Debug log for update
            error_log("Updated post ID: $postId with featured image: " . ($featuredImage ?: 'NULL'));
        } else {
            // Insert new post
            $query = "
                INSERT INTO kdd_website_posts (
                    website_id, title, slug, excerpt, content, 
                    featured_image, is_published, is_featured, 
                    created_at, updated_at, publish_date,
                    created_by, authority_id, sort_order
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, 
                    NOW(), NOW(), ?,
                    ?, ?, ?
                )
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $websiteId,
                $title,
                $slug,
                $excerpt,
                $content,
                $featuredImage,
                $isPublished,
                $isFeatured,
                $publishedAt,
                $userId,
                $authorityId,
                $sortOrder
            ]);

            $postId = $pdo->lastInsertId();

            // Debug log for insert
            error_log("Inserted new post ID: $postId with featured image: " . ($featuredImage ?: 'NULL'));
        }

        // Add categories if provided
        if (!empty($postData['categories']) && is_array($postData['categories'])) {
            $insertCatQuery = "INSERT INTO kdd_website_post_category_rel (post_id, category_id) VALUES (?, ?)";
            $catStmt = $pdo->prepare($insertCatQuery);

            foreach ($postData['categories'] as $categoryId) {
                // Verify category belongs to this website
                $checkCatStmt = $pdo->prepare("SELECT id FROM kdd_website_categories WHERE id = ? AND website_id = ?");
                $checkCatStmt->execute([$categoryId, $websiteId]);

                if ($checkCatStmt->fetch()) {
                    $catStmt->execute([$postId, $categoryId]);
                }
            }
        }

        $pdo->commit();

        // Get the updated post to return
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_posts WHERE id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug log for retrieved post
        error_log("Retrieved post after save - featured_image: " . ($post['featured_image'] ?: 'NULL'));

        echo json_encode(['success' => true, 'post' => $post, 'message' => 'Beitrag erfolgreich gespeichert']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error in savePost: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Datenbankfehler beim Speichern des Beitrags: ' . $e->getMessage()]);
    }
}

/**
 * Delete a post
 */
function deletePost(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get post ID from the request
        $requestData = getInputData();
        // Support both camelCase and snake_case
        $postId = isset($requestData['postId']) ? (int)$requestData['postId'] :
                  (isset($requestData['post_id']) ? (int)$requestData['post_id'] : 0);

        if (!$postId) {
            echo json_encode(['success' => false, 'error' => 'Beitrags-ID ist erforderlich']);
            return;
        }

        // Get website ID for the post to verify authority access
        $stmt = $pdo->prepare("SELECT website_id FROM kdd_website_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            echo json_encode(['success' => false, 'error' => 'Beitrag nicht gefunden']);
            return;
        }

        // Verify website belongs to current authority
        $stmt = $pdo->prepare("SELECT id FROM kdd_website_config WHERE id = ? AND authority_id = ?");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Keine Berechtigung für diese Aktion']);
            return;
        }

        $pdo->beginTransaction();

        // Option 1: Hard delete - first delete category relationships
        $stmt = $pdo->prepare("DELETE FROM kdd_website_post_category_rel WHERE post_id = ?");
        $stmt->execute([$postId]);

        // Then delete the post
        $stmt = $pdo->prepare("DELETE FROM kdd_website_posts WHERE id = ?");
        $stmt->execute([$postId]);

        /* 
        // Option 2: Soft delete - mark the post as deleted
        $stmt = $pdo->prepare("
            UPDATE kdd_website_posts 
            SET is_deleted = 1, 
                deleted_at = NOW(), 
                deleted_by = ? 
            WHERE id = ?
        ");
        $stmt->execute([$userId, $postId]);
        */

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_posts', $postId, $userId, [
            [
                'column_name' => 'delete',
                'old_value' => 'Post',
                'new_value' => null
            ]
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Beitrag erfolgreich gelöscht'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error in deletePost: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Datenbankfehler beim Löschen des Beitrags: ' . $e->getMessage()]);
    }
}

/**
 * Get categories for a website
 */
function getCategories(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] : 0;
        global $userId;

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }



        // Get categories
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_categories
            WHERE website_id = ?
            ORDER BY name ASC
        ");
        $stmt->execute([$websiteId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (PDOException $e) {
        error_log("Error in getCategories: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save a category
 */
function saveCategory(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $categoryData = $inputData['categoryData'] ?? null;

        if (!$categoryData || !isset($categoryData['website_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category data and website ID are required']);
            return;
        }

        $websiteId = (int)$categoryData['website_id'];

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Handle create/update logic
        $isUpdate = isset($categoryData['id']) && $categoryData['id'] > 0;

        if ($isUpdate) {
            // Check if category exists and belongs to this authority
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_website_categories 
                WHERE id = ? AND website_id = ? AND authority_id = ?
            ");
            $stmt->execute([$categoryData['id'], $websiteId, $authorityId]);

            if (!$stmt->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found or access denied']);
                return;
            }

            // Update existing category
            $stmt = $pdo->prepare("
                UPDATE kdd_website_categories SET
                name = ?,
                slug = ?,
                description = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $categoryData['name'],
                $categoryData['slug'] ?? '',
                $categoryData['description'] ?? '',
                $categoryData['id'],
                $authorityId
            ]);

            $categoryId = $categoryData['id'];
            $action = 'UPDATE';
            $message = 'Category updated successfully';
        } else {
            // Create new category
            $stmt = $pdo->prepare("
                INSERT INTO kdd_website_categories (
                    website_id, name, slug, description, 
                    authority_id
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId,
                $categoryData['name'],
                $categoryData['slug'] ?? '',
                $categoryData['description'] ?? '',
                $authorityId
            ]);
            $categoryId = $pdo->lastInsertId();
            $action = 'INSERT';
            $message = 'Category created successfully';
        }

        // Log the change
        logDatabaseChange($authorityId, $pdo, $action, 'kdd_website_categories', $categoryId, $userId, [
            [
                'column_name' => 'action',
                'old_value' => $isUpdate ? 'Category before update' : null,
                'new_value' => $message
            ]
        ]);

        // Get the updated/created category
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'category' => $category,
            'message' => $message
        ]);
    } catch (PDOException $e) {
        error_log("Error in saveCategory: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a category
 */
function deleteCategory(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both camelCase and snake_case
        $categoryId = isset($inputData['categoryId']) ? (int)$inputData['categoryId'] :
                      (isset($inputData['category_id']) ? (int)$inputData['category_id'] : 0);

        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            return;
        }

        // Get the website ID for the category
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_categories 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$categoryId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Check if category is being used in posts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM kdd_website_post_category_rel 
            WHERE category_id = ?
        ");
        $stmt->execute([$categoryId]);
        $postCount = $stmt->fetchColumn();

        if ($postCount > 0) {
            // Option 1: Prevent deletion if posts are using this category
            http_response_code(400);
            echo json_encode([
                'error' => 'Cannot delete category that is being used by posts. Please remove all post associations first.'
            ]);
            return;

            // Option 2 (alternative): Delete post category relationships first
            // $stmt = $pdo->prepare("DELETE FROM kdd_website_post_category_rel WHERE category_id = ?");
            // $stmt->execute([$categoryId]);
        }

        // Delete the category
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_categories 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$categoryId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_categories', $categoryId, $userId, [
            [
                'column_name' => 'action',
                'old_value' => 'Category',
                'new_value' => null
            ]
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in deleteCategory: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get media files for a website
 */
function getMedia(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_GET, 'websiteId', FILTER_VALIDATE_INT);

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        // Get all media files for this website
        $stmt = $pdo->prepare("
            SELECT id, website_id, file_name, file_path, file_type, file_size, 
                   title, alt_text, description, media_type, is_featured,
                   created_at, updated_at
            FROM kdd_website_media
            WHERE website_id = ? AND authority_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data for frontend
        foreach ($media as &$item) {
            $item['id'] = (int)$item['id'];
            $item['website_id'] = (int)$item['website_id'];
            $item['file_size'] = (int)$item['file_size'];
            $item['is_featured'] = (bool)$item['is_featured'];
        }

        echo json_encode([
            'success' => true,
            'media' => $media
        ]);
    } catch (PDOException $e) {
        error_log("Error in getMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Upload a media file
 */
function uploadMedia(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_POST, 'website_id', FILTER_VALIDATE_INT);
        $mediaType = filter_input(INPUT_POST, 'mediaType', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'image';

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        // Check if a file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = isset($_FILES['file']) ? getUploadErrorMessage($_FILES['file']['error']) : 'No file uploaded';
            http_response_code(400);
            echo json_encode(['error' => $errorMessage]);
            return;
        }

        $file = $_FILES['file'];
        $originalName = $file['name'];
        $tempPath = $file['tmp_name'];
        $fileType = $file['type'];
        $fileSize = $file['size'];

        // Validate file type (optional - adjust as needed)
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'text/plain',
            'video/mp4',
            'video/webm',
            'video/ogg',
            'audio/mpeg',
            'audio/wav',
            'audio/ogg'
        ];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Allowed types: jpg, png, gif, webp, svg, pdf, doc, docx, xls, xlsx, zip, txt, mp4, webm, ogg, mp3, wav']);
            return;
        }

        // Validate file size (max 10MB)
        $maxSize = 30 * 1024 * 1024; // 10MB
        if ($fileSize > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'File is too large. Maximum size is 10MB']);
            return;
        }

        // Create directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/website/' . $authorityId . '/' . $websiteId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
        $uploadPath = $uploadDir . '/' . $filename;
        $relativePath = 'uploads/website/' . $authorityId . '/' . $websiteId . '/' . $filename;

        // Move the uploaded file
        if (!move_uploaded_file($tempPath, $uploadPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file']);
            return;
        }

        // Determine the right media_type enum value based on file type
        $mediaTypeEnum = 'image'; // Default
        if (strpos($fileType, 'video/') === 0) {
            $mediaTypeEnum = 'video';
        } elseif (strpos($fileType, 'audio/') === 0) {
            $mediaTypeEnum = 'audio';
        } elseif (strpos($fileType, 'application/') === 0 || $fileType === 'text/plain') {
            $mediaTypeEnum = 'document';
        }

        // Save file information to database
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_media (
                website_id, file_name, file_path, file_type, file_size,
                media_type, authority_id, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $websiteId,
            $filename,
            $relativePath,
            $fileType,
            $fileSize,
            $mediaTypeEnum,
            $authorityId
        ]);

        $mediaId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'fileName' => $filename,
            'filePath' => $relativePath,
            'mediaId' => $mediaId
        ]);
    } catch (PDOException $e) {
        error_log("Error in uploadMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error in uploadMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a media file
 */
function deleteMedia(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both camelCase and snake_case
        $mediaId = isset($inputData['mediaId']) ? (int)$inputData['mediaId'] :
                   (isset($inputData['media_id']) ? (int)$inputData['media_id'] : 0);

        if (!$mediaId) {
            http_response_code(400);
            echo json_encode(['error' => 'Media ID is required']);
            return;
        }

        // Check if media exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT m.id, m.website_id, m.file_name, m.file_path
            FROM kdd_website_media m
            JOIN kdd_website_config w ON m.website_id = w.id
            WHERE m.id = ? AND w.authority_id = ?
        ");
        $stmt->execute([$mediaId, $authorityId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$media) {
            http_response_code(404);
            echo json_encode(['error' => 'Media not found or access denied']);
            return;
        }

        // Delete the media entry from database
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_media
            WHERE id = ?
        ");
        $result = $stmt->execute([$mediaId]);

        if ($result) {
            // Physically delete the file
            $filePath = __DIR__ . '/../../' . $media['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete media']);
        }
    } catch (PDOException $e) {
        error_log("Error in deleteMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update media metadata
 */
function updateMedia(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $mediaData = $inputData['mediaData'] ?? null;

        if (!$mediaData || !isset($mediaData['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Media data is required']);
            return;
        }

        $mediaId = (int)$mediaData['id'];

        // Check if media exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT m.id, m.website_id, m.media_type
            FROM kdd_website_media m
            JOIN kdd_website_config w ON m.website_id = w.id
            WHERE m.id = ? AND w.authority_id = ?
        ");
        $stmt->execute([$mediaId, $authorityId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$media) {
            http_response_code(404);
            echo json_encode(['error' => 'Media not found or access denied']);
            return;
        }

        // Determine the media type if it was changed
        $mediaType = $media['media_type']; // Default to current value
        if (isset($mediaData['media_type']) && in_array($mediaData['media_type'], ['image', 'document', 'video', 'audio'])) {
            $mediaType = $mediaData['media_type'];
        }

        // Update media metadata
        $stmt = $pdo->prepare("
            UPDATE kdd_website_media
            SET title = ?,
                alt_text = ?,
                description = ?,
                media_type = ?,
                is_featured = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $mediaData['title'] ?? '',
            $mediaData['alt_text'] ?? '',
            $mediaData['description'] ?? '',
            $mediaType,
            isset($mediaData['is_featured']) ? ($mediaData['is_featured'] ? 1 : 0) : 0,
            $mediaId
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Media updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update media']);
        }
    } catch (PDOException $e) {
        error_log("Error in updateMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Helper function to get error message for file upload errors
 */
function getUploadErrorMessage(int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}

/**
 * Get contact form submissions for a website
 */
function getContactSubmissions(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_GET, 'websiteId', FILTER_VALIDATE_INT);

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        // Get contact submissions - fixed table name from kdd_website_contact_submissions to kdd_website_contact
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_contact
            WHERE website_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$websiteId]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data for frontend
        foreach ($contacts as &$contact) {
            $contact['id'] = (int)$contact['id'];
            $contact['website_id'] = (int)$contact['website_id'];
            $contact['is_read'] = (bool)$contact['is_read'];
        }

        echo json_encode([
            'success' => true,
            'contacts' => $contacts
        ]);
    } catch (PDOException $e) {
        error_log("Error in getContactSubmissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Mark a contact form submission as read
 */
function markSubmissionAsRead(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $contactId = isset($inputData['contactId']) ? (int)$inputData['contactId'] : 0;
        $isRead = isset($inputData['isRead']) ? (bool)$inputData['isRead'] : true;

        if (!$contactId) {
            http_response_code(400);
            echo json_encode(['error' => 'Contact submission ID is required']);
            return;
        }

        // Get the website ID for this contact submission
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_contact 
            WHERE id = ?
        ");
        $stmt->execute([$contactId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Contact submission not found']);
            return;
        }

        // Check if website belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Update the read status
        $stmt = $pdo->prepare("
            UPDATE kdd_website_contact 
            SET is_read = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $result = $stmt->execute([$isRead ? 1 : 0, $contactId]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact submission updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update contact submission']);
        }
    } catch (PDOException $e) {
        error_log("Error in markSubmissionAsRead: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get all websites for the current authority
 */
function getAllWebsites(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        // Get all websites for this authority
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_config
            WHERE authority_id = ?
            ORDER BY site_name ASC
        ");
        $stmt->execute([$authorityId]);
        $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'websites' => $websites
        ]);
    } catch (PDOException $e) {
        error_log("Error in getAllWebsites: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get details for a specific website
 */
function getWebsiteDetails(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both camelCase and snake_case
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
        
        // For public actions, get the website's authority_id for subsequent operations
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

        // Get website details
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_config
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $website = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$website) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found']);
            return;
        }

        // Get pages
        $pagesStmt = $pdo->prepare("SELECT * FROM kdd_website_pages WHERE website_id = ? ORDER BY created_at DESC");
        $pagesStmt->execute([$websiteId]);
        $pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get posts
        $postsStmt = $pdo->prepare("SELECT * FROM kdd_website_posts WHERE website_id = ? ORDER BY sort_order ASC");
        $postsStmt->execute([$websiteId]);
        $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get categories
        $categoriesStmt = $pdo->prepare("SELECT * FROM kdd_website_categories WHERE website_id = ? ORDER BY name ASC");
        $categoriesStmt->execute([$websiteId]);
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get media
        $mediaStmt = $pdo->prepare("SELECT * FROM kdd_website_media WHERE website_id = ? ORDER BY created_at DESC");
        $mediaStmt->execute([$websiteId]);
        $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get contacts
        $contactsStmt = $pdo->prepare("SELECT * FROM kdd_website_contact WHERE website_id = ? ORDER BY created_at DESC");
        $contactsStmt->execute([$websiteId]);
        $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get navigation
        $navStmt = $pdo->prepare("SELECT * FROM kdd_website_navigation WHERE website_id = ? ORDER BY sort_order ASC");
        $navStmt->execute([$websiteId]);
        $navigation = $navStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get sections (for templates like one-pager)
        $sectionsStmt = $pdo->prepare("SELECT * FROM kdd_website_sections WHERE website_id = ? ORDER BY sort_order ASC");
        $sectionsStmt->execute([$websiteId]);
        $sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get news (for news/document system)
        $newsStmt = $pdo->prepare("
            SELECT n.*, m.file_path, m.file_name, m.file_type
            FROM kdd_website_news n
            LEFT JOIN kdd_website_media m ON n.document_id = m.id
            WHERE n.website_id = ?
            AND (n.is_published = 1 OR ? = 1)
            ORDER BY n.published_at DESC, n.created_at DESC
        ");
        $newsStmt->execute([$websiteId, $is_public_action ? 0 : 1]);
        $news = $newsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON fields in website
        if (!empty($website['custom_colors'])) {
            $website['custom_colors'] = json_decode($website['custom_colors'], true) ?? null;
        }
        if (!empty($website['social_links'])) {
            $website['social_links'] = json_decode($website['social_links'], true) ?? [];
        }
        if (!empty($website['contact_form_fields'])) {
            $website['contact_form_fields'] = json_decode($website['contact_form_fields'], true) ?? [];
        }

        // Convert boolean fields
        $website['use_custom_colors'] = (bool)($website['use_custom_colors'] ?? 0);
        $website['is_active'] = (bool)($website['is_active'] ?? 1);
        $website['show_contact_form'] = (bool)($website['show_contact_form'] ?? 0);
        $website['maintenance_mode'] = (bool)($website['maintenance_mode'] ?? 0);

        echo json_encode([
            'success' => true,
            'data' => [
                'website' => $website,
                'pages' => $pages,
                'posts' => $posts,
                'categories' => $categories,
                'media' => $media,
                'contacts' => $contacts,
                'navigation' => $navigation,
                'sections' => $sections,
                'news' => $news
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Error in getWebsiteDetails: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create a new website for a company
 */
function createWebsite(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteData = $inputData['websiteData'] ?? [];

        // Get authority name for default site name
        $authorityName = $authority;
        try {
            // kdd_authority (einzahl) gibt es nicht - die Tabelle heisst
            // kdd_authorities. Die Abfrage lief deshalb immer in den catch,
            // und der Standardname war der Schluessel der Behoerde statt ihres
            // Anzeigenamens: "firedepartment Website" statt "Fire Department".
            $stmt = $pdo->prepare("SELECT display_name, name FROM kdd_authorities WHERE id = ?");
            $stmt->execute([$authorityId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $authorityName = $result['display_name'] ?: $result['name'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching authority name: " . $e->getMessage());
        }

        // Use default values if websiteData is not provided or incomplete
        // Ein leerer Name ist so gut wie kein Name - sonst steht die Website
        // spaeter namenlos in der Auswahl.
        $siteName = trim((string)($websiteData['site_name'] ?? ''));
        if ($siteName === '') {
            $siteName = $authorityName . ' Website';
        }
        $siteSlogan = trim((string)($websiteData['site_slogan'] ?? ''));
        $siteDescription = $websiteData['site_description'] ?? '';
        $primaryColor = $websiteData['primary_color'] ?? '#3b82f6';
        $secondaryColor = $websiteData['secondary_color'] ?? '#1e3a8a';
        $backgroundColor = $websiteData['background_color'] ?? '#ffffff';
        $contactEmail = $websiteData['contact_email'] ?? '';
        $contactPhone = $websiteData['contact_phone'] ?? '';
        $footerText = $websiteData['footer_text'] ?? '';
        $layoutTemplate = $websiteData['layout_template'] ?? 'default';
        $navbarName = $websiteData['navbar_name'] ?? 'Home';

        // Insert website
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_config (
                site_name, site_slogan, site_description,
                primary_color, secondary_color, background_color,
                contact_email, contact_phone, footer_text,
                is_active, layout_template, navbar_name, authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $siteName,
            $siteSlogan,
            $siteDescription,
            $primaryColor,
            $secondaryColor,
            $backgroundColor,
            $contactEmail,
            $contactPhone,
            $footerText,
            0,
            $layoutTemplate,
            $navbarName,
            $authorityId
        ]);
        $websiteId = $pdo->lastInsertId();

        /*
           Eine neue Website ist nicht mehr leer.

           Bisher legte das Anlegen nur den Datensatz an; der Benutzer landete
           in einem Editor mit neun Reitern und nichts darin, ohne Hinweis,
           womit er anfangen soll. createDefaultSections() gibt es laengst - sie
           lief nur beim Speichern und nur fuer die Einseiter-Vorlage. Jetzt
           bekommt jede neue Website die Abschnitte ihrer Vorlage und ist vom
           ersten Augenblick an ansehbar.

           Schlaegt es fehl, steht die Website trotzdem: ein fehlender
           Startabschnitt ist kein Grund, das Anlegen scheitern zu lassen.
        */
        try {
            if ($layoutTemplate === 'onepager') {
                createDefaultSections($pdo, (int)$websiteId, $userId, $authorityId, $layoutTemplate);
            } else {
                /*
                   Die klassische Vorlage arbeitet mit Seiten, nicht mit
                   Abschnitten. Sie bekommt deshalb eine Startseite mit einem
                   Satz Text - genug, damit die Vorschau sofort etwas zeigt und
                   klar ist, wo man weiterschreibt.
                */
                $stmtSeite = $pdo->prepare("
                    INSERT INTO kdd_website_pages
                        (website_id, title, slug, content, is_published, status, published_at)
                    VALUES (?, ?, 'startseite', ?, 1, 'published', NOW())
                ");
                $stmtSeite->execute([
                    $websiteId,
                    'Startseite',
                    '<p>' . htmlspecialchars($siteSlogan !== '' ? $siteSlogan : $siteName, ENT_QUOTES, 'UTF-8')
                        . '</p><p>Dieser Text steht auf der Startseite. Ersetze ihn unter "Seiten".</p>',
                ]);

                // Ohne Eintrag in der Navigation ist die Seite zwar da, aber
                // nirgends verlinkt.
                $stmtNavi = $pdo->prepare("
                    INSERT INTO kdd_website_navigation (website_id, title, url, sort_order, is_active)
                    VALUES (?, 'Start', '/', 0, 1)
                ");
                $stmtNavi->execute([$websiteId]);
            }
        } catch (\Throwable $e) {
            error_log('Startinhalt fuer Website ' . $websiteId . ' nicht angelegt: ' . $e->getMessage());
        }

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_config', $websiteId, $userId, [
            [
                'column_name' => 'creation',
                'old_value' => null,
                'new_value' => 'New website created'
            ]
        ]);

        // Get the created website
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_config WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'website' => $website,
            'website_id' => $websiteId
        ]);
    } catch (PDOException $e) {
        error_log("Error in createWebsite: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save website details
 */
/**
 * Schaltet die Website oeffentlich oder nimmt sie wieder vom Netz.
 *
 * Eine eigene Aktion, weil saveWebsite() jede Spalte der Zeile ueberschreibt.
 * Ueber sie zu schalten hiesse, beim Umlegen des Schalters nebenbei den ganzen
 * Formularstand mitzuschreiben - auch das, was der Benutzer gerade nur
 * angetippt hat. Hier wird genau ein Feld angefasst.
 */
function setWebsitePublished(PDO $pdo, int $userId, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = (int)($inputData['website_id'] ?? $inputData['websiteId'] ?? 0);

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        if (!array_key_exists('is_active', $inputData)) {
            http_response_code(400);
            echo json_encode(['error' => 'is_active is required']);
            return;
        }
        $istOeffentlich = filter_var($inputData['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        // Die Behoerde muss zur Website passen - sonst schaltet jemand eine
        // fremde Website ab.
        $stmt = $pdo->prepare("SELECT id FROM kdd_website_config WHERE id = ? AND authority_id = ?");
        $stmt->execute([$websiteId, $authorityId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE kdd_website_config
            SET is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$istOeffentlich, $websiteId, $authorityId]);

        try {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_config', $websiteId, $userId, [
                ['column_name' => 'is_active', 'old_value' => $istOeffentlich ? '0' : '1', 'new_value' => (string)$istOeffentlich],
            ]);
        } catch (\Throwable $e) {
            error_log('Schaltvorgang nicht protokolliert: ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'is_active' => (bool)$istOeffentlich]);
    } catch (\Throwable $e) {
        error_log('setWebsitePublished fehlgeschlagen: ' . get_class($e) . ' ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo json_encode(['error' => 'Der Schaltvorgang ist fehlgeschlagen.']);
    }
}

function saveWebsite(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        error_log("saveWebsite called with userId=$userId, authorityId=$authorityId");
        $inputData = getInputData();
        error_log("Input data received: " . json_encode(array_keys($inputData)));

        // Support both formats: nested websiteData or flat structure
        $websiteData = $inputData['websiteData'] ?? $inputData;

        // Get website ID from multiple possible locations
        $websiteId = isset($inputData['websiteId'])
            ? (int)$inputData['websiteId']
            : (isset($inputData['website_id']) ? (int)$inputData['website_id']
            : (isset($websiteData['id']) ? (int)$websiteData['id'] : 0));

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        error_log("DEBUG: Checking if website $websiteId exists for authority $authorityId");
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);
        if (!$stmt->fetchColumn()) {
            error_log("DEBUG: Website not found or access denied");
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }
        error_log("DEBUG: Website exists, proceeding with update");
        error_log("DEBUG: layout_template=" . ($websiteData['layout_template'] ?? 'NOT SET'));

        // Update website - try with new columns first, fallback to old columns if they don't exist
        error_log("DEBUG: Attempting UPDATE with new columns");
        try {
            $stmt = $pdo->prepare("
                UPDATE kdd_website_config SET
                site_name = ?,
                site_slogan = ?,
                site_description = ?,
                primary_color = ?,
                secondary_color = ?,
                background_color = ?,
                contact_email = ?,
                contact_phone = ?,
                footer_text = ?,
                is_active = ?,
                layout_template = ?,
                template_settings = ?,
                sections_order = ?,
                navbar_name = ?,
                show_contact_form = ?,
                social_links = ?,
                logo = ?,
                banner = ?,
                background_image = ?,
                hero_image = ?,
                cta_text = ?,
                cta_target_type = ?,
                cta_target_id = ?,
                maintenance_mode = ?,
                maintenance_message = ?,
                selected_scheme = ?,
                use_custom_colors = ?,
                custom_colors = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            // Handle customColors - ensure it's JSON
            $customColorsJson = null;
            if (isset($websiteData['customColors'])) {
                $customColorsJson = is_string($websiteData['customColors']) ? $websiteData['customColors'] : json_encode($websiteData['customColors']);
            }

            $stmt->execute([
                $websiteData['site_name'],
                $websiteData['site_slogan'] ?? '',
                $websiteData['site_description'] ?? '',
                $websiteData['primary_color'] ?? '#3b82f6',
                $websiteData['secondary_color'] ?? '#1e3a8a',
                $websiteData['background_color'] ?? '#ffffff',
                $websiteData['contact_email'] ?? '',
                $websiteData['contact_phone'] ?? '',
                $websiteData['footer_text'] ?? '',
                isset($websiteData['is_active']) ? ($websiteData['is_active'] ? 1 : 0) : 1,
                $websiteData['layout_template'] ?? 'default',
                isset($websiteData['template_settings']) ? (is_string($websiteData['template_settings']) ? $websiteData['template_settings'] : json_encode($websiteData['template_settings'])) : null,
                isset($websiteData['sections_order']) ? (is_string($websiteData['sections_order']) ? $websiteData['sections_order'] : json_encode($websiteData['sections_order'])) : null,
                $websiteData['navbar_name'] ?? 'Home',
                isset($websiteData['show_contact_form']) ? ($websiteData['show_contact_form'] ? 1 : 0) : 0,
                $websiteData['social_links'] ?? null,
                $websiteData['logo'] ?? null,
                $websiteData['banner'] ?? null,
                $websiteData['background_image'] ?? null,
                $websiteData['hero_image'] ?? null,
                $websiteData['cta_text'] ?? null,
                $websiteData['cta_target_type'] ?? 'page',
                $websiteData['cta_target_id'] ?? null,
                isset($websiteData['maintenance_mode']) ? ($websiteData['maintenance_mode'] ? 1 : 0) : 0,
                $websiteData['maintenance_message'] ?? 'Diese Website befindet sich derzeit im Wartungsmodus und wird in Kürze wieder verfügbar sein.',
                $websiteData['selected_scheme'] ?? $websiteData['selectedScheme'] ?? 'Blue Ocean',
                isset($websiteData['use_custom_colors']) ? ($websiteData['use_custom_colors'] ? 1 : 0) : (isset($websiteData['useCustomColors']) ? ($websiteData['useCustomColors'] ? 1 : 0) : 0),
                $customColorsJson,
                $websiteId
            ]);
            error_log("DEBUG: UPDATE with new columns succeeded");
        } catch (PDOException $e) {
            // If columns don't exist (migration not run), update only base columns
            error_log("DEBUG: Could not update with new template columns, falling back to base columns: " . $e->getMessage());
            $stmt = $pdo->prepare("
                UPDATE kdd_website_config SET
                site_name = ?,
                site_slogan = ?,
                site_description = ?,
                primary_color = ?,
                secondary_color = ?,
                background_color = ?,
                contact_email = ?,
                contact_phone = ?,
                footer_text = ?,
                is_active = ?,
                navbar_name = ?,
                show_contact_form = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $websiteData['site_name'],
                $websiteData['site_slogan'] ?? '',
                $websiteData['site_description'] ?? '',
                $websiteData['primary_color'] ?? '#3b82f6',
                $websiteData['secondary_color'] ?? '#1e3a8a',
                $websiteData['background_color'] ?? '#ffffff',
                $websiteData['contact_email'] ?? '',
                $websiteData['contact_phone'] ?? '',
                $websiteData['footer_text'] ?? '',
                isset($websiteData['is_active']) ? ($websiteData['is_active'] ? 1 : 0) : 1,
                $websiteData['navbar_name'] ?? 'Home',
                isset($websiteData['show_contact_form']) ? ($websiteData['show_contact_form'] ? 1 : 0) : 0,
                $websiteId
            ]);
        }

        // Auto-create default sections if template is onepager and no sections exist
        // This is wrapped in try-catch to not break the save if sections table doesn't exist yet
        $layoutTemplate = $websiteData['layout_template'] ?? 'default';
        error_log("DEBUG: layout_template after save is: $layoutTemplate");
        if ($layoutTemplate === 'onepager') {
            error_log("DEBUG: OnePager template detected, calling createDefaultSections");
            try {
                $sectionsCreated = createDefaultSections($pdo, $websiteId, $userId, $authorityId, 'onepager');
                error_log("DEBUG: createDefaultSections returned: " . ($sectionsCreated ? 'true' : 'false'));
            } catch (PDOException $e) {
                // Log error but don't fail the save - table might not exist yet
                error_log("DEBUG: Could not create default sections (table might not exist): " . $e->getMessage());
            } catch (Exception $e) {
                error_log("DEBUG: Error creating default sections (general): " . $e->getMessage());
            }
        } else {
            error_log("DEBUG: Not OnePager template, skipping createDefaultSections");
        }

        // Log the change
        error_log("DEBUG: Calling logDatabaseChange");
        try {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_config', $websiteId, $userId, [
                [
                    'column_name' => 'update',
                    'old_value' => null,
                    'new_value' => 'Website updated'
                ]
            ]);
            error_log("DEBUG: logDatabaseChange succeeded");
        } catch (Exception $e) {
            error_log("DEBUG: logDatabaseChange failed: " . $e->getMessage());
        }

        // Get the updated website
        error_log("DEBUG: Fetching updated website data");
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_config WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("DEBUG: Website data fetched, sending response");

        echo json_encode([
            'success' => true,
            'website' => $website
        ]);
        error_log("DEBUG: Response sent successfully");
    } catch (PDOException $e) {
        error_log("Error in saveWebsite (PDO): " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'sql_state' => $e->getCode()
        ]);
    } catch (Exception $e) {
        error_log("Error in saveWebsite (General): " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage()
        ]);
    }
} 

/**
 * Get navigation menu items for a website
 */
function getNavigation(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $websiteId = isset($inputData['websiteId']) ? (int)$inputData['websiteId'] : 0;
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
        
        // For public actions, get the website's authority_id for subsequent operations
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

        // Get navigation items
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_navigation
            WHERE website_id = ? AND authority_id = ? AND is_active = 1
            ORDER BY parent_id, sort_order
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $navigation = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'navigation' => $navigation
        ]);
    } catch (PDOException $e) {
        error_log("Error in getNavigation: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create a new navigation item
 */
function createNavigationItem(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $navigationData = $inputData['navigationData'] ?? null;

        if (!$navigationData || !isset($navigationData['website_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Navigation data and website ID are required']);
            return;
        }

        $websiteId = (int)$navigationData['website_id'];

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Determine the next sort order value if not provided
        if (!isset($navigationData['sort_order'])) {
            $stmt = $pdo->prepare(
                "
                SELECT COALESCE(MAX(sort_order), 0) + 1 
                FROM kdd_website_navigation 
                WHERE website_id = ? 
                AND parent_id " . (isset($navigationData['parent_id']) ? "= ?" : "IS NULL")
            );

            if (isset($navigationData['parent_id'])) {
                $stmt->execute([$websiteId, $navigationData['parent_id']]);
            } else {
                $stmt->execute([$websiteId]);
            }

            $navigationData['sort_order'] = $stmt->fetchColumn();
        }

        // Create new navigation item
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_navigation (
                website_id, title, url, page_id, 
                parent_id, sort_order, target, 
                is_active, authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $websiteId,
            $navigationData['title'],
            $navigationData['url'] ?? '',
            $navigationData['page_id'] ?? null,
            $navigationData['parent_id'] ?? null,
            $navigationData['sort_order'],
            $navigationData['target'] ?? '_self',
            isset($navigationData['is_active']) ? ($navigationData['is_active'] ? 1 : 0) : 1,
            $authorityId
        ]);
        $navItemId = $pdo->lastInsertId();

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_navigation', $navItemId, $userId, [
            [
                'column_name' => 'creation',
                'old_value' => null,
                'new_value' => 'New navigation item created'
            ]
        ]);

        // Get the created navigation item
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_navigation WHERE id = ?");
        $stmt->execute([$navItemId]);
        $navigationItem = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'navigationItem' => $navigationItem
        ]);
    } catch (PDOException $e) {
        error_log("Error in createNavigationItem: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update an existing navigation item
 */
function updateNavigationItem(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $navigationData = $inputData['navigationData'] ?? null;

        if (!$navigationData || !isset($navigationData['id']) || !isset($navigationData['website_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Navigation data, ID, and website ID are required']);
            return;
        }

        $navItemId = (int)$navigationData['id'];
        $websiteId = (int)$navigationData['website_id'];

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Check if navigation item exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_navigation 
            WHERE id = ? AND website_id = ? AND authority_id = ?
        ");
        $stmt->execute([$navItemId, $websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Navigation item not found or access denied']);
            return;
        }

        // Prevent circular references - can't set parent to itself or its children
        if (isset($navigationData['parent_id']) && $navigationData['parent_id'] !== null) {
            $parentId = (int)$navigationData['parent_id'];

            if ($parentId === $navItemId) {
                http_response_code(400);
                echo json_encode(['error' => 'A navigation item cannot be its own parent']);
                return;
            }

            // Check if parent is a child of the current item (which would create a loop)
            $stmt = $pdo->prepare("
                WITH RECURSIVE nav_tree AS (
                    SELECT id, parent_id FROM kdd_website_navigation WHERE id = ?
                    UNION ALL
                    SELECT n.id, n.parent_id FROM kdd_website_navigation n
                    JOIN nav_tree t ON n.parent_id = t.id
                )
                SELECT COUNT(*) FROM nav_tree WHERE id = ?
            ");
            $stmt->execute([$navItemId, $parentId]);

            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot set a child item as parent (circular reference)']);
                return;
            }
        }

        // Update navigation item
        $stmt = $pdo->prepare("
            UPDATE kdd_website_navigation SET
            title = ?,
            url = ?,
            page_id = ?,
            parent_id = ?,
            sort_order = ?,
            target = ?,
            is_active = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([
            $navigationData['title'],
            $navigationData['url'] ?? '',
            $navigationData['page_id'] ?? null,
            $navigationData['parent_id'] ?? null,
            $navigationData['sort_order'] ?? 0,
            $navigationData['target'] ?? '_self',
            isset($navigationData['is_active']) ? ($navigationData['is_active'] ? 1 : 0) : 1,
            $navItemId,
            $authorityId
        ]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_navigation', $navItemId, $userId, [
            [
                'column_name' => 'update',
                'old_value' => null,
                'new_value' => 'Navigation item updated'
            ]
        ]);

        // Get the updated navigation item
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_navigation WHERE id = ?");
        $stmt->execute([$navItemId]);
        $navigationItem = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'navigationItem' => $navigationItem
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateNavigationItem: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a navigation item
 */
function deleteNavigationItem(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $navigationItemId = isset($inputData['navigationItemId']) ? (int)$inputData['navigationItemId'] : 0;

        if (!$navigationItemId) {
            http_response_code(400);
            echo json_encode(['error' => 'Navigation item ID is required']);
            return;
        }

        // Check if navigation item exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_navigation 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$navigationItemId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Navigation item not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Check if this item has child items
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM kdd_website_navigation 
            WHERE parent_id = ?
        ");
        $stmt->execute([$navigationItemId]);

        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Cannot delete navigation item with child items. Please delete or reassign child items first.'
            ]);
            return;
        }

        // Delete the navigation item
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_navigation 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$navigationItemId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_website_navigation', $navigationItemId, $userId, [
            [
                'column_name' => 'deletion',
                'old_value' => 'Navigation item',
                'new_value' => null
            ]
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Navigation item deleted successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in deleteNavigationItem: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update navigation item sort order (for drag and drop functionality)
 */
function updateNavigationOrder(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $navigationItemId = isset($inputData['navigationItemId']) ? (int)$inputData['navigationItemId'] : 0;
        $sortOrder = isset($inputData['sortOrder']) ? (float)$inputData['sortOrder'] : null;
        $parentId = isset($inputData['parentId']) ? (int)$inputData['parentId'] : null;

        if ($parentId === 0) {
            $parentId = null; // Convert 0 to NULL for root items
        }

        if (!$navigationItemId || $sortOrder === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Navigation item ID and sort order are required']);
            return;
        }

        // Check if navigation item exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_navigation 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$navigationItemId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Navigation item not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Before updating, verify the new parent (if provided) to prevent circular references
        if ($parentId !== null) {
            // Can't set parent to itself
            if ($parentId === $navigationItemId) {
                http_response_code(400);
                echo json_encode(['error' => 'A navigation item cannot be its own parent']);
                return;
            }

            // Check if parent belongs to same website and authority
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_website_navigation 
                WHERE id = ? AND website_id = ? AND authority_id = ?
            ");
            $stmt->execute([$parentId, $websiteId, $authorityId]);

            if (!$stmt->fetchColumn()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid parent item specified']);
                return;
            }

            // Check for circular references
            $stmt = $pdo->prepare("
                WITH RECURSIVE nav_tree AS (
                    SELECT id, parent_id FROM kdd_website_navigation WHERE id = ?
                    UNION ALL
                    SELECT n.id, n.parent_id FROM kdd_website_navigation n
                    JOIN nav_tree t ON n.parent_id = t.id
                )
                SELECT COUNT(*) FROM nav_tree WHERE id = ?
            ");
            $stmt->execute([$navigationItemId, $parentId]);

            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot set a child item as parent (circular reference)']);
                return;
            }
        }

        // Update the navigation item order and parent
        $stmt = $pdo->prepare("
            UPDATE kdd_website_navigation SET
            sort_order = ?,
            parent_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sortOrder, $parentId, $navigationItemId, $authorityId]);

        // Log the change
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_website_navigation', $navigationItemId, $userId, [
            [
                'column_name' => 'sort_order',
                'old_value' => null,
                'new_value' => (string)$sortOrder
            ],
            [
                'column_name' => 'parent_id',
                'old_value' => null,
                'new_value' => $parentId !== null ? (string)$parentId : 'NULL'
            ]
        ]);

        // Get all navigation items for this website to return updated state
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_website_navigation
            WHERE website_id = ? AND authority_id = ?
            ORDER BY parent_id, sort_order
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $navigation = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'navigation' => $navigation,
            'message' => 'Navigation order updated successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateNavigationOrder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update post sort order (for drag and drop functionality)
 */
function updatePostOrder(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $postId = isset($inputData['postId']) ? (int)$inputData['postId'] : 0;
        $sortOrder = isset($inputData['sortOrder']) ? (float)$inputData['sortOrder'] : null;

        if (!$postId || $sortOrder === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID and sort order are required']);
            return;
        }

        // Check if post exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_posts 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$postId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Post not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Update the post's sort order
        $stmt = $pdo->prepare("
            UPDATE kdd_website_posts 
            SET sort_order = ?, updated_at = NOW() 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sortOrder, $postId, $authorityId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Post order update failed']);
            return;
        }

        // Return updated posts list for the website
        $stmt = $pdo->prepare("
            SELECT id, title, sort_order, status, created_at, updated_at
            FROM kdd_website_posts 
            WHERE website_id = ? AND authority_id = ?
            ORDER BY sort_order ASC, created_at DESC
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'message' => 'Post order updated successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in updatePostOrder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update category sort order (for drag and drop functionality)
 */
function updateCategoryOrder(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $categoryId = isset($inputData['categoryId']) ? (int)$inputData['categoryId'] : 0;
        $sortOrder = isset($inputData['sortOrder']) ? (float)$inputData['sortOrder'] : null;

        if (!$categoryId || $sortOrder === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID and sort order are required']);
            return;
        }

        // Check if category exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_categories 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$categoryId, $authorityId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found or access denied']);
            return;
        }

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Update the category's sort order
        $stmt = $pdo->prepare("
            UPDATE kdd_website_categories 
            SET sort_order = ?, updated_at = NOW() 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$sortOrder, $categoryId, $authorityId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Category order update failed']);
            return;
        }

        // Return updated categories list for the website
        $stmt = $pdo->prepare("
            SELECT id, name, sort_order, created_at, updated_at
            FROM kdd_website_categories 
            WHERE website_id = ? AND authority_id = ?
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute([$websiteId, $authorityId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'message' => 'Category order updated successfully'
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateCategoryOrder: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save a navigation item (create or update)
 */
function saveNavigationItem(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();

        if (!isset($inputData['website_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        $websiteId = (int)$inputData['website_id'];

        // Check if user has access to the website
        if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Extract navigationData if it exists (frontend sends data in this format)
        if (isset($inputData['navigationData'])) {
            $navData = $inputData['navigationData'];
        } else {
            $navData = $inputData;
        }

        // Determine if this is an update or create
        $isUpdate = isset($navData['id']) && $navData['id'] > 0;
        $navItemId = $isUpdate ? (int)$navData['id'] : null;

        if ($isUpdate) {
            // Check if navigation item exists and belongs to this authority
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_website_navigation 
                WHERE id = ? AND website_id = ? AND authority_id = ?
            ");
            $stmt->execute([$navItemId, $websiteId, $authorityId]);

            if (!$stmt->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Navigation item not found or access denied']);
                return;
            }

            // Prevent circular references for parent_id
            if (isset($navData['parent_id']) && $navData['parent_id'] !== null) {
                $parentId = (int)$navData['parent_id'];

                if ($parentId === $navItemId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'A navigation item cannot be its own parent']);
                    return;
                }

                // Check for circular references
                $stmt = $pdo->prepare("
                    WITH RECURSIVE nav_tree AS (
                        SELECT id, parent_id FROM kdd_website_navigation WHERE id = ?
                        UNION ALL
                        SELECT n.id, n.parent_id FROM kdd_website_navigation n
                        JOIN nav_tree t ON n.parent_id = t.id
                    )
                    SELECT COUNT(*) FROM nav_tree WHERE id = ?
                ");
                $stmt->execute([$navItemId, $parentId]);

                if ($stmt->fetchColumn() > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Cannot set a child item as parent (circular reference)']);
                    return;
                }
            }
        } else {
            // For new items, determine the next sort order if not provided
            if (!isset($navData['sort_order'])) {
                $stmt = $pdo->prepare(
                    "
                    SELECT COALESCE(MAX(sort_order), 0) + 1
                    FROM kdd_website_navigation
                    WHERE website_id = ?
                    AND parent_id " . (isset($navData['parent_id']) ? "= ?" : "IS NULL")
                );

                if (isset($navData['parent_id'])) {
                    $stmt->execute([$websiteId, $navData['parent_id']]);
                } else {
                    $stmt->execute([$websiteId]);
                }

                $navData['sort_order'] = $stmt->fetchColumn();
            }
        }

        // Process blog categories if this is a blog navigation item
        $isBlog = isset($navData['is_blog']) ? (bool)$navData['is_blog'] : false;
        $blogCategories = null;

        if ($isBlog && isset($navData['blog_categories'])) {
            // If it's already a JSON string, keep it as is, otherwise encode it
            if (is_array($navData['blog_categories'])) {
                $blogCategories = json_encode($navData['blog_categories']);
            } else {
                $blogCategories = $navData['blog_categories'];
            }
        }

        if ($isUpdate) {
            // Update existing item
            $stmt = $pdo->prepare("
                UPDATE kdd_website_navigation SET
                title = ?,
                url = ?,
                page_id = ?,
                parent_id = ?,
                sort_order = ?,
                target = ?,
                is_active = ?,
                is_blog = ?,
                blog_categories = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $navData['title'],
                $navData['url'] ?? '',
                $navData['page_id'] ?? null,
                $navData['parent_id'] ?? null,
                $navData['sort_order'] ?? 0,
                $navData['target'] ?? '_self',
                isset($navData['is_active']) ? ($navData['is_active'] ? 1 : 0) : 1,
                $isBlog ? 1 : 0,
                $blogCategories,
                $navItemId,
                $authorityId
            ]);

            $message = 'Navigation item updated successfully';
            $logAction = 'UPDATE';
        } else {
            // Create new item
            $stmt = $pdo->prepare("
                INSERT INTO kdd_website_navigation (
                    website_id, title, url, page_id,
                    parent_id, sort_order, target,
                    is_active, is_blog, blog_categories, authority_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId,
                $navData['title'],
                $navData['url'] ?? '',
                $navData['page_id'] ?? null,
                $navData['parent_id'] ?? null,
                $navData['sort_order'] ?? 0,
                $navData['target'] ?? '_self',
                isset($navData['is_active']) ? ($navData['is_active'] ? 1 : 0) : 1,
                $isBlog ? 1 : 0,
                $blogCategories,
                $authorityId
            ]);

            $navItemId = $pdo->lastInsertId();
            $message = 'Navigation item created successfully';
            $logAction = 'INSERT';
        }

        // Log the change
        logDatabaseChange($authorityId, $pdo, $logAction, 'kdd_website_navigation', $navItemId, $userId, [
            [
                'column_name' => 'action',
                'old_value' => $isUpdate ? 'Navigation item before update' : null,
                'new_value' => $message
            ]
        ]);

        // Get the updated/created navigation item
        $stmt = $pdo->prepare("SELECT * FROM kdd_website_navigation WHERE id = ?");
        $stmt->execute([$navItemId]);
        $navigationItem = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'navigationItem' => $navigationItem,
            'message' => $message
        ]);
    } catch (PDOException $e) {
        error_log("Error in saveNavigationItem: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a contact form submission
 */
function deleteContact(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        // Support both camelCase and snake_case
        $contactId = isset($inputData['contactId']) ? (int)$inputData['contactId'] :
                     (isset($inputData['contact_id']) ? (int)$inputData['contact_id'] : 0);

        if (!$contactId) {
            http_response_code(400);
            echo json_encode(['error' => 'Contact submission ID is required']);
            return;
        }

        // Get the website ID for this contact submission
        $stmt = $pdo->prepare("
            SELECT website_id FROM kdd_website_contact 
            WHERE id = ?
        ");
        $stmt->execute([$contactId]);
        $websiteId = $stmt->fetchColumn();

        if (!$websiteId) {
            http_response_code(404);
            echo json_encode(['error' => 'Contact submission not found']);
            return;
        }

        // Check if website belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this website']);
            return;
        }

        // Delete the contact submission
        $stmt = $pdo->prepare("
            DELETE FROM kdd_website_contact 
            WHERE id = ?
        ");
        $result = $stmt->execute([$contactId]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact submission deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete contact submission']);
        }
    } catch (PDOException $e) {
        error_log("Error in deleteContact: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Upload multiple media files at once
 */
function uploadMediaBulk(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_POST, 'website_id', FILTER_VALIDATE_INT);
        $mediaType = filter_input(INPUT_POST, 'mediaType', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'image';

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config 
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        // Check if files were uploaded
        if (!isset($_FILES['files']) || !is_array($_FILES['files']['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No files uploaded or invalid format']);
            return;
        }

        $fileCount = count($_FILES['files']['name']);

        // Limit to 20 files per request
        $maxFiles = 20;
        if ($fileCount > $maxFiles) {
            http_response_code(400);
            echo json_encode(['error' => "Too many files. Maximum $maxFiles files allowed per upload."]);
            return;
        }

        // Create directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/website/' . $authorityId . '/' . $websiteId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedFiles = [];
        $errors = [];

        // Allowed file types
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'text/plain',
            'video/mp4',
            'video/webm',
            'video/ogg',
            'audio/mpeg',
            'audio/wav',
            'audio/ogg'
        ];

        // Maximum file size (10MB)
        $maxSize = 10 * 1024 * 1024;

        // Process each file
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "File {$_FILES['files']['name'][$i]}: " . getUploadErrorMessage($_FILES['files']['error'][$i]);
                continue;
            }

            $originalName = $_FILES['files']['name'][$i];
            $tempPath = $_FILES['files']['tmp_name'][$i];
            $fileType = $_FILES['files']['type'][$i];
            $fileSize = $_FILES['files']['size'][$i];

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "File $originalName: Invalid file type";
                continue;
            }

            // Validate file size
            if ($fileSize > $maxSize) {
                $errors[] = "File $originalName: File is too large (max 10MB)";
                continue;
            }

            // Generate unique filename
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
            $uploadPath = $uploadDir . '/' . $filename;
            $relativePath = 'uploads/website/' . $authorityId . '/' . $websiteId . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($tempPath, $uploadPath)) {
                $errors[] = "File $originalName: Failed to move uploaded file";
                continue;
            }

            // Determine media type enum
            $mediaTypeEnum = 'image'; // Default
            if (strpos($fileType, 'video/') === 0) {
                $mediaTypeEnum = 'video';
            } elseif (strpos($fileType, 'audio/') === 0) {
                $mediaTypeEnum = 'audio';
            } elseif (strpos($fileType, 'application/') === 0 || $fileType === 'text/plain') {
                $mediaTypeEnum = 'document';
            }

            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO kdd_website_media (
                    website_id, file_name, file_path, file_type, file_size,
                    media_type, authority_id, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $websiteId,
                $filename,
                $relativePath,
                $fileType,
                $fileSize,
                $mediaTypeEnum,
                $authorityId
            ]);

            $mediaId = $pdo->lastInsertId();

            // Add to successful uploads
            $uploadedFiles[] = [
                'id' => $mediaId,
                'file_name' => $filename,
                'file_path' => $relativePath,
                'file_type' => $fileType,
                'file_size' => $fileSize
            ];
        }

        // Return results
        echo json_encode([
            'success' => count($uploadedFiles) > 0,
            'file_names' => array_column($uploadedFiles, 'file_name'),
            'files' => $uploadedFiles,
            'errors' => $errors,
            'total_uploaded' => count($uploadedFiles),
            'total_failed' => count($errors)
        ]);
    } catch (PDOException $e) {
        error_log("Error in uploadMediaBulk: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error in uploadMediaBulk: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}

/**
 * Get page details
 */
function getPageDetails(PDO $pdo, string $authority, int $authorityId): void
{
    try {
        $inputData = getInputData();
        $pageId = isset($inputData['pageId']) ? (int)$inputData['pageId'] : 0;
        global $userId, $is_public_action;

        if (!$pageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID is required']);
            return;
        }

        // Get page details
        $stmt = $pdo->prepare("
            SELECT p.*, w.authority_id
            FROM kdd_website_pages p
            JOIN kdd_website_config w ON p.website_id = w.id
            WHERE p.id = ? AND p.is_published = 1
        ");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$page) {
            http_response_code(404);
            echo json_encode(['error' => 'Page not found or not published']);
            return;
        }

        // For non-public access, check permissions
        if (!$is_public_action) {
            $websiteId = $page['website_id'];
            
            if (!hasWebsiteAccess($pdo, $userId, $websiteId, $authorityId)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied to this website']);
                return;
            }
        }

        echo json_encode([
            'success' => true,
            'page' => $page
        ]);
    } catch (PDOException $e) {
        error_log("Error in getPageDetails: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get all publicly accessible websites for the website browser
 */
function getPublicWebsites(PDO $pdo): void
{
    try {
        // Get all active websites across all authorities
        $stmt = $pdo->prepare("
            SELECT 
                wc.id, 
                wc.site_name, 
                wc.site_description, 
                wc.logo,
                wc.site_name as company_name
            FROM 
                kdd_website_config wc
            WHERE 
                wc.is_active = 1 
                AND wc.maintenance_mode = 0
            ORDER BY 
                wc.site_name ASC
        ");
        $stmt->execute();
        $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        foreach ($websites as &$website) {
            // Convert numeric values to proper types
            $website['id'] = (int)$website['id'];
            
            // Set a default description if empty
            if (empty($website['site_description'])) {
                $website['site_description'] = 'Website of ' . ($website['company_name'] ?: 'Unknown Company');
            }
        }

        echo json_encode([
            'success' => true,
            'websites' => $websites
        ]);
    } catch (PDOException $e) {
        error_log("Error in getPublicWebsites: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error while retrieving websites',
            'websites' => []
        ]);
    }
}
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
    /*
       Startinhalt fuer den Einseiter.

       Bewusst kurz und erkennbar vorlaeufig. Vorher stand hier ausformulierter
       Werbetext ("Wir sind ein engagiertes Team ... erstklassige Loesungen") -
       der liest sich fertig und bleibt deshalb stehen. Ein halber Satz mit
       klarer Aufforderung wird ersetzt.

       Angeredet wird wie im uebrigen Website-Bereich: der Besucher mit "ihr",
       der Bearbeiter mit "du".
    */
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kdd_website_sections WHERE website_id = ?");
        $stmt->execute([$websiteId]);
        if ((int)$stmt->fetchColumn() > 0) {
            // Schon etwas da - nichts ueberschreiben.
            return false;
        }

        $stmt = $pdo->prepare("SELECT site_name, site_slogan, site_description FROM kdd_website_config WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $siteName = $website['site_name'] ?? 'Unsere Website';
        $siteSlogan = $website['site_slogan'] ?? '';
        $siteDescription = $website['site_description'] ?? '';

        if ($template !== 'onepager') {
            return false;
        }

        $defaultSections = [
            [
                'section_type' => 'hero',
                'title' => $siteName,
                'subtitle' => $siteSlogan !== '' ? $siteSlogan : 'Hier steht euer Slogan.',
                'content' => $siteDescription ?: 'Ein oder zwei Sätze darüber, wer ihr seid. Diesen Text kannst du hier ersetzen.',
                'settings' => json_encode([
                    'backgroundImage' => null,
                    'buttonText' => 'Mehr erfahren',
                    'buttonLink' => '#about',
                ]),
                'sort_order' => 0,
                'is_active' => 1,
            ],
            [
                'section_type' => 'about',
                'title' => 'Über uns',
                'subtitle' => 'Wer wir sind',
                'content' => '<p>Erzählt hier, wofür ihr steht und was euch ausmacht. Zwei Absätze reichen.</p>',
                'settings' => json_encode([]),
                'sort_order' => 1,
                'is_active' => 1,
            ],
            [
                'section_type' => 'services',
                'title' => 'Unsere Leistungen',
                'subtitle' => 'Was wir anbieten',
                'content' => null,
                'settings' => json_encode([
                    'items' => [
                        ['icon' => 'mdi mdi-cog', 'title' => 'Leistung 1', 'description' => 'Ein Satz dazu, was ihr hier anbietet.'],
                        ['icon' => 'mdi mdi-rocket', 'title' => 'Leistung 2', 'description' => 'Ein Satz dazu, was ihr hier anbietet.'],
                        ['icon' => 'mdi mdi-shield-check', 'title' => 'Leistung 3', 'description' => 'Ein Satz dazu, was ihr hier anbietet.'],
                    ],
                ]),
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'section_type' => 'contact',
                'title' => 'Kontakt',
                'subtitle' => 'So erreicht ihr uns',
                'content' => '<p>Schreibt uns – wir melden uns zurück.</p>',
                'settings' => json_encode([]),
                'sort_order' => 3,
                'is_active' => 1,
            ],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_sections (
                website_id, section_type, title, subtitle, content,
                settings, sort_order, is_active, authority_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($defaultSections as $section) {
            $stmt->execute([
                $websiteId,
                $section['section_type'],
                $section['title'],
                $section['subtitle'] ?? null,
                $section['content'],
                $section['settings'],
                $section['sort_order'],
                $section['is_active'],
                $authorityId,
            ]);
        }

        // Das Protokoll ist Beiwerk - scheitert es, steht der Startinhalt trotzdem.
        try {
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_website_sections', 0, $userId, [
                ['column_name' => 'action', 'old_value' => null, 'new_value' => "Startabschnitte fuer Vorlage: $template"],
            ]);
        } catch (\Throwable $e) {
            error_log('Startabschnitte nicht protokolliert: ' . $e->getMessage());
        }

        return true;
    } catch (\Throwable $e) {
        error_log('Startabschnitte fuer Website ' . $websiteId . ' fehlgeschlagen: '
            . get_class($e) . ' ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
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
            $inputData['document_id'] ?? null,
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
            $inputData['document_id'] ?? null,
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
