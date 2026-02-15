// stores/ui.ts
import { defineStore } from 'pinia';
import { apiClientAuth } from '@/api'; // API Client importieren
import { desktopApi } from '@/api'; // Desktop API importieren
import { ref, reactive, computed } from 'vue';

/**
 * UI Store
 * Enthält UI-bezogene Einstellungen und Zustände
 */

// Typdefinitionen
export interface IconPosition {
    x: number;
    y: number;
}

export interface RecentlyVisitedItem {
    route: string;
    title: string;
    timestamp: number;
}

export type LayoutPreference = 'desktop' | 'sidebar' | null;

export const useUIStore = defineStore('ui', () => {
    // Zustand
    const isDesktopMode = ref(false);
    const isDesktopWindow = ref(false);
    const isAppReady = ref(false);
    const desktopTheme = ref('default');
    const openWindows = ref([]);
    const startMenuOpen = ref(false);
    const desktopIconPositions = reactive<Record<string, IconPosition>>({});
    const isLoadingDesktopSettings = ref(false);
    
    // Navigation visibility
    const hideLeftNav = ref(false);
    const hideTopNav = ref(false);
    const hideTabs = ref(false);
    
    // Desktop settings
    const desktop = ref(false);
    
    // Computed styles
    const mainContentStyle = computed(() => {
        // This logic should be updated based on route.meta when route is available
        return {};
    });
    
    // Locale settings
    const currentLocale = ref('de');
    const localeOptions = ref([
        { title: 'Deutsch', value: 'de' },
        { title: 'English', value: 'en' }
    ]);

    // ========================================
    // SIDEBAR LAYOUT STATE
    // ========================================
    const layoutPreference = ref<LayoutPreference>(null);
    const alwaysAskForLayout = ref(false);
    const sidebarCollapsed = ref(false);
    const sidebarWidth = ref(280);
    const pinnedMenuItems = ref<string[]>([]);
    const recentlyVisited = ref<RecentlyVisitedItem[]>([]);

    // Methoden
    function setDesktopMode(value: boolean) {
        isDesktopMode.value = value;

        // HTML-Klasse aktualisieren
        if (value) {
            document.documentElement.classList.add('desktop-mode');
        } else {
            document.documentElement.classList.remove('desktop-mode');
        }
    }
    
    function toggleDesktopMode() {
        setDesktopMode(!isDesktopMode.value);
        
        // Wenn Desktop-Modus aktiviert wird, lade die Einstellungen
        if (isDesktopMode.value) {
            loadDesktopSettings();
        }
    }
    
    // Startmenü umschalten
    function toggleStartMenu() {
        startMenuOpen.value = !startMenuOpen.value;
    }
    
    // Desktop-Einstellungen vom Server laden
    function loadDesktopSettings(callback?: () => void) {
        if (isLoadingDesktopSettings.value) return;
        
        isLoadingDesktopSettings.value = true;
        
        apiClientAuth
            .get('/desktop', {
                params: {
                    action: 'getSettings',
                    _t: Date.now(), // Cache-Busting
                },
            })
            .then(response => {
                console.log('Desktop settings loaded:', response.data);
                if (response?.data) {
                    // Icon-Positionen verarbeiten
                    if (response.data.icon_positions) {
                        try {
                            const positions = typeof response.data.icon_positions === 'string'
                                ? JSON.parse(response.data.icon_positions)
                                : response.data.icon_positions;
                            
                            // Positionen in den Store übernehmen
                            Object.keys(positions).forEach(key => {
                                desktopIconPositions[key] = positions[key];
                            });
                        } catch (e) {
                            console.error('Failed to parse icon positions:', e);
                        }
                    }
                    
                    // Theme verarbeiten
                    if (response.data.theme) {
                        desktopTheme.value = response.data.theme;
                        document.documentElement.setAttribute('data-theme', response.data.theme);
                    }
                    
                    // Callback aufrufen, wenn vorhanden
                    if (callback) callback();
                }
            })
            .catch(err => {
                console.error('Error loading desktop settings:', err);
            })
            .finally(() => {
                isLoadingDesktopSettings.value = false;
            });
    }
    
    // Desktop-Einstellungen speichern
    function saveDesktopSettings() {
        return apiClientAuth.post('/desktop', {
            action: 'saveSettings',
            theme: desktopTheme.value,
            icon_positions: JSON.stringify(desktopIconPositions)
        });
    }
    
    // Icon Position aktualisieren
    function updateIconPosition(appId: string, position: IconPosition) {
        console.log('📍 Updating icon position in store:', appId, position);
        desktopIconPositions[appId] = position;
    }

    // ========================================
    // SIDEBAR LAYOUT METHODS
    // ========================================

    /**
     * Set layout preference (desktop, sidebar, or null = always ask)
     */
    async function setLayoutPreference(preference: LayoutPreference) {
        layoutPreference.value = preference;

        if (preference !== null) {
            await saveLayoutPreference();
        }
    }

    /**
     * Toggle "always ask" setting
     */
    async function setAlwaysAskForLayout(value: boolean) {
        alwaysAskForLayout.value = value;

        if (value) {
            // Wenn "immer fragen" aktiviert, lösche gespeicherte Präferenz
            layoutPreference.value = null;
        }

        await saveLayoutPreference();
    }

    /**
     * Set sidebar collapsed state
     */
    function setSidebarCollapsed(collapsed: boolean) {
        sidebarCollapsed.value = collapsed;
        saveSidebarSettings();
    }

    /**
     * Toggle menu item pin status
     */
    function togglePinnedMenuItem(itemId: string) {
        const index = pinnedMenuItems.value.indexOf(itemId);
        if (index > -1) {
            pinnedMenuItems.value.splice(index, 1);
        } else {
            pinnedMenuItems.value.push(itemId);
        }
        saveSidebarSettings();
    }

    /**
     * Add route to recently visited
     */
    function addRecentlyVisited(route: string, title: string) {
        // Remove existing entry
        recentlyVisited.value = recentlyVisited.value.filter(
            item => item.route !== route
        );

        // Add to beginning
        recentlyVisited.value.unshift({
            route,
            title,
            timestamp: Date.now()
        });

        // Keep only last 10
        recentlyVisited.value = recentlyVisited.value.slice(0, 10);

        saveSidebarSettings();
    }

    /**
     * Save sidebar settings to backend
     */
    async function saveSidebarSettings() {
        try {
            await apiClientAuth.post('/desktop/', {
                action: 'saveSidebarSettings',
                settings: {
                    collapsed: sidebarCollapsed.value,
                    width: sidebarWidth.value,
                    pinnedItems: pinnedMenuItems.value,
                    recentlyVisited: recentlyVisited.value
                }
            });
        } catch (error) {
            console.error('Failed to save sidebar settings:', error);
        }
    }

    /**
     * Load sidebar settings from backend
     */
    async function loadSidebarSettings() {
        try {
            const response = await apiClientAuth.get('/desktop/', {
                params: { action: 'getSidebarSettings' }
            });

            if (response.data) {
                sidebarCollapsed.value = response.data.collapsed || false;
                sidebarWidth.value = response.data.width || 280;
                pinnedMenuItems.value = response.data.pinnedItems || [];
                recentlyVisited.value = response.data.recentlyVisited || [];
            }
        } catch (error) {
            console.error('Failed to load sidebar settings:', error);
        }
    }

    /**
     * Save layout preference to backend
     */
    async function saveLayoutPreference() {
        try {
            await apiClientAuth.post('/desktop/', {
                action: 'saveLayoutPreference',
                preference: layoutPreference.value,
                alwaysAsk: alwaysAskForLayout.value
            });
        } catch (error) {
            console.error('Failed to save layout preference:', error);
        }
    }

    /**
     * Load layout preference from backend
     */
    async function loadLayoutPreference() {
        try {
            const response = await apiClientAuth.get('/desktop/', {
                params: { action: 'getLayoutPreference' }
            });

            if (response.data) {
                layoutPreference.value = response.data.preference || null;
                alwaysAskForLayout.value = response.data.alwaysAsk || false;
            }
        } catch (error) {
            console.error('Failed to load layout preference:', error);
        }
    }

    return {
        // Desktop State
        isDesktopMode,
        isDesktopWindow,
        isAppReady,
        desktopTheme,
        openWindows,
        startMenuOpen,
        desktopIconPositions,
        isLoadingDesktopSettings,
        hideLeftNav,
        hideTopNav,
        hideTabs,
        desktop,
        mainContentStyle,
        currentLocale,
        localeOptions,

        // Sidebar Layout State
        layoutPreference,
        alwaysAskForLayout,
        sidebarCollapsed,
        sidebarWidth,
        pinnedMenuItems,
        recentlyVisited,

        // Desktop Methods
        setDesktopMode,
        toggleDesktopMode,
        toggleStartMenu,
        loadDesktopSettings,
        saveDesktopSettings,
        updateIconPosition,

        // Sidebar Layout Methods
        setLayoutPreference,
        setAlwaysAskForLayout,
        setSidebarCollapsed,
        togglePinnedMenuItem,
        addRecentlyVisited,
        saveSidebarSettings,
        loadSidebarSettings,
        saveLayoutPreference,
        loadLayoutPreference,
    }
});
