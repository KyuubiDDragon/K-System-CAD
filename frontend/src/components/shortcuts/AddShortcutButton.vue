<template>
  <v-btn
    :icon="isShortcut ? 'mdi-star' : 'mdi-star-outline'"
    :color="isShortcut ? 'primary' : undefined"
    variant="text"
    :loading="loading"
    @click="toggleShortcut"
    :aria-label="buttonLabel"
  >
    <v-icon />
    <v-tooltip activator="parent" location="bottom">
      {{ buttonLabel }}
    </v-tooltip>
  </v-btn>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useShortcutsStore } from '@/stores/shortcuts';
import type { ShortcutType, ShortcutColor } from '@/types/Shortcut';

interface Props {
  type: ShortcutType;
  resourceId: number;
  title: string;
  subtitle?: string;
  icon?: string;
  color?: ShortcutColor;
  metadata?: Record<string, any>;
}

const props = defineProps<Props>();

const { t } = useI18n();
const shortcutsStore = useShortcutsStore();
const loading = ref(false);

// Computed
const isShortcut = computed(() =>
  shortcutsStore.hasShortcut(props.type, props.resourceId)
);

const buttonLabel = computed(() =>
  isShortcut.value
    ? t('shortcuts.removeFromShortcuts')
    : t('shortcuts.addToShortcuts')
);

// Methods
const toggleShortcut = async () => {
  loading.value = true;

  try {
    if (isShortcut.value) {
      // Remove shortcut
      const shortcut = shortcutsStore.shortcuts.find(
        s => s.type === props.type && s.resource_id === props.resourceId
      );

      if (shortcut) {
        await shortcutsStore.removeShortcut(shortcut.id);
      }
    } else {
      // Add shortcut
      await shortcutsStore.addShortcut({
        type: props.type,
        resource_id: props.resourceId,
        title: props.title,
        subtitle: props.subtitle,
        icon: props.icon,
        color: props.color,
        metadata: props.metadata,
      });
    }
  } catch (error) {
    console.error('Failed to toggle shortcut:', error);
  } finally {
    loading.value = false;
  }
};

// Load shortcuts on mount
onMounted(() => {
  shortcutsStore.loadShortcuts();
});
</script>
