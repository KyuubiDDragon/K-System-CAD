<script setup lang="ts">
/**
 * Ein Abschnitt in der Kontextspalte rechts.
 *
 * Die Ansicht schreibt ihren Inhalt hier hinein, der Rahmen zeigt ihn in der
 * dritten Zone an. Die Trennung ist nötig, weil nur die Ansicht weiß, was
 * gerade ausgewählt ist, aber nur der Rahmen weiß, wo Platz dafür ist.
 *
 * Ist nichts ausgewählt, gibt die Ansicht `v-if="auswahl"` an — dann meldet
 * sich dieser Baustein ab und die Spalte verschwindet mit ihm. Eine leere
 * 210-px-Spalte ist schlimmer als keine.
 */
import { CONTEXT_TARGET_ID, useContextProvider } from '@/composables/useContextAside';

defineProps<{
    /** Überschrift des Abschnitts, klein und gesperrt wie im Entwurf. */
    label?: string;
}>();

useContextProvider();
</script>

<template>
    <Teleport :to="`#${CONTEXT_TARGET_ID}`" defer>
        <section class="k-aside-sec">
            <p v-if="label" class="k-aside-sec__label">{{ label }}</p>
            <slot />
        </section>
    </Teleport>
</template>

<style scoped>
.k-aside-sec {
    padding: 12px;
    border-bottom: 1px solid var(--k-line, #e2e5ea);
}

.k-aside-sec:last-child {
    border-bottom: 0;
}

.k-aside-sec__label {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--k-ink-faint, #7d8794);
    margin: 0 0 8px;
}
</style>
