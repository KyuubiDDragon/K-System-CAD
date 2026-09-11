/**
 * Hover-Text fuer abgeschnittene Beschriftungen.
 *
 * Wo der Platz nicht reicht, endet eine Beschriftung mit "…". Was dort steht,
 * ist dann nicht mehr zu lesen - in Tabellen mit langen Dokumenttiteln, in der
 * Taskleiste, in Listen mit langen Namen. Diese Funktion haengt genau dort
 * einen Hover-Text an, wo tatsaechlich gekuerzt wird.
 *
 * Warum zentral und nicht je Komponente: das Kuerzen entsteht aus dem
 * Zusammenspiel von Inhalt und verfuegbarer Breite, nicht aus der Komponente.
 * Dieselbe Zelle kuerzt bei einem langen Namen und bei einem kurzen nicht, und
 * sie kuerzt je nach Fensterbreite mal so, mal so. Eine Regel an einer Stelle
 * trifft alle Faelle, auch die, die es heute noch nicht gibt.
 *
 * Warum das Attribut title und kein eigenes Feld: es braucht kein Markup, kein
 * Umbauen bestehender Ansichten, keine zusaetzliche Ebene ueber dem Inhalt -
 * und Bildschirmleser geben es mit aus.
 *
 * Gesetzt wird erst beim Zeigen, nicht im Voraus. Ein Durchlauf ueber alle
 * Elemente bei jedem Rendern waere teuer und meistens umsonst; beim Zeigen
 * steht genau ein Element zur Frage.
 */

/** Unterhalb dieser Differenz ist nichts gekuerzt, sondern nur gerundet. */
const SCHWELLE_PX = 1;

/** So viele Ebenen nach oben wird gesucht, wenn das Ziel selbst nicht kuerzt. */
const EBENEN = 3;

/** Merkt sich, was wir selbst gesetzt haben - Fremdes fassen wir nicht an. */
const VON_UNS = 'data-hovertext';

function istGekuerzt(el: Element): boolean {
    return el.scrollWidth > el.clientWidth + SCHWELLE_PX;
}

/**
 * Sucht vom Zielelement aufwaerts das erste, das wirklich kuerzt.
 *
 * Beim Zeigen auf eine Tabellenzelle liegt der Zeiger oft auf dem inneren
 * span, der gar nicht kuerzt - gekuerzt wird die Zelle darum herum.
 */
function findeGekuerztes(start: Element | null): HTMLElement | null {
    let el = start;
    for (let i = 0; i <= EBENEN && el instanceof HTMLElement; i++) {
        if (istGekuerzt(el) && el.textContent && el.textContent.trim()) {
            return el;
        }
        el = el.parentElement;
    }
    return null;
}

/**
 * Nimmt eigene Hover-Texte zurueck, wo wieder Platz ist.
 *
 * Muss vor dem Setzen laufen und unabhaengig davon: sonst bleibt ein alter
 * Text stehen, sobald weiter oben ein anderes Element kuerzt und die Suche
 * dort endet. Genau das war der Fall - das Fenster wurde breiter, die
 * Beschriftung passte wieder, und der Hover-Text von vorhin blieb.
 */
function raeumeAuf(start: Element): void {
    let el: Element | null = start;
    for (let i = 0; i <= EBENEN && el instanceof HTMLElement; i++) {
        if (el.hasAttribute(VON_UNS) && !istGekuerzt(el)) {
            el.removeAttribute('title');
            el.removeAttribute(VON_UNS);
        }
        el = el.parentElement;
    }
}

function beiZeigen(ereignis: Event): void {
    const ziel = ereignis.target;
    if (!(ziel instanceof Element)) return;

    raeumeAuf(ziel);

    const gekuerzt = findeGekuerztes(ziel);
    if (!gekuerzt) return;

    // Ein vorhandener Hover-Text, den jemand anders gesetzt hat, bleibt.
    if (gekuerzt.hasAttribute('title') && !gekuerzt.hasAttribute(VON_UNS)) return;

    const text = (gekuerzt.textContent || '').trim().replace(/\s+/g, ' ');
    if (!text) return;

    if (gekuerzt.getAttribute('title') !== text) {
        gekuerzt.setAttribute('title', text);
        gekuerzt.setAttribute(VON_UNS, '');
    }
}

/**
 * Einmal beim Start aufrufen.
 *
 * Ein einziger Zuhoerer am Dokument, in der Erfassungsphase - damit erreicht
 * er auch Elemente, die ihre eigenen Ereignisse abfangen, und er ueberlebt
 * jedes Neuzeichnen, weil er nicht an den Elementen selbst haengt.
 */
export function hoverTextFuerAbgeschnittenes(): void {
    document.addEventListener('pointerover', beiZeigen, { capture: true, passive: true });
}
