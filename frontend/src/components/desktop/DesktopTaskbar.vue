<!-- Desktop Taskbar Component -->
<template>
    <div class="taskbar" 
         :class="[
            position, 
            { 
                'compact-mode': compact,
                'auto-hide': autoHide,
                'active': isTaskbarActive
            }
         ]"
         @mouseenter="autoHide && activateTaskbar()">
        <!-- Start Button -->
        <div class="start-button" @click="$emit('toggle-start-menu')">
            <v-icon size="24">mdi-desktop-tower</v-icon>
            <span v-if="!compact" class="start-text">{{ t('desktop.start') }}</span>
        </div>

        <!-- Search Button -->
        <div class="search-button" @click="handleSearchClick" :title="t('desktop.searchTooltip')">
            <v-icon size="22">mdi-magnify</v-icon>
        </div>

        <!-- Pinned Applications -->
        <div class="taskbar-pinned-apps">
            <div v-for="app in pinnedApps" :key="app.id" 
                 class="taskbar-app pinned"
                 @click="openApp(app)">
                <template v-if="isImagePath(app.icon)">
                    <img :src="app.icon" class="taskbar-app-image" :alt="app.title">
                </template>
                <template v-else>
                    <v-icon size="24" :color="app.color">{{ app.icon }}</v-icon>
                </template>
            </div>
        </div>

        <!-- Running Applications -->
        <div class="taskbar-apps">
            <!-- Einzelne Apps und Gruppen -->
            <div v-for="(group, groupIndex) in groupedApps" :key="groupIndex">
                <!-- App Gruppe -->
                <div v-if="group.apps.length > 1" 
                     class="taskbar-app-group"
                     :class="{ active: isGroupActive(group.apps) }"
                     @click="toggleGroupExpanded(groupIndex)">
                    
                    <div class="app-group-icon">
                        <div class="app-icon-stack">
                            <template v-if="isImagePath(group.apps[0].icon)">
                                <img :src="group.apps[0].icon" class="taskbar-app-image" :alt="group.apps[0].title">
                            </template>
                            <template v-else>
                                <v-icon size="24" :color="group.color || getAppColor(group.apps[0])">{{ group.apps[0].icon }}</v-icon>
                            </template>
                            <div class="app-count">{{ group.apps.length }}</div>
                        </div>
                        <span v-if="!compact" class="taskbar-app-title">{{ getReadableCategory(group.category) }}</span>
                    </div>
                    
                    <!-- Expended Group Apps -->
                    <div v-if="expandedGroups[groupIndex]" class="group-expanded-apps">
                        <div class="group-header" v-if="group.apps.length > 2">
                            {{ getReadableCategory(group.category) }} ({{ group.apps.length }})
                        </div>
                        <div v-for="app in group.apps" 
                             :key="app.id"
                             class="group-app-item"
                             :class="{ active: activeAppId === app.id }"
                             @click.stop="activateApp(app)">
                            <template v-if="isImagePath(app.icon)">
                                <img :src="app.icon" class="taskbar-app-image small" :alt="app.title">
                            </template>
                            <template v-else>
                                <v-icon size="20" :color="getAppColor(app)">{{ app.icon }}</v-icon>
                            </template>
                            <span class="group-app-title">{{ app.title }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Einzelne App -->
                <div v-else
                    class="taskbar-app"
                    :class="{ minimized: group.apps[0].minimized, active: activeAppId === group.apps[0].id }"
                    @click="activateApp(group.apps[0])">
                    <template v-if="isImagePath(group.apps[0].icon)">
                        <img :src="group.apps[0].icon" class="taskbar-app-image" :alt="group.apps[0].title">
                    </template>
                    <template v-else>
                        <v-icon size="24" :color="getAppColor(group.apps[0])">{{ group.apps[0].icon }}</v-icon>
                    </template>
                    <span v-if="!compact" class="taskbar-app-title">{{ group.apps[0].title }}</span>
                </div>
            </div>
        </div>

        <!-- System Tray -->
        <div class="system-tray">           
            <!-- Weather Widget -->
            <taskbar-weather 
                :temperature="weatherData.temperature"
                :condition="weatherData.condition"
                :location="weatherData.location"
                :weather-icon="weatherData.icon"
                :compact="compact"
                @click="openWeather"
            />

            <!-- Reports Button -->
            <div class="tray-item reports-button" @click="openReportsApp">
                <v-icon size="20">mdi-file-document-outline</v-icon>
                <div v-if="openReports > 0" class="notification-badge">{{ formattedReportsCount }}</div>
            </div>
            
            <div class="tray-item" v-if="!compact">
                <v-icon size="20">mdi-wifi</v-icon>
            </div>
            <div class="tray-item" v-if="!compact">
                <v-icon size="20">mdi-volume-high</v-icon>
            </div>
            <div class="tray-item" v-if="!compact">
                <v-icon size="20">mdi-battery</v-icon>
            </div>
            
            <!-- Language Switcher -->
            <div class="tray-item language-switcher" @click="toggleLanguageMenu">
                <span class="language-code">{{ currentLanguageCode }}</span>
                
                <!-- Language Menu -->
                <div v-if="showLanguageMenu" class="language-menu">
                    <div 
                        v-for="lang in availableLanguages" 
                        :key="lang.code"
                        class="language-option"
                        :class="{ active: currentLocale === lang.code }"
                        @click.stop="changeLanguage(lang.code)"
                    >
                        <span class="language-flag">{{ lang.flag }}</span>
                        <span class="language-name">{{ lang.name }}</span>
                        <v-icon v-if="currentLocale === lang.code" size="16" class="check-icon">mdi-check</v-icon>
                    </div>
                </div>
            </div>
            
            <div class="tray-item clock" @click="openCalendar">
                <div class="time">{{ formattedTime }}</div>
                <div class="date">{{ formattedDate }}</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, type PropType } from 'vue';
