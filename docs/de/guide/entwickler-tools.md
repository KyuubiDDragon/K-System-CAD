# Entwickler-Tools

K-Systems umfasst mehrere leistungsstarke Entwickler- und Debugging-Tools, die Entwicklern und Administratoren helfen, das Systemverhalten zu überwachen, zu debuggen und zu analysieren. Diese Tools bieten tiefe Einblicke in den Laufzeitstatus der Anwendung, WebSocket-Verbindungen und Systemaktivitäten.

::: warning Nur für Entwickler/Administratoren
Diese Tools erfordern Entwickler- oder Administratorberechtigungen. Sie geben sensible Systeminformationen preis und sollten nur von autorisiertem Personal verwendet werden.
:::

::: tip Benutzerreferenz
Suchen Sie nach dem Spickzettel mit 10-Codes und Notrufnummern? Siehe den [Spickzettel-Leitfaden](./spickzettel).
:::

## Übersicht

Die Entwickler-Tools-Suite umfasst:

- **Desktop Debug** - Desktop-Modus-Debugging und Zustandsinspektion
- **Socket Debug** - WebSocket-Verbindungsdiagnose und -Überwachung
- **Systemprotokolle** - Umfassender Audit-Log-Viewer (nur Admin)

## Desktop Debug

**Zugriff:** `/desktopDebug`

Das Desktop-Debug-Tool hilft Entwicklern bei der Fehlerbehebung der Desktop-Modus-Funktionalität, die es K-Systems ermöglicht, als eingebettete Anwendung in Desktop-Frameworks oder Kiosk-Modi zu laufen.

### Was ist der Desktop-Modus?

Der Desktop-Modus ist ein spezieller Anzeigemodus, bei dem:
- Navigationselemente ausgeblendet werden können
- Die Benutzeroberfläche sich für eingebettete/Kiosk-Anzeige anpasst
- URL-Parameter den Schnittstellenstatus steuern
- CSS-Klassen dynamisch auf das HTML-Element angewendet werden

### Funktionen

#### 1. **Anzeige des aktuellen Modus**
Zeigt an, ob der Desktop-Modus derzeit aktiviert oder deaktiviert ist.

#### 2. **Query-Parameter-Inspektor**
Zeigt alle URL-Query-Parameter an, die den Desktop-Modus beeinflussen:
- `desktop` - Desktop-Modus aktivieren/deaktivieren
- `hideLeftNav` - Linke Navigationsseitenleiste ausblenden
- `hideTopNav` - Obere Navigationsleiste ausblenden

#### 3. **CSS-Klassen-Inspektor**
Zeigt, welche CSS-Klassen derzeit angewendet werden:
- `desktop-mode` - Haupt-Desktop-Modus-Klasse
- `hide-left-nav` - Linke Navigation ausgeblendet
- `hide-top-nav` - Obere Navigation ausgeblendet

#### 4. **UI-Store-Zustandsanzeige**
Zeigt den vollständigen UI-Store-Zustand im JSON-Format an:
- `isDesktopMode` - Aktueller Desktop-Modus-Status
- Andere UI-bezogene Zustandsvariablen

### Aktionen

**Desktop-Modus umschalten**
- Desktop-Modus manuell ein-/ausschalten
- Wendet die CSS-Klasse `desktop-mode` an/entfernt sie
- Aktualisiert den UI-Store-Zustand

**Seite aktualisieren**
- Lädt die gesamte Seite neu
- Nützlich nach Konfigurationsänderungen

**Mit Desktop-Parametern öffnen**
- Öffnet die aktuelle Seite mit Desktop-Modus-Query-Parametern
- Setzt `desktop=true`, `hideLeftNav=false`, `hideTopNav=false`
- Nützlich zum Testen des Desktop-Modus-Verhaltens

### Anwendungsfälle

- **Layout-Probleme debuggen** - Überprüfen Sie, ob CSS-Klassen korrekt angewendet werden
- **Eingebettete Szenarien testen** - Simulieren Sie Kiosk- oder eingebettete Anzeigemodi
- **Zustandsvalidierung** - Sicherstellen, dass der Desktop-Modus-Zustand korrekt erhalten bleibt
- **Integrationstests** - Testen Sie den Desktop-Modus mit verschiedenen Parameterkombinationen

