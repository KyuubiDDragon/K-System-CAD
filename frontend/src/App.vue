<template>
    <v-app class="app-container">
        <!-- Desktop-Modus -->
        <template v-if="isDesktopMode && !isDesktopWindow">
            <desktop-view>
                <!-- Desktop-Icons im Slot rendern -->
                <template #desktop-icons>
                    <!-- Dynamisch generierte Desktop-Icons hier integrieren -->
                </template>

                <!-- Fenster im Slot rendern -->
                <template #windows>
                    <app-window
                        v-for="window in windows"
                        :key="window.id"
                        :window="window"
                        :active="activeWindowId === window.id"
                        @close="closeWindow(window.id)"
                        @minimize="minimizeWindow(window.id)"
                        @maximize="maximizeWindow(window.id)"
                        @focus="focusWindow(window.id)"
                        @update-position="(x: number, y: number) => updateWindowPosition(window.id, x, y)"
                        @update-size="(width: number, height: number) => updateWindowSize(window.id, width, height)"
                    />
                </template>

                <!-- Taskbar-Items im Slot rendern -->
                <template #taskbar-items>
                    <!-- Taskleiste mit geöffneten Apps -->
                    <div
                        v-for="window in windows"
                        :key="window.id"
                        class="taskbar-item"
                        :class="{ active: activeWindowId === window.id }"
                        @click="focusWindow(window.id)"
                    >
                        <v-icon :icon="window.icon" size="small" class="mr-2"></v-icon>
                        <span class="taskbar-item-title">{{ window.title }}</span>
                    </div>
                </template>

                <!-- Start-Menü im Slot rendern -->
                <template #start-menu>
                    <!-- Start-Menü-Inhalte -->
                </template>
            </desktop-view>
        </template>

        <!-- Sidebar-Modus (wenn eingeloggt & nicht Desktop) -->
        <template v-else-if="!isDesktopMode && authStore.isLoggedIn && !isDesktopWindow">
            <sidebar-layout />
        </template>

        <!-- Fallback: Login oder App Window -->
        <template v-else>
            <div v-if="!isAppReady" class="app-loader">
                <v-progress-circular indeterminate color="primary" size="50"></v-progress-circular>
            </div>

            <!-- Main Content Area (für Login & App Windows) -->
            <v-main v-else :class="{ 'app-window-mode': $route.meta.isAppWindow }">
                <div
                    class="main-content"
                    :class="{ 'app-window-content': $route.meta.isAppWindow }"
                    :style="mainContentStyle"
                >
                    <router-view style="height: 100%; overflow: visible" />
                </div>
            </v-main>
        </template>

        <!-- Global Search -->
        <global-search
            v-model="showGlobalSearch"
            ref="globalSearchRef"
        />
    </v-app>
</template>

<script setup lang="ts">
/**
 * DEBUG MODE
 * ----------
 * This app supports a debug mode that can be enabled by adding ?debugMode=1 to the URL.
 * When debug mode is enabled, console.log statements will be displayed in the browser console.
 * When debug mode is disabled, console.log statements will be suppressed.
 *
 * The debug logger is implemented globally in plugins/debug-logger.js
 * and initialized in main.js, so it affects all files in the application.
 *
 * Usage:
 * - Enable debug mode: Add ?debugMode=1 to any URL
 * - Disable debug mode: Add ?debugMode=0 to the URL or remove the parameter
 * - Toggle debug mode in console: window.$debugLogger.toggle()
 * - Check debug status: window.$debugLogger.getStatus()
 */

// TypeScript-Deklarationen für globale Erweiterungen
declare global {
    interface Window {
        // Desktop-Fenster-Hilfsmethoden
        __desktopHelpers?: {
            closeWindow: () => void;
            minimizeWindow: () => void;
            maximizeWindow: () => void;
            focusWindow: () => void;
        };
        // Kompatibilitätsmethoden (alte API)
        closeDesktopWindow?: () => void;
        minimizeDesktopWindow?: () => void;
        maximizeDesktopWindow?: () => void;
        // Socket-Health-Intervall
        __socketHealthInterval?: NodeJS.Timeout;
        // Notification-Methode für native Benachrichtigungen
        addNotification?: (title: string, senderName: string) => void;
    }
}

// Import User type
import type { User } from '@/types/User';

import { ref, computed, watch, onMounted, onUnmounted, nextTick, defineAsyncComponent } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClientAuth } from '@/api';
const DesktopView = defineAsyncComponent(() => import('@/components/desktop/DesktopView.vue'));
const SidebarLayout = defineAsyncComponent(() => import('@/components/layout/SidebarLayout.vue'));
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';
import { useThemeStore } from '@/stores/theme';
import { storeToRefs } from 'pinia';
import { initializeSockets } from '@/plugins/socket';
import { useToast, POSITION } from 'vue-toastification';
import { useWindowsStore } from './stores/windows';
import AppWindow from './components/desktop/AppWindow.vue';
import { useI18n } from 'vue-i18n';
import GlobalSearch from './components/GlobalSearch.vue';
import { usePermissionCheck } from '@/composables/usePermissionCheck';

// Toast-Instanz initialisieren
const toast = useToast();

import type { GlobalSettings } from '@/types/Settings';

// Import stores
const authStore = useAuthStore();
const uiStore = useUIStore();
const themeStore = useThemeStore();
const windowsStore = useWindowsStore();

// Destructure store properties immediately with explicit typing
const { 
    isDesktopMode,
    isDesktopWindow,
    isAppReady,
    hideLeftNav,
    hideTopNav,
    hideTabs,
    desktop,
    mainContentStyle,
    currentLocale,
    localeOptions
} = storeToRefs(uiStore) as any;

const { windows, activeWindowId } = storeToRefs(windowsStore) as any;
const {
    closeWindow,
    minimizeWindow,
    maximizeWindow,
    focusWindow,
    updateWindowPosition,
    updateWindowSize,
} = windowsStore as any;

const route = useRoute();
const router = useRouter();
const $route = route as any;
const { locale, t } = useI18n() as any;

// Import vuetify instance
import vuetify from '@/plugins/vuetify';

// Global Search state
const showGlobalSearch = ref(false);
const globalSearchRef = ref<InstanceType<typeof GlobalSearch> | null>(null);

