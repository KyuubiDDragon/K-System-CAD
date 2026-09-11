<script setup lang="ts">
import { ref, computed } from 'vue'
import { useMailStore } from '@/stores/mail'
import type { MailWithRecipient, MailAttachment } from '@/types/Mail'
import MailAccessDialog from './MailAccessDialog.vue'

// Props
interface Props {
  mail: MailWithRecipient
  showBackButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showBackButton: false
})

// Emits
const emit = defineEmits<{
  back: []
  close: []
  reply: [mail: MailWithRecipient]
  replyAll: [mail: MailWithRecipient]
  forward: [mail: MailWithRecipient]
  'access-changed': []
}>()

// Store
const mailStore = useMailStore()

// State
const showRawHtml = ref(false)
const showAccessDialog = ref(false)

// Computed
const formattedDate = computed(() => {
  if (!props.mail.sent_at && !props.mail.created_at) return ''

  const date = new Date(props.mail.sent_at || props.mail.created_at)
  return date.toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  })
})

const recipients = computed(() => {
  const to = props.mail.to_addresses || []
  return to.join(', ')
})

const ccRecipients = computed(() => {
  const cc = props.mail.cc_addresses || []
  return cc.length > 0 ? cc.join(', ') : null
})

const bccRecipients = computed(() => {
  const bcc = props.mail.bcc_addresses || []
  return bcc.length > 0 ? bcc.join(', ') : null
})

const sanitizedHtml = computed(() => {
  // Basic HTML sanitization (in production, use a library like DOMPurify)
  if (!props.mail.body_html) return ''

  // For now, just return as-is with v-html
  // TODO: Add DOMPurify or similar sanitization
  return props.mail.body_html
})

const attachments = computed(() => {
  return props.mail.attachments || []
})

// Methods
async function toggleStar() {
  await mailStore.markAsStarred(props.mail.id, !props.mail.is_starred)
}

async function deleteMail() {
  if (confirm('Are you sure you want to delete this mail?')) {
    await mailStore.deleteMail(props.mail.id)
    emit('close')
  }
}

async function moveToFolder() {
  // This would open a folder selection dialog
  // For now, just a placeholder
  console.log('Move to folder')
}

function handleReply() {
  emit('reply', props.mail)
}

function handleReplyAll() {
  emit('replyAll', props.mail)
}

function handleForward() {
  emit('forward', props.mail)
}

