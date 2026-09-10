<script setup lang="ts">
import { defineComponent, ref, computed, type PropType } from 'vue';
import type { Company } from '@/types/Company';

import { formatAmount } from '@/utils/datetime';
interface Props {
  modelValue?: boolean
  invoiceToView?: Record<string, any>
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'close', 'closeForm']);

const invoice = ref({
    ...props.invoiceToView,
    is_delivered: convertToBoolean(props.invoiceToView.is_delivered),
    is_sent: convertToBoolean(props.invoiceToView.is_sent),
    is_paid: convertToBoolean(props.invoiceToView.is_paid),
    items: Array.isArray(props.invoiceToView.items) ? props.invoiceToView.items : [],
    attachments: Array.isArray(props.invoiceToView.attachments)
        ? props.invoiceToView.attachments
        : [],
    discount: props.invoiceToView.discount || 0,
    title: props.invoiceToView.title || '',
    customer: props.invoiceToView.customer || '',
    phone_number: props.invoiceToView.phone_number || '',
    email: props.invoiceToView.email || '',
    account: props.invoiceToView.account || '',
    description: props.invoiceToView.description || '',
});

// Helper function to correctly convert various value formats to boolean
function convertToBoolean(value) {
    // Convert various formats including numbers, strings, and boolean values
    if (value === true || value === 1 || value === '1' || value === 'true') {
        return true;
    }
    return false;
}

if (typeof props.invoiceToView.attachments === 'string') {
    try {
        invoice.value.attachments = JSON.parse(props.invoiceToView.attachments);
    } catch (e) {
        console.error('Fehler beim Parsen der Anhänge:', e);
        invoice.value.attachments = [];
    }
}

const calculateTotal = computed(() => {
    const subtotal = invoice.value.items.reduce((sum, item) => {
        return sum + item.price * item.quantity;
    }, 0);
    const discount = Number(invoice.value.discount) || 0;
    return subtotal - (parseFloat(invoice.value.discount) || 0);
});

const formattedTotal = computed(() => {
    return `$ ${calculateTotal.value.toFixed(2)}`;
});

const closeForm = () => {
    emit('closeForm');
};

const openAttachment = (attachment: string) => {
    window.open(attachment, '_blank');
};

const isImage = (path: string) => /\.(jpg|jpeg|png|gif|bmp)$/i.test(path);

const getFileName = (path: string): string => {
    return path.split('/').pop() || 'Unbekannt';
};

const copyInvoiceText = async () => {
    const today = new Date();
    const date = today.toLocaleDateString('de-DE');
    const total = formattedTotal.value;

    const itemList = invoice.value.items
        .map(
            item =>
                `${item.item_name} | ${item.description} | ${
                    item.quantity
                } | ${item.price.toFixed(2)}`
        )
        .join('\n');

    const discount = Number(invoice.value.discount).toFixed(2);

    const invoiceText = `
Sehr geehrte Damen und Herren,
anbei finden Sie die Rechnung für die bei Ihnen durchgeführte Dienstleistung.

________________________________________________________
Rechnungsdatum: ${date}

Folgende Dienstleistung wurde durchgeführt:

Gegenstand | Beschreibung | Menge | Preis
${itemList}

Rabatt: ${discount}
Insgesamt: ${total}

________________________________________________________

Wir bitte Sie, den Betrag innerhalb von 3 Tagen zu überweisen, sofern Sie die Rechnung noch nicht beglichen haben.
Bitte beachten Sie, den richtigen Verwendungszweck zu benutzen.

Kontoinhaber: FireGuard Solutions
Kontonummer: 15186374
Verwendungszweck: ${props.invoiceToView.title}
Rechnungsbetrag: ${total}
			`;

    if (navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(invoiceText);
            console.log('Text erfolgreich in die Zwischenablage kopiert');
        } catch (err) {
            console.error('Fehler beim Kopieren des Textes:', err);
        }
    }
};

/* Vorher: `$ ${Number(value).toFixed(2)}` - also "$ 12480.00", mit Punkt als
   Dezimaltrenner und ohne Tausenderpunkt. Der Entwurf will "12.480,00 $". */
const formatCurrency = (value: number | string | null | undefined) => formatAmount(value);

const getFileIcon = path => {
    const extension = path.split('.').pop()?.toLowerCase() || '';

    if (/pdf/i.test(extension)) return 'mdi-file-pdf-box';
    if (/doc|docx/i.test(extension)) return 'mdi-file-word-box';
    if (/xls|xlsx/i.test(extension)) return 'mdi-file-excel-box';
    if (/ppt|pptx/i.test(extension)) return 'mdi-file-powerpoint-box';
    if (/txt/i.test(extension)) return 'mdi-file-document-box';
    if (/zip|rar|7z/i.test(extension)) return 'mdi-zip-box';

    return 'mdi-file-outline';
};
</script>

