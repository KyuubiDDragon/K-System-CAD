<template>
    <div class="settings-section">
        <h3>Design und Farbschema</h3>

        <div class="form-group">
            <label>Vordefinierte Farbschemata</label>
            <div class="color-schemes-grid">
                <div
                    v-for="(scheme, schemeIndex) in presetColorSchemes"
                    :key="schemeIndex"
                    class="color-scheme-item"
                    :class="{
                        selected:
                            (localSettings.selectedScheme === scheme.name ||
                                localSettings.selected_scheme === scheme.name) &&
                            !localSettings.useCustomColors,
                    }"
                    @click="selectColorScheme(scheme.name)"
                >
                    <div class="scheme-colors">
                        <div
                            class="scheme-color"
                            :style="{ backgroundColor: scheme.primary }"
                        ></div>
                        <div
                            class="scheme-color"
                            :style="{ backgroundColor: scheme.secondary }"
                        ></div>
                        <div
                            class="scheme-color"
                            :style="{ backgroundColor: scheme.accent }"
                        ></div>
                        <div
                            class="scheme-color"
                            :style="{ backgroundColor: scheme.background }"
                        ></div>
                        <div
                            class="scheme-color"
                            :style="{ backgroundColor: scheme.text }"
                        ></div>
                    </div>
                    <div class="scheme-name">{{ beschriftung(scheme.name) }}</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="toggle-container">
                <div class="toggle-label">Benutzerdefinierte Farben verwenden</div>
                <div class="toggle-switch">
                    <input
                        type="checkbox"
                        id="custom-colors-toggle"
                        v-model="localSettings.useCustomColors"
                        @change="emitChange"
                    />
                    <label for="custom-colors-toggle"></label>
                </div>
            </div>
        </div>

        <div v-if="localSettings.useCustomColors" class="custom-colors-section">
            <div class="form-group color-group">
                <div>
                    <label for="scheme-primary-color">Primärfarbe</label>
                    <input
                        type="color"
                        id="scheme-primary-color"
                        v-model="localSettings.customColors.primary"
                        class="form-control color-picker"
                        @input="emitChange"
                    />
                </div>

                <div>
                    <label for="scheme-secondary-color">Sekundärfarbe</label>
                    <input
                        type="color"
                        id="scheme-secondary-color"
                        v-model="localSettings.customColors.secondary"
                        class="form-control color-picker"
                        @input="emitChange"
                    />
                </div>

                <div>
                    <label for="scheme-accent-color">Akzentfarbe</label>
                    <input
                        type="color"
                        id="scheme-accent-color"
                        v-model="localSettings.customColors.accent"
                        class="form-control color-picker"
                        @input="emitChange"
                    />
                </div>

                <div>
                    <label for="scheme-background-color">Hintergrundfarbe</label>
                    <input
                        type="color"
                        id="scheme-background-color"
                        v-model="localSettings.customColors.background"
                        class="form-control color-picker"
                        @input="emitChange"
                    />
                </div>

                <div>
                    <label for="scheme-text-color">Textfarbe</label>
                    <input
                        type="color"
                        id="scheme-text-color"
                        v-model="localSettings.customColors.text"
                        class="form-control color-picker"
                        @input="emitChange"
                    />
                </div>
            </div>
        </div>

        <div class="colorscheme-preview">
            <h4>Vorschau des aktuellen Farbschemas:</h4>
            <div class="color-palette-preview">
                <div class="color-item">
                    <div
                        class="color-swatch"
                        :style="{
                            backgroundColor: localSettings.useCustomColors
                                ? localSettings.customColors.primary
                                : getSelectedSchemeColor('primary') || 'var(--k-accent)',
                        }"
                    ></div>
                    <div class="color-label">Primärfarbe</div>
                </div>
                <div class="color-item">
                    <div
                        class="color-swatch"
                        :style="{
                            backgroundColor: localSettings.useCustomColors
                                ? localSettings.customColors.secondary
                                : getSelectedSchemeColor('secondary') || 'var(--k-accent)',
                        }"
                    ></div>
                    <div class="color-label">Sekundärfarbe</div>
                </div>
                <div class="color-item">
                    <div
                        class="color-swatch"
                        :style="{
                            backgroundColor: localSettings.useCustomColors
                                ? localSettings.customColors.accent
                                : getSelectedSchemeColor('accent') || 'var(--k-accent-line)',
                        }"
                    ></div>
                    <div class="color-label">Akzentfarbe</div>
                </div>
                <div class="color-item">
                    <div
                        class="color-swatch"
                        :style="{
                            backgroundColor: localSettings.useCustomColors
                                ? localSettings.customColors.background
                                : getSelectedSchemeColor('background') || '#ffffff',
                        }"
                    ></div>
                    <div class="color-label">Hintergrundfarbe</div>
                </div>
                <div class="color-item">
                    <div
                        class="color-swatch color-swatch-text"
                        :style="{
                            backgroundColor: localSettings.useCustomColors
                                ? localSettings.customColors.text
                                : getSelectedSchemeColor('text') || '#111827',
                        }"
                    ></div>
                    <div class="color-label">Textfarbe</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="custom-css">Benutzerdefiniertes CSS</label>
            <div class="css-template-actions">
                <button
                    type="button"
                    @click="insertCssTemplate"
                    class="btn btn-sm btn-secondary"
                >
                    <i class="mdi mdi-code"></i> CSS-Template einfügen
                </button>
            </div>
            <textarea
                id="custom-css"
                v-model="localSettings.custom_css"
                class="form-control code-editor"
                @input="emitChange"
                placeholder="Eigene CSS-Regeln …"
                rows="6"
            ></textarea>
            <small class="form-text"
                >Nur nötig, wenn die Farbschemata nicht reichen.</small
            >
        </div>
    </div>
