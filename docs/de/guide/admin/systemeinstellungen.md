# Systemeinstellungen

Umfassende Systemadministrationsanleitung für die Konfiguration globaler Einstellungen, E-Mail, API, Sicherheit, Backups und Systemwartung.

## Überblick

Die Systemeinstellungen ermöglichen Administratoren die Konfiguration organisationsweiter Einstellungen, die alle Benutzer und das Systemverhalten betreffen.

**Hauptbereiche:**
- Allgemeine Systemeinstellungen
- E-Mail-Konfiguration
- API-Einstellungen und Integrationen
- Sicherheitsrichtlinien
- Backup und Wiederherstellung
- Systemwartung
- Leistungsüberwachung
- Feature-Flags
- Lizenzverwaltung
- Systeminformationen

## Anforderungen

**Erforderliche Berechtigung:** `ADMIN_SYSTEM` (volle Systemadministration)

**Zugriffsebene:** Administrator oder Super Administrator

## Systemeinstellungen öffnen

**Desktop:** Start → Administration → Systemeinstellungen
**Menü:** Administration → Einstellungen
**Route:** `/admin/settings`

## Allgemeine Einstellungen

### Organisationsinformationen

**Organisation konfigurieren:**
1. Zu Tab **Allgemein** gehen
2. Organisationsdetails ausfüllen:

**Grundinformationen:**
- **Organisationsname**: Rechtlicher Name
- **Anzeigename**: Kurzname
- **Website**: Organisations-Website
- **Branche**: Geschäftstyp

**Kontaktinformationen:**
- **Haupttelefon**: Primärkontakt
- **Haupt-E-Mail**: Allgemeine Anfragen
- **Support-E-Mail**: Technischer Support
- **Physische Adresse**: Straßenadresse, Stadt, Bundesland, PLZ, Land

**Steuer & Rechtliches:**
- **Steuer-ID**: EIN oder Äquivalent
- **Registrierungsnummer**: Geschäftsregistrierung
- **Lizenznummern**: Berufliche Lizenzen

3. Klicken Sie auf **Einstellungen speichern**

### Systempräferenzen

**Präferenzen konfigurieren:**

**Datum & Uhrzeit:**
- **Zeitzone**: Server-Zeitzone
- **Datumsformat**: MM/TT/JJJJ, TT/MM/JJJJ, JJJJ-MM-TT
- **Zeitformat**: 12-Stunden, 24-Stunden
- **Erster Tag der Woche**: Sonntag, Montag, Samstag

**Regional:**
- **Standardsprache**: Systemstandard (en, de, es, fr)
- **Währung**: Primärwährung
- **Zahlenformat**: Dezimaltrennzeichen, Tausendertrennzeichen

**Systemverhalten:**
- **Sitzungstimeout**: Minuten (Standard: 60)
- **Auto-Speicher-Intervall**: Sekunden (Standard: 30)
- **Elemente pro Seite**: Standard-Paginierung (25, 50, 100)
- **Max Upload-Größe**: MB (Standard: 20)
- **Erlaubte Dateitypen**: Erweiterungen

**Funktionen:**
- Module aktivieren/deaktivieren
- Feature-Verfügbarkeit pro Behörde
- Beta-Features umschalten

## E-Mail-Konfiguration

### E-Mail-Server-Einstellungen

**E-Mail konfigurieren:**
1. Zu Tab **E-Mail** gehen
2. E-Mail-Methode auswählen:

**SMTP-Konfiguration:**
- **Host**: SMTP-Serveradresse
- **Port**: 587 (TLS), 465 (SSL), 25 (unverschlüsselt)
- **Verschlüsselung**: TLS (empfohlen), SSL, Keine
- **Authentifizierung**: Benutzername, Passwort
- **Von-Adresse**: Standardabsender
- **Von-Name**: Absende-Anzeigename

**Test-E-Mail:**
1. Klicken Sie auf **Test-E-Mail senden**
2. Empfänger-E-Mail eingeben
3. Senden
4. Empfang verifizieren

**Alternative Methoden:**
- Sendgrid API
- AWS SES
- Mailgun

