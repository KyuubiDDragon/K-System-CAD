<template>
  <!--
    Section Header

    Ohne mt-4 mb-2: die beiden Hilfsklassen setzten 16 px Luft oben und 8 unten
    und gewannen damit gegen die Regel des Systems, die 12 und 4 vorgibt. Die
    fuenf Gruppen trieben dadurch auseinander und lasen sich wie fuenf Inseln
    statt wie eine Liste. Der Abstand kommt jetzt aus scss/_density.scss.
  -->
  <v-list-subheader
    v-if="item.isSectionHeader"
    class="text-overline font-weight-bold"
    :class="{ 'text-center': collapsed }"
  >
    <!--
      Ohne Symbol: der Entwurf zeigt fuer die Gruppenzeile nur den gesperrten
      Text in Versalien. Das Symbol nahm mitsamt Abstand 25 px, weshalb
      "EINSATZ & KOMMUNIKATION" als einzige Zeile noch abbrach - und es ordnet
      nichts, was die Beschriftung nicht schon sagt.
    -->
    <span v-if="!collapsed">{{ item.title }}</span>
  </v-list-subheader>

  <!-- Nicht-Header Items -->
  <template v-else-if="!item.isSectionHeader">
    <!-- Gruppe mit Untermenüs -->
    <template v-if="item.children && item.children.length > 0">
    <!-- Wenn collapsed: zeige Icon mit Hover-Menü -->
    <v-menu
      v-if="collapsed"
      open-on-hover
      location="end"
      :close-delay="100"
      :open-delay="50"
    >
      <template #activator="{ props: menuProps }">
        <v-list-item
          v-bind="menuProps"
          :class="{ 'menu-item-collapsed': collapsed }"
          class="menu-item-group"
        >
          <template #prepend>
            <v-icon :color="isAnyChildActive ? 'primary' : undefined">
              {{ item.icon }}
            </v-icon>
          </template>
        </v-list-item>
      </template>

      <!-- Popup-Menü mit Unterpunkten -->
      <v-list density="compact" class="popup-menu elevation-4">
        <v-list-subheader class="text-caption font-weight-bold">
          {{ item.title }}
        </v-list-subheader>
        <v-divider class="my-1" />
        <v-list-item
          v-for="child in item.children"
          :key="child.id"
          :prepend-icon="child.icon"
          :active="activeRoute === child.route || activeRoute.startsWith(child.route + '/')"
          @click="handleChildClick(child.route)"
          class="popup-menu-item"
        >
          <v-list-item-title>{{ child.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Wenn expanded: normale v-list-group -->
    <v-list-group
      v-else
      :value="item.id"
      v-model="isGroupOpen"
    >
      <template #activator="{ props: activatorProps, isOpen }">
        <v-list-item
          v-bind="activatorProps"
          :class="{ 'menu-item-collapsed': collapsed }"
          class="menu-item-group"
        >
          <template #prepend>
            <v-icon :color="isAnyChildActive ? 'primary' : undefined">
              {{ item.icon }}
            </v-icon>
          </template>

          <v-list-item-title v-if="!collapsed">
            {{ item.title }}
            <v-chip
              v-if="item.badge"
              size="x-small"
              :color="item.badgeColor || 'error'"
              class="ml-2"
            >
              {{ item.badge }}
            </v-chip>
          </v-list-item-title>

          <!-- Dropdown Arrow -->
          <template #append v-if="!collapsed">
            <v-icon size="small">
              {{ isOpen ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
            </v-icon>
          </template>
        </v-list-item>
      </template>

      <!-- Rekursive Child-Items -->
      <sidebar-menu-item
        v-for="child in item.children"
        :key="child.id"
        :item="child"
        :collapsed="collapsed"
        :active-route="activeRoute"
        :level="level + 1"
        @navigate="$emit('navigate', $event)"
      />
    </v-list-group>
    </template>

    <!-- Einfacher Link (kein Untermenü) -->
    <v-list-item
      v-else
      :prepend-icon="isImageIcon(item.icon) ? undefined : item.icon"
      :active="isActive"
      :class="[
        'menu-item',
        { 'menu-item-collapsed': collapsed },
        `menu-item-level-${level}`
      ]"
      @click="handleClick"
    >
    <!-- Custom Image Icon -->
    <template #prepend v-if="isImageIcon(item.icon)">
      <v-avatar size="24" class="mr-2">
        <v-img :src="item.icon" :alt="item.title" />
      </v-avatar>
    </template>

    <!-- Tooltip für collapsed state -->
    <v-tooltip
      v-if="collapsed"
      location="end"
      :text="item.title"
    >
      <template #activator="{ props: tooltipProps }">
        <div v-bind="tooltipProps" class="full-width" />
      </template>
    </v-tooltip>

    <v-list-item-title v-if="!collapsed">
      {{ item.title }}
      <v-chip
        v-if="item.badge"
        size="x-small"
        :color="item.badgeColor || 'error'"
        class="ml-2"
      >
        {{ item.badge }}
      </v-chip>
    </v-list-item-title>


    <!-- Aktiver Indikator (linker Balken) -->
    <div v-if="isActive" class="active-indicator" />
  </v-list-item>
  </template>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useUIStore } from '@/stores/ui';
import type { MenuItem } from '@/types/Menu';

interface Props {
  item: MenuItem;
  collapsed: boolean;
  activeRoute: string;
  level?: number;
  pinned?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  level: 0,
  pinned: false
});

const emit = defineEmits(['navigate']);
const route = useRoute();
const uiStore = useUIStore();

/**
 * Check if item is active
 */
const isActive = computed(() => {
  if (!props.item.route) return false;
  return props.activeRoute === props.item.route ||
         props.activeRoute.startsWith(props.item.route + '/');
});

/**
 * Check if any child is active (for group highlighting)
 */
const isAnyChildActive = computed(() => {
  if (!props.item.children) return false;

  function checkRecursive(items: MenuItem[]): boolean {
    for (const child of items) {
      if (child.route && (props.activeRoute === child.route || props.activeRoute.startsWith(child.route + '/'))) {
        return true;
      }
      if (child.children && checkRecursive(child.children)) {
        return true;
      }
    }
    return false;
  }

  return checkRecursive(props.item.children);
});

/**
 * Control group open/close state
 * Automatically open group if any child is active (e.g., after F5 reload)
 */
const isGroupOpen = ref(isAnyChildActive.value);

/**
 * Watch for route changes and auto-expand if child becomes active
 */
watch(() => props.activeRoute, () => {
  if (isAnyChildActive.value && !isGroupOpen.value) {
    isGroupOpen.value = true;
  }
}, { immediate: true });

/**
 * Check if pinned
 */
const isPinned = computed(() => {
  return uiStore.pinnedMenuItems?.includes(props.item.id) || props.pinned;
});

/**
 * Check if icon is an image path
 */
function isImageIcon(icon: string): boolean {
  if (!icon || typeof icon !== 'string') return false;
  return icon.startsWith('/') ||
         icon.includes('.png') ||
         icon.includes('.jpg') ||
         icon.includes('.jpeg') ||
         icon.includes('.svg');
}

/**
 * Handle click
 */
function handleClick() {
  if (props.item.route) {
    emit('navigate', props.item.route);
  } else if (props.item.action) {
    // Handle special actions
    console.log('Action triggered:', props.item.action);
  }
}

/**
 * Handle child click (from popup menu)
 */
function handleChildClick(route: string) {
  if (route) {
    emit('navigate', route);
  }
}
</script>

<style lang="scss" scoped>
.menu-item {
  position: relative;
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);

    .pin-button {
      opacity: 1;
    }
  }

  &.v-list-item--active {
    background-color: rgba(var(--v-theme-primary), 0.15);
    color: rgb(var(--v-theme-primary));

    .active-indicator {
      opacity: 1;
    }
  }
}

