<script setup lang="ts">
import { ref, computed } from 'vue'
import type { MailWithRecipient } from '@/types/Mail'

// Props
interface Props {
  mails: MailWithRecipient[]
  selectedMailIds?: number[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selectedMailIds: () => [],
  loading: false
})

// Emits
const emit = defineEmits<{
  mailSelect: [mail: MailWithRecipient]
  selectionChange: [mailIds: number[]]
}>()

// State
const selectAll = ref(false)
const hoveredMailId = ref<number | null>(null)

// Computed
const allSelected = computed({
  get: () => selectAll.value,
  set: (value: boolean) => {
    if (value) {
      emit('selectionChange', props.mails.map(m => m.id))
    } else {
      emit('selectionChange', [])
    }
    selectAll.value = value
  }
})

const isSelected = computed(() => (mailId: number) => {
  return props.selectedMailIds.includes(mailId)
})

// Methods
function handleMailClick(mail: MailWithRecipient) {
  emit('mailSelect', mail)
}

function toggleSelection(mailId: number) {
  const selected = [...props.selectedMailIds]
  const index = selected.indexOf(mailId)

  if (index > -1) {
    selected.splice(index, 1)
  } else {
    selected.push(mailId)
  }

  emit('selectionChange', selected)
}

function formatDate(dateString: string | null): string {
  if (!dateString) return ''

  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) {
    // Today - show time
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  } else if (days === 1) {
    return 'Yesterday'
  } else if (days < 7) {
    return date.toLocaleDateString('en-US', { weekday: 'short' })
  } else if (date.getFullYear() === now.getFullYear()) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  } else {
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  }
}

function getPreview(mail: MailWithRecipient): string {
  if (mail.preview) return mail.preview

  // Generate preview from body_text
  if (mail.body_text) {
    return mail.body_text.substring(0, 100) + (mail.body_text.length > 100 ? '...' : '')
  }

  // Fallback to stripping HTML from body_html
  if (mail.body_html) {
    const text = mail.body_html.replace(/<[^>]*>/g, '')
    return text.substring(0, 100) + (text.length > 100 ? '...' : '')
  }

  return ''
}

function getSenderName(mail: MailWithRecipient): string {
  return mail.from_name || mail.from_address || 'Unknown'
}
</script>

<template>
  <div class="mail-list">
    <!-- Select All Header -->
    <div v-if="mails.length > 0" class="select-all-header">
      <v-checkbox
        v-model="allSelected"
        hide-details
        density="compact"
        color="primary"
      />
      <span class="ml-2 text-caption text-grey-darken-1">
        Select all
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <v-progress-circular indeterminate color="primary" />
      <p class="text-grey mt-4">Loading mails...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="mails.length === 0" class="empty-container">
      <v-icon size="64" color="grey-lighten-1">mdi-email-outline</v-icon>
      <p class="text-grey mt-4">No mails found</p>
    </div>

    <!-- Mail List with Virtual Scroll -->
    <v-virtual-scroll
      v-else
      :items="mails"
      :height="'calc(100vh - 180px)'"
      item-height="88"
      class="mail-virtual-scroll"
    >
      <template v-slot:default="{ item: mail }">
        <div
          class="mail-item"
          :class="{
            'mail-unread': !mail.is_read,
            'mail-selected': isSelected(mail.id),
            'mail-hovered': hoveredMailId === mail.id
          }"
          @click="handleMailClick(mail)"
          @mouseenter="hoveredMailId = mail.id"
          @mouseleave="hoveredMailId = null"
        >
          <!-- Checkbox -->
          <div class="mail-checkbox">
            <v-checkbox
              :model-value="isSelected(mail.id)"
              hide-details
              density="compact"
              color="primary"
              @click.stop="toggleSelection(mail.id)"
            />
          </div>

          <!-- Star Icon -->
          <div class="mail-star">
            <v-icon
              :color="mail.is_starred ? 'amber' : 'grey-lighten-1'"
              size="small"
              @click.stop
            >
              {{ mail.is_starred ? 'mdi-star' : 'mdi-star-outline' }}
            </v-icon>
          </div>

          <!-- Mail Content -->
          <div class="mail-content">
            <div class="mail-header">
              <span
                class="mail-sender"
                :class="{ 'font-weight-bold': !mail.is_read }"
              >
                {{ getSenderName(mail) }}
              </span>
              <span class="mail-date text-caption text-grey">
                {{ formatDate(mail.sent_at || mail.created_at) }}
              </span>
            </div>

            <div class="mail-subject" :class="{ 'font-weight-bold': !mail.is_read }">
              {{ mail.subject || '(no subject)' }}
            </div>

            <div class="mail-preview text-caption text-grey-darken-1">
              {{ getPreview(mail) }}
            </div>

            <!-- Indicators -->
            <div class="mail-indicators">
              <v-chip
                v-if="mail.has_attachments"
                size="x-small"
                variant="text"
                prepend-icon="mdi-paperclip"
                class="mr-1"
              >
                <v-icon size="x-small">mdi-paperclip</v-icon>
              </v-chip>
              <v-chip
                v-if="mail.is_important"
                size="x-small"
                variant="text"
                color="error"
                prepend-icon="mdi-label-important"
              >
                <v-icon size="x-small">mdi-label-important</v-icon>
              </v-chip>
            </div>
          </div>
        </div>
        <v-divider />
      </template>
    </v-virtual-scroll>
  </div>
</template>

<style scoped>
.mail-list {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.select-all-header {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
}

.loading-container,
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
}

.mail-virtual-scroll {
  flex: 1;
  overflow-y: auto;
}

.mail-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  min-height: 88px;
}

.mail-item:hover,
.mail-hovered {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.mail-item.mail-selected {
  background: rgba(var(--v-theme-primary), 0.12);
}

.mail-item.mail-unread {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.mail-checkbox {
  flex-shrink: 0;
  width: 40px;
  display: flex;
  align-items: center;
}

.mail-star {
  flex-shrink: 0;
  width: 32px;
  display: flex;
  align-items: center;
  cursor: pointer;
}

.mail-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.mail-sender {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.875rem;
}

.mail-date {
  flex-shrink: 0;
  font-size: 0.75rem;
}

.mail-subject {
  font-size: 0.875rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), 0.87);
}

.mail-preview {
  font-size: 0.8125rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), 0.6);
  line-height: 1.3;
}

.mail-indicators {
  display: flex;
  gap: 4px;
  margin-top: 2px;
}

/* Scrollbar styling */
.mail-virtual-scroll::-webkit-scrollbar {
  width: 8px;
}

.mail-virtual-scroll::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-surface), 0.5);
}

.mail-virtual-scroll::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 4px;
}

.mail-virtual-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-on-surface), 0.4);
}

/* Mobile adjustments */
@media (max-width: 600px) {
  .mail-item {
    padding: 8px 12px;
  }

  .mail-sender,
  .mail-subject {
    font-size: 0.8125rem;
  }

  .mail-preview {
    font-size: 0.75rem;
  }
}
</style>
