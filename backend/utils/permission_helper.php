<?php
/**
 * Permission Helper Utility Functions
 * Provides standardized functions for safely checking permissions
 */

/**
 * Ensures that a permissions variable is an array for safe checking
 * Handles the case where permissions come from JWT as stdClass
 * 
 * @param mixed $permissions The permissions to normalize
 * @return array Normalized permissions array
 */
function normalizePermissions($permissions): array {
    // Handle object case (stdClass)
    if (is_object($permissions)) {
        error_log("Warning: permissions was an object, converted to array");
        return (array)$permissions;
    }
    
    // Handle array case
    if (is_array($permissions)) {
        return $permissions;
    }
    
    // Handle other cases (null, string, etc)
    error_log("Warning: permissions was not an array or object, set to empty array");
    return [];
}

/**
 * Safely checks if a user has a specific permission
 * Supports both legacy string format and new bitmask format
 *
 * @param mixed $userPermissions The user's permissions (bitmask object or legacy array)
 * @param string $requiredPermission Legacy permission string (e.g., 'READ_EMPLOYEE', 'ADMIN_WRITE_USERS')
 * @return bool True if user has the permission
 */
function hasPermission($userPermissions, string $requiredPermission): bool {
    // Check for ALL_PERMISSIONS first (super admin has everything)
    if (hasAllPermissions($userPermissions)) {
        return true;
    }

    // Handle bitmask format (new system)
    if (is_object($userPermissions) || (is_array($userPermissions) && !empty($userPermissions))) {
        $firstValue = is_array($userPermissions) ? reset($userPermissions) : null;

        // Check if this is bitmask format (has numeric values)
        if (is_object($userPermissions) || (is_int($firstValue) && $firstValue !== false)) {
            // Convert legacy string to module.action format
            $parsed = parseLegacyPermissionString($requiredPermission);
            if ($parsed) {
                return hasModulePermission($userPermissions, $parsed['module'], $parsed['action']);
            }
            // If can't parse, permission doesn't exist in new system
            return false;
        }
    }

    // Fallback: Legacy array format (backwards compatibility)
    if (is_array($userPermissions)) {
        return in_array($requiredPermission, $userPermissions);
    }

    return false;
}

/**
 * Parse legacy permission string to module.action format
 * Looks up the actual module name in database to handle complex mappings
 *
 * Examples from DB:
 *   'READ_EMPLOYEE' -> module='employee', action='read'
 *   'READ_VEHICLE' -> module='dispatch.vehicle', action='read'
 *   'ADMIN_READ_USERS' -> module='admin.users', action='read'
 *
 * @param string $legacyPermission Legacy permission string
 * @return array|null ['module' => string, 'action' => string] or null if can't parse
 */
function parseLegacyPermissionString(string $legacyPermission): ?array {
    // Cache to avoid repeated DB lookups
    static $cache = [];

    if (isset($cache[$legacyPermission])) {
        return $cache[$legacyPermission];
    }

    // Lookup in database for exact match
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT module, sub_module, action
                FROM kdd_permissions
                WHERE name = ?
                LIMIT 1
            ");
            $stmt->execute([$legacyPermission]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $moduleKey = $row['module'];
                if (!empty($row['sub_module'])) {
                    $moduleKey .= '.' . $row['sub_module'];
                }

                $result = [
                    'module' => $moduleKey,
                    'action' => $row['action']
                ];

                $cache[$legacyPermission] = $result;
                return $result;
            }
        } catch (PDOException $e) {
            // DB lookup failed, fall back to parsing
            error_log("[PERMISSION] DB lookup failed for $legacyPermission: " . $e->getMessage());
        }
    }

    // Fallback: Try to parse (won't work for complex mappings like dispatch.vehicle)
    if (preg_match('/^ADMIN_(READ|WRITE|DELETE|VIEW|CREATE)_(.+)$/', $legacyPermission, $matches)) {
        $action = strtolower($matches[1]);
        $subModulePart = strtolower($matches[2]);
        $result = ['module' => 'admin.' . $subModulePart, 'action' => $action];
        $cache[$legacyPermission] = $result;
        return $result;
    }

    if (preg_match('/^(READ|WRITE|DELETE|ADMIN|VIEW|CREATE)_(.+)$/', $legacyPermission, $matches)) {
        $action = strtolower($matches[1]);
        $module = strtolower(str_replace('_', '.', $matches[2]));
        $result = ['module' => $module, 'action' => $action];
        $cache[$legacyPermission] = $result;
        return $result;
    }

    error_log("[PERMISSION] WARNING: Could not parse legacy permission string: $legacyPermission");
    $cache[$legacyPermission] = null;
    return null;
}

