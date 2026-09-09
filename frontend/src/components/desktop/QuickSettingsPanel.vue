<!-- Schnelleinstellungen-Panel für den Desktop -->
<template>
  <div class="quick-settings-panel" :class="{ 'show': show }">
    <div class="quick-settings-header">
      <div class="title">{{ t('layout.quickSettings') }}</div>
      <div class="close-button" @click="$emit('close')">
        <v-icon size="small">mdi-close</v-icon>
      </div>
    </div>
    
    <div class="quick-settings-sections">
      <!-- System-Einstellungen -->
      <div class="settings-section">
        <div class="section-title">{{ t('layout.systemSection') }}</div>
        <div class="settings-grid">
          <!-- Lautstärke-Einstellung -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">{{ volume > 50 ? 'mdi-volume-high' : volume > 0 ? 'mdi-volume-medium' : 'mdi-volume-off' }}</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.volume') }}</div>
              <div class="item-control">
                <input type="range" min="0" max="100" v-model="volume" class="slider" />
                <div class="value">{{ volume }}%</div>
              </div>
            </div>
          </div>
          
          <!-- Helligkeit-Einstellung -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">mdi-brightness-6</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.brightness') }}</div>
              <div class="item-control">
                <input type="range" min="0" max="100" v-model="brightness" class="slider" />
                <div class="value">{{ brightness }}%</div>
              </div>
            </div>
          </div>
          
          <!-- WLAN-Einstellung -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">{{ wifi ? 'mdi-wifi' : 'mdi-wifi-off' }}</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.wifi') }}</div>
              <div class="item-control">
                <div class="toggle" :class="{ 'active': wifi }" @click="wifi = !wifi">
                  <div class="toggle-button"></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Bluetooth-Einstellung -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">{{ bluetooth ? 'mdi-bluetooth' : 'mdi-bluetooth-off' }}</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.bluetooth') }}</div>
              <div class="item-control">
                <div class="toggle" :class="{ 'active': bluetooth }" @click="bluetooth = !bluetooth">
                  <div class="toggle-button"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Anzeige-Einstellungen -->
      <div class="settings-section">
        <div class="section-title">{{ t('layout.displaySection') }}</div>
        <div class="settings-grid">
          <!-- Taskbar-Position -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">mdi-monitor-dashboard</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.taskbarPosition') }}</div>
              <div class="item-control position-buttons">
                <button 
                  @click="changePosition('bottom')" 
                  :class="{ 'active': taskbarPosition === 'bottom' }"
                >{{ t('layout.positionBottom') }}</button>
                <button 
                  @click="changePosition('left')" 
                  :class="{ 'active': taskbarPosition === 'left' }"
                >{{ t('layout.positionLeft') }}</button>
                <button 
                  @click="changePosition('right')" 
                  :class="{ 'active': taskbarPosition === 'right' }"
                >{{ t('layout.positionRight') }}</button>
                <button 
                  @click="changePosition('top')" 
                  :class="{ 'active': taskbarPosition === 'top' }"
                >{{ t('layout.positionTop') }}</button>
              </div>
            </div>
          </div>
          
          <!-- Auto-Hide Taskbar -->
          <div class="settings-item">
            <div class="item-icon">
              <v-icon size="24">mdi-arrow-collapse-down</v-icon>
            </div>
            <div class="item-content">
              <div class="item-label">{{ t('layout.taskbarHide') }}</div>
              <div class="item-control">
                <div class="toggle" :class="{ 'active': autoHideTaskbar }" @click="toggleAutoHide">
                  <div class="toggle-button"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Schnellzugriff -->
      <div class="settings-section">
        <div class="section-title">{{ t('layout.quickAccessSection') }}</div>
        <div class="quick-access-grid">
          <div 
            v-for="app in quickAccessApps" 
            :key="app.id" 
            class="quick-access-app"
            @click="openApp(app)"
          >
            <div class="app-icon">
              <v-icon size="28" :color="app.color">{{ app.icon }}</v-icon>
            </div>
            <div class="app-name">{{ app.title }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  show?: boolean
  taskbarPosition?: string
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  taskbarPosition: 'bottom'
});

const emit = defineEmits(['close', 'position-change', 'auto-hide-change', 'open-app']);
const { t } = useI18n();

// Einstellungswerte
const volume = ref(75);
const brightness = ref(80);
const wifi = ref(true);
const bluetooth = ref(false);
const autoHideTaskbar = ref(props.autoHide);

