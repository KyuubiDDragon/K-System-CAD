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

            <!--
                Der Zustand des Beitrags.

                Vorher: ein Haekchen "Veroeffentlicht" und daneben ein Feld
                "Veroeffentlichungsdatum" mit dem Hinweis, man koenne ein
                zukuenftiges Datum setzen. Das Haekchen schrieb in is_published
                - eine Spalte, die in der Datenbank als veraltet markiert ist -
                und das Datum bewirkte nichts, weil status nie gesetzt wurde.
            -->
            <div class="form-group">
                <span class="feld-beschriftung">{{ t('post.zustand') }}</span>
                <div class="zustand-auswahl">
                    <button
                        v-for="z in ZUSTAENDE"
                        :key="z.wert"
                        type="button"
                        class="zustand-knopf"
                        :class="{ gewaehlt: form.status === z.wert }"
                        @click="form.status = z.wert"
                    >
                        <i :class="['mdi', z.symbol]"></i>
                        <span class="zustand-name">{{ t(z.name) }}</span>
                        <span class="zustand-text">{{ t(z.text) }}</span>
                    </button>
                </div>
            </div>

            <div class="form-group" v-if="form.status === 'scheduled'">
                <label for="scheduled-at">{{ t('post.terminLabel') }}</label>
                <input
                    type="datetime-local"
                    id="scheduled-at"
                    v-model="form.scheduled_at"
                    :min="fruehesterTermin"
                    class="form-control"
                />
                <small class="form-text">
                    {{ terminHinweis }}
                </small>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="form.is_featured" />
                        <span>{{ t('post.hervorgehoben') }}</span>
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
import { useI18n } from 'vue-i18n';
import TiptapEditor from '@/components/TiptapEditor.vue';

const { t } = useI18n();

/*
   Die vier Zustaende der Spalte status. Die Schluessel sind die Werte in der
   Datenbank und bleiben englisch; uebersetzt wird nur, was dasteht.

   'archived' ist bewusst dabei: es ist der Weg, einen alten Beitrag von der
   Website zu nehmen, ohne ihn zu loeschen.
*/
const ZUSTAENDE = [
    { wert: 'draft', symbol: 'mdi-pencil-outline', name: 'post.entwurf', text: 'post.entwurfText' },
    { wert: 'published', symbol: 'mdi-earth', name: 'post.veroeffentlicht', text: 'post.veroeffentlichtText' },
    { wert: 'scheduled', symbol: 'mdi-clock-outline', name: 'post.geplant', text: 'post.geplantText' },
    { wert: 'archived', symbol: 'mdi-archive-outline', name: 'post.archiviert', text: 'post.archiviertText' },
] as const;

/**
 * Wandelt einen Zeitstempel aus der Datenbank in das Format, das ein
 * datetime-local-Feld versteht. Es akzeptiert nur "JJJJ-MM-TTTHH:MM".
 */
function fuerEingabefeld(wert: string | null | undefined): string {
    if (!wert) return '';
    const d = new Date(String(wert).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '';
    const zwei = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${zwei(d.getMonth() + 1)}-${zwei(d.getDate())}T${zwei(d.getHours())}:${zwei(d.getMinutes())}`;
}


// Interfaces
export interface Post {
    id: number | null;
    title: string;
    slug: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    /* Aus der Datenbank: status ist massgeblich, is_published nur noch Altlast. */
    status?: 'draft' | 'published' | 'scheduled' | 'archived';
    scheduled_at?: string | null;
    is_published?: boolean | number;
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
    status: 'draft' | 'published' | 'scheduled' | 'archived';
    scheduled_at: string;
    is_featured: boolean;
    selectedCategories: number[];
    reading_time: string;
    gallery_images: string[];
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
    status: 'draft',
    scheduled_at: '',
    is_featured: false,
    selectedCategories: [],
    reading_time: '',
    gallery_images: [],
});

/** Ein Termin in der Vergangenheit ist keiner - das Feld begrenzt sich selbst. */
const fruehesterTermin = computed(() => fuerEingabefeld(new Date().toISOString()));

const terminHinweis = computed(() => {
    if (!form.scheduled_at) return t('post.terminFehlt');
    const d = new Date(form.scheduled_at);
    if (Number.isNaN(d.getTime())) return t('post.terminFehlt');
    if (d.getTime() <= Date.now()) return t('post.terminVergangen');
    return t('post.terminHinweis');
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
            /*
               Beitraege aus der Zeit vor der Zustandsspalte tragen dort noch
               den Vorgabewert 'draft', obwohl das Haekchen gesetzt war. Ein
               veroeffentlichter Beitrag soll nach dem Oeffnen nicht als
               Entwurf dastehen - deshalb im Zweifel aus is_published ableiten.
            */
            form.status = ZUSTAENDE.some((z) => z.wert === newPost.status)
                ? (newPost.status as PostForm['status'])
                : (newPost.is_published ? 'published' : 'draft');
            form.scheduled_at = fuerEingabefeld(newPost.scheduled_at);
            form.is_featured = !!newPost.is_featured;
            form.selectedCategories = newPost.categories
                ? newPost.categories.map(cat => cat.id)
                : [];
            form.reading_time = newPost.reading_time || calculateReadingTimeFromContent(newPost.content || '');
            form.gallery_images = newPost.gallery_images || [];
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
    form.status = 'draft';
    form.scheduled_at = '';
    form.is_featured = false;
    form.selectedCategories = [];
    form.reading_time = '';
    form.gallery_images = [];
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
    color: var(--k-on-fill);
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

/* --- Zustand des Beitrags ---------------------------------------------- */

.feld-beschriftung {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--k-ink);
}

.zustand-auswahl {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
}

.zustand-knopf {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
    padding: 10px 12px;
    text-align: left;
    background: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-radius: 6px;
    cursor: pointer;
    font: inherit;
    transition:
        border-color 120ms ease,
        background 120ms ease;
}

.zustand-knopf:hover {
    border-color: var(--k-line-strong);
}

.zustand-knopf:focus-visible {
    outline: 2px solid var(--k-accent);
    outline-offset: 2px;
}

.zustand-knopf.gewaehlt {
    border-color: var(--k-accent);
    background: color-mix(in srgb, var(--k-accent) 10%, var(--k-sunken));
}

.zustand-knopf .mdi {
    font-size: 17px;
    color: var(--k-ink-faint);
}

.zustand-knopf.gewaehlt .mdi {
    color: var(--k-accent);
}

.zustand-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--k-ink);
}

.zustand-text {
    font-size: 11.5px;
    line-height: 1.4;
    color: var(--k-ink-muted);
}
</style>
