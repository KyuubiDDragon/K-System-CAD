<template>
  <v-container fluid class="pa-6">
    <!-- List View -->
    <div v-if="currentView === 'list'">
      <!-- Header with Actions -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h2 class="text-h5 font-weight-bold">Meine Mail-Konten</h2>
            <p class="text-body-2 text-medium-emphasis">
              Verwalten Sie Ihre persönlichen und verlinkten Mail-Konten
            </p>
          </div>
          <div class="d-flex gap-2">
            <v-btn
              color="primary"
              variant="flat"
              prepend-icon="mdi-plus"
              @click="currentView = 'create'"
            >
              Neues Konto erstellen
            </v-btn>
            <v-btn
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-link-variant"
              @click="currentView = 'link'"
            >
              Bestehendes Konto verlinken
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <!-- Loading State -->
      <v-row v-if="loading">
        <v-col cols="12">
          <v-skeleton-loader type="card, card"></v-skeleton-loader>
        </v-col>
      </v-row>

      <!-- Accounts List -->
      <v-row v-else-if="accounts.length > 0">
        <v-col
          v-for="account in accounts"
          :key="account.id"
          cols="12"
          md="6"
          lg="4"
        >
          <v-card elevation="2" hover>
            <v-card-title class="d-flex align-center">
              <v-icon left color="primary">mdi-email</v-icon>
              {{ account.email }}
            </v-card-title>

            <v-card-subtitle>
              <v-chip
                :color="accountTypeColor(account.account_type)"
                size="small"
                class="mr-2"
              >
                {{ accountTypeLabel(account.account_type) }}
              </v-chip>
              <v-chip
                v-if="account.current_user_id"
                color="success"
                size="small"
                class="mr-2"
              >
                <v-icon left size="small">mdi-check-circle</v-icon>
                Aktiv
              </v-chip>
              <v-chip
                v-if="account.is_locked"
                color="error"
                size="small"
              >
                <v-icon left size="small">mdi-lock</v-icon>
                Gesperrt
              </v-chip>
            </v-card-subtitle>

            <v-card-text>
              <!-- Storage Usage -->
              <div class="mb-4">
                <div class="d-flex justify-space-between mb-1">
                  <span class="text-caption">Speicher verwendet</span>
                  <span class="text-caption font-weight-bold">
                    {{ formatBytes(account.storage_used) }} / {{ formatBytes(account.storage_limit) }}
                  </span>
                </div>
                <v-progress-linear
                  :model-value="storagePercentage(account)"
                  :color="storageColor(account)"
                  height="8"
                  rounded
                ></v-progress-linear>
              </div>

              <!-- Last Login -->
              <div v-if="account.last_login_at" class="text-caption text-medium-emphasis">
                <v-icon size="small">mdi-clock-outline</v-icon>
                Letzter Login: {{ formatDate(account.last_login_at) }}
              </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
              <v-btn
                v-if="account.current_user_id"
                size="small"
                variant="text"
                color="warning"
                @click="openUnlinkDialog(account)"
              >
                Verknüpfung aufheben
              </v-btn>
              <v-spacer></v-spacer>
              <v-btn
                size="small"
                variant="text"
                icon="mdi-lock-reset"
                @click="openChangePasswordDialog(account)"
              >
              </v-btn>
              <v-btn
                size="small"
                variant="text"
                icon="mdi-cog"
                @click="openAccountSettingsDialog(account)"
              >
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-row v-else>
        <v-col cols="12">
          <v-card class="text-center pa-8" variant="outlined">
            <v-icon size="64" color="grey-lighten-1">mdi-email-off-outline</v-icon>
            <h3 class="text-h6 mt-4 mb-2">Noch keine Mail-Konten</h3>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Erstellen Sie ein neues Konto oder verlinken Sie ein bestehendes Konto
            </p>
            <v-btn
              color="primary"
              variant="flat"
              prepend-icon="mdi-plus"
              @click="openCreateAccountEditor"
            >
              Neues Konto erstellen
            </v-btn>
          </v-card>
        </v-col>
      </v-row>

      <!-- Inline Unlink Confirmation -->
      <v-expand-transition>
        <v-alert v-if="showUnlinkConfirm" type="warning" variant="tonal" class="mt-4">
          <v-alert-title>Verknüpfung aufheben?</v-alert-title>
          <div class="mt-2">
            <p class="mb-2">
              Möchten Sie die Verknüpfung zu <strong>{{ selectedAccount?.email }}</strong> wirklich aufheben?
            </p>
            <p class="text-caption text-medium-emphasis mb-2">
              Das Konto bleibt erhalten, ist aber nicht mehr mit Ihrem Profil verknüpft.
            </p>
            <v-textarea
              v-model="unlinkReason"
              label="Grund (optional)"
              rows="2"
              variant="outlined"
              density="compact"
              class="mt-2"
            ></v-textarea>
          </div>
          <div class="mt-3 d-flex gap-2">
            <v-btn size="small" @click="closeUnlinkDialog">Abbrechen</v-btn>
            <v-btn size="small" color="warning" @click="unlinkAccount" :loading="unlinking">Verknüpfung aufheben</v-btn>
          </div>
        </v-alert>
      </v-expand-transition>
    </div>

    <!-- Create Account View -->
    <div v-else-if="currentView === 'create'">
      <v-card elevation="0">
        <v-card-title class="d-flex align-center py-3 px-4">
          <v-icon class="mr-2">mdi-plus</v-icon>
          <span class="text-h5">Neues Mail-Konto erstellen</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" size="small" variant="text" @click="currentView = 'list'" :disabled="creating"></v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <v-form ref="createForm" v-model="createFormValid">
            <!-- Domain Selection -->
            <v-select
              v-model="createFormData.domain_id"
              :items="availableDomains"
              item-title="display_name"
              item-value="id"
              label="Domain"
              prepend-icon="mdi-at"
              :rules="[rules.required]"
              required
            ></v-select>

            <!-- Local Part (Username) -->
            <v-text-field
              v-model="createFormData.local_part"
              label="Benutzername (vor dem @)"
              prepend-icon="mdi-account"
              :rules="[rules.required, rules.email_local]"
              :suffix="selectedDomainSuffix"
              @blur="checkAvailability"
              required
            ></v-text-field>

            <!-- Display Name -->
            <v-text-field
              v-model="createFormData.display_name"
              label="Anzeigename"
              prepend-icon="mdi-card-account-details"
              hint="Dieser Name wird in Mails und im globalen Verzeichnis angezeigt"
              persistent-hint
              :rules="[rules.required]"
              required
            ></v-text-field>

            <!-- Availability Check -->
            <v-alert
              v-if="availabilityMessage"
              :type="availabilityStatus"
              density="compact"
              class="mb-4"
            >
              {{ availabilityMessage }}
            </v-alert>

            <!-- Suggestions -->
            <v-chip-group v-if="suggestions.length > 0" class="mb-4">
              <v-chip
                v-for="suggestion in suggestions"
                :key="suggestion"
                @click="createFormData.local_part = suggestion"
                variant="outlined"
                size="small"
              >
                {{ suggestion }}
              </v-chip>
            </v-chip-group>

            <!-- Password -->
            <v-text-field
              v-model="createFormData.password"
              label="Passwort"
              :type="showPassword ? 'text' : 'password'"
              prepend-icon="mdi-lock"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              :rules="[rules.required, rules.minLength(8)]"
              required
            ></v-text-field>

            <!-- Confirm Password -->
            <v-text-field
              v-model="createFormData.password_confirm"
              label="Passwort bestätigen"
              :type="showPassword ? 'text' : 'password'"
              prepend-icon="mdi-lock-check"
              :rules="[rules.required, rules.passwordMatch]"
              required
            ></v-text-field>

            <!-- Privacy Setting -->
            <v-switch
              v-model="createFormData.is_searchable"
              label="In globalem Verzeichnis anzeigen"
              color="primary"
              hint="Andere Benutzer können Ihre E-Mail-Adresse finden"
              persistent-hint
            ></v-switch>
          </v-form>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-btn
            color="primary"
            variant="flat"
            block
            @click="createAccount"
            :loading="creating"
            :disabled="!createFormValid"
          >
            <v-icon start>mdi-plus</v-icon>
            Konto erstellen
          </v-btn>
        </v-card-actions>
      </v-card>
    </div>

    <!-- Link Account View -->
    <v-card v-else-if="currentView === 'link'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-link-variant</v-icon>
        <span class="text-h5">Bestehendes Konto verlinken</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="currentView = 'list'" :disabled="linking"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-form ref="linkForm" v-model="linkFormValid">
          <v-text-field
            v-model="linkFormData.email"
            label="E-Mail-Adresse"
            type="email"
            prepend-icon="mdi-email"
            :rules="[rules.required, rules.email]"
            required
          ></v-text-field>

          <v-text-field
            v-model="linkFormData.password"
            label="Passwort"
            :type="showLinkPassword ? 'text' : 'password'"
            prepend-icon="mdi-lock"
            :append-inner-icon="showLinkPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showLinkPassword = !showLinkPassword"
            :rules="[rules.required]"
            required
          ></v-text-field>

          <v-alert v-if="linkError" type="error" density="compact" class="mt-2">
            {{ linkError }}
          </v-alert>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="linkAccount"
          :loading="linking"
          :disabled="!linkFormValid"
        >
          <v-icon start>mdi-link-variant</v-icon>
          Verlinken
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Change Password View -->
    <v-card v-else-if="currentView === 'password'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-lock-reset</v-icon>
        <span class="text-h5">Passwort ändern</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="currentView = 'list'" :disabled="changingPassword"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-form ref="passwordForm" v-model="passwordFormValid">
          <v-text-field
            v-model="passwordFormData.old_password"
            label="Aktuelles Passwort"
            :type="showOldPassword ? 'text' : 'password'"
            prepend-icon="mdi-lock"
            :append-inner-icon="showOldPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showOldPassword = !showOldPassword"
            :rules="[rules.required]"
            required
          ></v-text-field>

          <v-text-field
            v-model="passwordFormData.new_password"
            label="Neues Passwort"
            :type="showNewPassword ? 'text' : 'password'"
            prepend-icon="mdi-lock-plus"
            :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showNewPassword = !showNewPassword"
            :rules="[rules.required, rules.minLength(8)]"
            required
          ></v-text-field>

          <v-text-field
            v-model="passwordFormData.new_password_confirm"
            label="Neues Passwort bestätigen"
            :type="showNewPassword ? 'text' : 'password'"
            prepend-icon="mdi-lock-check"
            :rules="[rules.required, rules.newPasswordMatch]"
            required
          ></v-text-field>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="changePassword"
          :loading="changingPassword"
          :disabled="!passwordFormValid"
        >
          <v-icon start>mdi-lock-reset</v-icon>
          Passwort ändern
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Account Settings View -->
    <v-card v-else-if="currentView === 'settings'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-cog</v-icon>
        <span class="text-h5">Kontoeinstellungen</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="currentView = 'list'" :disabled="updatingSettings"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-form ref="settingsForm" v-model="settingsFormValid">
          <v-text-field
            v-model="settingsFormData.display_name"
            label="Anzeigename"
            prepend-icon="mdi-account"
            hint="Dieser Name wird in Mails und im globalen Verzeichnis angezeigt"
            persistent-hint
            :rules="[rules.required]"
            required
          ></v-text-field>

          <v-switch
            v-model="settingsFormData.is_searchable"
            label="In globalem Verzeichnis anzeigen"
            color="primary"
            hint="Andere Benutzer können Ihre E-Mail-Adresse finden"
            persistent-hint
          ></v-switch>

          <!-- Storage Quota (Read-only) -->
          <v-card variant="outlined" class="mt-4">
            <v-card-text>
              <div class="text-subtitle-2 mb-2">Speicherplatz</div>
              <div class="text-h6 mb-2">
                {{ formatBytes(selectedAccount?.storage_used || 0) }} /
                {{ formatBytes(selectedAccount?.storage_limit || 0) }}
              </div>
              <v-progress-linear
                :model-value="storagePercentage(selectedAccount)"
                :color="storageColor(selectedAccount)"
                height="8"
                rounded
              ></v-progress-linear>
            </v-card-text>
          </v-card>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="updateAccountSettings"
          :loading="updatingSettings"
          :disabled="!settingsFormValid"
        >
          <v-icon start>mdi-content-save</v-icon>
          Speichern
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Snackbar for notifications -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
    >
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">
          Schließen
        </v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useMailStore } from '@/stores/mail'
