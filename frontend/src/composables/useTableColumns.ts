/**
 * Spaltenauswahl für Tabellenansichten.
 *
 * Der Entwurf verlangt: „Spalten sollten sich zuschalten lassen, aber nicht ab
 * Werk sichtbar sein." Genau dafür gibt es hier zwei Angaben je Spalte:
 *
 *   locked   — steht immer da und taucht im Menü nicht auf (Name, Aktionen)
 *   optional — steht ab Werk *nicht* da, lässt sich aber zuschalten
 *              (Bankverbindung, Geburtsdatum, Personalausweis …)
 *
 * Die Auswahl liegt je Ansicht im localStorage, damit sie den Seitenwechsel
 * überlebt. Fehlt ein Eintrag — etwa weil die Ansicht eine neue Spalte bekommen
 * hat —, entscheidet die Voreinstellung der Spalte.
 */
import { computed, ref, watch, type Ref } from 'vue';

export interface KColumn {
    /** Schlüssel der Spalte, wie ihn Vuetify erwartet. */
    key?: string;
    title?: string;
    align?: string;
    /** Immer sichtbar, nicht abwählbar. */
    locked?: boolean;
    /** Ab Werk ausgeblendet, aber zuschaltbar. */
    optional?: boolean;
    [k: string]: unknown;
}

const PREFIX = 'k-columns:';

function readStored(storageKey: string): Record<string, boolean> {
    if (!storageKey) return {};
    try {
        const raw = localStorage.getItem(PREFIX + storageKey);
        if (!raw) return {};
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? (parsed as Record<string, boolean>) : {};
    } catch {
        return {};
    }
}

function writeStored(storageKey: string, value: Record<string, boolean>): void {
    if (!storageKey) return;
    try {
        localStorage.setItem(PREFIX + storageKey, JSON.stringify(value));
    } catch {
        /* Speicher voll oder gesperrt — die Auswahl gilt dann nur für diese Sitzung. */
    }
}

/** Spalten, die Vuetify selbst einzieht und die nie im Menü stehen dürfen. */
function isStructural(col: KColumn): boolean {
    return (
        col.key === 'actions' || col.key === 'data-table-select' || col.key === 'data-table-expand'
    );
}

export function useTableColumns(storageKey: string, source: Ref<KColumn[]> | (() => KColumn[])) {
    const all = computed<KColumn[]>(() => (typeof source === 'function' ? source() : source.value));
    const choice = ref<Record<string, boolean>>(readStored(storageKey));

    watch(choice, value => writeStored(storageKey, value), { deep: true });

    function isVisible(col: KColumn): boolean {
        if (col.locked || isStructural(col)) return true;
        const key = String(col.key ?? '');
        if (key in choice.value) return choice.value[key];
        return !col.optional;
    }

    /** Nur die Spalten, die im Menü zur Wahl stehen. */
    const selectable = computed<KColumn[]>(() =>
        all.value.filter(col => !col.locked && !isStructural(col) && col.key)
    );

    const visible = computed<KColumn[]>(() => all.value.filter(isVisible));

    const hiddenCount = computed(() => selectable.value.filter(col => !isVisible(col)).length);

    function toggle(col: KColumn, next?: boolean): void {
        const key = String(col.key ?? '');
        if (!key) return;
        choice.value = { ...choice.value, [key]: next ?? !isVisible(col) };
    }

    function reset(): void {
        choice.value = {};
    }

    return { visible, selectable, hiddenCount, isVisible, toggle, reset };
}
