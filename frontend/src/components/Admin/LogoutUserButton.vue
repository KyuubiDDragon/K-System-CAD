<template>
  <v-btn
    :color="color"
    :variant="variant"
    :size="size"
    :icon="icon ? 'mdi-logout' : undefined"
    :prepend-icon="!icon ? 'mdi-logout' : undefined"
    @click="openDialog"
    :loading="loading"
  >
    <template v-if="!icon">
      {{ label }}
    </template>
  </v-btn>

  <!-- Confirmation Dialog -->
  <v-dialog v-model="showDialog" max-width="500">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon icon="mdi-logout" class="mr-2" color="error"></v-icon>
        Benutzer ausloggen
      </v-card-title>

      <v-card-text>
        <v-alert type="warning" variant="tonal" class="mb-4">
          Diese Aktion loggt <strong>{{ username }}</strong> von allen Geräten aus.
        </v-alert>

        <div v-if="sessionsLoading" class="text-center py-4">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
          <p class="text-caption mt-2">Lade aktive Sitzungen...</p>
        </div>

        <div v-else-if="activeSessions.length > 0">
          <p class="text-body-2 mb-2"><strong>{{ activeSessions.length }} aktive Sitzung(en):</strong></p>
          <v-list density="compact" class="mb-4">
            <v-list-item
              v-for="session in activeSessions.slice(0, 3)"
              :key="session.id"
              :prepend-icon="getDeviceIcon(session.device_type)"
            >
              <v-list-item-title class="text-body-2">{{ session.device_name }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ formatDate(session.last_activity) }}
              </v-list-item-subtitle>
            </v-list-item>
            <v-list-item v-if="activeSessions.length > 3">
              <v-list-item-subtitle class="text-caption">
                + {{ activeSessions.length - 3 }} weitere
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </div>

        <p v-else class="text-body-2 text-medium-emphasis">
          Keine aktiven Sitzungen gefunden.
        </p>

        <p class="text-body-2 mt-4">
          Möchten Sie fortfahren?
        </p>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn @click="showDialog = false" :disabled="loading">
          Abbrechen
        </v-btn>
        <v-btn
          color="error"
          @click="logoutUser"
          :loading="loading"
          :disabled="sessionsLoading"
        >
          Alle Sitzungen beenden
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useSessions } from '@/composables/useSessions'
import type { Session } from '@/types/Session'
import { formatDistanceToNow } from 'date-fns'
import { de } from 'date-fns/locale'

interface Props {
  userId: number
  username: string
  color?: string
  variant?: 'flat' | 'outlined' | 'text' | 'tonal'
  size?: 'small' | 'default' | 'large'
  icon?: boolean
  label?: string
}

const props = withDefaults(defineProps<Props>(), {
  color: 'error',
  variant: 'outlined',
  size: 'default',
  icon: false,
  label: 'Ausloggen'
})

const emit = defineEmits<{
  (e: 'success'): void
  (e: 'error', error: string): void
}>()

const { loadUserSessions, logoutAllUserSessions, loading } = useSessions()

const showDialog = ref(false)
const activeSessions = ref<Session[]>([])
const sessionsLoading = ref(false)

async function openDialog(): Promise<void> {
  showDialog.value = true
  sessionsLoading.value = true

  try {
    activeSessions.value = await loadUserSessions(props.userId)
  } catch (error) {
    console.error('Error loading sessions:', error)
  } finally {
    sessionsLoading.value = false
  }
}

async function logoutUser(): Promise<void> {
  const success = await logoutAllUserSessions(props.userId)

  if (success) {
    showDialog.value = false
    emit('success')
  } else {
    emit('error', 'Fehler beim Ausloggen des Benutzers')
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

function formatDate(dateString: string): string {
  try {
    const date = new Date(dateString)
    return formatDistanceToNow(date, { addSuffix: true, locale: de })
  } catch (e) {
    return dateString
  }
}
</script>