// Taskbar-Position ändern
const changePosition = (position: string) => {
  emit('position-change', position);
};

// Auto-Hide umschalten
const toggleAutoHide = () => {
  autoHideTaskbar.value = !autoHideTaskbar.value;
  emit('auto-hide-change', autoHideTaskbar.value);
};

// App öffnen
const openApp = (app: any) => {
  emit('open-app', app);
  emit('close');
};

// Schnellzugriff-Apps
const quickAccessApps = [
  { id: 'settings', title: t('settings'), icon: 'mdi-cog', color: '#64748b' },
  { id: 'calendar', title: t('calendar'), icon: 'mdi-calendar', color: '#ef4444' },
  { id: 'messages', title: t('messages'), icon: 'mdi-email', color: '#f59e0b' },
  { id: 'files', title: t('fileManager'), icon: 'mdi-folder', color: '#8b5cf6' },
  { id: 'weather', title: t('weather'), icon: 'mdi-weather-partly-cloudy', color: '#0ea5e9' },
  { id: 'reports', title: t('reports'), icon: 'mdi-file-document', color: '#ec4899' }
];
</script>

<style scoped>
.quick-settings-panel {
  position: absolute;
  bottom: calc(var(--taskbar-height) + 15px);
  right: 15px;
  width: 380px;
  background: rgba(var(--desktop-bg-dark-1, 17, 24, 39), 0.85);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--k-line);
  padding: 16px;
  transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
  transform: translateY(20px);
  opacity: 0;
  pointer-events: none;
  z-index: 1100;
}

.quick-settings-panel.show {
  transform: translateY(0);
  opacity: 1;
  pointer-events: all;
}

/* Taskbar-Position abhängige Positionierung */
.quick-settings-panel.top {
  bottom: auto;
  top: calc(var(--taskbar-height) + 15px);
}

.quick-settings-panel.left {
  right: auto;
  left: calc(var(--taskbar-height) + 15px);
  bottom: auto;
  top: 80px;
}

.quick-settings-panel.right {
  right: calc(var(--taskbar-height) + 15px);
  bottom: auto;
  top: 80px;
}

.quick-settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.title {
  font-size: 18px;
  font-weight: 600;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.close-button {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.close-button:hover {
  background-color: var(--k-ink);
}

.settings-section {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--k-line);
}

.settings-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.settings-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px;
  border-radius: 12px;
  background-color: var(--k-row-hover);
  transition: background-color 0.2s ease;
}

.settings-item:hover {
  background-color: var(--k-row-hover);
}

.item-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: linear-gradient(135deg, 
    rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15),
    rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.05));
  color: var(--desktop-accent-blue, var(--k-accent));
}

.item-content {
  flex: 1;
}

.item-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
  margin-bottom: 6px;
}

.item-control {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Slider-Styling */
.slider {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--k-row-hover);
  border-radius: 2px;
  outline: none;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--desktop-accent-blue, var(--k-accent));
  cursor: pointer;
  transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 0 8px rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.5);
}

.value {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  width: 36px;
  text-align: right;
}

/* Toggle-Switch Styling */
.toggle {
  position: relative;
  width: 42px;
  height: 22px;
  background-color: var(--k-row-hover);
  border-radius: 11px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.toggle.active {
  background-color: var(--desktop-accent-blue, var(--k-accent));
}

.toggle-button {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 16px;
  height: 16px;
  background-color: var(--k-ink);
  border-radius: 50%;
  transition: all 0.3s ease;
}

.toggle.active .toggle-button {
  left: calc(100% - 19px);
}

/* Position-Buttons */
.position-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}

.position-buttons button {
  padding: 6px;
  border-radius: 6px;
  background-color: var(--k-ink);
  border: none;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.position-buttons button:hover {
  background-color: var(--k-row-hover);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.position-buttons button.active {
  background-color: var(--desktop-accent-blue, var(--k-accent));
  color: var(--k-ink);
}

/* Schnellzugriff-Apps */
.quick-access-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.quick-access-app {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  border-radius: 12px;
  background-color: var(--k-row-hover);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
}

.quick-access-app:hover {
  background-color: var(--k-ink);
  transform: translateY(-3px);
}

.app-icon {
  margin-bottom: 8px;
  height: 42px;
  width: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: var(--k-row-hover);
}

.app-name {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  text-align: center;
}
</style> 