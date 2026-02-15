<?php
/**
 * Backend Endpoint: blackboard/index.php
 * Handles CRUD operations for Blackboard Entries based on board type (admin, employee, global).
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

// --- User Context & Authority / BoardType Validation ---
$userId = $decoded_jwt->userId ?? null;
$roleId = $decoded_jwt->roleId ?? null; // User's role ID
$userPermissions = $decoded_jwt->permissions ?? [];
$tokenAuthority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null; // Authority ID from user's token

if ($userId === null || !is_int($userId) || $tokenAuthority === null || !is_string($tokenAuthority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'blackboard')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to blackboard features."]); exit();
}

// Get and Validate boardType (legacy) OR area_id (new system)
$boardType = strtolower($_REQUEST['boardType'] ?? ''); // Allow GET or POST for boardType
$area_id = null;
$mode = 'legacy'; // 'legacy' or 'area'

// Check for area_id parameter (new area system)
if (isset($_REQUEST['area_id'])) {
    $area_id = (int)$_REQUEST['area_id'];
    $mode = 'area';
    $boardType = 'area'; // Set boardType to 'area' for consistency
} else {
    // Check JSON body for area_id
    $jsonBody = file_get_contents('php://input');
    $jsonData = json_decode($jsonBody, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['area_id'])) {
        $area_id = (int)$jsonData['area_id'];
        $mode = 'area';
        $boardType = 'area';
    }
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Area management actions don't need boardType validation
$areaManagementActions = ['getAreas', 'addArea', 'updateArea', 'deleteArea', 'getAreaPermissions', 'saveAreaPermissions'];
$isAreaManagement = in_array($action, $areaManagementActions);

// Only validate boardType for non-area-management actions
if (!$isAreaManagement) {
    // Validate boardType (extended with 'area')
    $allowedBoardTypes = ['all', 'admin', 'employee', 'global', 'area'];
    if (!in_array($boardType, $allowedBoardTypes)) {
        http_response_code(400); echo json_encode(['error' => 'Invalid board type specified.']); exit();
    }
}

// Map actions to module permissions
$permissions_map = [
    'getEntries'    => ['module' => 'blackboard', 'action' => 'read'],
    'setReaded'     => ['module' => 'blackboard', 'action' => 'read'], // Reading implies marking as read
    'addEntry'      => ['module' => 'blackboard', 'action' => 'write'],
    'pinEntry'      => ['module' => 'blackboard', 'action' => 'write'],
    'unpinEntry'    => ['module' => 'blackboard', 'action' => 'write'],
    'editEntry'     => ['module' => 'blackboard', 'action' => 'write'],
    'deleteEntry'   => ['module' => 'blackboard', 'action' => 'delete'],

    // NEW: Area management actions
    'getAreas'             => ['module' => 'blackboard.area', 'action' => 'read'],
    'addArea'              => ['module' => 'blackboard.area', 'action' => 'write'],
    'updateArea'           => ['module' => 'blackboard.area', 'action' => 'write'],
    'deleteArea'           => ['module' => 'blackboard.area', 'action' => 'delete'],
    'getAreaPermissions'   => ['module' => 'blackboard.area', 'action' => 'read'],
    'saveAreaPermissions'  => ['module' => 'blackboard.area', 'action' => 'write'],
];

$permission_config = $permissions_map[$action] ?? null;
$module = $permission_config['module'] ?? null;
$actionType = $permission_config['action'] ?? null;

$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if (!$permission_config) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Determine effective authority for database operations (only needed for entry actions)
$effectiveAuthority = ($boardType === 'global') ? 'global' : $tokenAuthority;
$effectiveAuthorityId = $authorityId;
if ($boardType === 'global') {
    $effectiveAuthorityId = -1; // Use -1 as a marker for global board
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Check permission levels (ALL > WRITE > READ, DELETE is separate)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
}
// Area-based permissions for entry actions (mode=area with area_id)
elseif (!$isAreaManagement && $mode === 'area' && $area_id) {
    // Get area permissions from database (multi-role support)
    $areaPermissions = getUserBlackboardAreaPermissions($pdo, $authorityId, $area_id, $userId);

    // Map action to required permission type
    if ($actionType === 'read') {
        $has_permission = $areaPermissions['can_read'];
    } elseif ($actionType === 'write') {
        $has_permission = $areaPermissions['can_write'];
    } elseif ($actionType === 'delete') {
        $has_permission = $areaPermissions['can_delete'];
    }
}
// Module-based permissions (for legacy boards and area management)
// DELETE implies WRITE and READ, WRITE implies READ (handled by hasModulePermission)
elseif ($module && $actionType && hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true;
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');
    // Add PUT/DELETE checks if using those methods semantically
    // $is_put_request = ($request_method === 'PUT');
    // $is_delete_request = ($request_method === 'DELETE');

    switch ($action) {
        case 'getEntries':    if ($request_method === 'GET') getEntries($pdo, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break;
        case 'addEntry':      if ($is_post_request) addEntry($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break;
        case 'setReaded':     if ($is_post_request) setReaded($pdo, $userId, $tokenAuthority, $authorityId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break; // Needs both authorities
        case 'deleteEntry':   if ($is_post_request) deleteEntry($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break; // Consider DELETE method
        case 'pinEntry':      if ($is_post_request) pinEntry($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break;
        case 'unpinEntry':    if ($is_post_request) unpinEntry($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break;
        case 'editEntry':     if ($is_post_request) editEntry($pdo, $userId, $effectiveAuthority, $effectiveAuthorityId, $boardType); else MethodNotAllowed(); break; // Consider PUT method

        // NEW: Area management actions
        case 'getAreas':              if ($request_method === 'GET' || $is_post_request) getBlackboardAreas($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'addArea':               if ($is_post_request) addBlackboardArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateArea':            if ($is_post_request) updateBlackboardArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteArea':            if ($is_post_request) deleteBlackboardArea($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getAreaPermissions':    if ($request_method === 'GET' || $is_post_request) getBlackboardAreaPermissions($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'saveAreaPermissions':   if ($is_post_request) saveBlackboardAreaPermissions($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    $requiredPermissionStr = $module && $actionType ? "$module.$actionType" : 'N/A';
    http_response_code(403); echo json_encode(["error" => "Permission denied for action '" . htmlspecialchars($action) . "' on board '" . htmlspecialchars($boardType) . "'. Required: " . htmlspecialchars($requiredPermissionStr)]);
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

function getEntries(PDO $pdo, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    global $area_id, $mode, $userId; // Access global variables

    try {
        // 0. Permission check for area mode
        if ($mode === 'area' && $area_id) {
            // Get all user roles for this authority
            $stmtRoles = $pdo->prepare("
                SELECT role_id FROM kdd_user_roles
                WHERE user_id = ? AND authority_id = ?
            ");
            $stmtRoles->execute([$userId, $effectiveAuthorityId]);
            $userRoles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

            if (empty($userRoles)) {
                http_response_code(403);
                echo json_encode(['error' => 'No roles assigned to user.']);
                return;
            }

            // Check if ANY of the user's roles has can_read permission for this area
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
            $stmtPerm = $pdo->prepare("
                SELECT COALESCE(MAX(can_read), 0) as has_read
                FROM kdd_blackboard_area_permissions
                WHERE area_id = ? AND authority_id = ? AND role_id IN ($placeholders)
            ");
            $stmtPerm->execute([$area_id, $effectiveAuthorityId, ...$userRoles]);
            $result = $stmtPerm->fetch(PDO::FETCH_ASSOC);

            if (empty($result) || !(bool)$result['has_read']) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions to read this area.']);
                return;
            }
        }

        // 1. Fetch board entries
        $sqlEntries = "";
        $params = [];

        if ($mode === 'area' && $area_id) {
            // Area mode - filter by specific area_id
            $sqlEntries = "SELECT b.id, b.title, b.author, b.text, b.color, b.created, b.need_readed, b.pinned,
                                  b.area_id, a.name AS area_name
                           FROM kdd_blackboard b
                           LEFT JOIN kdd_blackboard_areas a ON b.area_id = a.id
                           WHERE b.authority_id = ? AND b.area_id = ? AND b.deleted = 0
                           ORDER BY b.pinned DESC, b.id DESC";
            $params = [$effectiveAuthorityId, $area_id];
        } elseif ($boardType === 'all') {
            // All areas for this authority (EXCEPT global)
            $sqlEntries = "SELECT b.id, b.title, b.author, b.text, b.color, b.created, b.need_readed, b.pinned,
                                  b.area_id, a.name AS area_name
                           FROM kdd_blackboard b
                           LEFT JOIN kdd_blackboard_areas a ON b.area_id = a.id
                           WHERE b.authority_id = ? AND b.area_id IS NOT NULL AND b.deleted = 0
                           ORDER BY b.pinned DESC, b.id DESC";
            $params = [$effectiveAuthorityId];
        } elseif ($boardType === 'global') {
            // Global board only - cross-authority
            $sqlEntries = "SELECT b.id, b.title, b.author, b.text, b.color, b.created, b.need_readed, b.pinned,
                                  b.area_id, a.name AS area_name
                           FROM kdd_blackboard b
                           LEFT JOIN kdd_blackboard_areas a ON b.area_id = a.id
                           WHERE b.deleted = 0 AND b.blackboard = ?
                           ORDER BY b.pinned DESC, b.id DESC";
            $params = [$boardType];
        } else {
            // Legacy boards (admin/employee) - still supported for backward compatibility
            $sqlEntries = "SELECT b.id, b.title, b.author, b.text, b.color, b.created, b.need_readed, b.pinned,
                                  b.area_id, a.name AS area_name
                           FROM kdd_blackboard b
                           LEFT JOIN kdd_blackboard_areas a ON b.area_id = a.id
                           WHERE b.authority_id = ? AND b.deleted = 0 AND b.blackboard = ?
                           ORDER BY b.pinned DESC, b.id DESC";
            $params = [$effectiveAuthorityId, $boardType];
        }
        
        $stmtEntries = $pdo->prepare($sqlEntries);
        $stmtEntries->execute($params);
        $entriesData = $stmtEntries->fetchAll(PDO::FETCH_ASSOC);

        if (empty($entriesData)) {
            http_response_code(200); echo json_encode([]); return; // No entries found
        }

        // Prepare entries array indexed by ID for easy lookup
        $entryIds = [];
        $entries = [];
        foreach ($entriesData as $row) {
            $row['readers'] = ''; // Initialize as empty string as per original output
            $row['readers_list'] = []; // Use a proper array internally
            $entries[$row['id']] = $row;
            $entryIds[] = $row['id'];
        }

        // 2. Fetch Readers (Service Numbers) for these entries
        $placeholders = rtrim(str_repeat('?,', count($entryIds)), ',');
        
        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlReaders = "SELECT entry_id, servicenumber
                           FROM kdd_blackboard_rel
                           WHERE entry_id IN ({$placeholders})";
            $params = $entryIds;
        } else {
            // For other boards, filter by authority_id
            $sqlReaders = "SELECT entry_id, servicenumber
                           FROM kdd_blackboard_rel
                           WHERE authority_id = ? AND entry_id IN ({$placeholders})";
            $params = [$effectiveAuthorityId];
            $params = array_merge($params, $entryIds);
        }
        
        $stmtReaders = $pdo->prepare($sqlReaders);
        $stmtReaders->execute($params);
        $readerRows = $stmtReaders->fetchAll(PDO::FETCH_ASSOC);

        // 3. Map readers back to entries
        foreach ($readerRows as $reader) {
            if (isset($entries[$reader['entry_id']])) {
                $entries[$reader['entry_id']]['readers_list'][] = $reader['servicenumber'];
            }
        }

        // Format readers as comma-separated string for final output (matching original)
        foreach ($entries as &$entry) { // Use reference to modify array directly
             $entry['readers'] = implode(', ', $entry['readers_list']);
             unset($entry['readers_list']); // Remove temporary array
        }
        unset($entry); // Unset reference

        http_response_code(200);
        echo json_encode(array_values($entries)); // Return as numerically indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getEntries (Authority: $effectiveAuthority, Board: $boardType): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve entries."]);
    }
}

function addEntry(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    global $area_id, $mode; // Access global variables

    $data = getJsonRequestData();
    if (!$data) return;

    try {
        // Extract data, apply defaults, and sanitize
        $title = $data['title'] ?? '';
        $author = $data['author'] ?? '';
        $text = $data['text'] ?? '';
        $color = $data['color'] ?? '#212121';
        $needReaded = (bool)($data['need_readed'] ?? false);
        $pinned = (bool)($data['pinned'] ?? false);

        // Basic validation
        if (empty($title) || empty($author) || empty($text)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title, author, and text are required fields.']);
            return;
        }

        // NEW: Determine which area_id and blackboard type to use
        $entryAreaId = null;
        $entryBlackboardType = $boardType;

        if ($mode === 'area' && $area_id) {
            // Area mode
            $entryAreaId = $area_id;
            $entryBlackboardType = 'area';
        }

        // For global entries, we need to get a real authority_id or use 1 as fallback
        $realAuthorityId = $effectiveAuthorityId;
        if ($boardType === 'global' && $effectiveAuthorityId === -1) {
            try {
                // Try to get the global authority ID if it exists
                $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = 'global'");
                $stmt->execute();
                $globalAuthId = $stmt->fetchColumn();
                if ($globalAuthId) {
                    $realAuthorityId = $globalAuthId;
                } else {
                    // Try to get any authority id as fallback
                    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities LIMIT 1");
                    $stmt->execute();
                    $anyAuthId = $stmt->fetchColumn();
                    $realAuthorityId = $anyAuthId ?: 1; // Use 1 as last resort
                }
            } catch (\PDOException $e) {
                // If all else fails, use 1
                $realAuthorityId = 1;
                error_log("Could not determine a valid authority_id for global board: " . $e->getMessage());
            }
        }

        // Insert blackboard entry (NEW: with area_id)
        $sql = "INSERT INTO kdd_blackboard
                (authority_id, area_id, title, author, text, color, created, need_readed, pinned, blackboard)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $realAuthorityId,
            $entryAreaId,  // NEW: Can be NULL for legacy/global
            $title,
            $author,
            $text,
            $color,
            $needReaded ? 1 : 0,
            $pinned ? 1 : 0,
            $entryBlackboardType  // 'global', 'admin', 'employee', or 'area'
        ]);

        $newEntryId = $pdo->lastInsertId();

        if (!$newEntryId) {
            throw new \PDOException("Failed to insert new blackboard entry.");
        }

        // Log entry creation
        $changes = [
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'author', 'old_value' => null, 'new_value' => $author],
            ['column_name' => 'text', 'old_value' => null, 'new_value' => $text],
            ['column_name' => 'color', 'old_value' => null, 'new_value' => $color],
            ['column_name' => 'blackboard', 'old_value' => null, 'new_value' => $boardType],
            ['column_name' => 'need_readed', 'old_value' => null, 'new_value' => $needReaded ? 1 : 0]
        ];
        logDatabaseChange($realAuthorityId, $pdo, 'INSERT', 'kdd_blackboard', $newEntryId, $requestingUserId, $changes);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Entry created successfully.',
            'id' => $newEntryId
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in addEntry ($effectiveAuthority/$boardType): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not create blackboard entry.']);
    }
}

function setReaded(PDO $pdo, int $requestingUserId, string $tokenAuthority, int $authorityId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    // Process POST data
    $data = getJsonRequestData();
    $entryId = $data['id'];
    
    // Get service number from linked employee
    $servicenumber = '';
    $linkedEmployeeId = null;
    try {
        // First get the linked_employee from kdd_users
        $sqlGetLinkedEmployee = "SELECT linked_employee FROM kdd_users WHERE id = ?";
        $stmtGetLinkedEmployee = $pdo->prepare($sqlGetLinkedEmployee);
        $stmtGetLinkedEmployee->execute([$requestingUserId]);
        $linkedEmployeeId = $stmtGetLinkedEmployee->fetchColumn();
        
        if ($linkedEmployeeId) {
            // Now get the service number from the employee record
            $sqlGetServiceNumber = "SELECT servicenumber FROM kdd_employee WHERE id = ?";
            $stmtGetServiceNumber = $pdo->prepare($sqlGetServiceNumber);
            $stmtGetServiceNumber->execute([$linkedEmployeeId]);
            $servicenumber = $stmtGetServiceNumber->fetchColumn();
        }
    } catch (\PDOException $e) {
        error_log("Error fetching linked employee servicenumber: " . $e->getMessage());
    }
    
    // Fallback to POST data if we couldn't get the service number
    if (empty($servicenumber)) {
        $servicenumber = $_POST['servicenumber'] ?? '';
    }

    if (!$entryId || !$servicenumber || !$linkedEmployeeId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entry ID, service number, and linked employee are required.']);
        return;
    }

    try {
        // Check if entry exists and belongs to the right authority/board
        $sqlCheckEntry = "SELECT id FROM kdd_blackboard 
                         WHERE authority_id = ? AND id = ? AND blackboard = ?";
        $stmtCheckEntry = $pdo->prepare($sqlCheckEntry);
        $stmtCheckEntry->execute([$effectiveAuthorityId, $entryId, $boardType]);

        if (!$stmtCheckEntry->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found.']);
            return;
        }

        // Check if already marked as read
        $sqlCheckRead = "SELECT entry_id FROM kdd_blackboard_rel 
                        WHERE authority_id = ? AND entry_id = ? AND employee_id = ?";
        $stmtCheckRead = $pdo->prepare($sqlCheckRead);
        $stmtCheckRead->execute([$effectiveAuthorityId, $entryId, $linkedEmployeeId]);

        if ($stmtCheckRead->fetch()) {
            // Already marked as read, no action needed
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Entry already marked as read.']);
            return;
        }

        // Insert read record
        $sqlInsert = "INSERT INTO kdd_blackboard_rel
                     (authority_id, entry_id, employee_id, servicenumber, readed_at)
                     VALUES (?, ?, ?, ?, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$effectiveAuthorityId, $entryId, $linkedEmployeeId, $servicenumber]);

        $relId = $pdo->lastInsertId();

        // Log read record
        $changes = [
            ['column_name' => 'entry_id', 'old_value' => null, 'new_value' => $entryId],
            ['column_name' => 'employee_id', 'old_value' => null, 'new_value' => $linkedEmployeeId],
            ['column_name' => 'servicenumber', 'old_value' => null, 'new_value' => $servicenumber]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_blackboard_rel', $relId, $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Entry marked as read successfully.']);

    } catch (\PDOException $e) {
        error_log("DB error in setReaded ($effectiveAuthority/$boardType, EntryID: $entryId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not mark entry as read.']);
    }
}

function deleteEntry(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    // Process JSON request data
    $data = getJsonRequestData();

    $entryId = $data['id'] ?? null;

    if (!$entryId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entry ID is required.']);
        return;
    }

    try {
        // First, verify entry exists and belongs to right authority/board
        $sqlCheck = "";
        $params = [];
        
        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE id = ? AND blackboard = ?";
            $params = [$entryId, $boardType];
        } else {
            // For other boards, filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE authority_id = ? AND id = ? AND blackboard = ?";
            $params = [$effectiveAuthorityId, $entryId, $boardType];
        }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($params);

        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found.']);
            return;
        }

        // Delete entry (soft delete by setting deleted=1)
        $sqlDelete = "";
        $deleteParams = [];

        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlDelete = "UPDATE kdd_blackboard SET deleted = 1 WHERE id = ?";
            $deleteParams = [$entryId];
        } else {
            // For other boards, filter by authority_id
            $sqlDelete = "UPDATE kdd_blackboard SET deleted = 1
                         WHERE authority_id = ? AND id = ?";
            $deleteParams = [$effectiveAuthorityId, $entryId];
        }

        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute($deleteParams);

        // Log soft delete
        $changes = [
            ['column_name' => 'deleted', 'old_value' => 0, 'new_value' => 1]
        ];
        $logAuthorityId = ($boardType === 'global') ? $effectiveAuthorityId : $effectiveAuthorityId;
        logDatabaseChange($logAuthorityId, $pdo, 'UPDATE', 'kdd_blackboard', $entryId, $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Entry deleted successfully.']);

    } catch (\PDOException $e) {
        error_log("DB error in deleteEntry ($effectiveAuthority/$boardType, EntryID: $entryId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not delete entry.']);
    }
}

function pinEntry(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    // Process JSON request data
    $data = getJsonRequestData();

    $entryId = $data['id'] ?? null;

    if (!$entryId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entry ID is required.']);
        return;
    }

    try {
        // First, verify entry exists and belongs to right authority/board
        $sqlCheck = "";
        $params = [];
        
        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE id = ? AND blackboard = ?";
            $params = [$entryId, $boardType];
        } else {
            // For other boards, filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE authority_id = ? AND id = ? AND blackboard = ?";
            $params = [$effectiveAuthorityId, $entryId, $boardType];
        }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($params);

        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found.']);
            return;
        }

        // Pin entry
        $sqlPin = "";
        $pinParams = [];

        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlPin = "UPDATE kdd_blackboard SET pinned = 1 WHERE id = ?";
            $pinParams = [$entryId];
        } else {
            // For other boards, filter by authority_id
            $sqlPin = "UPDATE kdd_blackboard SET pinned = 1
                      WHERE authority_id = ? AND id = ?";
            $pinParams = [$effectiveAuthorityId, $entryId];
        }

        $stmtPin = $pdo->prepare($sqlPin);
        $stmtPin->execute($pinParams);

        // Log pin action
        $changes = [
            ['column_name' => 'pinned', 'old_value' => 0, 'new_value' => 1]
        ];
        logDatabaseChange($effectiveAuthorityId, $pdo, 'UPDATE', 'kdd_blackboard', $entryId, $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Entry pinned successfully.']);

    } catch (\PDOException $e) {
        error_log("DB error in pinEntry ($effectiveAuthority/$boardType, EntryID: $entryId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not pin entry.']);
    }
}

function unpinEntry(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    // Process JSON request data
    $data = getJsonRequestData();

    $entryId = $data['id'] ?? null;

    if (!$entryId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entry ID is required.']);
        return;
    }

    try {
        // First, verify entry exists and belongs to right authority/board
        $sqlCheck = "";
        $params = [];
        
        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE id = ? AND blackboard = ?";
            $params = [$entryId, $boardType];
        } else {
            // For other boards, filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE authority_id = ? AND id = ? AND blackboard = ?";
            $params = [$effectiveAuthorityId, $entryId, $boardType];
        }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($params);

        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found.']);
            return;
        }

        // Unpin entry
        $sqlUnpin = "";
        $unpinParams = [];

        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlUnpin = "UPDATE kdd_blackboard SET pinned = 0 WHERE id = ?";
            $unpinParams = [$entryId];
        } else {
            // For other boards, filter by authority_id
            $sqlUnpin = "UPDATE kdd_blackboard SET pinned = 0
                        WHERE authority_id = ? AND id = ?";
            $unpinParams = [$effectiveAuthorityId, $entryId];
        }

        $stmtUnpin = $pdo->prepare($sqlUnpin);
        $stmtUnpin->execute($unpinParams);

        // Log unpin action
        $changes = [
            ['column_name' => 'pinned', 'old_value' => 1, 'new_value' => 0]
        ];
        logDatabaseChange($effectiveAuthorityId, $pdo, 'UPDATE', 'kdd_blackboard', $entryId, $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Entry unpinned successfully.']);

    } catch (\PDOException $e) {
        error_log("DB error in unpinEntry ($effectiveAuthority/$boardType, EntryID: $entryId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not unpin entry.']);
    }
}

function editEntry(PDO $pdo, int $requestingUserId, string $effectiveAuthority, int $effectiveAuthorityId, string $boardType): void {
    // Process JSON request data
    $data = getJsonRequestData();

    $entryId = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $text = $data['text'] ?? '';
    $color = $data['color'] ?? '#212121';
    $needReaded = $data['need_readed'] ?? 0;

    if (!$entryId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entry ID is required.']);
        return;
    }

    try {
        // First, verify entry exists and belongs to right authority/board
        $sqlCheck = "";
        $params = [];
        
        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE id = ? AND blackboard = ?";
            $params = [$entryId, $boardType];
        } else {
            // For other boards, filter by authority_id
            $sqlCheck = "SELECT id FROM kdd_blackboard 
                        WHERE authority_id = ? AND id = ? AND blackboard = ?";
            $params = [$effectiveAuthorityId, $entryId, $boardType];
        }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute($params);

        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found.']);
            return;
        }

        // Get old data for logging
        $oldEntry = getEntryById($pdo, $entryId, 'kdd_blackboard', $boardType === 'global' ? null : $effectiveAuthorityId);

        // Edit entry
        $sqlUpdate = "";
        $updateParams = [];

        if ($boardType === 'global') {
            // For global board, don't filter by authority_id
            $sqlUpdate = "UPDATE kdd_blackboard
                         SET title = ?, text = ?, color = ?, need_readed = ?
                         WHERE id = ?";
            $updateParams = [$title, $text, $color, $needReaded, $entryId];
        } else {
            // For other boards, filter by authority_id
            $sqlUpdate = "UPDATE kdd_blackboard
                         SET title = ?, text = ?, color = ?, need_readed = ?
                         WHERE authority_id = ? AND id = ?";
            $updateParams = [$title, $text, $color, $needReaded, $effectiveAuthorityId, $entryId];
        }

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute($updateParams);

        // Log entry update
        if ($oldEntry) {
            $changes = [];
            if ($oldEntry['title'] != $title) {
                $changes[] = ['column_name' => 'title', 'old_value' => $oldEntry['title'], 'new_value' => $title];
            }
            if ($oldEntry['text'] != $text) {
                $changes[] = ['column_name' => 'text', 'old_value' => $oldEntry['text'], 'new_value' => $text];
            }
            if ($oldEntry['color'] != $color) {
                $changes[] = ['column_name' => 'color', 'old_value' => $oldEntry['color'], 'new_value' => $color];
            }
            if ($oldEntry['need_readed'] != $needReaded) {
                $changes[] = ['column_name' => 'need_readed', 'old_value' => $oldEntry['need_readed'], 'new_value' => $needReaded];
            }
            if (!empty($changes)) {
                logDatabaseChange($effectiveAuthorityId, $pdo, 'UPDATE', 'kdd_blackboard', $entryId, $requestingUserId, $changes);
            }
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Entry edited successfully.']);

    } catch (\PDOException $e) {
        error_log("DB error in editEntry ($effectiveAuthority/$boardType, EntryID: $entryId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not edit entry.']);
    }
}

// ===== NEW: AREA MANAGEMENT FUNCTIONS =====

function getBlackboardAreas(PDO $pdo, int $authorityId): void {
    global $userId, $userPermissions; // Access global variables

    try {
        // Check if user has ALL_PERMISSIONS (full access to everything)
        $hasAllPermissions = hasAllPermissions($userPermissions);

        // Get all role IDs for this user in this authority
        $stmtRoles = $pdo->prepare("
            SELECT role_id FROM kdd_user_roles
            WHERE user_id = ? AND authority_id = ?
        ");
        $stmtRoles->execute([$userId, $authorityId]);
        $userRoles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userRoles) && !$hasAllPermissions) {
            // User has no roles and no ALL_PERMISSIONS -> no access to any areas
            http_response_code(200);
            echo json_encode([]);
            return;
        }

        // Get all areas with aggregated permissions across ALL user roles
        // Using MAX() to get the highest permission level across all roles
        // If ANY role has can_read=1, user can read
        if ($hasAllPermissions) {
            // User with ALL_PERMISSIONS sees all areas with full access
            $stmt = $pdo->prepare("
                SELECT ba.*,
                       1 as can_read,
                       1 as can_write,
                       1 as can_delete
                FROM kdd_blackboard_areas ba
                WHERE ba.authority_id = ? AND ba.is_active = 1
                ORDER BY ba.sort_order ASC, ba.name ASC
            ");
            $stmt->execute([$authorityId]);
        } else {
            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));

            $stmt = $pdo->prepare("
                SELECT ba.*,
                       COALESCE(MAX(bap.can_read), 0) as can_read,
                       COALESCE(MAX(bap.can_write), 0) as can_write,
                       COALESCE(MAX(bap.can_delete), 0) as can_delete
                FROM kdd_blackboard_areas ba
                LEFT JOIN kdd_blackboard_area_permissions bap
                    ON ba.id = bap.area_id
                    AND bap.authority_id = ba.authority_id
                    AND bap.role_id IN ($placeholders)
                WHERE ba.authority_id = ? AND ba.is_active = 1
                GROUP BY ba.id
                ORDER BY ba.sort_order ASC, ba.name ASC
            ");
            $stmt->execute([...$userRoles, $authorityId]);
        }

        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format permissions and filter areas
        $filteredAreas = [];
        foreach ($areas as $area) {
            $canRead = (bool)$area['can_read'];
            $canWrite = (bool)$area['can_write'];
            $canDelete = (bool)$area['can_delete'];

            // Only return areas where user has at least read permission
            if ($canRead) {
                $area['permissions'] = [
                    'can_read' => $canRead,
                    'can_write' => $canWrite,
                    'can_delete' => $canDelete
                ];

                // Remove the individual permission columns
                unset($area['can_read'], $area['can_write'], $area['can_delete']);

                $filteredAreas[] = $area;
            }
        }

        http_response_code(200);
        echo json_encode($filteredAreas);
    } catch (\PDOException $e) {
        error_log("Error fetching blackboard areas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not fetch areas.']);
    }
}

function addBlackboardArea(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $key = $data['key'] ?? '';
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $icon = $data['icon'] ?? 'mdi-bulletin-board';
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;
    $sortOrder = $data['sort_order'] ?? 0;

    // Validation
    if (empty($key) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Key and name are required.']);
        return;
    }

    // Validate key format (lowercase, alphanumeric, underscores)
    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Key must contain only lowercase letters, numbers, and underscores.']);
        return;
    }

    try {
        // Check for duplicate key
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_blackboard_areas
            WHERE authority_id = ? AND `key` = ?
        ");
        $stmt->execute([$authorityId, $key]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'An area with this key already exists.']);
            return;
        }

        // Insert new area
        $stmt = $pdo->prepare("
            INSERT INTO kdd_blackboard_areas
            (authority_id, `key`, name, description, icon, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $authorityId,
            $key,
            $name,
            $description,
            $icon,
            $isActive ? 1 : 0,
            $sortOrder
        ]);

        $newAreaId = $pdo->lastInsertId();

        // Log creation
        $changes = [
            ['column_name' => 'key', 'old_value' => null, 'new_value' => $key],
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_blackboard_areas', $newAreaId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Area created successfully.',
            'id' => $newAreaId
        ]);

    } catch (\PDOException $e) {
        error_log("Error adding blackboard area: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not create area.']);
    }
}

function updateBlackboardArea(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $areaId = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $icon = $data['icon'] ?? 'mdi-bulletin-board';
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;
    $sortOrder = $data['sort_order'] ?? 0;

    if (!$areaId || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Area ID and name are required.']);
        return;
    }

    try {
        // Verify area belongs to authority
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_blackboard_areas
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$areaId, $authorityId]);
        $oldArea = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldArea) {
            http_response_code(404);
            echo json_encode(['error' => 'Area not found.']);
            return;
        }

        // Update area
        $stmt = $pdo->prepare("
            UPDATE kdd_blackboard_areas
            SET name = ?, description = ?, icon = ?, is_active = ?, sort_order = ?
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([
            $name,
            $description,
            $icon,
            $isActive ? 1 : 0,
            $sortOrder,
            $areaId,
            $authorityId
        ]);

        // Log changes
        $changes = [];
        if ($oldArea['name'] !== $name) {
            $changes[] = ['column_name' => 'name', 'old_value' => $oldArea['name'], 'new_value' => $name];
        }
        if (!empty($changes)) {
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_blackboard_areas', $areaId, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Area updated successfully.']);

    } catch (\PDOException $e) {
        error_log("Error updating blackboard area: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not update area.']);
    }
}

function deleteBlackboardArea(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $areaId = $data['id'] ?? null;

    if (!$areaId) {
        http_response_code(400);
        echo json_encode(['error' => 'Area ID is required.']);
        return;
    }

    try {
        // Verify area belongs to authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_blackboard_areas
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$areaId, $authorityId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Area not found.']);
            return;
        }

        // Check if area has entries
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM kdd_blackboard
            WHERE area_id = ? AND deleted = 0
        ");
        $stmt->execute([$areaId]);
        $entryCount = $stmt->fetchColumn();

        if ($entryCount > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete area with existing entries. Delete entries first.']);
            return;
        }

        // Delete area (CASCADE will delete permissions)
        $stmt = $pdo->prepare("
            DELETE FROM kdd_blackboard_areas
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$areaId, $authorityId]);

        // Log deletion
        logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_blackboard_areas', $areaId, $userId, []);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Area deleted successfully.']);

    } catch (\PDOException $e) {
        error_log("Error deleting blackboard area: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not delete area.']);
    }
}

function getBlackboardAreaPermissions(PDO $pdo, int $authorityId): void {
    // Support both GET and POST requests
    $areaId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $areaId = $_GET['area_id'] ?? null;
    } else {
        $data = getJsonRequestData();
        $areaId = $data['area_id'] ?? null;
    }

    if (!$areaId) {
        http_response_code(400);
        echo json_encode(['error' => 'Area ID is required.']);
        return;
    }

    try {
        // Get all roles for this authority
        $stmt = $pdo->prepare("
            SELECT id, name FROM kdd_roles
            WHERE authority_id = ?
            ORDER BY name
        ");
        $stmt->execute([$authorityId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get existing permissions
        $stmt = $pdo->prepare("
            SELECT role_id, can_read, can_write, can_delete
            FROM kdd_blackboard_area_permissions
            WHERE area_id = ? AND authority_id = ?
        ");
        $stmt->execute([$areaId, $authorityId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map permissions by role_id
        $permMap = [];
        foreach ($permissions as $perm) {
            $permMap[$perm['role_id']] = $perm;
        }

        // Combine roles with permissions
        $result = [];
        foreach ($roles as $role) {
            $result[] = [
                'id' => $role['id'],
                'name' => $role['name'],
                'permissions' => [
                    'can_read' => isset($permMap[$role['id']]) ? (bool)$permMap[$role['id']]['can_read'] : false,
                    'can_write' => isset($permMap[$role['id']]) ? (bool)$permMap[$role['id']]['can_write'] : false,
                    'can_delete' => isset($permMap[$role['id']]) ? (bool)$permMap[$role['id']]['can_delete'] : false,
                ]
            ];
        }

        http_response_code(200);
        echo json_encode(['roles' => $result]);

    } catch (\PDOException $e) {
        error_log("Error fetching blackboard area permissions: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        error_log("Area ID: " . $areaId . ", Authority ID: " . $authorityId);
        http_response_code(500);
        echo json_encode([
            'error' => 'Could not fetch permissions.',
            'debug' => $e->getMessage(), // TODO: Remove in production
            'area_id' => $areaId
        ]);
    }
}

function saveBlackboardAreaPermissions(PDO $pdo, int $userId, int $authorityId): void {
    $data = getJsonRequestData();
    $areaId = $data['area_id'] ?? null;
    $permissions = $data['permissions'] ?? [];

    if (!$areaId || !is_array($permissions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Area ID and permissions array are required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Delete existing permissions for this area
        $stmt = $pdo->prepare("
            DELETE FROM kdd_blackboard_area_permissions
            WHERE area_id = ? AND authority_id = ?
        ");
        $stmt->execute([$areaId, $authorityId]);

        // Insert new permissions
        $stmt = $pdo->prepare("
            INSERT INTO kdd_blackboard_area_permissions
            (area_id, role_id, authority_id, can_read, can_write, can_delete)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($permissions as $perm) {
            $roleId = $perm['role_id'] ?? null;
            $canRead = isset($perm['can_read']) ? (bool)$perm['can_read'] : false;
            $canWrite = isset($perm['can_write']) ? (bool)$perm['can_write'] : false;
            $canDelete = isset($perm['can_delete']) ? (bool)$perm['can_delete'] : false;

            if (!$roleId) continue;

            // Only insert if at least one permission is granted
            if ($canRead || $canWrite || $canDelete) {
                $stmt->execute([
                    $areaId,
                    $roleId,
                    $authorityId,
                    $canRead ? 1 : 0,
                    $canWrite ? 1 : 0,
                    $canDelete ? 1 : 0
                ]);
            }
        }

        $pdo->commit();

        // Log action
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_blackboard_area_permissions', $areaId, $userId, [
            ['column_name' => 'permissions', 'old_value' => null, 'new_value' => 'updated']
        ]);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Permissions saved successfully.']);

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error saving blackboard area permissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not save permissions.']);
    }
}
?>