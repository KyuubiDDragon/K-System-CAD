<?php
/**
 * Backend Endpoint: apartmentfile/index.php
 * Handles CRUD operations for Apartments and their relations.
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
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null; // ID of the user making the request
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'apartment_file')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to apartment features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST for GET or POST action trigger
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getApartments'         => ['module' => 'apartment.file', 'action' => 'read'],
    'getApartmentsByPerson' => ['module' => 'apartment.file', 'action' => 'read'],
    'addApartment'          => ['module' => 'apartment.file', 'action' => 'write'],
    'editApartment'         => ['module' => 'apartment.file', 'action' => 'write'],
    'deleteApartment'       => ['module' => 'apartment.file', 'action' => 'delete'],
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
    switch ($action) {
        case 'getApartments':
            if ($request_method === 'GET') getApartments($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getApartmentsByPerson':
             if ($request_method === 'GET') getApartmentsByPerson($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addApartment':
            if ($request_method === 'POST') addApartment($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editApartment':
             // PUT might be more appropriate semantically, but POST works
             if ($request_method === 'POST' || $request_method === 'PUT') editApartment($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteApartment':
             // DELETE might be more appropriate semantically, but POST works
             if ($request_method === 'POST' || $request_method === 'DELETE') deleteApartment($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        default:
            http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Gets input data from request, either from $_POST, $_GET, or from JSON request body
 * @return array Parsed input data
 */
function getInputData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    
    // If content type contains 'json', parse the request body as JSON
    if (strpos($contentType, 'json') !== false) {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
        return is_array($inputData) ? $inputData : [];
    }
    
    // Otherwise, return POST or GET data depending on request method
    if ($requestMethod === 'GET') {
        return $_GET ?? [];
    } else {
        return $_POST ?? [];
    }
}

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

function getApartments(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT a.*,
                       GROUP_CONCAT(CASE WHEN pr.type = 'owner' THEN pr.person_id END SEPARATOR ',') AS owners_str,
                       GROUP_CONCAT(CASE WHEN pr.type = 'tenant' THEN pr.person_id END SEPARATOR ',') AS tenants_str,
                       GROUP_CONCAT(CASE WHEN pr.type = 'landlord' THEN pr.person_id END SEPARATOR ',') AS landlords_str,
                       GROUP_CONCAT(CASE WHEN pr.type = 'subtenant' THEN pr.person_id END SEPARATOR ',') AS subtenants_str
                FROM kdd_apartment a
                LEFT JOIN kdd_apartment_rel pr ON pr.authority_id = a.authority_id AND a.id = pr.apartment_id
                WHERE a.authority_id = ? AND a.is_deleted = 0
                GROUP BY a.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $apartmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Post-process results to convert comma-separated strings to arrays
        $apartments = array_map(function($row) {
            $row['owners'] = !empty($row['owners_str']) ? explode(',', $row['owners_str']) : [];
            $row['tenants'] = !empty($row['tenants_str']) ? explode(',', $row['tenants_str']) : [];
            $row['landlords'] = !empty($row['landlords_str']) ? explode(',', $row['landlords_str']) : [];
            $row['subtenants'] = !empty($row['subtenants_str']) ? explode(',', $row['subtenants_str']) : [];
            // Remove the temporary string fields
            unset($row['owners_str'], $row['tenants_str'], $row['landlords_str'], $row['subtenants_str']);
            return $row;
        }, $apartmentsData);

        http_response_code(200);
        echo json_encode($apartments);

    } catch (\PDOException $e) {
        error_log("DB error in getApartments ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve apartments."]);
    }
}

