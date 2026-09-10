<template>
  <v-dialog
    v-model="isOpen"
    max-width="800"
    :fullscreen="$vuetify.display.mobile"
    transition="dialog-top-transition"
    :persistent="false"
    @click:outside="close"
  >
    <v-card class="global-search-container">
      <!-- Search Header -->
      <div class="search-header">
        <v-text-field
          ref="searchInput"
          v-model="searchQuery"
          :placeholder="$t('globalSearch.placeholder')"
          variant="solo-filled"
          flat
          hide-details
          autofocus
          density="comfortable"
          class="search-input"
          @keydown.esc="close"
          @keydown.enter="handleEnter"
          @keydown.up.prevent="navigateUp"
          @keydown.down.prevent="navigateDown"
        >
          <template v-slot:prepend-inner>
            <v-icon size="24">mdi-magnify</v-icon>
          </template>
          <template v-slot:append-inner>
            <v-chip
              v-if="activeCategory"
              size="small"
              closable
              @click:close="clearCategory"
              class="category-chip"
            >
              {{ getCategoryLabel(activeCategory) }}
            </v-chip>
            <kbd class="search-shortcut">ESC</kbd>
          </template>
        </v-text-field>
      </div>

      <!-- Category Tabs -->
      <v-tabs
        v-model="selectedTab"
        density="comfortable"
        class="search-tabs"
        show-arrows
      >
        <v-tab value="all">
          <v-icon size="18" class="mr-1">mdi-all-inclusive</v-icon>
          {{ $t('globalSearch.all') }}
        </v-tab>
        <v-tab
          v-for="category in categories"
          :key="category.id"
          :value="category.id"
        >
          <v-icon size="18" class="mr-1">{{ category.icon }}</v-icon>
          {{ category.label }}
          <v-chip
            v-if="resultCounts[category.id]"
            size="x-small"
            class="ml-2"
          >
            {{ resultCounts[category.id] }}
          </v-chip>
        </v-tab>
      </v-tabs>

      <!-- Search Results -->
      <v-card-text class="search-results pa-0">
        <div v-if="isSearching" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <div v-else-if="searchQuery && !hasResults" class="no-results">
          <v-icon size="48" color="grey">mdi-magnify-remove-outline</v-icon>
          <p class="text-h6 mt-2">{{ $t('globalSearch.noResults') }}</p>
          <p class="text-body-2 text-grey">{{ $t('globalSearch.tryDifferentSearch') }}</p>
        </div>

        <v-list v-else class="search-results-list" density="compact">
          <!--
            Zuletzt benutzt: die zuletzt geoeffneten Orte und Datensaetze, jeder
            mit seinem Bereich rechts. Steht ueber den letzten Suchbegriffen,
            weil ein geoeffneter Datensatz einen zurueck an die Arbeit bringt
            und ein Suchbegriff nur zurueck ins Suchfeld.
          -->
          <template v-if="!searchQuery && recentItems.length">
            <v-list-subheader class="k-pal-group">{{ $t('globalSearch.recentlyUsed') }}</v-list-subheader>
            <v-list-item
              v-for="(item, index) in recentItems"
              :key="`recent-item-${item.type}-${item.id}`"
              class="search-result-item"
              @click="openRecentItem(item)"
            >
              <template v-slot:prepend>
                <v-avatar size="26" :color="getCategoryColor(item.type) || 'grey'">
                  <v-icon size="16" color="white">{{ item.icon || 'mdi-file-outline' }}</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
              <template v-slot:append>
                <span v-if="item.where" class="k-pal-where">{{ item.where }}</span>
                <v-btn
                  icon
                  size="x-small"
                  variant="text"
                  :aria-label="$t('globalSearch.removeRecent')"
                  @click.stop="removeRecentItem(index)"
                >
                  <v-icon size="14">mdi-close</v-icon>
                </v-btn>
              </template>
            </v-list-item>
            <v-divider class="my-2" />
          </template>

          <!-- Recent Searches -->
          <template v-if="!searchQuery && recentSearches.length">
            <v-list-subheader class="k-pal-group">{{ $t('globalSearch.recent') }}</v-list-subheader>
            <v-list-item
              v-for="(recent, index) in recentSearches"
              :key="`recent-${index}`"
              @click="selectRecentSearch(recent)"
              class="search-result-item"
            >
              <template v-slot:prepend>
                <v-icon size="20">mdi-history</v-icon>
              </template>
              <v-list-item-title>{{ recent.query }}</v-list-item-title>
              <template v-slot:append>
                <v-btn
                  icon
                  size="small"
                  variant="text"
                  @click.stop="removeRecentSearch(index)"
                >
                  <v-icon size="16">mdi-close</v-icon>
                </v-btn>
              </template>
            </v-list-item>
            <v-divider class="my-2" />
          </template>

          <!-- Search Results by Category -->
          <template v-for="(category, categoryIndex) in visibleCategories" :key="category.id">
            <template v-if="getCategoryResults(category.id).length > 0">
              <!-- Special handling for documents - group by area -->
              <template v-if="category.id === 'document'">
                <v-list-subheader v-if="selectedTab === 'all' || visibleCategories.length > 1">
                  <v-icon size="18" class="mr-2">{{ category.icon }}</v-icon>
                  {{ category.label }}
                </v-list-subheader>

                <!-- Group documents by area -->
                <template v-for="(areaGroup, areaName) in getDocumentsByArea()" :key="`doc-area-${areaName}`">
                  <div class="document-area-group ml-2">
                    <v-list-subheader class="area-subheader py-1">
                      <v-icon size="14" class="mr-1">mdi-folder-outline</v-icon>
                      <span class="text-caption">{{ areaName }}</span>
                      <v-chip size="x-small" class="ml-2">{{ areaGroup.length }}</v-chip>
                    </v-list-subheader>

                    <v-list-item
                      v-for="(result, resultIndex) in areaGroup.slice(0, expandedCategories.has(`document-${areaName}`) ? undefined : 5)"
                      :key="`${category.id}-${result.id}`"
                      @click="selectResult(result)"
                      class="search-result-item"
                    >
                      <template v-slot:prepend>
                        <v-avatar size="32" :color="category.color || 'grey'">
                          <v-icon size="20" color="white">{{ result.icon || category.itemIcon }}</v-icon>
                        </v-avatar>
                      </template>

                      <v-list-item-title>
                        <span v-html="highlightMatch(result.title)"></span>
                        <v-chip
                          v-if="result.badge"
                          size="x-small"
                          :color="result.badgeColor"
                          class="ml-2"
                        >
                          {{ result.badge }}
                        </v-chip>
                      </v-list-item-title>

                      <v-list-item-subtitle v-if="result.subtitle">
                        <span v-html="highlightMatch(result.subtitle)"></span>
                      </v-list-item-subtitle>

                      <template v-slot:append>
                        <div class="d-flex align-center">
                          <v-chip
                            v-if="result.primaryRelation"
                            size="small"
                            variant="tonal"
                            class="mr-2"
                          >
                            <v-icon size="14" start>{{ result.primaryRelation.icon }}</v-icon>
                            {{ result.primaryRelation.label }}
                          </v-chip>
                          <v-icon size="18">mdi-chevron-right</v-icon>
                        </div>
                      </template>
                    </v-list-item>

                    <!-- Show More button for this area -->
                    <v-list-item
                      v-if="areaGroup.length > 5 && !expandedCategories.has(`document-${areaName}`)"
                      @click="expandedCategories.add(`document-${areaName}`)"
                      class="show-more-item text-center"
                    >
                      <v-list-item-title class="text-primary text-caption">
                        <v-icon size="14" class="mr-1">mdi-chevron-down</v-icon>
                        {{ $t('globalSearch.showMore') }} (+{{ areaGroup.length - 5 }})
                      </v-list-item-title>
                    </v-list-item>
                  </div>
                </template>
              </template>

              <!-- Regular display for other categories -->
              <template v-else>
                <v-list-subheader v-if="selectedTab === 'all' || visibleCategories.length > 1">
                  <v-icon size="18" class="mr-2">{{ category.icon }}</v-icon>
                  {{ category.label }}
                  <v-spacer />
                  <v-btn
                    v-if="hasMoreResults(category.id)"
                    variant="text"
                    size="small"
                    @click="showMore(category.id)"
                  >
                    {{ $t('globalSearch.showMore') }}
                  </v-btn>
                </v-list-subheader>

                <v-list-item
                v-for="(result, resultIndex) in getCategoryResults(category.id)"
                :key="`${category.id}-${result.id}`"
                @click="selectResult(result)"
                :class="{
                  'search-result-item': true,
                  'active': isActiveResult(categoryIndex, resultIndex)
                }"
              >
                <template v-slot:prepend>
                  <v-avatar size="32" :color="category.color || 'grey'">
                    <v-icon size="20" color="white">{{ result.icon || category.itemIcon }}</v-icon>
                  </v-avatar>
                </template>

                <v-list-item-title>
                  <span v-html="highlightMatch(result.title)"></span>
                  <v-chip
                    v-if="result.badge"
                    size="x-small"
                    :color="result.badgeColor"
                    class="ml-2"
                  >
                    {{ result.badge }}
                  </v-chip>
                </v-list-item-title>

                <v-list-item-subtitle v-if="result.subtitle">
                  <span v-html="highlightMatch(result.subtitle)"></span>
                </v-list-item-subtitle>

                <template v-slot:append>
                  <div class="d-flex align-center">
                    <v-chip
                      v-if="result.primaryRelation"
                      size="small"
                      variant="tonal"
                      class="mr-2"
                    >
                      <v-icon size="14" start>{{ result.primaryRelation.icon }}</v-icon>
                      {{ result.primaryRelation.label }}
                    </v-chip>
                    <v-icon size="18">mdi-chevron-right</v-icon>
                  </div>
                </template>
              </v-list-item>

              <v-divider v-if="categoryIndex < visibleCategories.length - 1" class="my-2" />
              </template>
            </template>
          </template>

          <!-- Quick Actions -->
          <template v-if="searchQuery && quickActions.length > 0">
            <v-list-subheader>{{ $t('globalSearch.quickActions') }}</v-list-subheader>
            <v-list-item
              v-for="action in quickActions"
              :key="action.id"
              @click="executeQuickAction(action)"
              class="quick-action-item"
            >
              <template v-slot:prepend>
                <v-icon :color="action.color">{{ action.icon }}</v-icon>
              </template>
              <v-list-item-title>{{ action.label }}</v-list-item-title>
              <template v-slot:append>
                <kbd class="action-shortcut">{{ action.shortcut }}</kbd>
              </template>
            </v-list-item>
          </template>
        </v-list>
      </v-card-text>

      <!-- Footer -->
      <v-divider />
      <v-card-actions class="search-footer">
        <div class="text-caption text-grey">
          <kbd>↑↓</kbd> {{ $t('globalSearch.navigate') }}
          <kbd>Enter</kbd> {{ $t('globalSearch.select') }}
          <kbd>Tab</kbd> {{ $t('globalSearch.switchCategory') }}
        </div>
        <v-spacer />
        <v-btn
          variant="text"
          size="small"
          @click="openSearchHelp"
        >
          <v-icon start size="16">mdi-help-circle-outline</v-icon>
          {{ $t('globalSearch.help') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { apiClientAuth } from '@/api';
import { debounce } from 'lodash-es';
import { useMenuItems } from '@/composables/useMenuItems';
import type { MenuItem } from '@/types/Menu';

interface SearchResult {
  id: string | number;
  type: string;
  title: string;
  subtitle?: string;
  icon?: string;
  badge?: string;
  badgeColor?: string;
  route?: string;
  primaryRelation?: {
    type: string;
    id: string | number;
    label: string;
    icon: string;
  };
  metadata?: Record<string, any>;
}

interface SearchCategory {
  id: string;
  label: string;
  icon: string;
  itemIcon: string;
  color?: string;
  searchPrefix?: string;
  searchSymbol?: string;
}

interface QuickAction {
  id: string;
  label: string;
  icon: string;
  color?: string;
  shortcut: string;
  action: () => void;
}

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const { menuItems } = useMenuItems();

// Props
const props = defineProps<{
  modelValue: boolean;
}>();

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

// State
const searchInput = ref<any>(null);
const searchQuery = ref('');
const selectedTab = ref('all');
const isSearching = ref(false);
const searchResults = ref<Record<string, SearchResult[]>>({});
const expandedCategories = ref<Set<string>>(new Set());
const activeResultIndex = ref(0);
const recentSearches = ref<{ query: string; category?: string }[]>([]);

/**
 * Zuletzt benutzt.
 *
 * Der Entwurf zeigt in der Palette Orte und Inhalte zugleich, jeden mit
 * seinem Bereich rechts: "So lernt man nebenbei, wo die Dinge liegen, statt
 * nur hinzuspringen." Genau das leistet diese Gruppe beim Oeffnen mit leerem
 * Feld - sie zeigt, was man zuletzt geoeffnet hat, samt Bereich.
 *
 * Bewusst nicht dasselbe wie die alten "letzten Suchen": ein gespeicherter
 * Suchbegriff bringt einen zurueck ins Suchfeld, ein geoeffneter Datensatz
 * zurueck an die Arbeit.
 */
interface RecentItem {
  id: string | number;
  type: string;
  title: string;
  /** Bereich oder Zusammenhang - steht rechts, wie im Entwurf. */
  where?: string;
  icon?: string;
  route?: string;
  at: number;
}

const recentItems = ref<RecentItem[]>([]);
const RECENT_ITEMS_KEY = 'k-recent-items';
const RECENT_ITEMS_MAX = 8;

// Categories configuration
const categories = ref<SearchCategory[]>([
  {
    // Ansichten werden lokal aus der Navigation gefuellt, nicht ueber die API.
    // Bei 63 Ansichten ist der Weg dorthin haeufig das eigentliche Ziel der
    // Suche - vorher fand die Palette nur Inhalte.
    id: 'view',
    label: t('globalSearch.categories.views'),
    icon: 'mdi-compass-outline',
    itemIcon: 'mdi-arrow-right-thin',
    color: 'primary',
    searchPrefix: 'view:',
    searchSymbol: '>'
  },
  {
    id: 'person',
    label: t('globalSearch.categories.persons'),
    icon: 'mdi-account-group',
    itemIcon: 'mdi-account',
    color: 'blue',
    searchPrefix: 'person:',
    searchSymbol: '@'
  },
  {
    id: 'company',
    label: t('globalSearch.categories.companies'),
    icon: 'mdi-domain',
    itemIcon: 'mdi-office-building',
    color: 'green',
    searchPrefix: 'company:',
    searchSymbol: '!'
  },
  {
    id: 'report',
    label: t('globalSearch.categories.reports'),
    icon: 'mdi-file-document-outline',
    itemIcon: 'mdi-file-document',
    color: 'orange',
    searchPrefix: 'report:',
    searchSymbol: '#'
  },
  {
    id: 'document',
    label: t('globalSearch.categories.documents'),
    icon: 'mdi-folder-outline',
    itemIcon: 'mdi-file',
    color: 'purple',
    searchPrefix: 'doc:'
  },
  {
    id: 'vehicle',
    label: t('globalSearch.categories.vehicles'),
    icon: 'mdi-car',
    itemIcon: 'mdi-car-side',
    color: 'red',
    searchPrefix: 'vehicle:'
  },
  {
    id: 'employee',
    label: t('globalSearch.categories.employees'),
    icon: 'mdi-badge-account',
    itemIcon: 'mdi-account-tie',
    color: 'teal',
    searchPrefix: 'employee:'
  },
  {
    id: 'calendar',
    label: t('globalSearch.categories.calendar'),
    icon: 'mdi-calendar',
    itemIcon: 'mdi-calendar-clock',
    color: 'indigo',
    searchPrefix: 'calendar:'
  },
  {
    id: 'todo',
    label: t('globalSearch.categories.todos'),
    icon: 'mdi-checkbox-marked-outline',
    itemIcon: 'mdi-checkbox-marked',
    color: 'cyan',
    searchPrefix: 'todo:'
  },
  {
    id: 'blackboard',
    label: t('globalSearch.categories.blackboard'),
    icon: 'mdi-bulletin-board',
    itemIcon: 'mdi-pin',
    color: 'amber',
    searchPrefix: 'blackboard:'
  },
  {
    id: 'invoice',
    label: t('globalSearch.categories.invoices'),
    icon: 'mdi-receipt',
    itemIcon: 'mdi-currency-eur',
    color: 'light-green',
    searchPrefix: 'invoice:'
  },
  {
    id: 'message',
    label: t('globalSearch.categories.messages'),
    icon: 'mdi-message-text',
    itemIcon: 'mdi-message',
    color: 'pink',
    searchPrefix: 'message:'
  },
  {
    id: 'application',
    label: t('globalSearch.categories.applications'),
    icon: 'mdi-account-plus',
    itemIcon: 'mdi-file-account',
    color: 'lime',
    searchPrefix: 'application:'
  },
  {
    id: 'note',
    label: t('globalSearch.categories.notes'),
    icon: 'mdi-note-text',
    itemIcon: 'mdi-note',
    color: 'yellow',
    searchPrefix: 'note:'
  },
  {
    id: 'map',
    label: t('globalSearch.categories.locations'),
    icon: 'mdi-map',
    itemIcon: 'mdi-map-marker',
    color: 'brown',
    searchPrefix: 'map:'
  },
  {
    id: 'dispatch',
    label: t('globalSearch.categories.dispatch'),
    icon: 'mdi-truck-fast',
    itemIcon: 'mdi-radio-tower',
    color: 'red',
    searchPrefix: 'dispatch:'
  }
]);

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

const activeCategory = computed(() => {
  const query = searchQuery.value.toLowerCase();
  for (const category of categories.value) {
    if (category.searchPrefix && query.startsWith(category.searchPrefix)) {
      return category.id;
    }
    if (category.searchSymbol && query.startsWith(category.searchSymbol)) {
      return category.id;
    }
  }
  return null;
});

const cleanSearchQuery = computed(() => {
  let query = searchQuery.value;
  const category = categories.value.find(c => c.id === activeCategory.value);
  if (category) {
    if (category.searchPrefix && query.startsWith(category.searchPrefix)) {
      query = query.substring(category.searchPrefix.length);
    } else if (category.searchSymbol && query.startsWith(category.searchSymbol)) {
      query = query.substring(category.searchSymbol.length);
    }
  }
  return query.trim();
});

const visibleCategories = computed(() => {
  if (selectedTab.value === 'all') {
    return categories.value.filter(cat => 
      searchResults.value[cat.id]?.length > 0
    );
  }
  return categories.value.filter(cat => cat.id === selectedTab.value);
});

const hasResults = computed(() => {
  return Object.values(searchResults.value).some(results => results.length > 0);
});

const resultCounts = computed(() => {
  const counts: Record<string, number> = {};
  for (const [category, results] of Object.entries(searchResults.value)) {
    counts[category] = results.length;
  }
  return counts;
});

const quickActions = computed((): QuickAction[] => {
  if (!cleanSearchQuery.value) return [];
  
  const actions: QuickAction[] = [];
  const query = cleanSearchQuery.value.toLowerCase();
  
  // Create new person
  if (activeCategory.value === 'person' || query.includes(' ')) {
    actions.push({
      id: 'create-person',
      label: t('globalSearch.createPerson', { name: cleanSearchQuery.value }),
      icon: 'mdi-account-plus',
      color: 'primary',
      shortcut: 'Ctrl+N',
      action: () => createNewPerson()
    });
  }
  
  // Create new report
  if (activeCategory.value === 'report' || /\d/.test(query)) {
    actions.push({
      id: 'create-report',
      label: t('globalSearch.createReport'),
      icon: 'mdi-file-document-plus',
      color: 'orange',
      shortcut: 'Ctrl+R',
      action: () => createNewReport()
    });
  }
  
  return actions;
});

// Methods
const getCategoryLabel = (categoryId: string) => {
  return categories.value.find(c => c.id === categoryId)?.label || categoryId;
};

/** Die Bereichsfarbe eines Treffers - dieselbe wie in der Trefferliste, damit
    ein Eintrag unter "Zuletzt benutzt" nicht anders aussieht als derselbe
    Eintrag im Suchergebnis. */
const getCategoryColor = (categoryId: string) => {
  return categories.value.find(c => c.id === categoryId)?.color || 'grey';
};

const getCategoryResults = (categoryId: string) => {
  const results = searchResults.value[categoryId] || [];
  const isExpanded = expandedCategories.value.has(categoryId);
  return isExpanded ? results : results.slice(0, 5);
};

const hasMoreResults = (categoryId: string) => {
  const results = searchResults.value[categoryId] || [];
  return results.length > 5 && !expandedCategories.value.has(categoryId);
};

const showMore = (categoryId: string) => {
  expandedCategories.value.add(categoryId);
};

const getDocumentsByArea = () => {
  const documents = searchResults.value['document'] || [];
  const grouped: Record<string, any[]> = {};

  documents.forEach((doc: any) => {
    const areaName = doc.metadata?.area_name || doc.metadata?.area || 'Sonstige';
    if (!grouped[areaName]) {
      grouped[areaName] = [];
    }
    grouped[areaName].push(doc);
  });

  return grouped;
};

const highlightMatch = (text: string) => {
  if (!cleanSearchQuery.value) return text;
  
  const regex = new RegExp(`(${cleanSearchQuery.value})`, 'gi');
  return text.replace(regex, '<mark>$1</mark>');
};

const isActiveResult = (categoryIndex: number, resultIndex: number) => {
  let currentIndex = 0;
  for (let i = 0; i < categoryIndex; i++) {
    const category = visibleCategories.value[i];
    currentIndex += getCategoryResults(category.id).length;
  }
  currentIndex += resultIndex;
  return currentIndex === activeResultIndex.value;
};

const navigateUp = () => {
  const totalResults = visibleCategories.value.reduce((sum, cat) => 
    sum + getCategoryResults(cat.id).length, 0
  );
  if (totalResults === 0) return;
  
  activeResultIndex.value = (activeResultIndex.value - 1 + totalResults) % totalResults;
};

const navigateDown = () => {
  const totalResults = visibleCategories.value.reduce((sum, cat) => 
    sum + getCategoryResults(cat.id).length, 0
  );
  if (totalResults === 0) return;
  
  activeResultIndex.value = (activeResultIndex.value + 1) % totalResults;
};

const handleEnter = () => {
  let currentIndex = 0;
  for (const category of visibleCategories.value) {
    const results = getCategoryResults(category.id);
    if (activeResultIndex.value < currentIndex + results.length) {
      const result = results[activeResultIndex.value - currentIndex];
      selectResult(result);
      return;
    }
    currentIndex += results.length;
  }
};

const selectResult = (result: SearchResult) => {
  // Save to recent searches
  saveRecentSearch();
  // ... und den geoeffneten Treffer selbst, fuer die Gruppe "Zuletzt benutzt".
  rememberRecentItem(result);

  // Check if we're in desktop mode
  const isDesktopMode = document.documentElement.classList.contains('desktop-mode');

  if (isDesktopMode) {
    // Desktop mode: Open the appropriate app
    // Use the route from the result if available, otherwise fall back to default
    const appMap: Record<string, any> = {
      person: {
        id: 'person',
        title: t('tabs.person'),
        icon: 'mdi-account-group',
        route: result.route || `/person?id=${result.id}`,
        color: 'var(--k-accent)'
      },
      company: {
        id: 'company',
        title: t('tabs.company'),
        icon: 'mdi-domain',
        route: result.route || `/company?id=${result.id}`,
        color: '#10b981'
      },
      report: {
        id: 'report',
        title: t('tabs.report'),
        icon: 'mdi-file-document-outline',
        route: result.route || `/report?id=${result.id}`,
        color: '#f59e0b'
      },
      document: {
        id: 'documentglobal',
        title: t('tabs.document'),
        icon: 'mdi-folder-outline',
        route: result.route || `/document/global?id=${result.id}`,
        color: '#8b5cf6'
      },
      vehicle: {
        id: 'vehicleFile',
        title: t('tabs.vehicle'),
        icon: 'mdi-car',
        route: result.route || `/vehicleFile?id=${result.id}`,
        color: '#ef4444'
      },
      employee: {
        id: 'employee',
        title: t('tabs.employee'),
        icon: 'mdi-badge-account',
        route: result.route || `/employee?id=${result.id}`,
        color: '#06b6d4'
      },
      calendar: {
        id: 'calendar',
        title: t('tabs.calendar'),
        icon: 'mdi-calendar',
        route: result.route || `/calendar?id=${result.id}`,
        color: '#4f46e5'
      },
      todo: {
        id: 'todo',
        title: t('tabs.todo'),
        icon: 'mdi-checkbox-marked-outline',
        route: result.route || `/todo?id=${result.id}`,
        color: '#0891b2'
      },
      blackboard: {
        id: 'blackboard-global',
        title: t('tabs.blackboard'),
        icon: 'mdi-bulletin-board',
        route: result.route || `/blackboard/global?id=${result.id}`,
        color: '#f59e0b'
      },
      invoice: {
        id: 'invoice',
        title: t('tabs.invoice'),
        icon: 'mdi-receipt',
        route: result.route || `/invoice?id=${result.id}`,
        color: '#84cc16'
      },
      message: {
        id: 'message',
        title: t('tabs.message'),
        icon: 'mdi-message-text',
        route: result.route || `/message?id=${result.id}`,
        color: '#ec4899'
      },
      application: {
        id: 'application',
        title: t('tabs.application'),
        icon: 'mdi-account-plus',
        route: result.route || `/application?id=${result.id}`,
        color: '#84cc16'
      },
      note: {
        id: 'dashboard',
        title: t('tabs.notes'),
        icon: 'mdi-note-text',
        route: result.route || `/note?id=${result.id}`,
        color: '#eab308'
      },
      map: {
        id: 'map',
        title: t('tabs.map'),
        icon: 'mdi-map',
        route: result.route || `/map?id=${result.id}`,
        color: '#8b5cf6'
      },
      dispatch: {
        id: 'dispatch',
        title: t('tabs.dispatch'),
        icon: 'mdi-truck-fast',
        route: result.route || `/dispatch?id=${result.id}`,
        color: '#ef4444'
      }
    };
    
    const app = appMap[result.type];
    if (app) {
      // Dispatch event to open desktop app
      window.dispatchEvent(new CustomEvent('open-desktop-app', {
        detail: app
      }));
    }
  } else {
    // Regular mode: Navigate normally
    if (result.route) {
      router.push(result.route);
    } else {
      // Build route based on type and id
      const routeMap: Record<string, string> = {
        person: `/person?id=${result.id}`,
        company: `/company?id=${result.id}`,
        report: `/report?id=${result.id}`,
        document: `/document/global?id=${result.id}`,
        vehicle: `/vehicleFile?id=${result.id}`,
        employee: `/employee?id=${result.id}`,
        calendar: `/calendar?id=${result.id}`,
        todo: `/todo?id=${result.id}`,
        blackboard: `/blackboard/global?id=${result.id}`,
        invoice: `/invoice?id=${result.id}`,
        message: `/message?id=${result.id}`,
        application: `/application?id=${result.id}`,
        note: `/note?id=${result.id}`,
        map: `/map?id=${result.id}`,
        dispatch: `/dispatch?id=${result.id}`
      };
      
      const route = routeMap[result.type];
      if (route) {
        router.push(route);
      }
    }
  }
  
  close();
};

const selectRecentSearch = (recent: { query: string; category?: string }) => {
  searchQuery.value = recent.query;
  if (recent.category) {
    selectedTab.value = recent.category;
  }
};

const removeRecentSearch = (index: number) => {
  recentSearches.value.splice(index, 1);
  saveRecentSearchesToStorage();
};

const clearCategory = () => {
  const category = categories.value.find(c => c.id === activeCategory.value);
  if (category) {
    if (category.searchPrefix && searchQuery.value.startsWith(category.searchPrefix)) {
      searchQuery.value = searchQuery.value.substring(category.searchPrefix.length);
    } else if (category.searchSymbol && searchQuery.value.startsWith(category.searchSymbol)) {
      searchQuery.value = searchQuery.value.substring(category.searchSymbol.length);
    }
  }
};

const executeQuickAction = (action: QuickAction) => {
  action.action();
  close();
};

const createNewPerson = () => {
  const isDesktopMode = document.documentElement.classList.contains('desktop-mode');
  
  if (isDesktopMode) {
    // Desktop mode: Open person app in create mode
    window.dispatchEvent(new CustomEvent('open-desktop-app', {
      detail: {
        id: 'person',
        title: t('tabs.person'),
        icon: 'mdi-account-group',
        route: `/person/create?prefill=${encodeURIComponent(cleanSearchQuery.value)}`,
        color: 'var(--k-accent)'
      }
    }));
  } else {
    // Regular mode
    router.push({ 
      name: 'person-create', 
      query: { prefill: cleanSearchQuery.value }
    });
  }
};

const createNewReport = () => {
  const isDesktopMode = document.documentElement.classList.contains('desktop-mode');
  
  if (isDesktopMode) {
    // Desktop mode: Open report app in create mode
    window.dispatchEvent(new CustomEvent('open-desktop-app', {
      detail: {
        id: 'report',
        title: t('tabs.report'),
        icon: 'mdi-file-document-outline',
        route: `/report/create?title=${encodeURIComponent(cleanSearchQuery.value)}`,
        color: '#f59e0b'
      }
    }));
  } else {
    // Regular mode
    router.push({ 
      name: 'report-create',
      query: { title: cleanSearchQuery.value }
    });
  }
};

const openSearchHelp = () => {
  // TODO: Open help dialog
  console.log('Search help');
};

const close = () => {
  isOpen.value = false;
  searchQuery.value = '';
  selectedTab.value = 'all';
  expandedCategories.value.clear();
  activeResultIndex.value = 0;
};

const saveRecentSearch = () => {
  if (!cleanSearchQuery.value) return;
  
  const recent = {
    query: searchQuery.value,
    category: activeCategory.value || selectedTab.value !== 'all' ? selectedTab.value : undefined
  };
  
  // Remove duplicates
  recentSearches.value = recentSearches.value.filter(r => 
    r.query !== recent.query
  );
  
  // Add to beginning
  recentSearches.value.unshift(recent);
  
  // Keep only last 10
  recentSearches.value = recentSearches.value.slice(0, 10);
  
  saveRecentSearchesToStorage();
};

const saveRecentSearchesToStorage = () => {
  localStorage.setItem('recentSearches', JSON.stringify(recentSearches.value));
};

/** Merkt sich einen geoeffneten Treffer. Derselbe Eintrag rutscht nach oben,
    statt sich zu verdoppeln. */
const rememberRecentItem = (result: SearchResult) => {
  const entry: RecentItem = {
    id: result.id,
    type: result.type,
    title: result.title,
    where: result.subtitle || getCategoryLabel(result.type),
    icon: result.icon,
    route: result.route,
    at: Date.now(),
  };

  recentItems.value = [
    entry,
    ...recentItems.value.filter(r => !(r.type === entry.type && String(r.id) === String(entry.id))),
  ].slice(0, RECENT_ITEMS_MAX);

  try {
    localStorage.setItem(RECENT_ITEMS_KEY, JSON.stringify(recentItems.value));
  } catch {
    /* Speicher gesperrt - die Liste gilt dann nur fuer diese Sitzung. */
  }
};

const loadRecentItems = () => {
  try {
    const saved = localStorage.getItem(RECENT_ITEMS_KEY);
    const parsed = saved ? JSON.parse(saved) : [];
    recentItems.value = Array.isArray(parsed) ? parsed : [];
  } catch {
    recentItems.value = [];
  }
};

const removeRecentItem = (index: number) => {
  recentItems.value.splice(index, 1);
  try {
    localStorage.setItem(RECENT_ITEMS_KEY, JSON.stringify(recentItems.value));
  } catch {
    /* siehe oben */
  }
};

/** Ein Eintrag aus "Zuletzt benutzt" geht denselben Weg wie ein Suchtreffer. */
const openRecentItem = (item: RecentItem) => {
  selectResult({
    id: item.id,
    type: item.type,
    title: item.title,
    subtitle: item.where,
    icon: item.icon,
    route: item.route,
  } as SearchResult);
};

const loadRecentSearches = () => {
  try {
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      recentSearches.value = JSON.parse(saved);
    }
  } catch (e) {
    console.error('Failed to load recent searches:', e);
  }
};

// Search function
const performSearch = debounce(async () => {
  if (!cleanSearchQuery.value && !activeCategory.value) {
    searchResults.value = {};
    return;
  }
  
  isSearching.value = true;
  activeResultIndex.value = 0;
  
  try {
    const response = await apiClientAuth.post('/search/', {
      query: cleanSearchQuery.value,
      category: activeCategory.value || (selectedTab.value !== 'all' ? selectedTab.value : null),
      fuzzy: true,
      limit: 10,
      authority_id: authStore.user?.authority_id
    });
    
    if (response.data.success) {
      searchResults.value = response.data.results || {};
    }
  } catch (error) {
    console.error('Search error:', error);
    searchResults.value = {};
  } finally {
    // Ansichten stammen aus der Navigation im Speicher und brauchen keine
    // Abfrage. Sie stehen bewusst vorn: wer "fahr" tippt, sucht meist die
    // Ansicht Fahrzeuge und nicht ein einzelnes Fahrzeug.
    const ansichten = sucheAnsichten(cleanSearchQuery.value);
    if (ansichten.length) {
      searchResults.value = { view: ansichten, ...searchResults.value };
    }
    isSearching.value = false;
  }
}, 300);

/**
 * Durchsucht die Navigation nach Ansichten. Der Bereich (die uebergeordnete
 * Gruppe) wird als Untertitel mitgegeben, damit ein Treffer einordbar ist -
 * "Fahrzeuge" allein sagt nicht, ob die Akte oder die Leitstelle gemeint ist.
 */
function sucheAnsichten(query: string): SearchResult[] {
  const q = query.trim().toLowerCase();
  if (!q || q.length < 2) return [];

  const gefunden: SearchResult[] = [];

  const durchlaufe = (items: MenuItem[], bereich = '') => {
    for (const item of items) {
      if (item.route && item.title?.toLowerCase().includes(q)) {
        gefunden.push({
          id: `view:${item.route}`,
          type: 'view',
          title: item.title,
          subtitle: bereich || undefined,
          icon: typeof item.icon === 'string' && item.icon.startsWith('mdi-') ? item.icon : 'mdi-arrow-right-thin',
          route: item.route,
        });
      }
      if (item.children?.length) {
        durchlaufe(item.children, item.route ? bereich : item.title);
      }
    }
  };

  durchlaufe(menuItems.value ?? []);
  return gefunden.slice(0, 8);
}

// Watchers
watch(searchQuery, () => {
  performSearch();
});

watch(selectedTab, () => {
  performSearch();
});

watch(isOpen, async (newValue) => {
  if (newValue) {
    await nextTick();
    searchInput.value?.focus();
  }
});

// Lifecycle
onMounted(() => {
  loadRecentSearches();
  loadRecentItems();
});

// Expose for parent component
defineExpose({
  open: () => { isOpen.value = true; },
  close
});
</script>

<style scoped lang="scss">
.global-search-container {
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 80vh;
  background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
  backdrop-filter: blur(30px) saturate(180%);
  -webkit-backdrop-filter: blur(30px) saturate(180%);
  border: 1px solid var(--k-line);
  border-radius: 24px;
  box-shadow:
    0 24px 60px rgba(0, 0, 0, 0.4),
    0 12px 32px rgba(0, 0, 0, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.search-header {
  padding: 20px;
  background: linear-gradient(135deg, rgba(51, 65, 85, 0.4), rgba(71, 85, 105, 0.3));
  border-bottom: 1px solid var(--k-line);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.search-input {
  :deep(.v-field) {
    font-size: 18px;
    background: var(--k-surface) !important;
    border-radius: 16px !important;
    border: 1px solid var(--k-line);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
      border-color: var(--k-line);
      background: var(--k-surface) !important;
    }

    &:focus-within {
      border-color: var(--k-accent-line);
      background: var(--k-surface) !important;
      box-shadow:
        0 0 0 3px var(--k-accent-weak),
        0 4px 12px rgba(0, 0, 0, 0.2);
    }
  }

  :deep(.v-field__input) {
    color: var(--k-ink);
    font-weight: 500;
    padding: 12px 16px;
  }

  :deep(.v-field__prepend-inner) {
    padding-left: 16px;
  }

  :deep(.v-icon) {
    color: var(--k-ink-muted);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  }
}

.search-shortcut {
  padding: 4px 10px;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.7));
  border: 1px solid var(--k-line);
  border-radius: 6px;
  font-size: 11px;
  font-family: monospace;
  font-weight: 600;
  color: var(--k-ink);
  box-shadow:
    0 2px 6px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.category-chip {
  margin-right: 8px;
  background: linear-gradient(135deg, var(--k-accent-weak), var(--k-accent-weak)) !important;
  border: 1px solid var(--k-accent-line);
  font-weight: 600;
  color: rgba(147, 197, 253, 0.95);
}

.search-tabs {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(51, 65, 85, 0.5));
  border-bottom: 1px solid var(--k-line);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  min-height: 48px;
  flex-shrink: 0;

  :deep(.v-tab) {
    color: var(--k-ink-muted);
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0.3px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    margin: 4px;
    min-height: 40px;
    font-size: 0.875rem;

    &:hover {
      color: var(--k-ink);
      background: var(--k-row-hover);
    }

    &.v-tab--selected {
      color: var(--k-ink);
      background: linear-gradient(135deg, var(--k-accent-line), var(--k-accent-weak));
      border: 1px solid var(--k-accent-line);
      box-shadow: 0 4px 12px var(--k-accent-weak);
    }

    .v-chip {
      background: var(--k-row-hover);
      color: var(--k-ink);
      font-weight: 700;
      font-size: 10px;
    }
  }

  :deep(.v-icon) {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  }
}

.search-results {
  flex: 1;
  overflow-y: auto;
  background: var(--k-surface);
  padding: 8px;
}

.search-results::-webkit-scrollbar {
  width: 8px;
}

.search-results::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  margin: 8px 0;
}