### Beispiel-URL-Parameter

```
# Desktop-Modus mit ausgeblendeter Navigation aktivieren
/?desktop=true&hideLeftNav=true&hideTopNav=true

# Desktop-Modus mit sichtbarer Navigation
/?desktop=true&hideLeftNav=false&hideTopNav=false

# Desktop-Modus deaktivieren
/?desktop=false
```

## Socket Debug

**Zugriff:** `/socketDebug`

Das Socket-Debug-Tool bietet umfassende Diagnosen für WebSocket-Verbindungen (Socket.io), die die Echtzeit-Funktionen von K-Systems wie Chat, Benachrichtigungen und kollaboratives Whiteboard antreiben.

### Architekturübersicht

K-Systems verwendet mehrere Socket.io-Namespaces:
- `/chat` - Echtzeit-Chat-Messaging
- `/notification` - Systembenachrichtigungen
- `/status` - Benutzer-Online-/Offline-Status
- `/whiteboard` - Kollaboratives Whiteboard-Zeichnen

### Funktionen

#### 1. **Verbindungsstatus-Panel**
Zeigt den aktuellen Status aller Socket-Verbindungen an:
- **Connected** (grün) - Socket ist aktiv und funktioniert
- **Disconnected** (grau) - Socket ist nicht verbunden
- **Reconnecting** (orange) - Socket versucht, sich erneut zu verbinden
- **Error** (rot) - Verbindungsfehler aufgetreten

Jede Verbindungskarte zeigt:
- Socket-Namespace-Name
- Aktueller Status
- Socket-ID (wenn verbunden)

#### 2. **Authentifizierungs-Panel**
Zeigt JWT-Token-Informationen an:
- **Token-Vorschau** - Erste/letzte Zeichen des Tokens
- **Quelle** - Woher das Token abgerufen wurde (localStorage, Cookie, etc.)
- **Gültigkeit** - Ob das Token gültig ist
- **Cookie-Status** - Vorhandensein von Authentifizierungs-Cookies

#### 3. **Verbindungstest-Panel**
Zeigt Ergebnisse von Verbindungstests:
- Teststatus (Erfolg, Timeout, Fehler)
- Testnachrichten und Antworten
- Zeitstempel jedes Tests

#### 4. **Fehler-Panel**
Zeigt aktuelle Verbindungsfehler an:
- Fehlerdetails
- Socket-Namespace, der den Fehler festgestellt hat
- Zeitstempel des Fehlers

#### 5. **Netzwerkstatus-Panel**
Zeigt Netzwerkkonnektivitätsinformationen:
- **Server erreichbar** - Ob der Socket-Server zugänglich ist
- **Socket-Server-URL** - Der konfigurierte Socket-Server-Endpunkt
- **Browser-URL** - Aktueller Browser-Standort

#### 6. **Umgebungs-Panel**
Zeigt Umgebungskonfiguration an:
- Socket-Server-URL aus Umgebungsvariablen
- API-URL-Konfiguration
- Andere relevante Umgebungseinstellungen

#### 7. **Manuelles Test-Panel**
Bietet Konsolenbefehle für erweitertes Debugging:

```javascript
// Diagnose-UI anzeigen
window.socketHelper.runDiagnostic()

// Token-Abruf testen
window.socketHelper.testToken()

// Manuelle Verbindung testen
window.socketHelper.testManualConnection()

// Alle Sockets zurücksetzen und neu verbinden
window.socketHelper.resetAndReconnect()
```

### Aktionen

**Verbindungen initialisieren**
- Socket-Initialisierung manuell auslösen
- Nützlich nach Konfigurationsänderungen

**Alle Verbindungen testen**
- Sendet Testereignisse an alle Socket-Namespaces
- Validiert bidirektionale Kommunikation
- Zeitüberschreitung nach 5 Sekunden pro Verbindung

**Alle Verbindungen zurücksetzen**
- Trennt alle Sockets
- Löscht Socket-Referenzen
- Initialisiert Verbindungen nach 1 Sekunde Verzögerung neu

**CORS testen**
- Überprüft Cross-Origin Resource Sharing-Konfiguration
- Validiert, dass der Browser mit dem Socket-Server kommunizieren kann

