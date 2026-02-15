# Schwarzes Brett & Ankündigungssystem

Das Schwarze Brett-System bietet ein zentrales Bulletin Board für organisationsweite Ankündigungen, Beiträge und Kommunikation, die für Ihr gesamtes Team sichtbar sind.

## Übersicht

Funktionen:
- Organisationsweite Ankündigungen
- Kategoriebasierte Organisation
- Beitragsplanung und Ablauf
- Rich-Text-Formatierung
- Dateianhänge
- Kommentare und Diskussionen
- Prioritätsstufen
- Lesebestätigungen
- E-Mail-Benachrichtigungen
- Wichtige Beiträge anheften
- Suche und Filterung
- Alte Beiträge archivieren
- Rollenbasierte Posting-Berechtigungen

## Anforderungen

**Erforderliche Berechtigung:** `READ_BLACKBOARD` (zum Anzeigen), `WRITE_BLACKBOARD` (zum Erstellen von Beiträgen)

**Admin-Konfiguration:**
- Kategorien: In Admin-Einstellungen einrichten
- Berechtigungen: Konfigurieren, wer posten kann
- Benachrichtigungen: Alarme aktivieren/deaktivieren

## Schwarzes Brett öffnen

**Desktop:** Doppelklick auf **Schwarzes Brett**-Symbol
**Menü:** Startmenü → Kommunikation → Schwarzes Brett
**Route:** `/blackboard`

## Oberflächenlayout

### Hauptansicht

**Oberer Bereich:**
- **Angeheftete Beiträge**: Wichtige Ankündigungen oben
- **Aktuelle Beiträge**: Neueste Ankündigungen
- **Kategorien**: Nach Kategorie filtern
- **Suche**: Bestimmte Beiträge finden

**Beitragsanzeige:**
- Titel und Autor
- Veröffentlichungsdatum
- Kategorie-Badge
- Prioritätsindikator
- Gelesen/Ungelesen-Status
- Kommentaranzahl
- Anhanganzahl

### Seitenleiste

**Schnellaktionen:**
- + Neuer Beitrag
- Meine Beiträge
- Entwürfe
- Geplante Beiträge

**Filter:**
- Alle Beiträge
- Nur ungelesene
- Nach Kategorie
- Nach Priorität
- Nach Datumsbereich
- Nur angeheftete
- Meine Beiträge

**Kategorien:**
- Allgemeine Ankündigungen
- Unternehmensnachrichten
- Veranstaltungen
- Schulung
- Sicherheit
- Richtlinien-Updates
- Benutzerdefinierte Kategorien

## Beiträge erstellen

### Neuer Beitrag

**Ankündigung erstellen:**
1. Klicken Sie auf **+ Neuer Beitrag**
2. Beitragsformular ausfüllen:

**Grundinformationen:**
- **Titel**: Klarer, beschreibender Titel (erforderlich)
  - Max. 200 Zeichen
  - Sollte Inhalt zusammenfassen
  - Beispiel: "Büroschließung - Feiertagsplan"
- **Kategorie**: Passende Kategorie auswählen (erforderlich)
  - Allgemein
  - Nachrichten
  - Veranstaltungen
  - Schulung
  - Sicherheit
  - Richtlinien
  - Benutzerdefiniert
- **Priorität**: Wichtigkeitsstufe festlegen
  - 🔴 Hoch: Dringend, erfordert sofortige Aufmerksamkeit
  - 🟡 Mittel: Wichtig aber nicht dringend
  - 🟢 Normal: Reguläre Ankündigungen
- **Inhalt**: Vollständige Nachricht (erforderlich)
  - Rich-Text-Editor
  - Formatierungsoptionen
  - Links unterstützt
  - Max. 10.000 Zeichen

**Sichtbarkeit:**
- **Zielgruppe**:
  - Jeder (Standard)
  - Bestimmte Abteilungen
  - Bestimmte Rollen
  - Bestimmte Benutzer
- **Bestätigung erforderlich**: Benutzer müssen Lesen bestätigen
- **E-Mail-Benachrichtigung senden**: Alle Empfänger per E-Mail

**Planung:**
- **Sofort veröffentlichen**: Beitrag sofort posten (Standard)
- **Für später planen**: Zukünftiges Veröffentlichungsdatum/-zeit festlegen
- **Ablaufdatum**: Nach Datum automatisch entfernen
  - Optional
  - Beitrag wird automatisch archiviert
  - Gut für temporäre Ankündigungen

