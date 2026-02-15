# Karte & Standortverfolgung

Interaktives Kartensystem für Standortverfolgung, Markierungen, Kategorien, Geofencing und Routenplanung.

## Überblick

Funktionen:
- Interaktive Kartenoberfläche
- Benutzerdefinierte Markierungen und Pins
- Standortkategorien
- Echtzeit-Standortverfolgung
- Geofencing und Zonen
- Routenplanung
- Entfernungsberechnung
- Standortverlauf
- Adress-Geocodierung
- Standorte exportieren
- Karten drucken
- Mobile GPS-Integration
- Standortfreigabe

## Voraussetzungen

**Erforderliche Berechtigung:** `READ_MAP` (zum Ansehen), `WRITE_MAP` (zum Erstellen von Markierungen)

## Karte öffnen

**Desktop:** Doppelklick auf **Karte**-Symbol
**Menü:** Startmenü → Einsätze → Karte

## Oberflächenlayout

### Kartenpanel

**Hauptkartenansicht:**
- Interaktive Karte (Zoom, Schwenken)
- Markierungen für Standorte
- Cluster-Gruppen (viele Markierungen)
- Info-Popups bei Klick
- Suchfeld
- Ebenensteuerung

**Kartensteuerung:**
- **Hinein-/Herauszoomen**: + / - Schaltflächen
- **Vollbild**: Auf Vollbild erweitern
- **Karte zentrieren**: Zur Standardansicht zurückkehren
- **Mein Standort**: GPS-Standort (falls aktiviert)
- **Messwerkzeug**: Entfernungs-/Flächenmessung
- **Zeichenwerkzeuge**: Formen und Zonen

### Linke Seitenleiste

**Markierungskategorien:**
- Alle Standorte
- Mitarbeiter
- Kunden
- Projekte
- Einsätze
- Assets
- Geofences
- Benutzerdefinierte Kategorien

**Filter:**
- Kategorien ein-/ausblenden
- Datumsbereich
- Zugewiesener Benutzer
- Status
- Suche

**Markierungsliste:**
- Listenansicht aller Markierungen
- Sortieren nach Name/Datum/Kategorie
- Schnellnavigation

### Rechtes Panel (Markierungsdetails)

**Bei Klick auf Markierung:**
- Standortname
- Adresse
- Kategorie
- Beschreibung
- Fotos
- Erstellt von/Datum
- Zugehörige Elemente (Mitarbeiter, Bericht, etc.)
- Aktionen (Bearbeiten, Löschen, Navigieren)

## Kartenanbieter

### Kartenstil auswählen

**Kartentypen:**
1. Klicken Sie auf **Ebenen**-Schaltfläche
2. Auswählen:
   - **Straßenkarte**: Standard, detaillierte Straßen
   - **Satellit**: Luftbildaufnahmen
   - **Hybrid**: Satellit + Beschriftungen
   - **Gelände**: Topografisch
   - **Dunkel**: Dunkelmodus-Karte

**Karteneinstellungen:**
- Standard-Zoomstufe
- Standardzentrum
- Maßeinheiten (km/Meilen)
- Sprache

## Markierungen erstellen

### Markierung schnell hinzufügen

**Durch Klicken hinzufügen:**
1. Klicken Sie auf **+ Markierung hinzufügen**
2. Auf Kartenstandort klicken
3. Name eingeben
4. Kategorie auswählen
5. Speichern

**Nach Adresse hinzufügen:**
1. Klicken Sie auf **+ Markierung hinzufügen**
2. Adresse in Suche eingeben
3. Aus Ergebnissen auswählen
4. Karte zentriert auf Standort
5. Bestätigen und speichern

### Detaillierte Markierungserstellung

**Vollständiges Markierungsformular:**
1. Klicken Sie auf **+ Markierung hinzufügen** → **Detailliert**
2. Alle Felder ausfüllen:

**Grundinformationen:**
- **Name**: Standortname (erforderlich)
  - Beispiel: "Hauptbüro", "Kundenstandort"
- **Kategorie**: Kategorie auswählen (erforderlich)
  - Aus vordefiniert wählen
  - Oder neue Kategorie erstellen
- **Beschreibung**: Details zum Standort
  - Rich-Text-Editor
  - Markdown-Unterstützung
- **Symbol**: Markierungssymbol
  - Aus Symbolbibliothek wählen
  - Farbauswahl
  - Benutzerdefinierte Symbole (Admin)

**Standort:**
- **Adresse**: Straßenadresse
  - Auto-Geocodierung zu Koordinaten
  - Manuelle Adresseingabe
- **Koordinaten**: Lat/Long
  - Automatisch von Adresse ausgefüllt
  - Manuelle Eingabe für Präzision
  - GPS-Erfassung (mobil)
- **Höhe**: Elevation (optional)
- **Genauigkeit**: GPS-Genauigkeitsradius

**Details:**
- **Kontaktperson**: Verantwortliche Person
- **Telefon**: Kontaktnummer
- **E-Mail**: Kontakt-E-Mail
- **Öffnungszeiten**: Zeitplan
- **Website**: URL
- **Notizen**: Zusätzliche Informationen

**Zuordnungen:**
- **Bezogen auf**:
  - Mitarbeiter
  - Unternehmen
  - Bericht
  - Projekt
  - Einsatz
- **Zugewiesen an**: Verantwortlicher Benutzer
- **Tags**: Benutzerdefinierte Tags zum Filtern

**Sichtbarkeit:**
- **Öffentlich**: Alle Benutzer sehen
- **Privat**: Nur Ersteller
- **Geteilt**: Bestimmte Benutzer/Gruppen
- **Organisationsweit**: Alle Organisationsbenutzer

**Medien:**
- Fotos hochladen
- Mehrere Bilder
- Fotobeschreibungen
- Miniaturbilderstellung

