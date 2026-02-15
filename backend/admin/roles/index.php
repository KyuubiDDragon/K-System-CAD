<?php
/**
 * Backend Endpoint: admin/roles/index.php
 * Handles ADMIN CRUD operations for Roles and Role-Permission assignments.
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
$userId = $decoded_jwt->userId ?? null;
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
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to default features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format matching DB structure)
// DB has: module='admin', sub_module='users'/'roles'/etc
$permissions_map = [
    'getRolePermissions' => ['module' => 'admin.users', 'action' => 'read'],
    'getRoles'           => ['module' => 'admin.users', 'action' => 'read'],
    'getPermissions'     => ['module' => 'admin.users', 'action' => 'read'],
    'getCategoryRoles'   => ['module' => 'admin.users', 'action' => 'read'],
    'updateRole'         => ['module' => 'admin.users', 'action' => 'write'],
    'createRole'         => ['module' => 'admin.users', 'action' => 'write'],
    'deleteRole'         => ['module' => 'admin.users', 'action' => 'write'],
];

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}

if (!isset($permissions_map[$action])) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permissions using bitmask system
$required = $permissions_map[$action];
$has_permission = false;

// Check for ALL_PERMISSIONS first (super admin)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} else {
    // Check module.action permission
    $has_permission = hasModulePermission($userPermissions, $required['module'], $required['action']);
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getRolePermissions': if ($request_method === 'GET') getRolePermissions($pdo, $authority); else MethodNotAllowed(); break;
        case 'getRoles':           if ($request_method === 'GET') getRoles($pdo, $authority); else MethodNotAllowed(); break;
        case 'getPermissions':     if ($request_method === 'GET') getPermissions($pdo, $authority); else MethodNotAllowed(); break;
        case 'updateRole':         if ($is_post_request) updateRole($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'createRole':         if ($is_post_request) createRole($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'deleteRole':         if ($is_post_request) deleteRole($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'getCategoryRoles':   if ($request_method === 'GET') getCategoryRoles($pdo, $authority); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

function getPermissions(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_permissions` ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($permissions);
    } catch (\PDOException $e) {
        error_log("DB error in getPermissions [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve permissions."]);
    }
}

function getRoles(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_roles` WHERE authority_id = ? AND is_deleted = 0 ORDER BY sort_order ASC, name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($roles);
    } catch (\PDOException $e) {
        error_log("DB error in getRoles [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve roles."]);
    }
}

function getRolePermissions(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_role_permissions` WHERE authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $rolePermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($rolePermissions);
    } catch (\PDOException $e) {
        error_log("DB error in getRolePermissions [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve role permissions."]);
    }
}

function createRole(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
        MethodNotAllowed(); 
        return; 
    }

    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;

    $name = $data['name'] ?? null;
    $description = $data['description'] ?? '';
    $power = filter_var($data['power'] ?? 0, FILTER_VALIDATE_INT, ['options'=>['default'=>0]]);
    $sort_order = filter_var($data['sort_order'] ?? 25, FILTER_VALIDATE_INT, ['options'=>['default'=>25]]);
    // Expect permissions as an array of IDs
    $permissions = isset($data['permissions']) && is_array($data['permissions'])
                ? array_filter(array_map('intval', $data['permissions']), fn($id) => $id > 0)
                : [];

    // Add category handling
    $categoryIds = isset($data['categoryIds']) && is_array($data['categoryIds'])
                ? array_filter(array_map('intval', $data['categoryIds']), fn($id) => $id > 0)
                : [];

    if (empty(trim($name ?? ''))) { 
        http_response_code(400); 
        echo json_encode(['error' => 'Role name is required.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Role
        $sqlRole = "INSERT INTO `kdd_roles` (authority_id, name, description, power, sort_order) VALUES (?, ?, ?, ?, ?)";
        $stmtRole = $pdo->prepare($sqlRole);
        $successRole = $stmtRole->execute([$authorityId, $name, $description, $power, $sort_order]);
        if (!$successRole) throw new \Exception("Failed to insert role.");
        $roleId = $pdo->lastInsertId();
        if (!$roleId) throw new \Exception("Failed to retrieve ID for new role.");

        // 2. Insert Permissions if any provided
        if (!empty($permissions)) {
            $sqlPerm = "INSERT INTO `kdd_role_permissions` (authority_id, role_id, permission_id) VALUES (?, ?, ?)";
            $stmtPerm = $pdo->prepare($sqlPerm);
            foreach ($permissions as $permissionId) {
                 // Optional: Check if permissionId exists in kdd_{authority}_permissions table first?
                 if (!$stmtPerm->execute([$authorityId, $roleId, $permissionId])) {
                      throw new \Exception("Failed to assign permission ID {$permissionId} to role {$roleId}.");
                 }
            }
        }

        // Insert new category assignments
        if (!empty($categoryIds)) {
            $sqlCategory = "INSERT INTO `kdd_report_category_user_roles` (role_id, report_category_id) VALUES (?, ?)";
            $stmtCategory = $pdo->prepare($sqlCategory);
            foreach ($categoryIds as $categoryId) {
                if (!$stmtCategory->execute([$roleId, $categoryId])) {
                    throw new \Exception("Failed to assign category ID {$categoryId} to role {$roleId}.");
                 }
            }
        }

        $pdo->commit();
        http_response_code(201); echo json_encode(["success" => true, "message" => "Role created successfully.", "id" => $roleId]);
        // Log change
        // $changes = ...; logDatabaseChange(...);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique name?
            http_response_code(409); echo json_encode(["error" => "Could not create role: Name might already exist."]);
         } else {
            error_log("Error in createRole [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not create role: " . $e->getMessage()]);
         }
    }
}

function updateRole(PDO $pdo, int $requestingUserId, string $authority): void {

    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
        MethodNotAllowed(); 
        return; 
    }

    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $description = $data['description'] ?? '';
    $power = filter_var($data['power'] ?? 0, FILTER_VALIDATE_INT, ['options'=>['default'=>0]]);
    $sort_order = filter_var($data['sort_order'] ?? 25, FILTER_VALIDATE_INT, ['options'=>['default'=>25]]);
    $permissions = isset($data['permissions']) && is_array($data['permissions'])
                    ? array_filter(array_map('intval', $data['permissions']), fn($pid) => $pid > 0)
                    : []; // Empty array means remove all permissions

    // Add category handling
    $categoryIds = isset($data['categoryIds']) && is_array($data['categoryIds'])
                ? array_filter(array_map('intval', $data['categoryIds']), fn($id) => $id > 0)
                : [];

    if (!$id || empty(trim($name ?? ''))) { 
        http_response_code(400); 
        echo json_encode(['error' => 'Role ID and name are required.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();

        // Find the correct authority_id for this role
        $findSql = "SELECT authority_id FROM `kdd_roles` WHERE id = ? AND is_deleted = 0";
        $findStmt = $pdo->prepare($findSql);
        $findStmt->execute([$id]);
        $roleAuthorityId = $findStmt->fetchColumn();
        
        if ($roleAuthorityId === false) {
            throw new \Exception("Role with ID {$id} not found or already deleted.");
        }
        
        // 1. Update Role Details with the role's actual authority_id
        $sqlRole = "UPDATE `kdd_roles` SET name = ?, description = ?, power = ?, sort_order = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmtRole = $pdo->prepare($sqlRole);
        $successRole = $stmtRole->execute([$name, $description, $power, $sort_order, $roleAuthorityId, $id]);
        if (!$successRole) throw new \Exception("Failed to update role data.");

        // 2. Delete existing permission assignments
        $sqlDeletePerms = "DELETE FROM kdd_role_permissions WHERE role_id = ?";
        $stmtDeletePerms = $pdo->prepare($sqlDeletePerms);
        if (!$stmtDeletePerms->execute([$id])) throw new \Exception("Failed to delete old permissions.");

        // 3. Insert new permission assignments
        if (!empty($permissions)) {
            $sqlPerm = "INSERT INTO `kdd_role_permissions` (authority_id, role_id, permission_id) VALUES (?, ?, ?)";
            $stmtPerm = $pdo->prepare($sqlPerm);
            foreach ($permissions as $permissionId) {
                 // Optional: Check if permissionId exists?
                 if (!$stmtPerm->execute([$roleAuthorityId, $id, $permissionId])) {
                      throw new \Exception("Failed to assign permission ID {$permissionId} to role {$id}.");
                 }
            }
        }

        // Delete existing category assignments
        $sqlDeleteCategories = "DELETE FROM kdd_report_category_user_roles WHERE role_id = ?";
        $stmtDeleteCategories = $pdo->prepare($sqlDeleteCategories);
        if (!$stmtDeleteCategories->execute([$id])) {
            throw new \Exception("Failed to delete old category assignments.");
        }

        // Insert new category assignments
        if (!empty($categoryIds)) {
            $sqlCategory = "INSERT INTO `kdd_report_category_user_roles` (role_id, report_category_id) VALUES (?, ?)";
            $stmtCategory = $pdo->prepare($sqlCategory);
            foreach ($categoryIds as $categoryId) {
                if (!$stmtCategory->execute([$id, $categoryId])) {
                    throw new \Exception("Failed to assign category ID {$categoryId} to role {$id}.");
                 }
            }
        }

        $pdo->commit();

        // ✅ Notify affected users via Socket.io that permissions have changed
        try {
            require_once __DIR__ . '/../../utils/SocketClient.php';
            $socketClient = new \Utils\SocketClient();

            // Get all users with this role
            $stmt = $pdo->prepare("SELECT DISTINCT user_id FROM kdd_user_roles WHERE role_id = ? AND authority_id = ?");
            $stmt->execute([$id, $roleAuthorityId]);
            $affectedUserIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Send permission refresh event to each affected user
            foreach ($affectedUserIds as $userId) {
                $socketClient->broadcastEvent('permissions:refresh', [
                    'message' => 'Your permissions have been updated',
                    'roleId' => $id,
                    'roleName' => $name
                ], $userId);
                error_log("[PERMISSIONS] Sent refresh event to user {$userId} (role {$id} updated)");
            }
        } catch (\Exception $e) {
            // Socket notification failed - log but don't fail the request
            error_log("[PERMISSIONS] Failed to send Socket.io notification: " . $e->getMessage());
        }

        http_response_code(200); echo json_encode(["success" => true, "message" => "Role updated successfully."]);
        // Log change (complex due to permission changes)
        // logDatabaseChange(...);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique name?
            http_response_code(409); echo json_encode(["error" => "Could not update role: Name might already exist."]);
         } else {
            error_log("Error in updateRole [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not update role: " . $e->getMessage()]);
         }
    }
}

function deleteRole(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) { 
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing role ID.']); 
        return; 
    }

    try {
        $pdo->beginTransaction();
        
        // 1. Check if any users are assigned to this role
        $sqlCheckUsers = "SELECT COUNT(*) FROM `kdd_user_roles` WHERE authority_id = ? AND role_id = ?";
        $stmtCheckUsers = $pdo->prepare($sqlCheckUsers);
        $stmtCheckUsers->execute([$authorityId, $id]);
        $userCount = (int)$stmtCheckUsers->fetchColumn();
        
        if ($userCount > 0) {
            // Users have this role assigned, prevent deletion
            http_response_code(409); // Conflict
            echo json_encode([
                'error' => 'Cannot delete role: It is still assigned to ' . $userCount . ' user(s). Remove all assignments first.'
            ]);
            $pdo->rollBack();
            return;
        }
        
        // 2. Mark role as deleted (soft delete)
        $sqlDelete = "UPDATE `kdd_roles` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$authorityId, $id]);
        
        if ($stmtDelete->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Role not found or already deleted.']);
            $pdo->rollBack();
            return;
        }
        
        // 3. Delete all permission assignments for this role
        $sqlDeletePerms = "DELETE FROM kdd_role_permissions WHERE role_id = ?";
        $stmtDeletePerms = $pdo->prepare($sqlDeletePerms);
        $stmtDeletePerms->execute([$id]);
        
        // Delete all category assignments for this role
        $sqlDeleteCategories = "DELETE FROM kdd_report_category_user_roles WHERE role_id = ?";
        $stmtDeleteCategories = $pdo->prepare($sqlDeleteCategories);
        $stmtDeleteCategories->execute([$id]);
        
        $pdo->commit();
        
        // Success response
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Role successfully marked as deleted.'
        ]);
        
        // Log the change
        $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "roles", $id, $requestingUserId, $changes);
        
    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in deleteRole [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete role: " . $e->getMessage()]);
    }
}

// Add new function for getting category roles
function getCategoryRoles(PDO $pdo, string $authority): void {
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    $roleId = filter_input(INPUT_GET, 'roleId', FILTER_VALIDATE_INT);
    if (!$roleId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role ID']);
        return;
    }

    try {
        // Get all categories for this authority with their role assignment status
        $sql = "SELECT 
                    c.*,
                    CASE WHEN r.role_id IS NOT NULL THEN 1 ELSE 0 END as has_role
                FROM kdd_report_category c
                LEFT JOIN kdd_report_category_user_roles r 
                    ON c.id = r.report_category_id 
                    AND r.role_id = :roleId
                WHERE c.authority_id = :authorityId
                ORDER BY c.title ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':authorityId' => $authorityId,
            ':roleId' => $roleId
        ]);
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get the IDs of categories that have the role assigned
        $sqlIds = "SELECT report_category_id 
                  FROM kdd_report_category_user_roles 
                  WHERE role_id = :roleId";
        $stmtIds = $pdo->prepare($sqlIds);
        $stmtIds->execute([':roleId' => $roleId]);
        $categoryIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        
        http_response_code(200);
        echo json_encode([
            'categories' => $categories,
            'categoryIds' => $categoryIds
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in getCategoryRoles [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve category roles."]);
    }
}
?>