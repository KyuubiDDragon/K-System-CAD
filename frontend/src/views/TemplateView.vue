<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import draggable from 'vuedraggable'; // Use vuedraggable
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useI18n } from 'vue-i18n';
// Import specific icons if needed, or rely on Vuetify's mdi integration
// import { mdiHome, mdiAccount, ... } from '@mdi/js';

// --- Define Local Interfaces ---
interface FieldValue {
    [key: string]: string | string[];
}

interface ExtractedField {
    field_name: string;
    is_multiple: boolean;
    is_required: boolean;
    field_label?: string; // Made optional
    field_type: string;
    field_description?: string; // Made optional
    field_value?: string | string[]; // Made optional
}

interface Field {
    id: number;
    template_id: number | null;
    field_name: string;
    field_label: string;
    field_type: string;
    field_description: string;
    field_value?: string | string[]; // Make optional as it might not always be present
    is_multiple?: boolean; // Add missing properties if needed based on usage
    is_required?: boolean; // Add missing properties if needed based on usage
}

interface Template {
    id: number;
    category_id: number;
    name: string;
    description: string;
    icon: string;
    text: string;
    recipient: string;
    subject: string;
    sort_order?: number; // Add if used for sorting
}

interface Category {
    id: number;
    name: string;
    sort_order: number;
    templates: Template[];
}

interface IconItem {
    name: string;
    path: string;
}

// --- Store, Router & Permissions ---
const authStore = useAuthStore(); // Instantiate store
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Component State ---
const categories = ref<Category[]>([]);
const allFields = ref<Field[]>([]); // All predefined fields
const loadingInitialData = ref(false);
const savingCategorySort = ref(false);
const savingTemplateSort = ref(false);
const savingTemplate = ref(false);
const savingCategory = ref(false);
const deletingCategory = ref(false);
const deletingTemplate = ref(false);
const searchInput = ref('');

// --- Dialog States ---
const dialog = ref(false); // Template preview/fill dialog
const addTemplateDialog = ref(false);
const editTemplateDialog = ref(false);
const addCategoryDialog = ref(false);
const deleteCategoryDialog = ref(false);
const deleteTemplateDialog = ref(false);
const showSortingModal = ref(false);
const iconPickerDialog = ref(false);

// --- Form & Dialog Data ---
const selectedTemplate = ref<Template | null>(null);
const templateFields = ref<Field[]>([]); // Fields relevant to the selected template
const fieldValues = reactive<FieldValue>({}); // Values entered by user for the template
const previewRecipient = ref(''); // Email recipient for preview dialog
const previewSubject = ref(''); // Email subject for preview dialog

// Add/Edit Category
const addCategoryFormRef = ref<any>(null);
const isAddCategoryFormValid = ref(false);
const newCategory = reactive({ name: '' });
const categoryToDelete = ref<Category | null>(null);

// Add/Edit Template
const addTemplateFormRef = ref<any>(null);
const editTemplateFormRef = ref<any>(null);
const isAddTemplateFormValid = ref(false);
const isEditTemplateFormValid = ref(false);
const initialTemplateFormData: Omit<Template, 'id' | 'sort_order'> & {
    id?: number;
    category_id: number;
} = {
    category_id: 0, // Initialize with 0 instead of null for compatibility with Template interface
    name: '',
    text: '{{header}}\n\nSehr geehrte/r Herr/Frau {{name}},\n\n...\n\n{{footer}}\n\n{{signature}}',
    description: '',
    icon: '',
    recipient: '',
    subject: '',
};
const newTemplate = reactive({ ...initialTemplateFormData });
const editedTemplate = reactive<Template>({
    id: 0,
    category_id: 0,
    name: '',
    description: '',
    icon: '',
    text: '',
    recipient: '',
    subject: '',
});
const templateToDelete = ref<Template | null>(null);

// Icon Picker
const selectedIcon = ref<IconItem>({ name: '', path: '' });

// Category Sorting
const draggableCategories = ref<Category[]>([]); // Separate list for draggable modal

// Inline Editing
const editingField = ref<{ type: string; id: number }>({ type: '', id: -1 });
const editedValue = ref('');

// --- Snackbar ---
const errorSnackbar = ref({
    visible: false,
    message: '',
    color: 'error',
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Helper Functions ---
const placeholderDisplay = (name: string) => `{{${name}}}`;

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || t('requiredField');

// --- Data Fetching ---
const fetchCategories = async () => {
    try {
        const response = await apiClientAuth.get<Category[]>('/templates/?action=getTemplates');
        // Ensure we have an array before mapping
        const data = Array.isArray(response.data) ? response.data : [];

        // Ensure templates within categories are sorted if sort_order exists
        categories.value = data
            .map(cat => ({
                ...cat,
                templates: (Array.isArray(cat.templates) ? cat.templates : []).sort(
                    (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)
                ),
            }))
            .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)); // Sort categories themselves
    } catch (error: any) {
        console.error('Error fetching categories:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Kategorien.', 'error');
        categories.value = [];
    }
};

const fetchFields = async () => {
    try {
        const response = await apiClientAuth.get<Field[]>('/templates/?action=getFields');
        allFields.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching fields:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Laden der Template-Felder.',
            'error'
        );
        allFields.value = [];
    }
};

const fetchAllInitialData = async () => {
    loadingInitialData.value = true;
    await Promise.all([fetchCategories(), fetchFields()]);
    loadingInitialData.value = false;
};

// --- Computed Properties ---
const filteredCategories = computed<Category[]>(() => {
    const searchTerm = searchInput.value.trim().toLowerCase();
    if (!searchTerm) {
        return categories.value; // Return all if no search term
    }

    return (
        categories.value
            .map(category => ({
                ...category,
                // Filter templates within each category (ensure templates is array)
                templates: (Array.isArray(category.templates) ? category.templates : []).filter(
                    template =>
                        template.name.toLowerCase().includes(searchTerm) ||
                        (template.description &&
                            template.description.toLowerCase().includes(searchTerm))
                ),
            }))
            // Keep categories that still have matching templates OR match the category name itself
            .filter(
                category =>
                    category.templates.length > 0 ||
                    category.name.toLowerCase().includes(searchTerm)
            )
    );
});

const renderedTemplatePreview = computed(() => renderTemplate());