### E-Mail-Vorlagen

**System-E-Mail-Vorlagen:**
- Willkommens-E-Mail
- Passwortzurücksetzung
- E-Mail-Verifizierung
- Login-Benachrichtigung
- Aufgabe zugewiesen
- Nachricht empfangen

**Vorlagen anpassen:**
1. Vorlage auswählen
2. Bearbeiten: Betreffzeile, Body, Header/Footer
3. Vorschau
4. Test senden
5. Speichern

## API-Einstellungen

### API-Konfiguration

**API-Zugriff:**
1. Zu Tab **API** gehen
2. Konfigurieren:

**Allgemeine API-Einstellungen:**
- **API aktivieren**: Master-Schalter
- **API-Basis-URL**: `https://api.ihredomain.com`
- **API-Version**: Aktuelle Version
- **Rate-Limiting**: Anfragen pro Minute/Stunde
- **CORS-Einstellungen**: Erlaubte Origins, Methoden
- **API-Dokumentation**: Öffentliche Docs aktivieren/deaktivieren

**Authentifizierung:**
- **JWT-Einstellungen**: Secret Key, Token-Ablauf
- **API-Schlüssel**: API-Schlüssel-Authentifizierung aktivieren

### API-Integrationen

**Drittanbieter-Integrationen:**

**Google-Dienste:**
- Google Maps API
- Google Calendar-Synchronisation
- Google Drive

**Microsoft-Dienste:**
- Office 365
- Outlook-Kalender
- OneDrive

**Kommunikation:**
- Twilio (SMS)
- Slack
- Microsoft Teams

**Integration konfigurieren:**
1. Dienst auswählen
2. Integration aktivieren
3. Anmeldedaten eingeben
4. Verbindung testen
5. Optionen konfigurieren
6. Einstellungen speichern

## Sicherheitseinstellungen

### Passwortrichtlinie

**Passwortanforderungen konfigurieren:**
1. Zu Tab **Sicherheit** gehen
2. Passwortregeln festlegen:

**Passwortkomplexität:**
- **Mindestlänge**: 8-32 Zeichen (empfohlen: 12)
- **Großbuchstaben erforderlich**: A-Z
- **Kleinbuchstaben erforderlich**: a-z
- **Zahlen erforderlich**: 0-9
- **Sonderzeichen erforderlich**: !@#$%^&*
- **Häufige Passwörter verhindern**: Gegen Liste prüfen

**Passwortablauf:**
- **Abläuft nach**: Tage (0 = nie, 90 empfohlen)
- **Warnungstage**: Vor Ablauf benachrichtigen (7 Tage)
- **Kulanzfrist**: Tage nach Ablauf

**Passwortverlauf:**
- **Vorherige merken**: Wie viele (0-24)
- **Kann nicht wiederverwendet werden**: Vorherige N Passwörter

### Zwei-Faktor-Authentifizierung

**2FA-Konfiguration:**

**2FA-Optionen:**
- Authenticator-App (Google Auth, Authy)
- SMS-Code
- E-Mail-Code
- Backup-Codes

**2FA-Richtlinie:**
- **Für Admins erzwingen**: Erforderlich
- **Für alle Benutzer erzwingen**: Optional oder erforderlich
- **Kulanzfrist**: Tage zum Aktivieren (7 empfohlen)
- **Gerät merken**: Tage (30 empfohlen)

### Login-Sicherheit

**Login-Schutz:**

**Kontosperre:**
- **Fehlversuche**: Vor Sperre (5 empfohlen)
- **Sperrdauer**: Minuten (30 empfohlen)
- **Permanente Sperre**: Nach N Sperren
- **Admin-Benachrichtigung**: Admin bei Sperre per E-Mail

**Sitzungssicherheit:**
- **Max Sitzungen**: Gleichzeitige Sitzungen pro Benutzer (3)
- **Sitzungstimeout**: Leerlauf-Minuten (60)
- **Absolutes Timeout**: Max Sitzungsdauer (8 Stunden)
- **Sichere Cookies**: Nur HTTPS
- **IP-Bindung**: Sitzung an IP binden

