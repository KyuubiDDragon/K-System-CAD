<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick, defineAsyncComponent, unref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import type { Category, Document } from '@/types/Document'; // Adjust path if needed
import draggable from 'vuedraggable'; // Use vuedraggable
import { useToast } from 'vue-toastification'; // Import Toastification
import { useI18n } from 'vue-i18n';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
// --- Async Component Imports ---
const DocumentEditor = defineAsyncComponent(
    () => import('@/components/document/DocumentEditor.vue')
); // Adjust path

// --- Store, Router & Permissions ---
const authStore = useAuthStore();
const route = useRoute();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  desktopWindow?: boolean
  id?: number | string
  areaId?: string
  key?: string
  embedded?: boolean // For iframe mode
  hideHeader?: boolean // Hide header in iframe
  readOnly?: boolean // Force read-only mode in iframe
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  desktopWindow: false,
  id: undefined,
  areaId: undefined,
  key: undefined,
  embedded: false,
  hideHeader: false,
  readOnly: false
});

// Check permissions from both route.meta and props (consider readOnly for iframe)
const canEdit = computed(() => !props.readOnly && (props.allPermissions || props.canEdit || !!route.meta.canEdit));
const canDelete = computed(() => !props.readOnly && (props.allPermissions || props.canDelete || !!route.meta.canDelete));
const canCreate = computed(() => !props.readOnly && (props.allPermissions || props.canCreate || !!route.meta.canCreate));
const docSite = computed(() => {
    // First check query params (for iframe usage)
    if (route.query.areaId) {
        console.log('📄 DocumentView - Using areaId from query params:', route.query.areaId);
        return route.query.areaId as string;
    }
    // Then check props directly (for desktop windows)
    if (props.areaId) {
        console.log('📄 DocumentView - Using areaId from props:', props.areaId);
        return props.areaId;
    }
    // Check key in props
    if (props.key) {
        console.log('📄 DocumentView - Using key from props:', props.key);
        return props.key;
    }
    // Then check meta (fallbacks for regular routes)
    if (route.meta.areaId) {
        console.log('📄 DocumentView - Using areaId from route.meta:', route.meta.areaId);
        return route.meta.areaId;
    }
    // Fallback to docType (legacy)
    console.log('📄 DocumentView - Falling back to docType:', route.meta.docType);
    return route.meta.docType as string | undefined;
});

// Check if we're using a numeric ID or a string key
const isAreaId = computed(() => {
    const value = docSite.value;
    return typeof value === 'number' || (typeof value === 'string' && !isNaN(Number(value)) && value !== 'global');
});

// Get the area key from route meta (for shortcuts navigation)
const areaKey = computed(() => route.meta.docType as string | undefined);

// --- Component State ---
const search = ref('');
const categories = ref<Category[]>([]); // Holds nested structure
const loadingData = ref(false);
const savingCategory = ref(false);
const savingDocument = ref(false); // Now handled within DocumentEditor? Assume so for now.
const savingSort = ref(false);
const deletingItem = ref(false); // Combined delete loading state

// Document editor states
const selectedDocumentPreview = ref<string | null>(null);
const selectedDocumentEmployeeDocument = ref<any | null>(null); // Using 'any' type for Employee, adjust if needed

// View Mode
const viewMode = ref<'tiles' | 'table'>('tiles'); // Default view

// --- Dialog States & Data ---
const selectedDocument = ref<Document | null>(null); // Document passed to editor
const showSortingModal = ref(false);
const addCategoryDialog = ref(false);
const deleteConfirmationDialog = ref(false);
const itemToDelete = ref<{ item: Document | Category; type: 'document' | 'category' } | null>(null);
const draggableCategories = ref<Category[]>([]); // Separate list for sorting modal

// Add Category Form
const addCategoryFormRef = ref<any>(null);
const isAddCategoryFormValid = ref(false);
const newCategory = reactive({ name: '', site: docSite.value }); // Include site

// Inline Editing
const editingField = ref<{ type: string; id: number }>({ type: '', id: -1 });
const editedValue = ref('');
const editFieldRef = ref<any>(null);

// --- Toastification ---
const toast = useToast();
const { t } = useI18n();

// --- Table Headers & Grouping (for Table View) ---
const tableHeaders = computed(() => [
    { title: 'Titel', key: 'title', sortable: true },
    { title: 'Ersteller', key: 'creator_name', sortable: true },
    { title: 'Erstellt am', key: 'created_at', sortable: true, align: 'end' },
    { title: 'Letzte Änderung', key: 'updated_at', sortable: true, optional: true, align: 'end' },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' },
]);
const groupByCategory = [{ key: 'categoryName', order: 'asc' as const }];

// --- Computed Properties ---

// Flattened list for table view
const flattenedDocuments = computed(() => {
    return categories.value.flatMap(category =>
        (category.documents || []).map(document => ({
            ...document,
            categoryName: category.name, // Add category name for grouping
        }))
    );
});

// Filtered categories (for tiles view)
const filteredCategories = computed(() => {
    const searchTermLower = search.value.trim().toLowerCase();
    if (!searchTermLower) {
        return categories.value; // Return all if no search term
    }
    return categories.value
        .map(category => ({
            ...category,
            documents: (category.documents || []).filter(doc =>
                doc.title.toLowerCase().includes(searchTermLower)
            ),
        }))
        .filter(
            category =>
                category.documents.length > 0 ||
                category.name.toLowerCase().includes(searchTermLower)
        );
});

