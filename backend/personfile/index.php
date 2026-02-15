<?php
/**
 * Backend Endpoint: personfile/index.php
 * Handles CRUD operations for Person Files, with dynamic fields based on authority.
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
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange and potentially getEntryById/getEntryChanges

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
if (!hasFeatureAccess($pdo, $authorityId, 'person_file')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to personfile features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getPersons'        => ['module' => 'person.file', 'action' => 'read'],
    'getOwnPersonCount' => ['module' => 'person.file', 'action' => 'read'],
    'addPerson'         => ['module' => 'person.file', 'action' => 'write'],
    'editPerson'        => ['module' => 'person.file', 'action' => 'write'],
    'deletePerson'      => ['module' => 'person.file', 'action' => 'delete'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

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
        case 'getPersons':        if ($request_method === 'GET') getPersons($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getOwnPersonCount': if ($request_method === 'GET') getOwnPersonCount($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addPerson':         if ($is_post_request) addPerson($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editPerson':        if ($is_post_request) editPerson($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT
        case 'deletePerson':      if ($is_post_request) deletePerson($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
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

/**
 * Returns the list of database fields relevant for a given authority's person file.
 */
function getAuthorityFields(string $authority, int $authorityId): array {
    global $pdo;
    
    // Common fields for all authorities
    $commonFields = [
        'firstname', 'lastname', 'birthplace', 'birthday', 'phonenumber', 'address',
        'idcard', 'bankaccount', 'mail', 'entry', 'licenses', 'wanted', 'text',
        'is_deleted', 'gender', 'title', 'creator', 'created_at' // Added audit fields
    ];

    try {
        // Fetch authority-specific fields from database
        $stmt = $pdo->prepare("
            SELECT field_name FROM kdd_authority_fields
            WHERE authority_id = ?
        ");
        $stmt->execute([$authorityId]);
        $authoritySpecificFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return array_merge($commonFields, $authoritySpecificFields);
    } catch (\PDOException $e) {
        error_log("Error fetching authority fields: " . $e->getMessage());
        
        // Fallback to hardcoded fields if DB query fails
        $authoritySpecificFields = [];
        switch ($authority) {
            case 'fire':      $authoritySpecificFields = ['fireRelatedTraining', 'fireDepartment']; break;
            case 'police':    $authoritySpecificFields = ['lastKnownLocation']; break;
            case 'medic':     $authoritySpecificFields = ['medicalHistory', 'allergies', 'emergencyContact']; break;
            case 'justice':   $authoritySpecificFields = ['caseHistory', 'legalStatus', 'lawyerName']; break;
            case 'statepark': $authoritySpecificFields = ['parkEntryStatus', 'violations', 'rangerNotes']; break;
            case 'casa':      $authoritySpecificFields = ['propertyStatus', 'rentalHistory', 'landlordName']; break;
            default:          $authoritySpecificFields = []; break;
        }
        return array_merge($commonFields, $authoritySpecificFields);
    }
}


function getPersons(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM `kdd_person_file` WHERE authority_id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log original data for debugging
        error_log("Original persons data before processing: " . json_encode($persons));

        // Process custom fields from JSON
        foreach ($persons as &$person) {
            if (!empty($person['custom_fields'])) {
                error_log("Processing custom fields for person ID {$person['id']}: {$person['custom_fields']}");
                $customFields = json_decode($person['custom_fields'], true);
                if (is_array($customFields)) {
                    // Merge custom fields into the person object
                    foreach ($customFields as $field => $value) {
                        $person[$field] = $value;
                        // Anpassung für Arrays im Log - JSON encoding für Array-Werte
                        if (is_array($value)) {
                            error_log("Added custom field {$field} with array value " . json_encode($value) . " to person {$person['id']}");
                        } else {
                            error_log("Added custom field {$field} with value {$value} to person {$person['id']}");
                        }
                    }
                }
                // Remove the JSON field from the response to avoid duplication
                unset($person['custom_fields']);
            }
        }
        
        // Log final data after processing
        error_log("Final persons data after processing: " . json_encode($persons));
        
        http_response_code(200);
        echo json_encode($persons);
    } catch (\PDOException $e) {
        error_log("DB error in getPersons (Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve persons."]);
    }
}

