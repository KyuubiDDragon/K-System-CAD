<?php
declare(strict_types=1);
/**
 * Backend Endpoint: company/index.php
 * Handles CRUD operations for Companies, Company Types, and Extinguishers.
 * Uses Cookie-based Authentication and PDO database connection.
 */

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
$userId = $decoded_jwt->userId ?? null;
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
if (!hasFeatureAccess($pdo, $authorityId, 'company')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to company features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getCompanies'         => ['module' => 'company', 'action' => 'read'],
    'getOnlyCompanies'     => ['module' => 'company', 'action' => 'read'],
    'addCompany'           => ['module' => 'company', 'action' => 'write'],
    'editCompany'          => ['module' => 'company', 'action' => 'write'],
    'deleteCompany'        => ['module' => 'company', 'action' => 'delete'],
    'getCompanyTypes'      => ['module' => 'company.type', 'action' => 'read'],
    'addCompanyType'       => ['module' => 'company.type', 'action' => 'write'],
    'editCompanyType'      => ['module' => 'company.type', 'action' => 'write'],
    'deleteCompanyType'    => ['module' => 'company.type', 'action' => 'delete'],
    'getExtinguishers'     => ['module' => 'company', 'action' => 'read'],
    'addExtinguisher'      => ['module' => 'company', 'action' => 'write'],
    'editExtinguisher'     => ['module' => 'company', 'action' => 'write'],
    'deleteExtinguisher'   => ['module' => 'company', 'action' => 'write'],
    'getCompanyHistory'    => ['module' => 'company', 'action' => 'read'],
    'getFireExtinguisherNr'=> ['module' => 'company', 'action' => 'read'],
    'setFireExtinguisherNr'=> ['module' => 'company', 'action' => 'write'],
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
    $has_permission = true; // Has specific permission
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
        case 'getCompanies':          if ($request_method === 'GET') getCompanies($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getOnlyCompanies':      if ($request_method === 'GET') getOnlyCompanies($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getCompanyTypes':       if ($request_method === 'GET') getCompanyTypes($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getExtinguishers':      if ($request_method === 'GET') getExtinguishers($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getCompanyHistory':     if ($request_method === 'GET') getCompanyHistory($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getFireExtinguisherNr': if ($request_method === 'GET') getFireExtinguisherNr($pdo, $authority, $authorityId); else MethodNotAllowed(); break;

        // POST Actions
        case 'addCompany':            if ($is_post_request) addCompany($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editCompany':           if ($is_post_request) editCompany($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteCompany':         if ($is_post_request) deleteCompany($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addCompanyType':        if ($is_post_request) addCompanyType($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editCompanyType':       if ($is_post_request) editCompanyType($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteCompanyType':     if ($is_post_request) deleteCompanyType($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addExtinguisher':       if ($is_post_request) addExtinguisher($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editExtinguisher':      if ($is_post_request) editExtinguisher($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteExtinguisher':    if ($is_post_request) deleteExtinguisher($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'setFireExtinguisherNr': if ($is_post_request) setFireExtinguisherNr($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;

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

// --- Function Implementations (PDO Refactored) ---

function getCompanies(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT c.*, ct.name as type_name, 
                (SELECT COUNT(*) FROM kdd_company_extinguishers ce 
                 WHERE ce.company_id = c.id AND ce.is_deleted = 0) as extinguisher_count
                FROM kdd_companies c
                LEFT JOIN kdd_company_types ct ON c.type_id = ct.id
                WHERE c.authority_id = ? AND c.is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($companies);
    } catch (\PDOException $e) {
        error_log("DB error in getCompanies ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve companies."]);
    }
}

function getOnlyCompanies(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT id, name FROM kdd_companies WHERE authority_id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($companies);
    } catch (\PDOException $e) {
        error_log("DB error in getOnlyCompanies ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve companies."]);
    }
}

function addCompany(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $name = trim($data['name'] ?? '');
    $location = trim($data['location'] ?? '');
    $ceo = trim($data['ceo'] ?? '');
    $co_ceo = trim($data['co_ceo'] ?? '');
    $type_id = (int)($data['type_id'] ?? 0);
    $contact_person = trim($data['contact_person'] ?? '');
    $phonenumber = trim($data['phonenumber'] ?? '');
    $last_fire_protection_inspection = trim($data['last_fire_protection_inspection'] ?? '');
    $fire_protection_inspection_valid_until = trim($data['fire_protection_inspection_valid_until'] ?? '');
    $extinguisher_count = (int)($data['extinguisher_count'] ?? 0);
    $contract_available = (bool)($data['contract_available'] ?? false);
    $description = trim($data['description'] ?? '');
    $email = trim($data['email'] ?? '');
    $website = trim($data['website'] ?? '');
    
    // Validate required fields
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Company name is required."]);
        return;
    }
    
    // Check if the type_id exists
    if ($type_id > 0) {
        $sqlCheckType = "SELECT 1 FROM kdd_company_types 
                         WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckType = $pdo->prepare($sqlCheckType);
        $stmtCheckType->execute([$type_id, $authorityId]);
        if (!$stmtCheckType->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid company type."]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO kdd_companies 
                (authority_id, name, location, ceo, co_ceo, type_id, contact_person, 
                 phonenumber, last_fire_protection_inspection, fire_protection_inspection_valid_until,
                 extinguisher_count, contract_available, description, email, website, created_at, updated_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $authorityId,
            $name,
            $location,
            $ceo,
            $co_ceo,
            $type_id,
            $contact_person,
            $phonenumber,
            $last_fire_protection_inspection,
            $fire_protection_inspection_valid_until,
            $extinguisher_count,
            $contract_available ? 1 : 0,
            $description,
            $email,
            $website
        ]);
        
        $companyId = $pdo->lastInsertId();
        
        // Add company history entry
        addCompanyHistory($pdo, (int)$companyId, $name, $ceo, $co_ceo, $contact_person, $phonenumber, $authorityId);

        // Log the action
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'location', 'old_value' => null, 'new_value' => $location],
            ['column_name' => 'ceo', 'old_value' => null, 'new_value' => $ceo],
            ['column_name' => 'co_ceo', 'old_value' => null, 'new_value' => $co_ceo],
            ['column_name' => 'type_id', 'old_value' => null, 'new_value' => $type_id],
            ['column_name' => 'contact_person', 'old_value' => null, 'new_value' => $contact_person],
            ['column_name' => 'phonenumber', 'old_value' => null, 'new_value' => $phonenumber],
            ['column_name' => 'last_fire_protection_inspection', 'old_value' => null, 'new_value' => $last_fire_protection_inspection],
            ['column_name' => 'fire_protection_inspection_valid_until', 'old_value' => null, 'new_value' => $fire_protection_inspection_valid_until],
            ['column_name' => 'extinguisher_count', 'old_value' => null, 'new_value' => $extinguisher_count],
            ['column_name' => 'contract_available', 'old_value' => null, 'new_value' => $contract_available ? 1 : 0],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
            ['column_name' => 'email', 'old_value' => null, 'new_value' => $email],
            ['column_name' => 'website', 'old_value' => null, 'new_value' => $website]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "companies", $companyId, $userId, $changes);

        $pdo->commit();
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Company added successfully.",
            "id" => $companyId
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in addCompany ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to add company: " . $e->getMessage()]);
    }
}

function editCompany(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $location = trim($data['location'] ?? '');
    $ceo = trim($data['ceo'] ?? '');
    $co_ceo = trim($data['co_ceo'] ?? '');
    $type_id = (int)($data['type_id'] ?? 0);
    $contact_person = trim($data['contact_person'] ?? '');
    $phonenumber = trim($data['phonenumber'] ?? '');
    $last_fire_protection_inspection = trim($data['last_fire_protection_inspection'] ?? '');
    $fire_protection_inspection_valid_until = trim($data['fire_protection_inspection_valid_until'] ?? '');
    $extinguisher_count = (int)($data['extinguisher_count'] ?? 0);
    $contract_available = (bool)($data['contract_available'] ?? false);
    $description = trim($data['description'] ?? '');
    $email = trim($data['email'] ?? '');
    $website = trim($data['website'] ?? '');
    
    // Validate required fields
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid company ID."]);
        return;
    }
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Company name is required."]);
        return;
    }
    
    // Check if the company exists and belongs to this authority
    $sqlCheckCompany = "SELECT 1 FROM kdd_companies 
                        WHERE id = ? AND authority_id = ? AND is_deleted = 0";
    $stmtCheckCompany = $pdo->prepare($sqlCheckCompany);
    $stmtCheckCompany->execute([$id, $authorityId]);
    if (!$stmtCheckCompany->fetchColumn()) {
        http_response_code(404);
        echo json_encode(["error" => "Company not found or already deleted."]);
        return;
    }
    
    // Check if the type_id exists
    if ($type_id > 0) {
        $sqlCheckType = "SELECT 1 FROM kdd_company_types 
                         WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckType = $pdo->prepare($sqlCheckType);
        $stmtCheckType->execute([$type_id, $authorityId]);
        if (!$stmtCheckType->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid company type."]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Fetch current company data for history and logging
        $sqlCurrentData = "SELECT * FROM kdd_companies WHERE id = ?";
        $stmtCurrentData = $pdo->prepare($sqlCurrentData);
        $stmtCurrentData->execute([$id]);
        $currentData = $stmtCurrentData->fetch(PDO::FETCH_ASSOC);
        
        // Update the company
        $sql = "UPDATE kdd_companies SET 
                name = ?, location = ?, ceo = ?, co_ceo = ?, type_id = ?, contact_person = ?, 
                phonenumber = ?, last_fire_protection_inspection = ?, fire_protection_inspection_valid_until = ?,
                extinguisher_count = ?, contract_available = ?, description = ?, email = ?, website = ?,
                updated_at = NOW()
                WHERE id = ? AND authority_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $location,
            $ceo,
            $co_ceo,
            $type_id,
            $contact_person,
            $phonenumber,
            $last_fire_protection_inspection,
            $fire_protection_inspection_valid_until,
            $extinguisher_count,
            $contract_available ? 1 : 0,
            $description,
            $email,
            $website,
            $id,
            $authorityId
        ]);
        
        // Check if critical fields changed for history
        $criticalFieldsChanged = 
            $currentData['name'] !== $name ||
            $currentData['ceo'] !== $ceo ||
            $currentData['co_ceo'] !== $co_ceo ||
            $currentData['contact_person'] !== $contact_person ||
            $currentData['phonenumber'] !== $phonenumber;
        
        if ($criticalFieldsChanged) {
            addCompanyHistory($pdo, $id, $name, $ceo, $co_ceo, $contact_person, $phonenumber, $authorityId);
        }

        // Log the action - track changes
        $changes = [];
        if ($currentData['name'] !== $name) {
            $changes[] = ['column_name' => 'name', 'old_value' => $currentData['name'], 'new_value' => $name];
        }
        if ($currentData['location'] !== $location) {
            $changes[] = ['column_name' => 'location', 'old_value' => $currentData['location'], 'new_value' => $location];
        }
        if ($currentData['ceo'] !== $ceo) {
            $changes[] = ['column_name' => 'ceo', 'old_value' => $currentData['ceo'], 'new_value' => $ceo];
        }
        if ($currentData['co_ceo'] !== $co_ceo) {
            $changes[] = ['column_name' => 'co_ceo', 'old_value' => $currentData['co_ceo'], 'new_value' => $co_ceo];
        }
        if ($currentData['type_id'] != $type_id) {
            $changes[] = ['column_name' => 'type_id', 'old_value' => $currentData['type_id'], 'new_value' => $type_id];
        }
        if ($currentData['contact_person'] !== $contact_person) {
            $changes[] = ['column_name' => 'contact_person', 'old_value' => $currentData['contact_person'], 'new_value' => $contact_person];
        }
        if ($currentData['phonenumber'] !== $phonenumber) {
            $changes[] = ['column_name' => 'phonenumber', 'old_value' => $currentData['phonenumber'], 'new_value' => $phonenumber];
        }
        if ($currentData['last_fire_protection_inspection'] !== $last_fire_protection_inspection) {
            $changes[] = ['column_name' => 'last_fire_protection_inspection', 'old_value' => $currentData['last_fire_protection_inspection'], 'new_value' => $last_fire_protection_inspection];
        }
        if ($currentData['fire_protection_inspection_valid_until'] !== $fire_protection_inspection_valid_until) {
            $changes[] = ['column_name' => 'fire_protection_inspection_valid_until', 'old_value' => $currentData['fire_protection_inspection_valid_until'], 'new_value' => $fire_protection_inspection_valid_until];
        }
        if ($currentData['extinguisher_count'] != $extinguisher_count) {
            $changes[] = ['column_name' => 'extinguisher_count', 'old_value' => $currentData['extinguisher_count'], 'new_value' => $extinguisher_count];
        }
        $contract_available_int = $contract_available ? 1 : 0;
        if ($currentData['contract_available'] != $contract_available_int) {
            $changes[] = ['column_name' => 'contract_available', 'old_value' => $currentData['contract_available'], 'new_value' => $contract_available_int];
        }
        if ($currentData['description'] !== $description) {
            $changes[] = ['column_name' => 'description', 'old_value' => $currentData['description'], 'new_value' => $description];
        }
        if ($currentData['email'] !== $email) {
            $changes[] = ['column_name' => 'email', 'old_value' => $currentData['email'], 'new_value' => $email];
        }
        if ($currentData['website'] !== $website) {
            $changes[] = ['column_name' => 'website', 'old_value' => $currentData['website'], 'new_value' => $website];
        }

        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "companies", $id, $userId, $changes);
        }

        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Company updated successfully."
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in editCompany ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to update company: " . $e->getMessage()]);
    }
}