import type { MailAccount, MailDomain } from '@/types/Mail'

const mailStore = useMailStore()

// State
const loading = ref(false)
const accounts = ref<MailAccount[]>([])
const availableDomains = ref<MailDomain[]>([])

// View states
const currentView = ref<'list' | 'create' | 'link' | 'password' | 'settings'>('list')
const showUnlinkConfirm = ref(false)

// Form states
const createFormValid = ref(false)
const linkFormValid = ref(false)
const passwordFormValid = ref(false)
const settingsFormValid = ref(false)

// Loading states
const creating = ref(false)
const linking = ref(false)
const unlinking = ref(false)
const changingPassword = ref(false)
const updatingSettings = ref(false)

// Password visibility
const showPassword = ref(false)
const showLinkPassword = ref(false)
const showOldPassword = ref(false)
const showNewPassword = ref(false)

// Availability check
const isAvailable = ref(false)
const availabilityStatus = ref<'success' | 'error' | 'info'>('info')
const availabilityMessage = ref('')
const suggestions = ref<string[]>([])

// Selected account
const selectedAccount = ref<MailAccount | null>(null)

// Form data
const createFormData = ref({
  domain_id: null as number | null,
  local_part: '',
  display_name: '',
  password: '',
  password_confirm: '',
  is_searchable: true
})