/**
 * Checks if a user has ALL_PERMISSIONS (super admin)
 * Supports both new bitmask format and legacy string array format
 *
 * NEW FORMAT: Checks for 'system' module in bitmask object
 * LEGACY FORMAT: Checks for 'ALL_PERMISSIONS' in string array
 *
 * @param mixed $userPermissions The user's permissions (bitmask object or legacy array)
 * @return bool True if user has ALL_PERMISSIONS
 */
function hasAllPermissions($userPermissions): bool {
    // Handle null/undefined
    if ($userPermissions === null || $userPermissions === false) {
        return false;
    }

    // Handle object case (stdClass from JWT)
    if (is_object($userPermissions)) {
        // Check for 'system' property in bitmask object
        return property_exists($userPermissions, 'system');
    }

    // Handle array cases
    if (is_array($userPermissions)) {
        if (empty($userPermissions)) {
            return false;
        }

        // Check if this is a bitmask object (associative array with numeric values)
        // Don't use reset() as it modifies the array pointer
        $values = array_values($userPermissions);
        if (isset($values[0]) && is_int($values[0])) {
            // NEW FORMAT: Bitmask object - check for 'system' module
            return isset($userPermissions['system']);
        }

        // LEGACY FORMAT: String array - check for 'ALL_PERMISSIONS' string
        return in_array('ALL_PERMISSIONS', $userPermissions);
    }

    return false;
}

/**
 * Checks if a user has any of the given permissions
 *
 * @param mixed $userPermissions The user's permissions (array or object)
 * @param array $requiredPermissions Array of permissions to check
 * @return bool True if user has any of the permissions
 */
function hasAnyPermission($userPermissions, array $requiredPermissions): bool {
    $permissions = normalizePermissions($userPermissions);
    foreach ($requiredPermissions as $permission) {
        if (in_array($permission, $permissions)) {
            return true;
        }
    }
    return false;
}

/**
 * Gets the area-based permissions for a user on a specific blackboard area (multi-role support)
 * Similar to getUserAreaPermissions() for documents
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @param int $areaId Blackboard area ID
 * @param int $userId User ID (will aggregate permissions across all user's roles)
 * @return array Array with can_read, can_write, can_delete booleans
 */
function getUserBlackboardAreaPermissions(
    PDO $pdo,
    int $authorityId,
    int $areaId,
    int $userId
): array {
    try {
        // Get all role IDs for this user in this authority
        $stmtRoles = $pdo->prepare("
            SELECT role_id FROM kdd_user_roles
            WHERE user_id = ? AND authority_id = ?
        ");
        $stmtRoles->execute([$userId, $authorityId]);
        $userRoles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userRoles)) {
            return ['can_read' => false, 'can_write' => false, 'can_delete' => false];
        }

        // Aggregate permissions across ALL roles using MAX()
        // If ANY role grants permission, user has that permission
        $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(MAX(can_read), 0) as can_read,
                COALESCE(MAX(can_write), 0) as can_write,
                COALESCE(MAX(can_delete), 0) as can_delete
            FROM kdd_blackboard_area_permissions
            WHERE authority_id = ? AND area_id = ? AND role_id IN ($placeholders)
        ");
        $stmt->execute([$authorityId, $areaId, ...$userRoles]);
        $perms = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'can_read' => (bool)($perms['can_read'] ?? false),
            'can_write' => (bool)($perms['can_write'] ?? false),
            'can_delete' => (bool)($perms['can_delete'] ?? false),
        ];
    } catch (\PDOException $e) {
        error_log("Error fetching blackboard area permissions: " . $e->getMessage());
        return ['can_read' => false, 'can_write' => false, 'can_delete' => false];
    }
}

