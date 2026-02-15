# Karte

Das Kartensystem von K-Systems bietet eine interaktive Karte zur Verwaltung von Standortmarkierungen mit Kategorien und benutzerdefinierten Symbolen basierend auf Leaflet.js.

## Ueberblick

Funktionen:
- Interaktive Leaflet-Karte mit Zoom und Verschieben
- Benutzerdefinierte Markierungen mit Koordinaten
- Markierungskategorien mit individuellen Symbolen
- Drei Kartenstile (Atlas, Satellit, Raster)
- Kategoriefilterung
- Suchfunktion
- Doppelklick zum Hinzufuegen von Markierungen
- Klick auf Markierung fuer Popup-Details
- Seitenleiste mit Kategorieorganisation
- Mandantenspezifische Datenisolierung

**Erforderliche Berechtigungen:** `READ_MAP` (Ansicht), `WRITE_MAP` (Erstellen/Bearbeiten), `DELETE_MAP` (Loeschen)

## Zugriff auf die Karte

- **Desktop:** Doppelklick auf das **Karte**-Symbol
- **Menue:** Startmenue -> Einsatz -> Karte
- **Route:** `/map`

## Oberflaeche

### Linke Seite - Kartenanzeige

**Kartenstile (obere Steuerung):**
- **Atlas** - Standard-Strassenkarte
- **Satellit** - Luftbildaufnahmen
- **Raster** - Rasterbasierte Ansicht

**Karte:**
- Leaflet-Kartenkomponente
- Standard-Zoom: 4
- Doppelklick zum Hinzufuegen einer Markierung

**Markierungen:**
- Dargestellt als benutzerdefinierte Symbole basierend auf der Kategorie
- Klicken oeffnet Popup mit Details:
  - Markierungsname
  - Ansprechpartner (bei bestimmten Kategorien)
  - Telefonnummer (bei bestimmten Kategorien)
  - Standort (immer angezeigt)
  - Bearbeiten-Button (bei WRITE-Berechtigung)
  - Loeschen-Button (bei DELETE-Berechtigung)

### Rechte Seite - Kategorien und Markierungen

**Seitenleiste:**
- Standorte-Titel mit Markierungssymbol

**Filter:**
- **Kategoriefilter:** Mehrfachauswahl-Dropdown mit Chips
- **Suchfeld:** Textsuche nach Markierungsnamen

**Standortliste:**
- Aufklappbare Panels gruppiert nach Kategorie
- Jedes Kategorie-Panel zeigt:
  - Ordnersymbol + Kategoriename
  - Zaehler-Badge (Anzahl der Markierungen)
  - Liste der Markierungen beim Aufklappen:
    - Markierungssymbol
    - Markierungsname (Titel)
    - Standort (Untertitel)
    - Klicken fokussiert auf Markierung (Zoom + Zentrierung)

## Kartenstile wechseln

Klicken Sie auf eine der drei Stilschaltflaechen:

- **Atlas:** Standard-Strassenkarte mit Strassen, Beschriftungen, Gelaende
- **Satellit:** Luft-/Satellitenbilder
- **Raster:** Rasterbasierte Karte

Der aktive Stil wird in Primaerfarbe hervorgehoben.

## Markierungen erstellen

### Markierung per Doppelklick hinzufuegen

1. Doppelklick auf den gewuenschten Kartenstandort
2. Dialog oeffnet sich mit Formular
3. Koordinaten werden automatisch aus der Klickposition ausgefuellt

**Formularfelder:**
- **Name** (erforderlich): Anzeigename der Markierung
- **Ansprechpartner** (optional): Name des Ansprechpartners
- **Telefonnummer** (optional): Kontakttelefonnummer
- **Standort** (optional): Adresse oder Standortbeschreibung
- **Kategorie** (erforderlich): Aus verfuegbaren Kategorien waehlen - bestimmt das Markierungssymbol auf der Karte

4. **Speichern** klicken
5. Markierung erscheint sofort auf der Karte

**Validierung:**
- Name ist erforderlich
- Kategorie ist erforderlich
- Speichern-Button deaktiviert bis beides ausgefuellt