3. Klicken Sie auf **Speichern**

## Markierungskategorien

### Standardkategorien

**Integrierte Kategorien:**
- 🏢 **Büros**: Unternehmensstandorte
- 👥 **Mitarbeiter**: Mitarbeiterstandorte
- 🏛️ **Kunden**: Kundenstandorte
- 🚗 **Fahrzeuge**: Fahrzeugstandorte
- 📦 **Assets**: Ausrüstungsstandorte
- 🚨 **Einsätze**: Einsatzorte
- 🏗️ **Projekte**: Projektstandorte
- 📍 **Allgemein**: Unkategorisiert

### Benutzerdefinierte Kategorien erstellen

**Neue Kategorie:**
1. Seitenleiste → **Kategorien verwalten**
2. Klicken Sie auf **+ Neue Kategorie**
3. Konfigurieren:
   - **Name**: Kategoriename
   - **Symbol**: Symbol auswählen
   - **Farbe**: Markierungsfarbe
   - **Standard-Zoom**: Zoomstufe
   - **Clustering**: Aktivieren/Deaktivieren
   - **Berechtigungen**: Wer hinzufügen kann
4. Speichern

**Kategoriefunktionen:**
- Benutzerdefinierte Symbole
- Farbcodierung
- Auto-Clustering
- Standardsichtbarkeit
- Berechtigungskontrolle

### Kategorien verwalten

**Kategorie bearbeiten:**
- Auf Kategorie klicken
- Einstellungen bearbeiten
- Änderungen speichern
- Alle Markierungen aktualisieren

**Kategorie löschen:**
- Kategorie auswählen
- Klicken Sie auf **Löschen**
- Aktion wählen:
  - Markierungen zu anderer Kategorie verschieben
  - Markierungen löschen
  - Markierungen nicht zuweisen

## Markierungen verwalten

### Markierungen anzeigen

**Kartenansicht:**
- Markierungen auf Karte anzeigen
- Klicken für Details
- Info-Popup erscheint
- Aktionen verfügbar

**Listenansicht:**
- Seitenleistenliste
- Sortieren und filtern
- Klicken zum Zentrieren der Karte
- Schnellaktionen

### Markierungen bearbeiten

**Markierung bearbeiten:**
1. Auf Markierung klicken
2. Klicken Sie auf **Bearbeiten** im Popup
3. Felder ändern
4. Änderungen speichern

**Markierung verschieben:**
1. Auf Markierung klicken
2. **Bearbeitungsmodus** aktivieren
3. Markierung an neue Position ziehen
4. Neue Position bestätigen
5. Adresse wird automatisch aktualisiert

**Schnellbearbeitung:**
- Doppelklick auf Markierung
- Name inline bearbeiten
- Kategorie-Dropdown ändern
- Beschreibung aktualisieren

### Markierungen löschen

**Markierung löschen:**
1. Auf Markierung klicken
2. Klicken Sie auf **Löschen**
3. Bestätigen
4. Markierung von Karte entfernt

**Massenlöschung:**
1. Mehrere Markierungen auswählen (Strg+Klick)
2. Aktionen → Ausgewählte löschen
3. Bestätigen

## Echtzeit-Standortverfolgung

### Mitarbeiterverfolgung

**Verfolgung aktivieren:**
(Erfordert Berechtigung und Zustimmung)

1. Mitarbeiter → Standortfreigabe aktivieren
2. Mobile App verfolgt GPS
3. Standort-Updates alle N Minuten
4. Zeigt auf Karte in Echtzeit

**Verfolgungsfunktionen:**
- Live-Standort-Updates
- Bewegungsverlauf
- Brotkrümelspur
- Zeit am Standort
- Geschwindigkeitsverfolgung
- Geofence-Alarme

**Datenschutz:**
- Opt-in erforderlich
- Nur Arbeitszeiten-Option
- Standortverlaufsspeicherung
- Benutzer kann jederzeit deaktivieren

### Fahrzeugverfolgung

**GPS-Geräteintegration:**
- GPS-Tracker verbinden
- Echtzeit-Fahrzeugstandort
- Geschwindigkeitsüberwachung
- Routenverlauf
- Standzeit-Verfolgung
- Kraftstoffverbrauch (falls unterstützt)

**Fahrzeugmarkierungen:**
- Fahrzeugsymbol
- Aktueller Standort
- Status (fahrend/stehend)
- Letzte Aktualisierungszeit
- Klicken für Details

## Geofencing & Zonen

### Geofences erstellen

**Geofence hinzufügen:**
1. Klicken Sie auf **Zeichenwerkzeuge** → **Geofence**
2. Auf Karte zeichnen:
   - **Kreis**: Zentrum klicken, Radius ziehen
   - **Polygon**: Punkte klicken, Form schließen
   - **Rechteck**: Klicken und ziehen
3. Geofence konfigurieren:

**Geofence-Einstellungen:**
- **Name**: Geofence-Name
  - Beispiel: "Bürozone", "Sperrgebiet"
- **Typ**:
  - Sichere Zone
  - Sperrzone
  - Arbeitszone
  - Benutzerdefiniert
- **Alarme**:
  - Zone betreten
  - Zone verlassen
  - Zeit in Zone
  - Unbefugter Zutritt
- **Benachrichtigen**:
  - Benutzer-E-Mail
  - Push-Benachrichtigung
  - SMS (falls aktiviert)
  - Webhook
- **Zeitplan**: Wann aktiv
  - Ganztägig
  - Nur Arbeitszeiten
  - Benutzerdefinierter Zeitplan
- **Farbe**: Zonenfarbe
- **Deckkraft**: Transparenz

4. Speichern

### Geofence-Alarme

