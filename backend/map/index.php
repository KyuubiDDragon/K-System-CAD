<?php
/**
 * Backend Endpoint: map/index.php
 * Handles CRUD operations for Map Markers and Categories, supporting authority-specific and global maps.
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

// --- Public Endpoint Check (before authentication) ---
// getSharedMap is publicly accessible without authentication
$action = $_REQUEST['action'] ?? '';
if ($action === 'getSharedMap') {
    $token = $_REQUEST['token'] ?? '';
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required.']);
        exit();
    }
    getSharedMap($pdo, $token);
    exit();
}

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority / MapType Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$tokenAuthority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null; // Authority ID from token

if ($userId === null || !is_int($userId) || $tokenAuthority === null || !is_string($tokenAuthority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'map')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to map features."]); exit();
}

// Get and Validate mapType (from GET or POST)
$mapTypeInput = strtolower($_REQUEST['mapType'] ?? '');

// If not found in request parameters, try to get it from JSON body
if (empty($mapTypeInput)) {
    $jsonBody = file_get_contents('php://input');
    $jsonData = json_decode($jsonBody, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['mapType'])) {
        $mapTypeInput = strtolower($jsonData['mapType']);
    }
}

$allowedMapTypes = ['global', $tokenAuthority, 'defaultmap']; // Allow 'global' or the user's own authority type
if (empty($mapTypeInput) || !in_array($mapTypeInput, $allowedMapTypes)) {
    if ($mapTypeInput !== $tokenAuthority && $mapTypeInput !== 'global') {
        http_response_code(400); echo json_encode(['error' => "Invalid or disallowed map type specified ('{$mapTypeInput}')."]); exit();
    }
    if (empty($mapTypeInput)) {
        http_response_code(400); echo json_encode(['error' => 'Map type parameter is required.']); exit();
    }
}
$mapType = $mapTypeInput; // Validated map type
$effectiveAuthority = $mapType == 'global' ? $mapType : $tokenAuthority; // Use mapType directly as effective authority ('global' or user's own)

// Determine the effective authority ID based on mapType
$effectiveAuthorityId = null;
if ($mapType === 'global') {
    // Verwende -1 als Marker für global
    $effectiveAuthorityId = -1;
    
    // Optional versuchen, eine echte globale Authority-ID zu bekommen, aber nicht fehlschlagen
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
        $stmt->execute();
        $globalAuthId = (int)$stmt->fetchColumn();
        if ($globalAuthId) {
            $effectiveAuthorityId = $globalAuthId;
        }
    } catch (\PDOException $e) {
        error_log("Could not determine global authority ID: " . $e->getMessage());
        // Weiter mit dem Marker -1
    }
} else {
    // Use the user's authority ID
    $effectiveAuthorityId = $authorityId;
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';

// If not found in request parameters, try to get it from JSON body
if (empty($action)) {
    if (isset($jsonData) && json_last_error() === JSON_ERROR_NONE && !empty($jsonData['action'])) {
        $action = $jsonData['action'];
    }
}

$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to base permission names (module will be 'map' or 'map.global' based on mapType)
$permissions_map = [
    'getCategories'  => 'read',
    'getMarker'      => 'read',
    'addMarker'      => 'write',
    'updateMarker'   => 'write',
    'deleteMarker'   => 'delete',
    'createShare'    => 'read', // Allow users with read permission to create shares
];

$actionType = $permissions_map[$action] ?? null;
$module = $mapType === 'global' ? 'map.global' : 'map';
$required_permission = $actionType ? ['module' => $module, 'action' => $actionType] : null;

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
        case 'getCategories': 
            if ($request_method === 'GET' || $request_method === 'POST') {
                getCategories($pdo, $effectiveAuthority, $effectiveAuthorityId);
            } else {
                MethodNotAllowed(); 
            }
            break;
        case 'getMarker':     
            if ($request_method === 'GET' || $request_method === 'POST') {
                getMarker($pdo, $effectiveAuthority, $effectiveAuthorityId);
            } else {
                MethodNotAllowed(); 
            }
            break;
        case 'addMarker':     if ($is_post_request) addMarker($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'updateMarker':  if ($is_post_request) updateMarker($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'deleteMarker':  if ($is_post_request) deleteMarker($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'createShare':   if ($is_post_request) createShare($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $mapType); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action '" . htmlspecialchars($action) . "' on map '" . htmlspecialchars($mapType) . "'. Required: " . htmlspecialchars($module) . " (action: " . htmlspecialchars($actionType) . ")"]);
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

function getCategories(PDO $pdo, string $effectiveAuthority, int $effectiveAuthorityId): void {
    try {
        $sql = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Karten-Typ keine Authority-ID-Filterung
            $sql = "SELECT * FROM kdd_map_category WHERE name LIKE 'global%' ORDER BY name ASC";
            $params = [];
        } else {
            // Normale Filterung mit Authority-ID
            $sql = "SELECT * FROM kdd_map_category WHERE authority_id = ? ORDER BY name ASC";
            $params = [$effectiveAuthorityId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($categories);
    } catch (\PDOException $e) {
        error_log("DB error in getCategories (Authority: $effectiveAuthority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve map categories."]);
    }
}

function getMarker(PDO $pdo, string $effectiveAuthority, int $effectiveAuthorityId): void {
    try {
        $sql = "";
        $params = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Karten-Typ keine Authority-ID-Filterung oder Filterung mit "global" im Namen
            $sql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                           m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                           c.icon as category_icon, c.name as category_name
                    FROM kdd_map m
                    LEFT JOIN kdd_map_category c ON m.category_id = c.id 
                    WHERE m.is_deleted = 0 AND (m.name LIKE 'global%' OR c.name LIKE 'global%')
                    ORDER BY m.name ASC";
        } else {
            // Normale Filterung mit Authority-ID
            $sql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                           m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                           c.icon as category_icon, c.name as category_name
                    FROM kdd_map m
                    LEFT JOIN kdd_map_category c ON m.category_id = c.id AND c.authority_id = ?
                    WHERE m.is_deleted = 0 AND m.authority_id = ?
                    ORDER BY m.name ASC";
            $params = [$effectiveAuthorityId, $effectiveAuthorityId];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $markers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($markers);
    } catch (\PDOException $e) {
        error_log("DB error in getMarker (Authority: $effectiveAuthority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve map markers."]);
    }
}

function addMarker(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    
    // Extract and validate marker data
    $name = trim($data['name'] ?? '');
    $ceo = trim($data['ceo'] ?? '');
    $phoneNumber = trim($data['phonenumber'] ?? '');
    $categoryId = filter_var($data['category_id'] ?? 0, FILTER_VALIDATE_INT);
    $xCoord = filter_var($data['x_coordinate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $yCoord = filter_var($data['y_coordinate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $location = trim($data['location'] ?? '');
    
    // Validation
    if (empty($name)) { http_response_code(400); echo json_encode(['error' => 'Marker name is required.']); return; }
    if ($categoryId <= 0) { http_response_code(400); echo json_encode(['error' => 'Valid category ID is required.']); return; }
    if ($xCoord === false || $yCoord === false) { http_response_code(400); echo json_encode(['error' => 'Valid coordinates are required.']); return; }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Versuche, die globale Authority-ID zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Fallback auf eine vorhandene Authority
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                    $stmt->execute();
                    $anyAuthId = $stmt->fetchColumn();
                    $realAuthorityId = $anyAuthId ?: 1;
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global map: " . $e->getMessage());
            }
        }
        
        // Verify the new category exists
        $catCheckSql = "";
        $catCheckParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte nach Name prüfen
            $catCheckSql = "SELECT id FROM kdd_map_category WHERE id = ? AND name LIKE 'global%'";
            $catCheckParams = [$categoryId];
        } else {
            // Normale Prüfung
            $catCheckSql = "SELECT id FROM kdd_map_category WHERE id = ? AND authority_id = ?";
            $catCheckParams = [$categoryId, $realAuthorityId];
        }
        
        $catCheckStmt = $pdo->prepare($catCheckSql);
        $catCheckStmt->execute($catCheckParams);
        
        if ($catCheckStmt->rowCount() === 0) {
            http_response_code(400); 
            echo json_encode(['error' => 'The specified category does not exist or is not accessible.']); 
            return;
        }
        
        // Stelle sicher, dass der Name für globale Einträge mit "global" beginnt
        if ($effectiveAuthorityId === -1 && !str_starts_with(strtolower($name), 'global')) {
            $name = "Global " . $name;
        }
        
        // Insert the new marker
        $sql = "INSERT INTO kdd_map (name, ceo, phonenumber, location, category_id, x_coordinate, y_coordinate, authority_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $ceo, $phoneNumber, $location, $categoryId, $xCoord, $yCoord, $realAuthorityId]);
        
        if ($success) {
            $newId = $pdo->lastInsertId();
            
            // Log the creation
            $changes = [
                ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
                ['column_name' => 'ceo', 'old_value' => null, 'new_value' => $ceo],
                ['column_name' => 'phonenumber', 'old_value' => null, 'new_value' => $phoneNumber],
                ['column_name' => 'location', 'old_value' => null, 'new_value' => $location],
                ['column_name' => 'category_id', 'old_value' => null, 'new_value' => $categoryId],
                ['column_name' => 'x_coordinate', 'old_value' => null, 'new_value' => $xCoord],
                ['column_name' => 'y_coordinate', 'old_value' => null, 'new_value' => $yCoord],
                ['column_name' => 'authority_id', 'old_value' => null, 'new_value' => $realAuthorityId]
            ];
            
            logDatabaseChange($realAuthorityId, $pdo, 'INSERT', "map", $newId, $requestingUserId, $changes);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Marker added successfully.',
                'id' => $newId
            ]);
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to add marker.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addMarker (Authority: $effectiveAuthority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not create map marker."]);
    }
}

function deleteMarker(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    
    // Extract and validate marker ID
    $markerId = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($markerId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid marker ID is required.']);
        return;
    }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) ermitteln wir die tatsächliche Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        
        if ($effectiveAuthorityId === -1) {
            // Zuerst versuchen wir, die Authority-ID des Markers zu bekommen
            $markerAuthStmt = $pdo->prepare("SELECT authority_id FROM kdd_map WHERE id = ?");
            $markerAuthStmt->execute([$markerId]);
            $markerAuthId = $markerAuthStmt->fetchColumn();
            
            if ($markerAuthId) {
                $realAuthorityId = $markerAuthId;
            } else {
                try {
                    // Wenn kein Marker gefunden, versuche die global Authority zu bekommen
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                    $stmt->execute();
                    $globalAuthId = $stmt->fetchColumn();
                    
                    if ($globalAuthId) {
                        $realAuthorityId = $globalAuthId;
                    } else {
                        // Fallback auf eine vorhandene Authority
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1;
                    }
                } catch (\PDOException $e) {
                    // Wenn alles fehlschlägt, verwende 1
                    $realAuthorityId = 1;
                    error_log("Could not determine a valid authority_id for global marker deletion: " . $e->getMessage());
                }
            }
        }
        
        // Verify marker exists and get original data for logging
        $checkStmt = $pdo->prepare("SELECT * FROM kdd_map WHERE id = ?");
        $checkStmt->execute([$markerId]);
        $marker = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$marker) {
            http_response_code(404);
            echo json_encode(['error' => 'Marker not found.']);
            return;
        }
        
        // Check access permission based on marker authority
        $markerAuthId = $marker['authority_id'];
        
        // Wenn es kein globaler Zugriff ist UND die Authority-IDs nicht übereinstimmen
        if ($effectiveAuthorityId !== -1 && $effectiveAuthorityId !== $markerAuthId) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to delete this marker.']);
            return;
        }
        
        // Soft delete the marker
        $sql = "UPDATE kdd_map SET is_deleted = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$markerId]);
        
        if ($success) {
            // Log the deletion
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => '0', 'new_value' => '1']
            ];
            
            logDatabaseChange($marker['authority_id'], $pdo, 'DELETE', "map", $markerId, $requestingUserId, $changes);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Marker deleted successfully.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete marker.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteMarker (Authority: $effectiveAuthority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete map marker."]);
    }
}

function updateMarker(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId): void {
    $data = getJsonRequestData();
    
    // Extract and validate marker data
    $markerId = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
    $name = trim($data['name'] ?? '');
    $ceo = trim($data['ceo'] ?? '');
    $phoneNumber = trim($data['phonenumber'] ?? '');
    $categoryId = filter_var($data['category_id'] ?? 0, FILTER_VALIDATE_INT);
    $xCoord = filter_var($data['x_coordinate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $yCoord = filter_var($data['y_coordinate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $location = trim($data['location'] ?? '');
    
    // Validation
    if ($markerId <= 0) { http_response_code(400); echo json_encode(['error' => 'Valid marker ID is required.']); return; }
    if (empty($name)) { http_response_code(400); echo json_encode(['error' => 'Marker name is required.']); return; }
    if ($categoryId <= 0) { http_response_code(400); echo json_encode(['error' => 'Valid category ID is required.']); return; }
    if ($xCoord === false || $yCoord === false) { http_response_code(400); echo json_encode(['error' => 'Valid coordinates are required.']); return; }
    
    try {
        // Bei globalen Inhalten (effectiveAuthorityId === -1) benötigen wir eine reale Authority-ID
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            try {
                // Versuche, die globale Authority-ID zu bekommen
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Versuche, die Authority-ID des Markers zu bekommen
                    $stmt = $pdo->prepare("SELECT authority_id FROM kdd_map WHERE id = ?");
                    $stmt->execute([$markerId]);
                    $markerAuthId = $stmt->fetchColumn();
                    if ($markerAuthId) {
                        $realAuthorityId = $markerAuthId;
                    } else {
                        // Fallback auf eine vorhandene Authority
                        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                        $stmt->execute();
                        $anyAuthId = $stmt->fetchColumn();
                        $realAuthorityId = $anyAuthId ?: 1;
                    }
                }
            } catch (\PDOException $e) {
                // Wenn alles fehlschlägt, verwende 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global map: " . $e->getMessage());
            }
        }
        
        // Get existing marker to check if it exists and to compare changes
        $checkSql = "";
        $checkParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte nach ID ohne Authority-Filter prüfen
            $checkSql = "SELECT * FROM kdd_map WHERE id = ? AND (authority_id = ? OR name LIKE 'global%')";
            $checkParams = [$markerId, $realAuthorityId];
        } else {
            // Normale Prüfung mit Authority-ID
            $checkSql = "SELECT * FROM kdd_map WHERE id = ? AND authority_id = ?";
            $checkParams = [$markerId, $realAuthorityId];
        }
        
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute($checkParams);
        $marker = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$marker) {
            http_response_code(404); 
            echo json_encode(['error' => 'Marker not found or not accessible.']); 
            return;
        }
        
        // Verify the new category exists
        $catCheckSql = "";
        $catCheckParams = [];
        
        if ($effectiveAuthorityId === -1) {
            // Für globale Inhalte nach Name prüfen
            $catCheckSql = "SELECT id FROM kdd_map_category WHERE id = ? AND name LIKE 'global%'";
            $catCheckParams = [$categoryId];
        } else {
            // Normale Prüfung
            $catCheckSql = "SELECT id FROM kdd_map_category WHERE id = ? AND authority_id = ?";
            $catCheckParams = [$categoryId, $realAuthorityId];
        }
        
        $catCheckStmt = $pdo->prepare($catCheckSql);
        $catCheckStmt->execute($catCheckParams);
        
        if ($catCheckStmt->rowCount() === 0) {
            http_response_code(400); 
            echo json_encode(['error' => 'The specified category does not exist or is not accessible.']); 
            return;
        }
        
        // Stelle sicher, dass der Name für globale Einträge mit "global" beginnt
        if ($effectiveAuthorityId === -1 && !str_starts_with(strtolower($name), 'global')) {
            $name = "Global " . $name;
        }
        
        // Update the marker
        $sql = "UPDATE kdd_map SET 
                name = ?, 
                ceo = ?, 
                phonenumber = ?, 
                category_id = ?, 
                x_coordinate = ?, 
                y_coordinate = ?, 
                location = ? 
                WHERE id = ? AND authority_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $ceo, $phoneNumber, $categoryId, $xCoord, $yCoord, $location, $markerId, $realAuthorityId]);
        
        if ($success) {
            // Track changes for logging
            $changes = [];
            if ($marker['name'] !== $name) {
                $changes[] = ['column_name' => 'name', 'old_value' => $marker['name'], 'new_value' => $name];
            }
            if ($marker['ceo'] !== $ceo) {
                $changes[] = ['column_name' => 'ceo', 'old_value' => $marker['ceo'], 'new_value' => $ceo];
            }
            if ($marker['phonenumber'] !== $phoneNumber) {
                $changes[] = ['column_name' => 'phonenumber', 'old_value' => $marker['phonenumber'], 'new_value' => $phoneNumber];
            }
            if ((int)$marker['category_id'] !== $categoryId) {
                $changes[] = ['column_name' => 'category_id', 'old_value' => $marker['category_id'], 'new_value' => $categoryId];
            }
            if ((float)$marker['x_coordinate'] !== $xCoord) {
                $changes[] = ['column_name' => 'x_coordinate', 'old_value' => $marker['x_coordinate'], 'new_value' => $xCoord];
            }
            if ((float)$marker['y_coordinate'] !== $yCoord) {
                $changes[] = ['column_name' => 'y_coordinate', 'old_value' => $marker['y_coordinate'], 'new_value' => $yCoord];
            }
            if ($marker['location'] !== $location) {
                $changes[] = ['column_name' => 'location', 'old_value' => $marker['location'], 'new_value' => $location];
            }
            
            if (!empty($changes)) {
                logDatabaseChange($realAuthorityId, $pdo, 'UPDATE', "map", $markerId, $requestingUserId, $changes);
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Marker updated successfully.'
            ]);
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to update marker.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateMarker (Authority: $effectiveAuthority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update map marker."]);
    }
}

/**
 * Create a shareable link for the map
 */
