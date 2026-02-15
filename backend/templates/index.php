<?php
/**
 * Backend Endpoint: templates/index.php
 * Handles CRUD operations for Templates and their Categories.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange and helpers

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'template')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to templates features."]); exit();
}
// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getTemplateCategories' => ['module' => 'template', 'action' => 'read'],
    'getTemplates'          => ['module' => 'template', 'action' => 'read'],
    'getTemplateFields'     => ['module' => 'template', 'action' => 'read'],
    'getFields'             => ['module' => 'template', 'action' => 'read'],
    'addTemplate'           => ['module' => 'template', 'action' => 'write'],
    'addCategorie'          => ['module' => 'template', 'action' => 'write'],
    'saveCategorySorting'   => ['module' => 'template', 'action' => 'write'],
    'updateCategoryName'    => ['module' => 'template', 'action' => 'write'],
    'updateTemplate'        => ['module' => 'template', 'action' => 'write'],
    'saveTemplateSorting'   => ['module' => 'template', 'action' => 'write'],
    'deleteTemplate'        => ['module' => 'template', 'action' => 'delete'],
    'deleteCategorie'       => ['module' => 'template', 'action' => 'delete'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
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
        // GET Actions
        case 'getTemplateCategories': if ($request_method === 'GET') getTemplateCategories($pdo, $authority); else MethodNotAllowed(); break;
        case 'getTemplates':          if ($request_method === 'GET') getTemplates($pdo, $authority); else MethodNotAllowed(); break;
        case 'getFields':             if ($request_method === 'GET') getFields($pdo, $authority); else MethodNotAllowed(); break;

        // POST Actions (or PUT/DELETE)
        case 'getTemplateFields':     if ($is_post_request) getTemplateFields($pdo, $authority); else MethodNotAllowed(); break; // Needs template_id via POST
        case 'addTemplate':           if ($is_post_request) addTemplate($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'addCategorie':          if ($is_post_request) addCategorie($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'saveCategorySorting':   if ($is_post_request) saveCategorySorting($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'updateCategoryName':    if ($is_post_request) updateCategoryName($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'updateTemplate':        if ($is_post_request) updateTemplate($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT
        case 'saveTemplateSorting':   if ($is_post_request) saveTemplateSorting($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'deleteTemplate':        if ($is_post_request) deleteTemplate($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE
        case 'deleteCategorie':       if ($is_post_request) deleteCategorie($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Helper function to get authority ID from the authority name
 * 
 * @param PDO $pdo Database connection
 * @param string|null $authority Authority name
 * @return int|null Authority ID or null if not found
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Gets input data from request, either from $_POST or from JSON request body
 * @return array Parsed input data
 */
function getInputData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // If content type contains 'json', parse the request body as JSON
    if (strpos($contentType, 'json') !== false) {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
        return is_array($inputData) ? $inputData : [];
    }
    
    // Otherwise, return POST data
    return $_POST ?? [];
}

// --- Function Implementations (PDO Refactored) ---

function getTemplateCategories(PDO $pdo, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        $sql = "SELECT id, name, icon, sort_order 
                FROM kdd_templates_categories 
                WHERE authority_id = ? AND is_deleted = 0
                ORDER BY sort_order ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($categories);
    } catch (\PDOException $e) {
        error_log("DB error in getTemplateCategories ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve template categories."]);
    }
}