import TaskbarWeather from './TaskbarWeather.vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const authStore = useAuthStore();
const { t, locale } = useI18n();

// Define interface for app objects
interface App {
    id: string;
    appId?: string;
    title: string;
    icon: string;
    minimized?: boolean;
    zIndex?: number;
    category?: string; // Zur Gruppierung
    isPinned?: boolean;
    color?: string;
    route?: string;
}

const activeAppId = ref<string | null>(null);
const compact = ref(false);
const position = ref('bottom'); // 'bottom', 'left', 'right', 'top'
const showPositionControls = ref(false);
const expandedGroups = ref<{[key: number]: boolean}>({});

// Language switcher state
const showLanguageMenu = ref(false);
const availableLanguages = ref([
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'en', name: 'English', flag: '🇬🇧' }
]);

const currentLocale = computed(() => locale.value);
const currentLanguageCode = computed(() => {
    return currentLocale.value.toUpperCase().slice(0, 3);
});

const emit = defineEmits(['toggle-start-menu', 'toggle-search', 'window-click', 'open-app', 'taskbar-position-changed']);
const openReports = ref(0); // Mock count for open reports
import { apiClientAuth, desktopApi } from '@/api'; // API Client importieren

interface Props {
  openApps?: any[]
  currentTime?: Date
  weatherData?: Record<string, any>
  autoHide?: boolean
  allApps?: App[]
}

const props = withDefaults(defineProps<Props>(), {
  openApps: () => [],
  weatherData: () => ({}),
  autoHide: false,
  allApps: () => []
});

// Reactive weather data
const internalWeatherData = ref({
  temperature: '22',
  condition: 'Teilweise bewölkt',
  location: 'Los Santos',
  icon: 'mdi-weather-partly-cloudy'
});

// Computed weather data that merges props and internal data
const weatherData = computed(() => ({
  ...internalWeatherData.value,
  ...props.weatherData
}));

// Compute pinned apps from allApps
const pinnedApps = computed(() => {
    const pinned = props.allApps?.filter(app => app.isPinned) || [];
    console.log('All apps received by taskbar:', props.allApps?.length || 0);
    console.log('Pinned apps in taskbar:', pinned);
    console.log('WaterDuck found:', pinned.find(app => app.id === 'waterduck'));
    return pinned;
});

// Auto-Hide Feature Funktionalität
const isTaskbarActive = ref(false);

const activateTaskbar = () => {
    isTaskbarActive.value = true;
    // Nach 3 Sekunden Inaktivität wieder ausblenden
    setTimeout(() => {
        if (!expandedGroups.value || Object.keys(expandedGroups.value).length === 0) {
            isTaskbarActive.value = false;
        }
    }, 3000);
};

// Verbesserte App-Gruppierung
const groupedApps = computed(() => {
    const groups: { category: string, apps: App[], color?: string }[] = [];
    
    // Intelligentere Gruppierung nach Typ/Kategorie
    props.openApps.forEach(app => {
        let appCategory = app.category || app.appId || 'uncategorized';
        let groupColor = '';
        
        // Intelligentere Kategorisierung basierend auf der App-ID
        if (app.id.includes('message') || app.id.includes('mail') || app.id.includes('chat')) {
            appCategory = 'communication';
            groupColor = '#f59e0b'; // Amber
        } else if (app.id.includes('file') || app.id.includes('document')) {
            appCategory = 'documents';
            groupColor = '#8b5cf6'; // Purple
        } else if (app.id.includes('admin') || app.id.includes('settings')) {
            appCategory = 'system';
            groupColor = '#64748b'; // Slate
        } else if (app.id.includes('report') || app.id.includes('analytics')) {
            appCategory = 'reports';
            groupColor = '#ec4899'; // Pink
        }
        
        const existingGroup = groups.find(g => g.category === appCategory);
        
        if (existingGroup) {
            existingGroup.apps.push(app);
        } else {
            groups.push({ 
                category: appCategory, 
                apps: [app],
                color: groupColor
            });
        }
    });
    
    // Sortieren der Apps innerhalb der Gruppe nach Namen
    groups.forEach(group => {
        group.apps.sort((a, b) => a.title.localeCompare(b.title));
    });
    
    return groups;
});

