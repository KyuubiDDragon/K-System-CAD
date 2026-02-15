# Bewerbungen & Recruiting

Umfassendes Bewerbungs- und Recruiting-Verwaltungssystem zur Verfolgung von Bewerbungen, Bewerbern, Bewerbungsprozessen und Einstellungsentscheidungen.

## Überblick

Funktionen:
- Online-Bewerbungsformulare
- Bewerberverfolgung
- Bewerbungsstatus-Workflow
- Vorstellungsgesprächs-Terminplanung
- Individuelle Bewerbungsfragen
- Dokument-Uploads (Lebenslauf, Anschreiben)
- Bewertung und Scoring
- Zusammenarbeit und Notizen
- E-Mail-Kommunikation
- Einstellungs-Workflow
- Berichterstattung und Analysen
- Integration mit Mitarbeiterverwaltung

## Voraussetzungen

**Erforderliche Berechtigung:** `READ_APPLICATION` (zum Anzeigen), `WRITE_APPLICATION` (zum Verwalten)

**Admin-Einstellungen:**
- Bewerbungsfragen: Administration → Bewerbungen → Fragen (`/admin/applicationquestions`)

## Bewerbungsverwaltung öffnen

**Desktop:** Doppelklick auf **Bewerbungen** Symbol
**Menü:** Startmenü → Mitarbeiterverwaltung → Bewerbungen
**Route:** `/application`

### Admin-Konfigurationsmenü
So konfigurieren Sie das Bewerbungssystem:
1. **Desktop:** Start → Administration → Bewerbungen → Fragen
2. **Direkte Route:** `/admin/applicationquestions`
3. **Konfigurieren:**
   - Bewerbungsfragen
   - Fragenvorlagen
   - Pflichtfelder
   - Workflow-Stufen
   - Auto-Antworten

## Bewerbungsliste

### Oberflächenlayout

**Bewerbungstabelle:**
- **Bewerbungs-ID**: Automatisch generierte Nummer
- **Bewerbername**: Vor- und Nachname
- **Position**: Stelle, auf die sich beworben wurde
- **Bewerbungsdatum**: Einreichungsdatum
- **Status**: Aktuelle Phase
- **Bewertung**: Punktzahl/Bewertung
- **Aktionen**: Anzeigen, Bearbeiten, Phase verschieben, Löschen

### Status-Workflow

**Bewerbungsphasen:**
- 🟦 **Neu**: Gerade eingereicht
- 🟡 **In Prüfung**: Wird bewertet
- 🟢 **Vorstellungsgespräch geplant**: Termin vereinbart
- 🟣 **Vorstellungsgespräch durchgeführt**: Gespräch abgeschlossen
- 🟢 **Angebot unterbreitet**: Stelle angeboten
- ✅ **Eingestellt**: Angenommen und eingestellt
- 🔴 **Abgelehnt**: Nicht ausgewählt
- ⚫ **Zurückgezogen**: Bewerber hat zurückgezogen

**Statusfarben:**
Visuelle Indikatoren zur schnellen Identifizierung

### Bewerbungen filtern

**Schnellfilter:**
- Alle Bewerbungen
- Neu (benötigt Prüfung)
- In Prüfung
- Vorstellungsgespräch geplant
- Angebote unterbreitet
- Diesen Monat eingestellt
- Abgelehnt

**Erweiterte Filter:**
1. Klicken Sie auf **Filter**
2. Konfigurieren Sie:
   - Datumsbereich
   - Position
   - Status
   - Bewertung (min/max)
   - Quelle (woher beworben)
3. Filter anwenden

## Bewerbungen anzeigen

### Bewerbungsdetails

**Bewerbung öffnen:**
1. Bewerbungszeile anklicken
2. Vollständige Details anzeigen:

**Bewerberinformationen:**
- Vollständiger Name
- E-Mail-Adresse
- Telefonnummer
- Adresse
- Geburtsdatum
- Verfügbares Startdatum

**Bewerbungsdetails:**
- Position, auf die sich beworben wurde
- Abteilung
- Bewerbungsdatum
- Quelle (Website, Empfehlung, Jobbörse)
- Antworten auf individuelle Fragen
- Angehängte Dokumente
- Aktueller Status
- Bewertung/Punktzahl

**Timeline:**
- Bewerbung eingereicht
- Statusänderungen
- Vorstellungsgespräche geplant
- Notizen hinzugefügt
- Kommunikation gesendet
- Entscheidung getroffen

### Angehängte Dokumente

**Dokumente anzeigen:**
- Lebenslauf/CV (PDF, DOC)
- Anschreiben
- Referenzen
- Zertifikate
- Portfolio
- Andere unterstützende Dokumente

