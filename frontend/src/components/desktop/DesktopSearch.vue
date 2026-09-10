<!-- Erweiterte Desktop-Suchfunktion -->
<template>
  <div class="desktop-search" :class="{ 'show': show, 'has-results': hasResults }">
    <!-- Suchfeld und Kopfzeile -->
    <div class="search-header">
      <div class="search-input-wrapper">
        <v-icon size="20" class="search-icon">mdi-magnify</v-icon>
        <input 
          type="text" 
          class="search-input" 
          v-model="searchQuery" 
          :placeholder="t('layout.searchPlaceholder')"
          @input="performSearch"
          @keydown.down.prevent="navigateResults('down')"
          @keydown.up.prevent="navigateResults('up')"
          @keydown.enter="selectResult"
          ref="searchInput"
          autofocus
        />
        <div class="search-clear" v-if="searchQuery" @click="clearSearch">
          <v-icon size="18">mdi-close</v-icon>
        </div>
      </div>
      <div class="search-close" @click="$emit('close')">
        <v-icon size="20">mdi-close</v-icon>
      </div>
    </div>

    <!-- Suchfilter -->
    <div class="search-filters" v-if="show">
      <div 
        v-for="(filter, index) in filters" 
        :key="index"
        class="filter-chip"
        :class="{ 'active': filter.active }"
        @click="toggleFilter(index)"
      >
        <v-icon size="16" class="filter-icon">{{ filter.icon }}</v-icon>
        <span class="filter-name">{{ filter.name }}</span>
      </div>
    </div>

    <!-- Suchergebnisse -->
    <div class="search-results" v-if="hasResults">
      <div v-for="(group, groupKey) in groupedResults" :key="groupKey" class="result-group">
        <div class="result-group-header">
          <v-icon size="16" :icon="getGroupIcon(groupKey)" class="result-group-icon"></v-icon>
          <span class="result-group-title">{{ getGroupTitle(groupKey) }}</span>
        </div>
        
        <div 
          v-for="(result, index) in group" 
          :key="result.id" 
          class="result-item"
          :class="{ 
            'active': selectedGroupIndex === groupKey && selectedItemIndex === index,
            'has-subtitle': result.subtitle
          }"
          @click="openResult(result)"
          @mouseenter="setSelectedResult(groupKey, index)"
        >
          <div class="result-icon-container" :style="{ backgroundColor: result.color + '20' }">
            <v-icon size="18" :color="result.color || '#64748b'">{{ result.icon }}</v-icon>
          </div>
          <div class="result-content">
            <div class="result-title">
              <span>{{ result.title }}</span>
              <span class="result-highlight" v-if="result.highlight">({{ result.highlight }})</span>
            </div>
            <div class="result-subtitle" v-if="result.subtitle">{{ result.subtitle }}</div>
          </div>
          <div class="result-action" v-if="result.action">
            <v-icon size="16" color="gray">{{ result.actionIcon || 'mdi-arrow-right' }}</v-icon>
          </div>
        </div>

        <div class="result-view-more" v-if="group.length > 5 && !showAllResults[groupKey]" @click="showMoreResults(groupKey)">
          {{ t('layout.viewMore', { count: group.length - 5 }) }}
        </div>
      </div>

      <!-- Keine Ergebnisse -->
      <div class="no-results" v-if="noResults">
        <v-icon size="40" color="#64748b">mdi-file-search-outline</v-icon>
        <div class="no-results-text">{{ t('layout.noResults', { query: searchQuery }) }}</div>
      </div>
    </div>

    <!-- Suchvorschläge, wenn keine Suche aktiv ist -->
    <div class="search-suggestions" v-if="!searchQuery && show">
      <div class="suggestion-header">
        <span>{{ t('layout.recentSearches') }}</span>
        <span class="clear-history" @click="clearSearchHistory">{{ t('layout.deleteLabel') }}</span>
      </div>
      <div 
        v-for="(item, index) in recentSearches" 
        :key="index"
        class="suggestion-item"
        @click="applySearchQuery(item)"
      >
        <v-icon size="16" color="#64748b">mdi-history</v-icon>
        <span class="suggestion-text">{{ item }}</span>
      </div>

      <div class="suggestion-section">
        <div class="suggestion-header">
          <span>{{ t('layout.quickAccess') }}</span>
        </div>
        <div class="quick-access-grid">
          <div 
            v-for="(app, index) in quickAccessApps" 
            :key="index"
            class="quick-access-item"
            @click="openResult(app)"
          >
            <div class="quick-access-icon" :style="{ backgroundColor: app.color + '20' }">
              <v-icon size="20" :color="app.color">{{ app.icon }}</v-icon>
            </div>
            <span class="quick-access-name">{{ app.title }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tastenkombinationen -->
    <div class="search-shortcuts" v-if="hasResults">
      <div class="shortcut-item">
        <div class="shortcut-key">↑↓</div>
        <div class="shortcut-label">{{ t('layout.shortcutNavigation') }}</div>
      </div>
      <div class="shortcut-item">
        <div class="shortcut-key">Enter</div>
        <div class="shortcut-label">{{ t('layout.shortcutOpen') }}</div>
      </div>
      <div class="shortcut-item">
        <div class="shortcut-key">Esc</div>
        <div class="shortcut-label">{{ t('layout.shortcutClose') }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';

// Props und Emits
interface Props {
  show?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  show: false
});

const emit = defineEmits(['close', 'open-result']);
const { t } = useI18n();

// Suchfeld-Referenz
const searchInput = ref<HTMLInputElement | null>(null);

// Suchstatus
const searchQuery = ref('');
const isSearching = ref(false);
const selectedGroupIndex = ref('');
const selectedItemIndex = ref(-1);
const showAllResults = ref<Record<string, boolean>>({});

// Filter-Optionen
const filters = ref([
  { name: t('layout.filterAll'), icon: 'mdi-magnify', active: true },
  { name: t('layout.filterApps'), icon: 'mdi-application', active: false },
  { name: t('layout.filterDocuments'), icon: 'mdi-file-document-outline', active: false },
  { name: t('layout.filterMessages'), icon: 'mdi-email-outline', active: false },
  { name: t('layout.filterContacts'), icon: 'mdi-account-outline', active: false },
  { name: t('layout.filterCalendar'), icon: 'mdi-calendar', active: false },
]);

// Letzte Suchen
const recentSearches = ref([
  t('layout.searchExampleReportForm'),
  t('layout.searchExampleOperationPlans'),
  t('layout.searchExampleDocument2022'),
  t('layout.searchExampleTrainingPlan'),
  t('layout.searchExampleMaintenancePlan')
]);

// Schnellzugriff-Apps
const quickAccessApps = ref([
  { id: 'calendar', title: t('calendar'), icon: 'mdi-calendar', color: '#ef4444', action: 'open' },
  { id: 'messages', title: t('messages'), icon: 'mdi-email', color: '#f59e0b', action: 'open' },
  { id: 'reports', title: t('reports'), icon: 'mdi-file-document', color: '#ec4899', action: 'open' },
  { id: 'files', title: t('fileManager'), icon: 'mdi-folder', color: '#8b5cf6', action: 'open' },
  { id: 'employee', title: t('employee'), icon: 'mdi-account-group', color: '#0ea5e9', action: 'open' },
  { id: 'settings', title: t('settings'), icon: 'mdi-cog', color: '#64748b', action: 'open' }
]);

// Beispiel-Suchergebnisse (würden in der Realität über API abgefragt)
const searchResults = ref<any[]>([]);

// Sortierte/gruppierte Ergebnisse
const groupedResults = computed(() => {
  if (!searchResults.value.length) return {};

  // Filtern nach aktiven Filtern
  let filteredResults = [...searchResults.value];
  const activeFilters = filters.value.filter(f => f.active && f.name !== t('layout.filterAll'));
  
  if (activeFilters.length > 0) {
    // Wenn Filter aktiv sind, nur passende Typen anzeigen
    const filterApps = t('layout.filterApps');
    const filterDocuments = t('layout.filterDocuments');
    const filterMessages = t('layout.filterMessages');
    const filterContacts = t('layout.filterContacts');
    const filterCalendar = t('layout.filterCalendar');

    const allowedTypes = activeFilters
      .map(f => {
        switch (f.name) {
          case filterApps:
            return 'app';
          case filterDocuments:
            return 'document';
          case filterMessages:
            return 'message';
          case filterContacts:
            return 'contact';
          case filterCalendar:
            return 'event';
          default:
            return '';
        }
      })
      .filter(Boolean);
    
    if (allowedTypes.length) {
      filteredResults = filteredResults.filter(item => allowedTypes.includes(item.type));
    }
  }

  // Nach Typ gruppieren
  const grouped: Record<string, any[]> = {};
  
  filteredResults.forEach(result => {
    if (!grouped[result.type]) {
      grouped[result.type] = [];
    }
    
    // Für jede Gruppe nur die ersten X anzeigen, wenn nicht "Alle anzeigen" aktiviert ist
    if (grouped[result.type].length < 5 || showAllResults.value[result.type]) {
      grouped[result.type].push(result);
    }
  });
  
  return grouped;
});

// Berechnete Eigenschaften
const hasResults = computed(() => {
  return searchQuery.value.length > 0 && searchResults.value.length > 0;
});

const noResults = computed(() => {
  return searchQuery.value.length > 0 && searchResults.value.length === 0;
});

// Methoden
const performSearch = async () => {
  if (!searchQuery.value) {
    searchResults.value = [];
    return;
  }
  
  isSearching.value = true;
  
  // Hier würde in der Praxis ein API-Aufruf erfolgen
  // Simuliere asynchrone Suche
  setTimeout(() => {
    // Mock-Suchergebnisse
    const query = searchQuery.value.toLowerCase();
    const mockResults = [
      // Apps
      { id: 'app1', type: 'app', title: 'Kalender', icon: 'mdi-calendar', color: '#ef4444' },
      { id: 'app2', type: 'app', title: 'Nachrichten', icon: 'mdi-email', color: '#f59e0b' },
      { id: 'app3', type: 'app', title: 'Berichte', icon: 'mdi-file-document', color: '#ec4899' },
      { id: 'app4', type: 'app', title: 'Dateien', icon: 'mdi-folder', color: '#8b5cf6' },
      { id: 'app5', type: 'app', title: 'Einstellungen', icon: 'mdi-cog', color: '#64748b' },
      
      // Dokumente
      { id: 'doc1', type: 'document', title: 'Einsatzberichte 2023', subtitle: 'PDF • 5MB • Bearbeitet: Gestern', icon: 'mdi-file-pdf-box', color: '#ef4444' },
      { id: 'doc2', type: 'document', title: 'Wartungsplan', subtitle: 'Excel • 1.2MB • Bearbeitet: Vor 3 Tagen', icon: 'mdi-file-excel', color: '#22c55e' },
      { id: 'doc3', type: 'document', title: 'Personalakte Max Mustermann', subtitle: 'Word • 800KB • Bearbeitet: 15.05.2023', icon: 'mdi-file-word', color: '#0ea5e9' },
      { id: 'doc4', type: 'document', title: 'Schulungsplan 2023', subtitle: 'PDF • 2.3MB • Bearbeitet: 01.06.2023', icon: 'mdi-file-pdf-box', color: '#ef4444' },
      { id: 'doc5', type: 'document', title: 'Inventarliste', subtitle: 'Excel • 3.5MB • Bearbeitet: 12.06.2023', icon: 'mdi-file-excel', color: '#22c55e' },
      { id: 'doc6', type: 'document', title: 'Zugangsberechtigungen', subtitle: 'PDF • 1.1MB • Bearbeitet: 10.06.2023', icon: 'mdi-file-pdf-box', color: '#ef4444' },
      
      // Nachrichten
      { id: 'msg1', type: 'message', title: 'Max Mustermann', subtitle: 'Einsatzbesprechung morgen um 9 Uhr', icon: 'mdi-email', color: '#f59e0b' },
      { id: 'msg2', type: 'message', title: 'Erika Musterfrau', subtitle: 'Bitte Bericht bis Freitag einreichen', icon: 'mdi-email', color: '#f59e0b' },
      { id: 'msg3', type: 'message', title: 'Thomas Schmidt', subtitle: 'Neues Schulungsmaterial verfügbar', icon: 'mdi-email', color: '#f59e0b' },
      
      // Kontakte
      { id: 'contact1', type: 'contact', title: 'Max Mustermann', subtitle: 'Leiter • Abteilung Einsatz', icon: 'mdi-account', color: '#0ea5e9' },
      { id: 'contact2', type: 'contact', title: 'Erika Musterfrau', subtitle: 'Verwaltung • Personalabteilung', icon: 'mdi-account', color: '#0ea5e9' },
      
      // Termine
      { id: 'event1', type: 'event', title: 'Teambesprechung', subtitle: 'Morgen • 09:00 Uhr • Konferenzraum', icon: 'mdi-calendar-clock', color: '#8b5cf6' },
      { id: 'event2', type: 'event', title: 'Fortbildung', subtitle: '15.07.2023 • 10:00 Uhr • Schulungsraum', icon: 'mdi-calendar-clock', color: '#8b5cf6' },
    ];
    
    // Ergebnisse filtern basierend auf der Suchanfrage
    searchResults.value = mockResults.filter(result => {
      return result.title.toLowerCase().includes(query) || 
            (result.subtitle && result.subtitle.toLowerCase().includes(query));
    });
    
    // Hervorhebung der Suchergebnisse
    searchResults.value.forEach(result => {
      if (result.title.toLowerCase().includes(query)) {
        // Einfügen von Highlight-Informationen, wenn Titel übereinstimmt
        result.highlight = result.title.toLowerCase().includes(query) ?
          t('layout.highlightContains', { query }) : '';
      }
    });
    
    // Gruppen und Ergebnis-Navigation zurücksetzen
    selectedGroupIndex.value = Object.keys(groupedResults.value)[0] || '';
    selectedItemIndex.value = 0;
    isSearching.value = false;
    
    // Speichern der Suchanfrage im Verlauf, wenn sie noch nicht existiert
    if (searchQuery.value && !recentSearches.value.includes(searchQuery.value)) {
      recentSearches.value.unshift(searchQuery.value);
      // Auf 5 letzte Suchen begrenzen
      if (recentSearches.value.length > 5) {
        recentSearches.value = recentSearches.value.slice(0, 5);
      }
    }
  }, 250);
};

// Filter umschalten
const toggleFilter = (index: number) => {
  // Wenn "Alle" aktiviert wird, alle anderen Filter deaktivieren
  if (index === 0) {
    filters.value.forEach((filter, i) => {
      filter.active = i === 0;
    });
  } else {
    // "Alle" Filter deaktivieren, wenn ein anderer Filter aktiviert wird
    filters.value[0].active = false;
    filters.value[index].active = !filters.value[index].active;
    
    // Wenn kein Filter aktiv ist, "Alle" wieder aktivieren
    if (!filters.value.some(filter => filter.active)) {
      filters.value[0].active = true;
    }
  }
  
  // Nach Filteränderung Suchergebnisse neu berechnen
  if (searchQuery.value) {
    performSearch();
  }
};

// Durch Ergebnisse navigieren
const navigateResults = (direction: 'up' | 'down') => {
  if (!hasResults.value) return;
  
  const groups = Object.keys(groupedResults.value);
  
  if (groups.length === 0) return;
  
  // Falls noch keine Selektion, erste Gruppe und erstes Element wählen
  if (!selectedGroupIndex.value) {
    selectedGroupIndex.value = groups[0];
    selectedItemIndex.value = 0;
    return;
  }
  
  const currentGroup = groupedResults.value[selectedGroupIndex.value];
  
  if (direction === 'down') {
    // Innerhalb der aktuellen Gruppe nach unten
    if (selectedItemIndex.value < currentGroup.length - 1) {
      selectedItemIndex.value++;
    } else {
      // Zur nächsten Gruppe wechseln
      const currentGroupIndex = groups.indexOf(selectedGroupIndex.value);
      if (currentGroupIndex < groups.length - 1) {
        selectedGroupIndex.value = groups[currentGroupIndex + 1];
        selectedItemIndex.value = 0;
      }
    }
  } else if (direction === 'up') {
    // Innerhalb der aktuellen Gruppe nach oben
    if (selectedItemIndex.value > 0) {
      selectedItemIndex.value--;
    } else {
      // Zur vorherigen Gruppe wechseln
      const currentGroupIndex = groups.indexOf(selectedGroupIndex.value);
      if (currentGroupIndex > 0) {
        selectedGroupIndex.value = groups[currentGroupIndex - 1];
        const prevGroupLength = groupedResults.value[selectedGroupIndex.value].length;
        selectedItemIndex.value = prevGroupLength - 1;
      }
    }
  }
};

// Ausgewähltes Ergebnis setzen
const setSelectedResult = (groupKey: string, index: number) => {
  selectedGroupIndex.value = groupKey;
  selectedItemIndex.value = index;
};

// Suchergebnis öffnen
const selectResult = () => {
  if (!hasResults.value || !selectedGroupIndex.value || selectedItemIndex.value === -1) return;
  
  const selectedGroup = groupedResults.value[selectedGroupIndex.value];
  if (selectedGroup && selectedGroup[selectedItemIndex.value]) {
    openResult(selectedGroup[selectedItemIndex.value]);
  }
};

// Ergebnis öffnen
const openResult = (result: any) => {
  emit('open-result', result);
  emit('close');
};

// Suchverlauf löschen
const clearSearchHistory = () => {
  recentSearches.value = [];
};

// Suche löschen
const clearSearch = () => {
  searchQuery.value = '';
  searchResults.value = [];
};

// Suchanfrage aus Suchverlauf übernehmen
const applySearchQuery = (query: string) => {
  searchQuery.value = query;
  performSearch();
};

// Weitere Ergebnisse einer Gruppe anzeigen
const showMoreResults = (groupKey: string) => {
  showAllResults.value[groupKey] = true;
};

// Gruppensymbole abrufen
const getGroupIcon = (groupKey: string): string => {
  const iconMap: Record<string, string> = {
    'app': 'mdi-application',
    'document': 'mdi-file-document-outline',
    'message': 'mdi-email-outline',
    'contact': 'mdi-account-outline',
    'event': 'mdi-calendar'
  };
  
  return iconMap[groupKey] || 'mdi-magnify';
};

// Gruppentitel abrufen
const getGroupTitle = (groupKey: string): string => {
  const titleMap: Record<string, string> = {
    'app': t('layout.groupApplications'),
    'document': t('layout.groupDocuments'),
    'message': t('layout.groupMessages'),
    'contact': t('layout.groupContacts'),
    'event': t('layout.groupEvents')
  };

  return titleMap[groupKey] || t('layout.groupResults');
};

// Beim Anzeigen automatisch auf das Suchfeld fokussieren
watch(() => props.show, (newValue) => {
  if (newValue) {
    nextTick(() => {
      if (searchInput.value) {
        searchInput.value.focus();
      }
    });
  } else {
    // Beim Schließen die Suche zurücksetzen
    if (!searchQuery.value) {
      clearSearch();
    }
  }
});

// Bei Eingabe automatisch suchen
watch(searchQuery, () => {
  performSearch();
});
</script>

<style scoped>
.desktop-search {
  position: fixed;
  top: 30%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.92);
  width: 720px;
  max-width: 90vw;
  background: var(--k-raised);
  border-radius: 6px;
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.5),
    0 8px 24px rgba(0, 0, 0, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  border: 1px solid var(--k-line);
  padding: 20px;
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.desktop-search.show {
  opacity: 1;
  pointer-events: all;
  transform: translate(-50%, -50%) scale(1);
  animation: searchAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes searchAppear {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.92) translateY(-20px);
  }
  100% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1) translateY(0);
  }
}