const previewRows = computed(() => {
    const minRows = 10;
    const maxRows = 25; // Adjust max rows for preview
    const text = renderedTemplatePreview.value;
    const numberOfLineBreaks = (text.match(/\n/g) || []).length;
    return Math.max(minRows, Math.min(numberOfLineBreaks + 1, maxRows));
});

// --- Methods ---

// Field Extraction and Value Handling (Adapted for Pinia)
function getFieldValue(uniqueFieldName: string): string | string[] {
    const fieldName = uniqueFieldName.split(':')[0]; // Get original name
    const user = authStore.user; // Get user data from Pinia

    switch (fieldName) {
        case 'header':
            return user?.mail_header ? `[img]${user.mail_header}[/img]` : '';
        case 'footer':
            return user?.mail_footer ? `[img]${user.mail_footer}[/img]` : '';
        case 'header_neutral':
            return user?.mail_header_neutral ? `[img]${user.mail_header_neutral}[/img]` : '';
        case 'footer_neutral':
            return user?.mail_footer_neutral ? `[img]${user.mail_footer_neutral}[/img]` : '';
        case 'signature':
            return user?.signature ? `[img]${user.signature}[/img]` : '';
        default:
            // Find predefined value from allFields if available
            const predefinedField = allFields.value.find(f => f.field_name === fieldName);
            return predefinedField?.field_value ?? ''; // Return predefined value or empty string
    }
}

function extractTemplateFields(templateText: string): ExtractedField[] {
    // Fix the regex pattern - it's missing backslashes before \w
    const variablePattern = /\{\{ *(\w+)((?: *, *\w+)*) *\}\}/g;
    const fields: ExtractedField[] = [];
    const fieldCounts: { [fieldName: string]: number } = {};
    let match;

    while ((match = variablePattern.exec(templateText)) !== null) {
        const fieldName = match[1];
        const properties =
            match[2]
                ?.split(',')
                .map(s => s.trim())
                .filter(Boolean) || [];

        fieldCounts[fieldName] = (fieldCounts[fieldName] || 0) + 1;
        const uniqueFieldName = `${fieldName}:${fieldCounts[fieldName]}`;

        const predefinedField = allFields.value.find(f => f.field_name === fieldName);

        fields.push({
            field_name: uniqueFieldName,
            is_multiple: properties.includes('multiple'),
            is_required: properties.includes('required'),
            field_label: predefinedField?.field_label || fieldName,
            field_type: predefinedField?.field_type || 'text',
            field_description: predefinedField?.field_description || '',
        });
    }
    return fields;
}

// Template Preview/Fill Dialog Logic
const openDialog = (template: Template) => {
    selectedTemplate.value = { ...template }; // Store a copy
    previewRecipient.value = template.recipient || ''; // Pre-fill recipient/subject
    previewSubject.value = template.subject || '';

    const extracted = extractTemplateFields(template.text);
    // Map extracted fields to the richer 'Field' type, using data from 'allFields'
    templateFields.value = extracted.map(extField => {
        const originalFieldName = extField.field_name.split(':')[0];
        const predefined = allFields.value.find(f => f.field_name === originalFieldName);
        return {
            id: predefined?.id ?? 0, // Use predefined ID or 0/null
            template_id: selectedTemplate.value?.id ?? null, // Link to current template
            field_name: extField.field_name, // Keep unique name (name:index)
            field_label: predefined?.field_label || originalFieldName, // Prefer predefined label
            field_type: predefined?.field_type || 'text', // Prefer predefined type
            field_description: predefined?.field_description || '',
            is_multiple: extField.is_multiple,
            is_required: extField.is_required,
            field_value: getFieldValue(extField.field_name), // Get initial/default value
        };
    });

    // Initialize fieldValues based on the processed templateFields
    for (const field of templateFields.value) {
        fieldValues[field.field_name] = field.field_value ?? '';
    }

    dialog.value = true;
};

function renderTemplate(): string {
    if (!selectedTemplate.value) return '';
    let renderedText = selectedTemplate.value.text;

    // Replace simple placeholders first (header, footer, etc.)
    const simplePlaceholders = [
        'header',
        'footer',
        'header_neutral',
        'footer_neutral',
        'signature',
    ];
    simplePlaceholders.forEach(ph => {
        const val = getFieldValue(ph + ':1');
        // Only replace if value exists and is not empty
        if (val && val !== '') {
            renderedText = renderedText.replace(
                new RegExp(`\\{\\{ *${ph} *\\}\\}`, 'g'),
                Array.isArray(val) ? val.join(', ') : val
            );
        }
    });

    // Replace field placeholders ONLY if they have a non-empty value
    for (const uniqueFieldName in fieldValues) {
        const fieldValue = fieldValues[uniqueFieldName];
        const baseFieldName = uniqueFieldName.split(':')[0];
        
        // Only replace if the field has a meaningful value
        if (fieldValue && fieldValue !== '') {
            const renderedFieldValue = Array.isArray(fieldValue)
                ? fieldValue.join(", ")
                : fieldValue;
            
            const escapedFieldName = baseFieldName.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            const variablePattern = new RegExp(`\\{\\{ *${escapedFieldName} *\\}\\}`, "g");
            renderedText = renderedText.replace(variablePattern, renderedFieldValue);
        }
    }

    return renderedText;
}

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(renderedTemplatePreview.value);
        showSnackbar('In Zwischenablage kopiert!', 'success');
    } catch (err) {
        console.error('Failed to copy text: ', err);
        showSnackbar('Kopieren fehlgeschlagen.', 'error');
    }
};

// --- Add/Edit Category ---
const openAddCategoryDialog = () => {
    newCategory.name = ''; // Reset form
    isAddCategoryFormValid.value = false;
    addCategoryDialog.value = true;
    setTimeout(() => addCategoryFormRef.value?.resetValidation(), 100);
};

const addNewCategory = async () => {
    if (!isAddCategoryFormValid.value) return;
    savingCategory.value = true;
    try {
        await apiClientAuth.post('/templates/?action=addCategorie', newCategory);
        await fetchCategories(); // Refresh list
        addCategoryDialog.value = false;
        showSnackbar('Kategorie erfolgreich hinzugefügt.', 'success');
    } catch (error: any) {
        console.error('Error adding category:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Hinzufügen der Kategorie.',
            'error'
        );
    } finally {
        savingCategory.value = false;
    }
};

