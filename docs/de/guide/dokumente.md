# Dokumente

Dokumentenverwaltungssystem zur Organisation von Rich-Text-Dokumenten in Kategorien mit Drag-and-Drop-Sortierung und bereichsbasierten Berechtigungen.

## Überblick

Funktionen:
- Rich-Text-Dokumenterstellung mit Formatierungswerkzeugleiste
- Kategoriebasierte Organisation
- Drag-and-Drop-Sortierung (Kategorien und Dokumente)
- Dokumentenbereiche mit rollenbasierten Berechtigungen
- Raster- und Listenansichtsmodi
- Grundlegende Suche nach Titel
- Multi-Tenant-Unterstützung

**Erforderliche Berechtigung:** `READ_DOCUMENT` (anzeigen), `WRITE_DOCUMENT` (erstellen/bearbeiten), `DELETE_DOCUMENT` (löschen)

## Zugriff auf Dokumente

**Route:** `/document`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Dokumente**-Symbol
- Menü: Startmenü → Dokumente
- Oder direkt zu `/document` navigieren

**Bereichsbasierter Zugriff:**
- Verschiedene Dokumentenbereiche können unterschiedliche Routen haben
- Zugriff wird durch Bereichsberechtigungen gesteuert (lesen, schreiben, löschen)
- Authority-spezifische Dokumentenisolation

## Interface-Layout

### Kopfzeile
- Titel mit Dokumentensymbol
- Suchfeld (Suche nach Titel)
- Ansichtsmodus-Umschalter (Raster/Liste)

### Kategorienbereich
- Erweiterbare Kategoriekarten
- Dokumentenzahl pro Kategorie
- Drag-and-Drop zum Umordnen von Kategorien
- Klicken zum Erweitern/Reduzieren von Dokumenten

### Dokumentenbereich
- Dokumentenkarten zeigen:
  - Titel
  - Erstellername
  - Erstellungsdatum
  - Datum der letzten Aktualisierung
- Hover zum Anzeigen von Bearbeiten/Löschen-Aktionen
- Klicken Sie auf Dokument zum Anzeigen/Bearbeiten

### Aktionsschaltflächen
- **Kategorie erstellen** - Neue Kategorie hinzufügen (bei Schreibberechtigung)
- **Kategorien sortieren** - Drag-and-Drop-Interface
- **Dokumente sortieren** - Drag-and-Drop-Interface innerhalb der Kategorie

## Dokumente erstellen

### Neues Dokument

1. Klicken Sie auf **Dokument erstellen**-Schaltfläche (+-Symbol)
2. Dokumenteneditor-Modal öffnet sich
3. Füllen Sie Felder aus:
   - **Titel** (erforderlich): Dokumentenname
   - **Kategorie** (erforderlich): Aus Dropdown auswählen
   - **Inhalt**: Rich-Text-Inhalt mit Formatierungswerkzeugleiste
   - **Notizen** (optional): Interne Notizen zum Dokument
4. Klicken Sie auf **Speichern**
5. Dokument erscheint in ausgewählter Kategorie

**Rich-Text-Editor-Funktionen:**
- Fett, kursiv, unterstrichen
- Überschriften (H1, H2, H3)
- Aufzählungslisten und nummerierte Listen
- Tabellen
- Links
- Bilder
- Textausrichtung
- Codeblöcke

**Hinweis:** Dokumente sind nur Rich-Text-Inhalte, keine Datei-Uploads. Zum Anhängen von Dateien verwenden Sie den File Manager.

## Kategorien erstellen

### Neue Kategorie

1. Klicken Sie auf **Kategorie erstellen**-Schaltfläche (Ordner+-Symbol)
2. Dialog öffnet sich
3. Geben Sie Kategorienamen ein
4. Klicken Sie auf **Erstellen**
5. Kategorie erscheint in Liste

**Kategoriefunktionen:**
- Unbegrenzte Kategorien pro Dokumentenbereich
- Drag-and-Drop-Sortierung zum Umordnen
- Umbenennen durch Bearbeiten der Kategorie
- Ganze Kategorie löschen (Warnung: löscht alle Dokumente darin)

## Dokumente anzeigen und bearbeiten

### Dokument anzeigen

