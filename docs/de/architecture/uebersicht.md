# Architektur-Übersicht

K-Systems basiert auf einer modernen dreistufigen Architektur bestehend aus einem Vue.js-Frontend, einer PHP-Backend-API und einem Node.js-Echtzeit-Server.

## Systemarchitektur-Diagramm

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend                             │
│  Vue 3 + TypeScript + Vuetify (Port 5173/80)               │
│  - Desktop Mode Interface                                    │
│  - Window Management System                                  │
│  - 76+ Vue Components                                        │
│  - 5 Pinia Stores                                           │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ HTTPS/WSS
                 │
    ┌────────────┴────────────┬───────────────────────┐
    │                         │                       │
    ▼                         ▼                       ▼
┌─────────┐          ┌──────────────┐      ┌──────────────┐
│ Backend │          │ Socket Server│      │   Database   │
│   PHP   │◄────────►│   Node.js    │◄────►│   MariaDB    │
│ (8080)  │          │   (3001)     │      │   (3306)     │
│         │          │              │      │              │
│ 50+ API │          │ Real-time    │      │ 104 Tables   │
│Endpoints│          │ WebSocket    │      │ Multi-tenant │
└─────────┘          └──────────────┘      └──────────────┘
     │                      │
     └──────────┬───────────┘
                │
                ▼
         ┌─────────────┐
         │   Logging   │
         │   System    │
         └─────────────┘
```

## Kernkomponenten

### Frontend-Schicht

**Technologie-Stack:**
- Vue 3.5 mit Composition API
- TypeScript 5.8
- Vuetify 3.8 (Material Design)
- Pinia 3.0 (State Management)
- Socket.io Client 4.8

**Hauptfunktionen:**
- **Desktop-Interface**: Windows-ähnliche Benutzeroberfläche mit verschiebbaren Fenstern
- **Komponentenbibliothek**: 76+ wiederverwendbare Vue-Komponenten
- **State Management**: 5 Pinia-Stores für den Anwendungszustand
- **Echtzeit-Updates**: Socket.io-Integration
- **Routing**: 91 Routen mit Guards und Berechtigungen
- **i18n**: Mehrsprachige Unterstützung (Englisch, Deutsch)

**Verzeichnisstruktur:**
```
frontend/src/
├── components/      # 76 Vue-Komponenten
├── views/           # 56 Routen-Views
├── stores/          # 5 Pinia-Stores
├── composables/     # Wiederverwendbare Composition-Funktionen
├── services/        # API-Service-Schicht
├── router/          # Vue-Router-Konfiguration
└── types/           # TypeScript-Typdefinitionen
```

[Mehr über die Frontend-Architektur erfahren →](/architecture/frontend/vue-architecture)

### Backend-Schicht

**Technologie-Stack:**
- PHP 8.x
- MariaDB 10.11
- PDO für Datenbankzugriff
- JWT-Authentifizierung (Firebase PHP-JWT)
- Composer für Abhängigkeiten

**Hauptfunktionen:**
- **RESTful API**: 50+ Endpunkte
- **JWT-Authentifizierung**: Sichere Token-basierte Authentifizierung
- **Multi-Tenant**: Vollständige Datenisolierung
- **Berechtigungssystem**: Rollenbasierte Zugriffskontrolle
- **Datenbankabstraktion**: PDO mit Prepared Statements

**Verzeichnisstruktur:**
```
backend/
├── admin/              # Admin-Endpunkte
├── login/              # Authentifizierung
├── employee/           # Mitarbeiterverwaltung
├── report/             # Berichtssystem
├── document/           # Dokumentenverwaltung
├── message/            # Nachrichtensystem
├── calendar/           # Kalender/Termine
├── utils/              # Hilfsfunktionen
├── db.php              # Datenbankverbindung
├── jwt.php             # JWT-Funktionen
├── auth_check.php      # Auth-Middleware
└── bootstrap.php       # Anwendungsinitialisierung
```

[Mehr über die Backend-Architektur erfahren →](/architecture/backend/php-api)

### Socket-Server-Schicht

**Technologie-Stack:**
- Node.js
- Express 4.18
- Socket.io 4.7
- Winston 3.17 (Logging)
- JWT für Authentifizierung

**Hauptfunktionen:**
- **Echtzeit-Kommunikation**: WebSocket-Verbindungen
- **Mehrere Namespaces**: Chat, Benachrichtigungen, Status
- **Authentifizierung**: JWT-basierte Socket-Authentifizierung
- **Broadcasting**: Benutzerspezifisches und globales Broadcasting
- **Logging**: Umfassendes Winston-Logging

**Namespaces:**
- `/notification` - Systembenachrichtigungen und Toasts
- `/chat` - Echtzeit-Messaging
- `/status` - Benutzer-Online/Offline-Status
- `/dispatch` - Einsatzoperationen
- `/whiteboard` - Kollaboratives Whiteboard

[Mehr über den Socket-Server erfahren →](/architecture/socket/realtime)

### Datenbankschicht

**Datenbank:** MariaDB 10.11

**Schema:**
- 104+ Tabellen mit `kdd_`-Präfix
- Multi-Tenant mit `authority_id`-Spalte
- Foreign-Key-Constraints für referenzielle Integrität
- Indizes für Performance

**Wichtige Tabellen:**
- **Benutzer & Auth**: kdd_users, kdd_roles, kdd_permissions
- **Multi-Tenant**: kdd_authorities, kdd_authority_features
- **Mitarbeiter**: kdd_employee, kdd_employee_department
- **Berichte**: kdd_reports, kdd_report_custom_fields
- **Dokumente**: kdd_doc_documents, kdd_doc_areas
- **Nachrichten**: kdd_messages, kdd_message_groups
- **Kalender**: kdd_calendar, kdd_calendar_assigned

[Mehr über das Datenbankschema erfahren →](/architecture/backend/database)

## Kommunikationsfluss

### Request-Flow

#### Standard-API-Request
```
1. Benutzer interagiert mit der Benutzeroberfläche
   ↓
