<script setup lang="ts">
import { ref, computed, onMounted, defineAsyncComponent, unref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { VehicleFile } from '@/types/Vehicle'; // Adjust path if needed
import type { PersonFile } from '@/types/Person';
import { useToast } from 'vue-toastification'; // Import Toastification
import { useI18n } from 'vue-i18n';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
// --- Dynamic Component Imports ---
// Using generic components as fallbacks, as in the original code.
// Replace with specific imports if needed based on authority.
const AuthorityAddVehicleFile = defineAsyncComponent({
    loader: () => import('@/components/VehicleFile/Authority/Add.vue'),
    errorComponent: {
        template: '<v-alert type="error">Error loading Add component</v-alert>'
    },
    loadingComponent: {
        template: '<v-progress-circular indeterminate></v-progress-circular>'
    },
    delay: 200,
    timeout: 10000
});
const AuthorityEditVehicleFile = defineAsyncComponent({
    loader: () => import('@/components/VehicleFile/Authority/Edit.vue'),
    errorComponent: {
        template: '<v-alert type="error">Error loading Edit component</v-alert>'
    },
    loadingComponent: {
        template: '<v-progress-circular indeterminate></v-progress-circular>'
    },
    delay: 200,
    timeout: 10000
});
const AuthorityViewVehicleFile = defineAsyncComponent({
    loader: () => import('@/components/VehicleFile/Authority/View.vue'),
    errorComponent: {
        template: '<v-alert type="error">Error loading View component</v-alert>'
    },
    loadingComponent: {
        template: '<v-progress-circular indeterminate></v-progress-circular>'
    },
    delay: 200,
    timeout: 10000
});

// --- Store, Router & Permissions ---
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, unknown>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: string | number
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
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);

// --- Component State ---
const vehicles = ref<VehicleFile[]>([]);
const search = ref('');
const loadingVehicles = ref(false);
const deletingVehicle = ref(false);

// --- Dialog States ---
const addVehicleDialog = ref(false);
const editVehicleDialog = ref(false);
const viewVehicleDialog = ref(false);
const deleteVehicleDialog = ref(false);

// --- Detail View State ---
const isInDetailMode = ref(false);
const detailVehicle = ref<VehicleFile | null>(null);

// --- Data for Child Components & Dialogs ---
const editedVehicle = ref<VehicleFile | null>(null); // Data passed to Edit component
const selectedVehicleToView = ref<VehicleFile | null>(null); // Data passed to View component
const vehicleToDelete = ref<VehicleFile | null>(null); // Data for Delete dialog

// --- Toastification ---
const toast = useToast();

// --- Additional state for inline detail view ---
const persons = ref<PersonFile[]>([]);
const currentTab = ref(0);

// --- Table Headers ---
const vehicleHeaders = computed(() => [
    { title: t('vehicleForm.numberplate'), key: 'numberplate', sortable: true },
    { title: t('vehicleForm.brand'), key: 'brand', sortable: true },
    { title: t('vehicleForm.model'), key: 'model', sortable: true },
    { title: t('vehicleForm.color'), key: 'color', sortable: true },
    { title: t('actions'), key: 'actions', sortable: false, align: 'end' },
] as const);

// Table headers for persons in detail view
const vehiclePersonHeaders = computed(() => [
    { title: t('personForm.name'), key: 'name', sortable: true },
    { title: t('personForm.phone'), key: 'phonenumber', sortable: true },
    { title: t('personForm.mail'), key: 'mail', sortable: true },
] as const);

