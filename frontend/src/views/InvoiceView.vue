<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { Invoice, InvoiceItem } from '@/types/Invoice'; // Adjust path and ensure types exist
import { useToast } from 'vue-toastification'; // Import toast

// Import Child Components
import AddInvoiceFile from '@/components/InvoiceFile/Add.vue'; // Adjust path
import EditInvoiceFile from '@/components/InvoiceFile/Edit.vue'; // Adjust path
import ViewInvoiceFile from '@/components/InvoiceFile/View.vue'; // Adjust path

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Router & Permissions
const route = useRoute();

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Component State ---
const invoices = ref<Invoice[]>([]);
const search = ref('');
const loadingInvoices = ref(false);
const savingInvoice = ref(false); // General saving state for add/edit
const deletingInvoice = ref(false);

// --- Dialog States & Data ---
const showAddInvoice = ref(false);
const editInvoiceDialog = ref(false);
const viewInvoiceDialog = ref(false);
const confirmDeleteDialog = ref(false);
const selectedInvoice = ref<Invoice | null>(null); // For Edit/View
const selectedCompany = ref<any>(null); // For Edit invoice company data
const invoiceToDelete = ref<Invoice | null>(null); // For Delete confirmation

// --- Toastification ---
const toast = useToast();
const { t } = useI18n();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Table Headers ---
const invoiceHeaders = computed(() => [
    { title: t('invoiceView.type'), key: 'outgoing', sortable: true, width: '120px' },
    { title: t('invoiceView.subject'), key: 'title', sortable: true },
    { title: t('invoiceView.customer'), key: 'customer', sortable: true },
    { title: t('invoiceView.delivered'), key: 'is_delivered_date', sortable: true, align: 'end' },
    { title: t('invoiceView.sent'), key: 'is_sent_date', sortable: true, align: 'end' },
    { title: t('invoiceView.paid'), key: 'is_paid_date', sortable: true, align: 'end' },
    { title: t('invoiceView.actions'), key: 'actions', sortable: false, align: 'end', width: '150px' },
] as const);

// --- Computed Properties ---
const filteredInvoices = computed(() => {
    const searchTermLower = search.value.trim().toLowerCase();
    if (!searchTermLower) {
        return invoices.value;
    }
    return invoices.value.filter(
        (invoice: Invoice) =>
            (invoice.customer?.toLowerCase() || '').includes(searchTermLower) ||
            (invoice.title?.toLowerCase() || '').includes(searchTermLower)
    );
});

// --- Data Fetching ---
const fetchInvoices = async () => {
    loadingInvoices.value = true;
    try {
        const response = await apiClientAuth.get<any>('/invoice/?action=getInvoices');
        // Extract invoices array from response, handling both direct array and nested object structure
        const invoicesData = Array.isArray(response.data) ? response.data : (response.data?.invoices || []);
        
        invoices.value = invoicesData
            .map(
                (invoice: any): Invoice => ({
                    ...invoice,
                    // Ensure boolean conversion
                    is_sent: Number(invoice.is_sent) == 1 || invoice.is_sent === true,
                    is_paid:  Number(invoice.is_paid) == 1 || invoice.is_paid === true,
                    is_delivered:  Number(invoice.is_delivered) == 1 || invoice.is_delivered === true,
                    outgoing: Number(invoice.outgoing) == 1 || invoice.outgoing === true,
                    // Format dates or keep original for potential sorting, format in template
                    is_sent_date: invoice.is_sent_date || null, // Keep null if not set
                    is_paid_date: invoice.is_paid_date || null,
                    is_delivered_date: invoice.is_delivered_date || null,
                    // Ensure items is an array
                    items: invoice.items || [],
                })
            )
            .sort((a, b) => b.id - a.id); // Sort by ID descending (newest first)
    } catch (error: any) {
        console.error('Error fetching invoices:', error);
        showSnackbar(error.response?.data?.error || t('invoiceView.errorLoad'), 'error');
        invoices.value = [];
    } finally {
        loadingInvoices.value = false;
    }
};

// --- Methods ---

