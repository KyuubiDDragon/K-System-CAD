<script setup lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue';
import type { VehicleFile } from "@/types/Vehicle";
import type { PersonFile } from "@/types/Person";
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

import TiptapEditor from '@/components/TiptapEditor.vue';
interface Props {
  modelValue: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(["update:modelValue", "vehicleAdded", 'close']); // Use consistent close event name

const dialog = computed({
			get: () => props.modelValue,
			set: (value) => {
				emit("update:modelValue", value);
			},
		});

		const vehicle = ref<VehicleFile>({
					id: 0,
					owners: [],
					drivers: [],
					brand: "",
					model: "",
					numberplate: "",
					color: "",
					stolen: false,
					wanted: false,
					registered: "",
					text: "",
					is_deleted: false,
				});
		
				const persons = ref<PersonFile[]>([]);
				const currentTab = ref(0);
				const saving = ref(false);
                                const toast = useToast();
                                const { t } = useI18n();

		const fetchPersons = async () => {
			try {
				const response = await apiClientAuth.get(
					"vehiclefile/?action=getPersons"
				);
				persons.value = response.data.map((person: PersonFile) => ({
					...person,
					fullname: `${person.firstname} ${person.lastname}`,
				}));
			} catch (error) {
                                toast.error(t('authorityComponents.common.loadError'));
			}
		};

                const requiredRule = (value: string) =>
                        !!value || t('authorityComponents.common.requiredField');
		const addNewVehicle = async () => {
			try {
				saving.value = true;
				await apiClientAuth.post("/vehiclefile/?action=addVehicle", vehicle.value);
				emit("vehicleAdded");
				closeDialog();
			} catch (error) {
                                toast.error(t('authorityComponents.vehicleFile.errorAdd'));
			} finally {
				saving.value = false;
			}
		};

		const isFormValid = (form: VehicleFile) => {
			return (
				form.brand !== "" &&
				form.model !== "" &&
				form.numberplate !== ""
			);
		};

		const closeDialog = () => {
			dialog.value = false;
			emit("close")
		};

		onMounted(fetchPersons);
</script>

<template>
	<!-- Neues Fahrzeug hinzufügen Formular -->
	<v-card 
	  v-if="dialog" 
	  class="vehicle-add-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
		<v-toolbar-title class="text-subtitle-1">
		  <v-icon start size="18" class="mr-2">mdi-car-plus</v-icon>
                  {{$t('authorityComponents.vehicleFile.addTitle')}}
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
				  v-model="vehicle.owners"
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
				></v-select>
			  </v-col>
			  
			  <v-col cols="12">
				<v-select
				  v-model="vehicle.drivers"
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
				></v-select>
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
				  v-model="vehicle.brand"
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
				  v-model="vehicle.model"
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
				  v-model="vehicle.numberplate"
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
				  v-model="vehicle.color"
				  prepend-inner-icon="mdi-palette"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-checkbox
				  v-model="vehicle.stolen"
                                  :label="$t('vehicleForm.stolen')"
				  color="warning"
				  hide-details
				  class="mt-2"
				></v-checkbox>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-checkbox
				  v-model="vehicle.wanted"
                                  :label="$t('vehicleForm.wanted')"
				  color="error"
				  hide-details
				  class="mt-2"
				></v-checkbox>
			  </v-col>
			  
			  <v-col cols="12">
				<v-text-field
                                  :label="$t('vehicleForm.registered')"
				  v-model="vehicle.registered"
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
				v-model="vehicle.text"
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
		  @click="addNewVehicle"
		  :disabled="!isFormValid(vehicle)"
		  :loading="saving"
		  class="add-button"
		>
		  <v-icon class="mr-1">mdi-car-plus</v-icon>
                {{$t('authorityComponents.common.add')}}
		</v-btn>
	  </v-card-actions>
	</v-card>
  </template>
  
  <style scoped>
  /* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */
  
  /* Card Styling */
  .vehicle-add-card {
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
  .action-button, .add-button {
	text-transform: none;
	letter-spacing: normal;
	font-weight: 500;
	transition: all 0.3s ease;
  }
  
  .action-button:hover {
	transform: translateY(-2px);
  }
  
  .add-button {
	background: linear-gradient(to right, #3b82f6, #60a5fa);
  }
  
  .add-button:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
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