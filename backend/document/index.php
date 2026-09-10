<?php
/**
 * Backend Endpoint: document/index.php
 * Handles CRUD operations for Documents and Categories, grouped by site/authority.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority / Site Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$tokenAuthority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null; // Authority ID from user's token
$roleId = $decoded_jwt->role_id ?? null; // Role ID from user's token

if ($userId === null || !is_int($userId) || $tokenAuthority === null || !is_string($tokenAuthority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'document')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to document features."]); exit();
}

// Get and Validate site parameter (crucial for determining context and permissions)
// First check if site is provided in request parameters
$site = isset($_REQUEST['site']) ? (string)strtolower($_REQUEST['site'] ?? '') : '';

// If not found in request parameters, try to get it from JSON body
if (empty($site)) {
    $jsonBody = file_get_contents('php://input');
    $jsonData = json_decode($jsonBody, true);
    
    // First check if area_id is in the JSON - this takes precedence
    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['area_id'])) {
        // If area_id is in the JSON, we'll use 'area' as the site
        $site = 'area';
        $area_id = (int)$jsonData['area_id'];
        $area = (string)$area_id;
    } 
    // Then check for site if area_id wasn't found
    else if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['site'])) {
        // Make sure to cast to string before using strtolower
        $site = (string)strtolower($jsonData['site']);
    }
}

// Check if area is provided as an alternative to site (new approach)
$area = isset($_REQUEST['area']) ? (string)$_REQUEST['area'] : '';
if (empty($area) && isset($jsonBody)) {
    $jsonData = isset($jsonData) ? $jsonData : json_decode($jsonBody, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['area'])) {
        $area = (string)$jsonData['area'];
    }
}

// If site is provided but area is not, treat site as area for new area-based documents
if (empty($area) && !empty($site) && $site !== 'global') {
    $area = $site;
}

// Check if area_id is provided in the request
if (isset($_REQUEST['area_id']) && empty($area_id)) {
    // Ensure it's properly cast as int for database operations
    $area_id = (int)$_REQUEST['area_id'];
    // But also store as string for string operations
    $area = (string)$area_id;
} else if (empty($area) && empty($area_id) && isset($jsonBody) && empty($site)) {
    $jsonData = isset($jsonData) ? $jsonData : json_decode($jsonBody, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['area_id'])) {
        // Also check JSON data for area_id
        $area_id = (int)$jsonData['area_id'];
        $area = (string)$area_id;
    }
}

// If $area is set but $area_id is not, convert to area_id
if (empty($area_id) && !empty($area)) {
    if (is_numeric($area)) {
        // Numeric area - use directly
        $area_id = (int)$area;
    } else {
        // String area key - lookup area_id from database
        try {
            $stmt = $pdo->prepare("SELECT id FROM kdd_doc_areas WHERE `key` = ? AND (authority_id = ? OR is_system = 1) LIMIT 1");
            $stmt->execute([$area, $authorityId]);
            $foundAreaId = $stmt->fetchColumn();
            if ($foundAreaId) {
                $area_id = (int)$foundAreaId;
            }
        } catch (\PDOException $e) {
            error_log("Error looking up area_id for key '$area': " . $e->getMessage());
        }
    }
}

// If using area_id, set a dummy site value for permission checks
// This ensures that when we use strtoupper($site) later, it won't throw an error
if (empty($site) && !empty($area)) {
    $site = 'area'; // Using 'area' as a fallback site value when using area_id
}

// Liste der Aktionen, die keinen site-Parameter benötigen
$site_independent_actions = [
    'getAreas', 'addArea', 'updateArea', 'deleteArea', 
    'getAreaPermissions', 'saveAreaPermissions', 'getAreaByKey',
    // Füge Aktionen hinzu, die jetzt mit area_id arbeiten können
    'addCategorie', 'updateCategoryName', 'deleteCategorie', 'deleteDocument',
    'saveDocumentSorting', 'saveCategorySorting', 'addDocument', 'updateDocument'
];

$allowedSites = ['document', 'global']; // Define allowed site contexts

// Hole die Aktion aus dem Request oder JSON-Body
$action = $_REQUEST['action'] ?? '';
if (empty($action)) {
    $jsonBody = isset($jsonBody) ? $jsonBody : file_get_contents('php://input');
    $jsonData = isset($jsonData) ? $jsonData : json_decode($jsonBody, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['action'])) {
        $action = $jsonData['action'];
    }
}



// Determine effective authority for database operations
$effectiveAuthority = ($site === 'global') ? 'global' : $tokenAuthority;
// Get effective authority ID
$effectiveAuthorityId = $authorityId;
if ($site === 'global') {
    // Für globale Inhalte verwenden wir -1 als Marker
    $effectiveAuthorityId = -1;
    
    // Versuchen wir optional die echte globale Authority-ID zu bekommen, aber schlagen nicht fehl
    try {
        $globalAuthorityId = getAuthorityId($pdo, 'global');
        if ($globalAuthorityId) {
            $effectiveAuthorityId = $globalAuthorityId;
        }
    } catch (\PDOException $e) {
        error_log("Could not determine global authority ID: " . $e->getMessage());
        // Weiter mit dem Marker -1
    }
}

// Security Check: Ensure the effective authority is allowed (e.g., user can't operate on 'fire' if their token says 'police', unless site is 'global')
if (!empty($site) && $site !== 'global' && $site !== $tokenAuthority && $tokenAuthority !== 'global') {
     // Allow if site matches token authority OR if token authority is 'global' (super admin?)
     // Or adjust this logic based on your rules. Example: Allow access only if site matches token authority.
     // if ($site !== $tokenAuthority) {
     //    http_response_code(403); echo json_encode(['error' => "Access to site '{$site}' denied for user authority '{$tokenAuthority}'."]); exit();
     // }
}

// --- Authorization & Action Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to base permission structure (site suffix will determine sub-module)
$permissions_map = [
    'getCategoriesAndDocuments' => ['base' => 'document', 'action' => 'read'],
    // Baustein "Zuletzt bearbeitete Dokumente". Fester Modul-Name, weil der
    // Baustein ueber alle Bereiche hinweg liest und keinen Bereich kennt.
    'getRecent'               => ['module' => 'document', 'action' => 'read'],
    'updateCategoryName'      => ['base' => 'document', 'action' => 'write'],
    'saveCategorySorting'     => ['base' => 'document', 'action' => 'write'],
    'addCategorie'            => ['base' => 'document', 'action' => 'write'],
    'deleteCategorie'         => ['base' => 'document', 'action' => 'delete'],
    'addDocument'             => ['base' => 'document', 'action' => 'write'],
    'updateDocument'          => ['base' => 'document', 'action' => 'write'],
    'deleteDocument'          => ['base' => 'document', 'action' => 'delete'],
    'saveDocumentSorting'     => ['base' => 'document', 'action' => 'write'],
    // Neue Aktionen für Dokumentenbereiche (no site suffix needed)
    'getAreas'               => ['module' => 'document', 'action' => 'read'], // Use 'document' not 'document.area' - areas are part of document feature
    'addArea'                => ['module' => 'document.areas', 'action' => 'admin'],
    'updateArea'             => ['module' => 'document.areas', 'action' => 'admin'],
    'deleteArea'             => ['module' => 'document.areas', 'action' => 'admin'],
    'getAreaPermissions'     => ['module' => 'document.areas', 'action' => 'admin'],
    'saveAreaPermissions'    => ['module' => 'document.areas', 'action' => 'admin'],
    'getAreaByKey'           => ['module' => 'document', 'action' => 'read'], // Use 'document' not 'document.area'
];

$permission_config = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($permission_config === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Determine module name - append site for document actions, use direct module for area actions
if (isset($permission_config['base'])) {
    // Dynamic module based on site (e.g., document.training, document.administration)
    $site_lowercase = strtolower((string)$site);
    $module = $permission_config['base'] . ($site !== 'global' && $site !== 'document' && !empty($site) ? '.' . $site_lowercase : '');
    $actionType = $permission_config['action'];
} else {
    // Fixed module (e.g., document.area)
    $module = $permission_config['module'];
    $actionType = $permission_config['action'];
}

// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
}
// NEW: Check area-based permissions if area_id is set (new system)
elseif (!empty($area_id) && is_int($area_id) && isset($permission_config['base'])) {
    // Use area-based permissions from kdd_doc_area_permissions table
    $areaPermissions = getUserAreaPermissions($pdo, $authorityId, $area_id, $roleId);

    // Map action to required permission type
    if ($actionType === 'read') {
        $has_permission = $areaPermissions['can_read'];
    } elseif ($actionType === 'write') {
        $has_permission = $areaPermissions['can_write'];
    } elseif ($actionType === 'delete') {
        $has_permission = $areaPermissions['can_delete'];
    }
}
// Check module-based permissions (works with both old and new tokens)
elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true;
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');
    
    switch ($action) {
        case 'getCategoriesAndDocuments': 
            // Allow both GET and POST for this action
            if ($request_method === 'GET' || $request_method === 'POST') {
                getCategoriesAndDocuments($pdo, $effectiveAuthority, $site, $effectiveAuthorityId);
            } else {
                MethodNotAllowed();
            }
            break;
        case 'updateCategoryName':      if ($is_post_request) updateCategoryName($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'saveCategorySorting':     if ($is_post_request) saveCategorySorting($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'addCategorie':            if ($is_post_request) addCategorie($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'deleteCategorie':         if ($is_post_request) deleteCategorie($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'addDocument':             if ($is_post_request) addDocument($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'updateDocument':          if ($is_post_request) updateDocument($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteDocument':          if ($is_post_request) deleteDocument($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break; // Consider DELETE
        case 'saveDocumentSorting':     if ($is_post_request) saveDocumentSorting($pdo, $userId, $effectiveAuthority, $site, $effectiveAuthorityId); else MethodNotAllowed(); break;
        
        // Dokumentenbereiche Aktionen
        case 'getAreas':               if ($is_post_request || $request_method === 'GET') getAreas($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'addArea':                if ($is_post_request) addArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateArea':             if ($is_post_request) updateArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteArea':             if ($is_post_request) deleteArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getAreaPermissions':     if ($is_post_request || $request_method === 'GET') getAreaPermissions($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'saveAreaPermissions':    if ($is_post_request) saveAreaPermissions($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getAreaByKey':     if ($is_post_request) getAreaByKey($pdo, $authorityId, $roleId); else MethodNotAllowed(); break;

        // Dashboard Widget Endpoint
        case 'getRecent':        if ($request_method === 'GET') getRecent($pdo, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action '" . htmlspecialchars($action) . "' on site '" . htmlspecialchars($site) . "'. Required: " . htmlspecialchars($required_permission ?? 'N/A')]);
}

exit();



// Helper function to get authority ID
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority ID: " . $e->getMessage());
        return null;
    }
}



// --- Function Implementations (PDO Refactored) ---

function getCategoriesAndDocuments(PDO $pdo, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    global $area; // Access the global area parameter
    
    try {
        // Check if we're using the new document area approach
        if (!empty($area)) {
            // Fetch categories by area
            getCategoriesByArea($pdo, $effectiveAuthorityId, $area);
            return;
        }
        
        // Traditional approach - fetch by site
        // 1. Fetch categories for the specified site
        $sqlCategories = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlCategories = "SELECT id, parent_id, name, description, sort_order
                            FROM kdd_doc_categories
                            WHERE site = ?
                            ORDER BY sort_order ASC";
            $params = [$site];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlCategories = "SELECT id, parent_id, name, description, sort_order
                            FROM kdd_doc_categories
                            WHERE site = ? AND authority_id = ? 
                            ORDER BY sort_order ASC";
            $params = [$site, $effectiveAuthorityId];
        }
        
        $stmtCats = $pdo->prepare($sqlCategories);
        $stmtCats->execute($params);
        $categoriesResult = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        if (empty($categoriesResult)) {
            http_response_code(200); 
            echo json_encode([]); 
            return; // No categories found for this site
        }

        // 2. Fetch documents belonging to these categories
        $categoryIds = array_column($categoriesResult, 'id');
        $placeholders = rtrim(str_repeat('?,', count($categoryIds)), ',');

        $sqlDocuments = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlDocuments = "SELECT id, category_id, title, content, sort_order, updated_at, updated_at_user, notes, creator, created_at, view_type, spreadsheet_data, default_view
                            FROM kdd_doc_documents
                            WHERE category_id IN ($placeholders) AND is_deleted = 0
                            ORDER BY sort_order ASC";
            $params = $categoryIds;
        } else {
            // Normale Filterung mit Authority-ID
            $sqlDocuments = "SELECT id, category_id, title, content, sort_order, updated_at, updated_at_user, notes, creator, created_at, view_type, spreadsheet_data, default_view
                            FROM kdd_doc_documents
                            WHERE category_id IN ($placeholders) AND authority_id = ? AND is_deleted = 0
                            ORDER BY sort_order ASC";
            $params = array_merge($categoryIds, [$effectiveAuthorityId]);
        }
        
        $stmtDocs = $pdo->prepare($sqlDocuments);
        $stmtDocs->execute($params);
        $documentsResult = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        // Decode spreadsheet_data JSON strings to arrays/objects before sending to client
        foreach ($documentsResult as &$doc) {
            if (!empty($doc['spreadsheet_data'])) {
                $decoded = json_decode($doc['spreadsheet_data'], true);
                // Only use decoded data if it's valid JSON, otherwise keep as is
                if (json_last_error() === JSON_ERROR_NONE) {
                    $doc['spreadsheet_data'] = $decoded;
                }
            }
        }
        unset($doc); // Break reference

        // 3. Build a structured response with nested categories and documents
        $response = buildCategoryHierarchy($categoriesResult, $documentsResult);
        
        http_response_code(200);
        echo json_encode($response);
    } catch (PDOException $e) {
        error_log("DB error in getCategoriesAndDocuments ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching documents and categories."]);
    }
}

// New function to handle document areas
function getCategoriesByArea(PDO $pdo, int $authorityId, $areaKey): void {
    try {
        $areaId = null;
        
        // Check if $areaKey is already a numeric ID or a string key
        if (is_numeric($areaKey)) {
            // It's already an ID
            $areaId = (int)$areaKey;
            
            // Verify the area exists and belongs to this authority
            $stmtVerify = $pdo->prepare("SELECT id FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerify->execute([$areaId, $authorityId]);
            
            if (!$stmtVerify->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found.']);
                return;
            }
        } else {
            // It's a string key, lookup the ID
            $stmtArea = $pdo->prepare("SELECT id FROM kdd_doc_areas WHERE `key` = ? AND (authority_id = ? OR is_system = 1)");
            $stmtArea->execute([$areaKey, $authorityId]);
            $areaId = $stmtArea->fetchColumn();
            
            if (!$areaId) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found.']);
                return;
            }
        }
        
        // Fetch categories for this area
        $sqlCategories = "SELECT id, parent_id, name, description, sort_order
                        FROM kdd_doc_categories
                        WHERE area_id = ?
                        ORDER BY sort_order ASC";
        $stmtCats = $pdo->prepare($sqlCategories);
        $stmtCats->execute([$areaId]);
        $categoriesResult = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($categoriesResult)) {
            http_response_code(200); 
            echo json_encode([]); 
            return; // No categories found for this area
        }
        
        // Fetch documents belonging to these categories
        $categoryIds = array_column($categoriesResult, 'id');
        $placeholders = rtrim(str_repeat('?,', count($categoryIds)), ',');
        
        $sqlDocuments = "SELECT id, category_id, title, content, sort_order, updated_at, updated_at_user, notes, creator, created_at, view_type, spreadsheet_data, default_view
                        FROM kdd_doc_documents
                        WHERE category_id IN ($placeholders) AND is_deleted = 0
                        ORDER BY sort_order ASC";
        
        $stmtDocs = $pdo->prepare($sqlDocuments);
        $stmtDocs->execute($categoryIds);
        $documentsResult = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        // Decode spreadsheet_data JSON strings to arrays/objects before sending to client
        foreach ($documentsResult as &$doc) {
            if (!empty($doc['spreadsheet_data'])) {
                $decoded = json_decode($doc['spreadsheet_data'], true);
                // Only use decoded data if it's valid JSON, otherwise keep as is
                if (json_last_error() === JSON_ERROR_NONE) {
                    $doc['spreadsheet_data'] = $decoded;
                }
            }
        }
        unset($doc); // Break reference

        // Build the response with nested categories and documents
        $response = buildCategoryHierarchy($categoriesResult, $documentsResult);
        
        http_response_code(200);
        echo json_encode($response);
    } catch (PDOException $e) {
        error_log("DB error in getCategoriesByArea (area: {$areaKey}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching documents and categories by area."]);
    }
}

// Helper function to build hierarchical category structure
function buildCategoryHierarchy(array $categories, array $documents): array {
    // Index categories by ID for quick lookup
    $categoryMap = [];
    foreach ($categories as $category) {
        $category['documents'] = []; // Initialize documents array
        $category['children'] = []; // Initialize children categories array
        $categoryMap[$category['id']] = $category;
    }
    
    // Add documents to their parent categories
    foreach ($documents as $document) {
        $categoryId = $document['category_id'];
        if (isset($categoryMap[$categoryId])) {
            $categoryMap[$categoryId]['documents'][] = $document;
        }
    }
    
    // Build the tree structure (parent -> children)
    $rootCategories = [];
    foreach ($categoryMap as $id => $category) {
        if (empty($category['parent_id'])) {
            // This is a root category
            $rootCategories[] = &$categoryMap[$id];
        } else {
            // This is a child category
            if (isset($categoryMap[$category['parent_id']])) {
                $categoryMap[$category['parent_id']]['children'][] = &$categoryMap[$id];
            }
        }
    }
    
    return $rootCategories;
}

function updateCategoryName(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $categoryId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $newName = trim($data['name'] ?? '');
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$categoryId || $categoryId <= 0 || empty($newName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid category ID and name are required.']);
        return;
    }
    
    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    if ($isAreaBased) {
        // Verify the area exists and belongs to this authority
        try {
            $stmtVerifyArea = $pdo->prepare("SELECT id, `key` FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerifyArea->execute([$areaId, $effectiveAuthorityId]);
            $areaInfo = $stmtVerifyArea->fetch(PDO::FETCH_ASSOC);
            
            if (!$areaInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found or access denied.']);
                return;
            }
            
            // Use the area key as the site for categories
            $site = $areaInfo['key'];
        } catch (PDOException $e) {
            error_log("DB error in updateCategoryName (verifying area): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying document area.']);
            return;
        }
    }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Zuerst versuchen wir, die Authority-ID der Kategorie direkt zu ermitteln
                $stmtGetCatAuthority = $pdo->prepare("SELECT authority_id FROM kdd_doc_categories WHERE id = ?");
                $stmtGetCatAuthority->execute([$categoryId]);
                $catAuthorityId = $stmtGetCatAuthority->fetchColumn();
                
                if ($catAuthorityId) {
                    $realAuthorityId = $catAuthorityId;
                } else {
                    // Versuche, die globale Authority-ID zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                    $stmt->execute();
                    $globalAuthId = $stmt->fetchColumn();
                    if ($globalAuthId) {
                        $realAuthorityId = $globalAuthId;
                    } else {
                        // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                    }
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }
        
        // Verify the category exists and belongs to this authority/site combination
        $sqlVerify = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlVerify = "SELECT name FROM kdd_doc_categories WHERE id = ? AND site = ?";
            $params = [$categoryId, $site];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlVerify = "SELECT name FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
            $params = [$categoryId, $site, $realAuthorityId];
        }
        
        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute($params);
        $existingCategory = $stmtVerify->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingCategory) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found for this site/authority.']);
            return;
        }
        
        // Update category name
        $sqlUpdate = "";
        $updateParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlUpdate = "UPDATE kdd_doc_categories SET name = ?, updated_at = NOW() WHERE id = ?";
            $updateParams = [$newName, $categoryId];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlUpdate = "UPDATE kdd_doc_categories SET name = ?, updated_at = NOW() WHERE id = ? AND authority_id = ?";
            $updateParams = [$newName, $categoryId, $realAuthorityId];
        }
        
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $success = $stmtUpdate->execute($updateParams);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Category name updated successfully.']);
            
            // Log change
            $changes = [
                ['column_name' => 'name', 'old_value' => $existingCategory['name'], 'new_value' => $newName]
            ];
            logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "doc_categories", $categoryId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category name.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in updateCategoryName ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while updating category name.']);
    }
}

function saveCategorySorting(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    global $area; // Access the global area parameter
    $data = getJsonRequestData();
    $categories = $data['categories'] ?? [];
    $areaId = $data['area_id'] ?? null;

    error_log("saveCategorySorting called - received categories: " . json_encode($categories) . ", area_id: " . ($areaId ?? 'null') . ", area: " . ($area ?? 'null') . ", site: $site");

    // Check if we're using area-based documents
    $isAreaBased = !empty($areaId) || !empty($area);
    $effectiveAreaId = $areaId ?? (is_numeric($area) ? (int)$area : null);

    error_log("saveCategorySorting - isAreaBased: " . ($isAreaBased ? 'true' : 'false') . ", effectiveAreaId: " . ($effectiveAreaId ?? 'null'));

    if (empty($categories) || !is_array($categories)) {
        http_response_code(400);
        echo json_encode(['error' => 'No categories provided or invalid format.']);
        return;
    }

    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Versuche, die globale Authority-ID zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                    $stmt->execute();
                    $anyAuthId = $stmt->fetchColumn();
                    $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }
        
        $pdo->beginTransaction();
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($categories as $category) {
            $categoryId = filter_var($category['id'] ?? null, FILTER_VALIDATE_INT);
            $sortOrder = filter_var($category['sort_order'] ?? null, FILTER_VALIDATE_INT);
            $parentId = isset($category['parent_id']) ? filter_var($category['parent_id'], FILTER_VALIDATE_INT) : null;

            error_log("Processing category - ID: $categoryId, sortOrder: $sortOrder (type: " . gettype($sortOrder) . "), parentId: " . ($parentId ?? 'null'));

            if (!$categoryId || $categoryId <= 0 || $sortOrder === false) {
                error_log("Skipping category - validation failed for ID: $categoryId");
                $failCount++;
                continue;
            }

            // Get old values for logging
            $sqlOld = "SELECT sort_order, parent_id FROM kdd_doc_categories WHERE id = ?";
            $stmtOld = $pdo->prepare($sqlOld);
            $stmtOld->execute([$categoryId]);
            $oldCategory = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $sqlUpdate = "";
            $updateParams = [];

            if ($isAreaBased) {
                // Area-based documents use area_id instead of site
                $sqlUpdate = "UPDATE kdd_doc_categories
                             SET sort_order = ?, parent_id = ?, updated_at = NOW()
                             WHERE id = ? AND area_id = ?";
                $updateParams = [$sortOrder, $parentId, $categoryId, $effectiveAreaId];
            } else if ($effectiveAuthorityId === -1) {
                // Für globale Inhalte keine Authority-ID-Filterung
                $sqlUpdate = "UPDATE kdd_doc_categories
                             SET sort_order = ?, parent_id = ?, updated_at = NOW()
                             WHERE id = ? AND site = ?";
                $updateParams = [$sortOrder, $parentId, $categoryId, $site];
            } else {
                // Normale Filterung mit Authority-ID
                $sqlUpdate = "UPDATE kdd_doc_categories
                             SET sort_order = ?, parent_id = ?, updated_at = NOW()
                             WHERE id = ? AND site = ? AND authority_id = ?";
                $updateParams = [$sortOrder, $parentId, $categoryId, $site, $realAuthorityId];
            }

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            error_log("Executing UPDATE - SQL: $sqlUpdate, Params: " . json_encode($updateParams));
            $success = $stmtUpdate->execute($updateParams);
            $rowsAffected = $stmtUpdate->rowCount();
            error_log("UPDATE result - Success: " . ($success ? 'true' : 'false') . ", Rows affected: $rowsAffected");

            if ($success && $rowsAffected > 0) {
                $successCount++;

                // Log changes
                if ($oldCategory) {
                    $changes = [];
                    if ($oldCategory['sort_order'] != $sortOrder) {
                        $changes[] = ['column_name' => 'sort_order', 'old_value' => $oldCategory['sort_order'], 'new_value' => $sortOrder];
                    }
                    if ($oldCategory['parent_id'] != $parentId) {
                        $changes[] = ['column_name' => 'parent_id', 'old_value' => $oldCategory['parent_id'], 'new_value' => $parentId];
                    }
                    if (!empty($changes)) {
                        logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "doc_categories", $categoryId, $requestingUserId, $changes);
                    }
                }
            } else {
                $failCount++;
                error_log("UPDATE failed or no rows affected for category ID: $categoryId");
            }
        }
        
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Categories sorting updated. Success: $successCount, Failed: $failCount"
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveCategorySorting ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while updating category sorting.']);
    }
}

function saveDocumentSorting(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    global $area; // Access the global area parameter
    $data = getJsonRequestData();
    $documents = $data['documents'] ?? [];
    $areaId = $data['area_id'] ?? null;

    error_log("saveDocumentSorting called - received documents: " . json_encode($documents) . ", area_id: " . ($areaId ?? 'null') . ", area: " . ($area ?? 'null'));

    // Check if we're using area-based documents
    $isAreaBased = !empty($areaId) || !empty($area);
    $effectiveAreaId = $areaId ?? (is_numeric($area) ? (int)$area : null);

    if (empty($documents) || !is_array($documents)) {
        http_response_code(400);
        echo json_encode(['error' => 'No documents provided or invalid format.']);
        return;
    }

    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Versuche, die globale Authority-ID zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                    $stmt->execute();
                    $anyAuthId = $stmt->fetchColumn();
                    $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }

        $pdo->beginTransaction();

        $successCount = 0;
        $failCount = 0;

        foreach ($documents as $document) {
            $documentId = filter_var($document['id'] ?? null, FILTER_VALIDATE_INT);
            $sortOrder = filter_var($document['sort_order'] ?? null, FILTER_VALIDATE_INT);

            error_log("Processing document - ID: $documentId, sortOrder: $sortOrder");

            if (!$documentId || $documentId <= 0 || $sortOrder === false) {
                error_log("Skipping document - validation failed for ID: $documentId");
                $failCount++;
                continue;
            }

            // Get old sort_order for logging
            $sqlOld = "SELECT sort_order FROM kdd_doc_documents WHERE id = ?";
            $stmtOld = $pdo->prepare($sqlOld);
            $stmtOld->execute([$documentId]);
            $oldSortOrder = $stmtOld->fetchColumn();

            $sqlUpdate = "";
            $updateParams = [];

            if ($isAreaBased) {
                // Area-based documents: just use ID (category links to area)
                $sqlUpdate = "UPDATE kdd_doc_documents
                             SET sort_order = ?, updated_at = NOW()
                             WHERE id = ?";
                $updateParams = [$sortOrder, $documentId];
            } else if ($effectiveAuthorityId === -1) {
                // Für globale Inhalte keine Authority-ID-Filterung
                $sqlUpdate = "UPDATE kdd_doc_documents
                             SET sort_order = ?, updated_at = NOW()
                             WHERE id = ?";
                $updateParams = [$sortOrder, $documentId];
            } else {
                // Normale Filterung mit Authority-ID
                $sqlUpdate = "UPDATE kdd_doc_documents
                             SET sort_order = ?, updated_at = NOW()
                             WHERE id = ? AND authority_id = ?";
                $updateParams = [$sortOrder, $documentId, $realAuthorityId];
            }

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            error_log("Executing UPDATE - SQL: $sqlUpdate, Params: " . json_encode($updateParams));
            $success = $stmtUpdate->execute($updateParams);
            $rowsAffected = $stmtUpdate->rowCount();
            error_log("UPDATE result - Success: " . ($success ? 'true' : 'false') . ", Rows affected: $rowsAffected");

            if ($success && $rowsAffected > 0) {
                $successCount++;

                // Log sort_order change
                if ($oldSortOrder != $sortOrder) {
                    $changes = [
                        ['column_name' => 'sort_order', 'old_value' => $oldSortOrder, 'new_value' => $sortOrder]
                    ];
                    logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "doc_documents", $documentId, $requestingUserId, $changes);
                }
            } else {
                $failCount++;
                error_log("UPDATE failed or no rows affected for document ID: $documentId");
            }
        }

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Documents sorting updated. Success: $successCount, Failed: $failCount"
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveDocumentSorting ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while updating document sorting.']);
    }
}

function addCategorie(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $parentId = filter_var($data['parent_id'] ?? null, FILTER_VALIDATE_INT);
    $sortOrder = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required.']);
        return;
    }
    
    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    if ($isAreaBased) {
        // Verify the area exists and belongs to this authority
        try {
            $stmtVerifyArea = $pdo->prepare("SELECT id, `key` FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerifyArea->execute([$areaId, $effectiveAuthorityId]);
            $areaInfo = $stmtVerifyArea->fetch(PDO::FETCH_ASSOC);
            
            if (!$areaInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found or access denied.']);
                return;
            }
            
            // Use the area key as the site for categories
            $site = $areaInfo['key'];
        } catch (PDOException $e) {
            error_log("DB error in addCategorie (verifying area): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying document area.']);
            return;
        }
    }
    
    // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
    $realAuthorityId = $effectiveAuthorityId;
    if ($effectiveAuthorityId === -1) {
        try {
            // Versuche, die globale Authority-ID zu bekommen
            $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
            $stmt->execute();
            $globalAuthId = $stmt->fetchColumn();
            if ($globalAuthId) {
                $realAuthorityId = $globalAuthId;
            } else {
                // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                $stmt->execute();
                $anyAuthId = $stmt->fetchColumn();
                $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
            }
        } catch (\PDOException $e) {
            // Wenn alles fehlschlägt, verwende 1
            $realAuthorityId = 1;
            error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
        }
    }
    
    // If parent ID is provided, verify it exists for this authority/site
    if ($parentId && $parentId > 0) {
        try {
            $sqlVerify = "";
            $params = [];
            
            if ($effectiveAuthorityId === -1) {
                // Für globale Inhalte keine Authority-ID-Filterung
                $sqlVerify = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ?";
                $params = [$parentId, $site];
            } else {
                // Normale Filterung mit Authority-ID
                $sqlVerify = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
                $params = [$parentId, $site, $realAuthorityId];
            }
            
            $stmtVerify = $pdo->prepare($sqlVerify);
            $stmtVerify->execute($params);
            
            if (!$stmtVerify->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Parent category not found.']);
                return;
            }
        } catch (PDOException $e) {
            error_log("DB error in addCategorie (verifying parent) ({$effectiveAuthority}/{$site}): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying parent category.']);
            return;
        }
    }
    
    try {
        $sql = "INSERT INTO kdd_doc_categories 
                (parent_id, name, description, sort_order, created_at, updated_at, site, authority_id, area_id) 
                VALUES (?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$parentId ?: null, $name, $description, $sortOrder, $site, $realAuthorityId, $isAreaBased ? $areaId : null]);
        
        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Category added successfully.',
                'id' => $newId
            ]);
            
            // Log the insertion
            $changes = [
                ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
                ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
                ['column_name' => 'parent_id', 'old_value' => null, 'new_value' => $parentId]
            ];
            logDatabaseChange($realAuthorityId, $pdo, 'INSERT', "doc_categories", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add category.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in addCategorie ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while adding category.']);
    }
}

function deleteCategorie(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $categoryId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$categoryId || $categoryId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid category ID is required.']);
        return;
    }
    
    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    if ($isAreaBased) {
        // Verify the area exists and belongs to this authority
        try {
            $stmtVerifyArea = $pdo->prepare("SELECT id, `key` FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerifyArea->execute([$areaId, $effectiveAuthorityId]);
            $areaInfo = $stmtVerifyArea->fetch(PDO::FETCH_ASSOC);
            
            if (!$areaInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found or access denied.']);
                return;
            }
            
            // Use the area key as the site for categories
            $site = $areaInfo['key'];
        } catch (PDOException $e) {
            error_log("DB error in deleteCategorie (verifying area): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying document area.']);
            return;
        }
    }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Zuerst versuchen wir, die Authority-ID der Kategorie direkt zu ermitteln
                $stmtGetCatAuthority = $pdo->prepare("SELECT authority_id FROM kdd_doc_categories WHERE id = ?");
                $stmtGetCatAuthority->execute([$categoryId]);
                $catAuthorityId = $stmtGetCatAuthority->fetchColumn();
                
                if ($catAuthorityId) {
                    $realAuthorityId = $catAuthorityId;
                } else {
                    // Versuche, die globale Authority-ID zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                    $stmt->execute();
                    $globalAuthId = $stmt->fetchColumn();
                    if ($globalAuthId) {
                        $realAuthorityId = $globalAuthId;
                    } else {
                        // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                    }
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }
        
        // Begin transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // 1. Verify the category exists and belongs to this authority/site
        $sqlVerify = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlVerify = "SELECT name FROM kdd_doc_categories WHERE id = ? AND site = ?";
            $params = [$categoryId, $site];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlVerify = "SELECT name FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
            $params = [$categoryId, $site, $realAuthorityId];
        }
        
        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute($params);
        $existingCategory = $stmtVerify->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingCategory) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Category not found for this site/authority.']);
            return;
        }
        
        // 2. Check if the category has child categories
        $sqlCheckChildren = "";
        $childParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlCheckChildren = "SELECT COUNT(*) FROM kdd_doc_categories WHERE parent_id = ?";
            $childParams = [$categoryId];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlCheckChildren = "SELECT COUNT(*) FROM kdd_doc_categories WHERE parent_id = ? AND authority_id = ?";
            $childParams = [$categoryId, $realAuthorityId];
        }
        
        $stmtCheckChildren = $pdo->prepare($sqlCheckChildren);
        $stmtCheckChildren->execute($childParams);
        $childCount = $stmtCheckChildren->fetchColumn();
        
        if ($childCount > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete category with child categories. Delete or move child categories first.']);
            return;
        }
        
        // 3. Mark documents in this category as deleted
        $sqlUpdateDocs = "";
        $updateParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlUpdateDocs = "UPDATE kdd_doc_documents SET is_deleted = 1, updated_at = NOW() WHERE category_id = ?";
            $updateParams = [$categoryId];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlUpdateDocs = "UPDATE kdd_doc_documents SET is_deleted = 1, updated_at = NOW() WHERE category_id = ? AND authority_id = ?";
            $updateParams = [$categoryId, $realAuthorityId];
        }
        
        $stmtUpdateDocs = $pdo->prepare($sqlUpdateDocs);
        $stmtUpdateDocs->execute($updateParams);
        
        // 4. Delete the category
        $sqlDelete = "";
        $deleteParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlDelete = "DELETE FROM kdd_doc_categories WHERE id = ? AND site = ?";
            $deleteParams = [$categoryId, $site];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlDelete = "DELETE FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
            $deleteParams = [$categoryId, $site, $realAuthorityId];
        }
        
        $stmtDelete = $pdo->prepare($sqlDelete);
        $success = $stmtDelete->execute($deleteParams);
        
        if ($success) {
            $pdo->commit();
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
            
            // Log deletion
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => '0', 'new_value' => '1']
            ];
            logDatabaseChange($realAuthorityId, $pdo, 'DELETE', "doc_categories", $categoryId, $requestingUserId, $changes);
        } else {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category.']);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteCategorie ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while deleting category.']);
    }
}

function addDocument(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $categoryId = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim($data['title'] ?? '');
    $content = $data['content'] ?? '';
    $sortOrder = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $notes = trim($data['notes'] ?? '');
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);

    // New fields for spreadsheet support
    $viewType = in_array($data['view_type'] ?? '', ['document', 'spreadsheet', 'both']) ? $data['view_type'] : 'document';

    // Handle spreadsheet_data - check if it's already a JSON string or needs encoding
    $spreadsheetData = null;
    if (isset($data['spreadsheet_data'])) {
        if (is_string($data['spreadsheet_data'])) {
            // Already a JSON string, validate and use it directly
            $decodedTest = json_decode($data['spreadsheet_data']);
            $spreadsheetData = (json_last_error() === JSON_ERROR_NONE) ? $data['spreadsheet_data'] : json_encode($data['spreadsheet_data']);
        } else {
            // It's an array/object, encode it to JSON
            $spreadsheetData = json_encode($data['spreadsheet_data']);
        }
    }

    $defaultView = in_array($data['default_view'] ?? '', ['document', 'spreadsheet']) ? $data['default_view'] : 'document';

    // Debug logging
    error_log("addDocument called - categoryId: $categoryId, title: $title, areaId: $areaId, effectiveAuthorityId: $effectiveAuthorityId, site: $site");

    if (!$categoryId || $categoryId <= 0 || empty($title)) {
        error_log("addDocument failed: Invalid categoryId or empty title");
        http_response_code(400);
        echo json_encode(['error' => 'Category ID and document title are required.']);
        return;
    }
    
    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    error_log("isAreaBased: " . ($isAreaBased ? 'true' : 'false'));

    try {
        // Verify the category exists and belongs to this authority/site/area
        $sqlVerify = "";
        $params = [];

        if ($isAreaBased) {
            // Check if category belongs to the specified area
            $sqlVerify = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND area_id = ?";
            $params = [$categoryId, $areaId];
            error_log("Using area-based verification: categoryId=$categoryId, areaId=$areaId");
        } elseif ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlVerify = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ?";
            $params = [$categoryId, $site];
            error_log("Using global verification: categoryId=$categoryId, site=$site");
        } else {
            // Normale Filterung mit Authority-ID
            $sqlVerify = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
            $params = [$categoryId, $site, $effectiveAuthorityId];
            error_log("Using authority-based verification: categoryId=$categoryId, site=$site, authorityId=$effectiveAuthorityId");
        }

        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute($params);
        $categoryExists = $stmtVerify->fetchColumn();

        error_log("Category verification result: " . ($categoryExists ? 'found' : 'NOT FOUND'));

        if (!$categoryExists) {
            error_log("addDocument failed: Category not found with query: $sqlVerify");
            http_response_code(404);
            echo json_encode(['error' => 'Category not found for this site/authority/area.']);
            return;
        }
        
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        error_log("Setting realAuthorityId, effectiveAuthorityId=$effectiveAuthorityId");

        if ($effectiveAuthorityId === -1) {
            error_log("effectiveAuthorityId is -1, looking for global authority");
            try {
                // Versuche, die globale Authority-ID zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                    $stmt->execute();
                    $anyAuthId = $stmt->fetchColumn();
                    $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }

        error_log("realAuthorityId determined: $realAuthorityId");

        // Get employee info
        $employeeInfo = getEmployeeInfoByUserId($pdo, $requestingUserId);
        $updatedByText = $employeeInfo
            ? $employeeInfo['servicenumber'] . ' ' . $employeeInfo['name']
            : "User ID $requestingUserId";

        // Insert the document
        $sql = "INSERT INTO kdd_doc_documents
                (category_id, title, content, sort_order, created_at, updated_at, updated_at_user, notes, creator, authority_id, view_type, spreadsheet_data, default_view)
                VALUES (?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $categoryId,
            $title,
            $content,
            $sortOrder,
            $updatedByText,
            $notes,
            $requestingUserId,
            $realAuthorityId,
            $viewType,
            $spreadsheetData,
            $defaultView
        ];

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Document added successfully.',
                'id' => $newId
            ]);

            // Log the insertion - verwende hier die reale Authority-ID für das Logging
            $changes = [
                ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
                ['column_name' => 'category_id', 'old_value' => null, 'new_value' => $categoryId]
            ];
            logDatabaseChange($realAuthorityId, $pdo, 'INSERT', "doc_documents", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add document.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in addDocument ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while adding document.']);
    }
}

function updateDocument(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $documentId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $categoryId = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim($data['title'] ?? '');
    $content = $data['content'] ?? '';
    $notes = trim($data['notes'] ?? '');
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);

    // New fields for spreadsheet support
    $viewType = isset($data['view_type']) && in_array($data['view_type'], ['document', 'spreadsheet', 'both']) ? $data['view_type'] : null;

    // Handle spreadsheet_data - check if it's already a JSON string or needs encoding
    $spreadsheetData = null;
    if (isset($data['spreadsheet_data'])) {
        if (is_string($data['spreadsheet_data'])) {
            // Already a JSON string, validate and use it directly
            $decodedTest = json_decode($data['spreadsheet_data']);
            $spreadsheetData = (json_last_error() === JSON_ERROR_NONE) ? $data['spreadsheet_data'] : json_encode($data['spreadsheet_data']);
        } else {
            // It's an array/object, encode it to JSON
            $spreadsheetData = json_encode($data['spreadsheet_data']);
        }
    }

    $defaultView = isset($data['default_view']) && in_array($data['default_view'], ['document', 'spreadsheet']) ? $data['default_view'] : null;
    
    if (!$documentId || $documentId <= 0 || empty($title)) {
        http_response_code(400);
        echo json_encode(['error' => 'Document ID and title are required.']);
        return;
    }

    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    if ($isAreaBased) {
        // Verify the area exists and belongs to this authority
        try {
            $stmtVerifyArea = $pdo->prepare("SELECT id, `key` FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerifyArea->execute([$areaId, $effectiveAuthorityId]);
            $areaInfo = $stmtVerifyArea->fetch(PDO::FETCH_ASSOC);
            
            if (!$areaInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found or access denied.']);
                return;
            }
            
            // Use the area key as the site for categories
            $site = $areaInfo['key'];
        } catch (PDOException $e) {
            error_log("DB error in updateDocument (verifying area): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying document area.']);
            return;
        }
    }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Zuerst versuchen wir, die Authority-ID des Dokuments direkt zu ermitteln
                $stmtGetDocAuthority = $pdo->prepare("SELECT authority_id FROM kdd_doc_documents WHERE id = ?");
                $stmtGetDocAuthority->execute([$documentId]);
                $docAuthorityId = $stmtGetDocAuthority->fetchColumn();
                
                if ($docAuthorityId) {
                    $realAuthorityId = $docAuthorityId;
                } else {
                    // Versuche, die globale Authority-ID zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                    $stmt->execute();
                    $globalAuthId = $stmt->fetchColumn();
                    if ($globalAuthId) {
                        $realAuthorityId = $globalAuthId;
                    } else {
                        // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                    }
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }
        
        // Verify the document exists and belongs to this authority
        $sqlVerify = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung, aber prüfen, ob es gelöscht wurde
            $sqlVerify = "SELECT d.*, c.site 
                         FROM kdd_doc_documents d
                         JOIN kdd_doc_categories c ON d.category_id = c.id
                         WHERE d.id = ? AND d.is_deleted = 0";
            $params = [$documentId];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlVerify = "SELECT d.*, c.site 
                         FROM kdd_doc_documents d
                         JOIN kdd_doc_categories c ON d.category_id = c.id
                         WHERE d.id = ? AND d.authority_id = ? AND d.is_deleted = 0";
            $params = [$documentId, $realAuthorityId];
        }
        
        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute($params);
        $existingDocument = $stmtVerify->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingDocument) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found or already deleted.']);
            return;
        }
        
        // Verify document belongs to a category with the correct site
        if ($existingDocument['site'] !== $site) {
            http_response_code(400);
            echo json_encode(['error' => 'Document does not belong to the specified site.']);
            return;
        }
        
        // If changing category, verify the new category exists and belongs to this site/authority
        if ($categoryId && $categoryId !== $existingDocument['category_id']) {
            $sqlVerifyCategory = "";
            $catParams = [];
            
            if ($effectiveAuthorityId === -1) {
                // Für globale Inhalte keine Authority-ID-Filterung
                $sqlVerifyCategory = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ?";
                $catParams = [$categoryId, $site];
            } else {
                // Normale Filterung mit Authority-ID
                $sqlVerifyCategory = "SELECT 1 FROM kdd_doc_categories WHERE id = ? AND site = ? AND authority_id = ?";
                $catParams = [$categoryId, $site, $realAuthorityId];
            }
            
            $stmtVerifyCategory = $pdo->prepare($sqlVerifyCategory);
            $stmtVerifyCategory->execute($catParams);
            
            if (!$stmtVerifyCategory->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'New category not found for this site/authority.']);
                return;
            }
        }
        
        // Get employee info
        $employeeInfo = getEmployeeInfoByUserId($pdo, $requestingUserId);
        $updatedByText = $employeeInfo 
            ? $employeeInfo['servicenumber'] . ' ' . $employeeInfo['name']
            : "User ID $requestingUserId";
        
        // Update the document
        $sql = "UPDATE kdd_doc_documents
                SET title = ?, content = ?, updated_at = NOW(),
                    updated_at_user = ?, notes = ?";

        $params = [$title, $content, $updatedByText, $notes];

        if ($categoryId && $categoryId !== $existingDocument['category_id']) {
            $sql .= ", category_id = ?";
            $params[] = $categoryId;
        }

        // Add new fields if provided
        if ($viewType !== null) {
            $sql .= ", view_type = ?";
            $params[] = $viewType;
        }
        if ($spreadsheetData !== null) {
            $sql .= ", spreadsheet_data = ?";
            $params[] = $spreadsheetData;
        }
        if ($defaultView !== null) {
            $sql .= ", default_view = ?";
            $params[] = $defaultView;
        }

        $sql .= " WHERE id = ?";
        $params[] = $documentId;
        
        // Bei nicht-globalen Inhalten nach Authority-ID filtern
        if ($effectiveAuthorityId !== -1) {
            $sql .= " AND authority_id = ?";
            $params[] = $realAuthorityId;
        }
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Document updated successfully.']);
            
            // Log changes
            $changes = [];
            if ($title !== $existingDocument['title']) {
                $changes[] = ['column_name' => 'title', 'old_value' => $existingDocument['title'], 'new_value' => $title];
            }
            if ($notes !== $existingDocument['notes']) {
                $changes[] = ['column_name' => 'notes', 'old_value' => $existingDocument['notes'], 'new_value' => $notes];
            }
            if ($categoryId && $categoryId !== $existingDocument['category_id']) {
                $changes[] = ['column_name' => 'category_id', 'old_value' => $existingDocument['category_id'], 'new_value' => $categoryId];
            }
            // Content changes may be too large to log in detail
            if ($content !== $existingDocument['content']) {
                $changes[] = ['column_name' => 'content', 'old_value' => 'Content updated', 'new_value' => 'Content updated'];
            }
            
            if (!empty($changes)) {
                logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "doc_documents", $documentId, $requestingUserId, $changes);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update document.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in updateDocument ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while updating document.']);
    }
}

function deleteDocument(PDO $pdo, int $requestingUserId, string $effectiveAuthority, string $site, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    $documentId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);

    error_log("deleteDocument called - documentId: $documentId, areaId: $areaId, site: $site, effectiveAuthorityId: $effectiveAuthorityId");

    if (!$documentId || $documentId <= 0) {
        error_log("deleteDocument failed: Invalid documentId");
        http_response_code(400);
        echo json_encode(['error' => 'Valid document ID is required.']);
        return;
    }

    // Check if using area-based approach
    $isAreaBased = !empty($areaId);
    error_log("deleteDocument isAreaBased: " . ($isAreaBased ? 'true' : 'false'));
    if ($isAreaBased) {
        // Verify the area exists and belongs to this authority
        try {
            $stmtVerifyArea = $pdo->prepare("SELECT id, `key` FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)");
            $stmtVerifyArea->execute([$areaId, $effectiveAuthorityId]);
            $areaInfo = $stmtVerifyArea->fetch(PDO::FETCH_ASSOC);
            
            if (!$areaInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Document area not found or access denied.']);
                return;
            }
            
            // Use the area key as the site for categories
            $site = $areaInfo['key'];
        } catch (PDOException $e) {
            error_log("DB error in deleteDocument (verifying area): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error while verifying document area.']);
            return;
        }
    }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Zuerst versuchen wir, die Authority-ID des Dokuments direkt zu ermitteln
                $stmtGetDocAuthority = $pdo->prepare("SELECT authority_id FROM kdd_doc_documents WHERE id = ?");
                $stmtGetDocAuthority->execute([$documentId]);
                $docAuthorityId = $stmtGetDocAuthority->fetchColumn();
                
                if ($docAuthorityId) {
                    $realAuthorityId = $docAuthorityId;
                } else {
                    // Versuche, die globale Authority-ID zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                    $stmt->execute();
                    $globalAuthId = $stmt->fetchColumn();
                    if ($globalAuthId) {
                        $realAuthorityId = $globalAuthId;
                    } else {
                        // Versuche, irgendeine Authority-ID als Fallback zu bekommen
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1; // Verwende 1 als letzten Ausweg
                    }
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global site: " . $e->getMessage());
            }
        }
        
        // Verify the document exists, belongs to the correct site/authority, and isn't already deleted
        $sqlVerify = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte keine Authority-ID-Filterung
            $sqlVerify = "SELECT d.*, c.site 
                        FROM kdd_doc_documents d
                        JOIN kdd_doc_categories c ON d.category_id = c.id
                        WHERE d.id = ? AND d.is_deleted = 0";
            $params = [$documentId];
        } else {
            // Normale Filterung mit Authority-ID
            $sqlVerify = "SELECT d.*, c.site
                        FROM kdd_doc_documents d
                        JOIN kdd_doc_categories c ON d.category_id = c.id
                        WHERE d.id = ? AND d.authority_id = ? AND d.is_deleted = 0";
            $params = [$documentId, $realAuthorityId];
        }

        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute($params);
        $existingDocument = $stmtVerify->fetch(PDO::FETCH_ASSOC);

        error_log("deleteDocument verification query: $sqlVerify");
        error_log("deleteDocument verification result: " . ($existingDocument ? 'found' : 'NOT FOUND'));

        if (!$existingDocument) {
            error_log("deleteDocument failed: Document not found or already deleted");
            http_response_code(404);
            echo json_encode(['error' => 'Document not found or already deleted.']);
            return;
        }

        error_log("deleteDocument existing site: " . ($existingDocument['site'] ?? 'NULL') . ", expected site: $site");

        // Verify document belongs to a category with the correct site (skip for area-based)
        if (!$isAreaBased && $existingDocument['site'] !== $site) {
            error_log("deleteDocument failed: Site mismatch - expected: $site, got: " . $existingDocument['site']);
            http_response_code(400);
            echo json_encode(['error' => 'Document does not belong to the specified site.']);
            return;
        }

        // Get employee info
        error_log("deleteDocument: Getting employee info for userId: $requestingUserId");
        $employeeInfo = getEmployeeInfoByUserId($pdo, $requestingUserId);
        error_log("deleteDocument: employeeInfo result: " . json_encode($employeeInfo));

        $updatedByText = $employeeInfo
            ? $employeeInfo['servicenumber'] . ' ' . $employeeInfo['name']
            : "User ID $requestingUserId";

        error_log("deleteDocument: updatedByText: $updatedByText");

        // Soft delete the document (mark as deleted rather than removing from database)
        error_log("deleteDocument: Preparing UPDATE statement");
        $sql = "UPDATE kdd_doc_documents
                SET is_deleted = 1, updated_at = NOW(), updated_at_user = ?
                WHERE id = ?";
        $params = [$updatedByText, $documentId];

        // Bei nicht-globalen Inhalten nach Authority-ID filtern
        if ($effectiveAuthorityId !== -1) {
            $sql .= " AND authority_id = ?";
            $params[] = $realAuthorityId;
        }

        error_log("deleteDocument: UPDATE params: " . json_encode($params));

        $stmt = $pdo->prepare($sql);
        error_log("deleteDocument: Executing UPDATE...");
        $success = $stmt->execute($params);

        error_log("deleteDocument: UPDATE result: " . ($success ? 'SUCCESS' : 'FAILED'));

        if ($success) {
            error_log("deleteDocument: Document deleted successfully");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully.']);
            
            // Log deletion
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => '0', 'new_value' => '1']
            ];
            logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "doc_documents", $documentId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete document.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in deleteDocument ({$effectiveAuthority}/{$site}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while deleting document.']);
    }
}

/**
 * Liste aller Dokumentenbereiche abrufen
 * Filtert die Bereiche bereits serverseitig nach Berechtigungen
 */
