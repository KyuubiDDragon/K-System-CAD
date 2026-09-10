<script setup lang="ts">
import { defineComponent, ref, onMounted, type PropType } from 'vue';
import { apiClientAuth } from '@/api';
import type { Company, CompanyType } from '@/types/Company'; // Adjust path and ensure types exist

interface Props {
  modelValue?: boolean
  companyToEdit?: Record<string, any>
  invoiceToEdit?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  invoiceToEdit: () => ({
            items: [],
            attachments: [],
        })
});

const emit = defineEmits([
    'update:modelValue',
    'companyUpdated',
    'close',
    'invoiceUpdated',
    'closeForm',
]);

console.log('Props invoice:', props.invoiceToEdit);
const invoice = ref({
    ...props.invoiceToEdit,
    items: Array.isArray(props.invoiceToEdit.items) ? props.invoiceToEdit.items : [],
    attachments: Array.isArray(props.invoiceToEdit.attachments)
        ? props.invoiceToEdit.attachments
        : [],
    newAttachments: [] as File[],
    removedAttachments: [] as string[],
    discount: props.invoiceToEdit.discount || 0,
    is_delivered: convertToBoolean(props.invoiceToEdit.is_delivered),
    is_sent: convertToBoolean(props.invoiceToEdit.is_sent),
    is_paid: convertToBoolean(props.invoiceToEdit.is_paid),
    outgoing: props.invoiceToEdit.outgoing || 0,
    title: props.invoiceToEdit.title || '',
    customer: props.invoiceToEdit.customer || '',
    phone_number: props.invoiceToEdit.phone_number || '',
    email: props.invoiceToEdit.email || '',
    account: props.invoiceToEdit.account || '',
    description: props.invoiceToEdit.description || '',
});

// Helper function to correctly convert various formats to boolean
function convertToBoolean(value: any): boolean {
    console.log("Converting value:", value, "Type:", typeof value);
    
    if (value === true || value === 1 || value === '1' || value === 'true') {
        return true;
    }
    return false;
}

const isImage = (path: string) => /\.(jpg|jpeg|png|gif|bmp)$/i.test(path);

// Datei aus dem Pfad extrahieren
const getFileName = (path: string): string => {
    return path.split('/').pop() || 'Unbekannt';
};

// Anhang öffnen
const openAttachment = (attachment: string) => {
    window.open(attachment, '_blank');
};

if (typeof props.invoiceToEdit.attachments === 'string') {
    try {
        invoice.value.attachments = JSON.parse(props.invoiceToEdit.attachments);
    } catch (e) {
        console.error('Fehler beim Parsen der Anhänge:', e);
        invoice.value.attachments = [];
    }
}

interface ItemOption {
    id: number;
    item_name: string;
    description: string;
    price: number;
}

const itemOptions = ref<ItemOption[]>([]);

interface InvoiceItem {
    id: string | number;
    item_name: string;
    description: string;
    price: number;
}