function deleteCompany(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    $id = (int)($data['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid company ID."]);
        return;
    }
    
    try {
        // Check if company exists and belongs to this authority
        $sqlCheck = "SELECT 1 FROM kdd_companies 
                     WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        
        if (!$stmtCheck->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company not found or already deleted."]);
            return;
        }
        
        $pdo->beginTransaction();

        // Get company data for logging before soft delete
        $sqlGetCompany = "SELECT * FROM kdd_companies WHERE id = ? AND authority_id = ?";
        $stmtGetCompany = $pdo->prepare($sqlGetCompany);
        $stmtGetCompany->execute([$id, $authorityId]);
        $companyData = $stmtGetCompany->fetch(PDO::FETCH_ASSOC);

        // Soft delete the company
        $sql = "UPDATE kdd_companies SET is_deleted = 1 WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $authorityId]);

        // Soft delete all related extinguishers
        $sqlExtinguishers = "UPDATE kdd_company_extinguishers SET is_deleted = 1
                             WHERE company_id = ? AND company_id IN (
                                SELECT id FROM kdd_companies WHERE authority_id = ?
                             )";
        $stmtExtinguishers = $pdo->prepare($sqlExtinguishers);
        $stmtExtinguishers->execute([$id, $authorityId]);

        // Log the action
        if ($companyData) {
            $changes = [
                ['column_name' => 'SOFT_DELETE', 'old_value' => json_encode($companyData), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "companies", $id, $userId, $changes);
        }

        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Company deleted successfully."
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteCompany ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete company: " . $e->getMessage()]);
    }
}

function getCompanyTypes(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM kdd_company_types WHERE authority_id = ? AND is_deleted = 0 ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($types);
    } catch (\PDOException $e) {
        error_log("DB error in getCompanyTypes ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve company types."]);
    }
}