function deleteCategorie(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing category ID.']); return;
    }

    try {
        // Soft delete
        $sql = "UPDATE kdd_templates_categories SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $id]);
        
        if ($stmt->rowCount() > 0) {
            // Also mark associated templates as deleted
            $sqlTemplates = "UPDATE kdd_templates SET is_deleted = 1 WHERE authority_id = ? AND category_id = ? AND is_deleted = 0";
            $stmtTemplates = $pdo->prepare($sqlTemplates);
            $stmtTemplates->execute([$authorityId, $id]);
            $templatesDeleted = $stmtTemplates->rowCount();
            
            http_response_code(200); 
            echo json_encode([
                "success" => true, 
                "message" => "Category deleted successfully. Also deleted {$templatesDeleted} associated templates."
            ]);
            
            // Log category change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "templates_categories", $id, $requestingUserId, $changes);
            
            // Log templates change if any
            if ($templatesDeleted > 0) {
                $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1, 'reason' => "Category {$id} deleted"]];
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'BATCH_DELETE', "templates", 0, $requestingUserId, $changes);
            }
        } else {
            http_response_code(404); echo json_encode(["error" => "Category not found or already deleted."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCategorie ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete category: " . $e->getMessage()]);
    }
}


function getTemplates(PDO $pdo, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        // Fetch categories and templates in one go, ordered correctly
        $sql = "SELECT c.id AS category_id, c.name AS category_name, c.icon AS category_icon, c.sort_order AS category_sort_order,
                       t.id AS template_id, t.name AS template_name, t.icon AS template_icon,
                       t.text AS template_text, t.description as template_description,
                       t.sort_order as template_sort_order, t.subject as template_subject,
                       t.recipient as template_recipient
                FROM kdd_templates_categories c
                LEFT JOIN kdd_templates t ON t.authority_id = ? AND c.id = t.category_id AND t.is_deleted = 0
                WHERE c.authority_id = ? AND c.is_deleted = 0
                ORDER BY c.sort_order, t.sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Structure data in PHP
        $categories = [];
        foreach ($rows as $row) {
            $categoryId = $row['category_id'];
            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'icon' => $row['category_icon'],
                    'sort_order' => $row['category_sort_order'],
                    'templates' => [],
                ];
            }
            // Add template only if it exists (due to LEFT JOIN)
            if ($row['template_id'] !== null) {
                $categories[$categoryId]['templates'][] = [
                    'id' => (int)$row['template_id'],
                    'category_id' => $categoryId,
                    'name' => $row['template_name'],
                    'icon' => $row['template_icon'],
                    'text' => $row['template_text'],
                    'sort_order' => (int)$row['template_sort_order'], // Cast to int
                    'description' => $row['template_description'],
                    'recipient' => $row['template_recipient'],
                    'subject' => $row['template_subject']
                 ];
            }
        }

        http_response_code(200);
        echo json_encode(array_values($categories)); // Return indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getTemplates ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve templates."]);
    }
}

function getTemplateFields(PDO $pdo, string $authority): void {
    $inputData = getInputData();
    $templateId = filter_var($inputData['template_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$templateId) {
        http_response_code(400); 
        echo json_encode(['error' => 'Template ID is required.']); 
        return;
    }
    
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        $sql = "SELECT * FROM kdd_template_fields 
                WHERE authority_id = ? AND template_id = ? 
                ORDER BY sort_by";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $templateId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getTemplateFields (TemplateID: $templateId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve template fields."]);
    }
}

// Fetches ALL unique field definitions across all templates? Or just a master list? Assume master list.
function getFields(PDO $pdo, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        // Get all fields regardless of template
        $sql = "SELECT * FROM kdd_template_fields 
                WHERE authority_id = ? 
                ORDER BY sort_by";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getFields ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve fields."]);
    }
}

function addTemplate(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getInputData();
    $category_id = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $text = $data['text'] ?? '';
    $description = $data['description'] ?? '';
    $icon = $data['icon'] ?? '';
    $recipient = $data['recipient'] ?? '';
    $subject = $data['subject'] ?? '';
    // Add sort_order? Get MAX+1? Assume default or 0 for now.

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (!$category_id || empty($name)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Category ID and template name are required.']); 
        return; 
    }

    try {
        // Get next sort order? Assume 0 or let DB handle default
        $sort_order = 0; // Placeholder

        $sql = "INSERT INTO `kdd_templates` (authority_id, category_id, name, icon, description, text, recipient, subject, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, 
            $category_id, $name, $icon, $description, $text, $recipient, $subject, $sort_order
        ]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Template added successfully.", "id" => $newId]);
            // Log change
            $logData = json_encode(['name' => $name, 'category_id' => $category_id]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "templates", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add template.']); }
    } catch (\PDOException $e) {
        error_log("DB error in addTemplate ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add template."]);
    }
}

