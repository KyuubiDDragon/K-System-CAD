<template>
  <widget-container
    :id="widgetId"
    :title="$t('widgets.notes.title')"
    icon="mdi-note-text"
    color="green"
    :position="position"
    :min-width="300"
    :min-height="300"
    :max-width="() => Math.min(600, window.innerWidth - 120)"
    :max-height="800"
    @update:position="$emit('update:position', $event)"
    @close="$emit('close')"
    @focus="$emit('focus')"
  >
    <div class="notes-widget">
      <!-- Notes Editor -->
      <div class="notes-editor">
        <v-textarea
          v-model="noteContent"
          :placeholder="$t('widgets.notes.placeholder')"
          variant="solo-filled"
          flat
          hide-details
          auto-grow
          rows="10"
          class="notes-textarea"
          @input="onNoteChange"
        />
      </div>

      <!-- Notes Footer -->
      <div class="notes-footer">
        <div class="save-status">
          <v-fade-transition>
            <div v-if="isSaving" class="status-item saving">
              <v-progress-circular 
                indeterminate 
                size="12" 
                width="2" 
                color="primary"
              />
              <span>{{ $t('widgets.notes.saving') }}</span>
            </div>
            <div v-else-if="lastSaved" class="status-item saved">
              <v-icon size="12" color="success">mdi-check-circle</v-icon>
              <span>{{ $t('widgets.notes.lastSaved', { time: formatLastSaved() }) }}</span>
            </div>
            <div v-else-if="saveError" class="status-item error">
              <v-icon size="12" color="error">mdi-alert-circle</v-icon>
              <span>{{ $t('widgets.notes.saveError') }}</span>
            </div>
          </v-fade-transition>
        </div>

        <div class="notes-actions">
          <v-btn
            icon
            size="x-small"
            variant="text"
            @click="clearNotes"
            :disabled="!noteContent || isSaving"
          >
            <v-icon size="18">mdi-delete</v-icon>
            <v-tooltip
              activator="parent"
              location="top"
            >
              {{ $t('common.clear') }}
            </v-tooltip>
          </v-btn>

          <v-btn
            icon
            size="x-small"
            variant="text"
            @click="downloadNotes"
            :disabled="!noteContent"
          >
            <v-icon size="18">mdi-download</v-icon>
            <v-tooltip
              activator="parent"
              location="top"
            >
              {{ $t('common.download') }}
            </v-tooltip>
          </v-btn>
        </div>
      </div>
    </div>
  </widget-container>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import WidgetContainer from './WidgetContainer.vue';
import { apiClientAuth } from '@/api';
import { debounce } from 'lodash-es';

interface Props {
  position?: { x: number; y: number; width: number; height: number };
  noteId?: string;
}

const props = withDefaults(defineProps<Props>(), {
  position: () => ({ x: 700, y: 20, width: 350, height: 400 }),
  noteId: 'widget-note-default'
});

const emit = defineEmits<{
  'update:position': [position: any];
  'close': [];
  'focus': [];
}>();

const { t, locale } = useI18n();

// State
const widgetId = `notes-widget-${Date.now()}`;
const noteContent = ref('');
const isSaving = ref(false);
const lastSaved = ref<Date | null>(null);
const saveError = ref(false);

// Auto-save timer
let autoSaveTimer: number | null = null;

// Methods
const formatLastSaved = (): string => {
  if (!lastSaved.value) return '';
  
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - lastSaved.value.getTime()) / 1000);
  
  if (diffInSeconds < 60) {
    return t('common.justNow');
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return t('common.minutesAgo', { count: minutes });
  } else {
    return lastSaved.value.toLocaleTimeString(locale.value, { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  }
};

const saveNotes = async () => {
  if (isSaving.value) return;
  
  isSaving.value = true;
  saveError.value = false;
  
  try {
    // Save notes to backend or localStorage
    const noteData = {
      id: props.noteId,
      content: noteContent.value,
      updatedAt: new Date().toISOString()
    };
    
    // Try to save to backend first
    try {
      await apiClientAuth.post('/notes/', noteData);
    } catch (err) {
      // Fallback to localStorage
      localStorage.setItem(`widget-notes-${props.noteId}`, JSON.stringify(noteData));
    }
    
    lastSaved.value = new Date();
  } catch (err) {
    console.error('Error saving notes:', err);
    saveError.value = true;
  } finally {
    isSaving.value = false;
  }
};

// Debounced save function
const debouncedSave = debounce(saveNotes, 1000);

const onNoteChange = () => {
  saveError.value = false;
  debouncedSave();
};

const loadNotes = async () => {
  try {
    // Try to load from backend first
    try {
      const response = await apiClientAuth.get(`/notes/${props.noteId}`);
      if (response.data && response.data.content) {
        noteContent.value = response.data.content;
        lastSaved.value = new Date(response.data.updatedAt);
      }
    } catch (err) {
      // Fallback to localStorage
      const savedData = localStorage.getItem(`widget-notes-${props.noteId}`);
      if (savedData) {
        const parsed = JSON.parse(savedData);
        noteContent.value = parsed.content || '';
        lastSaved.value = parsed.updatedAt ? new Date(parsed.updatedAt) : null;
      }
    }
  } catch (err) {
    console.error('Error loading notes:', err);
  }
};

const clearNotes = () => {
  if (confirm(t('common.confirmClear'))) {
    noteContent.value = '';
    saveNotes();
  }
};

const downloadNotes = () => {
  const blob = new Blob([noteContent.value], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `notes-${new Date().toISOString().split('T')[0]}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

// Lifecycle
onMounted(() => {
  loadNotes();
  
  // Auto-save every 30 seconds if there are changes
  autoSaveTimer = window.setInterval(() => {
    if (noteContent.value && !isSaving.value) {
      saveNotes();
    }
  }, 30000);
});

onUnmounted(() => {
  // Save any pending changes
  if (noteContent.value && !isSaving.value) {
    saveNotes();
  }
  
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer);
  }
});

// Watch for widget close to save notes
watch(() => noteContent.value, () => {
  if (noteContent.value === '') {
    lastSaved.value = null;
  }
});
</script>

<style scoped lang="scss">
.notes-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.notes-editor {
  flex: 1;
  overflow: hidden;
  
  :deep(.v-input) {
    height: 100%;
  }
  
  :deep(.v-field) {
    height: 100%;
    background: transparent;
    border: none;
  }
  
  :deep(.v-field__field) {
    height: 100%;
  }
  
  :deep(.v-field__input) {
    height: 100%;
    padding: 0;
  }
}

.notes-textarea {
  height: 100%;
  
  :deep(textarea) {
    height: 100% !important;
    font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
    color: var(--v-theme-on-surface);
    resize: none;
    
    &::placeholder {
      color: rgba(var(--v-theme-on-surface), 0.5);
    }
  }
}

.notes-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 8px;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  min-height: 32px;
}

.save-status {
  flex: 1;
  min-width: 0;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  
  &.saving {
    color: var(--v-theme-primary);
  }
  
  &.saved {
    color: var(--v-theme-success);
  }
  
  &.error {
    color: var(--v-theme-error);
  }
}

.notes-actions {
  display: flex;
  gap: 4px;
  margin-left: 8px;
}

// Dark theme adjustments
.v-theme--dark {
  .notes-textarea {
    :deep(.v-field) {
      background: var(--k-row-hover);
    }
  }
}

// Light theme adjustments
.v-theme--light {
  .notes-textarea {
    :deep(.v-field) {
      background: rgba(0, 0, 0, 0.02);
    }
  }
}
</style>