**Alarm-Auslöser:**
- Benutzer betritt Geofence
- Benutzer verlässt Geofence
- Benutzer innerhalb für > N Minuten
- Benutzer außerhalb während Arbeitszeiten
- Fahrzeug betritt/verlässt

**Alarm-Aktionen:**
- E-Mail-Benachrichtigung
- Push-Benachrichtigung
- Protokolleintrag erstellen
- Einsatzbericht erstellen
- Webhook an externes System

**Alarm-Verlauf:**
- Ansicht → Geofence-Alarme
- Datum/Uhrzeit
- Benutzer/Fahrzeug
- Geofence-Name
- Ereignistyp (betreten/verlassen)
- Dauer

### Geofences verwalten

**Geofence bearbeiten:**
1. Auf Geofence-Umriss klicken
2. Form ändern (Punkte ziehen)
3. Einstellungen aktualisieren
4. Speichern

**Geofence löschen:**
- Auf Geofence klicken
- Klicken Sie auf **Löschen**
- Bestätigen

**Geofence-Liste:**
- Alle Geofences anzeigen
- Aktivieren/Deaktivieren
- Einstellungen bearbeiten
- Alarme anzeigen

## Routenplanung

### Routen erstellen

**Route planen:**
1. Klicken Sie auf **Route**-Schaltfläche
2. Wegpunkte hinzufügen:
   - Auf Karte klicken
   - Oder Adressen eingeben
   - Oder Markierungen auswählen
3. Route wird automatisch berechnet
4. Zeigt:
   - Gesamtentfernung
   - Geschätzte Zeit
   - Schritt-für-Schritt-Anweisungen

**Routenoptionen:**
- **Optimieren**: Beste Reihenfolge für Wegpunkte
- **Vermeiden**: Mautstraßen, Autobahnen, Fähren
- **Fahrzeugtyp**: Auto, LKW, Fahrrad, Zu Fuß
- **Abfahrtszeit**: Jetzt oder geplant

**Route speichern:**
- Route benennen
- Zur Wiederverwendung speichern
- Mit Benutzern teilen
- Auf GPS exportieren

### Routenverfolgung

**Route folgen:**
1. Gespeicherte Route öffnen
2. Klicken Sie auf **Navigieren**
3. Mobile App bietet Anweisungen
4. Echtzeit-Verkehrsaktualisierungen (falls aktiviert)
5. ETA-Berechnung

**Routenverlauf:**
- Vergangene Routen anzeigen
- Zurückgelegte Entfernung
- Benötigte Zeit
- Abweichungen vom Plan

### Multi-Stopp-Routen

**Lieferroute:**
1. Mehrere Stopps hinzufügen
2. Reihenfolge optimieren
3. Fahrer zuweisen
4. Fortschritt verfolgen
5. Stopps als abgeschlossen markieren

**Routenfunktionen:**
- Geschätzte Zeit pro Stopp
- Gesamtroutenzeit
- Gesamtentfernung
- Kraftstoffschätzung
- Verkehrsberücksichtigung

## Messwerkzeuge

### Entfernungsmessung

**Entfernung messen:**
1. Klicken Sie auf **Mess**-Werkzeug
2. Startpunkt klicken
3. Endpunkt klicken (oder mehrere Punkte)
4. Entfernung wird angezeigt
5. Einheiten: km, Meilen, Meter

**Fläche messen:**
1. Klicken Sie auf **Messen** → **Fläche**
2. Polygon zeichnen
3. Form schließen
4. Fläche wird berechnet (qkm, Quadratmeilen)

### Radiuswerkzeug

**Radius zeichnen:**
1. Klicken Sie auf **Radius**-Werkzeug
2. Auf Mittelpunkt klicken
3. Radius eingeben
4. Kreis wird gezeichnet
5. Zeigt alle Markierungen im Radius

**Anwendungsfälle:**
- Mitarbeiter innerhalb X km finden
- Servicebereichsabdeckung
- Lieferradius
- Einsatzgebiet

## Suche & Filter

### Standorte suchen

**Suchfeld:**
- Markierungsnamen suchen
- Adressen suchen
- Beschreibungen suchen
- Kategorien suchen
- Tags suchen

**Suchergebnisse:**
- Liste der Treffer
- Klicken zum Zentrieren der Karte
- Alle Ergebnisse anzeigen

### Erweiterte Filter

**Markierungen filtern:**
1. Klicken Sie auf **Filter**-Schaltfläche
2. Konfigurieren:
   - **Kategorien**: Mehrere auswählen
   - **Datumsbereich**: Erstellt/Aktualisiert
   - **Zugewiesener Benutzer**: Bestimmter Benutzer
   - **Tags**: Tags auswählen
   - **Hat Fotos**: Ja/Nein
   - **Geofence**: Innerhalb/Außerhalb bestimmter Zone
   - **Entfernung von**: Innerhalb X von Punkt
3. Anwenden

**Aktive Filter:**
- Oben anzeigen
- Klicken zum Ändern
- Alle löschen-Option
- Filter-Voreinstellung speichern

### Clustering

**Markierungs-Clustering:**
- Viele Markierungen gruppieren sich zu Cluster
- Zeigt Anzahl im Cluster
- Klicken zum Hineinzoomen
- Erweitert automatisch beim Zoomen

**Cluster-Einstellungen:**
- Min. Markierungen zum Clustern (Standard: 10)
- Cluster-Radius
- Max. Cluster-Zoom
- Cluster-Farben

## Standortverlauf

### Verfolgungsverlauf

**Verlauf anzeigen:**
1. Benutzer/Fahrzeug auswählen
2. Klicken Sie auf **Verlauf**
3. Datumsbereich wählen
4. Ansicht:
   - Zurückgelegter Pfad
   - Brotkrümelspur
   - Zeit an jedem Standort
   - Gesamtentfernung
   - Stopps

