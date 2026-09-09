/**
 * Theme Loader - Lädt die Farbvariablen aus dem Local Storage oder verwendet Standardwerte
 */

import { ref } from 'vue';
import { updateVuetifyTheme, setVuetifyTheme, applyAuthorityAccent } from '@/plugins/vuetify';
import { tokensFor, tokensToCssVars } from '@/theme/tokens';

// Flag to track if we need to force a theme refresh
export const forceRefreshTheme = ref(false);

// Standardfarben (gleich wie in SettingsView)
const defaultColors = {
    primary: '#3B82F6', // Blue
    secondary: '#343541',
    accent: '#10B981', // Green
    background: '#111723',
    surface: '#111827',
    tertiary: '#444654',
    info: '#007bff',
    success: '#138D75',
    warning: '#FFC107',
    error: '#dc3545',
    // Text on color settings
    onPrimary: '#FFFFFF',
    onSecondary: '#FFFFFF',
    onAccent: '#121212',
    onBackground: '#FFFFFF',
    onSurface: '#FFFFFF',
    onSuccess: '#FFFFFF',
    onInfo: '#FFFFFF',
    onWarning: '#121212',
    onError: '#FFFFFF',
    // Other settings
    borderRadius: 8,
    darkMode: true,
    enableGradients: true,
    shadowStrength: 0.5,
    // Additional UI colors
    container: '#1E1E1E',
    border: '#2A2A2A',
    text: '#FFFFFF',
    textMuted: '#888888',
    inputBg: '#2A2A2A',
    // Desktop settings
    desktopBackgroundImage: 'https://source.unsplash.com/1920x1080/?nature,water'
};

// Ermittlung der korrekten API-Basis-URL
function getApiBaseUrl(): string {
    // Verschiedene Möglichkeiten für die API-Basis-URL aus Vite-Umgebungsvariablen
    // Diese werden aus .env.development oder .env.production geladen
    if (typeof import.meta !== 'undefined' && import.meta.env) {
        // Prüfe verschiedene mögliche Variablennamen
        const env = import.meta.env;
        
        // Log alle verfügbaren Umgebungsvariablen für Debugging
        console.log('ThemeLoader: Verfügbare Env-Variablen:', 
            Object.keys(env)
                .filter(key => key.startsWith('VITE_'))
                .reduce((obj, key) => {
                    obj[key] = env[key];
                    return obj;
                }, {} as Record<string, any>)
        );
        
        // Prüfe verschiedene mögliche Variablennamen in Prioritätsreihenfolge
        if (env.VITE_API_BASE_URL) return env.VITE_API_BASE_URL;
        if (env.VITE_API_URL) return env.VITE_API_URL;
        if (env.VITE_BACKEND_URL) return env.VITE_BACKEND_URL;
        if (env.VITE_BASE_API_URL) return env.VITE_BASE_API_URL;
        if (env.VITE_SERVER_URL) return env.VITE_SERVER_URL;
        
        console.log('ThemeLoader: Keine API-URL in Env-Variablen gefunden');
    }
    
    // Prüfe auf window._env_ (Custom Runtime Config)
    // @ts-ignore - _env_ ist eine Runtime-Property, die TypeScript nicht kennt
    if (typeof window !== 'undefined' && window._env_ && window._env_.API_BASE_URL) {
        // @ts-ignore
        return window._env_.API_BASE_URL;
    }
    
    // Prüfe auf localStorage (manuell gespeichert)
    const storedBaseUrl = localStorage.getItem('api_base_url');
    if (storedBaseUrl) {
        return storedBaseUrl;
    }
    
    // Fallback-Optionen für die API-URL basierend auf der aktuellen Domain
    const currentOrigin = window.location.origin;
    
    // Mögliche Produktionsumgebungen
    if (currentOrigin.includes('fireguard') ||
        currentOrigin.includes('cad.')) {
        // Nimm an, dass die API-URL die gleiche Domain mit /api ist
        return `${currentOrigin}/api`;
    }
    
    // Lokale Entwicklungsumgebung
    if (currentOrigin.includes('localhost') || currentOrigin.includes('127.0.0.1')) {
        // Typischer Setup für lokale Entwicklung: Frontend auf 5173, Backend auf 8000
        if (currentOrigin.includes('5173')) {
            return 'http://localhost:8000';
        }
    }
    
    // Default-Fallback
    return 'http://localhost:8000';
}