/**
 * Checks if a user has access to a specific blackboard area (multi-role support)
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @param int $areaId Blackboard area ID
 * @param int $userId User ID (will check permissions across all user's roles)
 * @param string $accessType Type of access: 'read', 'write', or 'delete'
 * @return bool True if user has the requested access
 */
function hasBlackboardAreaAccess(
    PDO $pdo,
    int $authorityId,
    int $areaId,
    int $userId,
    string $accessType = 'read'
): bool {
    $perms = getUserBlackboardAreaPermissions($pdo, $authorityId, $areaId, $userId);

    switch ($accessType) {
        case 'read':
            return $perms['can_read'];
        case 'write':
            return $perms['can_write'];
        case 'delete':
            return $perms['can_delete'];
        default:
            return false;
    }
}

/**
 * Konvertiert Bitmask-Object zu Legacy-String-Array (Wrapper)
 *
 * @param array $bitmaskObject ['employee' => 7, 'company' => 3, ...]
 * @return array ['READ_EMPLOYEE', 'WRITE_EMPLOYEE', ...]
 */
function convertBitmaskToLegacyArray(array $bitmaskObject): array {
    require_once __DIR__ . '/../helpers/PermissionManager.php';
    return PermissionManager::convertBitmaskToLegacyArray($bitmaskObject);
}

/**
 * Prüft Permission mit Bitmask-Support (modern)
 * Supports ALL_PERMISSIONS check and hierarchical module matching
 *
 * Hierarchical matching:
 * - If checking 'dispatch' and user has 'dispatch.vehicle', it matches!
 * - If checking 'dispatch.vehicle' and user has 'dispatch', it matches!
 *
 * @param mixed $userPermissions String-Array ODER Bitmask-Object
 * @param string $module z.B. 'employee', 'company', 'dispatch', 'blackboard.area'
 * @param string $action z.B. 'read', 'write', 'delete', 'admin'
 * @return bool
 */
function hasModulePermission($userPermissions, string $module, string $action): bool {
    require_once __DIR__ . '/../helpers/PermissionManager.php';

    // Check for ALL_PERMISSIONS first (super admin has access to everything)
    if (hasAllPermissions($userPermissions)) {
        return true;
    }

    // Convert stdClass to array (JWT decode returns stdClass)
    if (is_object($userPermissions)) {
        $userPermissions = (array)$userPermissions;
    }

    // Fall 1: Bitmask-Object (NEU - modern)
    if (is_array($userPermissions) && !empty($userPermissions)) {
        // Check if this is a bitmask object (has numeric values)
        $firstValue = reset($userPermissions);
        if (is_int($firstValue)) {
            // This is a bitmask object
            $actionBit = PermissionManager::ACTION_MAP[$action] ?? 0;

            // Direct match: exact module name
            if (isset($userPermissions[$module])) {
                $bitmask = $userPermissions[$module];
                if (($bitmask & $actionBit) === $actionBit) {
                    return true;
                }
            }

            // Hierarchical match: check if user has any sub-modules
            // Example: checking 'dispatch', user has 'dispatch.vehicle' or 'dispatch.crew'
            foreach ($userPermissions as $userModule => $bitmask) {
                // Check if user's module starts with requested module
                if (strpos($userModule, $module . '.') === 0) {
                    if (($bitmask & $actionBit) === $actionBit) {
                        return true;
                    }
                }

                // Check if requested module is more specific than user's module
                // Example: checking 'dispatch.vehicle', user has 'dispatch'
                if (strpos($module, $userModule . '.') === 0) {
                    if (($bitmask & $actionBit) === $actionBit) {
                        return true;
                    }
                }
            }

            return false;
        }
    }

    // Fall 2: Legacy String-Array (Backwards Compatibility)
    if (is_array($userPermissions)) {
        $permissionName = strtoupper($action) . '_' . strtoupper(str_replace('.', '_', $module));
        return in_array($permissionName, $userPermissions);
    }

    return false;
}