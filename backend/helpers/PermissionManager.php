<?php
/**
 * PermissionManager - Modern Permission System with Bitmask Support
 *
 * This class provides a structured, efficient way to handle permissions
 * using module-based organization and bitmask operations.
 *
 * @author Claude Code
 * @date 2025-10-31
 */

declare(strict_types=1);

class PermissionManager
{
    // Bitmask constants for actions
    public const ACTION_READ   = 0b00001;  // 1
    public const ACTION_WRITE  = 0b00010;  // 2
    public const ACTION_DELETE = 0b00100;  // 4
    public const ACTION_ADMIN  = 0b01000;  // 8
    public const ACTION_VIEW   = 0b10000;  // 16
    public const ACTION_CREATE = 0b100000; // 32

    // Action name to bitmask mapping (public for helper functions)
    public const ACTION_MAP = [
        'read'   => self::ACTION_READ,
        'write'  => self::ACTION_WRITE,
        'delete' => self::ACTION_DELETE,
        'admin'  => self::ACTION_ADMIN,
        'view'   => self::ACTION_VIEW,
        'create' => self::ACTION_CREATE,
    ];

    private PDO $pdo;
    private int $authorityId;
    private array $cachedPermissions = [];

    public function __construct(PDO $pdo, int $authorityId)
    {
        $this->pdo = $pdo;
        $this->authorityId = $authorityId;
    }