**Dokumentaktionen:**
- Inline-Vorschau
- Herunterladen
- Drucken
- Mit Team teilen

### Bewerbungsbewertung

**Bewerbung bewerten:**
1. Bewerbung öffnen
2. Gehen Sie zu **Bewertung** Bereich
3. Bewerten auf Skala:
   - 1-5 Sterne
   - Oder individuelle Skala (1-10)
4. Kategorien bewerten:
   - Qualifikationen
   - Erfahrung
   - Bildung
   - Referenzen
   - Gesamtpassung
5. Bewertungsnotizen hinzufügen
6. Speichern

**Team-Bewertungen:**
- Multiple Bewerter
- Durchschnittsbewertung
- Individuelle Punktzahlen
- Kommentare pro Bewerter

## Bewerbungen verwalten

### Bewerbungsstatus ändern

**Status aktualisieren:**
1. Bewerbung öffnen
2. Klicken Sie auf **Status ändern**
3. Neuen Status auswählen:
   - Zur nächsten Phase verschieben
   - Phasen überspringen falls nötig
   - Ablehnen
   - Zurückziehen
4. Notiz hinzufügen (warum verschieben/ablehnen)
5. Optional: E-Mail an Bewerber senden
6. Speichern

**Statusbenachrichtigungen:**
- Bewerber wird automatisch benachrichtigt
- E-Mail-Vorlagen pro Status
- Anpassbare Nachrichten

### Notizen und Kommentare hinzufügen

**Interne Notizen:**
1. Bewerbung öffnen
2. Gehen Sie zu **Notizen** Tab
3. Klicken Sie auf **+ Notiz hinzufügen**
4. Notiz eingeben:
   - Eindrücke aus Vorstellungsgespräch
   - Telefonscreening-Notizen
   - Referenzprüfungsergebnisse
   - Team-Feedback
   - Entscheidungsgrundlage
5. Teammitglieder @erwähnen
6. Speichern

**Notizfunktionen:**
- Zeitstempel
- Benutzer zugeordnet
- Kann privat oder team-sichtbar sein
- Durchsuchbar
- Exportierbar

### Massenaktionen

**Multiple Bewerbungen:**
1. Bewerbungen auswählen (Checkbox)
2. Klicken Sie auf **Massenaktionen**
3. Aktion wählen:
   - Status ändern
   - Prüfer zuweisen
   - E-Mail senden
   - Exportieren
   - Löschen
4. Auf alle ausgewählten anwenden

## Vorstellungsgesprächs-Verwaltung

### Vorstellungsgespräch planen

**Vorstellungsgespräch erstellen:**
1. Bewerbung öffnen
2. Klicken Sie auf **Vorstellungsgespräch planen**
3. Vorstellungsgespräch konfigurieren:

**Vorstellungsgesprächs-Details:**
- **Typ**: Gesprächstyp
  - Telefonscreening
  - Video-Interview
  - Persönliches Gespräch
  - Panel-Interview
  - Praktischer Test
- **Datum & Uhrzeit**: Wann
- **Dauer**: Minuten
- **Ort**: Wo/Video-Link
  - Büroadresse
  - Zoom/Teams Link
  - Telefonnummer
- **Gesprächsführer**: Wer nimmt teil
  - Teammitglieder auswählen
  - Multiple Gesprächsführer
- **Anweisungen**: Notizen für Bewerber
- **Kalendereintrag**: Automatisch erstellen

4. Klicken Sie auf **Planen**

**Vorstellungsgesprächs-Einladung:**
- Auto-E-Mail an Bewerber
- Kalendereinladung
- Vorbereitungsmaterialien
- Kontaktinformationen
- Anfahrtsbeschreibung/Parken

### Vorstellungsgesprächs-Checkliste

**Vorstellungsgespräch vorbereiten:**
- Bewerbung überprüfen
- Lebenslauf überprüfen
- Fragen vorbereiten
- Referenzen überprüfen
- Mit Team koordinieren

### Vorstellungsgesprächs-Notizen

**Vorstellungsgespräch protokollieren:**
1. Nach Vorstellungsgespräch
2. Gehen Sie zu **Vorstellungsgespräch** Tab
3. Notizen hinzufügen:
   - Stärken
   - Schwächen
   - Wichtige Antworten
   - Kulturelle Passung
   - Technische Fähigkeiten
   - Kommunikationsfähigkeiten
   - Gesamteindruck
4. Gesprächsleistung bewerten
5. Nächste Schritte empfehlen
6. Speichern

