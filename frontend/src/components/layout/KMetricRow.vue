<script setup lang="ts">
/**
 * Kennzahlenreihe über einer Liste.
 *
 * Der Entwurf zeigt sie nicht nur auf dem Dashboard, sondern über dem
 * Arbeitsbereich: „Einheiten belegt 3/8", „Mitarbeiter 24", „Fahrzeuge 10".
 * Der Sinn ist, dass man den Bestand sieht, bevor man in die Liste schaut —
 * die Liste beantwortet dann das „welche", nicht mehr das „wie viele".
 *
 * Die Zahl trägt die Aussage, nicht die Beschriftung. Deshalb steht die
 * Beschriftung klein und gedimmt darüber und der Zusatz klein darunter.
 *
 * Die Kacheln stehen auf einem 1-px-Raster aus Linienfarbe: das trennt sie
 * ohne Rahmen und ohne Schatten. Schatten wirft im System nur, was schwebt.
 */
export interface KMetric {
    /** Beschriftung über der Zahl. */
    cap: string;
    /** Die Zahl selbst. */
    val: string | number;
    /** Bezugsgröße, klein hinter der Zahl: „3 / 8". */
    unit?: string;
    /** Zusatz unter der Zahl: „4 mit Austrittsdatum". */
    sub?: string;
}

defineProps<{
    metrics: KMetric[];
}>();
</script>

<template>
    <div v-if="metrics.length" class="k-metrics">
        <div v-for="metric in metrics" :key="metric.cap" class="k-metric">
            <div class="k-metric__cap">{{ metric.cap }}</div>
            <div class="k-metric__val">
                {{ metric.val
                }}<span v-if="metric.unit" class="k-metric__unit"> {{ metric.unit }}</span>
            </div>
            <div v-if="metric.sub" class="k-metric__sub">{{ metric.sub }}</div>
        </div>
    </div>
</template>

<style scoped>
.k-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1px;
    background: var(--k-line, #e2e5ea);
    border: 1px solid var(--k-line, #e2e5ea);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 14px;
}
</style>
