<template>
  <div class="vacation-calendar-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="vacations.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-calendar-blank</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.vacationCalendar.noVacations') }}</p>
    </div>
    <div v-else class="vacations-list">
      <div v-for="vacation in vacations" :key="vacation.id" class="vacation-item mb-2 pa-2">
        <div class="d-flex align-center">
          <v-avatar size="32" color="primary" class="mr-2">
            <span class="text-caption">{{ getInitials(vacation.employee_name) }}</span>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-body-2">{{ vacation.employee_name }}</div>
            <div class="text-caption text-medium-emphasis">
              {{ formatDate(vacation.start) }} - {{ formatDate(vacation.end) }}
            </div>
          </div>
          <v-icon :color="getTypeColor(vacation.type)">{{ getTypeIcon(vacation.type) }}</v-icon>
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
const vacations = ref<any[]>([])

async function loadVacations() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/employee/?action=getUpcomingVacations')
    vacations.value = response.data || []
  } catch (err) {
    console.error('Failed to load vacations:', err)
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
  return new Date(dateString).toLocaleDateString()
}

function getTypeIcon(type: string): string {
  const icons: Record<string, string> = {
    vacation: 'mdi-beach',
    sick: 'mdi-medical-bag',
    training: 'mdi-school',
  }
  return icons[type] || 'mdi-calendar'
}

function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    vacation: 'success',
    sick: 'error',
    training: 'info',
  }
  return colors[type] || 'grey'
}

onMounted(() => loadVacations())
defineExpose({ refresh: loadVacations })
</script>

<style scoped lang="scss">
.vacation-calendar-widget {
  height: 100%;
  overflow: hidden;
}

.vacations-list {
  padding: 0;
}

.vacation-item {
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.03);
}

.no-data {
  color: rgba(255, 255, 255, 0.5);
}
</style>