// Change taskbar position
const changePosition = (newPosition: string) => {
    position.value = newPosition;
    localStorage.setItem('taskbar-position', newPosition);
    emit('taskbar-position-changed', newPosition);
};

// Load taskbar position from local storage
const loadTaskbarPosition = () => {
    const savedPosition = localStorage.getItem('taskbar-position');
    if (savedPosition) {
        position.value = savedPosition;
    }
};

// Toggle group expanded state
const toggleGroupExpanded = (groupIndex: number) => {
    // Alle anderen Gruppen schließen
    Object.keys(expandedGroups.value).forEach(key => {
        if (parseInt(key) !== groupIndex) {
            expandedGroups.value[parseInt(key)] = false;
        }
    });
    
    // Die angeklickte Gruppe umschalten
    expandedGroups.value[groupIndex] = !expandedGroups.value[groupIndex];
    
    // Verhindern, dass die Taskbar zu früh geschlossen wird
    if (expandedGroups.value[groupIndex]) {
        setTimeout(() => {
            // Event-Listener für das Schließen bei Klick außerhalb hinzufügen
            const closeExpandedGroup = (e: MouseEvent) => {
                const target = e.target as HTMLElement;
                if (!target.closest('.taskbar-app-group') || 
                    target.closest('.taskbar-app') ||
                    target.closest('.group-app-item')) {
                    expandedGroups.value[groupIndex] = false;
                    window.removeEventListener('click', closeExpandedGroup);
                }
            };
            
            window.addEventListener('click', closeExpandedGroup);
        }, 100);
    }
};

// Check if any app in the group is active
const isGroupActive = (apps: App[]) => {
    return apps.some(app => app.id === activeAppId.value);
};

// Update active app when clicked
const activateApp = (app: App) => {
    activeAppId.value = app.id;
    emit('window-click', app.id);
};

// Handle search button click
const handleSearchClick = () => {
    console.log('🔍 Search button clicked in Taskbar');
    emit('toggle-search');
};

// Get color for app icon
const getAppColor = (app: App) => {
    // Find app in the allApps list to get its color
    const appMatch = props.allApps?.find(a => a.id === app.appId);
    return appMatch?.color || 'var(--k-accent)';
};

// Fetch weather data from API
const fetchWeatherData = async () => {
    try {
        const response = await apiClientAuth.get('/weather/', {
            params: { action: 'getWeather' }
        });
        
        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
            const today = response.data[0];
            const avgTemp = Math.round((today.max_temp + today.min_temp) / 2);
            
            internalWeatherData.value = {
                temperature: avgTemp.toString(),
                condition: today.condition || 'Teilweise bewölkt',
                location: 'Los Santos',
                icon: getWeatherIcon(today.condition || 'Partly Cloudy')
            };
        }
    } catch (error) {
        console.error('Failed to fetch weather data for taskbar:', error);
        // Keep default values
    }
};

// Weather icon mapping function
const getWeatherIcon = (condition: string): string => {
    const conditionLower = condition.toLowerCase();
    
    if (conditionLower.includes('sun') || conditionLower.includes('clear')) {
        return 'mdi-weather-sunny';
    } else if (conditionLower.includes('cloud')) {
        return 'mdi-weather-cloudy';
    } else if (conditionLower.includes('rain')) {
        return 'mdi-weather-rainy';
    } else if (conditionLower.includes('storm') || conditionLower.includes('thunder')) {
        return 'mdi-weather-lightning';
    } else if (conditionLower.includes('snow')) {
        return 'mdi-weather-snowy';
    } else if (conditionLower.includes('fog') || conditionLower.includes('mist')) {
        return 'mdi-weather-fog';
    } else if (conditionLower.includes('wind')) {
        return 'mdi-weather-windy';
    } else if (conditionLower.includes('night')) {
        return 'mdi-weather-night';
    }
    
    return 'mdi-weather-partly-cloudy';
};

// Open weather app
const openWeather = () => {
    emit('open-app', {
        id: 'weather',
        title: t('desktop.weather'),
        icon: 'mdi-weather-partly-cloudy',
        route: '/weather',
        color: '#0ea5e9'
    });
};

// Open calendar app
const openCalendar = () => {
    emit('open-app', {
        id: 'calendar',
        title: t('desktop.calendar'),
        icon: 'mdi-calendar',
        route: '/calendar',
        color: '#ef4444'
    });
};

// Open reports app
const openReportsApp = () => {
    emit('open-app', {
        id: 'reports',
        title: t('desktop.reports'),
        icon: 'mdi-file-document-outline',
        route: '/report',
        color: '#ec4899'
    });
};

// Format open reports count (show 9+ if more than 9)
const formattedReportsCount = computed(() => {
    return openReports.value > 9 ? '9+' : openReports.value;
});

