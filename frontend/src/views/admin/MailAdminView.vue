<template>
  <v-container fluid class="pa-4">
    <!-- Breadcrumb -->
    <v-breadcrumbs :items="breadcrumbs" class="px-0 pb-4">
      <template v-slot:divider>
        <v-icon>mdi-chevron-right</v-icon>
      </template>
    </v-breadcrumbs>

    <!-- Page Title -->
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold mb-2">
          <v-icon left color="primary">mdi-shield-account</v-icon>
          Mail-System Administration
        </h1>
        <p class="text-subtitle-1 text-medium-emphasis mb-4">
          Zentrale Verwaltung aller Mail-Konten, Domains und Rechte
        </p>
      </v-col>
    </v-row>

    <!-- Tabs Navigation -->
    <v-card>
      <v-tabs
        v-model="activeTab"
        bg-color="primary"
        color="white"
        align-tabs="start"
        show-arrows
      >
        <v-tab value="dashboard">
          <v-icon left>mdi-view-dashboard</v-icon>
          Dashboard
        </v-tab>
        <v-tab value="accounts">
          <v-icon left>mdi-account-multiple</v-icon>
          Alle Accounts
        </v-tab>
        <v-tab value="domains">
          <v-icon left>mdi-domain</v-icon>
          Domains
        </v-tab>
        <v-tab value="mailboxes">
          <v-icon left>mdi-inbox-multiple</v-icon>
          Company Mailboxes
        </v-tab>
        <v-tab value="permissions">
          <v-icon left>mdi-shield-lock</v-icon>
          Berechtigungen
        </v-tab>
      </v-tabs>

      <v-divider></v-divider>

      <!-- Tab Content -->
      <v-window v-model="activeTab">
        <!-- Dashboard Tab -->
        <v-window-item value="dashboard">
          <v-container fluid class="pa-6">
            <!-- Statistics Cards -->
            <v-row v-if="!loadingStats">
              <v-col cols="12" sm="6" md="3">
                <v-card color="primary" dark>
                  <v-card-text>
                    <div class="text-h3 font-weight-bold">{{ stats.accounts?.total_accounts || 0 }}</div>
                    <div class="text-subtitle-1">Gesamt Accounts</div>
                    <div class="mt-2 text-caption">
                      {{ stats.accounts?.active_accounts || 0 }} aktiv
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card color="success" dark>
                  <v-card-text>
                    <div class="text-h3 font-weight-bold">{{ stats.mails?.total_mails || 0 }}</div>
                    <div class="text-subtitle-1">Gesamt Mails</div>
                    <div class="mt-2 text-caption">
                      {{ stats.mails?.sent_mails || 0 }} gesendet
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card color="info" dark>
                  <v-card-text>
                    <div class="text-h3 font-weight-bold">{{ formatBytes(stats.accounts?.total_storage_used || 0) }}</div>
                    <div class="text-subtitle-1">Speicher verwendet</div>
                    <div class="mt-2 text-caption">
                      von {{ formatBytes(stats.accounts?.total_storage_limit || 0) }} verfügbar
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card color="warning" dark>
                  <v-card-text>
                    <div class="text-h3 font-weight-bold">{{ stats.mailboxes?.total_mailboxes || 0 }}</div>
                    <div class="text-subtitle-1">Company Mailboxes</div>
                    <div class="mt-2 text-caption">
                      {{ stats.mailboxes?.active_mailboxes || 0 }} aktiv
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Domain Statistics -->
            <v-row class="mt-4">
              <v-col cols="12">
                <v-card>
                  <v-card-title>Domain-Übersicht</v-card-title>
                  <v-divider />
                  <v-card-text>
                    <v-table>
                      <thead>
                        <tr>
                          <th>Domain</th>
                          <th>Typ</th>
                          <th>Accounts</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="domain in stats.domains" :key="domain.domain">
                          <td>{{ domain.domain }}</td>
                          <td>
                            <v-chip :color="domain.domain_type === 'default' ? 'primary' : 'secondary'" size="small">
                              {{ domain.domain_type }}
                            </v-chip>
                          </td>
                          <td>{{ domain.account_count }}</td>
                        </tr>
                      </tbody>
                    </v-table>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-container>
        </v-window-item>

        <!-- All Accounts Tab -->
        <v-window-item value="accounts">
          <v-container fluid class="pa-6">
            <!-- Search and Filters -->
            <v-row class="mb-4">
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Suche nach Email oder User"
                  variant="outlined"
                  density="compact"
                  clearable
                  @input="debounceSearch"
                />
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="filterAccountType"
                  :items="accountTypes"
                  label="Account-Typ"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadAccounts"
                />
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="filterActive"
                  :items="activeOptions"
                  label="Status"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadAccounts"
                />
              </v-col>
              <v-col cols="12" md="4" class="d-flex justify-end align-center">
                <v-btn color="primary" prepend-icon="mdi-refresh" @click="loadAccounts">
                  Aktualisieren
                </v-btn>
              </v-col>
            </v-row>

            <!-- Accounts Table -->
            <v-card>
              <v-card-text>
                <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                <KTableToolbar :columns="kCols" :shown="(accounts || []).length" />
                <v-data-table
                  :headers="kCols.visible.value"
                  :items="accounts"
                  :loading="loadingAccounts"
                  :items-per-page="50"
                  :server-items-length="totalAccounts"
                  @update:options="loadAccountsWithPagination"
                 density="compact">
                  <!-- Email Column -->
                  <template v-slot:item.email="{ item }">
                    <div class="d-flex align-center">
                      <v-icon :color="item.is_active ? 'success' : 'grey'" size="small" class="mr-2">
                        {{ item.is_locked ? 'mdi-lock' : 'mdi-email' }}
                      </v-icon>
                      <span :class="{ 'text-decoration-line-through': !item.is_active }">
                        {{ item.email }}
                      </span>
                    </div>
                  </template>

                  <!-- User Column -->
                  <template v-slot:item.user="{ item }">
                    <template v-if="item.user_id">
                      {{ item.first_name }} {{ item.last_name }}
                      <div class="text-caption text-grey">@{{ item.username }}</div>
                    </template>
                    <span v-else class="text-grey">—</span>
                  </template>

                  <!-- Type Column -->
                  <template v-slot:item.account_type="{ item }">
                    <v-chip :color="item.account_type === 'personal' ? 'primary' : 'secondary'" size="small">
                      {{ item.account_type }}
                    </v-chip>
                  </template>

                  <!-- Storage Column -->
                  <template v-slot:item.storage="{ item }">
                    <div class="d-flex align-center">
                      <div style="min-width: 120px">
                        <v-progress-linear
                          :model-value="(item.storage_used / item.storage_limit) * 100"
                          :color="getStorageColor(item.storage_used, item.storage_limit)"
                          height="6"
                          rounded
                        />
                      </div>
                      <span class="ml-2 text-caption">
                        {{ formatBytes(item.storage_used) }} / {{ formatBytes(item.storage_limit) }}
                      </span>
                    </div>
                  </template>

                  <!-- Activity Column -->
                  <template v-slot:item.activity="{ item }">
                    <div class="text-caption">
                      <div>Gesendet: {{ item.sent_count || 0 }}</div>
                      <div>Empfangen: {{ item.received_count || 0 }}</div>
                    </div>
                  </template>

                  <!-- Actions Column -->
                  <template v-slot:item.actions="{ item }">
                    <v-menu>
                      <template v-slot:activator="{ props }">
                        <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
                      </template>
                      <v-list>
                        <v-list-item @click="openQuotaDialog(item)">
                          <template v-slot:prepend>
                            <v-icon>mdi-database</v-icon>
                          </template>
                          <v-list-item-title>Quota anpassen</v-list-item-title>
                        </v-list-item>
                        <v-list-item @click="toggleLockAccount(item)">
                          <template v-slot:prepend>
                            <v-icon :color="item.is_locked ? 'success' : 'warning'">
                              {{ item.is_locked ? 'mdi-lock-open' : 'mdi-lock' }}
                            </v-icon>
                          </template>
                          <v-list-item-title>
                            {{ item.is_locked ? 'Entsperren' : 'Sperren' }}
                          </v-list-item-title>
                        </v-list-item>
                        <v-list-item @click="toggleActiveAccount(item)">
                          <template v-slot:prepend>
                            <v-icon :color="item.is_active ? 'error' : 'success'">
                              {{ item.is_active ? 'mdi-toggle-switch-off' : 'mdi-toggle-switch' }}
                            </v-icon>
                          </template>
                          <v-list-item-title>
                            {{ item.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                          </v-list-item-title>
                        </v-list-item>
                        <v-divider />
                        <v-list-item @click="openDeleteDialog(item)" class="text-error">
                          <template v-slot:prepend>
                            <v-icon color="error">mdi-delete</v-icon>
                          </template>
                          <v-list-item-title>Löschen</v-list-item-title>
                        </v-list-item>
                      </v-list>
                    </v-menu>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-container>
        </v-window-item>

        <!-- Domains Tab -->
        <v-window-item value="domains">
          <v-container fluid class="pa-6">
            <v-card>
              <v-card-title>Domain-Verwaltung</v-card-title>
              <v-divider />
              <v-card-text>
                <v-alert type="info" variant="tonal" class="mb-4">
                  Die Domain-Verwaltung erfolgt über die Mail-Einstellungen.
                  Hier sehen Sie eine Übersicht aller aktiven Domains.
                </v-alert>
                <v-table>
                  <thead>
                    <tr>
                      <th>Domain</th>
                      <th>Typ</th>
                      <th>Accounts</th>
                      <th>Max Accounts</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="domain in stats.domains" :key="domain.domain">
                      <td class="font-weight-bold">{{ domain.domain }}</td>
                      <td>
                        <v-chip :color="domain.domain_type === 'default' ? 'primary' : 'secondary'" size="small">
                          {{ domain.domain_type === 'default' ? 'Standard' : 'Faction' }}
                        </v-chip>
                      </td>
                      <td>{{ domain.account_count }}</td>
                      <td>—</td>
                      <td>
                        <v-chip color="success" size="small">Aktiv</v-chip>
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card-text>
            </v-card>
          </v-container>
        </v-window-item>

        <!-- Company Mailboxes Tab -->
        <v-window-item value="mailboxes">
          <v-container fluid class="pa-6">
            <v-card>
              <v-card-title>Company Mailboxes Übersicht</v-card-title>
              <v-divider />
              <v-card-text>
                <v-alert type="info" variant="tonal">
                  Die Verwaltung von Company Mailboxes erfolgt über die Mail-Einstellungen unter
                  <router-link to="/mail/settings">/mail/settings → Unternehmenspostfächer</router-link>.
                  Dort können Sie Postfächer erstellen und Berechtigungen verwalten.
                </v-alert>
              </v-card-text>
            </v-card>
          </v-container>
        </v-window-item>

        <!-- Permissions Tab -->
        <v-window-item value="permissions">
          <v-container fluid class="pa-6">
            <v-card>
              <v-card-title>Mail-Berechtigungen Übersicht</v-card-title>
              <v-divider />
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <h3 class="mb-3">Verfügbare Permissions</h3>
                    <v-list>
                      <v-list-item>
                        <template v-slot:prepend>
                          <v-icon color="info">mdi-eye</v-icon>
                        </template>
                        <v-list-item-title>READ_MAIL</v-list-item-title>
                        <v-list-item-subtitle>Mails lesen und empfangen</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item>
                        <template v-slot:prepend>
                          <v-icon color="success">mdi-pencil</v-icon>
                        </template>
                        <v-list-item-title>WRITE_MAIL</v-list-item-title>
                        <v-list-item-subtitle>Mails senden und verwalten</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item>
                        <template v-slot:prepend>
                          <v-icon color="warning">mdi-shield-account</v-icon>
                        </template>
                        <v-list-item-title>ADMIN_MAIL</v-list-item-title>
                        <v-list-item-subtitle>Unternehmenspostfächer verwalten</v-list-item-subtitle>
                      </v-list-item>
                      <v-list-item>
                        <template v-slot:prepend>
                          <v-icon color="error">mdi-delete</v-icon>
                        </template>
                        <v-list-item-title>DELETE_MAIL</v-list-item-title>
                        <v-list-item-subtitle>Mails permanent löschen</v-list-item-subtitle>
                      </v-list-item>
                    </v-list>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-alert type="info" variant="tonal">
                      <h4 class="mb-2">Rechte-Verwaltung</h4>
                      <p class="mb-2">
                        Mail-Berechtigungen werden über das Rollen-System verwaltet.
                      </p>
                      <p class="mb-0">
                        Navigieren Sie zu
                        <router-link to="/admin/roles">Verwaltung → Rollen</router-link>
                        um Berechtigungen zu verwalten.
                      </p>
                    </v-alert>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-container>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- Quota Dialog -->
    <v-dialog v-model="quotaDialog" max-width="500px">
      <v-card>
        <v-card-title>Speicher-Quota anpassen</v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p class="mb-4">Account: <strong>{{ selectedAccount?.email }}</strong></p>
          <v-text-field
            v-model.number="newQuota"
            label="Neues Limit (in MB)"
            type="number"
            variant="outlined"
            suffix="MB"
            :hint="`Aktuell: ${formatBytes(selectedAccount?.storage_limit || 0)}`"
            persistent-hint
          />
        </v-card-text>
        <v-divider />
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="quotaDialog = false">Abbrechen</v-btn>
          <v-btn color="primary" variant="elevated" @click="saveQuota">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title class="text-error">
          <v-icon color="error" class="mr-2">mdi-alert</v-icon>
          Account permanent löschen?
        </v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <v-alert type="error" variant="tonal" class="mb-4">
            <strong>Warnung:</strong> Diese Aktion kann nicht rückgängig gemacht werden!
          </v-alert>
          <p class="mb-2">Account: <strong>{{ selectedAccount?.email }}</strong></p>
          <p class="mb-4">Zum Bestätigen geben Sie "DELETE" ein:</p>
          <v-text-field
            v-model="deleteConfirmation"
            label="Bestätigung"
            variant="outlined"
            placeholder="DELETE"
          />
        </v-card-text>
        <v-divider />
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Abbrechen</v-btn>
          <v-btn
            color="error"
            variant="elevated"
            :disabled="deleteConfirmation !== 'DELETE'"
            @click="deleteAccount"
          >
            Permanent Löschen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar for notifications -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
      {{ snackbarText }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">Schließen</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';

import { ref, onMounted, computed, unref } from 'vue';
import { useRoute } from 'vue-router'

// Route
const route = useRoute()

// Active tab state
const activeTab = ref('dashboard')

// Breadcrumbs
const breadcrumbs = computed(() => [
  { title: 'Administration', disabled: false, to: '/admin/settings' },
  { title: 'Mail-System', disabled: true }
])

// Stats
const stats = ref<any>({})
const loadingStats = ref(false)

// Accounts
const accounts = ref<any[]>([])
const loadingAccounts = ref(false)
const totalAccounts = ref(0)
const searchQuery = ref('')
const filterAccountType = ref('')
const filterActive = ref('')

// Dialogs
const quotaDialog = ref(false)
const deleteDialog = ref(false)
const selectedAccount = ref<any>(null)
const newQuota = ref(0)
const deleteConfirmation = ref('')

// Snackbar
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Options
const accountTypes = [
  { title: 'Personal', value: 'personal' },
  { title: 'Company', value: 'company' }
]

const activeOptions = [
  { title: 'Aktiv', value: '1' },
  { title: 'Inaktiv', value: '0' }
]

const accountHeaders = [
  { title: 'Email', key: 'email', sortable: true },
  { title: 'User', key: 'user', sortable: false },
  { title: 'Typ', key: 'account_type', sortable: true },
  { title: 'Speicher', key: 'storage', sortable: false },
  { title: 'Aktivität', key: 'activity', sortable: false },
  { title: 'Erstellt', key: 'created_at', sortable: true, align: 'end' },
  { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
]

// Methods
async function loadStats() {
  loadingStats.value = true
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/?action=getMailSystemStats`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      stats.value = data.stats || {}
    }
  } catch (error) {
    console.error('Error loading stats:', error)
  } finally {
    loadingStats.value = false
  }
}

async function loadAccounts(pagination: any = {}) {
  loadingAccounts.value = true
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const params = new URLSearchParams({
      action: 'getAllMailAccountsAdmin',
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(filterAccountType.value && { account_type: filterAccountType.value }),
      ...(filterActive.value && { is_active: filterActive.value }),
      ...(pagination.itemsPerPage && { limit: pagination.itemsPerPage.toString() }),
      ...(pagination.page && { offset: ((pagination.page - 1) * pagination.itemsPerPage).toString() })
    })

    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/?${params}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      accounts.value = data.accounts || []
      totalAccounts.value = data.total || 0
    }
  } catch (error) {
    console.error('Error loading accounts:', error)
  } finally {
    loadingAccounts.value = false
  }
}

function loadAccountsWithPagination(options: any) {
  loadAccounts(options)
}

let debounceTimer: any = null
function debounceSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadAccounts()
  }, 500)
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

function getStorageColor(used: number, limit: number): string {
  const percentage = (used / limit) * 100
  if (percentage >= 90) return 'error'
  if (percentage >= 75) return 'warning'
  return 'success'
}

function openQuotaDialog(account: any) {
  selectedAccount.value = account
  newQuota.value = Math.round(account.storage_limit / 1024 / 1024)
  quotaDialog.value = true
}

async function saveQuota() {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'updateAccountQuota',
        account_id: selectedAccount.value.id,
        storage_limit: newQuota.value * 1024 * 1024
      })
    })

    if (response.ok) {
      showSnackbar('Quota erfolgreich aktualisiert', 'success')
      quotaDialog.value = false
      loadAccounts()
    } else {
      showSnackbar('Fehler beim Aktualisieren der Quota', 'error')
    }
  } catch (error) {
    console.error('Error updating quota:', error)
    showSnackbar('Fehler beim Aktualisieren der Quota', 'error')
  }
}

async function toggleLockAccount(account: any) {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'lockUnlockAccount',
        account_id: account.id,
        is_locked: !account.is_locked,
        reason: account.is_locked ? 'Unlocked by admin' : 'Locked by admin'
      })
    })

    if (response.ok) {
      showSnackbar(
        account.is_locked ? 'Account entsperrt' : 'Account gesperrt',
        'success'
      )
      loadAccounts()
    } else {
      showSnackbar('Fehler beim Ändern des Account-Status', 'error')
    }
  } catch (error) {
    console.error('Error toggling lock:', error)
    showSnackbar('Fehler beim Ändern des Account-Status', 'error')
  }
}

async function toggleActiveAccount(account: any) {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'updateAccountSettingsAdmin',
        account_id: account.id,
        is_active: !account.is_active
      })
    })

    if (response.ok) {
      showSnackbar(
        account.is_active ? 'Account deaktiviert' : 'Account aktiviert',
        'success'
      )
      loadAccounts()
    } else {
      showSnackbar('Fehler beim Ändern des Account-Status', 'error')
    }
  } catch (error) {
    console.error('Error toggling active:', error)
    showSnackbar('Fehler beim Ändern des Account-Status', 'error')
  }
}

function openDeleteDialog(account: any) {
  selectedAccount.value = account
  deleteConfirmation.value = ''
  deleteDialog.value = true
}

async function deleteAccount() {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'deleteAccountAdmin',
        account_id: selectedAccount.value.id,
        confirm: 'DELETE'
      })
    })

    if (response.ok) {
      showSnackbar('Account erfolgreich gelöscht', 'success')
      deleteDialog.value = false
      loadAccounts()
      loadStats()
    } else {
      showSnackbar('Fehler beim Löschen des Accounts', 'error')
    }
  } catch (error) {
    console.error('Error deleting account:', error)
    showSnackbar('Fehler beim Löschen des Accounts', 'error')
  }
}

function showSnackbar(text: string, color: string = 'success') {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

// Lifecycle
onMounted(async () => {
  await loadStats()
  await loadAccounts()

  // Set initial tab from route query
  if (route.query.tab) {
    activeTab.value = route.query.tab as string
  }
})

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/MailAdminView', () => unref(accountHeaders) as any);
</script>

<style scoped>
.v-table th {
  font-weight: 600 !important;
}
</style>
