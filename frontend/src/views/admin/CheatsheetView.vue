<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api';
import html2canvas from 'html2canvas';
import { GridLayout, GridItem } from 'vue-grid-layout-v3';

// --- Interfaces ---
interface CheatsheetItem {
    id?: number;
    category_id: number;
    title?: string;
    description?: string;
    content_text?: string;
    image_url?: string;
    image_caption?: string;
    layout_json?: any;
    order_index: number;
    enabled: boolean;
}

interface CheatsheetCategory {
    id?: number;
    name: string;
    description?: string;
    icon: string;
    color: string;
    content_type: 'table' | 'text' | 'image' | 'mixed' | 'grid';
    width: 'full' | 'half' | 'third' | 'quarter';
    height: 'auto' | 'small' | 'medium' | 'large';
    order_index: number;
    enabled: boolean;
    collapsible: boolean;
    default_collapsed: boolean;
    show_headers?: boolean; // Option to show/hide table headers
    auto_height?: boolean; // Option for tables to auto-fit content
    grid_x?: number; // Grid layout x position
    grid_y?: number; // Grid layout y position
    grid_w?: number; // Grid layout width
    grid_h?: number; // Grid layout height
}

interface GridLayoutItem {
    i: string;
    x: number;
    y: number;
    w: number;
    h: number;
}

// --- State ---
const toast = useToast();
const { t } = useI18n();
const loading = ref(true);
const categories = ref<CheatsheetCategory[]>([]);
const itemsByCategory = ref<Record<number, CheatsheetItem[]>>({});

// View state
const viewMode = ref<'edit' | 'preview'>('preview'); // Default to preview
const selectedCategory = ref<CheatsheetCategory | null>(null);
const editingCategory = ref<CheatsheetCategory | null>(null);
const editingItem = ref<CheatsheetItem | null>(null);

// Dialog state
const categoryDialog = ref(false);
const itemDialog = ref(false);
const deleteDialog = ref(false);
const importDialog = ref(false);
const deleteTarget = ref<{ type: 'category' | 'item'; id: number } | null>(null);
const importData = ref('');

// Computed
const enabledCategories = computed(() => categories.value.filter(c => c.enabled));
const currentCategoryItems = computed(() => {
    if (!selectedCategory.value?.id) return [];
    return itemsByCategory.value[selectedCategory.value.id] || [];
});

// Drag & Drop state
const draggedCategory = ref<CheatsheetCategory | null>(null);
const dragOverIndex = ref<number | null>(null);

// Drag & Drop state for items
const draggedItem = ref<CheatsheetItem | null>(null);
const dragOverItemIndex = ref<number | null>(null);

// Column resizing state
const columnWidths = ref<Record<string, number>>({
    drag: 40,
    title: 200,
    description: 300,
    enabled: 100,
    actions: 120
});
const resizingColumn = ref<string | null>(null);
const resizeStartX = ref(0);
const resizeStartWidth = ref(0);

// Preview table column widths (per category)
const previewColumnWidths = ref<Record<number, { title: number; description: number }>>({});

// Preview container ref for export
const previewContainer = ref<HTMLElement | null>(null);
const exporting = ref(false);

// Grid layout state
const gridLayout = ref<GridLayoutItem[]>([]);
const isUpdatingFromDrag = ref(false);

// Watch categories and sync to grid layout
watch(enabledCategories, (cats) => {
    if (isUpdatingFromDrag.value) return;

    let currentX = 0;
    let currentY = 0;

    gridLayout.value = cats.map((category, index) => {
        const w = category.grid_w ?? getGridWidth(category.width);
        const h = category.grid_h ?? getGridHeight(category.height);

        // Use saved position or calculate new one
        let x = category.grid_x;
        let y = category.grid_y;

        if (x === null || x === undefined || y === null || y === undefined) {
            // Calculate default position - flow left to right, then down
            if (currentX + w > 12) {
                currentX = 0;
                currentY += h;
            }
            x = currentX;
            y = currentY;
            currentX += w;
        }

        console.log(`Category ${category.name}: saved(${category.grid_x},${category.grid_y}) using(${x},${y}) size(${w},${h})`);

        return {
            i: String(category.id),
            x,
            y,
            w,
            h
        };
    });
}, { immediate: true, deep: true });

// Convert width to grid columns
const getGridWidth = (width: string): number => {
    switch (width) {
        case 'full': return 12;
        case 'half': return 6;
        case 'third': return 4;
        case 'quarter': return 3;
        default: return 4;
    }
};

// Convert height to grid rows
const getGridHeight = (height: string): number => {
    switch (height) {
        case 'small': return 4;
        case 'medium': return 6;
        case 'large': return 9;
        case 'auto': return 6;
        default: return 6;
    }
};

// Get max height for preview
const getMaxHeight = (height: string) => {
    switch (height) {
        case 'small': return '250px';
        case 'medium': return '400px';
        case 'large': return '600px';
        case 'auto': return 'none';
        default: return '400px';
    }
};

// Find category by ID
const getCategoryById = (id: string): CheatsheetCategory | undefined => {
    return categories.value.find(c => String(c.id) === id);
};

// Grid layout with category data
const gridLayoutWithCategories = computed(() => {
    return gridLayout.value.map(item => {
        const category = getCategoryById(item.i);
        if (!category) return null;
        return {
            ...item,
            category
        };
    }).filter(item => item !== null);
});

