<?php
/**
 * Backend Endpoint: admin/authority/index.php
 * Handles ADMIN CRUD operations for Authorities and their Features.
 * Uses Cookie-based Authentication and PDO database connection.
 * Routing based on ?action=... parameter.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    error_log("Database connection failed in admin/authority/index.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php';
require_once __DIR__ . '/../../utils/permission_helper.php';

// --- Role Check ---
$userPermissions = $decoded_jwt->permissions ?? [];
if (!hasPermission($userPermissions, 'ADMIN') && !hasAllPermissions($userPermissions)) {
    http_response_code(403); echo json_encode(["error" => "Forbidden. Admin role or ALL_PERMISSIONS required."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
// Authority from user's token is needed for logging changes, but authorityId is used for filtering/access checks
$currentUserAuthorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $currentUserAuthorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

// For authority management, we need to check if the user has system admin rights
// This endpoint should only be accessible to admins with system management permissions
require_once __DIR__ . '/../../utils/permission_helper.php';

// For authority management, we must check for system admin permissions
if (!hasPermission($userPermissions, 'SYSTEM_ADMIN')) {
    http_response_code(403); echo json_encode(["error" => "You need system administrator rights for authority management."]); exit();
}

// --- Action Routing ---
$action = $_REQUEST['action'] ?? ''; // Get action from GET or POST request parameters
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions and allowed methods
// **WICHTIG:** Passe Berechtigungsnamen an dein System an!
$action_map = [
    'getAuthorities'        => ['permission' => 'SYSTEM_ADMIN', 'method' => 'GET', 'function' => 'getAuthorities'],
    'getAuthority'          => ['permission' => 'SYSTEM_ADMIN', 'method' => 'GET', 'function' => 'getAuthority'], // Get one authority by ID
    'getFeatures'           => ['permission' => 'SYSTEM_ADMIN', 'method' => 'GET', 'function' => 'getFeatures'], // Get ALL system features
    'getAuthorityFeatures'  => ['permission' => 'SYSTEM_ADMIN', 'method' => 'GET', 'function' => 'getAuthorityFeatures'], // Get features assigned to a specific authority ID
    'createAuthority'       => ['permission' => 'SYSTEM_ADMIN', 'method' => 'POST', 'function' => 'createAuthority'],
    'updateAuthority'       => ['permission' => 'SYSTEM_ADMIN', 'method' => 'POST', 'function' => 'updateAuthority'], // Using POST and ID in body like employee example
    'deleteAuthority'       => ['permission' => 'SYSTEM_ADMIN', 'method' => 'POST', 'function' => 'deleteAuthority'], // Using POST and ID in body like employee example
    'updateAuthorityFeatures' => ['permission' => 'SYSTEM_ADMIN', 'method' => 'POST', 'function' => 'updateAuthorityFeatures'], // Assign features
];

$route = $action_map[$action] ?? null;

// Validate action and method
if ($action === '' || $route === null) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing action specified."]);
    exit();
}

if ($request_method !== $route['method']) {
    http_response_code(405);
    echo json_encode(['error' => "Method Not Allowed. Action '" . htmlspecialchars($action) . "' requires " . $route['method']]);
    exit();
}

// Check specific permission (SYSTEM_ADMIN is already checked broadly, but this adds a layer per action if needed)
// For this specific endpoint, the initial SYSTEM_ADMIN check covers all actions.
// If you had more granular permissions later (e.g., edit_authority, delete_authority), you'd check route['permission'] here.
// For now, we rely on the initial broad check.

// --- Execute Action ---
$function_to_call = $route['function'];

// Pass $pdo and $userId, plus any action-specific parameters
switch ($function_to_call) {
    case 'getAuthorities':
    case 'getFeatures':
        // No additional parameters needed
        $function_to_call($pdo);
        break;
    case 'getAuthority':
    case 'getAuthorityFeatures':
        // Needs Authority ID, expected in $_REQUEST['id'] for GETs
        $id = filter_var($_REQUEST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
             http_response_code(400);
             echo json_encode(['error' => 'Missing or invalid ID parameter.']);
             exit();
        }
        $function_to_call($pdo, $id);
        break;
    case 'createAuthority':
        // Needs data from POST body
        $function_to_call($pdo, $userId); // userId for logging, data is read inside function
        break;
    case 'updateAuthority':
    case 'deleteAuthority':
        // Needs ID and data from POST body (emulating employee endpoint)
        // ID must be read from the body inside the function
         $function_to_call($pdo, $userId); // userId for logging, ID and data read inside function
        break;
    case 'updateAuthorityFeatures':
        // Needs Authority ID and features array from POST body
        // Both read from body inside the function
         $function_to_call($pdo, $userId); // userId for logging, data read inside function
        break;
    default:
        // Should not happen if action_map is correct
        http_response_code(500);
        echo json_encode(['error' => 'Internal server routing error.']);
        error_log("Authority endpoint: Unknown function requested: " . $function_to_call);
        break;
}

exit(); // Ensure no further code is executed

// --- Function Implementations ---
// (Behalten die ursprünglichen Funktionen bei, passen aber die Parameter an, falls nötig)

/**
 * Get all authorities
 */