### Datenschutz

**Datenschutz & Sicherheit:**

**Datenverschlüsselung:**
- Daten im Ruhezustand verschlüsseln
- Daten bei Übertragung verschlüsseln
- HTTPS erzwingen

**Datenaufbewahrung:**
- **Aktivitätsprotokolle**: Tage aufbewahren (365)
- **Gelöschte Elemente**: Tage vor permanent (30)
- **Audit-Trail**: Tage aufbewahren (7 Jahre)
- **Dateiversionen**: N Versionen behalten (10)

**GDPR-Konformität:**
- GDPR-Funktionen aktivieren
- Cookie-Einwilligung
- Datenexport (Benutzeranfrage)
- Recht auf Löschung

## Backup & Wiederherstellung

### Automatisierte Backups

**Backups konfigurieren:**
1. Zu Tab **Backup** gehen
2. Backup-Zeitplan festlegen:

**Backup-Zeitplan:**
- **Häufigkeit**: Stündlich, Täglich (empfohlen), Wöchentlich, Monatlich
- **Zeit**: Wann ausführen (2:00 Uhr empfohlen)

**Backup-Inhalte:**
- Datenbank
- Dateispeicher
- Systemkonfiguration
- Protokolle

**Backup-Speicher:**
- **Lokaler Speicher**: Pfad, Max Speicherplatz
- **Remote-Speicher**: AWS S3, Google Cloud Storage, Azure Blob Storage, FTP/SFTP

**Backup-Aufbewahrung:**
- **Täglich behalten**: 7 Tage
- **Wöchentlich behalten**: 4 Wochen
- **Monatlich behalten**: 12 Monate

### Manuelles Backup

**Manuelles Backup erstellen:**
1. Klicken Sie auf **Jetzt Backup erstellen**
2. Zu sicherndes auswählen
3. Beschreibung eingeben
4. Klicken Sie auf **Backup starten**
5. Fortschritt überwachen
6. Bei Fertigstellung herunterladen

### Aus Backup wiederherstellen

**Wiederherstellungsprozess:**
1. Klicken Sie auf **Wiederherstellen** bei Backup
2. **WARNUNG**: Dies überschreibt aktuelle Daten
3. Wiederherzustellendes auswählen
4. Bestätigung eingeben
5. Klicken Sie auf **Wiederherstellung starten**
6. System kann neu starten
7. Wiederherstellungserfolg verifizieren

## Systemwartung

### Systemzustand

**Zustandsdashboard:**
- **Systemstatus**: Gesamtzustand
- **CPU-Auslastung**: Aktuell und Durchschnitt
- **Speichernutzung**: Verwendet/Gesamt
- **Festplattenspeicher**: Verwendet/Verfügbar
- **Datenbankgröße**: Aktuelle Größe
- **Aktive Benutzer**: Aktuell angemeldet

**Zustandsprüfungen:**
- Datenbankkonnektivität
- E-Mail-Server-Konnektivität
- Externe API-Status
- Dateisystemzugriff
- Cache-System

### Wartungsmodus

**Wartung aktivieren:**
1. Zu Tab **Wartung** gehen
2. Klicken Sie auf **Wartungsmodus aktivieren**
3. Konfigurieren:
   - **Nachricht**: Benutzern anzeigen
   - **Admin-Zugriff erlauben**: Ja/Nein
   - **Geschätzte Dauer**: Zeit
4. Klicken Sie auf **Aktivieren**

**Während Wartung:**
- Alle Benutzer abgemeldet
- Zugriff verweigert (außer Admins)
- Wartungsseite angezeigt

**Wartung deaktivieren:**
1. Klicken Sie auf **Wartungsmodus deaktivieren**
2. System kehrt zu normal zurück

### Datenbankverwaltung

**Datenbankoperationen:**

**Datenbank optimieren:**
- Tabellen analysieren
- Tabellen optimieren
- Indizes neu aufbauen
- Statistiken aktualisieren
- Speicherplatz zurückgewinnen
- **Ausführen**: Wöchentlich empfohlen

