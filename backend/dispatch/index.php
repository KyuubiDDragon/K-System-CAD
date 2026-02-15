<?php
/**
 * Backend Endpoint: dispatch/index.php
 * Handles CRUD operations for Dispatches (Crews) and assignment of employees/vehicles.
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
if (!hasFeatureAccess($pdo, $authorityId, 'dispatch')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to dispatch features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getDispatches'  => ['module' => 'dispatch', 'action' => 'read'],
    'getEmployees'   => ['module' => 'dispatch', 'action' => 'read'],
    'getVehicles'    => ['module' => 'dispatch', 'action' => 'read'],
    'addDispatch'    => ['module' => 'dispatch', 'action' => 'write'],
    'editDispatch'   => ['module' => 'dispatch', 'action' => 'write'],
    'assignEmployee' => ['module' => 'dispatch', 'action' => 'write'],
    'assignVehicle'  => ['module' => 'dispatch', 'action' => 'write'],
    'saveDispatch'   => ['module' => 'dispatch', 'action' => 'write'],
    'deleteDispatch' => ['module' => 'dispatch', 'action' => 'delete'],
    'toggleAbsent'   => ['module' => 'dispatch', 'action' => 'write'],
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
        case 'getDispatches':  if ($request_method === 'GET') getDispatches($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEmployees':   if ($request_method === 'GET') getEmployees($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getVehicles':    if ($request_method === 'GET') getVehicles($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addDispatch':    if ($is_post_request) addDispatch($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editDispatch':   if ($is_post_request) editDispatch($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteDispatch': if ($is_post_request) deleteDispatch($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'assignEmployee': if ($is_post_request) assignEmployee($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'assignVehicle':  if ($is_post_request) assignVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveDispatch':   if ($is_post_request) saveDispatch($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'toggleAbsent':   if ($is_post_request) toggleAbsent($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getStats':       if ($request_method === 'GET') getStats($pdo, $authority, $authorityId); else MethodNotAllowed(); break; // Dashboard widget
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



// --- Function Implementations (PDO Refactored) ---

function getDispatches(PDO $pdo, string $authority, int $authorityId): void {
    $dispatches = [];
    $employees = [];
    $vehicles = [];

    try {
        $sqlDispatches = "SELECT * FROM kdd_dispatch WHERE authority_id = ? AND active = 1 ORDER BY sort_order, name";
        $stmtDispatches = $pdo->prepare($sqlDispatches);
        $stmtDispatches->execute([$authorityId]);
        $dispatchRows = $stmtDispatches->fetchAll(PDO::FETCH_ASSOC);

        if (empty($dispatchRows)) {
            http_response_code(200); 
            echo json_encode([]); 
            return;
        }

        $dispatchIds = [];
        foreach ($dispatchRows as $row) {
            $row['employees'] = [];
            $row['vehicles'] = [];
            $dispatches[$row['id']] = $row;
            $dispatchIds[] = $row['id'];
        }

        $sqlEmployees = "SELECT e.id, e.name, e.servicenumber, r.name as rank_name, e.dispatch_id, e.is_absent
                         FROM kdd_employee e
                         LEFT JOIN kdd_employee_rank r ON r.id = e.rank_id
                         WHERE e.authority_id = ? AND e.is_terminated = 0";
        $stmtEmployees = $pdo->prepare($sqlEmployees);
        $stmtEmployees->execute([$authorityId]);
        $allEmployees = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);

        $sqlVehicles = "SELECT id, title, numberplate, dispatch_id, rank 
                        FROM kdd_vehicles 
                        WHERE authority_id = ? AND active = 1";
        $stmtVehicles = $pdo->prepare($sqlVehicles);
        $stmtVehicles->execute([$authorityId]);
        $allVehicles = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allEmployees as $employee) {
            if ($employee['dispatch_id'] !== null && isset($dispatches[$employee['dispatch_id']])) {
                $dispatches[$employee['dispatch_id']]['employees'][] = $employee;
            }
        }
        foreach ($allVehicles as $vehicle) {
             if ($vehicle['dispatch_id'] !== null && isset($dispatches[$vehicle['dispatch_id']])) {
                $dispatches[$vehicle['dispatch_id']]['vehicles'][] = $vehicle;
            }
        }

        http_response_code(200);
        echo json_encode(array_values($dispatches));

    } catch (\PDOException $e) {
        error_log("DB error in getDispatches ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve dispatch data."]);
    }
}

function addDispatch(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $name = $data['name'] ?? null;
    $status = $data['status'] ?? 'Frei';
    $sort_order = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

    if (empty($name)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Dispatch name is required.']); 
        return; 
    }

    try {
        $sql = "INSERT INTO kdd_dispatch (authority_id, name, status, sort_order, created_at, active) 
                VALUES (?, ?, ?, ?, NOW(), 1)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $status, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); // Created
            echo json_encode(["success" => true, "message" => "Dispatch added successfully.", "id" => $newId]);
            
            // Log change
            $logData = json_encode(['name' => $name, 'status' => $status, 'sort_order' => $sort_order]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "dispatch", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to add dispatch.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addDispatch ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not add dispatch: " . $e->getMessage()]);
    }
}

function editDispatch(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $status = $data['status'] ?? null;
    $sort_order = filter_var($data['sort_order'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

    if ($id === false || $id <= 0 || empty($name)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Valid dispatch ID and name are required.']); 
        return;
    }

    try {
        // Get current dispatch data for logging
        $sqlCurrent = "SELECT * FROM kdd_dispatch WHERE id = ? AND authority_id = ?";
        $stmtCurrent = $pdo->prepare($sqlCurrent);
        $stmtCurrent->execute([$id, $authorityId]);
        $currentData = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentData) {
            http_response_code(404); 
            echo json_encode(['error' => 'Dispatch not found.']); 
            return;
        }

        $sql = "UPDATE kdd_dispatch SET name = ?, status = ?, sort_order = ?, updated_at = NOW() 
                WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $status, $sort_order, $id, $authorityId]);

        if ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Dispatch updated successfully."]);
            
            // Log changes
            $changes = [];
            if ($currentData['name'] !== $name) {
                $changes[] = ['column_name' => 'name', 'old_value' => $currentData['name'], 'new_value' => $name];
            }
            if ($currentData['status'] !== $status) {
                $changes[] = ['column_name' => 'status', 'old_value' => $currentData['status'], 'new_value' => $status];
            }
            if ($currentData['sort_order'] != $sort_order) {
                $changes[] = ['column_name' => 'sort_order', 'old_value' => $currentData['sort_order'], 'new_value' => $sort_order];
            }
            
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "dispatch", $id, $requestingUserId, $changes);
            }
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to update dispatch.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in editDispatch ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not update dispatch: " . $e->getMessage()]);
    }
}

function deleteDispatch(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if ($id === false || $id <= 0) {
        http_response_code(400); 
        echo json_encode(['error' => 'Valid dispatch ID is required.']); 
        return;
    }

    try {
        // Check if dispatch exists
        $sqlCheck = "SELECT 1 FROM kdd_dispatch WHERE id = ? AND authority_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id, $authorityId]);
        if (!$stmtCheck->fetchColumn()) {
            http_response_code(404); 
            echo json_encode(['error' => 'Dispatch not found.']); 
            return;
        }

        // Soft delete or permanently delete based on your design
        $sql = "UPDATE kdd_dispatch SET active = 0, updated_at = NOW() WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id, $authorityId]);

        if ($success) {
            // Get employees assigned to this dispatch for logging
            $sqlGetEmployees = "SELECT id, dispatch_id FROM kdd_employee WHERE dispatch_id = ? AND authority_id = ?";
            $stmtGetEmployees = $pdo->prepare($sqlGetEmployees);
            $stmtGetEmployees->execute([$id, $authorityId]);
            $assignedEmployees = $stmtGetEmployees->fetchAll(PDO::FETCH_ASSOC);

            // Get vehicles assigned to this dispatch for logging
            $sqlGetVehicles = "SELECT id, dispatch_id FROM kdd_vehicles WHERE dispatch_id = ? AND authority_id = ?";
            $stmtGetVehicles = $pdo->prepare($sqlGetVehicles);
            $stmtGetVehicles->execute([$id, $authorityId]);
            $assignedVehicles = $stmtGetVehicles->fetchAll(PDO::FETCH_ASSOC);

            // Reset dispatch_id for any employees and vehicles assigned to this dispatch
            $sqlResetEmployees = "UPDATE kdd_employee SET dispatch_id = NULL WHERE dispatch_id = ? AND authority_id = ?";
            $stmtResetEmployees = $pdo->prepare($sqlResetEmployees);
            $stmtResetEmployees->execute([$id, $authorityId]);

            // Log each employee dispatch_id reset
            foreach ($assignedEmployees as $employee) {
                $changes = [
                    ['column_name' => 'dispatch_id', 'old_value' => $employee['dispatch_id'], 'new_value' => null]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee['id'], $requestingUserId, $changes);
            }

            $sqlResetVehicles = "UPDATE kdd_vehicles SET dispatch_id = NULL WHERE dispatch_id = ? AND authority_id = ?";
            $stmtResetVehicles = $pdo->prepare($sqlResetVehicles);
            $stmtResetVehicles->execute([$id, $authorityId]);

            // Log each vehicle dispatch_id reset
            foreach ($assignedVehicles as $vehicle) {
                $changes = [
                    ['column_name' => 'dispatch_id', 'old_value' => $vehicle['dispatch_id'], 'new_value' => null]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_vehicles", $vehicle['id'], $requestingUserId, $changes);
            }

            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Dispatch deleted successfully."]);

            // Log deletion
            $changes = [['column_name' => 'active', 'old_value' => '1', 'new_value' => '0']];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "dispatch", $id, $requestingUserId, $changes);
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to delete dispatch.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteDispatch ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not delete dispatch: " . $e->getMessage()]);
    }
}

function assignEmployee(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $employee_id = filter_var($data['employee_id'] ?? null, FILTER_VALIDATE_INT);
    $dispatch_id_input = $data['dispatch_id'] ?? null;
    $dispatch_id = null;
    if ($dispatch_id_input !== null && $dispatch_id_input !== "0" && $dispatch_id_input !== "") {
         $dispatch_id = filter_var($dispatch_id_input, FILTER_VALIDATE_INT);
         if ($dispatch_id === false || $dispatch_id <= 0) {
             http_response_code(400); echo json_encode(['error' => 'Invalid dispatch ID provided.']); return;
         }
    }

    if ($employee_id === false || $employee_id <= 0) { http_response_code(400); echo json_encode(['error' => 'Valid Employee ID is required.']); return; }

    try {
        // Get old dispatch_id and is_absent for logging
        $sqlOld = "SELECT dispatch_id, is_absent FROM kdd_employee WHERE id = ?";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$employee_id]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $oldDispatchId = $oldData['dispatch_id'] ?? null;
        $oldIsAbsent = $oldData['is_absent'] ?? 0;

        // When assigning to a new dispatch, reset is_absent to 0
        $sql = "UPDATE kdd_employee SET dispatch_id = ?, is_absent = 0 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $dispatch_id, $dispatch_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $employee_id, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Employee assignment updated."]);

            // Log dispatch_id change
            $changes = [];
            if ($oldDispatchId != $dispatch_id) {
                $changes[] = ['column_name' => 'dispatch_id', 'old_value' => $oldDispatchId, 'new_value' => $dispatch_id];
            }
            if ($oldIsAbsent != 0) {
                $changes[] = ['column_name' => 'is_absent', 'old_value' => $oldIsAbsent, 'new_value' => 0];
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee_id, $requestingUserId, $changes);
            }
        } else {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Employee assignment updated."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in assignEmployee (EmpID: $employee_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not assign employee."]);
    }
}

function assignVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $vehicle_id = filter_var($data['vehicle_id'] ?? null, FILTER_VALIDATE_INT);
    $dispatch_id_input = $data['dispatch_id'] ?? null;
    $dispatch_id = null;
    
    if ($dispatch_id_input !== null && $dispatch_id_input !== "0" && $dispatch_id_input !== "") {
        $dispatch_id = filter_var($dispatch_id_input, FILTER_VALIDATE_INT);
        if ($dispatch_id === false || $dispatch_id <= 0) { 
            http_response_code(400); 
            echo json_encode(['error' => 'Invalid dispatch ID provided.']); 
            return; 
        }
    }

    if ($vehicle_id === false || $vehicle_id <= 0) { 
        http_response_code(400); 
        echo json_encode(['error' => 'Valid Vehicle ID is required.']); 
        return; 
    }

    try {
        // Get old dispatch_id for logging
        $sqlOld = "SELECT dispatch_id FROM kdd_vehicles WHERE id = ?";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$vehicle_id]);
        $oldDispatchId = $stmtOld->fetchColumn();

        $sql = "UPDATE kdd_vehicles SET dispatch_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $dispatch_id, $dispatch_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $vehicle_id, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Vehicle assignment updated."]);

            // Log dispatch_id change
            if ($oldDispatchId != $dispatch_id) {
                $changes = [
                    ['column_name' => 'dispatch_id', 'old_value' => $oldDispatchId, 'new_value' => $dispatch_id]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_vehicles", $vehicle_id, $requestingUserId, $changes);
            }
        } else {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Vehicle assignment updated."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in assignVehicle (VehID: $vehicle_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not assign vehicle."]);
    }
}

function saveDispatch(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $dispatch_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $employees = $data['employees'] ?? [];
    $vehicles = $data['vehicles'] ?? [];
    
    if ($dispatch_id === false || $dispatch_id <= 0) {
        http_response_code(400); 
        echo json_encode(['error' => 'Valid dispatch ID is required.']); 
        return;
    }

    try {
        // Check if dispatch exists and belongs to this authority
        $sqlCheckDispatch = "SELECT 1 FROM kdd_dispatch WHERE id = ? AND authority_id = ? AND active = 1";
        $stmtCheckDispatch = $pdo->prepare($sqlCheckDispatch);
        $stmtCheckDispatch->execute([$dispatch_id, $authorityId]);
        if (!$stmtCheckDispatch->fetchColumn()) {
            http_response_code(404); 
            echo json_encode(['error' => 'Dispatch not found or inactive.']); 
            return;
        }
        
        // Begin transaction
        $pdo->beginTransaction();

        // Get current employees assigned to this dispatch for logging
        $sqlGetOldEmployees = "SELECT id, dispatch_id FROM kdd_employee WHERE dispatch_id = ? AND authority_id = ?";
        $stmtGetOldEmployees = $pdo->prepare($sqlGetOldEmployees);
        $stmtGetOldEmployees->execute([$dispatch_id, $authorityId]);
        $oldEmployees = $stmtGetOldEmployees->fetchAll(PDO::FETCH_ASSOC);

        // Clear all employee assignments for this dispatch
        $sqlClearEmployees = "UPDATE kdd_employee SET dispatch_id = NULL WHERE dispatch_id = ? AND authority_id = ?";
        $stmtClearEmployees = $pdo->prepare($sqlClearEmployees);
        $stmtClearEmployees->execute([$dispatch_id, $authorityId]);

        // Log cleared employee assignments
        foreach ($oldEmployees as $employee) {
            $changes = [
                ['column_name' => 'dispatch_id', 'old_value' => $employee['dispatch_id'], 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee['id'], $requestingUserId, $changes);
        }

        // Assign selected employees to this dispatch
        if (!empty($employees)) {
            $employeeIds = array_map(function($employee) {
                return filter_var($employee['id'] ?? 0, FILTER_VALIDATE_INT);
            }, $employees);

            // Filter out invalid IDs
            $employeeIds = array_filter($employeeIds);

            if (!empty($employeeIds)) {
                $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
                $sqlAssignEmployees = "UPDATE kdd_employee
                                     SET dispatch_id = ?, updated_at = NOW()
                                     WHERE id IN ($placeholders) AND authority_id = ?";
                $stmtAssignEmployees = $pdo->prepare($sqlAssignEmployees);
                $params = array_merge([$dispatch_id], $employeeIds, [$authorityId]);
                $stmtAssignEmployees->execute($params);

                // Log each new employee assignment
                foreach ($employeeIds as $empId) {
                    $changes = [
                        ['column_name' => 'dispatch_id', 'old_value' => null, 'new_value' => $dispatch_id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $empId, $requestingUserId, $changes);
                }
            }
        }

        // Get current vehicles assigned to this dispatch for logging
        $sqlGetOldVehicles = "SELECT id, dispatch_id FROM kdd_vehicles WHERE dispatch_id = ? AND authority_id = ?";
        $stmtGetOldVehicles = $pdo->prepare($sqlGetOldVehicles);
        $stmtGetOldVehicles->execute([$dispatch_id, $authorityId]);
        $oldVehicles = $stmtGetOldVehicles->fetchAll(PDO::FETCH_ASSOC);

        // Clear all vehicle assignments for this dispatch
        $sqlClearVehicles = "UPDATE kdd_vehicles SET dispatch_id = NULL WHERE dispatch_id = ? AND authority_id = ?";
        $stmtClearVehicles = $pdo->prepare($sqlClearVehicles);
        $stmtClearVehicles->execute([$dispatch_id, $authorityId]);

        // Log cleared vehicle assignments
        foreach ($oldVehicles as $vehicle) {
            $changes = [
                ['column_name' => 'dispatch_id', 'old_value' => $vehicle['dispatch_id'], 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_vehicles", $vehicle['id'], $requestingUserId, $changes);
        }

        // Assign selected vehicles to this dispatch
        if (!empty($vehicles)) {
            $vehicleIds = array_map(function($vehicle) {
                return filter_var($vehicle['id'] ?? 0, FILTER_VALIDATE_INT);
            }, $vehicles);

            // Filter out invalid IDs
            $vehicleIds = array_filter($vehicleIds);

            if (!empty($vehicleIds)) {
                $placeholders = implode(',', array_fill(0, count($vehicleIds), '?'));
                $sqlAssignVehicles = "UPDATE kdd_vehicles
                                    SET dispatch_id = ?, updated_at = NOW()
                                    WHERE id IN ($placeholders) AND authority_id = ?";
                $stmtAssignVehicles = $pdo->prepare($sqlAssignVehicles);
                $params = array_merge([$dispatch_id], $vehicleIds, [$authorityId]);
                $stmtAssignVehicles->execute($params);

                // Log each new vehicle assignment
                foreach ($vehicleIds as $vehId) {
                    $changes = [
                        ['column_name' => 'dispatch_id', 'old_value' => null, 'new_value' => $dispatch_id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_vehicles", $vehId, $requestingUserId, $changes);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Dispatch assignments saved successfully."]);
    } catch (\PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("DB error in saveDispatch ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not save dispatch assignments: " . $e->getMessage()]);
    }
}

function getEmployees(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT e.id, e.name, e.servicenumber, r.name as rank_name, e.dispatch_id, e.is_absent
                FROM kdd_employee e
                LEFT JOIN kdd_employee_rank r ON r.id = e.rank_id
                WHERE e.authority_id = ? AND e.is_terminated = 0
                ORDER BY e.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($employees);
    } catch (\PDOException $e) {
        error_log("DB error in getEmployees ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve employees."]);
    }
}

function getVehicles(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT id, title, numberplate, dispatch_id, rank 
                FROM kdd_vehicles 
                WHERE authority_id = ? AND active = 1
                ORDER BY title";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($vehicles);
    } catch (\PDOException $e) {
        error_log("DB error in getVehicles ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve vehicles."]);
    }
}

/**
 * Get dispatch statistics for dashboard widget
 * Returns: active crews, available vehicles, active incidents (if applicable)
 */
