<template>
  <div class="employee-overview-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else class="overview-content">
      <div class="stats-grid">
        <div class="stat-card pa-3">
          <v-icon size="48" color="primary">mdi-account-group</v-icon>
          <div class="text-h4 mt-2">{{ stats.total }}</div>
          <div class="text-caption">Total Employees</div>
        </div>
        <div class="stat-card pa-3">
          <v-icon size="48" color="success">mdi-account-check</v-icon>
          <div class="text-h4 mt-2">{{ stats.active }}</div>
          <div class="text-caption">Active</div>
        </div>
        <div class="stat-card pa-3">
          <v-icon size="48" color="warning">mdi-beach</v-icon>
          <div class="text-h4 mt-2">{{ stats.onVacation }}</div>
          <div class="text-caption">On Vacation</div>
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

defineProps<Props>()

const loading = ref(true)
const stats = ref({ total: 0, active: 0, onVacation: 0 })

async function loadStats() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/employee/?action=getStats')
    stats.value = response.data || stats.value
  } catch (err) {
    console.error('Failed to load employee stats:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadStats())
defineExpose({ refresh: loadStats })
</script>

<style scoped lang="scss">
.employee-overview-widget {
  height: 100%;
  overflow: hidden;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-card {
  text-align: center;
  border-radius: 8px;
  background: var(--k-row-hover);
}
</style>
