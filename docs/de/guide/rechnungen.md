# Rechnungsverwaltung

Grundlegendes Rechnungsverfolgungssystem zur Verwaltung von eingehenden und ausgehenden Rechnungen mit Kundeninformationen und Positionselementen.

## Überblick

Funktionen:
- Rechnungserstellung mit Positionselementen
- Kundenverknüpfung (Firmen oder Personen)
- Status-Nachverfolgung (geliefert, gesendet, bezahlt)
- Dateianhänge
- Suchfunktionalität
- Authority-spezifische Datenisolation

**Erforderliche Berechtigung:** `READ_INVOICE` (anzeigen), `WRITE_INVOICE` (erstellen/bearbeiten), `DELETE_INVOICE` (löschen)

## Zugriff auf Rechnungen

**Route:** `/invoice`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Rechnungen**-Symbol
- Menü: Startmenü → Finanzen → Rechnungen
- Oder direkt zu `/invoice` navigieren

## Interface-Layout

### Rechnungstabelle

**Spalten:**
- **Typ** - Grüner "Einnahme"-Chip (outgoing=true) oder Roter "Ausgabe"-Chip (outgoing=false)
- **Betreff** - Rechnungstitel
- **Kunde** - Kundenname
- **Geliefert** - Lieferdatum (Chip) oder "-"
- **Gesendet** - Sendedatum (blauer Chip) oder "-"
- **Bezahlt** - Zahlungsdatum (grüner Chip) oder "-"
- **Aktionen** - Anzeigen, Bearbeiten, Löschen-Schaltflächen

**Werkzeugleiste:**
- Suchfeld (filtert nach Kundenname oder Rechnungstitel)
- **Neue Rechnung**-Schaltfläche

**Paginierung:**
- 25 Einträge pro Seite
- Sortiert nach ID absteigend (neueste zuerst)

## Rechnungen erstellen

### Neue Rechnung

1. Klicken Sie auf **Neue Rechnung**-Schaltfläche
2. Formulardialog öffnet sich mit drei Abschnitten

**Abschnitt 1: Kundenauswahl**
- **Firmen-Autocomplete** - Aus vorhandenen Firmen auswählen
- **Personen-Autocomplete** - Aus vorhandenen Personen auswählen
- **Daten laden-Schaltfläche** - Füllt automatisch Kundenkontaktinformationen aus
- Rechnung wird mit ausgewählter Firma ODER Person verknüpft (gegenseitig ausschließend)

**Abschnitt 2: Status-Umschalter**
- **Geliefert** - Checkbox (markiert Rechnung als geliefert)
- **Gesendet** - Checkbox (markiert Rechnung als gesendet)
- **Bezahlt** - Checkbox (markiert Rechnung als bezahlt)

**Abschnitt 3: Transaktionstyp**
- **Dropdown** - Wählen Sie "Einnahme" (outgoing=1) oder "Ausgabe" (outgoing=0)

**Abschnitt 4: Grundinformationen**
- **Titel*** (erforderlich) - Rechnungsbetreff
- **Kunde** - Kundenname (automatisch ausgefüllt oder manuell)
- **Telefonnummer** - Kundentelefon
- **E-Mail** - Kunden-E-Mail
- **Konto** - Bankkontoinformationen
- **Beschreibung** - Mehrzeiliger Textbeschreibung

**Abschnitt 5: Positionselemente**
- **Artikelselektor** - Dropdown vordefinierter Artikel
- **Beschreibung** - Artikelbeschreibung (automatisch ausgefüllt durch Auswahl)
- **Preis** - Stückpreis
- **Menge** - Menge (Standard: 1)
- **Artikel hinzufügen** - Blaue "+"-Schaltfläche zum Hinzufügen weiterer Artikel
- **Artikel entfernen** - Rote "X"-Schaltfläche bei jedem Artikel

**Abschnitt 6: Zusätzliche Optionen**
- **Rabatt** - Numerischer Rabattwert
- **Anhänge** - Mehrfacher Datei-Upload

3. Füllen Sie erforderliche Felder aus (Minimum: Titel)
4. Fügen Sie nach Bedarf Positionselemente hinzu
5. Klicken Sie auf **Speichern**
6. Rechnung erscheint in Tabelle

## Rechnungen bearbeiten

### Rechnung bearbeiten

