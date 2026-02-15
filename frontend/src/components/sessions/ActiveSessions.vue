<template>
  <v-card>
    <v-card-title>
      <v-icon icon="mdi-devices" class="mr-2"></v-icon>
      Aktive Geräte
    </v-card-title>

    <v-card-text>
      <v-alert v-if="error" type="error" class="mb-4" closable>
        {{ error }}
      </v-alert>

      <div v-if="loading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
      </div>

      <div v-else-if="sessions.length === 0" class="text-center py-4 text-medium-emphasis">
        Keine aktiven Sitzungen gefunden
      </div>

      <v-list v-else>
        <v-list-item
          v-for="session in sessions"
          :key="session.id"
          :class="{ 'bg-primary-lighten-5': session.is_current }"
        >
          <template v-slot:prepend>
            <v-avatar :color="session.is_current ? 'primary' : 'grey'">
              <v-icon>
                {{ getDeviceIcon(session.device_type) }}
              </v-icon>
            </v-avatar>
          </template>

          <v-list-item-title>
            {{ session.device_name }}
            <v-chip
              v-if="session.is_current"
              size="x-small"
              color="primary"
              class="ml-2"
            >
              Aktuell
            </v-chip>
          </v-list-item-title>

          <v-list-item-subtitle>
            IP: {{ session.ip_address }} •
            Letzte Aktivität: {{ formatDate(session.last_activity) }}
          </v-list-item-subtitle>

          <template v-slot:append>
            <v-btn
              icon="mdi-logout"
              size="small"
              color="error"
              variant="text"
              @click="confirmLogout(session)"
              :loading="logoutLoading === session.id"
            >
            </v-btn>
          </template>
        </v-list-item>
      </v-list>

      <v-divider class="my-4"></v-divider>

      <v-btn
        v-if="sessions.length > 1"
        color="warning"
        variant="outlined"
        prepend-icon="mdi-logout-variant"
        @click="confirmLogoutOthers"
        :loading="logoutAllLoading"
        block
      >
        Alle anderen Geräte ausloggen
      </v-btn>
    </v-card-text>

    <!-- Confirmation Dialog -->
    <v-dialog v-model="showConfirmDialog" max-width="400">
      <v-card>
        <v-card-title>Ausloggen bestätigen</v-card-title>
        <v-card-text>
          {{ confirmMessage }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showConfirmDialog = false">Abbrechen</v-btn>
          <v-btn color="error" @click="executeLogout">Ausloggen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSessions } from '@/composables/useSessions'
import type { Session } from '@/types/Session'
import { formatDistanceToNow } from 'date-fns'
import { de } from 'date-fns/locale'

const { sessions, loading, error, loadSessions, logoutSession, logoutOtherSessions } = useSessions()

const logoutLoading = ref<number | null>(null)
const logoutAllLoading = ref(false)
const showConfirmDialog = ref(false)
const confirmMessage = ref('')
const pendingAction = ref<'session' | 'others' | null>(null)
const pendingSessionId = ref<number | null>(null)

onMounted(async () => {
  await loadSessions()
})

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

function formatDate(dateString: string): string {
  try {
    const date = new Date(dateString)
    return formatDistanceToNow(date, { addSuffix: true, locale: de })
  } catch (e) {
    return dateString
  }
}

function confirmLogout(session: Session): void {
  pendingAction.value = 'session'
  pendingSessionId.value = session.id
  if (session.is_current) {
    confirmMessage.value = `Möchten Sie sich von diesem Gerät (aktuelle Sitzung) "${session.device_name}" wirklich ausloggen? Sie werden zum Login weitergeleitet.`
  } else {
    confirmMessage.value = `Möchten Sie das Gerät "${session.device_name}" wirklich ausloggen?`
  }
  showConfirmDialog.value = true
}

function confirmLogoutOthers(): void {
  pendingAction.value = 'others'
  confirmMessage.value = `Möchten Sie alle anderen Geräte (${sessions.value.length - 1}) wirklich ausloggen?`
  showConfirmDialog.value = true
}

async function executeLogout(): Promise<void> {
  showConfirmDialog.value = false

  if (pendingAction.value === 'session' && pendingSessionId.value) {
    logoutLoading.value = pendingSessionId.value
    const success = await logoutSession(pendingSessionId.value)
    logoutLoading.value = null

    if (success) {
      // Session was removed from list automatically
    }
  } else if (pendingAction.value === 'others') {
    logoutAllLoading.value = true
    await logoutOtherSessions()
    logoutAllLoading.value = false
  }

  pendingAction.value = null
  pendingSessionId.value = null
}
</script>
