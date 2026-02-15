<template>
  <v-container>
    <v-card>
      <v-card-title>{{ t('desktop.testTitle') }}</v-card-title>
      <v-card-text>
        <div>{{ t('desktop.currentMode') }}: {{ isDesktopMode ? t('desktop.enabled') : t('desktop.disabled') }}</div>
        <div>{{ t('desktop.desktopWindow') }}: {{ isDesktopWindow ? t('desktop.yes') : t('desktop.no') }}</div>
        <div class="mt-4">
          <v-btn color="primary" @click="toggleDesktopMode">
            {{ t('desktop.toggleMode') }}
          </v-btn>
          <v-btn color="info" class="ml-2" @click="logStoreState">
            {{ t('desktop.logStoreState') }}
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUIStore } from '@/stores/ui';
import { useI18n } from 'vue-i18n';

const uiStore = useUIStore();
const { t } = useI18n();

// Computed property for desktop mode state
const isDesktopMode = computed(() => uiStore.$state.isDesktopMode);
const isDesktopWindow = computed(() => {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.has('desktop');
});

// Method to toggle desktop mode
function toggleDesktopMode() {
  console.log('Toggling desktop mode', 'Current state:', uiStore.$state.isDesktopMode);
  uiStore.toggleDesktopMode();
  console.log('New state:', uiStore.$state.isDesktopMode);
}

// Log the current state of the UI store
function logStoreState() {
  console.log('UI Store State:', JSON.stringify(uiStore.$state, null, 2));
}

// Initialize when component mounts
onMounted(() => {
  // Make sure the isDesktopMode property exists in the store
  if (uiStore.$state.isDesktopMode === undefined) {
    uiStore.$state.isDesktopMode = false;
  }
  
  console.log('TestDesktopMode mounted, desktop mode is:', uiStore.$state.isDesktopMode);
  console.log('Full UI store state:', uiStore.$state);
});
</script>

<style scoped>
.mt-4 {
  margin-top: 16px;
}
.ml-2 {
  margin-left: 8px;
}
</style> 