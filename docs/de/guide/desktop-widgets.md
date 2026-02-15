# Desktop-Widgets

Desktop-Widgets bieten schnellen Zugriff auf Informationen und Funktionen, ohne vollständige Anwendungen öffnen zu müssen.

## Überblick

K-Systems bietet mehrere Desktop-Widgets, die frei auf dem Desktop positioniert werden können:

- **Notizen-Widget (Post-Its)** - Schnelle Haftnotizen
- **Kalender-Widget** - Anzeige heutiger Termine
- **Wetter-Widget** - Aktuelles Wetter und Vorhersage

Jedes Widget kann unabhängig verschoben, in der Größe geändert, minimiert und geschlossen werden.

## Widget-Container

Alle Widgets teilen gemeinsame Funktionen durch das Widget-Container-System.

### Widget-Steuerung

**Kopfzeile:**
- **Symbol und Titel**: Identifiziert das Widget
- **Minimieren-Schaltfläche (−)**: Widget auf Titelleiste reduzieren
- **Schließen-Schaltfläche (×)**: Widget vom Desktop entfernen

**Widget-Fenster:**
- **Verschiebbar**: Titelleiste anklicken und ziehen, um Widget zu verschieben
- **Größe änderbar**: Untere rechte Ecke ziehen, um Größe zu ändern
- **Automatisches Speichern**: Position und Größe werden automatisch gespeichert
- **Fokusverwaltung**: Widget anklicken, um es in den Vordergrund zu bringen

### Widgets verschieben

**Widget neu positionieren:**
1. Auf Widget-Kopfzeile (Titelleiste) klicken
2. An gewünschte Position ziehen
3. Maustaste loslassen
4. Position wird automatisch gespeichert

**Einschränkungen:**
- Widgets bleiben im rechten Bereich des Desktops
- Mindestabstand von 20px zu Bildschirmrändern
- Können nicht außerhalb des Bildschirms verschoben werden
- Werden bei Auflösungsänderung automatisch neu positioniert

### Größe von Widgets ändern

**Widget-Größe ändern:**
1. Über untere rechte Ecke fahren
2. Cursor ändert sich zu Größenänderungs-Symbol
3. Klicken und ziehen, um Größe zu ändern
4. Loslassen bei gewünschter Größe

**Größenbeschränkungen:**
- **Mindestbreite**: 200-320px (variiert je nach Widget)
- **Mindesthöhe**: 150-400px (variiert je nach Widget)
- **Maximalgröße**: Basierend auf Bildschirmauflösung
- Größenbeschränkungen verhindern unbrauchbare Layouts

### Widgets verwalten

**Widget minimieren:**
- Minimieren-Schaltfläche (−) klicken
- Widget wird nur auf Titelleiste reduziert
- Inhalt ausgeblendet, spart Platz
- Doppelklick auf Titelleiste zum Wiederherstellen
- Erneut Minimieren-Schaltfläche klicken zum Wiederherstellen

**Widget schließen:**
- Schließen-Schaltfläche (×) in Widget-Kopfzeile klicken
- Widget verschwindet vom Desktop
- Einstellungen und Daten bleiben gespeichert
- Erneut aktivieren über Widget-Menü oder Einstellungen

**Widget fokussieren:**
- Irgendwo auf Widget klicken
- Widget kommt in den Vordergrund (z-index erhöht)
- Andere Widgets dahinter
- Aktives Widget hat dezente Rahmenhervorhebung

## Notizen-Widget (Post-Its)

Schnelle Haftnotizen zum Notieren von Ideen, Erinnerungen und Aufgaben.

### Funktionen

**Kernfunktionalität:**
- Mehrzeiliger Texteditor
- Automatisches Speichern alle 1 Sekunde beim Tippen
- Automatisches Speichern alle 30 Sekunden im Leerlauf
- Monospace-Schrift für klare Lesbarkeit
- Speicherung im Backend oder localStorage (Fallback)

**Anzeige:**
- Grünes Titelleisten-Symbol (mdi-note-text)
- Saubere, ablenkungsfreie Oberfläche
- Automatisch wachsender Textbereich
- Zeichenlimit: Keines
- Standardgröße: 350x400px

### Notizen-Widget verwenden

