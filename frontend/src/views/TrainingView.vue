<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import auth store
import { useI18n } from 'vue-i18n';
import { useModulePermission } from '@/composables/useModulePermission';
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import type {
    Training,
    TrainingAssign,
    Employee,
    NewTraining,
    Rank,
} from '@/types/Training'; // Adjust path and ensure types exist
import { useToast } from 'vue-toastification'; // Import toast

// Define Company interface locally since it's not exported from Training types
interface Company {
    id: number;
    name: string;
    employee_ids?: number[];
}

// Define CategoryWithData type locally since it's not exported from Training types
interface CategoryWithData {
    id: number;
    name: string;
    sort_order: number;
    trainings: Training[];
    headers: {
        title: string;
        key: string;
        sortable?: boolean;
        align?: 'start' | 'end' | 'center';
        minWidth?: string;
        width?: string;
        value?: string;
    }[];
    employees: {
        id: number;
        name: string;
        servicenumber: number;
        rank_id: number;
        rank_name: string;
        trainingDetails: { [key: string]: { date: string; instructor: string } };
    }[];
}

// --- Router & Permissions ---
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

console.log('Training View props from desktop window:', {
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
  !!route.meta.canEdit
);

const canDelete = computed(() => 
  isAdmin.value || 
  props.canDelete || 
  props.meta?.canDelete || 
  !!route.meta.canDelete
);

const canCreate = computed(() =>
  isAdmin.value || 
  props.canCreate || 
  props.meta?.canCreate || 
  !!route.meta.canCreate
);

// --- Component State ---
const search = ref('');
const Trainings = ref<Training[]>([]);
const TrainingAssigns = ref<TrainingAssign[]>([]);
const Employees = ref<Employee[]>([]);
const Ranks = ref<Rank[]>([]);
const Companies = ref<Company[]>([]);
const loadingData = ref(false);
const savingTraining = ref(false);
const creatingPlan = ref(false);

// --- Filters ---
const selectedRanks = ref<number[]>([]);
const selectedCompanies = ref<number[]>([]);

// --- Quick Assign ---
const quickAssignNote = ref('');
const quickAssignDate = ref(''); // Empty means today's date
const quickAssigningTraining = ref<{ employeeId: number; trainingId: number } | null>(null);

// --- Dialog State ---
const editDialog = ref(false); // Dialog for adding new training assignments
const trainingFormRef = ref<any>(null);
const isTrainingFormValid = ref(false);
const initialNewTrainingData: NewTraining = {
    trainingId: [],
    employees: [],
    date: '',
    notes: '',
};
const newTraining = reactive<NewTraining>({ ...initialNewTrainingData });

// --- Expansion State für einklappbare Kategorien ---
const expandedCategories = ref<boolean[]>([]);

// --- Export Dialog State ---
interface ExportColumn {
    id: string;
    label: string;
    type: 'employee' | 'training' | 'custom';
    trainingId?: number; // For training columns
    customValue?: string; // For custom columns
}

const exportDialog = ref(false);
const exportingData = ref(false);

// Available columns for export
const availableEmployeeColumns = computed(() => [
    { id: 'servicenumber', label: t('trainingView.export.servicenumber') },
    { id: 'name', label: t('trainingView.export.name') },
    { id: 'rank', label: t('trainingView.export.rank') },
    { id: 'company', label: t('trainingView.export.company') },
]);

// Helper to get employee's company name
const getEmployeeCompany = (employeeId: number): string => {
    const company = Companies.value.find(c => c.employee_ids?.includes(employeeId));
    return company?.name || '';
};

const availableTrainingColumns = computed(() =>
    Trainings.value.map(training => ({
        id: `training_${training.id}`,
        label: training.name,
        trainingId: training.id,
        catName: training.cat_name,
    }))
);

// Selected columns for export (with order)
const selectedExportColumns = ref<ExportColumn[]>([]);

// Custom columns
const customColumns = ref<{ label: string; value: string }[]>([]);
const newCustomColumnLabel = ref('');
const newCustomColumnValue = ref('');

// Export options
const exportShowDate = ref(true); // Show date in training cells
const exportShowInstructor = ref(false); // Show instructor in training cells
const exportOnlyCompleted = ref(false); // Only show completed trainings with checkmark

// --- Snackbar ---
const toast = useToast();

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Computed Properties ---

const rankOptions = computed(() => Ranks.value.map(rank => ({ id: rank.id, name: rank.name })));
const companyOptions = computed(() =>
    Companies.value.map(company => ({ id: company.id, name: company.name }))
);

// Sorted employees by service number for dropdown
const sortedEmployees = computed(() =>
    [...Employees.value].sort((a, b) => a.servicenumber - b.servicenumber)
);

// Computed property to process and filter data reactively
const categoriesWithFilteredTrainings = computed((): CategoryWithData[] => {
    const categoriesMap: { [key: number]: CategoryWithData } = {};
    const searchTermLower = search.value.toLowerCase();

    // 1. Initialize Categories and Headers from Trainings
    Trainings.value.forEach(training => {
        if (!categoriesMap[training.catId]) {
            categoriesMap[training.catId] = {
                id: training.catId,
                name: training.cat_name,
                sort_order: training.cat_sort_order || 0,
                trainings: [],
                // Header: Employee Name + one column per Training in this category
                headers: [
                    {
                        title: 'Mitarbeiter',
                        key: 'name',
                        sortable: true,
                        align: 'start',
                        minWidth: '200px',
                    },
                ],
                employees: [], // Will be filled later
            };
        }
        categoriesMap[training.catId].trainings.push(training);
        // Add header for this training
        categoriesMap[training.catId].headers.push({
            title: training.name,
            key: training.id.toString(), // Use training ID as key
            sortable: false, // Typically not sortable
            align: 'center',
            width: '180px', // Increased width to accommodate icon + date
        });
    });

    // 2. Filter Employees based on Search, Rank, and Company
    const filteredEmployees = Employees.value.filter(employee => {
        const matchesSearch =
            !searchTermLower ||
            employee.name.toLowerCase().includes(searchTermLower) ||
            employee.servicenumber.toString().includes(searchTermLower) ||
            // Search within the names of the trainings this employee has completed? Maybe too complex/slow.
            false;

        const matchesRank =
            selectedRanks.value.length === 0 || selectedRanks.value.includes(employee.rank_id);

        const matchesCompany =
            selectedCompanies.value.length === 0 ||
            Companies.value.some(
                company =>
                    selectedCompanies.value.includes(company.id) && // Is one of the selected companies
                    company.employee_ids?.includes(employee.id) // And the employee is in it
            );

        return matchesSearch && matchesRank && matchesCompany;
    });

    // 3. Populate categories with filtered employees and their training details
    filteredEmployees.forEach(employee => {
        const trainingDetails: { [key: string]: { date: string; instructor: string } } = {};
        TrainingAssigns.value.forEach(assign => {
            if (assign.employeeid === employee.id) {
                trainingDetails[assign.training_id.toString()] = {
                    date: assign.date, // Assuming date is already formatted if needed
                    instructor: assign.instructor,
                };
            }
        });

        // Add this employee to each category map entry IF they need any training from that category (or just add all filtered employees?)
        // Let's add all filtered employees to every category for now, the table will show checks/crosses.
        Object.values(categoriesMap).forEach(category => {
            category.employees.push({
                ...employee,
                trainingDetails, // Add the processed details
            });
        });
    });

    // 4. Convert map to array and filter out categories with no employees (after filtering)
    const result = Object.values(categoriesMap).filter(cat => cat.employees.length > 0);

    // 5. Sort employees by service number within each category
    result.forEach(category => {
        category.employees.sort((a, b) => a.servicenumber - b.servicenumber);
    });

    // 6. Sort categories by sort_order, then by name as fallback
    result.sort((a, b) => {
        if (a.sort_order !== b.sort_order) {
            return a.sort_order - b.sort_order;
        }
        return a.name.localeCompare(b.name);
    });

    return result;
});

// --- Validation Rules ---
/**
 * Pflichtfeld-Regel.
 *
 * Der Entwurf verlangt, dass eine Meldung die Ursache benennt: "Diese
 * Dienstnummer ist bereits vergeben" statt "Ungueltige Eingabe". Fuer ein
 * fehlendes Pflichtfeld heisst das, den Namen des Feldes zu nennen - er steht
 * ohnehin als Beschriftung daneben.
 */
const requiredRule = (feld?: string) => (value: any) =>
    (value !== null && value !== undefined && String(value).trim() !== '') ||
    (feld ? `${feld} fehlt.` : 'Dieses Feld muss ausgefüllt werden.');
const multiSelectRequiredRule = (value: any[]) =>
    (value && value.length > 0) || 'Mindestens ein Element auswählen.';

// --- Methoden für einklappbare Kategorien ---
const toggleCategoryExpansion = (index: number) => {
    expandedCategories.value[index] = !expandedCategories.value[index];
};

const expandAllCategories = () => {
    expandedCategories.value = expandedCategories.value.map(() => true);
};

const collapseAllCategories = () => {
    expandedCategories.value = expandedCategories.value.map(() => false);
};

// Watcher um expandedCategories zu aktualisieren, wenn sich die Kategorien ändern
watch(
    categoriesWithFilteredTrainings,
    (newCategories, oldCategories) => {
        // Nur zurücksetzen wenn sich die Anzahl der Kategorien ändert (z.B. durch Filter)
        // oder beim initialen Laden (oldCategories undefined)
        const oldCategoryIds = oldCategories?.map(c => c.id).sort().join(',') || '';
        const newCategoryIds = newCategories.map(c => c.id).sort().join(',');

        if (oldCategoryIds !== newCategoryIds) {
            // Kategorien haben sich geändert - expandedCategories anpassen
            const newExpandedState: boolean[] = [];
            newCategories.forEach((cat) => {
                // Beibehalten des alten Zustands wenn die Kategorie vorher existierte
                const oldIndex = oldCategories?.findIndex(old => old.id === cat.id);
                if (oldIndex !== undefined && oldIndex >= 0 && expandedCategories.value[oldIndex]) {
                    newExpandedState.push(true);
                } else {
                    newExpandedState.push(false);
                }
            });
            expandedCategories.value = newExpandedState;
        }
        // Wenn sich nur die Daten innerhalb der Kategorien ändern, nicht zurücksetzen
    },
    { immediate: true }
);

// --- Data Fetching ---
const fetchData = async (
    action: string,
    targetRef: any,
    mapFn?: (item: any) => any,
    errorMessage?: string
) => {
    try {
        const response = await apiClientAuth.get<any[]>(`/training/?action=${action}`); // Adjusted endpoint path
        const data = response.data || response.data || []; // Handle potential variations in response structure
        targetRef.value = mapFn ? data.map(mapFn) : data;
        return true; // Indicate success
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(
            error.response?.data?.error || errorMessage || `Fehler beim Laden von ${action}.`,
            'error'
        );
        targetRef.value = []; // Clear data on error
        return false; // Indicate failure
    }
};

const fetchAllInitialData = async () => {
    loadingData.value = true;
    await Promise.all([
        fetchData('getTrainings', Trainings, item => ({ ...item })),
        fetchData('getTrainingAssigns', TrainingAssigns, item => ({ ...item })),
        fetchData('getEmployees', Employees, item => ({
            id: item.id,
            name: `[${item.servicenumber}] ${item.name}`, // Format name here
            servicenumber: item.servicenumber,
            rank_id: item.rank_id,
            rank_name: item.rank_name,
            // 'trainings' property removed as it wasn't used correctly and details are added later
        })),
        fetchData('getRanks', Ranks, item => ({ ...item })),
        fetchData('getCompanies', Companies, item => ({
            ...item,
            employee_ids: item.employee_ids || [],
        })), // Ensure employee_ids is array
    ]);
    // Options will automatically update through computed properties
    loadingData.value = false;
};

// --- Methods ---
const openNewTrainingDialog = () => {
    Object.assign(newTraining, { ...initialNewTrainingData }); // Reset form
    isTrainingFormValid.value = false;
    editDialog.value = true;
    setTimeout(() => trainingFormRef.value?.resetValidation(), 100);
};

const closeNewTrainingDialog = () => {
    editDialog.value = false;
};

const saveNewTraining = async () => {
    if (!isTrainingFormValid.value) return;
    savingTraining.value = true;
    try {
        // The payload expects arrays for trainingId and employees
        const payload = {
            trainingIds: newTraining.trainingId, // Rename key if API expects trainingIds
            employeeIds: newTraining.employees, // Rename key if API expects employeeIds
            date: newTraining.date,
            instructor: newTraining.notes, // Rename key if API expects instructor
        };
        await apiClientAuth.post('/training?action=saveTraining', payload); // Adjusted endpoint path
        await fetchData('getTrainingAssigns', TrainingAssigns, item => ({ ...item }));
        closeNewTrainingDialog();
        showSnackbar('Schulung(en) erfolgreich gespeichert.', 'success');
    } catch (error: any) {
        console.error('Error saving training:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern der Schulung.', 'error');
    } finally {
        savingTraining.value = false;
    }
};

// Quick assign a single training to a single employee
const quickAssignTraining = async (employeeId: number, trainingId: number) => {
    quickAssigningTraining.value = { employeeId, trainingId };
    try {
        // Use selected date or today's date if not set
        const assignDate = quickAssignDate.value || new Date().toISOString().split('T')[0];
        const payload = {
            trainingIds: [trainingId],
            employeeIds: [employeeId],
            date: assignDate,
            instructor: quickAssignNote.value || '', // Use the quick assign note
        };
        await apiClientAuth.post('/training?action=saveTraining', payload);
        await fetchData('getTrainingAssigns', TrainingAssigns, item => ({ ...item }));
        showSnackbar(t('trainingView.assignSuccess'), 'success');
    } catch (error: any) {
        console.error('Error quick assigning training:', error);
        showSnackbar(error.response?.data?.error || t('trainingView.assignError'), 'error');
    } finally {
        quickAssigningTraining.value = null;
    }
};

// Delete a training assignment
const deleteTrainingAssign = async (employeeId: number, trainingId: number) => {
    quickAssigningTraining.value = { employeeId, trainingId };
    try {
        const payload = {
            employeeId,
            trainingId,
        };
        await apiClientAuth.post('/training?action=deleteTrainingAssign', payload);
        await fetchData('getTrainingAssigns', TrainingAssigns, item => ({ ...item }));
        showSnackbar(t('trainingView.deleteSuccess'), 'success');
    } catch (error: any) {
        console.error('Error deleting training assignment:', error);
        showSnackbar(error.response?.data?.error || t('trainingView.deleteError'), 'error');
    } finally {
        quickAssigningTraining.value = null;
    }
};

const createTrainingplan = async () => {
    creatingPlan.value = true;
    const headerTemplate = (categoryName: string) => `
        <tr>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>${categoryName}</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Datum/ Uhrzeit</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Dozent</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Module benötigt</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Teilnahme an Schulung</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Mind. Teilnehmer</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Bemerkungen</strong></span></td>
            <td style="background-color:rgb(201, 201, 201); white-space:nowrap"><span style="color:#000000; font-weight: bold;"><strong>Ort</strong></span></td>
        </tr>`;

    let body = '';
    const categoryNamesToInclude = [
        'Ground Training',
        'Advanced Training',
        'Engine Training',
        'Ladder Training',
        'Rescue Training',
    ];
    const allCategories = categoriesWithFilteredTrainings.value; // Use computed property which has all categories

    // Sort categories according to the desired order
    const sortedCategories = allCategories.sort((a, b) => {
        const indexA = categoryNamesToInclude.indexOf(a.name);
        const indexB = categoryNamesToInclude.indexOf(b.name);
        // Put desired categories first, in their defined order
        if (indexA !== -1 && indexB !== -1) return indexA - indexB;
        if (indexA !== -1) return -1; // a is desired, b is not
        if (indexB !== -1) return 1; // b is desired, a is not
        return a.name.localeCompare(b.name); // Sort others alphabetically
    });

    sortedCategories.forEach(category => {
        // Only include categories from the specified list
        if (!categoryNamesToInclude.includes(category.name)) return;

        body += headerTemplate(category.name);

        category.trainings.forEach(training => {
            let companyFilterActive = false;
            let companyEmployeeIds: number[] = [];

            if (category.name === 'Engine Training') {
                const engineCompany = Companies.value.find(
                    company => company.name === 'Engine Company'
                );
                if (engineCompany) {
                    companyFilterActive = true;
                    companyEmployeeIds = engineCompany.employee_ids || [];
                }
            } else if (category.name === 'Ladder Training') {
                const ladderCompany = Companies.value.find(
                    company => company.name === 'Ladder Company'
                );
                if (ladderCompany) {
                    companyFilterActive = true;
                    companyEmployeeIds = ladderCompany.employee_ids || [];
                }
            } else if (category.name === 'Rescue Training') {
                const rescueCompany = Companies.value.find(
                    company => company.name === 'Rescue Company'
                );
                if (rescueCompany) {
                    companyFilterActive = true;
                    companyEmployeeIds = rescueCompany.employee_ids || [];
                }
            }

            const employeesWithoutTraining = Employees.value.filter(
                employee =>
                    // Apply company filter only if active for this category
                    (!companyFilterActive || companyEmployeeIds.includes(employee.id)) &&
                    // Check if employee is missing this specific training assignment
                    !TrainingAssigns.value.some(
                        assign =>
                            assign.training_id === training.id && assign.employeeid === employee.id
                    )
            );

            const employeeServiceNumbers = employeesWithoutTraining
                .map(employee => `${employee.servicenumber}`)
                .join(', ');

            body += `
                <tr>
                    <td style="white-space:nowrap; width:200px">${training.name}</td>
                    <td style="white-space:nowrap; width:180px">&nbsp;</td>
                    <td style="white-space:nowrap; width:100px">&nbsp;</td>
                    <td style="white-space:nowrap; width:240px">${employeeServiceNumbers || '&nbsp;'}</td>
                    <td style="white-space:nowrap; width:240px">&nbsp;</td>
                    <td style="white-space:nowrap">&nbsp;</td>
                    <td style="white-space:nowrap">Unterlagen durchlesen &amp; verinnerlichen</td>
                    <td style="white-space:nowrap; width:120px">&nbsp;</td>
                </tr>`;
        });
    });

    const trainingPlanHTML = `
        <p>An die Personalabteilung</p>
        <p>Die folgende Tabelle zeigt die Übersicht aller ausstehenden Schulungen auf, mit Bitte um Planung.</p>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width:1180px">
            <tbody>${body}</tbody>
        </table>
        <p>Mit freundlichen Grüßen</p>`;

    try {
        await navigator.clipboard.writeText(trainingPlanHTML);
        showSnackbar('Ausbildungsplan erfolgreich kopiert.', 'success');
    } catch (err) {
        console.error('Error copying training plan:', err);
        showSnackbar('Ausbildungsplan konnte nicht kopiert werden.', 'error');
    } finally {
        creatingPlan.value = false;
    }
};

// --- Export Functions ---
const openExportDialog = () => {
    // Initialize with default columns if empty
    if (selectedExportColumns.value.length === 0) {
        selectedExportColumns.value = [
            { id: 'servicenumber', label: t('trainingView.export.servicenumber'), type: 'employee' },
            { id: 'name', label: t('trainingView.export.name'), type: 'employee' },
            { id: 'rank', label: t('trainingView.export.rank'), type: 'employee' },
        ];
    }
    exportDialog.value = true;
};

const closeExportDialog = () => {
    exportDialog.value = false;
};

const addEmployeeColumn = (columnId: string) => {
    const column = availableEmployeeColumns.value.find(c => c.id === columnId);
    if (column && !selectedExportColumns.value.find(c => c.id === columnId)) {
        selectedExportColumns.value.push({
            id: column.id,
            label: column.label,
            type: 'employee',
        });
    }
};

const addTrainingColumn = (trainingId: number) => {
    const training = availableTrainingColumns.value.find(t => t.trainingId === trainingId);
    if (training && !selectedExportColumns.value.find(c => c.id === training.id)) {
        selectedExportColumns.value.push({
            id: training.id,
            label: training.label,
            type: 'training',
            trainingId: training.trainingId,
        });
    }
};

const addAllTrainingsFromCategory = (categoryName: string) => {
    const trainingsInCategory = availableTrainingColumns.value.filter(t => t.catName === categoryName);
    trainingsInCategory.forEach(training => {
        if (!selectedExportColumns.value.find(c => c.id === training.id)) {
            selectedExportColumns.value.push({
                id: training.id,
                label: training.label,
                type: 'training',
                trainingId: training.trainingId,
            });
        }
    });
};

const addCustomColumn = () => {
    if (newCustomColumnLabel.value.trim()) {
        const customId = `custom_${Date.now()}`;
        selectedExportColumns.value.push({
            id: customId,
            label: newCustomColumnLabel.value.trim(),
            type: 'custom',
            customValue: newCustomColumnValue.value.trim(),
        });
        customColumns.value.push({
            label: newCustomColumnLabel.value.trim(),
            value: newCustomColumnValue.value.trim(),
        });
        newCustomColumnLabel.value = '';
        newCustomColumnValue.value = '';
    }
};

const removeExportColumn = (index: number) => {
    selectedExportColumns.value.splice(index, 1);
};

const moveColumnUp = (index: number) => {
    if (index > 0) {
        const temp = selectedExportColumns.value[index];
        selectedExportColumns.value[index] = selectedExportColumns.value[index - 1];
        selectedExportColumns.value[index - 1] = temp;
    }
};

const moveColumnDown = (index: number) => {
    if (index < selectedExportColumns.value.length - 1) {
        const temp = selectedExportColumns.value[index];
        selectedExportColumns.value[index] = selectedExportColumns.value[index + 1];
        selectedExportColumns.value[index + 1] = temp;
    }
};

const getTrainingCategories = computed(() => {
    const categories = new Set<string>();
    availableTrainingColumns.value.forEach(t => categories.add(t.catName));
    return Array.from(categories);
});

const generateExportHTML = async () => {
    exportingData.value = true;

    try {
        // Get filtered employees (respecting current filters)
        const employeesToExport = categoriesWithFilteredTrainings.value.length > 0
            ? categoriesWithFilteredTrainings.value[0].employees
            : [];

        // Build table header
        let headerRow = '<tr>';
        selectedExportColumns.value.forEach(col => {
            headerRow += `<td style="background-color:#c9c9c9; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
        });
        headerRow += '</tr>';

        // Build table body
        let bodyRows = '';
        employeesToExport.forEach(employee => {
            let row = '<tr>';
            selectedExportColumns.value.forEach(col => {
                let cellContent = '&nbsp;';

                if (col.type === 'employee') {
                    switch (col.id) {
                        case 'servicenumber':
                            cellContent = String(employee.servicenumber);
                            break;
                        case 'name':
                            // Remove the [servicenumber] prefix if present
                            cellContent = employee.name.replace(/^\[\d+\]\s*/, '');
                            break;
                        case 'rank':
                            cellContent = employee.rank_name || '';
                            break;
                        case 'company':
                            cellContent = getEmployeeCompany(employee.id);
                            break;
                    }
                } else if (col.type === 'training' && col.trainingId) {
                    const trainingDetail = employee.trainingDetails[col.trainingId.toString()];
                    if (trainingDetail) {
                        // Training completed
                        let content = '✓';
                        if (exportShowDate.value && trainingDetail.date) {
                            content += ` ${formatDate(trainingDetail.date) || ''}`;
                        }
                        if (exportShowInstructor.value && trainingDetail.instructor) {
                            content += ` (${trainingDetail.instructor})`;
                        }
                        cellContent = content;
                    } else {
                        // Training not completed
                        cellContent = exportOnlyCompleted.value ? '' : '✗';
                    }
                } else if (col.type === 'custom') {
                    cellContent = col.customValue || '';
                }

                row += `<td style="white-space:nowrap; padding:5px;">${cellContent}</td>`;
            });
            row += '</tr>';
            bodyRows += row;
        });

        // Build complete HTML table
        const tableHTML = `<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tbody>
${headerRow}
${bodyRows}
</tbody>
</table>`;

        // Copy to clipboard
        await navigator.clipboard.writeText(tableHTML);
        showSnackbar(t('trainingView.export.success'), 'success');
        closeExportDialog();
    } catch (err) {
        console.error('Error generating export:', err);
        showSnackbar(t('trainingView.export.error'), 'error');
    } finally {
        exportingData.value = false;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string | null => {
    if (!dateString) return null;
    try {
        const date = new Date(dateString.split(' ')[0]); // Handle date part only
        if (isNaN(date.getTime())) return null;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    } catch {
        return null;
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllInitialData(); // Use simplified fetch
    
    // Check for ID from route query or props
    const trainingId = route.query.id || props.id || props.meta?.id;
    
    if (trainingId) {
        // Wait a bit for data to be fully loaded
        setTimeout(() => {
            // Find the training with the specified ID
            // We need to look through all categories for the training
            for (const category of categoriesWithFilteredTrainings.value) {
                const training = category.trainings.find(t => t.id === Number(trainingId));
                if (training) {
                    // Find the category index to expand it
                    const categoryIndex = categoriesWithFilteredTrainings.value.findIndex(
                        cat => cat.id === category.id
                    );
                    if (categoryIndex !== -1) {
                        // Expand this category
                        expandedCategories.value[categoryIndex] = true;
                    }
                    // TODO: If there's a way to highlight or select a specific training, do it here
                    break;
                }
            }
        }, 500);
    }
});
</script>

<template>
    <div class="training-container">
        <v-container fluid class="pa-4">
            <!-- Header with title and actions -->
            <v-row class="mb-4 align-center">
                <v-col cols="auto">
                    <div class="d-flex align-center header-title">
                        <v-icon
                            icon="mdi-school"
                            size="28"
                            class="mr-3 text-primary header-icon"
                        ></v-icon>
                        <h1 class="text-h5 font-weight-medium mb-0">{{ t('trainingView.title') }}</h1>
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
                        {{ t('trainingView.description') }}
                    </v-alert>
                </v-col>
            </v-row>

            <!-- Action Bar -->
            <v-card class="action-bar mb-5" variant="outlined">
                <v-card-text class="py-3 px-4">
                    <div class="d-flex align-center flex-wrap">
                        <div class="actions-group">
                            <v-btn
                                v-if="canEdit"
                                @click="openNewTrainingDialog"
                                color="primary"
                                variant="elevated"
                                prepend-icon="mdi-plus-box-outline"
                                class="mr-3 mb-2 mb-md-0 action-button"
                            >
                                {{ t('trainingView.enterTraining') }}
                            </v-btn>
                            
                            <!-- Expand/Collapse All Buttons -->
                            <v-btn
                                @click="expandAllCategories"
                                color="blue-grey"
                                variant="tonal"
                                prepend-icon="mdi-unfold-more-horizontal"
                                class="mr-2 mb-2 mb-md-0 action-button"
                            >
                                {{ t('trainingView.expandAll') }}
                            </v-btn>
                            <v-btn
                                @click="collapseAllCategories"
                                color="blue-grey"
                                variant="tonal"
                                prepend-icon="mdi-unfold-less-horizontal"
                                class="mr-2 mb-2 mb-md-0 action-button"
                            >
                                {{ t('trainingView.collapseAll') }}
                            </v-btn>
                            <v-btn
                                @click="openExportDialog"
                                color="orange"
                                variant="tonal"
                                prepend-icon="mdi-table-arrow-right"
                                class="mb-2 mb-md-0 action-button"
                            >
                                {{ t('trainingView.export.button') }}
                            </v-btn>
                        </div>

                        <v-spacer class="d-none d-md-block"></v-spacer>

                        <v-text-field
                            v-model="search"
                            :label="t('trainingView.searchPlaceholder')"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-magnify"
                            hide-details
                            clearable
                            
                            color="primary"
                            class="search-field ml-auto mb-2 mb-md-0 mx-md-2"
                            style="max-width: 280px"
                        />
                    </div>
                </v-card-text>
            </v-card>

            <!-- Filter Section -->
            <v-card class="filter-card mb-5" variant="outlined">
                <v-card-text class="py-3 px-4">
                    <div class="d-flex align-center filter-title mb-2">
                        <v-icon icon="mdi-filter-variant" size="small" class="mr-2"></v-icon>
                        <span class="text-subtitle-2">Filter</span>
                    </div>

                    <v-row>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="selectedRanks"
                                :items="rankOptions"
                                item-title="name"
                                item-value="id"
                                label="Filter nach Rang"
                                multiple
                                chips
                                closable-chips
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details

                                color="primary"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="selectedCompanies"
                                :items="companyOptions"
                                item-title="name"
                                item-value="id"
                                label="Filter nach Abteilung"
                                multiple
                                chips
                                closable-chips
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details

                                color="primary"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <!-- Quick Assign Section -->
            <v-card v-if="canEdit" class="quick-assign-card mb-5" variant="outlined">
                <v-card-text class="py-3 px-4">
                    <div class="d-flex align-center filter-title mb-2">
                        <v-icon icon="mdi-lightning-bolt" size="small" class="mr-2"></v-icon>
                        <span class="text-subtitle-2">{{ t('trainingView.quickAssign') }}</span>
                    </div>

                    <v-row align="center">
                        <v-col cols="12" md="5">
                            <v-text-field
                                v-model="quickAssignNote"
                                :label="t('trainingView.quickAssignNote')"
                                :hint="t('trainingView.quickAssignHint')"
                                persistent-hint
                                variant="outlined"
                                density="compact"
                                prepend-inner-icon="mdi-note-text"
                                clearable
                                color="primary"
                            />
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model="quickAssignDate"
                                :label="t('trainingView.quickAssignDate')"
                                :hint="t('trainingView.quickAssignDateHint')"
                                persistent-hint
                                type="date"
                                variant="outlined"
                                density="compact"
                                prepend-inner-icon="mdi-calendar"
                                clearable
                                color="primary"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-alert
                                density="compact"
                                type="info"
                                variant="tonal"
                                class="text-caption"
                            >
                                {{ t('trainingView.quickAssignInfo') }}
                            </v-alert>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <!-- Loading State -->
            <div
                v-if="loadingData"
                class="loading-state d-flex flex-column align-center justify-center pa-10"
            >
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4 text-medium-emphasis">{{ t('trainingView.loading') }}</p>
            </div>

            <!-- Empty State -->
            <v-card
                v-else-if="categoriesWithFilteredTrainings.length === 0"
                class="empty-state-card pa-8 mb-3"
                variant="outlined"
            >
                <div class="d-flex flex-column align-center">
                    <v-icon
                        icon="mdi-school-outline"
                        size="64"
                        color="grey-darken-1"
                        class="mb-4"
                    ></v-icon>
                    <span class="text-h6 text-grey-darken-1">{{ t('trainingView.noData') }}</span>
                    <span class="text-body-2 text-grey-darken-3 mt-2">
                        {{ t('trainingView.noDataFilter') }}
                    </span>
                </div>
            </v-card>

            <!-- Training Categories (Collapsible) -->
            <template v-else>
                <v-card
                    v-for="(category, index) in categoriesWithFilteredTrainings"
                    :key="category.id"
                    class="category-card mb-5"
                    variant="outlined"
                >
                    <div 
                        class="category-header px-4 py-3"
                        @click="toggleCategoryExpansion(index)"
                    >
                        <div class="d-flex align-center justify-space-between cursor-pointer">
                            <h3 class="category-title">
                                <v-icon icon="mdi-certificate" size="small" class="mr-2"></v-icon>
                                {{ category.name }}
                                <v-chip
                                    size="x-small"
                                    color="primary"
                                    variant="flat"
                                    class="ml-2"
                                >{{ category.employees.length }}</v-chip>
                            </h3>
                            
                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                color="grey"
                                :ripple="false"
                                class="expand-button"
                            >
                                <v-icon>{{ expandedCategories[index] ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                            </v-btn>
                        </div>
                    </div>

                    <v-divider></v-divider>
                    
                    <v-expand-transition>
                        <v-card-text v-if="expandedCategories[index]" class="pa-0 category-content">
                            <!--
                                Ohne Spaltenauswahl: die Spalten sind hier die
                                Schulungen der Kategorie, nicht Felder eines
                                Datensatzes - abwaehlbar waeren sie sinnlos.
                            -->
                            <KTableToolbar :shown="(category.employees || []).length" />
                            <v-data-table
                                :headers="category.headers"
                                :items="category.employees"
                                class="training-table"
                                item-value="id"
                                density="comfortable"
                                :items-per-page="-1"
                                hide-default-footer
                            >
                                <template v-slot:item="{ item }">
                                    <tr>
                                        <td>
                                            {{ item.name }}
                                            <span class="text-caption text-grey"
                                                >({{ item.rank_name }})</span
                                            >
                                        </td>
                                        <td
                                            v-for="header in category.headers.slice(1)"
                                            :key="header.key"
                                            class="text-center"
                                        >
                                            <div
                                                v-if="item.trainingDetails[header.key]"
                                                class="training-completed d-flex align-center justify-center"
                                            >
                                                <v-tooltip location="top">
                                                    <template v-slot:activator="{ props: tooltipProps }">
                                                        <div class="d-flex align-center completed-training-cell">
                                                            <v-checkbox
                                                                v-if="canEdit"
                                                                v-bind="tooltipProps"
                                                                :model-value="true"
                                                                :loading="quickAssigningTraining?.employeeId === item.id && quickAssigningTraining?.trainingId === Number(header.key)"
                                                                :disabled="quickAssigningTraining !== null"
                                                                hide-details
                                                                density="compact"
                                                                color="success"
                                                                class="quick-assign-checkbox completed"
                                                                @update:model-value="deleteTrainingAssign(item.id, Number(header.key))"
                                                            />
                                                            <v-icon
                                                                v-else
                                                                v-bind="tooltipProps"
                                                                color="success"
                                                                class="status-icon mr-1"
                                                                size="small"
                                                            >
                                                                mdi-check-circle
                                                            </v-icon>
                                                            <span class="text-caption text-success">
                                                                {{
                                                                    formatDate(
                                                                        item.trainingDetails[header.key]
                                                                            .date
                                                                    )
                                                                }}
                                                            </span>
                                                        </div>
                                                    </template>
                                                    <div class="training-tooltip pa-2">
                                                        <div class="mb-1">
                                                            <strong>{{ t('trainingView.completedOn') }}:</strong>
                                                            {{
                                                                formatDate(
                                                                    item.trainingDetails[header.key]
                                                                        .date
                                                                )
                                                            }}
                                                        </div>
                                                        <div>
                                                            <strong>{{ t('trainingView.instructor') }}:</strong>
                                                            {{
                                                                item.trainingDetails[header.key]
                                                                    .instructor || '-'
                                                            }}
                                                        </div>
                                                        <div v-if="canEdit" class="mt-2 text-caption text-warning">
                                                            {{ t('trainingView.clickToRemove') }}
                                                        </div>
                                                    </div>
                                                </v-tooltip>
                                            </div>
                                            <div v-else class="training-missing">
                                                <v-tooltip v-if="canEdit" location="top">
                                                    <template v-slot:activator="{ props }">
                                                        <v-checkbox
                                                            v-bind="props"
                                                            :model-value="false"
                                                            :loading="quickAssigningTraining?.employeeId === item.id && quickAssigningTraining?.trainingId === Number(header.key)"
                                                            :disabled="quickAssigningTraining !== null"
                                                            hide-details
                                                            density="compact"
                                                            color="success"
                                                            class="quick-assign-checkbox"
                                                            @update:model-value="quickAssignTraining(item.id, Number(header.key))"
                                                        />
                                                    </template>
                                                    <span>{{ t('trainingView.clickToAssign') }}</span>
                                                </v-tooltip>
                                                <v-icon v-else color="error" class="status-icon"
                                                    >mdi-close-circle</v-icon
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template v-slot:no-data>
                                    <div class="empty-category pa-4 text-center text-grey">
                                        {{ t('trainingView.noEmployees') }}
                                    </div>
                                </template>
                            </v-data-table>
                        </v-card-text>
                    </v-expand-transition>
                </v-card>
            </template>

            <!-- New Training Dialog -->
            <v-dialog v-model="editDialog" persistent max-width="700px">
                <v-card class="dialog-card">
                    <v-toolbar color="primary" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon
                                icon="mdi-certificate-outline"
                                class="mr-2"
                                size="small"
                            ></v-icon>
                            {{ t('trainingView.newTraining') }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            icon="mdi-close"
                            @click="closeNewTrainingDialog"
                            size="small"
                        ></v-btn>
                    </v-toolbar>

                    <v-card-text class="pt-4">
                        <v-form ref="trainingFormRef" v-model="isTrainingFormValid">
                            <v-container>
                                <v-row>
                                    <v-col cols="12">
                                        <v-autocomplete
                                            v-model="newTraining.trainingId"
                                            :items="Trainings"
                                            item-title="name"
                                            item-value="id"
                                            label="Schulung(en)"
                                            required
                                            :rules="[multiSelectRequiredRule]"
                                            searchable
                                            clearable
                                            multiple
                                            chips
                                            closable-chips
                                            variant="outlined"
                                            density="comfortable"
                                            
                                            color="primary"
                                        ></v-autocomplete>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-autocomplete
                                            v-model="newTraining.employees"
                                            :items="sortedEmployees"
                                            item-title="name"
                                            item-value="id"
                                            label="Mitarbeiter"
                                            multiple
                                            chips
                                            searchable
                                            clearable
                                            closable-chips
                                            required
                                            :rules="[multiSelectRequiredRule]"
                                            variant="outlined"
                                            density="comfortable"

                                            color="primary"
                                        ></v-autocomplete>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="newTraining.date"
                                            label="Datum"
                                            type="date"
                                            required
                                            :rules="[requiredRule('Datum')]"
                                            variant="outlined"
                                            density="comfortable"
                                            
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="newTraining.notes"
                                            label="Ausbilder / Notiz"
                                            hint="Wer hat die Schulung gehalten?"
                                            persistent-hint
                                            variant="outlined"
                                            density="comfortable"
                                            
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeNewTrainingDialog" class="mr-2"
                            >{{ t('cancel') }}</v-btn
                        >
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveNewTraining"
                            :disabled="!isTrainingFormValid"
                            :loading="savingTraining"
                            class="action-button"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Export Dialog -->
            <v-dialog v-model="exportDialog" persistent max-width="900px">
                <v-card class="dialog-card">
                    <v-toolbar color="orange-darken-2" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon icon="mdi-table-arrow-right" class="mr-2" size="small"></v-icon>
                            {{ t('trainingView.export.title') }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeExportDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-card-text class="pt-4">
                        <v-row>
                            <!-- Left Column: Available Columns -->
                            <v-col cols="12" md="5">
                                <div class="text-subtitle-2 mb-2">{{ t('trainingView.export.availableColumns') }}</div>

                                <!-- Employee Fields -->
                                <v-expansion-panels variant="accordion" class="mb-3">
                                    <v-expansion-panel>
                                        <v-expansion-panel-title class="text-body-2">
                                            <v-icon icon="mdi-account" size="small" class="mr-2"></v-icon>
                                            {{ t('trainingView.export.employeeFields') }}
                                        </v-expansion-panel-title>
                                        <v-expansion-panel-text>
                                            <v-chip
                                                v-for="col in availableEmployeeColumns"
                                                :key="col.id"
                                                @click="addEmployeeColumn(col.id)"
                                                class="ma-1"
                                                size="small"
                                                variant="outlined"
                                                prepend-icon="mdi-plus"
                                            >
                                                {{ col.label }}
                                            </v-chip>
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>

                                    <!-- Training Categories -->
                                    <v-expansion-panel v-for="category in getTrainingCategories" :key="category">
                                        <v-expansion-panel-title class="text-body-2">
                                            <v-icon icon="mdi-school" size="small" class="mr-2"></v-icon>
                                            {{ category }}
                                            <v-btn
                                                size="x-small"
                                                variant="text"
                                                color="primary"
                                                class="ml-2"
                                                @click.stop="addAllTrainingsFromCategory(category)"
                                            >
                                                {{ t('trainingView.export.addAll') }}
                                            </v-btn>
                                        </v-expansion-panel-title>
                                        <v-expansion-panel-text>
                                            <v-chip
                                                v-for="training in availableTrainingColumns.filter(t => t.catName === category)"
                                                :key="training.id"
                                                @click="addTrainingColumn(training.trainingId)"
                                                class="ma-1"
                                                size="small"
                                                variant="outlined"
                                                prepend-icon="mdi-plus"
                                            >
                                                {{ training.label }}
                                            </v-chip>
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>
                                </v-expansion-panels>

                                <!-- Custom Column -->
                                <div class="text-subtitle-2 mb-2">{{ t('trainingView.export.customColumn') }}</div>
                                <v-text-field
                                    v-model="newCustomColumnLabel"
                                    :label="t('trainingView.export.columnName')"
                                    variant="outlined"
                                    density="compact"
                                    class="mb-2"
                                ></v-text-field>
                                <v-text-field
                                    v-model="newCustomColumnValue"
                                    :label="t('trainingView.export.columnValue')"
                                    :hint="t('trainingView.export.columnValueHint')"
                                    persistent-hint
                                    variant="outlined"
                                    density="compact"
                                    class="mb-2"
                                ></v-text-field>
                                <v-btn
                                    @click="addCustomColumn"
                                    :disabled="!newCustomColumnLabel.trim()"
                                    color="primary"
                                    variant="tonal"
                                    size="small"
                                    prepend-icon="mdi-plus"
                                    block
                                >
                                    {{ t('trainingView.export.addCustom') }}
                                </v-btn>
                            </v-col>

                            <!-- Right Column: Selected Columns & Options -->
                            <v-col cols="12" md="7">
                                <div class="text-subtitle-2 mb-2">{{ t('trainingView.export.selectedColumns') }}</div>

                                <v-list density="compact" class="selected-columns-list mb-3">
                                    <v-list-item
                                        v-for="(col, index) in selectedExportColumns"
                                        :key="col.id"
                                        class="px-2"
                                    >
                                        <template v-slot:prepend>
                                            <v-icon
                                                :icon="col.type === 'employee' ? 'mdi-account' : col.type === 'training' ? 'mdi-school' : 'mdi-pencil'"
                                                size="small"
                                                class="mr-2"
                                            ></v-icon>
                                        </template>
                                        <v-list-item-title class="text-body-2">
                                            {{ col.label }}
                                            <span v-if="col.type === 'custom' && col.customValue" class="text-caption text-grey ml-2">
                                                ({{ col.customValue }})
                                            </span>
                                        </v-list-item-title>
                                        <template v-slot:append>
                                            <v-btn
                                                icon="mdi-chevron-up"
                                                size="x-small"
                                                variant="text"
                                                :disabled="index === 0"
                                                @click="moveColumnUp(index)"
                                            ></v-btn>
                                            <v-btn
                                                icon="mdi-chevron-down"
                                                size="x-small"
                                                variant="text"
                                                :disabled="index === selectedExportColumns.length - 1"
                                                @click="moveColumnDown(index)"
                                            ></v-btn>
                                            <v-btn
                                                icon="mdi-close"
                                                size="x-small"
                                                variant="text"
                                                color="error"
                                                @click="removeExportColumn(index)"
                                            ></v-btn>
                                        </template>
                                    </v-list-item>
                                    <v-list-item v-if="selectedExportColumns.length === 0">
                                        <v-list-item-title class="text-body-2 text-grey">
                                            {{ t('trainingView.export.noColumnsSelected') }}
                                        </v-list-item-title>
                                    </v-list-item>
                                </v-list>

                                <!-- Export Options -->
                                <div class="text-subtitle-2 mb-2">{{ t('trainingView.export.options') }}</div>
                                <v-checkbox
                                    v-model="exportShowDate"
                                    :label="t('trainingView.export.showDate')"
                                    density="compact"
                                    hide-details
                                    class="mb-1"
                                ></v-checkbox>
                                <v-checkbox
                                    v-model="exportShowInstructor"
                                    :label="t('trainingView.export.showInstructor')"
                                    density="compact"
                                    hide-details
                                    class="mb-1"
                                ></v-checkbox>
                                <v-checkbox
                                    v-model="exportOnlyCompleted"
                                    :label="t('trainingView.export.onlyCompleted')"
                                    density="compact"
                                    hide-details
                                ></v-checkbox>

                                <v-alert
                                    type="info"
                                    variant="tonal"
                                    density="compact"
                                    class="mt-4 text-caption"
                                >
                                    {{ t('trainingView.export.info') }}
                                </v-alert>
                            </v-col>
                        </v-row>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-chip size="small" variant="tonal">
                            {{ categoriesWithFilteredTrainings.length > 0 ? categoriesWithFilteredTrainings[0].employees.length : 0 }} {{ t('trainingView.export.employees') }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeExportDialog" class="mr-2">
                            {{ t('cancel') }}
                        </v-btn>
                        <v-btn
                            color="orange"
                            variant="elevated"
                            @click="generateExportHTML"
                            :disabled="selectedExportColumns.length === 0"
                            :loading="exportingData"
                            prepend-icon="mdi-content-copy"
                            class="action-button"
                        >
                            {{ t('trainingView.export.copyToClipboard') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.training-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 10% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 90% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Header Styles */
.header-title {
    animation: fadeIn 0.5s ease-out;
}

.header-icon {
    filter: drop-shadow(0 2px 6px rgba(59, 130, 246, 0.4));
}

.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Action Bar & Filters */
.action-bar,
.filter-card,
.quick-assign-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.5s ease-out;
}

.action-bar {
    animation-delay: 0.1s;
}

.filter-card {
    animation-delay: 0.15s;
}

.quick-assign-card {
    animation-delay: 0.18s;
    border-color: rgba(34, 197, 94, 0.2);
}

.filter-title {
    color: var(--k-ink-muted);
}

.search-field {
    transition: all 0.3s ease;
}

.search-field:focus-within {
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.3);
}

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

/* Category Card */
.category-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    animation: fadeIn 0.5s ease-out;
    animation-delay: 0.2s;
    transition: all 0.3s ease;
}

.category-header {
    background: rgba(30, 41, 59, 0.4);
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.category-header:hover {
    background: rgba(30, 41, 59, 0.6);
}

.category-title {
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.expand-button {
    transition: transform 0.3s ease;
}

.cursor-pointer {
    cursor: pointer;
}

.category-content {
    animation: slideDown 0.3s ease-out;
}

/* Table Styles */
.training-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

:deep(.v-table .v-table__wrapper > table > thead > tr > th) {
    font-weight: 600;
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    padding: 12px 16px;
}

:deep(.v-table .v-table__wrapper > table > thead > tr > th:not(:last-child)) {
    border-right: thin solid rgba(255, 255, 255, 0.1);
}

:deep(.v-table .v-table__wrapper > table > tbody > tr > td) {
    padding: 12px 16px;
    border-bottom: thin solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
    transition: background 0.2s ease;
}

:deep(.v-table .v-table__wrapper > table > tbody > tr > td:not(:last-child)) {
    border-right: thin solid rgba(255, 255, 255, 0.05);
}

:deep(.v-table .v-table__wrapper > table > tbody > tr:hover > td) {
    background: rgba(30, 41, 59, 0.4) !important;
}

.status-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.3s ease;
}

:deep(.v-table .v-table__wrapper > table > tbody > tr:hover) .status-icon {
    transform: scale(1.2);
}

.training-tooltip {
    max-width: 300px;
    font-size: 0.9rem;
}

/* Loading & Empty States */
.loading-state,
.empty-state-card,
.empty-category {
    min-height: 200px;
}

.empty-state-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
}

/* Dialog styles */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-toolbar {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover)) !important;
}

/* Animation */
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

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Quick Assign Checkbox */
.quick-assign-checkbox {
    display: inline-flex;
    margin: 0;
}

.quick-assign-checkbox :deep(.v-selection-control) {
    min-height: unset;
}

.quick-assign-checkbox :deep(.v-selection-control__wrapper) {
    width: 24px;
    height: 24px;
}

.quick-assign-checkbox :deep(.v-checkbox-btn) {
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.quick-assign-checkbox:hover :deep(.v-checkbox-btn) {
    opacity: 1;
}

/* Completed training cell */
.completed-training-cell {
    cursor: pointer;
}

.quick-assign-checkbox.completed :deep(.v-checkbox-btn) {
    opacity: 1;
}

.quick-assign-checkbox.completed:hover :deep(.v-checkbox-btn) {
    opacity: 0.7;
}

/* Export Dialog */
.selected-columns-list {
    background: rgba(30, 41, 59, 0.4) !important;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
}

.selected-columns-list :deep(.v-list-item) {
    border-bottom: 1px solid var(--k-line);
}

.selected-columns-list :deep(.v-list-item:last-child) {
    border-bottom: none;
}

/* Responsive Adjustments */
@media (max-width: 960px) {
    .search-field {
        width: 100%;
        max-width: 100%;
    }
}
</style>