/**
 * Die Arten von Abschnitten, in einer Liste.
 *
 * Die Schluessel sind die Werte der Spalte section_type und bleiben englisch -
 * daran haengt die Ausgabe im oeffentlichen Teil. Uebersetzt wird nur, was
 * angezeigt wird.
 *
 * Warum hier und nicht je Komponente: die Beschriftungen standen an drei
 * Stellen - im Auswahlfeld des Editors, im Filter der Liste und auf der
 * Kennzeichnung jeder Karte. Zwei davon kannten weniger Arten als die dritte,
 * und alle drei waren englisch. Eine Liste kann nicht auseinanderlaufen.
 */

export interface Abschnittsart {
    wert: string;
    name: string;
    /** Gruppe im Auswahlfeld des Editors. */
    gruppe: 'basis' | 'inhalt' | 'weiteres';
}

export const ABSCHNITTSARTEN: readonly Abschnittsart[] = [
    { wert: 'hero', name: 'Kopfbereich', gruppe: 'basis' },
    { wert: 'about', name: 'Über uns', gruppe: 'basis' },
    { wert: 'contact', name: 'Kontakt', gruppe: 'basis' },
    { wert: 'custom', name: 'Freier Text', gruppe: 'basis' },

    { wert: 'services', name: 'Leistungen', gruppe: 'inhalt' },
    { wert: 'features', name: 'Merkmale', gruppe: 'inhalt' },
    { wert: 'team', name: 'Team', gruppe: 'inhalt' },
    { wert: 'testimonials', name: 'Stimmen', gruppe: 'inhalt' },
    { wert: 'portfolio', name: 'Arbeiten', gruppe: 'inhalt' },
    { wert: 'gallery', name: 'Bildergalerie', gruppe: 'inhalt' },
    { wert: 'video', name: 'Video', gruppe: 'inhalt' },

    { wert: 'pricing', name: 'Preise', gruppe: 'weiteres' },
    { wert: 'statistics', name: 'Zahlen', gruppe: 'weiteres' },
    { wert: 'faq', name: 'Häufige Fragen', gruppe: 'weiteres' },
    { wert: 'cta', name: 'Handlungsaufruf', gruppe: 'weiteres' },
];

export const GRUPPENNAMEN: Record<Abschnittsart['gruppe'], string> = {
    basis: 'Grundgerüst',
    inhalt: 'Inhalt',
    weiteres: 'Weiteres',
};

/** Unbekannte Werte gibt sie unveraendert zurueck - besser als eine Luecke. */
export function nameDerArt(wert: string): string {
    return ABSCHNITTSARTEN.find((a) => a.wert === wert)?.name ?? wert;
}

export function artenDerGruppe(gruppe: Abschnittsart['gruppe']): Abschnittsart[] {
    return ABSCHNITTSARTEN.filter((a) => a.gruppe === gruppe);
}
