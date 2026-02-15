# Berichtverwaltung

Dynamisches Berichtssystem mit benutzerdefinierten Feldern, Kategorien, Status-Workflow, Freigabefunktionen und Filterung für strukturierte Dokumentation und Nachverfolgung.

## Überblick

Funktionen:
- Dynamische Berichtserstellung mit benutzerdefinierten Feldern
- Berichtskategorien zur Organisation
- Berichtscodes zur Klassifizierung
- Status-Workflow-Verwaltung
- Personen- und Firmenzuordnungen
- Benutzerdefinierte Felddefinitionen pro Authority
- Berichtsfreigabe mit Zugriffsebenen
- Erweiterte Filterung (6 Filtertypen + benutzerdefinierte Felder)
- Tab-basierte Ansichten (alle, mit mir geteilt, von mir geteilt)
- Wichtige Berichte anheften/lösen
- PDF-Download-Funktionalität
- Echtzeit-Suche über mehrere Felder
- Authority-basierte Datenisolation

**Erforderliche Berechtigungen:**
- `READ_REPORT` (Berichte anzeigen)
- `WRITE_REPORT` (Berichte erstellen/bearbeiten)
- `DELETE_REPORT` (Berichte löschen)
- `SHARE_REPORT` (Berichte mit anderen teilen)

## Zugriff auf Berichte

**Desktop:** Doppelklick auf **Berichte**-Symbol
**Menü:** Startmenü → Berichte & Dokumente → Berichte
**Route:** `/report`

## Interface-Layout

### Kopfzeilenbereich

**Titel:** "Berichte" mit Datei-Dokument-Symbol

**Aktionsschaltfläche:**
- **Neuer Bericht**-Schaltfläche (bei WRITE-Berechtigung)
- Öffnet Kategorieauswahldialog

### Filterkarte

**Filtertyp-Schaltflächen** (6 Typen mit Zählungen):
- **Alle** - Alle Berichte
- **Eigene** - Von Ihnen erstellte Berichte
- **Fehlend** - Berichte, die Ihre Eingabe benötigen
- **Unvollständig** - Berichte mit fehlenden Mitarbeiterdaten
- **Offen** - Nicht genehmigte Berichte
- **Ausstehend** - Vollständige, aber nicht genehmigte Berichte

**Suchfeld:**
- Durchsucht Titel, Standort und Berichtscode
- Echtzeit-Filterung
- Löschbar

**Dropdown-Filter:**
- **Kategorie** - Nach Berichtskategorie filtern
- **Ersteller** - Nach Mitarbeiter filtern, der den Bericht erstellt hat

**Benutzerdefinierte Felder-Filter** (Erweiterungspanel):
- Dynamische Filter basierend auf benutzerdefinierten Feldern der Authority
- Zeigt Anzahl verfügbarer benutzerdefinierter Felder
- Unterstützt Text, Nummer, Datum, Boolean, Select, Multiselect
- Jeder Feldtyp hat entsprechendes Eingabe-Widget

### Tab-Navigation

**Vier Tabs:**
1. **Alle Berichte** - Berichte Ihrer Organisation
2. **Mit mir geteilt** - Berichte, die andere Authorities mit Ihnen geteilt haben
3. **Von mir geteilt** - Berichte, die Sie mit anderen Authorities geteilt haben
4. **Alle geteilten** - Kombinierte Ansicht aller Freigabeaktivitäten

### Berichtstabelle

**Spalten:**
- **Status-Symbol** - Genehmigt (grüner Haken) / Offen (orangefarbene Warnung)
- **Berichtinfo** - Titel mit Code, optionales Anheft-Symbol
- **Kategorie** - Berichtskategoriename
- **Status** - Workflow-Statusname
- **Ersteller** - Mitarbeiter, der den Bericht erstellt hat
- **Erstellt** - Erstellungsdatum (TT.MM.JJJJ)
- **Aktualisiert** - Datum der letzten Änderung (TT.MM.JJJJ)
- **Aktionen** - Schaltflächen: Bearbeiten, Teilen, Löschen, Herunterladen, Anheften

