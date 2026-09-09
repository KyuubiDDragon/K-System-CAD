<script setup lang="ts">
import { defineAsyncComponent, ref, computed, onMounted, watch, type PropType } from 'vue';
import { useI18n } from 'vue-i18n';
import type { PersonFile } from '@/types/Person';
import type { VehicleFile } from '@/types/Vehicle';
import type { ApartmentPersonRelation, ApartmentWithRelations } from '@/types/Apartment';
import apiAuthClient from '@/api';
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import AuthorityFieldsService, { type AuthorityField as BaseAuthorityField, type FieldOption } from '@/services/AuthorityFieldsService';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

// Extended AuthorityField type to include 'multiselect'
type AuthorityField = BaseAuthorityField & {
    field_type: 'text' | 'number' | 'date' | 'boolean' | 'select' | 'textarea' | 'multiselect';
};

// Import authority-specific components
// --- Async Component Imports ---
const AuthorityViewVehicleFile = defineAsyncComponent(
    () => import('@/components/VehicleFile/Authority/View.vue')
); // Adjust path
const AuthorityViewApartmentFile = defineAsyncComponent(
    () => import('@/components/ApartmentFile/Authority/View.vue')
); // Adjust path

// --- Props ---
interface Props {
  modelValue?: boolean
  personToView?: PersonFile | null
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'close']);

const authStore = useAuthStore();
const authority = computed(() => authStore.user?.authority);
const { t } = useI18n();

const isVisible = computed(() => {
    console.log('📊 Visibility check, modelValue:', props.modelValue);
    return !!props.modelValue;
});

const person = ref<PersonFile>(props.personToView ? { ...props.personToView } : ({} as PersonFile));

// Watch for changes in personToView prop
watch(() => props.personToView, (newPerson) => {
    console.log('🔄 personToView prop changed:', newPerson);
    if (newPerson) {
        person.value = { ...newPerson };
        // Fetch related data when person changes
        fetchLinkedReports();
        fetchVehicles();
        fetchApartments();
        fetchAuthorityFields();
    }
}, { immediate: true });

// Watch for modelValue changes
watch(() => props.modelValue, (newValue) => {
    console.log('🔄 modelValue changed in View component:', newValue);
});
const linkedReports = ref<any[]>([]);
const vehicles = ref<VehicleFile[]>([]);
const apartments = ref<ApartmentWithRelations[]>([]);
const viewVehicleDialog = ref(false);
const selectedVehicle = ref<VehicleFile | null>(null);
const viewApartmentDialog = ref(false);
const selectedApartment = ref<ApartmentWithRelations | null>(null);
const currentTab = ref(0);

// Für benutzerdefinierte Felder
const authorityFields = ref<AuthorityField[]>([]);
const customFieldsLoading = ref(true);

const vehicleHeaders = [
    { title: t('vehicleForm.brand'), value: 'brand' },
    { title: t('vehicleForm.model'), value: 'model' },
    { title: t('vehicleForm.numberplate'), value: 'numberplate' },
    { title: t('vehicleForm.color'), value: 'color' },
    { title: t('vehicleForm.registered'), value: 'registered' },
    { title: t('authorityComponents.common.role'), value: 'role' },
    { title: t('authorityComponents.common.actions'), value: 'actions', sortable: false },
];

const apartmentHeaders = [
    { title: t('apartmentForm.name'), value: 'name' },
    { title: t('apartmentForm.location'), value: 'location' },
    { title: t('apartmentForm.street'), value: 'street' },
    { title: t('apartmentForm.housenumber'), value: 'housenumber' },
    { title: t('authorityComponents.common.role'), value: 'role' },
    { title: t('authorityComponents.common.actions'), value: 'actions', sortable: false },
];

const viewVehicleComponent = computed(() => {
    return AuthorityViewVehicleFile;
});

const viewApartmentComponent = computed(() => {
    return AuthorityViewApartmentFile;
});

