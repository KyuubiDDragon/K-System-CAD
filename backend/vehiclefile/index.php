<?php
/**
 * Backend Endpoint: vehiclefile/index.php
 * Handles CRUD operations for detailed Vehicle Files and person relations.
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
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID from token

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'vehicle_file')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to vehicle features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getVehicles'         => ['module' => 'vehicle.file', 'action' => 'read'],
    'getOwnVehicleCount'  => ['module' => 'vehicle.file', 'action' => 'read'],
    'addVehicle'          => ['module' => 'vehicle.file', 'action' => 'write'],
    'editVehicle'         => ['module' => 'vehicle.file', 'action' => 'write'],
    'deleteVehicle'       => ['module' => 'vehicle.file', 'action' => 'delete'],
    'getVehiclesByPerson' => ['module' => 'vehicle.file', 'action' => 'read'],
    'getPersons'          => ['module' => 'vehicle.file', 'action' => 'read'],
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
        // GET Actions
        case 'getVehicles':           if ($request_method === 'GET') getVehicles($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getOwnVehicleCount':    if ($request_method === 'GET') getOwnVehicleCount($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getVehiclesByPerson':   if ($request_method === 'GET') getVehiclesByPerson($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getPersons':            if ($request_method === 'GET') getPersons($pdo, $authority, $authorityId); else MethodNotAllowed(); break;

        // POST Actions (or PUT/DELETE)
        case 'addVehicle':            if ($is_post_request) addVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editVehicle':           if ($is_post_request) editVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteVehicle':         if ($is_post_request) deleteVehicle($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE

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

function getVehicles(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT v.*,
                       GROUP_CONCAT(DISTINCT CASE WHEN pr.type = 'owner' THEN pr.id_person END SEPARATOR ',') AS owners_str,
                       GROUP_CONCAT(DISTINCT CASE WHEN pr.type = 'driver' THEN pr.id_person END SEPARATOR ',') AS drivers_str
                FROM kdd_vehicle_file v
                LEFT JOIN `kdd_person_rel` pr ON pr.id_vehicle_file = v.id AND pr.authority_id = ?
                WHERE v.is_deleted = 0 AND v.authority_id = ?
                GROUP BY v.id
                ORDER BY v.brand, v.model"; // Add default sorting
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId]);
        $vehiclesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Post-process results
        $vehicles = array_map(function($row) {
            $row['owners'] = !empty($row['owners_str']) ? explode(',', $row['owners_str']) : [];
            $row['drivers'] = !empty($row['drivers_str']) ? explode(',', $row['drivers_str']) : [];
            unset($row['owners_str'], $row['drivers_str']);
            // Cast booleans
            $row['stolen'] = (bool)$row['stolen'];
            $row['wanted'] = (bool)$row['wanted'];
            $row['is_deleted'] = (bool)$row['is_deleted'];
            return $row;
        }, $vehiclesData);

        http_response_code(200);
        echo json_encode($vehicles);
    } catch (\PDOException $e) {
        error_log("DB error in getVehicles ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve vehicles."]);
    }
}

// Assumption: 'Own' means created by the user. Adjust SQL if 'owner' relation is meant.
function getOwnVehicleCount(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
     try {
        // Check if creator column exists, otherwise this query fails
        $sql = "SELECT COUNT(*) FROM `kdd_vehicle_file` WHERE authority_id = ? AND creator = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $requestingUserId]);
        $count = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["vehicle_count" => $count]);
    } catch (\PDOException $e) {
         // Handle potential error if 'creator' column doesn't exist
         if (str_contains($e->getMessage(), 'Unknown column')) {
              error_log("Missing 'creator' column in getOwnVehicleCount ($authority): " . $e->getMessage());
              http_response_code(501); // Not Implemented
              echo json_encode(["error" => "Cannot determine 'own' vehicles: Missing creator information."]);
         } else {
              error_log("DB error in getOwnVehicleCount (User: $requestingUserId, Authority: $authority): " . $e->getMessage());
              http_response_code(500); echo json_encode(["error" => "Could not retrieve vehicle count."]);
         }
    }
}

function addVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get data using getInputData()
    $inputData = getInputData();
    $brand = $inputData['brand'] ?? null;
    $model = $inputData['model'] ?? null;
    $numberplate = $inputData['numberplate'] ?? '';
    $color = $inputData['color'] ?? '';
    $stolen = filter_var($inputData['stolen'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $wanted = filter_var($inputData['wanted'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $registered = !empty($inputData['registered']) ? date('Y-m-d', strtotime($inputData['registered'])) : null;
    $text = $inputData['text'] ?? '';
    $owners = isset($inputData['owners']) && is_array($inputData['owners']) ? array_filter(array_map('intval', $inputData['owners']), fn($id) => $id > 0) : [];
    $drivers = isset($inputData['drivers']) && is_array($inputData['drivers']) ? array_filter(array_map('intval', $inputData['drivers']), fn($id) => $id > 0) : [];

    if (empty($brand) || empty($model)) { http_response_code(400); echo json_encode(['error' => 'Brand and model are required.']); return; }

    try {
        $pdo->beginTransaction();

        // 1. Insert Vehicle
        $sqlVehicle = "INSERT INTO `kdd_vehicle_file` (authority_id, brand, model, numberplate, color, stolen, wanted, registered, text, is_deleted)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmtVehicle = $pdo->prepare($sqlVehicle);
        $successVehicle = $stmtVehicle->execute([
            $authorityId, $brand, $model, $numberplate, $color, $stolen ? 1:0, $wanted ? 1:0, $registered, $text
        ]);
        if (!$successVehicle) throw new \Exception("Failed to insert vehicle record.");
        $vehicle_id = $pdo->lastInsertId();

        // 2. Insert Relations
        $sqlRel = "INSERT INTO `kdd_person_rel` (authority_id, id_person, id_vehicle_file, type) VALUES (?, ?, ?, ?)";
        $stmtRel = $pdo->prepare($sqlRel);
        foreach ($owners as $personId) { $stmtRel->execute([$authorityId, $personId, $vehicle_id, 'owner']); }
        foreach ($drivers as $personId) { $stmtRel->execute([$authorityId, $personId, $vehicle_id, 'driver']); }

        $pdo->commit();
        http_response_code(201); echo json_encode(["success" => true, "message" => "Vehicle added successfully.", "id" => $vehicle_id]);
        
        // Log change
        $logData = json_encode([
            'authority_id' => $authorityId,
            'brand' => $brand,
            'model' => $model,
            'numberplate' => $numberplate,
            'color' => $color,
            'stolen' => $stolen,
            'wanted' => $wanted,
            'registered' => $registered,
            'text' => $text,
            'owners' => $owners,
            'drivers' => $drivers
        ]);
        $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "vehicle_file", $vehicle_id, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in addVehicle ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add vehicle: " . $e->getMessage()]);
    }
}

function editVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

     $inputData = getInputData();
     $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
     if (!$id) { http_response_code(400); echo json_encode(['error' => 'Invalid or missing vehicle ID.']); return; }

     // Get other data
     $brand = $inputData['brand'] ?? null;
     $model = $inputData['model'] ?? null;
     $numberplate = $inputData['numberplate'] ?? '';
     $color = $inputData['color'] ?? '';
     $stolen = filter_var($inputData['stolen'] ?? false, FILTER_VALIDATE_BOOLEAN);
     $wanted = filter_var($inputData['wanted'] ?? false, FILTER_VALIDATE_BOOLEAN);
     $registered = !empty($inputData['registered']) ? date('Y-m-d', strtotime($inputData['registered'])) : null;
     $text = $inputData['text'] ?? '';
     $is_deleted = filter_var($inputData['is_deleted'] ?? false, FILTER_VALIDATE_BOOLEAN); // Allow updating deleted status?
     $owners = isset($inputData['owners']) && is_array($inputData['owners']) ? array_filter(array_map('intval', $inputData['owners']), fn($id) => $id > 0) : [];
     $drivers = isset($inputData['drivers']) && is_array($inputData['drivers']) ? array_filter(array_map('intval', $inputData['drivers']), fn($id) => $id > 0) : [];

     if (empty($brand) || empty($model)) { http_response_code(400); echo json_encode(['error' => 'Brand and model are required.']); return; }

     try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicle_file", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found."]); return;
        }

        // Get old relations
        $sqlOldRelations = "SELECT id_person, type FROM kdd_person_rel WHERE id_vehicle_file = ? AND authority_id = ?";
        $stmtOldRelations = $pdo->prepare($sqlOldRelations);
        $stmtOldRelations->execute([$id, $authorityId]);
        $oldRelations = $stmtOldRelations->fetchAll(PDO::FETCH_ASSOC);
        
        $oldOwners = [];
        $oldDrivers = [];
        foreach ($oldRelations as $relation) {
            if ($relation['type'] === 'owner') {
                $oldOwners[] = (int)$relation['id_person'];
            } elseif ($relation['type'] === 'driver') {
                $oldDrivers[] = (int)$relation['id_person'];
            }
        }

        $pdo->beginTransaction();

        // 1. Update Vehicle Info
        $sqlVehicle = "UPDATE `kdd_vehicle_file` SET brand = ?, model = ?, numberplate = ?, color = ?, stolen = ?, wanted = ?,
                        registered = ?, text = ?, is_deleted = ?
                      WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtVehicle = $pdo->prepare($sqlVehicle);
        $successVehicle = $stmtVehicle->execute([
            $brand, $model, $numberplate, $color, $stolen?1:0, $wanted?1:0, $registered, $text, $is_deleted?1:0,
            $id, $authorityId
        ]);
        if (!$successVehicle) { throw new \Exception("Failed to update vehicle data."); }
        if ($stmtVehicle->rowCount() === 0 && !$is_deleted) {
            // If no rows affected and we weren't trying to delete, the record might not exist or was already deleted
            error_log("Vehicle update for ID $id affected 0 rows (possibly no change or already deleted).");
        }

        // 2. Update Relations (Delete old, insert new)
        $sqlDeleteRel = "DELETE FROM kdd_person_rel WHERE id_vehicle_file = ? AND authority_id = ?";
        $stmtDeleteRel = $pdo->prepare($sqlDeleteRel);
        if (!$stmtDeleteRel->execute([$id, $authorityId])) { throw new \Exception("Failed to delete old relations."); }

        $sqlRel = "INSERT INTO `kdd_person_rel` (authority_id, id_person, id_vehicle_file, type) VALUES (?, ?, ?, ?)";
        $stmtRel = $pdo->prepare($sqlRel);
        foreach ($owners as $personId) { if (!$stmtRel->execute([$authorityId, $personId, $id, 'owner'])) throw new \Exception("Failed to insert owner relation {$personId}."); }
        foreach ($drivers as $personId) { if (!$stmtRel->execute([$authorityId, $personId, $id, 'driver'])) throw new \Exception("Failed to insert driver relation {$personId}."); }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle updated successfully."]);
        
        // Log change
        $newData = [
            'brand' => $brand,
            'model' => $model,
            'numberplate' => $numberplate,
            'color' => $color,
            'stolen' => $stolen,
            'wanted' => $wanted,
            'registered' => $registered,
            'text' => $text,
            'is_deleted' => $is_deleted,
            'authority_id' => $authorityId
        ];
        
        $changes = getEntryChanges($oldEntry, $newData);
        
        // Add relation changes
        if (!empty(array_diff($owners, $oldOwners)) || !empty(array_diff($oldOwners, $owners))) {
            $changes[] = ['column_name' => 'owners', 'old_value' => json_encode($oldOwners), 'new_value' => json_encode($owners)];
        }
        if (!empty(array_diff($drivers, $oldDrivers)) || !empty(array_diff($oldDrivers, $drivers))) {
            $changes[] = ['column_name' => 'drivers', 'old_value' => json_encode($oldDrivers), 'new_value' => json_encode($drivers)];
        }
        
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "vehicle_file", $id, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in editVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update vehicle: " . $e->getMessage()]);
    }
}


function deleteVehicle(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
     $inputData = getInputData();
     $id = filter_var($inputData['id'] ?? null, FILTER_VALIDATE_INT);
     if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing vehicle ID.']); 
        return; 
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_vehicle_file", $authorityId);
        if (!$oldEntry) {
            http_response_code(404); echo json_encode(["error" => "Vehicle not found or already deleted."]); return;
        }
        
        // Soft Delete
        $sql = "UPDATE `kdd_vehicle_file` SET is_deleted = 1 WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id, $authorityId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Vehicle marked as deleted."]);
            // Log change in more detail
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1],
                ['column_name' => 'full_data', 'old_value' => json_encode($oldEntry), 'new_value' => null]
            ];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "vehicle_file", $id, $requestingUserId, $changes);
        } elseif ($success) { 
            http_response_code(404); echo json_encode(["error" => "Vehicle not found or already deleted."]);
        } else { 
            http_response_code(500); echo json_encode(['error' => 'Failed to execute vehicle deletion.']); 
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteVehicle (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete vehicle."]);
    }
}


function getVehiclesByPerson(PDO $pdo, string $authority, int $authorityId): void {
    $inputData = getInputData();
    $personId = filter_var($inputData['personId'] ?? null, FILTER_VALIDATE_INT);
    if (!$personId) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing person ID.']); 
        return; 
    }

    error_log("getVehiclesByPerson (PersonID: $personId, Authority: $authority) called.");

    try {
        // Query vehicles linked via person_rel, also get all owners/drivers for those vehicles
        $sql = "SELECT v.*, 
                    GROUP_CONCAT(CASE WHEN pr2.type = 'owner' THEN pr2.id_person END) AS owners,
                    GROUP_CONCAT(CASE WHEN pr2.type = 'driver' THEN pr2.id_person END) AS drivers
                FROM kdd_vehicle_file v
                JOIN `kdd_person_rel` pr ON v.id = pr.id_vehicle_file AND pr.authority_id = ? AND pr.id_person = ?
                LEFT JOIN `kdd_person_rel` pr2 ON v.id = pr2.id_vehicle_file AND pr2.authority_id = ?
                WHERE v.is_deleted = 0 AND v.authority_id = ?
                GROUP BY v.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $personId, $authorityId, $authorityId]);
        $vehiclesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Post-process results
        $vehicles = array_map(function($row) {
            $row['owners'] = !empty($row['owners']) ? explode(',', $row['owners']) : [];
            $row['drivers'] = !empty($row['drivers']) ? explode(',', $row['drivers']) : [];
            $row['stolen'] = (bool)$row['stolen'];
            $row['wanted'] = (bool)$row['wanted'];
            $row['is_deleted'] = (bool)$row['is_deleted'];
            return $row;
        }, $vehiclesData);

        http_response_code(200);
        echo json_encode($vehicles);

    } catch (\PDOException $e) {
        error_log("DB error in getVehiclesByPerson (PersonID: $personId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve vehicles for person."]);
    }
}

function getPersons(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM `kdd_person_file` WHERE authority_id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cast booleans if needed
        $persons = array_map(function($person) {
            // Add a full name field for convenience
            $person['name'] = $person['firstname'] . ' ' . $person['lastname'];
            return $person;
        }, $persons);
        
        http_response_code(200);
        echo json_encode($persons);
    } catch (\PDOException $e) {
        error_log("DB error in getPersons ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve persons."]);
    }
}

?>