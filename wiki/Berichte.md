# Berichte

Das Berichtssystem von K-Systems bietet dynamische Berichte mit benutzerdefinierten Feldern, Kategorien, Status-Workflow, Freigabefunktionen und umfangreichen Filtermoglichkeiten.

## Ueberblick

Funktionen:
- Dynamische Berichtserstellung mit benutzerdefinierten Feldern
- Berichtskategorien zur Organisation
- Berichtscodes zur Klassifizierung
- Status-Workflow-Verwaltung
- Personen- und Firmenverknuepfungen
- Berichtsfreigabe mit Zugriffsebenen
- Erweiterte Filterung (6 Filtertypen + benutzerdefinierte Felder)
- Tab-basierte Ansichten (Alle, Mit mir geteilt, Von mir geteilt)
- Wichtige Berichte anheften
- PDF-Download

**Erforderliche Berechtigungen:**
- `READ_REPORT` (Berichte anzeigen)
- `WRITE_REPORT` (Berichte erstellen/bearbeiten)
- `DELETE_REPORT` (Berichte loeschen)
- `SHARE_REPORT` (Berichte mit anderen teilen)

## Zugriff auf Berichte

- **Desktop:** Doppelklick auf das **Berichte**-Symbol
- **Menue:** Startmenue -> Berichte & Dokumente -> Berichte
- **Route:** `/report`

## Oberflaeche

### Filterkarte

**Filtertyp-Schaltflaechen** (6 Typen mit Anzahl):
- **Alle** - Alle Berichte
- **Eigene** - Von Ihnen erstellte Berichte
- **Fehlend** - Berichte, die Ihre Eingabe benoetigen
- **Unvollstaendig** - Berichte mit fehlenden Mitarbeiterdaten
- **Offen** - Nicht genehmigte Berichte
- **Ausstehend** - Vollstaendige, aber nicht genehmigte Berichte

**Suchfeld:**
- Sucht nach Titel, Ort und Berichtscode
- Echtzeit-Filterung

**Dropdown-Filter:**
- **Kategorie** - Nach Berichtskategorie filtern
- **Ersteller** - Nach Mitarbeiter filtern

**Benutzerdefinierte Felder-Filter:**
- Dynamische Filter basierend auf konfigurierten benutzerdefinierten Feldern
- Unterstuetzt Text, Zahl, Datum, Boolean, Auswahl, Mehrfachauswahl

### Tab-Navigation

1. **Alle Berichte** - Berichte Ihrer Organisation
2. **Mit mir geteilt** - Von anderen Organisationen geteilte Berichte
3. **Von mir geteilt** - Von Ihnen geteilte Berichte
4. **Alle geteilten** - Kombinierte Ansicht aller Freigaben

### Berichtstabelle

Spalten: Statusanzeige, Berichtsinfo (Titel mit Code), Kategorie, Status, Ersteller, Erstellt am, Aktualisiert am, Aktionen

- 25 Berichte pro Seite
- Sortierbar durch Klick auf Spaltenkoepfe
- Angeheftete Berichte mit blauem Pinnadel-Symbol

## Berichte erstellen

### Schritt 1: Kategorie waehlen
1. **Neuer Bericht** klicken
2. Kategorie-Auswahldialog oeffnet sich
3. Berichtskategorie aus Dropdown waehlen
4. **Weiter** klicken

### Schritt 2: Berichtsformular ausfuellen
Das dynamische Formular oeffnet sich basierend auf der gewaehlten Kategorie.

- Felder variieren je nach Konfiguration der benutzerdefinierten Felder
- Pflichtfelder sind markiert
- Validierung vor dem Speichern

1. Alle erforderlichen Felder ausfuellen
2. **Speichern** klicken
3. Bericht wird erstellt und erscheint in der Tabelle

## Berichte bearbeiten

1. **Bearbeiten**-Symbol in der Berichtszeile klicken
2. Bearbeitungsdialog oeffnet sich mit aktuellen Daten
3. Felder aendern
4. **Speichern** klicken

### Geteilte Berichte anzeigen (Schreibgeschuetzt)
1. Im Tab "Mit mir geteilt" oder "Alle geteilten"
2. **Bearbeiten**-Symbol klicken
3. Schreibgeschuetzte Ansicht oeffnet sich (Vollbild)
4. Zeigt vollstaendige Berichtsdetails inkl. Quellorganisation

## Berichte loeschen