**Notizen-Widget öffnen:**
1. Rechtsklick auf Desktop → Widgets → Notizen
2. Oder Einstellungen → Desktop → Widgets → Notizen aktivieren
3. Widget erscheint auf Desktop (Standard: rechte Seite, oben)

**Notiz hinzufügen:**
1. In Notizbereich klicken
2. Mit Tippen beginnen
3. Text wird automatisch gespeichert
4. Keine "Speichern"-Schaltfläche erforderlich

**Notiz bearbeiten:**
1. In Textbereich klicken
2. Inhalt frei bearbeiten
3. Änderungen werden automatisch gespeichert
4. Anzeige "Wird gespeichert..." erscheint während des Speicherns

**Verhalten des automatischen Speicherns:**
- **Beim Tippen**: Speichert 1 Sekunde nachdem Sie aufhören zu tippen
- **Im Leerlauf**: Speichert alle 30 Sekunden
- **Beim Schließen**: Speichert sofort beim Schließen des Widgets
- **Statusanzeige**: Zeigt "Wird gespeichert..." oder "Gespeichert [Zeit]"

### Notizen-Aktionen

**Notizen löschen:**
1. Löschen-Schaltfläche (🗑️) in Fußzeile klicken
2. Bestätigungsdialog erscheint
3. Bestätigen, um gesamten Inhalt zu löschen
4. Leerer Zustand wiederhergestellt

**Notizen herunterladen:**
1. Download-Schaltfläche (📥) in Fußzeile klicken
2. Notizen als Textdatei gespeichert
3. Dateiname: `notes-JJJJ-MM-TT.txt`
4. Download in Download-Ordner des Browsers

**Zuletzt gespeichert:**
- Fußzeile zeigt letzte Speicherzeit
- "Gerade eben" (< 1 Minute her)
- "Vor X Minuten" (< 1 Stunde her)
- Zeitstempel (> 1 Stunde her)

### Einstellungen des Notizen-Widgets

**Standardkonfiguration:**
- **Position**: X: 700px von rechts, Y: 20px von oben
- **Größe**: 350px breit, 400px hoch
- **Mindestgröße**: 300px breit, 300px hoch
- **Maximalgröße**: 600px breit (oder Bildschirmbreite - 120px), 800px hoch
- **Notiz-ID**: `widget-note-default`

**Mehrere Notizen-Widgets:**
- Aktuell wird ein Notizen-Widget unterstützt
- Mehrere Notiz-IDs können konfiguriert werden
- Jede ID speichert separaten Notizinhalt
- Zukunft: Unterstützung für mehrere gleichzeitige Notizen-Widgets

### Tipps für Notizen-Widget

**Best Practices:**
- Für schnelle temporäre Notizen verwenden
- Wichtige Notizen für permanente Speicherung herunterladen
- Alte Notizen regelmäßig löschen
- Zeilenumbrüche zur Organisation verwenden
- Notizen fokussiert und prägnant halten

**Anwendungsfälle:**
- Schnelle Erinnerungen
- Besprechungsnotizen
- Telefonnummern
- URLs und Links
- To-Do-Listen
- Code-Snippets
- Ideen und Brainstorming

## Kalender-Widget

Anzeige heutiger Termine und schneller Zugriff auf die vollständige Kalenderanwendung.

### Funktionen

**Kernfunktionalität:**
- Zeigt heutiges Datum
- Zeigt bis zu 5 anstehende Termine
- Ganztägige Termine werden zuerst angezeigt
- Nach Zeit sortierte Terminliste
- Automatische Aktualisierung alle 5 Minuten
- Termin anklicken, um im vollständigen Kalender anzuzeigen

**Anzeige:**
- Lila Titelleisten-Symbol (mdi-calendar)
- Saubere Terminkarten
- Farbcodierte Termine
- Standortanzeigen
- Standardgröße: 350x450px

### Kalender-Widget verwenden

**Kalender-Widget öffnen:**
1. Rechtsklick auf Desktop → Widgets → Kalender
2. Oder Einstellungen → Desktop → Widgets → Kalender aktivieren
3. Widget erscheint auf Desktop (Standard: linke Seite, oben)

**Heutige Termine anzeigen:**
- Widget zeigt aktuelles Datum (z.B. "Montag, 1. Januar 2025")
- "Heute"-Badge angezeigt
- Termine chronologisch aufgelistet
- Ganztägige Termine oben angezeigt
- Zeitlich festgelegte Termine darunter mit Zeitstempeln

