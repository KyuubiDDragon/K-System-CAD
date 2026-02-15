import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

/**
 * Composable for checking module-based permissions with bitmask support
 *
 * Uses bitmask format from JWT: {"employee": 7, "calendar": 23}
 *
 * @example
 * const { hasModulePermission, hasAnyModulePermission } = useModulePermission()
 * const canRead = hasModulePermission('employee', 'read')
 * const canWrite = hasModulePermission('company.type', 'write')
 */
export function useModulePermission() {
  const authStore = useAuthStore();

  // Bitmask constants (must match backend PermissionManager.php)
  const ACTION_READ = 0b00001;   // 1
  const ACTION_WRITE = 0b00010;  // 2
  const ACTION_DELETE = 0b00100; // 4
  const ACTION_ADMIN = 0b01000;  // 8
  const ACTION_VIEW = 0b10000;   // 16
  const ACTION_CREATE = 0b100000; // 32

  const ACTION_MAP: Record<string, number> = {
    'read': ACTION_READ,
    'write': ACTION_WRITE,
    'delete': ACTION_DELETE,
    'admin': ACTION_ADMIN,
    'view': ACTION_VIEW,
    'create': ACTION_CREATE,
  };

  /**
   * Check if user has ALL_PERMISSIONS (super admin)
   * Users with 'system' module permission have access to everything
   */
  const hasAllPermissions = computed(() => {
    const permissions = authStore.user?.permissions;

    if (!permissions || typeof permissions !== 'object' || Array.isArray(permissions)) {
      return false;
    }

    // Check if user has 'system' module (ALL_PERMISSIONS in new format)
    return 'system' in (permissions as Record<string, number>);
  });

  /**
   * Check if user has a specific module permission
   *
   * @param module Module name (e.g., 'employee', 'company.type', 'blackboard.area')
   * @param action Action name ('read', 'write', 'delete', 'admin', 'view', 'create')
   * @returns boolean indicating if user has permission
   */
  const hasModulePermission = (module: string, action: string): boolean => {
    // Check for ALL_PERMISSIONS first (super admin)
    if (hasAllPermissions.value) {
      return true;
    }

    const permissions = authStore.user?.permissions;

    if (!permissions || typeof permissions !== 'object' || Array.isArray(permissions)) {
      return false;
    }

    const bitmask = (permissions as Record<string, number>)[module] ?? 0;
    const actionBit = ACTION_MAP[action] ?? 0;

    if (actionBit === 0) {
      return false;
    }

    return (bitmask & actionBit) === actionBit;
  };

  /**
   * Check if user has permission with implied hierarchies
   * WRITE implies READ
   * DELETE implies WRITE and READ
   *
   * @param module Module name
   * @param action Action name
   * @returns boolean indicating if user has permission or higher
   */
  const hasModulePermissionOrHigher = (module: string, action: string): boolean => {
    // Check exact permission
    if (hasModulePermission(module, action)) {
      return true;
    }

    // Check permission hierarchies
    if (action === 'read') {
      // WRITE implies READ
      if (hasModulePermission(module, 'write')) {
        return true;
      }
      // DELETE implies READ
      if (hasModulePermission(module, 'delete')) {
        return true;
      }
    } else if (action === 'write') {
      // DELETE implies WRITE
      if (hasModulePermission(module, 'delete')) {
        return true;
      }
    }

    return false;
  };

  /**
   * Check if user has ANY of the specified permissions for a module
   *
   * @param module Module name
   * @param actions Array of action names
   * @returns boolean indicating if user has any of the permissions
   */
  const hasAnyModulePermission = (module: string, actions: string[]): boolean => {
    for (const action of actions) {
      if (hasModulePermission(module, action)) {
        return true;
      }
    }

    return false;
  };

  /**
   * Check if user has ALL of the specified permissions for a module
   *
   * @param module Module name
   * @param actions Array of action names
   * @returns boolean indicating if user has all of the permissions
   */
  const hasAllModulePermissions = (module: string, actions: string[]): boolean => {
    for (const action of actions) {
      if (!hasModulePermission(module, action)) {
        return false;
      }
    }

    return true;
  };

  /**
   * Get the bitmask value for a module (useful for debugging)
   *
   * @param module Module name
   * @returns number (bitmask value)
   */
  const getModuleBitmask = (module: string): number => {
    const permissions = authStore.user?.permissions;

    if (!permissions || typeof permissions !== 'object' || Array.isArray(permissions)) {
      return 0;
    }

    return (permissions as Record<string, number>)[module] ?? 0;
  };

  /**
   * Get human-readable permissions for a module (useful for debugging)
   *
   * @param module Module name
   * @returns Array of action names the user has for this module
   */
  const getModuleActions = (module: string): string[] => {
    const actions: string[] = [];

    for (const [actionName, actionBit] of Object.entries(ACTION_MAP)) {
      if (hasModulePermission(module, actionName)) {
        actions.push(actionName);
      }
    }

    return actions;
  };

  return {
    hasAllPermissions, // Check if user has ALL_PERMISSIONS (super admin)
    hasModulePermission,
    hasModulePermissionOrHigher,
    hasAnyModulePermission,
    hasAllModulePermissions,
    getModuleBitmask,
    getModuleActions,
  };
}
