<script setup lang="ts">
import { defineComponent, ref, computed, onMounted, watch, type PropType,  } from 'vue';
import { type ApartmentWithRelations } from "@/types/Apartment";
import { type PersonFile } from "@/types/Person";
import { apiClientAuth } from "@/api";
import AuthorityViewPersonFile from "@/components/PersonFile/Authority/View.vue";
import { useI18n } from 'vue-i18n';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

// --- Props ---
interface Props {
  modelValue?: boolean
  apartmentToView?: Record<string, any>
  viewApartmentDialog?: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(["update:modelValue", "close"]);

const dialog = computed({
	get: () => props.modelValue,
	set: (value) => {
		emit("update:modelValue", value);
	},
});

const apartment = ref<ApartmentWithRelations>(props.apartmentToView ? {
	...props.apartmentToView,
	owners: Array.isArray(props.apartmentToView.owners) 
		? props.apartmentToView.owners.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number)) 
		: [],
	tenants: Array.isArray(props.apartmentToView.tenants) 
		? props.apartmentToView.tenants.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
		: [],
	landlords: Array.isArray(props.apartmentToView.landlords) 
		? props.apartmentToView.landlords.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
		: [],
	subtenants: Array.isArray(props.apartmentToView.subtenants) 
		? props.apartmentToView.subtenants.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
		: [],
} : {} as ApartmentWithRelations);

const persons = ref<PersonFile[]>([]);
const personHeaders = [
	{ title: t('personForm.name'), value: "name" },
	{ title: t('personForm.phone'), value: "phonenumber" },
	{ title: t('personForm.mail'), value: "mail" },
	{ title: t('authorityComponents.common.actions'), value: "actions", sortable: false },
];

const selectedPerson = ref<PersonFile | null>(null);
const viewPersonDialog = ref(false);

const viewPersonComponent = computed(() => {
	return AuthorityViewPersonFile;
});

const fetchPersons = async () => {
	try {
		const response = await apiClientAuth.get(
			"/personfile/?action=getPersons"
		);
		persons.value = response.data.map((person: PersonFile) => ({
			...person,
			name: `${person.firstname} ${person.lastname}`,
		}));
	} catch (error) {
		console.error(t('authorityComponents.common.loadError'), error);
	}
};

onMounted(fetchPersons);

watch(
	() => props.apartmentToView,
	(newApartment) => {
		if (newApartment) {
			apartment.value = {
				...newApartment,
				owners: Array.isArray(newApartment.owners) 
					? newApartment.owners.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
					: [],
				tenants: Array.isArray(newApartment.tenants) 
					? newApartment.tenants.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
					: [],
				landlords: Array.isArray(newApartment.landlords) 
					? newApartment.landlords.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
					: [],
				subtenants: Array.isArray(newApartment.subtenants) 
					? newApartment.subtenants.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id as number))
					: [],
			};
			console.log('Apartment data processed:', apartment.value);
		}
	},
	{ immediate: true }
);

const getPersonsByIds = (ids: (number | string)[] | undefined) => {
	if (!ids || !Array.isArray(ids) || ids.length === 0) {
		return [];
	}
	
	// Convert all IDs to numbers for comparison
	const numericIds = ids.map(id => typeof id === 'string' ? parseInt(id, 10) : id);
	
	return persons.value.filter(person => 
		numericIds.includes(person.id)
	);
};

const openPersonView = (person: PersonFile) => {
	selectedPerson.value = person;
	viewPersonDialog.value = true;
};

const closeDialog = () => {
	dialog.value = false;
	emit("close");
};

const viewPersonClose = () => {
	selectedPerson.value = null;
	viewPersonDialog.value = false;
};

const currentTab = ref(0);
const { t } = useI18n();
</script>