**Terminanzeige:**
- **Terminzeit**: Links angezeigt (z.B. "09:00")
- **Termintitel**: Hauptüberschrift
- **Standort**: Mit Kartensymbol angezeigt (falls festgelegt)
- **Farbbalken**: Linker Rand zeigt Terminfarbe
- **Ganztägige Termine**: Kalendersymbol statt Zeit

**Mit Terminen interagieren:**
- Über Terminkarte fahren für dezente Hervorhebung
- Klicken, um vollständige Kalender-App zu öffnen
- Durch Termine scrollen, wenn mehr als 5

### Kalender-Aktionen

**Weitere Termine anzeigen:**
- "Weitere anzeigen"-Schaltfläche erscheint bei mehr als 5 Terminen
- Zeigt Anzahl (z.B. "+3 weitere Termine")
- Klicken, um vollständige Kalenderanwendung zu öffnen

**Vollständigen Kalender öffnen:**
- "Kalender anzeigen"-Schaltfläche unten klicken
- Öffnet Kalender-App im Desktop-Modus oder wechselt zur Kalenderseite
- Zeigt vollständige Monatsansicht mit allen Terminen

**Termine aktualisieren:**
- Widget wird automatisch alle 5 Minuten aktualisiert
- Manuelle Aktualisierung: Widget schließen und wieder öffnen
- Ladespinner während des Abrufens

### Einstellungen des Kalender-Widgets

**Standardkonfiguration:**
- **Position**: X: 20px von rechts, Y: 120px von oben
- **Größe**: 350px breit, 450px hoch
- **Mindestgröße**: 300px breit, 350px hoch
- **Maximalgröße**: 500px breit (oder Bildschirmbreite - 120px), 600px hoch
- **Aktualisierungsintervall**: 5 Minuten (300 Sekunden)

**Termindatenquelle:**
- Abrufen von `/calendar/?action=getEventsToday`
- Zeigt nur Termine für aktuellen Tag
- Enthält ganztägige und zeitlich festgelegte Termine
- Farbcodiert basierend auf Kalendereinstellungen

### Zustände des Kalender-Widgets

**Ladezustand:**
- Fortschrittsspinner angezeigt
- Anzeige "Wird geladen..."
- Angezeigt während Abrufen von Terminen aus Backend

**Fehlerzustand:**
- Rotes Warnsymbol
- Fehlermeldung angezeigt
- "Erneut versuchen"-Schaltfläche zum erneuten Abrufen
- Fallback auf Mock-Daten für Demo

**Keine Termine:**
- Leeres Kalendersymbol
- Meldung "Keine Termine heute"
- Sauberer, leerer Zustand
- Ermutigt zum Erstellen neuer Termine

**Termine geladen:**
- Termine in Karten angezeigt
- Scrollbare Liste bei vielen Terminen
- Zeit- und Standortdetails
- Schnellzugriff auf vollständigen Kalender

## Wetter-Widget

Anzeige aktueller Wetterbedingungen und 5-Tage-Vorhersage.

### Funktionen

**Kernfunktionalität:**
- Aktuelle Temperatur (°C)
- Wetterzustandsbeschreibung
- "Gefühlte" Temperatur
- Luftfeuchtigkeit in Prozent
- Windgeschwindigkeit (km/h)
- Atmosphärischer Druck (hPa)
- Sichtweite (km)
- 5-Tage-Vorhersage
- Automatische Aktualisierung alle 10 Minuten

**Anzeige:**
- Blaues Titelleisten-Symbol (mdi-weather-partly-cloudy)
- Großes Wettersymbol (farbcodiert)
- Prominente Temperaturanzeige
- Detailliertes Wetterstatistik-Raster
- Kompakte Vorhersagekarten
- Standardgröße: 280x400px

### Wetter-Widget verwenden

**Wetter-Widget öffnen:**
1. Rechtsklick auf Desktop → Widgets → Wetter
2. Oder Einstellungen → Desktop → Widgets → Wetter aktivieren
3. Widget erscheint auf Desktop (Standard: linke Seite, oben)

**Aktuelle Wetteranzeige:**
- **Großes Wettersymbol**: Visuelle Darstellung (Sonne, Wolken, Regen, etc.)
- **Temperatur**: Große, fette Zahlen (z.B. "22°C")
- **Standort**: "Los Santos" (fest für K-Systems)
- **Zustand**: Textbeschreibung (z.B. "Teilweise bewölkt")
- **Gefühlt wie**: Gefühlte Temperatur