.desktop-search.has-results {
  top: 50%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
}

.search-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.search-input-wrapper {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  background: var(--k-row-hover);
  border-radius: 6px;
  padding: 0 16px;
  transition: all 0.2s ease;
}

.search-input-wrapper:focus-within {
  background: var(--k-row-hover);
  box-shadow: 0 0 0 2px rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.3);
}

.search-icon {
  color: var(--k-ink-faint);
  margin-right: 8px;
}

.search-input {
  background: transparent;
  border: none;
  height: 48px;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
  font-size: 16px;
  width: 100%;
  outline: none;
}

.search-input::placeholder {
  color: var(--k-ink-faint);
}

.search-clear {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  cursor: pointer;
  color: var(--k-ink-faint);
  transition: all 0.2s ease;
}

.search-clear:hover {
  background-color: var(--k-row-hover);
  color: var(--k-ink-muted);
}

.search-close {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--k-row-hover);
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-close:hover {
  background: var(--k-row-hover);
}

/* Filter Styling */
.search-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.filter-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 6px;
  background: var(--k-row-hover);
  border: 1px solid var(--k-line);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
}

.filter-chip:hover {
  background: var(--k-row-hover);
  border-color: var(--k-line);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.filter-chip.active {
  background: var(--k-accent-weak);
  border-color: rgba(59, 130, 246, 0.4);
  color: #60a5fa;
  box-shadow: none;
}

.filter-icon {
  opacity: 0.8;
  transition: transform 0.25s ease;
}

.filter-chip:hover .filter-icon,
.filter-chip.active .filter-icon {
  transform: scale(1.1);
}

/* Ergebnisse Styling */
.search-results {
  display: flex;
  flex-direction: column;
  gap: 20px;
  overflow-y: auto;
  flex: 1;
  margin: 5px -16px;
  padding: 0 16px 10px;
}

.result-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.result-group-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 12px 10px;
  color: var(--k-ink-faint);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.result-group-icon {
  opacity: 0.6;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
  position: relative;
}

.result-item::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 6px;
  background: var(--k-row-hover);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.result-item:hover::before {
  opacity: 1;
}

.result-item:hover {
  background: var(--k-row-hover);
  border-color: var(--k-line);
  transform: translateX(4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.result-item.active {
  background: rgba(59, 130, 246, 0.15);
  border-color: rgba(59, 130, 246, 0.3);
  box-shadow: none;
}

.result-icon-container {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  flex-shrink: 0;
  transition: all 0.25s ease;
}

.result-item:hover .result-icon-container {
  transform: scale(1.1);
}

.result-content {
  flex: 1;
  overflow: hidden;
}

.result-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-highlight {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.5));
  font-weight: normal;
}

.result-subtitle {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.55));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
}