    /**
     * Load all permissions for a user and convert to bitmask format
     *
     * @param int $userId
     * @return array Format: ['module' => bitmask, 'module.submodule' => bitmask]
     */
    public function loadUserPermissions(int $userId): array
    {
        // Use the same JOIN structure as login/index.php which works correctly
        $sql = "SELECT DISTINCT p.module, p.sub_module, p.action, p.bitmask_value, p.name
                FROM kdd_user_roles ur
                JOIN kdd_roles r ON r.authority_id = ur.authority_id AND ur.role_id = r.id
                JOIN kdd_role_permissions rp ON rp.authority_id = ur.authority_id AND r.id = rp.role_id
                JOIN kdd_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ?
                  AND ur.authority_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $this->authorityId]);

        $permissions = [];
        $legacyPermissions = []; // For backwards compatibility

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Build module key
            $moduleKey = $row['module'];
            if (!empty($row['sub_module'])) {
                $moduleKey .= '.' . $row['sub_module'];
            }

            // Initialize bitmask for this module if not exists
            if (!isset($permissions[$moduleKey])) {
                $permissions[$moduleKey] = 0;
            }

            // Add bitmask value (if available)
            if ($row['bitmask_value'] !== null) {
                $permissions[$moduleKey] |= (int)$row['bitmask_value'];
            }

            // Store legacy permission names for backwards compatibility
            $legacyPermissions[] = $row['name'];
        }

        // Cache both formats
        $this->cachedPermissions = [
            'bitmask' => $permissions,
            'legacy' => $legacyPermissions
        ];

        return $permissions;
    }

    /**
     * Check if user has ALL_PERMISSIONS (super admin)
     * Users with 'system' module permission have access to everything
     *
     * @param int $userId
     * @return bool
     */
    public function hasAllPermissions(int $userId): bool
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        // Check if user has 'system' module (ALL_PERMISSIONS in new format)
        return isset($this->cachedPermissions['bitmask']['system']);
    }

    /**
     * Check if user has a specific permission using module.action format
     *
     * @param string $module Module name (e.g., 'blackboard' or 'blackboard.area')
     * @param string $action Action name (read, write, delete, admin, view)
     * @param int $userId
     * @return bool
     */
    public function hasPermission(string $module, string $action, int $userId): bool
    {
        // Check for ALL_PERMISSIONS first (super admin)
        if ($this->hasAllPermissions($userId)) {
            return true;
        }

        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        $bitmask = $this->cachedPermissions['bitmask'][$module] ?? 0;
        $actionBit = self::ACTION_MAP[$action] ?? 0;

        return ($bitmask & $actionBit) === $actionBit;
    }

    /**
     * Check if user has legacy permission name (backwards compatibility)
     *
     * @param string $permissionName Legacy permission name (e.g., 'READ_BLACKBOARD_AREA')
     * @param int $userId
     * @return bool
     */
    public function hasLegacyPermission(string $permissionName, int $userId): bool
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        return in_array($permissionName, $this->cachedPermissions['legacy'] ?? [], true);
    }

    /**
     * Check if user has ANY of the specified actions on a module
     *
     * @param string $module
     * @param array $actions Array of action names
     * @param int $userId
     * @return bool
     */
    public function hasAnyPermission(string $module, array $actions, int $userId): bool
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        $bitmask = $this->cachedPermissions['bitmask'][$module] ?? 0;

        foreach ($actions as $action) {
            $actionBit = self::ACTION_MAP[$action] ?? 0;
            if (($bitmask & $actionBit) === $actionBit) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ALL of the specified actions on a module
     *
     * @param string $module
     * @param array $actions Array of action names
     * @param int $userId
     * @return bool
     */
    public function hasAllModulePermissions(string $module, array $actions, int $userId): bool
    {
        // Check for ALL_PERMISSIONS first (super admin)
        if ($this->hasAllPermissions($userId)) {
            return true;
        }

        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        $bitmask = $this->cachedPermissions['bitmask'][$module] ?? 0;

        $requiredBitmask = 0;
        foreach ($actions as $action) {
            $requiredBitmask |= self::ACTION_MAP[$action] ?? 0;
        }

        return ($bitmask & $requiredBitmask) === $requiredBitmask;
    }

    /**
     * Get all modules the user has any permission on
     *
     * @param int $userId
     * @return array
     */
    public function getUserModules(int $userId): array
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        return array_keys($this->cachedPermissions['bitmask'] ?? []);
    }

    /**
     * Get all actions user has on a specific module
     *
     * @param string $module
     * @param int $userId
     * @return array Array of action names
     */
    public function getModuleActions(string $module, int $userId): array
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        $bitmask = $this->cachedPermissions['bitmask'][$module] ?? 0;
        $actions = [];

        foreach (self::ACTION_MAP as $action => $bit) {
            if (($bitmask & $bit) === $bit) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Get user permissions as bitmask object (for JWT token)
     *
     * @param int $userId
     * @return array Format: ['employee' => 7, 'company' => 3, 'blackboard.area' => 1]
     */
    public function getUserPermissionsBitmask(int $userId): array
    {
        // Load permissions if not cached
        if (empty($this->cachedPermissions)) {
            $this->loadUserPermissions($userId);
        }

        return $this->cachedPermissions['bitmask'] ?? [];
    }

    /**
     * Convert bitmask object to legacy string array (for backwards compatibility)
     *
     * @param array $bitmaskObject ['employee' => 7, 'company' => 3, ...]
     * @return array ['READ_EMPLOYEE', 'WRITE_EMPLOYEE', 'DELETE_EMPLOYEE', 'READ_COMPANY', ...]
     */
    public static function convertBitmaskToLegacyArray(array $bitmaskObject): array
    {
        $legacyPermissions = [];

        // Check for ALL_PERMISSIONS (system module)
        if (isset($bitmaskObject['system'])) {
            $legacyPermissions[] = 'ALL_PERMISSIONS';
        }

        foreach ($bitmaskObject as $module => $bitmask) {
            // Convert module.submodule to MODULE_SUBMODULE
            $moduleUpper = strtoupper(str_replace('.', '_', $module));

            // Check each action bit
            if ($bitmask & self::ACTION_VIEW) {
                $legacyPermissions[] = "VIEW_{$moduleUpper}";
            }
            if ($bitmask & self::ACTION_READ) {
                $legacyPermissions[] = "READ_{$moduleUpper}";
            }
            if ($bitmask & self::ACTION_WRITE) {
                $legacyPermissions[] = "WRITE_{$moduleUpper}";
            }
            if ($bitmask & self::ACTION_DELETE) {
                $legacyPermissions[] = "DELETE_{$moduleUpper}";
            }
            if ($bitmask & self::ACTION_ADMIN) {
                $legacyPermissions[] = "ADMIN_{$moduleUpper}";
            }
            if ($bitmask & self::ACTION_CREATE) {
                $legacyPermissions[] = "CREATE_{$moduleUpper}";
            }
        }

        return $legacyPermissions;
    }

    /**
     * Convert legacy permission name to module.action format
     *
     * @param string $legacyName Legacy permission name (e.g., 'READ_BLACKBOARD_AREA')
     * @return array|null ['module' => 'blackboard.area', 'action' => 'read'] or null if not found
     */
    public static function parseLegacyPermission(string $legacyName): ?array
    {
        // Query database to get the structured format
        global $pdo; // Or inject PDO

        $stmt = $pdo->prepare("
            SELECT module, sub_module, action
            FROM kdd_permissions
            WHERE name = :name
            LIMIT 1
        ");
        $stmt->execute(['name' => $legacyName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $module = $row['module'];
        if (!empty($row['sub_module'])) {
            $module .= '.' . $row['sub_module'];
        }

        return [
            'module' => $module,
            'action' => $row['action']
        ];
    }

    /**
     * Clear cached permissions (useful after role changes)
     */
    public function clearCache(): void
    {
        $this->cachedPermissions = [];
    }

    /**
     * Export permissions in JSON format for frontend
     *
     * @param int $userId
     * @return string JSON string
     */
    public function exportToJson(int $userId): string
    {
        $permissions = $this->loadUserPermissions($userId);

        return json_encode([
            'modules' => $permissions,
            'legacy' => $this->cachedPermissions['legacy'] ?? [],
            'authority_id' => $this->authorityId,
            'user_id' => $userId,
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
    }
}

// ============================================================================
// Usage Examples
// ============================================================================

/*

// Example 1: Initialize Permission Manager
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/PermissionManager.php';

$userId = 42;
$authorityId = 1;
$pm = new PermissionManager($pdo, $authorityId);

// Example 2: Check modern permission format
if ($pm->hasPermission('blackboard.area', 'read', $userId)) {
    echo "User can read blackboard areas\n";
}

// Example 3: Check legacy permission (backwards compatible)
if ($pm->hasLegacyPermission('READ_BLACKBOARD_AREA', $userId)) {
    echo "User has legacy READ_BLACKBOARD_AREA permission\n";
}

// Example 4: Check if user has ANY write permission (write OR admin)
if ($pm->hasAnyPermission('blackboard.area', ['write', 'admin'], $userId)) {
    echo "User can modify blackboard areas\n";
}

// Example 5: Check if user has ALL permissions (read AND write AND delete)
if ($pm->hasAllPermissions('report', ['read', 'write', 'delete'], $userId)) {
    echo "User has full access to reports\n";
}

// Example 6: Get all modules user has access to
$modules = $pm->getUserModules($userId);
echo "User has access to: " . implode(', ', $modules) . "\n";

// Example 7: Get all actions for a specific module
$actions = $pm->getModuleActions('document.training', $userId);
echo "User can perform: " . implode(', ', $actions) . " on training documents\n";

// Example 8: Convert legacy permission to modern format
$parsed = PermissionManager::parseLegacyPermission('WRITE_BLACKBOARD_AREA');
if ($parsed) {
    echo "Module: {$parsed['module']}, Action: {$parsed['action']}\n";
}

// Example 9: Export permissions for frontend
$json = $pm->exportToJson($userId);
file_put_contents('/tmp/user_permissions.json', $json);

// Example 10: Clear cache after role changes
// (when user roles are modified)
$pm->clearCache();
$pm->loadUserPermissions($userId); // Reload fresh permissions

*/