**Netzwerk überprüfen**
- Testet Netzwerkkonnektivität
- Validiert Servererreichbarkeit

### Häufige Probleme und Lösungen

#### Problem: Alle Sockets getrennt
**Symptome:** Alle Verbindungen zeigen den Status "disconnected"

**Lösungen:**
1. Überprüfen Sie, ob der Socket-Server läuft (standardmäßig Port 3001)
2. Überprüfen Sie die Umgebungsvariable `VITE_SOCKET_URL`
3. Klicken Sie auf "Verbindungen initialisieren" zum erneuten Versuch
4. Überprüfen Sie die Browser-Konsole auf Verbindungsfehler

#### Problem: Authentifizierung fehlgeschlagen
**Symptome:** Verbindungen schlagen sofort nach dem Verbinden fehl

**Lösungen:**
1. Überprüfen Sie, ob JWT-Token im Authentifizierungs-Panel vorhanden ist
2. Überprüfen Sie Token-Gültigkeit
3. Melden Sie sich ab und wieder an, um ein neues Token zu erhalten
4. Überprüfen Sie die Authentifizierungs-Middleware des Socket-Servers

#### Problem: CORS-Fehler
**Symptome:** Browser-Konsole zeigt CORS-bezogene Fehler

**Lösungen:**
1. Klicken Sie auf "CORS testen" zur Überprüfung der Konfiguration
2. Überprüfen Sie die CORS-Einstellungen des Socket-Servers in `/socket-server/index.js`
3. Stellen Sie sicher, dass die Frontend-URL in der Liste der erlaubten Ursprünge enthalten ist

#### Problem: Zeitweilige Trennungen
**Symptome:** Sockets verbinden und trennen sich wiederholt

**Lösungen:**
1. Überprüfen Sie die Netzwerkstabilität
2. Überprüfen Sie Serverprotokolle auf Fehler
3. Überprüfen Sie Firewall-/Proxy-Einstellungen
4. Überprüfen Sie die Unterstützung des WebSocket-Protokolls

### Auto-Aktualisierung

Die Socket-Debug-Ansicht aktualisiert den Verbindungsstatus automatisch alle 3 Sekunden und bietet Echtzeitüberwachung ohne manuellen Eingriff.

## Systemprotokolle (Admin)

**Zugriff:** `/admin/logs`
**Erforderliche Berechtigung:** `SYSTEM_ADMIN`

Der Systemprotokoll-Viewer bietet umfassende Audit-Trail-Funktionalität und verfolgt alle Datenbankänderungen und Benutzeraktionen innerhalb von K-Systems.

### Übersicht

Jede Datenbankoperation (INSERT, UPDATE, DELETE) wird automatisch in der Tabelle `system_logs` protokolliert und erstellt einen vollständigen Audit-Trail der Systemaktivität. Dies ist wichtig für:
- Compliance- und regulatorische Anforderungen
- Sicherheitsaudits
- Fehlerbehebung bei Datenproblemen
- Verstehen des Benutzerverhaltens
- Nachverfolgung von Systemänderungen

### Funktionen

#### 1. **Statistik-Dashboard**

Einklappbares Statistik-Panel mit:

**Gesamtanzahl Logs**
- Gesamtzahl der Protokolleinträge im System

**Top-Benutzer**
- Benutzer mit den meisten protokollierten Aktionen
- Zeigt Benutzername und Aktionszahl
- Erweiterbar, um alle Benutzer anzuzeigen

**Aktionstypen**
- Aufschlüsselung nach Aktionstyp (INSERT, UPDATE, DELETE, LOGIN)
- Farbcodierte Chips zur schnellen Identifizierung
- Anzahl für jeden Aktionstyp

**Top-Tabellen**
- Am häufigsten geänderte Datenbanktabellen
- Nützlich zur Identifizierung von Bereichen mit hoher Aktivität

#### 2. **Erweiterte Filterung**

**Datumsbereichsfilter**
- Startdatum - Protokolle ab diesem Datum filtern
- Enddatum - Protokolle bis zu diesem Datum filtern
- Visueller Datumsauswähler für einfache Auswahl

