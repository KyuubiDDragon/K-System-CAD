<template>
  <div class="messages-feed-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>

    <div v-else-if="error" class="error-state pa-4 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ error }}</p>
    </div>

    <div v-else-if="messages.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-message-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.noMessages') }}</p>
    </div>

    <div v-else class="messages-list">
      <div
        v-for="message in messages"
        :key="message.id"
        class="message-item mb-2 pa-3"
        @click="openMessage(message)"
      >
        <div class="d-flex align-center mb-1">
          <v-avatar size="32" color="primary" class="mr-2">
            <span class="text-caption">{{ getInitials(message.from_name) }}</span>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="message-from text-body-2 font-weight-bold">
              {{ message.from_name }}
            </div>
            <div class="message-time text-caption text-medium-emphasis">
              {{ formatDate(message.created_at) }}
            </div>
          </div>
          <v-chip v-if="!message.read" size="x-small" color="primary">New</v-chip>
        </div>
        <div class="message-subject text-body-2 font-weight-medium mb-1">
          {{ message.subject }}
        </div>
        <div class="message-preview text-caption text-medium-emphasis">
          {{ getPreview(message.body) }}
        </div>
      </div>

      <v-btn block size="small" variant="text" color="primary" @click="goToMessages">
        {{ $t('dashboard.viewAll') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatDate } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

interface Message {
  id: number
  from_name: string
  subject: string
  body: string
  created_at: string
  read: boolean
}

const router = useRouter()
const { t } = useI18n()

const loading = ref(true)
const error = ref('')
const messages = ref<Message[]>([])

async function loadMessages() {
  loading.value = true
  error.value = ''

  try {
    const response = await apiClientAuth.get('/user/?action=getLast5Messages')
    messages.value = response.data || []
  } catch (err: any) {
    console.error('Failed to load messages:', err)
    error.value = err.response?.data?.error || 'Failed to load messages'
  } finally {
    loading.value = false
  }
}

function getInitials(name: string): string {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))

  if (diffHours < 1) return 'Just now'
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffHours < 48) return 'Yesterday'
  return formatDate(date)
}

function getPreview(body: string): string {
  const plainText = body.replace(/<[^>]*>/g, '')
  return plainText.length > 100 ? plainText.substring(0, 100) + '...' : plainText
}

function openMessage(message: Message) {
  router.push(`/messages/${message.id}`)
}

function goToMessages() {
  router.push('/messages')
}

onMounted(() => {
  loadMessages()
})

defineExpose({ refresh: loadMessages })
</script>

<style scoped lang="scss">
.messages-feed-widget {
  height: 100%;
  overflow: hidden;
}

.messages-list {
  padding: 0;
}

.message-item {
  border-radius: 4px;
  background: var(--k-row-hover);
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: var(--k-row-hover);
  }
}

.message-from {
  line-height: 1.2;
}

.message-subject {
  line-height: 1.3;
}

.message-preview {
  line-height: 1.4;
}

.no-data,
.error-state {
  color: var(--k-ink-faint);
}
</style>
