<template>
  <div class="mail-attachment-upload">
    <!-- Drag & Drop Zone -->
    <v-card
      :class="['drop-zone', { 'drop-zone-active': isDragging }]"
      variant="outlined"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
    >
      <v-card-text class="text-center py-6">
        <v-icon size="48" color="primary" class="mb-2">mdi-cloud-upload</v-icon>
        <p class="text-body-1 mb-2">
          <strong>Dateien hierher ziehen</strong> oder klicken Sie zum Auswahlen
        </p>
        <p class="text-caption text-medium-emphasis">
          Maximal 5 Dateien, je max. 10MB (PDF, JPG, PNG)
        </p>
        <v-file-input
          ref="fileInput"
          v-model="selectedFiles"
          multiple
          accept=".pdf,.jpg,.jpeg,.png"
          style="display: none"
          @update:model-value="onFilesSelected"
        ></v-file-input>
        <v-btn
          color="primary"
          variant="outlined"
          class="mt-2"
          @click="triggerFileSelect"
        >
          <v-icon start>mdi-paperclip</v-icon>
          Dateien auswahlen
        </v-btn>
      </v-card-text>
    </v-card>

    <!-- Upload Progress -->
    <div v-if="Object.keys(uploadProgress).length > 0" class="mt-4">
      <v-card variant="outlined">
        <v-card-text>
          <div
            v-for="(progress, filename) in uploadProgress"
            :key="filename"
            class="mb-3"
          >
            <div class="d-flex align-center mb-1">
              <v-icon size="small" class="mr-2">mdi-file-upload</v-icon>
              <span class="text-body-2">{{ filename }}</span>
              <v-spacer></v-spacer>
              <span class="text-caption">{{ Math.round(progress) }}%</span>
            </div>
            <v-progress-linear
              :model-value="progress"
              color="primary"
              height="4"
            ></v-progress-linear>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Attached Files List -->
    <div v-if="attachments.length > 0" class="mt-4">
      <v-card variant="outlined">
        <v-card-title class="text-subtitle-1 py-2 d-flex align-center">
          <v-icon start>mdi-attachment</v-icon>
          Anhange ({{ attachments.length }}/5)
          <v-spacer></v-spacer>
          <v-chip size="small" :color="totalSizeColor">
            {{ formatBytes(totalSize) }} / 25 MB
          </v-chip>
        </v-card-title>
        <v-divider></v-divider>
        <v-list density="compact">
          <v-list-item
            v-for="(attachment, index) in attachments"
            :key="attachment.id"
            class="attachment-item"
          >
            <template v-slot:prepend>
              <v-icon :color="getFileIconColor(attachment.mime_type)">
                {{ getFileIcon(attachment.mime_type) }}
              </v-icon>
            </template>

            <v-list-item-title>
              {{ attachment.original_filename }}
            </v-list-item-title>

            <v-list-item-subtitle>
              {{ formatBytes(attachment.file_size) }}
              <v-chip
                v-if="attachment.scan_status === 'clean'"
                size="x-small"
                color="success"
                class="ml-2"
              >
                <v-icon start size="x-small">mdi-check-circle</v-icon>
                Sicher
              </v-chip>
              <v-chip
                v-else-if="attachment.scan_status === 'pending'"
                size="x-small"
                color="warning"
                class="ml-2"
              >
                <v-icon start size="x-small">mdi-clock</v-icon>
                Wird gepruft...
              </v-chip>
              <v-chip
                v-else-if="attachment.scan_status === 'infected'"
                size="x-small"
                color="error"
                class="ml-2"
              >
                <v-icon start size="x-small">mdi-alert-circle</v-icon>
                Gefahrlich
              </v-chip>
            </v-list-item-subtitle>

            <template v-slot:append>
              <v-btn
                icon="mdi-close"
                size="small"
                variant="text"
                @click="removeAttachment(attachment.id)"
              ></v-btn>
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </div>

    <!-- Error Messages -->
    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      class="mt-4"
      closable
      @click:close="error = null"
    >
      {{ error }}
    </v-alert>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { MailAttachment } from '@/types/Mail'

