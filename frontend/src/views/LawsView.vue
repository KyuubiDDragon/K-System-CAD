<script setup lang="ts">
import LawsApp from '@/components/laws/LawsApp.vue';
const endpoint = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '') + '/laws/index.php';
</script>
<template><div class="laws-cad-window"><div class="laws-cad-content"><LawsApp :endpoint="endpoint"/></div></div></template>
<style scoped>
.laws-cad-window{container: laws-window / inline-size;box-sizing:border-box;width:100%;min-width:0;--surface:rgb(var(--v-theme-surface));--text:rgb(var(--v-theme-on-surface));--line:rgba(var(--v-theme-on-surface),.15);--brand:rgb(var(--v-theme-primary))}
.laws-cad-content{padding:20px;box-sizing:border-box;min-width:0}
.laws-cad-window :deep(.laws-app){box-sizing:border-box;width:100%;margin:0 auto;min-height:0;padding:24px}
@container laws-window (max-width:850px){.laws-cad-window :deep(.law-layout){grid-template-columns:1fr;gap:16px}.laws-cad-window :deep(.law-toc){position:static;max-height:280px}.laws-cad-window :deep(.laws-app){padding:16px}.laws-cad-window :deep(.law-article){padding:16px}.laws-cad-window :deep(.law-context){max-width:100%;overflow-wrap:anywhere}}
</style>