function getAuthorities(PDO $pdo): void {
    try {
        $sql = "SELECT id, name, display_name, description, active, created_at, updated_at FROM kdd_authorities ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $authorities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($authorities);
    } catch (\PDOException $e) {
        error_log("DB error in getAuthorities: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve authorities."]);
    }
}

/**
 * Get a specific authority by ID
 */
function getAuthority(PDO $pdo, int $id): void {
    try {
        $sql = "SELECT id, name, display_name, description, active, created_at, updated_at FROM kdd_authorities WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $authority = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($authority) {
            http_response_code(200);
            echo json_encode($authority);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Authority not found."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in getAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve authority."]);
    }
}

/**
 * Get all system features
 */
function getFeatures(PDO $pdo): void {
    try {
        $sql = "SELECT id, name, code, description, created_at, updated_at FROM kdd_authority_features ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($features);
    } catch (\PDOException $e) {
        error_log("DB error in getFeatures: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve features."]);
    }
}

/**
 * Get features assigned to a specific authority
 */
function getAuthorityFeatures(PDO $pdo, int $authorityId): void {
    try {
        // Debug für Nachverfolgung
        error_log("Fetching assigned features for authority ID: $authorityId");

        // Diese Abfrage holt die Feature-IDs, die der Authority zugeordnet sind
        $sql = "SELECT feature_id
                 FROM kdd_authority_features_rel
                 WHERE authority_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $featureIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        error_log("Found assigned feature IDs for authority $authorityId: " . json_encode($featureIds));

        // Gib das Array der IDs zurück
        http_response_code(200);
        echo json_encode($featureIds);
    } catch (\PDOException $e) {
        error_log("DB error in getAuthorityFeatures: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve authority features."]);
    }
}

/**
 * Create a new authority
 * Data from POST body. User ID for logging.
 */
