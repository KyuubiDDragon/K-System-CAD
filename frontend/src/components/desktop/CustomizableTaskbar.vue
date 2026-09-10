<!-- Anpassbare Taskleiste mit erweiterten Funktionen -->
<template>
  <div 
    class="taskbar-customizer"
    :class="{ 'show': show }"
  >
    <div class="customizer-header">
      <div class="customizer-title">{{ t('layout.customizerTitle') }}</div>
      <div class="customizer-close" @click="$emit('close')">
        <v-icon size="small">mdi-close</v-icon>
      </div>
    </div>
    
    <div class="customizer-content">
      <!-- Positionen -->
      <div class="customizer-section">
        <div class="section-header">
          <v-icon size="18" class="section-icon">mdi-monitor-dashboard</v-icon>
          <span>{{ t('layout.position') }}</span>
        </div>
        <div class="position-controls">
          <div 
            v-for="pos in positions" 
            :key="pos.value"
            class="position-option"
            :class="{ 'active': taskbarPosition === pos.value }"
            @click="changePosition(pos.value)"
          >
            <div class="position-icon">
              <v-icon size="18">{{ pos.icon }}</v-icon>
            </div>
            <span class="position-label">{{ pos.label }}</span>
          </div>
        </div>
      </div>
      
      <!-- Größe und Verhalten -->
      <div class="customizer-section">
        <div class="section-header">
          <v-icon size="18" class="section-icon">mdi-tune-vertical</v-icon>
          <span>{{ t('layout.appearanceBehavior') }}</span>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.taskbarSize') }}</span>
          </div>
          <div class="size-controls">
            <div 
              v-for="size in sizes" 
              :key="size.value"
              class="size-option"
              :class="{ 'active': taskbarSize === size.value }"
              @click="changeSize(size.value)"
            >
              <span>{{ size.label }}</span>
            </div>
          </div>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.autoHide') }}</span>
            <div class="setting-description">{{ t('layout.autoHideDescription') }}</div>
          </div>
          <div class="setting-control">
            <div 
              class="toggle-switch" 
              :class="{ 'active': autoHide }"
              @click="toggleAutoHide"
            >
              <div class="toggle-slider"></div>
            </div>
          </div>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.transparency') }}</span>
          </div>
          <div class="setting-control slider-control">
            <input
              type="range"
              min="0"
              max="100"
              step="5"
              v-model="transparency"
              class="range-slider"
              @input="updateTransparency"
            />
            <span class="slider-value">{{ transparency }}%</span>
          </div>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.blurEffect') }}</span>
          </div>
          <div class="setting-control slider-control">
            <input
              type="range"
              min="0"
              max="20"
              v-model="blurEffect"
              class="range-slider"
              @input="updateBlurEffect"
            />
            <span class="slider-value">{{ blurEffect }}px</span>
          </div>
        </div>
      </div>
      
      <!-- App-Anordnung -->
      <div class="customizer-section">
        <div class="section-header">
          <v-icon size="18" class="section-icon">mdi-view-grid-plus</v-icon>
          <span>{{ t('layout.appArrangement') }}</span>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.groupApps') }}</span>
            <div class="setting-description">{{ t('layout.groupAppsDescription') }}</div>
          </div>
          <div class="setting-control">
            <div 
              class="toggle-switch" 
              :class="{ 'active': groupApps }"
              @click="toggleGroupApps"
            >
              <div class="toggle-slider"></div>
            </div>
          </div>
        </div>
        
        <div class="setting-item">
          <div class="setting-label">
            <span>{{ t('layout.iconStyle') }}</span>
          </div>
          <div class="setting-control icon-style-control">
            <div 
              v-for="style in iconStyles" 
              :key="style.value"
              class="icon-style-option"
              :class="{ 'active': iconStyle === style.value }"
              @click="changeIconStyle(style.value)"
            >
              <div class="icon-preview" :class="style.value">
                <v-icon size="16">{{ style.icon }}</v-icon>
              </div>
              <span class="icon-style-label">{{ style.label }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Festgepinnte Apps -->
      <div class="customizer-section">
        <div class="section-header">
          <v-icon size="18" class="section-icon">mdi-pin</v-icon>
          <span>{{ t('layout.pinnedApps') }}</span>
        </div>
        
        <div class="pinned-apps-info">
          {{ t('layout.pinnedAppsInfo') }}
        </div>
        
        <div class="pinned-apps-list">
          <draggable
            v-model="pinnedApps"
            item-key="id"
            :animation="200"
            handle=".app-drag-handle"
            ghost-class="ghost-app"
            @end="savePinnedOrder"
          >
            <template #item="{ element }">
              <div class="pinned-app-item">
                <div class="app-drag-handle">
                  <v-icon size="16" color="grey">mdi-drag</v-icon>
                </div>
                <div class="app-info">
                  <div class="app-icon" :style="{ backgroundColor: element.color + '20' }">
                    <v-icon size="18" :color="element.color">{{ element.icon }}</v-icon>
                  </div>
                  <span class="app-name">{{ element.title }}</span>
                </div>
                <div class="app-remove" @click="removePin(element)">
                  <v-icon size="16" color="grey">mdi-close</v-icon>
                </div>
              </div>
            </template>
          </draggable>
        </div>
      </div>
      
      <!-- Aktionen -->
      <div class="customizer-actions">
        <button class="action-button cancel" @click="$emit('close')">{{ t('layout.cancel') }}</button>
        <button class="action-button apply" @click="applyChanges">{{ t('layout.applyChanges') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';

// Props from parent
interface Props {
  show?: boolean
  currentPosition?: string
  currentSize?: string
  currentAutoHide?: boolean
  currentTransparency?: number
  currentBlur?: number
  currentGroupApps?: boolean
  currentIconStyle?: string
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  currentPosition: 'bottom',
  currentSize: 'medium',
  currentAutoHide: false,
  currentTransparency: 30,
  currentBlur: 10,
  currentGroupApps: true,
  currentIconStyle: 'filled'
});

// Emit events
const emit = defineEmits([
  'close', 
  'update:position', 
  'update:size', 
  'update:auto-hide',
  'update:transparency',
  'update:blur',
  'update:group-apps',
  'update:icon-style',
  'update:pinned-apps',
  'apply-settings'
]);
const { t } = useI18n();

// Local state
const taskbarPosition = ref(props.currentPosition);
const taskbarSize = ref(props.currentSize);
const autoHide = ref(props.currentAutoHide);
const transparency = ref(props.currentTransparency);
const blurEffect = ref(props.currentBlur);
const groupApps = ref(props.currentGroupApps);
const iconStyle = ref(props.currentIconStyle);
const pinnedApps = ref(props.currentPinnedApps);

// Position options
const positions = [
  { value: 'bottom', label: t('layout.positionBottom'), icon: 'mdi-dock-bottom' },
  { value: 'left', label: t('layout.positionLeft'), icon: 'mdi-dock-left' },
  { value: 'right', label: t('layout.positionRight'), icon: 'mdi-dock-right' },
  { value: 'top', label: t('layout.positionTop'), icon: 'mdi-dock-top' }
];

// Size options
const sizes = [
  { value: 'small', label: t('layout.sizeSmall') },
  { value: 'medium', label: t('layout.sizeMedium') },
  { value: 'large', label: t('layout.sizeLarge') }
];

// Icon style options
const iconStyles = [
  { value: 'filled', label: t('layout.iconFilled'), icon: 'mdi-circle' },
  { value: 'outlined', label: t('layout.iconOutlined'), icon: 'mdi-circle-outline' },
  { value: 'tonal', label: t('layout.iconTonal'), icon: 'mdi-circle-half-full' }
];

// Methods for updating settings
const changePosition = (pos: string) => {
  taskbarPosition.value = pos;
};

const changeSize = (size: string) => {
  taskbarSize.value = size;
};

const toggleAutoHide = () => {
  autoHide.value = !autoHide.value;
};

const updateTransparency = () => {
  // Local update only - will be emitted on apply
};

const updateBlurEffect = () => {
  // Local update only - will be emitted on apply
};

const toggleGroupApps = () => {
  groupApps.value = !groupApps.value;
};

const changeIconStyle = (style: string) => {
  iconStyle.value = style;
};

const removePin = (app: any) => {
  pinnedApps.value = pinnedApps.value.filter((a: any) => a.id !== app.id);
};

const savePinnedOrder = () => {
  // Just updating the local state, will be emitted on apply
};

// Apply all changes at once
const applyChanges = () => {
  emit('update:position', taskbarPosition.value);
  emit('update:size', taskbarSize.value);
  emit('update:auto-hide', autoHide.value);
  emit('update:transparency', transparency.value);
  emit('update:blur', blurEffect.value);
  emit('update:group-apps', groupApps.value);
  emit('update:icon-style', iconStyle.value);
  emit('update:pinned-apps', pinnedApps.value);
  emit('apply-settings');
  emit('close');
};

// Reset to current values when dialog shows
watch(() => props.show, (newVal) => {
  if (newVal) {
    taskbarPosition.value = props.currentPosition;
    taskbarSize.value = props.currentSize;
    autoHide.value = props.currentAutoHide;
    transparency.value = props.currentTransparency;
    blurEffect.value = props.currentBlur;
    groupApps.value = props.currentGroupApps;
    iconStyle.value = props.currentIconStyle;
    pinnedApps.value = JSON.parse(JSON.stringify(props.currentPinnedApps));
  }
});
</script>

<style scoped>
.taskbar-customizer {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.95);
  width: 500px;
  max-width: 95vw;
  max-height: 90vh;
  background: rgba(var(--desktop-bg-dark-1, 17, 24, 39), 0.95);
  border-radius: 6px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--k-line);
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.taskbar-customizer.show {
  opacity: 1;
  pointer-events: all;
  transform: translate(-50%, -50%) scale(1);
}

.customizer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid var(--k-line);
}

