<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick, type Ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import api from '@/api';
import draggable from 'vuedraggable'; // Use vuedraggable
import { useToast } from 'vue-toastification'; // Toastification
import { useI18n } from 'vue-i18n';

// Import Components
import MemberCard from '@/components/cards/MemberCard.vue'; // Adjust path
import EmployeeForm from '@/components/cards/EmployeeForm.vue'; // Adjust path
import EmployeeQuickPreview from '@/components/employee/EmployeeQuickPreview.vue';
import EmployeeTableView from '@/components/employee/EmployeeTableView.vue';
import EmployeeCardMini from '@/components/employee/EmployeeCardMini.vue';

// Import Types
import type {
    Rank,
    Company,
    Department,
    License,
    JobTypes,
} from '@/types/Members'; // Adjust path

// Define EmployeeWithRank type that's missing from imported types
interface EmployeeWithRank {
    employee: {
        id: number;
        name: string;
        servicenumber?: string;
        rank_id?: number;
        rankId?: number;
        companies?: Company[] | Record<string, any>;
        departments?: Department[] | Record<string, any>;
        licenses?: License[] | Record<string, any>;
        is_terminated?: boolean;
        leavedate?: string;
        notes?: string;
        [key: string]: any;
    };
    rank?: Rank;
}

// Define JobType interface directly
interface JobType {
    id: number;
    name: string;
    // Add other properties as needed based on actual usage
}
import type { TrainingAssign, Training } from '@/types/Training'; // Adjust path

// --- Router & Permissions ---
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: string | number
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);

// --- Component State ---
const search = ref('');
const employeeData = ref<Rank[]>([]); // Holds raw data structured by rank from fetchEmployee
const companies = ref<Company[]>([]);
const departments = ref<Department[]>([]);
const ranks = ref<Rank[]>([]); // Flat list of all ranks for dropdowns etc.
const licenses = ref<License[]>([]);
const JobTypes = ref<JobType[]>([]); // Job types / main departments
const Trainings = ref<Training[]>([]);
const TrainingAssigns = ref<TrainingAssign[]>([]);
const loadingData = ref(false);
const savingRank = ref(false);
const deletingRank = ref(false);
const savingSort = ref(false);

// Filtering & Display State
const selectedDepartment = ref<number | null>(0); // Default to 'All Departments' option
const showIsTerminated = ref(false); // Toggle for terminated employees
const showPreview = ref(false); // Toggle for MemberCard preview

// View Mode State ('cards' or 'table')
const viewMode = ref<'cards' | 'table'>('cards');

// Selected employee for Quick Preview Sidebar
const selectedEmployee = ref<any | null>(null);
const selectedEmployeeRank = ref<Rank | null>(null);

// Define interface for the EmployeeForm component instance
interface EmployeeFormInstance {
    openDialog: (employeeData: EmployeeWithRank | null, isNew: boolean) => void;
    companies?: Company[];
    departments?: Department[];
    licenses?: License[];
    ranks?: Rank[];
    // Add other methods as needed
}

// --- Child Component Ref ---
const employeeFormRef = ref<EmployeeFormInstance | null>(null);

// --- Dialog States & Data ---
const addRankDialog = ref(false);
const deleteRankDialog = ref(false);
const showSortingModal = ref(false);
const addRankFormRef = ref<any>(null);
const isAddRankFormValid = ref(false);
const newRank = reactive({ name: '', department: 1 }); // Default department ID for new rank
const rankToDelete = ref<Rank | null>(null);
const draggableRanks = ref<Rank[]>([]); // Separate list for draggable modal

// --- Inline Editing State ---
const editingField = ref<{ type: string; id: number }>({ type: '', id: -1 });
const editedValue = ref('');
const editFieldRef = ref<any>(null); // Ref for the inline text field

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || 'Feld ist erforderlich.';

// --- Computed Properties ---

// Options for the department filter select, adding an "All" option
const jobTypeOptions = computed(() => {
    return [
        { id: 0, name: 'Alle Departments' }, // Option for showing all
        ...JobTypes.value,
    ];
});

