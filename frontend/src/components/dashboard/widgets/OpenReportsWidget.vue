<template>
  <div class="open-reports-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="reports.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-file-document-check-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.openReports.noReports') }}</p>
    </div>
    <div v-else class="reports-list">
      <div v-for="report in reports" :key="report.id" class="report-item mb-2 pa-2" @click="openReport(report)">
        <div class="d-flex align-center">
          <v-icon color="warning" class="mr-2">mdi-file-document</v-icon>
          <div class="flex-grow-1">
            <div class="text-body-2 font-weight-medium">{{ report.title }}</div>
            <div class="text-caption text-medium-emphasis">{{ formatDate(report.created_at) }}</div>
          </div>
          <v-chip size="x-small" color="warning">{{ $t('dashboard.widget.openReports.open') }}</v-chip>
        </div>
      </div>
      <v-btn block size="small" variant="text" color="primary" @click="goToReports">
        {{ $t('dashboard.widget.openReports.viewAll') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatDate } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

const props = defineProps<Props>()

/*
   maxItems aus der Vorlage wird jetzt beachtet.

   Die Vorlagen setzen die Laenge bewusst unterschiedlich - der Zettel des
   Mitarbeiters zeigt eine Ankuendigung, das Leitstellen-Dashboard fuenf, die
   Nachrichten fuenf oder zehn. Der Baustein schnitt die Liste bisher auf eine
   fest verdrahtete Zahl zu und die Einstellung lief ins Leere.
*/
const grenze = () => {
    const n = Number(props.config?.maxItems);
    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 5;
};


const router = useRouter()
const loading = ref(true)
const reports = ref<any[]>([])

async function loadReports() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/report/?action=getOpenReports')
    reports.value = (response.data || []).slice(0, grenze())
  } catch (err) {
    console.error('Failed to load reports:', err)
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

function openReport(report: any) {
  router.push(`/report/${report.id}`)
}

function goToReports() {
  router.push('/report')
}

onMounted(() => loadReports())
defineExpose({ refresh: loadReports })
</script>

<style scoped lang="scss">
.open-reports-widget {
  height: 100%;
  overflow: hidden;
}

.reports-list {
  padding: 0;
}

.report-item {
  border-radius: 4px;
  background: var(--k-row-hover);
  cursor: pointer;
  
  &:hover {
    background: var(--k-row-hover);
  }
}

.no-data {
  color: var(--k-ink-faint);
}
</style>
