<template>
    <div class="posts-tab">
        <h2>Beiträge verwalten</h2>
        <p>Hier können Sie Ihre Blog-Beiträge erstellen und bearbeiten.</p>

        <div class="posts-header">
            <button @click="handleCreate" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> Neuer Beitrag
            </button>

            <div v-if="categories.length > 0" class="category-filter">
                <label for="category-filter">Filter nach Kategorie:</label>
                <select id="category-filter" v-model="selectedCategoryFilter" class="form-control">
                    <option value="">Alle Kategorien</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="filteredPosts.length > 0" class="posts-list">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Kategorien</th>
                        <th>Datum</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <draggable
                    v-model="sortedPosts"
                    tag="tbody"
                    item-key="id"
                    handle=".drag-handle"
                    @end="handleSort"
                    :animation="200"
                    ghost-class="ghost-item"
                    chosen-class="chosen-item"
                >
                    <template #item="{ element: post }">
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="drag-handle mr-2">
                                        <i class="mdi mdi-drag-vertical"></i>
                                    </span>
                                    <div class="post-title-cell">
                                        <strong>{{ post.title }}</strong>
                                        <div class="post-meta">
                                            <span v-if="post.featured_image" class="has-image">
                                                <i class="mdi mdi-image"></i>
                                            </span>
                                            <span v-if="post.is_featured" class="is-featured">
                                                <i class="mdi mdi-star"></i> Hervorgehoben
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span v-if="post.categories && post.categories.length > 0">
                                    <span
                                        v-for="(category, index) in post.categories"
                                        :key="category.id"
                                        class="category-badge"
                                        :style="{ backgroundColor: category.color || '#007bff' }"
                                    >
                                        {{ category.name
                                        }}<span v-if="index < post.categories.length - 1">, </span>
                                    </span>
                                </span>
                                <span v-else class="no-category">Keine Kategorie</span>
                            </td>
                            <td>
                                {{ formatDate(post.published_at || post.created_at) }}
                            </td>
                            <td>
                                {{ post.is_published ? 'Veröffentlicht' : 'Entwurf' }}
                            </td>
                            <td class="actions">
                                <button @click="handleEdit(post)" class="btn-icon">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button @click="handleDelete(post)" class="btn-icon">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
        </div>
        <div v-else class="empty-state">
            <p>
                {{ selectedCategoryFilter
                    ? 'Keine Beiträge in dieser Kategorie gefunden.'
                    : 'Keine Beiträge vorhanden. Erstellen Sie Ihren ersten Beitrag.'
                }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';

// Interfaces
export interface Post {
    id: number | null;
    title: string;
    slug: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    is_published: boolean;
    is_featured: boolean;
    categories?: Category[];
    published_at?: string;
    created_at?: string;
    reading_time?: string;
    gallery_images?: string[];
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    color?: string;
}

// Props
const props = defineProps<{
    posts: Post[];
    categories: Category[];
}>();

// Emits
const emit = defineEmits<{
    create: [];
    edit: [post: Post];
    delete: [post: Post];
    sort: [event: any];
}>();

// Local state
const selectedCategoryFilter = ref<string | number>('');
const sortedPosts = ref<Post[]>([...props.posts]);

// Watch for external posts changes
watch(() => props.posts, (newPosts) => {
    sortedPosts.value = [...newPosts];
}, { deep: true });

// Computed
const filteredPosts = computed(() => {
    if (!selectedCategoryFilter.value) {
        return sortedPosts.value;
    }

    return sortedPosts.value.filter(post => {
        if (!post.categories || post.categories.length === 0) {
            return false;
        }
        return post.categories.some(cat => cat.id === selectedCategoryFilter.value);
    });
});

// Methods
function handleCreate() {
    emit('create');
}

function handleEdit(post: Post) {
    emit('edit', post);
}

function handleDelete(post: Post) {
    emit('delete', post);
}

function handleSort(event: any) {
    emit('sort', event);
}

function formatDate(dateString?: string): string {
    if (!dateString) return '';

    const date = new Date(dateString);
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    };

    return date.toLocaleDateString('de-DE', options);
}
</script>

<style scoped>
.posts-tab {
    padding: 1rem;
}

.posts-tab h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #e5e7eb;
}

.posts-tab > p {
    margin-bottom: 1.5rem;
    color: var(--k-ink-faint);
}

.posts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.category-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-filter label {
    margin: 0;
    white-space: nowrap;
    color: #e5e7eb;
}

.category-filter select {
    min-width: 200px;
}

.posts-list {
    margin-top: 1rem;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    background-color: #1e2327;
    border-radius: 8px;
    overflow: hidden;
}

.data-table thead {
    background-color: var(--k-sunken);
}

.data-table th,
.data-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--k-line);
}

.data-table th {
    font-weight: 600;
    color: #e5e7eb;
}

.data-table tbody tr {
    background-color: var(--k-sunken);
    transition: background-color 0.3s ease;
}

.data-table tbody tr:hover {
    background-color: #374151;
}

.drag-handle {
    cursor: move;
    color: var(--k-ink-faint);
    display: inline-flex;
    align-items: center;
}

.drag-handle:hover {
    color: #e5e7eb;
}

.ghost-item {
    opacity: 0.5;
    background: #374151;
}

.chosen-item {
    background: var(--k-accent);
}

.post-title-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.post-title-cell strong {
    color: #e5e7eb;
}

.post-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

.has-image {
    color: #10b981;
}

.is-featured {
    color: #fbbf24;
}

.category-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    color: var(--k-ink);
    margin-right: 0.25rem;
}

.no-category {
    color: var(--k-ink-faint);
    font-style: italic;
}

.data-table td {
    color: #e5e7eb;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    color: var(--k-accent);
    transition: all 0.3s ease;
    border-radius: 4px;
}

.btn-icon:hover {
    background-color: #374151;
    color: var(--k-accent);
}

.btn-icon:hover i.mdi-delete {
    color: #ef4444;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--k-ink-faint);
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.form-control {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #e5e7eb;
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    background-color: #374151;
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
</style>