**Anhänge:**
- Klicken Sie auf **Anhang hinzufügen**
- Dateien hochladen (bis zu 10 Dateien)
- Max. 20MB pro Datei
- Unterstützt: PDF, DOC, XLS, Bilder, etc.

**Anzeigeoptionen:**
- **Oben anheften**: Oben im Board halten
- **Kommentare erlauben**: Diskussionen ermöglichen
- **Autor anzeigen**: Anzeigen, wer gepostet hat
- **Hervorgehoben**: Mit speziellem Styling hervorheben

3. Klicken Sie auf **Veröffentlichen** oder **Entwurf speichern**

### Beitragsvorlagen

**Als Vorlage speichern:**
1. Beitrag mit häufigem Format erstellen
2. Klicken Sie auf **Als Vorlage speichern**
3. Vorlage benennen
4. Für zukünftige ähnliche Beiträge verwenden

**Häufige Vorlagen:**
- Wöchentliche Ankündigungen
- Veranstaltungseinladungen
- Schulungshinweise
- Richtlinien-Updates
- Sicherheitswarnungen

**Vorlagen verwenden:**
1. Klicken Sie auf **+ Neuer Beitrag**
2. Wählen Sie **Aus Vorlage**
3. Vorlage auswählen
4. Inhalt anpassen
5. Veröffentlichen

### Entwurfsverwaltung

**Entwurf speichern:**
- Klicken Sie auf **Entwurf speichern** während Erstellung
- Später weiter bearbeiten
- Für Benutzer nicht sichtbar
- Auto-Speicherung alle 30 Sekunden

**Entwürfe anzeigen:**
1. Seitenleiste → **Entwürfe**
2. Alle unveröffentlichten Beiträge sehen
3. Klicken zum Weiterbearbeiten
4. Veröffentlichen wenn bereit

**Entwurfsfunktionen:**
- Auto-Speicherungsschutz
- Revisionsverlauf
- Entwurf mit anderen zur Überprüfung teilen
- Erinnerungen zum Fertigstellen setzen

## Beitragskategorien

### Standardkategorien

**Allgemeine Ankündigungen:**
- Organisationsweite Nachrichten
- Wichtige Updates
- Allgemeine Informationen
- Farbe: Blau

**Unternehmensnachrichten:**
- Geschäftsupdates
- Erfolge
- Neueinstellungen
- Unternehmensmeilensteine
- Farbe: Grün

**Veranstaltungen:**
- Bevorstehende Veranstaltungen
- Besprechungen
- Konferenzen
- Soziale Zusammenkünfte
- Farbe: Lila

**Schulung:**
- Schulungsankündigungen
- Kursverfügbarkeit
- Zertifizierungen
- Bildungsmöglichkeiten
- Farbe: Orange

**Sicherheit:**
- Sicherheitswarnungen
- Notfallverfahren
- Sicherheitserinnerungen
- Vorfallberichte
- Farbe: Rot

**Richtlinien-Updates:**
- Richtlinienänderungen
- Verfahrensupdates
- Compliance-Anforderungen
- Dokumentationsänderungen
- Farbe: Gelb

### Benutzerdefinierte Kategorien

**Admin kann erstellen:**
1. Admin → Schwarzes Brett-Einstellungen
2. Klicken Sie auf **Kategorie hinzufügen**
3. Konfigurieren:
   - Name
   - Beschreibung
   - Farbe
   - Symbol
   - Wer kann posten
   - E-Mail-Benachrichtigungen
4. Speichern

**Kategoriefunktionen:**
- Benutzerdefinierte Namen
- Benutzerdefinierte Farben und Symbole
- Berechtigungskontrollen
- Standard-Benachrichtigungseinstellungen
- Auto-Kategorisierungsregeln

### Kategoriefilterung

**Nach Kategorie filtern:**
1. Kategorie in Seitenleiste anklicken
2. Nur Beiträge in dieser Kategorie anzeigen
3. Oder mehrere Kategorien auswählen
4. Filter löschen, um alle zu sehen

## Beiträge verwalten

### Beitragsdetails anzeigen