// Delete Category
const openDeleteCategoryDialog = (category: Category) => {
    if (category.templates.length > 0) {
        showSnackbar(
            'Kategorie kann nicht gelöscht werden, da sie noch Templates enthält.',
            'warning'
        );
        return;
    }
    categoryToDelete.value = category;
    deleteCategoryDialog.value = true;
};

const closeDeleteCategoryDialog = () => {
    deleteCategoryDialog.value = false;
    categoryToDelete.value = null;
};

const confirmDeleteCategory = async () => {
    if (!categoryToDelete.value) return;
    deletingCategory.value = true;
    try {
        await apiClientAuth.post('/templates/?action=deleteCategorie', {
            id: categoryToDelete.value.id,
        }); // Assume correct endpoint
        await fetchCategories(); // Refresh list
        closeDeleteCategoryDialog();
        showSnackbar('Kategorie erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting category:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Kategorie.', 'error');
    } finally {
        deletingCategory.value = false;
    }
};

// --- Add/Edit Template ---
const openAddTemplateDialog = () => {
    Object.assign(newTemplate, initialTemplateFormData); // Reset form
    selectedIcon.value = { name: '', path: '' }; // Reset icon
    isAddTemplateFormValid.value = false;
    addTemplateDialog.value = true;
    setTimeout(() => addTemplateFormRef.value?.resetValidation(), 100);
};

const closeAddTemplateDialog = () => {
    addTemplateDialog.value = false;
};

const addNewTemplate = async () => {
    if (!isAddTemplateFormValid.value || !newTemplate.text) {
        showSnackbar('Bitte füllen Sie alle erforderlichen Felder aus.', 'warning');
        return;
    }
    savingTemplate.value = true;
    try {
        const payload = { ...newTemplate, icon: selectedIcon.value.path || newTemplate.icon }; // Use path if selected
        await apiClientAuth.post('/templates/?action=addTemplate', payload);
        await fetchCategories(); // Refresh categories and templates
        closeAddTemplateDialog();
        showSnackbar('Template erfolgreich hinzugefügt.', 'success');
    } catch (error: any) {
        console.error('Error adding template:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Hinzufügen des Templates.',
            'error'
        );
    } finally {
        savingTemplate.value = false;
    }
};

const openEditDialog = (template: Template) => {
    Object.assign(editedTemplate, { ...template }); // Load data
    // Find matching icon object from `icons` list
    const foundIcon = icons.find(
        icon => icon.path === template.icon || icon.name === template.icon
    ); // Check path and name
    selectedIcon.value = foundIcon || { name: template.icon, path: template.icon }; // Fallback if not found
    isEditTemplateFormValid.value = false;
    editTemplateDialog.value = true;
    setTimeout(() => editTemplateFormRef.value?.resetValidation(), 100);
};

const closeEditTemplateDialog = () => {
    editTemplateDialog.value = false;
};

const editTemplate = async () => {
    if (!isEditTemplateFormValid.value || !editedTemplate.text) {
        showSnackbar('Bitte füllen Sie alle erforderlichen Felder aus.', 'warning');
        return;
    }
    savingTemplate.value = true;
    try {
        const payload = { ...editedTemplate, icon: selectedIcon.value.path || editedTemplate.icon }; // Use path if selected
        await apiClientAuth.post('/templates/?action=updateTemplate', payload);
        await fetchCategories(); // Refresh list
        closeEditTemplateDialog();
        showSnackbar('Template erfolgreich aktualisiert.', 'success');
    } catch (error: any) {
        console.error('Error updating template:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren des Templates.',
            'error'
        );
    } finally {
        savingTemplate.value = false;
    }
};

// Delete Template
const openDeleteTemplateDialog = (template: Template) => {
    templateToDelete.value = template;
    deleteTemplateDialog.value = true;
};

const closeDeleteTemplateDialog = () => {
    deleteTemplateDialog.value = false;
    templateToDelete.value = null;
};

const confirmDeleteTemplate = async () => {
    if (!templateToDelete.value) return;
    deletingTemplate.value = true;
    try {
        await apiClientAuth.post('/templates/?action=deleteTemplate', {
            id: templateToDelete.value.id,
        });
        await fetchCategories(); // Refresh list
        closeDeleteTemplateDialog();
        showSnackbar('Template erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting template:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Templates.', 'error');
    } finally {
        deletingTemplate.value = false;
    }
};

// --- Icon Picker ---
const icons: IconItem[] = [
    // Defined as array of IconItem
    // Simplified list for brevity - Add all needed MDI icons here
    { name: 'Home', path: 'mdi-home' },
    { name: 'Account', path: 'mdi-account' },
    { name: 'Email', path: 'mdi-email' },
    { name: 'Phone', path: 'mdi-phone' },
    { name: 'Map Marker', path: 'mdi-map-marker' },
    { name: 'Calendar', path: 'mdi-calendar' },
    { name: 'Lock', path: 'mdi-lock' },
    { name: 'Magnify', path: 'mdi-magnify' },
    { name: 'Plus', path: 'mdi-plus' },
    { name: 'Minus', path: 'mdi-minus' },
    { name: 'Check', path: 'mdi-check' },
    { name: 'Close', path: 'mdi-close' },
    { name: 'Cog', path: 'mdi-cog' },
    { name: 'Information', path: 'mdi-information' },
    { name: 'Alert', path: 'mdi-alert' },
    { name: 'Help', path: 'mdi-help-circle' },
    { name: 'Delete', path: 'mdi-delete' },
    { name: 'Pencil', path: 'mdi-pencil' },
    { name: 'Eye', path: 'mdi-eye' },
    { name: 'Eye Off', path: 'mdi-eye-off' },
    { name: 'Folder', path: 'mdi-folder' },
    { name: 'File', path: 'mdi-file-document-outline' },
    { name: 'Camera', path: 'mdi-camera' },
    { name: 'Image', path: 'mdi-image' },
    { name: 'Play', path: 'mdi-play' },
    { name: 'Pause', path: 'mdi-pause' },
    { name: 'Download', path: 'mdi-download' },
    { name: 'Upload', path: 'mdi-upload' },
    { name: 'Receipt', path: 'mdi-receipt' },
    { name: 'File Sign', path: 'mdi-file-sign' },
    { name: 'Cash', path: 'mdi-cash' },
    { name: 'Account Tie', path: 'mdi-account-tie' },
    { name: 'Briefcase', path: 'mdi-briefcase' },
    { name: 'Bank', path: 'mdi-bank' },
    { name: 'Car', path: 'mdi-car' },
    { name: 'Heart', path: 'mdi-heart' },
    { name: 'Star', path: 'mdi-star' },
];

