<template>
    <div
        v-show="!window.minimized"
        class="window"
        :class="{
            maximized: window.maximized,
            active: isActive,
            'snap-left': snapState === 'left', // Optional Snap classes
            'snap-right': snapState === 'right',
            'snap-top': snapState === 'top',
            dragging: isDraggingActive, // For the overlay
            resizing: isResizing,       // For visual indicators
        }"
        :style="windowStyle"
        :data-window-id="window.id"
        @mousedown="focusWindow"
        @keydown="handleKeyDown"
        tabindex="0"
        ref="windowRef"
    >
        <div class="window-titlebar" @mousedown="startDrag" @dblclick.stop.prevent="toggleMaximize">
            <div class="window-titlebar-left">
                <v-icon size="14" class="window-icon" :color="getIconColor">{{
                    window.icon
                }}</v-icon>
                <span class="window-title">{{ currentTitle }}</span>
            </div>

            <div class="window-controls">
                <button
                    v-if="window.route || window.appId"
                    class="window-control reload"
                    @mousedown.stop.prevent
                    @click.stop.prevent="reloadWindow"
                    :title="t('layout.reload')"
                >
                    <v-icon size="13">mdi-refresh</v-icon>
                </button>
                <button
                    class="window-control minimize"
                    @mousedown.stop.prevent
                    @click.stop.prevent="minimizeWindow"
                    :title="t('layout.minimize')"
                >
                    <v-icon size="13">mdi-minus</v-icon>
                </button>
                <button
                    v-if="!window.maximized"
                    class="window-control maximize maximize-button"
                    @mousedown.stop.prevent
                    @click.stop.prevent="maximizeWindow"
                    :title="t('layout.maximize')"
                >
                    <v-icon size="13">mdi-window-maximize</v-icon>
                </button>
                <button
                    v-else
                    class="window-control maximize restore-button"
                    @mousedown.stop.prevent
                    @click.stop.prevent="restoreWindow"
                    :title="t('layout.restore')"
                >
                    <v-icon size="13">mdi-window-restore</v-icon>
                </button>
                <button
                    class="window-control close"
                    @mousedown.stop.prevent
                    @click.stop.prevent="closeWindow"
                    :title="t('layout.close')"
                >
                    <v-icon size="13">mdi-close</v-icon>
                </button>
            </div>
        </div>

        <div class="window-content">
            <div v-if="isLoading && loadingStartTime && (Date.now() - loadingStartTime < 3000)" class="window-loading-overlay">
                <div class="window-loading-content">
                    <v-progress-circular indeterminate color="primary" size="50"></v-progress-circular>
                    <div class="mt-2">{{ t('layout.loading') }}</div>
                </div>
            </div>

            <div class="dynamic-component-container">
                <component
                    v-if="windowComponent"
                    :is="windowComponent"
                    :key="componentKey"
                    :window-id="window.id"
                    :desktop-window="true"
                    v-bind="windowProps"
                    :id="windowProps.id"
                    :meta="windowProps.meta || {}"
                    :canEdit="windowProps.canEdit"
                    :canDelete="windowProps.canDelete"
                    :canCreate="windowProps.canCreate"
                    @loaded="handleComponentLoaded"
                    class="dynamic-component"
                />

                <div v-else-if="!isLoading" class="window-placeholder">
                <v-icon size="48" opacity="0.5">{{ window.icon }}</v-icon>
                    <div class="placeholder-text">{{ window.title }} {{ t('layout.application') }}</div>
            </div>
        </div>
        </div>

        <div v-if="isDraggingActive" class="drag-overlay"></div>

        <template v-if="!window.maximized">
            <div class="resize-handle resize-handle-se" @mousedown.stop.prevent="e => startResize('se', e)"></div>
            <div class="resize-handle resize-handle-sw" @mousedown.stop.prevent="e => startResize('sw', e)"></div>
            <div class="resize-handle resize-handle-ne" @mousedown.stop.prevent="e => startResize('ne', e)"></div>
            <div class="resize-handle resize-handle-nw" @mousedown.stop.prevent="e => startResize('nw', e)"></div>
            <div class="resize-handle resize-handle-n" @mousedown.stop.prevent="e => startResize('n', e)"></div>
            <div class="resize-handle resize-handle-s" @mousedown.stop.prevent="e => startResize('s', e)"></div>
            <div class="resize-handle resize-handle-e" @mousedown.stop.prevent="e => startResize('e', e)"></div>
            <div class="resize-handle resize-handle-w" @mousedown.stop.prevent="e => startResize('w', e)"></div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, provide, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { getComponentForWindow, getRouteParams } from '@/registry/componentRegistry';
import { getWindowStore, removeWindowStore } from '@/stores/windowContext';
import { useAuthStore } from '@/stores/auth';

// --- Einbinden der alten App-Komponenten - Kann wahrscheinlich entfernt werden, wenn registry genutzt wird ---
// import CalculatorApp from './Calculator.vue';
// import MinesweeperApp from './Minesweeper.vue';
// import SolitaireApp from './Solitaire.vue';
// import SudokuApp from './Sudoku.vue';
// import WeatherAppComponent from './WeatherApp.vue';
// import WhiteboardApp from './WhiteboardApp.vue';
// import WaterDuckApp from './WaterDuck.vue';
// --- Ende Einbinden der alten App-Komponenten ---

// Define props
interface Props {
    window: Record<string, any>; // Erwartet ein Objekt mit { id, x, y, width, height, minimized, maximized, icon, title, ... }
    active?: boolean;
    allApps?: Array<any>;
}

const props = withDefaults(defineProps<Props>(), {
    active: false,
    allApps: () => []
});

// Define emits - Requests state changes from parent
const emit = defineEmits([
    'minimize', // Request to minimize
    'maximize', // Request to maximize (parent should set maximized = true)
    'restore',  // Request to restore (parent should set maximized = false and apply state)
    'close',    // Request to close
    'focus',    // Request to become active/focused (parent should update activeId and zIndex)
    'update-position', // Request to update x/y after drag (payload: { id, x, y, width, height })
    'update-size',     // Request to update width/height/x/y after resize (payload: { id, x, y, width, height })
    // Add events for snap if implementing snap outside this component
]);
const { t } = useI18n();

const router = useRouter();
const authStore = useAuthStore();

