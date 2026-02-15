# Behördenverwaltung (Super Admin)

Umfassende Anleitung für Super-Administratoren zur Verwaltung von Behörden (Organisationen) in der K-Systems Multi-Tenant-Umgebung.

## Überblick

Behördenverwaltung ist die Super-Admin-Oberfläche zur Verwaltung mehrerer Organisationen innerhalb von K-Systems. Jede Behörde repräsentiert eine separate Organisation mit isolierten Daten, Benutzern und Einstellungen.

**Hauptverantwortlichkeiten:**
- Behörden erstellen und konfigurieren
- Behördeneinstellungen verwalten
- Behördennutzung überwachen
- Datenbanken-Isolierung handhaben
- Lizenzverwaltung
- Systemweite Administration
- Behördenübergreifende Operationen
- Abrechnung und Abonnements
- Behördenmigration
- Technischer Support

## Anforderungen

**Erforderliche Berechtigung:** `SUPER_ADMIN` (höchste Zugriffsstufe)

**Zugriffsebene:** Nur Super Administrator

**Wichtig:** Dies ist die höchste Berechtigungsstufe in K-Systems. Nur designierte Systemadministratoren sollten diesen Zugriff haben.

## Behördenverwaltung öffnen

**Desktop:** Start → Super Admin → Behörden
**Menü:** Super Admin → Behördenverwaltung
**Route:** `/admin/authorities`

**Hinweis:** Dieser Bereich ist nur für Benutzer mit SUPER_ADMIN-Berechtigung sichtbar.

## Behörden verstehen

### Was ist eine Behörde?

**Behörde (Organisation):**
Eine Behörde repräsentiert eine vollständig separate Organisation innerhalb des K-Systems Multi-Tenant-Systems. Jede Behörde hat:

**Isolierte Daten:**
- Separate Datenbanktabellen
- Isolierter Dateispeicher
- Unabhängige Benutzer
- Private Dokumente
- Separate Berichte
- Individuelle Einstellungen

**Unabhängige Konfiguration:**
- Benutzerdefiniertes Branding
- Eigene Einstellungen
- Einzigartige Berechtigungen
- Benutzerdefinierte Module
- API-Integrationen
- E-Mail-Konfiguration

**Separate Abrechnung:**
- Individuelles Abonnement
- Nutzungsverfolgung
- Lizenzlimits
- Feature-Zugriff
- Speicherkontingente

### Multi-Tenant-Architektur

**Datenisolierung:**
- Jede Datenbanktabelle enthält `authority_id`
- Abfragen automatisch nach Behörde gefiltert
- Kein behördenübergreifender Datenzugriff (außer Super Admin)
- Dateispeicher nach Behördenordner getrennt
- Benutzer gehören zu einzelner Behörde

**Gemeinsame Ressourcen:**
- Anwendungscode (einzelne Instanz)
- Systeminfrastruktur
- Datenbankserver (separate Schemas/Tabellen)
- Kern-Systemfunktionen
- Updates und Patches

**Sicherheit:**
- Vollständige Datentrennung
- Keine behördenübergreifenden Abfragen
- Verschlüsselt im Ruhezustand
- Audit-Trails pro Behörde
- Unabhängige Authentifizierung

## Behördenliste

### Alle Behörden anzeigen

**Behörden-Dashboard:**
1. Behördenverwaltung öffnen
2. Alle Behörden sehen:

**Behördeninformationen:**
- **Name**: Organisationsname
- **Domain**: Benutzerdefinierte Domain (falls konfiguriert)
- **Status**: Aktiv, Suspendiert, Test
- **Erstellt**: Erstellungsdatum
- **Benutzer**: Benutzerzahl / Limit
- **Speicher**: Verwendet / Zugewiesen
- **Lizenz**: Lizenztyp
- **Abonnement**: Abrechnungsstatus
- **Zuletzt aktiv**: Letzter Benutzer-Login

