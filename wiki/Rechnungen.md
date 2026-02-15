# Rechnungen

Die Rechnungsverwaltung von K-Systems bietet ein einfaches System zur Nachverfolgung von eingehenden und ausgehenden Rechnungen mit Kundeninformationen und Positionen.

## Ueberblick

Funktionen:
- Rechnungen mit Positionen erstellen
- Kundenverknuepfung (Firmen oder Personen)
- Statusverfolgung (Geliefert, Gesendet, Bezahlt)
- Dateianhanginge
- Suchfunktion
- Mandantenspezifische Datenisolierung

**Erforderliche Berechtigungen:** `READ_INVOICE` (Ansicht), `WRITE_INVOICE` (Erstellen/Bearbeiten), `DELETE_INVOICE` (Loeschen)

## Zugriff auf Rechnungen

- **Desktop:** Doppelklick auf das **Rechnungen**-Symbol
- **Menue:** Startmenue -> Finanzen -> Rechnungen
- **Route:** `/invoice`

## Oberflaeche

### Rechnungstabelle

**Spalten:**
- **Typ** - Gruener "Einnahme"-Chip oder Roter "Ausgabe"-Chip
- **Betreff** - Rechnungstitel
- **Kunde** - Kundenname
- **Geliefert** - Lieferdatum oder "-"
- **Gesendet** - Sendedatum (blauer Chip) oder "-"
- **Bezahlt** - Zahlungsdatum (gruener Chip) oder "-"
- **Aktionen** - Anzeigen, Bearbeiten, Loeschen

**Werkzeugleiste:**
- Suchfeld (filtert nach Kundenname oder Rechnungstitel)
- **Neue Rechnung**-Button

**Seitennummerierung:** 25 Eintraege pro Seite, sortiert nach ID absteigend (neueste zuerst)

## Rechnungen erstellen

### Neue Rechnung

1. **Neue Rechnung** klicken
2. Formulardialog oeffnet sich mit mehreren Abschnitten:

**Abschnitt 1: Kundenauswahl**
- **Firma**: Aus bestehenden Firmen waehlen (Autocomplete)
- **Person**: Aus bestehenden Personen waehlen (Autocomplete)
- **Daten laden**: Fuellt Kundenkontaktdaten automatisch aus
- Rechnung wird mit der gewaehlten Firma ODER Person verknuepft (nicht beides)

**Abschnitt 2: Status-Umschalter**
- **Geliefert** - Kontrollkaestchen
- **Gesendet** - Kontrollkaestchen
- **Bezahlt** - Kontrollkaestchen

**Abschnitt 3: Transaktionstyp**
- Dropdown: "Einnahme" oder "Ausgabe" waehlen

**Abschnitt 4: Grundinformationen**
- **Titel** (erforderlich): Rechnungsbetreff
- **Kunde**: Kundenname (automatisch ausgefuellt oder manuell)
- **Telefonnummer**: Kundentelefon
- **E-Mail**: Kunden-E-Mail
- **Konto**: Bankverbindung
- **Beschreibung**: Mehrzeilige Textbeschreibung

**Abschnitt 5: Positionen**
- **Artikelauswahl**: Dropdown mit vordefinierten Artikeln
- **Beschreibung**: Artikelbeschreibung (automatisch ausgefuellt)
- **Preis**: Einzelpreis
- **Menge**: Anzahl (Standard: 1)
- **Artikel hinzufuegen**: Blaues "+"-Symbol
- **Artikel entfernen**: Rotes "X"-Symbol

**Abschnitt 6: Zusaetzliche Optionen**
- **Rabatt**: Numerischer Rabattwert
- **Anhaenge**: Mehrfacher Datei-Upload

3. Pflichtfelder ausfuellen (mindestens: Titel)
4. Positionen nach Bedarf hinzufuegen
5. **Speichern** klicken

## Rechnungen bearbeiten

1. **Bearbeiten**-Symbol (Stift) in der Rechnungszeile klicken
2. Bearbeitungsdialog oeffnet sich mit allen aktuellen Daten
3. Beliebige Felder aendern
4. Positionen hinzufuegen/entfernen
5. Neue Anhaenge hinzufuegen oder vorhandene entfernen
6. **Speichern** klicken

## Rechnungen anzeigen

