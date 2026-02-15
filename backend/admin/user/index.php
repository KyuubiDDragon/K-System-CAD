<?php
/**
 * Backend Endpoint: admin/user/index.php
 * Handles ADMIN operations for User Management (Ban, Unban, Add, Update).
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
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange etc.

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$requestingUserId = $decoded_jwt->userId ?? null; // The admin performing the action
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Admin's authority
$authorityId = $decoded_jwt->authority_id ?? null;

if ($requestingUserId === null || !is_int($requestingUserId) || $authority === null || !is_string($authority)) {
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

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getUsers'                   => 'ADMIN_READ_USERS',
    'getUserOverview'            => 'ADMIN_READ_USERS',
    'getUsersWithTodoPermissions'=> 'ADMIN_READ_USERS',
    'getGroups'                  => 'ADMIN_READ_USERS',
    'resetPassword'              => 'ADMIN_WRITE_USERS',
    'bannUser'                   => 'ADMIN_WRITE_USERS',
    'unbannUser'                 => 'ADMIN_WRITE_USERS',
    'addNewUser'                 => 'ADMIN_WRITE_USERS',
    'updateUser'                 => 'ADMIN_WRITE_USERS',
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';


// Check permission levels (ALL > ADMIN_WRITE) - No distinct READ actions left here
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission (ADMIN_WRITE_USERS)
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getUsers':                   if ($request_method === 'GET') getUsers($pdo, $authority); else MethodNotAllowed(); break;
        case 'getUserOverview':            if ($request_method === 'GET') getUserOverview($pdo, $authority); else MethodNotAllowed(); break;
        case 'getUsersWithTodoPermissions':if ($is_post_request) getUsersWithTodoPermissions($pdo, $authority); else MethodNotAllowed(); break;
        case 'getGroups':                  if ($request_method === 'GET') getGroups($pdo, $authority); else MethodNotAllowed(); break;
        case 'resetPassword':              if ($is_post_request) resetPassword($pdo, $requestingUserId, $authority); else MethodNotAllowed(); break;
        case 'bannUser':                   if ($is_post_request) bannUser($pdo, $requestingUserId, $authority); else MethodNotAllowed(); break;
        case 'unbannUser':                 if ($is_post_request) unbannUser($pdo, $requestingUserId, $authority); else MethodNotAllowed(); break;
        case 'addNewUser':                 if ($is_post_request) addNewUser($pdo, $requestingUserId, $authority); else MethodNotAllowed(); break;
        case 'updateUser':                 if ($is_post_request) updateUser($pdo, $requestingUserId, $authority); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error or action not found.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---



/** Finds the ID of the 'BANNED' role for the given authority */
function findBannedRoleId(PDO $pdo, int $authorityId): ?int {
     // Get authority ID
     global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
     if (!$authorityId) {
         error_log("Invalid authority in findBannedRoleId: {$authority}");
         return null;
     }
     
     try {
        $sql = "SELECT id FROM `kdd_roles` WHERE authority_id = ? AND name = 'BANNED' LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
     } catch (\PDOException $e) {
         error_log("Could not find BANNED role ID for authority {$authority}: " . $e->getMessage());
         return null;
     }
}

function bannUser(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    $data = getJsonRequestData();
    if (!$data) return;

    $idToBan = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$idToBan) {
        http_response_code(400); 
        echo json_encode(["error" => "User ID to ban is required."]); 
        return; 
    }
    if ($idToBan === 1) { http_response_code(400); echo json_encode(["error" => "Cannot ban the primary admin user (ID 1)."]); return; }
    if ($idToBan === $requestingUserId) { http_response_code(400); echo json_encode(["error" => "Cannot ban yourself."]); return; }

    $bannedRoleId = findBannedRoleId($pdo, $authorityId);
    if ($bannedRoleId === null) { http_response_code(500); echo json_encode(["error" => "BANNED role not found for this authority."]); return; }

    try {
        $pdo->beginTransaction();

        // 1. Set banned flag on user
        $sqlUser = "UPDATE `kdd_users` SET banned = 1 WHERE authority_id = ? AND id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $successUser = $stmtUser->execute([$authorityId, $idToBan]);
        if (!$successUser || $stmtUser->rowCount() === 0) { throw new \Exception("User not found or update failed."); }

        // 2. Add user to BANNED role (use INSERT IGNORE to avoid errors if already there)
        // Assumes kdd_user_roles is global, not authority specific
        $sqlRole = "INSERT IGNORE INTO kdd_user_roles (user_id, role_id, authority_id) VALUES (?, ?, ?)";
        $stmtRole = $pdo->prepare($sqlRole);
        if (!$stmtRole->execute([$idToBan, $bannedRoleId, $authorityId])) { throw new \Exception("Failed to add BANNED role assignment."); }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "User successfully banned."]);
        // Log change
        $changes = [['column_name' => 'banned', 'old_value' => 0, 'new_value' => 1]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "users", $idToBan, $requestingUserId, $changes); // Log against kdd_users

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in bannUser [ADMIN] (User: $idToBan, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not ban user: " . $e->getMessage()]);
    }
}


