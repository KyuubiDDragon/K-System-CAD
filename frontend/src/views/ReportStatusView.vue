<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { ReportStatus } from '@/types/Report'; // Adjust path if needed
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
const statuses = ref<ReportStatus[]>([]);
const loadingStatuses = ref(false);
const savingStatus = ref(false);
const deletingStatus = ref(false);

// Computed property to ensure v-data-table always gets an array
const tableItems = computed(() => {
    return Array.isArray(statuses.value) ? statuses.value : [];
});

// --- Dialog States ---
const statusDialog = ref(false); // Combined Add/Edit dialog
const deleteStatusDialog = ref(false);

// --- Form State & Data ---
const statusFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isStatusFormValid = ref(false);
const initialFormData: Omit<ReportStatus, 'id' | 'is_deleted'> & { id?: number } = {
    name: '',
    sort_order: 0,
};
const statusFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!statusFormData.id);

// --- Data for Dialogs ---
const statusToDelete = ref<ReportStatus | null>(null);

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
const statusHeaders = computed(() => [
    { title: t('reportStatusView.table.name'), key: 'name', sortable: true },
    { title: t('reportStatusView.table.sortOrder'), key: 'sort_order', sortable: true },
    { title: t('reportStatusView.table.actions'), key: 'actions', sortable: false, align: 'end' },
] as const);

// --- Validation Rules ---
const requiredRule = (value: string) => !!value || t('reportStatusView.validation.required');