// Filter and structure employee data based on selected filters
const filteredRanksWithEmployees = computed(() => {
    const currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);
    const searchTermLower = search.value.toLowerCase();

    return (
        employeeData.value
            // 1. Filter Ranks by selected Department (if any selected other than 'All')
            .filter(
                rank =>
                    selectedDepartment.value === 0 || rank.jobrole_id === selectedDepartment.value
            )
            .map(rank => ({
                ...rank,
                // 2. Filter Employees within each rank
                employees: rank.employees.filter(employee => {
                    // Termination status filter
                    const isActive =
                        !employee.is_terminated ||
                        (employee.leavedate && new Date(employee.leavedate) >= currentDate);
                    const matchesTerminationFilter = showIsTerminated.value ? !isActive : isActive; // Show terminated OR active based on toggle

                    // Search term filter
                    const matchesSearch =
                        !searchTermLower ||
                        employee.name.toLowerCase().includes(searchTermLower) ||
                        employee.servicenumber.toString().includes(searchTermLower);

                    return matchesTerminationFilter && matchesSearch;
                }),
            }))
            // 3. Optionally remove ranks with no employees after filtering (uncomment if needed)
            // .filter(rank => rank.employees.length > 0)
            // 4. Sort ranks by their sort_order
            .sort((a, b) => (a.sort_order ?? 999) - (b.sort_order ?? 999))
    );
});

/**
 * Besetzte Raenge. Unbesetzte bekommen keinen eigenen Abschnitt mehr - vorher
 * belegte jeder von ihnen die volle Breite, sodass sechs Rangueberschriften und
 * vier Personen einen ganzen Bildschirm fuellten.
 */
const besetzteRaenge = computed(() =>
    filteredRanksWithEmployees.value.filter(rank => rank.employees.length > 0)
);

/** Unbesetzte Raenge - zusammengefasst in einer Zeile statt einzeln. */
const unbesetzteRaenge = computed(() =>
    filteredRanksWithEmployees.value.filter(rank => rank.employees.length === 0)
);

/** Blendet die unbesetzten Raenge auf Wunsch wieder einzeln ein. */
const zeigeUnbesetzteRaenge = ref(false);

const sichtbareRaenge = computed(() =>
    zeigeUnbesetzteRaenge.value ? filteredRanksWithEmployees.value : besetzteRaenge.value
);