const windowStore = getWindowStore(props.window.id);
const { isLoading } = storeToRefs(windowStore);

// Refs for DOM element and local state within AppWindow
const windowRef = ref<HTMLElement | null>(null);
const isActive = ref(props.active); // Local copy of active state, synced by watcher

// Computed property for reactive title
const currentTitle = computed(() => {
    // If we have allApps and an appId, look up the current title
    if (props.allApps && props.allApps.length > 0 && props.window.appId) {
        const app = props.allApps.find(a => a.id === props.window.appId);
        if (app) {
            return app.title;
        }
    }
    // Fallback to the window's stored title
    return props.window.title;
});
const snapState = ref(''); // Local snap state (optional, or synced by parent prop)
const snapThreshold = 30; // Pixel-Abstand zum Rand für Snap-Erkennung (optional)

// State needed for restore function
const originalSizeBeforeMaximize = ref({ x: 0, y: 0, width: 0, height: 0 });

// Local state for drag/resize in progress (for CSS classes/overlays)
const isDraggingActive = ref(false); // For the drag overlay
const isDragging = ref(false); // Make this reactive
let dragStartX = 0;
let dragStartY = 0;
let initialX = 0; // Initial X from props.window.x when drag starts (used for final calculation)
let initialY = 0; // Initial Y from props.window.y when drag starts (used for final calculation)
// --- NEU: Lokale Refs für die VISUELLE Position während Drag/Resize ---
const currentX = ref(props.window.x);
const currentY = ref(props.window.y);
// --- Ende NEU ---

const isResizing = ref(false); // Make this reactive too
let resizeDirection = '';
let resizeStartX = 0;
let resizeStartY = 0;
let initialWidth = 0; // Initial width from props.window.width when resize starts (used for final calculation)
let initialHeight = 0; // Initial height from props.window.height when resize starts (used for final calculation)
let initialResizeX = 0; // Initial X from props.window.x when resize starts (needed for some directions)
let initialResizeY = 0; // Initial Y from props.window.y when resize starts (needed for some directions)
// --- NEU: Lokale Refs für die VISUELLE Größe während Resize ---
const currentWidth = ref(props.window.width);
const currentHeight = ref(props.window.height);
// --- Ende NEU ---

// Schlüssel zum erzwingen der Neuinstanziierung der Komponente beim Neuladen
const componentKey = ref(Date.now());

const loadingStartTime = ref<number | null>(null);

// Computed property for the dynamic component to render (resolved by registry)
const windowComponent = computed(() => {
    return getComponentForWindow(props.window.appId, props.window.route);
});

// Computed property for props passed to the dynamic component (app content)
const windowProps = computed(() => {
    console.log(`🔑 windowProps - Preparing props for window ${props.window.id}`);
    
    const componentProps: Record<string, any> = {
        windowId: props.window.id, // Pass window ID to the app component
        desktopWindow: true, // Indicate it's running in a desktop window
        hideLeftNav: true,   // Example flag
        hideTopNav: false,   // Example flag
        // Add default permission flags (will be overridden by getRouteParams if needed)
        canEdit: true,
        canDelete: true,
        canCreate: true,
    };
    
    if (props.window.appId) componentProps.appId = props.window.appId;
    
    if (props.window.route) {
        componentProps.route = props.window.route;
        console.log(`🔑 windowProps - Getting route params for ${props.window.route}`);
        const routeParams = getRouteParams(props.window.route);
        console.log(`🔑 windowProps - Route params returned:`, routeParams);
        Object.assign(componentProps, routeParams);
        
        // Create mock route meta for components that expect it
        componentProps.meta = {
            canEdit: routeParams.canEdit,
            canDelete: routeParams.canDelete,
            canCreate: routeParams.canCreate,
            ...routeParams.meta
        };
        
        console.log(`🔑 windowProps - Permission flags after route params:`, { 
            canEdit: componentProps.canEdit, 
            canDelete: componentProps.canDelete,
            'meta.canEdit': componentProps.meta.canEdit,
            'meta.canDelete': componentProps.meta.canDelete,
            allPermissions: routeParams.allPermissions || false
        });
        
        // Log all props being passed to component
        console.log(`🔑 windowProps - All component props:`, componentProps);
    }
    
    // Add any props from the window store
    if (windowStore.props) {
        console.log(`🔑 windowProps - Adding window store props`);
        Object.assign(componentProps, windowStore.props);
    }
    
    return componentProps;
});

// Dummy data for Weather app example (if used)
interface WeatherData { temperature: string; condition: string; location: string; icon: string; humidity: string; windSpeed: string; }
const weatherData = ref<WeatherData>({ temperature: '0°C', condition: 'Unbekannt', location: 'Berlin', icon: 'mdi-weather-cloudy', humidity: '0%', windSpeed: '0 km/h', });

const prepareWindowProps = () => {
    console.log(`🔑 prepareWindowProps - Setting up props for window ${props.window.id}`);
    
    const baseProps: Record<string, any> = { 
        desktopWindow: true, 
        hideLeftNav: true, 
        hideTopNav: false,
        // Default permissions
        canEdit: true,
        canDelete: true,
        canCreate: true,
    };
    
    if (props.window.route) { 
        console.log(`🔑 prepareWindowProps - Getting route params for ${props.window.route}`);
        const routeParams = getRouteParams(props.window.route);
        Object.assign(baseProps, routeParams);
        
        // Create mock route meta for components that expect it
        baseProps.meta = {
            canEdit: routeParams.canEdit,
            canDelete: routeParams.canDelete,
            canCreate: routeParams.canCreate, 
            ...routeParams.meta
        };
        
        console.log(`🔑 prepareWindowProps - Permission flags after route params:`, { 
            canEdit: baseProps.canEdit, 
            canDelete: baseProps.canDelete,
            'meta.canEdit': baseProps.meta.canEdit,
            'meta.canDelete': baseProps.meta.canDelete,
            allPermissions: routeParams.allPermissions || false
        });
    }
    
    if (props.window.appId === 'weather') { 
        Object.assign(baseProps, weatherData.value); 
    }
    
    console.log(`🔑 prepareWindowProps - Final props being set in store:`, baseProps);
    windowStore.setProps(baseProps);
};

