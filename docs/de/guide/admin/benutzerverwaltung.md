# Benutzerverwaltung

Umfassende Anleitung zur Verwaltung von Benutzern in K-Systems.

## Überblick

Die Benutzerverwaltung ermöglicht Administratoren:
- Benutzerkonten erstellen und bearbeiten
- Rollen und Berechtigungen zuweisen
- Benutzerstatus verwalten (aktiv/inaktiv/gesperrt)
- Passwörter zurücksetzen
- Benutzereinstellungen konfigurieren
- Benutzeraktivität anzeigen
- Massenoperationen durchführen
- Benutzer importieren/exportieren

## Anforderungen

**Erforderliche Berechtigung:** `ADMIN_USER`

## Benutzerverwaltung öffnen

**Desktop:** Start → Administration → Benutzerverwaltung
**Menü:** Administration → Benutzer

## Benutzerliste

### Oberfläche

**Benutzertabellen-Spalten:**
- **Avatar**: Benutzerprofilbild
- **Benutzername**: Login-Name
- **Vollständiger Name**: Anzeigename
- **E-Mail**: E-Mail-Adresse
- **Rollen**: Zugewiesene Rollen (Badges)
- **Status**: Aktiv/Inaktiv/Gesperrt
- **Letzter Login**: Letztes Login-Datum/-Zeit
- **Aktionen**: Bearbeiten, Löschen, Passwort zurücksetzen

### Filter

**Schnellfilter:**
- Alle Benutzer
- Aktive Benutzer
- Inaktive Benutzer
- Gesperrte Benutzer
- Keine Rolle zugewiesen
- Administratoren

**Erweiterte Filter:**
1. Klicken Sie auf **Filter**-Button
2. Konfigurieren:
   - Rolle
   - Abteilung
   - Letztes Login-Datum
   - Erstellungsdatum
   - Behörde
3. Anwenden

### Suche

**Benutzer suchen:**
- Benutzername
- Vollständiger Name
- E-Mail
- Telefonnummer
- Mitarbeiter-ID (falls verknüpft)

## Benutzer erstellen

### Schritt 1: Grundinformationen

1. Klicken Sie auf **+ Benutzer hinzufügen**
2. Pflichtfelder ausfüllen:

**Erforderlich:**
- **Benutzername**: Eindeutiger Login-Name
  - 3-50 Zeichen
  - Buchstaben, Zahlen, Unterstrich, Bindestrich
  - Beispiel: john.doe
- **E-Mail**: Gültige E-Mail-Adresse
  - Wird für Benachrichtigungen verwendet
  - Muss eindeutig sein
- **Passwort**: Initiales Passwort
  - Mindestens 8 Zeichen
  - Muss Groß-, Kleinbuchstaben, Zahl enthalten
  - Oder **Passwort generieren** verwenden
- **Passwort bestätigen**: Passwort übereinstimmen

**Optional:**
- **Vorname**
- **Nachname**
- **Telefonnummer**
- **Mobilnummer**

### Schritt 2: Behördenzuweisung

**Behörde auswählen:**
- Dropdown aller Behörden
- Erforderlich
- Kann nach Erstellung nicht geändert werden (Admin kann)
- Bestimmt Datenzugriff

### Schritt 3: Rollenzuweisung

**Rollen zuweisen:**
1. Klicken Sie auf **Rolle hinzufügen**
2. Rolle aus Dropdown wählen
3. Mehrere Rollen erlaubt
4. Klicken Sie auf **Hinzufügen**

**Rollentypen:**
- Super Admin (voller Zugriff)
- Admin (Behördenverwaltung)
- Manager (Teamverwaltung)
- Benutzer (Standardzugriff)
- Betrachter (nur Lesezugriff)
- Benutzerdefinierte Rollen (konfiguriert)

