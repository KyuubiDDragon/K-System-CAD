# Mannschaftsverwaltung & Dienstplanung

Umfassendes Mannschaftsverwaltungssystem zur Organisation von Schichten, Zuweisungen, Qualifikationen und Verfügbarkeitsverfolgung für Einsatzteams.

## Überblick

Funktionen:
- Mannschaftsplanung und Schichtverwaltung
- Schichtdienstplan-Erstellung
- Personalzuweisung
- Qualifikationsverfolgung
- Verfügbarkeitsverwaltung
- Schichttausch und -wechsel
- Überstundenverfolgung
- Mindestpersonalanforderungen
- Zertifizierungsverwaltung
- Integration mit Leitstelle
- Urlaubskoordination
- Schichtvorlagen
- Automatische Dienstplanung
- Berichterstattung und Analysen

## Anforderungen

**Erforderliche Berechtigung:** `READ_CREW` (zum Anzeigen), `WRITE_CREW` (zum Verwalten)

**Admin-Konfiguration:**
- Schichtmuster: A/B/C-Schichten, 24/48, etc.
- Mindestpersonalstärken
- Qualifikationsanforderungen
- Integration mit Leitstellensystem

## Mannschaftsverwaltung öffnen

**Desktop:** Doppelklicken Sie auf das **Mannschaft**-Symbol
**Menü:** Startmenü → Einsatz → Mannschaftsverwaltung
**Route:** `/crew`

## Oberflächen-Layout

### Haupt-Dashboard

**Oberer Bereich:**
- **Aktuelle Schicht**: Wer jetzt im Dienst ist
- **Bevorstehende Schichten**: Nächste 7 Tage
- **Personalstatus**: Aktuell vs. erforderlich
- **Verfügbarkeit**: Wer verfügbar ist
- **Warnungen**: Unterbesetzungswarnungen

**Schichtkalender:**
- Monats-/Wochenansicht
- Farbcodiert nach Schicht
- Auf Tag klicken für Details
- Drag-and-Drop-Zuweisungen
- Visuelle Personalindikatoren

### Seitenleiste

**Schnellaktionen:**
- + Schicht erstellen
- Personal zuweisen
- Verfügbarkeit anzeigen
- Schichttausch
- Urlaubsanträge

**Filter:**
- Alle Mannschaften
- Nach Wache
- Nach Schicht (A/B/C)
- Nach Position
- Nach Qualifikation
- Nur Verfügbare

**Ansichten:**
- Kalenderansicht
- Dienstplanansicht
- Listenansicht
- Zeitachsenansicht
- Personalmatrix

## Schichtkonfiguration

### Schichtmuster

**Gängige Schichtmuster:**

**24/48-Dienstplan:**
- 24 Stunden im Dienst
- 48 Stunden frei
- 3 Schichten (A, B, C)
- Rotierender Zyklus

**12-Stunden-Schichten:**
- Tagschicht: 7-19 Uhr
- Nachtschicht: 19-7 Uhr
- 2 Schichten (Tag/Nacht)
- Rotierender Dienstplan

**8-Stunden-Schichten:**
- Erste Schicht: 7-15 Uhr
- Zweite Schicht: 15-23 Uhr
- Dritte Schicht: 23-7 Uhr
- 3 Schichten pro Tag

**Kelly-Tage:**
- Zusätzliche freie Tage
- Überstunden ausgleichen
- Konfigurierbarer Dienstplan
- Automatische Berechnung

### Schichtmuster erstellen

**Muster definieren:**
1. Klicken Sie auf **Schichtmuster**
2. Klicken Sie auf **+ Neues Muster**
3. Konfigurieren Sie:

**Basisinformationen:**
- **Name**: Mustername (z.B. "24/48 A-Schicht")
- **Typ**:
  - 24-Stunden
  - 12-Stunden
  - 8-Stunden
  - Benutzerdefiniert
- **Zykluslänge**: Tage in Rotation
- **Schichtbezeichnungen**: A, B, C, etc.

**Zeitplanung:**
- **Startzeit**: Schicht beginnt
- **Endzeit**: Schicht endet
- **Dauer**: Gesamtstunden
- **Überlappung**: Übergabezeit

**Rotation:**
- **Rotationstyp**:
  - Täglich
  - Wöchentlich
  - Benutzerdefinierter Zyklus