// Filtered flattened documents (for table view)
const filteredFlattenedDocuments = computed(() => {
    const searchTermLower = search.value.trim().toLowerCase();
    if (!searchTermLower) {
        return flattenedDocuments.value;
    }
    return flattenedDocuments.value.filter(
        doc =>
            doc.title.toLowerCase().includes(searchTermLower) ||
            (doc.creator_name && doc.creator_name.toLowerCase().includes(searchTermLower)) ||
            (doc.categoryName && doc.categoryName.toLowerCase().includes(searchTermLower))
    );
});

// Delete Dialog Content
const deleteDialogContent = computed(() => {
    if (!itemToDelete.value) return { title: 'Löschen', text: '' };
    const typeText = itemToDelete.value.type === 'category' ? 'die Kategorie' : 'das Dokument';
    const name =
        itemToDelete.value.type === 'category'
            ? (itemToDelete.value.item as Category).name
            : (itemToDelete.value.item as Document).title || '';
    const extraWarning =
        itemToDelete.value.type === 'category' &&
        (itemToDelete.value.item as Category).documents?.length > 0
            ? '<br><strong>Achtung:</strong> Alle enthaltenen Dokumente werden ebenfalls gelöscht!'
            : '';
    return {
        title: `${typeText.charAt(0).toUpperCase() + typeText.slice(1)} löschen`,
        text: `Willst du ${typeText} "${name}" wirklich löschen? ${extraWarning}`,
    };
});

// --- Data Fetching ---
const fetchCategoriesAndDocuments = async () => {
    if (!docSite.value) {
        console.error('Document site type (docType) is not defined in route meta.');
        toast.error(t('toast.siteTypeUndefined'));
        return;
    }
    loadingData.value = true;
    try {
        const apiEndpoint = 'document?action=getCategoriesAndDocuments';
        const payload = isAreaId.value 
            ? { area: docSite.value } // Neue API mit area_id
            : { site: docSite.value }; // Alte API mit site
        
        const response = await apiClientAuth.post<Category[]>(
            apiEndpoint,
            payload
        );
        
        // Ensure documents array exists and sort categories/documents
        categories.value = (response.data || response.data || [])
            .map(cat => ({
                ...cat,
                documents: (cat.documents || []).sort(
                    (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)
                ),
            }))
            .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
    } catch (error: any) {
        console.error('Error fetching categories and documents:', error);
        toast.error(error.response?.data?.error || t('toast.loadDocumentsError'));
        categories.value = [];
    } finally {
        loadingData.value = false;
    }
};

// --- Validation Rules ---
/**
 * Pflichtfeld-Regel.
 *
 * Der Entwurf verlangt, dass eine Meldung die Ursache benennt: "Diese
 * Dienstnummer ist bereits vergeben" statt "Ungueltige Eingabe". Fuer ein
 * fehlendes Pflichtfeld heisst das, den Namen des Feldes zu nennen - er steht
 * ohnehin als Beschriftung daneben.
 */
const requiredRule = (feld?: string) => (value: any) =>
    (value !== null && value !== undefined && String(value).trim() !== '') ||
    (feld ? `${feld} fehlt.` : 'Dieses Feld muss ausgefüllt werden.');

// --- Methods ---

// View Mode
const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'tiles' ? 'table' : 'tiles';
    // Optionally save preference to user settings via Pinia action -> API call
    // authStore.updateUserPreferences({ documentView: viewMode.value });
};

// Document Editor Handling
const openDocumentEditor = (document: Document | null, isNew: boolean = false, event?: Event) => {
    // Prevent event bubbling to avoid double-clicks
    if (event) {
        event.stopPropagation();
    }
    
    if (isNew) {
        selectedDocument.value = {
            id: -1, // Temporary ID for new document
            title: '',
            content: '',
            category_id: categories.value[0]?.id ?? 1, // Default to first category or 1
            sort_order: 0,
            notes: '',
            creator: 0,
            creator_name: '', // Will be set by backend
            updated_at: '',
            updated_at_user: '',
            created_at: '',
            site: docSite.value, // Include site
            view_type: 'document', // Default to document view
            spreadsheet_data: null,
            default_view: 'document',
        };
        // Notify parent window
        sendMessageToParent('documentOpened', { documentId: null, isNew: true });
    } else if (document) {
        selectedDocument.value = { ...document }; // Pass a copy
        logAccess('documents', document.id); // Log access for existing docs
        // Notify parent window
        sendMessageToParent('documentOpened', { documentId: document.id, title: document.title });
    }
};

const resetSelectedDocument = () => {
    selectedDocument.value = null;
};

// Event handler when DocumentEditor saves (either add or update)
const handleDocumentSaved = () => {
    resetSelectedDocument(); // Close the editor
    fetchCategoriesAndDocuments(); // Refresh data
    // Notify parent window
    sendMessageToParent('documentSaved', { success: true });
    // Snackbar message is handled within the updateSelectedDocument/add logic now
};

