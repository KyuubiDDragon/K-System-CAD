<template>
  <div class="quick-dispatch-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else class="dispatch-content">
      <div class="stats-grid mb-3">
        <div class="stat-card pa-2">
          <div class="text-h4">{{ stats.activeCrews }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.dispatch.activeCrews') }}</div>
        </div>
        <div class="stat-card pa-2">
          <div class="text-h4">{{ stats.availableVehicles }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.dispatch.availableVehicles') }}</div>
        </div>
        <div class="stat-card pa-2">
          <div class="text-h4">{{ stats.activeIncidents }}</div>
          <div class="text-caption">{{ $t('dashboard.widget.dispatch.activeIncidents') }}</div>
        </div>
      </div>
      <v-btn block color="primary" @click="goToDispatch">
        <v-icon start>mdi-truck-fast</v-icon>
        {{ $t('dashboard.widget.dispatch.openDispatchCenter') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const router = useRouter()
const loading = ref(true)
const stats = ref({ activeCrews: 0, availableVehicles: 0, activeIncidents: 0 })

async function loadDispatchStats() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/dispatch/?action=getStats')
    stats.value = response.data || stats.value
  } catch (err) {
    console.error('Failed to load dispatch stats:', err)
  } finally {
    loading.value = false
  }
}

function goToDispatch() {
  router.push('/dispatch')
}

onMounted(() => loadDispatchStats())
defineExpose({ refresh: loadDispatchStats })
</script>

<style scoped lang="scss">
.quick-dispatch-widget {
  height: 100%;
  overflow: hidden;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.stat-card {
  text-align: center;
  border-radius: 4px;
  background: var(--k-row-hover);
}
</style>
