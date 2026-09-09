<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { Category } from '@/types/Report'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import TiptapEditor from '@/components/TiptapEditor.vue';

// --- Components & Stores ---
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
const categories = ref<Category[]>([]);
const roles = ref<any[]>([]); // Array für verfügbare Rollen
const loadingCategories = ref(false);
const loadingRoles = ref(false);
const savingCategory = ref(false);
const deletingCategory = ref(false);

// --- Dialog States ---
const categoryDialog = ref(false); // Combined Add/Edit dialog
const deleteCategoryDialog = ref(false);

// --- Form State & Data ---
const categoryFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isCategoryFormValid = ref(false);
const initialFormData: Omit<Category, 'id'> & { id?: number; roleIds?: number[] } = {
    name: '',
    title: '',
    template: '',
    roleIds: []
};
const categoryFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!categoryFormData.id);

// --- Data for Dialogs ---
const categoryToDelete = ref<Category | null>(null);

// --- Snackbar ---
const errorSnackbar = ref({
    visible: false,
    message: '',
    color: 'error', // Default color
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const categoryHeaders = computed(() => [
    { title: t('reportCategory.headers.name'), key: 'name', sortable: true },
    { title: t('reportCategory.headers.title'), key: 'title', sortable: true },
    { title: t('reportCategory.headers.roles'), key: 'roles', sortable: false },
    { title: t('reportCategory.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);

// --- Validation Rules ---
const requiredRule = (value: string) => !!value || 'Dieses Feld ist erforderlich.';

// --- Data Fetching ---
const fetchRoles = async () => {
    loadingRoles.value = true;
    try {
        const response = await apiClientAuth.get('/admin/roles?action=getRoles');
        roles.value = response.data;
    } catch (error: any) {
        console.error('Error fetching roles:', error);
        showSnackbar('Fehler beim Laden der Rollen.', 'error');
    } finally {
        loadingRoles.value = false;
    }
};

const fetchCategories = async () => {
    loadingCategories.value = true;
    try {
        // Adjust if data is nested, e.g., response.data.data
        const response = await apiClientAuth.get<any>('report/?action=getCategories');
        
        console.log("API Response:", response.data);
        
        // Handle nested data structure - response could be {categories: [...]} or directly array
        let categoriesData = response.data;
        
        if (categoriesData && typeof categoriesData === 'object' && !Array.isArray(categoriesData)) {
            // Check if data is in a nested 'categories' property
            if (categoriesData.categories && Array.isArray(categoriesData.categories)) {
                console.log('Found categories in nested structure');
                categoriesData = categoriesData.categories;
            }
        }
        
        // Ensure we have an array to work with
        if (!Array.isArray(categoriesData)) {
            console.warn('Categories data is not an array after processing:', categoriesData);
            categoriesData = [];
        }
        
        // Ensure template is always a string, even if null/undefined from API
        categories.value = categoriesData.map((cat: any) => ({
            ...cat,
            template: cat.template || '',
            roles: cat.roles || []
        }));
        
        console.log(`Loaded ${categories.value.length} categories successfully:`, categories.value);
    } catch (error: any) {
        console.error('Error fetching categories:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Kategorien.', 'error');
        categories.value = []; // Clear data on error
    } finally {
        loadingCategories.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openNewCategoryDialog = () => {
    Object.assign(categoryFormData, { ...initialFormData, id: undefined }); // Reset form
    isCategoryFormValid.value = false; // Reset validation state
    categoryDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => categoryFormRef.value?.resetValidation(), 100);
};

const openEditCategoryDialog = (category: Category) => {
    // Ensure template is a string before assigning
    Object.assign(categoryFormData, {
        ...category,
        template: category.template || '',
        roleIds: category.roles?.map(role => role.id) || []
    });
    isCategoryFormValid.value = false; // Reset validation state
    categoryDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => categoryFormRef.value?.resetValidation(), 100);
};

const closeCategoryDialog = () => {
    categoryDialog.value = false;
};

const saveCategory = async () => {
    if (!isCategoryFormValid.value) return;

    savingCategory.value = true;
    const action = isEditing.value ? 'editCategory' : 'addCategory';
    const payload = { ...categoryFormData };

    console.log('Saving category with payload:', payload);
    console.log('Selected roleIds:', categoryFormData.roleIds);

    try {
        const response = await apiClientAuth.post(`/report/?action=${action}`, payload);
        console.log('Server response:', response.data);
        await fetchCategories();
        closeCategoryDialog();
        showSnackbar(
            `Kategorie erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving category (Action: ${action}):`, error);
        console.error('Error response:', error.response?.data);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Kategorie.',
            'error'
        );
    } finally {
        savingCategory.value = false;
    }
};

// Delete Dialog Logic
const openDeleteCategoryDialog = (category: Category) => {
    categoryToDelete.value = category;
    deleteCategoryDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteCategoryDialog.value = false;
    categoryToDelete.value = null;
};

const confirmDeleteCategory = async () => {
    if (!categoryToDelete.value) return;

    deletingCategory.value = true;
    try {
        await apiClientAuth.post('/report/?action=deleteCategory', {
            id: categoryToDelete.value.id,
        });
        await fetchCategories(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar(t('reportCategorieView.messages.categoryDeleted'), 'success');
    } catch (error: any) {
        console.error('Error deleting category:', error);
        showSnackbar(error.response?.data?.error || t('reportCategorieView.messages.deleteCategoryError'), 'error');
        // Keep dialog open on error? Closing for now.
        // closeDeleteDialog();
    } finally {
        deletingCategory.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchCategories(); // Fetch data when component mounts
    fetchRoles();
});

// Stellen Sie sicher, dass die Tabelle immer ein Array erhält, selbst wenn Daten fehlschlagen
const tableItems = computed(() => {
    if (!categories.value || !Array.isArray(categories.value)) {
        return [];
    }
    return categories.value;
});
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="main-container pa-4">
        <!-- Header mit Titel und Aktionsbutton -->
        <v-row class="mb-4">
            <v-col cols="12">
                <div class="page-header d-flex align-center justify-space-between flex-wrap">
                    <div>
                        <h1 class="text-h4 font-weight-medium mb-2">
                            <v-icon size="36" class="mr-2">mdi-folder-multiple-outline</v-icon>
                            {{ $t('reportCategorieView.title') }}
                        </h1>
                        <p class="text-body-1 text-medium-emphasis">
                            {{ $t('reportCategorieView.subtitle') }}
                        </p>
                    </div>

                    <v-btn
                        v-if="canCreate"
                        @click="openNewCategoryDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-plus"
                        class="action-button"
                    >
                        {{ $t('reportCategorieView.new') }}
                    </v-btn>
                </div>
            </v-col>
        </v-row>

        <!-- Tabelle -->
        <v-card class="main-card" elevation="4">
            <v-data-table
                :headers="categoryHeaders"
                :items="tableItems"
                :loading="loadingCategories"
                item-value="id"
                hover
                density="comfortable"
                class="data-table"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span class="item-title">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.roles`]="{ item }">
                    <div class="d-flex flex-wrap gap-1">
                        <v-chip
                            v-for="role in item.roles"
                            :key="role.id"
                            size="small"
                            color="primary"
                            variant="outlined"
                        >
                            {{ role.name }}
                        </v-chip>
                    </div>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="action-buttons">
                        <v-tooltip :text="t('edit')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditCategoryDialog(item)"
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
                                    @click="openDeleteCategoryDialog(item)"
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
                            >mdi-folder-off-outline</v-icon
                        >
                        <p class="text-h6 text-grey">{{ $t('reportCategorieView.noData') }}</p>
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
                        <span>{{ $t('loadingCategories') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="categoryDialog" max-width="800" class="dialog-container">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon color="primary" class="mr-2">
                        {{ isEditing ? 'mdi-folder-edit-outline' : 'mdi-folder-plus-outline' }}
                    </v-icon>
                    {{ isEditing ? $t('reportCategorieView.editTitle') : $t('reportCategorieView.addTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <v-form ref="categoryFormRef" v-model="isCategoryFormValid">
                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
                                {{ t('reportCategorieView.basicData') }}
                            </div>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        :label="t('reportCategorieView.name')"
                                        v-model="categoryFormData.name"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prepend-inner-icon="mdi-folder"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" md="6">
                                    <v-text-field
                                        :label="t('reportCategorieView.titleForReports')"
                                        v-model="categoryFormData.title"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        prepend-inner-icon="mdi-format-title"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </div>

                        <!-- Neue Sektion für Rollenauswahl -->
                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-account-group-outline</v-icon>
                                {{ t('reportCategorieView.authorizedRoles') }}
                            </div>

                            <v-select
                                v-model="categoryFormData.roleIds"
                                :items="roles"
                                item-title="name"
                                item-value="id"
                                :label="t('reportCategorieView.selectRoles')"
                                multiple
                                chips
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                :loading="loadingRoles"
                            >
                                <template v-slot:chip="{ props, item }">
                                    <v-chip
                                        v-bind="props"
                                        :text="item.raw.name"
                                        color="primary"
                                        variant="outlined"
                                    ></v-chip>
                                </template>
                            </v-select>
                        </div>

                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-text-box-edit-outline</v-icon>
                                {{ t('reportCategorieView.templateOptional') }}
                            </div>

                            <div class="editor-container">
                                <TiptapEditor
                                    v-model="categoryFormData.template"
                                    :placeholder="$t('reportCategorieView.templatePlaceholder')"
                                    :show-character-count="false"
                                    :show-source-button="true"
                                    :show-table-of-contents="true"
                                    :editable="true"
                                />
                            </div>
                        </div>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeCategoryDialog" class="mr-2"
                        >{{ t('cancel') }}</v-btn
                    >
                    <v-btn
                        v-if="isEditing ? canEdit : canCreate"
                        color="primary"
                        variant="elevated"
                        @click="saveCategory"
                        :disabled="!isCategoryFormValid"
                        :loading="savingCategory"
                        class="action-button"
                    >
                        <v-icon start>mdi-content-save</v-icon>
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteCategoryDialog" max-width="500" class="delete-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ $t('reportCategorieView.deleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        {{
                            $t('reportCategorieView.confirmDelete', {
                                name: categoryToDelete?.name
                            })
                        }}
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        {{ $t('deleteWarning') }}
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteDialog" class="mr-2">
                        Abbrechen
                    </v-btn>
                    <v-btn
                        v-if="canDelete"
                        color="error"
                        variant="elevated"
                        @click="confirmDeleteCategory"
                        :loading="deletingCategory"
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
.main-container {
    min-height: 89vh;
    background-color: #111723;
    background-image:
        radial-gradient(circle at 15% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 30%);
    color: #e2e8f0;
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--v-theme-primary);
}

/* Editor */
.editor-container {
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
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
    background: linear-gradient(90deg, #1e3a8a, #3b82f6);
    color: white;
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
    min-height: 250px;
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

/* Neue Styles für Rollen-Chips */
.v-chip {
    margin: 2px;
}

/* Anpassung der Select-Komponente */
.v-select {
    margin-top: 8px;
}

.v-select :deep(.v-field__input) {
    min-height: 48px;
}
</style>
