# Systemkomponenten

Dieses Dokument bietet einen umfassenden Überblick über die K-Systems-Architektur und ihre Hauptkomponenten.

## Systemarchitektur-Übersicht

K-Systems ist eine moderne, mandantenfähige Enterprise-Management-Plattform, die mit einer dreistufigen Architektur aufgebaut ist:

```
┌─────────────────┐
│   Frontend      │  Vue 3 + Vuetify 3
│   (Port 5173)   │  TypeScript + Pinia
└────────┬────────┘
         │ HTTP/WebSocket
┌────────┴────────┐
│   Backend API   │  PHP 8.4 + Apache
│   (Port 8080)   │  JWT Authentication
└────────┬────────┘
         │ PDO
┌────────┴────────┐
│   Database      │  MariaDB/MySQL
│   (Port 3306)   │  Multi-tenant Schema
└─────────────────┘
         │
┌────────┴────────┐
│ Socket Server   │  Node.js + Socket.io
│   (Port 3001)   │  Real-time Events
└─────────────────┘
```

## Frontend-Architektur

### Technologie-Stack

- **Framework**: Vue 3 mit Composition API
- **UI-Bibliothek**: Vuetify 3 (Material Design)
- **Sprache**: TypeScript
- **State Management**: Pinia-Stores
- **Build-Tool**: Vite
- **Routing**: Vue Router 4
- **Internationalisierung**: Vue I18n

### Frontend-Struktur

```
frontend/
├── public/
│   ├── img/               # Statische Bilder einschließlich bg.jpg
│   └── locales/           # Übersetzungsdateien
├── src/
│   ├── api.ts            # Zentralisierter API-Client
│   ├── router.ts         # Anwendungsrouting
│   ├── main.ts           # Anwendungseinstiegspunkt
│   ├── App.vue           # Root-Komponente
│   ├── components/       # Wiederverwendbare Vue-Komponenten
│   ├── views/            # Funktionsbasierte Views
│   ├── stores/           # Pinia-State-Stores
│   ├── composables/      # Composition-API-Helper
│   ├── locales/          # i18n-Übersetzungen
│   ├── types/            # TypeScript-Typdefinitionen
│   └── utils/            # Hilfsfunktionen
└── package.json
```

### Wichtige Frontend-Komponenten

#### Desktop-Modus

K-Systems verfügt über eine Windows-ähnliche Desktop-Oberfläche:

- **Startmenü**: Anwendungsstarter mit kategorisierten Apps
- **Taskleiste**: Schnellzugriff auf laufende Anwendungen
- **Desktop**: Symbolbasierte Verknüpfungen zu Anwendungen
- **Fensterverwaltung**: Mehrere veränderbare, verschiebbare Fenster
- **Fenstersteuerung**: Minimieren-, Maximieren-, Schließen-Schaltflächen

#### State Management (Pinia-Stores)

```typescript
// stores/auth.ts
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    permissions: []
  }),
  actions: {
    async login(credentials) { ... },
    logout() { ... }
  }
})
```

Häufige Stores:
- `authStore`: Authentifizierung und Benutzerstatus
- `employeeStore`: Mitarbeiterdatenverwaltung
- `reportStore`: Berichtsdaten und -operationen
- `messageStore`: Nachrichtenstatus
- `calendarStore`: Kalenderereignisse
- `notificationStore`: Systembenachrichtigungen

#### API-Schicht

Zentralisierter API-Client in `/src/api.ts`:

```typescript
const API_URL = import.meta.env.VITE_API_URL

export const api = {
  async get(endpoint: string) {
    const response = await fetch(`${API_URL}${endpoint}`, {
      credentials: 'include',
      headers: { 'Authorization': `Bearer ${token}` }
    })
    return response.json()
  },
  // ... post, put, delete Methoden
}
```

#### Berechtigungssystem

Verwendet das `usePermissionCheck`-Composable:

