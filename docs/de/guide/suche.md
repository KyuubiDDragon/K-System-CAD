# Globale Suche

K-Systems verfügt über ein leistungsstarkes globales Suchsystem, mit dem Sie schnell Anwendungen, Daten, Mitarbeiter, Berichte, Dokumente und mehr im gesamten System finden können.

## Übersicht

Die globale Suche bietet:
- **Anwendungssuche**: Finden und starten Sie jede Anwendung
- **Datensuche**: Suche über Mitarbeiter, Berichte, Dokumente
- **Schnellnavigation**: Springen Sie direkt zu bestimmten Datensätzen
- **Tastatur-First**: Schnelle Suche mit Shortcuts
- **Echtzeit-Ergebnisse**: Sofortige Suche während der Eingabe
- **Berechtigungsbewusst**: Zeigt nur zugängliche Inhalte
- **Letzte Suchen**: Schnellzugriff auf frühere Suchen
- **Suchfilter**: Ergebnisse nach Typ eingrenzen

## Öffnen der globalen Suche

### Methode 1: Tastenkombination
Drücken Sie `Strg+K` (Windows/Linux) oder `Cmd+K` (Mac) von überall in der Anwendung.

### Methode 2: Taskleisten-Symbol
Klicken Sie auf das Suchsymbol (🔍) in der Taskleisten-Systemablage.

### Methode 3: Startmenü
Tippen Sie direkt in die Suchleiste des Startmenüs.

### Methode 4: Schrägstrich
Drücken Sie die `/`-Taste vom Desktop aus, um die Suche zu öffnen.

## Suchoberfläche

### Suchleiste

Die Suchoberfläche erscheint als zentriertes modales Overlay:

```
┌─────────────────────────────────────────────┐
│  🔍 K-Systems durchsuchen...                │
│                                             │
│  Letzte Suchen:                             │
│  • John Smith                               │
│  • Brandbericht 2024                        │
│  • Mitarbeiterverwaltung                    │
│                                             │
│  Schnellaktionen:                           │
│  [📝 Neuer Bericht] [👥 Neuer Mitarbeiter]  │
└─────────────────────────────────────────────┘
```

Wenn Sie tippen, erscheinen sofort Ergebnisse darunter:

```
┌─────────────────────────────────────────────┐
│  🔍 john smith                            × │
│                                             │
│  Anwendungen (1)                            │
│  → [👥] Mitarbeiterverwaltung               │
│                                             │
│  Mitarbeiter (3)                            │
│  → [👤] John Smith - Feuerwehrmann          │
│     Abteilung: Feuerwehr, Aktiv             │
│  → [👤] John Smithson - Sanitäter           │
│     Abteilung: Medizin, Aktiv               │
│  → [👤] Johnny Smith - Fahrer               │
│     Abteilung: Transport, Inaktiv           │
│                                             │
│  Berichte (5)                               │
│  → [📝] Brandeinsatz - John Smith           │
│     Status: Abgeschlossen, 2024-03-15       │
│  → [📝] Schulungsbericht - John Smith       │
│     Status: In Bearbeitung, 2024-03-10      │
│                                             │
│  Drücken Sie ↑↓ zum Navigieren, Enter zum Öffnen │
└─────────────────────────────────────────────┘
```

### Suchverhalten

**Echtzeit-Suche:**
- Ergebnisse erscheinen während der Eingabe
- Mindestens 2 Zeichen zum Starten der Suche
- Entprellt (300ms) zur Vermeidung übermäßiger Anfragen
- Aktualisiert sich sofort beim Weitertippen

**Fuzzy Matching:**
- Tippfehlertolerante Suche
- Teilwortübereinstimmung
- Akronym-Übereinstimmung (z.B. "MV" findet "Mitarbeiterverwaltung")
- Groß-/Kleinschreibung wird ignoriert