**Verlaufsfunktionen:**
- Wiedergabe-Animation
- Geschwindigkeitsregelung
- Zeitschieberegler
- Zu Zeit/Standort springen
- Track exportieren

### Standortberichte

**Bericht generieren:**
1. Berichte → Standortbericht
2. Konfigurieren:
   - Benutzer/Fahrzeug
   - Datumsbereich
   - Kartenschnappschuss einschließen
   - Entfernungszusammenfassung
   - Stopp-Details
3. PDF/Excel generieren

**Berichtsinhalte:**
- Gesamtentfernung
- Gesamtzeit
- Anzahl der Stopps
- Zeit an jedem Standort
- Durchschnittsgeschwindigkeit
- Karte mit Route

## Teilen & Zusammenarbeit

### Standorte teilen

**Markierung teilen:**
1. Auf Markierung klicken
2. Klicken Sie auf **Teilen**
3. Optionen:
   - **Link kopieren**: URL zur Markierung
   - **E-Mail**: An Benutzer senden
   - **Einbetten**: iFrame-Code
   - **QR-Code**: QR generieren

**Kartenansicht teilen:**
- Aktuelle Kartenansicht teilen
- Einschließlich Zoom und Zentrum
- Mit Benutzern teilen
- Öffentlicher Freigabelink

### Öffentliche Karten

**Öffentliche Karte erstellen:**
1. Karteneinstellungen → Öffentlicher Zugriff
2. Öffentliche Ansicht aktivieren
3. Wählen, was sichtbar ist:
   - Alle Markierungen
   - Ausgewählte Kategorien
   - Bestimmte Markierungen
4. Öffentliche URL generieren
5. URL teilen

**Öffentliche Kartenfunktionen:**
- Nur-Lese-Modus
- Keine Authentifizierung erforderlich
- In Website einbetten
- Benutzerdefiniertes Branding (falls aktiviert)

## Mobile Funktionen

### Mobile App

**Karte auf Mobilgerät:**
- Vollständige Kartenoberfläche
- GPS-Integration
- Offline-Karten (Cache)
- Fotos am Standort aufnehmen
- Sprachsuche
- Navigation

**GPS-Check-in:**
1. Am Standort ankommen
2. Karte öffnen
3. Klicken Sie auf **Einchecken**
4. App zeichnet auf:
   - Standort
   - Zeit
   - Foto (optional)
   - Notiz
5. Erstellt automatisch Markierung

### Standortberechtigungen

**GPS aktivieren:**
- App fordert Berechtigung an
- Hintergrundstandort-Option
- Batterieoptimierungseinstellungen
- Genauigkeitseinstellungen

## Import & Export

### Standorte importieren

**Aus Datei importieren:**
1. Klicken Sie auf **Importieren**
2. Dateiformat auswählen:
   - CSV
   - Excel
   - KML/KMZ (Google Earth)
   - GPX (GPS Exchange)
   - GeoJSON
3. Spalten zuordnen:
   - Name → Spalte A
   - Adresse → Spalte B
   - Kategorie → Spalte C
4. Vorschau
5. Importieren

**CSV-Format:**
```csv
name,address,category,description,lat,lng
Büro,Hauptstraße 123,Büros,Hauptbüro,52.52,13.405
Kundenstandort,Parkstraße 456,Kunden,Wichtiger Kunde,52.51,13.39
```

### Standorte exportieren

**Kartendaten exportieren:**
1. Klicken Sie auf **Exportieren**
2. Auswählen:
   - Alle Markierungen
   - Gefilterte Markierungen
   - Ausgewählte Markierungen
3. Format wählen:
   - Excel
   - CSV
   - KML (Google Earth)
   - GPX
   - GeoJSON
4. Herunterladen

**Exportoptionen:**
- Fotos einschließen
- Verlauf einschließen
- Routen einschließen
- Geofences einschließen

### Karte drucken

**Drucken:**
1. Klicken Sie auf **Drucken**-Schaltfläche
2. Konfigurieren:
   - Papiergröße (A4, Letter)
   - Ausrichtung (Hochformat/Querformat)
   - Legende einschließen
   - Maßstab einschließen
   - Titel
3. Drucken oder als PDF speichern

**Druckfunktionen:**
- Hohe Auflösung
- Benutzerdefinierte Zoomstufe
- Markierungsbeschriftungen
- Kategorienlegende
- Maßstabsleiste
- Nordpfeil

## Einstellungen & Konfiguration

### Karteneinstellungen

**Karte konfigurieren:**
(Einstellungen → Karte)

**Standardansicht:**
- Mittelpunkt (Lat/Long oder Adresse)
- Standard-Zoomstufe
- Standardkartentyp
- Standardkategorien angezeigt

**Anzeige:**
- Markierungs-Clustering (an/aus)
- Beschriftungen immer/beim Hover anzeigen
- Animationsgeschwindigkeit
- Info-Popup automatisch öffnen

**Verfolgung:**
- Aktualisierungsintervall (Minuten)
- Standortverlaufsspeicherung (Tage)
- GPS-Genauigkeitsschwelle
- Batteriespar-Modus

**Berechtigungen:**
- Wer Markierungen hinzufügen kann
- Wer Markierungen bearbeiten kann
- Wer Markierungen löschen kann
- Wer Kategorien erstellen kann
- Wer Verfolgung anzeigen kann
- Wer Geofences erstellen kann

### Kategorieverwaltung

**Admin-Einstellungen:**
1. Administration → Karte → Kategorien
2. Alle Kategorien verwalten:
   - Erstellen/Bearbeiten/Löschen
   - Standardkategorien festlegen
   - Kategorieberechtigungen
   - Symbolbibliothek
   - Farbpalette

### API-Integration

