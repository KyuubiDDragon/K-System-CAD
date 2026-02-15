<template>
  <v-card class="compose-card" elevation="0">
    <!-- Header -->
    <v-card-title class="compose-header d-flex align-center py-3 px-4">
      <v-icon start>mdi-email-edit</v-icon>
      <span>{{ title }}</span>
      <v-spacer></v-spacer>
      <v-btn
        icon
        size="small"
        variant="text"
        @click="handleCancel"
      >
        <v-icon>mdi-close</v-icon>
      </v-btn>
    </v-card-title>

    <v-divider></v-divider>

    <!-- Form Content -->
    <v-card-text class="pa-4">
      <!-- From Field -->
      <v-select
        v-if="mailAccounts.length > 1"
        v-model="form.from_address"
        :items="mailAccounts"
        item-title="email"
        item-value="email"
        label="Von"
        variant="outlined"
        density="compact"
        class="mb-2"
      >
        <template v-slot:prepend-inner>
          <v-icon size="small">mdi-account</v-icon>
        </template>
      </v-select>

      <!-- To Field -->
      <v-combobox
        v-model="form.to_addresses"
        :items="contactSuggestions"
        item-title="email"
        item-value="email"
        label="An"
        variant="outlined"
        density="compact"
        multiple
        chips
        closable-chips
        clearable
        class="mb-2"
        @update:search="searchContactsDebounced"
      >
        <template v-slot:prepend-inner>
          <v-icon size="small">mdi-email</v-icon>
        </template>
        <template v-slot:append>
          <v-btn
            v-if="!showCc"
            size="x-small"
            variant="text"
            @click="showCc = true"
          >
            CC
          </v-btn>
          <v-btn
            v-if="!showBcc"
            size="x-small"
            variant="text"
            @click="showBcc = true"
          >
            BCC
          </v-btn>
        </template>
        <template v-slot:chip="{ item, props }">
          <v-chip
            v-bind="props"
            :color="isValidEmail(item.value) ? 'primary' : 'error'"
            size="small"
          >
            {{ item.value }}
          </v-chip>
        </template>
      </v-combobox>

      <!-- CC Field -->
      <v-combobox
        v-if="showCc"
        v-model="form.cc_addresses"
        :items="contactSuggestions"
        item-title="email"
        item-value="email"
        label="CC"
        variant="outlined"
        density="compact"
        multiple
        chips
        closable-chips
        clearable
        class="mb-2"
        @update:search="searchContactsDebounced"
      >
        <template v-slot:prepend-inner>
          <v-icon size="small">mdi-email-outline</v-icon>
        </template>
        <template v-slot:chip="{ item, props }">
          <v-chip
            v-bind="props"
            :color="isValidEmail(item.value) ? 'primary' : 'error'"
            size="small"
          >
            {{ item.value }}
          </v-chip>
        </template>
      </v-combobox>

      <!-- BCC Field -->
      <v-combobox
        v-if="showBcc"
        v-model="form.bcc_addresses"
        :items="contactSuggestions"
        item-title="email"
        item-value="email"
        label="BCC"
        variant="outlined"
        density="compact"
        multiple
        chips
        closable-chips
        clearable
        class="mb-2"
        @update:search="searchContactsDebounced"
      >
        <template v-slot:prepend-inner>
          <v-icon size="small">mdi-email-lock</v-icon>
        </template>
        <template v-slot:chip="{ item, props }">
          <v-chip
            v-bind="props"
            :color="isValidEmail(item.value) ? 'primary' : 'error'"
            size="small"
          >
            {{ item.value }}
          </v-chip>
        </template>
      </v-combobox>

      <!-- Subject Field -->
      <v-text-field
        v-model="form.subject"
        label="Betreff"
        variant="outlined"
        density="compact"
        class="mb-4"
      >
        <template v-slot:prepend-inner>
          <v-icon size="small">mdi-text-short</v-icon>
        </template>
      </v-text-field>

      <!-- Rich Text Editor -->
      <MailEditor
        v-model="form.body_html"
        class="mb-4"
      />

      <!-- Attachments -->
      <MailAttachmentUpload
        :attachments="uploadedAttachments"
        :upload-progress="uploadProgress"
        @upload="handleUpload"
        @remove="handleRemoveAttachment"
      />

      <!-- Draft Saved Indicator -->
      <v-slide-y-transition>
        <v-alert
          v-if="showDraftSaved"
          type="success"
          variant="tonal"
          density="compact"
          class="mt-4"
        >
          <v-icon start>mdi-check</v-icon>
          Entwurf gespeichert
        </v-alert>
      </v-slide-y-transition>

      <!-- Discard Confirmation (inline) -->
      <v-expand-transition>
        <v-alert
          v-if="showDiscardConfirm"
          type="warning"
          variant="tonal"
          class="mt-4"
        >
          <v-alert-title>Entwurf verwerfen?</v-alert-title>
          <div class="mt-2">
            Möchten Sie diesen Entwurf wirklich verwerfen? Alle nicht gespeicherten Änderungen gehen verloren.
          </div>
          <div class="mt-3 d-flex gap-2">
            <v-btn size="small" @click="showDiscardConfirm = false">Abbrechen</v-btn>
            <v-btn size="small" color="error" @click="confirmDiscard">Verwerfen</v-btn>
          </div>
        </v-alert>
      </v-expand-transition>
    </v-card-text>

    <v-divider></v-divider>

    <!-- Footer -->
    <v-card-actions class="compose-footer pa-4">
      <v-btn
        color="primary"
        variant="elevated"
        :loading="sending"
        :disabled="!canSend"
        @click="handleSend"
      >
        <v-icon start>mdi-send</v-icon>
        Senden
        <v-tooltip activator="parent" location="top">Ctrl+Enter</v-tooltip>
      </v-btn>

      <v-btn
        variant="text"
        :loading="savingDraft"
        @click="handleSaveDraft"
      >
        <v-icon start>mdi-content-save</v-icon>
        Entwurf speichern
      </v-btn>

      <v-spacer></v-spacer>

      <!-- Template Menu -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            size="small"
          >
            <v-icon start>mdi-text-box-multiple</v-icon>
            Vorlage
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item
            v-for="template in templates"
            :key="template.id"
            @click="applyTemplate(template)"
          >
            <v-list-item-title>{{ template.name }}</v-list-item-title>
            <v-list-item-subtitle v-if="template.description">
              {{ template.description }}
            </v-list-item-subtitle>
          </v-list-item>
          <v-list-item v-if="templates.length === 0" disabled>
            <v-list-item-title>Keine Vorlagen verfügbar</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <!-- Signature Menu -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            size="small"
          >
            <v-icon start>mdi-draw</v-icon>
            Signatur
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item
            v-for="signature in signatures"
            :key="signature.id"
            @click="applySignature(signature)"
          >
            <v-list-item-title>{{ signature.name }}</v-list-item-title>
          </v-list-item>
          <v-list-item v-if="signatures.length === 0" disabled>
            <v-list-item-title>Keine Signaturen verfügbar</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <!-- Priority Menu -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            size="small"
          >
            <v-icon start :color="priorityColor">{{ priorityIcon }}</v-icon>
            Priorität
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item @click="form.priority = 'low'">
            <template v-slot:prepend>
              <v-icon color="success">mdi-chevron-down</v-icon>
            </template>
            <v-list-item-title>Niedrig</v-list-item-title>
          </v-list-item>
          <v-list-item @click="form.priority = 'normal'">
            <template v-slot:prepend>
              <v-icon>mdi-minus</v-icon>
            </template>
            <v-list-item-title>Normal</v-list-item-title>
          </v-list-item>
          <v-list-item @click="form.priority = 'high'">
            <template v-slot:prepend>
              <v-icon color="error">mdi-chevron-up</v-icon>
            </template>
            <v-list-item-title>Hoch</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <v-btn
        variant="text"
        color="error"
        @click="handleDiscard"
      >
        <v-icon start>mdi-delete</v-icon>
        Verwerfen
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useMailCompose } from '@/composables/useMailCompose'
import MailEditor from './MailEditor.vue'
import MailAttachmentUpload from './MailAttachmentUpload.vue'
import type { MailAccount, MailTemplate, MailSignature, RecipientSuggestion } from '@/types/Mail'
import { useToast } from 'vue-toastification'

