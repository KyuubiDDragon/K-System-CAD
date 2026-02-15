# Schulungsverwaltung

Schulungszuweisungs- und Verfolgungssystem mit Matrix-Ansicht, die den Schulungsabschlussstatus der Mitarbeiter über Kategorien hinweg anzeigt.

## Überblick

Funktionen:
- Matrix-Ansicht der Schulungen nach Kategorien
- Mitarbeiterschulungszuweisung
- Verfolgung des Schulungsabschlusses mit Daten und Dozenten
- Kategorie- und Rangfilterung
- Firmenbasierte Filterung
- HTML-Generierung von Schulungsplänen
- Organisationsspezifische Datenisolation
- Admin-Oberfläche für Schulungs-/Kategorieverwaltung

**Erforderliche Berechtigungen:**
- `READ_TRAINING` (Zuweisungen anzeigen)
- `WRITE_TRAINING` (Schulungen zuweisen)
- `ADMIN_READ_TRAINING` (Admin-Konfiguration)

## Zugriff auf Schulungen

**Benutzeransicht - Schulungszuweisung:**
**Desktop:** Doppelklick auf **Training**-Symbol
**Menü:** Startmenü → Mitarbeiterverwaltung → Schulungen
**Route:** `/trainingassign`

**Admin-Ansicht - Schulungskonfiguration:**
**Desktop:** Start → Administration → Schulungen → Einstellungen
**Route:** `/admin/trainings`

## Schulungszuweisungsansicht

### Oberflächenlayout

**Obere Symbolleiste:**
- **Neue Zuweisung** Schaltfläche (bei WRITE-Berechtigung)
- **Schulungsplan** Schaltfläche (erstellt HTML-Tabelle)
- **Alle erweitern** / **Alle zusammenklappen** Kategorie-Schaltflächen
- Suchfeld (filtert Mitarbeiter nach Name/Dienstnummer)
- Rangfilter (Mehrfachauswahl-Dropdown)
- Firmenfilter (Mehrfachauswahl-Dropdown)

**Kategorieabschnitte:**
- Zusammenklappbare Panels für jede Schulungskategorie
- Jede Kategorie zeigt:
  - Kategoriename-Kopfzeile
  - Datentabelle mit Schulungen als Spalten
  - Mitarbeiter als Zeilen
  - Häkchen/Daten für abgeschlossene Schulungen

### Matrix-Ansichtsstruktur

**Tabellenlayout:**
```
| Mitarbeitername | Schulung 1 | Schulung 2 | Schulung 3 | ... |
|-----------------|------------|------------|------------|-----|
| FGS001 Name     | ✓ Datum    |     -      | ✓ Datum    | ... |
| FGS002 Name     | ✓ Datum    | ✓ Datum    |     -      | ... |
```

**Mitarbeiterspalte:**
- Format: `FGS{dienstnummer} {name}`
- Beispiel: "FGS042 Max Mustermann"
- Sortierbar

**Schulungsspalten:**
- Eine Spalte pro Schulung in Kategorie
- Breite: je 150px
- Zentriert ausgerichtet
- Zeigt Abschlussstatus

**Abschlussstatus:**
- **✓ (Häkchen)** - Schulung abgeschlossen
- **Datum** - Format TT.MM.JJJJ des Abschlusses
- **Dozent** - Name wird beim Hovern oder im Tooltip angezeigt
- **-** (Strich) - Schulung nicht zugewiesen/abgeschlossen

### Filterung

**Suche:**
- Filtert Mitarbeiter nach Name oder Dienstnummer
- Echtzeit-Filterung
- Groß-/Kleinschreibung wird nicht beachtet
- Löschbar

**Rangfilter:**
- Mehrfachauswahl-Dropdown
- Zeigt alle Ränge aus der Datenbank
- Filtert Mitarbeiter nach ausgewählten Rängen
- Alle löschen, um alle Ränge anzuzeigen

**Firmenfilter:**
- Mehrfachauswahl-Dropdown
- Zeigt alle Firmen mit Mitarbeiterzuweisungen
- Filtert Mitarbeiter nach Firmenzugehörigkeit
- Verwendet `employee_ids` Array aus Firmendatensätzen
- Alle löschen, um alle Firmen anzuzeigen

**Kombinierte Filterung:**
- Alle Filter arbeiten zusammen (UND-Logik)
- Leere Kategorieabschnitte werden ausgeblendet, wenn keine passenden Mitarbeiter vorhanden sind

## Schulung zuweisen

### Neue Zuweisungsdialog

