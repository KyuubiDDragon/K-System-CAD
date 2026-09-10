<script setup lang="ts">
import { ref, computed, onMounted, reactive, shallowRef, watch, defineAsyncComponent, type Ref, unref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import { useModulePermission } from '@/composables/useModulePermission'; // Permission checking
import type {
    Report,
    BaseReport,
    FireReport,
    PoliceReport,
    Category,
    Employee,
    ReportCode,
    ReportStatus,
} from '@/types/Report'; // Adjust paths if needed
import type { PersonFile } from '@/types/Person';
import type { Company } from '@/types/Company'; // Define or import Company type
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import ReportService from '@/services/ReportService';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
// --- Dynamic Component Imports --- (Use defineAsyncComponent for better performance)
const AuthorityAddReportDialog = defineAsyncComponent(
    () => import('@/components/Reports/Authority/Add.vue')
); // Adjust path
const AuthorityEditReportDialog = defineAsyncComponent(
    () => import('@/components/Reports/Authority/Edit.vue')
); // Adjust path
const ReportSharingDialog = defineAsyncComponent(
    () => import('@/components/Reports/ReportSharingDialog.vue')
); // Import our sharing dialog component
const SharedReportView = defineAsyncComponent(
    () => import('@/components/Reports/SharedReportView.vue')
);
// Define specific Add/Edit components if they differ significantly per authority, otherwise use the generic ones above
// const AddFireReport = defineAsyncComponent(() => import('@/components/Reports/Fire/Add.vue'));
// const EditFireReport = defineAsyncComponent(() => import('@/components/Reports/Fire/Edit.vue'));
// ... etc.

// --- Store, Router & Permissions ---
const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();
const { hasModulePermission, hasAllPermissions } = useModulePermission();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
  id?: string | number;
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  desktopWindow: false,
  id: undefined
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);
const canRead = computed(() => !!route.meta.canRead); // Assuming a read permission might exist

// --- Multi-Permission Check for Report Creation ---
// Creating a report requires WRITE_REPORT (to create) AND READ_EMPLOYEE (to select employee)
const canCreateReport = computed(() =>
  props.allPermissions ||
  hasAllPermissions.value ||
  (canEdit.value && hasModulePermission('employee', 'read'))
);

// --- Component State ---
const reports = ref<Report[]>([]); // Raw reports from API
const categories = ref<Category[]>([]);
const employees = ref<Employee[]>([]);
const persons = ref<PersonFile[]>([]); // Use correct type if available
const companies = ref<Company[]>([]); // Use correct type if available
const reportCodes = ref<ReportCode[]>([]);
const reportStatuses = ref<ReportStatus[]>([]); // Needed for status names if not in report data
const search = ref('');
const loadingInitialData = ref(false);
const deletingReport = ref(false);
const downloadingPdf = ref<number | null>(null); // Track which PDF is downloading
const pinningReport = ref<number | null>(null); // Track which report is being pinned
const openFilterPanels = ref([]); // Keep filters panel closed by default

// --- Dialog States ---
const newReportDialog = ref(false); // Controls Add component visibility
const editReportDialog = ref(false); // Controls Edit component visibility
const deleteReportDialog = ref(false);
const categorySelectionDialog = ref(false);
const sharingDialog = ref(false); // Controls Sharing component visibility
const currentReportForSharing = ref<number | null>(null); // Keep track of which report to share
const isAddingOrEditing = computed(() => newReportDialog.value || editReportDialog.value);

// --- Dialog Control (v-model for dynamic components) ---
const dialogControl = computed({
    get: () => newReportDialog.value || editReportDialog.value,
    set: value => {
        if (!value) {
            // Only react to closing
            closeAddEditDialog();
        }
        // Cannot directly set new/edit dialog based on this alone
    },
});

// --- Data for Dialogs ---
const reportToDelete = ref<FormattedReport | null>(null); // Use formatted type for display title
const editedReport = ref<Report | null>(null); // Data passed to Edit component
const selectedCategory = ref<number | null>(null); // Category ID selected for new report

// --- Filters ---
const filters = reactive({
    category_id: null as number | null,
    creator: null as number | null,
    filterType: 'all' as 'all' | 'own' | 'missing' | 'incomplete' | 'open' | 'pending', // Active filter button
});

// Custom field filters
const customFieldFilters = ref<Record<string, any>>({}); // Initialize as ref with Record type for type safety
const availableCustomFields = ref([]);

/**
 * Loads all available custom fields for filtering
 */
const loadAvailableCustomFields = async () => {
    try {
        const response = await apiClientAuth.get('/admin/report_fields.php?action=getReportFields');
        if (response && response.data) {
            availableCustomFields.value = response.data;
        }
    } catch (error) {
        console.error('Error loading custom fields for filtering:', error);
    }
};