function createShare(PDO $pdo, int $userId, string $effectiveAuthority, int $effectiveAuthorityId, string $mapType): void {
    try {
        $jsonBody = file_get_contents('php://input');
        $data = json_decode($jsonBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON in request body.']);
            return;
        }

        $showSidebar = isset($data['showSidebar']) ? (bool)$data['showSidebar'] : false;
        $categoryIds = isset($data['categoryIds']) ? $data['categoryIds'] : null;

        // Validate category IDs if provided
        if ($categoryIds !== null && !is_array($categoryIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'categoryIds must be an array or null.']);
            return;
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32)); // 64 character hex string

        // Convert category IDs to JSON
        $categoryIdsJson = $categoryIds !== null ? json_encode($categoryIds) : null;

        // Determine real authority ID (convert -1 to actual global authority ID if needed)
        $realAuthorityId = $effectiveAuthorityId;
        if ($effectiveAuthorityId === -1) {
            $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
            $stmt->execute();
            $globalAuthId = (int)$stmt->fetchColumn();
            if ($globalAuthId) {
                $realAuthorityId = $globalAuthId;
            }
        }

        // Insert share record
        $sql = "INSERT INTO kdd_map_shares
                (share_token, authority_id, map_type, show_sidebar, category_ids, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $token,
            $realAuthorityId,
            $mapType,
            $showSidebar ? 1 : 0,
            $categoryIdsJson,
            $userId
        ]);

        if ($success) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'token' => $token,
                'url' => '/map/shared/' . $token
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create share link.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in createShare: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while creating share link.']);
    }
}

