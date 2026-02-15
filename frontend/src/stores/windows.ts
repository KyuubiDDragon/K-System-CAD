/**
 * Windows Store
 * Verwaltet alle Desktop-Fenster der Anwendung
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getWindowStore } from './windowContext'

// Typdefinitionen
export interface AppWindow {
  id: string;
  title: string;
  icon: string;
  route?: string;
  appId?: string;
  x: number;
  y: number;
  width: number;
  height: number;
  minimized: boolean;
  maximized: boolean;
  theme?: string;
}

export const useWindowsStore = defineStore('windows', () => {
  // Zustand
  const windows = ref<AppWindow[]>([]);
  const activeWindowId = ref<string | null>(null);
  
  // Getters
  const activeWindow = computed(() => 
    windows.value.find(window => window.id === activeWindowId.value)
  );
  
  // Aktionen
  function addWindow(window: AppWindow) {
    windows.value.push(window);
    activeWindowId.value = window.id;
    
    // Window-spezifischen Store erstellen
    getWindowStore(window.id);
  }
  
  function closeWindow(id: string) {
    const index = windows.value.findIndex(window => window.id === id);
    if (index !== -1) {
      windows.value.splice(index, 1);
      
      // Aktiviere das nächste verfügbare Fenster
      if (activeWindowId.value === id) {
        activeWindowId.value = windows.value.length > 0 ? windows.value[windows.value.length - 1].id : null;
      }
    }
  }
  
  function minimizeWindow(id: string) {
    const window = windows.value.find(window => window.id === id);
    if (window) {
      window.minimized = !window.minimized;
      
      // Wenn minimiert, aktives Fenster ändern
      if (window.minimized && activeWindowId.value === id) {
        activeWindowId.value = windows.value.find(w => !w.minimized)?.id || null;
      }
    }
  }
  
  function maximizeWindow(id: string) {
    const window = windows.value.find(window => window.id === id);
    if (window) {
      window.maximized = !window.maximized;
    }
  }
  
  function toggleMaximizeWindow(id: string) {
    const window = windows.value.find(window => window.id === id);
    if (window) {
      window.maximized = !window.maximized;
      
      // Wenn ein Fenster maximiert wird, soll es auch fokussiert werden
      if (window.maximized) {
        activeWindowId.value = id;
      }
    }
  }
  
  function focusWindow(id: string) {
    activeWindowId.value = id;
  }
  
  function updateWindowPosition(id: string, x: number, y: number, width?: number, height?: number) {
    const window = windows.value.find(window => window.id === id);
    if (window) {
      window.x = x;
      window.y = y;
      
      // Optional auch die Größe aktualisieren
      if (width !== undefined) window.width = width;
      if (height !== undefined) window.height = height;
    }
  }
  
  function updateWindowSize(id: string, width: number, height: number) {
    const window = windows.value.find(window => window.id === id);
    if (window) {
      window.width = width;
      window.height = height;
    }
  }
  
  // Return store
  return {
    windows,
    activeWindowId,
    activeWindow,
    addWindow,
    closeWindow,
    minimizeWindow,
    maximizeWindow,
    toggleMaximizeWindow,
    focusWindow,
    updateWindowPosition,
    updateWindowSize
  }
}) 