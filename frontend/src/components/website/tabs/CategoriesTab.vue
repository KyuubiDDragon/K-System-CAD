<template>
    <div class="categories-tab">
        <h2>Kategorien verwalten</h2>
        <p>Hier können Sie Kategorien für Ihre Beiträge erstellen und verwalten.</p>
        <button @click="$emit('create')" class="btn btn-primary">
            <i class="mdi mdi-plus"></i> Neue Kategorie
        </button>

        <div v-if="showForm" class="category-form">
            <div class="form-header">
                <h3>
                    {{
                        form.id ? 'Kategorie bearbeiten' : 'Neue Kategorie'
                    }}
                </h3>
                <button @click="$emit('closeForm')" class="btn-icon">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="form-group">
                <label for="category-name">Name</label>
                <input
                    type="text"
                    id="category-name"
                    v-model="form.name"
                    class="form-control"
                    required
                />
            </div>
            <div class="form-group">
                <label for="category-slug">URL-Slug</label>
                <input
                    type="text"
                    id="category-slug"
                    v-model="form.slug"
                    class="form-control"
                    placeholder="beispiel-kategorie"
                    required
                />
                <small class="form-text"
                    >Der Slug wird in der URL verwendet (z.B.
                    /kategorie/beispiel-kategorie)</small
                >
            </div>
            <div class="form-group">
                <label for="category-description">Beschreibung</label>
                <textarea
                    id="category-description"
                    v-model="form.description"
                    class="form-control"
                    rows="3"
                ></textarea>
            </div>
            <div class="form-group">
                <label for="category-color">Farbe</label>
                <div class="color-picker-container">
                    <input
                        type="color"
                        id="category-color"
                        v-model="form.color"
                        class="form-control color-picker"
                    />
                    <span
                        class="color-preview"
                        :style="{ backgroundColor: form.color }"
                    ></span>
                </div>
                <small class="form-text"
                    >Wählen Sie eine Farbe für diese Kategorie</small
                >
            </div>
            <div class="form-actions">
                <button @click="$emit('closeForm')" class="btn btn-secondary">
                    Abbrechen
                </button>
                <button @click="$emit('save', form)" class="btn btn-primary">
                    Speichern
                </button>
            </div>
        </div>

        <div v-else-if="categories.length > 0" class="categories-list">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Beschreibung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <draggable
                    v-model="localCategories"
                    tag="tbody"
                    item-key="id"
                    handle=".drag-handle"
                    @end="handleSort"
                    :animation="200"
                    ghost-class="ghost-item"
                    chosen-class="chosen-item"
                >
                    <template #item="{ element: category }">
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="drag-handle mr-2"
                                        ><i class="mdi mdi-drag-vertical"></i
                                    ></span>
                                    {{ category.name }}
                                </div>
                            </td>
                            <td>{{ category.slug }}</td>
                            <td>{{ truncateText(category.description, 100) }}</td>
                            <td class="actions">
                                <button
                                    @click="$emit('edit', category)"
                                    class="btn-icon"
                                >
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button
                                    @click="$emit('delete', category)"
                                    class="btn-icon"
                                >
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
        </div>
        <div v-else-if="!showForm" class="empty-state">
            <p>Keine Kategorien vorhanden. Erstellen Sie Ihre erste Kategorie.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string;
    color: string;
}

interface CategoryForm {
    id: number | null;
    name: string;
    slug: string;
    description: string;
    color: string;
}

interface Props {
    categories: Category[];
    showForm?: boolean;
    form: CategoryForm;
}

const props = withDefaults(defineProps<Props>(), {
    showForm: false,
});

const emit = defineEmits<{
    create: [];
    edit: [category: Category];
    delete: [category: Category];
    save: [form: CategoryForm];
    closeForm: [];
    sort: [event: any];
}>();

const localCategories = ref([...props.categories]);

watch(() => props.categories, (newValue) => {
    localCategories.value = [...newValue];
}, { deep: true });

function handleSort(event: any) {
    emit('sort', event);
}

function truncateText(text: string, maxLength: number): string {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + '...';
}
</script>

<style scoped>
.categories-tab {
    padding: 1rem;
}

.categories-tab h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #e5e7eb;
}

.categories-tab p {
    margin-bottom: 1.5rem;
    color: var(--k-ink-faint);
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.btn-secondary {
    background-color: var(--k-neutral);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-neutral);
}

.btn-icon {
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
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: #374151;
    color: #e5e7eb;
}

.category-form {
    background-color: var(--k-sunken);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid var(--k-line);
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-header h3 {
    color: #e5e7eb;
    font-size: 1.2rem;
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
    background-color: #1e2327;
    color: #e5e7eb;
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

.color-picker-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.color-picker {
    width: 100px;
    height: 40px;
    padding: 0.25rem;
}

.color-preview {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    border: 2px solid #4a5568;
}

.form-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.categories-list {
    margin-top: 2rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--k-sunken);
    border-radius: 8px;
    overflow: hidden;
}

.data-table thead {
    background-color: #374151;
}

.data-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #e5e7eb;
    border-bottom: 2px solid #4a5568;
}

.data-table td {
    padding: 1rem;
    color: #e5e7eb;
    border-bottom: 1px solid var(--k-line);
}

.data-table tbody tr:hover {
    background-color: #374151;
}

.data-table .actions {
    display: flex;
    gap: 0.5rem;
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.mr-2 {
    margin-right: 0.5rem;
}

.drag-handle {
    cursor: move;
    color: var(--k-ink-faint);
}

.drag-handle:hover {
    color: #e5e7eb;
}

.ghost-item {
    opacity: 0.5;
}

.chosen-item {
    background-color: var(--k-neutral);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--k-ink-faint);
}
</style>