</template>

<script setup lang="ts">
/*
   Die englischen Namen sind die Schluessel: sie stehen so in der Datenbank
   (selected_scheme) und die Vorlagen schlagen die Farben damit nach. Umbenennen
   wuerde bestehende Websites farblos machen. Angezeigt wird deshalb eine
   Uebersetzung daneben; faellt eine heraus, steht der Schluessel da.
*/
const FARBNAMEN: Record<string, string> = {
    'Blue Ocean': 'Blau',
    'Forest Green': 'Grün',
    'Royal Purple': 'Violett',
    'Sunset Orange': 'Orange',
    'Rose Pink': 'Rosé',
    'Dark Blue': 'Blau, dunkel',
    'Dark Forest': 'Grün, dunkel',
    'Dark Purple': 'Violett, dunkel',
    'Dark Amber': 'Bernstein, dunkel',
    'Dark Slate': 'Schiefer, dunkel',
};

function beschriftung(name: string): string {
    return FARBNAMEN[name] ?? name;
}

import { ref, watch } from 'vue';

interface ColorScheme {
    name: string;
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
    bannerBackground?: string;
    bannerText?: string;
    heroBackground?: string;
    heroText?: string;
    buttonBackground?: string;
    buttonText?: string;
}

interface CustomColors {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
}

interface ColorSchemeSettings {
    selectedScheme: string;
    selected_scheme: string;
    useCustomColors: boolean;
    customColors: CustomColors;
    custom_css: string | null;
}

interface Props {
    modelValue: ColorSchemeSettings;
    presetColorSchemes: ColorScheme[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: ColorSchemeSettings): void;
    (e: 'change'): void;
    (e: 'selectScheme', schemeName: string): void;
}>();

const localSettings = ref<ColorSchemeSettings>({ ...props.modelValue });

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

function getSelectedSchemeColor(type: keyof ColorScheme): string | null {
    const schemeName = localSettings.value.selectedScheme || localSettings.value.selected_scheme;
    if (!schemeName) return null;
    const scheme = props.presetColorSchemes.find((s) => s.name === schemeName);
    return scheme ? (scheme[type] as string) : null;
}

function selectColorScheme(schemeName: string) {
    localSettings.value.selectedScheme = schemeName;
    localSettings.value.selected_scheme = schemeName;
    localSettings.value.useCustomColors = false;
    emit('selectScheme', schemeName);
    emitChange();
}

