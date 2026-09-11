<template>
  <div class="system-health-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else class="health-content">
      <div class="health-status text-center mb-3">
        <v-icon :color="getHealthColor(health.status)" size="64">
          {{ getHealthIcon(health.status) }}
        </v-icon>
        <div class="text-h5 mt-2">{{ $t('dashboard.widget.systemHealth.system') }} {{ health.status }}</div>
      </div>
      <v-divider v-if="props.config?.showMetrics !== false" class="my-2" />
      <div v-if="props.config?.showMetrics !== false" class="metrics">
        <div class="metric-item d-flex align-center justify-space-between mb-2">
          <span class="text-body-2">{{ $t('dashboard.widget.systemHealth.cpuUsage') }}</span>
          <v-chip size="small" :color="getMetricColor(health.cpu)">{{ health.cpu }}%</v-chip>
        </div>
        <div class="metric-item d-flex align-center justify-space-between mb-2">
          <span class="text-body-2">{{ $t('dashboard.widget.systemHealth.memoryUsage') }}</span>
          <v-chip size="small" :color="getMetricColor(health.memory)">{{ health.memory }}%</v-chip>
        </div>
        <div class="metric-item d-flex align-center justify-space-between mb-2">
          <span class="text-body-2">{{ $t('dashboard.widget.systemHealth.diskUsage') }}</span>
          <v-chip size="small" :color="getMetricColor(health.disk)">{{ health.disk }}%</v-chip>
        </div>
        <div class="metric-item d-flex align-center justify-space-between">
          <span class="text-body-2">{{ $t('dashboard.widget.systemHealth.uptime') }}</span>
          <span class="text-caption">{{ health.uptime }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

const props = defineProps<Props>()

/*
   showMetrics aus der Vorlage wird jetzt beachtet: die Reihe aus Prozessor, Speicher, Platte und
   Laufzeit. Ohne die Angabe bleibt sie stehen, showMetrics: false zeigt nur
   den Gesamtzustand.
*/

const loading = ref(true)
const health = ref({ status: 'healthy', cpu: 0, memory: 0, disk: 0, uptime: '0d 0h' })

async function loadHealth() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/system/?action=getHealth')
    health.value = response.data || health.value
  } catch (err) {
    console.error('Failed to load system health:', err)
  } finally {
    loading.value = false
  }
}

function getHealthColor(status: string): string {
  const colors: Record<string, string> = {
    healthy: 'success',
    warning: 'warning',
    critical: 'error',
  }
  return colors[status] || 'grey'
}

function getHealthIcon(status: string): string {
  const icons: Record<string, string> = {
    healthy: 'mdi-check-circle',
    warning: 'mdi-alert',
    critical: 'mdi-close-circle',
  }
  return icons[status] || 'mdi-help-circle'
}

function getMetricColor(value: number): string {
  if (value < 60) return 'success'
  if (value < 80) return 'warning'
  return 'error'
}

onMounted(() => loadHealth())
defineExpose({ refresh: loadHealth })
</script>

<style scoped lang="scss">
.system-health-widget {
  height: 100%;
  overflow: hidden;
}

.health-status {
  text-transform: capitalize;
}

.metrics {
  padding: 8px 0;
}

.metric-item {
  padding: 4px 0;
}
</style>