**Beitrag öffnen:**
Auf Beitrag klicken, um vollständige Details anzuzeigen:
- Vollständiger Inhalt
- Alle Anhänge
- Kommentare
- Lesebestätigungen (falls aktiviert)
- Bearbeitungsverlauf
- Wer angesehen hat

**Beitragsinformationen:**
- Autor und Datum
- Zuletzt bearbeitet
- Anzahl Aufrufe
- Anzahl gelesen (falls verfolgt)
- Bestätigungsanzahl (falls erforderlich)

### Beitrag bearbeiten

**Bestehenden Beitrag bearbeiten:**
1. Beitrag öffnen
2. Klicken Sie auf **Bearbeiten** (falls Sie Berechtigung haben)
3. Inhalt ändern
4. Bearbeitungsgrund (optional, verfolgt)
5. Klicken Sie auf **Aktualisieren**

**Bearbeitungsberechtigungen:**
- Autor kann immer bearbeiten
- Administratoren können alle bearbeiten
- Bearbeiten innerhalb Zeitlimit (konfigurierbar)

**Bearbeitungsverlauf:**
- Alle Änderungen verfolgt
- Frühere Versionen anzeigen
- Sehen, wer wann bearbeitet hat
- Frühere Version wiederherstellen

### Beitrag löschen

**Löschoptionen:**

**Soft-Delete (Archivieren):**
1. Beitrag öffnen
2. Klicken Sie auf **Archivieren**
3. Beitrag vom Hauptboard ausgeblendet
4. In Archiven zugänglich
5. Kann später wiederhergestellt werden

**Permanent löschen:**
1. Beitrag öffnen
2. Klicken Sie auf **Löschen** → **Permanent löschen**
3. Bestätigung eingeben
4. WARNUNG: Kann nicht rückgängig gemacht werden
5. Kommentare werden auch gelöscht

**Löschberechtigungen:**
- Autor kann eigene Beiträge löschen
- Administratoren können jeden Beitrag löschen
- Löschung im Audit-Trail protokolliert

### Beiträge anheften/lösen

**Wichtigen Beitrag anheften:**
1. Beitrag öffnen
2. Klicken Sie auf **Oben anheften**
3. Beitrag bleibt oben im Board
4. Mehrere Beiträge können angeheftet werden
5. Angehefteter Bereich oben

**Lösen:**
1. Angehefteten Beitrag öffnen
2. Klicken Sie auf **Lösen**
3. Kehrt zu chronologischer Reihenfolge zurück

**Best Practices für Anheften:**
- Nur wirklich wichtige Elemente anheften
- Maximum 3-5 angeheftete Beiträge
- Lösen, wenn nicht mehr dringend
- Angeheftete Beiträge wöchentlich überprüfen

## Kommentare & Diskussionen

### Kommentare hinzufügen

**Beitrag kommentieren:**
1. Beitrag öffnen
2. Zu **Kommentare**-Bereich scrollen
3. Kommentar eingeben
4. Optional: Anhänge hinzufügen
5. Klicken Sie auf **Kommentar posten**

**Kommentarfunktionen:**
- Rich-Text-Formatierung
- @Erwähnungen (Benutzer benachrichtigen)
- Dateianhänge
- Emoji-Reaktionen
- Thread-Antworten
- Eigene Kommentare bearbeiten
- Eigene Kommentare löschen

### Kommentar-Threads

**Auf Kommentar antworten:**
1. Klicken Sie auf **Antworten** unter Kommentar
2. Antwort eingeben
3. Erstellt Thread-Unterhaltung
4. Eingerückte Anzeige
5. Threads ein-/ausklappen

**Thread-Funktionen:**
- Mehrstufige Threads
- Thread folgen
- Als gelöst markieren
- Thread-Benachrichtigungen

### Erwähnungen

**Benutzer erwähnen:**
- @ gefolgt von Name eingeben
- Aus Dropdown auswählen
- Benutzer erhält Benachrichtigung
- In Kommentar hervorgehoben
- Beispiel: @john.doe was denken Sie?

**Gruppen erwähnen:**
- @everyone - Alle Benutzer
- @abteilungsname - Bestimmte Abteilung
- @rollenname - Benutzer mit Rolle

### Kommentarmoderation

**Admin/Autor kann:**
- Jeden Kommentar bearbeiten
- Unangemessene Kommentare löschen
- Spam ausblenden
- Benutzer vom Kommentieren sperren
- Kommentare zu Beitrag sperren