.result-action {
  padding: 8px;
  opacity: 0;
  transition: all 0.2s ease;
  transform: translateX(-4px);
}

.result-item:hover .result-action {
  opacity: 0.6;
  transform: translateX(0);
}

.result-view-more {
  padding: 12px;
  margin-top: 4px;
  display: flex;
  justify-content: center;
  font-size: 13px;
  font-weight: 500;
  color: var(--desktop-accent-blue, var(--k-accent));
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.result-view-more:hover {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

/* Suchvorschläge Styling */
.search-suggestions {
  padding: 0 0 10px;
  overflow-y: auto;
  flex: 1;
}

.suggestion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 12px 10px;
  color: var(--k-ink-faint);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.clear-history {
  font-size: 11px;
  font-weight: 600;
  color: var(--desktop-accent-blue, var(--k-accent));
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.clear-history:hover {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
}

.suggestion-item:hover {
  background: var(--k-row-hover);
  border-color: var(--k-line);
  transform: translateX(4px);
}

.suggestion-text {
  font-size: 14px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.85));
}

.suggestion-section {
  margin-top: 20px;
}

.quick-access-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 10px;
}

.quick-access-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 12px;
  border-radius: 6px;
  background: var(--k-row-hover);
  border: 1px solid var(--k-line);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.quick-access-item:hover {
  background: var(--k-row-hover);
  border-color: var(--k-line);
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.quick-access-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  margin-bottom: 10px;
  transition: transform 0.3s ease;
}