**Methode 1: Klicken Sie auf Dokumentenkarte**
1. Finden Sie Dokument in Kategorie
2. Klicken Sie auf Dokumentenkarte
3. Dokumenteneditor öffnet sich im Ansichts-/Bearbeitungsmodus
4. Bearbeiten Sie Felder bei Schreibberechtigung
5. Klicken Sie auf **Speichern** zum Aktualisieren

**Methode 2: Direkter Zugriff**
- Wenn Dokumentenbereich spezifische ID-Route hat
- Dokumente laden automatisch für diesen Bereich

### Dokument bearbeiten

1. Klicken Sie auf Dokument zum Öffnen des Editors
2. Ändern Sie Felder:
   - Titel
   - Kategorie (kann in andere Kategorie verschoben werden)
   - Inhalt (Rich-Text-Editor)
   - Notizen
3. Klicken Sie auf **Speichern**
4. Änderungen werden sofort angewendet

**Erforderliche Berechtigungen:**
- Muss `WRITE_DOCUMENT`-Berechtigung haben
- Bereichsebene-Schreibberechtigung erforderlich

## Dokumente löschen

### Dokument löschen

1. Fahren Sie über Dokumentenkarte
2. Klicken Sie auf **Löschen**-Schaltfläche (Papierkorb-Symbol)
3. Bestätigen Sie Löschung im Dialog
4. Dokument wird soft-gelöscht (archiviert)

**Hinweis:** Löschung ist aus Benutzersicht permanent. Nur Administratoren können aus Datenbank wiederherstellen.

**Erforderliche Berechtigung:** `DELETE_DOCUMENT` + Bereichsebene-Löschberechtigung

## Kategorien löschen

### Kategorie löschen

1. Finden Sie Kategorie
2. Klicken Sie auf **Löschen**-Schaltfläche (Papierkorb-Symbol auf Kategoriekarte)
3. **Warnung:** Das Löschen einer Kategorie löscht ALLE Dokumente darin
4. Bestätigen Sie Löschung
5. Kategorie und alle Dokumente werden entfernt

**Erforderliche Berechtigung:** `DELETE_DOCUMENT`

## Sortierung

### Kategorien sortieren

**Drag-and-Drop:**
1. Klicken Sie auf **Kategorien sortieren**-Schaltfläche
2. Drag-and-Drop-Interface erscheint
3. Ziehen Sie Kategorien in gewünschte Reihenfolge
4. Klicken Sie auf **Reihenfolge speichern**
5. Kategorienreihenfolge wird aktualisiert

**Oder:**
- Ziehen Sie Kategoriekarten direkt in Hauptansicht
- Reihenfolge speichert automatisch über API

### Dokumente sortieren

**Drag-and-Drop innerhalb der Kategorie:**
1. Klicken Sie auf **Dokumente sortieren**-Schaltfläche
2. Wählen Sie zu sortierende Kategorie
3. Ziehen Sie Dokumente innerhalb dieser Kategorie
4. Klicken Sie auf **Reihenfolge speichern**
5. Dokumentenreihenfolge wird aktualisiert

**Hinweis:** Dokumente können nur innerhalb ihrer Kategorie sortiert werden. Zum Verschieben in andere Kategorie bearbeiten Sie das Dokument und ändern die Kategorie.

## Dokumente durchsuchen

### Suchfunktionalität

**Suchleiste:**
- Befindet sich in Kopfzeile (oben rechts)
- Geben Sie Dokumententitel ein zum Suchen
- Groß-/Kleinschreibung unabhängige Übereinstimmung
- Echtzeit-Filterung

**Was durchsucht wird:**
- Nur Dokumententitel
- Durchsucht nicht Dokumenteninhalt oder Notizen

**Suchverhalten:**
- Filtert Dokumente über alle Kategorien
- Zeigt nur Dokumente, die Suchbegriff entsprechen
- Suche löschen, um alle Dokumente wieder zu sehen

**Einschränkungen:**
- Keine erweiterte Suche (nach Ersteller, Datum, Kategorie)
- Keine Inhaltssuche
- Keine gespeicherten Suchen

## Ansichtsmodi

### Rasteransicht (Standard)

**Kartenlayout:**
- Größere Dokumentenkarten
- Zeigt Titel, Ersteller, Daten
- Kategoriengruppierung
- Hover-Overlay mit Aktionen

**Am besten für:**
- Visuelles Durchsuchen
- Schnelle Identifizierung nach Titel
- Metadaten auf einen Blick sehen

### Listenansicht