function getAreas(PDO $pdo, int $authorityId): void {
    global $userId, $userPermissions;
    require_once __DIR__ . '/../utils/permission_helper.php';
    $hasAllPermissions = hasAllPermissions($userPermissions);

    try {
        // Get all role IDs for this user in this authority
        $stmtRoles = $pdo->prepare("
            SELECT role_id FROM kdd_user_roles
            WHERE user_id = ? AND authority_id = ?
        ");
        $stmtRoles->execute([$userId, $authorityId]);
        $userRoles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userRoles) && !$hasAllPermissions) {
            // User has no roles and no ALL_PERMISSIONS -> no access to any areas
            http_response_code(200);
            echo json_encode([]);
            return;
        }

        // Get all areas with aggregated permissions across ALL user roles
        // Using MAX() to get the highest permission level across all roles
        if ($hasAllPermissions) {
            // User with ALL_PERMISSIONS sees all areas with full access
            $sql = "SELECT
                        da.id,
                        da.key,
                        da.name,
                        da.description,
                        da.icon,
                        da.is_system,
                        da.is_active,
                        da.sort_order,
                        (SELECT COUNT(*) FROM kdd_doc_categories WHERE area_id = da.id) as category_count,
                        (SELECT COUNT(*) FROM kdd_doc_documents d
                         JOIN kdd_doc_categories c ON d.category_id = c.id
                         WHERE c.area_id = da.id AND d.is_deleted = 0) as document_count,
                        1 as can_read,
                        1 as can_write,
                        1 as can_delete
                    FROM kdd_doc_areas da
                    WHERE (da.authority_id = ? OR da.is_system = 1) AND da.is_active = 1
                    ORDER BY da.sort_order ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId]);
        } else {
            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));

            $sql = "SELECT
                        da.id,
                        da.key,
                        da.name,
                        da.description,
                        da.icon,
                        da.is_system,
                        da.is_active,
                        da.sort_order,
                        (SELECT COUNT(*) FROM kdd_doc_categories WHERE area_id = da.id) as category_count,
                        (SELECT COUNT(*) FROM kdd_doc_documents d
                         JOIN kdd_doc_categories c ON d.category_id = c.id
                         WHERE c.area_id = da.id AND d.is_deleted = 0) as document_count,
                        COALESCE(MAX(dap.can_read), 0) as can_read,
                        COALESCE(MAX(dap.can_write), 0) as can_write,
                        COALESCE(MAX(dap.can_delete), 0) as can_delete
                    FROM kdd_doc_areas da
                    LEFT JOIN kdd_doc_area_permissions dap
                        ON da.id = dap.area_id
                        AND dap.role_id IN ($placeholders)
                    WHERE (da.authority_id = ? OR da.is_system = 1) AND da.is_active = 1
                    GROUP BY da.id
                    ORDER BY da.sort_order ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...$userRoles, $authorityId]);
        }

        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format permissions and filter areas
        $filteredAreas = [];
        foreach ($areas as $area) {
            $canRead = (bool)$area['can_read'];
            $canWrite = (bool)$area['can_write'];
            $canDelete = (bool)$area['can_delete'];

            // Only return areas where user has at least read permission
            if ($canRead) {
                $area['permissions'] = [
                    'can_read' => $canRead,
                    'can_write' => $canWrite,
                    'can_delete' => $canDelete
                ];

                // Remove the individual permission columns
                unset($area['can_read'], $area['can_write'], $area['can_delete']);

                $filteredAreas[] = $area;
            }
        }

        http_response_code(200);
        echo json_encode($filteredAreas);
    } catch (PDOException $e) {
        error_log("DB error in getAreas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Abrufen der Dokumentenbereiche."]);
    }
}