**Zeilenfunktionen:**
- Hover-Effekt
- Angeheftete Berichte zeigen blaues Anheft-Symbol
- Fehlende Mitarbeiter zeigen orangefarbenes Warnsymbol mit Tooltip
- Freigabeindikatoren:
  - Blauer Pfeil nach unten-links: Mit Ihnen geteilt
  - Grüner Pfeil nach oben-rechts: Von Ihnen geteilt

**Paginierung:**
- 25 Berichte pro Seite
- Seitennavigation unten

**Sortierung:**
- Klicken Sie auf eine sortierbare Spaltenüberschrift zum Sortieren
- Genehmigungsstatus, Titel, Kategorie, Status, Ersteller, Daten alle sortierbar

## Berichte erstellen

### Schritt 1: Kategorie auswählen

1. Klicken Sie auf **Neuer Bericht**-Schaltfläche
2. **Kategorieauswahldialog** öffnet sich
3. Wählen Sie Berichtskategorie aus Dropdown
4. Klicken Sie auf **Fortfahren**

**Kategorien:**
- Vom Administrator in der Kategorieverwaltung definiert
- Jede Authority kann eigene Kategorien haben
- Beispiele: Vorfallsbericht, Inspektionsbericht, etc.

### Schritt 2: Berichtsformular ausfüllen

Dynamisches Formular öffnet sich basierend auf ausgewählter Kategorie und Authority-Typ.

**Formular bereitgestellt von:** `AuthorityAddReportDialog`-Komponente (asynchron geladen)

**Allgemeine Felder** (an Komponente übergeben):
- Kategorienliste
- Mitarbeiterliste
- Personenliste
- Firmenliste
- Berichtscodesliste
- Berichtsstatusliste
- Aktueller Authority-Kontext

**Formularverhalten:**
- Felder variieren basierend auf Konfiguration benutzerdefinierter Felder
- Pflichtfelder markiert
- Validierung vor dem Speichern
- Automatisches Speichern in Datenbank über API

3. Füllen Sie alle Pflichtfelder aus
4. Klicken Sie auf **Speichern**
5. Bericht wird erstellt und erscheint in der Tabelle

## Berichte bearbeiten

### Eigene Berichte bearbeiten

1. Klicken Sie auf **Bearbeiten**-Schaltfläche (Datei-Dokument-Bearbeiten-Symbol) in der Berichtszeile
2. Bearbeitungsdialog öffnet sich mit aktuellen Daten
3. Ändern Sie beliebige Felder
4. Klicken Sie auf **Speichern** zum Aktualisieren
5. Bericht wird in Tabelle aktualisiert

**Bearbeitungsformular:**
- Gleiche Komponente wie Hinzufügen: `AuthorityEditReportDialog`
- Vorausgefüllt mit aktuellen Berichtsdaten
- Alle Felder bearbeitbar (abhängig von Konfiguration benutzerdefinierter Felder)

### Geteilte Berichte anzeigen (Nur-Lesen)

1. Im Tab "Mit mir geteilt" oder "Alle geteilten"
2. Klicken Sie auf **Bearbeiten**-Schaltfläche (Datei-Dokument-Bearbeiten-Symbol)
3. **Geteilter Bericht Ansicht**-Dialog öffnet sich (Vollbild)
4. Nur-Lesen-Anzeige zeigt:
   - Vollständige Berichtsdetails
   - Quell-Authority-Informationen
   - Freigabe-Metadaten (shared_at, access_level)
   - Erstellerdetails
   - Alle benutzerdefinierten Feldwerte

**Zugriffsebenen:**
- `read` - Nur-Lesen-Zugriff (am häufigsten für geteilte Berichte)
- `edit` - Bearbeitungszugriff (wenn explizit gewährt)

## Berichte löschen

1. Klicken Sie auf **Löschen**-Schaltfläche (rotes Papierkorb-Symbol) in der Berichtszeile
2. Bestätigungsdialog erscheint
3. Zeigt Berichtstitel in Warnung
4. Klicken Sie auf **Löschen** zum Bestätigen
5. Oder **Abbrechen** zum Abbrechen
6. Bericht wird aus der Datenbank entfernt

