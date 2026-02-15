<template>
  <div class="report-analytics-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else class="analytics-content">
      <div class="stats-grid mb-3">
        <div class="stat-card pa-2">
          <div class="text-h5">{{ analytics.total }}</div>
          <div class="text-caption">Total Reports</div>
        </div>
        <div class="stat-card pa-2">
          <div class="text-h5">{{ analytics.thisMonth }}</div>
          <div class="text-caption">This Month</div>
        </div>
        <div class="stat-card pa-2">
          <div class="text-h5">{{ analytics.open }}</div>
          <div class="text-caption">Open</div>
        </div>
        <div class="stat-card pa-2">
          <div class="text-h5">{{ analytics.closed }}</div>
          <div class="text-caption">Closed</div>
        </div>
      </div>
      <div class="categories">
        <div v-for="cat in analytics.byCategory" :key="cat.name" class="category-item d-flex align-center justify-space-between mb-1">
          <span class="text-caption">{{ cat.name }}</span>
          <v-chip size="x-small">{{ cat.count }}</v-chip>
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
const analytics = ref({ total: 0, thisMonth: 0, open: 0, closed: 0, byCategory: [] })

async function loadAnalytics() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/report/?action=getAnalytics')
    analytics.value = response.data || analytics.value
  } catch (err) {
    console.error('Failed to load analytics:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadAnalytics())
defineExpose({ refresh: loadAnalytics })
</script>

<style scoped lang="scss">
.report-analytics-widget {
  height: 100%;
  overflow: hidden;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.stat-card {
  text-align: center;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.05);
}

.categories {
  padding: 8px 0;
}

.category-item {
  padding: 4px 0;
}
</style>