const handleComponentLoaded = () => {
    console.log('Komponente wurde erfolgreich geladen für Fenster:', props.window.id);
    
    // Log the current permissions state
    console.log('🔑 Permissions when component loaded:', {
        'props.canEdit': windowProps.value.canEdit || false,
        'props.canDelete': windowProps.value.canDelete || false,
        'props.meta.canEdit': windowProps.value.meta?.canEdit || false,
        'props.meta.canDelete': windowProps.value.meta?.canDelete || false,
        'props.allPermissions': windowProps.value.allPermissions || false
    });
    
    windowStore.setLoading(false);
    loadingStartTime.value = null;
    if (windowRef.value) {
        windowRef.value.classList.add('component-loaded');
        setTimeout(() => { if (windowRef.value) { windowRef.value.classList.remove('component-loaded'); } }, 500);
    }
};

const reloadWindow = () => {
    console.log('Reloading window:', props.window.id);
    windowStore.setLoading(true);
    loadingStartTime.value = Date.now();
    
    // Aktualisiere den Komponenten-Schlüssel, um vollständige Neuinstanziierung zu erzwingen
    componentKey.value = Date.now();
    
    // Erstelle ein benutzerdefiniertes Event, um den Komponenten mitzuteilen, dass sie ihre Daten neu laden sollen
    const reloadEvent = new CustomEvent('force-reload', { detail: { timestamp: Date.now() } });
    
    nextTick(() => {
        // Bereite Props neu vor
        prepareWindowProps();
        
        // Sendet das Event an die dynamische Komponente
        if (windowRef.value) {
            const dynamicComponent = windowRef.value.querySelector('.dynamic-component');
            if (dynamicComponent) {
                dynamicComponent.dispatchEvent(reloadEvent);
            }
        }
        
        // Route-basiertes Neuladen, falls vorhanden
        if (props.window.route && router) {
            const currentRoute = props.window.route;
            
            // Setze einen neuen Timestamp-Parameter, um Cache zu umgehen
            const timestamp = Date.now();
            let routeWithParams = currentRoute;
            
            // Füge timestamp als query-Parameter hinzu
            if (routeWithParams.includes('?')) {
                routeWithParams = `${routeWithParams}&_reload=${timestamp}`;
            } else {
                routeWithParams = `${routeWithParams}?_reload=${timestamp}`;
            }
            
            // Kurzzeitig auf about:blank umleiten und dann zur Route mit Timestamp zurück
            windowStore.updateProps({ route: 'about:blank' });
            
            // Warten und dann Route mit Timestamp zurücksetzen
            setTimeout(() => {
                windowStore.updateProps({ route: routeWithParams });
                
                // Wenn Komponente mit API-Methode reload existiert, rufe diese auf
                if (windowComponent.value && typeof windowComponent.value.reload === 'function') {
                    windowComponent.value.reload();
                }
                
                // Bei Versionsverwaltung in Props (falls vorhanden) erhöhen
                if (windowStore.props && windowStore.props.version !== undefined) {
                    windowStore.updateProps({ 
                        version: (parseInt(windowStore.props.version) || 0) + 1 
                    });
                }
            }, 50);
        }
        
        // Timeout für Ladeindikator
        setTimeout(() => { 
            if (isLoading.value) { 
                console.warn('Loading component took too long.', props.window.id); 
                windowStore.setLoading(false); 
            } 
        }, 3000);
    });
};

const loadWeatherData = async () => {
     if (props.window.appId === 'weather') {
        console.log('Loading weather data for window:', props.window.id);
        setTimeout(() => {
             weatherData.value = { temperature: '18°C', condition: 'Teilweise bewölkt', location: 'Berlin, Deutschland', icon: 'mdi-weather-partly-cloudy', humidity: '65%', windSpeed: '12 km/h', };
             windowStore.updateProps(weatherData.value);
             console.log('Weather data loaded for window:', props.window.id);
        }, 1000);
     }
};
watch(() => props.window.appId, newAppId => { if (newAppId === 'weather') { loadWeatherData(); } }, { immediate: true });

// --- Dragging Functions (Updates local state, emits final on stop) ---
const startDrag = (event: MouseEvent) => {
    if (props.window.maximized || isDragging.value || isResizing.value) return;
    focusWindow();
    isDragging.value = true;
    isDraggingActive.value = true; // Show overlay

    dragStartX = event.clientX;
    dragStartY = event.clientY;
    initialX = props.window.x; // Store initial from props for final calculation
    initialY = props.window.y; // Store initial from props for final calculation

    // --- Initialize local state for visual feedback ---
    currentX.value = props.window.x;
    currentY.value = props.window.y;

    window.addEventListener('mousemove', dragWindow, { passive: false });
    window.addEventListener('mouseup', stopDrag);
    event.preventDefault();
    event.stopPropagation();
};

const dragWindow = (event: MouseEvent) => { // Fixed MouseMouseEvent typo
    if (!isDragging.value || props.window.maximized) return;
    // Removed requestAnimationFrame - direct reactivity update is fast enough
    const dx = event.clientX - dragStartX;
    const dy = event.clientY - dragStartY;

    // --- Update local state for VISUAL feedback ---
    currentX.value = initialX + dx;
    currentY.value = initialY + dy;

    // Prüfe, ob das Fenster innerhalb der Bildschirmgrenzen bleibt
    const titlebarHeight = 36; // Höhe der Titelleiste, muss erreichbar bleiben
    const taskbarHeight = 40; // Höhe der Taskbar unten
    const windowBorderSize = 1; // Berücksichtige den Fensterrahmen

    // Prüfe, ob das Fenster zu weit nach oben gezogen wird
    if (currentY.value < 0) {
        currentY.value = 0; // Verhindere, dass das Fenster über den oberen Bildschirmrand hinaus gezogen wird
    }
    
    // Stelle sicher, dass die Titelleiste im sichtbaren Bereich bleibt (mind. 30px)
    const minVisibleTitlebar = 30; // Größerer Wert für sicherere Sichtbarkeit
    const windowHeight = window.innerHeight - taskbarHeight;
    
    // Untere Begrenzung: Stelle sicher, dass das Fenster im sichtbaren Bereich bleibt
    if (currentY.value > windowHeight - minVisibleTitlebar) {
        currentY.value = windowHeight - minVisibleTitlebar;
    }

    // Do NOT update props.window.x/y here!
    event.preventDefault();
};

