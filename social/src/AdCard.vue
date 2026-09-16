<script setup lang="ts">
import { computed } from "vue";
import { mediaUrl, type Row } from "./api";
const props = defineProps<{ ad: Row; now: number }>();
const countdown = computed(() => {
  if (!props.ad.countdown_at) return "";
  const s = Math.max(
    0,
    Math.floor(
      (new Date(props.ad.countdown_at.replace(" ", "T") + "Z").getTime() -
        props.now) /
        1000,
    ),
  );
  return s
    ? `${Math.floor(s / 86400)} T ${Math.floor((s % 86400) / 3600)} Std ${Math.floor((s % 3600) / 60)} Min`
    : "Aktion gestartet";
});
</script>
<template>
  <section class="ad-card" :class="'placement-' + ad.placement">
    <small class="eyebrow">Werbung</small
    ><img
      v-for="m in ad.media"
      :key="m.id"
      :src="mediaUrl(m.id)"
      alt="Werbung"
    />
    <div>
      <h3>{{ ad.title }}</h3>
      <p>{{ ad.body }}</p>
      <strong v-if="countdown" class="countdown">{{ countdown }}</strong>
    </div>
    <a
      v-if="ad.target"
      :href="ad.target"
      class="button"
      :target="ad.target.startsWith('http') ? '_blank' : undefined"
      rel="noopener noreferrer"
      >Mehr erfahren ↗</a
    >
  </section>
</template>