**Warnung:** Löschung ist permanent.

## Berichte teilen

### Einen Bericht teilen

1. Klicken Sie auf **Teilen**-Schaltfläche (share-variant-Symbol) in der Berichtszeile
2. **Berichtsfreigabe-Dialog** öffnet sich
3. Konfigurieren Sie Freigabeeinstellungen:
   - Wählen Sie Empfänger-Authorities
   - Legen Sie Zugriffsebene fest (read/edit)
   - Optionale Ablaufeinstellungen
4. Klicken Sie auf **Teilen**
5. Bericht erscheint im "Mit mir geteilt"-Tab des Empfängers

**Anforderungen:**
- SHARE_REPORT oder ALL_PERMISSIONS-Berechtigung
- WRITE_REPORT-Berechtigung (canEdit)

**Freigabeindikatoren:**
- Grünes Pfeil-nach-oben-rechts-Symbol in "Von mir geteilt" oder "Alle geteilten" Tabs
- Tooltip zeigt Empfängernamen/Authorities

### Geteilte Berichte anzeigen

**Mit mir geteilt Tab:**
- Zeigt Berichte, die andere Authorities mit Ihnen geteilt haben
- Blaues Pfeil-nach-unten-links-Symbol
- Tooltip zeigt Quell-Authority
- Klicken zum Anzeigen im Nur-Lesen-Modus

**Von mir geteilt Tab:**
- Zeigt Berichte, die Sie mit anderen geteilt haben
- Grünes Pfeil-nach-oben-rechts-Symbol
- Tooltip zeigt Empfänger
- Vollständiger Bearbeitungszugriff beibehalten

**Alle geteilten Tab:**
- Kombinierte Ansicht von eingehenden und ausgehenden Freigaben
- Freigabetext-Spalte erklärt Richtung
- Gemischte Symbole basierend auf Freigabetyp

## Berichtsfunktionen

### Berichte anheften/lösen

**Wichtige Berichte anheften:**
1. Klicken Sie auf **Anheften**-Schaltfläche (Stecknadel-Symbol) in der Berichtszeile
2. Bericht wird als angeheftet markiert
3. Blaues Stecknadel-Symbol erscheint in Berichtinfo-Spalte
4. Angehefteter Status bleibt bestehen

**Lösen:**
1. Klicken Sie auf **Lösen**-Schaltfläche (pin-off-Symbol)
2. Stecknadel entfernt

**Anwendungsfall:** Häufig aufgerufene Berichte leicht identifizierbar halten

### PDF herunterladen

1. Klicken Sie auf **Herunterladen**-Schaltfläche (Download-Symbol) in der Berichtszeile
2. System generiert PDF (API-Aufruf mit Blob-Antwort)
3. Ladespinner zeigt während der Generierung
4. PDF wird heruntergeladen mit Dateinamenformat: `report_{id}_{title}.pdf`

**API-Endpunkt:** `GET /report/?action=getReportPdf&report_id={id}`

### Fehlende Mitarbeiter

**Was es bedeutet:**
- Einige Berichte erfordern Eingabe von mehreren Mitarbeitern
- `missing_employees`-Array verfolgt, wer noch keine Eingabe gemacht hat

**Visueller Indikator:**
- Orangefarbenes Konto-Warnsymbol in Statusspalte
- Tooltip zeigt Namen fehlender Mitarbeiter
- Format: "[Dienstnummer] Name"

**Nach Fehlend filtern:**
- Klicken Sie auf **Fehlend**-Filterschaltfläche
- Zeigt nur Berichte, bei denen SIE im missing_employees-Array sind

## Berichte filtern

### Filtertyp-Schaltflächen

**Alle (Anzahl):**
- Zeigt alle Berichte
- Zahl zeigt Gesamtanzahl der Berichte

**Eigene (Anzahl):**
- Zeigt nur von Ihnen erstellte Berichte (creator === currentUserId)
- Zahl zeigt Ihre Berichtsanzahl

