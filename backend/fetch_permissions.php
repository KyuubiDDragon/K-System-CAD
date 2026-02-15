<?php
/**
 * Permission Fetcher for Backend
 * 
 * This file fetches user permissions from the database when they're needed for
 * backend authentication checks, since permissions are no longer stored in the JWT.
 */

/**
 * Fetch permissions for a user from the database
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $authorityId Authority ID
 * @return array Array containing 'permissions' and 'roles'
 */
function fetchUserPermissions(PDO $pdo, int $userId, int $authorityId): array {
    try {
        // Get roles and permissions for the user
        // NEW SCHEMA: Uses module, sub_module, action instead of 'site'
        $sqlRolesPerms = "SELECT DISTINCT r.name as role_name, p.name as permission_name
                          FROM kdd_user_roles ur
                          JOIN kdd_roles r ON r.authority_id = ur.authority_id AND ur.role_id = r.id
                          JOIN kdd_role_permissions rp ON rp.authority_id = ur.authority_id AND r.id = rp.role_id
                          JOIN kdd_permissions p ON rp.permission_id = p.id
                          WHERE ur.user_id = ? AND ur.authority_id = ?";
        $stmtRolesPerms = $pdo->prepare($sqlRolesPerms);
        $stmtRolesPerms->execute([$userId, $authorityId]);
        $results = $stmtRolesPerms->fetchAll();

        $roles = [];
        $permissions = [];

        // Collect all permissions and roles
        foreach ($results as $row) {
            $roles[$row['role_name']] = $row['role_name']; // Make roles unique
            $permissions[] = $row['permission_name'];
        }

        return [
            'roles' => array_values($roles),
            'permissions' => array_unique($permissions)
        ];
    } catch (PDOException $e) {
        error_log("Database error in fetchUserPermissions: " . $e->getMessage());
        return [
            'roles' => [],
            'permissions' => []
        ];
    }
}

/**
 * Adds permissions to a decoded JWT object
 * 
 * @param object $decoded_jwt The JWT object to add permissions to
 * @param PDO $pdo Database connection
 * @return object The updated JWT object
 */
function addPermissionsToJwt(object $decoded_jwt, PDO $pdo): object {
    if (!isset($decoded_jwt->userId) || !isset($decoded_jwt->authority_id)) {
        error_log("Cannot add permissions to JWT: missing userId or authority_id");
        $decoded_jwt->permissions = [];
        $decoded_jwt->roles = [];
        return $decoded_jwt;
    }

    $userId = $decoded_jwt->userId;
    $authorityId = $decoded_jwt->authority_id;

    $userPermData = fetchUserPermissions($pdo, $userId, $authorityId);
    
    // Add the permissions and roles to the JWT object
    $decoded_jwt->permissions = $userPermData['permissions'];
    $decoded_jwt->roles = $userPermData['roles'];
    
    return $decoded_jwt;
}
?> 