**Tabellenlayout:**
- Kompakte Zeilen
- Dokumententitel + Ersteller + Daten in Spalten
- Mehr Dokumente pro Bildschirm sichtbar
- Aktionsschaltflächen inline

**Am besten für:**
- Viele Dokumente
- Schnelles Scannen nach Name
- Spezifische Dokumente in langen Listen finden

**Ansicht umschalten:**
- Klicken Sie auf Ansichtsmodus-Umschalter in Kopfzeile
- Wechseln zwischen Raster und Liste
- Präferenz speichert in Benutzereinstellungen

## Dokumentenbereiche

Dokumentenbereiche ermöglichen die Erstellung separater Dokumenten-Repositorys mit unabhängigen Berechtigungen.

### Was sind Dokumentenbereiche?

**Konzept:**
- Separate Dokumentenräume innerhalb Ihrer Authority
- Jeder Bereich hat eigene Kategorien und Dokumente
- Unabhängige Berechtigungseinstellungen pro Bereich
- Beispiele: HR-Dokumente, Betriebshandbuch, Schulungsmaterialien

**Bereichseigenschaften:**
- Name (z.B. "HR-Dokumente")
- Symbol (Material-Design-Symbolcode)
- Beschreibung
- Route/Identifier für Zugriff
- Authority-spezifisch

### Auf verschiedene Bereiche zugreifen

**Methode 1: Direkte Route**
- Jeder Bereich kann eigene Route haben
- Zu bereichsspezifischer Dokumentenansicht navigieren

**Methode 2: Bereichsauswahl**
- Falls implementiert, zwischen Bereichen über Dropdown wechseln

**Methode 3: Desktop-Symbole**
- Separate Desktop-Symbole für jeden Dokumentenbereich

## Berechtigungssystem

### Bereichsebene-Berechtigungen

**Drei Berechtigungstypen:**

**1. Leseberechtigung** (`READ_DOCUMENT`)
- Dokumentenbereichsliste anzeigen
- Kategorien und Dokumente durchsuchen
- Dokumenteninhalt lesen
- Kann nicht erstellen oder bearbeiten

**2. Schreibberechtigung** (`WRITE_DOCUMENT`)
- Alle LESE-Berechtigungen
- Neue Dokumente erstellen
- Vorhandene Dokumente bearbeiten
- Kategorien erstellen und umbenennen
- Dokumente und Kategorien sortieren

**3. Löschberechtigung** (`DELETE_DOCUMENT`)
- Dokumente löschen
- Kategorien löschen (mit allen Dokumenten darin)

**Berechtigungskonfiguration:**
- Pro Dokumentenbereich festgelegt
- Rollenbasierte Zuweisung
- Von Administratoren in DocumentAreasView konfiguriert

### Wie Berechtigungen funktionieren

**Bereichszugriff:**
- Benutzer muss Rolle zugewiesen haben, die dem Bereich zugeordnet ist
- Berechtigungsebenen bestimmen verfügbare Aktionen
- Keine Erstellen/Bearbeiten/Löschen-Schaltflächen ohne Berechtigung angezeigt

**Berechtigungsvererbung:**
- Bereichsberechtigungen gelten für alle Dokumente darin
- Keine pro-Dokument-Berechtigungen (alle Dokumente erben Bereichsberechtigungen)

## Multi-Tenant-Sicherheit

**Authority-Isolation:**
- Alle Dokumente auf `authority_id` beschränkt
- Benutzer sehen nur Dokumente aus ihrer Organisation
- Organisationsübergreifender Zugriff verhindert
- Dokumentenbereiche spezifisch für jede Authority

## Dokument-Datenmodell

**Dokumentenfelder (Datenbank):**
- `id` - Eindeutige Dokument-ID
- `category_id` - Kategoriezuweisung
- `title` - Dokumententitel
- `content` - Rich-Text-HTML-Inhalt
- `notes` - Interne Notizen
- `sort_order` - Sortierposition innerhalb der Kategorie
- `creator` - Benutzer-ID, der Dokument erstellt hat
- `is_deleted` - Soft-Delete-Flag
- `authority_id` - Organisations-/Mandanten-ID
- `created_at` - Erstellungszeitstempel
- `updated_at` - Zeitstempel der letzten Aktualisierung
- `updated_at_user` - Benutzer, der zuletzt aktualisiert hat