**Externe Dienste:**
- Google Maps API
- Mapbox
- OpenStreetMap
- HERE Maps

**API-Einstellungen:**
- API-Schlüsselkonfiguration
- Ratenbegrenzung
- Geocodierungsdienst
- Reverse-Geocodierung
- Routenoptimierungsdienst

## Best Practices

**Empfohlen:**
✅ Klare Markierungsnamen verwenden
✅ Markierungen richtig kategorisieren
✅ Fotos zur visuellen Referenz hinzufügen
✅ Standorte regelmäßig aktualisieren
✅ Geofences zur Automatisierung verwenden
✅ Datenschutzeinstellungen respektieren
✅ Alte Markierungen aufräumen
✅ Regelmäßig Backups exportieren

**Nicht empfohlen:**
❌ Ohne Zustimmung verfolgen
❌ Karte mit Markierungen überfüllen
❌ Adressen nicht aktualisieren
❌ Geofence-Alarme ignorieren
❌ Private Standorte öffentlich teilen
❌ Unnötigen Verlauf behalten
❌ GPS-Genauigkeit nicht testen

## Fehlerbehebung

### Markierungen werden nicht angezeigt
- Kategoriefilter prüfen
- Zoomstufe überprüfen
- Berechtigungen prüfen
- Cache leeren
- Karte aktualisieren

### GPS funktioniert nicht
- Standortberechtigungen prüfen
- GPS auf Gerät aktiviert überprüfen
- Genauigkeitseinstellungen prüfen
- Innen/Außen versuchen
- App neu starten

### Geofence-Alarme werden nicht ausgelöst
- Geofence aktiv überprüfen
- Zeitplaneinstellungen prüfen
- Benutzerberechtigungen überprüfen
- Benachrichtigungseinstellungen prüfen
- Mit manuellem Auslöser testen

### Langsame Kartenleistung
- Sichtbare Markierungen reduzieren
- Clustering aktivieren
- Aktualisierungshäufigkeit verringern
- Standortverlauf löschen
- Internetverbindung prüfen

## Administration & Konfiguration

### Admin-Menüzugriff

**Erforderliche Berechtigung:** `ADMIN_MAP`

**Admin-Einstellungen aufrufen:**
1. Desktop → Start → Administration → Karteneinstellungen
2. Oder direkt: `/admin/map`

### Route: `/admin/map`

**Kartenverwaltung:**
Globale Karteneinstellungen, Markierungskategorien und systemweite Standards konfigurieren.

### Globale Kartenkonfiguration

**System-Karteneinstellungen:**
1. Navigieren Sie zu `/admin/map`
2. Klicken Sie auf **Systemeinstellungen**-Tab
3. Konfigurieren:

**Standard-Karteneinstellungen:**
- **Standard-Kartenanbieter**:
  - Google Maps (erfordert API-Schlüssel)
  - Mapbox (erfordert API-Schlüssel)
  - OpenStreetMap (kostenlos, kein Schlüssel)
  - HERE Maps
- **Standardkartentyp**:
  - Straßenkarte
  - Satellit
  - Hybrid
  - Gelände
- **Standardmittelpunkt**:
  - Breitengrad: z.B. 52.5200
  - Längengrad: z.B. 13.4050
  - Oder Adresse eingeben (konvertiert automatisch)
- **Standard-Zoomstufe**: 1-20
  - Stadtebene: ~12
  - Straßenebene: ~15
  - Gebäudeebene: ~18
- **Maßeinheiten**:
  - Metrisch (km, Meter)
  - Imperial (Meilen, Fuß)

**Anzeigeeinstellungen:**
- **Markierungs-Clustering aktivieren**:
  - Mindestmarkierungen zum Clustern: 10 (Standard)
  - Cluster-Radius: 80px (Standard)
  - Max. Zoomstufe für Clustering: 15
  - Cluster-Farben: Anpassen
- **Markierungsbeschriftungen**:
  - Immer anzeigen
  - Nur beim Hover anzeigen
  - Nie anzeigen
- **Animationsgeschwindigkeit**:
  - Schnell
  - Normal
  - Langsam
  - Deaktiviert
- **Info-Popup-Verhalten**:
  - Automatisch bei Klick öffnen
  - Manuell öffnen
  - Hover zur Vorschau

### API-Konfiguration

**Kartenanbieter-APIs:**

**Google Maps-Einrichtung:**
1. Admin → Karte → API-Konfiguration
2. Klicken Sie auf **Google Maps**
3. Eingeben:
   - **API-Schlüssel**: Ihr Google Maps API-Schlüssel
   - **Dienste aktivieren**:
     - Maps JavaScript API
     - Geocoding API
     - Directions API
     - Places API
     - Distance Matrix API
   - **Einschränkungen**: Domain-/IP-Einschränkungen
   - **Abrechnung**: Nutzung überwachen
4. Verbindung testen
5. Speichern

**Mapbox-Einrichtung:**
- **Zugriffstoken**: Ihr Mapbox-Token
- **Style-URL**: Benutzerdefinierter Kartenstil
- **Geocodierung**: Aktivieren/Deaktivieren
- **Routen**: Aktivieren/Deaktivieren

**OpenStreetMap:**
- Kein API-Schlüssel erforderlich
- **Tile-Server**: Standard oder benutzerdefiniert
- **Namensnennung**: Erforderlich
- **Ratenbegrenzung**: OSM-Nutzungsrichtlinie beachten

### Markierungskategorien-Konfiguration

**Globale Kategorien erstellen:**
1. Admin → Karte → Kategorien
2. Klicken Sie auf **+ Neue Kategorie**
3. Konfigurieren:

**Kategorieeinstellungen:**
- **Kategoriename**: Anzeigename
  - Beispiel: "Hydranten", "Notausgänge"
  - Mehrsprachige Unterstützung
