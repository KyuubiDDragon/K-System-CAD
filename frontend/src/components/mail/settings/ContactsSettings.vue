<template>
  <v-container fluid class="pa-6">
    <!-- List View -->
    <div v-if="currentView === 'list'">
      <!-- Header with Actions -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center flex-wrap">
          <div>
            <h2 class="text-h5 font-weight-bold">Kontakte</h2>
            <p class="text-body-2 text-medium-emphasis">
              Verwalten Sie Ihre persönlichen Kontakte und durchsuchen Sie das globale Verzeichnis
            </p>
          </div>
          <div class="d-flex gap-2">
            <v-btn
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-upload"
              @click="currentView = 'import'"
            >
              Importieren
            </v-btn>
            <v-btn
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-download"
              @click="exportContacts"
            >
              Exportieren
            </v-btn>
            <v-btn
              color="primary"
              variant="flat"
              prepend-icon="mdi-plus"
              @click="openCreateContactDialog"
            >
              Kontakt hinzufügen
            </v-btn>
          </div>
        </v-col>
      </v-row>

    <!-- Source Toggle -->
    <v-row class="mb-4">
      <v-col cols="12">
        <v-btn-toggle
          v-model="contactSource"
          mandatory
          color="primary"
          variant="outlined"
          divided
        >
          <v-btn value="personal">
            <v-icon left>mdi-account</v-icon>
            Meine Kontakte
          </v-btn>
          <v-btn value="global">
            <v-icon left>mdi-earth</v-icon>
            Globales Verzeichnis
          </v-btn>
        </v-btn-toggle>
      </v-col>
    </v-row>

    <!-- Loading State -->
    <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

    <!-- Contacts Table -->
    <v-card v-if="displayedContacts.length > 0">
      <v-data-table
        :headers="headers"
        :items="displayedContacts"
        :items-per-page="15"
        :search="search"
      >
        <!-- Toolbar -->
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
              style="max-width: 400px"
            ></v-text-field>
          </v-toolbar>
        </template>

        <!-- Name Column -->
        <template v-slot:item.display_name="{ item }">
          <div class="d-flex align-center">
            <v-avatar size="40" class="mr-3" :color="item.avatar_url ? undefined : 'primary'">
              <v-img v-if="item.avatar_url" :src="item.avatar_url"></v-img>
              <span v-else class="text-h6">{{ getInitials(item.display_name) }}</span>
            </v-avatar>
            <div>
              <div class="font-weight-medium">{{ item.display_name }}</div>
              <div v-if="item.position" class="text-caption text-medium-emphasis">
                {{ item.position }}
              </div>
            </div>
          </div>
        </template>

        <!-- Email Column -->
        <template v-slot:item.email="{ item }">
          <a :href="`mailto:${item.email}`" class="text-decoration-none">
            {{ item.email }}
          </a>
        </template>

        <!-- Company Column -->
        <template v-slot:item.company="{ item }">
          {{ item.company || '-' }}
        </template>

        <!-- Tags Column -->
        <template v-slot:item.tags="{ item }">
          <v-chip-group v-if="item.tags && item.tags.length > 0">
            <v-chip
              v-for="tag in item.tags.slice(0, 2)"
              :key="tag"
              size="small"
              variant="outlined"
            >
              {{ tag }}
            </v-chip>
            <v-chip v-if="item.tags.length > 2" size="small" variant="text">
              +{{ item.tags.length - 2 }}
            </v-chip>
          </v-chip-group>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <!-- Favorite Column -->
        <template v-slot:item.is_favorite="{ item }">
          <v-btn
            v-if="contactSource === 'personal'"
            :icon="item.is_favorite ? 'mdi-star' : 'mdi-star-outline'"
            :color="item.is_favorite ? 'warning' : 'grey'"
            size="small"
            variant="text"
            @click="toggleFavorite(item)"
          >
          </v-btn>
        </template>

        <!-- Actions Column -->
        <template v-slot:item.actions="{ item }">
          <div v-if="contactSource === 'personal'">
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="openEditContactDialog(item)"
            >
            </v-btn>
            <v-btn
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              @click="openDeleteDialog(item)"
            >
            </v-btn>
          </div>
          <div v-else>
            <v-btn
              variant="text"
              size="small"
              prepend-icon="mdi-plus"
              @click="addToContacts(item)"
            >
              Hinzufügen
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Empty State -->
    <v-card v-else-if="!loading" class="text-center pa-8" variant="outlined">
      <v-icon size="64" color="grey-lighten-1">mdi-account-off-outline</v-icon>
      <h3 class="text-h6 mt-4 mb-2">
        {{ contactSource === 'personal' ? 'Keine Kontakte vorhanden' : 'Keine Ergebnisse' }}
      </h3>
      <p class="text-body-2 text-medium-emphasis mb-4">
        {{
          contactSource === 'personal'
            ? 'Fügen Sie Ihren ersten Kontakt hinzu'
            : 'Suchen Sie nach Benutzern im globalen Verzeichnis'
        }}
      </p>
      <v-btn
        v-if="contactSource === 'personal'"
        color="primary"
        variant="flat"
        prepend-icon="mdi-plus"
        @click="openCreateContactDialog"
      >
        Kontakt hinzufügen
      </v-btn>
    </v-card>

      <!-- Inline Delete Confirmation -->
      <v-expand-transition>
        <v-alert v-if="showDeleteConfirm" type="warning" variant="tonal" class="mt-4">
          <v-alert-title>Kontakt löschen?</v-alert-title>
          <div class="mt-2">
            Möchten Sie den Kontakt <strong>{{ selectedContact?.display_name }}</strong> wirklich löschen?
          </div>
          <div class="mt-3 d-flex gap-2">
            <v-btn size="small" @click="closeDeleteDialog">Abbrechen</v-btn>
            <v-btn size="small" color="error" @click="deleteContact" :loading="deleting">Löschen</v-btn>
          </div>
        </v-alert>
      </v-expand-transition>
    </div>

    <!-- Create/Edit Contact View -->
    <v-card v-else-if="currentView === 'contact'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">{{ editMode ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
        <span class="text-h5">{{ editMode ? 'Kontakt bearbeiten' : 'Neuer Kontakt' }}</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="closeContactDialog" :disabled="saving"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
          <v-form ref="contactForm" v-model="contactFormValid">
            <v-row>
              <!-- First Name -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.first_name"
                  label="Vorname"
                  prepend-icon="mdi-account"
                  :rules="[rules.required]"
                  required
                ></v-text-field>
              </v-col>

              <!-- Last Name -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.last_name"
                  label="Nachname"
                  :rules="[rules.required]"
                  required
                ></v-text-field>
              </v-col>

              <!-- Email -->
              <v-col cols="12">
                <v-text-field
                  v-model="contactFormData.email"
                  label="E-Mail"
                  type="email"
                  prepend-icon="mdi-email"
                  :rules="[rules.required, rules.email]"
                  required
                ></v-text-field>
              </v-col>

              <!-- Company -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.company"
                  label="Unternehmen"
                  prepend-icon="mdi-domain"
                ></v-text-field>
              </v-col>

              <!-- Position -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.position"
                  label="Position"
                  prepend-icon="mdi-briefcase"
                ></v-text-field>
              </v-col>

              <!-- Phone -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.phone"
                  label="Telefon"
                  prepend-icon="mdi-phone"
                ></v-text-field>
              </v-col>

              <!-- Mobile -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="contactFormData.mobile"
                  label="Mobil"
                  prepend-icon="mdi-cellphone"
                ></v-text-field>
              </v-col>

              <!-- Address -->
              <v-col cols="12">
                <v-text-field
                  v-model="contactFormData.address"
                  label="Adresse"
                  prepend-icon="mdi-map-marker"
                ></v-text-field>
              </v-col>

              <!-- Tags -->
              <v-col cols="12">
                <v-combobox
                  v-model="contactFormData.tags"
                  label="Tags"
                  prepend-icon="mdi-tag-multiple"
                  multiple
                  chips
                  closable-chips
                  hint="Drücken Sie Enter, um einen Tag hinzuzufügen"
                  persistent-hint
                ></v-combobox>
              </v-col>

              <!-- Notes -->
              <v-col cols="12">
                <v-textarea
                  v-model="contactFormData.notes"
                  label="Notizen"
                  prepend-icon="mdi-note-text"
                  rows="3"
                ></v-textarea>
              </v-col>

              <!-- Favorite -->
              <v-col cols="12">
                <v-switch
                  v-model="contactFormData.is_favorite"
                  label="Als Favorit markieren"
                  color="warning"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-btn
            color="primary"
            variant="flat"
            block
            @click="saveContact"
            :loading="saving"
            :disabled="!contactFormValid"
          >
            <v-icon start>{{ editMode ? 'mdi-content-save' : 'mdi-plus' }}</v-icon>
            {{ editMode ? 'Speichern' : 'Hinzufügen' }}
          </v-btn>
        </v-card-actions>
      </v-card>

    <!-- Import View -->
    <v-card v-else-if="currentView === 'import'" elevation="0">
      <v-card-title class="d-flex align-center py-3 px-4">
        <v-icon class="mr-2">mdi-upload</v-icon>
        <span class="text-h5">Kontakte importieren</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" size="small" variant="text" @click="currentView = 'list'" :disabled="importing"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-file-input
          v-model="importFile"
          label="CSV-Datei auswählen"
          accept=".csv"
          prepend-icon="mdi-file-upload"
          :rules="[rules.required]"
          show-size
        ></v-file-input>

        <v-alert type="info" density="compact" class="mt-4">
          Die CSV-Datei sollte folgende Spalten enthalten: Vorname, Nachname, E-Mail, Unternehmen, Position, Telefon, Mobil
        </v-alert>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          @click="importContactsFile"
          :loading="importing"
          :disabled="!importFile"
        >
          <v-icon start>mdi-upload</v-icon>
          Importieren
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
import { ref, computed, onMounted, watch } from 'vue'
import { useMailStore } from '@/stores/mail'
import type { Contact } from '@/types/Mail'

const mailStore = useMailStore()

// State
const loading = ref(false)
const contacts = ref<Contact[]>([])
const globalContacts = ref<any[]>([])
const search = ref('')
const contactSource = ref('personal')

// View state
const currentView = ref<'list' | 'contact' | 'import'>('list')
const showDeleteConfirm = ref(false)

// Form states
const contactFormValid = ref(false)
const editMode = ref(false)
const selectedContact = ref<Contact | null>(null)

// Loading states
const saving = ref(false)
const deleting = ref(false)
const importing = ref(false)

// Import
const importFile = ref<File | null>(null)

// Form data
const contactFormData = ref({
  first_name: '',
  last_name: '',
  email: '',
  company: '',
  position: '',
  phone: '',
  mobile: '',
  address: '',
  tags: [] as string[],
  notes: '',
  is_favorite: false
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Computed
const displayedContacts = computed(() => {
  return contactSource.value === 'personal' ? contacts.value : globalContacts.value
})

// Table headers
const headers = computed(() => {
  const baseHeaders = [
    { title: 'Name', key: 'display_name', sortable: true },
    { title: 'E-Mail', key: 'email', sortable: true },
    { title: 'Unternehmen', key: 'company', sortable: true },
    { title: 'Tags', key: 'tags', sortable: false }
  ]

  if (contactSource.value === 'personal') {
    baseHeaders.push(
      { title: 'Favorit', key: 'is_favorite', sortable: true, align: 'center' },
      { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
    )
  } else {
    baseHeaders.push(
      { title: 'Aktionen', key: 'actions', sortable: false, align: 'end' }
    )
  }

  return baseHeaders as any
})

// Validation rules
const rules = {
  required: (v: any) => !!v || 'Pflichtfeld',
  email: (v: string) => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return pattern.test(v) || 'Ungültige E-Mail-Adresse'
  }
}

// Methods
function getInitials(name: string) {
  return name
    .split(' ')
    .map(part => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

async function fetchContacts() {
  loading.value = true
  try {
    const data = await mailStore.fetchContacts()
    if (data) {
      contacts.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Kontakte', 'error')
  } finally {
    loading.value = false
  }
}

async function searchGlobalDirectory() {
  if (contactSource.value !== 'global') return

  loading.value = true
  try {
    const data = await mailStore.searchGlobalDirectory(search.value)
    if (data) {
      globalContacts.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Durchsuchen des globalen Verzeichnisses', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateContactDialog() {
  editMode.value = false
  currentView.value = 'contact'
}

function openEditContactDialog(contact: Contact) {
  editMode.value = true
  selectedContact.value = contact
  contactFormData.value = {
    first_name: contact.first_name || '',
    last_name: contact.last_name || '',
    email: contact.email,
    company: contact.company || '',
    position: contact.position || '',
    phone: contact.phone || '',
    mobile: contact.mobile || '',
    address: contact.address || '',
    tags: contact.tags || [],
    notes: contact.notes || '',
    is_favorite: contact.is_favorite
  }
  currentView.value = 'contact'
}

function closeContactDialog() {
  currentView.value = 'list'
  editMode.value = false
  selectedContact.value = null
  resetContactForm()
}

function resetContactForm() {
  contactFormData.value = {
    first_name: '',
    last_name: '',
    email: '',
    company: '',
    position: '',
    phone: '',
    mobile: '',
    address: '',
    tags: [],
    notes: '',
    is_favorite: false
  }
}

async function saveContact() {
  if (!contactFormValid.value) return

  saving.value = true
  try {
    const data = {
      ...contactFormData.value,
      display_name: `${contactFormData.value.first_name} ${contactFormData.value.last_name}`.trim()
    }

    if (editMode.value && selectedContact.value) {
      await mailStore.updateContact(selectedContact.value.id, data)
      showSnackbar('Kontakt erfolgreich aktualisiert', 'success')
    } else {
      await mailStore.createContact(data)
      showSnackbar('Kontakt erfolgreich hinzugefügt', 'success')
    }

    closeContactDialog()
    await fetchContacts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Speichern des Kontakts', 'error')
  } finally {
    saving.value = false
  }
}

function openDeleteDialog(contact: Contact) {
  selectedContact.value = contact
  showDeleteConfirm.value = true
}

function closeDeleteDialog() {
  showDeleteConfirm.value = false
  selectedContact.value = null
}

async function deleteContact() {
  if (!selectedContact.value) return

  deleting.value = true
  try {
    await mailStore.deleteContact(selectedContact.value.id)
    showSnackbar('Kontakt erfolgreich gelöscht', 'success')
    closeDeleteDialog()
    await fetchContacts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Löschen des Kontakts', 'error')
  } finally {
    deleting.value = false
  }
}

async function toggleFavorite(contact: Contact) {
  try {
    await mailStore.toggleFavorite(contact.id)
    contact.is_favorite = !contact.is_favorite
    showSnackbar(
      contact.is_favorite ? 'Zu Favoriten hinzugefügt' : 'Aus Favoriten entfernt',
      'success'
    )
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Aktualisieren des Favoriten', 'error')
  }
}

async function addToContacts(globalContact: any) {
  try {
    const data = {
      first_name: globalContact.first_name || '',
      last_name: globalContact.last_name || '',
      email: globalContact.email,
      display_name: globalContact.display_name,
      company: '',
      position: '',
      phone: '',
      mobile: '',
      address: '',
      tags: [],
      notes: '',
      is_favorite: false
    }

    await mailStore.createContact(data)
    showSnackbar('Kontakt erfolgreich hinzugefügt', 'success')
    await fetchContacts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Hinzufügen des Kontakts', 'error')
  }
}

async function exportContacts() {
  try {
    const result = await mailStore.exportContacts()
    if (result) {
      // Create download link
      const blob = new Blob([result], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `contacts_${new Date().toISOString().split('T')[0]}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
      showSnackbar('Kontakte erfolgreich exportiert', 'success')
    }
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Exportieren der Kontakte', 'error')
  }
}

async function importContactsFile() {
  if (!importFile.value) return

  importing.value = true
  try {
    const result = await mailStore.importContacts(importFile.value)
    showSnackbar(`${result.imported} Kontakte erfolgreich importiert`, 'success')
    currentView.value = 'list'
    importFile.value = null
    await fetchContacts()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Importieren der Kontakte', 'error')
  } finally {
    importing.value = false
  }
}

function showSnackbar(message: string, color: string = 'success') {
  snackbar.value = {
    show: true,
    message,
    color
  }
}

// Watchers
watch(contactSource, async (newSource) => {
  if (newSource === 'global') {
    await searchGlobalDirectory()
  }
})

watch(search, async () => {
  if (contactSource.value === 'global' && search.value) {
    await searchGlobalDirectory()
  }
})

// Lifecycle
onMounted(async () => {
  await fetchContacts()
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