**Rollenberechtigungen:**
- Jede Rolle hat spezifische Berechtigungen
- Mehrere Rollen = kombinierte Berechtigungen
- Höhere Berechtigung gewinnt bei Konflikt

### Schritt 4: Mitarbeiterverknüpfung (Optional)

**Mit Mitarbeiter verknüpfen:**
- Falls Benutzer auch Mitarbeiter ist
- Aus Mitarbeiterliste auswählen
- Verknüpft Benutzerkonto mit Mitarbeiterdatensatz
- Teilt Profilbild und Informationen

### Schritt 5: Benutzereinstellungen

**Kontoeinstellungen:**
- **Passwortwechsel erzwingen**: Benutzer muss Passwort beim ersten Login ändern
- **Kontoablauf**: Ablaufdatum festlegen (temporäre Konten)
- **Zwei-Faktor-Authentifizierung**: 2FA erforderlich
- **E-Mail verifiziert**: E-Mail als verifiziert markieren

**Zugriffseinstellungen:**
- **Desktop-Modus Standard**: Im Desktop-Modus starten
- **Standardsprache**: en, de, etc.
- **Zeitzone**: Benutzerzeitzone

### Schritt 6: Benachrichtigungseinstellungen

**Standard-Benachrichtigungen:**
- E-Mail-Benachrichtigungen (ein/aus)
- Push-Benachrichtigungen (ein/aus)
- SMS-Benachrichtigungen (ein/aus, falls aktiviert)
- Benachrichtigungsfrequenz (sofort/täglich/wöchentlich)

### Schritt 7: Benutzer speichern

**Speicheroptionen:**
- **Speichern**: Benutzer erstellen, Formular schließen
- **Speichern & Einladung senden**: Login-Details per E-Mail
- **Speichern & Neu**: Benutzer erstellen, neues Formular öffnen
- **Abbrechen**: Verwerfen

**Einladungs-E-Mail enthält:**
- Benutzername
- Temporäres Passwort (falls generiert)
- Login-URL
- Willkommensnachricht
- Nächste Schritte

## Benutzer bearbeiten

### Benutzerkonto bearbeiten

1. Benutzer in Liste finden
2. Klicken Sie auf **Bearbeiten**-Button
3. Felder ändern (wie bei Erstellung)
4. Klicken Sie auf **Speichern**

**Bearbeitbare Felder:**
- Grundinformationen
- E-Mail (erneut verifizieren)
- Rollen
- Mitarbeiterverknüpfung
- Einstellungen
- Status

**Nicht bearbeitbar (standardmäßig):**
- Benutzername (Admin-Override verfügbar)
- Behörde (Admin-Override verfügbar)

### Schnellbearbeitung

**Aus Benutzerliste:**
- Benutzername klicken → Schnellbearbeitungs-Popup
- Allgemeine Felder schnell ändern
- Speichern ohne vollständiges Formular

## Benutzerstatusverwaltung

### Statustypen

**Aktiv:**
- Normaler Status
- Kann sich anmelden
- Voller Zugriff auf Berechtigungen
- Empfängt Benachrichtigungen

**Inaktiv:**
- Vorübergehend deaktiviert
- Kann sich nicht anmelden
- Bewahrt Daten
- Kann jederzeit reaktiviert werden

**Gesperrt:**
- Permanent eingeschränkt
- Kann sich nicht anmelden
- Protokolliert Sperrversuche
- Admin-Prüfung erforderlich zum Entsperren

### Status ändern

**Benutzer aktivieren:**
1. Inaktiven/gesperrten Benutzer auswählen
2. Klicken Sie auf **Aktivieren**
3. Bestätigen
4. Benutzer kann sich sofort anmelden

**Benutzer deaktivieren:**
1. Aktiven Benutzer auswählen
2. Klicken Sie auf **Deaktivieren**
3. Grund eingeben (erforderlich)
4. Bestätigen
5. Benutzer sofort abgemeldet