**Kategorienfelder:**
- `id` - Kategorien-ID
- `name` - Kategorienname
- `parent_id` - Übergeordnete Kategorie (hierarchisch, falls verwendet)
- `sort_order` - Sortierposition
- `area_id` - Dokumentenbereichszuweisung
- `authority_id` - Organisations-ID

**Dokumentenbereichsfelder:**
- `id` - Bereichs-ID
- `name` - Bereichsname
- `icon` - Material-Design-Symbol
- `description` - Bereichsbeschreibung
- `authority_id` - Organisations-ID

## Admin: Dokumentenbereiche

Administratoren können Dokumentenbereiche über die Admin-Oberfläche erstellen und verwalten.

### Dokumentenbereich erstellen

**Zugriff:** Admin → Dokumente → Dokumentenbereiche

1. Klicken Sie auf **Bereich erstellen**-Schaltfläche
2. Füllen Sie Details aus:
   - Name (erforderlich)
   - Symbol (Material-Design-Symbolcode, z.B. `mdi-folder`)
   - Beschreibung
3. Klicken Sie auf **Erstellen**
4. Neuer Bereich erscheint in Liste

### Bereichsberechtigungen konfigurieren

1. Finden Sie Dokumentenbereich in Admin-Liste
2. Klicken Sie auf **Berechtigungen**-Schaltfläche
3. Wählen Sie Rollen zum Gewähren von Zugriff
4. Legen Sie Berechtigungsebene für jede Rolle fest:
   - Nur lesen
   - Lesen + Schreiben
   - Lesen + Schreiben + Löschen
5. Klicken Sie auf **Berechtigungen speichern**
6. Berechtigungen werden sofort angewendet

**Berechtigungszuweisung:**
- Mehrere Rollen können unterschiedliche Berechtigungsebenen haben
- Benutzer mit zugewiesenen Rollen erhalten Bereichszugriff
- Keine Rolle = kein Zugriff auf diesen Bereich

### Dokumentenbereich bearbeiten

1. Klicken Sie auf **Bearbeiten**-Schaltfläche im Bereich
2. Ändern Sie Name, Symbol oder Beschreibung
3. Klicken Sie auf **Speichern**
4. Änderungen angewendet

### Dokumentenbereich löschen

**Warnung:** Das Löschen eines Bereichs löscht ALLE Kategorien und Dokumente darin.

1. Klicken Sie auf **Löschen**-Schaltfläche
2. Bestätigen Sie Löschung
3. Bereich, Kategorien und Dokumente werden entfernt (soft-gelöscht)

## Einschränkungen

**Was NICHT verfügbar ist:**

### Dokumentenfunktionen
- ❌ Versionskontrolle/Historie
- ❌ Dokumentenvergleich
- ❌ Dateianhänge (verwenden Sie File Manager separat)
- ❌ Dokumentenfreigabe (interne Benutzer oder externe Links)
- ❌ Dokumentenvorlagen
- ❌ Dokumentenstatus (Entwurf/Veröffentlicht/Archiviert)
- ❌ Dokumentengenehmierungs-Workflows
- ❌ Kommentare/Diskussionen zu Dokumenten
- ❌ Dokumenten-Tags/Labels
- ❌ Dokumentenablauf-/Gültigkeitsdaten
- ❌ Dokumentennummerierungssystem
- ❌ Export nach PDF/Word

### Organisationsfunktionen
- ❌ Ordnerhierarchie (nur flache Kategorien pro Bereich)
- ❌ Dokumente zwischen Bereichen verschieben
- ❌ Dokumente kopieren
- ❌ Massenoperationen (Mehrfachauswahl, Massenbearbeitung/Löschen)
- ❌ Dokumentenverknüpfung/Querverweise

### Suchfunktionen
- ❌ Inhaltssuche (nur Titelsuche)
- ❌ Erweiterte Suche (nach Datum, Ersteller, Kategorie)
- ❌ Gespeicherte Suchen
- ❌ Suchfilter

### Kollaborationsfunktionen
- ❌ Echtzeit-Zusammenarbeit
- ❌ Dokumentensperre (Bearbeitungsschutz)
- ❌ Dokumentenabonnements/Benachrichtigungen
- ❌ Aktivitäts-Feeds
- ❌ Benutzererwähnungen

### Analysefunktionen
- ❌ Dokumentenansichtszahlen
- ❌ Beliebte Dokumente
- ❌ Speicherplatznutzungsstatistiken
- ❌ Aktivitätsberichte

