<script setup lang="ts">
import { ref, computed, watch, defineAsyncComponent } from 'vue';
import type { VehicleFile } from '@/types/Vehicle';
import type { PersonFile } from '@/types/Person';
import { apiClientAuth } from '@/api';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

const AuthorityViewPersonFile = defineAsyncComponent(
    () => import('@/components/PersonFile/Authority/View.vue')
);

// --- Props ---
interface Props {
  modelValue?: boolean
  vehicleToView?: VehicleFile | null
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'close']);

const dialog = computed({
    get: () => props.modelValue,
    set: value => {
        emit('update:modelValue', value);
    },
});

// Create a default empty vehicle object to avoid null reference errors
const defaultVehicle: VehicleFile = {
    id: 0,
    brand: '',
    model: '',
    numberplate: '',
    color: '',
    stolen: false,
    wanted: false,
    registered: '',
    text: '',
    owners: [],
    drivers: [],
    is_deleted: false,
};

// Initialize vehicle with default values to prevent null access errors
const vehicle = ref<VehicleFile>(defaultVehicle);

const toast = useToast();
const { t } = useI18n();

const persons = ref<PersonFile[]>([]);
const personHeaders = [
    { title: t('personForm.name'), value: 'name' },
    { title: t('personForm.phone'), value: 'phonenumber' },
    { title: t('personForm.mail'), value: 'mail' },
    { title: t('authorityComponents.common.actions'), value: 'actions', sortable: false },
];

const selectedPerson = ref<PersonFile | null>(null);
const viewPersonDialog = ref(false);

const viewPersonComponent = computed(() => {
    return AuthorityViewPersonFile;
});

const currentTab = ref(0);

const fetchPersons = async () => {
    try {
        const response = await apiClientAuth.get('/vehiclefile/?action=getPersons');
        persons.value = response.data.map((person: PersonFile) => ({
            ...person,
            name: `${person.firstname} ${person.lastname}`,
        }));
        console.log('Persons loaded successfully:', persons.value.length);
    } catch (error) {
        toast.error(t('authorityComponents.common.loadError'));
    }
};

const viewPersonClose = () => {
    selectedPerson.value = null;
    viewPersonDialog.value = false;
};

// Update vehicle data when vehicleToView prop changes
watch(
    () => props.vehicleToView,
    newVehicle => {
        if (newVehicle) {
            // Parse owners and drivers from strings to numbers if needed
            const parsedOwners = Array.isArray(newVehicle.owners)
                ? newVehicle.owners.map(id => (typeof id === 'string' ? parseInt(id, 10) : id))
                : [];

            const parsedDrivers = Array.isArray(newVehicle.drivers)
                ? newVehicle.drivers.map(id => (typeof id === 'string' ? parseInt(id, 10) : id))
                : [];

            // Create a complete vehicle object with all required properties
            vehicle.value = {
                ...defaultVehicle,
                ...newVehicle,
                owners: parsedOwners,
                drivers: parsedDrivers,
            };
            console.log('Vehicle updated in watcher:', vehicle.value);
            fetchPersons();
        }
    },
    { immediate: true, deep: true }
);

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

