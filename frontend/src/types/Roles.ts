export interface Permission {
    id: number;
    name: string;
    name_alias: string;
    // New structure after migration
    module: string;
    sub_module: string | null;
    action: string;
    bitmask_value: number | null;
    module_display: string;
    display_group: string; // For grouping related modules together
    action_display: string;
    description: string | null;
    authority_id?: number;
    created_at?: string;
}

export interface Role {
    id: number;
    name: string;
    description: string;
    sort_order: number;
    power: number;
    permissions?: Permission[];
    user_count?: number;
    module_count?: number;
}

export interface RolePermission {
    role_id: number;
    permission_id: number;
    authority_id: number;
}

// Hierarchical grouping for UI
export interface PermissionSubGroup {
    subModule: string;
    subModuleDisplay: string;
    permissions: Permission[];
    selectedCount: number;
    totalCount: number;
}

export interface PermissionGroup {
    module: string;
    displayGroup: string; // Used for grouping (e.g., "Rechnungen" for both invoice and invoiceitems)
    moduleDisplay: string; // Used for display (e.g., "Rechnungen" vs "Rechnungsposten")
    icon: string;
    isAdmin: boolean;
    permissions: Permission[]; // Permissions without sub_module
    subGroups: PermissionSubGroup[];
    selectedCount: number;
    totalCount: number;
}
  