**Relevanz-Sortierung:**
Ergebnisse werden nach Relevanz sortiert:
1. Exakte Übereinstimmungen
2. Beginnt mit Abfrage
3. Enthält Abfrage
4. Fuzzy-Übereinstimmungen
5. Kürzliche Elemente bevorzugt

## Suchkategorien

### Anwendungen

**Was wird durchsucht:**
- Alle verfügbaren Anwendungen nach Name
- Anwendungsbeschreibungen
- Kategorien

**Ergebnisse zeigen:**
- Anwendungssymbol
- Anwendungsname
- Kategorie
- Schnellstartfähigkeit

**Beispielsuche:** `"mitarbeiter"` findet:
- Mitarbeiterverwaltung
- Mitarbeiterschulung
- Mitarbeiterakten

**Aktionen:**
- Klicken Sie auf Ergebnis oder drücken Sie Enter, um die App zu öffnen
- Rechtsklick für Optionen (Anheften, In neuem Fenster öffnen)

### Mitarbeiter

**Was wird durchsucht:**
- Vorname, Nachname
- Mitarbeiter-ID
- E-Mail-Adresse
- Telefonnummern
- Abteilungsname
- Rang/Position

**Erforderliche Berechtigung:** `READ_EMPLOYEE`

**Ergebnisse zeigen:**
- Mitarbeitername und Avatar
- Abteilung und Rang
- Status (Aktiv/Inaktiv)
- Kontaktinformationsvorschau

**Beispielsuche:** `"john"` findet:
- John Smith
- Johnny Appleseed
- Jeder in "Johns Abteilung"

**Aktionen:**
- Klicken zum Öffnen der Mitarbeiterdetailansicht
- Zeigt Schnellvorschau beim Hovern

### Berichte

**Was wird durchsucht:**
- Berichtstitel
- Berichts-ID/Nummer
- Berichtskategorie
- Berichterstattername
- Berichtsinhalt (falls aktiviert)
- Zugeordnete Personen

**Erforderliche Berechtigung:** `READ_REPORT`

**Ergebnisse zeigen:**
- Berichtstitel und ID
- Kategorie und Status
- Erstellungsdatum
- Berichterstattername

**Beispielsuche:** `"brandeinsatz"` findet:
- Alle Berichte mit "Brand" oder "Einsatz"
- Berichte der Kategorie Brand
- Berichte der Feuerwehr

**Aktionen:**
- Klicken zum Öffnen der Berichtsdetails
- Rechtsklick für Schnellaktionen (Teilen, Exportieren, Bearbeiten)

**Status-Filterung:**
Geben Sie Status in Anführungszeichen ein: `"entwurf"`, `"in bearbeitung"`, `"abgeschlossen"`

### Dokumente

**Was wird durchsucht:**
- Dokumenttitel
- Dokumentbeschreibung
- Dateinamen
- Dokumentbereiche
- Tags und Kategorien
- Dokumentinhalt (falls indiziert)

**Erforderliche Berechtigung:** `READ_DOCUMENT`

**Ergebnisse zeigen:**
- Dokumenttitel und Symbol
- Dokumentbereich
- Letzte Änderung
- Dateigröße und -typ

**Beispielsuche:** `"richtlinienhandbuch"` findet:
- Richtlinienhandbuch-Dokumente
- Handbuch der Richtlinien
- Jedes Dokument mit beiden Wörtern

**Aktionen:**
- Klicken zum Anzeigen des Dokuments
- Direkt aus Suchergebnissen herunterladen

**Bereichsfilterung:**
Geben Sie Bereich in eckigen Klammern ein: `[richtlinien]`, `[formulare]`

### Nachrichten

**Was wird durchsucht:**
- Nachrichteninhalt (Vorschau)
- Absendernamen
- Betreffzeilen (falls vorhanden)
- Gesprächsteilnehmer

**Erforderliche Berechtigung:** `READ_MESSAGE`

**Ergebnisse zeigen:**
- Nachrichtenvorschau (erste 100 Zeichen)
- Absendername und Avatar
- Datum/Uhrzeit gesendet
- Gesprächskontext

