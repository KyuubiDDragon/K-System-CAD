# Wetter-Widget-Konfiguration

Wetter-API-Integration, Standorteinstellungen, Anzeigeeinstellungen und Wetterwarnungen für das K-Systems-Wetter-Widget konfigurieren.

## Überblick

Das Wetter-Widget bietet Echtzeit-Wetterinformationen, die im gesamten System angezeigt werden, einschließlich Desktop-Oberfläche, Dashboard und mobile App.

**Funktionen:**
- Mehrere Wetter-API-Unterstützung
- Standortbasiertes Wetter
- Mehrere Standortverfolgung
- Aktuelle Bedingungen
- Stündliche Vorhersagen
- Erweiterte Vorhersagen (7-14 Tage)
- Wetterwarnungen und -warnungen
- Radar und Karten
- Anpassbare Anzeige
- Widget-Platzierungskontrolle
- Automatische Aktualisierungen

## Anforderungen

**Erforderliche Berechtigung:** `ADMIN_SYSTEM` oder `ADMIN_WEATHER`

**Zugriffsebene:** Administrator oder Super Administrator

**Externe Anforderungen:**
- Wetter-API-Schlüssel (OpenWeather, Weather.gov, etc.)
- Internetkonnektivität
- Gültige Standortkoordinaten

## Wettereinstellungen öffnen

**Desktop:** Start → Administration → Wetterkonfiguration
**Menü:** Administration → System → Wetter
**Route:** `/admin/weather`

## Wetter-API-Konfiguration

### Wetteranbieter auswählen

**Unterstützte Anbieter:**

**OpenWeather API:**
- **Vorteile**: Globale Abdeckung, detaillierte Daten, zuverlässig
- **Nachteile**: Erfordert API-Schlüssel, Ratenlimits
- **Kostenloser Tarif**: 1000 Aufrufe/Tag
- **Website**: openweathermap.org

**Weather.gov (NWS):**
- **Vorteile**: Kostenlos, kein API-Schlüssel, US-Abdeckung
- **Nachteile**: Nur USA, langsamere Updates
- **Abdeckung**: Nur Vereinigte Staaten
- **Website**: weather.gov

**WeatherAPI.com:**
- **Vorteile**: Einfache Einrichtung, guter kostenloser Tarif
- **Kostenloser Tarif**: 1M Aufrufe/Monat

**Benutzerdefinierte API:**
- Benutzerdefinierten Endpunkt konfigurieren
- Datenformat definieren
- Antwortfelder zuordnen

### OpenWeather-Konfiguration

**OpenWeather einrichten:**
1. Zu Tab **API-Konfiguration** gehen
2. **OpenWeather API** auswählen
3. API-Schlüssel eingeben:

**API-Schlüssel erhalten:**
1. openweathermap.org besuchen
2. Konto erstellen
3. Zu API-Schlüssel gehen
4. Neuen Schlüssel generieren
5. Schlüssel kopieren

**Konfiguration:**
- **API-Schlüssel**: Ihren Schlüssel einfügen (erforderlich)
- **API-Version**: 2.5 (aktuell) oder 3.0
- **Einheiten**:
  - Imperial (°F, mph, in)
  - Metrisch (°C, m/s, mm)
  - Kelvin (wissenschaftlich)
- **Sprache**: English (en), Spanish (es), German (de), French (fr)

**Erweiterte Einstellungen:**
- **Aktualisierungsintervall**: Minuten (Standard: 15)
- **Cache-Dauer**: Minuten (Standard: 10)
- **Timeout**: Sekunden (Standard: 10)
- **Wiederholungsversuche**: Anzahl (Standard: 3)

**Verbindung testen:**
1. Klicken Sie auf **API-Verbindung testen**
2. System macht Testaufruf
3. Ergebnis anzeigen
4. Bei Bedarf Probleme beheben

### Weather.gov-Konfiguration

**Weather.gov einrichten:**
1. **Weather.gov (NWS)** auswählen
2. Konfigurieren:

**Einstellungen:**
- **User Agent**: Von NWS erforderlich, Format: (IhreOrganisation, kontakt@email.com)
- **Einheiten**: Nur Imperial (NWS-Standard)
- **Aktualisierungsintervall**: Minuten (Standard: 30)

**Hinweise:**
- Kein API-Schlüssel erforderlich
- Nur US-Standorte
- Offizielle NWS-Daten

## Standortverwaltung

### Standorte hinzufügen

**Wetterstandort hinzufügen:**
1. Zu Tab **Standorte** gehen
2. Klicken Sie auf **+ Standort hinzufügen**
3. Standortinformationen eingeben:

**Standortdetails:**
- **Name**: Standort-Spitzname (z.B. "Hauptsitz", "Station 1")
- **Typ**: Hauptsitz, Station, Büro, Remote-Standort
- **Adresse**: Vollständige Straßenadresse
- **Stadt**: Stadtname
- **Bundesland/Provinz**: Bundeslandcode
- **Land**: Ländercode
- **PLZ/Postleitzahl**: Postleitzahl

**Koordinaten:**
- **Breitengrad**: Dezimalgrad (z.B. 34.0522)
- **Längengrad**: Dezimalgrad (z.B. -118.2437)
- **Höhe**: Meter (optional)

**Anzeigeeinstellungen:**
- **Primärstandort**: Hauptwetteranzeige
- **Im Widget anzeigen**: In Rotation einschließen
- **Warnung aktiviert**: Wetterwarnungen erhalten
- **Symbol**: Standortsymbol/-markierung

4. Klicken Sie auf **Standort speichern**

### Nach Standort suchen

**Standort finden:**
1. Klicken Sie auf **Standort suchen**
2. Eingeben: Stadtname, Adresse, PLZ, Koordinaten
3. Aus Ergebnissen auswählen
4. Koordinaten automatisch ausgefüllt
5. Verifizieren und speichern

**Geocodierung:**
- System konvertiert Adresse zu Koordinaten
- Verwendet Geocodierungsdienst
- Validiert Standort
- Zeigt auf Karte

### Standorte verwalten

**Standortliste:**
- Alle gespeicherten Standorte anzeigen
- Aktuelle Wettervorschau
- Zuletzt aktualisierte Zeit
- Status (aktiv/Fehler)
- Aktionen: Bearbeiten, Löschen, Als primär festlegen

**Standort bearbeiten:**
1. Klicken Sie auf **Bearbeiten** bei Standort
2. Details ändern
3. Änderungen speichern
4. Widget aktualisiert automatisch

**Standort löschen:**
1. Klicken Sie auf **Löschen**
2. Löschung bestätigen
3. Primärstandort kann nicht gelöscht werden
4. Zuerst neuen Primärstandort festlegen

**Primärstandort festlegen:**
1. Klicken Sie auf **Als primär festlegen**
2. Neuer primärer Wetterstandort
3. Widget aktualisiert
4. Desktop zeigt dieses Wetter

## Anzeigeeinstellungen

### Widget-Erscheinungsbild

**Widget-Anzeige konfigurieren:**
1. Zu Tab **Anzeige** gehen
2. Erscheinungsbild anpassen:

**Widget-Stil:**
- **Layout**: Kompakt (Klein, wesentliche Info), Standard (Mittel, mehr Details), Detailliert (Groß, alle Informationen)
- **Theme**: Hell, Dunkel, Auto, Transparent

**Informationsanzeige:**
- Aktuelle Temperatur
- "Gefühlt wie" Temperatur
- Aktuelle Bedingungen
- Wettersymbol
- Standortname
- Windgeschwindigkeit und -richtung
- Luftfeuchtigkeit-Prozentsatz
- Druck
- Sichtbarkeit
- UV-Index
- Sonnenauf-/-untergangszeiten
- Zuletzt aktualisierte Zeit

**Temperaturanzeige:**
- **Anzeigen als**: Groß, Mittel, Klein
- **Dezimalstellen**: 0 oder 1
- **Einheitsanzeige**: °F/°C anzeigen

**Vorhersageanzeige:**
- Stündliche Vorhersage anzeigen (Wie viele Stunden: 6, 12, 24)
- Tägliche Vorhersage anzeigen (Wie viele Tage: 3, 5, 7, 10)
- Vorhersagediagramm anzeigen (Liniendiagramm, Temperaturtrend)

**Farben:**
- **Textfarbe**: Widget-Text
- **Hintergrundfarbe**: Widget-Hintergrund
- **Akzentfarbe**: Hervorhebungen
- **Temperaturfarben**: Heiß (Rottöne), Warm (Orange), Moderat (Gelb/Grün), Kühl (Blau), Kalt (Dunkelblau)

### Animationseinstellungen

**Animationen konfigurieren:**
- Animationen aktivieren
- Animierte Wettersymbole (Regenanimation, Schneeanimation, Bewegende Wolken)
- Übergangseffekte (Ausblenden, Schieben, Keine)
- **Animationsgeschwindigkeit**: Langsam, Normal, Schnell

**Hintergrundeffekte:**
- Partikeleffekte (Regentropfen, Schneeflocken, Wolken)
- Wetterbasierter Hintergrund
- Dynamische Farben

### Einheiten & Format

**Einheiten konfigurieren:**
- **Temperatur**: Fahrenheit (°F), Celsius (°C)
- **Windgeschwindigkeit**: mph, km/h, m/s, Knoten
- **Druck**: inHg, hPa, mmHg
- **Niederschlag**: Zoll (in), Millimeter (mm)
- **Entfernung/Sichtbarkeit**: Meilen (mi), Kilometer (km)

