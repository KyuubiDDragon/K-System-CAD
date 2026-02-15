<!-- Improved Start Menu Component with Search, Recent Apps, Keyboard Nav, Context Menu, Power Options -->
<template>
    <div class="start-menu-overlay" @click="closeMenu" @keydown="handleGlobalKeydown">
        <div class="start-menu" @click.stop ref="menuRef">
            <!-- Search Bar -->
            <div class="menu-search-section">
                <div class="search-input-wrapper">
                    <v-icon size="20" class="search-icon">mdi-magnify</v-icon>
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        :placeholder="t('desktop.searchApps')"
                        @input="handleSearch"
                        @keydown="handleSearchKeydown"
                    />
                    <v-icon
                        v-if="searchQuery"
                        size="18"
                        class="search-clear"
                        @click="clearSearch"
                    >mdi-close</v-icon>
                </div>
            </div>

            <!-- User section -->
            <div class="menu-user-section">
                <div class="user-avatar">
                    <img :src="userAvatar" :alt="userName" />
                </div>
                <div class="user-info">
                    <div class="user-name">{{ userName }}</div>
                    <div class="user-status">{{ userStatus }}</div>
                </div>
            </div>

            <!-- Search Results (if searching) -->
            <div v-if="searchQuery && searchResults.length > 0" class="search-results-section">
                <div class="section-title">{{ t('desktop.searchResults') }}</div>
                <div class="apps-list scrollable">
                    <div
                        v-for="(app, index) in searchResults"
                        :key="app.id"
                        class="list-app"
                        :class="{ active: selectedIndex === index && navigationMode === 'search' }"
                        @click="launchApp(app)"
                        @mouseenter="setSelectedIndex(index, 'search')"
                    >
                        <div class="list-app-icon" :style="{ backgroundColor: app.color || '#3b82f6' }">
                            <v-icon size="16" color="white">{{ app.icon }}</v-icon>
                        </div>
                        <div class="list-app-title">{{ app.title }}</div>
                        <v-icon size="14" class="arrow-icon">mdi-arrow-right</v-icon>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-else-if="searchQuery && searchResults.length === 0" class="no-results">
                <v-icon size="32" color="#94a3b8">mdi-magnify-close</v-icon>
                <div class="no-results-text">{{ t('desktop.noResults') }}</div>
            </div>

            <!-- Normal Menu (when not searching) -->
            <template v-else>
                <!-- Recently Used Apps -->
                <div v-if="recentApps.length > 0" class="menu-apps-section collapsible">
                    <div class="section-header" @click="toggleSection('recent')">
                        <div class="section-title-row">
                            <v-icon size="16" class="section-icon">mdi-clock-outline</v-icon>
                            <span class="section-title">{{ t('desktop.recentlyUsed') }}</span>
                        </div>
                        <v-icon size="18" class="collapse-icon" :class="{ collapsed: !expandedSections.recent }">
                            mdi-chevron-down
                        </v-icon>
                    </div>
                    <div v-show="expandedSections.recent" class="apps-grid">
                        <div
                            v-for="(app, index) in recentApps.slice(0, 6)"
                            :key="app.id"
                            class="menu-app"
                            :class="{ active: selectedIndex === index && navigationMode === 'recent' }"
                            @click="launchApp(app)"
                            @contextmenu.prevent="openContextMenu($event, app)"
                            @mouseenter="setSelectedIndex(index, 'recent')"
                        >
                            <div class="menu-app-icon" :style="{ backgroundColor: app.color || '#3b82f6' }">
                                <v-icon size="22" color="white">{{ app.icon }}</v-icon>
                            </div>
                            <div class="menu-app-title">{{ app.title }}</div>
                        </div>
                    </div>
                </div>

                <!-- Pinned/Favorite Apps -->
                <div class="menu-apps-section collapsible">
                    <div class="section-header" @click="toggleSection('favorites')">
                        <div class="section-title-row">
                            <v-icon size="16" class="section-icon">mdi-star</v-icon>
                            <span class="section-title">{{ t('desktop.pinnedApps') }}</span>
                        </div>
                        <v-icon size="18" class="collapse-icon" :class="{ collapsed: !expandedSections.favorites }">
                            mdi-chevron-down
                        </v-icon>
                    </div>
                    <div v-show="expandedSections.favorites" class="apps-grid">
                        <div
                            v-for="(app, index) in favoriteApps"
                            :key="app.id"
                            class="menu-app"
                            :class="{ active: selectedIndex === index && navigationMode === 'favorites' }"
                            @click="launchApp(app)"
                            @contextmenu.prevent="openContextMenu($event, app)"
                            @mouseenter="setSelectedIndex(index, 'favorites')"
                        >
                            <div class="menu-app-icon" :style="{ backgroundColor: app.color || '#3b82f6' }">
                                <v-icon size="22" color="white">{{ app.icon }}</v-icon>
                                <v-icon v-if="app.isPinned" size="12" class="pin-indicator">mdi-pin</v-icon>
                            </div>
                            <div class="menu-app-title">{{ app.title }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tools & Utilities -->
                <div class="menu-apps-section collapsible">
                    <div class="section-header" @click="toggleSection('tools')">
                        <div class="section-title-row">
                            <v-icon size="16" class="section-icon">mdi-toolbox</v-icon>
                            <span class="section-title">{{ t('desktop.toolsUtilities') }}</span>
                        </div>
                        <v-icon size="18" class="collapse-icon" :class="{ collapsed: !expandedSections.tools }">
                            mdi-chevron-down
                        </v-icon>
                    </div>
                    <div v-show="expandedSections.tools" class="apps-grid">
                        <div
                            v-for="(app, index) in toolsApps"
                            :key="app.id"
                            class="menu-app"
                            :class="{ active: selectedIndex === index && navigationMode === 'tools' }"
                            @click="launchApp(app)"
                            @contextmenu.prevent="openContextMenu($event, app)"
                            @mouseenter="setSelectedIndex(index, 'tools')"
                        >
                            <div class="menu-app-icon" :style="{ backgroundColor: app.color || '#3b82f6' }">
                                <template v-if="app.icon && (app.icon.startsWith('/') || app.icon.startsWith('http'))">
                                    <img :src="app.icon" style="width: 32px; height: 32px; object-fit: contain;" :alt="app.title">
                                </template>
                                <template v-else>
                                    <v-icon size="22" color="white">{{ app.icon }}</v-icon>
                                </template>
                            </div>
                            <div class="menu-app-title">{{ app.title }}</div>
                        </div>
                    </div>
                </div>

                <!-- All Apps List -->
                <div class="menu-list-section collapsible">
                    <div class="section-header" @click="toggleSection('allApps')">
                        <div class="section-title-row">
                            <v-icon size="16" class="section-icon">mdi-apps</v-icon>
                            <span class="section-title">{{ t('desktop.allApps') }}</span>
                        </div>
                        <v-icon size="18" class="collapse-icon" :class="{ collapsed: !expandedSections.allApps }">
                            mdi-chevron-down
                        </v-icon>
                    </div>
                    <div v-show="expandedSections.allApps" class="apps-list scrollable">
                        <div
                            v-for="(app, index) in allApps"
                            :key="app.id"
                            class="list-app"
                            :class="{ active: selectedIndex === index && navigationMode === 'allApps' }"
                            @click="launchApp(app)"
                            @contextmenu.prevent="openContextMenu($event, app)"
                            @mouseenter="setSelectedIndex(index, 'allApps')"
                        >
                            <div class="list-app-icon" :style="{ backgroundColor: app.color || '#3b82f6' }">
                                <v-icon size="16" color="white">{{ app.icon }}</v-icon>
                            </div>
                            <div class="list-app-title">{{ app.title }}</div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Bottom actions with Power Options -->
            <div class="menu-actions">
                <div class="action-button settings-button" @click="openSettings">
                    <v-icon size="20">mdi-cog</v-icon>
                    <span>{{ t('desktop.settings') }}</span>
                </div>
                <div class="action-button power-button" @click="togglePowerMenu">
                    <v-icon size="20">mdi-power</v-icon>
                    <span>{{ t('desktop.power') }}</span>

                    <!-- Power Menu Dropdown -->
                    <div v-if="showPowerMenu" class="power-menu" @click.stop>
                        <div class="power-option" @click="lockScreen">
                            <v-icon size="18">mdi-lock</v-icon>
                            <span>{{ t('desktop.lock') }}</span>
                        </div>
                        <div class="power-option" @click="sleep">
                            <v-icon size="18">mdi-power-sleep</v-icon>
                            <span>{{ t('desktop.sleep') }}</span>
                        </div>
                        <div class="power-option" @click="restart">
                            <v-icon size="18">mdi-restart</v-icon>
                            <span>{{ t('desktop.restart') }}</span>
                        </div>
                        <div class="power-option danger" @click="logout">
                            <v-icon size="18">mdi-logout</v-icon>
                            <span>{{ t('desktop.logout') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Context Menu -->
            <div
                v-if="contextMenu.show"
                class="context-menu"
                :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
                @click.stop
            >
                <div class="context-menu-item" @click="togglePin(contextMenu.app)">
                    <v-icon size="16">{{ contextMenu.app?.isPinned ? 'mdi-pin-off' : 'mdi-pin' }}</v-icon>
                    <span>{{ contextMenu.app?.isPinned ? t('desktop.unpin') : t('desktop.pin') }}</span>
                </div>
                <div class="context-menu-item" @click="addToDesktop(contextMenu.app)">
                    <v-icon size="16">mdi-monitor</v-icon>
                    <span>{{ t('desktop.addToDesktop') }}</span>
                </div>
                <div class="context-menu-divider"></div>
                <div class="context-menu-item" @click="openAppSettings(contextMenu.app)">
                    <v-icon size="16">mdi-cog</v-icon>
                    <span>{{ t('desktop.appSettings') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';

const { t } = useI18n();
const toast = useToast();

interface AppItem {
    id: string | number;
    title: string;
    icon: string;
    color?: string;
    isPinned?: boolean;
    lastUsed?: number;
    category?: string;
}

interface Props {
    apps?: any[];
    recentlyUsedApps?: string[]; // Array of app IDs
}

const props = withDefaults(defineProps<Props>(), {
    apps: () => [],
    recentlyUsedApps: () => []
});

const emit = defineEmits(['app-click', 'close', 'open-search']);

// Refs
const searchInput = ref<HTMLInputElement>();
const menuRef = ref<HTMLElement>();
const searchQuery = ref('');
const selectedIndex = ref(0);
const navigationMode = ref<'search' | 'recent' | 'favorites' | 'tools' | 'allApps'>('favorites');
const showPowerMenu = ref(false);

// Stores
const authStore = useAuthStore();

// Expanded sections state
const expandedSections = ref({
    recent: true,
    favorites: true,
    tools: true,
    allApps: false
});

// Context Menu
const contextMenu = ref({
    show: false,
    x: 0,
    y: 0,
    app: null as AppItem | null
});

// User data
const userAvatar = computed(() => {
    return authStore.user?.avatar || 'https://via.placeholder.com/48';
});

const userName = computed(() => {
    return authStore.user?.name || authStore.user?.username || t('desktop.userPlaceholder');
});

const userStatus = computed(() => {
    return t('desktop.online');
});

// Search Results
const searchResults = computed(() => {
    if (!searchQuery.value) return [];

    const query = searchQuery.value.toLowerCase();
    return props.apps.filter(app =>
        app.title.toLowerCase().includes(query) ||
        (app.category && app.category.toLowerCase().includes(query))
    );
});

// Recently Used Apps
const recentApps = computed(() => {
    if (!props.recentlyUsedApps || props.recentlyUsedApps.length === 0) return [];

    return props.recentlyUsedApps
        .map(id => props.apps.find(app => app.id === id))
        .filter(app => app != null)
        .slice(0, 6);
});

// Pinned/Favorite Apps
const favoriteApps = computed(() => {
    const nonToolApps = props.apps.filter(app =>
        !['calculator', 'minesweeper', 'solitaire', 'sudoku'].includes(app.id as string)
    );
    return nonToolApps.slice(0, 8);
});

// Tool apps
const toolsApps = computed(() => {
    return props.apps.filter(app =>
        ['calculator', 'minesweeper', 'solitaire', 'sudoku', 'whiteboard', 'waterduck', 'weather', 'quacklejump'].includes(app.id as string)
    );
});

// All Apps (sorted alphabetically)
const allApps = computed(() => {
    return [...props.apps].sort((a, b) => a.title.localeCompare(b.title));
});

// Methods
const launchApp = (app: AppItem) => {
    emit('app-click', app);
    closeMenu();
};

const closeMenu = () => {
    contextMenu.value.show = false;
    emit('close');
};

const clearSearch = () => {
    searchQuery.value = '';
    selectedIndex.value = 0;
    nextTick(() => searchInput.value?.focus());
};

const handleSearch = () => {
    selectedIndex.value = 0;
    navigationMode.value = 'search';
};

const toggleSection = (section: keyof typeof expandedSections.value) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

const setSelectedIndex = (index: number, mode: typeof navigationMode.value) => {
    selectedIndex.value = index;
    navigationMode.value = mode;
};

// Keyboard Navigation
const handleSearchKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        navigateDown();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        navigateUp();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        selectCurrentItem();
    } else if (event.key === 'Escape') {
        if (searchQuery.value) {
            clearSearch();
        } else {
            closeMenu();
        }
    }
};

const handleGlobalKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (contextMenu.value.show) {
            contextMenu.value.show = false;
        } else if (showPowerMenu.value) {
            showPowerMenu.value = false;
        } else {
            closeMenu();
        }
    }
};

const navigateDown = () => {
    const currentList = getCurrentList();
    if (selectedIndex.value < currentList.length - 1) {
        selectedIndex.value++;
    }
};

const navigateUp = () => {
    if (selectedIndex.value > 0) {
        selectedIndex.value--;
    }
};

const selectCurrentItem = () => {
    const currentList = getCurrentList();
    if (currentList[selectedIndex.value]) {
        launchApp(currentList[selectedIndex.value]);
    }
};

const getCurrentList = () => {
    if (searchQuery.value) return searchResults.value;

    switch (navigationMode.value) {
        case 'recent': return recentApps.value;
        case 'favorites': return favoriteApps.value;
        case 'tools': return toolsApps.value;
        case 'allApps': return allApps.value;
        default: return [];
    }
};

// Context Menu
const openContextMenu = (event: MouseEvent, app: AppItem) => {
    contextMenu.value = {
        show: true,
        x: event.clientX,
        y: event.clientY,
        app: app
    };
};

const closeContextMenu = () => {
    contextMenu.value.show = false;
};

const togglePin = (app: AppItem | null) => {
    if (!app) return;
    // Toggle pin logic here
    toast.success(app.isPinned ? t('desktop.unpinned') : t('desktop.pinned'));
    closeContextMenu();
};

const addToDesktop = (app: AppItem | null) => {
    if (!app) return;
    // Add to desktop logic
    toast.success(t('desktop.addedToDesktop'));
    closeContextMenu();
};

const openAppSettings = (app: AppItem | null) => {
    if (!app) return;
    // Open app settings
    closeContextMenu();
};

// Power Options
const togglePowerMenu = () => {
    showPowerMenu.value = !showPowerMenu.value;
};

const lockScreen = () => {
    showPowerMenu.value = false;
    toast.info(t('desktop.lockingScreen'));
    // Lock screen logic
};

const sleep = () => {
    showPowerMenu.value = false;
    toast.info(t('desktop.enteringSleep'));
    // Sleep logic
};

const restart = () => {
    showPowerMenu.value = false;
    if (confirm(t('desktop.confirmRestart'))) {
        // Restart logic
        window.location.reload();
    }
};

const logout = async () => {
    showPowerMenu.value = false;
    closeMenu();
    await authStore.logout();
};

const openSettings = () => {
    // Open settings
    closeMenu();
};

// Click outside to close context menu
const handleClickOutside = (event: MouseEvent) => {
    if (contextMenu.value.show) {
        const target = event.target as HTMLElement;
        if (!target.closest('.context-menu')) {
            closeContextMenu();
        }
    }
};

// Lifecycle
onMounted(() => {
    nextTick(() => {
        searchInput.value?.focus();
    });
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Watch for menu open to focus search
watch(() => props.apps, () => {
    nextTick(() => {
        searchInput.value?.focus();
    });
});
</script>

<style scoped>
/* Base styles - keeping most of the original beautiful design */
.start-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.start-menu {
    position: fixed;
    bottom: 60px;
    left: 12px;
    width: 520px;
    max-width: 90vw;
    max-height: 85vh;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
    backdrop-filter: blur(20px);
    overflow-y: auto;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 8px 16px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.15);
    z-index: 1001;
    animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: bottom left;
    display: flex;
    flex-direction: column;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Search Section */
.menu-search-section {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 10px 12px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.search-input-wrapper:focus-within {
    background: rgba(255, 255, 255, 0.15);
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
    color: rgba(255, 255, 255, 0.6);
    margin-right: 8px;
}

.search-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: white;
    font-size: 14px;
    font-weight: 500;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.search-clear {
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: color 0.2s;
}

.search-clear:hover {
    color: white;
}

/* User Section */
.menu-user-section {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #3b82f6;
    margin-right: 16px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-name {
    font-size: 16px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
    letter-spacing: 0.3px;
}

.user-status {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    font-weight: 500;
}

.user-status::before {
    content: '';
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #10b981;
    margin-right: 8px;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }
    50% { box-shadow: 0 0 12px rgba(16, 185, 129, 0.8); }
}

/* Collapsible Sections */
.collapsible .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.collapsible .section-header:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.section-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-icon {
    color: #3b82f6;
}

.section-title {
    font-size: 13px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.collapse-icon {
    color: rgba(255, 255, 255, 0.6);
    transition: transform 0.3s ease;
}

.collapse-icon.collapsed {
    transform: rotate(-90deg);
}

/* Apps Grid */
.menu-apps-section {
    padding-bottom: 12px;
}

.apps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 0 20px 8px;
}

.menu-app {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    padding: 8px;
    border-radius: 12px;
    position: relative;
}

.menu-app:hover,
.menu-app.active {
    transform: translateY(-4px) scale(1.05);
    background-color: rgba(255, 255, 255, 0.1);
}

.menu-app-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    position: relative;
}

.menu-app:hover .menu-app-icon {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}

.pin-indicator {
    position: absolute;
    top: 2px;
    right: 2px;
    color: #fbbf24;
}

.menu-app-title {
    font-size: 12px;
    color: white;
    text-align: center;
    max-width: 80px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}

/* Apps List (All Apps & Search Results) */
.menu-list-section,
.search-results-section {
    padding: 0 16px 12px;
}

.apps-list {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 8px;
    max-height: 240px;
}

.apps-list.scrollable {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) rgba(0, 0, 0, 0.2);
}

.apps-list::-webkit-scrollbar {
    width: 6px;
}

.apps-list::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
}

.apps-list::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.3);
    border-radius: 8px;
}

