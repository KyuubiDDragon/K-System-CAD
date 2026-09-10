<script setup lang="ts">
import { ref, computed, onMounted, reactive, defineAsyncComponent, unref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import type { Company, CompanyType } from '@/types/Company'; // Adjust path if needed
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
// --- Async Component Imports ---
const AuthorityAddCompanyFile = defineAsyncComponent(
    () => import('@/components/CompanyFile/Authority/Add.vue')
); // Adjust path
const AuthorityEditCompanyFile = defineAsyncComponent(
    () => import('@/components/CompanyFile/Authority/Edit.vue')
); // Adjust path
const AuthorityViewCompanyFile = defineAsyncComponent(
    () => import('@/components/CompanyFile/Authority/View.vue')
); // Adjust path

// --- Store, Router & Permissions ---
const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: string | number
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);
const isFireAuthority = computed(
    () => authStore.user?.authority === 'fire' || authStore.user?.authority === 'fireguard'
);

// --- Component State ---
const companies = ref<Company[]>([]);
const search = ref('');
const loadingCompanies = ref(false);
const loadingExtinguisherNr = ref(false);
const savingExtinguisherNr = ref(false);
const deletingCompany = ref(false);
const extinguisherNr = ref<string>('');

// --- Dialog States & Data ---
const newCompanyDialog = ref(false);
const editCompanyDialog = ref(false);
const viewCompanyDialog = ref(false);
const confirmDeleteDialog = ref(false);
const editedCompany = ref<Company | null>(null);
const selectedCompany = ref<Company | null>(null);
const companyToDelete = ref<Company | null>(null);

// Detail view mode for navigation from search
const detailViewMode = ref(false);
const companyDetailData = ref<Company | null>(null);