1. Klicken Sie auf **Neue Zuweisung** Schaltfläche
2. Dialog öffnet sich mit Formular

**Formularfelder:**

**Schulungsauswahl*** (erforderlich)
- Mehrfachauswahl-Autovervollständigung
- Zeigt alle verfügbaren Schulungen
- Nach Kategorie gruppiert (falls zutreffend)
- Mehrere Schulungen können gleichzeitig zugewiesen werden

**Mitarbeiterauswahl*** (erforderlich)
- Mehrfachauswahl-Autovervollständigung
- Format: `FGS{dienstnummer} {name}`
- Kann mehreren Mitarbeitern gleichzeitig zuweisen
- Nach Organisation gefiltert

**Datum** (optional)
- Datumsauswahl
- Abschlussdatum
- Format: JJJJ-MM-TT (Eingabe), TT.MM.JJJJ (Anzeige)

**Dozent/Notizen** (optional)
- Textfeld
- Name des Dozenten oder zusätzliche Notizen

3. Klicken Sie auf **Speichern**
4. Schulungszuweisungen für alle ausgewählten Kombinationen erstellt
5. Matrix aktualisiert sich automatisch
6. Erfolgs-Toast-Benachrichtigung

**Zuweisungslogik:**
- Erstellt training_assign-Datensatz für jede Schulung x Mitarbeiter Kombination
- Wenn Schulung bereits für Mitarbeiter existiert, werden Datum/Dozent aktualisiert (falls angegeben)
- Mehrere Schulungen können in einer Aktion mehreren Mitarbeitern zugewiesen werden

## Schulungsplan-Generierung

### Schulungsplan-HTML erstellen

1. Klicken Sie auf **Schulungsplan** Schaltfläche
2. HTML-Tabelle wird automatisch generiert
3. HTML in Zwischenablage kopiert
4. Erfolgsbenachrichtigung wird angezeigt

**Generierter Inhalt:**

**Fest codierte Kategorien** (in spezifischer Reihenfolge):
1. Ground Training
2. Advanced Training
3. Engine Training
4. Ladder Training
5. Rescue Training

**Tabellenstruktur:**
```
| Kategoriename | Datum/Uhrzeit | Dozent | Module benötigt | Teilnahme | Mind. Teilnehmer | Bemerkungen | Ort |
```

**Spalte "Module benötigt":**
- Listet Mitarbeiter OHNE diese Schulung auf
- Format: FGS001, FGS002, FGS003
- Firmenfilterung angewendet für bestimmte Kategorien:
  - Engine Training: Filtert nach "Engine Company" Mitgliedern
  - Ladder Training: Filtert nach "Ladder Company" Mitgliedern
  - Rescue Training: Filtert nach "Rescue Company" Mitgliedern

**Statische Felder:**
- Bemerkungen: "Unterlagen durchlesen & verinnerlichen"
- Andere Spalten: Leer für manuelle Eingabe

**HTML-Format:**
- Gestylte HTML-Tabelle mit Rahmen
- Graue Überschriften (rgb(201,201,201))
- Fetter Text in Überschriften
- Feste Spaltenbreiten
- Bereit für E-Mail oder Dokumenteinfügung

**Verwendung:**
- In TiptapEditor einfügen (Nachrichten, Dokumente)
- Per E-Mail an Abteilungsleiter senden
- Für Besprechungsunterlagen drucken

## Admin-Konfiguration

### Zugriff auf Admin-Ansicht

**Route:** `/admin/trainings`

**Zwei Abschnitte:**
1. Schulungsverwaltung
2. Kategorieverwaltung

### Schulungsverwaltung

**Schulungstabelle:**

**Spalten:**
- ID
- Name
- Kategorie (Kurzname)
- Sortierreihenfolge
- Aktionen (Bearbeiten, Löschen)

**Neue Schulung:**
1. Klicken Sie auf **Neue Schulung** Schaltfläche
2. Formular ausfüllen:
   - **Name*** (erforderlich) - Schulungsname
   - **Kategorie*** (erforderlich) - Aus Kategorien auswählen
   - **Sortierreihenfolge*** (erforderlich) - Nummer für Reihenfolge
3. Klicken Sie auf **Speichern**
4. Schulung erscheint in Liste

**Schulung bearbeiten:**
1. Bearbeiten-Symbol anklicken
2. Felder ändern
3. Speichern
4. Matrix-Ansicht aktualisiert sich

**Schulung löschen:**
1. Löschen-Symbol anklicken
2. Löschen bestätigen
3. Schulung entfernt
4. Alle Schulungszuweisungen für diese Schulung verbleiben in der Datenbank (verwaist)

