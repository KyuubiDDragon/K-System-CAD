<template>
    <div class="settings-section">
        <h3>Grundlegende Informationen</h3>
        <div class="form-group">
            <label for="site-name">Website-Name</label>
            <input
                type="text"
                id="site-name"
                v-model="localSettings.site_name"
                class="form-control"
                @input="emitChange"
            />
        </div>
        <div class="form-group">
            <label for="site-slogan">Slogan</label>
            <input
                type="text"
                id="site-slogan"
                v-model="localSettings.site_slogan"
                class="form-control"
                @input="emitChange"
            />
        </div>
        <div class="form-group">
            <label for="navbar-name">Navigationstext für Startseite</label>
            <input
                type="text"
                id="navbar-name"
                v-model="localSettings.navbar_name"
                class="form-control"
                placeholder="z.B. Home oder Startseite"
                @input="emitChange"
            />
            <small class="form-text">
                Dies ist der Text, der in der Navigation für die Startseite
                angezeigt wird.
            </small>
        </div>
        <div class="form-group">
            <label for="site-description">Beschreibung</label>
            <TiptapEditor
                id="site-description"
                v-model="localSettings.site_description"
                @update:modelValue="emitChange"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import TiptapEditor from '@/components/TiptapEditor.vue';

interface BasicInfoSettings {
    site_name: string;
    site_slogan: string;
    navbar_name: string;
    site_description: string;
}

interface Props {
    modelValue: BasicInfoSettings;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: BasicInfoSettings): void;
    (e: 'change'): void;
}>();

const localSettings = ref<BasicInfoSettings>({ ...props.modelValue });

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

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}
</style>
