<script setup lang="ts">
import { defineComponent, ref, computed, watch, type PropType } from 'vue';
import type { Report, Employee, Category, ReportCode, ReportStatus } from '@/types/Report';
import type { PersonFile } from '@/types/Person';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useRouter } from 'vue-router';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

interface Props {
  modelValue?: boolean
  authority?: string
  reportToEdit?: Report | null
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'reportUpdated', 'close']);

const dialog = computed({
    get: () => props.modelValue,
    set: value => {
        emit('update:modelValue', value);
    },
});

// Dynamische Felder basierend auf der Authority festlegen
const getAuthoritySpecificFields = (authority: string) => {
    switch (authority) {
        case 'fire':
            return { causeOfFire: '', fireIntensity: 0 };
        case 'police':
        case 'test':
            return { crimeTime: '' };
        default:
            return {};
    }
};

// Add missing refs and types
const form = ref();
const router = useRouter();
const customFields = ref<any[]>([]);
const customFieldValues = ref<Record<string, any>>({});
const selectedMissingEmployees = ref<number[]>([]);
const selectedLinkedPersons = ref<number[]>([]);
const selectedLinkedCompanies = ref<number[]>([]);

// Update Report type to include authority-specific fields
interface ExtendedReport extends Report {
    causeOfFire?: string;
    fireIntensity?: number;
    crimeTime?: string;
}

// Update report ref
const report = ref<ExtendedReport>({
    ...props.reportToEdit,
    ...getAuthoritySpecificFields(props.authority),
} as ExtendedReport);

const statuses = ref<ReportStatus[]>([]);
const employees = ref<Employee[]>([]);
const categories = ref<Category[]>([]);
const reportCodes = ref<ReportCode[]>([]);
const additionals = ref<any[]>([]);
const selectedAdditionals = ref<any[]>([]);
const persons = ref<PersonFile[]>([]);

// Add loading state
const isLoading = ref(false);
const loadingSteps = ref({
    categories: false,
    employees: false,
    persons: false,
    companies: false,
    reportCodes: false,
    statuses: false,
    customFields: false
});

// Update loadCustomFields function
const loadCustomFields = async () => {
    if (!report.value?.category_id) {
        customFields.value = [];
        loadingSteps.value.customFields = false;
        return;
    }
    
    try {
        loadingSteps.value.customFields = true;
        const response = await apiClientAuth.get('/admin/report_fields.php', {
            params: { 
                action: 'getFieldsByCategory',
                category_id: report.value.category_id
            }
        });
        
        customFields.value = response.data;
        console.log('Loaded custom fields:', customFields.value);
        
        // If we have custom fields in the report data, set their values FIRST
        if (props.reportToEdit?.custom_fields) {
            let existingCustomFields = {};
            
            if (typeof props.reportToEdit.custom_fields === 'string') {
                try {
                    existingCustomFields = JSON.parse(props.reportToEdit.custom_fields);
                } catch (e) {
                    console.error('Error parsing custom fields:', e);
                    existingCustomFields = {};
                }
            } else {
                existingCustomFields = props.reportToEdit.custom_fields || {};
            }
            
            console.log('Existing custom fields from report:', existingCustomFields);
            
            // Set values from the report
            Object.entries(existingCustomFields).forEach(([fieldName, value]) => {
                customFieldValues.value[fieldName] = value;
            });
        }
        
        // THEN initialize any missing fields with default values
        customFields.value.forEach(field => {
            if (!(field.field_name in customFieldValues.value)) {
                if (field.field_type === 'boolean') {
                    customFieldValues.value[field.field_name] = false;
                } else if (field.field_type === 'multiselect') {
                    customFieldValues.value[field.field_name] = [];
                } else {
                    customFieldValues.value[field.field_name] = '';
                }
            }
        });
        
        console.log('Final customFieldValues after loading:', customFieldValues.value);
    } catch (error) {
        console.error('Fehler beim Laden der benutzerdefinierten Felder:', error);
    } finally {
        loadingSteps.value.customFields = false;
    }
};