const linkFormData = ref({
  email: '',
  password: ''
})
const linkError = ref('')

const unlinkReason = ref('')

const passwordFormData = ref({
  old_password: '',
  new_password: '',
  new_password_confirm: ''
})

const settingsFormData = ref({
  display_name: '',
  is_searchable: true
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Computed
const selectedDomainSuffix = computed(() => {
  const domain = availableDomains.value.find(d => d.id === createFormData.value.domain_id)
  return domain ? `@${domain.domain}` : ''
})

// Validation rules
const rules = {
  required: (v: any) => !!v || 'Pflichtfeld',
  email: (v: string) => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return pattern.test(v) || 'Ungültige E-Mail-Adresse'
  },
  email_local: (v: string) => {
    const pattern = /^[a-zA-Z0-9._-]+$/
    return pattern.test(v) || 'Nur Buchstaben, Zahlen, Punkte, Bindestriche und Unterstriche erlaubt'
  },
  minLength: (min: number) => (v: string) => {
    return (v && v.length >= min) || `Mindestens ${min} Zeichen erforderlich`
  },
  passwordMatch: (v: string) => {
    return v === createFormData.value.password || 'Passwörter stimmen nicht überein'
  },
  newPasswordMatch: (v: string) => {
    return v === passwordFormData.value.new_password || 'Passwörter stimmen nicht überein'
  }
}

// Methods
function accountTypeColor(type: string) {
  const colors: Record<string, string> = {
    personal: 'primary',
    company: 'secondary',
    faction: 'info'
  }
  return colors[type] || 'grey'
}

function accountTypeLabel(type: string) {
  const labels: Record<string, string> = {
    personal: 'Persönlich',
    company: 'Unternehmen',
    faction: 'Fraktion'
  }
  return labels[type] || type
}

function storagePercentage(account: MailAccount | null | undefined) {
  if (!account || !account.storage_limit) return 0
  return (account.storage_used / account.storage_limit) * 100
}

function storageColor(account: MailAccount | null | undefined) {
  const percentage = storagePercentage(account)
  if (percentage >= 90) return 'error'
  if (percentage >= 75) return 'warning'
  return 'success'
}

function formatBytes(bytes: number) {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

function formatDate(dateString: string) {
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function fetchMyAccounts() {
  loading.value = true
  try {
    const data = await mailStore.fetchMyAccounts()
    if (data) {
      accounts.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Konten', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchAvailableDomains() {
  console.log('[MailAccountsSettings] fetchAvailableDomains called')
  try {
    const data = await mailStore.fetchAvailableDomains()
    console.log('[MailAccountsSettings] fetchAvailableDomains response:', data)
    if (data) {
      availableDomains.value = data
      console.log('[MailAccountsSettings] availableDomains set to:', availableDomains.value)
    } else {
      console.warn('[MailAccountsSettings] No domains returned from API')
    }
  } catch (error) {
    console.error('[MailAccountsSettings] Error fetching domains:', error)
    showSnackbar('Fehler beim Laden der Domains', 'error')
  }
}

async function checkAvailability(): Promise<boolean> {
  if (!createFormData.value.local_part || !createFormData.value.domain_id) {
    isAvailable.value = false
    availabilityStatus.value = 'info'
    availabilityMessage.value = ''
    return false
  }

  try {
    const domain = availableDomains.value.find(d => d.id === createFormData.value.domain_id)
    if (!domain) {
      isAvailable.value = false
      availabilityStatus.value = 'info'
      availabilityMessage.value = ''
      return false
    }

    const email = `${createFormData.value.local_part}@${domain.domain}`
    const result = await mailStore.checkMailAvailability(email)

    if (result.available) {
      isAvailable.value = true
      availabilityStatus.value = 'success'
      availabilityMessage.value = 'Diese E-Mail-Adresse ist verfügbar'
      suggestions.value = []
      return true
    } else {
      isAvailable.value = false
      availabilityStatus.value = 'error'
      availabilityMessage.value = 'Diese E-Mail-Adresse ist bereits vergeben'
      suggestions.value = result.suggestions || []
      return false
    }
  } catch (error) {
    availabilityMessage.value = ''
    isAvailable.value = false
    return false
  }
}

function openCreateAccountEditor() {
  currentView.value = 'create'

  // Set default domain to mail.ls
  const mailLsDomain = availableDomains.value.find(d => d.domain === 'mail.ls')
  if (mailLsDomain) {
    createFormData.value.domain_id = mailLsDomain.id
  } else if (availableDomains.value.length > 0) {
    // Fallback to first available domain
    createFormData.value.domain_id = availableDomains.value[0].id
  }
}

function closeCreateAccountEditor() {
  currentView.value = 'list'
  resetCreateForm()
}

function resetCreateForm() {
  createFormData.value = {
    domain_id: null,
    local_part: '',
    display_name: '',
    password: '',
    password_confirm: '',
    is_searchable: true
  }
  isAvailable.value = false
  availabilityStatus.value = 'info'
  availabilityMessage.value = ''
  suggestions.value = []
}

async function createAccount() {
  if (!createFormValid.value) return

  // Check availability
  const available = await checkAvailability()
  if (!available) {
    showSnackbar('Diese E-Mail-Adresse ist bereits vergeben', 'error')
    return
  }

  creating.value = true
  try {
    const domain = availableDomains.value.find(d => d.id === createFormData.value.domain_id)
    if (!domain) return

    await mailStore.createAccount({
      email: `${createFormData.value.local_part}@${domain.domain}`,
      display_name: createFormData.value.display_name,
      password: createFormData.value.password,
      account_type: 'personal'
    })

    showSnackbar('Konto erfolgreich erstellt', 'success')
    closeCreateAccountEditor()
    await fetchMyAccounts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Erstellen des Kontos', 'error')
  } finally {
    creating.value = false
  }
}

function openLinkAccountDialog() {
  currentView.value = 'link'
}

function closeLinkAccountDialog() {
  currentView.value = 'list'
  linkFormData.value = { email: '', password: '' }
  linkError.value = ''
}

async function linkAccount() {
  if (!linkFormValid.value) return

  linking.value = true
  linkError.value = ''
  try {
    await mailStore.linkAccount(linkFormData.value)
    showSnackbar('Konto erfolgreich verlinkt', 'success')
    closeLinkAccountDialog()
    await fetchMyAccounts()
  } catch (error: any) {
    linkError.value = error.message || 'Fehler beim Verlinken des Kontos'
  } finally {
    linking.value = false
  }
}

function openUnlinkDialog(account: MailAccount) {
  selectedAccount.value = account
  showUnlinkConfirm.value = true
}

function closeUnlinkDialog() {
  showUnlinkConfirm.value = false
  selectedAccount.value = null
  unlinkReason.value = ''
}

async function unlinkAccount() {
  if (!selectedAccount.value) return

  unlinking.value = true
  try {
    await mailStore.unlinkAccount(selectedAccount.value.id, unlinkReason.value)
    showSnackbar('Verknüpfung erfolgreich aufgehoben', 'success')
    closeUnlinkDialog()
    await fetchMyAccounts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Aufheben der Verknüpfung', 'error')
  } finally {
    unlinking.value = false
  }
}

function openChangePasswordDialog(account: MailAccount) {
  selectedAccount.value = account
  currentView.value = 'password'
}

function closeChangePasswordDialog() {
  currentView.value = 'list'
  selectedAccount.value = null
  passwordFormData.value = {
    old_password: '',
    new_password: '',
    new_password_confirm: ''
  }
}

async function changePassword() {
  if (!passwordFormValid.value || !selectedAccount.value) return

  changingPassword.value = true
  try {
    await mailStore.changePassword(
      selectedAccount.value.id,
      passwordFormData.value.old_password,
      passwordFormData.value.new_password
    )
    showSnackbar('Passwort erfolgreich geändert', 'success')
    closeChangePasswordDialog()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Ändern des Passworts', 'error')
  } finally {
    changingPassword.value = false
  }
}

function openAccountSettingsDialog(account: MailAccount) {
  selectedAccount.value = account
  settingsFormData.value = {
    display_name: account.display_name || account.email.split('@')[0],
    // Convert database tinyint (0/1 or "0"/"1") to boolean
    is_searchable: account.is_searchable === 1 || account.is_searchable === '1' || account.is_searchable === true
  }
  currentView.value = 'settings'
}

function closeAccountSettingsDialog() {
  currentView.value = 'list'
  selectedAccount.value = null
}

async function updateAccountSettings() {
  if (!settingsFormValid.value || !selectedAccount.value) return

  updatingSettings.value = true
  try {
    await mailStore.updateMailSettings({
      mail_account_id: selectedAccount.value.id,
      display_name: settingsFormData.value.display_name,
      // Convert boolean to integer for backend
      is_searchable: settingsFormData.value.is_searchable ? 1 : 0
    })
    showSnackbar('Einstellungen erfolgreich aktualisiert', 'success')
    closeAccountSettingsDialog()
    await fetchMyAccounts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Aktualisieren der Einstellungen', 'error')
  } finally {
    updatingSettings.value = false
  }
}

function showSnackbar(message: string, color: string = 'success') {
  snackbar.value = {
    show: true,
    message,
    color
  }
}

// Lifecycle
onMounted(async () => {
  console.log('[MailAccountsSettings] Component mounted')
  await fetchAvailableDomains()
  await fetchMyAccounts()
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
