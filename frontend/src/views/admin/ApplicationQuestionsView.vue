<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable'; // Use vuedraggable
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import type { Question } from '@/types/Application'; // Adjust path if needed
import type { JobTypes as JobType } from '@/types/Members'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar

const { t } = useI18n();

// --- Component State ---
const questions = ref<Question[]>([]); // All questions from API
const documentTypes = ref<JobType[]>([]); // Essentially JobTypes used as DocumentTypes
const loadingData = ref(false);
const loadingQuestionsInDialog = ref(false);
const savingQuestions = ref(false);
const savingSort = ref(false);
const deletingQuestion = ref(false);
const addingQuestion = ref(false); // For individual add request

// --- Dialog States & Data ---
const editDialog = ref(false); // Edit questions dialog
const sortingDialog = ref(false);
const deleteConfirmationDialog = ref(false);
const currentDocumentType = ref<JobType | null>(null); // Doc type being edited/sorted
const questionsInDialog = ref<Question[]>([]); // Questions shown/edited/sorted in dialogs
const newQuestionText = ref('');
const questionToDelete = ref<Question | null>(null);

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: "", color: "error" });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Data Fetching ---
const fetchJobTypes = async () => {
  try {
    // Assuming correct endpoint, adjust if necessary
    const response = await apiClientAuth.get<JobType[]>('/employee?action=getJobTypes');
    documentTypes.value = response.data || response.data || [];
  } catch (error: any) {
    console.error("Error fetching document types (JobTypes):", error);
    showSnackbar(error.response?.data?.error || "Fehler beim Laden der Dokumenttypen.", "error");
    documentTypes.value = [];
  }
};

const fetchQuestions = async () => {
  try {
    const response = await apiClientAuth.get<Question[]>('/admin/application?action=getQuestions'); // Adjust path
    questions.value = response.data || response.data || [];
  } catch (error: any) {
    console.error("Error fetching questions:", error);
    showSnackbar(error.response?.data?.error || "Fehler beim Laden der Fragen.", "error");
    questions.value = [];
  }
};

const fetchAllInitialData = async () => {
    loadingData.value = true;
    await Promise.all([
        fetchJobTypes(),
        fetchQuestions()
    ]);
    loadingData.value = false;
};

// --- Methods ---

// Dialog Openers
const openApplicantQuestionEditor = (docType: JobType) => {
    currentDocumentType.value = docType;
    // Filter questions for this document type and make a deep copy for editing
    questionsInDialog.value = JSON.parse(JSON.stringify(
        questions.value.filter(q => q.type === docType.id).sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    ));
    newQuestionText.value = ''; // Reset new question input
    editDialog.value = true;
};

const openSortingDialog = (docType: JobType) => {
    currentDocumentType.value = docType;
    // Filter questions for sorting, keep original order for draggable init
     questionsInDialog.value = questions.value
         .filter(q => q.type === docType.id)
         .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
    sortingDialog.value = true;
};

// Dialog Closers
const closeEditDialog = () => {
    editDialog.value = false;
    currentDocumentType.value = null;
    questionsInDialog.value = [];
};

const closeSortingDialog = () => {
    sortingDialog.value = false;
    currentDocumentType.value = null;
     questionsInDialog.value = []; // Clear the temporary list
};

// Question CRUD within Dialogs
const addQuestion = async () => {
    if (!newQuestionText.value.trim() || !currentDocumentType.value) return;

    addingQuestion.value = true; // Indicate loading
    const newSortOrder = questionsInDialog.value.length; // Append at the end

    try {
        const response = await apiClientAuth.post<Question>("/admin/application?action=addQuestion", {
            question: newQuestionText.value.trim(),
            type: currentDocumentType.value.id,
            sort_order: newSortOrder,
        });
        // Add the newly created question (with ID from response) to the dialog list
        questionsInDialog.value.push(response.data); // Adjust if response structure differs
        newQuestionText.value = ""; // Clear input
        // Update the main questions list as well
        await fetchQuestions();
        showSnackbar("Frage hinzugefügt.", "success");
    } catch (error: any) {
        console.error("Error adding question:", error);
        showSnackbar(error.response?.data?.error || "Fehler beim Hinzufügen der Frage.", "error");
    } finally {
        addingQuestion.value = false;
    }
};