```typescript
import { usePermissionCheck } from '@/composables/usePermissionCheck'

const { hasPermission } = usePermissionCheck()

if (hasPermission('WRITE_REPORT')) {
  // Bearbeitungs-Button anzeigen
}
```

### Styling und Themes

- **Standard-Theme**: Benutzerdefinierte Vuetify-Theme-Konfiguration
- **Desktop-Hintergrund**: `/public/img/bg.jpg`
- **Responsive Design**: Mobile-freundliche Layouts
- **Dark Mode**: Theme-Toggle-Unterstützung

## Backend-Architektur

### Technologie-Stack

- **Sprache**: PHP 8.4
- **Webserver**: Apache mit mod_php
- **Datenbankzugriff**: PDO (PHP Data Objects)
- **Authentifizierung**: JWT (JSON Web Tokens)
- **Session-Verwaltung**: Cookie-basierte Auth-Tokens

### Backend-Struktur

```
backend/
├── bootstrap.php         # Kerninitialisierung
├── db.php               # Datenbankverbindung
├── auth_check.php       # JWT-Validierung
├── login/               # Authentifizierungs-Endpunkte
├── employee/            # Mitarbeiter-Modul
├── report/              # Berichts-Modul
├── document/            # Dokument-Modul
├── message/             # Nachrichten-Modul
├── calendar/            # Kalender-Modul
├── invoice/             # Rechnungs-Modul
├── training/            # Schulungs-Modul
├── dispatch/            # Einsatz-Modul
├── todo/                # Todo-Modul
├── admin/               # Admin-Endpunkte
│   ├── user/           # Benutzerverwaltung
│   ├── roles/          # Rollenverwaltung
│   └── settings/       # Systemeinstellungen
├── utils/               # Hilfsfunktionen
│   ├── permission_helper.php
│   └── authority_helper.php
├── logging/             # Audit-Logging
└── uploads/             # Datei-Uploads (mandantenspezifisch)
```

### Request-Flow

1. **Client-Request**: Frontend sendet HTTP-Request mit JWT-Cookie
2. **Bootstrap**: `bootstrap.php` initialisiert Umgebung
3. **Datenbankverbindung**: `db.php` stellt PDO-Verbindung her
4. **Authentifizierung**: `auth_check.php` validiert JWT-Token
5. **Authority-Validierung**: Prüfung des Authority-Kontexts des Benutzers
6. **Berechtigungsprüfung**: Überprüfung, ob Benutzer erforderliche Berechtigungen hat
7. **Action-Routing**: Ausführung der angeforderten Aktion
8. **Response**: Rückgabe der JSON-Response

### Modul-Pattern

Jedes Backend-Modul folgt dieser Struktur:

```php
<?php
// Modul: backend/example/index.php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_check.php';

$userId = $decoded_jwt->userId ?? null;
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

$action = $_REQUEST['action'] ?? '';

// Berechtigungs-Mapping
$permissions_map = [
    'getData' => 'READ_EXAMPLE',
    'saveData' => 'WRITE_EXAMPLE'
];

// Berechtigungsprüfung
require_once __DIR__ . '/../utils/permission_helper.php';
if (!hasPermission($userPermissions, $permissions_map[$action])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

// Aktion ausführen
switch ($action) {
    case 'getData': getData($pdo, $authority); break;
    case 'saveData': saveData($pdo, $userId, $authority); break;
}
```

### Authentifizierungssystem

#### JWT-Token-Struktur

```json
{
  "userId": 42,
  "username": "john.doe",
  "authority": "authority1",
  "authority_id": 1,
  "permissions": ["READ_REPORT", "WRITE_REPORT"],
  "roles": ["Firefighter", "Admin"],
  "iat": 1706745600,
  "exp": 1706832000
}
```

#### Login-Flow

1. Benutzer übermittelt Anmeldedaten an `/backend/login/`
2. Backend validiert gegen Datenbank
3. JWT-Token mit Benutzerkontext generieren
4. Sicheres HTTP-Only-Cookie setzen
5. Benutzerinformationen an Frontend zurückgeben