// Mock data for apps - in a real implementation, this would be passed down
// Commented out because we're now receiving allApps from props
// const allApps = ref([
//     { id: 'dashboard', title: t('dashboard'), icon: 'mdi-view-dashboard', color: 'var(--k-accent)' },
//     { id: 'users', title: t('users'), icon: 'mdi-account-multiple', color: '#10b981' },
//     { id: 'messages', title: t('messages'), icon: 'mdi-email', color: '#f59e0b' },
//     { id: 'calendar', title: t('calendar'), icon: 'mdi-calendar', color: '#ef4444' },
//     { id: 'files', title: t('fileManager'), icon: 'mdi-folder', color: '#8b5cf6' },
//     { id: 'reports', title: t('reports'), icon: 'mdi-file-document', color: '#ec4899' },
//     { id: 'settings', title: t('settings'), icon: 'mdi-cog', color: '#64748b' },
// ]);

// Format current time
const formattedTime = computed(() => {
    const hours = props.currentTime.getHours().toString().padStart(2, '0');
    const minutes = props.currentTime.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
});

// Format current date
const formattedDate = computed(() => {
    const options: Intl.DateTimeFormatOptions = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    };
    return props.currentTime.toLocaleDateString('de-DE', options);
});

// Simulate fetching open reports count
const fetchOpenReportsCount = async () => {
    // In einer realen Implementierung würde hier ein API-Aufruf gemacht werden
    // Für Demo-Zwecke setzen wir einen festen Wert
    openReports.value = 3; // Fester Wert für die Anzahl der offenen Berichte

    try {
        const reportsResponse = await apiClientAuth.get(
            '/report/?action=getReportsToProcessCount'
        );
        openReports.value = reportsResponse.data.count || 0;
    } catch (error) {
        console.error('Fehler beim Abrufen der Berichts-Statistiken:', error);
    }
};

// Watch for active window changes from parent
watch(
    () => props.openApps,
    newApps => {
        // Find the app with the highest z-index (the active one)
        const activeApp = [...newApps].sort((a, b) => (b.zIndex ?? 0) - (a.zIndex ?? 0))[0];
        if (activeApp) {
            activeAppId.value = activeApp.id;
        }
    },
    { deep: true }
);

// Responsive taskbar
const checkScreenSize = () => {
    compact.value = window.innerWidth < 768;
};

onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    window.addEventListener('click', handleClickOutside);
    fetchOpenReportsCount();
    loadTaskbarPosition();
    fetchWeatherData(); // Load weather data on mount
    
    // Load saved language preference
    const savedLanguage = localStorage.getItem('userLanguage');
    if (savedLanguage && ['de', 'en'].includes(savedLanguage)) {
        locale.value = savedLanguage;
    }
    
    // Refresh weather data every 10 minutes
    const weatherInterval = setInterval(fetchWeatherData, 600000);
    
    // Store interval reference for cleanup
    onUnmounted(() => {
        clearInterval(weatherInterval);
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize);
    window.removeEventListener('click', handleClickOutside);
});

// Funktion hinzufügen, die prüft, ob es sich um einen Bildpfad handelt
const isImagePath = (icon: string): boolean => {
    if (!icon) return false;
    // Prüfen, ob es mit http://, https:// beginnt oder ein relativer Pfad ist
    return icon.startsWith('http://') || 
           icon.startsWith('https://') || 
           icon.startsWith('/') ||
           icon.endsWith('.png') || 
           icon.endsWith('.jpg') || 
           icon.endsWith('.jpeg') || 
           icon.endsWith('.svg') || 
           icon.endsWith('.webp');
};

// Helper function to get readable category
const getReadableCategory = (category: string | undefined): string => {
    if (!category) return 'Apps';
    
    // Konvertiere camelCase zu normalem Text mit Leerzeichen
    const readable = category.replace(/([A-Z])/g, ' $1')
                           .replace(/^./, str => str.toUpperCase());
    
    // Spezielle Übersetzungen für gängige Kategorien
    const translations: Record<string, string> = {
        'communication': 'Kommunikation',
        'documents': 'Dokumente',
        'system': 'System',
        'reports': 'Berichte',
        'admin': 'Verwaltung',
        'settings': 'Einstellungen',
        'misc': 'Sonstiges',
        'uncategorized': 'Nicht kategorisiert'
    };
    
    return translations[category.toLowerCase()] || readable;
};

// Function to open an app
const openApp = (app: App) => {
    emit('open-app', app);
};

// Language switcher functions
const toggleLanguageMenu = () => {
    showLanguageMenu.value = !showLanguageMenu.value;
};

const changeLanguage = (langCode: string) => {
    locale.value = langCode;
    localStorage.setItem('userLanguage', langCode);
    showLanguageMenu.value = false;
};

// Close language menu when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.language-switcher')) {
        showLanguageMenu.value = false;
    }
};
</script>

