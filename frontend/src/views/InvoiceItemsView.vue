<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from "vue-router";
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { formatAmount } from '@/utils/datetime';
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import type { InvoiceItem } from "@/types/Invoice"; // Adjust path and ensure type exists
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar

// --- Router & Permissions ---
const route = useRoute();
const { t } = useI18n();
const canEdit = computed(() => true); // Replace with route.meta check if needed
const canDelete = computed(() => true); // Replace with route.meta check if needed

// --- Component State ---
const items = ref<InvoiceItem[]>([]); // Use the specific type
const loadingItems = ref(false);
const savingItem = ref(false);
const deletingItem = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const deleteItemDialog = ref(false);
const itemFormRef = ref<any>(null);
const isItemFormValid = ref(false);

// Use InvoiceItem type for form data, make id optional
const initialFormData: Omit<InvoiceItem, 'id' | 'invoice_id'> & { id?: number | null } = {
  item_name: "", description: "", price: 0, quantity: 1
};
const selectedItem = reactive<Omit<InvoiceItem, 'invoice_id'> & { id?: number | null }>({ ...initialFormData });
const isEditing = computed(() => !!selectedItem.id);
const itemToDelete = ref<InvoiceItem | null>(null);

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: "", color: "error" });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
  errorSnackbar.value.message = message;
  errorSnackbar.value.color = color;
  errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const itemHeaders = computed(() => [
  { title: t('invoiceItemsView.headers.name'), key: "item_name", sortable: true },
  { title: t('invoiceItemsView.headers.description'), key: "description", sortable: false },
  { title: t('invoiceItemsView.headers.price'), key: "price", sortable: true, align: 'end' },
  { title: t('invoiceItemsView.headers.quantity'), key: "quantity", sortable: true, align: 'end' },
  { title: t('invoiceItemsView.headers.actions'), key: "actions", sortable: false, align: 'end', width: '120px' },
]);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || t('invoiceItemsView.form.validation.required');
const nonNegativeRule = (value: number | null) => (value !== null && value >= 0) || t('invoiceItemsView.form.validation.nonNegative');
const positiveIntegerRule = (value: number | null) => (value !== null && Number.isInteger(value) && value >= 1) || t('invoiceItemsView.form.validation.positiveInteger');

// --- Data Fetching ---
const fetchItems = async () => {
  loadingItems.value = true;
  try {
      const response = await apiClientAuth.get<{ data: InvoiceItem[] }>("/invoiceitems?action=getInvoiceItems"); // Adjust path/client if needed
      items.value = response.data.data || response.data || [];
  } catch (error: any) {
      console.error("Error fetching items:", error);
      showSnackbar(error.response?.data?.error || t('invoiceItemsView.messages.loadError'), "error");
      items.value = [];
  } finally {
      loadingItems.value = false;
  }
};

// --- Methods ---