const openPersonView = (person: PersonFile) => {
    selectedPerson.value = person;
    viewPersonDialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    emit('close');
};
</script>
<template>
    <v-dialog
        v-model="dialog"
        max-width="900"
        persistent
        transition="dialog-transition"
    >
        <!-- Fahrzeugdetails anzeigen -->
        <v-card v-if="vehicle" class="vehicle-card elevation-4">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-car</v-icon>
                {{ $t('authorityComponents.vehicleFile.viewTitle') }} - {{ vehicle.brand }} {{ vehicle.model }}
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <add-shortcut-button
                v-if="vehicle.id"
                type="vehicle"
                :resource-id="vehicle.id"
                :title="`${vehicle.brand} ${vehicle.model}`"
                :subtitle="vehicle.licensePlate || undefined"
                icon="mdi-car"
                color="deep-orange"
            />
            <v-chip
                v-if="vehicle.wanted"
                color="error"
                size="small"
                class="mr-2 ml-2"
                prepend-icon="mdi-alert-circle"
            >
                {{ $t('vehicleForm.wanted') }}
            </v-chip>
            <v-chip v-if="vehicle.stolen" color="warning" size="small" prepend-icon="mdi-car-off">
                {{ $t('vehicleForm.stolen') }}
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
                {{ $t('vehicleForm.infoTab') }}
            </v-tab>
            <v-tab value="2" class="tab-item">
                <v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
                {{ $t('vehicleForm.ownersDriversSection') }}
            </v-tab>
            <v-tab value="3" class="tab-item">
                <v-icon size="small" class="mr-2">mdi-text-box</v-icon>
                {{ $t('vehicleForm.descriptionTab') }}
            </v-tab>
        </v-tabs>

        <v-window v-model="currentTab" v-if="!viewPersonDialog">
            <!-- Tab 1: Fahrzeugdetails -->
            <v-window-item value="1">
                <v-form ref="form" class="pa-6">
                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                :label="$t('vehicleForm.brand')"
                                v-model="vehicle.brand"
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
                                :label="$t('vehicleForm.model')"
                                v-model="vehicle.model"
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
                                :label="$t('vehicleForm.numberplate')"
                                v-model="vehicle.numberplate"
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
                                :label="$t('vehicleForm.color')"
                                v-model="vehicle.color"
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
                                v-model="vehicle.stolen"
                                :label="$t('vehicleForm.stolen')"
                                color="warning"
                                hide-details
                                readonly
								bg-color="grey-darken-3"
                                class="mt-2"
                            ></v-checkbox>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <v-checkbox
                                v-model="vehicle.wanted"
                                :label="$t('vehicleForm.wanted')"
                                color="error"
                                hide-details
                                readonly
								bg-color="grey-darken-3"
                                class="mt-2"
                            ></v-checkbox>
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                :label="$t('vehicleForm.registered')"
                                v-model="vehicle.registered"
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
                        <span class="text-subtitle-1 font-weight-medium">Besitzer</span>
                    </div>

                    <v-card variant="outlined" class="mb-6 table-card" color="blue-grey-darken-3">
                        <v-data-table
                            :headers="personHeaders"
                            :items="getPersonsByIds(vehicle.owners || [])"
                            :items-per-page="5"
                            density="comfortable"
                            hover
                            class="person-table"
                        >
                            <template v-slot:[`item.actions`]="{ item }">
                                <v-tooltip :text="$t('vehicleView.showPerson')" location="top">
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
                                    <v-icon size="30" color="grey-darken-1" class="mb-2"
                                        >mdi-account-off</v-icon
                                    >
                                    <span>{{ $t('vehicleView.noOwners') }}</span>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>

                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-account-multiple</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">{{ $t('vehicleForm.drivers') }}</span>
                    </div>

                    <v-card variant="outlined" class="table-card" color="blue-grey-darken-3">
                        <v-data-table
                            :headers="personHeaders"
                            :items="getPersonsByIds(vehicle.drivers || [])"
                            :items-per-page="5"
                            density="comfortable"
                            hover
                            class="person-table"
                        >
                            <template v-slot:[`item.actions`]="{ item }">
                                <v-tooltip :text="$t('vehicleView.showPerson')" location="top">
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
                                    <v-icon size="30" color="grey-darken-1" class="mb-2"
                                        >mdi-account-off</v-icon
                                    >
                                    <span>{{ $t('vehicleView.noDrivers') }}</span>
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
                        <span class="text-subtitle-1 font-weight-medium">{{ $t('vehicleForm.vehicleDescriptionSection') }}</span>
                    </div>

                    <div v-if="vehicle.text" class="description-content">
                        <div v-html="vehicle.text"></div>
                    </div>

                    <div v-else class="empty-description">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-text-box-outline</v-icon
                        >
                        <span>{{ $t('vehicleView.noDescription') }}</span>
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
                {{ $t('memberCard.close') }}
            </v-btn>
        </v-card-actions>

        <!-- PersonView Component -->
        <component
            :is="viewPersonComponent"
            v-model="viewPersonDialog"
            :viewPersonDialog="viewPersonDialog"
            :personToView="selectedPerson"
            @close="viewPersonClose"
        />
    </v-card>
    </v-dialog>
</template>

<style scoped>

/* Card Styling */
.vehicle-card {
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

/* Form Field Styling */
.field-item {
    border-radius: 8px;
    transition: transform 0.2s ease;
}

.field-item:focus-within {
    transform: translateY(-2px);
}

/* Table Card */
.table-card {
    background: rgba(30, 41, 59, 0.3) !important;
    border-radius: 8px;
    overflow: hidden;
}

.person-table :deep(th) {
    background-color: rgba(30, 41, 59, 0.5) !important;
}

.person-table :deep(tr:hover) {
    background-color: var(--k-row-hover) !important;
}

/* Section Titles */
.section-title {
    display: flex;
    align-items: center;
    color: #e2e8f0;
}

/* Empty States */
.empty-state,
.empty-description {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: var(--k-ink-muted);
    text-align: center;
}

.description-content {
    background: rgba(30, 41, 59, 0.3);
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
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
}
</style>