**Wetterdetails-Raster:**
- **Luftfeuchtigkeit**: Prozentsatz mit Wassertropfen-Symbol
- **Windgeschwindigkeit**: km/h mit Wind-Symbol
- **Druck**: hPa mit Messgerät-Symbol
- **Sichtweite**: km mit Augen-Symbol

### Wettervorhersage

**5-Tage-Vorhersagekarten:**
- Heute bis 4 Tage voraus
- Tagesname (z.B. "Heute", "Morgen", "Mi")
- Wettersymbol (farbcodiert)
- Höchsttemperatur (groß)
- Tiefsttemperatur (kleiner, gedämpft)

**Vorhersageanzeige:**
- Horizontales Kartenlayout
- Gleichgroße Karten
- Hover-Effekt auf jedem Tag
- Farbcodierte Symbole:
  - **Sonne**: Orange (klar/sonnig)
  - **Wolken**: Grau (bewölkt)
  - **Regen**: Blau (regnerisch)
  - **Sturm**: Lila (Gewitter)
  - **Schnee**: Hellblau (Schnee)

### Wettersymbole

**Automatische Symbolauswahl:**
- Basierend auf Zustandsstring
- **Sonnig/Klar**: `mdi-weather-sunny` (orange)
- **Bewölkt**: `mdi-weather-cloudy` (grau)
- **Regnerisch**: `mdi-weather-rainy` (blau)
- **Sturm/Gewitter**: `mdi-weather-lightning` (lila)
- **Schnee**: `mdi-weather-snowy` (hellblau)
- **Nebel/Dunst**: `mdi-weather-fog` (grau)
- **Windig**: `mdi-weather-windy` (blaugrau)
- **Standard**: `mdi-weather-partly-cloudy` (blaugrau)

### Einstellungen des Wetter-Widgets

**Standardkonfiguration:**
- **Position**: X: 20px von rechts, Y: 80px von oben
- **Größe**: 280px breit, 400px hoch
- **Mindestgröße**: 320px breit, 400px hoch
- **Maximalgröße**: 450px breit (oder Bildschirmbreite - 120px), 600px hoch
- **Aktualisierungsintervall**: 10 Minuten (600 Sekunden)
- **Standort**: Los Santos (fest)

**Datenquelle:**
- Abrufen von `/weather/?action=getWeather`
- Gibt Array von Wettertagen zurück
- Erster Tag als aktuelles Wetter verwendet
- Bis zu 5 Tage für Vorhersage
- Fallback auf Mock-Daten bei Fehler

### Zustände des Wetter-Widgets

**Ladezustand:**
- Fortschrittsspinner angezeigt
- Anzeige "Wird geladen..."
- Angezeigt während Abrufen von Wetterdaten

**Fehlerzustand:**
- Rotes Tornado-Symbol
- Fehlermeldung angezeigt
- "Erneut versuchen"-Schaltfläche zum erneuten Abrufen
- Fallback auf Mock-Daten:
  - Temperatur: 22°C
  - Zustand: Teilweise bewölkt
  - Standort: Los Santos
  - Demo-Vorhersagedaten

**Letzte Aktualisierung:**
- Fußzeile zeigt letzte Aktualisierungszeit
- "Gerade eben" (< 1 Minute her)
- "Vor X Minuten" (< 1 Stunde her)
- "Vor X Stunden" (> 1 Stunde her)
- Aktualisierungs-Symbol-Anzeige

## Widgets hinzufügen/entfernen

### Widgets aktivieren

**Methode 1: Kontextmenü**
1. Rechtsklick auf Desktop
2. "Widgets" aus Menü wählen
3. Widget ankreuzen zum Aktivieren:
   - ☐ Notizen
   - ☐ Kalender
   - ☐ Wetter
4. Widget erscheint sofort

**Methode 2: Einstellungen**
1. Start → Einstellungen → Desktop klicken
2. Zu Abschnitt "Widgets" gehen
3. Widgets ein-/ausschalten
4. Übernehmen klicken
5. Aktivierte Widgets erscheinen

### Widgets deaktivieren