**Status-Indikatoren:**
- 🟢 **Aktiv**: Normaler Betrieb
- 🟡 **Test**: Testzeitraum
- 🔵 **Suspendiert**: Zahlungsproblem oder manuelle Suspendierung
- 🔴 **Deaktiviert**: Vom Admin deaktiviert
- ⚫ **Archiviert**: Alte/ungenutzte Behörde

**Schnellstatistiken:**
- Gesamtbehörden
- Aktive Behörden
- Gesamtbenutzer (alle Behörden)
- Verwendeter Speicher (alle Behörden)
- Umsatz (falls Abrechnung aktiviert)

### Suchen und Filtern

**Behörden finden:**
- **Suche**: Name, Domain, Kontakt
- **Nach Status filtern**: Aktiv, Test, Suspendiert, Deaktiviert
- **Nach Lizenz filtern**: Kostenlos, Basic, Professional, Enterprise, Benutzerdefiniert
- **Sortieren nach**: Name, Erstellungsdatum, Benutzerzahl, Verwendeter Speicher, Zuletzt aktiv

## Neue Behörde erstellen

### Schritt 1: Grundinformationen

**Behörde erstellen:**
1. Klicken Sie auf **+ Behörde erstellen**
2. Behördendetails ausfüllen:

**Organisationsinformationen:**
- **Behördenname**: Organisationsname (erforderlich)
- **Anzeigename**: Kurzname
- **Domain**: Benutzerdefinierte Subdomain (Format: `subdomain.k-systems.com`)
- **Kontaktinformationen**: Primärkontakt, E-Mail, Telefon, Adresse
- **Branche/Typ**: Feuerwehr, EMS, Strafverfolgung, Regierung, Unternehmen

### Schritt 2: Administrator-Konto

**Initialen Admin erstellen:**
- **Benutzername**: Admin-Benutzername
- **E-Mail**: Admin-E-Mail
- **Passwort**: Initiales Passwort (oder sicheres Passwort generieren)
- **Name**: Admin-Vor- und Nachname

**Admin-Berechtigungen:**
- Automatisch ADMIN-Rolle zugewiesen
- Volle Behördenverwaltung
- Benutzerverwaltung
- Einstellungskonfiguration
- Kein behördenübergreifender Zugriff

### Schritt 3: Lizenz & Funktionen

**Lizenz auswählen:**
- **Lizenztyp**: Kostenloser Test (30 Tage), Basic, Professional, Enterprise, Benutzerdefiniert
- **Abrechnungszyklus**: Monatlich, Jährlich (Rabatt), Benutzerdefiniert
- **Test-Optionen**: Testdauer: 30 Tage, Auto-Umwandlung zu bezahlt

**Feature-Zugriff:**
Basierend auf Lizenz aktivieren/deaktivieren:
- Desktop-Modus
- Erweiterte Berichte
- Leitstellenoperationen
- Crew-Management
- Brandschutz
- Schulungsmodul
- Erweiterte Integrationen
- API-Zugriff
- Mobile App
- Benutzerdefiniertes Branding
- SSO/SAML
- Prioritäts-Support

**Ressourcenlimits:**
- **Max Benutzer**: Zahl (oder unbegrenzt)
- **Speicherkontingent**: GB (10 GB, 50 GB, 100 GB, 500 GB, unbegrenzt)
- **API-Aufrufe**: Pro Monat
- **Datei-Upload-Limit**: MB pro Datei
- **E-Mail-Versandlimit**: Pro Tag

### Schritt 4: Einstellungen

**Initialkonfiguration:**

**Regionale Einstellungen:**
- **Zeitzone**: Behördenzeitzone
- **Sprache**: Standardsprache
- **Währung**: Primärwährung
- **Datumsformat**: MM/TT/JJJJ oder TT/MM/JJJJ
- **Zeitformat**: 12-Stunden oder 24-Stunden