.customizer-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.customizer-close {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 50%;
  transition: background 0.2s ease;
}

.customizer-close:hover {
  background: var(--k-row-hover);
}

.customizer-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 16px;
  overflow-y: auto;
  max-height: calc(90vh - 60px);
}

/* Scrollbar styling */
.customizer-content::-webkit-scrollbar {
  width: 6px;
}

.customizer-content::-webkit-scrollbar-track {
  background: transparent;
}

.customizer-content::-webkit-scrollbar-thumb {
  background-color: var(--k-row-hover);
  border-radius: 3px;
}

.customizer-content::-webkit-scrollbar-thumb:hover {
  background-color: var(--k-row-hover);
}

.customizer-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--k-line);
}

.customizer-section:last-child {
  border-bottom: none;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
  font-size: 14px;
  font-weight: 600;
}

.section-icon {
  color: var(--desktop-accent-blue, var(--k-accent));
}

/* Position controls */
.position-controls {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.position-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 8px;
  border-radius: 8px;
  background: var(--k-row-hover);
  cursor: pointer;
  transition: all 0.2s ease;
}

.position-option:hover {
  background: var(--k-row-hover);
}

.position-option.active {
  background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.15);
}

.position-icon {
  background: var(--k-row-hover);
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.position-option.active .position-icon {
  background-color: var(--desktop-accent-blue, var(--k-accent));
  color: var(--k-ink);
}

.position-label {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
}

/* Setting items */
.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 12px;
  border-radius: 8px;
  background: var(--k-row-hover);
}