const openDeleteConfirmationDialog = (question: Question) => {
  questionToDelete.value = question;
  deleteConfirmationDialog.value = true;
};

const closeDeleteConfirmationDialog = () => {
    deleteConfirmationDialog.value = false;
    questionToDelete.value = null;
};

const confirmDeleteQuestion = async () => {
  if (!questionToDelete.value) return;
  deletingQuestion.value = true;
  try {
    await apiClientAuth.post("/admin/application?action=deleteQuestion", { id: questionToDelete.value.id });
    // Remove from the dialog list immediately
    questionsInDialog.value = questionsInDialog.value.filter(q => q.id !== questionToDelete.value!.id);
    // Update the main list
    await fetchQuestions();
    closeDeleteConfirmationDialog();
    showSnackbar("Frage gelöscht.", "success");
  } catch (error: any) {
    console.error("Error deleting question:", error);
    showSnackbar(error.response?.data?.error || "Fehler beim Löschen der Frage.", "error");
  } finally {
    deletingQuestion.value = false;
  }
};

const saveQuestions = async () => {
    savingQuestions.value = true;
    // Prepare payload: only send questions that actually exist (have an ID) and potentially changed text
    // Or simply send the whole list from the dialog
    const payload = questionsInDialog.value.map((q, index) => ({
        id: q.id,
        question: q.question,
        type: q.type, // Ensure type is included if needed by backend
        sort_order: q.sort_order // Keep existing sort order for this save action
    }));

    try {
        await apiClientAuth.post("/admin/application?action=saveQuestions", { questions: payload }); // Send as { questions: [...] } if API expects that
        await fetchQuestions(); // Refresh main list
        closeEditDialog();
        showSnackbar("Änderungen gespeichert.", "success");
    } catch (error: any) {
        console.error("Error saving questions:", error);
        showSnackbar(error.response?.data?.error || "Fehler beim Speichern der Fragen.", "error");
    } finally {
        savingQuestions.value = false;
    }
};

