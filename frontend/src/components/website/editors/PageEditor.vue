<template>
    <div>
        <!-- Modal Backdrop -->
        <div
            class="modal-backdrop fade show"
            v-if="isVisible"
            @click="$emit('cancel')"
        ></div>

        <!-- Modal Dialog -->
        <div
            class="modal fade show"
            v-if="isVisible"
            style="display: block;"
            tabindex="-1"
        >
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="page-form">
                        <div class="form-header">
                            <h3>
                                {{ isEditing ? 'Seite bearbeiten' : 'Neue Seite' }}
                            </h3>
                            <button @click="$emit('cancel')" class="btn-icon">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>

                        <div class="form-group">
                            <label for="page-title">Titel</label>
                            <input
                                type="text"
                                id="page-title"
                                v-model="localForm.title"
                                class="form-control"
                                required
                                @input="handleTitleInput"
                            />
                        </div>

                        <div class="form-group">
                            <label for="page-slug">URL-Slug</label>
                            <input
                                type="text"
                                id="page-slug"
                                v-model="localForm.slug"
                                class="form-control"
                                placeholder="beispiel-seite"
                                required
                            />
                            <small class="form-text"
                                >Der Slug wird in der URL verwendet (z.B.
                                /beispiel-seite)</small
                            >
                        </div>

                        <div class="form-group">
                            <label for="page-content">Inhalt</label>
                            <div v-if="localForm.useBlocks">
                                <PageBlockEditor
                                    v-model="localForm.blocks"
                                    :pages="pages"
                                    :websiteId="websiteId"
                                    :colorScheme="colorScheme"
                                    @block-image-upload="handleBlockImageUpload"
                                />
                            </div>
                            <div v-else>
                                <div class="block-editor-toggle">
                                    <button
                                        class="btn btn-sm btn-secondary"
                                        @click="convertToBlockEditor"
                                    >
                                        <i class="mdi mdi-th-large"></i> Zum
                                        Baukastensystem wechseln
                                    </button>
                                    <p class="text-muted small mt-1">
                                        Hinweis: Beim Wechsel zum Baukastensystem
                                        wird der aktuelle Inhalt als einfacher
                                        Textblock übernommen.
                                    </p>
                                </div>
                                <TiptapEditor
                                    v-model="localForm.content"
                                    placeholder="Inhalt der Seite..."
                                    id="page-content"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input
                                        type="checkbox"
                                        v-model="localForm.is_published"
                                    />
                                    <span>Veröffentlicht</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                @click="$emit('cancel')"
                                class="btn btn-secondary"
                            >
                                Abbrechen
                            </button>
                            <button @click="handleSave" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import TiptapEditor from '@/components/TiptapEditor.vue';
import PageBlockEditor from '@/components/PageBlockEditor.vue';

interface PageBlock {
    id: string;
    type: string;
    content: Record<string, any>;
}

interface Page {
    id: number | null;
    title: string;
    slug: string;
    content: string;
    is_published: boolean;
    use_blocks?: boolean;
}

interface PageFormData {
    id: number | null;
    title: string;
    slug: string;
    content: string;
    is_published: boolean;
    useBlocks: boolean;
    blocks: PageBlock[];
}

interface Props {
    isVisible: boolean;
    currentPage: Page | null;
    pages: Page[];
    websiteId: number | null;
    colorScheme?: any;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    save: [formData: PageFormData];
    cancel: [];
    'block-image-upload': [data: any];
}>();

const localForm = reactive<PageFormData>({
    id: null,
    title: '',
    slug: '',
    content: '',
    is_published: true,
    useBlocks: false,
    blocks: [],
});

const isEditing = ref(false);

// Watch for changes to currentPage prop
watch(() => props.currentPage, (newPage) => {
    if (newPage) {
        isEditing.value = true;
        localForm.id = newPage.id;
        localForm.title = newPage.title;
        localForm.slug = newPage.slug;
        localForm.is_published = !!newPage.is_published;

        // Parse content to determine if it's blocks or regular content
        try {
            const content = newPage.content || '';
            if (content.startsWith('[') && content.endsWith(']')) {
                // It appears to be a JSON array, try to parse it
                localForm.blocks = JSON.parse(content);
                localForm.useBlocks = true;
                localForm.content = '';
            } else {
                // Normal content
                localForm.content = content;
                localForm.useBlocks = newPage.use_blocks ? true : false;

                // If use_blocks is enabled but no blocks are present, convert the content
                if (newPage.use_blocks && (!content.startsWith('[') || !content.endsWith(']'))) {
                    convertToBlockEditor();
                } else {
                    localForm.blocks = [];
                }
            }
        } catch (e) {
            // If parsing fails, treat it as normal content
            console.error('Error parsing blocks:', e);
            localForm.content = newPage.content || '';
            localForm.useBlocks = false;
            localForm.blocks = [];
        }
    } else {
        // Reset form for new page
        isEditing.value = false;
        resetForm();
    }
}, { immediate: true });

// Watch for visibility changes to reset form when opening new page
watch(() => props.isVisible, (newVal) => {
    if (newVal && !props.currentPage) {
        resetForm();
    }
});

function resetForm() {
    localForm.id = null;
    localForm.title = '';
    localForm.slug = '';
    localForm.content = '';
    localForm.is_published = true;
    localForm.useBlocks = false;
    localForm.blocks = [];
}

function handleTitleInput() {
    // Auto-generate slug only when creating new page and slug is empty
    if (!localForm.id && !localForm.slug) {
        localForm.slug = localForm.title
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
}

function convertToBlockEditor() {
    if (!localForm.useBlocks) {
        const textBlockContent = localForm.content || '';

        const newBlocks: PageBlock[] = [
            {
                id: generateUniqueId(),
                type: 'text',
                content: {
                    text: textBlockContent,
                },
            },
        ];

        localForm.blocks = newBlocks;
        localForm.useBlocks = true;
        localForm.content = '';
    }
}

function generateUniqueId(): string {
    // Check if crypto.randomUUID is available (modern browsers)
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    // Fallback implementation if randomUUID is not available
    let d = new Date().getTime();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

function handleSave() {
    emit('save', { ...localForm });
}

function handleBlockImageUpload(data: any) {
    emit('block-image-upload', data);
}
</script>

<style scoped>
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
    overflow-x: hidden;
    overflow-y: auto;
    z-index: 1050;
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem auto;
    max-width: 1200px;
}

.modal-xl {
    max-width: 1200px;
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    background-color: var(--k-sunken);
    background-clip: padding-box;
    border: 1px solid var(--k-line);
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.page-form {
    padding: 20px;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--k-line);
}

.form-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--k-ink);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #e5e7eb;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--k-line);
    border-radius: 4px;
    background-color: #1e2327;
    color: #e5e7eb;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--k-ink-faint);
}

.checkbox-group {
    display: flex;
    align-items: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    color: #e5e7eb;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
    cursor: pointer;
}

.checkbox-label span {
    user-select: none;
}

.block-editor-toggle {
    margin-bottom: 15px;
    padding: 15px;
    background-color: #1e2327;
    border: 1px solid var(--k-line);
    border-radius: 4px;
}

.block-editor-toggle .btn {
    margin-bottom: 8px;
}

.text-muted {
    color: var(--k-ink-faint);
}

.small {
    font-size: 12px;
}

.mt-1 {
    margin-top: 4px;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid var(--k-line);
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

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-icon {
    background: transparent;
    border: none;
    color: var(--k-ink-faint);
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background-color: #374151;
    color: #e5e7eb;
}
</style>
