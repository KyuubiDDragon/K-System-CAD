<template>
    <div class="settings-section">
        <h3>Bilder und Medien</h3>

        <div class="form-group">
            <label>Logo</label>
            <div class="media-upload-container">
                <div v-if="localSettings.logo" class="media-preview">
                    <img
                        :src="getMediaUrl(localSettings.logo)"
                        alt="Logo"
                        class="media-thumbnail"
                    />
                    <button @click="removeMedia('logo')" class="media-remove-btn">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div v-else class="media-upload">
                    <label for="logo-upload" class="media-upload-label">
                        <i class="mdi mdi-cloud-upload-alt"></i>
                        <span>Logo hochladen</span>
                    </label>
                    <input
                        type="file"
                        id="logo-upload"
                        @change="handleFileUpload($event, 'logo')"
                        accept="image/*"
                        class="media-upload-input"
                    />
                </div>
                <small class="form-text">Empfohlene Größe: 200x80 Pixel</small>
            </div>
        </div>

        <div class="form-group">
            <label>Banner</label>
            <div class="media-upload-container">
                <div v-if="localSettings.banner" class="media-preview">
                    <img
                        :src="getMediaUrl(localSettings.banner)"
                        alt="Banner"
                        class="media-thumbnail banner-preview"
                    />
                    <button @click="removeMedia('banner')" class="media-remove-btn">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div v-else class="media-upload">
                    <label for="banner-upload" class="media-upload-label">
                        <i class="mdi mdi-cloud-upload-alt"></i>
                        <span>Banner hochladen</span>
                    </label>
                    <input
                        type="file"
                        id="banner-upload"
                        @change="handleFileUpload($event, 'banner')"
                        accept="image/*"
                        class="media-upload-input"
                    />
                </div>
                <small class="form-text">Empfohlene Größe: 1200x400 Pixel</small>
            </div>
        </div>

        <div class="form-group">
            <label>Hintergrundbild</label>
            <div class="media-upload-container">
                <div v-if="localSettings.background_image" class="media-preview">
                    <img
                        :src="getMediaUrl(localSettings.background_image)"
                        alt="Hintergrundbild"
                        class="media-thumbnail bg-preview"
                    />
                    <button @click="removeMedia('background_image')" class="media-remove-btn">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div v-else class="media-upload">
                    <label for="bg-upload" class="media-upload-label">
                        <i class="mdi mdi-cloud-upload-alt"></i>
                        <span>Hintergrundbild hochladen</span>
                    </label>
                    <input
                        type="file"
                        id="bg-upload"
                        @change="handleFileUpload($event, 'background_image')"
                        accept="image/*"
                        class="media-upload-input"
                    />
                </div>
                <small class="form-text">Empfohlene Größe: Mindestens 1920x1080 Pixel</small>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface MediaSettings {
    logo: string | null;
    banner: string | null;
    background_image: string | null;
}

interface Props {
    modelValue: MediaSettings;
    getMediaUrl: (fileName: string | null) => string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaSettings): void;
    (e: 'change'): void;
    (e: 'uploadMedia', event: Event, mediaType: string): void;
    (e: 'removeMedia', mediaType: string): void;
}>();

const localSettings = ref<MediaSettings>({ ...props.modelValue });

watch(
    () => props.modelValue,
    (newValue) => {
        localSettings.value = { ...newValue };
    },
    { deep: true }
);

function emitChange() {
    emit('update:modelValue', { ...localSettings.value });
    emit('change');
}

function handleFileUpload(event: Event, mediaType: string) {
    emit('uploadMedia', event, mediaType);
}

function removeMedia(mediaType: keyof MediaSettings) {
    emit('removeMedia', mediaType);
    localSettings.value[mediaType] = null;
    emitChange();
}
</script>

<style scoped>
.settings-section {
    margin-bottom: 2rem;
}

.settings-section h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #e5e7eb;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #e5e7eb;
}

.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

.media-upload-container {
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background-color: var(--k-sunken);
}

.media-preview {
    position: relative;
    margin-bottom: 1rem;
}

.media-thumbnail {
    max-width: 100%;
    max-height: 200px;
    border-radius: 4px;
    object-fit: contain;
}

.banner-preview {
    max-height: 150px;
}

.bg-preview {
    max-height: 200px;
}

.media-remove-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 32px;
    height: 32px;
    padding: 0;
    border: none;
    background-color: #ef4444;
    color: var(--k-ink);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.media-remove-btn:hover {
    background-color: #dc2626;
}

.media-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1rem;
}

.media-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: var(--k-accent);
    font-weight: 500;
    transition: color 0.2s;
}

.media-upload-label:hover {
    color: var(--k-accent-hover);
}

.media-upload-label i {
    font-size: 2rem;
}

.media-upload-input {
    display: none;
}
</style>