function getOwnPersonCount(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT COUNT(*) FROM `kdd_person_file` WHERE authority_id = ? AND creator = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $requestingUserId]);
        $count = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["person_count" => $count]);
    } catch (\PDOException $e) {
        error_log("DB error in getOwnPersonCount (User: $requestingUserId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve person count."]);
    }
}

function addPerson(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    // Use getInputData instead of directly accessing $_POST
    $inputData = getInputData();

    // Get relevant fields for this authority
    $allowedDbFields = getAuthorityFields($authority, $authorityId);
    
    // Get list of standard fields (these are actual columns in the database)
    $standardFields = [
        'firstname', 'lastname', 'gender', 'title', 'birthplace', 'birthday', 
        'phonenumber', 'address', 'idcard', 'bankaccount', 'mail', 
        'entry', 'licenses', 'wanted', 'text', 'creator', 'created_at', 
        'updated_at', 'is_deleted'
    ];
    
    // Filter input data into standard fields and custom fields
    $fieldValues = [];
    $customFields = [];
    
    foreach ($inputData as $field => $value) {
        if (in_array($field, $standardFields)) {
            // Standard fields go into the main columns
            // Special handling for boolean 'wanted'
            if ($field === 'wanted') {
                $fieldValues[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            // Handle potential date fields (example for birthday/entry)
            elseif ($field === 'birthday' || $field === 'entry') {
                $fieldValues[$field] = !empty($value) ? date('Y-m-d', strtotime($value)) : null;
            }
            // Add other type conversions if necessary (int, float)
            else {
                $fieldValues[$field] = $value;
            }
        } 
        // Check if it's a custom field from the authority fields list
        elseif (in_array($field, $allowedDbFields) && !in_array($field, $standardFields)) {
            // Custom fields go into the JSON object
            $customFields[$field] = $value;
        }
    }

    // Ensure required fields are present after filtering
    if (empty($fieldValues['firstname']) || empty($fieldValues['lastname'])) {
        http_response_code(400); echo json_encode(['error' => 'Firstname and lastname are required.']); return;
    }

    // Set audit fields
    $fieldValues['updated_at'] = date('Y-m-d H:i:s');
    $fieldValues['is_deleted'] = 0; // Ensure not deleted
    
    // Add custom fields as JSON
    if (!empty($customFields)) {
        $fieldValues['custom_fields'] = json_encode($customFields);
    }

    try {
        // Build SQL dynamically
        $fields = array_keys($fieldValues);
        $placeholders = implode(', ', array_map(fn($f) => ":$f", $fields)); // Named placeholders :field
        $sqlFields = implode(', ', array_map(fn($f) => "`$f`", $fields)); // Add backticks

        $sql = "INSERT INTO `kdd_person_file` (authority_id, {$sqlFields}) VALUES (:authority_id, {$placeholders})";
        $stmt = $pdo->prepare($sql);

        // Build parameter array for execute, matching named placeholders
        $params = [":authority_id" => $authorityId];
        foreach ($fields as $field) {
            $params[":$field"] = $fieldValues[$field];
        }

        $success = $stmt->execute($params);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Person added successfully.", "id" => $newId]);
            // Log change
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => json_encode($fieldValues)]]; // Log inserted data
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_person_file", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add person.']); }
    } catch (\PDOException $e) {
         error_log("DB error in addPerson ($authority): " . $e->getMessage());
         http_response_code(500); echo json_encode(["error" => "Could not add person."]);
    }
}

