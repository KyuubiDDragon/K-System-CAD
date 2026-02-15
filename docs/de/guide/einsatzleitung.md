# Einsatzleitung

Umfassendes Einsatz- und Einsatzleitungssystem zur Koordinierung von Einheiten, Fahrzeugen, Besatzungen und Einsätzen in Echtzeit.

## Überblick

Funktionen:
- Echtzeit-Einheitenstatusverfolgung
- Einsatzmanagement
- Fahrzeugalarmierung
- Besatzungszuweisung
- Statusboard
- Standortverfolgung
- Funkverkehrsprotokoll
- Automatische Fahrzeugauswahl
- Ausrückezeitverfolgung
- Einsatzzeitachse
- Einheitenverfügbarkeit
- Ressourcenmanagement
- Berichte und Analysen

## Voraussetzungen

**Erforderliche Berechtigung:** `READ_DISPATCH` (zum Ansehen), `WRITE_DISPATCH` (zum Alarmieren)

**Admin-Einstellungen:**
- Fahrzeugverwaltung: `/vehicle`
- Besatzungsverwaltung: `/crew`
- Kartenintegration: `/map`

## Einsatzleitung öffnen

**Desktop:** Doppelklick auf **Einsatzleitung**-Symbol
**Menü:** Startmenü → Einsätze → Einsatzleitung
**Route:** `/dispatch`

## Oberflächenlayout

### Hauptstatusboard

**Oberer Bereich:**
- **Aktive Einsätze**: Laufende Einsätze
- **Verfügbare Einheiten**: Einsatzbereite Einheiten
- **Ausgerückte Einheiten**: Anfahrende Einheiten
- **Einheiten vor Ort**: Einheiten am Einsatzort
- **Nicht verfügbare Einheiten**: Außer Dienst/Wartung

**Statusfarben:**
- 🟢 **Verfügbar**: Einsatzbereit
- 🟡 **Unterwegs**: Fährt zum Einsatz
- 🔴 **Vor Ort**: Am Einsatzort
- ⚫ **Nicht verfügbar**: Außer Dienst
- 🟠 **Rückkehr**: Rückkehr zur Wache

### Linke Seitenleiste

**Schnellaktionen:**
- + Neuer Einsatz
- Einheit alarmieren
- Status aktualisieren
- Einheiten benachrichtigen
- Karte anzeigen

**Filter:**
- Alle Einheiten
- Nur verfügbare
- Nach Wache
- Nach Fahrzeugtyp
- Nach Besatzung

### Mittleres Panel - Einsatzliste

**Aktive Einsätze:**
- Einsatznummer
- Typ/Kategorie
- Ort
- Alarmierungszeit
- Zugewiesene Einheiten
- Priorität
- Status
- Aktionen

### Rechtes Panel - Einheitendetails

**Bei Auswahl einer Einheit:**
- Einheiten-ID und Funkrufname
- Aktueller Status
- Aktueller Standort (Karte)
- Zugewiesene Besatzung
- Fahrzeuginformationen
- Ausrüstung
- Aktueller Einsatz (falls vorhanden)
- Letzte Aktivitäten
- Schnellaktionen

## Einsätze erstellen

### Neuer Einsatz

**Einsatz erstellen:**
1. Klicken Sie auf **+ Neuer Einsatz**
2. Einsatzdetails ausfüllen:

**Grundinformationen:**
- **Einsatznummer**: Automatisch generiert (editierbar)
  - Format: INC-YYYY-NNNN
  - Beispiel: INC-2025-0123
- **Typ/Kategorie**: Einsatzart
  - Brand
  - Medizinischer Notfall
  - Technische Hilfeleistung
  - Gefahrgut
  - Verkehrsunfall
  - Hilfeleistung
  - Fehlalarm
  - Sonstiges
