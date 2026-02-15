// src/plugins/vuetify.js
import '@mdi/font/css/materialdesignicons.css'; // Importiere MDI Icons
import 'vuetify/styles'; // Importiere Vuetify Grundstile
import { mdi } from 'vuetify/iconsets/mdi';
import { createVuetify } from 'vuetify';
import { de as deVuetify, en as enVuetify } from 'vuetify/locale';
import { createVueI18nAdapter } from 'vuetify/locale/adapters/vue-i18n';
import { useI18n } from 'vue-i18n';
import i18n from './i18n';

const vuetify = createVuetify({
    locale: {
        adapter: createVueI18nAdapter({ i18n, useI18n }),
        locale: 'de',
        fallback: 'en',
        messages: { de: deVuetify, en: enVuetify },
    },
    theme: {
        defaultTheme: 'dark', // Stellt sicher, dass immer das Dark Theme verwendet wird
        themes: {
            dark: {
                dark: true,
                colors: {
                    background: '#121212',
                    surface: '#1E1E1E',
                    primary: '#3182ce',
                    secondary: '#03DAC6',
                    error: '#CF6679',
                    // Weitere Farben kannst du hier bei Bedarf definieren
                },
            },
        },
    },
    icons: {
        defaultSet: 'mdi',
        sets: { mdi },
    },
});

// Funktion zum Aktualisieren der Vuetify-Theme-Farben
export function updateVuetifyTheme() {
    // Hole die CSS-Variablen vom Root-Element
    const styles = getComputedStyle(document.documentElement);

    // Hole die aktuellen Farbwerte
    const primary = styles.getPropertyValue('--primary').trim() || '#3182ce';
    const secondary = styles.getPropertyValue('--secondary').trim() || '#03DAC6';
    const accent = styles.getPropertyValue('--accent').trim() || '#FF4081';
    const error = styles.getPropertyValue('--error').trim() || '#CF6679';
    const background = styles.getPropertyValue('--background').trim() || '#121212';
    const surface = styles.getPropertyValue('--surface').trim() || '#1E1E1E';

    console.log('Vuetify: Updating theme colors:', {
        primary,
        secondary,
        accent,
        error,
        background,
        surface,
    });

    // Aktualisiere die Vuetify-Theme-Farben direkt
    if (vuetify?.theme?.themes?.value?.dark) {
        vuetify.theme.themes.value.dark.colors.primary = primary;
        vuetify.theme.themes.value.dark.colors.secondary = secondary;
        vuetify.theme.themes.value.dark.colors.accent = accent;
        vuetify.theme.themes.value.dark.colors.error = error;
        vuetify.theme.themes.value.dark.colors.background = background;
        vuetify.theme.themes.value.dark.colors.surface = surface;

        // Löse ein Theme-Update aus
        document.documentElement.dispatchEvent(new CustomEvent('vuetify:theme-updated'));
        console.log('Vuetify: Theme colors updated successfully');
    } else {
        console.warn('Vuetify: Unable to update theme colors - theme object not found');
    }
}

export default vuetify;