- **Kategorieschlüssel**: Interner Identifikator
  - Kleinbuchstaben, keine Leerzeichen
  - Beispiel: `hydranten`
- **Symbol**: Markierungssymbol auswählen
  - Aus Symbolbibliothek wählen (500+ Symbole)
  - Benutzerdefiniertes SVG-Symbol hochladen
  - Symbolgröße: 32x32px empfohlen
- **Farbe**: Markierungsfarbe
  - Hex-Farbwähler
  - Vordefinierte Palette
  - Beispiel: #FF0000 für Rot
- **Beschreibung**: Kategoriezweck
- **Standardsichtbarkeit**: Standardmäßig anzeigen
  - Ja: Immer sichtbar
  - Nein: Versteckt bis aktiviert
- **Clustering aktiviert**: Auto-Cluster
  - Nützlich für Kategorien mit vielen Markierungen
- **Standard-Zoom**: Bei Auswahl
  - Zoomstufe 1-20
- **Sortierreihenfolge**: Anzeigereihenfolge (0-999)
- **Aktiv**: Kategorie aktivieren/deaktivieren

**Symbolbibliothek:**
- Gebäudesymbole
- Fahrzeugsymbole
- Notfallsymbole
- Natursymbole
- Benutzerdefinierte Uploads
- Font Awesome-Integration

4. Klicken Sie auf **Speichern**

**Kategorien verwalten:**
- Vorhandene Kategorien bearbeiten
- Kategorien neu ordnen (Drag & Drop)
- Ungenutzte Kategorien löschen
- Kategorien massenhaft importieren (CSV)
- Kategorien exportieren

**Standardkategorien:**
System enthält diese vorkonfigurierten:
- Büros (🏢)
- Mitarbeiter (👥)
- Kunden (🏛️)
- Fahrzeuge (🚗)
- Assets (📦)
- Einsätze (🚨)
- Projekte (🏗️)
- Allgemein (📍)

### GPS-Verfolgungskonfiguration

**Standortverfolgungseinstellungen:**
1. Admin → Karte → GPS-Verfolgung
2. Konfigurieren:

**Verfolgungsintervalle:**
- **Aktualisierungshäufigkeit**:
  - Echtzeit (alle 30 Sekunden)
  - Normal (alle 2 Minuten)
  - Batteriespar-Modus (alle 5 Minuten)
  - Benutzerdefiniertes Intervall
- **Genauigkeitsschwelle**:
  - Hohe Genauigkeit (< 10m)
  - Mittel (< 50m)
  - Niedrig (< 100m)
- **Standortverlaufsspeicherung**:
  - 7 Tage
  - 30 Tage
  - 90 Tage
  - 1 Jahr
  - Für immer
  - Benutzerdefiniert

**Datenschutzeinstellungen:**
- **Verfolgungszustimmung erforderlich**: Ja/Nein
  - Wenn Ja: Benutzer müssen zustimmen
  - Wenn Nein: Standardmäßig aktiviert (lokale Gesetze prüfen!)
- **Nur Arbeitszeiten**: Nur während Schichten verfolgen
- **Standortverlaufszugriff**:
  - Nur selbst
  - Vorgesetzte
  - Administratoren
  - Benutzerdefinierte Rollen
- **Anonymer Modus**: Option zur Deaktivierung der Verfolgung
- **Standortlöschung**: Benutzer können Verlauf löschen

**Batterieoptimierung:**
- **Adaptive Verfolgung**:
  - Häufigkeit reduzieren wenn stationär
  - Erhöhen wenn in Bewegung
- **Geofence-Modus**:
  - Nur in Nähe von Geofences verfolgen
  - Spart Batterie
- **Hintergrundbeschränkungen**:
  - iOS-Hintergrundstandort-Limits
  - Android Doze-Modus-Behandlung

### Geofencing-Konfiguration

**Geofence-Systemeinstellungen:**
1. Admin → Karte → Geofences
2. Konfigurieren:

**Globale Geofence-Einstellungen:**
- **Max. Geofences pro Organisation**: 100 (Standard)
- **Standard-Alarmtypen**:
  - Zone betreten
  - Zone verlassen
  - Verweilzeit
  - Unbefugter Zugriff
- **Benachrichtigungskanäle**:
  - E-Mail
  - Push-Benachrichtigung
  - SMS (falls konfiguriert)
  - Webhook
- **Geofence-Genauigkeit**: 10-100 Meter
- **Alarmverzögerung**: Fehlalarme verhindern
  - Sofort
  - 30 Sekunden
  - 1 Minute
  - 5 Minuten

**Geofence-Vorlagen:**
Wiederverwendbare Geofence-Konfigurationen erstellen:
1. Klicken Sie auf **Vorlagen**
2. **+ Neue Vorlage**
3. Konfigurieren:
   - Vorlagenname
   - Form (Kreis/Polygon)
   - Standardgröße
   - Alarmeinstellungen
   - Benachrichtigungsvorlage
4. Speichern
5. Benutzer können Vorlage beim Erstellen von Geofences verwenden

**Webhook-Integration:**
- **Webhook-URL**: Ihr Endpunkt
- **Payload-Format**: JSON/XML
- **Authentifizierung**: Header/Tokens
- **Wiederholungsrichtlinie**: Fehlgeschlagene Zustellungen
- **Beispiel-Payload**:
```json
{
  "event": "geofence_enter",
  "user_id": 123,
  "geofence_id": 456,
  "timestamp": "2025-10-01T10:30:00Z",
  "location": {
    "lat": 52.52,
    "lng": 13.405
  }
}
```

### Berechtigungskonfiguration

**Kartenberechtigungen:**
1. Admin → Karte → Berechtigungen
2. Pro Rolle konfigurieren:

