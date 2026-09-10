<template>
    <div class="settings-section template-selector">
        <h3>Website-Template</h3>
        <p class="section-description">
            Wählen Sie ein Template für Ihre Website. Jedes Template hat ein eigenes Design und Layout.
        </p>

        <div class="template-grid">
            <!-- Default Template -->
            <div
                class="template-card"
                :class="{ active: modelValue.layout_template === 'default' }"
                @click="selectTemplate('default')"
            >
                <div class="template-preview">
                    <i class="mdi mdi-web"></i>
                </div>
                <div class="template-info">
                    <h4>Standard Website</h4>
                    <p>Klassische Multi-Page Website mit Navigation, Seiten und Blog</p>
                    <ul class="template-features">
                        <li><i class="mdi mdi-check"></i> Mehrere Seiten</li>
                        <li><i class="mdi mdi-check"></i> Blog-System</li>
                        <li><i class="mdi mdi-check"></i> Navigation</li>
                    </ul>
                </div>
                <div v-if="modelValue.layout_template === 'default'" class="active-badge">
                    <i class="mdi mdi-check-circle"></i> Aktiv
                </div>
            </div>

            <!-- One-Pager Template -->
            <div
                class="template-card"
                :class="{ active: modelValue.layout_template === 'onepager' }"
                @click="selectTemplate('onepager')"
            >
                <div class="template-preview onepager">
                    <i class="mdi mdi-view-sequential"></i>
                </div>
                <div class="template-info">
                    <h4>One-Pager</h4>
                    <p>Moderne Single-Page mit Smooth-Scroll Sections</p>
                    <ul class="template-features">
                        <li><i class="mdi mdi-check"></i> Hero Section</li>
                        <li><i class="mdi mdi-check"></i> Über uns</li>
                        <li><i class="mdi mdi-check"></i> Services</li>
                        <li><i class="mdi mdi-check"></i> Kontakt</li>
                    </ul>
                </div>
                <div v-if="modelValue.layout_template === 'onepager'" class="active-badge">
                    <i class="mdi mdi-check-circle"></i> Aktiv
                </div>
                <div class="new-badge">NEU</div>
            </div>

            <!-- Landing Page Template -->
            <div
                class="template-card"
                :class="{ active: modelValue.layout_template === 'landing', disabled: true }"
                @click="selectTemplate('landing')"
            >
                <div class="template-preview landing">
                    <i class="mdi mdi-rocket-launch"></i>
                </div>
                <div class="template-info">
                    <h4>Landing Page</h4>
                    <p>Conversion-optimiert mit starkem Call-to-Action</p>
                    <ul class="template-features">
                        <li><i class="mdi mdi-check"></i> Lead-Capture</li>
                        <li><i class="mdi mdi-check"></i> Features</li>
                        <li><i class="mdi mdi-check"></i> Testimonials</li>
                    </ul>
                </div>
                <div class="coming-soon-badge">Bald verfügbar</div>
            </div>

            <!-- Portfolio Template -->
            <div
                class="template-card"
                :class="{ active: modelValue.layout_template === 'portfolio', disabled: true }"
                @click="selectTemplate('portfolio')"
            >
                <div class="template-preview portfolio">
                    <i class="mdi mdi-image-multiple"></i>
                </div>
                <div class="template-info">
                    <h4>Portfolio</h4>
                    <p>Für Kreative & Freelancer mit Projekt-Galerie</p>
                    <ul class="template-features">
                        <li><i class="mdi mdi-check"></i> Projekt-Grid</li>
                        <li><i class="mdi mdi-check"></i> Detailseiten</li>
                        <li><i class="mdi mdi-check"></i> About/Contact</li>
                    </ul>
                </div>
                <div class="coming-soon-badge">Bald verfügbar</div>
            </div>

            <!-- Business Template -->
            <div
                class="template-card"
                :class="{ active: modelValue.layout_template === 'business', disabled: true }"
                @click="selectTemplate('business')"
            >
                <div class="template-preview business">
                    <i class="mdi mdi-briefcase"></i>
                </div>
                <div class="template-info">
                    <h4>Business</h4>
                    <p>Professionell & Corporate für Unternehmen</p>
                    <ul class="template-features">
                        <li><i class="mdi mdi-check"></i> Services</li>
                        <li><i class="mdi mdi-check"></i> Team</li>
                        <li><i class="mdi mdi-check"></i> Case Studies</li>
                    </ul>
                </div>
                <div class="coming-soon-badge">Bald verfügbar</div>
            </div>
        </div>

        <!-- Template-specific settings -->
        <div v-if="modelValue.layout_template === 'onepager'" class="template-settings">
            <h4>One-Pager Einstellungen</h4>
            <div class="setting-item">
                <label>
                    <input type="checkbox" v-model="enableParallax" @change="updateSettings">
                    Parallax-Effekte aktivieren
                </label>
            </div>
            <div class="setting-item">
                <label>
                    <input type="checkbox" v-model="enableScrollSpy" @change="updateSettings">
                    Scroll-Spy Navigation aktivieren
                </label>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface TemplateSettings {
    layout_template: string;
    template_settings?: any;
}