**Benutzer sperren:**
1. Benutzer auswählen
2. Klicken Sie auf **Benutzer sperren**
3. Grund eingeben (erforderlich, protokolliert)
4. Bestätigen
5. Alle Sitzungen beendet

### Massenstatusänderung

**Mehrere Benutzer:**
1. Benutzer auswählen (Checkbox)
2. Klicken Sie auf **Aktionen** → **Status ändern**
3. Neuen Status auswählen
4. Grund eingeben
5. Auf alle ausgewählten anwenden

## Passwortverwaltung

### Passwort zurücksetzen

**Als Administrator:**
1. Benutzer öffnen
2. Klicken Sie auf **Passwort zurücksetzen**
3. Optionen:
   - **Neues generieren**: Automatisch sicheres Passwort generieren
   - **Manuell festlegen**: Spezifisches Passwort eingeben
   - **Reset-E-Mail senden**: Benutzer setzt eigenes Passwort
4. Option wählen
5. Bestätigen

**Passwortwechsel erzwingen:**
- **Muss Passwort ändern** aktivieren
- Benutzer wird beim nächsten Login aufgefordert
- Kann nicht fortfahren ohne Änderung

### Passwortrichtlinie

**Richtlinie konfigurieren (Global):**
1. Administration → Einstellungen → Sicherheit
2. Anforderungen festlegen:
   - Mindestlänge (Standard: 8)
   - Großbuchstaben erforderlich
   - Kleinbuchstaben erforderlich
   - Zahlen erforderlich
   - Sonderzeichen erforderlich
   - Passwortablauf (Tage)
   - Passwortverlauf (Wiederverwendung verhindern)
3. Speichern

**Benutzerpasswortanforderungen:**
- Entspricht Richtlinienanforderungen
- Nicht in häufiger Passwortliste
- Unterscheidet sich vom Benutzernamen
- Nicht zuvor verwendet (falls Verlauf aktiviert)

## Rollenverwaltung

### Rollen zuweisen

**Rolle zu Benutzer hinzufügen:**
1. Benutzer bearbeiten
2. Zu Tab **Rollen** gehen
3. Klicken Sie auf **Rolle hinzufügen**
4. Rolle auswählen
5. Speichern

**Rolle entfernen:**
1. Benutzer bearbeiten
2. Rolle in Liste finden
3. Klicken Sie auf ✖ zum Entfernen
4. Speichern

**Mehrere Rollen:**
- Benutzer kann mehrere Rollen haben
- Berechtigungen kombinieren (additiv)
- Höhere Berechtigung gewinnt bei Konflikt

### Rollendetails

**Rollenberechtigungen anzeigen:**
1. Rollennamen klicken
2. Alle enthaltenen Berechtigungen sehen
3. Gewährten Zugriff verstehen

**Benutzerdefinierte Rollen erstellen:**
- Siehe [Rollenverwaltungsleitfaden](/guide/admin/roles)

## Berechtigungen

### Benutzerberechtigungen

**Berechtigungsquellen:**
1. Zugewiesene Rollen
2. Direkte Berechtigungen (Override)
3. Gruppenberechtigungen
4. Feature-Flags

**Benutzerberechtigungen anzeigen:**
1. Benutzer öffnen
2. Zu Tab **Berechtigungen** gehen
3. Alle effektiven Berechtigungen sehen
4. Quelle pro Berechtigung angezeigt

### Direkte Berechtigungszuweisung

**Berechtigung direkt zuweisen:**
1. Benutzer bearbeiten
2. Zu Tab **Berechtigungen** gehen
3. Klicken Sie auf **Berechtigung hinzufügen**
4. Berechtigung auswählen
5. Speichern

**Anwendungsfälle:**
- Temporärer Zugriff
- Ausnahme von Rolle
- Testen
- Einmalige Anforderungen