1. Klicken Sie auf **Bearbeiten**-Symbol (Bleistift) in Rechnungszeile
2. Bearbeitungsdialog öffnet sich mit allen aktuellen Daten
3. Ändern Sie beliebige Felder über alle Abschnitte
4. Positionselemente hinzufügen/entfernen
5. Neue Anhänge hinzufügen oder vorhandene entfernen
6. Klicken Sie auf **Speichern**
7. Änderungen werden sofort angewendet

## Rechnungen anzeigen

### Rechnung anzeigen

1. Klicken Sie auf **Anzeigen**-Symbol (Auge) in Rechnungszeile
2. Nur-Ansicht-Dialog zeigt alle Informationen
3. Zeigt verknüpfte Firma/Person falls zutreffend
4. Zeigt alle Positionselemente
5. Zeigt alle Anhänge
6. Schließen, wenn fertig

## Rechnungen löschen

### Rechnung löschen

1. Klicken Sie auf **Löschen**-Symbol (Papierkorb) in Rechnungszeile
2. Bestätigen Sie Löschung im Dialog
3. Warnung über permanente Löschung angezeigt
4. Rechnung wird aus Datenbank entfernt

**Warnung:** Löschung ist permanent.

**Erforderliche Berechtigung:** `DELETE_INVOICE`

## Rechnungsstatus

**Status-Indikatoren:**
- **Geliefert** - Rechnung wurde an Kunden geliefert
- **Gesendet** - Rechnung wurde gesendet
- **Bezahlt** - Rechnung wurde bezahlt

**Status-Anzeige:**
- Datums-Chips zeigen, wann Status gesetzt wurde
- Daten formatiert als TT.MM.JJJJ
- Leerer Status zeigt "-"

**Hinweis:** Status verwendet Boolean-Umschalter, keine direkte Datumsbearbeitung im Formular.

## Kundenverknüpfung

**Firmenverknüpfung:**
1. Wählen Sie Firma aus Autocomplete
2. Klicken Sie auf "Daten laden"-Schaltfläche
3. Kundenname, Telefon, E-Mail werden automatisch aus Firmendatensatz ausgefüllt
4. Rechnung wird mit Firma verknüpft (linked_company-Feld gesetzt)

**Personenverknüpfung:**
1. Wählen Sie Person aus Autocomplete
2. Klicken Sie auf "Daten laden"-Schaltfläche
3. Kundeninformationen werden automatisch aus Personendatensatz ausgefüllt
4. Rechnung wird mit Person verknüpft (linked_person-Feld gesetzt)

**Gegenseitige Ausschließlichkeit:**
- Rechnung kann mit Firma ODER Person verknüpft werden, nicht beides
- Verknüpfung hilft, Rechnungen pro Kunde zu verfolgen

## Positionselemente

**Artikelverwaltung:**
1. Klicken Sie auf "Artikel hinzufügen" zum Hinzufügen neues Positionselement
2. Aus Dropdown vordefinierter Rechnungsartikel auswählen (optional)
3. Auswahl vordefinierter Artikel füllt automatisch Beschreibung und Preis aus
4. Oder manuell Beschreibung und Preis eingeben
5. Menge festlegen (Standard: 1)
6. Klicken Sie auf "X" zum Entfernen des Artikels

**Artikelfelder:**
- **Artikelselektor** - Optionales Dropdown für vordefinierte Artikel
- **Beschreibung** - Artikelbeschreibung
- **Preis** - Stückpreis
- **Menge** - Anzahl der Einheiten

**Mehrere Artikel:**
- Fügen Sie so viele Positionselemente wie benötigt hinzu
- Jeder Artikel auf separater Karte
- Unerwünschte Artikel vor dem Speichern entfernen

## Dateianhänge

**Anhänge hochladen:**
- Mehrfacher Datei-Upload-Unterstützung
- Erlaubte Typen: jpg, jpeg, png, pdf, docx, doc, txt
- Dateien in Authority-spezifischem Ordner gespeichert

**Anhänge verwalten:**
- Neue Anhänge beim Bearbeiten hinzufügen
- Vorhandene Anhänge entfernen
- Anhänge mit Rechnungsdaten übermittelt

## Suche und Filter

### Suchfunktionalität

**Suchleiste:**
- Befindet sich in Werkzeugleiste
- Filtert nach Kundenname oder Rechnungstitel
- Echtzeit-Filterung
- Groß-/Kleinschreibung unabhängige Übereinstimmung

**Verhalten:**
- Tippen zum Filtern der Rechnungsliste
- Zeigt übereinstimmende Rechnungen
- Suche löschen, um alle zu sehen

## Datenmodell

