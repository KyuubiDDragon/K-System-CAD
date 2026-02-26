import { createApp } from 'vue';
import type { App as VueApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import vuetify from './plugins/vuetify';
import i18n from './plugins/i18n';
import axios from 'axios';
import Toast from 'vue-toastification';
// Import the CSS for vue-toastification
import 'vue-toastification/dist/index.css';
import { loadFonts } from './plugins/webfontloader';
import { useAuthStore } from './stores/auth'; // Import the auth store
import { useModulePermission } from './composables/useModulePermission';
import './assets/css/desktop-mode.css';
import './scss/global.scss'; // Import global CSS variables and styles
import { initializeTheme } from './utils/themeLoader';
import { socketService } from './services/socket';

// Erweitere die Window-Schnittstelle für TypeScript
declare global {
    interface Window {
        __VUE_APP__?: VueApp; // Globaler Zugriff auf die Vue-App-Instanz
        useAuthStore?: () => ReturnType<typeof useAuthStore>;
        useModulePermission?: typeof useModulePermission;
    }
}

loadFonts();

// Überprüfen, ob ein Refresh des Theme-Cache erforderlich ist
const themeSettingsStr = localStorage.getItem('theme-settings');
let forceRefresh = false;

// Überprüfe ob der Cache zu alt ist oder ein Force-Refresh angefordert wurde
if (themeSettingsStr) {
    try {
        const themeSettings = JSON.parse(themeSettingsStr);
        const now = new Date().getTime();
        const lastUpdated = themeSettings.lastUpdated || 0;

        // Cache als veraltet betrachten, wenn er älter als 1 Minute ist
        const MAX_CACHE_AGE = 60 * 1000; // 1 Minute

        if (!lastUpdated || now - lastUpdated > MAX_CACHE_AGE) {
            console.log('Theme-Cache ist veraltet (> 1 Minute alt), wird gelöscht.');
            forceRefresh = true;
        }

        // Prüfen ob ein force_refresh_theme Flag gesetzt ist
        if (localStorage.getItem('force_refresh_theme') === 'true') {
            console.log('Manuelles Theme-Refresh angefordert, Cache wird gelöscht.');
            forceRefresh = true;
            localStorage.removeItem('force_refresh_theme');
        }
    } catch (e) {
        console.log('Fehler beim Parsen des Theme-Cache, wird gelöscht.');
        forceRefresh = true;
    }
}

// Lösche den Cache wenn nötig
if (forceRefresh) {
    localStorage.removeItem('theme-settings');
    console.log('Theme-Cache wurde gelöscht, neue Einstellungen werden vom Server geladen.');
} else {
    console.log('Theme-Cache ist aktuell, wird vorerst verwendet.');
}

const app = createApp(App);
const pinia = createPinia(); // Create Pinia instance
app.use(pinia); // Register pinia FIRST before using any store

// Now initialize the auth store
const authStore = useAuthStore();
// Don't automatically check auth status here - let the router handle it
// This prevents setting cookies when user is not logged in
// authStore.checkAuthStatus();

// Clean up any invalid auth_token cookies on app start
// This prevents issues with empty or invalid cookies from backend
const clearInvalidAuthCookies = () => {
    // Get all cookies
    const cookies = document.cookie.split(';');
    
    // Look for auth_token cookie
    cookies.forEach(cookie => {
        const [name, value] = cookie.split('=').map(c => c.trim());
        if (name === 'auth_token' && (!value || value === '')) {
            console.log('🍪 Removing invalid/empty auth_token cookie');
            // Remove cookie by setting it with past expiration on all possible paths and domains
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + window.location.hostname + ';';
        }
    });
};

// Clear invalid cookies on startup
clearInvalidAuthCookies();

// Expose auth store globally for component registry access
if (typeof window !== 'undefined') {
    console.log('🔑 Exposing auth store globally via window.useAuthStore');
    window.useAuthStore = () => {
        console.log('🔑 Global useAuthStore called, returning auth store');
        const store = useAuthStore();
        console.log('🔑 Auth store permissions:', store.userPermissions);
        return store;
    };

    // Verify it's accessible
    try {
        const testStore = window.useAuthStore?.();
        if (testStore) {
            console.log('🔑 Successfully verified global auth store access');
        } else {
            console.warn('⚠️ Could not verify global auth store access - store is null');
        }
    } catch (e) {
        console.error('❌ Error verifying global auth store access:', e);
    }
}

// Expose useModulePermission globally for debugging
if (typeof window !== 'undefined') {
    console.log('🔐 Exposing useModulePermission globally for debugging');
    window.useModulePermission = useModulePermission;
}

// 🟢 Direktive für 'checked'
app.directive('checked', {
    beforeMount(el, binding) {
        el.checked = !!binding.value;
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            el.checked = !!binding.value;
        }
    },
});

// 🟢 Axios als globale Property setzen
app.config.globalProperties.$axios = axios;

// 🟢 Plugins einbinden
app.use(i18n);
app.use(router);
app.use(vuetify);
// app.use(CKEditor); // <<< ENTFERNT

// 🟢 Vue-Toastification einbinden
app.use(Toast, {
    position: 'bottom-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    toastClassName: 'custom-toast',
    bodyClassName: 'custom-toast-body',
    containerClassName: 'custom-toast-container',
});

// Theme initialisieren, bevor App gerendert wird
initializeTheme();

// Nach der App-Erstellung und vor dem App-Mount
socketService.connect();

app.provide('socket', socketService);

// Mache die App global verfügbar, damit Legacy-Code darauf zugreifen kann
// Dies ist wichtig für den ThemeLoader
if (typeof window !== 'undefined') {
    window.__VUE_APP__ = app;
}

// 🟢 App starten
app.mount('#app');