/**
 * Get shared map data (public endpoint, no authentication required)
 */
function getSharedMap(PDO $pdo, string $token): void {
    try {
        // Fetch share record
        $sql = "SELECT * FROM kdd_map_shares WHERE share_token = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $share = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$share) {
            http_response_code(404);
            echo json_encode(['error' => 'Share link not found or expired.']);
            return;
        }

        // Check if expired (if expires_at is set)
        if ($share['expires_at'] !== null) {
            $expiresAt = new \DateTime($share['expires_at']);
            $now = new \DateTime();
            if ($now > $expiresAt) {
                http_response_code(410);
                echo json_encode(['error' => 'Share link has expired.']);
                return;
            }
        }

        $authorityId = (int)$share['authority_id'];
        $mapType = $share['map_type'];
        $showSidebar = (bool)$share['show_sidebar'];
        $categoryIds = $share['category_ids'] ? json_decode($share['category_ids'], true) : null;

        // Determine effective authority for queries
        $effectiveAuthority = $mapType;
        $effectiveAuthorityId = $authorityId;

        // If authority is global, set marker
        if ($mapType === 'global') {
            $effectiveAuthorityId = -1;
        }

        // Fetch categories
        $categories = [];
        $categorySql = "";
        $categoryParams = [];

        if ($effectiveAuthorityId === -1) {
            $categorySql = "SELECT * FROM kdd_map_category WHERE name LIKE 'global%' ORDER BY name ASC";
            $categoryParams = [];
        } else {
            $categorySql = "SELECT * FROM kdd_map_category WHERE authority_id = ? ORDER BY name ASC";
            $categoryParams = [$authorityId];
        }

        $stmt = $pdo->prepare($categorySql);
        $stmt->execute($categoryParams);
        $allCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Filter categories if specific ones are selected
        if ($categoryIds !== null) {
            $categories = array_filter($allCategories, function($cat) use ($categoryIds) {
                return in_array((int)$cat['id'], $categoryIds);
            });
            $categories = array_values($categories); // Re-index array
        } else {
            $categories = $allCategories;
        }

        // Fetch markers
        $markers = [];
        $markerSql = "";
        $markerParams = [];

        if ($effectiveAuthorityId === -1) {
            // Global map
            if ($categoryIds !== null) {
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $markerSql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                                     m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                                     c.icon as category_icon, c.name as category_name
                              FROM kdd_map m
                              LEFT JOIN kdd_map_category c ON m.category_id = c.id
                              WHERE m.is_deleted = 0
                              AND (m.name LIKE 'global%' OR c.name LIKE 'global%')
                              AND m.category_id IN ($placeholders)
                              ORDER BY m.name ASC";
                $markerParams = $categoryIds;
            } else {
                $markerSql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                                     m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                                     c.icon as category_icon, c.name as category_name
                              FROM kdd_map m
                              LEFT JOIN kdd_map_category c ON m.category_id = c.id
                              WHERE m.is_deleted = 0
                              AND (m.name LIKE 'global%' OR c.name LIKE 'global%')
                              ORDER BY m.name ASC";
                $markerParams = [];
            }
        } else {
            // Authority-specific map
            if ($categoryIds !== null) {
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $markerSql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                                     m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                                     c.icon as category_icon, c.name as category_name
                              FROM kdd_map m
                              LEFT JOIN kdd_map_category c ON m.category_id = c.id
                              WHERE m.is_deleted = 0
                              AND m.authority_id = ?
                              AND m.category_id IN ($placeholders)
                              ORDER BY m.name ASC";
                $markerParams = array_merge([$authorityId], $categoryIds);
            } else {
                $markerSql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                                     m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                                     c.icon as category_icon, c.name as category_name
                              FROM kdd_map m
                              LEFT JOIN kdd_map_category c ON m.category_id = c.id
                              WHERE m.is_deleted = 0
                              AND m.authority_id = ?
                              ORDER BY m.name ASC";
                $markerParams = [$authorityId];
            }
        }

        $stmt = $pdo->prepare($markerSql);
        $stmt->execute($markerParams);
        $markers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'mapType' => $mapType,
            'showSidebar' => $showSidebar,
            'categories' => $categories,
            'markers' => $markers
        ]);

    } catch (\PDOException $e) {
        error_log("DB error in getSharedMap: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error while fetching shared map.']);
    }
}

?>