<style scoped>
/* Zusätzliche Styles für Layout-Optimierungen */

/* Taskbar Positionierung */
.taskbar.top {
    top: 0;
    bottom: auto;
    border-top: none;
    border-bottom: 1px solid rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    margin: 8px 12px 0 12px;
}

.taskbar.left, .taskbar.right {
    width: var(--taskbar-height);
    height: calc(100% - 24px);
    flex-direction: column;
    margin: 12px 8px;
    padding: var(--desktop-padding) 0;
}

.taskbar.left {
    left: 0;
    right: auto;
    border-top: none;
    border-left: 1px solid rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
}

.taskbar.right {
    left: auto;
    right: 0;
    border-top: none;
    border-right: 1px solid rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2);
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
}

/* Anpassungen für vertikale Ausrichtung */
.taskbar.left .taskbar-apps, 
.taskbar.right .taskbar-apps {
    flex-direction: column;
    margin: 20px 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.taskbar.left .system-tray, 
.taskbar.right .system-tray {
    flex-direction: column;
    margin-top: auto;
    margin-left: 0;
    padding: 6px 0;
    border-left: none;
    border-top: 1px solid var(--k-line);
    padding-top: 18px;
}

.taskbar.left .clock, 
.taskbar.right .clock {
    align-items: center;
    text-align: center;
}

/* App-Gruppierung Styles */
/*
   Eine Aufgabe in der Leiste: 22 px, Radius 4, gesenkte Flaeche mit Linie.
   Das laufende Fenster traegt Akzentflaeche und Akzentrand - dasselbe
   Kennzeichen wie der aktive Punkt in der Seitenleiste, damit beide Modi
   dieselbe Sprache sprechen.

   Vorher: 40 px hoch, Radius 12, zwei Verlaeufe aus fest verdrahteten
   Dunkelwerten und beim Zeigen ein Sprung um drei Pixel nach oben.
*/
.taskbar-app-group {
    position: relative;
    display: flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 120ms ease, border-color 120ms ease;
    color: var(--k-ink-muted);
    white-space: nowrap;
    font-size: 12.5px;
    background: var(--k-sunken);
    border: 1px solid var(--k-line);
}

.taskbar-app-group.active {
    background: var(--k-accent-weak);
    border-color: var(--k-accent-line);
    color: var(--k-accent);
}

.taskbar-app-group:hover {
    background: var(--k-row-hover);
    color: var(--k-ink);
}

.app-group-icon {
    display: flex;
    align-items: center;
}

.app-icon-stack {
    position: relative;
}

.app-count {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background-color: var(--desktop-accent-blue);
    color: var(--k-ink);
    border-radius: 50%;
    font-size: 9px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.group-expanded-apps {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 0;
    background-color: rgba(var(--desktop-bg-dark-1), 0.95);
    border-radius: 6px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 220px;
    border: 1px solid var(--k-line);
    z-index: 100;
    animation: fade-in 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    max-height: 80vh;
    overflow-y: auto;
}

.group-app-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    position: relative;
    overflow: hidden;
}

.group-app-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, 
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15), 
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.05) 60%,
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0) 100%);
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    opacity: 0;
}

.group-app-item:hover::before {
    width: 100%;
    opacity: 1;
}

.group-app-item:hover {
    background-color: var(--k-row-hover);
    transform: translateX(3px);
}

.group-app-item.active {
    background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15);
}

.group-app-title {
    margin-left: 12px;
    font-size: 13px;
    font-weight: 500;
    position: relative;
    z-index: 1;
}

.group-app-icon {
    position: relative;
    z-index: 1;
}

/* Verbesserter App-Stack in Gruppen */
.app-icon-stack {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.app-count {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: var(--k-accent-weak);
    color: var(--k-ink);
    border-radius: 50%;
    font-size: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.4);
    z-index: 2;
}

