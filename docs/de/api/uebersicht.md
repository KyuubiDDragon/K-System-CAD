# API Übersicht

K-Systems bietet eine umfassende RESTful API für alle Systemfunktionalitäten.

## Basis-URLs

**Produktion:**
```
https://your-domain.com/api
```

**Entwicklung:**
```
http://localhost:8080
```

**Socket Server:**
```
ws://localhost:3001 (dev)
wss://your-domain.com (prod)
```

## Authentifizierung

Alle API-Anfragen (außer Login) erfordern eine Authentifizierung über JWT Token.

### Token-Speicherort

Das JWT Token kann auf zwei Arten bereitgestellt werden:

**1. Cookie (Empfohlen)**
```http
Cookie: auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**2. Authorization Header (FiveM Kompatibilität)**
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Token erhalten

```http
POST /login/?action=login
Content-Type: application/json

{
  "username": "john.doe",
  "password": "password123",
  "authority_id": 1,
  "remember_me": false
}
```

**Antwort:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 123,
    "username": "john.doe",
    "authority_id": 1,
    "roles": ["Admin"],
    "permissions": ["READ_EMPLOYEE", "WRITE_EMPLOYEE"]
  }
}
```

Das Token wird automatisch als HttpOnly Cookie gesetzt.

## Anfrage-Format

### Headers

```http
Content-Type: application/json
Cookie: auth_token=YOUR_JWT_TOKEN
```

Oder mit Authorization Header:

```http
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

### Body

Alle POST- und PUT-Anfragen akzeptieren JSON:

```json
{
  "field1": "value1",
  "field2": "value2"
}
```

## Antwort-Format

### Erfolgreiche Antwort

```json
{
  "success": true,
  "data": {
    // Antwortdaten
  },
  "message": "Operation successful"
}
```

### Fehler-Antwort

```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

### HTTP-Statuscodes

- **200 OK**: Erfolgreiche Anfrage
- **201 Created**: Ressource erstellt
- **400 Bad Request**: Ungültige Anfragedaten
- **401 Unauthorized**: Fehlendes oder ungültiges Token
- **403 Forbidden**: Unzureichende Berechtigungen
- **404 Not Found**: Ressource nicht gefunden
- **500 Internal Server Error**: Serverfehler

## Kern-Endpunkte

### Employee API

```http
GET    /employee/              # Mitarbeiter auflisten
GET    /employee/{id}          # Einzelnen Mitarbeiter abrufen
POST   /employee/              # Mitarbeiter erstellen
PUT    /employee/{id}          # Mitarbeiter aktualisieren
DELETE /employee/{id}          # Mitarbeiter löschen
```

[Vollständige Employee API Dokumentation →](/api/employee)

### Report API

```http
GET    /report/                # Berichte auflisten
GET    /report/{id}            # Einzelnen Bericht abrufen
POST   /report/                # Bericht erstellen
PUT    /report/{id}            # Bericht aktualisieren
DELETE /report/{id}            # Bericht löschen
POST   /report/share.php       # Teilbaren Link erstellen
```

[Vollständige Report API Dokumentation →](/api/report)

### Document API

```http
GET    /document/?action=getDocuments      # Dokumente auflisten
GET    /document/?action=getDocument       # Einzelnes Dokument abrufen
POST   /document/?action=createDocument    # Dokument erstellen
PUT    /document/?action=updateDocument    # Dokument aktualisieren
DELETE /document/?action=deleteDocument    # Dokument löschen
GET    /document/?action=getAreas          # Dokumentbereiche auflisten
```

[Vollständige Document API Dokumentation →](/api/document)

### Message API

```http
GET    /message/?action=getMessages        # Nachrichten auflisten
GET    /message/?action=getConversation    # Konversation abrufen
POST   /message/?action=sendMessage        # Nachricht senden
PUT    /message/?action=markRead           # Als gelesen markieren
POST   /message/?action=createGroup        # Gruppe erstellen
DELETE /message/?action=deleteMessage      # Nachricht löschen
```

[Vollständige Message API Dokumentation →](/api/message)

### Calendar API

```http
GET    /calendar/?action=getEvents         # Termine auflisten
GET    /calendar/?action=getEvent          # Einzelnen Termin abrufen
POST   /calendar/?action=createEvent       # Termin erstellen
PUT    /calendar/?action=updateEvent       # Termin aktualisieren
DELETE /calendar/?action=deleteEvent       # Termin löschen
GET    /calendar/?action=getGroups         # Kalendergruppen auflisten
```

[Vollständige Calendar API Dokumentation →](/api/calendar)

## Admin-Endpunkte

### Benutzerverwaltung

```http
GET    /admin/user/            # Benutzer auflisten
GET    /admin/user/{id}        # Benutzer abrufen
POST   /admin/user/            # Benutzer erstellen
PUT    /admin/user/{id}        # Benutzer aktualisieren
DELETE /admin/user/{id}        # Benutzer löschen
```

[Vollständige User API Dokumentation →](/api/admin/user)

### Rollenverwaltung

```http
GET    /admin/roles/                     # Rollen auflisten
GET    /admin/roles/{id}                 # Rolle abrufen
POST   /admin/roles/                     # Rolle erstellen
PUT    /admin/roles/{id}                 # Rolle aktualisieren
DELETE /admin/roles/{id}                 # Rolle löschen
GET    /admin/roles/{id}/permissions     # Rollenberechtigungen abrufen
POST   /admin/roles/{id}/permissions     # Rollenberechtigungen aktualisieren
```

