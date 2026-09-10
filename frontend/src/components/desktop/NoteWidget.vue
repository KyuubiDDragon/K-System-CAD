<template>
  <div class="note-widget" :style="{ '--notiz-farbe': color }">
    <div class="note-header">
      <div class="notes-selector">
        <v-btn density="comfortable" variant="text" icon="mdi-chevron-left" size="small" @click="emitPrevNote" :disabled="!hasPrevNote"></v-btn>
        <span class="note-counter">{{ noteIndex + 1 }} / {{ totalNotes }}</span>
        <v-btn density="comfortable" variant="text" icon="mdi-chevron-right" size="small" @click="emitNextNote" :disabled="!hasNextNote"></v-btn>
        <v-btn density="comfortable" variant="text" icon="mdi-plus" size="small" @click="emitNewNote" :title="t('desktop.newNote')"></v-btn>
      </div>
      <div class="note-actions">
        <v-btn density="comfortable" variant="text" icon="mdi-content-save" size="small" @click="saveNote" :disabled="!hasChanges || saving"></v-btn>
        <v-btn density="comfortable" variant="text" icon="mdi-delete" size="small" @click="confirmDelete" :disabled="saving || !note.id"></v-btn>
      </div>
    </div>
    
    <div class="note-content">
      <textarea 
        v-model="content" 
        :placeholder="t('desktop.enterNote')"
        @input="onInput"
        ref="noteTextarea"
      ></textarea>
    </div>
    
    <div class="note-tag-section">
      <div class="tag-input-container">
        <input 
          type="text" 
          v-model="newTag" 
          @keyup.enter="addTag" 
          :placeholder="t('desktop.addTag')"
          class="tag-input"
        >
        <v-btn density="comfortable" variant="text" icon="mdi-plus" size="x-small" @click="addTag" :disabled="!newTag.trim()"></v-btn>
      </div>
      <div class="tags-container">
        <div 
          v-for="(tag, index) in tags" 
          :key="tag" 
          class="tag-chip"
          :style="{ background: getTagColor(tag) }"
        >
          {{ tag }}
          <v-icon size="x-small" @click="removeTag(index)">mdi-close</v-icon>
        </div>
      </div>
    </div>
    
    <div class="color-picker">
      <div 
        v-for="(colorOption, index) in colorOptions" 
        :key="index" 
        class="color-option"
        :class="{ active: color === colorOption.value }"
        :style="{ background: colorOption.value }"
        @click="selectColor(colorOption.value)"
      ></div>
    </div>
    
    <div v-if="saving" class="note-saving-indicator">
      <v-progress-circular indeterminate size="16" width="2" color="white"></v-progress-circular>
      <span>{{ t('widgets.notes.saving') }}</span>
    </div>
    
    <div v-if="error" class="note-error">{{ error }}</div>
    
    <div class="note-footer">
      <span class="note-timestamp" v-if="note.updated_at">
        {{ t('desktop.lastUpdated') }} {{ formatDate(note.updated_at) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { apiClientAuth } from '@/api';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

interface Props {
  note: {
    id?: number | null;
    content?: string;
    color?: string;
    position?: { x: number; y: number };
    tags?: string[];
    created_at?: string | null;
    updated_at?: string | null;
  }
  noteIndex?: number
  totalNotes?: number
  hasPrevNote?: boolean
  hasNextNote?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  note: () => ({
    id: null,
    content: '',
    color: '#fbbf24',
    position: { x: 0, y: 0 },
    tags: [],
    created_at: null,
    updated_at: null
  }),
  noteIndex: 0,
  totalNotes: 1,
  hasPrevNote: false,
  hasNextNote: false
});

const emit = defineEmits(['saved', 'deleted', 'update:note', 'prev-note', 'next-note', 'new-note']);

// State
const content = ref(props.note.content || '');
const color = ref(props.note.color || '#fbbf24');
const tags = ref<string[]>(props.note.tags || []);
const newTag = ref('');
const saving = ref(false);
const error = ref('');
const noteTextarea = ref<HTMLTextAreaElement | null>(null);
const originalContent = ref(props.note.content || '');
const originalColor = ref(props.note.color || '#fbbf24');
const originalTags = ref<string[]>([...(props.note.tags || [])]);
const toast = useToast();
const { t } = useI18n();

// Color options
const colorOptions = [
  { name: 'Amber', value: '#fbbf24' },
  { name: 'Blue', value: 'var(--k-accent)' },
  { name: 'Green', value: '#10b981' },
  { name: 'Red', value: '#ef4444' },
  { name: 'Purple', value: '#8b5cf6' },
  { name: 'Pink', value: '#ec4899' },
];

// Generate colors for tags based on their name
const getTagColor = (tag: string) => {
  // Simple hash function to generate a consistent color based on tag name
  let hash = 0;
  for (let i = 0; i < tag.length; i++) {
    hash = tag.charCodeAt(i) + ((hash << 5) - hash);
  }
  
  // Generate HSL color with fixed saturation and lightness
  const hue = Math.abs(hash) % 360;
  return `hsl(${hue}, 70%, 65%)`;
};

// Computed properties
const hasChanges = computed(() => {
  return content.value !== originalContent.value || 
         color.value !== originalColor.value ||
         JSON.stringify(tags.value) !== JSON.stringify(originalTags.value);
});

/*
   Die gewaehlte Farbe markiert die Notiz, sie traegt sie nicht.
   Vorher lag die Farbe mit 50 Prozent Deckung als ganze Flaeche unter dem
   Zettel: der Zaehler "1 / 0" kam damit auf 1,51:1 und war nicht zu lesen. Der
   Zettel steht jetzt auf der normalen Flaeche, die Farbe sitzt als Kante links
   - dieselbe Rolle, die sie in den Listen als Bedeutungspunkt hat.
*/

// Methods for tags
const addTag = () => {
  const trimmedTag = newTag.value.trim();
  if (trimmedTag && !tags.value.includes(trimmedTag)) {
    tags.value.push(trimmedTag);
    newTag.value = '';
  }
};

const removeTag = (index: number) => {
  tags.value.splice(index, 1);
};

// Methods for note navigation
const emitPrevNote = () => {
  if (props.hasPrevNote) {
    emit('prev-note');
  }
};

const emitNextNote = () => {
  if (props.hasNextNote) {
    emit('next-note');
  }
};

const emitNewNote = () => {
  emit('new-note');
};

// Other methods
const selectColor = (newColor: string) => {
  color.value = newColor;
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('de-DE', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const onInput = () => {
  // Auto resize textarea
  if (noteTextarea.value) {
    noteTextarea.value.style.height = 'auto';
    noteTextarea.value.style.height = `${noteTextarea.value.scrollHeight}px`;
  }
};

const saveNote = async () => {
  if (!hasChanges.value) return;
  
  saving.value = true;
  error.value = '';
  
  try {
    let response;
    const noteData = {
      content: content.value,
      color: color.value,
      position: props.note.position || { x: 0, y: 0 },
      tags: tags.value
    };
    
    if (props.note.id) {
      // Update existing note
      response = await apiClientAuth.post(`/notes/?action=updateNote&id=${props.note.id}`, noteData);
    } else {
      // Create new note
      response = await apiClientAuth.post('/notes/?action=createNote', noteData);
    }
    
    if (response.data) {
      // Zuerst die Originalwerte aktualisieren
      originalContent.value = content.value;
      originalColor.value = color.value;
      originalTags.value = [...tags.value];
      
      // Dann das Notiz-Objekt aktualisieren, aber dabei den aktuellen content und color beibehalten
      const updatedNote = {
        ...response.data,
        content: content.value, // Sicherstellen, dass der aktuelle Inhalt beibehalten wird
        color: color.value,     // Sicherstellen, dass die aktuelle Farbe beibehalten wird
        tags: [...tags.value]   // Sicherstellen, dass die aktuellen Tags beibehalten werden
      };
      
      // Events emittieren
      emit('saved', updatedNote);
      emit('update:note', updatedNote);
      
      toast.success(props.note.id ? t('desktop.noteUpdated') : t('desktop.noteCreated'));
    }
  } catch (err) {
    console.error('Error saving note:', err);
    error.value = t('desktop.errorSavingNote');
    toast.error(t('desktop.errorSavingNote'));
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async () => {
  if (!props.note.id || saving.value) return;
  
  if (confirm(t('desktop.confirmDeleteNote'))) {
    deleteNote();
  }
};

const deleteNote = async () => {
  if (!props.note.id) return;
  
  saving.value = true;
  error.value = '';
  
  try {
    await apiClientAuth.delete(`/notes/?action=deleteNote&id=${props.note.id}`);
    emit('deleted', props.note.id);
    toast.info(t('desktop.noteDeleted'));
  } catch (err) {
    console.error('Error deleting note:', err);
    error.value = t('desktop.errorDeletingNote');
    toast.error(t('desktop.errorDeletingNote'));
  } finally {
    saving.value = false;
  }
};

// Auto resize textarea on initial load
onMounted(() => {
  nextTick(() => {
    if (noteTextarea.value && content.value) {
      onInput();
    }
  });
});

// Watch for external note changes
watch(() => props.note, (newNote) => {
  content.value = newNote.content || '';
  color.value = newNote.color || '#fbbf24';
  tags.value = [...(newNote.tags || [])];
  originalContent.value = newNote.content || '';
  originalColor.value = newNote.color || '#fbbf24';
  originalTags.value = [...(newNote.tags || [])];
}, { deep: true });
</script>

<style scoped>
.note-widget {
  background: var(--k-surface);
  border-left: 3px solid var(--notiz-farbe, var(--k-accent));
  border-radius: var(--border-radius-md);
  overflow: hidden;
  width: var(--desktop-widget-width);
  box-shadow: var(--shadow-medium);
  border: 1px solid var(--k-line);
  transition: all var(--animation-duration-normal) var(--animation-easing);
  animation: note-appear 0.5s var(--animation-easing);
  display: flex;
  flex-direction: column;
  min-height: 150px;
  width: 96%;
  margin-top: 10px;
}

/* Kein Anheben und kein Leuchten beim Zeigen - eine Notiz ist keine Schaltflaeche. */

.note-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px;
  border-bottom: 1px solid var(--k-line);
  background: var(--k-sunken);
}

.notes-selector {
  display: flex;
  align-items: center;
  gap: 4px;
}

.note-counter {
  font-size: 12px;
  color: var(--desktop-text-secondary);
  min-width: 45px;
  text-align: center;
}

.note-actions {
  display: flex;
  gap: 4px;
}

.note-content {
  flex: 1;
  padding: 10px;
  min-height: 100px;
}

.note-tag-section {
  padding: 8px 10px;
  border-top: 1px solid var(--k-line);
  background: var(--k-sunken);
}

.tag-input-container {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  background: var(--k-row-hover);
  border-radius: var(--border-radius-sm);
  padding: 0 5px;
}

.tag-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--desktop-text);
  padding: 5px;
  font-size: 12px;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.tag-chip {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
  color: rgba(0, 0, 0, 0.7);
  font-weight: 500;
}

.tag-chip .v-icon {
  cursor: pointer;
  opacity: 0.7;
}

.tag-chip .v-icon:hover {
  opacity: 1;
}

.color-picker {
  display: flex;
  gap: 6px;
  padding: 10px;
  border-top: 1px solid var(--k-line);
  background: rgba(0, 0, 0, 0.1);
  justify-content: center;
}

.color-option {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  cursor: pointer;
  border: 1px solid var(--k-line);
  transition: all 0.2s ease;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border: 2px solid white;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.3);
}

textarea {
  width: 100%;
  height: 100%;
  min-height: 80px;
  background: transparent;
  border: none;
  outline: none;
  resize: none;
  color: var(--desktop-text);
  font-family: var(--desktop-font-family);
  padding: 5px;
  border-radius: var(--border-radius-sm);
  border-left: 2px solid transparent;
  transition: all 0.3s ease;
  font-size: 14px;
}

textarea:focus {
  background: var(--k-row-hover);
}

.note-footer {
  padding: 6px 10px;
  font-size: 10px;
  color: var(--desktop-text-secondary);
  border-top: 1px solid var(--k-line);
  background: rgba(0, 0, 0, 0.1);
  text-align: right;
}

.note-timestamp {
  font-style: italic;
}

.note-saving-indicator {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.6);
  padding: 6px 12px;
  border-radius: var(--border-radius-md);
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  z-index: 5;
}

.note-error {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  font-size: 12px;
  padding: 6px 10px;
  text-align: center;
  margin-top: auto;
}

@keyframes note-appear {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style> 