<template>
    <div class="settings-section">
        <h3>Startseiten-Hero</h3>
        <div class="form-group">
            <label for="hero-image">Hero-Bild (Hintergrundbild für Startseite)</label>
            <div class="media-selector">
                <div v-if="localSettings.hero_image" class="selected-media">
                    <img :src="getMediaUrl(localSettings.hero_image)" alt="Hero-Bild" />
                </div>
                <input
                    type="file"
                    id="hero-image-upload"
                    class="hidden-upload"
                    @change="handleFileUpload($event, 'hero_image')"
                    accept="image/*"
                />
                <div class="media-actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="triggerFileInput('hero-image-upload')"
                    >
                        <i class="mdi mdi-upload"></i> Hochladen
                    </button>
                    <button
                        v-if="localSettings.hero_image"
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="removeMedia('hero_image')"
                    >
                        <i class="mdi mdi-delete"></i> Entfernen
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="cta-text">Call-to-Action Button Text</label>
            <input
                type="text"
                id="cta-text"
                v-model="localSettings.cta_text"
                class="form-control"
                placeholder="z.B. 'Mehr erfahren' oder 'Kontakt'"
                @input="emitChange"
            />
        </div>

        <div class="form-group">
            <label for="cta-url">Call-to-Action URL</label>
            <div class="input-group">
                <select
                    v-model="localSettings.cta_target_type"
                    class="form-control"
                    style="max-width: 150px"
                    @change="emitChange"
                >
                    <option value="page">Zu Seite</option>
                    <option value="contact">Kontaktformular</option>
                </select>

                <select
                    v-if="localSettings.cta_target_type === 'page'"
                    v-model="localSettings.cta_target_id"
                    class="form-control"
                    @change="emitChange"
                >
                    <option v-for="page in pages" :key="page.id" :value="page.id">
                        {{ page.title }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface Page {
    id: number;
    title: string;
}

interface HeroSettings {
    hero_image: string | null;
    cta_text: string;
    cta_target_type: string;
    cta_target_id: number | null;
}

interface Props {
    modelValue: HeroSettings;
    pages: Page[];
    getMediaUrl: (fileName: string | null) => string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: HeroSettings): void;
    (e: 'change'): void;
    (e: 'uploadMedia', event: Event, mediaType: string): void;
    (e: 'removeMedia', mediaType: string): void;
}>();

const localSettings = ref<HeroSettings>({ ...props.modelValue });

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

function removeMedia(mediaType: string) {
    emit('removeMedia', mediaType);
    localSettings.value.hero_image = null;
    emitChange();
}

function triggerFileInput(inputId: string) {
    const input = document.getElementById(inputId) as HTMLInputElement;
    if (input) {
        input.click();
    }
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

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--k-line);
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px var(--k-accent-weak);
}

.media-selector {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 1rem;
    background-color: var(--k-sunken);
}

.selected-media {
    margin-bottom: 1rem;
}

.selected-media img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 4px;
    object-fit: cover;
}

.hidden-upload {
    display: none;
}

.media-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.btn-danger {
    background-color: #ef4444;
    color: var(--k-ink);
}

.btn-danger:hover {
    background-color: #dc2626;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.input-group .form-control {
    flex: 1;
}
</style>