// Prepare FormData (Shared logic for Add/Edit)
const createInvoiceFormData = (invoiceData: any): FormData => {
    const formData = new FormData();
    for (const [key, value] of Object.entries(invoiceData)) {
        if (key === 'newAttachments' && Array.isArray(value)) {
            value.forEach((file: File | null) => {
                // Allow for null if File object might be null
                if (file instanceof File) {
                    // Check if it's actually a File
                    formData.append('newAttachments[]', file);
                }
            });
        } else if (key === 'removedAttachments' && Array.isArray(value)) {
            value.forEach((attachmentId: number | string) => {
                // Assuming IDs are sent
                formData.append('removedAttachments[]', String(attachmentId));
            });
        } else if (key === 'items' && Array.isArray(value)) {
            value.forEach((item: InvoiceItem, index: number) => {
                for (const [itemKey, itemValue] of Object.entries(item)) {
                    formData.append(`items[${index}][${itemKey}]`, String(itemValue ?? '')); // Handle null/undefined item values
                }
            });
        } else if (value !== null && value !== undefined) {
            // Append other non-null values
            // Convert boolean back to '1'/'0' if backend expects it
            let apiValue = value;
            if (typeof value === 'boolean') {
                apiValue = value ? '1' : '0';
            }
            formData.append(key, String(apiValue));
        }
    }
    return formData;
};