.setting-label {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.setting-label > span {
  font-size: 13px;
  font-weight: 500;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.setting-description {
  font-size: 11px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.6));
  max-width: 250px;
}

.setting-control {
  display: flex;
  align-items: center;
}

/* Toggle switch styling */
.toggle-switch {
  position: relative;
  width: 44px;
  height: 24px;
  background-color: var(--k-row-hover);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.toggle-switch.active {
  background-color: var(--desktop-accent-blue, var(--k-accent));
}

.toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background-color: #fff;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.toggle-switch.active .toggle-slider {
  left: calc(100% - 22px);
}

/* Size controls */
.size-controls {
  display: flex;
  gap: 8px;
}

.size-option {
  padding: 6px 12px;
  border-radius: 6px;
  background: var(--k-row-hover);
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  cursor: pointer;
  transition: all 0.2s ease;
}

.size-option:hover {
  background: var(--k-row-hover);
}

.size-option.active {
  background-color: var(--desktop-accent-blue, var(--k-accent));
  color: var(--k-ink);
}

/* Slider control */
.slider-control {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 180px;
}

.range-slider {
  flex: 1;
  -webkit-appearance: none;
  height: 4px;
  border-radius: 2px;
  background: var(--k-row-hover);
  outline: none;
}

.range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--desktop-accent-blue, var(--k-accent));
  cursor: pointer;
  transition: all 0.2s ease;
}

.range-slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
}

.slider-value {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  width: 40px;
  text-align: right;
}

/* Icon style control */
.icon-style-control {
  display: flex;
  gap: 10px;
}

.icon-style-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.icon-preview {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: var(--k-row-hover);
  transition: all 0.2s ease;
}

.icon-preview:hover {
  background: var(--k-row-hover);
}

.icon-style-option.active .icon-preview {
  background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.3);
  color: var(--desktop-accent-blue, var(--k-accent));
}

.icon-style-label {
  font-size: 11px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
}

/* Pinned apps */
.pinned-apps-info {
  font-size: 12px;
  color: var(--desktop-text-secondary, rgba(255, 255, 255, 0.7));
  padding: 8px 12px;
  background: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.1);
  border-radius: 6px;
  border-left: 3px solid var(--desktop-accent-blue, var(--k-accent));
}

.pinned-apps-list {
  max-height: 200px;
  overflow-y: auto;
  margin-top: 8px;
}

.pinned-app-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: var(--k-row-hover);
  border-radius: 8px;
  margin-bottom: 8px;
  transition: all 0.2s ease;
}

.pinned-app-item:hover {
  background: var(--k-row-hover);
}

.app-drag-handle {
  cursor: grab;
  padding: 4px;
}

.app-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.app-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.app-name {
  font-size: 13px;
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.app-remove {
  padding: 6px;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.app-remove:hover {
  background: var(--k-row-hover);
  color: #ef4444;
}

/* Ghost class for dragging */
.ghost-app {
  opacity: 0.5;
  background: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.2) !important;
}

/* Action buttons */
.customizer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 8px;
}

.action-button {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.action-button.cancel {
  background: var(--k-row-hover);
  color: var(--desktop-text, rgba(255, 255, 255, 0.95));
}

.action-button.cancel:hover {
  background: var(--k-row-hover);
}

.action-button.apply {
  background-color: var(--desktop-accent-blue, var(--k-accent));
  color: var(--k-ink);
}

.action-button.apply:hover {
  background-color: rgba(var(--desktop-accent-blue-rgb, 59, 130, 246), 0.9);
}
</style> 