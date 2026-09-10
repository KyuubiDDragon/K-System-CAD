<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { useRoute } from "vue-router";
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import type { Crew } from '@/types/Crew'; // Adjust path if needed
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
// --- Store, Router & Permissions ---
const authStore = useAuthStore();
const route = useRoute();

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
const crews = ref<Crew[]>([]);
const loadingCrews = ref(false);
const savingCrew = ref(false);
const deletingCrew = ref(false);
const togglingActivation = ref<number | null>(null); // Store ID of crew being toggled

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const deleteCrewDialog = ref(false);
const itemFormRef = ref<any>(null);
const isItemFormValid = ref(false);

const initialFormData: Omit<Crew, 'id' | 'active' | 'employees' | 'vehicles'> & { id?: number | null } = {
    name: '', status: '', sort_order: 0
};
const selectedCrew = reactive<Omit<Crew, 'active' | 'employees' | 'vehicles'> & { id?: number | null }>({ ...initialFormData });
const isEditing = computed(() => !!selectedCrew.id);
const crewToDelete = ref<Crew | null>(null);

// --- Snackbar ---
const toast = useToast();
const { t } = useI18n();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers ---
const headers = computed(() => [
    { title: 'Name', key: 'name', sortable: true },
    { title: 'Status', key: 'status', sortable: false },
    { title: 'Sortierung', key: 'sort_order', sortable: true, align: 'end' },
    { title: 'Aktiv', key: 'active', sortable: true, align: 'center' }, // Added active status column
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end', width: '180px' }
]);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || 'Feld ist erforderlich.';

