<template>
    <div class="news-tab">
        <h2>News & Ankündigungen</h2>
        <p>Verwalten Sie News, Ankündigungen und Dokumente für Ihre Website.</p>

        <!-- Toolbar -->
        <div class="news-toolbar">
            <button class="btn btn-primary" @click="createNews">
                <i class="mdi mdi-plus"></i> Neue News
            </button>

            <!-- Filter -->
            <div class="news-filters">
                <select v-model="filterCategory" class="filter-select">
                    <option value="">Alle Kategorien</option>
                    <option value="announcement">Ankündigung</option>
                    <option value="update">Update</option>
                    <option value="download">Download</option>
                    <option value="news">News</option>
                </select>

                <select v-model="filterStatus" class="filter-select">
                    <option value="">Alle Status</option>
                    <option value="published">Veröffentlicht</option>
                    <option value="draft">Entwurf</option>
                </select>
            </div>
        </div>

        <!-- News List -->
        <div v-if="filteredNews.length > 0" class="news-list">
            <div
                v-for="newsItem in filteredNews"
                :key="newsItem.id"
                class="news-card"
                :class="{ draft: !newsItem.is_published }"
            >
                <div class="news-header">
                    <div class="news-meta">
                        <span class="news-category" :class="`category-${newsItem.category}`">
                            {{ getCategoryLabel(newsItem.category) }}
                        </span>
                        <span class="news-priority" :class="`priority-${newsItem.priority}`">
                            <i :class="getPriorityIcon(newsItem.priority)"></i>
                            {{ getPriorityLabel(newsItem.priority) }}
                        </span>
                        <span v-if="!newsItem.is_published" class="draft-badge">
                            <i class="mdi mdi-file-document-edit-outline"></i> Entwurf
                        </span>
                    </div>
                    <div class="news-actions">
                        <button class="btn-icon" @click="editNews(newsItem)" title="Bearbeiten">
                            <i class="mdi mdi-pencil"></i>
                        </button>
                        <button class="btn-icon danger" @click="deleteNews(newsItem)" title="Löschen">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </div>

                <h3 class="news-title">{{ newsItem.title }}</h3>

                <p v-if="newsItem.excerpt" class="news-excerpt">{{ newsItem.excerpt }}</p>

                <div class="news-footer">
                    <div class="news-info">
                        <span v-if="newsItem.published_at">
                            <i class="mdi mdi-calendar"></i> {{ formatDate(newsItem.published_at) }}
                        </span>
                        <span v-if="newsItem.expires_at" class="expires">
                            <i class="mdi mdi-clock-alert"></i> Läuft ab: {{ formatDate(newsItem.expires_at) }}
                        </span>
                        <span v-if="newsItem.document_id">
                            <i class="mdi mdi-file-document"></i> {{ newsItem.file_name }}
                        </span>
                    </div>
                    <div class="news-stats">
                        <span v-if="newsItem.view_count" class="stat">
                            <i class="mdi mdi-eye"></i> {{ newsItem.view_count }}
                        </span>
                        <span v-if="newsItem.download_count" class="stat">
                            <i class="mdi mdi-download"></i> {{ newsItem.download_count }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <i class="mdi mdi-newspaper-variant-outline empty-icon"></i>
            <h3>Keine News vorhanden</h3>
            <p v-if="filterCategory || filterStatus">
                Keine News gefunden, die den ausgewählten Filtern entsprechen.
            </p>
            <p v-else>Erstellen Sie Ihre erste News oder Ankündigung.</p>
            <button v-if="!filterCategory && !filterStatus" class="btn btn-primary" @click="createNews">
                <i class="mdi mdi-plus"></i> Erste News erstellen
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface NewsItem {
    id: number;
    title: string;
    excerpt?: string;
    content?: string;
    category: string;
    priority: string;
    is_published: boolean;
    published_at?: string;
    expires_at?: string;
    document_id?: number;
    file_name?: string;
    file_path?: string;
    download_count: number;
    view_count: number;
    created_at: string;
}

interface Props {
    news: NewsItem[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    create: [];
    edit: [newsItem: NewsItem];
    delete: [newsItem: NewsItem];
}>();

// Filters
const filterCategory = ref('');
const filterStatus = ref('');

// Computed
const filteredNews = computed(() => {
    let result = [...props.news];

    if (filterCategory.value) {
        result = result.filter(n => n.category === filterCategory.value);
    }

    if (filterStatus.value === 'published') {
        result = result.filter(n => n.is_published);
    } else if (filterStatus.value === 'draft') {
        result = result.filter(n => !n.is_published);
    }

    // Sort by published_at DESC, then created_at DESC
    result.sort((a, b) => {
        const dateA = new Date(a.published_at || a.created_at).getTime();
        const dateB = new Date(b.published_at || b.created_at).getTime();
        return dateB - dateA;
    });

    return result;
});

// Methods
function createNews() {
    emit('create');
}

function editNews(newsItem: NewsItem) {
    emit('edit', newsItem);
}

function deleteNews(newsItem: NewsItem) {
    if (confirm(`Möchten Sie die News "${newsItem.title}" wirklich löschen?`)) {
        emit('delete', newsItem);
    }
}

function getCategoryLabel(category: string): string {
    const labels: Record<string, string> = {
        announcement: 'Ankündigung',
        update: 'Update',
        download: 'Download',
        news: 'News'
    };
    return labels[category] || category;
}

function getPriorityLabel(priority: string): string {
    const labels: Record<string, string> = {
        low: 'Niedrig',
        normal: 'Normal',
        high: 'Hoch',
        urgent: 'Dringend'
    };
    return labels[priority] || priority;
}

function getPriorityIcon(priority: string): string {
    const icons: Record<string, string> = {
        low: 'mdi mdi-arrow-down',
        normal: 'mdi mdi-minus',
        high: 'mdi mdi-arrow-up',
        urgent: 'mdi mdi-alert'
    };
    return icons[priority] || 'mdi mdi-minus';
}

function formatDate(dateString: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}
</script>

<style scoped>
.news-tab {
    padding: 1rem;
}

.news-tab h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--k-ink);
}

