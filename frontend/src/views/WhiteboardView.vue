<template>
  <div class="whiteboard-view" :aria-label="t('whiteboardView.title')">
    <whiteboard-app :visible="true" />
  </div>
</template>

<script setup lang="ts">
import WhiteboardApp from '@/components/desktop/WhiteboardApp.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<style scoped>
.whiteboard-view {
  height: 100vh;
  width: 100%;
  background-color: #f5f5f5;
}
</style> 