**Aktionstyp-Filter**
- Nach spezifischer Aktion filtern (INSERT, UPDATE, DELETE, LOGIN)
- Dynamisch aus tatsächlichen Protokolldaten gefüllt

**Tabellenname-Filter**
- Nach spezifischer Datenbanktabelle filtern
- Dynamisch aus tatsächlichen Protokolldaten gefüllt

**Behördenfilter** (nur System-Admin)
- Protokolle nach Organisation/Behörde filtern
- Nützlich in Mehrmieter-Umgebungen

**Suche**
- Freitextsuche über alle Protokollfelder
- Durchsucht Benutzernamen, Tabellennamen, Werte, etc.

#### 3. **Datentabelle**

Paginierte Tabelle mit sortierbaren Spalten:

| Spalte | Beschreibung |
|--------|--------------|
| ID | Eindeutiger Protokolleintrag-Identifikator |
| Aktion | Art der Aktion (INSERT, UPDATE, DELETE) |
| Tabelle | Betroffene Datenbanktabelle |
| Benutzer | Benutzername, der die Aktion durchgeführt hat |
| Spalte | Spezifische Spalte, die geändert wurde |
| Datensatz-ID | Datenbank-Datensatz-Identifikator |
| Zeitpunkt | Wann die Aktion stattfand |
| Details | Schaltfläche zum Anzeigen vollständiger Protokolldetails |

**Tabellenfunktionen:**
- Sortierbar nach jeder Spalte
- Paginierung (25, 50 oder 100 Elemente pro Seite)
- Ladeskelett während des Abrufens
- Leerer Zustand mit hilfreicher Nachricht

#### 4. **Protokolldetails-Dialog**

Klicken Sie auf das Augensymbol, um vollständige Protokolldetails anzuzeigen:

**Grundinformationen:**
- Log-ID
- Zeitstempel (formatiert in deutscher Lokalität)
- Benutzer (Benutzername und ID)
- Aktionstyp (farbcodierter Chip)
- Tabellenname
- Datensatz-ID
- Spaltenname (falls zutreffend)
- Behörden-ID (falls zutreffend)

**Wertevergleich:**
- **Alter Wert** - Vorherige Daten vor der Änderung
- **Neuer Wert** - Neue Daten nach der Änderung
- JSON-Formatierung für komplexe Daten
- Seite-an-Seite-Vergleich für UPDATE-Aktionen

#### 5. **Export-Funktionalität**

**CSV-Export**
- Exportiert gefilterte Protokolle in CSV-Format
- Berücksichtigt alle aktiven Filter
- Geeignet für externe Analyse in Excel, Google Sheets, etc.
- Enthält alle Protokollfelder

#### 6. **Protokolllöschung** (nur System-Admin)

**Protokolle löschen-Dialog**

Zwei Löschmodi:

**Alle Protokolle löschen**
- Entfernt alle Protokolleinträge aus dem System
- Kann nicht rückgängig gemacht werden

**Protokolle vor Datum löschen**
- Entfernt Protokolle, die älter als das angegebene Datum sind
- Nützlich für regelmäßige Bereinigung
- Behält aktuelle Protokolle für aktive Überwachung bei

::: danger Warnung
Das Löschen von Protokollen ist dauerhaft und kann nicht rückgängig gemacht werden. Exportieren Sie Protokolle immer vor dem Löschen, wenn Sie die Daten behalten müssen.
:::

### Farbcodierung

Aktionen sind farbcodiert zur schnellen Identifizierung:

- **INSERT** - Grün (Erfolg) - Neuer Datensatz erstellt
- **UPDATE** - Blau (Info) - Vorhandener Datensatz geändert
- **DELETE** - Rot (Fehler) - Datensatz gelöscht
- **LOGIN** - Orange (Warnung) - Benutzer-Login-Ereignis

### Anwendungsfälle

#### Compliance-Auditing
Verfolgen Sie alle Änderungen für regulatorische Compliance-Anforderungen (DSGVO, HIPAA, SOX, etc.)

#### Sicherheitsuntersuchung
Untersuchen Sie verdächtige Aktivitäten:
1. Nach bestimmtem Benutzer filtern
2. Alle ihre letzten Aktionen überprüfen
3. Auf unbefugten Datenzugriff prüfen
4. Anmeldezeiten und -muster überprüfen

