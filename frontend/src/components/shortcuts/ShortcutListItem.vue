<template>
  <v-list-item
    :prepend-icon="displayIcon"
    :title="shortcut.title"
    :subtitle="shortcut.subtitle"
    density="comfortable"
    class="shortcut-list-item"
    @click="$emit('navigate', shortcut)"
    @keydown.enter="$emit('navigate', shortcut)"
    @keydown.space.prevent="$emit('navigate', shortcut)"
    :tabindex="0"
    role="menuitem"
    :aria-label="ariaLabel"
  >
    <!-- Optional: Color indicator -->
    <template #prepend v-if="shortcut.color">
      <v-icon :icon="displayIcon" :color="shortcut.color" />
    </template>

    <!-- Actions -->
    <template #append>
      <v-btn
        icon="mdi-close"
        size="small"
        variant="text"
        @click.stop="handleRemove"
        :aria-label="$t('shortcuts.removeFromShortcuts')"
        class="remove-btn"
      >
        <v-icon size="small">mdi-close</v-icon>
      </v-btn>
    </template>
  </v-list-item>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Shortcut } from '@/types/Shortcut';

// Props
interface Props {
  shortcut: Shortcut;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  navigate: [shortcut: Shortcut];
  remove: [shortcut: Shortcut];
}>();

// i18n
const { t } = useI18n();

// Computed
const displayIcon = computed(() => {
  // Fallback icons by type if not set
  const iconMap: Record<string, string> = {
    document: 'mdi-file-document',
    person_file: 'mdi-account',
    company_file: 'mdi-domain',
    apartment_file: 'mdi-home',
    document_category: 'mdi-folder',
    document_area: 'mdi-folder-multiple',
    report: 'mdi-file-chart',
    employee: 'mdi-account-tie',
    vehicle: 'mdi-car',
    training: 'mdi-school',
    route: 'mdi-map-marker',
    blackboard_global: 'mdi-bulletin-board',  // NEW
    blackboard_area: 'mdi-bulletin-board',     // NEW
  };

  return props.shortcut.icon || iconMap[props.shortcut.type] || 'mdi-star';
});

const ariaLabel = computed(() => {
  return `${props.shortcut.title}${props.shortcut.subtitle ? ` - ${props.shortcut.subtitle}` : ''}. ${t('shortcuts.pressEnterToOpen')}`;
});

// Methods
const handleRemove = () => {
  emit('remove', props.shortcut);
};
</script>

<style scoped>
.shortcut-list-item {
  transition: background-color 0.2s ease;
  cursor: pointer;
}

.shortcut-list-item:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.08);
}

.shortcut-list-item:focus {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: -2px;
}

.remove-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.shortcut-list-item:hover .remove-btn,
.shortcut-list-item:focus-within .remove-btn {
  opacity: 1;
}
</style>