export interface ThemeSettings {
    primaryColor: string;
    secondaryColor: string;
    accentColor: string;
    backgroundColor: string;
    surfaceColor: string;
    tertiaryColor: string;
    infoColor: string;
    successColor: string;
    warningColor: string;
    errorColor: string;
    onPrimaryColor: string;
    onSecondaryColor: string;
    onAccentColor: string;
    onBackgroundColor: string;
    onSurfaceColor: string;
    onSuccessColor: string;
    onInfoColor: string;
    onWarningColor: string;
    onErrorColor: string;
    borderRadius: number;
    darkMode: boolean;
    enableGradients: boolean;
    desktopBackgroundImage?: string;
    // Additional UI properties
    containerColor?: string;
    borderColor?: string;
    textColor?: string;
    textMuted?: string;
    inputBackground?: string;
    shadowStrength?: number;
    [key: string]: any; // Allow additional properties
}

/**
 * Converts a hex color to its RGB components
 * @param hex Hex color code
 * @param returnType Whether to return an object or string
 * @returns Object with r, g, b values or RGB string depending on returnType
 */
function hexToRgb(hex: string, returnType: 'object' | 'string' = 'object'): { r: number, g: number, b: number } | string {
    // Remove # if present
    hex = hex.replace(/^#/, '');
    
    // Convert from 3-digit to 6-digit format
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    
    // Parse components
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    
    return returnType === 'string' ? `${r}, ${g}, ${b}` : { r, g, b };
}

/**
 * Laden der Theme-Einstellungen aus dem Local Storage, mit Fallback auf Standardwerte
 */
export function loadThemeSettings(): ThemeSettings {
    // Try to load from localStorage first
    const savedThemeString = localStorage.getItem('theme-settings');
    let themeSettings: Partial<ThemeSettings> = {};
    let useLocalStorage = true;
    
    if (savedThemeString) {
        try {
            const savedTheme = JSON.parse(savedThemeString);
            
            // Check if stored settings are too old (older than 5 minutes)
            const now = new Date().getTime();
            const lastUpdated = savedTheme.lastUpdated || 0;
            const maxAge = 5 * 60 * 1000; // 5 minutes in milliseconds
            
            if (now - lastUpdated > maxAge) {
                console.log('Stored theme settings are older than 5 minutes, will refresh from server');
                useLocalStorage = false;
            } else {
                themeSettings = savedTheme;
            }
        } catch (error) {
            console.error('Error parsing stored theme settings:', error);
            useLocalStorage = false;
        }
    }
    
    // Merge stored settings with defaults
    return {
        primaryColor: themeSettings.primaryColor || defaultColors.primary,
        secondaryColor: themeSettings.secondaryColor || defaultColors.secondary,
        accentColor: themeSettings.accentColor || defaultColors.accent,
        backgroundColor: themeSettings.backgroundColor || defaultColors.background,
        surfaceColor: themeSettings.surfaceColor || defaultColors.surface,
        tertiaryColor: themeSettings.tertiaryColor || defaultColors.tertiary,
        infoColor: themeSettings.infoColor || defaultColors.info,
        successColor: themeSettings.successColor || defaultColors.success,
        warningColor: themeSettings.warningColor || defaultColors.warning,
        errorColor: themeSettings.errorColor || defaultColors.error,
        
        onPrimaryColor: themeSettings.onPrimaryColor || defaultColors.onPrimary,
        onSecondaryColor: themeSettings.onSecondaryColor || defaultColors.onSecondary,
        onAccentColor: themeSettings.onAccentColor || defaultColors.onAccent,
        onBackgroundColor: themeSettings.onBackgroundColor || defaultColors.onBackground,
        onSurfaceColor: themeSettings.onSurfaceColor || defaultColors.onSurface,
        onSuccessColor: themeSettings.onSuccessColor || defaultColors.onSuccess,
        onInfoColor: themeSettings.onInfoColor || defaultColors.onInfo,
        onWarningColor: themeSettings.onWarningColor || defaultColors.onWarning,
        onErrorColor: themeSettings.onErrorColor || defaultColors.onError,
        
        borderRadius: themeSettings.borderRadius || defaultColors.borderRadius,
        darkMode: themeSettings.darkMode !== undefined ? themeSettings.darkMode : defaultColors.darkMode,
        enableGradients: themeSettings.enableGradients !== undefined ? themeSettings.enableGradients : defaultColors.enableGradients,
        desktopBackgroundImage: themeSettings.desktopBackgroundImage
    };
}

/**
 * Load theme settings from localStorage
 * @returns Theme settings object or null if not found
 */
export function loadThemeFromStorage(): ThemeSettings | null {
    try {
        const themeSettingsStr = localStorage.getItem('theme-settings');
        if (themeSettingsStr) {
            return JSON.parse(themeSettingsStr);
        }
    } catch (e) {
        console.error('ThemeLoader: Error loading theme from storage', e);
    }
    return null;
}

/**
 * Save theme settings to localStorage
 * @param theme Theme settings object
 */
export function saveThemeToStorage(theme: ThemeSettings): void {
    try {
        localStorage.setItem('theme-settings', JSON.stringify(theme));
        console.log('ThemeLoader: Theme saved to storage');
    } catch (e) {
        console.error('ThemeLoader: Error saving theme to storage', e);
    }
}

/**
 * Wendet die Themeneinstellungen auf das DOM an
 * @param settings Die Themeneinstellungen
 */
export function applyThemeToDOM(settings?: ThemeSettings): void {
  console.log('Applying theme to DOM', settings);
  
  // Lade die Einstellungen aus dem Speicher, wenn keine übergeben wurden
  const themeSettings = settings || loadThemeSettings();
  console.log('Theme settings being applied:', themeSettings);
  
  // Die Farben aus den Einstellungen verwenden, oder die Standardfarben, wenn keine Einstellungen vorhanden sind
  const primaryColor = themeSettings.primaryColor || defaultColors.primary;
  const primaryColorRGB = hexToRgb(primaryColor, 'string') as string;
  
  const secondaryColor = themeSettings.secondaryColor || defaultColors.secondary;
  const secondaryColorRGB = hexToRgb(secondaryColor, 'string') as string;
  
  const accentColor = themeSettings.accentColor || defaultColors.accent;
  const accentColorRGB = hexToRgb(accentColor, 'string') as string;
  
  // Auch die Grundfläche folgt dem Modus, nicht der Behörden-Einstellung.
  const backgroundColor = tokensFor(themeSettings.darkMode !== false).canvas;
  const backgroundColorRGB = hexToRgb(backgroundColor, 'string') as string;
  
  const errorColor = themeSettings.errorColor || defaultColors.error;
  const errorColorRGB = hexToRgb(errorColor, 'string') as string;
  
  const infoColor = themeSettings.infoColor || defaultColors.info;
  const infoColorRGB = hexToRgb(infoColor, 'string') as string;
  
  const successColor = themeSettings.successColor || defaultColors.success;
  const successColorRGB = hexToRgb(successColor, 'string') as string;
  
  const warningColor = themeSettings.warningColor || defaultColors.warning;
  const warningColorRGB = hexToRgb(warningColor, 'string') as string;
  
  // Erstelle oder aktualisiere ein Style-Element für überschriebene Themenvariablen
  // Diese Methode hat eine höhere Priorität als inline-Styles
  const styleEl = document.getElementById('theme-variables') || document.createElement('style');
  styleEl.id = 'theme-variables';
  
  // Generate CSS for color variants with opacity
  const generateOpacityVariants = (colorName: string, rgbValue: string) => {
    return `
      --${colorName}: ${colorName === 'primary' ? primaryColor : 
                      colorName === 'secondary' ? secondaryColor : 
                      colorName === 'accent' ? accentColor : 
                      colorName === 'background' ? backgroundColor : 
                      colorName === 'error' ? errorColor : 
                      colorName === 'info' ? infoColor : 
                      colorName === 'success' ? successColor : warningColor};
      --${colorName}-rgb: ${rgbValue};
      --${colorName}-5: rgba(${rgbValue}, 0.05);
      --${colorName}-10: rgba(${rgbValue}, 0.1);
      --${colorName}-20: rgba(${rgbValue}, 0.2);
      --${colorName}-30: rgba(${rgbValue}, 0.3);
      --${colorName}-40: rgba(${rgbValue}, 0.4);
      --${colorName}-50: rgba(${rgbValue}, 0.5);
      --${colorName}-60: rgba(${rgbValue}, 0.6);
      --${colorName}-70: rgba(${rgbValue}, 0.7);
      --${colorName}-80: rgba(${rgbValue}, 0.8);
      --${colorName}-90: rgba(${rgbValue}, 0.9);
    `;
  }
  
  // Map ThemeSettings properties to defaultColors properties
  // Flächen- und Textfarben stammen immer aus den Design-Tokens des aktiven
  // Modus, nicht aus den Behörden-Einstellungen. Andernfalls bliebe der helle
  // Modus dunkel, weil die gespeicherten Werte (#111723, #1E1E1E …) für den
  // dunklen Modus gedacht sind und keinen hellen Gegenpart haben.
  // Die Marke einer Behörde steckt im Akzent (primary/secondary/accent), nicht
  // in den Flächen — siehe theme/tokens.ts.
  const uiTokens = tokensFor(themeSettings.darkMode !== false);
  const containerColor = uiTokens.surface;
  const borderColor = uiTokens.line;
  const textColor = uiTokens.ink;
  const textMutedColor = uiTokens.inkMuted;
  const inputBackground = uiTokens.surface;
  const shadowStrength = themeSettings.shadowStrength || defaultColors.shadowStrength;
  
  // Set all necessary CSS variables with !important to override any existing styles
  styleEl.innerHTML = `
    :root {
      /* Base Colors */
      ${generateOpacityVariants('primary', primaryColorRGB)}
      ${generateOpacityVariants('secondary', secondaryColorRGB)}
      ${generateOpacityVariants('accent', accentColorRGB)}
      ${generateOpacityVariants('background', backgroundColorRGB)}
      ${generateOpacityVariants('error', errorColorRGB)}
      ${generateOpacityVariants('info', infoColorRGB)}
      ${generateOpacityVariants('success', successColorRGB)}
      ${generateOpacityVariants('warning', warningColorRGB)}
      
      /* Container and UI variables */
      --container-color: ${containerColor};
      --border-color: ${borderColor};
      --text-color: ${textColor};
      --text-muted: ${textMutedColor};
      --input-bg: ${inputBackground};
      
      /* Apply global configuration */
      --border-radius: ${themeSettings.borderRadius || defaultColors.borderRadius}px;
      --shadow-strength: ${shadowStrength};
      
      /* Apply mode-specific overrides if any */
      ${themeSettings.darkMode ? 
        `--mode-bg: var(--dark-bg);
         --mode-text: var(--dark-text);` : 
        `--mode-bg: var(--light-bg);
         --mode-text: var(--light-text);`
      }
    }
  `;
  
  // Add the style element to the head if not already present
  if (!document.head.contains(styleEl)) {
    document.head.appendChild(styleEl);
    console.log('Added theme variables style element to head');
  } else {
    console.log('Updated existing theme variables style element');
  }
  
  // Set the CSS variables on the HTML element as well (double security)
  const html = document.documentElement;
  
  // Set the base colors directly on the HTML element
  html.style.setProperty('--primary', primaryColor);
  html.style.setProperty('--primary-rgb', primaryColorRGB);
  html.style.setProperty('--secondary', secondaryColor);
  html.style.setProperty('--secondary-rgb', secondaryColorRGB);
  html.style.setProperty('--accent', accentColor);
  html.style.setProperty('--accent-rgb', accentColorRGB);
  html.style.setProperty('--background', backgroundColor);
  html.style.setProperty('--background-rgb', backgroundColorRGB);
  html.style.setProperty('--error', errorColor);
  html.style.setProperty('--error-rgb', errorColorRGB);
  html.style.setProperty('--info', infoColor);
  html.style.setProperty('--info-rgb', infoColorRGB);
  html.style.setProperty('--success', successColor);
  html.style.setProperty('--success-rgb', successColorRGB);
  html.style.setProperty('--warning', warningColor);
  html.style.setProperty('--warning-rgb', warningColorRGB);
  
  // Container and UI variables
  html.style.setProperty('--container-color', containerColor);
  html.style.setProperty('--border-color', borderColor);
  html.style.setProperty('--text-color', textColor);
  html.style.setProperty('--text-muted', textMutedColor);
  html.style.setProperty('--input-bg', inputBackground);
  
  // Apply global configuration
  html.style.setProperty('--border-radius', `${themeSettings.borderRadius || defaultColors.borderRadius}px`);
  html.style.setProperty('--shadow-strength', String(shadowStrength));
  
  // Überprüfe nach einer kurzen Verzögerung, ob die Farben tatsächlich angewendet wurden
  setTimeout(() => {
    checkAppliedColors(primaryColor, secondaryColor, backgroundColor, true);
  }, 100);
  
  // --- Modusabhängige Design-Tokens ---
  // Diese bestimmen Flächen, Linien und Text. Sie stammen aus theme/tokens.ts
  // und sind der Grund, warum der Umschalter überhaupt etwas bewirkt: vorher
  // gab es nur einen modusunabhängigen Farbsatz.
  const isDark = themeSettings.darkMode !== false;
  const tokens = tokensFor(isDark);
  for (const [name, value] of Object.entries(tokensToCssVars(tokens))) {
    html.style.setProperty(name, value);
  }

  // Die gewachsenen Variablen aus main.scss sind statisch aus SCSS erzeugt und
  // damit modusunabhängig. Sie werden hier auf die Tokens des aktiven Modus
  // gezogen, sonst bleiben Karten und Flächen im hellen Modus dunkel.
  const legacyFromTokens: Record<string, string> = {
    '--surface': tokens.surface,
    '--card-bg': tokens.surface,
    '--panel-bg': tokens.surface,
    '--dialog-bg': tokens.raised,
    '--menu-bg': tokens.raised,
    '--hover-bg': tokens.rowHover,
    '--divider-color': tokens.line,
    '--on-surface': tokens.ink,
    '--on-background': tokens.ink,
    // Der Fenster-Modus hat eigene Variablen. Ohne diese Zuordnung blieben
    // Taskleiste, Fenstertitel und Symbolbeschriftungen dort weiss und damit
    // im hellen Modus unlesbar.
    '--desktop-text': tokens.ink,
    '--desktop-text-secondary': tokens.inkMuted,
    '--desktop-text-tertiary': tokens.inkFaint,
    '--desktop-text-muted': tokens.inkFaint,
    '--desktop-border': tokens.line,
    '--card-border': tokens.line,
    '--desktop-accent-blue': tokens.accent,
    '--desktop-button-highlight': tokens.rowHover,
    '--scrollbar-thumb-color': tokens.lineStrong,
    '--scrollbar-track-color': tokens.sunken,
  };
  for (const [name, value] of Object.entries(legacyFromTokens)) {
    html.style.setProperty(name, value);
  }

  // Kennzeichnet den Modus für CSS-Regeln, die sich daran hängen wollen.
  html.setAttribute('data-color-scheme', isDark ? 'dark' : 'light');
  // Damit Browser-Bedienelemente (Scrollbalken, Auswahlfelder) mitziehen.
  html.style.setProperty('color-scheme', isDark ? 'dark' : 'light');

  // Update Vuetify theme if available
  try {
    setVuetifyTheme(isDark);
    // Die Akzentfarbe der Behörde überschreibt nur den Akzent, nicht die
    // Flächen — sonst wäre der helle Modus wieder dunkel.
    applyAuthorityAccent(primaryColor);
    console.log('Updated Vuetify theme:', isDark ? 'dark' : 'light');
  } catch (e) {
    console.warn('Could not update Vuetify theme:', e);
  }
}

/**
 * Überprüft, ob die Farben korrekt angewendet wurden und zeigt Debug-Informationen an
 * @param expectedPrimary Die erwartete Primärfarbe (optional)
 * @param expectedSecondary Die erwartete Sekundärfarbe (optional)
 * @param expectedBg Die erwartete Hintergrundfarbe (optional)
 * @param forceRefresh Optional: Erzwingt die Aktualisierung auch wenn die Farben bereits gesetzt sind
 */
export function checkAppliedColors(expectedPrimary?: string, expectedSecondary?: string, expectedBg?: string, forceRefresh = false): void {
  const html = document.documentElement;
  const computedStyle = getComputedStyle(html);
  
  // Hole die aktuell angewendeten Farben und entferne Leerzeichen
  const appliedPrimary = computedStyle.getPropertyValue('--primary').trim();
  const appliedSecondary = computedStyle.getPropertyValue('--secondary').trim();
  const appliedBg = computedStyle.getPropertyValue('--background').trim();
  
  console.group('Theme Colors Check');
  console.log('CSS Variables in DOM:');
  console.log('--primary:', appliedPrimary);
  console.log('--primary-rgb:', computedStyle.getPropertyValue('--primary-rgb').trim());
  console.log('--secondary:', appliedSecondary);
  console.log('--secondary-rgb:', computedStyle.getPropertyValue('--secondary-rgb').trim());
  console.log('--accent:', computedStyle.getPropertyValue('--accent').trim());
  console.log('--error:', computedStyle.getPropertyValue('--error').trim());
  console.log('--background:', appliedBg);
  
  // Check if override style element exists
  const overrideEl = document.getElementById('theme-override');
  console.log('Theme override element exists:', !!overrideEl);
  if (overrideEl) {
    console.log('Override content:', overrideEl.innerHTML);
  }
  
  // If we have expected values, check if they match and apply overrides if needed
  if (expectedPrimary && expectedSecondary && expectedBg) {
    console.log(`Expected - Primary: ${expectedPrimary}, Secondary: ${expectedSecondary}, BG: ${expectedBg}`);
    console.log(`Applied  - Primary: ${appliedPrimary}, Secondary: ${appliedSecondary}, BG: ${appliedBg}`);
    
    // Normalisiere die Farben für den Vergleich (entferne #, konvertiere zu Kleinbuchstaben)
    const normalizeColor = (color: string) => color.replace('#', '').toLowerCase();
    const normExpPrimary = normalizeColor(expectedPrimary);
    const normExpSecondary = normalizeColor(expectedSecondary);
    const normExpBg = normalizeColor(expectedBg);
    
    const normAppliedPrimary = normalizeColor(appliedPrimary || '');
    const normAppliedSecondary = normalizeColor(appliedSecondary || '');
    const normAppliedBg = normalizeColor(appliedBg || '');
    
    // Wenn die Farben nicht übereinstimmen oder ein Refresh erzwungen wird, wende das Thema erneut an
    if (forceRefresh || 
        normAppliedPrimary !== normExpPrimary || 
        normAppliedSecondary !== normExpSecondary || 
        normAppliedBg !== normExpBg) {
      
      console.log('Colors mismatch or force refresh requested. Forcing theme application through CSS variables.');
      
      // Wende die CSS-Variablen direkt als wichtig an
      const styleEl = document.getElementById('theme-override') || document.createElement('style');
      styleEl.id = 'theme-override';
      styleEl.innerHTML = `
        :root {
          --primary: ${expectedPrimary} !important;
          --primary-rgb: ${hexToRgb(expectedPrimary, 'string') as string} !important;
          --secondary: ${expectedSecondary} !important;
          --secondary-rgb: ${hexToRgb(expectedSecondary, 'string') as string} !important;
          --background: ${expectedBg} !important;
          --background-rgb: ${hexToRgb(expectedBg, 'string') as string} !important;
        }
      `;
      
      if (!styleEl.parentNode) {
        document.head.appendChild(styleEl);
        console.log('Added override style element to head');
      } else {
        console.log('Updated existing override style element');
      }
      
      // Aktualisiere Vuetify Theme erneut
      try {
        updateVuetifyTheme();
      } catch (e) {
        console.error('Error updating Vuetify theme after override:', e);
      }
    } else {
      console.log('Colors match the expected values. No additional override needed.');
    }
  } else {
    // Wenn keine erwarteten Werte übergeben wurden, wende einen Refresh an wenn erzwungen
    if (forceRefresh) {
      console.log('Force refresh requested but no expected colors provided. Using current settings.');
      
      // Lade die aktuellen Einstellungen
      const settings = loadThemeSettings();
      
      // Erstelle ein Override-Element mit den aktuellen Farben
      const styleEl = document.getElementById('theme-override') || document.createElement('style');
      styleEl.id = 'theme-override';
      styleEl.innerHTML = `
        :root {
          --primary: ${settings.primaryColor} !important;
          --primary-rgb: ${hexToRgb(settings.primaryColor, 'string') as string} !important;
          --secondary: ${settings.secondaryColor} !important;
          --secondary-rgb: ${hexToRgb(settings.secondaryColor, 'string') as string} !important;
          --background: ${settings.backgroundColor} !important;
          --background-rgb: ${hexToRgb(settings.backgroundColor, 'string') as string} !important;
        }
      `;
      
      if (!styleEl.parentNode) {
        document.head.appendChild(styleEl);
        console.log('Added override style element to head with current settings');
      } else {
        console.log('Updated existing override style element with current settings');
      }
    } else {
      console.log('No expected colors provided and no force refresh. Just displaying current values.');
    }
  }
  
  console.groupEnd();
}

/**
 * Sets up a mutation observer to ensure theme stays applied
 * even if DOM changes remove theme variables
 */
export function setupThemeListener(): MutationObserver {
  const observer = new MutationObserver((mutations) => {
    // Check if our variables were removed
    const root = document.documentElement;
    if (!root.style.getPropertyValue('--primary')) {
      applyThemeToDOM();
    }
  });
  
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['style', 'class']
  });
  
  return observer;
}

