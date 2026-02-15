import type { ComputedRef } from 'vue'
import type { Permission } from '@/types/Roles'

/**
 * Composable for permission dependencies (action-level + module-level)
 * NEW: Works with module/sub_module/action structure instead of permission names
 */
export function usePermissionDependencies(permissions: ComputedRef<Permission[]>) {
  /**
   * Get parent action based on hierarchy
   * Hierarchy: view ← read ← write ← delete/create
   */
  const getParentAction = (action: string): string | null => {
    const actionHierarchy: Record<string, string> = {
      'delete': 'write',
      'create': 'write',
      'write': 'read',
      'read': 'view',
      'admin': 'write' // admin actions require write
    }

    return actionHierarchy[action.toLowerCase()] || null
  }

  /**
   * Get all parent actions recursively
   * Example: delete → write → read → view
   */
  const getAllParentActions = (action: string): string[] => {
    const parents: string[] = []
    let current = action.toLowerCase()

    while (true) {
      const parent = getParentAction(current)
      if (!parent) break
      parents.push(parent)
      current = parent
    }

    return parents
  }

  /**
   * Find permission by module/sub_module/action
   */
  const findPermission = (
    module: string,
    subModule: string | null,
    action: string
  ): Permission | null => {
    return permissions.value.find(
      (p) =>
        p.module === module &&
        (subModule ? p.sub_module === subModule : !p.sub_module) &&
        p.action === action
    ) || null
  }

  /**
   * Get all action parent permissions for a given permission
   * Example: (blackboard, area, delete) → (blackboard, area, write) → (blackboard, area, read)
   */
  const getActionParentPermissions = (permission: Permission): Permission[] => {
    const parents: Permission[] = []
    const parentActions = getAllParentActions(permission.action)

    parentActions.forEach((parentAction) => {
      const parent = findPermission(permission.module, permission.sub_module, parentAction)
      if (parent) parents.push(parent)
    })

    return parents
  }

  /**
   * Get module parent permission for a sub_module permission
   * Example: (blackboard, area, read) → (blackboard, null, read)
   */
  const getModuleParentPermission = (permission: Permission): Permission | null => {
    // No sub_module → no parent
    if (!permission.sub_module) return null

    // Find permission with same module + action, but NO sub_module
    return findPermission(permission.module, null, permission.action)
  }

  /**
   * Get all parents (action + module hierarchy)
   * NEW: Uses module/sub_module/action structure
   */
  const getAllParentPermissions = (permission: Permission): Permission[] => {
    const parents: Permission[] = []
    const addedIds = new Set<number>()

    // Helper to add unique permission
    const addPermission = (p: Permission) => {
      if (!addedIds.has(p.id)) {
        parents.push(p)
        addedIds.add(p.id)
      }
    }

    // 1. Module parent (if sub_module exists)
    const moduleParent = getModuleParentPermission(permission)
    if (moduleParent) {
      addPermission(moduleParent)

      // 2. Action parents of module parent
      const moduleParentActionParents = getActionParentPermissions(moduleParent)
      moduleParentActionParents.forEach((parent) => addPermission(parent))
    }

    // 3. Action parents of current permission
    const actionParents = getActionParentPermissions(permission)
    actionParents.forEach((parent) => addPermission(parent))

    return parents
  }

  /**
   * Get all child actions
   * Example: read → [write, delete, create]
   */
  const getChildActions = (action: string): string[] => {
    const children: string[] = []
    const actionLower = action.toLowerCase()

    // Find all actions that have this action as parent
    const allActions = ['view', 'read', 'write', 'delete', 'create', 'admin']
    allActions.forEach((a) => {
      if (getParentAction(a) === actionLower) {
        children.push(a)
      }
    })

    return children
  }

  /**
   * Get all child permissions (direct and recursive)
   * NEW: Uses module/sub_module/action structure
   */
  const getAllChildPermissions = (permission: Permission): Permission[] => {
    const children: Permission[] = []
    const addedIds = new Set<number>()

    const findChildren = (p: Permission) => {
      // 1. Find action children (same module/sub_module, child actions)
      const childActions = getChildActions(p.action)
      childActions.forEach((childAction) => {
        const child = findPermission(p.module, p.sub_module, childAction)
        if (child && !addedIds.has(child.id)) {
          children.push(child)
          addedIds.add(child.id)
          // Recurse
          findChildren(child)
        }
      })

      // 2. Find sub_module children if this is a parent module (no sub_module)
      if (!p.sub_module) {
        permissions.value.forEach((child) => {
          if (
            child.module === p.module &&
            child.sub_module &&
            child.action === p.action &&
            !addedIds.has(child.id)
          ) {
            children.push(child)
            addedIds.add(child.id)
            // Recurse
            findChildren(child)
          }
        })
      }
    }

    findChildren(permission)
    return children
  }

  /**
   * Format permission display name
   * Example: (blackboard, area, read) → "Blackboard › Area › Read"
   */
  const formatPermissionName = (permission: Permission): string => {
    const parts: string[] = []

    // Use module_display if available, else capitalize module
    if (permission.module_display) {
      parts.push(permission.module_display)
    } else {
      parts.push(permission.module.charAt(0).toUpperCase() + permission.module.slice(1))
    }

    // Add sub_module if exists
    if (permission.sub_module) {
      parts.push(permission.sub_module.charAt(0).toUpperCase() + permission.sub_module.slice(1))
    }

    // Use action_display if available, else capitalize action
    if (permission.action_display) {
      parts.push(permission.action_display)
    } else {
      parts.push(permission.action.charAt(0).toUpperCase() + permission.action.slice(1))
    }

    return parts.join(' › ')
  }

  /**
   * Apply dependencies: add parents, remove children
   * Returns updated permission IDs with reasons for changes
   */
  const applyDependencies = (
    newIds: number[],
    oldIds: number[]
  ): {
    ids: number[]
    added: Array<{ id: number; name: string; reason: string }>
    removed: Array<{ id: number; name: string; reason: string }>
  } => {
    const result = new Set(newIds)
    const added: Array<{ id: number; name: string; reason: string }> = []
    const removed: Array<{ id: number; name: string; reason: string }> = []

    // Find added permissions
    const addedIds = newIds.filter((id) => !oldIds.includes(id))

    // For each added permission, add all required parents
    addedIds.forEach((id) => {
      const perm = permissions.value.find((p) => p.id === id)
      if (!perm) return

      const parents = getAllParentPermissions(perm)

      parents.forEach((parent) => {
        if (!result.has(parent.id)) {
          result.add(parent.id)
          added.push({
            id: parent.id,
            name: formatPermissionName(parent),
            reason: `Benötigt von ${formatPermissionName(perm)}`
          })
        }
      })
    })

    // Find removed permissions
    const removedIds = oldIds.filter((id) => !newIds.includes(id))

    // For each removed permission, remove all children
    removedIds.forEach((id) => {
      const perm = permissions.value.find((p) => p.id === id)
      if (!perm) return

      const children = getAllChildPermissions(perm)

      children.forEach((child) => {
        if (result.has(child.id)) {
          result.delete(child.id)
          removed.push({
            id: child.id,
            name: formatPermissionName(child),
            reason: `Abhängig von ${formatPermissionName(perm)}`
          })
        }
      })
    })

    return {
      ids: Array.from(result),
      added,
      removed
    }
  }

  /**
   * Check if permission was auto-added (has parents that are selected)
   */
  const isAutoAdded = (permissionId: number, selectedIds: number[]): boolean => {
    const perm = permissions.value.find((p) => p.id === permissionId)
    if (!perm) return false

    const parents = getAllParentPermissions(perm)
    return parents.some((p) => selectedIds.includes(p.id))
  }

  return {
    getParentAction,
    getAllParentActions,
    getModuleParentPermission,
    getAllParentPermissions,
    getAllChildPermissions,
    applyDependencies,
    isAutoAdded,
    formatPermissionName
  }
}
