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
    show_headers?: boolean;
    auto_height?: boolean;
    grid_x?: number;
    grid_y?: number;
    grid_w?: number;
    grid_h?: number;
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
const categoryDialog = ref(false);
const selectedCategory = ref<CheatsheetCategory | null>(null);

// Grid layout state
const gridLayout = ref<GridLayoutItem[]>([]);

// Preview container ref for export
const previewContainer = ref<HTMLElement | null>(null);
const exporting = ref(false);

// Preview table column widths (per category) - shared with admin
const previewColumnWidths = ref<Record<number, { title: number; description: number }>>({});

// Column resize state
const resizingPreviewColumn = ref<{ categoryId: number; column: 'title' | 'description' } | null>(null);
const previewResizeStartX = ref(0);
const previewResizeStartWidth = ref(0);

// --- Computed ---
const enabledCategories = computed(() => categories.value.filter(c => c.enabled));

const selectedCategoryItems = computed(() => {
    if (!selectedCategory.value?.id) return [];
    return itemsByCategory.value[selectedCategory.value.id] || [];
});

// Watch categories and sync to grid layout
watch(enabledCategories, (cats) => {
    console.log('CheatsheetView: enabledCategories changed, count:', cats.length);
    let currentX = 0;
    let currentY = 0;

    gridLayout.value = cats.map((category) => {
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

        return {
            i: String(category.id),
            x,
            y,
            w,
            h
        };
    });
}, { immediate: true, deep: true });

// Grid layout with category data
const gridLayoutWithCategories = computed(() => {
    const result = gridLayout.value.map(item => {
        const category = getCategoryById(item.i);
        if (!category) {
            console.warn('CheatsheetView: Category not found for item:', item.i);
            return null;
        }
        return {
            ...item,
            category
        };
    }).filter(item => item !== null);
    console.log('CheatsheetView: gridLayoutWithCategories count:', result.length);
    return result;
});

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

// Find category by ID
const getCategoryById = (id: string): CheatsheetCategory | undefined => {
    return categories.value.find(c => String(c.id) === id);
};

// --- Methods ---
async function loadCheatsheet() {
    loading.value = true;
    try {
        const response = await apiClientAuth.get('/cheatsheet/?action=getAll');
        const data = response.data;
        console.log('CheatsheetView: API response:', data);
        if (data.success) {
            // Convert enabled from 1/0 to boolean, default to true if not present
            categories.value = data.categories.map((cat: any) => ({
                ...cat,
                enabled: cat.enabled !== undefined ? (cat.enabled === 1 || cat.enabled === '1' || cat.enabled === true) : true,
                show_headers: cat.show_headers !== undefined ? (cat.show_headers === 1 || cat.show_headers === '1' || cat.show_headers === true) : true,
                auto_height: cat.auto_height !== undefined ? (cat.auto_height === 1 || cat.auto_height === '1' || cat.auto_height === true) : false
            }));
            itemsByCategory.value = data.items;
            console.log('CheatsheetView: Loaded categories:', categories.value.length, 'items:', Object.keys(itemsByCategory.value).length);
        } else {
            throw new Error(data.message || 'Failed to load cheatsheet');
        }
    } catch (err: any) {
        toast.error(err.response?.data?.error || err.message || t('cheatsheetView.loadError'));
        console.error('Error loading cheatsheet:', err);
    } finally {
        loading.value = false;
    }
}

function openCategoryDialog(category: CheatsheetCategory) {
    selectedCategory.value = category;
    categoryDialog.value = true;
}

function closeCategoryDialog() {
    categoryDialog.value = false;
    selectedCategory.value = null;
}

// Preview table column resize functions
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

async function exportAsImage() {
    if (!previewContainer.value) return;

    exporting.value = true;
    toast.info(t('cheatsheetView.exportingImage') || 'Generating image...');

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

            toast.success(t('cheatsheetView.imageExported') || 'Image exported successfully');
        }, 'image/png');
    } catch (err: any) {
        console.error('Export error:', err);
        toast.error(err.message || t('cheatsheetView.exportImageError') || 'Failed to export image');
    } finally {
        exporting.value = false;
    }
}

onMounted(() => {
    loadCheatsheet();

    // Load saved preview column widths from localStorage (shared with admin)
    const savedPreviewWidths = localStorage.getItem('cheatsheet_preview_column_widths');
    if (savedPreviewWidths) {
        try {
            previewColumnWidths.value = JSON.parse(savedPreviewWidths);
        } catch (e) {
            console.error('Failed to parse saved preview column widths:', e);
        }
    }
});

// Watch preview column widths and save to localStorage
watch(previewColumnWidths, (newWidths) => {
    localStorage.setItem('cheatsheet_preview_column_widths', JSON.stringify(newWidths));
}, { deep: true });

onUnmounted(() => {
    // Clean up resize listeners
    document.removeEventListener('mousemove', onPreviewResizeMove);
    document.removeEventListener('mouseup', stopPreviewResize);
});
</script>