// Update fetch functions to handle loading states
const fetchStatuses = async () => {
    try {
        loadingSteps.value.statuses = true;
        const response = await apiClientAuth.get('/report/?action=getStatuses');
        console.log('Statuses response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Status data is null or undefined');
            statuses.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.statuses) {
                data = data.statuses;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Status data is not an array after processing:', data);
            statuses.value = [];
            return;
        }
        
        statuses.value = data.map((item: { id: string | number, [key: string]: any }) => ({
            ...item,
            id: Number(item.id),
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Status:', error);
        statuses.value = [];
    } finally {
        loadingSteps.value.statuses = false;
    }
};

const fetchAdditionals = async () => {
    try {
        console.log('Starting additionals fetch in Edit component...');
        const response = await apiClientAuth.get('/report/?action=getAdditionals');
        console.log('Additionals response in Edit component:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Additionals data is null or undefined');
            additionals.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.additionals) {
                data = data.additionals;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Additionals data is not an array after processing:', data);
            additionals.value = [];
            return;
        }
        
        additionals.value = data.map((item: { id: string | number, [key: string]: any }) => ({
            ...item,
            id: Number(item.id),
        }));
        console.log('Additionals loaded successfully in Edit component:', additionals.value.length, 'items');
    } catch (error) {
        console.error('Fehler beim Laden der Zusatzleistungen in Edit component:', error);
        additionals.value = [];
    }
};

const fetchEmployees = async () => {
    try {
        const response = await apiClientAuth.get('/report/?action=getEmployees');
        console.log('Employees response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Employees data is null or undefined');
            employees.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.employees) {
                data = data.employees;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Employees data is not an array after processing:', data);
            employees.value = [];
            return;
        }
        
        employees.value = data.map((emp: Employee) => ({
            ...emp,
            id: Number(emp.id),
            display_name: `[${emp.servicenumber}] ${emp.name}`,
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Mitarbeiter:', error);
        employees.value = [];
    }
};

const fetchCategories = async () => {
    try {
        const response = await apiClientAuth.get('/report/?action=getCategories');
        console.log('Categories response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Categories data is null or undefined');
            categories.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.categories) {
                data = data.categories;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Categories data is not an array after processing:', data);
            categories.value = [];
            return;
        }
        
        categories.value = data;
    } catch (error) {
        console.error('Fehler beim Laden der Kategorien:', error);
        categories.value = [];
    }
};

const fetchReportCodes = async () => {
    try {
        const response = await apiClientAuth.get('/report/?action=getCodes');
        console.log('Report codes response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Report codes data is null or undefined');
            reportCodes.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.codes) {
                data = data.codes;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Report codes data is not an array after processing:', data);
            reportCodes.value = [];
            return;
        }
        
        reportCodes.value = data.map((code: { id: string | number, [key: string]: any }) => ({
            ...code,
            id: Number(code.id),
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Report Codes:', error);
        reportCodes.value = [];
    }
};

const fetchPersons = async () => {
    try {
        const response = await apiClientAuth.get('/personfile/?action=getPersons');
        console.log('Persons response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Persons data is null or undefined');
            persons.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.persons) {
                data = data.persons;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Persons data is not an array after processing:', data);
            persons.value = [];
            return;
        }
        
        persons.value = data.map((person: PersonFile) => ({
            ...person,
            id: Number(person.id),
            fullname: `${person.firstname} ${person.lastname}`,
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Personen:', error);
        persons.value = [];
    }
};

const companies = ref<any[]>([]);
const fetchCompanies = async () => {
    try {
        const response = await apiClientAuth.get('/company/?action=getOnlyCompanies');
        console.log('Companies response:', response.data);
        
        // Ensure data is in array format
        let data = response.data;
        if (!data) {
            console.warn('Companies data is null or undefined');
            companies.value = [];
            return;
        }
        
        // Handle nested data structure
        if (typeof data === 'object' && !Array.isArray(data)) {
            if (data.companies) {
                data = data.companies;
            } else if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                // Try to convert to array if object with numeric keys
                const objKeys = Object.keys(data);
                if (objKeys.length > 0 && !isNaN(Number(objKeys[0]))) {
                    data = Object.values(data);
                }
            }
        }
        
        // Ensure we have an array
        if (!Array.isArray(data)) {
            console.warn('Companies data is not an array after processing:', data);
            companies.value = [];
            return;
        }
        
        companies.value = data.map((company: { id: string | number, name: string, [key: string]: any }) => ({
            ...company,
            id: Number(company.id),
            name: company.name,
        }));
    } catch (error: any) {
        console.error(
            'Fehler beim Abrufen der Unternehmen:',
            error.response?.data?.error || error.message
        );
        companies.value = [];
    }
};

// Update und Validation Methoden
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

const updateReport = async () => {
    try {
        isLoading.value = true;

        // Prepare custom fields data
        const customFieldsData = {};
        console.log('Current customFieldValues before update:', customFieldValues.value);
        
        Object.keys(customFieldValues.value).forEach(fieldName => {
            const field = customFields.value.find(f => f.field_name === fieldName);
            if (field) {
                const value = customFieldValues.value[fieldName];
                customFieldsData[fieldName] = value;
            }
        });
        
        console.log('Prepared customFieldsData for update:', customFieldsData);

        // Create a new report object to avoid modifying the original
        const updatedReport = {
            ...report.value,
            custom_fields: customFieldsData,
            additionals: selectedAdditionals.value,
            report_status_id: report.value.report_status_id,
        };

        console.log('Sending report data:', updatedReport); // Debug log

        const response = await apiClientAuth.post('/report/index.php?action=editReport', updatedReport);
        console.log('Response from server:', response.data);
        
        emit('reportUpdated');
        closeDialog();
    } catch (error) {
        console.error('Fehler beim Speichern des Berichts:', error);
        showErrorSnackbar('Fehler beim Speichern des Berichts');
    } finally {
        isLoading.value = false;
    }
};

const closeDialog = () => {
    isLoading.value = false;
    emit('update:modelValue', false);
    emit('close');
    dialog.value = false;
};

const updateCalculations = () => {
    selectedAdditionals.value = [...selectedAdditionals.value];
};

const addNewAdditional = () => {
    selectedAdditionals.value.push({
        id: null,
        price: 0.0,
        units: 1,
        amount: 1,
    });
};

const removeAdditional = (index :number) => {
    selectedAdditionals.value.splice(index, 1);
    updateCalculations();
};

const totalCost = computed(() => {
    return selectedAdditionals.value
        .reduce((acc, additional) => acc + additional.price * additional.amount, 0)
        .toFixed(2);
});

const totalUnits = computed(() => {
    return selectedAdditionals.value.reduce(
        (acc, additional) => acc + additional.units * additional.amount,
        0
    );
});

const onAdditionalSelected = (index:number, selectedId:number) => {
    const additional = additionals.value.find(item => item.id === selectedId);

    if (additional) {
        selectedAdditionals.value[index].id = additional.id;
        selectedAdditionals.value[index].price = additional.price;
        selectedAdditionals.value[index].units = additional.units;
        selectedAdditionals.value[index].amount = 1;
    }
};

// Initialisiere Daten beim Öffnen des Dialogs
watch(
    () => dialog.value,
    newVal => {
        if (newVal) {
            console.log("Dialog opened, checking if data needs to be fetched");
            
            // Always fetch additionals first as they're needed for the form
            console.log("Fetching additionals");
            fetchAdditionals();
            
            // Only fetch missing data
            if (categories.value.length === 0) {
                console.log("Fetching categories");
                fetchCategories();
            }
            
            if (employees.value.length === 0) {
                console.log("Fetching employees");
                fetchEmployees();
            }
            
            if (persons.value.length === 0) {
                console.log("Fetching persons");
                fetchPersons();
            }
            
            if (companies.value.length === 0) {
                console.log("Fetching companies");
                fetchCompanies();
            }
            
            if (reportCodes.value.length === 0) {
                console.log("Fetching report codes");
                fetchReportCodes();
            }
            
            if (statuses.value.length === 0) {
                console.log("Fetching statuses");
                fetchStatuses();
            }

            // Always load custom fields when dialog opens
            console.log("Loading custom fields");
            loadCustomFields();
        }
    },
    { immediate: true } // Make the watcher run immediately when component is mounted
);

const currentTab = ref(0); // currentTab hier definieren

// Add computed property for overall loading state
const isDataLoading = computed(() => {
    return Object.values(loadingSteps.value).some(step => step === true);
});

// Update handleSubmit function
const handleSubmit = async () => {
    if (!form.value.validate()) return;

    try {
        isLoading.value = true;

        // Prepare custom fields data
        const customFieldsData = {};
        console.log('Current customFieldValues before submit:', customFieldValues.value);
        
        Object.keys(customFieldValues.value).forEach(fieldName => {
            const field = customFields.value.find(f => f.field_name === fieldName);
            if (field) {
                const value = customFieldValues.value[fieldName];
                customFieldsData[fieldName] = value;
            }
        });
        
        console.log('Prepared customFieldsData for submission:', customFieldsData);

        // Create a new report object to avoid modifying the original
        const updatedReport = {
            ...report.value,
            custom_fields: customFieldsData,
            missing_employees: selectedMissingEmployees.value,
            linked_persons: selectedLinkedPersons.value,
            linked_companies: selectedLinkedCompanies.value,
            additionals: selectedAdditionals.value
        };

        console.log('Sending report data:', updatedReport); // Debug log

        const response = await apiClientAuth.post('/report/index.php?action=editReport', updatedReport);
        console.log('Response from server:', response.data);

        if (response.data.success) {
            showSuccessSnackbar('Bericht erfolgreich aktualisiert');
            closeDialog();
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren des Berichts:', error);
        showErrorSnackbar('Fehler beim Aktualisieren des Berichts');
    } finally {
        isLoading.value = false;
    }
};

// Add snackbar functions
const showSuccessSnackbar = (message: string) => {
    // Implement your snackbar logic here
    console.log('Success:', message);
};

const showErrorSnackbar = (message: string) => {
    // Implement your snackbar logic here
    console.error('Error:', message);
};

// Add watcher for dialog state
watch(
    () => dialog.value,
    (newVal) => {
        if (!newVal) {
            isLoading.value = false;
        }
    }
);
</script>

<template>
    <v-card v-if="reportToEdit" class="report-card" elevation="4">
        <v-overlay
            :model-value="isDataLoading"
            class="align-center justify-center"
            contained
        >
            <v-progress-circular
                indeterminate
                size="64"
                color="primary"
            ></v-progress-circular>
            <div class="mt-4 text-center">
                <div v-if="loadingSteps.categories">{{ $t('loadingCategories') }}</div>
                <div v-if="loadingSteps.employees">{{ $t('loadingEmployees') }}</div>
                <div v-if="loadingSteps.persons">{{ $t('loadingPersons') }}</div>
                <div v-if="loadingSteps.companies">{{ $t('loadingCompanies') }}</div>
                <div v-if="loadingSteps.reportCodes">{{ $t('loadingReportCodes') }}</div>
                <div v-if="loadingSteps.statuses">{{ $t('loadingStatuses') }}</div>
                <div v-if="loadingSteps.customFields">{{ $t('loadingCustomFields') }}</div>
            </div>
        </v-overlay>

        <v-toolbar flat density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-file-document-edit</v-icon>
                Bericht bearbeiten
            </v-toolbar-title>
            <v-spacer />
            <add-shortcut-button
                v-if="reportToEdit && reportToEdit.id"
                type="report"
                :resource-id="reportToEdit.id"
                :title="reportToEdit.title || 'Bericht'"
                :subtitle="reportToEdit.location || undefined"
                icon="mdi-file-chart"
                color="orange"
            />
        </v-toolbar>

        <v-tabs v-model="currentTab" bg-color="transparent" slider-color="primary" class="custom-tabs">
            <v-tab :value="1" class="custom-tab">
                <v-icon start size="16">mdi-information-outline</v-icon>
                Bericht Infos
            </v-tab>
            <v-tab :value="2" class="custom-tab">
                <v-icon start size="16">mdi-text-box-outline</v-icon>
                Text Feld
            </v-tab>
            <v-tab :value="3" class="custom-tab">
                <v-icon start size="16">mdi-plus-circle-outline</v-icon>
                Zusätze
            </v-tab>
        </v-tabs>

        <v-window v-model="currentTab" class="window-content">
            <!-- Tab 1: Bericht Infos -->
            <v-window-item :value="1">
                <v-form ref="form" class="pa-4">
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-account-details</v-icon>
                            Grunddaten
                        </div>
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-select
                                    label="Ersteller"
                                    v-model="report.creator"
                                    :items="employees"
                                    item-title="display_name"
                                    item-value="id"
                                    required
                                    :rules="[requiredRule('Ersteller')]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-select
                                    label="Kategorie"
                                    v-model="report.category_id"
                                    :items="categories"
                                    item-title="name"
                                    item-value="id"
                                    required
                                    :rules="[requiredRule('Kategorie')]"
                                    :disabled="true"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-select
                                    label="Status"
                                    v-model="report.report_status_id"
                                    :items="statuses"
                                    item-title="name"
                                    item-value="id"
                                    required
                                    :rules="[requiredRule('Status')]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                ></v-select>
                            </v-col>
                        </v-row>
                    </div>

                    <div class="form-section mb-4" v-if="authority === 'fire' || authority === 'police' || authority === 'test'">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-shield-alert-outline</v-icon>
                            Behördenspezifische Daten
                        </div>
                        <v-row>
                            <template v-if="authority === 'fire'">
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        label="Brandursache"
                                        v-model="report.causeOfFire"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prepend-inner-icon="mdi-fire"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        label="Brandintensität"
                                        type="number"
                                        v-model.number="report.fireIntensity"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prepend-inner-icon="mdi-thermometer-high"
                                    ></v-text-field>
                                </v-col>
                            </template>

                            <template v-if="authority === 'police' || authority === 'test'">
                                <v-col cols="12">
                                    <v-text-field
                                        label="Tatzeit"
                                        v-model="report.crimeTime"
                                        type="datetime-local"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prepend-inner-icon="mdi-calendar-clock"
                                    ></v-text-field>
                                </v-col>
                            </template>
                        </v-row>
                    </div>

                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-file-document-outline</v-icon>
                            Berichtsdaten
                        </div>
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    label="Datum"
                                    v-model="report.report_date"
                                    required
                                    :rules="[requiredRule('Datum')]"
                                    type="datetime-local"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-calendar"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-select
                                    label="Report Code"
                                    v-model="report.report_code_id"
                                    :items="reportCodes"
                                    item-title="code"
                                    item-value="id"
                                    required
                                    :rules="[requiredRule('Report Code')]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-barcode"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    label="Standort"
                                    v-model="report.location"
                                    required
                                    :rules="[requiredRule('Standort')]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-map-marker"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    label="Titel"
                                    v-model="report.title"
                                    required
                                    :rules="[requiredRule('Titel')]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-format-title"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </div>

                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-link-variant</v-icon>
                            Verknüpfungen
                        </div>
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-autocomplete
                                    label="Verlinkte Personen"
                                    v-model="report.linked_persons"
                                    :items="persons"
                                    item-title="fullname"
                                    item-value="id"
                                    multiple
                                    chips
                                    closable-chips
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-account-multiple"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-autocomplete
                                    label="Verlinkte Unternehmen"
                                    v-model="report.linked_companies"
                                    :items="companies"
                                    item-title="name"
                                    item-value="id"
                                    multiple
                                    chips
                                    closable-chips
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    prepend-inner-icon="mdi-domain"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="12">
                                <v-select
                                    v-model="report.missing_employees"
                                    :items="employees"
                                    item-title="display_name"
                                    item-value="id"
                                    label="Fehlende Berichtergänzungen"
                                    multiple
                                    chips
                                    closable-chips
                                    variant="outlined"
                                    density="comfortable"
                                    color="warning"
                                    prepend-inner-icon="mdi-account-alert"
                                ></v-select>
                            </v-col>
                        </v-row>
                    </div>

                    <div class="form-section status-section">
                        <v-checkbox 
                            v-model="report.approved" 
                            label="Freigegeben" 
                            color="success"
                            hide-details
                        >
                            <template v-slot:prepend>
                                <v-icon :color="report.approved ? 'success' : 'grey'" class="mr-2">
                                    {{ report.approved ? 'mdi-check-circle' : 'mdi-check-circle-outline' }}
                                </v-icon>
                            </template>
                        </v-checkbox>
                    </div>

                    <!-- Add Custom Fields Section -->
                    <div class="form-section mb-4" v-if="customFields.length > 0">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-form-select</v-icon>
                            Benutzerdefinierte Felder
                        </div>
                        <v-row>
                            <v-col v-for="field in customFields" :key="field.field_name" cols="12" :md="field.field_type === 'boolean' ? 6 : 12">
                                <!-- Text Field -->
                                <v-text-field
                                    v-if="field.field_type === 'text'"
                                    :label="field.field_name"
                                    v-model="customFieldValues[field.field_name]"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    :required="field.required"
                                ></v-text-field>

                                <!-- Number Field -->
                                <v-text-field
                                    v-else-if="field.field_type === 'number'"
                                    :label="field.field_name"
                                    v-model.number="customFieldValues[field.field_name]"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    :required="field.required"
                                ></v-text-field>

                                <!-- Boolean Field -->
                                <v-checkbox
                                    v-else-if="field.field_type === 'boolean'"
                                    :label="field.field_name"
                                    v-model="customFieldValues[field.field_name]"
                                    color="primary"
                                    :required="field.required"
                                ></v-checkbox>

                                <!-- Select Field -->
                                <v-select
                                    v-else-if="field.field_type === 'select'"
                                    :label="field.field_name"
                                    v-model="customFieldValues[field.field_name]"
                                    :items="field.options"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    :required="field.required"
                                ></v-select>

                                <!-- Multiselect Field -->
                                <v-select
                                    v-else-if="field.field_type === 'multiselect'"
                                    :label="field.field_name"
                                    v-model="customFieldValues[field.field_name]"
                                    :items="field.options"
                                    multiple
                                    chips
                                    closable-chips
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    :required="field.required"
                                ></v-select>
                            </v-col>
                        </v-row>
                    </div>
                </v-form>
            </v-window-item>

            <!-- Tab 2: Text Feld -->
            <v-window-item :value="2">
                <div class="pa-4">
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-text-box-edit</v-icon>
                            Editor
                        </div>
                        <div class="editor-container mb-4">
                            <TiptapEditor
                                v-model="report.text"
                                placeholder="Geben Sie hier Notizen oder zusätzliche Informationen ein..."
                                :show-character-count="false"
                                :show-source-button="true"
                                :show-table-of-contents="true"
                                :editable="true"
                            />
                        </div>

                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-text-short</v-icon>
                            Kurzbeschreibung
                        </div>
                        <v-textarea
                            label="Beschreibung"
                            rows="3"
                            v-model="report.description"
                            variant="outlined"
                            density="comfortable"
                            color="primary"
                            hide-details
                        ></v-textarea>
                    </div>
                </div>
            </v-window-item>

            <!-- Tab 3: Zusätze -->
            <v-window-item :value="3">
                <div class="pa-4">
                    <div class="form-section mb-4">
                        <div class="section-title d-flex justify-space-between align-center">
                            <div>
                                <v-icon size="small" class="mr-1">mdi-package-variant-closed</v-icon>
                                Zusätzliche Leistungen
                            </div>
                            <v-btn 
                                @click="addNewAdditional" 
                                size="small" 
                                variant="tonal" 
                                color="primary"
                                prepend-icon="mdi-plus"
                                class="action-button"
                            >
                                Zusatz hinzufügen
                            </v-btn>
                        </div>

                        <div v-if="selectedAdditionals.length === 0" class="empty-state">
                            <v-icon color="grey-darken-1" size="40" class="mb-2">mdi-package-variant-closed</v-icon>
                            <span>Keine Zusatzleistungen vorhanden</span>
                        </div>

                        <div v-else class="additionals-list">
                            <v-row 
                                v-for="(additional, index) in selectedAdditionals"
                                :key="index"
                                align="center"
                                class="additional-item mb-2"
                            >
                                <v-col cols="12" md="3">
                                    <v-select
                                        label="Zusatzleistung"
                                        v-model="additional.id"
                                        :items="additionals"
                                        item-title="name"
                                        item-value="id"
                                        @update:modelValue="onAdditionalSelected(index, $event)"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        hide-details
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-text-field
                                        label="Preis"
                                        type="number"
                                        v-model.number="additional.price"
                                        @input="updateCalculations"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prefix="$"
                                        hide-details
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-text-field
                                        label="HE"
                                        type="number"
                                        v-model.number="additional.units"
                                        @input="updateCalculations"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        hide-details
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-text-field
                                        label="Anzahl"
                                        type="number"
                                        v-model.number="additional.amount"
                                        @input="updateCalculations"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        hide-details
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="1" class="d-flex justify-end">
                                    <v-btn 
                                        icon 
                                        @click="removeAdditional(index)" 
                                        title="Zusatz entfernen"
                                        variant="text"
                                        color="error"
                                        size="small"
                                        class="action-icon"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </div>
                    </div>

                    <v-divider class="mb-4"></v-divider>

                    <div class="totals-section">
                        <div class="d-flex align-center justify-space-between mb-2">
                            <div class="text-subtitle-1 font-weight-medium">{{$t('totalAmount')}}:</div>
                            <div class="text-h5 font-weight-bold total-cost">{{ totalCost }} $</div>
                        </div>
                        <div class="d-flex align-center justify-space-between">
                            <div class="text-subtitle-1 font-weight-medium">Hafteinheiten:</div>
                            <div class="text-h5 font-weight-bold total-units">{{ totalUnits }}</div>
                        </div>
                    </div>
                </div>
            </v-window-item>
        </v-window>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn variant="text" @click="closeDialog" class="mr-2">{{$t('cancel')}}</v-btn>
            <v-btn 
                color="primary" 
                variant="elevated"
                @click="handleSubmit"
                :disabled="isLoading"
                :loading="isLoading"
                class="action-button"
            >
                <v-icon start>mdi-content-save</v-icon>
                {{ isLoading ? $t('saving') : $t('save') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>
.report-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

/* Tabs */
.custom-tabs {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--card-border);
}

.custom-tab {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
}

.window-content {
    background-color: transparent !important;
}

/* Form Sections */
.form-section {
    margin-bottom: 24px;
}

.section-title {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 16px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--k-line);
    color: var(--v-theme-primary);
}

.status-section {
    background: var(--k-sunken);
    padding: 16px;
    border-radius: 8px;
}

/* Editor */
.editor-container {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
}

/* Additionals */
.additionals-list {
    max-height: 400px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--v-theme-primary) transparent;
}

.additionals-list::-webkit-scrollbar {
    width: 6px;
}

.additionals-list::-webkit-scrollbar-thumb {
    background-color: var(--k-accent-line);
    border-radius: 3px;
}

.additionals-list::-webkit-scrollbar-track {
    background: transparent;
}

.additional-item {
    background: var(--k-sunken);
    border-radius: 8px;
    padding: 8px;
    margin-bottom: 8px;
    transition: all var(--transition-timing);
}

.additional-item:hover {
    background: var(--k-sunken);
}

/* Empty state */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: var(--k-sunken);
    border-radius: 8px;
    color: var(--k-ink-muted);
}

/* Totals section */
.totals-section {
    background: var(--k-sunken);
    padding: 16px;
    border-radius: 8px;
}

.total-cost {
    color: var(--v-theme-primary);
}

.total-units {
    color: var(--v-theme-info);
}

/* Action Buttons */
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

.action-icon {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Responsive Adjustments */
@media (max-width: 960px) {
    .additional-item .v-col {
        margin-bottom: 8px;
    }
}

/* Add loading overlay styles */
.v-overlay {
    background-color: rgba(0, 0, 0, 0.7) !important;
    backdrop-filter: blur(4px);
}

.v-progress-circular {
    margin-bottom: 16px;
}
</style>