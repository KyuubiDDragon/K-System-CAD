<script setup lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue';
import apiCllientAuth from '@/api';

const emit = defineEmits(['companyAdded', 'close', 'invoiceAdded', 'closeForm']);

interface InvoiceItem {
    id: number | null;
    description: string;
    price: number;
    quantity: number;
}

interface InvoiceItemOption {
    id: number;
    item_name: string;
    description: string;
    price: number;
}

const invoice = ref({
    title: '',
    customer: '',
    phone_number: '',
    email: '',
    account: '',
    description: '',
    is_delivered: false,
    is_sent: false,
    is_paid: false,
    discount: 0,
    attachments: [],
    items: [] as InvoiceItem[],
    linked_person: null,
    linked_company: null,
    outgoing: 1,
});

const linked = ref('');

const companies = ref([]);
const persons = ref([]);
const selectedCompanyId = ref(null);
const selectedPersonId = ref(null);

const companySearch = ref('');
const personSearch = ref('');

const fetchCompanies = async () => {
    try {
        const response = await apiCllientAuth.get('invoice/?action=getCompanies');
        companies.value = response.data;
    } catch (error) {
        console.error('Fehler beim Laden der Unternehmen:', error);
    }
};

interface Person {
    id: number;
    firstname: string;
    lastname: string;
    phonenumber?: string;
    mail?: string;
}