- **Priorität**: Dringlichkeitsstufe
  - 🔴 Priorität 1: Lebensgefahr
  - 🟠 Priorität 2: Dringend
  - 🟡 Priorität 3: Routine
  - 🟢 Priorität 4: Nicht dringend
- **Beschreibung**: Einsatzdetails
  - Was ist passiert
  - Anruferinformationen
  - Besondere Hinweise

**Ort:**
- **Adresse**: Straßenadresse
  - Auto-Vervollständigung aus Datenbank
  - Geocodierung zu Koordinaten
- **Kreuzungen**: Nächste Kreuzungen
- **Koordinaten**: Lat/Long (automatisch ausgefüllt)
- **Ortstyp**:
  - Wohngebäude
  - Gewerbe
  - Industrie
  - Autobahn
  - Freiland
- **Zugangshinweise**: Torcodes, Gefahren, etc.

**Anruferinformationen:**
- **Name des Anrufers**: Wer gemeldet hat
- **Rückrufnummer**: Kontaktnummer
- **Verhältnis**: Eigentümer, Nachbar, Passant

**Alarmierungsinformationen:**
- **Meldezeit**: Wann Anruf eingegangen
- **Disponent**: Wer Anruf angenommen (automatisch)
- **Empfohlene Einheiten**: Vorgeschlagene Alarmierung
  - Basierend auf Einsatzart
  - Ortsnähe
  - Einheitenverfügbarkeit

3. Klicken Sie auf **Erstellen & Alarmieren**

### Konfiguration der Einsatzarten

**Standardtypen:**
Jeder Typ hat eine empfohlene Alarmierung:
- **Gebäudebrand**:
  - 3 Löschfahrzeuge, 1 Drehleiter, 1 Einsatzleiter
- **Medizinischer Notfall**:
  - 1 Rettungswagen, 1 Löschfahrzeug (falls verfügbar)
- **Verkehrsunfall**:
  - 1 Löschfahrzeug, 1 Rettungswagen
- **Gefahrgut**:
  - 1 Gefahrguteinheit, 2 Löschfahrzeuge, 1 Einsatzleiter

**Benutzerdefinierte Typen:**
Konfiguration in Admin-Einstellungen

## Einheiten alarmieren

### Automatische Alarmierung

**Intelligente Alarmierung:**
1. System empfiehlt Einheiten basierend auf:
   - Einsatzart
   - Einheitenstandort (nächste zuerst)
   - Einheitenverfügbarkeit
   - Ausrüstungsanforderungen
   - Geschätzte Ausrückezeiten
2. Empfehlungen überprüfen
3. Klicken Sie auf **Alle alarmieren** oder wählen Sie einzelne Einheiten
4. Einheiten werden sofort benachrichtigt

**Alarmierungskriterien:**
- Nähe zum Einsatzort
- Einheitenfähigkeiten
- Mitgeführte Ausrüstung
- Besatzungszertifizierungen
- Aktuelle Auslastung
- Ausrückezeitziele

### Manuelle Alarmierung

**Spezifische Einheit alarmieren:**
1. Klicken Sie auf Einsatz
2. Klicken Sie auf **Einheit alarmieren**
3. Wählen Sie Einheit aus verfügbarer Liste
4. Fügen Sie besondere Hinweise hinzu
5. Klicken Sie auf **Alarmieren**

**Einheit erhält:**
- Einsatzdetails
- Ort und Karte
- Routennavigation
- Besondere Hinweise
- Anfahrende Einheiten

### Mehrfachalarmierung

**Mehrere alarmieren:**
1. Wählen Sie Einsatz aus
2. Klicken Sie auf **Mehrere alarmieren**
3. Markieren Sie zu alarmierende Einheiten:
   - Primäreinheiten (erforderlich)
   - Unterstützungseinheiten (optional)
   - Spezialeinheiten (bei Bedarf)
4. Anfahrtsreihenfolge festlegen
5. Alle alarmieren

