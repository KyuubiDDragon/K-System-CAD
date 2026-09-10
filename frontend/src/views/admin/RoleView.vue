<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, type Ref } from 'vue';
import { useRoute } from 'vue-router'; // Import if permissions needed
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store (optional, if needed elsewhere)
import type { Permission, Role, RolePermission } from '@/types/Roles'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import type { Category } from '@/types/Report';
import { useI18n } from 'vue-i18n';
import { usePermissionGrouping } from '@/composables/usePermissionGrouping';
import { usePermissionDependencies } from '@/composables/usePermissionDependencies';

// --- Interfaces & Types ---
interface EditedRoleData {
    id?: number | null;
    name: string;
    description: string | null; // Allow null
    sort_order: number | null;
    power: number | null;
    permissionIds: number[]; // Store only IDs
}

// --- Store, Router & Permissions ---
const authStore = useAuthStore(); // Instantiate store
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  desktopWindow: false,
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Component State ---
const permissions = ref<Permission[]>([]);
const roles = ref<Role[]>([]);
const rolePermissions = ref<RolePermission[]>([]); // Role-Permission mappings from API
const loadingRoles = ref(false);
const loadingPermissions = ref(false);
const loadingRolePermissions = ref(false); // Separate loading for mappings
const savingRole = ref(false);
const categories = ref<Category[]>([]);
const loadingCategories = ref(false);
const selectedCategoryIds = ref<number[]>([]);

// Search/Filter state (NEW: unified search + filter)
const searchQuery = ref('');
const permissionFilter = ref<'all' | 'admin' | 'general'>('all');

// Master-Detail state (NEW: for left navigation / right detail view)
const selectedModule = ref<string | null>(null);
const masterTab = ref<'general' | 'admin'>('general'); // Tab state for left panel

// Store original data for change detection
const originalPermissionIds = ref<number[]>([]);
const originalCategoryIds = ref<number[]>([]);

// --- UI State & Data ---
const showDetailView = ref(false);
const activeTab = ref(0);
const roleInfoFormRef = ref<any>(null);
const isRoleInfoFormValid = ref(false);

// NEU: Zustand für Lösch-Dialog
const deleteRoleDialog = reactive({
    show: false,
    title: t('roleView.deleteTitle'),
    text: '',
    confirmText: t('delete'),
    confirmColor: 'error',
    action: (() => {}) as () => Promise<void>,
    role: null as Role | null,
});

const initialRoleData: EditedRoleData = {
    id: null,
    name: '',
    description: null,
    sort_order: 0,
    power: 0,
    permissionIds: [],
};
const editedRole = reactive<EditedRoleData>({ ...initialRoleData });
const cardTitle = computed(() => (editedRole.id ? t('roleView.editRole') : t('roleView.newRole')));

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers & Grouping ---
const roleHeaders = ref([
    { title: t('roleView.roleName'), key: 'name', sortable: true, align: 'start' },
    { title: t('roleView.description'), key: 'description', sortable: false, align: 'start' },
    { title: t('roleView.powerLevel'), key: 'power', sortable: true, align: 'start' },
    { title: t('roleView.sortOrder'), key: 'sort_order', sortable: true, align: 'start' },
    { title: t('roleView.actions'), key: 'actions', sortable: false, align: 'end', width: '100px' },
] as const);

// Headers for the permission tables inside the dialog
const permissionsHeaders = ref([
    { title: t('roleView.name'), key: 'name_alias', sortable: false, width: '30%' },
    { title: t('roleView.description'), key: 'description', sortable: false, width: '55%' },
    { title: t('roleView.assigned'), key: 'actions', sortable: false, align: 'center', width: '15%' },
] as const);

// Initialize composables for permission grouping and dependencies
const selectedPermissionIds = computed(() => editedRole.permissionIds);
const {
    groupPermissions,
    filterGroups,
    filterGroupsBySearch,
    countByFilter,
    hasSelectedAdminPermissions,
    getGroupPermissionIds,
    getSubGroupPermissionIds
} = usePermissionGrouping(computed(() => permissions.value), selectedPermissionIds);

const { applyDependencies, isAutoAdded, formatPermissionName } = usePermissionDependencies(computed(() => permissions.value));

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || t('roleView.fieldRequired');

// --- Change Detection ---
const hasChanges = computed(() => {
    // Check if we're creating a new role
    if (!editedRole.id) {
        return isRoleInfoFormValid.value;
    }
    
    // For existing roles, check if anything has changed
    const permissionsChanged = JSON.stringify([...editedRole.permissionIds].sort()) !== 
                              JSON.stringify([...originalPermissionIds.value].sort());
    const categoriesChanged = JSON.stringify([...selectedCategoryIds.value].sort()) !== 
                             JSON.stringify([...originalCategoryIds.value].sort());
    
    return isRoleInfoFormValid.value && (permissionsChanged || categoriesChanged);
});

const canSave = computed(() => {
    if (!editedRole.id) {
        // Creating new role - basic info must be valid
        return isRoleInfoFormValid.value;
    } else {
        // Editing existing role - basic info must be valid AND something must have changed
        return isRoleInfoFormValid.value && hasChanges.value;
    }
});

// --- Computed Permissions (NEW: unified with filter) ---
const filteredPermissions = computed(() => {
    // Filter out special permissions managed separately
    let filtered = permissions.value.filter(p =>
        p.name !== 'ALL_PERMISSIONS' &&
        p.name !== 'SYSTEM_ADMIN' &&
        p.name !== 'IS_SUSPENDED_GROUP'
    );

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(query) ||
            (p.module_display?.toLowerCase() || p.module.toLowerCase()).includes(query) ||
            (p.action_display?.toLowerCase() || p.action.toLowerCase()).includes(query) ||
            p.description?.toLowerCase().includes(query) ||
            p.module.toLowerCase().includes(query) ||
            p.sub_module?.toLowerCase().includes(query) ||
            p.action.toLowerCase().includes(query)
        );
    }

    return filtered;
});

// Get grouped and filtered permissions
// NEW: Apply search filter AND admin/general filter
const displayedGroups = computed(() => {
    let groups = groupPermissions.value;

    // Apply search filter
    if (searchQuery.value) {
        groups = filterGroupsBySearch(groups, searchQuery.value);
    }

    // Apply admin/general filter
    groups = filterGroups(groups, permissionFilter.value);

    return groups;
});

