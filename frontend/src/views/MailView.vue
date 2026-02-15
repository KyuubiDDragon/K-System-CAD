<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMailStore } from '@/stores/mail'
import { useMailSocket, requestNotificationPermission } from '@/composables/useMailSocket'
import MailFolderSidebar from '@/components/mail/MailFolderSidebar.vue'
import MailList from '@/components/mail/MailList.vue'
import MailDetail from '@/components/mail/MailDetail.vue'
import type { MailWithRecipient } from '@/types/Mail'

// Router
const route = useRoute()
const router = useRouter()

// Store
const mailStore = useMailStore()

// Socket connection for real-time mail updates
const { connected: socketConnected, notifyMailRead, notifyMailStarred } = useMailSocket()

// State
const selectedMailIds = ref<number[]>([])
const showCompose = ref(false)
const searchQuery = ref('')
const isMobile = ref(false)
const showSidebar = ref(true)
const showDetail = ref(false)
const hasMailAccounts = ref(true)
const checkingAccounts = ref(true)
const availableDomains = ref<any[]>([])
const selectedFromDomain = ref('mail.ls')
const composeRecipient = ref('')
const composeSubject = ref('')
const composeMessage = ref('')

// Computed
const filteredMails = computed(() => {
  if (!searchQuery.value) return mailStore.currentMails

  const query = searchQuery.value.toLowerCase()
  return mailStore.currentMails.filter(mail =>
    mail.subject.toLowerCase().includes(query) ||
    mail.from_name?.toLowerCase().includes(query) ||
    mail.body_text?.toLowerCase().includes(query)
  )
})

const showList = computed(() => {
  if (!isMobile.value) return true
  return !showDetail.value
})

const selectedMailForDetail = computed(() => mailStore.selectedMail)

// Methods
function handleMailSelect(mail: MailWithRecipient) {
  mailStore.selectedMail = mail

  // Mark as read when opened
  if (!mail.is_read) {
    mailStore.markAsRead(mail.id, true)
    // Notify other tabs via socket
    notifyMailRead(mail.id)
  }

  // On mobile, show detail view
  if (isMobile.value) {
    showDetail.value = true
  }
}

function handleBackToList() {
  showDetail.value = false
  mailStore.selectedMail = null
}

function handleFolderChange(folder: string) {
  mailStore.setCurrentFolder(folder)
  loadCurrentFolder()

  // Clear selection
  selectedMailIds.value = []
  mailStore.selectedMail = null
  showDetail.value = false
}

async function handleCompose() {
  showCompose.value = true
  mailStore.selectedMail = null

  // Fetch available domains
  await fetchAvailableDomains()

  // Reset compose form
  composeRecipient.value = ''
  composeSubject.value = ''
  composeMessage.value = ''

  // Set default domain to mail.ls or first available
  if (availableDomains.value.length > 0) {
    const mailLsDomain = availableDomains.value.find(d => d.domain === 'mail.ls')
    selectedFromDomain.value = mailLsDomain ? mailLsDomain.domain : availableDomains.value[0].domain
  } else {
    selectedFromDomain.value = 'mail.ls'
  }
}

async function fetchAvailableDomains() {
  try {
    const domains = await mailStore.fetchAvailableDomains()
    availableDomains.value = domains || []

    // Always ensure mail.ls is in the list
    if (!availableDomains.value.find(d => d.domain === 'mail.ls')) {
      availableDomains.value.unshift({
        domain: 'mail.ls',
        domain_type: 'default',
        display_name: 'mail.ls'
      })
    }
  } catch (error) {
    console.error('Error fetching domains:', error)
    // Fallback to mail.ls
    availableDomains.value = [{
      domain: 'mail.ls',
      domain_type: 'default',
      display_name: 'mail.ls'
    }]
  }
}

function handleBulkDelete() {
  if (selectedMailIds.value.length === 0) return

  mailStore.bulkAction({
    mail_ids: selectedMailIds.value,
    action: 'delete'
  }).then(() => {
    selectedMailIds.value = []
  })
}

