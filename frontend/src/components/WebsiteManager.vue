<template>
    <div class="website-manager-app">
        <!-- Top Toolbar -->
        <div class="app-toolbar" v-if="!isPreviewMode">
            <div class="app-title">
                <i class="mdi mdi-web icon"></i>
                <span>{{ t('website.managerTitle') }}</span>
            </div>

            <div class="website-selector" v-if="websites.length > 0 && !isPreviewMode">
                <label for="website-select">{{ t('website.labelWebsite') }}</label>
                <select
                    id="website-select"
                    v-model="selectedWebsiteId"
                    @change="loadWebsiteDetails"
                >
                    <option v-for="website in websites" :key="website.id" :value="website.id">
                        {{ website.site_name }}
                    </option>
                </select>
            </div>

            <div class="action-buttons">
                <button v-if="isPreviewMode" class="btn btn-secondary" @click="togglePreviewMode">
                    <i class="mdi mdi-pencil"></i> {{ t('website.backToEditor') }}
                </button>
                <template v-else>
                    <button
                        class="btn btn-primary save-btn"
                        @click="saveAll"
                        :disabled="!isDirty"
                        v-if="hasWebsite"
                    >
                        <i class="mdi mdi-content-save"></i> {{ t('website.save') }}
                    </button>
                    <button
                        class="btn btn-secondary preview-btn"
                        @click="togglePreviewMode"
                        :disabled="!hasWebsite"
                        v-if="hasWebsite"
                    >
                        <i class="mdi mdi-eye"></i> {{ t('website.preview') }}
                    </button>
                    <button
                        class="btn btn-success create-btn"
                        @click="createNewWebsite"
                        v-if="websites.length === 0"
                    >
                        <i class="mdi mdi-plus"></i> {{ t('website.createWebsite') }}
                    </button>
                </template>
            </div>
        </div>

        <!-- Preview Mode -->
        <div v-if="isPreviewMode" class="preview-mode">
            <!-- Preview Controls -->
            <div class="preview-controls">
                <button class="btn btn-secondary" @click="togglePreviewMode">
                    <i class="mdi mdi-pencil"></i> {{ t('website.backToEditor') }}
                </button>
            </div>

            <div class="website-preview">
                <WebsiteView
                    :website="websiteSettings"
                    :navigationItems="navigationItems"
                    :pages="pages"
                    :posts="posts"
                    :categories="categories"
                    :sections="sections"
                    :news="news"
                    :previewMode="true"
                />
            </div>
        </div>

        <!-- Main Content -->
        <div v-else>
            <!-- Loading State -->
            <div class="app-content" v-if="isLoading">
                <div class="loading-spinner">
                    <i class="mdi mdi-spinner fa-spin"></i> {{ t('website.loadingDataShort') }}
                </div>
            </div>

            <!-- No Website State -->
            <div
                class="app-content no-website-container"
                v-else-if="!hasWebsite && websites.length === 0"
            >
                <div class="no-website-message">
                    <i class="mdi mdi-web-off icon"></i>
                    <h3>{{ t('website.noWebsite') }}</h3>
                    <p>Sie haben noch keine Website erstellt. Jede Authority kann eine Website haben.</p>
                    <div class="button-container">
                        <button type="button" @click="createNewWebsite" class="create-website-btn">
                            {{ t('website.createWebsite') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- No Website Selected -->
            <div class="app-content" v-else-if="!hasWebsite">
                <div class="no-website-message">
                    <i class="mdi mdi-exclamation-circle"></i>
                    <h3>{{ t('website.noWebsiteSelected') }}</h3>
                    <p>{{ t('website.selectPrompt') }}</p>
                </div>
            </div>

            <!-- Main Editor Layout -->
            <div class="app-content main-content" v-else>
                <!-- Sidebar Navigation -->
                <div class="sidebar">
                    <ul class="nav-menu">
                        <li
                            :class="{ active: activeTab === 'settings' }"
                            @click="activeTab = 'settings'"
                        >
                            <i class="mdi mdi-cog"></i> Einstellungen
                        </li>
                        <li :class="{ active: activeTab === 'pages' }" @click="activeTab = 'pages'">
                            <i class="mdi mdi-file-document-outline"></i> Seiten
                        </li>
                        <li :class="{ active: activeTab === 'posts' }" @click="activeTab = 'posts'">
                            <i class="mdi mdi-newspaper"></i> Beiträge
                        </li>
                        <li
                            :class="{ active: activeTab === 'categories' }"
                            @click="activeTab = 'categories'"
                        >
                            <i class="mdi mdi-tag-multiple"></i> Kategorien
                        </li>
                        <li :class="{ active: activeTab === 'media' }" @click="activeTab = 'media'">
                            <i class="mdi mdi-image-multiple"></i> Medien
                        </li>
                        <li :class="{ active: activeTab === 'news' }" @click="activeTab = 'news'">
                            <i class="mdi mdi-newspaper-variant"></i> News
                        </li>
                        <li :class="{ active: activeTab === 'sections' }" @click="activeTab = 'sections'">
                            <i class="mdi mdi-view-sequential"></i> Sections
                        </li>
                        <li
                            :class="{ active: activeTab === 'contact' }"
                            @click="activeTab = 'contact'"
                        >
                            <i class="mdi mdi-email"></i> Kontaktanfragen
                        </li>
                        <li :class="{ active: activeTab === 'howto' }" @click="activeTab = 'howto'">
                            <i class="mdi mdi-help-circle"></i> How To
                        </li>
                    </ul>
                </div>

                <!-- Content Area -->
                <div class="content-area">
                    <!-- Settings Tab -->
                    <SettingsTab
                        v-if="activeTab === 'settings'"
                        :website-settings="websiteSettings"
                        :pages="pages"
                        :social-links="socialLinks"
                        :preset-color-schemes="presetColorSchemes"
                        :get-media-url="getMediaUrl"
                        @change="onChange('settings')"
                        @update:website-settings="updateWebsiteSettings"
                        @upload-media="uploadMedia"
                        @remove-media="removeMedia"
                        @update-social-links="updateSocialLinks"
                        @select-scheme="selectColorScheme"
                    />

                    <!-- Pages Tab -->
                    <PagesTab
                        v-if="activeTab === 'pages'"
                        :pages="pages"
                        :active-sub-tab="activeSubTab"
                        :is-page-editor-visible="isPageFormVisible"
                        :navigation-items="navigationItems"
                        :show-navigation-form="isNavigationFormVisible"
                        :navigation-form="navigationForm"
                        :categories="categories"
                        @create-page="createPage"
                        @edit-page="editPage"
                        @delete-page="deletePage"
                        @update:active-sub-tab="activeSubTab = $event"
                        @switch-to-navigation="activeSubTab = 'navigation'"
                        @create-navigation="createNavigation"
                        @edit-navigation="editNavigation"
                        @delete-navigation="deleteNavigation"
                        @save-navigation="saveNavigation"
                        @close-navigation-form="closeNavigationForm"
                        @sort-navigation="updateNavigationOrder"
                    />

                    <!-- Posts Tab -->
                    <PostsTab
                        v-if="activeTab === 'posts'"
                        :posts="posts"
                        :categories="categories"
                        @create="createPost"
                        @edit="editPost"
                        @delete="deletePost"
                        @sort="updatePostOrder"
                    />

                    <!-- Categories Tab -->
                    <CategoriesTab
                        v-if="activeTab === 'categories'"
                        :categories="categories"
                        :show-form="isCategoryFormVisible"
                        :form="categoryForm"
                        @create="createCategory"
                        @edit="editCategory"
                        @delete="deleteCategory"
                        @save="saveCategory"
                        @close-form="closeCategoryForm"
                        @sort="updateCategoryOrder"
                    />

                    <!-- Media Tab -->
                    <MediaTab
                        v-if="activeTab === 'media'"
                        :media="media"
                        :is-loading="isMediaLoading"
                        :filter="mediaFilter"
                        :show-edit-form="isMediaEditFormVisible"
                        :edit-form="mediaEditForm"
                        :get-media-url="getMediaUrl"
                        @upload="uploadMedia"
                        @edit="editMediaItem"
                        @delete="deleteMediaItem"
                        @save="saveMediaItem"
                        @close-edit="closeMediaEditForm"
                        @update:filter="mediaFilter = $event"
                    />

                    <!-- News Tab -->
                    <NewsTab
                        v-if="activeTab === 'news'"
                        :news="news"
                        @create="createNews"
                        @edit="editNews"
                        @delete="deleteNews"
                    />

                    <!-- Sections Tab -->
                    <SectionsTab
                        v-if="activeTab === 'sections'"
                        :sections="sections"
                        :current-template="websiteSettings.layout_template || 'default'"
                        @create="createSection"
                        @edit="editSection"
                        @delete="deleteSection"
                        @toggle-active="toggleSectionActive"
                        @update-order="updateSectionOrder"
                    />

                    <!-- Contact Tab -->
                    <ContactTab
                        v-if="activeTab === 'contact'"
                        :contacts="contacts"
                        :is-loading="isContactsLoading"
                        :filter="contactFilter"
                        :selected-contact="selectedContact"
                        @view="viewContact"
                        @toggle-read="toggleContactRead"
                        @reply="replyToContact"
                        @delete="deleteContact"
                        @close-detail="closeContactDetail"
                        @update:filter="contactFilter = $event"
                    />

                    <!-- How To Tab -->
                    <div v-if="activeTab === 'howto'" class="howto-tab">
                        <h2>Anleitung zur Website-Verwaltung</h2>
                        <div class="howto-content">
                            <h3>1. Website-Einstellungen</h3>
                            <p>
                                In den Einstellungen können Sie grundlegende Informationen wie Name,
                                Slogan und Beschreibung Ihrer Website festlegen.
                            </p>
                            <h3>2. Seiten erstellen</h3>
                            <p>
                                Erstellen Sie einzelne Seiten für Ihre Website. Jede Seite hat einen
                                Titel, eine URL (Slug) und Inhalte.
                            </p>
                            <h3>3. Navigation verwalten</h3>
                            <p>
                                Legen Sie fest, welche Seiten in der Navigation angezeigt werden und
                                in welcher Reihenfolge.
                            </p>
                            <h3>4. Beiträge und Kategorien</h3>
                            <p>
                                Fügen Sie Blog-Beiträge hinzu und organisieren Sie diese in
                                Kategorien.
                            </p>
                            <h3>5. Medien hochladen</h3>
                            <p>
                                Laden Sie Bilder und andere Medien hoch, die Sie in Ihren Seiten und
                                Beiträgen verwenden können.
                            </p>
                            <h3>6. Kontaktanfragen</h3>
                            <p>
                                Sehen Sie eingehende Kontaktanfragen von Besuchern Ihrer Website ein
                                und antworten Sie darauf.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Editor Modal -->
        <PageEditor
            :is-visible="isPageFormVisible"
            :current-page="editingPage"
            :pages="pages"
            :website-id="selectedWebsiteId"
            :color-scheme="getCurrentColorScheme()"
            @save="savePage"
            @cancel="closePageForm"
            @block-image-upload="handleBlockImageUpload"
        />

        <!-- Post Editor Modal -->
        <PostEditor
            :is-visible="isPostFormVisible"
            :post="editingPost"
            :categories="categories"
            @save="savePost"
            @cancel="closePostForm"
        />

        <!-- News Editor Modal -->
        <NewsEditor
            v-if="isNewsEditorVisible"
            :news-item="editingNews"
            @close="closeNewsEditor"
            @save="saveNews"
        />

        <!-- Section Editor Modal -->
        <SectionEditor
            v-if="isSectionEditorVisible"
            :section="editingSection"
            @close="closeSectionEditor"
            @save="saveSection"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, defineAsyncComponent } from 'vue';
import { apiClientAuth } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import WebsiteView from '@/views/WebsiteView.vue';

// Lazy load tab components
const SettingsTab = defineAsyncComponent(() => import('./website/tabs/SettingsTab.vue'));
const PagesTab = defineAsyncComponent(() => import('./website/tabs/PagesTab.vue'));
const PostsTab = defineAsyncComponent(() => import('./website/tabs/PostsTab.vue'));
const CategoriesTab = defineAsyncComponent(() => import('./website/tabs/CategoriesTab.vue'));
const MediaTab = defineAsyncComponent(() => import('./website/tabs/MediaTab.vue'));
const NewsTab = defineAsyncComponent(() => import('./website/tabs/NewsTab.vue'));
const SectionsTab = defineAsyncComponent(() => import('./website/tabs/SectionsTab.vue'));
const ContactTab = defineAsyncComponent(() => import('./website/tabs/ContactTab.vue'));

// Lazy load editor components
const PageEditor = defineAsyncComponent(() => import('./website/editors/PageEditor.vue'));
const PostEditor = defineAsyncComponent(() => import('./website/editors/PostEditor.vue'));
const NewsEditor = defineAsyncComponent(() => import('./website/editors/NewsEditor.vue'));
const SectionEditor = defineAsyncComponent(() => import('./website/editors/SectionEditor.vue'));

// Composables and utilities
// import { useWebsiteData } from '@/composables/useWebsiteData';
// import { useAutoSave } from '@/composables/useAutoSave';
// import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
// import { generateSlug } from '@/utils/slugGenerator';

const toast = useToast();
const authStore = useAuthStore();
const { t } = useI18n();

// Toast helpers
function showError(message: string) {
    toast.error(message, {
        timeout: 3000,
    });
}

function showSuccess(message: string) {
    toast.success(message, {
        timeout: 3000,
    });
}

// State
const isLoading = ref(true);
const websites = ref<any[]>([]);
const selectedWebsiteId = ref<number | null>(null);
const currentWebsite = ref<any>(null);

const websiteSettings = reactive({
    id: null,
    site_name: '',
    site_slogan: '',
    navbar_name: 'Home',
    site_description: '',
    hero_image: null,
    cta_text: '',
    cta_target_type: 'page',
    cta_target_id: null,
    logo: null,
    banner: null,
    background_image: null,
    custom_css: null,
    selectedScheme: 'Blue Ocean',
    selected_scheme: 'Blue Ocean',
    useCustomColors: false,
    customColors: {
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#ffffff',
        text: '#111827',
    },
    contact_email: '',
    contact_phone: '',
    footer_text: '',
    is_active: true,
    maintenance_mode: false,
    maintenance_message: '',
    show_contact_form: true,
    layout_template: 'default',
    template_settings: null,
    sections_order: null,
});

const presetColorSchemes = [
    // ===== WHITE MODE DESIGNS (Row 1) =====
    {
        name: 'Blue Ocean',
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: 'var(--k-accent-hover)',
        bannerText: '#ffffff',
        heroBackground: 'var(--k-accent)',
        heroText: '#ffffff',
        buttonBackground: 'var(--k-accent)',
        buttonText: '#ffffff',
    },
    {
        name: 'Forest Green',
        primary: '#10b981',
        secondary: '#34d399',
        accent: '#6ee7b7',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: '#065f46',
        bannerText: '#ffffff',
        heroBackground: '#10b981',
        heroText: '#ffffff',
        buttonBackground: '#10b981',
        buttonText: '#ffffff',
    },
    {
        name: 'Royal Purple',
        primary: '#8b5cf6',
        secondary: '#a78bfa',
        accent: '#c4b5fd',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: '#5b21b6',
        bannerText: '#ffffff',
        heroBackground: '#8b5cf6',
        heroText: '#ffffff',
        buttonBackground: '#8b5cf6',
        buttonText: '#ffffff',
    },
    {
        name: 'Sunset Orange',
        primary: '#f59e0b',
        secondary: '#fbbf24',
        accent: '#fcd34d',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: '#b45309',
        bannerText: '#ffffff',
        heroBackground: '#f59e0b',
        heroText: '#ffffff',
        buttonBackground: '#f59e0b',
        buttonText: '#ffffff',
    },
    {
        name: 'Rose Pink',
        primary: '#ec4899',
        secondary: '#f472b6',
        accent: '#fbcfe8',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: '#9f1239',
        bannerText: '#ffffff',
        heroBackground: '#ec4899',
        heroText: '#ffffff',
        buttonBackground: '#ec4899',
        buttonText: '#ffffff',
    },

    // ===== DARK MODE DESIGNS (Row 2) =====
    {
        name: 'Dark Blue',
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#111827',
        text: '#f9fafb',
        bannerBackground: '#1f2937',
        bannerText: '#f9fafb',
        heroBackground: 'var(--k-accent-hover)',
        heroText: '#f9fafb',
        buttonBackground: 'var(--k-accent)',
        buttonText: '#ffffff',
    },
    {
        name: 'Dark Forest',
        primary: '#10b981',
        secondary: '#34d399',
        accent: '#6ee7b7',
        background: '#0f1419',
        text: '#f0fdf4',
        bannerBackground: '#1a1f1e',
        bannerText: '#f0fdf4',
        heroBackground: '#064e3b',
        heroText: '#f0fdf4',
        buttonBackground: '#10b981',
        buttonText: '#ffffff',
    },
    {
        name: 'Dark Purple',
        primary: '#a855f7',
        secondary: '#c084fc',
        accent: '#e9d5ff',
        background: '#1e1b2e',
        text: '#faf5ff',
        bannerBackground: '#2d2640',
        bannerText: '#faf5ff',
        heroBackground: '#581c87',
        heroText: '#faf5ff',
        buttonBackground: '#a855f7',
        buttonText: '#ffffff',
    },
    {
        name: 'Dark Amber',
        primary: '#f59e0b',
        secondary: '#fbbf24',
        accent: '#fde68a',
        background: '#1c1917',
        text: '#fffbeb',
        bannerBackground: '#292524',
        bannerText: '#fffbeb',
        heroBackground: '#78350f',
        heroText: '#fffbeb',
        buttonBackground: '#f59e0b',
        buttonText: '#ffffff',
    },
    {
        name: 'Dark Slate',
        primary: '#64748b',
        secondary: '#94a3b8',
        accent: '#cbd5e1',
        background: '#0f172a',
        text: '#f1f5f9',
        bannerBackground: '#1e293b',
        bannerText: '#f1f5f9',
        heroBackground: '#334155',
        heroText: '#f1f5f9',
        buttonBackground: '#475569',
        buttonText: '#ffffff',
    },
];

// Data arrays
const pages = ref<any[]>([]);
const posts = ref<any[]>([]);
const categories = ref<any[]>([]);
const media = ref<any[]>([]);
const news = ref<any[]>([]);
const sections = ref<any[]>([]);
const contacts = ref<any[]>([]);
const navigationItems = ref<any[]>([]);
const socialLinks = ref<any[]>([]);

// UI State
const activeTab = ref('settings');
const activeSubTab = ref('pages');
const isDirty = ref(false);
const changedSections = reactive(new Set());
const isPreviewMode = ref(false);

// Form visibility
const isPageFormVisible = ref(false);
const isPostFormVisible = ref(false);
const isNewsEditorVisible = ref(false);
const isSectionEditorVisible = ref(false);
const editingPage = ref<any>(null);
const editingPost = ref<any>(null);
const editingNews = ref<any>(null);
const editingSection = ref<any>(null);

// Category state
const isCategoryFormVisible = ref(false);
const editingCategory = ref<any>(null);
const categoryForm = reactive({
    id: null as number | null,
    name: '',
    slug: '',
    description: '',
    color: '#007bff',
});

// Media state
const isMediaLoading = ref(false);
const mediaFilter = ref('all');
const isMediaEditFormVisible = ref(false);
const editingMedia = ref<any>(null);
const mediaEditForm = reactive({
    id: null as number | null,
    title: '',
    alt_text: '',
    description: '',
    media_type: 'content',
    is_featured: false,
});

// Contacts state
const isContactsLoading = ref(false);
const contactFilter = ref('all');
const selectedContact = ref<any>(null);

// Navigation state
const isNavigationFormVisible = ref(false);
const editingNavigationItem = ref<any>(null);
const navigationForm = reactive({
    id: null as number | null,
    title: '',
    url: '',
    page_id: null as number | null,
    parent_id: null as number | null,
    target: '_self',
    is_active: true,
    is_blog: false,
    blog_categories: '',
});

// Computed
const hasWebsite = computed(() => currentWebsite.value !== null);

// Methods
function onChange(section: string) {
    isDirty.value = true;
    changedSections.add(section);
}

function updateWebsiteSettings(newSettings: any) {
    Object.assign(websiteSettings, newSettings);
    onChange('settings');
}

function selectColorScheme(schemeName: string) {
    websiteSettings.selectedScheme = schemeName;
    websiteSettings.selected_scheme = schemeName;
    websiteSettings.useCustomColors = false;
    onChange('settings');
}

function getCurrentColorScheme() {
    if (websiteSettings.useCustomColors) {
        return websiteSettings.customColors;
    }
    const scheme = presetColorSchemes.find(
        s => s.name === websiteSettings.selectedScheme || s.name === websiteSettings.selected_scheme
    );
    return scheme || presetColorSchemes[0];
}

function getMediaUrl(fileName: string | null): string {
    if (!fileName) return '';
    const apiUrl = import.meta.env.VITE_API_URL || '';
    return `${apiUrl}/uploads/website/${fileName}`;
}

// Page Management
function createPage() {
    editingPage.value = null;
    isPageFormVisible.value = true;
}

function editPage(page: any) {
    editingPage.value = page;
    isPageFormVisible.value = true;
}

function closePageForm() {
    isPageFormVisible.value = false;
    editingPage.value = null;
}

async function savePage(formData: any) {
    try {
        const action = formData.id ? 'updatePage' : 'createPage';
        const response = await apiClientAuth.post('/company/website/', {
            action,
            website_id: selectedWebsiteId.value,
            ...formData,
        });

        if (response.data.success) {
            showSuccess(formData.id ? 'Seite aktualisiert' : 'Seite erstellt');
            await loadWebsiteDetails();
            closePageForm();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving page:', error);
        showError('Fehler beim Speichern der Seite');
    }
}

async function deletePage(page: any) {
    if (!confirm(`Möchten Sie die Seite "${page.title}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deletePage',
            website_id: selectedWebsiteId.value,
            page_id: page.id,
        });

        if (response.data.success) {
            showSuccess('Seite gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting page:', error);
        showError('Fehler beim Löschen der Seite');
    }
}

// Post Management
function createPost() {
    editingPost.value = null;
    isPostFormVisible.value = true;
}

function editPost(post: any) {
    editingPost.value = post;
    isPostFormVisible.value = true;
}

function closePostForm() {
    isPostFormVisible.value = false;
    editingPost.value = null;
}

async function savePost(formData: any) {
    try {
        const action = formData.id ? 'updatePost' : 'createPost';
        const response = await apiClientAuth.post('/company/website/', {
            action,
            website_id: selectedWebsiteId.value,
            ...formData,
        });

        if (response.data.success) {
            showSuccess(formData.id ? 'Beitrag aktualisiert' : 'Beitrag erstellt');
            await loadWebsiteDetails();
            closePostForm();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving post:', error);
        showError('Fehler beim Speichern des Beitrags');
    }
}

async function deletePost(post: any) {
    if (!confirm(`Möchten Sie den Beitrag "${post.title}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deletePost',
            website_id: selectedWebsiteId.value,
            post_id: post.id,
        });

        if (response.data.success) {
            showSuccess('Beitrag gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting post:', error);
        showError('Fehler beim Löschen des Beitrags');
    }
}

async function updatePostOrder(event: any) {
    // Extract the new order from draggable event
    const newOrder = posts.value.map((post, index) => ({
        id: post.id,
        sort_order: index,
    }));

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updatePostOrder',
            website_id: selectedWebsiteId.value,
            posts: newOrder,
        });

        if (response.data.success) {
            showSuccess('Reihenfolge aktualisiert');
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Error updating post order:', error);
        showError('Fehler beim Aktualisieren der Reihenfolge');
    }
}