// Props
interface Props {
  mode?: 'compose' | 'reply' | 'replyAll' | 'forward' | 'draft'
  draftId?: number
  replyToMailId?: number
  mailAccounts?: MailAccount[]
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'compose',
  mailAccounts: () => []
})

// Emits
const emit = defineEmits<{
  sent: []
  cancelled: []
}>()

// Composables
const toast = useToast()
const {
  form,
  loading,
  sending,
  savingDraft,
  uploadProgress,
  uploadedAttachments,
  hasContent,
  sendMail,
  saveDraft,
  uploadAttachment,
  removeAttachment,
  searchContacts,
  applyTemplate: applyTemplateFunc,
  applySignature: applySignatureFunc,
  resetForm,
  loadDraft,
  isValidEmail
} = useMailCompose(props.draftId)

// State
const showCc = ref(false)
const showBcc = ref(false)
const showDiscardConfirm = ref(false)
const showDraftSaved = ref(false)
const contactSuggestions = ref<RecipientSuggestion[]>([])
const templates = ref<MailTemplate[]>([])
const signatures = ref<MailSignature[]>([])
const autoSaveTimer = ref<ReturnType<typeof setTimeout> | null>(null)
const searchDebounceTimer = ref<ReturnType<typeof setTimeout> | null>(null)

// Computed
const title = computed(() => {
  switch (props.mode) {
    case 'reply':
      return 'Antworten'
    case 'replyAll':
      return 'Allen antworten'
    case 'forward':
      return 'Weiterleiten'
    case 'draft':
      return 'Entwurf bearbeiten'
    default:
      return 'Neue E-Mail'
  }
})

const canSend = computed(() => {
  return (
    form.value.to_addresses.length > 0 &&
    form.value.subject.trim() !== '' &&
    form.value.body_html.trim() !== '' &&
    !sending.value
  )
})

