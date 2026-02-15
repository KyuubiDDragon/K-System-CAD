# Dokumente

Die Dokumentenverwaltung von K-Systems ermoeglicht das Erstellen und Organisieren von Rich-Text-Dokumenten in Kategorien mit Drag-and-Drop-Sortierung und bereichsbasierten Berechtigungen.

## Ueberblick

Funktionen:
- Rich-Text-Dokumente mit Formatierungswerkzeugen erstellen
- Kategoriebasierte Organisation
- Drag-and-Drop-Sortierung (Kategorien und Dokumente)
- Dokumentbereiche mit rollenbasierten Berechtigungen
- Raster- und Listenansicht
- Titelsuche
- Mandantenfaehigkeit

**Erforderliche Berechtigungen:** `READ_DOCUMENT` (Ansicht), `WRITE_DOCUMENT` (Erstellen/Bearbeiten), `DELETE_DOCUMENT` (Loeschen)

## Zugriff auf Dokumente

- **Desktop:** Doppelklick auf das **Dokumente**-Symbol
- **Menue:** Startmenue -> Dokumente
- **Route:** `/document`

**Bereichsbasierter Zugriff:**
- Verschiedene Dokumentbereiche koennen unterschiedliche Routen haben
- Zugriff wird ueber Bereichsberechtigungen gesteuert (Lesen, Schreiben, Loeschen)
- Mandantenspezifische Dokumentenisolierung

## Oberflaeche

### Kopfzeile
- Titel mit Dokumentsymbol
- Suchfeld (Suche nach Titel)
- Ansichtsmodus-Umschalter (Raster/Liste)

### Kategorienbereich
- Aufklappbare Kategoriekarten
- Dokumentenanzahl pro Kategorie
- Drag-and-Drop zum Neuordnen
- Klicken zum Auf-/Zuklappen

### Dokumentenbereich
- Dokumentenkarten mit Titel, Ersteller und Datum
- Hover-Effekt fuer Bearbeitungs-/Loeschaktionen
- Klicken zum Anzeigen/Bearbeiten

## Dokumente erstellen

### Neues Dokument

1. Klicken Sie auf **Dokument erstellen** (+-Symbol)
2. Der Dokument-Editor oeffnet sich
3. Felder ausfuellen:
   - **Titel** (erforderlich): Dokumentenname
   - **Kategorie** (erforderlich): Aus Dropdown waehlen
   - **Inhalt:** Rich-Text-Inhalt mit Formatierungswerkzeugen
   - **Notizen** (optional): Interne Notizen zum Dokument
4. **Speichern** klicken
5. Dokument erscheint in der gewaehlten Kategorie

**Rich-Text-Editor-Funktionen:**
- Fett, Kursiv, Unterstrichen
- Ueberschriften (H1, H2, H3)
- Aufzaehlungs- und nummerierte Listen
- Tabellen und Links
- Bilder und Textausrichtung
- Code-Bloecke

**Hinweis:** Dokumente sind nur Rich-Text-Inhalte, keine Datei-Uploads. Fuer Dateianhang nutzen Sie den [[Dateimanager]].

## Kategorien erstellen

1. Klicken Sie auf **Kategorie erstellen** (Ordner+-Symbol)
2. Kategorienamen eingeben
3. **Erstellen** klicken
4. Kategorie erscheint in der Liste

**Kategorie-Funktionen:**
- Unbegrenzte Kategorien pro Dokumentbereich
- Drag-and-Drop-Sortierung
- Umbenennen durch Bearbeitung
- Gesamte Kategorie loeschen (Warnung: loescht alle enthaltenen Dokumente)

## Dokumente anzeigen und bearbeiten

### Dokument anzeigen
1. Dokument in der Kategorie finden
2. Auf die Dokumentenkarte klicken
3. Dokument-Editor oeffnet sich im Ansichts-/Bearbeitungsmodus

### Dokument bearbeiten
1. Dokument anklicken, um den Editor zu oeffnen
2. Felder aendern: Titel, Kategorie, Inhalt, Notizen
3. **Speichern** klicken
4. Aenderungen werden sofort uebernommen

**Berechtigung erforderlich:** `WRITE_DOCUMENT` und bereichsbezogene Schreibberechtigung

## Dokumente loeschen

1. Mit der Maus ueber die Dokumentenkarte fahren
2. **Loeschen** (Papierkorb-Symbol) klicken
3. Loeschung im Dialog bestaetigen

**Berechtigung erforderlich:** `DELETE_DOCUMENT` und bereichsbezogene Loeschberechtigung

## Kategorien loeschen

