import { computed, type ComputedRef } from 'vue'
import type { Permission, PermissionGroup, PermissionSubGroup } from '@/types/Roles'

/**
 * Composable for permission grouping and hierarchy
 */
export function usePermissionGrouping(
  permissions: ComputedRef<Permission[]>,
  selectedIds: ComputedRef<number[]>
) {
  /**
   * Action sort order (consistent order across all modules)
   * view → read → write → create → delete → admin → execute
   */
  const actionSortOrder: Record<string, number> = {
    'view': 1,
    'read': 2,
    'write': 3,
    'create': 4,
    'delete': 5,
    'admin': 6,
    'execute': 7
  }

  /**
   * Sort permissions by action order
   */
  const sortPermissionsByAction = (perms: Permission[]): Permission[] => {
    return [...perms].sort((a, b) => {
      const orderA = actionSortOrder[a.action.toLowerCase()] || 999
      const orderB = actionSortOrder[b.action.toLowerCase()] || 999
      return orderA - orderB
    })
  }

  /**
   * Icon mapping for modules
   */
  const moduleIcons: Record<string, string> = {
    'access': 'mdi-key',
    'account': 'mdi-account-circle',
    'admin': 'mdi-shield-star',
    'apartment': 'mdi-home-city',
    'application': 'mdi-file-document-edit',
    'auth': 'mdi-login',
    'authority': 'mdi-domain',
    'blackboard': 'mdi-bulletin-board',
    'calendar': 'mdi-calendar',
    'cheatsheet': 'mdi-text-box-check',
    'company': 'mdi-office-building',
    'dispatch': 'mdi-fire-truck',
    'document': 'mdi-file-document',
    'employee': 'mdi-account-group',
    'filemanager': 'mdi-folder-multiple',
    'fireprotection': 'mdi-fire',
    'invoice': 'mdi-receipt',
    'invoiceitems': 'mdi-receipt-text',
    'mail': 'mdi-email',
    'map': 'mdi-map-marker',
    'messaging': 'mdi-message-text',
    'person': 'mdi-account',
    'rank': 'mdi-star',
    'report': 'mdi-chart-box',
    'settings': 'mdi-cog',
    'suspended': 'mdi-account-cancel',
    'system': 'mdi-server',
    'template': 'mdi-file-document-outline',
    'todo': 'mdi-checkbox-marked-circle',
    'training': 'mdi-school',
    'users': 'mdi-account-multiple',
    'vehicle': 'mdi-car',
    'weather': 'mdi-weather-partly-cloudy',
    'whiteboard': 'mdi-draw'
  }

  /**
   * Get icon for module
   */
  const getModuleIcon = (module: string): string => {
    return moduleIcons[module] || 'mdi-folder'
  }

  /**
   * Check if permission is admin-level
   * Admin permissions are either:
   * 1. module='admin' with sub_module set (e.g., admin › users)
   * 2. Legacy: module has 'admin' in the name (e.g., READ_BLACKBOARD_ADMIN)
   */
  const isAdminPermission = (permission: Permission): boolean => {
    // Check if module is 'admin'
    if (permission.module === 'admin') return true

    // Check for legacy admin permissions (name contains 'ADMIN')
    if (permission.name.includes('ADMIN')) return true

    return false
  }

  /**
   * Get grouping key for a permission
   * Uses display_group for grouping (e.g., "Rechnungen" groups both invoice and invoiceitems)
   */
  const getGroupKey = (permission: Permission): string => {
    return permission.display_group || permission.module
  }

  /**
   * Get display name for group
   * Uses display_group as the main group name
   * Removes "Admin_" prefix for admin groups (they're already in admin tab)
   */
  const getGroupDisplay = (permission: Permission): string => {
    let displayName = permission.display_group || permission.module_display || capitalize(permission.module)

    // Remove "Admin_" prefix for cleaner display in admin tab
    if (displayName.startsWith('Admin_')) {
      displayName = displayName.substring(6) // Remove "Admin_" (6 characters)
    }

    return displayName
  }

  /**
   * Capitalize first letter
   */
  const capitalize = (str: string): string => {
    if (!str) return ''
    return str.charAt(0).toUpperCase() + str.slice(1)
  }

  /**
   * Get display name for module/sub_module
   */
  const getDisplayName = (
    module: string,
    subModule: string | null,
    moduleDisplay?: string
  ): string => {
    // Use module_display if available
    if (moduleDisplay) return moduleDisplay

    // Generate from module + sub_module
    const moduleName = capitalize(module)
    if (subModule) {
      return `${moduleName} › ${capitalize(subModule)}`
    }
    return moduleName
  }

  /**
   * Group permissions hierarchically by display_group, then by module/sub_module
   * NEW: Uses display_group for main grouping (e.g., "Rechnungen" groups invoice + invoiceitems)
   * Filters out special permissions (ALL_PERMISSIONS, IS_SUSPENDED_GROUP)
   */
  const groupPermissions = computed((): PermissionGroup[] => {
    const groups = new Map<string, PermissionGroup>()

    // Filter out special permissions that should not be shown in the list
    const filteredPermissions = permissions.value.filter(
      (p) => p.name !== 'ALL_PERMISSIONS' && p.name !== 'IS_SUSPENDED_GROUP' && p.name !== 'SYSTEM_ADMIN'
    )

    // First pass: Group by display_group and collect all modules in each group
    const modulesByGroup = new Map<string, Set<string>>()
    filteredPermissions.forEach((p) => {
      const groupKey = getGroupKey(p)
      if (!modulesByGroup.has(groupKey)) {
        modulesByGroup.set(groupKey, new Set())
      }
      modulesByGroup.get(groupKey)!.add(p.module)
    })

    filteredPermissions.forEach((p) => {
      const groupKey = getGroupKey(p)
      const displayGroup = getGroupDisplay(p)

      // Get or create main group
      if (!groups.has(groupKey)) {
        groups.set(groupKey, {
          module: groupKey,
          displayGroup: displayGroup,
          moduleDisplay: displayGroup,
          icon: getModuleIcon(p.module),
          isAdmin: isAdminPermission(p),
          permissions: [],
          subGroups: [],
          selectedCount: 0,
          totalCount: 0
        })
      }

      const group = groups.get(groupKey)!
      const hasMultipleModules = modulesByGroup.get(groupKey)!.size > 1

      // If this display_group has multiple modules, treat each module as a sub-group
      if (hasMultipleModules) {
        // Find or create sub-group for this module
        let moduleSubGroup = group.subGroups.find((sg) => sg.subModule === p.module)

        if (!moduleSubGroup) {
          moduleSubGroup = {
            subModule: p.module,
            subModuleDisplay: p.module_display || capitalize(p.module),
            permissions: [],
            selectedCount: 0,
            totalCount: 0
          }
          group.subGroups.push(moduleSubGroup)
        }

        // If permission has sub_module, create nested structure within module sub-group
        // For now, just add all permissions to the module sub-group
        moduleSubGroup.permissions.push(p)
        moduleSubGroup.totalCount++
        group.totalCount++
      } else {
        // Single module in this display_group
        if (!p.sub_module) {
          // Permission without sub_module → main level
          group.permissions.push(p)
          group.totalCount++
        } else {
          // Permission with sub_module → sub-group
          let subGroup = group.subGroups.find((sg) => sg.subModule === p.sub_module)

          if (!subGroup) {
            subGroup = {
              subModule: p.sub_module,
              subModuleDisplay: capitalize(p.sub_module),
              permissions: [],
              selectedCount: 0,
              totalCount: 0
            }
            group.subGroups.push(subGroup)
          }

          subGroup.permissions.push(p)
          subGroup.totalCount++
          group.totalCount++
        }
      }
    })

    // Sort permissions by action and calculate selected counts
    groups.forEach((group) => {
      // Sort main permissions by action order
      group.permissions = sortPermissionsByAction(group.permissions)

      // Calculate selected count for main permissions
      group.selectedCount = group.permissions.filter((p) =>
        selectedIds.value.includes(p.id)
      ).length

      // Sort and count sub-groups
      group.subGroups.forEach((sub) => {
        sub.permissions = sortPermissionsByAction(sub.permissions)
        sub.selectedCount = sub.permissions.filter((p) => selectedIds.value.includes(p.id)).length
        group.selectedCount += sub.selectedCount
      })

      // Sort sub-groups by name
      group.subGroups.sort((a, b) => a.subModuleDisplay.localeCompare(b.subModuleDisplay))
    })

    // Sort groups by display name
    return Array.from(groups.values()).sort((a, b) =>
      a.displayGroup.localeCompare(b.displayGroup)
    )
  })

  /**
   * Filter permissions by search query
   */
  const filterPermissionsBySearch = (
    permissions: Permission[],
    searchQuery: string
  ): Permission[] => {
    if (!searchQuery) return permissions

    const query = searchQuery.toLowerCase()
    return permissions.filter((p) =>
      p.name.toLowerCase().includes(query) ||
      (p.module_display?.toLowerCase() || p.module.toLowerCase()).includes(query) ||
      (p.display_group?.toLowerCase() || '').includes(query) ||
      (p.action_display?.toLowerCase() || p.action.toLowerCase()).includes(query) ||
      p.description?.toLowerCase().includes(query) ||
      p.module.toLowerCase().includes(query) ||
      p.sub_module?.toLowerCase().includes(query) ||
      p.action.toLowerCase().includes(query)
    )
  }

  /**
   * Filter groups by admin status
   */
  const filterGroups = (
    groups: PermissionGroup[],
    filter: 'all' | 'admin' | 'general'
  ): PermissionGroup[] => {
    if (filter === 'all') return groups
    if (filter === 'admin') return groups.filter((g) => g.isAdmin)
    return groups.filter((g) => !g.isAdmin)
  }

  /**
   * Filter groups by search query
   * Removes groups with no matching permissions
   */
  const filterGroupsBySearch = (
    groups: PermissionGroup[],
    searchQuery: string
  ): PermissionGroup[] => {
    if (!searchQuery) return groups

    const query = searchQuery.toLowerCase()

    // Filter groups and their permissions
    return groups
      .map((group) => {
        // Filter main permissions
        const filteredPermissions = group.permissions.filter((p) =>
          p.name.toLowerCase().includes(query) ||
          (p.module_display?.toLowerCase() || p.module.toLowerCase()).includes(query) ||
          (p.display_group?.toLowerCase() || '').includes(query) ||
          (p.action_display?.toLowerCase() || p.action.toLowerCase()).includes(query) ||
          p.description?.toLowerCase().includes(query) ||
          p.module.toLowerCase().includes(query) ||
          p.sub_module?.toLowerCase().includes(query) ||
          p.action.toLowerCase().includes(query) ||
          group.displayGroup.toLowerCase().includes(query) ||
          group.moduleDisplay.toLowerCase().includes(query)
        )

        // Filter sub-groups
        const filteredSubGroups = group.subGroups
          .map((sub) => ({
            ...sub,
            permissions: sub.permissions.filter((p) =>
              p.name.toLowerCase().includes(query) ||
              (p.module_display?.toLowerCase() || p.module.toLowerCase()).includes(query) ||
              (p.display_group?.toLowerCase() || '').includes(query) ||
              (p.action_display?.toLowerCase() || p.action.toLowerCase()).includes(query) ||
              p.description?.toLowerCase().includes(query) ||
              p.module.toLowerCase().includes(query) ||
              p.sub_module?.toLowerCase().includes(query) ||
              p.action.toLowerCase().includes(query) ||
              group.displayGroup.toLowerCase().includes(query) ||
              group.moduleDisplay.toLowerCase().includes(query) ||
              sub.subModuleDisplay.toLowerCase().includes(query)
            )
          }))
          .filter((sub) => sub.permissions.length > 0)

        return {
          ...group,
          permissions: filteredPermissions,
          subGroups: filteredSubGroups,
          totalCount: filteredPermissions.length + filteredSubGroups.reduce((sum, sg) => sum + sg.permissions.length, 0)
        }
      })
      .filter((group) => group.totalCount > 0)
  }

  /**
   * Count permissions by filter
   */
  const countByFilter = computed(() => {
    const groups = groupPermissions.value
    return {
      all: groups.reduce((sum, g) => sum + g.totalCount, 0),
      admin: groups.filter((g) => g.isAdmin).reduce((sum, g) => sum + g.totalCount, 0),
      general: groups.filter((g) => !g.isAdmin).reduce((sum, g) => sum + g.totalCount, 0)
    }
  })

  /**
   * Check if any admin permissions are selected
   */
  const hasSelectedAdminPermissions = computed((): boolean => {
    return groupPermissions.value
      .filter((g) => g.isAdmin)
      .some((g) => g.selectedCount > 0)
  })

  /**
   * Get all permission IDs in a group (including sub-groups)
   */
  const getGroupPermissionIds = (group: PermissionGroup): number[] => {
    const ids: number[] = []

    // Main permissions
    group.permissions.forEach((p) => ids.push(p.id))

    // Sub-group permissions
    group.subGroups.forEach((sub) => {
      sub.permissions.forEach((p) => ids.push(p.id))
    })

    return ids
  }

  /**
   * Get all permission IDs in a sub-group
   */
  const getSubGroupPermissionIds = (subGroup: PermissionSubGroup): number[] => {
    return subGroup.permissions.map((p) => p.id)
  }

  return {
    groupPermissions,
    filterGroups,
    filterGroupsBySearch,
    countByFilter,
    hasSelectedAdminPermissions,
    getGroupPermissionIds,
    getSubGroupPermissionIds,
    getModuleIcon,
    getDisplayName,
    isAdminPermission
  }
}
