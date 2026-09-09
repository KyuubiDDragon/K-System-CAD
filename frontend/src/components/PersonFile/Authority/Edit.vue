<script setup lang="ts">
import { defineComponent, ref, computed, watch, type PropType, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import type { PersonFile } from "@/types/Person";
import TiptapEditor from '@/components/TiptapEditor.vue';
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import AuthorityFieldsService, { type AuthorityField } from '@/services/AuthorityFieldsService';

interface Props {
  modelValue?: boolean
  personToEdit?: PersonFile | null
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(["update:modelValue", "personUpdated", 'close']);

const authStore = useAuthStore();
const authority = computed(() => authStore.user?.authority);
const { t } = useI18n();

// Dynamische Felder aus der Datenbank laden
const authorityFields = ref<AuthorityField[]>([]);
const loading = ref(true);

onMounted(async () => {
  try {
    loading.value = true;
    authorityFields.value = await AuthorityFieldsService.getFields();
  } catch (error) {
    console.error(t('authorityComponents.common.loadError'), error);
  } finally {
    loading.value = false;
  }
});

// --- Computed Properties ---
const hasAuthoritySpecificFields = computed(() => authorityFields.value.length > 0);

const formHasErrors = computed(() => {
  return !isFormValid(person.value);
});

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => {
	emit("update:modelValue", value);
  },
});

// --- Component State ---
const person = ref<PersonFile>({ ...(props.personToEdit as PersonFile) });
const currentTab = ref(0);
const saving = ref(false);

// Für besseres Debugging
const isPersonDataLoaded = computed(() => {
  console.log("Current person data:", JSON.stringify(person.value, null, 2));
  return Object.keys(person.value).length > 0;
});

// Prüfen, ob benutzerdefinierte Felder vorhanden sind
const hasCustomFieldValues = computed(() => {
  if (!authorityFields.value || authorityFields.value.length === 0) return false;
  
  let hasAnyValue = false;
  authorityFields.value.forEach(field => {
    const value = person.value[field.field_name];
    console.log(`Field ${field.field_name}:`, value);
    if (value !== undefined && value !== null && value !== '') {
      hasAnyValue = true;
    }
  });
  
  return hasAnyValue;
});

watch(
  () => props.personToEdit,
  (newVal) => {
    if (newVal) {
      console.log('Raw personToEdit prop:', JSON.stringify(newVal));
      
      // Kopiere die Daten in das reaktive Objekt
      person.value = { ...newVal } as PersonFile;
      
      console.log('Copied person data:', JSON.stringify(person.value));
      console.log('Authority fields available:', authorityFields.value);
    }
  },
  { immediate: true }
);

// Also watch authority fields to sync with form data
watch(
  () => authorityFields.value,
  (newFields) => {
    if (newFields.length > 0 && person.value) {
      console.log('Authority fields loaded, checking if person has values for them');
      // Check if we need to initialize any missing fields to avoid reactivity issues
      newFields.forEach(field => {
        if (person.value[field.field_name] === undefined) {
          console.log(`Initializing missing field: ${field.field_name}`);
          if (field.field_type === 'boolean') {
            person.value[field.field_name] = false;
          } else if (field.field_type === 'number') {
            person.value[field.field_name] = 0;
          } else if (field.field_type === 'select' && field.options && field.options.length > 0) {
            person.value[field.field_name] = field.options[0].value;
          } else {
            person.value[field.field_name] = '';
          }
        } else {
          console.log(`Field ${field.field_name} already has value:`, person.value[field.field_name]);
        }
      });
    }
  }
);

// --- Form Validation ---
const requiredRule = (value: string) => !!value || t('authorityComponents.common.requiredField');

// --- Methods ---
const updatePerson = async () => {
  saving.value = true;
  try {
	await apiClientAuth.post("/personfile/?action=editPerson", person.value);
	emit("personUpdated");
	closeDialog();
  } catch (error) {
        console.error(t('authorityComponents.personFile.errorUpdate'), error);
  } finally {
	saving.value = false;
  }
};

const isFormValid = (form: PersonFile) => 
  form.firstname !== "" && 
  form.lastname !== "" && 
  form.birthplace !== "" && 
  form.birthday !== "";

const closeDialog = () => {
  dialog.value = false;
  emit("close");
};