// --- Data Fetching ---
const fetchCrews = async () => {
    loadingCrews.value = true;
    try {
        const response = await apiClientAuth.get<{ data: Crew[] }>('crew/?action=getCrews'); // Adjust path, assuming GET
         // Sort by sort_order, then name
         crews.value = (response.data.data || response.data || []).sort((a,b) => {
             const sortOrderDiff = (a.sort_order ?? 999) - (b.sort_order ?? 999);
             if (sortOrderDiff !== 0) return sortOrderDiff;
             return (a.name || '').localeCompare(b.name || '');
          });
    } catch (error: any) {
        console.error("Error fetching crews:", error);
        showSnackbar(error.response?.data?.error || t("crewView.errorLoad"), "error");
        crews.value = [];
    } finally {
        loadingCrews.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog Logic
const openNewCrewDialog = () => {
    Object.assign(selectedCrew, { ...initialFormData, id: null }); // Reset form
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openEditCrewDialog = (crew: Crew) => {
    Object.assign(selectedCrew, { ...crew }); // Load data
    isItemFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const closeAddEditDialog = () => {
    addEditDialog.value = false;
};

const saveCrew = async () => {
    if (!isItemFormValid.value) return;
    savingCrew.value = true;

    const action = isEditing.value ? 'editCrew' : 'addCrew';
    const payload = { ...selectedCrew };

    try {
        await apiClientAuth.post(`crew/?action=${action}`, payload); // Adjust path
        closeAddEditDialog();
        await fetchCrews(); // Refresh list
        showSnackbar(isEditing.value ? t('crewView.updateSuccess') : t('crewView.addSuccess'), 'success');
    } catch (error: any) {
        console.error(`Error saving crew (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || t("crewView.errorSave"), "error");
    } finally {
        savingCrew.value = false;
    }
};

// Delete Logic
const openDeleteCrewDialog = (crew: Crew) => {
    crewToDelete.value = crew;
    deleteCrewDialog.value = true;
};

const closeDeleteCrewDialog = () => {
    deleteCrewDialog.value = false;
    crewToDelete.value = null;
};

const confirmDeleteCrew = async () => {
    if (!crewToDelete.value) return;
    deletingCrew.value = true;
    try {
        await apiClientAuth.post("/crew/?action=deleteCrew", { id: crewToDelete.value.id }); // Adjust path
        closeDeleteCrewDialog();
        await fetchCrews(); // Refresh list
        showSnackbar(t("crewView.deleteSuccess"), "success");
    } catch (error: any) {
        console.error("Error deleting crew:", error);
        showSnackbar(error.response?.data?.error || t("crewView.errorDelete"), "error");
    } finally {
        deletingCrew.value = false;
    }
};

// Activation Toggle Logic
const toggleCrewActivation = async (crew: Crew) => {
    togglingActivation.value = crew.id; // Set loading indicator for this specific row
    try {
        await apiClientAuth.post("/crew/?action=toggleCrewActivation", { id: crew.id }); // Adjust path
        // Optimistic update or refetch
        // Find the crew in the local list and toggle its active status
        const index = crews.value.findIndex(c => c.id === crew.id);
        if (index !== -1) {
             crews.value[index].active = crews.value[index].active == 1 ? 0 : 1;
        }
         // Or refetch: await fetchCrews();
        showSnackbar(
          t('crewView.toggleSuccess', {
            name: crew.name,
            state: crew.active == 1 ? t('crewView.activated') : t('crewView.deactivated')
          }),
          'success'
        );
    } catch (error: any) {
        console.error("Error toggling crew activation:", error);
        showSnackbar(error.response?.data?.error || t("crewView.errorToggle"), "error");
         // Consider refetching on error to ensure correct state
         // await fetchCrews();
    } finally {
        togglingActivation.value = null; // Reset loading indicator
    }
};

// --- Lifecycle Hooks ---
onMounted(fetchCrews);

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('CrewView', () => unref(headers) as any);


/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(kFilters.filtered.value) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'einheiten' });
}

/**
 * Filter der Leiste. Schalter tragen eine feste Bedingung,
 * Facetten holen ihre Werte aus dem Bestand - nicht aus einer
 * gepflegten Liste, die am Tag ihrer Einfuehrung veraltet waere.
 */
const kFilters = useTableFilters(
    () => (unref(crews) as any[]) ?? [],
    [
        {
            key: 'active',
            label: t('crewView.filterActive'),
            on: true,
            test: (c: any) => Number(c.active) === 1,
        },
    ],
    [
        { field: 'status', label: t('crewView.status'), emptyLabel: t('crewView.withoutStatus') },
    ],
);

</script>

<template>
    <div class="crew-container">
      <v-container fluid class="pa-4">
        <!-- Header -->
        <div class="section-header mb-4">
          <div class="d-flex align-center">
            <v-icon icon="mdi-account-group" size="24" class="mr-2 text-primary"></v-icon>
            <h1 class="text-h5 font-weight-medium mb-0">{{ t("crewView.title") }}</h1>
          </div>
          <v-btn
            v-if="canEdit"
            @click="openNewCrewDialog"
            color="primary"
            variant="elevated"
            prepend-icon="mdi-account-group-outline"
            size="small"
            class="ml-auto"
          >
            {{ t("crewView.newCrew") }}
          </v-btn>
        </div>
        
        <!-- Datentabelle -->
        <v-card class="main-table-card" elevation="3">
          <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
          <KTableToolbar
                :filters="kFilters"
                :columns="kCols"
                :shown="kFilters.filtered.value.length"
                :total="(crews || []).length"
                :noun="t('crewView.noun')"
            />
          <v-data-table
            :headers="kCols.visible.value"
            :items="kFilters.filtered.value"
            item-value="id"
            :loading="loadingCrews"
            hover
            density="comfortable"
            class="crew-table"
              v-model="kSelected"
              show-select
          >
            <template v-slot:loader>
              <div class="d-flex align-center justify-center pa-4">
                <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
                <span class="ml-4">{{ t("crewView.loading") }}</span>
              </div>
            </template>
            
            <template v-slot:[`item.name`]="{ item }">
              <div class="crew-name">
                <v-icon icon="mdi-account-group" size="small" class="mr-2"></v-icon>
                {{ item.name }}
              </div>
            </template>
            
            <template v-slot:[`item.status`]="{ item }">
              <span v-if="item.status">{{ item.status }}</span>
              <span v-else class="text-grey">Kein Status</span>
            </template>
            
            <template v-slot:[`item.active`]="{ item }">
              <v-chip 
                :color="item.active == 1 ? 'success' : 'error'" 
                size="small" 
                label 
                variant="outlined"
                :prepend-icon="item.active == 1 ? 'mdi-check-circle' : 'mdi-close-circle'"
              >
                {{ item.active == 1 ? t('crewView.active') : t('crewView.inactive') }}
              </v-chip>
            </template>
            
            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex justify-end">
                <v-tooltip :text="t('edit')" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn 
                      v-if="canEdit" 
                      icon 
                      variant="text" 
                      size="small" 
                      @click="openEditCrewDialog(item)" 
                      v-bind="props"
                      color="primary"
                      class="action-btn"
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
                      @click="openDeleteCrewDialog(item)" 
                      v-bind="props" 
                      color="error"
                      class="action-btn"
                    >
                      <v-icon size="small">mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
                
                <v-tooltip :text="item.active == 1 ? t('crewView.deactivate') : t('crewView.activate')" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn 
                      icon 
                      variant="text" 
                      size="small" 
                      @click="toggleCrewActivation(item)" 
                      v-bind="props" 
                      :loading="togglingActivation === item.id"
                      :color="item.active == 1 ? 'success' : 'grey'"
                      class="action-btn"
                    >
                      <v-icon size="small">
                        {{ item.active == 1 ? 'mdi-power-plug' : 'mdi-power-plug-off' }}
                      </v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </div>
            </template>
            
            <template v-slot:no-data>
              <div class="empty-state pa-6">
                <v-icon icon="mdi-account-group-off" size="48" color="grey-darken-1" class="mb-4"></v-icon>
                <span>{{ t("crewView.noData") }}</span>
                <v-btn
                  v-if="canEdit"
                  color="primary"
                  variant="tonal"
                  class="mt-4"
                  prepend-icon="mdi-account-group-outline"
                  @click="openNewCrewDialog"
                >
                  {{ t("crewView.addCrew") }}
                </v-btn>
              </div>
            </template>
          </v-data-table>
          <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
          <KBulkBar
              :count="kSelected.length"
              :shown="kFilters.filtered.value.length"
              :total="(crews || []).length"
              @clear="kSelected = []"
          >
              <template #actions>
                  <v-btn variant="outlined" size="small" @click="kExportSelection">
                      {{ t('kTable.exportSelection') }}
                  </v-btn>
              </template>
          </KBulkBar>
        </v-card>
        
        <!-- Besatzung hinzufügen/bearbeiten Dialog -->
        <v-dialog v-model="addEditDialog" max-width="600px" persistent>
          <v-card class="dialog-card">
            <v-card-title class="dialog-title">
              <v-icon :icon="isEditing ? 'mdi-account-edit' : 'mdi-account-plus'" class="mr-2"></v-icon>
              {{ isEditing ? t('crewView.editTitle') : t('crewView.addTitle') }}
            </v-card-title>
            <v-form ref="itemFormRef" v-model="isItemFormValid">
              <v-card-text class="pa-4">
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      v-model="selectedCrew.name"
                      label="Name"
                      required
                      :rules="[requiredRule]"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      bg-color="grey-darken-3"
                      prepend-inner-icon="mdi-account-group"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model="selectedCrew.status"
                      label="Status (optional)"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      bg-color="grey-darken-3"
                      prepend-inner-icon="mdi-information-outline"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model.number="selectedCrew.sort_order"
                      label="Sortierung"
                      type="number"
                      required
                      :rules="[requiredRule]"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      bg-color="grey-darken-3"
                      prepend-inner-icon="mdi-sort"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-card-text>
              <v-divider></v-divider>
              <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn variant="text" @click="closeAddEditDialog">{{ t('cancel') }}</v-btn>
                <v-btn
                  v-if="isEditing ? canEdit : canCreate"
                  color="primary"
                  variant="elevated"
                  @click="saveCrew"
                  :disabled="!isItemFormValid"
                  :loading="savingCrew"
                >
                  {{ t('save') }}
                </v-btn>
              </v-card-actions>
            </v-form>
          </v-card>
        </v-dialog>
        
        <!-- {{ t("crewView.deleteTitle") }} Dialog -->
        <v-dialog v-model="deleteCrewDialog" max-width="500" persistent>
          <v-card class="dialog-card">
            <v-card-title class="dialog-title">
              <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
              {{ t('crewView.deleteTitle') }}
            </v-card-title>
            <v-card-text class="pa-4">
              <p class="text-body-1 mb-2">{{ t("crewView.confirmDelete") }}</p>
              <p class="text-body-2 font-weight-bold">{{ crewToDelete?.name }}</p>
              <p class="text-caption text-grey-darken-1 mt-4">
                {{ t("crewView.deleteWarning") }}
              </p>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="pa-4">
              <v-spacer></v-spacer>
              <v-btn variant="text" @click="closeDeleteCrewDialog">{{ t('cancel') }}</v-btn>
              <v-btn 
                color="error" 
                variant="elevated" 
                @click="confirmDeleteCrew" 
                :loading="deletingCrew"
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
  .crew-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image: 
      radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
      radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
  }
  
  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--k-line);
  }
  
  .main-table-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
  }
  
  .crew-table {
    width: 100%;
  }
  
  .crew-name {
    font-weight: 500;
    display: flex;
    align-items: center;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    color: var(--k-ink-muted);
    text-align: center;
  }
  
  .action-btn {
    margin: 0 2px;
    transition: transform 0.2s ease;
  }
  
  .action-btn:hover {
    transform: translateY(-2px);
  }
  
  /* Dialog styling */
  .dialog-card {
    background-color: var(--k-ink) !important;
    border: 1px solid var(--k-line);
  }
  
  .dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
  }
  
  /* Animation effects */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  /* Table row animations */
  .v-data-table-rows tr {
    animation: fadeIn 0.3s ease-out forwards;
  }
  </style>