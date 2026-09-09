<script setup lang="ts">
import { ref, computed, onMounted, reactive, defineAsyncComponent } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store (optional)
import type { Apartment } from '@/types/Apartment'; // Adjust path if needed
import { useToast } from 'vue-toastification';

// --- Async Component Imports ---
const AuthorityAddApartmentFile = defineAsyncComponent(
    () => import('@/components/ApartmentFile/Authority/Add.vue')
); // Adjust path
const AuthorityEditApartmentFile = defineAsyncComponent(
    () => import('@/components/ApartmentFile/Authority/Edit.vue')
); // Adjust path
const AuthorityViewApartmentFile = defineAsyncComponent(
    () => import('@/components/ApartmentFile/Authority/View.vue')
); // Adjust path

// --- Store, Router & Permissions ---
const authStore = useAuthStore(); // Instantiate store
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
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  desktopWindow: false
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);

// --- Component State ---
const apartments = ref<Apartment[]>([]);
const search = ref('');
const loadingApartments = ref(false);
const deletingApartment = ref(false);

// --- Dialog States & Data ---
const addApartmentDialog = ref(false);
const editApartmentDialog = ref(false);
const viewApartmentDialog = ref(false);
const deleteApartmentDialog = ref(false);
const editedApartment = ref<Apartment | null>(null); // For Edit component
const selectedApartment = ref<Apartment | null>(null); // For View component
const apartmentToDelete = ref<Apartment | null>(null); // For Delete confirmation

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers ---
const apartmentHeaders = computed(() => [
    { title: 'Name', key: 'name', sortable: true },
    { title: 'Ort', key: 'location', sortable: true },
    { title: 'Straße', key: 'street', sortable: true },
    { title: 'Hausnr.', key: 'housenumber', sortable: true },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' },
]);

// --- Dynamic Component Selection (Simplified based on original code) ---
const addApartmentComponent = computed(() => AuthorityAddApartmentFile);
const editApartmentComponent = computed(() => AuthorityEditApartmentFile);
const viewApartmentComponent = computed(() => AuthorityViewApartmentFile);

// --- Data Fetching ---
const fetchApartments = async () => {
    loadingApartments.value = true;
    try {
        // Assuming GET is appropriate, change to post if needed
        const response = await apiClientAuth.get<{ data: Apartment[] }>(
            'apartmentfile?action=getApartments'
        ); // Adjust path
        apartments.value = response.data.data || response.data || [];
    } catch (error: any) {
        console.error('Error fetching apartments:', error);
        showSnackbar(error.response?.data?.error || t('apartmentView.loadError'), 'error');
        apartments.value = [];
    } finally {
        loadingApartments.value = false;
    }
};

// --- Computed Properties ---
const filteredApartments = computed(() => {
    const searchTermLower = search.value.trim().toLowerCase();
    if (!searchTermLower) {
        return apartments.value;
    }
    return apartments.value.filter(apartment =>
        `${apartment.name || ''} ${apartment.location || ''} ${apartment.street || ''} ${apartment.housenumber || ''}`
            .toLowerCase()
            .includes(searchTermLower)
    );
});

// --- Methods ---

// Dialog Openers/Closers and Event Handlers
const openNewApartmentDialog = () => (addApartmentDialog.value = true);
const closeAddApartmentDialog = () => (addApartmentDialog.value = false);
const onApartmentAdded = () => {
    closeAddApartmentDialog();
    showSnackbar(t('apartmentView.added'), 'success');
    fetchApartments(); // Refresh list
};

const openEditApartmentDialog = (apartment: Apartment) => {
    editedApartment.value = { ...apartment }; // Pass copy
    editApartmentDialog.value = true;
};
const closeEditApartmentDialog = () => {
    editApartmentDialog.value = false;
    editedApartment.value = null;
};
const onApartmentUpdated = () => {
    closeEditApartmentDialog();
    showSnackbar(t('apartmentView.updated'), 'success');
    fetchApartments(); // Refresh list
};

const openViewApartmentDialog = (apartment: Apartment) => {
    if (!apartment) {
        console.error('Invalid apartment data passed to view dialog');
        showSnackbar(t('apartmentView.viewError'), 'error');
        return;
    }
    selectedApartment.value = { ...apartment }; // Pass copy
    viewApartmentDialog.value = true;
};
const closeViewApartmentDialog = () => {
    viewApartmentDialog.value = false;
    selectedApartment.value = null;
};

// Delete Logic
const openDeleteApartmentDialog = (apartment: Apartment) => {
    apartmentToDelete.value = apartment;
    deleteApartmentDialog.value = true;
};
const closeDeleteApartmentDialog = () => {
    deleteApartmentDialog.value = false;
    apartmentToDelete.value = null;
};
const confirmDeleteApartment = async () => {
    if (!apartmentToDelete.value) return;
    deletingApartment.value = true;
    try {
        await apiClientAuth.post('/apartmentfile?action=deleteApartment', {
            id: apartmentToDelete.value.id,
        }); // Adjust path
        closeDeleteApartmentDialog();
        await fetchApartments(); // Refresh list
        showSnackbar(t('apartmentView.deleted'), 'success');
    } catch (error: any) {
        console.error('Error deleting apartment:', error);
        showSnackbar(error.response?.data?.error || t('apartmentView.deleteError'), 'error');
    } finally {
        deletingApartment.value = false;
    }
};