**Methode 1: Schließen-Schaltfläche**
1. × in Widget-Kopfzeile klicken
2. Widget verschwindet sofort
3. Kann später wieder aktiviert werden
4. Einstellungen bleiben erhalten

**Methode 2: Einstellungen**
1. Einstellungen → Desktop → Widgets
2. Widget abwählen
3. Änderungen übernehmen
4. Widget schließt

**Methode 3: Kontextmenü**
1. Rechtsklick auf Desktop
2. Widgets-Menü
3. Widget abwählen
4. Widget schließt

## Widget-Positionierung

### Standardpositionen

Widgets erscheinen beim ersten Aktivieren in Standardpositionen:

**Notizen-Widget:**
- X: 700px vom rechten Rand
- Y: 20px von oben
- Rechte Seite des Bildschirms

**Kalender-Widget:**
- X: 20px vom rechten Rand
- Y: 120px von oben
- Oberer rechter Bereich

**Wetter-Widget:**
- X: 20px vom rechten Rand
- Y: 80px von oben
- Obere rechte Ecke

### Benutzerdefinierte Positionierung

**Widgets verschieben:**
1. Widget-Kopfzeile anklicken und ziehen
2. Irgendwo auf rechter Seite positionieren
3. Loslassen zum Ablegen
4. Position wird automatisch gespeichert

**Position zurücksetzen:**
- Widget schließen
- Aus Menü wieder aktivieren
- Widget kehrt zu Standardposition zurück
- Oder manuell zu bevorzugter Position verschieben

**Positionsbeschränkungen:**
- Widgets auf rechten Bereich beschränkt
- Mindestens 20px von Bildschirmrändern
- Können nicht außerhalb des Bildschirms verschoben werden
- Bei Auflösungsänderung automatisch angepasst
- Position pro Benutzer gespeichert

## Widget-Daten und Speicherung

### Datenbeständigkeit

**Notizen-Widget:**
- **Backend**: Speichert an `/notes/` Endpunkt
- **Fallback**: localStorage (`widget-notes-{noteId}`)
- **Format**: JSON mit Inhalt und Zeitstempel
- **Automatisches Speichern**: Alle 1 Sekunde (debounced)
- **Backup**: Alle 30 Sekunden

**Kalender-Widget:**
- **Datenquelle**: `/calendar/?action=getEventsToday`
- **Cache**: Client-seitig, 5-Minuten-Aktualisierung
- **Keine Speicherung**: Nur-Lese-Anzeige
- **Echtzeit**: Spiegelt Kalenderänderungen wider

**Wetter-Widget:**
- **Datenquelle**: `/weather/?action=getWeather`
- **Cache**: Client-seitig, 10-Minuten-Aktualisierung
- **Keine Speicherung**: Nur-Lese-Anzeige
- **Fallback**: Mock-Daten bei Fehler

### Widget-Einstellungen

**Gespeicherte Einstellungen:**
- Widget-Position (x, y)
- Widget-Größe (Breite, Höhe)
- Widget-Zustand (minimiert, fokussiert)
- Aktiviert/Deaktiviert-Zustand

**Speicherort:**
- Benutzerpräferenzen im Backend
- localStorage-Backup
- Pro Benutzer, pro Widget
- Über Sitzungen synchronisiert

## Tipps und Best Practices

### Produktivitätstipps

**Notizen-Widget:**
- Für temporäre Informationen verwenden
- Wichtige Notizen herunterladen
- Regelmäßig löschen, um Unordnung zu vermeiden
- Ein Widget = ein Thema/Projekt

**Kalender-Widget:**
- Schneller Blick auf Zeitplan
- An gut sichtbarer Stelle anheften
- Für Tagesagenda verwenden
- Vollständiger Kalender für detaillierte Planung

**Wetter-Widget:**
- Tag basierend auf Vorhersage planen
- Vor dem Verlassen prüfen
- Wo leicht sichtbar positionieren
- Für schnelle Wetterupdates verwenden

### Organisation

**Widget-Platzierung:**
- Häufig verwendete Widgets höher platzieren
- Verwandte Widgets zusammen gruppieren
- Abstand zwischen Widgets lassen
- Widgets nicht überlappen

**Widget-Verwaltung:**
- Nicht verwendete Widgets schließen
- Bei Bedarf wieder aktivieren
- Nur wesentliche Widgets offen halten
- Vollständige Apps für detaillierte Arbeit verwenden

