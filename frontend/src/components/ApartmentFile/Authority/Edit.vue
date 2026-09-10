<script setup lang="ts">
import { defineComponent, ref, computed, onMounted, watch, type PropType } from 'vue';
import { type ApartmentWithRelations } from '@/types/Apartment'; // Verwende den richtigen Typ
import { type PersonFile } from '@/types/Person';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { apiClientAuth } from '@/api';
import { useI18n } from 'vue-i18n';

interface Props {
  modelValue?: boolean
  apartmentToEdit?: Record<string, any>
  editApartmentDialog?: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'apartmentUpdated', 'close']);

const dialog = computed({
    get: () => props.modelValue,
    set: value => {
        emit('update:modelValue', value);
    },
});

const apartment = ref<ApartmentWithRelations>(
    props.apartmentToEdit
        ? {
              ...props.apartmentToEdit,
              owners: props.apartmentToEdit.owners?.map(Number) ?? [],
              tenants: props.apartmentToEdit.tenants?.map(Number) ?? [],
              landlords: props.apartmentToEdit.landlords?.map(Number) ?? [],
              subtenants: props.apartmentToEdit.subtenants?.map(Number) ?? [],
          }
        : ({} as ApartmentWithRelations)
);

const persons = ref<PersonFile[]>([]);
const currentTab = ref(0);
const saving = ref(false);
const { t } = useI18n();

const fetchPersons = async () => {
    try {
        const response = await apiClientAuth.get('/personfile/?action=getPersons');
        persons.value = response.data.map((person: PersonFile) => ({
            ...person,
            id: Number(person.id), // Stelle sicher, dass die ID als Zahl gespeichert ist
            fullname: `${person.firstname} ${person.lastname}`,
        }));
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

watch(
    () => props.apartmentToEdit,
    newVal => {
        if (newVal) {
            apartment.value = {
                ...newVal,
                owners: newVal.owners?.map(Number) ?? [],
                tenants: newVal.tenants?.map(Number) ?? [],
                landlords: newVal.landlords?.map(Number) ?? [],
                subtenants: newVal.subtenants?.map(Number) ?? [],
            };
        }
    },
    { immediate: true }
);

const requiredRule = (value: string) => !!value || t('authorityComponents.common.requiredField');
const updateApartment = async () => {
    saving.value = true;
    try {
        const payload = {
            ...apartment.value,
            owners: apartment.value.owners,
            tenants: apartment.value.tenants,
            landlords: apartment.value.landlords,
            subtenants: apartment.value.subtenants,
        };
        await apiClientAuth.post('/apartmentfile/?action=editApartment', payload);
        emit('apartmentUpdated');
        closeDialog();
    } catch (error) {
        console.error(t('authorityComponents.apartmentFile.errorUpdate'), error);
    } finally {
        saving.value = false;
    }
};

const isFormValid = (form: ApartmentWithRelations) => {
    return (
        form.name !== '' && form.location !== '' && form.street !== '' && form.housenumber !== ''
    );
};

const closeDialog = () => {
    dialog.value = false;
    emit('close');
};

onMounted(fetchPersons);
</script>

<template>
    <!-- Wohnung bearbeiten Formular -->
    <v-card v-if="apartmentToEdit" class="apartment-edit-card elevation-4">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-home-edit</v-icon>
                {{ $t('authorityComponents.apartmentFile.editTitle') }} - {{ apartment.name }}
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
                        <span class="text-subtitle-1 font-weight-medium"
                            >Wohnungsinformationen</span
                        >
                    </div>

                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                label="Wohnungsname"
                                v-model="apartment.name"
                                required
                                :rules="[requiredRule]"
                                prepend-inner-icon="mdi-home-variant"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12" sm="5">
                            <v-text-field
                                label="Ort"
                                v-model="apartment.location"
                                required
                                :rules="[requiredRule]"
                                prepend-inner-icon="mdi-map-marker"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" sm="5">
                            <v-text-field
                                label="Straße"
                                v-model="apartment.street"
                                required
                                :rules="[requiredRule]"
                                prepend-inner-icon="mdi-road-variant"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" sm="2">
                            <v-text-field
                                label="Hausnummer"
                                v-model="apartment.housenumber"
                                required
                                :rules="[requiredRule]"
                                prepend-inner-icon="mdi-pound"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                label="Anzahl der Einheiten"
                                v-model="apartment.units"
                                type="number"
                                required
                                :rules="[requiredRule]"
                                prepend-inner-icon="mdi-home-group"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <!-- Besitzer und Vermieter -->
                    <div class="section-title mb-4 mt-4">
                        <v-icon size="small" class="mr-2">mdi-account-key</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">Besitzer & Vermieter</span>
                    </div>

                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-select
                                label="Besitzer"
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
                                class="field-item"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <v-select
                                label="Vermieter"
                                v-model="apartment.landlords"
                                :items="persons"
                                item-title="fullname"
                                item-value="id"
                                multiple
                                chips
                                prepend-inner-icon="mdi-account-cash"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-select>
                        </v-col>
                    </v-row>

                    <!-- Mieter und Untermieter -->
                    <div class="section-title mb-4 mt-4">
                        <v-icon size="small" class="mr-2">mdi-account-group</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">Mieter & Untermieter</span>
                    </div>

                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-select
                                label="Mieter"
                                v-model="apartment.tenants"
                                :items="persons"
                                item-title="fullname"
                                item-value="id"
                                multiple
                                chips
                                prepend-inner-icon="mdi-account-check"
                                variant="outlined"
                                density="comfortable"
                                class="field-item"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <v-select
                                label="Untermieter"
                                v-model="apartment.subtenants"
                                :items="persons"
                                item-title="fullname"
                                item-value="id"
                                multiple
                                chips
                                prepend-inner-icon="mdi-account-multiple"
                                variant="outlined"
                                density="comfortable"
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
                        <span class="text-subtitle-1 font-weight-medium">Wohnungsbeschreibung</span>
                    </div>

                    <div class="editor-container">
                        <TiptapEditor
                            v-model="apartment.text"
                            placeholder="Geben Sie hier Notizen oder zusätzliche Informationen zur Wohnung ein..."
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
                @click="updateApartment"
                :disabled="!isFormValid(apartment)"
                :loading="saving"
                class="save-button"
            >
                <v-icon class="mr-1">mdi-content-save</v-icon>
                {{ $t('authorityComponents.common.save') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>

/* Card Styling */
.apartment-edit-card {
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
.action-button,
.save-button {
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
    box-shadow: 0 6px 12px var(--k-accent-weak);
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
