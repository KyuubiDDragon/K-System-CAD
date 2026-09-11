<template>
  <div class="widget-placeholder">
    <v-icon :icon="icon" size="48" color="grey" class="mb-3" />
    <p class="text-body-1 font-weight-medium">{{ title }}</p>
    <p class="text-caption text-grey">{{ description }}</p>
    <v-chip size="small" color="info" variant="outlined" class="mt-2">
      {{ $t('dashboard.widget.placeholder.comingSoon') }}
    </v-chip>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useWidgetRegistry } from '@/composables/useWidgetRegistry'

interface Props {
  widgetId: string
  config: {
    type: string
    title: string
  }
}

const props = defineProps<Props>()
const { t } = useI18n()
const { getWidgetDefinition } = useWidgetRegistry()

const widgetDef = computed(() => getWidgetDefinition(props.config.type))

const icon = computed(() => widgetDef.value?.icon || 'mdi-widgets')
const title = computed(() => props.config.title || t('dashboard.widget.placeholder.fallbackTitle'))
const description = computed(() => widgetDef.value?.description
    ? t(widgetDef.value.description)
    : t('dashboard.widget.placeholder.fallbackDescription'))
</script>

<style scoped lang="scss">
.widget-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 150px;
  text-align: center;
  padding: 2rem;
  opacity: 0.7;
}
</style>