function createAuthority(PDO $pdo, int $userId): void {
    global $authorityId; // Access the global authorityId variable
    
    $data = getJsonRequestData();
    if (!$data) return;
    
    $name = $data['name'] ?? null;
    $display_name = $data['display_name'] ?? null;
    $description = $data['description'] ?? '';
    $active = isset($data['active']) ? (bool)$data['active'] : true;
    $createAdminAccount = isset($data['create_admin_account']) ? (bool)$data['create_admin_account'] : false;
    $adminUsername = $data['admin_username'] ?? null;
    $adminEmail = $data['admin_email'] ?? null;
    $adminPassword = $data['admin_password'] ?? null;
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Authority name is required.']);
        return;
    }
    
    if (empty($display_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Authority display name is required.']);
        return;
    }
    
    // If creating admin account, validate required fields
    if ($createAdminAccount) {
        if (empty($adminUsername) || strlen($adminUsername) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $adminUsername)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid admin username is required (min 3 characters, alphanumeric and underscores only).']);
            return;
        }
        
        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid admin email is required when creating admin account.']);
            return;
        }
        
        if (empty($adminPassword) || strlen($adminPassword) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Admin password must be at least 8 characters.']);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if authority name already exists
        $checkSql = "SELECT COUNT(*) FROM kdd_authorities WHERE name = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$name]);
        
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'An authority with this name already exists.']);
            return;
        }

        
        // Insert new authority
        $sql = "INSERT INTO kdd_authorities (name, display_name, description, active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $display_name, $description, $active]);
        
        if (!$success) {
            throw new \Exception("Failed to create authority record");
        }
        
        $newAuthorityId = $pdo->lastInsertId();
        
        // Log the change
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'display_name', 'old_value' => null, 'new_value' => $display_name],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
            ['column_name' => 'active', 'old_value' => null, 'new_value' => $active ? '1' : '0']
        ];
        logDatabaseChange((int)$newAuthorityId, $pdo, 'INSERT', 'authority', (int)$newAuthorityId, $userId, $changes);
        
        // Create default job role "Mitarbeiter" for this authority
        $jobroleInsertSql = "INSERT INTO kdd_jobroles (name, authority_id) VALUES (?, ?)";
        $jobroleStmt = $pdo->prepare($jobroleInsertSql);
        if (!$jobroleStmt->execute(['Mitarbeiter', $newAuthorityId])) {
            throw new \Exception("Failed to create default job role");
        }
        
        $jobroleId = $pdo->lastInsertId();
        
        // Log job role creation
        logDatabaseChange((int)$newAuthorityId, $pdo, 'INSERT', 'jobrole', (int)$jobroleId, $userId, [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => 'Mitarbeiter'],
            ['column_name' => 'authority_id', 'old_value' => null, 'new_value' => (string)$newAuthorityId]
        ]);

        // Create default blackboard areas for this authority
        $blackboardAreas = [
            [
                'key' => 'administration',
                'name' => 'Verwaltung',
                'description' => 'Informationen von der Verwaltung und Administration',
                'icon' => 'mdi-office-building',
                'sort_order' => 10
            ],
            [
                'key' => 'employees',
                'name' => 'Mitarbeiter',
                'description' => 'Interne Kommunikation für alle Mitarbeiter',
                'icon' => 'mdi-account-group',
                'sort_order' => 20
            ]
        ];

        foreach ($blackboardAreas as $areaData) {
            $areaInsertSql = "INSERT INTO kdd_blackboard_areas (authority_id, `key`, name, description, icon, is_active, sort_order)
                              VALUES (?, ?, ?, ?, ?, 1, ?)";
            $areaStmt = $pdo->prepare($areaInsertSql);
            if (!$areaStmt->execute([
                $newAuthorityId,
                $areaData['key'],
                $areaData['name'],
                $areaData['description'],
                $areaData['icon'],
                $areaData['sort_order']
            ])) {
                throw new \Exception("Failed to create default blackboard area: " . $areaData['key']);
            }

            $areaId = $pdo->lastInsertId();

            // Log area creation
            logDatabaseChange((int)$newAuthorityId, $pdo, 'INSERT', 'blackboard_area', (int)$areaId, $userId, [
                ['column_name' => 'key', 'old_value' => null, 'new_value' => $areaData['key']],
                ['column_name' => 'name', 'old_value' => null, 'new_value' => $areaData['name']],
                ['column_name' => 'authority_id', 'old_value' => null, 'new_value' => (string)$newAuthorityId]
            ]);
        }

        // Create admin role and user if requested
        $adminRoleId = null;
        $adminUserId = null;
        
        if ($createAdminAccount) {
            // 1. Create admin role for this authority
            $roleInsertSql = "INSERT INTO kdd_roles (authority_id, name, description) 
                              VALUES (?, 'Administrator', 'System Administrator with full access')";
            $roleStmt = $pdo->prepare($roleInsertSql);
            if (!$roleStmt->execute([$newAuthorityId])) {
                throw new \Exception("Failed to create administrator role");
            }
            
            $adminRoleId = $pdo->lastInsertId();
            
            // Log role creation
            logDatabaseChange((int)$newAuthorityId, $pdo, 'INSERT', 'role', (int)$adminRoleId, $userId, [
                ['column_name' => 'name', 'old_value' => null, 'new_value' => 'Administrator'],
                ['column_name' => 'is_admin', 'old_value' => null, 'new_value' => '1']
            ]);
            
            // 2. Get all permissions from the database
            $getPermissionsSql = "SELECT id FROM kdd_permissions";
            $getPermissionsStmt = $pdo->prepare($getPermissionsSql);
            $getPermissionsStmt->execute();
            $permissions = $getPermissionsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($permissions)) {
                throw new \Exception("No permissions found in the database");
            }
            
            // 3. Assign all permissions to this role
            $permissionInsertSql = "INSERT INTO kdd_role_permissions (role_id, permission_id, authority_id) VALUES (?, ?, ?)";
            $permissionStmt = $pdo->prepare($permissionInsertSql);
            
            // Insert each permission
            foreach ($permissions as $permissionId) {
                if (!$permissionStmt->execute([$adminRoleId, $permissionId, $newAuthorityId])) {
                    throw new \Exception("Failed to assign permission ID {$permissionId} to administrator role");
                }
            }
            
            // 4. Create admin user
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            
            // Set default values for fields required by JWT
            $mailHeader = '<p>Dear User,</p>';
            $mailFooter = '<p>Best regards,<br>Your ' . $name . ' Team</p>';
            $mailHeaderNeutral = '<p>Dear User,</p>';
            $mailFooterNeutral = '<p>Best regards,<br>Your ' . $name . ' Team</p>';
            
            $userInsertSql = "INSERT INTO kdd_users (authority_id, username, email, password, authority, 
                               mail_header, mail_footer, mail_header_neutral, mail_footer_neutral) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $userStmt = $pdo->prepare($userInsertSql);
            if (!$userStmt->execute([
                $newAuthorityId, 
                $adminUsername, 
                $adminEmail, 
                $hashedPassword, 
                $name,
                $mailHeader,
                $mailFooter,
                $mailHeaderNeutral,
                $mailFooterNeutral
            ])) {
                throw new \Exception("Failed to create administrator user");
            }
            
            $adminUserId = $pdo->lastInsertId();
            
            // Log user creation (don't log password hash)
            logDatabaseChange((int)$newAuthorityId, $pdo, 'INSERT', 'user', (int)$adminUserId, $userId, [
                ['column_name' => 'username', 'old_value' => null, 'new_value' => $adminUsername],
                ['column_name' => 'email', 'old_value' => null, 'new_value' => $adminEmail],
                ['column_name' => 'authority_id', 'old_value' => null, 'new_value' => (string)$newAuthorityId],
                ['column_name' => 'authority', 'old_value' => null, 'new_value' => $name],
                ['column_name' => 'is_active', 'old_value' => null, 'new_value' => '1']
            ]);
            
            // 5. Assign role to user
            $userRoleInsertSql = "INSERT INTO kdd_user_roles (user_id, role_id, authority_id) VALUES (?, ?, ?)";
            $userRoleStmt = $pdo->prepare($userRoleInsertSql);
            if (!$userRoleStmt->execute([$adminUserId, $adminRoleId, $newAuthorityId])) {
                throw new \Exception("Failed to assign role to administrator user");
            }

            // 6. Set default permissions for blackboard areas (admin gets READ + WRITE + DELETE)
            $areaPermissionsSql = "INSERT INTO kdd_blackboard_area_permissions (area_id, role_id, authority_id, can_read, can_write, can_delete)
                                   SELECT ba.id, ?, ?, 1, 1, 1
                                   FROM kdd_blackboard_areas ba
                                   WHERE ba.authority_id = ? AND ba.key IN ('administration', 'employees')";
            $areaPermStmt = $pdo->prepare($areaPermissionsSql);
            if (!$areaPermStmt->execute([$adminRoleId, $newAuthorityId, $newAuthorityId])) {
                throw new \Exception("Failed to set default blackboard area permissions for admin role");
            }
        }

        // Commit all changes
        $pdo->commit();
        
        // Return success response
        http_response_code(201); // Created
        $response = [
            'success' => true,
            'message' => 'Authority created successfully.',
            'id' => $newAuthorityId
        ];
        
        if ($createAdminAccount) {
            $response['admin_account'] = [
                'role_id' => $adminRoleId,
                'user_id' => $adminUserId,
                'username' => $adminUsername,
                'email' => $adminEmail,
                'authority' => $name,
                'authority_id' => (int)$newAuthorityId
            ];
        }
        
        echo json_encode($response);
        
    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error in createAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create authority: " . $e->getMessage()]);
    }
}

