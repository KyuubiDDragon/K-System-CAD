<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { Vehicle } from '@/types/Vehicle'; // Adjust path if needed
import { useToast } from 'vue-toastification'; // Import Toastification
import { useI18n } from 'vue-i18n';

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Check permissions from both route.meta and props
const route = useRoute();
const { t } = useI18n();
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete); // Not used, but kept

const requiredRule = (value: any) => !!value || t('validation.fieldRequired');

// --- Component State ---
const vehicles = ref<Vehicle[]>([]);
const loadingVehicles = ref(false);
const savingVehicle = ref(false);
const updatingStatus = ref<number | null>(null); // Store ID of vehicle being activated/deactivated
const updatingDamage = ref<number | null>(null); // Store ID of vehicle damage being updated

// Detail view mode for navigation from search
const detailViewMode = ref(false);
const vehicleDetailData = ref<ExtendedVehicle | null>(null)

// --- Dialog States ---
const addEditVehicleDialog = ref(false);
const viewVehicleDialog = ref(false);
const deactivateDialog = ref(false);
const activateDialog = ref(false);
const reportDamageDialog = ref(false);
const removeDamageDialog = ref(false);

// --- Form State & Data ---
const vehicleFormRef = ref<any>(null);
const reportDamageFormRef = ref<any>(null);
const isVehicleFormValid = ref(false);
const isReportDamageFormValid = ref(false);

// Extend Vehicle type with missing properties
interface ExtendedVehicle extends Vehicle {
  damage_description?: string | null;
}

const initialFormData: Omit<
	ExtendedVehicle,
	'id' | 'active' | 'damage' | 'damage_officer' | 'damage_description' | 'damage_time' | 'created_at' | 'updated_at'
> & { id?: number | null } = {
	title: '',
	numberplate: '',
	rank: '',
	sort_order: 0,
};
const vehicleFormData = reactive({ ...initialFormData });
const isEditing = computed(() => !!vehicleFormData.id);

const vehicleToModify = ref<ExtendedVehicle | null>(null); // Vehicle for activate/deactivate/damage dialogs
const vehicleToView = ref<ExtendedVehicle | null>(null); // Vehicle for view dialog
const damageOfficer = ref('');
const damageDescription = ref('');

// --- Toastification ---
const toast = useToast();