#### Authority-Kontext

Jede Anfrage enthält `authority_id` für Multi-Tenant-Isolierung:

```php
// Authority validieren
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid authority context"]);
    exit();
}

// Alle Abfragen enthalten authority_id
$stmt = $pdo->prepare("SELECT * FROM employees WHERE authority_id = :aid");
$stmt->bindParam(':aid', $authorityId);
```

## Datenbankarchitektur

### Datenbankplattform

- **DBMS**: MariaDB 10.11+ / MySQL 8.0+
- **Zeichensatz**: UTF-8 (utf8mb4)
- **Engine**: InnoDB für Transaktionen und Foreign Keys

### Tabellennamen-Konvention

Alle Tabellen verwenden das `kdd_`-Präfix:
- `kdd_employees`
- `kdd_reports`
- `kdd_documents`
- `kdd_users`
- etc.

### Multi-Tenant-Schema

Jede Tabelle enthält eine `authority_id`-Spalte:

```sql
CREATE TABLE kdd_example (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

### Kerntabellen

#### Authority-Verwaltung
- `kdd_authorities`: Organisations-/Mandanten-Definitionen
- `kdd_authority_features`: Verfügbare Features pro Authority
- `kdd_authority_feature_access`: Authority-Feature-Zuweisungen

#### Benutzerverwaltung
- `kdd_users`: Benutzerkonten
- `kdd_roles`: Rollendefinitionen
- `kdd_user_roles`: Benutzer-Rollen-Zuweisungen
- `kdd_permissions`: Verfügbare Berechtigungen
- `kdd_role_permissions`: Rollen-Berechtigungs-Zuweisungen

#### Mitarbeiterverwaltung
- `kdd_employees`: Mitarbeiterdatensätze
- `kdd_departments`: Abteilungsstruktur
- `kdd_ranks`: Mitarbeiterränge/-positionen
- `kdd_employee_training`: Schulungszuweisungen
- `kdd_employee_certifications`: Zertifizierungen

#### Operationen
- `kdd_reports`: Einsatzberichte
- `kdd_report_custom_fields`: Benutzerdefinierte Berichtsfelder
- `kdd_documents`: Dokument-Metadaten
- `kdd_document_areas`: Dokumentenkategorisierung
- `kdd_crews`: Einsatzeinheiten
- `kdd_crew_employees`: Crew-Zuweisungen
- `kdd_crew_vehicles`: Fahrzeugzuweisungen

#### Kommunikation
- `kdd_messages`: Nachrichtensystem
- `kdd_message_groups`: Gruppenkonversationen
- `kdd_calendar_events`: Kalendereinträge
- `kdd_calendar_invites`: Event-RSVPs

#### Geschäftlich
- `kdd_companies`: Firmenverzeichnis
- `kdd_invoices`: Rechnungsdatensätze
- `kdd_invoice_entries`: Rechnungspositionen
- `kdd_applicants`: Bewerbungen

### Indizes und Performance

Wichtige Indizes für Performance:
- Primary Keys auf allen Tabellen
- Foreign-Key-Indizes für Beziehungen
- `authority_id`-Indizes für Multi-Tenant-Abfragen
- Zusammengesetzte Indizes auf häufig abgefragten Spalten
- Volltext-Indizes auf durchsuchbaren Inhalten

## Socket-Server-Architektur

### Technologie-Stack

- **Runtime**: Node.js 20+
- **Framework**: Socket.io für WebSocket-Verbindungen
- **Authentifizierung**: JWT-Token-Validierung
- **Port**: 3001

### Socket-Server-Struktur

```
socket-server/
├── server.js            # Haupt-Server-Datei
├── handlers/            # Event-Handler
│   ├── chat.js
│   ├── notification.js
│   ├── status.js
│   └── whiteboard.js
├── middleware/          # Authentifizierungs-Middleware
└── package.json
```

### Socket-Namespaces

#### `/chat`-Namespace
- Echtzeit-Chat-Messaging
- Tipp-Indikatoren
- Lesebestätigungen
- Online-Status

#### `/notification`-Namespace
- Systembenachrichtigungen
- Alert-Broadcasts
- Event-Benachrichtigungen

#### `/status`-Namespace
- Benutzer-Online/Offline-Status
- Aktivitätsverfolgung
- Präsenzverwaltung

#### `/whiteboard`-Namespace
- Kollaboratives Zeichnen
- Echtzeit-Canvas-Synchronisation
- Multi-User-Bearbeitung

### Verbindungsfluss

```javascript
// Client-Verbindung
import io from 'socket.io-client'

