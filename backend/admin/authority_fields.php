<?php
/**
 * Backend Endpoint: admin/authority_fields.php
 * Handles CRUD operations for Authority-specific fields configuration.
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
require_once __DIR__ . '/../logging/logging.php';

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
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Check for required permission based on action
require_once __DIR__ . '/../utils/permission_helper.php';

// Determine if this is a read-only operation
$isReadOperation = in_array($action, ['getFields', 'getFieldsByAuth']) && $request_method === 'GET';
$requiredPermission = $isReadOperation ? 'READ_AUTHORITY_FIELDS' : 'WRITE_AUTHORITY_FIELDS';

// Check permission
if (!hasPermission($userPermissions, $requiredPermission) && 
    !hasPermission($userPermissions, 'ADMIN_AUTHORITY_FIELDS') && 
    !hasAllPermissions($userPermissions)) {
    http_response_code(403); 
    echo json_encode(["error" => "Permission denied for authority field management."]); 
    exit();
}

// Execute requested action
if ($action === '') { 
    http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); 
}

switch ($action) {
    case 'getFields':        if ($request_method === 'GET')  getAuthorityFields($pdo); else MethodNotAllowed(); break;
    case 'getFieldsByAuth':  if ($request_method === 'GET')  getFieldsByAuthority($pdo); else MethodNotAllowed(); break;
    case 'getCurrentAuthority': if ($request_method === 'GET') getCurrentAuthority($pdo, $authorityId); else MethodNotAllowed(); break;
    case 'addField':         if ($request_method === 'POST') addAuthorityField($pdo, $userId); else MethodNotAllowed(); break;
    case 'updateField':      if ($request_method === 'POST') updateAuthorityField($pdo, $userId); else MethodNotAllowed(); break;
    case 'deleteField':      if ($request_method === 'POST') deleteAuthorityField($pdo, $userId); else MethodNotAllowed(); break;
    default: http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); break;
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
 * Get all fields for all authorities (admin view)
 */
function getAuthorityFields(PDO $pdo): void {
    try {
        $sql = "SELECT af.*, a.name as authority_name 
                FROM kdd_authority_fields af
                JOIN kdd_authorities a ON af.authority_id = a.id
                ORDER BY a.name, af.display_order";
        $stmt = $pdo->query($sql);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process options for fields that have them
        foreach ($fields as &$field) {
            if (!empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
        }
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getAuthorityFields: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve authority fields."]);
    }
}

/**
 * Get fields for a specific authority
 */
function getFieldsByAuthority(PDO $pdo): void {
    $authorityId = isset($_GET['authority_id']) ? (int)$_GET['authority_id'] : null;
    
    if (!$authorityId) {
        http_response_code(400); echo json_encode(["error" => "Authority ID required"]); return;
    }
    
    try {
        $sql = "SELECT * FROM kdd_authority_fields WHERE authority_id = ? ORDER BY display_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process options for fields that have them
        foreach ($fields as &$field) {
            if (!empty($field['options'])) {
                $field['options'] = json_decode($field['options'], true);
            }
        }
        
        http_response_code(200);
        echo json_encode($fields);
    } catch (\PDOException $e) {
        error_log("DB error in getFieldsByAuthority: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve authority fields."]);
    }
}

/**
 * Get the current user's authority details
 */
function getCurrentAuthority(PDO $pdo, int $authorityId): void {
    try {
        $sql = "SELECT id, name, display_name FROM kdd_authorities WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $authority = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$authority) {
            http_response_code(404);
            echo json_encode(["error" => "Authority not found"]);
            return;
        }
        
        http_response_code(200);
        echo json_encode($authority);
    } catch (\PDOException $e) {
        error_log("DB error in getCurrentAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve authority information."]);
    }
}

/**
 * Add a new authority field
 */
function addAuthorityField(PDO $pdo, int $userId): void {
    global $authorityId; // Access the global authorityId variable from the JWT
    $inputData = getInputData();
    
    // Get authority_id from input or use the user's current authority
    $fieldAuthorityId = isset($inputData['authority_id']) ? (int)$inputData['authority_id'] : $authorityId;
    
    // Validate required fields
    $requiredFields = ['field_name', 'display_name', 'field_type'];
    foreach ($requiredFields as $field) {
        if (empty($inputData[$field])) {
            http_response_code(400); echo json_encode(["error" => "Missing required field: $field"]); return;
        }
    }
    
    try {
        // Validate field name (only alphanumeric and underscore)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $inputData['field_name'])) {
            http_response_code(400); 
            echo json_encode(["error" => "Field name must contain only letters, numbers, and underscores"]); 
            return;
        }
        
        // Process options if present
        $options = null;
        if (isset($inputData['options']) && !empty($inputData['options'])) {
            $options = json_encode($inputData['options']);
        }
        
        $sql = "INSERT INTO kdd_authority_fields (authority_id, field_name, display_name, 
                field_type, required, options, display_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fieldAuthorityId,
            $inputData['field_name'],
            $inputData['display_name'],
            $inputData['field_type'],
            isset($inputData['required']) && $inputData['required'] ? 1 : 0,
            $options,
            isset($inputData['display_order']) ? (int)$inputData['display_order'] : 0
        ]);
        
        $id = $pdo->lastInsertId();
        
        // Log the change
        logDatabaseChange($fieldAuthorityId, $pdo, 'create', 'kdd_authority_fields', $id, $userId, [
            'authority_id' => $fieldAuthorityId,
            'field_name' => $inputData['field_name'],
            'display_name' => $inputData['display_name']
        ]);
        
        http_response_code(201);
        echo json_encode([
            "id" => $id,
            "message" => "Field added successfully"
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in addAuthorityField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add authority field: " . $e->getMessage()]);
    }
}

