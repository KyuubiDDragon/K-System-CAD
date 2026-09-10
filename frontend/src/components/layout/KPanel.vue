<script setup lang="ts">
/**
 * Fläche mit Kopfzeile.
 *
 * Der Entwurf zeigt Inhalte nicht als nackte Karte, sondern in einem Panel:
 * 34 px hohe Kopfzeile auf gesenkter Fläche, darin der Name und rechts die
 * Aktion. Zwei Gründe dafür stehen im Entwurf:
 *
 *   „Die Aktion wandert in den Kopf der Kachel — dort ist sie auch dann
 *    erreichbar, wenn die Liste voll ist."
 *
 * und für den Leerzustand: der Kopf bleibt stehen, der Inhalt schrumpft auf
 * einen Satz. Ein Leerzustand darf nicht mehr Platz beanspruchen als der
 * gefüllte Zustand — sonst ist der Bildschirm am leersten, wenn am meisten
 * los ist.
 *
 * Kein Schatten: eine Fläche liegt flach auf dem Grund, nur was schwebt
 * (Menü, Dialog, Fenster) wirft einen.
 */
defineProps<{
    /** Name in der Kopfzeile. Fehlt er, entfällt die Kopfzeile. */
    title?: string;
    /** Kein Innenabstand — für Tabellen, die bündig anliegen sollen. */
    flush?: boolean;
}>();
</script>

<template>
    <section class="k-panel">
        <header v-if="title || $slots.head" class="k-panel__head">
            <span class="k-panel__title">{{ title }}</span>
            <span class="k-panel__spacer"></span>
            <slot name="head" />
        </header>
        <div class="k-panel__body" :class="{ 'k-panel__body--flush': flush }">
            <slot />
        </div>
    </section>
</template>

<style scoped>
.k-panel {
    background: var(--k-surface, #fff);
    border: 1px solid var(--k-line, #e2e5ea);
    border-radius: 6px;
    overflow: hidden;
}

.k-panel__head {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 34px;
    padding: 0 12px;
    border-bottom: 1px solid var(--k-line, #e2e5ea);
    background: var(--k-sunken, #fafbfc);
}

.k-panel__title {
    font-size: 12px;
    font-weight: 600;
    color: var(--k-ink, #171b22);
}

.k-panel__spacer {
    flex: 1;
}

/* Aktionen im Kopf bleiben klein - sie ordnen sich der Kopfzeile unter. */
.k-panel__head :deep(.v-btn) {
    --v-btn-height: 22px;
    font-size: 12px;
}

.k-panel__body--flush {
    padding: 0;
}

.k-panel__body:not(.k-panel__body--flush) {
    padding: 12px;
}
</style>