// --- Filters ---
const filters = reactive({
    expiryFilter: null as string | null,
});
const expiryFilterOptions = [
    { title: 'Alle', value: null },
    { title: 'Nächste 30 Tage', value: 'next-30-days' },
    { title: 'Letzte 30 Tage', value: 'last-30-days' },
    { title: 'Kein Datum', value: 'not-available' },
];

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers & Grouping ---
const companyHeaders = computed(() => [
    { title: t('company.headers.name'), key: 'name', sortable: true },
    { title: t('company.headers.controlCenter'), key: 'phonenumber', sortable: false },
    { title: t('company.headers.ceo'), key: 'ceo', sortable: true },
    { title: t('company.headers.fireExtinguishers'), key: 'extinguisher_count', sortable: true, align: 'end' },
    { title: t('company.headers.lastInspection'), key: 'last_fire_protection_inspection', sortable: true, optional: true, align: 'end' },
    { title: t('company.headers.nextInspection'), key: 'fire_protection_inspection_valid_until', sortable: true, align: 'end' },
    { title: t('company.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);
const groupByCategoryName = [{ key: 'type_name', order: 'asc' as const }];
const groupBy = computed(() => (search.value ? [] : groupByCategoryName));

// --- Data Fetching ---
const fetchCompanies = async () => {
    loadingCompanies.value = true;
    try {
        const response = await apiClientAuth.get<Company[]>('/company/?action=getCompanies');
        companies.value = (response.data || []).map(
            (entry: any): Company => ({
                ...entry,
                contract_available:
                    entry.contract_available === '1' || entry.contract_available === true,
                fire_protection_inspection_valid_until:
                    entry.fire_protection_inspection_valid_until === '0000-00-00'
                        ? null
                        : entry.fire_protection_inspection_valid_until,
                extinguisher_count: Number(entry.extinguisher_count) || 0,
                type_id: Number(entry.type_id) || null,
            })
        );
    } catch (error: any) {
        console.error('Error fetching companies:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Firmen.', 'error');
        companies.value = [];
    } finally {
        loadingCompanies.value = false;
    }
};

const fetchExtinguisherNr = async () => {
    if (!isFireAuthority.value) return;
    loadingExtinguisherNr.value = true;
    try {
        const response = await apiClientAuth.get<{ extinguisherNr: string }>(
            '/company/?action=getFireExtinguisherNr'
        );
        extinguisherNr.value = response.data?.extinguisherNr ?? response.data?.extinguisherNr ?? '';
    } catch (error: any) {
        console.error('Error fetching extinguisher number:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Laden der Feuerlöschernummer.',
            'error'
        );
    } finally {
        loadingExtinguisherNr.value = false;
    }
};

const fetchAllInitialData = () => {
    fetchCompanies();
    fetchExtinguisherNr();
};

// --- Computed Properties ---
const filteredCompanies = computed(() => {
    return companies.value.filter(company => {
        // Search Filter
        const searchTermLower = search.value.trim().toLowerCase();
        const matchesSearch =
            !searchTermLower ||
            (company.name?.toLowerCase() || '').includes(searchTermLower) ||
            (company.ceo?.toLowerCase() || '').includes(searchTermLower) ||
            (company.phonenumber?.toLowerCase() || '').includes(searchTermLower);

        if (!matchesSearch) return false;

        // Expiry Date Filter
        if (!filters.expiryFilter) return true;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const validUntilStr = company.fire_protection_inspection_valid_until;

        if (filters.expiryFilter === 'not-available') {
            return !validUntilStr;
        }

        if (!validUntilStr) return false;

        try {
            const dateValidUntil = new Date(validUntilStr);
            dateValidUntil.setHours(0, 0, 0, 0);
            if (isNaN(dateValidUntil.getTime())) return false;

            if (filters.expiryFilter === 'next-30-days') {
                const thirtyDaysFromNow = new Date(today);
                thirtyDaysFromNow.setDate(today.getDate() + 30);
                return dateValidUntil >= today && dateValidUntil <= thirtyDaysFromNow;
            } else if (filters.expiryFilter === 'last-30-days') {
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 30);
                return dateValidUntil < today && dateValidUntil >= thirtyDaysAgo;
            }
        } catch (e) {
            console.error('Error parsing date for filter:', validUntilStr, e);
            return false;
        }
        return true; // Should only hit if filter value is invalid
    });
});

// --- Methods ---

// Dialog Openers/Closers and Event Handlers
const openNewCompanyDialog = () => (newCompanyDialog.value = true);
const closeAddCompanyDialog = () => (newCompanyDialog.value = false);
const handleCompanyAdded = () => {
    closeAddCompanyDialog();
    showSnackbar('Firma erfolgreich hinzugefügt.', 'success');
    fetchCompanies(); // Refresh list
};

const openEditCompanyDialog = (company: Company) => {
    editedCompany.value = { ...company };
    editCompanyDialog.value = true;
};
const closeEditCompanyDialog = () => {
    editCompanyDialog.value = false;
    editedCompany.value = null;
};
const handleCompanyUpdated = () => {
    closeEditCompanyDialog();
    showSnackbar('Firma erfolgreich aktualisiert.', 'success');
    fetchCompanies(); // Refresh list
};

const viewCompanyDetails = (company: Company) => {
    // If we're in the context of search navigation, don't open dialog
    if (route.query.id || props.id || props.meta?.id || props.meta?.companyId) {
        return;
    }
    selectedCompany.value = { ...company };
    viewCompanyDialog.value = true;
};
const closeViewCompanyDialog = () => {
    viewCompanyDialog.value = false;
    selectedCompany.value = null;
};

const showCompanyDetails = (company: Company) => {
    companyDetailData.value = { ...company };
    detailViewMode.value = true;
};

const backToList = () => {
    detailViewMode.value = false;
    companyDetailData.value = null;
    // Clear query params if they exist
    if (route.query.id) {
        const router = useRouter();
        router.push({ name: route.name, query: {} });
    }
};

// Delete Logic
const openDeleteCompanyDialog = (company: Company) => {
    companyToDelete.value = company;
    confirmDeleteDialog.value = true;
};
const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    companyToDelete.value = null;
};
const proceedWithDelete = async () => {
    if (!companyToDelete.value) return;
    deletingCompany.value = true;
    try {
        await apiClientAuth.post('/company/?action=deleteCompany', { id: companyToDelete.value.id });
        closeConfirmDeleteDialog();
        await fetchCompanies(); // Refresh list
        showSnackbar('Firma erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting company:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Firma.', 'error');
    } finally {
        deletingCompany.value = false;
    }
};

// Extinguisher Number Update
const updateFireExtinguisherNr = async () => {
    if (!extinguisherNr.value || savingExtinguisherNr.value) return; // Prevent saving if empty or already saving
    savingExtinguisherNr.value = true;
    try {
        await apiClientAuth.post('/company/?action=setFireExtinguisherNr', {
            extinguisherNr: extinguisherNr.value,
        });
        showSnackbar('Feuerlöschernummer aktualisiert.', 'success');
        // Optional: Re-fetch to confirm
        // await fetchExtinguisherNr();
    } catch (error: any) {
        console.error('Error updating extinguisher number:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren der Nummer.',
            'error'
        );
        await fetchExtinguisherNr(); // Fetch again on error to revert
    } finally {
        savingExtinguisherNr.value = false;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return '-';
    }
};

const getExpiryClass = (dateString?: string | null): string => {
    if (!dateString) return '';
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const dateValidUntil = new Date(dateString);
        dateValidUntil.setHours(0, 0, 0, 0);
        if (isNaN(dateValidUntil.getTime())) return '';
        const thirtyDaysFromNow = new Date(today);
        thirtyDaysFromNow.setDate(today.getDate() + 30);
        if (dateValidUntil < today) return 'text-error font-weight-bold';
        if (dateValidUntil <= thirtyDaysFromNow) return 'text-warning';
    } catch {
        return '';
    }
    return '';
};

const getCompanyCountByType = (typeName: string | unknown): number => {
    // Handle the case when typeName is null or undefined
    if (typeName === null || typeName === undefined) {
        return companies.value.filter(company => !company.type_name).length;
    }
    return companies.value.filter(company => company.type_name === typeName).length;
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllInitialData();
    
    // Check if we need to open a specific company from query parameter or props
    const companyId = route.query.id || props.id || props.meta?.id || props.meta?.companyId;
    console.log('[CompanyView] Checking for company ID in query/props:', companyId);
    console.log('[CompanyView] ID sources - route.query.id:', route.query.id, 'props.id:', props.id, 'props.meta?.id:', props.meta?.id);
    
    if (companyId) {
        // Find the company in the loaded data
        const company = companies.value.find(c => c.id === Number(companyId));
        console.log('[CompanyView] Found company:', company);
        if (company) {
            // Show details inline instead of dialog
            console.log('[CompanyView] Showing company details inline:', company.id);
            showCompanyDetails(company);
        } else {
            console.log('[CompanyView] Company not found with ID:', companyId);
        }
    }
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('CompanyView', () => unref(companyHeaders) as any);


/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(kFilters.filtered.value) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'firmen' });
}