.search-results::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.15));
  border-radius: 8px;
  border: 2px solid transparent;
  background-clip: padding-box;

  &:hover {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.25));
  }
}

.search-results-list {
  background: transparent;

  :deep(.v-list-subheader) {
    color: var(--k-ink-muted);
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 12px 16px 8px;
    background: transparent;
  }
}

.search-result-item {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 12px;
  margin: 4px 8px;
  padding: 12px !important;
  background: var(--k-sunken);
  border: 1px solid var(--k-line);

  &:hover {
    background: linear-gradient(135deg, var(--k-accent-weak), var(--k-accent-weak));
    border-color: var(--k-accent-line);
    transform: translateX(4px);
    box-shadow:
      0 4px 12px rgba(0, 0, 0, 0.2),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }

  &.active {
    background: linear-gradient(135deg, var(--k-accent-line), var(--k-accent-weak));
    border-color: var(--k-accent-line);
    box-shadow:
      0 6px 16px var(--k-accent-line),
      inset 0 1px 0 rgba(255, 255, 255, 0.15);
  }

  :deep(.v-list-item__content) {
    overflow: visible;
  }

  :deep(.v-list-item-title) {
    color: var(--k-ink);
    font-weight: 600;
    font-size: 14px;
  }

  :deep(.v-list-item-subtitle) {
    color: var(--k-ink-muted);
    font-size: 12px;
  }

  :deep(.v-avatar) {
    box-shadow:
      0 4px 12px rgba(0, 0, 0, 0.2),
      inset 0 1px 0 rgba(255, 255, 255, 0.2);
  }

  :deep(.v-chip) {
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  }
}

