# Brandschutz & Prüfungen

Umfassendes Brandschutzprüfungs- und Vollzugssystem zur Verwaltung von Gebäudeprüfungen, Verstößen, Bußgeldern, Nachkontrollen und Compliance-Tracking.

## Überblick

Funktionen:
- Brandschutzprüfungen
- Gebäude-Code-Compliance
- Verstoßverfolgung und Bußgelder
- Nachkontrollenplanung
- Prüfberichte und Dokumentation
- Einsatzvorplanung
- Nutzungsverwaltung
- Gefahrenerkennung
- Brandschutzaufklärung
- Prüfverlauf
- Bußgeldvollzug
- Gerichtsterminnachverfolgung
- Compliance-Überprüfung
- Immobiliendatenbank
- Prüferzuweisungen

## Voraussetzungen

**Erforderliche Berechtigung:** `READ_FIREPROTECTION` (zum Ansehen), `WRITE_FIREPROTECTION` (zum Durchführen von Prüfungen)

**Admin-Konfiguration:**
- Prüfungsarten und Checklisten
- Verstoßcodes
- Bußgeldvorlagen
- Prüfzeitpläne
- Prüferzuweisungen

## Brandschutz öffnen

**Desktop:** Doppelklick auf **Brandschutz**-Symbol
**Menü:** Startmenü → Einsätze → Brandschutz
**Route:** `/fireprotection`

## Oberflächenlayout

### Haupt-Dashboard

**Oberer Bereich:**
- **Fällige Prüfungen**: Heute geplante Prüfungen
- **Überfällig**: Verspätete Prüfungen
- **Offene Verstöße**: Aktive Verstöße
- **Nachkontrollen**: Ausstehende Wiederholungsprüfungen
- **Ausgestellte Bußgelder**: Aktive Bußgelder

**Dashboard-Metriken:**
- Abgeschlossene Prüfungen (Monat)
- Compliance-Rate
- Aktive Verstöße
- Ausstehende Bußgelder
- Geprüfte Immobilien

### Seitenleiste

**Schnellaktionen:**
- + Neue Prüfung
- Prüfung planen
- Bußgeld ausstellen
- Immobilien suchen
- Verstoßsuche

**Filter:**
- Alle Immobilien
- Heute fällig
- Überfällig
- Nach Prüfer
- Nach Nutzungsart
- Nur Verstöße
- Nur Bußgelder

**Ansichten:**
- Kalenderansicht
- Listenansicht
- Kartenansicht
- Immobilienansicht
- Prüferansicht

## Immobilienverwaltung

### Immobiliendatenbank

**Immobilieninformationen:**
- **Adresse**: Vollständige Straßenadresse
- **Nutzungsart**:
  - Versammlungsstätte
  - Geschäft
  - Bildungseinrichtung
  - Fabrik/Industrie
  - Institution
  - Verkaufsstätte
  - Wohngebäude
  - Lager
  - Mischnutzung
- **Personenlast**: Maximale Personenzahl
- **Bauart**: I, II, III, IV, V
- **Gebäudefläche**: Quadratmeter
- **Anzahl Geschosse**: Höhe
- **Sprinkleranlage**: Ja/Nein/Teil
- **Brandmeldeanlage**: Typ und Überwachung
- **Steigleitungssystem**: Ja/Nein
- **Feuerlöscher**: Standorte

**Eigentümerinformationen:**
- Eigentümername
- Kontaktinformationen
- Postanschrift
- E-Mail und Telefon
- Notfallkontakt

**Mieterinformationen:**
- Firmenname
- Kontaktperson
- Telefon und E-Mail
- Öffnungszeiten
- Anzahl Mitarbeiter

### Immobilie hinzufügen

**Immobiliendatensatz erstellen:**
1. Klicken Sie auf **+ Immobilie hinzufügen**
2. Immobiliendetails ausfüllen:

**Grundinformationen:**
- **Adresse**: Straßenadresse (erforderlich)
  - Auto-Vervollständigung aus Karte
  - Geocodierung der Koordinaten