**Fehlend (Anzahl):**
- Zeigt Berichte, die IHRE Eingabe benötigen (Sie in missing_employees)
- Zahl zeigt, wie viele Berichte Sie benötigen

**Unvollständig (Anzahl):**
- Zeigt Berichte mit IRGENDWELCHEN fehlenden Mitarbeiterdaten
- Zahl zeigt Anzahl unvollständiger Berichte

**Offen (Anzahl):**
- Zeigt nicht genehmigte Berichte (approved === false)
- Zahl zeigt Anzahl nicht genehmigter Berichte

**Ausstehend (Anzahl):**
- Zeigt vollständige, aber nicht genehmigte Berichte
- Muss haben: keine fehlenden Mitarbeiter UND nicht genehmigt
- Zahl zeigt Anzahl auf Genehmigung wartender Berichte

### Textsuche

**Durchsucht:**
- Berichtstitel
- Standortfeld
- Berichtscode-ID

**Verhalten:**
- Groß-/Kleinschreibung unabhängig
- Teilübereinstimmung (enthält)
- Echtzeit-Filterung während der Eingabe
- Lösch-Schaltfläche zum Zurücksetzen

### Dropdown-Filter

**Kategoriefilter:**
- Wählen Sie bestimmte Kategorie
- Zeigt nur Berichte in dieser Kategorie
- Exakte ID-Übereinstimmung

**Erstellerfilter:**
- Wählen Sie bestimmten Mitarbeiter
- Zeigt nur von diesem Mitarbeiter erstellte Berichte
- Dropdown zeigt: "[Dienstnummer] Name"

### Benutzerdefinierte Feldfilter

**Wenn benutzerdefinierte Felder existieren:**
- Erweiterungspanel erscheint in Filterkarte
- Zeigt Anzahl: "Benutzerdefinierte Felder (X)"
- Erweitern, um alle verfügbaren Filter zu sehen

**Filtertypen nach Feldtyp:**

**Text/Textarea:**
- Texteingabefeld
- Groß-/Kleinschreibung unabhängige Contains-Suche

**Nummer:**
- Zahleneingabefeld
- Exakte Übereinstimmung

**Datum:**
- Datumsauswahl
- Exakte Datumsübereinstimmung

**Boolean:**
- Ja/Nein-Dropdown
- Exakte wahr/falsch-Übereinstimmung

**Select (einzeln):**
- Dropdown mit Optionen
- Exakte Wertübereinstimmung

**Multiselect:**
- Multi-Select mit Chips
- Übereinstimmung, wenn IRGENDEIN ausgewählter Wert vorhanden

**Kombinierte Filterung:**
- Alle Filter arbeiten zusammen (UND-Logik)
- Nur Berichte, die ALLE aktiven Filter erfüllen, werden angezeigt
- Leere/Null-Filter werden ignoriert

## Datenmodell

**Bericht (Basis):**
```typescript
interface Report {
  id: number;
  title: string;
  description?: string;
  text?: string; // Berichtsinhalt
  category_id: number;
  cat_name?: string; // Kategoriename (verknüpft)
  report_code_id?: number;
  code_name?: string; // Codename (verknüpft)
  status_id?: number;
  status_name?: string; // Statusname (verknüpft)
  creator: number; // Mitarbeiter-ID
  emp_name?: string; // Erstellername (verknüpft)
  created_at: string; // JJJJ-MM-TT HH:MM:SS
  updated_at: string; // JJJJ-MM-TT HH:MM:SS
  approved: boolean; // Genehmigungsstatus
  pinned: boolean; // Anheft-Status
  location?: string;
  custom_fields: string | object; // JSON-String oder Objekt
  missing_employees?: number[]; // Mitarbeiter-IDs, die Eingabe benötigen
  authority_id: number;
}
```

**Kategorie:**
```typescript
interface Category {
  id: number;
  name: string;
  authority_id: number;
}
```