**Systemeinstellungen:**
- **E-Mail-Konfiguration**: System-SMTP verwenden oder eigenes SMTP konfigurieren
- **Sitzungstimeout**: Minuten
- **Passwortrichtlinie**: Systemstandard oder benutzerdefiniert
- **2FA-Anforderung**: Optional, erforderlich, nur Admin

### Schritt 5: Datenbank-Setup

**Datenbankkonfiguration:**
- **Datenbanken-Isolierung**: Automatisch
- **Tabellen erstellen**: System erstellt Behördentabellen
- **Daten initialisieren**: Initiale Daten säen (Standardrollen, Berechtigungssätze)
- **Datenbank-Präfix**: authority_{id}_

**Speicher-Setup:**
- **Dateispeicher**: Behördenordner erstellen
- **Speicherpfad**: /storage/authority_{id}/
- **Berechtigungen**: Ordnerberechtigungen festlegen
- **Backup einschließen**: Zu Backup-Zeitplan hinzufügen

### Schritt 6: Überprüfen und erstellen

**Konfiguration überprüfen:**
- Behördendetails
- Admin-Konto
- Lizenz und Limits
- Aktivierte Funktionen
- Einstellungszusammenfassung

**Behörde erstellen:**
1. Klicken Sie auf **Behörde erstellen**
2. System verarbeitet: Behördendatensatz erstellen, Datenbanktabellen einrichten, Dateispeicher erstellen
3. Behörde erstellt

**Nach Erstellung:**
- Willkommens-E-Mail an Admin gesendet
- Login-Anmeldedaten bereitgestellt
- Setup-Assistent für Admin verfügbar
- Behörde sofort aktiv

## Behörden verwalten

### Behörden-Dashboard

**Behördendetails anzeigen:**
1. Behördennamen klicken
2. Behörden-Dashboard sehen:

**Überblick:**
- Behördeninformationen
- Aktueller Status
- Nutzungsstatistiken
- Aktuelle Aktivität
- Schnellaktionen

**Statistiken:**
- **Benutzer**: Gesamtbenutzer, aktive Benutzer
- **Speicher**: Verwendeter Speicher, Kontingent
- **Aktivität**: Logins, Aktionen
- **Funktionen**: Aktivierte Module
- **Lizenz**: Typ, Ablauf
- **Abrechnung**: Status, Betrag

**Aktuelle Aktivität:**
- Aktuelle Logins
- Aktuelle Aktionen
- Systemereignisse
- Fehler/Probleme
- Leistungsmetriken

### Behördeneinstellungen bearbeiten

**Behörde ändern:**
1. Klicken Sie auf **Behörde bearbeiten**
2. Einstellungen aktualisieren:

**Bearbeitbare Felder:**
- Organisationsname und Info
- Kontaktinformationen
- Domain/Subdomain
- Lizenztyp
- Feature-Zugriff
- Ressourcenlimits
- Regionale Einstellungen
- Status

**Nicht bearbeitbar:**
- Behörden-ID (systemgeneriert)
- Erstellungsdatum
- Datenbanktabellen (automatisch)

**Änderungen speichern:**
- Klicken Sie auf **Speichern**
- Änderungen gelten sofort
- Behörden-Admin benachrichtigen (optional)
- Konfigurationsänderung protokollieren

### Behördenstatusverwaltung

**Status ändern:**

**Behörde aktivieren:**
- Alle Funktionen aktivieren
- Benutzer-Logins erlauben
- Betrieb fortsetzen
- Reaktivierungs-E-Mail senden

**Behörde suspendieren:**
1. Klicken Sie auf **Suspendieren**
2. Grund eingeben: Zahlungsrückstand, Richtlinienverstoß, Sicherheitsproblem
3. Suspendierung bestätigen
4. Auswirkungen: Alle Benutzer abgemeldet, Login deaktiviert, Daten bewahrt

**Behörde deaktivieren:**
- Stärker als suspendieren
- Kein Zugriff überhaupt
- Abrechnung gestoppt
- Daten aufbewahrt
- Umkehrbar

