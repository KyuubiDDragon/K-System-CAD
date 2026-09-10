<template>
    <!-- Modal Backdrop -->
    <div
        class="modal-backdrop fade show"
        v-if="isVisible"
        @click="handleCancel"
    ></div>

    <!-- Modal -->
    <div
        class="modal fade show"
        v-if="isVisible"
        style="display: block;"
        @click.self="handleCancel"
    >
        <div class="modal-dialog modal-xl" @click.stop>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>{{ isEditing ? 'Beitrag bearbeiten' : 'Neuer Beitrag' }}</h3>
                    <button @click="handleCancel" class="btn-icon">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>

                <div class="modal-body">
            <div class="form-group">
                <label for="post-title">Titel</label>
                <input
                    type="text"
                    id="post-title"
                    v-model="form.title"
                    class="form-control"
                    required
                    @input="onTitleChange"
                />
            </div>

            <div class="form-group">
                <label for="post-slug">URL-Slug</label>
                <input
                    type="text"
                    id="post-slug"
                    v-model="form.slug"
                    class="form-control"
                    placeholder="beispiel-beitrag"
                    required
                />
                <small class="form-text">
                    Der Slug wird in der URL verwendet (z.B. /blog/beispiel-beitrag)
                </small>
            </div>

            <div class="form-group">
                <label for="post-excerpt">Auszug</label>
                <textarea
                    id="post-excerpt"
                    v-model="form.excerpt"
                    class="form-control"
                    rows="3"
                    placeholder="Kurze Zusammenfassung des Beitrags"
                ></textarea>
            </div>

            <div class="form-group">
                <label for="post-content">Inhalt</label>
                <TiptapEditor
                    v-model="form.content"
                    placeholder="Inhalt des Beitrags..."
                    @update:model-value="calculateReadingTime"
                />
                <div class="reading-time" v-if="form.reading_time">
                    <i class="mdi mdi-clock"></i> Lesezeit: {{ form.reading_time }}
                </div>
            </div>

            <div class="form-group">
                <label>Kategorien</label>
                <div class="categories-select">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="category-checkbox"
                    >
                        <input
                            type="checkbox"
                            :id="'category-' + category.id"
                            :value="category.id"
                            v-model="form.selectedCategories"
                        />
                        <label :for="'category-' + category.id">{{ category.name }}</label>
                    </div>
                </div>
                <div v-if="categories.length === 0" class="empty-categories">
                    <p>Keine Kategorien vorhanden. Erstellen Sie zuerst Kategorien.</p>
                </div>
            </div>

            <div class="form-group">
                <label>Beitragsbild</label>
                <div class="media-upload-container">
                    <div v-if="form.featured_image" class="media-preview">
                        <img
                            :src="getMediaUrl(form.featured_image)"
                            alt="Beitragsbild"
                            class="media-thumbnail featured-image-preview"
                        />
                        <button @click="removeFeaturedImage" class="media-remove-btn">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <div v-else class="media-upload">
                        <label for="featured-image-upload" class="media-upload-label">
                            <i class="mdi mdi-cloud-upload-alt"></i>
                            <span>Beitragsbild hochladen</span>
                        </label>
                        <input
                            type="file"
                            id="featured-image-upload"
                            @change="handleImageUpload"
                            accept="image/*"
                            class="media-upload-input"
                        />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="publish-date">Veröffentlichungsdatum</label>
                <input
                    type="datetime-local"
                    id="publish-date"
                    v-model="form.publish_date"
                    class="form-control"
                />
                <small class="form-text">
                    Optional: Legen Sie ein zukünftiges Datum für die geplante Veröffentlichung fest
                </small>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="form.is_published" />
                        <span>Veröffentlicht</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="form.is_featured" />
                        <span>Hervorgehoben</span>
                    </label>
                </div>
            </div>

                    <div class="form-actions">
                        <button @click="handleCancel" class="btn btn-secondary">Abbrechen</button>
                        <button @click="handleSave" class="btn btn-primary">Speichern</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import TiptapEditor from '@/components/TiptapEditor.vue';

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
    publish_date?: string;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    color?: string;
}

interface PostForm {
    id: number | null;
    title: string;
    slug: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    is_published: boolean;
    is_featured: boolean;
    selectedCategories: number[];
    reading_time: string;
    gallery_images: string[];
    publish_date: string;
}

// Props
const props = defineProps<{
    isVisible: boolean;
    post?: Post | null;
    categories: Category[];
}>();

// Helper function for media URLs
function getMediaUrl(fileName: string | null): string {
    if (!fileName) return '';
    const apiUrl = import.meta.env.VITE_API_URL || '';
    return `${apiUrl}/uploads/website/${fileName}`;
}