// Category Management
function createCategory() {
    // Reset form
    categoryForm.id = null;
    categoryForm.name = '';
    categoryForm.slug = '';
    categoryForm.description = '';
    categoryForm.color = '#007bff';
    isCategoryFormVisible.value = true;
}

function editCategory(category: any) {
    // Fill form with category data
    categoryForm.id = category.id;
    categoryForm.name = category.name;
    categoryForm.slug = category.slug;
    categoryForm.description = category.description || '';
    categoryForm.color = category.color || '#007bff';
    isCategoryFormVisible.value = true;
}

function closeCategoryForm() {
    isCategoryFormVisible.value = false;
    categoryForm.id = null;
    categoryForm.name = '';
    categoryForm.slug = '';
    categoryForm.description = '';
    categoryForm.color = '#007bff';
}

async function saveCategory(formData: any) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'saveCategory',
            website_id: selectedWebsiteId.value,
            ...formData,
        });

        if (response.data.success) {
            showSuccess(formData.id ? 'Kategorie aktualisiert' : 'Kategorie erstellt');
            await loadWebsiteDetails();
            closeCategoryForm();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving category:', error);
        showError('Fehler beim Speichern der Kategorie');
    }
}

async function updateCategoryOrder(event: any) {
    // Extract the new order from draggable event
    const newOrder = categories.value.map((cat, index) => ({
        id: cat.id,
        sort_order: index,
    }));

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateCategoryOrder',
            website_id: selectedWebsiteId.value,
            categories: newOrder,
        });

        if (response.data.success) {
            showSuccess('Reihenfolge aktualisiert');
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Error updating category order:', error);
        showError('Fehler beim Aktualisieren der Reihenfolge');
    }
}

