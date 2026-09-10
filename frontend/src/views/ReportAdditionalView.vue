<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { ReportAdditional } from '@/types/Report'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar

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
const additionals = ref<ReportAdditional[]>([]);
const loadingAdditionals = ref(false);
const savingAdditional = ref(false);
const deletingAdditional = ref(false);

// Computed property to ensure v-data-table always gets an array
const tableItems = computed(() => {
    return Array.isArray(additionals.value) ? additionals.value : [];
});

// --- Dialog States ---
const additionalDialog = ref(false); // Combined Add/Edit dialog
const deleteAdditionalDialog = ref(false);

// --- Form State & Data ---
const additionalFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isAdditionalFormValid = ref(false);
const initialFormData: Omit<
    ReportAdditional,
    'id' | 'sort_order' | 'created_at' | 'changed_at' | 'is_deleted'
> & { id?: number } = {
    name: '',
    description: '',
    price: 0,
    units: 1,
};
const additionalFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!additionalFormData.id);

// --- Data for Dialogs ---
const additionalToDelete = ref<ReportAdditional | null>(null);

// --- Snackbar ---
const errorSnackbar = ref({
    visible: false,
    message: '',
    color: 'error', // Default color, can be overridden
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color; // Use provided color
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const additionalHeaders = computed(() => [
    { title: t('reportAdditional.headers.name'), key: 'name', sortable: true },
    { title: t('reportAdditional.headers.description'), key: 'description', sortable: false },
    { title: t('reportAdditional.headers.price'), key: 'price', sortable: true },
    { title: t('reportAdditional.headers.units'), key: 'units', sortable: true },
    { title: t('reportAdditional.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || 'Dieses Feld ist erforderlich.';
const positivePriceRule = (value: number) =>
    (value && value > 0) || 'Preis muss größer als 0 sein.';

// --- Data Fetching ---
const fetchAdditionals = async () => {
    loadingAdditionals.value = true;
    try {
        console.log('Fetching additionals...');
        
        // Get response from API
        const response = await apiClientAuth.get('/report/?action=getAdditionals');
        console.log('Additionals response:', response);
        
        // Extract additionals from the correct property in the response
        if (response.data && response.data.additionals && Array.isArray(response.data.additionals)) {
            // If response has additionals property
            additionals.value = response.data.additionals;
            console.log('Found additionals array:', additionals.value);
        } else if (Array.isArray(response.data)) {
            // If response is directly an array
            additionals.value = response.data;
            console.log('Direct array of additionals:', additionals.value);
        } else {
            console.warn('API did not return expected format:', response.data);
            additionals.value = [];
        }
    } catch (error: any) {
        console.error('Error fetching additionals:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Laden der Zusatzleistungen.',
            'error'
        );
        additionals.value = []; // Clear data on error
    } finally {
        loadingAdditionals.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openNewAdditionalDialog = () => {
    Object.assign(additionalFormData, { ...initialFormData, id: undefined }); // Reset form for adding
    isAdditionalFormValid.value = false; // Reset validation state
    additionalDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => additionalFormRef.value?.resetValidation(), 100);
};

const openEditAdditionalDialog = (additional: ReportAdditional) => {
    Object.assign(additionalFormData, { ...additional }); // Load data for editing
    isAdditionalFormValid.value = false; // Reset validation state
    additionalDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => additionalFormRef.value?.resetValidation(), 100);
};

const closeAdditionalDialog = () => {
    additionalDialog.value = false;
    // Reset form data might be good practice here if needed
    // Object.assign(additionalFormData, { ...initialFormData, id: undefined });
};

const saveAdditional = async () => {
    // Optional: Trigger validation programmatically
    // const { valid } = await additionalFormRef.value?.validate();
    // if (!valid) return;
    if (!isAdditionalFormValid.value) return; // Rely on form's v-model validity

    savingAdditional.value = true;
    const action = isEditing.value ? 'editAdditional' : 'addAdditional';
    const payload = { ...additionalFormData };
	// Ensure units is a valid number with a default of 1 for empty/invalid values
	payload.units = Number.isNaN(Number(payload.units)) ? 1 : Number(payload.units);

    try {
        await apiClientAuth.post(`/report/?action=${action}`, payload);
        await fetchAdditionals(); // Refresh list
        closeAdditionalDialog(); // Close dialog
        showSnackbar(
            `Zusatzleistung erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving additional (Action: ${action}):`, error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Zusatzleistung.',
            'error'
        );
    } finally {
        savingAdditional.value = false;
    }
};

// Delete Dialog Logic
const openDeleteAdditionalDialog = (additional: ReportAdditional) => {
    additionalToDelete.value = additional;
    deleteAdditionalDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteAdditionalDialog.value = false;
    additionalToDelete.value = null;
};

const confirmDeleteAdditional = async () => {
    if (!additionalToDelete.value) return;

    deletingAdditional.value = true;
    try {
        await apiClientAuth.post('/report/?action=deleteAdditional', {
            id: additionalToDelete.value.id,
        });
        await fetchAdditionals(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar('Zusatzleistung erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting additional:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Löschen der Zusatzleistung.',
            'error'
        );
        // Keep dialog open on error? Closing for now.
        // closeDeleteDialog();
    } finally {
        deletingAdditional.value = false;
    }
};

// --- Utility Functions ---
const formatCurrency = (value: number | null | undefined) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'USD' }).format(value); // Adjust currency as needed
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchAdditionals(); // Fetch data when component mounts
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
				<v-icon size="36" class="mr-2">mdi-package-variant-plus</v-icon>
                                {{ $t('reportAdditionalView.title') }}
			  </h1>
			  <p class="text-body-1 text-medium-emphasis">
                                {{ $t('reportAdditionalView.subtitle') }}
			  </p>
			</div>
  
			<v-btn
			  v-if="canEdit"
			  @click="openNewAdditionalDialog"
			  color="primary"
			  variant="elevated"
			  prepend-icon="mdi-plus"
			  class="action-button"
			>
                          {{ $t('reportAdditionalView.new') }}
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
		  :headers="additionalHeaders"
		  :items="tableItems"
		  :loading="loadingAdditionals"
		  item-value="id"
		  hover
		  density="comfortable"
		  class="data-table"
		>
		  <template v-slot:[`item.name`]="{ item }">
			<span class="item-title">{{ item.name }}</span>
		  </template>
  
		  <template v-slot:[`item.description`]="{ item }">
			<span class="item-description">{{ item.description || '-' }}</span>
		  </template>
  
		  <template v-slot:[`item.price`]="{ item }">
			<span class="price-tag">{{ formatCurrency(item.price) }}</span>
		  </template>
  
		  <template v-slot:[`item.units`]="{ item }">
			<span class="units-badge">{{ item.units || 1 }}</span>
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
					@click="openEditAdditionalDialog(item)"
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
					@click="openDeleteAdditionalDialog(item)"
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
			  <v-icon size="64" color="grey-darken-1" class="mb-3">mdi-package-variant-closed-remove</v-icon>
                          <p class="text-h6 text-grey">{{ $t('reportAdditionalView.noData') }}</p>
			</div>
		  </template>
  
		  <template v-slot:loading>
			<div class="loading-state">
			  <v-progress-circular indeterminate color="primary" size="32" class="mr-3"></v-progress-circular>
                          <span>{{ $t('reportAdditionalView.loading') }}</span>
			</div>
		  </template>
		</v-data-table>
	  </v-card>
  
	  <!-- Add/Edit Dialog -->
	  <v-dialog v-model="additionalDialog" max-width="700" class="dialog-container">
		<v-card class="dialog-card">
		  <v-card-title class="dialog-title">
			<v-icon color="primary" class="mr-2">
			  {{ isEditing ? 'mdi-package-variant-edit' : 'mdi-package-variant-plus' }}
			</v-icon>
                        {{ isEditing ? $t('reportAdditionalView.editTitle') : $t('reportAdditionalView.addTitle') }}
		  </v-card-title>
  
		  <v-card-text class="pt-4">
			<v-form ref="additionalFormRef" v-model="isAdditionalFormValid">
			  <div class="form-section mb-4">
				<div class="section-title">
				  <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
				  Grunddaten
				</div>
				
				<v-text-field
				  label="Name"
				  v-model="additionalFormData.name"
				  required
				  :rules="[requiredRule]"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-package-variant"
				  class="mb-3"
				></v-text-field>
				
				<v-textarea
				  label="Beschreibung"
				  v-model="additionalFormData.description"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-text-box-outline"
				  rows="3"
				  class="mb-3"
				></v-textarea>
			  </div>
  
			  <div class="form-section">
				<div class="section-title">
				  <v-icon size="small" class="mr-1">mdi-currency-usd</v-icon>
				  Preis und Einheiten
				</div>
				
				<v-row>
				  <v-col cols="12" md="6">
					<v-text-field
					  label="Preis"
					  v-model.number="additionalFormData.price"
					  required
					  type="number"
					  prefix="$"
					  :rules="[requiredRule, positivePriceRule]"
					  variant="outlined"
					  density="comfortable"
					  color="primary"
					  prepend-inner-icon="mdi-cash"
					></v-text-field>
				  </v-col>
				  <v-col cols="12" md="6">
					<v-text-field
					  label="Einheiten (optional)"
					  v-model.number="additionalFormData.units"
					  type="number"
					  variant="outlined"
					  density="comfortable"
					  color="primary"
					  prepend-inner-icon="mdi-counter"
					></v-text-field>
				  </v-col>
				</v-row>
			  </div>
			</v-form>
		  </v-card-text>
  
		  <v-divider></v-divider>
  
		  <v-card-actions class="pa-4">
			<v-spacer></v-spacer>
			<v-btn variant="text" @click="closeAdditionalDialog" class="mr-2">Abbrechen</v-btn>
			<v-btn
			  color="primary"
			  variant="elevated"
			  @click="saveAdditional"
			  :disabled="!isAdditionalFormValid"
			  :loading="savingAdditional"
			  class="action-button"
			>
			  <v-icon start>mdi-content-save</v-icon>
			  Speichern
			</v-btn>
		  </v-card-actions>
		</v-card>
	  </v-dialog>
  
	  <!-- Delete Confirmation Dialog -->
	  <v-dialog v-model="deleteAdditionalDialog" max-width="500" class="delete-dialog">
		<v-card class="dialog-card">
		  <v-card-title class="text-h5 dialog-title">
			<v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
			Zusatzleistung löschen
		  </v-card-title>
		  
		  <v-card-text class="pt-4">
			<p>Möchten Sie die Zusatzleistung "{{ additionalToDelete?.name }}" wirklich löschen?</p>
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
			  @click="confirmDeleteAdditional"
			  :loading="deletingAdditional"
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
  
  .item-description {
	  color: var(--k-ink-faint);
	  display: -webkit-box;
	  -webkit-line-clamp: 2;
	  -webkit-box-orient: vertical;
	  overflow: hidden;
	  text-overflow: ellipsis;
	  max-width: 300px;
  }
  
  .price-tag {
	  background: rgba(20, 184, 166, 0.1);
	  color: #2dd4bf;
	  font-weight: 500;
	  padding: 2px 8px;
	  border-radius: 4px;
	  border: 1px solid rgba(20, 184, 166, 0.2);
  }
  
  .units-badge {
	  background: rgba(59, 130, 246, 0.1);
	  color: #60a5fa;
	  font-weight: 500;
	  padding: 2px 8px;
	  border-radius: 4px;
	  border: 1px solid rgba(59, 130, 246, 0.2);
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