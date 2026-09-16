<script setup lang="ts">
import LawsApp from '@/components/laws/LawsApp.vue';
const endpoint = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '') + '/laws/index.php';
</script>
<template><LawsApp :endpoint="endpoint"/></template>