// Watch for locale changes
watch(currentLocale, (val: string) => {
    locale.value = val;
});

// Watch for i18n locale changes and sync with Vuetify
watch(locale, (newLocale: string) => {
    if (vuetify.locale) {
        vuetify.locale.current.value = newLocale;
    }
});

// UI Store für Desktop-Modus is now destructured from storeToRefs
const isDesktopView = computed(() => {
    // Checking if we're in desktop mode
    return typeof route.query.desktop === 'string' && route.query.desktop === 'true';
});
const shouldHideLeftNav = computed(() => {
    return (
        isDesktopView.value &&
        typeof route.query.hideLeftNav === 'string' &&
        route.query.hideLeftNav === 'true'
    );
});
const shouldHideTopIcons = computed(() => isDesktopView.value);
const shouldShowTabs = computed(() => {
    // Always show tabs if they exist and we're not explicitly hiding them
    if (!showTabs.value) return false;

    // In desktop mode, always show tabs unless hideTabs is explicitly set to 'true'
    if (isDesktopView.value) {
        return route.query.hideTabs !== 'true';
    }

    // In regular mode, show tabs if they exist
    return true;
});

const toggleDesktopMode = () => {
    console.log('Toggling desktop mode', uiStore.$state.isDesktopMode);

    // First clear any desktop related classes from the document
    document.documentElement.classList.remove('desktop-mode');
    document.documentElement.classList.remove('hide-left-nav');
    document.documentElement.classList.remove('hide-top-nav');

    // Verwende die Store-Methode statt direkter State-Manipulation
    // uiStore.$state.isDesktopMode = !uiStore.$state.isDesktopMode;
    uiStore.toggleDesktopMode();

    // Aktualisiere UI basierend auf dem aktuellen State
    if (uiStore.$state.isDesktopMode) {
        document.documentElement.classList.add('desktop-mode');
    } else {
        console.log('Desktop mode disabled');
    }
};

const miniVariant = ref(true); // Standardmäßig minimierte Sidebar
const fab = ref(false); // Speed dial state
const userStatus = ref('online'); // User status

// Theme-Werte aus dem Store verwenden
const isDarkTheme = computed(() => themeStore.isDark);
const toggleTheme = () => themeStore.toggleDarkMode();

const isAdminExpanded = ref(false);

const toggleAdminExpanded = () => {
    isAdminExpanded.value = !isAdminExpanded.value;
};

const toggleDrawer = () => {
    miniVariant.value = !miniVariant.value;
};

const drawer = ref(true);
// Global settings reference
const globalSettings = ref<GlobalSettings>({} as GlobalSettings);

interface Notification {
    title: string;
    senderName: string;
    visible: boolean;
    timestamp: string;
}

const notifications = ref<Notification[]>([]);
const activeTab = ref(0);
const socket = ref<WebSocket | null>(null);

const authority = computed(() => authStore.user?.authority ?? ''); // Authority für HTML class
const userPermissions = computed(() => authStore.user?.permissions ?? []);
const { hasPermission } = usePermissionCheck();

const userProfile = computed<User>(() => (authStore.user as User) ?? ({} as User));
const isLoggedIn = computed(() => authStore.isLoggedIn);
const { user } = storeToRefs(authStore);

const unreadMessagesCount = computed(() => authStore.user?.unreadMessagesCount || 0);
const todayEventsCount = computed(() => authStore.user?.todayEventsCount || 0);

// Definiere die Tabs für spezifische Basis-Pfade
const tabs: Record<string, Array<{ title: string; route: string }>> = {
    '/dispatch': [
        { title: t('tabs.dispatch'), route: '/dispatch' },
        { title: t('tabs.vehicle'), route: '/vehicle' },
        { title: t('tabs.crew'), route: '/crew' },
    ],
    '/employee': [
        { title: t('tabs.employee'), route: '/employee' },
        { title: t('tabs.vacation'), route: '/vacation' },
    ],
    '/report': [
        { title: t('tabs.report'), route: '/report' },
        { title: t('tabs.category'), route: '/reportcategory' },
        { title: t('tabs.template'), route: '/reporttemplate' },
        { title: t('tabs.code'), route: '/reportcode' },
        { title: t('tabs.additional'), route: '/reportadditional' },
        { title: t('tabs.status'), route: '/reportstatus' },
    ],
    '/blackboard/global': [
        { title: t('tabs.blackboard'), route: '/blackboard/global' },
        { title: t('tabs.document'), route: '/document/global' },
        { title: t('tabs.map'), route: '/map/global' },
    ],
    '/company': [
        { title: t('tabs.company'), route: '/company' },
        { title: t('tabs.companyType'), route: '/companytype' },
    ],
    '/todo': [
        { title: t('tabs.todo'), route: '/todo' },
        { title: t('tabs.calendar'), route: '/calendar' },
        { title: t('tabs.application'), route: '/application' },
    ],
    '/document': [
        { title: t('tabs.department'), route: '/department' },
        { title: t('tabs.document'), route: '/document' },
        { title: t('tabs.training'), route: '/training' },
        { title: t('tabs.administration'), route: '/administration' },
    ],
    '/trainingassign': [
        { title: t('tabs.overview'), route: '/trainingassign' },
        { title: t('tabs.generateTest'), route: '/test' },
    ],
    '/fireprotection': [
        { title: t('tabs.fireProtection'), route: '/fireprotection' },
        { title: t('tabs.cheatsheet'), route: '/cheatsheet' },
    ],
    // Tabs für "Akten" hinzugefügt
    '/person': [
        { title: t('tabs.person'), route: '/person' },
        { title: t('tabs.vehicle'), route: '/vehicleFile' },
        { title: t('tabs.apartment'), route: '/apartmentFile' },
    ],
    '/map': [{ title: t('tabs.map'), route: '/map' }],
    '/invoice': [
        { title: t('tabs.invoice'), route: '/invoice' },
        { title: t('tabs.invoiceItem'), route: '/invoiceitems' },
    ],
    '/filemanager': [{ title: t('tabs.fileManager'), route: '/filemanager' }],
};

