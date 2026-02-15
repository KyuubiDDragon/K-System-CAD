<template>
  <v-container fluid class="pa-6">
    <!-- List View -->
    <div v-if="currentView === 'list'">
      <!-- Header with Actions -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h2 class="text-h5 font-weight-bold">Unternehmenspostfächer</h2>
            <p class="text-body-2 text-medium-emphasis">
              Verwalten Sie gemeinsame Postfächer für Teams und Abteilungen
            </p>
          </div>
          <v-btn
            v-if="canCreateMailbox"
            color="primary"
            variant="flat"
            prepend-icon="mdi-plus"
            @click="openCreateMailboxDialog"
          >
            Postfach erstellen
          </v-btn>
        </v-col>
      </v-row>

    <!-- Loading State -->
    <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

    <!-- Mailboxes Table -->
    <v-card v-if="mailboxes.length > 0">
      <v-data-table
        :headers="headers"
        :items="mailboxes"
        :items-per-page="10"
        :search="search"
      >
        <!-- Search -->
        <template v-slot:top>
          <v-toolbar flat>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Suchen"
              single-line
              hide-details
              clearable
              density="compact"
              style="max-width: 300px"
            ></v-text-field>
          </v-toolbar>
        </template>

        <!-- Email Column -->
        <template v-slot:item.email="{ item }">
          <div class="d-flex align-center">
            <v-icon left color="primary" class="mr-2">mdi-inbox</v-icon>
            <span class="font-weight-medium">{{ item.email }}</span>
          </div>
        </template>

        <!-- Type Column -->
        <template v-slot:item.type="{ item }">
          <v-chip :color="item.department ? 'secondary' : 'primary'" size="small">
            {{ item.department || 'Unternehmen' }}
          </v-chip>
        </template>

        <!-- Members Column -->
        <template v-slot:item.members="{ item }">
          <v-chip size="small">
            {{ getMemberCount(item) }} Mitglied{{ getMemberCount(item) !== 1 ? 'er' : '' }}
          </v-chip>
        </template>

        <!-- Permissions Column -->
        <template v-slot:item.permissions="{ item }">
          <v-chip-group>
            <v-chip v-if="item.permissions?.can_read" size="x-small" color="success">
              Lesen
            </v-chip>
            <v-chip v-if="item.permissions?.can_send" size="x-small" color="primary">
              Senden
            </v-chip>
            <v-chip v-if="item.permissions?.can_manage_settings" size="x-small" color="warning">
              Verwalten
            </v-chip>
          </v-chip-group>
        </template>

        <!-- Actions Column -->
        <template v-slot:item.actions="{ item }">
          <v-btn
            v-if="item.permissions?.can_manage_settings"
            icon="mdi-cog"
            size="small"
            variant="text"
            @click="openManageMailboxDialog(item)"
          >
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Empty State -->
    <v-card v-else-if="!loading" class="text-center pa-8" variant="outlined">
      <v-icon size="64" color="grey-lighten-1">mdi-inbox-multiple-outline</v-icon>
      <h3 class="text-h6 mt-4 mb-2">Keine Postfächer verfügbar</h3>
      <p class="text-body-2 text-medium-emphasis mb-4">
        <template v-if="factionDomains.length === 0">
          Sie benötigen mindestens eine Fraktionsdomain, um ein Unternehmenspostfach zu erstellen.
          Globale Domains wie "mail.ls" sind für Unternehmenspostfächer nicht verfügbar.
          Bitte kontaktieren Sie Ihren Administrator.
        </template>
        <template v-else>
          Sie haben noch keinen Zugriff auf Unternehmenspostfächer
        </template>
      </p>
      <v-btn
        v-if="canCreateMailbox"
        color="primary"
        variant="flat"
        prepend-icon="mdi-plus"
        @click="openCreateMailboxDialog"
      >
        Postfach erstellen
      </v-btn>
    </v-card>
    </div>

    <!-- Create Mailbox View -->
    <v-card v-else-if="currentView === 'create'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-plus</v-icon>
        <span class="text-h5">Neues Postfach erstellen</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="closeCreateMailboxDialog" :disabled="creating"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-form ref="createForm" v-model="createFormValid">
          <v-text-field
            v-model="createFormData.name"
            label="Name"
            prepend-icon="mdi-text"
            :rules="[rules.required]"
            hint="z.B. 'Kundensupport', 'Vertrieb'"
            persistent-hint
            required
          ></v-text-field>

          <v-row class="mt-4">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="createFormData.local_part"
                label="E-Mail-Adresse (vor dem @)"
                prepend-icon="mdi-at"
                :rules="[rules.required, rules.email_local]"
                hint="z.B. 'support', 'sales'"
                persistent-hint
                required
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="createFormData.domain_id"
                :items="factionDomains"
                item-title="domain"
                item-value="id"
                label="Domain (nur Fraktionsdomains)"
                prepend-icon="mdi-web"
                :rules="[rules.required]"
                hint="Nur Fraktionsdomains verfügbar"
                persistent-hint
                required
              ></v-select>
            </v-col>
          </v-row>

          <v-select
            v-model="createFormData.type"
            :items="mailboxTypes"
            item-title="text"
            item-value="value"
            label="Typ"
            prepend-icon="mdi-tag"
            class="mt-4"
            required
          ></v-select>

          <v-textarea
            v-model="createFormData.description"
            label="Beschreibung (optional)"
            prepend-icon="mdi-text-box"
            rows="3"
            class="mt-4"
          ></v-textarea>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="createMailbox"
          :loading="creating"
          :disabled="!createFormValid"
        >
          <v-icon start>mdi-check</v-icon>
          Erstellen
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Manage Mailbox View -->
    <v-card v-else-if="currentView === 'manage'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-cog</v-icon>
        <span class="text-h5">Postfach verwalten</span>
        <v-chip class="ml-2" size="small">{{ selectedMailbox?.email }}</v-chip>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="closeManageMailboxDialog"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-0">
          <v-tabs v-model="manageTab" bg-color="grey-lighten-4">
            <v-tab value="settings">
              <v-icon left>mdi-cog</v-icon>
              Einstellungen
            </v-tab>
            <v-tab value="members">
              <v-icon left>mdi-account-multiple</v-icon>
              Mitglieder
            </v-tab>
            <v-tab value="stats">
              <v-icon left>mdi-chart-bar</v-icon>
              Statistiken
            </v-tab>
          </v-tabs>

          <v-window v-model="manageTab">
            <!-- Settings Tab -->
            <v-window-item value="settings" class="pa-6">
              <v-form ref="settingsForm" v-model="settingsFormValid">
                <v-text-field
                  v-model="settingsFormData.name"
                  label="Name"
                  prepend-icon="mdi-text"
                  :rules="[rules.required]"
                ></v-text-field>

                <v-textarea
                  v-model="settingsFormData.description"
                  label="Beschreibung"
                  prepend-icon="mdi-text-box"
                  rows="3"
                ></v-textarea>

                <v-switch
                  v-model="settingsFormData.auto_reply_enabled"
                  label="Automatische Antwort aktivieren"
                  color="primary"
                ></v-switch>

                <v-textarea
                  v-if="settingsFormData.auto_reply_enabled"
                  v-model="settingsFormData.auto_reply_message"
                  label="Automatische Antwortnachricht"
                  rows="4"
                ></v-textarea>
              </v-form>
            </v-window-item>

            <!-- Members Tab -->
            <v-window-item value="members" class="pa-6">
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="toggleAddMemberForm"
                class="mb-4"
              >
                {{ showAddMemberForm ? 'Abbrechen' : 'Mitglied hinzufügen' }}
              </v-btn>

              <!-- Add Member Form (inline) -->
              <v-expand-transition>
                <v-card v-if="showAddMemberForm" variant="outlined" class="mb-4">
                  <v-card-text class="pa-4">
                    <v-autocomplete
                      v-model="selectedUserId"
                      :items="availableUsers"
                      item-title="name"
                      item-value="id"
                      label="Benutzer auswählen"
                      prepend-icon="mdi-account"
                      clearable
                    ></v-autocomplete>

                    <v-card variant="outlined" class="mt-4 pa-4">
                      <div class="text-subtitle-2 mb-3">Berechtigungen</div>
                      <v-checkbox
                        v-model="newMemberPermissions.can_read"
                        label="Lesen"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                      <v-checkbox
                        v-model="newMemberPermissions.can_send"
                        label="Senden"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                      <v-checkbox
                        v-model="newMemberPermissions.can_delete"
                        label="Löschen"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                      <v-checkbox
                        v-model="newMemberPermissions.can_assign"
                        label="Zuweisen"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                      <v-checkbox
                        v-model="newMemberPermissions.can_manage_members"
                        label="Mitglieder verwalten"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                      <v-checkbox
                        v-model="newMemberPermissions.can_manage_settings"
                        label="Einstellungen verwalten"
                        hide-details
                        density="compact"
                      ></v-checkbox>
                    </v-card>

                    <v-btn
                      color="primary"
                      variant="flat"
                      block
                      @click="addMember"
                      :loading="addingMember"
                      :disabled="!selectedUserId"
                      class="mt-4"
                    >
                      <v-icon start>mdi-plus</v-icon>
                      Hinzufügen
                    </v-btn>
                  </v-card-text>
                </v-card>
              </v-expand-transition>

              <v-data-table
                :headers="memberHeaders"
                :items="members"
                :items-per-page="5"
                density="compact"
              >
                <!-- User Column -->
                <template v-slot:item.user="{ item }">
                  <div class="d-flex align-center">
                    <v-avatar size="32" class="mr-2">
                      <v-icon>mdi-account-circle</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-medium">{{ item.user_name }}</div>
                      <div class="text-caption text-medium-emphasis">{{ item.user_email }}</div>
                    </div>
                  </div>
                </template>

                <!-- Permission Checkboxes -->
                <template v-slot:item.can_read="{ item }">
                  <v-checkbox
                    v-model="item.can_read"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <template v-slot:item.can_send="{ item }">
                  <v-checkbox
                    v-model="item.can_send"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <template v-slot:item.can_delete="{ item }">
                  <v-checkbox
                    v-model="item.can_delete"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <template v-slot:item.can_assign="{ item }">
                  <v-checkbox
                    v-model="item.can_assign"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <template v-slot:item.can_manage_members="{ item }">
                  <v-checkbox
                    v-model="item.can_manage_members"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <template v-slot:item.can_manage_settings="{ item }">
                  <v-checkbox
                    v-model="item.can_manage_settings"
                    @change="updateMemberPermissions(item)"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </template>

                <!-- Actions -->
                <template v-slot:item.actions="{ item }">
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    color="error"
                    @click="removeMember(item)"
                  >
                  </v-btn>
                </template>
              </v-data-table>
            </v-window-item>

            <!-- Statistics Tab -->
            <v-window-item value="stats" class="pa-6">
              <v-row>
                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Gesamte E-Mails</div>
                      <div class="text-h4">1,234</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Ungelesen</div>
                      <div class="text-h4">45</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-text>
                      <div class="text-caption text-medium-emphasis">Heute gesendet</div>
                      <div class="text-h4">23</div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-card variant="outlined" class="mt-4">
                <v-card-title>Aktivste Mitglieder</v-card-title>
                <v-card-text>
                  <v-list density="compact">
                    <v-list-item
                      v-for="i in 5"
                      :key="i"
                      :title="`Mitglied ${i}`"
                      :subtitle="`${Math.floor(Math.random() * 100)} E-Mails bearbeitet`"
                    >
                      <template v-slot:prepend>
                        <v-avatar size="32">
                          <v-icon>mdi-account-circle</v-icon>
                        </v-avatar>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-window-item>
          </v-window>
        </v-card-text>

        <v-divider></v-divider>

        <!-- Actions - Save button only shown for Settings tab -->
        <v-card-actions v-if="manageTab === 'settings'" class="pa-4">
          <v-btn
            color="primary"
            variant="flat"
            block
            @click="updateMailboxSettings"
            :loading="updatingSettings"
            :disabled="!settingsFormValid"
          >
            <v-icon start>mdi-content-save</v-icon>
            Speichern
          </v-btn>
        </v-card-actions>
      </v-card>

    <!-- Snackbar -->
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
import { useAuthStore } from '@/stores/auth'
import type { CompanyMailbox } from '@/types/Mail'
import { useModulePermission } from '@/composables/useModulePermission'

