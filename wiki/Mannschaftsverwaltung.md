# Mannschaftsverwaltung

Die Mannschaftsverwaltung (Crew Management) von K-Systems bietet eine einfache Verwaltung von Mannschaftslisten zur Organisation von Einsatzteams mit grundlegenden CRUD-Operationen.

## Ueberblick

Funktionen:
- Mannschaften erstellen, bearbeiten und loeschen
- Mannschaftsname und Statusverfolgung
- Sortierreihenfolge konfigurieren
- Aktiv/Inaktiv-Status umschalten
- Mandantenspezifische Datenisolierung
- Einfache Tabellenansicht

**Erforderliche Berechtigungen:** `READ_CREW` (Ansicht), `WRITE_CREW` (Erstellen/Bearbeiten), `DELETE_CREW` (Loeschen)

## Zugriff auf die Mannschaftsverwaltung

- **Desktop:** Doppelklick auf das **Mannschaft**-Symbol
- **Menue:** Startmenue -> Einsatz -> Mannschaftsverwaltung
- **Route:** `/crew`

## Oberflaeche

### Hauptansicht

**Kopfzeile:**
- Titel mit Mannschafts-Symbol
- **Neue Mannschaft**-Button (bei WRITE-Berechtigung)

**Mannschaftstabelle:**

| Spalte | Beschreibung |
|--------|-------------|
| **Name** | Mannschaftsname mit Symbol |
| **Status** | Optionaler Statustext |
| **Sortierung** | Sortierreihenfolge (Zahl) |
| **Aktiv** | Aktiv/Inaktiv-Status-Chip |
| **Aktionen** | Bearbeiten, Loeschen, Aktivieren/Deaktivieren |

**Sortierung:** Primaer nach `Sortierung` (aufsteigend), sekundaer nach `Name` (alphabetisch)

## Mannschaften erstellen

### Neue Mannschaft

1. **Neue Mannschaft** klicken (erfordert `WRITE_CREW`-Berechtigung)
2. Dialog oeffnet sich mit Formular

**Formularfelder:**

- **Name** (erforderlich): Mannschaftsname oder Bezeichnung
  - Beispiel: "Fahrzeug 1 A-Schicht", "Leiter 2 B-Schicht", "Rettungswagen 3"
- **Status** (optional): Optionale Statusbeschreibung
  - Beispiel: "Im Dienst", "Ausbildung", "Verfuegbar"
- **Sortierung** (erforderlich): Sortierreihenfolge fuer die Tabellenanzeige
  - Niedrigere Zahlen erscheinen zuerst
  - Standard: 0

3. **Speichern** klicken
4. Mannschaft erscheint in der Tabelle, sortiert nach Sortierreihenfolge

**Validierung:**
- Name ist erforderlich
- Sortierreihenfolge ist erforderlich (Zahl)
- Speichern-Button deaktiviert bis gueltig

## Mannschaften bearbeiten

1. **Bearbeiten**-Symbol (Stift) in der Mannschaftszeile klicken
2. Dialog oeffnet sich mit aktuellen Daten
3. Felder aendern: Name, Status, Sortierreihenfolge
4. **Speichern** klicken
5. Aenderungen werden sofort uebernommen

**Berechtigung erforderlich:** `WRITE_CREW`

## Mannschaften loeschen

1. **Loeschen**-Symbol (Papierkorb) in der Mannschaftszeile klicken
2. Bestaetigung im Dialog:
   - Warnsymbol
   - "Sind Sie sicher, dass Sie diese Mannschaft loeschen moechten?"
   - Mannschaftsname wird angezeigt
   - Hinweis auf dauerhafte Loeschung
3. **Loeschen** klicken zum Bestaetigen
4. Oder **Abbrechen**

**Berechtigung erforderlich:** `DELETE_CREW`

**Warnung:** Loeschung ist dauerhaft und kann nicht rueckgaengig gemacht werden.

## Aktiv/Inaktiv-Status

### Status umschalten

1. **Stecker**-Symbol in der Mannschaftszeile klicken
   - Gruenes Stecker-Symbol = Aktiv
   - Graues Stecker-aus-Symbol = Inaktiv
2. Status wechselt sofort
3. Ladeanzeige waehrend des API-Aufrufs
4. Erfolgs-Benachrichtigung

**Statusanzeige:**
- **Aktiv:** Gruener Chip mit Haekchen-Symbol
- **Inaktiv:** Roter Chip mit Kreuz-Symbol

**Tooltip:** Zeigt "Aktivieren" oder "Deaktivieren" beim Hovern

## Tipps und Empfehlungen

**Mannschaften erstellen:**
- Klare, beschreibende Namen verwenden
- Schichtbezeichnung im Namen angeben, falls zutreffend
- Einheitliche Namenskonvention verwenden
- Logische Sortierreihenfolge setzen (nach Station, Schicht, Prioritaet)

**Sortierreihenfolge planen:**
- Nummerierungsschema planen (z.B. 10, 20, 30 fuer einfaches Einfuegen)
- Niedrigere Zahlen erscheinen zuerst
- Gleiche Schrittweite fuer einfache Neuordnung verwenden

**Statusfeld:**
- Fuer aktuellen Zustand verwenden (z.B. "Im Dienst", "Ausbildung")
- Kurz und klar halten
- Bei Bedarf aktualisieren
- Optional - leer lassen wenn nicht benoetigt

**Aktiv/Inaktiv:**
- Nicht mehr genutzte Mannschaften deaktivieren
- Historische Mannschaften als inaktiv belassen statt zu loeschen
- Fuer saisonale oder temporaere Mannschaften nutzen

## Fehlerbehebung

### Mannschaft kann nicht erstellt werden
- `WRITE_CREW`-Berechtigung pruefen
- Funktion fuer Ihre Organisation aktiviert?
- Seite aktualisieren

### Mannschaft wird nicht gespeichert
- Name ausgefuellt (Pflichtfeld)?
- Sortierreihenfolge ausgefuellt (Pflichtfeld)?
- Validierungsmeldungen pruefen
- Internetverbindung pruefen

### Mannschaft kann nicht bearbeitet werden
- `WRITE_CREW`-Berechtigung pruefen
- Mannschaft gehoert zu Ihrer Organisation?
- Seite aktualisieren

### Mannschaft kann nicht geloescht werden
- `DELETE_CREW`-Berechtigung pruefen
- Mannschaft hat moeglicherweise verknuepfte Datensaetze
- Fehlermeldung in Toast-Benachrichtigung pruefen
- Deaktivieren statt Loeschen erwaegen

### Aktivierung/Deaktivierung schlaegt fehl
- Internetverbindung pruefen
- Browser-Konsole auf API-Fehler pruefen
- Seite aktualisieren

### Mannschaften werden nicht geladen
- Internetverbindung pruefen
- Backend laeuft?
- Browser-Konsole auf API-Fehler pruefen

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Mitarbeiterverwaltung]] - Mitarbeiter verwalten
- [[Einsatzleitung]] - Einsatzleitungssystem
- [[Kalender]] - Terminplanung