**BerichtsCode:**
```typescript
interface ReportCode {
  id: number;
  code: string;
  name: string;
  authority_id: number;
}
```

**BerichtsStatus:**
```typescript
interface ReportStatus {
  id: number;
  name: string;
  color?: string;
  authority_id: number;
}
```

**Mitarbeiter (für Ersteller):**
```typescript
interface Employee {
  id: number;
  name: string;
  servicenumber: number;
  display_name: string; // "[Dienstnummer] Name"
}
```

## API-Integration

**Basispfad:** `/report/`

**Alle Berichte abrufen:**
```
GET /report/?action=getReports
Response: Report[]
```

**Kategorien abrufen:**
```
GET /report/?action=getCategories
Response: Category[]
```

**Mitarbeiter abrufen:**
```
GET /report/?action=getEmployees
Response: Employee[]
```

**Berichtscodes abrufen:**
```
GET /report/?action=getCodes
Response: ReportCode[]
```

**Berichtsstatus abrufen:**
```
GET /report/?action=getStatuses
Response: ReportStatus[]
```

**Personen abrufen:**
```
GET /personfile/?action=getPersons
Response: PersonFile[]
```

**Firmen abrufen:**
```
GET /company/?action=getOnlyCompanies
Response: Company[]
```

**Benutzerdefinierte Felder abrufen:**
```
GET /admin/report_fields.php?action=getReportFields
Response: CustomField[]
```

**Bericht löschen:**
```
POST /report/?action=deleteReport
Payload: { id: number }
```

**Bericht anheften/lösen:**
```
POST /report/?action=pinReport
Payload: { id: number, pinned: boolean }
```

**PDF herunterladen:**
```
GET /report/?action=getReportPdf&report_id={id}
Response: Blob (PDF-Datei)
```

**Zugriff protokollieren:**
```
POST /logging/?action=logAccess
Payload: {
  report_id: number,
  action: string
}
```

**Freigabe-API (über ReportService):**
- `ReportService.getSharedReports()` - Berichte abrufen, die mit/von Ihnen geteilt wurden
- `ReportService.getReportSharing(reportId)` - Freigabeinfo für Bericht abrufen
- `ReportService.getSharedReport(reportId)` - Vollständige geteilte Berichtsdetails abrufen

## Admin-Konfiguration

**Admin-Routen** (zugänglich über Start → Administration → Berichte):

**Benutzerdefinierte Felder:** `/admin/reportfields`
- Benutzerdefinierte Feldtypen definieren
- Pro Authority konfigurieren
- Feldeigenschaften festlegen (Name, Typ, erforderlich, etc.)

**Kategorien:** Route unbekannt (über Hauptinterface verwaltet)
- Berichtskategorien erstellen
- Berichte nach Typ organisieren

**Berichtsstatus:** Route unbekannt
- Workflow-Status konfigurieren
- Statusfarben und Namen festlegen

**Berichtscodes:** Route unbekannt
- Berichtsklassifizierungscodes organisieren
- Codes Berichten zuweisen

## Multi-Tenant-Sicherheit

**Authority-Isolation:**
- Alle Berichte auf `authority_id` beschränkt
- Alle Kategorien auf `authority_id` beschränkt
- Alle benutzerdefinierten Felder auf `authority_id` beschränkt
- Benutzer sehen nur Daten aus ihrer Organisation
- Backend filtert automatisch nach Authority
- Freigabe erlaubt Authority-übergreifenden Zugriff (mit Nachverfolgung)

## Desktop-Fenstermodus

