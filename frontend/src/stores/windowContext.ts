/**
 * WindowContext Store
 * Verwaltet den Kontext und Status für ein einzelnes Desktop-Fenster
 */
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'

// Typdefinitionen
export interface WindowContext {
  windowId: string;
  isLoading: boolean;
  props: Record<string, any>;
  setLoading: (loading: boolean) => void;
  setProps: (props: Record<string, any>) => void;
  updateProps: (props: Record<string, any>) => void;
}

// Map, um alle Fenster-Stores zu verwalten
const windowStores = new Map<string, ReturnType<typeof createWindowStore>>();

// Factory-Funktion, um einen Store für ein bestimmtes Fenster zu erstellen
export function createWindowStore(windowId: string) {
  const useWindowStore = defineStore(`window-${windowId}`, () => {
    const isLoading = ref(true);
    const props = reactive<Record<string, any>>({});
    
    function setLoading(loading: boolean) {
      isLoading.value = loading;
    }
    
    function setProps(newProps: Record<string, any>) {
      // Alle Eigenschaften zurücksetzen und neue setzen
      Object.keys(props).forEach(key => {
        delete props[key];
      });
      
      Object.entries(newProps).forEach(([key, value]) => {
        props[key] = value;
      });
    }
    
    function updateProps(newProps: Record<string, any>) {
      // Bestehende Eigenschaften aktualisieren oder neue hinzufügen
      Object.entries(newProps).forEach(([key, value]) => {
        props[key] = value;
      });
    }
    
    return {
      isLoading,
      props,
      setLoading,
      setProps,
      updateProps
    };
  });
  
  return useWindowStore;
}

/**
 * Gibt den Store für ein bestimmtes Fenster zurück oder erstellt ihn, wenn er noch nicht existiert
 * 
 * @param windowId - Die ID des Fensters
 * @returns Der Pinia-Store für das Fenster
 */
export function getWindowStore(windowId: string) {
  if (!windowStores.has(windowId)) {
    const store = createWindowStore(windowId);
    windowStores.set(windowId, store);
  }
  
  return windowStores.get(windowId)!();
}

/**
 * Entfernt den Store für ein Fenster, wenn es geschlossen wird
 * 
 * @param windowId - Die ID des zu entfernenden Fensters
 */
export function removeWindowStore(windowId: string) {
  windowStores.delete(windowId);
} 