**Best Practice:**
- Rollen verwenden, nicht direkte Berechtigungen
- Direkte Berechtigungen erschweren Verwaltung
- Dokumentieren, warum direkte Berechtigung vergeben

## Benutzeraktivität

### Aktivitätsprotokoll anzeigen

**Benutzeraktivität:**
1. Benutzer öffnen
2. Zu Tab **Aktivität** gehen
3. Anzeigen:
   - Login-Verlauf
   - Seitenbesuche
   - Durchgeführte Aktionen
   - API-Aufrufe
   - Vorgenommene Änderungen

**Aktivitätsdetails:**
- Zeitstempel
- IP-Adresse
- Browser/Gerät
- Aktionstyp
- Ergebnis (Erfolg/Fehler)

**Aktivität filtern:**
- Datumsbereich
- Aktionstyp
- Nur Erfolg/Fehler

**Aktivität exportieren:**
- Nach Excel exportieren
- Nach CSV exportieren
- Datumsbereichsauswahl

### Login-Verlauf

**Letzte Logins:**
- Letzte 100 Logins
- Datum/Uhrzeit
- IP-Adresse
- Standort (falls verfügbar)
- Gerät/Browser
- Erfolg/Fehler

**Fehlgeschlagene Logins:**
- Fehlversuche nachverfolgen
- Potenzielle Sicherheitsprobleme
- Auto-Sperre nach N Fehlern

### Sitzungsverwaltung

**Aktive Sitzungen:**
1. Benutzer öffnen
2. Zu Tab **Sitzungen** gehen
3. Alle aktiven Sitzungen sehen:
   - Login-Zeit
   - Letzte Aktivität
   - IP-Adresse
   - Gerät

**Sitzung beenden:**
- Klicken Sie auf **Beenden** bei Sitzung
- Benutzer sofort abgemeldet
- Muss sich erneut anmelden

**Alle Sitzungen beenden:**
- Meldet alle Geräte ab
- Verwenden bei kompromittiertem Konto
- Benutzer muss sich erneut authentifizieren

## Massenoperationen

### Mehrere Benutzer auswählen

**Auswahl:**
- Checkbox neben jedem Benutzer
- Oder Header-Checkbox für alle (Seite)
- Alle auswählen (alle Seiten) Option

### Massenaktionen

**Verfügbare Aktionen:**

**1. Rolle zuweisen**
- Benutzer auswählen
- Aktionen → Rolle zuweisen
- Rolle wählen
- Auf alle anwenden

**2. Rolle entfernen**
- Benutzer auswählen
- Aktionen → Rolle entfernen
- Zu entfernende Rolle wählen
- Anwenden

**3. Status ändern**
- Benutzer auswählen
- Aktionen → Status ändern
- Neuen Status wählen
- Grund eingeben
- Anwenden

**4. Behörde festlegen** (nur Super Admin)
- Benutzer auswählen
- Aktionen → Behörde festlegen
- Behörde wählen
- Bestätigen (Datenauswirkungen)

**5. Benutzer löschen**
- Benutzer auswählen
- Aktionen → Löschen
- Bestätigen (PERMANENT)
- Benutzer entfernt

**6. Benutzer exportieren**
- Benutzer auswählen
- Aktionen → Exportieren
- Format wählen (Excel/CSV)
- Datei herunterladen

**7. E-Mail senden**
- Benutzer auswählen
- Aktionen → E-Mail senden
- Nachricht verfassen
- An alle ausgewählten senden

## Benutzer importieren

### Aus Datei importieren

**Datei vorbereiten:**
1. Vorlage herunterladen (Excel/CSV)
2. Benutzerdaten ausfüllen:
   - Benutzername (erforderlich)
   - E-Mail (erforderlich)
   - Passwort (optional, generiert falls leer)
   - Vorname
   - Nachname
   - Rollen (kommagetrennt)
   - Abteilung
   - Telefon
3. Datei speichern