**Berechtigungstypen:**
- `READ_MAP`: Karte und Markierungen ansehen
- `WRITE_MAP`: Markierungen erstellen
- `EDIT_MAP`: Beliebige Markierung bearbeiten
- `DELETE_MAP`: Markierungen löschen
- `ADMIN_MAP`: Vollständige Kartenverwaltung
- `READ_TRACKING`: Standortverfolgung ansehen
- `WRITE_TRACKING`: Verfolgung aktivieren/deaktivieren
- `READ_GEOFENCE`: Geofences ansehen
- `WRITE_GEOFENCE`: Geofences erstellen
- `ADMIN_GEOFENCE`: Alle Geofences verwalten

**Kategoriebasierte Berechtigungen:**
- Lese-/Schreibrecht pro Kategorie gewähren
- Beispiel: "Mitarbeiter"-Kategorie auf Personalabteilung beschränkt
- Beispiel: "Assets"-Kategorie nur für Admin

**Sichtbarkeitsregeln:**
- **Öffentlich**: Alle Benutzer können sehen
- **Nur Abteilung**: Gleiche Abteilung
- **Rollenbasiert**: Bestimmte Rollen
- **Benutzerdefiniert**: Pro Benutzer

### Routenplanungskonfiguration

**Routeneinstellungen:**
1. Admin → Karte → Routing
2. Konfigurieren:

**Routenanbieter:**
- **Google Directions API**:
  - API-Schlüssel erforderlich
  - Verkehrsdaten
  - Mehrere Modi (Fahren, Gehen, Radfahren, ÖPNV)
- **Mapbox Directions**:
  - Zugriffstoken erforderlich
  - Verkehrsbewusste Routenführung
- **OpenRouteService**:
  - Kostenlose Alternative
  - API-Schlüssel erforderlich
- **OSRM** (Open Source):
  - Selbstgehostete Option
  - Kein API-Schlüssel

**Standard-Routeneinstellungen:**
- **Fahrzeugtyp**: Auto (Standard)
- **Vermeiden**: Mautstraßen, Autobahnen, Fähren
- **Optimieren**: Schnellste oder kürzeste
- **Verkehr**: Echtzeit-Verkehr verwenden
- **Max. Wegpunkte**: 10-25 Stopps

**Routenoptimierung:**
- **TSP-Solver aktivieren**: Traveling Salesman Problem
  - Optimiert Multi-Stopp-Routen
  - CPU-intensiv für 10+ Stopps
- **Optimierungsdienst**:
  - Google Optimization API
  - Mapbox Optimization API
  - Benutzerdefinierter Algorithmus

### Kartendatenverwaltung

**Dateneinstellungen:**
1. Admin → Karte → Datenverwaltung

**Speicherung:**
- **Markierungslimit**: Pro Organisation
  - Unbegrenzt
  - 1.000 Markierungen
  - 5.000 Markierungen
  - 10.000+ Markierungen
- **Fotospeicherung**: Pro Markierung
  - Max. Dateigröße: 5MB
  - Max. Fotos: 10 pro Markierung
  - Komprimierung: Aktivieren/Deaktivieren
- **Verlaufsspeicherung**:
  - Standortpunkte pro Benutzer/Tag
  - Automatische Bereinigung nach Aufbewahrungsfrist

**Massenoperationen:**
- **Markierungen importieren**:
  - CSV/Excel-Vorlage
  - Spalten zu Feldern zuordnen
  - Vorschau vor Import
  - Fehlerbehandlung
- **Markierungen exportieren**:
  - Alle Markierungen oder gefiltert
  - Fotos einschließen (ZIP)
  - Verlauf einschließen
- **Bereinigungswerkzeuge**:
  - Alte Markierungen löschen (> 1 Jahr)
  - Doppelte Markierungen entfernen
  - Inaktive Markierungen archivieren

### Integrationseinstellungen

**Drittanbieter-Integrationen:**

**Einsatzleitungsintegration:**
- Karte mit Einsatzleitungssystem verknüpfen
- Einheitenstandorte auf Karte anzeigen
- Einsätze von Karte erstellen
- Einheiten zu Standorten routen

**Mitarbeiterintegration:**
- Mitarbeiterstandorte anzeigen
- Mit Mitarbeiterprofilen verknüpfen
- Arbeitsstandortzuweisungen
- Ein-/Auschecken

**Fahrzeugintegration:**
- GPS-Tracker-Integration
- Echtzeit-Fahrzeugstandorte
- Wartungsstandortverlauf
- Kraftstoffverfolgung an Standorten

**Berichtsintegration:**
- Standorte an Berichte anhängen
- Berichtsstandorte auf Karte anzeigen
- Berichte nach Standort filtern
- Kartenansicht aller Berichte

### Mobile App-Einstellungen

**Mobile Konfiguration:**
1. Admin → Karte → Mobile Einstellungen

**Mobile Funktionen:**
- **Offline-Karten**: Aktivieren/Deaktivieren
  - Cache-Größe: 100MB-1GB
  - Bereich automatisch herunterladen
  - Regionen manuell herunterladen
- **GPS-Foto-Tagging**: Aktivieren
  - Fotos automatisch mit Standort taggen
  - EXIF-Daten-Einbeziehung
- **Hintergrundverfolgung**: Erlauben
  - iOS-Hintergrundmodi
  - Android-Vordergrund-Dienst
- **Schnell-Check-in**: Aktivieren
  - Ein-Tipp-Check-in-Schaltfläche
  - Markierung automatisch erstellen
  - Foto erforderlich: Ja/Nein

**Batterieeinstellungen:**
- **Aggressiver Modus**: Hohe Genauigkeit, hoher Batterieverbrauch
- **Ausgewogener Modus**: Mittlere Genauigkeit
- **Eco-Modus**: Niedrige Genauigkeit, niedriger Batterieverbrauch