function getStats(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Active crews (dispatches with active = 1)
        $sqlActiveCrews = "SELECT COUNT(*) FROM kdd_dispatch
                           WHERE authority_id = ? AND active = 1";
        $stmtActiveCrews = $pdo->prepare($sqlActiveCrews);
        $stmtActiveCrews->execute([$authorityId]);
        $activeCrews = (int)$stmtActiveCrews->fetchColumn();

        // Total crews
        $sqlTotalCrews = "SELECT COUNT(*) FROM kdd_dispatch WHERE authority_id = ?";
        $stmtTotalCrews = $pdo->prepare($sqlTotalCrews);
        $stmtTotalCrews->execute([$authorityId]);
        $totalCrews = (int)$stmtTotalCrews->fetchColumn();

        // Available vehicles (assuming status = 'available' or similar)
        // Note: Adjust this query based on your vehicle status field
        $sqlVehicles = "SELECT COUNT(*) FROM kdd_vehicles
                        WHERE authority_id = ?";
        $stmtVehicles = $pdo->prepare($sqlVehicles);
        $stmtVehicles->execute([$authorityId]);
        $availableVehicles = (int)$stmtVehicles->fetchColumn();

        // Active incidents (if you have an incidents table, otherwise set to 0)
        // This is a placeholder - adjust based on your actual incident tracking
        $activeIncidents = 0;

        http_response_code(200);
        echo json_encode([
            'activeCrews' => $activeCrews,
            'totalCrews' => $totalCrews,
            'availableVehicles' => $availableVehicles,
            'activeIncidents' => $activeIncidents
        ]);
    } catch (\PDOException $e) {
        error_log("Database error in getStats (authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve dispatch statistics."]);
    }
}

/**
 * Toggle employee absence status in dispatch
 * Toggles the is_absent field for an employee
 */
function toggleAbsent(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();

    $employee_id = filter_var($data['employee_id'] ?? null, FILTER_VALIDATE_INT);

    if ($employee_id === false || $employee_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid Employee ID is required.']);
        return;
    }

    try {
        // Check if employee exists and belongs to this authority
        $sqlCheck = "SELECT id, is_absent, dispatch_id FROM kdd_employee WHERE id = ? AND authority_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$employee_id, $authorityId]);
        $employee = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found.']);
            return;
        }

        // Toggle the is_absent status
        $newStatus = $employee['is_absent'] ? 0 : 1;

        $sql = "UPDATE kdd_employee SET is_absent = ? WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$newStatus, $employee_id, $authorityId]);

        if ($success) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => $newStatus ? "Employee marked as absent." : "Employee marked as present.",
                "is_absent" => $newStatus
            ]);

            // Log the change
            $changes = [
                ['column_name' => 'is_absent', 'old_value' => $employee['is_absent'], 'new_value' => $newStatus]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee_id, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update employee absence status.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in toggleAbsent (EmpID: $employee_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update employee absence status."]);
    }
}

?>