/**
 * Diese Funktion wird für Abwärtskompatibilität beibehalten,
 * aber intern verwendet sie jetzt den ThemeStore, falls verfügbar
 */
export function initializeTheme(): void {
    console.log('ThemeLoader: initializeTheme() aufgerufen (Legacy-Methode)');
    
    try {
        // Versuche den ThemeStore zu finden, wenn möglich
        if (typeof window !== 'undefined') {
            // TypeScript-sicherer Zugriff auf die globale App-Instance
            // @ts-ignore - Wir prüfen erst zur Laufzeit, ob dies existiert
            const vueApp = window.__VUE_APP__;
            
            if (vueApp && vueApp.$pinia) {
                // @ts-ignore - Wir prüfen erst zur Laufzeit, ob dies existiert
                const themeStore = vueApp.$pinia.state.value.theme;
                
                if (themeStore && typeof themeStore.initializeTheme === 'function') {
                    console.log('ThemeLoader: Verwende ThemeStore.initializeTheme()');
                    themeStore.initializeTheme();
                    return;
                }
            }
        }
    } catch (e) {
        console.error('ThemeLoader: Fehler beim Zugriff auf den ThemeStore:', e);
    }
    
    // Fallback auf die direkte Anwendung, wenn der Store nicht verfügbar ist
    console.log('ThemeLoader: Fallback auf direkte Theme-Anwendung');
    const settings = loadThemeSettings();
    applyThemeToDOM(settings);
}