- **Flurstücksnummer**: Steuer-Flurstück-ID
- **Immobilientyp**:
  - Gewerbe
  - Wohngebäude (Mehrfamilienhaus)
  - Industrie
  - Institution
  - Mischnutzung
- **Eigentümer**: Mit Firma oder Person verknüpfen
- **Mieter**: Aktueller Bewohner

**Gebäudedetails:**
- **Bauart**: Typ I-V
- **Baujahr**: Baudatum
- **Quadratmeterzahl**: Gesamtfläche
- **Geschosse**: Anzahl Etagen
- **Keller**: Ja/Nein
- **Dachboden**: Ja/Nein

**Brandschutzsysteme:**
- **Sprinkleranlage**:
  - Keine
  - Teil
  - Vollständige Abdeckung
  - Typ: Nass, Trocken, Vorsteuerung
  - Überwachung: Ja/Nein
- **Brandmeldeanlage**:
  - Typ
  - Überwacht
  - Zonen
  - Zuletzt getestet
- **Steigleitung**: Typ und Standort
- **Feuerlöscher**: Anzahl und Typ
- **Notbeleuchtung**: Ja/Nein
- **Fluchtwegschilder**: Anzahl
- **Brandschutztüren**: Standorte
- **Feuerwehrschlüsseldepot**: Ja/Nein, Standort

**Gefahren:**
- Vorhandene Gefahrstoffe
- Lagerungsart
- Mengen
- Sicherheitsdatenblätter vorhanden
- Besondere Überlegungen

3. Klicken Sie auf **Immobilie speichern**

### Immobilienverlauf

**Verlauf anzeigen:**
1. Immobilie öffnen
2. Gehe zum **Verlauf**-Tab
3. Sehen:
   - Alle Prüfungen
   - Gefundene Verstöße
   - Ausgestellte Bußgelder
   - Nachkontrollen
   - Systemänderungen
   - Eigentümerwechsel
   - Einsätze am Standort

**Zeitachsenansicht:**
- Chronologischer Verlauf
- Farbcodierte Ereignisse
- Klicken für Details
- Nach Typ filtern
- Verlauf exportieren

## Prüfungen

### Prüfungsarten

**Routineprüfung:**
- Regelmäßig geplante Prüfung
- Jährlich oder halbjährlich
- Vollständige Immobilienüberprüfung
- Code-Compliance-Prüfung

**Nachkontrolle:**
- Verstoßkorrektur überprüfen
- Bestimmte Punkte erneut prüfen
- Kürzere Dauer
- Fokus auf vorherige Verstöße

**Beschwerdeprüfung:**
- Reaktion auf Bürgerbeschwerden
- Spezifisches Problem untersuchen
- Befunde dokumentieren
- Kann zu Bußgeld führen

**Einsatzvorplanung:**
- Gebäudemerkmale dokumentieren
- Gefahren identifizieren
- Zugangswege planen
- Wasserversorgungsstandorte
- Besondere Überlegungen

**Nutzungsänderung:**
- Neue Mieterprüfung
- Compliance überprüfen
- Informationen aktualisieren
- Nutzungsgenehmigung ausstellen

**Bauprüfung:**
- Neubau
- Renovierungsarbeiten
- Brandschutzsysteme
- Abschlussprüfung

### Prüfung planen

**Prüfung erstellen:**
1. Klicken Sie auf **+ Neue Prüfung** oder **Prüfung planen**
2. Details ausfüllen:

**Prüfungsinformationen:**
- **Immobilie**: Aus Datenbank auswählen
  - Nach Adresse suchen
  - Oder neue Immobilie erstellen
- **Prüfungsart**: Typ auswählen
- **Geplantes Datum**: Wann prüfen
- **Geplante Zeit**: Zeitfenster
- **Prüfer**: Prüfer zuweisen
  - Auto-Zuweisung nach Zone
  - Oder bestimmten Prüfer auswählen
- **Priorität**:
  - Routine
  - Hohe Priorität
  - Dringend
  - Nachkontrolle

**Prüfungsdetails:**
- **Grund**: Warum prüfen
  - Geplante Routine
  - Beschwerde
  - Nachkontrolle
  - Nutzungsänderung
  - Systeminstallation
