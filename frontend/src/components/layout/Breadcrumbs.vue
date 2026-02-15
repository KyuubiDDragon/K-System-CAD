<template>
  <div class="breadcrumbs-wrapper">
    <v-breadcrumbs
      :items="breadcrumbsWithIcons"
      density="compact"
      class="pa-0"
    >
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="small" />
      </template>

      <template #item="{ item }">
        <v-breadcrumbs-item
          :to="item.disabled ? undefined : item.route"
          :disabled="item.disabled"
          class="breadcrumb-item"
        >
          <v-icon
            v-if="item.icon"
            :icon="item.icon"
            size="small"
            class="mr-1"
          />
          {{ item.title }}
        </v-breadcrumbs-item>
      </template>
    </v-breadcrumbs>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Breadcrumb } from '@/types/Menu';

interface Props {
  items: Breadcrumb[];
}

const props = defineProps<Props>();

/**
 * Add icons to breadcrumbs
 */
const breadcrumbsWithIcons = computed(() => {
  return props.items.map((item, index) => {
    // First item (Home) gets home icon
    if (index === 0) {
      return {
        ...item,
        icon: 'mdi-home',
        disabled: item.disabled || false
      };
    }
    // Last item is disabled (current page)
    if (index === props.items.length - 1) {
      return {
        ...item,
        disabled: true
      };
    }
    return {
      ...item,
      disabled: item.disabled || false
    };
  });
});
</script>

<style lang="scss" scoped>
.breadcrumbs-wrapper {
  max-width: 100%;
  overflow: hidden;
}

.breadcrumb-item {
  font-size: 0.875rem;
  transition: color 0.2s ease;

  &:not(.v-breadcrumbs-item--disabled):hover {
    color: rgb(var(--v-theme-primary));
  }
}
</style>
