<script setup lang="ts">
/**
 * Filterleiste über einer Tabelle.
 *
 * Der Entwurf zeigt sie so: links die Filter als Chips, rechts die Anzahl,
 * daneben „Spalten" und die Hauptaktion. Ein noch nicht gesetzter Filter ist
 * gestrichelt und trägt ein „+" — er lädt zum Zuschalten ein, ohne wie ein
 * aktiver Zustand auszusehen.
 *
 * Die Leiste rendert nur; gefiltert wird in `useTableFilters`, die Spalten
 * verwaltet `useTableColumns`. Beides gibt die Ansicht herein, damit sie die
 * gefilterte Liste auch selbst benutzen kann (Ausgabe, Zähler, Kennzahlen).
 */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { KColumn } from '@/composables/useTableColumns';
import type { FacetOption, KFacet, KPreset } from '@/composables/useTableFilters';

const { t } = useI18n();

interface FilterApi {
    presets: KPreset[];
    facets: KFacet[];
    optionsFor: (facet: KFacet) => FacetOption[];
    isPresetOn: (key: string) => boolean;
    togglePreset: (key: string) => void;
    facetValues: (field: string) => string[];
    toggleFacetValue: (field: string, value: string) => void;
    clearFacet: (field: string) => void;
    clearAll: () => void;
    activeCount: { value: number };
}

interface ColumnApi {
    selectable: { value: KColumn[] };
    hiddenCount: { value: number };
    isVisible: (col: KColumn) => boolean;
    toggle: (col: KColumn, next?: boolean) => void;
    reset: () => void;
}

const props = defineProps<{
    /** Rückgabe von `useTableFilters` — ohne sie zeigt die Leiste keine Chips. */
    filters?: FilterApi | null;
    /** Rückgabe von `useTableColumns` — ohne sie fehlt der Knopf „Spalten". */
    columns?: ColumnApi | null;
    /** Sichtbare Zeilen nach Filterung. */
    shown?: number;
    /** Zeilen insgesamt. */
    total?: number;
    /** Was gezählt wird: „Mitarbeiter", „Fahrzeuge" … Ohne Angabe „Einträge". */
    noun?: string;
}>();

const countText = computed(() => {
    const shown = props.shown ?? 0;
    const total = props.total ?? shown;
    const noun = props.noun?.trim();
    if (total !== shown) {
        return noun
            ? t('kTable.countOfNoun', { n: shown, total, noun })
            : t('kTable.countOf', { n: shown, total });
    }
    return noun ? t('kTable.countNoun', { n: shown, noun }) : t('common.entries', { n: shown });
});

/** Beschriftung eines Facetten-Chips: ungesetzt „+ Rang", gesetzt „Rang: Lieutenant". */
function facetLabel(facet: KFacet): string {
    const chosen = props.filters?.facetValues(facet.field) ?? [];
    if (chosen.length === 0) return `+ ${facet.label}`;
    if (chosen.length === 1) {
        const option = props.filters?.optionsFor(facet).find(o => o.value === chosen[0]);
        return `${facet.label}: ${option?.label ?? chosen[0]}`;
    }
    return `${facet.label}: ${chosen.length}`;
}

function isFacetOn(facet: KFacet): boolean {
    return (props.filters?.facetValues(facet.field) ?? []).length > 0;
}
</script>

