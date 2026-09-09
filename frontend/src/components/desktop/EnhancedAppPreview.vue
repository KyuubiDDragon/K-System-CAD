<!-- Erweiterte App-Vorschau mit mehr interaktiven Funktionen -->
<template>
  <div 
    class="enhanced-app-preview" 
    :class="{ 'show': show, 'multiple-windows': hasMultipleWindows }" 
    :style="previewPositionStyle"
    @mouseleave="resetCloseTimer"
  >
    <div class="preview-container">
      <!-- Header mit App-Informationen -->
      <div class="preview-header">
        <div class="app-info">
          <div class="app-icon-container" :style="{ backgroundColor: getAppColor(app) + '20' }">
            <template v-if="app.icon && isImagePath(app.icon)">
              <img :src="app.icon" class="app-icon" :alt="app.title">
            </template>
            <template v-else>
              <v-icon size="16" :color="getAppColor(app)">{{ app.icon }}</v-icon>
            </template>
          </div>
          <div class="app-title-container">
            <span class="app-title">{{ app.title }}</span>
            <span class="app-subtitle" v-if="app.subtitle">{{ app.subtitle }}</span>
          </div>
        </div>
        <div class="preview-actions" v-if="!hasMultipleWindows">
          <div class="action-button" @click="minimizeApp" :v-tooltip="t('desktop.minimize')">
            <v-icon size="14">mdi-minus</v-icon>
          </div>
          <div class="action-button close" @click="closeApp" :v-tooltip="t('close')">
            <v-icon size="14">mdi-close</v-icon>
          </div>
        </div>
      </div>

      <!-- Fenster-Vorschau -->
      <div class="preview-content">
        <!-- Wenn keine Fenster vorhanden sind, zeige Platzhalter -->
        <div class="preview-placeholder" v-if="!app.windows || app.windows.length === 0">
          <div class="app-logo-container">
            <template v-if="app.icon && isImagePath(app.icon)">
              <img :src="app.icon" class="app-logo" :alt="app.title">
            </template>
            <template v-else>
              <v-icon size="56" :color="getAppColor(app)">{{ app.icon }}</v-icon>
            </template>
          </div>
          <div class="placeholder-message">{{ t('desktop.noActiveWindows') }}</div>
        </div>

        <!-- Wenn mehrere Fenster vorhanden sind, zeige Fenster-Auswahl -->
        <div class="windows-carousel" v-else-if="hasMultipleWindows">
          <div
            v-for="(window, index) in app.windows"
            :key="window.id"
            class="window-preview-item"
            :class="{ 'active': activeWindowIndex === index }"
            @click="selectWindow(index)"
          >
            <div class="window-preview-container">
              <img 
                v-if="window.screenshot" 
                :src="window.screenshot" 
                class="window-screenshot" 
                :alt="`${window.title || app.title} preview`"
              >
              <div v-else class="window-thumbnail-placeholder">
                <v-icon size="24" :color="getAppColor(app)">{{ app.icon }}</v-icon>
                <div class="window-placeholder-title">{{ window.title || app.title }}</div>
              </div>
              
              <div class="window-index-badge">{{ index + 1 }}</div>
            </div>
            <div class="window-preview-title">
              {{ window.title || app.title }}
              <div class="window-preview-subtitle" v-if="window.subtitle">{{ window.subtitle }}</div>
            </div>
          </div>
        </div>

        <!-- Aktives Fenster-Vorschau -->
        <div class="active-window-preview" v-else>
          <img 
            v-if="currentWindow && currentWindow.screenshot" 
            :src="currentWindow.screenshot" 
            class="preview-screenshot" 
            :alt="`${app.title} preview`"
          >
          <div v-else class="preview-placeholder-content">
            <template v-if="app.icon && isImagePath(app.icon)">
              <img :src="app.icon" class="app-logo" :alt="app.title">
            </template>
            <template v-else>
              <v-icon size="48" :color="getAppColor(app)">{{ app.icon }}</v-icon>
            </template>
            <div class="placeholder-message">{{ t('desktop.noPreviewAvailable') }}</div>
          </div>
        </div>
      </div>

      <!-- Footer mit Aktionen und Informationen -->
      <div class="preview-footer">
        <!-- Benachrichtigungen und Status -->
        <div class="status-info">
          <div class="notification-indicator" v-if="app.notifications && app.notifications > 0">
            {{ t(app.notifications === 1 ? 'desktop.newNotification' : 'desktop.newNotifications', { count: app.notifications }) }}
          </div>
          <div class="last-activity" v-else-if="app.lastActivity">
            {{ t('desktop.lastActivity') }}: {{ formatLastActivity(app.lastActivity) }}
          </div>
        </div>

        <!-- Schnellaktionen -->
        <div class="quick-actions">
          <template v-if="hasMultipleWindows">
            <div class="action-chip" @click="closeAllWindows">
              <v-icon size="14">mdi-window-close</v-icon>
              <span>{{ t('desktop.closeAll') }}</span>
            </div>
          </template>
          <template v-else>
            <div class="action-chip" @click="activateApp">
              <v-icon size="14">mdi-arrow-up</v-icon>
              <span>{{ t('desktop.bringToFront') }}</span>
            </div>
          </template>
          <div class="action-chip" @click="restartApp" v-if="app.restartable">
            <v-icon size="14">mdi-restart</v-icon>
            <span>{{ t('desktop.restart') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation für mehrere Fenster -->
    <div class="multi-window-navigation" v-if="hasMultipleWindows">
      <div 
        class="window-dot" 
        v-for="(window, index) in app.windows" 
        :key="index"
        :class="{ 'active': activeWindowIndex === index }"
        @click="selectWindow(index)"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';

interface Position {
  x: number;
  y: number;
}

interface AppWindow {
  id: string;
  title?: string;
  subtitle?: string;
  screenshot?: string;
}

interface AppType {
  id: string;
  title: string;
  subtitle?: string;
  icon: string;
  color?: string;
  notifications?: number;
  lastActivity?: number;
  restartable?: boolean;
  windows?: AppWindow[];
}

// Props
interface Props {
  app?: Record<string, any>
  show?: boolean
  position?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  position: () => ({ x: 0, y: 0 })
});

// Emits
const emit = defineEmits([
  'close',
  'close-all',
  'minimize',
  'activate',
  'restart',
  'select-window'
]);
const { t } = useI18n();

// Zustände
const activeWindowIndex = ref(0);
const closeTimer = ref<number | null>(null);
const isDragging = ref(false);
const dragOffset = ref<Position>({ x: 0, y: 0 });
const previewPosition = ref<Position>({ x: props.position.x, y: props.position.y });

// Berechnete Eigenschaften
const hasMultipleWindows = computed(() => {
  return props.app.windows && props.app.windows.length > 1;
});

const currentWindow = computed(() => {
  if (props.app.windows && props.app.windows.length > 0) {
    return props.app.windows[activeWindowIndex.value];
  }
  return null;
});

const previewPositionStyle = computed(() => {
  // Grundposition basierend auf Taskbar-Position anpassen
  let x = previewPosition.value.x;
  let y = previewPosition.value.y;
  
  // Anpassung je nach Taskbar-Position
  if (props.taskbarPosition === 'bottom') {
    y = y - 30;
  } else if (props.taskbarPosition === 'top') {
    y = y + 50;
  } else if (props.taskbarPosition === 'left') {
    x = x + 50;
  } else if (props.taskbarPosition === 'right') {
    x = x - 50;
  }
  
  return { 
    left: `${x}px`, 
    top: `${y}px`
  };
});

// Methoden
const closeApp = () => {
  emit('close', props.app.id);
};

const closeAllWindows = () => {
  emit('close-all', props.app.id);
};

const minimizeApp = () => {
  emit('minimize', props.app.id);
};

const activateApp = () => {
  emit('activate', props.app.id, currentWindow.value?.id);
};

const restartApp = () => {
  emit('restart', props.app.id);
};

const selectWindow = (index: number) => {
  activeWindowIndex.value = index;
  if (props.app.windows && props.app.windows.length > index) {
    emit('select-window', props.app.id, props.app.windows[index].id);
  }
};

const resetCloseTimer = () => {
  // Automatisches Schließen nach kurzer Verzögerung, wenn nicht mehr gehovered
  if (closeTimer.value) {
    clearTimeout(closeTimer.value);
  }
  closeTimer.value = setTimeout(() => {
    if (!isDragging.value) {
      emit('close');
    }
  }, 300) as unknown as number;
};

const formatLastActivity = (timestamp: number): string => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  
  if (diffSeconds < 60) {
    return `vor ${diffSeconds} Sekunden`;
  } else if (diffMinutes < 60) {
    return `vor ${diffMinutes} Minuten`;
  } else if (diffHours < 24) {
    return `vor ${diffHours} Stunden`;
  } else {
    return date.toLocaleString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
};

const isImagePath = (icon: string): boolean => {
  if (!icon) return false;
  return icon.startsWith('http://') || 
         icon.startsWith('https://') || 
         icon.startsWith('/') ||
         icon.endsWith('.png') || 
         icon.endsWith('.jpg') || 
         icon.endsWith('.jpeg') || 
         icon.endsWith('.svg') || 
         icon.endsWith('.webp');
};

const getAppColor = (app: any): string => {
  return app.color || '#64748b';
};

// Lebenszyklusmethoden
onMounted(() => {
  // Beim Mounten der Komponente aktives Fenster auf erstes setzen
  activeWindowIndex.value = 0;
  
  // Position aus Props übernehmen
  previewPosition.value = { x: props.position.x, y: props.position.y };
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    // Position aktualisieren, wenn Komponente sichtbar wird
    previewPosition.value = { x: props.position.x, y: props.position.y };
    
    if (closeTimer.value) {
      clearTimeout(closeTimer.value);
      closeTimer.value = null;
    }
  }
});