// --- Dynamic Component Selection (Simplified based on original code) ---
// These computed properties determine which component to load for Add/Edit/View actions.
// Currently, they return the generic Authority components. Modify the logic using
// authStore.user?.authority if different components per authority are needed.
const addVehicleComponent = computed(() => AuthorityAddVehicleFile);
const editVehicleComponent = computed(() => AuthorityEditVehicleFile);
const viewVehicleComponent = computed(() => AuthorityViewVehicleFile);
const isDetailView = computed(() => {
	return addVehicleDialog.value || editVehicleDialog.value || viewVehicleDialog.value || isInDetailMode.value;
  });
  
  // --- Zusätzliche Methode zum Schließen aller Detailansichten ---
  const closeAllDetailViews = () => {
	addVehicleDialog.value = false;
	editVehicleDialog.value = false;
	viewVehicleDialog.value = false;
	isInDetailMode.value = false;
	editedVehicle.value = null;
	selectedVehicleToView.value = null;
	detailVehicle.value = null;
  };
// --- Data Fetching ---
const fetchVehicles = async () => {
    loadingVehicles.value = true;
    try {
        const response = await apiClientAuth.get<VehicleFile[]>(
            'vehiclefile/?action=getVehicles'
        );
        vehicles.value = (response.data || []).map((vehicle: VehicleFile) => ({
            ...vehicle,
            // Ensure boolean conversion is robust
            stolen: vehicle.stolen === '1' || vehicle.stolen === true,
            wanted: vehicle.wanted === '1' || vehicle.wanted === true,
        }));
    } catch (error: unknown) {
        const errorMessage = error instanceof Error && 'response' in error ? 
            (error as { response?: { data?: { error?: string } } }).response?.data?.error : undefined;
        toast.error(errorMessage || t('toast.loadVehiclesError'));
        vehicles.value = [];
    } finally {
        loadingVehicles.value = false;
    }
};

// --- Computed Properties ---
const filteredVehicles = computed(() => {
    const searchTermLower = search.value.trim().toLowerCase();
    if (!searchTermLower) {
        return vehicles.value;
    }
    return vehicles.value.filter(vehicle => {
        const searchableContent = `
		${vehicle.numberplate || ''}
		${vehicle.brand || ''}
		${vehicle.model || ''}
		${vehicle.color || ''}
	  `.toLowerCase();
        return searchableContent.includes(searchTermLower);
    });
});

// --- Methods ---

// Dialog Openers
const openAddVehicleDialog = () => {
    try {
        console.log('Opening add vehicle dialog');
        addVehicleDialog.value = true;
    } catch (error) {
        console.error('Error opening add dialog:', error);
        toast.error('Error opening add dialog');
    }
};

const openEditVehicleDialog = (vehicle: VehicleFile) => {
    try {
        console.log('Opening edit vehicle dialog for:', vehicle);
        editedVehicle.value = { ...vehicle }; // Pass a copy
        editVehicleDialog.value = true;
    } catch (error) {
        console.error('Error opening edit dialog:', error);
        toast.error('Error opening edit dialog');
    }
};

const openViewVehicleDialog = (vehicle: VehicleFile) => {
    try {
        if (vehicle) {
            console.log('Opening view vehicle dialog for:', vehicle);
            selectedVehicleToView.value = { ...vehicle }; // Pass a copy
            viewVehicleDialog.value = true;
        } else {
            toast.error(t('toast.vehicleDataLoadError'));
        }
    } catch (error) {
        console.error('Error opening view dialog:', error);
        toast.error('Error opening view dialog');
    }
};

const showVehicleDetails = async (vehicle: VehicleFile) => {
    try {
        if (vehicle) {
            console.log('Showing vehicle details inline for:', vehicle);
            detailVehicle.value = { ...vehicle }; // Pass a copy
            isInDetailMode.value = true;
            currentTab.value = 0; // Reset to first tab
            await fetchPersons(); // Load persons data for owners/drivers
        } else {
            toast.error(t('toast.vehicleDataLoadError'));
        }
    } catch (error) {
        console.error('Error showing vehicle details:', error);
        toast.error('Error showing vehicle details');
    }
};

const openDeleteVehicleDialog = (vehicle: VehicleFile) => {
    vehicleToDelete.value = vehicle; // Store for confirmation dialog text
    deleteVehicleDialog.value = true;
};

