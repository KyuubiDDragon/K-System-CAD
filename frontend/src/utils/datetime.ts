/**
 * Datums- und Zeitformate für die Oberfläche.
 *
 * Ohne Sprachangabe verwendet `toLocaleDateString()` die Sprache des Browsers.
 * In einer deutschen Oberfläche stand deshalb `11/20/2025, 12:00:00 PM` —
 * Monat vor Tag, 12-Stunden-Zeit, Sekunden. Diese Funktionen richten sich nach
 * der aktiven Sprache der Anwendung, nicht nach der des Browsers.
 *
 * Sekunden erscheinen nur dort, wo sie eine Aussage haben (Protokolle, Funk),
 * nicht bei Terminen.
 */

import i18n from '@/plugins/i18n';

const LOCALES: Record<string, string> = {
    de: 'de-DE',
    en: 'en-GB',
};

/** Sprachkennung der aktiven Oberfläche, z. B. "de-DE". */
export function currentLocale(): string {
    const raw = i18n.global.locale as unknown;
    const active =
        raw && typeof raw === 'object' && 'value' in raw
            ? (raw as { value: string }).value
            : ((raw as string) ?? 'de');
    return LOCALES[String(active)] ?? 'de-DE';
}

function toDate(value: string | number | Date | null | undefined): Date | null {
    if (value === null || value === undefined || value === '') return null;
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
}

/** 20.11.2025 */
export function formatDate(value: string | number | Date | null | undefined): string {
    const d = toDate(value);
    if (!d) return '—';
    return d.toLocaleDateString(currentLocale(), {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

/** 20.11.2025, 12:00 */
export function formatDateTime(value: string | number | Date | null | undefined): string {
    const d = toDate(value);
    if (!d) return '—';
    return d.toLocaleString(currentLocale(), {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** 14:22 — für Einträge des laufenden Tages. */
export function formatTime(value: string | number | Date | null | undefined): string {
    const d = toDate(value);
    if (!d) return '—';
    return d.toLocaleTimeString(currentLocale(), {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** 14:22:07 — für Protokolle und Funkverkehr, wo Sekunden zählen. */
export function formatTimeExact(value: string | number | Date | null | undefined): string {
    const d = toDate(value);
    if (!d) return '—';
    return d.toLocaleTimeString(currentLocale(), {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

/**
 * Kurze Zeitangabe für Listen: „vor 4 Min." innerhalb einer Stunde,
 * „14:22" am selben Tag, sonst das Datum.
 *
 * Unter einer Stunde ist der Abstand die eigentliche Information — ein
 * Zeitstempel müsste dafür erst im Kopf verrechnet werden.
 */
export function formatRelative(value: string | number | Date | null | undefined): string {
    const d = toDate(value);
    if (!d) return '—';

    const diffMin = Math.floor((Date.now() - d.getTime()) / 60000);

    if (diffMin < 1) return String(i18n.global.t('common.justNow'));
    if (diffMin < 60) return String(i18n.global.t('common.minutesAgo', { n: diffMin }));

    const today = new Date();
    const sameDay =
        d.getDate() === today.getDate() &&
        d.getMonth() === today.getMonth() &&
        d.getFullYear() === today.getFullYear();

    return sameDay ? formatTime(d) : formatDate(d);
}
