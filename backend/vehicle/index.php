<?php
/**
 * Backend Endpoint: vehicle/index.php
 * Handles CRUD operations and status updates for Vehicles.
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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null || !is_int($authorityId)) {
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
    'getVehicles'       => ['module' => 'dispatch.vehicle', 'action' => 'read'],
    'addVehicle'        => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'editVehicle'       => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'activateVehicle'   => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'deactivateVehicle' => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'reportDamage'      => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'removeDamage'      => ['module' => 'dispatch.vehicle', 'action' => 'write'],
    'deleteVehicle'     => ['module' => 'dispatch.vehicle', 'action' => 'delete'],
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
    // Add PUT/DELETE checks if preferred

    switch ($action) {
        case 'getVehicles':         if ($request_method === 'GET') getVehicles($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addVehicle':          if ($is_post_request) addVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editVehicle':         if ($is_post_request) editVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteVehicle':       if ($is_post_request) deleteVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE
        case 'activateVehicle':     if ($is_post_request) activateVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT/PATCH
        case 'deactivateVehicle':   if ($is_post_request) deactivateVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT/PATCH
        case 'reportDamage':        if ($is_post_request) reportDamage($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'removeDamage':        if ($is_post_request) removeDamage($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();




// --- Function Implementations (PDO Refactored) ---

function getVehicles(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM `kdd_vehicles` WHERE authority_id = ? ORDER BY sort_order, title"; // Added sorting
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert boolean/tinyint fields for consistent JSON output
        foreach ($vehicles as &$vehicle) {
            $vehicle['active'] = (bool)$vehicle['active'];
            $vehicle['damage'] = (bool)$vehicle['damage'];
            // Cast numeric types if necessary
            $vehicle['id'] = (int)$vehicle['id'];
            $vehicle['sort_order'] = (int)$vehicle['sort_order'];
            $vehicle['dispatch_id'] = $vehicle['dispatch_id'] !== null ? (int)$vehicle['dispatch_id'] : null;
        }
        unset($vehicle); // Unset reference

        http_response_code(200);
        echo json_encode($vehicles);
    } catch (\PDOException $e) {
        error_log("DB error in getVehicles ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve vehicles."]);
    }
}

function addVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $title = $data['title'] ?? null;
    $numberplate = $data['numberplate'] ?? null;
    $rank = $data['rank'] ?? ''; // Rank might be optional
    $sort_order_input = $data['sort_order'] ?? null;

    if (empty($title) || empty($numberplate)) {
        http_response_code(400); echo json_encode(['error' => 'Title and numberplate are required.']); return;
    }

    try {
        $sort_order = 0; // Default sort order
        if ($sort_order_input !== null && $sort_order_input !== '') {
             $sort_order = filter_var($sort_order_input, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        } else {
            // Get the current max sort_order if not provided
            $stmtMax = $pdo->prepare("SELECT MAX(sort_order) FROM `kdd_vehicles` WHERE authority_id = ?");
            $stmtMax->execute([$authorityId]);
            $maxSort = $stmtMax->fetchColumn();
            $sort_order = ($maxSort === null) ? 0 : (int)$maxSort + 1;
        }

        $sql = "INSERT INTO `kdd_vehicles` (authority_id, title, numberplate, rank, sort_order, created_at, active)
                VALUES (?, ?, ?, ?, ?, NOW(), 1)"; // Default active=1
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $title, $numberplate, $rank, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Vehicle added successfully.", "id" => $newId]);
            // Log change
            $logData = json_encode([
                'title' => $title, 
                'numberplate' => $numberplate, 
                'rank' => $rank, 
                'sort_order' => $sort_order,
                'authority_id' => $authorityId
            ]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "vehicles", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add vehicle.']); }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') { // Check for unique constraint violation (e.g., numberplate?)
            http_response_code(409); echo json_encode(["error" => "Could not add vehicle: Numberplate might already exist."]);
        } else {
            error_log("DB error in addVehicle ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not add vehicle."]);
        }
    }
}

function editVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $title = $data['title'] ?? null;
    $numberplate = $data['numberplate'] ?? null;
    $rank = $data['rank'] ?? null; // Allow rank update
    $sort_order = filter_var($data['sort_order'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

    if (!$id || empty($title) || empty($numberplate)) {
        http_response_code(400); echo json_encode(['error' => 'ID, title and numberplate are required.']); return; 
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        $sql = "UPDATE `kdd_vehicles` SET title = ?, numberplate = ?, rank = ?, sort_order = ?, updated_at = NOW() WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        // Use default sort_order 0 if not provided or invalid
        $success = $stmt->execute([$title, $numberplate, $rank ?: '', $sort_order ?? 0, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle updated successfully."]);
            // Log change
            $newEntry = [
                'id' => $id,
                'title' => $title,
                'numberplate' => $numberplate,
                'rank' => $rank ?: '',
                'sort_order' => $sort_order ?? 0,
                'authority_id' => $authorityId
            ];
            $changes = getEntryChanges($oldEntry, $newEntry);
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicles", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["message" => "No changes made to the vehicle."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute vehicle update.']); }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') { // Check for unique constraint violation
            http_response_code(409); echo json_encode(["error" => "Could not update vehicle: Numberplate might already exist."]);
        } else {
            error_log("DB error in editVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not update vehicle."]);
        }
    }
}

function deleteVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing vehicle ID.']); return; 
    }

    try {
        // Get old entry for logging before deletion
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        // Check dependencies before deleting? E.g., assigned dispatch?
        $sqlCheck = "SELECT dispatch_id FROM `kdd_vehicles` WHERE authority_id = ? AND id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $dispatchId = $stmtCheck->fetchColumn();

        if ($dispatchId !== null && $dispatchId !== false) {
             http_response_code(409); // Conflict
             echo json_encode(["error" => "Cannot delete vehicle while it is assigned to a dispatch."]);
             return;
        }

        $sql = "DELETE FROM kdd_vehicles WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle deleted successfully."]);
            // Log deletion
            $changes = [['column_name'=>'row_data','old_value'=>json_encode($oldEntry),'new_value'=>null]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "vehicles", $id, $requestingUserId, $changes);
        } else {
            http_response_code(500); echo json_encode(['error' => 'Failed to execute vehicle deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete vehicle."]);
    }
}

function activateVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing vehicle ID.']); return; 
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        $sql = "UPDATE `kdd_vehicles` SET active = 1, updated_at = NOW() WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) { 
            http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle activated."]);
            // Log change
            $changes = [['column_name' => 'active', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicles", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["message" => "Vehicle not updated (not found or already active)."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to activate vehicle.']); }
    } catch (\PDOException $e) {
        error_log("DB error in activateVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not activate vehicle."]);
    }
}

function deactivateVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing vehicle ID.']); return; 
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        $sql = "UPDATE `kdd_vehicles` SET active = 0, updated_at = NOW() WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) { 
            http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle deactivated."]);
            // Log change
            $changes = [['column_name' => 'active', 'old_value' => 1, 'new_value' => 0]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicles", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["message" => "Vehicle not updated (not found or already inactive)."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to deactivate vehicle.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deactivateVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not deactivate vehicle."]);
    }
}

function reportDamage(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $vehicle_id = filter_var($data['vehicle_id'] ?? null, FILTER_VALIDATE_INT);
    $damage_officer = $data['damage_officer'] ?? null;
    $damage_description = $data['damage_description'] ?? null;

    if (!$vehicle_id || empty($damage_officer) || empty($damage_description)) {
        http_response_code(400); echo json_encode(['error' => 'Vehicle ID, Damage Officer name, and Description are required.']); return;
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $vehicle_id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        $sql = "UPDATE `kdd_vehicles` SET damage = 1, damage_officer = ?, damage_description = ?, damage_time = NOW(),
                    updated_at = NOW()
                WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$damage_officer, $damage_description, $authorityId, $vehicle_id]);

        if ($success && $stmt->rowCount() > 0) { 
            http_response_code(200); echo json_encode(["success" => true, "message" => "Damage reported successfully."]);
            // Log change
            $changes = [
                ['column_name' => 'damage', 'old_value' => 0, 'new_value' => 1],
                ['column_name' => 'damage_officer', 'old_value' => $oldEntry['damage_officer'], 'new_value' => $damage_officer],
                ['column_name' => 'damage_description', 'old_value' => $oldEntry['damage_description'], 'new_value' => $damage_description]
            ];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicles", $vehicle_id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["message" => "Damage not reported (vehicle not found or already marked as damaged)."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to report damage.']); }
    } catch (\PDOException $e) {
        error_log("DB error in reportDamage (ID: $vehicle_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not report damage."]);
    }
}

function removeDamage(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    
    $vehicle_id = filter_var($data['vehicle_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$vehicle_id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing vehicle ID.']); return; 
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $vehicle_id, "kdd_vehicles", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        $sql = "UPDATE `kdd_vehicles` SET damage = 0, damage_officer = NULL, damage_description = NULL, damage_time = NULL,
                    updated_at = NOW()
                WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $vehicle_id]);

        if ($success && $stmt->rowCount() > 0) { 
            http_response_code(200); echo json_encode(["success" => true, "message" => "Damage removed successfully."]);
            // Log change
            $changes = [
                ['column_name' => 'damage', 'old_value' => 1, 'new_value' => 0],
                ['column_name' => 'damage_officer', 'old_value' => $oldEntry['damage_officer'], 'new_value' => null],
                ['column_name' => 'damage_description', 'old_value' => $oldEntry['damage_description'], 'new_value' => null]
            ];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicles", $vehicle_id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["message" => "Damage status not changed (vehicle not found or not marked as damaged)."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to remove damage status.']); }
    } catch (\PDOException $e) {
        error_log("DB error in removeDamage (ID: $vehicle_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not remove damage status."]);
    }
}

?>