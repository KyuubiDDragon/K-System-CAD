<template>
  <v-card>
    <v-card-title>
      <v-icon icon="mdi-history" class="mr-2"></v-icon>
      Login-Verlauf
    </v-card-title>

    <v-card-subtitle class="text-caption">
      Zeigt alle Login-Aktivitäten der letzten 90 Tage für Sicherheits-Audits
    </v-card-subtitle>

    <v-card-text>
      <v-alert v-if="error" type="error" class="mb-4" closable>
        {{ error }}
      </v-alert>

      <!-- Suspicious Logins Warning -->
      <v-alert
        v-if="suspiciousLogins.length > 0"
        type="warning"
        variant="tonal"
        class="mb-4"
      >
        <v-alert-title>
          <v-icon icon="mdi-alert" class="mr-2"></v-icon>
          Verdächtige Logins erkannt
        </v-alert-title>
        {{ suspiciousLogins.length }} Login(s) von neuen IP-Adressen in den letzten 7 Tagen
      </v-alert>

      <div v-if="loading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
      </div>

      <div v-else-if="history.length === 0" class="text-center py-4 text-medium-emphasis">
        Keine Login-Historie gefunden
      </div>

      <v-list v-else lines="two">
        <v-list-item
          v-for="entry in paginatedHistory"
          :key="entry.id"
          :class="{
            'bg-warning-lighten-5': isSuspicious(entry)
          }"
        >
          <template v-slot:prepend>
            <v-avatar :color="getStatusColor(entry.status)">
              <v-icon>{{ getDeviceIcon(entry.device_type) }}</v-icon>
            </v-avatar>
          </template>

          <v-list-item-title>
            {{ entry.device_name }}
            <v-chip
              :color="getStatusColor(entry.status)"
              size="x-small"
              class="ml-2"
            >
              {{ getStatusText(entry.status) }}
            </v-chip>
          </v-list-item-title>

          <v-list-item-subtitle>
            <div>
              <strong>IP:</strong> {{ entry.ip_address }}
              <v-chip
                v-if="isSuspicious(entry)"
                color="warning"
                size="x-small"
                class="ml-2"
              >
                Neue IP
              </v-chip>
            </div>
            <div>
              <strong>Login:</strong> {{ formatDateTime(entry.login_time) }}
            </div>
            <div v-if="entry.status !== 'active'">
              <strong>Letzte Aktivität:</strong> {{ formatDateTime(entry.last_activity) }}
            </div>
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>

      <!-- Pagination -->
      <div v-if="history.length > itemsPerPage" class="d-flex justify-center mt-4">
        <v-pagination
          v-model="currentPage"
          :length="totalPages"
          :total-visible="5"
          density="compact"
        ></v-pagination>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api'
import { format } from 'date-fns'
import { de } from 'date-fns/locale'

interface LoginHistoryEntry {
  id: number
  device_name: string
  device_type: 'desktop' | 'mobile' | 'tablet'
  ip_address: string
  login_time: string
  last_activity: string
  expires_at: string
  is_active: number
  status: 'active' | 'logged_out' | 'expired'
}

interface SuspiciousLogin {
  id: number
  device_name: string
  ip_address: string
  created_at: string
}

const history = ref<LoginHistoryEntry[]>([])
const suspiciousLogins = ref<SuspiciousLogin[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

// Pagination
const currentPage = ref(1)
const itemsPerPage = 5

const totalPages = computed(() => Math.ceil(history.value.length / itemsPerPage))

const paginatedHistory = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return history.value.slice(start, end)
})

onMounted(async () => {
  await loadHistory()
})

async function loadHistory(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    // Load login history
    const historyResponse = await api.get<{ history: LoginHistoryEntry[] }>('/session/?action=getHistory')
    history.value = historyResponse.data.history

    // Load suspicious logins
    const suspiciousResponse = await api.get<{ suspicious: SuspiciousLogin[] }>('/session/?action=getSuspicious')
    suspiciousLogins.value = suspiciousResponse.data.suspicious
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Fehler beim Laden der Login-Historie'
    console.error('Error loading history:', err)
  } finally {
    loading.value = false
  }
}

function getDeviceIcon(deviceType: string): string {
  switch (deviceType) {
    case 'mobile':
      return 'mdi-cellphone'
    case 'tablet':
      return 'mdi-tablet'
    default:
      return 'mdi-monitor'
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'active':
      return 'success'
    case 'logged_out':
      return 'grey'
    case 'expired':
      return 'warning'
    default:
      return 'grey'
  }
}

function getStatusText(status: string): string {
  switch (status) {
    case 'active':
      return 'Aktiv'
    case 'logged_out':
      return 'Ausgeloggt'
    case 'expired':
      return 'Abgelaufen'
    default:
      return status
  }
}

function formatDateTime(dateString: string): string {
  try {
    const date = new Date(dateString)
    return format(date, 'dd.MM.yyyy HH:mm', { locale: de })
  } catch (e) {
    return dateString
  }
}

function isSuspicious(entry: LoginHistoryEntry): boolean {
  return suspiciousLogins.value.some(s => s.id === entry.id)
}
</script>
