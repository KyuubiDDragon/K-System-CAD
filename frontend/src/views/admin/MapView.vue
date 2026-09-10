<script setup lang="ts">
import { ref, onMounted, reactive, computed, unref } from 'vue';
import { useRoute } from 'vue-router'; // Import if permissions needed
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { Categories as MapCategory } from '@/types/Map'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useI18n } from 'vue-i18n';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
// --- Define Interfaces ---
interface IconInfo {
    name: string; // Filename without extension (e.g., 'icon1')
    filename: string; // Full filename (e.g., 'icon1.png')
}
// Interface for the form data, matching MapCategory but making id optional
interface CategoryFormData {
    id?: number | null;
    name: string;
    icon: string | null; // Icon name without extension
    marker_count?: number; // Optional, likely read-only
}

// --- Router & Permissions ---
const route = useRoute(); // If using route meta for permissions
const canEdit = computed(() => true); // Replace with route.meta check if needed
const canDelete = computed(() => true); // Replace with route.meta check if needed

// --- Component State ---
const categories = ref<MapCategory[]>([]);
const availableIcons = ref<IconInfo[]>([]);
const loadingCategories = ref(false);
const loadingIcons = ref(false);
const savingCategory = ref(false);
const deletingCategory = ref(false);
const { t } = useI18n();

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const confirmDeleteDialog = ref(false);
const iconPickerVisible = ref(false);
const itemFormRef = ref<any>(null);
const isItemFormValid = ref(false);
const initialFormData: CategoryFormData = { id: null, name: '', icon: null };
const selectedItem = reactive<CategoryFormData>({ ...initialFormData });
const formTitle = computed(() =>
    selectedItem.id ? t('mapView.editTitle') : t('mapView.addTitle')
);
const itemToDelete = ref<MapCategory | null>(null);

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const categoryHeaders = computed(() => [
    { title: t('adminMap.headers.id'), key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: t('adminMap.headers.name'), key: 'name', sortable: true },
    { title: t('adminMap.headers.elements'), key: 'marker_count', sortable: true }, // Assuming marker_count is available
    { title: t('adminMap.headers.icon'), key: 'icon', sortable: false },
    { title: t('adminMap.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
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
const fetchCategories = async () => {
    loadingCategories.value = true;
    try {
        const response = await apiClientAuth.get<MapCategory[]>('/admin/map?action=getCategories'); // Adjust path
        categories.value = response.data || response.data || [];
    } catch (error: any) {
        console.error('Error fetching categories:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Kategorien.', 'error');
        categories.value = [];
    } finally {
        loadingCategories.value = false;
    }
};

const fetchAvailableIcons = async () => {
    loadingIcons.value = true;
    try {
        const response = await apiClientAuth.get<IconInfo[]>('/admin/map?action=getAvailableIcons');
        if (Array.isArray(response.data)) {
            availableIcons.value = response.data.map(icon => ({
                name: icon.filename.includes('.')
                    ? icon.filename.substring(0, icon.filename.lastIndexOf('.'))
                    : icon.filename,
                filename: icon.filename,
            }));
        } else {
            console.error('Unexpected response format for icons:', response.data);
            availableIcons.value = [];
            showSnackbar('Icon-Daten haben ein unerwartetes Format.', 'warning');
        }
    } catch (error: any) {
        console.error('Error fetching available icons:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Icons.', 'error');
        availableIcons.value = [];
    } finally {
        loadingIcons.value = false;
    }
};

// --- Methods ---

// Dialog Openers
const openNewCategoryDialog = () => {
    Object.assign(selectedItem, { ...initialFormData }); // Reset form
    isItemFormValid.value = false;
    iconPickerVisible.value = false; // Close picker if open
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openEditCategoryDialog = (category: MapCategory) => {
    Object.assign(selectedItem, { ...category }); // Load data
    isItemFormValid.value = false;
    iconPickerVisible.value = false; // Close picker if open
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

// Dialog Closer
const closeAddEditDialog = () => {
    addEditDialog.value = false;
    iconPickerVisible.value = false; // Ensure picker is closed
};

// Save Item (Add/Edit)
const saveItem = async () => {
    if (!isItemFormValid.value) return;
    savingCategory.value = true;
    try {
        // API expects icon name without extension
        const payload = { ...selectedItem, icon: selectedItem.icon }; // Ensure icon is just the name
        await apiClientAuth.post('/admin/map?action=saveCategory', payload); // Adjust path
        closeAddEditDialog();
        await fetchCategories(); // Refresh list
        showSnackbar('Kategorie erfolgreich gespeichert.', 'success');
    } catch (error: any) {
        console.error('Error saving category:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Kategorie.',
            'error'
        );
    } finally {
        savingCategory.value = false;
    }
};

// Icon Picker Logic
const selectIcon = (filename: string) => {
    // Extract name without extension
    selectedItem.icon = filename.includes('.')
        ? filename.substring(0, filename.lastIndexOf('.'))
        : filename;
    iconPickerVisible.value = false; // Close picker after selection
};

// Delete Logic
const openConfirmDeleteDialog = (item: MapCategory) => {
    itemToDelete.value = item;
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    itemToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!itemToDelete.value) return;
    deletingCategory.value = true;
    try {
        await apiClientAuth.post('/admin/map?action=deleteCategory', { id: itemToDelete.value.id }); // Adjust path
        closeConfirmDeleteDialog();
        await fetchCategories(); // Refresh list
        showSnackbar('Kategorie erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting category:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Kategorie.', 'error');
    } finally {
        deletingCategory.value = false;
    }
};

// Utility for image error
const onIconError = (event: Event) => {
    const target = event.target as HTMLImageElement;
    // Optional: Replace with a placeholder or hide
    target.style.display = 'none';
    // Or set a placeholder source: target.src = '/path/to/placeholder.png';
    console.warn(`Failed to load icon: ${target.src}`);
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchCategories();
    fetchAvailableIcons();
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/MapView', () => unref(categoryHeaders) as any);
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon
                        icon="mdi-map-marker-category"
                        size="24"
                        class="mr-2 text-primary"
                    ></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">Karten-Kategorien</h1>
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
                    Hier können Sie Kategorien für Kartenmarkierungen verwalten und den passenden
                    Icon-Stil festlegen.
                </v-alert>
            </v-col>
        </v-row>

        <v-card class="main-card elevation-4">
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-shape-plus</v-icon>
                    Kategorien
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn
                    v-if="canEdit"
                    @click="openNewCategoryDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-plus"
                    size="small"
                    class="action-button"
                >
                    Neue Kategorie
                </v-btn>
            </v-toolbar>

            <v-divider></v-divider>

            <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
            <KTableToolbar :columns="kCols" :shown="(categories || []).length" />
            <v-data-table
                :headers="kCols.visible.value"
                :items="categories"
                item-value="id"
                :loading="loadingCategories"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.id`]="{ item }">
                    <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                        {{ item.id }}
                    </v-chip>
                </template>

                <template v-slot:[`item.name`]="{ item }">
                    <span class="font-weight-medium">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.marker_count`]="{ item }">
                    <v-chip
                        v-if="(item.marker_count ?? 0) > 0"
                        size="small"
                        label
                        color="success"
                        variant="tonal"
                    >
                        {{ item.marker_count }}
                    </v-chip>
                    <span v-else class="text-grey text-caption">Keine Marker</span>
                </template>

                <template v-slot:[`item.icon`]="{ item }">
                    <div class="d-flex align-center">
                        <v-avatar size="28" class="icon-avatar mr-2">
                            <img
                                v-if="item.icon"
                                :src="`/img/mapIcons/${item.icon}.png`"
                                :alt="item.icon"
                                @error="onIconError"
                            />
                            <v-icon v-else size="small" color="grey">mdi-image-off-outline</v-icon>
                        </v-avatar>
                        <span class="text-caption">{{ item.icon }}</span>
                    </div>
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
                                    @click="openEditCategoryDialog(item)"
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
                                    @click="openConfirmDeleteDialog(item)"
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
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-map-marker-off</v-icon
                        >
                        <span>Keine Kategorien gefunden.</span>
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
                        <span>{{ $t('loadingCategories') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="addEditDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon
                        :icon="selectedItem.id ? 'mdi-shape-edit' : 'mdi-shape-plus'"
                        class="mr-2"
                    ></v-icon>
                    {{ formTitle }}
                </v-card-title>

                <v-form ref="itemFormRef" v-model="isItemFormValid">
                    <v-card-text class="pa-4">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedItem.name"
                                        label="Kategoriename"
                                        required
                                        :rules="[requiredRule('Kategoriename')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-text-box-outline"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedItem.icon"
                                        label="Icon auswählen"
                                        readonly
                                        required
                                        :rules="[requiredRule('Icon auswählen')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        append-inner-icon="mdi-chevron-down"
                                        @click="iconPickerVisible = !iconPickerVisible"
                                        class="icon-selector"
                                    >
                                        <template v-slot:prepend-inner>
                                            <v-avatar size="28" class="icon-preview mr-2">
                                                <img
                                                    v-if="selectedItem.icon"
                                                    :src="`/img/mapIcons/${selectedItem.icon}.png`"
                                                    alt="Icon"
                                                    @error="onIconError"
                                                />
                                                <v-icon v-else size="small"
                                                    >mdi-image-off-outline</v-icon
                                                >
                                            </v-avatar>
                                        </template>
                                    </v-text-field>

                                    <v-expand-transition>
                                        <div
                                            v-show="iconPickerVisible"
                                            class="icon-picker mt-2 pa-3 rounded"
                                        >
                                            <div v-if="loadingIcons" class="text-center py-4">
                                                <v-progress-circular
                                                    indeterminate
                                                    color="primary"
                                                    size="32"
                                                ></v-progress-circular>
                                                <div class="mt-2">Lade Icons...</div>
                                            </div>

                                            <div
                                                v-else-if="availableIcons.length === 0"
                                                class="text-center text-grey py-4"
                                            >
                                                <v-icon size="40" color="grey-darken-1" class="mb-2"
                                                    >mdi-image-off</v-icon
                                                >
                                                <div>Keine Icons gefunden.</div>
                                            </div>

                                            <div v-else>
                                                <div
                                                    class="d-flex align-center justify-space-between mb-3"
                                                >
                                                    <div class="text-subtitle-2">
                                                        Verfügbare Icons
                                                    </div>
                                                    <v-btn
                                                        variant="text"
                                                        density="compact"
                                                        @click="iconPickerVisible = false"
                                                        prepend-icon="mdi-close"
                                                        size="small"
                                                    >
                                                        Schließen
                                                    </v-btn>
                                                </div>
                                                <v-row dense>
                                                    <v-col
                                                        v-for="icon in availableIcons"
                                                        :key="icon.filename"
                                                        cols="auto"
                                                        class="pa-1"
                                                    >
                                                        <v-tooltip :text="icon.name" location="top">
                                                            <template v-slot:activator="{ props }">
                                                                <v-btn
                                                                    icon
                                                                    :variant="
                                                                        selectedItem.icon ===
                                                                        icon.name
                                                                            ? 'flat'
                                                                            : 'tonal'
                                                                    "
                                                                    size="x-large"
                                                                    @click="
                                                                        selectIcon(icon.filename)
                                                                    "
                                                                    class="icon-btn"
                                                                    v-bind="props"
                                                                    :color="
                                                                        selectedItem.icon ===
                                                                        icon.name
                                                                            ? 'primary'
                                                                            : undefined
                                                                    "
                                                                    :elevation="
                                                                        selectedItem.icon ===
                                                                        icon.name
                                                                            ? 4
                                                                            : 1
                                                                    "
                                                                >
                                                                    <v-avatar size="32">
                                                                        <img
                                                                            :src="`/img/mapIcons/${icon.filename}`"
                                                                            :alt="icon.name"
                                                                            @error="onIconError"
                                                                        />
                                                                    </v-avatar>
                                                                </v-btn>
                                                            </template>
                                                        </v-tooltip>
                                                    </v-col>
                                                </v-row>
                                            </div>
                                        </div>
                                    </v-expand-transition>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog">{{ t('cancel') }}</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveItem"
                            :disabled="!isItemFormValid"
                            :loading="savingCategory"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-delete-alert" color="error" class="mr-2"></v-icon>
                    {{ t('mapView.deleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>{{ t('mapView.deleteConfirm', { name: itemToDelete?.name }) }}</p>

                    <v-alert
                        v-if="itemToDelete?.marker_count && itemToDelete.marker_count > 0"
                        class="mt-4"
                        border="start"
                        border-color="warning"
                        elevation="2"
                        density="compact"
                        icon="mdi-alert"
                        variant="tonal"
                        color="warning"
                    >
                        Diese Kategorie enthält {{ itemToDelete.marker_count }} Kartenmarkierung{{
                            itemToDelete.marker_count !== 1 ? 'en' : ''
                        }}, die nach dem Löschen keiner Kategorie mehr zugeordnet sind.
                    </v-alert>

                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2">
                        {{ t('cancel') }}
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingCategory"
                        class="delete-button"
                        prepend-icon="mdi-delete"
                    >
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Main Container */
.map-category-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
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
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Card Toolbar */
.card-toolbar {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--card-border);
}

/* Chips */
.id-chip {
    min-width: 36px;
    justify-content: center;
}

/* Icon Display */
.icon-avatar {
    background: var(--k-sunken);
    border: 1px solid var(--k-line);
}

.icon-preview {
    background: var(--k-sunken);
    border: 1px solid var(--k-line);
}

.icon-avatar img,
.icon-preview img,
.icon-btn img {
    object-fit: contain;
    width: 100%;
    height: 100%;
}

/* Icon Picker */
.icon-selector {
    cursor: pointer;
}

.icon-picker {
    max-height: 350px;
    overflow-y: auto;
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.icon-btn {
    margin: 2px;
    transition: all 0.2s ease;
    overflow: hidden;
    border: 1px solid var(--k-line);
}

.icon-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    border-color: var(--k-accent-line);
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

/* Styling for scrollbar in icon picker */
.icon-picker::-webkit-scrollbar {
    width: 8px;
}

.icon-picker::-webkit-scrollbar-track {
    background: var(--k-surface);
    border-radius: 4px;
}

.icon-picker::-webkit-scrollbar-thumb {
    background: var(--k-accent-line);
    border-radius: 4px;
}

.icon-picker::-webkit-scrollbar-thumb:hover {
    background: var(--k-accent-line);
}
</style>