- **Richtung**: Vorwärts oder rückwärts
- **Kelly-Tag-Dienstplan**: Automatisch oder manuell

**Abdeckung:**
- **Mindestpersonal**: Erforderliches Personal
- **Nach Position**:
  - Offiziere: 1
  - Maschinisten: 1
  - Feuerwehrleute: 2
  - Rettungssanitäter: 1
- **Flexibles Personal**: Zusätzliche Abdeckung

4. Klicken Sie auf **Muster speichern**

### Schichtmuster zuweisen

**Auf Mannschaft anwenden:**
1. Wählen Sie Mannschaft oder Wache
2. Klicken Sie auf **Muster zuweisen**
3. Wählen Sie Schichtmuster
4. Setzen Sie Startdatum
5. Konfigurieren Sie:
   - Welches Personal
   - Positionszuweisungen
   - Ausnahmedaten
   - Feiertagsbehandlung
6. Dienstplan generieren
7. Überprüfen und genehmigen

## Mannschaftsplanung

### Dienstplan erstellen

**Schichtdienstplan erstellen:**
1. Klicken Sie auf **Dienstplan erstellen**
2. Wählen Sie:
   - Zeitraum (Monat/Quartal)
   - Wachen/Mannschaften
   - Schichtmuster
3. Klicken Sie auf **Automatisch generieren**
4. System erstellt Dienstplan basierend auf:
   - Schichtmustern
   - Qualifikationen
   - Verfügbarkeit
   - Früheren Zuweisungen
   - Fairness-Rotation

**Dienstplan überprüfen:**
- Alle Positionen abgedeckt prüfen
- Qualifikationen erfüllt verifizieren
- Überstundenverteilung überprüfen
- Auf Konflikte prüfen
- Mindestpersonal sicherstellen

**Manuelle Anpassungen:**
- Personal auf verschiedene Schichten ziehen
- Zuweisungen tauschen
- Zusätzliche Abdeckung hinzufügen
- Ausnahmen markieren
- Notizen hinzufügen

5. Klicken Sie auf **Dienstplan veröffentlichen**

### Manuelle Zuweisung

**Personal zu Schicht zuweisen:**
1. Öffnen Sie Schichtdatum
2. Klicken Sie auf **Personal zuweisen**
3. Wählen Sie Position:
   - Offizier/Hauptmann
   - Maschinist/Fahrer
   - Feuerwehrmann
   - Rettungssanitäter/Notfallsanitäter
   - Vertretende Funktion
4. Wählen Sie Person aus verfügbarer Liste
5. Filter zeigen:
   - Qualifiziertes Personal
   - Verfügbar (nicht frei)
   - Nicht in anderer Schicht
   - Anforderungen erfüllend
6. Klicken Sie auf **Zuweisen**

**Mehrfachzuweisungen:**
- Alle Positionen auf einmal zuweisen
- Vorlagen verwenden
- Aus vorheriger Schicht kopieren
- Massenoperationen

### Schichtvorlagen

**Vorlage erstellen:**
1. Idealen Schichtdienstplan erstellen
2. Alle Positionen zuweisen
3. Vorlage benennen (z.B. "Löschzug 1 Standard")
4. Speichern

**Vorlage verwenden:**
1. Wählen Sie Schichtdatum
2. Klicken Sie auf **Vorlage anwenden**
3. Wählen Sie Vorlage
4. System weist zu basierend auf:
   - Verfügbarkeit
   - Rotationsfairness
   - Qualifikationen
5. Überprüfen und bestätigen

**Vorlagenfunktionen:**
- Positionsanforderungen
- Mindestqualifikationen
- Bevorzugtes Personal
- Ersatzoptionen
- Notizen und besondere Anweisungen

## Personalverwaltung

### Mannschaftsmitglieder

**Personal hinzufügen:**
1. Klicken Sie auf **Personal**
2. Klicken Sie auf **+ Mitglied hinzufügen**
3. Mit Mitarbeiterdatensatz verknüpfen
4. Mannschaftsinformationen setzen:

**Mannschaftsdetails:**
- **Wachenzuweisung**: Hauptwache
- **Schichtzuweisung**: A, B, C, etc.
- **Primäre Position**:
  - Offizier
  - Maschinist
  - Feuerwehrmann
  - Notfallsanitäter
- **Sekundäre Position**: Ersatzrolle
- **Einstellungsdatum**: Startdatum
- **Dienstalter**: Dienstjahre

