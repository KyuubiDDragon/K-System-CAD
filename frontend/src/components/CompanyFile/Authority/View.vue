<script setup lang="ts">
import { defineComponent, ref, watch, reactive, type PropType } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Company } from '@/types/Company';
import { apiClientAuth } from '@/api';
import TiptapEditor from '@/components/TiptapEditor.vue';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

interface Props {
  modelValue?: boolean
  companyToView?: Company | null
  viewCompanyDialog?: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'close']);

const currentTab = ref(0);
const { t } = useI18n();
const localCompanyToView = ref<Company>({ ...(props.companyToView || {}) } as Company);
const reportCategories = ref<any[]>([]);
const selectedReport = ref<any | null>(null);
const reportHeaders = [
    { title: t('reportForm.title'), align: 'start', key: 'title' },
    { title: t('reportForm.creator'), align: 'start', key: 'emp_name' },
    { title: t('reportForm.createdAt'), align: 'start', key: 'created_at' },
    { title: t('reportForm.lastChange'), align: 'start', key: 'updated_at' },
    { title: t('authorityComponents.common.actions'), align: 'start', key: 'actions' },
] as const;

const fetchLinkedReports = async () => {
    try {
        const response = await apiClientAuth.get(
            `/report/?action=getReportsByLinkedCompanies&companyId=${localCompanyToView.value.id}`
        );
        const rawReports = response.data;

        const categorizedReports = rawReports.reduce((categories: any[], report: any) => {
            const categoryName = report.cat_name || 'Unkategorisiert';
            let category = categories.find((cat: any) => cat.name === categoryName);

            if (!category) {
                category = {
                    id: report.category_id,
                    name: categoryName,
                    reports: [],
                };
                categories.push(category);
            }

            category.reports.push({
                ...report,
                created_at: new Date(report.created_at).toLocaleDateString('de-DE'),
            });

            return categories;
        }, []);

        reportCategories.value = categorizedReports;
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const viewReport = (report: any) => {
    selectedReport.value = report;
};

const closeReportView = () => {
    selectedReport.value = null;
};

const closeView = () => {
    emit('update:modelValue', false);
};

watch(
    () => props.companyToView,
    newVal => {
        localCompanyToView.value = { ...newVal };
        fetchLinkedReports();
    },
    { immediate: true }
);

const extinguishers = ref<any[]>([]);
const extinguisherHeaders = [
    { title: t('companyForm.identifier'), value: 'identifier' },
    { title: t('companyForm.registrationDate'), value: 'registration_date' },
    { title: t('companyForm.lastInspection'), value: 'last_inspection' },
    { title: t('companyForm.inspectedBy'), value: 'inspected_by' },
    { title: t('companyForm.location'), value: 'location' },
    { title: t('authorityComponents.common.actions'), value: 'actions', sortable: false },
];
const extinguisherDialog = ref(false);
const extinguisherForm = reactive<any>({
    id: null,
    identifier: '',
    registration_date: '',
    last_inspection: '',
    inspected_by: '',
    location: '',
    company_id: null,
});
const isEditMode = ref(false);
const selectedExtinguisher = ref<any | null>(null);
const deleteConfirmationDialog = ref(false);

// Funktionen zum Abrufen, Hinzufügen, Bearbeiten und Löschen
const fetchExtinguishers = async () => {
    try {
        const response = await apiClientAuth.get(
            `/company/?action=getExtinguishers&company_id=${localCompanyToView.value.id}`
        );
        extinguishers.value = response.data;
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const openAddExtinguisherDialog = () => {
    isEditMode.value = false;
    Object.assign(extinguisherForm, {
        id: null,
        identifier: '',
        registration_date: '',
        last_inspection: '',
        inspected_by: '',
        location: '',
        company_id: localCompanyToView.value.id,
    });
    extinguisherDialog.value = true;
};

const openEditExtinguisherDialog = (extinguisher: any) => {
    isEditMode.value = true;
    Object.assign(extinguisherForm, extinguisher);
    extinguisherDialog.value = true;
};

const closeExtinguisherDialog = () => {
    extinguisherDialog.value = false;
};

const saveExtinguisher = async () => {
    try {
        if (isEditMode.value) {
            // Bearbeiten
            const response = await apiClientAuth.post(
                '/company/?action=editExtinguisher',
                extinguisherForm
            );
            console.log('Feuerlöscher aktualisiert:', response.data);
        } else {
            // Hinzufügen
            const response = await apiClientAuth.post(
                '/company/?action=addExtinguisher',
                extinguisherForm
            );
            console.log('Feuerlöscher hinzugefügt:', response.data);
        }
        closeExtinguisherDialog();
        fetchExtinguishers();
    } catch (error) {
        console.error('Fehler beim Speichern des Feuerlöschers:', error);
    }
};

const confirmDeleteExtinguisher = (extinguisher: any) => {
    selectedExtinguisher.value = extinguisher;
    deleteConfirmationDialog.value = true;
};

const deleteExtinguisher = async () => {
    try {
        await apiClientAuth.post('/company/?action=deleteExtinguisher', {
            id: selectedExtinguisher.value.id,
        });
        deleteConfirmationDialog.value = false;
        fetchExtinguishers();
    } catch (error) {
        console.error('Fehler beim Löschen des Feuerlöschers:', error);
    }
};

// Hilfsfunktion zur Bestimmung der Ablaufklasse für Datumsfelder
const getExpiryClass = (date: string): string => {
    if (!date) return '';
    
    const expiryDate = new Date(date);
    const today = new Date();
    
    // Set both dates to the start of the day for accurate comparison
    expiryDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    
    // Calculate days left
    const daysLeft = Math.floor((expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    
    if (daysLeft < 0) {
        return 'expired-field';  // Date has passed
    } else if (daysLeft <= 30) {
        return 'warning-field';  // Less than or equal to 30 days left
    }
    
    return '';  // More than 30 days left
};

// Aktualisieren der Feuerlöscher, wenn sich die Firma ändert
watch(
    () => props.companyToView,
    newVal => {
        localCompanyToView.value = { ...newVal };
        fetchExtinguishers();
    },
    { immediate: true }
);
</script>

<template>
    <!-- Firma Details Card -->
    <v-card v-if="viewCompanyDialog && !selectedReport" class="company-detail-card">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-domain</v-icon>
                {{ $t('authorityComponents.companyFile.viewTitle') }} - {{ localCompanyToView.name }}
            </v-toolbar-title>
            <v-spacer />
            <add-shortcut-button
                v-if="localCompanyToView.id"
                type="company_file"
                :resource-id="localCompanyToView.id"
                :title="localCompanyToView.name"
                :subtitle="localCompanyToView.ceo || undefined"
                icon="mdi-domain"
                color="blue"
            />
        </v-toolbar>

        <!-- Tabs für verschiedene Ansichten -->
        <v-tabs
            v-model="currentTab"
            show-arrows
            color="primary"
            class="detail-tabs mb-2"
            slider-color="primary"
            density="comfortable"
        >
            <v-tab value="1" class="tab-item">
                <v-icon start size="16">mdi-information-outline</v-icon>
                {{ $t('authorityComponents.tabs.info') }}
            </v-tab>
            <v-tab value="2" class="tab-item">
                <v-icon start size="16">mdi-text-box-outline</v-icon>
                {{ $t('authorityComponents.tabs.description') }}
            </v-tab>
            <v-tab value="3" class="tab-item">
                <v-icon start size="16">mdi-file-document-outline</v-icon>
                {{ $t('companyForm.linkedReportsTab') }}
            </v-tab>
            <v-tab value="4" class="tab-item">
                <v-icon start size="16">mdi-fire-extinguisher</v-icon>
                {{ $t('companyForm.extinguisherTab') }}
            </v-tab>
        </v-tabs>

        <!-- Inhalt der Tabs -->
        <v-window v-model="currentTab" class="tab-content">
            <!-- Tab 1: Firmendetails -->
            <v-window-item value="1">
                <v-card-text class="pt-2">
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-domain</v-icon>
                            {{ $t('companyForm.basicInfoSection') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.name')"
                                    v-model="localCompanyToView.name"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-domain"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.location')"
                                    v-model="localCompanyToView.location"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-map-marker"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.type')"
                                    v-model="localCompanyToView.type_name"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-shape"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-checkbox
                                    v-model="localCompanyToView.contract_available"
                                    :label="$t('companyForm.contractAvailable')"
                                    readonly
                                    color="primary"
                                    hide-details
                                    class="mt-3"
                                ></v-checkbox>
                            </v-col>
                        </v-row>
                    </div>

                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-account-tie</v-icon>
                            {{ $t('companyForm.contactInfoSection') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.phone')"
                                    v-model="localCompanyToView.phonenumber"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-phone"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.email')"
                                    v-model="localCompanyToView.email"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-email"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.ceo')"
                                    v-model="localCompanyToView.ceo"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-account-tie"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.coCeo')"
                                    v-model="localCompanyToView.co_ceo"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-account-tie-outline"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    :label="$t('companyForm.contactPerson')"
                                    v-model="localCompanyToView.contact_person"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-account-voice"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-shield-fire</v-icon>
                            {{ $t('companyForm.fireProtectionSection') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.lastFireInspection')"
                                    v-model="localCompanyToView.last_fire_protection_inspection"
                                    readonly
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-calendar-check"
                                    class="readonly-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.fireInspectionValidUntil')"
                                    v-model="
                                        localCompanyToView.fire_protection_inspection_valid_until
                                    "
                                    readonly
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-calendar-clock"
                                    class="readonly-field"
                                    :class="
                                        getExpiryClass(
                                            localCompanyToView.fire_protection_inspection_valid_until ?? ''
                                        )
                                    "
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
            </v-window-item>

            <!-- Tab 2: Beschreibung -->
            <v-window-item value="2">
                <v-card-text class="pt-2">
                    <div class="editor-container">
                        <TiptapEditor
                            v-model="localCompanyToView.description"
                            placeholder="Geben Sie hier Notizen oder zusätzliche Informationen ein..."
                            :show-character-count="false"
                            :show-source-button="true"
                            :editable="false"
                        />
                    </div>
                </v-card-text>
            </v-window-item>

            <!-- Tab 3: Verlinkte Berichte -->
            <v-window-item value="3">
                <v-card-text class="pt-2">
                    <div v-if="reportCategories.length === 0" class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-file-document-multiple-outline</v-icon
                        >
                        <span>Keine verknüpften Berichte vorhanden.</span>
                    </div>

                    <div v-else>
                        <v-expansion-panels variant="accordion" multiple>
                            <v-expansion-panel
                                v-for="category in reportCategories"
                                :key="category.id"
                                :title="category.name"
                                class="mb-3 report-panel"
                            >
                                <template v-slot:text>
                                    <v-data-table
                                        :headers="reportHeaders"
                                        :items="category.reports"
                                        hover
                                        density="comfortable"
                                        class="report-table"
                                    >
                                        <template v-slot:[`item.actions`]="{ item }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="viewReport(item)"
                                                class="action-icon"
                                            >
                                                <v-icon size="small">mdi-eye</v-icon>
                                            </v-btn>
                                        </template>

                                        <template v-slot:no-data>
                                            <div class="text-center py-3 text-grey">
                                                Keine Berichte in dieser Kategorie
                                            </div>
                                        </template>
                                    </v-data-table>
                                </template>
                            </v-expansion-panel>
                        </v-expansion-panels>
                    </div>
                </v-card-text>
            </v-window-item>

            <!-- Tab 4: Feuerlöscher -->
            <v-window-item value="4">
                <v-card-text class="pt-2">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <h3 class="text-h6">
                            <v-icon start>mdi-fire-extinguisher</v-icon>
                            {{ $t('companyForm.extinguisherOverview') }}
                        </h3>

                        <v-btn
                            color="primary"
                            variant="elevated"
                            prepend-icon="mdi-plus"
                            @click="openAddExtinguisherDialog"
                            class="action-button"
                        >
                            {{ $t('companyForm.addExtinguisher') }}
                        </v-btn>
                    </div>

                    <div v-if="extinguishers.length === 0" class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-fire-extinguisher</v-icon
                        >
                        <span>{{ $t('companyForm.noExtinguishers') }}</span>
                    </div>

                    <v-data-table
                        v-else
                        :headers="extinguisherHeaders"
                        :items="extinguishers"
                        hover
                        density="comfortable"
                        class="extinguisher-table"
                    >
                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="d-flex gap-2">
                                <v-tooltip :text="$t('companyForm.edit')" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditExtinguisherDialog(item)"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-pencil</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>

                                <v-tooltip :text="$t('companyForm.delete')" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="confirmDeleteExtinguisher(item)"
                                            v-bind="props"
                                            color="error"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-delete</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-window-item>
        </v-window>

        <!-- Aktionsbuttons -->
        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="elevated" @click="closeView" class="close-button">
                <v-icon start>mdi-close</v-icon>
                Schließen
            </v-btn>
        </v-card-actions>

        <!-- Dialog zum Hinzufügen/Bearbeiten -->
        <v-dialog v-model="extinguisherDialog" max-width="600" class="extinguisher-dialog">
            <v-card class="dialog-card">
                <v-toolbar
                    density="compact"
                    :color="isEditMode ? 'primary' : 'success'"
                    class="card-toolbar"
                >
                    <v-toolbar-title class="text-subtitle-1">
                        <v-icon start size="18" class="mr-2">
                            {{ isEditMode ? 'mdi-pencil' : 'mdi-plus' }}
                        </v-icon>
                        {{
                            isEditMode ? $t('companyForm.editExtinguisher') : $t('companyForm.addExtinguisher')
                        }}
                    </v-toolbar-title>
                </v-toolbar>

                <v-card-text class="pa-4">
                    <v-form ref="extinguisherFormRef">
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    :label="$t('companyForm.identifier') + '*'"
                                    v-model="extinguisherForm.identifier"
                                    required
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-identifier"
                                    class="field-item"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.registrationDate')"
                                    v-model="extinguisherForm.registration_date"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-calendar-plus"
                                    class="field-item"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.lastInspection')"
                                    v-model="extinguisherForm.last_inspection"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-calendar-check"
                                    class="field-item"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.inspectedBy')"
                                    v-model="extinguisherForm.inspected_by"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-account-hard-hat"
                                    class="field-item"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    :label="$t('companyForm.location')"
                                    v-model="extinguisherForm.location"
                                    variant="outlined"
                                    density="comfortable"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-map-marker"
                                    class="field-item"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeExtinguisherDialog" class="mr-2">
                        {{ $t('companyForm.cancel') }}
                    </v-btn>

                    <v-btn
                        :color="isEditMode ? 'primary' : 'success'"
                        variant="elevated"
                        @click="saveExtinguisher"
                        class="save-button"
                    >
                        <v-icon start>{{ isEditMode ? 'mdi-content-save' : 'mdi-plus' }}</v-icon>
                        {{ isEditMode ? $t('companyForm.save') : $t('companyForm.add') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog zur Löschbestätigung -->
        <v-dialog v-model="deleteConfirmationDialog" max-width="500" class="confirmation-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Feuerlöscher löschen
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>Möchten Sie diesen Feuerlöscher wirklich löschen?</p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Identifier: <strong>{{ selectedExtinguisher?.identifier }}</strong>
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="deleteConfirmationDialog = false" class="mr-2">
                        {{ $t('companyForm.cancel') }}
                    </v-btn>

                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="deleteExtinguisher"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        {{ $t('companyForm.delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>

    <!-- Bericht Details Ansicht -->
    <v-card v-else-if="selectedReport" class="report-detail-card">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-file-document-outline</v-icon>
                {{ selectedReport.title }}
            </v-toolbar-title>
        </v-toolbar>

        <v-card-text class="report-content pa-4">
            <div class="report-metadata mb-4">
                <v-chip size="small" label color="primary" variant="tonal" class="mr-2">
                    <v-icon start size="14">mdi-account</v-icon>
                    {{ selectedReport.emp_name }}
                </v-chip>

                <v-chip size="small" label color="info" variant="tonal" class="mr-2">
                    <v-icon start size="14">mdi-calendar</v-icon>
                    {{ selectedReport.created_at }}
                </v-chip>
            </div>

            <div class="report-html" v-html="selectedReport.text"></div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="elevated" @click="closeReportView" class="close-button">
                <v-icon start>mdi-arrow-left</v-icon>
                {{ $t('companyForm.backToCompany') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>

/* Card Styling */
.company-detail-card,
.report-detail-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

/* Tabs Styling */
.detail-tabs {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--k-line);
}

.tab-item {
    min-width: 140px;
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
}

.tab-content {
    min-height: 400px;
}

/* Form Sections */
.form-section {
    margin-bottom: 24px;
}

.section-title {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 16px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--k-line);
    color: var(--v-theme-primary);
}

.readonly-field {
    opacity: 0.9;
    transition: all var(--transition-timing);
}

.readonly-field:hover {
    opacity: 1;
    background: var(--k-sunken);
}

/* Editor Container */
.editor-container {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
}

/* Report Panels */
.report-panel {
    background: var(--k-sunken) !important;
    border: 1px solid var(--k-line);
    border-radius: 8px !important;
    overflow: hidden;
    transition: all var(--transition-timing);
}

.report-panel:hover {
    transform: var(--button-hover-translate);
    box-shadow: var(--shadow-elevation);
}

.report-table,
.extinguisher-table {
    border-radius: 8px;
    overflow: hidden;
}

/* Empty States */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: var(--k-ink-muted);
    text-align: center;
    min-height: 200px;
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

/* Action Icons */
.action-icon {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Close & Save Buttons */
.close-button,
.save-button,
.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.close-button:hover,
.save-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

.delete-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* Dialog Cards */
.dialog-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.extinguisher-dialog :deep(.v-overlay__content),
.confirmation-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #991b1b, #dc2626);
    color: var(--k-ink);
    padding: 16px;
}

/* Form Fields */
.field-item {
    border-radius: 8px;
    transition: all var(--transition-timing);
}

.field-item:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Report Content */
.report-content {
    padding: 20px;
}

.report-metadata {
    margin-bottom: 16px;
}

.report-html {
    background: var(--k-sunken);
    padding: 16px;
    border-radius: 8px;
    min-height: 300px;
}

.report-html :deep(p) {
    margin-bottom: 1em;
}

.report-html :deep(h1),
.report-html :deep(h2),
.report-html :deep(h3),
.report-html :deep(h4),
.report-html :deep(h5),
.report-html :deep(h6) {
    margin-top: 1em;
    margin-bottom: 0.5em;
}

.report-html :deep(ul),
.report-html :deep(ol) {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

/* Status Colors (for BSB dates) */
.expired-field {
    color: #ef4444 !important;
    font-weight: 600;
}

.warning-field {
    color: #f59e0b !important;
    font-weight: 500;
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .tab-item {
        min-width: auto;
        padding: 0 8px;
    }

    .report-metadata {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