const stopDrag = (event: MouseEvent) => {
    if (!isDragging.value) return;
    isDragging.value = false;
    isDraggingActive.value = false; // Hide overlay
    window.removeEventListener('mousemove', dragWindow);
    window.removeEventListener('mouseup', stopDrag);

    const dx = event.clientX - dragStartX;
    const dy = event.clientY - dragStartY;
    let finalX = initialX + dx; // Calculate final based on initial and total move
    let finalY = initialY + dy; // Calculate final based on initial and total move

    // Sichere Positionsgrenzen
    const titlebarHeight = 36;
    const taskbarHeight = 40; // Höhe der Taskbar berücksichtigen
    const minVisibleTitlebar = 30; // Größerer Wert für bessere Sichtbarkeit
    
    // Obere Grenze
    finalY = Math.max(0, finalY);
    
    // Untere Grenze - Stelle sicher, dass die Titelleiste erreichbar bleibt
    const windowHeight = window.innerHeight - taskbarHeight;
    
    // Stärkere Begrenzung für den unteren Rand
    if (finalY > windowHeight - minVisibleTitlebar) {
        finalY = windowHeight - minVisibleTitlebar;
    }
    
    // Seitliche Begrenzungen - mindestens 50px des Fensters sollten sichtbar sein
    const windowWidth = window.innerWidth;
    const minVisibleWidth = 50;
    
    finalX = Math.max(-props.window.width + minVisibleWidth, finalX);
    finalX = Math.min(windowWidth - minVisibleWidth, finalX);

    // --- Emit event to parent with FINAL position ---
    emit('update-position', {
        id: props.window.id,
        x: finalX,
        y: finalY,
        // Include size, although it didn't change during drag
        width: props.window.width,
        height: props.window.height,
    });
    event.preventDefault();
    event.stopPropagation();
};

// --- Resizing Functions (Updates local state, emits final on stop) ---
const startResize = (direction: string, event: MouseEvent) => {
    if (props.window.maximized || isDragging.value || isResizing.value) { return; }
    isResizing.value = true;
    resizeDirection = direction;

    resizeStartX = event.clientX;
    resizeStartY = event.clientY;
    initialWidth = props.window.width; // Store initial from props for final calculation
    initialHeight = props.window.height; // Store initial from props for final calculation
    initialResizeX = props.window.x; // Store initial from props (for corner/edge moves calculation)
    initialResizeY = props.window.y; // Store initial from props (for corner/edge moves calculation)

    // --- Initialize local state for visual feedback ---
    currentX.value = props.window.x;
    currentY.value = props.window.y;
    currentWidth.value = props.window.width;
    currentHeight.value = props.window.height;

    if (windowRef.value) { windowRef.value.classList.add('resizing'); }
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', finishResize);
    focusWindow();
    event.preventDefault();
    event.stopPropagation();
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value) return;
    // Removed requestAnimationFrame - direct reactivity update is fast enough
    const dx = event.clientX - resizeStartX;
    const dy = event.clientY - resizeStartY;

    let newWidth = initialWidth;
    let newHeight = initialHeight;
    let newX = initialResizeX;
    let newY = initialResizeY;

    // Taskbar-Höhe und Sicherheitsabstand
    const taskbarHeight = 40;
    const minVisibleTitlebar = 30;
    const maxWindowHeight = window.innerHeight - taskbarHeight;

    // Calculate NEW dimensions/position for local state
    switch (resizeDirection) {
        case 'se': 
            newWidth = Math.max(300, initialWidth + dx); 
            newHeight = Math.max(200, initialHeight + dy);
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (newY + newHeight > maxWindowHeight) {
                newHeight = maxWindowHeight - newY;
            }
            break;
        case 'sw': 
            newWidth = Math.max(300, initialWidth - dx); 
            newHeight = Math.max(200, initialHeight + dy); 
            newX = initialResizeX + initialWidth - newWidth;
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (newY + newHeight > maxWindowHeight) {
                newHeight = maxWindowHeight - newY;
            }
            break;
        case 'ne': 
            newWidth = Math.max(300, initialWidth + dx); 
            newHeight = Math.max(200, initialHeight - dy); 
            newY = initialResizeY + initialHeight - newHeight;
            
            // Begrenze Position nach oben
            newY = Math.max(0, newY);
            newHeight = initialResizeY + initialHeight - newY;
            break;
        case 'nw': 
            newWidth = Math.max(300, initialWidth - dx); 
            newHeight = Math.max(200, initialHeight - dy); 
            newX = initialResizeX + initialWidth - newWidth; 
            newY = initialResizeY + initialHeight - newHeight;
            
            // Begrenze Position nach oben
            newY = Math.max(0, newY);
            newHeight = initialResizeY + initialHeight - newY;
            break;
        case 'n': 
            newHeight = Math.max(200, initialHeight - dy); 
            newY = initialResizeY + initialHeight - newHeight;
            
            // Begrenze Position nach oben
            newY = Math.max(0, newY);
            newHeight = initialResizeY + initialHeight - newY;
            break;
        case 's': 
            newHeight = Math.max(200, initialHeight + dy);
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (newY + newHeight > maxWindowHeight) {
                newHeight = maxWindowHeight - newY;
            }
            break;
        case 'e': 
            newWidth = Math.max(300, initialWidth + dx);
            break;
        case 'w': 
            newWidth = Math.max(300, initialWidth - dx); 
            newX = initialResizeX + initialWidth - newWidth;
            break;
    }

    // --- Update local state for VISUAL feedback ---
    currentWidth.value = newWidth;
    currentHeight.value = newHeight;
    currentX.value = newX;
    currentY.value = newY;

    // Optional: Add bounds checks for current position/size

    // Do NOT update props.window... properties here!
    event.preventDefault();
    event.stopPropagation();
};