function handleBulkMarkRead(isRead: boolean) {
  if (selectedMailIds.value.length === 0) return

  mailStore.bulkAction({
    mail_ids: selectedMailIds.value,
    action: isRead ? 'mark_read' : 'mark_unread'
  }).then(() => {
    selectedMailIds.value = []
  })
}

function handleBulkMove(folderId: number | null) {
  if (selectedMailIds.value.length === 0) return

  mailStore.bulkAction({
    mail_ids: selectedMailIds.value,
    action: 'move',
    folder_id: folderId
  }).then(() => {
    selectedMailIds.value = []
  })
}

async function loadCurrentFolder() {
  const folder = mailStore.currentFolder

  switch (folder) {
    case 'inbox':
      await mailStore.fetchInbox()
      break
    case 'sent':
      await mailStore.fetchSent()
      break
    case 'drafts':
      await mailStore.fetchDrafts()
      break
    case 'starred':
      await mailStore.fetchStarred()
      break
    case 'trash':
      await mailStore.fetchTrash()
      break
    default:
      // Custom folder - fetch inbox which includes all mails
      await mailStore.fetchInbox()
      break
  }
}

function checkMobile() {
  isMobile.value = window.innerWidth < 960
}

async function checkMailAccounts() {
  checkingAccounts.value = true
  try {
    const accounts = await mailStore.fetchMyAccounts()
    hasMailAccounts.value = accounts && accounts.length > 0
  } catch (error) {
    console.error('Error checking mail accounts:', error)
    hasMailAccounts.value = false
  } finally {
    checkingAccounts.value = false
  }
}

function navigateToSettings() {
  router.push('/mail/settings')
}

// Lifecycle
onMounted(async () => {
  checkMobile()
  window.addEventListener('resize', checkMobile)

  // Request notification permission
  requestNotificationPermission()

  // Check if user has mail accounts
  await checkMailAccounts()

  // Only load mails if user has accounts
  if (hasMailAccounts.value) {
    // Load folders
    await mailStore.fetchFolders()

    // Load initial view (inbox by default)
    await loadCurrentFolder()

    // Check if there's a mail ID in the route
    const mailId = route.query.mailId
    if (mailId) {
      const mail = mailStore.mails.find(m => m.id === Number(mailId))
      if (mail) {
        handleMailSelect(mail)
      }
    }
  }
})

// Watch for folder changes
watch(() => mailStore.currentFolder, () => {
  loadCurrentFolder()
})
</script>

