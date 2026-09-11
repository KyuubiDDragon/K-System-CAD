<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useMailStore } from '@/stores/mail'
import type { MailFolder, MailAccount } from '@/types/Mail'

// Emits
const emit = defineEmits<{
  folderChange: [folder: string]
  compose: []
  navigateToSettings: []
}>()

// Store
const mailStore = useMailStore()

// State
const myAccounts = ref<MailAccount[]>([])
const loadingAccounts = ref(false)

// State
const showFolderDialog = ref(false)
const editingFolder = ref<MailFolder | null>(null)
const folderName = ref('')
const folderColor = ref('#1976d2')

// Computed
const systemFolders = computed(() => [
  {
    id: 'inbox',
    name: 'Inbox',
    icon: 'mdi-inbox',
    count: mailStore.inboxMails.length,
    unread: mailStore.unreadCount,
    color: 'primary'
  },
  {
    id: 'sent',
    name: 'Sent',
    icon: 'mdi-send',
    count: mailStore.sentMails.length,
    unread: 0,
    color: 'blue'
  },
  {
    id: 'drafts',
    name: 'Drafts',
    icon: 'mdi-file-document-edit-outline',
    count: mailStore.draftMails.length,
    unread: 0,
    color: 'orange'
  },
  {
    id: 'starred',
    name: 'Starred',
    icon: 'mdi-star',
    count: mailStore.starredMails.length,
    unread: 0,
    color: 'amber'
  },
  {
    id: 'trash',
    name: 'Trash',
    icon: 'mdi-delete',
    count: mailStore.trashedMails.length,
    unread: 0,
    color: 'grey'
  }
])

const customFolders = computed(() => {
  return mailStore.folders.map(folder => ({
    ...folder,
    count: mailStore.mails.filter(m => m.folder_id === folder.id && !m.is_deleted).length,
    unread: mailStore.folderUnreadCount(folder.id)
  }))
})

const isActive = computed(() => (folderId: string) => {
  return mailStore.currentFolder === folderId
})

// Methods
function selectFolder(folderId: string) {
  emit('folderChange', folderId)
}

function openComposeDialog() {
  emit('compose')
}

function openNewFolderDialog() {
  editingFolder.value = null
  folderName.value = ''
  folderColor.value = '#1976d2'
  showFolderDialog.value = true
}

function openEditFolderDialog(folder: MailFolder) {
  editingFolder.value = folder
  folderName.value = folder.name
  folderColor.value = folder.color
  showFolderDialog.value = true
}

async function saveFolder() {
  if (!folderName.value.trim()) return

  if (editingFolder.value) {
    // Update existing folder
    await mailStore.updateFolder(editingFolder.value.id, {
      name: folderName.value,
      color: folderColor.value
    })
  } else {
    // Create new folder
    await mailStore.createFolder(folderName.value, folderColor.value)
  }

  showFolderDialog.value = false
  folderName.value = ''
  folderColor.value = '#1976d2'
  editingFolder.value = null
}

async function deleteFolder(folder: MailFolder) {
  if (confirm(`Are you sure you want to delete the folder "${folder.name}"?`)) {
    await mailStore.deleteFolder(folder.id)
  }
}

function navigateToAccounts() {
  emit('navigateToSettings')
}

async function loadMyAccounts() {
  loadingAccounts.value = true
  try {
    const accounts = await mailStore.fetchMyAccounts()
    if (accounts) {
      myAccounts.value = accounts
      // Set first account as current if none selected
      if (!mailStore.currentAccount && accounts.length > 0) {
        mailStore.setCurrentAccount(accounts[0])
      }
    }
  } catch (error) {
    console.error('Failed to load accounts:', error)
  } finally {
    loadingAccounts.value = false
  }
}

function switchAccount(account: MailAccount) {
  mailStore.setCurrentAccount(account)
  // Reload current folder
  emit('folderChange', mailStore.currentFolder)
}

onMounted(() => {
  loadMyAccounts()
})
</script>