async function deleteCategory(category: any) {
    if (!confirm(`Möchten Sie die Kategorie "${category.name}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteCategory',
            website_id: selectedWebsiteId.value,
            category_id: category.id,
        });

        if (response.data.success) {
            showSuccess('Kategorie gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        showError('Fehler beim Löschen der Kategorie');
    }
}

// Media Management
function editMediaItem(item: any) {
    // Fill form with media data
    mediaEditForm.id = item.id;
    mediaEditForm.title = item.title || '';
    mediaEditForm.alt_text = item.alt_text || '';
    mediaEditForm.description = item.description || '';
    mediaEditForm.media_type = item.media_type || 'content';
    mediaEditForm.is_featured = !!item.is_featured;
    isMediaEditFormVisible.value = true;
}

function closeMediaEditForm() {
    isMediaEditFormVisible.value = false;
    mediaEditForm.id = null;
    mediaEditForm.title = '';
    mediaEditForm.alt_text = '';
    mediaEditForm.description = '';
    mediaEditForm.media_type = 'content';
    mediaEditForm.is_featured = false;
}

async function saveMediaItem(formData: any) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateMedia',
            website_id: selectedWebsiteId.value,
            ...formData,
        });

        if (response.data.success) {
            showSuccess('Medien-Informationen aktualisiert');
            await loadWebsiteDetails();
            closeMediaEditForm();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving media:', error);
        showError('Fehler beim Speichern der Medien-Informationen');
    }
}

async function deleteMediaItem(item: any) {
    if (!confirm(`Möchten Sie die Datei "${item.filename}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteMedia',
            website_id: selectedWebsiteId.value,
            media_id: item.id,
        });

        if (response.data.success) {
            showSuccess('Datei gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting media:', error);
        showError('Fehler beim Löschen der Datei');
    }
}