// This function is called by the editor on save, determines add vs update
const updateSelectedDocument = async (updatedDocument: Document) => {
    savingDocument.value = true;
    const isNew = updatedDocument.id === -1;
    const action = isNew ? 'addDocument' : 'updateDocument';

    // Erstelle Payload: Entweder mit site oder area_id, je nach Route
    const payload = isNew
        ? { 
            ...updatedDocument, 
            id: undefined,
            ...(isAreaId.value 
                ? { area_id: docSite.value } 
                : { site: docSite.value })
        }
        : { 
            ...updatedDocument,
            ...(isAreaId.value 
                ? { area_id: docSite.value } 
                : { site: docSite.value })
        };

    try {
        await apiClientAuth.post(`document?action=${action}`, payload);
        await fetchCategoriesAndDocuments();
        toast.success(isNew ? t('toast.documentAddSuccess') : t('toast.documentUpdateSuccess'));
    } catch (error: any) {
        console.error(`Error ${action}:`, error);
        toast.error(
            error.response?.data?.error ||
                (isNew ? t('toast.documentAddError') : t('toast.documentUpdateError'))
        );
    } finally {
        savingDocument.value = false;
    }
};

// Category CRUD & Sorting
const openAddCategoryDialog = (event?: Event) => {
    // Stop event propagation to prevent double-click issues
    if (event) {
        event.stopPropagation();
    }

    Object.assign(newCategory, { name: '', site: docSite.value }); // Reset form
    isAddCategoryFormValid.value = false;
    addCategoryDialog.value = true;
    setTimeout(() => addCategoryFormRef.value?.resetValidation(), 100);
};

const addNewCategory = async () => {
    if (!isAddCategoryFormValid.value) return;
    savingCategory.value = true;
    try {
        // Payload abhängig von Route erstellen
        const payload = isAreaId.value
            ? { name: newCategory.name, area_id: docSite.value }
            : { name: newCategory.name, site: docSite.value };
            
        await apiClientAuth.post('/document?action=addCategorie', payload);
        addCategoryDialog.value = false;
        await fetchCategoriesAndDocuments();
        toast.success(t('toast.categoryAddSuccess'));
    } catch (error: any) {
        console.error('Error adding category:', error);
        toast.error(error.response?.data?.error || t('toast.categoryAddError'));
    } finally {
        savingCategory.value = false;
    }
};

const saveCategorySorting = async () => {
    savingSort.value = true;
    const sortedData = draggableCategories.value.map((cat, index) => ({
        id: cat.id,
        sort_order: index,
    }));
    try {
        // Payload abhängig von Route erstellen
        const payload = isAreaId.value
            ? { 
                categories: sortedData,
                area_id: docSite.value 
              }
            : { 
                categories: sortedData,
                site: docSite.value 
              };
              
        await apiClientAuth.post('/document?action=saveCategorySorting', payload);
        await fetchCategoriesAndDocuments(); // Refresh data with new order
        toast.success(t('toast.categoryOrderSaved'));
    } catch (error: any) {
        console.error('Error saving category sorting:', error);
        toast.error(
            error.response?.data?.error || t('toast.categoryOrderSaveError')
        );
    } finally {
        savingSort.value = false;
        showSortingModal.value = false;
    }
};

const cancelCategorySorting = () => {
    showSortingModal.value = false;
};

// Document Sorting
const saveDocumentSorting = async (event: any, documentList: Document[], categoryId: number) => {
    savingSort.value = true; // Use a general sort saving indicator
    const sortedData = documentList.map((doc, index) => ({
        id: doc.id,
        sort_order: index,
    }));
    try {
        // Payload abhängig von Route erstellen
        const payload = isAreaId.value
            ? { 
                documents: sortedData,
                category_id: categoryId,
                area_id: docSite.value 
              }
            : { 
                documents: sortedData,
                category_id: categoryId,
                site: docSite.value 
              };
              
        await apiClientAuth.post('/document?action=saveDocumentSorting', payload);
        await fetchCategoriesAndDocuments(); // Refresh data
        toast.success(t('toast.documentOrderSaved'));
    } catch (error: any) {
        console.error('Error saving document sorting:', error);
        toast.error(
            error.response?.data?.error || t('toast.documentOrderSaveError')
        );
        // Revert local changes on error by refetching
        await fetchCategoriesAndDocuments();
    } finally {
        savingSort.value = false;
    }
};

// Inline Category Name Editing
const startEditingField = async (fieldType: string, category: Category) => {
    if (!canEdit.value || fieldType !== 'categoryName') return;
    cancelEditingField();
    editingField.value = { type: fieldType, id: category.id };
    editedValue.value = category.name;
    await nextTick();
    editFieldRef.value?.focus();
};

const cancelEditingField = () => {
    editingField.value = { type: '', id: -1 };
    editedValue.value = '';
};

const updateCategoryName = async (category: Category) => {
    const currentEditedId = editingField.value.id;
    const currentEditedValue = editedValue.value.trim();

    if (
        category.id !== currentEditedId ||
        category.name === currentEditedValue ||
        !currentEditedValue
    ) {
        cancelEditingField();
        return;
    }

    const originalName = category.name;
    category.name = currentEditedValue; // Optimistic update
    cancelEditingField();

    try {
        // Payload abhängig von Route erstellen
        const payload = isAreaId.value
            ? { 
                id: category.id,
                name: currentEditedValue,
                area_id: docSite.value 
              }
            : { 
                id: category.id,
                name: currentEditedValue,
                site: docSite.value 
              };
              
        await apiClientAuth.post('/document?action=updateCategoryName', payload);
        toast.success(t('toast.categoryNameUpdated'));
        // Refresh might be needed if flattened list relies on fetched names
        await fetchCategoriesAndDocuments();
    } catch (error: any) {
        console.error('Error updating category name:', error);
        toast.error(
            error.response?.data?.error || t('toast.categoryNameUpdateError')
        );
        category.name = originalName; // Revert optimistic update
    }
};