const fetchPersons = async () => {
    try {
        const response = await apiCllientAuth.get('invoice/?action=getPersons');
        persons.value = response.data.map((person: Person) => ({
            ...person,
            fullName: `${person.firstname} ${person.lastname}`,
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Personen:', error);
    }
};

const clearCompanySelection = () => {
    selectedCompanyId.value = null;
};

const clearPersonSelection = () => {
    selectedPersonId.value = null;
};

const loadCustomerData = async () => {
    if (selectedCompanyId.value) {
        try {
            const response = await apiCllientAuth.get('invoice/?action=getCompanyData', {
                params: {
                    id: selectedCompanyId.value,
                },
            });
            const data = response.data;
            invoice.value.customer = data.name;
            invoice.value.phone_number = data.phonenumber;
            invoice.value.email = data.email;
            invoice.value.linked_person = null;
            invoice.value.linked_company = selectedCompanyId.value;
            linked.value = t('invoiceForm.linkedWithCompany', { name: data.name });
        } catch (error) {
            console.error('Fehler beim Laden der Unternehmensdaten:', error);
        }
    } else if (selectedPersonId.value) {
        try {
            const response = await apiCllientAuth.get('invoice/?action=getPersonData', {
                params: {
                    id: selectedPersonId.value,
                },
            });
            const data = response.data;
            invoice.value.customer = `${data.firstname} ${data.lastname}`;
            invoice.value.phone_number = data.phonenumber;
            invoice.value.email = data.mail;
            invoice.value.linked_person = selectedPersonId.value;
            invoice.value.linked_company = null;
            linked.value = t('invoiceForm.linkedWithPerson', { name: `${data.firstname} ${data.lastname}` });
        } catch (error) {
            console.error('Fehler beim Laden der Personendaten:', error);
        }
    }
};

const itemOptions = ref<InvoiceItemOption[]>([]);

const fetchItems = async () => {
    try {
        const response = await apiCllientAuth.get('invoiceitems/?action=getInvoiceItems');
        itemOptions.value = response.data;
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

const onItemSelected = (index: number, selectedId: number) => {
    const selectedItem = itemOptions.value.find(opt => opt.id === selectedId);
    if (selectedItem) {
        invoice.value.items[index].description = selectedItem.description;
        invoice.value.items[index].price = selectedItem.price;
        invoice.value.items[index].quantity = 1; // Standardmenge
    }
};

const saveInvoice = () => {
    emit('invoiceAdded', invoice.value);
};

const closeForm = () => {
    emit('closeForm');
};

onMounted(() => {
    fetchCompanies();
    fetchPersons();
    fetchItems();
});
</script>

```vue
<template>
    <v-card class="invoice-card elevation-4">
        <v-toolbar flat density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-file-document-plus</v-icon>
                {{ $t('invoiceForm.addTitle') }}
            </v-toolbar-title>
        </v-toolbar>

        <v-card-text class="pa-4">
            <!-- Kundenauswahl -->
            <div class="form-section mb-4">
                <div class="section-title">
                    <v-icon size="small" class="mr-1">mdi-account-search</v-icon>
                    {{ $t('invoiceForm.customerSelection') }}
                </div>

                <v-row>
                    <v-col cols="12" md="5">
                        <v-autocomplete
                            v-model="selectedCompanyId"
                            :items="companies"
                            item-title="name"
                            item-value="id"
                            :label="$t('invoiceForm.selectCompany')"
                            :placeholder="$t('invoiceForm.chooseCompany')"
                            @change="clearPersonSelection"
                            clearable
                            variant="outlined"
                            density="comfortable"
                            
                            prepend-inner-icon="mdi-domain"
                            hide-details
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="12" md="5">
                        <v-autocomplete
                            v-model="selectedPersonId"
                            :items="persons"
                            item-title="fullName"
                            item-value="id"
                            :label="$t('invoiceForm.selectPerson')"
                            :placeholder="$t('invoiceForm.choosePerson')"
                            @change="clearCompanySelection"
                            clearable
                            variant="outlined"
                            density="comfortable"
                            
                            prepend-inner-icon="mdi-account"
                            hide-details
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="12" md="2" class="d-flex align-center">
                        <v-btn
                            @click="loadCustomerData"
                            color="primary"
                            variant="tonal"
                            class="action-button w-100"
                            prepend-icon="mdi-database-import"
                        >
                            {{ $t('invoiceForm.loadData') }}
                        </v-btn>
                    </v-col>
                </v-row>

                <v-alert
                    v-if="linked"
                    density="comfortable"
                    type="info"
                    variant="tonal"
                    class="mt-3"
                    border="start"
                    border-color="info"
                    elevation="2"
                >
                    <div class="text-body-2">
                        <strong>{{ linked }}</strong
                        ><br />
                        <span class="text-caption">{{ $t('invoiceForm.linkedWarning') }}</span>
                    </div>
                </v-alert>
            </div>

            <!-- Status Panel -->
            <div class="status-panel mb-4">
                <div class="d-flex flex-wrap align-center">
                    <v-switch
                        v-model="invoice.is_delivered"
                        :label="$t('invoiceView.delivered')"
                        color="success"
                        density="comfortable"
                        class="mr-3 status-switch"
                    ></v-switch>

                    <v-switch
                        v-model="invoice.is_sent"
                        :label="$t('invoiceView.sent')"
                        color="info"
                        density="comfortable"
                        class="mr-3 status-switch"
                    ></v-switch>

                    <v-switch
                        v-model="invoice.is_paid"
                        :label="$t('invoiceView.paid')"
                        color="success"
                        density="comfortable"
                        class="status-switch"
                    ></v-switch>
                </div>
            </div>

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
                                        v-model="item.id"
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
                                v-model="invoice.attachments"
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
            </v-form>
        </v-card-text>

        <!-- Actions -->
        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="primary" variant="elevated" @click="saveInvoice" class="action-button">
                <v-icon start>mdi-content-save</v-icon>
                {{ $t('invoiceForm.save') }}
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

/* Status Panel */
.status-panel {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    align-items: center;
    padding: 16px;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 12px;
    margin-bottom: 24px;
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

/* Action Buttons */
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
    background: rgba(30, 41, 59, 0.3);
    border-radius: 8px;
    color: var(--k-ink-muted);
}

/* Invoice Items */
.invoice-item {
    background-color: rgba(30, 41, 59, 0.3);
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
    background-color: rgba(30, 41, 59, 0.5);
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

    .status-panel {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