2. Vue-Komponente ruft API-Service auf
   ↓
3. Axios sendet HTTP-Request mit JWT-Token
   ↓
4. Backend validiert JWT (auth_check.php)
   ↓
5. Backend prüft Berechtigungen
   ↓
6. Backend fragt Datenbank ab (mit authority_id)
   ↓
7. Backend gibt JSON-Response zurück
   ↓
8. Frontend aktualisiert Benutzeroberfläche
```

#### Echtzeit-Socket-Event
```
1. Benutzer führt Aktion aus
   ↓
2. Backend löst Socket-Benachrichtigung aus
   ↓
3. Socket-Server empfängt Anfrage
   ↓
4. Socket-Server validiert API-Key
   ↓
5. Socket-Server sendet Broadcast an Benutzerraum
   ↓
6. Frontend empfängt Socket-Event
   ↓
7. Frontend aktualisiert Benutzeroberfläche in Echtzeit
```

## Datenfluss

### Authentifizierungsfluss

```
1. Benutzer gibt Anmeldedaten ein
   ↓
2. POST /login mit authority_id
   ↓
3. Backend validiert Passwort (bcrypt)
   ↓
4. Backend lädt Rollen & Berechtigungen
   ↓
5. Backend erstellt JWT-Token
   ↓
6. Backend setzt HttpOnly-Cookie
   ↓
7. Frontend speichert Token in localStorage
   ↓
8. Frontend leitet zum Desktop weiter
```

### Multi-Tenant-Datenisolierung

```
Jede Datenbankabfrage enthält authority_id:

SELECT * FROM kdd_employee
WHERE authority_id = ? AND id = ?

