<template>
  <v-dialog
    v-model="dialogModel"
    max-width="600"
    persistent
  >
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon :icon="widgetDefinition?.icon" class="mr-2" />
        {{ $t('dashboard.configDialog.title', { widget: widgetDefinition?.name }) }}
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="handleClose"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-4">
        <v-form ref="formRef" v-model="formValid">
          <!-- Widget Title -->
          <v-text-field
            v-model="configData.title"
            :label="$t('dashboard.configDialog.widgetTitle')"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            :rules="[v => !!v || $t('validation.required')]"
          />

          <!-- Dynamic fields based on schema -->
          <template v-if="widgetDefinition?.configSchema">
            <div
              v-for="(field, key) in widgetDefinition.configSchema.properties"
              :key="key"
              class="mb-4"
            >
              <!-- Number Input -->
              <v-text-field
                v-if="field.type === 'number'"
                v-model.number="configData[key]"
                :label="$t(`dashboard.configDialog.fields.${key}`, field.label || key)"
                :hint="$t(`dashboard.configDialog.hints.${key}`, field.description || '')"
                variant="outlined"
                density="comfortable"
                type="number"
                :min="field.min"
                :max="field.max"
                :rules="getFieldRules(field)"
              />

              <!-- Boolean Switch -->
              <v-switch
                v-else-if="field.type === 'boolean'"
                v-model="configData[key]"
                :label="$t(`dashboard.configDialog.fields.${key}`, field.label || key)"
                :hint="$t(`dashboard.configDialog.hints.${key}`, field.description || '')"
                color="primary"
                density="comfortable"
                hide-details="auto"
              />

              <!-- Select/Enum -->
              <v-select
                v-else-if="field.enum"
                v-model="configData[key]"
                :label="$t(`dashboard.configDialog.fields.${key}`, field.label || key)"
                :hint="$t(`dashboard.configDialog.hints.${key}`, field.description || '')"
                :items="enumItemsCache[key] || []"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                :rules="getFieldRules(field)"
              />

              <!-- Text Input -->
              <v-text-field
                v-else-if="field.type === 'string'"
                v-model="configData[key]"
                :label="$t(`dashboard.configDialog.fields.${key}`, field.label || key)"
                :hint="$t(`dashboard.configDialog.hints.${key}`, field.description || '')"
                variant="outlined"
                density="comfortable"
                :rules="getFieldRules(field)"
              />

              <!-- Array (multi-select) -->
              <v-select
                v-else-if="field.type === 'array'"
                v-model="configData[key]"
                :label="$t(`dashboard.configDialog.fields.${key}`, field.label || key)"
                :hint="$t(`dashboard.configDialog.hints.${key}`, field.description || '')"
                :items="field.items?.enum || []"
                variant="outlined"
                density="comfortable"
                multiple
                chips
                :rules="getFieldRules(field)"
              />
            </div>
          </template>

          <!-- Refresh Interval -->
          <v-select
            v-if="widgetDefinition?.realtime"
            v-model="configData.refreshInterval"
            :label="$t('dashboard.configDialog.refreshInterval')"
            :items="refreshIntervals"
            item-title="label"
            item-value="value"
            variant="outlined"
            density="comfortable"
          />
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions>
        <v-btn
          variant="text"
          @click="handleReset"
        >
          {{ $t('dashboard.configDialog.resetToDefault') }}
        </v-btn>
        <v-spacer />
        <v-btn
          variant="text"
          @click="handleClose"
        >
          {{ $t('cancel') }}
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :disabled="!formValid"
          @click="handleSave"
        >
          {{ $t('save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useWidgetRegistry } from '@/composables/useWidgetRegistry'
import { useI18n } from 'vue-i18n'
import type { WidgetConfig } from '@/stores/dashboardStore'

interface Props {
  modelValue: boolean
  widgetId: string | null
  widgetConfig: WidgetConfig | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', widgetId: string, config: WidgetConfig): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { getWidgetDefinition } = useWidgetRegistry()
const { t } = useI18n()

const formRef = ref()
const formValid = ref(false)
const configData = ref<any>({})

// Dialog model
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Widget definition
const widgetDefinition = computed(() => {
  if (!props.widgetConfig) return null
  return getWidgetDefinition(props.widgetConfig.type)
})

// Refresh interval options
const refreshIntervals = computed(() => [
  { label: t('dashboard.configDialog.intervals.manual'), value: 0 },
  { label: t('dashboard.configDialog.intervals.30s'), value: 30 },
  { label: t('dashboard.configDialog.intervals.1min'), value: 60 },
  { label: t('dashboard.configDialog.intervals.5min'), value: 300 },
  { label: t('dashboard.configDialog.intervals.15min'), value: 900 }
])

// Get translated enum items for all fields
const enumItemsCache = computed(() => {
  if (!widgetDefinition.value?.configSchema?.properties) return {}

  const cache: Record<string, any[]> = {}
  Object.entries(widgetDefinition.value.configSchema.properties).forEach(([key, field]: [string, any]) => {
    if (field.enum) {
      if (field.enumLabels && field.enumLabels.length === field.enum.length) {
        cache[key] = field.enum.map((value: string, index: number) => ({
          title: t(field.enumLabels[index]),
          value: value
        }))
      } else {
        cache[key] = field.enum.map((value: string) => ({
          title: value,
          value: value
        }))
      }
    }
  })
  return cache
})

/**
 * Get validation rules for a field
 */
function getFieldRules(field: any) {
  const rules: any[] = []

  if (field.required) {
    rules.push((v: any) => !!v || t('validation.required'))
  }

  if (field.type === 'number') {
    if (field.min !== undefined) {
      rules.push((v: any) => v >= field.min || t('validation.min', { min: field.min }))
    }
    if (field.max !== undefined) {
      rules.push((v: any) => v <= field.max || t('validation.max', { max: field.max }))
    }
  }

  return rules
}

/**
 * Initialize config data from widget config
 */
function initializeConfigData() {
  if (!props.widgetConfig) {
    configData.value = {}
    return
  }

  // Clone current config
  configData.value = {
    title: props.widgetConfig.title || widgetDefinition.value?.name || '',
    refreshInterval: props.widgetConfig.refreshInterval || 0,
    ...JSON.parse(JSON.stringify(props.widgetConfig))
  }

  // Set defaults from schema if not present
  if (widgetDefinition.value?.configSchema?.properties) {
    Object.entries(widgetDefinition.value.configSchema.properties).forEach(([key, field]: [string, any]) => {
      if (configData.value[key] === undefined && field.default !== undefined) {
        configData.value[key] = field.default
      }
    })
  }
}

/**
 * Reset to default values
 */
function handleReset() {
  if (!widgetDefinition.value?.configSchema?.properties) return

  Object.entries(widgetDefinition.value.configSchema.properties).forEach(([key, field]: [string, any]) => {
    if (field.default !== undefined) {
      configData.value[key] = field.default
    }
  })

  configData.value.title = widgetDefinition.value.name
  configData.value.refreshInterval = 0
}

/**
 * Handle save
 */
function handleSave() {
  if (!props.widgetId || !formValid.value) return

  emit('save', props.widgetId, configData.value)
  emit('update:modelValue', false)
}

/**
 * Handle close
 */
function handleClose() {
  emit('update:modelValue', false)
}

// Watch for dialog open to initialize data
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    initializeConfigData()
  }
})
</script>

<style scoped lang="scss">
// Add any custom styles if needed
</style>