#### Datenwiederherstellung
Identifizieren Sie, was geändert wurde:
1. Nach Tabellenname und Datensatz-ID filtern
2. Alten Wert vor der Änderung anzeigen
3. Daten manuell wiederherstellen, falls erforderlich

#### Systemüberwachung
Systemgesundheit überwachen:
1. Top-Tabellen auf ungewöhnliche Aktivität überprüfen
2. Auf Fehlermuster prüfen
3. Benutzer mit hohem Volumen identifizieren
4. Systemnutzungstrends verfolgen

#### Fehlerbehebung
Datenprobleme debuggen:
1. Finden Sie heraus, wann ein Datensatz zuletzt geändert wurde
2. Sehen Sie, wer die Änderung vorgenommen hat
3. Vergleichen Sie alte vs. neue Werte
4. Verfolgen Sie die Abfolge der Änderungen

### Leistungsüberlegungen

Die Systemprotokolle-Tabelle kann in aktiven Systemen sehr groß werden. Berücksichtigen Sie:

- **Regelmäßige Bereinigung** - Löschen Sie alte Protokolle regelmäßig (z. B. älter als 1 Jahr)
- **Archivierung** - Exportieren und archivieren Sie alte Protokolle in externem Speicher
- **Indizierung** - Datenbankindizes auf häufig abgefragten Spalten (user_id, table_name, timestamp)
- **Filterung** - Verwenden Sie immer Datumsbereichsfilter für große Datensätze

### Datenbankschema

Die Struktur der `system_logs`-Tabelle:

```sql
CREATE TABLE system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(50),           -- INSERT, UPDATE, DELETE, LOGIN
  table_name VARCHAR(100),      -- Betroffene Datenbanktabelle
  record_id INT,                -- Datensatz-ID in der betroffenen Tabelle
  user_id INT,                  -- Benutzer, der die Aktion durchgeführt hat
  column_name VARCHAR(100),     -- Geänderte Spalte
  old_value TEXT,               -- Vorheriger Wert (JSON)
  new_value TEXT,               -- Neuer Wert (JSON)
  timestamp TIMESTAMP,          -- Wann die Aktion stattfand
  authority_id INT,             -- Organisations-/Behörden-ID
  INDEX idx_timestamp (timestamp),
  INDEX idx_user_id (user_id),
  INDEX idx_table_name (table_name)
);
```

## Sicherheitsüberlegungen

::: warning Sicherheitshinweis
Entwickler-Tools geben sensible Systeminformationen preis und sollten geschützt werden:
:::

### Zugriffskontrolle

1. **Berechtigungsbasierter Zugriff**
   - Desktop Debug: Entwicklerberechtigung empfohlen
   - Socket Debug: Entwicklerberechtigung empfohlen
   - Systemprotokolle: `SYSTEM_ADMIN`-Berechtigung erforderlich

2. **Produktionsumgebung**
   - Erwägen Sie, Entwickler-Tools in der Produktion zu deaktivieren
   - Oder beschränken Sie sie auf bestimmte IP-Adressen
   - Verwenden Sie VPN für Remote-Zugriff

3. **Sensible Daten**
   - Systemprotokolle können sensible Benutzerdaten enthalten
   - Alte/neue Werte können Passwörter, E-Mails, persönliche Informationen enthalten
   - Stellen Sie sicher, dass ordnungsgemäße Zugriffskontrollen vorhanden sind
   - Erwägen Sie Datenmaskierung für sensible Felder

### Best Practices

1. **Zugriff beschränken**
   - Gewähren Sie Entwickler-/Admin-Berechtigungen nur vertrauenswürdigen Benutzern
   - Überprüfen Sie regelmäßig, wer Zugriff hat
   - Entfernen Sie den Zugriff, wenn Mitarbeiter ausscheiden

2. **Nutzung überwachen**
   - Protokollieren Sie, wer auf Entwickler-Tools zugreift
   - Verfolgen Sie, welche Aktionen durchgeführt werden
   - Warnen Sie bei verdächtiger Aktivität