const finishResize = (event: MouseEvent) => {
    if (!isResizing.value) return;
    isResizing.value = false;
    if (windowRef.value) { windowRef.value.classList.remove('resizing'); }
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', finishResize);

    const dx = event.clientX - resizeStartX;
    const dy = event.clientY - resizeStartY;

    let finalWidth = initialWidth;
    let finalHeight = initialHeight;
    let finalX = initialResizeX;
    let finalY = initialResizeY;

    // Taskbar-Höhe und Sicherheitsabstand
    const taskbarHeight = 40;
    const minVisibleTitlebar = 30;
    const maxWindowHeight = window.innerHeight - taskbarHeight;

    // Calculate FINAL dimensions/position (logic repeated from handleResize)
    switch (resizeDirection) {
        case 'se': 
            finalWidth = Math.max(300, initialWidth + dx); 
            finalHeight = Math.max(200, initialHeight + dy);
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (finalY + finalHeight > maxWindowHeight) {
                finalHeight = maxWindowHeight - finalY;
            }
            break;
        case 'sw': 
            finalWidth = Math.max(300, initialWidth - dx); 
            finalHeight = Math.max(200, initialHeight + dy); 
            finalX = initialResizeX + initialWidth - finalWidth;
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (finalY + finalHeight > maxWindowHeight) {
                finalHeight = maxWindowHeight - finalY;
            }
            break;
        case 'ne': 
            finalWidth = Math.max(300, initialWidth + dx); 
            finalHeight = Math.max(200, initialHeight - dy); 
            finalY = initialResizeY + initialHeight - finalHeight;
            
            // Begrenze Position nach oben
            finalY = Math.max(0, finalY);
            finalHeight = initialResizeY + initialHeight - finalY;
            break;
        case 'nw': 
            finalWidth = Math.max(300, initialWidth - dx); 
            finalHeight = Math.max(200, initialHeight - dy); 
            finalX = initialResizeX + initialWidth - finalWidth; 
            finalY = initialResizeY + initialHeight - finalHeight;
            
            // Begrenze Position nach oben
            finalY = Math.max(0, finalY);
            finalHeight = initialResizeY + initialHeight - finalY;
            break;
        case 'n': 
            finalHeight = Math.max(200, initialHeight - dy); 
            finalY = initialResizeY + initialHeight - finalHeight;
            
            // Begrenze Position nach oben
            finalY = Math.max(0, finalY);
            finalHeight = initialResizeY + initialHeight - finalY;
            break;
        case 's': 
            finalHeight = Math.max(200, initialHeight + dy);
            
            // Begrenze Höhe, wenn Fenster nach unten zu groß wird
            if (finalY + finalHeight > maxWindowHeight) {
                finalHeight = maxWindowHeight - finalY;
            }
            break;
        case 'e': 
            finalWidth = Math.max(300, initialWidth + dx);
            break;
        case 'w': 
            finalWidth = Math.max(300, initialWidth - dx); 
            finalX = initialResizeX + initialWidth - finalWidth;
            break;
    }
    // Optional: Add bounds checks for final values

    // --- Emit event to parent with FINAL size and position ---
    emit('update-size', {
        id: props.window.id,
        x: finalX,
        y: finalY,
        width: finalWidth,
        height: finalHeight,
    });
    event.preventDefault();
    event.stopPropagation();
};

// --- Window Control Functions (Emit events) ---
const minimizeWindow = () => {
    emit('minimize', props.window.id); // Parent should set window.minimized = true
};

const maximizeWindow = () => {
    console.log(`AppWindow ${props.window.id}: Requesting maximize.`);
    if (isDragging.value || isResizing.value) return;

    // Store the current window position and size BEFORE emitting the maximize request
    if (!props.window.maximized) { // Only store if currently NOT maximized
         if (windowRef.value) {
             const rect = windowRef.value.getBoundingClientRect();
              console.log(`AppWindow ${props.window.id}: Aktuelle DOM Rect VOR Max:`, rect);
              if (rect.width > 50 && rect.height > 50) {
                  originalSizeBeforeMaximize.value = { x: rect.left, y: rect.top, width: rect.width, height: rect.height };
                  console.log(`AppWindow ${props.window.id}: Gespeicherter Zustand vor Max (via Rect):`, originalSizeBeforeMaximize.value);
              } else if (props.window.width > 50 && props.window.height > 50) {
                   originalSizeBeforeMaximize.value = { x: props.window.x, y: props.window.y, width: props.window.width, height: props.window.height };
                    console.log(`AppWindow ${props.window.id}: Gespeicherter Zustand vor Max (via Prop):`, originalSizeBeforeMaximize.value);
              } else {
                   originalSizeBeforeMaximize.value = { x: 100, y: 100, width: 800, height: 600 };
                    console.log(`AppWindow ${props.window.id}: Gespeicherter Zustand vor Max (Fallback):`, originalSizeBeforeMaximize.value);
              }
         } else {
              originalSizeBeforeMaximize.value = { x: props.window.x || 100, y: props.window.y || 100, width: props.window.width || 800, height: props.window.height || 600 };
               console.log(`AppWindow ${props.window.id}: Gespeicherter Zustand vor Max (Fallback Ref fehlt):`, originalSizeBeforeMaximize.value);
         }
    }

    // Emit event to the parent component to request state change to maximized
    // The parent will update the window object in its state (window.maximized = true).
    emit('maximize', props.window.id);

    // Request focus
    focusWindow();
};

const restoreWindow = () => {
    console.log(`AppWindow ${props.window.id}: Requesting restore.`);
    if (isDragging.value || isResizing.value) return;

    // Emit event to the parent component to request restore
    // Pass the saved state to which it should be restored.
    console.log(`AppWindow ${props.window.id}: Emitting 'restore' with state:`, originalSizeBeforeMaximize.value);
    emit('restore', {
        id: props.window.id,
        state: originalSizeBeforeMaximize.value // Pass the locally stored state
    });

    focusWindow();
};

// Combined toggle maximize/restore
const toggleMaximize = (event: MouseEvent) => {
    console.log(`AppWindow ${props.window.id}: Toggle maximize called. Current state: ${props.window.maximized}`);
    event.stopPropagation();
    event.preventDefault();
    if (props.window.maximized) {
        restoreWindow();
    } else {
        maximizeWindow();
    }
};

const closeWindow = () => {
    emit('close', props.window.id);
};

const focusWindow = () => {
    emit('focus', props.window.id);
     if (windowRef.value) {
         nextTick(() => {
              windowRef.value?.focus();
         });
     }
};

// Keyboard shortcuts
const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeWindow();
    }
};

// --- Watchers ---
// Sync local isActive state with active prop
watch(
    () => props.active,
    (newActive) => {
        isActive.value = newActive;
        if (newActive && windowRef.value) {
             nextTick(() => {
                  windowRef.value?.focus();
             });
        }
    }
);