async function uploadMedia(event: Event, mediaType = 'content') {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', 'uploadMedia');
    formData.append('website_id', selectedWebsiteId.value?.toString() || '');
    formData.append('media_type', mediaType);
    formData.append('file', file);

    try {
        const response = await apiClientAuth.post('/company/website/', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data.success) {
            showSuccess('Datei hochgeladen');

            // Update the appropriate field
            if (mediaType !== 'content') {
                (websiteSettings as any)[mediaType] = response.data.filename;
                onChange('settings');
            }

            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Hochladen');
        }
    } catch (error) {
        console.error('Error uploading media:', error);
        showError('Fehler beim Hochladen der Datei');
    }
}

function removeMedia(mediaType: string) {
    (websiteSettings as any)[mediaType] = null;
    onChange('settings');
}

function updateSocialLinks(links: any[]) {
    socialLinks.value = links;
    onChange('settings');
}

// Contact Management
function viewContact(contact: any) {
    selectedContact.value = contact;
    // Optionally mark as read when viewing
    if (!contact.is_read) {
        markContactAsRead(contact);
    }
}

function closeContactDetail() {
    selectedContact.value = null;
}

async function toggleContactRead(contact: any, closeAfter = false) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'markSubmissionAsRead',
            website_id: selectedWebsiteId.value,
            contact_id: contact.id,
            is_read: !contact.is_read,
        });

        if (response.data.success) {
            showSuccess(contact.is_read ? 'Als ungelesen markiert' : 'Als gelesen markiert');
            await loadWebsiteDetails();
            if (closeAfter) {
                closeContactDetail();
            }
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Error toggling contact read status:', error);
        showError('Fehler beim Aktualisieren des Status');
    }
}

