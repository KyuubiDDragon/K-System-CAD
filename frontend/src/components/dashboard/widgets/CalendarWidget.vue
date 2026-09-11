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

const props = defineProps<Props>()

const router = useRouter()
const loading = ref(true)
const events = ref<any[]>([])

/*
   daysAhead und maxItems aus der Vorlage werden jetzt beachtet.

   Der Baustein heisst in den Vorlagen "Meine Termine" und bekommt dort
   daysAhead: 7 mit - er soll die naechste Woche zeigen, nicht die naechsten
   fuenf Eintraege, egal wie weit die in der Zukunft liegen. Beides lief
   bisher ins Leere: geladen wurde alles und stumpf auf fuenf gekuerzt.
*/
const grenze = () => {
    const n = Number(props.config?.maxItems);
    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 5;
};

const imZeitfenster = (alle: any[]) => {
    const tage = Number(props.config?.daysAhead);
    if (!Number.isFinite(tage) || tage <= 0) return alle;

    const grenzwert = new Date();
    grenzwert.setDate(grenzwert.getDate() + Math.floor(tage));
    grenzwert.setHours(23, 59, 59, 999);

    return alle.filter((e) => {
        const start = new Date(e.start);
        // Was sich nicht lesen laesst, bleibt lieber stehen als zu verschwinden.
        return isNaN(start.getTime()) ? true : start <= grenzwert;
    });
};

async function loadEvents() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/calendar/?action=getEvents')
    events.value = imZeitfenster(response.data || []).slice(0, grenze())
  } catch (err) {
    console.error('Failed to load events:', err)
  } finally {
    loading.value = false
  }
}

/*
   Die Zeitangabe kommt aus utils/datetime.

   Hier stand eine gleichnamige oertliche Funktion, die nichts tat als sich
   selbst aufzurufen - der Baustein stuerzte beim Zeichnen mit
   "Maximum call stack size exceeded" ab, sobald eine Zeile mit Datum kam.
   Live nachgewiesen am Kalender-Baustein: vier Termine geladen, Kachel leer.
*/

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