// --- Watcher to sync local state from props when NOT dragging/resizing ---
// This is crucial so that the local state (currentX, etc.) correctly
// reflects the position/size managed by the parent when the window
// is not being actively dragged or resized by the user.
// It also syncs when exiting maximized/minimized states.
watch(
    () => ({
        x: props.window.x,
        y: props.window.y,
        width: props.window.width,
        height: props.window.height,
        maximized: props.window.maximized, // Watch maximized state too
        minimized: props.window.minimized, // Watch minimized state too
    }),
    (newState, oldState) => {
        // Only sync local state if we are NOT currently dragging or resizing
        // AND the window is currently in a state where local position/size is relevant (not maximized/minimized)
        if (!isDragging.value && !isResizing.value && !newState.maximized && !newState.minimized) {
             // Check if position/size props actually changed significantly
             // Use a small threshold to avoid unnecessary updates from floating point inaccuracies
             if (Math.abs(newState.x - oldState.x) > 1 || Math.abs(newState.y - oldState.y) > 1 ||
                 Math.abs(newState.width - oldState.width) > 1 || Math.abs(newState.height - oldState.height) > 1) {
                 console.log(`AppWindow ${props.window.id}: Syncing local state from props (normal update):`, newState);
                 currentX.value = newState.x;
                 currentY.value = newState.y;
                 currentWidth.value = newState.width;
                 currentHeight.value = newState.height;
             }
        }
         // Handle specific syncing when exiting maximized/minimized states
         // In this case, the parent has just updated the props to the restored size/position.
         // We need to ensure the local state is also updated to these new prop values
         // so that the *next* drag/resize starts from the correct position.
         // Using nextTick gives Vue a moment to apply styles based on new props first.
         if ((!newState.maximized && oldState.maximized) || (!newState.minimized && oldState.minimized)) {
              console.log(`AppWindow ${props.window.id}: State changed (Max/Min exit), scheduling local state sync.`);
              nextTick(() => {
                 // Only sync if windowRef is available and we are not immediately starting a new interaction
                 // and the window is now in a state where local position/size is relevant
                 if (windowRef.value && !isDragging.value && !isResizing.value && !props.window.maximized && !props.window.minimized) {
                      console.log(`AppWindow ${props.window.id}: Syncing local state after state change (nextTick):`, {x: props.window.x, y: props.window.y, w: props.window.width, h: props.window.height});
                      currentX.value = props.window.x;
                      currentY.value = props.window.y;
                      currentWidth.value = props.window.width;
                      currentHeight.value = props.window.height;
                 } else {
                      console.log(`AppWindow ${props.window.id}: Sync after state change skipped (dragging/resizing or not in normal state).`);
                 }
              });
         } else if (newState.maximized && !oldState.maximized) {
             // --- Sync local state when entering maximized ---
             // Although local state isn't used for styling in maximized state,
             // keeping it updated helps ensure the next interaction (after restore)
             // starts from a position/size close to the maximized window's final resting place (0,0,100%,...)
              console.log(`AppWindow ${props.window.id}: State changed (entering Maximize), scheduling local state sync.`);
             nextTick(() => {
                 if (windowRef.value && !isDragging.value && !isResizing.value) { // Sync even if dragging/resizing ended just as maximize happened
                      const rect = windowRef.value?.getBoundingClientRect();
                       if (rect) {
                           console.log(`AppWindow ${props.window.id}: Synced local state on entering Maximize (nextTick):`, {x: rect.left, y: rect.top, w: rect.width, h: rect.height});
                           currentX.value = rect.left;
                           currentY.value = rect.top;
                           currentWidth.value = rect.width;
                           currentHeight.value = rect.height;
                       }
                 }
             });
         }
    },
    {
        deep: true,
        // 'post' ensures the watcher runs after DOM updates triggered by parent state changes.
        flush: 'post'
    }
);

// --- Computed Styles (Reads from local state during interaction, otherwise from props) ---
const windowStyle = computed(() => {
    const style: Record<string, string | number> = {
        // Transition enabled unless dragging or resizing
        // Added top/left/width/height transitions for smooth movement *after* dragging/resizing stops
        transition: (isDragging.value || isResizing.value) ? 'none' : 'box-shadow 0.2s ease, width 0.3s ease, height 0.3s ease, top 0.3s ease, left 0.3s ease',
        position: 'absolute', // Default position
        // zIndex handled by isActive or parent prop
        zIndex: isActive.value ? 100 : 10,
    };

    // Apply styles based on maximized state from props
    if (props.window.maximized) {
        style.position = 'fixed'; // Use fixed position for full viewport coverage
        style.top = '0px';
        style.left = '0px';
        style.width = '100vw';
        style.height = 'calc(100vh - 40px)'; // Subtract Taskbar height (adjust 40px as needed)
        style.borderRadius = '0'; // No border radius when maximized
        style.border = 'none';      // No border when maximized
        style.maxWidth = '100vw';   // Ensure it doesn't exceed viewport
        style.maxHeight = 'calc(100vh - 40px)'; // Ensure it doesn't exceed viewport height

        // --- Local state is NOT used for styling in maximized state ---
        // The watcher syncs it when entering maximized state.

    } else {
        // --- Apply styles based on local state (currentX, currentY, ...) when dragging or resizing, ---
        // --- OTHERWISE apply styles based on props.window state (synced by parent) ---
        style.top = `${(isDragging.value || isResizing.value) ? currentY.value : props.window.y}px`;
        style.left = `${(isDragging.value || isResizing.value) ? currentX.value : props.window.x}px`;
        style.width = `${(isDragging.value || isResizing.value) ? currentWidth.value : props.window.width}px`;
        style.height = `${(isDragging.value || isResizing.value) ? currentHeight.value : props.window.height}px`;

        style.borderRadius = '6px';
        style.border = '1px solid rgba(0, 0, 0, 0.1)';
        style.maxWidth = 'unset';
        style.maxHeight = 'calc(100vh - 40px)'; // Still cap max height
    }

    return style;
});

// --- Computed property for icon color based on icon name and active state ---
const getIconColor = computed(() => {
    // Default color for active and inactive state, works in both light and dark mode
    return isActive.value ? 'white' : 'grey lighten-1';
});

