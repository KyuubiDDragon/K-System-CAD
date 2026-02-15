/**
 * Usage Examples for useModulePermission Composable
 *
 * This file demonstrates how to use the new bitmask-aware permission composable
 * in Vue 3 components. The composable supports both the new bitmask format and
 * legacy string-based permissions for backwards compatibility.
 */

import { useModulePermission } from './useModulePermission';

/**
 * Example 1: Basic Permission Check in a Component
 *
 * Use this pattern when you need to check a single permission
 * for show/hide logic or conditional rendering.
 */
export function example1_BasicPermissionCheck() {
  const { hasModulePermission } = useModulePermission();

  // Check if user can read employee data
  const canReadEmployees = hasModulePermission('employee', 'read');

  // Check if user can write to calendar
  const canWriteCalendar = hasModulePermission('calendar', 'write');

  // Check if user can delete reports
  const canDeleteReports = hasModulePermission('report', 'delete');

  return {
    canReadEmployees,
    canWriteCalendar,
    canDeleteReports
  };
}

/**
 * Example 2: Check Multiple Permissions (ANY)
 *
 * Use this when you want to show UI if the user has at least ONE
 * of several permissions.
 */
export function example2_CheckAnyPermission() {
  const { hasAnyModulePermission } = useModulePermission();

  // Show employee section if user can read OR write
  const canAccessEmployees = hasAnyModulePermission('employee', ['read', 'write']);

  // Show calendar if user has any calendar permission
  const canAccessCalendar = hasAnyModulePermission('calendar', ['read', 'write', 'delete', 'admin']);

  return {
    canAccessEmployees,
    canAccessCalendar
  };
}

/**
 * Example 3: Check Multiple Permissions (ALL)
 *
 * Use this when you need to ensure the user has ALL specified permissions
 * before showing UI or enabling functionality.
 */
export function example3_CheckAllPermissions() {
  const { hasAllModulePermissions } = useModulePermission();

  // Check if user has full employee management access
  const canFullyManageEmployees = hasAllModulePermissions('employee', ['read', 'write', 'delete']);

  // Check if user can create and view reports
  const canCreateAndViewReports = hasAllModulePermissions('report', ['create', 'view']);

  return {
    canFullyManageEmployees,
    canCreateAndViewReports
  };
}

/**
 * Example 4: Vue Component Template Usage
 *
 * This shows how to use the composable in a Vue 3 component
 * with Composition API.
 */
export const Example4_VueComponent = `
<template>
  <v-container>
    <!-- Show button only if user can write -->
    <v-btn
      v-if="canEditEmployee"
      @click="editEmployee"
      color="primary"
    >
      Edit Employee
    </v-btn>

    <!-- Show delete button only if user has delete permission -->
    <v-btn
      v-if="canDeleteEmployee"
      @click="deleteEmployee"
      color="error"
    >
      Delete Employee
    </v-btn>

    <!-- Show admin panel if user has admin permission -->
    <v-card v-if="isEmployeeAdmin">
      <v-card-title>Admin Panel</v-card-title>
      <v-card-text>
        Admin-only content here
      </v-card-text>
    </v-card>

    <!-- Show section if user has ANY of the permissions -->
    <v-card v-if="hasAnyEmployeeAccess">
      <v-card-title>Employee Data</v-card-title>
      <v-card-text>
        <!-- Employee data here -->
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { useModulePermission } from '@/composables/useModulePermission';

const {
  hasModulePermission,
  hasAnyModulePermission,
  hasAllModulePermissions
} = useModulePermission();

// Single permission checks
const canEditEmployee = hasModulePermission('employee', 'write');
const canDeleteEmployee = hasModulePermission('employee', 'delete');
const isEmployeeAdmin = hasModulePermission('employee', 'admin');

// Check if user has ANY employee permission
const hasAnyEmployeeAccess = hasAnyModulePermission('employee', ['read', 'write', 'delete']);

// Check if user has ALL required permissions for a feature
const canFullyManageEmployees = hasAllModulePermissions('employee', ['read', 'write', 'delete']);

const editEmployee = () => {
  console.log('Editing employee...');
};

const deleteEmployee = () => {
  console.log('Deleting employee...');
};
</script>
`;

/**
 * Example 5: Router Navigation Guard
 *
 * Use this pattern to protect routes based on module permissions.
 */
export const Example5_RouterGuard = `
import { useModulePermission } from '@/composables/useModulePermission';
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router';

export function employeeRouteGuard(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext
) {
  const { hasModulePermission } = useModulePermission();

  // Check if user can access employee module
  if (hasModulePermission('employee', 'read')) {
    next(); // Allow navigation
  } else {
    next('/unauthorized'); // Redirect to unauthorized page
  }
}

// In your router configuration:
{
  path: '/employees',
  component: EmployeeView,
  beforeEnter: employeeRouteGuard
}
`;

