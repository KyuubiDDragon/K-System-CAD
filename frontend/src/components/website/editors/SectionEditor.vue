<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container section-editor">
            <div class="modal-header">
                <h2>{{ isEdit ? 'Section bearbeiten' : 'Neue Section erstellen' }}</h2>
                <button class="btn-close" @click="$emit('close')">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <div class="modal-body">
                <form @submit.prevent="handleSubmit">
                    <!-- Section Type -->
                    <div class="form-group">
                        <label for="section-type">Section Typ *</label>
                        <select
                            id="section-type"
                            v-model="formData.section_type"
                            class="form-control"
                            required
                            :disabled="isEdit"
                        >
                            <optgroup label="Basis">
                                <option value="hero">Hero</option>
                                <option value="about">About</option>
                                <option value="contact">Contact</option>
                                <option value="custom">Custom</option>
                            </optgroup>
                            <optgroup label="Content">
                                <option value="services">Services</option>
                                <option value="features">Features</option>
                                <option value="team">Team</option>
                                <option value="testimonials">Testimonials</option>
                                <option value="portfolio">Portfolio</option>
                                <option value="gallery">Gallery</option>
                                <option value="video">Video</option>
                            </optgroup>
                            <optgroup label="Business">
                                <option value="pricing">Pricing</option>
                                <option value="statistics">Statistics/Counter</option>
                                <option value="faq">FAQ</option>
                                <option value="cta">Call-to-Action</option>
                            </optgroup>
                        </select>
                        <small v-if="isEdit" class="form-text">Section-Typ kann nach Erstellung nicht geändert werden</small>
                    </div>

                    <!-- Title -->
                    <div class="form-group">
                        <label for="section-title">Titel *</label>
                        <input
                            id="section-title"
                            v-model="formData.title"
                            type="text"
                            class="form-control"
                            placeholder="z.B. Willkommen bei unserem Service"
                            required
                        />
                    </div>

                    <!-- Content -->
                    <div class="form-group">
                        <label for="section-content">Inhalt</label>
                        <TiptapEditor
                            v-model="formData.content"
                            placeholder="Beschreibungstext für diese Section..."
                            :show-character-count="false"
                        />
                    </div>

                    <!-- Section-specific Settings -->

                    <!-- Hero Settings -->
                    <div v-if="formData.section_type === 'hero'" class="section-settings">
                        <h3>Hero Einstellungen</h3>

                        <div class="form-group">
                            <label for="hero-subtitle">Untertitel</label>
                            <input
                                id="hero-subtitle"
                                v-model="formData.settings.subtitle"
                                type="text"
                                class="form-control"
                                placeholder="Kurzer Untertitel"
                            />
                        </div>

                        <div class="form-group">
                            <label for="hero-button-text">Button Text</label>
                            <input
                                id="hero-button-text"
                                v-model="formData.settings.buttonText"
                                type="text"
                                class="form-control"
                                placeholder="Mehr erfahren"
                            />
                        </div>

                        <div class="form-group">
                            <label for="hero-button-link">Button Link</label>
                            <input
                                id="hero-button-link"
                                v-model="formData.settings.buttonLink"
                                type="text"
                                class="form-control"
                                placeholder="#section-id oder https://..."
                            />
                        </div>

                    </div>

                    <!-- Services Settings -->
                    <div v-if="formData.section_type === 'services'" class="section-settings">
                        <h3>Services Einstellungen</h3>

                        <div class="form-group">
                            <label>Service Items (JSON)</label>
                            <textarea
                                v-model="servicesJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"icon":"mdi-cog","title":"Service 1","description":"Beschreibung"}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"icon":"mdi-cog","title":"Service 1","description":"Beschreibung"}]
                            </small>
                        </div>
                    </div>

                    <!-- Team Settings -->
                    <div v-if="formData.section_type === 'team'" class="section-settings">
                        <h3>Team Einstellungen</h3>

                        <div class="form-group">
                            <label>Team Members (JSON)</label>
                            <textarea
                                v-model="teamJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"name":"Max Mustermann","role":"CEO","image":"https://..."}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"name":"Max Mustermann","role":"CEO","image":"https://...","description":"Bio"}]
                            </small>
                        </div>
                    </div>

                    <!-- Pricing Settings -->
                    <div v-if="formData.section_type === 'pricing'" class="section-settings">
                        <h3>Pricing Einstellungen</h3>

                        <div class="form-group">
                            <label>Pricing Plans (JSON)</label>
                            <textarea
                                v-model="pricingJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"name":"Basic","price":"9.99","features":["Feature 1","Feature 2"]}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"name":"Basic","price":"9.99","currency":"€","features":["Feature 1"]}]
                            </small>
                        </div>
                    </div>

                    <!-- CTA Settings -->
                    <div v-if="formData.section_type === 'cta'" class="section-settings">
                        <h3>Call-to-Action Einstellungen</h3>

                        <div class="form-group">
                            <label for="cta-button-text">Button Text</label>
                            <input
                                id="cta-button-text"
                                v-model="formData.settings.buttonText"
                                type="text"
                                class="form-control"
                                placeholder="Jetzt starten"
                            />
                        </div>

                        <div class="form-group">
                            <label for="cta-button-link">Button Link</label>
                            <input
                                id="cta-button-link"
                                v-model="formData.settings.buttonLink"
                                type="text"
                                class="form-control"
                                placeholder="#contact oder https://..."
                            />
                        </div>
                    </div>

                    <!-- Gallery Settings -->
                    <div v-if="formData.section_type === 'gallery'" class="section-settings">
                        <h3>Gallery Einstellungen</h3>

                        <div class="form-group">
                            <label for="gallery-columns">Spalten</label>
                            <select
                                id="gallery-columns"
                                v-model.number="formData.settings.columns"
                                class="form-control"
                            >
                                <option :value="2">2 Spalten</option>
                                <option :value="3">3 Spalten</option>
                                <option :value="4">4 Spalten</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Bilder (JSON)</label>
                            <textarea
                                v-model="galleryJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"url":"image.jpg","caption":"Beschreibung"}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"url":"image.jpg","caption":"Bild 1"}]
                            </small>
                        </div>
                    </div>

                    <!-- Video Settings -->
                    <div v-if="formData.section_type === 'video'" class="section-settings">
                        <h3>Video Einstellungen</h3>

                        <div class="form-group">
                            <label for="video-embed">Video Embed URL</label>
                            <input
                                id="video-embed"
                                v-model="formData.settings.embedUrl"
                                type="text"
                                class="form-control"
                                placeholder="https://www.youtube.com/embed/..."
                            />
                            <small class="form-text">
                                YouTube, Vimeo oder andere Embed-URLs
                            </small>
                        </div>
                    </div>

                    <!-- FAQ Settings -->
                    <div v-if="formData.section_type === 'faq'" class="section-settings">
                        <h3>FAQ Einstellungen</h3>

                        <div class="form-group">
                            <label>FAQ Items (JSON)</label>
                            <textarea
                                v-model="faqJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"question":"Frage?","answer":"Antwort"}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"question":"Wie funktioniert das?","answer":"So geht es..."}]
                            </small>
                        </div>
                    </div>

                    <!-- Statistics Settings -->
                    <div v-if="formData.section_type === 'statistics'" class="section-settings">
                        <h3>Statistik Einstellungen</h3>

                        <div class="form-group">
                            <label for="stats-columns">Spalten</label>
                            <select
                                id="stats-columns"
                                v-model.number="formData.settings.columns"
                                class="form-control"
                            >
                                <option :value="2">2 Spalten</option>
                                <option :value="3">3 Spalten</option>
                                <option :value="4">4 Spalten</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Statistiken (JSON)</label>
                            <textarea
                                v-model="statsJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"value":"500+","label":"Kunden","icon":"mdi-account-group"}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"value":"500+","label":"Kunden","icon":"mdi-account-group"}]
                            </small>
                        </div>
                    </div>

                    <!-- Features Settings -->
                    <div v-if="formData.section_type === 'features'" class="section-settings">
                        <h3>Features Einstellungen</h3>

                        <div class="form-group">
                            <label for="features-columns">Spalten</label>
                            <select
                                id="features-columns"
                                v-model.number="formData.settings.columns"
                                class="form-control"
                            >
                                <option :value="2">2 Spalten</option>
                                <option :value="3">3 Spalten</option>
                                <option :value="4">4 Spalten</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Features (JSON)</label>
                            <textarea
                                v-model="featuresJson"
                                class="form-control code-input"
                                rows="8"
                                placeholder='[{"icon":"mdi-check","title":"Feature","text":"Beschreibung"}]'
                            ></textarea>
                            <small class="form-text">
                                Beispiel: [{"icon":"mdi-check","title":"Feature 1","text":"Beschreibung"}]
                            </small>
                        </div>
                    </div>

                    <!-- General Settings for all types -->
                    <div class="section-settings">
                        <h3>Allgemeine Einstellungen</h3>

                        <div class="form-group">
                            <label for="title-alignment">Titel-Ausrichtung</label>
                            <select
                                id="title-alignment"
                                v-model="formData.settings.titleAlignment"
                                class="form-control"
                            >
                                <option value="left">Links</option>
                                <option value="center">Zentriert</option>
                                <option value="right">Rechts</option>
                            </select>
                        </div>

                        <h4 class="settings-subheading">Layout & Spacing</h4>

                        <div class="form-group">
                            <label for="section-min-height">Minimale Höhe (%)</label>
                            <input
                                id="section-min-height"
                                v-model.number="formData.settings.minHeight"
                                type="number"
                                min="0"
                                max="200"
                                step="10"
                                class="form-control"
                                placeholder="100 (= 100vh)"
                            />
                            <small class="form-text">
                                Prozent der Viewport-Höhe (0 = auto, 100 = volle Bildschirmhöhe)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="padding-top">Abstand Oben (rem)</label>
                            <input
                                id="padding-top"
                                v-model.number="formData.settings.paddingTop"
                                type="number"
                                min="0"
                                max="20"
                                step="0.5"
                                class="form-control"
                                placeholder="6"
                            />
                            <small class="form-text">
                                Innenabstand oben in rem (Standard: 6rem)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="padding-bottom">Abstand Unten (rem)</label>
                            <input
                                id="padding-bottom"
                                v-model.number="formData.settings.paddingBottom"
                                type="number"
                                min="0"
                                max="20"
                                step="0.5"
                                class="form-control"
                                placeholder="6"
                            />
                            <small class="form-text">
                                Innenabstand unten in rem (Standard: 6rem)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="content-width">Content-Breite (px)</label>
                            <input
                                id="content-width"
                                v-model.number="formData.settings.contentWidth"
                                type="number"
                                min="800"
                                max="1920"
                                step="100"
                                class="form-control"
                                placeholder="1200"
                            />
                            <small class="form-text">
                                Maximale Breite des Inhalts in Pixeln (Standard: 1200px)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="section-bg-color">Hintergrundfarbe</label>
                            <input
                                id="section-bg-color"
                                v-model="formData.settings.backgroundColor"
                                type="color"
                                class="form-control color-input"
                            />
                            <small class="form-text">
                                Leer lassen für Standard-Hintergrund
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="section-bg-image">Hintergrundbild URL</label>
                            <input
                                id="section-bg-image"
                                v-model="formData.settings.backgroundImage"
                                type="text"
                                class="form-control"
                                placeholder="https://example.com/image.jpg"
                            />
                            <small class="form-text">
                                Optionales Hintergrundbild für diese Section
                            </small>
                        </div>

                        <div class="form-group" v-if="formData.settings.backgroundImage">
                            <label for="bg-overlay-opacity">Overlay-Transparenz ({{ formData.settings.overlayOpacity || 0.3 }})</label>
                            <input
                                id="bg-overlay-opacity"
                                v-model.number="formData.settings.overlayOpacity"
                                type="range"
                                min="0"
                                max="1"
                                step="0.1"
                                class="form-control range-input"
                            />
                            <small class="form-text">
                                Dunkles Overlay über dem Hintergrundbild für bessere Lesbarkeit
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="divider-shape">Section-Trenner (Divider)</label>
                            <select
                                id="divider-shape"
                                v-model="formData.settings.dividerShape"
                                class="form-control"
                            >
                                <option value="none">Kein Trenner</option>
                                <optgroup label="Klassisch">
                                    <option value="wave">Welle (Wave)</option>
                                    <option value="tilt">Schräg (Tilt)</option>
                                    <option value="curve">Kurve (Curve)</option>
                                    <option value="triangle">Dreieck (Triangle)</option>
                                </optgroup>
                                <optgroup label="Geometrisch">
                                    <option value="zigzag">Zickzack</option>
                                    <option value="mountains">Berge (Mountains)</option>
                                    <option value="pyramids">Pyramiden</option>
                                    <option value="arrow">Pfeil</option>
                                </optgroup>
                                <optgroup label="Organisch">
                                    <option value="waves-opacity">Wellen mit Transparenz</option>
                                    <option value="clouds">Wolken (Clouds)</option>
                                    <option value="slime">Tropfen/Slime</option>
                                </optgroup>
                                <optgroup label="Special Effects">
                                    <option value="split">Zerrissen (Split)</option>
                                    <option value="book">Buchseiten (Book)</option>
                                    <option value="lightning">Blitz (Lightning)</option>
                                </optgroup>
                            </select>
                            <small class="form-text">
                                Wählen Sie einen visuellen Trenner zwischen den Sections
                            </small>
                        </div>

                        <div class="form-group" v-if="formData.settings.dividerShape && formData.settings.dividerShape !== 'none'">
                            <label for="divider-position">Divider Position</label>
                            <select
                                id="divider-position"
                                v-model="formData.settings.dividerPosition"
                                class="form-control"
                            >
                                <option value="bottom">Unten</option>
                                <option value="top">Oben</option>
                                <option value="both">Oben & Unten</option>
                            </select>
                        </div>

                        <div class="form-group" v-if="formData.settings.dividerShape && formData.settings.dividerShape !== 'none'">
                            <label for="divider-flip">Divider spiegeln</label>
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    id="divider-flip"
                                    v-model="formData.settings.dividerFlip"
                                />
                                <span>Divider horizontal spiegeln</span>
                            </label>
                        </div>

                        <div class="form-group" v-if="formData.settings.dividerShape && formData.settings.dividerShape !== 'none'">
                            <label for="divider-height">Divider Höhe ({{ formData.settings.dividerHeight || 80 }}px)</label>
                            <input
                                id="divider-height"
                                v-model.number="formData.settings.dividerHeight"
                                type="range"
                                min="30"
                                max="200"
                                step="10"
                                class="form-control range-input"
                            />
                            <small class="form-text">
                                Höhe des Dividers in Pixeln (30-200px)
                            </small>
                        </div>

                        <div class="form-group" v-if="formData.settings.dividerShape && formData.settings.dividerShape !== 'none'">
                            <label for="divider-color-mode">Divider Farbe</label>
                            <select
                                id="divider-color-mode"
                                v-model="formData.settings.dividerColorMode"
                                class="form-control"
                            >
                                <option value="primary">Primärfarbe (Standard)</option>
                                <option value="auto">Auto (Hintergrund nächste Section)</option>
                                <option value="custom">Benutzerdefiniert</option>
                            </select>
                            <small class="form-text">
                                Auto nutzt die Hintergrundfarbe der nächsten Section für nahtlose Übergänge
                            </small>
                        </div>

                        <div class="form-group" v-if="formData.settings.dividerShape && formData.settings.dividerShape !== 'none' && formData.settings.dividerColorMode === 'custom'">
                            <label for="divider-color">Benutzerdefinierte Farbe</label>
                            <input
                                id="divider-color"
                                v-model="formData.settings.dividerColor"
                                type="color"
                                class="form-control color-input"
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="formData.is_active" />
                            <span>Section aktivieren</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$emit('close')">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-primary" @click="handleSubmit" :disabled="!isValid">
                    {{ isEdit ? 'Aktualisieren' : 'Erstellen' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import TiptapEditor from '@/components/TiptapEditor.vue';

interface SectionSettings {
    subtitle?: string;
    buttonText?: string;
    buttonLink?: string;
    backgroundImage?: string;
    backgroundColor?: string;
    overlayOpacity?: number;
    items?: any[];
    members?: any[];
    plans?: any[];
    images?: any[];
    statistics?: any[];
    features?: any[];
    columns?: number;
    embedUrl?: string;
    titleAlignment?: 'left' | 'center' | 'right';
    minHeight?: number;
    paddingTop?: number;
    paddingBottom?: number;
    contentWidth?: number;
    dividerShape?: string;
    dividerPosition?: 'top' | 'bottom' | 'both';
    dividerFlip?: boolean;
    dividerHeight?: number;
    dividerColorMode?: 'primary' | 'auto' | 'custom';
    dividerColor?: string;
}

interface Section {
    id?: number;
    section_type: string;
    title: string;
    content?: string;
    settings?: SectionSettings;
    sort_order?: number;
    is_active: boolean;
}

interface Props {
    section?: Section | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    save: [data: Section];
}>();

// Form data
const formData = ref<Section>({
    section_type: 'hero',
    title: '',
    content: '',
    settings: {
        titleAlignment: 'center',
        minHeight: 100,
        paddingTop: 6,
        paddingBottom: 6,
        contentWidth: 1200,
        backgroundColor: '',
        backgroundImage: '',
        overlayOpacity: 0.3,
        dividerShape: 'none',
        dividerPosition: 'bottom',
        dividerFlip: false,
        dividerHeight: 80,
        dividerColorMode: 'primary',
        dividerColor: 'var(--k-accent)'
    },
    is_active: true,
});

// JSON helpers for complex settings
const servicesJson = ref('[]');
const teamJson = ref('[]');
const pricingJson = ref('[]');
const galleryJson = ref('[]');
const faqJson = ref('[]');
const statsJson = ref('[]');
const featuresJson = ref('[]');

// Computed
const isEdit = computed(() => !!props.section?.id);

const isValid = computed(() => {
    return formData.value.title.trim().length > 0;
});

// Watch for section changes (edit mode)
watch(() => props.section, (section) => {
    if (section) {
        formData.value = {
            id: section.id,
            section_type: section.section_type || 'hero',
            title: section.title || '',
            content: section.content || '',
            settings: {
                ...section.settings,
                titleAlignment: section.settings?.titleAlignment || 'center',
                minHeight: section.settings?.minHeight ?? 100,
                paddingTop: section.settings?.paddingTop ?? 6,
                paddingBottom: section.settings?.paddingBottom ?? 6,
                contentWidth: section.settings?.contentWidth || 1200,
                backgroundColor: section.settings?.backgroundColor || '',
                backgroundImage: section.settings?.backgroundImage || '',
                overlayOpacity: section.settings?.overlayOpacity ?? 0.3,
                dividerShape: section.settings?.dividerShape || 'none',
                dividerPosition: section.settings?.dividerPosition || 'bottom',
                dividerFlip: section.settings?.dividerFlip || false,
                dividerHeight: section.settings?.dividerHeight || 80,
                dividerColorMode: section.settings?.dividerColorMode || 'primary',
                dividerColor: section.settings?.dividerColor || 'var(--k-accent)'
            },
            sort_order: section.sort_order,
            is_active: section.is_active ?? true,
        };

        // Parse JSON settings
        if (section.settings?.items) {
            servicesJson.value = JSON.stringify(section.settings.items, null, 2);
            faqJson.value = JSON.stringify(section.settings.items, null, 2); // FAQ uses items too
        }
        if (section.settings?.members) {
            teamJson.value = JSON.stringify(section.settings.members, null, 2);
        }
        if (section.settings?.plans) {
            pricingJson.value = JSON.stringify(section.settings.plans, null, 2);
        }
        if (section.settings?.images) {
            galleryJson.value = JSON.stringify(section.settings.images, null, 2);
        }
        if (section.settings?.statistics) {
            statsJson.value = JSON.stringify(section.settings.statistics, null, 2);
        }
        if (section.settings?.features) {
            featuresJson.value = JSON.stringify(section.settings.features, null, 2);
        }
    } else {
        // Reset form for new section
        formData.value = {
            section_type: 'hero',
            title: '',
            content: '',
            settings: {
                titleAlignment: 'center',
                minHeight: 100,
                paddingTop: 6,
                paddingBottom: 6,
                contentWidth: 1200,
                backgroundColor: '',
                backgroundImage: '',
                overlayOpacity: 0.3,
                dividerShape: 'none',
                dividerPosition: 'bottom',
                dividerFlip: false,
                dividerHeight: 80,
                dividerColorMode: 'primary',
                dividerColor: 'var(--k-accent)'
            },
            is_active: true,
        };
        servicesJson.value = '[]';
        teamJson.value = '[]';
        pricingJson.value = '[]';
        galleryJson.value = '[]';
        faqJson.value = '[]';
        statsJson.value = '[]';
        featuresJson.value = '[]';
    }
}, { immediate: true });

// Methods
function handleSubmit() {
    if (!isValid.value) return;

    const data = { ...formData.value };

    // Parse JSON settings based on section type
    try {
        if (data.section_type === 'services') {
            data.settings!.items = JSON.parse(servicesJson.value);
        }
        if (data.section_type === 'team') {
            data.settings!.members = JSON.parse(teamJson.value);
        }
        if (data.section_type === 'pricing') {
            data.settings!.plans = JSON.parse(pricingJson.value);
        }
        if (data.section_type === 'gallery') {
            data.settings!.images = JSON.parse(galleryJson.value);
        }
        if (data.section_type === 'faq') {
            data.settings!.items = JSON.parse(faqJson.value);
        }
        if (data.section_type === 'statistics') {
            data.settings!.statistics = JSON.parse(statsJson.value);
        }
        if (data.section_type === 'features') {
            data.settings!.features = JSON.parse(featuresJson.value);
        }
    } catch (error) {
        alert('Ungültiges JSON-Format. Bitte überprüfen Sie die Eingabe.');
        return;
    }

    // Ensure settings is a plain object, not null/undefined
    if (!data.settings) {
        data.settings = {};
    }

    emit('save', data);
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 1rem;
}

.modal-container {
    background-color: var(--k-surface);
    border-radius: 12px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--k-line);
}

.modal-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--k-ink);
    margin: 0;
}

.btn-close {
    width: 36px;
    height: 36px;
    padding: 0;
    border: none;
    background-color: transparent;
    color: var(--k-ink-faint);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.2s;
}

.btn-close:hover {
    background-color: var(--k-sunken);
    color: var(--k-ink);
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--k-ink);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background-color: var(--k-sunken);
    color: var(--k-ink);
    border: 1px solid var(--k-line);
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px var(--k-accent-weak);
}

.form-control:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.code-input {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
}

.color-input {
    height: 50px;
    cursor: pointer;
}

.range-input {
    cursor: pointer;
    padding: 0.5rem;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--k-ink-faint);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: var(--k-ink);
    font-size: 0.875rem;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Section Settings */
.section-settings {
    padding: 1.5rem;
    background-color: var(--k-sunken);
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.section-settings h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--k-ink);
    margin-bottom: 1rem;
}

.settings-subheading {
    font-size: 1rem;
    font-weight: 600;
    color: var(--k-ink-faint);
    margin: 1.5rem 0 1rem 0;
    padding-top: 1rem;
    border-top: 1px solid var(--k-line);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid var(--k-line);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-secondary {
    background-color: var(--k-neutral);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-neutral);
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--k-accent-hover);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
}
</style>