**Importieren:**
1. Klicken Sie auf **Benutzer importieren**
2. Datei auswählen
3. Spalten zuordnen (falls nötig)
4. Import-Vorschau
5. Optionen wählen:
   - Bestehende Benutzer aktualisieren
   - Duplikate überspringen
   - Einladungen senden
6. Klicken Sie auf **Importieren**
7. Ergebnisse anzeigen:
   - Erfolgszahl
   - Fehlgeschlagene Zeilen (mit Gründen)
   - Fehlerbericht herunterladen

**Import-Validierung:**
- Benutzernamenformat
- E-Mail-Format
- Doppelte Benutzernamen
- Ungültige Rollen
- Pflichtfelder

### Massenbenutzererstellung

**Aus Vorlage:**
1. Benutzervorlage erstellen
2. Standardrollen definieren
3. Mehrere Benutzer generieren
4. Optional: Sequentielle Benutzernamen/E-Mails

**Anwendungsfall:**
- Großes Team onboarden
- Schulungskonten erstellen
- Testzwecke

## Benutzerprofilverwaltung

### Profilinformationen

**Bearbeitbar durch Admin:**
- Profilbild
- Vollständiger Name
- Kontaktinformationen
- Berufsbezeichnung
- Abteilung
- Bio/Beschreibung

**Bearbeitbar durch Benutzer:**
(Einstellungen → Profil)
- Profilbild
- Anzeigename
- Bio
- Kontaktpräferenzen

### Profilbild

**Bild hochladen:**
1. Benutzer bearbeiten
2. Klicken Sie auf **Bild ändern**
3. Bild auswählen
4. Zuschneiden falls nötig
5. Speichern

**Anforderungen:**
- JPG, PNG, GIF
- Max 5MB
- Empfohlen: 256x256px quadratisch

**Bild entfernen:**
- Klicken Sie auf **Bild entfernen**
- Standard-Avatar angezeigt

## Sicherheitsfunktionen

### Zwei-Faktor-Authentifizierung

**2FA für Benutzer aktivieren:**
1. Benutzer bearbeiten
2. Zu Tab **Sicherheit** gehen
3. **2FA erforderlich** aktivieren
4. Benutzer richtet beim nächsten Login ein

**2FA-Methoden:**
- Authenticator-App (Google Auth, etc.)
- SMS-Code (falls aktiviert)
- E-Mail-Code
- Backup-Codes

**2FA zurücksetzen:**
- Falls Benutzer Gerät verliert
- Klicken Sie auf **2FA zurücksetzen**
- Benutzer konfiguriert neu

### Kontosperre

**Automatische Sperre:**
- Nach N fehlgeschlagenen Login-Versuchen (Standard: 5)
- Dauer: 30 Minuten
- Oder Admin entsperrt

**Manuelle Sperre:**
1. Benutzer bearbeiten
2. Klicken Sie auf **Konto sperren**
3. Grund eingeben
4. Speichern

**Entsperren:**
- Klicken Sie auf **Konto entsperren**
- Benutzer kann sich sofort anmelden

### API-Zugriff

**API-Tokens:**
1. Benutzer bearbeiten
2. Zu Tab **API** gehen
3. API-Token generieren
4. Berechtigungen festlegen
5. Ablauf festlegen
6. Speichern

**Token-Verwaltung:**
- Aktive Tokens anzeigen
- Tokens widerrufen
- Token-Ablauf festlegen
- Berechtigungen pro Token begrenzen

## Benutzer löschen

### Benutzer löschen

**Soft Delete (Empfohlen):**
1. Benutzer auswählen
2. Klicken Sie auf **Löschen**
3. Bestätigen
4. Benutzer deaktiviert
5. Kann innerhalb 30 Tagen wiederhergestellt werden

**Hard Delete:**
1. Deaktivierten Benutzer auswählen
2. Klicken Sie auf **Permanent löschen**
3. Bestätigung eingeben
4. WARNUNG: Kann nicht rückgängig gemacht werden
5. Alle Benutzerdaten gelöscht