**Beispielsuche:** `"besprechung morgen"` findet:
- Nachrichten über Besprechungen
- Morgige geplante Diskussionen

**Aktionen:**
- Klicken zum Öffnen der Konversation
- Direkt aus der Suche antworten (zukünftig)

**Datenschutzhinweis:**
Nur Ihre eigenen Nachrichten und Gruppennachrichten, an denen Sie beteiligt sind, sind durchsuchbar.

### Kalendertermine

**Was wird durchsucht:**
- Termintitel
- Terminbeschreibung
- Ort
- Zugewiesene Benutzer
- Kalendergruppennamen

**Erforderliche Berechtigung:** `READ_CALENDAR`

**Ergebnisse zeigen:**
- Termintitel
- Datum und Uhrzeit
- Ort
- Teilnehmeranzahl

**Beispielsuche:** `"teambesprechung"` findet:
- Alle Teambesprechungen
- Termine in Team-Kalendergruppe
- Termine mit "Team" in Beschreibung

**Aktionen:**
- Klicken zum Anzeigen der Termindetails
- RSVP direkt aus der Suche
- Zum persönlichen Kalender hinzufügen

**Datumsfilterung:**
- `heute` - Heutige Termine
- `morgen` - Morgige Termine
- `diese woche` - Termine dieser Woche
- `2024-03-15` - Bestimmtes Datum

### Firmen

**Was wird durchsucht:**
- Firmenname
- Firmen-ID
- Kontaktpersonennamen
- E-Mail und Telefon
- Adresse
- Notizen

**Erforderliche Berechtigung:** `READ_COMPANY`

**Ergebnisse zeigen:**
- Firmenname und Logo
- Hauptkontakt
- Telefon und E-Mail
- Status (Aktiv/Inaktiv)

**Beispielsuche:** `"bau"` findet:
- ABC Baufirma
- Firmen in der Baubranche
- Baubezogene Kontakte

**Aktionen:**
- Klicken zum Anzeigen des Firmenprofils
- Telefonnummer schnell wählen
- E-Mail senden

### Rechnungen

**Was wird durchsucht:**
- Rechnungsnummer
- Kundenname
- Beschreibung
- Positionsartikel
- Rechnungsstatus

**Erforderliche Berechtigung:** `READ_INVOICE`

**Ergebnisse zeigen:**
- Rechnungsnummer und ID
- Kundenname
- Gesamtbetrag
- Status (Bezahlt/Ausstehend/Überfällig)
- Datum

**Beispielsuche:** `"rechnung 2024"` findet:
- Alle 2024-Rechnungen
- Rechnung #2024-xxx

**Aktionen:**
- Klicken zum Anzeigen der Rechnung
- PDF herunterladen
- Als bezahlt markieren (falls berechtigt)

### Aufgaben

**Was wird durchsucht:**
- Aufgabentitel
- Aufgabenbeschreibung
- Zugewiesene Benutzer
- Tags

**Erforderliche Berechtigung:** `READ_TODO`

**Ergebnisse zeigen:**
- Aufgabentitel
- Fälligkeitsdatum
- Status (Ausstehend/Abgeschlossen)
- Zugewiesen an

**Beispielsuche:** `"dringend"` findet:
- Dringende Aufgaben
- Aufgaben mit hoher Priorität

**Aktionen:**
- Klicken zum Öffnen der Aufgabe
- Aus Suche als erledigt markieren
- Fälligkeitsdatum bearbeiten

## Erweiterte Suche

### Suchoperatoren

**Exakte Phrase:**
```
"brandsicherheitsbericht"
```
Findet nur exakte Phrase.

**ODER-Operator:**
```
john ODER jane
```
Findet einen der Begriffe.

**NICHT-Operator:**
```
bericht -entwurf
```
Schließt Entwürfe aus.

**Feldsuche:**
```
status:abgeschlossen
kategorie:brand
autor:john
```
Durchsucht spezifische Felder.

