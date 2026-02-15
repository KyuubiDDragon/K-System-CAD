# Urlaubs- & Abwesenheitsübersicht

Mitarbeiterabwesenheitsübersicht mit aktuellen und bevorstehenden Urlaubszeiten mit Filterung nach Zeiträumen und Ranggruppierung.

## Überblick

Funktionen:
- Aktuell abwesende Mitarbeiter anzeigen
- Mitarbeiter sehen, die innerhalb von 7/30 Tagen zurückkehren
- Mitarbeiter mit bevorstehendem Urlaub in 7/30 Tagen sehen
- Nach Rang filtern
- Mitarbeiter suchen
- Gruppierte Anzeige nach Rang

**Erforderliche Berechtigung:** `READ_EMPLOYEE`

## Zugriff auf Urlaubsübersicht

**Route:** `/vacation`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Urlaub**-Symbol
- Menü: Startmenü → Mitarbeiterverwaltung → Urlaub
- Oder direkt zu `/vacation` navigieren

## Oberflächenlayout

### Filter-Schaltflächen

**Abwesenheitsstatus-Filter:**
- **Aktuell abwesend** - Mitarbeiter, die aktuell im Urlaub sind
- **Rückkehr in 7 Tagen** - Mitarbeiter, die innerhalb von 7 Tagen aus dem Urlaub zurückkehren
- **Rückkehr in 30 Tagen** - Mitarbeiter, die innerhalb von 30 Tagen aus dem Urlaub zurückkehren
- **Bevorstehend in 7 Tagen** - Mitarbeiter, die innerhalb von 7 Tagen in den Urlaub gehen
- **Bevorstehend in 30 Tagen** - Mitarbeiter, die innerhalb von 30 Tagen in den Urlaub gehen

**Rangfilter:**
- Filter-Dropdown, um nur bestimmte Ränge anzuzeigen
- Zeigt standardmäßig alle Ränge

**Suche:**
- Suchfeld zum Filtern von Mitarbeitern nach Name
- Echtzeit-Filterung

### Mitarbeiterkarten

**Kartenlayout:**
- Mitarbeiterfoto (falls verfügbar)
- Vollständiger Name
- Rang
- Urlaubszeitraum (Von - Bis Daten)
- Gruppiert nach Rang

**Anzeigeformat:**
- Karten organisiert in Ranggruppen
- Rangüberschrift zeigt Rangname
- Mitarbeiter innerhalb jedes Rangs sortiert

## Verwendung der Urlaubsübersicht

### Aktuell abwesende Mitarbeiter anzeigen

1. Klicken Sie auf Filter-Schaltfläche **Aktuell abwesend**
2. System zeigt Mitarbeiter, die heute im Urlaub sind
3. Karten zeigen Urlaubsstart- und Enddaten
4. Gruppiert nach Rang

### Bald zurückkehrende Mitarbeiter anzeigen

**Rückkehr in 7 Tagen:**
1. Klicken Sie auf Schaltfläche **Rückkehr in 7 Tagen**
2. Zeigt Mitarbeiter, die innerhalb der nächsten 7 Tage aus dem Urlaub zurückkehren
3. Nützlich für Personalplanung

**Rückkehr in 30 Tagen:**
1. Klicken Sie auf Schaltfläche **Rückkehr in 30 Tagen**
2. Zeigt Mitarbeiter, die innerhalb der nächsten 30 Tage aus dem Urlaub zurückkehren
3. Breitere Planungsansicht

### Bevorstehende Urlaube anzeigen

**Bevorstehend in 7 Tagen:**
1. Klicken Sie auf Schaltfläche **Bevorstehend in 7 Tagen**
2. Zeigt Mitarbeiter, die innerhalb der nächsten 7 Tage in den Urlaub gehen
3. Auf bevorstehende Abwesenheiten vorbereiten