const selectIcon = (iconItem: IconItem) => {
    selectedIcon.value = iconItem;
    // Update the correct form data based on which dialog is implicitly open
    if (addTemplateDialog.value) {
        newTemplate.icon = iconItem.path;
    } else if (editTemplateDialog.value) {
        editedTemplate.icon = iconItem.path;
    }
    iconPickerDialog.value = false;
};

// --- Sorting ---
watch(showSortingModal, newValue => {
    if (newValue) {
        // Clone categories for safe drag-and-drop operations
        draggableCategories.value = JSON.parse(JSON.stringify(categories.value));
    }
});

const cancelCategorySorting = () => {
    showSortingModal.value = false;
    // No need to revert, draggableCategories is a separate copy
};

const saveCategorySorting = async () => {
    savingCategorySort.value = true;
    const sortedData = draggableCategories.value.map((cat, index) => ({
        id: cat.id,
        sort_order: index + 1,
    }));
    try {
        await apiClientAuth.post('/templates/?action=saveCategorySorting', {
            categories: sortedData,
        }); // Adjust payload structure if needed
        await fetchCategories(); // Refresh list with new order
        showSnackbar('Kategorienreihenfolge gespeichert.', 'success');
    } catch (error: any) {
        console.error('Error saving category sorting:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Kategorienreihenfolge.',
            'error'
        );
        // Optionally revert local state if API call fails
        // draggableCategories.value = JSON.parse(JSON.stringify(categories.value));
    } finally {
        savingCategorySort.value = false;
        showSortingModal.value = false;
    }
};

const saveTemplateSorting = async (event: any, templateList: Template[]) => {
    // event might contain oldIndex, newIndex if needed
    savingTemplateSort.value = true; // Use a general indicator maybe
    const sortedData = templateList.map((template, index) => ({
        id: template.id,
        sort_order: index + 1,
    }));
    try {
        await apiClientAuth.post('/templates/?action=saveTemplateSorting', {
            templates: sortedData,
        }); // Adjust payload
        // No snackbar here? Or a less intrusive one? Fetch might reorder anyway.
        await fetchCategories(); // Refetch to confirm order
    } catch (error: any) {
        console.error('Error saving template sorting:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Template-Reihenfolge.',
            'error'
        );
        // Revert local changes on error
        await fetchCategories();
    } finally {
        savingTemplateSort.value = false;
    }
};

// --- Inline Category Name Editing ---
const startEditingField = (fieldType: string, category: Category) => {
    if (!canEdit.value) return;
    editingField.value = { type: fieldType, id: category.id };
    editedValue.value = category.name;
    // Focus the input field automatically
    // This requires the input field to have a ref="editFieldRef" or similar
    // await nextTick();
    // editFieldRef.value?.focus(); // Assuming you add a ref to the inline v-text-field
};

const cancelEditingField = () => {
    editingField.value = { type: '', id: -1 };
    editedValue.value = '';
};

