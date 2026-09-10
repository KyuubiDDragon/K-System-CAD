/**
 * Die Kontextspalte rechts.
 *
 * Der Entwurf beschreibt den Arbeitsbereich als drei Zonen: „links wo man ist,
 * in der Mitte die Arbeit, rechts der Zusammenhang zur ausgewählten Einheit."
 * Die dritte Zone fehlte bisher.
 *
 * Sie gehört zum Rahmen (SidebarLayout), ihr Inhalt aber zur Ansicht — die
 * weiß als Einzige, was gerade ausgewählt ist. Deshalb hängt der Rahmen nur
 * ein Ziel auf, und die Ansicht schiebt ihren Inhalt per `<Teleport>` hinein.
 *
 * Zwei Zustände muss der Rahmen dafür kennen:
 *
 *   hasContent — ob überhaupt eine Ansicht etwas beigesteuert hat. Ohne
 *                Inhalt darf keine leere 210-px-Spalte stehen bleiben.
 *   collapsed  — ob der Nutzer sie zugeklappt hat. Das merkt sich das System,
 *                sonst müsste man es auf jeder Seite neu tun.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

export const CONTEXT_TARGET_ID = 'k-context-target';

const STORAGE_KEY = 'k-context-collapsed';

/** Wie viele Ansichten gerade Inhalt beisteuern. Fast immer 0 oder 1. */
const providers = ref(0);

const collapsed = ref<boolean>(readCollapsed());

function readCollapsed(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function writeCollapsed(value: boolean): void {
    try {
        localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
    } catch {
        /* Speicher gesperrt — die Wahl gilt dann nur für diese Sitzung. */
    }
}

/** Für den Rahmen: Sichtbarkeit und Umschalter. */
export function useContextAside() {
    const hasContent = computed(() => providers.value > 0);
    const isOpen = computed(() => hasContent.value && !collapsed.value);

    function toggle(): void {
        collapsed.value = !collapsed.value;
        writeCollapsed(collapsed.value);
    }

    return { hasContent, isOpen, collapsed, toggle };
}

/**
 * Für die Ansicht: meldet an, dass es Inhalt gibt, und wieder ab, sobald die
 * Ansicht verlassen wird. Ohne das Abmelden bliebe die Spalte auf der
 * nächsten Seite als leerer Streifen stehen.
 */
export function useContextProvider() {
    onMounted(() => {
        providers.value += 1;
    });
    onBeforeUnmount(() => {
        providers.value = Math.max(0, providers.value - 1);
    });
}
