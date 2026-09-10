<!-- Desktop View Component -->
<template>
    <div class="app-container" :class="{ 'desktop-mode': isDesktopMode }">
        <!-- Windows-Style Loading Screen -->
        <desktop-loading-screen
            v-if="isLoading || isLoadingDesktopSettings || isResettingLayout"
            :loading-title="loadingScreenTitle"
            :loading-message="loadingScreenMessage"
            :progress-text="loadingProgressText"
        />

        <div
            v-show="!isLoading && !isLoadingDesktopSettings && !isResettingLayout"
            class="desktop-container"
            :style="backgroundStyle"
            @contextmenu.self.prevent="openDesktopMenu"
        >
            <!-- Desktop Background -->
            <div class="desktop-background" @contextmenu.self.prevent="openDesktopMenu">
                <!-- Desktop-Hintergrund mit Overlay -->
                <div class="desktop-background-image" :style="backgroundStyle"></div>
                <div class="desktop-background-overlay"></div>

                <!-- Desktop Icons -->
                <div class="desktop-icons" @contextmenu.self.prevent="openDesktopMenu">
                    <!-- Desktop-Icons immer anzeigen -->
                    <desktop-icon
                        v-for="app in desktopApps"
                        :key="app.id"
                        :icon="{ icon: app.icon }"
                        :title="app.title"
                        :color="app.color"
                        :is-folder="app.isGroup"
                        :selected="selectedIcon === app.id"
                        :position="getIconPosition(app.id)"
                        @click="handleIconClick(app)"
                        @position-change="pos => updateIconPosition(app.id, pos)"
                        @select="selectIcon(app.id)"
                    />
                </div>

                <!-- Background Selector -->
                <background-selector @background-change="changeBackground" />

                <!-- Desktop Widgets -->
                <div class="desktop-widgets">
                    
                    <!-- Weather Widget -->
                    <weather-widget
                        v-if="activeWidgets.includes('weather')"
                        :position="widgetPositions.weather"
                        @update:position="updateWidgetPosition('weather', $event)"
                        @close="removeWidget('weather')"
                        @focus="focusWidget('weather')"
                    />

                    <!-- Calendar Widget -->
                    <calendar-widget
                        v-if="activeWidgets.includes('calendar')"
                        :position="widgetPositions.calendar"
                        @update:position="updateWidgetPosition('calendar', $event)"
                        @close="removeWidget('calendar')"
                        @focus="focusWidget('calendar')"
                    />

                    <!-- Notes Widget -->
                    <notes-widget
                        v-if="activeWidgets.includes('notes')"
                        :position="widgetPositions.notes"
                        @update:position="updateWidgetPosition('notes', $event)"
                        @close="removeWidget('notes')"
                        @focus="focusWidget('notes')"
                    />

                    <!-- Legacy Note Widget (always show if new notes widget not active) -->
                    <note-widget
                        v-if="!activeWidgets.includes('notes')"
                        :note="activeNote"
                        :note-index="activeNoteIndex"
                        :total-notes="notes.length"
                        :has-prev-note="activeNoteIndex > 0"
                        :has-next-note="activeNoteIndex < notes.length - 1"
                        @saved="handleNoteSaved"
                        @deleted="handleNoteDeleted"
                        @update:note="activeNote = $event"
                        @new-note="handleNewNote"
                        @prev-note="handlePrevNote"
                        @next-note="handleNextNote"
                    />
                </div>

                <!--
                    Kontextmenue der Arbeitsflaeche.

                    "Icons sortieren" und "Layout zuruecksetzen" schwebten
                    bisher dauerhaft unten rechts. Der Entwurf ist hier
                    deutlich: sie "gehoeren ins Kontextmenue der
                    Arbeitsflaeche - gebraucht werden sie selten". Zwei
                    Schaltflaechen, die staendig ueber dem Bild liegen, kosten
                    jeden Tag Platz fuer etwas, das man im Monat einmal tut.
                -->
                <!--
                    Kontextmenue der Arbeitsflaeche.

                    Eigen gebaut statt v-menu: Vuetifys Menue braucht einen
                    Ausloeser oder einen Zielpunkt, und mit einem reinen
                    Koordinatenziel oeffnete es zwar, rendert seinen Inhalt
                    aber nicht. Hier genuegt eine Liste an der Klickstelle -
                    das ist weniger Technik und tut genau das, was es soll.
                -->
                <Teleport to="body">
                    <div
                        v-if="desktopMenuOpen"
                        class="desk-menu-scrim"
                        @click="desktopMenuOpen = false"
                        @contextmenu.prevent="desktopMenuOpen = false"
                    ></div>
                    <div
                        v-if="desktopMenuOpen"
                        class="desk-menu"
                        :style="{ left: desktopMenuAt[0] + 'px', top: desktopMenuAt[1] + 'px' }"
                    >
                        <p class="desk-menu__label">{{ t('desktop.sortIcons') }}</p>
                        <button
                            v-for="mode in ['priority', 'alphabetical', 'category']"
                            :key="mode"
                            type="button"
                            class="desk-menu__item"
                            :class="{ 'is-on': iconSortMode === mode }"
                            @click="chooseSortMode(mode)"
                        >
                            {{ t(sortModeLabels[mode]) }}
                        </button>
                        <hr class="desk-menu__rule" />
                        <button type="button" class="desk-menu__item" @click="resetDesktopLayout">
                            {{ t('desktop.resetLayout') }}
                        </button>
                    </div>
                </Teleport>

                <!-- Folder Popup -->
                <div v-if="activeFolder" class="folder-popup" :style="folderPopupStyle">
                    <div class="folder-popup-header">
                        <v-icon
                            :icon="activeFolder.icon"
                            :color="activeFolder.color"
                            size="18"
                            class="mr-2"
                        ></v-icon>
                        <span>{{ activeFolder.title }}</span>
                        <v-spacer></v-spacer>
                        <v-btn icon size="small" @click="activeFolder = null">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                    <div class="folder-items">
                        <div
                            v-for="item in folderItems"
                            :key="item.id"
                            class="folder-item"
                            @click="openApp(item)"
                        >
                            <v-icon
                                :icon="item.icon"
                                :color="item.color"
                                size="20"
                                class="mr-2"
                            ></v-icon>
                            <span>{{ item.title }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Windows/Applications -->
            <div class="desktop-windows">
                <app-window
                    v-for="window in openWindows"
                    :key="window.id"
                    :window="window"
                    :active="activeWindowId === window.id"
                    :all-apps="allApps"
                    @close="closeWindow(window.id)"
                    @minimize="minimizeWindow(window.id)"
                    @maximize="handleMaximizeRequest(window.id)"
                    @restore="handleRestoreRequest"
                    @focus="focusWindow(window.id)"
                    @update-position="handleUpdatePosition"
                    @update-size="handleUpdateSize" 
                />
            </div>

            <!-- Taskbar -->
            <desktop-taskbar
                :open-apps="openWindows"
                :current-time="currentTime"
                :weather-data="weather"
                :all-apps="allApps"
                @toggle-start-menu="toggleStartMenu"
                @toggle-search="toggleGlobalSearch"
                @window-click="focusWindow"
                @open-app="openApp"
            />

            <!-- Start Menu (conditionally rendered) -->
            <start-menu
                v-if="startMenuOpen"
                :apps="allApps"
                @app-click="openApp"
                @close="closeStartMenu"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, reactive, provide } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import DesktopIcon from './DesktopIcon.vue';
