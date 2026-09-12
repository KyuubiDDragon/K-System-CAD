/**
 * Steht die Ersteinrichtung noch aus?
 *
 * Der Grundstock der Datenbank liefert kein Konto mit. Solange keines
 * existiert, muss jeder Weg zur Ersteinrichtung fuehren und danach keiner
 * mehr. Diese eine Frage beantwortet dieses Modul - genau einmal je
 * Seitenaufruf, denn sie aendert sich hoechstens einmal im Leben einer Anlage.
 *
 * Faellt die Abfrage aus - alte Fassung ohne den Endpunkt, Netz weg, Server
 * antwortet nicht - gilt die Anlage als eingerichtet. Ein Fehler beim Fragen
 * darf niemanden aus seinem angemeldeten System aussperren.
 */
import { apiClientPublic } from '@/api';

let laufendeAbfrage: Promise<boolean> | null = null;
let bekannt: boolean | null = null;

export async function einrichtungNoetig(): Promise<boolean> {
    if (bekannt !== null) return bekannt;
    if (laufendeAbfrage) return laufendeAbfrage;

    laufendeAbfrage = apiClientPublic
        .get('/setup/?action=status')
        .then(antwort => {
            bekannt = antwort?.data?.needsSetup === true;
            return bekannt;
        })
        .catch(() => {
            bekannt = false;
            return false;
        })
        .finally(() => {
            laufendeAbfrage = null;
        });

    return laufendeAbfrage;
}

/**
 * Nach dem Anlegen des ersten Kontos: die Antwort von vorhin gilt nicht mehr.
 */
export function einrichtungNeuPruefen(): void {
    bekannt = null;
    laufendeAbfrage = null;
}