// --- Data Fetching ---
const fetchStatuses = async () => {
    loadingStatuses.value = true;
    try {
        console.log('Fetching statuses...');
        
        // Get response from API
        const response = await apiClientAuth.get('/report/?action=getStatuses');
        console.log('Status response:', response);
        
        // Extract statuses from the correct property in the response
        if (response.data && response.data.statuses && Array.isArray(response.data.statuses)) {
            // If response has statuses property
            statuses.value = response.data.statuses.map(status => ({
                ...status,
                sort_order: status.sort_order ?? 0, // Use nullish coalescing for default
            }));
            console.log('Found statuses array:', statuses.value);
        } else if (Array.isArray(response.data)) {
            // If response is directly an array
            statuses.value = response.data.map(status => ({
                ...status,
                sort_order: status.sort_order ?? 0, // Use nullish coalescing for default
            }));
            console.log('Direct array of statuses:', statuses.value);
        } else {
            console.warn('API did not return expected format:', response.data);
            statuses.value = [];
        }
    } catch (error: any) {
        console.error('Error fetching statuses:', error);
        showSnackbar(error.response?.data?.error || t('reportStatusView.errors.loadingStatuses'), 'error');
        statuses.value = []; // Clear data on error
    } finally {
        loadingStatuses.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog and Save Logic
const openNewStatusDialog = () => {
    Object.assign(statusFormData, { ...initialFormData, id: undefined }); // Reset form
    isStatusFormValid.value = false; // Reset validation state
    statusDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => statusFormRef.value?.resetValidation(), 100);
};

const openEditStatusDialog = (status: ReportStatus) => {
    Object.assign(statusFormData, { ...status, sort_order: status.sort_order ?? 0 }); // Load data
    isStatusFormValid.value = false; // Reset validation state
    statusDialog.value = true;
    // Reset validation state visually after dialog opens
    setTimeout(() => statusFormRef.value?.resetValidation(), 100);
};

const closeStatusDialog = () => {
    statusDialog.value = false;
};

const saveStatus = async () => {
    if (!isStatusFormValid.value) return; // Rely on form's v-model validity

    savingStatus.value = true;
    const action = isEditing.value ? 'editStatus' : 'addStatus';
    // Ensure sort_order is a number or 0
    const payload = {
        ...statusFormData,
        sort_order: Number.isNaN(Number(statusFormData.sort_order))
            ? 0
            : Number(statusFormData.sort_order),
    };

    try {
        await apiClientAuth.post(`/report/?action=${action}`, payload);
        await fetchStatuses(); // Refresh list
        closeStatusDialog(); // Close dialog
        showSnackbar(
            isEditing.value ? t('reportStatusView.messages.statusUpdated') : t('reportStatusView.messages.statusAdded'),
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving status (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || t('reportStatusView.errors.savingStatus'), 'error');
    } finally {
        savingStatus.value = false;
    }
};

// Delete Dialog Logic
const openDeleteStatusDialog = (status: ReportStatus) => {
    statusToDelete.value = status;
    deleteStatusDialog.value = true;
};

const closeDeleteDialog = () => {
    deleteStatusDialog.value = false;
    statusToDelete.value = null;
};

const confirmDeleteStatus = async () => {
    if (!statusToDelete.value) return;

    deletingStatus.value = true;
    try {
        await apiClientAuth.post('/report/?action=deleteStatus', { id: statusToDelete.value.id });
        await fetchStatuses(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar(t('reportStatusView.messages.statusDeleted'), 'success');
    } catch (error: any) {
        console.error('Error deleting status:', error);
        showSnackbar(error.response?.data?.error || t('reportStatusView.errors.deletingStatus'), 'error');
        // Keep dialog open on error? Closing for now.
        // closeDeleteDialog();
    } finally {
        deletingStatus.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchStatuses(); // Fetch data when component mounts
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
				<v-icon size="36" class="mr-2">mdi-tag-multiple-outline</v-icon>
                                {{ $t('reportStatusView.title') }}
			  </h1>
			  <p class="text-body-1 text-medium-emphasis">
                                {{ $t('reportStatusView.subtitle') }}
			  </p>
			</div>
  
			<v-btn
			  v-if="canEdit"
			  @click="openNewStatusDialog"
			  color="primary"
			  variant="elevated"
			  prepend-icon="mdi-plus"
			  class="action-button"
			>
                          {{ $t('reportStatusView.new') }}
			</v-btn>
		  </div>
		</v-col>
	  </v-row>
  
	  <!-- Tabelle -->
	  <v-card class="main-card" elevation="4">
		<v-data-table
		  :headers="statusHeaders"
		  :items="tableItems"
		  :loading="loadingStatuses"
		  item-value="id"
		  hover
		  density="comfortable"
		  class="data-table"
		>
		  <template v-slot:[`item.name`]="{ item }">
			<span class="item-title">{{ item.name }}</span>
		  </template>
  
		  <template v-slot:[`item.sort_order`]="{ item }">
			<span class="sort-order">{{ item.sort_order }}</span>
		  </template>
  
		  <template v-slot:[`item.actions`]="{ item }">
			<div class="action-buttons">
			  <v-tooltip :text="t('reportStatusView.actions.edit')" location="top">
				<template v-slot:activator="{ props }">
				  <v-btn
					v-if="canEdit"
					icon
					variant="text"
					size="small"
					@click="openEditStatusDialog(item)"
					v-bind="props"
					class="action-icon"
				  >
					<v-icon size="small">mdi-pencil</v-icon>
				  </v-btn>
				</template>
			  </v-tooltip>
			  
			  <v-tooltip :text="t('reportStatusView.actions.delete')" location="top">
				<template v-slot:activator="{ props }">
				  <v-btn
					v-if="canDelete"
					icon
					variant="text"
					size="small"
					@click="openDeleteStatusDialog(item)"
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
			  <v-icon size="64" color="grey-darken-1" class="mb-3">mdi-tag-off-outline</v-icon>
                          <p class="text-h6 text-grey">{{ $t('reportStatusView.noData') }}</p>
			</div>
		  </template>
  
		  <template v-slot:loading>
			<div class="loading-state">
			  <v-progress-circular indeterminate color="primary" size="32" class="mr-3"></v-progress-circular>
                          <span>{{ $t('reportStatusView.loading') }}</span>
			</div>
		  </template>
		</v-data-table>
	  </v-card>
  
	  <!-- Add/Edit Dialog -->
	  <v-dialog v-model="statusDialog" max-width="600" class="dialog-container">
		<v-card class="dialog-card">
		  <v-card-title class="dialog-title">
			<v-icon color="primary" class="mr-2">
			  {{ isEditing ? 'mdi-tag-edit-outline' : 'mdi-tag-plus-outline' }}
			</v-icon>
                        {{ isEditing ? $t('reportStatusView.editTitle') : $t('reportStatusView.addTitle') }}
		  </v-card-title>
  
		  <v-card-text class="pt-4">
			<v-form ref="statusFormRef" v-model="isStatusFormValid">
			  <div class="form-section mb-4">
				<div class="section-title">
				  <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
				  {{ t('reportStatusView.form.statusInfo') }}
				</div>
				
				<v-text-field
				  :label="t('reportStatusView.form.name')"
				  v-model="statusFormData.name"
				  required
				  :rules="[requiredRule]"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-tag"
				  class="mb-3"
				></v-text-field>
				
				<v-text-field
				  :label="t('reportStatusView.form.sortOrder')"
				  v-model.number="statusFormData.sort_order"
				  type="number"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  prepend-inner-icon="mdi-sort"
				></v-text-field>
			  </div>
			</v-form>
		  </v-card-text>
  
		  <v-divider></v-divider>
  
		  <v-card-actions class="pa-4">
			<v-spacer></v-spacer>
			<v-btn variant="text" @click="closeStatusDialog" class="mr-2">{{ t('reportStatusView.actions.cancel') }}</v-btn>
			<v-btn
			  color="primary"
			  variant="elevated"
			  @click="saveStatus"
			  :disabled="!isStatusFormValid"
			  :loading="savingStatus"
			  class="action-button"
			>
			  <v-icon start>mdi-content-save</v-icon>
			  {{ t('reportStatusView.actions.save') }}
			</v-btn>
		  </v-card-actions>
		</v-card>
	  </v-dialog>
  
	  <!-- Delete Confirmation Dialog -->
	  <v-dialog v-model="deleteStatusDialog" max-width="500" class="delete-dialog">
		<v-card class="dialog-card">
		  <v-card-title class="text-h5 dialog-title">
			<v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
			{{ t('reportStatusView.deleteDialog.title') }}
		  </v-card-title>
		  
		  <v-card-text class="pt-4">
			<p>{{ t('reportStatusView.deleteDialog.confirmText', { name: statusToDelete?.name }) }}</p>
			<div class="text-caption text-medium-emphasis mt-2">
			  {{ t('reportStatusView.deleteDialog.warningText') }}
			</div>
		  </v-card-text>
		  
		  <v-divider></v-divider>
		  
		  <v-card-actions class="pa-4">
			<v-spacer></v-spacer>
			<v-btn variant="text" @click="closeDeleteDialog" class="mr-2">
			  {{ t('reportStatusView.actions.cancel') }}
			</v-btn>
			<v-btn
			  color="error"
			  variant="elevated"
			  @click="confirmDeleteStatus"
			  :loading="deletingStatus"
			  class="delete-button"
			>
			  <v-icon start>mdi-delete</v-icon>
			  {{ t('reportStatusView.actions.delete') }}
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
  
  .sort-order {
	  background: rgba(59, 130, 246, 0.1);
	  padding: 2px 8px;
	  border-radius: 12px;
	  font-size: 0.8rem;
	  color: #93c5fd;
	  font-weight: 500;
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