/* Verbesserte Animationen für die erweiterten App-Menüs */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Verbesserte Positionierung für verschiedene Taskbar-Positionen */
.taskbar.top .group-expanded-apps {
    bottom: auto;
    top: calc(100% + 10px);
    animation: fade-in-top 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

@keyframes fade-in-top {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.taskbar.left .group-expanded-apps {
    bottom: auto;
    left: 100%; /* Das Menü erscheint rechts von der Taskbar */
    right: auto;
    top: 0;
    margin-left: 12px; /* Extra Abstand zwischen Taskbar und Menü */
    transform: none;
    animation: fade-in-left 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

@keyframes fade-in-left {
    from { opacity: 0; transform: translateX(-10px) scale(0.98); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}

.taskbar.right .group-expanded-apps {
    bottom: auto;
    right: 100%;
    left: auto;
    top: 0;
    margin-right: 12px;
    animation: fade-in-right 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

@keyframes fade-in-right {
    from { opacity: 0; transform: translateX(10px) scale(0.98); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}

/* Icon-Styles verfeinert */
.taskbar-app-image {
    width: 24px;
    height: 24px;
    object-fit: contain;
    border-radius: 6px;
    transition: all 0.25s ease;
}

.taskbar-app:hover .taskbar-app-image,
.taskbar-app-group:hover .taskbar-app-image {
    transform: scale(1.1);
}

.taskbar-app-image.small {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

/* Auto-Hide für die Taskbar (aktiviert durch eine Klasse) */
.taskbar.auto-hide {
    transform: translateY(calc(100% - 5px));
    transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

.taskbar.auto-hide:hover,
.taskbar.auto-hide.active {
    transform: translateY(0);
}

.taskbar.left.auto-hide {
    transform: translateX(calc(-100% + 5px));
}

.taskbar.left.auto-hide:hover,
.taskbar.left.auto-hide.active {
    transform: translateX(0);
}

.taskbar.right.auto-hide {
    transform: translateX(calc(100% - 5px));
}

.taskbar.right.auto-hide:hover,
.taskbar.right.auto-hide.active {
    transform: translateX(0);
}

.taskbar.top.auto-hide {
    transform: translateY(calc(-100% + 5px));
}

.taskbar.top.auto-hide:hover,
.taskbar.top.auto-hide.active {
    transform: translateY(0);
}

/* Position Controls */
.position-controls {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(var(--desktop-bg-dark-1), 0.95);
    border-radius: 6px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.25);
    padding: 12px;
    border: 1px solid var(--k-line);
    z-index: 1001;
    animation: fade-in 0.2s ease;
    width: 250px;
}

.position-control-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--desktop-text);
    text-align: center;
}

.position-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.position-buttons button {
    padding: 8px 12px;
    border-radius: 8px;
    background-color: rgba(var(--desktop-bg-dark-2), 0.4);
    border: 1px solid var(--k-line);
    color: var(--desktop-text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.position-buttons button:hover {
    background-color: rgba(var(--desktop-bg-dark-2), 0.6);
    color: var(--desktop-text);
}

.position-buttons button.active {
    background-color: var(--desktop-accent-blue);
    color: var(--k-ink);
}

.close-controls {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    color: var(--desktop-text-secondary);
    transition: color 0.2s ease;
}

.close-controls:hover {
    color: var(--desktop-text);
}

.layout-settings {
    position: relative;
}

.layout-settings:hover::after {
    content: 'Taskbar-Position ändern';
    position: absolute;
    top: -30px;
    right: 0;
    background-color: rgba(var(--desktop-bg-dark-1), 0.95);
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    box-shadow: var(--shadow-small);
}

.taskbar.top .position-controls,
.taskbar.left .position-controls,
.taskbar.right .position-controls {
    bottom: auto;
    top: calc(100% + 10px);
}

.taskbar.left .position-controls,
.taskbar.right .position-controls {
    left: 0;
    transform: none;
}

/* Base Taskbar Styles - Adding these to make the taskbar visible */
/*
   Taskleiste nach Entwurf: 34 px, Flaeche und Trennlinie aus den Merkern.

   Vorher: 60 px hoch, ein fest verdrahteter dunkler Verlauf mit 25 px
   Weichzeichner und 180 % Saettigung, dazu drei Schatten nach oben. Der
   Verlauf kippte im hellen Modus nicht mit, und die 26 Pixel Unterschied
   fehlen der Arbeitsflaeche auf jedem Bildschirm.
*/
.taskbar {
    --taskbar-height: 48px;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: var(--taskbar-height);
    background: var(--k-surface);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 7px;
    padding: 0 10px;
    z-index: 1000;
    border-top: 1px solid var(--k-line);
}

/* Start Button mit modernerem Design */
.start-button {
    display: flex;
    align-items: center;
    padding: 0 18px;
    height: 44px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.75));
    margin-right: 12px;
    background: linear-gradient(135deg,
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15),
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.08));
    border: 1px solid rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.start-button:hover {
    background: linear-gradient(135deg,
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.25),
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15));
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 8px 20px rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2),
        0 4px 8px rgba(0, 0, 0, 0.15);
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
    border-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.35);
}

.start-button:active {
    transform: translateY(-1px) scale(0.98);
}

.start-text {
    margin-left: 8px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Search Button */
.search-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.75));
    margin-right: 12px;
    background: var(--k-row-hover);
    border: 1px solid var(--k-line);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.search-button:hover {
    background: var(--k-row-hover);
    transform: translateY(-3px) scale(1.08);
    box-shadow:
        0 8px 20px rgba(0, 0, 0, 0.25),
        0 4px 8px rgba(0, 0, 0, 0.15);
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
    border-color: var(--k-line);
}

.search-button:active {
    transform: translateY(-1px) scale(1);
}

/* Taskbar Apps Section */
.taskbar-apps {
    display: flex;
    flex: 1;
    gap: 6px;
    overflow-x: auto;
    padding: 0 10px;
    margin-left: 10px;
    scrollbar-width: none; /* Firefox */
}

.taskbar-apps::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge */
}

