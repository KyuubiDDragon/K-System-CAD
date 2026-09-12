/**
 * Ausgabe einer Tabelle als CSV.
 *
 * Der Entwurf begründet, warum die Spaltenauswahl in die Tabelle gehört: „Was
 * man sieht, sollte man auch ausgeben können." Deshalb nimmt diese Funktion
 * genau die sichtbaren Spalten in genau ihrer Reihenfolge — und nicht eine
 * zweite, getrennt gepflegte Liste, die mit der Zeit auseinanderläuft.
 *
 * Trennzeichen ist das Semikolon und nicht das Komma: Excel in deutscher
 * Einstellung erwartet das, und Beträge tragen hier ein Komma als
 * Dezimaltrenner.
 */
import type { KColumn } from '@/composables/useTableColumns';

/** Spalten, die in einer Ausgabe nichts verloren haben. */
function isExportable(col: KColumn): boolean {
    return (
        !!col.key &&
        col.key !== 'actions' &&
        col.key !== 'data-table-select' &&
        col.key !== 'data-table-expand'
    );
}

function escapeCell(value: string): string {
    return `"${value.replace(/"/g, '""')}"`;
}

/** Rohwert einer Zelle als Text. Verschachtelte Pfade („rank.name") gehen mit. */
export function plainCell(item: any, key: string): string {
    const raw = key.includes('.')
        ? key.split('.').reduce((acc: any, part) => (acc == null ? acc : acc[part]), item)
        : item?.[key];

    if (raw === null || raw === undefined) return '';
    if (Array.isArray(raw)) return raw.map(v => plainValue(v)).join(' / ');
    if (typeof raw === 'object')
        return Object.values(raw)
            .map(v => plainValue(v))
            .join(' / ');
    if (typeof raw === 'boolean') return raw ? 'ja' : 'nein';
    return String(raw);
}

function plainValue(value: any): string {
    if (value === null || value === undefined) return '';
    if (typeof value === 'object') return String(value.name ?? value.title ?? value.label ?? '');
    return String(value);
}

export interface ExportOptions {
    /** Dateiname ohne Endung. Das Datum hängt die Funktion an. */
    name?: string;
    /** Eigene Textfassung je Zelle, etwa für formatierte Daten und Beträge. */
    cell?: (item: any, key: string) => string | undefined;
}

/**
 * Erzeugt die Datei und stößt den Download an.
 * Gibt die Zahl der ausgegebenen Zeilen zurück, damit die Ansicht eine
 * Rückmeldung zeigen kann.
 */
export function exportRowsAsCsv(
    columns: KColumn[],
    rows: any[],
    options: ExportOptions = {}
): number {
    const cols = columns.filter(isExportable);
    if (!cols.length || !rows.length) return 0;

    const cellOf = (item: any, key: string): string => {
        const custom = options.cell?.(item, key);
        return custom !== undefined ? custom : plainCell(item, key);
    };

    const csv = [
        cols.map(c => escapeCell(String(c.title ?? c.key))).join(';'),
        ...rows.map(item => cols.map(c => escapeCell(cellOf(item, String(c.key)))).join(';')),
    ].join('\r\n');

    // Byte-Order-Mark, sonst zeigt Excel Umlaute als Kauderwelsch.
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${options.name ?? 'tabelle'}-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    return rows.length;
}