// Emits
const emit = defineEmits<{
    save: [post: PostForm];
    cancel: [];
    uploadImage: [event: Event];
}>();

// Local state
const form = reactive<PostForm>({
    id: null,
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    featured_image: null,
    is_published: true,
    is_featured: false,
    selectedCategories: [],
    reading_time: '',
    gallery_images: [],
    publish_date: '',
});

// Computed
const isEditing = computed(() => !!props.post);

// Watch for post prop changes
watch(
    () => props.post,
    (newPost) => {
        if (newPost) {
            form.id = newPost.id;
            form.title = newPost.title;
            form.slug = newPost.slug;
            form.excerpt = newPost.excerpt || '';
            form.content = newPost.content || '';
            form.featured_image = newPost.featured_image || null;
            form.is_published = !!newPost.is_published;
            form.is_featured = !!newPost.is_featured;
            form.selectedCategories = newPost.categories
                ? newPost.categories.map(cat => cat.id)
                : [];
            form.reading_time = newPost.reading_time || calculateReadingTimeFromContent(newPost.content || '');
            form.gallery_images = newPost.gallery_images || [];
            form.publish_date = newPost.publish_date || '';
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Methods
function resetForm() {
    form.id = null;
    form.title = '';
    form.slug = '';
    form.excerpt = '';
    form.content = '';
    form.featured_image = null;
    form.is_published = true;
    form.is_featured = false;
    form.selectedCategories = [];
    form.reading_time = '';
    form.gallery_images = [];
    form.publish_date = '';
}

function onTitleChange() {
    // Auto-generate slug from title if creating new post
    if (!form.id && !form.slug) {
        form.slug = generateSlug(form.title);
    }
}

function generateSlug(title: string): string {
    return title
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
}

function calculateReadingTime() {
    if (form.content) {
        const readingTime = calculateReadingTimeFromContent(form.content);
        form.reading_time = readingTime;
    }
}

function calculateReadingTimeFromContent(content: string): string {
    // Remove HTML tags for word count
    const text = content.replace(/<[^>]*>/g, ' ');
    const words = text.trim().split(/\s+/).length;
    const minutes = Math.ceil(words / 200); // Average reading speed: 200 words per minute
    return `${minutes} min`;
}

function removeFeaturedImage() {
    form.featured_image = null;
}

function handleImageUpload(event: Event) {
    emit('uploadImage', event);
}

function handleSave() {
    emit('save', { ...form });
}

function handleCancel() {
    emit('cancel');
}
</script>

<style scoped>
/* Modal Backdrop */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    overflow-y: auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    max-width: 90%;
    width: 1200px;
    margin: 20px auto;
}

.modal-xl {
    max-width: 1200px;
}

.modal-content {
    background-color: var(--k-surface);
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    color: var(--k-ink);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--k-line);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--k-ink);
}

.btn-icon {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: var(--k-ink-faint);
    font-size: 1.25rem;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.btn-icon:hover {
    background-color: var(--k-sunken);
    color: var(--k-critical);
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
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
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

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
}

textarea.form-control {
    resize: vertical;
}

.reading-time {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--k-ink-faint);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.categories-select {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.category-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-checkbox input[type='checkbox'] {
    width: auto;
    cursor: pointer;
}

.category-checkbox label {
    margin: 0;
    cursor: pointer;
    font-weight: normal;
}

.empty-categories {
    padding: 1rem;
    background-color: var(--k-sunken);
    border-radius: 4px;
    text-align: center;
    color: var(--k-ink-faint);
}

.empty-categories p {
    margin: 0;
}

.media-upload-container {
    border: 2px dashed #4a5568;
    border-radius: 8px;
    padding: 1rem;
    background-color: var(--k-sunken);
}

.media-preview {
    position: relative;
    display: inline-block;
}

.media-thumbnail {
    max-width: 300px;
    max-height: 200px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.featured-image-preview {
    display: block;
}

.media-remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: var(--k-critical);
    color: var(--k-ink);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: background-color 0.2s;
}

.media-remove-btn:hover {
    background-color: #c82333;
}

.media-upload {
    text-align: center;
}

.media-upload-label {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2rem;
    cursor: pointer;
    color: var(--k-ink-faint);
    transition: color 0.3s ease;
}

.media-upload-label:hover {
    color: var(--k-accent);
}

.media-upload-label i {
    font-size: 3rem;
}

.media-upload-input {
    display: none;
}

.checkbox-group {
    display: flex;
    gap: 1.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type='checkbox'] {
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--k-line);
}

.btn {
    padding: 0.5rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
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
</style>
