<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { CompanyType } from '@/types/Company'; // Adjust path if needed
import { useToast } from 'vue-toastification';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
// --- Interfaces ---
// Interface for form data, making id optional
interface CompanyTypeFormData {
    id?: number | null;
    name: string;
    description: string | null; // Allow null
    sort_order?: number | null; // If sort order is used
}

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
const companyTypes = ref<CompanyType[]>([]);
const loadingTypes = ref(false);
const savingType = ref(false);
const deletingType = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const deleteTypeDialog = ref(false);
const typeFormRef = ref<any>(null);
const isTypeFormValid = ref(false);

const initialFormData: CompanyTypeFormData = { id: null, name: '', description: null };
const selectedType = reactive<CompanyTypeFormData>({ ...initialFormData });
const isEditing = computed(() => !!selectedType.id);
const typeToDelete = ref<CompanyType | null>(null);

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers ---
const typeHeaders = computed(() => [
    { title: t('companyType.headers.name'), key: 'name', sortable: true },
    { title: t('companyType.headers.description'), key: 'description', sortable: false },
    // { title: t('companyType.headers.sortOrder'), key: 'sort_order', sortable: true, align: 'end' }, // Uncomment if sort_order is used
    { title: t('companyType.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
]);

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
const fetchCompanyTypes = async () => {
    loadingTypes.value = true;
    try {
        const response = await apiClientAuth.get<{ data: CompanyType[] }>(
            'company?action=getCompanyTypes'
        ); // Adjust path
        companyTypes.value = (response.data.data || response.data || []).map((type: any) => ({
            ...type,
            description: type.description || '', // Ensure description is a string
        }));
    } catch (error: any) {
        console.error('Error fetching company types:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Firmentypen.', 'error');
        companyTypes.value = [];
    } finally {
        loadingTypes.value = false;
    }
};

// --- Methods ---

// Dialog Openers
const openNewTypeDialog = () => {
    Object.assign(selectedType, { ...initialFormData }); // Reset form
    isTypeFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => typeFormRef.value?.resetValidation(), 100);
};

const openEditTypeDialog = (type: CompanyType) => {
    Object.assign(selectedType, { ...type, description: type.description || '' }); // Load data
    isTypeFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => typeFormRef.value?.resetValidation(), 100);
};

// Dialog Closer
const closeAddEditDialog = () => {
    addEditDialog.value = false;
};

// Save Type (Add/Edit)
const saveType = async () => {
    if (!isTypeFormValid.value) return;
    savingType.value = true;

    const action = isEditing.value ? 'editCompanyType' : 'addCompanyType';
    const payload = { ...selectedType };

    try {
        await apiClientAuth.post(`company?action=${action}`, payload); // Adjust path
        closeAddEditDialog();
        await fetchCompanyTypes(); // Refresh list
        showSnackbar(
            `Firmentyp erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving type (Action: ${action}):`, error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern des Firmentyps.',
            'error'
        );
    } finally {
        savingType.value = false;
    }
};

// Delete Logic
const openDeleteTypeDialog = (type: CompanyType) => {
    typeToDelete.value = type;
    deleteTypeDialog.value = true;
};

const closeDeleteTypeDialog = () => {
    deleteTypeDialog.value = false;
    typeToDelete.value = null;
};