const fetchVehicles = async () => {
    try {
        // Only proceed if person exists and has an id
        if (!person.value || !person.value.id) {
            console.warn('Cannot fetch vehicles: No valid person ID');
            return;
        }

        console.log('Fetching vehicles for person:', person.value.id);
        const response = await apiAuthClient.get(
            `vehiclefile/?action=getVehiclesByPerson&personId=${person.value.id}`
        );
        vehicles.value = response.data.map((vehicle: VehicleFile) => {
            // Determine if the current person is an owner of this vehicle
            const isOwner = vehicle.owners?.includes(person.value.id);
            // Set appropriate role
            return {
                ...vehicle,
                role: isOwner ? 'Besitzer' : 'Fahrer',
                stolen:
                    typeof vehicle.stolen === 'number' ? Boolean(vehicle.stolen) : vehicle.stolen,
                wanted:
                    typeof vehicle.wanted === 'number' ? Boolean(vehicle.wanted) : vehicle.wanted,
            };
        });
        console.log('Loaded vehicles:', vehicles.value);
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const fetchApartments = async () => {
    try {
        // Only proceed if person exists and has an id
        if (!person.value || !person.value.id) {
            console.warn('Cannot fetch apartments: No valid person ID');
            return;
        }

        const response = await apiAuthClient.get(
            `apartmentfile/?action=getApartmentsByPerson&personId=${person.value.id}`
        );
        apartments.value = response.data.map((apartment: ApartmentWithRelations) => ({
            ...apartment,
            // Use the relation type if available, otherwise fallback to the directly assigned type
            role: getRoleFromType(apartment.type || ''),
        }));
        console.log('Mapped apartments:', apartments.value);
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

// Helper function to convert type to role text
function getRoleFromType(type: string): string {
    switch (type) {
        case 'owner':
            return 'Besitzer';
        case 'tenant':
            return 'Mieter';
        case 'landlord':
            return 'Vermieter';
        case 'subtenant':
            return 'Untermieter';
        default:
            return '';
    }
}

const openViewVehicleDialog = (vehicle: VehicleFile) => {
    selectedVehicle.value = vehicle;
    viewVehicleDialog.value = true;
};

const viewVehicleClose = (vehicle: VehicleFile) => {
    selectedVehicle.value = null;
    viewVehicleDialog.value = false;
};

const openViewApartmentDialog = (apartment: ApartmentWithRelations) => {
    selectedApartment.value = apartment;
    viewApartmentDialog.value = true;
};

const viewApartmentClose = (apartment: ApartmentWithRelations) => {
    selectedApartment.value = null;
    viewApartmentDialog.value = false;
};

const closeDialog = () => {
    emit('update:modelValue', false);
    emit('close');
};

watch(
    () => props.personToView,
    newVal => {
        if (newVal) {
            // Clone the person object to avoid reference issues
            person.value = JSON.parse(JSON.stringify(newVal));
            console.log('Person updated in watcher:', person.value.id);
            // Only fetch related data when we have a valid person
            if (person.value && person.value.id) {
                fetchVehicles();
                fetchApartments();
            }
        }
    },
    { immediate: true, deep: true }
);

onMounted(async () => {
    console.log('🎯 View component mounted');
    console.log('🎯 Initial props:', {
        modelValue: props.modelValue,
        personToView: props.personToView
    });
    
    // Fetch authorization-specific fields
    await fetchAuthorityFields();
    
    // Then fetch other related data
    if (person.value && person.value.id) {
        fetchVehicles();
        fetchApartments();
    }
});

// Fetch authority-specific fields
const fetchAuthorityFields = async () => {
    try {
        customFieldsLoading.value = true;
        authorityFields.value = await AuthorityFieldsService.getFields();
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    } finally {
        customFieldsLoading.value = false;
    }
};

const hasAuthorityData = computed(() => {
    if (!authority.value) return false;

    if (authority.value === 'police' || authority.value === 'test') {
        return !!person.value?.lastKnownLocation || !!person.value?.gangAffiliation;
    }

    if (authority.value === 'medic') {
        return (
            !!person.value?.allergies ||
            !!person.value?.emergencyContact ||
            !!person.value?.insured ||
            !!person.value?.bloodType ||
            !!person.value?.firstAid
        );
    }

    if (authority.value === 'justice') {
        return !!person.value?.lastKnownLocation || !!person.value?.gangAffiliation;
    }

    if (authority.value === 'casa') {
        return !!person.value?.job || !!person.value?.freelance;
    }

    return false;
});

// Helper method to safely access person fields
const getPersonFieldValue = (fieldName: string) => {
    if (!person.value || typeof person.value !== 'object' || !(fieldName in person.value)) {
        return '';
    }
    const value = (person.value as Record<string, any>)[fieldName];
    return value === null || value === undefined ? '' : value;
};

// Helper to get the display text for select fields
const getSelectFieldDisplayText = (field: AuthorityField, value: any) => {
    if (!field.options || !Array.isArray(field.options)) return value;
    
    try {
        const options = field.options; // Store in local variable after null check
        
        // Handle multiselect (array of values)
        if (Array.isArray(value)) {
            return value.map(val => {
                const option = options.find(opt => opt.value === val);
                return option ? option.text : val;
            }).join(', ');
        }
        
        // Handle single select
        const option = options.find(opt => opt.value === value);
        return option ? option.text : value;
    } catch (e) {
        console.error('Error processing field options:', e);
        return value;
    }
};

// Computed to check if we have custom fields with data
const hasCustomFields = computed(() => {
    if (!authorityFields.value || authorityFields.value.length === 0) return false;
    
    // Check if any of the custom fields has data in the person object
    return authorityFields.value.some(field => {
        const fieldName = field.field_name;
        return person.value && 
               typeof person.value === 'object' &&
               fieldName in person.value && 
               (person.value as Record<string, any>)[fieldName] !== undefined && 
               (person.value as Record<string, any>)[fieldName] !== null && 
               (person.value as Record<string, any>)[fieldName] !== '';
    });
});

// Funktion, um die Farbe je nach Rolle zu bestimmen
function getRoleColor(role: any) {
    switch (role) {
        case 'Besitzer':
            return 'success';
        case 'Mieter':
            return 'info';
        case 'Vermieter':
            return 'primary';
        case 'Untermieter':
            return 'purple';
        default:
            return 'grey';
    }
}
</script>

<template>
    <!-- Person View Card -->
    <v-card v-if="personToView && isVisible" class="person-card elevation-4">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-btn icon variant="text" @click="closeDialog" class="mr-2">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-account-details</v-icon>
                {{ $t('authorityComponents.personFile.viewTitle') }} - {{ person.firstname }} {{ person.lastname }}
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <add-shortcut-button
                v-if="person.id"
                type="person_file"
                :resource-id="person.id"
                :title="`${person.firstname} ${person.lastname}`"
                :subtitle="person.birthday || undefined"
                icon="mdi-account"
                color="primary"
            />
            <v-chip v-if="person.wanted" color="error" size="small" prepend-icon="mdi-alert-circle">
                {{ $t('personForm.wanted') }}
            </v-chip>
        </v-toolbar>

        <!-- Tabs für verschiedene Formularinformationen -->
        <v-tabs
            v-if="!viewVehicleDialog && !viewApartmentDialog"
            v-model="currentTab"
            bg-color="rgba(30, 41, 59, 0.4)"
            slider-color="primary"
            density="comfortable"
            class="tab-bar"
        >
            <v-tab value="1" class="tab-item">
                <v-icon size="small" class="mr-2">mdi-account-details</v-icon>
                {{ $t('personView.personInfoTab') }}
            </v-tab>
            <v-tab value="2" class="tab-item">
                <v-icon size="small" class="mr-2">mdi-office-building</v-icon>
                {{ $t('personView.vehiclesTab') }}
            </v-tab>
        </v-tabs>

        <v-window v-model="currentTab" v-if="!viewVehicleDialog && !viewApartmentDialog">
            <!-- Tab 1: Personeninformationen -->
            <v-window-item value="1">
                <v-form ref="form" class="pa-6">
                    <v-row>
                        <!-- Persönliche Informationen -->
                        <v-col cols="12">
                            <div class="section-title mb-4">
                                <v-icon size="small" class="mr-2">mdi-account</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">
                                    {{ $t('personForm.personalInfoSection') }}
                                </span>
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.firstname')"
                                        v-model="person.firstname"
                                        prepend-inner-icon="mdi-account"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.lastname')"
                                        v-model="person.lastname"
                                        prepend-inner-icon="mdi-account-box"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.birthplace')"
                                        v-model="person.birthplace"
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
                                        :label="$t('personForm.birthday')"
                                        v-model="person.birthday"
                                        prepend-inner-icon="mdi-calendar"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-col>

                        <!-- Kontaktinformationen -->
                        <v-col cols="12" class="mt-2">
                            <div class="section-title mb-4">
                                <v-icon size="small" class="mr-2">mdi-contacts</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">
                                    {{ $t('personForm.contactInfoSection') }}
                                </span>
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.phone')"
                                        v-model="person.phonenumber"
                                        prepend-inner-icon="mdi-phone"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
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
                                        bg-color="grey-darken-3"
                                        readonly
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
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-col>

                        <!-- Dokumenteninformationen -->
                        <v-col cols="12" class="mt-2">
                            <div class="section-title mb-4">
                                <v-icon size="small" class="mr-2">mdi-file-document</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">
                                    {{ $t('personForm.documentInfoSection') }}
                                </span>
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.idcard')"
                                        v-model="person.idcard"
                                        prepend-inner-icon="mdi-card-account-details"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
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
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('personForm.entry')"
                                        v-model="person.entry"
                                        prepend-inner-icon="mdi-calendar-check"
                                        variant="outlined"
                                        density="comfortable"
                                        bg-color="grey-darken-3"
                                        readonly
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
                                        bg-color="grey-darken-3"
                                        readonly
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-checkbox
                                        v-model="person.wanted"
                                        :label="$t('personForm.wanted')"
                                        color="error"
                                        hide-details
                                        readonly
                                        class="mt-2"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                        </v-col>

                        <!-- Authority-spezifische Felder -->
                        <v-col cols="12" v-if="hasAuthorityData" class="mt-2">
                            <div class="section-title mb-4">
                                <v-icon size="small" class="mr-2">mdi-shield-account</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">
                                    {{ $t('personView.authorityDataSection') }}
                                </span>
                            </div>

                            <v-row>
                                <!-- Police & Test Authority -->
                                <template v-if="authority === 'police' || authority === 'test'">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.lastKnownLocation')"
                                            v-model="person.lastKnownLocation"
                                            prepend-inner-icon="mdi-map-marker-radius"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.gangAffiliation')"
                                            v-model="person.gangAffiliation"
                                            prepend-inner-icon="mdi-account-group"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>
                                </template>

                                <!-- Medic Authority -->
                                <template v-if="authority === 'medic'">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.allergies')"
                                            v-model="person.allergies"
                                            prepend-inner-icon="mdi-alert-circle"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.emergencyContact')"
                                            v-model="person.emergencyContact"
                                            prepend-inner-icon="mdi-phone-alert"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.insured')"
                                            v-model="person.insured"
                                            prepend-inner-icon="mdi-shield-check"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :label="$t('personView.bloodType')"
                                            v-model="person.bloodType"
                                            prepend-inner-icon="mdi-water"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            :label="$t('personView.firstAid')"
                                            v-model="person.firstAid"
                                            prepend-inner-icon="mdi-medical-bag"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>
                                </template>

                                <!-- Justice Authority -->
                                <template v-if="authority === 'justice'">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Letzter bekannter Ort"
                                            v-model="person.lastKnownLocation"
                                            prepend-inner-icon="mdi-map-marker-radius"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Gangangehörigkeit"
                                            v-model="person.gangAffiliation"
                                            prepend-inner-icon="mdi-account-group"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>
                                </template>

                                <!-- Casa Authority -->
                                <template v-if="authority === 'casa'">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Beruf"
                                            v-model="person.job"
                                            prepend-inner-icon="mdi-briefcase"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Freiberufliche Tätigkeit"
                                            v-model="person.freelance"
                                            prepend-inner-icon="mdi-account-tie"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                    </v-col>
                                </template>
                            </v-row>
                        </v-col>

                        <!-- Benutzerdefinierte Felder aus der Datenbank -->
                        <v-col cols="12" v-if="hasCustomFields" class="mt-2">
                            <div class="section-title mb-4">
                                <v-icon size="small" class="mr-2">mdi-database-cog</v-icon>
                                <span class="text-subtitle-1 font-weight-medium">
                                    Benutzerdefinierte Felder
                                </span>
                            </div>

                            <v-row>
                                <v-col 
                                    v-for="field in authorityFields" 
                                    :key="field.id"
                                    cols="12" 
                                    :sm="field.field_type === 'textarea' ? 12 : 6"
                                >
                                    <template v-if="person && getPersonFieldValue(field.field_name) !== ''">
                                        <!-- Text Field -->
                                        <v-text-field
                                            v-if="field.field_type === 'text'"
                                            :label="field.display_name"
                                            :model-value="String(getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                        
                                        <!-- Number Field -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'number'"
                                            :label="field.display_name"
                                            :model-value="String(getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                        
                                        <!-- Date Field -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'date'"
                                            :label="field.display_name"
                                            :model-value="String(getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>
                                        
                                        <!-- Boolean Field -->
                                        <v-checkbox
                                            v-else-if="field.field_type === 'boolean'"
                                            :label="field.display_name"
                                            :model-value="Boolean(getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-checkbox>
                                        
                                        <!-- Select Field -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'select'"
                                            :label="field.display_name"
                                            :model-value="getSelectFieldDisplayText(field, getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>

                                        <!-- Multiselect Field -->
                                        <v-text-field
                                            v-else-if="field.field_type === 'multiselect' || String(field.field_type) === 'multiselect'"
                                            :label="field.display_name"
                                            :model-value="getSelectFieldDisplayText(field, getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-text-field>

                                        <!-- Textarea Field -->
                                        <v-textarea
                                            v-else-if="field.field_type === 'textarea'"
                                            :label="field.display_name"
                                            :model-value="String(getPersonFieldValue(field.field_name))"
                                            variant="outlined"
                                            density="comfortable"
                                            bg-color="grey-darken-3"
                                            readonly
                                            class="field-item"
                                        ></v-textarea>
                                    </template>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-form>
            </v-window-item>

            <!-- Tab 2: Fahrzeuge & Wohnungen -->
            <v-window-item value="2">
                <div class="pa-6">
                    <!-- Fahrzeuge -->
                    <div class="section-title mb-4">
                        <v-icon size="small" class="mr-2">mdi-car</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">Fahrzeuge</span>
                    </div>

                    <v-card variant="outlined" class="mb-6 table-card" color="blue-grey-darken-3">
                        <v-data-table
                            :headers="vehicleHeaders"
                            :items="vehicles"
                            :items-per-page="5"
                            density="comfortable"
                            hover
                            class="vehicle-table"
                        >
                            <template v-slot:[`item.role`]="{ item }">
                                <v-chip
                                    :color="item.role === 'Besitzer' ? 'success' : 'info'"
                                    size="small"
                                    variant="tonal"
                                    class="text-caption"
                                    >{{ item.role }}</v-chip
                                >
                            </template>

                            <template v-slot:[`item.actions`]="{ item }">
                                <v-tooltip text="Fahrzeug anzeigen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openViewVehicleDialog(item)"
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
                                        >mdi-car-off</v-icon
                                    >
                                    <span>Keine Fahrzeuge eingetragen</span>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>

                    <!-- Wohnungen -->
                    <div class="section-title mb-4 mt-6">
                        <v-icon size="small" class="mr-2">mdi-home</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">Wohnungen</span>
                    </div>

                    <v-card variant="outlined" class="mb-6 table-card" color="blue-grey-darken-3">
                        <v-data-table
                            :headers="apartmentHeaders"
                            :items="apartments"
                            :items-per-page="5"
                            density="comfortable"
                            hover
                            class="apartment-table"
                        >
                            <template v-slot:[`item.type`]="{ item }">
                                <v-chip
                                    :color="getRoleColor(item.type)"
                                    size="small"
                                    variant="tonal"
                                    class="text-caption"
                                    >{{ item.type }}</v-chip
                                >
                            </template>

                            <template v-slot:[`item.actions`]="{ item }">
                                <v-tooltip text="Wohnung anzeigen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openViewApartmentDialog(item)"
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
                                        >mdi-home-off</v-icon
                                    >
                                    <span>Keine Wohnungen eingetragen</span>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>

                    <!-- Beschreibung -->
                    <div class="section-title mb-4 mt-6">
                        <v-icon size="small" class="mr-2">mdi-text-box</v-icon>
                        <span class="text-subtitle-1 font-weight-medium">Beschreibung</span>
                    </div>

                    <div v-if="person.text" class="description-content pa-4">
                        <div v-html="person.text"></div>
                    </div>

                    <div v-else class="empty-description mt-2">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-text-box-outline</v-icon
                        >
                        <span>Keine Beschreibung vorhanden</span>
                    </div>
                </div>
            </v-window-item>
        </v-window>

        <!-- Aktionsbuttons -->
        <v-divider v-if="!viewVehicleDialog && !viewApartmentDialog"></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer v-if="!viewVehicleDialog && !viewApartmentDialog"></v-spacer>
            <v-btn
                color="primary"
                variant="elevated"
                prepend-icon="mdi-close"
                @click="closeDialog"
                class="close-button"
                v-if="!viewVehicleDialog && !viewApartmentDialog"
            >
                Schließen
            </v-btn>
        </v-card-actions>

        <!-- VehicleView Component -->
        <component
            :is="AuthorityViewVehicleFile"
            v-model="viewVehicleDialog"
            :vehicleToView="selectedVehicle"
            @close="viewVehicleClose"
        />
        <!-- ApartmentView Component -->
        <component
            :is="AuthorityViewApartmentFile"
            v-model="viewApartmentDialog"
            :viewApartmentDialog="viewApartmentDialog"
            :apartmentToView="selectedApartment"
            @close="viewApartmentClose"
        />
    </v-card>
</template>

<style scoped>

/* Card Styling */
.person-card {
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

/* Table Card */
.table-card {
    background: rgba(30, 41, 59, 0.3) !important;
    border-radius: 8px;
    overflow: hidden;
}

.vehicle-table :deep(th),
.apartment-table :deep(th) {
    background-color: rgba(30, 41, 59, 0.5) !important;
}

.vehicle-table :deep(tr:hover),
.apartment-table :deep(tr:hover) {
    background-color: var(--k-row-hover) !important;
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
    min-height: 150px;
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
