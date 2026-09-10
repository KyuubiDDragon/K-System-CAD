<template>
  <div>
    <h1>{{ t('desktop.testTitle') }}</h1>
    <button @click="toggleDesktopMode">{{ t('desktop.toggleMode') }}</button>
    <p>{{ t('desktop.currentMode') }}: {{ isDesktopMode ? t('desktop.enabled') : t('desktop.disabled') }}</p>
    
    <div v-if="isDesktopMode" class="desktop-container">
      <h2>{{ t('desktop.debugTitle') }}</h2>
      <p>{{ t('desktop.noContent') }}</p>
    </div>
    <div v-else class="normal-container">
      <h2>{{ t('desktop.testTitle') }}</h2>
      <p>{{ t('desktop.disabled') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

// Simple local state for testing, independent of store issues
const desktopModeEnabled = ref(false);
const isDesktopMode = computed(() => desktopModeEnabled.value);
const { t } = useI18n();

function toggleDesktopMode() {
  console.log("Toggling desktop mode", desktopModeEnabled.value);
  desktopModeEnabled.value = !desktopModeEnabled.value;
  console.log("New desktop mode state:", desktopModeEnabled.value);
}
</script>

<style scoped>
.desktop-container {
  background-color: var(--k-sunken);
  color: var(--k-ink);
  padding: 20px;
  border-radius: 8px;
  margin-top: 20px;
}

.normal-container {
  background-color: var(--k-ink);
  color: #334155;
  padding: 20px;
  border-radius: 8px;
  margin-top: 20px;
}

button {
  background-color: var(--k-accent);
  color: var(--k-ink);
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

button:hover {
  background-color: var(--k-accent-hover);
}
</style> 