**Gestaffelte Alarmierung:**
- Erstalarm: Erstanfahrt
- Zweitalarm: Zusätzliche Kräfte
- Drittalarm: Großschadenslage
- Auto-Nachalarmerungsregeln

## Einheitenstatusverwaltung

### Statustypen

**Verfügbare Status:**
- **In Wache**: Einsatzbereit in Wache
- **Alarmiert**: Einsatz zugewiesen
- **Unterwegs**: Fährt zum Einsatz
- **Vor Ort**: Am Einsatzort angekommen
- **Transport**: Patiententransport (Rettungsdienst)
- **Im Krankenhaus**: In medizinischer Einrichtung
- **Rückkehr**: Rückkehr zur Wache
- **Außer Dienst**: Nicht verfügbar (Wartung, Tanken)
- **Übung**: Übungsbetrieb
- **Außer Dienst**: Nicht verfügbar

### Status aktualisieren

**Einheitenstatus ändern:**
1. Einheit auswählen
2. Klicken Sie auf **Status aktualisieren**
3. Neuen Status wählen
4. Notiz hinzufügen (optional)
5. Automatischer Zeitstempel

**Schnelle Statusaktualisierungen:**
- Unterwegs: Ein-Klick bei Alarmierung
- Vor Ort: GPS-Autodetektion (optional)
- Verfügbar: Automatisch bei Rückkehr zur Wache
- Außer Dienst: Geplante Wartung

**Statusbenachrichtigungen:**
- Disponent wird benachrichtigt
- Einsatzzeitachse aktualisiert
- Ausrückezeiten berechnet
- Berichte aktualisiert

### Ausrückezeiten

**Automatische Verfolgung:**
- **Alarmierungszeit**: Wann Einheit alarmiert wurde
- **Ausrückezeit**: Wann Einheit ausrückt
- **Eintreffszeit**: Wann Einheit eintrifft
- **Verfügbarkeit**: Wann Einheit einsatzbereit
- **Gesamteinsatzzeit**: Gesamtdauer

**Leistungsmetriken:**
- Durchschnittliche Ausrückezeit
- Ausrückezeit nach Einsatzart
- Ausrückezeit nach Einheit
- Ausrückezeit nach Standort
- Einhaltung der Ziele

## Besatzungsverwaltung

### Besatzungszuweisung

**Besatzung Einheit zuweisen:**
1. Einheit auswählen
2. Klicken Sie auf **Besatzung zuweisen**
3. Besatzungsmitglieder auswählen:
   - Fahrer/Maschinist (erforderlich)
   - Führungskraft (für einige Einheiten erforderlich)
   - Feuerwehrleute/Rettungsassistenten
   - Gesamtbesatzungsstärke
4. Zertifizierungen überprüfen
5. Speichern

**Besatzungsanforderungen:**
- Mindestbesatzungsstärke pro Einheitentyp
- Erforderliche Zertifizierungen
- Führungskraftanforderungen
- Fahrerlaubnisse

**Besatzungsstatus:**
- Im Dienst
- Verfügbar
- Im Einsatz
- Pause/Mahlzeit
- Übung

### Besatzungsplanung

**Schichtverwaltung:**
- A/B/C-Schichtrotation
- 24/48-Zeitplan
- Kelly-Tage
- Überstunden
- Schichttausch

**Besatzungsplan anzeigen:**
1. Einsatzleitung → Besatzung-Tab
2. Aktuelle Schicht ansehen
3. Besatzungszuweisungen sehen
4. Verfügbarkeit prüfen

**Schichtwechsel:**
- Auto-Update der Einheitenverfügbarkeit
- Besatzungsbenachrichtigungen
- Statusboard-Aktualisierungen

## Fahrzeugverwaltung

### Fahrzeugstatus