function handleBack() {
  emit('back')
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

function downloadAttachment(attachment: MailAttachment) {
  // TODO: Implement actual download
  console.log('Download attachment:', attachment)
}
</script>

<template>
  <v-card class="mail-detail-card fill-height" flat>
    <!-- Toolbar -->
    <v-toolbar flat density="comfortable" class="mail-detail-toolbar">
      <v-btn
        v-if="showBackButton"
        icon="mdi-arrow-left"
        @click="handleBack"
      />

      <v-spacer />

      <!-- Action Buttons -->
      <v-btn
        icon
        variant="text"
        @click="toggleStar"
      >
        <v-icon :color="mail.is_starred ? 'amber' : undefined">
          {{ mail.is_starred ? 'mdi-star' : 'mdi-star-outline' }}
        </v-icon>
        <v-tooltip activator="parent" location="bottom">
          {{ mail.is_starred ? 'Unstar' : 'Star' }}
        </v-tooltip>
      </v-btn>

      <v-btn
        icon="mdi-account-multiple"
        variant="text"
        @click="showAccessDialog = true"
      >
        <v-icon>mdi-account-multiple</v-icon>
        <v-tooltip activator="parent" location="bottom">Zugriff teilen</v-tooltip>
      </v-btn>

      <v-btn
        icon="mdi-delete-outline"
        variant="text"
        @click="deleteMail"
      >
        <v-icon>mdi-delete-outline</v-icon>
        <v-tooltip activator="parent" location="bottom">Delete</v-tooltip>
      </v-btn>

      <v-btn
        icon="mdi-folder-outline"
        variant="text"
        @click="moveToFolder"
      >
        <v-icon>mdi-folder-outline</v-icon>
        <v-tooltip activator="parent" location="bottom">{{ $t('mail.moveToFolder') }}</v-tooltip>
      </v-btn>

      <v-divider vertical class="mx-2" />

      <v-btn
        icon="mdi-reply"
        variant="text"
        @click="handleReply"
      >
        <v-icon>mdi-reply</v-icon>
        <v-tooltip activator="parent" location="bottom">Reply</v-tooltip>
      </v-btn>

      <v-btn
        icon="mdi-reply-all"
        variant="text"
        @click="handleReplyAll"
      >
        <v-icon>mdi-reply-all</v-icon>
        <v-tooltip activator="parent" location="bottom">{{ $t('mail.replyAll') }}</v-tooltip>
      </v-btn>

      <v-btn
        icon="mdi-share"
        variant="text"
        @click="handleForward"
      >
        <v-icon>mdi-share</v-icon>
        <v-tooltip activator="parent" location="bottom">Forward</v-tooltip>
      </v-btn>
    </v-toolbar>

    <v-divider />

    <!-- Mail Content -->
    <v-card-text class="mail-detail-content">
      <!-- Subject -->
      <h2 class="mail-subject mb-4">
        {{ mail.subject || '(no subject)' }}
      </h2>

      <!-- Header Info -->
      <div class="mail-header-info mb-4">
        <!-- From -->
        <div class="d-flex align-center mb-2">
          <v-avatar color="primary" size="40" class="mr-3">
            <span class="text-h6">
              {{ (mail.from_name || mail.from_address || '?')[0].toUpperCase() }}
            </span>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="font-weight-medium">
              {{ mail.from_name || mail.from_address }}
            </div>
            <div class="text-caption text-grey">
              {{ formattedDate }}
            </div>
          </div>
        </div>

        <!-- Recipients -->
        <v-expansion-panels variant="accordion" class="mt-2">
          <v-expansion-panel>
            <v-expansion-panel-title class="text-caption py-2">
              <span class="text-grey-darken-1">To:</span>
              <span class="ml-2">{{ recipients }}</span>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div class="text-body-2">
                <div class="mb-2">
                  <strong>To:</strong> {{ recipients }}
                </div>
                <div v-if="ccRecipients" class="mb-2">
                  <strong>CC:</strong> {{ ccRecipients }}
                </div>
                <div v-if="bccRecipients" class="mb-2">
                  <strong>BCC:</strong> {{ bccRecipients }}
                </div>
                <div>
                  <strong>From:</strong> {{ mail.from_address }}
                </div>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </div>

      <!-- Attachments -->
      <div v-if="attachments.length > 0" class="mail-attachments mb-4">
        <v-divider class="mb-3" />
        <div class="text-subtitle-2 mb-2">
          <v-icon size="small" class="mr-1">mdi-paperclip</v-icon>
          {{ attachments.length }} Attachment{{ attachments.length > 1 ? 's' : '' }}
        </div>
        <v-chip
          v-for="attachment in attachments"
          :key="attachment.id"
          class="mr-2 mb-2"
          variant="outlined"
          @click="downloadAttachment(attachment)"
          prepend-icon="mdi-file"
        >
          {{ attachment.filename }}
          <span class="text-caption ml-2">({{ formatFileSize(attachment.file_size) }})</span>
        </v-chip>
        <v-divider class="mt-3" />
      </div>

      <!-- Body -->
      <div class="mail-body mt-4">
        <!-- Toggle for viewing raw HTML (for debugging) -->
        <v-switch
          v-model="showRawHtml"
          :label="$t('mail.showRawHtml')"
          density="compact"
          hide-details
          class="mb-2"
        />

        <!-- HTML Body -->
        <div
          v-if="!showRawHtml"
          class="mail-html-content"
          v-html="sanitizedHtml"
        />

        <!-- Raw HTML -->
        <pre v-else class="mail-raw-html">{{ mail.body_html }}</pre>

        <!-- Plain Text Fallback -->
        <div v-if="!mail.body_html && mail.body_text" class="mail-text-content">
          {{ mail.body_text }}
        </div>

        <!-- No Content -->
        <div v-if="!mail.body_html && !mail.body_text" class="text-grey text-center py-8">
          <v-icon size="48" color="grey-lighten-1">mdi-email-outline</v-icon>
          <p class="mt-2">{{ $t('mail.noContent') }}</p>
        </div>
      </div>
    </v-card-text>

    <!-- Reply Section (sticky bottom) -->
    <v-divider />
    <v-card-actions class="mail-detail-actions">
      <v-btn
        color="primary"
        variant="elevated"
        prepend-icon="mdi-reply"
        @click="handleReply"
      >
        Reply
      </v-btn>
      <v-btn
        variant="text"
        prepend-icon="mdi-reply-all"
        @click="handleReplyAll"
      >
        {{ $t('mail.replyAll') }}
      </v-btn>
      <v-btn
        variant="text"
        prepend-icon="mdi-share"
        @click="handleForward"
      >
        Forward
      </v-btn>
    </v-card-actions>

    <!-- Access Rights Dialog -->
    <MailAccessDialog
      v-model="showAccessDialog"
      :mail-id="mail.id"
      @access-changed="$emit('access-changed')"
    />
  </v-card>
</template>

<style scoped>
.mail-detail-card {
  display: flex;
  flex-direction: column;
  background-color: var(--k-surface);
  overflow: hidden;
}

.mail-detail-toolbar {
  border-bottom: 1px solid var(--k-line);
}

.mail-detail-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.mail-subject {
  font-size: 1.5rem;
  font-weight: 500;
  line-height: 1.3;
  color: #212121;
}

.mail-header-info {
  border-bottom: 1px solid var(--k-line);
  padding-bottom: 16px;
}

.mail-attachments {
  padding: 12px 0;
}

.mail-body {
  line-height: 1.6;
  color: #424242;
}

.mail-html-content {
  /* Reset some common email styles */
  font-family: inherit;
  font-size: inherit;
  color: inherit;
  line-height: 1.6;
}

.mail-html-content :deep(a) {
  color: var(--k-accent);
  text-decoration: none;
}

.mail-html-content :deep(a:hover) {
  text-decoration: underline;
}

.mail-html-content :deep(img) {
  max-width: 100%;
  height: auto;
}

.mail-html-content :deep(blockquote) {
  border-left: 3px solid var(--k-line);
  margin: 16px 0;
  padding-left: 16px;
  color: #757575;
}

.mail-text-content {
  white-space: pre-wrap;
  word-wrap: break-word;
}

.mail-raw-html {
  font-family: monospace;
  font-size: 0.875rem;
  background-color: var(--k-sunken);
  padding: 16px;
  border-radius: 4px;
  overflow-x: auto;
}

.mail-detail-actions {
  border-top: 1px solid var(--k-line);
  padding: 16px 24px;
  background-color: var(--k-sunken);
}

/* Scrollbar styling */
.mail-detail-content::-webkit-scrollbar {
  width: 8px;
}

.mail-detail-content::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.mail-detail-content::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.mail-detail-content::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Mobile adjustments */
@media (max-width: 600px) {
  .mail-detail-content {
    padding: 16px;
  }

  .mail-subject {
    font-size: 1.25rem;
  }

  .mail-detail-actions {
    padding: 12px 16px;
  }
}
</style>
