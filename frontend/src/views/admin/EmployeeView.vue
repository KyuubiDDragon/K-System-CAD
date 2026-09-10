<script setup lang="ts">
import { ref, onMounted, reactive, computed, type Ref } from 'vue';
import { useRoute } from 'vue-router'; // Import useRoute if permissions are needed
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { EmployeeCompany, EmployeeDepartment } from '@/types/Members'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useAuthStore } from '@/stores/auth'; // Import auth store
import { useI18n } from 'vue-i18n';
import { useModulePermission } from '@/composables/useModulePermission';

// --- Define Interfaces (if not fully covered by import) ---
interface ItemFormData {
    id?: number | null;
    name: string;
    sort_order: number | null;
}

interface JobRole {
    id: number;
    name: string;
}

// --- Router & Permissions ---
const route = useRoute();
const authStore = useAuthStore();
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

console.log('Admin Employee View props from desktop window:', {
  canEdit: props.canEdit,
  canDelete: props.canDelete,
  'meta.canEdit': props.meta?.canEdit,
  'meta.canDelete': props.meta?.canDelete,
  allPermissions: props.allPermissions,
  isDesktopWindow: props.desktopWindow
});

// --- Permissions ---
const { hasAllPermissions } = useModulePermission();
const isAdmin = computed(() =>
  hasAllPermissions.value || props.allPermissions
);

const canEdit = computed(() => 
  isAdmin.value || 
  props.canEdit || 
  props.meta?.canEdit || 
  !!route.meta?.canEdit || 
  true // Default to true for admin view
);

const canDelete = computed(() => 
  isAdmin.value || 
  props.canDelete || 
  props.meta?.canDelete || 
  !!route.meta?.canDelete || 
  true // Default to true for admin view
);

// --- Component State ---
const companies = ref<EmployeeCompany[]>([]);
const departments = ref<EmployeeDepartment[]>([]);
const jobRoles = ref<JobRole[]>([]); // Add job roles state
const loadingCompanies = ref(false);
const loadingDepartments = ref(false);
const loadingJobRoles = ref(false); // Add loading state for job roles
const savingItem = ref(false);
const deletingItem = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const confirmDeleteDialog = ref(false);
const itemFormRef = ref<any>(null);
const isItemFormValid = ref(false);
const initialFormData: ItemFormData = { id: null, name: '', sort_order: 0 };
const selectedItem = reactive<ItemFormData>({ ...initialFormData });
const itemType = ref<'company' | 'department' | 'jobrole' | null>(null); // Add jobrole to possible types
const itemToDelete = ref<{
    item: EmployeeCompany | EmployeeDepartment | JobRole;
    type: 'company' | 'department' | 'jobrole';
} | null>(null);
const isEditing = computed(() => !!selectedItem.id);
const addEditTitle = computed(() => {
  if (!itemType.value) return '';
  const typeKey =
    itemType.value === 'company'
      ? 'company'
      : itemType.value === 'department'
        ? 'department'
        : 'jobrole';
  return isEditing.value
    ? t(`employeeView.edit${typeKey.charAt(0).toUpperCase() + typeKey.slice(1)}`)
    : t(`employeeView.add${typeKey.charAt(0).toUpperCase() + typeKey.slice(1)}`);
});

