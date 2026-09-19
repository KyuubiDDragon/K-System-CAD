<script setup lang="ts">
import LawsView from './LawsView.vue';
defineProps<{windowId?:string}>();
</script>
<template><LawsView editorial :window-id="windowId"/></template>