function addCompanyType(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Type name is required."]);
        return;
    }
    
    try {
        // Check if type with same name already exists
        $sqlCheck = "SELECT 1 FROM kdd_company_types 
                     WHERE name = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$name, $authorityId]);
        
        if ($stmtCheck->fetchColumn()) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Company type with this name already exists."]);
            return;
        }
        
        $sql = "INSERT INTO kdd_company_types 
                (authority_id, name, description, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $name, $description]);
        
        $typeId = $pdo->lastInsertId();

        // Log the action
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "company_types", $typeId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Company type added successfully.",
            "id" => $typeId
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in addCompanyType ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to add company type: " . $e->getMessage()]);
    }
}

function editCompanyType(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid type ID."]);
        return;
    }
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Type name is required."]);
        return;
    }
    
    try {
        // Check if type exists and belongs to this authority
        $sqlCheck = "SELECT 1 FROM kdd_company_types 
                     WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        
        if (!$stmtCheck->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company type not found or already deleted."]);
            return;
        }
        
        // Check if another type with the same name exists
        $sqlCheckName = "SELECT 1 FROM kdd_company_types 
                         WHERE name = ? AND id != ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckName = $pdo->prepare($sqlCheckName);
        $stmtCheckName->execute([$name, $id, $authorityId]);
        
        if ($stmtCheckName->fetchColumn()) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Another company type with this name already exists."]);
            return;
        }
        
        // Fetch current type data for logging
        $sqlCurrentData = "SELECT * FROM kdd_company_types WHERE id = ?";
        $stmtCurrentData = $pdo->prepare($sqlCurrentData);
        $stmtCurrentData->execute([$id]);
        $currentData = $stmtCurrentData->fetch(PDO::FETCH_ASSOC);
        
        // Update the type
        $sql = "UPDATE kdd_company_types
                SET name = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $id, $authorityId]);

        // Log the action - track changes
        $changes = [];
        if ($currentData['name'] !== $name) {
            $changes[] = ['column_name' => 'name', 'old_value' => $currentData['name'], 'new_value' => $name];
        }
        if ($currentData['description'] !== $description) {
            $changes[] = ['column_name' => 'description', 'old_value' => $currentData['description'], 'new_value' => $description];
        }

        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "company_types", $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Company type updated successfully."
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in editCompanyType ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to update company type: " . $e->getMessage()]);
    }
}

