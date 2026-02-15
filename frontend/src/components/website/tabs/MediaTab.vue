<template>
    <div class="media-tab">
        <h2>Medien verwalten</h2>
        <p>
            Hier können Sie Bilder und andere Medien für Ihre Website hochladen und
            verwalten.
        </p>

        <div class="media-upload-section">
            <div class="media-upload">
                <label for="media-upload" class="media-upload-label">
                    <i class="mdi mdi-cloud-upload-alt"></i>
                    <span>Dateien hochladen</span>
                </label>
                <input
                    type="file"
                    id="media-upload"
                    @change="handleUpload"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                    class="media-upload-input"
                    multiple
                />
            </div>
        </div>

        <div class="media-filter">
            <select v-model="localFilter" class="form-control">
                <option value="all">Alle Medien</option>
                <option value="image">Bilder</option>
                <option value="document">Dokumente</option>
                <option value="banner">Banner</option>
                <option value="logo">Logos</option>
                <option value="background">Hintergrundbilder</option>
            </select>
        </div>

        <div v-if="isLoading" class="loading-media">
            <i class="mdi mdi-spinner fa-spin"></i> Medien werden geladen...
        </div>

        <div v-else-if="filteredMediaItems.length > 0" class="media-grid">
            <div v-for="item in filteredMediaItems" :key="item.id" class="media-item">
                <div class="media-preview">
                    <img
                        v-if="isImageFile(item.file_type)"
                        :src="getMediaUrl(item.file_path)"
                        :alt="item.title || item.file_name"
                        class="media-thumbnail"
                    />
                    <div v-else class="document-preview">
                        <i :class="getFileIcon(item.file_type)"></i>
                    </div>
                </div>
                <div class="media-info">
                    <div class="media-title">
                        {{ item.title || truncateText(item.file_name, 20) }}
                    </div>
                    <div class="media-meta">
                        <span>{{ formatFileSize(item.file_size) }}</span>
                        <span>{{ item.media_type }}</span>
                    </div>
                </div>
                <div class="media-actions">
                    <button
                        @click="$emit('edit', item)"
                        class="btn-icon"
                        title="Bearbeiten"
                    >
                        <i class="mdi mdi-pencil"></i>
                    </button>
                    <button
                        @click="$emit('delete', item)"
                        class="btn-icon"
                        title="Löschen"
                    >
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="empty-state">
            <p>
                Keine Medien vorhanden. Laden Sie Medien hoch, um sie hier zu sehen.
            </p>
        </div>

        <div v-if="showEditForm" class="media-edit-form">
            <div class="form-header">
                <h3>Medien bearbeiten</h3>
                <button @click="$emit('closeEdit')" class="btn-icon">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label for="media-title">Titel</label>
                    <input
                        type="text"
                        id="media-title"
                        v-model="editForm.title"
                        class="form-control"
                    />
                </div>
                <div class="form-group">
                    <label for="media-alt">Alt-Text (für Bilder)</label>
                    <input
                        type="text"
                        id="media-alt"
                        v-model="editForm.alt_text"
                        class="form-control"
                    />
                </div>
                <div class="form-group">
                    <label for="media-description">Beschreibung</label>
                    <textarea
                        id="media-description"
                        v-model="editForm.description"
                        class="form-control"
                        rows="3"
                    ></textarea>
                </div>
                <div class="form-group">
                    <label for="media-type">Medientyp</label>
                    <select
                        id="media-type"
                        v-model="editForm.media_type"
                        class="form-control"
                    >
                        <option value="content">Content</option>
                        <option value="gallery">Galerie</option>
                        <option value="banner">Banner</option>
                        <option value="logo">Logo</option>
                        <option value="background">Hintergrund</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            v-model="editForm.is_featured"
                        />
                        <span>Hervorgehoben</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button @click="$emit('closeEdit')" class="btn btn-secondary">
                    Abbrechen
                </button>
                <button @click="$emit('save', editForm)" class="btn btn-primary">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

interface MediaItem {
    id: number;
    title: string;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    media_type: string;
    alt_text?: string;
    description?: string;
    is_featured?: boolean;
}

interface MediaEditForm {
    id: number | null;
    title: string;
    alt_text: string;
    description: string;
    media_type: string;
    is_featured: boolean;
}

interface Props {
    media: MediaItem[];
    isLoading?: boolean;
    filter?: string;
    showEditForm?: boolean;
    editForm?: MediaEditForm;
    getMediaUrl: (path: string) => string;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    filter: 'all',
    showEditForm: false,
    editForm: () => ({
        id: null,
        title: '',
        alt_text: '',
        description: '',
        media_type: 'content',
        is_featured: false,
    }),
});

const emit = defineEmits<{
    upload: [event: Event];
    edit: [item: MediaItem];
    delete: [item: MediaItem];
    save: [form: MediaEditForm];
    closeEdit: [];
    'update:filter': [value: string];
}>();

const localFilter = ref(props.filter);

watch(localFilter, (newValue) => {
    emit('update:filter', newValue);
});

watch(() => props.filter, (newValue) => {
    localFilter.value = newValue;
});

const filteredMediaItems = computed(() => {
    if (localFilter.value === 'all') {
        return props.media;
    } else if (localFilter.value === 'image') {
        return props.media.filter(item => isImageFile(item.file_type));
    } else if (localFilter.value === 'document') {
        return props.media.filter(item => !isImageFile(item.file_type));
    } else {
        return props.media.filter(item => item.media_type === localFilter.value);
    }
});

function handleUpload(event: Event) {
    emit('upload', event);
}

function isImageFile(fileType: string): boolean {
    const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    return imageTypes.includes(fileType) || fileType.startsWith('image/');
}

function getFileIcon(fileType: string): string {
    if (fileType.includes('pdf')) {
        return 'mdi mdi-file-pdf';
    } else if (fileType.includes('doc') || fileType.includes('word')) {
        return 'mdi mdi-file-word';
    } else if (fileType.includes('xls') || fileType.includes('excel')) {
        return 'mdi mdi-file-excel';
    } else if (fileType.includes('ppt') || fileType.includes('powerpoint')) {
        return 'mdi mdi-file-powerpoint';
    } else if (fileType.includes('zip') || fileType.includes('rar')) {
        return 'mdi mdi-file-archive';
    } else if (fileType.includes('text') || fileType.includes('txt')) {
        return 'mdi mdi-file-alt';
    } else {
        return 'mdi mdi-file';
    }
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function truncateText(text: string, maxLength: number): string {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + '...';
}
</script>

<style scoped>
/* Media tab specific styles can be imported from parent or defined here */
</style>