**Behörde archivieren:**
- Langfristig inaktiv
- Keine Abrechnung
- Daten im Archiv bewahrt
- Kann später wiederhergestellt werden
- Gibt Ressourcen frei

**Behörde löschen (Permanent):**
1. Klicken Sie auf **Löschen**
2. Bestätigungstext eingeben
3. WARNUNG: Kann nicht rückgängig gemacht werden
4. Datenhandhabung wählen: Alle Daten löschen (empfohlen), Daten zuerst exportieren
5. Finale Bestätigung
6. Behörde gelöscht: Alle Daten entfernt, Benutzer entfernt, Dateien gelöscht, Datenbank bereinigt, Kann nicht wiederhergestellt werden

**Archivierte wiederherstellen:**
1. Archivierte Behörde finden
2. Klicken Sie auf **Wiederherstellen**
3. Lizenz reaktivieren
4. Behörde betriebsbereit
5. Admin benachrichtigen

## Lizenzverwaltung

### Lizenzdetails anzeigen

**Lizenzinformationen:**
- **Lizenztyp**: Aktueller Plan
- **Status**: Aktiv, Test, Abgelaufen
- **Startdatum**: Wann gestartet
- **Ablauf**: Wann abläuft (falls zutreffend)
- **Erneuerungsdatum**: Nächste Erneuerung
- **Auto-Erneuern**: Ja/Nein

**Lizenzlimits:**
- **Benutzer**: Aktuell / Maximum
- **Speicher**: Verwendet / Zugewiesen
- **Funktionen**: Aktivierte Liste
- **API-Aufrufe**: Verwendet / Limit
- **E-Mail-Versand**: Verwendet / Limit

**Nutzungsverfolgung:**
- Echtzeit-Nutzung
- Historische Nutzung
- Trends und Muster
- Überschreitungswarnungen
- Upgrade-Empfehlungen

### Lizenz ändern

**Lizenz upgraden/downgraden:**
1. Klicken Sie auf **Lizenz ändern**
2. Neuen Lizenztyp auswählen
3. Änderungen überprüfen: Feature-Änderungen, Limit-Änderungen, Preisunterschied
4. Übergang wählen: Sofort, Ende Abrechnungszyklus, Spezifisches Datum
5. Änderung bestätigen

**Upgrade-Effekte:**
- Neue Funktionen sofort aktiviert
- Limits erhöht
- Anteilige Abrechnung
- Kein Datenverlust

**Downgrade-Effekte:**
- Funktionen können deaktiviert werden
- Prüfen ob über neuen Limits
- Muss möglicherweise Benutzer reduzieren
- Muss möglicherweise Speicher reduzieren
- Anteilige Gutschrift

### Test-Verwaltung

**Test verlängern:**
1. Test-Behörde auswählen
2. Klicken Sie auf **Test verlängern**
3. Neue Dauer eingeben: Tage hinzufügen oder neues Enddatum festlegen
4. Notiz hinzufügen (Grund)
5. Verlängern

**Test umwandeln:**
1. Klicken Sie auf **In Bezahlt umwandeln**
2. Lizenztyp auswählen
3. Abrechnungsdetails festlegen
4. Sofort aktivieren
5. Test wandelt in bezahlt um

**Test-Benachrichtigungen:**
- 7 Tage vor Ablauf
- 3 Tage vor
- 1 Tag vor
- Am Ablauftag
- Kulanzfrist (optional)

## Datenbankverwaltung

### Datenbanken-Isolierung

**Wie Isolierung funktioniert:**
- Jede Behörde hat authority_id
- Alle Abfragen nach authority_id gefiltert
- Anwendung erzwingt Isolierung
- Super Admin kann umgehen (vorsichtig)
- Alle behördenübergreifenden Abfragen prüfen