Dies stellt sicher:
- Vollständige Datenisolierung
- Keine mandantenübergreifenden Datenlecks
- Mandantenspezifische Abfragen
```

## Sicherheitsarchitektur

### Authentifizierung
- **JWT-Tokens**: HS256-signierte Tokens
- **Cookie-Speicherung**: HttpOnly, Secure, SameSite
- **Token-Ablauf**: 24 Stunden (30 Tage mit "Angemeldet bleiben")
- **Automatische Aktualisierung**: Token-Refresh bei Aktivität

### Autorisierung
- **Rollenbasiert**: Benutzern werden Rollen zugewiesen
- **Berechtigungsbasiert**: Feinkörnige Berechtigungen
- **Feature-Flags**: Modul-Aktivierung/-Deaktivierung pro Mandant
- **Dynamische Prüfungen**: Frontend- und Backend-Validierung

### Datensicherheit
- **Multi-Tenant-Isolierung**: authority_id in allen Abfragen
- **Prepared Statements**: SQL-Injection-Prävention
- **Eingabevalidierung**: Backend-Validierung
- **XSS-Schutz**: Vue-Template-Escaping
- **CORS-Konfiguration**: Kontrollierte Origins

[Mehr über Sicherheit erfahren →](/architecture/security/multi-tenant)

## Skalierbarkeitsüberlegungen

### Horizontale Skalierung
- **Frontend**: Zustandslos, kann mehrere Instanzen hinter Load-Balancer ausführen
- **Backend**: Zustandslose API, horizontal skalierbar
- **Socket-Server**: Kann Redis-Adapter für Multi-Instanz verwenden
- **Datenbank**: Master-Slave-Replikation möglich

### Performance-Optimierung
- **Frontend**:
  - Code-Splitting mit Vite
  - Lazy Loading von Routen
  - Komponenten-Level-Caching
  - Virtual Scrolling für große Listen

- **Backend**:
  - Datenbankabfrage-Optimierung
  - Richtige Indizierung
  - Connection Pooling
  - Response-Caching (zukünftig)

- **Socket-Server**:
  - Raumbasiertes Broadcasting
  - Event-Drosselung
  - Verbindungslimits
  - Speicherverwaltung

## Technologieentscheidungen

### Warum Vue 3?
- Moderne Composition API
- Hervorragende TypeScript-Unterstützung
- Großartiges Ökosystem (Vuetify, Pinia)
- Kleine Bundle-Größe
- Schnelles Virtual DOM

### Warum PHP?
- Ausgereift und stabil
- Hervorragende Datenbankunterstützung
- Weite Hosting-Verfügbarkeit
- Gute Performance mit PHP 8
- Große Entwickler-Community

### Warum Socket.io?
- Zuverlässige WebSocket-Bibliothek
- Automatischer Fallback auf Polling
- Room- und Namespace-Unterstützung
- Broadcasting-Fähigkeiten
- Gute Dokumentation

### Warum MariaDB?
- MySQL-Kompatibilität
- Bessere Performance
- Open-Source
- Aktive Entwicklung
- JSON-Unterstützung

## Entwicklung vs. Produktion

### Entwicklungsumgebung
- **Frontend**: Vite-Dev-Server (Port 5173)
- **Backend**: PHP-Built-in-Server oder Apache
- **Socket**: Nodemon mit Auto-Restart
- **Datenbank**: Docker-Container
- **Hot Reload**: Sofortige Updates

### Produktionsumgebung
- **Frontend**: Nginx-Server für statische Dateien
- **Backend**: Apache/PHP-FPM
- **Socket**: PM2-Prozessmanager
- **Datenbank**: Verwaltete MariaDB-Instanz
- **SSL**: Let's Encrypt-Zertifikate
- **Reverse Proxy**: Traefik oder Nginx

## Nächste Schritte

Vertiefen Sie sich in spezifische Architekturkomponenten:

- [Frontend-Architektur](/architecture/frontend/vue-architecture)
- [Backend-Architektur](/architecture/backend/php-api)
- [Socket-Server](/architecture/socket/realtime)
- [Datenbankschema](/architecture/backend/database)
- [Sicherheit](/architecture/security/multi-tenant)
- [Technologie-Stack](/architecture/tech-stack)

---

::: tip Die Architektur verstehen
Ein solides Verständnis der Architektur hilft Ihnen, effektiv zum Projekt beizutragen und fundierte Entscheidungen bei der Erweiterung der Funktionalität zu treffen.
:::
