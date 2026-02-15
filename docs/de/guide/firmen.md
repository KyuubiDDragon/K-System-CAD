# Firmenverwaltung

Firmenverwaltungssystem zur Verfolgung von Firmen und Kunden mit Brandschutzinspektions-Monitoring.

## Überblick

Funktionen:
- Grundlegende Firmen-CRUD-Operationen
- Firmentypklassifizierung
- Mutterfirmenbeziehungen
- Brandschutzinspektions-Nachverfolgung
- Suche und Filterung
- Authority-spezifische Datenisolation

**Erforderliche Berechtigung:** `READ_COMPANY` (anzeigen), `WRITE_COMPANY` (erstellen/bearbeiten), `DELETE_COMPANY` (löschen)

## Zugriff auf Firmen

**Route:** `/company`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Firmen**-Symbol
- Menü: Startmenü → Firmen
- Oder direkt zu `/company` navigieren

## Interface-Layout

### Firmenliste-Tabelle

**Spalten:**
- Firmenname
- Straßenadresse
- Stadt
- Postleitzahl
- Telefon
- E-Mail
- Letzte Brandschutzinspektion
- Brandschutzinspektion gültig bis
- Feuerlöscheranzahl
- Aktionen (Bearbeiten, Löschen)

**Werkzeugleiste:**
- Suchfeld (filtert nach Name, Adresse, Stadt, Postleitzahl, Telefon, E-Mail)
- **Neue Firma**-Schaltfläche

**Paginierung:**
- Eingebaute Tabellenpaginierung
- Clientseitige Filterung

## Firmen erstellen

### Neue Firma

1. Klicken Sie auf **Neue Firma**-Schaltfläche
2. Formulardialog öffnet sich
3. Füllen Sie Felder aus:

**Grundinformationen:**
- **Firmenname*** (erforderlich)
- **Firmentyp**: Kunde, Lieferant, Partner, Sonstiges
- **Mutterfirma**: Aus vorhandenen Firmen auswählen (erstellt Hierarchie)

**Kontaktinformationen:**
- Straßenadresse
- Postleitzahl
- Stadt
- Land
- Telefon
- E-Mail
- Website

**Geschäftsdetails:**
- Steuernummer
- Firmeneintragungsnummer
- **Vertrag verfügbar** (Checkbox)

**Brandschutzinspektion:**
- Letzte Brandschutzinspektion (Datum)
- Brandschutzinspektion gültig bis (Datum)
- Feuerlöscheranzahl (Zahl)

4. Klicken Sie auf **Speichern**
5. Firma erscheint in Liste

## Firmen bearbeiten

### Firma bearbeiten

1. Klicken Sie auf **Bearbeiten**-Symbol in Firmenzeile
2. Formulardialog öffnet sich mit aktuellen Daten
3. Ändern Sie beliebige Felder
4. Klicken Sie auf **Speichern**
5. Änderungen werden sofort angewendet

**Authority-Validierung:**
- Backend stellt sicher, dass Firma zur Authority des Benutzers gehört
- Verhindert mandantenübergreifende Änderungen

## Firmen löschen

### Firma löschen

1. Klicken Sie auf **Löschen**-Symbol in Firmenzeile
2. Bestätigen Sie Löschung im Dialog
3. Firma wird permanent gelöscht

**Warnung:** Löschung ist permanent. Kann fehlschlagen, wenn Firma abhängige Datensätze in anderen Modulen hat.

**Erforderliche Berechtigung:** `DELETE_COMPANY`

## Brandschutzinspektions-Nachverfolgung

Das System enthält spezifische Felder für Brandsicherheits-Compliance:

**Felder:**
1. **Letzte Brandschutzinspektion** - Datum der letzten Inspektion
2. **Brandschutzinspektion gültig bis** - Ablaufdatum
3. **Feuerlöscheranzahl** - Anzahl der Feuerlöscher am Standort

**Anwendungsfall:**
- Compliance mit Brandsicherheitsvorschriften verfolgen
- Inspektionsablaufdaten überwachen
- Gerätezahlen für Audits pflegen

**Anzeige:**
- Alle drei Felder erscheinen als Spalten in Firmentabelle
- Auf einen Blick für Compliance-Überwachung sichtbar

## Firmenhierarchien