// Dialog Openers
const openNewItemDialog = () => {
  Object.assign(selectedItem, { ...initialFormData }); // Reset form
  isItemFormValid.value = false;
  addEditDialog.value = true;
  setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openEditItemDialog = (item: InvoiceItem) => {
  Object.assign(selectedItem, { ...item }); // Load data
  isItemFormValid.value = false;
  addEditDialog.value = true;
  setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const openDeleteItemDialog = (item: InvoiceItem) => {
  itemToDelete.value = item;
  deleteItemDialog.value = true;
};

// Dialog Closers
const closeAddEditDialog = () => {
  addEditDialog.value = false;
};

const closeDeleteItemDialog = () => {
  deleteItemDialog.value = false;
  itemToDelete.value = null;
};

// Save Item (Add/Edit)
const saveItem = async () => {
  if (!isItemFormValid.value) return;
  savingItem.value = true;

  const action = isEditing.value ? 'editInvoiceItem' : 'addInvoiceItem';
  // Ensure numeric fields are numbers
  const payload = {
       ...selectedItem,
       price: Number(selectedItem.price) || 0,
       quantity: Number(selectedItem.quantity) || 1,
   };

  try {
      await apiClientAuth.post(`/invoiceitems?action=${action}`, payload); // Adjust path/client
      closeAddEditDialog();
      await fetchItems(); // Refresh list
      showSnackbar(isEditing.value ? t('invoiceItemsView.messages.updateSuccess') : t('invoiceItemsView.messages.addSuccess'), "success");
  } catch (error: any) {
      console.error(`Error saving item (Action: ${action}):`, error);
      showSnackbar(error.response?.data?.error || t('invoiceItemsView.messages.saveError'), "error");
  } finally {
      savingItem.value = false;
  }
};

// Delete Item
const confirmDeleteItem = async () => {
  if (!itemToDelete.value) return;
  deletingItem.value = true;
  try {
      await apiClientAuth.post('/invoiceitems?action=deleteInvoiceItem', { id: itemToDelete.value.id }); // Adjust path/client
      closeDeleteItemDialog();
      await fetchItems(); // Refresh list
      showSnackbar(t('invoiceItemsView.messages.deleteSuccess'), "success");
  } catch (error: any) {
      console.error("Error deleting item:", error);
      showSnackbar(error.response?.data?.error || t('invoiceItemsView.messages.deleteError'), "error");
  } finally {
      deletingItem.value = false;
  }
};

// --- Utility ---
/* Fest auf de-DE verdrahtet: englische Nutzer bekamen deutsche Trennzeichen.
   Die gemeinsame Fassung richtet sich nach der aktiven Sprache. */
const formatCurrency = (value: number | null | undefined) => formatAmount(value);

// --- Lifecycle Hooks ---
onMounted(fetchItems);

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('InvoiceItemsView', () => unref(itemHeaders) as any);
</script>
<template>
  <ErrorSnackbar v-model="errorSnackbar" />
  <v-container fluid class="invoice-items-container pa-4">
    <v-card class="main-card elevation-4">
      <v-toolbar flat density="comfortable" color="transparent" class="card-toolbar px-4 py-2">
        <v-toolbar-title class="text-h6">
          <v-icon start size="20" class="mr-2">mdi-view-list-outline</v-icon>
          {{ t('invoiceItemsView.title') }}
        </v-toolbar-title>
        
        <v-spacer></v-spacer>
        
        <v-btn
          v-if="canEdit"
          @click="openNewItemDialog"
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          class="action-button"
          elevation="2"
        >
          {{ t('invoiceItemsView.newItem') }}
        </v-btn>
      </v-toolbar>
      
      <v-divider></v-divider>
      
      <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
      <KTableToolbar :columns="kCols" :shown="(items || []).length" />
      <v-data-table
        :headers="kCols.visible.value"
        :items="items"
        item-value="id"
        :loading="loadingItems"
        hover
        density="comfortable"
        class="items-table"
      >
        <template v-slot:[`item.price`]="{ item }">
          <span class="price-value">{{ formatCurrency(item.price) }}</span>
        </template>
        
        <template v-slot:[`item.quantity`]="{ item }">
          <span class="quantity-value">{{ item.quantity }}</span>
        </template>
        
        <template v-slot:[`item.description`]="{ item }">
          <span class="description-text">{{ item.description || '-' }}</span>
        </template>
        
        <template v-slot:[`item.actions`]="{ item }">
          <div class="action-buttons">
            <v-tooltip :text="t('invoiceItemsView.tooltips.edit')" location="top">
              <template v-slot:activator="{ props }">
                <v-btn 
                  v-if="canEdit" 
                  icon 
                  variant="text" 
                  size="small" 
                  @click="openEditItemDialog(item)" 
                  v-bind="props"
                  class="action-icon"
                >
                  <v-icon size="small">mdi-pencil</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            
            <v-tooltip :text="t('invoiceItemsView.tooltips.delete')" location="top">
              <template v-slot:activator="{ props }">
                <v-btn 
                  v-if="canDelete" 
                  icon 
                  variant="text" 
                  size="small" 
                  @click="openDeleteItemDialog(item)" 
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
            <v-icon size="64" color="grey-darken-1" class="mb-3">mdi-clipboard-text-off</v-icon>
            <p class="text-h6 text-grey">{{ t('invoiceItemsView.empty.noItems') }}</p>
          </div>
        </template>
        
        <template v-slot:loading>
          <div class="loading-state">
            <v-progress-circular indeterminate color="primary" size="32" class="mr-3"></v-progress-circular>
            <span>{{ t('invoiceItemsView.loading.loadingItems') }}</span>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="addEditDialog" persistent max-width="700" class="item-dialog">
      <v-card class="dialog-card">
        <v-toolbar flat density="compact" color="primary" class="dialog-toolbar">
          <v-toolbar-title class="text-subtitle-1">
            <v-icon start size="18" class="mr-2">{{ isEditing ? 'mdi-pencil' : 'mdi-plus-circle' }}</v-icon>
            {{ isEditing ? t('invoiceItemsView.form.editTitle') : t('invoiceItemsView.form.addTitle') }}
          </v-toolbar-title>
        </v-toolbar>
        
        <v-form ref="itemFormRef" v-model="isItemFormValid">
          <v-card-text class="pa-4">
            <div class="form-section">
              <v-text-field
                v-model="selectedItem.item_name"
                :label="t('invoiceItemsView.form.itemName')"
                required
                :rules="[requiredRule]"
                variant="outlined"
                density="comfortable"
                bg-color="grey-darken-3"
                prepend-inner-icon="mdi-label"
                class="mb-4"
              ></v-text-field>
              
              <v-textarea
                v-model="selectedItem.description"
                :label="t('invoiceItemsView.form.description')"
                variant="outlined"
                density="comfortable"
                bg-color="grey-darken-3"
                prepend-inner-icon="mdi-text-box-outline"
                rows="3"
                class="mb-4"
              ></v-textarea>
              
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model.number="selectedItem.price"
                    :label="t('invoiceItemsView.form.price')"
                    required
                    type="number"
                    prefix="$"
                    min="0"
                    step="0.01"
                    :rules="[requiredRule, nonNegativeRule]"
                    variant="outlined"
                    density="comfortable"
                    bg-color="grey-darken-3"
                    prepend-inner-icon="mdi-currency-usd"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model.number="selectedItem.quantity"
                    :label="t('invoiceItemsView.form.defaultQuantity')"
                    required
                    type="number"
                    min="1"
                    step="1"
                    :rules="[requiredRule, positiveIntegerRule]"
                    variant="outlined"
                    density="comfortable"
                    bg-color="grey-darken-3"
                    prepend-inner-icon="mdi-numeric"
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>
          </v-card-text>
          
          <v-divider></v-divider>
          
          <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn variant="tonal" @click="closeAddEditDialog" class="action-button">
              <v-icon start>mdi-close</v-icon>
              {{ t('invoiceItemsView.buttons.cancel') }}
            </v-btn>
            <v-btn 
              color="primary" 
              variant="elevated" 
              @click="saveItem" 
              :disabled="!isItemFormValid" 
              :loading="savingItem"
              class="action-button ml-2"
            >
              <v-icon start>mdi-content-save</v-icon>
              {{ t('invoiceItemsView.buttons.save') }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteItemDialog" persistent max-width="500" class="delete-dialog">
      <v-card class="dialog-card">
        <v-card-title class="text-h5 dialog-title">
          <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
          {{ t('invoiceItemsView.dialog.deleteTitle') }}
        </v-card-title>
        
        <v-card-text class="pt-4">
          <p>{{ t('invoiceItemsView.dialog.deleteConfirm', { name: itemToDelete?.item_name }) }}</p>
          <div class="text-caption text-medium-emphasis mt-2">{{ t('invoiceItemsView.dialog.deleteWarning') }}</div>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="closeDeleteItemDialog" class="mr-2">{{ t('invoiceItemsView.buttons.cancel') }}</v-btn>
          <v-btn 
            color="error" 
            variant="elevated" 
            @click="confirmDeleteItem" 
            :loading="deletingItem"
            class="delete-button"
          >
            <v-icon start>mdi-delete</v-icon>
            {{ t('invoiceItemsView.buttons.delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<style scoped>

.invoice-items-container {
  min-height: 89vh;
  background-color: var(--k-canvas);
}

/* Main Card */
.main-card {
  background: var(--card-bg) !important;
  border: 1px solid var(--card-border);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  overflow: hidden;
}

.card-toolbar {
  background-color: var(--k-sunken) !important;
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

/* Table Styling */
.items-table {
  background: transparent !important;
}

.price-value {
  font-weight: 500;
  color: var(--v-theme-success);
}

.quantity-value {
  font-weight: 500;
}

.description-text {
  color: var(--k-ink-muted);
  font-size: 0.9rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Action Icons */
.action-buttons {
  display: flex;
  gap: 4px;
}

.action-icon {
  opacity: 0.7;
  transition: all 0.2s;
}

.action-icon:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* Empty and Loading States */
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
.dialog-card {
  background: var(--k-surface) !important;
  border: 1px solid var(--card-border);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  overflow: hidden;
}

.dialog-toolbar {
  border-bottom: 1px solid var(--card-border);
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

/* Form Section */
.form-section {
  padding: 8px 0;
}

/* Responsive adjustments */
@media (max-width: 600px) {
  .action-buttons {
    flex-direction: column;
  }
  
  .dialog-card {
    margin: 0 12px;
  }
}
</style>