### Löschauswirkungen

**Was passiert:**
- Benutzer kann sich nicht anmelden
- Sitzungen beendet
- Profil verborgen
- **Daten bewahrt:**
  - Erstellte Berichte
  - Nachrichten (anonymisiert)
  - Aktivitätsprotokolle
  - Audit-Trail

**Eigentumsübertragung:**
- Vor dem Löschen übertragen:
  - Berichte an anderen Benutzer
  - Gruppenbesitze
  - Dokumentbesitze
  - Kalenderereignisse

### Gelöschten Benutzer wiederherstellen

**Innerhalb 30 Tagen:**
1. Zu **Gelöschte Benutzer** gehen
2. Benutzer finden
3. Klicken Sie auf **Wiederherstellen**
4. Bestätigen
5. Benutzer reaktiviert
6. Passwort zurücksetzen muss

## Berichte & Analysen

### Benutzerstatistiken

**Systemstatistiken:**
- Gesamtbenutzer
- Aktive Benutzer
- Inaktive Benutzer
- Gesperrte Benutzer
- Benutzer nach Rolle
- Benutzer nach Behörde
- Neue Benutzer (diesen Monat)
- Login-Aktivität

**Statistiken anzeigen:**
1. Administration → Benutzer → Statistiken
2. Diagramme und Metriken anzeigen
3. Nach Datumsbereich filtern
4. Berichte exportieren

### Benutzerberichte

**Verfügbare Berichte:**
1. **Benutzerliste**: Alle Benutzer mit Details
2. **Aktive Benutzer**: Aktuell aktive
3. **Inaktive Benutzer**: Benötigen Aufmerksamkeit
4. **Rollenzuweisung**: Benutzer nach Rolle
5. **Login-Aktivität**: Login-Muster
6. **Berechtigungsaudit**: Berechtigungsübersicht

**Bericht generieren:**
1. Berichtstyp auswählen
2. Filter konfigurieren
3. Format wählen (PDF/Excel)
4. Herunterladen

## Best Practices

**Tun Sie:**
✅ Rollen zuweisen, nicht direkte Berechtigungen
✅ Starke Passwortrichtlinie verwenden
✅ 2FA für Admins aktivieren
✅ Regelmäßige Benutzeraudits
✅ Rollenänderungen dokumentieren
✅ Deaktivieren statt löschen
✅ Fehlgeschlagene Logins überwachen

**Nicht tun:**
❌ Admin-Konten teilen
❌ Schwache Passwörter verwenden
❌ Unnötige Berechtigungen vergeben
❌ Vergessen, alte Konten zu entfernen
❌ Fehlgeschlagene Login-Versuche ignorieren
❌ Benutzer permanent löschen
❌ Offboarding-Prozess überspringen

## Fehlerbehebung

### Benutzer kann sich nicht anmelden
1. Status überprüfen (Aktiv?)
2. Anmeldedaten verifizieren
3. Kontosperre überprüfen
4. Behördenübereinstimmung verifizieren
5. 2FA-Status überprüfen
6. Fehlerprotokolle überprüfen

### Fehlende Berechtigungen
1. Zugewiesene Rollen überprüfen
2. Rollenberechtigungen verifizieren
3. Feature-Flags überprüfen
4. Behördenberechtigungen verifizieren
5. Direkte Berechtigungen überprüfen

### Import fehlgeschlagen
1. Dateiformat überprüfen
2. Spaltenüberschriften verifizieren
3. Auf Duplikate prüfen
4. Datenformat validieren
5. Fehlerprotokoll überprüfen

---

## Nächste Schritte
- [🔐 Rollenverwaltung](/guide/admin/roles)
- [🏢 Behördenverwaltung](/guide/admin/authority)
- [⚙️ Systemeinstellungen](/guide/admin/settings)