**Mutterfirmen-Funktion:**
- Firmen können mit Mutterfirma verknüpft werden
- Erstellt hierarchische Beziehung (Tochtergesellschaften)
- Mutterfirmen-Selektor zeigt durchsuchbares Dropdown
- Nützlich für Verwaltung von Unternehmensstrukturen

**Einschränkungen:**
- Keine visuelle Hierarchie-Baumansicht
- Keine kaskadierenden Operationen
- Keine Berichterstattung über Hierarchien

## Suche und Filter

### Suchfunktionalität

**Suchleiste:**
- Befindet sich in Werkzeugleiste
- Echtzeit-clientseitige Filterung
- Groß-/Kleinschreibung unabhängige Übereinstimmung

**Durchsuchbare Felder:**
- Firmenname
- Straßenadresse
- Stadt
- Postleitzahl
- Telefon
- E-Mail

**Verhalten:**
- Filtert während der Eingabe
- Zeigt übereinstimmende Firmen
- Suche löschen, um alle zu sehen

## Datenmodell

**Firmendatensatzfelder:**
- `id` - Eindeutige Kennung
- `authority_id` - Multi-Tenant-Isolation
- `name` - Firmenname (erforderlich)
- `company_type` - Typ: Kunde, Lieferant, Partner, Sonstiges
- `parent_company_id` - Mutterfirmenreferenz
- `street`, `postal_code`, `city`, `country` - Adresse
- `phone`, `email`, `website` - Kontaktinfo
- `tax_id` - Steueridentifikation
- `registration_number` - Firmeneintragung
- `contract_available` - Vertragsstatus-Flag
- `last_fire_protection_inspection` - Inspektionsdatum
- `fire_protection_inspection_valid_until` - Ablaufdatum
- `extinguisher_count` - Gerätezahl
- `created_at`, `updated_at` - Zeitstempel

## Multi-Tenant-Sicherheit

**Authority-Isolation:**
- Alle Firmen auf `authority_id` beschränkt
- Benutzer sehen nur Firmen in ihrer Organisation
- Organisationsübergreifender Zugriff verhindert
- Mutterfirmen-Dropdown nach Authority gefiltert

## Einschränkungen

**Was NICHT verfügbar ist:**

### Kontaktverwaltung
- ❌ Dedizierte Kontakte/Personen pro Firma
- ❌ Mehrere Kontaktpersonen
- ❌ Kontaktrollen oder Abteilungen
- ❌ Telefon/E-Mail nur auf Firmenebene gespeichert (nicht kontaktspezifisch)

### Dokumentenverwaltung
- ❌ Dokumenten-Uploads oder Anhänge
- ❌ Vertragsspeicherung
- ❌ Zertifikats-Uploads
- ❌ Brandschutzzertifikate
- ❌ Dokumentenversionskontrolle

### Notizen & Historie
- ❌ Notizen- oder Kommentarbereich
- ❌ Interaktionshistorien-Protokollierung
- ❌ Aktivitätszeitstrahl
- ❌ Kommunikationsverfolgung

### Erweiterte Funktionen
- ❌ Erweiterte Firmenbeziehungen (über Mutterfirma hinaus)
- ❌ Tags oder benutzerdefinierte Felder
- ❌ Import/Export-Funktionalität
- ❌ Massenoperationen
- ❌ Erweiterte Filterung (nach Typ, Inspektionsstatus, etc.)
- ❌ Berichterstattung oder Analysen
- ❌ Firmenstatus-Workflow (aktiv/inaktiv/archiviert)
- ❌ Duplikatserkennung
- ❌ Automatische Inspektionsablauf-Warnungen

### Integrationsfunktionen
- ❌ Verknüpfung zu Rechnungen aus anderen Modulen
- ❌ Verknüpfung zu Dokumenten
- ❌ Projektzuordnungen
- ❌ Mitarbeiterzuweisungen zu Firmen

### Sucheinschränkungen
- ❌ Nur clientseitige Suche (skaliert nicht gut)
- ❌ Keine gespeicherten Suchen
- ❌ Keine Sortierung nach Brandschutzdaten
- ❌ Keine serverseitige Paginierung

