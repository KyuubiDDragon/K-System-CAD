<template>
  <div class="vehicle-status-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="vehicles.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-car-off</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.vehicleStatus.noVehicles') }}</p>
    </div>
    <div v-else class="vehicles-list">
      <div v-for="vehicle in vehicles" :key="vehicle.id" class="vehicle-item mb-2 pa-2">
        <div class="d-flex align-center">
          <v-icon :color="getStatusColor(vehicle.status)" class="mr-2">mdi-car</v-icon>
          <div class="flex-grow-1">
            <div class="text-body-2 font-weight-medium">{{ vehicle.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ vehicle.type }}</div>
          </div>
          <v-chip size="x-small" :color="getStatusColor(vehicle.status)">
            {{ getStatusLabel(vehicle.status) }}
          </v-chip>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const { t: $t } = useI18n()
const loading = ref(true)
const vehicles = ref<any[]>([])

async function loadVehicles() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/vehicle/?action=getVehicles')
    vehicles.value = response.data || []
  } catch (err) {
    console.error('Failed to load vehicles:', err)
  } finally {
    loading.value = false
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    available: 'success',
    in_use: 'warning',
    maintenance: 'error',
  }
  return colors[status?.toLowerCase()] || 'grey'
}

function getStatusLabel(status: string): string {
  const statusMap: Record<string, string> = {
    available: 'available',
    in_use: 'inUse',
    maintenance: 'maintenance',
  }
  const key = statusMap[status?.toLowerCase()] || 'available'
  return $t(`dashboard.widget.vehicleStatus.${key}`)
}

onMounted(() => loadVehicles())
defineExpose({ refresh: loadVehicles })
</script>

<style scoped lang="scss">
.vehicle-status-widget {
  height: 100%;
  overflow: hidden;
}

.vehicles-list {
  padding: 0;
}

.vehicle-item {
  border-radius: 4px;
  background: var(--k-row-hover);
}

.no-data {
  color: var(--k-ink-faint);
}
</style>