<template>
    <v-container fluid class="cheatsheet pa-6">
        <!-- Export Button - Top Right -->
        <div class="export-button-container">
            <v-btn
                prepend-icon="mdi-image-export"
                color="success"
                variant="tonal"
                @click="exportAsImage"
                :loading="exporting"
            >
                {{ t('cheatsheetView.exportAsImage') || 'Export as Image' }}
            </v-btn>
        </div>

        <!-- Header -->
        <v-row justify="center" class="header-section mb-6">
            <v-col cols="12" lg="10" xl="8" class="text-center">
                <v-img
                    src="/img/lsfdschriftzug.png"
                    class="mb-3 centered-image header-logo"
                    max-width="1000px"
                    max-height="80px"
                    contain
                ></v-img>
                <h1 class="text-h4 text-md-h3 font-weight-light mb-2">
                    <span class="gradient-text">{{ t('cheatsheetView.title') }}</span>
                </h1>
                <p class="text-body-2 text-medium-emphasis">
                    {{ t('cheatsheetView.description') }}
                </p>
            </v-col>
        </v-row>

        <!-- Loading State -->
        <v-row v-if="loading" justify="center">
            <v-col cols="12" class="text-center">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4">{{ t('common.loading') || 'Loading...' }}</p>
            </v-col>
        </v-row>

        <!-- Grid Layout -->
        <div v-else class="grid-container" ref="previewContainer">
            <GridLayout
                v-model:layout="gridLayout"
                :col-num="12"
                :row-height="50"
                :is-draggable="false"
                :is-resizable="false"
                :vertical-compact="false"
                :prevent-collision="true"
                :use-css-transforms="true"
                :margin="[16, 16]"
            >
                <GridItem
                    v-for="item in gridLayoutWithCategories"
                    :key="item.i"
                    :i="item.i"
                    :x="item.x"
                    :y="item.y"
                    :w="item.w"
                    :h="item.h"
                >
                    <v-card elevation="2" class="preview-card">
                        <v-toolbar density="compact" flat :color="item.category.color">
                            <v-toolbar-title class="d-flex align-center">
                                <v-icon start>{{ item.category.icon }}</v-icon>
                                <span class="text-subtitle-1">{{ item.category.name }}</span>
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-chip size="small" label color="white" variant="text">
                                {{ itemsByCategory[item.category.id!]?.length || 0 }}
                            </v-chip>
                            <v-btn
                                icon="mdi-open-in-new"
                                size="x-small"
                                variant="text"
                                color="white"
                                @click.stop="openCategoryDialog(item.category)"
                            ></v-btn>
                        </v-toolbar>
                        <v-divider></v-divider>

                        <div class="preview-content" :class="{ 'auto-height': item.category.auto_height }">
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

        <!-- Category Dialog -->
        <v-dialog v-model="categoryDialog" max-width="1000px" scrollable>
            <v-card v-if="selectedCategory">
                <v-toolbar :color="selectedCategory.color" density="compact">
                    <v-icon start class="ml-2">{{ selectedCategory.icon }}</v-icon>
                    <v-toolbar-title>{{ selectedCategory.name }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon @click="closeCategoryDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <v-card-text style="max-height: 70vh;" class="pa-0">
                    <!-- Table Content Type in Dialog -->
                    <v-table
                        v-if="selectedCategory.content_type === 'table'"
                        density="comfortable"
                        class="data-table-dense"
                    >
                        <thead>
                            <tr>
                                <th class="text-left font-weight-bold">{{ t('cheatsheet.headers.title') || 'Title' }}</th>
                                <th class="text-left font-weight-bold">{{ t('cheatsheet.headers.description') || 'Description' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dialogItem in selectedCategoryItems" :key="dialogItem.id">
                                <td>{{ dialogItem.title }}</td>
                                <td>{{ dialogItem.description }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <!-- Grid Content Type in Dialog -->
                    <v-table
                        v-else-if="selectedCategory.content_type === 'grid'"
                        density="comfortable"
                        class="data-table-dense"
                    >
                        <thead>
                            <tr>
                                <th class="text-left font-weight-bold">{{ t('cheatsheet.headers.title') || 'Title' }}</th>
                                <th class="text-left font-weight-bold">{{ t('cheatsheet.headers.description') || 'Description' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dialogItem in selectedCategoryItems" :key="dialogItem.id">
                                <td><b>{{ dialogItem.title }}</b></td>
                                <td>{{ dialogItem.description }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <!-- Image/Text Content Types in Dialog -->
                    <div v-else class="pa-4">
                        <div v-for="dialogItem in selectedCategoryItems" :key="dialogItem.id" class="mb-4">
                            <h3 v-if="dialogItem.title" class="text-h6 mb-2">{{ dialogItem.title }}</h3>
                            <v-img
                                v-if="dialogItem.image_url"
                                :src="dialogItem.image_url"
                                class="mb-2"
                                max-width="100%"
                                contain
                            ></v-img>
                            <p v-if="dialogItem.content_text" class="text-body-1">{{ dialogItem.content_text }}</p>
                        </div>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Footer -->
        <v-row class="mt-6">
            <v-col cols="12" class="text-center text-caption text-medium-emphasis">
                Los Santos Fire Department © {{ new Date().getFullYear() }} • Alle Angaben ohne Gewähr
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped>
/* Base Styles */
.cheatsheet {
    min-height: 89vh;
    background-color: var(--k-canvas);
    color: #e2e8f0;
    position: relative;
}

/* Export Button Container */
.export-button-container {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 10;
}

/* Header Section */
.header-section {
    position: relative;
}

.header-logo {
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
    animation: fadeIn 1s ease-out;
}

.gradient-text {
    background: linear-gradient(90deg, var(--k-accent), var(--k-accent));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

/* Grid Container */
.grid-container {
    width: 100%;
    min-height: 500px;
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

/* Card Styles */
.preview-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
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

.preview-content {
    flex: 1;
    overflow: auto;
}

.preview-content.auto-height {
    overflow: visible;
    height: auto;
}

/* Table Styling */
/* Preview data table with resizable columns */
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

.data-table-dense .v-table__wrapper > table > tbody > tr > td {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

/* Image Styling */
.centered-image {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

/* Custom Scrollbar */
.preview-content::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.preview-content::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
}

.preview-content::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 4px;
}

.preview-content::-webkit-scrollbar-thumb:hover {
    background: var(--k-row-hover);
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
