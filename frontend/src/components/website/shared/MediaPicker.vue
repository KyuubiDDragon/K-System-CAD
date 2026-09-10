<template>
    <div class="media-picker">
        <button @click="openPicker" class="btn btn-primary" type="button">
            <i class="mdi mdi-image"></i>
            {{ buttonText }}
        </button>

        <div v-if="isOpen" class="media-picker-modal">
            <div class="modal-overlay" @click="closePicker"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>{{ title }}</h3>
                    <button @click="closePicker" class="btn-icon">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="allowUpload" class="upload-section">
                        <label for="media-picker-upload" class="upload-label">
                            <i class="mdi mdi-cloud-upload"></i>
                            <span>Neue Datei hochladen</span>
                        </label>
                        <input
                            type="file"
                            id="media-picker-upload"
                            @change="handleUpload"
                            :accept="acceptedTypes"
                            class="upload-input"
                            :multiple="multiple"
                        />
                    </div>

                    <div class="filter-section">
                        <select v-model="localFilter" class="form-control">
                            <option value="all">Alle Medien</option>
                            <option value="image">Nur Bilder</option>
                            <option value="document">Nur Dokumente</option>
                        </select>
                    </div>

                    <div v-if="isLoading" class="loading-state">
                        <i class="mdi mdi-spinner fa-spin"></i> Medien werden geladen...
                    </div>

                    <div v-else-if="filteredItems.length > 0" class="media-grid">
                        <div
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="media-item"
                            :class="{
                                selected: isSelected(item),
                                'can-select': canSelectMore(item)
                            }"
                            @click="toggleSelection(item)"
                        >
                            <div class="media-preview">
                                <img
                                    v-if="isImageFile(item.file_type)"
                                    :src="getMediaUrl(item.file_path)"
                                    :alt="item.title || item.file_name"
                                />
                                <div v-else class="file-icon">
                                    <i :class="getFileIcon(item.file_type)"></i>
                                </div>
                                <div v-if="isSelected(item)" class="selection-indicator">
                                    <i class="mdi mdi-check-circle"></i>
                                </div>
                            </div>
                            <div class="media-info">
                                <div class="media-title">{{ item.title || item.file_name }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <p>Keine Medien verfügbar.</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closePicker" class="btn btn-secondary">
                        Abbrechen
                    </button>
                    <button
                        @click="confirmSelection"
                        class="btn btn-primary"
                        :disabled="selectedItems.length === 0"
                    >
                        {{ multiple ? `${selectedItems.length} auswählen` : 'Auswählen' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface MediaItem {
    id: number;
    title: string;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    media_type: string;
}

interface Props {
    media: MediaItem[];
    isLoading?: boolean;
    buttonText?: string;
    title?: string;
    multiple?: boolean;
    maxSelection?: number;
    acceptedTypes?: string;
    allowUpload?: boolean;
    filter?: string;
    getMediaUrl: (path: string) => string;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    buttonText: 'Medien auswählen',
    title: 'Medien auswählen',
    multiple: false,
    maxSelection: 1,
    acceptedTypes: 'image/*',
    allowUpload: true,
    filter: 'all',
});

const emit = defineEmits<{
    select: [items: MediaItem[]];
    upload: [event: Event];
}>();

const isOpen = ref(false);
const selectedItems = ref<MediaItem[]>([]);
const localFilter = ref(props.filter);

const filteredItems = computed(() => {
    if (localFilter.value === 'all') {
        return props.media;
    } else if (localFilter.value === 'image') {
        return props.media.filter(item => isImageFile(item.file_type));
    } else if (localFilter.value === 'document') {
        return props.media.filter(item => !isImageFile(item.file_type));
    }
    return props.media;
});

function openPicker() {
    isOpen.value = true;
    selectedItems.value = [];
}

function closePicker() {
    isOpen.value = false;
    selectedItems.value = [];
}

function isSelected(item: MediaItem): boolean {
    return selectedItems.value.some(selected => selected.id === item.id);
}

function canSelectMore(item: MediaItem): boolean {
    if (!props.multiple) return !isSelected(item);
    return selectedItems.value.length < props.maxSelection || isSelected(item);
}

function toggleSelection(item: MediaItem) {
    if (isSelected(item)) {
        selectedItems.value = selectedItems.value.filter(selected => selected.id !== item.id);
    } else {
        if (props.multiple) {
            if (selectedItems.value.length < props.maxSelection) {
                selectedItems.value.push(item);
            }
        } else {
            selectedItems.value = [item];
        }
    }
}

function confirmSelection() {
    emit('select', [...selectedItems.value]);
    closePicker();
}

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
</script>

<style scoped>
.media-picker {
    display: inline-block;
}

.media-picker-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    max-width: 900px;
    width: 90%;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--k-line);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.upload-section {
    margin-bottom: 1.5rem;
}

.upload-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--k-accent);
    color: var(--k-ink);
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.upload-label:hover {
    background: #0056b3;
}

.upload-input {
    display: none;
}

.filter-section {
    margin-bottom: 1.5rem;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.media-item {
    border: 2px solid var(--k-line);
    border-radius: 8px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.media-item:hover {
    border-color: var(--k-accent-line);
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.media-item.selected {
    border-color: var(--k-accent-line);
    background: #e7f3ff;
}

.media-item:not(.can-select) {
    opacity: 0.5;
    cursor: not-allowed;
}

.media-preview {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    background: var(--k-sunken);
    border-radius: 4px;
    overflow: hidden;
}

.media-preview img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: #666;
}

.selection-indicator {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: var(--k-accent);
    color: var(--k-ink);
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-info {
    margin-top: 0.5rem;
}

.media-title {
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.loading-state,
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #666;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--k-line);
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    font-size: 1.5rem;
    color: #666;
    transition: color 0.2s;
}

.btn-icon:hover {
    color: #000;
}
</style>
