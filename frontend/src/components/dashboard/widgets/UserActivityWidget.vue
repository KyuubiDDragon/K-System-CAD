<template>
  <div class="user-activity-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else class="activity-content">
      <div class="stats-row d-flex justify-space-around mb-3">
        <div class="stat text-center">
          <div class="text-h5">{{ stats.logins }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.userActivity.loginsToday') }}</div>
        </div>
        <v-divider vertical />
        <div class="stat text-center">
          <div class="text-h5">{{ stats.active }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.userActivity.activeUsers') }}</div>
        </div>
        <v-divider vertical />
        <div class="stat text-center">
          <div class="text-h5">{{ stats.newUsers }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.userActivity.newUsers') }}</div>
        </div>
      </div>
      <v-divider class="my-2" />
      <div class="recent-activity">
        <div v-for="activity in activities" :key="activity.id" class="activity-item mb-2">
          <div class="d-flex align-center">
            <v-icon size="small" class="mr-2">{{ getActivityIcon(activity.type) }}</v-icon>
            <div class="flex-grow-1">
              <div class="text-caption">{{ activity.description }}</div>
              <div class="text-caption text-medium-emphasis">{{ formatTime(activity.timestamp) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatTime } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const loading = ref(true)
const stats = ref({ logins: 0, active: 0, newUsers: 0 })
const activities = ref<any[]>([])

async function loadActivity() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/system/?action=getUserActivity')
    stats.value = response.data?.stats || stats.value
    activities.value = response.data?.activities || []
  } catch (err) {
    console.error('Failed to load user activity:', err)
  } finally {
    loading.value = false
  }
}

function getActivityIcon(type: string): string {
  const icons: Record<string, string> = {
    login: 'mdi-login',
    logout: 'mdi-logout',
    create: 'mdi-plus-circle',
    update: 'mdi-pencil',
    delete: 'mdi-delete',
  }
  return icons[type] || 'mdi-circle-small'
}

function formatTime(timestamp: string): string {
  return formatTime(timestamp)
}

onMounted(() => loadActivity())
defineExpose({ refresh: loadActivity })
</script>

<style scoped lang="scss">
.user-activity-widget {
  height: 100%;
  overflow: hidden;
}

.stats-row {
  padding: 8px 0;
}

.stat {
  flex: 1;
}

.recent-activity {
  padding: 8px 0;
  max-height: 200px;
  overflow-y: auto;
}

.activity-item {
  padding: 4px 0;
}
</style>