**Kommentare sperren:**
1. Beitrag öffnen
2. Klicken Sie auf **Kommentare sperren**
3. Keine neuen Kommentare erlaubt
4. Bestehende Kommentare sichtbar

## Beitragsplanung

### Beitrag planen

**Zukünftigen Beitrag planen:**
1. Beitrag erstellen
2. **Für später planen** aktivieren
3. Datum und Uhrzeit auswählen
4. Zeitzone wählen
5. Speichern

**Geplante Beitragsfunktionen:**
- Zeigt in **Geplante Beiträge**-Liste
- Vor Veröffentlichungszeit bearbeiten
- Planung abbrechen
- Manuell früher veröffentlichen
- E-Mail-Benachrichtigung bei Veröffentlichung

**Geplante anzeigen:**
1. Seitenleiste → **Geplante Beiträge**
2. Alle ausstehenden Beiträge sehen
3. Veröffentlichungsdatum/-zeit angezeigt
4. Bearbeiten oder abbrechen

### Beitragsablauf

**Ablauf festlegen:**
1. Beitrag erstellen/bearbeiten
2. **Ablaufdatum** aktivieren
3. Datum/Uhrzeit auswählen
4. Beitrag wird nach Datum automatisch archiviert

**Ablaufverhalten:**
- Beitrag vom Hauptboard ausgeblendet
- In Archive verschoben
- Erscheint nicht mehr in Suchen
- Kann Ablauf manuell verlängern
- Benutzer mit Link können noch zugreifen

**Anwendungsfälle:**
- Veranstaltungsankündigungen (nach Veranstaltung ablaufen)
- Temporäre Hinweise
- Zeitkritische Informationen
- Saisonale Ankündigungen

### Wiederkehrende Beiträge

**Wiederkehrenden Beitrag erstellen:**
1. Beitragsvorlage erstellen
2. **Wiederkehrend** aktivieren
3. Muster festlegen:
   - Täglich
   - Wöchentlich (Tage auswählen)
   - Monatlich (Datum oder Tag)
   - Benutzerdefinierter Zeitplan
4. Enddatum oder Wiederholungsanzahl festlegen
5. Speichern

**Wiederkehrende Beispiele:**
- Wöchentliche Mitarbeiterbesprechungserinnerungen
- Monatliche Sicherheitstipps
- Vierteljährliche Richtlinienüberprüfungen
- Jährliche Erinnerungen

## Bestätigung & Lesebestätigungen

### Bestätigung erforderlich

**Für Beitrag aktivieren:**
1. Beitrag erstellen/bearbeiten
2. **Bestätigung erforderlich** aktivieren
3. Beitrag mit 🔔 Symbol markiert
4. Benutzer müssen "Ich bestätige" klicken
5. Kann nicht verworfen werden bis bestätigt

**Bestätigungsfunktionen:**
- Verfolgen, wer bestätigt hat
- Verfolgen, wann bestätigt
- Erinnerungen an Nicht-Leser senden
- Bestätigungsbericht exportieren
- Admin kann Compliance sehen

### Lesebestätigungen anzeigen

**Sehen, wer gelesen hat:**
1. Beitrag öffnen
2. Klicken Sie auf **Bestätigungen anzeigen**
3. Liste sehen:
   - Wer gelesen hat
   - Wann gelesen
   - Wer nicht gelesen hat
   - Bestätigungsstatus

**Leseverfolgung:**
- Automatische Verfolgung
- Datenschutz konfigurierbar
- Nützlich für wichtige Beiträge
- Compliance-Dokumentation

### Erinnerungen senden

**Nicht-Leser erinnern:**
1. Beitrag mit Bestätigung öffnen
2. Klicken Sie auf **Erinnerung senden**
3. Auswählen:
   - Bestimmte Benutzer
   - Alle, die nicht gelesen haben
   - Abteilung/Rolle
4. Erinnerungsnachricht verfassen
5. Senden

**Erinnerungsoptionen:**
- E-Mail-Benachrichtigung
- In-App-Benachrichtigung
- Push-Benachrichtigung
- Geplante Erinnerungen (täglich bis gelesen)

## Suche & Filter

### Beiträge suchen

**Suchfeld:**
- Schlüsselwörter eingeben
- Durchsucht:
  - Beitragstitel
  - Inhalt
  - Autorennamen
  - Kommentare
  - Anhänge