**Datum/Uhrzeit-Format:**
- **Datum**: MM/TT/JJJJ oder TT/MM/JJJJ
- **Zeit**: 12-Stunden oder 24-Stunden
- **Zeitzone**: Standortzeitzone oder Benutzerpräferenz

## Widget-Platzierung

### Desktop-Widget

**Desktop-Platzierung:**
1. Zu Tab **Platzierung** gehen
2. Desktop-Widget konfigurieren:

**Position:**
- **Standort**: Obere Leiste (Standard), Desktop-Ecke, Seitenleiste, Dashboard, Versteckt
- **Ecke** (falls Desktop-Ecke): Oben-Links, Oben-Rechts, Unten-Links, Unten-Rechts
- **Offset**: Pixel vom Rand (X-Offset, Y-Offset)

**Sichtbarkeit:**
- Auf Desktop anzeigen
- Auf Login-Bildschirm anzeigen
- In oberer Leiste anzeigen
- Standardmäßig minimieren
- Zum Erweitern klicken
- Immer erweitert

**Größe:**
- **Breite**: Pixel oder Prozentsatz (Auto, Fest 200-600px, Prozentsatz 20-50%)
- **Höhe**: Auto oder fest
- **Responsive**: An Bildschirmgröße anpassen

### Dashboard-Widget

**Dashboard-Integration:**
1. Dashboard-Widget aktivieren
2. Konfigurieren:

**Dashboard-Einstellungen:**
- Im Dashboard anzeigen
- **Größe**: Klein, Mittel, Groß, Volle Breite
- **Position**: Dashboard-Rasterposition
- **Aktualisieren**: Auto-Aktualisierungsintervall
- **Erweiterbar**: Für Details klicken

### Mobile Widget

**Mobile Konfiguration:**
- In mobiler App anzeigen
- **Position**: Obere Leiste, Dashboard-Karte, Tab-Leiste
- **Tap-Aktion**: Details anzeigen, Volles Wetter öffnen, Erweitern umschalten

## Wetterwarnungen

### Warnungskonfiguration

**Warnungen konfigurieren:**
1. Zu Tab **Warnungen** gehen
2. Warnsystem aktivieren:

**Warnungseinstellungen:**
- Wetterwarnungen aktivieren
- Im Widget anzeigen
- Benachrichtigungen anzeigen
- E-Mail-Warnungen senden
- Push-Benachrichtigungen senden
- SMS-Warnungen (falls konfiguriert)

**Warnungstypen:**
- Schwere Wetterwarnungen (Tornado-Warnung, Schweres Gewitter, Sturzflut)
- Watches (Tornado-Watch, Gewitter-Watch)
- Advisories (Winterwetter, Hitzewarnung, Windwarnung)
- Spezielle Statements
- Marine-Warnungen
- Feuerwetter

**Warnungsschwere:**
- **Extrem**: Rote Warnung (Sofortige Maßnahme erforderlich, Lebensgefährlich)
- **Schwer**: Orange Warnung (Gefährliche Bedingungen)
- **Moderat**: Gelbe Warnung (Bewusst sein)
- **Gering**: Blaue Info (Informiert bleiben)

### Warnungsbenachrichtigungen

**Benachrichtigungseinstellungen:**
- **Wer erhält Warnungen**: Alle Benutzer, Spezifische Rollen, Benutzer am Standort
- **Benachrichtigungsmethode**: In-App, E-Mail, Push, SMS, Desktop

**Warnungsanzeige:**
- **Banner**: Prominentes Banner (Oben auf Bildschirm, Auto-Dismiss oder manuell)
- **Badge**: Symbol-Badge (Warnungszähler, Schweregrad-Farbe)
- **Sound**: Warnungston

### Warnungsaktionen

**Automatisierte Aktionen:**
- Warnung im System protokollieren
- Vorfall erstellen (falls schwer)
- Leitstelle benachrichtigen
- Broadcast-Nachricht senden
- Benutzerdefinierten Workflow auslösen

**Warnungsreaktion:**
- Als bestätigt markieren
- Notizen hinzufügen
- Aktionselemente zuweisen
- Warnung verwerfen
- Warnung schlummern

## Radar & Karten

### Wetterradar

**Radar aktivieren:**
1. Zu Tab **Radar** gehen
2. Radaranzeige konfigurieren:

**Radar-Einstellungen:**
- Radaransicht aktivieren
- **Radarquelle**: Wetteranbieter-Radar, NOAA/NWS-Radar
- **Radar-Ebenen**: Niederschlag, Sturmspuren, Blitze, Schweres Wetter, Satellitenwolken

**Radar-Anzeige:**
- **Animation**: Statisch aktuell, Animierte Schleife
- **Aktualisieren**: Minuten
- **Karten-Overlay**: Straßen, Satellit, Gelände
- **Zoom-Ebene**: Standard-Zoom