<template>
    <v-card class="invoice-detail-card">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-file-document-outline</v-icon>
                {{ $t('invoiceForm.viewTitle') }}
            </v-toolbar-title>

            <v-spacer></v-spacer>

            <v-btn
                @click="copyInvoiceText"
                variant="tonal"
                size="small"
                color="primary"
                prepend-icon="mdi-content-copy"
                class="action-button"
            >
                {{ $t('invoiceForm.copyEmail') }}
            </v-btn>
        </v-toolbar>

        <v-card-text class="pa-4">
            <!-- Schnelle Status-Übersicht -->
            <div class="status-panel mb-4">
                <v-chip
                    :color="invoice.outgoing ? 'success' : 'error'"
                    variant="tonal"
                    class="status-chip mr-2 mb-2"
                >
                    <v-icon start size="16">{{
                        invoice.outgoing ? 'mdi-arrow-bottom-left' : 'mdi-arrow-top-right'
                    }}</v-icon>
                    {{ invoice.outgoing ? $t('invoiceView.income') : $t('invoiceView.expense') }}
                </v-chip>

                <div class="d-flex flex-wrap align-center">
                    <v-switch
                        v-model="invoice.is_delivered"
                        :label="$t('invoiceView.delivered')"
                        readonly
                        color="success"
                        density="comfortable"
                        class="mr-3 status-switch"
                    ></v-switch>

                    <v-switch
                        v-model="invoice.is_sent"
                        :label="$t('invoiceView.sent')"
                        readonly
                        color="info"
                        density="comfortable"
                        class="mr-3 status-switch"
                    ></v-switch>

                    <v-switch
                        v-model="invoice.is_paid"
                        :label="$t('invoiceView.paid')"
                        readonly
                        color="success"
                        density="comfortable"
                        class="status-switch"
                    ></v-switch>
                </div>
            </div>

            <!-- Hauptinformationen -->
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
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-format-title"
                            class="readonly-field"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="invoice.customer"
                            :label="$t('invoiceForm.customer')"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-account-outline"
                            class="readonly-field"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="invoice.phone_number"
                            :label="$t('invoiceForm.phone')"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-phone-outline"
                            class="readonly-field"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="invoice.email"
                            :label="$t('invoiceForm.email')"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-email-outline"
                            class="readonly-field"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="invoice.account"
                            :label="$t('invoiceForm.account')"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-bank-outline"
                            class="readonly-field"
                        ></v-text-field>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12">
                        <v-textarea
                            v-model="invoice.description"
                            :label="$t('invoiceForm.description')"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-text-box-outline"
                            rows="3"
                            class="readonly-field"
                        ></v-textarea>
                    </v-col>
                </v-row>
            </div>

            <!-- Rechnungsposten -->
            <div class="form-section mb-4">
                <div class="section-title d-flex justify-space-between align-center">
                    <div>
                        <v-icon size="small" class="mr-1">mdi-cart-outline</v-icon>
                        {{ $t('invoiceForm.itemsSection') }}
                    </div>
                    <div class="text-body-2 text-grey">{{ invoice.items.length }} Posten</div>
                </div>

                <div class="invoice-items">
                    <div
                        v-for="(item, index) in invoice.items"
                        :key="index"
                        class="invoice-item mb-3"
                    >
                        <div class="invoice-item-header">
                            <span class="index-badge">{{ index + 1 }}</span>
                            <span class="item-name">{{ item.item_name }}</span>
                            <span class="item-price">{{
                                formatCurrency(item.price * item.quantity)
                            }}</span>
                        </div>

                        <div class="invoice-item-details">
                            <div class="detail-row">
                                <span class="detail-label">Beschreibung:</span>
                                <span class="detail-value">{{ item.description || '-' }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Preis pro Stück:</span>
                                <span class="detail-value">{{ formatCurrency(item.price) }}</span>
                            </div>

                            <div class="detail-row">
                                <span class="detail-label">Menge:</span>
                                <span class="detail-value">{{ item.quantity }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="invoice.items.length === 0" class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-cart-off</v-icon>
                        <span>{{ $t('invoiceForm.noItems') }}</span>
                    </div>
                </div>

                <div class="invoice-totals">
                    <div class="d-flex justify-space-between align-center mb-2">
                        <span class="font-weight-medium">{{ $t('invoiceForm.discount') }}:</span>
                        <span>{{ formatCurrency(invoice.discount) }}</span>
                    </div>

                    <div class="d-flex justify-space-between align-center total-row">
                        <span class="text-h6 font-weight-bold">{{ $t('invoiceForm.total') }}:</span>
                        <span
                            class="text-h6"
                            :class="{
                                'text-success': invoice.outgoing,
                                'text-error': !invoice.outgoing,
                            }"
                        >
                            {{ formattedTotal }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Anhänge -->
            <div class="form-section">
                <div class="section-title d-flex justify-space-between align-center">
                    <div>
                        <v-icon size="small" class="mr-1">mdi-paperclip</v-icon>
                        {{ $t('invoiceForm.attachments') }}
                    </div>
                    <div class="text-body-2 text-grey">
                        {{ invoice.attachments.length }} {{ $t('invoiceForm.attachments') }}
                    </div>
                </div>

                <v-row>
                    <template v-if="invoice.attachments.length > 0">
                        <v-col
                            v-for="(attachment, index) in invoice.attachments"
                            :key="index"
                            cols="12"
                            sm="6"
                            md="4"
                            lg="3"
                            xl="2"
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

                                    <v-icon
                                        v-else
                                        size="64"
                                        class="attachment-icon"
                                        :icon="getFileIcon(attachment)"
                                    ></v-icon>
                                </div>

                                <v-card-text class="pa-3">
                                    <div class="attachment-name">{{ getFileName(attachment) }}</div>
                                </v-card-text>

                                <v-divider></v-divider>

                                <v-card-actions class="pa-2 justify-center">
                                    <v-btn
                                        variant="text"
                                        size="small"
                                        color="primary"
                                        prepend-icon="mdi-download"
                                        @click="openAttachment(attachment)"
                                        class="download-button"
                                    >
                                    {{ $t('invoiceForm.open') }}
                                    </v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </template>

                    <v-col v-else cols="12">
                        <div class="empty-state">
                            <v-icon size="40" color="grey-darken-1" class="mb-2"
                                >mdi-paperclip-off</v-icon
                            >
                            <span>{{ $t('invoiceForm.noAttachments') }}</span>
                        </div>
                    </v-col>
                </v-row>
            </div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="elevated" @click="closeForm" class="close-button">
                <v-icon start>mdi-close</v-icon>
                {{ $t('invoiceForm.cancel') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>
/* :root Deklaration entfernt - diese Variablen sind bereits in main.scss definiert */

/* Card Styling */
.invoice-detail-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

/* Action Button */
.action-button,
.close-button,
.download-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover,
.close-button:hover,
.download-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

/* Status Panel */
.status-panel {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 12px;
    margin-bottom: 24px;
}

.status-chip {
    min-width: 100px;
    justify-content: center;
}

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

.readonly-field {
    opacity: 0.9;
    transition: all var(--transition-timing);
}

.readonly-field:hover {
    opacity: 1;
    background: rgba(30, 41, 59, 0.3);
}

/* Invoice Items */
.invoice-items {
    margin-bottom: 24px;
}

.invoice-item {
    background: rgba(30, 41, 59, 0.3);
    border-radius: 8px;
    overflow: hidden;
    transition: all var(--transition-timing);
    border: 1px solid var(--k-line);
}

.invoice-item:hover {
    transform: var(--button-hover-translate);
    box-shadow: var(--shadow-elevation);
}

.invoice-item-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: rgba(30, 41, 59, 0.5);
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
    margin-right: 12px;
}

.item-name {
    flex: 1;
    font-weight: 500;
}

.item-price {
    font-weight: 600;
    color: var(--v-theme-primary);
}

.invoice-item-details {
    padding: 12px 16px;
}

.detail-row {
    display: flex;
    margin-bottom: 8px;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    flex: 0 0 140px;
    color: var(--k-ink-muted);
}

.detail-value {
    flex: 1;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 8px;
    color: var(--k-ink-muted);
}

.invoice-totals {
    padding: 16px;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 8px;
    margin-top: 16px;
}

.total-row {
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--k-line);
}

/* Attachments */
.attachment-card {
    height: 230px;
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    background: rgba(30, 41, 59, 0.3) !important;
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
    background: rgba(15, 23, 42, 0.5);
    overflow: hidden;
}

.attachment-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
    .status-panel {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .status-chip {
        margin-bottom: 8px;
    }

    .detail-row {
        flex-direction: column;
        margin-bottom: 12px;
    }

    .detail-label {
        margin-bottom: 4px;
    }

    .invoice-item-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .index-badge {
        margin-bottom: 4px;
    }

    .item-price {
        align-self: flex-end;
    }
}
</style>

