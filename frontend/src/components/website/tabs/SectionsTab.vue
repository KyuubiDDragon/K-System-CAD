<template>
    <div class="sections-tab">
        <h2>Sections verwalten</h2>
        <p>Verwalten Sie die Sections für Ihre One-Pager Website. Ziehen Sie Sections, um die Reihenfolge zu ändern.</p>

        <!-- Template Info -->
        <div v-if="currentTemplate !== 'onepager'" class="template-notice">
            <i class="mdi mdi-information"></i>
            <p>Sections sind nur für One-Pager Websites verfügbar. Wechseln Sie in den Einstellungen zum One-Pager Template.</p>
        </div>

        <!-- Toolbar -->
        <div v-else class="sections-toolbar">
            <button class="btn btn-primary" @click="createSection">
                <i class="mdi mdi-plus"></i> Neue Section
            </button>

            <!-- Filter -->
            <div class="sections-filters">
                <select v-model="filterType" class="filter-select">
                    <option value="">Alle Typen</option>
                    <option value="hero">Hero</option>
                    <option value="about">About</option>
                    <option value="services">Services</option>
                    <option value="portfolio">Portfolio</option>
                    <option value="team">Team</option>
                    <option value="testimonials">Testimonials</option>
                    <option value="contact">Contact</option>
                    <option value="features">Features</option>
                    <option value="pricing">Pricing</option>
                    <option value="cta">Call-to-Action</option>
                    <option value="custom">Custom</option>
                </select>

                <label class="checkbox-label">
                    <input type="checkbox" v-model="showInactiveOnly" />
                    <span>Nur inaktive</span>
                </label>
            </div>
        </div>

        <!-- Sections List (Draggable) -->
        <div v-if="currentTemplate === 'onepager' && filteredSections.length > 0" class="sections-list">
            <div
                v-for="section in filteredSections"
                :key="section.id"
                class="section-card"
                :class="{ inactive: !section.is_active }"
                draggable="true"
                @dragstart="handleDragStart($event, section)"
                @dragover.prevent
                @drop="handleDrop($event, section)"
            >
                <div class="section-header">
                    <div class="section-info">
                        <i class="mdi mdi-drag-vertical drag-handle"></i>
                        <span class="section-type" :class="`type-${section.section_type}`">
                            {{ getSectionTypeLabel(section.section_type) }}
                        </span>
                        <span v-if="!section.is_active" class="inactive-badge">
                            <i class="mdi mdi-eye-off"></i> Inaktiv
                        </span>
                    </div>
                    <div class="section-actions">
                        <button
                            class="btn-icon"
                            @click="toggleSectionActive(section)"
                            :title="section.is_active ? 'Deaktivieren' : 'Aktivieren'"
                        >
                            <i :class="section.is_active ? 'mdi mdi-eye' : 'mdi mdi-eye-off'"></i>
                        </button>
                        <button class="btn-icon" @click="editSection(section)" title="Bearbeiten">
                            <i class="mdi mdi-pencil"></i>
                        </button>
                        <button class="btn-icon danger" @click="deleteSection(section)" title="Löschen">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </div>

                <h3 class="section-title">{{ section.title || 'Ohne Titel' }}</h3>

                <p v-if="section.content" class="section-preview">
                    {{ truncateContent(section.content) }}
                </p>

                <div class="section-footer">
                    <span class="section-order">
                        <i class="mdi mdi-sort-numeric-variant"></i> Reihenfolge: {{ section.sort_order + 1 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="currentTemplate === 'onepager'" class="empty-state">
            <i class="mdi mdi-view-sequential empty-icon"></i>
            <h3>Keine Sections vorhanden</h3>
            <p v-if="filterType || showInactiveOnly">
                Keine Sections gefunden, die den ausgewählten Filtern entsprechen.
            </p>
            <p v-else>Erstellen Sie Ihre erste Section für die One-Pager Website.</p>
            <button v-if="!filterType && !showInactiveOnly" class="btn btn-primary" @click="createSection">
                <i class="mdi mdi-plus"></i> Erste Section erstellen
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface Section {
    id: number;
    section_type: string;
    title?: string;
    content?: string;
    settings?: any;
    sort_order: number;
    is_active: boolean;
}

interface Props {
    sections: Section[];
    currentTemplate: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    create: [];
    edit: [section: Section];
    delete: [section: Section];
    toggleActive: [section: Section];
    updateOrder: [sections: Section[]];
}>();

// Filters
const filterType = ref('');
const showInactiveOnly = ref(false);

// Drag & Drop State
const draggedSection = ref<Section | null>(null);

// Computed
const filteredSections = computed(() => {
    let result = [...props.sections];

    if (filterType.value) {
        result = result.filter(s => s.section_type === filterType.value);
    }

    if (showInactiveOnly.value) {
        result = result.filter(s => !s.is_active);
    }

    // Sort by sort_order
    result.sort((a, b) => a.sort_order - b.sort_order);

    return result;
});

// Methods
function createSection() {
    emit('create');
}

function editSection(section: Section) {
    emit('edit', section);
}

function deleteSection(section: Section) {
    if (confirm(`Möchten Sie die Section "${section.title || section.section_type}" wirklich löschen?`)) {
        emit('delete', section);
    }
}

function toggleSectionActive(section: Section) {
    emit('toggleActive', section);
}

function getSectionTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        hero: 'Hero',
        about: 'About',
        services: 'Services',
        portfolio: 'Portfolio',
        team: 'Team',
        testimonials: 'Testimonials',
        contact: 'Contact',
        features: 'Features',
        pricing: 'Pricing',
        cta: 'Call-to-Action',
        custom: 'Custom'
    };
    return labels[type] || type;
}

