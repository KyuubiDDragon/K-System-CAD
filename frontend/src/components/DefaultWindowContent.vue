<template>
  <div class="default-window-content">
    <div class="missing-component">
      <v-icon size="48" color="grey" class="mb-4">mdi-alert-circle-outline</v-icon>
      <h3 class="text-h6 mb-2">{{$t('defaultWindowContent.componentNotFound')}}</h3>
      <p class="text-subtitle-1 text-grey mb-4">
        {{ t('defaultWindowContent.loadError') }}
      </p>
      <div class="technical-info">
        <pre v-if="windowProps.route">{{ t('defaultWindowContent.route') }}: {{ windowProps.route }}</pre>
        <pre v-if="windowProps.appId">{{ t('defaultWindowContent.appId') }}: {{ windowProps.appId }}</pre>
        <pre>{{ t('defaultWindowContent.availableProps') }}:</pre>
        <pre class="props-details">{{ propsDetails }}</pre>
      </div>
      
      <div class="action-buttons mt-4">
        <v-btn color="primary" @click="reloadWindow">
          <v-icon left>mdi-refresh</v-icon>
          {{$t('defaultWindowContent.reloadComponent')}}
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject, ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { WindowContext } from '@/stores/windowContext';

const { t } = useI18n();

// Props von außen
const props = defineProps<{
  windowId?: string;
  route?: string;
  appId?: string;
  [key: string]: any;
}>();

// Window-Kontext und Props
const windowContext = inject<WindowContext>('windowContext');
const windowProps = ref(props);

// Formatieren der Props für bessere Lesbarkeit
const propsDetails = computed(() => {
  const details = { ...props };
  // Einige Props, die zu lang sein könnten, ausschließen
  delete details.class;
  delete details.style;
  return JSON.stringify(details, null, 2);
});

// Komponente neu laden
const reloadWindow = () => {
  if (windowContext) {
    windowContext.setLoading(true);
    // Kurze Verzögerung, um Loading-Effekt zu zeigen
    setTimeout(() => {
      // Hier können Sie optional die Route oder App neu laden
      // Für jetzt setzen wir einfach loading wieder auf false
      windowContext.setLoading(false);
    }, 1000);
  } else if (props.windowId) {
    // Wenn im iFrame-Modus: Iframe neu laden
    const iframe = document.querySelector(`iframe[data-window-id="${props.windowId}"]`);
    if (iframe && iframe instanceof HTMLIFrameElement) {
      iframe.src = iframe.src;
    }
  }
};

// Wenn das Fenster geladen ist, Loading-Status auf false setzen
onMounted(() => {
  if (windowContext) {
    // Im Desktop-Kontext
    windowContext.setLoading(false);
  }
  
  // Log für Debugging-Zwecke
  console.log('DefaultWindowContent geladen mit Props:', props);
});
</script>

<style scoped>
.default-window-content {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  padding: 20px;
  background-color: var(--v-surface-variant);
}

.missing-component {
  max-width: 500px;
  text-align: center;
  padding: 40px;
  border-radius: 8px;
  background-color: var(--k-surface);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.technical-info {
  margin-top: 20px;
  text-align: left;
  background-color: var(--k-sunken);
  padding: 12px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
}

pre {
  white-space: pre-wrap;
  overflow-wrap: break-word;
}
</style> 