**Bereinigen:**
- Alte Protokolle löschen
- Gelöschte Elemente entfernen
- Temporäre Dateien löschen
- Speicher optimieren
- Cache leeren
- **Ausführen**: Monatlich

## Leistungsüberwachung

### Leistungsmetriken

**Leistung überwachen:**

**Antwortzeiten:**
- Durchschnittliche Antwortzeit
- Langsamste Endpunkte
- Datenbank-Abfragezeit

**Ressourcennutzung:**
- CPU-Nutzung im Zeitverlauf
- Speichernutzungstrends
- Festplatten-I/O
- Netzwerkbandbreite

**Benutzermetriken:**
- Gleichzeitige Benutzer
- Seitenaufrufe
- Aktive Sitzungen
- Spitzennutzungszeiten

### Systemprotokolle

**Protokolle anzeigen:**
1. Zu Tab **Protokolle** gehen
2. Protokolltyp auswählen:

**Protokolltypen:**
- Anwendungsprotokolle
- Zugriffsprotokolle
- Fehlerprotokolle
- Audit-Protokolle
- Sicherheitsprotokolle

**Protokollanzeige:**
- Nach Ebene filtern (Info, Warnung, Fehler)
- Nach Datumsbereich filtern
- Protokollinhalt durchsuchen
- Protokolle exportieren

## Systeminformationen

### Systemdetails

**Systeminfo anzeigen:**

**Versionsinformationen:**
- K-Systems-Version
- PHP-Version
- Datenbankversion
- Node.js-Version
- Betriebssystem

**Module:**
- Installierte Module
- Modulversionen
- Aktiviert/Deaktiviert-Status

**Lizenz:**
- Lizenztyp
- Lizenziert an
- Ablaufdatum
- Lizenzierte Benutzer

### System aktualisieren

**Systemaktualisierungen:**

**Auf Updates prüfen:**
1. Klicken Sie auf **Auf Updates prüfen**
2. Verfügbare Updates anzeigen

**Aktualisierungsprozess:**
1. **Backup**: Auto-Backup erstellen
2. **Herunterladen**: Update-Dateien herunterladen
3. **Verifizieren**: Integrität prüfen
4. **Wartung**: Wartungsmodus aktivieren
5. **Installieren**: Update anwenden
6. **Migrieren**: Datenbank aktualisieren
7. **Testen**: Funktionalität verifizieren
8. **Abschließen**: Wartung deaktivieren

## Best Practices

**Tun Sie:**
✅ Regelmäßige Backups (täglich mindestens)
✅ Wiederherstellungsprozeduren testen
✅ Systemzustand überwachen
✅ Software aktuell halten
✅ Starke Passwortrichtlinien
✅ 2FA für Admins aktivieren
✅ Protokolle regelmäßig überprüfen

**Nicht tun:**
❌ Backups überspringen
❌ Sicherheitswarnungen ignorieren
❌ Standardpasswörter verwenden
❌ Sicherheitsfunktionen deaktivieren
❌ Systemaktualisierungen vernachlässigen
❌ Wartungsmodus anlassen

## Fehlerbehebung

### E-Mail wird nicht gesendet
- SMTP-Verbindung testen
- Anmeldedaten überprüfen
- Firewall/Ports verifizieren
- E-Mail-Protokolle überprüfen

### System läuft langsam
- Ressourcennutzung überprüfen
- Langsame Abfragen überprüfen
- Cache leeren
- Datenbank optimieren
- Festplattenspeicher überprüfen

### Backup fehlgeschlagen
- Festplattenspeicher überprüfen
- Berechtigungen verifizieren
- Remote-Speicher-Anmeldedaten überprüfen
- Backup-Protokolle überprüfen

---

## Nächste Schritte
- [👥 Benutzerverwaltung](/guide/admin/users)
- [🔐 Rollenverwaltung](/guide/admin/roles)
- [🏢 Behördenverwaltung](/guide/admin/authorities)
- [🎨 Authority-Branding](/guide/admin/authority-branding)
