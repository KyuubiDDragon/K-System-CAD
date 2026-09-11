<template>
  <div class="dashboard-grid-container">
    <!-- Grid Layout -->
    <grid-layout
      v-model:layout="layoutModel"
      :col-num="12"
      :row-height="30"
      :is-draggable="editMode"
      :is-resizable="editMode"
      :vertical-compact="true"
      :use-css-transforms="true"
      :margin="[16, 16]"
      :responsive="true"
      @layout-updated="onLayoutUpdated"
    >
      <grid-item
        v-for="item in layoutModel"
        :key="item.i"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        :i="item.i"
        :min-w="item.minW"
        :min-h="item.minH"
        :max-w="item.maxW"
        :max-h="item.maxH"
        :static="item.static"
        class="grid-item-wrapper"
        :class="{ 'edit-mode': editMode }"
      >
        <DashboardWidget
          :widget-id="item.i"
          :widget-config="widgets[item.i]"
          :edit-mode="editMode"
          @remove="handleRemoveWidget(item.i)"
          @configure="handleConfigureWidget(item.i)"
        />
      </grid-item>
    </grid-layout>

    <!-- Empty State -->
    <div v-if="layoutModel.length === 0" class="empty-state">
      <v-icon size="64" color="grey">mdi-view-dashboard-outline</v-icon>
      <p class="text-h6 mt-4">{{ $t('dashboard.emptyState.title') }}</p>
      <p class="text-body-2 text-grey">{{ $t('dashboard.emptyState.description') }}</p>
      <v-btn
        v-if="editMode"
        color="primary"
        class="mt-4"
        @click="$emit('add-widget')"
      >
        <v-icon left>mdi-plus</v-icon>
        {{ $t('dashboard.addWidget') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { GridLayout, GridItem } from 'vue-grid-layout-v3'
import DashboardWidget from './DashboardWidget.vue'
import type { WidgetLayout, WidgetConfig } from '@/stores/dashboardStore'

interface Props {
  layout: WidgetLayout[]
  widgets: Record<string, WidgetConfig>
  editMode: boolean
}

interface Emits {
  (e: 'update:layout', value: WidgetLayout[]): void
  (e: 'remove-widget', widgetId: string): void
  (e: 'configure-widget', widgetId: string): void
  (e: 'add-widget'): void
  (e: 'layout-updated', layout: WidgetLayout[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local layout model for grid-layout v-model
const layoutModel = computed({
  get: () => props.layout,
  set: (value) => emit('update:layout', value)
})

/**
 * Handle layout updated event from grid-layout
 */
function onLayoutUpdated(newLayout: WidgetLayout[]) {
  emit('layout-updated', newLayout)
}

/**
 * Handle widget removal
 */
function handleRemoveWidget(widgetId: string) {
  emit('remove-widget', widgetId)
}

/**
 * Handle widget configuration
 */
function handleConfigureWidget(widgetId: string) {
  emit('configure-widget', widgetId)
}

// Watch layout changes for debugging
watch(
  () => props.layout,
  (newLayout) => {
    console.log('Dashboard layout updated:', newLayout.length, 'widgets')
  },
  { deep: true }
)
</script>

<style scoped lang="scss">
/*
   Der Aussenrand des Rasters.

   vue-grid-layout legt seinen Abstand von 16 px zwischen die Kacheln, aber
   nicht an den Rand - die linke Spalte klebte an der Kante des
   Arbeitsbereichs, waehrend zwischen den Spalten 16 px standen. Gemessen
   begannen die Spalten bei 0, 328 und 336: der Aussenrand fehlte, die
   waagerechte Luecke war mal 8, mal 16.

   Der negative Rand zieht den Rasterabstand nach aussen, das Polster gibt ihn
   als gleichen Wert zurueck. Damit steht ringsum und dazwischen dasselbe.
*/
.dashboard-grid-container {
  width: 100%;
  min-height: 400px;
  position: relative;
  padding: 0 8px;
}

:deep(.vue-grid-layout) {
  margin: 0 -8px;
}

.grid-item-wrapper {
  touch-action: none;

  &.edit-mode {
    cursor: move;

    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
  }
}

// Grid layout styles
:deep(.vue-grid-layout) {
  background: transparent;
  transition: height 0.3s ease;
}

:deep(.vue-grid-item) {
  transition: all 0.3s ease;

  &.vue-grid-placeholder {
    background: rgba(var(--v-theme-primary), 0.2);
    border: 2px dashed rgba(var(--v-theme-primary), 0.5);
    border-radius: 8px;
    z-index: 2;
    transition: all 0.2s ease;
  }

  &.resizing {
    opacity: 0.9;
    z-index: 3;
  }

  &.vue-draggable-dragging {
    opacity: 0.8;
    z-index: 4;
    transition: none;
  }
}

// Resize handle styles
:deep(.vue-resizable-handle) {
  opacity: 0;
  transition: opacity 0.2s ease;
}

:deep(.vue-grid-item:hover .vue-resizable-handle) {
  opacity: 1;
}

// Empty state
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 3rem;
  text-align: center;
  opacity: 0.7;
}

/* Der Aussenrand gilt jetzt in jeder Breite - die Medienabfrage, die ihn
   frueher nur auf schmalen Bildschirmen setzte, ist damit hinfaellig. */

@media (max-width: 600px) {
  :deep(.vue-grid-item) {
    // Force single column on mobile
    width: 100% !important;
    transform: translate(0, var(--y)) !important;
  }
}
</style>
