<?php
/**
 * Backend Endpoint: admin/employee/index.php
 * Handles ADMIN CRUD operations for Employee Companies and Departments.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange etc.
require_once __DIR__ . '/../../utils/permission_helper.php';

// --- Role Check ---
$userPermissions = $decoded_jwt->permissions ?? [];
if (!hasPermission($userPermissions, 'ADMIN') && !hasAllPermissions($userPermissions)) {
    http_response_code(403); echo json_encode(["error" => "Forbidden. Admin role or ALL_PERMISSIONS required."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to employee features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getCompanies'      => 'ADMIN_READ_EMPLOYEE', // Assume reading companies/depts requires reading employee data
    'getDepartments'    => 'ADMIN_READ_EMPLOYEE',
    'getEmployees'      => 'ADMIN_READ_EMPLOYEE', // Added getEmployees permission
    'saveCompany'       => 'ADMIN_WRITE_EMPLOYEE',
    'saveDepartment'    => 'ADMIN_WRITE_EMPLOYEE',
    'deleteCompany'     => 'ADMIN_WRITE_EMPLOYEE', // Original code used WRITE for delete actions here
    'deleteDepartment'  => 'ADMIN_WRITE_EMPLOYEE', // Original code used WRITE for delete actions here
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels (ALL > ADMIN_WRITE > ADMIN_READ)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission
} elseif ($required_permission === 'ADMIN_READ_EMPLOYEE' && hasPermission($userPermissions, 'ADMIN_WRITE_EMPLOYEE')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getCompanies':        if ($request_method === 'GET') getCompanies($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getDepartments':      if ($request_method === 'GET') getDepartments($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEmployees':        if ($request_method === 'GET') getEmployees($pdo, $authority, $authorityId); else MethodNotAllowed(); break; // Added getEmployees case
        case 'saveCompany':         if ($is_post_request) saveCompany($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveDepartment':      if ($is_post_request) saveDepartment($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteCompany':       if ($is_post_request) deleteCompany($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE
        case 'deleteDepartment':    if ($is_post_request) deleteDepartment($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

function getCompanies(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM kdd_employee_company WHERE authority_id = ? AND is_deleted = 0 ORDER BY sort_order, name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($companies);
    } catch (\PDOException $e) {
        error_log("DB error in getCompanies [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve companies."]);
    }
}

function getDepartments(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM kdd_employee_department WHERE authority_id = ? AND is_deleted = 0 ORDER BY sort_order, name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($departments);
    } catch (\PDOException $e) {
        error_log("DB error in getDepartments [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve departments."]);
    }
}

function saveCompany(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $sort_order = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

    if (empty(trim($name ?? ''))) {
        http_response_code(400); 
        echo json_encode(['error' => 'Company name is required.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();
        $logAction = '';
        $logId = $id;
        // $oldEntry = null; // For logging changes

        if ($id) { // Update existing
            $logAction = 'UPDATE';
            // $oldEntry = getEntryById($pdo, $id, "kdd_employee_company");
            // if (!$oldEntry) { throw new \Exception("Company with ID {$id} not found."); }

            $sql = "UPDATE kdd_employee_company SET name = ?, sort_order = ? WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$name, $sort_order, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update company.");
            $rowCount = $stmt->rowCount();

        } else { // Insert new
            $logAction = 'INSERT';
            $sql = "INSERT INTO kdd_employee_company (authority_id, name, sort_order) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $name, $sort_order]);
            if (!$success) throw new \Exception("Failed to insert company.");
            $logId = $pdo->lastInsertId();
            $rowCount = 1; // Indicate success for insert
        }

        $pdo->commit();

        if ($rowCount > 0) {
            http_response_code($id ? 200 : 201); // OK or Created
            echo json_encode(["success" => true, "message" => "Company saved successfully.", "id" => $logId]);
            // Log change
            // $changes = ... ; logDatabaseChange(...);
        } else {
            // Success technically, but no rows affected on update
            http_response_code(200); echo json_encode(["error" => "No changes made to the company."]);
        }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Handle potential unique name constraint
            http_response_code(409); echo json_encode(["error" => "Could not save company: Name might already exist."]);
         } else {
            error_log("Error in saveCompany [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save company: " . $e->getMessage()]);
         }
    }
}

function saveDepartment(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $sort_order = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

    if (empty(trim($name ?? ''))) {
        http_response_code(400); 
        echo json_encode(['error' => 'Department name is required.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();
        $logAction = '';
        $logId = $id;
        // $oldEntry = null;

        if ($id) { // Update
            $logAction = 'UPDATE';
             // $oldEntry = getEntryById($pdo, $id, "kdd_employee_department");
             // if (!$oldEntry) { throw new \Exception("Department with ID {$id} not found."); }

            $sql = "UPDATE kdd_employee_department SET name = ?, sort_order = ? WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$name, $sort_order, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update department.");
            $rowCount = $stmt->rowCount();

        } else { // Insert
            $logAction = 'INSERT';
            $sql = "INSERT INTO kdd_employee_department (authority_id, name, sort_order) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $name, $sort_order]);
             if (!$success) throw new \Exception("Failed to insert department.");
            $logId = $pdo->lastInsertId();
            $rowCount = 1;
        }

        $pdo->commit();

        if ($rowCount > 0) {
            http_response_code($id ? 200 : 201);
            echo json_encode(["success" => true, "message" => "Department saved successfully.", "id" => $logId]);
            // Log change
            // $changes = ... ; logDatabaseChange(...);
        } else {
            http_response_code(200); echo json_encode(["error" => "No changes made to the department."]);
        }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') {
            http_response_code(409); echo json_encode(["error" => "Could not save department: Name might already exist."]);
         } else {
            error_log("Error in saveDepartment [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save department: " . $e->getMessage()]);
         }
    }
}


function deleteCompany(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing company ID.']); 
        return; 
    }

    try {
         // Optional: Check if company is assigned to any non-deleted employees before deleting?
         // $stmtCheck = $pdo->prepare("SELECT 1 FROM kdd_employee_company_rel WHERE authority_id = ? AND company_id = ? LIMIT 1");
         // $stmtCheck->execute([$authorityId, $id]);
         // if ($stmtCheck->fetchColumn()) { ... return error ... }

        // Soft delete
        $sql = "UPDATE kdd_employee_company SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Company marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "employee_company", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Company not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute company deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCompany (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete company."]);
    }
}


function deleteDepartment(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing department ID.']); 
        return; 
    }

    try {
         // Optional: Check dependencies (employees assigned) before deleting?

        // Soft delete
        $sql = "UPDATE kdd_employee_department SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Department marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "employee_department", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Department not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute department deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteDepartment (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete department."]);
    }
}

function getEmployees(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT id, name, servicenumber FROM kdd_employee 
                WHERE authority_id = ? AND !is_terminated
                ORDER BY servicenumber";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($employees);
    } catch (\PDOException $e) {
        error_log("DB error in getEmployees [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve employees."]);
    }
}


?>