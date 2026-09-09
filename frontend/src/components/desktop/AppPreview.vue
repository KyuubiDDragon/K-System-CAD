<!-- App-Vorschau Komponente für Taskbar Hover -->
<template>
  <div 
    class="app-preview" 
    :class="{ 'show': show }" 
    :style="{ left: `${position.x}px`, top: `${position.y}px` }"
  >
    <!-- Header mit Titel und Actions -->
    <div class="preview-header">
      <div class="app-title">
        <template v-if="app.icon && isImagePath(app.icon)">
          <img :src="app.icon" class="app-icon" :alt="app.title">
        </template>
        <template v-else-if="app.icon">
          <v-icon size="16" :color="app.color || 'white'">{{ app.icon }}</v-icon>
        </template>
        <span class="title-text">{{ app.title }}</span>
      </div>
      <div class="preview-actions">
        <div class="action-button minimize" @click="minimizeApp(app.id)">
          <v-icon size="14">mdi-minus</v-icon>
        </div>
        <div class="action-button close" @click="closeApp(app.id)">
          <v-icon size="14">mdi-close</v-icon>
        </div>
      </div>
    </div>

    <!-- Vorschau-Inhalt -->
    <div class="preview-content">
      <!-- Fallback wenn kein Screenshot vorhanden ist -->
      <div v-if="!app.screenshot" class="preview-placeholder">
        <template v-if="app.icon && isImagePath(app.icon)">
          <img :src="app.icon" class="app-logo" :alt="app.title">
        </template>
        <template v-else-if="app.icon">
          <v-icon size="40" :color="app.color || 'white'">{{ app.icon }}</v-icon>
        </template>
        <div class="placeholder-text">{{ t('desktop.appPreview') }}</div>
      </div>
      
      <!-- Screenshot wenn vorhanden -->
      <img 
        v-else 
        :src="app.screenshot" 
        class="preview-screenshot" 
        :alt="`${app.title} preview`"
      >
    </div>

    <!-- Footer mit Aktionen -->
    <div class="preview-footer">
      <div class="last-activity" v-if="app.lastActivity">
        {{ t('desktop.lastActivity') }}: {{ formatLastActivity(app.lastActivity) }}
      </div>
      <div class="footer-actions">
        <div class="footer-button" @click="activateApp(app.id)">
          <v-icon size="14">mdi-arrow-up</v-icon>
          <span>{{ t('desktop.bringToFront') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
;
import { useI18n } from 'vue-i18n';

interface Props {
  app?: Record<string, any>
  show?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  show: false
});

const emit = defineEmits(['close', 'minimize', 'activate']);
const { t } = useI18n();

// App schließen
const closeApp = (appId: string) => {
  emit('close', appId);
};

// App minimieren
const minimizeApp = (appId: string) => {
  emit('minimize', appId);
};

// App aktivieren/in den Vordergrund bringen
const activateApp = (appId: string) => {
  emit('activate', appId);
};

// Letzten Aktivitätszeitpunkt formatieren
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

// Prüfen ob ein Icon ein Bildpfad ist
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
</script>

<style scoped>
.app-preview {
  position: absolute;
  width: 320px;
  background: rgba(var(--desktop-bg-dark-1, 17, 24, 39), 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
  border: 1px solid var(--k-line);
  overflow: hidden;
  z-index: 1050;
  opacity: 0;
  transform: translateY(10px) scale(0.95);
  pointer-events: none;
  transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
}

.app-preview.show {
  opacity: 1;
  transform: translateY(0) scale(1);
  pointer-events: all;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: rgba(0, 0, 0, 0.1);
  border-bottom: 1px solid var(--k-line);
}

.app-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.app-icon {
  width: 16px;
  height: 16px;
  object-fit: contain;
}

.title-text {
  font-size: 13px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.preview-actions {
  display: flex;
  gap: 8px;
}

.action-button {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: var(--k-ink);
}

.action-button:hover {
  background-color: var(--k-row-hover);
}

.action-button.close:hover {
  background-color: rgba(239, 68, 68, 0.8);
}

.preview-content {
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  opacity: 0.7;
}

.app-logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
}

.placeholder-text {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
}

.preview-screenshot {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-footer {
  padding: 10px 12px;
  background: rgba(0, 0, 0, 0.1);
  border-top: 1px solid var(--k-line);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.last-activity {
  font-size: 11px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
}

.footer-button {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 10px;
  border-radius: 6px;
  background-color: var(--k-ink);
  font-size: 11px;
  cursor: pointer;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  transition: all 0.2s ease;
}

.footer-button:hover {
  background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}
</style> 