<template>
  <v-container fluid class="pa-6">
    <!-- List View -->
    <div v-if="!showEditor">
      <!-- Header with Actions -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h2 class="text-h5 font-weight-bold">E-Mail-Vorlagen</h2>
            <p class="text-body-2 text-medium-emphasis">
              Erstellen und verwalten Sie wiederverwendbare E-Mail-Vorlagen
            </p>
          </div>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-plus"
            @click="openCreateTemplate"
          >
            Vorlage erstellen
          </v-btn>
        </v-col>
      </v-row>

      <!-- Filter Chips -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group
            v-model="selectedCategory"
            mandatory
            selected-class="text-primary"
          >
            <v-chip value="all" filter>Alle</v-chip>
            <v-chip value="personal" filter>Persönlich</v-chip>
            <v-chip value="mailbox" filter>Postfach</v-chip>
            <v-chip value="system" filter>System</v-chip>
          </v-chip-group>
        </v-col>
      </v-row>

      <!-- Loading State -->
      <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

      <!-- Templates Grid -->
      <v-row v-if="filteredTemplates.length > 0">
        <v-col
          v-for="template in filteredTemplates"
          :key="template.id"
          cols="12"
          md="6"
          lg="4"
        >
          <v-card elevation="2" hover>
            <v-card-title class="d-flex align-center">
              <v-icon left :color="getCategoryColor(template.template_type)">
                {{ getCategoryIcon(template.template_type) }}
              </v-icon>
              <span class="text-truncate">{{ template.name }}</span>
            </v-card-title>

            <v-card-subtitle>
              <v-chip
                :color="getCategoryColor(template.template_type)"
                size="small"
                class="mr-2"
              >
                {{ getCategoryLabel(template.template_type) }}
              </v-chip>
              <v-chip
                v-if="template.is_active"
                color="success"
                size="small"
              >
                Aktiv
              </v-chip>
              <v-chip
                v-else
                color="grey"
                size="small"
              >
                Inaktiv
              </v-chip>
            </v-card-subtitle>

            <v-card-text>
              <!-- Subject Preview -->
              <div v-if="template.subject_template" class="mb-2">
                <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
                  Betreff:
                </div>
                <div class="text-body-2 text-truncate">
                  {{ template.subject_template }}
                </div>
              </div>

              <!-- Body Preview -->
              <div>
                <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
                  Vorschau:
                </div>
                <div class="text-body-2 preview-text">
                  {{ getBodyPreview(template.body_template) }}
                </div>
              </div>

              <!-- Variables -->
              <div v-if="template.available_variables && template.available_variables.length > 0" class="mt-3">
                <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
                  Variablen:
                </div>
                <v-chip-group>
                  <v-chip
                    v-for="variable in template.available_variables.slice(0, 3)"
                    :key="variable"
                    size="x-small"
                    variant="outlined"
                  >
                    {{ variable }}
                  </v-chip>
                  <v-chip
                    v-if="template.available_variables.length > 3"
                    size="x-small"
                    variant="text"
                  >
                    +{{ template.available_variables.length - 3 }}
                  </v-chip>
                </v-chip-group>
              </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-email"
                @click="useTemplate(template)"
              >
                Verwenden
              </v-btn>
              <v-spacer></v-spacer>
              <v-btn
                v-if="template.template_type !== 'system'"
                size="small"
                variant="text"
                icon="mdi-pencil"
                @click="openEditTemplate(template)"
              >
              </v-btn>
              <v-btn
                v-if="template.template_type !== 'system'"
                size="small"
                variant="text"
                icon="mdi-delete"
                color="error"
                @click="openDeleteDialog(template)"
              >
              </v-btn>
              <v-btn
                v-if="template.template_type === 'system'"
                size="small"
                variant="text"
                icon="mdi-content-copy"
                @click="duplicateTemplate(template)"
              >
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-card v-else-if="!loading" class="text-center pa-8" variant="outlined">
        <v-icon size="64" color="grey-lighten-1">mdi-file-document-outline</v-icon>
        <h3 class="text-h6 mt-4 mb-2">Keine Vorlagen vorhanden</h3>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Erstellen Sie Ihre erste E-Mail-Vorlage
        </p>
        <v-btn
          color="primary"
          variant="flat"
          prepend-icon="mdi-plus"
          @click="openCreateTemplate"
        >
          Vorlage erstellen
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
            @click="closeTemplateEditor"
          />
          <v-toolbar-title>
            {{ editMode ? 'Vorlage bearbeiten' : 'Neue Vorlage' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn
            color="primary"
            variant="flat"
            @click="saveTemplate"
            :loading="saving"
            :disabled="!templateFormValid"
          >
            {{ editMode ? 'Speichern' : 'Erstellen' }}
          </v-btn>
        </v-toolbar>

        <v-divider />

        <v-card-text class="pa-6">
          <v-form ref="templateForm" v-model="templateFormValid">
            <v-row>
              <!-- Name -->
              <v-col cols="12">
                <v-text-field
                  v-model="templateFormData.name"
                  label="Vorlagenname"
                  prepend-icon="mdi-text"
                  :rules="[rules.required]"
                  required
                ></v-text-field>
              </v-col>

              <!-- Category -->
              <v-col cols="12" md="6">
                <v-select
                  v-model="templateFormData.template_type"
                  :items="categoryOptions"
                  item-title="text"
                  item-value="value"
                  label="Kategorie"
                  prepend-icon="mdi-tag"
                  :rules="[rules.required]"
                  required
                ></v-select>
              </v-col>

              <!-- Mailbox Selection (if mailbox type) -->
              <v-col v-if="templateFormData.template_type === 'mailbox'" cols="12" md="6">
                <v-select
                  v-model="templateFormData.owner_mailbox_id"
                  :items="mailboxes"
                  item-title="name"
                  item-value="id"
                  label="Postfach"
                  prepend-icon="mdi-inbox"
                  :rules="[rules.required]"
                  required
                ></v-select>
              </v-col>

              <!-- Description -->
              <v-col cols="12">
                <v-textarea
                  v-model="templateFormData.description"
                  label="Beschreibung (optional)"
                  prepend-icon="mdi-text-box"
                  rows="2"
                ></v-textarea>
              </v-col>

              <!-- Subject Template -->
              <v-col cols="12">
                <v-text-field
                  v-model="templateFormData.subject_template"
                  label="Betreff-Vorlage"
                  prepend-icon="mdi-format-title"
                  hint="Verwenden Sie Variablen wie {{name}}, {{date}}, etc."
                  persistent-hint
                ></v-text-field>
              </v-col>

              <!-- Body Template -->
              <v-col cols="12">
                <div class="text-subtitle-2 mb-2">
                  <v-icon class="mr-1">mdi-text</v-icon>
                  E-Mail-Inhalt
                </div>
                <TiptapEditor
                  v-model="templateFormData.body_template"
                  :editable="true"
                  :show-source-button="true"
                  placeholder="Erstellen Sie Ihre E-Mail-Vorlage... (Verwenden Sie Variablen wie {{name}}, {{date}}, {{company}})"
                />
                <div class="text-caption text-medium-emphasis mt-1">
                  Tipp: Verwenden Sie Variablen wie {{name}}, {{date}}, {{company}} für dynamische Inhalte
                </div>
              </v-col>

              <!-- Available Variables -->
              <v-col cols="12">
                <v-combobox
                  v-model="templateFormData.available_variables"
                  label="Verfügbare Variablen"
                  prepend-icon="mdi-code-braces"
                  multiple
                  chips
                  closable-chips
                  hint="Geben Sie Variablennamen ein (z.B. name, date, company)"
                  persistent-hint
                ></v-combobox>
              </v-col>

              <!-- Common Variables Helper -->
              <v-col cols="12">
                <v-card variant="outlined" class="pa-3">
                  <div class="text-subtitle-2 mb-2">Häufig verwendete Variablen:</div>
                  <v-chip-group>
                    <v-chip
                      v-for="variable in commonVariables"
                      :key="variable"
                      size="small"
                      variant="outlined"
                      @click="addVariable(variable)"
                    >
                      {{ variable }}
                    </v-chip>
                  </v-chip-group>
                </v-card>
              </v-col>

              <!-- Active Status -->
              <v-col cols="12">
                <v-switch
                  v-model="templateFormData.is_active"
                  label="Vorlage aktiv"
                  color="primary"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
      </v-card>

      <!-- Inline Delete Confirmation -->
      <v-expand-transition>
        <v-alert v-if="showDeleteConfirm" type="warning" variant="tonal" class="mt-4">
          <v-alert-title>Vorlage löschen?</v-alert-title>
          <div class="mt-2">
            Möchten Sie die Vorlage <strong>{{ selectedTemplate?.name }}</strong> wirklich löschen?
          </div>
          <div class="mt-3 d-flex gap-2">
            <v-btn size="small" @click="closeDeleteDialog">Abbrechen</v-btn>
            <v-btn size="small" color="error" @click="deleteTemplate" :loading="deleting">Löschen</v-btn>
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
import { ref, computed, onMounted } from 'vue'
import { useMailStore } from '@/stores/mail'
import { useRouter } from 'vue-router'
import type { MailTemplate } from '@/types/Mail'
import TiptapEditor from '@/components/TiptapEditor.vue'

const mailStore = useMailStore()
const router = useRouter()

// State
const loading = ref(false)
const templates = ref<MailTemplate[]>([])
const mailboxes = ref<any[]>([])
const selectedCategory = ref('all')

// View state
const showEditor = ref(false)

// Dialogs
const showDeleteConfirm = ref(false)

// Form states
const templateFormValid = ref(false)
const editMode = ref(false)
const selectedTemplate = ref<MailTemplate | null>(null)

// Loading states
const saving = ref(false)
const deleting = ref(false)

// Form data
const templateFormData = ref({
  name: '',
  description: '',
  template_type: 'personal' as 'personal' | 'mailbox' | 'system',
  owner_mailbox_id: null as number | null,
  subject_template: '',
  body_template: '',
  available_variables: [] as string[],
  is_active: true
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Common variables
const commonVariables = [
  '{{name}}',
  '{{first_name}}',
  '{{last_name}}',
  '{{email}}',
  '{{company}}',
  '{{date}}',
  '{{time}}',
  '{{subject}}',
  '{{sender_name}}'
]

// Computed
const filteredTemplates = computed(() => {
  if (selectedCategory.value === 'all') {
    return templates.value
  }
  return templates.value.filter(t => t.template_type === selectedCategory.value)
})

const categoryOptions = [
  { text: 'Persönlich', value: 'personal' },
  { text: 'Postfach', value: 'mailbox' }
]

// Validation rules
const rules = {
  required: (v: any) => !!v || 'Pflichtfeld'
}

// Methods
function getCategoryColor(type: string) {
  const colors: Record<string, string> = {
    personal: 'primary',
    mailbox: 'secondary',
    system: 'info'
  }
  return colors[type] || 'grey'
}

function getCategoryIcon(type: string) {
  const icons: Record<string, string> = {
    personal: 'mdi-account',
    mailbox: 'mdi-inbox',
    system: 'mdi-cog'
  }
  return icons[type] || 'mdi-file'
}

function getCategoryLabel(type: string) {
  const labels: Record<string, string> = {
    personal: 'Persönlich',
    mailbox: 'Postfach',
    system: 'System'
  }
  return labels[type] || type
}

function getBodyPreview(body: string) {
  // Strip HTML tags and limit to 150 characters
  const text = body.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()
  return text.length > 150 ? text.substring(0, 150) + '...' : text
}

async function fetchTemplates() {
  loading.value = true
  try {
    const data = await mailStore.fetchTemplates()
    if (data) {
      templates.value = data
    }
  } catch (error) {
    showSnackbar('Fehler beim Laden der Vorlagen', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchMailboxes() {
  try {
    const data = await mailStore.fetchCompanyMailboxes()
    if (data) {
      mailboxes.value = data
    }
  } catch (error) {
    console.error('Error fetching mailboxes:', error)
  }
}

function openCreateTemplate() {
  editMode.value = false
  showEditor.value = true
}

function openEditTemplate(template: MailTemplate) {
  editMode.value = true
  selectedTemplate.value = template
  templateFormData.value = {
    name: template.name,
    description: template.description || '',
    template_type: template.template_type,
    owner_mailbox_id: template.owner_mailbox_id,
    subject_template: template.subject_template || '',
    body_template: template.body_template,
    available_variables: template.available_variables || [],
    is_active: template.is_active
  }
  showEditor.value = true
}

function closeTemplateEditor() {
  showEditor.value = false
  editMode.value = false
  selectedTemplate.value = null
  resetTemplateForm()
}

function resetTemplateForm() {
  templateFormData.value = {
    name: '',
    description: '',
    template_type: 'personal',
    owner_mailbox_id: null,
    subject_template: '',
    body_template: '',
    available_variables: [],
    is_active: true
  }
}

function addVariable(variable: string) {
  if (!templateFormData.value.available_variables.includes(variable)) {
    templateFormData.value.available_variables.push(variable)
  }
}

async function saveTemplate() {
  if (!templateFormValid.value) return

  saving.value = true
  try {
    if (editMode.value && selectedTemplate.value) {
      await mailStore.updateTemplate(selectedTemplate.value.id, templateFormData.value)
      showSnackbar('Vorlage erfolgreich aktualisiert', 'success')
    } else {
      await mailStore.createTemplate(templateFormData.value)
      showSnackbar('Vorlage erfolgreich erstellt', 'success')
    }

    closeTemplateEditor()
    await fetchTemplates()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Speichern der Vorlage', 'error')
  } finally {
    saving.value = false
  }
}

function openDeleteDialog(template: MailTemplate) {
  selectedTemplate.value = template
  showDeleteConfirm.value = true
}

function closeDeleteDialog() {
  showDeleteConfirm.value = false
  selectedTemplate.value = null
}

async function deleteTemplate() {
  if (!selectedTemplate.value) return

  deleting.value = true
  try {
    await mailStore.deleteTemplate(selectedTemplate.value.id)
    showSnackbar('Vorlage erfolgreich gelöscht', 'success')
    closeDeleteDialog()
    await fetchTemplates()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Löschen der Vorlage', 'error')
  } finally {
    deleting.value = false
  }
}

async function duplicateTemplate(template: MailTemplate) {
  try {
    const data = {
      name: `${template.name} (Kopie)`,
      description: template.description,
      template_type: 'personal' as const,
      owner_mailbox_id: null,
      subject_template: template.subject_template,
      body_template: template.body_template,
      available_variables: template.available_variables,
      is_active: true
    }

    await mailStore.createTemplate(data)
    showSnackbar('Vorlage erfolgreich dupliziert', 'success')
    await fetchTemplates()
  } catch (error: any) {
    showSnackbar(error.message || 'Fehler beim Duplizieren der Vorlage', 'error')
  }
}

function useTemplate(template: MailTemplate) {
  // Navigate to mail compose with template
  router.push({
    name: 'mail',
    query: { compose: 'true', template: template.id.toString() }
  })
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
  await fetchTemplates()
  await fetchMailboxes()
})
</script>

<style scoped>
.preview-text {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}
</style>