const socket = io('http://localhost:3001/chat', {
  auth: {
    token: localStorage.getItem('token')
  }
})

socket.on('connect', () => {
  console.log('Connected to socket server')
})

socket.on('message', (data) => {
  console.log('New message:', data)
})
```

### Authentifizierung

Socket-Server validiert JWT-Tokens:

```javascript
io.use((socket, next) => {
  const token = socket.handshake.auth.token
  try {
    const decoded = jwt.verify(token, SECRET_KEY)
    socket.userId = decoded.userId
    socket.authority = decoded.authority
    next()
  } catch (err) {
    next(new Error('Authentication failed'))
  }
})
```

### Echtzeit-Events

Häufige Event-Muster:

```javascript
// An spezifischen Benutzer senden
io.to(`user_${userId}`).emit('notification', data)

// An Authority senden
io.to(`authority_${authorityId}`).emit('broadcast', data)

// An Raum senden
io.to(`chat_${roomId}`).emit('message', data)
```

## Deployment-Architektur

### Docker-Container

K-Systems verwendet Docker Compose für die Orchestrierung:

```yaml
services:
  frontend:
    build: ./frontend
    ports: ["5173:5173"]

  backend:
    build: ./backend
    ports: ["8080:80"]

  socket-server:
    build: ./socket-server
    ports: ["3001:3001"]

  database:
    image: mariadb:10.11
    ports: ["3306:3306"]
```

### Umgebungskonfiguration

Umgebungsvariablen werden über `.env`-Dateien verwaltet:

```bash
# Frontend (.env)
VITE_API_URL=http://localhost:8080/backend
VITE_SOCKET_URL=http://localhost:3001

# Backend (.env)
DB_HOST=localhost
DB_NAME=ksystems
DB_USER=ksystems_user
DB_PASS=secure_password
JWT_SECRET=your_secret_key
```

### Dateispeicherung

Uploads sind nach Authority organisiert:

```
uploads/
├── authority1/
│   ├── documents/
│   ├── invoices/
│   ├── trainings/
│   └── avatars/
├── authority2/
│   └── ...
```

### Logging und Monitoring

- **Anwendungslogs**: Gespeichert in `/var/log/`
- **Datenbank-Audit**: `kdd_access_logs`-Tabelle
- **Error-Logging**: PHP-Errorlogs und Node.js-Console
- **Access-Logs**: Apache-Access-Logs

## Integrationspunkte

### Frontend ↔ Backend

- HTTP-REST-API-Aufrufe
- Cookie-basierte JWT-Authentifizierung
- JSON-Request/Response-Format
- Datei-Upload/Download-Endpunkte

### Frontend ↔ Socket-Server

- WebSocket-Verbindungen
- Echtzeit-Event-Streaming
- JWT-Authentifizierung
- Namespace-basierte Kanäle

### Backend ↔ Datenbank

- PDO-Prepared-Statements
- Transaktionsunterstützung
- Connection Pooling
- Multi-Tenant-Abfragen

### Backend ↔ Socket-Server

- HTTP-API zum Auslösen von Events
- Gemeinsame Authentifizierung (JWT)
- Event-Benachrichtigungssystem

## Sicherheitsarchitektur

### Authentifizierungsschichten

1. **JWT-Validierung**: Alle Anfragen validiert
2. **Authority-Kontext**: Multi-Tenant-Isolierung
3. **Berechtigungsprüfungen**: Rollenbasierte Zugriffskontrolle
4. **Session-Verwaltung**: Sichere Cookie-Handhabung

### Datenschutz

- Passwort-Hashing mit bcrypt
- SQL-Injection-Prävention (PDO-Prepared-Statements)
- XSS-Prävention (Output-Escaping)
- CSRF-Schutz (Token-Validierung)
- Datei-Upload-Validierung

### Audit-Trail

Alle kritischen Operationen werden protokolliert:
- Benutzeraktionen
- Datenänderungen
- Berechtigungsänderungen
- Login-Versuche
- Fehlgeschlagene Zugriffsversuche

## Skalierbarkeitsüberlegungen

### Horizontale Skalierung

- Frontend: Mehrere Frontend-Server hinter Load-Balancer
- Backend: Zustandslose PHP-Server mit Session-Sharing
- Socket-Server: Socket.io mit Redis-Adapter
- Datenbank: Read-Replicas für Query-Verteilung

### Caching-Strategie

- Browser-Caching für statische Assets
- API-Response-Caching (Redis)
- Datenbank-Query-Caching
- Kompiliertes Template-Caching

### Performance-Optimierung

- Lazy Loading von Vue-Komponenten
- Datenbankabfrage-Optimierung
- CDN für statische Assets
- Gzip-Kompression
- Connection Pooling

## Entwicklungs-Workflow

### Lokale Entwicklung

```bash
# Alle Services starten
docker-compose up -d