**Vorstellungsgesprächs-Scorecard:**
Individuelle Bewertungskriterien:
- Technische Kompetenz (1-5)
- Kommunikation (1-5)
- Team-Passung (1-5)
- Problemlösung (1-5)
- Führungspotenzial (1-5)

## Einstellungsprozess

### Angebot unterbreiten

**Stellenangebot machen:**
1. Bewerbung öffnen
2. Status → **Angebot unterbreitet**
3. Angebot erstellen:
   - Positionsbezeichnung
   - Abteilung
   - Startdatum
   - Gehalt/Vergütung
   - Leistungszusammenfassung
   - Bedingungen (Hintergrundprüfung, etc.)
4. Angebotsschreiben generieren
5. An Bewerber senden

**Angebotsverfolgung:**
- Angebot gesendet Datum
- Antwortfrist
- Angebot angenommen/abgelehnt
- Verhandlungsnotizen
- Endgültige Bedingungen

### Angebot annehmen/ablehnen

**Bewerber nimmt an:**
1. Status → **Eingestellt**
2. Onboarding auslösen:
   - Mitarbeiterdatensatz erstellen
   - Neueinstellungs-Unterlagen senden
   - Einführung planen
   - Ausrüstung zuweisen
   - Zur Gehaltsabrechnung hinzufügen
3. Bewerbung schließen

**Bewerber lehnt ab:**
1. Status → **Angebot abgelehnt**
2. Grund erfassen
3. Als abgelehnt markieren
4. Zum Kandidatenpool zurückkehren (optional)

### Mitarbeiterdatensatz erstellen

**Zu Mitarbeiter konvertieren:**
1. Bewerbung → **Mitarbeiter erstellen**
2. Bewerbungsdaten werden automatisch ausgefüllt:
   - Name, Kontaktinformationen
   - Position, Abteilung
   - Startdatum
   - Angehängte Dokumente
3. Mitarbeiterspezifische Informationen hinzufügen:
   - Mitarbeiternummer
   - Rang
   - Gehaltsangaben
   - Notfallkontakte
4. Mitarbeiter speichern
5. Mit Bewerbung verknüpfen

## Kommunikation

### Bewerber per E-Mail kontaktieren

**E-Mail senden:**
1. Bewerbung(en) auswählen
2. Klicken Sie auf **E-Mail senden**
3. Vorlage wählen:
   - Bewerbung erhalten
   - In Prüfung
   - Vorstellungsgesprächs-Einladung
   - Vorstellungsgesprächs-Nachverfolgung
   - Angebotsschreiben
   - Ablehnungsschreiben
   - Um weitere Informationen bitten
4. Nachricht anpassen
5. Dokumente anhängen
6. Senden

**E-Mail-Vorlagen:**
- Vorgeschriebene Nachrichten
- Serienfeldern (Name, Position, etc.)
- Professionelle Formatierung
- Compliance-Sprache
- Abmeldemöglichkeit

**E-Mail-Tracking:**
- Gesendet Datum/Uhrzeit
- Geöffnet (falls Tracking aktiviert)
- Links geklickt
- Beantwortet
- Zurückgewiesen/Fehlgeschlagen

### Kommunikationsprotokoll

**Alle Kommunikationen:**
Vollständige Historie anzeigen:
- Gesendete/empfangene E-Mails
- Protokollierte Telefonanrufe
- Vorstellungsgesprächs-Einladungen
- Angebotsschreiben
- Sonstige Korrespondenz

## Berichterstattung & Analysen

### Bewerbungsberichte

**Verfügbare Berichte:**

1. **Bewerbungs-Pipeline**
   - Bewerbungen nach Phase
   - Konversionsraten
   - Zeit in jeder Phase
   - Engpässe

2. **Quellenanalyse**
   - Woher Bewerber kommen
   - Qualität nach Quelle
   - Kosten pro Einstellung nach Quelle
   - ROI-Analyse

3. **Zeit bis zur Einstellung**
   - Durchschnittliche Tage bis zur Einstellung
   - Nach Position
   - Nach Abteilung
   - Trend im Zeitverlauf

4. **Bewerber-Demografie**
   - Geografische Verteilung
   - Altersspannen
   - Bildungsniveaus
   - Erfahrungsstufen

5. **Einstellungs-Funnel**
   - Gesamtbewerbungen
   - Geprüft
   - Vorstellungsgespräche geführt
   - Angebote gemacht
   - Angenommen
   - Konversionsraten

**Bericht generieren:**
1. Klicken Sie auf **Berichte**
2. Berichtstyp auswählen
3. Parameter festlegen:
   - Datumsbereich
   - Positionen
   - Abteilungen
   - Status