/**
 * Neuen Dokumentenbereich hinzufügen
 */
function addArea(PDO $pdo, int $requestingUserId, int $authorityId): void {
    $data = getJsonRequestData();
    $key = trim($data['key'] ?? '');
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $icon = trim($data['icon'] ?? 'mdi-file-document-outline');
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;
    $sortOrder = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    
    if (empty($key) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Schlüssel und Name sind erforderlich.']);
        return;
    }
    
    // Prüfen, ob der Schlüssel bereits existiert
    try {
        $sqlCheck = "SELECT 1 FROM kdd_doc_areas WHERE `key` = ? AND authority_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$key, $authorityId]);
        
        if ($stmtCheck->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['error' => 'Ein Bereich mit diesem Schlüssel existiert bereits.']);
            return;
        }
        
        // Neuen Bereich einfügen
        $sql = "INSERT INTO kdd_doc_areas 
                (`key`, name, description, icon, is_system, is_active, sort_order, authority_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$key, $name, $description, $icon, $isActive, $sortOrder, $authorityId]);
        
        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Dokumentenbereich erfolgreich erstellt.',
                'id' => $newId
            ]);
            
            // Änderung loggen
            $changes = [
                ['column_name' => 'key', 'old_value' => null, 'new_value' => $key],
                ['column_name' => 'name', 'old_value' => null, 'new_value' => $name]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', "doc_areas", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Erstellen des Dokumentenbereichs.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in addArea: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler beim Erstellen des Dokumentenbereichs.']);
    }
}

