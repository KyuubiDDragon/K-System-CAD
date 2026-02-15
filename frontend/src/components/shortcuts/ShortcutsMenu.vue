<template>
  <!-- Desktop: v-menu -->
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom end"
    min-width="400"
    max-width="500"
    max-height="600"
    offset="8"
  >
    <template #activator="{ props: menuProps }">
      <v-btn
        icon
        variant="text"
        v-bind="menuProps"
        :aria-label="$t('shortcuts.openShortcuts')"
      >
        <v-badge
          :content="shortcutsStore.count"
          :model-value="shortcutsStore.count > 0"
          color="primary"
          overlap
        >
          <v-icon>mdi-star</v-icon>
        </v-badge>
        <v-tooltip activator="parent" location="bottom">
          {{ $t('shortcuts.myShortcuts') }} ({{ shortcutsStore.count }})
        </v-tooltip>
      </v-btn>
    </template>

    <!-- Menu Content -->
    <v-card>
      <!-- Header -->
      <v-card-title class="d-flex align-center pa-4">
        <v-icon class="mr-2">mdi-star</v-icon>
        {{ $t('shortcuts.myShortcuts') }} ({{ shortcutsStore.count }})

        <v-spacer />

        <v-btn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="menuOpen = false"
        />
      </v-card-title>

      <v-divider />

      <!-- Search -->
      <v-card-text class="pa-3">
        <v-text-field
          v-model="searchQuery"
          :placeholder="$t('shortcuts.searchPlaceholder')"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          hide-details
          clearable
        />
      </v-card-text>

      <v-divider />

      <!-- Shortcuts List -->
      <v-card-text class="pa-0" style="max-height: 400px; overflow-y: auto;">
        <v-list v-if="filteredShortcuts.length > 0" density="compact">
          <shortcut-list-item
            v-for="shortcut in filteredShortcuts"
            :key="shortcut.id"
            :shortcut="shortcut"
            @navigate="handleNavigate"
            @remove="handleRemove"
          />
        </v-list>

        <!-- Empty State -->
        <div v-else class="pa-8 text-center text-medium-emphasis">
          <v-icon size="64" color="grey-lighten-1">mdi-star-outline</v-icon>
          <p class="mt-4">
            {{ searchQuery ? $t('shortcuts.noResults') : $t('shortcuts.noShortcuts') }}
          </p>
        </div>
      </v-card-text>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useShortcutsStore } from '@/stores/shortcuts';
import { useResourceNavigation } from '@/composables/useResourceNavigation';
import ShortcutListItem from './ShortcutListItem.vue';
import type { Shortcut } from '@/types/Shortcut';

// Stores & Composables
const { t } = useI18n();
const shortcutsStore = useShortcutsStore();
const { navigateToShortcut } = useResourceNavigation();

// State
const menuOpen = ref(false);
const searchQuery = ref('');

// Computed
const filteredShortcuts = computed(() => {
  if (!searchQuery.value) {
    return shortcutsStore.shortcuts;
  }

  const query = searchQuery.value.toLowerCase();
  return shortcutsStore.shortcuts.filter(
    (s) =>
      s.title.toLowerCase().includes(query) ||
      s.subtitle?.toLowerCase().includes(query)
  );
});

// Methods
const handleNavigate = async (shortcut: Shortcut) => {
  const result = await navigateToShortcut(shortcut);
  if (result.success) {
    menuOpen.value = false;
  }
};

const handleRemove = async (shortcut: Shortcut) => {
  try {
    await shortcutsStore.removeShortcut(shortcut.id);
  } catch (error) {
    console.error('Failed to remove shortcut:', error);
  }
};
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-on-surface), 0.3);
}
</style>