// --- Data Fetching ---
const fetchData = async <T,>(
    action: string,
    targetRef: Ref<T[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string,
    mapFn?: (item: any) => T,
    baseEndpoint = 'employee'
) => {
    loadingRef.value = true; // Use the passed loading ref
    try {
        // Adjust endpoint path construction
        let url = '';
        if (baseEndpoint === 'employee/index_fetch.php') {
            url = `employee/index_fetch.php?action=${action}`;
        } else if (baseEndpoint === 'training') {
            url = `training?action=${action}`; // Assume GET for training data
        } else {
            url = `${baseEndpoint}?action=${action}`; // Assume GET for others unless POST needed
        }

        // Use GET for fetching lists typically
        const response = await apiClientAuth.get<any[]>(url); // Use GET
        const data = response.data || response.data || [];
        targetRef.value = mapFn ? data.map(mapFn) : data;
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

const fetchEmployeeData = () =>
    fetchData<Rank>(
        'getEmployee',
        employeeData,
        loadingData,
        'Fehler beim Laden der Mitarbeiterdaten.'
    );
const fetchCompaniesData = () =>
    fetchData<Company>('getCompanies', companies, loadingData, 'Fehler beim Laden der Firmen.');
const fetchDepartmentsData = () =>
    fetchData<Department>(
        'getDepartments',
        departments,
        loadingData,
        'Fehler beim Laden der Abteilungen.'
    );
const fetchRanksData = () =>
    fetchData<Rank>('getRanks', ranks, loadingData, 'Fehler beim Laden der Ränge.');
const fetchLicensesData = () =>
    fetchData<License>('getLicenses', licenses, loadingData, 'Fehler beim Laden der Lizenzen.');
const fetchJobTypesData = () =>
    fetchData<JobType>(
        'getJobTypes',
        JobTypes,
        loadingData,
        'Fehler beim Laden der Jobtypen.',
        undefined,
        'employee'
    );
const fetchTrainingsData = () =>
    fetchData<Training>(
        'getTrainings',
        Trainings,
        loadingData,
        'Fehler beim Laden der Trainings.',
        item => ({ ...item }),
        'training'
    );
const fetchTrainingAssignsData = () =>
    fetchData<TrainingAssign>(
        'getTrainingAssigns',
        TrainingAssigns,
        loadingData,
        'Fehler beim Laden der Trainingszuweisungen.',
        item => ({ ...item }),
        'training'
    );

const fetchAllInitialData = async () => {
    loadingData.value = true; // Single loading indicator
    await Promise.all([
        fetchJobTypesData(),
        fetchEmployeeData(), // Fetches employees grouped by rank
        fetchCompaniesData(),
        fetchDepartmentsData(),
        fetchRanksData(), // Fetches flat list of ranks
        fetchLicensesData(),
        fetchTrainingsData(),
        fetchTrainingAssignsData(),
    ]);
    loadingData.value = false;
};

// --- Methods ---

// Employee Form Interaction
const openAddEmployeeDialog = async () => {
    try {
        // Add debugging
        console.log('Opening add employee dialog');
        console.log('employeeFormRef:', employeeFormRef.value);
        
        // Wait a tick to ensure component is mounted
        await nextTick();
        
        if (employeeFormRef.value) {
            console.log('Methods available:', Object.keys(employeeFormRef.value));
            
            // Check if openDialog exists and is a function
            if (typeof employeeFormRef.value.openDialog === 'function') {
                employeeFormRef.value.openDialog(null, true);
                console.log('Add dialog opened successfully');
            } else {
                console.error('openDialog is not available or not a function', employeeFormRef.value);
                showSnackbar('Fehler beim Öffnen des Formulars', 'error');
                
                // Try again after a delay
                setTimeout(() => {
                    if (employeeFormRef.value && typeof employeeFormRef.value.openDialog === 'function') {
                        employeeFormRef.value.openDialog(null, true);
                        console.log('Add dialog opened on retry');
                    }
                }, 500);
            }
        } else {
            console.error('Employee form reference is not available');
            showSnackbar('Fehler beim Öffnen des Formulars', 'error');
        }
    } catch (error) {
        console.error('Error in openAddEmployeeDialog:', error);
        showSnackbar('Fehler beim Öffnen des Formulars', 'error');
    }
};

// Helper function to convert object properties to arrays of IDs
const idsToArray = (obj: Record<string, any>) => {
    // If obj is an array of objects with id property, extract those ids
    if (Array.isArray(obj)) {
        return obj.map(item => item.id);
    }
    // If obj is a record with numeric keys
    return obj ? Object.keys(obj).map(key => parseInt(key)) : [];
};

const openEditEmployeeDialog = async (data: EmployeeWithRank) => {
    try {
        console.log('Opening edit employee dialog with data:', data);
        
        // Wait a tick to ensure component is mounted
        await nextTick();
        
        if (data && data.employee) {
            const employeeData = { ...data.employee };
            
            // Convert departments, companies and licenses to ID arrays for the form
            // Note: We're keeping the property names as "company" and "department" to match
            // the form field expectations, although they are arrays
            employeeData.company = Array.isArray(employeeData.companies) 
                ? employeeData.companies.map((c: Company) => c.id) 
                : (employeeData.companies ? idsToArray(employeeData.companies) : []);
                
            employeeData.department = Array.isArray(employeeData.departments) 
                ? employeeData.departments.map((d: Department) => d.id) 
                : (employeeData.departments ? idsToArray(employeeData.departments) : []);
                
            employeeData.license = Array.isArray(employeeData.licenses) 
                ? employeeData.licenses.map((l: License) => l.id) 
                : (employeeData.licenses ? idsToArray(employeeData.licenses) : []);
                
            // Set rank ID correctly
            employeeData.rankId = employeeData.rank_id || 0;
            
            console.log('Prepared employee data:', employeeData);
            
            // Open the dialog with the properly formatted employee data
            if (employeeFormRef.value) {
                // Das Formular erwartet die Daten direkt, nicht in einem employee-Objekt
                if (typeof employeeFormRef.value.openDialog === 'function') {
                    employeeFormRef.value.openDialog(employeeData, false);
                    console.log('Edit dialog opened successfully');
                } else {
                    console.error('openDialog method not available');
                    showSnackbar('Fehler beim Öffnen des Formulars', 'error');
                }
            } else {
                console.error('Employee form reference not available');
                showSnackbar('Fehler beim Öffnen des Formulars', 'error');
            }
        } else {
            console.error('Invalid employee data provided');
            showSnackbar('Fehler beim Öffnen des Mitarbeiterformulars', 'error');
        }
    } catch (error) {
        console.error('Error in openEditEmployeeDialog:', error);
        showSnackbar('Fehler beim Öffnen des Mitarbeiterformulars', 'error');
    }
};

const handleEmployeeFormClose = () => {
    // Logic after the EmployeeForm dialog closes, e.g., refetch data
    // fetchEmployeeData(); // Already handled by @updateEmployeeList event
};

// Rank CRUD & Sorting
const openAddRankDialog = () => {
    Object.assign(newRank, { name: '', department: selectedDepartment.value || 1 }); // Reset form, default department
    isAddRankFormValid.value = false;
    addRankDialog.value = true;
    setTimeout(() => addRankFormRef.value?.resetValidation(), 100);
};
const closeAddRankDialog = () => (addRankDialog.value = false);

const addNewRank = async () => {
    if (!isAddRankFormValid.value) return;
    savingRank.value = true;
    try {
        await apiClientAuth.post('/employee?action=addRank', newRank); // Adjust path
        await fetchRanksData(); // Refresh flat rank list
        await fetchEmployeeData(); // Refresh grouped employee data
        closeAddRankDialog();
        showSnackbar('Rang erfolgreich hinzugefügt.', 'success');
    } catch (error: any) {
        console.error('Error adding rank:', error);
        showSnackbar(error.response?.data?.error || t('employeeView.messages.addRankError'), 'error');
    } finally {
        savingRank.value = false;
    }
};

const openDeleteRankDialog = (rank: Rank) => {
    if (rank.employees && rank.employees.length > 0) {
        showSnackbar(
            'Rang kann nicht gelöscht werden, da ihm Mitarbeiter zugewiesen sind.',
            'warning'
        );
        return;
    }
    rankToDelete.value = rank;
    deleteRankDialog.value = true;
};
const closeDeleteRankDialog = () => {
    deleteRankDialog.value = false;
    rankToDelete.value = null;
};
const confirmDeleteRank = async () => {
    if (!rankToDelete.value) return;
    deletingRank.value = true;
    try {
        const response = await apiClientAuth.post('/employee?action=deleteRank', {
            id: rankToDelete.value.id,
        }); // Adjust path
        if (response.data.success) {
            await fetchRanksData();
            await fetchEmployeeData(); // Refresh data after delete
            closeDeleteRankDialog();
            showSnackbar('Rang erfolgreich gelöscht.', 'success');
        } else {
            showSnackbar(response.data.message || t('employeeView.messages.deleteRankError'), 'error');
        }
    } catch (error: any) {
        console.error('Error deleting rank:', error);
        showSnackbar(error.response?.data?.error || t('employeeView.messages.deleteRankError'), 'error');
    } finally {
        deletingRank.value = false;
    }
};

watch(showSortingModal, newValue => {
    if (newValue) {
        // Clone ranks for safe drag-and-drop, sort them initially by current order
        draggableRanks.value = JSON.parse(JSON.stringify(ranks.value)).sort(
            (a: Rank, b: Rank) => (a.sort_order ?? 999) - (b.sort_order ?? 999)
        );
    }
});

const cancelRankSorting = () => {
    showSortingModal.value = false;
};

const saveRankSorting = async () => {
    savingSort.value = true;
    const sortedData = draggableRanks.value.map((rank, index) => ({
        id: rank.id,
        sort_order: index + 1, // Assign new sort order
    }));
    try {
        await apiClientAuth.post('/employee?action=saveCategorySorting', { ranks: sortedData }); // Adjust path and payload key if needed
        await fetchRanksData(); // Refresh flat list
        await fetchEmployeeData(); // Refresh grouped data to reflect new rank order
        showSnackbar('Rangreihenfolge gespeichert.', 'success');
    } catch (error: any) {
        console.error('Error saving rank sorting:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Rangreihenfolge.',
            'error'
        );
    } finally {
        savingSort.value = false;
        showSortingModal.value = false;
    }
};

// Inline Rank Name Editing
const startEditingField = async (fieldType: string, rank: Rank) => {
    if (!canEdit.value || fieldType !== 'rankName') return;
    cancelEditingField(); // Cancel previous edit
    editingField.value = { type: fieldType, id: rank.id };
    editedValue.value = rank.name;
    await nextTick();
    editFieldRef.value?.focus();
};

const cancelEditingField = () => {
    editingField.value = { type: '', id: -1 };
    editedValue.value = '';
};

const updateRankName = async (rank: Rank) => {
    const currentEditedId = editingField.value.id;
    const currentEditedValue = editedValue.value.trim();

    if (rank.id !== currentEditedId || rank.name === currentEditedValue || !currentEditedValue) {
        cancelEditingField();
        return;
    }

    const originalName = rank.name;
    rank.name = currentEditedValue; // Optimistic update
    cancelEditingField();

    try {
        await apiClientAuth.post('/employee?action=updateRankName', {
            // Adjust path
            id: rank.id,
            name: currentEditedValue,
        });
        // Update the flat ranks list as well for consistency in dropdowns etc.
        const flatRank = ranks.value.find(r => r.id === rank.id);
        if (flatRank) flatRank.name = currentEditedValue;
        showSnackbar('Rangname aktualisiert.', 'success');
    } catch (error: any) {
        console.error('Error updating rank name:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren des Rangnamens.',
            'error'
        );
        rank.name = originalName; // Revert optimistic update
        // Revert flat list too
        const flatRank = ranks.value.find(r => r.id === rank.id);
        if (flatRank) flatRank.name = originalName;
    }
};

// Other Methods
const updateNotes = (data: { id: number; notes: string }) => {
    // Find the employee in the nested structure and update notes
    // This might be inefficient, consider if a flat employee list is better for updates
    for (const rank of employeeData.value) {
        const employee = rank.employees.find(emp => emp.id === data.id);
        if (employee) {
            employee.notes = data.notes;
            break; // Found the employee, no need to continue outer loop
        }
    }
};

const getDepartmentName = (jobRoleId: number | null): string => {
    return JobTypes.value.find((jt: JobType) => jt.id === jobRoleId)?.name ?? 'Unbekannt';
};

// Quick Preview Sidebar Methods
const handleEmployeeClick = (employee: any, rank: Rank) => {
    if (viewMode.value === 'cards') {
        selectedEmployee.value = employee;
        selectedEmployeeRank.value = rank;
    }
};

const closePreviewSidebar = () => {
    selectedEmployee.value = null;
    selectedEmployeeRank.value = null;
};

const handlePreviewEdit = (employee: any) => {
    const employeeWithRank: EmployeeWithRank = {
        employee: employee,
        rank: selectedEmployeeRank.value!
    };
    openEditEmployeeDialog(employeeWithRank);
    closePreviewSidebar();
};

const handlePreviewEditNotes = (employee: any) => {
    closePreviewSidebar();
    // Trigger the editNotes function from MemberCard
    // This will be handled via the MemberCard component
};

const handlePreviewAddVacation = (employee: any) => {
    closePreviewSidebar();
    // This will be handled via the MemberCard component
};

const handlePreviewStopVacation = async (vacationId: number) => {
    try {
        await api.post('user/?action=stopVacation', {
            id: vacationId,
            newdate: new Date(),
        });
        await fetchEmployeeData(); // Refresh data
        showSnackbar('Urlaub beendet.', 'success');
    } catch (err) {
        showSnackbar('Fehler beim Beenden des Urlaubs.', 'error');
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllInitialData();

    // Check if we need to open a specific employee from query parameter or props
    const employeeId = route.query.id || props.id || props.meta?.id || props.meta?.employeeId;
    console.log('[EmployeeView] Looking for employee ID:', employeeId);

    if (employeeId) {
        // Find the employee in the loaded data
        for (const rank of employeeData.value) {
            const employee = rank.employees.find(emp => emp.id === Number(employeeId));
            if (employee) {
                console.log('[EmployeeView] Found employee:', employee);

                if (canEdit.value) {
                    // Open the edit dialog for this employee if user has edit permission
                    const employeeWithRank: EmployeeWithRank = {
                        employee: employee,
                        rank: rank
                    };
                    openEditEmployeeDialog(employeeWithRank);
                } else {
                    // Open quick preview sidebar if user doesn't have edit permission
                    selectedEmployee.value = employee;
                    selectedEmployeeRank.value = rank;
                }
                break;
            }
        }
    }
});
</script>
<template>
    <div class="employee-container">
        <v-container fluid class="pa-4">
            <!-- Header mit Aktionsbuttons -->
            <div class="section-header mb-4">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-account-group" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('employeeView.title') }}</h1>
                </div>
                <div class="action-buttons ml-auto">
                    <v-btn
                        v-if="canEdit"
                        @click="openAddEmployeeDialog"
                        color="primary"
                        variant="elevated"
                        class="mr-2"
                        prepend-icon="mdi-account-plus-outline"
                        size="small"
                    >
                        {{ t('employeeView.addEmployee') }}
                    </v-btn>
                    <v-btn
                        v-if="canEdit"
                        @click="openAddRankDialog"
                        color="primary"
                        variant="tonal"
                        class="mr-2"
                        prepend-icon="mdi-shield-plus-outline"
                        size="small"
                    >
                        {{ t('employeeView.addRank') }}
                    </v-btn>
                    <v-btn
                        v-if="canEdit"
                        @click="showSortingModal = true"
                        color="primary"
                        variant="tonal"
                        class="mr-2"
                        prepend-icon="mdi-sort"
                        size="small"
                    >
                        {{ t('employeeView.sortRanks') }}
                    </v-btn>
                    <v-switch
                        v-model="showIsTerminated"
                        hide-details
                        inset
                        :label="t('employeeView.showTerminated')"
                        density="compact"
                        color="warning"
                        class="ml-4"
                    ></v-switch>

                    <!-- View Mode Toggle -->
                    <v-btn-toggle
                        v-model="viewMode"
                        mandatory
                        density="compact"
                        class="ml-4"
                        color="primary"
                    >
                        <v-btn value="cards" size="small">
                            <v-icon start>mdi-view-grid</v-icon>
                            Karten
                        </v-btn>
                        <v-btn value="table" size="small">
                            <v-icon start>mdi-table</v-icon>
                            Tabelle
                        </v-btn>
                    </v-btn-toggle>
                </div>
            </div>

            <!-- Suchfilter -->
            <div class="search-filters mb-6">
                <v-row>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="search"
                            :label="t('employeeView.searchPlaceholder')"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-magnify"
                            hide-details
                            clearable
                            
                            color="primary"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-select
                            v-model="selectedDepartment"
                            :items="jobTypeOptions"
                            item-title="name"
                            item-value="id"
                            :label="t('employeeView.departmentFilter')"
                            variant="outlined"
                            density="compact"
                            clearable
                            hide-details
                            color="primary"
                            :menu-props="{ 
                                contentClass: 'dropdown-menu-container',
                                closeOnContentClick: true,
                                openOnClick: true,
                                maxHeight: 300
                            }"
                            eager
                            attach
                        ></v-select>
                    </v-col>
                </v-row>
            </div>

            <!-- Ladezustand -->
            <v-row v-if="loadingData" justify="center" class="my-10">
                <v-col cols="auto" class="text-center">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="64"
                    ></v-progress-circular>
                    <p class="mt-4 text-grey">Lade Mitarbeiterdaten...</p>
                </v-col>
            </v-row>

            <!-- Keine Daten Zustand -->
            <v-card
                v-else-if="filteredRanksWithEmployees.length === 0"
                class="empty-state-card pa-8 mb-3"
                variant="outlined"
            >
                <div class="d-flex flex-column align-center">
                    <v-icon
                        icon="mdi-account-group"
                        size="64"
                        color="grey-darken-1"
                        class="mb-4"
                    ></v-icon>
                    <span class="text-h6 text-grey-darken-1">Keine Mitarbeiter gefunden</span>
                    <span class="text-body-2 text-grey-darken-3 mt-2">
                        Passe deine Suchfilter an oder füge neue Mitarbeiter hinzu
                    </span>
                </div>
            </v-card>

            <!-- Mitarbeiterliste - Beide Views -->
            <div v-else>
                <!-- Mitarbeiterliste - Kartenansicht mit Sidebar -->
                <div v-if="viewMode === 'cards'" class="employee-layout">
                <div class="employee-content" :class="{ 'with-sidebar': selectedEmployee }">
                    <div class="rank-sections">
                        <template v-for="rank in sichtbareRaenge" :key="rank.id">
                            <div class="rank-section">
                                <!-- Rang-Header -->
                                <div class="rank-header">
                                    <template
                                        v-if="
                                            editingField.type !== 'rankName' || editingField.id !== rank.id
                                        "
                                    >
                                        <h2
                                            @dblclick="canEdit ? startEditingField('rankName', rank) : null"
                                            :style="canEdit ? 'cursor: pointer;' : ''"
                                            class="rank-title"
                                        >
                                            {{ rank.name }}
                                            <v-tooltip
                                                v-if="canEdit"
                                                :text="t('employeeView.doubleClickToEdit')"
                                                location="top"
                                            >
                                                <template v-slot:activator="{ props }">
                                                    <v-icon
                                                        v-bind="props"
                                                        size="x-small"
                                                        class="ml-1"
                                                        color="grey"
                                                        >mdi-pencil-outline</v-icon
                                                    >
                                                </template>
                                            </v-tooltip>
                                        </h2>
                                    </template>
                                    <template v-else>
                                        <v-text-field
                                            v-model="editedValue"
                                            density="compact"
                                            variant="underlined"
                                            single-line
                                            autofocus
                                            hide-details
                                            ref="editFieldRef"
                                            @keydown.enter="updateRankName(rank)"
                                            @keydown.esc="cancelEditingField"
                                            @blur="updateRankName(rank)"
                                            class="rank-edit-field"
                                            bg-color="grey-darken-4"
                                            color="primary"
                                        ></v-text-field>
                                    </template>
                                    <v-tooltip text="Rang löschen (nur wenn leer)" location="top">
                                        <template v-slot:activator="{ props }">
                                            <div v-bind="props" class="d-inline-block">
                                                <v-btn
                                                    v-if="canDelete"
                                                    icon
                                                    variant="text"
                                                    size="small"
                                                    @click="openDeleteRankDialog(rank)"
                                                    color="error"
                                                    :disabled="rank.employees.length > 0"
                                                >
                                                    <v-icon size="small">mdi-delete-outline</v-icon>
                                                </v-btn>
                                            </div>
                                        </template>
                                    </v-tooltip>
                                </div>

                                <!-- Mitarbeiterkarten - Kompakte Grid-Ansicht -->
                                <div class="employee-grid">
                                    <EmployeeCardMini
                                        v-for="employee in rank.employees"
                                        :key="employee.id"
                                        :employee="employee"
                                        :rank="rank"
                                        :is-selected="selectedEmployee?.id === employee.id"
                                        @click="handleEmployeeClick"
                                    />
                                </div>
                            </div>
                            <v-divider
                                v-if="rank.employees.length > 0"
                                class="rank-divider my-6"
                            ></v-divider>
                        </template>

                        <!--
                            Unbesetzte Raenge zusammengefasst: sie verschwinden
                            nicht, kosten aber eine Zeile statt je einen ganzen
                            Abschnitt. Ein Klick blendet sie wieder einzeln ein.
                        -->
                        <div
                            v-if="unbesetzteRaenge.length > 0"
                            class="empty-ranks-row"
                        >
                            <v-icon size="16" class="empty-ranks-row__icon">mdi-account-off-outline</v-icon>
                            <span class="empty-ranks-row__text">
                                {{ $t('employee.emptyRanks', { n: unbesetzteRaenge.length }) }}
                            </span>
                            <span class="empty-ranks-row__names">
                                {{ unbesetzteRaenge.map(r => r.name).join(' · ') }}
                            </span>
                            <v-btn
                                variant="text"
                                size="small"
                                class="empty-ranks-row__toggle"
                                @click="zeigeUnbesetzteRaenge = !zeigeUnbesetzteRaenge"
                            >
                                {{ zeigeUnbesetzteRaenge ? $t('common.hide') : $t('common.show') }}
                            </v-btn>
                        </div>
                    </div>
                </div>

                <!-- Quick Preview Sidebar -->
                <EmployeeQuickPreview
                    :employee="selectedEmployee"
                    :rank="selectedEmployeeRank"
                    :companies="companies"
                    :departments="departments"
                    :licenses="licenses"
                    :trainings="Trainings"
                    :trainingAssigns="TrainingAssigns"
                    @close="closePreviewSidebar"
                    @edit="handlePreviewEdit"
                    @editNotes="handlePreviewEditNotes"
                    @addVacation="handlePreviewAddVacation"
                    @stopVacation="handlePreviewStopVacation"
                />
                </div>

                <!-- Mitarbeiterliste - Tabellenansicht -->
                <EmployeeTableView
                    v-else
                    :ranks="filteredRanksWithEmployees"
                    :companies="companies"
                    :departments="departments"
                    :licenses="licenses"
                    :canEdit="canEdit"
                    :canDelete="canDelete"
                    @edit="openEditEmployeeDialog"
                    @editNotes="(employee) => {}"
                    @addVacation="(employee) => {}"
                    @stopVacation="(vacationId) => handlePreviewStopVacation(vacationId)"
                />
            </div>

            <!-- Mitarbeiter-Formular Komponente -->
            <EmployeeForm
                ref="employeeFormRef"
                :companies="companies"
                :departments="departments"
                :ranks="ranks"
                :licenses="licenses"
                :jobTypes="JobTypes"
                @updateEmployeeList="fetchEmployeeData"
                @close="handleEmployeeFormClose"
            />

            <!-- Rangfolgendialog -->
            <v-dialog v-model="showSortingModal" max-width="600px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-sort" class="mr-2"></v-icon>
                        Ränge sortieren
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-caption mb-4">Ränge per Drag & Drop sortieren.</p>
                        <draggable
                            v-model="draggableRanks"
                            :list="draggableRanks"
                            tag="v-list"
                            item-key="id"
                            :animation="200"
                            handle=".drag-handle"
                            ghost-class="ghost-item"
                            class="draggable-list"
                        >
                            <template #item="{ element }">
                                <v-list-item class="draggable-item mb-2" :key="element.id">
                                    <template v-slot:prepend>
                                        <v-icon
                                            class="drag-handle mr-2"
                                            icon="mdi-drag-horizontal-variant"
                                            size="small"
                                        ></v-icon>
                                    </template>
                                    <v-list-item-title>{{ element.name }}</v-list-item-title>
                                    <v-list-item-subtitle>{{
                                        getDepartmentName(element.jobrole_id)
                                    }}</v-list-item-subtitle>
                                </v-list-item>
                            </template>
                        </draggable>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="cancelRankSorting">{{ t('cancel') }}</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveRankSorting"
                            :loading="savingSort"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Hinzufügen Rang Dialog -->
            <v-dialog v-model="addRankDialog" max-width="500px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-shield-plus" class="mr-2"></v-icon>
                        Neuen Rang hinzufügen
                    </v-card-title>
                    <v-form ref="addRankFormRef" v-model="isAddRankFormValid">
                        <v-card-text class="pa-4">
                            <v-text-field
                                :label="t('employeeView.rankName')"
                                required
                                v-model="newRank.name"
                                :rules="[requiredRule]"
                                variant="outlined"
                                density="comfortable"
                                class="mb-3"
                                
                                color="primary"
                            ></v-text-field>
                            <v-select
                                v-model="newRank.department"
                                :label="t('employeeView.associatedDepartment')"
                                :items="jobTypeOptions"
                                item-title="name"
                                item-value="id"
                                required
                                :rules="[requiredRule]"
                                variant="outlined"
                                density="comfortable"
                                
                                color="primary"
                            ></v-select>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions class="pa-4">
                            <v-spacer></v-spacer>
                            <v-btn variant="text" @click="closeAddRankDialog">{{ t('cancel') }}</v-btn>
                            <v-btn
                                color="primary"
                                variant="elevated"
                                @click="addNewRank"
                                :disabled="!isAddRankFormValid"
                                :loading="savingRank"
                            >
                                {{ t('add') }}
                            </v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-dialog>

            <!-- Löschen Rang Dialog -->
            <v-dialog v-model="deleteRankDialog" max-width="500px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Rang löschen
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-body-1 mb-2">
                            Möchten Sie den folgenden Rang wirklich löschen?
                        </p>
                        <p class="text-body-2 font-weight-bold">{{ rankToDelete?.name }}</p>
                        <p class="text-caption text-grey-darken-1 mt-4">
                            Dies ist nur möglich, wenn dem Rang keine Mitarbeiter mehr zugeordnet
                            sind. Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteRankDialog">{{ t('cancel') }}</v-btn>
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDeleteRank"
                            :loading="deletingRank"
                        >
                            {{ t('delete') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.employee-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Add styles for dropdown */
:deep(.dropdown-menu-container) {
    z-index: 1000 !important;
    position: fixed !important;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--k-line);
}

.action-buttons {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.empty-state-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
}

.rank-section {
    margin-bottom: 20px;
    animation: fadeIn 0.3s ease-out forwards;
}

.rank-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: rgba(30, 41, 59, 0.4);
    border-radius: 8px;
    backdrop-filter: blur(8px);
    border: 1px solid var(--k-line);
}

.rank-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.rank-edit-field {
    max-width: 300px;
    padding: 0;
    margin: 0;
}

.rank-edit-field :deep(.v-field__input) {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    min-height: auto !important;
    font-size: 1.25rem;
    font-weight: 600;
}

.rank-divider {
    border-color: var(--k-ink) !important;
    width: 100%;
}

.employee-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

/* Dialog styling */
.dialog-card {
    background-color: var(--k-ink) !important;
    border: 1px solid var(--k-line);
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

/* Draggable items */
.draggable-list {
    background: rgba(15, 23, 42, 0.6) !important;
    border-radius: 8px;
    padding: 8px;
    border: 1px solid var(--k-line);
}

.draggable-item {
    background: rgba(30, 41, 59, 0.7) !important;
    margin-bottom: 8px;
    border-radius: 6px;
    border: 1px solid var(--k-line);
    transition: all 0.2s ease;
}

.draggable-item:hover {
    background: rgba(30, 41, 59, 0.9) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ghost-item {
    opacity: 0.5;
    background: rgba(59, 130, 246, 0.2) !important;
    border: 1px dashed rgba(59, 130, 246, 0.5) !important;
}

.drag-handle {
    cursor: move;
    opacity: 0.6;
    transition: opacity 0.2s ease-in-out;
    color: var(--k-ink-muted);
}

.draggable-item:hover .drag-handle {
    opacity: 1;
    color: var(--k-ink-faint);
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

.editor-overlay{
	background-color: rgba(0, 0, 0, 0) !important;
}

.document-editor-container {
  max-height: unset !important;
  position: relative !important;
}

.editor-overlay {
  padding: 0px !important;
}

/* Employee Layout with Sidebar */
.employee-layout {
    display: flex;
    gap: 20px;
    position: relative;
}

.employee-content {
    flex: 1;
    min-width: 0;
    transition: all 0.3s ease;
}

.employee-content.with-sidebar {
    margin-right: 0;
}

/* Employee Grid Responsive */
@media (max-width: 1600px) {
    .employee-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .employee-grid {
        grid-template-columns: 1fr;
    }
}

/* Responsive adjustments */
@media (max-width: 1400px) {
    .employee-layout {
        flex-direction: column;
    }

    .employee-content.with-sidebar {
        margin-right: 0;
    }
}

/* Unbesetzte Raenge: eine Zeile statt je ein Abschnitt. */
.empty-ranks-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    margin-top: 4px;
    border: 1px dashed var(--k-line-strong, #363e4a);
    border-radius: 6px;
    font-size: 12.5px;
    color: var(--k-ink-muted, #9aa4b2);
}

.empty-ranks-row__icon {
    opacity: 0.6;
    flex: none;
}

.empty-ranks-row__text {
    font-weight: 550;
    flex: none;
}

.empty-ranks-row__names {
    color: var(--k-ink-faint, #6b7684);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-ranks-row__toggle {
    margin-left: auto;
    flex: none;
}
</style>
