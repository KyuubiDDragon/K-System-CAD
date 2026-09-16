<script setup lang="ts">
import { ref } from 'vue';
const loaded = ref(false);
const configured = import.meta.env.VITE_SOCIAL_ENABLED === 'true' ? import.meta.env.VITE_SOCIAL_URL : '';
const source = (() => {
  try { const url = new URL(configured); return ['https:', 'http:'].includes(url.protocol) ? url.href : ''; }
  catch { return ''; }
})();
</script>
<template>
 <section class="social-desktop-app">
  <p v-if="!source" class="social-status">Die Social-Plattform ist für diese Installation nicht eingerichtet.</p>
  <template v-else>
   <p v-if="!loaded" class="social-status" role="status">Social wird geladen …</p>
   <iframe :src="source" title="Social-Plattform" allow="fullscreen" referrerpolicy="strict-origin-when-cross-origin" @load="loaded = true" />
  </template>
 </section>
</template>
<style scoped>
.social-desktop-app{position:relative;display:flex;flex:1;min-height:0;height:100%;width:100%;overflow:hidden;background:rgb(var(--v-theme-background))}.social-desktop-app iframe{display:block;border:0;width:100%;height:100%;flex:1;min-height:0}.social-status{position:absolute;top:20px;left:24px;right:24px;margin:0;padding:16px;background:rgb(var(--v-theme-surface));border-radius:10px}
</style>