**Props-Unterstützung:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
  id?: string | number; // Berichts-ID zum automatischen Öffnen
}
```

**Auto-Öffnen-Funktion:**
- Übergeben Sie `id`-Prop oder `?id=X`-Query-Parameter
- Beim Mounten öffnet sich automatisch Bearbeitungs-/Ansichtsdialog für diesen Bericht
- Nützlich für Benachrichtigungen/Links

## Einschränkungen

**Was NICHT verfügbar ist:**

### Berichtsfunktionen
- ❌ Keine Berichtsvorlagen in UI (nur Hinzufügen/Bearbeiten-Dialoge)
- ❌ Keine automatische Entwurfsspeicherung
- ❌ Keine Berichtsversionierung/Historie
- ❌ Kein Berichtskommentar-/Notizenbereich
- ❌ Keine Dateianhänge zu Berichten (verwenden Sie File Manager separat)
- ❌ Kein Bild-Upload zu Berichten
- ❌ Kein Berichte klonen/duplizieren
- ❌ Keine Massenoperationen (Mehrfachauswahl)
- ❌ Kein Berichtsexport außer PDF

### Workflow-Funktionen
- ❌ Kein mehrstufiger Genehmigungs-Workflow
- ❌ Keine Genehmigungs-Routing/Zuweisung
- ❌ Keine Workflow-Stufen über Status hinaus
- ❌ Keine automatisierten Statusübergänge
- ❌ Keine Workflow-Benachrichtigungen

### Berichterstattung & Analysen
- ❌ Kein Berichtsstatistik-Dashboard
- ❌ Keine Analysen zu Berichtsdaten
- ❌ Keine Diagramme/Grafiken von Berichtstrends
- ❌ Kein Export nach Excel/CSV aus Tabelle
- ❌ Keine Berichtsgruppierung/Aggregation
- ❌ Keine geplante Berichtsgenerierung

### Erweiterte Filterung
- ❌ Keine Datumsbereichsfilter (erstellt/aktualisiert)
- ❌ Keine gespeicherten Filter-Presets
- ❌ Kein erweiterter Such-Builder
- ❌ Kein Filter nach Personen-/Firmenzuordnungen
- ❌ Kein Filter nach Freigabestatus

### Freigabefunktionen
- ❌ Keine Ablaufdaten für Freigaben
- ❌ Keine Freigabelink-Generierung (nur direkte Freigabe)
- ❌ Keine öffentliche/anonyme Freigabe
- ❌ Keine Freigabebenachrichtigungen
- ❌ Keine Freigabewiderruf-UI (muss Freigabedialog verwenden)
- ❌ Kein Audit-Log von Freigabeänderungen

### UI-Einschränkungen
- ❌ Keine Listenansicht (nur Tabelle)
- ❌ Kein Detail-Vorschaufenster
- ❌ Keine Vollbild-Berichtsansicht (außer geteilte Berichte)
- ❌ Keine Druckansichts-Optimierung
- ❌ Keine Spaltensichtbarkeits-Anpassung
- ❌ Keine Spaltenumordnung
- ❌ Keine Inline-Bearbeitung

**Aktuelle Realität:**
Dies ist ein **dynamisches Berichtsverwaltungssystem** mit Unterstützung für benutzerdefinierte Felder, Filterung, Freigabe und Status-Workflow. Es ist keine umfassende Berichtsplattform, kein Business-Intelligence-Tool oder Dokumentenverwaltungssystem mit Versionierung.

## Anwendungsfälle

**Strukturierte Berichterstattung:**
- Vorfallsberichte mit benutzerdefinierten Feldern erstellen
- Inspektionsberichte nach Kategorie verfolgen
- Berichte mit Codes und Status organisieren
- Datenerfassung pro Authority standardisieren

**Zusammenarbeit:**
- Berichte über Authorities hinweg teilen
- Verfolgen, wer Eingabe leisten muss
- Genehmigungs-Workflows über Status verwalten
- PDFs zur Verteilung herunterladen

**Organisation:**
- Nach mehreren Kriterien filtern
- Wichtige Berichte anheften
- Berichte durchsuchen
- Tab-basierte Ansichten für verschiedene Perspektiven

## Best Practices

**Berichte erstellen:**
1. Wählen Sie zuerst passende Kategorie
2. Füllen Sie alle erforderlichen benutzerdefinierten Felder aus
3. Ordnen Sie relevante Personen/Firmen zu
4. Wählen Sie korrekten Berichtscode
5. Legen Sie initialen Status fest

**Filter verwenden:**
1. Beginnen Sie mit Filtertyp-Schaltflächen für Schnellansichten
2. Kombinieren Sie mit Suche für spezifische Berichte
3. Verwenden Sie benutzerdefinierte Feldfilter für detaillierte Suchen
4. Löschen Sie Filter beim Aufgabenwechsel

**Berichte teilen:**
1. Teilen Sie nur vollständige Berichte
2. Legen Sie passende Zugriffsebene fest (normalerweise read)
3. Verfolgen Sie Freigabe im "Von mir geteilt"-Tab
4. Überprüfen Sie geteilte Berichte regelmäßig

**Datenqualität:**
1. Verwenden Sie konsistente Berichtscodes
2. Aktualisieren Sie Status während der Bearbeitung
3. Stellen Sie sicher, dass erforderliche Mitarbeiter Eingabe vervollständigen
4. Heften Sie kritische Berichte für schnellen Zugriff an

## Fehlerbehebung

### Kann keinen Bericht erstellen

**Problem:** Neuer Bericht-Schaltfläche fehlt oder funktioniert nicht

**Lösungen:**
1. Prüfen Sie WRITE_REPORT-Berechtigung
2. Überprüfen Sie, ob Kategorien im System existieren
3. Stellen Sie sicher, dass benutzerdefinierte Felder konfiguriert sind (falls erforderlich)
4. Seite aktualisieren

### Bericht wird nicht gespeichert

**Problem:** Speichern bleibt im Hinzufügen/Bearbeiten-Dialog nicht bestehen

**Lösungen:**
1. Prüfen Sie, ob alle erforderlichen benutzerdefinierten Felder ausgefüllt sind
2. Überprüfen Sie Internetverbindung
3. Prüfen Sie Browser-Konsole auf API-Fehler
4. Stellen Sie sicher, dass alle Dropdown-Daten geladen sind (Kategorien, Codes, Status)

### Filter funktionieren nicht

**Problem:** Filter zeigen keine erwarteten Berichte

**Lösungen:**
1. Prüfen Sie, ob mehrere Filter nicht in Konflikt stehen
2. Löschen Sie Suchfeld
3. Setzen Sie benutzerdefinierte Feldfilter zurück
4. Überprüfen Sie Filtertyp-Schaltfläche-Auswahl (alle/eigene/etc.)
5. Seite aktualisieren, um Daten neu zu laden

### Kann Bericht nicht teilen

**Problem:** Teilen-Schaltfläche fehlt oder funktioniert nicht

**Lösungen:**
1. Prüfen Sie SHARE_REPORT oder ALL_PERMISSIONS-Berechtigung
2. Überprüfen Sie WRITE_REPORT-Berechtigung (canEdit erforderlich)
3. Prüfen Sie, ob Freigabedialog lädt
4. Überprüfen Sie Browser-Konsole auf Fehler

### PDF-Download schlägt fehl

**Problem:** PDF wird nicht heruntergeladen oder zeigt Fehler

**Lösungen:**
1. Prüfen Sie, ob Bericht Inhalt hat
2. Überprüfen Sie, ob Backend-PDF-Generierung funktioniert
3. Prüfen Sie Browser-Konsole auf API-Fehler
4. Versuchen Sie anderen Browser
5. Prüfen Sie Popup-Blocker-Einstellungen

### Fehlende Mitarbeiter werden nicht aktualisiert

**Problem:** Fehlende Mitarbeiter-Indikator ist falsch

**Lösungen:**
1. Überprüfen Sie, ob Mitarbeiter im Bericht als vollständig markiert ist
2. Prüfen Sie, ob Backend missing_employees-Array aktualisiert
3. Seite aktualisieren, um Berichtsdaten neu zu laden
4. Überprüfen Sie Berichtsbearbeitungsformular auf Vollständigkeitsstatus

## Verwandte Dokumentation

- [📄 Dokumente](/guide/documents) - Dokumentenverwaltung
- [👥 Personenakten](/guide/person-files) - Personenzuordnungen
- [🏢 Firmen](/guide/companies) - Firmenzuordnungen

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend tatsächlicher Implementierung)
