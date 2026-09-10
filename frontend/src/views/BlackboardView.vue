<template>
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center" v-if="!showDetailView">
            <v-col cols="auto">
                <v-btn
                    v-if="hasWritePermission"
                    @click="openAddEntryDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-plus-box-outline"
                    elevation="2"
                    class="action-button"
                >
                    {{ t('blackboardView.addEntry') }}
                </v-btn>
            </v-col>
            <v-col>
                <v-alert
                    border="start"
                    border-color="primary"
                    elevation="2"
                    density="comfortable"
                    icon="mdi-information-outline"
                    variant="tonal"
                    class="mt-0 info-alert"
                >
                    <span v-if="boardType === 'area' && areaName">{{ t('blackboardView.infoArea', { area: areaName }) }}</span>
                    <span v-else-if="boardType === 'admin'">{{ t('blackboardView.infoAdmin') }}</span>
                    <span v-else-if="boardType === 'employee'">{{ t('blackboardView.infoEmployee') }}</span>
                    <span v-else-if="boardType === 'global'">{{ t('blackboardView.infoGlobal') }}</span>
                    <span v-else>{{ t('blackboardView.infoOther', { type: boardType }) }}</span>
                </v-alert>
            </v-col>
        </v-row>

        <!-- Entries List - Hidden when detail view is shown -->
        <v-row v-if="!showDetailView && loadingEntries" justify="center" class="my-10">
            <v-col cols="auto" class="text-center">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4 text-medium-emphasis">{{ t('blackboardView.loadingEntries') }}</p>
            </v-col>
        </v-row>

        <v-row v-else-if="!showDetailView && entries.length === 0">
            <v-col cols="12" class="empty-state">
                <v-icon size="64" color="grey-darken-1" class="mb-4">mdi-bulletin-board</v-icon>
                <span class="text-body-1">{{ t('blackboardView.noEntries') }}</span>
            </v-col>
        </v-row>

        <v-row v-else-if="!showDetailView">
            <v-col cols="12" v-for="entry in entries" :key="entry.id">
                <v-card
                    :id="`entry-${entry.id}`"
                    class="board-entry"
                    :prepend-icon="entry.pinned ? 'mdi-pin' : undefined"
                    elevation="4"
                    :class="{'pinned-entry': entry.pinned}"
                >
                    <div class="entry-color-bar" :style="`background-color: ${entry.color || 'var(--k-accent)'}`"></div>
                    
                    <template v-slot:title>
                        <div class="d-flex align-center">
                            <v-icon v-if="entry.pinned" icon="mdi-pin" color="primary" class="mr-2" size="small"></v-icon>
                            <span class="text-h6">{{ entry.title }}</span>
                        </div>
                    </template>
                    
                    <template v-slot:subtitle>
                        <span class="text-caption text-medium-emphasis">
                            Von {{ entry.author }} am {{ formatDate(entry.created) }}
                        </span>
                    </template>

                    <template v-slot:append v-if="hasWritePermission">
                        <div class="entry-actions">
                            <v-tooltip :text="entry.pinned ? t('unpin') : t('pin')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        size="small"
                                        variant="text"
                                        @click="entry.pinned ? unpinEntry(entry.id) : pinEntry(entry.id)"
                                        :loading="pinningEntry === entry.id"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon :color="entry.pinned ? 'primary' : 'grey'">{{
                                            entry.pinned ? 'mdi-pin-off' : 'mdi-pin'
                                        }}</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="t('edit')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon="mdi-pencil"
                                        size="small"
                                        variant="text"
                                        @click="openEditEntryDialog(entry)"
                                        v-bind="props"
                                        class="action-icon"
                                    ></v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip :text="t('delete')" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canDelete"
                                        icon="mdi-delete"
                                        size="small"
                                        variant="text"
                                        color="error"
                                        @click="openDeleteConfirmationDialog(entry)"
                                        v-bind="props"
                                        class="action-icon"
                                    ></v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </template>

                    <v-divider class="entry-divider"></v-divider>

                    <v-card-text class="entry-content pt-4">
                        <div v-html="entry.renderedText"></div>
                    </v-card-text>

                    <template v-if="entry.need_readed && boardType !== 'global'">
                        <v-divider class="entry-divider"></v-divider>
                        <v-card-text class="pt-3 pb-3 read-confirmation">
                            <div class="d-flex align-center flex-wrap">
                                <div class="read-confirmation-label">
                                    <v-icon size="small" color="info" class="mr-1">mdi-eye</v-icon>
                                    <strong>Gelesen von:</strong>
                                </div>
                                
                                <div class="read-by-list ml-2">
                                    <span
                                        v-if="!entry.readers || entry.readers.length === 0"
                                        class="text-caption text-grey"
                                    >Noch niemand</span>
                                    
                                    <v-chip
                                        v-else
                                        v-for="reader in entry.readers.split(',')"
                                        :key="reader"
                                        size="small"
                                        label
                                        variant="tonal"
                                        color="info"
                                        class="ma-1 reader-chip"
                                    >{{ reader.trim() }}</v-chip>
                                </div>
                                
                                <v-spacer></v-spacer>
                                
                                <v-btn
                                    prepend-icon="mdi-eye-check-outline"
                                    size="small"
                                    variant="tonal"
                                    color="success"
                                    @click="markAsRead(entry.id)"
                                    :loading="markingRead === entry.id"
                                    class="read-button"
                                >
                                    {{ t('blackboardView.markAsRead') }}
                                </v-btn>
                            </div>
                        </v-card-text>
                    </template>
                </v-card>
            </v-col>
        </v-row>

        <!-- Add/Edit Form - Shown instead of entries list -->
        <v-row v-if="showDetailView">
            <v-col cols="12">
                <v-card :loading="savingEntry" class="editor-card">
                    <v-toolbar color="primary" flat>
                        <v-btn icon="mdi-arrow-left" @click="closeAddEditDialog"></v-btn>
                        <v-toolbar-title class="text-h6">{{
                            isEditing ? t('blackboardView.editEntry') : t('blackboardView.newEntry')
                        }}</v-toolbar-title>
                    </v-toolbar>

                    <v-form ref="entryFormRef" v-model="isEntryFormValid">
                        <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        label="Titel"
                                        required
                                        v-model="entryFormData.title"
                                        :rules="[requiredRule('Titel')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        label="Autor"
                                        required
                                        v-model="entryFormData.author"
                                        :rules="[requiredRule('Autor')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <div class="text-body-2 mb-2">Inhalt</div>

                                    <TiptapEditor
                                        v-model="entryFormData.text"
                                        placeholder="Geben Sie hier Notizen oder zusätzliche Informationen ein..."
                                        :show-character-count="false"
                                        :show-source-button="true"
                                        :editable="true"
                                        class="editor-container"
                                    />
                                    <div
                                        v-if="!entryFormData.text"
                                        class="v-input__details text-error pa-1 text-caption"
                                    >
                                        Inhalt ist erforderlich.
                                    </div>
                                </v-col>
                                <v-col cols="12" sm="8">
                                    <div class="text-body-2 mb-2">Randfarbe</div>
                                    <v-color-picker
                                        v-model="entryFormData.color"
                                        :swatches="predefinedColors"
                                        show-swatches
                                        hide-canvas
                                        hide-sliders
                                        hide-inputs
                                        mode="hexa"
                                        width="100%"
                                        class="color-picker"
                                        style="background-color: var(--k-canvas) !important;"
                                    ></v-color-picker>
                                </v-col>
                                <v-col cols="12" sm="4" class="d-flex flex-column justify-center options-container">
                                    <v-checkbox
                                        v-if="boardType !== 'global'"
                                        label="Lesebestätigung erforderlich?"
                                        v-model="entryFormData.need_readed"
                                        density="comfortable"
                                        color="primary"
                                        hide-details
                                        class="mb-2"
                                    ></v-checkbox>
                                    <v-checkbox
                                        :label="t('pin') + '?'"
                                        v-model="entryFormData.pinned"
                                        density="comfortable"
                                        color="primary"
                                        hide-details
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    
                    <v-divider></v-divider>
                    
                    <v-card-actions class="dialog-actions pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog">{{ t('blackboardView.cancel') }}</v-btn>
                        <v-btn
                            v-if="hasWritePermission"
                            color="primary"
                            variant="elevated"
                            @click="saveEntry"
                            :disabled="!isEntryFormValid || !entryFormData.text"
                            :loading="savingEntry"
                        >{{ t('blackboardView.save') }}</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
            </v-col>
        </v-row>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteConfirmationDialog" max-width="500px" persistent>
            <v-card class="confirmation-dialog">
                <v-card-title class="text-h6">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    {{ t('blackboardView.deleteTitle') }}
                </v-card-title>
                
                <v-card-text class="pt-4">
                    <p>{{ t('blackboardView.deleteConfirm', { title: entryToDelete?.title }) }}</p>
                    <div class="text-caption text-medium-emphasis mt-2">{{ t('blackboardView.deleteWarning') }}</div>
                </v-card-text>
                
                <v-divider></v-divider>
                
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteConfirmationDialog" class="mr-2">{{ t('blackboardView.cancel') }}</v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        prepend-icon="mdi-delete"
                        @click="confirmDeleteEntry"
                        :loading="deletingEntry"
                        class="delete-button"
                    >{{ t('blackboardView.delete') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import TiptapEditor from '@/components/TiptapEditor.vue';
import { useToast } from 'vue-toastification';
import { useModulePermission } from '@/composables/useModulePermission';

// --- Interfaces ---
interface BlackboardEntry {
    id: number;
    title: string;
    author: string;
    text: string; // Raw HTML from CKEditor
    renderedText?: string; // Processed text with iframes
    color: string | null;
    need_readed: boolean; // Use boolean
    pinned: boolean; // Use boolean
    created: string; // Assuming datetime string from API
    readers: string | null; // Comma-separated string?
    area_id?: number | null; // Area ID if entry belongs to an area
    area_name?: string | null; // Area name from backend
    // Add other fields like updated_at if available
}

interface EntryFormData {
    id?: number | null;
    title: string;
    author: string;
    text: string;
    color: string | null;
    need_readed: boolean;
    pinned: boolean;
}

// --- Components & Store ---
const authStore = useAuthStore();

// --- Router & Permissions ---
const route = useRoute();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
  area_id?: number | string  // NEW: For area-based boards
  area_key?: string           // NEW: For area key
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined,
  area_id: undefined,
  area_key: undefined
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

const boardType = computed(() => {
    // NEW: Check if using area_id (highest priority)
    if (props.area_id || props.meta?.area_id || route.query.area_id) {
        return 'area';
    }
    // First check props.meta (for desktop windows)
    if (props.meta && props.meta.boardType) {
        return props.meta.boardType as string;
    }
    // Then check route.meta (for regular routes)
    if (route.meta && route.meta.boardType) {
        return route.meta.boardType as string;
    }
    // Default to 'global' if not set anywhere
    return 'global';
});

// NEW: Get area_id if in area mode
const areaId = computed(() => {
    if (props.area_id) return Number(props.area_id);
    if (props.meta?.area_id) return Number(props.meta.area_id);
    if (route.query.area_id) return Number(route.query.area_id);
    return null;
});

// NEW: Get area key
const areaKey = computed(() => {
    if (props.area_key) return props.area_key;
    if (props.meta?.area_key) return props.meta.area_key;
    if (route.query.area_key) return route.query.area_key;
    return null;
});

// Granular write permission check
const { hasAllPermissions, hasModulePermission } = useModulePermission();
const hasWritePermission = computed(() => {
    // Check if user has write permission for the specific blackboard type
    // boardType can be 'global', 'admin', 'employee', or 'area'
    return hasModulePermission('blackboard', 'WRITE') || hasAllPermissions.value;
});

// --- Component State ---
const entries = ref<BlackboardEntry[]>([]);
const areaName = ref<string>(''); // Store the actual area name for display
const loadingEntries = ref(false);
const savingEntry = ref(false);
const deletingEntry = ref(false);
const markingRead = ref<number | null>(null); // ID of entry being marked read
const pinningEntry = ref<number | null>(null); // ID of entry being pinned/unpinned

// --- Dialog States & Data ---
const showDetailView = ref(false);
const deleteConfirmationDialog = ref(false);
const entryFormRef = ref<any>(null); // Shared ref for add/edit forms
const isEntryFormValid = ref(false);

const initialFormData: EntryFormData = {
    id: null,
    title: '',
    author: '',
    text: '',
    color: '#212121',
    need_readed: false,
    pinned: false,
};
const entryFormData = reactive<EntryFormData>({ ...initialFormData });
const isEditing = computed(() => !!entryFormData.id);
const entryToDelete = ref<BlackboardEntry | null>(null);

// --- CKEditor Config & Colors ---
const predefinedColors: string[][] = [
    // Use single array for v-color-picker swatches
    ['#1976D2', '#2196F3', '#03A9F4', '#00BCD4'], // Blues
    ['#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B'], // Greens/Yellows
    ['#FF9800', '#FF5722', '#f44336', '#E91E63'], // Oranges/Reds/Pinks
    ['#673AB7', '#9C27B0', '#607D8B', '#9E9E9E'], // Purples/Greys
    ['#FFFFFF', '#BDBDBD', '#757575', '#212121'], // Greyscale
];

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

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

// --- oEmbed Conversion --- (Keep original logic, needs DOM access)
function createYouTubeIframe(url: string): HTMLIFrameElement | null {
    const videoIdMatch = url.match(/(?:v=|\/)([a-zA-Z0-9_-]{11})/); // More robust regex
    const videoId = videoIdMatch ? videoIdMatch[1] : null;
    if (!videoId) return null;
    // Use standard youtube embed URL
    const embedUrl = `https://www.youtube.com/embed/${videoId}`;
    const iframe = document.createElement('iframe');
    iframe.setAttribute('src', embedUrl);
    return iframe;
}

function createTwitchIframe(url: string): HTMLIFrameElement | null {
    // Extract clip ID or channel name/video ID depending on Twitch URL structure
    let embedUrl = '';
    const clipMatch = url.match(/clips\.twitch\.tv\/([a-zA-Z0-9_-]+)/);
    const videoMatch = url.match(/twitch\.tv\/videos\/(\d+)/);
    const channelMatch = url.match(/twitch\.tv\/([^/?]+)/);

    if (clipMatch) {
        embedUrl = `https://clips.twitch.tv/embed?clip=${clipMatch[1]}&parent=${window.location.hostname}`; // Added parent parameter
    } else if (videoMatch) {
        embedUrl = `https://player.twitch.tv/?video=${videoMatch[1]}&parent=${window.location.hostname}`; // Added parent parameter
    } else if (channelMatch) {
        // Embedding live channel might require different setup or permissions
        // embedUrl = `https://player.twitch.tv/?channel=${channelMatch[1]}&parent=${window.location.hostname}`;
        console.warn(
            'Direct Twitch channel embedding might not work reliably without further setup.'
        );
        return null; // Return null for channel embeds for now
    } else {
        return null;
    }

    const iframe = document.createElement('iframe');
    iframe.setAttribute('src', embedUrl);
    iframe.setAttribute('preload', 'auto'); // Added preload attribute
    return iframe;
}

function convertOembedToIframe(html: string | null): string {
    if (!html) return '';
    // Create a temporary, disconnected div to parse the HTML
    const div = document.createElement('div');
    div.innerHTML = html;
    const oembeds = div.querySelectorAll('oembed[url]');

    oembeds.forEach(oembed => {
        const urlAttr = oembed.getAttribute('url');
        if (!urlAttr) return;

        let iframe: HTMLIFrameElement | null = null;

        if (urlAttr.includes('youtube.com') || urlAttr.includes('youtu.be')) {
            iframe = createYouTubeIframe(urlAttr);
        } else if (urlAttr.includes('twitch.tv')) {
            iframe = createTwitchIframe(urlAttr);
        }
        // Add more providers if needed (Vimeo, etc.)

        if (iframe) {
            iframe.setAttribute('width', '100%'); // Responsive width
            iframe.setAttribute('height', '315'); // Default height, adjust as needed
            iframe.style.aspectRatio = '16 / 9'; // Maintain aspect ratio
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute(
                'allow',
                'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
            );
            iframe.setAttribute('allowfullscreen', 'true');

            // Replace the <oembed> tag with the generated <iframe>
            oembed.parentNode?.replaceChild(iframe, oembed);
        } else {
            // Optional: Replace oembed with a simple link if no iframe could be created
            const link = document.createElement('a');
            link.href = urlAttr;
            link.textContent = urlAttr;
            link.target = '_blank';
            oembed.parentNode?.replaceChild(link, oembed);
        }
    });
    return div.innerHTML; // Return the processed HTML string
}

// --- Data Fetching ---
const fetchAreaName = async () => {
    // Fetch area name if in area mode and we have an area_id
    if (boardType.value === 'area' && areaId.value) {
        try {
            const response = await apiClientAuth.get<any>('/blackboard/?action=getAreas');
            const areas = response.data || [];
            const currentArea = areas.find((a: any) => a.id === areaId.value);
            if (currentArea) {
                areaName.value = currentArea.name;
            }
        } catch (error: any) {
            console.error('Error fetching area name:', error);
            // Non-critical error, don't show toast
        }
    }
};

const fetchEntries = async () => {
    if (!boardType.value && !areaId.value) return; // Don't fetch if neither boardType nor areaId is set
    loadingEntries.value = true;
    try {
        // NEW: Construct URL based on mode (area vs legacy)
        let url = '/blackboard/?action=getEntries';
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        const response = await apiClientAuth.get<{ data: BlackboardEntry[] }>(url);
        entries.value = (response.data.data || response.data || [])
            .map(entry => ({
                ...entry,
                need_readed: Number(entry.need_readed) == 1 || entry.need_readed === true, // Ensure boolean
                pinned: Number(entry.pinned) == 1 || entry.pinned === true, // Ensure boolean
                renderedText: convertOembedToIframe(entry.text), // Convert oEmbeds immediately
            }))
            .sort((a, b) => {
                // Sort pinned first, then by creation date descending
                if (a.pinned !== b.pinned) return a.pinned ? -1 : 1;
                return new Date(b.created).getTime() - new Date(a.created).getTime();
            });

        // Extract area name from entries if in area mode
        if (boardType.value === 'area' && entries.value.length > 0 && entries.value[0].area_name) {
            areaName.value = entries.value[0].area_name;
        }
    } catch (error: any) {
        console.error('Error fetching entries:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Einträge.', 'error');
        entries.value = [];
    } finally {
        loadingEntries.value = false;
    }
};

// --- Methods ---

// Dialog Openers/Closers
const openAddEntryDialog = () => {
    Object.assign(entryFormData, { ...initialFormData, author: authStore.user?.username || '' }); // Reset form, prefill author
    isEntryFormValid.value = false;
    showDetailView.value = true;
    setTimeout(() => entryFormRef.value?.resetValidation(), 100);
};
const closeAddEditDialog = () => {
    showDetailView.value = false;
    Object.assign(entryFormData, initialFormData); // Reset form data
};

const openEditEntryDialog = (entry: BlackboardEntry) => {
    Object.assign(entryFormData, {
        ...entry,
        text: entry.text, // Use raw text for editor
    });
    isEntryFormValid.value = false;
    showDetailView.value = true;
    setTimeout(() => entryFormRef.value?.resetValidation(), 100);
};

const openDeleteConfirmationDialog = (entry: BlackboardEntry) => {
    entryToDelete.value = entry;
    deleteConfirmationDialog.value = true;
};
const closeDeleteConfirmationDialog = () => {
    deleteConfirmationDialog.value = false;
    entryToDelete.value = null;
};

// CRUD operations
const saveEntry = async () => {
    // Check CKEditor content manually
    if (!isEntryFormValid.value || !entryFormData.text?.trim()) {
        showSnackbar(t('blackboardView.validationError'), 'warning');
        return;
    }
    savingEntry.value = true;

    const isNew = !entryFormData.id;
    const action = isNew ? 'addEntry' : 'editEntry';
    // Convert booleans back to '1'/'0' if backend expects that
    const payload = {
        ...entryFormData,
        need_readed: entryFormData.need_readed ? 1 : 0,
        pinned: entryFormData.pinned ? 1 : 0,
        // NEW: Add area_id if in area mode
        ...(boardType.value === 'area' && areaId.value ? { area_id: areaId.value } : {})
    };
    if (isNew) delete payload.id;

    try {
        // NEW: Construct URL based on mode
        let url = `/blackboard/?action=${action}`;
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        await apiClientAuth.post(url, payload);
        closeAddEditDialog();
        await fetchEntries(); // Refresh list
        showSnackbar(`Beitrag erfolgreich ${isNew ? 'hinzugefügt' : 'aktualisiert'}.`, 'success');
    } catch (error: any) {
        console.error(`Error saving entry (Action: ${action}):`, error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern des Beitrags.', 'error');
    } finally {
        savingEntry.value = false;
    }
};

const confirmDeleteEntry = async () => {
    if (!entryToDelete.value) return;
    deletingEntry.value = true;
    try {
        // NEW: Construct URL based on mode
        let url = '/blackboard/?action=deleteEntry';
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        await apiClientAuth.post(url, {
            id: entryToDelete.value.id,
        });
        closeDeleteConfirmationDialog();
        await fetchEntries(); // Refresh list
        showSnackbar(t('blackboardView.entryDeleted'), 'success');
    } catch (error: any) {
        console.error('Error deleting entry:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Beitrags.', 'error');
    } finally {
        deletingEntry.value = false;
    }
};

// Other Actions
const markAsRead = async (entryId: number) => {
    if (!entryId) return;
    markingRead.value = entryId;
    try {
        // NEW: Construct URL based on mode
        let url = '/blackboard/?action=setReaded';
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        await apiClientAuth.post(url, {
            id: entryId,
        });
        await fetchEntries(); // Refresh to update reader list
        showSnackbar(t('blackboardView.markedRead'), 'success');
    } catch (error: any) {
        console.error('Error marking as read:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Markieren als gelesen.', 'error');
    } finally {
        markingRead.value = null;
    }
};

const pinEntry = async (entryId: number) => {
    if (!entryId) return;
    pinningEntry.value = entryId;
    try {
        // NEW: Construct URL based on mode
        let url = '/blackboard/?action=pinEntry';
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        await apiClientAuth.post(url, {
            id: entryId,
        });
        await fetchEntries(); // Refresh to update pinning and order
    } catch (error: any) {
        console.error('Error pinning entry:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Anpinnen.', 'error');
    } finally {
        pinningEntry.value = null;
    }
};

const unpinEntry = async (entryId: number) => {
    if (!entryId) return;
    pinningEntry.value = entryId;
    try {
        // NEW: Construct URL based on mode
        let url = '/blackboard/?action=unpinEntry';
        if (boardType.value === 'area' && areaId.value) {
            url += `&area_id=${areaId.value}`;
        } else {
            url += `&boardType=${boardType.value}`;
        }

        await apiClientAuth.post(url, {
            id: entryId,
        });
        await fetchEntries(); // Refresh to update pinning and order
    } catch (error: any) {
        console.error('Error unpinning entry:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Abpinnen.', 'error');
    } finally {
        pinningEntry.value = null;
    }
};

// --- Utility Functions ---
const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Ungültig';
        return date.toLocaleString('de-DE', { dateStyle: 'medium', timeStyle: 'short' }); // Include time
    } catch {
        return '?';
    }
};

// --- Watchers ---
// Watch for route changes to update boardType and refetch data
watch(
    () => route.meta.boardType,
    newBoardType => {
        if (newBoardType && route.path.includes('/blackboard/')) {
            // Check path too
            fetchEntries();
        }
    },
    { immediate: false }
); // Run on mount is handled separately

// Watch for area_id changes (when switching between different areas)
watch(
    () => route.params.area_id,
    async (newAreaId, oldAreaId) => {
        if (newAreaId && newAreaId !== oldAreaId && boardType.value === 'area') {
            console.log('Area ID changed:', oldAreaId, '->', newAreaId);
            // Reset area name and entries
            areaName.value = '';
            entries.value = [];
            // Fetch new area data
            await fetchAreaName();
            await fetchEntries();
        }
    },
    { immediate: false }
);

// Watch for full route path changes (backup for area switching)
watch(
    () => route.path,
    async (newPath, oldPath) => {
        if (newPath !== oldPath && newPath.includes('/blackboard/area/')) {
            console.log('Blackboard route changed:', oldPath, '->', newPath);
            // Reset and reload
            areaName.value = '';
            entries.value = [];
            await fetchAreaName();
            await fetchEntries();
        }
    },
    { immediate: false }
);

// Watch for props.meta changes (for desktop window mode)
watch(
    () => props.meta?.boardType,
    newBoardType => {
        if (newBoardType) {
            console.log('BoardType changed from props:', newBoardType);
            fetchEntries();
        }
    },
    { immediate: true }
);

// --- Lifecycle Hooks ---
onMounted(async () => {
    console.log('BlackboardView mounted with boardType:', boardType.value);
    if (boardType.value) {
        // Fetch area name first if in area mode
        await fetchAreaName();
        // Ensure boardType is available from route meta or props.meta
        await fetchEntries();
        
        // Check for ID from route query or props
        const entryId = route.query.id || props.id || props.meta?.id;
        
        if (entryId) {
            // Find the entry with the specified ID
            const entry = entries.value.find(e => e.id === Number(entryId));
            if (entry) {
                // Scroll to and highlight the entry
                await nextTick();
                const entryElement = document.getElementById(`entry-${entry.id}`);
                if (entryElement) {
                    entryElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add a temporary highlight effect
                    entryElement.classList.add('highlight-entry');
                    setTimeout(() => {
                        entryElement.classList.remove('highlight-entry');
                    }, 3000);
                }
            }
        }
    } else {
        console.error('Board type not found in route meta or props.');
        showSnackbar(t('blackboardView.typeError'), 'error');
    }
});
</script>
<style lang="scss">
@import '@/scss/main.scss'; // Import your variables

/* Highlight animation for entries when navigated to via ID */
.highlight-entry {
    animation: highlightPulse 0.5s ease-in-out 3;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.6) !important;
}