.news-tab > p {
    margin-bottom: 1.5rem;
    color: var(--k-ink-faint);
}

/* Toolbar */
.news-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

.news-filters {
    display: flex;
    gap: 1rem;
}

.filter-select {
    padding: 0.5rem 1rem;
    background-color: var(--k-sunken);
    color: var(--k-ink);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    font-size: 0.875rem;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: var(--k-accent);
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
    background-color: var(--k-sunken);
    color: var(--k-ink);
}

.btn-icon.danger:hover {
    background-color: var(--k-critical);
    color: var(--k-ink);
}

/* News List */
.news-list {
    display: grid;
    gap: 1.5rem;
}

.news-card {
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 8px;
    padding: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.news-card.draft {
    border-left: 4px solid var(--k-warning);
}

.news-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.news-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.news-category,
.news-priority,
.draft-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.news-category {
    background-color: var(--k-accent-hover);
    color: var(--k-ink);
}

.category-announcement {
    background-color: var(--k-accent);
}

.category-update {
    background-color: var(--k-success);
}

.category-download {
    background-color: #8b5cf6;
}

.category-news {
    background-color: #0891b2;
}

.news-priority {
    background-color: var(--k-sunken);
    color: var(--k-ink);
}

.priority-high {
    background-color: var(--k-warning);
    color: var(--k-ink);
}

.priority-urgent {
    background-color: var(--k-critical);
    color: var(--k-ink);
}

.draft-badge {
    background-color: var(--k-warning);
    color: var(--k-ink);
}

.news-actions {
    display: flex;
    gap: 0.5rem;
}

.news-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--k-ink);
    margin-bottom: 0.5rem;
}

.news-excerpt {
    color: var(--k-ink-faint);
    line-height: 1.5;
    margin-bottom: 1rem;
}

.news-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--k-line);
    font-size: 0.875rem;
}

.news-info {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    color: var(--k-ink-faint);
}

.news-info span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.news-info .expires {
    color: var(--k-warning);
}

.news-stats {
    display: flex;
    gap: 1rem;
    color: var(--k-ink-faint);
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
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
    color: var(--k-ink);
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .news-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .news-filters {
        flex-direction: column;
    }

    .news-header {
        flex-direction: column;
        gap: 1rem;
    }

    .news-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>