import AppWindow from './AppWindow.vue';
import DesktopTaskbar from './DesktopTaskbar.vue';
import StartMenu from './StartMenu.vue';
import type { App, DesktopSettings, WidgetConfig } from '@/types/Desktop';
import type { DesktopViewContext } from '@/types/Desktop';
import DesktopWidget from './DesktopWidget.vue';
import { jwtDecode } from "jwt-decode";
import { useUIStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import WaterDuck from './WaterDuck.vue';
import NoteWidget from './NoteWidget.vue';
import BackgroundSelector from './BackgroundSelector.vue';
import Draggable from 'vuedraggable';
import { useToast } from 'vue-toastification'; // Import für Toast-Benachrichtigungen
import { apiClientAuth, desktopApi } from '@/api';
import { debounce } from 'lodash-es';
import type { DocArea } from '@/types';
import { useModulePermission } from '@/composables/useModulePermission';
import { convertLegacyToModule } from '@/utils/permissionMapping';
import WeatherWidget from './widgets/WeatherWidget.vue';
import CalendarWidget from './widgets/CalendarWidget.vue';
import NotesWidget from './widgets/NotesWidget.vue';
import DesktopLoadingScreen from './DesktopLoadingScreen.vue';
// Toast-Referenz
const toast = useToast();

// Store reference
const uiStore = useUIStore();
const authStore = useAuthStore();
const router = useRouter();
const isDesktopMode = computed(() => uiStore.isDesktopMode);
const { t } = useI18n();
const startMenuOpen = computed(() => uiStore.startMenuOpen);
const desktopTheme = computed(() => uiStore.desktopTheme);
const isLoadingDesktopSettings = computed(() => uiStore.isLoadingDesktopSettings);

// Ladestatus für lokale Operationen
const isLoading = ref(true);
// Check if we're coming from a reset/reload
const wasResetting = sessionStorage.getItem('desktop-resetting') === 'true';
const isResettingLayout = ref(wasResetting);

// Loading screen text
const loadingScreenTitle = computed(() => {
    if (isResettingLayout.value) {
        return t('desktop.resettingLayout');
    }
    return 'K-Systems';
});

const loadingScreenMessage = computed(() => {
    if (isResettingLayout.value) {
        return t('desktop.rearrangingIcons');
    }
    return t('desktop.welcomeMessage');
});

const loadingProgressText = computed(() => {
    if (isResettingLayout.value) {
        return t('desktop.pleaseWaitReset');
    }
    if (isLoading.value || isLoadingDesktopSettings.value) {
        return t('desktop.loadingDesktop');
    }
    return '';
});

// Define interfaces
interface App {
    id: string;
    title: string;
    icon: string;
    route?: string;
    color: string;
    action?: string;
    parent?: string;
    isGroup?: boolean;
    isDesktopApp?: boolean;
    hideOnDesktop?: boolean;
}

interface AppWindow {
    id: string;
    appId: string;
    title: string;
    icon: string;
    route?: string;
    x: number;
    y: number;
    width: number;
    height: number;
    zIndex: number;
    minimized: boolean;
    maximized: boolean;
    isDesktopApp?: boolean;
}

// State
const currentTime = ref(new Date());
const openWindows = ref<AppWindow[]>([]);
const lastZIndex = ref(1000);

// ✅ v2.2: Provide DesktopView context for shortcuts navigation
// This enables useResourceNavigation to open shortcuts as Desktop windows
provide<DesktopViewContext>('desktopView', {
  openWindows,
  lastZIndex,
  focusWindow: (windowId: string) => focusWindow(windowId),
});

const activeFolder = ref<App | null>(null);
const folderPosition = ref({ x: 0, y: 0 });
const desktopBackgroundImage = ref('/img/bg.jpg');
const selectedIcon = ref('');
const activeWindowId = ref('');
const iconPositions = computed(() => uiStore.desktopIconPositions || {});

// State for notes
const notes = ref([]);
const loadingNotes = ref(false);
const activeNote = ref({
    id: null,
    content: '',
    color: '#fbbf24',
    position: { x: 0, y: 0 },
    created_at: null,
    updated_at: null,
});
const activeNoteIndex = ref(0);
const showNoteWidget = ref(true);

// Zustand für Dokumentenbereiche nach der Definition von loadingNotes, activeNoteIndex, etc.
const documentAreas = ref<DocArea[]>([]);
const loadingDocAreas = ref(false);

// Zustand für Blackboard-Bereiche
interface BlackboardArea {
  id: number;
  key: string;
  name: string;
  icon: string;
  is_active: boolean;
  permissions?: {
    can_read: boolean;
    can_write: boolean;
    can_delete: boolean;
  };
}
const blackboardAreas = ref<BlackboardArea[]>([]);
const loadingBlackboardAreas = ref(false);

// Widget management state
const activeWidgets = ref<string[]>([]);
// Calculate smart widget positions based on screen size (right-aligned)
const calculateSmartWidgetPositions = () => {
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const rightPadding = 20; // Distance from right screen edge
    const topOffset = 80; // Space for taskbar
    const defaultWidgetWidth = 280;
    
    // Position widgets close to right edge (20px from right)
    const widgetStartX = rightPadding;
    
    // Stack widgets vertically with some spacing
    return {
        weather: { 
            x: widgetStartX,
            y: topOffset, 
            width: defaultWidgetWidth, 
            height: 400 
        },
        calendar: { 
            x: widgetStartX,
            y: topOffset + 420, // Below weather widget
            width: defaultWidgetWidth, 
            height: 350 
        },
        notes: {
            x: widgetStartX,
            y: topOffset + 800, // Below calendar widget  
            width: defaultWidgetWidth,
            height: 300
        }
    };
};

const widgetPositions = ref<Record<string, any>>(calculateSmartWidgetPositions());
const focusedWidget = ref<string | null>(null);
const MAX_WIDGETS = 4;

// Definieren der Desktop-Notification-Funktionalität
// Wenn eine Funktion addNotification global existiert, überschreiben wir sie nicht
if (typeof window !== 'undefined') {
    // Erweitere den Window-Typ für TypeScript
    declare global {
        interface Window {
            __originalAddNotification?: (title: string, senderName: string) => void;
            addNotification?: (title: string, senderName: string) => void;
            __desktopNotificationsHandled?: boolean;
            addEventListener(
                type: string,
                listener: EventListenerOrEventListenerObject,
                options?: boolean | AddEventListenerOptions
            ): void;
            removeEventListener(
                type: string,
                listener: EventListenerOrEventListenerObject,
                options?: boolean | EventListenerOptions
            ): void;
        }
    }
}

// Verschiebe die Definition von handleSocketNotification nach oben, außerhalb des Blocks
// Socket-Benachrichtigungen abfangen
const handleSocketNotification = (event: CustomEvent) => {
    if (!event.detail || !event.detail.type) return;

    // Nur für Toast-Benachrichtigungen
    if (event.detail.type === 'toast' && event.detail.data) {
        const isDesktopMode = document.documentElement.classList.contains('desktop-mode');
        const isDesktopWindow =
            document.documentElement.classList.contains('desktop-window');

        console.log('Desktop received socket notification:', {
            title: event.detail.data.title,
            isDesktopMode,
            isDesktopWindow,
        });
    }
};

// Hier startet die bestehende Funktion
const initializeDesktopNotifications = () => {
    // Wenn bereits im Desktop-Modus
    if (document.documentElement.classList.contains('desktop-mode')) {
        // Desktop-Modus spezifisches Setup
        console.log('Desktop mode detected, applying specific styles.');
        document.documentElement.style.overflow = 'hidden';
    } else {
        // Nicht-Desktop-Modus
        console.log('Non-desktop mode.');
        if (document.documentElement.style.overflow === 'hidden') {
            document.documentElement.style.overflow = '';
        }
    }

    // Vermeiden von doppelter Initialisierung
    if (!window.__desktopNotificationsHandled) {
        window.__desktopNotificationsHandled = true;
        console.log('Desktop notification handler initialized');

        // Event-Listener für Socket-Benachrichtigungen hinzufügen
        window.addEventListener(
            'socket:notification-received',
            handleSocketNotification as EventListener
        );

        // Globale addNotification-Funktion überschreiben nur wenn noch nicht geschehen
        if (!window.__originalAddNotification && window.addNotification) {
            // Speichere die Original-Funktion
            window.__originalAddNotification = window.addNotification;

            // Überschreibe die globale Benachrichtigungsfunktion
            window.addNotification = function (title, senderName) {
                // Prüfe ob wir im Desktop-Modus sind
                const isDesktopMode = document.documentElement.classList.contains('desktop-mode');
                const isDesktopWindow =
                    document.documentElement.classList.contains('desktop-window');

                // Logik für die Anzeige von Benachrichtigungen - NICHT die Original-Funktion aufrufen
                // sondern die Prüflogik in socket.js arbeiten lassen
                if (isDesktopMode && !isDesktopWindow) {
                    // Desktop-spezifische Benachrichtigung
                    console.log('Desktop notification will be shown directly:', {
                        title,
                        senderName,
                    });

                    // Rufe die Original-Funktion auf, aber deaktiviere die Verdopplung
                    if (window.__originalAddNotification) {
                        window.__originalAddNotification(title, senderName);
                    }
                } else if (!isDesktopMode && !isDesktopWindow) {
                    // In der normalen App-Ansicht
                    console.log('Normal app notification will be shown directly:', {
                        title,
                        senderName,
                    });

                    if (window.__originalAddNotification) {
                        window.__originalAddNotification(title, senderName);
                    }
                } else if (isDesktopWindow) {
                    // In Desktop-Fenstern
                    console.log('Window notification will be shown directly:', {
                        title,
                        senderName,
                    });

                    if (window.__originalAddNotification) {
                        window.__originalAddNotification(title, senderName);
                    }
                } else {
                    console.log('Notification skipped - not in active view');
                }
            };
        }
    } else {
        console.log('Desktop notification handler already initialized, skipping duplicate setup');
    }
};

// Timer for clock
let clockTimer: number | null = null;

// Handle open-desktop-app events from widgets
const handleOpenDesktopApp = (event: CustomEvent) => {
    if (event.detail) {
        openApp(event.detail);
    }
};

// Initialize components
onMounted(async () => {
    console.log('Desktop View mounted');

    // Start the clock
    updateTime();
    clockTimer = window.setInterval(updateTime, 1000);
    
    // Initialisiere Desktop-Benachrichtigungen
    if (typeof window !== 'undefined') {
        initializeDesktopNotifications();
    }
    
    // Add event listener for open-desktop-app events
    window.addEventListener('open-desktop-app', handleOpenDesktopApp as EventListener);

    // Fetch desktop data
    await fetchDesktopData();
    
    // Dokumentenbereiche laden
    await fetchDocumentAreas();
    await fetchBlackboardAreas();

    // Load widget state
    await loadWidgetState();
    
    // Listen for window resize
    window.addEventListener('resize', handleWindowResize);

    /*
       Einmaliges Nachruecken ins engere Raster.

       Die Schrittweite lag frueher bei 130 x 150 px, jetzt bei 100 x 116. Wer
       schon einmal hier war, traegt die alten, weiten Positionen im Store -
       neue Maße allein ruecken sie nicht zusammen. Deshalb wird die Anordnung
       genau einmal neu berechnet und das im Browser vermerkt, damit es beim
       naechsten Besuch nicht wieder passiert.

       Eine von Hand gelegte Anordnung geht damit verloren; das ist der Preis
       dafuer, die alte weite Aufteilung nicht ewig mitzuschleppen.
    */
    const RASTER_STAND = 'k-desktop-raster-3';
    try {
        if (localStorage.getItem(RASTER_STAND) !== '1') {
            localStorage.setItem(RASTER_STAND, '1');
            await forceGenerateIcons();
        }
    } catch {
        /* Speicher gesperrt - dann bleibt die Anordnung, wie sie ist. */
    }
});

// Handle window resize to adjust widget positions
const handleWindowResize = debounce(() => {
    console.log('📐 Window resized, recalculating widget positions');
    
    // Recalculate and fix all widget positions
    const currentPositions = { ...widgetPositions.value };
    widgetPositions.value = fixWidgetPositions(currentPositions);
    
    // Save updated positions
    saveWidgetState();
}, 300);

// Clean up interval on unmount
onUnmounted(() => {
    // Remove resize listener
    window.removeEventListener('resize', handleWindowResize);
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
    
    // Also clean up notification listeners
    window.removeEventListener(
        'socket:notification-received',
        handleSocketNotification as EventListener
    );
    
    // Clean up open-desktop-app listener
    window.removeEventListener(
        'open-desktop-app',
        handleOpenDesktopApp as EventListener
    );
});

const userPermissions = computed(() => authStore.user?.permissions ?? []);
const authority = computed(() => authStore.user?.authority ?? ''); // Authority für HTML class
const userId = computed(() => authStore.user?.id || null);

// Weather & Statistics state
const weather = ref({
    temperature: 22,
    condition: 'Teilweise bewölkt',
    location: 'Los Santos',
    icon: null,
});

const statistics = ref({
    openReports: 0,
    upcomingVacations: 3,
    todoItems: 5,
});

// Function to get the appropriate weather icon
const getWeatherIcon = (condition: string): string => {
    if (!condition) return 'mdi-weather-partly-cloudy';

    // Wenn die Bedingung bereits ein Icon-Name ist (mdi-*), direkt verwenden
    if (condition.startsWith('mdi-')) {
        return condition;
    }

    // Ansonsten aus dem Text ableiten
    const conditionLower = condition.toLowerCase();

    // Check for special warnings first
    if (conditionLower.includes('wildfire')) return 'mdi-fire-alert';
    if (conditionLower.includes('stark') && conditionLower.includes('regen'))
        return 'mdi-weather-alert';

    // Then check for specific weather conditions
    if (conditionLower.includes('regen') && conditionLower.includes('gewitter'))
        return 'mdi-weather-lightning-rainy';
    if (conditionLower.includes('regen') && conditionLower.includes('schnee'))
        return 'mdi-weather-snowy-rainy';
    if (conditionLower.includes('gewitter')) return 'mdi-weather-lightning';
    if (conditionLower.includes('regen')) return 'mdi-weather-rainy';
    if (conditionLower.includes('schnee')) return 'mdi-weather-snowy';
    if (conditionLower.includes('nebel')) return 'mdi-weather-fog';
    if (conditionLower.includes('wind')) return 'mdi-weather-windy';
    if (conditionLower.includes('klar') || conditionLower.includes('sonnig'))
        return 'mdi-weather-sunny';

    // Cloud conditions - check for partial clouds first
    if (conditionLower.includes('wolke') || conditionLower.includes('bewölkt')) {
        if (conditionLower.includes('teil')) return 'mdi-weather-partly-cloudy';
        return 'mdi-weather-cloudy';
    }

    // Default icon if nothing matches
    return 'mdi-weather-partly-cloudy';
};

// Computed property for appointment activities
const appointmentActivities = computed(() => {
    // If no actual data or loading
    if (!userId.value || loadingEvents.value) {
        return [];
    }
    console.log('events', events.value);
    // Process actual events from API
    return events.value.map(event => ({
        title: event.title,
        time: formatEventDateTime(event.start, event.end),
        icon: getEventIcon(event),
        color: event.color || '#8b5cf6',
    }));
});

// Events data
const events = ref([]);
const loadingEvents = ref(false);

function getEventIcon(event) {
    // Extract type from event object if available
    const type = event.type?.toLowerCase() || event.title?.toLowerCase() || '';

    // Return appropriate icon based on event type
    if (type.includes('meeting') || type.includes('besprechung')) return 'mdi-account-group';
    if (type.includes('training') || type.includes('schulung')) return 'mdi-school';
    if (type.includes('deadline') || type.includes('frist')) return 'mdi-clock-alert';
    if (type.includes('urlaub') || type.includes('vacation')) return 'mdi-beach';
    if (type.includes('präsentation')) return 'mdi-presentation';

    // Default icon
    return 'mdi-calendar-clock';
}

function formatEventDateTime(start, end) {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);

    // Format time
    const startTime = startDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    const endTime = endDate.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });

    // Check if event is today or tomorrow
    let datePrefix = '';
    if (startDate.toDateString() === today.toDateString()) {
        datePrefix = 'Heute';
    } else if (startDate.toDateString() === tomorrow.toDateString()) {
        datePrefix = 'Morgen';
    } else {
        datePrefix = startDate.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
        });
    }

    return `${datePrefix}, ${startTime} - ${endTime} Uhr`;
}

// Function to update the current time
const updateTime = () => {
    currentTime.value = new Date();
};

// Function to check if icon positions are valid
const validateIconPositions = (positions) => {
    if (!positions || typeof positions !== 'object') return false;
    
    // Check if there are any positions at all
    const keys = Object.keys(positions);
    if (keys.length === 0) return false;
    
    // Create a set to track used positions 
    const usedPositions = new Set();
    let hasOverlap = false;
    
    // Check each position
    for (const id of keys) {
        const pos = positions[id];
        
        // Check if position has valid x and y coordinates
        if (!pos || typeof pos !== 'object' || 
            typeof pos.x !== 'number' || 
            typeof pos.y !== 'number') {
            return false;
        }
        
        // Check for overlapping positions (less than 100px apart)
        const posKey = `${Math.floor(pos.x/100)},${Math.floor(pos.y/100)}`;
        if (usedPositions.has(posKey)) {
            hasOverlap = true;
            break;
        }
        usedPositions.add(posKey);
    }
    
    // If we found overlapping positions, consider the layout invalid
    return !hasOverlap;
};

// ====== ICON ARRANGEMENT CONFIGURATION ======
/*
   Raster der Arbeitsflaeche.

   Die Kachel misst 88 px breit; hoch ist sie 79 px bei einzeiligem und 94 px
   bei zweizeiligem Namen ("Schwarzes Brett", "Tools & Utilities",
   "Website-Manager") - live gemessen. Die Schrittweite ist die Kachel plus
   12 px Luft: 100 waagerecht, 106 senkrecht. Mit 130 bzw. 116 stand zwischen
   zwei Reihen ueber dreimal so viel Luft wie zwischen zwei Spalten, das Raster
   fiel in Streifen auseinander.
*/
const ICON_CONFIG = {
    width: 100,           // waagerechte Schrittweite: 88 Kachel + 12 Luft
    height: 106,          // senkrechte Schrittweite: 94 Kachel + 12 Luft
    startX: 20,           // Left margin
    startY: 20,           // Top margin
    gridSize: 20,         // Snap-to-grid size
    maxColsPerRow: 8,     // Maximum icons per row
    maxRows: 10,          // Maximum rows (increased for more flexibility)
    minSpacing: 10,       // Minimum spacing between icons
};

// Icon sorting modes
const iconSortMode = ref<'priority' | 'alphabetical' | 'category'>('priority');

/**
 * Kontextmenue der Arbeitsflaeche.
 *
 * `desktopMenuAt` traegt den Punkt, an dem geklickt wurde - Vuetify haengt das
 * Menue daran auf. Ein Kontextmenue, das immer an derselben Stelle aufgeht,
 * zwingt die Maus zurueck ueber den halben Bildschirm.
 */
const desktopMenuOpen = ref(false);
const desktopMenuAt = ref<[number, number]>([0, 0]);

const sortModeLabels: Record<string, string> = {
    priority: 'desktop.sortByPriority',
    alphabetical: 'desktop.sortAlphabetically',
    category: 'desktop.sortByCategory',
};

function openDesktopMenu(event: MouseEvent) {
    // Nicht ueber den Rand hinaus: sonst steht das Menue halb ausserhalb.
    const breite = 210;
    const hoehe = 190;
    desktopMenuAt.value = [
        Math.min(event.clientX, window.innerWidth - breite - 8),
        Math.min(event.clientY, window.innerHeight - hoehe - 8),
    ];
    desktopMenuOpen.value = true;
}

function chooseSortMode(mode: string) {
    iconSortMode.value = mode as typeof iconSortMode.value;
    desktopMenuOpen.value = false;
    forceGenerateIcons();
}

function resetDesktopLayout() {
    desktopMenuOpen.value = false;
    forceGenerateIcons();
}

// Priority list for common apps (shown first)
const PRIORITY_APP_IDS = [
    'dashboard',
    'employee',
    'company',
    'document',
    'report',
    'todo',
    'calendar',
    'calculator',
    'minesweeper',
    'solitaire',
    'sudoku'
];

// ====== COMPACT ICON ARRANGEMENT ALGORITHM ======
// This arranges icons without gaps, respecting permissions and visibility
const generateCompactIconLayout = (sortMode: 'priority' | 'alphabetical' | 'category' = 'priority') => {
    const iconPosObj: Record<string, { x: number; y: number }> = {};

    try {
        // Get all accessible desktop apps
        let appsToArrange = desktopApps.value.filter(app => {
            // Filter out taskbar-only apps
            if (app.id === 'waterduck') return false;
            // Check route permissions
            return !app.route || canAccessRoute(app.route);
        });

        // Sort apps based on selected mode
        if (sortMode === 'alphabetical') {
            appsToArrange = appsToArrange.sort((a, b) =>
                a.title.localeCompare(b.title)
            );
        } else if (sortMode === 'category') {
            // Group by isGroup first, then alphabetically
            appsToArrange = appsToArrange.sort((a, b) => {
                if (a.isGroup && !b.isGroup) return -1;
                if (!a.isGroup && b.isGroup) return 1;
                return a.title.localeCompare(b.title);
            });
        } else {
            // Priority mode: priority apps first, then others
            const priorityApps = appsToArrange.filter(app =>
                PRIORITY_APP_IDS.includes(app.id)
            );
            const otherApps = appsToArrange.filter(app =>
                !PRIORITY_APP_IDS.includes(app.id)
            );

            // Sort priority apps by their order in PRIORITY_APP_IDS
            priorityApps.sort((a, b) => {
                const indexA = PRIORITY_APP_IDS.indexOf(a.id);
                const indexB = PRIORITY_APP_IDS.indexOf(b.id);
                return indexA - indexB;
            });

            // Sort other apps alphabetically
            otherApps.sort((a, b) => a.title.localeCompare(b.title));

            appsToArrange = [...priorityApps, ...otherApps];
        }

        // Arrange icons in compact grid (no gaps)
        let row = 0;
        let col = 0;

        appsToArrange.forEach(app => {
            if (row >= ICON_CONFIG.maxRows) return; // Stop if max rows reached

            // Calculate position with snap-to-grid
            const x = col * ICON_CONFIG.width + ICON_CONFIG.startX;
            const y = row * ICON_CONFIG.height + ICON_CONFIG.startY;

            iconPosObj[app.id] = { x, y };

            // Move to next position
            col++;
            if (col >= ICON_CONFIG.maxColsPerRow) {
                col = 0;
                row++;
            }
        });

        console.log(`📐 Generated compact layout for ${Object.keys(iconPosObj).length} icons (mode: ${sortMode})`);
        return iconPosObj;

    } catch (error) {
        console.error('Error during compact icon layout generation:', error);
        toast.error(t('toast.iconGenerateErrorPrefix') + (error?.message || 'Unbekannter Fehler'));
        return {};
    }
};

