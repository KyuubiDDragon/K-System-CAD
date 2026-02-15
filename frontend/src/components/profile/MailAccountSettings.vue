<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-email-outline</v-icon>
      Mail-Konto verknüpfen
    </v-card-title>

    <v-card-text>
      <!-- Current linked mail account -->
      <div v-if="linkedMailAccount" class="mb-4">
        <v-alert type="success" variant="tonal" class="mb-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="font-weight-bold">{{ linkedMailAccount.email }}</div>
              <div class="text-caption">Verknüpft am: {{ formatDate(linkedMailAccount.linked_at) }}</div>
            </div>
            <v-btn
              color="error"
              variant="tonal"
              size="small"
              @click="showUnlinkDialog = true"
            >
              Trennen
            </v-btn>
          </div>
        </v-alert>

        <!-- Storage info -->
        <v-card variant="outlined" class="mb-4">
          <v-card-text>
            <div class="text-caption text-medium-emphasis mb-2">Speicher</div>
            <v-progress-linear
              :model-value="storagePercentage"
              :color="storageColor"
              height="20"
              rounded
            >
              <template v-slot:default>
                <strong>{{ formatSize(linkedMailAccount.storage_used) }} / {{ formatSize(linkedMailAccount.storage_limit) }}</strong>
              </template>
            </v-progress-linear>
          </v-card-text>
        </v-card>

        <!-- Quick actions -->
        <div class="d-flex gap-2">
          <v-btn
            color="primary"
            variant="outlined"
            prepend-icon="mdi-email"
            :to="`/mail`"
          >
            Mail öffnen
          </v-btn>
          <v-btn
            color="primary"
            variant="outlined"
            prepend-icon="mdi-cog"
            :to="`/mail/settings`"
          >
            Mail-Einstellungen
          </v-btn>
        </div>
      </div>

      <!-- No linked account -->
      <div v-else>
        <v-alert type="info" variant="tonal" class="mb-4">
          Kein Mail-Konto verknüpft. Sie können ein bestehendes Konto verknüpfen oder ein neues erstellen.
        </v-alert>

        <div class="d-flex gap-2">
          <v-btn
            color="primary"
            prepend-icon="mdi-link"
            @click="showLinkDialog = true"
          >
            Konto verknüpfen
          </v-btn>
          <v-btn
            color="primary"
            variant="outlined"
            prepend-icon="mdi-plus"
            :to="`/mail/settings?tab=accounts&action=create`"
          >
            Neues Konto erstellen
          </v-btn>
        </div>
      </div>
    </v-card-text>

    <!-- Link Account Dialog -->
    <v-dialog v-model="showLinkDialog" max-width="500">
      <v-card>
        <v-card-title>Mail-Konto verknüpfen</v-card-title>
        <v-card-text>
          <v-form ref="linkForm" v-model="linkFormValid" @submit.prevent="linkAccount">
            <v-text-field
              v-model="linkForm.email"
              label="E-Mail-Adresse"
              type="email"
              :rules="[v => !!v || 'E-Mail ist erforderlich']"
              required
            />
            <v-text-field
              v-model="linkForm.password"
              label="Passwort"
              type="password"
              :rules="[v => !!v || 'Passwort ist erforderlich']"
              required
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showLinkDialog = false">Abbrechen</v-btn>
          <v-btn
            color="primary"
            :loading="linking"
            :disabled="!linkFormValid"
            @click="linkAccount"
          >
            Verknüpfen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Unlink Account Dialog -->
    <v-dialog v-model="showUnlinkDialog" max-width="500">
      <v-card>
        <v-card-title>Mail-Konto trennen</v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" class="mb-4">
            Das Mail-Konto wird von Ihrem Profil getrennt, aber nicht gelöscht. Sie können es später wieder verknüpfen.
          </v-alert>
          <v-textarea
            v-model="unlinkReason"
            label="Grund (optional)"
            rows="3"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showUnlinkDialog = false">Abbrechen</v-btn>
          <v-btn
            color="error"
            :loading="unlinking"
            @click="unlinkAccount"
          >
            Trennen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { apiClientAuth } from '@/api'

const toast = useToast()

// State
const linkedMailAccount = ref<any>(null)
const showLinkDialog = ref(false)
const showUnlinkDialog = ref(false)
const linkFormValid = ref(false)
const linking = ref(false)
const unlinking = ref(false)
const unlinkReason = ref('')

const linkForm = ref({
  email: '',
  password: ''
})

// Computed
const storagePercentage = computed(() => {
  if (!linkedMailAccount.value) return 0
  return (linkedMailAccount.value.storage_used / linkedMailAccount.value.storage_limit) * 100
})

const storageColor = computed(() => {
  const pct = storagePercentage.value
  if (pct > 90) return 'error'
  if (pct > 75) return 'warning'
  return 'success'
})

// Methods
async function fetchLinkedAccount() {
  try {
    const response = await apiClientAuth.post('/mail/', {
      action: 'getMyMailAccounts'
    })
    // Find the currently linked account
    const accounts = response.data.accounts || []
    linkedMailAccount.value = accounts.find((acc: any) => acc.is_current_user_link) || null
  } catch (error) {
    console.error('Failed to fetch linked mail account:', error)
  }
}

async function linkAccount() {
  if (!linkFormValid.value) return

  linking.value = true
  try {
    await apiClientAuth.post('/mail/', {
      action: 'linkMailAccount',
      email: linkForm.value.email,
      password: linkForm.value.password
    })

    toast.success('Mail-Konto erfolgreich verknüpft')
    showLinkDialog.value = false
    linkForm.value = { email: '', password: '' }
    await fetchLinkedAccount()
  } catch (error: any) {
    toast.error(error.response?.data?.error || 'Fehler beim Verknüpfen des Kontos')
  } finally {
    linking.value = false
  }
}

async function unlinkAccount() {
  if (!linkedMailAccount.value) return

  unlinking.value = true
  try {
    await apiClientAuth.post('/mail/', {
      action: 'unlinkMailAccount',
      mail_account_id: linkedMailAccount.value.id,
      reason: unlinkReason.value || 'user_request'
    })

    toast.success('Mail-Konto erfolgreich getrennt')
    showUnlinkDialog.value = false
    unlinkReason.value = ''
    linkedMailAccount.value = null
  } catch (error: any) {
    toast.error(error.response?.data?.error || 'Fehler beim Trennen des Kontos')
  } finally {
    unlinking.value = false
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

// Lifecycle
onMounted(() => {
  fetchLinkedAccount()
})
</script>

<style scoped>
.gap-2 {
  gap: 0.5rem;
}
</style>