**Erweiterte Suche:**
1. Klicken Sie auf **Erweiterte Suche**
2. Konfigurieren:
   - Schlüsselwörter
   - Datumsbereich
   - Autor
   - Kategorie
   - Priorität
   - Hat Anhänge
   - Hat Kommentare
3. Suchen

### Filteroptionen

**Schnellfilter:**
- Alle Beiträge
- Ungelesen
- Hohe Priorität
- Angeheftet
- Erfordert Bestätigung
- Meine Beiträge
- Heutige Beiträge
- Diese Woche

**Erweiterte Filter:**
1. Klicken Sie auf **Filter**-Schaltfläche
2. Mehrere auswählen:
   - Kategorien
   - Prioritäten
   - Datumsbereiche
   - Autoren
   - Abteilungen
   - Lesestatus
3. Anwenden
4. Filterkombination speichern

**Benutzerdefinierte Filter speichern:**
- Filterkombination erstellen
- Klicken Sie auf **Filter speichern**
- Benennen
- Später Schnellzugriff
- Mit anderen teilen

## Benachrichtigungen

### Benachrichtigungstypen

**Schwarzes Brett-Benachrichtigungen:**
- **Neuer Beitrag**: Wenn neuer Beitrag veröffentlicht
- **Kategoriebeitrag**: Beitrag in gefolgter Kategorie
- **Kommentar**: Kommentar zu Ihrem Beitrag
- **Antwort**: Antwort auf Ihren Kommentar
- **Erwähnung**: Sie werden erwähnt
- **Bestätigung erforderlich**: Muss Beitrag bestätigen
- **Erinnerung**: Wichtigen Beitrag nicht gelesen

### Benachrichtigungseinstellungen

**Präferenzen konfigurieren:**
1. Profil → Einstellungen → Benachrichtigungen
2. Gehen Sie zu **Schwarzes Brett**-Bereich
3. Jeden Typ aktivieren/deaktivieren
4. Zustellungsmethode wählen:
   - E-Mail
   - In-App
   - Push-Benachrichtigung
5. Häufigkeit festlegen:
   - Sofort
   - Täglicher Digest
   - Wöchentlicher Digest

**Kategorie-Abonnements:**
1. Kategorie anklicken
2. Klicken Sie auf **Abonnieren**
3. Über neue Beiträge benachrichtigt werden
4. Oder **Abbestellen**

### E-Mail-Benachrichtigungen

**E-Mail-Digest:**
- Option für tägliche Zusammenfassung
- Alle Beiträge der letzten 24 Stunden
- Kategorisiert und formatiert
- Links zum Online-Ansehen
- Anzahl ungelesener

**Sofortige E-Mails:**
- Beiträge mit hoher Priorität
- Beiträge, die Bestätigung erfordern
- @Erwähnungen
- Antworten auf Ihre Kommentare

## Anhänge

### Anhänge hinzufügen

**Dateien an Beitrag anhängen:**
1. Beitrag erstellen/bearbeiten
2. Klicken Sie auf **Anhang hinzufügen**
3. Dateien auswählen
4. Hochladen (bis zu 10 Dateien)
5. Beschreibung hinzufügen (optional)

**Unterstützte Dateitypen:**
- Dokumente: PDF, DOC, DOCX, XLS, XLSX, PPT
- Bilder: JPG, PNG, GIF
- Archive: ZIP
- Text: TXT, CSV
- Andere: Die meisten Dateitypen

**Anhangsfunktionen:**
- Im Browser in Vorschau anzeigen (PDFs, Bilder)
- Einzelne Dateien herunterladen
- Alle als ZIP herunterladen
- Dateigröße angezeigt
- Upload-Datum
- Anzahl Aufrufe

### Anhänge verwalten

**Anhänge bearbeiten:**
1. Beitrag bearbeiten
2. Gehen Sie zu **Anhänge**-Tab
3. Neue Dateien hinzufügen
4. Bestehende entfernen (falls noch keine Downloads)
5. Dateien umbenennen
6. Neu anordnen

**Anhang-Berechtigungen:**
- Alle Betrachter können herunterladen
- Admin kann alle verwalten
- Autor kann eigene bearbeiten

## Archiv & Verlauf

### Archive anzeigen