1. **Loeschen**-Symbol (roter Papierkorb) in der Berichtszeile klicken
2. Bestaetigung im Dialog
3. **Loeschen** klicken

**Warnung:** Loeschung ist dauerhaft.

## Berichte teilen

### Einen Bericht teilen
1. **Teilen**-Symbol in der Berichtszeile klicken
2. Freigabedialog oeffnet sich
3. Empfaengerorganisationen auswaehlen
4. Zugriffsebene festlegen (Lesen/Bearbeiten)
5. **Teilen** klicken

**Voraussetzungen:** `SHARE_REPORT` oder `ALL_PERMISSIONS` und `WRITE_REPORT`

### Freigabe-Indikatoren
- Blauer Pfeil (links unten): Mit Ihnen geteilt
- Gruener Pfeil (rechts oben): Von Ihnen geteilt
- Tooltip zeigt Quell-/Zielorganisation

## Berichte anheften

1. **Anheften**-Symbol in der Berichtszeile klicken
2. Bericht wird als angeheftet markiert
3. Blaues Pinnadel-Symbol erscheint
4. Erneut klicken zum Losloesung

**Verwendung:** Haeufig genutzte Berichte schnell identifizierbar halten.

## PDF herunterladen

1. **Download**-Symbol in der Berichtszeile klicken
2. System generiert PDF
3. Ladeanzeige waehrend der Erstellung
4. PDF wird heruntergeladen mit Dateiname: `report_{id}_{titel}.pdf`

## Fehlende Mitarbeiter

Einige Berichte erfordern Eingaben von mehreren Mitarbeitern:
- Oranges Warnsymbol in der Statusspalte
- Tooltip zeigt Namen der fehlenden Mitarbeiter
- Filter **Fehlend** zeigt nur Berichte, bei denen SIE fehlen

## Filterung

### Filtertyp-Schaltflaechen
- **Alle:** Zeigt alle Berichte
- **Eigene:** Nur von Ihnen erstellte Berichte
- **Fehlend:** Berichte, die Ihre Eingabe brauchen
- **Unvollstaendig:** Berichte mit fehlenden Mitarbeiterdaten
- **Offen:** Nicht genehmigte Berichte
- **Ausstehend:** Vollstaendig, aber nicht genehmigt

### Textsuche
- Sucht in Titel, Ort und Berichtscode
- Gross-/Kleinschreibung wird ignoriert
- Echtzeit-Filterung

### Benutzerdefinierte Felder-Filter
- Aufklappbares Panel in der Filterkarte
- Filtertypen je nach Feldtyp: Text, Zahl, Datum, Boolean, Auswahl, Mehrfachauswahl
- Alle Filter arbeiten zusammen (UND-Logik)

## Tipps und Empfehlungen

**Berichte erstellen:**
1. Passende Kategorie waehlen
2. Alle Pflichtfelder ausfuellen
3. Relevante Personen/Firmen verknuepfen
4. Richtigen Berichtscode waehlen
5. Anfangsstatus setzen

**Filter nutzen:**
1. Mit Filtertyp-Schaltflaechen fuer Schnellansichten beginnen
2. Mit Suche fuer spezifische Berichte kombinieren
3. Benutzerdefinierte Felder-Filter fuer detaillierte Suchen verwenden
4. Filter loeschen beim Aufgabenwechsel

**Berichte teilen:**
1. Nur abgeschlossene Berichte teilen
2. Angemessene Zugriffsebene setzen (meist Lesen)
3. Freigaben im Tab "Von mir geteilt" verfolgen

## Fehlerbehebung

### Bericht kann nicht erstellt werden
- `WRITE_REPORT`-Berechtigung pruefen
- Kategorien im System vorhanden?
- Benutzerdefinierte Felder konfiguriert?
- Seite aktualisieren

### Bericht wird nicht gespeichert
- Alle Pflichtfelder ausgefuellt?
- Internetverbindung pruefen
- Dropdown-Daten geladen (Kategorien, Codes, Status)?

### Filter funktionieren nicht
- Mehrere sich widersprechende Filter pruefen
- Suchfeld loeschen
- Benutzerdefinierte Felder-Filter zuruecksetzen
- Seite aktualisieren

### PDF-Download schlaegt fehl
- Bericht hat Inhalt?
- Popup-Blocker-Einstellungen pruefen
- Anderen Browser versuchen

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Dokumente]] - Dokumentenverwaltung
- [[Rechnungen]] - Rechnungsverwaltung
- [[Dateimanager]] - Dateiverwaltung
