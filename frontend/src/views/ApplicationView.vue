<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, type Ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
// Remove Vuex import: import { useStore } from 'vuex';
import type { Applicant, Question, ApplicationData } from '@/types/Application'; // Adjust path

// Define JobType interface locally since it's not exported from Application types
interface JobType {
  id: number;
  name: string;
}
import { useToast } from 'vue-toastification'; // Import Toastification
import draggable from 'vuedraggable'; // Import if used for category sorting
import { useI18n } from 'vue-i18n';

// Define Async Components if needed (though not used in this template version)
// const AuthorityAddCompanyFile = defineAsyncComponent(() => import('@/components/CompanyFile/Authority/Add.vue'));

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
  desktopWindow?: boolean
  id?: number | string
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

// --- Component State ---
const applicants = ref<Applicant[]>([]);
const questions = ref<Question[]>([]); // All possible questions
const jobTypes = ref<JobType[]>([]); // For filtering and type selection
const jobStatusTypes = [
    { value: 'pending', text: 'Ausstehend' },
    { value: 'approved', text: 'Angenommen' },
    { value: 'rejected', text: 'Abgelehnt' },
];
const loadingApplicants = ref(false);
const loadingQuestions = ref(false);
const loadingJobTypes = ref(false);
const savingApplication = ref(false);
const search = ref(''); // Renamed from nameFilter for clarity
const sortOrder = ref('Zuletzt hinzugefügt');
const sortOptions = [
    'Zuletzt hinzugefügt',
    'Name A-Z',
    'Name Z-A',
    'Datum aufsteigend',
    'Datum absteigend',
];

// --- Filter State ---
const selectedJobTypes = reactive<Record<number, boolean>>({}); // Use object for checkbox binding
const filterStatus = reactive({
    pending: true,
    rejected: true,
    approved: true,
});

// --- Dialog States & Data ---
const addEditDialog = ref(false); // Combined dialog state
const activeTab = ref(0); // For tabs inside the dialog
const formRef = ref<any>(null); // For v-form
const isFormValid = ref(false); // v-model for v-form

const initialApplicationData: ApplicationData = {
    newApplication: true,
    id: 0,
    name: '',
    type: 0, // Changed from null to 0 as default value
    email: '',
    phonenumber: '',
    birthdate: '',
    status: 'pending',
    info: '',
    jobinterviewDate: '',
    jobinterviewTime: '',
    added: '',
    addToCalendar: false,
    answers: [],
};
const applicationData = reactive<ApplicationData>({ ...initialApplicationData });
const isEditing = computed(() => !applicationData.newApplication && !!applicationData.id);

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
const emailRule = (value: string) => /.+@.+\..+/.test(value) || 'Gültige E-Mail erforderlich.';