### Leistung

**Leistung optimieren:**
- Auf 2-3 aktive Widgets beschränken
- Bei Nichtbenutzung schließen
- Überlappende Widgets vermeiden
- Automatische Aktualisierung Updates handhaben lassen

**Last reduzieren:**
- Widgets nicht manuell aktualisieren
- Automatischem Speichern für Notizen vertrauen
- Widgets bei intensiven Aufgaben schließen
- Bei Bedarf wieder aktivieren

## Fehlerbehebung

### Widget lässt sich nicht öffnen

**Lösung:**
1. Prüfen, ob bereits geöffnet (auf Desktop nach Widget suchen)
2. Schließen und erneut öffnen versuchen
3. Seite aktualisieren (F5)
4. Browser-Cache leeren
5. Browser-Konsole auf Fehler prüfen

### Widget-Position verloren

**Lösung:**
1. Widget manuell neu positionieren
2. Position wird automatisch gespeichert
3. Bei Fortbestehen localStorage prüfen
4. Administrator kontaktieren, wenn Einstellungen nicht speichern

### Notizen werden nicht gespeichert

**Lösung:**
1. Internetverbindung prüfen
2. Backend-Erreichbarkeit überprüfen
3. Browser-Konsole auf Fehler prüfen
4. Notizen fallen auf localStorage zurück
5. Notizen als Backup herunterladen versuchen

### Kalendertermine laden nicht

**Lösung:**
1. Internetverbindung prüfen
2. Kalenderberechtigung überprüfen
3. Erneut versuchen-Schaltfläche klicken
4. Widget aktualisieren (schließen und erneut öffnen)
5. Prüfen, ob Kalendermodul aktiviert ist

### Wetter wird nicht aktualisiert

**Lösung:**
1. Internetverbindung prüfen
2. Auf automatische Aktualisierung warten (10 Minuten)
3. Widget schließen und erneut öffnen
4. Bei Fehleranzeige Erneut versuchen klicken
5. Erreichbarkeit des Wetter-Endpunkts überprüfen

### Widget außerhalb des Bildschirms

**Lösung:**
1. Widget schließen (bei Bedarf über Einstellungen)
2. Widget wieder aktivieren
3. Erscheint an Standardposition
4. Nach Wunsch neu positionieren

**Automatische Korrektur:**
- Widgets werden automatisch auf Bildschirm beschränkt
- Bei Fenstergrößenänderung passen sich Widgets an
- Können nicht außerhalb des Bildschirms verschoben werden
- Automatische Begrenzungsprüfung

## Erweiterte Funktionen

### Widget-Fokusverwaltung

**Z-Index-System:**
- Widgets stapeln basierend auf Fokus
- Widget anklicken, um in den Vordergrund zu bringen
- Zuletzt geklicktes Widget oben
- Automatische z-index-Verwaltung

**Fokusverhalten:**
- Widget anklicken: Sendet Fokus-Event
- Außerhalb klicken: Entfernt Fokus
- Aktives Widget mit dezenter Rahmenhervorhebung
- Fokussiertes Widget interagierbar

### Widget-Kommunikation

**Event-System:**
- `update:position`: Position geändert
- `close`: Widget geschlossen
- `focus`: Widget fokussiert
- Eltern-Container behandelt Events

**Integration:**
- Kalender-Widget öffnet Kalender-App
- Wetter-Widget verlinkt zu Wettermodul (zukünftig)
- Notizen-Widget speichert in Notizen-Modul
- Modulübergreifende Kommunikation

## Zukünftige Erweiterungen

**Geplante Funktionen:**
- Mehrere Notizen-Widgets mit verschiedenen IDs
- Wetter-Standort-Anpassung
- Kalender-Widget-Filterung
- Widget-Themes und Farben
- Weitere Widget-Typen (Aufgaben, E-Mails, Statistiken)
- Widget-Profile (Arbeit, Privat)
- Widget-Freigabe (falls erlaubt)

---

## Nächste Schritte

- [🎨 Desktop-Anpassung](/de/guide/desktop-anpassung)
- [🖥️ Desktop-Oberfläche](/de/guide/desktop-interface)
- [📅 Kalender-Anleitung](/de/guide/kalender)
- [⚙️ Benutzereinstellungen](/de/guide/benutzereinstellungen)