**Kontaktinformationen:**
- Notfallkontakte
- Telefonnummern
- E-Mail
- Adresse

**Beschäftigung:**
- Vollzeit oder Teilzeit
- Regulär oder in Probezeit
- Gehaltsgruppe
- Gewerkschaftsmitgliedschaft

4. Klicken Sie auf **Speichern**

### Positionsanforderungen

**Positionen definieren:**
1. Admin → Mannschaftseinstellungen
2. Klicken Sie auf **Positionen**
3. Für jede Position konfigurieren:

**Positionsdetails:**
- **Titel**: Offizier, Maschinist, etc.
- **Mindestqualifikationen**: Erforderliche Zertifikate
- **Erforderliche Erfahrung**: Jahre
- **Erforderliche Schulung**: Kurse
- **Körperliche Anforderungen**: Standards
- **Berichtet an**: Befehlskette

**Dienstplanung:**
- **Minimum pro Schicht**: Wie viele benötigt
- **Maximum pro Schicht**: Obergrenze
- **Kann höher eingesetzt werden**: Höhere Position ausfüllen
- **Kann allein arbeiten**: Oder erfordert Aufsicht

**Vergütung:**
- **Basistarif**: Reguläre Bezahlung
- **Überstundensatz**: Multiplikator
- **Vertretungszulage**: Bei höherer Rolle
- **Sonderzulage**: Gefahr, etc.

### Qualifikationen & Zertifizierungen

**Qualifikationen verfolgen:**
1. Wählen Sie Personal
2. Gehen Sie zum **Qualifikationen**-Tab
3. Klicken Sie auf **Qualifikation hinzufügen**
4. Geben Sie ein:

**Qualifikationsdetails:**
- **Typ**:
  - Zertifizierung
  - Lizenz
  - Schulungsabschluss
  - Besondere Fähigkeit
- **Name**: FW1, FW2, RS, Notfallsanitäter, etc.
- **Ausstellende Behörde**: Land, NFPA, etc.
- **Nummer**: Zertifikatsnummer
- **Ausstellungsdatum**: Wann erhalten
- **Ablaufdatum**: Wann abläuft
- **Dokument**: Zertifikat hochladen

**Status:**
- Aktuell (gültig)
- Läuft bald ab (Warnung)
- Abgelaufen (benötigt Erneuerung)
- In Bearbeitung (Schulung)

**Erneuerung:**
- Auto-Erinnerungen vor Ablauf
- Erneuerungsprozess verfolgen
- Neues Zertifikat hochladen
- Ablaufdatum aktualisieren

**Erforderliche Qualifikationen:**
- Anforderungen pro Position setzen
- System warnt bei fehlender Qualifikation
- Kann nicht zuweisen bei fehlenden erforderlichen Zertifikaten
- Qualifikationsabdeckung verfolgen

## Verfügbarkeitsverwaltung

### Verfügbarkeit markieren

**Personal markiert Verfügbar/Nicht verfügbar:**
1. Kalender öffnen
2. Daten auswählen
3. Klicken Sie auf **Verfügbarkeit setzen**
4. Wählen Sie Status:
   - Verfügbar
   - Nicht verfügbar
   - Für Überstunden verfügbar
   - Eingeschränkte Verfügbarkeit
5. Geben Sie Grund ein (optional)
6. Speichern

**Verfügbarkeitstypen:**

**Verfügbar:**
- Normalstatus
- Kann eingeteilt werden
- Grüner Indikator

**Nicht verfügbar:**
- Kann nicht eingeteilt werden
- Gründe:
  - Urlaub
  - Krankheitsurlaub
  - Persönlicher Urlaub
  - Krankheitsurlaub
  - Militärdienst
  - Schulung
  - Sonstiges
- Roter Indikator

**Für Überstunden verfügbar:**
- Verfügbar für Zusatzschichten
- Bereit mehr zu arbeiten
- Blauer Indikator

**Eingeschränkte Verfügbarkeit:**
- Verfügbar bestimmte Stunden
- Besondere Bedingungen
- Gelber Indikator

### Freistellungsanträge

**Freistellung beantragen:**
1. Klicken Sie auf **Freistellung beantragen**
2. Antrag ausfüllen:

**Antragsdetails:**
- **Typ**:
  - Urlaub
  - Krankheitsurlaub
  - Persönlicher Tag
  - Ausgleichszeit
  - FMLA
  - Militärurlaub