- **Besondere Anweisungen**: Hinweise für Prüfer
- **Vorherige Verstöße**: Bei Nachkontrolle überprüfen
- **Zu prüfende Bereiche**: Spezifischer Fokus
- **Geschätzte Dauer**: Benötigte Zeit

**Benachrichtigungen:**
- **Eigentümer benachrichtigen**: Benachrichtigung senden
- **Vorankündigung**: Tage Vorlauf erforderlich
- **Kontaktmethode**: E-Mail, Telefon, Post
- **Termin erforderlich**: Mit Eigentümer vereinbaren

3. Klicken Sie auf **Planen**

**Prüfkalender:**
- Kalenderansicht der Prüfungen
- Nach Prüfer oder nach Tag
- Ziehen zum Umplanen
- Farbcodiert nach Typ
- Klicken für Details

### Prüfung durchführen

**Prüfung beginnen:**
1. Geplante Prüfung öffnen
2. Klicken Sie auf **Prüfung beginnen**
3. Immobilieninfo überprüfen
4. Checkliste aufrufen

**Prüfcheckliste:**

**Gebäudeäußeres:**
- [ ] Adresse deutlich sichtbar
- [ ] Feuerwehrzufahrt frei
- [ ] Feuerwehrschlüsseldepot zugänglich
- [ ] Feuerlöschanschluss zugänglich
- [ ] Außenausgänge markiert
- [ ] Vegetationspflege

**Brandschutzsysteme:**
- [ ] Sprinkleranlage betriebsbereit
- [ ] Letztes Prüfdatum aktuell
- [ ] Brandmeldeanlage funktionsfähig
- [ ] Feuerlöscher vorhanden
- [ ] Feuerlöscher aktuell (jährlich)
- [ ] Notbeleuchtung funktioniert
- [ ] Fluchtwegschilder beleuchtet

**Fluchtwege:**
- [ ] Ausgangstüren betriebsbereit
- [ ] Fluchtwege frei
- [ ] Richtige Fluchtbeschilderung
- [ ] Notbeleuchtung
- [ ] Treppen unversperrt
- [ ] Türbeschläge funktionsfähig
- [ ] Panikbeschläge funktionieren

**Brandschutztüren:**
- [ ] Selbstschließend
- [ ] Verriegelt richtig
- [ ] Nicht verkeilt
- [ ] Dichtungen intakt
- [ ] Kennzeichnung sichtbar

**Elektrik:**
- [ ] Zugang zum Verteiler frei
- [ ] Keine überlasteten Stromkreise
- [ ] Verlängerungskabel ordnungsgemäß verwendet
- [ ] Notstromversorgung funktionsfähig

**Gefahrstoffe:**
- [ ] Ordnungsgemäß gelagert
- [ ] Sicherheitsdatenblätter verfügbar
- [ ] Mengen innerhalb der Grenzen
- [ ] Richtige Behälter
- [ ] Ausreichende Belüftung

**Nutzung:**
- [ ] Personenlast ausgehängt
- [ ] Innerhalb der Grenzen
- [ ] Ordnungsgemäße Raumnutzung
- [ ] Keine nicht genehmigten Änderungen

**Allgemein:**
- [ ] Hausordnung angemessen
- [ ] Keine Lagerung bei Ausgängen
- [ ] Keine Lagerung beim Verteiler
- [ ] Feuerwehrzugang

**Für jeden Punkt:**
- Bestanden/Nicht bestanden markieren
- Fotos hinzufügen
- Notizen hinzufügen
- Verstöße markieren
- Korrekturmaßnahmen eingeben

**Während der Prüfung:**
- Fotos von Verstößen machen
- Mit Notizen dokumentieren
- Auf Grundriss markieren
- Messungen aufzeichnen
- Bewohner befragen
- Wartungsprotokolle überprüfen
- Systeme bei Bedarf testen

### Prüfung abschließen

**Prüfung finalisieren:**
1. Alle Checklistenpunkte überprüfen
2. Befunde zusammenfassen:
   - Punkte in Compliance
   - Gefundene Verstöße
   - Identifizierte Gefahren
   - Empfehlungen