### Filter

**Typfilter:**
Klicken Sie auf Filterschaltflächen unter der Suchleiste:
- Alle Ergebnisse (Standard)
- Anwendungen
- Mitarbeiter
- Berichte
- Dokumente
- Nachrichten
- Termine

**Datumsfilter:**
```
datum:heute
datum:gestern
datum:diese_woche
datum:dieser_monat
datum:2024-03
datum:>2024-01-01
datum:<2024-12-31
```

**Statusfilter:**
```
status:aktiv
status:abgeschlossen
status:entwurf
status:ausstehend
```

**Kombinierte Filter:**
```
typ:bericht status:abgeschlossen datum:dieser_monat
```

### Suchkürzel

**Schnellbefehle:**

Geben Sie diese Spezialbefehle ein:

**`/apps`** - Alle Anwendungen anzeigen
**`/employees`** - Nur Mitarbeiter durchsuchen
**`/reports`** - Nur Berichte durchsuchen
**`/docs`** - Nur Dokumente durchsuchen
**`/new`** - "Neu erstellen"-Optionen anzeigen
**`/recent`** - Letzte Elemente anzeigen
**`/help`** - Hilfe & Dokumentation durchsuchen

**Navigationskürzel:**

**`!`** gefolgt von Nummer: Zu bestimmter ID springen
```
!123         → Öffnet Mitarbeiter #123
!bericht-456 → Öffnet Bericht #456
```

**`#`** gefolgt von Tag: Nach Tag suchen
```
#dringend    → Mit "dringend" getaggte Elemente
#2024        → Mit "2024" getaggte Elemente
```

**`@`** gefolgt von Name: Nach Person suchen
```
@john        → Elemente mit Bezug zu John
@me          → Ihnen zugewiesene Elemente
```

## Suchergebnisse

### Navigation

**Tastaturnavigation:**
- `↓` oder `Tab` - Nach unten durch Ergebnisse
- `↑` oder `Shift+Tab` - Nach oben durch Ergebnisse
- `Enter` - Ausgewähltes Ergebnis öffnen
- `Strg+Enter` - In neuem Fenster öffnen
- `Esc` - Suche schließen

**Mausnavigation:**
- Hovern zum Hervorheben des Ergebnisses
- Klicken zum Öffnen
- Rechtsklick für Kontextmenü

### Ergebnisaktionen

**Primäraktion:**
Klicken oder Enter drücken, um das Element zu öffnen.

**Sekundäraktionen:**
Rechtsklick oder Klick auf ⋮-Menü:
- In neuem Fenster öffnen
- Link kopieren
- Teilen
- Zu Favoriten hinzufügen
- Schnellbearbeitung
- Löschen (falls berechtigt)

**Massenaktionen:**
Mehrere Ergebnisse auswählen (Strg+Klick):
- Ausgewählte exportieren
- Ausgewählte teilen
- Ausgewählte löschen
- Zu Ordner hinzufügen

### Ergebnisvorschauen

Hovern über ein Ergebnis für Schnellvorschau:

**Mitarbeitervorschau:**
- Vollständiger Name und Foto
- Kontaktinformationen
- Aktueller Status
- Abteilung und Rang
- Letzte Aktivität

**Berichtsvorschau:**
- Berichtstitel und ID
- Status und Kategorie
- Erstellungsdatum und Autor
- Erste Zeilen des Inhalts
- Anzahl angehängter Dateien

**Dokumentvorschau:**
- Dokumentminiatur
- Dateityp und -größe
- Letztes Änderungsdatum
- Download-Schaltfläche

## Suchverlauf

### Letzte Suchen

**Auf letzte Suchen zugreifen:**
1. Suche öffnen (Strg+K)
2. Leere Suche zeigt letzte Suchen
3. Klicken zum Wiederholen der Suche