[Vollständige Role API Dokumentation →](/api/admin/role)

### Authority-Verwaltung

```http
GET    /admin/authority/                 # Authorities auflisten
GET    /admin/authority/{id}             # Authority abrufen
POST   /admin/authority/                 # Authority erstellen
PUT    /admin/authority/{id}             # Authority aktualisieren
DELETE /admin/authority/{id}             # Authority löschen
GET    /admin/authority/{id}/features    # Authority-Funktionen abrufen
POST   /admin/authority/{id}/features    # Funktionen aktualisieren
```

[Vollständige Authority API Dokumentation →](/api/admin/authority)

## Socket API

### HTTP-Endpunkte

```http
POST /api/notification/toast      # Toast-Benachrichtigung senden
POST /api/notification            # Benachrichtigung senden
POST /api/broadcast               # An alle Benutzer senden
GET  /api/diagnostic              # Systemdiagnose
GET  /health                      # Gesundheitsprüfung
```

**Authentifizierung:** API Key im Header
```http
X-API-Key: your_socket_api_key
```

[Vollständige Socket API Dokumentation →](/api/socket/notifications)

### WebSocket Events

**Verbindung:**
```javascript
import { io } from 'socket.io-client';

const socket = io('/notification', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**
- `notification:new` - Neue Benachrichtigung
- `notification:toast` - Toast-Nachricht
- `chat:message` - Chat-Nachricht
- `status:update` - Statusänderung

[Vollständige Socket Events Dokumentation →](/api/socket/notifications)

## Berechtigungen

Die meisten Endpunkte erfordern spezifische Berechtigungen:

| Berechtigung | Beschreibung |
|-----------|-------------|
| `READ_EMPLOYEE` | Mitarbeiter anzeigen |
| `WRITE_EMPLOYEE` | Mitarbeiter erstellen/aktualisieren |
| `DELETE_EMPLOYEE` | Mitarbeiter löschen |
| `READ_REPORT` | Berichte anzeigen |
| `WRITE_REPORT` | Berichte erstellen/aktualisieren |
| `DELETE_REPORT` | Berichte löschen |
| `ADMIN_USER` | Benutzerverwaltung |
| `ADMIN_ROLE` | Rollenverwaltung |
| `ALL_PERMISSIONS` | Super-Admin-Zugriff |

### Berechtigungen prüfen

Das Backend prüft automatisch Berechtigungen mittels `auth_check.php`:

```php
// Backend validiert automatisch
if (!hasPermission($pdo, $userId, $authorityId, 'READ_EMPLOYEE')) {
    http_response_code(403);
    exit();
}
```

## Rate Limiting

Derzeit ist kein Rate Limiting implementiert. Zukünftige Versionen können enthalten:
- Benutzerbezogene Rate Limits
- Endpunktbezogene Limits
- IP-basierte Limits

## Pagination

Listen-Endpunkte unterstützen Pagination:

```http
GET /employee/?page=1&limit=25&search=john
```

**Parameter:**
- `page` - Seitennummer (Standard: 1)
- `limit` - Einträge pro Seite (Standard: 25)
- `search` - Suchanfrage (optional)

**Antwort:**
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 250,
    "per_page": 25
  }
}
```

## Fehlerbehandlung

### Häufige Fehler

**401 Unauthorized**
```json
{
  "error": "Invalid or expired token"
}
```

**403 Forbidden**
```json
{
  "error": "Permission denied",
  "required_permission": "WRITE_EMPLOYEE"
}
```

**400 Bad Request**
```json
{
  "error": "Validation failed",
  "details": {
    "name": "Name is required",
    "email": "Invalid email format"
  }
}
```

## API testen

### Mit cURL

```bash
# Login
curl -X POST http://localhost:8080/login/?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password","authority_id":1}' \
  -c cookies.txt

# Mitarbeiter auflisten (mit gespeicherten Cookies)
curl http://localhost:8080/employee/ \
  -b cookies.txt

# Mitarbeiter erstellen
curl -X POST http://localhost:8080/employee/ \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"firstname":"John","lastname":"Doe"}'
```

### Mit Postman

1. Importieren Sie die K-Systems Collection
2. Setzen Sie Umgebungsvariablen:
   - `BASE_URL`: http://localhost:8080
   - `TOKEN`: Ihr JWT Token
3. Verwenden Sie {{BASE_URL}} in Anfragen
4. Token wird automatisch in Anfragen eingefügt

### Mit JavaScript

```javascript
// Mit Axios
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  withCredentials: true
});

// Login
await api.post('/login/?action=login', {
  username: 'admin',
  password: 'password',
  authority_id: 1
});

// Mitarbeiter auflisten
const { data } = await api.get('/employee/');
console.log(data);
```

## API-Versionierung

Derzeit verwendet K-Systems eine einzelne API-Version. Zukünftige Versionen können enthalten:
- `/api/v1/` - Version 1
- `/api/v2/` - Version 2

## Nächste Schritte

Erkunden Sie die detaillierte Dokumentation für jede API:

- [Employee API](/api/employee)
- [Report API](/api/report)
- [Document API](/api/document)
- [Message API](/api/message)
- [Calendar API](/api/calendar)
- [Admin APIs](/api/admin/user)
- [Socket API](/api/socket/notifications)

---

::: tip API-Tests
Verwenden Sie den Network-Tab des Browsers, um tatsächliche API-Anfragen des Frontends zu sehen!
:::