### Benutzerdefinierte Kartenebenen

**Benutzerdefinierte Ebenen hinzufügen:**
1. Admin → Karte → Benutzerdefinierte Ebenen
2. **+ Neue Ebene**
3. Konfigurieren:

**Ebenentypen:**
- **WMS** (Web Map Service):
  - WMS-URL
  - Ebenenname
  - Deckkraft
  - Namensnennung
- **Tile-Ebene**:
  - Tile-URL-Vorlage
  - Min/Max-Zoom
  - Namensnennung
- **GeoJSON-Ebene**:
  - GeoJSON-Datei hochladen
  - Style-Konfiguration
  - Interaktive Funktionen
- **Heatmap-Ebene**:
  - Datenquelle
  - Radius
  - Verlaufsfarben
  - Intensität

**Beispiel benutzerdefinierte Ebene:**
- Feuerwehrbezirksgrenzen (WMS)
- Zonendaten (GeoJSON)
- Abdeckungs-Heatmap (Heatmap)

### Analysen & Berichte

**Kartenanalysen:**
1. Admin → Karte → Analysen

**Nutzungsstatistiken:**
- Gesamtzahl erstellter Markierungen
- Markierungen nach Kategorie
- Aktive vs. inaktive Markierungen
- Meistbesuchte Standorte
- Suchanfragen
- Benutzer nach Aktivität

**Verfolgungsstatistiken:**
- Gesamtzurückgelegte Entfernung
- Verfolgte Standorte pro Tag
- Aktiv verfolgende Benutzer
- GPS-Genauigkeitsstatistiken
- Batterieverbrauchsauswirkung

**Geofence-Analysen:**
- Alarmhäufigkeit
- Meist ausgelöste Geofences
- Durchschnittliche Verweilzeit
- Unbefugte Zutritte
- Alarm-Reaktionszeit

**Berichte:**
- PDF-Berichte generieren
- E-Mail-Berichte planen (täglich/wöchentlich)
- Nach Excel exportieren
- Benutzerdefinierte Datumsbereiche

### Wartung & Überwachung

**Systemzustand:**
1. Admin → Karte → Systemzustand

**Überwachen:**
- **API-Status**:
  - Google Maps API-Status
  - Ratenlimit-Nutzung
  - Fehler/Ausfälle
  - Antwortzeiten
- **GPS-Verfolgung**:
  - Aktive Tracker
  - Update-Verzögerungen
  - Fehlgeschlagene Updates
  - Batterieverbrauchsberichte
- **Speichernutzung**:
  - Markierungsanzahl
  - Verwendeter Fotospeicher
  - Größe der Verlaufsdaten
  - Datenbankgröße

**Wartungsaufgaben:**
- Standort-Cache leeren
- Markierungsindex neu erstellen
- Datenbank optimieren
- Alte Daten archivieren
- API-Verbindungen testen

**Alarme:**
Admin-Alarme konfigurieren:
- API-Ratenlimit nähert sich
- Speicherlimit erreicht Kapazität
- GPS-Verfolgungsfehler
- Geofence-Systemfehler
- Datenbankleistungsprobleme

### Best Practices (Admin)

**Empfohlen:**
✅ Angemessene API-Ratenlimits festlegen
✅ Datenspeicherungsrichtlinien konfigurieren
✅ Markierungs-Clustering für Leistung aktivieren
✅ Berechtigungen regelmäßig überprüfen
✅ API-Nutzung und -Kosten überwachen
✅ GPS-Genauigkeit im Feld testen
✅ Backup-/Export-Zeitpläne einrichten
✅ Geofence-Alarme richtig konfigurieren

**Nicht empfohlen:**
❌ API-Schlüssel öffentlich teilen
❌ Standortverlauf unbegrenzt aufbewahren
❌ Unbegrenzte Markierungserstellung erlauben
❌ Datenschutzvorschriften ignorieren (DSGVO)
❌ Batterieverbrauch mobil nicht testen
❌ Clustering bei 1000+ Markierungen deaktivieren
❌ API-Kostenüberwachung überspringen

### Fehlerbehebung (Admin)

**Karte lädt nicht:**
- API-Schlüsselgültigkeit prüfen
- API-Dienste aktiviert überprüfen
- Ratenlimits prüfen
- Browser-Konsolenfehler überprüfen
- API-Endpunkt direkt testen

**GPS-Verfolgungsprobleme:**
- Berechtigungen gewährt überprüfen
- Aktualisierungsintervall-Einstellungen prüfen
- Batterieoptimierungseinstellungen überprüfen
- Mit verschiedenen Geräten testen
- Netzwerkverbindung prüfen

**Leistungsprobleme:**
- Markierungs-Clustering aktivieren
- Sichtbare Markierungen reduzieren
- Datenbankabfragen optimieren
- Serverressourcen erhöhen
- API-Antwortzeiten prüfen

**Geofence-Alarme funktionieren nicht:**
- Webhook-Endpunkt überprüfen
- Benachrichtigungseinstellungen prüfen
- Geofence-Aktivstatus überprüfen
- Alarmauslöser manuell testen
- Benutzerberechtigungen prüfen

---

## Tastaturkürzel
- **+/-**: Hinein-/Herauszoomen
- **M**: Messwerkzeug
- **R**: Routenplaner
- **F**: Suchen/Filtern
- **G**: Geofence-Werkzeug
- **L**: Mein Standort
- **Esc**: Popups schließen
- **Strg+Klick**: Mehrere auswählen

---

## Nächste Schritte
- [🚓 Einsatzleitung](/guide/dispatch)
- [👥 Mitarbeiterverwaltung](/guide/employee-management)
- [📊 Berichte](/guide/reports)
- [⚙️ Admin: Kartenkonfiguration](/guide/admin/map)
