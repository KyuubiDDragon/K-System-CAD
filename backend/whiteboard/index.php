<?php
/**
 * Backend Endpoint: whiteboard/index.php
 * Handles CRUD operations for Whiteboards and their elements.
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


// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getMyWhiteboards'    => ['module' => 'whiteboard', 'action' => 'read'],
    'getPublicWhiteboards' => ['module' => 'whiteboard', 'action' => 'read'],
    'getWhiteboard'       => ['module' => 'whiteboard', 'action' => 'read'],
    'createWhiteboard'    => ['module' => 'whiteboard', 'action' => 'write'],
    'deleteWhiteboard'    => ['module' => 'whiteboard', 'action' => 'delete'],
    'saveElement'         => ['module' => 'whiteboard', 'action' => 'write'],
    'deleteElement'       => ['module' => 'whiteboard', 'action' => 'write'],
    'createSnapshot'      => ['module' => 'whiteboard', 'action' => 'write'],
    'joinWithCode'        => ['module' => 'whiteboard', 'action' => 'read'],
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
        case 'getMyWhiteboards':     if ($request_method === 'GET') getMyWhiteboards($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getPublicWhiteboards': if ($request_method === 'GET') getPublicWhiteboards($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'getWhiteboard':        if ($request_method === 'GET') getWhiteboard($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createWhiteboard':     if ($request_method === 'POST') createWhiteboard($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteWhiteboard':     if ($request_method === 'POST') deleteWhiteboard($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'saveElement':          if ($request_method === 'POST') saveElement($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteElement':        if ($request_method === 'POST') deleteElement($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createSnapshot':       if ($request_method === 'POST') createSnapshot($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'joinWithCode':         if ($request_method === 'POST') joinWithCode($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        default: http_response_code(404); echo json_encode(['error' => 'Action not implemented.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





// --- Function Implementations ---

/**
 * Get all whiteboards belonging to the user
 */