// Master-Detail: Separate admin and general groups
const generalGroups = computed(() => {
    let groups = groupPermissions.value.filter(g => !g.isAdmin);

    if (searchQuery.value) {
        groups = filterGroupsBySearch(groups, searchQuery.value);
    }

    return groups.sort((a, b) => a.displayGroup.localeCompare(b.displayGroup));
});

const adminGroups = computed(() => {
    let groups = groupPermissions.value.filter(g => g.isAdmin);

    if (searchQuery.value) {
        groups = filterGroupsBySearch(groups, searchQuery.value);
    }

    return groups.sort((a, b) => a.displayGroup.localeCompare(b.displayGroup));
});

// Master-Detail: Selected module details
const selectedModuleDetails = computed(() => {
    if (!selectedModule.value) return null;

    const allGroups = groupPermissions.value;
    return allGroups.find(g => g.module === selectedModule.value) || null;
});

// Master-Detail: Progress for selected module
const selectedModuleProgress = computed(() => {
    const details = selectedModuleDetails.value;
    if (!details) return { selected: 0, total: 0, percentage: 0 };

    return {
        selected: details.selectedCount,
        total: details.totalCount,
        percentage: details.totalCount > 0 ? Math.round((details.selectedCount / details.totalCount) * 100) : 0
    };
});

// Get ALL_PERMISSIONS permission object
const allPermissionsPermission = computed(() =>
    permissions.value.find(p => p.name === 'ALL_PERMISSIONS')
);

// Get IS_SUSPENDED_GROUP permission object
const suspendedGroupPermission = computed(() =>
    permissions.value.find(p => p.name === 'IS_SUSPENDED_GROUP')
);

// Check if role has ALL_PERMISSIONS
const hasAllPermissions = computed({
    get: () => {
        if (!allPermissionsPermission.value) return false;
        return editedRole.permissionIds.includes(allPermissionsPermission.value.id);
    },
    set: (value: boolean) => {
        if (!allPermissionsPermission.value) return;
        const permId = allPermissionsPermission.value.id;
        if (value && !editedRole.permissionIds.includes(permId)) {
            editedRole.permissionIds.push(permId);
        } else if (!value) {
            editedRole.permissionIds = editedRole.permissionIds.filter(id => id !== permId);
        }
    }
});

// Check if role has IS_SUSPENDED_GROUP
const hasSuspendedGroup = computed({
    get: () => {
        if (!suspendedGroupPermission.value) return false;
        return editedRole.permissionIds.includes(suspendedGroupPermission.value.id);
    },
    set: (value: boolean) => {
        if (!suspendedGroupPermission.value) return;
        const permId = suspendedGroupPermission.value.id;
        if (value && !editedRole.permissionIds.includes(permId)) {
            editedRole.permissionIds.push(permId);
        } else if (!value) {
            editedRole.permissionIds = editedRole.permissionIds.filter(id => id !== permId);
        }
    }
});

// --- Bulk Actions ---
const toggleAllInGroup = (groupPermissionIds: number[]) => {
    const allSelected = groupPermissionIds.every(id => editedRole.permissionIds.includes(id));

    if (allSelected) {
        // Deselect all in group
        editedRole.permissionIds = editedRole.permissionIds.filter(id => !groupPermissionIds.includes(id));
    } else {
        // Select all in group
        const newIds = groupPermissionIds.filter(id => !editedRole.permissionIds.includes(id));
        editedRole.permissionIds.push(...newIds);
    }
};

const selectAllPermissions = () => {
    const allIds = filteredPermissions.value.map(p => p.id);
    editedRole.permissionIds = [...new Set([...editedRole.permissionIds, ...allIds])];
};

const deselectAllPermissions = () => {
    const allIds = new Set(filteredPermissions.value.map(p => p.id));
    editedRole.permissionIds = editedRole.permissionIds.filter(id => !allIds.has(id));
};

// Master-Detail: Select/Deselect all in selected module
const selectAllInSelectedModule = () => {
    const details = selectedModuleDetails.value;
    if (!details) return;

    const allIds = getGroupPermissionIds(details);
    editedRole.permissionIds = [...new Set([...editedRole.permissionIds, ...allIds])];
};

const deselectAllInSelectedModule = () => {
    const details = selectedModuleDetails.value;
    if (!details) return;

    const allIds = new Set(getGroupPermissionIds(details));
    editedRole.permissionIds = editedRole.permissionIds.filter(id => !allIds.has(id));
};

// Watch for permission changes and apply dependencies (NEW: with module dependencies)
let isApplyingDependencies = false; // Prevent infinite loops
watch(() => editedRole.permissionIds, (newIds, oldIds) => {
    if (isApplyingDependencies) return;

    isApplyingDependencies = true;
    const result = applyDependencies(newIds, oldIds || []);

    // Only update if there are actual changes
    if (JSON.stringify(result.ids.sort()) !== JSON.stringify(newIds.sort())) {
        // Show notification for auto-added parent permissions
        if (result.added.length > 0) {
            const message = result.added.map(a => `${a.name} (${a.reason})`).join(', ');
            showSnackbar(
                `Benötigte Berechtigungen hinzugefügt: ${message}`,
                'info'
            );
        }

        // Show notification for auto-removed child permissions
        if (result.removed.length > 0) {
            const message = result.removed.map(r => `${r.name} (${r.reason})`).join(', ');
            showSnackbar(
                `Abhängige Berechtigungen entfernt: ${message}`,
                'warning'
            );
        }

        editedRole.permissionIds = result.ids;
    }

    isApplyingDependencies = false;
}, { deep: true });