**Rechnungsdatensatzfelder:**
- `id` - Eindeutige Kennung
- `title` - Rechnungsbetreff (erforderlich)
- `customer` - Kundenname
- `phone_number` - Telefonnummer
- `email` - E-Mail-Adresse
- `account` - Bankkontoinformationen
- `description` - Zusätzliche Beschreibung
- `is_sent` - Gesendet-Status (Boolean)
- `is_paid` - Bezahlt-Status (Boolean)
- `is_delivered` - Geliefert-Status (Boolean)
- `is_sent_date` - Datum des Versands
- `is_paid_date` - Datum der Bezahlung
- `is_delivered_date` - Datum der Lieferung
- `outgoing` - Einnahme (true) oder Ausgabe (false)
- `discount` - Rabattwert
- `linked_person` - Personen-ID falls verknüpft
- `linked_company` - Firmen-ID falls verknüpft
- `items` - Array von Positionselementen
- `authority_id` - Organisations-ID

**Positionselementfelder:**
- `id` - Artikel-ID
- `invoice_id` - Übergeordnete Rechnung
- `item_name` - Vordefinierter Artikelname
- `description` - Artikelbeschreibung
- `price` - Stückpreis
- `quantity` - Menge

## Multi-Tenant-Sicherheit

**Authority-Isolation:**
- Alle Rechnungen auf `authority_id` beschränkt
- Benutzer sehen nur Rechnungen aus ihrer Organisation
- Datei-Uploads in Authority-spezifischen Verzeichnissen
- Firmen-/Personen-Dropdowns nach Authority gefiltert

## Berechtigungen

**Berechtigungsebenen:**
- `READ_INVOICE` - Rechnungsliste und Details anzeigen
- `WRITE_INVOICE` - Rechnungen erstellen und bearbeiten
- `DELETE_INVOICE` - Rechnungen löschen
- `ALL` - Vollzugriff

## Einschränkungen

**Was NICHT verfügbar ist:**

### Finanzfunktionen
- ❌ Steuer-/MwSt.-Berechnung
- ❌ Steuerkonfiguration
- ❌ Automatische Rechnungsnummerierung
- ❌ Zahlungsverfolgung über bezahlt/unbezahlt hinaus
- ❌ Teilzahlungsunterstützung
- ❌ Zahlungsmethoden-Verfolgung
- ❌ Währungsauswahl
- ❌ Multiwährungs-Unterstützung

### Dokumentenfunktionen
- ❌ PDF-Generierung
- ❌ PDF-Export
- ❌ Anpassbare Rechnungsvorlagen
- ❌ E-Mail-Versand aus System
- ❌ Rechnungsvorschau vor dem Speichern

### Erweiterte Funktionen
- ❌ Wiederkehrende Rechnungen
- ❌ Abonnementrechnungen
- ❌ Gutschriften
- ❌ Rückerstattungen
- ❌ Rechnungsversionierung
- ❌ Revisionshistorie
- ❌ Genehmigungs-Workflows
- ❌ Zahlungserinnerungen bei Verspätung
- ❌ Berichterstattung oder Analysen
- ❌ Fälligkeitsdaten-Verfolgung
- ❌ Buchhaltungssystem-Integration

### UI-Einschränkungen
- ❌ Daten nicht direkt im Formular bearbeitbar (nur Boolean umschalten)
- ❌ Massenoperationen
- ❌ Rechnungsduplizierung/Klonen
- ❌ Entwurfsspeicherung
- ❌ Positionselemente können nicht umgeordnet werden
- ❌ Keine Rechnungssummen-Anzeige in Tabelle
- ❌ Keine Validierung für Gesamtberechnungen
- ❌ Anhänge können vor Upload nicht vorgeschaut werden
- ❌ Suche beschränkt auf Kunde und Titel (kein Datumsbereich, Betrag, Status)

### Status-Funktionen
- ❌ Rechnungsstatus-Workflow (nur Boolean-Umschalter)
- ❌ Überfälligkeitserkennung
- ❌ Zahlungsbedingungen
- ❌ Automatische Status-Aktualisierungen

**Aktuelle Realität:**
Dies ist ein **einfacher Rechnungsverfolger** mit Positionselementen und Dateianhängen. Es ist kein umfassendes Rechnungs- oder Buchhaltungssystem.

## Anwendungsfälle

**Rechnungsverfolgung:**
- Ausgehende Rechnungen erfassen (Einnahmen)
- Eingehende Rechnungen erfassen (Ausgaben)
- Rechnungsstatus verfolgen

