<template>
    <div class="pages-tab">
        <h2>Seiten & Navigation verwalten</h2>
        <p>
            Hier können Sie Ihre Webseiten und Navigationspunkte erstellen und
            bearbeiten.
        </p>

        <div class="tabs-container">
            <div class="tabs">
                <div
                    class="tab"
                    :class="{ active: activeSubTab === 'pages' }"
                    @click="$emit('update:activeSubTab', 'pages')"
                >
                    <i class="mdi mdi-file-document-outline"></i> Seiten
                </div>
                <div
                    class="tab"
                    :class="{ active: activeSubTab === 'navigation' }"
                    @click="$emit('switch-to-navigation')"
                >
                    <i class="mdi mdi-menu"></i> Navigation
                </div>
            </div>

            <div class="tab-content">
                <div v-if="activeSubTab === 'pages'" class="pages-content">
                    <button @click="$emit('create-page')" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Neue Seite
                    </button>

                    <div class="pages-list" v-if="pages.length > 0 && !isPageEditorVisible">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Titel</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="page in pages" :key="page.id">
                                    <td>{{ page.title }}</td>
                                    <td>/{{ page.slug }}</td>
                                    <td>
                                        {{
                                            page.is_published
                                                ? 'Veröffentlicht'
                                                : 'Entwurf'
                                        }}
                                    </td>
                                    <td class="actions">
                                        <button
                                            @click="$emit('edit-page', page)"
                                            class="btn-icon"
                                        >
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <button
                                            @click="$emit('delete-page', page)"
                                            class="btn-icon"
                                        >
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else-if="!isPageEditorVisible" class="empty-state">
                        <p>
                            Keine Seiten vorhanden. Erstellen Sie Ihre erste Seite.
                        </p>
                    </div>
                </div>

                <div v-else-if="activeSubTab === 'navigation'" class="navigation-content">
                    <NavigationTab
                        :navigation-items="navigationItems"
                        :show-form="showNavigationForm"
                        :form="navigationForm"
                        :pages="pages"
                        :categories="categories"
                        @create="$emit('create-navigation')"
                        @edit="$emit('edit-navigation', $event)"
                        @delete="$emit('delete-navigation', $event)"
                        @save="$emit('save-navigation', $event)"
                        @close-form="$emit('close-navigation-form')"
                        @sort="$emit('sort-navigation', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import NavigationTab from './NavigationTab.vue';

interface Page {
    id: number;
    title: string;
    slug: string;
    content: string;
    is_published: boolean;
    use_blocks?: boolean;
}

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
    pages: Page[];
    activeSubTab: string;
    isPageEditorVisible: boolean;
    navigationItems: NavigationItem[];
    showNavigationForm?: boolean;
    navigationForm?: NavigationForm;
    categories: any[];
}

defineProps<Props>();

defineEmits<{
    'create-page': [];
    'edit-page': [page: Page];
    'delete-page': [page: Page];
    'update:activeSubTab': [value: string];
    'switch-to-navigation': [];
    'create-navigation': [];
    'edit-navigation': [item: NavigationItem];
    'delete-navigation': [item: NavigationItem];
    'save-navigation': [form: NavigationForm];
    'close-navigation-form': [];
    'sort-navigation': [items: NavigationItem[]];
}>();
</script>

<style scoped>
.pages-tab {
    width: 100%;
}

.pages-tab h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--k-ink);
}

.pages-tab > p {
    margin-bottom: 1.5rem;
    color: var(--k-ink-faint);
}

.tabs-container {
    margin-top: 20px;
}

.tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #4a5568;
    margin-bottom: 20px;
}

.tab {
    padding: 10px 20px;
    cursor: pointer;
    background-color: var(--k-sunken);
    border: 1px solid var(--k-line);
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    color: var(--k-ink);
    transition: all 0.3s ease;
}

.tab:hover {
    background-color: #374151;
}

.tab.active {
    background-color: var(--k-accent);
    color: var(--k-ink);
    border-color: var(--k-accent);
}

.tab i {
    margin-right: 8px;
}

.tab-content {
    padding: 20px 0;
}

.pages-content {
    width: 100%;
}

.pages-list {
    margin-top: 20px;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    margin-top: 20px;
    background-color: #1e2327;
    color: var(--k-ink);
}

.data-table thead th {
    background-color: var(--k-sunken);
    color: var(--k-ink);
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #4a5568;
}

.data-table tbody tr {
    background-color: var(--k-sunken);
    transition: background-color 0.3s ease;
}

.data-table tbody tr:hover {
    background-color: #374151;
}

.data-table tbody td {
    padding: 12px;
    border-bottom: 1px solid var(--k-line);
}

.data-table tbody td.actions {
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

.btn-icon {
    background: transparent;
    border: none;
    color: var(--k-accent);
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background-color: #374151;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--k-ink-faint);
}

.empty-state p {
    font-size: 16px;
}
</style>