async function markContactAsRead(contact: any) {
    if (contact.is_read) return; // Already read

    try {
        await apiClientAuth.post('/company/website/', {
            action: 'markSubmissionAsRead',
            website_id: selectedWebsiteId.value,
            contact_id: contact.id,
            is_read: true,
        });
        // Silently update without reloading
        contact.is_read = true;
    } catch (error) {
        console.error('Error marking contact as read:', error);
    }
}

function replyToContact(contact: any) {
    // Open email client with pre-filled data
    const subject = `Re: ${contact.subject || 'Ihre Kontaktanfrage'}`;
    const mailtoLink = `mailto:${contact.email}?subject=${encodeURIComponent(subject)}`;
    window.location.href = mailtoLink;
}

async function deleteContact(contact: any) {
    if (!confirm(`Möchten Sie diese Kontaktanfrage wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteContact',
            website_id: selectedWebsiteId.value,
            contact_id: contact.id,
        });

        if (response.data.success) {
            showSuccess('Kontaktanfrage gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting contact:', error);
        showError('Fehler beim Löschen der Kontaktanfrage');
    }
}

// News Management
function createNews() {
    editingNews.value = null;
    isNewsEditorVisible.value = true;
}

function editNews(newsItem: any) {
    editingNews.value = newsItem;
    isNewsEditorVisible.value = true;
}

function closeNewsEditor() {
    isNewsEditorVisible.value = false;
    editingNews.value = null;
}

async function saveNews(newsData: any) {
    try {
        const action = newsData.id ? 'updateNews' : 'createNews';

        const response = await apiClientAuth.post('/company/website/', {
            action,
            website_id: selectedWebsiteId.value,
            ...newsData,
        });

        if (response.data.success) {
            showSuccess(newsData.id ? 'News aktualisiert' : 'News erstellt');
            await loadWebsiteDetails();
            closeNewsEditor();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving news:', error);
        showError('Fehler beim Speichern der News');
    }
}

async function deleteNews(newsItem: any) {
    if (!confirm(`Möchten Sie die News "${newsItem.title}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteNews',
            website_id: selectedWebsiteId.value,
            news_id: newsItem.id,
        });

        if (response.data.success) {
            showSuccess('News gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting news:', error);
        showError('Fehler beim Löschen der News');
    }
}

