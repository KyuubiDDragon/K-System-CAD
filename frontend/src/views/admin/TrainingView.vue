<script setup lang="ts">
import { ref, onMounted, reactive, computed, type Ref, unref } from 'vue';
import { useRoute } from 'vue-router'; // Import useRoute if permissions are needed
import { useI18n } from 'vue-i18n'; // Import i18n for translations
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import auth store
import { useModulePermission } from '@/composables/useModulePermission';
// Import Training type and define TrainingCategory interface
import type { Training } from '@/types/Training';
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
// Initialize i18n
const { t } = useI18n();

// Define the missing TrainingCategory interface
interface TrainingCategory {
    id: number;
    name: string;
    short: string;
    sort_order: number;
}

// Form validation rules
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

// --- Define Interfaces ---
// Type for the shared form data (adjust properties based on actual types)
interface ItemFormData {
    id?: number | null;
    name: string;
    sort_order: number | null;
    short?: string; // Specific to Category
    catId?: number | null; // Specific to Training
}

// --- Router & Permissions ---
const route = useRoute(); // If using route meta for permissions
const authStore = useAuthStore();

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

console.log('Admin Training View props from desktop window:', {
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
  !!route.meta?.canEdit || 
  true // Default to true for admin view
);

const canDelete = computed(() => 
  isAdmin.value || 
  props.canDelete || 
  props.meta?.canDelete || 
  !!route.meta?.canDelete || 
  true // Default to true for admin view
);

// --- Component State ---
const trainings = ref<Training[]>([]);
const categories = ref<TrainingCategory[]>([]);
const loadingTrainings = ref(false);
const loadingCategories = ref(false);
const savingItem = ref(false);
const deletingItem = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const confirmDeleteDialog = ref(false);
const itemFormRef = ref<any>(null);
const isItemFormValid = ref(false);
const initialFormData: ItemFormData = { id: null, name: '', sort_order: 0, short: '', catId: null };
const selectedItem = reactive<ItemFormData>({ ...initialFormData });
const itemType = ref<'training' | 'category' | null>(null); // To track which type is being edited/added/deleted
const itemToDelete = ref<{
    item: Training | TrainingCategory;
    type: 'training' | 'category';
} | null>(null);
const isEditing = computed(() => !!selectedItem.id);
const dialogTitle = computed(() => {
    if (!itemType.value) return '';
    const typeName = itemType.value === 'training' ? 'Training' : 'Kategorie';
    return isEditing.value ? `${typeName} bearbeiten` : `Neue ${typeName}`;
});

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const trainingHeaders = computed(() => [
    { title: t('adminTraining.headers.id'), key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: t('adminTraining.headers.name'), key: 'name', sortable: true },
    { title: t('adminTraining.headers.category'), key: 'cat_short', align: 'start', sortable: true }, // Display category short name
    { title: t('adminTraining.headers.sortOrder'), key: 'sort_order', sortable: true, align: 'end' },
    { title: t('adminTraining.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const);

const categoryHeaders = computed(() => [
    { title: t('adminTraining.headers.id'), key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: t('adminTraining.headers.name'), key: 'name', sortable: true },
    { title: t('adminTraining.headers.abbreviation'), key: 'short', sortable: true },
    { title: t('adminTraining.headers.sortOrder'), key: 'sort_order', sortable: true, align: 'end' },
    { title: t('adminTraining.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const);

// --- Data Fetching ---
const fetchData = async (
    action: string,
    targetRef: Ref<any[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string
) => {
    loadingRef.value = true;
    try {
        const response = await apiClientAuth.get<any[]>(
            `/admin/training?action=${action}`
        ); // Adjust path, using POST as in original
        targetRef.value = (response.data || response.data || []).sort(
            (a, b) => (a.sort_order ?? 999) - (b.sort_order ?? 999)
        ); // Sort by sort_order
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

const fetchTrainings = () =>
    fetchData('getTrainings', trainings, loadingTrainings, 'Fehler beim Laden der Trainings.');
const fetchCategories = () =>
    fetchData('getCategories', categories, loadingCategories, 'Fehler beim Laden der Kategorien.');

const fetchAllInitialData = () => {
    fetchTrainings();
    fetchCategories();
};

// --- Methods ---

// Add/Edit Dialog Logic
const openNewItemDialog = (type: 'training' | 'category') => {
    Object.assign(selectedItem, { ...initialFormData }); // Reset form
    itemType.value = type;
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openEditItemDialog = (item: Training | TrainingCategory, type: 'training' | 'category') => {
    Object.assign(selectedItem, { ...item }); // Load data
    itemType.value = type;
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const closeAddEditDialog = () => {
    addEditDialog.value = false;
};

const saveItem = async () => {
    if (!isItemFormValid.value || !itemType.value) return;
    savingItem.value = true;

    const action = itemType.value === 'training' ? 'saveTrainings' : 'saveCategories'; // Assuming these handle add/edit
    const payload = { ...selectedItem };
    // Remove properties not relevant to the current type before sending
    if (itemType.value === 'training') {
        delete payload.short;
    } else {
        // category
        delete payload.catId;
    }

    try {
        await apiClientAuth.post(`/admin/training?action=${action}`, payload); // Adjust path
        closeAddEditDialog();
        showSnackbar(
            `${itemType.value === 'training' ? 'Training' : 'Kategorie'} erfolgreich gespeichert.`,
            'success'
        );

        // Refresh the correct list
        if (itemType.value === 'training') {
            await fetchTrainings();
        } else {
            await fetchCategories();
        }
    } catch (error: any) {
        console.error(`Error saving ${itemType.value}:`, error);
        showSnackbar(
            error.response?.data?.error || `Fehler beim Speichern (${itemType.value}).`,
            'error'
        );
    } finally {
        savingItem.value = false;
    }
};

// Delete Confirmation Logic
const openConfirmDeleteDialog = (
    item: Training | TrainingCategory,
    type: 'training' | 'category'
) => {
    itemToDelete.value = { item, type };
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    itemToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!itemToDelete.value) return;

    deletingItem.value = true;
    const { item, type } = itemToDelete.value;
    const action = type === 'training' ? 'deleteTrainings' : 'deleteCategories';

    try {
        await apiClientAuth.post(`/admin/training?action=${action}`, { id: item.id }); // Adjust path
        closeConfirmDeleteDialog();
        showSnackbar(
            `${type === 'training' ? 'Training' : 'Kategorie'} erfolgreich gelöscht.`,
            'success'
        );

        // Refresh the correct list
        if (type === 'training') {
            await fetchTrainings();
        } else {
            await fetchCategories();
            // Also refresh trainings, as category assignment might change/be removed
            await fetchTrainings();
        }
    } catch (error: any) {
        console.error(`Error deleting ${type}:`, error);
        showSnackbar(error.response?.data?.error || `Fehler beim Löschen (${type}).`, 'error');
    } finally {
        deletingItem.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchAllInitialData();
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/TrainingView', () => unref(trainingHeaders) as any);
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-school" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">Training Management</h1>
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
                    Hier können Sie Trainings und Kategorien verwalten, die den Mitarbeitern zugewiesen werden können.
                </v-alert>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="6">
                <v-card class="main-card elevation-4">
                    <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                        <v-toolbar-title class="text-h6">
                            <v-icon start size="20" class="mr-2">mdi-certificate</v-icon>
                            Trainings
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="canEdit"
                            @click="openNewItemDialog('training')"
                            color="primary"
                            variant="elevated"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            Neues Training
                        </v-btn>
                    </v-toolbar>
                    
                    <v-divider></v-divider>
                    
                    <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                    <KTableToolbar :columns="kCols" :shown="(trainings || []).length" />
                    <v-data-table
                        :headers="kCols.visible.value"
                        :items="trainings"
                        item-value="id"
                        class="elevation-0"
                        :loading="loadingTrainings"
                        density="comfortable"
                        hover
                    >
                        <template v-slot:[`item.id`]="{ item }">
                            <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                                {{ item.id }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.name`]="{ item }">
                            <span class="font-weight-medium">{{ item.name }}</span>
                        </template>
                        
                        <template v-slot:[`item.cat_short`]="{ item }">
                            <v-chip size="small" label variant="tonal" color="info" class="category-chip">
                                {{ item.cat_short }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.sort_order`]="{ item }">
                            <span class="text-grey">{{ item.sort_order }}</span>
                        </template>
                        
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-1">
                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditItemDialog(item, 'training')"
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
                                            @click="openConfirmDeleteDialog(item, 'training')"
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
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-certificate-outline</v-icon>
                                <span>Keine Trainings gefunden.</span>
                            </div>
                        </template>
                        
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>Lade Trainings...</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card class="main-card elevation-4">
                    <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                        <v-toolbar-title class="text-h6">
                            <v-icon start size="20" class="mr-2">mdi-tag-multiple</v-icon>
                            Kategorien
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="canEdit"
                            @click="openNewItemDialog('category')"
                            color="primary"
                            variant="elevated"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            Neue Kategorie
                        </v-btn>
                    </v-toolbar>
                    
                    <v-divider></v-divider>
                    
                    <v-data-table
                        :headers="categoryHeaders"
                        :items="categories"
                        item-value="id"
                        class="elevation-0"
                        :loading="loadingCategories"
                        density="comfortable"
                        hover
                    >
                        <template v-slot:[`item.id`]="{ item }">
                            <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                                {{ item.id }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.name`]="{ item }">
                            <span class="font-weight-medium">{{ item.name }}</span>
                        </template>
                        
                        <template v-slot:[`item.short`]="{ item }">
                            <v-chip size="small" label variant="tonal" color="primary" class="short-chip">
                                {{ item.short }}
                            </v-chip>
                        </template>
                        
                        <template v-slot:[`item.sort_order`]="{ item }">
                            <span class="text-grey">{{ item.sort_order }}</span>
                        </template>
                        
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-1">
                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditItemDialog(item, 'category')"
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
                                            @click="openConfirmDeleteDialog(item, 'category')"
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
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-tag-off-outline</v-icon>
                                <span>Keine Kategorien gefunden.</span>
                            </div>
                        </template>
                        
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>Lade Kategorien...</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="addEditDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-toolbar density="compact" color="primary" class="dialog-toolbar">
                    <v-toolbar-title>
                        <v-icon
                            :icon="isEditing
                                ? (itemType === 'training' ? 'mdi-certificate-edit' : 'mdi-tag-edit')
                                : (itemType === 'training' ? 'mdi-certificate-plus' : 'mdi-tag-plus')"
                            class="mr-2"
                        ></v-icon>
                        {{ dialogTitle }}
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                    <add-shortcut-button
                      v-if="isEditing && itemType === 'training' && selectedItem.id"
                      type="training"
                      :resource-id="selectedItem.id"
                      :title="selectedItem.name"
                      icon="mdi-school"
                      color="purple"
                    />
                </v-toolbar>
                
                <v-form ref="itemFormRef" v-model="isItemFormValid">
                    <v-card-text class="pa-4">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedItem.name"
                                        label="Name"
                                        required
                                        :rules="[requiredRule('Name')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        :prepend-inner-icon="itemType === 'training' ? 'mdi-certificate' : 'mdi-tag'"
                                        class="mb-3"
                                    ></v-text-field>
                                </v-col>
                                
                                <v-col cols="12" v-if="itemType === 'category'">
                                    <v-text-field
                                        v-model="selectedItem.short"
                                        label="Kürzel"
                                        required
                                        :rules="[requiredRule('Kürzel')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-alphabetical"
                                        hint="Kurzes Akronym für die Anzeige"
                                        persistent-hint
                                        class="mb-3"
                                    ></v-text-field>
                                </v-col>
                                
                                <v-col cols="12" v-if="itemType === 'training'">
                                    <v-select
                                        v-model="selectedItem.catId"
                                        :items="categories"
                                        item-title="name"
                                        item-value="id"
                                        label="Kategorie"
                                        required
                                        :rules="[requiredRule('Kategorie')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-tag-multiple"
                                        class="mb-3"
                                    >
                                        <template v-slot:item="{ item, props }">
                                            <v-list-item v-bind="props">
                                                <template v-slot:prepend>
                                                    <v-icon icon="mdi-tag" class="mr-2" size="small"></v-icon>
                                                </template>
                                                <v-list-item-title>
                                                    {{ item.raw.name }}
                                                    <span class="text-caption text-grey ml-2">({{ item.raw.short }})</span>
                                                </v-list-item-title>
                                            </v-list-item>
                                        </template>
                                    </v-select>
                                </v-col>
                                
                                <v-col cols="12">
                                    <v-text-field
                                        v-model.number="selectedItem.sort_order"
                                        label="Sortierung"
                                        type="number"
                                        required
                                        :rules="[requiredRule('Sortierung')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-sort"
                                        hint="Bestimmt die Anzeigereihenfolge"
                                        persistent-hint
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    
                    <v-divider></v-divider>
                    
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog">Abbrechen</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveItem"
                            :disabled="!isItemFormValid"
                            :loading="savingItem"
                        >
                            Speichern
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Löschen bestätigen
                </v-card-title>
                
                <v-card-text class="pt-4">
                    <p>
                        Willst du {{ itemToDelete?.type === 'training' ? 'das Training' : 'die Kategorie' }} 
                        <span class="font-weight-bold">"{{ itemToDelete?.item?.name }}"</span> 
                        wirklich löschen?
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                    <v-alert
                        v-if="itemToDelete?.type === 'category'"
                        class="mt-4"
                        border="start"
                        border-color="warning"
                        elevation="2"
                        density="compact"
                        icon="mdi-alert"
                        variant="tonal"
                        color="warning"
                    >
                        Hinweis: Das Löschen einer Kategorie kann bestehende Trainings beeinflussen.
                    </v-alert>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2">Abbrechen</v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingItem"
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

/* Main Container */
.training-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Action Button */
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

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Main Card */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* Chips */
.id-chip, .category-chip, .short-chip {
    min-width: 36px;
    justify-content: center;
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

/* Empty and Loading States */
.empty-state, .loading-state {
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

/* Dialog Styling */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
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
    .main-card {
        margin-bottom: 16px;
    }
}
</style>