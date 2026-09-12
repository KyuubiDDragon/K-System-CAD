/**
 * Filter über Tabellenansichten.
 *
 * Bisher trug die Filterleiste nur einen Zähler. Der Entwurf zeigt dort Chips,
 * die tatsächlich filtern — im Beispiel „Aktiv", „+ Rang", „+ Einheit",
 * „+ Abwesend". Zwei Arten stecken darin:
 *
 *   Schalter (preset) — ein Ja/Nein-Filter mit fester Bedingung. „Aktiv" ist
 *                       ab Werk gesetzt, weil ausgeschiedene Mitarbeiter in
 *                       der täglichen Liste nur stören.
 *   Facette (facet)   — filtert auf ein Feld, die Werte kommen aus den Daten.
 *                       Das Menü zeigt jeden vorkommenden Wert mit seiner
 *                       Anzahl; nichts gewählt heißt „alle".
 *
 * Facetten sind bewusst datengetrieben: Ränge, Einheiten und Status stehen
 * nirgends als gepflegte Liste, sondern ergeben sich aus dem Bestand. Eine
 * fest verdrahtete Auswahl wäre am Tag ihrer Einführung veraltet.
 */
import { computed, ref, type Ref } from 'vue';

export interface KPreset {
    key: string;
    label: string;
    /** Trifft zu = Zeile bleibt sichtbar, solange der Schalter an ist. */
    test: (item: any) => boolean;
    /** Ab Werk gesetzt. */
    on?: boolean;
}

export interface KFacet {
    /** Feld im Datensatz — auch verschachtelt: 'rank.name'. */
    field: string;
    label: string;
    /** Anzeigename eines Werts; ohne Angabe der Wert selbst. */
    format?: (value: any, item: any) => string;
    /** Beschriftung für Zeilen ohne Wert. Ohne Angabe werden sie nicht angeboten. */
    emptyLabel?: string;
}

export interface FacetOption {
    value: string;
    label: string;
    count: number;
}

/** Innerer Schlüssel für „Feld ist leer" — führendes Leerzeichen, damit er mit
    keinem echten Wert kollidiert. */
const EMPTY = ' leer';

function readPath(item: any, path: string): any {
    if (!item) return undefined;
    if (!path.includes('.')) return item[path];
    return path.split('.').reduce((acc, part) => (acc == null ? acc : acc[part]), item);
}

function isBlank(value: any): boolean {
    return value === null || value === undefined || value === '' || value === '-';
}

export function useTableFilters(
    items: Ref<any[]> | (() => any[]),
    presets: KPreset[] = [],
    facets: KFacet[] = []
) {
    const source = computed<any[]>(() => {
        const raw = typeof items === 'function' ? items() : items.value;
        return Array.isArray(raw) ? raw : [];
    });

    /** Gesetzte Schalter. */
    const activePresets = ref<Set<string>>(new Set(presets.filter(p => p.on).map(p => p.key)));
    /** Je Facette die gewählten Werte. Leer = alle. */
    const activeFacets = ref<Record<string, string[]>>({});

    function matchesPresets(item: any): boolean {
        return presets.every(p => !activePresets.value.has(p.key) || p.test(item));
    }

    function matchesFacetsExcept(item: any, skipField: string | null): boolean {
        return facets.every(f => {
            if (f.field === skipField) return true;
            const chosen = activeFacets.value[f.field];
            if (!chosen || chosen.length === 0) return true;
            const raw = readPath(item, f.field);
            const key = isBlank(raw) ? EMPTY : String(raw);
            return chosen.includes(key);
        });
    }

    const filtered = computed(() =>
        source.value.filter(item => matchesPresets(item) && matchesFacetsExcept(item, null))
    );

    /**
     * Die Werte einer Facette samt Anzahl. Gezählt wird auf dem Bestand, der
     * nach allen *anderen* Filtern übrig bleibt — sonst zeigt das Menü Werte
     * mit „0", sobald man einen davon gewählt hat.
     */
    function optionsFor(facet: KFacet): FacetOption[] {
        const pool = source.value.filter(
            item => matchesPresets(item) && matchesFacetsExcept(item, facet.field)
        );
        const counts = new Map<string, { label: string; count: number }>();
        for (const item of pool) {
            const raw = readPath(item, facet.field);
            const blank = isBlank(raw);
            if (blank && !facet.emptyLabel) continue;
            const key = blank ? EMPTY : String(raw);
            const label = blank
                ? (facet.emptyLabel as string)
                : facet.format
                  ? facet.format(raw, item)
                  : String(raw);
            const seen = counts.get(key);
            if (seen) seen.count += 1;
            else counts.set(key, { label, count: 1 });
        }
        return [...counts.entries()]
            .map(([value, meta]) => ({ value, label: meta.label, count: meta.count }))
            .sort((a, b) => a.label.localeCompare(b.label, 'de'));
    }

    function isPresetOn(key: string): boolean {
        return activePresets.value.has(key);
    }

    function togglePreset(key: string): void {
        const next = new Set(activePresets.value);
        if (next.has(key)) next.delete(key);
        else next.add(key);
        activePresets.value = next;
    }

    function facetValues(field: string): string[] {
        return activeFacets.value[field] ?? [];
    }

    function toggleFacetValue(field: string, value: string): void {
        const current = facetValues(field);
        const next = current.includes(value)
            ? current.filter(v => v !== value)
            : [...current, value];
        activeFacets.value = { ...activeFacets.value, [field]: next };
    }

    function clearFacet(field: string): void {
        activeFacets.value = { ...activeFacets.value, [field]: [] };
    }

    function clearAll(): void {
        activePresets.value = new Set();
        activeFacets.value = {};
    }

    /** Wie viele Filter gerade greifen — für „Alle zurücksetzen". */
    const activeCount = computed(
        () =>
            activePresets.value.size +
            Object.values(activeFacets.value).filter(v => v.length > 0).length
    );

    /** Steht die Liste vollständig da, oder ist sie beschnitten? */
    const isFiltered = computed(() => filtered.value.length !== source.value.length);

    return {
        filtered,
        total: computed(() => source.value.length),
        presets,
        facets,
        optionsFor,
        isPresetOn,
        togglePreset,
        facetValues,
        toggleFacetValue,
        clearFacet,
        clearAll,
        activeCount,
        isFiltered,
    };
}
