<template>
  <div class="recent-documents-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="documents.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-file-document-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.recentDocuments.noDocuments') }}</p>
    </div>
    <div v-else class="documents-list">
      <div v-for="doc in documents" :key="doc.id" class="doc-item mb-2 pa-2" @click="openDocument(doc)">
        <div class="d-flex align-center">
          <v-icon :icon="getFileIcon(doc.type)" class="mr-2" />
          <div class="flex-grow-1">
            <div class="text-body-2">{{ doc.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ formatDate(doc.created_at) }}</div>
          </div>
        </div>
      </div>
      <v-btn block size="small" variant="text" color="primary" @click="goToDocuments">
        {{ $t('dashboard.widget.recentDocuments.viewAllDocuments') }}
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
const documents = ref<any[]>([])

async function loadDocuments() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/document/?action=getRecent')
    documents.value = (response.data || []).slice(0, 5)
  } catch (err) {
    console.error('Failed to load documents:', err)
  } finally {
    loading.value = false
  }
}

function getFileIcon(type: string): string {
  const icons: Record<string, string> = {
    pdf: 'mdi-file-pdf-box',
    doc: 'mdi-file-word-box',
    xls: 'mdi-file-excel-box',
    image: 'mdi-file-image-box',
  }
  return icons[type] || 'mdi-file-document-outline'
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}

function openDocument(doc: any) {
  window.open(doc.url, '_blank')
}

function goToDocuments() {
  router.push('/documents')
}

onMounted(() => loadDocuments())
defineExpose({ refresh: loadDocuments })
</script>

<style scoped lang="scss">
.recent-documents-widget {
  height: 100%;
  overflow: hidden;
}

.documents-list {
  padding: 0;
}

.doc-item {
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  
  &:hover {
    background: rgba(255, 255, 255, 0.08);
  }
}

.no-data {
  color: rgba(255, 255, 255, 0.5);
}
</style>