**Datenbankstruktur:**
```sql
-- Jede Tabelle enthält authority_id
CREATE TABLE users (
  id INT PRIMARY KEY,
  authority_id INT NOT NULL,
  username VARCHAR(255),
  ...
  FOREIGN KEY (authority_id) REFERENCES authorities(id)
);

-- Abfragen automatisch gefiltert
SELECT * FROM users WHERE authority_id = ?;
```

**Verifizierung:**
- Regelmäßige Isolierungsprüfungen
- Behördenübergreifenden Zugriff prüfen
- Abfrageprotokolle überwachen
- Sicherheitstests
- Penetrationstests

### Datenbankverwaltung

**Pro-Behörden-Wartung:**

**Datenbank optimieren:**
1. Behörde auswählen
2. Klicken Sie auf **Datenbank optimieren**
3. Operationen: Tabellen analysieren, Tabellen optimieren, Indizes neu aufbauen
4. Optimierung ausführen
5. Ergebnisse anzeigen

**Daten bereinigen:**
- Gelöschte Elemente entfernen (nach Aufbewahrung)
- Alte Datensätze archivieren
- Alte Daten komprimieren
- Verwaiste Dateien entfernen
- Temporäre Daten bereinigen

**Datenbank-Backup:**
- Pro-Behörden-Backup
- On-Demand-Backup
- Geplante Backups
- Separat speichern
- Wiederherstellungsfähigkeit testen

## Speicherverwaltung

### Speichernutzung

**Speicher anzeigen:**
- **Gesamtspeicher**: Alle Behörden kombiniert
- **Pro Behörde**: Individuelle Nutzung
- **Speichertyp**: Datenbankgröße, Dateispeicher, Backups, Protokolle, Cache

**Speicheraufschlüsselung:**
- Dokumente
- Bilder
- Anhänge
- Backups
- Berichte
- Protokolle
- Sonstiges

**Speichertrends:**
- Wachstumsrate
- Nutzungsmuster
- Projektionen
- Bereinigungsmöglichkeiten

### Speicherlimits

**Kontingente durchsetzen:**
- Pro Behörde festlegen
- Vor Limit warnen
- Bei Limit blockieren
- Kulanzfrist (optional)
- Auto-Bereinigung (optional)

**Über Kontingent:**
1. Behörden-Admin benachrichtigen
2. Speichernutzung anzeigen
3. Bereinigung vorschlagen: Alte Dateien, Große Dateien, Duplikate
4. Upgrade-Option
5. Kulanzfrist

## Systemüberwachung

### Systemzustand

**Gesamtsystem überwachen:**

**Leistungsmetriken:**
- CPU-Nutzung (Server)
- Speichernutzung
- Festplatten-I/O
- Netzwerkverkehr
- Datenbankverbindungen
- Abfrageleistung
- Cache-Trefferrate
- Warteschlangenlänge

**Behördenzustand:**
- Pro-Behörden-Metriken
- Antwortzeiten
- Fehlerraten
- Benutzeraktivität
- Ressourcennutzung

**Warnungen:**
- Hohe CPU/Speicher
- Niedriger Festplattenspeicher
- Langsame Datenbank
- API-Fehler
- Sicherheitsereignisse
- Systemfehler

### Aktivitätsüberwachung

**Systemaktivität:**
- Aktive Benutzer (alle Behörden)
- Gleichzeitige Sitzungen
- API-Aufrufe pro Minute
- Datenbank-Abfragen pro Sekunde
- Datei-Uploads/-Downloads
- E-Mail-Versand
- Hintergrundjobs

**Pro-Behörden-Aktivität:**
- Benutzer-Logins
- Durchgeführte Aktionen
- Modulnutzung
- Feature-Nutzung
- API-Nutzung
- Speicheränderungen

## Abrechnung & Abonnements

### Abrechnungsübersicht

