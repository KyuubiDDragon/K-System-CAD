<template>
  <div
    ref="widgetEl"
    class="widget-container"
    :class="{
      'is-dragging': isDragging,
      'is-resizing': isResizing,
      'is-minimized': isMinimized,
      'is-focused': isFocused
    }"
    :style="widgetStyle"
    @mousedown="focus"
  >
    <!-- Widget Header -->
    <div 
      class="widget-header"
      @mousedown="startDrag"
      @dblclick="toggleMinimize"
    >
      <div class="widget-header-content">
        <v-icon size="18" :color="color" class="widget-icon">{{ icon }}</v-icon>
        <span class="widget-title">{{ title }}</span>
      </div>
      <div class="widget-controls">
        <v-btn
          icon
          size="x-small"
          variant="text"
          @click.stop="toggleMinimize"
          class="widget-control"
        >
          <v-icon size="14">{{ isMinimized ? 'mdi-window-maximize' : 'mdi-window-minimize' }}</v-icon>
        </v-btn>
        <v-btn
          icon
          size="x-small"
          variant="text"
          @click.stop="$emit('close')"
          class="widget-control"
        >
          <v-icon size="14">mdi-close</v-icon>
        </v-btn>
      </div>
    </div>

    <!-- Widget Content -->
    <div v-show="!isMinimized" class="widget-content">
      <slot></slot>
    </div>

    <!-- Resize Handle -->
    <div
      v-show="!isMinimized"
      class="widget-resize-handle"
      @mousedown.stop="startResize"
    >
      <v-icon size="12">mdi-resize-bottom-right</v-icon>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

interface WidgetPosition {
  x: number;
  y: number;
  width: number;
  height: number;
}

interface Props {
  id: string;
  title: string;
  icon: string;
  color?: string;
  position?: WidgetPosition;
  minWidth?: number;
  minHeight?: number;
  maxWidth?: number | (() => number);
  maxHeight?: number | (() => number);
  canResize?: boolean;
  canMove?: boolean;
  zIndex?: number;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'primary',
  position: () => ({ x: 100, y: 100, width: 300, height: 200 }),
  minWidth: 200,
  minHeight: 150,
  maxWidth: () => Math.min(600, window.innerWidth - 120),
  maxHeight: () => Math.min(800, window.innerHeight - 100),
  canResize: true,
  canMove: true,
  zIndex: 1000
});

const emit = defineEmits<{
  'update:position': [position: WidgetPosition];
  'close': [];
  'focus': [];
}>();

// Refs
const widgetEl = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const isResizing = ref(false);
const isMinimized = ref(false);
const isFocused = ref(false);

// Position state
const currentPosition = ref<WidgetPosition>({ ...props.position });

// Drag state
let dragStartX = 0;
let dragStartY = 0;
let dragOffsetX = 0;
let dragOffsetY = 0;

// Resize state
let resizeStartX = 0;
let resizeStartY = 0;
let resizeStartWidth = 0;
let resizeStartHeight = 0;

// Computed styles
const widgetStyle = computed(() => ({
  right: `${currentPosition.value.x}px`,
  top: `${currentPosition.value.y}px`,
  width: `${currentPosition.value.width}px`,
  height: isMinimized.value ? 'auto' : `${currentPosition.value.height}px`,
  zIndex: props.zIndex,
  position: 'fixed'
}));

// Methods
const focus = () => {
  isFocused.value = true;
  emit('focus');
};

const blur = () => {
  isFocused.value = false;
};

const toggleMinimize = () => {
  isMinimized.value = !isMinimized.value;
};

const startDrag = (e: MouseEvent) => {
  if (!props.canMove || e.button !== 0) return;
  
  e.preventDefault();
  isDragging.value = true;
  
  const rect = widgetEl.value!.getBoundingClientRect();
  dragStartX = e.clientX;
  dragStartY = e.clientY;
  dragOffsetX = e.clientX - rect.left;
  dragOffsetY = e.clientY - rect.top;
  
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
};