<template>
  <v-card flat class="mail-sidebar fill-height">
    <v-list density="compact" nav>
      <!-- Account Selector (show if multiple accounts) -->
      <v-list-item v-if="myAccounts.length > 1" class="pa-2 mb-2">
        <v-select
          :model-value="mailStore.currentAccount"
          :items="myAccounts"
          item-title="email"
          item-value="id"
          density="compact"
          variant="outlined"
          prepend-inner-icon="mdi-account"
          hide-details
          @update:model-value="switchAccount"
          return-object
        >
          <template v-slot:selection="{ item }">
            <div class="text-truncate">
              <div class="text-body-2 font-weight-medium">{{ item.raw.display_name || item.raw.email.split('@')[0] }}</div>
              <div class="text-caption text-grey">{{ item.raw.email }}</div>
            </div>
          </template>
          <template v-slot:item="{ item, props }">
            <v-list-item v-bind="props">
              <template v-slot:prepend>
                <v-icon>mdi-email</v-icon>
              </template>
              <v-list-item-title>{{ item.raw.display_name || item.raw.email.split('@')[0] }}</v-list-item-title>
              <v-list-item-subtitle>{{ item.raw.email }}</v-list-item-subtitle>
            </v-list-item>
          </template>
        </v-select>
      </v-list-item>

      <!-- Current Account Display (show if only one account) -->
      <v-list-item v-else-if="myAccounts.length === 1 && mailStore.currentAccount" class="pa-2 mb-2">
        <div class="d-flex align-center">
          <v-icon class="mr-2" color="primary">mdi-account-circle</v-icon>
          <div class="flex-grow-1 text-truncate">
            <div class="text-body-2 font-weight-medium">{{ mailStore.currentAccount.display_name || mailStore.currentAccount.email.split('@')[0] }}</div>
            <div class="text-caption text-grey">{{ mailStore.currentAccount.email }}</div>
          </div>
        </div>
      </v-list-item>

      <!-- Compose Button -->
      <v-list-item class="pa-2 mb-2">
        <v-btn
          color="primary"
          block
          prepend-icon="mdi-pencil"
          size="large"
          @click="openComposeDialog"
          elevation="2"
        >
          Compose
        </v-btn>
      </v-list-item>

      <v-divider class="mb-2" />

      <!-- System Folders -->
      <v-list-subheader>FOLDERS</v-list-subheader>

      <v-list-item
        v-for="folder in systemFolders"
        :key="folder.id"
        :value="folder.id"
        :active="isActive(folder.id)"
        @click="selectFolder(folder.id)"
        class="folder-item"
      >
        <template v-slot:prepend>
          <v-icon :color="folder.color">{{ folder.icon }}</v-icon>
        </template>

        <v-list-item-title>{{ folder.name }}</v-list-item-title>

        <template v-slot:append>
          <v-badge
            v-if="folder.unread > 0"
            :content="folder.unread"
            color="error"
            inline
          />
          <span v-else-if="folder.count > 0" class="count-badge">
            {{ folder.count }}
          </span>
        </template>
      </v-list-item>

      <!-- Custom Folders -->
      <template v-if="customFolders.length > 0">
        <v-divider class="my-2" />
        <v-list-subheader>
          CUSTOM FOLDERS
          <v-btn
            icon="mdi-plus"
            size="x-small"
            variant="text"
            @click="openNewFolderDialog"
            class="ml-2"
          >
            <v-icon>mdi-plus</v-icon>
            <v-tooltip activator="parent" location="right">{{ $t('mail.newFolder') }}</v-tooltip>
          </v-btn>
        </v-list-subheader>

        <v-list-item
          v-for="folder in customFolders"
          :key="folder.id"
          :value="folder.id.toString()"
          :active="isActive(folder.id.toString())"
          @click="selectFolder(folder.id.toString())"
          class="folder-item"
        >
          <template v-slot:prepend>
            <v-icon :color="folder.color">mdi-folder</v-icon>
          </template>

          <v-list-item-title>{{ folder.name }}</v-list-item-title>

          <template v-slot:append>
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  size="x-small"
                  variant="text"
                  v-bind="props"
                  @click.stop
                >
                  <v-icon size="small">mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list>
                <v-list-item @click="openEditFolderDialog(folder)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deleteFolder(folder)">
                  <template v-slot:prepend>
                    <v-icon color="error">mdi-delete</v-icon>
                  </template>
                  <v-list-item-title>Delete</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>

            <v-badge
              v-if="folder.unread > 0"
              :content="folder.unread"
              color="error"
              inline
              class="ml-2"
            />
            <span v-else-if="folder.count > 0" class="count-badge ml-2">
              {{ folder.count }}
            </span>
          </template>
        </v-list-item>
      </template>

      <!-- New Folder Button (if no custom folders) -->
      <template v-else>
        <v-divider class="my-2" />
        <v-list-item @click="openNewFolderDialog">
          <template v-slot:prepend>
            <v-icon>mdi-folder-plus</v-icon>
          </template>
          <v-list-item-title>{{ $t('mail.newFolder') }}</v-list-item-title>
        </v-list-item>
      </template>

      <!-- Settings -->
      <v-divider class="my-2" />
      <v-list-item @click="navigateToAccounts">
        <template v-slot:prepend>
          <v-icon color="primary">mdi-cog</v-icon>
        </template>
        <v-list-item-title class="text-primary">Einstellungen</v-list-item-title>
      </v-list-item>
    </v-list>

    <!-- Inline Folder Form -->
    <v-expand-transition>
      <v-card v-if="showFolderDialog" variant="outlined" class="ma-2">
        <v-card-title class="d-flex align-center py-3 px-3">
          <v-icon class="mr-2">{{ editingFolder ? 'mdi-folder-edit' : 'mdi-folder-plus' }}</v-icon>
          <span class="text-subtitle-1">{{ editingFolder ? 'Edit Folder' : 'New Folder' }}</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" size="small" variant="text" @click="showFolderDialog = false"></v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-3">
          <v-text-field
            v-model="folderName"
            :label="$t('mail.folderName')"
            variant="outlined"
            density="compact"
            autofocus
            class="mb-3"
          />
          <v-color-picker
            v-model="folderColor"
            mode="hexa"
            hide-inputs
            show-swatches
            :swatches="[
              ['#1976d2', '#2196f3', '#03a9f4', '#00bcd4'],
              ['#4caf50', '#8bc34a', '#cddc39', '#ffeb3b'],
              ['#ff9800', '#ff5722', '#f44336', '#e91e63'],
              ['#9c27b0', '#673ab7', '#3f51b5', '#607d8b']
            ]"
          />
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-3">
          <v-btn
            color="primary"
            variant="flat"
            block
            @click="saveFolder"
            :disabled="!folderName.trim()"
          >
            <v-icon start>{{ editingFolder ? 'mdi-content-save' : 'mdi-plus' }}</v-icon>
            {{ editingFolder ? 'Save' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-expand-transition>
  </v-card>
</template>

<style scoped>
.mail-sidebar {
  background: rgb(var(--v-theme-surface));
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.folder-item {
  margin-bottom: 2px;
  border-radius: 8px;
  transition: all 0.2s;
}

.folder-item:hover {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.folder-item.v-list-item--active {
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}

.count-badge {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-weight: 500;
}

:deep(.v-list-subheader) {
  font-weight: 600;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  color: rgba(var(--v-theme-on-surface), 0.7);
}
</style>