/**
 * Update an existing authority
 * Data including ID from POST body. User ID for logging.
 */
function updateAuthority(PDO $pdo, int $userId): void {
    // Get data from JSON body
    $data = getJsonRequestData();
    if (!$data) return; // getJsonRequestData handles errors

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT); // Get ID from body
    $name = $data['name'] ?? null;
    $display_name = $data['display_name'] ?? null;
    $description = $data['description'] ?? null;
    $active = isset($data['active']) ? (bool)$data['active'] : null; // Use null to check if active was provided

    if ($id === false || $id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid authority ID in request body.']);
        return;
    }
    if (empty($name)) { // Name is required
        http_response_code(400);
        echo json_encode(['error' => 'Authority name is required.']);
        return;
    }
    if (empty($display_name)) { // Display name is required
        http_response_code(400);
        echo json_encode(['error' => 'Authority display name is required.']);
        return;
    }
     // Active is optional, no check needed if null is acceptable for no change, but update query needs a non-null value
     // Assuming active must be boolean if provided, let's check if it was explicitly set
     $activeProvided = array_key_exists('active', $data);


    try {
        $pdo->beginTransaction();

        // Fetch current data for logging changes and existence check
        $currentSql = "SELECT name, display_name, description, active FROM kdd_authorities WHERE id = ?";
        $currentStmt = $pdo->prepare($currentSql);
        $currentStmt->execute([$id]);
        $currentData = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentData) {
            http_response_code(404);
            echo json_encode(['error' => 'Authority not found.']);
            return;
        }

        // Check if name exists for another authority (only if name is changed)
        if ($name !== $currentData['name']) {
            $checkSql = "SELECT COUNT(*) FROM kdd_authorities WHERE name = ? AND id != ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$name, $id]);

            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'Another authority with this name already exists.']);
                return;
            }
        }

        // Build dynamic update query based on provided fields
        $updateFields = [];
        $updateValues = [];
        $changes = [];
        $originalValues = []; // Store original for log comparison

        // Collect provided fields and check for changes
        if ($name !== $currentData['name']) { $updateFields[] = "name = ?"; $updateValues[] = $name; $changes[] = ['column_name' => 'name', 'old_value' => $currentData['name'], 'new_value' => $name]; }
        if ($display_name !== $currentData['display_name']) { $updateFields[] = "display_name = ?"; $updateValues[] = $display_name; $changes[] = ['column_name' => 'display_name', 'old_value' => $currentData['display_name'], 'new_value' => $display_name]; }
        // Only add description if provided (allow setting to empty string)
         if (array_key_exists('description', $data)) {
             if ($description !== $currentData['description']) { // Compare allows null == null or string == string
                  $updateFields[] = "description = ?"; $updateValues[] = $description; $changes[] = ['column_name' => 'description', 'old_value' => $currentData['description'], 'new_value' => $description];
             }
         }
        // Only add active if explicitly provided in the request body
        if ($activeProvided) {
             $currentActiveBool = (bool)$currentData['active'];
             if ($active !== $currentActiveBool) {
                  $updateFields[] = "active = ?"; $updateValues[] = $active; $changes[] = ['column_name' => 'active', 'old_value' => $currentActiveBool ? '1' : '0', 'new_value' => $active ? '1' : '0'];
             }
        }


        if (empty($updateFields)) {
             // No fields to update
             $pdo->rollBack(); // No changes means no transaction needed
             http_response_code(200);
             echo json_encode(["success" => true, "message" => "No changes detected for authority."]);
             return;
        }

        // Add updated_at and ID to the update query
        $updateFields[] = "updated_at = NOW()"; // No value needed for NOW()
        $updateValues[] = $id; // ID is the last parameter

        $sql = "UPDATE kdd_authorities SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        // Execute update
        $success = $stmt->execute($updateValues);

        if ($success) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                // Log changes (using current user's authority ID for log context)
                global $currentUserAuthorityId;
                logDatabaseChange($currentUserAuthorityId, $pdo, 'UPDATE', 'authority', $id, $userId, $changes);

                $pdo->commit();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Authority updated successfully.'
                ]);
            } else {
                 // Query executed but no rows matched the WHERE clause (should not happen if currentData was found)
                 $pdo->rollBack();
                 http_response_code(500); // This indicates an internal logic issue
                 echo json_encode(['error' => 'Failed to update authority (no rows matched).']);
                 error_log("Error updating authority ID $id: found for reading, but not for updating?");
            }
        } else {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute authority update.']);
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
         if ($e instanceof \PDOException && $e->getCode() === '23000') { // Handle potential unique name constraint
             http_response_code(409); // Conflict
             echo json_encode(['error' => 'Could not update authority: Name might already exist.']);
         } else {
             error_log("DB error in updateAuthority: " . $e->getMessage());
             http_response_code(500);
             echo json_encode(["error" => "Could not update authority: " . $e->getMessage()]);
         }
    }
}