**Aktuelle Realität:**
Dies ist ein **einfaches Firmenverzeichnis** mit Brandschutzinspektions-Nachverfolgung. Es ist kein umfassendes CRM- oder Firmenverwaltungssystem.

## Anwendungsfälle

**Firmenverzeichnis:**
- Liste von Kunden, Lieferanten, Partnern pflegen
- Grundlegende Kontaktinformationen speichern
- Firmentypen verfolgen

**Brandsicherheits-Compliance:**
- Brandschutzinspektionen überwachen
- Inspektionsablaufdaten verfolgen
- Feuerlöscherzahlen pflegen
- Regulatorische Compliance sicherstellen

**Einfache Hierarchie:**
- Mutter-/Tochtergesellschaftsbeziehungen verfolgen
- Grundlegende Unternehmensstruktur

## Tipps & Best Practices

**Dateneingabe:**
1. Immer Firmennamen ausfüllen (erforderlich)
2. Firmentyp zur Organisation festlegen
3. Mutterfirma für Tochtergesellschaften verwenden
4. Brandschutzdaten aktuell halten

**Brandschutz-Compliance:**
1. "Gültig bis"-Datum beim Erfassen von Inspektionen festlegen
2. Feuerlöscheranzahl während Inspektionen aktualisieren
3. Regelmäßig Firmen mit bevorstehenden Ablaufdaten überprüfen
4. **Hinweis:** Keine automatischen Warnungen - erfordert manuelle Überprüfung

**Suche:**
1. Verwenden Sie spezifische Begriffe für schnellere Ergebnisse
2. Suche funktioniert über mehrere Felder
3. Suche löschen, um vollständige Liste anzuzeigen

**Hierarchieverwaltung:**
1. Mutterfirma beim Erstellen von Tochtergesellschaften festlegen
2. Mutterfirmen-Dropdown zeigt nur Firmen in gleicher Authority
3. Keine visuelle Baumansicht - manuell verfolgen

## Fehlerbehebung

### Kann keine Firma erstellen

**Problem:** Neue Firma-Schaltfläche funktioniert nicht

**Lösungen:**
1. Überprüfen Sie `WRITE_COMPANY`-Berechtigung
2. Prüfen Sie, ob Firmenname ausgefüllt ist (erforderlich)
3. Validieren Sie E-Mail-Format, falls angegeben
4. Prüfen Sie Browser-Konsole auf Fehler

### Kann Firma nicht bearbeiten

**Problem:** Bearbeitung wird nicht gespeichert

**Lösungen:**
1. Prüfen Sie `WRITE_COMPANY`-Berechtigung
2. Überprüfen Sie, ob Firmenname nicht leer ist
3. Prüfen Sie Internetverbindung
4. Stellen Sie sicher, dass Firma zu Ihrer Authority gehört

### Kann Firma nicht löschen

**Problem:** Löschen schlägt fehl

**Lösungen:**
1. Prüfen Sie `DELETE_COMPANY`-Berechtigung
2. Firma hat möglicherweise abhängige Datensätze in anderen Modulen
3. Überprüfen Sie, ob Firma zu Ihrer Authority gehört
4. Prüfen Sie Fehlermeldung

### Suche funktioniert nicht

**Problem:** Keine Ergebnisse

**Lösungen:**
1. Prüfen Sie, ob Suchbegriff Name, Straße, Stadt, Postleitzahl, Telefon oder E-Mail entspricht
2. Suche ist Groß-/Kleinschreibung unabhängig, muss aber Substring entsprechen
3. Überprüfen Sie, ob Firmen existieren
4. Suche löschen und erneut versuchen

### Brandschutzdaten werden nicht gespeichert

**Problem:** Daten bleiben nicht bestehen

**Lösungen:**
1. Prüfen Sie, ob Daten gültiges Format haben
2. Überprüfen Sie Browser-Konsole auf Fehler
3. Prüfen Sie, ob Backend Datumsfelder akzeptiert
4. Stellen Sie ordnungsgemäße Datumsauswahl sicher

## Verwandte Dokumentation

- [Erste Schritte](/guide/getting-started) - K-Systems-Grundlagen
- [Personenakten](/guide/person-files) - Personenkontakte
- [Rechnungen](/guide/invoices) - Rechnungsverwaltung

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert entsprechend tatsächlicher Implementierung)
