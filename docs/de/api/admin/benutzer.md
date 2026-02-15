# Benutzerverwaltung API

Die Benutzerverwaltung API bietet administrative Endpunkte zur Verwaltung von Benutzerkonten, Berechtigungen und Zugriffskontrolle.

## Base URL

```
/backend/admin/user/
```

## Authentication

Alle Endpunkte erfordern administrative JWT-Authentifizierung mit `ADMIN_READ_USERS` oder `ADMIN_WRITE_USERS` Berechtigungen.

## Erforderliche Berechtigungen

- **ADMIN_READ_USERS**: Benutzerkonten und Informationen anzeigen
- **ADMIN_WRITE_USERS**: Benutzer erstellen, aktualisieren, sperren, entsperren und Passwörter zurücksetzen

## Endpoints

### Get Users

**GET** `/backend/admin/user/?action=getUsers`

Ruft alle Benutzer in der Behörde ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "username": "john.doe",
      "email": "john.doe@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "employee_id": 100,
      "roles": ["Firefighter", "Admin"],
      "status": "active",
      "last_login": "2025-01-20 14:30:00",
      "created_at": "2024-06-15 10:00:00"
    }
  ]
}
```

### Get User Overview

**GET** `/backend/admin/user/?action=getUserOverview`

Ruft detaillierte Übersicht aller Benutzer einschließlich Aktivitätsmetriken ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "username": "john.doe",
      "email": "john.doe@example.com",
      "full_name": "John Doe",
      "employee_id": 100,
      "roles": ["Firefighter", "Admin"],
      "permissions": ["READ_REPORT", "WRITE_REPORT", "ADMIN_READ_USERS"],
      "status": "active",
      "last_login": "2025-01-20 14:30:00",
      "login_count": 245,
      "reports_created": 38,
      "messages_sent": 156,
      "created_at": "2024-06-15 10:00:00"
    }
  ]
}
```

### Get Groups

**GET** `/backend/admin/user/?action=getGroups`

Ruft alle Benutzergruppen/Rollen für Zuweisung ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "groups": [
    {
      "id": 1,
      "name": "Administrators",
      "description": "Full system access",
      "user_count": 3
    },
    {
      "id": 2,
      "name": "Firefighters",
      "description": "Standard firefighter access",
      "user_count": 45
    }
  ]
}
```

### Add New User

**POST** `/backend/admin/user/?action=addNewUser`

Erstellt ein neues Benutzerkonto.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "username": "jane.smith",
  "email": "jane.smith@example.com",
  "password": "SecurePassword123!",
  "first_name": "Jane",
  "last_name": "Smith",
  "employee_id": 101,
  "roles": [2, 5]
}
```

**Response:**
```json
{
  "success": true,
  "message": "User created successfully",
  "user_id": 43
}
```

### Update User

**POST** `/backend/admin/user/?action=updateUser`

Aktualisiert ein bestehendes Benutzerkonto.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "id": 43,
  "username": "jane.smith",
  "email": "jane.smith@example.com",
  "first_name": "Jane",
  "last_name": "Smith",
  "employee_id": 101,
  "roles": [2, 5, 8]
}
```

**Response:**
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

### Ban User

**POST** `/backend/admin/user/?action=bannUser`

Sperrt ein Benutzerkonto und verhindert Anmeldung.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43,
  "reason": "Violation of company policy",
  "duration": 30
}
```

**Response:**
```json
{
  "success": true,
  "message": "User banned successfully"
}
```

### Unban User

**POST** `/backend/admin/user/?action=unbannUser`

Entsperrt ein gesperrtes Benutzerkonto.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43
}
```

**Response:**
```json
{
  "success": true,
  "message": "User unbanned successfully"
}
```

### Reset Password

**POST** `/backend/admin/user/?action=resetPassword`

Setzt das Passwort eines Benutzers zurück.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43,
  "new_password": "NewSecurePassword456!",
  "send_email": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

### Get Users with Todo Permissions

**POST** `/backend/admin/user/?action=getUsersWithTodoPermissions`

Ruft Benutzer ab, die aufgabenbezogene Berechtigungen haben.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Request:**
```json
{
  "list_id": 5
}
```

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "can_read": true,
      "can_write": true,
      "can_delete": false
    }
  ]
}
```

## User Status Values

- `active`: Konto ist aktiv und kann sich anmelden
- `suspended`: Konto ist vorübergehend gesperrt
- `inactive`: Konto ist deaktiviert
- `pending`: Konto wartet auf Aktivierung

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Ungültige Daten oder fehlende Felder |
| 403 | Forbidden - Unzureichende Admin-Berechtigungen |
| 404 | Not found - Benutzer existiert nicht |
| 409 | Conflict - Benutzername oder E-Mail existiert bereits |
| 500 | Internal server error |

## Examples

### JavaScript

```javascript
// Get all users
async function getUsers() {
  const response = await fetch('/backend/admin/user/?action=getUsers', {
    credentials: 'include'
  });
  return await response.json();
}

// Create new user
async function createUser(userData) {
  const response = await fetch('/backend/admin/user/?action=addNewUser', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(userData)
  });
  return await response.json();
}

// Ban user
async function banUser(userId, reason) {
  const response = await fetch('/backend/admin/user/?action=bannUser', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: userId, reason })
  });
  return await response.json();
}
```

### cURL

```bash
# Get all users
curl -X GET 'http://localhost:8080/backend/admin/user/?action=getUsers' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create user
curl -X POST 'http://localhost:8080/backend/admin/user/?action=addNewUser' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "username": "new.user",
    "email": "new.user@example.com",
    "password": "SecurePass123!",
    "first_name": "New",
    "last_name": "User",
    "roles": [2]
  }'

# Ban user
curl -X POST 'http://localhost:8080/backend/admin/user/?action=bannUser' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "user_id": 43,
    "reason": "Policy violation"
  }'
```

## Best Practices

1. Verwenden Sie starke Passwortrichtlinien
2. Weisen Sie minimal notwendige Berechtigungen zu
3. Dokumentieren Sie Sperrgründe
4. Führen Sie regelmäßige Benutzerzugriffs-Audits durch
5. Verknüpfen Sie Benutzer mit Mitarbeiterdatensätzen
6. Überwachen Sie inaktive Konten

## Security Notes

- Passwörter werden mit bcrypt gehasht
- Passwort-Zurücksetzungen generieren Audit-Logs
- Benutzeränderungen werden protokolliert
- Admin-Aktionen erfordern erhöhte Berechtigungen
- Behördenisolation verhindert mandantenübergreifenden Zugriff

## Database Tables

- `users`: Benutzerkonten
- `user_roles`: Rollenzuweisungen
- `roles`: Rollendefinitionen
- `employees`: Mitarbeiterverknüpfung
