<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container news-editor">
            <div class="modal-header">
                <h2>{{ isEdit ? 'News bearbeiten' : 'Neue News erstellen' }}</h2>
                <button class="btn-close" @click="$emit('close')">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <div class="modal-body">
                <form @submit.prevent="handleSubmit">
                    <!-- Title -->
                    <div class="form-group">
                        <label for="news-title">Titel *</label>
                        <input
                            id="news-title"
                            v-model="formData.title"
                            type="text"
                            class="form-control"
                            placeholder="z.B. Neue Datenschutzerklärung verfügbar"
                            required
                        />
                    </div>

                    <!-- Category & Priority -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="news-category">Kategorie *</label>
                            <select id="news-category" v-model="formData.category" class="form-control" required>
                                <option value="announcement">Ankündigung</option>
                                <option value="update">Update</option>
                                <option value="download">Download</option>
                                <option value="news">News</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="news-priority">Priorität *</label>
                            <select id="news-priority" v-model="formData.priority" class="form-control" required>
                                <option value="low">Niedrig</option>
                                <option value="normal">Normal</option>
                                <option value="high">Hoch</option>
                                <option value="urgent">Dringend</option>
                            </select>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="form-group">
                        <label for="news-excerpt">Kurzbeschreibung</label>
                        <textarea
                            id="news-excerpt"
                            v-model="formData.excerpt"
                            class="form-control"
                            rows="2"
                            placeholder="Kurze Zusammenfassung (optional)"
                        ></textarea>
                        <small class="form-text">Wird in der News-Liste angezeigt</small>
                    </div>

                    <!-- Content -->
                    <div class="form-group">
                        <label for="news-content">Inhalt / Dokument</label>
                        <textarea
                            id="news-content"
                            v-model="formData.content"
                            class="form-control"
                            rows="10"
                            placeholder="Vollständiger Inhalt der News oder Dokumenttext..."
                        ></textarea>
                        <small class="form-text">Hier können Sie den vollständigen Text Ihres Dokuments oder Ihrer Ankündigung eingeben</small>
                    </div>

                    <!-- Publishing Settings -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="formData.is_published" />
                            <span>Sofort veröffentlichen</span>
                        </label>
                    </div>

                    <!-- Publish Date -->
                    <div v-if="formData.is_published" class="form-row">
                        <div class="form-group">
                            <label for="news-publish-date">Veröffentlichungsdatum</label>
                            <input
                                id="news-publish-date"
                                v-model="formData.published_at"
                                type="datetime-local"
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label for="news-expires-date">Ablaufdatum (optional)</label>
                            <input
                                id="news-expires-date"
                                v-model="formData.expires_at"
                                type="datetime-local"
                                class="form-control"
                            />
                            <small class="form-text">News wird nach diesem Datum nicht mehr angezeigt</small>
                        </div>
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

interface NewsItem {
    id?: number;
    title: string;
    excerpt?: string;
    content?: string;
    category: string;
    priority: string;
    is_published: boolean;
    published_at?: string;
    expires_at?: string;
}

interface Props {
    newsItem?: NewsItem | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    save: [data: NewsItem];
}>();

// Form data
const formData = ref<NewsItem>({
    title: '',
    excerpt: '',
    content: '',
    category: 'announcement',
    priority: 'normal',
    is_published: false,
    published_at: '',
    expires_at: '',
});

// Computed
const isEdit = computed(() => !!props.newsItem?.id);

const isValid = computed(() => {
    return formData.value.title.trim().length > 0;
});

// Watch for newsItem changes (edit mode)
watch(() => props.newsItem, (newsItem) => {
    if (newsItem) {
        formData.value = {
            id: newsItem.id,
            title: newsItem.title || '',
            excerpt: newsItem.excerpt || '',
            content: newsItem.content || '',
            category: newsItem.category || 'announcement',
            priority: newsItem.priority || 'normal',
            is_published: newsItem.is_published || false,
            published_at: newsItem.published_at ? formatDateTimeLocal(newsItem.published_at) : '',
            expires_at: newsItem.expires_at ? formatDateTimeLocal(newsItem.expires_at) : '',
        };
    } else {
        // Set default publish date to now
        const now = new Date();
        formData.value.published_at = formatDateTimeLocal(now.toISOString());
    }
}, { immediate: true });

// Methods
function handleSubmit() {
    if (!isValid.value) return;

    // Format dates for backend
    const data = { ...formData.value };

    // Convert datetime-local to ISO string
    if (data.published_at) {
        data.published_at = new Date(data.published_at).toISOString();
    }
    if (data.expires_at) {
        data.expires_at = new Date(data.expires_at).toISOString();
    }

    emit('save', data);
}

function formatDateTimeLocal(isoString: string): string {
    if (!isoString) return '';
    const date = new Date(isoString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
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
    background-color: #1e2327;
    border-radius: 12px;
    width: 100%;
    max-width: 800px;
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
    color: #e5e7eb;
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
    background-color: #374151;
    color: #e5e7eb;
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
    color: #e5e7eb;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background-color: var(--k-sunken);
    color: #e5e7eb;
    border: 1px solid var(--k-line);
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--k-ink-faint);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
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
    color: #e5e7eb;
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

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