### Erweiterte Funktionen
- ❌ Benutzerdefinierte Metadatenfelder
- ❌ Bereichsexport/Import-Konfiguration
- ❌ Globale/Authority-übergreifende Dokumente
- ❌ Dokumentenaufbewahrungsrichtlinien
- ❌ Audit-Trail (nur grundlegende Zeitstempel)

## Tipps & Best Practices

**Dokumente organisieren:**
- Erstellen Sie klare, beschreibende Kategorienamen
- Verwenden Sie konsistente Namenskonventionen für Dokumente
- Halten Sie Kategorienanzahl überschaubar (< 20 pro Bereich)
- Verwenden Sie Dokumentennotizen für internen Kontext

**Inhalte erstellen:**
- Schreiben Sie klare, prägnante Dokumententitel
- Verwenden Sie Überschriften im Inhalt für Struktur
- Halten Sie Dokumente fokussiert (ein Thema pro Dokument)
- Verwenden Sie Rich-Text-Formatierung für Lesbarkeit

**Bereiche verwalten:**
- Erstellen Sie separate Bereiche für verschiedene Abteilungen
- Legen Sie passende Berechtigungen pro Rolle fest
- Verwenden Sie beschreibende Bereichsnamen und Symbole
- Überprüfen und bereinigen Sie regelmäßig veraltete Dokumente

**Leistung:**
- Vermeiden Sie extrem lange Dokumente (in mehrere aufteilen)
- Verwenden Sie einfache Formatierung (vermeiden Sie übermäßige Bilder im Inhalt)
- Halten Sie Kategorienstrukturen flach
- Regelmäßige Bereinigung ungenutzter Dokumente

## Fehlerbehebung

### Kann kein Dokument erstellen

**Problem:** Erstellen-Schaltfläche fehlt oder ist deaktiviert

**Lösungen:**
1. Überprüfen Sie, ob Sie `WRITE_DOCUMENT`-Berechtigung haben
2. Prüfen Sie Bereichsebene-Schreibberechtigung
3. Überprüfen Sie, ob Dokumentenbereich zugänglich ist
4. Seite aktualisieren
5. Prüfen Sie Browser-Konsole auf Fehler

### Kann Dokument nicht bearbeiten

**Problem:** Speichern-Schaltfläche funktioniert nicht

**Lösungen:**
1. Prüfen Sie, ob Sie Schreibberechtigung für diesen Bereich haben
2. Überprüfen Sie, ob Titel und Kategorie ausgefüllt sind
3. Prüfen Sie Internetverbindung
4. Suchen Sie nach Fehlermeldungen in Toast-Benachrichtigungen
5. Versuchen Sie Aktualisieren und erneutes Bearbeiten

### Dokumente werden nicht geladen

**Problem:** Kategorien leer oder Dokumente fehlen

**Lösungen:**
1. Überprüfen Sie, ob Sie Leseberechtigung für den Bereich haben
2. Prüfen Sie, ob Bereichs-ID in Route korrekt ist
3. Löschen Sie Suchfilter (falls aktiv)
4. Seite aktualisieren
5. Prüfen Sie Browser-Konsole auf API-Fehler

### Suche funktioniert nicht

**Problem:** Suche filtert keine Dokumente

**Lösungen:**
1. Prüfen Sie Rechtschreibung des Dokumententitels
2. Versuchen Sie Teiltitel
3. Löschen Sie Suche und versuchen Sie es erneut
4. Seite aktualisieren, um Dokumente neu zu laden
5. Überprüfen Sie, ob Dokumente in Datenbank existieren

### Sortierung wird nicht gespeichert

**Problem:** Drag-and-Drop-Reihenfolge bleibt nicht bestehen

**Lösungen:**
1. Prüfen Sie, ob Sie Schreibberechtigung haben
2. Überprüfen Sie, ob Sie auf "Reihenfolge speichern"-Schaltfläche geklickt haben
3. Prüfen Sie Internetverbindung während des Speicherns
4. Suchen Sie nach Fehlermeldungen
5. Versuchen Sie manuelle Sortierung über Sortierdialog

## Verwandte Dokumentation

- [Erste Schritte](/guide/getting-started) - Lernen Sie die Grundlagen von K-Systems
- [File Manager](/guide/file-manager) - Dateispeicherung und -verwaltung
- [Berichte](/guide/reports) - Berichtserstellung und -verwaltung

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend tatsächlicher Implementierung)