/**
 * Delete an authority (Soft Delete)
 * ID from POST body. User ID for logging.
 * NOTE: Original code did a hard delete, changing to soft delete for safety and consistency if is_deleted exists.
 * Assuming is_deleted column exists in kdd_authorities, if not, revert to hard delete SQL.
 * Check dependency on kdd_users first.
 */
function deleteAuthority(PDO $pdo, int $userId): void {
     // Get data from JSON body
    $data = getJsonRequestData();
    if (!$data) return; // getJsonRequestData handles errors

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT); // Get ID from body

    if ($id === false || $id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid authority ID in request body.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if authority exists and get name for logging
        $checkSql = "SELECT name, display_name FROM kdd_authorities WHERE id = ? AND active = 1"; // Check if active before deleting? Or check if *exists*? Let's just check existence.
         $checkSql = "SELECT name, display_name FROM kdd_authorities WHERE id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id]);
        $authorityData = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$authorityData) {
            http_response_code(404);
            echo json_encode(['error' => 'Authority not found.']);
            return;
        }

        // Check if any users are assigned to this authority
        $userCheckSql = "SELECT COUNT(*) FROM kdd_users WHERE authority_id = ?";
        $userCheckStmt = $pdo->prepare($userCheckSql);
        $userCheckStmt->execute([$id]);

        if ($userCheckStmt->fetchColumn() > 0) {
            $pdo->rollBack(); // Don't proceed if users exist
            http_response_code(409); // Conflict - cannot delete due to dependencies
            echo json_encode(['error' => 'Cannot delete this authority because users are assigned to it.']);
            return;
        }

        // Perform Soft Delete (assuming is_deleted column exists)
        // If no is_deleted column, uncomment the Hard Delete section below and remove this section
         // NOTE: Your previous delete function did a hard delete. Let's stick to that for consistency with the original intent.
         /*
        $sql = "UPDATE kdd_authorities SET active = 0 WHERE id = ? AND active = 1"; // Example soft delete by setting active to false
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id]);
        $rowCount = $stmt->rowCount();
         */

        // --- Hard Delete (Original Logic) ---
        // Delete authority-feature assignments first (CASCADE should handle this if configured, but explicit delete is safer)
        $deleteFeaturesSql = "DELETE FROM kdd_authority_features_rel WHERE authority_id = ?";
        $deleteFeaturesStmt = $pdo->prepare($deleteFeaturesSql);
        $deleteFeaturesStmt->execute([$id]); // Execute even if no relations exist

        // Now delete the authority
        $sql = "DELETE FROM kdd_authorities WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id]);
        $rowCount = $stmt->rowCount();
        // --- End Hard Delete ---


        if ($success && $rowCount > 0) {
            // Log the deletion (using current user's authority ID for log context)
            global $currentUserAuthorityId;
            $changes = [
                ['column_name' => 'name', 'old_value' => $authorityData['name'], 'new_value' => null],
                ['column_name' => 'display_name', 'old_value' => $authorityData['display_name'], 'new_value' => null]
                // Assuming no is_deleted column for hard delete, otherwise log that change
            ];
            logDatabaseChange($currentUserAuthorityId, $pdo, 'DELETE', 'authority', $id, $userId, $changes);

            $pdo->commit();
            http_response_code(200); // OK for successful deletion
            echo json_encode([
                'success' => true,
                'message' => 'Authority deleted successfully.'
            ]);
        } elseif ($success) {
             // RowCount is 0, means it didn't find a row matching the ID (already deleted or wrong ID)
             $pdo->rollBack(); // Nothing was deleted, rollback the feature_rel delete too if it ran
             http_response_code(404); // Not found
             echo json_encode(["error" => "Authority not found or already deleted."]);
        }
        else {
             $pdo->rollBack(); // Rollback feature_rel delete as well
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute authority deletion query.']);
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB error in deleteAuthority (ID: $id): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete authority: " . $e->getMessage()]);
    }
}