watch(() => props.position, (newVal) => {
  // Position aktualisieren, wenn sich Props ändern
  previewPosition.value = { x: newVal.x, y: newVal.y };
});

onBeforeUnmount(() => {
  // Timer aufräumen beim Unmounten
  if (closeTimer.value) {
    clearTimeout(closeTimer.value);
  }
});
</script>

<style scoped>
.enhanced-app-preview {
  position: absolute;
  width: 350px;
  background: rgba(var(--desktop-bg-dark-1, 17, 24, 39), 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4), 0 0 12px rgba(0, 0, 0, 0.2);
  border: 1px solid var(--k-line);
  overflow: hidden;
  z-index: 1050;
  opacity: 0;
  transform: translateY(12px) scale(0.96);
  pointer-events: none;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: transform, opacity;
  user-select: none;
  display: flex;
  flex-direction: column;
}

.enhanced-app-preview.show {
  opacity: 1;
  transform: translateY(0) scale(1);
  pointer-events: all;
}

.enhanced-app-preview.multiple-windows {
  width: 420px;
}

.preview-container {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Header Styling */
.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  background: rgba(0, 0, 0, 0.15);
  border-bottom: 1px solid var(--k-line);
}

.app-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.app-icon-container {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.app-icon {
  width: 18px;
  height: 18px;
  object-fit: contain;
}

.app-title-container {
  display: flex;
  flex-direction: column;
}

.app-title {
  font-size: 14px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.app-subtitle {
  font-size: 11px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
  margin-top: -1px;
}

.preview-actions {
  display: flex;
  gap: 8px;
}

.action-button {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  background-color: var(--k-ink);
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
}

.action-button:hover {
  background-color: var(--k-row-hover);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.action-button.close:hover {
  background-color: rgba(239, 68, 68, 0.8);
  color: var(--k-ink);
}

/* Hauptinhalt Styling */
.preview-content {
  flex: 1;
  position: relative;
  overflow: hidden;
}

.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 0;
  gap: 12px;
  height: 200px;
}

.app-logo-container {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--k-row-hover);
  width: 80px;
  height: 80px;
  border-radius: 16px;
}

.app-logo {
  width: 56px;
  height: 56px;
  object-fit: contain;
}

.placeholder-message {
  font-size: 13px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
}

.active-window-preview {
  height: 200px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-screenshot {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-placeholder-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 16px;
}

/* Windows-Karussell Styling */
.windows-carousel {
  display: flex;
  overflow-x: auto;
  gap: 10px;
  padding: 16px;
  height: 150px;
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.windows-carousel::-webkit-scrollbar {
  display: none;
}

.window-preview-item {
  flex: 0 0 auto;
  width: 140px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.window-preview-item:hover .window-preview-container {
  border-color: var(--k-line);
  transform: translateY(-3px);
}

.window-preview-item.active .window-preview-container {
  border-color: var(--desktop-accent-blue, var(--k-accent));
  box-shadow: 0 0 0 2px rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.3);
}

.window-preview-container {
  height: 90px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--k-line);
  transition: all 0.2s ease;
}

.window-screenshot {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.window-thumbnail-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--k-row-hover);
  gap: 8px;
  padding: 8px;
}

.window-placeholder-title {
  font-size: 10px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.window-index-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  font-size: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--desktop-text, rgba(255, 255, 255, 0.9));
  border: 1px solid var(--k-line);
}

.window-preview-title {
  font-size: 11px;
  color: var(--desktop-text, rgba(255, 255, 255, 0.9));
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  padding: 0 2px;
}

.window-preview-subtitle {
  font-size: 10px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
  margin-top: 2px;
}

/* Footer Styling */
.preview-footer {
  padding: 12px 14px;
  background: rgba(0, 0, 0, 0.15);
  border-top: 1px solid var(--k-line);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.status-info {
  font-size: 11px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
}

.notification-indicator {
  color: var(--desktop-accent-blue, var(--k-accent));
  font-weight: 500;
}

.quick-actions {
  display: flex;
  gap: 8px;
}

.action-chip {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 4px;
  background: var(--k-row-hover);
  font-size: 11px;
  cursor: pointer;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  transition: all 0.15s ease;
}

.action-chip:hover {
  background: var(--k-row-hover);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

/* Multi-Window Navigation */
.multi-window-navigation {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
}

.window-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--k-row-hover);
  cursor: pointer;
  transition: all 0.2s ease;
}

.window-dot:hover {
  background: var(--k-row-hover);
}

.window-dot.active {
  background: var(--desktop-accent-blue, var(--k-accent));
  width: 10px;
  height: 10px;
}
</style> 