1. Kategorie finden
2. **Loeschen** (Papierkorb-Symbol auf der Kategoriekarte) klicken
3. **Warnung:** Das Loeschen einer Kategorie loescht ALLE enthaltenen Dokumente
4. Loeschung bestaetigen

## Sortierung

### Kategorien sortieren
1. **Kategorien sortieren** klicken
2. Drag-and-Drop-Oberflaeche erscheint
3. Kategorien in gewuenschte Reihenfolge ziehen
4. **Reihenfolge speichern** klicken

### Dokumente sortieren
1. **Dokumente sortieren** klicken
2. Kategorie zum Sortieren waehlen
3. Dokumente innerhalb der Kategorie ziehen
4. **Reihenfolge speichern** klicken

**Hinweis:** Dokumente koennen nur innerhalb ihrer Kategorie sortiert werden. Zum Verschieben in eine andere Kategorie bearbeiten Sie das Dokument.

## Suche

- Suchleiste in der Kopfzeile (oben rechts)
- Sucht nach Dokumenttiteln (nicht nach Inhalt)
- Echtzeit-Filterung bei der Eingabe
- Gross-/Kleinschreibung wird ignoriert
- Suche loeschen, um alle Dokumente anzuzeigen

## Ansichtsmodi

### Rasteransicht (Standard)
- Groessere Dokumentenkarten
- Zeigt Titel, Ersteller, Daten
- Kategoriegruppierung
- Ideal fuer visuelles Durchstoebern

### Listenansicht
- Kompakte Zeilen
- Mehr Dokumente pro Bildschirm sichtbar
- Aktionsschaltflaechen inline
- Ideal fuer lange Dokumentenlisten

Klicken Sie auf den Ansichtsmodus-Umschalter in der Kopfzeile, um zwischen Raster und Liste zu wechseln.

## Dokumentbereiche

Dokumentbereiche ermoelichen separate Dokumenten-Repositories mit unabhaengigen Berechtigungen.

### Was sind Dokumentbereiche?
- Separate Dokumenten-Raeume innerhalb Ihrer Organisation
- Jeder Bereich hat eigene Kategorien und Dokumente
- Unabhaengige Berechtigungseinstellungen pro Bereich
- Beispiele: HR-Dokumente, Betriebshandbuch, Schulungsmaterialien

### Zugriff auf verschiedene Bereiche
- Direkte Route pro Bereich
- Bereichsauswahl (falls implementiert)
- Separate Desktop-Symbole fuer jeden Bereich

## Berechtigungssystem

**Drei Berechtigungsstufen:**

1. **Leseberechtigung** (`READ_DOCUMENT`): Bereiche, Kategorien und Dokumente anzeigen
2. **Schreibberechtigung** (`WRITE_DOCUMENT`): Dokumente und Kategorien erstellen/bearbeiten, sortieren
3. **Loeschberechtigung** (`DELETE_DOCUMENT`): Dokumente und Kategorien loeschen

Berechtigungen werden pro Dokumentbereich und pro Rolle konfiguriert. Kein Zugriff ohne zugewiesene Rolle.

## Tipps und Empfehlungen

**Dokumente organisieren:**
- Klare, beschreibende Kategorienamen erstellen
- Einheitliche Namenskonventionen verwenden
- Kategorienanzahl ueberschaubar halten (unter 20 pro Bereich)
- Dokumentnotizen fuer internen Kontext nutzen

**Inhalte erstellen:**
- Klare Dokumenttitel vergeben
- Ueberschriften im Inhalt fuer Struktur verwenden
- Dokumente fokussiert halten (ein Thema pro Dokument)
- Rich-Text-Formatierung fuer Lesbarkeit nutzen

## Fehlerbehebung

### Dokument kann nicht erstellt werden
- `WRITE_DOCUMENT`-Berechtigung pruefen
- Bereichsbezogene Schreibberechtigung pruefen
- Seite aktualisieren

### Dokument kann nicht bearbeitet werden
- Schreibberechtigung fuer den Bereich pruefen
- Titel und Kategorie muessen ausgefuellt sein
- Auf Fehlermeldungen in Toast-Benachrichtigungen achten

### Dokumente werden nicht geladen
- Leseberechtigung fuer den Bereich pruefen
- Suchfilter loeschen (falls aktiv)
- Seite aktualisieren

### Sortierung wird nicht gespeichert
- Schreibberechtigung pruefen
- "Reihenfolge speichern"-Button geklickt?
- Internetverbindung pruefen

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Dateimanager]] - Dateispeicherung und -verwaltung
- [[Berichte]] - Berichtserstellung und -verwaltung
- [[Nachrichten]] - Internes Nachrichtensystem
