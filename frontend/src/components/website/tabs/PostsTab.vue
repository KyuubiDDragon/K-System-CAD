<template>
    <div class="posts-tab">
        <h2>Beiträge</h2>
        <p>Alles, was auf der Website mit Datum erscheint - Neuigkeiten, Berichte, Ankündigungen.</p>

        <!--
            Kategorien standen bis hierher als eigener Hauptpunkt daneben,
            obwohl sie ohne Beitraege nichts sind. Jetzt liegen sie dort, wo man
            sie braucht: eine Handbreit neben den Beitraegen.
        -->
        <div class="tabs">
            <div
                class="tab"
                :class="{ active: unterreiter === 'beitraege' }"
                @click="unterreiter = 'beitraege'"
            >
                <i class="mdi mdi-newspaper"></i> Beiträge
            </div>
            <div
                class="tab"
                :class="{ active: unterreiter === 'kategorien' }"
                @click="unterreiter = 'kategorien'"
            >
                <i class="mdi mdi-tag-multiple"></i> Kategorien
            </div>
        </div>

        <div v-if="unterreiter === 'kategorien'" class="kategorien-inhalt">
            <slot name="kategorien" />
        </div>

        <template v-else>
        <div class="posts-header">
            <button @click="handleCreate" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> Neuer Beitrag
            </button>

            <div v-if="categories.length > 0" class="category-filter">
                <label for="category-filter">Kategorie:</label>
                <select id="category-filter" v-model="selectedCategoryFilter" class="form-control">
                    <option value="">Alle</option>
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
                                <span class="zustand-marke" :class="'zustand-' + zustandVon(post)">
                                    <i :class="['mdi', zustandSymbol(post)]"></i>
                                    {{ zustandName(post) }}
                                </span>
                                <div v-if="zustandVon(post) === 'scheduled' && post.scheduled_at" class="zustand-termin">
                                    {{ formatDate(post.scheduled_at) }}
                                </div>
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
                    ? 'In dieser Kategorie steht noch nichts.'
                    : 'Noch keine Beiträge. Der erste kann auch nur zwei Sätze lang sein.'
                }}
            </p>
        </div>
        </template>
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
    /* status ist massgeblich; is_published bleibt fuer alte Daten lesbar. */
    status?: 'draft' | 'published' | 'scheduled' | 'archived';
    scheduled_at?: string | null;
    is_published?: boolean | number;
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
/*
   Der Zustand eines Beitrags. Beitraege aus der Zeit vor der Zustandsspalte
   tragen dort noch 'draft', obwohl das Haekchen gesetzt war - deshalb im
   Zweifel aus is_published ableiten.
*/
const ZUSTAND_NAMEN: Record<string, string> = {
    draft: 'Entwurf',
    published: 'Veröffentlicht',
    scheduled: 'Geplant',
    archived: 'Archiviert',
};

const ZUSTAND_SYMBOLE: Record<string, string> = {
    draft: 'mdi-pencil-outline',
    published: 'mdi-earth',
    scheduled: 'mdi-clock-outline',
    archived: 'mdi-archive-outline',
};

function zustandVon(post: any): string {
    const s = String(post?.status || '');
    if (ZUSTAND_NAMEN[s]) return s;
    return post?.is_published ? 'published' : 'draft';
}

function zustandName(post: any): string {
    return ZUSTAND_NAMEN[zustandVon(post)] ?? zustandVon(post);
}

function zustandSymbol(post: any): string {
    return ZUSTAND_SYMBOLE[zustandVon(post)] ?? 'mdi-help-circle-outline';
}

const unterreiter = ref<'beitraege' | 'kategorien'>('beitraege');
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
    color: var(--k-ink);
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
    color: var(--k-ink);
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
    background-color: var(--k-surface);
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
    color: var(--k-ink);
}

.data-table tbody tr {
    background-color: var(--k-sunken);
    transition: background-color 0.3s ease;
}

.data-table tbody tr:hover {
    background-color: var(--k-sunken);
}

.drag-handle {
    cursor: move;
    color: var(--k-ink-faint);
    display: inline-flex;
    align-items: center;
}

.drag-handle:hover {
    color: var(--k-ink);
}

.ghost-item {
    opacity: 0.5;
    background: var(--k-sunken);
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
    color: var(--k-ink);
}

.post-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

.has-image {
    color: var(--k-success);
}

.is-featured {
    color: var(--k-warning);
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
    color: var(--k-ink);
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
    background-color: var(--k-sunken);
    color: var(--k-accent);
}

.btn-icon:hover i.mdi-delete {
    color: var(--k-critical);
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
    color: var(--k-on-fill);
}

.btn-primary:hover {
    background-color: var(--k-accent-hover);
}

.form-control {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--k-ink);
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    background-color: var(--k-sunken);
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

.tabs {
    display: flex;
    gap: 2px;
    border-bottom: 1px solid var(--k-line);
    margin-bottom: 18px;
}

/*
   Unterreiter fuehren innerhalb eines Bereichs, sie eroeffnen keinen neuen.
   Deshalb eine Unterstreichung statt einer gefuellten Lasche: der gefuellte
   Reiter zog mehr Aufmerksamkeit auf sich als die Ueberschrift darueber - und
   stellte dunkle Schrift auf die Akzentflaeche, wo --k-on-fill hingehoert.
*/
.tab {
    padding: 8px 14px;
    cursor: pointer;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    font-size: 13px;
    color: var(--k-ink-muted);
    transition:
        color 120ms ease,
        border-color 120ms ease;
}

.tab:hover {
    color: var(--k-ink);
}

.tab.active {
    color: var(--k-ink);
    border-bottom-color: var(--k-accent);
}

.tab i {
    margin-right: 6px;
}

.kategorien-inhalt {
    padding-top: 2px;
}


/* --- Zustand in der Liste ---------------------------------------------- */

.zustand-marke {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid transparent;
}

.zustand-marke .mdi {
    font-size: 13px;
}

/* Farbe sagt hier etwas: live, in Arbeit, terminiert, aus dem Verkehr. */
.zustand-published {
    color: var(--k-success);
    border-color: color-mix(in srgb, var(--k-success) 40%, transparent);
    background: color-mix(in srgb, var(--k-success) 12%, transparent);
}

.zustand-draft {
    color: var(--k-ink-muted);
    border-color: var(--k-line);
}

.zustand-scheduled {
    color: var(--k-accent);
    border-color: color-mix(in srgb, var(--k-accent) 40%, transparent);
    background: color-mix(in srgb, var(--k-accent) 12%, transparent);
}

.zustand-archived {
    color: var(--k-ink-faint);
    border-color: var(--k-line);
    background: var(--k-sunken);
}

.zustand-termin {
    margin-top: 3px;
    font-size: 11px;
    color: var(--k-ink-faint);
}
</style>