// Dialog Closers & Event Handlers
const closeAddVehicleDialog = () => {
    addVehicleDialog.value = false;
};

const onVehicleAdded = () => {
    addVehicleDialog.value = false;
    toast.success(t('toast.vehicleAddSuccess'));
    fetchVehicles(); // Refresh list
};

const closeEditVehicleDialog = () => {
    editVehicleDialog.value = false;
    editedVehicle.value = null;
};

const onVehicleUpdated = () => {
    editVehicleDialog.value = false;
    editedVehicle.value = null;
    toast.success(t('toast.vehicleUpdateSuccess'));
    fetchVehicles(); // Refresh list
};

const closeViewVehicleDialog = () => {
    viewVehicleDialog.value = false;
    selectedVehicleToView.value = null;
};

const closeDeleteDialog = () => {
    deleteVehicleDialog.value = false;
    vehicleToDelete.value = null;
};

const confirmDeleteVehicle = async () => {
    if (!vehicleToDelete.value) return;
    deletingVehicle.value = true;
    try {
        await apiClientAuth.post('/vehiclefile/?action=deleteVehicle', {
            id: vehicleToDelete.value.id,
        });
        await fetchVehicles(); // Refresh list
        closeDeleteDialog();
        toast.success(t('toast.vehicleDeleteSuccess'));
    } catch (error: unknown) {
        const errorMessage = error instanceof Error && 'response' in error ? 
            (error as { response?: { data?: { error?: string } } }).response?.data?.error : undefined;
        toast.error(errorMessage || t('toast.vehicleDeleteError'));
    } finally {
        deletingVehicle.value = false;
    }
};

// --- Methods for inline detail view ---
const fetchPersons = async () => {
    try {
        const response = await apiClientAuth.get('/vehiclefile/?action=getPersons');
        persons.value = response.data.map((person: PersonFile) => ({
            ...person,
            name: `${person.firstname} ${person.lastname}`,
        }));
        console.log('Persons loaded successfully:', persons.value.length);
    } catch (error: unknown) {
        console.error('Error loading persons:', error);
        toast.error(t('authorityComponents.common.loadError'));
    }
};

// Get persons based on their IDs
const getPersonsByIds = (ids: (number | string)[]) => {
    if (!ids || !Array.isArray(ids) || ids.length === 0) return [];

    // Convert all IDs to numbers for comparison
    const numericIds = ids.map(id => (typeof id === 'string' ? parseInt(id, 10) : id));

    // Filter persons by these IDs
    const result = persons.value.filter(person => numericIds.includes(person.id));
    console.log('Found persons by IDs:', numericIds, result);
    return result;
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    console.log('🚗 VehicleFileView onMounted started');
    console.log('📍 Current route:', route.path);
    console.log('📍 Route query params:', route.query);
    console.log('📍 Props:', props);
    console.log('📍 Props meta:', props.meta);
    console.log('📍 Props id:', props.id);
    
    await fetchVehicles();
    
    console.log('✅ Vehicles loaded, count:', vehicles.value.length);
    console.log('📋 Vehicle IDs available:', vehicles.value.map(v => v.id));
    
    // Check if we need to open a specific vehicle (from route, props.id, or props.meta)
    const vehicleId = route.query.id || props.id || props.meta?.id || props.meta?.vehicleId;
    console.log('🔍 Looking for vehicle ID:', vehicleId, 'Type:', typeof vehicleId);
    console.log('🔍 ID sources - route.query.id:', route.query.id, 'props.id:', props.id, 'props.meta?.id:', props.meta?.id);
    
    if (vehicleId) {
        // Find the vehicle in the loaded data
        const vehicle = vehicles.value.find(v => {
            console.log(`Comparing: ${v.id} (type: ${typeof v.id}) with ${vehicleId} (type: ${typeof vehicleId})`);
            return v.id == vehicleId;
        });
        
        if (vehicle) {
            console.log('✅ Vehicle found:', vehicle);
            console.log('🔓 Showing vehicle details inline for vehicle:', vehicle.numberplate);
            // Show the vehicle details inline instead of opening a dialog
            showVehicleDetails(vehicle);
        } else {
            console.error('❌ Vehicle not found with ID:', vehicleId);
            console.log('Available vehicles:', vehicles.value);
            toast.error(t('toast.vehicleNotFound'));
        }
    } else {
        console.log('ℹ️ No vehicle ID provided in route or props');
    }
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('VehicleFileView', () => unref(vehicleHeaders) as any);

/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(filteredVehicles) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'fahrzeugakten' });
}

