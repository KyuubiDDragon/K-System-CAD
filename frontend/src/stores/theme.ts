import { defineStore } from 'pinia';
import type { ThemeSettings } from '@/utils/themeLoader';
import { applyThemeToDOM, loadThemeSettings } from '@/utils/themeLoader';
import { apiClientAuth } from '@/api';

// Interface für globale Einstellungen
export interface GlobalSettings {
  siteName?: string;
  siteLogo?: string;
  [key: string]: any;
}

export const useThemeStore = defineStore('theme', {
  state: () => ({
    themeSettings: {} as ThemeSettings,
    globalSettings: {
      siteName: 'K-Systems', // Default-Wert
      siteLogo: '/img/logo.png' // Default-Wert
    } as GlobalSettings,
    isLoading: false,
    isInitialized: false,
    isDarkTheme: true,
  }),
  
  getters: {
    // Getter als Computed Properties (ohne Klammern beim Aufruf)
    getThemeSettings: (state) => state.themeSettings,
    getGlobalSettings: (state) => state.globalSettings,
    getSiteName: (state) => state.globalSettings.siteName || 'K-Systems',
    getSiteLogo: (state) => state.globalSettings.siteLogo || '/img/logo.png',
    getPrimaryColor: (state) => state.themeSettings.primaryColor || '#2B62C4',
    getBackgroundColor: (state) => state.themeSettings.backgroundColor || '#111723',
    isDark: (state) => state.isDarkTheme
  },
  
  actions: {
    /**
     * Initialisiert das Theme mit den Einstellungen aus dem localStorage oder Standardwerten
     */
    initializeTheme() {
      if (this.isInitialized) return;
      
      this.isLoading = true;
      
      // Lade initiale Theme-Einstellungen (aus localStorage oder Default-Werte)
      const initialSettings = loadThemeSettings();
      this.themeSettings = initialSettings;
      this.isDarkTheme = initialSettings.darkMode;
      
      // Wende das Theme auf das DOM an
      applyThemeToDOM(this.themeSettings);
      
      this.isLoading = false;
      this.isInitialized = true;
      
      // Lade die globalen Einstellungen und Theme-Einstellungen vom Server
      this.loadGlobalSettings();
    },
    
    /**
     * Lädt alle globalen Einstellungen vom Server, einschließlich Theme und siteName
     */
    async loadGlobalSettings() {
      try {
        this.isLoading = true;
        console.log('ThemeStore: Lade globale Einstellungen vom Backend...');
        
        const response = await apiClientAuth.get('/admin/settings/?action=getGlobalSettings');
        console.log('ThemeStore: API-Antwort erhalten:', response.data);
        
        if (response.data) {
          // Alle globalen Einstellungen speichern
          this.globalSettings = {
            ...this.globalSettings, // Behalte Default-Werte
            ...response.data // Überschreibe mit Server-Werten
          };
          
          console.log('ThemeStore: Globale Einstellungen geladen:', this.globalSettings);
          console.log('ThemeStore: siteName ist jetzt:', this.globalSettings.siteName);
          
          // Jetzt auch die Theme-Einstellungen aktualisieren
          this.loadThemeFromDatabase(response.data);
          
          return true;
        } else {
          console.warn('ThemeStore: Keine Daten in der API-Antwort gefunden', response.data);
          return false;
        }
      } catch (error) {
        console.error('ThemeStore: Fehler beim Laden der globalen Einstellungen:', error);
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    /**
     * Lädt die Theme-Einstellungen aus der Datenbank über die API
     */
    async loadThemeFromDatabase(themeSettings: any = null) {
      try {
        this.isLoading = true;
        
        // Verwende die übergebenen Einstellungen oder hole sie vom Server
        let settings = themeSettings;
        if (!settings) {
          console.log('ThemeStore: Lade Theme-Einstellungen vom Backend...');
          const response = await apiClientAuth.get('/admin/settings/?action=getGlobalSettings');
          if (response.data) {
            settings = response.data;
          } else {
            console.warn('ThemeStore: Keine Daten in der API-Antwort gefunden');
            return false;
          }
        }
        
        console.log('ThemeStore: Theme-Einstellungen verarbeiten:', settings);
        
        // Erstelle ein neues Theme-Objekt mit allen notwendigen Eigenschaften
        const processedSettings: ThemeSettings = {
          primaryColor: settings.primaryColor || this.themeSettings.primaryColor,
          secondaryColor: settings.secondaryColor || this.themeSettings.secondaryColor,
          accentColor: settings.accentColor || this.themeSettings.accentColor,
          backgroundColor: settings.backgroundColor || this.themeSettings.backgroundColor,
          surfaceColor: settings.surfaceColor || this.themeSettings.surfaceColor,
          tertiaryColor: settings.tertiaryColor || this.themeSettings.tertiaryColor,
          infoColor: settings.infoColor || this.themeSettings.infoColor,
          successColor: settings.successColor || this.themeSettings.successColor,
          warningColor: settings.warningColor || this.themeSettings.warningColor,
          errorColor: settings.errorColor || this.themeSettings.errorColor,
          onPrimaryColor: settings.onPrimaryColor || this.themeSettings.onPrimaryColor,
          onSecondaryColor: settings.onSecondaryColor || this.themeSettings.onSecondaryColor,
          onAccentColor: settings.onAccentColor || this.themeSettings.onAccentColor,
          onBackgroundColor: settings.onBackgroundColor || this.themeSettings.onBackgroundColor,
          onSurfaceColor: settings.onSurfaceColor || this.themeSettings.onSurfaceColor,
          onSuccessColor: settings.onSuccessColor || this.themeSettings.onSuccessColor,
          onInfoColor: settings.onInfoColor || this.themeSettings.onInfoColor,
          onWarningColor: settings.onWarningColor || this.themeSettings.onWarningColor,
          onErrorColor: settings.onErrorColor || this.themeSettings.onErrorColor,
          borderRadius: parseInt(settings.borderRadius) || this.themeSettings.borderRadius,
          darkMode: settings.darkMode === 'true' || this.themeSettings.darkMode,
          enableGradients: settings.enableGradients === 'true' || this.themeSettings.enableGradients
        };
        
        // Theme im Store aktualisieren
        this.themeSettings = processedSettings;
        this.isDarkTheme = processedSettings.darkMode;
        
        // Theme auf DOM anwenden
        applyThemeToDOM(processedSettings);
        
        // Speichere die Einstellungen im localStorage mit Zeitstempel
        localStorage.setItem('theme-settings', JSON.stringify({
          ...processedSettings,
          lastUpdated: new Date().getTime()
        }));
        
        console.log('ThemeStore: Theme erfolgreich aus der Datenbank angewendet');
        return true;
      } catch (error) {
        console.error('ThemeStore: Fehler beim Verarbeiten der Theme-Einstellungen:', error);
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    /**
     * Wechselt zwischen Hell- und Dunkel-Modus
     */
    toggleDarkMode() {
      this.isDarkTheme = !this.isDarkTheme;
      
      // Aktualisiere auch die Theme-Einstellungen
      this.themeSettings.darkMode = this.isDarkTheme;
      
      // Wende das Theme auf das DOM an
      applyThemeToDOM(this.themeSettings);
      
      // Speichere die Einstellungen im localStorage mit Zeitstempel
      localStorage.setItem('theme-settings', JSON.stringify({
        ...this.themeSettings,
        lastUpdated: new Date().getTime()
      }));
    },
    
    /**
     * Setzt ein bestimmtes Theme-Setting und wendet es an
     */
    setThemeSetting(key: keyof ThemeSettings, value: any) {
      if (this.themeSettings) {
        this.themeSettings[key] = value;
        
        // Wende das Theme auf das DOM an
        applyThemeToDOM(this.themeSettings);
        
        // Speichere die Einstellungen im localStorage mit Zeitstempel
        localStorage.setItem('theme-settings', JSON.stringify({
          ...this.themeSettings,
          lastUpdated: new Date().getTime()
        }));
      }
    },
    
    /**
     * Setzt alle Theme-Einstellungen auf einmal und wendet sie an
     */
    setThemeSettings(settings: Partial<ThemeSettings>) {
      this.themeSettings = { ...this.themeSettings, ...settings };
      this.isDarkTheme = this.themeSettings.darkMode;
      
      // Wende das Theme auf das DOM an
      applyThemeToDOM(this.themeSettings);
      
      // Speichere die Einstellungen im localStorage mit Zeitstempel
      localStorage.setItem('theme-settings', JSON.stringify({
        ...this.themeSettings,
        lastUpdated: new Date().getTime()
      }));
    }
  },
}); 