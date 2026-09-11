<template>
  <div class="blackboard-widget">
    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state pa-4 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ error }}</p>
    </div>

    <!-- No Entries -->
    <div v-else-if="!latestEntry" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-bulletin-board</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.blackboard.noEntries') }}</p>
    </div>

    <!-- Latest Entry -->
    <div v-else class="blackboard-entry">
      <!-- Header with pin indicator -->
      <div class="entry-header d-flex align-center mb-2">
        <v-icon v-if="latestEntry.pinned" color="warning" size="small" class="mr-1">
          mdi-pin
        </v-icon>
        <span class="text-caption text-medium-emphasis">
          {{ formatDate(latestEntry.created_at) }}
        </span>
        <v-spacer />
        <v-chip v-if="latestEntry.area_name" size="x-small" color="primary">
          {{ latestEntry.area_name }}
        </v-chip>
        <v-chip v-else size="x-small" :color="getBoardTypeColor(latestEntry.board_type)">
          {{ $t(`dashboard.widget.blackboard.type.${latestEntry.board_type}`) }}
        </v-chip>
      </div>

      <!-- Title -->
      <h4 class="entry-title text-h6 mb-2">
        {{ latestEntry.title }}
      </h4>

      <!-- Content Preview -->
      <div class="entry-content text-body-2 mb-3" v-html="getContentPreview(latestEntry.content)" />

      <!-- Footer -->
      <div class="entry-footer d-flex align-center">
        <div class="author-info d-flex align-center">
          <v-avatar size="24" color="primary" class="mr-2">
            <span class="text-caption">{{ getInitials(latestEntry.author_name) }}</span>
          </v-avatar>
          <span class="text-caption">{{ latestEntry.author_name }}</span>
        </div>

        <v-spacer />

        <v-btn
          size="small"
          variant="text"
          color="primary"
          @click="goToBlackboard"
        >
          {{ $t('dashboard.widget.blackboard.viewAll') }}
          <v-icon end size="small">mdi-arrow-right</v-icon>
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Unter eigenem Namen, damit die oertliche Staffelung unten sie aufrufen
// kann, ohne sich selbst zu treffen.
import { formatDate as datumKurz } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'

interface BlackboardEntry {
  id: number
  title: string
  content: string
  author_name: string
  created_at: string
  board_type: string
  pinned: boolean
  area_id?: number | null
  area_name?: string | null
}

interface Props {
  widgetId: string
  config: {
    boardType?: string
    area_id?: number
    maxItems?: number
    pinnedOnly?: boolean
  }
}

const props = defineProps<Props>()
const router = useRouter()
const { t } = useI18n()

const loading = ref(true)
const error = ref('')
const latestEntry = ref<BlackboardEntry | null>(null)

/**
 * Load latest blackboard entry
 */
async function loadLatestEntry() {
  loading.value = true
  error.value = ''

  try {
    let url = '/blackboard/?action=getEntries'

    // Use area_id if specified, otherwise use boardType
    if (props.config.area_id) {
      url += `&area_id=${props.config.area_id}`
    } else {
      const boardType = props.config.boardType || 'all'
      url += `&boardType=${boardType}`
    }

    const response = await apiClientAuth.get(url)

    if (response.data && response.data.length > 0) {
      // Get the most recent entry (assuming sorted by date desc)
      latestEntry.value = response.data[0]
    }
  } catch (err: any) {
    console.error('Failed to load blackboard entry:', err)
    error.value = err.response?.data?.error || t('dashboard.widget.blackboard.loadError')
  } finally {
    loading.value = false
  }
}

/**
 * Format date
 */
function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffHours / 24)

  if (diffHours < 1) {
    return t('dashboard.widget.blackboard.justNow')
  } else if (diffHours < 24) {
    return t('dashboard.widget.blackboard.hoursAgo', { hours: diffHours })
  } else if (diffDays === 1) {
    return t('dashboard.widget.blackboard.yesterday')
  } else if (diffDays < 7) {
    return t('dashboard.widget.blackboard.daysAgo', { days: diffDays })
  } else {
    return datumKurz(date)
  }
}

/**
 * Get content preview (max 150 chars)
 */
function getContentPreview(content: string): string {
  const plainText = content.replace(/<[^>]*>/g, '')
  return plainText.length > 150 ? plainText.substring(0, 150) + '...' : plainText
}

/**
 * Get initials from name
 */
function getInitials(name: string): string {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

/**
 * Get board type color
 */
function getBoardTypeColor(boardType: string): string {
  switch (boardType) {
    case 'all': return 'primary'
    case 'employee': return 'info'
    case 'global': return 'success'
    default: return 'grey'
  }
}

/**
 * Navigate to blackboard
 */
function goToBlackboard() {
  // Navigate to area-specific board if area_id is set
  if (props.config.area_id) {
    router.push(`/blackboard/area/${props.config.area_id}`)
  } else if (latestEntry.value?.area_id) {
    // If entry is from an area, navigate to that area
    router.push(`/blackboard/area/${latestEntry.value.area_id}`)
  } else {
    // Default blackboard navigation
    router.push('/blackboard')
  }
}

// Load on mount
onMounted(() => {
  loadLatestEntry()
})

// Expose refresh method
defineExpose({
  refresh: loadLatestEntry
})
</script>

<style scoped lang="scss">
.blackboard-widget {
  height: 100%;
  overflow: hidden;
}

.blackboard-entry {
  padding: 0;
}

.entry-header {
  padding-bottom: 8px;
  border-bottom: 1px solid var(--k-line);
}

.entry-title {
  font-weight: 600;
  line-height: 1.3;
}

.entry-content {
  color: var(--k-ink-muted);
  line-height: 1.5;
  max-height: 100px;
  overflow: hidden;
}

.entry-footer {
  padding-top: 8px;
  border-top: 1px solid var(--k-line);
}

.author-info {
  color: var(--k-ink-muted);
}

.no-data,
.error-state {
  color: var(--k-ink-faint);
}
</style>