</script>

<template>
	<v-container fluid class="pa-4">
	  <v-row class="mb-4 align-center">
		<v-col cols="auto">
		  <v-btn
			v-if="!addVehicleDialog && !editVehicleDialog && !viewVehicleDialog && canEdit"
			@click="openAddVehicleDialog"
			color="primary"
			variant="elevated"
			prepend-icon="mdi-car-plus"
			class="action-button"
			elevation="2"
                  >
                        {{ t('vehicleFileView.newVehicle') }}
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
                        {{ t('vehicleFileView.description') }}
		  </v-alert>
		</v-col>
	  </v-row>
  
	  <!-- Haupttabelle - nur anzeigen, wenn keine Details/Dialoge geöffnet sind -->
	  <v-card 
		v-if="!addVehicleDialog && !editVehicleDialog && !viewVehicleDialog && !isInDetailMode"
		class="main-card elevation-4"
	  >
		<v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                  <v-toolbar-title class="text-h6">
                        <v-icon start size="20" class="mr-2">mdi-car-multiple</v-icon>
                        {{ t('vehicleFileView.title') }}
                  </v-toolbar-title>
		  <v-spacer></v-spacer>
                  <v-text-field
                        v-model="search"
                        :label="t('vehicleFileView.searchPlaceholder')"
			prepend-inner-icon="mdi-magnify"
			hide-details
			density="compact"
			variant="solo-filled"
			flat
			class="max-w-400 search-field"
		  />
		</v-toolbar>
		
		<v-divider></v-divider>
		
		<!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
		<KTableToolbar :columns="kCols" :shown="(filteredVehicles || []).length" />
		<v-data-table
		  :headers="kCols.visible.value"
		  :items="filteredVehicles"
		  class="elevation-0"
		  :search="search"
		  :items-per-page="25"
		  item-value="id"
		  :loading="loadingVehicles"
		  hover
		  density="comfortable"
		    v-model="kSelected"
		    show-select
		>
		  <template v-slot:[`item.numberplate`]="{ item }">
			<span
			  @click="showVehicleDetails(item)"
			  class="vehicle-link"
			>
			  {{ item.numberplate }}
			</span>
		  </template>
  
		  <template v-slot:[`item.stolen`]="{ item }">
			<v-chip
			  v-if="item.stolen"
			  size="small"
			  color="error"
			  variant="tonal"
			  class="text-caption"
			>
			  <v-icon start size="x-small">mdi-car-off</v-icon>
                          {{ t('vehicleForm.stolen') }}
			</v-chip>
			<span v-else>-</span>
		  </template>
  
		  <template v-slot:[`item.wanted`]="{ item }">
			<v-chip
			  v-if="item.wanted"
			  size="small"
			  color="warning"
			  variant="tonal"
			  class="text-caption"
			>
			  <v-icon start size="x-small">mdi-alert-circle</v-icon>
                          {{ t('vehicleForm.wanted') }}
			</v-chip>
			<span v-else>-</span>
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
					@click="openEditVehicleDialog(item)" 
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
					@click="openDeleteVehicleDialog(item)" 
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
			  <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-car-off</v-icon>
                          <span>{{ t('vehicleFileView.noData') }}</span>
			</div>
		  </template>
		  
		  <template v-slot:loading>
			<div class="loading-state">
			  <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                          <span>{{ t('vehicleFileView.loading') }}</span>
			</div>
		  </template>
		</v-data-table>
		<!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
		<KBulkBar
		    :count="kSelected.length"
		    :shown="filteredVehicles.length"
		    :total="filteredVehicles.length"
		    @clear="kSelected = []"
		>
		    <template #actions>
		        <v-btn variant="outlined" size="small" @click="kExportSelection">
		            {{ t('kTable.exportSelection') }}
		        </v-btn>
		    </template>
		</KBulkBar>
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
                  {{ t('vehicleFileView.backToList') }}
		</v-btn>
	  </div>

	  <!-- Inline Vehicle Detail View -->
	  <v-card 
		v-if="isInDetailMode && detailVehicle"
		class="vehicle-detail-card elevation-4"
	  >
		<v-toolbar density="compact" color="primary" class="card-toolbar">
			<v-toolbar-title class="text-subtitle-1">
				<v-icon start size="18" class="mr-2">mdi-car</v-icon>
				{{ t('vehicleView.detailsTitle', { name: `${detailVehicle.brand} ${detailVehicle.model}` }) }}
			</v-toolbar-title>
			<v-spacer></v-spacer>
			<v-chip
				v-if="detailVehicle.wanted"
				color="error"
				size="small"
				class="mr-2"
				prepend-icon="mdi-alert-circle"
			>
				{{ t('vehicleForm.wanted') }}
			</v-chip>
			<v-chip v-if="detailVehicle.stolen" color="warning" size="small" prepend-icon="mdi-car-off">
				{{ t('vehicleForm.stolen') }}
			</v-chip>
		</v-toolbar>

		<!-- Tabs für Fahrzeuginformationen -->
		<v-tabs
			v-model="currentTab"
			bg-color="rgba(30, 41, 59, 0.4)"
			slider-color="primary"
			density="comfortable"
			class="tab-bar"
		>
			<v-tab value="1" class="tab-item">
				<v-icon size="small" class="mr-2">mdi-car-info</v-icon>
				{{ t('vehicleForm.infoTab') }}
			</v-tab>
			<v-tab value="2" class="tab-item">
				<v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
				{{ t('vehicleForm.ownersDriversSection') }}
			</v-tab>
			<v-tab value="3" class="tab-item">
				<v-icon size="small" class="mr-2">mdi-text-box</v-icon>
				{{ t('vehicleForm.descriptionTab') }}
			</v-tab>
		</v-tabs>

		<v-window v-model="currentTab">
			<!-- Tab 1: Fahrzeugdetails -->
			<v-window-item value="1">
				<v-form ref="form" class="pa-6">
					<v-row>
						<v-col cols="12" sm="6">
							<v-text-field
								:label="t('vehicleForm.brand')"
								v-model="detailVehicle.brand"
								prepend-inner-icon="mdi-car-estate"
								variant="outlined"
								density="comfortable"
								readonly
								bg-color="grey-darken-3"
								class="field-item"
							></v-text-field>
						</v-col>

						<v-col cols="12" sm="6">
							<v-text-field
								:label="t('vehicleForm.model')"
								v-model="detailVehicle.model"
								prepend-inner-icon="mdi-car-side"
								variant="outlined"
								density="comfortable"
								readonly
								bg-color="grey-darken-3"
								class="field-item"
							></v-text-field>
						</v-col>

						<v-col cols="12" sm="6">
							<v-text-field
								:label="t('vehicleForm.numberplate')"
								v-model="detailVehicle.numberplate"
								prepend-inner-icon="mdi-card-account-details"
								variant="outlined"
								density="comfortable"
								readonly
								bg-color="grey-darken-3"
								class="field-item"
							></v-text-field>
						</v-col>

						<v-col cols="12" sm="6">
							<v-text-field
								:label="t('vehicleForm.color')"
								v-model="detailVehicle.color"
								prepend-inner-icon="mdi-palette"
								variant="outlined"
								density="comfortable"
								readonly
								bg-color="grey-darken-3"
								class="field-item"
							></v-text-field>
						</v-col>

						<v-col cols="12" sm="6">
							<v-checkbox
								v-model="detailVehicle.stolen"
								:label="t('vehicleForm.stolen')"
								color="warning"
								hide-details
								readonly
								bg-color="grey-darken-3"
								class="mt-2"
							></v-checkbox>
						</v-col>

						<v-col cols="12" sm="6">
							<v-checkbox
								v-model="detailVehicle.wanted"
								:label="t('vehicleForm.wanted')"
								color="error"
								hide-details
								readonly
								bg-color="grey-darken-3"
								class="mt-2"
							></v-checkbox>
						</v-col>

						<v-col cols="12">
							<v-text-field
								:label="t('vehicleForm.registered')"
								v-model="detailVehicle.registered"
								prepend-inner-icon="mdi-calendar"
								variant="outlined"
								density="comfortable"
								readonly
								bg-color="grey-darken-3"
								class="field-item"
							></v-text-field>
						</v-col>
					</v-row>
				</v-form>
			</v-window-item>

			<!-- Tab 2: Besitzer & Fahrer -->
			<v-window-item value="2">
				<div class="pa-6">
					<div class="section-title mb-3">
						<v-icon size="small" class="mr-2">mdi-account-key</v-icon>
						<span class="text-subtitle-1 font-weight-medium">{{ t('vehicleForm.owners') }}</span>
					</div>

					<v-card variant="outlined" class="mb-6 table-card" color="blue-grey-darken-3">
						<v-data-table
							:headers="vehiclePersonHeaders"
							:items="getPersonsByIds(detailVehicle.owners || [])"
							:items-per-page="5"
							density="comfortable"
							hover
							class="person-table"
						>
							<template v-slot:no-data>
								<div class="empty-state">
									<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
									<span>{{ t('vehicleView.noOwners') }}</span>
								</div>
							</template>
						</v-data-table>
					</v-card>

					<div class="section-title mb-3">
						<v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
						<span class="text-subtitle-1 font-weight-medium">{{ t('vehicleForm.drivers') }}</span>
					</div>

					<v-card variant="outlined" class="table-card" color="blue-grey-darken-3">
						<v-data-table
							:headers="vehiclePersonHeaders"
							:items="getPersonsByIds(detailVehicle.drivers || [])"
							:items-per-page="5"
							density="comfortable"
							hover
							class="person-table"
						>
							<template v-slot:no-data>
								<div class="empty-state">
									<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
									<span>{{ t('vehicleView.noDrivers') }}</span>
								</div>
							</template>
						</v-data-table>
					</v-card>
				</div>
			</v-window-item>

			<!-- Tab 3: Beschreibung -->
			<v-window-item value="3">
				<div class="description-container pa-6">
					<div class="section-title mb-4">
						<v-icon size="small" class="mr-2">mdi-text-box</v-icon>
						<span class="text-subtitle-1 font-weight-medium">{{ t('vehicleForm.vehicleDescriptionSection') }}</span>
					</div>

					<div v-if="detailVehicle.text" class="description-content">
						<div v-html="detailVehicle.text"></div>
					</div>

					<div v-else class="empty-description">
						<v-icon size="40" color="grey-darken-1" class="mb-2">mdi-text-box-outline</v-icon>
						<span>{{ t('vehicleView.noDescription') }}</span>
					</div>
				</div>
			</v-window-item>
		</v-window>
	  </v-card>
  
	  <!-- Add Vehicle Dialog Component -->
	  <component
		:is="addVehicleComponent"
		v-model="addVehicleDialog"
		@vehicle-added="onVehicleAdded"
		@close="closeAddVehicleDialog"
	  />
  
	  <!-- Edit Vehicle Dialog Component -->
	  <component
		:is="editVehicleComponent"
		v-model="editVehicleDialog"
		:vehicle-to-edit="editedVehicle"
		@vehicle-updated="onVehicleUpdated"
		@close="closeEditVehicleDialog"
	  />
  
	  <!-- View Vehicle Dialog Component -->
	  <component
		:is="viewVehicleComponent"
		v-model="viewVehicleDialog"
		:vehicle-to-view="selectedVehicleToView"
		@close="closeViewVehicleDialog"
	  />
  
	  <!-- Delete Confirmation Dialog -->
	  <v-dialog v-model="deleteVehicleDialog" max-width="500" persistent class="confirmation-dialog">
		<v-card>
		  <v-card-title class="text-h5 dialog-title">
			<v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                        {{ t('vehicleFileView.deleteTitle') }}
		  </v-card-title>
		  
		  <v-card-text class="pt-4">
                        <p>{{ t('vehicleFileView.confirmDelete', { numberplate: vehicleToDelete?.numberplate }) }}</p>
                        <div class="text-caption text-medium-emphasis mt-2">{{ t('vehicleFileView.irreversibleWarning') }}</div>
		  </v-card-text>
		  
		  <v-divider></v-divider>
		  
		  <v-card-actions class="pa-4">
			<v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteDialog" class="mr-2">{{ t('cancel') }}</v-btn>
			<v-btn 
			  color="error" 
			  variant="elevated" 
			  @click="confirmDeleteVehicle" 
			  :loading="deletingVehicle"
			  class="delete-button"
			>
                          {{ t('delete') }}
			</v-btn>
		  </v-card-actions>
		</v-card>
	  </v-dialog>
	</v-container>
  </template>

  <style scoped>
  /* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */
  
  /* Main Container Styles */
  .main-card {
	background: rgba(15, 23, 42, 0.6) !important;
	border: 1px solid var(--card-border);
	backdrop-filter: blur(10px);
	border-radius: 12px;
	overflow: hidden;
  }

  /* Vehicle Detail Card Styles */
  .vehicle-detail-card {
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
  
  .vehicle-link {
	cursor: pointer;
	color: #1976D2;
	transition: all 0.2s ease;
  }
  
  .vehicle-link:hover {
	text-decoration: underline;
	color: #2196F3;
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
  .confirmation-dialog :deep(.v-overlay__content) {
	border-radius: 16px;
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
  
  /* Detail View Specific Styles */
  .vehicle-detail-card .tab-bar {
	background: rgba(30, 41, 59, 0.4) !important;
	border-bottom: 1px solid var(--card-border);
  }

  .vehicle-detail-card .tab-item {
	text-transform: none;
	letter-spacing: normal;
	transition: all 0.2s ease;
  }

  .vehicle-detail-card .field-item {
	border-radius: 8px;
	transition: transform 0.2s ease;
  }

  .vehicle-detail-card .field-item:focus-within {
	transform: translateY(-2px);
  }

  .vehicle-detail-card .table-card {
	background: rgba(30, 41, 59, 0.3) !important;
	border-radius: 8px;
	overflow: hidden;
  }

  .vehicle-detail-card .person-table :deep(th) {
	background-color: rgba(30, 41, 59, 0.5) !important;
  }

  .vehicle-detail-card .person-table :deep(tr:hover) {
	background-color: var(--k-row-hover) !important;
  }

  .vehicle-detail-card .section-title {
	display: flex;
	align-items: center;
	color: #e2e8f0;
  }

  .vehicle-detail-card .description-content {
	background: rgba(30, 41, 59, 0.3);
	border-radius: 8px;
	padding: 16px;
	border: 1px solid var(--card-border);
	min-height: 200px;
  }

  .vehicle-detail-card .empty-description {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 32px 16px;
	color: var(--k-ink-muted);
	text-align: center;
  }

  /* Responsive adjustments */
  @media (max-width: 600px) {
	.max-w-400 {
	  max-width: 100%;
	}
  }
  </style>