const onDrag = (e: MouseEvent) => {
  if (!isDragging.value) return;
  
  const deltaX = e.clientX - dragStartX;
  const deltaY = e.clientY - dragStartY;
  
  // For right-positioned widgets, deltaX moves in opposite direction
  const newX = currentPosition.value.x - deltaX;
  const newY = currentPosition.value.y + deltaY;
  
  // Boundary checking - constrain to right-side area
  const screenWidth = window.innerWidth;
  const screenHeight = window.innerHeight;
  const padding = 20;
  const maxRightPosition = 20; // Maximum distance from right screen edge (minimum right value)
  
  const minX = maxRightPosition; // Minimum distance from right edge
  const maxX = screenWidth - currentPosition.value.width - padding; // Maximum right position
  const maxY = screenHeight - currentPosition.value.height;
  
  currentPosition.value.x = Math.max(minX, Math.min(newX, maxX));
  currentPosition.value.y = Math.max(padding, Math.min(newY, maxY));
  
  dragStartX = e.clientX;
  dragStartY = e.clientY;
};

const stopDrag = () => {
  if (!isDragging.value) return;
  
  isDragging.value = false;
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
  
  emit('update:position', { ...currentPosition.value });
};

const startResize = (e: MouseEvent) => {
  if (!props.canResize || e.button !== 0) return;
  
  e.preventDefault();
  e.stopPropagation();
  isResizing.value = true;
  
  resizeStartX = e.clientX;
  resizeStartY = e.clientY;
  resizeStartWidth = currentPosition.value.width;
  resizeStartHeight = currentPosition.value.height;
  
  document.addEventListener('mousemove', onResize);
  document.addEventListener('mouseup', stopResize);
};

const onResize = (e: MouseEvent) => {
  if (!isResizing.value) return;
  
  const deltaX = e.clientX - resizeStartX;
  const deltaY = e.clientY - resizeStartY;
  
  const newWidth = resizeStartWidth + deltaX;
  const newHeight = resizeStartHeight + deltaY;
  
  // Dynamic max width/height based on screen size
  const maxWidth = typeof props.maxWidth === 'function' ? props.maxWidth() : props.maxWidth;
  const maxHeight = typeof props.maxHeight === 'function' ? props.maxHeight() : props.maxHeight;
  
  currentPosition.value.width = Math.max(props.minWidth, Math.min(newWidth, maxWidth));
  currentPosition.value.height = Math.max(props.minHeight, Math.min(newHeight, maxHeight));
};

const stopResize = () => {
  if (!isResizing.value) return;
  
  isResizing.value = false;
  document.removeEventListener('mousemove', onResize);
  document.removeEventListener('mouseup', stopResize);
  
  emit('update:position', { ...currentPosition.value });
};

// Handle click outside
const handleClickOutside = (e: MouseEvent) => {
  if (widgetEl.value && !widgetEl.value.contains(e.target as Node)) {
    blur();
  }
};

// Watch for external position changes
watch(() => props.position, (newPosition) => {
  currentPosition.value = { ...newPosition };
  // Check position after update
  setTimeout(() => constrainToScreen(), 50);
}, { deep: true });

// Fix widget position if it goes off screen
const constrainToScreen = () => {
  const screenWidth = window.innerWidth;
  const screenHeight = window.innerHeight;
  const padding = 20; // Padding from screen edges
  const maxRightPosition = 20; // Maximum distance from right screen edge (minimum right value)
  
  let needsUpdate = false;
  const newPosition = { ...currentPosition.value };
  
  // Constrain widget width reasonably
  const maxReasonableWidth = 400;
  if (newPosition.width > maxReasonableWidth) {
    console.log(`📏 Constraining widget width: ${newPosition.width} → ${maxReasonableWidth}`);
    newPosition.width = Math.max(props.minWidth, maxReasonableWidth);
    needsUpdate = true;
  }
  
  // Force widgets to stay within right-side area
  const minX = maxRightPosition; // Minimum distance from right edge
  const maxX = screenWidth - newPosition.width - padding; // Maximum right position (widget can't go too far left)
  const minY = padding;
  const maxY = screenHeight - newPosition.height - padding;
  
  // Horizontal constraint - force into right-side area
  if (newPosition.x < minX) {
    console.log(`🔧 Moving widget to min right position: x ${newPosition.x} → ${minX}`);
    newPosition.x = minX;
    needsUpdate = true;
  } else if (newPosition.x > maxX) {
    console.log(`🔧 Moving widget closer to right edge: x ${newPosition.x} → ${maxX}`);
    newPosition.x = maxX;
    needsUpdate = true;
  }
  
  // Vertical constraint
  if (newPosition.y < minY) {
    newPosition.y = minY;
    needsUpdate = true;
  } else if (newPosition.y > maxY) {
    newPosition.y = Math.max(minY, maxY);
    needsUpdate = true;
  }
  
  if (needsUpdate) {
    console.log(`📐 Constraining widget ${props.id} to right area:`, 
      `pos(${currentPosition.value.x},${currentPosition.value.y}) size(${currentPosition.value.width}x${currentPosition.value.height})`,
      `→ pos(${newPosition.x},${newPosition.y}) size(${newPosition.width}x${newPosition.height})`);
    currentPosition.value = newPosition;
    emit('update:position', newPosition);
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
  
  // Fix position on mount if needed
  constrainToScreen();
  
  // Also fix on window resize
  window.addEventListener('resize', constrainToScreen);
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
  document.removeEventListener('mousemove', onResize);
  document.removeEventListener('mouseup', stopResize);
  window.removeEventListener('resize', constrainToScreen);
});

