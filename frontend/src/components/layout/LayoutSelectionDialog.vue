<template>
  <v-dialog
    v-model="dialogVisible"
    max-width="800"
    persistent
    :scrim="true"
  >
    <v-card class="layout-selection-card" elevation="8">
      <v-card-title class="text-h5 text-center pa-6">
        {{ $t('layoutSelection.title') }}
      </v-card-title>

      <v-card-subtitle class="text-center px-6 pb-4">
        {{ $t('layoutSelection.subtitle') }}
      </v-card-subtitle>

      <v-card-text class="px-8 pb-8">
        <v-row>
          <!-- Desktop Mode Option -->
          <v-col cols="12" md="6">
            <v-card
              class="layout-option"
              :class="{ 'selected': selectedLayout === 'desktop' }"
              @click="selectedLayout = 'desktop'"
              hover
              elevation="2"
            >
              <div class="layout-preview desktop-preview">
                <v-icon size="80" color="primary">mdi-monitor</v-icon>
              </div>
              <v-card-title class="text-center">
                {{ $t('layoutSelection.desktopMode') }}
              </v-card-title>
              <v-card-text class="text-center">
                {{ $t('layoutSelection.desktopDescription') }}
              </v-card-text>

              <v-chip
                v-if="selectedLayout === 'desktop'"
                color="primary"
                class="selection-chip"
                prepend-icon="mdi-check-circle"
              >
                {{ $t('layoutSelection.selected') }}
              </v-chip>
            </v-card>
          </v-col>

          <!-- Sidebar Mode Option -->
          <v-col cols="12" md="6">
            <v-card
              class="layout-option"
              :class="{ 'selected': selectedLayout === 'sidebar' }"
              @click="selectedLayout = 'sidebar'"
              hover
              elevation="2"
            >
              <div class="layout-preview sidebar-preview">
                <v-icon size="80" color="secondary">mdi-view-list</v-icon>
              </div>
              <v-card-title class="text-center">
                {{ $t('layoutSelection.sidebarMode') }}
              </v-card-title>
              <v-card-text class="text-center">
                {{ $t('layoutSelection.sidebarDescription') }}
              </v-card-text>

              <v-chip
                v-if="selectedLayout === 'sidebar'"
                color="primary"
                class="selection-chip"
                prepend-icon="mdi-check-circle"
              >
                {{ $t('layoutSelection.selected') }}
              </v-chip>
            </v-card>
          </v-col>
        </v-row>

        <!-- Remember choice checkbox -->
        <v-row class="mt-4">
          <v-col cols="12" class="text-center">
            <v-checkbox
              v-model="rememberChoice"
              :label="$t('layoutSelection.rememberChoice')"
              color="primary"
              hide-details
              density="compact"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions class="px-8 pb-6">
        <v-spacer />
        <v-btn
          color="primary"
          size="large"
          :disabled="!selectedLayout"
          @click="confirmSelection"
          rounded
          min-width="200"
        >
          {{ $t('layoutSelection.continue') }}
          <v-icon end>mdi-arrow-right</v-icon>
        </v-btn>
        <v-spacer />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { LayoutPreference } from '@/types/Menu';

interface Props {
  modelValue: boolean;
  defaultLayout?: LayoutPreference;
}

const props = withDefaults(defineProps<Props>(), {
  defaultLayout: null
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'select': [layout: LayoutPreference, remember: boolean];
}>();

// Local state
const dialogVisible = ref(props.modelValue);
const selectedLayout = ref<LayoutPreference>(props.defaultLayout);
const rememberChoice = ref(false);

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
  dialogVisible.value = newVal;
});

watch(dialogVisible, (newVal) => {
  emit('update:modelValue', newVal);
});

/**
 * Confirm layout selection
 */
function confirmSelection() {
  if (selectedLayout.value) {
    emit('select', selectedLayout.value, rememberChoice.value);
    dialogVisible.value = false;
  }
}
</script>

<style lang="scss" scoped>
.layout-selection-card {
  background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
  backdrop-filter: blur(30px);
  border: 1px solid var(--k-line);
}

.layout-option {
  position: relative;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid transparent;
  min-height: 320px;
  display: flex;
  flex-direction: column;

  &:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
  }

  &.selected {
    border-color: rgb(var(--v-theme-primary));
    box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.15);
  }
}

.layout-preview {
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--k-accent-weak), rgba(99, 102, 241, 0.05));
  border-bottom: 1px solid var(--k-line);
  transition: all 0.3s ease;

  .v-icon {
    transition: all 0.3s ease;
  }
}

.layout-option:hover .layout-preview {
  background: linear-gradient(135deg, var(--k-accent-weak), rgba(99, 102, 241, 0.1));

  .v-icon {
    transform: scale(1.1);
  }
}

.layout-option.selected .layout-preview {
  background: linear-gradient(135deg, var(--k-accent-weak), rgba(99, 102, 241, 0.15));
}

.selection-chip {
  position: absolute;
  top: 12px;
  right: 12px;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.v-card-title {
  font-weight: 600;
  padding-top: 16px;
}

.v-card-text {
  color: var(--k-ink-muted);
  font-size: 0.9rem;
  line-height: 1.5;
}
</style>