const updateCategoryName = async (category: Category) => {
    const currentEditedId = editingField.value.id;
    const currentEditedValue = editedValue.value.trim();

    // Only proceed if we were actually editing this category and the value changed and is not empty
    if (
        category.id !== currentEditedId ||
        category.name === currentEditedValue ||
        !currentEditedValue
    ) {
        cancelEditingField(); // Cancel if no change or empty value or not the right item
        return;
    }

    // Optimistic UI update
    const originalName = category.name;
    category.name = currentEditedValue;
    cancelEditingField(); // Close input immediately

    try {
        await apiClientAuth.post('/templates/?action=updateCategoryName', {
            id: category.id,
            name: currentEditedValue,
        });
        // Success already reflected optimistically
        showSnackbar('Kategoriename aktualisiert.', 'success');
    } catch (error: any) {
        console.error('Error updating category name:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren des Kategorienamens.',
            'error'
        );
        // Revert optimistic update on error
        category.name = originalName;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchAllInitialData();
});
</script>
<template>
    <div class="app-container">
        <ErrorSnackbar
            v-model="errorSnackbar"
        />
        <v-container fluid>
            <!-- Header Bereich -->
            <div class="section-header mb-4">
                <div class="d-flex align-center">
                    <v-icon
                        icon="mdi-text-box-outline"
                        size="24"
                        class="mr-2 text-primary"
                    ></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('templateView.title') }}</h1>
                </div>

                <div class="action-buttons">
                    <v-btn
                        v-if="canEdit"
                        @click="openAddTemplateDialog"
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-plus-box-outline"
                        size="small"
                        class="action-button mr-2"
                    >
                        {{ t('templateView.addTemplate') }}
                    </v-btn>

                    <v-btn
                        v-if="canEdit"
                        @click="openAddCategoryDialog"
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-shape-plus-outline"
                        size="small"
                        class="action-button mr-2"
                    >
                        {{ t('templateView.addCategory') }}
                    </v-btn>

                    <v-btn
                        v-if="canEdit"
                        @click="showSortingModal = true"
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-sort"
                        size="small"
                        class="action-button"
                    >
                        {{ t('templateView.sortCategories') }}
                    </v-btn>
                </div>
            </div>

            <!-- Suchfeld -->
            <v-row class="mb-4">
                <v-col cols="12" md="6" lg="4">
                    <v-text-field
                        :label="t('templateView.searchPlaceholder')"
                        v-model="searchInput"
                        class="search-field"
                        variant="outlined"
                        density="comfortable"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        clearable
                    ></v-text-field>
                </v-col>
            </v-row>

            <!-- Ladeindikator -->
            <div
                v-if="loadingInitialData"
                class="d-flex flex-column align-center justify-center py-8"
            >
                <v-progress-circular
                    indeterminate
                    color="primary"
                    size="64"
                    class="mb-4"
                ></v-progress-circular>
                <div class="text-body-1 text-medium-emphasis">{{ t('templateView.loading') }}</div>
            </div>

            <!-- Keine Ergebnisse Anzeige -->
            <v-card
                v-else-if="filteredCategories.length === 0"
                class="empty-state-card pa-8"
                variant="outlined"
            >
                <div class="d-flex flex-column align-center justify-center py-4">
                    <v-icon
                        icon="mdi-text-box-search-outline"
                        size="64"
                        class="mb-4 text-grey"
                    ></v-icon>
                    <h3 class="text-h6 mb-2">{{ t('templateView.noTemplates') }}</h3>
                    <p class="text-body-1 text-medium-emphasis">
                        {{
                            searchInput
                                ? t('templateView.noResults')
                                : t('templateView.noneAdded')
                        }}
                    </p>
                    <v-btn
                        v-if="canEdit && searchInput"
                        @click="searchInput = ''"
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-close"
                        class="mt-4"
                    >
                        {{ t('templateView.resetSearch') }}
                    </v-btn>
                    <v-btn
                        v-else-if="canEdit"
                        @click="openAddTemplateDialog"
                        color="primary"
                        prepend-icon="mdi-plus"
                        class="mt-4"
                    >
                        {{ t('templateView.addTemplate') }}
                    </v-btn>
                </div>
            </v-card>

            <!-- Kategorien und Templates -->
            <template v-else v-for="(category, index) in filteredCategories" :key="category.id">
                <!-- Kategorie-Header -->
                <div class="category-header mb-3">
                    <div class="d-flex align-center">
                        <v-avatar size="32" color="primary" variant="flat" class="mr-3">
                            <v-icon>mdi-folder</v-icon>
                        </v-avatar>

                        <!-- Kategoriename (bearbeitbar) -->
                        <div class="category-name-container">
                            <!-- Inline-Bearbeitungsfeld -->
                            <template
                                v-if="
                                    editingField.type === 'categoryName' &&
                                    editingField.id === category.id
                                "
                            >
                                <v-text-field
                                    v-model="editedValue"
                                    density="compact"
                                    variant="outlined"
                                    bg-color="grey-darken-3"
                                    color="primary"
                                    hide-details
                                    single-line
                                    autofocus
                                    @keydown.enter="updateCategoryName(category)"
                                    @keydown.esc="cancelEditingField"
                                    @blur="updateCategoryName(category)"
                                    class="edit-field"
                                    style="max-width: 300px"
                                ></v-text-field>
                            </template>

                            <!-- Anzeigename mit Doppelklick zum Bearbeiten -->
                            <h2
                                v-else
                                class="text-h6 font-weight-bold mb-0"
                                @dblclick="
                                    canEdit ? startEditingField('categoryName', category) : null
                                "
                                :style="canEdit ? 'cursor: pointer;' : ''"
                            >
                                {{ category.name }}
                                <v-tooltip
                                    v-if="canEdit"
                                    text="Doppelklick zum Bearbeiten"
                                    location="top"
                                >
                                    <template v-slot:activator="{ props }">
                                        <v-icon
                                            size="small"
                                            class="ml-1"
                                            color="grey"
                                            v-bind="props"
                                            >mdi-pencil-outline</v-icon
                                        >
                                    </template>
                                </v-tooltip>
                            </h2>
                        </div>

                        <!-- Badge für Anzahl der Templates -->
                        <v-chip size="small" color="primary" variant="tonal" class="ml-2">
                            {{ category.templates.length }} Templates
                        </v-chip>
                    </div>

                    <!-- Kategorie-Aktionen -->
                    <div class="category-actions">
                        <v-tooltip text="Kategorie löschen" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete && category.templates.length === 0"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openDeleteCategoryDialog(category)"
                                    v-bind="props"
                                    color="error"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip
                            v-if="canDelete && category.templates.length > 0"
                            text="Kategorie kann nicht gelöscht werden, da sie noch Templates enthält."
                            location="top"
                        >
                            <template v-slot:activator="{ props }">
                                <div v-bind="props" class="d-inline-block">
                                    <v-btn icon variant="text" size="small" disabled color="grey">
                                        <v-icon size="small">mdi-delete-off</v-icon>
                                    </v-btn>
                                </div>
                            </template>
                        </v-tooltip>
                    </div>
                </div>

                <!-- Template-Grid mit fester Höhe -->
                <v-row>
                    <!-- Ersetze die draggable-Komponente und die v-row mit diesem Code -->
<draggable
  :list="category.templates"
  :group="{ name: `templates-${category.id}`, pull: false, put: false }"
  class="template-grid"
  :animation="150"
  item-key="id"
  @end="event => saveTemplateSorting(event, category.templates)"
  :disabled="!canEdit"
  handle=".drag-handle"
>
  <template #item="{ element: template }">
    <v-card class="template-card" elevation="2">
      <v-toolbar
        density="compact"
        flat
        color="rgba(30, 41, 59, 0.4)"
        class="template-toolbar"
      >
        <v-icon
          v-if="canEdit"
          size="small"
          class="drag-handle mr-2"
          color="grey"
        >
          mdi-drag
        </v-icon>

        <v-spacer></v-spacer>

        <v-tooltip text="Template bearbeiten" location="top">
          <template v-slot:activator="{ props }">
            <v-btn
              v-if="canEdit"
              icon="mdi-pencil"
              density="comfortable"
              size="small"
              variant="text"
              @click.stop="openEditDialog(template)"
              v-bind="props"
              color="primary"
              class="action-icon"
            ></v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Template löschen" location="top">
          <template v-slot:activator="{ props }">
            <v-btn
              v-if="canDelete"
              icon="mdi-delete"
              density="comfortable"
              size="small"
              variant="text"
              @click.stop="openDeleteTemplateDialog(template)"
              v-bind="props"
              color="error"
              class="action-icon"
            ></v-btn>
          </template>
        </v-tooltip>
      </v-toolbar>

      <div
        @click="openDialog(template)"
        class="template-card-content"
      >
        <div class="template-icon-container">
          <v-icon size="48" color="primary">
            {{ template.icon && template.icon.startsWith('mdi-')
                ? template.icon
                : 'mdi-file-document-outline' }}
          </v-icon>
        </div>

        <div class="template-title">{{ template.name }}</div>

        <div class="template-description">
          {{ template.description || 'Keine Beschreibung vorhanden' }}
        </div>
      </div>

      <v-divider></v-divider>

      <v-card-actions>
        <v-btn
          variant="text"
          block
          color="primary"
          @click="openDialog(template)"
          prepend-icon="mdi-open-in-new"
          class="open-button"
        >
          Öffnen
        </v-btn>
      </v-card-actions>
    </v-card>
  </template>