.document-area-group {
  margin-bottom: 12px;

  /*
    Gruppenueberschrift wie im Entwurf: klein, gesperrt, gedimmt. Vorher trug
    sie einen 3-px-Balken in einem Violett, das in der Palette nicht vorkommt,
    und einen fest verdrahteten dunklen Grund - im hellen Modus ein dunkler
    Streifen auf heller Flaeche. Eine Ueberschrift ordnet; sie braucht dafuer
    weder Balken noch eigene Flaeche.
  */
  .area-subheader {
    font-size: 10px;
    color: var(--k-ink-faint);
    font-weight: 650;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    padding: 10px 16px 4px;
    background: transparent;
  }
}

/* Gruppen der Palette: 10 px, gesperrt, gedimmt - genau wie .pal-group im
   Entwurf. */
.k-pal-group {
  font-size: 10px !important;
  font-weight: 650;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--k-ink-faint) !important;
  min-height: 24px;
}

/*
  Der Bereich rechts neben dem Treffer. Im Entwurf traegt jeder Eintrag ihn:
  "So lernt man nebenbei, wo die Dinge liegen, statt nur hinzuspringen."
*/
.k-pal-where {
  font-size: 11.5px;
  color: var(--k-ink-faint);
  padding-left: 12px;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.show-more-item {
  cursor: pointer;
  transition: all 0.2s ease;
  margin: 4px 8px;
  padding: 8px !important;
  background: transparent;
  border: 1px dashed var(--k-accent-line);
  border-radius: 8px;

  &:hover {
    background: var(--k-accent-weak);
    border-color: var(--k-accent-line);
  }

  :deep(.v-list-item-title) {
    font-weight: 600;
    font-size: 12px;
  }
}

.quick-action-item {
  cursor: pointer;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(6, 182, 212, 0.1));
  border-radius: 12px;
  margin: 4px 8px;
  padding: 12px !important;
  border: 1px solid rgba(14, 165, 233, 0.25);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  &:hover {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.25), rgba(6, 182, 212, 0.2));
    border-color: rgba(14, 165, 233, 0.4);
    transform: translateX(4px);
    box-shadow:
      0 6px 16px rgba(14, 165, 233, 0.25),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }

  :deep(.v-list-item-title) {
    color: rgba(125, 211, 252, 0.95);
    font-weight: 600;
  }

  :deep(.v-icon) {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  }
}