function unbannUser(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    $data = getJsonRequestData();
    if (!$data) return;
    
    $idToUnban = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$idToUnban) {
        http_response_code(400); 
        echo json_encode(["error" => "User ID to unban is required."]); 
        return; 
    }

     $bannedRoleId = findBannedRoleId($pdo, $authorityId);
     if ($bannedRoleId === null) { http_response_code(500); echo json_encode(["error" => "BANNED role not found for this authority."]); return; }

     try {
        $pdo->beginTransaction();

        // 1. Unset banned flag on user
        $sqlUser = "UPDATE `kdd_users` SET banned = 0 WHERE authority_id = ? AND id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $successUser = $stmtUser->execute([$authorityId, $idToUnban]);
         // Don't throw error if rowCount is 0, maybe user wasn't banned flag-wise anyway
         if (!$successUser) { throw new \Exception("Failed to update user banned status."); }

        // 2. Remove user from BANNED role
        $sqlRole = "DELETE FROM kdd_user_roles WHERE user_id = ? AND role_id = ?";
        $stmtRole = $pdo->prepare($sqlRole);
        if (!$stmtRole->execute([$idToUnban, $bannedRoleId])) { throw new \Exception("Failed to remove BANNED role assignment."); }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "User successfully unbanned."]);
        // Log change
        $changes = [['column_name' => 'banned', 'old_value' => 1, 'new_value' => 0]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "users", $idToUnban, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in unbannUser [ADMIN] (User: $idToUnban, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not unban user: " . $e->getMessage()]);
    }
}


function addNewUser(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
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
    
    $username = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;
    // Groups expected as array of role IDs
    $groups = isset($data['groups']) && is_array($data['groups'])
               ? array_filter(array_map('intval', $data['groups']), fn($gid) => $gid > 0)
               : [];

    // Basic Validation
    if (empty(trim($username ?? ''))) {
        http_response_code(400); 
        echo json_encode(["error" => "Username is required."]); 
        return; 
    }
    if (empty($password)) { http_response_code(400); echo json_encode(["error" => "Password is required."]); return; }
    // Add password strength validation here if needed
    if (empty($groups)) { http_response_code(400); echo json_encode(['error' => 'At least one group assignment is required.']); return; }

    try {
        // Check if username or email already exists FOR THIS AUTHORITY
        $sqlCheck = "SELECT id FROM `kdd_users` WHERE authority_id = ? AND (username = ? OR email = ?)";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $username, $email]);
        if ($stmtCheck->fetch()) {
             http_response_code(409); echo json_encode(["error" => "Username or Email already exists for this authority."]); return;
        }

        $pdo->beginTransaction();

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user (authority is the admin's authority context)
        // Add other default fields like mail headers, signature if needed
        $sqlInsertUser = "INSERT INTO `kdd_users` (authority_id, username, email, password, authority)
                          VALUES (?, ?, ?, ?, ?)";
        $stmtInsertUser = $pdo->prepare($sqlInsertUser);
        $successUser = $stmtInsertUser->execute([$authorityId, $username, $email, $hashedPassword, $authority]);
        if (!$successUser) throw new \Exception("Failed to insert user.");
        $newUserId = $pdo->lastInsertId();
        if (!$newUserId) throw new \Exception("Failed to retrieve ID for new user.");

        // Assign groups (roles)
        // Assumes kdd_user_roles is global table linking kdd_users.id and kdd_{auth}_roles.id
        // This might be wrong - if kdd_user_roles also has authority context, adjust SQL
        $sqlAssign = "INSERT INTO `kdd_user_roles` (authority_id, user_id, role_id) VALUES (?, ?, ?)";
        $stmtAssign = $pdo->prepare($sqlAssign);
        foreach ($groups as $groupId) {
             // Optional: Verify $groupId exists in kdd_{authority}_roles?
            if (!$stmtAssign->execute([$authorityId, $newUserId, $groupId])) throw new \Exception("Failed to assign role ID {$groupId}.");
        }

        // REMOVED call to createTodoList - should be handled elsewhere if needed

        $pdo->commit();
        http_response_code(201); echo json_encode(['success' => true, 'message' => 'User created successfully.', 'id' => $newUserId]);
        // Log change
        $logData = json_encode(['username'=>$username, 'email'=>$email, 'authority'=>$authority, 'groups'=>$groups]);
        $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "users", $newUserId, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in addNewUser [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add user: " . $e->getMessage()]);
    }
}


