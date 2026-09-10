<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useModulePermission } from '@/composables/useModulePermission';
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
// No route needed here based on original code

// --- Store & Route ---
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

console.log('Test View props from desktop window:', {
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

const hasEditPermission = computed(() => 
  isAdmin.value || 
  props.canEdit || 
  props.meta?.canEdit || 
  !!route.meta?.canEdit
);

const hasDeletePermission = computed(() => 
  isAdmin.value || 
  props.canDelete || 
  props.meta?.canDelete || 
  !!route.meta?.canDelete
);

const hasCreatePermission = computed(() =>
  isAdmin.value || 
  props.canCreate || 
  props.meta?.canCreate || 
  !!route.meta?.canCreate
);

// --- Define Interfaces ---
// Interface for the flat structure from getQuestionsAndAnswers
interface FlatQuestionAnswer {
    id: number;
    question: string;
    isKO: string | boolean; // Accept both string and boolean
    answer1?: string;
    answer1_result?: string;
    answer2?: string;
    answer2_result?: string;
    answer3?: string;
    answer3_result?: string;
    // New structure properties
    answers?: Array<{
        id: number;
        answer: string;
        result: boolean | string;
    }>;
}

// Interface for the structured generated test questions
interface TestAnswer {
    text: string;
    correct: boolean; // Assuming boolean
}
interface TestQuestion {
    id?: number; // May or may not have ID from generation
    question: string;
    isKO: boolean; // Assuming boolean
    answers: TestAnswer[];
}

// Interface for the Add/Edit Form Data
interface QuestionFormData {
    id?: number | null;
    question: string;
    isKO: boolean;
    answer1: string;
    answer1_result: boolean;
    answer2: string;
    answer2_result: boolean;
    answer3: string;
    answer3_result: boolean;
}

// --- Component State ---
const questionsAndAnswers = ref<FlatQuestionAnswer[]>([]);
const testQuestions = ref<TestQuestion[]>([]); // Holds generated test
const loadingQuestions = ref(false);
const savingQuestion = ref(false);
const deletingQuestion = ref(false);
const generatingTest = ref(false);

// --- Dialog States ---
const questionDialog = ref(false); // Combined Add/Edit dialog
const deleteConfirmationDialog = ref(false);

// --- Form State & Data ---
const questionFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isQuestionFormValid = ref(false);
const initialFormData: QuestionFormData = {
    id: null,
    question: '',
    isKO: false,
    answer1: '',
    answer1_result: false,
    answer2: '',
    answer2_result: false,
    answer3: '',
    answer3_result: false,
};
const questionFormData = reactive<QuestionFormData>({ ...initialFormData });
const isEditing = computed(() => !!questionFormData.id);

// --- Data for Dialogs ---
const questionToDeleteId = ref<number | null>(null);

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
const headers = ref([
    { title: 'Frage', key: 'question', sortable: true },
    { title: 'K.O.', key: 'isKO', sortable: true, width: '80px', align: 'center' },
    { title: 'Antwort 1', key: 'answer1', sortable: false }, // Sorting might be complex
    { title: 'Antwort 2', key: 'answer2', sortable: false },
    { title: 'Antwort 3', key: 'answer3', sortable: false },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const);

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

// --- Data Fetching ---
const fetchQuestionsAndAnswers = async () => {
    loadingQuestions.value = true;
    try {
        const response = await apiClientAuth.get<FlatQuestionAnswer[]>(
            '/test/?action=getQuestionsAndAnswers'
        );
        
        // Process response data to handle both old and new formats
        questionsAndAnswers.value = response.data || [];
        console.log('Questions data:', questionsAndAnswers.value);
    } catch (error: any) {
        console.error('Error fetching questions:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Fragen.', 'error');
        questionsAndAnswers.value = [];
    } finally {
        loadingQuestions.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openAddQuestionDialog = () => {
    Object.assign(questionFormData, { ...initialFormData, id: null }); // Reset form
    isQuestionFormValid.value = false;
    questionDialog.value = true;
    setTimeout(() => questionFormRef.value?.resetValidation(), 100);
};

const openEditQuestionDialog = (item: FlatQuestionAnswer) => {
    // Map structure to form data, handling both formats
    questionFormData.id = item.id;
    questionFormData.question = item.question;
    questionFormData.isKO = typeof item.isKO === 'boolean' ? item.isKO : item.isKO === 'true';
    
    // Handle the case when the answers are in the new "answers" array format
    if (item.answers && Array.isArray(item.answers)) {
        // Map the first answer if it exists
        if (item.answers.length > 0) {
            questionFormData.answer1 = item.answers[0].answer;
            questionFormData.answer1_result = 
                typeof item.answers[0].result === 'boolean' 
                    ? item.answers[0].result 
                    : item.answers[0].result === 'true';
        } else {
            questionFormData.answer1 = '';
            questionFormData.answer1_result = false;
        }
        
        // Map the second answer if it exists
        if (item.answers.length > 1) {
            questionFormData.answer2 = item.answers[1].answer;
            questionFormData.answer2_result = 
                typeof item.answers[1].result === 'boolean'
                    ? item.answers[1].result
                    : item.answers[1].result === 'true';
        } else {
            questionFormData.answer2 = '';
            questionFormData.answer2_result = false;
        }
        
        // Map the third answer if it exists
        if (item.answers.length > 2) {
            questionFormData.answer3 = item.answers[2].answer;
            questionFormData.answer3_result = 
                typeof item.answers[2].result === 'boolean'
                    ? item.answers[2].result
                    : item.answers[2].result === 'true';
        } else {
            questionFormData.answer3 = '';
            questionFormData.answer3_result = false;
        }
    } else {
        // Original flat structure
        questionFormData.answer1 = item.answer1 || '';
        questionFormData.answer1_result = item.answer1_result === 'true';
        questionFormData.answer2 = item.answer2 || '';
        questionFormData.answer2_result = item.answer2_result === 'true';
        questionFormData.answer3 = item.answer3 || '';
        questionFormData.answer3_result = item.answer3_result === 'true';
    }

    isQuestionFormValid.value = false;
    questionDialog.value = true;
    setTimeout(() => questionFormRef.value?.resetValidation(), 100);
};

const closeQuestionDialog = () => {
    questionDialog.value = false;
};

const saveQuestion = async () => {
    if (!isQuestionFormValid.value) return;

    savingQuestion.value = true;
    const action = isEditing.value ? 'editQuestion' : 'addQuestion';
    // Prepare payload, potentially converting booleans back to strings if API expects 'true'/'false'
    const payload = { ...questionFormData };
    // Example conversion back to string if needed:
    // payload.isKO = payload.isKO ? 'true' : 'false';
    // payload.answer1_result = payload.answer1_result ? 'true' : 'false';
    // payload.answer2_result = payload.answer2_result ? 'true' : 'false';
    // payload.answer3_result = payload.answer3_result ? 'true' : 'false';

    try {
        await apiClientAuth.post(`/test/?action=${action}`, payload);
        await fetchQuestionsAndAnswers(); // Refresh list
        closeQuestionDialog();
        showSnackbar(
            `Frage erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving question (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern der Frage.', 'error');
    } finally {
        savingQuestion.value = false;
    }
};

// Delete Dialog Logic
const openDeleteConfirmationDialog = (id: number) => {
    questionToDeleteId.value = id;
    deleteConfirmationDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteConfirmationDialog.value = false;
    questionToDeleteId.value = null;
};

const confirmDeleteQuestion = async () => {
    if (!questionToDeleteId.value) return;

    deletingQuestion.value = true;
    try {
        await apiClientAuth.post('/test/?action=deleteQuestion', { id: questionToDeleteId.value });
        await fetchQuestionsAndAnswers(); // Refresh list
        closeDeleteDialog();
        showSnackbar('Frage erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting question:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Frage.', 'error');
    } finally {
        deletingQuestion.value = false;
    }
};

// Test Generation and Copy Logic
const generateRandomTest = async () => {
    generatingTest.value = true;
    testQuestions.value = []; // Clear previous test
    try {
        // Get data from API
        const response = await apiClientAuth.get('/test/?action=generateRandomTest');
        
        // Transform the data structure to match what our components expect
        if (response.data && Array.isArray(response.data)) {
            testQuestions.value = response.data.map(question => ({
                id: question.id,
                question: question.question,
                isKO: typeof question.isKO === 'boolean' ? question.isKO : question.isKO === 'true',
                answers: Array.isArray(question.answers) ? question.answers.map((ans: { id: number; answer: string; result: boolean | string }) => ({
                    id: ans.id,
                    text: ans.answer,  // Map 'answer' to 'text'
                    correct: typeof ans.result === 'boolean' ? ans.result : ans.result === 'true'  // Map 'result' to 'correct'
                })) : []
            }));
        }
        
        if (testQuestions.value.length === 0) {
            showSnackbar('Keine Fragen gefunden, um einen Test zu generieren.', 'warning');
        }
    } catch (error: any) {
        console.error('Error generating test:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Generieren des Tests.', 'error');
    } finally {
        generatingTest.value = false;
    }
};

// --- Formatting for Copy ---
const formatTestForCopy = (): string => {
    return testQuestions.value
        .map((q, index) => {
            const answers = q.answers.map(a => `( ) ${a.text}`).join('\n');
            return `${index + 1}. ${q.question}${q.isKO ? ' (K.O.)' : ''}\n${answers}`;
        })
        .join('\n\n');
};

const formatSolutionsForCopy = (useUnicode: boolean): string => {
    return testQuestions.value
        .map((q, index) => {
            const answers = q.answers
                .map(
                    a =>
                        `${useUnicode ? (a.correct ? '🟢' : '🔴') : a.correct ? '(x)' : '( )'} ${a.text}`
                )
                .join('\n');
            return `${index + 1}. ${q.question}${q.isKO ? ' (K.O.)' : ''}\n${answers}`;
        })
        .join('\n\n');
};

// --- Clipboard Functions ---
const copyToClipboard = async (text: string, successMessage: string, errorMessage: string) => {
    try {
        await navigator.clipboard.writeText(text);
        showSnackbar(successMessage, 'success');
    } catch (err) {
        console.error('Failed to copy: ', err);
        showSnackbar(errorMessage, 'error');
    }
};

const copyTest = () => {
    copyToClipboard(
        formatTestForCopy(),
        'Test erfolgreich kopiert.',
        'Test konnte nicht kopiert werden.'
    );
};

const copySolutions = () => {
    copyToClipboard(
        formatSolutionsForCopy(true),
        'Lösungen erfolgreich kopiert.',
        'Lösungen konnten nicht kopiert werden.'
    );
};

const copySolutionsWithoutUnicode = () => {
    copyToClipboard(
        formatSolutionsForCopy(false),
        'Lösungen (Text) erfolgreich kopiert.',
        'Lösungen (Text) konnten nicht kopiert werden.'
    );
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchQuestionsAndAnswers(); // Fetch data when component mounts
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('TestView', () => unref(headers) as any);
</script>

<template>
    <div class="question-catalog-container">
        <ErrorSnackbar v-model="errorSnackbar" />
        <v-container fluid class="pa-4">
            <!-- Header with title and actions -->
            <v-row class="mb-4 align-center">
                <v-col cols="auto">
                    <div class="d-flex align-center header-title">
                        <v-icon
                            icon="mdi-help-circle-outline"
                            size="28"
                            class="mr-3 text-primary header-icon"
                        ></v-icon>
                        <h1 class="text-h5 font-weight-medium mb-0">{{ t('testView.title') }}</h1>
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
                        {{ t('testView.description') }}
                    </v-alert>
                </v-col>
            </v-row>

            <!-- Action Bar -->
            <v-card class="action-bar mb-5" variant="outlined">
                <v-card-text class="py-3 px-4">
                    <div class="d-flex align-center flex-wrap">
                        <div class="actions-group">
                            <v-btn
                                @click="openAddQuestionDialog()"
                                color="primary"
                                variant="elevated"
                                prepend-icon="mdi-plus"
                                class="mr-3 mb-2 mb-md-0 action-button"
                            >
                                {{ t('testView.addQuestion') }}
                            </v-btn>
                            <v-btn
                                @click="generateRandomTest()"
                                color="secondary"
                                variant="tonal"
                                prepend-icon="mdi-dice-multiple-outline"
                                :loading="generatingTest"
                                class="mb-2 mb-md-0 action-button"
                            >
                                {{ t('testView.generateTest') }}
                            </v-btn>
                        </div>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Question Table -->
            <v-card class="main-card elevation-4 mb-5">
                <v-toolbar
                    flat
                    density="compact"
                    color="transparent"
                    class="card-toolbar px-4 py-2"
                >
                    <v-toolbar-title class="text-h6">
                        <v-icon start size="20" class="mr-2">mdi-format-list-bulleted</v-icon>
                        {{ t('testView.questionAndAnswers') }}
                    </v-toolbar-title>
                </v-toolbar>

                <v-divider></v-divider>

                <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                <KTableToolbar :columns="kCols" :shown="(questionsAndAnswers || []).length" />
                <v-data-table
                    :headers="kCols.visible.value"
                    :items="questionsAndAnswers"
                    class="question-table"
                    item-value="id"
                    :loading="loadingQuestions"
                    hover
                    density="comfortable"
                >
                    <template v-slot:[`item.isKO`]="{ item }">
                        <v-tooltip v-if="item.isKO === 'true' || item.isKO === true" location="top" :text="t('testView.koQuestion')">
                            <template v-slot:activator="{ props }">
                                <v-icon v-bind="props" color="warning" class="status-icon">
                                    mdi-alert-octagon
                                </v-icon>
                            </template>
                        </v-tooltip>
                    </template>

                    <template v-slot:[`item.answer1`]="{ item }">
                        <!-- Handle new format with answers array -->
                        <span v-if="item.answers && item.answers.length > 0">
                            <span
                                :class="{
                                    'text-success font-weight-bold': item.answers[0].result === true || item.answers[0].result === 'true',
                                    'text-error': item.answers[0].result === false || item.answers[0].result === 'false',
                                }"
                            >
                                {{ item.answers[0].answer }}
                                <v-icon
                                    v-if="item.answers[0].result === true || item.answers[0].result === 'true'"
                                    color="success"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-check
                                </v-icon>
                                <v-icon
                                    v-else
                                    color="error"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-close
                                </v-icon>
                            </span>
                        </span>
                        <!-- Fallback to original format -->
                        <span v-else
                            :class="{
                                'text-success font-weight-bold': item.answer1_result === 'true',
                                'text-error': item.answer1_result === 'false' && item.answer1,
                            }"
                        >
                            {{ item.answer1 }}
                            <v-icon
                                v-if="item.answer1_result === 'true'"
                                color="success"
                                size="x-small"
                                class="ml-1 answer-icon"
                            >
                                mdi-check
                            </v-icon>
                            <v-icon
                                v-else-if="item.answer1"
                                color="error"
                                size="x-small"
                                class="ml-1 answer-icon"
                            >
                                mdi-close
                            </v-icon>
                        </span>
                    </template>

                    <template v-slot:[`item.answer2`]="{ item }">
                        <!-- Handle new format with answers array -->
                        <span v-if="item.answers && item.answers.length > 1">
                            <span
                                :class="{
                                    'text-success font-weight-bold': item.answers[1].result === true || item.answers[1].result === 'true',
                                    'text-error': item.answers[1].result === false || item.answers[1].result === 'false',
                                }"
                            >
                                {{ item.answers[1].answer }}
                                <v-icon
                                    v-if="item.answers[1].result === true || item.answers[1].result === 'true'"
                                    color="success"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-check
                                </v-icon>
                                <v-icon
                                    v-else
                                    color="error"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-close
                                </v-icon>
                            </span>
                        </span>
                        <!-- Fallback to original format -->
                        <span
                            v-else-if="item.answer2"
                            :class="{
                                'text-success font-weight-bold': item.answer2_result === 'true',
                                'text-error': item.answer2_result === 'false',
                            }"
                        >
                            {{ item.answer2 }}
                            <v-icon
                                v-if="item.answer2_result === 'true'"
                                color="success"
                                size="x-small"
                                class="ml-1 answer-icon"
                            >
                                mdi-check
                            </v-icon>
                            <v-icon v-else color="error" size="x-small" class="ml-1 answer-icon">
                                mdi-close
                            </v-icon>
                        </span>
                    </template>

                    <template v-slot:[`item.answer3`]="{ item }">
                        <!-- Handle new format with answers array -->
                        <span v-if="item.answers && item.answers.length > 2">
                            <span
                                :class="{
                                    'text-success font-weight-bold': item.answers[2].result === true || item.answers[2].result === 'true',
                                    'text-error': item.answers[2].result === false || item.answers[2].result === 'false',
                                }"
                            >
                                {{ item.answers[2].answer }}
                                <v-icon
                                    v-if="item.answers[2].result === true || item.answers[2].result === 'true'"
                                    color="success"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-check
                                </v-icon>
                                <v-icon
                                    v-else
                                    color="error"
                                    size="x-small"
                                    class="ml-1 answer-icon"
                                >
                                    mdi-close
                                </v-icon>
                            </span>
                        </span>
                        <!-- Fallback to original format -->
                        <span
                            v-else-if="item.answer3"
                            :class="{
                                'text-success font-weight-bold': item.answer3_result === 'true',
                                'text-error': item.answer3_result === 'false',
                            }"
                        >
                            {{ item.answer3 }}
                            <v-icon
                                v-if="item.answer3_result === 'true'"
                                color="success"
                                size="x-small"
                                class="ml-1 answer-icon"
                            >
                                mdi-check
                            </v-icon>
                            <v-icon v-else color="error" size="x-small" class="ml-1 answer-icon">
                                mdi-close
                            </v-icon>
                        </span>
                    </template>

                    <template v-slot:[`item.actions`]="{ item }">
                        <div class="d-flex gap-1">
                            <v-tooltip text="Bearbeiten" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openEditQuestionDialog(item)"
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
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openDeleteConfirmationDialog(item.id)"
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
                            <v-icon size="40" color="grey-darken-1" class="mb-2">
                                mdi-help-circle-outline
                            </v-icon>
                            <span>Keine Fragen im Katalog vorhanden.</span>
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
                            <span>Lade Fragen...</span>
                        </div>
                    </template>
                </v-data-table>
            </v-card>

            <!-- Generated Test Display -->
            <v-card
                v-if="testQuestions.length > 0"
                class="test-card mb-5"
                variant="outlined"
            >
                <div class="test-header px-4 py-3">
                    <div class="d-flex align-center justify-space-between">
                        <h3 class="test-title">
                            <v-icon
                                icon="mdi-file-document-multiple"
                                size="small"
                                class="mr-2"
                            ></v-icon>
                            Generierter Test ({{ testQuestions.length }} Fragen)
                        </h3>
                        <div class="test-actions">
                            <v-btn
                                @click="copyTest()"
                                variant="tonal"
                                size="small"
                                color="primary"
                                class="mr-2 copy-button"
                                prepend-icon="mdi-content-copy"
                            >
                                Test kopieren
                            </v-btn>
                            <v-btn
                                @click="copySolutions()"
                                variant="tonal"
                                size="small"
                                color="info"
                                class="mr-2 copy-button"
                                prepend-icon="mdi-format-list-checks"
                            >
                                Lösungen kopieren
                            </v-btn>
                            <v-btn
                                @click="copySolutionsWithoutUnicode()"
                                variant="tonal"
                                size="small"
                                color="secondary"
                                class="copy-button"
                                prepend-icon="mdi-code-tags"
                            >
                                Lsg. (Text) kopieren
                            </v-btn>
                        </div>
                    </div>
                </div>

                <v-divider></v-divider>

                <v-card-text class="pa-4">
                    <div
                        v-for="(question, index) in testQuestions"
                        :key="question.id || index"
                        class="question-item mb-4"
                    >
                        <p class="question-text">
                            <strong>{{ index + 1 }}.</strong> {{ question.question }}
                            <v-icon v-if="question.isKO" color="warning" size="small" class="ml-1">
                                mdi-alert-octagon
                            </v-icon>
                        </p>
                        <div
                            v-for="(answer, aIndex) in question.answers"
                            :key="aIndex"
                            class="answer-item ml-6 mt-2"
                            :class="{
                                'correct-answer': answer.correct,
                                'incorrect-answer': !answer.correct,
                            }"
                        >
                            <v-icon
                                size="small"
                                :color="answer.correct ? 'success' : 'error'"
                                class="mr-2"
                            >
                                {{
                                    answer.correct
                                        ? 'mdi-check-circle-outline'
                                        : 'mdi-close-circle-outline'
                                }}
                            </v-icon>
                            {{ answer.text }}
                        </div>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Question Dialog -->
            <v-dialog v-model="questionDialog" max-width="700px" persistent>
                <v-card class="dialog-card">
                    <v-toolbar color="primary" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon icon="mdi-help-box" class="mr-2" size="small"></v-icon>
                            {{ isEditing ? 'Frage bearbeiten' : 'Neue Frage hinzufügen' }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeQuestionDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-card-text class="pt-4">
                        <v-form ref="questionFormRef" v-model="isQuestionFormValid">
                            <v-row>
                                <v-col cols="12" md="9">
                                    <v-text-field
                                        label="Frage"
                                        required
                                        v-model="questionFormData.question"
                                        :rules="[requiredRule('Frage')]"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="3" class="d-flex align-center">
                                    <v-checkbox
                                        :label="t('testView.koQuestion')"
                                        v-model="questionFormData.isKO"
                                        density="comfortable"
                                        hide-details
                                        color="warning"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                            <v-divider class="my-4"></v-divider>
                            <v-row align="center">
                                <v-col cols="12" md="9">
                                    <v-text-field
                                        label="Antwort 1"
                                        required
                                        v-model="questionFormData.answer1"
                                        :rules="[requiredRule('Antwort 1')]"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        label="Richtig?"
                                        v-model="questionFormData.answer1_result"
                                        density="comfortable"
                                        hide-details
                                        color="success"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                            <v-row align="center">
                                <v-col cols="12" md="9">
                                    <v-text-field
                                        label="Antwort 2 (optional)"
                                        v-model="questionFormData.answer2"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        label="Richtig?"
                                        v-model="questionFormData.answer2_result"
                                        :disabled="!questionFormData.answer2"
                                        density="comfortable"
                                        hide-details
                                        color="success"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                            <v-row align="center">
                                <v-col cols="12" md="9">
                                    <v-text-field
                                        label="Antwort 3 (optional)"
                                        v-model="questionFormData.answer3"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        label="Richtig?"
                                        v-model="questionFormData.answer3_result"
                                        :disabled="!questionFormData.answer3"
                                        density="comfortable"
                                        hide-details
                                        color="success"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeQuestionDialog" class="mr-2"
                            >{{ t('cancel') }}</v-btn
                        >
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveQuestion"
                            :disabled="!isQuestionFormValid"
                            :loading="savingQuestion"
                            class="action-button"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete Confirmation Dialog -->
            <v-dialog v-model="deleteConfirmationDialog" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-toolbar color="error" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon icon="mdi-delete-alert" class="mr-2" size="small"></v-icon>
                            Frage löschen
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeDeleteDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-card-text class="pt-4">
                        <p>Bist du sicher, dass du diese Frage löschen willst?</p>
                        <p class="text-caption text-medium-emphasis mt-2">
                            Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteDialog" class="mr-2"
                            >{{ t('cancel') }}</v-btn
                        >
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDeleteQuestion"
                            :loading="deletingQuestion"
                            class="delete-button"
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
.question-catalog-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    position: relative;
}

/* Header Styles */
.header-title {
    animation: fadeIn 0.5s ease-out;
}

.header-icon {
    filter: drop-shadow(0 2px 6px var(--k-accent-line));
}

.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Action Bar */
.action-bar {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.5s ease-out;
    animation-delay: 0.1s;
}

.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover,
.copy-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Main Card & Table Styles */
.main-card,
.test-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    animation: fadeIn 0.5s ease-out;
    animation-delay: 0.2s;
}

.card-toolbar {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--k-line);
}

:deep(.v-table .v-table__wrapper > table > thead > tr > th) {
    font-weight: 600;
    color: #e2e8f0;
    background: var(--k-sunken) !important;
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
    background: var(--k-sunken) !important;
}

/* Table Icons & Text Colors */
.action-icon {
    opacity: 0.7;
    transition: all 0.2s;
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

.status-icon,
.answer-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.3s ease;
}

.text-success {
    color: #4caf50 !important;
}

.text-error {
    color: #f44336 !important;
}

/* Generated Test Styles */
.test-header {
    background: var(--k-sunken);
}

.test-title {
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.question-item {
    background: var(--k-sunken);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.question-item:hover {
    background: var(--k-sunken);
}

.question-text {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 10px;
}

.answer-item {
    font-size: 0.95rem;
    padding: 6px 10px;
    border-radius: 6px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
}

.correct-answer {
    background: rgba(76, 175, 80, 0.1);
}

.incorrect-answer {
    background: rgba(244, 67, 54, 0.1);
}

/* Empty & Loading States */
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

/* Dialog Styles */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-toolbar {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover)) !important;
}

.dialog-toolbar.v-toolbar {
    border-radius: 0;
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

/* Responsive Adjustments */
@media (max-width: 960px) {
    .test-header {
        flex-direction: column;
    }

    .test-actions {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .copy-button {
        flex-grow: 1;
    }
}
</style>