// --- Lifecycle Hooks ---
onMounted(() => {
    console.log('AppWindow mounted for window:', props.window.id);
    console.log('🔑 Permissions for mounted component:', {
        canEdit: windowProps.value.canEdit || false,
        canDelete: windowProps.value.canDelete || false,
        canCreate: windowProps.value.canCreate || false,
        'meta.canEdit': windowProps.value.meta?.canEdit || false,
        'meta.canDelete': windowProps.value.meta?.canDelete || false,
        'meta.canCreate': windowProps.value.meta?.canCreate || false,
        allPermissions: windowProps.value.allPermissions || false,
    });
    
    prepareWindowProps();

    // Initialize local visual state from props on mount
    // Use nextTick to ensure windowRef has actual dimensions if mounted hidden or off-screen initially
    nextTick(() => {
         if (windowRef.value) {
              const rect = windowRef.value.getBoundingClientRect();
              console.log(`AppWindow ${props.window.id}: Initial DOM Rect on Mounted:`, rect);
              // Use rect if valid, fallback to props
               if (rect.width > 0 && rect.height > 0) { // Check for 0 dimensions
                   currentX.value = rect.left;
                   currentY.value = rect.top;
                   currentWidth.value = rect.width;
                   currentHeight.value = rect.height;
               } else {
                   currentX.value = props.window.x;
                   currentY.value = props.window.y;
                   currentWidth.value = props.window.width;
                   currentHeight.value = props.window.height;
               }
         } else {
              // Fallback if ref is not available (e.g., v-show initially false)
              currentX.value = props.window.x;
              currentY.value = props.window.y;
              currentWidth.value = props.window.width;
              currentHeight.value = props.window.height;
         }
          console.log(`AppWindow ${props.window.id}: Initial local state (currentX, etc.):`, {x: currentX.value, y: currentY.value, w: currentWidth.value, h: currentHeight.value});
    });

    loadingStartTime.value = Date.now();
    windowStore.setLoading(true);

    setTimeout(() => { if (isLoading.value) { console.warn('Loading component took too long on mount, forcing hide.', props.window.id); windowStore.setLoading(false); } }, 3000);

    if (props.active && windowRef.value) {
        nextTick(() => { windowRef.value?.focus(); });
    }
});

// Clean up event listeners and store
onUnmounted(() => {
    console.log('AppWindow unmounted for window:', props.window.id);
    window.removeEventListener('mousemove', dragWindow);
    window.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', finishResize);
    removeWindowStore(props.window.id);
});

// Provide window-specific store to content
provide('windowContext', windowStore);

</script>

<style scoped>
/* --- Base Window Styles --- */
/*
   Fenstergestalt nach Entwurf: Radius 7 px, Flaeche und Linie aus den Merkern,
   ein Schatten - mehr nicht. Vorher lag hier ein fest verdrahtetes
   rgba(30,41,59,.98), also eine dunkle Platte, die im hellen Modus nicht
   mitkippte, dazu 16 px Radius und drei uebereinandergelegte Schatten samt
   Innenkante.
*/
.window {
    background: var(--k-surface);
    box-shadow: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    pointer-events: auto;
    max-height: calc(100vh - 40px);
    min-width: 300px;
    min-height: 200px;
    outline: none;
    border: 1px solid var(--k-line-strong);
    color: var(--k-ink);
    border-radius: 7px;
    transition: box-shadow 150ms cubic-bezier(0.16, 1, 0.3, 1),
        border-color 150ms cubic-bezier(0.16, 1, 0.3, 1);
}

/*
   "Das aktive Fenster erkennt man an der Titelleiste, nicht an einem
   Leuchten." Deshalb hier nur der zweite Schattengrad und eine Randfarbe -
   kein blauer Ring, kein Anheben um zwei Pixel. Das Heben verschob beim
   Fokuswechsel jedes Mal den gesamten Inhalt.
*/
.window.active {
    z-index: 100;
    box-shadow: none;
    border-color: var(--k-accent-line);
}

/* Maximized state styling (applied via windowStyle) */
.window.maximized {
    border-radius: 0 !important;
    border: none !important;
    transform: none !important;
    box-shadow: none !important;
}

/* Improved animation for window appearance */
/* Einblenden statt Aufspringen: der Entwurf laesst 120-180 ms fuer
   Zustandswechsel und Einblendungen zu - ein Federn ueber den Zielwert hinaus
   klaert nichts. */