/* App Item */
.taskbar-app {
    display: flex;
    align-items: center;
    height: 44px;
    padding: 0 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.75));
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    background: var(--k-row-hover);
    border: 1px solid var(--k-line);
}

.taskbar-app::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg,
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0),
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 1),
        rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0));
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(-50%);
    opacity: 0;
    border-radius: 2px 2px 0 0;
}

.taskbar-app:hover::before {
    width: 80%;
    opacity: 1;
}

.taskbar-app.active {
    background: var(--k-accent-weak);
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
    border-color: var(--k-accent-line);
    box-shadow: none;
}

.taskbar-app.active::before {
    width: 80%;
    opacity: 1;
}

.taskbar-app:hover {
    background: var(--k-row-hover);
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 6px 18px rgba(0, 0, 0, 0.25),
        0 3px 8px rgba(0, 0, 0, 0.15);
    border-color: var(--k-line);
}

.taskbar-app:active {
    transform: translateY(-1px) scale(0.98);
}

.taskbar-app-title {
    margin-left: 12px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.taskbar-app-image {
    width: 24px;
    height: 24px;
    object-fit: contain;
    filter: none; /* Ensure image is not filtered */
}

/* System Tray */
.system-tray {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
    padding-left: 20px;
    border-left: 1px solid var(--k-line);
    height: 100%;
    padding-right: 16px;
}

.tray-item {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    background: var(--k-row-hover);
    border: 1px solid var(--k-line);
}

.tray-item:hover {
    background: var(--k-row-hover);
    transform: translateY(-3px) scale(1.05);
    box-shadow:
        0 6px 18px rgba(0, 0, 0, 0.25),
        0 3px 8px rgba(0, 0, 0, 0.15);
    border-color: var(--k-line);
}

.tray-item:active {
    transform: translateY(-1px) scale(1);
}

/* Clock */
.clock {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: 12px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.8));
    padding: 8px 14px;
    border-radius: 6px;
    background: var(--k-row-hover);
    border: 1px solid var(--k-line);
}

.clock:hover {
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
    background: var(--k-row-hover);
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 6px 18px rgba(0, 0, 0, 0.25),
        0 3px 8px rgba(0, 0, 0, 0.15);
    border-color: var(--k-line);
}

.clock:active {
    transform: translateY(-1px) scale(0.98);
}

.time {
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.date {
    font-size: 11px;
    opacity: 0.85;
    letter-spacing: 0.3px;
    font-weight: 500;
}

/* Notification Badges */
.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: var(--k-surface);
    color: var(--k-ink);
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 4px 12px rgba(239, 68, 68, 0.4),
        0 2px 6px rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.5);
    animation: badge-pulse 2s infinite;
}

@keyframes badge-pulse {
    0%, 100% {
        box-shadow:
            0 4px 12px rgba(239, 68, 68, 0.4),
            0 2px 6px rgba(0, 0, 0, 0.3);
    }
    50% {
        box-shadow:
            0 4px 16px rgba(239, 68, 68, 0.6),
            0 2px 8px rgba(0, 0, 0, 0.4);
    }
}

/* Compact Mode */
.taskbar.compact-mode {
    padding: 0 10px;
}

.taskbar.compact-mode .taskbar-app {
    padding: 0 10px;
}

.taskbar.compact-mode .system-tray {
    padding-left: 10px;
    gap: 8px;
}

/* Verbesserte Positionierung für Group-Expanded-Apps */
@media screen and (max-width: 768px) {
    .taskbar.left .group-expanded-apps {
        left: 100%; /* konsistent mit der normalen Positionierung */
        max-height: 80vh;
        overflow-y: auto;
    }
}

/* Sicherstellen, dass das Menü immer sichtbar ist, auch wenn es am Rand erscheinen würde */
.group-expanded-apps {
    max-height: 80vh; /* Verhindert, dass das Menü zu groß wird */
    overflow-y: auto; /* Scrollbar hinzufügen, wenn nötig */
}

/* Sorgt dafür, dass das Menü immer im Sichtbereich bleibt */
.taskbar-app-group {
    position: relative;
}

/* Verbesserte Animation für bessere Sichtbarkeit */
@keyframes group-slide-in-left {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(5px); }
}

.taskbar.left .group-expanded-apps {
    animation: group-slide-in-left 0.2s ease forwards;
}

/* Styles für den Header der Gruppen-Apps */
.group-header {
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.taskbar-pinned-apps {
    display: flex;
    height: 100%;
    align-items: center;
    border-right: 1px solid var(--desktop-border, rgba(255, 255, 255, 0.1));
    padding-right: 8px;
    margin-right: 8px;
}

.taskbar-app.pinned {
    position: relative;
    padding: 0 10px;
    height: 40px;
    display: flex;
    align-items: center;
    transition: all var(--animation-duration-fast, 0.2s) var(--animation-easing, ease);
    cursor: pointer;
    border-radius: 6px;
}

.taskbar-app.pinned::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 8px;
    right: 8px;
    height: 2px;
    background-color: var(--desktop-accent-color);
    opacity: 0;
    transition: opacity var(--animation-duration-fast) var(--animation-easing);
}

