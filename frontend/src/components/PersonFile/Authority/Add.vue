<script setup lang="ts">
import TiptapEditor from '@/components/TiptapEditor.vue';
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { PersonFile } from "@/types/Person";
import { apiClientAuth } from "@/api";
import { useAuthStore } from '@/stores/auth';
import AuthorityFieldsService, { type AuthorityField } from '@/services/AuthorityFieldsService';

interface Props {
  modelValue: boolean
  newPersonDialog: boolean
  addPersonDialog: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'personAdded', 'close']); // Renamed closeForm to close

const authStore = useAuthStore();
const authority = computed(() => authStore.user?.authority);
const { t } = useI18n();

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});

const saving = ref(false);
const authorityFields = ref<AuthorityField[]>([]);
const loading = ref(true);

// Lade die Feldkonfiguration
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

// Initialisiere das Person-Objekt mit Standardwerten
const initializePerson = () => {
    const personObj: any = {
        id: 0,
        firstname: "",
        lastname: "",
        birthplace: "",
        birthday: "",
        gender: "",
        title: "",
        phonenumber: "",
        address: "",
        idcard: "",
        bankaccount: "",
        mail: "",
        entry: "",
        licenses: "",
        wanted: false,
        text: "",
        is_deleted: false,
    };

    // Füge dynamische Felder hinzu
    authorityFields.value.forEach(field => {
        // Setze Standardwerte je nach Feldtyp
        if (field.field_type === 'boolean') {
            personObj[field.field_name] = false;
        } else if (field.field_type === 'number') {
            personObj[field.field_name] = 0;
        } else if (field.field_type === 'select' && field.options && field.options.length > 0) {
            personObj[field.field_name] = field.options[0].value;
        } else {
            personObj[field.field_name] = "";
        }
    });

    return personObj as PersonFile;
};

const person = ref<PersonFile>(initializePerson());

// Aktualisiere das Person-Objekt, wenn die Felder geladen wurden
watch(authorityFields, () => {
    person.value = initializePerson();
}, { immediate: false });

const genderOptions = computed(() => [
    { text: t('personForm.genderMale'), value: 'male' },
    { text: t('personForm.genderFemale'), value: 'female' },
    { text: t('personForm.genderOther'), value: 'diverse' },
]);

const currentTab = ref(0); 

// Define required field validation rule
const requiredRule = (value: any) => !!value || t('authorityComponents.common.requiredField');

const addNewPerson = async () => {
    saving.value = true;
    try {
        await apiClientAuth.post("/personfile/?action=addPerson", person.value);
        emit("personAdded");
        closeDialog();
    } catch (error) {
        console.error(t('authorityComponents.personFile.errorAdd'), error);
    } finally {
        saving.value = false;
    }
};

const isFormValid = (form: PersonFile) =>
    form.firstname !== "" && form.lastname !== "" && form.birthplace !== "" && form.birthday !== "";

const closeDialog = () => {
    dialog.value = false;
};
</script>

<template>
	<!-- Neue Person hinzufügen Formular -->
	<v-card 
	  v-if="addPersonDialog" 
	  class="person-add-card elevation-4"
	>
	  <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                  <v-icon start size="18" class="mr-2">mdi-account-plus</v-icon>
                  {{ $t('authorityComponents.personFile.addTitle') }}
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
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			</v-row>
			
                        <!-- Dokumenteninformationen -->
                        <div class="section-title mb-4 mt-4">
                          <v-icon size="small" class="mr-2">mdi-file-document</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.documentInfoSection')}}</span>
                        </div>
			
			<v-row>
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.idcard')"
                                  v-model="person.idcard"
				  prepend-inner-icon="mdi-card-account-details"
				  variant="outlined"
				  density="comfortable"
				  
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
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.licenses')"
                                  v-model="person.licenses"
				  prepend-inner-icon="mdi-license"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12" sm="6">
                                <v-text-field
                                  :label="$t('personForm.entry')"
                                  v-model="person.entry"
                                  type="datetime-local"
				  prepend-inner-icon="mdi-calendar-check"
				  variant="outlined"
				  density="comfortable"
				  
				  class="field-item"
				></v-text-field>
			  </v-col>
			  
			  <v-col cols="12">
                                <v-checkbox
                                  v-model="person.wanted"
                                  :label="$t('personForm.wanted')"
                                  color="error"
				  hide-details
				  class="mt-2"
				></v-checkbox>
			  </v-col>
			</v-row>
			
			<!-- Dynamischer Bereich für autorität-spezifische Felder -->
                        <div v-if="authorityFields.length > 0" class="section-title mb-4 mt-4">
                                <v-icon size="small" class="mr-2">mdi-shield-account</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">{{$t('authorityComponents.sections.authoritySpecific')}}</span>
                        </div>
			
			<v-row v-if="authorityFields.length > 0">
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
						class="field-item"
					></v-textarea>
				</v-col>
			</v-row>
		  </v-form>
		</v-window-item>
  
		<!-- Tab 2: Beschreibung -->
		<v-window-item value="2">
		  <div class="pa-6">
			<div class="section-title mb-4">
			  <v-icon size="small" class="mr-2">mdi-text-box-edit</v-icon>
                          <span class="text-subtitle-1 font-weight-medium">{{$t('personForm.descriptionSection')}}</span>
			</div>
			
			<div class="editor-container">
			  <TiptapEditor
				v-model="person.text"
				:show-character-count="false"
				:show-source-button="true"
				:editable="true"
                                :placeholder="$t('personForm.descriptionPlaceholder')"
				class="person-editor"
			  />
			</div>
		  </div>
		</v-window-item>
	  </v-window>
  
	  <!-- Aktionsbuttons -->
	  <v-divider></v-divider>
	  
	  <v-card-actions class="pa-4">
                <div v-if="!isFormValid(person)" class="error-message">
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
                  @click="addNewPerson"
                  :disabled="!isFormValid(person)"
                  :loading="saving"
                  class="add-button"
                >
                  <v-icon class="mr-1">mdi-account-plus</v-icon>
                  {{ $t('authorityComponents.common.add') }}
                </v-btn>
	  </v-card-actions>
	</v-card>
  </template>
  
  <style scoped>

  /* Card Styling */
  .person-add-card {
	background: var(--k-surface) !important;
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
	color: var(--k-ink);
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
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  /* Editor Container */
  .editor-container {
	background: var(--k-sunken);
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
	background: linear-gradient(to right, var(--k-accent), var(--k-accent));
  }
  
  .add-button:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 12px var(--k-accent-line);
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