function truncateContent(content: string): string {
    if (!content) return '';
    const maxLength = 150;
    return content.length > maxLength ? content.substring(0, maxLength) + '...' : content;
}

// Drag & Drop Handlers
function handleDragStart(event: DragEvent, section: Section) {
    draggedSection.value = section;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function handleDrop(event: DragEvent, targetSection: Section) {
    event.preventDefault();

    if (!draggedSection.value || draggedSection.value.id === targetSection.id) {
        return;
    }

    // Create a new array with updated order
    const sections = [...props.sections];
    const draggedIndex = sections.findIndex(s => s.id === draggedSection.value!.id);
    const targetIndex = sections.findIndex(s => s.id === targetSection.id);

    // Remove dragged section and insert at target position
    const [removed] = sections.splice(draggedIndex, 1);
    sections.splice(targetIndex, 0, removed);

    // Update sort_order for all sections
    sections.forEach((section, index) => {
        section.sort_order = index;
    });

    emit('updateOrder', sections);
    draggedSection.value = null;
}
</script>

<style scoped>
.sections-tab {
    padding: 1rem;
}

.sections-tab h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #e5e7eb;
}

.sections-tab > p {
    margin-bottom: 1.5rem;
    color: var(--k-ink-faint);
}

/* Template Notice */
.template-notice {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background-color: #374151;
    border: 1px solid #4b5563;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.template-notice i {
    font-size: 2rem;
    color: #60a5fa;
}

.template-notice p {
    margin: 0;
    color: #e5e7eb;
}

/* Toolbar */
.sections-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

.sections-filters {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filter-select {
    padding: 0.5rem 1rem;
    background-color: var(--k-sunken);
    color: #e5e7eb;
    border: 1px solid var(--k-line);
    border-radius: 4px;
    font-size: 0.875rem;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: var(--k-accent);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #e5e7eb;
    font-size: 0.875rem;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Buttons */
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    border: none;
    background-color: transparent;
    color: var(--k-ink-faint);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: #374151;
    color: #e5e7eb;
}

.btn-icon.danger:hover {
    background-color: #dc2626;
    color: var(--k-ink);
}

/* Sections List */
.sections-list {
    display: grid;
    gap: 1.5rem;
}

.section-card {
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 8px;
    padding: 1.5rem;
    cursor: grab;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.section-card:active {
    cursor: grabbing;
}

.section-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.section-card.inactive {
    opacity: 0.6;
    border-left: 4px solid #6b7280;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.drag-handle {
    font-size: 1.5rem;
    color: var(--k-ink-muted);
    cursor: grab;
}

.section-type {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.type-hero {
    background-color: #8b5cf6;
}

.type-about {
    background-color: #10b981;
}

.type-services {
    background-color: var(--k-accent);
}

.type-portfolio {
    background-color: #f59e0b;
}

.type-team {
    background-color: #06b6d4;
}

.type-testimonials {
    background-color: #ec4899;
}

.type-contact {
    background-color: #ef4444;
}

.type-features {
    background-color: #14b8a6;
}

.type-pricing {
    background-color: #a855f7;
}

.type-cta {
    background-color: #f97316;
}

.type-custom {
    background-color: var(--k-ink-muted);
}

.inactive-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: var(--k-ink-muted);
    color: var(--k-ink);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.section-actions {
    display: flex;
    gap: 0.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #e5e7eb;
    margin-bottom: 0.5rem;
}

.section-preview {
    color: var(--k-ink-faint);
    line-height: 1.5;
    margin-bottom: 1rem;
}

.section-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--k-line);
    font-size: 0.875rem;
}

.section-order {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--k-ink-faint);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--k-ink-faint);
}

.empty-icon {
    font-size: 4rem;
    color: var(--k-ink-muted);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    color: #e5e7eb;
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .sections-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .sections-filters {
        flex-direction: column;
    }

    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>
