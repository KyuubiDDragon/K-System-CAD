<script setup lang="ts">
import { computed, ref } from "vue";
import type { Row } from "./api";
const props = defineProps<{ modelValue?: Row }>();
const emit = defineEmits(["update:modelValue"]);
const mode = ref("light");
const defaults: Row = {
  light: {
    canvas: "#f3f5f6",
    surface: "#ffffff",
    subtle: "#edf1f3",
    text: "#1f2933",
    muted: "#616e79",
    line: "#e0e5e9",
    soft: "#e2f1ef",
    nav: "#ffffff",
    nav_text: "#1f2933",
  },
  dark: {
    canvas: "#14171b",
    surface: "#1b2026",
    subtle: "#252c34",
    text: "#eaf0f3",
    muted: "#a3b0ba",
    line: "#303943",
    soft: "#173d3d",
    nav: "#1b2026",
    nav_text: "#eaf0f3",
  },
};
const fields: Row = {
  canvas: "Seitenhintergrund",
  surface: "Karten und Dialoge",
  subtle: "Hoverflächen",
  text: "Haupttext",
  muted: "Sekundärer Text",
  line: "Rahmen und Trennlinien",
  soft: "Markierungen",
  nav: "Navigationsleiste",
  nav_text: "Navigationstext",
};
const colors = computed(() => ({
  ...defaults[mode.value],
  ...props.modelValue?.[mode.value],
}));
function setColor(key: string, value: string) {
  emit("update:modelValue", {
    ...props.modelValue,
    [mode.value]: { ...props.modelValue?.[mode.value], [key]: value },
  });
}
function reset() {
  emit("update:modelValue", { ...props.modelValue, [mode.value]: {} });
}
</script>
<template>
  <section class="theme-color-editor">
    <h3>Farben der Oberfläche</h3>
    <p class="small muted">
      Farben gelten für die gesamte Community. Änderungen werden erst beim
      Speichern übernommen.
    </p>
    <div class="row">
      <button
        type="button"
        :aria-pressed="mode === 'light'"
        @click="mode = 'light'"
      >
        Hellmodus anpassen</button
      ><button
        type="button"
        :aria-pressed="mode === 'dark'"
        @click="mode = 'dark'"
      >
        Dunkelmodus anpassen
      </button>
    </div>
    <div class="form-grid">
      <label v-for="(label, key) in fields" :key="key"
        >{{ label
        }}<input
          type="color"
          :value="colors[key]"
          @input="
            setColor(String(key), ($event.target as HTMLInputElement).value)
          "
      /></label>
    </div>
    <div
      class="palette-preview"
      :style="{
        background: colors.canvas,
        color: colors.text,
        borderColor: colors.line,
      }"
    >
      <div
        class="palette-navigation"
        :style="{
          background: colors.nav,
          color: colors.nav_text,
          borderColor: colors.line,
        }"
      >
        Deine Plattform <span>Suche · Profil</span>
      </div>
      <div
        class="palette-card"
        :style="{ background: colors.surface, borderColor: colors.line }"
      >
        <strong>So sieht ein Beitrag aus</strong>
        <p :style="{ color: colors.muted }">Profilname · gerade eben</p>
        <p>Ein kurzer Beispieltext für deine neuen Farben.</p>
        <span :style="{ background: colors.soft, color: colors.text }"
          >Kategorie</span
        >
      </div>
    </div>
    <button type="button" class="quiet" @click="reset">
      {{ mode === "light" ? "Hellmodus" : "Dunkelmodus" }} auf Standardfarben
      zurücksetzen
    </button>
  </section>
</template>
<style scoped>
.theme-color-editor {
  margin: 24px 0;
  padding-top: 20px;
  border-top: 1px solid var(--line);
}
.theme-color-editor button[aria-pressed="true"] {
  box-shadow: 0 0 0 2px var(--brand);
}
.palette-preview {
  border: 1px solid;
  border-radius: 12px;
  overflow: hidden;
  margin: 14px 0;
}
.palette-navigation {
  display: flex;
  justify-content: space-between;
  padding: 14px;
  border-bottom: 1px solid;
}
.palette-navigation span {
  font-size: 12px;
}
.palette-card {
  border: 1px solid;
  border-radius: 10px;
  padding: 18px;
  margin: 20px;
}
.palette-card span {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}
</style>