@keyframes windowAppear {
    from {
        opacity: 0;
        transform: scale(0.98);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.window {
    animation: windowAppear 150ms cubic-bezier(0.16, 1, 0.3, 1);
}

@media (prefers-reduced-motion: reduce) {
    .window {
        animation: none;
    }
}

/* Snapping classes (optional) */
.window.snap-left { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.window.snap-right { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.window.snap-top { border-top-left-radius: 0; border-top-right-radius: 0; }

/* --- Title Bar Styles --- */
/*
   Titelleiste: 28 px auf gesenkter Flaeche. Die beiden Verlaeufe hier waren
   fest verdrahtet und trugen im hellen Modus dieselbe dunkle Farbe wie im
   dunklen.
*/
.window-titlebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 7px;
    padding: 0 10px;
    height: 28px;
    background: var(--k-sunken);
    cursor: grab;
    user-select: none;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    position: relative;
    z-index: 10;
    flex-shrink: 0;
    border-bottom: 1px solid var(--k-line);
    color: var(--k-ink-muted);
    margin: 0;
}

/* Hier - und nur hier - erkennt man das aktive Fenster. */
.window.active .window-titlebar {
    background: var(--k-accent-weak);
    color: var(--k-accent);
    border-bottom-color: var(--k-accent-line);
}

.window-titlebar-left {
    display: flex;
    align-items: center;
    min-width: 0;
    flex-grow: 1;
    overflow: hidden;
    padding-left: 0;
}

.window-icon {
    margin-right: 7px;
}

/* 11.5 / 600 wie im Entwurf. Ein Schlagschatten auf 14-px-Text macht ihn
   unschaerfer, nicht lesbarer. */
.window-title {
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0;
}

.window-controls {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    gap: 4px;
}

.window-control {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 20px;
    border: none;
    background: transparent;
    color: inherit;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 120ms ease;
    outline: none;
}

/*
   Die Steuerknoepfe tragen dieselbe Schriftfarbe wie die Titelleiste, in der
   sie sitzen - im aktiven Fenster also die Akzentfarbe, sonst gedimmt. Vorher
   stand hier fest --k-ink, wodurch sie im aktiven Fenster aus der Farbe der
   Leiste fielen.
*/
.window .window-control {
    color: var(--k-ink-muted);
}

.window.active .window-control {
    color: var(--k-accent);
}

.window-control:hover {
    background: var(--k-row-hover);
}

.window.active .window-control:hover {
    background: var(--k-surface);
}

/* Schliessen wird erst beim Zeigen rot - so schlaegt es nicht dauernd
   Alarm. Die Farbe kommt aus den Bedeutungsmerkern, die Schrift darauf aus
   --on-fill, weil sie im dunklen Modus kippen muss. */
.window-control.close:hover {
    background: var(--k-critical);
    color: var(--k-on-fill);
}

.window-control:active {
    transform: scale(0.95);
}

/* --- Window Content Styles --- */
.window-content {
    flex: 1;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    background: rgba(15, 23, 42, 0.95);
    z-index: 1;
    pointer-events: auto;
    box-shadow: inset 0 1px 0 rgba(0, 0, 0, 0.1);
    margin: 0;
    border: none;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}

/* Style for direct children of window-content (e.g. dynamic-component-container, placeholder) */
.window-content > * {
     flex: 1 1 auto; width: 100%; height: 100%;
     overflow: hidden; /* Container should hide overflow */
     position: relative; z-index: 2;
     display: flex; flex-direction: column; /* Make container a flex column */
     /* Ensure no white gaps */
     margin: 0;
     padding: 0;
}

/* Style for the actual dynamic component */
.dynamic-component-container .dynamic-component {
    flex: 1;
    width: 100%;
    height: 100%;
    overflow: auto;
    position: relative;
    z-index: 3;
    pointer-events: auto;
    margin: 0;
    padding: 0;
}

/* Custom scrollbar for dynamic components */
.dynamic-component-container .dynamic-component::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dynamic-component-container .dynamic-component::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 5px;
}

.dynamic-component-container .dynamic-component::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 5px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.dynamic-component-container .dynamic-component::-webkit-scrollbar-thumb:hover {
    background: var(--k-row-hover);
}

/* Fallback Placeholder Styles */
.window-placeholder {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
    background: rgba(15, 23, 42, 0.95);
    padding: 20px;
    color: var(--k-ink-muted);
    text-align: center;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}

.placeholder-text {
    margin-top: 16px;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Loading Overlay Styles */
.window-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.95);
    z-index: 20;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
    transition: opacity 0.3s ease;
    pointer-events: all;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.window-loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: var(--k-ink);
    padding: 32px;
    background: rgba(51, 65, 85, 0.5);
    border-radius: 6px;
    border: 1px solid var(--k-line);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

/* Overlay shown while dragging */
.drag-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    cursor: grabbing; background: transparent; z-index: 1000; pointer-events: all;
}

/* --- Resize Handle Styles --- */
.resize-handle {
    position: absolute;
    background-color: transparent;
    z-index: 10;
    pointer-events: auto;
    opacity: 0;
    transition: opacity 0.2s ease;
}

/* Edge handles - invisible but functional */
.resize-handle-n { top: -4px; left: 10px; right: 10px; height: 8px; cursor: n-resize; }
.resize-handle-s { bottom: -4px; left: 10px; right: 10px; height: 8px; cursor: s-resize; }
.resize-handle-e { top: 10px; right: -4px; bottom: 10px; width: 8px; cursor: e-resize; }
.resize-handle-w { top: 10px; left: -4px; bottom: 10px; width: 8px; cursor: w-resize; }

/* Corner handles - only visible on hover */
.resize-handle-se {
    bottom: 0;
    right: 0;
    width: 20px;
    height: 20px;
    cursor: se-resize;
}

.resize-handle-sw {
    bottom: 0;
    left: 0;
    width: 20px;
    height: 20px;
    cursor: sw-resize;
}

.resize-handle-ne {
    top: 0;
    right: 0;
    width: 20px;
    height: 20px;
    cursor: ne-resize;
}

.resize-handle-nw {
    top: 0;
    left: 0;
    width: 20px;
    height: 20px;
    cursor: nw-resize;
}

/* Show corner indicators on window hover */
.window:hover .resize-handle-se::after,
.window:hover .resize-handle-sw::after,
.window:hover .resize-handle-ne::after,
.window:hover .resize-handle-nw::after {
    content: '';
    position: absolute;
    width: 8px;
    height: 8px;
    background: var(--k-row-hover);
    border-radius: 2px;
    opacity: 0.5;
    transition: all 0.2s ease;
}

.resize-handle-se::after { bottom: 4px; right: 4px; }
.resize-handle-sw::after { bottom: 4px; left: 4px; }
.resize-handle-ne::after { top: 4px; right: 4px; }
.resize-handle-nw::after { top: 4px; left: 4px; }

/* Brighter on active window */
.window.active:hover .resize-handle-se::after,
.window.active:hover .resize-handle-sw::after,
.window.active:hover .resize-handle-ne::after,
.window.active:hover .resize-handle-nw::after {
    background: var(--k-accent-line);
    opacity: 1;
}

/* Show on direct hover */
.resize-handle-se:hover::after,
.resize-handle-sw:hover::after,
.resize-handle-ne:hover::after,
.resize-handle-nw:hover::after {
    background: var(--k-accent-line);
    opacity: 1;
    transform: scale(1.2);
}

/* Class added while resizing */
.window.resizing {
    transition: none !important;
    user-select: none;
    opacity: 0.92; /* Slightly transparent during resize */
}
.window.resizing::after {
    content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: transparent; z-index: 500; cursor: inherit; pointer-events: auto;
}

/* Improved animation for component loaded */
.component-loaded {
    animation: componentLoaded 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes componentLoaded {
    0% { opacity: 0.8; transform: scale(0.98); }
    50% { opacity: 1.02; transform: scale(1.01); }
    100% { opacity: 1; transform: scale(1); }
}

/* Animation for when dragging starts */
.window.dragging {
    transition: none !important;
    opacity: 0.92;
}
</style>