// --- Form Options ---
const genderOptions = computed(() => [
  { text: t('personForm.genderMale'), value: 'male' },
  { text: t('personForm.genderFemale'), value: 'female' },
  { text: t('personForm.genderOther'), value: 'diverse' },
]);
</script>
<template>
	<!-- Personen bearbeiten Formular -->
	<v-card 
	  v-if="personToEdit" 
	  class="person-edit-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
		<v-toolbar-title class="text-subtitle-1">
                  <v-icon start size="18" class="mr-2">mdi-account-edit</v-icon>
                  {{ $t('authorityComponents.personFile.editTitle') }} - {{ person.firstname }} {{ person.lastname }}
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
                  <v-icon size="small" class="mr-2">mdi-account-details</v-icon>
                  {{ $t('authorityComponents.tabs.info') }}
                </v-tab>
                <v-tab value="2" class="tab-item">
                  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                  {{ $t('authorityComponents.tabs.description') }}
                </v-tab>
	  </v-tabs>
  
	  <v-window v-model="currentTab">
		<!-- Tab 1: Personeninformationen -->
		<v-window-item value="1">
		  <v-form ref="form" class="pa-6">
                        <!-- Persönliche Informationen -->
                        <div class="section-title mb-4">
                          <v-icon size="small" class="mr-2">mdi-account</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.personalInfoSection')}}</span>
                        </div>
			
			<v-row>
			  <v-col cols="12" sm="2">
                                <v-select
                                  :label="$t('personForm.gender')"
                                  v-model="person.gender"
				  :items="genderOptions"
				  item-value="value"
				  item-title="text"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-gender-male-female"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-select>
			  </v-col>
			  
			  <v-col cols="12" sm="2">
                                <v-text-field
                                  :label="$t('personForm.title')"
                                  v-model="person.title"
				  prepend-inner-icon="mdi-format-title"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="4">
                                <v-text-field
                                  :label="$t('personForm.firstname')"
                                  v-model="person.firstname"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-account"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="4">
                                <v-text-field
                                  :label="$t('personForm.lastname')"
                                  v-model="person.lastname"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-account-box"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.birthday')"
                                  v-model="person.birthday"
				  type="date"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-calendar"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.birthplace')"
                                  v-model="person.birthplace"
				  required
				  :rules="[requiredRule]"
				  prepend-inner-icon="mdi-map-marker"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
                        <!-- Kontaktinformationen -->
                        <div class="section-title mb-4 mt-4">
                          <v-icon size="small" class="mr-2">mdi-contacts</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.contactInfoSection')}}</span>
                        </div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.phone')"
                                  v-model="person.phonenumber"
				  prepend-inner-icon="mdi-phone"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.mail')"
                                  v-model="person.mail"
				  prepend-inner-icon="mdi-email"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12">
                                <v-text-field
                                  :label="$t('personForm.address')"
                                  v-model="person.address"
				  prepend-inner-icon="mdi-home"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
                        <!-- Identitätsinformationen -->
                        <div class="section-title mb-4 mt-4">
                          <v-icon size="small" class="mr-2">mdi-card-account-details</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.documentInfoSection')}}</span>
                        </div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.idcard')"
                                  v-model="person.idcard"
				  prepend-inner-icon="mdi-card-account-details-outline"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.bankaccount')"
                                  v-model="person.bankaccount"
				  prepend-inner-icon="mdi-bank"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
                        <!-- Zusätzliche Informationen -->
                        <div class="section-title mb-4 mt-4">
                          <v-icon size="small" class="mr-2">mdi-information</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.additionalInfoSection')}}</span>
                        </div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.entry')"
                                  v-model="person.entry"
				  type="date"
				  prepend-inner-icon="mdi-airplane-landing"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.licenses')"
                                  v-model="person.licenses"
				  prepend-inner-icon="mdi-certificate"
				  variant="outlined"
				  density="comfortable"
				  color="primary"
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12">
                                <v-checkbox
                                  :label="$t('personForm.wanted')"
                                  v-model="person.wanted"
				  color="error"
				  hide-details
				  dense
				  class="field-item mt-2"
				></v-checkbox>
			  </v-col>
			</v-row>
			
			<!-- Dynamischer Bereich für autorität-spezifische Felder -->
                        <div v-if="hasAuthoritySpecificFields" class="section-title mb-4 mt-4">
                                <v-icon size="small" class="mr-2">mdi-shield-account</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">{{$t('authorityComponents.sections.authoritySpecific')}}</span>
                        </div>
			
			<v-row v-if="hasAuthoritySpecificFields">
				<v-col 
					v-for="field in authorityFields" 
					:key="field.id"
					cols="12" 
					:sm="field.field_type === 'textarea' ? 12 : 6"
				>
					<!-- Text Field -->
					<v-text-field
						v-if="field.field_type === 'text'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-text-field>
					
					<!-- Number Field -->
					<v-text-field
						v-else-if="field.field_type === 'number'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						type="number"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-text-field>
					
					<!-- Date Field -->
					<v-text-field
						v-else-if="field.field_type === 'date'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						type="date"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-text-field>
					
					<!-- Boolean Field -->
					<v-checkbox
						v-else-if="field.field_type === 'boolean'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						:required="field.required"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-checkbox>
					
					<!-- Select Field -->
					<v-select
						v-else-if="field.field_type === 'select'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						:items="field.options || []"
						item-title="text"
						item-value="value"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-select>
					
					<!-- Multiselect Field -->
					<v-select
						v-else-if="field.field_type === 'multiselect'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						:items="field.options || []"
						item-title="text"
						item-value="value"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						multiple
						chips
						class="field-item"
					></v-select>
					
					<!-- Textarea Field -->
					<v-textarea
						v-else-if="field.field_type === 'textarea'"
						:label="field.display_name"
						v-model="person[field.field_name]"
						:required="field.required"
						:rules="field.required ? [requiredRule] : []"
						variant="outlined"
						density="comfortable"
						color="primary"
						class="field-item"
					></v-textarea>
				</v-col>
			</v-row>
		  </v-form>
		</v-window-item>
  
                <!-- Tab 2: Personenbeschreibung -->
                <v-window-item value="2">
                  <div class="pa-6">
                        <div class="section-title mb-4">
                          <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.descriptionSection')}}</span>
                        </div>
			
			<TiptapEditor v-model="person.text" />
		  </div>
		</v-window-item>
	  </v-window>
	  
	  <v-divider></v-divider>
	  
	  <v-card-actions>
		<v-spacer></v-spacer>
                <v-btn text @click="closeDialog" class="mr-2">
                  {{ $t('authorityComponents.common.cancel') }}
                </v-btn>
		<v-btn 
		  color="primary" 
		  @click="updatePerson" 
		  :loading="saving"
		  :disabled="formHasErrors"
		>
                  <v-icon start size="small">mdi-content-save</v-icon>
                  {{ $t('authorityComponents.common.save') }}
                </v-btn>
	  </v-card-actions>
	</v-card>
  </template>

  <style scoped>
  /* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */
  
  /* Card Styling */
  .person-edit-card {
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
  
  .person-editor {
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