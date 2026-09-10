<template>
  <div class="recent-logs-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="logs.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-text-box-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.recentLogs.noLogs') }}</p>
    </div>
    <div v-else class="logs-list">
      <div v-for="log in logs" :key="log.id" class="log-item mb-1 pa-2">
        <div class="d-flex align-center">
          <v-icon :color="getLevelColor(log.level)" size="small" class="mr-2">
            {{ getLevelIcon(log.level) }}
          </v-icon>
          <div class="flex-grow-1">
            <div class="text-caption">{{ log.message }}</div>
            <div class="text-caption text-medium-emphasis">{{ formatTime(log.timestamp) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatDateTime } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const loading = ref(true)
const logs = ref<any[]>([])

async function loadLogs() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/system/?action=getRecentLogs')
    logs.value = (response.data || []).slice(0, 10)
  } catch (err) {
    console.error('Failed to load logs:', err)
  } finally {
    loading.value = false
  }
}

function getLevelColor(level: string): string {
  const colors: Record<string, string> = {
    error: 'error',
    warning: 'warning',
    info: 'info',
    debug: 'grey',
  }
  return colors[level?.toLowerCase()] || 'grey'
}

function getLevelIcon(level: string): string {
  const icons: Record<string, string> = {
    error: 'mdi-alert-circle',
    warning: 'mdi-alert',
    info: 'mdi-information',
    debug: 'mdi-bug',
  }
  return icons[level?.toLowerCase()] || 'mdi-circle-small'
}

function formatTime(timestamp: string): string {
  return formatDateTime(timestamp)
}

onMounted(() => loadLogs())
defineExpose({ refresh: loadLogs })
</script>

<style scoped lang="scss">
.recent-logs-widget {
  height: 100%;
  overflow: hidden;
}

.logs-list {
  padding: 0;
  font-family: monospace;
}

.log-item {
  border-radius: 4px;
  background: var(--k-row-hover);
  font-size: 11px;
}

.no-data {
  color: var(--k-ink-faint);
}
</style>