/**
 * Lädt das Theme vom Server und wendet es an
 * Diese Funktion kann auch manuell aufgerufen werden, um das Theme zu aktualisieren
 */
export async function loadThemeFromServer(): Promise<boolean> {
    try {
        // Determine the base URL for the API
        const apiBaseUrl = getApiBaseUrl();
        console.log(`ThemeLoader: Using API base URL: ${apiBaseUrl}`);
        
        console.log('ThemeLoader: Trying to load settings from server...');
        
        // Possible paths relative to the API base URL
        const possiblePaths = [
            '/admin/settings/index.php?action=getGlobalSettings'
        ];
        
        let response;
        let success = false;
        let usedUrl = '';
        
        // Try each path in sequence
        for (const path of possiblePaths) {
            const fullUrl = `${apiBaseUrl}${path}`;
            try {
                console.log(`ThemeLoader: Trying URL: ${fullUrl}`);
                
                response = await fetch(fullUrl, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    success = true;
                    usedUrl = fullUrl;
                    console.log(`ThemeLoader: Success! Theme settings loaded from ${fullUrl}`);
                    break;
                } else {
                    const errorText = await response.text();
                    console.log(`ThemeLoader: Error at ${fullUrl}: ${response.status} ${response.statusText}`, errorText);
                }
            } catch (e) {
                console.log(`ThemeLoader: Network error at ${fullUrl}:`, e);
            }
        }
        
        if (success && response?.ok) {
            const data = await response.json();
            console.log(`ThemeLoader: Data successfully loaded from ${usedUrl}:`, data);
            
            if (data && data.settings) {
                const serverSettings = data.settings;
                console.log('ThemeLoader: SERVER SETTINGS FOUND!', serverSettings);
                console.log('ThemeLoader: PRIMARY COLOR FROM SERVER:', serverSettings.primaryColor);
                
                // Type conversion for boolean and numeric values
                const processedSettings: ThemeSettings = {
                    primaryColor: serverSettings.primaryColor || defaultColors.primary,
                    secondaryColor: serverSettings.secondaryColor || defaultColors.secondary,
                    accentColor: serverSettings.accentColor || defaultColors.accent,
                    backgroundColor: serverSettings.backgroundColor || defaultColors.background,
                    surfaceColor: serverSettings.surfaceColor || defaultColors.surface,
                    tertiaryColor: serverSettings.tertiaryColor || defaultColors.tertiary,
                    infoColor: serverSettings.infoColor || defaultColors.info,
                    successColor: serverSettings.successColor || defaultColors.success,
                    warningColor: serverSettings.warningColor || defaultColors.warning,
                    errorColor: serverSettings.errorColor || defaultColors.error,
                    onPrimaryColor: serverSettings.onPrimaryColor || defaultColors.onPrimary,
                    onSecondaryColor: serverSettings.onSecondaryColor || defaultColors.onSecondary,
                    onAccentColor: serverSettings.onAccentColor || defaultColors.onAccent,
                    onBackgroundColor: serverSettings.onBackgroundColor || defaultColors.onBackground,
                    onSurfaceColor: serverSettings.onSurfaceColor || defaultColors.onSurface,
                    onSuccessColor: serverSettings.onSuccessColor || defaultColors.onSuccess,
                    onInfoColor: serverSettings.onInfoColor || defaultColors.onInfo,
                    onWarningColor: serverSettings.onWarningColor || defaultColors.onWarning,
                    onErrorColor: serverSettings.onErrorColor || defaultColors.onError,
                    borderRadius: parseInt(serverSettings.borderRadius || defaultColors.borderRadius.toString()),
                    darkMode: serverSettings.darkMode === true || serverSettings.darkMode === 'true',
                    enableGradients: serverSettings.enableGradients === true || serverSettings.enableGradients === 'true',
                    // Add timestamp for cache invalidation
                    lastUpdated: new Date().getTime()
                };
                
                // Check for desktop background image
                if (serverSettings.desktopBackgroundImage) {
                    processedSettings.desktopBackgroundImage = serverSettings.desktopBackgroundImage;
                }
                
                console.log('ThemeLoader: Processed settings:', processedSettings);
                
                // Apply theme immediately
                applyThemeToDOM(processedSettings);
                
                // Store in localStorage for quicker access next time
                saveThemeToStorage(processedSettings);
                
                console.log('ThemeLoader: Theme settings from server applied and saved to localStorage');
                return true;
            } else {
                console.warn('ThemeLoader: Data loaded, but no settings property found:', data);
            }
        } else {
            console.warn('ThemeLoader: Could not load theme settings from any URL. Using default settings.');
            
            // Apply default settings
            const defaultSettings: ThemeSettings = {
                primaryColor: defaultColors.primary,
                secondaryColor: defaultColors.secondary,
                accentColor: defaultColors.accent,
                backgroundColor: defaultColors.background,
                surfaceColor: defaultColors.surface,
                tertiaryColor: defaultColors.tertiary,
                infoColor: defaultColors.info,
                successColor: defaultColors.success,
                warningColor: defaultColors.warning,
                errorColor: defaultColors.error,
                onPrimaryColor: defaultColors.onPrimary,
                onSecondaryColor: defaultColors.onSecondary,
                onAccentColor: defaultColors.onAccent,
                onBackgroundColor: defaultColors.onBackground,
                onSurfaceColor: defaultColors.onSurface,
                onSuccessColor: defaultColors.onSuccess,
                onInfoColor: defaultColors.onInfo,
                onWarningColor: defaultColors.onWarning,
                onErrorColor: defaultColors.onError,
                borderRadius: defaultColors.borderRadius,
                darkMode: defaultColors.darkMode,
                enableGradients: defaultColors.enableGradients,
                desktopBackgroundImage: defaultColors.desktopBackgroundImage
            };
            
            applyThemeToDOM(defaultSettings);
            console.log('ThemeLoader: Default theme settings applied');
        }
    } catch (error) {
        console.error('ThemeLoader: Critical error loading theme settings:', error);
    }
    
    return false;
}

