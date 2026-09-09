<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import type { VehicleFile } from "@/types/Vehicle";
import type { PersonFile } from "@/types/Person";
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

import TiptapEditor from '@/components/TiptapEditor.vue';
interface Props {
  modelValue?: boolean
  vehicleToEdit?: VehicleFile | null
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(["update:modelValue", "vehicleUpdated", 'close']);

const toast = useToast();
const { t } = useI18n();

const dialog = computed({
			get: () => props.modelValue,
			set: (value) => {
				emit("update:modelValue", value);
			},
		});

		// Create a properly typed vehicle object with defaults and merge with provided data
		const vehicle = ref<VehicleFile | null>(null);
		const persons = ref<PersonFile[]>([]);
		const currentTab = ref(0);
		const saving = ref(false);

		// Modify the fetch persons function to ensure persons are loaded before selecting
		const fetchPersons = async () => {
			try {
				const response = await apiClientAuth.get(
					"vehiclefile/?action=getPersons"
				);
				persons.value = response.data.map((person: PersonFile) => ({
					...person,
					fullname: `${person.firstname} ${person.lastname}`,
					// Adding text and value properties for compatibility with v-select
					text: `${person.firstname} ${person.lastname}`,
					value: person.id
				}));
				
				// Force refresh the selection mappings
				if (vehicle.value) {
					matchPersonsToSelectedIds();
				}
			} catch (error) {
        toast.error(t('authorityComponents.common.loadError'));
			}
		};

		// Function to match person objects to the selected IDs
		function matchPersonsToSelectedIds() {
			if (!vehicle.value || !persons.value.length) return;
		}

        // Modify the watch function to ensure correct initialization
        watch(
            () => props.vehicleToEdit,
            (newVal) => {
                if (newVal) {
                    // Flache Kopie - nur verwenden, wenn keine verschachtelten Objekte/Arrays geändert werden!
                    vehicle.value = { ...newVal };

                    // Arrays trotzdem sicherstellen für v-model multiple
                    if (!Array.isArray(vehicle?.value.owners)) {
                        vehicle.value.owners = [];
                    }
                    if (!Array.isArray(vehicle?.value.drivers)) {
                        vehicle.value.drivers = [];
                    }
                    
                    // Match persons when vehicle is set
                    matchPersonsToSelectedIds();
                } else {
                    vehicle.value = null;
                }
            },
            { immediate: true, deep: true }
        );

		// Watch for changes in the persons array to re-match with IDs
		watch(
			() => persons.value,
			() => {
				matchPersonsToSelectedIds();
			},
			{ immediate: false }
		);

const requiredRule = (value: string) =>
        !!value || t('authorityComponents.common.requiredField');
		const updateVehicle = async () => {
			try {
				saving.value = true;
				// Include multiple owners and drivers in the request payload
				const payload = {
					...vehicle?.value,
					owners: vehicle.value?.owners,
					drivers: vehicle.value?.drivers,
				};
				await apiClientAuth.post("/vehiclefile/?action=editVehicle", payload);
				emit("vehicleUpdated");
				closeDialog();
			} catch (error) {
        toast.error(t('authorityComponents.vehicleFile.errorUpdate'));
			} finally {
				saving.value = false;
			}
		};

		const isFormValid = (form: VehicleFile | null) => {
			if (!form) return false;
			return (
				form.brand !== "" &&
				form.model !== "" &&
				form.numberplate !== ""
			);
		};

		const closeDialog = () => {
			dialog.value = false;
			emit("close");
		};

		onMounted(fetchPersons);

		 // Helper function to get person by ID
		function getPersonById(id: number): PersonFile | undefined {
			return persons.value.find(p => p.id === id);
		}

		 // Add a function to transform the owners array from IDs to person objects
		function ownersAsPersons() {
			if (!vehicle.value || !vehicle.value.owners || !Array.isArray(vehicle.value.owners))
				return [];
			
			return vehicle.value.owners.map(id => {
				const person = getPersonById(Number(id));
				return person || { id, fullname: `ID: ${id}` };
			});
		}

		// Add a function to transform the drivers array from IDs to person objects
		function driversAsPersons() {
			if (!vehicle.value || !vehicle.value.drivers || !Array.isArray(vehicle.value.drivers))
				return [];
			
			return vehicle.value.drivers.map(id => {
				const person = getPersonById(Number(id));
				return person || { id, fullname: `ID: ${id}` };
			});
		}

		// Add functions to handle selection updates
		function updateOwners(selectedPersons: (PersonFile | { id: number; fullname: string; })[]) {
			if (vehicle.value) {
				vehicle.value.owners = selectedPersons.map(p => p.id);
			}
		}

