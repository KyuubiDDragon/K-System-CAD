<template>
  <v-snackbar
      v-model="isVisible"
      :color="snackbarColor"
      location="top end"
      :timeout="4000"
      variant="elevated"
  >
      {{ model?.message || '' }}
      <template v-slot:actions>
          <v-btn variant="text" @click="isVisible = false">{{ t('general.close') }}</v-btn>
        </template>
  </v-snackbar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

// --- Define Interface for Model ---
interface SnackbarState {
  visible: boolean;
  message: string;
  color?: 'success' | 'error' | 'info' | 'warning' | string; // Allow standard colors or custom strings
}

// --- Define Model (for v-model binding) ---
// This assumes the parent uses v-model="errorSnackbarObject"
const model = defineModel<SnackbarState | undefined>({ default: () => ({ visible: false, message: '', color: 'error'}) });
// If parent uses v-model:snackbar, use:
// const model = defineModel<SnackbarState | undefined>('snackbar', { default: () => ({ visible: false, message: '', color: 'error'}) });

// --- Computed Properties ---
const isVisible = computed({
  get: () => model.value?.visible || false,
  set: (value: boolean) => {
    if (model.value) {
      model.value.visible = value;
    }
  },
});

const snackbarColor = computed(() => {
  // Provide default color and ensure type correctness
  const color = model.value?.color || 'error';
  // Check if it's one of the standard types Vuetify expects for contextual coloring
  if (['success', 'error', 'info', 'warning'].includes(color)) {
       return color as 'success' | 'error' | 'info' | 'warning';
   }
   // Otherwise, assume it's a custom color string
   return color;
});

// --- Methods ---
// No methods needed as v-model handles visibility

</script>

<style scoped>
/* Add specific styles if needed */
</style>