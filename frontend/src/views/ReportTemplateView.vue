<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import TiptapEditor from '@/components/TiptapEditor.vue';
import { useToast } from 'vue-toastification';

// --- Define Local Interface ---
interface Template {
    id: number;
    name: string;
    template: string; // Content from CKEditor
}

// No global store needed here directly

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
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Component State ---
const templates = ref<Template[]>([]);
const loadingTemplates = ref(false);
const savingTemplate = ref(false);
const deletingTemplate = ref(false);

// Computed property to ensure v-data-table always gets an array
const tableItems = computed(() => {
    return Array.isArray(templates.value) ? templates.value : [];
});

// --- Dialog States ---
const templateDialog = ref(false); // Combined Add/Edit dialog
const deleteTemplateDialog = ref(false);

// --- Form State & Data ---
const templateFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isTemplateFormValid = ref(false);
const initialFormData: Omit<Template, 'id'> & { id?: number } = {
    name: '',
    template: '',
};
const templateFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!templateFormData.id);

// --- Data for Dialogs ---
const templateToDelete = ref<Template | null>(null);

// --- Snackbar ---
const toast = useToast();

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers ---
const templateHeaders = computed(() => [
    { title: t('reportTemplate.headers.name'), key: 'name', sortable: true },
    { title: t('reportTemplate.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);

// --- Validation Rules ---
const requiredRule = (value: string) => !!value || 'Dieses Feld ist erforderlich.';
// Add rule for template if needed, though often just checking non-empty is enough
const templateRequiredRule = (value: string) =>
    (value && value.trim() !== '') || 'Template Inhalt darf nicht leer sein.';

// --- Data Fetching ---
const fetchTemplates = async () => {
    loadingTemplates.value = true;
    try {
        console.log('Fetching templates...');
        
        // Get response from API
        const response = await apiClientAuth.get('/report/?action=getTemplates');
        console.log('Template response:', response);
        
        // Extract templates from the correct property in the response
        if (response.data && response.data.templates && Array.isArray(response.data.templates)) {
            templates.value = response.data.templates;
            console.log('Found templates array:', templates.value);
        } else {
            console.warn('API did not return expected format:', response.data);
            templates.value = [];
        }
    } catch (error: any) {
        console.error('Error fetching templates:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Templates.', 'error');
        templates.value = []; // Clear data on error
    } finally {
        loadingTemplates.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openNewTemplateDialog = () => {
    Object.assign(templateFormData, { ...initialFormData, id: undefined }); // Reset form
    isTemplateFormValid.value = false; // Reset validation state
    templateDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => templateFormRef.value?.resetValidation(), 100);
};

const openEditTemplateDialog = (template: Template) => {
    Object.assign(templateFormData, { ...template }); // Load data
    isTemplateFormValid.value = false; // Reset validation state
    templateDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => templateFormRef.value?.resetValidation(), 100);
};

const closeTemplateDialog = () => {
    templateDialog.value = false;
};

const saveTemplate = async () => {
    // Manually check template content because v-form doesn't validate CKEditor directly well
    if (!templateFormData.name || !templateFormData.template) {
        isTemplateFormValid.value = false; // Ensure form state reflects reality
        showSnackbar('Name und Template Inhalt sind erforderlich.', 'warning');
        return;
    }
    // Trigger Vuetify form validation for the name field
    // const { valid } = await templateFormRef.value?.validate();
    // if (!valid) return;
    // Combine checks
    if (!isTemplateFormValid.value) return;

    savingTemplate.value = true;
    const action = isEditing.value ? 'editTemplate' : 'addTemplate';
    const payload = { ...templateFormData };

    try {
        await apiClientAuth.post(`/report/?action=${action}`, payload);
        await fetchTemplates(); // Refresh list
        closeTemplateDialog(); // Close dialog
        showSnackbar(
            `Template erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving template (Action: ${action}):`, error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern des Templates.',
            'error'
        );
    } finally {
        savingTemplate.value = false;
    }
};

// Delete Dialog Logic
const openDeleteTemplateDialog = (template: Template) => {
    templateToDelete.value = template;
    deleteTemplateDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteTemplateDialog.value = false;
    templateToDelete.value = null;
};

const confirmDeleteTemplate = async () => {
    if (!templateToDelete.value) return;

    deletingTemplate.value = true;
    try {
        await apiClientAuth.post('/report/?action=deleteTemplate', {
            id: templateToDelete.value.id,
        });
        await fetchTemplates(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar('Template erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting template:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Templates.', 'error');
        // Keep dialog open on error? Closing for now.
        // closeDeleteDialog();
    } finally {
        deletingTemplate.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchTemplates(); // Fetch data when component mounts
});
</script>

<template>
    <v-container fluid class="main-container pa-4">
        <!-- Header mit Titel und Aktionsbutton -->
        <v-row class="mb-4">
            <v-col cols="12">
                <div class="page-header d-flex align-center justify-space-between flex-wrap">
                    <div>
                        <h1 class="text-h4 font-weight-medium mb-2">
                            <v-icon size="36" class="mr-2"
                                >mdi-file-document-multiple-outline</v-icon
                            >
                            {{ $t('reportTemplateView.title') }}
                        </h1>
                        <p class="text-body-1 text-medium-emphasis">
                            {{ $t('reportTemplateView.subtitle') }}
                        </p>
                    </div>

                    <v-btn
                        v-if="canEdit"
                        @click="openNewTemplateDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-plus"
                        class="action-button"
                    >
                        {{ $t('reportTemplateView.new') }}
                    </v-btn>
                </div>
            </v-col>
        </v-row>

        <!-- Tabelle -->
        <v-card class="main-card" elevation="4">
            <!-- Filterleiste: Anzahl der Eintraege, wie im Entwurf. -->
            <div class="k-toolbar">
                <span class="k-toolbar__spacer"></span>
                <span class="k-toolbar__count">{{ $t("common.entries", { n: (tableItems || []).length }) }}</span>
            </div>
            <v-data-table
                :headers="templateHeaders"
                :items="tableItems"
                :loading="loadingTemplates"
                item-value="id"
                hover
                density="comfortable"
                class="data-table"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span class="item-title">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="action-buttons">
                        <v-tooltip text="Bearbeiten" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditTemplateDialog(item)"
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
                                    @click="openDeleteTemplateDialog(item)"
                                    v-bind="props"
                                    class="action-icon"
                                    color="error"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:no-data>
                    <div class="empty-state">
                        <v-icon size="64" color="grey-darken-1" class="mb-3"
                            >mdi-file-document-off-outline</v-icon
                        >
                        <p class="text-h6 text-grey">{{ $t('reportTemplateView.noData') }}</p>
                    </div>
                </template>

                <template v-slot:loading>
                    <div class="loading-state">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="32"
                            class="mr-3"
                        ></v-progress-circular>
                        <span>{{ $t('reportTemplateView.loading') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="templateDialog" max-width="900" class="dialog-container">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon color="primary" class="mr-2">
                        {{
                            isEditing
                                ? 'mdi-file-document-edit-outline'
                                : 'mdi-file-document-plus-outline'
                        }}
                    </v-icon>
                    {{ isEditing ? $t('reportTemplateView.editTitle') : $t('reportTemplateView.addTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <v-form ref="templateFormRef" v-model="isTemplateFormValid">
                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
                                Template-Informationen
                            </div>

                            <v-text-field
                                label="Name"
                                v-model="templateFormData.name"
                                required
                                :rules="[requiredRule]"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                prepend-inner-icon="mdi-format-title"
                            ></v-text-field>
                        </div>

                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-text-box-edit-outline</v-icon>
                                Template Inhalt
                            </div>

                            <div class="editor-container">
                                <TiptapEditor
                                    v-model="templateFormData.template"
                                    placeholder="Geben Sie hier den Inhalt des Templates ein..."
                                    :show-character-count="false"
                                    :show-source-button="true"
                                    :show-table-of-contents="true"
                                    :editable="true"
                                />
                            </div>

                            <div v-if="!isTemplateFormValid" class="validation-errors mt-2">
                                <div
                                    v-if="!templateFormData.template"
                                    class="text-error text-caption"
                                >
                                    <v-icon start size="small" color="error"
                                        >mdi-alert-circle</v-icon
                                    >
                                    Template Inhalt ist erforderlich.
                                </div>
                            </div>
                        </div>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeTemplateDialog" class="mr-2"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="saveTemplate"
                        :disabled="!isTemplateFormValid"
                        :loading="savingTemplate"
                        class="action-button"
                    >
                        <v-icon start>mdi-content-save</v-icon>
                        Speichern
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteTemplateDialog" max-width="500" class="delete-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Template löschen
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>Möchten Sie das Template "{{ templateToDelete?.name }}" wirklich löschen?</p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteDialog" class="mr-2">
                        Abbrechen
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="confirmDeleteTemplate"
                        :loading="deletingTemplate"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Löschen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

.main-container {
    min-height: 89vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 15% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 30%);
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
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
}

/* Cards */
.main-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Data Table */
.data-table {
    border-radius: 8px;
    overflow: hidden;
}

.item-title {
    font-weight: 500;
    color: #e2e8f0;
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

/* Editor */
.editor-container {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
}

/* Validation Errors */
.validation-errors {
    padding: 8px 12px;
    background-color: rgba(220, 38, 38, 0.1);
    border-radius: 8px;
    border-left: 3px solid rgba(220, 38, 38, 0.5);
}

/* Dialog Styling */
.dialog-container :deep(.v-overlay__content),
.delete-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-card {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent));
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

/* Editor specific styles */
:deep(.tiptap-editor-container) {
    min-height: 300px;
    border-radius: 8px;
    overflow: hidden;
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
}
</style>