// Sections Management
function createSection() {
    editingSection.value = null;
    isSectionEditorVisible.value = true;
}

function editSection(section: any) {
    editingSection.value = section;
    isSectionEditorVisible.value = true;
}

function closeSectionEditor() {
    isSectionEditorVisible.value = false;
    editingSection.value = null;
}

async function saveSection(sectionData: any) {
    try {
        const action = sectionData.id ? 'updateSection' : 'createSection';

        // Ensure settings is a JSON string
        const payload = {
            action,
            website_id: selectedWebsiteId.value,
            ...sectionData,
            settings: JSON.stringify(sectionData.settings || {}),
        };

        const response = await apiClientAuth.post('/company/website/', payload);

        if (response.data.success) {
            showSuccess(sectionData.id ? 'Section aktualisiert' : 'Section erstellt');
            await loadWebsiteDetails();
            closeSectionEditor();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving section:', error);
        showError('Fehler beim Speichern der Section');
    }
}

async function deleteSection(section: any) {
    if (!confirm(`Möchten Sie die Section "${section.title || section.section_type}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteSection',
            website_id: selectedWebsiteId.value,
            section_id: section.id,
        });

        if (response.data.success) {
            showSuccess('Section gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting section:', error);
        showError('Fehler beim Löschen der Section');
    }
}

async function toggleSectionActive(section: any) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateSection',
            website_id: selectedWebsiteId.value,
            id: section.id,
            section_type: section.section_type,
            title: section.title,
            content: section.content,
            settings: JSON.stringify(section.settings || {}),
            is_active: !section.is_active,
        });

        if (response.data.success) {
            showSuccess(section.is_active ? 'Section deaktiviert' : 'Section aktiviert');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Error toggling section active:', error);
        showError('Fehler beim Aktualisieren der Section');
    }
}

async function updateSectionOrder(sections: any[]) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateSectionOrder',
            website_id: selectedWebsiteId.value,
            sections: sections.map(s => ({
                id: s.id,
                sort_order: s.sort_order,
            })),
        });

        if (response.data.success) {
            showSuccess('Reihenfolge aktualisiert');
            // Update local state without full reload for smoother UX
            sections.value = sections;
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
            // Reload on error to get correct state
            await loadWebsiteDetails();
        }
    } catch (error) {
        console.error('Error updating section order:', error);
        showError('Fehler beim Aktualisieren der Reihenfolge');
        await loadWebsiteDetails();
    }
}

// Navigation Management
function createNavigation() {
    // Reset form
    navigationForm.id = null;
    navigationForm.title = '';
    navigationForm.url = '';
    navigationForm.page_id = null;
    navigationForm.parent_id = null;
    navigationForm.target = '_self';
    navigationForm.is_active = true;
    navigationForm.is_blog = false;
    navigationForm.blog_categories = '';
    isNavigationFormVisible.value = true;
}

function editNavigation(item: any) {
    // Fill form with navigation item data
    navigationForm.id = item.id;
    navigationForm.title = item.title;
    navigationForm.url = item.url || '';
    navigationForm.page_id = item.page_id || null;
    navigationForm.parent_id = item.parent_id || null;
    navigationForm.target = item.target || '_self';
    navigationForm.is_active = !!item.is_active;
    navigationForm.is_blog = !!item.is_blog;
    navigationForm.blog_categories = item.blog_categories || '';
    isNavigationFormVisible.value = true;
}

function closeNavigationForm() {
    isNavigationFormVisible.value = false;
    navigationForm.id = null;
    navigationForm.title = '';
    navigationForm.url = '';
    navigationForm.page_id = null;
    navigationForm.parent_id = null;
    navigationForm.target = '_self';
    navigationForm.is_active = true;
    navigationForm.is_blog = false;
    navigationForm.blog_categories = '';
}

async function saveNavigation(formData: any) {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'saveNavigationItem',
            website_id: selectedWebsiteId.value,
            navigationData: formData,
        });

        if (response.data.success) {
            showSuccess(formData.id ? 'Navigationspunkt aktualisiert' : 'Navigationspunkt erstellt');
            await loadWebsiteDetails();
            closeNavigationForm();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving navigation item:', error);
        showError('Fehler beim Speichern des Navigationspunkts');
    }
}

async function deleteNavigation(item: any) {
    if (!confirm(`Möchten Sie den Navigationspunkt "${item.title}" wirklich löschen?`)) return;

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'deleteNavigationItem',
            website_id: selectedWebsiteId.value,
            navigation_id: item.id,
        });

        if (response.data.success) {
            showSuccess('Navigationspunkt gelöscht');
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error deleting navigation item:', error);
        showError('Fehler beim Löschen des Navigationspunkts');
    }
}

async function updateNavigationOrder(items: any[]) {
    // Extract the new order from items
    const newOrder = items.map((item, index) => ({
        id: item.id,
        sort_order: index,
    }));

    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateNavigationOrder',
            website_id: selectedWebsiteId.value,
            items: newOrder,
        });

        if (response.data.success) {
            showSuccess('Reihenfolge aktualisiert');
        } else {
            showError(response.data.error || 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        console.error('Error updating navigation order:', error);
        showError('Fehler beim Aktualisieren der Reihenfolge');
    }
}

// Save and Load
async function saveAll() {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'updateWebsite',
            website_id: selectedWebsiteId.value,
            ...websiteSettings,
            social_links: JSON.stringify(socialLinks.value),
        });

        if (response.data.success) {
            showSuccess('Änderungen gespeichert');
            isDirty.value = false;
            changedSections.clear();
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error saving website:', error);
        showError('Fehler beim Speichern der Website');
    }
}

async function loadWebsiteDetails() {
    if (!selectedWebsiteId.value) return;

    try {
        isLoading.value = true;
        const response = await apiClientAuth.get('/company/website/', {
            params: {
                action: 'getWebsiteDetails',
                website_id: selectedWebsiteId.value,
            },
        });

        if (response.data.success) {
            const data = response.data.data;
            currentWebsite.value = data.website;

            // Update settings
            Object.assign(websiteSettings, data.website);

            // Parse social links
            if (data.website.social_links) {
                try {
                    socialLinks.value = JSON.parse(data.website.social_links);
                } catch (e) {
                    socialLinks.value = [];
                }
            }

            // Update data arrays
            pages.value = data.pages || [];
            posts.value = data.posts || [];
            categories.value = data.categories || [];
            media.value = data.media || [];
            news.value = data.news || [];

            // Parse sections settings from JSON strings
            sections.value = (data.sections || []).map((section: any) => {
                if (section.settings && typeof section.settings === 'string') {
                    try {
                        section.settings = JSON.parse(section.settings);
                    } catch (e) {
                        console.error('Error parsing section settings:', e);
                        section.settings = {};
                    }
                }
                return section;
            });

            contacts.value = data.contacts || [];
            navigationItems.value = data.navigation || [];
        }
    } catch (error) {
        console.error('Error loading website details:', error);
        showError('Fehler beim Laden der Website-Daten');
    } finally {
        isLoading.value = false;
    }
}

async function createNewWebsite() {
    try {
        const response = await apiClientAuth.post('/company/website/', {
            action: 'createWebsite',
        });

        if (response.data.success) {
            showSuccess('Website erstellt');
            await loadWebsites();
            selectedWebsiteId.value = response.data.website_id;
            await loadWebsiteDetails();
        } else {
            showError(response.data.error || 'Fehler beim Erstellen');
        }
    } catch (error) {
        console.error('Error creating website:', error);
        showError('Fehler beim Erstellen der Website');
    }
}

async function loadWebsites() {
    try {
        const response = await apiClientAuth.get('/company/website/', {
            params: { action: 'getWebsites' },
        });

        if (response.data.success) {
            websites.value = response.data.websites || [];
            if (websites.value.length > 0 && !selectedWebsiteId.value) {
                selectedWebsiteId.value = websites.value[0].id;
            }
        }
    } catch (error) {
        console.error('Error loading websites:', error);
        showError('Fehler beim Laden der Websites');
    }
}

function togglePreviewMode() {
    isPreviewMode.value = !isPreviewMode.value;
}

function handleBlockImageUpload(data: any) {
    // Handle block image upload
    uploadMedia(data.event, 'content');
}

// Lifecycle
onMounted(async () => {
    await loadWebsites();
    if (selectedWebsiteId.value) {
        await loadWebsiteDetails();
    } else {
        isLoading.value = false;
    }
});

// Watch for website selection changes
watch(selectedWebsiteId, (newId) => {
    if (newId) {
        loadWebsiteDetails();
    }
});
</script>

<style scoped>
/* Import the styles from the original WebsiteManagerApp.vue */
/* For brevity, only essential styles are included here */
.website-manager-app {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #1e2327;
    color: var(--k-ink);
}

.app-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
    gap: 1rem;
    flex-wrap: wrap;
    min-height: 70px;
}

.app-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.website-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.website-selector select {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: 1px solid var(--k-line);
    background-color: #1e2327;
    color: var(--k-ink);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: nowrap;
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

.btn-secondary {
    background-color: var(--k-neutral);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-neutral);
}

.btn-success {
    background-color: #10b981;
    color: var(--k-ink);
}

.btn-success:hover {
    background-color: #059669;
}

.app-content {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
}

.main-content {
    display: flex;
    gap: 2rem;
    padding: 0;
    height: 100%;
}

.sidebar {
    width: 250px;
    min-height: 100%;
    background-color: var(--k-sunken);
    padding: 1rem;
    border-right: 1px solid var(--k-line);
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-menu li {
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-menu li:hover {
    background-color: #374151;
}

.nav-menu li.active {
    background-color: var(--k-accent);
    color: var(--k-ink);
}

.content-area {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;
}

.no-website-message {
    text-align: center;
    padding: 4rem 2rem;
}

.no-website-message .icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.create-website-btn {
    padding: 0.75rem 1.5rem;
    background-color: var(--k-accent);
    color: var(--k-ink);
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.create-website-btn:hover {
    background-color: var(--k-accent-hover);
}

.preview-mode {
    flex: 1;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
}

.preview-controls {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 1100;
    display: flex;
    gap: 0.5rem;
}

.website-preview {
    position: relative;
    flex: 1;
}

.howto-tab {
    padding: 1rem;
}

.howto-content {
    margin-top: 1.5rem;
}

.howto-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--k-accent);
}

.howto-content p {
    margin-bottom: 1rem;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .main-content {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--k-line);
    }

    .nav-menu {
        display: flex;
        overflow-x: auto;
    }

    .nav-menu li {
        white-space: nowrap;
    }
}
</style>
