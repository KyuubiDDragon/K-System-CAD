<template>
  <v-navigation-drawer
    v-model="localOpen"
    :rail="collapsed"
    :width="sidebarWidth"
    :rail-width="64"
    permanent
    class="sidebar-drawer elevation-2"
    color="surface"
  >
    <!-- Header Section -->
    <div class="sidebar-header pa-4" :class="{ 'justify-center': collapsed }">
      <div v-if="!collapsed" class="d-flex align-center">
        <v-img
          :src="authorityLogo"
          max-width="32"
          class="mr-3"
          @error="handleLogoError"
        />
        <span class="text-h6 font-weight-bold primary--text">
          {{ authorityName || 'K-Systems' }}
        </span>
      </div>
      <v-avatar v-else color="primary" size="32">
        <v-img
          v-if="authorityLogo && !logoError"
          :src="authorityLogo"
          @error="handleLogoError"
        />
        <v-icon v-else color="white">mdi-folder-multiple</v-icon>
      </v-avatar>
    </div>

    <v-divider />

    <!-- Search Field (nur wenn expanded) -->
    <div v-if="!collapsed" class="pa-3">
      <v-text-field
        v-model="searchQuery"
        density="compact"
        variant="outlined"
        prepend-inner-icon="mdi-magnify"
        :placeholder="$t('menu.search')"
        hide-details
        clearable
        class="sidebar-search"
      />
    </div>

    <!-- Menu Items -->
    <v-list
      density="compact"
      nav
      class="sidebar-menu pa-2"
    >
      <!-- Main Menu Items -->
      <sidebar-menu-item
        v-for="item in filteredMenuItems"
        :key="item.id"
        :item="item"
        :collapsed="collapsed"
        :active-route="activeRoute"
        :level="0"
        @navigate="handleNavigate"
      />
    </v-list>

    <!-- Footer Section - Collapse button -->
    <template #append>
      <div class="sidebar-footer pa-2">
        <v-btn
          :icon="collapsed ? 'mdi-chevron-right' : 'mdi-chevron-left'"
          variant="text"
          size="small"
          @click="$emit('toggle-collapse')"
        >
          <v-icon size="small">{{ collapsed ? 'mdi-chevron-right' : 'mdi-chevron-left' }}</v-icon>
          <v-tooltip
            v-if="collapsed"
            activator="parent"
            location="end"
          >
            {{ $t('menu.expand') }}
          </v-tooltip>
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useUIStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import SidebarMenuItem from './SidebarMenuItem.vue';
import type { MenuItem } from '@/types/Menu';

interface Props {
  menuItems: MenuItem[];
  collapsed: boolean;
  activeRoute: string;
  modelValue: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue', 'toggle-collapse', 'navigate']);

const router = useRouter();
const uiStore = useUIStore();
const authStore = useAuthStore();

// Local state
const searchQuery = ref('');
const sidebarWidth = 280;
const logoError = ref(false);

// Authority display name from authority_branding (preferred) or fallback to user fields
const authorityName = computed(() =>
  authStore.user?.authority_branding?.display_name ||
  authStore.user?.authority_display_name ||
  authStore.user?.authority ||
  ''
);

// Authority logo - try authority_branding first, then authority_logo, fallback to /logo.png
const authorityLogo = computed(() => {
  if (logoError.value) return '/logo.png';
  return authStore.user?.authority_branding?.logo_url ||
         authStore.user?.authority_logo ||
         '/logo.png';
});

// Handle logo load error
function handleLogoError() {
  logoError.value = true;
}

const localOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

/**
 * Filtered menu items based on search
 */
const filteredMenuItems = computed(() => {
  if (!searchQuery.value) return props.menuItems;

  const query = searchQuery.value.toLowerCase();
  return props.menuItems.filter(item =>
    filterMenuRecursive(item, query)
  );
});

/**
 * Recursive filter function
 */
function filterMenuRecursive(item: MenuItem, query: string): boolean {
  // Check item title
  if (item.title.toLowerCase().includes(query)) return true;

  // Check children
  if (item.children) {
    return item.children.some(child => filterMenuRecursive(child, query));
  }

  return false;
}

/**
 * Handle navigation
 */
function handleNavigate(route: string) {
  emit('navigate', route);

  // Add to recently visited - DISABLED: Feature not yet used in UI
  // const matchedItem = findItemByRoute(route);
  // if (matchedItem) {
  //   uiStore.addRecentlyVisited(route, matchedItem.title);
  // }
}

/**
 * Find menu item by route
 */
function findItemByRoute(route: string): MenuItem | null {
  function searchRecursive(items: MenuItem[]): MenuItem | null {
    for (const item of items) {
      if (item.route === route) return item;
      if (item.children) {
        const found = searchRecursive(item.children);
        if (found) return found;
      }
    }
    return null;
  }
  return searchRecursive(props.menuItems);
}

</script>

<style lang="scss" scoped>
.sidebar-drawer {
  border-right: 1px solid rgba(var(--v-border-color), 0.12);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  :deep(.v-navigation-drawer__content) {
    display: flex;
    flex-direction: column;
  }
}

.sidebar-header {
  display: flex;
  align-items: center;
  min-height: 64px;
  transition: all 0.3s ease;

  &.justify-center {
    justify-content: center;
  }
}

.sidebar-search {
  :deep(.v-field__input) {
    font-size: 0.875rem;
  }
}

.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;

  // Custom scrollbar
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background: rgba(var(--v-theme-on-surface), 0.2);
    border-radius: 3px;

    &:hover {
      background: rgba(var(--v-theme-on-surface), 0.3);
    }
  }
}

.sidebar-footer {
  background-color: rgba(var(--v-theme-surface), 0.5);
}
</style>