function updateUser(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
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
        echo json_encode(['error' => 'Invalid or missing user ID.']); 
        return; 
    }

    // Security check: Prevent non-super-admins from editing user ID 1?
    if ($id === 1 && $requestingUserId !== 1) {
        http_response_code(403); echo json_encode(["error" => "Operation not permitted on primary admin account."]); return;
    }

    // Get fields to update
    $username = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    // Expect 'allGroups' as array of role IDs
    $groups = isset($data['groups']) && is_array($data['groups'])
                ? array_filter(array_map('intval', $data['groups']), fn($gid) => $gid > 0)
                : null; // null means don't update groups
    $mail_header = $data['mail_header'] ?? null;
    $mail_footer = $data['mail_footer'] ?? null;
    $mail_header_neutral = $data['mail_header_neutral'] ?? null;
    $mail_footer_neutral = $data['mail_footer_neutral'] ?? null;
    $signature = $data['signature'] ?? null;
    $linked_employee = filter_var($data['linked_employee'] ?? null, FILTER_VALIDATE_INT, ['options'=>['default'=>null]]); // Allow NULL

    // Build SET part dynamically
    $setParts = []; $params = [':authority_id' => $authorityId];
    if ($username !== null) { $setParts[] = "username = :username"; $params[':username'] = $username; }
    $setParts[] = "email = :email"; $params[':email'] = $email;

    if ($mail_header !== null) { $setParts[] = "mail_header = :mail_header"; $params[':mail_header'] = $mail_header; }
    if ($mail_footer !== null) { $setParts[] = "mail_footer = :mail_footer"; $params[':mail_footer'] = $mail_footer; }
    if ($mail_header_neutral !== null) { $setParts[] = "mail_header_neutral = :mail_header_neutral"; $params[':mail_header_neutral'] = $mail_header_neutral; }
    if ($mail_footer_neutral !== null) { $setParts[] = "mail_footer_neutral = :mail_footer_neutral"; $params[':mail_footer_neutral'] = $mail_footer_neutral; }
    if ($signature !== null) { $setParts[] = "signature = :signature"; $params[':signature'] = $signature; }
    if (array_key_exists('linked_employee', $data)) { // Allow setting it to NULL (array_key_exists works with null values, isset does not)
        $setParts[] = "linked_employee = :linked_employee"; $params[':linked_employee'] = $linked_employee;
    }

    if (empty($setParts) && $groups === null) {
        http_response_code(400); echo json_encode(['error' => 'No data provided for update.']); return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update User Details (if any fields provided)
        if (!empty($setParts)) {
            $params[':id'] = $id;

            $sqlUser = "UPDATE `kdd_users` SET " . implode(', ', $setParts) . " WHERE authority_id = :authority_id AND id = :id";
            $stmtUser = $pdo->prepare($sqlUser);
            if (!$stmtUser->execute($params)) { throw new \Exception("Failed to update user details."); }
            if ($stmtUser->rowCount() === 0) { /* User not found or no change? Handle if needed */ }
        }

        // 2. Update Group Assignments (if groups array was provided)
        if ($groups !== null) {
            // Delete existing assignments
            $sqlDeleteRoles = "DELETE FROM kdd_user_roles WHERE authority_id = ? AND user_id = ?";
            $stmtDeleteRoles = $pdo->prepare($sqlDeleteRoles);
            if (!$stmtDeleteRoles->execute([$authorityId, $id])) throw new \Exception("Failed to remove old role assignments.");

            // Insert new assignments
            if (!empty($groups)) {
                $sqlAssign = "INSERT INTO `kdd_user_roles` (authority_id, user_id, role_id) VALUES (?, ?, ?)";
                $stmtAssign = $pdo->prepare($sqlAssign);
                foreach ($groups as $groupId) {
                    // Optional: Verify $groupId exists in kdd_{authority}_roles?
                    if (!$stmtAssign->execute([$authorityId, $id, $groupId])) throw new \Exception("Failed to assign role ID {$groupId}.");
                }
            }
        }

        $pdo->commit();
        http_response_code(200); echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        // Log change (complex, need old/new data for user fields and groups)
        // logDatabaseChange(...);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique constraint (username/email?)
            http_response_code(409); echo json_encode(["error" => "Could not update user: Username or Email might already exist."]);
        } else {
            error_log("Error in updateUser [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not update user: " . $e->getMessage()]);
        }
    }
}