// Generiere Standard-Positionen für alle Desktop-Icons
const generateDefaultIconPositions = () => {
    try {
        const iconPosObj = generateCompactIconLayout(iconSortMode.value);

        // Positionen im Store speichern
        if (uiStore?.updateAllIconPositions) {
            uiStore.updateAllIconPositions(iconPosObj);
        } else if (Array.isArray(iconPositions.value) || Object.keys(iconPositions.value).length === 0) {
            // Als Fallback, wenn Store-Methode nicht verfügbar oder iconPositions leer ist
            Object.keys(iconPosObj).forEach(appId => {
                if (!iconPositions.value[appId]) iconPositions.value[appId] = {};
                iconPositions.value[appId] = iconPosObj[appId];
            });
            saveIconPositions();
        }
    } catch (error) {
        console.error('Error during icon generation:', error);
        toast.error(t('toast.iconGenerateErrorPrefix') + (error?.message || 'Unbekannter Fehler'));
    }
};

// Fetch desktop data
const fetchDesktopData = async () => {
    // Ladestatus aktivieren
    isLoading.value = true;

    // Track start time for minimum loading duration
    const loadingStartTime = Date.now();
    const MIN_LOADING_DURATION = 3000; // 3 seconds minimum

    try {
        console.log('Fetching desktop data...');

        // Check if UI store might have isLoadingDesktopSettings stuck
        if (uiStore.isLoadingDesktopSettings) {
            console.warn('isLoadingDesktopSettings was already true - resetting it');
            uiStore.$patch({
                isLoadingDesktopSettings: false
            });
        }
        
        // Load desktop settings directly with the correct endpoint format
        try {
            // Direct API call with correct URL format
            const response = await apiClientAuth.get('/desktop/', {
                params: { 
                    action: 'getSettings',
                    _t: Date.now() // Cache-Busting
                }
            });
            
            console.log('Desktop settings response:', response.data);
            
            // Extract data from the response
            if (response.data && response.data.success !== false) {
                // Load background from server if available
                if (response.data.background) {
                    console.log('Setting background from server:', response.data.background);
                    desktopBackgroundImage.value = response.data.background;
                    localStorage.setItem('desktop-background', response.data.background);
                } else {
                    console.log('No background from server, keeping default');
                }
                
                // Parse and load icon positions
                if (response.data.icon_positions) {
                    try {
                        let positionsData = typeof response.data.icon_positions === 'string'
                            ? JSON.parse(response.data.icon_positions)
                            : response.data.icon_positions;
                        
                        // Validate the positions - if invalid, generate new ones
                        if (!validateIconPositions(positionsData)) {
                            console.warn('Invalid icon positions received, generating defaults');
                            positionsData = generateDefaultIconPositions();
                        } else if (positionsData && typeof positionsData === 'object') {
                            // Update the UI store directly with valid positions
                            console.log('Valid icon positions loaded from server');
                            uiStore.$patch({ 
                                desktopIconPositions: positionsData 
                            });
                        }
                    } catch (err) {
                        console.error('Error parsing icon positions:', err);
                        // Generate defaults on error
                        generateDefaultIconPositions();
                    }
                } else {
                    // No icon positions in response, generate defaults
                    console.log('No icon positions in server response, generating defaults');
                    generateDefaultIconPositions();
                }
                
                // Set theme if provided
                if (response.data.theme) {
                    uiStore.$patch({ desktopTheme: response.data.theme });
                    document.documentElement.setAttribute('data-theme', response.data.theme);
                }
            } else {
                console.log('No desktop settings found or invalid response');
                // Generate default positions if no valid settings
                generateDefaultIconPositions();
            }
        } catch (err) {
            console.error('Error loading desktop settings:', err);
            // Generate defaults on error
            generateDefaultIconPositions();
        }
        
        // Tatsächliche API-Aufrufe verwenden, ähnlich wie im Dashboard
        if (!authStore.user) return;
        
        // Wetter-Daten abrufen
        try {
            // Statische Wetterdaten verwenden anstatt API-Aufruf
            const staticWeatherData = [
                {"id":55,"date":"2025-04-24","day":"Donnerstag","min_temp":"9.0","max_temp":"16.0","icon":"mdi-weather-partly-cloudy","humidity":60,"warning":"Keine Warnungen","wind_speed":"3.00"},
                {"id":56,"date":"2025-04-25","day":"Freitag","min_temp":"10.0","max_temp":"17.0","icon":"mdi-weather-sunny","humidity":55,"warning":"Keine Warnungen","wind_speed":"2.80"},
                {"id":57,"date":"2025-04-26","day":"Samstag","min_temp":"11.0","max_temp":"18.0","icon":"mdi-weather-sunny","humidity":50,"warning":"Klarer Himmel","wind_speed":"2.50"}
            ];

            if (staticWeatherData && staticWeatherData.length > 0) {
                const currentWeatherData = staticWeatherData[0];
                weather.value = {
                    temperature: Number(currentWeatherData.max_temp) || 22,
                    condition:
                        currentWeatherData.warning ||
                        (currentWeatherData.icon
                            ? currentWeatherData.icon.replace(/-/g, ' ')
                            : 'Teilweise bewölkt'),
                    location: 'Los Santos',
                    icon: currentWeatherData.icon || null,
                };
            }
        } catch (error) {
            console.error('Fehler beim Abrufen der Wetterdaten:', error);
        }

        // Berichte zur Bearbeitung abrufen
        try {
            const reportsResponse = await apiClientAuth.get(
                '/report/?action=getReportsToProcessCount'
            );
            statistics.value.openReports = reportsResponse.data.count || 0;
        } catch (error) {
            console.error('Fehler beim Abrufen der Berichts-Statistiken:', error);
        }

        // Termine abrufen
        try {
            loadingEvents.value = true;
            const eventsResponse = await apiClientAuth.get(
                '/calendar/?action=getUpcomingAssignedEvents&days=5&limit=5'
            );
            events.value = eventsResponse.data || [];
        } catch (error) {
            console.error('Fehler beim Abrufen der Termine:', error);
        } finally {
            loadingEvents.value = false;
        }

        // Notizen abrufen
        try {
            loadingNotes.value = true;
            const notesResponse = await apiClientAuth.get('/notes/?action=getNotes');
            notes.value = notesResponse.data || [];

            // Wenn Notizen vorhanden sind, aktiviere die erste, sonst ein leeres Template
            if (notes.value.length > 0) {
                activeNote.value = notes.value[0];
                activeNoteIndex.value = 0;
            } else {
                // Leere Notiz für neue Erstellung
                activeNote.value = {
                    id: null,
                    content: '',
                    color: '#fbbf24',
                    position: { x: 0, y: 0 },
                    created_at: null,
                    updated_at: null,
                };
                activeNoteIndex.value = 0;
            }
        } catch (error) {
            console.error('Fehler beim Abrufen der Notizen:', error);
        } finally {
            loadingNotes.value = false;
        }
    } catch (error) {
        console.error('Fehler beim Abrufen der Desktop-Daten:', error);
    } finally {
        // Calculate remaining time to reach minimum loading duration
        const elapsedTime = Date.now() - loadingStartTime;
        const remainingTime = Math.max(0, MIN_LOADING_DURATION - elapsedTime);

        if (remainingTime > 0) {
            console.log(`⏱️ Waiting additional ${remainingTime}ms to reach minimum loading duration`);
            await new Promise(resolve => setTimeout(resolve, remainingTime));
        }

        // Ladestatus ausblenden
        isLoading.value = false;

        // Clear reset flag after initial load is complete
        if (wasResetting) {
            sessionStorage.removeItem('desktop-resetting');
            isResettingLayout.value = false;
        }
    }
};

// --- Berechtigungs-Helfer ---
const canAccessRoute = (targetRoutePath: string): boolean => {
    // Get the route by path
    const targetRoute = router.getRoutes().find(route => route.path === targetRoutePath) || {
        meta: {}
    };

    // Check if permissions and features are required
    const { requiredModule, requiredAction, requiredPermission, requiredFeature } = targetRoute.meta || {};

    // Check permission - supports both new and old formats
    let hasRequiredPermission = true;

    if (requiredModule && requiredAction) {
        // NEW FORMAT: Use module-based permission check
        const { hasModulePermission } = useModulePermission();
        hasRequiredPermission = hasModulePermission(requiredModule, requiredAction);
    } else if (requiredPermission) {
        // OLD FORMAT: Try to convert to new format
        const converted = convertLegacyToModule(requiredPermission);

        if (converted) {
            // Use module-based check with converted permission
            const { hasModulePermission } = useModulePermission();
            hasRequiredPermission = hasModulePermission(converted.module, converted.action);
        } else {
            // Permission not found in mapping - log warning and deny access
            console.warn(`[DesktopView] Unknown permission: ${requiredPermission}. Add to permissionMapping.ts`);
            hasRequiredPermission = false;
        }
    }

    // Check if the feature is active
    let hasFeatureAccess = true;
    if (requiredFeature) {
        const activeFeatures = authStore.user?.active_features || [];
        hasFeatureAccess = Array.isArray(activeFeatures) && activeFeatures.includes(requiredFeature);
    }

    // User can access the route only if they have both permission and feature access
    return hasRequiredPermission && hasFeatureAccess;
};

// Importiere die canAccessRoute und canAccessAnyRoute Funktionen
const canAccessAnyRoute = (paths: string[]): boolean => {
    return paths.some(path => canAccessRoute(path));
};

