<template>
  <v-card elevation="0">
    <v-card-title class="d-flex align-center py-3 px-4">
      <v-icon class="mr-2">mdi-account-multiple</v-icon>
      Zugriff teilen
      <v-spacer />
      <v-btn icon="mdi-close" variant="text" size="small" @click="close" />
    </v-card-title>

    <v-divider />

      <!-- Current Access List -->
      <v-card-text class="pa-4">
        <h3 class="mb-3">Berechtigte Personen/Rollen</h3>

        <v-list v-if="accessList.length > 0" density="compact">
          <v-list-item
            v-for="access in accessList"
            :key="access.id"
            class="mb-2"
          >
            <template v-slot:prepend>
              <v-avatar :color="access.role_id ? 'secondary' : 'primary'" size="40">
                <v-icon color="white">
                  {{ access.role_id ? 'mdi-account-group' : 'mdi-account' }}
                </v-icon>
              </v-avatar>
            </template>

            <v-list-item-title class="font-weight-bold">
              <template v-if="access.user_id">
                {{ access.first_name }} {{ access.last_name }}
                <span class="text-caption text-grey">({{ access.username }})</span>
              </template>
              <template v-else>
                {{ access.role_name }}
                <v-chip size="x-small" color="secondary" class="ml-2">Rolle</v-chip>
              </template>
            </v-list-item-title>

            <v-list-item-subtitle>
              <v-chip-group>
                <v-chip v-if="access.can_read" size="x-small" color="info">Lesen</v-chip>
                <v-chip v-if="access.can_reply" size="x-small" color="success">Antworten</v-chip>
                <v-chip v-if="access.can_forward" size="x-small" color="warning">Weiterleiten</v-chip>
                <v-chip v-if="access.can_delete" size="x-small" color="error">Löschen</v-chip>
              </v-chip-group>
              <div class="text-caption mt-1">
                Geteilt von: {{ access.granted_by_first_name }} {{ access.granted_by_last_name }}
                am {{ formatDate(access.granted_at) }}
              </div>
            </v-list-item-subtitle>

            <template v-slot:append>
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="confirmRevoke(access)"
              >
                <v-icon>mdi-delete</v-icon>
                <v-tooltip activator="parent" location="top">Zugriff entfernen</v-tooltip>
              </v-btn>
            </template>
          </v-list-item>
        </v-list>

        <v-alert v-else type="info" variant="tonal">
          Noch keine Berechtigungen vergeben. Diese Mail ist für alle zugänglich.
        </v-alert>

        <v-divider class="my-4" />

        <!-- Add New Access -->
        <h3 class="mb-3">Zugriff hinzufügen</h3>

        <v-tabs v-model="grantTab" class="mb-4">
          <v-tab value="user">
            <v-icon left>mdi-account</v-icon>
            User
          </v-tab>
          <v-tab value="role">
            <v-icon left>mdi-account-group</v-icon>
            Rolle
          </v-tab>
        </v-tabs>

        <v-window v-model="grantTab">
          <!-- Grant to User -->
          <v-window-item value="user">
            <v-autocomplete
              v-model="selectedUser"
              :items="availableUsers"
              item-title="full_name"
              item-value="id"
              label="User auswählen"
              prepend-icon="mdi-account"
              variant="outlined"
              density="compact"
              clearable
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-avatar color="primary" size="32">
                      <span class="text-white text-caption">
                        {{ getInitials(item.raw.first_name, item.raw.last_name) }}
                      </span>
                    </v-avatar>
                  </template>
                  <v-list-item-title>
                    {{ item.raw.first_name }} {{ item.raw.last_name }}
                  </v-list-item-title>
                  <v-list-item-subtitle>{{ item.raw.username }}</v-list-item-subtitle>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-window-item>

          <!-- Grant to Role -->
          <v-window-item value="role">
            <v-select
              v-model="selectedRole"
              :items="availableRoles"
              item-title="name"
              item-value="id"
              label="Rolle auswählen"
              prepend-icon="mdi-account-group"
              variant="outlined"
              density="compact"
              clearable
            />
          </v-window-item>
        </v-window>

        <!-- Permissions Selection -->
        <h4 class="mt-4 mb-2">Berechtigungen</h4>
        <v-row>
          <v-col cols="6" sm="3">
            <v-checkbox
              v-model="permissions.can_read"
              label="Lesen"
              color="info"
              hide-details
              density="compact"
            />
          </v-col>
          <v-col cols="6" sm="3">
            <v-checkbox
              v-model="permissions.can_reply"
              label="Antworten"
              color="success"
              hide-details
              density="compact"
            />
          </v-col>
          <v-col cols="6" sm="3">
            <v-checkbox
              v-model="permissions.can_forward"
              label="Weiterleiten"
              color="warning"
              hide-details
              density="compact"
            />
          </v-col>
          <v-col cols="6" sm="3">
            <v-checkbox
              v-model="permissions.can_delete"
              label="Löschen"
              color="error"
              hide-details
              density="compact"
            />
          </v-col>
        </v-row>

        <!-- Revoke Confirmation (inline) -->
        <v-expand-transition>
          <v-alert
            v-if="revokeDialog"
            type="warning"
            variant="tonal"
            class="mt-4"
          >
            <v-alert-title>Zugriff entfernen?</v-alert-title>
            <div class="mt-2">
              Möchten Sie den Zugriff für
              <strong>
                <template v-if="revokeTarget?.user_id">
                  {{ revokeTarget.first_name }} {{ revokeTarget.last_name }}
                </template>
                <template v-else>
                  {{ revokeTarget?.role_name }}
                </template>
              </strong>
              wirklich entfernen?
            </div>
            <div class="mt-3 d-flex gap-2">
              <v-btn size="small" @click="revokeDialog = false">Abbrechen</v-btn>
              <v-btn size="small" color="error" @click="revokeAccess">Entfernen</v-btn>
            </div>
          </v-alert>
        </v-expand-transition>
      </v-card-text>

      <v-divider />

      <!-- Actions at bottom -->
      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="elevated"
          block
          :disabled="!canGrant"
          :loading="granting"
          @click="grantAccess"
        >
          <v-icon left>mdi-account-plus</v-icon>
          Zugriff gewähren
        </v-btn>
      </v-card-actions>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
      {{ snackbarText }}
    </v-snackbar>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Props