function editPerson(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
     }

    // Use getInputData instead of directly accessing $_POST
    $inputData = getInputData();
    
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Valid Person ID is required.']); return; }

    // Get relevant fields for this authority
    $allowedDbFields = getAuthorityFields($authority, $authorityId);
    
    // Get list of standard fields (these are actual columns in the database)
    $standardFields = [
        'firstname', 'lastname', 'gender', 'title', 'birthplace', 'birthday', 
        'phonenumber', 'address', 'idcard', 'bankaccount', 'mail', 
        'entry', 'licenses', 'wanted', 'text', 'creator', 'created_at', 
        'updated_at', 'is_deleted'
    ];
    
    // Filter input data into standard fields and custom fields
    $fieldValues = [];
    $customFields = [];
    
    // First, check if we need to get existing custom fields to update them
    try {
        $stmt = $pdo->prepare("SELECT custom_fields FROM `kdd_person_file` WHERE id = ? AND authority_id = ?");
        $stmt->execute([$id, $authorityId]);
        $existingData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingData && !empty($existingData['custom_fields'])) {
            $customFields = json_decode($existingData['custom_fields'], true) ?: [];
        }
    } catch (\PDOException $e) {
        error_log("DB error fetching existing custom fields: " . $e->getMessage());
        // Continue with empty custom fields if there was an error
        $customFields = [];
    }
    
    // Process input fields
    foreach ($inputData as $field => $value) {
        if ($field === 'id') continue; // Skip ID field
        
        if (in_array($field, $standardFields)) {
            // Standard fields go into the main columns
            // Handle booleans
            if ($field === 'wanted' || $field === 'is_deleted') {
                $fieldValues[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            // Handle dates
            elseif ($field === 'birthday' || $field === 'entry') {
                $fieldValues[$field] = !empty($value) ? date('Y-m-d', strtotime($value)) : null;
            }
            else {
                $fieldValues[$field] = $value;
            }
        } 
        // Check if it's a custom field from the authority fields list
        elseif (in_array($field, $allowedDbFields) && !in_array($field, $standardFields)) {
            // Custom fields go into the JSON object
            $customFields[$field] = $value;
        }
    }

    // Ensure required fields are not being emptied (if applicable)
    if (isset($fieldValues['firstname']) && empty($fieldValues['firstname'])) { http_response_code(400); echo json_encode(['error' => 'Firstname cannot be empty.']); return; }
    if (isset($fieldValues['lastname']) && empty($fieldValues['lastname'])) { http_response_code(400); echo json_encode(['error' => 'Lastname cannot be empty.']); return; }

    // Add custom fields to fieldValues if there are any
    if (!empty($customFields)) {
        $fieldValues['custom_fields'] = json_encode($customFields);
    }
    
    if (empty($fieldValues)) { 
        http_response_code(400); echo json_encode(['error' => 'No valid data provided for update.']); return;
    }

    try {
        // Build dynamic SET part and params array
        $setParts = []; $params = [];
        foreach ($fieldValues as $field => $value) {
            $setParts[] = "`{$field}` = :{$field}"; // Use named placeholders
            $params[":{$field}"] = $value;
        }
        $params[":id"] = $id; // Add ID for WHERE clause
        $params[":authorityId"] = $authorityId; // Add authority ID for WHERE clause

        if (empty($setParts)) { http_response_code(400); echo json_encode(['error' => 'No fields to update.']); return; }

        $sql = "UPDATE `kdd_person_file` SET " . implode(', ', $setParts) . " WHERE authority_id = :authorityId AND id = :id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Person updated successfully."]);
             // Log change
             $changes = [['column_name' => 'updated', 'old_value' => null, 'new_value' => json_encode($fieldValues)]];
             global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_person_file", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["error" => "No changes made to the person record."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute person update.']); }
    } catch (\PDOException $e) {
        error_log("DB error in editPerson (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update person."]);
    }
}


function deletePerson(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use getInputData instead of directly accessing $_POST
    $inputData = getInputData();
    
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing person ID.']); 
        return; 
    }

    try {
        // Soft delete
        $sql = "UPDATE `kdd_person_file` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Person marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_person_file", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Person not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute person deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deletePerson (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete person."]);
    }
}

?>