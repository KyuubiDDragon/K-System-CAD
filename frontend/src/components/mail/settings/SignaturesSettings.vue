<template>
  <v-container fluid class="pa-6">
    <!-- List View -->
    <div v-if="!showEditor">
      <!-- Header with Actions -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h2 class="text-h5 font-weight-bold">E-Mail-Signaturen</h2>
            <p class="text-body-2 text-medium-emphasis">
              Verwalten Sie Ihre E-Mail-Signaturen für neue Nachrichten und Antworten
            </p>
          </div>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-plus"
            @click="openCreateSignature"
          >
            Signatur erstellen
          </v-btn>
        </v-col>
      </v-row>

      <!-- Loading State -->
      <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

      <!-- Signatures List -->
      <v-row v-if="signatures.length > 0">
        <v-col
          v-for="signature in signatures"
          :key="signature.id"
          cols="12"
          md="6"
        >
          <v-card elevation="2" hover>
            <v-card-title class="d-flex align-center">
              <v-icon left color="primary">mdi-draw</v-icon>
              <span class="flex-grow-1">{{ signature.name }}</span>
              <v-chip
                v-if="signature.is_default"
                color="success"
                size="small"
              >
                <v-icon left size="small">mdi-check-circle</v-icon>
                Standard
              </v-chip>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text>
              <!-- Signature Preview -->
              <div class="signature-preview pa-3 rounded" v-html="signature.signature_html"></div>

              <!-- Usage Settings -->
              <div class="mt-3">
                <v-chip
                  v-if="signature.use_for_new"
                  size="small"
                  color="primary"
                  variant="outlined"
                  class="mr-2"
                >
                  <v-icon left size="small">mdi-email-plus</v-icon>
                  Neue E-Mails
                </v-chip>
                <v-chip
                  v-if="signature.use_for_reply"
                  size="small"
                  color="secondary"
                  variant="outlined"
                >
                  <v-icon left size="small">mdi-reply</v-icon>
                  Antworten
                </v-chip>
              </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
              <v-btn
                v-if="!signature.is_default"
                size="small"
                variant="text"
                prepend-icon="mdi-check"
                @click="setAsDefault(signature)"
              >
                Als Standard
              </v-btn>
              <v-spacer></v-spacer>
              <v-btn
                size="small"
                variant="text"
                icon="mdi-pencil"
                @click="openEditSignature(signature)"
              >
              </v-btn>
              <v-btn
                size="small"
                variant="text"
                icon="mdi-delete"
                color="error"
                :disabled="signature.is_default"
                @click="openDeleteDialog(signature)"
              >
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-card v-else-if="!loading" class="text-center pa-8" variant="outlined">
        <v-icon size="64" color="grey-lighten-1">mdi-draw-pen</v-icon>
        <h3 class="text-h6 mt-4 mb-2">Keine Signaturen vorhanden</h3>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Erstellen Sie Ihre erste E-Mail-Signatur
        </p>
        <v-btn
          color="primary"
          variant="flat"
          prepend-icon="mdi-plus"
          @click="openCreateSignature"
        >
          Signatur erstellen
        </v-btn>
      </v-card>
    </div>

    <!-- Editor View -->
    <div v-else>
      <v-card>
        <v-toolbar flat>
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            @click="closeSignatureEditor"
          />
          <v-toolbar-title>
            {{ editMode ? 'Signatur bearbeiten' : 'Neue Signatur' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn
            color="primary"
            variant="flat"
            @click="saveSignature"
            :loading="saving"
            :disabled="!signatureFormValid"
          >
            {{ editMode ? 'Speichern' : 'Erstellen' }}
          </v-btn>
        </v-toolbar>

        <v-divider></v-divider>

        <v-card-text class="pa-6">
          <v-form ref="signatureForm" v-model="signatureFormValid">
            <!-- Name -->
            <v-text-field
              v-model="signatureFormData.name"
              label="Signaturname"
              prepend-icon="mdi-text"
              :rules="[rules.required]"
              hint="z.B. 'Geschäftlich', 'Persönlich'"
              persistent-hint
              required
            ></v-text-field>

            <!-- Rich Text Editor -->
            <div class="mt-4">
              <div class="text-subtitle-2 mb-2">Signatur-Inhalt</div>
              <TiptapEditor
                v-model="signatureFormData.signature_html"
                :editable="true"
                :show-source-button="true"
                placeholder="Erstellen Sie Ihre E-Mail-Signatur..."
              />
            </div>

            <!-- Templates -->
            <v-card variant="outlined" class="mt-4 pa-3">
              <div class="text-subtitle-2 mb-2">Schnellvorlagen:</div>
              <v-chip-group>
                <v-chip
                  v-for="template in signatureTemplates"
                  :key="template.name"
                  size="small"
                  variant="outlined"
                  @click="applyTemplate(template)"
                >
                  {{ template.name }}
                </v-chip>
              </v-chip-group>
            </v-card>

            <!-- Settings -->
            <v-divider class="my-4"></v-divider>

            <v-switch
              v-model="signatureFormData.is_default"
              label="Als Standardsignatur festlegen"
              color="success"
              hint="Diese Signatur wird automatisch verwendet"
              persistent-hint
            ></v-switch>

            <v-switch
              v-model="signatureFormData.use_for_new"
              label="Für neue E-Mails verwenden"
              color="primary"
              class="mt-2"
            ></v-switch>

            <v-switch
              v-model="signatureFormData.use_for_reply"
              label="Für Antworten verwenden"
              color="secondary"
              class="mt-2"
            ></v-switch>
          </v-form>
        </v-card-text>
      </v-card>

      <!-- Inline Delete Confirmation -->
      <v-expand-transition>
        <v-alert v-if="showDeleteConfirm" type="warning" variant="tonal" class="mt-4">
          <v-alert-title>Signatur löschen?</v-alert-title>
          <div class="mt-2">
            Möchten Sie die Signatur <strong>{{ selectedSignature?.name }}</strong> wirklich löschen?
          </div>
          <div class="mt-3 d-flex gap-2">
            <v-btn size="small" @click="closeDeleteDialog">Abbrechen</v-btn>
            <v-btn size="small" color="error" @click="deleteSignature" :loading="deleting">Löschen</v-btn>
          </div>
        </v-alert>
      </v-expand-transition>
    </div>

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
import { ref, onMounted } from 'vue'
import { useMailStore } from '@/stores/mail'
import type { MailSignature } from '@/types/Mail'
import TiptapEditor from '@/components/TiptapEditor.vue'

const mailStore = useMailStore()

// State
const loading = ref(false)
const signatures = ref<MailSignature[]>([])

// View state
const showEditor = ref(false)

// Dialogs
const showDeleteConfirm = ref(false)

// Form states
const signatureFormValid = ref(false)
const editMode = ref(false)
const selectedSignature = ref<MailSignature | null>(null)

// Loading states
const saving = ref(false)
const deleting = ref(false)

// Form data
const signatureFormData = ref({
  name: '',
  signature_html: '',
  is_default: false,
  use_for_new: true,
  use_for_reply: false
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Signature templates
const signatureTemplates = [
  {
    name: 'Einfach',
    html: `<div style="font-family: Arial, sans-serif; font-size: 14px;">
  <p>Mit freundlichen Grüßen<br>
  <strong>Ihr Name</strong></p>
  <p style="color: #666; font-size: 12px;">
    Position<br>
    Unternehmen<br>
    E-Mail: name@example.com<br>
    Tel: +49 123 456789
  </p>
</div>`
  },
  {
    name: 'Professionell',
    html: `<div style="font-family: Arial, sans-serif; font-size: 14px;">
  <table cellpadding="0" cellspacing="0" style="border-top: 2px solid #0066cc; padding-top: 10px;">
    <tr>
      <td style="padding-right: 20px;">
        <strong style="color: #0066cc; font-size: 16px;">Ihr Name</strong><br>
        <span style="color: #666;">Position</span><br>
        <span style="color: #666;">Unternehmen</span>
      </td>
    </tr>
    <tr>
      <td style="padding-top: 10px; font-size: 12px; color: #666;">
        E-Mail: name@example.com<br>
        Tel: +49 123 456789<br>
        Web: www.example.com
      </td>
    </tr>
  </table>
</div>`
  },
  {
    name: 'Minimal',
    html: `<div style="font-family: Arial, sans-serif; font-size: 13px; color: #333;">
  <p>Beste Grüße<br>
  <strong>Ihr Name</strong> | Position | name@example.com</p>
</div>`
  }
]

// Validation rules
const rules = {
  required: (v: any) => !!v || 'Pflichtfeld'
}

// Methods
async function fetchSignatures() {
  loading.value = true
  try {
    const data = await mailStore.fetchSignatures()
    if (data) {
      signatures.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Signaturen', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateSignature() {
  editMode.value = false
  showEditor.value = true
}

function openEditSignature(signature: MailSignature) {
  editMode.value = true
  selectedSignature.value = signature
  signatureFormData.value = {
    name: signature.name,
    signature_html: signature.signature_html,
    is_default: signature.is_default,
    use_for_new: signature.use_for_new,
    use_for_reply: signature.use_for_reply
  }
  showEditor.value = true
}

function closeSignatureEditor() {
  showEditor.value = false
  editMode.value = false
  selectedSignature.value = null
  resetSignatureForm()
}

function resetSignatureForm() {
  signatureFormData.value = {
    name: '',
    signature_html: '',
    is_default: false,
    use_for_new: true,
    use_for_reply: false
  }
}

function applyTemplate(template: { name: string; html: string }) {
  signatureFormData.value.signature_html = template.html
}

async function saveSignature() {
  if (!signatureFormValid.value) return

  saving.value = true
  try {
    if (editMode.value && selectedSignature.value) {
      await mailStore.updateSignature(selectedSignature.value.id, signatureFormData.value)
      showSnackbar('Signatur erfolgreich aktualisiert', 'success')
    } else {
      await mailStore.createSignature(signatureFormData.value)
      showSnackbar('Signatur erfolgreich erstellt', 'success')
    }

    closeSignatureEditor()
    await fetchSignatures()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Speichern der Signatur', 'error')
  } finally {
    saving.value = false
  }
}

async function setAsDefault(signature: MailSignature) {
  try {
    await mailStore.updateSignature(signature.id, { is_default: true })
    showSnackbar('Standardsignatur festgelegt', 'success')
    await fetchSignatures()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Festlegen der Standardsignatur', 'error')
  }
}

function openDeleteDialog(signature: MailSignature) {
  selectedSignature.value = signature
  showDeleteConfirm.value = true
}

function closeDeleteDialog() {
  showDeleteConfirm.value = false
  selectedSignature.value = null
}

async function deleteSignature() {
  if (!selectedSignature.value) return

  deleting.value = true
  try {
    await mailStore.deleteSignature(selectedSignature.value.id)
    showSnackbar('Signatur erfolgreich gelöscht', 'success')
    closeDeleteDialog()
    await fetchSignatures()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Löschen der Signatur', 'error')
  } finally {
    deleting.value = false
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
  await fetchSignatures()
})
</script>

<style scoped>
.signature-preview {
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-height: 100px;
  max-height: 200px;
  overflow-y: auto;
}
</style>