function addApartment(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Get data using getInputData instead of directly accessing $_POST
    $inputData = getInputData();
    
    $name = $inputData['name'] ?? null;
    $location = $inputData['location'] ?? null;
    $street = $inputData['street'] ?? '';
    $housenumber = $inputData['housenumber'] ?? '';
    $units = filter_var($inputData['units'] ?? 0, FILTER_VALIDATE_INT);
    $bought = filter_var($inputData['bought'] ?? 0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); // Allow true/false/'1'/'0'
    $rented = filter_var($inputData['rented'] ?? 0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $text = $inputData['text'] ?? '';

    // Relationship IDs (expecting arrays from input data, e.g., owners[]=1&owners[]=2)
    $owners = isset($inputData['owners']) && is_array($inputData['owners']) ? array_map('intval', $inputData['owners']) : [];
    $tenants = isset($inputData['tenants']) && is_array($inputData['tenants']) ? array_map('intval', $inputData['tenants']) : [];
    $landlords = isset($inputData['landlords']) && is_array($inputData['landlords']) ? array_map('intval', $inputData['landlords']) : [];
    $subtenants = isset($inputData['subtenants']) && is_array($inputData['subtenants']) ? array_map('intval', $inputData['subtenants']) : [];

    // Validation
    if (empty($name) || empty($location) || $units === false || $bought === null || $rented === null) {
        http_response_code(400); 
        echo json_encode(['error' => 'Missing or invalid required fields (name, location, units, bought, rented).']); 
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Apartment
        $sqlApt = "INSERT INTO kdd_apartment (authority_id, name, location, street, housenumber, units, bought, rented, text, is_deleted)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmtApt = $pdo->prepare($sqlApt);
        $stmtApt->execute([$authorityId, $name, $location, $street, $housenumber, $units, $bought, $rented, $text]);
        $apartmentId = $pdo->lastInsertId();

        if (!$apartmentId) { throw new \Exception("Failed to insert apartment record."); }

        // 2. Insert Relationships
        $sqlRel = "INSERT INTO kdd_apartment_rel (authority_id, apartment_id, person_id, type) VALUES (?, ?, ?, ?)";
        $stmtRel = $pdo->prepare($sqlRel);

        foreach ($owners as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $apartmentId, $personId, 'owner']); 
        }
        foreach ($tenants as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $apartmentId, $personId, 'tenant']); 
        }
        foreach ($landlords as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $apartmentId, $personId, 'landlord']); 
        }
        foreach ($subtenants as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $apartmentId, $personId, 'subtenant']); 
        }

        // If all operations were successful, commit the transaction
        $pdo->commit();

        http_response_code(201); // Created
        echo json_encode(["success" => true, "message" => "Apartment added successfully.", "new_id" => $apartmentId]);

        // Optional: Log the change
        // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_apartment", $apartmentId, $requestingUserId, ["action"=>"add", "data"=>$inputData]);

    } catch (\PDOException | \Exception $e) {
        // An error occurred, roll back the transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in addApartment ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add apartment: " . $e->getMessage()]);
    }
}

function editApartment(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Get data using getInputData instead of directly accessing $_POST
    $inputData = getInputData();
    
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $inputData['name'] ?? null;
    $location = $inputData['location'] ?? null;
    $street = $inputData['street'] ?? '';
    $housenumber = $inputData['housenumber'] ?? '';
    $units = filter_var($inputData['units'] ?? null, FILTER_VALIDATE_INT);
    $bought = filter_var($inputData['bought'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $rented = filter_var($inputData['rented'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $text = $inputData['text'] ?? '';

    // Relationships
    $owners = isset($inputData['owners']) && is_array($inputData['owners']) ? array_map('intval', $inputData['owners']) : [];
    $tenants = isset($inputData['tenants']) && is_array($inputData['tenants']) ? array_map('intval', $inputData['tenants']) : [];
    $landlords = isset($inputData['landlords']) && is_array($inputData['landlords']) ? array_map('intval', $inputData['landlords']) : [];
    $subtenants = isset($inputData['subtenants']) && is_array($inputData['subtenants']) ? array_map('intval', $inputData['subtenants']) : [];

    // Validation
    if ($id === false || $id <= 0 || empty($name) || empty($location) || $units === false || $bought === null || $rented === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid required fields (id, name, location, units, bought, rented).']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update Apartment Info
        $sqlApt = "UPDATE kdd_apartment SET name = ?, location = ?, street = ?, housenumber = ?, units = ?,
                       bought = ?, rented = ?, text = ?
                   WHERE authority_id = ? AND id = ? AND is_deleted = 0"; // Ensure not deleted
        $stmtApt = $pdo->prepare($sqlApt);
        $successApt = $stmtApt->execute([$name, $location, $street, $housenumber, $units, $bought, $rented, $text, $authorityId, $id]);

        // Check if apartment existed and was updated
        if (!$successApt || $stmtApt->rowCount() === 0) {
             throw new \Exception("Apartment with ID $id not found or update failed.");
        }

        // 2. Delete ALL old relationships for this apartment
        $sqlDelRel = "DELETE FROM kdd_apartment_rel WHERE authority_id = ? AND apartment_id = ?";
        $stmtDelRel = $pdo->prepare($sqlDelRel);
        $stmtDelRel->execute([$authorityId, $id]); // No need to check affected rows here

        // 3. Insert NEW relationships
        $sqlRel = "INSERT INTO kdd_apartment_rel (authority_id, apartment_id, person_id, type) VALUES (?, ?, ?, ?)";
        $stmtRel = $pdo->prepare($sqlRel);

        foreach ($owners as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $id, $personId, 'owner']); 
        }
        foreach ($tenants as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $id, $personId, 'tenant']); 
        }
        foreach ($landlords as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $id, $personId, 'landlord']); 
        }
        foreach ($subtenants as $personId) { 
            if ($personId > 0) $stmtRel->execute([$authorityId, $id, $personId, 'subtenant']); 
        }

        // Commit transaction
        $pdo->commit();

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Apartment updated successfully."]);

        // Optional: Log the change (might be complex due to relationship changes)
        // logDatabaseChange(...);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in editApartment (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update apartment: " . $e->getMessage()]);
    }
}