// Delete Confirmation Logic
const openDeleteConfirmationDialog = (
    item: Document | Category,
    type?: 'document' | 'category'
) => {
    // If type isn't passed, infer from item properties (less robust)
    const determinedType = type || ('content' in item ? 'document' : 'category');
    itemToDelete.value = { item, type: determinedType };
    deleteConfirmationDialog.value = true;
};

const closeDeleteConfirmationDialog = () => {
    deleteConfirmationDialog.value = false;
    itemToDelete.value = null;
};

const confirmDelete = async () => {
    if (!itemToDelete.value) return;
    deletingItem.value = true;
    const { item, type } = itemToDelete.value;
    const action = type === 'document' ? 'deleteDocument' : 'deleteCategorie'; // API actions
    const id = item.id;

    try {
        // Payload abhängig von Route erstellen
        const payload = isAreaId.value
            ? { id, area_id: docSite.value }
            : { id, site: docSite.value };
            
        await apiClientAuth.post(`document?action=${action}`, payload);
        closeDeleteConfirmationDialog();
        await fetchCategoriesAndDocuments(); // Refresh data
        toast.success(t('toast.deleteSuccess', { type: type === 'document' ? 'Dokument' : 'Kategorie' }));
    } catch (error: any) {
        console.error(`Error deleting ${type}:`, error);
        toast.error(error.response?.data?.error || t('toast.deleteError', { type }));
    } finally {
        deletingItem.value = false;
    }
};

// --- Utility Functions ---
const logAccess = async (table: string, entryId: number) => {
    /* ... (implementation from previous component, using apiClientAuth) ... */
};

const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Return original if invalid (e.g., 'Nicht gesendet')
        return date.toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' });
    } catch (e) {
        return 'Fehler';
    }
};

// --- Watchers ---
// Watch for route changes to update docSite and refetch data
watch(
    () => route.meta.docType,
    newDocType => {
        if (newDocType) {
            // docSite.value = newDocType as string; // This is handled by the computed property now
            fetchCategoriesAndDocuments();
        }
    },
    { immediate: false }
); // Don't run immediately, onMounted handles initial load

// Watch for showSortingModal state to initialize draggableCategories
watch(showSortingModal, isOpen => {
    if (isOpen) {
        // Create a deep copy of the categories when the modal opens
        draggableCategories.value = JSON.parse(JSON.stringify(categories.value));
    }
});

// --- Iframe Communication ---
const sendMessageToParent = (type: string, data: any) => {
    if (window.parent !== window) {
        window.parent.postMessage({
            type: `documentView:${type}`,
            data,
            source: 'documentView'
        }, '*'); // In production, replace '*' with specific origin
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    // Check for iframe mode from query parameters
    const isEmbedded = route.query.embedded === 'true' || props.embedded;
    if (isEmbedded) {
        // Override props for iframe mode
        const readOnly = route.query.readOnly === 'true' || props.readOnly;
        const hideHeader = route.query.hideHeader === 'true' || props.hideHeader;
        
        // You can handle these settings here
        console.log('Running in iframe mode', { readOnly, hideHeader });
        
        // Notify parent that iframe is ready
        sendMessageToParent('ready', { 
            readOnly, 
            hideHeader,
            areaId: docSite.value 
        });
        
        // Listen for messages from parent
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type) {
                switch (event.data.type) {
                    case 'documentView:openDocument':
                        const doc = flattenedDocuments.value.find(d => d.id === event.data.documentId);
                        if (doc) {
                            openDocumentEditor(doc, false);
                        }
                        break;
                    case 'documentView:refresh':
                        fetchCategoriesAndDocuments();
                        break;
                    case 'documentView:setView':
                        if (event.data.view === 'table' || event.data.view === 'tiles') {
                            viewMode.value = event.data.view;
                        }
                        break;
                }
            }
        });
    }
    
    // Get initial view mode from Pinia store, fallback to 'tiles'
    viewMode.value = authStore.user?.documentView === 'table' ? 'table' : 'tiles';
    
    // Override view mode from query params if provided
    if (route.query.view === 'table' || route.query.view === 'tiles') {
        viewMode.value = route.query.view as 'tiles' | 'table';
    }
    
    // Check for specific areaId from query params (for iframe usage)
    if (route.query.areaId) {
        // This will override the computed docSite value
        console.log('Using areaId from query params:', route.query.areaId);
    }
    
    await fetchCategoriesAndDocuments(); // Fetch initial data based on route meta

    // Check for ID from route query or props
    const documentId = route.query.id || props.id || props.meta?.id;

    if (documentId) {
        // Find the document with the specified ID
        const document = flattenedDocuments.value.find(doc => doc.id === Number(documentId));
        if (document) {
            // Open the document editor for viewing
            openDocumentEditor(document, false);
        }
    }

    // Check for category from route query to scroll to it
    const categoryId = route.query.category;
    if (categoryId) {
        await nextTick(); // Wait for DOM to render
        const categoryElement = document.getElementById(`category-${categoryId}`);
        if (categoryElement) {
            categoryElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
                inline: 'nearest'
            });
            // Optional: Add a temporary highlight
            categoryElement.style.transition = 'box-shadow 0.3s ease';
            categoryElement.style.boxShadow = '0 0 0 3px rgba(33, 150, 243, 0.5)';
            setTimeout(() => {
                categoryElement.style.boxShadow = '';
            }, 2000);
        }
    }
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('DocumentView', () => unref(tableHeaders) as any);


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
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'dokumente' });
}