		function updateDrivers(selectedPersons: (PersonFile | { id: number; fullname: string; })[]) {
			if (vehicle.value) {
				vehicle.value.drivers = selectedPersons.map(p => p.id);
			}
		}

		const selectedOwners = computed({
			get: () => ownersAsPersons(),
			set: (value) => updateOwners(value)
		});

		const selectedDrivers = computed({
			get: () => driversAsPersons(),
			set: (value) => updateDrivers(value)
		});

		const brand = computed({
			get: () => vehicle.value?.brand || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.brand = value;
			}
		});

		const model = computed({
			get: () => vehicle.value?.model || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.model = value;
			}
		});

		const numberplate = computed({
			get: () => vehicle.value?.numberplate || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.numberplate = value;
			}
		});

		const color = computed({
			get: () => vehicle.value?.color || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.color = value;
			}
		});

		const stolen = computed({
			get: () => vehicle.value?.stolen || false,
			set: (value) => {
				if (vehicle.value) vehicle.value.stolen = value;
			}
		});

		const wanted = computed({
			get: () => vehicle.value?.wanted || false,
			set: (value) => {
				if (vehicle.value) vehicle.value.wanted = value;
			}
		});

		const registered = computed({
			get: () => vehicle.value?.registered || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.registered = value;
			}
		});

		const text = computed({
			get: () => vehicle.value?.text || '',
			set: (value) => {
				if (vehicle.value) vehicle.value.text = value;
			}
		});
</script>