const priorityIcon = computed(() => {
  switch (form.value.priority) {
    case 'high':
      return 'mdi-chevron-up'
    case 'low':
      return 'mdi-chevron-down'
    default:
      return 'mdi-minus'
  }
})

const priorityColor = computed(() => {
  switch (form.value.priority) {
    case 'high':
      return 'error'
    case 'low':
      return 'success'
    default:
      return ''
  }
})

// Watch
watch(() => form.value, () => {
  scheduleAutoSave()
}, { deep: true })

// Methods
async function initialize() {
  // Set default from address if available
  if (props.mailAccounts.length > 0 && !form.value.from_address) {
    form.value.from_address = props.mailAccounts[0].email
  }

  // Load templates and signatures
  await fetchTemplates()
  await fetchSignatures()

  // Auto-insert default signature for new compose
  if (props.mode === 'compose') {
    const defaultSig = signatures.value.find(s => s.is_default && s.use_for_new)
    if (defaultSig) {
      applySignatureFunc(defaultSig)
    }
  }

  // Load draft if editing
  if (props.mode === 'draft' && props.draftId) {
    await loadDraft(props.draftId)
  }

  // Handle reply/forward modes
  if (props.replyToMailId && ['reply', 'replyAll', 'forward'].includes(props.mode)) {
    await loadReplyData()
  }

  // Start auto-save
  scheduleAutoSave()
}

async function fetchTemplates() {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action: 'getTemplates' })
    })
    const data = await response.json()
    if (data.success) {
      templates.value = data.data || []
    }
  } catch (error) {
    console.error('Error fetching templates:', error)
  }
}

async function fetchSignatures() {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action: 'getSignatures' })
    })
    const data = await response.json()
    if (data.success) {
      signatures.value = data.data || []
    }
  } catch (error) {
    console.error('Error fetching signatures:', error)
  }
}

async function loadReplyData() {
  // TODO: Implement loading original mail and formatting reply/forward
}

function searchContactsDebounced(query: string) {
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }

  searchDebounceTimer.value = setTimeout(async () => {
    if (query && query.length >= 2) {
      const results = await searchContacts(query)
      contactSuggestions.value = results
    }
  }, 300)
}

function applyTemplate(template: MailTemplate) {
  applyTemplateFunc(template)
  toast.success('Vorlage angewendet')
}

function applySignature(signature: MailSignature) {
  applySignatureFunc(signature)
  toast.success('Signatur eingefügt')
}

async function handleSend() {
  const result = await sendMail()

  if (result.success) {
    toast.success('E-Mail erfolgreich gesendet')
    clearAutoSave()
    resetForm()
    emit('sent')
  } else {
    toast.error(result.error || 'Fehler beim Senden')
  }
}

async function handleSaveDraft() {
  const result = await saveDraft()

  if (result.success) {
    showDraftSaved.value = true
    setTimeout(() => {
      showDraftSaved.value = false
    }, 3000)
    toast.success('Entwurf gespeichert')
  } else {
    toast.error(result.error || 'Fehler beim Speichern')
  }
}

function scheduleAutoSave() {
  if (autoSaveTimer.value) {
    clearTimeout(autoSaveTimer.value)
  }

  // Auto-save every 30 seconds
  autoSaveTimer.value = setTimeout(async () => {
    if (hasContent.value) {
      await saveDraft()
      showDraftSaved.value = true
      setTimeout(() => {
        showDraftSaved.value = false
      }, 2000)
    }
  }, 30000)
}

function clearAutoSave() {
  if (autoSaveTimer.value) {
    clearTimeout(autoSaveTimer.value)
    autoSaveTimer.value = null
  }
}

async function handleUpload(file: File) {
  const result = await uploadAttachment(file)
  if (!result.success) {
    toast.error(result.error || 'Fehler beim Hochladen')
  }
}

async function handleRemoveAttachment(attachmentId: number) {
  const result = await removeAttachment(attachmentId)
  if (!result.success) {
    toast.error(result.error || 'Fehler beim Entfernen')
  }
}

function handleCancel() {
  if (hasContent.value) {
    showDiscardConfirm.value = true
  } else {
    emit('cancelled')
  }
}

function handleDiscard() {
  if (hasContent.value) {
    showDiscardConfirm.value = true
  } else {
    resetForm()
    emit('cancelled')
  }
}

function confirmDiscard() {
  showDiscardConfirm.value = false
  clearAutoSave()
  resetForm()
  emit('cancelled')
}

// Keyboard shortcuts
function handleKeydown(e: KeyboardEvent) {
  // Ctrl+Enter to send
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    e.preventDefault()
    if (canSend.value) {
      handleSend()
    }
  }

  // Escape to cancel
  if (e.key === 'Escape' && !showDiscardConfirm.value) {
    e.preventDefault()
    handleCancel()
  }
}

// Lifecycle
onMounted(() => {
  initialize()
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  clearAutoSave()
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
})
</script>

<style scoped>
.compose-card {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.compose-header {
  background: rgb(var(--v-theme-surface));
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.compose-footer {
  background: rgb(var(--v-theme-surface));
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
