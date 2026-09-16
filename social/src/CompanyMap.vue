<script setup lang="ts">
import { ref } from "vue";
const props = defineProps<{
  x?: number | string | null;
  y?: number | string | null;
  editable?: boolean;
}>();
const emit = defineEmits(["point"]);
const zoom = ref(1);
function pick(e: MouseEvent) {
  if (!props.editable) return;
  const r = (e.currentTarget as HTMLElement).getBoundingClientRect();
  emit("point", {
    map_x: Math.round(((e.clientX - r.left) / r.width) * 10000) / 100,
    map_y: Math.round(((e.clientY - r.top) / r.height) * 10000) / 100,
  });
}
</script>
<template>
  <div class="company-map">
    <div class="row between">
      <strong>Standort auf der GTA-Karte</strong>
      <div class="row">
        <button
          type="button"
          aria-label="Karte verkleinern"
          :disabled="zoom === 1"
          @click="zoom--"
        >
          −</button
        ><button
          type="button"
          aria-label="Karte vergrößern"
          :disabled="zoom === 4"
          @click="zoom++"
        >
          +
        </button>
      </div>
    </div>
    <p v-if="editable" class="small muted">
      Karte anklicken, um den Unternehmensstandort zu setzen.
    </p>
    <div class="map-scroll">
      <div class="map-viewport">
        <div
          class="map-surface"
          :style="{ width: zoom * 100 + '%' }"
          @click="pick"
        >
          <template v-for="y in 4" :key="y"
            ><img
              v-for="x in 4"
              :key="x"
              :src="`/gta-map/${x - 1}/${y - 1}.jpg`"
              alt=""
              draggable="false" /></template
          ><span
            v-if="props.x != null && props.y != null"
            class="map-pin"
            :style="{ left: props.x + '%', top: props.y + '%' }"
            role="img"
            aria-label="Unternehmensstandort"
            >●</span
          >
        </div>
      </div>
    </div>
    <div v-if="editable" class="row">
      <button
        type="button"
        @click="emit('point', { map_x: null, map_y: null })"
      >
        Punkt entfernen</button
      ><label
        >X (%)<input
          type="number"
          min="0"
          max="100"
          step="0.01"
          :value="x"
          @input="
            emit('point', {
              map_x: Number(($event.target as HTMLInputElement).value),
              map_y: y ?? 50,
            })
          " /></label
      ><label
        >Y (%)<input
          type="number"
          min="0"
          max="100"
          step="0.01"
          :value="y"
          @input="
            emit('point', {
              map_x: x ?? 50,
              map_y: Number(($event.target as HTMLInputElement).value),
            })
          "
      /></label>
    </div>
  </div>
</template>