**Fahrzeuginformationen:**
- Einheiten-ID und Funkrufname
- Fahrzeugtyp (Löschfahrzeug, Drehleiter, Rettungswagen, etc.)
- Aktueller Status
- Aktueller Standort
- Zugewiesene Besatzung
- Ausrüstungsmanifest
- Wartungsstatus
- Kraftstoffstand
- Letzte Prüfung

**Fahrzeugtypen:**
- **Löschfahrzeug**: Löschgruppenfahrzeug
- **Drehleiter**: Hubrettungsfahrzeug
- **Rettungswagen**: Medizinischer Transport
- **Rüstwagen**: Technische Hilfeleistung
- **Gefahrgut**: Gefahrguteinheit
- **Kommandowagen**: Führungsfahrzeug
- **Tanklöschfahrzeug**: Löschwasserversorgung
- **Gerätewagen**: Unterstützungsfahrzeug

### Ausrüstungsverfolgung

**Ausrüstung an Bord:**
- Atemschutzgeräte (Anzahl)
- Schläuche (Längen)
- Medizinische Ausrüstung
- Rettungsgeräte
- Spezialausrüstung

**Ausrüstungsstatus:**
- Verfügbar
- Im Einsatz
- Wartung erforderlich
- Außer Betrieb

**Ausrüstungsprüfungen:**
- Tägliche Prüfungen
- Prüfungen vor Einsatz
- Prüfungen nach Einsatz
- Wartungsprotokolle

## Einsatzzeitachse

### Zeitachsenansicht

**Einsatzzeitachse:**
Automatische Protokollierung von:
1. **Einsatz erstellt**: Erstmeldung
2. **Einheiten alarmiert**: Welche Einheiten, wann
3. **Unterwegs**: Einheitenausrücken
4. **Vor Ort**: Eintreffzeiten
5. **Zusätzliche Einheiten**: Nachalarmierungsanfragen
6. **Statusaktualisierungen**: Fortschrittsnotizen
7. **Patientenkontakt** (Rettungsdienst)
8. **Transport begonnen** (Rettungsdienst)
9. **Krankenhaus erreicht** (Rettungsdienst)
10. **Einheiten frei**: Rückkehr in Bereitschaft
11. **Einsatz abgeschlossen**: Endstatus

**Zeitachsenfunktionen:**
- Automatische Zeitstempel
- Manuelle Notizen/Aktualisierungen
- Fotoanhänge
- Audioaufnahmen
- Bearbeitungsfähigkeit (mit Audit)

### Einsatznotizen

**Notiz hinzufügen:**
1. Einsatz auswählen
2. Klicken Sie auf **Notiz hinzufügen**
3. Aktualisierung eingeben
4. Auto-Zeitstempel
5. Speichern

**Notiztypen:**
- Disponententnotizen
- Vor-Ort-Aktualisierungen
- Führungsentscheidungen
- Ressourcenanfragen
- Besondere Umstände
- Follow-up erforderlich

## Kartenintegration

### Einsatzleitungs-Kartenansicht

**Interaktive Karte:**
- Alle Einsätze eingezeichnet
- Alle Einheiten angezeigt (Echtzeit)
- Farbcodiert nach Status
- Klicken für Details
- Routenplanung

**Kartenebenen:**
- Aktive Einsätze (🔴)
- Einheiten (🚒 farbcodiert nach Status)
- Wachen (🏢)
- Hydranten (💧)
- Krankenhäuser (🏥)
- Gefahren (⚠️)

**Navigation:**
- Zoom zum Einsatz
- Zoom zur Einheit
- Route von Einheit zu Einsatz
- ETA-Berechnung
- Verkehrsdaten (falls verfügbar)

### GPS-Verfolgung

**Echtzeitverfolgung:**
- Einheitenstandort-Updates
- Brotkrümelspur
- Geschwindigkeitsüberwachung
- Geofence-Alarme
- Geschätzte Ankunft

**GPS-Funktionen:**
- 30-Sekunden-Updates
- Historische Wiedergabe
- Anfahrtsweg-Überprüfung
- Zurückgelegte Strecke
- Zeit vor Ort