.quick-access-item:hover .quick-access-icon {
  transform: scale(1.1) rotate(5deg);
}

.quick-access-name {
  font-size: 12px;
  font-weight: 500;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.75));
  text-align: center;
}

/* Tastenkombinationen */
.search-shortcuts {
  display: flex;
  justify-content: center;
  gap: 16px;
  padding: 14px 0 0;
  border-top: 1px solid var(--k-line);
  margin-top: 8px;
}

.shortcut-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.shortcut-key {
  padding: 6px 10px;
  border-radius: 6px;
  background: var(--k-row-hover);
  border: 1px solid var(--k-line);
  font-size: 11px;
  font-weight: 600;
  color: var(--desktop-text, rgba(255, 255, 255, 0.9));
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  min-width: 32px;
  text-align: center;
}

.shortcut-label {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
}

/* Keine Ergebnisse Styling */
.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 50px 20px;
  opacity: 0.6;
}

.no-results-text {
  margin-top: 20px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.55));
  font-size: 14px;
  font-weight: 500;
  text-align: center;
}

/* Angepasste Scrollbar */
.search-results::-webkit-scrollbar,
.search-suggestions::-webkit-scrollbar {
  width: 8px;
}

.search-results::-webkit-scrollbar-track,
.search-suggestions::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  margin: 4px 0;
}

.search-results::-webkit-scrollbar-thumb,
.search-suggestions::-webkit-scrollbar-thumb {
  background: var(--k-row-hover);
  border-radius: 4px;
  border: 2px solid transparent;
  background-clip: padding-box;
  transition: background 0.2s ease;
}

.search-results::-webkit-scrollbar-thumb:hover,
.search-suggestions::-webkit-scrollbar-thumb:hover {
  background: var(--k-row-hover);
}
</style> 