.no-results {
  text-align: center;
  padding: 64px 24px;
  color: var(--k-ink-faint);

  .v-icon {
    opacity: 0.4;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
  }

  .text-h6 {
    color: var(--k-ink-muted);
    font-weight: 600;
    margin-top: 16px;
  }

  .text-body-2 {
    color: var(--k-ink-faint);
    margin-top: 8px;
  }
}

.search-footer {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(51, 65, 85, 0.6));
  border-top: 1px solid var(--k-line);
  padding: 14px 20px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);

  .text-caption {
    color: var(--k-ink-muted);
    font-weight: 500;
    letter-spacing: 0.3px;
  }

  :deep(.v-btn) {
    color: var(--k-ink-muted);
    font-weight: 600;
    text-transform: none;
    border-radius: 10px;

    &:hover {
      background: var(--k-row-hover);
      color: var(--k-ink);
    }
  }
}

.action-shortcut {
  padding: 3px 8px;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.7));
  border: 1px solid var(--k-line);
  border-radius: 6px;
  font-size: 10px;
  font-family: monospace;
  font-weight: 600;
  color: var(--k-ink);
  margin-left: 8px;
  box-shadow:
    0 2px 6px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

mark {
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.35), rgba(245, 158, 11, 0.3));
  color: rgba(254, 243, 199, 0.95);
  padding: 2px 4px;
  border-radius: 4px;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}

kbd {
  display: inline-block;
  padding: 4px 8px;
  font-size: 11px;
  line-height: 1.2;
  color: var(--k-ink);
  vertical-align: middle;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(51, 65, 85, 0.8));
  border: 1px solid var(--k-line);
  border-radius: 6px;
  box-shadow:
    0 2px 6px rgba(0, 0, 0, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1),
    inset 0 -1px 0 rgba(0, 0, 0, 0.2);
  font-family: monospace;
  font-weight: 600;
  margin: 0 2px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}
</style>