### Kategorieverwaltung

**Kategorietabelle:**

**Spalten:**
- ID
- Name
- Abkürzung (Kurz)
- Sortierreihenfolge
- Aktionen (Bearbeiten, Löschen)

**Neue Kategorie:**
1. Klicken Sie auf **Neue Kategorie** Schaltfläche
2. Formular ausfüllen:
   - **Name*** (erforderlich) - Vollständiger Kategoriename
   - **Abkürzung*** (erforderlich) - Kurzname (z.B. "GT" für Ground Training)
   - **Sortierreihenfolge*** (erforderlich) - Anzeigereihenfolge
3. Klicken Sie auf **Speichern**
4. Kategorie erscheint in Liste

**Kategorie bearbeiten:**
1. Bearbeiten-Symbol anklicken
2. Felder ändern
3. Speichern
4. Alle Schulungen in Kategorie aktualisiert

**Kategorie löschen:**
1. Löschen-Symbol anklicken
2. Löschen bestätigen
3. Kategorie entfernt
4. Schulungen in dieser Kategorie verlieren Kategoriezuweisung (catId wird ungültig)
5. Sowohl Kategorie- als auch Schulungslisten werden aktualisiert

**Sortierreihenfolge:**
- Kategorien sortieren nach `sort_order` aufsteigend
- Schulungen innerhalb der Kategorie sortieren ebenfalls nach `sort_order`
- Niedrigere Zahlen erscheinen zuerst

## Datenmodell

**Training:**
```typescript
interface Training {
  id: number;
  name: string;
  catId: number;
  cat_name: string; // Kategoriename (verknüpft)
  cat_short: string; // Kategorieabkürzung (verknüpft)
  sort_order: number;
  authority_id: number;
}
```

**TrainingCategory:**
```typescript
interface TrainingCategory {
  id: number;
  name: string;
  short: string; // Abkürzung
  sort_order: number;
  authority_id: number;
}
```

**TrainingAssign:**
```typescript
interface TrainingAssign {
  id: number;
  training_id: number;
  employeeid: number;
  date: string; // JJJJ-MM-TT HH:MM:SS oder JJJJ-MM-TT
  instructor: string;
  authority_id: number;
}
```

**Employee (für Matrix):**
```typescript
{
  id: number;
  name: string; // Formatiert als "FGS{dienstnummer} {name}"
  servicenumber: number;
  rank_id: number;
  rank_name: string;
  trainingDetails: {
    [trainingId: string]: {
      date: string;
      instructor: string;
    }
  };
}
```

## API-Integration

### Benutzeransicht-API

**Basispfad:** `/training/`

**Schulungen abrufen:**
```
GET /training/?action=getTrainings
Response: Training[]
```

**Schulungszuweisungen abrufen:**
```
GET /training/?action=getTrainingAssigns
Response: TrainingAssign[]
```

**Mitarbeiter abrufen:**
```
GET /training/?action=getEmployees
Response: Employee[] (mit Ranginformationen)
```

**Ränge abrufen:**
```
GET /training/?action=getRanks
Response: Rank[]
```

**Firmen abrufen:**
```
GET /training/?action=getCompanies
Response: Company[] (mit employee_ids)
```

**Schulungszuweisung speichern:**
```
POST /training/?action=saveTraining
Payload: {
  trainingIds: number[];
  employeeIds: number[];
  date: string;
  instructor: string;
}
```

### Admin-Ansicht-API

**Basispfad:** `/admin/training/`

**Schulungen abrufen:**
```
GET /admin/training?action=getTrainings
Response: Training[]
```

**Kategorien abrufen:**
```
GET /admin/training?action=getCategories
Response: TrainingCategory[]
```

**Schulung speichern:**
```
POST /admin/training?action=saveTrainings
Payload: {
  id?: number;
  name: string;
  catId: number;
  sort_order: number;
}
```

**Kategorie speichern:**
```
POST /admin/training?action=saveCategories
Payload: {
  id?: number;
  name: string;
  short: string;
  sort_order: number;
}
```

**Schulung löschen:**
```
POST /admin/training?action=deleteTrainings
Payload: { id: number }
```

**Kategorie löschen:**
```
POST /admin/training?action=deleteCategories
Payload: { id: number }
```

## Multi-Tenant-Sicherheit

**Organisationsisolation:**
- Alle Schulungen, Kategorien und Zuweisungen sind auf `authority_id` beschränkt
- Benutzer sehen nur Daten ihrer Organisation
- Backend filtert automatisch nach Organisation
- Organisationsübergreifender Zugriff verhindert

