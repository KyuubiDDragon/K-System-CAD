<template>
  <v-container>
    <v-card class="mb-4">
      <v-card-title>{{ t('desktop.debugTitle') }}</v-card-title>
      <v-card-text>
        <div>{{ t('desktop.currentMode') }}: {{ isDesktopMode ? t('desktop.enabled') : t('desktop.disabled') }}</div>
        <div>{{ t('desktop.queryParams') }}:</div>
        <pre>{{ JSON.stringify(queryParams, null, 2) }}</pre>
        <div>{{ t('desktop.cssClasses') }}:</div>
        <div>- desktop-mode: {{ hasClass('desktop-mode') }}</div>
        <div>- hide-left-nav: {{ hasClass('hide-left-nav') }}</div>
        <div>- hide-top-nav: {{ hasClass('hide-top-nav') }}</div>
        <div class="mt-4">
          <v-btn color="primary" @click="toggleDesktopMode">{{ t('desktop.toggleMode') }}</v-btn>
          <v-btn color="info" class="ml-2" @click="refreshPage">{{ t('desktop.refreshPage') }}</v-btn>
          <v-btn color="success" class="ml-2" @click="openWithDesktopParams">{{ t('desktop.openWithParams') }}</v-btn>
        </div>
      </v-card-text>
    </v-card>
    
    <v-card>
      <v-card-title>{{ t('desktop.uiState') }}</v-card-title>
      <v-card-text>
        <pre>{{ JSON.stringify(uiStore.$state, null, 2) }}</pre>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUIStore } from '@/stores/ui';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const uiStore = useUIStore();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

// Computed properties
const isDesktopMode = computed(() => uiStore.$state.isDesktopMode);
const queryParams = computed(() => route.query);

// Check if a class exists on the HTML element
function hasClass(className: string): boolean {
  return document.documentElement.classList.contains(className);
}

// Method to toggle desktop mode
function toggleDesktopMode() {
  console.log('Toggling desktop mode', 'Current state:', uiStore.$state.isDesktopMode);
  // Use the store action if available, otherwise fallback to direct state mutation
  if (typeof uiStore.toggleDesktopMode === 'function') {
    uiStore.toggleDesktopMode();
  } else {
    uiStore.$state.isDesktopMode = !uiStore.$state.isDesktopMode;
  }
  console.log('New state:', uiStore.$state.isDesktopMode);
  
  // Apply or remove desktop mode class
  if (uiStore.$state.isDesktopMode) {
    document.documentElement.classList.add('desktop-mode');
  } else {
    document.documentElement.classList.remove('desktop-mode');
  }
}

// Method to refresh the page
function refreshPage() {
  window.location.reload();
}

// Method to open the page with desktop parameters
function openWithDesktopParams() {
  const currentUrl = new URL(window.location.href);
  currentUrl.searchParams.set('desktop', 'true');
  currentUrl.searchParams.set('hideLeftNav', 'false');
  currentUrl.searchParams.set('hideTopNav', 'false');
  window.location.href = currentUrl.toString();
}

// Initialize when component mounts
onMounted(() => {
  console.log('DesktopDebug mounted, desktop mode is:', uiStore.$state.isDesktopMode);
  console.log('Current query parameters:', route.query);
  console.log('Classes on HTML element:', document.documentElement.className);
});
</script>

<style scoped>
.mt-4 {
  margin-top: 16px;
}
.ml-2 {
  margin-left: 8px;
}
pre {
  background-color: var(--k-sunken);
  padding: 8px;
  border-radius: 4px;
  overflow-x: auto;
}
</style> 