.taskbar-app.pinned:hover {
    background-color: rgba(var(--desktop-bg-dark-2, 31, 41, 55), 0.5);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.taskbar-app.pinned:hover::after {
    opacity: 0.5;
}

.taskbar-app.pinned .taskbar-app-image {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

/* Language Switcher Styles */
.language-switcher {
    position: relative;
    min-width: 40px;
    justify-content: center;
}

.language-code {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: var(--desktop-text, rgba(255, 255, 255, 0.9));
}

.language-menu {
    position: absolute;
    bottom: calc(100% + 10px);
    right: 0;
    background-color: rgba(var(--desktop-bg-dark-1), 0.95);
    border-radius: 6px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    padding: 8px;
    min-width: 180px;
    z-index: 1001;
    border: 1px solid var(--k-line);
    animation: fade-in 0.2s ease;
}

.language-option {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
}

.language-option:hover {
    background-color: var(--k-row-hover);
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.language-option.active {
    background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15);
    color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.language-flag {
    font-size: 18px;
    margin-right: 10px;
}

.language-name {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
}

.check-icon {
    color: var(--desktop-accent-blue, var(--k-accent));
}

/* Positioning adjustments for different taskbar positions */
.taskbar.top .language-menu {
    bottom: auto;
    top: calc(100% + 10px);
}

.taskbar.left .language-menu {
    bottom: auto;
    left: calc(100% + 10px);
    right: auto;
}

.taskbar.right .language-menu {
    bottom: auto;
    right: calc(100% + 10px);
    left: auto;
}

/* ============================================================
   MASSE DER LEISTE

   Der Entwurf gibt fuer die Taskleiste 34 px an - allerdings in
   einer Arbeitsflaeche, die dort nur 400 px hoch ist. Das ist ein
   Modell im Kleinen, kein 1:1-Mass: auf einem echten Bildschirm
   waere eine 34-px-Leiste ein Streifen, in dem Symbol und Name
   kaum Platz haben.

   Uebernommen werden deshalb die Verhaeltnisse, nicht die
   absoluten Zahlen. Die Leiste misst 48 px wie unter Windows,
   und alles darin waechst im selben Verhaeltnis mit:

       Leiste       34 -> 48
       Aufgabe      22 -> 32
       Schrift    11.5 -> 12.5
       Symbol       15 -> 18

   Gestalt und Verhalten bleiben, wie der Entwurf sie zeigt:
   gesenkte Flaeche mit Linie, das laufende Fenster in
   Akzentflaeche mit Akzentrand, die Uhr rechts in Festbreite.
   ============================================================ */
.taskbar .v-icon {
    font-size: 18px !important;
    width: 18px;
    height: 18px;
}

.taskbar .taskbar-app-image {
    width: 18px;
    height: 18px;
    object-fit: contain;
}

.taskbar .start-button,
.taskbar .search-button,
.taskbar .taskbar-app,
.taskbar .taskbar-app-group,
.taskbar .system-tray > * {
    height: 32px;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0 10px;
    border-radius: 5px;
    font-size: 12.5px;
    color: var(--k-ink-muted);
    cursor: pointer;
}

.taskbar .start-button:hover,
.taskbar .search-button:hover,
.taskbar .taskbar-app:hover,
.taskbar .system-tray > *:hover {
    background: var(--k-row-hover);
    color: var(--k-ink);
}

.taskbar .start-text {
    font-size: 12.5px;
    font-weight: 550;
}

/* Die Zaehlmarke einer Gruppe bleibt im Knopf. */
.taskbar .app-count {
    bottom: -3px;
    right: -3px;
    min-width: 14px;
    height: 14px;
    font-size: 9px;
    border: 0;
    box-shadow: none;
    background: var(--k-accent);
    color: var(--k-on-fill);
}

/* Die Uhr steht in Festbreite, damit sie beim Ticken nicht springt. */
.taskbar .clock,
.taskbar .taskbar-clock {
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 12.5px;
    font-variant-numeric: tabular-nums;
    color: var(--k-ink-muted);
    margin-left: auto;
}

/* Die Uhr traegt Zeit und Datum untereinander - sie braucht deshalb die
   volle Hoehe eines Leistenelements, sonst steht das Datum ausserhalb ihrer
   Flaeche. Genau das war zu sehen. */
.taskbar .tray-item.clock,
.taskbar .clock {
    height: 34px;
    flex-direction: column;
    justify-content: center;
    align-items: flex-end;
    gap: 0;
    line-height: 1.15;
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-variant-numeric: tabular-nums;
    color: var(--k-ink-muted);
}

.taskbar .tray-item.clock > *,
.taskbar .clock > * {
    font-size: 12px;
    white-space: nowrap;
}

</style>