<template>
	<!-- Fahrzeug bearbeiten Formular -->
	<v-card 
	  v-if="vehicleToEdit" 
	  class="vehicle-edit-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
		<v-toolbar-title class="text-subtitle-1">
		  <v-icon start size="18" class="mr-2">mdi-car-wrench</v-icon>
                  {{$t('authorityComponents.vehicleFile.editTitle')}} - {{ vehicle?.brand }} {{ vehicle?.model }}
		</v-toolbar-title>
	  </v-toolbar>
  
	  <!-- Tabs für verschiedene Formularinformationen -->
	  <v-tabs 
		v-model="currentTab" 
		bg-color="rgba(30, 41, 59, 0.4)" 
		slider-color="primary"
		density="comfortable"
		class="tab-bar"
	  >
		<v-tab value="1" class="tab-item">
		  <v-icon size="small" class="mr-2">mdi-car-info</v-icon>
                  {{$t('authorityComponents.tabs.info')}}
		</v-tab>
		<v-tab value="2" class="tab-item">
		  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                  {{$t('authorityComponents.tabs.description')}}
		</v-tab>
	  </v-tabs>
  
	  <v-window v-model="currentTab">
		<!-- Tab 1: Fahrzeuginformationen -->
		<v-window-item value="1">
		  <v-form ref="form" class="pa-6">
			<!-- Eigentümer & Fahrer -->
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('vehicleForm.ownersDriversSection')}}</span>
			</div>
			
			<v-row>
			  <v-col cols="12">
				<v-select
				  v-model="selectedOwners"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
                                  :label="$t('vehicleForm.owners')"
				  required
				  multiple
				  chips
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-account-key"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				>
				  <!-- Custom template for selected items in chips -->
				  <template v-slot:selection="{ item, index }">
					<v-chip
					  v-if="index < 2"
					  color="primary"
					  size="small"
					  class="mr-1"
					>
					  {{ item.fullname }}
					</v-chip>
					<span
					  v-else-if="index === 2"
					  class="text-grey text-caption"
					>
					  (+{{ selectedOwners.length - 2 }} weitere)
					</span>
				  </template>
				</v-select>
			  </v-col>
			  
			  <v-col cols="12">
				<v-select
				  v-model="selectedDrivers"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
                                  :label="$t('vehicleForm.drivers')"
				  required
				  multiple
				  chips
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-account-multiple"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				>
				  <!-- Custom template for selected items in chips -->
				  <template v-slot:selection="{ item, index }">
					<v-chip
					  v-if="index < 2"
					  color="info"
					  size="small"
					  class="mr-1"
					>
					  {{ item.fullname }}
					</v-chip>
					<span
					  v-else-if="index === 2"
					  class="text-grey text-caption"
					>
					  (+{{ selectedDrivers.length - 2 }} weitere)
					</span>
				  </template>
				</v-select>
			  </v-col>
			</v-row>
			
			<!-- Fahrzeugdaten -->
			<div class="section-title mb-4 mt-4">
			  <v-icon size="small" class="mr-2">mdi-car</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('vehicleForm.vehicleDataSection')}}</span>
			</div>
			
			<v-row>
			  <v-col cols="12" sm="6">
				<v-text-field
                                  :label="$t('vehicleForm.brand')"
				  v-model="brand"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-car-estate"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
                                  :label="$t('vehicleForm.model')"
				  v-model="model"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-car-side"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
                                  :label="$t('vehicleForm.numberplate')"
				  v-model="numberplate"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-card-account-details"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
                                  :label="$t('vehicleForm.color')"
				  v-model="color"
				  prepend-inner-icon="mdi-palette"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-checkbox
				  v-model="stolen"
                                  :label="$t('vehicleForm.stolen')"
				  color="warning"
				  hide-details
				  class="mt-2"
				></v-checkbox>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-checkbox
				  v-model="wanted"
                                  :label="$t('vehicleForm.wanted')"
				  color="error"
				  hide-details
				  class="mt-2"
				></v-checkbox>
			  </v-col>
			  
			  <v-col cols="12">
				<v-text-field
                                  :label="$t('vehicleForm.registered')"
				  v-model="registered"
				  type="date"
				  prepend-inner-icon="mdi-calendar"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
		  </v-form>
		</v-window-item>
  
		<!-- Tab 2: Beschreibung -->
		<v-window-item value="2">
		  <div class="pa-6">
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('vehicleForm.vehicleDescriptionSection')}}</span>
			</div>
			
			<div class="editor-container">
			  <TiptapEditor
				v-model="text"
                                :placeholder="$t('vehicleForm.descriptionPlaceholder')"
				:show-character-count="false"
				:show-source-button="true"
				:editable="true"
				class="vehicle-editor"
			  />
			</div>
		  </div>
		</v-window-item>
	  </v-window>
  
	  <!-- Aktionsbuttons -->
	  <v-divider></v-divider>
	  
	  <v-card-actions class="pa-4">
		<div v-if="!isFormValid(vehicle)" class="error-message">
		  <v-icon size="small" class="mr-1" color="error">mdi-alert-circle</v-icon>
                  <span>{{$t('authorityComponents.common.formError')}}</span>
		</div>
		
		<v-spacer></v-spacer>
		
		<v-btn 
		  color="grey-darken-1" 
		  variant="text" 
		  @click="closeDialog"
		  class="action-button mr-2"
		>
                  {{$t('authorityComponents.common.cancel')}}
		</v-btn>
		
		<v-btn 
		  color="primary" 
		  variant="elevated" 
		  @click="updateVehicle"
		  :disabled="!isFormValid(vehicle)"
		  :loading="saving"
		  class="save-button"
		>
		  <v-icon class="mr-1">mdi-content-save</v-icon>
                  {{$t('authorityComponents.common.save')}}
		</v-btn>
	  </v-card-actions>
	</v-card>
  </template>
  
  <style scoped>
  /* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */
  
  /* Card Styling */
  .vehicle-edit-card {
	background: rgba(15, 23, 42, 0.6) !important;
	border: 1px solid var(--card-border);
	backdrop-filter: blur(10px);
	border-radius: 12px;
	overflow: hidden;
  }
  
  .card-toolbar {
	border-bottom: 1px solid var(--card-border);
  }
  
  /* Tabs Styling */
  .tab-bar {
	background: rgba(30, 41, 59, 0.4) !important;
	border-bottom: 1px solid var(--card-border);
  }
  
  .tab-item {
	text-transform: none;
	letter-spacing: normal;
	transition: all 0.2s ease;
  }
  
  /* Section Titles */
  .section-title {
	display: flex;
	align-items: center;
	color: #e2e8f0;
	padding-bottom: 8px;
	border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  /* Form Field Styling */
  .field-item {
	border-radius: 8px;
	transition: transform 0.2s ease;
  }
  
  .field-item:focus-within {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  /* Editor Container */
  .editor-container {
	background: rgba(30, 41, 59, 0.3);
	border-radius: 8px;
	border: 1px solid var(--card-border);
	overflow: hidden;
  }
  
  .vehicle-editor {
	min-height: 300px;
  }
  
  /* Error Message */
  .error-message {
	display: flex;
	align-items: center;
	color: var(--v-theme-error);
	font-size: 0.85rem;
  }
  
  /* Action Buttons */
  .action-button, .save-button {
	text-transform: none;
	letter-spacing: normal;
	font-weight: 500;
	transition: all 0.3s ease;
  }
  
  .action-button:hover {
	transform: translateY(-2px);
  }
  
  .save-button:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
  }
  
  /* Animation for Transitions */
  .v-enter-active,
  .v-leave-active {
	transition: opacity 0.3s ease;
  }
  
  .v-enter-from,
  .v-leave-to {
	opacity: 0;
  }
  </style>