- **Startdatum**: Erster freier Tag
- **Enddatum**: Letzter freier Tag
- **Teilzeit**: Nur Stunden
- **Grund**: Optionale Beschreibung

**Genehmigung:**
- An Vorgesetzten übermittelt
- Personalauswirkung prüfen
- Genehmigen oder ablehnen
- Mitarbeiter benachrichtigen
- Verfügbarkeit aktualisieren

**Freistellungsguthaben:**
- Verfügbare Urlaubsstunden
- Krankheitsurlaubsguthaben
- Ausgleichszeitguthaben
- Jahr-bis-heute-Nutzung
- Ansammlungsrate

### Schichttausch

**Schichttausch beantragen:**
1. Finden Sie Ihre Schicht
2. Klicken Sie auf **Tausch beantragen**
3. Optionen:

**Tauschtypen:**

**Mit bestimmter Person tauschen:**
- Wählen Sie anderes Mannschaftsmitglied
- Wählen Sie deren Schicht zum Tauschen
- Beide müssen zustimmen
- Vorgesetzter genehmigt
- Schichten getauscht

**Offener Tauschantrag:**
- Schicht jedem Qualifizierten anbieten
- Erster der akzeptiert bekommt sie
- Muss genehmigt werden
- Schicht neu zugewiesen

**Teiltausch:**
- Teil der Schicht tauschen
- Stunden angeben
- Geteilte Schichtabdeckung
- Muss Mindestpersonal erfüllen

**Tauschprozess:**
1. Antragsteller reicht ein
2. Tauschpartner akzeptiert/lehnt ab
3. Vorgesetzter genehmigt/lehnt ab
4. Dienstplan aktualisiert automatisch
5. Beide Parteien benachrichtigt

**Tauschregeln:**
- Muss für Position qualifiziert sein
- Kann keine Überstunden schaffen (außer genehmigt)
- Mindestpersonal aufrechterhalten
- Faire Tauschpraktiken
- Maximale Tausche pro Monat (optional)

## Mindestpersonal

### Anforderungen konfigurieren

**Mindestpersonal setzen:**
1. Admin → Mannschaftseinstellungen
2. Klicken Sie auf **Mindestpersonal**
3. Konfigurieren nach:

**Nach Wache:**
- Wache 1: 4 Personal
- Wache 2: 6 Personal
- Wache 3: 3 Personal

**Nach Fahrzeug:**
- Löschfahrzeug: 3 Minimum (1 Offizier, 1 Maschinist, 1 FM)
- Drehleiter: 4 Minimum
- Rettungswagen: 2 Minimum (1 Notfallsanitäter erforderlich)

**Nach Position:**
- Offiziere: 1 pro Wache
- Maschinisten: 1 pro Fahrzeug
- Feuerwehrleute: 2 pro Löschfahrzeug
- Notfallsanitäter: 1 pro Rettungswagen

**Nach Zeit:**
- Tagschicht: Höhere Minima
- Nachtschicht: Niedrigere Minima
- Wochenenden: Angepasstes Personal
- Feiertage: Besondere Anforderungen

### Personalwarnungen

**Automatische Überwachung:**
- System prüft Personal kontinuierlich
- Warnt bei Unterschreitung Minimum
- Identifiziert Lücken im Dienstplan
- Schlägt Lösungen vor

**Warntypen:**

**Unterbesetzungswarnung:**
- Schicht unter Minimum
- Wer benötigt wird
- Verfügbare Personalliste
- Überstundenkandidaten

**Keine Abdeckung:**
- Kritische Position unbesetzt
- Sofortiges Handeln erforderlich
- Automatische Rückrufoptionen
- Nachbarschaftshilfe-Koordination

**Überfällig:**
- Ablösung nicht arrangiert
- Schichtübernahme erforderlich
- Betroffenes Personal benachrichtigen
- Übernahmestunden verfolgen

### Personallösungen

**Bei Unterbesetzung:**

**Option 1: Aus Verfügbaren besetzen:**
- Qualifiziertes Personal auflisten
- Überstundenverfügbarkeit prüfen
- Automatische Anrufe/SMS
- Erster der antwortet wird zugewiesen

**Option 2: Schichtübernahme:**
- Aktuelle Schicht verlängern
- Überstunden für aktuelle Mannschaft
- Übernahmezeit verfolgen
- Ablösung so schnell wie möglich arrangieren