<template>
  <v-container fluid class="mail-view-container pa-0 ma-0 fill-height">
    <v-row no-gutters class="fill-height">
      <!-- Folder Sidebar - Only show if user has mail accounts -->
      <v-col
        v-if="hasMailAccounts && !checkingAccounts && (!isMobile || (isMobile && !showDetail))"
        :cols="isMobile ? 12 : 2"
        class="sidebar-col"
      >
        <MailFolderSidebar
          @folder-change="handleFolderChange"
          @compose="handleCompose"
          @navigate-to-settings="navigateToSettings"
        />
      </v-col>

      <!-- Mail List OR Registration Prompt -->
      <v-col
        v-if="showList"
        :cols="isMobile ? 12 : (!hasMailAccounts || checkingAccounts) ? 12 : (selectedMailForDetail || showCompose) ? 5 : 10"
        class="list-col"
      >
        <!-- No Mail Accounts - Registration Prompt -->
        <v-card v-if="!hasMailAccounts && !checkingAccounts" class="fill-height d-flex align-center justify-center" flat>
          <v-card-text class="text-center pa-8">
            <v-icon size="80" color="primary" class="mb-4">mdi-email-plus-outline</v-icon>
            <h2 class="text-h5 font-weight-bold mb-3">Willkommen im Mail-System!</h2>
            <p class="text-body-1 mb-6">
              Um das Mail-System nutzen zu können, müssen Sie zunächst ein Mail-Konto erstellen oder ein bestehendes Konto verlinken.
            </p>
            <v-btn
              color="primary"
              size="large"
              variant="elevated"
              prepend-icon="mdi-cog"
              @click="navigateToSettings"
            >
              Zu den Mail-Einstellungen
            </v-btn>
          </v-card-text>
        </v-card>

        <!-- Loading State -->
        <v-card v-else-if="checkingAccounts" class="fill-height d-flex align-center justify-center" flat>
          <v-progress-circular indeterminate size="64" color="primary" />
        </v-card>

        <!-- Mail List (Normal View) -->
        <v-card v-else class="mail-list-card fill-height" flat>
          <!-- Search Bar -->
          <v-toolbar flat density="comfortable" class="mail-toolbar">
            <v-text-field
              v-model="searchQuery"
              prepend-inner-icon="mdi-magnify"
              label="Search mail"
              single-line
              hide-details
              density="compact"
              variant="solo"
              class="mx-4"
              clearable
            />
            <v-btn
              color="primary"
              prepend-icon="mdi-pencil"
              @click="handleCompose"
              class="mr-4"
            >
              Compose
            </v-btn>
          </v-toolbar>

          <v-divider />

          <!-- Bulk Actions Toolbar -->
          <v-toolbar
            v-if="selectedMailIds.length > 0"
            flat
            density="compact"
            color="blue-grey-lighten-5"
            class="bulk-toolbar"
          >
            <v-toolbar-title class="text-subtitle-2">
              {{ selectedMailIds.length }} selected
            </v-toolbar-title>
            <v-spacer />
            <v-btn
              icon="mdi-email-open-outline"
              size="small"
              variant="text"
              @click="handleBulkMarkRead(true)"
            >
              <v-icon>mdi-email-open-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Mark as read</v-tooltip>
            </v-btn>
            <v-btn
              icon="mdi-email-outline"
              size="small"
              variant="text"
              @click="handleBulkMarkRead(false)"
            >
              <v-icon>mdi-email-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Mark as unread</v-tooltip>
            </v-btn>
            <v-btn
              icon="mdi-delete-outline"
              size="small"
              variant="text"
              @click="handleBulkDelete"
            >
              <v-icon>mdi-delete-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Delete</v-tooltip>
            </v-btn>
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-folder-outline"
                  size="small"
                  variant="text"
                  v-bind="props"
                >
                  <v-icon>mdi-folder-outline</v-icon>
                  <v-tooltip activator="parent" location="bottom">Move to folder</v-tooltip>
                </v-btn>
              </template>
              <v-list>
                <v-list-item
                  v-for="folder in mailStore.folders"
                  :key="folder.id"
                  @click="handleBulkMove(folder.id)"
                >
                  <v-list-item-title>{{ folder.name }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </v-toolbar>

          <v-divider v-if="selectedMailIds.length > 0" />

          <!-- Mail List Component -->
          <MailList
            :mails="filteredMails"
            :selected-mail-ids="selectedMailIds"
            :loading="mailStore.loading"
            @mail-select="handleMailSelect"
            @selection-change="selectedMailIds = $event"
          />
        </v-card>
      </v-col>

      <!-- Mail Detail OR Compose Window -->
      <v-col
        v-if="(selectedMailForDetail && !isMobile && !showCompose) || (isMobile && showDetail) || showCompose"
        :cols="isMobile ? 12 : 5"
        class="detail-col"
      >
        <!-- Compose Window -->
        <v-card v-if="showCompose" class="fill-height d-flex flex-column" flat>
          <v-toolbar flat density="comfortable" class="mail-toolbar">
            <v-toolbar-title class="text-h6">
              <v-icon class="mr-2">mdi-pencil</v-icon>
              Neue Nachricht
            </v-toolbar-title>
            <v-spacer />
            <v-btn icon="mdi-close" variant="text" @click="showCompose = false" />
          </v-toolbar>

          <v-divider />

          <v-card-text class="flex-grow-1 overflow-y-auto pa-4">
            <v-form>
              <!-- From Domain Selector -->
              <v-select
                v-model="selectedFromDomain"
                :items="availableDomains"
                item-title="domain"
                item-value="domain"
                label="Von"
                prepend-icon="mdi-email-send"
                variant="outlined"
                density="comfortable"
                class="mb-3"
                :disabled="availableDomains.length <= 1"
                hint="Wählen Sie Ihre Absender-Domain"
                persistent-hint
              >
                <template v-slot:item="{ props, item }">
                  <v-list-item v-bind="props">
                    <template v-slot:prepend>
                      <v-icon v-if="item.raw.domain_type === 'default'">mdi-earth</v-icon>
                      <v-icon v-else>mdi-domain</v-icon>
                    </template>
                    <template v-slot:subtitle v-if="item.raw.domain_type !== 'default'">
                      {{ item.raw.display_name || 'Firmen-Domain' }}
                    </template>
                  </v-list-item>
                </template>
              </v-select>

              <!-- To Field with Suffix -->
              <v-text-field
                v-model="composeRecipient"
                label="An"
                prepend-icon="mdi-account"
                variant="outlined"
                density="comfortable"
                class="mb-3"
                placeholder="empfaenger"
                :suffix="'@' + selectedFromDomain"
                hint="Empfänger-Adresse (ohne @domain)"
                persistent-hint
              />

              <!-- Subject Field -->
              <v-text-field
                v-model="composeSubject"
                label="Betreff"
                prepend-icon="mdi-text-subject"
                variant="outlined"
                density="comfortable"
                class="mb-3"
              />

              <!-- Message Field -->
              <v-textarea
                v-model="composeMessage"
                label="Nachricht"
                prepend-icon="mdi-message-text"
                variant="outlined"
                rows="15"
                auto-grow
                placeholder="Schreiben Sie Ihre Nachricht..."
              />
            </v-form>
          </v-card-text>

          <v-divider />

          <v-card-actions class="pa-4">
            <v-btn
              color="primary"
              variant="elevated"
              prepend-icon="mdi-send"
              size="large"
            >
              Senden
            </v-btn>
            <v-btn
              variant="text"
              prepend-icon="mdi-paperclip"
            >
              Anhang
            </v-btn>
            <v-spacer />
            <v-btn
              variant="text"
              @click="showCompose = false"
            >
              Abbrechen
            </v-btn>
          </v-card-actions>
        </v-card>

        <!-- Mail Detail -->
        <MailDetail
          v-else-if="selectedMailForDetail"
          :mail="selectedMailForDetail"
          :show-back-button="isMobile"
          @back="handleBackToList"
          @close="mailStore.selectedMail = null"
        />
      </v-col>
    </v-row>

    <!-- Loading Overlay -->
    <v-overlay
      :model-value="mailStore.loading"
      class="align-center justify-center"
      contained
    >
      <v-progress-circular indeterminate size="64" />
    </v-overlay>

    <!-- Error Snackbar -->
    <v-snackbar
      :model-value="!!mailStore.error"
      color="error"
      timeout="5000"
      @update:model-value="mailStore.clearError()"
    >
      {{ mailStore.error }}
      <template v-slot:actions>
        <v-btn variant="text" @click="mailStore.clearError()">Close</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<style scoped>
.mail-view-container {
  height: 100vh;
  overflow: hidden;
}

.sidebar-col {
  background: rgb(var(--v-theme-surface));
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  height: 100vh;
  overflow-y: auto;
}

.list-col {
  height: 100vh;
  overflow: hidden;
}

.detail-col {
  border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  height: 100vh;
  overflow: hidden;
}

.mail-list-card {
  display: flex;
  flex-direction: column;
  background: rgb(var(--v-theme-surface));
}

.mail-toolbar {
  background: rgb(var(--v-theme-surface));
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.bulk-toolbar {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* Mobile adjustments */
@media (max-width: 959px) {
  .sidebar-col,
  .list-col,
  .detail-col {
    border: none;
  }
}

/* Scrollbar styling */
.sidebar-col::-webkit-scrollbar,
.list-col::-webkit-scrollbar,
.detail-col::-webkit-scrollbar {
  width: 8px;
}

.sidebar-col::-webkit-scrollbar-track,
.list-col::-webkit-scrollbar-track,
.detail-col::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-surface), 0.5);
}

.sidebar-col::-webkit-scrollbar-thumb,
.list-col::-webkit-scrollbar-thumb,
.detail-col::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 4px;
}

.sidebar-col::-webkit-scrollbar-thumb:hover,
.list-col::-webkit-scrollbar-thumb:hover,
.detail-col::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-on-surface), 0.4);
}
</style>