1. **Anzeigen**-Symbol (Auge) in der Rechnungszeile klicken
2. Schreibgeschuetzter Dialog zeigt alle Informationen
3. Verknuepfte Firma/Person, Positionen und Anhaenge werden angezeigt

## Rechnungen loeschen

1. **Loeschen**-Symbol (Papierkorb) in der Rechnungszeile klicken
2. Loeschung im Dialog bestaetigen

**Warnung:** Loeschung ist dauerhaft.
**Berechtigung erforderlich:** `DELETE_INVOICE`

## Rechnungsstatus

**Statusindikatoren:**
- **Geliefert** - Rechnung wurde an den Kunden geliefert
- **Gesendet** - Rechnung wurde versendet
- **Bezahlt** - Rechnung wurde bezahlt

Datumschips zeigen an, wann der Status gesetzt wurde (Format: TT.MM.JJJJ). Leerer Status zeigt "-" an.

## Kundenverknuepfung

### Firmenverknuepfung
1. Firma aus Autocomplete waehlen
2. "Daten laden" klicken
3. Kundenname, Telefon, E-Mail werden automatisch ausgefuellt
4. Rechnung wird mit der Firma verknuepft

### Personenverknuepfung
1. Person aus Autocomplete waehlen
2. "Daten laden" klicken
3. Kundeninformationen werden automatisch ausgefuellt
4. Rechnung wird mit der Person verknuepft

**Hinweis:** Rechnung kann mit Firma ODER Person verknuepft werden, nicht mit beiden.

## Positionen

1. "Artikel hinzufuegen" klicken fuer neue Position
2. Optional: Vordefiniertes Element aus Dropdown waehlen (fuellt Beschreibung und Preis automatisch aus)
3. Oder manuell Beschreibung und Preis eingeben
4. Menge festlegen (Standard: 1)
5. "X" klicken zum Entfernen

Fuegen Sie beliebig viele Positionen hinzu. Jede Position wird auf einer separaten Karte angezeigt.

## Dateianhanginge

**Upload:**
- Mehrfacher Datei-Upload wird unterstuetzt
- Erlaubte Typen: jpg, jpeg, png, pdf, docx, doc, txt
- Dateien werden im mandantenspezifischen Ordner gespeichert

**Verwaltung:**
- Neue Anhaenge beim Bearbeiten hinzufuegen
- Vorhandene Anhaenge entfernen

## Suche

- Suchleiste in der Werkzeugleiste
- Filtert nach Kundenname oder Rechnungstitel
- Echtzeit-Filterung
- Gross-/Kleinschreibung wird ignoriert

## Tipps und Empfehlungen

**Rechnungen erstellen:**
- Immer Titel ausfuellen (Pflichtfeld)
- Mit Firma oder Person verknuepfen fuer bessere Nachverfolgung
- "Daten laden" nutzen fuer automatische Kundeninformationen
- Transaktionstyp (Einnahme/Ausgabe) korrekt setzen

**Positionen:**
- Vordefinierte Artikel nutzen, wenn verfuegbar
- Spezifische Artikelbeschreibungen verwenden
- Mengen und Preise ueberpruefen

**Statusverwaltung:**
- Geliefert-Status aktualisieren, wenn Rechnung zugestellt wurde
- Als gesendet markieren, wenn tatsaechlich versendet
- Als bezahlt markieren bei Zahlungseingang

## Fehlerbehebung

### Rechnung kann nicht erstellt werden
- `WRITE_INVOICE`-Berechtigung pruefen
- Titelfeld ausgefuellt?
- Seite aktualisieren

### Kundendaten werden nicht geladen
- Erst Firma oder Person auswaehlen
- Hat die Firma/Person Kontaktinformationen?
- Existiert die Firma/Person im System?

### Position wird nicht gespeichert
- Beschreibung und Preis ausgefuellt?
- Speichern-Button im Rechnungsdialog geklickt?

### Anhaenge werden nicht hochgeladen
- Dateityp unterstuetzt (jpg, jpeg, png, pdf, docx, doc, txt)?
- Dateigroesse angemessen?
- Internetverbindung pruefen

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Berichte]] - Berichtsverwaltung
- [[Dokumente]] - Dokumentenverwaltung
- [[Dateimanager]] - Dateiverwaltung
