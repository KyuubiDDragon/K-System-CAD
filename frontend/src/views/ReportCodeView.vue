<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { ReportCode } from '@/types/Report'; // Adjust path if needed
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
const codes = ref<ReportCode[]>([]);
const loadingCodes = ref(false);
const savingCode = ref(false);
const deletingCode = ref(false);

// Computed property to ensure v-data-table always gets an array
const tableItems = computed(() => {
    return Array.isArray(codes.value) ? codes.value : [];
});

// --- Dialog States ---
const codeDialog = ref(false); // Combined Add/Edit dialog
const deleteCodeDialog = ref(false);

// --- Form State & Data ---
const codeFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isCodeFormValid = ref(false);
const initialFormData: Omit<ReportCode, 'id'> & { id?: number } = {
    code: '',
    description: '',
};
const codeFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!codeFormData.id);

// --- Data for Dialogs ---
const codeToDelete = ref<ReportCode | null>(null);

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
const codeHeaders = computed(() => [
    { title: t('reportCode.headers.code'), key: 'code', sortable: true },
    { title: t('reportCode.headers.description'), key: 'description', sortable: true },
    { title: t('reportCode.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);

// --- Validation Rules ---
const requiredRule = (value: string) => !!value || 'Dieses Feld ist erforderlich.';

// --- Data Fetching ---
const fetchCodes = async () => {
    loadingCodes.value = true;
    try {
        console.log('Fetching codes...');
        
        // Get response from API
        const response = await apiClientAuth.get('/report/?action=getCodes');
        console.log('Code response:', response);
        
        // Extract codes from the correct property in the response
        if (response.data && response.data.codes && Array.isArray(response.data.codes)) {
            // If response has codes property
            codes.value = response.data.codes.map(code => ({
                ...code,
                description: code.description || '',
            }));
            console.log('Found codes array:', codes.value);
        } else if (Array.isArray(response.data)) {
            // If response is directly an array
            codes.value = response.data.map(code => ({
                ...code,
                description: code.description || '',
            }));
            console.log('Direct array of codes:', codes.value);
        } else {
            console.warn('API did not return expected format:', response.data);
            codes.value = [];
        }
    } catch (error: any) {
        console.error('Error fetching codes:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Codes.', 'error');
        codes.value = []; // Clear data on error
    } finally {
        loadingCodes.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openNewCodeDialog = () => {
    Object.assign(codeFormData, { ...initialFormData, id: undefined }); // Reset form
    isCodeFormValid.value = false; // Reset validation state
    codeDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => codeFormRef.value?.resetValidation(), 100);
};

const openEditCodeDialog = (code: ReportCode) => {
    Object.assign(codeFormData, { ...code, description: code.description || '' }); // Load data
    isCodeFormValid.value = false; // Reset validation state
    codeDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => codeFormRef.value?.resetValidation(), 100);
};

const closeCodeDialog = () => {
    codeDialog.value = false;
};

const saveCode = async () => {
    if (!isCodeFormValid.value) return; // Rely on form's v-model validity

    savingCode.value = true;
    const action = isEditing.value ? 'editCode' : 'addCode';
    const payload = { ...codeFormData };

    try {
        await apiClientAuth.post(`/report/?action=${action}`, payload);
        await fetchCodes(); // Refresh list
        closeCodeDialog(); // Close dialog
        showSnackbar(
            `Code erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving code (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern des Codes.', 'error');
    } finally {
        savingCode.value = false;
    }
};

// Delete Dialog Logic
const openDeleteCodeDialog = (code: ReportCode) => {
    codeToDelete.value = code;
    deleteCodeDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteCodeDialog.value = false;
    codeToDelete.value = null;
};

const confirmDeleteCode = async () => {
    if (!codeToDelete.value) return;

    deletingCode.value = true;
    try {
        await apiClientAuth.post('/report/?action=deleteCode', { id: codeToDelete.value.id });
        await fetchCodes(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar('Code erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting code:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Codes.', 'error');
        // Keep dialog open on error? Closing for now.
        // closeDeleteDialog();
    } finally {
        deletingCode.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchCodes(); // Fetch data when component mounts
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
				<v-icon size="36" class="mr-2">mdi-barcode</v-icon>
                                {{ $t('reportCodeView.title') }}
			  </h1>
			  <p class="text-body-1 text-medium-emphasis">
                                {{ $t('reportCodeView.subtitle') }}
			  </p>
			</div>
  
			<v-btn
			  v-if="canCreate"
			  @click="openNewCodeDialog"
			  color="primary"
			  variant="elevated"
			  prepend-icon="mdi-plus"
			  class="action-button"
			>
                        {{ $t('newCode') }}
			</v-btn>
		  </div>
		</v-col>
	  </v-row>
  
	  <!-- Tabelle -->
	  <v-card class="main-card" elevation="4">
		<v-data-table
		  :headers="codeHeaders"
		  :items="tableItems"
		  :loading="loadingCodes"
		  item-value="id"
		  hover
		  density="comfortable"
		  class="data-table"
		>
		  <template v-slot:[`item.code`]="{ item }">
			<span class="item-title">{{ item.code }}</span>
		  </template>
  
		  <template v-slot:[`item.description`]="{ item }">
			<span class="item-description">{{ item.description || '-' }}</span>
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
					@click="openEditCodeDialog(item)"
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
					@click="openDeleteCodeDialog(item)"
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
			  <v-icon size="64" color="grey-darken-1" class="mb-3">mdi-barcode-off</v-icon>
                          <p class="text-h6 text-grey">{{ $t('reportCodeView.noData') }}</p>
			</div>
		  </template>
  
		  <template v-slot:loading>
			<div class="loading-state">
			  <v-progress-circular indeterminate color="primary" size="32" class="mr-3"></v-progress-circular>
                          <span>{{ $t('reportCodeView.loading') }}</span>
			</div>
		  </template>
		</v-data-table>
	  </v-card>
  
	  <!-- Add/Edit Dialog -->
	  <v-dialog v-model="codeDialog" max-width="700" class="dialog-container">
		<v-card class="dialog-card">
		  <v-card-title class="dialog-title">
			<v-icon color="primary" class="mr-2">
			  {{ isEditing ? 'mdi-barcode-scan' : 'mdi-barcode' }}
			</v-icon>
                        {{ isEditing ? $t('editCode') : $t('newCode') }}
		  </v-card-title>
  
		  <v-card-text class="pt-4">
			<v-form ref="codeFormRef" v-model="isCodeFormValid">
			  <div class="form-section mb-4">
				<div class="section-title">
				  <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
				  Code-Informationen
				</div>
				
				<v-text-field
				  label="Code"
				  v-model="codeFormData.code"
				  required
				  :rules="[requiredRule]"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-barcode-scan"
				  class="mb-3"
				></v-text-field>
				
                                <v-textarea
                                  :label="$t('descriptionOptional')"
                                  v-model="codeFormData.description"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-text-box-outline"
				  rows="3"
				  auto-grow
				></v-textarea>
			  </div>
			</v-form>
		  </v-card-text>
  
		  <v-divider></v-divider>
  
		  <v-card-actions class="pa-4">
			<v-spacer></v-spacer>
			<v-btn variant="text" @click="closeCodeDialog" class="mr-2">Abbrechen</v-btn>
			<v-btn
			  v-if="isEditing ? canEdit : canCreate"
			  color="primary"
			  variant="elevated"
			  @click="saveCode"
			  :disabled="!isCodeFormValid"
			  :loading="savingCode"
			  class="action-button"
			>
			  <v-icon start>mdi-content-save</v-icon>
			  Speichern
			</v-btn>
		  </v-card-actions>
		</v-card>
	  </v-dialog>
  
	  <!-- Delete Confirmation Dialog -->
	  <v-dialog v-model="deleteCodeDialog" max-width="500" class="delete-dialog">
		<v-card class="dialog-card">
		  <v-card-title class="text-h5 dialog-title">
			<v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
			Code löschen
		  </v-card-title>
		  
		  <v-card-text class="pt-4">
			<p>Möchten Sie den Code "{{ codeToDelete?.code }}" wirklich löschen?</p>
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
			  v-if="canDelete"
			  color="error"
			  variant="elevated"
			  @click="confirmDeleteCode"
			  :loading="deletingCode"
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
  
  .item-description {
	  color: #94a3b8;
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