**Auf archivierte Beiträge zugreifen:**
1. Klicken Sie auf **Archive**-Schaltfläche
2. Alle archivierten Beiträge anzeigen
3. Nach Datum/Kategorie filtern
4. In Archiven suchen

**Archivierte Beiträge:**
- Abgelaufene Beiträge
- Manuell archivierte Beiträge
- Gelöschte Beiträge (Soft-Delete)
- Nur-Lese-Zugriff
- Kann bei Bedarf wiederhergestellt werden

### Archivierten Beitrag wiederherstellen

**Zu Aktiv wiederherstellen:**
1. Archivierten Beitrag öffnen
2. Klicken Sie auf **Wiederherstellen**
3. Beitrag kehrt zum Hauptboard zurück
4. Ablauf bei Bedarf aktualisieren
5. Optional Benutzer benachrichtigen

### Verlauf exportieren

**Beiträge exportieren:**
1. Datumsbereich auswählen
2. Kategorien wählen
3. Format auswählen:
   - PDF-Bericht
   - Excel-Tabelle
   - CSV-Datei
4. Einschließen:
   - Beitragsinhalt
   - Kommentare
   - Anhänge (separates ZIP)
   - Lesebestätigungen
5. Herunterladen

## Best Practices

**Tun:**
✅ Klare, beschreibende Titel verwenden
✅ Beiträge korrekt kategorisieren
✅ Nur kritische Informationen anheften
✅ Ablauf für zeitkritische Beiträge setzen
✅ Bestätigung für wichtige Punkte erforderlich
✅ Auf Kommentare zeitnah antworten
✅ Alte Beiträge regelmäßig archivieren
✅ Rich-Formatierung für Lesbarkeit verwenden
✅ Relevante Dokumente anhängen
✅ Beiträge für optimales Timing planen

**Nicht tun:**
❌ Sensible Informationen öffentlich posten
❌ Beiträge übermäßig anheften (max. 3-5)
❌ Kommentare und Fragen ignorieren
❌ Titel in Großbuchstaben verwenden
❌ Ohne Korrekturlesen posten
❌ Kategorien nicht setzen
❌ Abgelaufene Beiträge aktiv lassen
❌ Mit zu vielen Beiträgen spammen
❌ Schwarzes Brett für private Kommunikation verwenden
❌ Veraltete Informationen nicht aktualisieren

## Fehlerbehebung

### Kann keinen Beitrag erstellen
- `WRITE_BLACKBOARD`-Berechtigung prüfen
- Verifizieren, dass Funktion für Authority aktiviert
- Kategorieberechtigungen prüfen
- Administrator kontaktieren

### Beitrag nicht sichtbar
- Prüfen, ob für Zukunft geplant
- Zielgruppe schließt Sie ein verifizieren
- Kategoriefilter prüfen
- Sicherstellen, nicht abgelaufen
- Prüfen, ob versehentlich archiviert

### Benachrichtigungen nicht erhalten
- Benachrichtigungseinstellungen prüfen
- E-Mail-Adresse korrekt verifizieren
- Spam-Ordner prüfen
- Sicherstellen, Kategorie abonniert (falls zutreffend)
- E-Mail-Server-Einstellungen prüfen (Admin)

### Kann Beitrag nicht bearbeiten
- Muss Autor oder Admin sein
- Bearbeitungszeitlimit prüfen (falls festgelegt)
- Beitrag kann gesperrt sein
- Administrator kontaktieren

### Anhang-Upload fehlgeschlagen
- Dateigröße prüfen (max. 20MB)
- Dateityp erlaubt verifizieren
- Verfügbaren Speicherplatz prüfen
- Kleinere Datei versuchen oder komprimieren
- Administrator kontaktieren bei Persistenz

## Tastaturkürzel

- **N**: Neuer Beitrag
- **S**: Suchen
- **F**: Filter öffnen
- **R**: Feed aktualisieren
- **P**: Angeheftete Beiträge anzeigen
- **A**: Archive anzeigen
- **↑/↓**: Beiträge navigieren
- **Enter**: Ausgewählten Beitrag öffnen
- **Esc**: Dialoge schließen

---

## Nächste Schritte
- [💬 Nachrichten](/guide/messages)
- [📅 Kalender](/guide/calendar)
- [📝 Dokumente](/guide/documents)
- [⚙️ Admin: Schwarzes Brett-Einstellungen](/guide/admin/blackboard)