function insertCssTemplate() {
    const cssTemplate = `/* ===== WEBSITE ANPASSUNGEN ===== */
/* Dieses Template enthält alle anpassbaren CSS-Eigenschaften für Ihre Website */

/* ===== GRUNDLEGENDE SCHRIFT- UND FARBEINSTELLUNGEN ===== */
body {
    /* Hauptschriftart für den gesamten Inhalt */
    font-family: 'Arial', sans-serif; /* Ersetzen Sie durch Ihre gewünschte Schriftart */

    /* Textfarbe für den allgemeinen Inhalt */
    color: #333333;

    /* Zeilenhöhe für bessere Lesbarkeit */
    line-height: 1.6;
}

/* Schriftgrößen für verschiedene Überschriften */
h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
}

h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.6rem;
}

/* Links anpassen */
a {
    /* Linkfarbe */
    color: var(--k-accent);

    /* Übergangseffekt bei Hover */
    transition: color 0.3s ease;
    text-decoration: none;
}

a:hover {
    /* Linkfarbe bei Hover */
    color: #1d4ed8;
    text-decoration: underline;
}

/* ===== HEADER-BEREICH ===== */
.site-header {
    /* Zusätzliche Anpassungen für den Header-Bereich */
    padding: 2rem 1rem;
}

.site-title {
    /* Anpassungen für den Website-Titel */
    font-size: 2.8rem;
    letter-spacing: 0.5px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

.site-slogan {
    /* Anpassungen für den Slogan */
    font-size: 1.4rem;
    font-style: italic;
    opacity: 0.9;
}

/* ===== NAVIGATION ===== */
.site-nav ul {
    /* Anpassungen für die Navigationsliste */
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.site-nav li {
    margin: 0;
    position: relative;
}

.site-nav a {
    /* Anpassungen für die Navigationslinks */
    color: var(--k-ink);
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    padding: 0.6rem 1.2rem;
    border-radius: 4px;
    transition: all 0.3s ease;
    background-color: var(--k-row-hover);
}`;

    localSettings.value.custom_css = cssTemplate;
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
    background-color: var(--k-sunken);
    color: var(--k-ink);
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    background-color: var(--k-sunken);
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

.color-schemes-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

/* Responsive for smaller screens */
@media (max-width: 1200px) {
    .color-schemes-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .color-schemes-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .color-schemes-grid {
        grid-template-columns: 1fr;
    }
}

.color-scheme-item {
    border: 2px solid #4a5568;
    border-radius: 8px;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: var(--k-sunken);
}

.color-scheme-item:hover {
    border-color: var(--k-accent);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/*
   Die Auswahl war eine gefuellte Akzentflaeche mit heller Schrift darauf -
   hell auf hell. Jetzt traegt der Rahmen die Auswahl, die Flaeche bleibt
   ruhig; so bleibt auch sichtbar, welche Farben die Kachel selbst zeigt.
*/
.color-scheme-item.selected {
    border-color: var(--k-accent);
    background-color: color-mix(in srgb, var(--k-accent) 12%, var(--k-surface));
    box-shadow: 0 0 0 1px var(--k-accent);
}

.color-scheme-item.selected .scheme-name {
    color: var(--k-ink);
    font-weight: 620;
}

.scheme-colors {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.scheme-color {
    flex: 1;
    height: 30px;
    border-radius: 4px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.scheme-name {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    color: var(--k-ink);
}

.toggle-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: var(--k-sunken);
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.toggle-label {
    font-weight: 500;
    color: var(--k-ink);
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input[type='checkbox'] {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-switch label {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e0;
    transition: 0.4s;
    border-radius: 34px;
}

.toggle-switch label:before {
    position: absolute;
    content: '';
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: #fff;
    transition: 0.4s;
    border-radius: 50%;
}

.toggle-switch input:checked + label {
    background-color: var(--k-accent);
}

.toggle-switch input:checked + label:before {
    transform: translateX(24px);
}

.custom-colors-section {
    margin-top: 1rem;
}

.color-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.color-picker {
    height: 50px;
    cursor: pointer;
}

.colorscheme-preview {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: var(--k-sunken);
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.colorscheme-preview h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--k-ink);
}

.color-palette-preview {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.color-item {
    flex: 1;
    min-width: 100px;
    text-align: center;
}

.color-swatch {
    width: 100%;
    height: 60px;
    border-radius: 8px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    margin-bottom: 0.5rem;
}

.color-swatch-text {
    border: 2px solid #e5e7eb;
}

.color-label {
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

.css-template-actions {
    margin-bottom: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.btn-secondary {
    background-color: var(--k-neutral);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-neutral);
}

.code-editor {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    resize: vertical;
}
</style>