// --- Data Fetching ---
const fetchData = async <T,>(
    action: string,
    targetRef: Ref<T[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string,
    baseEndpoint: string = '/admin/roles'
) => {
    loadingRef.value = true;
    try {
        // Assume POST is required based on original code for all role actions
        const response = await apiClientAuth.get<any[]>(`${baseEndpoint}?action=${action}`); // Use POST
        targetRef.value = response.data || response.data || [];
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

const fetchAllInitialData = () => {
    loadingRoles.value = true;
    loadingPermissions.value = true;
    loadingRolePermissions.value = true;
    loadingCategories.value = true;
    Promise.all([
        fetchData<Role>('getRoles', roles, loadingRoles, 'Fehler beim Laden der Rollen.'),
        fetchData<Permission>(
            'getPermissions',
            permissions,
            loadingPermissions,
            'Fehler beim Laden der Berechtigungen.'
        ),
        fetchData<RolePermission>(
            'getRolePermissions',
            rolePermissions,
            loadingRolePermissions,
            'Fehler beim Laden der Rollen-Berechtigungen.'
        ),
        fetchData<Category>(
            'getCategories',
            categories,
            loadingCategories,
            'Fehler beim Laden der Berichtskategorien.',
            'report'
        ),
    ]);
};

// --- Helper Methods ---
const isGroupFullySelected = (groupPermissions: Permission[]) => {
    return groupPermissions.every(p => editedRole.permissionIds.includes(p.id));
};

const isGroupPartiallySelected = (groupPermissions: Permission[]) => {
    const selectedCount = groupPermissions.filter(p => editedRole.permissionIds.includes(p.id)).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
};

const getActionIcon = (action: string): string => {
    const icons: Record<string, string> = {
        'read': 'mdi-book-open',
        'write': 'mdi-pencil',
        'delete': 'mdi-delete',
        'view': 'mdi-eye',
        'admin': 'mdi-shield-crown',
        'create': 'mdi-plus-circle',
        'manage': 'mdi-cog',
        'share': 'mdi-share-variant',
        'execute': 'mdi-play-circle'
    };
    return icons[action] || 'mdi-help-circle';
};

const getActionColor = (action: string): string => {
    const colors: Record<string, string> = {
        'read': 'blue',
        'write': 'orange',
        'delete': 'red',
        'view': 'grey',
        'admin': 'purple',
        'create': 'green',
        'manage': 'indigo',
        'share': 'teal',
        'execute': 'cyan'
    };
    return colors[action] || 'grey';
};

// Detail View Openers
const openAddRoleView = () => {
    Object.assign(editedRole, { ...initialRoleData }); // Reset
    selectedCategoryIds.value = [];
    originalPermissionIds.value = [];
    originalCategoryIds.value = [];
    activeTab.value = 0;
    isRoleInfoFormValid.value = false;
    showDetailView.value = true;
    setTimeout(() => roleInfoFormRef.value?.resetValidation(), 100);
};

const openEditRoleView = (role: Role) => {
    const currentPermissionIds = rolePermissions.value
        .filter(rp => Number(rp.role_id) === Number(role.id))
        .map(rp => Number(rp.permission_id))
        .filter(id => !isNaN(id));
        
    Object.assign(editedRole, {
        id: role.id,
        name: role.name,
        description: role.description,
        sort_order: role.sort_order,
        power: role.power,
        permissionIds: [...currentPermissionIds],
    });
    
    // Store original values for change detection
    originalPermissionIds.value = [...currentPermissionIds];

    // Fetch category access for this role
    if (role.id) {
        loadingCategories.value = true;
        apiClientAuth.get(`/admin/roles/?action=getCategoryRoles&roleId=${role.id}`)
            .then(response => {
                // Update categories with the has_role information
                if (response.data.categories) {
                    categories.value = response.data.categories;
                }
                // Set selected category IDs
                if (response.data.categoryIds) {
                    selectedCategoryIds.value = response.data.categoryIds.map((id: number) => Number(id));
                    originalCategoryIds.value = [...selectedCategoryIds.value];
                } else {
                    selectedCategoryIds.value = [];
                    originalCategoryIds.value = [];
                }
            })
            .catch(error => {
                console.error('Error fetching category roles:', error);
                showSnackbar(t('roleView.categoryLoadError'), 'error');
            })
            .finally(() => {
                loadingCategories.value = false;
            });
    }

    activeTab.value = 0;
    isRoleInfoFormValid.value = false;
    showDetailView.value = true;

    // Master-Detail: Auto-select first module and set correct tab
    setTimeout(() => {
        roleInfoFormRef.value?.resetValidation();

        // Select first general group by default
        if (generalGroups.value.length > 0) {
            selectedModule.value = generalGroups.value[0].module;
            masterTab.value = 'general';
        } else if (adminGroups.value.length > 0) {
            selectedModule.value = adminGroups.value[0].module;
            masterTab.value = 'admin';
        }
    }, 100);
};

const closeDetailView = () => {
    showDetailView.value = false;
    // Reset after animation
    setTimeout(() => {
        Object.assign(editedRole, { ...initialRoleData });
        selectedCategoryIds.value = [];
        searchQuery.value = '';
        permissionFilter.value = 'all';
        selectedModule.value = null; // Reset selected module
        masterTab.value = 'general'; // Reset to general tab
    }, 300);
};

// NEU: Funktion zum Öffnen des Lösch-Dialogs
const openDeleteRoleDialog = (role: Role) => {
    deleteRoleDialog.role = role;
    deleteRoleDialog.text = t('roleView.deleteConfirm', { name: `<strong>${role.name}</strong>` });
    deleteRoleDialog.action = confirmDeleteRole; // Verweis auf die Bestätigungsfunktion
    deleteRoleDialog.show = true;
};


const closeDeleteRoleDialog = () => {
    deleteRoleDialog.show = false;
    setTimeout(() => {
        deleteRoleDialog.role = null;
    }, 300); // Kurze Verzögerung, damit der Inhalt nicht sofort verschwindet
};

// Save Role (Add/Edit)
const saveRole = async () => {
    const { valid } = await roleInfoFormRef.value?.validate();
    if (!valid) {
        activeTab.value = 0;
        showSnackbar(t('roleView.fillRequiredFields'), 'warning');
        return;
    }
    savingRole.value = true;
    const payload = {
        id: editedRole.id,
        name: editedRole.name,
        description: editedRole.description,
        sort_order: editedRole.sort_order,
        power: editedRole.power,
        permissions: editedRole.permissionIds,
        categoryIds: selectedCategoryIds.value
    };
    const action = editedRole.id ? 'updateRole' : 'createRole';

    try {
        await apiClientAuth.post(`/admin/roles?action=${action}`, payload);
        closeDetailView();
        await fetchAllInitialData();
        showSnackbar(
            editedRole.id ? t('roleView.roleUpdated') : t('roleView.roleCreated'),
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving role (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || t('roleView.saveError'), 'error');
    } finally {
        savingRole.value = false;
    }
};
const deletingRoleId = ref<number | null>(null);
// NEU: Funktion zum Bestätigen und Ausführen des Löschens
const confirmDeleteRole = async () => {
    if (!deleteRoleDialog.role?.id) return;

    deletingRoleId.value = deleteRoleDialog.role.id; // Setze Loading-Zustand für den Button
    try {
        // API-Aufruf an deine PHP-Funktion (angenommen POST mit JSON-Body)
        const response = await apiClientAuth.post('/admin/roles?action=deleteRole', {
            id: deleteRoleDialog.role.id
        });

        // Erfolg
        closeDeleteRoleDialog();
        showSnackbar(response.data?.message || t('roleView.roleDeleted'), 'success');
        await fetchAllInitialData(); // Rollenliste neu laden

    } catch (error: any) {
        console.error("Error deleting role:", error);
        // Spezifische Fehlermeldung aus der API anzeigen (z.B. bei Konflikt)
        let errorMessage = t('roleView.deleteError');
        if (error.response?.status === 409) { // Konflikt (Rolle noch zugewiesen)
             errorMessage = error.response?.data?.error || t('roleView.deleteErrorAssigned');
        } else if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        }
        showSnackbar(errorMessage, 'error');
        // Optional: Dialog offen lassen bei Fehler? Oder schließen? Hier: schließen.
        closeDeleteRoleDialog();
    } finally {
        deletingRoleId.value = null; // Loading-Zustand zurücksetzen
    }
};

// --- Lifecycle Hooks ---
onMounted(fetchAllInitialData);
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row no-gutters class="fill-height">
            <!-- Roles List - Hidden when detail view is shown -->
            <v-col v-if="!showDetailView" cols="12">
                <v-row class="mb-4 align-center">
                    <v-col cols="auto">
                        <v-btn
                            v-if="canEdit"
                            @click="openAddRoleView"
                            color="primary"
                            variant="elevated"
                            prepend-icon="mdi-shield-plus-outline"
                            elevation="2"
                            class="action-button"
                        >
                            {{ t('roleView.newRoleButton') }}
                        </v-btn>
                    </v-col>
                    <v-col v-if="!showDetailView">
                        <v-alert
                            border="start"
                            border-color="primary"
                            elevation="2"
                            density="comfortable"
                            icon="mdi-information-outline"
                            variant="tonal"
                            class="mt-0 info-alert"
                        >
                            {{ t('roleView.description') }}
                        </v-alert>
                    </v-col>
                </v-row>

                <!-- Haupttabelle -->
                <v-card class="main-card elevation-4">
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-shield-account</v-icon>
                    {{ t('roleView.title') }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
            </v-toolbar>

            <v-divider></v-divider>

            <!-- Filterleiste: Anzahl der Eintraege, wie im Entwurf. -->
            <div class="k-toolbar">
                <span class="k-toolbar__spacer"></span>
                <span class="k-toolbar__count">{{ $t("common.entries", { n: (roles || []).length }) }}</span>
            </div>
            <v-data-table
                :headers="roleHeaders"
                :items="roles"
                class="elevation-0"
                item-value="id"
                :loading="loadingRoles"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span class="font-weight-medium">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.power`]="{ item }">
                    <v-chip size="small" label variant="tonal" color="info">{{
                        item.power
                    }}</v-chip>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-1">
                        <v-tooltip :text="t('edit')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditRoleView(item)"
                                    v-bind="props"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                        <v-tooltip :text="t('delete')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openDeleteRoleDialog(item)"
                                    v-bind="props"
                                    :loading="deletingRoleId === item.id"
                                    color="error"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-delete-outline</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:no-data>
                    <div class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-shield-off-outline</v-icon
                        >
                        <span>{{ t('roleView.noRoles') }}</span>
                    </div>
                </template>

                <template v-slot:loading>
                    <div class="loading-state">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="24"
                            class="mr-2"
                        ></v-progress-circular>
                        <span>{{ t('roleView.loadingRoles') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>
            </v-col>

            <!-- Role Details - Full width when shown -->
            <v-col v-if="showDetailView" cols="12">
            <v-card
                class="dialog-card"
                :loading="savingRole || loadingPermissions || loadingRolePermissions"
            >
                <v-card-title class="dialog-title d-flex align-center">
                    <v-icon
                        :icon="editedRole.id ? 'mdi-shield-edit' : 'mdi-shield-plus'"
                        class="mr-2"
                    ></v-icon>
                    <span>{{ cardTitle }}</span>
                    <v-spacer></v-spacer>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        @click="closeDetailView"
                        size="small"
                    ></v-btn>
                </v-card-title>

                <v-divider></v-divider>

                <v-tabs v-model="activeTab" bg-color="primary" dark grow>
                    <v-tab :value="0">
                        <v-icon start size="small">mdi-information-outline</v-icon>
                        {{ t('roleView.information') }}
                    </v-tab>
                    <v-tab :value="1">
                        <v-icon start size="small">mdi-shield-account</v-icon>
                        Berechtigungen
                    </v-tab>
                    <v-tab :value="2">
                        <v-icon start size="small">mdi-folder-multiple-outline</v-icon>
                        {{ t('roleView.reportCategories') }}
                    </v-tab>
                </v-tabs>

                <v-card-text style="max-height: calc(100vh - 200px); overflow-y: auto;" class="role-dialog-content">
                    <v-form ref="roleInfoFormRef" v-model="isRoleInfoFormValid">
                        <v-window v-model="activeTab">
                            <v-window-item :value="0" class="pa-4">
                                <v-container>
                                    <v-row>
                                        <v-col cols="12">
                                            <v-text-field
                                                :label="t('roleView.roleName')"
                                                v-model="editedRole.name"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="comfortable"
                                                color="primary"
                                                bg-color="grey-darken-3"
                                                prepend-inner-icon="mdi-shield-account"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                :label="t('roleView.description')"
                                                v-model="editedRole.description"
                                                variant="outlined"
                                                density="comfortable"
                                                color="primary"
                                                bg-color="grey-darken-3"
                                                prepend-inner-icon="mdi-text-box-outline"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                :label="t('roleView.powerLevel')"
                                                v-model.number="editedRole.power"
                                                type="number"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="comfortable"
                                                color="primary"
                                                bg-color="grey-darken-3"
                                                prepend-inner-icon="mdi-trending-up"
                                                :hint="t('roleView.powerLevelHint')"
                                                persistent-hint
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field
                                                :label="t('roleView.sortOrder')"
                                                v-model.number="editedRole.sort_order"
                                                type="number"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="comfortable"
                                                color="primary"
                                                bg-color="grey-darken-3"
                                                prepend-inner-icon="mdi-sort"
                                                :hint="t('roleView.sortOrderHint')"
                                                persistent-hint
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-divider class="my-4"></v-divider>
                                            <v-checkbox
                                                v-model="hasAllPermissions"
                                                color="error"
                                                density="comfortable"
                                                hide-details
                                            >
                                                <template v-slot:label>
                                                    <div class="d-flex align-center">
                                                        <v-icon color="error" class="mr-2">mdi-shield-crown</v-icon>
                                                        <span class="font-weight-bold">Administrator (ALL_PERMISSIONS)</span>
                                                    </div>
                                                </template>
                                            </v-checkbox>
                                            <div class="text-caption text-grey ml-8 mt-1">
                                                Dieser Rolle werden automatisch ALLE Rechte gewährt, unabhängig von den einzelnen Berechtigungen
                                            </div>
                                            <v-alert
                                                v-if="hasAllPermissions"
                                                type="warning"
                                                variant="tonal"
                                                density="compact"
                                                class="mt-3"
                                            >
                                                ⚠️ Diese Rolle hat vollständigen Zugriff auf das gesamte System!
                                            </v-alert>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-divider class="my-4"></v-divider>
                                            <v-checkbox
                                                v-model="hasSuspendedGroup"
                                                color="warning"
                                                density="comfortable"
                                                hide-details
                                            >
                                                <template v-slot:label>
                                                    <div class="d-flex align-center">
                                                        <v-icon color="warning" class="mr-2">mdi-account-cancel</v-icon>
                                                        <span class="font-weight-bold">Gesperrte Gruppe (IS_SUSPENDED_GROUP)</span>
                                                    </div>
                                                </template>
                                            </v-checkbox>
                                            <div class="text-caption text-grey ml-8 mt-1">
                                                Mitglieder dieser Gruppe können sich nicht mehr anmelden. Nur Administratoren (ALL_PERMISSIONS) sind davon ausgenommen.
                                            </div>
                                            <v-alert
                                                v-if="hasSuspendedGroup"
                                                type="error"
                                                variant="tonal"
                                                density="compact"
                                                class="mt-3"
                                            >
                                                🚫 Benutzer in dieser Gruppe sind vom System ausgeschlossen!
                                            </v-alert>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </v-window-item>

<v-window-item :value="1" class="pa-0">
    <!-- Master-Detail Layout -->
    <div class="master-detail-container" style="height: 600px; display: flex;">

        <!-- LEFT: Navigation Panel -->
        <div class="master-panel" style="width: 320px; border-right: 1px solid rgba(0,0,0,0.12); display: flex; flex-direction: column;">
            <!-- Search -->
            <div class="pa-4 pb-2">
                <v-text-field
                    v-model="searchQuery"
                    prepend-inner-icon="mdi-magnify"
                    label="Module durchsuchen..."
                    clearable
                    variant="outlined"
                    density="compact"
                    hide-details
                ></v-text-field>
            </div>

            <!-- Tabs for General/Admin -->
            <div class="px-3 pb-2">
                <v-tabs
                    v-model="masterTab"
                    density="compact"
                    color="primary"
                    grow
                >
                    <v-tab value="general" class="text-none">
                        <v-icon icon="mdi-folder-outline" class="mr-1"></v-icon>
                        {{ generalGroups.reduce((sum, g) => sum + g.totalCount, 0) }}
                    </v-tab>
                    <v-tab value="admin" class="text-none">
                        <v-icon icon="mdi-shield-crown" class="mr-1" color="warning"></v-icon>
                        {{ adminGroups.reduce((sum, g) => sum + g.totalCount, 0) }}
                    </v-tab>
                </v-tabs>
            </div>

            <!-- Scrollable Module List -->
            <div class="flex-grow-1" style="overflow-y: auto; overflow-x: hidden;">
                <!-- General Rights Section -->
                <div v-if="masterTab === 'general' && generalGroups.length > 0" class="px-3 pb-2">
                    <v-list density="compact" class="py-0">
                        <v-list-item
                            v-for="group in generalGroups"
                            :key="group.module"
                            :active="selectedModule === group.module"
                            :value="group.module"
                            @click="selectedModule = group.module"
                            class="mb-1"
                            rounded="lg"
                        >
                            <template v-slot:prepend>
                                <v-icon :icon="group.icon" size="small" color="primary"></v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">
                                {{ group.displayGroup }}
                            </v-list-item-title>
                            <template v-slot:append>
                                <v-chip
                                    size="x-small"
                                    :color="group.selectedCount > 0 ? 'primary' : 'default'"
                                    variant="tonal"
                                    class="font-weight-bold"
                                >
                                    {{ group.selectedCount }}/{{ group.totalCount }}
                                </v-chip>
                            </template>
                        </v-list-item>
                    </v-list>
                </div>

                <!-- Admin Rights Section -->
                <div v-if="masterTab === 'admin' && adminGroups.length > 0" class="px-3 pb-2">
                    <v-list density="compact" class="py-0">
                        <v-list-item
                            v-for="group in adminGroups"
                            :key="group.module"
                            :active="selectedModule === group.module"
                            :value="group.module"
                            @click="selectedModule = group.module"
                            class="mb-1"
                            rounded="lg"
                            color="warning"
                        >
                            <template v-slot:prepend>
                                <v-icon :icon="group.icon" size="small" color="warning"></v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">
                                {{ group.displayGroup }}
                            </v-list-item-title>
                            <template v-slot:append>
                                <v-chip
                                    size="x-small"
                                    :color="group.selectedCount > 0 ? 'warning' : 'default'"
                                    variant="tonal"
                                    class="font-weight-bold"
                                >
                                    {{ group.selectedCount }}/{{ group.totalCount }}
                                </v-chip>
                            </template>
                        </v-list-item>
                    </v-list>
                </div>

                <!-- No Results -->
                <div v-if="(masterTab === 'general' && generalGroups.length === 0) || (masterTab === 'admin' && adminGroups.length === 0)" class="text-center pa-8 text-medium-emphasis">
                    <v-icon icon="mdi-magnify" size="48" class="mb-2"></v-icon>
                    <div class="text-body-2">
                        {{ searchQuery ? 'Keine Module gefunden' : 'Keine Berechtigungen verfügbar' }}
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="pa-3 pt-2 border-t">
                <v-btn
                    size="small"
                    variant="tonal"
                    block
                    prepend-icon="mdi-checkbox-multiple-marked"
                    @click="selectAllPermissions"
                >
                    Alle auswählen
                </v-btn>
            </div>
        </div>

        <!-- RIGHT: Detail Panel -->
        <div class="detail-panel flex-grow-1" style="display: flex; flex-direction: column; overflow: hidden;">
            <!-- Empty State -->
            <div v-if="!selectedModuleDetails" class="d-flex align-center justify-center h-100 text-center pa-8">
                <div>
                    <v-icon icon="mdi-cursor-pointer" size="64" color="grey-lighten-1" class="mb-4"></v-icon>
                    <div class="text-h6 text-medium-emphasis mb-2">Modul auswählen</div>
                    <div class="text-body-2 text-disabled">
                        Wähle ein Modul aus der Liste, um Berechtigungen zu verwalten
                    </div>
                </div>
            </div>

            <!-- Detail Content -->
            <div v-else class="d-flex flex-column h-100">
                <!-- Header -->
                <div class="pa-4 pb-3 border-b">
                    <div class="d-flex align-center mb-3">
                        <v-icon
                            :icon="selectedModuleDetails.icon"
                            size="large"
                            :color="selectedModuleDetails.isAdmin ? 'warning' : 'primary'"
                            class="mr-3"
                        ></v-icon>
                        <div class="flex-grow-1">
                            <div class="d-flex align-center">
                                <h3 class="text-h6">{{ selectedModuleDetails.displayGroup }}</h3>
                                <v-chip
                                    v-if="selectedModuleDetails.isAdmin"
                                    size="small"
                                    variant="flat"
                                    color="warning"
                                    class="ml-2"
                                >
                                    🛡️ ADMIN
                                </v-chip>
                            </div>
                            <div class="text-caption text-medium-emphasis">
                                {{ selectedModuleDetails.totalCount }} {{ selectedModuleDetails.totalCount === 1 ? 'Berechtigung' : 'Berechtigungen' }}
                            </div>
                        </div>
                        <v-chip
                            size="large"
                            :color="selectedModuleProgress.selected > 0 ? (selectedModuleDetails.isAdmin ? 'warning' : 'primary') : 'default'"
                            variant="tonal"
                            class="font-weight-bold"
                        >
                            {{ selectedModuleProgress.selected }} / {{ selectedModuleProgress.total }}
                        </v-chip>
                    </div>

                    <!-- Progress Bar -->
                    <v-progress-linear
                        :model-value="selectedModuleProgress.percentage"
                        :color="selectedModuleDetails.isAdmin ? 'warning' : 'primary'"
                        height="8"
                        rounded
                        class="mb-3"
                    ></v-progress-linear>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <v-btn
                            size="small"
                            variant="tonal"
                            prepend-icon="mdi-checkbox-multiple-marked"
                            @click="selectAllInSelectedModule"
                        >
                            Alle aktivieren
                        </v-btn>
                        <v-btn
                            size="small"
                            variant="tonal"
                            prepend-icon="mdi-checkbox-multiple-blank-outline"
                            @click="deselectAllInSelectedModule"
                        >
                            Alle deaktivieren
                        </v-btn>
                    </div>
                </div>

                <!-- Scrollable Permissions List -->
                <div class="flex-grow-1 pa-4 pt-2" style="overflow-y: auto;">
                    <!-- All Permissions in Expansion Panels when there are sub-groups -->
                    <div v-if="selectedModuleDetails.permissions.length > 0 || selectedModuleDetails.subGroups.length > 0">
                        <v-expansion-panels variant="accordion" class="mb-2">
                            <!-- Main Module Permissions (shown as expansion panel if there are sub-groups) -->
                            <v-expansion-panel
                                v-if="selectedModuleDetails.permissions.length > 0 && selectedModuleDetails.subGroups.length > 0"
                                elevation="0"
                                class="mb-2 border"
                                rounded="lg"
                            >
                                <v-expansion-panel-title class="py-2">
                                    <div class="d-flex align-center w-100">
                                        <v-icon icon="mdi-star-outline" size="small" class="mr-2"></v-icon>
                                        <span class="text-body-2 font-weight-medium flex-grow-1">
                                            Basis
                                        </span>
                                        <v-chip
                                            size="x-small"
                                            :color="selectedModuleDetails.permissions.filter(p => editedRole.permissionIds.includes(p.id)).length > 0 ? (selectedModuleDetails.isAdmin ? 'warning' : 'primary') : 'default'"
                                            variant="tonal"
                                            class="font-weight-bold"
                                        >
                                            {{ selectedModuleDetails.permissions.filter(p => editedRole.permissionIds.includes(p.id)).length }} / {{ selectedModuleDetails.permissions.length }}
                                        </v-chip>
                                    </div>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <v-list class="py-0">
                                        <v-list-item
                                            v-for="perm in selectedModuleDetails.permissions"
                                            :key="perm.id"
                                            class="mb-2 px-3"
                                            rounded="lg"
                                        >
                                            <template v-slot:prepend>
                                                <v-icon
                                                    :icon="getActionIcon(perm.action)"
                                                    size="small"
                                                    :color="getActionColor(perm.action)"
                                                    class="mr-2"
                                                ></v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2 font-weight-medium">
                                                {{ perm.action_display || perm.action }}
                                                <v-chip
                                                    v-if="isAutoAdded(perm.id, editedRole.permissionIds)"
                                                    size="x-small"
                                                    variant="tonal"
                                                    color="info"
                                                    class="ml-2"
                                                >
                                                    ✨ Auto
                                                </v-chip>
                                            </v-list-item-title>
                                            <v-list-item-subtitle class="text-caption text-medium-emphasis mt-1">
                                                {{ formatPermissionName(perm) }}
                                                <span v-if="perm.description" class="text-disabled"> · {{ perm.description }}</span>
                                            </v-list-item-subtitle>
                                            <template v-slot:append>
                                                <v-switch
                                                    v-model="editedRole.permissionIds"
                                                    :value="perm.id"
                                                    :color="selectedModuleDetails.isAdmin ? 'warning' : 'primary'"
                                                    density="compact"
                                                    hide-details
                                                    inset
                                                ></v-switch>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </v-expansion-panel-text>
                            </v-expansion-panel>

                            <!-- Main permissions as simple list if NO sub-groups -->
                            <div v-if="selectedModuleDetails.permissions.length > 0 && selectedModuleDetails.subGroups.length === 0" class="mb-4">
                                <v-list class="py-0">
                                    <v-list-item
                                        v-for="perm in selectedModuleDetails.permissions"
                                        :key="perm.id"
                                        class="mb-2 px-3"
                                        rounded="lg"
                                    >
                                        <template v-slot:prepend>
                                            <v-icon
                                                :icon="getActionIcon(perm.action)"
                                                size="small"
                                                :color="getActionColor(perm.action)"
                                                class="mr-2"
                                            ></v-icon>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">
                                            {{ perm.action_display || perm.action }}
                                            <v-chip
                                                v-if="isAutoAdded(perm.id, editedRole.permissionIds)"
                                                size="x-small"
                                                variant="tonal"
                                                color="info"
                                                class="ml-2"
                                            >
                                                ✨ Auto
                                            </v-chip>
                                        </v-list-item-title>
                                        <v-list-item-subtitle class="text-caption text-medium-emphasis mt-1">
                                            {{ formatPermissionName(perm) }}
                                            <span v-if="perm.description" class="text-disabled"> · {{ perm.description }}</span>
                                        </v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="editedRole.permissionIds"
                                                :value="perm.id"
                                                :color="selectedModuleDetails.isAdmin ? 'warning' : 'primary'"
                                                density="compact"
                                                hide-details
                                                inset
                                            ></v-switch>
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </div>

                            <!-- Sub-Modules -->
                            <v-expansion-panel
                                v-for="subGroup in selectedModuleDetails.subGroups"
                                :key="subGroup.subModule"
                                elevation="0"
                                class="mb-2 border"
                                rounded="lg"
                            >
                                <v-expansion-panel-title class="py-2">
                                    <div class="d-flex align-center w-100">
                                        <v-icon icon="mdi-folder-outline" size="small" class="mr-2"></v-icon>
                                        <span class="text-body-2 font-weight-medium flex-grow-1">
                                            {{ subGroup.subModuleDisplay }}
                                        </span>
                                        <v-chip
                                            size="x-small"
                                            :color="subGroup.selectedCount > 0 ? (selectedModuleDetails.isAdmin ? 'warning' : 'primary') : 'default'"
                                            variant="tonal"
                                            class="font-weight-bold"
                                        >
                                            {{ subGroup.selectedCount }} / {{ subGroup.totalCount }}
                                        </v-chip>
                                    </div>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <v-list class="py-0">
                                        <v-list-item
                                            v-for="perm in subGroup.permissions"
                                            :key="perm.id"
                                            class="mb-2 px-3"
                                            rounded="lg"
                                        >
                                            <template v-slot:prepend>
                                                <v-icon
                                                    :icon="getActionIcon(perm.action)"
                                                    size="small"
                                                    :color="getActionColor(perm.action)"
                                                    class="mr-2"
                                                ></v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2 font-weight-medium">
                                                {{ perm.action_display || perm.action }}
                                                <v-chip
                                                    v-if="isAutoAdded(perm.id, editedRole.permissionIds)"
                                                    size="x-small"
                                                    variant="tonal"
                                                    color="info"
                                                    class="ml-2"
                                                >
                                                    ✨ Auto
                                                </v-chip>
                                            </v-list-item-title>
                                            <v-list-item-subtitle class="text-caption text-medium-emphasis mt-1">
                                                {{ formatPermissionName(perm) }}
                                                <span v-if="perm.description" class="text-disabled"> · {{ perm.description }}</span>
                                            </v-list-item-subtitle>
                                            <template v-slot:append>
                                                <v-switch
                                                    v-model="editedRole.permissionIds"
                                                    :value="perm.id"
                                                    :color="selectedModuleDetails.isAdmin ? 'warning' : 'primary'"
                                                    density="compact"
                                                    hide-details
                                                    inset
                                                ></v-switch>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>
                    </div>

                    <!-- Auto-Dependency Info -->
                    <v-alert
                        type="info"
                        variant="tonal"
                        density="compact"
                        class="mt-4"
                        border="start"
                    >
                        <div class="text-caption">
                            <strong>💡 Automatische Abhängigkeiten:</strong><br>
                            • "Schreiben" aktiviert automatisch "Lesen" und "Ansehen"<br>
                            • "Löschen" aktiviert automatisch "Schreiben", "Lesen" und "Ansehen"<br>
                            • Sub-Module aktivieren automatisch das Parent-Modul
                        </div>
                    </v-alert>
                </div>
            </div>
        </div>
    </div>
</v-window-item>


                            <v-window-item :value="2" class="pa-3">
                                <div class="permissions-header mb-3 d-flex align-center">
                                    <v-icon
                                        icon="mdi-folder-multiple-outline"
                                        size="small"
                                        class="mr-2 text-info"
                                    ></v-icon>
                                    <span class="text-subtitle-2 font-weight-medium">
                                        {{ t('roleView.accessToReportCategories') }}
                                    </span>
                                </div>
                                
                                <v-data-table
                                    :headers="[
                                        { title: t('roleView.name'), key: 'name', sortable: true },
                                        { title: t('roleView.title'), key: 'title', sortable: true },
                                        { title: t('roleView.access'), key: 'actions', sortable: false, align: 'center', width: '100px' }
                                    ]"
                                    :items="categories"
                                    item-value="id"
                                    :items-per-page="-1"
                                    hide-default-footer
                                    density="compact"
                                    :loading="loadingCategories"
                                    class="permission-table"
                                >
                                    <template v-slot:[`item.name`]="{ item }">
                                        <div class="d-flex align-center">
                                            <v-icon
                                                icon="mdi-folder-outline"
                                                size="small"
                                                class="mr-2 text-info"
                                            ></v-icon>
                                            {{ item.name }}
                                        </div>
                                    </template>

                                    <template v-slot:[`item.title`]="{ item }">
                                        <span class="text-caption">{{ item.title }}</span>
                                    </template>

                                    <template v-slot:[`item.actions`]="{ item }">
                                        <v-checkbox-btn
                                            v-model="selectedCategoryIds"
                                            :value="item.id"
                                            density="compact"
                                            color="info"
                                            :disabled="loadingCategories"
                                        ></v-checkbox-btn>
                                    </template>

                                    <template v-slot:loading>
                                        <v-progress-linear
                                            indeterminate
                                            color="primary"
                                        ></v-progress-linear>
                                    </template>

                                    <template v-slot:no-data>
                                        <div class="text-center pa-4 text-grey">
                                            {{ t('roleView.noReportCategories') }}
                                        </div>
                                    </template>
                                </v-data-table>
                            </v-window-item>
                        </v-window>
                    </v-form>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDetailView">{{ t('cancel') }}</v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="saveRole"
                        :disabled="!canSave"
                        :loading="savingRole"
                    >
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="deleteRoleDialog.show" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-toolbar :color="deleteRoleDialog.confirmColor" class="dialog-toolbar" density="compact">
                    <v-toolbar-title class="text-subtitle-1">
                         <v-icon icon="mdi-alert-circle-outline" class="mr-2" size="small"></v-icon>
                        {{ deleteRoleDialog.title }}
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                     <v-btn icon="mdi-close" @click="closeDeleteRoleDialog" size="small"></v-btn>
                </v-toolbar>

                 <v-card-text class="pt-4">
                     <span v-html="deleteRoleDialog.text"></span>
                 </v-card-text>

                 <v-divider></v-divider>

                 <v-card-actions class="pa-4">
                     <v-spacer></v-spacer>
                     <v-btn variant="text" @click="closeDeleteRoleDialog" class="mr-2">{{ t('cancel') }}</v-btn>
                     <v-btn
                         :color="deleteRoleDialog.confirmColor"
                         variant="elevated"
                         @click="deleteRoleDialog.action"
                         :loading="!!deletingRoleId"
                         class="action-button"
                     >
                         {{ deleteRoleDialog.confirmText }}
                     </v-btn>
                 </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Main Card */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* Action Icons */
.action-icon {
    opacity: 0.7;
    transition: all 0.2s;
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty and Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    color: var(--v-theme-text-secondary);
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styling */
.dialog-card {
    background-color: var(--k-ink) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

.role-dialog-content {
    background: var(--k-canvas);
}

/* Permission Tables */
.permission-table {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 8px;
    overflow: hidden;
    background: rgba(30, 41, 59, 0.3) !important;
}

.group-header-cell {
    background: rgba(30, 41, 59, 0.6) !important;
    padding: 8px 16px !important;
}

.permission-name {
    font-weight: 500;
}

.permissions-header {
    padding: 8px 12px;
    background: rgba(30, 41, 59, 0.2);
    border-radius: 6px;
    margin-bottom: 12px;
}

/* More compact rows for permission tables */
.permission-table :deep(td) {
    padding: 0 12px !important;
    height: 40px !important;
}

.permission-table :deep(th) {
    height: 44px !important;
    font-size: 0.85rem;
    background: rgba(30, 41, 59, 0.5) !important;
}

.permission-table :deep(.v-data-table-group-header-row td) {
    height: 40px !important;
}

.permission-table :deep(.v-checkbox-btn) {
    height: 36px !important;
    margin-top: -8px;
    margin-bottom: -8px;
}

.permission-table :deep(.v-checkbox-btn .v-label) {
    font-size: 0.85rem;
}

.permission-table :deep(.v-checkbox-btn .v-selection-control__input) {
    font-size: 0.85rem;
}

@media (max-width: 600px) {
    .permission-table {
        font-size: 0.8rem;
    }

    .permission-table :deep(td) {
        padding: 0 8px !important;
    }
}

/* New styles for improved permissions layout */
.permissions-grid {
    max-height: 60vh;
    overflow-y: auto;
}

.permission-group {
    margin-bottom: 8px;
    background: rgba(30, 41, 59, 0.3) !important;
    border: 1px solid var(--k-line);
}

.permission-group :deep(.v-expansion-panel-title) {
    min-height: 48px;
    padding: 12px 16px;
    background: rgba(30, 41, 59, 0.5) !important;
    text-align: left !important;
}

.permission-group :deep(.v-expansion-panel-title__overlay) {
    display: none;
}

.permission-group :deep(.v-expansion-panel-title:hover) {
    background: rgba(30, 41, 59, 0.7) !important;
}

.permission-group :deep(.v-expansion-panel-text__wrapper) {
    padding: 0;
}

.permission-list {
    background: transparent !important;
}

.permission-item {
    border-bottom: 1px solid var(--k-line);
}

.permission-item:last-child {
    border-bottom: none;
}

.permission-item:hover {
    background: var(--k-row-hover) !important;
}

/* Fullscreen dialog adjustments */
.v-dialog--fullscreen .role-dialog-content {
    max-height: calc(100vh - 200px) !important;
}

.gap-2 {
    gap: 8px;
}

/* Master-Detail Layout Utilities */
.border-t {
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.border-b {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.border {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.master-detail-container {
    background-color: rgb(var(--v-theme-surface));
}

.master-panel {
    background-color: rgb(var(--v-theme-surface));
}

.detail-panel {
    background-color: rgb(var(--v-theme-surface));
}

.h-100 {
    height: 100%;
}
</style>