.menu-item-collapsed {
  justify-content: center !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  min-width: 0 !important;

  // Hide spacer in collapsed state
  :deep(.v-list-item__spacer) {
    display: none !important;
  }

  // Hide content (text) in collapsed state
  :deep(.v-list-item__content) {
    display: none !important;
  }

  // Hide append section
  :deep(.v-list-item__append) {
    display: none !important;
  }

  // Center the prepend icon with empirical offset
  :deep(.v-list-item__prepend) {
    margin-inline-start: 0 !important;
    margin-inline-end: 0 !important;
    margin-left: 7px !important;  // Empirical offset for perfect visual centering
    margin-right: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    min-width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;

    // Center icon within prepend
    > .v-icon {
      margin: 0 !important;
    }
  }

  // Ensure overlay is properly positioned
  :deep(.v-list-item__overlay) {
    z-index: 0;
  }
}

// Level-based indentation
.menu-item-level-1 {
  padding-left: 48px !important;
  font-size: 0.9rem;
}
.menu-item-level-2 {
  padding-left: 64px !important;
  font-size: 0.875rem;
}
.menu-item-level-3 {
  padding-left: 80px !important;
  font-size: 0.85rem;
}

.menu-item-group {
  font-weight: 600;

  :deep(.v-list-group__header__append-icon) {
    transition: transform 0.2s ease;
  }
}

.active-indicator {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 24px;
  background-color: rgb(var(--v-theme-primary));
  border-radius: 0 4px 4px 0;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.pin-button {
  opacity: 0;
  transition: opacity 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.12);
  }
}

.full-width {
  width: 100%;
  height: 100%;
}

.popup-menu {
  min-width: 200px;
  max-width: 300px;
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface));

  .v-list-subheader {
    padding-top: 8px;
    padding-bottom: 4px;
  }
}

.popup-menu-item {
  border-radius: 4px;
  margin: 2px 4px;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
  }

  &.v-list-item--active {
    background-color: rgba(var(--v-theme-primary), 0.15);
  }
}
</style>
