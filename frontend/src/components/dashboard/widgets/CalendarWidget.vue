<template>
  <div class="calendar-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="events.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-calendar-blank</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.calendar.noEvents') }}</p>
    </div>
    <div v-else class="events-list">
      <div v-for="event in events" :key="event.id" class="event-item mb-2 pa-2">
        <div class="d-flex align-center">
          <v-icon :color="event.color || 'primary'" class="mr-2">mdi-circle</v-icon>
          <div class="flex-grow-1">
            <div class="text-body-2 font-weight-medium">{{ event.title }}</div>
            <div class="text-caption text-medium-emphasis">
              <v-icon size="x-small">mdi-clock-outline</v-icon>
              {{ formatDateTime(event.start) }}
            </div>
          </div>
        </div>
      </div>
      <v-btn block size="small" variant="text" color="primary" @click="goToCalendar">
        {{ $t('dashboard.widget.calendar.viewCalendar') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatDateTime } from '@/utils/datetime';
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
const events = ref<any[]>([])

async function loadEvents() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/calendar/?action=getEvents')
    events.value = (response.data || []).slice(0, 5)
  } catch (err) {
    console.error('Failed to load events:', err)
  } finally {
    loading.value = false
  }
}

function formatDateTime(dateString: string): string {
  return formatDateTime(dateString)
}

function goToCalendar() {
  router.push('/calendar')
}

onMounted(() => loadEvents())
defineExpose({ refresh: loadEvents })
</script>

<style scoped lang="scss">
.calendar-widget {
  height: 100%;
  overflow: hidden;
}

.events-list {
  padding: 0;
}

.event-item {
  border-radius: 4px;
  background: var(--k-row-hover);
  
  &:hover {
    background: var(--k-row-hover);
  }
}

.no-data {
  color: var(--k-ink-faint);
}
</style>