**Letzte Elemente:**
- Letzte 10 Suchen gespeichert
- Bleibt über Sitzungen erhalten
- Klicken Sie auf ×, um einzelne Elemente zu entfernen
- "Verlauf löschen", um alle zu entfernen

### Suchvorschläge

Während der Eingabe schlägt K-Systems vor:
- Beliebte Suchen von Benutzern
- Ihre häufigen Suchen
- Verwandte Suchbegriffe
- Rechtschreibkorrekturen

### Gespeicherte Suchen (Zukünftige Funktion)

Komplexe Suchen speichern:
1. Suche mit Filtern durchführen
2. Auf "Suche speichern" klicken
3. Suche benennen
4. Aus "Gespeicherte Suchen"-Menü zugreifen

**Anwendungsfälle:**
- "Berichte meines Teams diesen Monat"
- "Überfällige Rechnungen"
- "Mitarbeiter mit Schulungsbedarf"
- "Diese Woche geänderte Dokumente"

## Sucheinstellungen

**Suche konfigurieren:**
1. Suche öffnen
2. Auf Zahnradsymbol (⚙️) klicken
3. Einstellungen anpassen

**Verfügbare Einstellungen:**

**Suchbereich:**
- ☑ Anwendungen
- ☑ Mitarbeiter
- ☑ Berichte
- ☑ Dokumente
- ☑ Nachrichten
- ☑ Kalendertermine
- ☑ Firmen
- ☑ Rechnungen
- ☑ Aufgaben

**Suchverhalten:**
- **Mindestzeichen**: 2-5 (Standard: 2)
- **Max. Ergebnisse**: 10-100 (Standard: 25)
- **Inhalt einschließen**: In Dokumenten suchen (langsamer)
- **Fuzzy Matching**: Tippfehlertoleranz aktivieren
- **Vorschauen anzeigen**: Hover-Vorschauen

**Datenschutzeinstellungen:**
- **Suchverlauf speichern**: Ein/Aus
- **In Analysen einbeziehen**: Ein/Aus
- **Suchvorschläge**: Ein/Aus

## Leistung

**Suchleistung:**
- Indizierte Felder: < 100ms Antwortzeit
- Volltextsuche: < 500ms Antwortzeit
- Maximal 1000 zurückgegebene Ergebnisse
- Ergebnisse für große Mengen paginiert

**Optimierungstipps:**
1. Seien Sie spezifisch: Mehr Wörter = bessere Ergebnisse
2. Verwenden Sie Filter: Nach Typ/Datum/Status eingrenzen
3. Verwenden Sie Feldsuche: Schneller als Volltextsuche
4. Vermeiden Sie Wildcards: Langsamere Leistung

**Suchbeschränkungen:**
- Archivierte Elemente werden standardmäßig nicht durchsucht
- Gelöschte Elemente niemals durchsuchbar
- Berechtigungsbeschränkte Elemente ausgeblendet
- Externe Links nicht indiziert

## Mobile Suche

**Mobil-optimiert:**
- Vollbild-Suchoberfläche
- Touch-freundliche Ergebnisse
- Sprachsuchunterstützung (browserabhängig)
- Wischen zum Schließen der Ergebnisse
- Letzte Suchen prominent

**Mobile Kürzel:**
- Auf Desktop herunterziehen, um Suche zu öffnen
- Auf Suchsymbol in Navigation tippen
- Widget für Schnellsuche verwenden

## Fehlerbehebung

### Keine Ergebnisse gefunden

**Problem:** Suche gibt keine Ergebnisse zurück

**Lösungen:**
1. Rechtschreibung überprüfen
2. Weniger Suchbegriffe verwenden
3. Filter entfernen
4. Berechtigungen für diesen Datentyp überprüfen
5. Andere Suchbegriffe ausprobieren
6. Prüfen, ob Element existiert

### Suche ist langsam

**Problem:** Suche dauert > 5 Sekunden