/**
 * Clear theme cache and force reload
 */
export function clearThemeCache(): void {
    try {
        localStorage.removeItem('theme-settings');
        localStorage.setItem('force_refresh_theme', 'true');
        console.log('ThemeLoader: Theme cache cleared and reload forced');
        
        forceRefreshTheme.value = true;
        
        // Apply default theme settings
        const defaultSettings: ThemeSettings = {
            primaryColor: defaultColors.primary,
            secondaryColor: defaultColors.secondary,
            accentColor: defaultColors.accent,
            backgroundColor: defaultColors.background,
            surfaceColor: defaultColors.surface,
            tertiaryColor: defaultColors.tertiary,
            infoColor: defaultColors.info,
            successColor: defaultColors.success,
            warningColor: defaultColors.warning,
            errorColor: defaultColors.error,
            onPrimaryColor: defaultColors.onPrimary,
            onSecondaryColor: defaultColors.onSecondary,
            onAccentColor: defaultColors.onAccent,
            onBackgroundColor: defaultColors.onBackground,
            onSurfaceColor: defaultColors.onSurface,
            onSuccessColor: defaultColors.onSuccess,
            onInfoColor: defaultColors.onInfo,
            onWarningColor: defaultColors.onWarning,
            onErrorColor: defaultColors.onError,
            borderRadius: defaultColors.borderRadius,
            darkMode: defaultColors.darkMode,
            enableGradients: defaultColors.enableGradients,
            desktopBackgroundImage: defaultColors.desktopBackgroundImage
        };
        
        applyThemeToDOM(defaultSettings);
    } catch (e) {
        console.error('ThemeLoader: Error clearing theme cache:', e);
    }
} 