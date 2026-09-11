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
        <div v-for="activity in imZeitraum" :key="activity.id" class="activity-item mb-2">
          <div class="d-flex align-center">
            <v-icon size="small" class="mr-2">{{ getActivityIcon(activity.type) }}</v-icon>
            <div class="flex-grow-1">
              <div class="text-caption">{{ activity.username }}</div>
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
import { ref, computed, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

const props = defineProps<Props>()

const loading = ref(true)
const stats = ref({ logins: 0, active: 0, newUsers: 0 })
const activities = ref<any[]>([])

/*
   period aus der Vorlage wird jetzt beachtet.

   Die Vorlage "admin" setzt period: "24h" - die Liste soll zeigen, was
   zuletzt passiert ist, nicht die letzten zehn Anmeldungen, egal wie alt.
   Erlaubt sind Angaben der Form 24h, 7d oder 30d.

   Die drei Kennzahlen darueber bleiben unberuehrt: sie sind mit "heute",
   "gerade aktiv" und "diesen Monat" beschriftet und haben ihren eigenen
   Zeitbezug.
*/
const stundenAusZeitraum = () => {
    const roh = String(props.config?.period ?? '').trim().toLowerCase();
    const m = roh.match(/^(\d+)\s*([hd])$/);
    if (!m) return null;
    const zahl = Number(m[1]);
    if (!Number.isFinite(zahl) || zahl <= 0) return null;
    return m[2] === 'd' ? zahl * 24 : zahl;
};

const imZeitraum = computed(() => {
    const stunden = stundenAusZeitraum();
    if (stunden === null) return activities.value;

    const grenze = Date.now() - stunden * 3600 * 1000;
    return activities.value.filter((a: any) => {
        const z = new Date(a.timestamp).getTime();
        // Ohne lesbaren Zeitpunkt bleibt der Eintrag stehen.
        return isNaN(z) ? true : z >= grenze;
    });
});

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

/*
   Die Zeitangabe kommt aus utils/datetime.

   Hier stand eine gleichnamige oertliche Funktion, die nichts tat als sich
   selbst aufzurufen - der Baustein stuerzte beim Zeichnen mit
   "Maximum call stack size exceeded" ab, sobald eine Zeile mit Datum kam.
   Live nachgewiesen am Kalender-Baustein: vier Termine geladen, Kachel leer.
*/

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