## Kommunikation

### Einheitenkommunikation

**Einheiten benachrichtigen:**
1. Einheit(en) auswählen
2. Klicken Sie auf **Nachricht**
3. Nachricht eingeben
4. Senden an:
   - Einzelne Einheit
   - Mehrere Einheiten
   - Alle Einheiten im Einsatz
   - Alle verfügbaren Einheiten
5. Zugestellt an mobile Geräte

**Nachrichtentypen:**
- Alarmierungsanweisungen
- Statusanfragen
- Einsatzort-Updates
- Rückkehr zur Wache
- Administrativ

### Funkprotokoll

**Funkverkehrsprotokoll:**
Verfolgung aller Funkkommunikation:
- Zeit
- Einheit
- Nachricht
- Kanal
- Disponent

**Protokolleintrag:**
1. Klicken Sie auf **Funk protokollieren**
2. Einheit auswählen
3. Nachricht eingeben
4. Mit Zeitstempel speichern

**Funkkanäle:**
- Leitstellenkanal
- Taktische Kanäle
- Führungskanal
- Überörtliche Hilfe-Kanal

## Einsatzmanagement

### Einsatzleiter

**Einsatzleiter zuweisen:**
1. Einsatz auswählen
2. Klicken Sie auf **Einsatzleiter zuweisen**
3. Führungskraft auswählen
4. Einsatzleiter hat Führungsbefugnis

**Einsatzleiterfähigkeiten:**
- Zusätzliche Einheiten anfordern
- Einsatzstatus aktualisieren
- Taktische Operationen zuweisen
- Einheiten freigeben
- Einsatz abschließen

### Einheitenanforderungen

**Weitere Einheiten anfordern:**
1. Einsatzleiter oder Disponent
2. Klicken Sie auf **Einheiten anfordern**
3. Angeben:
   - Benötigter Typ
   - Anzahl
   - Priorität
   - Besondere Anforderungen
4. Auto-Alarmierung oder manuell

**Überörtliche Hilfe:**
- Anfrage von Nachbarfeuerwehren
- Externe Einheiten verfolgen
- Anfahrt koordinieren
- Rechnungsstellung/Erstattungsverfolgung

### Einsatzeskalation

**Alarmstufen:**
- **Erstalarm**: Erstanfahrt
- **Zweitalarm**: +50% Kräfte
- **Drittalarm**: Großschadenslage
- **Viertalarm+**: Katastrophal

**Auto-Eskalation:**
- Basierend auf Einsatzart
- Zeit vor Ort
- Einsatzleiteranfrage
- Spezifische Auslöser

## Einsätze abschließen

### Einsatz abschließen

**Wann abschließen:**
- Alle Einheiten frei
- Einsatzort gesichert
- Keine weiteren Maßnahmen erforderlich
- Berichte abgeschlossen

**Abschlussprozess:**
1. Alle Einheiten als verfügbar überprüfen
2. Klicken Sie auf **Einsatz abschließen**
3. Status wählen:
   - Erledigt
   - Fehlalarm
   - Abgebrochen
   - An andere Behörde verwiesen
   - Nicht auffindbar
4. Abschlussnotizen hinzufügen
5. Bestätigen

**Nach dem Einsatz:**
- Berichte erstellt
- Ausrückezeiten berechnet
- Einheiten verfügbar für nächsten Einsatz
- Einsatz archiviert

### Einsatzstatus

**Statustypen:**
- **Brand - Gelöscht**: Feuer gelöscht
- **Rettungsdienst - Transportiert**: Patient ins Krankenhaus
- **Rettungsdienst - Abgelehnt**: Patient lehnte Versorgung ab
- **Hilfeleistung abgeschlossen**: Hilfeleistung erbracht
- **Fehlalarm**: Kein Notfall vorgefunden
- **Abgebrochen**: Unterwegs abgebrochen
- **Verwiesen**: Von anderer Behörde übernommen