@keyframes highlightPulse {
    0% {
        transform: translateY(-2px) scale(1);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }
    50% {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.8);
    }
    100% {
        transform: translateY(-2px) scale(1);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Board Entries */
.board-entry {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    background-color: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    margin-bottom: 16px;
}

.board-entry:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.pinned-entry {
    border: 1px solid rgba(59, 130, 246, 0.3);
    background-color: rgba(30, 64, 175, 0.05) !important;
}

.entry-color-bar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.entry-content {
    font-size: 1rem;
    line-height: 1.6;
}

.entry-content :deep(iframe) {
    max-width: 100%;
    aspect-ratio: 16 / 9;
    height: auto;
    margin: 16px 0;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.entry-content :deep(p) {
    margin-bottom: 0.8em;
}

.entry-content :deep(a) {
    color: #60a5fa;
    text-decoration: none;
    transition: color 0.2s;
}

.entry-content :deep(a:hover) {
    color: #93c5fd;
    text-decoration: underline;
}

.entry-divider {
    opacity: 0.1;
}

/* Entry Actions */
.entry-actions {
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

/* Read Confirmation */
.read-confirmation {
    background-color: rgba(30, 41, 59, 0.3);
}

.read-confirmation-label {
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.read-by-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-width: 70%;
}

.reader-chip {
    transition: all 0.2s;
}

.reader-chip:hover {
    transform: translateY(-2px);
}

.read-button {
    transition: all 0.2s;
    text-transform: none;
    letter-spacing: normal;
}

.read-button:hover {
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 0;
    color: var(--text-secondary);
    text-align: center;
}

/* Editor Card Styling */
.editor-card {
    border-radius: 12px;
    overflow: hidden;
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--card-border);
}

.editor-container {
    border-radius: 8px;
    overflow: hidden;
}

.color-picker :deep(.v-color-picker__controls) {
    background-color: var(--k-canvas) !important;
    border-radius: 8px;
}

.options-container {
    padding: 16px;
    background-color: rgba(30, 41, 59, 0.3);
    border-radius: 8px;
    margin-top: 8px;
}

.dialog-actions {
    background-color: rgba(17, 24, 39, 0.5);
}

.confirmation-dialog {
    border-radius: 12px;
    overflow: hidden;
    background-color: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.delete-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .read-by-list {
        max-width: 100%;
        margin-top: 8px;
        margin-left: 0;
    }
    
    .read-confirmation .d-flex {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .read-confirmation .v-btn {
        margin-top: 12px;
        align-self: flex-end;
    }
}
</style>