**Interaktive Funktionen:**
- Schwenken und zoomen
- Für Details klicken
- Zeitschieberegler
- Animation abspielen/pausieren
- Vollbildmodus

### Wetterkarten

**Karten aktivieren:**
- Temperaturkarte
- Niederschlagskarte
- Windkarte
- Wolkenbedeckungskarte
- Druckkarte

**Karten-Einstellungen:**
- Farbschema
- Konturlinien
- Legendenanzeige
- Transparenz
- Aktualisierungshäufigkeit

## Erweiterte Einstellungen

### API-Ratenlimitierung

**API-Nutzung verwalten:**
- **Ratenlimits**: Interne Limits festlegen (Aufrufe pro Minute/Stunde/Tag)
- **Nutzungsverfolgung**: Aktuelle Nutzung anzeigen, Historische Nutzung
- **Warnungen**: Bei Annäherung an Limit warnen

**Optimierung:**
- Cache-Dauer erhöhen
- Aktualisierungshäufigkeit reduzieren
- Standorte begrenzen
- Nicht verwendete Funktionen deaktivieren

### Daten-Caching

**Cache-Einstellungen:**
- **Cache-Typ**: Speicher-Cache (schnell), Datei-Cache (persistent), Datenbank-Cache (geteilt)
- **Cache-Dauer**: Aktuelle Bedingungen: 10 min, Stündliche Vorhersage: 30 min, Tägliche Vorhersage: 2 Stunden
- **Cache-Invalidierung**: Auto bei Update, Manuell leeren, Geplante Bereinigung

## Überwachung & Protokolle

### System-Überwachung

**Wettersystem überwachen:**
1. Tab **Überwachung** anzeigen
2. Metriken überprüfen:

**Zustandsmetriken:**
- **API-Status**: Oben/Unten
- **Letzte Aktualisierung**: Zeitstempel
- **Erfolgsrate**: Prozentsatz
- **Durchschnittliche Antwort**: Millisekunden
- **Fehler**: Fehlerzähler
- **Cache-Trefferrate**: Prozentsatz

**Standortstatus:**
- Status jedes Standorts
- Letzte erfolgreiche Aktualisierung
- Aktuelle Bedingungen
- Etwaige Fehler

**Nutzungsstatistiken:**
- API-Aufrufe heute
- API-Aufrufe diesen Monat
- Kosten (falls zutreffend)
- Verbleibende Quote

### Fehlerprotokolle

**Protokolle anzeigen:**
- Aktuelle Fehler
- API-Fehler
- Timeout-Fehler
- Ungültige Antworten
- Konfigurationsfehler

**Export-Protokolle:**
- Protokolldatei herunterladen
- Datumsbereichsauswahl
- Systeminformationen einschließen
- Zur Fehlerbehebung

## Best Practices

**Tun Sie:**
✅ Angemessenen API-Tarif für Nutzung verwenden
✅ Vernünftige Aktualisierungsintervalle festlegen
✅ Caching zur Reduzierung von API-Aufrufen aktivieren
✅ Warnungen vor Bereitstellung testen
✅ API-Nutzung und -Kosten überwachen
✅ API-Schlüssel sicher aufbewahren

**Nicht tun:**
❌ Zu häufig aktualisieren (API-Limits)
❌ API-Schlüssel öffentlich teilen
❌ Ratenlimit-Warnungen ignorieren
❌ Warnungstests überspringen
❌ Ungenaue Standorte verwenden
❌ Caching deaktivieren (verschwendet Aufrufe)

## Fehlerbehebung

### Wetter aktualisiert nicht
- API-Schlüsselgültigkeit überprüfen
- Internetverbindung verifizieren
- API-Ratenlimits überprüfen
- Fehlerprotokolle überprüfen
- API-Verbindung testen
- Cache leeren und erneut versuchen

### Falscher Standort Wetter
- Koordinaten korrekt verifizieren
- Standorteinstellungen überprüfen
- Geocodierung aktualisieren
- Mit bekannten Koordinaten testen

### Warnungen werden nicht angezeigt
- Warnsystem aktivieren
- Warnungseinstellungen überprüfen
- Benachrichtigungseinstellungen verifizieren
- Mit Beispielwarnung testen
- Benutzerpräferenzen überprüfen

### Widget wird nicht angezeigt
- Platzierungseinstellungen überprüfen
- Widget aktiviert verifizieren
- Browser-Cache leeren
- Auf JavaScript-Fehler prüfen
- Auf anderem Browser testen

---

## Nächste Schritte
- [⚙️ Systemeinstellungen](/guide/admin/system-settings)
- [🗺️ Kartenkonfiguration](/guide/admin/map)
- [🏢 Behördeneinstellungen](/guide/admin/authorities)
