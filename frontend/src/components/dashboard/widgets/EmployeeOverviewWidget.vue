<template>
  <div class="employee-overview-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <!--
      Kennzahlen im Entwurfsstil: Beschriftung klein und gedimmt, die Zahl
      gross. Vorher dominierte ein 48-px-Symbol ueber jeder Zahl - das Symbol
      trug aber keine Information, die Zahl schon.
    -->
    <div v-else class="stats-grid">
      <div class="k-metric">
        <div class="k-metric__cap">{{ t('dashboard.widget.employeeOverview.total') }}</div>
        <div class="k-metric__val">{{ stats.total }}</div>
      </div>
      <div class="k-metric">
        <div class="k-metric__cap">{{ t('dashboard.widget.employeeOverview.active') }}</div>
        <div class="k-metric__val">
          {{ stats.active }}<span class="k-metric__unit"> / {{ stats.total }}</span>
        </div>
      </div>
      <div class="k-metric">
        <div class="k-metric__cap">{{ t('dashboard.widget.employeeOverview.onVacation') }}</div>
        <div class="k-metric__val">{{ stats.onVacation }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'
import { useI18n } from 'vue-i18n'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const loading = ref(true)
const { t } = useI18n()
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
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1px;
  background: var(--k-line, #e2e5ea);
}

.stat-card {
  text-align: center;
  border-radius: 8px;
  background: var(--k-row-hover);
}
</style>