4. Generieren
5. Exportieren (PDF/Excel)

### Recruiting-Kennzahlen

**Schlüsselkennzahlen:**
- Bewerbungen pro Position
- Zeit bis zum ersten Vorstellungsgespräch
- Vorstellungsgespräch zu Angebot Verhältnis
- Angebotsannahmequote
- Durchschnittliche Zeit bis zur Einstellung
- Kosten pro Einstellung
- Quelleneffektivität
- Bewerberqualitäts-Score

## Administration & Konfiguration

### Admin-Menü-Zugriff

**Erforderliche Berechtigung:** `ADMIN_READ_APPLICATION`

**Admin-Einstellungen zugreifen:**
1. Desktop → Start → Administration → Bewerbungen → Fragen
2. Oder direkt: `/admin/applicationquestions`

### Route: `/admin/applicationquestions`

**Bewerbungssystem-Konfiguration:**
Bewerbungsfragen und Vorlagen konfigurieren

### Bewerbungsfragen

**Fragen verwalten:**
1. Navigieren Sie zu `/admin/applicationquestions`
2. Alle individuellen Fragen anzeigen

**Frage erstellen:**
1. Klicken Sie auf **+ Neue Frage**
2. Frage konfigurieren:

**Frageneinstellungen:**
- **Fragentext**: Die zu stellende Frage
  - Beispiel: "Was interessiert Sie an dieser Position?"
- **Fragentyp**: Format
  - Kurztext (einzeilig)
  - Langtext (Absatz)
  - Multiple Choice
  - Checkbox (mehrere Antworten)
  - Dropdown
  - Datum
  - Datei-Upload
  - Bewertungsskala
  - Ja/Nein
- **Erforderlich**: Muss beantwortet werden
- **Hilfetext**: Zusätzliche Anleitung
- **Platzhalter**: Beispielantwort
- **Validierung**: Regeln
  - Minimallänge
  - Maximallänge
  - Musterabgleich (E-Mail, Telefon)
- **Position**: Welche Positionen stellen diese Frage
  - Alle Positionen
  - Spezifische Positionen
- **Anzeigereihenfolge**: Fragensequenz

**Für Multiple Choice/Dropdown:**
- Antwortoptionen hinzufügen
- Standardauswahl festlegen
- "Andere" Option erlauben

3. Frage speichern

**Standardfragen:**
- Persönliche Informationen (automatisch enthalten)
- Arbeitsberechtigung
- Bildungsniveau
- Jahre Erfahrung
- Verfügbarkeit
- Gehaltsvorstellungen
- Wie haben Sie von uns erfahren?

**Individuelle Fragen Beispiele:**
- "Beschreiben Sie Ihre relevante Erfahrung..."
- "Warum möchten Sie hier arbeiten?"
- "Was sind Ihre Karriereziele?"
- "Haben Sie erforderliche Zertifikate?"
- "Sind Sie bereit umzuziehen?"
- "Bevorzugte Schicht/Zeitplan?"

### Fragenvorlagen

**Fragenvorlage erstellen:**
1. Admin → Bewerbungen → Fragenvorlagen
2. Verwandte Fragen gruppieren:

**Vorlagenbeispiele:**
- **Einstiegsposition**:
  - Nur grundlegende Fragen
  - Betonung auf Lernbereitschaft
  - Verfügbarkeitsfragen
- **Führungsposition**:
  - Managementerfahrung
  - Führungsphilosophie
  - Strategisches Denken Fragen
- **Technische Position**:
  - Technische Fähigkeitsbewertung
  - Portfolio/Projektbeispiele
  - Problemlösungs-Szenarien

**Vorlage anwenden:**
Beim Erstellen neuer Position:
- Vorlage auswählen
- Fragen werden automatisch ausgefüllt
- Nach Bedarf anpassen

### Positionskonfiguration

**Positionen konfigurieren:**
1. Admin → Bewerbungen → Positionen
2. Position erstellen/bearbeiten:

**Positionseinstellungen:**
- **Titel**: Stellenbezeichnung
- **Abteilung**: Welche Abteilung
- **Beschreibung**: Vollständige Stellenbeschreibung
- **Anforderungen**: Benötigte Qualifikationen
- **Bewerbungsfragen**: Welche Fragen zu stellen sind
- **Workflow**: Individuelle Phasen falls nötig
- **Auto-Antworten**: E-Mail-Vorlagen
- **Aktiv**: Für Bewerbungen offen

### Workflow-Konfiguration

