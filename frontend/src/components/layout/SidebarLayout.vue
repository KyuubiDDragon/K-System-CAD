<template>
  <!-- Navigation Sidebar (links) -->
  <!-- Vuetify requires v-navigation-drawer and v-main to be direct children of v-app -->
  <!-- So we use Vue 3's Fragment (multi-root) instead of wrapping div -->
  <navigation-sidebar
    v-model="sidebarOpen"
    :collapsed="sidebarCollapsed"
    :menu-items="menuItems"
    :active-route="currentRoute"
    @toggle-collapse="toggleSidebarCollapse"
    @navigate="handleNavigation"
  />

  <!-- Main Content Area -->
  <v-main class="sidebar-layout-main">
      <!-- Top Bar (Header) -->
      <top-bar
        :sidebar-collapsed="sidebarCollapsed"
        :breadcrumbs="breadcrumbs"
        :user="currentUser"
        @toggle-sidebar="toggleSidebar"
        @toggle-layout="switchToDesktopMode"
        @open-search="openGlobalSearch"
        @logout="handleLogout"
      />

      <!-- Router View (aktuelle Seite) -->
      <v-container fluid class="content-container pa-6">
        <transition name="fade-slide" mode="out-in">
          <router-view />
        </transition>
      </v-container>
    </v-main>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';
import { useMenuItems } from '@/composables/useMenuItems';
import NavigationSidebar from './NavigationSidebar.vue';
import TopBar from './TopBar.vue';
import type { Breadcrumb } from '@/types/Menu';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUIStore();

// Menu items aus Composable
const {
  initializeMenu,
  buildHierarchicalMenu,
  menuItems: allMenuItems,
  loading: menuLoading
} = useMenuItems();

// Sidebar-State
const sidebarOpen = ref(true);
const sidebarCollapsed = ref(false);

// Current route path
const currentRoute = computed(() => route.path);

// Current user
const currentUser = computed(() => authStore.user);

// Menu items (hierarchical for sidebar)
const menuItems = computed(() => buildHierarchicalMenu());

/**
 * Breadcrumbs generieren
 */
const breadcrumbs = computed((): Breadcrumb[] => {
  const crumbs: Breadcrumb[] = [];
  const paths = route.path.split('/').filter(Boolean);
  let currentPath = '';

  // Home hinzufügen
  crumbs.push({
    title: 'Home',
    route: '/dashboard',
    disabled: false
  });

  // Hierarchie aufbauen
  paths.forEach(segment => {
    currentPath += `/${segment}`;
    const matchedRoute = router.getRoutes().find(r => r.path === currentPath);

    if (matchedRoute?.meta?.title) {
      crumbs.push({
        title: matchedRoute.meta.title as string,
        route: currentPath,
        disabled: false
      });
    } else {
      // Fallback: Capitalize segment
      crumbs.push({
        title: segment.charAt(0).toUpperCase() + segment.slice(1),
        route: currentPath,
        disabled: false
      });
    }
  });

  return crumbs;
});

/**
 * Toggle Sidebar (open/close on mobile)
 */
function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value;
}

/**
 * Toggle Sidebar Collapse (expand/collapse)
 */
function toggleSidebarCollapse() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  // Save preference to UI store
  uiStore.setSidebarCollapsed(sidebarCollapsed.value);
}

/**
 * ============================================
 * SCHNELLWECHSEL: Sidebar → Desktop
 * Ohne Logout! Sofortiger Wechsel!
 * ============================================
 */
async function switchToDesktopMode() {
  try {
    // 1. Setze Desktop-Modus
    uiStore.setDesktopMode(true);

    // 2. Speichere Präferenz - WICHTIG für F5 Reload!
    await uiStore.saveLayoutPreference('desktop');

    // 3. Navigiere zum Desktop
    await router.push('/desktop');
  } catch (error) {
    console.error('❌ Failed to switch to desktop mode:', error);
  }
}

/**
 * Handle navigation
 */
function handleNavigation(routePath: string) {
  router.push(routePath);
}

/**
 * Open global search
 */
function openGlobalSearch() {
  // Trigger global search (Strg+K)
  window.dispatchEvent(new KeyboardEvent('keydown', {
    key: 'k',
    ctrlKey: true
  }));
}

/**
 * Handle logout
 */
async function handleLogout() {
  try {
    await authStore.logout();
    await router.push('/');
  } catch (error) {
    console.error('Logout failed:', error);
  }
}

/**
 * Watch for permissions changes and reload menu
 * This ensures menu is updated after F5 reload when permissions load
 * Supports both legacy array format and new bitmask object format
 *
 * CRITICAL: Do NOT trigger during token refresh to prevent infinite loops!
 */
watch(
  () => authStore.user?.permissions,
  async (newPermissions, oldPermissions) => {
    // ✅ CRITICAL: Skip if currently refreshing token to prevent infinite loop
    if (authStore.isRefreshing || authStore.isLoading) {
      console.log('⏸️ Permissions changed during auth operation, skipping menu reload');
      return;
    }

    if (!newPermissions) {
      return;
    }

    // Check if permissions have substance (either array with items or object with keys)
    const hasPermissions = Array.isArray(newPermissions)
      ? newPermissions.length > 0
      : Object.keys(newPermissions).length > 0;

    if (hasPermissions && JSON.stringify(newPermissions) !== JSON.stringify(oldPermissions)) {
      await initializeMenu();
    }
  },
  { deep: true }
);

// Watch for active features changes and rebuild menu
// CRITICAL: Do NOT trigger during token refresh to prevent infinite loops!
watch(
  () => authStore.user?.active_features,
  (newFeatures, oldFeatures) => {
    // ✅ CRITICAL: Skip if currently refreshing token to prevent infinite loop
    if (authStore.isRefreshing || authStore.isLoading) {
      console.log('⏸️ Features changed during auth operation, skipping menu reload');
      return;
    }

    if (JSON.stringify(newFeatures) !== JSON.stringify(oldFeatures)) {
      initializeMenu();
    }
  },
  { deep: true }
);

onMounted(async () => {
  // ✅ CRITICAL: Wait until auth is fully loaded AND not refreshing before making API calls
  // This prevents infinite loop when tokens are expired and need refresh
  // Without this, onMounted fires before checkAuthStatus completes, causing:
  // 1. initializeMenu() makes API calls with expired tokens
  // 2. Each 401 triggers a refresh attempt
  // 3. Multiple parallel refreshes cause infinite loop

  // Wait for auth to finish loading AND refreshing
  while (authStore.isLoading || authStore.isRefreshing) {
    await new Promise(resolve => setTimeout(resolve, 50));
  }

  // Only initialize menu if user is actually logged in
  if (authStore.isLoggedIn) {
    await initializeMenu();

    // Load sidebar settings (including collapsed state)
    await uiStore.loadSidebarSettings();

    // Restore collapsed state from store
    sidebarCollapsed.value = uiStore.sidebarCollapsed;
  }
});
</script>

<style lang="scss" scoped>
.sidebar-layout-main {
  background-color: var(--background);
}

.content-container {
  min-height: calc(100vh - 64px);
  background-color: rgb(var(--v-theme-background));
}

// Smooth transitions für Content-Wechsel
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

// Responsive: Mobile
@media (max-width: 960px) {
  .content-container {
    padding: 16px !important;
  }
}
</style>
