<template>
  <div class="desktop-view-base">
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { inject, onMounted, onUpdated, ref, computed, watch } from 'vue';
import type { WindowContext } from '@/stores/windowContext';

// Props, die von außen übermittelt werden (entsprechen den Route-Parametern)
interface Props {
  desktopWindow?: boolean
  appId?: string
  key?: string
  hideTopNav?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  desktopWindow: false,
  appId: null,
  key: null,
  hideTopNav: false
});

// WindowContext aus dem umgebenden Fenster
const windowContext = inject<WindowContext>('windowContext', null);

// Emits für die Kommunikation mit dem umgebenden Fenster
const emit = defineEmits(['loaded', 'refresh-needed']);

// In Fenster gerendert oder direkt in der App
const isInWindow = computed(() => !!windowContext || props.desktopWindow);

// Prüfen, ob ein Desktop-Kontext vorhanden ist
onMounted(() => {
  console.log('DesktopViewBase mounted', { windowId: props.windowId, desktopWindow: props.desktopWindow });
  
  // Loading-Status aktualisieren, wenn Komponente gemountet ist
  if (windowContext) {
    windowContext.setLoading(false);
  }
  
  // Dem Fenster mitteilen, dass die Komponente geladen ist
  emit('loaded');
});

// Bei Update der Komponente
onUpdated(() => {
  if (windowContext) {
    // Loading-Status aktualisieren
    windowContext.setLoading(false);
  }
});

// Watchers
watch(() => props, (newProps) => {
  console.log('DesktopViewBase props changed', newProps);
}, { deep: true });
</script>

<style scoped>
.desktop-view-base {
  height: 100%;
  width: 100%;
  overflow: auto;
  background-color: var(--v-surface-base, var(--k-sunken));
  display: flex;
  flex-direction: column;
}
</style> 