.list-app {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.list-app:hover,
.list-app.active {
    background-color: rgba(59, 130, 246, 0.2);
}

.list-app-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
}

.list-app-title {
    font-size: 13px;
    color: white;
    flex: 1;
}

.arrow-icon {
    color: rgba(255, 255, 255, 0.4);
}

/* No Results */
.no-results {
    padding: 40px 20px;
    text-align: center;
    color: #94a3b8;
}

.no-results-text {
    margin-top: 12px;
    font-size: 14px;
}

/* Bottom Actions */
.menu-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 12px 20px;
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.6));
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0 0 16px 16px;
    margin-top: auto;
}

.action-button {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 10px;
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    flex: 1;
    justify-content: center;
    position: relative;
}

.action-button:hover {
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.action-button span {
    margin-left: 8px;
}

.settings-button:hover {
    background-color: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
}

.power-button:hover {
    background-color: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
}

/* Power Menu */
.power-menu {
    position: absolute;
    bottom: 100%;
    right: 0;
    margin-bottom: 8px;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
    backdrop-filter: blur(20px);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    z-index: 10;
    min-width: 180px;
    animation: slideUp 0.2s ease;
}

.power-option {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: white;
    font-size: 13px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.power-option:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.power-option.danger:hover {
    background-color: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

.power-option span {
    margin-left: 12px;
}

/* Context Menu */
.context-menu {
    position: fixed;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
    backdrop-filter: blur(20px);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    z-index: 2000;
    min-width: 200px;
    overflow: hidden;
    animation: fadeIn 0.15s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.context-menu-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: white;
    font-size: 13px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.context-menu-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.context-menu-item span {
    margin-left: 12px;
}

.context-menu-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 4px 0;
}

/* Responsive */
@media (max-width: 480px) {
    .start-menu {
        width: calc(100% - 24px);
    }

    .apps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>