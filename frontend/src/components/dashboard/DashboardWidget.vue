<template>
  <v-card
    class="dashboard-widget"
    :class="{
      'edit-mode': editMode,
      'has-error': hasError,
      'is-loading': isLoading
    }"
    :elevation="0"
  >
    <!-- Widget Header -->
    <v-card-title class="widget-header d-flex justify-space-between align-center pa-3">
      <div class="widget-title-container d-flex align-center">
        <v-icon
          v-if="widgetDefinition"
          :icon="widgetDefinition.icon"
          size="small"
          class="mr-2"
        />
        <span class="widget-title text-subtitle-1">{{ widgetTitle }}</span>
        <v-chip
          v-if="widgetDefinition?.realtime"
          size="x-small"
          color="success"
          variant="flat"
          class="ml-2 realtime-indicator"
        >
          <v-icon size="x-small" start>mdi-circle</v-icon>
          Live
        </v-chip>
      </div>

      <!-- Widget Actions -->
      <div v-if="editMode" class="widget-actions d-flex ga-1">
        <v-btn
          v-if="widgetDefinition?.configurable"
          icon
          size="x-small"
          variant="text"
          @click.stop="$emit('configure')"
        >
          <v-icon size="small">mdi-cog</v-icon>
          <v-tooltip activator="parent" location="top">
            {{ $t('dashboard.widget.configure') }}
          </v-tooltip>
        </v-btn>

        <v-btn
          icon
          size="x-small"
          variant="text"
          color="error"
          @click.stop="handleRemove"
        >
          <v-icon size="small">mdi-close</v-icon>
          <v-tooltip activator="parent" location="top">
            {{ $t('dashboard.widget.remove') }}
          </v-tooltip>
        </v-btn>
      </div>

      <!-- Widget Menu (non-edit mode) -->
      <div v-else class="widget-menu">
        <v-menu location="bottom end">
          <template #activator="{ props: menuProps }">
            <v-btn
              icon
              size="x-small"
              variant="text"
              v-bind="menuProps"
            >
              <v-icon size="small">mdi-dots-vertical</v-icon>
            </v-btn>
          </template>

          <v-list density="compact">
            <v-list-item
              v-if="hasRefresh"
              @click="handleRefresh"
            >
              <template #prepend>
                <v-icon size="small">mdi-refresh</v-icon>
              </template>
              <v-list-item-title>{{ $t('dashboard.widget.refresh') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
              v-if="widgetDefinition?.configurable"
              @click="$emit('configure')"
            >
              <template #prepend>
                <v-icon size="small">mdi-cog</v-icon>
              </template>
              <v-list-item-title>{{ $t('dashboard.widget.settings') }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </div>
    </v-card-title>

    <v-divider />

    <!-- Widget Content -->
    <v-card-text class="widget-body pa-3">
      <Suspense>
        <template #default>
          <component
            :is="widgetComponent"
            v-if="widgetComponent && !hasError"
            :widget-id="widgetId"
            :config="widgetConfig"
            @error="handleError"
            @loading="handleLoading"
            @refresh="hasRefresh = true"
          />
        </template>

        <template #fallback>
          <div class="widget-loading">
            <v-progress-circular indeterminate size="32" />
          </div>
        </template>
      </Suspense>

      <!-- Error State -->
      <!--
        Fehlerzustand in einer Zeile statt als Block: ein 48-px-Symbol traegt
        keine Information, die Meldung schon.
      -->
      <div v-if="hasError" class="widget-error">
        <v-icon size="20" color="error">mdi-alert-circle-outline</v-icon>
        <p class="text-body-2">{{ errorMessage }}</p>
        <v-btn
          size="small"
          variant="text"
          color="primary"
          @click="handleRetry"
        >
          {{ $t('dashboard.widget.retry') }}
        </v-btn>
      </div>
    </v-card-text>

    <!-- Loading Overlay -->
    <v-overlay
      v-if="isLoading"
      contained
      persistent
      class="widget-loading-overlay"
      scrim="transparent"
    >
      <v-progress-circular indeterminate size="32" />
    </v-overlay>

    <!-- Edit Mode Overlay -->
    <div v-if="editMode" class="edit-mode-overlay">
      <v-icon size="large">mdi-drag</v-icon>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useWidgetRegistry } from '@/composables/useWidgetRegistry'
import type { WidgetConfig } from '@/stores/dashboardStore'

interface Props {
  widgetId: string
  widgetConfig: WidgetConfig
  editMode: boolean
}

interface Emits {
  (e: 'remove'): void
  (e: 'configure'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { t, te } = useI18n()
const { getWidgetDefinition } = useWidgetRegistry()

// Widget state
const hasError = ref(false)
const errorMessage = ref('')
const isLoading = ref(false)
const hasRefresh = ref(false)
const retryKey = ref(0)

// Get widget definition
const widgetDefinition = computed(() => {
  return getWidgetDefinition(props.widgetConfig.type)
})

// Translated widget title
const widgetTitle = computed(() => {
  const title = props.widgetConfig.title

  // Der Titel ist bereits ein i18n-Schluessel
  if (title && te(title)) {
    return t(title)
  }

  // Gespeicherte Layouts tragen den englischen Klartext aus der Registry
  // ("My Crew", "Announcements" ...). Die deutschen Namen liegen unter
  // dashboard.widget.names.<typ> bereits vor und werden hier benutzt.
  const byType = `dashboard.widget.names.${props.widgetConfig.type}`
  if (te(byType)) {
    return t(byType)
  }

  return title
})

// Load widget component
const widgetComponent = computed(() => {
  if (!widgetDefinition.value || hasError.value) {
    return null
  }

  try {
    return defineAsyncComponent({
      loader: widgetDefinition.value.component,
      timeout: 30000,
      errorComponent: null,
      onError(error) {
        console.error(`Failed to load widget ${props.widgetConfig.type}:`, error)
        hasError.value = true
        errorMessage.value = t('dashboard.widget.errors.loadFailed')
      }
    })
  } catch (error) {
    console.error('Error creating widget component:', error)
    hasError.value = true
    errorMessage.value = t('dashboard.widget.errors.invalidConfig')
    return null
  }
})

/**
 * Handle widget removal with confirmation
 */
function handleRemove() {
  // In edit mode, remove immediately
  if (props.editMode) {
    emit('remove')
  }
}

/**
 * Handle widget error
 */
function handleError(error: string | Error) {
  hasError.value = true
  errorMessage.value = typeof error === 'string' ? error : error.message
  isLoading.value = false
}

/**
 * Handle loading state
 */
function handleLoading(loading: boolean) {
  isLoading.value = loading
}

/**
 * Handle refresh
 */
function handleRefresh() {
  hasError.value = false
  errorMessage.value = ''
  retryKey.value++
}

/**
 * Handle retry after error
 */
function handleRetry() {
  handleRefresh()
}

// Watch widget config changes
watch(
  () => props.widgetConfig,
  () => {
    // Reset error state when config changes
    hasError.value = false
    errorMessage.value = ''
  },
  { deep: true }
)
</script>

<style scoped lang="scss">
.dashboard-widget {
  position: relative;
  height: 100%;
  /*
    Kein min-height von 200 px mehr. Der Entwurf ist hier deutlich: "Ein
    Leerzustand darf nicht mehr Platz beanspruchen als der gefuellte Zustand -
    sonst ist der Bildschirm am leersten, wenn am meisten los ist." Eine
    Kachel mit einem Satz Inhalt streckte sich vorher auf volle Hoehe.
  */
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--k-surface);
  border: 1px solid var(--k-line);
  border-radius: 6px;
  /* Kein Schatten: nur was schwebt (Menue, Dialog, Fenster) wirft einen. */
  box-shadow: none !important;
  transition: border-color 150ms cubic-bezier(0.16, 1, 0.3, 1);

  &:hover {
    border-color: var(--k-line-strong);
  }

  &.edit-mode {
    cursor: move;

    .widget-body {
      pointer-events: none;
      opacity: 0.7;
    }

    &:hover {
      border-color: var(--k-accent);
    }
  }

  &.has-error {
    border-color: rgba(var(--v-theme-error), 0.5);
  }

  &.is-loading {
    opacity: 0.8;
  }
}

/*
  Kopfzeile eines Panels, nicht einer Karte.

  Vorher: 48 px hoch und mit 8 % Akzentfarbe hinterlegt. Der Akzent markiert
  im System aber genau zwei Dinge - wo man gerade ist und was die Hauptaktion
  ist. Sieben Kachelkoepfe nebeneinander in Akzentfarbe sagen weder das eine
  noch das andere; sie faerben nur.

  Jetzt: 34 px auf gesenkter Flaeche, wie jede andere Panel-Kopfzeile. Das
  spart pro Kachel 14 px, was auf einem Dashboard mit sieben Kacheln knapp
  hundert Pixel Inhalt zurueckgibt.
*/
.widget-header {
  flex-shrink: 0;
  background: var(--k-sunken, #fafbfc);
  border-bottom: 1px solid var(--k-line, #e2e5ea);
  min-height: 34px !important;
  height: 34px;
  padding: 0 12px !important;
}

.widget-title-container {
  flex: 1;
  min-width: 0;
}

.widget-title {
  font-size: 12px !important;
  font-weight: 600;
  color: var(--k-ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.realtime-indicator {
  height: 20px !important;
  font-size: 10px;

  :deep(.v-icon) {
    animation: pulse 2s infinite;
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.widget-actions,
.widget-menu {
  flex-shrink: 0;
}

.widget-body {
  flex: 1;
  overflow: hidden;
  position: relative;

  // Custom scrollbar (only visible when content overflows)
  &::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }

  &::-webkit-scrollbar-track {
    background: var(--k-row-hover);
  }

  &::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 3px;

    &:hover {
      background: var(--k-row-hover);
    }
  }
}

.widget-loading,
/* Eine Zeile, kein Block. Vorher: 200 px Mindesthoehe und 2 rem Rand um ein
   Symbol herum - der Fehlerzustand nahm mehr Platz als der gefuellte. */
.widget-error {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  text-align: left;
  color: var(--k-critical);

  p {
    margin: 0;
    font-size: 12.5px;
  }

  .v-btn {
    margin-left: auto;
  }
}

.widget-loading-overlay {
  background: rgba(var(--v-theme-surface), 0.8);
  backdrop-filter: blur(2px);
}

.edit-mode-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s ease;

  .dashboard-widget.edit-mode:hover & {
    opacity: 1;
  }
}

// Responsive
@media (max-width: 600px) {
  .widget-header {
    padding: 6px 8px !important;
  }

  .widget-title {
    font-size: 0.9rem;
  }

  .widget-body {
    padding: 8px !important;
  }
}
</style>