/**
 * Filter der Leiste. Schalter tragen eine feste Bedingung,
 * Facetten holen ihre Werte aus dem Bestand - nicht aus einer
 * gepflegten Liste, die am Tag ihrer Einfuehrung veraltet waere.
 */
const kFilters = useTableFilters(
    () => (unref(filteredFlattenedDocuments) as any[]) ?? [],
    [],
    [
        { field: 'categoryName', label: t('documentView.category') },
        { field: 'creator_name', label: t('documentView.creator'), emptyLabel: t('documentView.withoutCreator') },
    ],
);

</script>

<template>
    <div class="document-container" :class="{ 'embedded-mode': props.embedded || route.query.embedded === 'true' }">
        <v-container fluid class="pa-4">
            <!-- Header mit Aktionsbuttons - Hidden when editor is open -->
            <div v-if="!props.hideHeader && route.query.hideHeader !== 'true' && selectedDocument === null" class="section-header mb-4">
                <div class="header-actions">
                    <v-btn
                        v-if="canEdit"
                        @click="(e) => openDocumentEditor(null, true, e)"
                        color="primary"
                        variant="elevated"
                        class="mr-2"
                        prepend-icon="mdi-file-plus-outline"
                        size="small"
                    >
                        {{ $t('documentView.addDocument') }}
                    </v-btn>

                    <v-btn
                        v-if="canEdit"
                        @click="openAddCategoryDialog"
                        color="primary"
                        variant="tonal"
                        class="mr-2"
                        prepend-icon="mdi-shape-plus-outline"
                        size="small"
                    >
                        {{ $t('documentView.addCategory') }}
                    </v-btn>

                    <v-btn
                        v-if="canEdit"
                        @click="showSortingModal = true"
                        color="primary"
                        variant="tonal"
                        class="mr-2"
                        prepend-icon="mdi-sort"
                        size="small"
                    >
                        {{ $t('documentView.sortCategories') }}
                    </v-btn>

                    <v-btn
                        @click="toggleViewMode"
                        color="info"
                        variant="tonal"
                        :prepend-icon="
                            viewMode === 'tiles' ? 'mdi-view-list' : 'mdi-view-dashboard-outline'
                        "
                        size="small"
                    >
                        {{ viewMode === 'tiles' ? $t('documentView.tableView') : $t('documentView.tileView') }}
                    </v-btn>

                    <add-shortcut-button
                        v-if="isAreaId && docSite && areaKey"
                        type="document_area"
                        :resource-id="Number(docSite)"
                        :title="areaKey"
                        :subtitle="$t('documentView.documentArea')"
                        icon="mdi-folder-multiple"
                        color="teal"
                        :metadata="{ areaKey: areaKey }"
                    />
                </div>

                <div class="search-field">
                    <v-text-field
                        v-model="search"
                        :label="$t('documentView.searchByTitle')"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-magnify"
                        hide-details
                        clearable
                        color="primary"
                    />
                </div>
            </div>

            <!-- Ladeindikator - Hidden when editor is open -->
            <v-row v-if="loadingData && selectedDocument === null" justify="center" class="my-10">
                <v-col cols="auto" class="text-center">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="64"
                    ></v-progress-circular>
                    <p class="mt-4 text-grey">{{ $t('documentView.loadingDocuments') }}</p>
                </v-col>
            </v-row>

            <!-- Keine Ergebnisse Anzeige - Hidden when editor is open -->
            <v-card
                v-else-if="
                    selectedDocument === null &&
                    ((viewMode === 'tiles' && filteredCategories.length === 0) ||
                    (viewMode === 'table' && filteredFlattenedDocuments.length === 0))
                "
                class="empty-state-card pa-8 mb-3"
                variant="outlined"
            >
                <div class="d-flex flex-column align-center">
                    <v-icon
                        icon="mdi-file-search"
                        size="64"
                        color="grey-darken-1"
                        class="mb-4"
                    ></v-icon>
                    <span class="text-h6 text-grey-darken-1">{{ $t('documentView.noDocumentsFound') }}</span>
                    <span class="text-body-2 text-grey-darken-3 mt-2">
                        {{
                            search
                                ? $t('documentView.adjustSearchFilters')
                                : $t('documentView.noDocumentsAvailable')
                        }}
                    </span>
                    <v-btn
                        v-if="canEdit"
                        color="primary"
                        variant="tonal"
                        class="mt-4"
                        prepend-icon="mdi-file-plus-outline"
                        @click="(e) => openDocumentEditor(null, true, e)"
                    >
                        {{ $t('documentView.addDocument') }}
                    </v-btn>
                </div>
            </v-card>

            <!-- {{ $t('documentView.tileView') }} - Hidden when editor is open -->
            <template v-else-if="viewMode === 'tiles' && !loadingData && selectedDocument === null">
                <template v-for="(category, catIndex) in filteredCategories" :key="category.id">
                    <v-card
                        v-if="category.documents.length > 0 || search === ''"
                        :id="`category-${category.id}`"
                        class="category-card mb-5"
                        variant="outlined"
                        elevation="1"
                    >
                        <div class="category-header px-4 py-3">
                            <template
                                v-if="
                                    editingField.type !== 'categoryName' ||
                                    editingField.id !== category.id
                                "
                            >
                                <h2
                                    @dblclick="
                                        canEdit ? startEditingField('categoryName', category) : null
                                    "
                                    :style="canEdit ? 'cursor: pointer;' : ''"
                                    class="category-title"
                                >
                                    <v-icon icon="mdi-folder" size="small" class="mr-2"></v-icon>
                                    {{ category.name }}
                                    <v-tooltip
                                        v-if="canEdit"
                                        :text="$t('documentView.doubleClickToEdit')"
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-icon
                                                v-bind="props"
                                                size="x-small"
                                                class="ml-1"
                                                color="grey"
                                                >mdi-pencil-outline</v-icon
                                            >
                                        </template>
                                    </v-tooltip>
                                </h2>
                            </template>

                            <template v-else>
                                <v-text-field
                                    v-model="editedValue"
                                    density="compact"
                                    variant="underlined"
                                    single-line
                                    autofocus
                                    hide-details
                                    ref="editFieldRef"
                                    @keydown.enter="updateCategoryName(category)"
                                    @keydown.esc="cancelEditingField"
                                    @blur="updateCategoryName(category)"
                                    class="category-edit-field"
                                    color="primary"
                                ></v-text-field>
                            </template>

                            <v-spacer></v-spacer>
                            <v-tooltip :text="$t('documentView.deleteCategoryTooltip')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canDelete"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openDeleteConfirmationDialog(category, 'category')"
                                        color="error"
                                        :disabled="category.documents.length > 0"
                                        v-bind="props"
                                    >
                                        <v-icon size="small">mdi-delete-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <add-shortcut-button
                                type="document_category"
                                :resource-id="category.id"
                                :title="category.name"
                                :subtitle="areaKey ? `Bereich: ${areaKey}` : undefined"
                                icon="mdi-folder"
                                color="amber"
                                :metadata="areaKey ? { areaId: docSite, areaKey: areaKey } : undefined"
                            />
                        </div>

                        <v-divider></v-divider>

                        <div class="document-grid pa-4" v-if="category.documents.length > 0">
                            <draggable
                                :list="category.documents"
                                group="documents"
                                tag="div"
                                class="document-draggable"
                                :animation="150"
                                item-key="id"
                                @end="
                                    event =>
                                        saveDocumentSorting(event, category.documents, category.id)
                                "
                                :disabled="!canEdit"
                            >
                                <template #item="{ element: document }">
                                    <div class="document-item">
                                        <v-tooltip location="top">
                                            <template v-slot:activator="{ props: tooltipProps }">
                                                <v-card
                                                    class="document-card"
                                                    hover
                                                    @click="(e) => openDocumentEditor(document, false, e)"
                                                    :style="
                                                        canEdit
                                                            ? 'cursor: grab;'
                                                            : 'cursor: pointer;'
                                                    "
                                                    elevation="2"
                                                    v-bind="tooltipProps"
                                                >
                                                    <div class="card-actions">
                                                        <v-tooltip text="Löschen" location="top">
                                                            <template v-slot:activator="{ props }">
                                                                <v-btn
                                                                    v-if="canDelete"
                                                                    icon="mdi-delete-outline"
                                                                    variant="text"
                                                                    size="small"
                                                                    @click.stop="
                                                                        openDeleteConfirmationDialog(
                                                                            document,
                                                                            'document'
                                                                        )
                                                                    "
                                                                    v-bind="props"
                                                                    color="error"
                                                                ></v-btn>
                                                            </template>
                                                        </v-tooltip>
                                                    </div>

                                                    <div class="card-icon">
                                                        <!-- Dynamic icon based on view_type -->
                                                        <v-icon
                                                            v-if="document.view_type === 'spreadsheet'"
                                                            size="50"
                                                            color="success"
                                                        >mdi-table-large</v-icon>
                                                        <v-icon
                                                            v-else-if="document.view_type === 'both'"
                                                            size="50"
                                                            color="info"
                                                        >mdi-file-document-multiple-outline</v-icon>
                                                        <v-icon
                                                            v-else
                                                            size="50"
                                                            color="primary"
                                                        >mdi-file-document-outline</v-icon>
                                                    </div>

                                                    <v-card-title class="document-title">{{
                                                        document.title
                                                    }}</v-card-title>

                                                    <v-card-subtitle class="document-date">
                                                        <div class="date-line">
                                                            <v-icon
                                                                icon="mdi-calendar-plus"
                                                                size="small"
                                                                class="mr-1"
                                                            ></v-icon>
                                                            Erstellt:
                                                            {{ formatDate(document.created_at) }}
                                                        </div>
                                                        <div class="date-line">
                                                            <v-icon
                                                                icon="mdi-calendar-edit"
                                                                size="small"
                                                                class="mr-1"
                                                            ></v-icon>
                                                            Geändert:
                                                            {{ formatDate(document.updated_at) }}
                                                        </div>
                                                        <div
                                                            v-if="document.updated_at_user"
                                                            class="user-modifier pl-6"
                                                        >von {{ document.updated_at_user }}
                                                        </div>
                                                    </v-card-subtitle>
                                                </v-card>
                                            </template>
                                            <span
                                                >{{ document.title }}<br /><small
                                                    >Bearbeiten/Lesen</small
                                                ></span
                                            >
                                        </v-tooltip>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </v-card>
                </template>
            </template>

            <!-- {{ $t('documentView.tableView') }} -->
            <!-- Table View - Hidden when editor is open -->
            <v-card
                v-else-if="
                    selectedDocument === null &&
                    viewMode === 'table' && !loadingData && filteredFlattenedDocuments.length > 0
                "
                class="main-table-card"
                elevation="3"
            >
                <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                <KTableToolbar
                :filters="kFilters"
                :columns="kCols"
                :shown="kFilters.filtered.value.length"
                :total="(filteredFlattenedDocuments || []).length"
                :noun="t('documentView.noun')"
            />
                <v-data-table
                    :headers="kCols.visible.value"
                    :items="kFilters.filtered.value"
                    :group-by="groupByCategory"
                    items-per-page="25"
                    item-value="id"
                    hover
                    density="comfortable"
                    class="document-table"
                    v-model="kSelected"
                    show-select
                >
                    <template v-slot:[`group-header`]="{ item, columns, toggleGroup, isGroupOpen }">
                        <tr class="group-header-row">
                            <td :colspan="columns.length" class="group-header-cell">
                                <div class="d-flex align-center">
                                    <v-btn
                                        :icon="isGroupOpen(item) ? '$expand' : '$next'"
                                        size="small"
                                        variant="text"
                                        @click="toggleGroup(item)"
                                        color="primary"
                                    ></v-btn>
                                    <v-icon icon="mdi-folder" size="small" class="mr-2"></v-icon>
                                    <span class="text-subtitle-1 font-weight-medium">{{
                                        item.value
                                    }}</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template v-slot:[`item.title`]="{ item }">
                        <div class="table-document-title" @click="(e) => openDocumentEditor(item, false, e)">
                            <!-- Dynamic icon based on view_type -->
                            <v-icon
                                v-if="item.view_type === 'spreadsheet'"
                                icon="mdi-table-large"
                                size="small"
                                class="mr-2"
                                color="success"
                            ></v-icon>
                            <v-icon
                                v-else-if="item.view_type === 'both'"
                                icon="mdi-file-document-multiple-outline"
                                size="small"
                                class="mr-2"
                                color="info"
                            ></v-icon>
                            <v-icon
                                v-else
                                icon="mdi-file-document-outline"
                                size="small"
                                class="mr-2"
                            ></v-icon>
                            {{ item.title }}
                        </div>
                    </template>

                    <template v-slot:[`item.created_at`]="{ item }">
                        <span>{{ formatDate(item.created_at) }}</span>
                    </template>

                    <template v-slot:[`item.updated_at`]="{ item }">
                        <span>
                            {{ formatDate(item.updated_at) }}
                        </span>
                    </template>

                    <template v-slot:[`item.actions`]="{ item }">
                        <div class="d-flex justify-end">
                            <v-tooltip text="Bearbeiten/Lesen" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="(e) => openDocumentEditor(item, false, e)"
                                        v-bind="props"
                                        color="primary"
                                        class="action-btn"
                                    >
                                        <v-icon size="small">mdi-file-document-edit-outline</v-icon>
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
                                        @click="openDeleteConfirmationDialog(item, 'document')"
                                        v-bind="props"
                                        color="error"
                                        class="action-btn"
                                    >
                                        <v-icon size="small">mdi-delete</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </template>

                    <template v-slot:no-data>
                        <div class="empty-table-message">
                            {{ $t('documentView.noSearchResults') }}
                        </div>
                    </template>
                </v-data-table>
                <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                <KBulkBar
                    :count="kSelected.length"
                    :shown="kFilters.filtered.value.length"
                    :total="(filteredFlattenedDocuments || []).length"
                    @clear="kSelected = []"
                >
                    <template #actions>
                        <v-btn variant="outlined" size="small" @click="kExportSelection">
                            {{ t('kTable.exportSelection') }}
                        </v-btn>
                    </template>
                </KBulkBar>
            </v-card>
            <DocumentEditor
                v-if="selectedDocument !== null"
                class="document-editor-container"
                :selected-document="selectedDocument"
                :selected-document-preview="selectedDocumentPreview"
                :selected-document-employee-document="selectedDocumentEmployeeDocument"
                :categories="categories"
                :area-id="docSite"
                :area-key="areaKey"
                @update:selectedDocument="updateSelectedDocument"
                @close-document="resetSelectedDocument"
                @document-saved="handleDocumentSaved"
                persistent
            />

            <!-- Dialoge -->

            <!-- Neue Kategorie Dialog -->
            <v-dialog v-model="addCategoryDialog" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-folder-plus" class="mr-2"></v-icon>
                        {{ $t('documentView.newCategoryTitle') }}
                    </v-card-title>
                    <v-form ref="addCategoryFormRef" v-model="isAddCategoryFormValid">
                        <v-card-text class="pa-4">
                            <v-text-field
                                :label="$t('name')"
                                required
                                v-model="newCategory.name"
                                :rules="[requiredRule($t('name'))]"
                                variant="outlined"
                                density="comfortable"
                                @keydown.enter="addNewCategory"
                                bg-color="grey-darken-3"
                                color="primary"
                                prepend-inner-icon="mdi-folder"
                            ></v-text-field>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions class="pa-4">
                            <v-spacer></v-spacer>
                            <v-btn variant="text" @click="addCategoryDialog = false"
                                >{{ $t('cancel') }}</v-btn
                            >
                            <v-btn
                                v-if="canEdit"
                                color="primary"
                                variant="elevated"
                                @click="addNewCategory"
                                :disabled="!isAddCategoryFormValid"
                                :loading="savingCategory"
                            >
                                {{ $t('create') }}
                            </v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-dialog>

            <!-- Kategorien sortieren Dialog -->
            <v-dialog v-model="showSortingModal" max-width="600px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-sort" class="mr-2"></v-icon>
                        {{ $t('documentView.sortCategories') }}
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-caption mb-4">{{ $t('documentView.dragSortHint') }}</p>
                        <draggable
                            v-model="draggableCategories"
                            :list="draggableCategories"
                            tag="v-list"
                            item-key="id"
                            :animation="150"
                            handle=".drag-handle"
                            ghost-class="ghost-item"
                            class="category-sort-list"
                        >
                            <template #item="{ element }">
                                <v-list-item class="draggable-category-item" :key="element.id">
                                    <template v-slot:prepend>
                                        <v-icon
                                            class="drag-handle mr-2"
                                            icon="mdi-drag-horizontal-variant"
                                            size="small"
                                        ></v-icon>
                                    </template>
                                    <v-list-item-title class="draggable-category-title">
                                        <v-icon
                                            icon="mdi-folder"
                                            size="small"
                                            class="mr-2"
                                        ></v-icon>
                                        {{ element.name }}
                                    </v-list-item-title>
                                </v-list-item>
                            </template>
                        </draggable>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="cancelCategorySorting">{{ $t('cancel') }}</v-btn>
                        <v-btn
                            v-if="canEdit"
                            color="primary"
                            variant="elevated"
                            @click="saveCategorySorting"
                            :loading="savingSort"
                        >
                            {{ $t('documentView.saveOrder') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Löschen Bestätigungsdialog -->
            <v-dialog v-model="deleteConfirmationDialog" max-width="500px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        {{ deleteDialogContent.title }}
                    </v-card-title>
                    <v-card-text class="pa-4" v-html="deleteDialogContent.text"></v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteConfirmationDialog"
                            >{{ $t('cancel') }}</v-btn
                        >
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDelete"
                            :loading="deletingItem"
                        >
                            {{ $t('delete') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.document-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

.section-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--k-line);
}

.header-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.search-field {
    min-width: 300px;
    max-width: 400px;
}

.empty-state-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
}

.main-table-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.category-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(30, 41, 59, 0.4);
}