function deleteCompanyType(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    $id = (int)($data['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid type ID."]);
        return;
    }
    
    try {
        // Check if type exists and belongs to this authority
        $sqlCheck = "SELECT 1 FROM kdd_company_types 
                     WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        
        if (!$stmtCheck->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company type not found or already deleted."]);
            return;
        }
        
        // Check if any companies use this type
        $sqlCheckUsage = "SELECT COUNT(*) FROM kdd_companies 
                          WHERE type_id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckUsage = $pdo->prepare($sqlCheckUsage);
        $stmtCheckUsage->execute([$id, $authorityId]);
        $usageCount = (int)$stmtCheckUsage->fetchColumn();
        
        if ($usageCount > 0) {
            http_response_code(409); // Conflict
            echo json_encode([
                "error" => "Cannot delete this type as it is used by {$usageCount} companies.",
                "usage_count" => $usageCount
            ]);
            return;
        }

        // Get type data for logging before soft delete
        $sqlGetType = "SELECT * FROM kdd_company_types WHERE id = ? AND authority_id = ?";
        $stmtGetType = $pdo->prepare($sqlGetType);
        $stmtGetType->execute([$id, $authorityId]);
        $typeData = $stmtGetType->fetch(PDO::FETCH_ASSOC);

        // Soft delete the type
        $sql = "UPDATE kdd_company_types SET is_deleted = 1 WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $authorityId]);

        // Log the action
        if ($typeData) {
            $changes = [
                ['column_name' => 'SOFT_DELETE', 'old_value' => json_encode($typeData), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "company_types", $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Company type deleted successfully."
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in deleteCompanyType ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete company type: " . $e->getMessage()]);
    }
}

function getExtinguishers(PDO $pdo, string $authority, int $authorityId): void {
    $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    
    if ($companyId <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Company ID is required."]);
        return;
    }
    
    try {
        // Check if company exists and belongs to this authority
        $sqlCheckCompany = "SELECT 1 FROM kdd_companies 
                            WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckCompany = $pdo->prepare($sqlCheckCompany);
        $stmtCheckCompany->execute([$companyId, $authorityId]);
        
        if (!$stmtCheckCompany->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company not found or access denied."]);
            return;
        }
        
        $sql = "SELECT * FROM kdd_company_extinguishers 
                WHERE company_id = ? AND is_deleted = 0
                ORDER BY identifier";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        $extinguishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($extinguishers);
        
    } catch (\PDOException $e) {
        error_log("DB error in getExtinguishers ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve extinguishers."]);
    }
}

function addExtinguisher(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $companyId = (int)($data['company_id'] ?? 0);
    $identifier = trim($data['identifier'] ?? '');
    $registrationDate = trim($data['registration_date'] ?? '');
    $lastInspection = trim($data['last_inspection'] ?? '');
    $inspectedBy = trim($data['inspected_by'] ?? '');
    $location = trim($data['location'] ?? '');
    
    if ($companyId <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Company ID is required."]);
        return;
    }
    
    if (empty($identifier)) {
        http_response_code(400);
        echo json_encode(["error" => "Extinguisher identifier is required."]);
        return;
    }
    
    try {
        // Check if company exists and belongs to this authority
        $sqlCheckCompany = "SELECT 1 FROM kdd_companies 
                            WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheckCompany = $pdo->prepare($sqlCheckCompany);
        $stmtCheckCompany->execute([$companyId, $authorityId]);
        
        if (!$stmtCheckCompany->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company not found or access denied."]);
            return;
        }
        
        // Check if extinguisher with same identifier already exists for this company
        $sqlCheckIdentifier = "SELECT 1 FROM kdd_company_extinguishers 
                               WHERE company_id = ? AND identifier = ? AND is_deleted = 0";
        $stmtCheckIdentifier = $pdo->prepare($sqlCheckIdentifier);
        $stmtCheckIdentifier->execute([$companyId, $identifier]);
        
        if ($stmtCheckIdentifier->fetchColumn()) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Extinguisher with this identifier already exists for this company."]);
            return;
        }
        
        $sql = "INSERT INTO kdd_company_extinguishers 
                (company_id, identifier, registration_date, last_inspection, inspected_by, location, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $companyId,
            $identifier,
            $registrationDate,
            $lastInspection,
            $inspectedBy,
            $location
        ]);
        
        $extinguisherId = $pdo->lastInsertId();
        
        // Update the extinguisher count on the company
        $sqlUpdateCount = "UPDATE kdd_companies 
                           SET extinguisher_count = (
                               SELECT COUNT(*) FROM kdd_company_extinguishers 
                               WHERE company_id = ? AND is_deleted = 0
                           ),
                           updated_at = NOW()
                           WHERE id = ? AND authority_id = ?";
        $stmtUpdateCount = $pdo->prepare($sqlUpdateCount);
        $stmtUpdateCount->execute([$companyId, $companyId, $authorityId]);

        // Log the action
        $changes = [
            ['column_name' => 'company_id', 'old_value' => null, 'new_value' => $companyId],
            ['column_name' => 'identifier', 'old_value' => null, 'new_value' => $identifier],
            ['column_name' => 'registration_date', 'old_value' => null, 'new_value' => $registrationDate],
            ['column_name' => 'last_inspection', 'old_value' => null, 'new_value' => $lastInspection],
            ['column_name' => 'inspected_by', 'old_value' => null, 'new_value' => $inspectedBy],
            ['column_name' => 'location', 'old_value' => null, 'new_value' => $location]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "company_extinguishers", $extinguisherId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Extinguisher added successfully.",
            "id" => $extinguisherId
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in addExtinguisher ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to add extinguisher: " . $e->getMessage()]);
    }
}

function editExtinguisher(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    
    $id = (int)($data['id'] ?? 0);
    $companyId = (int)($data['company_id'] ?? 0);
    $identifier = trim($data['identifier'] ?? '');
    $registrationDate = trim($data['registration_date'] ?? '');
    $lastInspection = trim($data['last_inspection'] ?? '');
    $inspectedBy = trim($data['inspected_by'] ?? '');
    $location = trim($data['location'] ?? '');
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid extinguisher ID."]);
        return;
    }
    
    if (empty($identifier)) {
        http_response_code(400);
        echo json_encode(["error" => "Extinguisher identifier is required."]);
        return;
    }
    
    try {
        // Check if extinguisher exists and belongs to a company in this authority
        $sqlCheck = "SELECT e.id, e.company_id FROM kdd_company_extinguishers e
                     JOIN kdd_companies c ON e.company_id = c.id
                     WHERE e.id = ? AND c.authority_id = ? AND e.is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        $extinguisher = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$extinguisher) {
            http_response_code(404);
            echo json_encode(["error" => "Extinguisher not found or access denied."]);
            return;
        }
        
        // If company_id is provided, check if it's valid and belongs to this authority
        if ($companyId > 0 && $companyId !== (int)$extinguisher['company_id']) {
            $sqlCheckCompany = "SELECT 1 FROM kdd_companies 
                                WHERE id = ? AND authority_id = ? AND is_deleted = 0";
            $stmtCheckCompany = $pdo->prepare($sqlCheckCompany);
            $stmtCheckCompany->execute([$companyId, $authorityId]);
            
            if (!$stmtCheckCompany->fetchColumn()) {
                http_response_code(404);
                echo json_encode(["error" => "New company not found or access denied."]);
                return;
            }
        } else {
            // Use the original company_id if none provided or unchanged
            $companyId = (int)$extinguisher['company_id'];
        }
        
        // Check if another extinguisher with the same identifier exists for this company
        $sqlCheckIdentifier = "SELECT 1 FROM kdd_company_extinguishers 
                               WHERE company_id = ? AND identifier = ? AND id != ? AND is_deleted = 0";
        $stmtCheckIdentifier = $pdo->prepare($sqlCheckIdentifier);
        $stmtCheckIdentifier->execute([$companyId, $identifier, $id]);
        
        if ($stmtCheckIdentifier->fetchColumn()) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Another extinguisher with this identifier already exists for this company."]);
            return;
        }
        
        // Fetch current data for logging
        $sqlCurrentData = "SELECT * FROM kdd_company_extinguishers WHERE id = ?";
        $stmtCurrentData = $pdo->prepare($sqlCurrentData);
        $stmtCurrentData->execute([$id]);
        $currentData = $stmtCurrentData->fetch(PDO::FETCH_ASSOC);
        
        // Update the extinguisher
        $sql = "UPDATE kdd_company_extinguishers 
                SET company_id = ?, identifier = ?, registration_date = ?, 
                    last_inspection = ?, inspected_by = ?, location = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $companyId,
            $identifier,
            $registrationDate,
            $lastInspection,
            $inspectedBy,
            $location,
            $id
        ]);
        
        // Update extinguisher counts on affected companies
        $companiesToUpdate = [$companyId];
        if ($currentData['company_id'] != $companyId) {
            $companiesToUpdate[] = $currentData['company_id'];
        }
        
        foreach ($companiesToUpdate as $cid) {
            $sqlUpdateCount = "UPDATE kdd_companies 
                               SET extinguisher_count = (
                                   SELECT COUNT(*) FROM kdd_company_extinguishers 
                                   WHERE company_id = ? AND is_deleted = 0
                               ),
                               updated_at = NOW()
                               WHERE id = ? AND authority_id = ?";
            $stmtUpdateCount = $pdo->prepare($sqlUpdateCount);
            $stmtUpdateCount->execute([$cid, $cid, $authorityId]);
        }

        // Log the action - track changes
        $changes = [];
        if ($currentData['company_id'] != $companyId) {
            $changes[] = ['column_name' => 'company_id', 'old_value' => $currentData['company_id'], 'new_value' => $companyId];
        }
        if ($currentData['identifier'] !== $identifier) {
            $changes[] = ['column_name' => 'identifier', 'old_value' => $currentData['identifier'], 'new_value' => $identifier];
        }
        if ($currentData['registration_date'] !== $registrationDate) {
            $changes[] = ['column_name' => 'registration_date', 'old_value' => $currentData['registration_date'], 'new_value' => $registrationDate];
        }
        if ($currentData['last_inspection'] !== $lastInspection) {
            $changes[] = ['column_name' => 'last_inspection', 'old_value' => $currentData['last_inspection'], 'new_value' => $lastInspection];
        }
        if ($currentData['inspected_by'] !== $inspectedBy) {
            $changes[] = ['column_name' => 'inspected_by', 'old_value' => $currentData['inspected_by'], 'new_value' => $inspectedBy];
        }
        if ($currentData['location'] !== $location) {
            $changes[] = ['column_name' => 'location', 'old_value' => $currentData['location'], 'new_value' => $location];
        }

        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "company_extinguishers", $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Extinguisher updated successfully."
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in editExtinguisher ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to update extinguisher: " . $e->getMessage()]);
    }
}