</draggable>
                </v-row>

                <!-- Trennlinie zwischen Kategorien -->
                <v-divider
                    v-if="index < filteredCategories.length - 1"
                    class="my-6 opacity-25"
                ></v-divider>
            </template>

            <!-- Vorschau-Dialog bleibt unverändert -->
            <v-dialog v-model="dialog" max-width="1000px" persistent>
                <v-card v-if="selectedTemplate" class="dialog-card">
                    <v-card-title class="dialog-title d-flex align-center">
                        <v-icon left class="mr-2">{{
                            selectedTemplate.icon && selectedTemplate.icon.startsWith('mdi-')
                                ? selectedTemplate.icon
                                : 'mdi-file-document-outline'
                        }}</v-icon>
                        {{ selectedTemplate.name }}
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" variant="text" @click="dialog = false"></v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-form ref="formRef">
                            <v-row>
                                <v-col cols="12" md="5">
                                    <div
                                        v-if="templateFields.length === 0"
                                        class="text-grey pa-4 text-center"
                                    >
                                        Keine ausfüllbaren Felder in diesem Template gefunden.
                                    </div>
                                    <template
                                        v-for="field in templateFields"
                                        :key="field.field_name"
                                    >
                                        <v-text-field
                                            v-if="field.field_type === 'text' && !field.is_multiple"
                                            :label="field.field_label || field.field_name"
                                            :hint="field.field_description"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name] as string"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-text-field>
                                        <v-combobox
                                            v-else-if="field.field_type === 'text' && field.is_multiple"
                                            :label="field.field_label || field.field_name"
                                            :hint="field.field_description"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            multiple
                                            chips
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-combobox>
                                        <v-textarea
                                            v-else-if="field.field_type === 'textarea'"
                                            :label="field.field_label || field.field_name"
                                            :hint="field.field_description"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            rows="3"
                                            auto-grow
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-textarea>
                                        <v-text-field
                                            v-else-if="field.field_type === 'date'"
                                            :label="field.field_label || field.field_name"
                                            :hint="field.field_description"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            type="date"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-text-field>
                                        <v-select
                                            v-else-if="
                                                field.field_type === 'select' &&
                                                field.field_name.startsWith('salutation:')
                                            "
                                            :label="field.field_label || 'Anrede'"
                                            :hint="field.field_description"
                                            :items="[
                                                'Sehr geehrter Herr',
                                                'Sehr geehrte Frau',
                                                'Sehr geehrte Damen und Herren',
                                                'Guten Tag',
                                            ]"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-select>
                                        <v-select
                                            v-else-if="
                                                field.field_type === 'select' &&
                                                field.field_name.startsWith('gender:')
                                            "
                                            :label="field.field_label || 'Pronomen'"
                                            :hint="field.field_description"
                                            :items="['Herr', 'Frau', '']"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-select>
                                        <v-text-field
                                            v-else
                                            :label="field.field_label || field.field_name"
                                            :hint="field.field_description"
                                            :required="field.is_required"
                                            :rules="field.is_required ? [requiredRule] : []"
                                            v-model="fieldValues[field.field_name]"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            class="mb-3"
                                        ></v-text-field>
                                    </template>
                                </v-col>

                                <v-col cols="12" md="7">
                                    <div class="text-overline mb-1">Vorschau</div>
                                    <v-textarea
                                        :model-value="renderedTemplatePreview"
                                        :rows="previewRows"
                                        variant="outlined"
                                        density="compact"
                                        readonly
                                        no-resize
                                        bg-color="grey-darken-4"
                                    ></v-textarea>
                                    <v-text-field
                                        label="Empfänger E-Mail (optional)"
                                        hint="Für direkten Versand (falls konfiguriert)"
                                        v-model="previewRecipient"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mt-3"
                                        type="email"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Betreff (optional)"
                                        hint="Betreff für den E-Mail Versand"
                                        v-model="previewSubject"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mt-3"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-btn
                            @click="copyToClipboard"
                            prepend-icon="mdi-content-copy"
                            variant="tonal"
                            >In Zwischenablage kopieren</v-btn
                        >
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="dialog = false">Schließen</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Kategorien sortieren Dialog -->
            <v-dialog v-model="showSortingModal" max-width="600px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-sort" class="mr-2"></v-icon>
                        Kategorien sortieren
                    </v-card-title>
                    <v-card-text>
                        <p class="text-caption mb-2">Kategorien per Drag & Drop sortieren.</p>
                        <draggable
                            v-model="draggableCategories"
                            :list="draggableCategories"
                            tag="v-list"
                            item-key="id"
                            :animation="150"
                            handle=".drag-handle"
                            ghost-class="ghost"
                            class="sorting-list"
                        >
                            <template #item="{ element }">
                                <v-list-item class="draggable-item mb-1 elevation-1">
                                    <template v-slot:prepend>
                                        <v-icon class="drag-handle" style="cursor: move"
                                            >mdi-drag-variant</v-icon
                                        >
                                    </template>
                                    <v-list-item-title>{{ element.name }}</v-list-item-title>
                                </v-list-item>
                            </template>
                        </draggable>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="cancelCategorySorting">Abbrechen</v-btn>
                        <v-btn
                            v-if="canEdit"
                            color="primary"
                            variant="flat"
                            @click="saveCategorySorting"
                            :loading="savingCategorySort"
                        >
                            Reihenfolge speichern
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Alle anderen Dialoge sind unverändert und werden beibehalten -->
            <v-dialog v-model="addTemplateDialog" max-width="1000px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-plus-box-outline" class="mr-2"></v-icon>
                        {{ t('templateView.addTitle') }}
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="addTemplateFormRef" v-model="isAddTemplateFormValid">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-select
                                        label="Kategorie"
                                        item-value="id"
                                        item-title="name"
                                        :items="categories"
                                        v-model="newTemplate.category_id"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-select>
                                    <v-text-field
                                        label="Name"
                                        required
                                        v-model="newTemplate.name"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Beschreibung"
                                        required
                                        v-model="newTemplate.description"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Icon (z.B. mdi-home)"
                                        required
                                        v-model="newTemplate.icon"
                                        :append-inner-icon="
                                            newTemplate.icon || 'mdi-help-circle-outline'
                                        "
                                        @click:append-inner="iconPickerDialog = true"
                                        @click="iconPickerDialog = true"
                                        :rules="[requiredRule]"
                                        readonly
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                        hint="Icon auswählen"
                                        persistent-hint
                                    ></v-text-field>
                                    <v-text-field
                                        label="Empfänger (optional)"
                                        v-model="newTemplate.recipient"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Betreff (optional)"
                                        v-model="newTemplate.subject"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="text-overline mb-1">Verfügbare Felder</div>
                                    <v-table density="compact" bg-color="grey-darken-4">
                                        <thead>
                                            <tr>
                                                <th>Platzhalter</th>
                                                <th>Beschreibung</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="field in allFields" :key="field.id">
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay(field.field_name)
                                                    }}</code>
                                                </td>
                                                <td>{{ field.field_description }}</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{ placeholderDisplay('header') }}</code>
                                                </td>
                                                <td>Organisations-Header</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{ placeholderDisplay('footer') }}</code>
                                                </td>
                                                <td>Organisations-Footer</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('header_neutral')
                                                    }}</code>
                                                </td>
                                                <td>Neutraler Header</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('footer_neutral')
                                                    }}</code>
                                                </td>
                                                <td>Neutraler Footer</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('signature')
                                                    }}</code>
                                                </td>
                                                <td>Signatur</td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </v-col>
                            </v-row>
                            <div class="text-overline mt-4 mb-1">Vorlagentext</div>
                            <v-textarea
                                label="Vorlagentext"
                                required
                                v-model="newTemplate.text"
                                :rules="[requiredRule]"
                                variant="outlined"
                                rows="10"
                                bg-color="grey-darken-3"
                                class="mt-1"
                            ></v-textarea>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddTemplateDialog">Abbrechen</v-btn>
                        <v-btn
                            v-if="canCreate"
                            color="primary"
                            variant="flat"
                            @click="addNewTemplate"
                            :disabled="!isAddTemplateFormValid"
                            :loading="savingTemplate"
                        >
                            Hinzufügen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog v-model="addCategoryDialog" width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-shape-plus-outline" class="mr-2"></v-icon>
                        Neue Kategorie hinzufügen
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="addCategoryFormRef" v-model="isAddCategoryFormValid">
                            <v-text-field
                                label="Name"
                                required
                                v-model="newCategory.name"
                                :rules="[requiredRule]"
                                variant="outlined"
                                density="compact"
                                bg-color="grey-darken-3"
                            ></v-text-field>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="addCategoryDialog = false">Abbrechen</v-btn>
                        <v-btn
                            v-if="canCreate"
                            color="primary"
                            variant="flat"
                            @click="addNewCategory"
                            :disabled="!isAddCategoryFormValid"
                            :loading="savingCategory"
                        >
                            Hinzufügen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog v-model="editTemplateDialog" max-width="1000px" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-pencil" class="mr-2"></v-icon>
                        Template bearbeiten
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="editTemplateFormRef" v-model="isEditTemplateFormValid">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-select
                                        label="Kategorie"
                                        item-value="id"
                                        item-title="name"
                                        :items="categories"
                                        v-model="editedTemplate.category_id"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-select>
                                    <v-text-field
                                        label="Name"
                                        required
                                        v-model="editedTemplate.name"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Beschreibung"
                                        required
                                        v-model="editedTemplate.description"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Icon (z.B. mdi-home)"
                                        required
                                        v-model="editedTemplate.icon"
                                        :append-inner-icon="
                                            editedTemplate.icon || 'mdi-help-circle-outline'
                                        "
                                        @click:append-inner="iconPickerDialog = true"
                                        @click="iconPickerDialog = true"
                                        :rules="[requiredRule]"
                                        readonly
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                        hint="Icon auswählen"
                                        persistent-hint
                                    ></v-text-field>
                                    <v-text-field
                                        label="Empfänger (optional)"
                                        v-model="editedTemplate.recipient"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        class="mb-3"
                                    ></v-text-field>
                                    <v-text-field
                                        label="Betreff (optional)"
                                        v-model="editedTemplate.subject"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="text-overline mb-1">Verfügbare Felder</div>
                                    <v-table
                                        density="compact"
                                        bg-color="grey-darken-4"
                                        height="300px"
                                        fixed-header
                                    >
                                        <thead>
                                            <tr>
                                                <th>Platzhalter</th>
                                                <th>Beschreibung</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="field in allFields" :key="field.id">
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay(field.field_name)
                                                    }}</code>
                                                </td>
                                                <td>{{ field.field_description }}</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{ placeholderDisplay('header') }}</code>
                                                </td>
                                                <td>Organisations-Header</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{ placeholderDisplay('footer') }}</code>
                                                </td>
                                                <td>Organisations-Footer</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('header_neutral')
                                                    }}</code>
                                                </td>
                                                <td>Neutraler Header</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('footer_neutral')
                                                    }}</code>
                                                </td>
                                                <td>Neutraler Footer</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <code>{{
                                                        placeholderDisplay('signature')
                                                    }}</code>
                                                </td>
                                                <td>Signatur</td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </v-col>
                            </v-row>
                            <div class="text-overline mt-4 mb-1">Vorlagentext</div>
                            <v-textarea
                                label="Vorlagentext"
                                required
                                v-model="editedTemplate.text"
                                :rules="[requiredRule]"
                                variant="outlined"
                                rows="10"
                                bg-color="grey-darken-3"
                                class="mt-1"
                            ></v-textarea>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeEditTemplateDialog">Abbrechen</v-btn>
                        <v-btn
                            color="primary"
                            variant="flat"
                            @click="editTemplate"
                            :disabled="!isEditTemplateFormValid"
                            :loading="savingTemplate"
                        >
                            Speichern
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog v-model="iconPickerDialog" max-width="700px">
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-selection" class="mr-2"></v-icon>
                        Icon auswählen
                    </v-card-title>
                    <v-card-text style="max-height: 500px; overflow-y: auto">
                        <v-row dense>
                            <v-col
                                v-for="iconItem in icons"
                                :key="iconItem.name"
                                cols="2"
                                sm="1"
                                class="text-center"
                            >
                                <v-btn
                                    icon
                                    variant="text"
                                    @click="selectIcon(iconItem)"
                                    :title="iconItem.name"
                                    class="icon-pick-btn"
                                >
                                    <v-icon size="large">{{ iconItem.path }}</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="iconPickerDialog = false">Schließen</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog v-model="deleteCategoryDialog" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Kategorie löschen
                    </v-card-title>
                    <v-card-text class="pt-4">
                        <p class="text-body-1">
                            Möchten Sie die Kategorie
                            <strong>"{{ categoryToDelete?.name }}"</strong> wirklich löschen?
                        </p>
                        <p class="text-body-2 text-medium-emphasis mt-2">
                            Dies kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteCategoryDialog">Abbrechen</v-btn>
                        <v-btn
                            color="error"
                            variant="flat"
                            @click="confirmDeleteCategory"
                            :loading="deletingCategory"
                        >
                            Löschen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog v-model="deleteTemplateDialog" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Template löschen
                    </v-card-title>
                    <v-card-text class="pt-4">
                        <p class="text-body-1">
                            Möchten Sie das Template
                            <strong>"{{ templateToDelete?.name }}"</strong> wirklich löschen?
                        </p>
                        <p class="text-body-2 text-medium-emphasis mt-2">
                            Dies kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteTemplateDialog">Abbrechen</v-btn>
                        <v-btn
                            color="error"
                            variant="flat"
                            @click="confirmDeleteTemplate"
                            :loading="deletingTemplate"
                        >
                            Löschen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.app-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--k-line);
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.search-field {
    max-width: 100%;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.search-field:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(30, 41, 59, 0.4);
    border: 1px solid var(--k-line);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.category-header:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    background: rgba(30, 41, 59, 0.5);
}