const basePathMapping: Record<string, string> = {
    '/dispatch': '/dispatch',
    '/vehicle': '/dispatch',
    '/crew': '/dispatch',
    '/employee': '/employee',
    '/vacation': '/employee',
    '/report': '/report',
    '/reportcategory': '/report',
    '/reporttemplate': '/report',
    '/reportcode': '/report',
    '/reportadditional': '/report',
    '/reportstatus': '/report',
    '/blackboard/global': '/blackboard/global',
    '/document/global': '/blackboard/global',
    '/map/global': '/blackboard/global',
    '/company': '/company',
    '/companytype': '/company',
    '/todo': '/todo',
    '/calendar': '/todo',
    '/application': '/todo',
    '/department': '/document',
    '/document': '/document',
    '/training': '/document',
    '/administration': '/document',
    '/trainingassign': '/trainingassign',
    '/test': '/trainingassign',
    '/fireprotection': '/fireprotection',
    '/cheatsheet': '/fireprotection',
    '/person': '/person',
    '/vehicleFile': '/person',
    '/apartmentFile': '/person',
    '/map': '/map',
    '/invoice': '/invoice',
    '/invoiceitems': '/invoice',
    '/filemanager': '/filemanager',
    // Füge weitere Mappings hinzu
};

const basePath = computed(() => basePathMapping[route.path] || '');

const currentTabs = computed(() => tabs[basePath.value] || []);
// Berechne die Tabs basierend auf dem Basis-Pfad und filtere sie basierend auf Berechtigungen
const filteredTabs = computed(() => {
    return currentTabs.value
        .filter((tab: any) => canAccessRoute(tab.route))
        .map((tab: any) => {
            // Füge die aktuellen Query-Parameter zu jeder Tab-Route hinzu
            const query = { ...route.query };
            return {
                ...tab,
                route: { path: tab.route, query },
            };
        });
});
const showTabs = computed(() => filteredTabs.value.length > 0);

// Beobachte die Route und setze den aktiven Tab zurück
watch(
    () => route.path,
    (newPath: string) => {
        const tabIndex = filteredTabs.value.findIndex((tab: any) => tab.route === newPath);
        activeTab.value = tabIndex >= 0 ? tabIndex : 0;
    },
    { immediate: true }
);

const goToMessages = () => router.push('/message');
const goToCalendar = () => router.push('/calendar');

const isActiveNav = (basePathToCheck: string): boolean => {
    // Prüft, ob die aktuelle Route mit dem Basis-Pfad beginnt ODER
    // ob der berechnete Basis-Pfad der aktuellen Route dem zu prüfenden Pfad entspricht
    return route.path.startsWith(basePathToCheck) || basePath.value === basePathToCheck;
};
// isAppReady is now provided by the UI store

// isDesktopWindow is now provided by the UI store
// This computed logic should be moved to the store if needed

// Setup socket connection
let socketConnection: any = null;

// Tracking-Mechanismus für Benachrichtigungen
const notificationDebounce = {
    lastNotificationTime: 0,
    debounceTime: 250, // 250ms Debounce-Zeit
    processedNotifications: new Set(),
};

// Verbesserte Version der addNotification Funktion mit Vue Toastification
const addNotification = (
    title: string,
    senderName: string,
    message: string | null = null,
    type: 'info' | 'success' | 'warning' | 'error' = 'info',
    duration = 5000,
    options = {}
) => {
    console.log('🔰 Adding notification:', { title, senderName, message, type });

    // WICHTIG: Debug-Ausgabe für leere Nachrichten
    if (!message || message.trim() === '') {
        console.warn('⚠️ Warnung: Leere Nachricht in addNotification:', {
            title,
            senderName,
            message,
            type,
        });
    }

    // Generiere eindeutige ID für die Benachrichtigung
    const notificationId = `${title}:${senderName}:${Date.now()}`;

    // DEBOUNCE: Verhindern mehrfacher fast gleichzeitiger Aufrufe
    const now = Date.now();
    if (now - notificationDebounce.lastNotificationTime < notificationDebounce.debounceTime) {
        console.log(
            `⏱️ App.vue Benachrichtigung debounced (${now - notificationDebounce.lastNotificationTime}ms)`
        );
        return;
    }

    // Prüfen, ob diese spezifische Benachrichtigung bereits verarbeitet wurde
    if (notificationDebounce.processedNotifications.has(notificationId)) {
        console.log(`🚫 App.vue Doppelte Benachrichtigung verhindert: ${notificationId}`);
        return;
    }

    // Als verarbeitet markieren
    notificationDebounce.processedNotifications.add(notificationId);
    notificationDebounce.lastNotificationTime = now;

    // Nach einer gewissen Zeit aus dem Set entfernen
    setTimeout(() => {
        notificationDebounce.processedNotifications.delete(notificationId);
    }, 500);

    // DOM-basierte Erkennung verwenden (wie in socket.js)
    const isDesktopWindow =
        document.documentElement.classList.contains('desktop-window') ||
        (window !== window.parent &&
            new URLSearchParams(window.location.search).has('desktop_window'));

    // Korrigierte Logik: Benachrichtigungen sind grundsätzlich erlaubt,
    // außer wenn wir uns in einem Desktop-Fenster befinden
    const shouldShowNotification = !isDesktopWindow;

    if (!shouldShowNotification) {
        console.log('Notification skipped - not in active view');
        return;
    }

    // Stelle sicher, dass toast verfügbar ist
    if (!toast) {
        console.error('Toast nicht verfügbar in addNotification!');
        return;
    }

    // Konstruiere Nachrichtentext
    let displayText = '';

    // Wenn Nachricht vorhanden ist, verwende sie
    if (message && message.trim() !== '') {
        displayText = message;
    }
    // Sonst verwende Standardtext mit Sender
    else {
        displayText = `Neue Nachricht von ${senderName}`;
    }

    console.log(`🟢 Toast wird angezeigt mit Text: "${displayText}"`);

    // Verwende den richtigen Toast-Typ basierend auf dem Parameter
    const toastMethod = toast[type] || toast.info;

    // Zeige Toast-Nachricht an
    toastMethod(displayText, {
        timeout: duration,
        position: POSITION.BOTTOM_RIGHT,
    });
};

// Globale Funktion für Benachrichtigungen
if (typeof window !== 'undefined') {
    // Globale Funktion für Benachrichtigungen bereitstellen
    window.addNotification = addNotification;
}