const mailStore = useMailStore()
const authStore = useAuthStore()

// State
const loading = ref(false)
const mailboxes = ref<CompanyMailbox[]>([])
const search = ref('')
const availableDomains = ref<any[]>([])

// View state
const currentView = ref<'list' | 'create' | 'manage'>('list')
const showAddMemberForm = ref(false)

// Forms
const createFormValid = ref(false)
const settingsFormValid = ref(false)

// Loading states
const creating = ref(false)
const updatingSettings = ref(false)
const addingMember = ref(false)

// Selected mailbox
const selectedMailbox = ref<CompanyMailbox | null>(null)
const manageTab = ref('settings')

// Members
const members = ref<any[]>([])
const availableUsers = ref<any[]>([])
const selectedUserId = ref<number | null>(null)

// Form data
const createFormData = ref({
  name: '',
  local_part: '',
  domain_id: null as number | null,
  type: 'company',
  description: ''
})

const settingsFormData = ref({
  name: '',
  description: '',
  auto_reply_enabled: false,
  auto_reply_message: ''
})

const newMemberPermissions = ref({
  can_read: true,
  can_send: false,
  can_delete: false,
  can_assign: false,
  can_manage_members: false,
  can_manage_settings: false
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Computed
const factionDomains = computed(() => {
  // Filter out global domains - only faction/company domains allowed
  // Use domain_type (mapped from backend's 'type' field in store)
  return availableDomains.value.filter(d => d.domain_type === 'faction')
})

const { hasAllPermissions, hasModulePermission } = useModulePermission()
const canCreateMailbox = computed(() => {
  // Check if user has admin permission for mail module or is system admin
  const hasPermission = hasModulePermission('mail', 'ADMIN') || hasAllPermissions.value
  const hasDomains = factionDomains.value.length > 0
  return hasPermission && hasDomains
})

// Table headers
const headers = [
  { title: 'E-Mail', key: 'email', sortable: true },
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Typ', key: 'type', sortable: false },
  { title: 'Mitglieder', key: 'members', sortable: false },
  { title: 'Ihre Berechtigungen', key: 'permissions', sortable: false },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
]

const memberHeaders = [
  { title: 'Benutzer', key: 'user', sortable: true },
  { title: 'Lesen', key: 'can_read', sortable: false, align: 'center' },
  { title: 'Senden', key: 'can_send', sortable: false, align: 'center' },
  { title: 'Löschen', key: 'can_delete', sortable: false, align: 'center' },
  { title: 'Zuweisen', key: 'can_assign', sortable: false, align: 'center' },
  { title: 'Mitglieder', key: 'can_manage_members', sortable: false, align: 'center' },
  { title: 'Einstellungen', key: 'can_manage_settings', sortable: false, align: 'center' },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
]

const mailboxTypes = [
  { text: 'Unternehmen', value: 'company' },
  { text: 'Fraktion', value: 'faction' }
]

// Validation rules
const rules = {
  required: (v: any) => !!v || 'Pflichtfeld',
  email_local: (v: string) => {
    const pattern = /^[a-zA-Z0-9._-]+$/
    return pattern.test(v) || 'Nur Buchstaben, Zahlen, Punkte, Bindestriche und Unterstriche'
  }
}

// Methods
function getMemberCount(mailbox: CompanyMailbox) {
  // This would come from the API
  return Math.floor(Math.random() * 20) + 1
}

async function fetchMailboxes() {
  loading.value = true
  try {
    const data = await mailStore.fetchCompanyMailboxes()
    if (data) {
      mailboxes.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Postfächer', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchAvailableDomains() {
  try {
    const data = await mailStore.fetchAvailableDomains()
    if (data) {
      availableDomains.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Domains', 'error')
  }
}

function openCreateMailboxDialog() {
  currentView.value = 'create'
}

function closeCreateMailboxDialog() {
  currentView.value = 'list'
  createFormData.value = {
    name: '',
    local_part: '',
    domain_id: null,
    type: 'company',
    description: ''
  }
}

async function createMailbox() {
  if (!createFormValid.value) return

  creating.value = true
  try {
    const domain = factionDomains.value.find(d => d.id === createFormData.value.domain_id)
    if (!domain) {
      showSnackbar('Bitte wählen Sie eine gültige Fraktionsdomain', 'error')
      return
    }

    const fullEmail = `${createFormData.value.local_part}@${domain.domain}`

    await mailStore.createMailbox({
      ...createFormData.value,
      email: fullEmail
    })
    showSnackbar('Postfach erfolgreich erstellt', 'success')
    closeCreateMailboxDialog()
    await fetchMailboxes()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Erstellen des Postfachs', 'error')
  } finally {
    creating.value = false
  }
}

async function openManageMailboxDialog(mailbox: CompanyMailbox) {
  selectedMailbox.value = mailbox
  settingsFormData.value = {
    name: mailbox.name,
    description: mailbox.description || '',
    auto_reply_enabled: mailbox.auto_reply_enabled,
    auto_reply_message: mailbox.auto_reply_message || ''
  }
  currentView.value = 'manage'
  await fetchMailboxMembers(mailbox.id)
}

function closeManageMailboxDialog() {
  currentView.value = 'list'
  selectedMailbox.value = null
  manageTab.value = 'settings'
  members.value = []
  showAddMemberForm.value = false
}

async function updateMailboxSettings() {
  if (!settingsFormValid.value || !selectedMailbox.value) return

  updatingSettings.value = true
  try {
    await mailStore.updateMailboxSettings(selectedMailbox.value.id, settingsFormData.value)
    showSnackbar('Einstellungen erfolgreich aktualisiert', 'success')
    await fetchMailboxes()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Aktualisieren der Einstellungen', 'error')
  } finally {
    updatingSettings.value = false
  }
}

async function fetchMailboxMembers(mailboxId: number) {
  try {
    const data = await mailStore.fetchMailboxMembers(mailboxId)
    if (data) {
      members.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Mitglieder', 'error')
  }
}

async function fetchAvailableUsers() {
  try {
    // This would fetch users from API
    availableUsers.value = [
      { id: 1, name: 'Max Mustermann' },
      { id: 2, name: 'Erika Musterfrau' }
    ]
  } catch (error) {
    showSnackbar('Fehler beim Laden der Benutzer', 'error')
  }
}

function toggleAddMemberForm() {
  showAddMemberForm.value = !showAddMemberForm.value
  if (showAddMemberForm.value) {
    fetchAvailableUsers()
  } else {
    selectedUserId.value = null
    newMemberPermissions.value = {
      can_read: true,
      can_send: false,
      can_delete: false,
      can_assign: false,
      can_manage_members: false,
      can_manage_settings: false
    }
  }
}

async function addMember() {
  if (!selectedUserId.value || !selectedMailbox.value) return

  addingMember.value = true
  try {
    await mailStore.addMailboxPermission(
      selectedMailbox.value.id,
      selectedUserId.value,
      newMemberPermissions.value
    )
    showSnackbar('Mitglied erfolgreich hinzugefügt', 'success')
    showAddMemberForm.value = false
    selectedUserId.value = null
    newMemberPermissions.value = {
      can_read: true,
      can_send: false,
      can_delete: false,
      can_assign: false,
      can_manage_members: false,
      can_manage_settings: false
    }
    await fetchMailboxMembers(selectedMailbox.value.id)
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Hinzufügen des Mitglieds', 'error')
  } finally {
    addingMember.value = false
  }
}

async function updateMemberPermissions(member: any) {
  if (!selectedMailbox.value) return

  try {
    await mailStore.updateMailboxPermission(
      selectedMailbox.value.id,
      member.user_id,
      {
        can_read: member.can_read,
        can_send: member.can_send,
        can_delete: member.can_delete,
        can_assign: member.can_assign,
        can_manage_members: member.can_manage_members,
        can_manage_settings: member.can_manage_settings
      }
    )
    showSnackbar('Berechtigungen aktualisiert', 'success')
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Aktualisieren der Berechtigungen', 'error')
  }
}

async function removeMember(member: any) {
  if (!selectedMailbox.value) return

  if (!confirm(`Möchten Sie ${member.user_name} wirklich entfernen?`)) return

  try {
    await mailStore.removeMailboxPermission(selectedMailbox.value.id, member.user_id)
    showSnackbar('Mitglied entfernt', 'success')
    await fetchMailboxMembers(selectedMailbox.value.id)
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Entfernen des Mitglieds', 'error')
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
  await Promise.all([
    fetchMailboxes(),
    fetchAvailableDomains()
  ])
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