.edit-field {
    margin-top: -8px;
    margin-bottom: -16px;
}

.template-grid {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    padding: 8px;
}

.template-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    cursor: pointer;
}

.template-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
}

.template-toolbar {
    background: rgba(30, 41, 59, 0.4) !important;
    border-bottom: 1px solid var(--k-line);
}

.template-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.template-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.template-description {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-height: 48px;
}

.dialog-card {
    background-color: var(--k-ink) !important;
    border: 1px solid var(--k-line);
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

.empty-state-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.drag-handle {
    cursor: grab;
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.drag-handle:hover {
    opacity: 1;
}

.action-icon {
    transition: all 0.2s ease;
}

.action-icon:hover {
    transform: scale(1.2);
}

.open-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.open-button:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

.icon-pick-btn {
    transition: all 0.2s ease;
}

.icon-pick-btn:hover {
    transform: scale(1.2);
    background-color: rgba(59, 130, 246, 0.1);
}

.sorting-list {
    background: rgba(15, 23, 42, 0.4) !important;
    border-radius: 8px;
    padding: 8px;
    border: 1px solid var(--k-line);
}

.draggable-item {
    background: rgba(30, 41, 59, 0.4) !important;
    border: 1px solid var(--k-line);
    margin-bottom: 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.draggable-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
    transform: translateY(-2px);
}

.ghost {
    opacity: 0.5;
    background: rgba(59, 130, 246, 0.2) !important;
    border: 1px dashed rgba(59, 130, 246, 0.5) !important;
}

code {
    background-color: rgba(15, 23, 42, 0.6);
    padding: 2px 4px;
    border-radius: 4px;
    color: #60a5fa;
    font-family: monospace;
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .action-buttons {
        margin-top: 16px;
        width: 100%;
    }
}

@media (max-width: 600px) {
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }

    .action-button {
        width: 100%;
    }
}

/* Zusätzliche Stile für konsistente Kachelgrößen */
.template-grid {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  padding: 8px;
}

.template-card {
  background: rgba(15, 23, 42, 0.6) !important;
  border: 1px solid var(--k-line);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.template-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
}

.template-card-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 16px;
  flex-grow: 1;
  cursor: pointer;
}

