<template>
  <v-app-bar
    elevation="1"
    class="top-bar"
    color="surface"
    height="64"
  >
    <!-- Toggle Sidebar Button -->
    <v-btn
      :icon="sidebarCollapsed ? 'mdi-menu' : 'mdi-menu-open'"
      variant="text"
      @click="$emit('toggle-sidebar')"
    >
      <v-tooltip activator="parent" location="bottom">
        {{ sidebarCollapsed ? $t('menu.expand') : $t('menu.collapse') }}
      </v-tooltip>
    </v-btn>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container mx-4">
      <breadcrumbs :items="breadcrumbs" />
    </div>

    <v-spacer />

    <!-- Actions -->
    <div class="d-flex align-center ga-2">
      <!-- Global Search -->
      <v-btn
        icon="mdi-magnify"
        variant="text"
        @click="$emit('open-search')"
      >
        <v-icon />
        <v-tooltip activator="parent" location="bottom">
          {{ $t('menu.search') }} (Strg+K)
        </v-tooltip>
      </v-btn>

      <!-- Notifications -->
      <v-menu offset-y>
        <template #activator="{ props: menuProps }">
          <v-btn
            icon
            variant="text"
            v-bind="menuProps"
          >
            <v-badge
              :content="notificationCount"
              :model-value="notificationCount > 0"
              color="error"
              overlap
            >
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
            <v-tooltip activator="parent" location="bottom">
              {{ $t('menu.notifications') }}
            </v-tooltip>
          </v-btn>
        </template>

        <v-card min-width="300" max-width="400">
          <v-card-title class="text-subtitle-1">
            {{ $t('menu.notifications') }}
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-0">
            <v-list density="compact">
              <v-list-item v-if="notificationCount === 0">
                <v-list-item-title class="text-center text-medium-emphasis">
                  {{ $t('menu.noNotifications') }}
                </v-list-item-title>
              </v-list-item>
              <!-- Add actual notifications here -->
            </v-list>
          </v-card-text>
        </v-card>
      </v-menu>

      <!-- Mail -->
      <v-btn
        icon
        variant="text"
        @click="router.push('/mail')"
      >
        <v-badge
          :content="mailUnreadCount"
          :model-value="mailUnreadCount > 0"
          color="error"
          overlap
        >
          <v-icon>mdi-email</v-icon>
        </v-badge>
        <v-tooltip activator="parent" location="bottom">
          {{ $t('menu.mail') || 'Mail' }}
        </v-tooltip>
      </v-btn>

      <!-- ============================================ -->
      <!-- QUICK ACCESS SHORTCUTS (v2.2) -->
      <!-- ============================================ -->
      <shortcuts-menu />

      <!-- ============================================ -->
      <!-- SCHNELLWECHSEL: Sidebar ↔ Desktop -->
      <!-- ============================================ -->
      <v-tooltip location="bottom">
        <template #activator="{ props: tooltipProps }">
          <v-btn
            icon="mdi-swap-horizontal"
            variant="text"
            color="primary"
            v-bind="tooltipProps"
            @click="handleLayoutSwitch"
            :loading="switchingLayout"
          >
            <v-icon />
          </v-btn>
        </template>
        {{ $t('menu.switchToDesktop') }}
      </v-tooltip>

      <!-- Theme Toggle -->
      <v-btn
        :icon="isDark ? 'mdi-weather-night' : 'mdi-weather-sunny'"
        variant="text"
        @click="toggleTheme"
      >
        <v-icon />
        <v-tooltip activator="parent" location="bottom">
          {{ isDark ? $t('menu.lightMode') : $t('menu.darkMode') }}
        </v-tooltip>
      </v-btn>

      <!-- User Menu -->
      <v-menu offset-y>
        <template #activator="{ props: menuProps }">
          <v-btn
            variant="text"
            class="user-menu-button"
            v-bind="menuProps"
          >
            <v-avatar size="32" class="mr-2">
              <v-img
                v-if="user?.avatar"
                :src="user.avatar"
                :alt="userName"
              />
              <v-icon v-else>mdi-account-circle</v-icon>
            </v-avatar>
            <span class="user-name">{{ userName }}</span>
            <v-icon class="ml-1" size="small">mdi-chevron-down</v-icon>
          </v-btn>
        </template>

        <v-list density="compact">
          <v-list-item prepend-icon="mdi-account" to="/profile">
            <v-list-item-title>{{ $t('menu.profile') }}</v-list-item-title>
          </v-list-item>
          <v-list-item prepend-icon="mdi-cog" to="/settings">
            <v-list-item-title>{{ $t('menu.settings') }}</v-list-item-title>
          </v-list-item>
          <v-divider />
          <v-list-item
            prepend-icon="mdi-logout"
            @click="$emit('logout')"
          >
            <v-list-item-title>{{ $t('menu.logout') }}</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </v-app-bar>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useThemeStore } from '@/stores/theme';
import { useUIStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import { useMailStore } from '@/stores/mail';
import Breadcrumbs from './Breadcrumbs.vue';
import ShortcutsMenu from '@/components/shortcuts/ShortcutsMenu.vue';
import type { Breadcrumb } from '@/types/Menu';
import type { User } from '@/types/User';

interface Props {
  sidebarCollapsed: boolean;
  breadcrumbs: Breadcrumb[];
  user: User | null;
}

const props = defineProps<Props>();
const emit = defineEmits([
  'toggle-sidebar',
  'toggle-layout',
  'open-search',
  'logout'
]);

const router = useRouter();
const themeStore = useThemeStore();
const uiStore = useUIStore();
const authStore = useAuthStore();
const mailStore = useMailStore();

// State
const switchingLayout = ref(false);

// Computed
const isDark = computed(() => themeStore.isDark);
const notificationCount = computed(() => authStore.user?.unreadNotificationsCount || 0);
const mailUnreadCount = computed(() => mailStore.unreadCount);
const userName = computed(() => props.user?.display_name || props.user?.name || props.user?.username || 'User');

/**
 * Toggle theme (dark/light)
 */
function toggleTheme() {
  themeStore.toggleDarkMode();
}

/**
 * ============================================
 * SCHNELLWECHSEL: Sidebar → Desktop
 * Ohne Logout! Sofortiger Wechsel!
 * ============================================
 */
async function handleLayoutSwitch() {
  switchingLayout.value = true;

  try {
    // 1. Setze Desktop-Modus
    uiStore.setDesktopMode(true);

    // 2. Speichere Präferenz - WICHTIG für F5 Reload!
    await uiStore.saveLayoutPreference('desktop');

    // 3. Navigiere zum Desktop
    await router.push('/desktop');

    console.log('✅ Switched from Sidebar to Desktop mode');
  } catch (error) {
    console.error('❌ Failed to switch layout:', error);
  } finally {
    switchingLayout.value = false;
  }
}
</script>

<style lang="scss" scoped>
.top-bar {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
}

.breadcrumbs-container {
  max-width: 600px;
  overflow: hidden;
}

.user-menu-button {
  text-transform: none;
  letter-spacing: normal;

  .user-name {
    font-size: 0.875rem;
    font-weight: 500;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
</style>