/**
 * Update an existing authority field
 */
function updateAuthorityField(PDO $pdo, int $userId): void {
    global $userPermissions; // Access user permissions to check if they can change authority
    $inputData = getInputData();
    
    // Validate required fields
    if (empty($inputData['id'])) {
        http_response_code(400); echo json_encode(["error" => "Field ID is required"]); return;
    }
    
    $id = (int)$inputData['id'];
    $fieldAuthorityId = null;
    
    try {
        // First, get the current field data for logging
        $stmt = $pdo->prepare("SELECT * FROM kdd_authority_fields WHERE id = ?");
        $stmt->execute([$id]);
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldData) {
            http_response_code(404); echo json_encode(["error" => "Field not found"]); return;
        }
        
        // Get the authority_id from old data
        $fieldAuthorityId = (int)$oldData['authority_id'];
        
        // Process options if present
        $options = null;
        if (isset($inputData['options'])) {
            $options = !empty($inputData['options']) ? json_encode($inputData['options']) : null;
        }
        
        // Build the UPDATE query dynamically based on provided fields
        $updateFields = [];
        $params = [];
        
        // Only allow changing authority_id if user has system admin or all permissions
        if (isset($inputData['authority_id']) && 
            (hasPermission($userPermissions, 'SYSTEM_ADMIN') || hasAllPermissions($userPermissions))) {
            $updateFields[] = "authority_id = ?";
            $params[] = (int)$inputData['authority_id'];
        }
        
        if (isset($inputData['display_name'])) {
            $updateFields[] = "display_name = ?";
            $params[] = $inputData['display_name'];
        }
        
        if (isset($inputData['field_type'])) {
            $updateFields[] = "field_type = ?";
            $params[] = $inputData['field_type'];
        }
        
        if (isset($inputData['required'])) {
            $updateFields[] = "required = ?";
            $params[] = $inputData['required'] ? 1 : 0;
        }
        
        if (isset($options)) {
            $updateFields[] = "options = ?";
            $params[] = $options;
        }
        
        if (isset($inputData['display_order'])) {
            $updateFields[] = "display_order = ?";
            $params[] = (int)$inputData['display_order'];
        }
        
        // If there's nothing to update, return success
        if (empty($updateFields)) {
            http_response_code(200); echo json_encode(["message" => "No changes to update"]); return;
        }
        
        // Add ID to params
        $params[] = $id;
        
        // Execute the update
        $sql = "UPDATE kdd_authority_fields SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log the change
        logDatabaseChange($fieldAuthorityId, $pdo, 'update', 'kdd_authority_fields', $id, $userId, [
            'old' => $oldData,
            'new' => $inputData
        ]);
        
        http_response_code(200);
        echo json_encode(["message" => "Field updated successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in updateAuthorityField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update authority field: " . $e->getMessage()]);
    }
}

/**
 * Delete an authority field
 */
function deleteAuthorityField(PDO $pdo, int $userId): void {
    $inputData = getInputData();
    
    if (empty($inputData['id'])) {
        http_response_code(400); echo json_encode(["error" => "Field ID is required"]); return;
    }
    
    $id = (int)$inputData['id'];
    $fieldAuthorityId = null;
    
    try {
        // First, get the field data for logging
        $stmt = $pdo->prepare("SELECT * FROM kdd_authority_fields WHERE id = ?");
        $stmt->execute([$id]);
        $fieldData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fieldData) {
            http_response_code(404); echo json_encode(["error" => "Field not found"]); return;
        }
        
        // Get the authority_id from field data
        $fieldAuthorityId = (int)$fieldData['authority_id'];
        
        // Execute delete
        $stmt = $pdo->prepare("DELETE FROM kdd_authority_fields WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log the deletion
        logDatabaseChange($fieldAuthorityId, $pdo, 'delete', 'kdd_authority_fields', $id, $userId, $fieldData);
        
        http_response_code(200);
        echo json_encode(["message" => "Field deleted successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in deleteAuthorityField: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete authority field: " . $e->getMessage()]);
    }
} 