// --- Data Fetching ---
const fetchData = async <T,>(
    action: string,
    targetRef: Ref<T[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string,
    baseEndpoint: string
) => {
    loadingRef.value = true;
    try {
        const response = await apiClientAuth.get<T[]>(`${baseEndpoint}?action=${action}`);
        targetRef.value = response.data || response.data || [];
        return true;
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
        return false;
    } finally {
        loadingRef.value = false;
    }
};

const fetchJobTypes = async () => {
    await fetchData<JobType>(
        'getJobTypes',
        jobTypes,
        loadingJobTypes,
        'Fehler beim Laden der Jobtypen.',
        'employee/index.php'
    );
    // Initialize filter selection after fetching
    jobTypes.value.forEach(jt => {
        selectedJobTypes[jt.id] = true;
    });
};
const fetchApplicants = () =>
    fetchData<Applicant>(
        'getApplicants',
        applicants,
        loadingApplicants,
        'Fehler beim Laden der Bewerber.',
        'application/'
    );
const fetchQuestions = () =>
    fetchData<Question>(
        'getQuestions',
        questions,
        loadingQuestions,
        'Fehler beim Laden der Fragen.',
        'application/'
    );

const fetchAllInitialData = () => {
    // Fetch necessary data in parallel
    Promise.all([fetchJobTypes(), fetchApplicants(), fetchQuestions()]);
};

// --- Computed Properties ---

// Filter questions based on the selected application type for the dialog
const filteredQuestions = computed(() => {
    const currentType = applicationData.type; // Type selected in the form
    if (!currentType) return [];
    return questions.value.filter(
        q => Number(q.type) === Number(currentType) || Number(q.type) === 0
    ); // Assuming 0 means 'Both'
});

// Filter and Sort Applicants for Display
const filteredApplicants = computed(() => {
    const filtered = applicants.value.filter(applicant => {
        // Job Type Filter
        const typeId = Number(applicant.type);
        if (!selectedJobTypes[typeId]) return false;

        // Status Filter
        const matchesStatus =
            (filterStatus.pending && applicant.status === 'pending') ||
            (filterStatus.rejected && applicant.status === 'rejected') ||
            (filterStatus.approved && applicant.status === 'approved');
        if (!matchesStatus) return false;

        // Name Filter
        const nameTermLower = search.value.toLowerCase();
        if (nameTermLower && !applicant.name.toLowerCase().includes(nameTermLower)) return false;

        return true; // Passed all filters
    });

    // Sorting
    switch (sortOrder.value) {
        case 'Zuletzt hinzugefügt':
            filtered.sort((a, b) => new Date(b.added).getTime() - new Date(a.added).getTime());
            break;
        case 'Name A-Z':
            filtered.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'Name Z-A':
            filtered.sort((a, b) => b.name.localeCompare(a.name));
            break;
        case 'Datum aufsteigend':
            filtered.sort(
                (a, b) =>
                    new Date(a.jobinterviewDate ?? 0).getTime() -
                    new Date(b.jobinterviewDate ?? 0).getTime()
            );
            break;
        case 'Datum absteigend':
            filtered.sort(
                (a, b) =>
                    new Date(b.jobinterviewDate ?? 0).getTime() -
                    new Date(a.jobinterviewDate ?? 0).getTime()
            );
            break;
    }
    return filtered;
});

// --- Methods ---

// Dialog Openers/Closers
const openNewApplicationDialog = () => {
    Object.assign(applicationData, {
        ...initialApplicationData,
        newApplication: true,
        answers: [],
    }); // Reset fully
    isFormValid.value = false;
    activeTab.value = 0; // Start on info tab
    addEditDialog.value = true;
    setTimeout(() => formRef.value?.resetValidation(), 100);
};

const openEditDialog = (applicant: Applicant) => {
    Object.assign(applicationData, { ...applicant, newApplication: false }); // Load data
    // Ensure answers array exists and potentially pre-fill missing answers based on filteredQuestions
    const currentQuestions = questions.value.filter(
        q => Number(q.type) === Number(applicant.type) || Number(q.type) === 0
    );
    const existingAnswerMap = new Map(
        (applicationData.answers || []).map(a => [a.question_id, a.answer])
    );
    applicationData.answers = currentQuestions.map(q => ({
        applicant_id: applicationData.id,
        question_id: q.id,
        question: q.question, // Keep question text for display
        answer: existingAnswerMap.get(q.id) || '', // Use existing answer or empty string
    }));

    isFormValid.value = false;
    activeTab.value = 0; // Start on info tab
    addEditDialog.value = true;
    setTimeout(() => formRef.value?.resetValidation(), 100);
};

const closeDialog = () => {
    addEditDialog.value = false;
};

const isLoading = ref(false); // General loading state
// Save Application (Add/Edit)
const submitApplication = async () => {
    // Manual validation because tabs might hide invalid fields
    const { valid } = await formRef.value?.validate();
    if (!valid) {
        showSnackbar('Bitte überprüfen Sie die markierten Felder.', 'warning');
        activeTab.value = 0; // Switch to info tab where most required fields are
        return;
    }
    if (isLoading.value) return; // Prevent double submit

    isLoading.value = true;
    savingApplication.value = true; // Use specific loading state

    try {
        // Prepare payload - ensure answers only contain id and answer text if needed by backend
        const { newApplication, ...payload } = {
            ...applicationData,
            answers: applicationData.answers.map(a => ({
                question_id: a.question_id,
                answer: a.answer,
            })),
            // Convert boolean addToCalendar if needed by backend
            addToCalendar: applicationData.addToCalendar ? 1 : 0,
        };
        // newApplication flag is now excluded from the payload

        await apiClientAuth.post(`application?action=addApplication`, payload); // Adjust path

        await fetchApplicants(); // Refresh list
        // Questions usually don't need refresh unless add/edit modifies them
        // await fetchQuestions();

        closeDialog();
        showSnackbar(
            `Bewerbung erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error('Error submitting application:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Bewerbung.',
            'error'
        );
    } finally {
        isLoading.value = false;
        savingApplication.value = false;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Ungültig';
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return 'Fehler';
    }
};

const cardStatusColor = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'orange-darken-2';
        case 'rejected':
            return 'red-darken-2';
        case 'approved':
            return 'green-darken-2';
        default:
            return 'grey-darken-1';
    }
};
const getStatusText = (status: string): string => {
    const option = jobStatusTypes.find(s => s.value === status);
    return option?.text || status;
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllInitialData();
    
    // Check for ID from route query or props
    const applicationId = route.query.id || props.id || props.meta?.id;
    
    if (applicationId) {
        // Wait a bit for data to be fully loaded
        setTimeout(() => {
            // Find the application with the specified ID
            const application = applicants.value.find(app => app.id === Number(applicationId));
            if (application) {
                // Open the edit dialog for this application
                openEditDialog(application);
            }
        }, 500);
    }
});

// Watch filtered questions to update answers structure when type changes in ADD mode
watch(filteredQuestions, newQuestions => {
    if (!isEditing.value) {
        // Only update answers for NEW applications when type changes
        applicationData.answers = newQuestions.map(q => ({
            applicant_id: applicationData.id, // Use current ID (will be 0 for new applications)
            question_id: q.id,
            question: q.question,
            answer: '',
        }));
    }
});
</script>

<template>
    <div class="application-container">
        <v-container fluid class="pa-4">
            <!-- Header mit Aktionsbuttons und Suchoptionen -->
            <div class="section-header mb-4">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-account-tie" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('applicationView.title') }}</h1>
                </div>

                <div class="header-actions">
                    <v-btn
                        v-if="canEdit"
                        @click="openNewApplicationDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-account-plus-outline"
                        size="small"
                        class="action-button mr-2"
                    >
                        {{ t('applicationView.newApplicant') }}
                    </v-btn>

                    <v-menu open-on-hover :close-on-content-click="false" location="bottom start">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                prepend-icon="mdi-filter-variant"
                                variant="tonal"
                                color="primary"
                                size="small"
                            >
                                Filter
                            </v-btn>
                        </template>

                        <v-card class="filter-menu">
                            <v-list density="compact" subheader>
                                <v-list-subheader>NACH POSITION FILTERN</v-list-subheader>
                                <v-list-item v-if="loadingJobTypes" class="text-center">
                                    <v-progress-circular
                                        indeterminate
                                        size="20"
                                        color="primary"
                                    ></v-progress-circular>
                                </v-list-item>
                                <v-list-item
                                    v-for="jobType in jobTypes"
                                    :key="`filter-${jobType.id}`"
                                    @click.stop
                                    class="filter-list-item"
                                >
                                    <template v-slot:prepend>
                                        <v-checkbox-btn
                                            v-model="selectedJobTypes[jobType.id]"
                                            density="compact"
                                            color="primary"
                                        ></v-checkbox-btn>
                                    </template>
                                    <v-list-item-title>{{ jobType.name }}</v-list-item-title>
                                </v-list-item>
                            </v-list>

                            <v-divider></v-divider>

                            <v-list density="compact" subheader>
                                <v-list-subheader>NACH STATUS FILTERN</v-list-subheader>
                                <v-list-item @click.stop class="filter-list-item">
                                    <template v-slot:prepend>
                                        <v-checkbox-btn
                                            v-model="filterStatus.pending"
                                            density="compact"
                                            color="orange"
                                        ></v-checkbox-btn>
                                    </template>
                                    <v-list-item-title>
                                        <v-icon
                                            icon="mdi-clock-outline"
                                            size="small"
                                            color="orange"
                                            class="mr-1"
                                        ></v-icon>
                                        Ausstehend
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item @click.stop class="filter-list-item">
                                    <template v-slot:prepend>
                                        <v-checkbox-btn
                                            v-model="filterStatus.approved"
                                            density="compact"
                                            color="success"
                                        ></v-checkbox-btn>
                                    </template>
                                    <v-list-item-title>
                                        <v-icon
                                            icon="mdi-check-circle-outline"
                                            size="small"
                                            color="success"
                                            class="mr-1"
                                        ></v-icon>
                                        Angenommen
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item @click.stop class="filter-list-item">
                                    <template v-slot:prepend>
                                        <v-checkbox-btn
                                            v-model="filterStatus.rejected"
                                            density="compact"
                                            color="error"
                                        ></v-checkbox-btn>
                                    </template>
                                    <v-list-item-title>
                                        <v-icon
                                            icon="mdi-close-circle-outline"
                                            size="small"
                                            color="error"
                                            class="mr-1"
                                        ></v-icon>
                                        Abgelehnt
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-card>
                    </v-menu>

                    <div class="search-filters ml-auto mt-3 mt-sm-0">
                        <v-text-field
                            v-model="search"
                            :label="t('applicationView.searchPlaceholder')"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-magnify"
                            hide-details
                            clearable
                            color="primary"
                            class="search-field mr-3"
                        ></v-text-field>

                        <v-select
                            v-model="sortOrder"
                            :items="sortOptions"
                            label="Sortierung"
                            variant="outlined"
                            density="compact"
                            hide-details
                            color="primary"
                            class="sort-field"
                        ></v-select>
                    </div>
                </div>
            </div>

            <!-- Ladeindikator -->
            <v-row v-if="loadingApplicants || loadingQuestions" justify="center" class="my-10">
                <v-col cols="auto" class="text-center">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="64"
                    ></v-progress-circular>
                    <p class="mt-4 text-medium-emphasis">
                        {{ t('applicationView.loadingApplicants') }}
                    </p>
                </v-col>
            </v-row>

            <!-- Keine Ergebnisse Anzeige -->
            <v-card
                v-else-if="filteredApplicants.length === 0"
                class="empty-state-card pa-8 mb-3"
                variant="outlined"
            >
                <div class="d-flex flex-column align-center">
                    <v-icon
                        icon="mdi-account-search"
                        size="64"
                        color="grey-darken-1"
                        class="mb-4"
                    ></v-icon>
                    <span class="text-h6 text-grey-darken-1">
                        {{ t('applicationView.noApplicants') }}
                    </span>
                    <span class="text-body-2 text-grey-darken-3 mt-2">
                        {{ t('applicationView.adjustFilters') }}
                    </span>
                    <v-btn
                        v-if="canEdit"
                        color="primary"
                        variant="tonal"
                        class="mt-4"
                        prepend-icon="mdi-account-plus-outline"
                        @click="openNewApplicationDialog"
                    >
                        {{ t('applicationView.addApplicant') }}
                    </v-btn>
                </div>
            </v-card>

            <!-- Bewerber Karten -->
            <v-row v-else dense class="mt-4">
                <v-col
                    v-for="(applicant, index) in filteredApplicants"
                    :key="applicant.id"
                    cols="12"
                    sm="6"
                    md="4"
                    lg="3"
                    xl="2"
                    class="d-flex mb-4"
                >
                    <v-card
                        class="applicant-card flex-grow-1"
                        elevation="2"
                        @click="openEditDialog(applicant)"
                        :style="{ '--index': index }"
                        height="100%"
                    >
                        <div
                            class="card-status-indicator"
                            :class="`status-${applicant.status}`"
                        ></div>

                        <div class="card-header">
                            <v-avatar
                                :icon="applicant.type == 2 ? 'mdi-account' : 'mdi-fire-truck'"
                                size="42"
                                :color="cardStatusColor(applicant.status)"
                                class="applicant-avatar"
                            ></v-avatar>

                            <div class="applicant-title">
                                <div
                                    class="text-subtitle-1 font-weight-bold text-truncate applicant-name"
                                >
                                    {{ applicant.name }}
                                </div>
                                <v-chip
                                    size="x-small"
                                    label
                                    :color="cardStatusColor(applicant.status)"
                                    variant="flat"
                                    class="status-chip mt-1"
                                >
                                    {{ getStatusText(applicant.status) }}
                                </v-chip>
                            </div>
                        </div>

                        <v-divider class="mx-3 my-2"></v-divider>

                        <div class="card-info">
                            <div class="info-row">
                                <v-icon
                                    icon="mdi-calendar-clock"
                                    size="small"
                                    color="grey"
                                ></v-icon>
                                <span class="text-caption text-medium-emphasis">
                                    {{ formatDate(applicant.jobinterviewDate) }}
                                </span>
                            </div>

                            <div class="info-row">
                                <v-icon icon="mdi-clock-outline" size="small" color="grey"></v-icon>
                                <span class="text-caption text-medium-emphasis">
                                    {{ applicant.jobinterviewTime?.substring(0, 5) || '--:--' }}
                                </span>
                            </div>
                        </div>

                        <div class="card-overlay">
                            <v-btn variant="tonal" size="small" color="primary" class="edit-button">
                                Öffnen
                                <v-icon end>mdi-chevron-right</v-icon>
                            </v-btn>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Bewerber Dialog -->
            <v-dialog v-model="addEditDialog" persistent max-width="900px" scrollable>
                <v-card class="dialog-card">
                    <v-toolbar color="primary" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-h6">
                            <v-icon
                                :icon="isEditing ? 'mdi-account-edit' : 'mdi-account-plus'"
                                class="mr-2"
                                size="small"
                            ></v-icon>
                            {{ isEditing ? 'Bewerbung bearbeiten' : 'Neuer Bewerber' }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-card-item class="pa-2">
                        <v-tabs
                            v-model="activeTab"
                            color="primary"
                            density="compact"
                            align-tabs="start"
                            class="application-tabs"
                            slider-color="primary"
                        >
                            <v-tab :value="0">
                                <v-icon icon="mdi-information-outline" size="small" start></v-icon>
                                Informationen
                            </v-tab>
                            <v-tab :value="1" :disabled="!filteredQuestions.length">
                                <v-icon icon="mdi-help-circle-outline" size="small" start></v-icon>
                                Fragen
                                <v-chip
                                    size="x-small"
                                    class="ml-1"
                                    v-if="filteredQuestions.length"
                                    >{{ filteredQuestions.length }}</v-chip
                                >
                            </v-tab>
                        </v-tabs>
                    </v-card-item>

                    <v-divider></v-divider>

                    <v-card-text class="dialog-content pa-4">
                        <v-form ref="formRef" v-model="isFormValid" lazy-validation>
                            <v-window v-model="activeTab">
                                <!-- Informationen Tab -->
                                <v-window-item :value="0">
                                    <v-row dense>
                                        <v-col cols="12" sm="6" md="4">
                                            <v-select
                                                v-model="applicationData.type"
                                                :items="jobTypes"
                                                item-title="name"
                                                item-value="id"
                                                label="Job Typ / Abteilung"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="compact"
                                                :disabled="isEditing"
                                                color="primary"
                                            ></v-select>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-select
                                                v-model="applicationData.status"
                                                :items="jobStatusTypes"
                                                item-title="text"
                                                item-value="value"
                                                label="Bewerbungsstatus"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="compact"
                                                color="primary"
                                            ></v-select>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-checkbox
                                                v-model="applicationData.addToCalendar"
                                                label="Zum Kalender hinzufügen?"
                                                density="compact"
                                                hide-details
                                                color="primary"
                                            ></v-checkbox>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.name"
                                                :rules="[requiredRule]"
                                                label="Name"
                                                required
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-account"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.email"
                                                :rules="[requiredRule]"
                                                label="E-Mail"
                                                required
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-email"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.phonenumber"
                                                label="Telefonnummer"
                                                required
                                                :rules="[requiredRule]"
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-phone"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.birthdate"
                                                :rules="[requiredRule]"
                                                label="Geburtstag"
                                                type="date"
                                                required
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-cake"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.jobinterviewDate"
                                                :rules="[requiredRule]"
                                                label="Bewerbungsgespräch Datum"
                                                type="date"
                                                required
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-calendar"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12" sm="6" md="4">
                                            <v-text-field
                                                v-model="applicationData.jobinterviewTime"
                                                :rules="[requiredRule]"
                                                label="Bewerbungsgespräch Zeit"
                                                type="time"
                                                required
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-clock"
                                                color="primary"
                                            ></v-text-field>
                                        </v-col>

                                        <v-col cols="12">
                                            <v-textarea
                                                label="Zusätzliche Informationen / Notizen"
                                                auto-grow
                                                rows="3"
                                                v-model="applicationData.info"
                                                variant="outlined"
                                                density="compact"
                                                prepend-inner-icon="mdi-text-box"
                                                color="primary"
                                            ></v-textarea>
                                        </v-col>
                                    </v-row>
                                </v-window-item>

                                <!-- Fragen Tab -->
                                <v-window-item :value="1">
                                    <div
                                        v-if="filteredQuestions.length === 0"
                                        class="empty-questions my-4"
                                    >
                                        <v-icon
                                            icon="mdi-help-circle-outline"
                                            size="48"
                                            color="grey-darken-1"
                                            class="mb-4"
                                        ></v-icon>
                                        <span>
                                            {{ t('applicationView.noQuestions') }}
                                        </span>
                                    </div>

                                    <div v-else class="questions-container">
                                        <div
                                            v-for="(question, index) in filteredQuestions"
                                            :key="question.id"
                                            class="question-wrapper mb-3"
                                        >
                                            <v-card class="question-card" variant="outlined">
                                                <div class="question-header px-3 py-2">
                                                    <v-icon
                                                        icon="mdi-help-circle"
                                                        size="small"
                                                        class="mr-2 text-primary"
                                                    ></v-icon>
                                                    <span class="text-subtitle-2">{{
                                                        question.question
                                                    }}</span>
                                                </div>

                                                <v-divider></v-divider>

                                                <div class="px-3 py-2">
                                                    <v-textarea
                                                        v-model="
                                                            applicationData.answers[index].answer
                                                        "
                                                        :placeholder="t('applicationView.answerPlaceholder')"
                                                        rows="2"
                                                        auto-grow
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        color="primary"
                                                        class="answer-field"
                                                    ></v-textarea>
                                                </div>
                                            </v-card>
                                        </div>
                                    </div>
                                </v-window-item>
                            </v-window>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-3">
                        <v-btn
                            v-if="activeTab === 1"
                            variant="text"
                            prepend-icon="mdi-arrow-left"
                            @click="activeTab = 0"
                            density="comfortable"
                        >
                            Zurück
                        </v-btn>

                        <v-spacer></v-spacer>

                        <v-btn
                            v-if="activeTab === 0 && filteredQuestions.length > 0"
                            color="primary"
                            variant="text"
                            append-icon="mdi-arrow-right"
                            @click="activeTab = 1"
                            class="mr-2"
                            density="comfortable"
                        >
                            Zu den Fragen
                        </v-btn>

                        <v-btn
                            variant="text"
                            @click="closeDialog"
                            class="mx-2"
                            density="comfortable"
                        >
                            {{ t('cancel') }}
                        </v-btn>

                        <v-btn
                            color="primary"
                            variant="elevated"
                            :disabled="!isFormValid"
                            :loading="savingApplication"
                            @click="submitApplication"
                            density="comfortable"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.application-container {
    min-height: 90vh;
    background-color: #111723;
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Header Styles */
.section-header {
    display: flex;
    flex-direction: column;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    margin-top: 16px;
}

.search-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.search-field {
    min-width: 200px;
    flex-grow: 1;
}

.sort-field {
    min-width: 200px;
}

/* Filter Menu */
.filter-menu {
    background: rgba(15, 23, 42, 0.9) !important;
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.filter-list-item {
    transition: background-color 0.2s ease;
}

.filter-list-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
}

/* Empty State */
.empty-state-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border-radius: 12px;
}

/* Applicant Card Styles */
.applicant-card {
    position: relative;
    height: 100%;
    width: 100%;
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 10px !important;
    background: rgba(15, 23, 42, 0.6) !important;
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-out forwards;
    animation-delay: calc(var(--index, 0) * 0.05s);
}

.applicant-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.08);
}

.applicant-card:active {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.card-status-indicator {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.status-pending {
    background: linear-gradient(to right, #f59e0b, #d97706);
}

.status-approved {
    background: linear-gradient(to right, #10b981, #059669);
}

.status-rejected {
    background: linear-gradient(to right, #ef4444, #b91c1c);
}

.card-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
}

.applicant-avatar {
    margin-right: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.applicant-title {
    flex: 1;
    min-width: 0; /* Ensures text truncation works */
}

.applicant-name {
    max-width: 100%;
}

.card-info {
    padding: 0 16px 16px;
}

.info-row {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
}

.info-row .v-icon {
    margin-right: 8px;
}

/* Card Overlay */
.card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
    z-index: 5;
    border-radius: inherit;
}

.applicant-card:hover .card-overlay {
    opacity: 1;
}

.edit-button {
    transform: translateY(10px);
    transition: transform 0.2s ease-in-out;
}

.applicant-card:hover .edit-button {
    transform: translateY(0);
}

/* Dialog Styling */
.dialog-card {
    background: #0f172a !important;
    border-radius: 12px;
    overflow: hidden;
}

.dialog-toolbar {
    background: linear-gradient(90deg, #1e3a8a, #2563eb) !important;
}

.dialog-content {
    max-height: 100%;
    overflow-y: auto;
}

/* Tabs Styling */
.application-tabs {
    background: transparent !important;
}

.application-tabs :deep(.v-tab) {
    min-width: auto;
    font-size: 0.9rem;
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    padding: 0 16px;
}

.application-tabs :deep(.v-tab--selected) {
    background: rgba(59, 130, 246, 0.1);
}

/* Question Cards */
.questions-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.question-wrapper {
    transition: transform 0.2s ease;
}

.question-wrapper:hover {
    transform: translateY(-2px);
}

.question-card {
    background: rgba(30, 41, 59, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    overflow: hidden;
}

.question-header {
    display: flex;
    align-items: center;
    background: rgba(15, 23, 42, 0.5) !important;
}

.answer-field :deep(.v-field__input) {
    padding-top: 8px;
    padding-bottom: 8px;
    min-height: 80px;
}

.empty-questions {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #94a3b8;
}

/* Buttons */
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

/* Responsive Styles */
@media (min-width: 768px) {
    .section-header {
        flex-direction: row;
        align-items: center;
    }

    .header-actions {
        margin-top: 0;
        margin-left: auto;
    }
}

@media (max-width: 767px) {
    .section-header {
        align-items: flex-start;
    }

    .header-actions {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-filters {
        width: 100%;
        margin-top: 16px;
    }

    .search-field,
    .sort-field {
        width: 100%;
    }
}

@media (max-width: 600px) {
    .card-overlay {
        display: none; /* On mobile, just click the card directly */
    }
}

/* Animation Effects */
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
</style>