// Function to check if we're in an iframe
const setupWindowInIframeSupport = () => {
    const isInIframe = window !== window.parent;

    // Füge globale Hilfsfunktionen für Desktop-Fenster hinzu,
    // die von jeder Komponente aufgerufen werden können
    window.__desktopHelpers = {
        // Funktion zum Schließen des aktuellen Fensters
        closeWindow: () => {
            if (isInIframe) {
                console.log('Window helper: Closing window');
                const frameName = window.name;
                const windowId = frameName.replace('desktop-frame-', '');
                window.parent.postMessage({ action: 'closeWindow', windowId }, '*');
            }
        },

        // Funktion zum Minimieren des aktuellen Fensters
        minimizeWindow: () => {
            if (isInIframe) {
                console.log('Window helper: Minimizing window');
                const frameName = window.name;
                const windowId = frameName.replace('desktop-frame-', '');
                window.parent.postMessage({ action: 'minimizeWindow', windowId }, '*');
            }
        },

        // Funktion zum Maximieren des aktuellen Fensters
        maximizeWindow: () => {
            if (isInIframe) {
                console.log('Window helper: Maximizing window');
                const frameName = window.name;
                const windowId = frameName.replace('desktop-frame-', '');
                window.parent.postMessage({ action: 'maximizeWindow', windowId }, '*');
            }
        },

        // Funktion zum Fokussieren des aktuellen Fensters
        focusWindow: () => {
            if (isInIframe) {
                console.log('Window helper: Focusing window');
                const frameName = window.name;
                const windowId = frameName.replace('desktop-frame-', '');
                window.parent.postMessage({ action: 'focusWindow', windowId }, '*');
            }
        },
    };

    // Wenn wir uns in einem iframe befinden und der desktop_window Parameter gesetzt ist
    if (
        isInIframe &&
        typeof route.query.desktop_window === 'string' &&
        route.query.desktop_window === 'true'
    ) {
        console.log('App running in desktop window iframe mode');

        // Kompatibilität mit alten Methoden
        window.closeDesktopWindow = window.__desktopHelpers.closeWindow;
        window.minimizeDesktopWindow = window.__desktopHelpers.minimizeWindow;
        window.maximizeDesktopWindow = window.__desktopHelpers.maximizeWindow;
    }
};

// Keyboard shortcuts handler
const handleKeyboardShortcuts = (event: KeyboardEvent) => {
    // Ctrl+K or Cmd+K for global search
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        showGlobalSearch.value = true;
    }
};

