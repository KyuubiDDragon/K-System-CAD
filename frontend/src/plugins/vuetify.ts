// src/plugins/vuetify.ts
import '@mdi/font/css/materialdesignicons.css'; // Importiere MDI Icons
import 'vuetify/styles'; // Importiere Vuetify Grundstile
import { mdi } from 'vuetify/iconsets/mdi';
import { createVuetify } from 'vuetify';
import { de as deVuetify, en as enVuetify } from 'vuetify/locale';
import { createVueI18nAdapter } from 'vuetify/locale/adapters/vue-i18n';
import { useI18n } from 'vue-i18n';
import i18n from './i18n';
import { LIGHT_TOKENS, DARK_TOKENS, type ThemeTokens } from '@/theme/tokens';

/**
 * Baut ein Vuetify-Farbschema aus den Design-Tokens.
 *
 * Vuetify leitet aus `on-*` die Schriftfarben ab. Entscheidend ist `on-primary`:
 * im dunklen Modus ist der Akzent hell, weiße Schrift darauf wäre unlesbar.
 * Deshalb kommt dort `onFill` zum Einsatz und nicht pauschal Weiß.
 */
function colorsFrom(t: ThemeTokens) {
    return {
        background: t.canvas,
        surface: t.surface,
        'surface-bright': t.raised,
        'surface-light': t.sunken,
        'surface-variant': t.sunken,
        'on-surface-variant': t.inkMuted,
        primary: t.accent,
        'primary-darken-1': t.accentHover,
        secondary: t.neutral,
        accent: t.accent,
        error: t.critical,
        warning: t.warning,
        success: t.success,
        info: t.accent,
        'on-background': t.ink,
        'on-surface': t.ink,
        'on-primary': t.onFill,
        'on-secondary': t.onFill,
        'on-error': t.onFill,
        'on-warning': t.onFill,
        'on-success': t.onFill,
        'on-info': t.onFill,
    };
}

const vuetify = createVuetify({
    locale: {
        adapter: createVueI18nAdapter({ i18n, useI18n }),
        locale: 'de',
        fallback: 'en',
        messages: { de: deVuetify, en: enVuetify },
    },
    theme: {
        // Der tatsächliche Startwert wird von themeStore.initializeTheme()
        // gesetzt, sobald die gespeicherte Einstellung geladen ist.
        defaultTheme: 'kDark',
        themes: {
            kLight: { dark: false, colors: colorsFrom(LIGHT_TOKENS) },
            kDark: { dark: true, colors: colorsFrom(DARK_TOKENS) },
        },
    },
    defaults: {
        // Dichte-System: kompaktere Grundmaße als die Vuetify-Standardhöhen.
        // Wirkt auf alle Ansichten, ohne sie einzeln anzufassen.
        VDataTable: { density: 'compact' },
        VDataTableServer: { density: 'compact' },
        VTextField: { density: 'compact', variant: 'outlined', hideDetails: 'auto' },
        VTextarea: { density: 'compact', variant: 'outlined', hideDetails: 'auto' },
        VSelect: { density: 'compact', variant: 'outlined', hideDetails: 'auto' },
        VAutocomplete: { density: 'compact', variant: 'outlined', hideDetails: 'auto' },
        VCombobox: { density: 'compact', variant: 'outlined', hideDetails: 'auto' },
        VCheckbox: { density: 'compact', hideDetails: 'auto' },
        VSwitch: { density: 'compact', hideDetails: 'auto' },
        VList: { density: 'compact' },
        VBtn: { variant: 'flat' },
    },
    icons: {
        defaultSet: 'mdi',
        sets: { mdi },
    },
});

/** Namen der beiden registrierten Themes. */
export const THEME_LIGHT = 'kLight';
export const THEME_DARK = 'kDark';

/**
 * Schaltet das aktive Vuetify-Theme um.
 *
 * Das war bisher die Lücke: Es gab nur ein Theme, und `theme.global.name` wurde
 * nie gesetzt — der Umschalter in der Kopfleiste hat den Zustand gewechselt,
 * aber keine einzige Farbe.
 */
export function setVuetifyTheme(isDark: boolean): void {
    vuetify.theme.global.name.value = isDark ? THEME_DARK : THEME_LIGHT;
}

/**
 * Übernimmt die Akzentfarbe der Behörde in beide Themes.
 *
 * Nur der Akzent wird überschrieben. Flächen, Linien und Bedeutungsfarben
 * bleiben aus den Tokens, damit der helle Modus hell bleibt und Rot weiterhin
 * Rot bedeutet — unabhängig davon, welche Hausfarbe eine Behörde wählt.
 */
export function applyAuthorityAccent(primaryColor?: string | null): void {
    if (!primaryColor) return;
    for (const name of [THEME_LIGHT, THEME_DARK]) {
        const theme = vuetify.theme.themes.value[name];
        if (!theme) continue;
        theme.colors.primary = primaryColor;
        theme.colors.accent = primaryColor;
        theme.colors.info = primaryColor;
    }
}

/**
 * Rückwärtskompatibler Name — wird an mehreren Stellen aufgerufen.
 * Liest den aktuellen Akzent aus den CSS-Variablen und übernimmt ihn.
 */
export function updateVuetifyTheme(): void {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary').trim();
    if (primary) applyAuthorityAccent(primary);
}

export default vuetify;