3. Abschließende Notizen hinzufügen
4. Unterschrift erfassen:
   - Prüferunterschrift
   - Eigentümer-/Bewohnerunterschrift
   - Datum und Uhrzeit
5. Klicken Sie auf **Prüfung abschließen**

**Prüfbericht:**
- Automatisch aus Checkliste generiert
- Alle Fotos einschließen
- Alle Verstöße auflisten
- Erforderliche Korrekturmaßnahmen
- Korrekturfristen
- Prüferempfehlungen

**Berichtsverteilung:**
- E-Mail an Eigentümer
- E-Mail an Mieter
- Kopie zur Akte
- Kopie an Prüfer
- Im System archivieren

## Verstöße

### Verstoßcodes

**Standard-Verstoßcodes:**

**Ausgänge und Fluchtwege:**
- **101**: Ausgangstür verschlossen/blockiert
- **102**: Fluchtweg versperrt
- **103**: Fluchtweg-Schild nicht beleuchtet
- **104**: Notbeleuchtung außer Betrieb

**Brandschutz:**
- **201**: Sprinkleranlage beeinträchtigt
- **202**: Brandmeldeanlage außer Betrieb
- **203**: Feuerlöscher fehlt/abgelaufen
- **204**: Brandschutztür nicht selbstschließend

**Elektrik:**
- **301**: Überlastete Stromkreise
- **302**: Verlängerungskabel-Missbrauch
- **303**: Elektroverteilung blockiert

**Gefahrstoffe:**
- **401**: Unsachgemäße Lagerung
- **402**: Fehlende Sicherheitsdatenblätter
- **403**: Menge überschreitet Grenzwert
- **404**: Unzureichende Belüftung

**Allgemein:**
- **501**: Hausordnungsproblem
- **502**: Lagerung zu nah an Heizung
- **503**: Personenlast überschritten
- **504**: Nicht genehmigte Nutzungsänderung

**Benutzerdefinierte Codes:**
- Admin kann erstellen
- Zuständigkeitsspezifisch
- Lokale Vorschriften durchsetzen
- Mit Codeabschnitten verknüpfen

### Verstoß ausstellen

**Verstoß erstellen:**
1. Während Prüfung Checklistenpunkt als nicht bestanden markieren
2. Klicken Sie auf **Verstoß hinzufügen**
3. Verstoßdetails ausfüllen:

**Verstoßinformationen:**
- **Code**: Verstoßcode auswählen
- **Beschreibung**: Was ist falsch
  - Spezifischer Standort
  - Was gefunden
  - Codereferenz
  - Warum Verstoß
- **Schweregrad**:
  - Geringfügig: Keine Lebensgefahr
  - Mäßig: Korrektur erforderlich
  - Schwer: Lebensgefahrenproblem
  - Kritisch: Unmittelbare Gefahr
- **Fotos**: Verstoßfotos anhängen
- **Standort**: Wo im Gebäude

**Erforderliche Korrektur:**
- **Korrekturmaßnahme**: Was getan werden muss
  - Spezifische Schritte
  - Klare Anforderungen
  - Erforderliche Code-Compliance
- **Korrekturfrist**: Datum zur Behebung
  - Basierend auf Schweregrad
  - Geringfügig: 30 Tage
  - Mäßig: 14 Tage
  - Schwer: 7 Tage
  - Kritisch: 24 Stunden
- **Geschätzte Kosten**: Falls zutreffend
- **Genehmigung erforderlich**: Falls Arbeiten Genehmigung benötigen

**Nachkontrolle:**
- **Nachkontrolle erforderlich**: Ja/Nein
- **Nachkontrolle planen**: Auto oder manuell
- **Eigentümer benachrichtigen**: Verstoßmitteilung senden
- **Bußgeld**: Wird Bußgeld ausgestellt?

4. Klicken Sie auf **Verstoß ausstellen**

**Verstoßmitteilung:**
- An Eigentümer/Mieter gesendet
- Listet alle Verstöße auf
- Korrekturanforderungen
- Fristen
- Nachkontroll-Info
- Bußgeldwarnung
- Einspruchsverfahren

