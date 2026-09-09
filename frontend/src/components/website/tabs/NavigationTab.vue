<template>
    <div class="navigation-tab">
        <div class="header">
            <button @click="$emit('create')" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> Neuer Navigationspunkt
            </button>
        </div>

        <div v-if="navigationItems.length > 0" class="navigation-list">
            <draggable
                v-model="localNavigationItems"
                @end="handleSort"
                handle=".drag-handle"
                item-key="id"
            >
                <template #item="{ element: item }">
                    <div class="navigation-item" :style="{ paddingLeft: getIndent(item) }">
                        <div class="item-content">
                            <i class="mdi mdi-drag drag-handle"></i>
                            <div class="item-info">
                                <div class="item-title">
                                    {{ item.title }}
                                    <span v-if="item.is_blog" class="badge badge-blog">Blog</span>
                                    <span v-if="!item.is_active" class="badge badge-inactive">Inaktiv</span>
                                </div>
                                <div class="item-details">
                                    <span v-if="item.page_id" class="detail">
                                        <i class="mdi mdi-file-document-outline"></i>
                                        Verknüpft mit Seite
                                    </span>
                                    <span v-else-if="item.url" class="detail">
                                        <i class="mdi mdi-link"></i>
                                        {{ item.url }}
                                    </span>
                                    <span v-if="item.target === '_blank'" class="detail">
                                        <i class="mdi mdi-open-in-new"></i>
                                        Neues Fenster
                                    </span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button
                                    @click="$emit('edit', item)"
                                    class="btn-icon"
                                    title="Bearbeiten"
                                >
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button
                                    @click="$emit('delete', item)"
                                    class="btn-icon btn-danger"
                                    title="Löschen"
                                >
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
        <div v-else class="empty-state">
            <i class="mdi mdi-menu"></i>
            <p>Keine Navigationspunkte vorhanden.</p>
            <p>Erstellen Sie Ihren ersten Navigationspunkt.</p>
        </div>

        <!-- Navigation Form Modal -->
        <div v-if="showForm" class="modal-backdrop fade show" @click="$emit('close-form')"></div>
        <div v-if="showForm" class="modal fade show" style="display: block;">
            <div class="modal-dialog" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ form.id ? 'Navigationspunkt bearbeiten' : 'Neuer Navigationspunkt' }}</h3>
                        <button @click="$emit('close-form')" class="btn-close">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="$emit('save', form)">
                            <div class="form-group">
                                <label>Titel *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="form-control"
                                    required
                                    placeholder="z.B. Über uns"
                                />
                            </div>

                            <div class="form-group">
                                <label>Link-Typ</label>
                                <select v-model="linkType" class="form-control">
                                    <option value="page">Seite verknüpfen</option>
                                    <option value="url">Eigene URL</option>
                                    <option value="blog">Blog-Übersicht</option>
                                </select>
                            </div>

                            <div v-if="linkType === 'page'" class="form-group">
                                <label>Seite auswählen *</label>
                                <select v-model="form.page_id" class="form-control" required>
                                    <option :value="null">-- Seite wählen --</option>
                                    <option v-for="page in pages" :key="page.id" :value="page.id">
                                        {{ page.title }}
                                    </option>
                                </select>
                            </div>

                            <div v-if="linkType === 'url'" class="form-group">
                                <label>URL *</label>
                                <input
                                    v-model="form.url"
                                    type="text"
                                    class="form-control"
                                    required
                                    placeholder="z.B. https://example.com oder /kontakt"
                                />
                            </div>

                            <div v-if="linkType === 'blog'" class="form-group">
                                <label>Blog-Kategorien</label>
                                <div class="checkbox-group">
                                    <label v-for="category in categories" :key="category.id" class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            :value="category.id"
                                            v-model="selectedCategories"
                                        />
                                        {{ category.name }}
                                    </label>
                                </div>
                                <small>Leer lassen für alle Kategorien</small>
                            </div>

                            <div class="form-group">
                                <label>Übergeordneter Punkt</label>
                                <select v-model="form.parent_id" class="form-control">
                                    <option :value="null">-- Kein (Hauptebene) --</option>
                                    <option
                                        v-for="nav in navigationItems.filter(n => n.id !== form.id)"
                                        :key="nav.id"
                                        :value="nav.id"
                                    >
                                        {{ nav.title }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Link-Ziel</label>
                                <select v-model="form.target" class="form-control">
                                    <option value="_self">Gleiches Fenster</option>
                                    <option value="_blank">Neues Fenster</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="form.is_active" />
                                    Navigationspunkt ist aktiv/sichtbar
                                </label>
                            </div>

                            <div class="form-actions">
                                <button type="button" @click="$emit('close-form')" class="btn btn-secondary">
                                    Abbrechen
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    {{ form.id ? 'Aktualisieren' : 'Erstellen' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';

interface NavigationItem {
    id: number;
    title: string;
    url?: string;
    page_id?: number;
    parent_id?: number;
    sort_order: number;
    target: string;
    is_active: boolean;
    is_blog: boolean;
    blog_categories?: string;
}

interface NavigationForm {
    id: number | null;
    title: string;
    url: string;
    page_id: number | null;
    parent_id: number | null;
    target: string;
    is_active: boolean;
    is_blog: boolean;
    blog_categories: string;
}

interface Props {
    navigationItems: NavigationItem[];
    showForm?: boolean;
    form?: NavigationForm;
    pages: any[];
    categories: any[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    create: [];
    edit: [item: NavigationItem];
    delete: [item: NavigationItem];
    save: [form: NavigationForm];
    'close-form': [];
    sort: [items: NavigationItem[]];
}>();

const localNavigationItems = ref([...props.navigationItems]);

// Watch for changes in navigationItems prop
watch(
    () => props.navigationItems,
    (newItems) => {
        localNavigationItems.value = [...newItems];
    }
);

// Link type management
const linkType = ref<'page' | 'url' | 'blog'>('page');
const selectedCategories = ref<number[]>([]);

watch(
    () => props.form,
    (newForm) => {
        if (newForm) {
            // Determine link type based on form data
            if (newForm.is_blog) {
                linkType.value = 'blog';
                if (newForm.blog_categories) {
                    try {
                        selectedCategories.value = JSON.parse(newForm.blog_categories);
                    } catch (e) {
                        selectedCategories.value = [];
                    }
                }
            } else if (newForm.page_id) {
                linkType.value = 'page';
            } else if (newForm.url) {
                linkType.value = 'url';
            } else {
                linkType.value = 'page';
            }
        }
    },
    { immediate: true }
);

// Update form based on link type
watch(linkType, (newType) => {
    if (props.form) {
        if (newType === 'page') {
            props.form.is_blog = false;
            props.form.url = '';
            props.form.blog_categories = '';
        } else if (newType === 'url') {
            props.form.is_blog = false;
            props.form.page_id = null;
            props.form.blog_categories = '';
        } else if (newType === 'blog') {
            props.form.is_blog = true;
            props.form.page_id = null;
            props.form.url = '/blog';
        }
    }
});

// Update form blog_categories when selectedCategories changes
watch(selectedCategories, (newCategories) => {
    if (props.form && linkType.value === 'blog') {
        props.form.blog_categories = JSON.stringify(newCategories);
    }
});

function handleSort() {
    emit('sort', localNavigationItems.value);
}

function getIndent(item: NavigationItem): string {
    return item.parent_id ? '30px' : '0';
}
</script>

<style scoped>
.navigation-tab {
    width: 100%;
}

.header {
    margin-bottom: 20px;
}

.navigation-list {
    margin-top: 20px;
}

.navigation-item {
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.navigation-item:hover {
    background-color: #374151;
    border-color: #5a6478;
}

.item-content {
    display: flex;
    align-items: center;
    padding: 15px;
    gap: 15px;
}

.drag-handle {
    cursor: grab;
    color: var(--k-ink-faint);
    font-size: 20px;
}

.drag-handle:active {
    cursor: grabbing;
}

.item-info {
    flex: 1;
}

.item-title {
    font-size: 16px;
    font-weight: 500;
    color: #e5e7eb;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.item-details {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: var(--k-ink-faint);
}

.detail {
    display: flex;
    align-items: center;
    gap: 4px;
}

.detail i {
    font-size: 14px;
}

.badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-blog {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.badge-inactive {
    background-color: var(--k-ink-muted);
    color: var(--k-ink);
}

.item-actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.btn-secondary {
    background-color: var(--k-ink-muted);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-ink-muted);
}

.btn-icon {
    background: transparent;
    border: none;
    color: var(--k-accent);
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background-color: #374151;
}

.btn-icon.btn-danger {
    color: #ef4444;
}

.btn-icon.btn-danger:hover {
    background-color: #7f1d1d;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--k-ink-faint);
}

.empty-state i {
    font-size: 64px;
    color: var(--k-ink-muted);
    margin-bottom: 20px;
}

.empty-state p {
    font-size: 16px;
    margin: 5px 0;
}

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    max-width: 600px;
    width: 90%;
    margin: 20px auto;
}

.modal-content {
    background-color: #1e2327;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    color: #e5e7eb;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--k-line);
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #e5e7eb;
}

.btn-close {
    background: transparent;
    border: none;
    color: var(--k-ink-faint);
    cursor: pointer;
    font-size: 24px;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-close:hover {
    background-color: #374151;
    color: #e5e7eb;
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #e5e7eb;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    color: #e5e7eb;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    background-color: #374151;
}

.form-control::placeholder {
    color: var(--k-ink-muted);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #e5e7eb;
    font-weight: normal;
}

.checkbox-label input[type='checkbox'] {
    cursor: pointer;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--k-ink-faint);
    font-size: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}
</style>