const deleteConfirmText = computed(() => {
  if (!itemToDelete.value) return '';
  const typeLabel =
    itemToDelete.value.type === 'company'
      ? t('employeeView.company')
      : itemToDelete.value.type === 'department'
        ? t('employeeView.department')
        : t('employeeView.jobrole');
  return t('employeeView.deleteConfirm', {
    type: typeLabel,
    name: itemToDelete.value.item.name,
  });
});

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const baseHeaders = [
    { title: t('adminEmployee.headers.id'), key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: t('adminEmployee.headers.name'), key: 'name', sortable: true },
    { title: t('adminEmployee.headers.sortOrder'), key: 'sort_order', sortable: true, width: '120px' },
    { title: t('adminEmployee.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const;
const companyHeaders = ref(baseHeaders);
const departmentHeaders = ref(baseHeaders);
// Custom headers for job roles (only name and actions)
const jobRoleHeaders = computed(() => [
    { title: t('adminEmployee.headers.name'), key: 'name', sortable: true },
    { title: t('adminEmployee.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
]);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || 'Name ist erforderlich.';

// --- Data Fetching ---
const fetchData = async (
    action: string,
    targetRef: Ref<any[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string
) => {
    loadingRef.value = true;
    try {
        // Assuming endpoint structure like 'admin/employee?action=...'
        const response = await apiClientAuth.get<any[]>(
            `/admin/employee?action=${action}`
        );
        // Ensure we always have an array before sorting
        const data = Array.isArray(response.data) ? response.data : [];
        targetRef.value = data.sort(
            (a, b) => (a.sort_order ?? 999) - (b.sort_order ?? 999)
        ); // Sort by sort_order
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

const fetchCompanies = () =>
    fetchData('getCompanies', companies, loadingCompanies, 'Fehler beim Laden der Company.');
const fetchDepartments = () =>
    fetchData(
        'getDepartments',
        departments,
        loadingDepartments,
        'Fehler beim Laden der Abteilungen.'
    );
const fetchJobRoles = () =>
    fetchData(
        'getJobRoles',
        jobRoles,
        loadingJobRoles,
        'Fehler beim Laden der Jobrollen.'
    );

const fetchAllInitialData = () => {
    fetchCompanies();
    fetchDepartments();
    fetchJobRoles(); // Add fetch for job roles
};

// --- Methods ---

// Add/Edit Dialog Logic
const openAddItemDialog = (type: 'company' | 'department' | 'jobrole') => {
    Object.assign(selectedItem, { ...initialFormData, id: null }); // Reset form
    itemType.value = type;
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openEditItemDialog = (
    item: EmployeeCompany | EmployeeDepartment | JobRole,
    type: 'company' | 'department' | 'jobrole'
) => {
    Object.assign(selectedItem, { ...item }); // Load data
    itemType.value = type;
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const closeAddEditDialog = () => {
    addEditDialog.value = false;
    // itemType.value = null; // Keep type until save/delete potentially needs it? Or clear here? Clearing is safer.
    // Object.assign(selectedItem, { ...initialFormData, id: null });
};

const saveItem = async () => {
    if (!isItemFormValid.value || !itemType.value) return;
    savingItem.value = true;

    const action = itemType.value === 'company'
            ? 'saveCompany'
            : itemType.value === 'department'
            ? 'saveDepartment'
            : 'saveJobRole'; // Add job role save action

    const payload = { ...selectedItem };
    
    // Remove sort_order field for jobroles if it's not needed
    if (itemType.value === 'jobrole') {
        delete payload.sort_order;
    }

    try {
        // Assume saveCompany/saveDepartment handles both add (id=null) and edit (id!=null)
        // Or use addCompany/addDepartment if needed
        await apiClientAuth.post(`/admin/employee?action=${action}`, payload);

        closeAddEditDialog();
        showSnackbar(
            `${
                itemType.value === 'company' 
                    ? 'Company' 
                    : itemType.value === 'department' 
                    ? 'Abteilung' 
                    : 'Jobrolle'
            } erfolgreich gespeichert.`,
            'success'
        );

        // Refresh the correct list
        if (itemType.value === 'company') {
            await fetchCompanies();
        } else if (itemType.value === 'department') {
            await fetchDepartments();
        } else {
            await fetchJobRoles();
        }
    } catch (error: any) {
        console.error(`Error saving ${itemType.value}:`, error);
        showSnackbar(
            error.response?.data?.error || `Fehler beim Speichern (${itemType.value}).`,
            'error'
        );
    } finally {
        savingItem.value = false;
    }
};

// Delete Confirmation Logic
const openConfirmDeleteDialog = (
    item: EmployeeCompany | EmployeeDepartment | JobRole,
    type: 'company' | 'department' | 'jobrole'
) => {
    itemToDelete.value = { item, type };
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    itemToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!itemToDelete.value) return;

    deletingItem.value = true;
    const { item, type } = itemToDelete.value;
    const action = type === 'company' 
        ? 'deleteCompany' 
        : type === 'department' 
        ? 'deleteDepartment' 
        : 'deleteJobRole'; // Add job role delete action

    try {
        await apiClientAuth.post(`/admin/employee?action=${action}`, { id: item.id });
        closeConfirmDeleteDialog();
        showSnackbar(
            `${
                type === 'company' 
                    ? 'Company' 
                    : type === 'department' 
                    ? 'Abteilung' 
                    : 'Jobrolle'
            } erfolgreich gelöscht.`,
            'success'
        );

        // Refresh the correct list
        if (type === 'company') {
            await fetchCompanies();
        } else if (type === 'department') {
            await fetchDepartments();
        } else {
            await fetchJobRoles();
        }
    } catch (error: any) {
        console.error(`Error deleting ${type}:`, error);
        showSnackbar(error.response?.data?.error || `Fehler beim Löschen (${type}).`, 'error');
    } finally {
        deletingItem.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchAllInitialData();
});
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-domain-plus" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">Company, Abteilungen & Jobrollen</h1>
                </div>
            </v-col>
            <v-col>
                <v-alert
                    border="start"
                    border-color="primary"
                    elevation="2"
                    density="comfortable"
                    icon="mdi-information-outline"
                    variant="tonal"
                    class="mt-0 info-alert"
                >
                    Hier können Sie Company, Abteilungen und Jobrollen verwalten, die den Mitarbeitern zugeordnet werden können.
                </v-alert>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="4">
                <v-card class="main-card elevation-4">
                    <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                        <v-toolbar-title class="text-h6 d-flex align-center">
                            <v-icon start size="20" class="mr-2">mdi-domain</v-icon>
                            Company
                            <v-tooltip location="top" max-width="300">
                                <template v-slot:activator="{ props }">
                                    <v-icon 
                                        size="18" 
                                        class="ms-2 text-medium-emphasis" 
                                        v-bind="props"
                                        icon="mdi-information-outline"
                                    ></v-icon>
                                </template>
                                <span>Eine Company könnte hier "Leitung" sein.</span>
                            </v-tooltip>
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="canEdit"
                            @click="openAddItemDialog('company')"
                            color="primary"
                            variant="elevated"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            Neue Company
                        </v-btn>
                    </v-toolbar>
                    
                    <v-divider></v-divider>
                    
                    <!-- Filterleiste: Anzahl der Eintraege, wie im Entwurf. -->
                    <div class="k-toolbar">
                        <span class="k-toolbar__spacer"></span>
                        <span class="k-toolbar__count">{{ $t("common.entries", { n: (companies || []).length }) }}</span>
                    </div>
                    <v-data-table
                        :headers="companyHeaders"
                        :items="companies"
                        item-value="id"
                        class="elevation-0"
                        :loading="loadingCompanies"
                        density="comfortable"
                        hover
                    >
                        <template v-slot:[`item.id`]="{ item }">
                            <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                                {{ item.id }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.name`]="{ item }">
                            <span class="font-weight-medium">{{ item.name }}</span>
                        </template>
                        
                        <template v-slot:[`item.sort_order`]="{ item }">
                            <span class="text-grey">{{ item.sort_order }}</span>
                        </template>
                        
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-1">
                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditItemDialog(item, 'company')"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-pencil</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                                
                                <v-tooltip text="Löschen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canDelete"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openConfirmDeleteDialog(item, 'company')"
                                            v-bind="props"
                                            color="error"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-delete</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </template>
                        
                        <template v-slot:no-data>
                            <div class="empty-state">
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-domain-off</v-icon>
                                <span>Keine Company gefunden.</span>
                            </div>
                        </template>
                        
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>Lade Company...</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>

            <v-col cols="12" md="4">
                <v-card class="main-card elevation-4">
                    <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                        <v-toolbar-title class="text-h6 d-flex align-center">
                            <v-icon start size="20" class="mr-2">mdi-office-building</v-icon>
                            Abteilungen
                            <v-tooltip location="top" max-width="300">
                                <template v-slot:activator="{ props }">
                                    <v-icon 
                                        size="18" 
                                        class="ms-2 text-medium-emphasis" 
                                        v-bind="props"
                                        icon="mdi-information-outline"
                                    ></v-icon>
                                </template>
                                <span>Eine Abteilung ist eine Abteilung in einer Company.</span>
                            </v-tooltip>
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="canEdit"
                            @click="openAddItemDialog('department')"
                            color="primary"
                            variant="elevated"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            Neue Abteilung
                        </v-btn>
                    </v-toolbar>
                    
                    <v-divider></v-divider>
                    
                    <v-data-table
                        :headers="departmentHeaders"
                        :items="departments"
                        item-value="id"
                        class="elevation-0"
                        :loading="loadingDepartments"
                        density="comfortable"
                        hover
                    >
                        <template v-slot:[`item.id`]="{ item }">
                            <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                                {{ item.id }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.name`]="{ item }">
                            <span class="font-weight-medium">{{ item.name }}</span>
                        </template>
                        
                        <template v-slot:[`item.sort_order`]="{ item }">
                            <span class="text-grey">{{ item.sort_order }}</span>
                        </template>
                        
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-1">
                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditItemDialog(item, 'department')"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-pencil</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                                
                                <v-tooltip text="Löschen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canDelete"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openConfirmDeleteDialog(item, 'department')"
                                            v-bind="props"
                                            color="error"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-delete</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </template>
                        
                        <template v-slot:no-data>
                            <div class="empty-state">
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-office-building-off</v-icon>
                                <span>Keine Abteilungen gefunden.</span>
                            </div>
                        </template>
                        
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>Lade Abteilungen...</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>

            <!-- New Job Roles Section -->
            <v-col cols="12" md="4">
                <v-card class="main-card elevation-4">
                    <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                        <v-toolbar-title class="text-h6 d-flex align-center">
                            <v-icon start size="20" class="mr-2">mdi-account-tie</v-icon>
                            Jobrollen
                            <v-tooltip location="top" max-width="300">
                                <template v-slot:activator="{ props }">
                                    <v-icon 
                                        size="18" 
                                        class="ms-2 text-medium-emphasis" 
                                        v-bind="props"
                                        icon="mdi-information-outline"
                                    ></v-icon>
                                </template>
                                <span>Eine Jobrole ist der Hauptreiter. Hier könnte man zwischen Operative und Administrative unterscheiden. (Wird bei Bewerbungsfragen, Bewerbern usw.) darunter unterteilt</span>
                            </v-tooltip>
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="canEdit"
                            @click="openAddItemDialog('jobrole')"
                            color="primary"
                            variant="elevated"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            Neue Jobrolle
                        </v-btn>
                    </v-toolbar>
                    
                    <v-divider></v-divider>
                    
                    <v-data-table
                        :headers="jobRoleHeaders"
                        :items="jobRoles"
                        item-value="id"
                        class="elevation-0"
                        :loading="loadingJobRoles"
                        density="comfortable"
                        hover
                    >
                        <template v-slot:[`item.name`]="{ item }">
                            <span class="font-weight-medium">{{ item.name }}</span>
                        </template>
                        
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-1">
                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditItemDialog(item, 'jobrole')"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-pencil</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                                
                                <v-tooltip text="Löschen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canDelete"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openConfirmDeleteDialog(item, 'jobrole')"
                                            v-bind="props"
                                            color="error"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-delete</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </template>
                        
                        <template v-slot:no-data>
                            <div class="empty-state">
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-account-tie-off</v-icon>
                                <span>Keine Jobrollen gefunden.</span>
                            </div>
                        </template>
                        
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>Lade Jobrollen...</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="addEditDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon
                        :icon="isEditing 
                            ? (itemType === 'company' 
                                ? 'mdi-domain-edit' 
                                : itemType === 'department' 
                                ? 'mdi-office-building-edit'
                                : 'mdi-account-tie-voice')
                            : (itemType === 'company' 
                                ? 'mdi-domain-plus' 
                                : itemType === 'department' 
                                ? 'mdi-office-building-plus'
                                : 'mdi-account-tie-plus')"
                        class="mr-2"
                    ></v-icon>
                    {{ addEditTitle }}
                </v-card-title>
                
                <v-card-text class="pa-4">
                    <v-form ref="itemFormRef" v-model="isItemFormValid">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedItem.name"
                                        label="Name"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        :prepend-inner-icon="
                                            itemType === 'company' 
                                            ? 'mdi-domain' 
                                            : itemType === 'department' 
                                            ? 'mdi-office-building' 
                                            : 'mdi-account-tie'
                                        "
                                    ></v-text-field>
                                </v-col>
                                
                                <v-col cols="12" v-if="itemType !== 'jobrole'">
                                    <v-text-field
                                        v-model.number="selectedItem.sort_order"
                                        label="Sortierung"
                                        type="number"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-sort"
                                        hint="Bestimmt die Anzeigereihenfolge"
                                        persistent-hint
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-form>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeAddEditDialog">{{ t('cancel') }}</v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="saveItem"
                        :disabled="!isItemFormValid"
                        :loading="savingItem"
                    >
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ t('employeeView.deleteTitle') }}
                </v-card-title>
                
                <v-card-text class="pt-4">
                    <p>
                        {{ deleteConfirmText }}
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2">{{ t('cancel') }}</v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingItem"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>
/* Main Container */
.employee-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

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
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* ID Chip */
.id-chip {
    min-width: 36px;
    justify-content: center;
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
.empty-state, .loading-state {
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

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.delete-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

/* Animation effects */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .main-card {
        margin-bottom: 16px;
    }
}
</style>