## Markierungen bearbeiten

1. Markierung auf der Karte anklicken
2. Popup oeffnet sich
3. **Bearbeiten**-Button klicken (bei WRITE-Berechtigung)
4. Bearbeitungsdialog oeffnet sich mit aktuellen Daten
5. Felder aendern
6. **Speichern** klicken

**Hinweis:** Koordinaten bleiben unveraendert (nicht editierbar).

## Markierungen loeschen

1. Markierung auf der Karte anklicken
2. Popup oeffnet sich
3. **Loeschen**-Button klicken (bei DELETE-Berechtigung)
4. Bestaetigung im Dialog
5. Markierung wird von Karte und Datenbank entfernt

**Warnung:** Loeschung ist dauerhaft.

## Auf Markierungen fokussieren

### Ueber die Seitenleiste zoomen

1. Kategorie-Panel aufklappen
2. Markierung in der Liste anklicken
3. Karte zentriert sich auf die Markierung
4. Karte zoomt zum Markierungsstandort
5. Popup oeffnet sich automatisch

**Anwendung:** Schnelle Navigation zu bestimmten Standorten ohne manuelles Verschieben der Karte.

## Kategoriefilterung

1. Kategorie-Dropdown in der Seitenleiste anklicken
2. Eine oder mehrere Kategorien waehlen
3. Nur Markierungen der gewaehlten Kategorien werden angezeigt
4. Chips zeigen ausgewaehlte Kategorien
5. X auf einem Chip klicken zum Entfernen des Filters
6. Alle loeschen zeigt alle Markierungen

**Kombiniert mit Suche:** Kategoriefilter UND Suche werden beide angewendet.

## Suche

1. In das Suchfeld tippen
2. Markierungen werden in Echtzeit nach Name gefiltert
3. Gross-/Kleinschreibung wird ignoriert
4. Kategorien ohne passende Markierungen werden ausgeblendet
5. Suche loeschen zeigt alle Markierungen

## Tipps und Empfehlungen

**Markierungen erstellen:**
- Klare, beschreibende Namen verwenden
- Immer eine Kategorie zuweisen fuer Organisation
- Standortbeschreibung fuer Klarheit angeben
- Ansprechpartner/Telefon fuer Geschaeftsstandorte hinzufuegen

**Kategorien:**
- Logische Kategoriestruktur erstellen
- Aussagekraeftige Kategorienamen verwenden
- Verschiedene Symbole pro Kategorie zuweisen
- Kategorieanzahl ueberschaubar halten

**Kartenverwaltung:**
- Passenden Kartenstil fuer den Anwendungsfall waehlen
- Kategoriefilter nutzen um Uebersichtlichkeit zu verbessern
- Nach bestimmten Standorten suchen
- Listeneintraege anklicken fuer schnelle Navigation

## Fehlerbehebung

### Markierung kann nicht hinzugefuegt werden
- `WRITE_MAP`-Berechtigung pruefen
- Doppelklick erneut versuchen (zeitkritisch)
- Karte vollstaendig geladen?
- Seite aktualisieren

### Markierung wird nicht gespeichert
- Name und Kategorie ausgefuellt (Pflichtfelder)?
- Internetverbindung pruefen
- Browser-Konsole auf API-Fehler pruefen

### Karte wird nicht geladen
- Internetverbindung pruefen (Kacheln werden vom Server geladen)
- Kartenstil-Konfiguration pruefen
- Anderen Kartenstil versuchen
- Seite aktualisieren

### Markierungen nicht sichtbar
- Kategoriefilter pruefen (filtert moeglicherweise aus)
- Suchfeld loeschen
- Koordinaten gueltig?
- Herauszoomen um mehr zu sehen
- Seite aktualisieren

### Bearbeiten/Loeschen nicht moeglich
- `WRITE_MAP` und `DELETE_MAP`-Berechtigungen pruefen
- Markierung gehoert zu Ihrer Organisation?
- Seite aktualisieren

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Einsatzleitung]] - Einsatzleitungssystem
- [[Mitarbeiterverwaltung]] - Mitarbeiter verwalten
- [[Dateimanager]] - Dateiverwaltung