/**
 * Dokumentenbereich aktualisieren
 */
function updateArea(PDO $pdo, int $requestingUserId, int $authorityId): void {
    $data = getJsonRequestData();
    $areaId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $icon = trim($data['icon'] ?? '');
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;
    $sortOrder = isset($data['sort_order']) ? filter_var($data['sort_order'], FILTER_VALIDATE_INT) : null;
    
    if (!$areaId || $areaId <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID und Name sind erforderlich.']);
        return;
    }
    
    try {
        // Prüfen, ob der Bereich existiert und der Behörde gehört
        $sqlCheck = "SELECT * FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$areaId, $authorityId]);
        $existingArea = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingArea) {
            http_response_code(404);
            echo json_encode(['error' => 'Dokumentenbereich nicht gefunden.']);
            return;
        }
        
        // System-Bereiche haben Einschränkungen
        if ($existingArea['is_system']) {
            // Bei System-Bereichen darf der Key nicht geändert werden
            $key = $existingArea['key'];
        } else {
            // Bei normalen Bereichen kann der Key geändert werden, wenn im Request vorhanden
            $key = trim($data['key'] ?? $existingArea['key']);
            
            // Wenn der Key geändert wurde, prüfen, ob er bereits existiert
            if ($key !== $existingArea['key']) {
                $sqlCheckKey = "SELECT 1 FROM kdd_doc_areas WHERE `key` = ? AND authority_id = ? AND id != ?";
                $stmtCheckKey = $pdo->prepare($sqlCheckKey);
                $stmtCheckKey->execute([$key, $authorityId, $areaId]);
                
                if ($stmtCheckKey->fetchColumn()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ein Bereich mit diesem Schlüssel existiert bereits.']);
                    return;
                }
            }
        }
        
        // Bereich aktualisieren
        $sql = "UPDATE kdd_doc_areas SET 
                `key` = ?, 
                name = ?, 
                description = ?, 
                icon = ?,
                updated_at = NOW()";
        
        $params = [$key, $name, $description, $icon];
        
        // Nur hinzufügen, wenn im Request vorhanden
        if ($isActive !== null) {
            $sql .= ", is_active = ?";
            $params[] = $isActive;
        }
        
        if ($sortOrder !== null) {
            $sql .= ", sort_order = ?";
            $params[] = $sortOrder;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $areaId;
        
        // Bei System-Bereichen keine Behörden-ID-Prüfung
        if (!$existingArea['is_system']) {
            $sql .= " AND authority_id = ?";
            $params[] = $authorityId;
        }
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Dokumentenbereich erfolgreich aktualisiert.'
            ]);
            
            // Änderungen loggen
            $changes = [];
            if ($name !== $existingArea['name']) {
                $changes[] = ['column_name' => 'name', 'old_value' => $existingArea['name'], 'new_value' => $name];
            }
            if ($key !== $existingArea['key']) {
                $changes[] = ['column_name' => 'key', 'old_value' => $existingArea['key'], 'new_value' => $key];
            }
            if ($description !== $existingArea['description']) {
                $changes[] = ['column_name' => 'description', 'old_value' => $existingArea['description'], 'new_value' => $description];
            }
            if ($icon !== $existingArea['icon']) {
                $changes[] = ['column_name' => 'icon', 'old_value' => $existingArea['icon'], 'new_value' => $icon];
            }
            if ($isActive !== null && $isActive != $existingArea['is_active']) {
                $changes[] = ['column_name' => 'is_active', 'old_value' => $existingArea['is_active'] ? 'true' : 'false', 'new_value' => $isActive ? 'true' : 'false'];
            }
            
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "doc_areas", $areaId, $requestingUserId, $changes);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Aktualisieren des Dokumentenbereichs.']);
        }
    } catch (PDOException $e) {
        error_log("DB error in updateArea: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler beim Aktualisieren des Dokumentenbereichs.']);
    }
}