function getMyWhiteboards(PDO $pdo, int $userId, int $authorityId): void {
    try {
        $sql = "SELECT w.*, 
                (w.owner_id = ?) as is_owner,
                (SELECT COUNT(*) FROM kdd_whiteboard_users WHERE whiteboard_id = w.id) as participants
         FROM kdd_whiteboards w
         JOIN kdd_whiteboard_users wu ON w.id = wu.whiteboard_id
         WHERE wu.user_id = ? 
         AND w.authority_id = ?
         AND w.is_archived = false
         ORDER BY w.updated_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $authorityId]);
        $whiteboards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for client
        $formattedWhiteboards = [];
        foreach ($whiteboards as $wb) {
            $formattedWhiteboards[] = [
                'id' => $wb['id'],
                'name' => $wb['name'],
                'accessType' => $wb['access_type'],
                'joinCode' => $wb['is_owner'] ? $wb['join_code'] : null,
                'createdBy' => $wb['owner_id'],
                'isOwner' => (bool)$wb['is_owner'],
                'participants' => (int)$wb['participants'],
                'createdAt' => $wb['created_at'],
                'backgroundColor' => $wb['background_color']
            ];
        }
        
        http_response_code(200);
        echo json_encode(['whiteboards' => $formattedWhiteboards]);
    } catch (\PDOException $e) {
        error_log("Error in getMyWhiteboards: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Get all public whiteboards
 */
function getPublicWhiteboards(PDO $pdo, int $authorityId): void {
    try {
        $sql = "SELECT w.*, 
                (SELECT COUNT(*) FROM kdd_whiteboard_users WHERE whiteboard_id = w.id) as participants
         FROM kdd_whiteboards w
         WHERE w.access_type = 'public' 
         AND w.authority_id = ?
         AND w.is_archived = false
         ORDER BY w.updated_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $whiteboards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for client
        $formattedWhiteboards = [];
        foreach ($whiteboards as $wb) {
            $formattedWhiteboards[] = [
                'id' => $wb['id'],
                'name' => $wb['name'],
                'createdBy' => $wb['owner_id'],
                'participants' => (int)$wb['participants'],
                'createdAt' => $wb['created_at']
            ];
        }
        
        http_response_code(200);
        echo json_encode(['whiteboards' => $formattedWhiteboards]);
    } catch (\PDOException $e) {
        error_log("Error in getPublicWhiteboards: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Get a single whiteboard with all elements
 */
function getWhiteboard(PDO $pdo, int $userId, int $authorityId): void {
    $whiteboardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$whiteboardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid whiteboard ID']);
        return;
    }
    
    try {
        // Get whiteboard data
        $sql = "SELECT * FROM kdd_whiteboards 
                WHERE id = ? AND authority_id = ? AND is_archived = false";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$whiteboardId, $authorityId]);
        $whiteboard = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$whiteboard) {
            http_response_code(404);
            echo json_encode(['error' => 'Whiteboard not found']);
            return;
        }
        
        // Check if user has access
        $accessSql = "SELECT COUNT(*) FROM kdd_whiteboard_users 
                      WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
        $accessStmt = $pdo->prepare($accessSql);
        $accessStmt->execute([$whiteboardId, $userId, $authorityId]);
        $hasAccess = (int)$accessStmt->fetchColumn() > 0;
        
        // For public or code-based boards, automatically add user if they have proper access
        if (!$hasAccess) {
            if ($whiteboard['access_type'] === 'public') {
                // Add user to the whiteboard
                addUserToWhiteboard($pdo, $whiteboardId, $userId, $authorityId);
                $hasAccess = true;
            } elseif ($whiteboard['access_type'] === 'private' && $whiteboard['owner_id'] === $userId) {
                // Owner always has access
                $hasAccess = true;
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have access to this whiteboard']);
                return;
            }
        }
        
        // Get whiteboard elements
        $elementsSql = "SELECT * FROM kdd_whiteboard_elements 
                       WHERE whiteboard_id = ? AND authority_id = ? AND deleted_at IS NULL
                       ORDER BY created_at ASC";
        $elementsStmt = $pdo->prepare($elementsSql);
        $elementsStmt->execute([$whiteboardId, $authorityId]);
        $elementRows = $elementsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format elements
        $elements = [];
        foreach ($elementRows as $row) {
            $data = json_decode($row['data'], true);
            $elements[$row['element_id']] = [
                'id' => $row['element_id'],
                'type' => $row['type'],
                'userId' => $row['user_id'],
                'timestamp' => $row['created_at'],
                ...$data
            ];
        }
        
        // Get participant count
        $participantsSql = "SELECT COUNT(*) FROM kdd_whiteboard_users WHERE whiteboard_id = ?";
        $participantsStmt = $pdo->prepare($participantsSql);
        $participantsStmt->execute([$whiteboardId]);
        $participants = (int)$participantsStmt->fetchColumn();
        
        // Response data
        $responseData = [
            'whiteboard' => [
                'id' => $whiteboard['id'],
                'name' => $whiteboard['name'],
                'createdBy' => $whiteboard['owner_id'],
                'participants' => $participants,
                'createdAt' => $whiteboard['created_at'],
                'content' => $elements,
                'backgroundColor' => $whiteboard['background_color']
            ]
        ];
        
        http_response_code(200);
        echo json_encode($responseData);
    } catch (\PDOException $e) {
        error_log("Error in getWhiteboard: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Create a new whiteboard
 */
function createWhiteboard(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    
    $name = $data['name'] ?? 'Neues Whiteboard';
    $accessType = $data['accessType'] ?? 'private';
    $joinCode = $data['joinCode'] ?? null;
    $backgroundColor = $data['backgroundColor'] ?? '#FFFFFF';
    
    // Generate random join code if access type is 'code' and no code provided
    if ($accessType === 'code' && empty($joinCode)) {
        $joinCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }
    
    try {
        $sql = "INSERT INTO kdd_whiteboards 
                (name, owner_id, authority_id, access_type, join_code, background_color, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $userId, $authorityId, $accessType, $joinCode, $backgroundColor]);
        $newId = $pdo->lastInsertId();
        
        // Add creator as first user (owner)
        $userSql = "INSERT INTO kdd_whiteboard_users
                    (whiteboard_id, user_id, authority_id, role, joined_at, last_active_at)
                    VALUES (?, ?, ?, 'owner', NOW(), NOW())";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([$newId, $userId, $authorityId]);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'whiteboard' => [
                'id' => $newId,
                'name' => $name,
                'accessType' => $accessType,
                'joinCode' => $joinCode,
                'createdBy' => $userId,
                'participants' => 1,
                'createdAt' => date('Y-m-d H:i:s'),
                'backgroundColor' => $backgroundColor
            ]
        ]);
    } catch (\PDOException $e) {
        error_log("Error in createWhiteboard: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Delete a whiteboard (soft delete)
 */
function deleteWhiteboard(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    $whiteboardId = $data['whiteboardId'] ?? null;
    
    if (!$whiteboardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid whiteboard ID']);
        return;
    }
    
    try {
        // Check if user is the owner
        $ownerSql = "SELECT COUNT(*) FROM kdd_whiteboards 
                     WHERE id = ? AND owner_id = ? AND authority_id = ?";
        $ownerStmt = $pdo->prepare($ownerSql);
        $ownerStmt->execute([$whiteboardId, $userId, $authorityId]);
        $isOwner = (int)$ownerStmt->fetchColumn() > 0;
        
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['error' => 'Only the owner can delete this whiteboard']);
            return;
        }
        
        // Soft delete (mark as archived)
        $sql = "UPDATE kdd_whiteboards SET is_archived = true, updated_at = NOW()
                WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$whiteboardId, $authorityId]);

        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        error_log("Error in deleteWhiteboard: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Save a whiteboard element (drawing, shape, text, etc.)
 */
function saveElement(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    
    $whiteboardId = $data['whiteboardId'] ?? null;
    $element = $data['element'] ?? null;
    
    if (!$whiteboardId || !$element || !isset($element['id']) || !isset($element['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid element data']);
        return;
    }
    
    try {
        // Check if user has access to the whiteboard
        $accessSql = "SELECT COUNT(*) FROM kdd_whiteboard_users 
                      WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
        $accessStmt = $pdo->prepare($accessSql);
        $accessStmt->execute([$whiteboardId, $userId, $authorityId]);
        $hasAccess = (int)$accessStmt->fetchColumn() > 0;
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this whiteboard']);
            return;
        }
        
        // Extract element data
        $elementId = $element['id'];
        $elementType = $element['type'];
        
        // Remove id, type, and userId from data to store
        $elementData = $element;
        unset($elementData['id']);
        unset($elementData['type']);
        unset($elementData['userId']);
        
        // Store element
        $sql = "INSERT INTO kdd_whiteboard_elements 
                (whiteboard_id, element_id, user_id, authority_id, type, data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$whiteboardId, $elementId, $userId, $authorityId, $elementType, json_encode($elementData)]);
        
        // Update whiteboard last updated timestamp
        $updateSql = "UPDATE kdd_whiteboards SET updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$whiteboardId]);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'elementId' => $elementId]);
    } catch (\PDOException $e) {
        error_log("Error in saveElement: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Delete a whiteboard element
 */
function deleteElement(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    
    $whiteboardId = $data['whiteboardId'] ?? null;
    $elementId = $data['elementId'] ?? null;
    
    if (!$whiteboardId || !$elementId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing whiteboard ID or element ID']);
        return;
    }
    
    try {
        // Check if user has access to the whiteboard
        $accessSql = "SELECT COUNT(*) FROM kdd_whiteboard_users 
                      WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
        $accessStmt = $pdo->prepare($accessSql);
        $accessStmt->execute([$whiteboardId, $userId, $authorityId]);
        $hasAccess = (int)$accessStmt->fetchColumn() > 0;
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this whiteboard']);
            return;
        }
        
        // Mark element as deleted
        $sql = "UPDATE kdd_whiteboard_elements SET deleted_at = NOW() 
                WHERE whiteboard_id = ? AND element_id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$whiteboardId, $elementId, $authorityId]);
        
        // Update whiteboard last updated timestamp
        $updateSql = "UPDATE kdd_whiteboards SET updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$whiteboardId]);
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        error_log("Error in deleteElement: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Create a snapshot of the whiteboard
 */
function createSnapshot(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    
    $whiteboardId = $data['whiteboardId'] ?? null;
    $name = $data['name'] ?? ('Snapshot ' . date('Y-m-d H:i:s'));
    $thumbnail = $data['thumbnail'] ?? null;
    
    if (!$whiteboardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing whiteboard ID']);
        return;
    }
    
    try {
        // Check if user has access to the whiteboard
        $accessSql = "SELECT COUNT(*) FROM kdd_whiteboard_users 
                      WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
        $accessStmt = $pdo->prepare($accessSql);
        $accessStmt->execute([$whiteboardId, $userId, $authorityId]);
        $hasAccess = (int)$accessStmt->fetchColumn() > 0;
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this whiteboard']);
            return;
        }
        
        // Create snapshot
        $sql = "INSERT INTO kdd_whiteboard_snapshots 
                (whiteboard_id, user_id, authority_id, name, thumbnail, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$whiteboardId, $userId, $authorityId, $name, $thumbnail]);
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        error_log("Error in createSnapshot: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Join a whiteboard with a code
 */
function joinWithCode(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    $joinCode = $data['joinCode'] ?? null;
    
    if (!$joinCode) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing join code']);
        return;
    }
    
    try {
        // Find whiteboard with this code
        $sql = "SELECT * FROM kdd_whiteboards 
                WHERE join_code = ? AND authority_id = ? AND access_type = 'code' AND is_archived = false";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$joinCode, $authorityId]);
        $whiteboard = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$whiteboard) {
            http_response_code(404);
            echo json_encode(['error' => 'No whiteboard found with this code']);
            return;
        }
        
        // Add user to whiteboard users
        addUserToWhiteboard($pdo, $whiteboard['id'], $userId, $authorityId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'whiteboardId' => $whiteboard['id']
        ]);
    } catch (\PDOException $e) {
        error_log("Error in joinWithCode: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

/**
 * Helper to add a user to a whiteboard
 */
function addUserToWhiteboard(PDO $pdo, $whiteboardId, $userId, $authorityId, $role = 'editor'): void {
    // Check if user is already in the whiteboard
    $checkSql = "SELECT COUNT(*) FROM kdd_whiteboard_users 
                 WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$whiteboardId, $userId, $authorityId]);
    $exists = (int)$checkStmt->fetchColumn() > 0;
    
    if ($exists) {
        // Update last active timestamp
        $updateSql = "UPDATE kdd_whiteboard_users SET last_active_at = NOW() 
                     WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$whiteboardId, $userId, $authorityId]);
    } else {
        // Check if user is owner and adjust role if needed
        $ownerSql = "SELECT owner_id FROM kdd_whiteboards WHERE id = ?";
        $ownerStmt = $pdo->prepare($ownerSql);
        $ownerStmt->execute([$whiteboardId]);
        $ownerId = $ownerStmt->fetchColumn();
        
        if ((int)$ownerId === $userId) {
            $role = 'owner';
        }
        
        // Insert new user
        $insertSql = "INSERT INTO kdd_whiteboard_users 
                      (whiteboard_id, user_id, authority_id, role, joined_at, last_active_at) 
                      VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([$whiteboardId, $userId, $authorityId, $role]);
    }
} 