## Desktop-Fenstermodus

**Props-Unterstützung:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  id?: number | string; // Schulungs-ID (für zukünftige Hervorhebungsfunktion)
}
```

**Berechtigungsauflösung:**
- Prüft Props, route.meta und ALL_PERMISSIONS
- Admin-Ansicht standardmäßig true für canEdit/canDelete

## Einschränkungen

**Was NICHT verfügbar ist:**

### Schulungsfunktionen
- ❌ Test-/Quizsystem
- ❌ Schulungsmaterialien (Dokumente, Videos, Präsentationen)
- ❌ Voraussetzungen
- ❌ Schulungsdauer-Verfolgung
- ❌ Schulungsort
- ❌ Online-/Präsenz-Kennzeichnung
- ❌ Dozentenzuweisung (nur Notizenfeld)
- ❌ Schulungsprioritätsstufen
- ❌ Schulungsstatus-Workflow (nur abgeschlossen/nicht abgeschlossen)

### Zertifizierungsfunktionen
- ❌ Zertifizierungsverfolgung getrennt von Schulung
- ❌ Zertifikatsablauf/-erneuerung
- ❌ Zertifikats-Upload
- ❌ Zertifikatsgenerierung
- ❌ Rezertifizierungs-Erinnerungen
- ❌ Compliance-Verfolgung
- ❌ Zertifikatstypen

### Zuweisungsfunktionen
- ❌ Massenzuweisung nach Abteilung
- ❌ Fälligkeitsdaten für Schulungen
- ❌ Überfälligkeitsverfolgung
- ❌ Automatische Erinnerungen/Benachrichtigungen
- ❌ Wiederkehrende Schulungen (jährlich/halbjährlich)
- ❌ E-Mail-Benachrichtigungen
- ❌ Kalenderintegration
- ❌ Geplante Klassen/Sitzungen

### Anwesenheitsfunktionen
- ❌ Klassensitzungen
- ❌ Anwesenheitsverfolgung
- ❌ Anwesenheitslisten
- ❌ Anwesenheitsberichte
- ❌ Nichterscheinen-Verfolgung
- ❌ Nachholsitzungen

### Dozentenfunktionen
- ❌ Dozentenverwaltung
- ❌ Dozentenqualifikationen
- ❌ Dozentenplanung
- ❌ Dozentenzuweisungen

### Schulungsaufzeichnungen
- ❌ Schulungsprotokoll/Historie pro Mitarbeiter
- ❌ Berechnung der Gesamtschulungsstunden
- ❌ Testergebnisse
- ❌ Bestanden/Nicht bestanden-Verfolgung
- ❌ Schulungsnotizen/Kommentare
- ❌ Export als PDF

### Reporting & Analytics
- ❌ Abschlussraten
- ❌ Überfälligkeitsberichte
- ❌ Schulungsstundenberichte
- ❌ Compliance-Berichte
- ❌ Exportfunktionalität
- ❌ Schulungsstatistiken
- ❌ Trendanalyse

### Erweiterte Funktionen
- ❌ Schulungskalenderansicht
- ❌ Schulungsmaterialbibliothek
- ❌ Lernmanagementsystem (LMS)
- ❌ Kurskatalog
- ❌ Externe Schulungsverfolgung
- ❌ Schulungsbudgetverfolgung
- ❌ Anbieterverwaltung
- ❌ Schulungsbewertung/Feedback
- ❌ Kompetenzmatrix
- ❌ Kompetenzverfolgung

### UI-Einschränkungen
- ❌ Nur Matrix-Ansicht (keine Listenansicht)
- ❌ Kein Mitarbeiterdetail-Drilldown
- ❌ Keine Schulungsdetailansicht
- ❌ Eingeschränkte Sortierung (nur nach Mitarbeitername)
- ❌ Kein Export aus Matrix
- ❌ Keine druckoptimierte Ansicht
- ❌ Schulungsplan fest codiert auf 5 Kategorien

**Aktuelle Realität:**
Dies ist ein **Schulungszuweisungs-Tracker** mit Matrix-Ansicht, die zeigt, wer welche Schulungen abgeschlossen hat. Es ist kein umfassendes Learning Management System (LMS), Zertifizierungs-Tracker oder Schulungsverwaltungsplattform.

## Anwendungsfälle

**Schulungsverfolgung:**
- Verfolgen, welche Mitarbeiter welche Schulungen abgeschlossen haben
- Visuelle Matrix des Schulungsabschlussstatus
- Schnelle Identifizierung von Schulungslücken

**Schulungszuweisung:**
- Mehrere Schulungen mehreren Mitarbeitern zuweisen
- Abschlussdaten und Dozenten erfassen
- Massenzuweisungs-Workflows

**Planung:**
- Schulungsplan-HTML für fehlende Schulungen generieren
- Firmenspezifische Filterung für spezialisierte Schulungen
- Mitarbeiter identifizieren, die bestimmte Schulungen benötigen

**Organisation:**
- Schulungen nach Typ kategorisieren
- Schulungen und Kategorien logisch ordnen
- Nach Rang und Firma filtern

## Best Practices

**Einrichtung:**
1. Erstellen Sie zuerst Kategorien in der Admin-Ansicht
2. Fügen Sie Schulungen zu Kategorien mit klaren Namen hinzu
3. Verwenden Sie sort_order für logische Gruppierung (10, 20, 30, usw.)
4. Halten Sie Kategorieabkürzungen kurz (2-3 Zeichen)

**Schulungen zuweisen:**
1. Verwenden Sie Massenzuweisung für Effizienz
2. Erfassen Sie Daten, wenn verfügbar
3. Fügen Sie Dozentennamen im Notizenfeld hinzu
4. Weisen Sie verwandte Schulungen zusammen zu

**Matrix-Ansicht verwenden:**
1. Filtern nach Firma für spezialisierte Schulungen
2. Verwenden Sie Suche, um bestimmte Mitarbeiter zu finden
3. Klappen Sie ungenutzte Kategorien zusammen
4. Generieren Sie Schulungspläne regelmäßig

**Schulungspläne:**
1. Überprüfen Sie generierten Plan vor der Verteilung
2. Aktualisieren Sie Firmenmitgliedschaften für genaue Filterung
3. Verwenden für Planung von Sitzungen/Kursen
4. Teilen mit Abteilungsleitern

## Fehlerbehebung

### Schulung kann nicht zugewiesen werden

**Problem:** Neue Zuweisungs-Schaltfläche fehlt oder funktioniert nicht

**Lösungen:**
1. Prüfen Sie `WRITE_TRAINING` Berechtigung
2. Verifizieren Sie, dass Funktion für Organisation aktiviert ist
3. Seite aktualisieren
4. Browser-Konsole auf Fehler prüfen

### Schulung wird nicht gespeichert

**Problem:** Zuweisung wird nicht dauerhaft gespeichert

**Lösungen:**
1. Prüfen Sie, ob alle Pflichtfelder ausgefüllt sind (Schulungen, Mitarbeiter)
2. Internetverbindung überprüfen
3. Browser-Konsole auf API-Fehler prüfen
4. Sicherstellen, dass Schulungen/Mitarbeiter existieren
5. Verifizieren, dass authority_id übereinstimmt

### Matrix lädt nicht

**Problem:** Leere Matrix oder Lade-Spinner

**Lösungen:**
1. Prüfen Sie `READ_TRAINING` Berechtigung
2. Verifizieren Sie, dass Backend läuft
3. Browser-Konsole auf API-Fehler prüfen
4. Sicherstellen, dass Schulungen und Kategorien existieren
5. Prüfen Sie, ob Mitarbeiter im System existieren

### Schulungsplan-Kopie schlägt fehl

**Problem:** Zwischenablage-Kopie funktioniert nicht

**Lösungen:**
1. Browser-Zwischenablage-Berechtigungen prüfen
2. Erneut versuchen
3. Browser-Konsole auf Fehler prüfen
4. Verifizieren, dass Kategorien/Schulungen existieren

### Kategorie/Schulung löscht nicht

**Problem:** Löschen schlägt in Admin-Ansicht fehl

**Lösungen:**
1. Berechtigungen prüfen
2. Kategorie löschen kann Schulungen beeinflussen (catId wird ungültig)
3. Erwägen Sie stattdessen zu bearbeiten
4. Auf Fehlermeldungen prüfen

### Filter funktionieren nicht

**Problem:** Rang-/Firmenfilter filtern nicht

**Lösungen:**
1. Verifizieren Sie rank_id bei Mitarbeitern
2. Prüfen Sie, ob company employee_ids Array befüllt ist
3. Filter löschen und erneut versuchen
4. Seite aktualisieren

## Verwandte Dokumentation

- [👥 Mitarbeiterverwaltung](/guide/employee-management) - Mitarbeiter verwalten
- [🏢 Firmen](/guide/companies) - Firmenverwaltung
- [⚙️ Admin](/guide/admin/trainings) - Schulungskonfiguration

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend der tatsächlichen Implementierung)