function addCategorie(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $name = $inputData['name'] ?? null;
    $icon = $inputData['icon'] ?? ''; // Icon might be optional

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (empty($name)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Category name is required.']); 
        return; 
    }

    try {
        // Get next sort order
        $stmtMaxSort = $pdo->prepare("SELECT MAX(sort_order) FROM kdd_templates_categories WHERE authority_id = ?");
        $stmtMaxSort->execute([$authorityId]);
        $maxSort = $stmtMaxSort->fetchColumn();
        $sort_order = ($maxSort === false || $maxSort === null) ? 0 : (int)$maxSort + 1;

        $sql = "INSERT INTO kdd_templates_categories (authority_id, name, icon, sort_order) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $icon, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Category added successfully.", "id" => $newId]);
            // Log change
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => $name], ['column_name' => 'icon', 'old_value' => null, 'new_value' => $icon]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "templates_categories", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add category.']); }
    } catch (\PDOException $e) {
        error_log("DB error in addCategorie ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add category."]);
    }
}

function saveCategorySorting(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $sorting = $inputData['categories'] ?? null;

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (!is_array($sorting)) {
        http_response_code(400); echo json_encode(['error' => 'Invalid sorting data.']); return;
    }

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE kdd_templates_categories SET sort_order = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);

        foreach ($sorting as $position => $categoryId) {
            $categoryId = filter_var($categoryId['id'], FILTER_VALIDATE_INT);
            if (!$categoryId) continue; // Skip invalid IDs
            $stmt->execute([$position, $authorityId, $categoryId]);
        }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "Category sorting updated successfully."]);
        
        // It might be excessive to log each individual category sort update
        // So just log a summary
        $logSummary = json_encode(['action' => 'Updated category sorting', 'items_count' => count($sorting)]);
        $changes = [['column_name' => 'sort_order', 'old_value' => null, 'new_value' => $logSummary]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "templates_categories", 0, $requestingUserId, $changes);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveCategorySorting ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update sorting."]);
    }
}

function saveTemplateSorting(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $categoryId = filter_var($inputData['category_id'] ?? null, FILTER_VALIDATE_INT);
    $sorting = $inputData['sorting'] ?? null;

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (!$categoryId || !is_array($sorting)) {
        http_response_code(400); echo json_encode(['error' => 'Category ID and valid sorting data required.']); return;
    }

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE kdd_templates SET sort_order = ? WHERE authority_id = ? AND id = ? AND category_id = ?";
        $stmt = $pdo->prepare($sql);

        foreach ($sorting as $position => $templateId) {
            $templateId = filter_var($templateId, FILTER_VALIDATE_INT);
            if (!$templateId) continue; // Skip invalid IDs
            $stmt->execute([$position, $authorityId, $templateId, $categoryId]);
        }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "Template sorting updated successfully."]);
        
        // Log a summary
        $logSummary = json_encode(['action' => 'Updated template sorting for category', 'category_id' => $categoryId, 'items_count' => count($sorting)]);
        $changes = [['column_name' => 'sort_order', 'old_value' => null, 'new_value' => $logSummary]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "templates", 0, $requestingUserId, $changes);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveTemplateSorting ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update template sorting."]);
    }
}