# Frontend-Entwicklung
cd frontend
npm run dev

# Backend auf Apache
# Läuft bereits in Docker

# Socket-Server-Entwicklung
cd socket-server
npm run dev
```

### Build-Prozess

```bash
# Frontend-Produktions-Build
cd frontend
npm run build

# Backend (kein Build erforderlich)
# PHP läuft direkt

# Socket-Server
cd socket-server
npm start
```

### Testing

- Frontend: Vitest-Unit-Tests
- Backend: PHPUnit-Integrationstests
- E2E: Playwright/Cypress
- API: Postman-Collections

## Technologie-Versionen

### Frontend
- Vue: 3.4+
- Vuetify: 3.5+
- TypeScript: 5.3+
- Vite: 5.0+
- Node.js: 20+ (für Build)

### Backend
- PHP: 8.4+
- Apache: 2.4+
- Composer: 2.7+

### Datenbank
- MariaDB: 10.11+ oder MySQL: 8.0+

### Socket-Server
- Node.js: 20+
- Socket.io: 4.6+

## Systemanforderungen

### Entwicklungsumgebung
- CPU: 4+ Kerne empfohlen
- RAM: 8GB Minimum, 16GB empfohlen
- Speicher: 20GB verfügbarer Platz
- OS: Linux, macOS oder Windows mit WSL2

### Produktionsumgebung
- CPU: 8+ Kerne
- RAM: 16GB Minimum, 32GB+ empfohlen
- Speicher: SSD mit 100GB+ verfügbar
- Netzwerk: 1Gbps-Verbindung
- OS: Ubuntu Server 22.04 LTS oder ähnlich

## Monitoring und Wartung

### Health-Checks

Endpunkt-Überwachung:
- Frontend: `http://localhost:5173/`
- Backend: `http://localhost:8080/backend/health`
- Socket: WebSocket-Verbindungstest
- Datenbank: Connection-Pool-Status

### Backup-Strategie

- Datenbank: Tägliche automatisierte Backups
- Datei-Uploads: Inkrementelle Backups
- Konfiguration: Versionskontrolle (Git)
- Logs: Rotation und Archivierung

### Update-Prozess

1. Updates in Entwicklung testen
2. Datenbank-Backup erstellen
3. Backend-Updates deployen
4. Frontend-Build deployen
5. Socket-Server neu starten
6. Funktionalität überprüfen
7. Logs überwachen