## Bußgelder

### Bußgeld ausstellen

**Wann Bußgeld:**
- Verstoß nicht bis Frist korrigiert
- Kritischer Verstoß
- Wiederholungsverstoß
- Verweigerung der Korrektur
- Lebensgefahr

**Bußgeld erstellen:**
1. Verstoß öffnen
2. Klicken Sie auf **Bußgeld ausstellen**
3. Bußgeld ausfüllen:

**Bußgeldinformationen:**
- **Bußgeldnummer**: Automatisch generiert
- **Verstoß**: Mit Verstoß(en) verknüpfen
- **Immobilie**: Adresse
- **Verletzer**:
  - Eigentümer
  - Mieter
  - Verantwortliche Partei
- **Codeabschnitt**: Rechtliche Referenz
- **Verstoßbeschreibung**: Was verletzt
- **Bußgeldbetrag**: Strafe
  - Basierend auf Verstoßcode
  - Eskalierende Bußgelder
  - Tägliche Bußgelder bei fortlaufend

**Rechtliche Details:**
- **Bußgelddatum**: Ausstellungsdatum
- **Zustellmethode**:
  - Persönliche Zustellung
  - Einschreiben
  - Aushang
- **Zustelldatum**: Wann zugestellt
- **Fälligkeitsdatum**: Zahlung oder Erscheinen bis
- **Gerichtstermin**: Falls zutreffend
- **Gerichtsstandort**: Wo erscheinen

**Optionen für Verletzer:**
- **Bußgeld zahlen**: Betrag und wie
- **Anhörung beantragen**: Bußgeld anfechten
- **Korrigieren und Einstellung beantragen**: Problem beheben
- **Zahlungsplan**: Falls verfügbar

4. Bußgeld drucken oder per E-Mail versenden
5. Bußgeld zustellen
6. Klicken Sie auf **Ausstellen**

**Bußgeldverfolgung:**
- Status: Ausgestellt, Bezahlt, Anhörung, Eingestellt
- Zahlung erhalten
- Anhörung geplant
- Gerichtsergebnis
- Beitreibungsstatus

### Bußgeldverwaltung

**Bußgeldstatus:**

**Ausgestellt:**
- Bußgeld zugestellt
- Warte auf Reaktion
- Fälligkeitsdatum ausstehend
- Kann zahlen oder anfechten

**Bezahlt:**
- Bußgeld vollständig bezahlt
- Kann noch Korrektur erfordern
- Schließen oder weiter überwachen
- Quittung ausstellen

**Anhörung beantragt:**
- Verletzer fechtet an
- Anhörung planen
- Beweise vorbereiten
- Anhörung beiwohnen
- Auf Entscheidung warten

**Eingestellt:**
- Verstoß korrigiert
- Bußgeld zurückgezogen
- Anhörungsergebnis
- Grund dokumentieren

**Überfällig:**
- Nicht bis Fälligkeitsdatum bezahlt
- Nicht angefochten
- Eskalierende Bußgelder
- Beitreibungsverfahren
- Mögliches Gericht

**Alle Bußgelder anzeigen:**
1. Klicken Sie auf **Bußgelder**
2. Filtern nach:
   - Status
   - Datumsbereich
   - Prüfer
   - Immobilie
   - Bußgeldbetrag
3. Liste exportieren
4. Massenoperationen

## Nachkontrollen

### Nachkontrolle planen

**Automatische Nachkontrolle:**
- System plant automatisch
- Basierend auf Korrekturfrist
- Zugewiesen an ursprünglichen Prüfer
- Eigentümer benachrichtigt

**Manuelle Nachkontrolle:**
1. Verstoß öffnen
2. Klicken Sie auf **Nachkontrolle planen**
3. Datum auswählen (nach Frist)
4. Prüfer zuweisen
5. Notizen hinzufügen
6. Speichern

**Nachkontroll-Checkliste:**
- Ursprüngliche Verstöße überprüfen
- Korrekturen überprüfen
- Compliance dokumentieren
- Nachher-Fotos machen
- Verstoßstatus aktualisieren

