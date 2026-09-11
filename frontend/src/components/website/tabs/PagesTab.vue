<template>
    <div class="pages-tab">
        <h2>Seiten</h2>
        <p>
            Der Aufbau der Website: welche Seiten es gibt und wie man sie
            erreicht.
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
                <!--
                    Abschnitte gab es bis hierher als eigenen Hauptpunkt. Sie
                    sind aber nichts Eigenes: sie sind der Aufbau der einen
                    Seite, die der Einseiter hat. Deshalb stehen sie jetzt hier
                    - und nur dann, wenn die Vorlage sie ueberhaupt kennt.
                -->
                <div
                    v-if="istEinseiter"
                    class="tab"
                    :class="{ active: activeSubTab === 'abschnitte' }"
                    @click="$emit('update:activeSubTab', 'abschnitte')"
                >
                    <i class="mdi mdi-view-sequential"></i> Abschnitte
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
                            Noch keine Seiten. Leg die erste an – sie wird zur Startseite.
                        </p>
                    </div>
                </div>

                <div v-else-if="activeSubTab === 'abschnitte'" class="abschnitte-content">
                    <slot name="abschnitte" />
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
import { computed } from 'vue';
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
    /** Vorlage der Website - entscheidet, ob es Abschnitte gibt. */
    layoutTemplate?: string;
}

const props = defineProps<Props>();

const istEinseiter = computed(() => props.layoutTemplate === 'onepager');

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
    background-color: var(--k-surface);
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
    background-color: var(--k-sunken);
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
    color: var(--k-on-fill);
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
    background-color: var(--k-sunken);
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