const saveQuestionsSort = async () => {
    savingSort.value = true;
     // Update sort_order based on the draggable list order
     const payload = questionsInDialog.value.map((q, index) => ({
         id: q.id,
         sort_order: index, // Assign new sort order based on index
     }));
    try {
        await apiClientAuth.post("/admin/application?action=saveQuestionSorting", { questions: payload }); // Send as { questions: [...] }
        await fetchQuestions(); // Refresh main list to reflect new order
        closeSortingDialog();
        showSnackbar("Reihenfolge gespeichert.", "success");
    } catch (error: any) {
        console.error("Error saving question sorting:", error);
        showSnackbar(error.response?.data?.error || "Fehler beim Speichern der Reihenfolge.", "error");
    } finally {
        savingSort.value = false;
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
                    <v-icon icon="mdi-account-question" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('applicationQuestionsView.title') }}</h1>
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
                    {{ t('applicationQuestionsView.description') }}
                </v-alert>
            </v-col>
        </v-row>

        <!-- Ladezustand -->
        <v-row v-if="loadingData" justify="center" class="my-10">
            <v-col cols="auto" class="text-center">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4 text-medium-emphasis">Lade Daten...</p>
            </v-col>
        </v-row>

        <!-- Dokumenttypen als Karten -->
        <v-row v-else class="mt-4">
            <v-col cols="12" sm="6" md="4" lg="3" v-for="docType in documentTypes" :key="docType.id">
                <v-card 
                    hover 
                    class="position-card elevation-4 fill-height d-flex flex-column"
                >
                    <v-toolbar density="compact" flat color="transparent" class="card-toolbar">
                        <v-spacer></v-spacer>
                        <v-tooltip :text="t('applicationQuestionsView.sort')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn 
                                    icon="mdi-sort" 
                                    density="comfortable" 
                                    variant="text" 
                                    @click.stop="openSortingDialog(docType)" 
                                    v-bind="props"
                                    class="action-icon"
                                ></v-btn>
                            </template>
                        </v-tooltip>
                    </v-toolbar>

                    <v-card-text 
                        class="d-flex flex-column justify-center align-center flex-grow-1 position-card-content"
                        @click="openApplicantQuestionEditor(docType)" 
                    >
                        <v-icon size="64" color="primary" class="mb-4">mdi-file-document-edit-outline</v-icon>
                        <span class="text-h6 font-weight-medium text-center">{{ docType.name }}</span>
                        <span class="text-caption text-grey text-center mt-2">
                            {{ 
                                questions.filter(q => q.type === docType.id).length 
                                ? `${questions.filter(q => q.type === docType.id).length} ${t('applicationQuestionsView.questions')}`
                                : t('applicationQuestionsView.noQuestions')
                            }}
                        </span>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Fragen bearbeiten Dialog -->
        <v-dialog v-model="editDialog" max-width="1000px" persistent scrollable>
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-file-document-edit" class="mr-2"></v-icon>
                    {{ t('applicationQuestionsView.editTitle', { name: currentDocumentType?.name }) }}
                    <v-spacer></v-spacer>
                    <v-btn icon="mdi-close" variant="text" @click="closeEditDialog" size="small"></v-btn>
                </v-card-title>
                
                <v-divider></v-divider>
                
                <v-card-text style="max-height: 60vh;" class="pa-4">
                    <div v-if="loadingQuestionsInDialog" class="text-center pa-5">
                        <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
                        <div class="mt-2">{{ t('applicationQuestionsView.loadingQuestions') }}</div>
                    </div>
                    
                    <div v-else>
                        <div 
                            v-for="(element, index) in questionsInDialog" 
                            :key="element.id || `new-${index}`" 
                            class="question-item mb-3"
                        >
                            <div class="d-flex align-center">
                                <div class="question-number">
                                    <v-chip size="small" color="primary" variant="tonal" class="font-weight-medium">
                                        {{ index + 1 }}
                                    </v-chip>
                                </div>
                                
                                <v-text-field
                                    v-model="element.question"
                                    :label="`Frage ${index + 1}`"
                                    variant="outlined"
                                    density="comfortable"
                                    hide-details="auto"
                                    class="mx-2 flex-grow-1"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                ></v-text-field>
                                
                                <v-tooltip text="Frage löschen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn 
                                            icon="mdi-delete-outline" 
                                            color="error" 
                                            variant="text" 
                                            size="small" 
                                            @click="openDeleteConfirmationDialog(element)" 
                                            v-bind="props"
                                            class="action-icon"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </div>
                        
                        <v-divider class="my-4"></v-divider>
                        
                        <div class="d-flex align-center">
                            <v-text-field
                                v-model="newQuestionText"
                                :label="t('applicationQuestionsView.newQuestion')"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-format-list-checks"
                                append-inner-icon="mdi-plus-circle"
                                @click:append-inner="addQuestion"
                                @keydown.enter.prevent="addQuestion"
                                :loading="addingQuestion"
                                :disabled="addingQuestion"
                                hide-details
                                class="flex-grow-1"
                                color="primary"
                                bg-color="grey-darken-3"
                            ></v-text-field>
                        </div>
                    </div>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeEditDialog">{{ t('cancel') }}</v-btn>
                    <v-btn 
                        color="primary" 
                        variant="elevated" 
                        @click="saveQuestions" 
                        :loading="savingQuestions"
                        prepend-icon="mdi-content-save"
                    >
                        {{ t('saveChanges') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Sortieren Dialog -->
        <v-dialog v-model="sortingDialog" max-width="700px" persistent scrollable>
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-sort" class="mr-2"></v-icon>
                    {{ t('applicationQuestionsView.sortTitle', { name: currentDocumentType?.name }) }}
                    <v-spacer></v-spacer>
                    <v-btn icon="mdi-close" variant="text" @click="closeSortingDialog" size="small"></v-btn>
                </v-card-title>
                
                <v-divider></v-divider>
                
                <v-card-text style="max-height: 60vh;" class="pa-4">
                    <div v-if="loadingQuestionsInDialog" class="text-center pa-5">
                        <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
                        <div class="mt-2">{{ t('applicationQuestionsView.loadingQuestions') }}</div>
                    </div>
                    
                    <div v-else>
                        <p class="text-body-2 mb-4">
                            {{ t('applicationQuestionsView.sortHint') }} <v-icon size="small">mdi-drag-variant</v-icon>
                        </p>
                        
                        <draggable
                            v-model="questionsInDialog"
                            :list="questionsInDialog"
                            tag="v-list"
                            item-key="id"
                            :animation="200"
                            handle=".drag-handle"
                            ghost-class="ghost-item"
                            class="draggable-list"
                        >
                            <template #item="{ element, index }">
                                <v-list-item class="draggable-item mb-2" :key="element.id || `new-${index}`">
                                    <template v-slot:prepend>
                                        <div class="question-number mr-2">
                                            <v-chip size="small" color="primary" variant="tonal" class="font-weight-medium">
                                                {{ index + 1 }}
                                            </v-chip>
                                        </div>
                                        <v-icon 
                                            class="drag-handle mr-2" 
                                            icon="mdi-drag-horizontal-variant"
                                            size="small"
                                        ></v-icon>
                                    </template>
                                    <v-list-item-title>{{ element.question }}</v-list-item-title>
                                </v-list-item>
                            </template>
                        </draggable>
                    </div>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeSortingDialog">{{ t('cancel') }}</v-btn>
                    <v-btn 
                        color="primary" 
                        variant="elevated" 
                        @click="saveQuestionsSort" 
                        :loading="savingSort"
                        prepend-icon="mdi-sort-alphabetical-ascending"
                    >
                        {{ t('applicationQuestionsView.saveOrder') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Löschen Bestätigungsdialog -->
        <v-dialog v-model="deleteConfirmationDialog" max-width="500px" persistent>
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-delete-alert" color="error" class="mr-2"></v-icon>
                    {{ t('applicationQuestionsView.deleteTitle') }}
                </v-card-title>
                
                <v-card-text class="pt-4">
                    <p>{{ t('applicationQuestionsView.deleteConfirm') }}</p>
                    <v-card class="mt-3 pa-3 bg-grey-darken-3">
                        <div class="text-body-2">{{ questionToDelete?.question }}</div>
                    </v-card>
                    <div class="text-caption text-medium-emphasis mt-3">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteConfirmationDialog">{{ t('cancel') }}</v-btn>
                    <v-btn 
                        color="error" 
                        variant="elevated" 
                        @click="confirmDeleteQuestion" 
                        :loading="deletingQuestion"
                        prepend-icon="mdi-delete"
                        class="delete-button"
                    >
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Position Card */
.position-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.position-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.2);
}

.position-card-content {
    padding: 24px;
}

/* Card Toolbar */
.card-toolbar {
    background-color: var(--k-sunken) !important;
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

/* Question Item */
.question-item {
    background: var(--k-sunken);
    border-radius: 8px;
    padding: 8px;
    transition: all 0.2s ease;
}

.question-item:hover {
    background: var(--k-sunken);
}

.question-number {
    min-width: 40px;
    display: flex;
    justify-content: center;
}

/* Draggable Items */
.draggable-list {
    background: var(--k-surface) !important;
    border-radius: 8px;
    padding: 8px;
    border: 1px solid var(--k-line);
}

.draggable-item {
    background: var(--k-sunken) !important;
    border: 1px solid var(--k-line);
    border-radius: 6px;
    transition: all 0.2s ease;
}

.draggable-item:hover {
    background: var(--k-sunken) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

.ghost-item {
    opacity: 0.5;
    background: var(--k-accent-weak) !important;
    border: 1px dashed var(--k-accent-line) !important;
}

/* Dialog Styling */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover));
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
    .position-card {
        margin-bottom: 16px;
    }
}
</style>