// Add Invoice
const openAddInvoiceForm = () => {
    showAddInvoice.value = true;
};
const closeAddInvoiceForm = () => {
    showAddInvoice.value = false;
};
const handleInvoiceAdded = async (newInvoiceData: any) => {
    savingInvoice.value = true; // Indicate saving
    try {
        const formData = createInvoiceFormData(newInvoiceData);
        await apiClientAuth.post('/invoice/?action=addInvoice', formData, {
            // Adjust path
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await fetchInvoices(); // Refresh list
        closeAddInvoiceForm();
        showSnackbar(t('invoiceView.addSuccess'), 'success');
    } catch (error: any) {
        console.error('Error adding invoice:', error);
        showSnackbar(
            error.response?.data?.error || t('invoiceView.addError'),
            'error'
        );
    } finally {
        savingInvoice.value = false;
    }
};

// Edit Invoice
const openEditInvoiceDialog = (invoice: Invoice) => {
    selectedInvoice.value = { ...invoice }; // Pass copy to Edit component
    editInvoiceDialog.value = true;
};
const closeEditInvoiceDialog = () => {
    editInvoiceDialog.value = false;
    selectedInvoice.value = null;
};
const handleInvoiceUpdated = async (updatedInvoiceData: any) => {
    savingInvoice.value = true;
    try {
        const formData = createInvoiceFormData(updatedInvoiceData);
        await apiClientAuth.post('/invoice/?action=editInvoice', formData, {
            // Adjust path
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await fetchInvoices(); // Refresh list
        closeEditInvoiceDialog();
        showSnackbar(t('invoiceView.updateSuccess'), 'success');
    } catch (error: any) {
        console.error('Error updating invoice:', error);
        showSnackbar(
            error.response?.data?.error || t('invoiceView.updateError'),
            'error'
        );
    } finally {
        savingInvoice.value = false;
    }
};

// View Invoice
const openViewInvoiceDialog = (invoice: Invoice) => {
    selectedInvoice.value = { ...invoice };
    viewInvoiceDialog.value = true;
};
const closeViewInvoiceDialog = () => {
    viewInvoiceDialog.value = false;
    selectedInvoice.value = null;
};

// Delete Invoice
const openConfirmDeleteDialog = (invoice: Invoice) => {
    invoiceToDelete.value = invoice;
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    invoiceToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!invoiceToDelete.value) return;
    deletingInvoice.value = true;
    try {
        await apiClientAuth.post('/invoice/?action=deleteInvoice', { id: invoiceToDelete.value.id }); // Adjust path
        await fetchInvoices(); // Refresh list
        closeConfirmDeleteDialog();
        showSnackbar(t('invoiceView.deleteSuccess'), 'success');
    } catch (error: any) {
        console.error('Error deleting invoice:', error);
        showSnackbar(error.response?.data?.error || t('invoiceView.deleteError'), 'error');
    } finally {
        deletingInvoice.value = false;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string => {
    // Use this format for display, keep YYYY-MM-DD for inputs
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return t('invoiceView.invalidDate'); // Handle cases like "Nicht gesendet"
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch (e) {
        return t('invoiceView.error');
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchInvoices();
    
    // Check for ID from route query or props
    const invoiceId = route.query.id || props.id || props.meta?.id;
    
    if (invoiceId) {
        // Find the invoice with the specified ID
        const invoice = invoices.value.find(inv => inv.id === Number(invoiceId));
        if (invoice) {
            // Open the view dialog for this invoice
            openViewInvoiceDialog(invoice);
        }
    }
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('InvoiceView', () => unref(invoiceHeaders) as any);


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
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'rechnungen' });
}

/**
 * Filter der Leiste. Schalter tragen eine feste Bedingung,
 * Facetten holen ihre Werte aus dem Bestand - nicht aus einer
 * gepflegten Liste, die am Tag ihrer Einfuehrung veraltet waere.
 */
const kFilters = useTableFilters(
    () => (unref(filteredInvoices) as any[]) ?? [],
    [
        {
            key: 'outgoing',
            label: t('invoiceView.filterOutgoing'),
            test: (i: any) => Number(i.outgoing) === 1,
        },
        {
            key: 'open',
            label: t('invoiceView.filterOpen'),
            test: (i: any) => !i.is_paid_date,
        },
    ],
    [
        { field: 'customer', label: t('invoiceView.customer'), emptyLabel: t('invoiceView.withoutCustomer') },
    ],
);

</script>

<template>
    <v-container fluid class="invoice-container pa-4">
        <!-- Header mit Titel und Aktionsbutton -->
        <v-row class="mb-4">
            <v-col cols="12">
                <div class="page-header d-flex align-center justify-space-between flex-wrap">
                    <div>
                        <h1 class="text-h4 font-weight-medium mb-2">
                            <v-icon size="36" class="mr-2">mdi-file-document-outline</v-icon>
                            {{ t('invoiceView.title') }}
                        </h1>
                        <p class="text-body-1 text-medium-emphasis">
                            {{ t('invoiceView.description') }}
                        </p>
                    </div>

                    <v-btn
                        v-if="
                            !showAddInvoice && !editInvoiceDialog && !viewInvoiceDialog && canEdit
                        "
                        @click="openAddInvoiceForm"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-plus"
                        class="action-button"
                    >
                        {{ t('invoiceView.new') }}
                    </v-btn>
                </div>
            </v-col>
        </v-row>

        <!-- Haupttabelle -->
        <v-card
            v-if="!showAddInvoice && !editInvoiceDialog && !viewInvoiceDialog"
            class="main-card elevation-4"
        >
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
                        {{ t('invoiceView.filter') }}
                    </v-toolbar-title>

                    <v-spacer></v-spacer>

                    <v-text-field
                        v-model="search"
                        :label="t('invoiceView.searchPlaceholder')"
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
                :total="(filteredInvoices || []).length"
                :noun="t('invoiceView.noun')"
            />
                <v-data-table
                    :headers="kCols.visible.value"
                    :items="kFilters.filtered.value"
                    :search="search"
                    :items-per-page="25"
                    item-value="id"
                    :loading="loadingInvoices"
                    hover
                    density="comfortable"
                    class="invoice-table"
                    v-model="kSelected"
                    show-select
                >
                    <template v-slot:[`item.outgoing`]="{ item }">
                        <v-chip
                            :color="item.outgoing ? 'success' : 'error'"
                            :text="item.outgoing ? t('invoiceView.income') : t('invoiceView.expense')"
                            size="small"
                            label
                            variant="tonal"
                            class="status-chip"
                        ></v-chip>
                    </template>

                    <template v-slot:[`item.title`]="{ item }">
                        <span class="invoice-title">{{ item.title }}</span>
                    </template>

                    <template v-slot:[`item.customer`]="{ item }">
                        <span class="invoice-customer">{{ item.customer }}</span>
                    </template>

                    <template v-slot:[`item.is_delivered_date`]="{ item }">
                        <v-chip
                            v-if="item.is_delivered_date"
                            size="small"
                            color="grey"
                            variant="tonal"
                            class="date-chip"
                        >
                            {{ formatDate(item.is_delivered_date) }}
                        </v-chip>
                        <span v-else class="status-pending">-</span>
                    </template>

                    <template v-slot:[`item.is_sent_date`]="{ item }">
                        <v-chip
                            v-if="item.is_sent_date"
                            size="small"
                            color="info"
                            variant="tonal"
                            class="date-chip"
                        >
                            {{ formatDate(item.is_sent_date) }}
                        </v-chip>
                        <span v-else class="status-pending">-</span>
                    </template>

                    <template v-slot:[`item.is_paid_date`]="{ item }">
                        <v-chip
                            v-if="item.is_paid_date"
                            size="small"
                            color="success"
                            variant="tonal"
                            class="date-chip"
                        >
                            {{ formatDate(item.is_paid_date) }}
                        </v-chip>
                        <span v-else class="status-pending">-</span>
                    </template>

                    <template v-slot:[`item.actions`]="{ item }">
                        <div class="action-buttons">
                            <v-tooltip :text="t('view')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openViewInvoiceDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-eye</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="t('edit')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openEditInvoiceDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-pencil</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="t('delete')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canDelete"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openConfirmDeleteDialog(item)"
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
                                >mdi-file-document-outline</v-icon
                            >
                            <p class="text-h6 text-grey">{{ t('invoiceView.noData') }}</p>
                            <p class="text-body-2 text-grey mt-2">
                                {{ t('invoiceView.noDataHint') }}
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
                            <span>{{ t('invoiceView.loading') }}</span>
                        </div>
                    </template>
                </v-data-table>
                <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                <KBulkBar
                    :count="kSelected.length"
                    :shown="kFilters.filtered.value.length"
                    :total="(filteredInvoices || []).length"
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

        <!-- Komponenten-Dialoge (unverändert) -->
        <AddInvoiceFile
            v-if="showAddInvoice"
            :model-value="showAddInvoice"
            @update:modelValue="showAddInvoice = $event"
            @invoice-added="handleInvoiceAdded"
            @close-form="closeAddInvoiceForm"
        />
        <EditInvoiceFile
            v-if="editInvoiceDialog"
            :model-value="editInvoiceDialog"
            @update:modelValue="editInvoiceDialog = $event"
            :invoice-to-edit="selectedInvoice"
            :company-to-edit="selectedCompany"
            @invoice-updated="handleInvoiceUpdated"
            @close-form="closeEditInvoiceDialog"
        />

        <ViewInvoiceFile
            v-if="viewInvoiceDialog"
            :model-value="viewInvoiceDialog"
            @update:modelValue="viewInvoiceDialog = $event"
            :invoice-to-view="selectedInvoice"
            :company-to-view="selectedCompany"
            @close-form="closeViewInvoiceDialog"
        />

        <!-- Löschen-Dialog -->
        <v-dialog v-model="confirmDeleteDialog" max-width="500" class="delete-dialog">
            <v-card class="dialog-card">
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ t('invoiceView.confirmDeleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        {{ t('invoiceView.confirmDelete', { title: invoiceToDelete?.title, customer: invoiceToDelete?.customer }) }}
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        {{ t('invoiceView.deleteWarning') }}
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
                        :loading="deletingInvoice"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

.invoice-container {
    min-height: 89vh;
    background-color: var(--k-canvas);
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--k-line);
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
    background-color: var(--k-sunken) !important;
}

.search-field {
    transition: all var(--transition-timing);
}

.search-field:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Invoice Table */
.invoice-table {
    border-radius: 8px;
    overflow: hidden;
}

.invoice-title {
    font-weight: 500;
    color: var(--k-ink);
}

.invoice-customer {
    color: var(--k-ink-muted);
}

.status-chip {
    min-width: 80px;
    justify-content: center;
}

.date-chip {
    min-width: 90px;
    justify-content: center;
}

.status-pending {
    color: var(--k-ink-faint);
    font-style: italic;
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
    background: var(--k-surface) !important;
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
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .page-header .action-button {
        align-self: stretch;
    }

    .filter-toolbar {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
        gap: 12px;
    }

    .filter-toolbar .v-toolbar-title {
        margin-bottom: 8px;
    }

    .search-field {
        width: 100%;
        max-width: none !important;
    }

    .action-buttons {
        justify-content: flex-end;
    }
}
</style>