**Bevorstehend in 30 Tagen:**
1. Klicken Sie auf Schaltfläche **Bevorstehend in 30 Tagen**
2. Zeigt Mitarbeiter, die innerhalb der nächsten 30 Tage in den Urlaub gehen
3. Langfristige Personalplanung

### Nach Rang filtern

1. Klicken Sie auf Rangfilter-Dropdown
2. Wählen Sie bestimmten Rang aus
3. Ansicht aktualisiert sich, um nur ausgewählten Rang anzuzeigen
4. Filter löschen, um alle Ränge zu sehen

### Nach Mitarbeiter suchen

1. Geben Sie Mitarbeitername im Suchfeld ein
2. Liste filtert in Echtzeit
3. Funktioniert über alle Filteransichten hinweg
4. Suche löschen, um alle Ergebnisse zu sehen

## Wie Urlaubsdaten funktionieren

**Datenquelle:**
- Urlaubsdaten stammen aus Mitarbeiterdatensätzen
- Verwaltet über Mitarbeiterverwaltungssystem
- Kein separates Urlaubsantragssystem

**Urlaub festlegen:**
- Urlaubsdaten werden in der Mitarbeiterverwaltung festgelegt
- Administratoren oder Personalabteilung legen Urlaubszeiträume fest
- Kein Mitarbeiter-Self-Service für Urlaubsanträge

**Berechnung:**
- System berechnet aktuellen Status basierend auf heutigem Datum
- Vergleicht Urlaubsstart-/Enddaten mit aktuellem Datum
- Aktualisiert sich automatisch täglich

## Einschränkungen

**Was NICHT verfügbar ist:**

### Antrags- & Genehmigungssystem
- ❌ Mitarbeiter-Urlaubsantrag-Einreichung
- ❌ Manager-Genehmigungsworkflow
- ❌ Urlaubssaldo-Verfolgung
- ❌ Urlaubstyp-Auswahl (krank, persönlich, usw.)
- ❌ Grund für Abwesenheit
- ❌ Anhang-Upload (Arztattest, usw.)
- ❌ Mehrstufige Genehmigung
- ❌ Ablehnungsgründe

### Saldo & Ansammlung
- ❌ Urlaubssaldo-Anzeige
- ❌ Angesammelte Tage-Verfolgung
- ❌ Genutzte Tage vs. Verbleibende
- ❌ Übertrag aus Vorjahr
- ❌ Automatische Ansammlungsregeln
- ❌ Anteiliger Urlaub für Neueinstellungen

### Kalenderfunktionen
- ❌ Teamkalenderansicht
- ❌ Abteilungsabdeckungsplanung
- ❌ Konflikterkennung (überlappende Urlaube)
- ❌ Sperrdaten/eingeschränkte Zeiträume
- ❌ Kalenderexport (iCal, Google Calendar)

### Vertretungsverwaltung
- ❌ Vertreter während Abwesenheit zuweisen
- ❌ Übergabenotizen
- ❌ Ansprechperson während Abwesenheit

### Benachrichtigungen
- ❌ E-Mail-Benachrichtigungen für Genehmigungen
- ❌ Erinnerungen für bevorstehenden Urlaub
- ❌ Benachrichtigungen für Teammitglieder
- ❌ Manager-Benachrichtigung über Anträge

### Reporting
- ❌ Saldo-Berichte
- ❌ Nutzungsberichte
- ❌ Ausstehende Genehmigungen-Bericht
- ❌ Abdeckungslücken-Analyse
- ❌ Ansammlungsberichte

### Admin-Konfiguration
- ❌ Urlaubstypen-Konfiguration
- ❌ Ansammlungsrichtlinien-Einstellungen
- ❌ Genehmigungsworkflow-Regeln
- ❌ Sperrdaten-Verwaltung
- ❌ Berechtigungsregeln