.category-title {
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.category-edit-field {
    max-width: 300px;
}

.document-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.document-draggable {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    width: 100%;
}

.document-item {
    flex: 0 0 calc(25% - 16px);
    min-width: 220px;
    animation: fadeIn 0.3s ease-out forwards;
}

.document-card {
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px;
    background: rgba(30, 41, 59, 0.4) !important;
    border: 1px solid var(--k-line);
    transition: all 0.2s ease;
}

.document-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.card-actions {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 2;
}

.card-icon {
    margin: 16px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.document-title {
    font-size: 0.95rem !important;
    line-height: 1.3;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    padding: 8px 4px 4px;
}

.document-date {
    font-size: 0.75rem !important;
    text-align: center;
    padding: 0 4px 8px;
}

.date-line {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
}

.user-modifier {
    opacity: 0.7;
    font-style: italic;
}

.table-document-title {
    display: flex;
    align-items: center;
    color: var(--k-accent);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.table-document-title:hover {
    transform: translateX(4px);
}

.group-header-row {
    background: rgba(30, 41, 59, 0.4) !important;
}

.group-header-cell {
    padding: 8px 16px !important;
}

.draggable-category-item {
    background: rgba(30, 41, 59, 0.4) !important;
    border: 1px solid var(--k-line);
    margin-bottom: 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.draggable-category-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
    transform: translateY(-2px);
}

.draggable-category-title {
    display: flex;
    align-items: center;
}

.category-sort-list {
    background: rgba(15, 23, 42, 0.4) !important;
    border-radius: 8px;
    padding: 8px;
    border: 1px solid var(--k-line);
}

.ghost-item {
    opacity: 0.5;
    background: rgba(59, 130, 246, 0.2) !important;
    border: 1px dashed rgba(59, 130, 246, 0.5) !important;
}

.drag-handle {
    cursor: move;
    opacity: 0.6;
    transition: opacity 0.2s ease-in-out;
    color: var(--k-ink-muted);
}

.draggable-category-item:hover .drag-handle {
    opacity: 1;
    color: var(--k-ink-faint);
}

.action-btn {
    margin: 0 2px;
    transition: transform 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.document-editor-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1050;
    overflow: visible; /* Let content scroll naturally, not this container */
    padding: 16px;
}

.empty-table-message {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
    color: var(--k-ink-muted);
}

/* Dialog styling */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

/* Animation effects */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 599px) {
    .document-item {
        flex: 0 0 100%;
    }

    .section-header {
        flex-direction: column;
        align-items: stretch;
    }

    .search-field {
        min-width: 100%;
        margin-top: 16px;
    }
}

.document-editor-container {
    max-height: 100vh !important;
}

/* Iframe/Embedded mode styles */
.embedded-mode {
    min-height: 100vh;
    background: transparent;
}

.embedded-mode .document-container {
    background-color: transparent;
    background-image: none;
}

.embedded-mode .v-container {
    padding: 8px !important;
}

.embedded-mode .section-header {
    margin-bottom: 16px;
    padding-bottom: 8px;
}

.embedded-mode .category-card,
.embedded-mode .main-table-card,
.embedded-mode .empty-state-card {
    backdrop-filter: none;
    background: rgba(15, 23, 42, 0.95) !important;
}
</style>