// Expose methods
defineExpose({
  focus,
  blur,
  toggleMinimize
});
</script>

<style scoped lang="scss">
.widget-container {
  position: fixed;
  background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(51, 65, 85, 0.9));
  backdrop-filter: blur(25px) saturate(180%);
  -webkit-backdrop-filter: blur(25px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 20px;
  box-shadow:
    0 12px 40px rgba(0, 0, 0, 0.3),
    0 6px 16px rgba(0, 0, 0, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  &.is-dragging {
    cursor: move;
    box-shadow:
      0 20px 60px rgba(0, 0, 0, 0.4),
      0 10px 24px rgba(0, 0, 0, 0.3),
      inset 0 1px 0 rgba(255, 255, 255, 0.15);
    opacity: 0.95;
    transform: scale(1.02);
  }

  &.is-resizing {
    cursor: nwse-resize;
  }

  &.is-minimized {
    height: auto;
  }

  &.is-focused {
    box-shadow:
      0 16px 50px rgba(0, 0, 0, 0.35),
      0 8px 20px rgba(0, 0, 0, 0.25),
      0 0 0 2px rgba(59, 130, 246, 0.3),
      inset 0 1px 0 rgba(255, 255, 255, 0.15);
    border-color: rgba(59, 130, 246, 0.4);
  }

  &:hover {
    box-shadow:
      0 14px 45px rgba(0, 0, 0, 0.32),
      0 7px 18px rgba(0, 0, 0, 0.22),
      inset 0 1px 0 rgba(255, 255, 255, 0.12);
    transform: translateY(-2px);
  }
}

.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background: linear-gradient(135deg, rgba(51, 65, 85, 0.5), rgba(71, 85, 105, 0.4));
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  cursor: move;
  user-select: none;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);

  &:active {
    cursor: grabbing;
  }

  &:hover {
    background: linear-gradient(135deg, rgba(51, 65, 85, 0.6), rgba(71, 85, 105, 0.5));
  }
}

.widget-header-content {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.widget-icon {
  flex-shrink: 0;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.widget-title {
  font-size: 14px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.95);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.widget-controls {
  display: flex;
  gap: 4px;
  margin-left: 8px;
}

.widget-control {
  opacity: 0.7;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 8px;

  &:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
  }

  &:active {
    transform: scale(0.95);
  }
}

.widget-content {
  flex: 1;
  overflow: auto;
  padding: 16px;
}

.widget-content::-webkit-scrollbar {
  width: 8px;
}

.widget-content::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 4px;
}

.widget-content::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.1));
  border-radius: 4px;
  border: 2px solid transparent;
  background-clip: padding-box;
}

.widget-content::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.2));
}

.widget-resize-handle {
  position: absolute;
  bottom: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  cursor: nwse-resize;
  opacity: 0.5;
  transition: opacity 0.2s ease;

  &:hover {
    opacity: 1;
  }
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.3;
  transition: opacity 0.2s;
  
  &:hover {
    opacity: 0.6;
  }
}

// Scrollbar styling
.widget-content {
  &::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }
  
  &::-webkit-scrollbar-track {
    background: transparent;
  }
  
  &::-webkit-scrollbar-thumb {
    background: rgba(var(--v-theme-on-surface), 0.2);
    border-radius: 3px;
    
    &:hover {
      background: rgba(var(--v-theme-on-surface), 0.3);
    }
  }
}
</style>