<template>
    <div class="k-toolbar">
        <!-- Schalter: feste Bedingungen wie „Aktiv" oder „Abwesend". -->
        <v-chip
            v-for="preset in filters?.presets ?? []"
            :key="preset.key"
            size="small"
            variant="outlined"
            class="k-filter-chip"
            :class="{ 'is-active': filters?.isPresetOn(preset.key) }"
            @click="filters?.togglePreset(preset.key)"
        >
            {{ preset.label }}
        </v-chip>

        <!-- Facetten: die Werte kommen aus den Daten. -->
        <v-menu
            v-for="facet in filters?.facets ?? []"
            :key="facet.field"
            location="bottom start"
            :close-on-content-click="false"
        >
            <template #activator="{ props: menuProps }">
                <v-chip
                    v-bind="menuProps"
                    size="small"
                    variant="outlined"
                    class="k-filter-chip"
                    :class="{ 'is-active': isFacetOn(facet) }"
                >
                    {{ facetLabel(facet) }}
                </v-chip>
            </template>
            <v-list density="compact" class="k-facet-menu">
                <v-list-item
                    v-for="option in filters?.optionsFor(facet) ?? []"
                    :key="option.value"
                    @click="filters?.toggleFacetValue(facet.field, option.value)"
                >
                    <template #prepend>
                        <v-checkbox-btn
                            :model-value="(filters?.facetValues(facet.field) ?? []).includes(option.value)"
                            density="compact"
                            @click.stop="filters?.toggleFacetValue(facet.field, option.value)"
                        />
                    </template>
                    <v-list-item-title class="k-facet-menu__label">{{ option.label }}</v-list-item-title>
                    <template #append>
                        <span class="k-facet-menu__count">{{ option.count }}</span>
                    </template>
                </v-list-item>
                <v-list-item v-if="(filters?.optionsFor(facet) ?? []).length === 0" disabled>
                    <v-list-item-title class="k-facet-menu__label">
                        {{ t('kTable.noValues') }}
                    </v-list-item-title>
                </v-list-item>
                <v-divider v-if="isFacetOn(facet)" />
                <v-list-item v-if="isFacetOn(facet)" @click="filters?.clearFacet(facet.field)">
                    <v-list-item-title class="k-facet-menu__label">
                        {{ t('kTable.clearFacet') }}
                    </v-list-item-title>
                </v-list-item>
            </v-list>
        </v-menu>

        <!-- Erst wenn mehr als ein Filter greift, lohnt ein Weg zurück. -->
        <v-btn
            v-if="(filters?.activeCount.value ?? 0) > 1"
            variant="text"
            size="x-small"
            class="k-toolbar__reset"
            @click="filters?.clearAll()"
        >
            {{ t('kTable.clearAll') }}
        </v-btn>

        <slot name="filters" />

        <span class="k-toolbar__spacer"></span>

        <span class="k-toolbar__count">{{ countText }}</span>

        <!-- Spaltenauswahl: was man sieht, sollte man auch ausgeben können. -->
        <v-menu
            v-if="columns && columns.selectable.value.length"
            location="bottom end"
            :close-on-content-click="false"
        >
            <template #activator="{ props: menuProps }">
                <v-btn v-bind="menuProps" variant="outlined" size="small" class="k-toolbar__columns">
                    {{ t('kTable.columns') }}
                    <span v-if="columns.hiddenCount.value" class="k-toolbar__columns-badge">
                        {{ columns.hiddenCount.value }}
                    </span>
                </v-btn>
            </template>
            <v-list density="compact" class="k-facet-menu">
                <v-list-item
                    v-for="col in columns.selectable.value"
                    :key="String(col.key)"
                    @click="columns.toggle(col)"
                >
                    <template #prepend>
                        <v-checkbox-btn
                            :model-value="columns.isVisible(col)"
                            density="compact"
                            @click.stop="columns.toggle(col)"
                        />
                    </template>
                    <v-list-item-title class="k-facet-menu__label">{{ col.title }}</v-list-item-title>
                </v-list-item>
                <v-divider />
                <v-list-item @click="columns.reset()">
                    <v-list-item-title class="k-facet-menu__label">
                        {{ t('kTable.resetColumns') }}
                    </v-list-item-title>
                </v-list-item>
            </v-list>
        </v-menu>

        <slot name="actions" />
        <slot />
    </div>
</template>

<style scoped>
.k-toolbar__reset {
    font-size: 11.5px;
    min-width: 0;
    padding-inline: 6px;
}

.k-toolbar__columns-badge {
    margin-inline-start: 5px;
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 10.5px;
    background: var(--k-neutral-weak, #eef0f3);
    color: var(--k-ink-muted, #5c6675);
    border-radius: 3px;
    padding: 0 4px;
}

.k-facet-menu {
    max-height: 320px;
    min-width: 200px;
    background: var(--k-raised, #fff);
}

.k-facet-menu__label {
    font-size: 12.5px;
}

.k-facet-menu__count {
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 11px;
    font-variant-numeric: tabular-nums;
    color: var(--k-ink-faint, #7d8794);
}
</style>