**Lösungen:**
1. Bereich reduzieren (einige Kategorien deaktivieren)
2. Spezifischer sein
3. Filter zum Eingrenzen verwenden
4. Inhaltssuche deaktivieren
5. Browser-Cache löschen
6. Netzwerkverbindung prüfen

### Ergebnisse werden nicht aktualisiert

**Problem:** Neue Elemente erscheinen nicht in der Suche

**Lösungen:**
1. Seite aktualisieren (F5)
2. 1-2 Minuten für Indizierung warten
3. Browser-Cache löschen
4. Prüfen, ob Element ordnungsgemäß gespeichert ist
5. Berechtigungen überprüfen

### Falsche Ergebnisse

**Problem:** Irrelevante Ergebnisse erscheinen

**Lösungen:**
1. Exakte Phrasensuche verwenden: "begriff"
2. Feldsuche verwenden: feld:wert
3. Ausschluss hinzufügen: begriff -ausschließen
4. Bei Persistenz an Admin melden

### Suche öffnet nicht

**Problem:** Strg+K funktioniert nicht

**Lösungen:**
1. Auf Suchsymbol klicken versuchen
2. Browser-Erweiterungen prüfen (können blockieren)
3. Anderen Browser versuchen
4. Tastatureinstellungen prüfen
5. Stattdessen Startmenü-Suche verwenden

## Such-API

Entwickler können die Suche programmatisch integrieren:

**Endpunkt:**
```http
GET /api/search?q=abfrage&type=employee&limit=25
```

**Parameter:**
- `q` - Suchabfrage (erforderlich)
- `type` - Nach Typ filtern (optional)
- `limit` - Max. Ergebnisse (Standard: 25)
- `page` - Seitennummer (Standard: 1)
- `filters` - JSON-Filter (optional)

**Antwort:**
```json
{
  "results": [
    {
      "id": 123,
      "type": "employee",
      "title": "John Smith",
      "subtitle": "Feuerwehrmann - Feuerwehr",
      "url": "/employee/123",
      "relevance": 0.95
    }
  ],
  "total": 5,
  "page": 1,
  "pages": 1
}
```

**Rate Limiting:**
- 100 Anfragen pro Minute pro Benutzer
- 1000 Anfragen pro Stunde pro Benutzer

[API-Dokumentation →](/api/search)

## Best Practices

**Effektives Suchen:**

1. **Breit beginnen, dann eingrenzen**
   - Mit allgemeinem Begriff beginnen
   - Filter hinzufügen, wenn zu viele Ergebnisse
   - Bei Bedarf spezifische Feldsuche verwenden

2. **Tastenkombinationen verwenden**
   - Strg+K ist der schnellste Weg zur Suche
   - Pfeiltasten zur Navigation
   - Enter zum Öffnen

3. **Häufige Suchen speichern**
   - Häufig verwendete Suchen
   - Komplexe Filterkombinationen
   - Im Team geteilte Suchen

4. **Operatoren lernen**
   - Exakte Phrasen: "begriff"
   - Ausschlüsse: -begriff
   - Feldsuche: feld:wert
   - Daten: datum:>2024-01-01

5. **Suchverlauf sauber halten**
   - Veraltete Suchen entfernen
   - Verlauf monatlich löschen
   - Datenschutzbewusst

**Suchtipps:**

- **Mitarbeitersuche**: Vor- ODER Nachname verwenden
- **Berichtssuche**: Berichtsnummer oder Kategorie versuchen
- **Dokumentsuche**: Dateiname oft relevanter
- **Nachrichtensuche**: Absendername meist besser als Inhalt
- **Datumssuche**: JJJJ-MM-TT-Format verwenden

**Was NICHT zu tun ist:**

❌ Ein-Buchstaben-Suchen (zu breit)
❌ Lange Texte kopieren (Schlüsselphrasen verwenden)
❌ Unnötige Sonderzeichen verwenden
❌ Gelöschte Elemente suchen (sie sind weg)
❌ Sofortige Indizierung erwarten (1-2 Min warten)

## Erweiterte Anwendungsfälle