**Aktuelle Realität:**
Dies ist eine **schreibgeschützte Übersicht** von Mitarbeiterabwesenheiten basierend auf Urlaubsdaten, die im Mitarbeiterverwaltungssystem festgelegt wurden. Es ist kein vollständiges Urlaubsantrags- und Genehmigungssystem.

## Mitarbeiterurlaub verwalten

**Um Urlaubsdaten festzulegen:**
1. Gehen Sie zur Mitarbeiterverwaltung (`/employee`)
2. Mitarbeiterdatensatz bearbeiten
3. Urlaubsstart- und Enddaten festlegen
4. Mitarbeiterdatensatz speichern
5. Urlaubsübersicht aktualisiert sich automatisch

**Wer kann Urlaub festlegen:**
- Administratoren
- Benutzer mit `WRITE_EMPLOYEE` Berechtigung
- Kann nicht von Mitarbeitern selbst festgelegt werden

## Anwendungsfälle

**Personalkoordinator:**
- Prüfen, wer aktuell abwesend ist
- Abdeckung für bevorstehende Abwesenheiten planen
- Sehen, wann Mitarbeiter zurückkehren

**Manager:**
- Teamurlaubsplan überwachen
- Projekte um Abwesenheiten herum planen
- Ausreichende Personalbesetzung sicherstellen

**Personalabteilung:**
- Überblick über alle Abteilungsabwesenheiten
- Personallücken identifizieren
- Urlaubspläne koordinieren

## Tipps & Best Practices

**Filter effektiv nutzen:**
- Verwenden Sie "Aktuell abwesend" für heutige Personalbesetzung
- Verwenden Sie "Rückkehr in 7 Tagen" für kurzfristige Planung
- Verwenden Sie "Bevorstehend in 30 Tagen" für langfristige Planung
- Kombinieren Sie Rangfilter mit Datumsfiltern für Abteilungsansicht

**Planung:**
- Prüfen Sie "Bevorstehend in 7 Tagen" wöchentlich
- Überprüfen Sie "Bevorstehend in 30 Tagen" monatlich
- Koordinieren Sie mit Mitarbeiterverwaltung für Terminplanung
- Planen Sie Projekte um bekannte Urlaubszeiten herum

## Fehlerbehebung

### Mitarbeiter wird nicht in Liste angezeigt

**Problem:** Mitarbeiter im Urlaub erscheint nicht

**Lösungen:**
1. Prüfen Sie, ob Urlaubsdaten in Mitarbeiterverwaltung festgelegt sind
2. Verifizieren Sie, dass Daten im korrekten Format sind
3. Sicherstellen, dass Mitarbeiterdatensatz aktiv ist
4. Filterauswahl prüfen (korrekter Zeitraum)
5. Versuchen Sie, alle Filter zu löschen und nach Name zu suchen

### Daten scheinen falsch

**Problem:** Urlaubsdaten entsprechen nicht den Erwartungen

**Lösungen:**
1. Mitarbeiterdatensatz in Mitarbeiterverwaltung prüfen
2. Urlaubsstart- und Enddaten verifizieren
3. Sicherstellen, dass Daten nicht geändert wurden
4. Zeitzoneneinstellungen prüfen, falls zutreffend

### Rangfilter funktioniert nicht

**Problem:** Rangfilter zeigt erwartete Mitarbeiter nicht an

**Lösungen:**
1. Verifizieren Sie, dass Mitarbeiter korrekten Rang zugewiesen haben
2. Prüfen Sie, ob Rang in Mitarbeiterdatensätzen korrekt geschrieben ist
3. Versuchen Sie, Filter zu löschen und erneut anzuwenden
4. Seite aktualisieren

## Verwandte Dokumentation

- [Mitarbeiterverwaltung](/guide/employee-management) - Wo Urlaubsdaten tatsächlich festgelegt werden
- [Kalender](/guide/calendar) - Teamkalender und Ereignisse
- [Erste Schritte](/guide/getting-started) - K-Systems Grundlagen

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend der tatsächlichen Implementierung)