<template>
	<!-- Wohnungsdetails anzeigen -->
	<v-card 
	  v-if="apartmentToView" 
	  class="apartment-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
		<v-toolbar-title class="text-subtitle-1">
		  <v-icon start size="18" class="mr-2">mdi-home</v-icon>
                  {{ $t('authorityComponents.apartmentFile.viewTitle') }} - {{ apartment.name }}
		</v-toolbar-title>
		<v-spacer></v-spacer>
		<add-shortcut-button
		  v-if="apartment.id"
		  type="apartment_file"
		  :resource-id="apartment.id"
		  :title="apartment.name"
		  :subtitle="`${apartment.street} ${apartment.housenumber}, ${apartment.location}`"
		  icon="mdi-home"
		  color="green"
		/>
		<v-chip
		  size="small"
		  color="info"
		  class="address-chip ml-2"
		>
		  {{ apartment.street }} {{ apartment.housenumber }}, {{ apartment.location }}
		</v-chip>
	  </v-toolbar>
  
	  <!-- Tabs für verschiedene Informationen -->
	  <v-tabs 
		v-model="currentTab" 
		bg-color="rgba(30, 41, 59, 0.4)" 
		slider-color="primary"
		density="comfortable"
		class="tab-bar"
		 v-if="!viewPersonDialog"
	  >
		<v-tab value="1" class="tab-item">
		  <v-icon size="small" class="mr-2">mdi-information-outline</v-icon>
		  {{ $t('authorityComponents.tabs.info') }}
		</v-tab>
		<v-tab value="2" class="tab-item">
		  <v-icon size="small" class="mr-2">mdi-account-group</v-icon>
		  {{ $t('apartmentForm.tenantsSubtenantsSection') }}
		</v-tab>
                <v-tab value="3" class="tab-item">
                  <v-icon size="small" class="mr-2">mdi-text-box</v-icon>
                  {{ $t('apartmentForm.descriptionTab') }}
                </v-tab>
	  </v-tabs>
  
	  <v-window v-model="currentTab" v-if="!viewPersonDialog">
		<!-- Tab 1: Details -->
		<v-window-item value="1">
		  <div class="pa-6">
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-home-city</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.apartmentInfoSection') }}</span>
			</div>
			
			<v-row>
			  <v-col cols="12" sm="6">
				<v-text-field
				  label="Name"
				  v-model="apartment.name"
				  prepend-inner-icon="mdi-home-variant"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  readonly
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
				  label="Ort"
				  v-model="apartment.location"
				  prepend-inner-icon="mdi-map-marker"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  readonly
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
				  label="Straße"
				  v-model="apartment.street"
				  prepend-inner-icon="mdi-road-variant"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  readonly
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
				  label="Hausnummer"
				  v-model="apartment.housenumber"
				  prepend-inner-icon="mdi-pound"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  readonly
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
				<v-text-field
				  label="Anzahl der Einheiten"
				  v-model="apartment.units"
				  prepend-inner-icon="mdi-door"
				  variant="outlined"
				  density="comfortable"
				  bg-color="grey-darken-3"
				  readonly
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
			<div class="mt-4 pa-4 address-map">
			  <div class="address-map-placeholder">
				<v-icon size="40" color="grey-darken-1" class="mb-2">mdi-map</v-icon>
				<div class="text-center">
				  <div class="text-body-2 font-weight-medium mb-1">Vollständige Adresse:</div>
				  <div class="text-body-2 text-grey">
					{{ apartment.street }} {{ apartment.housenumber }}<br>
					{{ apartment.location }}
				  </div>
				</div>
			  </div>
			</div>
		  </div>
		</v-window-item>
		
		<!-- Tab 2: Bewohner -->
		<v-window-item value="2">
		  <div class="pa-6">
			<!-- Besitzer -->
			<div class="section-title mb-3">
			  <v-icon size="small" class="mr-2">mdi-account-key</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.owners') }}</span>
			</div>
			
			<v-card 
			  variant="outlined" 
			  class="mb-6 table-card"
			  color="blue-grey-darken-3"
			>
			  <v-data-table
				:headers="personHeaders"
				:items="getPersonsByIds(apartment.owners)"
				density="comfortable"
				hover
				class="person-table"
			  >
				<template v-slot:[`item.actions`]="{ item }">
				  <v-tooltip text="Person anzeigen" location="top">
					<template v-slot:activator="{ props }">
					  <v-btn 
						icon 
						variant="text" 
						size="small" 
						@click="openPersonView(item)" 
						v-bind="props"
						class="action-icon"
					  >
						<v-icon size="small">mdi-eye</v-icon>
					  </v-btn>
					</template>
				  </v-tooltip>
				</template>
				
				<template v-slot:no-data>
				  <div class="empty-state">
					<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
					<span>Keine Besitzer eingetragen</span>
				  </div>
				</template>
			  </v-data-table>
			</v-card>
			
			<!-- Vermieter -->
			<div class="section-title mb-3 mt-6">
			  <v-icon size="small" class="mr-2">mdi-account-cash</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.landlords') }}</span>
			</div>
			
			<v-card 
			  variant="outlined" 
			  class="mb-6 table-card"
			  color="blue-grey-darken-3"
			>
			  <v-data-table
				:headers="personHeaders"
				:items="getPersonsByIds(apartment.landlords)"
				density="comfortable"
				hover
				class="person-table"
			  >
				<template v-slot:[`item.actions`]="{ item }">
				  <v-tooltip text="Person anzeigen" location="top">
					<template v-slot:activator="{ props }">
					  <v-btn 
						icon 
						variant="text" 
						size="small" 
						@click="openPersonView(item)" 
						v-bind="props"
						class="action-icon"
					  >
						<v-icon size="small">mdi-eye</v-icon>
					  </v-btn>
					</template>
				  </v-tooltip>
				</template>
				
				<template v-slot:no-data>
				  <div class="empty-state">
					<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
					<span>Keine Vermieter eingetragen</span>
				  </div>
				</template>
			  </v-data-table>
			</v-card>
			
			<!-- Mieter -->
			<div class="section-title mb-3 mt-6">
			  <v-icon size="small" class="mr-2">mdi-account-check</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.tenants') }}</span>
			</div>
			
			<v-card 
			  variant="outlined" 
			  class="mb-6 table-card"
			  color="blue-grey-darken-3"
			>
			  <v-data-table
				:headers="personHeaders"
				:items="getPersonsByIds(apartment.tenants)"
				density="comfortable"
				hover
				class="person-table"
			  >
				<template v-slot:[`item.actions`]="{ item }">
				  <v-tooltip text="Person anzeigen" location="top">
					<template v-slot:activator="{ props }">
					  <v-btn 
						icon 
						variant="text" 
						size="small" 
						@click="openPersonView(item)" 
						v-bind="props"
						class="action-icon"
					  >
						<v-icon size="small">mdi-eye</v-icon>
					  </v-btn>
					</template>
				  </v-tooltip>
				</template>
				
				<template v-slot:no-data>
				  <div class="empty-state">
					<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
					<span>Keine Mieter eingetragen</span>
				  </div>
				</template>
			  </v-data-table>
			</v-card>
			
			<!-- Untermieter -->
			<div class="section-title mb-3 mt-6">
			  <v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.subtenants') }}</span>
			</div>
			
			<v-card 
			  variant="outlined" 
			  class="mb-6 table-card"
			  color="blue-grey-darken-3"
			>
			  <v-data-table
				:headers="personHeaders"
				:items="getPersonsByIds(apartment.subtenants)"
				density="comfortable"
				hover
				class="person-table"
			  >
				<template v-slot:[`item.actions`]="{ item }">
				  <v-tooltip text="Person anzeigen" location="top">
					<template v-slot:activator="{ props }">
					  <v-btn 
						icon 
						variant="text" 
						size="small" 
						@click="openPersonView(item)" 
						v-bind="props"
						class="action-icon"
					  >
						<v-icon size="small">mdi-eye</v-icon>
					  </v-btn>
					</template>
				  </v-tooltip>
				</template>
				
				<template v-slot:no-data>
				  <div class="empty-state">
					<v-icon size="30" color="grey-darken-1" class="mb-2">mdi-account-off</v-icon>
					<span>Keine Untermieter eingetragen</span>
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
                          <span class="text-subtitle-1 font-weight-medium">{{ $t('apartmentForm.apartmentDescriptionSection') }}</span>
			</div>
			
			<div v-if="apartment.text" class="description-content">
			  <div v-html="apartment.text"></div>
			</div>
			
			<div v-else class="empty-description">
			  <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-text-box-outline</v-icon>
                          <span>{{ $t('noDescription') }}</span>
			</div>
		  </div>
		</v-window-item>
	  </v-window>
  
	  <!-- Aktionsbuttons -->
	  <v-divider v-if="!viewPersonDialog"></v-divider>
	  
	  <v-card-actions class="pa-4" v-if="!viewPersonDialog">
		<v-spacer></v-spacer>
		<v-btn 
		  color="primary" 
		  variant="elevated" 
		  prepend-icon="mdi-close" 
		  @click="closeDialog"
		  class="close-button"
		>
		  Schließen
		</v-btn>
	  </v-card-actions>
	</v-card>
  
	<!-- PersonView Component -->
	<component
	  :is="viewPersonComponent"
	  v-model="viewPersonDialog"
	  :viewPersonDialog="viewPersonDialog"
	  :personToView="selectedPerson"
	  @close="viewPersonClose"
	/>
  </template>
  
  <style scoped>

  /* Card Styling */
  .apartment-card {
	background: var(--k-surface) !important;
	border: 1px solid var(--card-border);
	backdrop-filter: blur(10px);
	border-radius: 12px;
	overflow: hidden;
  }
  
  .card-toolbar {
	border-bottom: 1px solid var(--card-border);
  }
  
  .address-chip {
	font-size: 0.75rem;
	font-weight: 500;
  }
  
  /* Tabs Styling */
  .tab-bar {
	background: var(--k-sunken) !important;
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
	border-bottom: 1px solid var(--k-line);
  }
  
  /* Form Field Styling */
  .field-item {
	border-radius: 8px;
	transition: transform 0.2s ease;
  }
  
  .field-item:focus-within {
	transform: translateY(-2px);
  }
  
  /* Address Map Placeholder */
  .address-map {
	border-radius: 8px;
	background: var(--k-sunken);
	border: 1px solid var(--card-border);
  }
  
  .address-map-placeholder {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-height: 150px;
	color: var(--k-ink-faint);
  }
  
  /* Table Card */
  .table-card {
	background: var(--k-sunken) !important;
	border-radius: 8px;
	overflow: hidden;
  }
  
  .person-table :deep(th) {
	background-color: var(--k-sunken) !important;
  }
  
  .person-table :deep(tr:hover) {
	background-color: var(--k-row-hover) !important;
  }
  
  /* Empty States */
  .empty-state, .empty-description {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 32px 16px;
	color: var(--k-ink-muted);
	text-align: center;
  }
  
  .description-content {
	background: var(--k-sunken);
	border-radius: 8px;
	padding: 16px;
	border: 1px solid var(--card-border);
	min-height: 200px;
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
  
  /* Close Button */
  .close-button {
	text-transform: none;
	letter-spacing: normal;
	font-weight: 500;
	transition: all 0.3s ease;
  }
  
  .close-button:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 12px var(--k-accent-weak);
  }
  </style>