function deleteExtinguisher(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    $id = (int)($data['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid extinguisher ID."]);
        return;
    }
    
    try {
        // Check if extinguisher exists and belongs to a company in this authority
        $sqlCheck = "SELECT e.company_id FROM kdd_company_extinguishers e
                     JOIN kdd_companies c ON e.company_id = c.id
                     WHERE e.id = ? AND c.authority_id = ? AND e.is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Extinguisher not found or already deleted."]);
            return;
        }
        
        $companyId = (int)$result['company_id'];

        $pdo->beginTransaction();

        // Get extinguisher data for logging before soft delete
        $sqlGetExtinguisher = "SELECT * FROM kdd_company_extinguishers WHERE id = ?";
        $stmtGetExtinguisher = $pdo->prepare($sqlGetExtinguisher);
        $stmtGetExtinguisher->execute([$id]);
        $extinguisherData = $stmtGetExtinguisher->fetch(PDO::FETCH_ASSOC);

        // Soft delete the extinguisher
        $sql = "UPDATE kdd_company_extinguishers SET is_deleted = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // Update the extinguisher count on the company
        $sqlUpdateCount = "UPDATE kdd_companies
                           SET extinguisher_count = (
                               SELECT COUNT(*) FROM kdd_company_extinguishers
                               WHERE company_id = ? AND is_deleted = 0
                           ),
                           updated_at = NOW()
                           WHERE id = ? AND authority_id = ?";
        $stmtUpdateCount = $pdo->prepare($sqlUpdateCount);
        $stmtUpdateCount->execute([$companyId, $companyId, $authorityId]);

        // Log the action
        if ($extinguisherData) {
            $changes = [
                ['column_name' => 'SOFT_DELETE', 'old_value' => json_encode($extinguisherData), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "company_extinguishers", $id, $userId, $changes);
        }

        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Extinguisher deleted successfully."
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteExtinguisher ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete extinguisher: " . $e->getMessage()]);
    }
}

function getCompanyHistory(PDO $pdo, string $authority, int $authorityId): void {
    $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    
    if ($companyId <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Company ID is required."]);
        return;
    }
    
    try {
        // Check if company exists and belongs to this authority
        $sqlCheckCompany = "SELECT 1 FROM kdd_companies 
                            WHERE id = ? AND authority_id = ?";
        $stmtCheckCompany = $pdo->prepare($sqlCheckCompany);
        $stmtCheckCompany->execute([$companyId, $authorityId]);
        
        if (!$stmtCheckCompany->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["error" => "Company not found or access denied."]);
            return;
        }
        
        $sql = "SELECT * FROM kdd_company_history 
                WHERE company_id = ? AND authority_id = ?
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $authorityId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($history);
        
    } catch (\PDOException $e) {
        error_log("DB error in getCompanyHistory ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve company history."]);
    }
}

