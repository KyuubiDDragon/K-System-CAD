<?php
/**
 * Backend Endpoint: admin/messages/index.php
 * Handles ADMIN CRUD operations for Message Groups and Memberships.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null; // ID of the admin user performing the action
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'messaging')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to messaging features."]); exit();
}

// Get authority ID
global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
if (!$authorityId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid authority']);
    exit;
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getGroups'      => 'ADMIN_READ_MESSAGES', // Or a specific ADMIN_READ_GROUPS?
    'saveGroup'      => 'ADMIN_WRITE_MESSAGES',// Or ADMIN_WRITE_GROUPS?
    'deleteGroup'    => 'ADMIN_WRITE_MESSAGES',// Assuming WRITE covers DELETE here
    // 'getUsers' action removed
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
} elseif ($required_permission === 'ADMIN_READ_MESSAGES' && hasPermission($userPermissions, 'ADMIN_WRITE_MESSAGES')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getGroups':     if ($request_method === 'GET') getGroups($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveGroup':     if ($is_post_request) saveGroup($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Handles Insert/Update
        case 'deleteGroup':   if ($is_post_request) deleteGroup($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE method
        // case 'getUsers': // Removed

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

/** Fetches all non-deleted groups with members and indicates if the requesting user is a member */
function getGroups(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $groups = [];
    $groupsById = [];
    $groupIds = [];

    try {
        // 1. Fetch all non-deleted groups
        $sqlGroups = "SELECT g.*,
                             -- Check if the requesting user is a direct member (performance hit?)
                             -- Alternative: Check this flag in PHP after fetching all members
                             EXISTS(SELECT 1 FROM kdd_message_group_members AS gm_check 
                                    WHERE gm_check.group_id = g.id AND gm_check.user_id = ?) as is_member_flag
                      FROM kdd_message_groups g
                      WHERE g.is_deleted = 0 AND g.authority_id = ?
                      ORDER BY g.name ASC";
        $stmtGroups = $pdo->prepare($sqlGroups);
        $stmtGroups->execute([$requestingUserId, $authorityId]);
        $groupRows = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

        if (empty($groupRows)) {
            http_response_code(200); echo json_encode([]); return; 
        }

        // Prepare groups array and collect IDs
        foreach ($groupRows as $row) {
            $groupId = $row['id'];
            $row['is_member'] = (bool)$row['is_member_flag']; // Convert flag to boolean
            unset($row['is_member_flag']); // Remove temporary flag
            $row['members'] = []; // Initialize members array
            $groupsById[$groupId] = $row;
            $groupIds[] = $groupId;
        }

        // 2. Fetch all members for these specific groups
        if (!empty($groupIds)) {
            $placeholders = rtrim(str_repeat('?,', count($groupIds)), ',');
            $sqlMembers = "SELECT gm.group_id, gm.user_id, u.username 
                          FROM kdd_message_group_members AS gm 
                          JOIN kdd_users u ON u.id = gm.user_id
                          WHERE gm.group_id IN ({$placeholders}) 
                          AND gm.authority_id = ? 
                          AND u.authority_id = ?"; // Ensure user belongs to correct authority
            $stmtMembers = $pdo->prepare($sqlMembers);
            $params = array_merge($groupIds, [$authorityId, $authorityId]); // Combine IDs, authorityId and authority
            $stmtMembers->execute($params);
            $memberRows = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

            // 3. Map members back to groups
            foreach ($memberRows as $member) {
                if (isset($groupsById[$member['group_id']])) {
                    $groupsById[$member['group_id']]['members'][] = [
                        'user_id' => (int)$member['user_id'],
                        'username' => $member['username']
                    ];
                }
            }
        }

        http_response_code(200);
        echo json_encode(array_values($groupsById)); // Return indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getGroups [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve message groups."]);
    }
}

/** Saves (Inserts or Updates) a message group and its members */
function saveGroup(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    // Expect members as an array of user IDs
    $members = isset($data['members']) && is_array($data['members'])
               ? array_filter(array_map('intval', $data['members']), fn($uid) => $uid > 0)
               : []; // Default to empty array, filter invalid IDs

    if (empty(trim($name ?? ''))) {
        http_response_code(400); echo json_encode(['error' => 'Group name is required.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();
        $logAction = ''; $logId = $id; $rowCount = 0;
        // $oldEntry = null;

        if ($id) { // Update existing group
            $logAction = 'UPDATE';
            // $oldEntry = getEntryById($pdo, $id, "kdd_message_groups");
            // if (!$oldEntry) throw new \Exception("Group with ID {$id} not found.");

            $sql = "UPDATE kdd_message_groups SET name = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$name, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update group name.");
            $rowCount = $stmt->rowCount();
            if ($rowCount === 0) error_log("Group update executed but no rows affected (ID: $id)."); // Maybe already deleted?

        } else { // Insert new group
            $logAction = 'INSERT';
            $sql = "INSERT INTO kdd_message_groups (authority_id, name) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $name]);
            if (!$success) throw new \Exception("Failed to insert group.");
            $logId = (int) $pdo->lastInsertId();
            if (!$logId) throw new \Exception("Failed to retrieve ID for new group.");
            $rowCount = 1;
        }

        // If group insert/update succeeded (or potentially 0 rows affected on update), update members
        if ($rowCount >= 0) { // Proceed even if update didn't change rows (maybe only members changed)
            $currentGroupId = $logId; // ID of the group being processed

            // Delete existing members for this group
            $sqlDeleteMembers = "DELETE FROM kdd_message_group_members WHERE group_id = ? AND authority_id = ?";
            $stmtDeleteMembers = $pdo->prepare($sqlDeleteMembers);
            if (!$stmtDeleteMembers->execute([$currentGroupId, $authorityId])) throw new \Exception("Failed to delete old group members.");

            // Insert new members
            if (!empty($members)) {
                $sqlInsertMember = "INSERT INTO kdd_message_group_members (group_id, user_id, authority_id) VALUES (?, ?, ?)";
                $stmtInsertMember = $pdo->prepare($sqlInsertMember);
                foreach ($members as $memberUserId) {
                    if (!$stmtInsertMember->execute([$currentGroupId, $memberUserId, $authorityId])) throw new \Exception("Failed to insert member {$memberUserId}.");
                }
            }
        } else {
            throw new \Exception("Group save operation failed initially.");
        }

        $pdo->commit();
        http_response_code($id ? 200 : 201); echo json_encode(["success" => true, "message" => "Group saved successfully.", "id" => $logId]);
        // Log change (complex logging needed to show member changes accurately)
        // $changes = ...; logDatabaseChange(...);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique name?
            http_response_code(409); echo json_encode(["error" => "Could not save group: Name might already exist."]);
        } else {
            error_log("Error in saveGroup [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save group: " . $e->getMessage()]);
        }
    }
}

function deleteGroup(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
     
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing group ID.']); 
        return; 
    }

    try {
        // Consider implications: Delete members from kdd_message_group_members too? Or just soft delete the group?
        // Current logic only soft deletes the group.

        $sql = "UPDATE kdd_message_groups SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Group marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "message_groups", $id, $requestingUserId, $changes);
        } elseif ($success) { 
            http_response_code(404); echo json_encode(["error" => "Group not found or already deleted."]);
        } else { 
            http_response_code(500); echo json_encode(['error' => 'Failed to execute group deletion.']); 
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteGroup [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete group."]);
    }
}

// --- Removed Duplicate Function: getUsers ---

?>