const isDetailView = computed(() => {
    return addApartmentDialog.value || editApartmentDialog.value || viewApartmentDialog.value;
});

// --- Zusätzliche Methode zum Schließen aller Detailansichten ---
const closeAllDetailViews = () => {
    addApartmentDialog.value = false;
    editApartmentDialog.value = false;
    viewApartmentDialog.value = false;
    editedApartment.value = null;
    selectedApartment.value = null;
};

// --- Lifecycle Hooks ---
onMounted(fetchApartments);
</script>

<template>
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <v-btn
                    v-if="
                        !addApartmentDialog &&
                        !editApartmentDialog &&
                        !viewApartmentDialog &&
                        canEdit
                    "
                    @click="openNewApartmentDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-home-plus-outline"
                    class="action-button"
                    elevation="2"
                >
                    {{ t('apartmentView.new') }}
                </v-btn>
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
                    {{ t('apartmentView.info') }}
                </v-alert>
            </v-col>
        </v-row>

        <!-- Haupttabelle - nur anzeigen, wenn keine Details/Dialoge geöffnet sind -->
        <v-card
            v-if="!addApartmentDialog && !editApartmentDialog && !viewApartmentDialog"
            class="main-card elevation-4"
        >
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-home-city</v-icon>
                    {{ t('apartmentView.title') }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-text-field
                    v-model="search"
                    :label="t('apartmentView.searchPlaceholder')"
                    prepend-inner-icon="mdi-magnify"
                    hide-details
                    density="compact"
                    variant="solo-filled"
                    flat
                    clearable
                    class="max-w-400 search-field"
                />
            </v-toolbar>

            <v-divider></v-divider>

            <v-data-table
                :headers="apartmentHeaders"
                :items="filteredApartments"
                class="elevation-0"
                :search="search"
                :items-per-page="25"
                item-value="id"
                :loading="loadingApartments"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span @click="openViewApartmentDialog(item)" class="apartment-link">
                        {{ item.name }}
                    </span>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-1">
                        <v-tooltip :text="t('edit')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditApartmentDialog(item)"
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
                                    @click="openDeleteApartmentDialog(item)"
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
                        <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-home-off</v-icon>
                        <span>{{ t('apartmentView.noData') }}</span>
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
                        <span>{{ t('apartmentView.loading') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Zurück-Button, wenn eine Detail-Ansicht geöffnet ist -->
        <div v-if="isDetailView" class="back-button-container mb-4">
            <v-btn
                color="primary"
                variant="tonal"
                prepend-icon="mdi-arrow-left"
                @click="closeAllDetailViews"
                size="small"
                class="back-button"
            >
                {{ t('apartmentView.back') }}
            </v-btn>
        </div>

        <!-- Add Apartment Dialog Component -->
        <component
            v-if="addApartmentDialog"
            :is="addApartmentComponent"
            v-model="addApartmentDialog"
            @apartment-added="onApartmentAdded"
            @close="closeAddApartmentDialog"
        />

        <!-- Edit Apartment Dialog Component -->
        <component
            v-if="editApartmentDialog"
            :is="editApartmentComponent"
            v-model="editApartmentDialog"
            :editApartmentDialog="editApartmentDialog"
            :apartment-to-edit="editedApartment"
            @apartment-updated="onApartmentUpdated"
            @close="closeEditApartmentDialog"
        />

        <!-- View Apartment Dialog Component -->
        <component
            v-if="viewApartmentDialog"
            :is="viewApartmentComponent"
            v-model="viewApartmentDialog"
            :viewApartmentDialog="viewApartmentDialog"
            :apartment-to-view="selectedApartment"
            @close="closeViewApartmentDialog"
        />

        <!-- Delete Confirmation Dialog -->
        <v-dialog
            v-model="deleteApartmentDialog"
            max-width="500"
            persistent
            class="confirmation-dialog"
        >
            <v-card>
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ t('apartmentView.deleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        {{
                            t('apartmentView.deleteConfirm', { name: apartmentToDelete?.name })
                        }}
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        {{ t('apartmentView.deleteWarning') }}
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteApartmentDialog" class="mr-2"
                        >{{ t('apartmentView.cancel') }}</v-btn
                    >
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="confirmDeleteApartment"
                        :loading="deletingApartment"
                        class="delete-button"
                    >
                        {{ t('apartmentView.delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Main Container Styles */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
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

/* Table Styles */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

.max-w-400 {
    max-width: 400px;
}

.search-field {
    transition: all 0.2s ease;
}

.search-field:focus-within {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.apartment-link {
    cursor: pointer;
    color: #1976d2;
    transition: all 0.2s ease;
}

.apartment-link:hover {
    text-decoration: underline;
    color: #2196f3;
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
.confirmation-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, #2563eb);
    color: white;
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

/* Back Button */
.back-button-container {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    padding: 12px 0;
    border-radius: 8px;
}

.back-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.back-button:hover {
    transform: translateX(-4px);
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .max-w-400 {
        max-width: 100%;
    }
}
</style>