interface Props {
    modelValue: TemplateSettings;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: TemplateSettings): void;
    (e: 'change'): void;
}>();

// One-Pager specific settings
const enableParallax = ref(false);
const enableScrollSpy = ref(true);

// Initialize settings from modelValue
watch(() => props.modelValue.template_settings, (settings) => {
    if (settings && typeof settings === 'object') {
        enableParallax.value = settings.enableParallax ?? false;
        enableScrollSpy.value = settings.enableScrollSpy ?? true;
    }
}, { immediate: true });

function selectTemplate(template: string) {
    // Don't allow selection of disabled templates
    if (template !== 'default' && template !== 'onepager') {
        return;
    }

    emit('update:modelValue', {
        ...props.modelValue,
        layout_template: template,
        template_settings: template === 'onepager' ? {
            enableParallax: enableParallax.value,
            enableScrollSpy: enableScrollSpy.value
        } : null
    });
    emit('change');
}

function updateSettings() {
    if (props.modelValue.layout_template === 'onepager') {
        emit('update:modelValue', {
            ...props.modelValue,
            template_settings: {
                enableParallax: enableParallax.value,
                enableScrollSpy: enableScrollSpy.value
            }
        });
        emit('change');
    }
}
</script>

<style scoped>
.settings-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--k-sunken);
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.settings-section h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #e5e7eb;
}

.section-description {
    color: var(--k-ink-faint);
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.template-card {
    background-color: #1e2327;
    border: 2px solid #4a5568;
    border-radius: 8px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.template-card:hover:not(.disabled) {
    border-color: var(--k-accent);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.template-card.active {
    border-color: #10b981;
    background-color: #1f2d28;
}

.template-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.template-preview {
    width: 100%;
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.template-preview i {
    font-size: 3rem;
    color: var(--k-ink);
}

.template-preview.onepager {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.template-preview.landing {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.template-preview.portfolio {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.template-preview.business {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.template-info h4 {
    color: #e5e7eb;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.template-info p {
    color: var(--k-ink-faint);
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.template-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.template-features li {
    color: #d1d5db;
    font-size: 0.813rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.template-features i {
    color: #10b981;
    font-size: 0.875rem;
}

.active-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background-color: #10b981;
    color: var(--k-ink);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.new-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background-color: var(--k-accent);
    color: var(--k-ink);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.coming-soon-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background-color: var(--k-neutral);
    color: var(--k-ink);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.template-settings {
    background-color: #1e2327;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.template-settings h4 {
    color: #e5e7eb;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.setting-item {
    margin-bottom: 1rem;
}

.setting-item label {
    color: #e5e7eb;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.setting-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .template-grid {
        grid-template-columns: 1fr;
    }
}
</style>