interface Props {
  mailId: number
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'closed': []
  'access-changed': []
}>()

// State
const accessList = ref<any[]>([])
const availableUsers = ref<any[]>([])
const availableRoles = ref<any[]>([])
const grantTab = ref('user')
const selectedUser = ref<number | null>(null)
const selectedRole = ref<number | null>(null)
const permissions = ref({
  can_read: true,
  can_reply: false,
  can_forward: false,
  can_delete: false
})

const granting = ref(false)
const revokeDialog = ref(false)
const revokeTarget = ref<any>(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Computed
const canGrant = computed(() => {
  return (grantTab.value === 'user' && selectedUser.value) ||
         (grantTab.value === 'role' && selectedRole.value)
})

// Methods
async function loadAccessList() {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/mail/?action=getMailAccessList&mail_id=${props.mailId}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    )

    if (response.ok) {
      const data = await response.json()
      accessList.value = data.access_list || []
    }
  } catch (error) {
    console.error('Error loading access list:', error)
  }
}

async function loadUsers() {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/user/?users`,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    )

    if (response.ok) {
      const data = await response.json()
      availableUsers.value = (data.users || []).map((user: any) => ({
        ...user,
        full_name: `${user.first_name} ${user.last_name}`
      }))
    }
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

async function loadRoles() {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/role/?roles`,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    )

    if (response.ok) {
      const data = await response.json()
      availableRoles.value = data.roles || []
    }
  } catch (error) {
    console.error('Error loading roles:', error)
  }
}

async function grantAccess() {
  granting.value = true
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/mail/`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'grantMailAccess',
          mail_id: props.mailId,
          user_id: grantTab.value === 'user' ? selectedUser.value : null,
          role_id: grantTab.value === 'role' ? selectedRole.value : null,
          ...permissions.value
        })
      }
    )

    if (response.ok) {
      showSnackbar('Zugriff erfolgreich gewährt', 'success')
      selectedUser.value = null
      selectedRole.value = null
      await loadAccessList()
      emit('access-changed')
    } else {
      showSnackbar('Fehler beim Gewähren des Zugriffs', 'error')
    }
  } catch (error) {
    console.error('Error granting access:', error)
    showSnackbar('Fehler beim Gewähren des Zugriffs', 'error')
  } finally {
    granting.value = false
  }
}

function confirmRevoke(access: any) {
  revokeTarget.value = access
  revokeDialog.value = true
}

async function revokeAccess() {
  if (!revokeTarget.value) return

  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    const response = await fetch(
      `${import.meta.env.VITE_API_URL}/mail/`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'revokeMailAccess',
          access_id: revokeTarget.value.id
        })
      }
    )

    if (response.ok) {
      showSnackbar('Zugriff erfolgreich entfernt', 'success')
      revokeDialog.value = false
      await loadAccessList()
      emit('access-changed')
    } else {
      showSnackbar('Fehler beim Entfernen des Zugriffs', 'error')
    }
  } catch (error) {
    console.error('Error revoking access:', error)
    showSnackbar('Fehler beim Entfernen des Zugriffs', 'error')
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getInitials(firstName: string, lastName: string): string {
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase()
}

function showSnackbar(text: string, color: string = 'success') {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

function close() {
  emit('closed')
}

// Lifecycle
onMounted(() => {
  loadAccessList()
  loadUsers()
  loadRoles()
})
</script>