// Hilfsfunktion, um Navigationsitems zu extrahieren
const extractMenuItems = () => {
    const menuItems = [];

    // Check if user has system admin permissions (new bitmask format)
    const { hasModulePermission } = useModulePermission();
    const isSystemAdmin = hasModulePermission('system', 'ADMIN');

    // Hauptmenüpunkte
    // Dashboard
    if (canAccessRoute('/dashboard')) {
        menuItems.push({
            id: 'dashboard',
            title: t('tabs.dashboard'),
            icon: 'mdi-view-dashboard-outline',
            route: '/dashboard',
            color: 'var(--k-accent)',
        });
    }

    // Tools & Utilities Gruppe
    menuItems.push({
        id: 'tools-group',
        title: t('desktop.toolsUtilities'),
        icon: 'mdi-tools',
        color: '#10b981',
        isGroup: true,
        hideOnDesktop: false, // Group itself is hidden, but children are shown
    });

    // Taschenrechner
    menuItems.push({
        id: 'calculator',
        title: t('tabs.calculator'),
        icon: 'mdi-calculator',
        color: '#10b981',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // Minesweeper
    menuItems.push({
        id: 'minesweeper',
        title: t('tabs.minesweeper'),
        icon: 'mdi-mine',
        color: 'var(--k-accent)',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // Solitaire
    menuItems.push({
        id: 'solitaire',
        title: t('tabs.solitaire'),
        icon: 'mdi-cards',
        color: '#EC4899',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // Sudoku
    menuItems.push({
        id: 'sudoku',
        title: t('tabs.sudoku'),
        icon: 'mdi-puzzle',
        color: '#8B5CF6',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // QuackleJump
    menuItems.push({
        id: 'quacklejump',
        title: 'QuackleJump',
        icon: 'mdi-duck',
        color: '#FFD700',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // Wetterapp
    menuItems.push({
        id: 'weather',
        title: t('desktop.weather'),
        icon: 'mdi-weather-partly-cloudy',
        color: '#0ea5e9',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // Whiteboard
    menuItems.push({
        id: 'whiteboard',
        title: t('tabs.whiteboard'),
        icon: 'mdi-drawing-box',
        color: '#6366f1',
        route: '/whiteboard',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
    });

    // WaterDuck Browser
    menuItems.push({
        id: 'waterduck',
        title: t('tabs.waterduck'),
        icon: '/img/waterduck.png', // Direct path to the image file
        color: '#1976D2',
        route: '/waterduck',
        isDesktopApp: true,
        parent: 'tools-group',
        hideOnDesktop: true,
        isPinned: true // Mark as pinned to show in taskbar
    });

    // Admin-Gruppe
    if (
        canAccessAnyRoute([
            '/admin/users',
            '/admin/roles',
            '/admin/employees',
            '/admin/trainings',
            '/admin/applicationquestions',
            '/admin/mail',
            '/admin/map',
            '/admin/weather',
            '/admin/settings',
            '/admin/authorities',
            '/admin/authorityfields',
            '/admin/reportfields',
            '/admin/cheatsheet',
            '/admin/documentareas',
            '/admin/blackboard-areas',
            '/admin/logs',
        ])
    ) {
        // Admin Haupteintrag
        menuItems.push({
            id: 'admin',
            title: t('tabs.administration'),
            icon: 'mdi-shield-account-outline',
            route: '/admin/users',
            color: '#6B7684',
            isGroup: true,
        });

        // Admin Untermenüs
        if (canAccessRoute('/admin/users')) {
            menuItems.push({
                id: 'admin-users',
                title: t('tabs.users'),
                icon: 'mdi-account-multiple-outline',
                route: '/admin/users',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/roles')) {
            menuItems.push({
                id: 'admin-roles',
                title: t('tabs.roles'),
                icon: 'mdi-account-key-outline',
                route: '/admin/roles',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        // System tab - only for system admins (requires ADMIN permission on 'system' module)
        if (canAccessRoute('/admin/authorities') && isSystemAdmin) {
            menuItems.push({
                id: 'admin-authorities',
                title: t('tabs.system'),
                icon: 'mdi-cog-outline',
                route: '/admin/authorities',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/authorityfields')) {
            menuItems.push({
                id: 'admin-authorityfields',
                title: t('tabs.authorityFields'),
                icon: 'mdi-form-select',
                route: '/admin/authorityfields',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/reportfields')) {
            menuItems.push({
                id: 'admin-reportfields',
                title: t('tabs.reportFields'),
                icon: 'mdi-notebook-edit-outline',
                route: '/admin/reportfields',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/employees')) {
            menuItems.push({
                id: 'admin-employees',
                title: t('tabs.employee'),
                icon: 'mdi-account-group-outline',
                route: '/admin/employees',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/trainings')) {
            menuItems.push({
                id: 'admin-trainings',
                title: t('tabs.training'),
                icon: 'mdi-school-outline',
                route: '/admin/trainings',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/applicationquestions')) {
            menuItems.push({
                id: 'admin-applicationquestions',
                title: t('tabs.applicationQuestions'),
                icon: 'mdi-help-circle-outline',
                route: '/admin/applicationquestions',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/mail')) {
            menuItems.push({
                id: 'admin-mail',
                title: 'Mail-System',
                icon: 'mdi-shield-account',
                route: '/admin/mail',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/map')) {
            menuItems.push({
                id: 'admin-map',
                title: t('tabs.map'),
                icon: 'mdi-map-outline',
                route: '/admin/map',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/weather')) {
            menuItems.push({
                id: 'admin-weather',
                title: t('desktop.weather'),
                icon: 'mdi-weather-partly-cloudy',
                route: '/admin/weather',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/documentareas')) {
            menuItems.push({
                id: 'admin-documentareas',
                title: t('tabs.documentAreas'),
                icon: 'mdi-folder-multiple-outline',
                route: '/admin/documentareas',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/blackboard-areas')) {
            menuItems.push({
                id: 'admin-blackboard-areas',
                title: t('tabs.blackboardAreas'),
                icon: 'mdi-bulletin-board',
                route: '/admin/blackboard-areas',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/settings')) {
            menuItems.push({
                id: 'admin-settings',
                title: t('tabs.authorityBranding'),  // Changed from tabs.settings
                icon: 'mdi-palette-outline',  // Changed to branding icon
                route: '/admin/settings',
                color: '#6B7684',  // Changed to purple
                parent: 'admin',
            });
        }

        // System logs - only for system admins (requires ADMIN permission on 'system' module)
        if (canAccessRoute('/admin/logs') && isSystemAdmin) {
            menuItems.push({
                id: 'admin-logs',
                title: t('tabs.systemLogs'),
                icon: 'mdi-database-search',
                route: '/admin/logs',
                color: '#6B7684',
                parent: 'admin',
            });
        }

        if (canAccessRoute('/admin/cheatsheet')) {
            menuItems.push({
                id: 'admin-cheatsheet',
                title: t('tabs.cheatsheet'),
                icon: 'mdi-book-open-variant',
                route: '/admin/cheatsheet',
                color: '#6B7684',
                parent: 'admin',
            });
        }
    }

    // Allgemeine Gruppe
    // Leitstelle-Gruppe
    if (canAccessAnyRoute(['/dispatch', '/vehicle', '/crew'])) {
        menuItems.push({
            id: 'dispatch-group',
            title: t('tabs.dispatch'),
            icon: 'mdi-fire-truck',
            color: '#D9534F',
            isGroup: true,
        });

        if (canAccessRoute('/dispatch')) {
            menuItems.push({
                id: 'dispatch',
                title: t('tabs.dispatch'),
                icon: 'mdi-fire-truck',
                route: '/dispatch',
                color: '#D9534F',
                parent: 'dispatch-group',
            });
        }

        if (canAccessRoute('/vehicle')) {
            menuItems.push({
                id: 'vehicle',
                title: t('tabs.vehicle'),
                icon: 'mdi-car',
                route: '/vehicle',
                color: '#D9534F',
                parent: 'dispatch-group',
            });
        }

        if (canAccessRoute('/crew')) {
            menuItems.push({
                id: 'crew',
                title: t('tabs.crew'),
                icon: 'mdi-account-group',
                route: '/crew',
                color: '#D9534F',
                parent: 'dispatch-group',
            });
        }
    }

    // Schwarzes Brett Gruppe (Dynamic Areas)
    if (blackboardAreas.value && blackboardAreas.value.length > 0) {
        menuItems.push({
            id: 'blackboard-group',
            title: t('tabs.blackboard'),
            icon: 'mdi-bulletin-board',
            color: '#D9534F',
            isGroup: true,
        });

        // Add dynamic blackboard areas
        blackboardAreas.value.forEach((area, index) => {
            if (area.is_active && area.permissions?.can_read) {
                menuItems.push({
                    id: `blackboard-area-${area.key}`,
                    title: area.name,
                    icon: area.icon || 'mdi-bulletin-board',
                    route: `/blackboard/area/${area.id}`,
                    color: index % 2 === 0 ? '#f59e0b' : '#d97706',
                    parent: 'blackboard-group',
                });
            }
        });
    }

    // Behörden Gruppe
    if (canAccessAnyRoute(['/blackboard/global', '/document/global', '/map/global'])) {
        menuItems.push({
            id: 'authorities-group',
            title: t('tabs.authorities'),
            icon: 'mdi-home-group',
            color: '#D9534F',
            isGroup: true,
        });

        if (canAccessRoute('/blackboard/global')) {
            menuItems.push({
                id: 'blackboard-global',
                title: t('tabs.blackboard'),
                icon: 'mdi-developer-board',
                route: '/blackboard/global',
                color: '#D9534F',
                parent: 'authorities-group',
            });
        }

        if (canAccessRoute('/document/global')) {
            menuItems.push({
                id: 'document-global',
                title: t('tabs.document'),
                icon: 'mdi-file-document',
                route: '/document/global',
                color: '#3D7DD8',
                parent: 'authorities-group',
            });
        }

        if (canAccessRoute('/map/global')) {
            menuItems.push({
                id: 'map-global',
                title: t('tabs.map'),
                icon: 'mdi-map',
                route: '/map/global',
                color: '#D9534F',
                parent: 'authorities-group',
            });
        }
    }

    // Templates
    if (canAccessRoute('/template')) {
        menuItems.push({
            id: 'templates',
            title: t('tabs.template'),
            icon: 'mdi-file-edit-outline',
            route: '/template',
            color: '#6B7684',
        });
    }

    // Website-Manager als eigener Hauptpunkt
    if (canAccessRoute('/company/website')) {
        menuItems.push({
            id: 'companywebsite',
            title: t('tabs.websiteManager'),
            icon: 'mdi-web',
            route: '/company/website',
            color: '#6B7684',
        });
    }

    // Akten Gruppe
    if (canAccessAnyRoute(['/person', '/vehicleFile', '/apartmentFile'])) {
        menuItems.push({
            id: 'files-group',
            title: t('tabs.records'),
            icon: 'mdi-folder-outline',
            color: '#3D7DD8',
            isGroup: true,
        });

        if (canAccessRoute('/person')) {
            menuItems.push({
                id: 'person',
                title: t('tabs.person'),
                icon: 'mdi-account',
                route: '/person',
                color: '#3D7DD8',
                parent: 'files-group',
            });
        }

        if (canAccessRoute('/vehicleFile')) {
            menuItems.push({
                id: 'vehicleFile',
                title: t('tabs.vehicle'),
                icon: 'mdi-car',
                route: '/vehicleFile',
                color: '#3D7DD8',
                parent: 'files-group',
            });
        }

        if (canAccessRoute('/apartmentFile')) {
            menuItems.push({
                id: 'apartmentFile',
                title: t('tabs.apartment'),
                icon: 'mdi-home',
                route: '/apartmentFile',
                color: '#3D7DD8',
                parent: 'files-group',
            });
        }
    }

    // Mitarbeiter Gruppe
    if (canAccessAnyRoute(['/employee', '/vacation'])) {
        menuItems.push({
            id: 'employees-group',
            title: t('tabs.employee'),
            icon: 'mdi-account-group-outline',
            color: '#3D7DD8',
            isGroup: true,
        });

        if (canAccessRoute('/employee')) {
            menuItems.push({
                id: 'employee',
                title: t('tabs.employee'),
                icon: 'mdi-account-group',
                route: '/employee',
                color: '#3D7DD8',
                parent: 'employees-group',
            });
        }

        if (canAccessRoute('/vacation')) {
            menuItems.push({
                id: 'vacation',
                title: t('tabs.vacation'),
                icon: 'mdi-beach',
                route: '/vacation',
                color: '#3D7DD8',
                parent: 'employees-group',
            });
        }
    }

    // Unternehmen Gruppe
    if (canAccessAnyRoute(['/company', '/companytype'])) {
        menuItems.push({
            id: 'companies-group',
            title: t('tabs.company'),
            icon: 'mdi-domain',
            color: '#3D7DD8',
            isGroup: true,
        });

        if (canAccessRoute('/company')) {
            menuItems.push({
                id: 'company',
                title: t('tabs.company'),
                icon: 'mdi-domain',
                route: '/company',
                color: '#3D7DD8',
                parent: 'companies-group',
            });
        }

        if (canAccessRoute('/companytype')) {
            menuItems.push({
                id: 'companytype',
                title: t('tabs.companyType'),
                icon: 'mdi-shape',
                route: '/companytype',
                color: '#3D7DD8',
                parent: 'companies-group',
            });
        }
    }

    // Rechnungen Gruppe
    if (canAccessAnyRoute(['/invoice', '/invoiceitems'])) {
        menuItems.push({
            id: 'invoices-group',
            title: t('tabs.invoices'),
            icon: 'mdi-currency-usd',
            color: '#3D7DD8',
            isGroup: true,
        });

        if (canAccessRoute('/invoice')) {
            menuItems.push({
                id: 'invoice',
                title: t('tabs.invoice'),
                icon: 'mdi-currency-usd',
                route: '/invoice',
                color: '#3D7DD8',
                parent: 'invoices-group',
            });
        }

        if (canAccessRoute('/invoiceitem')) {
            menuItems.push({
                id: 'invoiceitem',
                title: t('tabs.invoiceItem'),
                icon: 'mdi-receipt',
                route: '/invoiceitems',
                color: '#3D7DD8',
                parent: 'invoices-group',
            });
        }
    }

    // Berichte Gruppe
    if (
        canAccessAnyRoute([
            '/report',
            '/reportcategory',
            '/reporttemplate',
            '/reportcode',
            '/reportadditional',
            '/reportstatus',
        ])
    ) {
        menuItems.push({
            id: 'reports-group',
            title: t('tabs.report'),
            icon: 'mdi-book-open-variant',
            color: '#3D7DD8',
            isGroup: true,
        });

        if (canAccessRoute('/report')) {
            menuItems.push({
                id: 'report',
                title: t('tabs.report'),
                icon: 'mdi-book-open-variant',
                route: '/report',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }

        if (canAccessRoute('/reportcategory')) {
            menuItems.push({
                id: 'reportcategory',
                title: t('tabs.category'),
                icon: 'mdi-shape',
                route: '/reportcategory',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }

        if (canAccessRoute('/reporttemplate')) {
            menuItems.push({
                id: 'reporttemplate',
                title: t('tabs.template'),
                icon: 'mdi-file-document-edit',
                route: '/reporttemplate',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }

        if (canAccessRoute('/reportcode')) {
            menuItems.push({
                id: 'reportcode',
                title: t('tabs.code'),
                icon: 'mdi-code-tags',
                route: '/reportcode',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }

        if (canAccessRoute('/reportadditional')) {
            menuItems.push({
                id: 'reportadditional',
                title: t('tabs.additional'),
                icon: 'mdi-plus-box',
                route: '/reportadditional',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }

        if (canAccessRoute('/reportstatus')) {
            menuItems.push({
                id: 'reportstatus',
                title: t('tabs.status'),
                icon: 'mdi-clipboard-check',
                route: '/reportstatus',
                color: '#3D7DD8',
                parent: 'reports-group',
            });
        }
    }

    // Dokumente Gruppe
    if (canAccessAnyRoute(['/documentarea', '/document']) || (documentAreas.value && documentAreas.value.length > 0)) {
        menuItems.push({
            id: 'documents-group',
            title: t('tabs.document'),
            icon: 'mdi-file-document-outline',
            color: '#3D7DD8',
            isGroup: true,
        });

        // Füge dynamische Dokumentenbereiche hinzu (Global wird unter Behörden angezeigt)
        if (documentAreas.value && documentAreas.value.length > 0) {
            documentAreas.value.forEach((area, index) => {
                // Skip the 'global' document area as it's shown under Authorities group
                if (area.key === 'global') return;
                
                if (area.is_active && area.permissions?.can_read) {
                    menuItems.push({
                        id: `documentarea-${area.key}`,
                        title: area.name,
                        icon: area.icon || 'mdi-file-document-outline',
                        route: `/documentarea/${area.key}`,
                        color: index % 2 === 0 ? '#ea580c' : '#c2410c', // Alternierend verschiedene Orangetöne
                        parent: 'documents-group',
                    });
                }
            });
        }
    }

    // Organisation Gruppe
    if (canAccessAnyRoute(['/todo', '/calendar', '/application', '/report'])) {
        menuItems.push({
            id: 'organization-group',
            title: t('tabs.organization'),
            icon: 'mdi-format-list-checks',
            color: '#2FA36B',
            isGroup: true,
        });

        if (canAccessRoute('/todo')) {
            menuItems.push({
                id: 'todo',
                title: t('tabs.todo'),
                icon: 'mdi-checkbox-marked-circle',
                route: '/todo',
                color: '#2FA36B',
                parent: 'organization-group',
            });
        }

        if (canAccessRoute('/calendar')) {
            menuItems.push({
                id: 'calendar',
                title: t('tabs.calendar'),
                icon: 'mdi-calendar',
                route: '/calendar',
                color: '#2FA36B',
                parent: 'organization-group',
            });
        }

        if (canAccessRoute('/application')) {
            menuItems.push({
                id: 'application',
                title: t('tabs.application'),
                icon: 'mdi-file-account',
                route: '/application',
                color: '#2FA36B',
                parent: 'organization-group',
            });
        }
    }

    // Datei Manager
    if (canAccessAnyRoute(['/filemanager'])) {
        menuItems.push({
            id: 'filemanager',
            title: t('tabs.fileManager'),
            icon: 'mdi-file-tree',
            route: '/filemanager',
            color: '#2FA36B',
        });
    }

    // Schulungen Gruppe
    if (canAccessAnyRoute(['/trainingassign', '/test'])) {
        menuItems.push({
            id: 'training-group',
            title: t('tabs.training'),
            icon: 'mdi-school',
            color: '#2FA36B',
            isGroup: true,
        });

        if (canAccessRoute('/trainingassign')) {
            menuItems.push({
                id: 'trainingassign',
                title: t('tabs.overview'),
                icon: 'mdi-view-dashboard',
                route: '/trainingassign',
                color: '#2FA36B',
                parent: 'training-group',
            });
        }

        if (canAccessRoute('/test')) {
            menuItems.push({
                id: 'test',
                title: t('tabs.generateTest'),
                icon: 'mdi-file-document-edit',
                route: '/test',
                color: '#2FA36B',
                parent: 'training-group',
            });
        }
    }

    // Sonstiges Gruppe
    if (canAccessAnyRoute(['/fireprotection', '/cheatsheet'])) {
        menuItems.push({
            id: 'misc-group',
            title: t('tabs.misc'),
            icon: 'mdi-fire',
            color: '#6B7684',
            isGroup: true,
        });

        if (canAccessRoute('/fireprotection')) {
            menuItems.push({
                id: 'fireprotection',
                title: t('tabs.fireProtection'),
                icon: 'mdi-fire',
                route: '/fireprotection',
                color: '#6B7684',
                parent: 'misc-group',
            });
        }

        if (canAccessRoute('/cheatsheet')) {
            menuItems.push({
                id: 'cheatsheet',
                title: t('tabs.cheatsheet'),
                icon: 'mdi-file-document',
                route: '/cheatsheet',
                color: '#6B7684',
                parent: 'misc-group',
            });
        }
    }

    // Karte
    if (canAccessAnyRoute(['/map'])) {
        menuItems.push({
            id: 'map',
            title: t('tabs.map'),
            icon: 'mdi-map',
            route: '/map',
            color: '#D9534F',
        });
    }

    // Widgets Group
    menuItems.push({
        id: 'widgets-group',
        title: t('desktop.widgets'),
        icon: 'mdi-widgets',
        color: '#6366f1',
        isGroup: true,
    });

    // Weather Widget
    menuItems.push({
        id: 'widget-weather',
        title: t('widgets.weather.title'),
        icon: 'mdi-weather-partly-cloudy',
        color: 'var(--k-accent)',
        action: 'addWidget',
        widgetType: 'weather',
        parent: 'widgets-group',
    });

    // Calendar Widget
    menuItems.push({
        id: 'widget-calendar',
        title: t('widgets.calendar.title'),
        icon: 'mdi-calendar',
        color: '#8b5cf6',
        action: 'addWidget',
        widgetType: 'calendar',
        parent: 'widgets-group',
    });

    // Notes Widget
    menuItems.push({
        id: 'widget-notes',
        title: t('widgets.notes.title'),
        icon: 'mdi-note-text',
        color: '#10b981',
        action: 'addWidget',
        widgetType: 'notes',
        parent: 'widgets-group',
    });

    // Exit Desktop - immer hinzufügen
    menuItems.push({
        id: 'exit',
        title: t('desktop.exitDesktop'),
        icon: 'mdi-exit-to-app',
        action: 'exitDesktop',
        color: '#475569',
    });

    return menuItems;
};

// Verwende die Hilfsfunktion, um alle Menüpunkte zu extrahieren
// Make allApps reactive to language changes
const allApps = computed(() => extractMenuItems());

// Computed properties
const backgroundStyle = computed(() => {
    // Basis-URL für Assets (z.B. http://localhost:8000 oder https://deine-domain.de)
    const baseUrl = import.meta.env.VITE_API_URL || '';

    // Prüfe, ob die Hintergrund-URL als kompletter Pfad gespeichert ist
    if (desktopBackgroundImage.value) {
        // Füge einen Cache-Busting-Parameter hinzu
        const timestamp = Date.now();
        // URL mit Timestamp für Cache-Busting
        const imageUrl = `${desktopBackgroundImage.value}?_t=${timestamp}`;
        
        // Wenn das Bild eine relative URL ist, präfixiere die baseUrl
        if (desktopBackgroundImage.value.startsWith('/')) {
            return {
                backgroundImage: `url(${baseUrl}${imageUrl})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat',
            };
        } else {
            // Bei absoluter URL direkt verwenden
            return {
                backgroundImage: `url(${imageUrl})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat',
            };
        }
    }

    // Fallback-Hintergrund, wenn kein Bild gesetzt ist
    return {
        backgroundColor: 'var(--desktop-bg-dark-2)',
        backgroundImage: 'none',
    };
});

// Helper function to check if an icon path is an image (not Material Design Icon)
const isImagePath = (iconPath) => {
    if (!iconPath || typeof iconPath !== 'string') return false;
    return iconPath.startsWith('/') || 
           iconPath.includes('.png') || 
           iconPath.includes('.jpg') || 
           iconPath.includes('.jpeg') || 
           iconPath.includes('.svg');
};

// Create computed property for desktop apps
const desktopApps = computed(() => {
    // Filter apps that should appear on desktop
    // Include apps that are in the tools-group and have isDesktopApp=true
    // as well as top level apps without parent
    // IMPORTANT: Respect hideOnDesktop flag for all apps
    const apps = allApps.value.filter(app => 
        (!app.parent && !app.hideOnDesktop && app.id !== 'exit') || 
        (app.parent === 'tools-group' && app.isDesktopApp && !app.hideOnDesktop)
    );

    // Custom ordering of apps - you can modify this order as needed
    const orderedApps: App[] = [];

    // Define the order of app IDs as you want them to appear
    const appOrder = [
        'dashboard',
        'admin',
        'map',
        'filemanager',
        'templates',
        'calculator',
        'minesweeper',
        'solitaire',
        'sudoku',
        'waterduck', // Add the WaterDuck browser app
        'dispatch-group',
        'blackboard-group',
        'authorities-group',
        'files-group',
        'employees-group',
        'companies-group',
        'invoices-group',
        'reports-group',
        'documents-group',
        'organization-group',
        'training-group',
        'misc-group',
    ];

    // First add apps in the specified order
    appOrder.forEach(id => {
        const app = apps.find(a => a.id === id);
        if (app) {
            // Only add mdi- prefix if it's not an image path and doesn't already have mdi- prefix
            if (app.icon && !app.icon.startsWith('mdi-') && !isImagePath(app.icon)) {
                app.icon = `mdi-${app.icon}`;
            }
            orderedApps.push(app);
        }
    });

    // Then add any remaining apps that weren't in the order list
    apps.forEach(app => {
        if (!orderedApps.some(a => a.id === app.id)) {
            // Only add mdi- prefix if it's not an image path and doesn't already have mdi- prefix
            if (app.icon && !app.icon.startsWith('mdi-') && !isImagePath(app.icon)) {
                app.icon = `mdi-${app.icon}`;
            }
            orderedApps.push(app);
        }
    });

    return orderedApps;
});

// Computed property for the items in the active folder
const folderItems = computed(() => {
    if (!activeFolder.value) return [];
    
    // Wenn wir das Dokumente-Ordner-Popup anzeigen, zeige NUR die dynamischen Dokumentenbereiche (Global unter Behörden)
    if (activeFolder.value.id === 'documents-group') {
        console.log('📂 Adding document areas to folder popup, available areas:', documentAreas.value);

        const items = [];

        // Dynamische Dokumentenbereiche hinzufügen (ohne Global - wird unter Behörden angezeigt)
        if (documentAreas.value && documentAreas.value.length > 0) {
            documentAreas.value.forEach((area, index) => {
                // Skip the 'global' document area as it's shown under Authorities group
                if (area.key === 'global') return;
                
                items.push({
                    id: `documentarea-${area.key}`,
                    title: area.name,
                    icon: area.icon || 'mdi-file-document-outline',
                    route: `/documentarea/${area.key}`,
                    color: index % 2 === 0 ? '#ea580c' : '#c2410c', // Alternierend verschiedene Orangetöne
                    parent: 'documents-group',
                });
            });
        }
        
        console.log('📋 Document folder items:', items);
        return items;
    }
    
    // Für alle anderen Ordner: Normale Filterung nach parent-ID
    const items = allApps.value.filter(app => app.parent === activeFolder.value?.id);
    return items;
});

// Computed style for the folder popup with smart positioning
const folderPopupStyle = computed(() => {
    const POPUP_WIDTH = 320;
    const POPUP_MIN_HEIGHT = 200;
    const POPUP_MAX_HEIGHT = 500;
    const MARGIN = 20;

    let x = folderPosition.value.x;
    let y = folderPosition.value.y;

    // Get viewport dimensions
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Estimate popup height based on folder items (rough calculation)
    const itemHeight = 40;
    const headerHeight = 50;
    const estimatedHeight = Math.min(
        POPUP_MAX_HEIGHT,
        Math.max(POPUP_MIN_HEIGHT, headerHeight + (folderItems.value.length * itemHeight))
    );

    // Adjust horizontal position to keep popup within viewport
    if (x + POPUP_WIDTH + MARGIN > viewportWidth) {
        // Position to the left of the icon if it would overflow right
        x = Math.max(MARGIN, viewportWidth - POPUP_WIDTH - MARGIN);
    }

    // Adjust vertical position to keep popup within viewport
    if (y + estimatedHeight + MARGIN > viewportHeight) {
        // Position above or adjust if it would overflow bottom
        y = Math.max(MARGIN, viewportHeight - estimatedHeight - MARGIN - 60); // 60px for taskbar
    }

    // Ensure minimum margins from edges
    x = Math.max(MARGIN, Math.min(x, viewportWidth - POPUP_WIDTH - MARGIN));
    y = Math.max(MARGIN, Math.min(y, viewportHeight - estimatedHeight - MARGIN - 60));

    return {
        left: `${x}px`,
        top: `${y}px`,
        maxHeight: `${POPUP_MAX_HEIGHT}px`,
    };
});

// Handle click on desktop icon
const handleIconClick = (app: App, event?: MouseEvent) => {
    console.log('🖱️ Icon clicked:', app);

    if (app.isGroup) {
        console.log('📁 Opening folder:', app.title);

        // Close any active window to prevent interference
        activeFolder.value = null;

        // Set a short timeout to ensure DOM has updated
        setTimeout(() => {
            // Determine position for the folder popup
            let x = 100;
            let y = 100;

            // Get all desktop icons
            const icons = Array.from(document.querySelectorAll('.desktop-icon'));

            // Try to find the icon by title
            for (let i = 0; i < icons.length; i++) {
                const icon = icons[i] as HTMLElement;
                const titleEl = icon.querySelector('.icon-title');
                if (titleEl && titleEl.textContent === app.title) {
                    const rect = icon.getBoundingClientRect();
                    // Position directly below the icon, but make sure it's not off-screen
                    x = Math.min(rect.left, window.innerWidth - 330);
                    y = rect.bottom + 10;
                    break;
                }
            }

            // If position wasn't found, use a fallback
            if (x === 100 && y === 100) {
                x = Math.min(window.innerWidth - 320, Math.max(50, 100));
                y = Math.min(window.innerHeight - 420, Math.max(100, 100));
            }

            folderPosition.value = { x, y };
            activeFolder.value = app;

            // Log folder items for debugging
            console.log('📋 Folder items:', folderItems.value);
        }, 10);
    } else {
        openApp(app);
    }
};

// Methods
const toggleStartMenu = () => {
    // Toggle directly without using the computed
    uiStore.toggleStartMenu();

    // Close any open folder when opening start menu
    activeFolder.value = null;
};

const closeStartMenu = () => {
    // Verwende direkt die State-Property oder die toggleStartMenu-Methode
    uiStore.$state.startMenuOpen = false; // Alternative: uiStore.toggleStartMenu() wenn das Menü offen ist
};

const toggleGlobalSearch = () => {
    console.log('🔍 Opening global search from desktop');
    // Trigger global search by dispatching a custom event
    window.dispatchEvent(new KeyboardEvent('keydown', {
        key: 'k',
        ctrlKey: true,
        bubbles: true
    }));
};

// Konstante für die Höhe der Taskleiste
const taskbarHeight = 48; // Höhe der Taskleiste in Pixeln

// Window focus function
const focusWindow = (windowId: string) => {
    // Setze das aktive Fenster
    activeWindowId.value = windowId;
    
    // Finde das Fenster und bringe es nach vorne (erhöhe den z-index)
    const window = openWindows.value.find(w => w.id === windowId);
    if (window) {
        // Wenn das Fenster minimiert ist, stelle es wieder her
        if (window.minimized) {
            window.minimized = false;
        }
        
        // Erhöhe den z-index für das aktive Fenster
        window.zIndex = lastZIndex.value++;
    }
};

// Window close function
const closeWindow = (windowId: string) => {
    // Remove the window from the openWindows array
    openWindows.value = openWindows.value.filter(w => w.id !== windowId);
    
    // If this was the active window, clear the active window ID
    if (activeWindowId.value === windowId) {
        activeWindowId.value = null;
        
        // Set the next window as active if there are any left
        if (openWindows.value.length > 0) {
            // Find the window with the highest z-index
            const topWindow = openWindows.value.reduce((prev, current) => 
                (current.zIndex > prev.zIndex) ? current : prev
            );
            activeWindowId.value = topWindow.id;
        }
    }
};

const openApp = (app: App) => {
    console.log('🚀 Opening app:', app);
    console.log('🔍 App action:', app.action);
    console.log('🔍 Widget type:', app.widgetType);
    
    // Handle widget actions
    if (app.action === 'addWidget' && app.widgetType) {
        console.log('🎯 Triggering addWidget for:', app.widgetType);
        addWidget(app.widgetType);
        // Close folder and start menu
        activeFolder.value = null;
        closeStartMenu();
        return;
    }

    // Für dynamische Dokumentenbereiche spezielle Behandlung - ABER als Fenster öffnen
    if (app.id && app.id.startsWith('documentarea-')) {
        console.log('📄 Opening document area as window:', app);
        // Schließe aktiven Ordner
        activeFolder.value = null;
        // Schließe Startmenü
        closeStartMenu();
        
        // Prüfen, ob bereits ein Fenster für diesen Dokumentenbereich geöffnet ist
        const existingWindow = openWindows.value.find(
            w => w.appId === app.id || (w.route && w.route === app.route)
        );
        if (existingWindow) {
            console.log('🔄 Document area window already exists, focusing:', existingWindow);
            if (existingWindow.minimized) {
                existingWindow.minimized = false;
            }
            focusWindow(existingWindow.id);
            return;
        }
        
        // Standard-Fenstergröße berechnen
        const screenWidth = window.innerWidth;
        const screenHeight = window.innerHeight - taskbarHeight;
        let width = Math.floor(screenWidth * 0.6);
        const height = Math.floor(screenHeight * 0.7);
        
        // Mache Dokumente etwas breiter für bessere Lesbarkeit
        width = Math.floor(screenWidth * 0.65);
        
        // Position berechnen
        const x = Math.round((screenWidth - width) / 2);
        const y = Math.round((screenHeight - height) / 4);
        
        // Neues Fenster erstellen
        const newWindow: AppWindow = {
            id: `app-${app.id}-${Date.now()}`,
            appId: app.id,
            title: app.title,
            icon: app.icon,
            route: app.route,
            x,
            y,
            width,
            height,
            zIndex: lastZIndex.value++,
            minimized: false,
            maximized: false,
            isDesktopApp: false,
        };
        
        // Fenster zur Liste hinzufügen
        openWindows.value.push(newWindow);
        
        // Auf das neue Fenster fokussieren
        focusWindow(newWindow.id);
        return;
    }

    // Prevent opening multiple instances of the same app
    const existingWindow = openWindows.value.find(
        w => w.appId === app.id || (w.route && w.route === app.route)
    );
    if (existingWindow) {
        console.log('🔄 App window already exists, focusing:', existingWindow);
        if (existingWindow.minimized) {
            existingWindow.minimized = false;
        }
        focusWindow(existingWindow.id);
        return;
    }

    // Calculate window dimensions (30% of screen width/height as default)
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight - taskbarHeight;
    let width = Math.floor(screenWidth * 0.6);
    let height = Math.floor(screenHeight * 0.7);
    
    // Adjust width and height for specific apps
    if (app.id === 'calculator') {
        width = 320;
        height = 480;
    } else if (app.id === 'minesweeper') {
        width = 450;
        height = 550;
    } else if (app.id === 'sudoku') {
        width = 480;
        height = 800;
    } else if (app.id === 'solitaire') {
        width = 840;  // Increased width to accommodate the new design
        height = 700; // Increased height to better show the cards
    } else if (app.id === 'weather') {
        width = 600;
        height = 650;
    } else if (app.id === 'waterduck') {
        width = 1000;  // Wider width for browser experience
        height = 700;  // Taller height to show more content
    }
    
    // Close active folder when opening an app
    activeFolder.value = null;

    // Close start menu
    closeStartMenu();

    // Calculate centered position
    const x = Math.round((screenWidth - width) / 2);
    const y = Math.round((screenHeight - height) / 4); // Position it higher on the screen

    // Create new window
    const newWindow: AppWindow = {
        id: `app-${app.id}-${Date.now()}`,
        appId: app.id,
        title: app.title,
        icon: app.icon,
        route: app.route,
        x,
        y,
        width,
        height,
        zIndex: lastZIndex.value++,
        minimized: false,
        maximized: false,
        isDesktopApp: app.isDesktopApp,
    };

    // Add new window to the list
    openWindows.value.push(newWindow);

    // Focus on the new window
    focusWindow(newWindow.id);
};

// Function to exit desktop mode and return to normal view
const exitDesktopMode = () => {
    // Direktes Update des Stores verwenden
    uiStore.setDesktopMode(false);

    // Custom Event als Backup auslösen
    const evt = new CustomEvent('exit-desktop', { bubbles: true });
    document.dispatchEvent(evt);

    // Entferne Desktop-Klassen direkt vom HTML-Element
    document.documentElement.classList.remove('desktop-mode', 'hide-left-nav', 'hide-top-nav');

    // Bei Bedarf zur Standardansicht navigieren
    router.push('/dashboard');

    console.log('Exiting desktop mode...');
};

const isDarkTheme = ref(true);
const userProfile = computed(() => authStore.user || {});
const hasAdminAccess = computed(() => {
    return canAccessAnyRoute([
        '/admin/users',
        '/admin/roles',
        '/admin/employees',
        '/admin/trainings',
        '/admin/settings',
        '/admin/authority-branding',
    ]);
});

const toggleTheme = () => {
    isDarkTheme.value = !isDarkTheme.value;
    // Apply theme changes
};

const logout = async () => {
    try {
        await authStore.logout();
    } catch (error) {
        console.error('Error logging out:', error);
    }
};

// Handle note saved event
const handleNoteSaved = savedNote => {
    // Update the notes list with the saved note
    const noteIndex = notes.value.findIndex(note => note.id === savedNote.id);
    if (noteIndex >= 0) {
        // Update existing note
        notes.value[noteIndex] = savedNote;
        activeNoteIndex.value = noteIndex;
    } else {
        // Add new note
        notes.value.unshift(savedNote);
        activeNoteIndex.value = 0;
    }
};

// Handle note deleted event
const handleNoteDeleted = noteId => {
    // Find current index before deletion
    const deletedIndex = notes.value.findIndex(note => note.id === noteId);

    // Remove the deleted note from the list
    notes.value = notes.value.filter(note => note.id !== noteId);

    // If there are other notes, switch to one of them
    if (notes.value.length > 0) {
        // If we deleted the last note, or there was only one note, go to the first note
        // Otherwise stay on the same index (which now points to the next note)
        const newIndex = deletedIndex >= notes.value.length ? 0 : deletedIndex;
        activeNoteIndex.value = newIndex;
        activeNote.value = notes.value[newIndex];
    } else {
        // Create a new empty note
        activeNote.value = {
            id: null,
            content: '',
            color: '#fbbf24',
            position: { x: 0, y: 0 },
            created_at: null,
            updated_at: null,
        };
        activeNoteIndex.value = 0;
    }
};

// Handle new note event
const handleNewNote = () => {
    // Create a new empty note without losing the existing notes
    activeNote.value = {
        id: null,
        content: '',
        color: '#fbbf24',
        position: { x: 0, y: 0 },
        tags: [],
        created_at: null,
        updated_at: null,
    };
    // Set index to indicate we're creating a new note (not modifying an existing one)
    activeNoteIndex.value = -1;
};

// Handle navigation between notes
const handlePrevNote = () => {
    if (activeNoteIndex.value > 0) {
        activeNoteIndex.value--;
        activeNote.value = notes.value[activeNoteIndex.value];
    }
};

const handleNextNote = () => {
    if (activeNoteIndex.value < notes.value.length - 1) {
        activeNoteIndex.value++;
        activeNote.value = notes.value[activeNoteIndex.value];
    }
};

// Find optimal position for new widget to avoid overlap
const findOptimalWidgetPosition = (widgetType: string) => {
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const padding = 20;
    const topOffset = 80;
    const widgetAreaWidth = 320; // Desktop widget area width
    
    // Calculate right-side widget area bounds
    const widgetAreaLeft = screenWidth - widgetAreaWidth;
    const widgetStartX = widgetAreaLeft + padding;
    const maxWidgetWidth = widgetAreaWidth - (padding * 2);
    
    // Get all current widget positions
    const occupiedPositions = activeWidgets.value
        .filter(w => w !== widgetType)
        .map(w => widgetPositions.value[w])
        .filter(p => p);
    
    // Default size for new widget - constrained to widget area
    const defaultSizes = {
        weather: { width: Math.min(280, maxWidgetWidth), height: 400 },
        calendar: { width: Math.min(280, maxWidgetWidth), height: 450 },
        notes: { width: Math.min(280, maxWidgetWidth), height: 350 }
    };
    
    const size = defaultSizes[widgetType] || { width: Math.min(280, maxWidgetWidth), height: 400 };
    
    // Try to find a position within the right-side widget area
    const positions = [
        // Positions within the right-side widget area
        { x: widgetStartX, y: topOffset },
        { x: widgetStartX, y: topOffset + 420 },
        { x: widgetStartX, y: topOffset + 870 },
        { x: widgetStartX, y: topOffset + 1320 },
        { x: widgetStartX, y: topOffset + 1770 }
    ];
    
    for (const pos of positions) {
        // Check if position is within the right-side widget area and screen bounds
        if (pos.x >= widgetStartX && 
            pos.x + size.width <= screenWidth - padding && 
            pos.y + size.height <= screenHeight - padding) {
            
            // Check for overlaps with existing widgets
            let hasOverlap = false;
            for (const occupied of occupiedPositions) {
                if (occupied && 
                    pos.x < occupied.x + occupied.width &&
                    pos.x + size.width > occupied.x &&
                    pos.y < occupied.y + occupied.height &&
                    pos.y + size.height > occupied.y) {
                    hasOverlap = true;
                    break;
                }
            }
            
            if (!hasOverlap) {
                return { ...pos, ...size };
            }
        }
    }
    
    // Fallback: cascade position within right-side widget area
    const cascadeOffset = activeWidgets.value.length * 30;
    return {
        x: widgetStartX,
        y: topOffset + cascadeOffset,
        ...size
    };
};

// Widget management methods
const addWidget = (widgetType: string) => {
    console.log('🔧 addWidget called:', widgetType);
    console.log('📊 Current activeWidgets:', activeWidgets.value);
    console.log('📊 widgetPositions:', widgetPositions.value);
    console.log('📊 MAX_WIDGETS:', MAX_WIDGETS);
    
    if (activeWidgets.value.length >= MAX_WIDGETS) {
        console.log('⚠️ Maximum widgets reached!');
        toast.warning(t('widgets.maxWidgetsReached', { max: MAX_WIDGETS }));
        return;
    }
    
    if (!activeWidgets.value.includes(widgetType)) {
        // Find optimal position for new widget if not already positioned
        if (!widgetPositions.value[widgetType]) {
            widgetPositions.value[widgetType] = findOptimalWidgetPosition(widgetType);
            console.log('📍 Assigned position for', widgetType, ':', widgetPositions.value[widgetType]);
        }
        
        activeWidgets.value.push(widgetType);
        console.log('✅ Widget added:', widgetType);
        console.log('📊 Updated activeWidgets:', activeWidgets.value);
        
        // Force reactivity update
        const newActiveWidgets = [...activeWidgets.value];
        activeWidgets.value = newActiveWidgets;
        
        saveWidgetState();
        
        // Additional debug info
        console.log('🎯 Widget should now be visible in template');
        console.log('🔍 Check if CalendarWidget component is rendered');
    } else {
        console.log('⚠️ Widget already active:', widgetType);
    }
};

const removeWidget = (widgetType: string) => {
    console.log('🗑️ removeWidget called:', widgetType);
    console.log('📊 Current activeWidgets before removal:', activeWidgets.value);
    
    const index = activeWidgets.value.indexOf(widgetType);
    if (index > -1) {
        activeWidgets.value.splice(index, 1);
        console.log('✅ Widget removed:', widgetType);
        console.log('📊 Updated activeWidgets after removal:', activeWidgets.value);
        
        if (focusedWidget.value === widgetType) {
            focusedWidget.value = null;
        }
        saveWidgetState();
    } else {
        console.log('⚠️ Widget not found in activeWidgets:', widgetType);
    }
};

const updateWidgetPosition = (widgetType: string, position: any) => {
    widgetPositions.value[widgetType] = position;
    
    // Save to localStorage immediately (fast operation)
    localStorage.setItem('desktop-widgets', JSON.stringify({
        active: activeWidgets.value,
        positions: widgetPositions.value
    }));
    
    // Use debounced API save to prevent continuous calls
    debouncedSaveWidgetStateToAPI();
};

const focusWidget = (widgetType: string) => {
    focusedWidget.value = widgetType;
};

const saveWidgetState = async () => {
    try {
        // Save to localStorage as backup (always works)
        localStorage.setItem('desktop-widgets', JSON.stringify({
            active: activeWidgets.value,
            positions: widgetPositions.value
        }));
        
        // Skip API save if disabled due to errors
        if (isAPISaveDisabled.value) {
            console.log('⏭️ Skipping immediate API save due to recent errors');
            return;
        }
        
        // Try to save to backend
        await desktopApi.updateSettings({
            widgets: {
                active: activeWidgets.value,
                positions: widgetPositions.value
            }
        });
    } catch (error: any) {
        console.error('Failed to save widget state:', error);
        
        // Check if this is a database error and disable further saves temporarily
        if (error?.response?.data?.error?.includes('Database error')) {
            console.warn('🚫 Database error detected - disabling API saves temporarily');
            isAPISaveDisabled.value = true;
            lastAPIErrorTime.value = Date.now();
        }
    }
};

// Flag to prevent spam when API is failing
const isAPISaveDisabled = ref(false);
const lastAPIErrorTime = ref(0);
const API_ERROR_COOLDOWN = 30000; // 30 seconds

// Separate API save function for debouncing
const saveWidgetStateToAPI = async () => {
    // Skip if API saves are disabled due to recent errors
    if (isAPISaveDisabled.value) {
        const timeSinceError = Date.now() - lastAPIErrorTime.value;
        if (timeSinceError < API_ERROR_COOLDOWN) {
            console.log('⏭️ Skipping API save due to recent errors (cooldown active)');
            return;
        } else {
            // Re-enable after cooldown
            isAPISaveDisabled.value = false;
        }
    }
    
    try {
        console.log('💾 Saving widget state to API (debounced)');
        await desktopApi.updateSettings({
            widgets: {
                active: activeWidgets.value,
                positions: widgetPositions.value
            }
        });
        
        // Reset error state on successful save
        isAPISaveDisabled.value = false;
        lastAPIErrorTime.value = 0;
        
    } catch (error: any) {
        console.error('Failed to save widget state to API:', error);
        
        // Check if this is a database error
        if (error?.response?.data?.error?.includes('Database error')) {
            console.warn('🚫 Database error detected - disabling API saves temporarily');
            isAPISaveDisabled.value = true;
            lastAPIErrorTime.value = Date.now();
        }
    }
};

// Debounced version to prevent continuous API calls during widget dragging
// Increased debounce time to reduce API spam
const debouncedSaveWidgetStateToAPI = debounce(saveWidgetStateToAPI, 3000);

// Fix widget positions that might be off-screen
const fixWidgetPositions = (positions: Record<string, any>) => {
    const fixed = { ...positions };
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const rightPadding = 20; // Distance from right screen edge
    const padding = 20;
    
    // Get smart default positions based on current screen size
    const safeDefaults = calculateSmartWidgetPositions();
    
    // Fix positions for each widget
    Object.keys(fixed).forEach(widgetType => {
        const pos = fixed[widgetType];
        if (pos && typeof pos.x === 'number') {
            let needsFix = false;
            const newPos = { ...pos };
            
            // Constrain widget size reasonably
            newPos.width = Math.max(200, Math.min(pos.width || 280, 400));
            newPos.height = Math.max(150, Math.min(pos.height || 400, screenHeight - 100));
            
            // Force widget to stay within right area
            const minX = rightPadding; // Minimum distance from right edge
            const maxX = screenWidth - newPos.width - padding; // Widget can't go too far left
            
            if (pos.x < minX || pos.x > maxX) {
                // Move widget to safe position (20px from right)
                newPos.x = rightPadding;
                console.log(`🔧 Moving widget ${widgetType} to right area: ${pos.x} → ${newPos.x}`);
                needsFix = true;
            }
            
            // Fix Y position
            if (pos.y < 60 || pos.y + newPos.height > screenHeight - padding) {
                const safeY = safeDefaults[widgetType]?.y || 100;
                console.log(`🔧 Fixing Y position for ${widgetType}: ${pos.y} → ${safeY}`);
                newPos.y = safeY;
                needsFix = true;
            }
            
            if (needsFix) {
                fixed[widgetType] = newPos;
            }
        } else {
            // If position is invalid, use safe defaults
            fixed[widgetType] = safeDefaults[widgetType] || { x: widgetAreaLeft + padding, y: 100, width: 280, height: 400 };
        }
    });
    
    return fixed;
};

const loadWidgetState = async () => {
    console.log('📥 Loading widget state...');
    try {
        // Try to load from backend first
        const response = await desktopApi.getSettings();
        if (response.data?.widgets) {
            activeWidgets.value = response.data.widgets.active || [];
            // Fix positions that might be off-screen
            const loadedPositions = response.data.widgets.positions || widgetPositions.value;
            widgetPositions.value = fixWidgetPositions(loadedPositions);
            console.log('✅ Loaded widgets from backend:', activeWidgets.value);
            return;
        }
    } catch (error) {
        console.error('Failed to load widget state from backend:', error);
    }
    
    // Fallback to localStorage
    const saved = localStorage.getItem('desktop-widgets');
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            activeWidgets.value = parsed.active || [];
            // Fix positions that might be off-screen
            const loadedPositions = parsed.positions || widgetPositions.value;
            widgetPositions.value = fixWidgetPositions(loadedPositions);
            console.log('✅ Loaded widgets from localStorage:', activeWidgets.value);
        } catch (error) {
            console.error('Failed to parse saved widget state:', error);
        }
    } else {
        console.log('ℹ️ No saved widget state found');
    }
};

// Verbesserte Funktion zur Bestimmung der Iconposition
/**
 * Rasterplaetze fuer Symbole ohne gespeicherte Position.
 *
 * Vorher gab diese Funktion jedem Symbol ohne Eintrag dieselbe
 * Standardposition (20, 20) zurueck. Zwei neue Symbole lagen damit exakt
 * uebereinander - auf der Arbeitsflaeche standen "Dashboard" und "Schwarzes
 * Brett" buchstaeblich am selben Punkt und ihre Namen ueberdruckten sich.
 *
 * Jetzt bekommt jedes von ihnen den naechsten freien Platz im Raster. Belegt
 * sind dabei sowohl die gespeicherten Positionen als auch die, die in diesem
 * Durchgang schon vergeben wurden. Die Reihenfolge haengt an der Liste der
 * Programme und ist damit ueber Neuaufbauten hinweg stabil.
 */
const SPALTE = 100;   // waagerechter Abstand zweier Symbole
const ZEILE = 116;    // senkrechter Abstand
const RAND = 20;

const NAEHE = 60;     // ab hier ueberdecken sich zwei Symbole sichtbar

const berechnetePositionen = computed(() => {
    const gespeichert = iconPositions.value || {};
    // Belegt sind die gespeicherten Plaetze - und zwar nach Abstand, nicht
    // nach exakter Koordinate: gespeicherte Positionen liegen selten genau
    // auf dem Raster, und ein Vergleich auf Gleichheit haette sie dann
    // uebersehen.
    const belegt = Object.values(gespeichert).map(pos => ({ x: pos.x, y: pos.y }));
    const istFrei = platz =>
        !belegt.some(
            b => Math.abs(b.x - platz.x) < NAEHE && Math.abs(b.y - platz.y) < NAEHE,
        );

    const zeilenProSpalte = Math.max(
        1,
        Math.floor((window.innerHeight - 120) / ZEILE),
    );

    const ergebnis = {};
    let n = 0;
    for (const app of desktopApps.value) {
        if (gespeichert[app.id]) continue;
        let platz;
        let versuche = 0;
        do {
            const spalte = Math.floor(n / zeilenProSpalte);
            const zeile = n % zeilenProSpalte;
            platz = { x: RAND + spalte * SPALTE, y: RAND + zeile * ZEILE };
            n += 1;
            versuche += 1;
        } while (!istFrei(platz) && versuche < 200);
        belegt.push(platz);
        ergebnis[app.id] = platz;
    }
    return ergebnis;
});

const getIconPosition = appId => {
    const savedPositions = iconPositions.value || {};
    if (savedPositions[appId]) {
        return savedPositions[appId];
    }
    return berechnetePositionen.value[appId] || { x: RAND, y: RAND };
};

// Window minimize function (bleibt gleich)
const minimizeWindow = (windowId: string) => {
    // Find the window and set its minimized state to true
    const window = openWindows.value.find(w => w.id === windowId);
    if (window) {
        window.minimized = true;
    }

    // If this was the active window, clear the active window ID
    if (activeWindowId.value === windowId) {
        activeWindowId.value = null;

        // Set the next visible window as active if there are any
        const visibleWindows = openWindows.value.filter(w => !w.minimized);
        if (visibleWindows.length > 0) {
            // Find the visible window with the highest z-index
            // Annahme: Ihre Fensterobjekte haben eine zIndex Eigenschaft
            const topWindow = visibleWindows.reduce((prev, current) =>
                (current.zIndex > prev.zIndex) ? current : prev
            );
            activeWindowId.value = topWindow.id;
        }
    }
};

// Handle Maximize Request from AppWindow
// Renamed from maximizeWindow to avoid confusion with AppWindow's own maximizeWindow function
const handleMaximizeRequest = (windowId: string) => {
    const window = openWindows.value.find(w => w.id === windowId);
    if (window) {
        // Set maximized to true. Do NOT toggle here, the restore button/event handles false.
        window.maximized = true;
        window.minimized = false; // Maximizing implies not minimized
        console.log(`Eltern: Fenster ${windowId} als maximiert markiert.`);
        // Position/Größe werden vom AppWindow über CSS gesetzt (fixed, 100% etc.)
        // Hier im Eltern müssen wir nur den Zustand im Datenmodell ändern.

        // Also focus the window
        focusWindow(windowId); // Stellen Sie sicher, dass focusWindow die Z-Index Logik korrekt handhabt.
    }
};

// >>> NEU: Handle Restore Request from AppWindow <<<
const handleRestoreRequest = (payload: { id: string, state: { x: number, y: number, width: number, height: number } }) => {
    const window = openWindows.value.find(w => w.id === payload.id);
    if (window) {
        console.log(`Eltern: Fenster ${payload.id} wiederherstellen auf x:${payload.state.x}, y:${payload.state.y}, ${payload.state.width}x${payload.state.height}`);
        // Set maximized to false
        window.maximized = false;
        window.minimized = false; // Restoring implies not minimized

        // Apply the saved position and size received from the child component
        window.x = payload.state.x;
        window.y = payload.state.y;
        window.width = payload.state.width;
        window.height = payload.state.height;

        // Also focus the window
        focusWindow(payload.id);
    }
};

// Handle Update Position event from AppWindow (after drag)
// Expects the full payload object { id, x, y, width, height }
const handleUpdatePosition = (payload: { id: string, x: number, y: number, width: number, height: number }) => {
    const window = openWindows.value.find(w => w.id === payload.id);
    // Important: Only update position if NOT maximized or minimized (prevent conflicts)
    if (window && !window.maximized && !window.minimized) {
        console.log(`Eltern: Fenster ${payload.id} Position aktualisiert auf x:${payload.x}, y:${payload.y}`);
        window.x = payload.x;
        window.y = payload.y;
        // We don't update width/height here, only position changes after drag
        // window.width = payload.width;
        // window.height = payload.height;
    }
};

// Handle Update Size event from AppWindow (after resize)
// Expects the full payload object { id, x, y, width, height }
const handleUpdateSize = (payload: { id: string, x: number, y: number, width: number, height: number }) => {
    const window = openWindows.value.find(w => w.id === payload.id);
    // Important: Only update size/position if NOT maximized or minimized
    if (window && !window.maximized && !window.minimized) {
        console.log(`Eltern: Fenster ${payload.id} Größe/Position aktualisiert auf ${payload.width}x${payload.height} @ x:${payload.x}, y:${payload.y}`);
        // Update width and height
        window.width = payload.width;
        window.height = payload.height;
        // >>> KORREKTUR: Update x and y as well, they might change during resize! <<<
        window.x = payload.x;
        window.y = payload.y;
    }
};

// Funktion zum Aktualisieren der Icon-Position
const updateIconPosition = (appId, position) => {
    // Kollisionserkennung - prüfen, ob die neue Position bereits belegt ist
    const iconWidth = 88; // Desktop-Icon Breite
    const iconHeight = 108; // Desktop-Icon Höhe
    
    // Offset für Randbereich des Icons bestimmen
    const collisionThreshold = 80; // Kollisionserkennung mit Puffer
    
    let hasCollision = false;
    let collidedWith = null;
    
    // Nur für die Icons prüfen, die tatsächlich angezeigt werden (zu denen der Benutzer Zugriff hat)
    const displayedApps = desktopApps.value;
    
    // Maximale Bildschirmgröße für Randerkennung
    const maxX = window.innerWidth - iconWidth;
    const maxY = window.innerHeight - iconHeight - 60; // 60px Taskleiste berücksichtigen
    
    // Position im sichtbaren Bereich begrenzen
    position.x = Math.max(20, Math.min(position.x, maxX));
    position.y = Math.max(20, Math.min(position.y, maxY));
    
    // Auf Kollisionen mit anderen sichtbaren Icons prüfen
    displayedApps.forEach(app => {
        if (app.id === appId) return; // Nicht mit sich selbst vergleichen
        
        const existingPos = getIconPosition(app.id);
        
        // Kollisionsberechnung - überprüfen ob Überlappung existiert
        const xOverlap = Math.abs(position.x - existingPos.x) < collisionThreshold;
        const yOverlap = Math.abs(position.y - existingPos.y) < collisionThreshold;
        
        if (xOverlap && yOverlap) {
            hasCollision = true;
            collidedWith = { id: app.id, position: existingPos };
        }
    });
    
    // Bei Kollision neue Position berechnen
    if (hasCollision && collidedWith) {
        console.log(`Kollision erkannt mit ${collidedWith.id}, platziere Icon daneben`);
        
        // Entscheide, ob rechts oder unten platziert werden soll
        // Priorisiere rechts, wenn genug Platz ist
        
        // Wenn rechts genug Platz ist, dort platzieren
        if (collidedWith.position.x + iconWidth + 20 < maxX) {
            position.x = collidedWith.position.x + 130;
            position.y = collidedWith.position.y;
        }
        // Sonst unter dem kollidierenden Icon platzieren
        else {
            position.x = collidedWith.position.x;
            position.y = collidedWith.position.y + 140;
        }
        
        // Rekursiv prüfen, ob die neue Position frei ist
        // (begrenzen auf maximal 10 Versuche, um Endlosschleifen zu vermeiden)
        let attempts = 0;
        const findFreePosition = (pos, maxAttempts = 10) => {
            if (attempts >= maxAttempts) return pos;
            attempts++;
            
            let hasAnotherCollision = false;
            
            // Nur mit sichtbaren Apps auf Kollision prüfen
            displayedApps.forEach(app => {
                const existingAppId = app.id;
                if (existingAppId === appId) return;
                
                const existingPos = getIconPosition(existingAppId);
                const xOverlap = Math.abs(pos.x - existingPos.x) < collisionThreshold;
                const yOverlap = Math.abs(pos.y - existingPos.y) < collisionThreshold;
                
                if (xOverlap && yOverlap) {
                    hasAnotherCollision = true;
                    
                    // Neue Position berechnen - alternierend rechts/unten
                    if (pos.x + iconWidth + 20 < maxX) {
                        pos.x = pos.x + 130;
                    } else {
                        pos.x = 20; // Zurück an den linken Rand
                        pos.y = pos.y + 140;
                    }
                    
                    // Position im sichtbaren Bereich begrenzen
                    pos.x = Math.max(20, Math.min(pos.x, maxX));
                    pos.y = Math.max(20, Math.min(pos.y, maxY));
                    
                    return; // Erste Kollision reicht für diese Iteration
                }
            });
            
            // Wenn wieder eine Kollision auftrat, rekursiv weitersuchen
            return hasAnotherCollision ? findFreePosition(pos, maxAttempts) : pos;
        };
        
        position = findFreePosition(position);
    }
    
    // Aktualisierte Position im Store speichern - ohne Fallback
    uiStore.updateIconPosition(appId, position);
    
    // Speichere Icon-Positionen mit Debouncing
    saveIconPositions();
};

// Füge eine neue ref-Variable hinzu für den Ladestatus
const isLoadingSettings = ref(false); // Auf false setzen, damit die Icons sofort angezeigt werden

// Laden der lokalen Einstellungen (Fallback)
const loadLocalSettings = () => {
    // Icon-Positionen
    const savedPositions = localStorage.getItem('desktop-icon-positions');
    if (savedPositions) {
        try {
            iconPositions.value = JSON.parse(savedPositions);

            // Zusätzliche Überprüfung, ob es wirklich ein Objekt ist
            if (typeof iconPositions.value !== 'object' || iconPositions.value === null) {
                console.warn('Loaded local icon positions is not an object, creating empty object');
                iconPositions.value = {};
                generateDefaultIconPositions();
            }
        } catch (error) {
            console.error('Fehler beim Laden der Icon-Positionen:', error);
            iconPositions.value = {};
            generateDefaultIconPositions();
        }
    } else {
        iconPositions.value = {};
        generateDefaultIconPositions();
    }

    // Hintergrundbild
    const savedBg = localStorage.getItem('desktop-background');
    if (savedBg) {
        desktopBackgroundImage.value = savedBg;
    }
};

const saveIconPositions = () => {
    // Stelle sicher, dass iconPositions.value ein Objekt ist
    if (typeof iconPositions.value !== 'object' || iconPositions.value === null) {
        console.warn(
            'iconPositions.value is not an object in saveIconPositions',
            iconPositions.value
        );
        return; // Exit early if invalid data
    }

    // Speichere im localStorage (als Fallback)
    localStorage.setItem('desktop-icon-positions', JSON.stringify(iconPositions.value));

    // Speichere im Backend with debouncing
    debouncedSaveDesktopSettings();
};

// Speichere die Desktop-Einstellungen im Backend
const saveDesktopSettings = async () => {
    try {
        // Stelle sicher, dass iconPositions.value ein Objekt ist
        if (typeof iconPositions.value !== 'object' || iconPositions.value === null) {
            console.warn(
                'iconPositions.value is not an object in saveDesktopSettings',
                iconPositions.value
            );
            return; // Exit early if invalid data
        }

        // Überprüfen, ob die Icon-Positionen leer sind
        const hasIconPositions = Object.keys(iconPositions.value).length > 0;

        // Speicherung nur durchführen, wenn Icon-Positionen vorhanden sind
        if (!hasIconPositions) {
            console.warn('Keine Icon-Positionen vorhanden - Backend-Speicherung wird übersprungen');
            return;
        }

        // Sammle alle Desktop-Einstellungen für die API
        // JSON-Daten vorbereiten - WICHTIG: icon_positions muss ein String sein, kein Objekt
        const settings = {
            background: desktopBackgroundImage.value,
            icon_positions: JSON.stringify(iconPositions.value),  // Immer als String senden
            theme: uiStore.desktopTheme || 'dark'
        };

        // Debug-Log für die Daten
        console.log('Saving desktop settings with positions:', {
            background: desktopBackgroundImage.value,
            iconCount: Object.keys(iconPositions.value).length,
            positionSample: Object.entries(iconPositions.value).slice(0, 2),
            theme: uiStore.desktopTheme || 'dark'
        });
        
        // Add cache busting timestamp 
        const timestamp = Date.now();
        
        // Make API call with properly formatted JSON data
        const response = await apiClientAuth.post('/desktop/', 
            settings,
            {
                params: { 
                    action: 'saveSettings',
                    _t: timestamp
                }
            }
        );

        if (response.data && response.data.success) {
            console.log('Desktop settings saved successfully:', response.data.message);
        } else {
            console.warn('Unexpected response when saving desktop settings:', response.data);
        }

        // Speichere die Icon-Positionen auch im UI Store (ohne Hintergrundbild)
        uiStore.$patch({
            desktopIconPositions: iconPositions.value,
        });
        
        // Speichere im localStorage (als Fallback)
        localStorage.setItem('desktop-icon-positions', JSON.stringify(iconPositions.value));
    } catch (error) {
        console.error('Error saving desktop settings:', error);
    }
};

// Debounced version of saveDesktopSettings to prevent excessive API calls
const debouncedSaveDesktopSettings = debounce(saveDesktopSettings, 1000);

// Hintergrundbild ändern
const changeBackground = (path: string) => {
    desktopBackgroundImage.value = path;

    // Speichere im localStorage (als Fallback)
    localStorage.setItem('desktop-background', path);

    // Speichere die Änderung auch im Backend
    saveDesktopSettings();
};

// Icon auswählen
const selectIcon = (appId: string) => {
    selectedIcon.value = appId;
};

// Window management
const updateWindowPosition = (windowId: string, x: number, y: number) => {
    const window = openWindows.value.find(w => w.id === windowId);
    if (window) {
        window.x = x;
        window.y = y;
    }
};

const updateWindowSize = (windowId: string, width: number, height: number) => {
    const window = openWindows.value.find(w => w.id === windowId);
    if (window) {
        window.width = width;
        window.height = height;
    }
};

// Ensure a savePositionsTimeout ref is defined
const savePositionsTimeout = ref(null);

// Debug helper functions
const debugIconState = () => {
    console.log('Current Icon Positions State:', {
        iconPositions: iconPositions.value,
        uiStore: uiStore,
        localStorage: localStorage.getItem('desktop-icon-positions'),
        desktopApps: desktopApps.value,
    });
    toast.info(t('toast.debugInfoLogged'));
};

const forceGenerateIcons = async () => {
    try {
        // Show loading screen
        isResettingLayout.value = true;

        console.log('🔄 Resetting icon layout with compact arrangement...');

        // Minimum display time for loading screen
        const MIN_RESET_DURATION = 1500; // 1.5 seconds
        const resetStartTime = Date.now();

        const iconPosObj = generateCompactIconLayout(iconSortMode.value);

        if (Object.keys(iconPosObj).length === 0) {
            isResettingLayout.value = false;
            toast.error('No icons to arrange');
            return;
        }

        const settings = {
            background: desktopBackgroundImage.value,
            icon_positions: iconPosObj,
            theme: uiStore.desktopTheme || 'dark',
        };

        await desktopApi.saveSettings(settings);
        console.log('✅ Icons successfully regenerated');
        localStorage.setItem('desktop-icon-positions', JSON.stringify(iconPosObj));

        if (uiStore?.updateAllIconPositions) {
            uiStore.updateAllIconPositions(iconPosObj);
        }

        // Calculate remaining time to reach minimum reset duration
        const elapsedTime = Date.now() - resetStartTime;
        const remainingTime = Math.max(0, MIN_RESET_DURATION - elapsedTime);

        if (remainingTime > 0) {
            console.log(`⏱️ Waiting additional ${remainingTime}ms for smooth transition`);
            await new Promise(resolve => setTimeout(resolve, remainingTime));
        }

        // Set flag before reload to maintain loading screen
        sessionStorage.setItem('desktop-resetting', 'true');

        toast.success(t('toast.iconsRegeneratedSuccess'));

        // Reload page - loading screen will persist via sessionStorage flag
        setTimeout(() => {
            window.location.reload();
        }, 300);
    } catch (error) {
        console.error('❌ Error:', error);
        isResettingLayout.value = false;
        toast.error(t('toast.iconsSaveError') || 'Error saving');
    }
};

// Weitere Anwendungen für den Desktop, die keiner Gruppe angehören
const appDefinitions = [
    {
        id: 'dashboard',
        title: t('tabs.dashboard'),
        icon: 'mdi-view-dashboard',
        route: '/dashboard',
        color: 'var(--k-accent)',
    },
    {
        id: 'calendar',
        title: t('tabs.calendar'),
        icon: 'mdi-calendar',
        route: '/calendar',
        color: '#2FA36B',
    },
    {
        id: 'todo',
        title: t('tabs.todoList'),
        icon: 'mdi-checkbox-marked-circle-outline',
        route: '/todo',
        color: '#2FA36B',
    },
    {
        id: 'profile',
        title: t('tabs.profile'),
        icon: 'mdi-account-outline',
        route: '/profile',
        color: '#a855f7',
    },
    {
        id: 'filemanager',
        title: t('tabs.fileManager'),
        icon: 'mdi-folder-outline',
        route: '/filemanager',
        color: '#2FA36B',
    },
    {
        id: 'map',
        title: t('tabs.map'),
        icon: 'mdi-map',
        route: '/map',
        color: '#D9534F',
    },
    {
        id: 'template',
        title: t('tabs.template'),
        icon: 'mdi-text-box-multiple-outline',
        route: '/template',
        color: '#6366f1',
    },
    {
        id: 'person',
        title: t('tabs.person'),
        icon: 'mdi-account-multiple-outline',
        route: '/person',
        color: '#3D7DD8',
    },
    {
        id: 'vehicleFile',
        title: t('tabs.vehicleFile'),
        icon: 'mdi-car',
        route: '/vehicleFile',
        color: '#3D7DD8',
    },
    {
        id: 'apartmentFile',
        title: t('tabs.apartmentFile'),
        icon: 'mdi-home',
        route: '/apartmentFile',
        color: '#3D7DD8',
    },
    {
        id: 'application',
        title: t('tabs.application'),
        icon: 'mdi-account-plus-outline',
        route: '/application',
        color: '#2FA36B',
    },
    {
        id: 'trainingassign',
        title: t('tabs.training'),
        icon: 'mdi-school-outline',
        route: '/trainingassign',
        color: '#2FA36B',
    },
    {
        id: 'tests',
        title: t('tabs.tests'),
        icon: 'mdi-notebook-check-outline',
        route: '/test',
        color: '#0284c7',
    },
    {
        id: 'calculator',
        title: t('tabs.calculator'),
        icon: 'mdi-calculator',
        color: '#0ea5e9',
        isApp: true,
    },
    {
        id: 'minesweeper',
        title: t('tabs.minesweeper'),
        icon: 'mdi-mine',
        color: '#f43f5e',
        isApp: true,
    },
    {
        id: 'solitaire',
        title: t('tabs.solitaire'),
        icon: 'mdi-cards',
        color: '#22c55e',
        isApp: true,
    },
    {
        id: 'sudoku',
        title: t('tabs.sudoku'),
        icon: 'mdi-grid',
        color: '#f97316',
        isApp: true,
    },
    {
        id: 'weather',
        title: t('desktop.weather'),
        icon: 'mdi-weather-partly-cloudy',
        color: 'var(--k-accent)',
        isApp: true,
    },
    {
        id: 'whiteboard',
        title: t('tabs.whiteboard'),
        icon: 'mdi-drawing-box',
        route: '/whiteboard',
        color: '#6366f1',
        isApp: true,
    },
    {
        id: 'waterduck',
        title: t('tabs.waterduck'),
        icon: 'mdi-duck',
        color: '#1976D2',
        route: '/waterduck',
        isApp: true
    },
];

// Nach userPermissions computed property, füge diese Funktion hinzu:
// Dokumentenbereiche abrufen
async function fetchDocumentAreas() {
    if (!authStore.isLoggedIn) {
        console.log('⚠️ Not fetching document areas - user not logged in');
        return;
    }

    console.log('🔍 Fetching document areas...');
    loadingDocAreas.value = true;
    try {
        const response = await apiClientAuth.post('/document/?action=getAreas');
        console.log('📂 Document areas response:', response.data);

        // Bereiche werden jetzt serverseitig gefiltert - nur Bereiche mit can_read=true werden zurückgegeben
        documentAreas.value = response.data;

        console.log('📂 Document areas available:', documentAreas.value);
    } catch (error) {
        console.error('❌ Failed to fetch document areas:', error);
        toast.error(t('toast.loadDocumentAreasError'));
        documentAreas.value = [];
    } finally {
        loadingDocAreas.value = false;
    }
}

// Blackboard-Bereiche abrufen
async function fetchBlackboardAreas() {
    if (!authStore.isLoggedIn) {
        console.log('⚠️ Not fetching blackboard areas - user not logged in');
        return;
    }

    console.log('🔍 Fetching blackboard areas...');
    loadingBlackboardAreas.value = true;
    try {
        const response = await apiClientAuth.get('/blackboard/?action=getAreas');
        console.log('📋 Blackboard areas response:', response.data);

        // Filter areas that are active and user has read permission
        blackboardAreas.value = response.data.filter((area: BlackboardArea) =>
            area.is_active && area.permissions?.can_read
        );

        console.log('📋 Blackboard areas available:', blackboardAreas.value);
    } catch (error) {
        console.error('❌ Failed to fetch blackboard areas:', error);
        blackboardAreas.value = [];
    } finally {
        loadingBlackboardAreas.value = false;
    }
}

// Neue Funktion zum direkten Öffnen des ersten Dokumentenbereichs (für Tests)
const testOpenFirstDocArea = () => {
    if (documentAreas.value && documentAreas.value.length > 0) {
        const firstArea = documentAreas.value[0];
        console.log('🧪 TEST: Testing direct navigation to document area:', firstArea);
        
        const route = `/documentarea/${firstArea.key}`;
        console.log('🧪 TEST: Navigating directly to:', route);
        
        router.push(route).catch(error => {
            console.error('❌ TEST: Navigation error:', error);
            toast.error(t('toast.openDocumentAreaErrorPrefix') + error.message);
        });
    } else {
        console.warn('⚠️ No document areas available for testing');
        toast.warning(t('toast.noDocumentAreasWarning'));
    }
};
</script>

<style scoped>
.desktop-container {
    position: relative;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background-image: var(--desktop-background-image);
    font-family: var(--desktop-font-family);
    color: var(--desktop-text);
}

.desktop-background {
    position: relative;
    width: 100%;
    height: 100%;
    z-index: 1;
}

/*
   Das Raster der Arbeitsflaeche, wie .desk-grid im Entwurf: zwei feine Linien
   im 28-px-Abstand, stark abgeschwaecht. Es macht sichtbar, dass die Symbole
   auf einem Raster liegen, ohne mit dem Hintergrundbild zu konkurrieren.

   Ueber einem Foto wuerde es unruhig wirken - deshalb liegt es nur dort, wo
   die Arbeitsflaeche ihre eigene Farbe traegt.
*/
.desktop-background-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 2;
    background-image:
        linear-gradient(to right, var(--k-line) 1px, transparent 1px),
        linear-gradient(to bottom, var(--k-line) 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: 0.12;
}

.desktop-area {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: var(--desktop-gap);
    padding: var(--desktop-padding);
    width: 100%;
    height: calc(100vh - var(--taskbar-height));
    overflow: hidden;
    position: relative;
    z-index: 3;
}

.desktop-icons {
    /*display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    grid-auto-rows: 120px;
    gap: 8px;*/
    padding: var(--desktop-padding);
    padding-right: calc(var(--desktop-widget-width, 320px) + var(--desktop-padding) * 2);
    width: calc(100% - var(--desktop-widget-width, 320px));
    height: calc(100vh - var(--taskbar-height));
    position: relative;
    z-index: 4;
    overflow-y: auto;
}

.desktop-windows {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: calc(100vh - var(--taskbar-height));
    pointer-events: none;
    z-index: 20;
}

.desktop-widgets {
    position: absolute;
    top: 0;
    right: 0;
    width: var(--desktop-widget-width, 320px);
    height: 100%;
    pointer-events: none;
    z-index: 10;
    background: rgba(0, 0, 0, 0.02);
    border-right: 1px solid var(--k-line);
}

.desktop-widgets > * {
    pointer-events: auto;
}

@keyframes widgets-container-slide {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Die schwebenden Schaltflaechen unten rechts sind entfallen - ihre Aktionen
   stehen jetzt im Kontextmenue der Arbeitsflaeche. */
.desk-menu-scrim {
    position: fixed;
    inset: 0;
    z-index: 3000;
}

.desk-menu {
    position: fixed;
    z-index: 3001;
    width: 210px;
    padding: 4px;
    background: var(--k-raised);
    border: 1px solid var(--k-line-strong);
    border-radius: 6px;
    box-shadow: none;
}

.desk-menu__label {
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--k-ink-faint);
    margin: 0;
    padding: 6px 8px 4px;
}

.desk-menu__item {
    display: block;
    width: 100%;
    text-align: left;
    font: inherit;
    font-size: 12.5px;
    color: var(--k-ink);
    background: transparent;
    border: 0;
    border-radius: 4px;
    padding: 6px 8px;
    cursor: pointer;
}

.desk-menu__item:hover {
    background: var(--k-row-hover);
}

.desk-menu__item.is-on {
    color: var(--k-accent);
    background: var(--k-accent-weak);
}

.desk-menu__rule {
    border: 0;
    border-top: 1px solid var(--k-line);
    margin: 4px 0;
}

.folder-popup {
    position: fixed;
    width: var(--folder-width);
    max-height: 70vh;
    background: var(--k-raised);
    border: 1px solid var(--k-line);
    border-radius: 6px;
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.5),
        0 8px 24px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    animation: folderAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes folderAppear {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.folder-popup-header {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: var(--k-raised);
    border-bottom: 1px solid var(--k-line);
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.3px;
    border-radius: 6px 20px 0 0;
}

.folder-items {
    padding: 12px;
    overflow-y: auto;
    max-height: 70vh;
}

.folder-items::-webkit-scrollbar {
    width: 8px;
}

.folder-items::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    margin: 4px 0;
}

.folder-items::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 4px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.folder-items::-webkit-scrollbar-thumb:hover {
    background: var(--k-row-hover);
}

.folder-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 4px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--k-row-hover);
    border: 1px solid var(--k-line);
    font-weight: 500;
}

.folder-item:hover {
    background: var(--k-row-hover);
    transform: translateX(4px) scale(1.01);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    border-color: var(--k-line);
}

.folder-item:active {
    transform: translateX(2px) scale(0.99);
}

.folder-empty {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    color: var(--desktop-text-muted);
    font-style: italic;
}

/*
   Hier stand ein zweiter Satz Regeln fuer .desktop-icon - Breite 110, Hoehe
   120, Radius 4, dazu ein Hover mit Anheben und Schatten. Weil Vue die
   Bereichskennung der Elternkomponente auch auf die Wurzel des Kindes setzt,
   trafen sie dieselben Elemente wie die Regeln in DesktopIcon.vue und gewannen
   als spaetere Definition. Die Kachel blieb deshalb auf der alten Groesse, und
   das Anheben beim Zeigen kam von hier - nicht aus dem Symbol selbst.

   Wie ein Symbol aussieht, gehoert in DesktopIcon.vue. Diese Ansicht bestimmt
   nur, wo es liegt.
*/

.icon-image {
    width: var(--desktop-icon-size);
    height: var(--desktop-icon-size);
    margin-bottom: 6px;
    object-fit: contain;
    filter: var(--desktop-icon-filter);
    transition: transform var(--animation-duration-fast) var(--animation-easing);
}

.desktop-icon:hover .icon-image {
    transform: scale(1.1);
}

.icon-label {
    width: 100%;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: var(--desktop-text);
    text-shadow: var(--text-shadow);
    padding: 2px 4px;
    border-radius: var(--border-radius-sm);
}

.start-menu {
    position: fixed;
    bottom: var(--taskbar-height);
    left: var(--desktop-padding);
    width: 320px;
    max-height: calc(80vh - var(--taskbar-height));
    background-color: rgba(var(--desktop-bg-dark-1), var(--glass-bg-opacity));
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-large);
    border: 1px solid var(--desktop-border);
    overflow: hidden;
    z-index: 1001;
    display: flex;
    flex-direction: column;
    transform-origin: bottom left;
    animation: startMenuAppear var(--animation-duration-normal) var(--animation-easing-bounce);
}

@keyframes startMenuAppear {
    from {
        transform: scale(0.9) translateY(10px);
        opacity: 0;
    }
    to {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

.start-menu-header {
    padding: 16px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--desktop-border);
}

.start-menu-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.start-menu-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--desktop-accent-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: var(--k-ink);
    font-size: 18px;
}

.start-menu-user-info {
    flex: 1;
}

.start-menu-username {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.start-menu-email {
    font-size: 12px;
    opacity: 0.7;
}

.start-menu-content {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    scrollbar-width: thin;
    scrollbar-color: var(--scrollbar-thumb-color) var(--scrollbar-track-color);
}

.start-menu-content::-webkit-scrollbar {
    width: var(--scrollbar-width);
}

.start-menu-content::-webkit-scrollbar-track {
    background: var(--scrollbar-track-color);
}

.start-menu-content::-webkit-scrollbar-thumb {
    background-color: var(--scrollbar-thumb-color);
    border-radius: var(--border-radius-md);
}

.start-menu-section {
    margin-bottom: 20px;
}

.start-menu-section-title {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.7;
    margin-bottom: 8px;
    color: var(--desktop-text-secondary);
}

.start-menu-items {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.start-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px;
    border-radius: var(--border-radius-sm);
    transition: background-color var(--animation-duration-fast) var(--animation-easing);
    cursor: pointer;
}

.start-menu-item:hover {
    background-color: rgba(var(--desktop-bg-dark-2), 0.5);
    transform: var(--button-hover-translate);
}

.start-menu-item-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 4px;
    object-fit: contain;
    filter: var(--desktop-icon-filter);
}

.start-menu-item-label {
    font-size: 12px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
    color: var(--desktop-text);
}

.start-menu-footer {
    display: flex;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid var(--desktop-border);
    background-color: rgba(var(--desktop-bg-dark-2), 0.3);
}

.start-menu-power,
.start-menu-settings {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: var(--border-radius-sm);
    font-size: 13px;
    transition: background-color var(--animation-duration-fast) var(--animation-easing);
    color: var(--desktop-text-secondary);
}

.start-menu-power:hover,
.start-menu-settings:hover {
    background-color: rgba(var(--desktop-bg-dark-2), 0.7);
    color: var(--desktop-text);
}

.desktop-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--desktop-text);
    text-shadow: var(--text-shadow);
    background-color: rgba(0, 0, 0, 0.2);
    padding: 20px;
    border-radius: var(--border-radius-md);
    z-index: 5;
}

/* Ladebalken-Styles */
.desktop-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.desktop-loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: var(--k-ink);
}

.desktop-loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: var(--k-ink);
    animation: spin 1s ease-in-out infinite;
    margin-bottom: 20px;
}

.desktop-loading-text {
    font-size: 18px;
    font-weight: 500;
    color: var(--k-ink);
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