/**
 * Example 6: Permission Format Detection
 *
 * Use this when you need to know which permission format is being used
 * (useful for debugging or conditional logic).
 */
export function example6_FormatDetection() {
  const { isUsingBitmaskFormat, getModulePermissionValue } = useModulePermission();

  console.log('Using bitmask format:', isUsingBitmaskFormat.value);

  if (isUsingBitmaskFormat.value) {
    // Get raw bitmask value for debugging
    const employeePermValue = getModulePermissionValue('employee');
    console.log('Employee permission bitmask:', employeePermValue);

    // Example output: 7 (which is 0b111 = read + write + delete)
  }
}

/**
 * Example 7: Permission Hierarchy in Action
 *
 * The composable automatically handles permission hierarchy:
 * - DELETE implies WRITE and READ
 * - WRITE implies READ
 * - ADMIN implies all others
 */
export function example7_PermissionHierarchy() {
  const { hasModulePermission } = useModulePermission();

  // If user has DELETE permission...
  const hasDelete = hasModulePermission('employee', 'delete');

  // ...they automatically have WRITE permission
  const hasWrite = hasModulePermission('employee', 'write');

  // ...and READ permission
  const hasRead = hasModulePermission('employee', 'read');

  console.log('Delete:', hasDelete);
  console.log('Write:', hasWrite); // Will be true if hasDelete is true
  console.log('Read:', hasRead);   // Will be true if hasDelete or hasWrite is true
}

/**
 * Example 8: Backwards Compatibility with Legacy Format
 *
 * The composable automatically detects and works with legacy string-based
 * permissions. No code changes needed in components!
 */
export function example8_BackwardsCompatibility() {
  const { hasModulePermission } = useModulePermission();

  // Works with legacy format: ["READ_EMPLOYEE", "WRITE_EMPLOYEE"]
  // Automatically converts ('employee', 'read') → 'READ_EMPLOYEE' check
  const canRead = hasModulePermission('employee', 'read');

  // Works with bitmask format: {"employee": 7}
  // Automatically uses bitwise operations: (7 & 1) === 1
  const canWrite = hasModulePermission('employee', 'write');

  // Same code, works with both formats!
  return { canRead, canWrite };
}

/**
 * TESTING EXAMPLES
 *
 * How to test different permission scenarios
 */

/**
 * Test Case 1: Bitmask Format
 *
 * Permissions: {"employee": 7, "calendar": 23}
 * Binary breakdown:
 * - employee: 7 = 0b000111 = read + write + delete
 * - calendar: 23 = 0b010111 = read + write + delete + view
 */
export function testCase1_BitmaskFormat() {
  const { hasModulePermission } = useModulePermission();

  // Should return true
  console.assert(hasModulePermission('employee', 'read') === true);
  console.assert(hasModulePermission('employee', 'write') === true);
  console.assert(hasModulePermission('employee', 'delete') === true);

  // Should return false (no admin permission)
  console.assert(hasModulePermission('employee', 'admin') === false);

  // Should return true
  console.assert(hasModulePermission('calendar', 'view') === true);
}

/**
 * Test Case 2: Legacy Format
 *
 * Permissions: ["READ_EMPLOYEE", "WRITE_EMPLOYEE", "READ_CALENDAR"]
 */
export function testCase2_LegacyFormat() {
  const { hasModulePermission } = useModulePermission();

  // Should return true
  console.assert(hasModulePermission('employee', 'read') === true);
  console.assert(hasModulePermission('employee', 'write') === true);

  // Should return false (no delete permission)
  console.assert(hasModulePermission('employee', 'delete') === false);

  // Should return true
  console.assert(hasModulePermission('calendar', 'read') === true);

  // Should return false (no write permission for calendar)
  console.assert(hasModulePermission('calendar', 'write') === false);
}

/**
 * Test Case 3: ALL_PERMISSIONS Super Admin
 *
 * Permissions: ["ALL_PERMISSIONS"] or {"ALL_PERMISSIONS": 1}
 */
export function testCase3_SuperAdmin() {
  const { hasModulePermission } = useModulePermission();

  // Should return true for ANY module and ANY action
  console.assert(hasModulePermission('employee', 'read') === true);
  console.assert(hasModulePermission('employee', 'admin') === true);
  console.assert(hasModulePermission('any_module', 'any_action') === true);
}

/**
 * MIGRATION GUIDE
 *
 * How to migrate from usePermissionCheck to useModulePermission:
 *
 * BEFORE (usePermissionCheck):
 * ```typescript
 * const { hasPermission } = usePermissionCheck();
 * const canEdit = hasPermission('WRITE_EMPLOYEE');
 * ```
 *
 * AFTER (useModulePermission):
 * ```typescript
 * const { hasModulePermission } = useModulePermission();
 * const canEdit = hasModulePermission('employee', 'write');
 * ```
 *
 * Note: Both composables can coexist. useModulePermission is specifically
 * designed for the new bitmask system but maintains full backwards
 * compatibility with the legacy system.
 */
