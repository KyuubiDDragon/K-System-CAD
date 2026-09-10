<template>
    <div class="settings-section">
        <h3>Weitere Einstellungen</h3>
        <div class="form-group">
            <label for="footer-text">Fußzeile Text</label>
            <input
                type="text"
                id="footer-text"
                v-model="localSettings.footer_text"
                class="form-control"
                @input="emitChange"
            />
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    v-model="localSettings.is_active"
                    @change="emitChange"
                />
                <span>Website aktiv</span>
            </label>
            <small class="form-text"
                >Wenn aktiviert, ist Ihre Website öffentlich zugänglich.</small
            >
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    v-model="localSettings.maintenance_mode"
                    @change="emitChange"
                />
                <span>Wartungsmodus</span>
            </label>
            <small class="form-text"
                >Wenn aktiviert, wird Besuchern eine Wartungsseite angezeigt.</small
            >
        </div>

        <div v-if="localSettings.maintenance_mode" class="form-group">
            <label for="maintenance-message">Wartungsmeldung</label>
            <textarea
                id="maintenance-message"
                v-model="localSettings.maintenance_message"
                class="form-control"
                rows="3"
                @input="emitChange"
            ></textarea>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    v-model="localSettings.show_contact_form"
                    @change="emitChange"
                />
                <span>Kontaktformular aktivieren</span>
            </label>
            <small class="form-text"
                >Wenn aktiviert, wird ein Kontaktformular und ein entsprechender
                Navigationspunkt automatisch zur Website hinzugefügt.</small
            >
        </div>

        <!-- Contact form configuration section -->
        <div v-if="localSettings.show_contact_form" class="contact-form-config mt-4">
            <h4 class="mb-3">
                <i class="mdi mdi-form-select"></i>
                Kontaktformular konfigurieren
            </h4>
            <p class="text-muted mb-3">
                Passen Sie die Felder Ihres Kontaktformulars an. Sie können die vorhandenen
                Felder bearbeiten oder neue Felder wie Textfelder, Dropdowns, Multi-Selects
                und Checkboxen hinzufügen.
            </p>

            <div class="contact-form-fields">
                <slot name="contact-form-editor"></slot>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface ContactSettings {
    footer_text: string;
    is_active: boolean;
    maintenance_mode: boolean;
    maintenance_message: string;
    show_contact_form: boolean;
}

interface Props {
    modelValue: ContactSettings;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: ContactSettings): void;
    (e: 'change'): void;
}>();

const localSettings = ref<ContactSettings>({ ...props.modelValue });

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
    color: var(--k-ink);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--k-ink);
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type='checkbox'] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label span {
    user-select: none;
}

.contact-form-config {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: var(--k-sunken);
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.contact-form-config h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--k-ink);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.text-muted {
    color: var(--k-ink-faint);
    font-size: 0.875rem;
}

.mb-3 {
    margin-bottom: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}
</style>