**Individueller Workflow:**
1. Admin → Bewerbungen → Workflow
2. Phasen definieren:
   - Phasen hinzufügen/entfernen
   - Phasen umbenennen
   - Phasen neu ordnen
   - Phasenfarben festlegen
   - Phasenaktionen konfigurieren

**Phasenaktionen:**
- Auto-E-Mail an Bewerber
- Prüfer zuweisen
- Erinnerung setzen
- Integration auslösen

### E-Mail-Vorlagen-Konfiguration

**Vorlagen verwalten:**
1. Admin → Bewerbungen → E-Mail-Vorlagen
2. Vorlagen erstellen/bearbeiten:

**Vorlageneinstellungen:**
- **Name**: Vorlagen-Identifikator
- **Betreff**: E-Mail-Betreff
- **Text**: E-Mail-Inhalt
  - Rich-Text-Editor
  - Serienfelder: {{name}}, {{position}}, etc.
  - Professionelle Formatierung
- **Auslöser**: Wann zu senden
  - Manuell
  - Auto bei Statusänderung
  - Geplant (X Tage danach)
- **Anhänge**: Standardanhänge

**Standardvorlagen:**
- Bewerbungseingangsbestätigung
- Bewerbung in Prüfung
- Vorstellungsgesprächs-Einladung
- Vorstellungsgesprächs-Erinnerung
- Dankeschön nach Vorstellungsgespräch
- Angebotsschreiben
- Ablehnung (allgemein)
- Ablehnung (nach Vorstellungsgespräch)

### Integrationseinstellungen

**Integrieren mit:**
1. Admin → Bewerbungen → Integrationen

**Verfügbare Integrationen:**
- **Jobbörsen**: Indeed, LinkedIn, ZipRecruiter
  - Positionen automatisch veröffentlichen
  - Bewerbungen importieren
- **Hintergrundprüfung**: Drittanbieter-Services
  - Auto-Auslösung bei Angebot
  - Ergebnisse empfangen
- **Assessment-Tools**: Fähigkeitstests
  - Test-Links senden
  - Punktzahlen importieren
- **Kalender**: Outlook, Google
  - Vorstellungsgespräche synchronisieren
  - Verfügbarkeitsprüfung
- **ATS-Export**: Export in andere Systeme
  - Strukturierte Daten
  - Compliance-Formate

## Best Practices

**Empfohlen:**
✅ Bewerbungen zeitnah prüfen
✅ Auf alle Bewerber antworten
✅ Detaillierte Notizen führen
✅ Konsistent bei Bewertung sein
✅ Referenzen überprüfen
✅ Kommunikation aufrechterhalten
✅ Alle Interaktionen verfolgen
✅ Nach Vorstellungsgesprächen nachverfolgen

**Nicht empfohlen:**
❌ Bewerbungen ignorieren
❌ Feedback übermäßig verzögern
❌ Übereilte Entscheidungen treffen
❌ Dokumentation überspringen
❌ Kommunikation verlieren
❌ Rechtliche Compliance vergessen
❌ Voreingenommenheit bei Bewertung
❌ Kandidaten zu viel versprechen

## Fehlerbehebung

### Bewerbung kann nicht eingereicht werden
- Alle Pflichtfelder überprüfen
- Dateigrößenlimits verifizieren
- Internetverbindung überprüfen
- Anderen Browser versuchen

### Status kann nicht geändert werden
- Berechtigungen verifizieren
- Workflow-Regeln überprüfen
- Sicherstellen, dass Bewerbung nicht gesperrt ist
- Seite aktualisieren

### E-Mail wird nicht gesendet
- E-Mail-Vorlage überprüfen
- Bewerber-E-Mail verifizieren
- Spam-Ordner überprüfen
- E-Mail-Service-Status überprüfen

### Dokumente werden nicht hochgeladen
- Dateigröße überprüfen (< 10MB)
- Dateiformat verifizieren
- Speicherplatz überprüfen
- Andere Datei versuchen

## Tastenkombinationen
- **Strg+N**: Neue Bewerbung (manuelle Eingabe)
- **Strg+F**: Bewerber finden
- **Strg+E**: Bewerbung bearbeiten
- **Strg+S**: Änderungen speichern
- **Strg+R**: Bewerbung bewerten
- **Esc**: Dialoge schließen

---

## Nächste Schritte
- [👥 Mitarbeiterverwaltung](/guide/employee-management)
- [📅 Vorstellungsgesprächs-Kalender](/guide/calendar)
- [📧 E-Mail-Kommunikation](/guide/messages)
- [⚙️ Admin: Bewerbungsfragen](/guide/admin/application-questions)