/**
 * Dokumentenbereich löschen
 */
function deleteArea(PDO $pdo, int $requestingUserId, int $authorityId): void {
    $data = getJsonRequestData();
    $areaId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$areaId || $areaId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Bereichs-ID ist erforderlich.']);
        return;
    }
    
    try {
        // Transaktion starten
        $pdo->beginTransaction();
        
        // Prüfen, ob der Bereich existiert, der Behörde gehört und kein System-Bereich ist
        $sqlCheck = "SELECT * FROM kdd_doc_areas WHERE id = ? AND authority_id = ? AND is_system = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$areaId, $authorityId]);
        $existingArea = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingArea) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Dokumentenbereich nicht gefunden oder ist ein System-Bereich.']);
            return;
        }
        
        // Prüfen, ob Kategorien vorhanden sind
        $sqlCheckCategories = "SELECT COUNT(*) FROM kdd_doc_categories WHERE area_id = ?";
        $stmtCheckCategories = $pdo->prepare($sqlCheckCategories);
        $stmtCheckCategories->execute([$areaId]);
        $categoryCount = $stmtCheckCategories->fetchColumn();
        
        if ($categoryCount > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Der Bereich enthält noch Kategorien. Bitte zuerst alle Kategorien löschen.']);
            return;
        }
        
        // Bereichsberechtigungen löschen
        $sqlDeletePermissions = "DELETE FROM kdd_doc_area_permissions WHERE area_id = ?";
        $stmtDeletePermissions = $pdo->prepare($sqlDeletePermissions);
        $stmtDeletePermissions->execute([$areaId]);
        
        // Bereich löschen
        $sqlDelete = "DELETE FROM kdd_doc_areas WHERE id = ? AND authority_id = ? AND is_system = 0";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $success = $stmtDelete->execute([$areaId, $authorityId]);
        
        if ($success) {
            $pdo->commit();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Dokumentenbereich erfolgreich gelöscht.'
            ]);
            
            // Löschung loggen
            logDatabaseChange($authorityId, $pdo, 'DELETE', "doc_areas", $areaId, $requestingUserId, [
                ['column_name' => 'deleted', 'old_value' => 'false', 'new_value' => 'true']
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Löschen des Dokumentenbereichs.']);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteArea: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler beim Löschen des Dokumentenbereichs.']);
    }
}