**Option 3: Rückruf:**
- Dienstfreies Personal zurückrufen
- Nach Dienstalter oder Rotation kontaktieren
- Rückrufzeit verfolgen
- Nach Vertrag vergüten

**Option 4: Nachbarschaftshilfe:**
- Von anderen Wachen anfordern
- Abdeckung koordinieren
- Hilfe verfolgen
- Für Aufzeichnungen dokumentieren

## Überstundenverwaltung

### Überstundenverfolgung

**Automatische Berechnung:**
- Gearbeitete vs. geplante Stunden
- Über regulären Dienstplan hinaus
- Übernahmen
- Zusatzschichten
- Rückrufe

**Überstundentypen:**

**Geplante Überstunden:**
- Geplante Zusatzschichten
- Freiwillige Anmeldung
- Vorankündigung
- Regulärer Überstundensatz

**Notfall-Überstunden:**
- Ungeplante Bedürfnisse
- Pflicht-Rückrufe
- Kurzfristige Benachrichtigung
- Kann höherer Satz sein

**Übernahme:**
- Schichtverlängerung
- Keine Ablösung verfügbar
- Anderthalbfacher Satz
- Begrenzte Dauer

**Rückruf:**
- Dienstfrei zurückgerufen
- Mindeststunden garantiert
- Höherer Satz
- Fahrzeit kann zählen

### Überstundenverteilung

**Faire Verteilung:**
- Überstunden pro Person verfolgen
- Ausgleichsliste
- Nach Rotation anbieten
- Über Mannschaft ausgleichen

**Überstundenliste:**
1. **Überstundenliste** anzeigen
2. Zeigt Rangfolge:
   - Name
   - Gesamte Überstunden Jahr-bis-heute
   - Letztes Überstundendatum
   - Nächster in Rotation
   - Verfügbarkeitsstatus

**Überstunden anbieten:**
1. Überstundenschicht verfügbar
2. System bietet nächstem auf Liste an
3. Bei Ablehnung zum nächsten
4. Angebote und Ablehnungen verfolgen
5. Fairness aufrechterhalten

**Erzwungene Überstunden:**
- Wenn keine Freiwilligen
- Umgekehrte Dienstalter-Reihenfolge
- Oder Rotationsbasis
- Grund dokumentieren
- Begrenzte Häufigkeit

### Überstundenberichte

**Überstunden-Analysen:**
- Gesamte Überstunden nach Monat
- Kostenanalyse
- Aufschlüsselung pro Person
- Nach Grund (geplant, Notfall, etc.)
- Trends über Zeit
- Budgetauswirkung

**Berichte exportieren:**
- Lohnbuchhaltungsintegration
- Excel-Export
- Benutzerdefinierte Datumsbereiche
- Nach Personal/Wache filtern

## Integration mit Leitstelle

### Echtzeit-Mannschaftsstatus

**Leitstellenintegration:**
- Aktueller Mannschaftsdienstplan in Leitstelle sichtbar
- Wer auf welchem Fahrzeug
- Qualifikationen angezeigt
- Kontaktinformationen
- Statusaktualisierungen

**Automatische Aktualisierungen:**
- Schichtänderungen aktualisieren Leitstelle
- Neue Zuweisungen reflektiert
- Urlaubsaktualisierungen Verfügbarkeit
- Zertifizierungen aktuell

### Fahrzeugbesatzung

**Fahrzeugmannschaft anzeigen:**
1. Leitstelle wählt Fahrzeug
2. Sehen Sie aktuelle Mannschaft:
   - Namen und Positionen
   - Zertifizierungen
   - Kontaktinformationen
   - Besondere Qualifikationen
3. Vor Alarmierung verifizieren

**Mannschaftsänderungen:**
- Vertretende Zuweisungen angezeigt
- Temporäre Zuweisungen
- Flexibles Personal
- Nachbarschaftshilfe-Personal

**Qualifikationsverifizierung:**
- Erforderliche Zertifikate für Einsatztyp
- System hebt hervor ob erfüllt
- Warnungen bei Fehlen
- Schlägt alternative Fahrzeuge vor

## Berichterstattung & Analysen

### Personalberichte

**Verfügbare Berichte:**

**1. Dienstplanbericht:**
- Aktueller Schichtdienstplan
- Alles Personal und Positionen
- Kontaktinformationen
- Qualifikationen