onMounted(async () => {
    try {
        // NOTE: Token cleanup moved to logout() in auth store
        // Tokens are needed for FiveM embedded browser compatibility
        // DO NOT delete tokens on app startup - they are required for authentication!
        
        // Add keyboard shortcuts listener
        window.addEventListener('keydown', handleKeyboardShortcuts);
        
        // Prüfen, ob wir uns in einem iframe befinden und entsprechend setup durchführen
        setupWindowInIframeSupport();

        // Theme sofort initialisieren, um Flackern beim Laden zu verhindern
        console.log('App.vue: Initializing theme');
        themeStore.initializeTheme();

        // Explizit globale Einstellungen laden (zusätzlich zur Initialisierung)
        console.log('App.vue: Loading global settings explicitly');
        await themeStore.loadGlobalSettings();
        
        // Initialize Vuetify locale from saved preference
        const savedLanguage = localStorage.getItem('userLanguage');
        if (savedLanguage && ['de', 'en'].includes(savedLanguage)) {
            locale.value = savedLanguage;
            if (vuetify.locale) {
                vuetify.locale.current.value = savedLanguage;
            }
        } else {
            // Sync Vuetify locale with i18n locale
            if (vuetify.locale) {
                vuetify.locale.current.value = locale.value;
            }
        }

        // Der Desktop-Modus wird jetzt ausschließlich über den watch()-Hook für authStore.isLoggedIn gesteuert
        // Dies löst das Timing-Problem mit 401-Fehlern bei frühen API-Anfragen

        // Socket-Verbindung im Hintergrund überwachen
        setTimeout(() => {
            try {
                import('@/plugins/socket')
                    .then(async ({ initializeSockets, getSocketStatus, reconnectSockets }) => {
                        // Regelmäßige Überprüfung einrichten - ohne Logging
                        window.__socketHealthInterval = setInterval(async () => {
                            const currentStatus = await getSocketStatus();

                            // Wenn die Socket-Verbindung existiert, aber keine Rooms hat
                            if (
                                currentStatus.connected &&
                                (!currentStatus.rooms ||
                                    !currentStatus.rooms.includes(`user:${authStore.user?.id}`))
                            ) {
                                // Versuche Sockets neu zu verbinden
                                await reconnectSockets();
                            }
                        }, 30000); // Alle 30 Sekunden prüfen
                    })
                    .catch(err => {
                        console.error('❌ Fehler beim Importieren der Socket-Module:', err);
                    });
            } catch (e) {
                console.error('❌ Fehler beim Socket-Debugging:', e);
            }
        }, 5000);

        // Add event listener for exit-desktop
        const handleExitDesktop = () => {
            if (uiStore.$state.isDesktopMode) {
                uiStore.$state.isDesktopMode = false;
                uiStore.$state.openWindows = [];
                uiStore.$state.startMenuOpen = false;
            }
        };

        document.addEventListener('exit-desktop', handleExitDesktop);

        // Store the handler function for cleanup
        (window as any).__exitDesktopHandler = handleExitDesktop;

        if (user.value?.authority) {
            setHtmlClass(user.value.authority);
        }

        // Desktop-Modus-Parameter verarbeiten
        function getDesktopParams() {
            const params = new URLSearchParams(window.location.search);
            return {
                isDesktopMode: params.get('desktop') === 'true',
                hideLeftNav: params.get('hideLeftNav') === 'true',
                hideTopNav: params.get('hideTopNav') === 'true',
            };
        }

        // Desktop-Modus-Klassen anwenden
        const desktopParams = getDesktopParams();

        // URL-Parameter für den Desktop-Modus werden jetzt auch nur dann angewendet,
        // wenn der Benutzer bereits authentifiziert ist
        if (desktopParams.isDesktopMode && authStore.isLoggedIn) {
            console.log('App.vue: Applying desktop mode from URL parameters');

            // Desktop-Klasse zum HTML-Element hinzufügen
            document.documentElement.classList.add('desktop-mode');

            if (desktopParams.hideLeftNav) {
                document.documentElement.classList.add('hide-left-nav');
            }

            // Only hide top nav if specifically requested
            if (desktopParams.hideTopNav) {
                document.documentElement.classList.add('hide-top-nav');
            }
        } else if (typeof route.query.desktop === 'string' && route.query.desktop === 'true') {
            // This is for windows opened from desktop
            console.log('App.vue: Applying desktop window mode');
            document.documentElement.classList.add('desktop-window');

            if (route.query.hideLeftNav === 'true') {
                document.documentElement.classList.add('hide-left-nav');
            }

            // Only hide top nav if specifically requested
            if (route.query.hideTopNav === 'true') {
                document.documentElement.classList.add('hide-top-nav');
            }
        }

        // Trigger Socket.io initialization
        console.log('📱 App.vue: Dispatching app-loaded event to initialize Socket.io');
        window.dispatchEvent(new Event('app-loaded'));

        // Initialize socket connection
        if (isLoggedIn.value) {
            socketConnection = initializeSockets();
        }
    } finally {
        // Ladebalken nach fester Zeit ausblenden, unabhängig von Erfolg oder Fehler
        setTimeout(() => {
            isAppReady.value = true;

            // Initialize Socket.io directly when app is ready
            try {
                // Import dynamically to avoid TypeScript errors
                import('@/plugins/socket')
                    .then(({ initializeSockets, disconnectSockets }) => {
                        // Store the disconnectSockets function for cleanup
                        socketConnection = { disconnect: disconnectSockets };

                        const sockets = initializeSockets();

                        // We no longer need to manually listen for socket events here
                        // since they are already handled in socket.js
                        console.log('Socket.io connection initialized');

                        // Add a listener only for monitoring socket status
                        if (sockets && sockets.main) {
                            sockets.main.on('connect', () => {
                                console.log('✅ App.vue: Socket connected');
                            });

                            sockets.main.on('disconnect', () => {
                                console.log('❌ App.vue: Socket disconnected');
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Error initializing sockets:', err);
                    });
            } catch (error) {
                console.error('Socket initialization error:', error);
            }
        }, 1500);
    }
});

onUnmounted(() => {
    // Remove keyboard shortcuts listener
    window.removeEventListener('keydown', handleKeyboardShortcuts);
    
    // Use the stored handler function to remove the event listener
    if ((window as any).__exitDesktopHandler) {
        document.removeEventListener('exit-desktop', (window as any).__exitDesktopHandler);
        delete (window as any).__exitDesktopHandler;
    }

    // Clean up socket connections
    if (socketConnection) {
        try {
            if (typeof socketConnection.disconnect === 'function') {
                socketConnection.disconnect();
            } else {
                import('@/plugins/socket').then(({ disconnectSockets }) => {
                    disconnectSockets();
                });
            }
        } catch (error) {
            console.error('Error closing socket connections:', error);
        }
    }
});

const fetchGlobalSettings = async () => {
    try {
        const response = await apiClientAuth.get('/settings/?action=getGlobalSettings');
        if (response.data) {
            return response.data;
        }
    } catch (error) {
        console.error('Error fetching global settings:', error);
    }
    return {};
};

const fetchSettings = async () => {
    const settings = await fetchGlobalSettings();
    globalSettings.value = settings;
};

// mainContentStyle is now provided by the UI store

// --- Berechtigungs-Helfer ---
const canAccessRoute = (path: string): boolean => {
    const targetRoute = router.getRoutes().find(r => r.path === path);
    if (!targetRoute?.meta) return true; // Allow if no meta defined

    const { requiredPermission, requiredSite } = targetRoute.meta;
    const currentAuthority = authority.value; // Use computed ref

    // Use the new permission check composable which handles both bitmask and legacy formats
    const hasRequiredPermission =
        !requiredPermission ||
        hasPermission(requiredPermission);

    const hasSiteAccess =
        !requiredSite || (!!currentAuthority && requiredSite.includes(currentAuthority));

    return hasRequiredPermission && hasSiteAccess;
};

const canAccessAnyRoute = (paths: string[]): boolean => {
    return paths.some(path => canAccessRoute(path));
};

const setHtmlClass = (newClass: string) => {
    const htmlElement = document.documentElement;

    // Clear all existing classes
    htmlElement.className = '';

    // Add the new class only if it's not empty
    if (newClass) {
        htmlElement.classList.add(newClass);
    }
};

// Beobachte Änderungen der `authority` und setze die Klasse entsprechend
watch(authority, newAuthority => {
    setHtmlClass(newAuthority);
});

const goToProfile = () => {
    router.push('/profile');
};

const editProfile = () => {
    router.push('/edit-profile');
};

const logout = async () => {
    try {
        await authStore.logout();
    } catch (error) {
        console.error('Error logging out:', error);
    }
};

// Hinzufügen der fehlenden setUserStatus Methode
const setUserStatus = (status: string) => {
    userStatus.value = status;
};
/**
 * Gets the current desktop mode query parameters from the URL
 */
function getDesktopQueryParams() {
    const query = route.query;
    const desktopParams = {};

    // Preserve desktop mode parameters
    if (typeof query.desktop === 'string') {
        desktopParams.desktop = query.desktop;
    }
    if (typeof query.hideLeftNav === 'string') {
        desktopParams.hideLeftNav = query.hideLeftNav;
    }
    if (typeof query.hideTopNav === 'string') {
        desktopParams.hideTopNav = query.hideTopNav;
    }

    return desktopParams;
}

/**
 * Creates a route object with the current desktop parameters preserved
 */
function getRouteWithDesktopParams(routePath) {
    // Get the desktop params
    const desktopParams = getDesktopQueryParams();

    // Explicitly set hideTabs to false to ensure tabs are always shown
    if (desktopParams.desktop === 'true') {
        desktopParams.hideTabs = 'false';
    }

    // If routePath is already an object with path and query, merge the queries
    if (typeof routePath === 'object' && routePath.path) {
        return {
            path: routePath.path,
            query: { ...desktopParams, ...routePath.query },
        };
    }

    // If routePath is a string, create a new route object
    return {
        path: routePath,
        query: desktopParams,
    };
}

// Stellt sicher, dass die Window-Helper auch in den globalen Kontext exportiert werden
if (typeof window !== 'undefined') {
    // Diese Funktionen werden zum globalen window-Objekt hinzugefügt,
    // sodass sie von überall aus aufgerufen werden können
    window.closeDesktopWindow = () => window.__desktopHelpers?.closeWindow?.();
    window.minimizeDesktopWindow = () => window.__desktopHelpers?.minimizeWindow?.();
    window.maximizeDesktopWindow = () => window.__desktopHelpers?.maximizeWindow?.();
}

// Beobachte den Auth-Status, um das Theme nach dem Login zu laden
watch(
    () => authStore.isLoggedIn,
    newValue => {
        // Wenn der Benutzer sich einloggt, Theme erneut laden und anwenden
        if (newValue) {
            console.log('App.vue: User logged in, reapplying theme');
            themeStore.initializeTheme();
        }
    },
    { immediate: true }
);

// Load desktop settings when switching to desktop mode
// Layout preference is now loaded in router guard (router/index.ts)
watch(
    () => uiStore.isDesktopMode,
    async (isDesktop) => {
        if (isDesktop && authStore.isLoggedIn) {
            // Load desktop settings when entering desktop mode
            if (typeof uiStore.loadDesktopSettings === 'function') {
                uiStore.loadDesktopSettings(() => {
                    console.log('App.vue: Desktop settings loaded successfully');
                });
            } else {
                // Fallback mit direktem API-Aufruf
                apiClientAuth
                    .get('/desktop/', {
                        params: {
                            action: 'getSettings',
                            _t: Date.now(),
                        },
                    })
                    .then(response => {
                        console.log('Desktop settings loaded via API:', response.data);
                        if (response?.data) {
                            // Icon-Positionen verarbeiten
                            if (response.data.icon_positions) {
                                try {
                                    const positions =
                                        typeof response.data.icon_positions === 'string'
                                            ? JSON.parse(response.data.icon_positions)
                                            : response.data.icon_positions;
                                    uiStore.$state.desktopIconPositions = positions;
                                } catch (e) {
                                    console.error('Failed to parse icon positions:', e);
                                }
                            }
                            // Theme verarbeiten
                            if (response.data.theme) {
                                uiStore.$state.desktopTheme = response.data.theme;
                                document.documentElement.setAttribute(
                                    'data-theme',
                                    response.data.theme
                                );
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error loading desktop settings via API:', err);
                    });
            }
        }
    }
);

// Beobachte, wann sich der isLoggedIn-Status ändert
watch(
    isLoggedIn,
    async (newValue, oldValue) => {
        if (newValue === true) {
            // Skip desktop mode routing if the current route has isAppWindow meta flag
            if (route.meta.isAppWindow) {
                console.log(
                    'App.vue: Route has isAppWindow flag, skipping desktop mode redirection'
                );
                isAppReady.value = true;
                return;
            }

            // Der Benutzer ist jetzt eingeloggt
            if (uiStore.isDesktopMode) {
                console.log('App.vue: User logged in, Desktop mode already enabled');
                isAppReady.value = true;
            } else {
                console.log('App.vue: User logged in');
                // Desktop mode is now checked and set in LoginView before navigation
                // No need to check again here to avoid dashboard flash
                isAppReady.value = true;
            }
        } else if (newValue === false && oldValue === true) {
            // Benutzer wurde ausgeloggt
            console.log('App.vue: User logged out, disabling desktop mode if active');
            if (uiStore.isDesktopMode) {
                uiStore.setDesktopMode(false);
            }
        }
    },
    { immediate: true }
);

// Store destructuring moved to top of script
</script>

<style lang="scss">
// App Container
.app-container {
    background-color: var(--background);
    color: var(--text);
}

// Navigation Drawer
.modern-drawer {
    width: var(--drawer-width) !important;
    background: linear-gradient(180deg, var(--surface) 0%, rgba(17, 24, 39, 0.95) 100%) !important;
    border-right: 1px solid var(--border);
    overflow: visible !important;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &.v-navigation-drawer--rail {
        width: var(--drawer-width-compact) !important;

        .drawer-header {
            justify-content: center;
            padding: 16px 0;
        }

        .nav-section-title {
            opacity: 0;
            height: 0;
            margin: 0;
            padding: 0;
        }

        .nav-list-item {
            .v-list-item__content {
                opacity: 0;
            }
        }
    }
}

.drawer-header {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 16px;
    transition: padding 0.3s ease;
}

.logo-container {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.logo-image {
    max-width: 32px;
    margin-right: 12px;
    transition: margin 0.2s ease;
}

.main-content {
    height: 100%;
}

.logo-text {
    font-size: 1.2rem;
    font-weight: 600;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    -webkit-background-clip: text;
    background-clip: text;
    white-space: nowrap;
}

.search-container {
    padding: 0 16px 16px 16px;
}

.search-field {
    border-radius: 20px;

    :deep(.v-field__input) {
        padding-top: 6px;
        padding-bottom: 6px;
        min-height: 36px;
    }
}

.drawer-divider {
    border-color: var(--border);
    margin: 0;
}

.drawer-scroll-area {
    height: calc(100% - 80px);
}

.drawer-list {
    padding: 8px;
}

.nav-list-item {
    border-radius: 8px;
    margin-bottom: 2px;
    transition: all 0.2s ease;

    &:hover {
        background-color: var(--k-row-hover);
    }

    &.v-list-item--active {
        background-color: rgba(59, 130, 246, 0.15);

        &::before {
            opacity: 0;
        }

        :deep(.v-list-item__content) {
            color: var(--primary-light);
        }

        :deep(.v-icon) {
            color: var(--primary-light);
        }
    }
}

.nav-list-group {
    &.v-list-group--active {
        > .v-list-group__header .v-list-item__icon {
            color: var(--primary-light);
        }
    }
}

.nested-nav-item {
    padding-left: 48px;
    min-height: 36px;
    font-size: 0.875rem;
    opacity: 0.85;

    &:hover {
        opacity: 1;
    }

    &.v-list-item--active {
        font-weight: 500;
    }
}

.nav-divider {
    margin: 8px 0;
    border-color: var(--border);
}

.nav-section-title {
    padding: 8px 16px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary-light);
    opacity: 0.7;
    transition: all 0.3s ease;
    text-transform: uppercase;
}

// App Bar
.modern-app-bar {
    background-color: rgba(17, 24, 39, 0.8) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
}

.toggle-icon {
    transition: transform 0.3s ease;

    &:hover {
        transform: rotate(90deg);
    }
}

.app-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.title-text {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    -webkit-background-clip: text;
    background-clip: text;
}

.app-tabs {
    height: 64px;

    .v-tab {
        text-transform: none;
        letter-spacing: normal;
        font-weight: 500;
        opacity: 0.7;
        min-width: 0;

        &.v-tab--selected {
            opacity: 1;
            font-weight: 600;
        }
    }
}

.app-bar-actions {
    display: flex;
    align-items: center;
}

.action-btn {
    margin: 0 4px;
    opacity: 0.7;
    transition: all 0.2s;

    &:hover {
        opacity: 1;
        transform: translateY(-2px);
    }
}

.toggle-theme-btn {
    animation: pulse 2s infinite;

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.2);
        }
        70% {
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
        }
    }
}

.action-divider {
    height: 24px;
    opacity: 0.2;
}

.user-profile-btn {
    text-transform: none;
    letter-spacing: normal;
    font-weight: normal;
    background-color: var(--k-row-hover);
    border-radius: 50px;
    padding: 0 16px 0 4px;
    height: 40px;
    position: relative;

    &:hover {
        background-color: var(--k-row-hover);

        .user-avatar {
            transform: scale(1.05);
        }
    }
}

.user-avatar {
    transition: transform 0.2s ease;
    border: 2px solid var(--primary);
}

.status-badge {
    position: absolute;
    bottom: 0;
    right: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
    margin-left: 8px;
    margin-right: 4px;
    min-width: 0;
}

.user-name {
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.user-role {
    font-size: 0.7rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.user-dropdown {
    background-color: var(--surface-light);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.user-dropdown-header {
    padding: 16px;
    display: flex;
    align-items: center;
}

.user-large-avatar {
    border: 2px solid var(--primary);
    margin-right: 16px;
    transition: transform 0.2s;

    &:hover {
        transform: scale(1.05);
    }
}

.user-dropdown-info {
    overflow: hidden;
}

.user-dropdown-name {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-dropdown-role {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.user-dropdown-email {
    font-size: 0.75rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-menu-list {
    background-color: transparent;
}

.logout-item {
    color: var(--error);
}

.status-selector {
    padding: 12px 16px;
}

.status-label {
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.status-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.status-option {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    background-color: var(--k-row-hover);
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background-color: var(--k-row-hover);
    }

    &.active {
        background-color: rgba(59, 130, 246, 0.15);
        color: var(--primary-light);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;

        &.online {
            background-color: var(--success);
        }

        &.away {
            background-color: var(--warning);
        }

        &.busy {
            background-color: var(--error);
        }

        &.offline {
            background-color: var(--text-secondary);
        }
    }
}

// Notification Dropdown
.notification-dropdown {
    background-color: var(--surface-light);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
}

.header-title {
    font-size: 0.9rem;
    font-weight: 600;
}

.notification-list {
    max-height: 320px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    position: relative;
    transition: background-color 0.2s;

    &:hover {
        background-color: var(--k-row-hover);

        .notification-close {
            opacity: 1;
        }
    }

    &:last-child {
        border-bottom: none;
    }
}

.notification-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;

    &.success {
        background-color: rgba(16, 185, 129, 0.15);
        color: var(--success);
    }

    &.warning {
        background-color: rgba(245, 158, 11, 0.15);
        color: var(--warning);
    }

    &.error {
        background-color: rgba(239, 68, 68, 0.15);
        color: var(--error);
    }

    &.info {
        background-color: rgba(59, 130, 246, 0.15);
        color: var(--info);
    }
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 4px;
}

.notification-desc {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.notification-time {
    font-size: 0.7rem;
    color: var(--text-secondary);
}

.notification-close {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.2s;
}

.notification-footer {
    padding: 8px;
}

// Quick Actions
.quick-actions {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 100;
}

.quick-action-btn {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    transition: transform 0.3s ease;

    &:hover {
        transform: rotate(45deg);
    }
}

.quick-action-item {
    margin-bottom: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transform: scale(0.85);
    transition: all 0.2s ease;

    &:hover {
        transform: scale(1);
    }
}

// Mobile Tabs
.mobile-tabs {
    margin: 8px;
    background-color: var(--surface) !important;
    border-radius: 8px;
    overflow: hidden;
}

// Main Content

#web-body {
    background: linear-gradient(135deg, #0a0e17 0%, #131b2c 100%);
    position: relative;
}

#web-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image:
        radial-gradient(rgba(255, 255, 255, 0.05) 2px, transparent 2px),
        radial-gradient(rgba(255, 255, 255, 0.05) 2px, transparent 2px);
    background-size: 50px 50px;
    background-position:
        0 0,
        25px 25px;
    opacity: 0.6;
}

@keyframes wave {
    0% {
        background-position-x: 0%;
    }
    100% {
        background-position-x: 100%;
    }
}

// Snackbar
.modern-snackbar {
    .v-snackbar__wrapper {
        background-color: var(--surface-light);
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    .v-snackbar__content {
        padding: 16px;
    }
}

.snackbar-content {
    display: flex;
    flex-direction: column;
}

.snackbar-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.875rem;
}

.snackbar-details {
    padding-left: 26px;
    font-size: 0.8125rem;
}

.snackbar-sender,
.snackbar-title {
    margin-bottom: 2px;
}

// CKEditor styles
.ck-content pre {
    color: var(--k-ink);
    direction: ltr;
    font-style: normal;
    -moz-tab-size: 4;
    tab-size: 4;
    text-align: left;
    white-space: pre-wrap;
}

.language-plaintext {
    color: var(--k-ink);
}

// Responsive adjustments
@media (max-width: 960px) {
    .modern-drawer {
        z-index: 1000;
    }
}

.app-loader {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--background);
    z-index: 9999;
}

/* Fügen Sie dies zu Ihrem Style-Block hinzu */
.modern-drawer.v-navigation-drawer--rail .nav-list-group :deep(.v-list-group__items) {
    /* Verstecke den Text, zeige nur Icons */
    padding-left: 0 !important;
}

.modern-drawer.v-navigation-drawer--rail .nav-list-group :deep(.v-list-group__items .v-list-item) {
    /* Zentriere die Icons */
    justify-content: center;
    padding-left: 0 !important;
}

.modern-drawer.v-navigation-drawer--rail
    .nav-list-group
    :deep(.v-list-group__items .v-list-item__content) {
    /* Verstecke den Text vollständig */
    display: none;
}

.modern-drawer.v-navigation-drawer--rail
    .nav-list-group
    :deep(.v-list-group__items .v-list-item .v-list-item__prepend) {
    /* Stelle sicher, dass Icons angezeigt werden */
    display: flex !important;
    margin-right: 0 !important;
}

/* Spezielles Styling für eingeklappte Navigationsleiste */
.mini-list-group .v-list-group__items {
    position: relative !important;
    padding-left: 0 !important;
}

.mini-nav-item {
    justify-content: center !important;
    min-width: auto !important;
    padding: 6px 0 !important;
    color: #478df7;
}

.mini-nav-item > i {
    color: #478df7;
}

.mini-nav-item .v-list-item__content {
    display: none !important;
}

.mini-nav-item .v-list-item__prepend {
    margin-right: 0 !important;
}

/* Fixes für Vuetify 3 Rail-Modus */
.v-navigation-drawer--rail .v-list-group--rail .v-list-group__items {
    display: flex !important;
    flex-direction: column !important;
    visibility: visible !important;
    position: static !important;
    padding-left: 0 !important;
}

.v-navigation-drawer--rail .v-list-group--rail .v-list-item__content {
    display: none !important;
}

.v-navigation-drawer--rail .v-list-group--rail .v-list-item {
    justify-content: center !important;
}

.v-navigation-drawer--rail .v-list-group--rail .v-list-item .v-icon {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Styling für eingeklappte Untermenü-Icons */
.v-navigation-drawer--rail .v-list-group .v-list-group__items {
    position: relative !important;
    padding-left: 0 !important;
    visibility: visible !important;
}

.v-navigation-drawer--rail .v-list-group .v-list-group__items .v-list-item {
    padding-left: 30px !important; /* Leichte Einrückung */
    margin-top: 4px !important; /* Abstand zwischen Icons */
}

.v-navigation-drawer--rail .v-list-group .v-list-group__items .v-list-item .v-list-item__content {
    display: none !important;
}

.v-navigation-drawer--rail .v-list-group .v-list-group__items .v-list-item .v-icon {
    font-size: 18px !important; /* Etwas kleinere Icons */
    opacity: 0.85 !important; /* Leicht transparenter */
}

/* Indikator für Submenu */
.v-navigation-drawer--rail .v-list-group .v-list-group__items .v-list-item::before {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 30px;
    width: 3px;
    background-color: var(--primary);
    opacity: 0.5;
    border-radius: 0 2px 2px 0;
}

/* Hover-Effekt für Submenu-Items */
.v-navigation-drawer--rail .v-list-group .v-list-group__items .v-list-item:hover::before {
    opacity: 1;
}

/* Aktiver Zustand für Submenu-Items */
.v-navigation-drawer--rail
    .v-list-group
    .v-list-group__items
    .v-list-item.v-list-item--active::before {
    opacity: 1;
    height: 24px;
}

/* Desktop Window Styles */
.desktop-window-content {
    padding: 0 !important;
    margin: 0 !important;
    height: 100% !important;
    --v-layout-top: 0 !important;
    overflow: auto !important; /* Ensure content is scrollable */
}

.desktop-content {
    padding: 0 !important;
    margin: 0 !important;
    height: 100% !important;
}

/* Customized router view for desktop iframes */
.desktop-content .v-main {
    background: none !important;
}

.desktop-content:before {
    display: none !important;
}

/* Toast-Benachrichtigungen Styling */
/* Diese :root Variablen werden für die Toast-Komponente benötigt */
:root {
    --toastification-color-info: var(--primary) !important;
    --toastification-color-success: var(--success) !important;
    --toastification-color-warning: var(--warning) !important;
    --toastification-color-error: var(--error) !important;
}

/* Toast-Container */
.Vue-Toastification__container {
    z-index: 9999;
}

/* Toast-Element */
.Vue-Toastification__toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    padding: 16px !important;
    min-height: auto !important;
}

/* Anpassungen für die benutzerdefinierten Toast-Inhalte */
.toast-custom {
    font-family: inherit;
}

.toast-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 1rem;
}

.toast-body {
    display: flex;
    flex-direction: column;
    padding-left: 28px;
}

.toast-body .sender {
    font-size: 0.875rem;
    margin-bottom: 4px;
}

.toast-body .timestamp {
    font-size: 0.75rem;
    opacity: 0.7;
}

/* Dunkel-Modus Anpassungen */
.v-theme--dark .Vue-Toastification__toast {
    background-color: var(--surface-light) !important;
    color: var(--text) !important;
    border: 1px solid var(--k-line);
}

.v-theme--dark .Vue-Toastification__close-button {
    color: var(--k-ink-muted) !important;
}

.v-theme--dark .Vue-Toastification__progress-bar {
    background-color: var(--k-line-strong) !important;
}

/* Debug Overlays ausblenden */
* {
    --debug-display: none !important;
}

body::before,
body::after,
.window::before,
.window::after,
.v-application::before,
.v-application::after,
.v-overlay__content::before,
.v-overlay__content::after,
iframe::before,
iframe::after,
.app-container::before,
.app-container::after,
.window-content::before,
.window-content::after,
.window-iframe::before,
.window-iframe::after {
    content: none !important;
    display: none !important;
}

/* Debug Info ausblenden */
.debug-overlay,
.debug-info,
.size-info,
.window-size,
.debug-size,
.size-display,
.debug-container,
.debug-element {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}

/* App window mode */
.v-main {
    &:has(+ .app-window-mode) {
        padding: 0 !important;
        margin: 0 !important;
    }
}

/* When isAppWindow is true, ensure content has no padding */
.app-window-content {
    padding: 0 !important;
    margin: 0 !important;
    height: 100% !important;
    width: 100% !important;
}

/* Override for app-window specific styling */
.app-window-mode .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 0 !important;
}

@media (max-width: 960px) {
    .modern-drawer {
        z-index: 1000;
    }
}
</style>