const confirmDeleteType = async () => {
    if (!typeToDelete.value) return;
    deletingType.value = true;
    try {
        await apiClientAuth.post('/company?action=deleteCompanyType', { id: typeToDelete.value.id }); // Adjust path
        closeDeleteTypeDialog();
        await fetchCompanyTypes(); // Refresh list
        showSnackbar('Firmentyp erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting type:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Firmentyps.', 'error');
    } finally {
        deletingType.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(fetchCompanyTypes);

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('CompanyTypeView', () => unref(typeHeaders) as any);
</script>

<template>
    <v-container fluid class="company-type-container pa-4">
        <!-- Header mit Titel und Aktionsbutton -->
        <v-row class="mb-4">
            <v-col cols="12">
                <div class="page-header d-flex align-center justify-space-between flex-wrap">
                    <div>
                        <h1 class="text-h4 font-weight-medium mb-2">
                            <v-icon size="36" class="mr-2">mdi-shape-outline</v-icon>
                            {{ t('companyTypeView.title') }}
                        </h1>
                        <p class="text-body-1 text-medium-emphasis">
                            {{ t('companyTypeView.description') }}
                        </p>
                    </div>

                    <v-btn
                        v-if="canCreate"
                        @click="openNewTypeDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-plus"
                        class="action-button"
                    >
                        {{ t('companyTypeView.newType') }}
                    </v-btn>
                </div>
            </v-col>
        </v-row>

        <!-- Haupttabelle -->
        <v-card class="main-card elevation-4">
            <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
            <KTableToolbar :columns="kCols" :shown="(companyTypes || []).length" />
            <v-data-table
                :headers="kCols.visible.value"
                :items="companyTypes"
                item-value="id"
                :loading="loadingTypes"
                hover
                density="comfortable"
                class="type-table"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span class="type-name">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.description`]="{ item }">
                    <span class="type-description">{{ item.description || '-' }}</span>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-2">
                        <v-tooltip text="Bearbeiten" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditTypeDialog(item)"
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
                                    @click="openDeleteTypeDialog(item)"
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
                        <v-icon size="64" color="grey-darken-1" class="mb-3"
                            >mdi-shape-outline</v-icon
                        >
                        <p class="text-h6 text-grey">{{ t('companyTypeView.noData') }}</p>
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
                        <span>{{ t('companyTypeView.loading') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Dialog zum Hinzufügen/Bearbeiten von Typen -->
        <v-dialog v-model="addEditDialog" max-width="700" class="type-dialog">
            <v-card class="dialog-card">
                <v-toolbar
                    density="compact"
                    :color="isEditing ? 'primary' : 'success'"
                    class="card-toolbar"
                >
                    <v-toolbar-title class="text-subtitle-1">
                        <v-icon start size="18" class="mr-2">
                            {{ isEditing ? 'mdi-pencil' : 'mdi-plus' }}
                        </v-icon>
                        {{ isEditing ? t('companyTypeView.editTitle') : t('companyTypeView.addTitle') }}
                    </v-toolbar-title>
                </v-toolbar>

                <v-form ref="typeFormRef" v-model="isTypeFormValid">
                    <v-card-text class="pa-4">
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="selectedType.name"
                                    label="Name*"
                                    required
                                    :rules="[requiredRule('Name')]"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-format-title"
                                    class="field-item mb-4"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    v-model="selectedType.description"
                                    :label="$t('descriptionOptional')"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-text-box-outline"
                                    rows="4"
                                    class="field-item"
                                ></v-textarea>
                            </v-col>
                        </v-row>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog" class="mr-2">
                            Abbrechen
                        </v-btn>

                        <v-btn
                            v-if="isEditing ? canEdit : canCreate"
                            :color="isEditing ? 'primary' : 'success'"
                            variant="elevated"
                            @click="saveType"
                            :disabled="!isTypeFormValid"
                            :loading="savingType"
                            class="save-button"
                        >
                            <v-icon start>{{ isEditing ? 'mdi-content-save' : 'mdi-plus' }}</v-icon>
                            {{ isEditing ? 'Speichern' : 'Hinzufügen' }}
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Dialog zum Löschen eines Typs -->
        <v-dialog v-model="deleteTypeDialog" max-width="500" class="delete-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ t('companyTypeView.deleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>{{ t('companyTypeView.deleteConfirm', { name: typeToDelete?.name }) }}</p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        {{ t('companyTypeView.deleteWarning') }}
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteTypeDialog" class="mr-2">
                        {{ t('companyTypeView.cancel') }}
                    </v-btn>

                    <v-btn
                        v-if="canDelete"
                        color="error"
                        variant="elevated"
                        @click="confirmDeleteType"
                        :loading="deletingType"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        {{ t('companyTypeView.delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<style scoped>

.company-type-container {
    min-height: 89vh;
    background-color: var(--k-canvas);
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

/* Main Card */
.main-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Type Table */
.type-table {
    border-radius: 8px;
    overflow: hidden;
}

.type-name {
    font-weight: 500;
    color: #e2e8f0;
}

.type-description {
    font-size: 0.9rem;
    color: var(--k-ink-muted);
}

/* Action Icons */
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
.type-dialog :deep(.v-overlay__content),
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

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

.dialog-title {
    background: linear-gradient(90deg, #991b1b, #dc2626);
    color: var(--k-ink);
    padding: 16px;
}

/* Form Fields */
.field-item {
    border-radius: 8px;
    transition: all var(--transition-timing);
}

.field-item:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Buttons */
.save-button,
.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.save-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
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

    .field-item {
        margin-bottom: 8px;
    }
}
</style>