**2. Personalzusammenfassung:**
- Abdeckung nach Schicht
- Ist vs. Minimum
- Offene Positionen
- Überstundennutzung

**3. Qualifikationsmatrix:**
- Personal vs. Zertifizierungen
- Ablaufverfolgung
- Abdeckung nach Qualifikation
- Schulungsbedarf

**4. Überstundenbericht:**
- Überstunden nach Person
- Überstundenkosten
- Verteilungsfairness
- Trends

**5. Verfügbarkeitsbericht:**
- Wer wann verfügbar
- Urlaubszusammenfassung
- Krankheitsurlaubsnutzung
- Urlaubsguthaben

**6. Schichttauschbericht:**
- Abgeschlossene Tausche
- Tauschablehnungen
- Tauschhäufigkeit
- Tauschmuster

### Berichte generieren

**Bericht erstellen:**
1. Klicken Sie auf **Berichte**
2. Wählen Sie Berichtstyp
3. Konfigurieren Sie:
   - Datumsbereich
   - Wachen/Personal
   - Filter
   - Gruppierung
4. Vorschau
5. Exportieren:
   - PDF
   - Excel
   - CSV
6. Speichern oder per E-Mail senden

## Best Practices

**Tun:**
✅ Aktuelle Qualifikationen pflegen
✅ Verfügbarkeit zeitnah aktualisieren
✅ Schichttausche im Voraus planen
✅ Überstunden fair ausgleichen
✅ Mindestpersonal überwachen
✅ Zertifizierungen proaktiv verfolgen
✅ Dienstplanänderungen kommunizieren
✅ Besondere Umstände dokumentieren
✅ Regelmäßige Dienstplanüberprüfungen
✅ Ersatzpersonal schulen

**Nicht tun:**
❌ Ablaufende Zertifizierungen ignorieren
❌ Last-Minute-Schichtänderungen
❌ Unfaire Überstundenverteilung
❌ Unter Minima einteilen
❌ Verfügbarkeit nicht aktualisieren
❌ Tausche ohne Prüfung genehmigen
❌ Ersatzpositionen vernachlässigen
❌ Übernahmedokumentation verpassen
❌ Personalwarnungen ignorieren
❌ Genehmigungsprozesse überspringen

## Fehlerbehebung

### Kann Personal nicht zuweisen
- Qualifikationen auf Position prüfen
- Verfügbarkeitsstatus verifizieren
- Sicherstellen nicht in anderer Schicht
- Maximale Stundengrenzen prüfen
- Genehmigungsanforderungen überprüfen

### Dienstplan generiert nicht
- Schichtmuster konfiguriert verifizieren
- Ausreichendes Personal prüfen
- Qualifikationsanforderungen überprüfen
- Verfügbarkeitsdaten aktuell sicherstellen
- Datumsbereichsgültigkeit prüfen

### Überstunden berechnen nicht
- Schichtstunden korrekt eingegeben verifizieren
- Basisdienstplan definiert prüfen
- Überstundenregelkonfiguration überprüfen
- Übernahmen protokolliert sicherstellen
- Urlaubsabzüge prüfen

### Qualifikation läuft bald ab
- Ablaufdaten überprüfen
- Erneuerungserinnerungen senden
- Erneuerungsprozess verfolgen
- Neue Zertifikate hochladen
- System sofort aktualisieren

### Personalwarnung zeigt nicht
- Mindestpersonal konfiguriert prüfen
- Warneinstellungen aktiviert verifizieren
- Benachrichtigungseinstellungen überprüfen
- Warnschwellen prüfen
- Dashboard aktualisieren

## Tastaturkürzel

- **Strg+N**: Neue Schichtzuweisung
- **Strg+F**: Personal finden
- **Strg+A**: Verfügbarkeit anzeigen
- **Strg+T**: Schichttausch
- **Strg+O**: Überstundenliste anzeigen
- **Strg+Q**: Qualifikationen
- **Strg+R**: Bericht generieren
- **←/→**: Vorherige/Nächste Woche
- **Esc**: Dialoge schließen

---

## Nächste Schritte
- [🚒 Leitstellenbetrieb](/guide/dispatch)
- [🚗 Fahrzeugverwaltung](/guide/vehicles)
- [👥 Mitarbeiterverwaltung](/guide/employee-management)
- [📊 Berichte](/guide/reports)
- [⚙️ Admin: Mannschaftseinstellungen](/guide/admin/crew)