/**
 * Berechtigungen für einen Bereich abrufen
 */
function getAreaPermissions(PDO $pdo, int $authorityId): void {
    $areaId = filter_var($_REQUEST['area_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$areaId) {
        // Versuche, aus JSON-Daten zu lesen
        $data = getJsonRequestData();
        $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);
    }
    
    if (!$areaId || $areaId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Bereichs-ID ist erforderlich.']);
        return;
    }
    
    try {
        // Prüfen, ob der Bereich existiert und der Behörde gehört
        $sqlCheckArea = "SELECT 1 FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)";
        $stmtCheckArea = $pdo->prepare($sqlCheckArea);
        $stmtCheckArea->execute([$areaId, $authorityId]);
        
        if (!$stmtCheckArea->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Dokumentenbereich nicht gefunden.']);
            return;
        }
        
        // Rollen abrufen
        $sqlRoles = "SELECT id, name, description 
                    FROM kdd_roles 
                    WHERE authority_id = ? 
                    ORDER BY name ASC";
        $stmtRoles = $pdo->prepare($sqlRoles);
        $stmtRoles->execute([$authorityId]);
        $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
        
        // Berechtigungen pro Rolle abrufen
        foreach ($roles as &$role) {
            $permissions = getUserAreaPermissions($pdo, $authorityId, $areaId, $role['id']);
            $role['permissions'] = $permissions;
        }
        
        http_response_code(200);
        echo json_encode([
            'roles' => $roles
        ]);
    } catch (PDOException $e) {
        error_log("DB error in getAreaPermissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler beim Abrufen der Bereichsberechtigungen.']);
    }
}

/**
 * Berechtigungen für einen Bereich speichern
 */
function saveAreaPermissions(PDO $pdo, int $requestingUserId, int $authorityId): void {
    $data = getJsonRequestData();
    $areaId = filter_var($data['area_id'] ?? null, FILTER_VALIDATE_INT);
    $rolePermissions = $data['permissions'] ?? [];
    
    if (!$areaId || $areaId <= 0 || empty($rolePermissions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bereichs-ID und Berechtigungen sind erforderlich.']);
        return;
    }
    
    try {
        // Prüfen, ob der Bereich existiert und der Behörde gehört
        $sqlCheckArea = "SELECT 1 FROM kdd_doc_areas WHERE id = ? AND (authority_id = ? OR is_system = 1)";
        $stmtCheckArea = $pdo->prepare($sqlCheckArea);
        $stmtCheckArea->execute([$areaId, $authorityId]);
        
        if (!$stmtCheckArea->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Dokumentenbereich nicht gefunden.']);
            return;
        }
        
        // Transaktion starten
        $pdo->beginTransaction();
        
        // Alle bisherigen Berechtigungen für diesen Bereich löschen
        $sqlDelete = "DELETE FROM kdd_doc_area_permissions WHERE area_id = ?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$areaId]);
        
        // Neue Berechtigungen einfügen
        $sqlInsert = "INSERT INTO kdd_doc_area_permissions (area_id, role_id, can_read, can_write, can_delete) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($rolePermissions as $rolePermission) {
            $roleId = filter_var($rolePermission['role_id'] ?? null, FILTER_VALIDATE_INT);
            // Convert to int (0 or 1) for MySQL
            $canRead = isset($rolePermission['can_read']) ? (int)(bool)$rolePermission['can_read'] : 0;
            $canWrite = isset($rolePermission['can_write']) ? (int)(bool)$rolePermission['can_write'] : 0;
            $canDelete = isset($rolePermission['can_delete']) ? (int)(bool)$rolePermission['can_delete'] : 0;
            
            if (!$roleId || $roleId <= 0) {
                $failCount++;
                continue;
            }
            
            // Prüfen, ob die Rolle zur Behörde gehört
            $sqlCheckRole = "SELECT 1 FROM kdd_roles WHERE id = ? AND authority_id = ?";
            $stmtCheckRole = $pdo->prepare($sqlCheckRole);
            $stmtCheckRole->execute([$roleId, $authorityId]);
            
            if (!$stmtCheckRole->fetchColumn()) {
                $failCount++;
                continue;
            }
            
            $success = $stmtInsert->execute([$areaId, $roleId, $canRead, $canWrite, $canDelete]);
            
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Berechtigungen gespeichert. Erfolg: $successCount, Fehlgeschlagen: $failCount"
        ]);
        
        // Änderung loggen
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "doc_area_permissions", $areaId, $requestingUserId, [
            ['column_name' => 'permissions', 'old_value' => 'Previous permissions', 'new_value' => 'Updated permissions']
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveAreaPermissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler beim Speichern der Bereichsberechtigungen.']);
    }
}

/**
 * Hilfsfunktion: Berechtigungen eines Nutzers oder einer Rolle für einen Bereich abrufen
 */
function getUserAreaPermissions(PDO $pdo, int $authorityId, int $areaId, ?int $roleId = null): array {
    try {
        $sql = "";
        $params = [];
        
        if ($roleId) {
            // Berechtigungen für eine bestimmte Rolle
            $sql = "SELECT can_read, can_write, can_delete 
                    FROM kdd_doc_area_permissions 
                    WHERE area_id = ? AND role_id = ?";
            $params = [$areaId, $roleId];
        } else {
            // Globale Berechtigungen für alle Rollen der Behörde anzeigen
            // Bei der Anzeige im Menü nützlich, um zu wissen, welche Bereiche verfügbar sind
            $sql = "SELECT 
                    MAX(can_read) as can_read,
                    MAX(can_write) as can_write, 
                    MAX(can_delete) as can_delete
                    FROM kdd_doc_area_permissions 
                    WHERE area_id = ?";
            $params = [$areaId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'can_read' => (bool)$result['can_read'],
                'can_write' => (bool)$result['can_write'],
                'can_delete' => (bool)$result['can_delete']
            ];
        } else {
            // Wenn keine Berechtigungen gefunden wurden
            return [
                'can_read' => false,
                'can_write' => false,
                'can_delete' => false
            ];
        }
    } catch (PDOException $e) {
        error_log("DB error in getUserAreaPermissions: " . $e->getMessage());
        return [
            'can_read' => false,
            'can_write' => false,
            'can_delete' => false
        ];
    }
}

// Funktion zum Abrufen eines Dokumentenbereichs anhand des Schlüssels
function getAreaByKey(PDO $pdo, int $authorityId, int $roleId = null) {
    // Hole den Key-Parameter aus der Anfrage
    $jsonData = json_decode(file_get_contents('php://input'), true);
    $key = $jsonData['key'] ?? '';
    
    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing key parameter.']);
        exit;
    }
    
    try {
        // Modified to check both authority-specific areas and system areas
        $stmt = $pdo->prepare("SELECT * FROM kdd_doc_areas WHERE `key` = ? AND (authority_id = ? OR is_system = 1)");
        $stmt->execute([$key, $authorityId]);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$area) {
            http_response_code(404);
            echo json_encode(['error' => 'Document area not found.']);
            exit;
        }
        
        // Berechtigungen für den Benutzer für diesen Bereich abrufen
        $permissions = getUserAreaPermissions($pdo, $authorityId, $area['id'], $roleId);
        $area['permissions'] = $permissions;
        
        echo json_encode($area);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching document area: ' . $e->getMessage()]);
    }
}