/**
 * Update features assigned to an authority
 * Authority ID and features array from POST body. User ID for logging.
 */
function updateAuthorityFeatures(PDO $pdo, int $userId): void {
    // Get data from JSON body
    $data = getJsonRequestData();
    if (!$data) return; // getJsonRequestData handles errors

    $authorityId = filter_var($data['authority_id'] ?? null, FILTER_VALIDATE_INT); // Get Authority ID from body
    $featureIds = $data['features'] ?? []; // Get features array from body

    if ($authorityId === false || $authorityId === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid authority ID in request body.']);
        return;
    }

    if (!is_array($featureIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Features must be provided as an array of IDs in request body.']);
        return;
    }

    // Ensure all provided featureIds are integers
    $featureIds = array_map('intval', $featureIds);
    $featureIds = array_filter($featureIds, function($id) { return $id > 0; }); // Basic validation


    // Debug logging
    error_log("updateAuthorityFeatures: Authority ID = $authorityId, Feature IDs = " . json_encode($featureIds));

    try {
        $pdo->beginTransaction();

        // Check if authority exists
        $checkSql = "SELECT COUNT(*) FROM kdd_authorities WHERE id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$authorityId]);

        if ($checkStmt->fetchColumn() == 0) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Authority not found.']);
            return;
        }

        // Get current features for this authority for logging
        $currentFeaturesSql = "SELECT feature_id FROM kdd_authority_features_rel WHERE authority_id = ?";
        $currentFeaturesStmt = $pdo->prepare($currentFeaturesSql);
        $currentFeaturesStmt->execute([$authorityId]);
        $currentFeatureIds = array_map('intval', $currentFeaturesStmt->fetchAll(PDO::FETCH_COLUMN)); // Ensure current IDs are integers

        error_log("Current feature IDs for logging: " . json_encode($currentFeatureIds));

        // Delete all current features for this authority
        $deleteFeaturesSql = "DELETE FROM kdd_authority_features_rel WHERE authority_id = ?";
        $deleteFeaturesStmt = $pdo->prepare($deleteFeaturesSql);
        $deleteFeaturesStmt->execute([$authorityId]);

        // Insert new features
        if (!empty($featureIds)) {
             // Ensure all provided feature IDs actually exist in kdd_authority_features before inserting
             $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
             $checkFeaturesSql = "SELECT COUNT(*) FROM kdd_authority_features WHERE id IN ($placeholders)";
             $checkFeaturesStmt = $pdo->prepare($checkFeaturesSql);
             $checkFeaturesStmt->execute($featureIds);
             if ($checkFeaturesStmt->fetchColumn() !== count($featureIds)) {
                  $pdo->rollBack();
                  http_response_code(400);
                  echo json_encode(['error' => 'One or more provided feature IDs are invalid.']);
                  return;
             }


            $insertFeaturesSql = "INSERT INTO kdd_authority_features_rel (authority_id, feature_id) VALUES (?, ?)";
            $insertFeaturesStmt = $pdo->prepare($insertFeaturesSql);

            foreach ($featureIds as $featureId) {
                // No need for separate error_log for each insert if stmt->execute returns false (PDOException will catch)
                $insertFeaturesStmt->execute([$authorityId, $featureId]);
            }
        }

        // Log changes (using current user's authority ID for log context)
        global $currentUserAuthorityId;
        $addedFeatures = array_diff($featureIds, $currentFeatureIds);
        $removedFeatures = array_diff($currentFeatureIds, $featureIds);

        $changes = [];
        if (!empty($addedFeatures) || !empty($removedFeatures)) {
            // Log added features
            if (!empty($addedFeatures)) {
                 $changes[] = [
                      'column_name' => 'features_added',
                      'old_value' => null,
                      'new_value' => implode(',', $addedFeatures)
                 ];
            }
             // Log removed features
             if (!empty($removedFeatures)) {
                 $changes[] = [
                      'column_name' => 'features_removed',
                      'old_value' => implode(',', $removedFeatures),
                      'new_value' => null
                 ];
             }
             // Optional: Log combined state change instead
             /*
             $changes[] = [
                 'column_name' => 'features',
                 'old_value' => implode(',', $currentFeatureIds),
                 'new_value' => implode(',', $featureIds)
             ];
             */

            // Log the change against the authority entity that had its features updated
            logDatabaseChange($currentUserAuthorityId, $pdo, 'UPDATE', 'authority_features_rel', $authorityId, $userId, $changes);
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Authority features updated successfully.',
            'added' => count($addedFeatures),
            'removed' => count($removedFeatures)
        ]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB error in updateAuthorityFeatures: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update authority features: " . $e->getMessage()]);
    } catch (\Exception $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         error_log("Error in updateAuthorityFeatures: " . $e->getMessage());
         http_response_code(500);
         echo json_encode(["error" => "Could not update authority features: " . $e->getMessage()]);
    }
}



// --- Helper (Get Entry By ID - Uncomment if needed for logging old values) ---
/*
function getEntryById(PDO $pdo, int $id, string $tableName): ?array {
    $sql = "SELECT * FROM `$tableName` WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result : null;
}
*/

?>