// Props
interface Props {
  attachments: MailAttachment[]
  uploadProgress: { [key: string]: number }
  maxFiles?: number
  maxFileSize?: number // in bytes
  maxTotalSize?: number // in bytes
  allowedTypes?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  maxFiles: 5,
  maxFileSize: 10 * 1024 * 1024, // 10MB
  maxTotalSize: 25 * 1024 * 1024, // 25MB
  allowedTypes: () => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
})

// Emits
const emit = defineEmits<{
  upload: [file: File]
  remove: [attachmentId: number]
}>()

// State
const isDragging = ref(false)
const selectedFiles = ref<File[]>([])
const error = ref<string | null>(null)
const fileInput = ref<any>(null)

// Computed
const totalSize = computed(() => {
  return props.attachments.reduce((sum, att) => sum + att.file_size, 0)
})

const totalSizeColor = computed(() => {
  const percentage = (totalSize.value / props.maxTotalSize) * 100
  if (percentage > 90) return 'error'
  if (percentage > 70) return 'warning'
  return 'success'
})

// Methods
function triggerFileSelect() {
  fileInput.value?.$el.querySelector('input')?.click()
}

function onDragOver(e: DragEvent) {
  isDragging.value = true
}

function onDragLeave(e: DragEvent) {
  isDragging.value = false
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  const files = Array.from(e.dataTransfer?.files || [])
  processFiles(files)
}

function onFilesSelected(files: File[] | null) {
  if (!files) return
  processFiles(files)
  selectedFiles.value = []
}

function processFiles(files: File[]) {
  error.value = null

  for (const file of files) {
    const validation = validateFile(file)
    if (!validation.valid) {
      error.value = validation.error || 'File validation failed'
      continue
    }

    // Emit upload event
    emit('upload', file)
  }
}

function validateFile(file: File): { valid: boolean; error?: string } {
  // Check file count
  if (props.attachments.length >= props.maxFiles) {
    return {
      valid: false,
      error: `Maximal ${props.maxFiles} Dateien erlaubt`
    }
  }

  // Check file type
  if (!props.allowedTypes.includes(file.type)) {
    return {
      valid: false,
      error: `Dateityp nicht erlaubt: ${file.name}. Nur PDF, JPG und PNG erlaubt.`
    }
  }

  // Check file size
  if (file.size > props.maxFileSize) {
    return {
      valid: false,
      error: `Datei zu groß: ${file.name}. Maximal ${formatBytes(props.maxFileSize)} erlaubt.`
    }
  }

  // Check total size
  const newTotalSize = totalSize.value + file.size
  if (newTotalSize > props.maxTotalSize) {
    return {
      valid: false,
      error: `Gesamtgroße uberschreitet ${formatBytes(props.maxTotalSize)}`
    }
  }

  return { valid: true }
}

function removeAttachment(attachmentId: number) {
  emit('remove', attachmentId)
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

function getFileIcon(mimeType: string): string {
  if (mimeType === 'application/pdf') return 'mdi-file-pdf-box'
  if (mimeType.startsWith('image/')) return 'mdi-file-image'
  return 'mdi-file-document'
}

function getFileIconColor(mimeType: string): string {
  if (mimeType === 'application/pdf') return 'error'
  if (mimeType.startsWith('image/')) return 'success'
  return 'grey'
}
</script>

<style scoped>
.mail-attachment-upload {
  width: 100%;
}

.drop-zone {
  border: 2px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  transition: all 0.3s ease;
  cursor: pointer;
  background-color: rgba(var(--v-theme-surface), 1);
}

.drop-zone:hover {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.drop-zone-active {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.1);
  border-width: 3px;
}

.attachment-item {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.attachment-item:last-child {
  border-bottom: none;
}

.attachment-item:hover {
  background-color: rgba(var(--v-theme-surface), 0.5);
}
</style>