// --- Table Headers ---
const headers = computed(() => [
    { title: t('vehicle.headers.sort'), key: 'sort_order', sortable: true, width: '80px' },
    { title: t('vehicle.headers.title'), key: 'title', sortable: true },
    { title: t('vehicle.headers.numberplate'), key: 'numberplate', sortable: true },
    { title: t('vehicle.headers.rank'), key: 'rank', sortable: true },
    { title: t('vehicle.headers.damageOfficer'), key: 'damage_officer', sortable: false }, // Damage Officer
    { title: t('vehicle.headers.damageDescription'), key: 'damage_description', sortable: false }, // Damage Description
    { title: t('vehicle.headers.damageTime'), key: 'damage_time', sortable: true }, // Damage Time
    { title: t('vehicle.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '180px' },
]);

const fetchVehicles = async () => {
    loadingVehicles.value = true;
    try {
        const response = await apiClientAuth.get<any>('/vehicle/?action=getVehicles');
        
        // Debug-Information zur API-Antwort
        console.log('Vehicle API response:', response.data);
        
        // Ermittle die tatsächliche Datenquelle (ob response.data oder response.data.data)
        let vehicleData;
        if (Array.isArray(response.data)) {
            // API gibt direkt ein Array zurück
            vehicleData = response.data;
        } else if (response.data && Array.isArray(response.data.data)) {
            // API gibt { data: [...] } zurück
            vehicleData = response.data.data;
        } else {
            // Unerwartetes Format
            console.warn('Unexpected vehicle data format:', response.data);
            vehicleData = [];
        }
        
        // Stelle sicher, dass vehicleData ein Array ist, bevor sortiert wird
        if (Array.isArray(vehicleData)) {
            // Sortiere nach sort_order, dann title
            vehicles.value = vehicleData.sort((a, b) => {
                // Prüfe, ob die Eigenschaften existieren
                if (a.sort_order !== undefined && b.sort_order !== undefined) {
                    const sortOrderDiff = (a.sort_order ?? 999) - (b.sort_order ?? 999);
                    if (sortOrderDiff !== 0) return sortOrderDiff;
                }
                // Fallback auf title, falls vorhanden
                return (a.title || '').localeCompare(b.title || '');
            });
        } else {
            vehicles.value = [];
        }
        
        console.log('Processed vehicles:', vehicles.value);
    } catch (error: any) {
        console.error('Error fetching vehicles:', error);
        toast.error(error.response?.data?.error || 'Fehler beim Laden der Fahrzeuge.');
        vehicles.value = [];
    } finally {
        loadingVehicles.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog Logic
const openNewVehicleDialog = () => {
    Object.assign(vehicleFormData, { ...initialFormData, id: null }); // Reset form
    isVehicleFormValid.value = false;
    addEditVehicleDialog.value = true;
    setTimeout(() => vehicleFormRef.value?.resetValidation(), 100);
};

const openEditVehicleDialog = (vehicle: Vehicle) => {
    Object.assign(vehicleFormData, { ...vehicle }); // Load data
    isVehicleFormValid.value = false;
    addEditVehicleDialog.value = true;
    setTimeout(() => vehicleFormRef.value?.resetValidation(), 100);
};

const openViewVehicleDialog = (vehicle: Vehicle) => {
    // If we're in the context of search navigation, don't open dialog
    if (route.query.id || props.id || props.meta?.id) {
        return;
    }
    vehicleToView.value = { ...vehicle } as ExtendedVehicle; // Load data for viewing
    viewVehicleDialog.value = true;
};

const showVehicleDetails = (vehicle: Vehicle) => {
    vehicleDetailData.value = { ...vehicle } as ExtendedVehicle;
    detailViewMode.value = true;
};

const backToList = () => {
    detailViewMode.value = false;
    vehicleDetailData.value = null;
    // Clear query params if they exist
    if (route.query.id) {
        const router = useRouter();
        router.push({ name: route.name, query: {} });
    }
};

const closeAddEditVehicleDialog = () => {
    addEditVehicleDialog.value = false;
};

const closeViewVehicleDialog = () => {
    viewVehicleDialog.value = false;
    vehicleToView.value = null;
};

const saveVehicle = async () => {
    if (!isVehicleFormValid.value) return;
    savingVehicle.value = true;
    const action = isEditing.value ? 'editVehicle' : 'addVehicle';
    const payload = { ...vehicleFormData };
    try {
        await apiClientAuth.post(`/vehicle/?action=${action}`, payload);
        await fetchVehicles(); // Refresh list
        closeAddEditVehicleDialog();
        toast.success(
            `Fahrzeug erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`
        );
    } catch (error: any) {
        console.error(`Error saving vehicle (Action: ${action}):`, error);
        toast.error(
            error.response?.data?.error || 'Fehler beim Speichern des Fahrzeugs.'
        );
    } finally {
        savingVehicle.value = false;
    }
};

// Activate/Deactivate Logic
const openDeactivateDialog = (vehicle: Vehicle) => {
    vehicleToModify.value = vehicle;
    deactivateDialog.value = true;
};

const openActivateDialog = (vehicle: Vehicle) => {
    vehicleToModify.value = vehicle;
    activateDialog.value = true;
};

const closeConfirmDialogs = () => {
    deactivateDialog.value = false;
    activateDialog.value = false;
    reportDamageDialog.value = false;
    removeDamageDialog.value = false;
    vehicleToModify.value = null;
    // Reset damage form
    damageOfficer.value = '';
    damageDescription.value = '';
};

const confirmDeactivateVehicle = async () => {
    if (!vehicleToModify.value) return;
    updatingStatus.value = vehicleToModify.value.id; // Set loading state for this item
    try {
        await apiClientAuth.post('/vehicle/?action=deactivateVehicle', {
            id: vehicleToModify.value.id,
        });
        await fetchVehicles(); // Refresh list
        closeConfirmDialogs();
        toast.success('Fahrzeug deaktiviert.');
    } catch (error: any) {
        console.error('Error deactivating vehicle:', error);
        toast.error(error.response?.data?.error || 'Fehler beim Deaktivieren.');
    } finally {
        updatingStatus.value = null;
    }
};

const confirmActivateVehicle = async () => {
    if (!vehicleToModify.value) return;
    updatingStatus.value = vehicleToModify.value.id;
    try {
        await apiClientAuth.post('/vehicle/?action=activateVehicle', {
            id: vehicleToModify.value.id,
        });
        await fetchVehicles();
        closeConfirmDialogs();
        toast.success('Fahrzeug aktiviert.');
    } catch (error: any) {
        console.error('Error activating vehicle:', error);
        toast.error(error.response?.data?.error || 'Fehler beim Aktivieren.');
    } finally {
        updatingStatus.value = null;
    }
};

// Damage Report/Remove Logic
const openReportDamageDialog = (vehicle: Vehicle) => {
    // Original check was inside the template, moved here for clarity
    if (Number(vehicle.damage) == 1) {
        toast.warning(
            'Schaden muss zuerst als behoben markiert werden, bevor ein neuer gemeldet werden kann.'
        );
        return;
    }
    vehicleToModify.value = vehicle;
    damageOfficer.value = ''; // Reset form
    damageDescription.value = '';
    isReportDamageFormValid.value = false;
    reportDamageDialog.value = true;
    setTimeout(() => reportDamageFormRef.value?.resetValidation(), 100);
};

const openRemoveDamageDialog = (vehicle: Vehicle) => {
    vehicleToModify.value = vehicle;
    removeDamageDialog.value = true;
};

const confirmReportDamage = async () => {
    if (!vehicleToModify.value || !isReportDamageFormValid.value) return;
    updatingDamage.value = vehicleToModify.value.id;
    try {
        await apiClientAuth.post('/vehicle/?action=reportDamage', {
            vehicle_id: vehicleToModify.value.id,
            damage_officer: damageOfficer.value,
            damage_description: damageDescription.value,
        });
        await fetchVehicles();
        closeConfirmDialogs();
        toast.success('Schaden erfolgreich gemeldet.');
    } catch (error: any) {
        console.error('Error reporting damage:', error);
        toast.error(error.response?.data?.error || 'Fehler beim Melden des Schadens.');
    } finally {
        updatingDamage.value = null;
    }
};

const confirmRemoveDamage = async () => {
    if (!vehicleToModify.value) return;
    updatingDamage.value = vehicleToModify.value.id;
    try {
        await apiClientAuth.post('/vehicle/?action=removeDamage', {
            vehicle_id: vehicleToModify.value.id,
        });
        await fetchVehicles();
        closeConfirmDialogs();
        toast.success('Schaden als behoben markiert.');
    } catch (error: any) {
        console.error('Error removing damage:', error);
        toast.error(error.response?.data?.error || 'Fehler beim Entfernen des Schadens.');
    } finally {
        updatingDamage.value = null;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string | null => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Ungültig';
        return date.toLocaleString('de-DE', {
            // Include time
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return 'Fehler Datum';
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchVehicles();
    
    // Check for ID from route query or props
    const vehicleId = route.query.id || props.id || props.meta?.id;
    if (vehicleId) {
        // Find the vehicle in the loaded data
        const vehicle = vehicles.value.find(v => v.id == vehicleId);
        if (vehicle) {
            // Show details inline instead of dialog
            showVehicleDetails(vehicle);
        } else {
            toast.warning(`Fahrzeug mit ID ${vehicleId} nicht gefunden`);
        }
    }
});
</script>

<template>
	<div class="vehicle-container">
	  <v-container fluid class="pa-4">
		<!-- Detail View Mode -->
		<template v-if="detailViewMode && vehicleDetailData">
		  <!-- Detail View Header -->
		  <div class="section-header mb-4">
			<div class="d-flex align-center">
			  <v-btn
				icon
				variant="text"
				@click="backToList"
				class="mr-2"
			  >
				<v-icon>mdi-arrow-left</v-icon>
			  </v-btn>
			  <v-icon icon="mdi-car-info" size="24" class="mr-2 text-primary"></v-icon>
			  <h1 class="text-h5 font-weight-medium mb-0">{{ vehicleDetailData.title }}</h1>
			</div>
		  </div>
		  
		  <!-- Detail View Content -->
		  <v-card class="detail-card" elevation="3">
			<v-card-text class="pa-6">
			  <v-row>
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Titel / Rufname" 
					:model-value="vehicleDetailData.title" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-car"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Nummernschild" 
					:model-value="vehicleDetailData.numberplate" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-card-account-details"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Rang" 
					:model-value="vehicleDetailData.rank || 'Nicht angegeben'" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-shield"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Sortierung" 
					:model-value="String(vehicleDetailData.sort_order || 0)" 
					variant="outlined" 
					density="comfortable"
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-sort"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-chip 
					:color="Number(vehicleDetailData.active) == 1 ? 'success' : 'grey'" 
					variant="tonal"
					size="large"
					:prepend-icon="Number(vehicleDetailData.active) == 1 ? 'mdi-check-circle' : 'mdi-close-circle'"
					class="mt-2"
				  >
					Status: {{ Number(vehicleDetailData.active) == 1 ? 'Aktiv' : 'Inaktiv' }}
				  </v-chip>
				</v-col>
				
				<v-col cols="12" md="6" v-if="Number(vehicleDetailData.damage) == 1">
				  <v-chip 
					color="error" 
					variant="tonal"
					size="large"
					prepend-icon="mdi-alert"
					class="mt-2"
				  >
					Fahrzeug beschädigt
				  </v-chip>
				</v-col>
				
				<v-col cols="12" v-if="Number(vehicleDetailData.damage) == 1 && vehicleDetailData.damage_description">
				  <v-card variant="outlined" class="damage-info">
					<v-card-title class="text-subtitle-2">
					  <v-icon icon="mdi-information" class="mr-2" color="warning"></v-icon>
					  Schadensinformationen
					</v-card-title>
					<v-card-text>
					  <p><strong>Melder:</strong> {{ vehicleDetailData.damage_officer || 'Unbekannt' }}</p>
					  <p><strong>Gemeldet am:</strong> {{ formatDate(vehicleDetailData.damage_time) }}</p>
					  <p class="mt-2"><strong>Beschreibung:</strong></p>
					  <p>{{ vehicleDetailData.damage_description }}</p>
					</v-card-text>
				  </v-card>
				</v-col>
			  </v-row>
			  
			  <!-- Action buttons in detail view -->
			  <v-row class="mt-4">
				<v-col cols="12">
				  <v-divider class="mb-4"></v-divider>
				  <div class="d-flex justify-end gap-2">
					<v-btn
					  v-if="canEdit"
					  color="primary"
					  variant="tonal"
					  prepend-icon="mdi-pencil"
					  @click="openEditVehicleDialog(vehicleDetailData)"
					>
					  Bearbeiten
					</v-btn>
					<v-btn
					  :color="Number(vehicleDetailData.active) == 1 ? 'warning' : 'success'"
					  variant="tonal"
					  :prepend-icon="Number(vehicleDetailData.active) == 1 ? 'mdi-car-off' : 'mdi-car-check'"
					  @click="Number(vehicleDetailData.active) == 1 ? openDeactivateDialog(vehicleDetailData) : openActivateDialog(vehicleDetailData)"
					  :loading="updatingStatus === vehicleDetailData.id"
					>
					  {{ Number(vehicleDetailData.active) == 1 ? 'Deaktivieren' : 'Aktivieren' }}
					</v-btn>
					<v-btn
					  :color="Number(vehicleDetailData.damage) == 1 ? 'success' : 'error'"
					  variant="tonal"
					  :prepend-icon="Number(vehicleDetailData.damage) == 1 ? 'mdi-wrench' : 'mdi-alert-octagon-outline'"
					  @click="Number(vehicleDetailData.damage) == 1 ? openRemoveDamageDialog(vehicleDetailData) : openReportDamageDialog(vehicleDetailData)"
					  :loading="updatingDamage === vehicleDetailData.id"
					>
					  {{ Number(vehicleDetailData.damage) == 1 ? 'Schaden beheben' : 'Schaden melden' }}
					</v-btn>
				  </div>
				</v-col>
			  </v-row>
			</v-card-text>
		  </v-card>
		</template>
		
		<!-- List View Mode -->
		<template v-else>
		  <!-- Header -->
		  <div class="section-header mb-4">
			<div class="d-flex align-center">
			  <v-icon icon="mdi-car-fleet" size="24" class="mr-2 text-primary"></v-icon>
			  <h1 class="text-h5 font-weight-medium mb-0">{{ t('vehicleView.title') }}</h1>
			</div>
			<v-btn
			  v-if="canEdit"
			  @click="openNewVehicleDialog"
			  color="primary"
			  variant="elevated"
			  prepend-icon="mdi-car-plus"
			  size="small"
			  class="ml-auto"
			>
			  {{ t('vehicleView.newVehicle') }}
			</v-btn>
		  </div>
		
		<!-- Datentabelle -->
		<v-card class="main-table-card" elevation="3">
		  <v-data-table
			:headers="headers"
			:items="vehicles"
			item-value="id"
			:loading="loadingVehicles"
			hover
			density="comfortable"
			class="vehicle-table"
		  >
			<template v-slot:loader>
			  <div class="d-flex align-center justify-center pa-4">
				<v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
				<span class="ml-4">Lade Fahrzeuge...</span>
			  </div>
			</template>
			
			<template v-slot:[`item.title`]="{ item }">
			  <span :class="{ 'damage-text': Number(item.damage) == 1 }">
				{{ item.title }}
				<v-tooltip v-if="Number(item.damage) == 1" text="Fahrzeug hat Schaden" location="top">
				  <template v-slot:activator="{ props }">
					<v-icon color="error" size="small" class="ml-1" v-bind="props">mdi-alert-circle</v-icon>
				  </template>
				</v-tooltip>
			  </span>
			</template>
			
			<template v-slot:[`item.damage_time`]="{ item }">
			  {{ item.damage_time ? formatDate(item.damage_time) : '-' }}
			</template>
			
			<template v-slot:[`item.damage`]="{ item }">
			  <v-chip 
				v-if="Number(item.damage) == 1" 
				size="small" 
				color="error" 
				variant="outlined"
				label
				prepend-icon="mdi-alert"
			  >
				Beschädigt
			  </v-chip>
			  <span v-else>-</span>
			</template>
			
			<template v-slot:[`item.active`]="{ item }">
			  <v-chip 
				size="small" 
				:color="Number(item.active) == 1 ? 'success' : 'grey'" 
				variant="outlined"
				label
				:prepend-icon="Number(item.active) == 1 ? 'mdi-check-circle' : 'mdi-close-circle'"
			  >
				{{ Number(item.active) == 1 ? 'Aktiv' : 'Inaktiv' }}
			  </v-chip>
			</template>
			
			<template v-slot:[`item.actions`]="{ item }">
			  <div class="d-flex justify-end">
				<v-tooltip text="Anzeigen" location="top">
				  <template v-slot:activator="{ props }">
					<v-btn 
					  icon 
					  variant="text" 
					  size="small" 
					  @click="openViewVehicleDialog(item)" 
					  v-bind="props"
					  color="info"
					  class="action-btn"
					>
					  <v-icon size="small">mdi-eye</v-icon>
					</v-btn>
				  </template>
				</v-tooltip>
				
				<v-tooltip text="Bearbeiten" location="top">
				  <template v-slot:activator="{ props }">
					<v-btn 
					  v-if="canEdit" 
					  icon 
					  variant="text" 
					  size="small" 
					  @click="openEditVehicleDialog(item)" 
					  v-bind="props"
					  color="primary"
					  class="action-btn"
					>
					  <v-icon size="small">mdi-pencil</v-icon>
					</v-btn>
				  </template>
				</v-tooltip>
				
				<v-tooltip :text="Number(item.active) == 1 ? 'Deaktivieren' : 'Aktivieren'" location="top">
				  <template v-slot:activator="{ props }">
					<v-btn 
					  icon 
					  variant="text" 
					  size="small" 
					  @click="Number(item.active) == 1 ? openDeactivateDialog(item) : openActivateDialog(item)" 
					  v-bind="props" 
					  :loading="updatingStatus === item.id"
					  :color="Number(item.active) == 1 ? 'success' : 'grey'"
					  class="action-btn"
					>
					  <v-icon size="small">{{ Number(item.active) == 1 ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
					</v-btn>
				  </template>
				</v-tooltip>
				
				<v-tooltip :text="Number(item.damage) == 1 ? 'Schaden beheben' : 'Schaden melden'" location="top">
				  <template v-slot:activator="{ props }">
					<v-btn 
					  icon 
					  variant="text" 
					  size="small" 
					  @click="Number(item.damage) == 1 ? openRemoveDamageDialog(item) : openReportDamageDialog(item)" 
					  v-bind="props" 
					  :loading="updatingDamage === item.id"
					  :color="Number(item.damage) == 1 ? 'warning' : 'grey'"
					  class="action-btn"
					>
					  <v-icon size="small">{{ Number(item.damage) == 1 ? 'mdi-wrench' : 'mdi-alert-octagon-outline' }}</v-icon>
					</v-btn>
				  </template>
				</v-tooltip>
			  </div>
			</template>
			
			<template v-slot:no-data>
			  <div class="empty-state pa-6">
				<v-icon icon="mdi-car-off" size="48" color="grey-darken-1" class="mb-4"></v-icon>
				<span>Keine Fahrzeuge gefunden.</span>
				<v-btn
				  v-if="canEdit"
				  color="primary"
				  variant="tonal"
				  class="mt-4"
				  prepend-icon="mdi-car-plus"
				  @click="openNewVehicleDialog"
				>
                                  {{ t('vehicleView.addVehicle') }}
				</v-btn>
			  </div>
			</template>
		  </v-data-table>
		</v-card>
		</template>
		
		<!-- Dialoge -->
		
		<!-- Fahrzeug hinzufügen/bearbeiten Dialog -->
		<v-dialog v-model="addEditVehicleDialog" max-width="600px" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon :icon="isEditing ? 'mdi-car-wrench' : 'mdi-car-plus'" class="mr-2"></v-icon>
			  {{ isEditing ? 'Fahrzeug bearbeiten' : 'Neues Fahrzeug' }}
			</v-card-title>
			<v-card-text class="pa-4">
			  <v-form ref="vehicleFormRef" v-model="isVehicleFormValid">
				<v-row>
				  <v-col cols="12">
					<v-text-field 
					  label="Titel / Rufname" 
					  v-model="vehicleFormData.title" 
					  required 
					  :rules="[requiredRule]" 
					  variant="outlined" 
					  density="comfortable" 
					  color="primary"
					  bg-color="grey-darken-3"
					  prepend-inner-icon="mdi-car"
					></v-text-field>
				  </v-col>
				  
				  <v-col cols="12" md="6">
					<v-text-field 
					  label="Nummernschild" 
					  v-model="vehicleFormData.numberplate" 
					  required 
					  :rules="[requiredRule]" 
					  variant="outlined" 
					  density="comfortable" 
					  color="primary"
					  bg-color="grey-darken-3"
					  prepend-inner-icon="mdi-card-account-details"
					></v-text-field>
				  </v-col>
				  
				  <v-col cols="12" md="6">
					<v-text-field 
					  label="Rang (optional)" 
					  v-model="vehicleFormData.rank" 
					  variant="outlined" 
					  density="comfortable" 
					  color="primary"
					  bg-color="grey-darken-3"
					  prepend-inner-icon="mdi-shield"
					></v-text-field>
				  </v-col>
				  
				  <v-col cols="12" md="6">
					<v-text-field 
					  type="number" 
					  label="Sortierung" 
					  v-model.number="vehicleFormData.sort_order" 
					  variant="outlined" 
					  density="comfortable"
					  color="primary"
					  bg-color="grey-darken-3"
					  prepend-inner-icon="mdi-sort"
					></v-text-field>
				  </v-col>
				</v-row>
			  </v-form>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeAddEditVehicleDialog">{{ t('cancel') }}</v-btn>
			  <v-btn
				color="primary"
				variant="elevated"
				@click="saveVehicle"
				:disabled="!isVehicleFormValid"
				:loading="savingVehicle"
			  >
                                {{ t('save') }}
			  </v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
		<!-- Fahrzeug deaktivieren Dialog -->
		<v-dialog v-model="deactivateDialog" max-width="500" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon icon="mdi-car-off" class="mr-2" color="warning"></v-icon>
			  Fahrzeug deaktivieren
			</v-card-title>
			<v-card-text class="pa-4">
			  <p class="text-body-1 mb-2">Möchtest du das folgende Fahrzeug außer Dienst stellen?</p>
			  <p class="text-body-2 font-weight-bold">{{ vehicleToModify?.title }} ({{ vehicleToModify?.numberplate }})</p>
			  <p class="text-caption text-grey-darken-1 mt-4">
				Das Fahrzeug wird als inaktiv markiert und erscheint nicht mehr in der aktiven Flotte.
			  </p>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeConfirmDialogs">{{ t('cancel') }}</v-btn>
			  <v-btn 
				color="warning" 
				variant="elevated" 
				@click="confirmDeactivateVehicle" 
				:loading="updatingStatus === vehicleToModify?.id"
			  >
				Deaktivieren
			  </v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
		<!-- Fahrzeug aktivieren Dialog -->
		<v-dialog v-model="activateDialog" max-width="500" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon icon="mdi-car-check" class="mr-2" color="success"></v-icon>
			  Fahrzeug aktivieren
			</v-card-title>
			<v-card-text class="pa-4">
			  <p class="text-body-1 mb-2">Möchtest du das folgende Fahrzeug in Dienst stellen?</p>
			  <p class="text-body-2 font-weight-bold">{{ vehicleToModify?.title }} ({{ vehicleToModify?.numberplate }})</p>
			  <p class="text-caption text-grey-darken-1 mt-4">
				Das Fahrzeug wird als aktiv markiert und erscheint wieder in der aktiven Flotte.
			  </p>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeConfirmDialogs">{{ t('cancel') }}</v-btn>
			  <v-btn 
				color="success" 
				variant="elevated" 
				@click="confirmActivateVehicle" 
				:loading="updatingStatus === vehicleToModify?.id"
			  >
				Aktivieren
			  </v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
		<!-- Schaden melden Dialog -->
		<v-dialog v-model="reportDamageDialog" max-width="600px" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon icon="mdi-car-wrench" class="mr-2" color="warning"></v-icon>
			  Schaden melden für "{{ vehicleToModify?.title }}"
			</v-card-title>
			<v-card-text class="pa-4">
			  <v-form ref="reportDamageFormRef" v-model="isReportDamageFormValid">
				<v-text-field 
				  label="Meldender Beamter" 
				  v-model="damageOfficer" 
				  required 
				  :rules="[requiredRule]" 
				  variant="outlined" 
				  density="comfortable" 
				  class="mb-3"
				  bg-color="grey-darken-3"
				  color="primary"
				  prepend-inner-icon="mdi-account-tie"
				></v-text-field>
				<v-textarea 
				  label="Schadensbeschreibung" 
				  v-model="damageDescription" 
				  required 
				  :rules="[requiredRule]" 
				  variant="outlined" 
				  density="comfortable" 
				  rows="3"
				  bg-color="grey-darken-3"
				  color="primary"
				  prepend-inner-icon="mdi-clipboard-text"
				></v-textarea>
			  </v-form>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeConfirmDialogs">{{ t('cancel') }}</v-btn>
			  <v-btn 
				color="error" 
				variant="elevated" 
				@click="confirmReportDamage" 
				:disabled="!isReportDamageFormValid" 
				:loading="updatingDamage === vehicleToModify?.id"
			  >
				Schaden melden
			  </v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
		<!-- Schaden beheben Dialog -->
		<v-dialog v-model="removeDamageDialog" max-width="500" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon icon="mdi-wrench" class="mr-2" color="success"></v-icon>
			  Schaden beheben für "{{ vehicleToModify?.title }}"
			</v-card-title>
			<v-card-text class="pa-4">
			  <p class="text-body-1 mb-2">Bestätigen, dass der Schaden am Fahrzeug behoben wurde?</p>
			  <p class="text-caption text-grey-darken-1 mt-4">
				Die vorherige Schadensmeldung wird dadurch entfernt.
			  </p>
			  
			  <v-card class="damage-details mt-4" variant="outlined">
				<v-card-text>
				  <p class="text-subtitle-2">Aktuelle Schadensmeldung:</p>
				  <p><strong>Melder:</strong> {{ vehicleToModify?.damage_officer }}</p>
				  <p><strong>Meldung vom:</strong> {{ formatDate(vehicleToModify?.damage_time) }}</p>
				  <p class="mt-2"><strong>Beschreibung:</strong></p>
				  <p>{{ vehicleToModify?.damage_description }}</p>
				</v-card-text>
			  </v-card>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeConfirmDialogs">{{ t('cancel') }}</v-btn>
			  <v-btn 
				color="success" 
				variant="elevated" 
				@click="confirmRemoveDamage" 
				:loading="updatingDamage === vehicleToModify?.id"
			  >
				Schaden behoben
			  </v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
		<!-- Fahrzeug anzeigen Dialog -->
		<v-dialog v-model="viewVehicleDialog" max-width="600px" persistent>
		  <v-card class="dialog-card">
			<v-card-title class="dialog-title">
			  <v-icon icon="mdi-car-info" class="mr-2" color="info"></v-icon>
			  Fahrzeug Details - "{{ vehicleToView?.title }}"
			</v-card-title>
			<v-card-text class="pa-4">
			  <v-row v-if="vehicleToView">
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Titel / Rufname" 
					:model-value="vehicleToView.title" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-car"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Nummernschild" 
					:model-value="vehicleToView.numberplate" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-card-account-details"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Rang" 
					:model-value="vehicleToView.rank || 'Nicht angegeben'" 
					variant="outlined" 
					density="comfortable" 
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-shield"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-text-field 
					label="Sortierung" 
					:model-value="String(vehicleToView.sort_order || 0)" 
					variant="outlined" 
					density="comfortable"
					color="primary"
					bg-color="grey-darken-3"
					prepend-inner-icon="mdi-sort"
					readonly
				  ></v-text-field>
				</v-col>
				
				<v-col cols="12" md="6">
				  <v-chip 
					:color="Number(vehicleToView.active) == 1 ? 'success' : 'grey'" 
					variant="tonal"
					size="large"
					:prepend-icon="Number(vehicleToView.active) == 1 ? 'mdi-check-circle' : 'mdi-close-circle'"
					class="mt-2"
				  >
					Status: {{ Number(vehicleToView.active) == 1 ? 'Aktiv' : 'Inaktiv' }}
				  </v-chip>
				</v-col>
				
				<v-col cols="12" md="6" v-if="Number(vehicleToView.damage) == 1">
				  <v-chip 
					color="error" 
					variant="tonal"
					size="large"
					prepend-icon="mdi-alert"
					class="mt-2"
				  >
					Fahrzeug beschädigt
				  </v-chip>
				</v-col>
				
				<v-col cols="12" v-if="Number(vehicleToView.damage) == 1 && vehicleToView.damage_description">
				  <v-card variant="outlined" class="damage-info">
					<v-card-title class="text-subtitle-2">
					  <v-icon icon="mdi-information" class="mr-2" color="warning"></v-icon>
					  Schadensinformationen
					</v-card-title>
					<v-card-text>
					  <p><strong>Melder:</strong> {{ vehicleToView.damage_officer || 'Unbekannt' }}</p>
					  <p><strong>Gemeldet am:</strong> {{ formatDate(vehicleToView.damage_time) }}</p>
					  <p class="mt-2"><strong>Beschreibung:</strong></p>
					  <p>{{ vehicleToView.damage_description }}</p>
					</v-card-text>
				  </v-card>
				</v-col>
			  </v-row>
			</v-card-text>
			<v-divider></v-divider>
			<v-card-actions class="pa-4">
			  <v-spacer></v-spacer>
                          <v-btn variant="text" @click="closeViewVehicleDialog">Schließen</v-btn>
			</v-card-actions>
		  </v-card>
		</v-dialog>
		
	  </v-container>
	</div>
  </template>
  
  <style scoped>
  @import '@/scss/common.scss';

  .vehicle-container {
	@extend .page-container;
  }
  
  .section-header {
	@extend .section-header;
  }
  
  .main-table-card {
	@extend .app-card;
  }
  
  .vehicle-table {
	width: 100%;
  }
  
  .empty-state {
	@extend .empty-state;
  }
  
  .action-btn {
	@extend .action-btn;
  }
  
  .dialog-card {
	@extend .dialog-card;
  }
  
  .dialog-title {
	@extend .dialog-title;
  }
  
  .damage-text {
	color: var(--error) !important; 
	font-weight: 600;
	position: relative;
  }
  
  .damage-details {
	background: rgba(var(--desktop-bg-dark-2), 0.4) !important;
	border: 1px solid var(--card-border);
  }
  
  .detail-card {
	@extend .app-card;
	max-width: 1200px;
	margin: 0 auto;
  }
  
  .damage-info {
	background: rgba(var(--desktop-bg-dark-2), 0.4) !important;
	border: 1px solid var(--card-border);
  }
  
  /* Animation effects */
  @keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
  }
  </style>