## Berichte & Analysen

### Einsatzleitungsberichte

**Verfügbare Berichte:**
1. **Ausrückzeitbericht**
   - Durchschnittliche Ausrückezeiten
   - Nach Einsatzart
   - Nach Einheit
   - Nach Tageszeit
   - Einhaltungsverfolgung

2. **Einheitenaktivitätsbericht**
   - Einsätze pro Einheit
   - Zeit im Einsatz
   - Verfügbarkeitsprozentsatz
   - Wartungsausfallzeit

3. **Einsatzzusammenfassung**
   - Gesamteinsätze
   - Nach Typ
   - Nach Priorität
   - Nach Standort
   - Heatmaps

4. **Besatzungsarbeitsauslastung**
   - Einsätze pro Besatzung
   - Überstundenverfolgung
   - Schichtstatistiken

**Bericht generieren:**
1. Klicken Sie auf **Berichte**
2. Typ auswählen
3. Datumsbereich festlegen
4. Filter konfigurieren
5. Generieren
6. Export (PDF/Excel)

### Leistungsmetriken

**Hauptmetriken:**
- Durchschnittliche Ausrückezeit
- Erste Einheit vor Ort Zeit
- Gesamteinsatzzeit
- Einheitenauslastung
- Einsatzvolumen-Trends
- Spitzenzeiten/-tage
- Ausrückezeit-Einhaltung (NFPA)

**Dashboards:**
- Echtzeit-Metriken
- Heutige Statistiken
- Monatliche Trends
- Jahr-für-Jahr-Vergleich

## Best Practices

**Empfohlen:**
✅ Status sofort aktualisieren
✅ Besatzungszuweisungen überprüfen
✅ Ausrüstung vor Schicht prüfen
✅ Alle Kommunikationen protokollieren
✅ Genaue Ortsinformationen
✅ Einsätze zeitnah abschließen
✅ Regelmäßige Ausrüstungsprüfungen
✅ Ausrückezeiten überprüfen

**Nicht empfohlen:**
❌ Statusaktualisierungen vergessen
❌ Nicht verfügbare Einheiten alarmieren
❌ Besatzungsüberprüfung überspringen
❌ Zeitachsennotizen fehlen
❌ Ausrüstungsprobleme ignorieren
❌ Einsätze offen lassen
❌ Ohne Ortsangabe alarmieren
❌ Einsatznachbesprechung überspringen

## Fehlerbehebung

### Einheit wird nicht als verfügbar angezeigt
- Einheitenstatus prüfen
- Besatzungszuweisung überprüfen
- Wartungsstatus prüfen
- Statusboard aktualisieren

### GPS-Standort wird nicht aktualisiert
- Mobilgeräteverbindung prüfen
- GPS aktiviert überprüfen
- Batteriesparmodis prüfen
- Mobile App neu starten

### Kann Einheit nicht alarmieren
- Einheit verfügbar überprüfen
- Berechtigungen prüfen
- Besatzung zugewiesen sicherstellen
- Fahrzeugstatus prüfen

### Ausrückezeit inkorrekt
- Alle Zeitstempel überprüfen
- Statusaktualisierungen prüfen
- Zeitachse überprüfen
- Fehler korrigieren

## Tastaturkürzel
- **Strg+N**: Neuer Einsatz
- **Strg+D**: Einheit alarmieren
- **Strg+U**: Status aktualisieren
- **Strg+M**: Einheiten benachrichtigen
- **Strg+F**: Einheit finden
- **Strg+R**: Board aktualisieren
- **Esc**: Dialoge schließen

---

## Nächste Schritte
- [🚗 Fahrzeugverwaltung](/guide/vehicles)
- [👥 Besatzungsverwaltung](/guide/crew)
- [🗺️ Karte & Standorte](/guide/map)
- [📊 Berichte](/guide/reports)