// Get employee info function - returns employee info or username as fallback
function getEmployeeInfoByUserId(PDO $pdo, int $userId): ?array {
    try {
        // First try to get employee info
        $sql = "SELECT e.name, e.servicenumber
                FROM kdd_users u
                JOIN kdd_employee e ON u.linked_employee = e.id
                WHERE u.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result;
        }

        // If no employee linked, use username instead
        $sqlUser = "SELECT username FROM kdd_users WHERE id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$userId]);
        $username = $stmtUser->fetchColumn();

        if ($username) {
            // Return in same format as employee info for consistency
            return [
                'servicenumber' => '',
                'name' => $username
            ];
        }

        return null;
    } catch (PDOException $e) {
        error_log("Error getting employee info: " . $e->getMessage());
        return null;
    }
}
?>
/**
 * Get recent documents for dashboard widget
 * Returns: List of recently modified documents (limited to 5)
 */
function getRecent(PDO $pdo, int $authorityId): void {
    try {
        $sql = "SELECT
                    d.id,
                    d.title as name,
                    c.name as category,
                    d.updated_at as created_at,
                    d.content
                FROM kdd_doc_documents d
                LEFT JOIN kdd_doc_categories c ON d.category_id = c.id
                WHERE d.authority_id = ?
                AND d.is_deleted = 0
                ORDER BY d.updated_at DESC
                LIMIT 5";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add a simple URL for each document (adjust based on your routing)
        foreach ($documents as &$doc) {
            $doc['url'] = "/document/" . $doc['id'];
            $doc['type'] = $doc['category'] ?? 'Document';
            /*
               Nach Zeichen kuerzen, nicht nach Bytes. substr() schneidet
               mitten in ein mehrteiliges Zeichen - die Dokumenttitel tragen
               Emoji und Umlaute - und was dabei herauskommt, ist kein
               gueltiges UTF-8 mehr. json_encode() liefert dafuer false, und
               der Baustein bekommt eine leere Antwort.
            */
            if (isset($doc['content']) && mb_strlen($doc['content'], 'UTF-8') > 100) {
                $doc['content'] = mb_substr($doc['content'], 0, 100, 'UTF-8') . '...';
            }
        }
        unset($doc);

        // Ein einzelnes ungueltiges Byte darf nicht die ganze Antwort kosten.
        $json = json_encode($documents, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            error_log("json_encode failed in getRecent: " . json_last_error_msg());
            http_response_code(500);
            echo json_encode(["error" => "Could not encode recent documents."]);
            return;
        }

        http_response_code(200);
        echo $json;
    } catch (\Throwable $e) {
        /*
           Throwable statt PDOException: der Baustein antwortete mit einem
           leeren 500 - Status gesetzt, kein Text, nichts im Protokoll. Genau
           das passiert, wenn hier etwas anderes als ein Datenbankfehler
           auftritt und niemand ihn faengt.
        */
        error_log("Error in getRecent: " . get_class($e) . ' ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve recent documents."]);
    }
}