3. **Datenschutz**
   - Verschlüsseln Sie Systemprotokolle im Ruhezustand
   - Verwenden Sie HTTPS für alle Kommunikationen
   - Implementieren Sie ordnungsgemäße JWT-Token-Sicherheit

4. **Regelmäßige Überprüfungen**
   - Überprüfen Sie Systemprotokolle regelmäßig
   - Prüfen Sie auf unbefugten Zugriff
   - Überprüfen Sie Konfigurationsänderungen

## Fehlerbehebung

### Desktop-Debug-Probleme

**Problem:** Desktop-Modus wird nicht aktiviert
**Lösung:** Überprüfen Sie URL-Parameter, überprüfen Sie, ob UI-Store funktioniert

**Problem:** CSS-Klassen werden nicht angewendet
**Lösung:** Überprüfen Sie die Browser-Konsole auf Fehler, überprüfen Sie, ob CSS-Dateien geladen wurden

### Socket-Debug-Probleme

**Problem:** Socket-Debug-Seite ist leer
**Lösung:** Überprüfen Sie, ob das Socket-Diagnose-Plugin initialisiert ist

**Problem:** Tests laufen immer in Zeitüberschreitung
**Lösung:** Überprüfen Sie, ob der Socket-Server läuft und zugänglich ist

**Problem:** Token wird als "nicht gefunden" angezeigt
**Lösung:** Melden Sie sich ab und wieder an, um ein neues JWT-Token zu erhalten

### Systemprotokoll-Probleme

**Problem:** Protokolle werden nicht angezeigt
**Lösung:** Überprüfen Sie `SYSTEM_ADMIN`-Berechtigung, überprüfen Sie Datenbank-Trigger

**Problem:** Statistiken werden nicht geladen
**Lösung:** Überprüfen Sie die Browser-Konsole, überprüfen Sie, ob Backend-API antwortet

**Problem:** Export schlägt fehl
**Lösung:** Überprüfen Sie Browser-Popup-Blocker, überprüfen Sie Dateiberechtigungen

## Verwandte Dokumentation

- [Authentifizierung](./authentication.md) - JWT-Token und Session-Management
- [WebSocket-Leitfaden](./websocket.md) - Echtzeit-Funktionen-Architektur
- [Berechtigungen](./permissions.md) - Rollenbasierte Zugriffskontrolle
- [Mehrmieter](./multi-tenant.md) - Behördenisolationssystem

## API-Referenz

### Systemprotokoll-Endpunkte

```http
GET /admin/logs?action=getLogEntries
Parameter:
  - page: number
  - limit: number
  - start_date: YYYY-MM-DD
  - end_date: YYYY-MM-DD
  - action: INSERT|UPDATE|DELETE|LOGIN
  - table_name: string
  - authority_id: number
  - search: string

GET /admin/logs?action=getLogStats
Parameter:
  - startDate: YYYY-MM-DD
  - endDate: YYYY-MM-DD
  - searchTerm: string
  - authorityId: number

POST /admin/logs
Body:
  - action: "clearLogs"
  - mode: "all" | "date"
  - beforeDate: YYYY-MM-DD (wenn mode=date)

GET /admin/logs?action=getLogEntries&format=csv
(Gibt CSV-Datei-Download zurück)
```

## Entwicklerhinweise

### Hinzufügen von Socket-Namespaces

Beim Hinzufügen neuer Socket-Namespaces:

1. Fügen Sie den Namespace zu `/socket-server/index.js` hinzu
2. Initialisieren Sie in `/frontend/src/plugins/socket.ts`
3. Registrieren Sie im Diagnose-Tool in `/frontend/src/plugins/socket-diagnostic.ts`
4. Socket Debug erkennt und überwacht ihn automatisch

### Benutzerdefinierte Protokollfelder

Um zusätzliche Informationen in Systemprotokollen zu verfolgen:

1. Fügen Sie Spalten zur Tabelle `system_logs` hinzu
2. Aktualisieren Sie Datenbank-Trigger, um neue Felder zu füllen
3. Ändern Sie `/backend/admin/logs/index.php`, um neue Felder zurückzugeben
4. Aktualisieren Sie Frontend-Komponenten, um neue Felder anzuzeigen

---

**Zuletzt aktualisiert:** 2025-10-01
**Version:** 1.0.0
