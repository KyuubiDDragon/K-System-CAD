<?php
/**
 * Backend Endpoint: admin/report_fields.php
 * Handles CRUD operations for custom report fields.
 * Supports both global fields (category_id = NULL) and category-specific fields.
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
require_once __DIR__ . '/../logging/logging.php'; // For logging database changes

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID from token

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'reports')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to report features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
$permissions_map = [
    'getReportFields'      => 'VIEW_REPORT',
    'getFieldsByCategory'  => 'ADMIN_REPORTS',
    'addReportField'       => 'ADMIN_REPORTS',
    'updateReportField'    => 'ADMIN_REPORTS',
    'deleteReportField'    => 'ADMIN_REPORTS',
    'getReportCategories'  => 'ADMIN_REPORTS',
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Check permission
if (hasAllPermissions($userPermissions) || hasPermission($userPermissions, $required_permission)) {
    $has_permission = true;
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getReportFields':      if ($request_method === 'GET') getReportFields($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'getFieldsByCategory':  if ($request_method === 'GET') getFieldsByCategory($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'addReportField':       if ($is_post_request) addReportField($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateReportField':    if ($is_post_request) updateReportField($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteReportField':    if ($is_post_request) deleteReportField($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getReportCategories':  if ($request_method === 'GET') getReportCategories($pdo, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



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

// --- Function Implementations ---

/**
 * Returns all report fields for the authority
 */