### Nachkontrolle durchführen

**Nachprüfungsverfahren:**
1. Geplante Nachkontrolle öffnen
2. Klicken Sie auf **Prüfung beginnen**
3. Ursprüngliche Verstöße überprüfen
4. Für jeden Verstoß:
   - **Korrigiert**: Als konform markieren
     - Verifizierungsfoto machen
     - Dokumentieren was getan wurde
     - Datum korrigiert
   - **Nicht korrigiert**: Immer noch Verstoß
     - Dokumentieren warum
     - Frist verlängern oder Bußgeld
     - Aktuelle Fotos machen
   - **Teilweise korrigiert**: Teilfortschritt
     - Dokumentieren was erledigt ist
     - Was noch aussteht
     - Neue Frist setzen

5. Prüfung abschließen
6. Verstoßstatus aktualisieren
7. Bericht generieren

**Nachkontrollergebnisse:**

**Alles korrigiert:**
- Verstöße schließen
- Compliance-Brief senden
- Immobilienstatus aktualisieren
- Nächste Routineprüfung planen

**Teilweise korrigiert:**
- Korrigierte Punkte schließen
- Frist für andere verlängern
- Oder Bußgeld ausstellen
- Weitere Nachkontrolle planen

**Nichts korrigiert:**
- Bußgeld ausstellen
- Vollzug eskalieren
- Rechtliche Schritte
- Gerichtsverfahren

## Compliance & Berichte

### Compliance-Tracking

**Immobilien-Compliance:**
- **Konform**: Keine aktiven Verstöße
- **Geringfügige Probleme**: Unkritische Verstöße
- **Schwere Probleme**: Ernste Verstöße
- **Nicht konform**: Kritische Verstöße
- **Unbekannt**: Nicht kürzlich geprüft

**Compliance-Dashboard:**
- Compliance-Rate nach Nutzungsart
- Immobilien nach Status
- Verstöße nach Typ
- Bußgeldeffektivität
- Korrekturrate
- Durchschnittliche Korrekturzeit

**Zuständigkeitsweite Statistiken:**
- Gesamtimmobilien
- Abgeschlossene Prüfungen
- Compliance-Prozentsatz
- Aktive Verstöße
- Ausgestellte Bußgelder
- Eingenommene Einnahmen

### Prüfberichte

**Verfügbare Berichte:**

**1. Prüfprotokoll:**
- Alle durchgeführten Prüfungen
- Datum, Immobilie, Prüfer
- Befundzusammenfassung
- Gefundene Verstöße
- Status

**2. Verstoßbericht:**
- Alle aktiven Verstöße
- Nach Schweregrad
- Nach Typ
- Nach Immobilie
- Alterungsbericht

**3. Bußgeldbericht:**
- Ausgestellte Bußgelder
- Statusverfolgung
- Zahlungsstatus
- Gerichtstermine
- Einnahmen

**4. Prüferaktivität:**
- Prüfungen pro Prüfer
- Gefundene Verstöße
- Ausgestellte Bußgelder
- Compliance-Rate
- Produktivität

**5. Immobilienbericht:**
- Immobilien nach Typ
- Prüfhäufigkeit
- Compliance-Verlauf
- Verstoßtrends
- Risikobewertung

**6. Nutzungsbericht:**
- Nach Nutzungsart
- Compliance-Raten
- Häufige Verstöße
- Prüfpläne

**Bericht generieren:**
1. Berichtstyp auswählen
2. Datumsbereich festlegen
3. Filter konfigurieren
4. Vorschau
5. Export:
   - PDF
   - Excel
   - CSV
6. Wiederkehrend planen

## Einsatzvorplanung

### Vorplan erstellen

**Einsatzvorplan:**
1. Während Prüfung oder separat
2. Klicken Sie auf **Vorplan erstellen**
3. Dokumentieren:

**Gebäudeinformationen:**
- Baudetails
- Anzahl Geschosse
- Nutzungsinformationen
- Personenlast
- Betriebszeiten