// --- Snackbar ---
const errorSnackbar = ref({
    visible: false,
    message: '',
    color: 'error',
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const reportHeaders = computed(() => [
    { title: '', key: 'approved', sortable: true, width: '60px' }, // Sort by approved status
    { title: 'Bericht', key: 'report_info', sortable: true, sortKey: 'display_title' }, // Sort by display_title
    { title: 'Kategorie', key: 'cat_name', sortable: true },
    { title: 'Status', key: 'status_name', sortable: true },
    { title: 'Ersteller', key: 'emp_name', sortable: true },
    { title: 'Erstellt am', key: 'created_at', sortable: true, align: 'end' },
    { title: 'Letzte Änderung', key: 'updated_at', sortable: true, optional: true, align: 'end' },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end', width: '180px' },
]);

// --- Dynamic Component Logic ---
const dynamicReportComponent = computed(() => {
    if (newReportDialog.value) {
        // Determine ADD component based on authority - using generic for now
        return AuthorityAddReportDialog;
        // switch (authStore.user?.authority) {
        //   case 'fire': return AddFireReport;
        //   // ... other cases
        //   default: return AuthorityAddReportDialog;
        // }
    } else if (editReportDialog.value) {
        // Determine EDIT component based on authority - using generic for now
        return AuthorityEditReportDialog;
        // switch (authStore.user?.authority) {
        //   case 'fire': return EditFireReport;
        //   // ... other cases
        //   default: return AuthorityEditReportDialog;
        // }
    }
    return null; // No dialog active
});

const dynamicReportProps = computed(() => {
    const baseProps = {
        // Common props needed by both Add and Edit dialogs
        categories: Array.isArray(categories.value) ? categories.value : [],
        employees: Array.isArray(employees.value) ? employees.value : [],
        persons: Array.isArray(persons.value) ? persons.value : [],
        companies: Array.isArray(companies.value) ? companies.value : [],
        reportCodes: Array.isArray(reportCodes.value) ? reportCodes.value : [],
        reportStatuses: Array.isArray(reportStatuses.value) ? reportStatuses.value : [],
        authority: authStore.user?.authority || 'default',
    };

    if (newReportDialog.value) {
        return {
            ...baseProps,
            title: t('newReport'), // Generic title, specific component might override
            selectedCategory: selectedCategory.value, // Pass selected category ID
        };
    } else if (editReportDialog.value) {
        return {
            ...baseProps,
            title: 'Bericht bearbeiten', // Generic title
            reportToEdit: editedReport.value, // Pass report data
        };
    }
    return {}; // No props needed if no dialog is active
});

// --- Data Fetching ---
const fetchData = async (
    endpoint: string,
    refToUpdate: any,
    errorMessage: string,
    mapFn?: (item: any) => any
) => {
    try {
        const response = await apiClientAuth.get<any[]>(`/report/?action=${endpoint}`); // Assuming base endpoint, adjust if needed
        const data = response.data || [];
        refToUpdate.value = mapFn ? data.map(mapFn) : data;
    } catch (error: any) {
        console.error(`Error fetching ${endpoint}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        refToUpdate.value = []; // Clear data on error
    }
};

const fetchAllInitialData = async () => {
    loadingInitialData.value = true;
    await Promise.all([
        fetchData('getReports', reports, 'Fehler beim Laden der Berichte.'),
        fetchData('getCategories', categories, 'Fehler beim Laden der Kategorien.'),
        fetchData('getEmployees', employees, 'Fehler beim Laden der Mitarbeiter.', emp => ({
            ...emp,
            display_name: `[${emp.servicenumber}] ${emp.name}`,
        })),
        // Use correct endpoints for persons/companies if different from report/
        fetchData('getPersons', persons, 'Fehler beim Laden der Personen.', person => ({
            ...person,
            fullname: `${person.firstname || ''} ${person.lastname || ''}`.trim(),
        })), // Example endpoint change
        fetchData('getOnlyCompanies', companies, 'Fehler beim Laden der Firmen.', undefined), // Example endpoint change
        fetchData('getCodes', reportCodes, 'Fehler beim Laden der Berichtscodes.'),
        fetchData('getStatuses', reportStatuses, 'Fehler beim Laden der Berichtsstatus.'),
    ]);
    loadingInitialData.value = false;
};

// Update fetchDataSimple for better handling of non-array responses
const fetchDataSimple = async <T>(
    action: string,
    refToUpdate: Ref<T[]>,
    errorMessage: string,
    mapFn?: (item: any) => T,
    baseEndpoint = '/report'
) => {
    try {
        const response = await apiClientAuth.get<any>(`${baseEndpoint}/?action=${action}`);
        
        // Handle both direct arrays and nested objects
        let data = response.data;
        
        // Direct null/undefined check
        if (data === null || data === undefined) {
            refToUpdate.value = [];
            return;
        }
        
        // Check if data is in a nested structure (common API pattern)
        if (typeof data === 'object' && !Array.isArray(data)) {
            // Try to extract data from common property names based on the action
            if (action === 'getEmployees' && data.employees) {
                data = data.employees;
            } else if (action === 'getReports' && data.reports) {
                data = data.reports;
            } else if (action === 'getCategories' && data.categories) {
                data = data.categories;
            } else if (action === 'getPersons' && data.persons) {
                data = data.persons;
            } else if (action === 'getOnlyCompanies' && data.companies) {
                data = data.companies;
            } else {
                // Try common property names as fallback
                const keys = ['data', 'items', 'results', 'list', action.replace('get', '').toLowerCase()];
                for (const key of keys) {
                    if (data[key] && Array.isArray(data[key])) {
                        data = data[key];
                        break;
                    }
                }
            }
        }
        
        // Final safety check - ensure we have an array to work with
        if (!Array.isArray(data)) {
            // Try to convert non-array data to an array if possible
            if (data && typeof data === 'object') {
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    // Object might be array-like with numeric keys
                    const convertedArray = Object.values(data);
                    data = convertedArray;
                } else {
                    // Last resort - wrap the object in an array
                    data = [data];
                }
            } else {
                // Nothing we can do, use empty array
                data = [];
            }
        }
        
        // Apply mapping function if provided
        if (mapFn && Array.isArray(data)) {
            try {
                const mappedData = data.map(mapFn);
                refToUpdate.value = mappedData;
            } catch (mapError) {
                console.error(`Error mapping ${action} data:`, mapError);
                refToUpdate.value = [];
                showSnackbar(`Fehler beim Formatieren der ${action}-Daten.`, 'error');
            }
        } else {
            refToUpdate.value = data;
        }
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        refToUpdate.value = [];
    }
};

const fetchAllInitialDataSimplified = async () => {
    loadingInitialData.value = true;
    await Promise.all([
        fetchDataSimple<Report>('getReports', reports, 'Fehler beim Laden der Berichte.'),
        fetchDataSimple<Category>('getCategories', categories, 'Fehler beim Laden der Kategorien.'),
        fetchDataSimple<Employee>(
            'getEmployees',
            employees,
            'Fehler beim Laden der Mitarbeiter.',
            emp => ({ ...emp, display_name: `[${emp.servicenumber}] ${emp.name}` })
        ),
        fetchDataSimple<PersonFile>(
            'getPersons',
            persons,
            'Fehler beim Laden der Personen.',
            person => ({
                ...person,
                fullname: `${person.firstname || ''} ${person.lastname || ''}`.trim(),
            }),
            'personfile'
        ),
        fetchDataSimple<Company>(
            'getOnlyCompanies',
            companies,
            'Fehler beim Laden der Firmen.',
            undefined,
            'company'
        ),
        fetchDataSimple<ReportCode>(
            'getCodes',
            reportCodes,
            'Fehler beim Laden der Berichtscodes.'
        ),
        fetchDataSimple<ReportStatus>(
            'getStatuses',
            reportStatuses,
            'Fehler beim Laden der Berichtsstatus.'
        ),
    ]);
    loadingInitialData.value = false;
};

// --- Computed Properties ---
const currentUserId = computed(() => authStore.user?.linked_employee ?? null);

// Extend Report type for display purposes
interface FormattedReport extends Report {
    id: number; // Explicitly define id property
    report_code_name: string;
    formatted_report_date: string;
    display_title: string;
    pinned: boolean; // Ensure boolean
    updated_at: string; // Explicitly define updated_at property
    created_at: string; // Explicitly define created_at property
    location?: string; // Add location property as optional
    approved: boolean; // Explicitly define approved property
    missing_employees: number[]; // Array of employee IDs that need to provide input
}

// Filtering logic
const filteredReports = computed(() => {
    const filtered = reports.value.filter((report) => {
        // Filter by type
        if (
            filters.filterType === 'own' &&
            report.creator !== currentUserId.value
        ) {
            return false;
        }

        if (
            filters.filterType === 'missing' &&
            (!report.missing_employees || report.missing_employees.length === 0)
        ) {
            return false;
        }

        if (
            filters.filterType === 'incomplete' &&
            (!report.missing_employees || report.missing_employees.length === 0)
        ) {
            return false;
        }

        if (
            filters.filterType === 'open' &&
            report.approved !== false
        ) {
            return false;
        }

        if (
            filters.filterType === 'pending' &&
            (!report.missing_employees || report.missing_employees.length > 0) &&
            report.approved !== false
        ) {
            return false;
        }

        // Filter by search
        if (search.value) {
            const searchLower = search.value.toLowerCase();
            const titleMatch = report.title && report.title.toLowerCase().includes(searchLower);
            const locationMatch =
                report.location && report.location.toLowerCase().includes(searchLower);
            const codeMatch = report.report_code_id && String(report.report_code_id).toLowerCase().includes(searchLower);

            if (!titleMatch && !locationMatch && !codeMatch) {
                return false;
            }
        }

        // Filter by category
        if (
            filters.category_id &&
            Number(report.category_id) !== Number(filters.category_id)
        ) {
            return false;
        }

        // Filter by creator
        if (
            filters.creator &&
            Number(report.creator) !== Number(filters.creator)
        ) {
            return false;
        }
        
        // Filter by custom fields
        const customFields = Object.keys(customFieldFilters.value);
        if (customFields.length > 0) {
            // Check if we have active filters (non-empty values)
            const activeFilters = customFields.filter(fieldName => {
                const filterValue = customFieldFilters.value[fieldName];
                return filterValue !== null && filterValue !== undefined && filterValue !== '' &&
                       !(Array.isArray(filterValue) && filterValue.length === 0);
            });
            
            // If no active filters are set, don't filter out any reports
            if (activeFilters.length === 0) {
                return true;
            }
            
            // Parse custom_fields if it's a string
            let parsedCustomFields = null;
            if (typeof report.custom_fields === 'string') {
                try {
                    parsedCustomFields = JSON.parse(report.custom_fields);
                } catch (e) {
                    console.error(`Error parsing custom_fields for report ${report.id}:`, e);
                    return false;
                }
            } else if (report.custom_fields === null || report.custom_fields === undefined) {
                return false;
            } else {
                parsedCustomFields = report.custom_fields;
            }
            
            // Check each active filter
            for (const fieldName of activeFilters) {
                const filterValue = customFieldFilters.value[fieldName];
                
                // For JSON object format: {"fieldName": "value"}
                const fieldValue = parsedCustomFields[fieldName];
                
                // Skip this report if field doesn't exist
                if (fieldValue === undefined) {
                    return false;
                }
                
                // Handle different filter types
                if (typeof filterValue === 'string') {
                    // Text search - case insensitive contains
                    if (filterValue.trim() !== '' && 
                        (!fieldValue || !String(fieldValue).toLowerCase().includes(filterValue.toLowerCase()))) {
                        return false;
                    }
                } else if (typeof filterValue === 'boolean') {
                    // Boolean comparison
                    if (fieldValue !== filterValue) {
                        return false;
                    }
                } else if (typeof filterValue === 'number') {
                    // Number comparison
                    if (Number(fieldValue) !== filterValue) {
                        return false;
                    }
                } else if (Array.isArray(filterValue) && filterValue.length > 0) {
                    // Multiselect - check if any filter value matches
                    const fieldValueArray = Array.isArray(fieldValue) ? fieldValue : 
                                          (typeof fieldValue === 'string' ? fieldValue.split(',') : [fieldValue]);
                    
                    if (!filterValue.some(val => fieldValueArray.includes(val))) {
                        return false;
                    }
                }
            }
            
            return true;
        }

        return true;
    });

    return filtered;
});

// Korrigiere die formattedFilteredReports computed-Property
const formattedFilteredReports = computed(() => filteredReports.value);

// Füge eine separate Funktion hinzu, die die Berichte mit Sharing-Informationen anreichert
const fetchReportsWithSharingInfo = async () => {
    try {
        console.log('Enhancing all reports with sharing info');
        // Füge für jeden Bericht Sharing-Informationen hinzu
        const promises = reports.value.map(async (report) => {
            // Prüfe, ob der Bericht geteilt wurde, ohne UI zu aktualisieren
            try {
                const sharingInfo = await ReportService.getReportSharing(report.id);
                if (sharingInfo?.shared_with?.length > 0) {
                    // Bericht wurde geteilt
                    return {
                        ...report,
                        shared_with: sharingInfo.shared_with
                    };
                }
            } catch (error) {
                console.log(`No sharing info for report ${report.id}`);
            }
            return report;
        });
        
        const reportsWithSharing = await Promise.all(promises);
        reports.value = reportsWithSharing;
        console.log('Reports enhanced with sharing info');
    } catch (error) {
        console.error('Error enhancing reports with sharing info:', error);
    }
};

const reportCounts = computed(() => {
    const userId = currentUserId.value;
    const all = reports.value.length;
    const own = userId ? reports.value.filter(r => r.creator === userId).length : 0;
    const missing = userId
        ? reports.value.filter(r => r.missing_employees?.includes(userId)).length
        : 0;
    const incomplete = reports.value.filter(r => (r.missing_employees?.length ?? 0) > 0).length;
    const open = reports.value.filter(r => !r.approved).length;
    const pending = reports.value.filter(
        r => (r.missing_employees?.length ?? 0) === 0 && !r.approved
    ).length;
    return { all, own, missing, incomplete, open, pending };
});

const employeesForFilter = computed(() => {
    // Add an 'All' option or handle null explicitly if needed
    return employees.value; //.map(emp => ({...emp, display_name: `[${emp.servicenumber}] ${emp.name}`})); // Mapping done in fetch
});

const getMissingEmployeeNames = (employeeIds: number[]): string[] => {
    if (!employeeIds || employeeIds.length === 0) return [];
    return employeeIds
        .map(id => employees.value.find(emp => emp.id === id)?.name || `ID: ${id}`)
        .filter(name => name !== undefined) as string[];
};

// --- Methods ---

// Dialog Openers
const openCategorySelectionDialog = () => {
    selectedCategory.value = null; // Reset selection
    categorySelectionDialog.value = true;
};

const createNewReport = () => {
    if (!selectedCategory.value) {
        showSnackbar(t('reportView.pleaseSelectCategory'), 'error');
        return; // Should be disabled, but check anyway
    }
    categorySelectionDialog.value = false;
    editedReport.value = null; // Ensure not in edit mode
    
    // Make sure all necessary data is loaded and is in array format
    if (!Array.isArray(categories.value) || categories.value.length === 0) {
        fetchDataSimple<Category>('getCategories', categories, 'Fehler beim Laden der Kategorien.');
    }
    
    if (!Array.isArray(employees.value) || employees.value.length === 0) {
        fetchDataSimple<Employee>(
            'getEmployees',
            employees,
            'Fehler beim Laden der Mitarbeiter.',
            emp => ({ ...emp, display_name: `[${emp.servicenumber}] ${emp.name}` })
        );
    }
    
    if (!Array.isArray(reportCodes.value) || reportCodes.value.length === 0) {
        fetchDataSimple<ReportCode>(
            'getCodes',
            reportCodes,
            'Fehler beim Laden der Berichtscodes.'
        );
    }
    
    if (!Array.isArray(reportStatuses.value) || reportStatuses.value.length === 0) {
        fetchDataSimple<ReportStatus>(
            'getStatuses',
            reportStatuses,
            'Fehler beim Laden der Berichtsstatus.'
        );
    }
    
    // Add a small delay to ensure data is loaded
    setTimeout(() => {
        newReportDialog.value = true; // Open the Add dialog/component
    }, 100);
};

const openEditReportDialog = async (report: Report | FormattedReport) => {
    // Unterscheide zwischen normalen und geteilten Berichten
    const isSharedReport = activeTab.value === 'shared-with-me' || 
        (activeTab.value === 'all-shared' && report.sharing_type === 'shared_with_me');
    
    if (isSharedReport) {
        // Bei geteilten Berichten nutzen wir die SharedReportView-Komponente (nur Lesemodus)
        try {
            // Zeige Loading-Status
            loadingInitialData.value = true;
            
            // Lade den vollständigen geteilten Bericht über die spezielle API
            const fullReport = await ReportService.getSharedReport(report.id);
            
            // Sicherstellen, dass alle erforderlichen Felder vorhanden sind
            const sanitizedReport = {
                ...fullReport,
                // Bewahre die Sharing-Informationen vom ursprünglichen Objekt
                source_authority: fullReport.source_authority || report.source_authority || '',
                source_authority_display: fullReport.source_authority_display || report.source_authority_display || '',
                source_authority_id: fullReport.source_authority_id || report.source_authority_id || 0,
                access_level: fullReport.access_level || 'read', // Nutze den Wert aus dem geteilten Bericht oder Fallback
                shared_at: fullReport.shared_at || report.shared_at || '',
                // Stelle sicher, dass Basis-Felder existieren
                title: fullReport.title || report.title || t('sharedReport.noTitle'),
                description: fullReport.description || '',
                text: fullReport.text || '',
                category_name: fullReport.category_name || report.category_name || '',
                status_name: fullReport.status_name || report.status_name || '',
                creator_name: fullReport.creator_name || report.creator_name || '',
                creator_display_name: fullReport.creator_display_name || t('sharedReport.unknownCreator'),
            };
            
            // Setze den aufbereiteten Bericht
            sharedReportToView.value = sanitizedReport;
            
            // Öffne den SharedReportView Dialog
            sharedReportDialog.value = true;
        } catch (error) {
            console.error('Error loading shared report details:', error);
            showSnackbar('Fehler beim Laden der Berichtsdetails.', 'error');
        } finally {
            loadingInitialData.value = false;
        }
    } else {
        // Für normale Berichte finden wir das Original
        const rawReport = reports.value.find(r => r.id === report.id);
        if (!rawReport) {
            showSnackbar('Berichtsdaten nicht gefunden.', 'error');
            return;
        }
        editedReport.value = { ...rawReport }; // Pass copy of raw data
        
        // Öffne den normalen Edit-Dialog
        newReportDialog.value = false; // Ensure not in add mode
        editReportDialog.value = true; // Open the Edit dialog/component
    }
    
    // Protokolliere den Zugriff
    logAccess('reports', report.id);
};

const openDeleteReportDialog = (report: FormattedReport) => {
    reportToDelete.value = report; // Store formatted report for display title in dialog
    deleteReportDialog.value = true;
};

// Dialog Closers
const closeAddEditDialog = () => {
    newReportDialog.value = false;
    editReportDialog.value = false;
    editedReport.value = null;
    selectedCategory.value = null; // Reset category selection as well
};

const closeDeleteDialog = () => {
    deleteReportDialog.value = false;
    reportToDelete.value = null;
};

// Event Handlers from Child Components
const onReportAdded = () => {
    closeAddEditDialog();
    showSnackbar(t('reportView.reportAddedSuccess'), 'success');
    fetchAllInitialDataSimplified(); // Refresh all data as IDs/relations might have changed
};

const onReportUpdated = () => {
    closeAddEditDialog();
    showSnackbar(t('reportView.reportUpdatedSuccess'), 'success');
    fetchAllInitialDataSimplified(); // Refresh all data
};

// Report Actions
const logAccess = async (table: string, entryId: number) => {
    try {
        await apiClientAuth.post('/logging/?action=logAccess', { table, entry_id: entryId });
        console.log('Access logged successfully for table:', table, 'entry ID:', entryId);
    } catch (error: any) {
        console.error('Error logging access:', error.response?.data?.error || error.message);
        // Optionally show a snackbar message for logging failure? Usually silent failure is okay.
    }
};

const confirmDeleteReport = async () => {
    if (!reportToDelete.value) return;
    deletingReport.value = true;
    try {
        // Use the ID from the stored reportToDelete object
        await apiClientAuth.post('/report/?action=deleteReport', { id: reportToDelete.value.id });
        await fetchAllInitialDataSimplified(); // Refresh data
        closeDeleteDialog();
        showSnackbar(t('reportView.reportDeletedSuccess'), 'success');
    } catch (error: any) {
        console.error('Error deleting report:', error);
        showSnackbar(error.response?.data?.error || t('reportView.reportDeleteError'), 'error');
    } finally {
        deletingReport.value = false;
    }
};

const togglePinReport = async (report: FormattedReport) => {
    pinningReport.value = report.id;
    const newPinnedStatus = !report.pinned;
    try {
        // Send new status to backend
        await apiClientAuth.post('/report/?action=pinReport', {
            id: report.id,
            pinned: newPinnedStatus,
        });
        // Optimistic update or refetch
        const index = reports.value.findIndex(r => r.id === report.id);
        if (index > -1) {
            // Update the original reports array, computed property will recalculate
            reports.value[index].pinned = newPinnedStatus;
        }
        // Or uncomment below to refetch the whole list
        // await fetchAllInitialDataSimplified();
        showSnackbar(newPinnedStatus ? t('reportView.reportPinned') : t('reportView.reportUnpinned'), 'success');
    } catch (error: any) {
        console.error('Error pinning report:', error);
        showSnackbar(
            error.response?.data?.error || t('reportView.reportPinError'),
            'error'
        );
    } finally {
        pinningReport.value = null;
    }
};

const downloadReportPdf = async (reportId: number) => {
    downloadingPdf.value = reportId;
    try {
        const response = await apiClientAuth.get(
            `/report/?action=getReportPdf&report_id=${reportId}`,
            { responseType: 'blob' }
        );
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        // Find report title for filename, fallback if not found
        const report = formattedFilteredReports.value.find(r => r.id === reportId);
        const filename = report
            ? `Bericht_${report.display_title.replace(/[^a-z0-9]/gi, '_')}.pdf`
            : `report_${reportId}.pdf`;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url); // Clean up
    } catch (error: any) {
        console.error('Error downloading PDF:', error);
        showSnackbar(error.response?.data?.error || t('reportView.pdfDownloadError'), 'error');
    } finally {
        downloadingPdf.value = null;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Ungültiges Datum';
        // Include time as well?
        return date.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            // hour: '2-digit', minute: '2-digit' // Uncomment to add time
        });
    } catch (e) {
        return 'Fehler Datum';
    }
};

// Validation Rule (already defined but keep for clarity)
const requiredRule = (value: any) => !!value || t('requiredField');

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllInitialDataSimplified();
    // Load available custom fields for filtering
    await loadAvailableCustomFields();
    
    // Verbessere alle Berichte mit Sharing-Informationen
    await fetchReportsWithSharingInfo();
    
    // Also fetch shared reports
    await fetchSharedWithMeReports();
    
    // Check if we need to open a specific report from query parameter or props
    const reportId = route.query.id || props.id || props.meta?.id || props.meta?.reportId;
    console.log('[ReportView] Checking for report ID in query/props:', reportId);
    console.log('[ReportView] ID sources - route.query.id:', route.query.id, 'props.id:', props.id, 'props.meta?.id:', props.meta?.id);
    
    if (reportId) {
        // Find the report in the loaded data
        const report = reports.value.find(r => r.id === Number(reportId));
        console.log('[ReportView] Found report:', report);
        if (report) {
            // Open the view dialog for this report
            console.log('[ReportView] Opening edit dialog for report:', report.id);
            openEditReportDialog(report);
        } else {
            console.log('[ReportView] Report not found with ID:', reportId);
        }
    }
});

// Watch customFieldFilters for changes
watch(customFieldFilters, () => {
    // Wir brauchen keine neue Serveranfrage, da wir die Daten bereits lokal haben
    // Die computed property filteredReports reagiert automatisch auf Änderungen
}, { deep: true });

const filterReportsByStatus = (report: FormattedReport, status: ReportStatus): boolean => {
    switch (status) {
        case 'approved':
            return report.approved === true;
        case 'open':
            return report.approved === false;
        case 'pending':
            return (!Array.isArray(report.missing_employees) || report.missing_employees.length === 0) 
                && report.approved === false;
        default:
            return true; // 'all'
    }
};

// Function to open sharing dialog
const openSharingDialog = (report: Report) => {
    currentReportForSharing.value = report.id;
    sharingDialog.value = true;
};

// Handle sharing update
const handleSharingUpdated = () => {
    fetchAllInitialDataSimplified(); // Refresh reports after sharing updated
    showSnackbar('Sharing settings updated successfully', 'success');
};

// Add view mode state
const activeTab = ref('all'); // 'all', 'shared-by-me', 'shared-with-me'

// Add refs for the new shared reports
const sharedByMeReports = ref<Report[]>([]);
const sharedWithMeReports = ref<any[]>([]);
const loadingSharedByMe = ref(false);
const loadingSharedWithMe = ref(false);

// Add new methods for fetching shared reports
const fetchSharedWithMeReports = async (updateUI = true) => {
    if (updateUI) loadingSharedWithMe.value = true;
    try {
        console.log('Fetching reports shared with me');
        const sharedReports = await ReportService.getSharedReports();
        console.log('Received shared reports:', sharedReports);
        sharedWithMeReports.value = sharedReports;
        return sharedReports;
    } catch (error) {
        console.error('Error fetching reports shared with me:', error);
        if (updateUI) showSnackbar('Fehler beim Laden der geteilten Berichte.', 'error');
        return [];
    } finally {
        if (updateUI) loadingSharedWithMe.value = false;
    }
};

const fetchSharedByMeReports = async (updateUI = true) => {
    if (updateUI) loadingSharedByMe.value = true;
    try {
        // Nutze die bereits geladenen Reports statt fetchReports aufzurufen
        if (reports.value.length === 0) {
            await fetchAllInitialDataSimplified();
        }
        
        // Dann filtere die, die geteilt wurden (mit shared_with Einträgen)
        const myReportsWithSharing = await Promise.all(
            reports.value.map(async (report) => {
                try {
                    const sharingInfo = await ReportService.getReportSharing(report.id);
                    // Nur Berichte einschließen, die mit anderen geteilt wurden
                    if (sharingInfo.shared_with && sharingInfo.shared_with.length > 0) {
                        return {
                            ...report,
                            shared_with: sharingInfo.shared_with
                        };
                    }
                    return null;
                } catch (error) {
                    console.error(`Error checking sharing for report ${report.id}:`, error);
                    return null;
                }
            })
        );
        
        // Filtere nulls (Berichte, die nicht geteilt sind)
        const filtered = myReportsWithSharing.filter(report => report !== null);
        sharedByMeReports.value = filtered;
        console.log('Reports shared by me:', sharedByMeReports.value);
        return filtered;
    } catch (error) {
        console.error('Error fetching reports shared by me:', error);
        if (updateUI) showSnackbar('Fehler beim Laden Ihrer geteilten Berichte.', 'error');
        return [];
    } finally {
        if (updateUI) loadingSharedByMe.value = false;
    }
};

// Füge eine neue ref für alle geteilten Berichte hinzu (nach den anderen shared refs)
const allSharedReports = ref<any[]>([]);
const loadingAllShared = ref(false);

// Füge eine neue Methode zum Laden aller geteilten Berichte hinzu
const fetchAllSharedReports = async () => {
    loadingAllShared.value = true;
    try {
        // Lade nur die von uns geteilten Berichte
        await fetchSharedByMeReports(false);
        
        // Verwende nur die von uns geteilten Berichte
        allSharedReports.value = sharedByMeReports.value.map(report => ({
            ...report,
            sharing_type: 'shared_by_me',
            sharing_text: `Mit ${report.shared_with?.map(sw => sw.display_name || sw.name).join(', ')} geteilt`
        }));
        
        console.log('All outgoing shared reports:', allSharedReports.value);
    } catch (error) {
        console.error('Error fetching outgoing shared reports:', error);
        showSnackbar('Fehler beim Laden der von uns geteilten Berichte.', 'error');
    } finally {
        loadingAllShared.value = false;
    }
};

// Tracking der Tab-Änderung, um Sharing-Infos zu laden
watch(activeTab, async (newTab, oldTab) => {
    if (newTab === 'all' && oldTab !== 'all') {
        // Wenn zu "Alle Berichte" gewechselt wird, Sharing-Infos laden
        await fetchReportsWithSharingInfo();
    }
});

// Füge neue Refs für die SharedReportView hinzu
const sharedReportDialog = ref(false);
const sharedReportToView = ref(null);

// Funktion, die aufgerufen wird, wenn ein geteilter Bericht aktualisiert wurde
const onSharedReportUpdated = () => {
    closeSharedReportDialog();
    showSnackbar('Geteilter Bericht erfolgreich aktualisiert.', 'success');
    fetchAllInitialDataSimplified(); // Aktualisiere die Daten
};

// Funktion zum Schließen des SharedReportView-Dialogs
const closeSharedReportDialog = () => {
    sharedReportDialog.value = false;
    sharedReportToView.value = null;
};

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('ReportView', () => unref(reportHeaders) as any);


/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(kFilters.filtered.value) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'berichte' });
}

/**
 * Die Tabelle zeigt je nach Reiter eine andere Quelle. Damit Filter und
 * Zaehler nicht viermal dieselbe Fallunterscheidung wiederholen, steht sie
 * hier einmal.
 */
const kReportRows = computed<any[]>(() => {
    switch (activeTab.value) {
        case 'shared-with-me': return (sharedWithMeReports.value as any[]) ?? [];
        case 'shared-by-me': return (sharedByMeReports.value as any[]) ?? [];
        case 'all-shared': return (allSharedReports.value as any[]) ?? [];
        default: return (formattedFilteredReports.value as any[]) ?? [];
    }
});

/**
 * Kategorie, Status und Ersteller sind gepflegte Stammdaten - genau die
 * Felder, nach denen man einen Bericht sucht, wenn man seinen Titel nicht
 * mehr weiss.
 */
const kFilters = useTableFilters(
    () => kReportRows.value,
    [
        {
            key: 'approved',
            label: t('reportView.filterApproved'),
            test: (r: any) => Number(r.approved) === 1,
        },
        {
            key: 'open',
            label: t('reportView.filterOpen'),
            test: (r: any) => Number(r.approved) !== 1,
        },
    ],
    [
        { field: 'cat_name', label: t('reportView.category'), emptyLabel: t('reportView.withoutCategory') },
        { field: 'status_name', label: t('reportView.status'), emptyLabel: t('reportView.withoutStatus') },
        { field: 'emp_name', label: t('reportView.creator'), emptyLabel: t('reportView.withoutCreator') },
    ],
);

</script>

<template>
    <div>
        <ErrorSnackbar v-model="errorSnackbar" />
        <v-container fluid class="main-container pa-4">
            <v-row class="mb-4">
                <v-col cols="12">
                    <div class="page-header d-flex align-center justify-space-between flex-wrap">
                        <div>
                            <h1 class="text-h4 font-weight-medium mb-2">
                                <v-icon size="36" class="mr-2">mdi-file-document-outline</v-icon>
                                {{ $t('reportView.title') }}
                            </h1>
                            <p class="text-body-1 text-medium-emphasis">
                                {{ $t('reportView.subtitle') }}
                            </p>
                        </div>

                        <v-btn
                            v-if="canCreateReport && !isAddingOrEditing"
                            @click="openCategorySelectionDialog"
                            color="primary"
                            variant="elevated"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            {{ $t('newReport') }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>

            <!-- Filter Buttons -->
            <v-card v-if="!isAddingOrEditing" class="filter-card mb-4" elevation="4">
                <v-toolbar flat density="comfortable" color="transparent" class="px-4 py-2">
                    <v-toolbar-title class="text-subtitle-1">
                        <v-icon start size="18" class="mr-1">mdi-filter-variant</v-icon>
                        {{ $t('reportView.filter') }}
                    </v-toolbar-title>
                </v-toolbar>

                <v-divider></v-divider>

                <v-card-text class="filter-options py-3">
                    <v-btn-toggle
                        v-model="filters.filterType"
                        mandatory
                        color="primary"
                        variant="outlined"
                        divided
                        class="filter-toggle mb-3"
                    >
                        <v-btn value="all" class="filter-button">{{ $t('reportView.all') }} ({{ reportCounts.all }})</v-btn>
                        <v-btn value="own" class="filter-button">{{ $t('reportView.own') }} ({{ reportCounts.own }})</v-btn>
                        <v-btn value="missing" class="filter-button"
                            >{{ $t('reportView.missing') }} ({{ reportCounts.missing }})</v-btn
                        >
                        <v-btn value="incomplete" class="filter-button"
                            >{{ $t('reportView.incomplete') }} ({{ reportCounts.incomplete }})</v-btn
                        >
                        <v-btn value="open" class="filter-button"
                            >{{ $t('reportView.open') }} ({{ reportCounts.open }})</v-btn
                        >
                        <v-btn value="pending" class="filter-button"
                            >{{ $t('reportView.pending') }} ({{ reportCounts.pending }})</v-btn
                        >
                    </v-btn-toggle>

                    <v-row dense>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="search"
                                :label="$t('reportView.searchPlaceholder')"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                hide-details
                                clearable
                                class="search-field"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="filters.category_id"
                                :items="categories"
                                item-title="name"
                                item-value="id"
                                :label="$t('reportView.department')"
                                clearable
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="filters.creator"
                                :items="employeesForFilter"
                                item-title="display_name"
                                item-value="id"
                                :label="$t('reportView.creator')"
                                clearable
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                hide-details
                            />
                        </v-col>
                    </v-row>
                    
                    <!-- Custom Fields Filter -->
                    <v-expansion-panels v-model="openFilterPanels" v-if="availableCustomFields.length > 0" class="mt-4" variant="accordion">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="custom-field-filters-title">
                                <v-icon size="small" class="mr-2">mdi-form-select</v-icon>
                                {{ $t('reportView.customFields') }} ({{ availableCustomFields.length }})
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <v-row dense>
                                    <v-col v-for="field in availableCustomFields" :key="field.id" cols="12" md="4">
                                        <!-- Text Field Filter -->
                                        <v-text-field
                                            v-if="field.field_type === 'text' || field.field_type === 'textarea'"
                                            v-model="customFieldFilters[field.field_name]"
                                            :label="field.field_label"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />

                                        <!-- Number Field Filter -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'number'"
                                            v-model.number="customFieldFilters[field.field_name]"
                                            type="number"
                                            :label="field.field_label"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />

                                        <!-- Date Field Filter -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'date'"
                                            v-model="customFieldFilters[field.field_name]"
                                            type="date"
                                            :label="field.field_label"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />

                                        <!-- Boolean Field Filter -->
                                        <v-select
                                            v-else-if="field.field_type === 'boolean'"
                                            v-model="customFieldFilters[field.field_name]"
                                            :items="[{text: 'Ja', value: true}, {text: 'Nein', value: false}]"
                                            item-title="text"
                                            item-value="value"
                                            :label="field.field_label"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />

                                        <!-- Select Field Filter -->
                                        <v-select
                                            v-else-if="field.field_type === 'select'"
                                            v-model="customFieldFilters[field.field_name]"
                                            :items="field.options || []"
                                            :label="field.field_label"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />

                                        <!-- Multiselect Field Filter -->
                                        <v-select
                                            v-else-if="field.field_type === 'multiselect'"
                                            v-model="customFieldFilters[field.field_name]"
                                            :items="field.options || []"
                                            :label="field.field_label"
                                            multiple
                                            chips
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            hide-details
                                        />
                                    </v-col>
                                </v-row>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card-text>
            </v-card>

            <!-- Tabs für normale/geteilte Berichte -->
            <v-card v-if="!isAddingOrEditing" class="tabs-card mb-4" elevation="4">
                <v-tabs v-model="activeTab" color="primary" centered grow>
                    <v-tab value="all">{{ $t('reportView.allReports') }}</v-tab>
                    <v-tab value="shared-with-me" @click="fetchSharedWithMeReports()">{{ $t('reportView.sharedWithMe') }}</v-tab>
                    <v-tab value="shared-by-me" @click="fetchSharedByMeReports()">{{ $t('reportView.sharedByMe') }}</v-tab>
                    <v-tab value="all-shared" @click="fetchAllSharedReports()">{{ $t('reportView.allShared') }}</v-tab>
                </v-tabs>
            </v-card>

            <!-- Haupttabelle -->
            <v-card v-if="!isAddingOrEditing" class="main-card" elevation="4">
                <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                <KTableToolbar
                    :filters="kFilters"
                    :columns="kCols"
                    :shown="kFilters.filtered.value.length"
                    :total="kReportRows.length"
                    :noun="t('reportView.noun')"
                />
                <v-data-table
                    :headers="kCols.visible.value"
                    :items="kFilters.filtered.value"
                    :search="search"
                    :items-per-page="25"
                    item-value="id"
                    :loading="activeTab === 'all' 
                        ? loadingInitialData 
                        : (activeTab === 'shared-with-me' 
                            ? loadingSharedWithMe 
                            : (activeTab === 'shared-by-me' 
                                ? loadingSharedByMe 
                                : loadingAllShared))"
                    hover
                    density="comfortable"
                    class="data-table"
                    v-model="kSelected"
                    show-select
                >
                    <template v-slot:[`item.approved`]="{ item }">
                        <div class="d-flex align-center">
                            <v-tooltip location="top">
                                <template v-slot:activator="{ props }">
                                    <v-icon
                                        v-bind="props"
                                        :color="item.approved ? 'success' : 'warning'"
                                        size="small"
                                    >
                                        {{
                                            item.approved
                                                ? 'mdi-check-circle'
                                                : 'mdi-alert-circle-outline'
                                        }}
                                    </v-icon>
                                </template>
                                <span>{{ item.approved ? 'Abgeschlossen' : 'Offen' }}</span>
                            </v-tooltip>
                            <v-tooltip v-if="item.pinned" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-icon v-bind="props" color="info" size="small" class="ml-1"
                                        >mdi-pin</v-icon
                                    >
                                </template>
                                <span>Angepinnt</span>
                            </v-tooltip>
                        </div>
                    </template>

                    <template v-slot:[`item.report_info`]="{ item }">
                        <span @click="openEditReportDialog(item)" class="report-title-link">
                            {{ item.title || t('sharedReport.noTitle') }}
                            <v-tooltip v-if="item.missing_employees && item.missing_employees.length > 0" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-icon color="orange" size="x-small" class="ml-1" v-bind="props">
                                        mdi-account-alert
                                    </v-icon>
                                </template>
                                <span>
                                    Fehlende Ergänzungen:
                                    {{ getMissingEmployeeNames(item.missing_employees).join(', ') }}
                                </span>
                            </v-tooltip>
                            
                            <!-- Anzeige für mit mir geteilte Berichte -->
                            <v-tooltip v-if="(activeTab === 'shared-with-me' || (activeTab === 'all-shared' && item.sharing_type === 'shared_with_me')) && item.source_authority" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-icon color="primary" size="x-small" class="ml-1" v-bind="props">
                                        mdi-arrow-down-left
                                    </v-icon>
                                </template>
                                <span>Geteilt von: {{ item.source_authority_display || item.source_authority }}</span>
                            </v-tooltip>
                            
                            <!-- Anzeige für von mir geteilte Berichte - auch unter "Alle Berichte" -->
                            <v-tooltip v-if="(activeTab === 'all' || activeTab === 'shared-by-me' || activeTab === 'all-shared') && item.shared_with && item.shared_with.length > 0" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-icon color="success" size="x-small" class="ml-1" v-bind="props">
                                        mdi-arrow-up-right
                                    </v-icon>
                                </template>
                                <span>Geteilt mit: {{ Array.isArray(item.shared_with) ? item.shared_with.map(sw => sw.display_name || sw.name).join(', ') : 'Unbekannt' }}</span>
                            </v-tooltip>
                            
                            <!-- Für den Tab "Alle Geteilten" zusätzliche Informationen -->
                            <small v-if="activeTab === 'all-shared' && item.sharing_text" class="ml-2 text-grey">
                                ({{ item.sharing_text }})
                            </small>
                        </span>
                    </template>

                    <template v-slot:[`item.cat_name`]="{ item }">
                        {{ categories.find(c => c.id === item.category_id)?.name || '-' }}
                    </template>

                    <template v-slot:[`item.emp_name`]="{ item }">
                        {{ employees.find(e => e.id === item.creator)?.name || '-' }}
                    </template>

                    <template v-slot:[`item.created_at`]="{ item }">
                        {{ formatDate(item.created_at) }}
                    </template>

                    <template v-slot:[`item.updated_at`]="{ item }">
                        {{ formatDate(item.updated_at) }}
                    </template>

                    <template v-slot:[`item.actions`]="{ item }">
                        <div class="action-buttons">
                            <v-tooltip :text="canEdit ? $t('reportView.edit') : $t('reportView.view')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit || canRead"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openEditReportDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">{{ canEdit ? 'mdi-file-document-edit-outline' : 'mdi-eye-outline' }}</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="$t('reportView.shareReport')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit && (hasModulePermission('report', 'write') || hasAllPermissions.value)"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openSharingDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-share-variant</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="$t('delete')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canDelete"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openDeleteReportDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                        color="error"
                                    >
                                        <v-icon size="small">mdi-delete</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="$t('reportView.downloadPdf')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="downloadReportPdf(item.id)"
                                        v-bind="props"
                                        :loading="downloadingPdf === item.id"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-download</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip
                                :text="item.pinned ? t('unpin') : t('pin')"
                                location="top"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="togglePinReport(item)"
                                        v-bind="props"
                                        :loading="pinningReport === item.id"
                                        class="action-icon"
                                        :color="item.pinned ? 'info' : ''"
                                    >
                                        <v-icon size="small">
                                            {{ item.pinned ? 'mdi-pin-off' : 'mdi-pin' }}
                                        </v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </template>

                    <template v-slot:no-data>
                        <div class="empty-state">
                            <v-icon size="64" color="grey-darken-1" class="mb-3">
                                mdi-file-document-outline
                            </v-icon>
                            <p class="text-h6 text-grey">
                                {{ $t('reportView.noData') }}
                            </p>
                        </div>
                    </template>

                    <template v-slot:loading>
                        <div class="loading-state">
                            <v-progress-circular indeterminate color="primary" size="32" class="mr-3">
                            </v-progress-circular>
                            <span>{{ $t('reportView.loading') }}</span>
                        </div>
                    </template>
                </v-data-table>
                <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                <KBulkBar
                    :count="kSelected.length"
                    :shown="kFilters.filtered.value.length"
                    :total="kReportRows.length"
                    @clear="kSelected = []"
                >
                    <template #actions>
                        <v-btn variant="outlined" size="small" @click="kExportSelection">
                            {{ t('kTable.exportSelection') }}
                        </v-btn>
                    </template>
                </KBulkBar>
            </v-card>

            <!-- Kategorie Auswahl Dialog -->
            <v-dialog
                v-model="categorySelectionDialog"
                max-width="500"
                persistent
                class="dialog-container"
            >
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon color="primary" class="mr-2">mdi-folder-outline</v-icon>
                        {{ $t('reportView.selectCategory') }}
                    </v-card-title>

                    <v-card-text class="pt-4">
                        <v-select
                            :label="$t('reportView.reportCategory')"
                            v-model="selectedCategory"
                            :items="categories"
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
                        <v-btn variant="text" @click="categorySelectionDialog = false">Abbrechen</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="createNewReport"
                            :disabled="!selectedCategory"
                            class="action-button"
                        >
                            {{ $t('reportView.continue') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Dynamische Komponente für Add/Edit - Always rendered but controlled by dialogControl -->
            <component
                v-if="dynamicReportComponent"
                :is="dynamicReportComponent"
                v-bind="dynamicReportProps"
                :modelValue="dialogControl"
                @update:modelValue="dialogControl = $event"
                @report-added="onReportAdded"
                @report-updated="onReportUpdated"
                @close="closeAddEditDialog"
            />

            <!-- Delete Confirmation Dialog -->
            <v-dialog v-model="deleteReportDialog" max-width="500" persistent class="delete-dialog">
                <v-card class="dialog-card">
                    <v-card-title class="text-h5 dialog-title">
                        <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                        {{ $t('reportView.deleteReport') }}
                    </v-card-title>

                    <v-card-text class="pt-4">
                        <p>
                            {{ $t('reportView.confirmDeleteReport', { title: reportToDelete?.display_title }) }}
                        </p>
                        <div class="text-caption text-medium-emphasis mt-2">
                            {{ $t('reportView.deleteWarning') }}
                        </div>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteDialog" class="mr-2">
                            {{ $t('cancel') }}
                        </v-btn>
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDeleteReport"
                            :loading="deletingReport"
                            class="delete-button"
                        >
                            <v-icon start>mdi-delete</v-icon>
                            {{ $t('delete') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Report sharing dialog - Always rendered but controlled by v-model -->
            <ReportSharingDialog
                v-model="sharingDialog"
                :report-id="currentReportForSharing"
                @sharing-updated="handleSharingUpdated"
            />

            <!-- Shared Report View Dialog -->
            <v-dialog
                v-model="sharedReportDialog"
                fullscreen
                transition="dialog-bottom-transition"
                :scrim="false"
                :retain-focus="false"
            >
                <v-card class="report-dialog-card">
                    <v-toolbar color="primary" density="comfortable">
                        <v-btn icon @click="sharedReportDialog = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Geteilter Bericht</v-toolbar-title>
                        <v-spacer></v-spacer>
                    </v-toolbar>
                    
                    <v-card-text class="pa-6">
                        <SharedReportView
                            v-if="sharedReportToView"
                            :report="sharedReportToView"
                            :persons="persons"
                            :companies="companies"
                            :employees="employees"
                            :categories="categories"
                            @close="sharedReportDialog = false"
                        />
                    </v-card-text>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
/* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */

.main-container {
    min-height: 89vh;
    background-color: var(--k-canvas);
    color: #e2e8f0;
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--k-line);
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

/* Cards */
.filter-card,
.main-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Filter Section */
.filter-toggle {
    width: 100%;
    overflow-x: auto;
    flex-wrap: nowrap;
}

.filter-button {
    flex-shrink: 0;
    min-width: auto;
    white-space: nowrap;
}

.filter-options {
    background-color: var(--k-surface);
}

.search-field {
    transition: all var(--transition-timing);
}

.search-field:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Data Table */
.data-table {
    border-radius: 8px;
    overflow: hidden;
}

.report-title-link {
    font-weight: 500;
    color: var(--k-accent);
    cursor: pointer;
    transition: all var(--transition-timing);
}

.report-title-link:hover {
    text-decoration: underline;
    color: var(--k-accent-line);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 4px;
}

.action-icon {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty & Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styling */
.dialog-container :deep(.v-overlay__content),
.delete-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent));
    color: var(--k-ink);
    padding: 16px;
}

.delete-dialog .dialog-title {
    background: linear-gradient(90deg, #991b1b, #dc2626);
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.delete-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .page-header .action-button {
        align-self: stretch;
    }

    .filter-toggle {
        overflow-x: auto;
        padding-bottom: 8px;
    }

    .action-buttons {
        justify-content: flex-end;
    }
}

@media (max-width: 960px) {
    /* Verbesserte Mobilansicht für die Filter-Sektion */
    .filter-options .v-col {
        margin-bottom: 12px;
    }
}

/* Add styling for tabs-card near the bottom after all other card styles */
.tabs-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Shared Report Dialog */
.report-dialog-card {
    background: rgba(0, 0, 0, 0.5);
}

.report-dialog-card .dialog-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.report-dialog-card .dialog-title {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent));
    color: var(--k-ink);
    padding: 16px;
}
</style>