function getReportFields(PDO $pdo, int $authorityId): void {
    try {
        $sql = "SELECT rf.*, rc.name as category_name 
                FROM kdd_report_fields rf
                LEFT JOIN kdd_report_category rc ON rf.category_id = rc.id
                WHERE rf.authority_id = ?
                ORDER BY (CASE WHEN rf.category_id IS NULL THEN 0 ELSE 1 END), rf.sort_order";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process options for select/multiselect fields
        foreach ($fields as &$field) {
            if (in_array($field['field_type'], ['select', 'multiselect']) && !empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
        }
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getReportFields: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve report fields."]);
    }
}

/**
 * Returns fields for a specific category (or global fields when category_id is null)
 */
function getFieldsByCategory(PDO $pdo, int $authorityId): void {
    $categoryId = isset($_GET['category_id']) ? filter_var($_GET['category_id'], FILTER_VALIDATE_INT) : null;
    $includeGlobal = isset($_GET['include_global']) ? filter_var($_GET['include_global'], FILTER_VALIDATE_BOOLEAN) : true;
    
    try {
        if ($includeGlobal) {
            // Get category-specific fields AND global fields (category_id IS NULL)
            if ($categoryId !== null && $categoryId > 0) {
                $sql = "SELECT * FROM kdd_report_fields 
                        WHERE authority_id = ? AND (category_id = ? OR category_id IS NULL)
                        ORDER BY sort_order";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$authorityId, $categoryId]);
            } else {
                // Only global fields
                $sql = "SELECT * FROM kdd_report_fields 
                        WHERE authority_id = ? AND category_id IS NULL
                        ORDER BY sort_order";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$authorityId]);
            }
        } else {
            // Only category-specific fields
            if ($categoryId !== null && $categoryId > 0) {
                $sql = "SELECT * FROM kdd_report_fields 
                        WHERE authority_id = ? AND category_id = ?
                        ORDER BY sort_order";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$authorityId, $categoryId]);
            } else {
                // Invalid request - need a category ID if not including global fields
                http_response_code(400);
                echo json_encode(["error" => "Category ID required when not including global fields"]);
                return;
            }
        }
        
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process options for select/multiselect fields
        foreach ($fields as &$field) {
            if (in_array($field['field_type'], ['select', 'multiselect']) && !empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
        }
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getFieldsByCategory: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve fields for the category."]);
    }
}

/**
 * Add a new report field
 */
function addReportField(PDO $pdo, int $userId, int $authorityId): void {
    $inputData = getInputData();
    
    // Validate required fields
    $requiredFields = ['field_name', 'field_label', 'field_type'];
    foreach ($requiredFields as $field) {
        if (empty($inputData[$field])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required field: {$field}"]);
            return;
        }
    }
    
    // Process and validate input
    $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '', $inputData['field_name']);
    if (empty($fieldName)) {
        http_response_code(400);
        echo json_encode(["error" => "Field name can only contain letters, numbers, and underscores"]);
        return;
    }
    
    $fieldLabel = trim($inputData['field_label']);
    $fieldType = $inputData['field_type'];
    $categoryId = !empty($inputData['category_id']) ? filter_var($inputData['category_id'], FILTER_VALIDATE_INT) : null;
    $sortOrder = isset($inputData['sort_order']) ? filter_var($inputData['sort_order'], FILTER_VALIDATE_INT) : 0;
    $isRequired = isset($inputData['is_required']) ? filter_var($inputData['is_required'], FILTER_VALIDATE_BOOLEAN) : false;
    
    // Process options for select/multiselect fields
    $options = null;
    if (in_array($fieldType, ['select', 'multiselect']) && isset($inputData['options'])) {
        if (is_array($inputData['options'])) {
            $options = json_encode($inputData['options']);
        } elseif (is_string($inputData['options'])) {
            // Try to parse if provided as JSON string
            $decoded = json_decode($inputData['options'], true);
            if (is_array($decoded)) {
                $options = $inputData['options']; // Already a JSON string
            } else {
                // Try to parse as comma-separated list
                $optionsArray = array_map('trim', explode(',', $inputData['options']));
                $options = json_encode($optionsArray);
            }
        }
    }
    
    // Check for duplicate field name
    try {
        $sql = "SELECT COUNT(*) FROM kdd_report_fields 
                WHERE authority_id = ? AND field_name = ? AND (category_id = ? OR (category_id IS NULL AND ? IS NULL))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $fieldName, $categoryId, $categoryId]);
        $count = (int) $stmt->fetchColumn();
        
        if ($count > 0) {
            http_response_code(400);
            echo json_encode(["error" => "A field with this name already exists for this category"]);
            return;
        }
        
        // Insert the new field
        $sql = "INSERT INTO kdd_report_fields 
                (authority_id, category_id, field_name, field_label, field_type, options, sort_order, is_required) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $authorityId,
            $categoryId,
            $fieldName,
            $fieldLabel,
            $fieldType,
            $options,
            $sortOrder,
            $isRequired ? 1 : 0
        ]);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            
            // Log the change
            $changes = [
                ['column_name' => 'field_creation', 'old_value' => null, 'new_value' => json_encode([
                    'field_name' => $fieldName,
                    'field_label' => $fieldLabel,
                    'field_type' => $fieldType,
                    'category_id' => $categoryId
                ])]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_report_fields', $newId, $userId, $changes);
            
            http_response_code(201);
            echo json_encode(["success" => true, "id" => $newId, "message" => "Report field created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create report field"]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addReportField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add report field."]);
    }
}

/**
 * Update an existing report field
 */
