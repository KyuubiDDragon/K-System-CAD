<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
const props = defineProps<{ categories: string[]; modelValue: string }>();
const emit = defineEmits<{
  "update:modelValue": [value: string];
  change: [];
}>();
const root = ref<HTMLElement>();
const measure = ref<HTMLElement>();
const more = ref<HTMLButtonElement>();
const count = ref(0);
const expanded = ref(false);
const visible = computed(() => props.categories.slice(0, count.value));
const hidden = computed(() => props.categories.slice(count.value));
const hiddenSelected = computed(() => hidden.value.includes(props.modelValue));
let observer: ResizeObserver | undefined;
function fit() {
  if (!root.value || !measure.value) return;
  const widths = Array.from(measure.value.querySelectorAll("button")).map(
    (el) => el.getBoundingClientRect().width,
  );
  const width = root.value.clientWidth;
  const gap = 8;
  if (widths.length < 2) return;
  const allWidth = widths[0];
  const categoryWidths = widths.slice(1, -1);
  const total = allWidth + categoryWidths.reduce((sum, w) => sum + w + gap, 0);
  if (total <= width) {
    count.value = props.categories.length;
    expanded.value = false;
    return;
  }
  let used = allWidth + widths[widths.length - 1] + gap;
  let fits = 0;
  for (const w of categoryWidths) {
    if (used + w + gap > width) break;
    used += w + gap;
    fits++;
  }
  count.value = fits;
}
function choose(value: string) {
  const fromPopup = expanded.value;
  expanded.value = false;
  emit("update:modelValue", value);
  emit("change");
  if (fromPopup) more.value?.focus();
}
function outside(e: PointerEvent) {
  if (!root.value?.contains(e.target as Node)) expanded.value = false;
}
function escape(e: KeyboardEvent) {
  if (e.key === "Escape" && expanded.value) {
    expanded.value = false;
    more.value?.focus();
    e.stopPropagation();
  }
}
watch(
  () => props.categories,
  async () => {
    await nextTick();
    fit();
  },
  { deep: true },
);
onMounted(() => {
  observer = new ResizeObserver(fit);
  if (root.value) observer.observe(root.value);
  fit();
  document.fonts.ready.then(fit);
  document.addEventListener("pointerdown", outside);
});
onUnmounted(() => {
  observer?.disconnect();
  document.removeEventListener("pointerdown", outside);
});
</script>
<template>
  <div ref="root" class="category-filter compact-categories" @keydown="escape">
    <div
      class="category-row"
      role="group"
      aria-label="Beiträge nach Kategorie filtern"
    >
      <button
        class="all-categories"
        :aria-pressed="!modelValue"
        @click="choose('')"
      >
        Alle Kategorien
      </button>
      <button
        v-for="c in visible"
        :key="c"
        :aria-pressed="modelValue === c"
        @click="choose(c)"
      >
        {{ c }}
      </button>
      <button
        v-if="hidden.length"
        ref="more"
        class="category-more"
        :aria-expanded="expanded"
        :aria-pressed="hiddenSelected"
        :aria-label="
          hiddenSelected
            ? `Weitere Kategorien – ausgewählt: ${modelValue}`
            : 'Weitere Kategorien'
        "
        :title="hiddenSelected ? modelValue : 'Weitere Kategorien'"
        @click="expanded = !expanded"
      >
        …
      </button>
    </div>
    <div
      v-if="expanded && hidden.length"
      class="category-overflow"
      role="group"
      aria-label="Weitere Kategorien"
    >
      <button
        v-for="c in hidden"
        :key="c"
        :aria-pressed="modelValue === c"
        @click="choose(c)"
      >
        {{ c }}
      </button>
    </div>
    <div class="category-measure-clip" aria-hidden="true" inert>
      <div ref="measure" class="category-measure">
        <button tabindex="-1">Alle Kategorien</button
        ><button v-for="c in categories" :key="c" tabindex="-1">{{ c }}</button
        ><button class="category-more" tabindex="-1">…</button>
      </div>
    </div>
  </div>
</template>
<style scoped>
.compact-categories {
  display: block;
  position: relative;
  min-width: 0;
}
.category-row {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
}
.category-row > button {
  flex-shrink: 0;
  white-space: nowrap;
}
.category-row > .all-categories {
  flex-shrink: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}
.category-more {
  width: 38px;
  min-width: 38px;
  padding-inline: 0;
}
.category-measure-clip {
  position: absolute;
  inset: 0;
  overflow: hidden;
  visibility: hidden;
  pointer-events: none;
}
.category-measure {
  position: absolute;
  left: 0;
  top: 0;
  display: flex;
  gap: 8px;
  width: max-content;
  visibility: hidden;
  pointer-events: none;
}
.category-measure button {
  flex-shrink: 0;
  white-space: nowrap;
}
.category-overflow {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 20;
  width: 240px;
  max-width: 100%;
  max-height: 300px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--surface);
  box-shadow: 0 8px 28px #0002;
}
.category-overflow button {
  text-align: left;
  white-space: normal;
  overflow-wrap: anywhere;
}
</style>