const fetchItems = async () => {
    try {
        const response = await apiClientAuth.get('/invoiceitems/?action=getInvoiceItems');
        itemOptions.value = response.data.map((item: InvoiceItem) => ({
            id: Number(item.id),
            item_name: item.item_name,
            description: item.description,
            price: item.price,
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Gegenstände:', error);
    }
};

const addItem = () => {
    invoice.value.items.push({
        id: null,
        description: '',
        price: 0,
        quantity: 1,
    });
};

const removeItem = (index: number) => {
    invoice.value.items.splice(index, 1);
};

const removeAttachment = (index: number) => {
    const removed = invoice.value.attachments.splice(index, 1)[0];
    invoice.value.removedAttachments.push(removed); // Entfernten Anhang speichern
};

const onItemSelected = (index: number, selectedId: number) => {
    const selectedItem = itemOptions.value.find(opt => opt.id === selectedId);
    if (selectedItem) {
        invoice.value.items[index].item_id = selectedItem.id;
        invoice.value.items[index].item_name = selectedItem.item_name;
        invoice.value.items[index].description = selectedItem.description;
        invoice.value.items[index].price = selectedItem.price;
    } else {
        console.warn(`Item with ID ${selectedId} not found in itemOptions`);
    }
};

const updateInvoice = () => {
    emit('invoiceUpdated', invoice.value);
};

const closeForm = () => {
    emit('closeForm');
};

onMounted(fetchItems);
</script>

```vue
<template>
    <v-card class="invoice-card elevation-4">
        <v-toolbar flat density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-file-document-edit</v-icon>
                {{ $t('invoiceForm.editTitle') }}
            </v-toolbar-title>

            <v-spacer></v-spacer>

            <div class="d-flex align-center">
                <v-switch
                    v-model="invoice.is_delivered"
                    :label="$t('invoiceView.delivered')"
                    hide-details
                    density="comfortable"
                    color="success"
                    class="status-switch mr-2"
                ></v-switch>

                <v-switch
                    v-model="invoice.is_sent"
                    :label="$t('invoiceView.sent')"
                    hide-details
                    density="comfortable"
                    color="info"
                    class="status-switch mr-2"
                ></v-switch>

                <v-switch
                    v-model="invoice.is_paid"
                    :label="$t('invoiceView.paid')"
                    hide-details
                    density="comfortable"
                    color="success"
                    class="status-switch"
                ></v-switch>
            </div>
        </v-toolbar>

        <v-card-text class="pa-4">
            <v-form ref="form">
                <!-- System selection -->
                <div class="form-section mb-4">
                    <div class="section-title">
                        <v-icon size="small" class="mr-1">mdi-cash-register</v-icon>
                        {{ $t('invoiceForm.transactionType') }}
                    </div>

                    <v-select
                        :label="$t('invoiceForm.selectSystem')"
                        :items="[
                            { text: $t('invoiceView.expense'), value: 0 },
                            { text: $t('invoiceView.income'), value: 1 },
                        ]"
                        item-title="text"
                        item-value="value"
                        v-model="invoice.outgoing"
                        variant="outlined"
                        density="comfortable"
                        
                        class="mb-0"
                    ></v-select>
                </div>

                <!-- Basic details -->
                <div class="form-section mb-4">
                    <div class="section-title">
                        <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
                        {{ $t('invoiceForm.basicData') }}
                    </div>

                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                v-model="invoice.title"
                                :label="$t('invoiceForm.title')"
                                required
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-format-title"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="invoice.customer"
                                :label="$t('invoiceForm.customer')"
                                required
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-account"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="invoice.phone_number"
                                :label="$t('invoiceForm.phone')"
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-phone"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="invoice.email"
                                :label="$t('invoiceForm.email')"
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-email"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" sm="6" md="3">
                            <v-text-field
                                v-model="invoice.account"
                                :label="$t('invoiceForm.account')"
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-bank"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <v-textarea
                                v-model="invoice.description"
                                :label="$t('invoiceForm.description')"
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-text-box"
                                rows="3"
                            ></v-textarea>
                        </v-col>
                    </v-row>
                </div>

                <!-- Items section -->
                <div class="form-section mb-4">
                    <div class="section-title d-flex justify-space-between align-center">
                        <div>
                            <v-icon size="small" class="mr-1">mdi-cart-outline</v-icon>
                            {{ $t('invoiceForm.itemsSection') }}
                        </div>

                        <v-btn
                            @click="addItem"
                            color="primary"
                            variant="tonal"
                            size="small"
                            prepend-icon="mdi-plus"
                            class="action-button"
                        >
                            {{ $t('invoiceForm.addItem') }}
                        </v-btn>
                    </div>

                    <div v-if="invoice.items.length === 0" class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-cart-off</v-icon>
                        <span>{{ $t('invoiceForm.noItems') }}</span>
                    </div>

                    <div
                        v-for="(item, index) in invoice.items"
                        :key="index"
                        class="invoice-item mb-3"
                    >
                        <div class="item-header">
                            <span class="index-badge">{{ index + 1 }}</span>
                            <v-spacer></v-spacer>
                            <v-btn
                                icon
                                color="error"
                                size="small"
                                variant="text"
                                @click="removeItem(index)"
                                class="delete-button"
                            >
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </div>

                        <div class="item-content pa-3">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="invoice.items[index].item_id"
                                        :items="itemOptions"
                                        item-title="item_name"
                                        item-value="id"
                                        :label="$t('invoiceForm.item')"
                                        :placeholder="$t('invoiceForm.chooseItem')"
                                        required
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        @update:modelValue="onItemSelected(index, $event)"
                                    ></v-select>
                                </v-col>

                                <v-col cols="12" md="4">
                                    <v-text-field
                                        v-model="item.description"
                                        :label="$t('invoiceForm.itemDescription')"
                                        :placeholder="$t('invoiceForm.itemDescription')"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6" md="2">
                                    <v-text-field
                                        v-model="item.price"
                                        :label="$t('invoiceForm.price')"
                                        type="number"
                                        placeholder="0.00"
                                        required
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-currency-usd"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6" md="2">
                                    <v-text-field
                                        v-model="item.quantity"
                                        :label="$t('invoiceForm.quantity')"
                                        type="number"
                                        placeholder="1"
                                        required
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-numeric"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </div>
                    </div>
                </div>

                <!-- Additional options -->
                <div class="form-section mb-4">
                    <div class="section-title">
                        <v-icon size="small" class="mr-1">mdi-cog-outline</v-icon>
                        {{ $t('invoiceForm.additionalOptions') }}
                    </div>

                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="invoice.discount"
                                :label="$t('invoiceForm.discount')"
                                type="number"
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-inner-icon="mdi-tag-outline"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-file-input
                                v-model="invoice.newAttachments"
                                :label="$t('invoiceForm.attachments')"
                                multiple
                                variant="outlined"
                                density="comfortable"
                                
                                prepend-icon="mdi-paperclip"
                                :show-size="true"
                            ></v-file-input>
                        </v-col>
                    </v-row>
                </div>

                <!-- Existing attachments -->
                <div class="form-section">
                    <div class="section-title d-flex justify-space-between align-center">
                        <div>
                            <v-icon size="small" class="mr-1">mdi-attachment</v-icon>
                            {{ $t('invoiceForm.existingAttachments') }}
                        </div>
                        <div class="text-body-2 text-grey">
                            {{ invoice.attachments.length }} {{ $t('invoiceForm.attachments') }}
                        </div>
                    </div>

                    <v-row v-if="invoice.attachments.length > 0">
                        <v-col
                            v-for="(attachment, index) in invoice.attachments"
                            :key="index"
                            cols="12"
                            sm="6"
                            md="4"
                            lg="3"
                        >
                            <v-card class="attachment-card" elevation="2">
                                <div class="attachment-preview">
                                    <v-img
                                        v-if="isImage(attachment)"
                                        :src="attachment"
                                        height="140"
                                        cover
                                        class="attachment-image"
                                    ></v-img>

                                    <v-icon v-else size="64" class="attachment-icon"
                                        >mdi-file-outline</v-icon
                                    >
                                </div>

                                <v-card-text class="pa-3">
                                    <div class="attachment-name">{{ getFileName(attachment) }}</div>
                                </v-card-text>

                                <v-divider></v-divider>

                                <v-card-actions class="pa-2">
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        icon
                                        size="small"
                                        variant="text"
                                        @click="openAttachment(attachment)"
                                        color="primary"
                                        class="action-icon"
                                    >
                                        <v-icon>mdi-download</v-icon>
                                    </v-btn>

                                    <v-btn
                                        icon
                                        size="small"
                                        variant="text"
                                        @click="removeAttachment(index)"
                                        color="error"
                                        class="action-icon"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                    <v-spacer></v-spacer>
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>

                    <div v-else class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-attachment-off</v-icon
                        >
                        <span>{{ $t('invoiceForm.noAttachments') }}</span>
                    </div>
                </div>
            </v-form>
        </v-card-text>

        <!-- Aktionen -->
        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="elevated" @click="updateInvoice" class="action-button">
                <v-icon start>mdi-content-save</v-icon>
                {{ $t('invoiceForm.update') }}
            </v-btn>
            <v-btn variant="tonal" @click="closeForm" class="action-button ml-2">
                <v-icon start>mdi-close</v-icon>
                {{ $t('invoiceForm.cancel') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>

/* Card Styling */
.invoice-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

/* Status Switches */
.status-switch {
    margin-bottom: 0;
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

/* Action Buttons */
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

.action-icon {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: var(--k-sunken);
    border-radius: 8px;
    color: var(--k-ink-muted);
}

/* Invoice Items */
.invoice-item {
    background-color: var(--k-sunken);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--k-line);
    transition: all var(--transition-timing);
}

.invoice-item:hover {
    transform: var(--button-hover-translate);
    box-shadow: var(--shadow-elevation);
}

.item-header {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background-color: var(--k-sunken);
}

.index-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--v-theme-primary);
    color: var(--k-ink);
    font-size: 12px;
    font-weight: bold;
}

.delete-button {
    opacity: 0.7;
    transition: all var(--transition-timing);
}

.delete-button:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Attachments */
.attachment-card {
    height: 250px;
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    background: var(--k-sunken) !important;
    border: 1px solid var(--k-line);
    transition: all var(--transition-timing);
    overflow: hidden;
}

.attachment-card:hover {
    transform: var(--button-hover-translate);
    box-shadow: var(--shadow-elevation);
}

.attachment-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 140px;
    background: var(--k-surface);
    overflow: hidden;
}

.attachment-icon {
    color: var(--v-theme-primary);
}

.attachment-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .item-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .card-toolbar {
        flex-direction: column;
        gap: 8px;
        padding: 16px;
    }

    .status-switch {
        margin-right: 0;
        margin-bottom: 8px;
    }
}
</style>