function updateReportField(PDO $pdo, int $userId, int $authorityId): void {
    $inputData = getInputData();
    
    // Validate field ID
    $fieldId = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$fieldId) {
        http_response_code(400);
        echo json_encode(["error" => "Valid field ID is required"]);
        return;
    }
    
    // Check field exists for this authority
    try {
        $sql = "SELECT * FROM kdd_report_fields WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fieldId, $authorityId]);
        $existingField = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingField) {
            http_response_code(404);
            echo json_encode(["error" => "Field not found or access denied"]);
            return;
        }
        
        // Process input data
        $updateData = [];
        $updateParams = [];
        
        // Field label
        if (isset($inputData['field_label']) && $inputData['field_label'] !== $existingField['field_label']) {
            $updateData[] = "field_label = ?";
            $updateParams[] = trim($inputData['field_label']);
        }
        
        // Field type (be careful changing this as it can break data)
        if (isset($inputData['field_type']) && $inputData['field_type'] !== $existingField['field_type']) {
            $updateData[] = "field_type = ?";
            $updateParams[] = $inputData['field_type'];
        }
        
        // Category ID
        if (isset($inputData['category_id'])) {
            $categoryId = filter_var($inputData['category_id'], FILTER_VALIDATE_INT);
            if ($categoryId !== (int)$existingField['category_id']) {
                $updateData[] = "category_id = ?";
                $updateParams[] = $categoryId ?: null;
            }
        }
        
        // Sort order
        if (isset($inputData['sort_order'])) {
            $sortOrder = filter_var($inputData['sort_order'], FILTER_VALIDATE_INT);
            if ($sortOrder !== null && $sortOrder !== (int)$existingField['sort_order']) {
                $updateData[] = "sort_order = ?";
                $updateParams[] = $sortOrder;
            }
        }
        
        // Required flag
        if (isset($inputData['is_required'])) {
            $isRequired = filter_var($inputData['is_required'], FILTER_VALIDATE_BOOLEAN);
            if ($isRequired !== (bool)$existingField['is_required']) {
                $updateData[] = "is_required = ?";
                $updateParams[] = $isRequired ? 1 : 0;
            }
        }
        
        // Options for select/multiselect
        if (isset($inputData['options']) && in_array($existingField['field_type'], ['select', 'multiselect'])) {
            $options = null;
            if (is_array($inputData['options'])) {
                $options = json_encode($inputData['options']);
            } elseif (is_string($inputData['options'])) {
                // Try to parse if provided as JSON string
                $decoded = json_decode($inputData['options'], true);
                if (is_array($decoded)) {
                    $options = $inputData['options']; // Already a JSON string
                } else {
                    // Try to parse as comma-separated list
                    $optionsArray = array_map('trim', explode(',', $inputData['options']));
                    $options = json_encode($optionsArray);
                }
            }
            
            if ($options !== $existingField['options']) {
                $updateData[] = "options = ?";
                $updateParams[] = $options;
            }
        }
        
        // If there's nothing to update
        if (empty($updateData)) {
            http_response_code(200);
            echo json_encode(["message" => "No changes to apply"]);
            return;
        }
        
        // Add field ID and authority ID to params
        $updateParams[] = $fieldId;
        $updateParams[] = $authorityId;
        
        // Update the field
        $sql = "UPDATE kdd_report_fields SET " . implode(", ", $updateData) . " WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($updateParams);
        
        if ($result) {
            // Log the change
            $changes = [
                ['column_name' => 'field_update', 'old_value' => json_encode($existingField), 'new_value' => json_encode($inputData)]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_report_fields', $fieldId, $userId, $changes);
            
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Report field updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update report field"]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateReportField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update report field."]);
    }
}

/**
 * Delete a report field
 */
function deleteReportField(PDO $pdo, int $userId, int $authorityId): void {
    $inputData = getInputData();
    
    // Validate field ID
    $fieldId = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$fieldId) {
        http_response_code(400);
        echo json_encode(["error" => "Valid field ID is required"]);
        return;
    }
    
    // Check field exists for this authority
    try {
        $sql = "SELECT * FROM kdd_report_fields WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fieldId, $authorityId]);
        $existingField = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingField) {
            http_response_code(404);
            echo json_encode(["error" => "Field not found or access denied"]);
            return;
        }
        
        // Delete the field
        $sql = "DELETE FROM kdd_report_fields WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$fieldId, $authorityId]);
        
        if ($result) {
            // Log the change
            $changes = [
                ['column_name' => 'field_deletion', 'old_value' => json_encode($existingField), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_report_fields', $fieldId, $userId, $changes);
            
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Report field deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete report field"]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteReportField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete report field."]);
    }
}

/**
 * Get report categories for this authority
 */
function getReportCategories(PDO $pdo, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, name, template, authority_id
            FROM kdd_report_category
            WHERE authority_id = :authority_id
            ORDER BY name ASC
        ");
        
        $stmt->execute(['authority_id' => $authorityId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($categories);
    } catch (PDOException $e) {
        error_log("DB error in getReportCategories: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Datenbankfehler beim Abrufen der Kategorien"]);
    }
}
?> 