function deleteApartment(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Get data using getInputData instead of directly accessing $_POST
    $inputData = getInputData();
    
    $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing apartment ID.']);
        return;
    }

    try {
        // Soft delete the apartment
        $sql = "UPDATE kdd_apartment SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Apartment deleted successfully."]);
            // Optional: Log the deletion
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_apartment", $id, $requestingUserId, []);
        } elseif ($success) {
            http_response_code(404); // Not found or already deleted
            echo json_encode(["error" => "Apartment not found or already deleted."]);
        } else {
             http_response_code(500); echo json_encode(["error" => "Failed to execute apartment deletion."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteApartment (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete apartment."]);
    }
}

function getApartmentsByPerson(PDO $pdo, string $authority, int $authorityId): void {
    // Get data using getInputData instead of directly accessing $_GET
    $inputData = getInputData();
     
    $personId = filter_var($inputData['personId'] ?? null, FILTER_VALIDATE_INT);

    if ($personId === false || $personId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing person ID.']);
        return;
    }

    try {
        // Query similar to getApartments, but filtered by personId in the relationship table
        $sql = "SELECT a.*,
                        pr.type AS person_role_in_this_apartment, -- Role of the requested person in this apartment
                        GROUP_CONCAT(DISTINCT CASE WHEN pr_all.type = 'owner' THEN pr_all.person_id END SEPARATOR ',') AS owners_str,
                        GROUP_CONCAT(DISTINCT CASE WHEN pr_all.type = 'tenant' THEN pr_all.person_id END SEPARATOR ',') AS tenants_str,
                        GROUP_CONCAT(DISTINCT CASE WHEN pr_all.type = 'landlord' THEN pr_all.person_id END SEPARATOR ',') AS landlords_str,
                        GROUP_CONCAT(DISTINCT CASE WHEN pr_all.type = 'subtenant' THEN pr_all.person_id END SEPARATOR ',') AS subtenants_str
                 FROM kdd_apartment a
                 JOIN kdd_apartment_rel pr ON pr.authority_id = a.authority_id AND a.id = pr.apartment_id AND pr.person_id = ? -- Filter by personId
                 LEFT JOIN kdd_apartment_rel pr_all ON pr_all.authority_id = a.authority_id AND a.id = pr_all.apartment_id -- Join again to get all relations
                 WHERE a.authority_id = ? AND a.is_deleted = 0
                 GROUP BY a.id"; // Group by apartment ID

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personId, $authorityId]);
        $apartmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Post-process results
        $apartments = array_map(function($row) {
            $row['owners'] = !empty($row['owners_str']) ? explode(',', $row['owners_str']) : [];
            $row['tenants'] = !empty($row['tenants_str']) ? explode(',', $row['tenants_str']) : [];
            $row['landlords'] = !empty($row['landlords_str']) ? explode(',', $row['landlords_str']) : [];
            $row['subtenants'] = !empty($row['subtenants_str']) ? explode(',', $row['subtenants_str']) : [];
            unset($row['owners_str'], $row['tenants_str'], $row['landlords_str'], $row['subtenants_str']);
            return $row;
        }, $apartmentsData);

        http_response_code(200);
        echo json_encode($apartments);

    } catch (\PDOException $e) {
        error_log("DB error in getApartmentsByPerson (PersonID: $personId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve apartments for person."]);
    }
}
?>