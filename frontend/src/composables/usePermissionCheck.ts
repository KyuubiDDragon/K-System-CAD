import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

/**
 * @deprecated This composable is deprecated as of Phase 3 migration.
 * Please use `useModulePermission` instead for new code.
 *
 * This composable uses legacy string-based permissions (e.g., 'READ_EMPLOYEE').
 * The new system uses module-based permissions with bitmask support.
 *
 * Migration: Replace with `useModulePermission` from '@/composables/useModulePermission'
 * - hasPermission('READ_EMPLOYEE') → hasModulePermission('employee', 'read')
 * - hasPermission('WRITE_COMPANY') → hasModulePermission('company', 'write')
 *
 * This composable is kept for backwards compatibility during gradual migration.
 *
 * @see useModulePermission for the new permission system
 */
export function usePermissionCheck() {
  const authStore = useAuthStore();

  // --- PERFORMANCE IMPROVEMENT: Convert permission Array to Set for O(1) lookups ---
  // This computed property creates a Set from the user's permissions array.
  // It automatically re-runs whenever authStore.user?.permissions changes.
  const permissionSet = computed<Set<string>>(() => {
    const permissions = authStore.user?.permissions;
    console.log('permissions', permissions);
    // Ensure it's an array before creating the Set.
    // If permissions is null, undefined, or not an array, return an empty Set.
    if (!permissions || !Array.isArray(permissions)) {
      console.warn('PermissionCheck: user.permissions is not a valid array, creating empty Set.', permissions);
      return new Set(); // Return empty Set if no permissions or invalid format
    }
    // console.log(`PermissionCheck: Creating permission Set with ${permissions.length} permissions.`);
    return new Set(permissions); // Convert the array to a Set - this is done only when the source array changes.
  });
  // --- END PERFORMANCE IMPROVEMENT ---


  /**
   * Checks if the user has a specific permission using an efficient lookup (Set.has()).
   * This function is O(1) on average, regardless of the number of permissions the user has.
   * @param permission The permission string to check (e.g., 'READ_USERS')
   * @returns boolean indicating if user has permission
   */
  const hasPermission = (permission?: string): boolean => {
    // Handle invalid input gracefully.
    if (!permission || typeof permission !== 'string') {
       // console.warn('PermissionCheck: hasPermission called with undefined or invalid permission:', permission); // Reduce log noise if this is intentional
       return false; // Cannot have an undefined or invalid permission.
    }

    // The permissionSet computed property handles the case where authStore.user?.permissions is null/empty/not array.
    // We don't need to check authStore.isLoggedIn here; if not logged in, permissions will be null/empty,
    // and permissionSet will be empty, so has() will correctly return false.


    // --- Efficient Lookup using Set.has() ---
    // 1. Check for the 'ALL_PERMISSIONS' special permission first (very fast Set lookup).
    if (permissionSet.value.has('ALL_PERMISSIONS')) {
        // console.log(`PermissionCheck: hasPermission(${permission}): ALL_PERMISSIONS grants access.`);
        return true; // User is a super-admin, bypass all other checks.
    }

    // 2. Check for the specific permission directly using Set.has() (O(1) on average).
    if (permissionSet.value.has(permission)) {
        // console.log(`PermissionCheck: hasPermission(${permission}): Specific permission found.`);
        return true; // User has the exact permission.
    }

    // 3. Check for WRITE permission if the requested permission is a READ_ permission (using Set.has()).
    //    Example: If checking 'READ_USERS' and user has 'WRITE_USERS', grant access.
    if (permission.startsWith('READ_')) {
        const writePermission = permission.replace('READ_', 'WRITE_');
        if (permissionSet.value.has(writePermission)) {
             // console.log(`PermissionCheck: hasPermission(${permission}): WRITE permission "${writePermission}" grants READ access.`);
             return true; // User has the corresponding WRITE permission.
        }
    }
    // --- End Efficient Lookup ---

    // If none of the above conditions are met, the user does not have the permission.
    return false;
  };


  /**
   * Checks if the user has *any* of the specified permissions using efficient lookups.
   * This function iterates the *input list* of permissions (which should be small per check),
   * but each individual permission check within it is fast (O(1)).
   * @param permissions Array of permission strings to check (e.g., ['READ_USERS', 'READ_ROLES'])
   * @returns boolean True if the user has any of the permissions or 'ALL_PERMISSIONS'. Returns false for empty/invalid input.
   */
  const hasAnyPermission = (permissions: string[] | undefined): boolean => {
    // Handle undefined/invalid input or empty required permissions array.
    if (!permissions || !Array.isArray(permissions) || permissions.length === 0) {
      // console.warn('PermissionCheck: Invalid or empty permissions array in hasAnyPermission:', permissions); // Reduce log noise
      return false; // Cannot have any permission from an empty or invalid list.
    }

    // Check for 'ALL_PERMISSIONS' first (efficient Set lookup).
     if (permissionSet.value.has('ALL_PERMISSIONS')) {
         // console.log(`PermissionCheck: hasAnyPermission([${permissions.join(', ')}]): ALL_PERMISSIONS grants access.`);
         return true; // User is a super-admin.
     }

    // Iterate through the *input list* of permissions (this list is usually small, defining required permissions for a feature).
    // For each required permission, perform an efficient check using the permissionSet.
    for (const requiredPerm of permissions) {
      if (typeof requiredPerm !== 'string') {
           console.warn('PermissionCheck: hasAnyPermission received non-string permission in array:', requiredPerm);
           continue; // Skip invalid entries in the input array.
      }

      // Perform the same checks as in hasPermission for this specific required permission.
      if (permissionSet.value.has(requiredPerm)) {
           // console.log(`PermissionCheck: hasAnyPermission([${permissions.join(', ')}]): Found specific permission "${requiredPerm}".`);
           return true; // User has this specific required permission.
      }

      // Check WRITE -> READ implication for this specific required permission.
      if (requiredPerm.startsWith('READ_')) {
          const writePermission = requiredPerm.replace('READ_', 'WRITE_');
          if (permissionSet.value.has(writePermission)) {
              // console.log(`PermissionCheck: hasAnyPermission([${permissions.join(', ')}]): Found WRITE equivalent "${writePermission}" for READ permission "${requiredPerm}".`);
              return true; // User has the corresponding WRITE permission for a required READ permission.
          }
      }
    }

    // If the loop finishes without finding any matching permission (or WRITE equivalent for READ), return false.
    return false;
  };


   /**
   * Checks if the user has *all* of the specified permissions using efficient lookups.
   * This function iterates the *input list* of permissions (which should be small per check),
   * ensuring each required permission (or its READ/WRITE equivalent) is present.
   * @param permissions Array of permission strings to check
   * @returns boolean True if the user has all of the permissions (or WRITE equivalents for READ) or 'ALL_PERMISSIONS'. Returns false for empty/invalid input.
   */
  const hasAllPermissions = (permissions: string[] | undefined): boolean => {
    // Handle undefined/invalid input.
    if (!permissions || !Array.isArray(permissions)) {
      // console.warn('PermissionCheck: Invalid permissions array in hasAllPermissions:', permissions); // Reduce log noise
      return false; // Cannot have all permissions from an invalid list.
    }

    // If an empty array is passed, the user technically has all 0 required permissions.
    if (permissions.length === 0) {
        // console.log('PermissionCheck: hasAllPermissions([]): Empty permissions list requires no permissions.');
        return true;
    }

    // Check for 'ALL_PERMISSIONS' first (efficient Set lookup).
     if (permissionSet.value.has('ALL_PERMISSIONS')) {
         // console.log(`PermissionCheck: hasAllPermissions([${permissions.join(', ')}]): ALL_PERMISSIONS grants access.`);
         return true; // User is a super-admin.
     }

    // Iterate through the *input list* of required permissions.
    for (const requiredPerm of permissions) {
       if (typeof requiredPerm !== 'string') {
            console.warn('PermissionCheck: hasAllPermissions received non-string permission in array:', requiredPerm);
            return false; // Invalid entry in the required list means user cannot have *all* valid ones.
       }

      // Check if this *required* permission is present in the user's set.
      if (!permissionSet.value.has(requiredPerm)) {
          // If the specific permission is NOT in the user's set, check if it's a READ permission...
          if (requiredPerm.startsWith('READ_')) {
              const writePermission = requiredPerm.replace('READ_', 'WRITE_');
              // ...and check if the user has the corresponding WRITE permission instead.
              if (!permissionSet.value.has(writePermission)) {
                  // If the user is missing the READ permission AND doesn't have the WRITE equivalent,
                  // then they are missing this required permission.
                   // console.log(`PermissionCheck: hasAllPermissions([${permissions.join(', ')}]): Missing required permission "${requiredPerm}" (and no WRITE equivalent "${writePermission}").`);
                  return false; // User does not have ALL required permissions.
              }
              // If they had the WRITE equivalent, the check for this required READ permission passes.
          } else {
              // If the required permission is not in the user's set and is NOT a READ_ permission,
              // then the user is missing this required permission.
               // console.log(`PermissionCheck: hasAllPermissions([${permissions.join(', ')}]): Missing required permission "${requiredPerm}".`);
              return false; // User does not have ALL required permissions.
          }
      }
      // If the check passed for the current required permission (either it was in the set, or it was READ_ and WRITE_ was in the set),
      // the loop continues to check the next required permission.
    }

    // If the loop completes without returning false, it means the user has all required permissions
    // (either directly or via WRITE equivalents for READ permissions).
    // console.log(`PermissionCheck: hasAllPermissions([${permissions.join(', ')}]): User has all required permissions.`);
    return true;
  };


  // The async ensureAndCheckPermission is not directly used by the filtering computed properties,
  // but it's kept here if other parts of the app need it.
  // It's crucial that permissions are loaded by other means (like onMounted in DesktopView calling refreshPermissions)
  // before computed properties relying on hasPermission are initially evaluated for a large set.
   const isCheckingPermissions = ref(false); // State for ensureAndCheckPermission
   // Note: Ensure authStore.fetchPermissions exists and correctly updates authStore.user.permissions

   // A simplified async check that just uses the store's loading state if available
   const checkPermissionAsync = async (permission?: string): Promise<boolean> => {
        // Optionally wait for the store to finish loading if it has a loading state
        // while(authStore.isLoadingPermissions) { // Assuming authStore has an isLoadingPermissions state
        //    await new Promise(resolve => setTimeout(resolve, 50));
        //}
         // If permissions were empty/null when called, this check will correctly use the empty set.
         // You might *want* to wait for fetchPermissions to complete *before* checking in some async contexts.
         // If you need to *force* load before check, use ensureAndCheckPermission.
         // For UI filtering via computed, relying on the reactive permissionSet is correct.
        return hasPermission(permission); // Just use the reactive check after potential wait
   };


  // Expose the public API of the composable
  return {
    hasPermission, // Use this for single permission checks (O(1))
    hasAnyPermission, // Use this for checking if user has at least one permission from a list (Efficient)
    hasAllPermissions, // Use this for checking if user has all permissions from a list (Efficient)
    // isCheckingPermissions, // Only if ensureAndCheckPermission is actively used
    // We don't need to export the raw userPermissions array or the internal permissionSet unless strictly necessary
    // userPermissions: computed(() => authStore.user?.permissions), // Optional: export the original array
  };
}