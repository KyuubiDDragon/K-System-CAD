<template>
  <div class="dashboard-view">
    <!-- Toolbar -->
    <v-toolbar
      elevation="0"
      class="dashboard-toolbar"
    >
      <v-toolbar-title>
        <v-icon icon="mdi-view-dashboard" class="mr-2" />
        {{ $t('dashboard.title') }}
      </v-toolbar-title>

      <v-spacer />

      <!-- Toolbar Actions -->
      <template v-if="!dashboardStore.editMode">
        <v-btn
          variant="text"
          @click="showTemplatesDialog = true"
          :disabled="dashboardStore.loading"
        >
          <v-icon start>mdi-view-grid-plus</v-icon>
          {{ $t('dashboard.templates') }}
        </v-btn>

        <v-btn
          variant="text"
          @click="showExportImportDialog = true"
        >
          <v-icon start>mdi-import-export</v-icon>
          {{ $t('dashboard.exportImport') }}
        </v-btn>

        <v-btn
          color="primary"
          @click="enableEditMode"
          :disabled="dashboardStore.loading"
        >
          <v-icon start>mdi-pencil</v-icon>
          {{ $t('dashboard.editMode') }}
        </v-btn>
      </template>

      <!-- Edit Mode Actions -->
      <template v-else>
        <v-chip
          color="primary"
          variant="flat"
          class="mr-4"
        >
          <v-icon start size="small">mdi-pencil</v-icon>
          {{ $t('dashboard.editModeActive') }}
        </v-chip>

        <v-btn
          variant="text"
          @click="showWidgetCatalog = true"
        >
          <v-icon start>mdi-plus</v-icon>
          {{ $t('dashboard.addWidget') }}
        </v-btn>

        <v-btn
          variant="text"
          @click="handleCancelEdit"
        >
          {{ $t('cancel') }}
        </v-btn>

        <v-btn
          color="primary"
          @click="handleSaveLayout"
          :loading="dashboardStore.saving"
        >
          <v-icon start>mdi-content-save</v-icon>
          {{ $t('save') }}
        </v-btn>
      </template>
    </v-toolbar>

    <!-- Dashboard Content -->
    <v-container fluid class="dashboard-content pa-4">
      <!-- Loading State -->
      <div v-if="dashboardStore.loading" class="loading-container">
        <v-progress-circular indeterminate size="64" color="primary" />
        <p class="text-h6 mt-4">{{ $t('dashboard.loading') }}</p>
      </div>

      <!-- Dashboard Grid -->
      <DashboardGrid
        v-else
        v-model:layout="dashboardStore.layout"
        :widgets="dashboardStore.widgets"
        :edit-mode="dashboardStore.editMode"
        @remove-widget="handleRemoveWidget"
        @configure-widget="handleConfigureWidget"
        @add-widget="showWidgetCatalog = true"
        @layout-updated="onLayoutUpdated"
      />
    </v-container>

    <!-- Widget Catalog Dialog -->
    <v-dialog
      v-model="showWidgetCatalog"
      max-width="900"
      scrollable
    >
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-widgets" class="mr-2" />
          {{ $t('dashboard.widgetCatalog.title') }}
          <v-spacer />
          <v-btn
            icon="mdi-close"
            variant="text"
            @click="showWidgetCatalog = false"
          />
        </v-card-title>

        <v-divider />

        <!-- Category Tabs -->
        <v-tabs
          v-model="selectedCategory"
          bg-color="surface"
          color="primary"
          grow
        >
          <v-tab value="all">
            <v-icon start>mdi-all-inclusive</v-icon>
            {{ $t('dashboard.widgetCatalog.all') }}
          </v-tab>
          <v-tab
            v-for="cat in widgetCategories"
            :key="cat.key"
            :value="cat.key"
          >
            <v-icon :icon="cat.icon" start size="small" />
            {{ cat.label }}
          </v-tab>
        </v-tabs>

        <v-divider />

        <v-card-text style="max-height: 500px;">
          <v-row>
            <v-col
              v-for="widget in filteredAvailableWidgets"
              :key="widget.key"
              cols="12"
              sm="6"
              md="4"
            >
              <v-card
                class="widget-catalog-card"
                :ripple="false"
                @click="handleAddWidget(widget.key)"
              >
                <v-card-text>
                  <div class="d-flex align-center mb-2">
                    <v-icon :icon="widget.icon" size="large" color="primary" class="mr-3" />
                    <div>
                      <div class="text-subtitle-1 font-weight-medium">{{ widget.name }}</div>
                      <v-chip
                        v-if="widget.realtime"
                        size="x-small"
                        color="success"
                        variant="flat"
                      >
                        Live
                      </v-chip>
                    </div>
                  </div>
                  <p class="text-caption text-grey">{{ widget.description }}</p>
                </v-card-text>
                <v-card-actions>
                  <v-btn
                    block
                    color="primary"
                    variant="flat"
                    size="small"
                  >
                    <v-icon start size="small">mdi-plus</v-icon>
                    {{ $t('dashboard.widgetCatalog.add') }}
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>

          <!-- Empty State -->
          <div v-if="filteredAvailableWidgets.length === 0" class="text-center pa-8">
            <v-icon size="64" color="grey">mdi-widgets-outline</v-icon>
            <p class="text-body-1 mt-4">{{ $t('dashboard.widgetCatalog.noWidgets') }}</p>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Templates Dialog -->
    <v-dialog
      v-model="showTemplatesDialog"
      max-width="900"
      scrollable
    >
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-view-grid-plus" class="mr-2" />
          {{ $t('dashboard.templates') }}
          <v-spacer />
          <v-btn
            icon="mdi-close"
            variant="text"
            @click="showTemplatesDialog = false"
          />
        </v-card-title>

        <v-divider />

        <v-card-text style="max-height: 500px;">
          <v-alert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            {{ $t('dashboard.templateDialog.info') }}
          </v-alert>

          <v-row>
            <v-col
              v-for="template in dashboardStore.availableTemplates"
              :key="template.key"
              cols="12"
              sm="6"
              md="4"
            >
              <v-card
                class="template-card"
                :ripple="false"
                @click="handleApplyTemplate(template.key)"
              >
                <v-card-text>
                  <div class="d-flex align-center mb-3">
                    <v-icon :icon="template.icon" size="x-large" color="primary" />
                  </div>
                  <h3 class="text-h6 mb-2">{{ template.name }}</h3>
                  <p class="text-caption text-grey mb-2">{{ template.description }}</p>
                  <v-chip size="small" variant="outlined">
                    {{ template.layout.length }} {{ $t('dashboard.widgets') }}
                  </v-chip>
                </v-card-text>
                <v-card-actions>
                  <v-btn
                    block
                    color="primary"
                    variant="flat"
                  >
                    {{ $t('dashboard.applyTemplate') }}
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Export/Import Dialog -->
    <v-dialog
      v-model="showExportImportDialog"
      max-width="600"
    >
      <v-card>
        <v-card-title>
          <v-icon icon="mdi-swap-horizontal" class="mr-2" />
          {{ $t('dashboard.exportImport') }}
        </v-card-title>

        <v-divider />

        <v-card-text>
          <v-tabs v-model="exportImportTab" grow>
            <v-tab value="export">
              <v-icon start>mdi-export</v-icon>
              {{ $t('dashboard.export') }}
            </v-tab>
            <v-tab value="import">
              <v-icon start>mdi-import</v-icon>
              {{ $t('dashboard.import') }}
            </v-tab>
          </v-tabs>

          <v-window v-model="exportImportTab" class="mt-4">
            <!-- Export Tab -->
            <v-window-item value="export">
              <p class="mb-4">{{ $t('dashboard.exportDialog.description') }}</p>

              <v-btn
                block
                color="primary"
                size="large"
                @click="handleExport"
                :loading="exporting"
              >
                <v-icon start>mdi-download</v-icon>
                {{ $t('dashboard.exportAsJson') }}
              </v-btn>

              <v-text-field
                v-if="exportHash"
                v-model="exportHash"
                :label="$t('dashboard.exportHash')"
                readonly
                variant="outlined"
                class="mt-4"
                :append-inner-icon="'mdi-content-copy'"
                @click:append-inner="copyExportHash"
              />
            </v-window-item>

            <!-- Import Tab -->
            <v-window-item value="import">
              <v-tabs v-model="importMethod" grow>
                <v-tab value="file">{{ $t('dashboard.importFromFile') }}</v-tab>
                <v-tab value="hash">{{ $t('dashboard.importFromHash') }}</v-tab>
              </v-tabs>

              <v-window v-model="importMethod" class="mt-4">
                <!-- Import from File -->
                <v-window-item value="file">
                  <v-file-input
                    v-model="importFile"
                    :label="$t('dashboard.selectJsonFile')"
                    accept=".json"
                    prepend-icon="mdi-file-document"
                    variant="outlined"
                    class="mb-4"
                  />

                  <v-btn
                    block
                    color="primary"
                    @click="handleImportFromFile"
                    :disabled="!importFile"
                    :loading="importing"
                  >
                    <v-icon start>mdi-import</v-icon>
                    {{ $t('dashboard.import') }}
                  </v-btn>
                </v-window-item>

                <!-- Import from Hash -->
                <v-window-item value="hash">
                  <v-text-field
                    v-model="importHash"
                    :label="$t('dashboard.enterExportHash')"
                    variant="outlined"
                    class="mb-4"
                  />

                  <v-btn
                    block
                    color="primary"
                    @click="handleImportFromHash"
                    :disabled="!importHash"
                    :loading="importing"
                  >
                    <v-icon start>mdi-import</v-icon>
                    {{ $t('dashboard.import') }}
                  </v-btn>
                </v-window-item>
              </v-window>
            </v-window-item>
          </v-window>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showExportImportDialog = false"
          >
            {{ $t('close') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Widget Configuration Dialog -->
    <WidgetConfigDialog
      v-model="showConfigDialog"
      :widget-id="configWidgetId"
      :widget-config="configWidgetConfig"
      @save="handleSaveWidgetConfig"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboardStore'
import { useModulePermission } from '@/composables/useModulePermission'
import { useWidgetRegistry, getWidgetCategories } from '@/composables/useWidgetRegistry'
import DashboardGrid from '@/components/dashboard/DashboardGrid.vue'
import WidgetConfigDialog from '@/components/dashboard/WidgetConfigDialog.vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'

const dashboardStore = useDashboardStore()
const { hasModulePermission } = useModulePermission() // Module-based permission system
const { getAvailableWidgets } = useWidgetRegistry()
const toast = useToast()
const { t } = useI18n()

// Dialog states
const showWidgetCatalog = ref(false)
const showTemplatesDialog = ref(false)
const showExportImportDialog = ref(false)
const showConfigDialog = ref(false)
const configWidgetId = ref<string | null>(null)
const configWidgetConfig = ref<any>(null)

// Widget catalog
const selectedCategory = ref('all')
const widgetCategories = getWidgetCategories()

// Export/Import
const exportImportTab = ref('export')
const importMethod = ref('file')
const exportHash = ref('')
const importHash = ref('')
const importFile = ref<File | null>(null)
const exporting = ref(false)
const importing = ref(false)

// Available widgets based on permissions
// The getAvailableWidgets() function now uses the new permission system internally
const availableWidgets = computed(() => {
  return getAvailableWidgets()
})

// Filtered widgets by category
const filteredAvailableWidgets = computed(() => {
  if (selectedCategory.value === 'all') {
    return dashboardStore.availableWidgets
  }
  return dashboardStore.availableWidgets.filter(
    w => w.category === selectedCategory.value
  )
})

/**
 * Initialize dashboard
 */
onMounted(async () => {
  try {
    await Promise.all([
      dashboardStore.loadLayout(),
      dashboardStore.loadAvailableWidgets(),
      dashboardStore.loadTemplates()
    ])
  } catch (error) {
    console.error('Failed to initialize dashboard:', error)
    toast.error(t('dashboard.errors.loadFailed'))
  }
})

/**
 * Enable edit mode
 */
function enableEditMode() {
  dashboardStore.enableEditMode()
}

/**
 * Handle save layout
 */
async function handleSaveLayout() {
  try {
    await dashboardStore.saveLayout()
    dashboardStore.disableEditMode()
    toast.success(t('dashboard.messages.saved'))
  } catch (error) {
    console.error('Failed to save layout:', error)
    toast.error(t('dashboard.errors.saveFailed'))
  }
}

/**
 * Handle cancel edit
 */
async function handleCancelEdit() {
  // Reload layout to discard changes
  await dashboardStore.loadLayout()
  dashboardStore.disableEditMode()
}

/**
 * Handle add widget
 */
function handleAddWidget(widgetKey: string) {
  dashboardStore.addWidget(widgetKey)
  showWidgetCatalog.value = false
  toast.success(t('dashboard.messages.widgetAdded'))
}

/**
 * Handle remove widget
 */
function handleRemoveWidget(widgetId: string) {
  dashboardStore.removeWidget(widgetId)
  toast.info(t('dashboard.messages.widgetRemoved'))
}

/**
 * Handle configure widget
 */
function handleConfigureWidget(widgetId: string) {
  const widget = dashboardStore.widgets[widgetId]
  if (!widget) return

  configWidgetId.value = widgetId
  configWidgetConfig.value = widget
  showConfigDialog.value = true
}

/**
 * Handle save widget config
 */
async function handleSaveWidgetConfig(widgetId: string, config: any) {
  try {
    await dashboardStore.updateWidgetConfig(widgetId, config)
    toast.success(t('dashboard.messages.configSaved'))
  } catch (error) {
    console.error('Failed to save widget config:', error)
    toast.error(t('dashboard.errors.configSaveFailed'))
  }
}

/**
 * Handle layout updated
 */
function onLayoutUpdated(newLayout: any) {
  // Auto-update layout in store
  // dashboardStore.updateLayout(newLayout)
}

/**
 * Handle apply template
 */
async function handleApplyTemplate(templateKey: string) {
  try {
    await dashboardStore.applyTemplate(templateKey)
    showTemplatesDialog.value = false
    toast.success(t('dashboard.messages.templateApplied'))
  } catch (error) {
    console.error('Failed to apply template:', error)
    toast.error(t('dashboard.errors.templateFailed'))
  }
}

/**
 * Handle export
 */
async function handleExport() {
  exporting.value = true
  try {
    exportHash.value = await dashboardStore.exportLayout()
    toast.success(t('dashboard.messages.exported'))
  } catch (error) {
    console.error('Failed to export layout:', error)
    toast.error(t('dashboard.errors.exportFailed'))
  } finally {
    exporting.value = false
  }
}

/**
 * Copy export hash to clipboard
 */
function copyExportHash() {
  navigator.clipboard.writeText(exportHash.value)
  toast.success(t('dashboard.messages.hashCopied'))
}

/**
 * Handle import from file
 */
async function handleImportFromFile() {
  if (!importFile.value) return

  importing.value = true
  try {
    const text = await importFile.value.text()
    const importData = JSON.parse(text)

    await dashboardStore.importLayout({ importData })

    showExportImportDialog.value = false
    importFile.value = null
    toast.success(t('dashboard.messages.imported'))
  } catch (error) {
    console.error('Failed to import layout:', error)
    toast.error(t('dashboard.errors.importFailed'))
  } finally {
    importing.value = false
  }
}

/**
 * Handle import from hash
 */
async function handleImportFromHash() {
  if (!importHash.value) return

  importing.value = true
  try {
    await dashboardStore.importLayout({ exportHash: importHash.value })

    showExportImportDialog.value = false
    importHash.value = ''
    toast.success(t('dashboard.messages.imported'))
  } catch (error) {
    console.error('Failed to import layout:', error)
    toast.error(t('dashboard.errors.importFailed'))
  } finally {
    importing.value = false
  }
}
</script>

<style scoped lang="scss">
.dashboard-view {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: rgba(var(--v-theme-background));
}

.dashboard-toolbar {
  flex-shrink: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.dashboard-content {
  flex: 1;
  overflow: hidden;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

// Widget Catalog Card
.widget-catalog-card {
  height: 100%;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid rgba(255, 255, 255, 0.05);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
}

// Template Card
.template-card {
  height: 100%;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid rgba(255, 255, 255, 0.05);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
}
</style>