// --- Methods ---
async function loadCategories() {
    loading.value = true;
    try {
        const response = await apiClientAuth.get('/cheatsheet/?action=getCategoriesAdmin');
        const data = response.data;
        if (data.success) {
            // Convert enabled from 1/0 to boolean
            categories.value = data.categories.map((cat: any) => ({
                ...cat,
                enabled: cat.enabled === 1 || cat.enabled === '1' || cat.enabled === true,
                show_headers: cat.show_headers === 1 || cat.show_headers === '1' || cat.show_headers === true,
                auto_height: cat.auto_height === 1 || cat.auto_height === '1' || cat.auto_height === true
            }));
            // Also load items for preview
            await loadAllItems();
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.loadError'));
    } finally {
        loading.value = false;
    }
}

async function loadAllItems() {
    try {
        const response = await apiClientAuth.get('/cheatsheet/?action=getAll');
        const data = response.data;
        if (data.success) {
            // Convert enabled from 1/0 to boolean for all items
            const convertedItems: Record<number, CheatsheetItem[]> = {};
            for (const [catId, items] of Object.entries(data.items as Record<number, any[]>)) {
                convertedItems[Number(catId)] = items.map((item: any) => ({
                    ...item,
                    enabled: item.enabled === 1 || item.enabled === '1' || item.enabled === true
                }));
            }
            itemsByCategory.value = convertedItems;
        }
    } catch (err: any) {
        console.error('Failed to load items for preview:', err);
    }
}

async function loadCategoryItems(categoryId: number) {
    try {
        const response = await apiClientAuth.get(`/cheatsheet/?action=getItems&category_id=${categoryId}`);
        const data = response.data;
        if (data.success) {
            // Convert enabled from 1/0 to boolean
            itemsByCategory.value[categoryId] = data.items.map((item: any) => ({
                ...item,
                enabled: item.enabled === 1 || item.enabled === '1' || item.enabled === true
            }));
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.loadItemsError'));
    }
}

function selectCategory(category: CheatsheetCategory) {
    selectedCategory.value = category;
    if (!itemsByCategory.value[category.id!]) {
        loadCategoryItems(category.id!);
    }
}

function openCategoryDialog(category: CheatsheetCategory | null = null) {
    if (category) {
        editingCategory.value = { ...category };
    } else {
        editingCategory.value = {
            name: '',
            description: '',
            icon: 'mdi-information',
            color: 'primary',
            content_type: 'table',
            width: 'third',
            height: 'medium',
            order_index: categories.value.length,
            enabled: true,
            collapsible: false,
            default_collapsed: false,
            show_headers: true,
            auto_height: false
        };
    }
    categoryDialog.value = true;
}

async function saveCategory() {
    if (!editingCategory.value) return;

    try {
        const action = editingCategory.value.id ? 'updateCategory' : 'createCategory';
        const response = await apiClientAuth.post(`/cheatsheet/?action=${action}`, editingCategory.value);
        const data = response.data;
        if (data.success) {
            toast.success(editingCategory.value.id ? t('admin.cheatsheet.categoryUpdated') : t('admin.cheatsheet.categoryCreated'));
            categoryDialog.value = false;
            await loadCategories();
            // Auto-generate screenshot after save
            await generateAndSaveScreenshot();
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.saveCategoryError'));
    }
}

function openItemDialog(item: CheatsheetItem | null = null) {
    if (!selectedCategory.value) {
        toast.warning(t('admin.cheatsheet.selectCategoryFirst'));
        return;
    }

    if (item) {
        editingItem.value = { ...item };
    } else {
        editingItem.value = {
            category_id: selectedCategory.value.id!,
            title: '',
            description: '',
            content_text: '',
            order_index: currentCategoryItems.value.length,
            enabled: true
        };
    }
    itemDialog.value = true;
}

async function saveItem() {
    if (!editingItem.value) return;

    try {
        const action = editingItem.value.id ? 'updateItem' : 'createItem';
        const response = await apiClientAuth.post(`/cheatsheet/?action=${action}`, editingItem.value);
        const data = response.data;
        if (data.success) {
            toast.success(editingItem.value.id ? t('admin.cheatsheet.itemUpdated') : t('admin.cheatsheet.itemCreated'));
            itemDialog.value = false;
            await loadCategoryItems(selectedCategory.value!.id!);
            // Auto-generate screenshot after save
            await generateAndSaveScreenshot();
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.saveItemError'));
    }
}

function confirmDelete(type: 'category' | 'item', id: number) {
    deleteTarget.value = { type, id };
    deleteDialog.value = true;
}

async function executeDelete() {
    if (!deleteTarget.value) return;

    try {
        const action = deleteTarget.value.type === 'category' ? 'deleteCategory' : 'deleteItem';
        const response = await apiClientAuth.post(`/cheatsheet/?action=${action}`, { id: deleteTarget.value.id });
        const data = response.data;
        if (data.success) {
            toast.success(t('admin.cheatsheet.deleteSuccess'));
            deleteDialog.value = false;

            if (deleteTarget.value.type === 'category') {
                await loadCategories();
                if (selectedCategory.value?.id === deleteTarget.value.id) {
                    selectedCategory.value = null;
                }
            } else {
                await loadCategoryItems(selectedCategory.value!.id!);
            }
            // Auto-generate screenshot after delete
            await generateAndSaveScreenshot();
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.deleteError'));
    }
}

async function toggleCategoryEnabled(category: CheatsheetCategory) {
    try {
        const response = await apiClientAuth.post('/cheatsheet/?action=updateCategory', {
            ...category,
            enabled: !category.enabled
        });
        const data = response.data;
        if (data.success) {
            await loadCategories();
            toast.success(t('admin.cheatsheet.categoryUpdated'));
            // Auto-generate screenshot after toggle
            await generateAndSaveScreenshot();
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.updateError'));
    }
}

async function exportCategory(category: CheatsheetCategory) {
    try {
        const response = await apiClientAuth.get(`/cheatsheet/?action=exportCategory&id=${category.id}`);
        const data = response.data;
        if (data.success) {
            const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `cheatsheet-${category.name.toLowerCase().replace(/\s+/g, '-')}.json`;
            a.click();
            URL.revokeObjectURL(url);
            toast.success(t('admin.cheatsheet.exportSuccess'));
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.exportError'));
    }
}

async function importCategory() {
    try {
        const data = JSON.parse(importData.value);
        const response = await apiClientAuth.post('/cheatsheet/?action=importCategory', data);
        const result = response.data;
        if (result.success) {
            toast.success(t('admin.cheatsheet.importSuccess'));
            importDialog.value = false;
            importData.value = '';
            await loadCategories();
        } else {
            throw new Error(result.message);
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.importError'));
    }
}

// Drag & Drop functions
function onDragStart(category: CheatsheetCategory, index: number) {
    draggedCategory.value = category;
}

function onDragOver(event: DragEvent, index: number) {
    event.preventDefault();
    dragOverIndex.value = index;
}

function onDragLeave() {
    dragOverIndex.value = null;
}

function onDrop(event: DragEvent, targetIndex: number) {
    event.preventDefault();

    if (!draggedCategory.value) return;

    const sourceIndex = enabledCategories.value.findIndex(c => c.id === draggedCategory.value!.id);
    if (sourceIndex === -1 || sourceIndex === targetIndex) {
        draggedCategory.value = null;
        dragOverIndex.value = null;
        return;
    }

    // Reorder categories
    const allCategories = [...categories.value];
    const enabledCats = [...enabledCategories.value];

    // Remove from source and insert at target
    const [movedCat] = enabledCats.splice(sourceIndex, 1);
    enabledCats.splice(targetIndex, 0, movedCat);

    // Update order_index for all enabled categories
    enabledCats.forEach((cat, idx) => {
        const catInAll = allCategories.find(c => c.id === cat.id);
        if (catInAll) {
            catInAll.order_index = idx;
        }
    });

    categories.value = allCategories;

    // Save order to backend
    saveOrder();

    draggedCategory.value = null;
    dragOverIndex.value = null;
}

async function saveOrder() {
    try {
        const orderData = categories.value.map((cat, index) => ({
            id: cat.id,
            order_index: cat.order_index
        }));

        await apiClientAuth.post('/cheatsheet/?action=updateOrder', { categories: orderData });
        toast.success(t('admin.cheatsheet.orderUpdated') || 'Order updated');
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || 'Failed to save order');
    }
}

// Item Drag & Drop functions
function onItemDragStart(item: CheatsheetItem, index: number) {
    draggedItem.value = item;
}

function onItemDragOver(event: DragEvent, index: number) {
    event.preventDefault();
    dragOverItemIndex.value = index;
}

function onItemDragLeave() {
    dragOverItemIndex.value = null;
}

function onItemDrop(event: DragEvent, targetIndex: number) {
    event.preventDefault();

    if (!draggedItem.value || !selectedCategory.value?.id) return;

    const items = [...currentCategoryItems.value];
    const sourceIndex = items.findIndex(i => i.id === draggedItem.value!.id);

    if (sourceIndex === -1 || sourceIndex === targetIndex) {
        draggedItem.value = null;
        dragOverItemIndex.value = null;
        return;
    }

    // Reorder items
    const [movedItem] = items.splice(sourceIndex, 1);
    items.splice(targetIndex, 0, movedItem);

    // Update order_index for all items
    items.forEach((item, idx) => {
        item.order_index = idx;
    });

    // Update local state
    itemsByCategory.value[selectedCategory.value.id] = items;

    // Save order to backend
    saveItemsOrder();

    draggedItem.value = null;
    dragOverItemIndex.value = null;
}

async function saveItemsOrder() {
    if (!selectedCategory.value?.id) return;

    try {
        const itemIds = currentCategoryItems.value.map(item => item.id);
        await apiClientAuth.post('/cheatsheet/?action=reorderItems', { items: itemIds });
        toast.success(t('admin.cheatsheet.orderUpdated') || 'Order updated');
        // Auto-generate screenshot after reorder
        await generateAndSaveScreenshot();
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || 'Failed to save items order');
    }
}

// Column resize functions
function startResize(event: MouseEvent, columnKey: string) {
    event.preventDefault();
    event.stopPropagation();
    resizingColumn.value = columnKey;
    resizeStartX.value = event.clientX;
    resizeStartWidth.value = columnWidths.value[columnKey];

    document.addEventListener('mousemove', onResizeMove);
    document.addEventListener('mouseup', stopResize);
}

function onResizeMove(event: MouseEvent) {
    if (!resizingColumn.value) return;

    const diff = event.clientX - resizeStartX.value;
    const newWidth = Math.max(50, resizeStartWidth.value + diff);
    columnWidths.value[resizingColumn.value] = newWidth;
}

function stopResize() {
    resizingColumn.value = null;
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', stopResize);
}

// Preview table column resize functions
const resizingPreviewColumn = ref<{ categoryId: number; column: 'title' | 'description' } | null>(null);
const previewResizeStartX = ref(0);
const previewResizeStartWidth = ref(0);

function getPreviewColumnWidth(categoryId: number, column: 'title' | 'description'): number {
    if (!previewColumnWidths.value[categoryId]) {
        previewColumnWidths.value[categoryId] = { title: 150, description: 300 };
    }
    return previewColumnWidths.value[categoryId][column];
}

function startPreviewResize(event: MouseEvent, categoryId: number, column: 'title' | 'description') {
    event.preventDefault();
    event.stopPropagation();
    resizingPreviewColumn.value = { categoryId, column };
    previewResizeStartX.value = event.clientX;
    previewResizeStartWidth.value = getPreviewColumnWidth(categoryId, column);

    document.addEventListener('mousemove', onPreviewResizeMove);
    document.addEventListener('mouseup', stopPreviewResize);
}

function onPreviewResizeMove(event: MouseEvent) {
    if (!resizingPreviewColumn.value) return;

    const diff = event.clientX - previewResizeStartX.value;
    const newWidth = Math.max(50, previewResizeStartWidth.value + diff);

    if (!previewColumnWidths.value[resizingPreviewColumn.value.categoryId]) {
        previewColumnWidths.value[resizingPreviewColumn.value.categoryId] = { title: 150, description: 300 };
    }
    previewColumnWidths.value[resizingPreviewColumn.value.categoryId][resizingPreviewColumn.value.column] = newWidth;
}

function stopPreviewResize() {
    resizingPreviewColumn.value = null;
    document.removeEventListener('mousemove', onPreviewResizeMove);
    document.removeEventListener('mouseup', stopPreviewResize);
}

async function cycleWidth(category: CheatsheetCategory) {
    const widths: Array<'quarter' | 'third' | 'half' | 'full'> = ['quarter', 'third', 'half', 'full'];
    const currentIndex = widths.indexOf(category.width);
    const nextIndex = (currentIndex + 1) % widths.length;
    category.width = widths[nextIndex];

    // Save immediately
    try {
        await apiClientAuth.post('/cheatsheet/?action=updateCategory', category);
        toast.success(t('admin.cheatsheet.categoryUpdated'));
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.updateError'));
    }
}

async function cycleHeight(category: CheatsheetCategory) {
    const heights: Array<'auto' | 'small' | 'medium' | 'large'> = ['auto', 'small', 'medium', 'large'];
    const currentIndex = heights.indexOf(category.height);
    const nextIndex = (currentIndex + 1) % heights.length;
    category.height = heights[nextIndex];

    // Save immediately
    try {
        await apiClientAuth.post('/cheatsheet/?action=updateCategory', category);
        toast.success(t('admin.cheatsheet.categoryUpdated'));
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('admin.cheatsheet.updateError'));
    }
}

// Grid layout changed handler
function onGridLayoutUpdated(newLayout: GridLayoutItem[]) {
    isUpdatingFromDrag.value = true;

    // Update grid layout ref
    gridLayout.value = newLayout;

    // Update categories with new positions and sizes
    newLayout.forEach(item => {
        const category = categories.value.find(c => String(c.id) === item.i);
        if (category) {
            category.grid_x = item.x;
            category.grid_y = item.y;
            category.grid_w = item.w;
            category.grid_h = item.h;

            // Update width based on grid width
            if (item.w === 12) category.width = 'full';
            else if (item.w === 6) category.width = 'half';
            else if (item.w === 4) category.width = 'third';
            else if (item.w === 3) category.width = 'quarter';

            // Update height based on grid height
            if (item.h === 4) category.height = 'small';
            else if (item.h === 6) category.height = 'medium';
            else if (item.h === 9) category.height = 'large';
        }
    });

    isUpdatingFromDrag.value = false;

    // Debounced save
    saveGridLayout();
}

// Save grid layout to backend
let saveGridLayoutTimer: NodeJS.Timeout | null = null;
function saveGridLayout() {
    if (saveGridLayoutTimer) clearTimeout(saveGridLayoutTimer);

    saveGridLayoutTimer = setTimeout(async () => {
        try {
            const updates = categories.value.map(cat => ({
                id: cat.id,
                grid_x: cat.grid_x,
                grid_y: cat.grid_y,
                grid_w: cat.grid_w,
                grid_h: cat.grid_h,
                width: cat.width,
                height: cat.height
            }));

            await apiClientAuth.post('/cheatsheet/?action=updateGridLayout', { categories: updates });
            // Auto-generate screenshot after layout change
            await generateAndSaveScreenshot();
        } catch (err: any) {
            console.error('Failed to save grid layout:', err);
        }
    }, 1000);
}

// Screenshot state
const shareableImageUrl = ref<string | null>(null);
const generatingScreenshot = ref(false);
const isCapturingScreenshot = ref(false);

const fullShareableUrl = computed(() => {
    if (!shareableImageUrl.value) return null;
    return window.location.origin + shareableImageUrl.value;
});

async function exportAsImage() {
    if (!previewContainer.value) return;

    exporting.value = true;
    toast.info(t('admin.cheatsheet.exportingImage') || 'Generating image...');

    try {
        // Wait for next tick to ensure DOM is updated
        await new Promise(resolve => setTimeout(resolve, 100));

        const canvas = await html2canvas(previewContainer.value, {
            backgroundColor: '#111723',
            scale: 2, // Higher quality
            logging: false,
            useCORS: true,
            allowTaint: true
        });

        // Convert to blob and download
        canvas.toBlob((blob) => {
            if (!blob) {
                throw new Error('Failed to create image');
            }

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `cheatsheet-${new Date().toISOString().split('T')[0]}.png`;
            a.click();
            URL.revokeObjectURL(url);

            toast.success(t('admin.cheatsheet.imageExported') || 'Image exported successfully');
        }, 'image/png');
    } catch (err: any) {
        console.error('Export error:', err);
        toast.error(err.message || t('admin.cheatsheet.exportImageError') || 'Failed to export image');
    } finally {
        exporting.value = false;
    }
}

async function generateAndSaveScreenshot() {
    // Skip if not in preview mode
    if (viewMode.value !== 'preview') {
        return;
    }

    // Check if preview container exists and is a valid element
    if (!previewContainer.value || !(previewContainer.value instanceof HTMLElement)) {
        console.warn('Preview container not available, skipping screenshot');
        return;
    }

    // Check if element is in the DOM
    if (!document.body.contains(previewContainer.value)) {
        console.warn('Preview container not in DOM, skipping screenshot');
        return;
    }

    generatingScreenshot.value = true;
    isCapturingScreenshot.value = true;

    try {
        // Wait longer to ensure DOM is fully rendered and controls are hidden
        await new Promise(resolve => setTimeout(resolve, 500));

        // Re-check everything after wait
        if (!previewContainer.value || !(previewContainer.value instanceof HTMLElement)) {
            console.warn('Preview container no longer available');
            return;
        }

        if (!document.body.contains(previewContainer.value)) {
            console.warn('Preview container removed from DOM during wait');
            return;
        }

        const canvas = await html2canvas(previewContainer.value, {
            backgroundColor: '#111723',
            scale: 2,
            logging: false,
            useCORS: true,
            allowTaint: true
        });

        // Convert canvas to base64 data URL
        const imageDataUrl = canvas.toDataURL('image/png');

        // Send to backend to save
        const response = await apiClientAuth.post('/cheatsheet/?action=saveScreenshot', {
            image: imageDataUrl
        });

        const data = response.data;
        if (data.success) {
            shareableImageUrl.value = data.url;
            toast.success(t('admin.cheatsheet.screenshotSaved') || 'Shareable image updated');
        } else {
            throw new Error(data.message);
        }
    } catch (err: any) {
        console.error('Screenshot generation error:', err);
        // Silent fail - don't show error to user for auto-generation
    } finally {
        generatingScreenshot.value = false;
        isCapturingScreenshot.value = false;
    }
}

function copyImageUrl() {
    if (!fullShareableUrl.value) return;

    navigator.clipboard.writeText(fullShareableUrl.value);
    toast.success(t('admin.cheatsheet.urlCopied') || 'URL copied to clipboard');
}

onMounted(() => {
    loadCategories();

    // Load saved column widths from localStorage
    const savedWidths = localStorage.getItem('cheatsheet_column_widths');
    if (savedWidths) {
        try {
            columnWidths.value = JSON.parse(savedWidths);
        } catch (e) {
            console.error('Failed to parse saved column widths:', e);
        }
    }

    // Load saved preview column widths from localStorage
    const savedPreviewWidths = localStorage.getItem('cheatsheet_preview_column_widths');
    if (savedPreviewWidths) {
        try {
            previewColumnWidths.value = JSON.parse(savedPreviewWidths);
        } catch (e) {
            console.error('Failed to parse saved preview column widths:', e);
        }
    }
});

// Watch column widths and save to localStorage
watch(columnWidths, (newWidths) => {
    localStorage.setItem('cheatsheet_column_widths', JSON.stringify(newWidths));
}, { deep: true });

// Watch preview column widths and save to localStorage
watch(previewColumnWidths, (newWidths) => {
    localStorage.setItem('cheatsheet_preview_column_widths', JSON.stringify(newWidths));
}, { deep: true });

onUnmounted(() => {
    // Clean up any active resize listeners
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', stopResize);
    document.removeEventListener('mousemove', onPreviewResizeMove);
    document.removeEventListener('mouseup', stopPreviewResize);
});
</script>

<template>
    <div class="admin-cheatsheet-container">
        <!-- Top Toolbar -->
        <div class="top-toolbar">
            <div class="d-flex align-center">
                <v-icon class="mr-2" color="primary">mdi-book-open-variant</v-icon>
                <h2 class="text-h6">{{ t('admin.cheatsheet.title') || 'Cheatsheet Management' }}</h2>
            </div>

            <div class="d-flex align-center gap-2">
                <v-btn-toggle v-model="viewMode" mandatory density="compact" color="primary">
                    <v-btn value="preview" size="small">
                        <v-icon start>mdi-eye</v-icon>
                        {{ t('admin.cheatsheet.preview') || 'Preview' }}
                    </v-btn>
                    <v-btn value="edit" size="small">
                        <v-icon start>mdi-pencil</v-icon>
                        {{ t('admin.cheatsheet.edit') || 'Edit' }}
                    </v-btn>
                </v-btn-toggle>

                <v-divider vertical class="mx-2"></v-divider>

                <v-btn
                    prepend-icon="mdi-plus"
                    color="primary"
                    size="small"
                    @click="openCategoryDialog(null)"
                >
                    {{ t('admin.cheatsheet.addCategory') || 'Add Category' }}
                </v-btn>
                <v-btn
                    prepend-icon="mdi-upload"
                    color="secondary"
                    size="small"
                    variant="tonal"
                    @click="importDialog = true"
                >
                    {{ t('admin.cheatsheet.import') || 'Import' }}
                </v-btn>

                <v-btn
                    v-if="viewMode === 'preview'"
                    prepend-icon="mdi-image-export"
                    color="success"
                    size="small"
                    variant="tonal"
                    @click="exportAsImage"
                    :loading="exporting"
                >
                    {{ t('admin.cheatsheet.exportAsImage') || 'Export as Image' }}
                </v-btn>

                <v-divider v-if="viewMode === 'preview'" vertical class="mx-2"></v-divider>

                <!-- Generate Shareable Image Button -->
                <v-btn
                    v-if="viewMode === 'preview'"
                    prepend-icon="mdi-share-variant"
                    color="info"
                    size="small"
                    variant="tonal"
                    @click="generateAndSaveScreenshot"
                    :loading="generatingScreenshot"
                >
                    {{ t('admin.cheatsheet.generateShareable') || 'Generate Shareable' }}
                </v-btn>

                <!-- Copy Shareable URL Button -->
                <v-btn
                    v-if="viewMode === 'preview' && shareableImageUrl"
                    prepend-icon="mdi-content-copy"
                    color="primary"
                    size="small"
                    variant="tonal"
                    @click="copyImageUrl"
                >
                    {{ t('admin.cheatsheet.copyUrl') || 'Copy URL' }}
                </v-btn>
            </div>
        </div>

        <!-- Shareable Image URL Display -->
        <v-alert
            v-if="viewMode === 'preview' && shareableImageUrl"
            type="info"
            variant="tonal"
            density="compact"
            class="ma-4"
        >
            <div class="d-flex align-center justify-space-between">
                <div class="flex-grow-1">
                    <strong>{{ t('admin.cheatsheet.shareableUrl') || 'Shareable URL' }}:</strong>
                    <code class="ml-2">{{ fullShareableUrl }}</code>
                </div>
                <v-btn
                    icon="mdi-content-copy"
                    size="small"
                    variant="text"
                    @click="copyImageUrl"
                ></v-btn>
            </div>
        </v-alert>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <p class="mt-4">{{ t('common.loading') || 'Loading...' }}</p>
        </div>

        <!-- Main Content -->
        <div v-else class="main-content">
            <!-- Edit Mode: Split View -->
            <div v-if="viewMode === 'edit'" class="split-view">
                <!-- Left Panel: Categories List -->
                <div class="left-panel">
                    <div class="panel-header">
                        <h3 class="text-subtitle-1">{{ t('admin.cheatsheet.categories') || 'Categories' }}</h3>
                        <v-chip size="small" color="primary">{{ categories.length }}</v-chip>
                    </div>

                    <v-list class="category-list" density="compact">
                        <v-list-item
                            v-for="category in categories"
                            :key="category.id"
                            :active="selectedCategory?.id === category.id"
                            @click="selectCategory(category)"
                            class="category-item"
                        >
                            <template v-slot:prepend>
                                <v-icon :color="category.color">{{ category.icon }}</v-icon>
                            </template>

                            <v-list-item-title>{{ category.name }}</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip size="x-small" :color="category.color" variant="tonal" class="mr-1">
                                    {{ category.content_type }}
                                </v-chip>
                                <v-chip size="x-small" variant="tonal">
                                    {{ itemsByCategory[category.id!]?.length || 0 }} items
                                </v-chip>
                            </v-list-item-subtitle>

                            <template v-slot:append>
                                <v-switch
                                    :model-value="category.enabled"
                                    color="success"
                                    hide-details
                                    density="compact"
                                    @click.stop="toggleCategoryEnabled(category)"
                                    class="mr-2"
                                ></v-switch>
                                <v-btn
                                    icon="mdi-pencil"
                                    size="x-small"
                                    variant="text"
                                    @click.stop="openCategoryDialog(category)"
                                ></v-btn>
                                <v-btn
                                    icon="mdi-download"
                                    size="x-small"
                                    variant="text"
                                    @click.stop="exportCategory(category)"
                                ></v-btn>
                                <v-btn
                                    icon="mdi-delete"
                                    size="x-small"
                                    variant="text"
                                    color="error"
                                    @click.stop="confirmDelete('category', category.id!)"
                                ></v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                </div>

                <!-- Right Panel: Items Management -->
                <div class="right-panel">
                    <div v-if="selectedCategory" class="items-panel">
                        <div class="panel-header">
                            <div class="d-flex align-center">
                                <v-icon :color="selectedCategory.color" class="mr-2">{{ selectedCategory.icon }}</v-icon>
                                <h3 class="text-subtitle-1">{{ selectedCategory.name }}</h3>
                            </div>
                            <v-btn
                                prepend-icon="mdi-plus"
                                color="primary"
                                size="small"
                                @click="openItemDialog(null)"
                            >
                                {{ t('admin.cheatsheet.addItem') || 'Add Item' }}
                            </v-btn>
                        </div>

                        <div class="items-table-container">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th :style="{ width: `${columnWidths.drag}px` }" class="resizable-header">
                                            <div class="resize-handle" @mousedown="startResize($event, 'drag')"></div>
                                        </th>
                                        <th :style="{ width: `${columnWidths.title}px` }" class="resizable-header">
                                            {{ t('admin.cheatsheet.table.title') || 'Title' }}
                                            <div class="resize-handle" @mousedown="startResize($event, 'title')"></div>
                                        </th>
                                        <th :style="{ width: `${columnWidths.description}px` }" class="resizable-header">
                                            {{ t('admin.cheatsheet.table.description') || 'Description' }}
                                            <div class="resize-handle" @mousedown="startResize($event, 'description')"></div>
                                        </th>
                                        <th :style="{ width: `${columnWidths.enabled}px` }" class="resizable-header">
                                            {{ t('admin.cheatsheet.table.enabled') || 'Enabled' }}
                                            <div class="resize-handle" @mousedown="startResize($event, 'enabled')"></div>
                                        </th>
                                        <th :style="{ width: `${columnWidths.actions}px` }" class="resizable-header">
                                            {{ t('admin.cheatsheet.table.actions') || 'Actions' }}
                                            <div class="resize-handle" @mousedown="startResize($event, 'actions')"></div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in currentCategoryItems"
                                        :key="item.id"
                                        :class="{ 'drag-over': dragOverItemIndex === index }"
                                        draggable="true"
                                        @dragstart="onItemDragStart(item, index)"
                                        @dragover="onItemDragOver($event, index)"
                                        @dragleave="onItemDragLeave"
                                        @drop="onItemDrop($event, index)"
                                    >
                                        <td :style="{ width: `${columnWidths.drag}px` }" class="drag-handle-cell">
                                            <v-icon size="small" class="drag-handle-icon">mdi-drag-vertical</v-icon>
                                        </td>
                                        <td :style="{ width: `${columnWidths.title}px` }">{{ item.title }}</td>
                                        <td :style="{ width: `${columnWidths.description}px` }">{{ item.description }}</td>
                                        <td :style="{ width: `${columnWidths.enabled}px` }">
                                            <v-chip :color="item.enabled ? 'success' : 'error'" size="small">
                                                {{ item.enabled ? t('common.yes') : t('common.no') }}
                                            </v-chip>
                                        </td>
                                        <td :style="{ width: `${columnWidths.actions}px` }">
                                            <v-btn
                                                icon="mdi-pencil"
                                                size="small"
                                                variant="text"
                                                @click="openItemDialog(item)"
                                            ></v-btn>
                                            <v-btn
                                                icon="mdi-delete"
                                                size="small"
                                                variant="text"
                                                color="error"
                                                @click="confirmDelete('item', item.id)"
                                            ></v-btn>
                                        </td>
                                    </tr>
                                    <tr v-if="currentCategoryItems.length === 0">
                                        <td colspan="5" class="text-center pa-4 text-medium-emphasis">
                                            {{ t('admin.cheatsheet.noItems') || 'No items yet. Click "Add Item" to create one.' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <v-icon size="64" color="grey">mdi-arrow-left</v-icon>
                        <p class="mt-4">{{ t('admin.cheatsheet.selectCategory') || 'Select a category to manage items' }}</p>
                    </div>
                </div>
            </div>

            <!-- Preview Mode: Live Preview with Page Builder -->
            <div v-else class="preview-container">
                <v-alert variant="tonal" color="info" density="compact" class="mb-4">
                    <v-icon start>mdi-information</v-icon>
                    {{ t('admin.cheatsheet.pageBuilderHint') || 'Page Builder: Drag and resize cards to customize layout' }}
                </v-alert>

                <div ref="previewContainer" :class="{ 'capturing-screenshot': isCapturingScreenshot }">
                    <GridLayout
                        v-model:layout="gridLayout"
                    :col-num="12"
                    :row-height="50"
                    :is-draggable="true"
                    :is-resizable="true"
                    :vertical-compact="false"
                    :prevent-collision="false"
                    :use-css-transforms="true"
                    :margin="[16, 16]"
                    @layout-updated="onGridLayoutUpdated"
                >
                    <GridItem
                        v-for="item in gridLayoutWithCategories"
                        :key="item.i"
                        :i="item.i"
                        :x="item.x"
                        :y="item.y"
                        :w="item.w"
                        :h="item.h"
                        :min-w="3"
                        :min-h="3"
                        :max-w="12"
                        drag-allow-from=".drag-handle"
                    >
                        <v-card
                            elevation="2"
                            class="preview-card draggable-card"
                        >
                                <v-toolbar density="compact" flat :color="item.category.color" class="drag-handle">
                                    <v-icon v-if="!isCapturingScreenshot" class="drag-icon mr-2" size="small" color="white">mdi-drag</v-icon>
                                    <v-toolbar-title class="d-flex align-center">
                                        <v-icon start color="white">{{ item.category.icon }}</v-icon>
                                        <span class="text-subtitle-1">{{ item.category.name }}</span>
                                    </v-toolbar-title>
                                    <v-spacer></v-spacer>
                                    <v-chip size="small" label color="white" variant="text" class="no-drag">
                                        {{ itemsByCategory[item.category.id!]?.length || 0 }}
                                    </v-chip>
                                    <v-btn
                                        v-if="!isCapturingScreenshot"
                                        icon="mdi-pencil"
                                        size="x-small"
                                        variant="text"
                                        color="white"
                                        class="no-drag"
                                        @click.stop="openCategoryDialog(item.category)"
                                    ></v-btn>
                                </v-toolbar>
                                <v-divider></v-divider>

                                <div class="preview-content no-drag" :class="{ 'auto-height': item.category.auto_height }">
                                <!-- Table Content -->
                                <table
                                    v-if="item.category.content_type === 'table'"
                                    class="preview-data-table"
                                >
                                    <thead v-if="item.category.show_headers !== false">
                                        <tr>
                                            <th :style="{ width: `${getPreviewColumnWidth(item.category.id!, 'title')}px` }" class="preview-resizable-header">
                                                {{ t('cheatsheet.headers.title') || 'Title' }}
                                                <div class="preview-resize-handle" @mousedown="startPreviewResize($event, item.category.id!, 'title')"></div>
                                            </th>
                                            <th :style="{ width: `${getPreviewColumnWidth(item.category.id!, 'description')}px` }" class="preview-resizable-header">
                                                {{ t('cheatsheet.headers.description') || 'Description' }}
                                                <div class="preview-resize-handle" @mousedown="startPreviewResize($event, item.category.id!, 'description')"></div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="tableItem in itemsByCategory[item.category.id!]" :key="tableItem.id">
                                            <td :style="{ width: `${getPreviewColumnWidth(item.category.id!, 'title')}px` }">{{ tableItem.title }}</td>
                                            <td :style="{ width: `${getPreviewColumnWidth(item.category.id!, 'description')}px` }">{{ tableItem.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Grid Content -->
                                <v-table
                                    v-else-if="item.category.content_type === 'grid'"
                                    density="compact"
                                    class="data-table-dense"
                                    :class="{ 'no-headers': !item.category.show_headers }"
                                >
                                    <thead v-if="item.category.show_headers !== false">
                                        <tr>
                                            <th class="text-left font-weight-bold" style="width: 20%">Buchst.</th>
                                            <th class="text-left font-weight-bold" style="width: 30%">Wort</th>
                                            <th class="text-left font-weight-bold" style="width: 20%">Buchst.</th>
                                            <th class="text-left font-weight-bold" style="width: 30%">Wort</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="i in Math.ceil((itemsByCategory[item.category.id!]?.length || 0) / 2)" :key="i">
                                            <td><b>{{ itemsByCategory[item.category.id!]?.[i - 1]?.title }}</b></td>
                                            <td>{{ itemsByCategory[item.category.id!]?.[i - 1]?.description }}</td>
                                            <td><b>{{ itemsByCategory[item.category.id!]?.[i - 1 + Math.ceil((itemsByCategory[item.category.id!]?.length || 0) / 2)]?.title }}</b></td>
                                            <td>{{ itemsByCategory[item.category.id!]?.[i - 1 + Math.ceil((itemsByCategory[item.category.id!]?.length || 0) / 2)]?.description }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>

                                <!-- Image Content -->
                                <div v-else-if="item.category.content_type === 'image'" class="pa-4">
                                    <template v-for="imgItem in itemsByCategory[item.category.id!]" :key="imgItem.id">
                                        <v-img
                                            v-if="imgItem.image_url"
                                            :src="imgItem.image_url"
                                            class="mb-4"
                                            max-width="100%"
                                            max-height="250px"
                                            contain
                                        ></v-img>
                                        <v-alert
                                            v-if="imgItem.content_text"
                                            :color="item.category.color"
                                            variant="tonal"
                                            density="compact"
                                        >
                                            <div class="text-body-2" v-html="imgItem.content_text"></div>
                                        </v-alert>
                                    </template>
                                </div>

                                <!-- Text Content -->
                                <div v-else-if="item.category.content_type === 'text'" class="pa-4">
                                    <div v-for="textItem in itemsByCategory[item.category.id!]" :key="textItem.id" class="mb-3">
                                        <h4 v-if="textItem.title" class="text-subtitle-2 mb-1">{{ textItem.title }}</h4>
                                        <p v-if="textItem.content_text" class="text-body-2">{{ textItem.content_text }}</p>
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </GridItem>
                </GridLayout>
                </div>
            </div>
        </div>

        <!-- Category Dialog -->
        <v-dialog v-model="categoryDialog" max-width="800px" scrollable>
            <v-card v-if="editingCategory">
                <v-card-title>
                    {{ editingCategory.id ? t('admin.cheatsheet.editCategory') : t('admin.cheatsheet.newCategory') }}
                </v-card-title>

                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="8">
                            <v-text-field
                                v-model="editingCategory.name"
                                :label="t('admin.cheatsheet.form.name') || 'Name'"
                                variant="outlined"
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-switch
                                v-model="editingCategory.enabled"
                                :label="t('admin.cheatsheet.form.enabled') || 'Enabled'"
                                color="success"
                            ></v-switch>
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="editingCategory.description"
                                :label="t('admin.cheatsheet.form.description') || 'Description'"
                                variant="outlined"
                                rows="2"
                            ></v-textarea>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="editingCategory.icon"
                                :label="t('admin.cheatsheet.form.icon') || 'Icon (MDI)'"
                                variant="outlined"
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="editingCategory.color"
                                :label="t('admin.cheatsheet.form.color') || 'Color'"
                                :items="['primary', 'secondary', 'success', 'info', 'warning', 'error', 'purple', 'cyan']"
                                variant="outlined"
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="editingCategory.content_type"
                                :label="t('admin.cheatsheet.form.contentType') || 'Content Type'"
                                :items="['table', 'text', 'image', 'mixed', 'grid']"
                                variant="outlined"
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="editingCategory.width"
                                :label="t('admin.cheatsheet.form.width') || 'Width'"
                                :items="['full', 'half', 'third', 'quarter']"
                                variant="outlined"
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="editingCategory.height"
                                :label="t('admin.cheatsheet.form.height') || 'Height'"
                                :items="['auto', 'small', 'medium', 'large']"
                                variant="outlined"
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-switch
                                v-model="editingCategory.show_headers"
                                :label="t('admin.cheatsheet.form.showHeaders') || 'Show Table Headers'"
                                color="primary"
                                hint="Display headers for table content types"
                                persistent-hint
                            ></v-switch>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-switch
                                v-model="editingCategory.auto_height"
                                :label="t('admin.cheatsheet.form.autoHeight') || 'Auto-fit Height'"
                                color="primary"
                                hint="Table height adapts to number of entries"
                                persistent-hint
                            ></v-switch>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="categoryDialog = false">{{ t('common.cancel') || 'Cancel' }}</v-btn>
                    <v-btn color="primary" @click="saveCategory">{{ t('common.save') || 'Save' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Item Dialog -->
        <v-dialog v-model="itemDialog" max-width="800px" scrollable>
            <v-card v-if="editingItem">
                <v-card-title>
                    {{ editingItem.id ? t('admin.cheatsheet.editItem') : t('admin.cheatsheet.newItem') }}
                </v-card-title>

                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="editingItem.title"
                                :label="t('admin.cheatsheet.form.title') || 'Title'"
                                variant="outlined"
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model.number="editingItem.order_index"
                                :label="t('admin.cheatsheet.form.order') || 'Order'"
                                type="number"
                                variant="outlined"
                                density="comfortable"
                                hint="Lower numbers appear first"
                                persistent-hint
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-switch
                                v-model="editingItem.enabled"
                                :label="t('admin.cheatsheet.form.enabled') || 'Enabled'"
                                color="success"
                            ></v-switch>
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="editingItem.description"
                                :label="t('admin.cheatsheet.form.description') || 'Description'"
                                variant="outlined"
                                rows="2"
                            ></v-textarea>
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="editingItem.content_text"
                                :label="t('admin.cheatsheet.form.contentText') || 'Content Text (optional)'"
                                variant="outlined"
                                rows="4"
                            ></v-textarea>
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="editingItem.image_url"
                                :label="t('admin.cheatsheet.form.imageUrl') || 'Image URL (optional)'"
                                variant="outlined"
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="itemDialog = false">{{ t('common.cancel') || 'Cancel' }}</v-btn>
                    <v-btn color="primary" @click="saveItem">{{ t('common.save') || 'Save' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="500px">
            <v-card>
                <v-card-title>{{ t('admin.cheatsheet.confirmDelete') || 'Confirm Delete' }}</v-card-title>
                <v-card-text>
                    {{ t('admin.cheatsheet.deleteWarning') || 'Are you sure you want to delete this' }} {{ deleteTarget?.type }}?
                    <span v-if="deleteTarget?.type === 'category'" class="text-error">
                        {{ t('admin.cheatsheet.deleteCategoryWarning') || 'All items in this category will also be deleted.' }}
                    </span>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">{{ t('common.cancel') || 'Cancel' }}</v-btn>
                    <v-btn color="error" @click="executeDelete">{{ t('common.delete') || 'Delete' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Import Dialog -->
        <v-dialog v-model="importDialog" max-width="800px" scrollable>
            <v-card>
                <v-card-title>{{ t('admin.cheatsheet.importCategory') || 'Import Category' }}</v-card-title>

                <v-card-text>
                    <v-textarea
                        v-model="importData"
                        :label="t('admin.cheatsheet.importLabel') || 'Paste JSON data'"
                        variant="outlined"
                        rows="15"
                        placeholder='{"category": {...}, "items": [...]}'
                    ></v-textarea>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="importDialog = false">{{ t('common.cancel') || 'Cancel' }}</v-btn>
                    <v-btn color="primary" @click="importCategory">{{ t('admin.cheatsheet.import') || 'Import' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<style scoped>
.admin-cheatsheet-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    width: 100%;
    overflow: hidden;
    background: var(--k-surface);
}

.top-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
    flex-shrink: 0;
    z-index: 10;
}

.loading-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
}

.main-content {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Split View Styles */
.split-view {
    display: flex;
    height: 100%;
    overflow: hidden;
}

.left-panel {
    width: 350px;
    flex-shrink: 0;
    border-right: 1px solid var(--k-line);
    display: flex;
    flex-direction: column;
    background: var(--k-surface);
    overflow: hidden;
}

.right-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.panel-header {
    padding: 16px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.category-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.category-item {
    margin-bottom: 8px;
    border-radius: 8px;
    border: 1px solid var(--k-line);
    transition: all 0.2s ease;
}

.category-item:hover {
    background: var(--k-accent-weak);
    border-color: var(--k-accent-line);
}

.items-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.items-table-container {
    flex: 1;
    overflow: auto;
    background: var(--k-surface);
}

.items-table {
    width: auto;
    border-collapse: collapse;
}

.items-table thead tr th {
    position: sticky;
    top: 0;
    z-index: 1;
    font-weight: 600;
    background-color: #141c2e !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid var(--k-accent-weak) !important;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    text-align: left;
    white-space: nowrap;
}

.resizable-header {
    position: relative;
    user-select: none;
}

.resize-handle {
    position: absolute;
    top: 0;
    right: -3px;
    width: 6px;
    height: 100%;
    cursor: col-resize;
    z-index: 2;
    background: transparent;
    transition: background 0.2s;
}

.resize-handle:hover {
    background: var(--k-accent-line);
}

.resize-handle:active {
    background: var(--k-accent);
}

.items-table tbody tr {
    cursor: move;
    transition: all 0.2s ease;
    background-color: var(--k-surface);
    border-bottom: 1px solid var(--k-line);
}

.items-table tbody tr:hover {
    background: var(--k-sunken) !important;
}

.items-table tbody tr.drag-over {
    background: var(--k-accent-weak) !important;
    border-top: 2px solid var(--k-accent);
}

.items-table tbody tr td {
    padding: 10px 16px;
    font-size: 0.85rem;
    color: #e2e8f0;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

.drag-handle-cell {
    text-align: center;
    cursor: grab;
    padding: 10px 8px !important;
}

.drag-handle-cell:active {
    cursor: grabbing;
}

.drag-handle-icon {
    opacity: 0.5;
    transition: opacity 0.2s;
}

.items-table tbody tr:hover .drag-handle-icon {
    opacity: 1;
}

.empty-state {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
    color: var(--k-ink-faint);
}

/* Preview Styles */
.preview-container {
    padding: 24px;
    overflow-y: auto;
    height: 100%;
}

.preview-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.preview-card .v-toolbar {
    color: var(--k-ink) !important;
}

.preview-card .v-toolbar-title {
    color: var(--k-ink) !important;
}

.preview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    border-color: var(--k-accent-weak);
}

/* Vue Grid Layout Styles */
.vue-grid-layout {
    background: transparent;
    min-height: 500px;
}

.vue-grid-item {
    transition: all 200ms ease;
    transition-property: left, top, right, bottom;
}

.vue-grid-item.resizing {
    opacity: 0.9;
    z-index: 3;
}

.vue-grid-item.static {
    background: transparent;
}

.vue-grid-item .resizing {
    opacity: 0.9;
}

.vue-grid-item .no-drag {
    cursor: default;
}

.vue-resizable-handle {
    position: absolute;
    width: 20px;
    height: 20px;
    bottom: 0;
    right: 0;
    cursor: se-resize;
    background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTggMThoLTJ2LTJoMnYyem0tNiAwaDJ2LTJoLTJ2MnptMy02aDJ2LTJoLTJ2MnptLTMgM2gydi0yaC0ydjJ6bTYtNmgydi0yaC0ydjJ6bS0zIDNoMnYtMmgtMnYyeiIgZmlsbD0iIzNiODJmNiIvPjwvc3ZnPg==');
    background-position: bottom right;
    background-repeat: no-repeat;
    background-size: 20px 20px;
    opacity: 0.4;
    transition: opacity 0.2s;
}

.vue-grid-item:hover .vue-resizable-handle {
    opacity: 1;
}

.draggable-card {
    cursor: default;
    height: 100%;
}

.drag-handle {
    cursor: move;
    user-select: none;
}

.drag-icon {
    cursor: move;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.drag-handle:hover .drag-icon {
    opacity: 1;
}

.preview-content {
    flex: 1;
    overflow: auto;
}

.preview-content.auto-height {
    overflow: visible;
    height: auto;
}

/* Preview data table */
.preview-data-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.preview-data-table thead tr th {
    font-weight: 600;
    background-color: #141c2e !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid var(--k-accent-weak) !important;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 12px;
    text-align: left;
    white-space: nowrap;
}

.preview-resizable-header {
    position: relative;
    user-select: none;
}

.preview-resize-handle {
    position: absolute;
    top: 0;
    right: -3px;
    width: 6px;
    height: 100%;
    cursor: col-resize;
    z-index: 2;
    background: transparent;
    transition: background 0.2s;
}

.preview-resize-handle:hover {
    background: var(--k-accent-line);
}

.preview-resize-handle:active {
    background: var(--k-accent);
}

/* Hide resize handles and drag controls during screenshot capture */
.capturing-screenshot .preview-resize-handle,
.capturing-screenshot .vue-resizable-handle {
    display: none !important;
}

.capturing-screenshot .drag-handle {
    cursor: default !important;
}

.preview-data-table tbody tr {
    transition: background-color 0.2s ease;
}

.preview-data-table tbody tr:hover {
    background: var(--k-sunken) !important;
}

.preview-data-table tbody tr:nth-child(odd) {
    background-color: var(--k-surface);
}

.preview-data-table tbody tr:nth-child(even) {
    background-color: var(--k-surface);
}

.preview-data-table tbody tr td {
    padding: 6px 12px;
    font-size: 0.85rem;
    color: #e2e8f0;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

/* Table styling to match user-facing view */
.data-table-dense {
    background: transparent !important;
}

.data-table-dense .v-table__wrapper > table > thead > tr > th {
    font-weight: 600;
    background-color: #141c2e !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid var(--k-accent-weak) !important;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table-dense .v-table__wrapper > table > tbody > tr {
    transition: background-color 0.2s ease;
}

.data-table-dense .v-table__wrapper > table > tbody > tr:hover {
    background: var(--k-sunken) !important;
}

.data-table-dense .v-table__wrapper > table > tbody > tr:nth-child(odd) {
    background-color: var(--k-surface);
}

.data-table-dense .v-table__wrapper > table > tbody > tr:nth-child(even) {
    background-color: var(--k-surface);
}

.data-table-dense .v-table__wrapper > table > tbody > tr > td,
.data-table-dense .v-table__wrapper > table > thead > tr > th {
    padding: 6px 12px;
    font-size: 0.85rem;
    border: none;
}

/* Custom Scrollbar */
.category-list::-webkit-scrollbar,
.items-table-container::-webkit-scrollbar,
.preview-container::-webkit-scrollbar,
.preview-content::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.category-list::-webkit-scrollbar-track,
.items-table-container::-webkit-scrollbar-track,
.preview-container::-webkit-scrollbar-track,
.preview-content::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
}

.category-list::-webkit-scrollbar-thumb,
.items-table-container::-webkit-scrollbar-thumb,
.preview-container::-webkit-scrollbar-thumb,
.preview-content::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 4px;
}

.category-list::-webkit-scrollbar-thumb:hover,
.items-table-container::-webkit-scrollbar-thumb:hover,
.preview-container::-webkit-scrollbar-thumb:hover,
.preview-content::-webkit-scrollbar-thumb:hover {
    background: var(--k-row-hover);
}
</style>