/**
 * Filter der Leiste. Schalter tragen eine feste Bedingung,
 * Facetten holen ihre Werte aus dem Bestand - nicht aus einer
 * gepflegten Liste, die am Tag ihrer Einfuehrung veraltet waere.
 */
const kFilters = useTableFilters(
    () => (unref(filteredCompanies) as any[]) ?? [],
    [
        {
            key: 'inspectionDue',
            label: t('company.filterInspectionDue'),
            test: (c: any) => {
                const until = c.fire_protection_inspection_valid_until;
                return !!until && new Date(until) < new Date();
            },
        },
    ],
    [
        { field: 'type_name', label: t('company.type'), emptyLabel: t('company.withoutType') },
    ],
);

</script>

<template>
    <v-container fluid class="company-container pa-4">
        <!-- Detail View Mode -->
        <template v-if="detailViewMode && companyDetailData">
            <!-- Detail View Header -->
            <v-row class="mb-5">
                <v-col cols="12">
                    <div class="page-header">
                        <div class="d-flex align-center">
                            <v-btn
                                icon
                                variant="text"
                                @click="backToList"
                                class="mr-2"
                            >
                                <v-icon>mdi-arrow-left</v-icon>
                            </v-btn>
                            <v-icon size="36" class="mr-2" color="primary">mdi-domain</v-icon>
                            <h1 class="text-h4 font-weight-medium mb-0">{{ companyDetailData.name }}</h1>
                        </div>
                    </div>
                </v-col>
            </v-row>
            
            <!-- Detail View Content -->
            <v-card class="detail-card elevation-4">
                <v-card-text class="pa-6">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                label="Firmenname"
                                :model-value="companyDetailData.name"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-domain"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                            <v-text-field
                                label="Leitstelle"
                                :model-value="companyDetailData.phonenumber || 'Nicht angegeben'"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-phone"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                            <v-text-field
                                label="CEO"
                                :model-value="companyDetailData.ceo || 'Nicht angegeben'"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-account-tie"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                            <v-text-field
                                label="Typ"
                                :model-value="companyDetailData.type_name || 'Nicht angegeben'"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-shape"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="4">
                            <v-text-field
                                label="Feuerlöscher"
                                :model-value="String(companyDetailData.extinguisher_count || 0)"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-fire-extinguisher"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="4">
                            <v-text-field
                                label="Letzte BSB"
                                :model-value="formatDate(companyDetailData.last_fire_protection_inspection)"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-calendar-check"
                                readonly
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" md="4">
                            <v-text-field
                                label="Nächste BSB"
                                :model-value="formatDate(companyDetailData.fire_protection_inspection_valid_until)"
                                variant="outlined"
                                density="comfortable"
                                :color="getExpiryClass(companyDetailData.fire_protection_inspection_valid_until) ? 'error' : 'primary'"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-calendar-alert"
                                readonly
                                :class="getExpiryClass(companyDetailData.fire_protection_inspection_valid_until)"
                            ></v-text-field>
                        </v-col>
                        
                        <v-col cols="12" v-if="companyDetailData.contract_available">
                            <v-chip
                                color="success"
                                variant="tonal"
                                size="large"
                                prepend-icon="mdi-file-document-check"
                            >
                                Vertrag vorhanden
                            </v-chip>
                        </v-col>
                    </v-row>
                    
                    <!-- Action buttons in detail view -->
                    <v-row class="mt-4">
                        <v-col cols="12">
                            <v-divider class="mb-4"></v-divider>
                            <div class="d-flex justify-end gap-2">
                                <v-btn
                                    v-if="canEdit"
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-pencil"
                                    @click="openEditCompanyDialog(companyDetailData)"
                                >
                                    Bearbeiten
                                </v-btn>
                                <v-btn
                                    v-if="canDelete"
                                    color="error"
                                    variant="tonal"
                                    prepend-icon="mdi-delete"
                                    @click="openDeleteCompanyDialog(companyDetailData)"
                                >
                                    Löschen
                                </v-btn>
                            </div>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </template>
        
        <!-- List View Mode -->
        <template v-else-if="!newCompanyDialog && !editCompanyDialog && !viewCompanyDialog">
            <!-- Kopfzeile mit Header und Aktionsbuttons -->
            <v-row class="mb-5">
                <v-col cols="12">
                    <div class="page-header">
                        <h1 class="text-h4 font-weight-medium mb-2">
                            <v-icon size="36" class="mr-2">mdi-domain</v-icon>
                            {{ t('companyView.title') }}
                        </h1>
                        <p class="text-body-1 text-medium-emphasis">
                            Übersicht aller registrierten Firmen und deren Brandschutz-Status
                        </p>
                    </div>
                </v-col>
            </v-row>

            <!-- Aktionsleiste -->
            <v-row class="action-section mb-4 align-center">
                <v-col cols="12" md="4" lg="3" v-if="isFireAuthority">
                    <v-card class="extinguisher-card elevation-2">
                        <v-card-text class="py-2 px-4">
                            <v-text-field
                                :append-inner-icon="canEdit ? 'mdi-content-save' : undefined"
                                density="comfortable"
                                label="Nächste Feuerlöschernummer"
                                variant="outlined"
                                hide-details
                                v-model="extinguisherNr"
                                :loading="loadingExtinguisherNr || savingExtinguisherNr"
                                :readonly="!canEdit"
                                @click:append-inner="canEdit ? updateFireExtinguisherNr : undefined"
                                @keydown.enter="canEdit ? updateFireExtinguisherNr : undefined"
                                title="Nächste zu vergebende Nummer für Feuerlöscher-Inspektion"
                                class="extinguisher-field"
                            ></v-text-field>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-spacer></v-spacer>

                <v-col cols="auto">
                    <v-btn
                        v-if="canEdit"
                        @click="openNewCompanyDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-domain-plus"
                        class="action-button"
                    >
                        {{ t('companyView.newCompany') }}
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Haupttabelle -->
            <v-card class="main-card elevation-4">
                <v-card-text class="pa-0">
                    <!-- Filterleiste -->
                    <v-toolbar
                        flat
                        density="comfortable"
                        color="transparent"
                        class="filter-toolbar px-4 py-3"
                    >
                        <v-toolbar-title class="text-h6">
                            <v-icon start size="20" class="mr-2">mdi-filter-variant</v-icon>
                            {{ t('companyView.filterSearch') }}
                        </v-toolbar-title>

                        <v-spacer></v-spacer>

                        <v-select
                            v-model="filters.expiryFilter"
                            :items="expiryFilterOptions"
                            label="Filter Ablaufdatum (BSB)"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            hide-details
                            class="mx-2 filter-select"
                            style="max-width: 250px"
                        ></v-select>

                        <v-text-field
                            v-model="search"
                            :label="t('companyView.searchPlaceholder')"
                            prepend-inner-icon="mdi-magnify"
                            hide-details
                            density="comfortable"
                            variant="outlined"
                            clearable
                            style="max-width: 300px"
                            class="search-field"
                        />
                    </v-toolbar>

                    <v-divider></v-divider>

                    <!-- Datentabelle -->
                    <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                    <KTableToolbar
                :filters="kFilters"
                :columns="kCols"
                :shown="kFilters.filtered.value.length"
                :total="(filteredCompanies || []).length"
                :noun="t('company.noun')"
            />
                    <v-data-table
                        :headers="kCols.visible.value"
                        :items="kFilters.filtered.value"
                        :search="search"
                        :items-per-page="50"
                        :group-by="groupBy"
                        item-value="id"
                        :loading="loadingCompanies"
                        hover
                        density="comfortable"
                        show-group-by
                        class="company-table"
                        v-model="kSelected"
                        show-select
                    >
                        <template
                            v-if="!search"
                            v-slot:[`group-header`]="{ item, columns, toggleGroup, isGroupOpen }"
                        >
                            <tr class="group-header-row">
                                <td :colspan="columns.length">
                                    <div class="group-header">
                                        <v-btn
                                            :icon="
                                                isGroupOpen(item)
                                                    ? 'mdi-chevron-down'
                                                    : 'mdi-chevron-right'
                                            "
                                            size="small"
                                            variant="text"
                                            @click="toggleGroup(item)"
                                            class="group-toggle"
                                        ></v-btn>
                                        <span class="text-subtitle-1 font-weight-medium">{{
                                            item.value || 'Unbekannt'
                                        }}</span>
                                        <v-chip
                                            size="small"
                                            label
                                            variant="tonal"
                                            color="primary"
                                            class="ml-2 group-count"
                                        >
                                            {{ getCompanyCountByType(item.value) }}
                                        </v-chip>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template v-slot:[`item.last_fire_protection_inspection`]="{ item }">
                            {{ formatDate(item.last_fire_protection_inspection) }}
                        </template>

                        <template v-slot:[`item.fire_protection_inspection_valid_until`]="{ item }">
                            <span
                                :class="getExpiryClass(item.fire_protection_inspection_valid_until)"
                            >
                                {{ formatDate(item.fire_protection_inspection_valid_until) }}
                            </span>
                        </template>

                        <template v-slot:[`item.name`]="{ item }">
                            <span class="company-name-link" @click="viewCompanyDetails(item)">
                                {{ item.name }}
                            </span>
                        </template>

                        <template v-slot:[`item.extinguisher_count`]="{ item }">
                            <v-chip
                                size="small"
                                label
                                variant="tonal"
                                color="info"
                                class="extinguisher-chip"
                            >
                                {{ item.extinguisher_count }}
                            </v-chip>
                        </template>

                        <template v-slot:[`item.actions`]="{ item }">
                            <div class="action-buttons">
                                <v-tooltip text="Details ansehen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="viewCompanyDetails(item)"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-eye</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>

                                <v-tooltip text="Bearbeiten" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canEdit"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openEditCompanyDialog(item)"
                                            v-bind="props"
                                            class="action-icon"
                                        >
                                            <v-icon size="small">mdi-pencil</v-icon>
                                        </v-btn>
                                    </template>
                                </v-tooltip>

                                <v-tooltip text="Löschen" location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-if="canDelete"
                                            icon
                                            variant="text"
                                            size="small"
                                            @click="openDeleteCompanyDialog(item)"
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

                        <template v-slot:no-data>
                            <div class="empty-state">
                                <v-icon size="64" color="grey-darken-1" class="mb-3"
                                    >mdi-domain-off</v-icon
                                >
                                <p class="text-h6 text-grey">Keine Firmen gefunden.</p>
                                <p class="text-body-2 text-grey mt-2">
                                    Versuchen Sie einen anderen Filter oder eine andere Suche.
                                </p>
                            </div>
                        </template>

                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular
                                    indeterminate
                                    color="primary"
                                    size="32"
                                    class="mr-3"
                                ></v-progress-circular>
                                <span>Lade Firmen...</span>
                            </div>
                        </template>
                    </v-data-table>
                    <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                    <KBulkBar
                        :count="kSelected.length"
                        :shown="kFilters.filtered.value.length"
                        :total="(filteredCompanies || []).length"
                        @clear="kSelected = []"
                    >
                        <template #actions>
                            <v-btn variant="outlined" size="small" @click="kExportSelection">
                                {{ t('kTable.exportSelection') }}
                            </v-btn>
                        </template>
                    </KBulkBar>
                </v-card-text>
            </v-card>
        </template>

        <!-- Komponenten-Dialoge - Always rendered but controlled by v-model -->
        <AuthorityAddCompanyFile
            v-model="newCompanyDialog"
            :newCompanyDialog="newCompanyDialog"
            @company-added="handleCompanyAdded"
            @close="closeAddCompanyDialog"
        />

        <AuthorityEditCompanyFile
            v-model="editCompanyDialog"
            :editCompanyDialog="editCompanyDialog"
            :company-to-edit="editedCompany"
            @company-updated="handleCompanyUpdated"
            @close="closeEditCompanyDialog"
        />

        <AuthorityViewCompanyFile
            v-model="viewCompanyDialog"
            :viewCompanyDialog="viewCompanyDialog"
            :company-to-view="selectedCompany"
            @close="closeViewCompanyDialog"
        />

        <!-- Löschen-Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500" class="delete-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Löschen bestätigen
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>Willst du die Firma "{{ companyToDelete?.name }}" wirklich löschen?</p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2">
                        {{ t('cancel') }}
                    </v-btn>

                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingCompany"
                        class="delete-button"
                    >
                        <v-icon class="mr-1">mdi-delete</v-icon>
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