.template-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  margin-bottom: 16px;
}

.template-title {
  font-size: 1.15rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  margin-bottom: 8px;
}

.template-description {
  font-size: 0.875rem;
  text-align: center;
  color: var(--k-ink-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 42px;
  line-height: 1.4;
}

/* Zusätzliche Stile für das <style>-Tag */

.d-flex > .v-col {
  height: 100%;
}

/* Diese Regeln sorgen dafür, dass Flexbox-Elemente korrekt gestreckt werden */
.template-grid .v-col.d-flex {
  display: flex;
  flex-direction: column;
}

.template-grid .v-col.d-flex .v-card {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

/* Sorgt für gleichmäßige Höhe in der gesamten Reihe */
.v-row {
  display: flex;
  flex-wrap: wrap;
}

.v-row::before,
.v-row::after {
  display: none;
}

/* Stellt sicher, dass der Inhalt von Template-Karten ausgewogen ist */
.template-card-content {
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-grow: 1;
}

/* Verbessert die Konsistenz der Toolbar-Höhe */
.template-toolbar {
  min-height: 48px !important;
}

/* Stellt sicher, dass alle Karten die gleiche Mindesthöhe haben */
.template-card {
  min-height: 280px;
}

/* Diese Styles sorgen für einheitliche Kartenhöhen und -breiten */
.template-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
  width: 100%;
  padding: 8px;
}

/* Entfernen des normalen row/col Layouts */
.template-grid > .v-col {
  width: 100%;
  margin: 0 !important;
  padding: 0 !important;
  max-width: 100%;
}

/* Stilisierung der Karten */
.template-card {
  background: rgba(15, 23, 42, 0.6) !important;
  border: 1px solid var(--k-line);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 280px;
  overflow: hidden;
}

.template-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
}

.template-toolbar {
  background: rgba(30, 41, 59, 0.4) !important;
  border-bottom: 1px solid var(--k-line);
  min-height: 48px !important;
  height: 48px;
  padding: 0 8px;
}

.template-card-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  flex-grow: 1;
  cursor: pointer;
}

.template-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  margin-bottom: 16px;
}

.template-title {
  font-size: 1.15rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  margin-bottom: 8px;
}

.template-description {
  font-size: 0.875rem;
  text-align: center;
  color: var(--k-ink-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 42px;
  line-height: 1.4;
}

/* Anpassung des Template-Grid für Draggable */
.template-grid:deep(.sortable-ghost) {
  opacity: 0.5;
  background: rgba(59, 130, 246, 0.1);
}

/* Medienabfragen für responsive Anpassungen */
@media (max-width: 960px) {
  .template-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  }
}

@media (max-width: 600px) {
  .template-grid {
    grid-template-columns: 1fr;
  }
}
</style>
