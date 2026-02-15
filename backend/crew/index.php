<?php
/**
 * Backend Endpoint: crew/index.php
 * Handles CRUD operations and status updates for Crews/Dispatch Units.
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
if (!hasFeatureAccess($pdo, $authorityId, 'dispatch')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to dispatch features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getCrews'             => ['module' => 'dispatch.crew', 'action' => 'read'],
    'addCrew'              => ['module' => 'dispatch.crew', 'action' => 'write'],
    'editCrew'             => ['module' => 'dispatch.crew', 'action' => 'write'],
    'deleteCrew'           => ['module' => 'dispatch.crew', 'action' => 'delete'],
    'updateCrewStatus'     => ['module' => 'dispatch.crew', 'action' => 'write'],
    'toggleCrewActivation' => ['module' => 'dispatch.crew', 'action' => 'write'],
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
    // Add PUT/DELETE checks if preferred for semantic methods
    // $is_put_request = ($request_method === 'PUT');
    // $is_delete_request = ($request_method === 'DELETE');

    switch ($action) {
        case 'getCrews':             if ($request_method === 'GET') getCrews($pdo, $authority); else MethodNotAllowed(); break;
        case 'addCrew':              if ($is_post_request) addCrew($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'editCrew':             if ($is_post_request) editCrew($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteCrew':           if ($is_post_request) deleteCrew($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE
        case 'updateCrewStatus':     if ($is_post_request) updateCrewStatus($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT/PATCH
        case 'toggleCrewActivation': if ($is_post_request) toggleCrewActivation($pdo, $userId, $authority); else MethodNotAllowed(); break;
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





// --- Function Implementations (PDO Refactored) ---

function getCrews(PDO $pdo, string $authority): void {
    try {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        // Fetch all columns, oder spezifiziere benötigte
        $sql = "SELECT * FROM `kdd_dispatch` WHERE authority_id = ? ORDER BY sort_order, name"; // Add default sorting
        $stmt = $pdo->prepare($sql); // Prepare the query first
        $stmt->execute([$authorityId]);
        $crews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($crews);
    } catch (\PDOException $e) {

        error_log("DB error in getCrews ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve crews."]);
    }
}

function addCrew(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();

    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    
    $name = $data['name'] ?? null;
    $status = $data['status'] ?? 'Frei'; // Default status?
    $sort_order = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

    if (empty($name)) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        http_response_code(400); echo json_encode(['error' => 'Crew name is required.']); return;
    }

    try {
        $sql = "INSERT INTO `kdd_dispatch` (authority_id, name, status, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        // Assuming status can be empty string if not provided, adjust if needed
        $success = $stmt->execute([$authorityId, $name, $status ?: 'Frei', $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); // Created
            echo json_encode(["success" => true, "message" => "Crew added successfully.", "id" => $newId]);
            // Log change
            $logData = json_encode(['name' => $name, 'status' => $status ?: 'Frei', 'sort_order' => $sort_order]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "dispatch", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500); echo json_encode(['error' => 'Failed to add crew.']);
        }
    } catch (\PDOException $e) {
         // Check for specific errors like duplicate entry if name should be unique
         // if ($e->getCode() == '23000') { ... }
        error_log("DB error in addCrew ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add crew."]);
    }
}

function editCrew(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $status = $data['status'] ?? null; // Allow updating status
    $sort_order = filter_var($data['sort_order'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

    if ($id === false || $id <= 0 || empty($name)) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        http_response_code(400); echo json_encode(['error' => 'Valid ID and name are required.']); return;
    }
     // Ensure status is not empty if provided, or handle NULL if allowed
     if (isset($status) && $status === '') { $status = null; } // Example: Treat empty string as NULL if DB allows

    try {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        // Fetch old data for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_dispatch"); // Assuming this helper is available & PDO ready

        // Build SET part dynamically? Or update all provided fields.
        $sql = "UPDATE `kdd_dispatch` SET name = ?, status = ?, sort_order = ?, updated_at = NOW() WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        // Use default values if not provided? Here we use what's passed or default from variable init.
        $success = $stmt->execute([$name, $status, $sort_order ?? 0, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Crew updated successfully."]);
             // Log change if helpers are available
             if ($oldEntry) {
                 $updatedData = ['id' => $id, 'name' => $name, 'status' => $status, 'sort_order' => $sort_order ?? 0];
                 $changes = getEntryChanges($oldEntry, $updatedData);
                 if (!empty($changes)) { global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "dispatch", $id, $requestingUserId, $changes); }
             }
        } elseif ($success) {
            http_response_code(200); echo json_encode(["error" => "No changes made to the crew."]);
        } else {
            http_response_code(500); echo json_encode(['error' => 'Failed to execute crew update.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in editCrew (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update crew."]);
    }
}

function deleteCrew(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        http_response_code(400); echo json_encode(['error' => 'Invalid or missing crew ID.']); return;
    }

    try {
        // Optional: Fetch old data for logging before deleting
        // $oldEntry = getEntryById($pdo, $id, "kdd_dispatch");
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        // Perform hard delete
        $sql = "DELETE FROM kdd_dispatch WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Crew deleted successfully."]);
             // Log deletion if old data was fetched
             // if ($oldEntry) {
             //     $changes = [['column_name' => 'row_data', 'old_value' => json_encode($oldEntry), 'new_value' => null]];
             //     global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                $changes = [['column_name' => 'is_deleted', 'old_value' => '0', 'new_value' => '1']];
                logDatabaseChange($authorityId, $pdo, 'DELETE', "dispatch", $id, $requestingUserId, $changes);
             // }
        } elseif ($success) {
            http_response_code(404); echo json_encode(["error" => "Crew not found."]);
        } else {
            http_response_code(500); echo json_encode(['error' => 'Failed to execute crew deletion.']);
        }
    } catch (\PDOException $e) {
         // Check for foreign key constraint errors if applicable
         // if ($e->getCode() == '23000') { ... }
        error_log("DB error in deleteCrew (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete crew. It might be in use."]);
    }
}

function updateCrewStatus(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $data['status'] ?? null; // Allow empty status? Assume required.

    if ($id === false || $id <= 0 || $status === null || $status === '') {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        http_response_code(400); echo json_encode(['error' => 'Valid ID and status are required.']); return;
    }

    try {
         // Optional: Fetch old status for logging
         // $stmtOld = $pdo->prepare("SELECT status FROM `kdd_dispatc` WHERE authority_id = ?h WHERE id = ?");
         // $stmtOld->execute([$id]);
         // $oldStatus = $stmtOld->fetchColumn();
         global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;

        $sql = "UPDATE `kdd_dispatch` SET status = ?, updated_at = NOW() WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$status, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Crew status updated successfully."]);
             // Log change
             // if ($oldStatus !== false && $oldStatus != $status) {
             //    $changes = [['column_name' => 'status', 'old_value' => $oldStatus, 'new_value' => $status]];
             //    global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                $changes = [['column_name' => 'status', 'old_value' => 'previous', 'new_value' => $status]];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "dispatch", $id, $requestingUserId, $changes);
             // }
        } elseif ($success) {
            http_response_code(200); echo json_encode(["error" => "Crew status not changed (already has this status or ID not found)."]);
        } else {
            http_response_code(500); echo json_encode(['error' => 'Failed to execute status update.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateCrewStatus (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update crew status."]);
    }
}

function toggleCrewActivation(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Toggle the active status
        // Use CASE statement or IF condition for toggling
        $sqlUpdateToggle = "UPDATE `kdd_dispatch` SET authority_id = ?, active = CASE WHEN active = 1 THEN 0 ELSE 1 END,
                                updated_at = NOW()
                            WHERE id = ?";
        $stmtToggle = $pdo->prepare($sqlUpdateToggle);
        $successToggle = $stmtToggle->execute([$authorityId, $id]);

        if (!$successToggle || $stmtToggle->rowCount() === 0) {
             throw new \Exception("Crew with ID $id not found or toggle failed.");
        }

        // 2. Check the new status
        $sqlCheckStatus = "SELECT active FROM `kdd_dispatch` WHERE authority_id = ? AND id = ?";
        $stmtCheckStatus = $pdo->prepare($sqlCheckStatus);
        $stmtCheckStatus->execute([$authorityId, $id]);
        $newActiveStatus = $stmtCheckStatus->fetchColumn();

        // 3. If now inactive (status = 0), clear assignments
        if ($newActiveStatus === 0) {
            // Update vehicles (set dispatch_id to NULL)
            $sqlVehicles = "UPDATE `kdd_vehicles` SET dispatch_id = NULL WHERE authority_id = ? AND dispatch_id = ?";
            $stmtVehicles = $pdo->prepare($sqlVehicles);
            $stmtVehicles->execute([$authorityId, $id]); // Ignore rowCount here, just clear assignments

            // Update employees (set dispatch_id to NULL)
            $sqlEmployees = "UPDATE `kdd_employee` SET dispatch_id = NULL WHERE authority_id = ? AND dispatch_id = ?";
            $stmtEmployees = $pdo->prepare($sqlEmployees);
            $stmtEmployees->execute([$authorityId, $id]); // Ignore rowCount here
        }

        // Commit transaction
        $pdo->commit();

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Crew activation status toggled successfully.", "new_status" => (int)$newActiveStatus]);

        // Log change
        $authorityId = $decoded_jwt->authority_id ?? 0;
        $changes = [['column_name' => 'active', 'old_value' => ($newActiveStatus ? 0 : 1), 'new_value' => (int)$newActiveStatus]];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "dispatch", $id, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) { // Catch PDO or general exceptions
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in toggleCrewActivation (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not toggle crew activation: " . $e->getMessage()]);
    }
}

?>