function getUsers(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT u.id, username, COALESCE(CONCAT('[', e.servicenumber, '] ', e.name), u.username) as employeename 
                FROM `kdd_users` u 
                LEFT JOIN `kdd_employee` e ON e.authority_id = ? AND u.linked_employee = e.id
                WHERE u.authority_id = ?
                ORDER BY username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("DB error in getUsers [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve users."]);
    }
}

function getUserOverview(PDO $pdo, string $authority): void {
    try {
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        $sql = "SELECT
                    u.id,
                    u.username,
                    u.email,
                    GROUP_CONCAT(r.name ORDER BY r.sort_order) AS groups,
                    u.mail_header,
                    u.mail_footer,
                    u.mail_header_neutral,
                    u.mail_footer_neutral,
                    u.signature,
                    u.last_login,
                    u.banned,
                    u.linked_employee,
                    u.authority,
                    e.name,
                    e.servicenumber
                FROM
                    kdd_users u
                LEFT JOIN `kdd_user_roles` ur ON ur.authority_id = ? AND u.id = ur.user_id
                LEFT JOIN `kdd_roles` r ON r.authority_id = ? AND ur.role_id = r.id
                LEFT JOIN `kdd_employee` e ON e.authority_id = ? AND u.linked_employee = e.id
                WHERE u.authority_id = ?
                GROUP BY u.id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $authorityId, $authorityId]);
        $users = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get the user's groups - Fix the syntax error in this query
            $userGroupsSql = "SELECT r.* FROM `kdd_user_roles` ur 
                            JOIN `kdd_roles` r ON r.authority_id = ? AND ur.role_id = r.id
                            WHERE ur.authority_id = ? AND ur.user_id = ?";
            $userGroupsStmt = $pdo->prepare($userGroupsSql);
            $userGroupsStmt->execute([$authorityId, $authorityId, $row['id']]);
            $userGroups = $userGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $row['allGroups'] = $userGroups;
            $users[] = $row;
        }
        
        http_response_code(200);
        echo json_encode($users);
    
    } catch (\PDOException $e) {
        error_log("DB error in getUserOverview [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve user overview."]);
    }
}

function getUsersWithTodoPermissions(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        // Get data from JSON instead of POST
        $data = getJsonRequestData();
        if (!$data) return;
        
        $listId = filter_var($data['list_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$listId) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid list ID"]);
            return;
        }
        
        $sql = "SELECT DISTINCT 
                kdd_users.id, 
                kdd_users.username
                FROM kdd_users
                LEFT JOIN kdd_ta_todolist_permissions ON kdd_ta_todolist_permissions.user_id = kdd_users.id
                WHERE (kdd_ta_todolist_permissions.list_id = ?
                OR kdd_ta_todolist_permissions.list_id = (SELECT parent_list_id FROM `kdd_reports` AS WHERE authority_id = ? id = ?)) 
                AND kdd_users.authority_id = ?
                GROUP BY kdd_users.id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $listId, $listId, $authorityId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("DB error in getUsersWithTodoPermissions [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve users with todo permissions."]);
    }
}

function getGroups(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_roles` WHERE authority_id = ? ORDER BY sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($groups);
    } catch (\PDOException $e) {
        error_log("DB error in getGroups [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve groups."]);
    }
}

function resetPassword(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        // Get data from JSON instead of POST
        $data = getJsonRequestData();
        if (!$data) return;
        
        $userId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $new_password = $data['password'] ?? null;
        
        if (!$userId || !$new_password) {
            http_response_code(400);
            echo json_encode(["error" => "User ID and new password are required."]);
            return;
        }
        
        if ($userId == 1) {
            http_response_code(400);
            echo json_encode(["message" => "Das darfst du nicht!"]);
            return;
        }
        
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE `kdd_users` SET password = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$hashed_new_password, $authorityId, $userId]);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Passwort wurde erfolgreich geändert."]);
            
            // Log the change
            $changes = [['column_name' => 'password', 'old_value' => '********', 'new_value' => '********']];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update password."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in resetPassword [ADMIN] (User ID: $userId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not reset password."]);
    }
}

?>