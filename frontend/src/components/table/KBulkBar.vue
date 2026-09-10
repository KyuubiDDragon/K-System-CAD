<script setup lang="ts">
/**
 * Fußzeile einer Tabelle: Auswahl links, Bestand rechts.
 *
 * Der Entwurf zeigt dort „1 ausgewählt" mit den Aktionen daneben, und ganz
 * rechts „Zeilen: 25" und „1–7 von 24". Solange nichts ausgewählt ist, bleibt
 * die linke Seite leer — die Aktionen erscheinen erst, wenn sie etwas zu tun
 * haben. Die Leiste selbst bleibt stehen, damit der Bestand immer ablesbar ist
 * und die Tabelle beim Auswählen nicht springt.
 */
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    /** Wie viele Zeilen ausgewählt sind. */
    count?: number;
    /** Sichtbare Zeilen nach Filterung. */
    shown?: number;
    /** Zeilen insgesamt. */
    total?: number;
}>();

const emit = defineEmits<{ (e: 'clear'): void }>();
</script>

<template>
    <div class="k-tblfoot">
        <template v-if="count">
            <span class="k-tblfoot__count">{{ t('kTable.selected', { n: count }) }}</span>
            <slot name="actions" />
            <v-btn variant="text" size="x-small" class="k-tblfoot__clear" @click="emit('clear')">
                {{ t('kTable.clearSelection') }}
            </v-btn>
        </template>

        <span class="k-tblfoot__spacer"></span>

        <span v-if="typeof shown === 'number'" class="k-tblfoot__range">
            <template v-if="typeof total === 'number' && total !== shown">
                {{ t('kTable.ofTotal', { n: shown, total }) }}
            </template>
            <template v-else>
                {{ t('common.entries', { n: shown }) }}
            </template>
        </span>
    </div>
</template>

<style scoped>
.k-tblfoot {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 8px 12px;
    border-top: 1px solid var(--k-line, #e2e5ea);
    background: var(--k-sunken, #fafbfc);
    font-size: 12px;
    color: var(--k-ink-muted, #5c6675);
    min-height: 36px;
}

.k-tblfoot__spacer {
    flex: 1;
}

.k-tblfoot__count {
    font-weight: 600;
    color: var(--k-ink, #171b22);
    font-variant-numeric: tabular-nums;
}

.k-tblfoot__clear {
    font-size: 11.5px;
    min-width: 0;
    padding-inline: 6px;
}

.k-tblfoot__range {
    font-variant-numeric: tabular-nums;
    color: var(--k-ink-faint, #7d8794);
}

/* Massenaktionen sind klein: sie stehen in einer Fußzeile, nicht im Vordergrund. */
.k-tblfoot :deep(.v-btn--size-default),
.k-tblfoot :deep(.v-btn--size-small) {
    --v-btn-height: 24px;
    font-size: 12px;
}
</style>