### Power-User-Workflows

**Tägliche Berichtsüberprüfung:**
```
/reports status:ausstehend @me datum:heute
```
Alle ausstehenden, Ihnen heute zugewiesenen Berichte.

**Teamverwaltung:**
```
typ:mitarbeiter abteilung:"feuerwehr" status:aktiv
```
Alle aktiven Feuerwehrmitarbeiter.

**Rechnungsverfolgung:**
```
typ:rechnung status:überfällig betrag:>1000
```
Überfällige Rechnungen über 1000 €.

**Veranstaltungsplanung:**
```
typ:termin datum:diese_woche ort:"schulung"
```
Schulungsveranstaltungen dieser Woche.

### Beispiele für gespeicherte Suchen

**Personalabteilung:**
- "Mitarbeiter mit ablaufenden Zertifizierungen"
- "Urlaubsanträge diesen Monat"
- "Neueinstellungen dieses Quartals"

**Betrieb:**
- "Heutige Einsatzzuweisungen"
- "Überfällige Berichte nach Typ"
- "Fällige Fahrzeugwartung"

**Verwaltung:**
- "Inaktive Benutzer der letzten 90 Tage"
- "Letzte Systemänderungen"
- "Berechtigungsprüfungsprotokoll"

## Integration mit anderen Funktionen

**Suchintegrationen:**

**Desktop-Modus:**
- Suche aus Startmenü
- Taskleisten-Schnellsuche
- Desktop-Such-Widget

**Benachrichtigungen:**
- Benachrichtigungsverlauf durchsuchen
- Verwandte Benachrichtigungen finden

**Berichte:**
- Innerhalb des Berichtsinhalts suchen
- Verwandte Berichte finden

**Dokumente:**
- Volltextsuche in Dokumenten
- Nach Metadaten suchen

**Nachrichten:**
- Konversationen durchsuchen
- Anhänge finden

## Zukünftige Verbesserungen

**Geplante Funktionen:**

**KI-gestützte Suche:**
- Natürlichsprachliche Abfragen
- Absichtserkennung
- Intelligente Vorschläge
- Kontextbewusste Ergebnisse

**Erweiterte Filter:**
- Benutzerdefinierte Datumsbereiche
- Komplexe boolesche Logik
- Gespeicherte Filtervorlagen
- Teilbare Filter

**Suchanalysen:**
- Beliebte Suchen
- Suchen ohne Ergebnis
- Benutzersuchmuster
- Optimierungsvorschläge

**Sprachsuche:**
- Sprache-zu-Text-Suche
- Sprachbefehle
- Audio-Ergebniswiedergabe

**Visuelle Suche:**
- Suche per Screenshot
- Bilderkennung
- Ähnliche Elemente finden

## Nächste Schritte

Jetzt, da Sie die globale Suche verstehen:

- [🖥️ Desktop-Modus](/guide/desktop-mode) - Desktop-Oberfläche kennenlernen
- [👥 Mitarbeiterverwaltung](/guide/employee-management) - Mitarbeiter suchen
- [📝 Berichte](/guide/reports) - Berichte suchen und verwalten
- [📄 Dokumente](/guide/documents) - Dokumente schnell finden
- [💬 Nachrichten](/guide/messages) - Konversationen durchsuchen

---

::: tip Such-Profi-Tipp
Lernen Sie Tastenkombinationen! `Strg+K` gefolgt von Eingabe und Enter ist der schnellste Weg, um auf alles in K-Systems zuzugreifen.
:::

::: info Suchleistung
Die Suche ist auf Geschwindigkeit optimiert. Die meisten Abfragen liefern Ergebnisse in unter 100ms. Komplexe Suchen können bis zu 500ms dauern.
:::

::: warning Datenschutzhinweis
Die Suche respektiert alle Berechtigungseinstellungen. Sie sehen nur Ergebnisse, auf die Sie basierend auf Ihrer Rolle und Behörde Zugriff haben.
:::