function getFireExtinguisherNr(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT value FROM kdd_global_settings WHERE authority_id = ? AND key_name = 'next_fire_extinguisher_number'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextNumber = '1001'; // Default starting number
        if ($result && !empty($result['value'])) {
            $nextNumber = $result['value'];
        }
        
        http_response_code(200);
        echo json_encode(["next_number" => $nextNumber]);
        
    } catch (\PDOException $e) {
        error_log("DB error in getFireExtinguisherNr ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve next fire extinguisher number."]);
    }
}

function setFireExtinguisherNr(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getInputData();
    $nextNumber = trim($data['next_number'] ?? '');
    
    if (empty($nextNumber) || !preg_match('/^\d+$/', $nextNumber)) {
        http_response_code(400);
        echo json_encode(["error" => "Valid next number is required (digits only)."]);
        return;
    }
    
    try {
        // Check if the setting already exists
        $sqlCheck = "SELECT 1 FROM kdd_global_settings 
                    WHERE authority_id = ? AND key_name = 'next_fire_extinguisher_number'";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId]);
        $exists = $stmtCheck->fetchColumn();
        
        if ($exists) {
            $sql = "UPDATE kdd_global_settings 
                    SET value = ? 
                    WHERE authority_id = ? AND key_name = 'next_fire_extinguisher_number'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nextNumber, $authorityId]);
        } else {
            $sql = "INSERT INTO kdd_global_settings (authority_id, key_name, value) 
                    VALUES (?, 'next_fire_extinguisher_number', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId, $nextNumber]);
        }
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Next fire extinguisher number updated successfully."
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in setFireExtinguisherNr ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to update next fire extinguisher number: " . $e->getMessage()]);
    }
}

function addCompanyHistory(PDO $pdo, int $company_id, string $name, string $ceo, string $co_ceo, 
                          string $contact_person, string $control_center_number, int $authorityId): bool {
    try {
        $sql = "INSERT INTO kdd_company_history 
                (authority_id, company_id, name, ceo, co_ceo, contact_person, control_center_number, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $company_id, $name, $ceo, $co_ceo, $contact_person, $control_center_number]);
        return true;
    } catch (\PDOException $e) {
        error_log("DB error in addCompanyHistory: " . $e->getMessage());
        return false;
    }
}

?>