**Brandschutz:**
- Sprinklerabdeckung
- Steigleitungsstandorte
- Brandmeldezonen
- Feuerlöschanschluss-Standort
- Absperrungen

**Zugang:**
- Haupteingänge
- Notfallzugangspunkte
- Feuerwehrschlüsseldepot-Standort
- Dachzugang
- Kellerzugang

**Gefahren:**
- Gefahrstoffe
- Spezielle Prozesse
- Hochwertige Bereiche
- Hochrisikobereiche
- Bauliche Bedenken

**Versorgungseinrichtungen:**
- Gasabsperrung
- Stromabsperrung
- Wasserabsperrung
- HLK-Steuerungen

**Taktische Überlegungen:**
- Bereitstellungsräume
- Wasserversorgung
- Gefährdungen
- Benötigte Spezialausrüstung
- Evakuierungswege

**Diagramme:**
- Grundrisse
- Lageplan
- Wasserversorgungskarte
- Fotos
- Luftaufnahme

4. Vorplan speichern

**Vorplan-Zugriff:**
- In Leitstelle verfügbar
- Im Feld zugänglich
- Mobile App-Zugriff
- Regelmäßige Updates
- Jährliche Überprüfung

## Best Practices

**Empfohlen:**
✅ Prüfungen konsistent planen
✅ Gründlich mit Fotos dokumentieren
✅ Verstöße klar erklären
✅ Angemessene Fristen setzen
✅ Verstöße nachverfolgen
✅ Professionelles Auftreten wahren
✅ Immobilieneigentümer aufklären
✅ Vorpläne aktuell halten
✅ Bußgeldergebnisse verfolgen
✅ Regelmäßige Schulung zu Vorschriften

**Nicht empfohlen:**
❌ Immobilienbenachrichtigungen überspringen
❌ Dokumentation fehlt
❌ Geringfügige Verstöße ignorieren
❌ Inkonsistent bei Vollzug sein
❌ Nachkontrollen vergessen
❌ Prüfungen überstürzen
❌ Codeanforderungen nicht erklären
❌ Verstöße über Frist hinaus lassen
❌ Einsatzvorpläne vernachlässigen
❌ Sicherheit während Prüfungen ignorieren

## Fehlerbehebung

### Immobilie nicht in Datenbank
- Klicken Sie auf **Immobilie hinzufügen**
- Vollständige Informationen eingeben
- Adressgenauigkeit überprüfen
- Mit Eigentümer/Mieter verknüpfen
- Prüfung beginnen

### Kann Prüfung nicht planen
- Prüferverfügbarkeit prüfen
- Immobilie existiert überprüfen
- Richtige Berechtigungen sicherstellen
- Datumsvalidität prüfen
- Systemeinstellungen überprüfen

### Verstoß wird nicht korrigiert
- Bußgeld ausstellen
- Vollzug verstärken
- Nicht-Compliance dokumentieren
- Rechtliche Beratung
- Gerichtsverfahren

### Bußgeld nicht erhalten
- Zustellmethode überprüfen
- Postadresse prüfen
- Bei Bedarf erneut zustellen
- Versuche dokumentieren
- Aushang erwägen

### Bericht wird nicht generiert
- Datumsbereich prüfen
- Daten vorhanden überprüfen
- Angewendete Filter prüfen
- Browser-Cache leeren
- Administrator kontaktieren

## Tastaturkürzel

- **Strg+N**: Neue Prüfung
- **Strg+S**: Prüfung speichern/abschließen
- **Strg+F**: Immobilie finden
- **Strg+V**: Verstöße anzeigen
- **Strg+C**: Bußgelder anzeigen
- **Strg+P**: Bericht drucken
- **Strg+R**: Bericht generieren
- **Esc**: Dialoge schließen

---

## Nächste Schritte
- [🚒 Einsatzleitung](/guide/dispatch)
- [🗺️ Karte & Standorte](/guide/map)
- [📄 Dokumente](/guide/documents)
- [📊 Berichte](/guide/reports)
- [⚙️ Admin: Brandschutzeinstellungen](/guide/admin/fireprotection)
