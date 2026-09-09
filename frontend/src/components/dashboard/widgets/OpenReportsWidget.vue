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
const reports = ref<any[]>([])

async function loadReports() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/report/?action=getOpenReports')
    reports.value = (response.data || []).slice(0, 5)
  } catch (err) {
    console.error('Failed to load reports:', err)
  } finally {
    loading.value = false
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}

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
