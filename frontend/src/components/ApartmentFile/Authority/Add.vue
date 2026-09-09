<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { type Apartment } from "@/types/Apartment";
import { type PersonFile } from "@/types/Person";
import { apiClientAuth } from "@/api";
import TiptapEditor from "@/components/TiptapEditor.vue";
import { useI18n } from 'vue-i18n';

const saving = ref(false);
const { t } = useI18n();

// Props definieren
const props = defineProps<{
	modelValue: boolean;
}>();

// Emits definieren
const emit = defineEmits<{
	(e: "update:modelValue", value: boolean): void;
	(e: "apartmentAdded"): void;
	(e: "close"): void;
}>();

const dialog = computed({
	get: () => props.modelValue,
	set: (value) => {
		emit("update:modelValue", value);
	},
});

const addNewApartment = async () => {
    try {
		saving.value = true;
        await apiClientAuth.post( // <- Geändert
            "/apartmentfile/?action=addApartment",
            apartment.value
        );
        emit("apartmentAdded");
        closeDialog();
    } catch (error) {
        console.error(t('authorityComponents.apartmentFile.errorAdd'), error);
    } finally {
		saving.value = false;
	}
};

const fetchPersons = async () => {
    try {
        const response = await apiClientAuth.get("/personfile/?action=getPersons"); // <- Geändert
		persons.value = response.data.map((person: PersonFile) => ({
			...person,
			fullname: `${person.firstname} ${person.lastname}`,
		}));
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const apartment = ref<Apartment>({
	id: 0,
	name: "",
	location: "",
	street: "",
	housenumber: "",
	units: 0,
	text: "",
	owners: [],
	tenants: [],
	landlords: [],
	subtenants: [],
	bought: false,
	rented: false,
});

const persons = ref<PersonFile[]>([]);
const currentTab = ref(0);

const requiredRule = (value: string) =>
        !!value || t('authorityComponents.common.requiredField');

const isFormValid = (form: Apartment) => {
	return (
		form.name !== "" &&
		form.location !== "" &&
		form.street !== "" &&
		form.housenumber !== ""
	);
};

const closeDialog = () => {
	dialog.value = false;
	emit("close");
};

onMounted(fetchPersons);
</script>

<template>
	<!-- Neue Wohnung hinzufügen Formular -->
	<v-card 
	  v-if="modelValue" 
	  class="apartment-add-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                  <v-icon start size="18" class="mr-2">mdi-home-plus</v-icon>
                  {{ $t('authorityComponents.apartmentFile.addTitle') }}
                </v-toolbar-title>
	  </v-toolbar>
  
	  <!-- Tabs für Wohnungsinformationen -->
	  <v-tabs 
		v-model="currentTab" 
		bg-color="rgba(30, 41, 59, 0.4)" 
		slider-color="primary"
		density="comfortable"
		class="tab-bar"
	  >
                <v-tab value="1" class="tab-item">
                  <v-icon size="small" class="mr-2">mdi-home-city</v-icon>
                  {{ $t('authorityComponents.tabs.info') }}
                </v-tab>
                <v-tab value="2" class="tab-item">
                  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                  {{ $t('authorityComponents.tabs.description') }}
                </v-tab>
	  </v-tabs>
  
	  <v-window v-model="currentTab">
		<!-- Tab 1: Wohnungsdetails -->
		<v-window-item value="1">
		  <v-form ref="form" class="pa-6">
			<!-- Allgemeine Informationen -->
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-home</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.apartmentInfoSection') }}</span>
			</div>
			
			<v-row>
			  <v-col cols="12">
                                <v-text-field
                                  :label="$t('apartmentForm.apartmentName')"
                                  v-model="apartment.name"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-home-variant"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
			<v-row>
			  <v-col cols="12" sm="5">
                                <v-text-field
                                  :label="$t('apartmentForm.location')"
                                  v-model="apartment.location"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-map-marker"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="5">
                                <v-text-field
                                  :label="$t('apartmentForm.street')"
                                  v-model="apartment.street"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-road-variant"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="2">
                                <v-text-field
                                  :label="$t('apartmentForm.houseNumber')"
                                  v-model="apartment.housenumber"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-pound"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
			<v-row>
			  <v-col cols="12">
                                <v-text-field
                                  :label="$t('apartmentForm.units')"
                                  v-model="apartment.units"
				  type="number"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-home-group"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
			<!-- Besitzer und Vermieter -->
			<div class="section-title mb-4 mt-4">
			  <v-icon size="small" class="mr-2">mdi-account-key</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.ownersLandlordsSection') }}</span>
			</div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-select
                                  :label="$t('apartmentForm.owners')"
                                  v-model="apartment.owners"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
				  required
				  multiple
				  chips
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-account-key"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-select>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-select
                                  :label="$t('apartmentForm.landlords')"
                                  v-model="apartment.landlords"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
				  multiple
				  chips
				  prepend-inner-icon="mdi-account-cash"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-select>
			  </v-col>
			</v-row>
			
			<!-- Mieter und Untermieter -->
			<div class="section-title mb-4 mt-4">
			  <v-icon size="small" class="mr-2">mdi-account-group</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.tenantsSubtenantsSection') }}</span>
			</div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-select
                                  :label="$t('apartmentForm.tenants')"
                                  v-model="apartment.tenants"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
				  multiple
				  chips
				  prepend-inner-icon="mdi-account-check"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-select>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-select
                                  :label="$t('apartmentForm.subtenants')"
                                  v-model="apartment.subtenants"
				  :items="persons"
				  item-title="fullname"
				  item-value="id"
				  multiple
				  chips
				  prepend-inner-icon="mdi-account-multiple"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  class="field-item"
				></v-select>
			  </v-col>
			</v-row>
		  </v-form>
		</v-window-item>
  
		<!-- Tab 2: Beschreibung -->
		<v-window-item value="2">
		  <div class="pa-6">
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.apartmentDescriptionSection') }}</span>
			</div>
			
			<div class="editor-container">
			  <TiptapEditor
				v-model="apartment.text"
                                :placeholder="$t('apartmentForm.descriptionPlaceholder')"
				:show-character-count="false"
				:show-source-button="true"
				:editable="true"
				class="apartment-editor"
			  />
			</div>
		  </div>
		</v-window-item>
	  </v-window>
  
	  <!-- Aktionsbuttons -->
	  <v-divider></v-divider>
	  
	  <v-card-actions class="pa-4">
                <div v-if="!isFormValid(apartment)" class="error-message">
                  <v-icon size="small" class="mr-1" color="error">mdi-alert-circle</v-icon>
                  <span>{{ $t('authorityComponents.common.formError') }}</span>
                </div>
		
		<v-spacer></v-spacer>
		
		<v-btn 
		  color="grey-darken-1" 
		  variant="text" 
		  @click="closeDialog"
		  class="action-button mr-2"
		>
		  {{ $t('authorityComponents.common.cancel') }}
		</v-btn>
		
		<v-btn 
		  color="primary" 
		  variant="elevated" 
		  @click="addNewApartment"
		  :disabled="!isFormValid(apartment)"
		  :loading="saving"
		  class="add-button"
		>
		  <v-icon class="mr-1">mdi-home-plus</v-icon>
		  {{ $t('authorityComponents.common.add') }}
		</v-btn>
	  </v-card-actions>
	</v-card>
  </template>
  
  <style scoped>
  :root {
	--card-bg: #1f2937;
	--card-border: rgba(255, 255, 255, 0.08);
	--hover-bg: rgba(255, 255, 255, 0.05);
  }
  
  /* Card Styling */
  .apartment-add-card {
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
  
  .apartment-editor {
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