function updateCategoryName(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $inputData['name'] ?? null;
    $icon = $inputData['icon'] ?? null;

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (!$id || empty($name)) {
        http_response_code(400); echo json_encode(['error' => 'Category ID and name are required.']); return;
    }

    try {
        // First fetch the old values for logging changes
        $stmtFetch = $pdo->prepare("SELECT name, icon FROM kdd_templates_categories WHERE authority_id = ? AND id = ?");
        $stmtFetch->execute([$authorityId, $id]);
        $oldValues = $stmtFetch->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldValues) {
            http_response_code(404); echo json_encode(["error" => "Category not found."]); return;
        }

        $sql = "UPDATE kdd_templates_categories SET name = ?, icon = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $icon, $authorityId, $id]);

        if ($success) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Category updated successfully."]);
            
            // Log changes
            $changes = [];
            if ($oldValues['name'] !== $name) {
                $changes[] = ['column_name' => 'name', 'old_value' => $oldValues['name'], 'new_value' => $name];
            }
            if ($oldValues['icon'] !== $icon) {
                $changes[] = ['column_name' => 'icon', 'old_value' => $oldValues['icon'], 'new_value' => $icon];
            }
            
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "templates_categories", $id, $requestingUserId, $changes);
            }
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to update category.']); }
    } catch (\PDOException $e) {
        error_log("DB error in updateCategoryName ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update category."]);
    }
}

function updateTemplate(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    $category_id = filter_var($inputData['category_id'] ?? null, FILTER_VALIDATE_INT);
    $name = $inputData['name'] ?? null;
    $text = $inputData['text'] ?? null;
    $icon = $inputData['icon'] ?? null;
    $description = $inputData['description'] ?? null;
    $recipient = $inputData['recipient'] ?? null;
    $subject = $inputData['subject'] ?? null;

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (!$id || !$category_id || empty($name)) {
        http_response_code(400); echo json_encode(['error' => 'Template ID, category ID, and name are required.']); return;
    }

    try {
        // First fetch the old values for logging changes
        $stmtFetch = $pdo->prepare("SELECT * FROM kdd_templates WHERE authority_id = ? AND id = ?");
        $stmtFetch->execute([$authorityId, $id]);
        $oldValues = $stmtFetch->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldValues) {
            http_response_code(404); echo json_encode(["error" => "Template not found."]); return;
        }

        $sql = "UPDATE kdd_templates SET 
                category_id = ?, name = ?, icon = ?, description = ?, text = ?, recipient = ?, subject = ?
                WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$category_id, $name, $icon, $description, $text, $recipient, $subject, $authorityId, $id]);

        if ($success) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Template updated successfully."]);
            
            // Log changes
            $changes = [];
            $fields = ['category_id', 'name', 'icon', 'description', 'text', 'recipient', 'subject'];
            $newValues = [$category_id, $name, $icon, $description, $text, $recipient, $subject];
            
            foreach ($fields as $index => $field) {
                if ($oldValues[$field] != $newValues[$index]) { // Intentional loose comparison for type flexibility
                    $changes[] = ['column_name' => $field, 'old_value' => $oldValues[$field], 'new_value' => $newValues[$index]];
                }
            }
            
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "templates", $id, $requestingUserId, $changes);
            }
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to update template.']); }
    } catch (\PDOException $e) {
        error_log("DB error in updateTemplate ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update template."]);
    }
}

function deleteTemplate(PDO $pdo, int $requestingUserId, string $authority): void {
    $inputData = getInputData();
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing template ID.']); return;
    }

    try {
        // Check if template exists
        $stmtCheck = $pdo->prepare("SELECT name FROM kdd_templates WHERE authority_id = ? AND id = ? AND is_deleted = 0");
        $stmtCheck->execute([$authorityId, $id]);
        $template = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            http_response_code(404); echo json_encode(["error" => "Template not found or already deleted."]); return;
        }

        // Soft delete
        $sql = "UPDATE kdd_templates SET is_deleted = 1 WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Template deleted successfully."]);
            
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "templates", $id, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to delete template.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteTemplate ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete template."]);
    }
}

?>