**Kundenverwaltung:**
- Rechnungen mit Firmen verknüpfen
- Rechnungen mit Personen verknüpfen
- Rechnungen pro Kunde verfolgen

**Positionselement-Details:**
- Rechnungsdetails aufschlüsseln
- Mengen und Preise verfolgen
- Vordefinierte Artikel für Konsistenz verwenden

**Dokumentenspeicherung:**
- Rechnungs-PDFs anhängen
- Unterstützende Dokumente speichern
- Rechnungsbezogene Dateien zusammenhalten

## Tipps & Best Practices

**Rechnungen erstellen:**
1. Immer Titel ausfüllen (erforderlich)
2. Mit Firma oder Person für bessere Verfolgung verknüpfen
3. "Daten laden" verwenden zum automatischen Ausfüllen von Kundeninformationen
4. Transaktionstyp (Einnahme/Ausgabe) korrekt festlegen

**Positionselemente:**
1. Vordefinierte Artikel verwenden, wenn verfügbar
2. In Artikelbeschreibungen spezifisch sein
3. Mengen und Preise überprüfen
4. Alle Artikel vor dem Speichern hinzufügen

**Status-Verwaltung:**
1. Geliefert-Status aktualisieren, wenn Rechnung versendet
2. Als gesendet markieren, wenn tatsächlich gesendet
3. Als bezahlt markieren, wenn Zahlung eingegangen
4. Datums-Chips verwenden, um zu überprüfen, wann Status gesetzt wurde

**Anhänge:**
1. Rechnungs-PDF anhängen, falls verfügbar
2. Unterstützende Dokumente einbeziehen
3. Dateigrößen angemessen halten
4. Nur unterstützte Dateitypen verwenden

## Fehlerbehebung

### Kann keine Rechnung erstellen

**Problem:** Neue Rechnung-Schaltfläche funktioniert nicht

**Lösungen:**
1. Überprüfen Sie `WRITE_INVOICE`-Berechtigung
2. Prüfen Sie, ob Titelfeld ausgefüllt ist
3. Seite aktualisieren
4. Prüfen Sie Browser-Konsole auf Fehler

### Kundendaten werden nicht geladen

**Problem:** Daten laden-Schaltfläche füllt keine Kundeninformationen aus

**Lösungen:**
1. Überprüfen Sie, ob zuerst Firma oder Person ausgewählt ist
2. Prüfen Sie, ob Firma/Person Kontaktinformationen hat
3. Versuchen Sie erneutes Auswählen
4. Überprüfen Sie, ob Firma/Person im System existiert

### Positionselement wird nicht gespeichert

**Problem:** Hinzugefügte Artikel verschwinden

**Lösungen:**
1. Überprüfen Sie, ob Sie Beschreibung und Preis ausgefüllt haben
2. Prüfen Sie, ob Sie auf Speichern im Rechnungsdialog geklickt haben
3. Stellen Sie sicher, dass keine Validierungsfehler vorliegen
4. Versuchen Sie, Artikel erneut hinzuzufügen

### Status wird nicht aktualisiert

**Problem:** Status-Umschalter werden nicht gespeichert

**Lösungen:**
1. Prüfen Sie, ob Sie `WRITE_INVOICE`-Berechtigung haben
2. Überprüfen Sie, ob Sie auf Speichern-Schaltfläche geklickt haben
3. Prüfen Sie Internetverbindung
4. Suchen Sie nach Fehlermeldungen

### Anhänge werden nicht hochgeladen

**Problem:** Dateien werden nicht angehängt

**Lösungen:**
1. Prüfen Sie, ob Dateityp unterstützt wird (jpg, jpeg, png, pdf, docx, doc, txt)
2. Überprüfen Sie, ob Dateigröße angemessen ist
3. Prüfen Sie Internetverbindung
4. Versuchen Sie andere Datei
5. Prüfen Sie Browser-Konsole auf Fehler

### Suche funktioniert nicht

**Problem:** Suche filtert nicht

**Lösungen:**
1. Prüfen Sie Rechtschreibung von Kunde oder Titel
2. Versuchen Sie Teilübereinstimmung
3. Suche löschen und erneut versuchen
4. Seite aktualisieren

## Verwandte Dokumentation

- [Erste Schritte](/guide/getting-started) - K-Systems-Grundlagen
- [Firmen](/guide/companies) - Firmenverwaltung
- [Personenakten](/guide/person-files) - Personenverwaltung

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend tatsächlicher Implementierung)