.company-container {
    min-height: 89vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 15% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 30%);
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--k-line);
}

/* Action Section */
.action-section {
    margin-bottom: 24px;
}

.extinguisher-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    transition:
        transform var(--transition-timing),
        box-shadow var(--transition-timing);
}

.extinguisher-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-elevation);
}

.extinguisher-field {
    transition: all var(--transition-timing);
}

.extinguisher-field:focus-within {
    transform: var(--button-hover-translate);
}

.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
}

/* Main Card */
.main-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

/* Filter Toolbar */
.filter-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
}

.filter-select,
.search-field {
    transition: all var(--transition-timing);
}

.filter-select:focus-within,
.search-field:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Company Table */
.company-table {
    margin-top: 0;
}

.company-name-link {
    cursor: pointer;
    color: var(--k-accent);
    transition: all 0.2s ease;
    display: inline-block;
}

.company-name-link:hover {
    text-decoration: underline;
    transform: translateX(2px);
}

.extinguisher-chip {
    min-width: 40px;
    justify-content: center;
}

.group-header {
    display: flex;
    align-items: center;
    padding: 8px 0;
}

.group-header-row {
    background-color: rgba(30, 41, 59, 0.3) !important;
}

.group-toggle {
    margin-right: 8px;
}

.group-count {
    min-width: 32px;
    justify-content: center;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 4px;
}

.action-icon {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty & Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styling */
.delete-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-card {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #991b1b, #dc2626);
    color: var(--k-ink);
    padding: 16px;
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.delete-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

/* Text Colors for Expiry */
.text-error {
    color: #ef4444;
    font-weight: 600;
}

.text-warning {
    color: #f59e0b;
    font-weight: 500;
}

/* Detail View */
.detail-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    max-width: 1200px;
    margin: 0 auto;
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .filter-toolbar {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
        gap: 12px;
    }

    .filter-toolbar .v-toolbar-title {
        margin-bottom: 8px;
    }

    .filter-select,
    .search-field {
        width: 100%;
        max-width: none !important;
    }

    .action-buttons {
        justify-content: flex-end;
    }

    .page-header {
        text-align: center;
    }
}
</style>