**System-Abrechnung:**
- Gesamtumsatz
- Aktive Abonnements
- Ausstehende Rechnungen
- Überfällige Zahlungen
- Abwanderungsrate
- MRR (Monatlich wiederkehrender Umsatz)
- ARR (Jährlich wiederkehrender Umsatz)

**Pro Behörde:**
- Abonnementstatus
- Aktuelles Guthaben
- Zahlungsmethode
- Abrechnungsverlauf
- Bevorstehende Gebühren

### Abrechnung verwalten

**Behörden-Abrechnung:**
1. Behörde auswählen
2. Zu Tab **Abrechnung** gehen
3. Anzeigen/Verwalten:

**Abonnement:**
- Aktueller Plan
- Abrechnungszyklus
- Erneuerungsdatum
- Auto-Erneuerungsstatus
- Plan ändern
- Abonnement kündigen

**Zahlungsmethode:**
- Hinterlegte Karte
- Bankkonto
- Rechnungsabrechnung
- Methode aktualisieren
- Zahlung verifizieren

**Rechnungen:**
- Vergangene Rechnungen
- Ausstehende Gebühren
- Zahlungsverlauf
- Rechnungen herunterladen
- Rechnungen erneut senden
- Als bezahlt markieren (manuell)

### Zahlungsprobleme

**Überfällige handhaben:**
1. Überfällige Behörden anzeigen
2. Für jede: Zahlungserinnerung senden, Kulanzfrist (falls konfiguriert), Suspendieren falls nicht bezahlt

**Fehlgeschlagene Zahlung:**
- Automatischer Wiederholungsversuch
- Admin benachrichtigen
- Zahlungsmethode aktualisieren
- Zahlung wiederholen
- Suspendieren falls ungelöst

**Rückerstattungen:**
1. Rechnung auswählen
2. Klicken Sie auf **Rückerstattung ausstellen**
3. Betrag eingeben: Volle Rückerstattung, Teilweise Rückerstattung, Grund
4. Rückerstattung verarbeiten
5. Behörde benachrichtigen
6. Datensätze aktualisieren

## Best Practices

**Tun Sie:**
✅ Regelmäßige Backups (alle Behörden)
✅ Systemzustand konstant überwachen
✅ Datenbanken-Isolierung regelmäßig testen
✅ Alle behördenübergreifenden Aktionen dokumentieren
✅ Behördenaktivität monatlich überprüfen
✅ Software aktuell halten
✅ Abrechnung und Zahlungen überwachen

**Nicht tun:**
❌ Unnötig auf Behördendaten zugreifen
❌ Sicherheitsmaßnahmen umgehen
❌ Super-Admin-Anmeldedaten teilen
❌ Undokumentierte Änderungen vornehmen
❌ Überwachungswarnungen ignorieren
❌ Behörden ohne Backup löschen
❌ Behördendaten mischen

## Fehlerbehebung

### Behörde nicht zugänglich
- Status überprüfen (aktiv?)
- Datenbankverbindung verifizieren
- Dateiberechtigungen überprüfen
- Fehlerprotokolle überprüfen
- Als Behörden-Admin testen
- Domain/DNS überprüfen

### Datenisolierungsverletzung
- **KRITISCH**: Sofort untersuchen
- Abfrageprotokolle überprüfen
- Anwendungscode überprüfen
- authority_id-Filter verifizieren
- Vorfall dokumentieren
- Betroffene Behörden benachrichtigen
- Fix implementieren
- Sicherheitsaudit

### Leistungsprobleme
- Serverressourcen überprüfen
- Langsame Abfragen überprüfen
- Pro-Behörden-Last überprüfen
- Schwere Behörden optimieren
- Ressourcenlimits erwägen
- Infrastruktur skalieren

---

## Nächste Schritte
- [⚙️ Systemeinstellungen](/guide/admin/system-settings)
- [👥 Benutzerverwaltung](/guide/admin/users)
- [🔐 Rollenverwaltung](/guide/admin/roles)
- [🎨 Authority-Branding](/guide/admin/authority-branding)
